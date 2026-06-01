<?php

declare(strict_types=1);

namespace App\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class BaseAction
{
    protected Request $request;
    protected Response $response;
    protected array $args;

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        return $this->action();
    }

    abstract protected function action(): Response;

    protected function getBody(): array
    {
        $body = $this->request->getParsedBody();
        return is_array($body) ? $body : [];
    }

    protected function jsonResponse(array $data, int $status = 200): Response
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->response->getBody()->write($json);
        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    protected function successResponse($data, int $status = 200): Response
    {
        return $this->jsonResponse(array_merge(['ok' => true], $data), $status);
    }

    protected function errorResponse(string $message, int $status = 400, array $errors = []): Response
    {
        $payload = ['ok' => false, 'error' => $message];
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }
        return $this->jsonResponse($payload, $status);
    }

    protected function getUserId(): ?string
    {
        return $this->request->getAttribute('userId');
    }
}
