<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\ProductRepository;
use Psr\Http\Message\ResponseInterface as Response;

class DeleteProductSizeAction extends BaseAction
{
    private ProductRepository $productRepo;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepo = $productRepo;
    }

    protected function action(): Response
    {
        $productId = $this->args['productId'] ?? '';
        $sizeId = $this->args['sizeId'] ?? '';

        $size = $this->productRepo->findSizeByIdAndProduct($sizeId, $productId);
        if (!$size) {
            return $this->errorResponse('Ukuran tidak ditemukan.', 404);
        }

        $this->productRepo->deleteSize($sizeId);

        return $this->successResponse([
            'message' => 'Ukuran berhasil dihapus.',
        ]);
    }
}
