<?php

use HerdManager\Service\HerdService;

describe('HerdService', function (): void {
    beforeEach(function (): void {
        $this->manager = new HerdService();
    });

    describe('parseSitesList', function (): void {
        it('parses valid site output correctly', function (): void {
            $output = [
                '  | empresta-legal |          | http://empresta-legal.test | /Users/daniel/Sites/empresta-legal |',
                '  | my-api         |          | http://my-api.test         | /Users/daniel/Sites/my-api         |',
            ];

            $sites = $this->manager->parseSitesList($output, 'parked');

            expect($sites)->toHaveCount(2);
            expect($sites[0])->toMatchArray([
                'name' => 'empresta-legal',
                'url' => 'http://empresta-legal.test',
                'type' => 'parked',
            ]);
        });

        it('returns empty array for invalid output', function (): void {
            $output = [
                'Invalid line',
                'Another invalid line',
            ];

            $sites = $this->manager->parseSitesList($output, 'parked');

            expect($sites)->toBeEmpty();
        });

        it('detects exposed status correctly', function (): void {
            $output = [
                '  | test-site |          | http://test-site.test | /Users/daniel/Sites/test-site |',
            ];

            $sites = $this->manager->parseSitesList($output, 'parked');

            expect($sites)->toHaveCount(1);
            expect($sites[0])->toHaveKey('exposed');
            expect($sites[0]['exposed'])->toBeBool();
        });
    });

    describe('checkPortAvailability', function (): void {
        it('returns false for invalid ports', function (): void {
            expect($this->manager->checkPortAvailability(0))->toBeFalse();
            expect($this->manager->checkPortAvailability(-1))->toBeFalse();
            expect($this->manager->checkPortAvailability(65536))->toBeFalse();
            expect($this->manager->checkPortAvailability(99999))->toBeFalse();
        });

        it('returns true for valid ports', function (): void {
            $availablePort = 9999;
            expect($this->manager->checkPortAvailability($availablePort))->toBeTrue();
        });

        it('returns false for ports in use', function (): void {
            $usedPort = 80;
            $result = $this->manager->checkPortAvailability($usedPort);
            expect($result)->toBeBool();
        });
    });

    describe('generateNginxConfiguration', function (): void {
        it('generates correct nginx configuration', function (): void {
            $siteData = [
                'name' => 'test-site',
                'url' => 'http://test-site.test',
                'port' => 8000,
            ];

            $nginxConfiguration = $this->manager->generateNginxConfiguration($siteData);

            expect($nginxConfiguration)->toContain('listen 0.0.0.0:8000');
            expect($nginxConfiguration)->toContain('proxy_set_header Host test-site.test');
            expect($nginxConfiguration)->toContain('proxy_pass http://127.0.0.1:80');
        });

        it('strips protocol from domain correctly', function (): void {
            $siteData = [
                'name' => 'test-site',
                'url' => 'https://test-site.test',
                'port' => 8001,
            ];

            $nginxConfiguration = $this->manager->generateNginxConfiguration($siteData);

            expect($nginxConfiguration)->toContain('proxy_set_header Host test-site.test');
            expect($nginxConfiguration)->not->toContain('https://');
        });

        it('uses correct port number', function (): void {
            $siteData = [
                'name' => 'test-site',
                'url' => 'http://test-site.test',
                'port' => 3000,
            ];

            $nginxConfiguration = $this->manager->generateNginxConfiguration($siteData);

            expect($nginxConfiguration)->toContain('listen 0.0.0.0:3000');
        });
    });

    describe('getConfigurationPath', function (): void {
        it('returns correct config path', function (): void {
            $siteName = 'my-site';
            $configurationPath = $this->manager->getConfigurationPath($siteName);

            expect($configurationPath)->toContain('my-site-local.conf');
            expect($configurationPath)->toContain('/Library/Application Support/Herd/config/nginx/');
        });
    });

    describe('getLocalIpAddress', function (): void {
        it('returns a valid IP address format', function (): void {
            $ipAddress = $this->manager->getLocalIpAddress();

            expect($ipAddress)->toMatch('/^(\d{1,3}\.){3}\d{1,3}$/');
        });

        it('returns an IP address', function (): void {
            $ipAddress = $this->manager->getLocalIpAddress();

            expect($ipAddress)->not->toBeEmpty();
            expect($ipAddress)->toBeString();
        });
    });
});
