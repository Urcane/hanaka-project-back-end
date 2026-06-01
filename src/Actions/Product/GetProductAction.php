<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\ProductRepository;
use Psr\Http\Message\ResponseInterface as Response;

class GetProductAction extends BaseAction
{
    private ProductRepository $productRepo;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepo = $productRepo;
    }

    protected function action(): Response
    {
        $productId = $this->args['productId'] ?? '';

        $product = $this->productRepo->findById($productId);

        if (!$product) {
            return $this->errorResponse('Produk tidak ditemukan.', 404);
        }

        return $this->successResponse([
            'product' => $product,
        ]);
    }
}
