<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Support\View;
use App\Infrastructure\Repositories\AnnouncementRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Admin side of the owner's announcements: dismissing only. Authoring lives in
 * the owner panel (App\Owner\Controllers\AnnouncementController).
 */
class AnnouncementController extends Controller
{
    private AnnouncementRepository $announcementRepo;

    public function __construct(View $view, AnnouncementRepository $announcementRepo)
    {
        parent::__construct($view);
        $this->announcementRepo = $announcementRepo;
    }

    /**
     * POST /admin/announcements/{announcementId}/read
     */
    public function markRead(Request $request, Response $response, array $args): Response
    {
        $this->announcementRepo->markRead(
            (string) $args['announcementId'],
            (string) $request->getAttribute('userId')
        );

        return $this->redirect($response, $this->refererPath($request));
    }

    /**
     * Send the admin back to the page they dismissed the banner on, but only if
     * that page is inside the admin panel — never bounce off a supplied Referer.
     */
    private function refererPath(Request $request): string
    {
        $referer = $request->getHeaderLine('Referer');
        if ($referer === '') {
            return '/admin/dashboard';
        }

        $path = (string) parse_url($referer, PHP_URL_PATH);
        $query = parse_url($referer, PHP_URL_QUERY);

        if (!str_starts_with($path, '/admin/')) {
            return '/admin/dashboard';
        }

        return $query ? $path . '?' . $query : $path;
    }
}
