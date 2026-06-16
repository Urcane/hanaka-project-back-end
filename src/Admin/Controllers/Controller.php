<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Support\Cookie;
use App\Admin\Support\Flash;
use App\Admin\Support\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Base controller for the server-rendered admin panel.
 * Provides view rendering, redirects and flash messaging shared by all controllers.
 */
abstract class Controller
{
    protected View $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    /**
     * Render an admin page, injecting the current admin user, active nav key and flash.
     */
    protected function render(Request $request, Response $response, string $template, array $data = []): Response
    {
        $data['currentUser'] = [
            'id' => $request->getAttribute('userId'),
            'email' => $request->getAttribute('userEmail'),
            'fullName' => $request->getAttribute('userName') ?? $request->getAttribute('userEmail'),
            'role' => $request->getAttribute('userRole'),
        ];
        $data['flash'] = Flash::read($request);
        $data['logoutUrl'] = '/admin/logout';

        $response = $this->view->render($response, $template, $data);

        // Clear any flash that was just displayed.
        if ($data['flash'] !== null) {
            $response = Cookie::forget($response, Cookie::FLASH, Cookie::isSecureRequest($request));
        }

        return $response;
    }

    /**
     * Issue a 302 redirect.
     */
    protected function redirect(Response $response, string $location, int $status = 302): Response
    {
        return $response->withHeader('Location', $location)->withStatus($status);
    }

    /**
     * Redirect carrying a one-shot flash message.
     */
    protected function redirectWithFlash(
        Request $request,
        Response $response,
        string $location,
        string $type,
        string $text
    ): Response {
        $response = Cookie::set(
            $response,
            Cookie::FLASH,
            Flash::encode($type, $text),
            60,
            Cookie::isSecureRequest($request)
        );
        return $this->redirect($response, $location);
    }

    /**
     * URL of the React frontend login screen (admins authenticate there first).
     */
    protected function frontendLoginUrl(): string
    {
        $base = rtrim($_ENV['FRONTEND_URL'] ?? 'http://localhost:5173', '/');
        return $base . '/login';
    }
}
