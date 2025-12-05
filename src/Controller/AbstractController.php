<?php

declare(strict_types=1);

namespace HerdManager\Controller;

use Nyholm\Psr7\Response;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

abstract readonly class AbstractController
{
    /**
     * @param array<string, mixed> $responseData
     */
    protected function json(array $responseData, int $statusCode = 200): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode($responseData) ?: '{}'
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseJsonBody(ServerRequestInterface $request): array
    {
        $body = (string) $request->getBody();
        $decoded = json_decode($body, associative: true);

        if (! is_array($decoded)) {
            return [];
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    protected function getHomeDirectory(): string
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
}
