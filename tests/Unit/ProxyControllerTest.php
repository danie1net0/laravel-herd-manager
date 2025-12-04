<?php

use HerdManager\Controller\ProxyController;
use HerdManager\Service\ProxyService;
use Nyholm\Psr7\Factory\Psr17Factory;

describe('ProxyController', function (): void {
    beforeEach(function (): void {
        $this->proxyService = new ProxyService();
        $this->controller = new ProxyController($this->proxyService);
        $this->psr17Factory = new Psr17Factory();
    });

    describe('list', function (): void {
        it('retorna lista de proxies em JSON', function (): void {
            $request = $this->psr17Factory->createServerRequest('GET', '/api/proxies');
            $response = $this->controller->list($request);

            expect($response->getStatusCode())->toBe(200);
            expect($response->getHeaderLine('Content-Type'))->toBe('application/json');

            $data = json_decode((string) $response->getBody(), true);
            expect($data)->toHaveKey('proxies');
            expect($data['proxies'])->toBeArray();
        });
    });

    describe('create', function (): void {
        it('retorna erro 400 para nome vazio', function (): void {
            $request = $this->psr17Factory->createServerRequest('POST', '/api/proxies')
                ->withBody($this->psr17Factory->createStream(json_encode(['name' => '', 'port' => 3000])));

            $response = $this->controller->create($request);

            expect($response->getStatusCode())->toBe(400);

            $data = json_decode((string) $response->getBody(), true);
            expect($data)->toHaveKey('success');
            expect($data['success'])->toBeFalse();
            expect($data)->toHaveKey('error');
        });

        it('retorna erro 400 para porta inválida', function (): void {
            $request = $this->psr17Factory->createServerRequest('POST', '/api/proxies')
                ->withBody($this->psr17Factory->createStream(json_encode(['name' => 'test', 'port' => 99999])));

            $response = $this->controller->create($request);

            expect($response->getStatusCode())->toBe(400);

            $data = json_decode((string) $response->getBody(), true);
            expect($data['success'])->toBeFalse();
        });

        it('retorna erro 400 para formato de nome inválido', function (): void {
            $request = $this->psr17Factory->createServerRequest('POST', '/api/proxies')
                ->withBody($this->psr17Factory->createStream(json_encode(['name' => 'Invalid_Name', 'port' => 3000])));

            $response = $this->controller->create($request);

            expect($response->getStatusCode())->toBe(400);
        });
    });

    describe('delete', function (): void {
        it('retorna erro 400 para nome vazio', function (): void {
            $request = $this->psr17Factory->createServerRequest('DELETE', '/api/proxies/');

            $response = $this->controller->delete($request, ['name' => '']);

            expect($response->getStatusCode())->toBe(400);

            $data = json_decode((string) $response->getBody(), true);
            expect($data)->toHaveKey('success');
            expect($data['success'])->toBeFalse();
        });

        it('retorna erro 404 para proxy não encontrado', function (): void {
            $request = $this->psr17Factory->createServerRequest('DELETE', '/api/proxies/non-existent');

            $response = $this->controller->delete($request, ['name' => 'non-existent-proxy-' . time()]);

            expect($response->getStatusCode())->toBe(404);

            $data = json_decode((string) $response->getBody(), true);
            expect($data['success'])->toBeFalse();
        });
    });

    describe('HTTP Status Codes', function (): void {
        it('usa código 200 para listagem', function (): void {
            $request = $this->psr17Factory->createServerRequest('GET', '/api/proxies');
            $response = $this->controller->list($request);
            expect($response->getStatusCode())->toBe(200);
        });

        it('usa código 400 para bad request', function (): void {
            $request = $this->psr17Factory->createServerRequest('POST', '/api/proxies')
                ->withBody($this->psr17Factory->createStream(json_encode(['name' => '', 'port' => 0])));

            $response = $this->controller->create($request);
            expect($response->getStatusCode())->toBe(400);
        });

        it('usa código 404 para recurso não encontrado', function (): void {
            $request = $this->psr17Factory->createServerRequest('DELETE', '/api/proxies/non-existent');

            $response = $this->controller->delete($request, ['name' => 'non-existent']);

            expect($response->getStatusCode())->toBe(404);
        });
    });

    describe('JSON Response Format', function (): void {
        it('sempre retorna JSON válido na listagem', function (): void {
            $request = $this->psr17Factory->createServerRequest('GET', '/api/proxies');
            $response = $this->controller->list($request);
            $content = (string) $response->getBody();

            expect(json_decode($content))->not->toBeNull();
        });

        it('sempre retorna JSON válido em erros', function (): void {
            $request = $this->psr17Factory->createServerRequest('POST', '/api/proxies')
                ->withBody($this->psr17Factory->createStream(json_encode(['name' => ''])));

            $response = $this->controller->create($request);
            $content = (string) $response->getBody();

            expect(json_decode($content))->not->toBeNull();
        });
    });
});
