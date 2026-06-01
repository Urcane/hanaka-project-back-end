<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use Psr\Http\Message\ResponseInterface as Response;

class LogoutAction extends BaseAction
{
    protected function action(): Response
    {
        // JWT is stateless — client simply discards the token.
        // This endpoint exists for API completeness.
        return $this->successResponse([]);
    }
}
