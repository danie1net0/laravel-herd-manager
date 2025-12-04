<?php

declare(strict_types=1);

namespace HerdManager\Controller;

use Exception;
use HerdManager\Service\{HerdService, PortCheckService};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

readonly class SiteController extends AbstractController
{
    public function __construct(
        private HerdService $herdService,
        private PortCheckService $portCheckService
    ) {}

    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $sitesList = $this->herdService->listSites();

        return $this->json([
            'sites' => $sitesList,
        ]);
    }

    public function getIp(ServerRequestInterface $request): ResponseInterface
    {
        $ipAddress = $this->herdService->getLocalIpAddress();

        return $this->json([
            'ip' => $ipAddress,
        ]);
    }

    public function apply(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $body = $this->parseJsonBody($request);
            $sites = $body['sites'] ?? [];

            if (! is_array($sites) || $sites === []) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid sites data',
                ], 400);
            }

            /** @var array<int, array{name: string, url: string, port: int}> $sites */
            $applySuccess = $this->herdService->applyChanges($sites);

            if (! $applySuccess) {
                return $this->json([
                    'success' => false,
                    'error' => 'Failed to apply configurations',
                ], 500);
            }

            return $this->json([
                'success' => true,
                'message' => 'Configurations applied successfully',
            ]);
        } catch (Exception $exception) {
            return $this->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function checkPort(ServerRequestInterface $request): ResponseInterface
    {
        $queryParameters = $request->getQueryParams();
        $portNumber = (int) ($queryParameters['port'] ?? 0);

        if ($portNumber < 1 || $portNumber > 65535) {
            return $this->json([
                'available' => false,
                'error' => 'Invalid port',
            ], 400);
        }

        $isAvailable = $this->herdService->checkPortAvailability($portNumber);

        return $this->json([
            'available' => $isAvailable,
            'port' => $portNumber,
        ]);
    }

    public function status(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->parseJsonBody($request);

        $activePorts = $body['activePorts'] ?? [];
        $inactivePorts = $body['inactivePorts'] ?? [];

        $activePortsList = [];
        $inactivePortsList = [];

        if (is_array($activePorts)) {
            foreach ($activePorts as $port) {
                if (is_int($port) || is_numeric($port)) {
                    $activePortsList[] = (int) $port;
                }
            }
        }

        if (is_array($inactivePorts)) {
            foreach ($inactivePorts as $port) {
                if (is_int($port) || is_numeric($port)) {
                    $inactivePortsList[] = (int) $port;
                }
            }
        }

        $activePortsStatus = $this->portCheckService->checkActivePortsStatus($activePortsList);
        $inactivePortsStatus = $this->portCheckService->checkInactivePortsStatus($inactivePortsList);

        $allActivePortsReady = $activePortsList === [] || ! in_array(false, $activePortsStatus, strict: true);
        $allInactivePortsStopped = $inactivePortsList === [] || ! in_array(false, $inactivePortsStatus, strict: true);

        $systemReady = $allActivePortsReady && $allInactivePortsStopped;

        return $this->json([
            'running' => $systemReady,
            'activePorts' => $activePortsStatus,
            'inactivePorts' => $inactivePortsStatus,
        ]);
    }

    public function debug(ServerRequestInterface $request): ResponseInterface
    {
        $homeDirectory = $this->getHomeDirectory();
        $herdExecutablePath = $homeDirectory . '/Library/Application Support/Herd/bin/herd';
        $pathVariable = sprintf('PATH="%s/Library/Application Support/Herd/bin:$PATH" ', $homeDirectory);
        exec($pathVariable . escapeshellarg($herdExecutablePath) . " parked 2>&1", $commandOutput, $exitCode);

        return $this->json([
            'output' => $commandOutput,
            'returnCode' => $exitCode,
        ]);
    }

    public function testApply(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $body = $this->parseJsonBody($request);
            $sites = $body['sites'] ?? [];

            $homeDirectory = $this->getHomeDirectory();
            $nginxConfigurationDirectory = $homeDirectory . '/Library/Application Support/Herd/config/nginx/';
            $nginxMainConfigurationPath = $nginxConfigurationDirectory . 'nginx.conf';

            $debugResult = [
                'step' => 'start',
                'sites_count' => is_array($sites) ? count($sites) : 0,
                'nginx_dir' => $nginxConfigurationDirectory,
                'nginx_config_exists' => file_exists($nginxMainConfigurationPath),
                'nginx_config_writable' => is_writable($nginxMainConfigurationPath),
            ];

            return $this->json([
                'success' => true,
                'debug' => $debugResult,
            ]);
        } catch (Exception $exception) {
            return $this->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
