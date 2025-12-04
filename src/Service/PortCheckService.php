<?php

declare(strict_types=1);

namespace HerdManager\Service;

readonly class PortCheckService
{
    public function __construct(
        private string $host = '127.0.0.1',
        private int $timeout = 1
    ) {}

    /**
     * @param array<int, int> $portsList
     * @return array<int, bool>
     */
    public function checkActivePortsStatus(array $portsList): array
    {
        $portsStatus = [];

        foreach ($portsList as $portNumber) {
            $socketConnection = @fsockopen($this->host, $portNumber, timeout: $this->timeout);

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
    public function checkInactivePortsStatus(array $portsList): array
    {
        $portsStatus = [];

        foreach ($portsList as $portNumber) {
            $socketConnection = @fsockopen($this->host, $portNumber, timeout: $this->timeout);

            if (! $socketConnection) {
                $portsStatus[$portNumber] = true;

                continue;
            }

            $portsStatus[$portNumber] = false;
            fclose($socketConnection);
        }

        return $portsStatus;
    }

    public function isPortAvailable(int $port): bool
    {
        $socketConnection = @fsockopen($this->host, $port, timeout: $this->timeout);

        if (! $socketConnection) {
            return true;
        }

        fclose($socketConnection);

        return false;
    }
}
