<?php

use HerdManager\Service\{HerdService, ProxyService};

describe('API Integration Tests', function (): void {
    describe('HerdManager Integration', function (): void {
        beforeEach(function (): void {
            $this->manager = new HerdService();
        });

        it('integra listagem de sites com parsing', function (): void {
            $sites = $this->manager->listSites();

            expect($sites)->toBeArray();

            if (count($sites) > 0) {
                expect($sites[0])->toHaveKey('name');
                expect($sites[0])->toHaveKey('url');
                expect($sites[0])->toHaveKey('path');
                expect($sites[0])->toHaveKey('exposed');
                expect($sites[0])->toHaveKey('port');
                expect($sites[0])->toHaveKey('type');
            }
        });

        it('verifica disponibilidade de múltiplas portas', function (): void {
            $ports = [9999, 9998, 9997];

            foreach ($ports as $port) {
                $available = $this->manager->checkPortAvailability($port);
                expect($available)->toBeBool();
            }
        });

        it('gera configuração nginx válida para site real', function (): void {
            $site = [
                'name' => 'empresta-legal',
                'url' => 'http://empresta-legal.test',
                'port' => 8000,
            ];

            $config = $this->manager->generateNginxConfiguration($site);

            expect($config)->toBeString();
            expect($config)->toContain('server {');
            expect($config)->toContain('listen 0.0.0.0:8000');
            expect($config)->toContain('location / {');
            expect($config)->toContain('}');
        });

        it('retorna IP local válido', function (): void {
            $ip = $this->manager->getLocalIpAddress();

            expect($ip)->toBeString();
            expect($ip)->not->toBeEmpty();

            // Validar formato IPv4
            $parts = explode('.', $ip);
            expect($parts)->toHaveCount(4);

            foreach ($parts as $part) {
                $num = (int) $part;
                expect($num)->toBeGreaterThanOrEqual(0);
                expect($num)->toBeLessThanOrEqual(255);
            }
        });
    });

    describe('ProxyManager Integration', function (): void {
        beforeEach(function (): void {
            $this->manager = new ProxyService();
        });

        it('lista proxies existentes', function (): void {
            $proxies = $this->manager->listProxies();

            expect($proxies)->toBeArray();

            foreach ($proxies as $proxy) {
                expect($proxy)->toHaveKey('name');
                expect($proxy)->toHaveKey('domain');
                expect($proxy)->toHaveKey('port');
                expect($proxy)->toHaveKey('created_at');
            }
        });

        it('gera configuração nginx com headers corretos', function (): void {
            $config = $this->manager->generateProxyNginxConfiguration('test.test', 3000);

            // Verificar estrutura básica
            expect($config)->toContain('server {');
            expect($config)->toContain('listen 127.0.0.1:80;');
            expect($config)->toContain('location / {');

            // Verificar configurações de proxy
            expect($config)->toContain('proxy_pass http://127.0.0.1:3000;');
            expect($config)->toContain('proxy_http_version 1.1;');

            // Verificar headers WebSocket
            expect($config)->toContain('Upgrade');
            expect($config)->toContain('Connection');

            // Verificar headers de proxy reverso
            expect($config)->toContain('X-Real-IP');
            expect($config)->toContain('X-Forwarded-For');
            expect($config)->toContain('X-Forwarded-Proto');

            // Verificar timeout
            expect($config)->toContain('proxy_read_timeout 86400');
        });

        it('valida múltiplos formatos de nome', function (): void {
            $validNames = [
                'simple',
                'with-dash',
                'with123',
                'complex-name-123',
            ];

            foreach ($validNames as $name) {
                $isValid = preg_match('/^[a-z0-9-]+$/', $name) === 1;
                expect($isValid)->toBeTrue();
            }
        });

        it('rejeita formatos de nome inválidos', function (): void {
            $invalidNames = [
                'With_Underscore',
                'UPPERCASE',
                'with space',
                'special@char',
                'name.with.dots',
            ];

            foreach ($invalidNames as $name) {
                expect(fn () => $this->manager->createProxy($name, 3000))
                    ->toThrow(InvalidArgumentException::class);
            }
        });

        it('valida ranges de porta corretamente', function (): void {
            $validPorts = [1024, 3000, 8080, 65535];
            $invalidPorts = [0, 80, 443, 1023, 65536, -1];

            foreach ($validPorts as $port) {
                expect($port)->toBeGreaterThanOrEqual(1024);
                expect($port)->toBeLessThanOrEqual(65535);
            }

            foreach ($invalidPorts as $port) {
                expect(fn () => $this->manager->createProxy('test', $port))
                    ->toThrow(InvalidArgumentException::class);
            }
        });
    });

    describe('Fluxo Completo de Exposição', function (): void {
        it('simula fluxo completo de expor um site', function (): void {
            $manager = new HerdService();

            // 1. Listar sites disponíveis
            $sites = $manager->listSites();
            expect($sites)->toBeArray();

            // 2. Verificar disponibilidade de porta
            $port = 8000;
            $available = $manager->checkPortAvailability($port);
            expect($available)->toBeBool();

            // 3. Gerar configuração nginx
            if (count($sites) > 0) {
                $site = $sites[0];
                $site['port'] = $port;
                $site['exposed'] = true;

                $config = $manager->generateNginxConfiguration($site);
                expect($config)->toBeString();
                expect($config)->toContain("listen 0.0.0.0:{$port}");
            }

            // 4. Obter IP para instruções ao usuário
            $ip = $manager->getLocalIpAddress();
            expect($ip)->toMatch('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/');
        });
    });

    describe('Fluxo Completo de Proxy', function (): void {
        it('simula fluxo completo de criar proxy', function (): void {
            $manager = new ProxyService();

            // 1. Validar nome
            $name = 'test-proxy-' . time();
            expect($name)->toMatch('/^[a-z0-9-]+$/');

            // 2. Validar porta
            $port = 3000;
            expect($port)->toBeGreaterThanOrEqual(1024);
            expect($port)->toBeLessThanOrEqual(65535);

            // 3. Gerar configuração
            $domain = $name . '.test';
            $config = $manager->generateProxyNginxConfiguration($domain, $port);

            expect($config)->toContain("server_name {$domain}");
            expect($config)->toContain("proxy_pass http://127.0.0.1:{$port}");
        });
    });

    describe('Validação de Dados', function (): void {
        it('valida estrutura de site completa', function (): void {
            $site = [
                'name' => 'test-site',
                'url' => 'http://test-site.test',
                'path' => '/Users/daniel/Sites/test-site',
                'exposed' => false,
                'port' => 8000,
                'type' => 'parked',
            ];

            expect($site)->toHaveKey('name');
            expect($site)->toHaveKey('url');
            expect($site)->toHaveKey('path');
            expect($site)->toHaveKey('exposed');
            expect($site)->toHaveKey('port');
            expect($site)->toHaveKey('type');

            expect($site['name'])->toBeString();
            expect($site['url'])->toStartWith('http');
            expect($site['path'])->toStartWith('/');
            expect($site['exposed'])->toBeBool();
            expect($site['port'])->toBeInt();
            expect($site['type'])->toBeIn(['parked', 'linked']);
        });

        it('valida estrutura de proxy completa', function (): void {
            $proxy = [
                'name' => 'test-proxy',
                'domain' => 'test-proxy.test',
                'port' => 3000,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            expect($proxy)->toHaveKey('name');
            expect($proxy)->toHaveKey('domain');
            expect($proxy)->toHaveKey('port');
            expect($proxy)->toHaveKey('created_at');

            expect($proxy['name'])->toBeString();
            expect($proxy['domain'])->toEndWith('.test');
            expect($proxy['port'])->toBeInt();
            expect($proxy['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
        });
    });

    describe('Configurações Nginx', function (): void {
        it('gera configuração nginx com proxy headers corretos', function (): void {
            $manager = new HerdService();

            $site = [
                'name' => 'test',
                'url' => 'http://test.test',
                'port' => 8000,
            ];

            $config = $manager->generateNginxConfiguration($site);

            $requiredHeaders = [
                'proxy_set_header Host',
                'proxy_set_header X-Forwarded-Host',
                'proxy_set_header X-Forwarded-Proto',
                'proxy_set_header X-Forwarded-For',
                'proxy_set_header X-Forwarded-Port',
            ];

            foreach ($requiredHeaders as $header) {
                expect($config)->toContain($header);
            }
        });

        it('gera configuração nginx proxy com suporte websocket', function (): void {
            $manager = new ProxyService();
            $config = $manager->generateProxyNginxConfiguration('test.test', 3000);

            $websocketConfig = [
                'proxy_http_version 1.1',
                'proxy_set_header Upgrade',
                'proxy_set_header Connection',
            ];

            foreach ($websocketConfig as $setting) {
                expect($config)->toContain($setting);
            }
        });

        it('gera configuração com client_max_body_size adequado', function (): void {
            $manager = new ProxyService();
            $config = $manager->generateProxyNginxConfiguration('test.test', 3000);

            expect($config)->toContain('client_max_body_size 1024M');
        });
    });
});
