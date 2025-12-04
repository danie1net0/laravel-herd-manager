<?php

declare(strict_types=1);

namespace HerdManager\Controller;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Nyholm\Psr7\Response;

class WebController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $html = file_get_contents(__DIR__ . '/../../views/index.html');

        if ($html === false) {
            $html = '<h1>Error loading page</h1>';
        }

        return new Response(
            200,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $html
        );
    }
}
