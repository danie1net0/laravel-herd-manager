<?php

declare(strict_types=1);

namespace HerdManager\Controller;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use HerdManager\Service\ProxyService;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

readonly class ProxyController extends AbstractController
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
            $body = $this->parseJsonBody($request);

            $name = $body['name'] ?? '';
            $port = $body['port'] ?? 0;

            if (! is_string($name)) {
                $name = '';
            }

            if (! is_int($port)) {
                $port = is_numeric($port) ? (int) $port : 0;
            }

            $proxyData = $this->proxyService->createProxy($name, $port);

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
        } catch (Exception) {
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
        } catch (Exception) {
            return $this->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }
}
