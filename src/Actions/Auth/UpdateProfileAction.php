<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\UserRepository;
use App\Validation\AuthValidator;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;

class UpdateProfileAction extends BaseAction
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

        $body = $this->getBody();
        $data = Validator::sanitize($body, AuthValidator::allowedProfileFields());

        $validator = new AuthValidator();
        $errors = $validator->validateProfile($data);
        if (!empty($errors)) {
            return $this->errorResponse('Data profil tidak valid.', 400, $errors);
        }

        // Nomor telepon harus unik antar akun.
        $clash = $this->userRepo->findByPhoneExcept($data['phone'], $userId);
        if ($clash) {
            return $this->errorResponse('Nomor telepon sudah dipakai akun lain.', 400, [
                'phone' => 'Nomor telepon sudah dipakai akun lain.',
            ]);
        }

        $updated = $this->userRepo->updateProfile($userId, $data['fullName'], $data['phone']);

        return $this->successResponse([
            'user' => UserRepository::formatUser($updated),
        ]);
    }
}
