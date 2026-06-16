<?php

declare(strict_types=1);

namespace App\Admin\Support;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * Minimal PHP template renderer for the admin panel.
 *
 * Templates are plain PHP files under templates/admin/. A page template is
 * rendered into the {{ content }} slot of layout.php. No external dependency
 * (Twig etc.) so `composer install` is not required.
 */
class View
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        require_once __DIR__ . '/helpers.php';
    }

    /**
     * Render a page template wrapped in the layout and write it to the response.
     */
    public function render(Response $response, string $template, array $data = []): Response
    {
        $html = $this->renderPage($template, $data);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Render a page template inside the layout shell, returning the HTML string.
     */
    public function renderPage(string $template, array $data = []): string
    {
        $content = $this->partial($template, $data);
        return $this->partial('layout', array_merge($data, ['content' => $content]));
    }

    /**
     * Render a single template fragment to a string.
     */
    public function partial(string $template, array $data = []): string
    {
        $file = $this->basePath . '/' . ltrim($template, '/') . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Admin template not found: {$template}");
        }

        $data['view'] = $this;

        return (function () use ($file, $data): string {
            extract($data, EXTR_SKIP);
            ob_start();
            include $file;
            return (string) ob_get_clean();
        })();
    }
}
