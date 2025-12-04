<?php

declare(strict_types=1);

namespace HerdManager\Service;

use InvalidArgumentException;
use RuntimeException;

final class ProxyService
{
    public string $proxiesFile {
        get => $this->homeDirectory . '/.herd-manager-proxies.json';
    }

    public string $valetNginxDirectory {
        get => $this->homeDirectory . '/Library/Application Support/Herd/config/valet/Nginx/';
    }

    public string $herdBinaryPath {
        get => $this->homeDirectory . '/Library/Application Support/Herd/bin';
    }

    private readonly string $homeDirectory;

    private readonly string $templatesDirectory;

    private readonly CommandTemplateService $commandTemplateService;

    public function __construct(
        ?string $homeDirectory = null,
        ?string $templatesDirectory = null,
        ?CommandTemplateService $commandTemplateService = null
    ) {
        $this->homeDirectory = $homeDirectory ?? $this->getHomeDirectory();
        $this->templatesDirectory = $templatesDirectory ?? __DIR__ . '/../../templates';
        $this->commandTemplateService = $commandTemplateService ?? new CommandTemplateService($this->templatesDirectory);
    }

    /**
     * @return array<int, array{name: string, domain: string, port: int, created_at: string}>
     */
    public function listProxies(): array
    {
        return array_values($this->loadProxies());
    }

    /**
     * @return array{name: string, domain: string, port: int, created_at: string}
     */
    public function createProxy(string $proxyName, int $portNumber): array
    {
        match (true) {
            $proxyName === '' || $proxyName === '0' || $portNumber === 0 => throw new InvalidArgumentException('Name and port are required'),
            ! preg_match('/^[a-z0-9-]+$/', $proxyName) => throw new InvalidArgumentException('Name must contain only lowercase letters, numbers and hyphens'),
            $portNumber < 1024 || $portNumber > 65535 => throw new InvalidArgumentException('Port must be between 1024 and 65535'),
            default => null,
        };

        $proxyList = $this->loadProxies();

        if (isset($proxyList[$proxyName])) {
            throw new RuntimeException('Proxy with this name already exists');
        }

        $domainName = $proxyName . '.test';
        $configurationFilePath = $this->valetNginxDirectory . $domainName;

        file_put_contents(
            $configurationFilePath,
            $this->generateProxyNginxConfiguration($domainName, $portNumber)
        );

        $proxyData = [
            'name' => $proxyName,
            'domain' => $domainName,
            'port' => $portNumber,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $proxyList[$proxyName] = $proxyData;
        $this->saveProxies($proxyList);
        $this->restartNginx();

        return $proxyData;
    }

    public function deleteProxy(string $proxyName): bool
    {
        if ($proxyName === '' || $proxyName === '0') {
            throw new InvalidArgumentException('Name is required');
        }

        $proxyList = $this->loadProxies();

        if (! isset($proxyList[$proxyName])) {
            throw new RuntimeException('Proxy not found');
        }

        $configurationFilePath = $this->valetNginxDirectory . $proxyList[$proxyName]['domain'];

        if (file_exists($configurationFilePath)) {
            unlink($configurationFilePath);
        }

        unset($proxyList[$proxyName]);
        $this->saveProxies($proxyList);
        $this->restartNginx();

        return true;
    }

    public function generateProxyNginxConfiguration(string $domainName, int $portNumber): string
    {
        $templatePath = $this->templatesDirectory . '/proxy-nginx.conf';
        $templateContent = file_get_contents($templatePath);

        if ($templateContent === false) {
            throw new RuntimeException('Failed to read template file: ' . $templatePath);
        }

        return str_replace(
            ['{{DOMAIN}}', '{{PORT}}'],
            [$domainName, (string) $portNumber],
            $templateContent
        );
    }

    private function getHomeDirectory(): string
    {
        $home = $_SERVER['HOME'] ?? getenv('HOME');

        if (is_string($home) && $home !== '') {
            return $home;
        }

        $userInfo = posix_getpwuid(posix_getuid());

        if ($userInfo !== false) {
            return $userInfo['dir'];
        }

        return '/tmp';
    }

    /**
     * @return array<string, array{name: string, domain: string, port: int, created_at: string}>
     */
    private function loadProxies(): array
    {
        if (! file_exists($this->proxiesFile)) {
            return [];
        }

        $fileContent = file_get_contents($this->proxiesFile);

        if ($fileContent === false) {
            return [];
        }

        $decoded = json_decode($fileContent, associative: true);

        if (! is_array($decoded)) {
            return [];
        }

        /** @var array<string, array{name: string, domain: string, port: int, created_at: string}> */
        return $decoded;
    }

    /**
     * @param array<string, array{name: string, domain: string, port: int, created_at: string}> $proxyList
     */
    private function saveProxies(array $proxyList): void
    {
        file_put_contents(
            $this->proxiesFile,
            json_encode($proxyList, JSON_PRETTY_PRINT)
        );
    }

    private function restartNginx(): void
    {
        $restartCommand = $this->commandTemplateService->render('restart_nginx', [
            'herd_bin_path' => $this->herdBinaryPath,
        ]);

        shell_exec($restartCommand);
    }
}
