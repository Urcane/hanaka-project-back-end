<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Support\View;
use App\Infrastructure\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CustomerController extends Controller
{
    private UserRepository $userRepo;

    public function __construct(View $view, UserRepository $userRepo)
    {
        parent::__construct($view);
        $this->userRepo = $userRepo;
    }

    /**
     * GET /admin/customers
     */
    public function index(Request $request, Response $response): Response
    {
        $customers = array_map(
            [UserRepository::class, 'formatUser'],
            $this->userRepo->findAllCustomers()
        );

        return $this->render($request, $response, 'customers/index', [
            'active' => 'customers',
            'title' => 'Customer List',
            'customers' => $customers,
        ]);
    }
}
