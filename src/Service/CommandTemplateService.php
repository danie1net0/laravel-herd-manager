<?php

declare(strict_types=1);

namespace HerdManager\Service;

use InvalidArgumentException;

final readonly class CommandTemplateService
{
    /** @var array<string, string> */
    private array $templates;

    public function __construct(?string $templatesDirectory = null)
    {
        $templatesDirectory ??= __DIR__ . '/../../templates';
        $this->templates = require $templatesDirectory . '/commands.php';
    }

    /**
     * @param array<string, string> $variables
     */
    public function render(string $templateName, array $variables = []): string
    {
        if (! isset($this->templates[$templateName])) {
            throw new InvalidArgumentException(sprintf("Template '%s' not found", $templateName));
        }

        $template = $this->templates[$templateName];

        foreach ($variables as $key => $value) {
            $template = str_replace("{{" . mb_strtoupper($key) . "}}", $value, $template);
        }

        return $template;
    }
}
