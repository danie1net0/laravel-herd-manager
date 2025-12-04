<?php

declare(strict_types=1);

namespace HerdManager\Service;

use Throwable;

final class HerdService
{
    public string $herdBinaryPath {
        get => $this->homeDirectory . '/Library/Application Support/Herd/bin';
    }

    public string $nginxConfigurationDirectory {
        get => $this->homeDirectory . '/Library/Application Support/Herd/config/nginx/';
    }

    public string $valetNginxDirectory {
        get => $this->homeDirectory . '/Library/Application Support/Herd/config/valet/Nginx/';
    }

    private readonly string $homeDirectory;

    private readonly string $templatesDirectory;

    private readonly CommandTemplateService $commandTemplateService;

    public function __construct(
        ?string $homeDirectory = null,
        ?string $templatesDirectory = null,
        ?CommandTemplateService $commandTemplateService = null
    ) {
        $this->homeDirectory = $homeDirectory ?? $_SERVER['HOME'] ?? getenv('HOME') ?? posix_getpwuid(posix_getuid())['dir'];
        $this->templatesDirectory = $templatesDirectory ?? __DIR__ . '/../../templates';
        $this->commandTemplateService = $commandTemplateService ?? new CommandTemplateService($this->templatesDirectory);
    }

    /**
     * @return array<int, array{name: string, url: string, path: string, exposed: bool, port: int, type: string}>
     */
    public function listSites(): array
    {
        $sites = [
            ...$this->getParkedSites(),
            ...$this->getLinkedSites(),
        ];

        usort($sites, fn (array $firstSite, array $secondSite): int => strcmp($firstSite['name'], $secondSite['name']));

        return $sites;
    }

    /**
     * @param array<int, string> $commandOutput
     * @return array<int, array{name: string, url: string, path: string, exposed: bool, port: int, type: string}>
     */
    public function parseSitesList(array $commandOutput, string $siteType): array
    {
        $sites = [];
        $regexPattern = '/\|\s+([a-z0-9\.-]+)\s+\|\s+(\S*)\s+\|\s+(https?:\/\/[^\s]+)\s+\|\s+([^\|]+?)\s+\|/';

        foreach ($commandOutput as $outputLine) {
            if (! preg_match($regexPattern, $outputLine, $regexMatches)) {
                continue;
            }

            $siteName = mb_trim($regexMatches[1]);
            $configurationFilePath = $this->getConfigurationPath($siteName);
            $isExposed = file_exists($configurationFilePath);

            $sites[] = [
                'name' => $siteName,
                'url' => mb_trim($regexMatches[3]),
                'path' => mb_trim($regexMatches[4]),
                'exposed' => $isExposed,
                'port' => $isExposed ? $this->getPortFromConfiguration($configurationFilePath) : 8000,
                'type' => $siteType,
            ];
        }

        return $sites;
    }

    public function getConfigurationPath(string $siteName): string
    {
        return $this->nginxConfigurationDirectory . $siteName . '-local.conf';
    }

    public function checkPortAvailability(int $portNumber): bool
    {
        if ($portNumber < 1 || $portNumber > 65535) {
            return false;
        }

        $socketConnection = @fsockopen('127.0.0.1', $portNumber, timeout: 1);

        if ($socketConnection === false) {
            return true;
        }

        fclose($socketConnection);

        return false;
    }

    /**
     * @param array{name: string, url: string, port: int} $siteData
     */
    public function generateNginxConfiguration(array $siteData): string
    {
        $portNumber = $siteData['port'];
        $domainName = preg_replace('/^https?:\/\//', '', $siteData['url']);

        $templatePath = $this->templatesDirectory . '/site-nginx.conf';
        $templateContent = file_get_contents($templatePath);

        return str_replace(
            ['{{PORT}}', '{{DOMAIN}}'],
            [$portNumber, $domainName],
            $templateContent
        );
    }

