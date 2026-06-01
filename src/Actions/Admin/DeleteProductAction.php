<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\ProductRepository;
use Psr\Http\Message\ResponseInterface as Response;

class DeleteProductAction extends BaseAction
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

        $this->productRepo->deleteProduct($productId);

        return $this->successResponse([
            'message' => 'Produk berhasil dihapus.',
        ]);
    }
}
