<?php

declare(strict_types=1);

namespace HerdManager\Service;

readonly class UpdateService
{
    private string $projectRoot;

    public function __construct()
    {
        $this->projectRoot = dirname(__DIR__, 2);
    }

    /**
     * @return array{available: bool, current: string|null, latest: string|null, behind: int}
     */
    public function checkForUpdates(): array
    {
        $currentCommit = $this->getCurrentCommit();
        $latestCommit = $this->getLatestRemoteCommit();
        $behindCount = 0;

        if ($currentCommit !== null && $latestCommit !== null && $currentCommit !== $latestCommit) {
            $behindCount = $this->getCommitsBehind($currentCommit, $latestCommit);
        }

        return [
            'available' => $behindCount > 0,
            'current' => $currentCommit,
            'latest' => $latestCommit,
            'behind' => $behindCount,
        ];
    }

    /**
     * @return array{url: string|null, branch: string|null}
     */
    public function getRepositoryInfo(): array
    {
        $remoteUrl = $this->getRemoteUrl();
        $currentBranch = $this->getCurrentBranch();

        return [
            'url' => $remoteUrl,
            'branch' => $currentBranch,
        ];
    }

    private function getCurrentCommit(): ?string
    {
        $command = sprintf(
            'cd %s && git rev-parse HEAD 2>/dev/null',
            escapeshellarg($this->projectRoot)
        );

        $output = shell_exec($command);

        if ($output === null || $output === false) {
            return null;
        }

        return mb_trim($output) ?: null;
    }

    private function getLatestRemoteCommit(): ?string
    {
        $fetchCommand = sprintf(
            'cd %s && git fetch origin 2>&1',
            escapeshellarg($this->projectRoot)
        );

        shell_exec($fetchCommand);

        $command = sprintf(
            'cd %s && git rev-parse origin/$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "master") 2>/dev/null',
            escapeshellarg($this->projectRoot)
        );

        $output = shell_exec($command);

        if ($output === null || $output === false) {
            return null;
        }

        return mb_trim($output) ?: null;
    }

    private function getCommitsBehind(string $currentCommit, string $latestCommit): int
    {
        $command = sprintf(
            'cd %s && git rev-list --count %s..%s 2>/dev/null',
            escapeshellarg($this->projectRoot),
            escapeshellarg($currentCommit),
            escapeshellarg($latestCommit)
        );

        $output = shell_exec($command);

        if ($output === null || $output === false) {
            return 0;
        }

        return (int) mb_trim($output);
    }

    private function getRemoteUrl(): ?string
    {
        $command = sprintf(
            'cd %s && git config --get remote.origin.url 2>/dev/null',
            escapeshellarg($this->projectRoot)
        );

        $output = shell_exec($command);

        if ($output === null || $output === false) {
            return null;
        }

        return mb_trim($output) ?: null;
    }

    private function getCurrentBranch(): ?string
    {
        $command = sprintf(
            'cd %s && git rev-parse --abbrev-ref HEAD 2>/dev/null',
            escapeshellarg($this->projectRoot)
        );

        $output = shell_exec($command);

        if ($output === null || $output === false) {
            return null;
        }

        return mb_trim($output) ?: null;
    }
}
