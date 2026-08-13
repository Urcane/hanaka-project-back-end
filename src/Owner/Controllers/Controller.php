<?php

declare(strict_types=1);

namespace App\Owner\Controllers;

use App\Admin\Support\Cookie;
use App\Admin\Support\Flash;
use App\Admin\Support\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Base controller for the server-rendered owner panel.
 *
 * Mirrors App\Admin\Controllers\Controller and reuses the same support classes;
 * only the template root differs (templates/owner, injected as `owner.view`).
 */
abstract class Controller
{
    protected View $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    protected function render(Request $request, Response $response, string $template, array $data = []): Response
    {
        $data['currentUser'] = [
            'id' => $request->getAttribute('userId'),
            'email' => $request->getAttribute('userEmail'),
            'fullName' => $request->getAttribute('userName') ?? $request->getAttribute('userEmail'),
            'role' => $request->getAttribute('userRole'),
        ];
        $data['flash'] = Flash::read($request);
        // Logout is shared with the admin panel: same cookie, same handoff.
        $data['logoutUrl'] = '/admin/logout';

        $response = $this->view->render($response, $template, $data);

        if ($data['flash'] !== null) {
            $response = Cookie::forget($response, Cookie::FLASH, Cookie::isSecureRequest($request));
        }

        return $response;
    }

    protected function redirect(Response $response, string $location, int $status = 302): Response
    {
        return $response->withHeader('Location', $location)->withStatus($status);
    }

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
     * Read `?from=` / `?to=` (Y-m-d) from the query string, falling back to the
     * last 30 days. Invalid or reversed ranges are corrected rather than
     * rejected — a report page should never dead-end on a typo.
     *
     * @return array{from: string, to: string}
     */
    protected function resolveRange(Request $request, int $defaultDays = 30): array
    {
        $params = $request->getQueryParams();
        $today = new \DateTimeImmutable('today');

        $to = $this->parseDate($params['to'] ?? null) ?? $today;
        $from = $this->parseDate($params['from'] ?? null)
            ?? $to->modify('-' . ($defaultDays - 1) . ' days');

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        // Cap the window so one query cannot try to chart several years of days.
        if ($from < $to->modify('-365 days')) {
            $from = $to->modify('-365 days');
        }

        return ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
    }

    private function parseDate(?string $value): ?\DateTimeImmutable
    {
        if (empty($value)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date === false ? null : $date;
    }
}
