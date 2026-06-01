<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;

class MeAction extends BaseAction
{
    private UserRepository $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    protected function action(): Response
    {
        $userId = $this->getUserId();

        if (!$userId) {
            return $this->errorResponse('Token tidak valid atau sudah expired.', 401);
        }

        $user = $this->userRepo->findById($userId);
        if (!$user) {
            return $this->errorResponse('User tidak ditemukan.', 404);
        }

        return $this->successResponse([
            'user' => UserRepository::formatUser($user),
        ]);
    }
}
