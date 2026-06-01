<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\ProductRepository;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;

class CreateProductAction extends BaseAction
{
    private ProductRepository $productRepo;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepo = $productRepo;
    }

    protected function action(): Response
    {
        $body = $this->getBody();
        $allowed = ['id', 'name', 'shortDescription', 'longDescription', 'featured', 'coverGradient', 'coverImage', 'maxMessageLength', 'sizes'];
        $data = Validator::sanitize($body, $allowed);

        // Validate required fields
        $errors = [];
        if (empty($data['id'])) {
            $errors['id'] = 'ID produk wajib diisi.';
        }
        if (empty($data['name'])) {
            $errors['name'] = 'Nama produk wajib diisi.';
        }
        if (empty($data['shortDescription'])) {
            $errors['shortDescription'] = 'Deskripsi singkat wajib diisi.';
        }

        if (!empty($errors)) {
            return $this->errorResponse('Data produk tidak valid.', 400, $errors);
        }

        // Check if product ID already exists
        $existing = $this->productRepo->findById($data['id']);
        if ($existing) {
            return $this->errorResponse('ID produk sudah digunakan.', 409);
        }

        $product = $this->productRepo->createProduct($data);

        // Create sizes if provided
        if (!empty($data['sizes']) && is_array($data['sizes'])) {
            foreach ($data['sizes'] as $size) {
                if (!empty($size['label']) && !empty($size['fullLabel']) && isset($size['price'])) {
                    $this->productRepo->createSize($data['id'], $size);
                }
            }
            // Refetch to include sizes
            $product = $this->productRepo->findById($data['id']);
        }

        return $this->successResponse([
            'product' => $product,
        ], 201);
    }
}
