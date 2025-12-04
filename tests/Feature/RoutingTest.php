<?php

use FastRoute\Dispatcher;

describe('Sistema de Rotas FastRoute', function () {
    beforeEach(function () {
        $this->dispatcher = FastRoute\simpleDispatcher(require __DIR__ . '/../../routes/api.php');
    });

    describe('Rotas de Sites', function () {
        it('rota GET /api/sites', function () {
            $routeInfo = $this->dispatcher->dispatch('GET', '/api/sites');

            expect($routeInfo[0])->toBe(Dispatcher::FOUND);
            expect($routeInfo[1])->toBe('SiteController@list');
        });

        it('rota GET /api/sites/ip', function () {
            $routeInfo = $this->dispatcher->dispatch('GET', '/api/sites/ip');

            expect($routeInfo[0])->toBe(Dispatcher::FOUND);
            expect($routeInfo[1])->toBe('SiteController@getIp');
        });

        it('rota GET /api/sites/check-port', function () {
            $routeInfo = $this->dispatcher->dispatch('GET', '/api/sites/check-port');

            expect($routeInfo[0])->toBe(Dispatcher::FOUND);
            expect($routeInfo[1])->toBe('SiteController@checkPort');
        });

        it('rota GET /api/sites/debug', function () {
            $routeInfo = $this->dispatcher->dispatch('GET', '/api/sites/debug');

            expect($routeInfo[0])->toBe(Dispatcher::FOUND);
            expect($routeInfo[1])->toBe('SiteController@debug');
        });

        it('rota POST /api/sites/apply', function () {
            $routeInfo = $this->dispatcher->dispatch('POST', '/api/sites/apply');

            expect($routeInfo[0])->toBe(Dispatcher::FOUND);
            expect($routeInfo[1])->toBe('SiteController@apply');
        });

        it('rota POST /api/sites/status', function () {
            $routeInfo = $this->dispatcher->dispatch('POST', '/api/sites/status');

            expect($routeInfo[0])->toBe(Dispatcher::FOUND);
            expect($routeInfo[1])->toBe('SiteController@status');
        });

        it('rota POST /api/sites/test-apply', function () {
            $routeInfo = $this->dispatcher->dispatch('POST', '/api/sites/test-apply');

            expect($routeInfo[0])->toBe(Dispatcher::FOUND);
            expect($routeInfo[1])->toBe('SiteController@testApply');
        });
    });

    describe('Rotas de Proxies', function () {
        it('rota GET /api/proxies', function () {
            $routeInfo = $this->dispatcher->dispatch('GET', '/api/proxies');

            expect($routeInfo[0])->toBe(Dispatcher::FOUND);
            expect($routeInfo[1])->toBe('ProxyController@list');
        });

        it('rota POST /api/proxies', function () {
            $routeInfo = $this->dispatcher->dispatch('POST', '/api/proxies');

            expect($routeInfo[0])->toBe(Dispatcher::FOUND);
            expect($routeInfo[1])->toBe('ProxyController@create');
        });

        it('rota DELETE /api/proxies/{name}', function () {
            $routeInfo = $this->dispatcher->dispatch('DELETE', '/api/proxies/test-proxy');

            expect($routeInfo[0])->toBe(Dispatcher::FOUND);
            expect($routeInfo[1])->toBe('ProxyController@delete');
            expect($routeInfo[2])->toHaveKey('name');
            expect($routeInfo[2]['name'])->toBe('test-proxy');
        });
    });

    describe('Validação de Métodos HTTP', function () {
        it('rejeita POST em rota GET', function () {
            $routeInfo = $this->dispatcher->dispatch('POST', '/api/sites');

            expect($routeInfo[0])->toBe(Dispatcher::METHOD_NOT_ALLOWED);
        });

        it('rejeita GET em rota POST', function () {
            $routeInfo = $this->dispatcher->dispatch('GET', '/api/sites/apply');

            expect($routeInfo[0])->toBe(Dispatcher::METHOD_NOT_ALLOWED);
        });
    });

    describe('Rotas Não Encontradas', function () {
        it('retorna NOT_FOUND para rota inexistente', function () {
            $routeInfo = $this->dispatcher->dispatch('GET', '/api/invalid');

            expect($routeInfo[0])->toBe(Dispatcher::NOT_FOUND);
        });

        it('retorna NOT_FOUND para caminho vazio', function () {
            $routeInfo = $this->dispatcher->dispatch('GET', '/');

            expect($routeInfo[0])->toBe(Dispatcher::NOT_FOUND);
        });
    });

    describe('Parâmetros de Rota', function () {
        it('extrai nome do proxy da URL', function () {
            $routeInfo = $this->dispatcher->dispatch('DELETE', '/api/proxies/my-proxy-123');

            expect($routeInfo[2]['name'])->toBe('my-proxy-123');
        });

        it('aceita nomes com hífens', function () {
            $routeInfo = $this->dispatcher->dispatch('DELETE', '/api/proxies/test-proxy-name');

            expect($routeInfo[2]['name'])->toBe('test-proxy-name');
        });

        it('aceita nomes com números', function () {
            $routeInfo = $this->dispatcher->dispatch('DELETE', '/api/proxies/proxy123');

            expect($routeInfo[2]['name'])->toBe('proxy123');
        });
    });
});
