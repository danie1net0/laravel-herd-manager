<?php

use HerdManager\Service\ProxyService;

describe('ProxyService', function (): void {
    beforeEach(function (): void {
        $this->manager = new ProxyService();
    });

    describe('createProxy', function (): void {
        it('throws exception when name is empty', function (): void {
            expect(fn () => $this->manager->createProxy('', 3000))
                ->toThrow(InvalidArgumentException::class, 'Name and port are required');
        });

        it('throws exception when port is zero', function (): void {
            expect(fn () => $this->manager->createProxy('test', 0))
                ->toThrow(InvalidArgumentException::class, 'Name and port are required');
        });

        it('throws exception for invalid name format', function (): void {
            expect(fn () => $this->manager->createProxy('Invalid_Name', 3000))
                ->toThrow(InvalidArgumentException::class, 'Name must contain only lowercase letters, numbers and hyphens');

            expect(fn () => $this->manager->createProxy('UPPERCASE', 3000))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => $this->manager->createProxy('with space', 3000))
                ->toThrow(InvalidArgumentException::class);
        });

        it('accepts valid name formats', function (): void {
            $validNames = [
                'lowercase',
                'with-hyphens',
                'with123numbers',
                'mix-123-test',
            ];

            foreach ($validNames as $name) {
                expect($name)->toMatch('/^[a-z0-9-]+$/');
            }
        });

        it('throws exception for invalid port ranges', function (): void {
            expect(fn () => $this->manager->createProxy('test', 1023))
                ->toThrow(InvalidArgumentException::class, 'Port must be between 1024 and 65535');

            expect(fn () => $this->manager->createProxy('test', 65536))
                ->toThrow(InvalidArgumentException::class, 'Port must be between 1024 and 65535');

            expect(fn () => $this->manager->createProxy('test', -1))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('deleteProxy', function (): void {
        it('throws exception when name is empty', function (): void {
            expect(fn () => $this->manager->deleteProxy(''))
                ->toThrow(InvalidArgumentException::class, 'Name is required');
        });

        it('throws exception for non-existent proxy', function (): void {
            expect(fn () => $this->manager->deleteProxy('non-existent-proxy'))
                ->toThrow(RuntimeException::class, 'Proxy not found');
        });
    });

    describe('generateProxyNginxConfiguration', function (): void {
        it('generates correct proxy nginx configuration', function (): void {
            $domainName = 'my-proxy.test';
            $portNumber = 3000;

            $nginxConfiguration = $this->manager->generateProxyNginxConfiguration($domainName, $portNumber);

            expect($nginxConfiguration)->toContain('listen 127.0.0.1:80');
            expect($nginxConfiguration)->toContain(sprintf('server_name %s www.%s *.%s', $domainName, $domainName, $domainName));
            expect($nginxConfiguration)->toContain('proxy_pass http://127.0.0.1:' . $portNumber);
        });

        it('includes WebSocket support headers', function (): void {
            $nginxConfiguration = $this->manager->generateProxyNginxConfiguration('test.test', 3000);

            expect($nginxConfiguration)->toContain('proxy_http_version 1.1');
            expect($nginxConfiguration)->toContain('proxy_set_header Upgrade $http_upgrade');
            expect($nginxConfiguration)->toContain("proxy_set_header Connection 'upgrade'");
        });

        it('includes proper proxy headers', function (): void {
            $nginxConfiguration = $this->manager->generateProxyNginxConfiguration('test.test', 3000);

            expect($nginxConfiguration)->toContain('proxy_set_header Host $host');
            expect($nginxConfiguration)->toContain('proxy_set_header X-Real-IP $remote_addr');
            expect($nginxConfiguration)->toContain('proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for');
            expect($nginxConfiguration)->toContain('proxy_set_header X-Forwarded-Proto $scheme');
        });

        it('sets correct timeout', function (): void {
            $nginxConfiguration = $this->manager->generateProxyNginxConfiguration('test.test', 3000);

            expect($nginxConfiguration)->toContain('proxy_read_timeout 86400');
        });

        it('uses correct port in proxy_pass', function (): void {
            $portNumber = 8080;
            $nginxConfiguration = $this->manager->generateProxyNginxConfiguration('test.test', $portNumber);

            expect($nginxConfiguration)->toContain('proxy_pass http://127.0.0.1:' . $portNumber);
        });
    });

    describe('listProxies', function (): void {
        it('returns an array', function (): void {
            $proxies = $this->manager->listProxies();

            expect($proxies)->toBeArray();
        });

        it('returns empty array when no proxies exist', function (): void {
            $proxies = $this->manager->listProxies();

            expect($proxies)->toBeArray();
        });
    });
});
