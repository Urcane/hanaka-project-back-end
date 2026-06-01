<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\CartRepository;
use App\Infrastructure\Services\SessionService;
use Psr\Http\Message\ResponseInterface as Response;

class ClearCartAction extends BaseAction
{
    private CartRepository $cartRepo;

    public function __construct(CartRepository $cartRepo)
    {
        $this->cartRepo = $cartRepo;
    }

    protected function action(): Response
    {
        $identity = SessionService::resolveCartIdentity($this->request);
        $cart = null;

        if (!$identity['isGuest']) {
            $cart = $this->cartRepo->findByUser($identity['userId']);
        } elseif ($identity['sessionToken']) {
            $cart = $this->cartRepo->findBySession($identity['sessionToken']);
        }

        if ($cart) {
            $this->cartRepo->clearItems($cart['id']);
        }

        return $this->successResponse([]);
    }
}
