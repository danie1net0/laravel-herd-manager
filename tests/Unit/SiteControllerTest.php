<?php

use HerdManager\Controller\SiteController;
use HerdManager\Service\{HerdService, PortCheckService};
use Nyholm\Psr7\Factory\Psr17Factory;

describe('SiteController', function (): void {
    beforeEach(function (): void {
        $this->herdService = new HerdService();
        $this->portCheckService = new PortCheckService();
        $this->controller = new SiteController($this->herdService, $this->portCheckService);
        $this->psr17Factory = new Psr17Factory();
    });

    describe('list', function (): void {
        it('retorna lista de sites em JSON', function (): void {
            $request = $this->psr17Factory->createServerRequest('GET', '/api/sites');
            $response = $this->controller->list($request);

            expect($response->getStatusCode())->toBe(200);
            expect($response->getHeaderLine('Content-Type'))->toBe('application/json');

            $data = json_decode((string) $response->getBody(), true);
            expect($data)->toHaveKey('sites');
            expect($data['sites'])->toBeArray();
        });
    });

    describe('getIp', function (): void {
        it('retorna IP local em JSON', function (): void {
            $request = $this->psr17Factory->createServerRequest('GET', '/api/sites/ip');
            $response = $this->controller->getIp($request);

            expect($response->getStatusCode())->toBe(200);

            $data = json_decode((string) $response->getBody(), true);
            expect($data)->toHaveKey('ip');
            expect($data['ip'])->toMatch('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/');
        });
    });

    describe('checkPort', function (): void {
        it('valida porta disponível', function (): void {
            $request = $this->psr17Factory->createServerRequest('GET', '/api/sites/check-port?port=9999');
            $response = $this->controller->checkPort($request);

            expect($response->getStatusCode())->toBe(200);

            $data = json_decode((string) $response->getBody(), true);
            expect($data)->toHaveKey('available');
            expect($data)->toHaveKey('port');
            expect($data['port'])->toBe(9999);
        });

        it('retorna erro para porta inválida', function (): void {
            $request = $this->psr17Factory->createServerRequest('GET', '/api/sites/check-port?port=99999');
            $response = $this->controller->checkPort($request);

            expect($response->getStatusCode())->toBe(400);

            $data = json_decode((string) $response->getBody(), true);
            expect($data)->toHaveKey('available');
            expect($data)->toHaveKey('error');
            expect($data['available'])->toBeFalse();
        });

        it('retorna erro para porta zero', function (): void {
            $request = $this->psr17Factory->createServerRequest('GET', '/api/sites/check-port?port=0');
            $response = $this->controller->checkPort($request);

            expect($response->getStatusCode())->toBe(400);

            $data = json_decode((string) $response->getBody(), true);
            expect($data['available'])->toBeFalse();
        });
    });

    describe('apply', function (): void {
        it('valida dados obrigatórios', function (): void {
            $request = $this->psr17Factory->createServerRequest('POST', '/api/sites/apply')
                ->withBody($this->psr17Factory->createStream(json_encode(['invalid' => 'data'])));

            $response = $this->controller->apply($request);

            expect($response->getStatusCode())->toBe(400);

            $data = json_decode((string) $response->getBody(), true);
            expect($data)->toHaveKey('success');
            expect($data['success'])->toBeFalse();
            expect($data)->toHaveKey('error');
        });

        it('aceita array de sites válido', function (): void {
            $request = $this->psr17Factory->createServerRequest('POST', '/api/sites/apply')
                ->withBody($this->psr17Factory->createStream(json_encode(['sites' => []])));

            $response = $this->controller->apply($request);

            $data = json_decode((string) $response->getBody(), true);
            expect($data)->toHaveKey('success');
        });
    });

    describe('status', function (): void {
        it('verifica status de portas', function (): void {
            $requestData = [
                'activePorts' => [8000],
                'inactivePorts' => [9999],
            ];

            $request = $this->psr17Factory->createServerRequest('POST', '/api/sites/status')
                ->withBody($this->psr17Factory->createStream(json_encode($requestData)));

            $response = $this->controller->status($request);

            expect($response->getStatusCode())->toBe(200);

            $data = json_decode((string) $response->getBody(), true);
            expect($data)->toHaveKey('running');
            expect($data)->toHaveKey('activePorts');
            expect($data)->toHaveKey('inactivePorts');
        });

        it('retorna estrutura correta quando não há portas', function (): void {
            $request = $this->psr17Factory->createServerRequest('POST', '/api/sites/status')
                ->withBody($this->psr17Factory->createStream(json_encode(['activePorts' => [], 'inactivePorts' => []])));

            $response = $this->controller->status($request);

            $data = json_decode((string) $response->getBody(), true);
            expect($data['running'])->toBeBool();
            expect($data['activePorts'])->toBeArray();
            expect($data['inactivePorts'])->toBeArray();
        });
    });

    describe('testApply', function (): void {
        it('retorna informações de debug', function (): void {
            $request = $this->psr17Factory->createServerRequest('POST', '/api/sites/test-apply')
                ->withBody($this->psr17Factory->createStream(json_encode(['sites' => []])));

            $response = $this->controller->testApply($request);

            expect($response->getStatusCode())->toBe(200);

            $data = json_decode((string) $response->getBody(), true);
            expect($data)->toHaveKey('success');
            expect($data)->toHaveKey('debug');
            expect($data['debug'])->toHaveKey('sites_count');
            expect($data['debug'])->toHaveKey('nginx_dir');
        });
    });
});
