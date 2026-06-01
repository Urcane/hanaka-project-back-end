<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\ProductRepository;
use Psr\Http\Message\ResponseInterface as Response;

class UploadProductImageAction extends BaseAction
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const MAX_SIZE_BYTES = 2 * 1024 * 1024; // 2MB
    private const UPLOAD_DIR = __DIR__ . '/../../../public/uploads/products/';

    private ProductRepository $productRepo;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepo = $productRepo;
    }

    protected function action(): Response
    {
        $productId = $this->args['productId'];

        $product = $this->productRepo->findById($productId);
        if (!$product) {
            return $this->errorResponse('Produk tidak ditemukan.', 404);
        }

        $uploadedFiles = $this->request->getUploadedFiles();
        if (empty($uploadedFiles['image'])) {
            return $this->errorResponse('File gambar wajib diupload.', 400);
        }

        $file = $uploadedFiles['image'];

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return $this->errorResponse('Upload file gagal.', 400);
        }

        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            return $this->errorResponse('Ukuran file maksimal 2MB.', 400);
        }

        $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return $this->errorResponse('Format file tidak didukung. Gunakan JPG, PNG, atau WebP.', 400);
        }

        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        // Delete old uploaded image if exists
        if (!empty($product['coverImage']) && str_starts_with($product['coverImage'], 'uploads/')) {
            $oldPath = __DIR__ . '/../../../public/' . $product['coverImage'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $filename = $productId . '_' . uniqid() . '.' . $ext;
        $file->moveTo(self::UPLOAD_DIR . $filename);

        $coverImagePath = 'uploads/products/' . $filename;
        $updatedProduct = $this->productRepo->updateProduct($productId, ['coverImage' => $coverImagePath]);

        return $this->successResponse(['product' => $updatedProduct]);
    }
}
