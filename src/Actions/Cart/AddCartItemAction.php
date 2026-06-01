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

class AddCartItemAction extends BaseAction
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
        $body = $this->getBody();
        $data = Validator::sanitize($body, CartValidator::allowedAddFields());

        $validator = new CartValidator();
        $errors = $validator->validateAddItem($data);

        if (!empty($errors)) {
            return $this->errorResponse('Data item tidak valid.', 400, $errors);
        }

        // Verify product exists
        $product = $this->productRepo->findById($data['productId']);
        if (!$product) {
            return $this->errorResponse('Produk tidak ditemukan.', 400, [
                'productId' => 'Produk tidak ditemukan.',
            ]);
        }

        // Verify size belongs to product
        $size = $this->productRepo->findSizeByIdAndProduct($data['sizeId'], $data['productId']);
        if (!$size) {
            return $this->errorResponse('Ukuran cake tidak valid.', 400, [
                'sizeId' => 'Ukuran cake tidak valid untuk produk ini.',
            ]);
        }

        // Check message length against product's max
        if (!empty($data['message']) && mb_strlen($data['message']) > $product['maxMessageLength']) {
            return $this->errorResponse('Pesan terlalu panjang.', 400, [
                'message' => "Pesan maksimal {$product['maxMessageLength']} karakter.",
            ]);
        }

        // Get or create cart
        $identity = SessionService::resolveCartIdentity($this->request);
        if (!$identity['isGuest']) {
            $cart = $this->cartRepo->findOrCreateByUser($identity['userId']);
        } else {
            $sessionToken = $identity['sessionToken'] ?? SessionService::generateSessionToken();
            $cart = $this->cartRepo->findOrCreateBySession($sessionToken);
        }

        // Add item
        $data['unitPrice'] = $size['price'];
        $itemId = $this->cartRepo->addItem($cart['id'], $data);

        // Get formatted item for response
        $rawItems = $this->cartRepo->getCartItems($cart['id']);
        $formattedItem = null;
        foreach ($rawItems as $raw) {
            if ($raw['id'] === $itemId) {
                $formattedItem = CartRepository::formatCartItem($raw);
                break;
            }
        }

        $responseData = ['item' => $formattedItem];

        // If this was a new guest session, include the session token
        if ($identity['isGuest'] && empty($identity['sessionToken'])) {
            $responseData['sessionToken'] = $sessionToken;
        }

        return $this->successResponse($responseData, 201);
    }
}
