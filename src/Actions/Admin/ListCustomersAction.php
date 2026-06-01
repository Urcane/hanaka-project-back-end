<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;

class ListCustomersAction extends BaseAction
{
    private UserRepository $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    protected function action(): Response
    {
        $customers = $this->userRepo->findAllCustomers();
        $formatted = array_map([UserRepository::class, 'formatUser'], $customers);

        return $this->successResponse([
            'customers' => $formatted,
        ]);
    }
}
