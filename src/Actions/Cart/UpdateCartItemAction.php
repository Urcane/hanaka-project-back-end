<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\CartRepository;
use App\Infrastructure\Repositories\ProductRepository;
use App\Infrastructure\Services\SessionService;
use App\Validation\CartValidator;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;

class UpdateCartItemAction extends BaseAction
{
    private CartRepository $cartRepo;
    private ProductRepository $productRepo;

    public function __construct(CartRepository $cartRepo, ProductRepository $productRepo)
    {
        $this->cartRepo = $cartRepo;
        $this->productRepo = $productRepo;
    }

    protected function action(): Response
    {
        $itemId = $this->args['itemId'] ?? '';
        $body = $this->getBody();
        $data = Validator::sanitize($body, CartValidator::allowedUpdateFields());

        $validator = new CartValidator();
        $errors = $validator->validateUpdateItem($data);

        if (!empty($errors)) {
            return $this->errorResponse('Data item tidak valid.', 400, $errors);
        }

        // Find existing item
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

        // Verify size belongs to the item's product
        $size = $this->productRepo->findSizeByIdAndProduct($data['sizeId'], $item['product_id']);
        if (!$size) {
            return $this->errorResponse('Ukuran cake tidak valid.', 400, [
                'sizeId' => 'Ukuran cake tidak valid untuk produk ini.',
            ]);
        }

        $data['unitPrice'] = $size['price'];
        $this->cartRepo->updateItem($itemId, $data);

        // Get formatted item
        $rawItems = $this->cartRepo->getCartItems($cart['id']);
        $formattedItem = null;
        foreach ($rawItems as $raw) {
            if ($raw['id'] === $itemId) {
                $formattedItem = CartRepository::formatCartItem($raw);
                break;
            }
        }

        return $this->successResponse(['item' => $formattedItem]);
    }
}
