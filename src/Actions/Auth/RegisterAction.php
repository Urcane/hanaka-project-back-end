<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Infrastructure\Database;
use App\Infrastructure\Repositories\CartRepository;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Services\JwtService;
use App\Infrastructure\Services\SessionService;
use App\Validation\AuthValidator;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;

class RegisterAction extends BaseAction
{
    private UserRepository $userRepo;
    private JwtService $jwtService;
    private CartRepository $cartRepo;

    public function __construct(UserRepository $userRepo, JwtService $jwtService, CartRepository $cartRepo)
    {
        $this->userRepo = $userRepo;
        $this->jwtService = $jwtService;
        $this->cartRepo = $cartRepo;
    }

    protected function action(): Response
    {
        $body = $this->getBody();
        $data = Validator::sanitize($body, AuthValidator::allowedRegisterFields());

        $validator = new AuthValidator();
        $errors = $validator->validateRegister($data);

        if (!empty($errors)) {
            return $this->errorResponse('Data registrasi tidak valid.', 400, $errors);
        }

        // Check email uniqueness
        $existing = $this->userRepo->findByEmail($data['email']);
        if ($existing) {
            return $this->errorResponse('Email ini sudah terdaftar. Silakan login.', 409, [
                'email' => 'Email ini sudah terdaftar.',
            ]);
        }

        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $userId = Database::generateId('usr');

        $user = $this->userRepo->create($userId, $data['fullName'], $data['email'], $data['phone'], $passwordHash);
        $token = $this->jwtService->createToken($userId, $data['email'], 'customer');

        // Merge guest cart if exists
        $sessionToken = SessionService::getSessionToken($this->request);
        if ($sessionToken) {
            $this->cartRepo->mergeGuestCart($sessionToken, $userId);
        }

        return $this->successResponse([
            'user' => UserRepository::formatUser($user),
            'token' => $token,
        ], 201);
    }
}
