<?php

declare(strict_types=1);

namespace HerdManager\Controller;

use Exception;
use HerdManager\Service\HerdService;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Nyholm\Psr7\Response;

readonly class SiteController
{
    public function __construct(
        private HerdService $herdService
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
            $requestData = json_decode((string) $request->getBody(), associative: true);

            if (! isset($requestData['sites']) || ! is_array($requestData['sites'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid sites data',
                ], 400);
            }

            $applySuccess = $this->herdService->applyChanges($requestData['sites']);

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
        $requestData = json_decode((string) $request->getBody(), associative: true);

        $activePortsList = $requestData['activePorts'] ?? [];
        $inactivePortsList = $requestData['inactivePorts'] ?? [];

        $activePortsStatus = $this->checkActivePortsStatus($activePortsList);
        $inactivePortsStatus = $this->checkInactivePortsStatus($inactivePortsList);

        $allActivePortsReady = empty($activePortsList) || ! in_array(false, $activePortsStatus, strict: true);
        $allInactivePortsStopped = empty($inactivePortsList) || ! in_array(false, $inactivePortsStatus, strict: true);

        $systemReady = $allActivePortsReady && $allInactivePortsStopped;

        return $this->json([
            'running' => $systemReady,
            'activePorts' => $activePortsStatus,
            'inactivePorts' => $inactivePortsStatus,
        ]);
    }

    public function debug(ServerRequestInterface $request): ResponseInterface
    {
        $herdExecutablePath = $_SERVER['HOME'] . '/Library/Application Support/Herd/bin/herd';
        exec("PATH=\"{$_SERVER['HOME']}/Library/Application Support/Herd/bin:\$PATH\" " . escapeshellarg($herdExecutablePath) . " parked 2>&1", $commandOutput, $exitCode);

        return $this->json([
            'output' => $commandOutput,
            'returnCode' => $exitCode,
        ]);
    }

    public function testApply(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $requestData = json_decode((string) $request->getBody(), associative: true);
            $sitesList = $requestData['sites'] ?? [];

            $nginxConfigurationDirectory = $_SERVER['HOME'] . '/Library/Application Support/Herd/config/nginx/';
            $nginxMainConfigurationPath = $nginxConfigurationDirectory . 'nginx.conf';

            $debugResult = [
                'step' => 'start',
                'sites_count' => count($sitesList),
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

    /**
     * @param array<int, int> $portsList
     * @return array<int, bool>
     */
    private function checkActivePortsStatus(array $portsList): array
    {
        $portsStatus = [];

        foreach ($portsList as $portNumber) {
            $socketConnection = @fsockopen('127.0.0.1', $portNumber, timeout: 1);

            if (! $socketConnection) {
                $portsStatus[$portNumber] = false;

                continue;
            }

            $portsStatus[$portNumber] = true;
            fclose($socketConnection);
        }

        return $portsStatus;
    }

    /**
     * @param array<int, int> $portsList
     * @return array<int, bool>
     */
    private function checkInactivePortsStatus(array $portsList): array
    {
        $portsStatus = [];

        foreach ($portsList as $portNumber) {
            $socketConnection = @fsockopen('127.0.0.1', $portNumber, timeout: 1);

            if (! $socketConnection) {
                $portsStatus[$portNumber] = true;

                continue;
            }

            $portsStatus[$portNumber] = false;
            fclose($socketConnection);
        }

        return $portsStatus;
    }

    /**
     * @param array<string, mixed> $responseData
     */
    private function json(array $responseData, int $statusCode = 200): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode($responseData)
        );
    }
}
