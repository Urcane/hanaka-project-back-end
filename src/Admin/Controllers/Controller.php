<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Support\Cookie;
use App\Admin\Support\Flash;
use App\Admin\Support\View;
use App\Infrastructure\Repositories\AnnouncementRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Base controller for the server-rendered admin panel.
 * Provides view rendering, redirects and flash messaging shared by all controllers.
 */
abstract class Controller
{
    protected View $view;

    /**
     * Built on demand rather than injected: every admin page shows the owner's
     * unread notices, and threading this repository through the constructor of
     * all five subclasses (SessionController included, which renders nothing)
     * buys nothing. The repository itself has no dependencies — it takes the
     * shared PDO connection from Database::getConnection().
     */
    private ?AnnouncementRepository $announcementRepo = null;

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
        $data['announcements'] = $this->unreadAnnouncements((string) $request->getAttribute('userId'));

        $response = $this->view->render($response, $template, $data);

        // Clear any flash that was just displayed.
        if ($data['flash'] !== null) {
            $response = Cookie::forget($response, Cookie::FLASH, Cookie::isSecureRequest($request));
        }

        return $response;
    }

    /**
     * Owner notices this admin has not dismissed yet. A missing announcements
     * table (migrations not run yet) must not take the whole panel down.
     */
    private function unreadAnnouncements(string $userId): array
    {
        if ($userId === '') {
            return [];
        }

        $this->announcementRepo ??= new AnnouncementRepository();

        try {
            return $this->announcementRepo->findUnreadFor($userId);
        } catch (\PDOException) {
            return [];
        }
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
        return $this->frontendBaseUrl() . '/login';
    }

    /**
     * URL of the React frontend logout route, which clears the JWT held in
     * localStorage before returning the visitor to the login screen.
     */
    protected function frontendLogoutUrl(): string
    {
        return $this->frontendBaseUrl() . '/logout';
    }

    private function frontendBaseUrl(): string
    {
        return rtrim($_ENV['FRONTEND_URL'] ?? 'http://localhost:5173', '/');
    }
}
