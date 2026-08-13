<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\ProductRepository;
use Psr\Http\Message\ResponseInterface as Response;

class CreateProductSizeAction extends BaseAction
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

        $body = $this->getBody();

        $errors = [];
        if (empty($body['label'])) {
            $errors['label'] = 'Label ukuran wajib diisi.';
        }
        if (empty($body['fullLabel'])) {
            $errors['fullLabel'] = 'Label lengkap wajib diisi.';
        }
        if (!isset($body['price']) || !is_numeric($body['price']) || (int) $body['price'] <= 0) {
            $errors['price'] = 'Harga wajib diisi dan harus lebih dari 0.';
        }
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

        $size = $this->productRepo->createSize($productId, $body);

        return $this->successResponse([
            'size' => $size,
        ], 201);
    }
}
