<?php

use HerdManager\Service\UpdateService;

describe('UpdateService', function (): void {
    beforeEach(function (): void {
        $this->service = new UpdateService();
    });

    describe('checkForUpdates', function (): void {
        it('returns update information structure', function (): void {
            $result = $this->service->checkForUpdates();

            expect($result)->toBeArray();
            expect($result)->toHaveKeys(['available', 'current', 'latest', 'behind']);
            expect($result['available'])->toBeBool();
            expect($result['behind'])->toBeInt();
        });
    });

    describe('getRepositoryInfo', function (): void {
        it('returns repository information structure', function (): void {
            $result = $this->service->getRepositoryInfo();

            expect($result)->toBeArray();
            expect($result)->toHaveKeys(['url', 'branch']);
        });
    });
});
