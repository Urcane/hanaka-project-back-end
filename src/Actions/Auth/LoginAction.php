<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\CartRepository;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Services\JwtService;
use App\Infrastructure\Services\SessionService;
use App\Validation\AuthValidator;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;

class LoginAction extends BaseAction
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
        $data = Validator::sanitize($body, AuthValidator::allowedLoginFields());

        $validator = new AuthValidator();
        $errors = $validator->validateLogin($data);

        if (!empty($errors)) {
            return $this->errorResponse('Data login tidak valid.', 400, $errors);
        }

        $user = $this->userRepo->findByEmail($data['email']);
        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            return $this->errorResponse('Email atau password belum sesuai.', 401);
        }

        $token = $this->jwtService->createToken($user['id'], $user['email'], $user['role'] ?? 'customer');

        // Merge guest cart if exists
        $sessionToken = SessionService::getSessionToken($this->request);
        if ($sessionToken) {
            $this->cartRepo->mergeGuestCart($sessionToken, $user['id']);
        }

        return $this->successResponse([
            'user' => UserRepository::formatUser($user),
            'token' => $token,
        ]);
    }
}
