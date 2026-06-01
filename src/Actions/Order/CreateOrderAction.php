<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\CartRepository;
use App\Infrastructure\Repositories\OrderRepository;
use App\Infrastructure\Services\SessionService;
use App\Validation\CheckoutValidator;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;

class CreateOrderAction extends BaseAction
{
    private OrderRepository $orderRepo;
    private CartRepository $cartRepo;

    public function __construct(OrderRepository $orderRepo, CartRepository $cartRepo)
    {
        $this->orderRepo = $orderRepo;
        $this->cartRepo = $cartRepo;
    }

    protected function action(): Response
    {
        $body = $this->getBody();
        $data = Validator::sanitize($body, CheckoutValidator::allowedFields());

        $validator = new CheckoutValidator();
        $errors = $validator->validateCheckout($data);

        if (!empty($errors)) {
            return $this->errorResponse('Data checkout tidak valid.', 400, $errors);
        }

        // Resolve cart identity
        $identity = SessionService::resolveCartIdentity($this->request);
        $cart = null;

        if (!$identity['isGuest']) {
            $cart = $this->cartRepo->findByUser($identity['userId']);
        } elseif ($identity['sessionToken']) {
            $cart = $this->cartRepo->findBySession($identity['sessionToken']);
        }

        if (!$cart) {
            return $this->errorResponse('Keranjang masih kosong.', 400);
        }

        $cartItems = $this->cartRepo->getCartItems($cart['id']);
        if (empty($cartItems)) {
            return $this->errorResponse('Keranjang masih kosong.', 400);
        }

        // Build order data
        $data['userId'] = $identity['userId'];
        $data['sessionToken'] = $identity['sessionToken'];

        $order = $this->orderRepo->create($data, $cartItems);

        // Clear cart after order
        $this->cartRepo->clearItems($cart['id']);

        return $this->successResponse([
            'order' => OrderRepository::formatOrder($order),
        ], 201);
    }
}
