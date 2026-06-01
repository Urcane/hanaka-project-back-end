<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\CartRepository;
use App\Infrastructure\Services\SessionService;
use Psr\Http\Message\ResponseInterface as Response;

class RemoveCartItemAction extends BaseAction
{
    private CartRepository $cartRepo;

    public function __construct(CartRepository $cartRepo)
    {
        $this->cartRepo = $cartRepo;
    }

    protected function action(): Response
    {
        $itemId = $this->args['itemId'] ?? '';

        // Find item
        $item = $this->cartRepo->findItemById($itemId);
        if (!$item) {
            return $this->errorResponse('Item tidak ditemukan.', 404);
        }

        // Verify access
        $identity = SessionService::resolveCartIdentity($this->request);
        $cart = null;
        if (!$identity['isGuest']) {
            $cart = $this->cartRepo->findByUser($identity['userId']);
        } elseif ($identity['sessionToken']) {
            $cart = $this->cartRepo->findBySession($identity['sessionToken']);
        }

        if (!$cart || $item['cart_id'] !== $cart['id']) {
            return $this->errorResponse('Akses ditolak.', 403);
        }

        $this->cartRepo->removeItem($itemId);

        return $this->successResponse([]);
    }
}
