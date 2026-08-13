<?php

declare(strict_types=1);

namespace App\Owner\Controllers;

use App\Admin\Support\View;
use App\Infrastructure\Repositories\AnnouncementRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AnnouncementController extends Controller
{
    private AnnouncementRepository $announcementRepo;

    public function __construct(View $view, AnnouncementRepository $announcementRepo)
    {
        parent::__construct($view);
        $this->announcementRepo = $announcementRepo;
    }

    /**
     * GET /owner/announcements
     */
    public function index(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'announcements/index', [
            'active' => 'announcements',
            'title' => 'Pemberitahuan',
            'announcements' => $this->announcementRepo->findAll(),
        ]);
    }

    /**
     * POST /owner/announcements
     */
    public function store(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $title = trim((string) ($body['title'] ?? ''));
        $text = trim((string) ($body['body'] ?? ''));
        $priority = (string) ($body['priority'] ?? 'info');

        if ($title === '' || $text === '') {
            return $this->redirectWithFlash(
                $request,
                $response,
                '/owner/announcements',
                'error',
                'Judul dan isi pemberitahuan wajib diisi.'
            );
        }
        if (mb_strlen($title) > 120) {
            return $this->redirectWithFlash(
                $request,
                $response,
                '/owner/announcements',
                'error',
                'Judul maksimal 120 karakter.'
            );
        }

        $this->announcementRepo->create(
            (string) $request->getAttribute('userId'),
            $title,
            $text,
            $priority
        );

        return $this->redirectWithFlash(
            $request,
            $response,
            '/owner/announcements',
            'success',
            'Pemberitahuan dikirim ke seluruh admin.'
        );
    }

    /**
     * POST /owner/announcements/{announcementId}/delete
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->announcementRepo->delete((string) $args['announcementId']);

        return $this->redirectWithFlash(
            $request,
            $response,
            '/owner/announcements',
            'success',
            'Pemberitahuan dihapus.'
        );
    }
}
