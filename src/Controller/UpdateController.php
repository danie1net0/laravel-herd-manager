<?php

declare(strict_types=1);

namespace HerdManager\Controller;

use HerdManager\Service\UpdateService;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

readonly class UpdateController extends AbstractController
{
    public function __construct(
        private UpdateService $updateService
    ) {}

    public function check(ServerRequestInterface $request): ResponseInterface
    {
        $updateInfo = $this->updateService->checkForUpdates();
        $repoInfo = $this->updateService->getRepositoryInfo();

        return $this->json([
            'update' => $updateInfo,
            'repository' => $repoInfo,
        ]);
    }
}
