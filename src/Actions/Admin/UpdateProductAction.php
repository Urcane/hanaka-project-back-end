<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\ProductRepository;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;

class UpdateProductAction extends BaseAction
{
    private ProductRepository $productRepo;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepo = $productRepo;
    }

    protected function action(): Response
    {
        $productId = $this->args['productId'] ?? '';

        $existing = $this->productRepo->findById($productId);
        if (!$existing) {
            return $this->errorResponse('Produk tidak ditemukan.', 404);
        }

        $body = $this->getBody();
        $allowed = ['name', 'shortDescription', 'longDescription', 'featured', 'coverGradient', 'coverImage', 'maxMessageLength'];
        $data = Validator::sanitize($body, $allowed);

        $product = $this->productRepo->updateProduct($productId, $data);

        return $this->successResponse([
            'product' => $product,
        ]);
    }
}
