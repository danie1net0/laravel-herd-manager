<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use HerdManager\Controller\ProxyController;
use HerdManager\Controller\SiteController;
use HerdManager\Controller\WebController;
use HerdManager\Service\HerdService;
use HerdManager\Service\ProxyService;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7Server\ServerRequestCreator;

$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator(
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
    $psr17Factory
);

$request = $creator->fromGlobals();

$httpMethod = $request->getMethod();
$uri = $request->getUri()->getPath();

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

$uri = rawurldecode($uri);

$isApi = str_starts_with($uri, '/api/');

if ($isApi) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($httpMethod === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    $dispatcher = FastRoute\simpleDispatcher(require __DIR__ . '/../routes/api.php');
} else {
    $dispatcher = FastRoute\simpleDispatcher(require __DIR__ . '/../routes/web.php');
}

$herdService = new HerdService();
$proxyService = new ProxyService();

$siteController = new SiteController($herdService);
$proxyController = new ProxyController($proxyService);
$webController = new WebController();

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

try {
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            $response = new Response(
                404,
                ['Content-Type' => $isApi ? 'application/json' : 'text/html'],
                $isApi ? json_encode(['error' => 'Route not found']) : '<h1>404 Not Found</h1>'
            );
            break;

        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $response = new Response(
                405,
                ['Content-Type' => $isApi ? 'application/json' : 'text/html'],
                $isApi ? json_encode(['error' => 'Method not allowed']) : '<h1>405 Method Not Allowed</h1>'
            );
            break;

        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];

            [$controllerName, $method] = explode('@', $handler);

            $controller = match ($controllerName) {
                'SiteController' => $siteController,
                'ProxyController' => $proxyController,
                'WebController' => $webController,
                default => throw new \RuntimeException('Controller not found'),
            };

            if (empty($vars)) {
                $response = $controller->$method($request);
            } else {
                $response = $controller->$method($request, $vars);
            }
            break;

        default:
            $response = new Response(
                500,
                ['Content-Type' => $isApi ? 'application/json' : 'text/html'],
                $isApi ? json_encode(['error' => 'Unknown routing error']) : '<h1>500 Internal Server Error</h1>'
            );
    }

} catch (\Throwable $e) {
    $response = new Response(
        500,
        ['Content-Type' => $isApi ? 'application/json' : 'text/html'],
        $isApi ? json_encode([
            'error' => 'Internal server error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]) : '<h1>500 Internal Server Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>'
    );
}

http_response_code($response->getStatusCode());

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

echo $response->getBody();
