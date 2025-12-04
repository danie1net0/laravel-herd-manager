<?php

declare(strict_types=1);

namespace HerdManager\Controller;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use HerdManager\Service\ProxyService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response;

readonly class ProxyController
{
    public function __construct(
        private ProxyService $proxyService
    ) {}

    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $proxiesList = $this->proxyService->listProxies();

        return $this->json([
            'proxies' => $proxiesList,
        ]);
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $requestData = json_decode((string) $request->getBody(), associative: true);

            $proxyName = $requestData['name'] ?? '';
            $portNumber = (int) ($requestData['port'] ?? 0);

            $proxyData = $this->proxyService->createProxy($proxyName, $portNumber);

            return $this->json([
                'success' => true,
                'proxy' => $proxyData,
            ], 201);

        } catch (InvalidArgumentException $exception) {
            return $this->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 400);

        } catch (RuntimeException $exception) {
            return $this->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 409);

        } catch (Exception $exception) {
            return $this->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * @param array<string, string> $routeVariables
     */
    public function delete(ServerRequestInterface $request, array $routeVariables): ResponseInterface
    {
        try {
            $proxyName = $routeVariables['name'] ?? '';

            $this->proxyService->deleteProxy($proxyName);

            return $this->json([
                'success' => true,
            ]);

        } catch (InvalidArgumentException $exception) {
            return $this->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 400);

        } catch (RuntimeException $exception) {
            return $this->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 404);

        } catch (Exception $exception) {
            return $this->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
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