    /**
     * @param array<int, array{name: string, url: string, port: int}> $sitesData
     */
    public function applyChanges(array $sitesData): bool
    {
        try {
            $activeConfigurationFiles = array_map(
                fn (array $siteData): string => basename($this->getConfigurationPath($siteData['name'])),
                $sitesData
            );

            foreach ($sitesData as $siteData) {
                file_put_contents(
                    $this->getConfigurationPath($siteData['name']),
                    $this->generateNginxConfiguration($siteData)
                );
            }

            $this->removeInactiveConfigurations($activeConfigurationFiles);
            $this->updateMainNginxConfiguration($activeConfigurationFiles);
            $this->restartNginx();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param array<int, string> $activeConfigurationFiles
     */
    public function updateMainNginxConfiguration(array $activeConfigurationFiles): void
    {
        $mainConfigurationFile = $this->nginxConfigurationDirectory . 'nginx.conf';
        $fileContent = file_get_contents($mainConfigurationFile);
        $contentLines = explode("\n", $fileContent);
        $newContentLines = [];
        $herdConfigurationFound = false;

        foreach ($contentLines as $contentLine) {
            $isLocalConfigurationLine = preg_match('/([a-z0-9-]+-local\.conf)/', $contentLine, $configurationMatch);

            if ($isLocalConfigurationLine) {
                $this->processLocalConfigurationLine($contentLine, $configurationMatch, $activeConfigurationFiles, $newContentLines);

                continue;
            }

            $newContentLines[] = $contentLine;

            if ($herdConfigurationFound || ! str_contains($contentLine, 'include herd.conf;')) {
                continue;
            }

            $herdConfigurationFound = true;
            $this->addActiveConfigurationsToNginx($activeConfigurationFiles, $newContentLines);
            $activeConfigurationFiles = [];
        }

        file_put_contents($mainConfigurationFile, implode("\n", $newContentLines));
    }

    public function getLocalIpAddress(): string
    {
        $getIpCommand = $this->commandTemplateService->render('get_local_ip');
        exec($getIpCommand, $commandOutput);

        return $commandOutput[0] ?? '127.0.0.1';
    }

    /**
     * @return array<int, array{name: string, url: string, path: string, exposed: bool, port: int, type: string}>
     */
    private function getParkedSites(): array
    {
        $commandOutput = $this->executeHerdCommand('parked');

        return $this->parseSitesList($commandOutput, 'parked');
    }

    /**
     * @return array<int, array{name: string, url: string, path: string, exposed: bool, port: int, type: string}>
     */
    private function getLinkedSites(): array
    {
        $commandOutput = $this->executeHerdCommand('links');

        return $this->parseSitesList($commandOutput, 'linked');
    }

    /**
     * @return array<int, string>
     */
    private function executeHerdCommand(string $command): array
    {
        $herdExecutablePath = $this->herdBinaryPath . '/herd';
        $fullHerdCommand = $this->commandTemplateService->render('herd_command', [
            'herd_bin_path' => escapeshellarg($this->herdBinaryPath),
            'herd_executable' => escapeshellarg($herdExecutablePath),
            'command' => escapeshellcmd($command),
        ]);

        exec($fullHerdCommand, $commandOutput, $exitCode);

        return $exitCode === 0 ? $commandOutput : [];
    }

    private function getPortFromConfiguration(string $configurationFilePath): int
    {
        $fileContent = file_get_contents($configurationFilePath);

        if (preg_match('/listen 0\.0\.0\.0:(\d+);/', $fileContent, $portMatches)) {
            return (int) $portMatches[1];
        }

        return 8000;
    }

    /**
     * @param array<int, string> $activeConfigurationFiles
     */
    private function removeInactiveConfigurations(array $activeConfigurationFiles): void
    {
        foreach (glob($this->nginxConfigurationDirectory . '*-local.conf') ?: [] as $configurationFile) {
            if (in_array(basename($configurationFile), $activeConfigurationFiles, strict: true)) {
                continue;
            }

            unlink($configurationFile);
        }
    }

    /**
     * @param array<int|string, string> $configurationMatch
     * @param array<int, string> $activeConfigurationFiles
     * @param array<int, string> $newContentLines
     */
    private function processLocalConfigurationLine(
        string $contentLine,
        array $configurationMatch,
        array &$activeConfigurationFiles,
        array &$newContentLines
    ): void {
        if (! in_array($configurationMatch[1], $activeConfigurationFiles, strict: true)) {
            return;
        }

        $newContentLines[] = $contentLine;
        $activeConfigurationFiles = array_diff($activeConfigurationFiles, [$configurationMatch[1]]);
    }

    /**
     * @param array<int, string> $activeConfigurationFiles
     * @param array<int, string> $newContentLines
     */
    private function addActiveConfigurationsToNginx(array $activeConfigurationFiles, array &$newContentLines): void
    {
        foreach ($activeConfigurationFiles as $configurationFile) {
            $newContentLines[] = $this->commandTemplateService->render('nginx_include', [
                'config_file' => $configurationFile,
            ]);
        }
    }

    private function restartNginx(): void
    {
        $restartCommand = $this->commandTemplateService->render('restart_nginx', [
            'herd_bin_path' => $this->herdBinaryPath,
        ]);

        shell_exec($restartCommand);
    }
}
