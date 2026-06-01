<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\CartRepository;
use App\Infrastructure\Services\SessionService;
use Psr\Http\Message\ResponseInterface as Response;

class GetCartAction extends BaseAction
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

        if (!$cart) {
            return $this->successResponse([
                'items' => [],
                'subtotal' => 0,
                'itemCount' => 0,
            ]);
        }

        $rawItems = $this->cartRepo->getCartItems($cart['id']);
        $items = array_map([CartRepository::class, 'formatCartItem'], $rawItems);

        $subtotal = 0;
        $itemCount = 0;
        foreach ($items as $item) {
            $subtotal += $item['totalPrice'];
            $itemCount += $item['quantity'];
        }

        return $this->successResponse([
            'items' => $items,
            'subtotal' => $subtotal,
            'itemCount' => $itemCount,
        ]);
    }
}
