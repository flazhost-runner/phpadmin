<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Home\Controllers;

use PHPAdmin\Core\AppConfig;
use PHPAdmin\Modules\Home\Contracts\IFeTemplateService;
use PHPAdmin\Modules\Setting\Contracts\ISettingService;

/**
 * HomeController — serves the public-facing landing page.
 *
 * Logic:
 *   - If the active fe_template slug resolves to a locally-cached HTML file,
 *     output that HTML directly (the template is self-contained).
 *   - Otherwise, render the default native PHP landing view with $setting data.
 *
 * Both GET / and GET /home route here.
 */
class HomeController
{
    public function __construct(
        private readonly IFeTemplateService $templateService,
        private readonly ISettingService $settingService,
        private readonly AppConfig $config
    ) {
    }

    // ─── Web handlers ─────────────────────────────────────────────────────────

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function index(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $html = $this->templateService->getActiveHtml();

        if ($html !== null) {
            // Custom opentailwind template — serve cached HTML directly.
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            return;
        }

        // Default native landing view.
        $setting = $this->settingService->get();

        render(
            $this->config->appRoot . '/src/views/home/default/index.php',
            [
                'setting'   => $setting,
                'pageTitle' => ($setting['name'] ?? $this->config->appName) . ' — Home',
            ]
        );
    }
}
