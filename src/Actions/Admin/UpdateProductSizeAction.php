<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\ProductRepository;
use Psr\Http\Message\ResponseInterface as Response;

class UpdateProductSizeAction extends BaseAction
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

        $body = $this->getBody();

        $errors = [];
        if (isset($body['stock']) && (!is_numeric($body['stock']) || (int) $body['stock'] < 0)) {
            $errors['stock'] = 'Stok harus berupa angka 0 atau lebih.';
        }
        if (isset($body['lowStockThreshold'])
            && (!is_numeric($body['lowStockThreshold']) || (int) $body['lowStockThreshold'] < 0)) {
            $errors['lowStockThreshold'] = 'Batas stok menipis harus berupa angka 0 atau lebih.';
        }
        if (!empty($errors)) {
            return $this->errorResponse('Data ukuran tidak valid.', 400, $errors);
        }

        $updated = $this->productRepo->updateSize($sizeId, $body);

        return $this->successResponse([
            'size' => $updated,
        ]);
    }
}
