<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Support\View;
use App\Infrastructure\Repositories\ProductRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

class ProductController extends Controller
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const MAX_SIZE_BYTES = 2 * 1024 * 1024; // 2MB
    private const UPLOAD_DIR = __DIR__ . '/../../../public/uploads/products/';

    private ProductRepository $productRepo;

    public function __construct(View $view, ProductRepository $productRepo)
    {
        parent::__construct($view);
        $this->productRepo = $productRepo;
    }

    /**
     * GET /admin/products
     */
    public function index(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'products/index', [
            'active' => 'products',
            'title' => 'Product Management',
            'products' => $this->productRepo->findAll(),
        ]);
    }

    /**
     * POST /admin/products
     */
    public function store(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $data = [
            'id' => trim((string) ($body['id'] ?? '')),
            'name' => trim((string) ($body['name'] ?? '')),
            'shortDescription' => trim((string) ($body['shortDescription'] ?? '')),
            'longDescription' => trim((string) ($body['longDescription'] ?? '')),
            'coverGradient' => trim((string) ($body['coverGradient'] ?? '')),
            'featured' => isset($body['featured']),
            'maxMessageLength' => (int) ($body['maxMessageLength'] ?? 60) ?: 60,
        ];

        if ($data['id'] === '' || $data['name'] === '' || $data['shortDescription'] === '') {
            return $this->redirectWithFlash($request, $response, '/admin/products', 'error', 'ID, nama, dan deskripsi singkat wajib diisi.');
        }
        if ($this->productRepo->findById($data['id']) !== null) {
            return $this->redirectWithFlash($request, $response, '/admin/products', 'error', 'ID produk sudah digunakan.');
        }

        $this->productRepo->createProduct($data);

        try {
            $this->handleImageUpload($request, $data['id'], null);
        } catch (\RuntimeException $e) {
            return $this->redirectWithFlash($request, $response, '/admin/products', 'error', $e->getMessage());
        }

        return $this->redirectWithFlash($request, $response, '/admin/products', 'success', 'Produk berhasil dibuat.');
    }

    /**
     * POST /admin/products/{productId}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $productId = (string) $args['productId'];
        $product = $this->productRepo->findById($productId);
        if ($product === null) {
            return $this->redirectWithFlash($request, $response, '/admin/products', 'error', 'Produk tidak ditemukan.');
        }

        $body = (array) $request->getParsedBody();
        $data = [
            'name' => trim((string) ($body['name'] ?? '')),
            'shortDescription' => trim((string) ($body['shortDescription'] ?? '')),
            'longDescription' => trim((string) ($body['longDescription'] ?? '')),
            'coverGradient' => trim((string) ($body['coverGradient'] ?? '')),
            'featured' => isset($body['featured']),
            'maxMessageLength' => (int) ($body['maxMessageLength'] ?? 60) ?: 60,
        ];

        if ($data['name'] === '' || $data['shortDescription'] === '') {
            return $this->redirectWithFlash($request, $response, '/admin/products', 'error', 'Nama dan deskripsi singkat wajib diisi.');
        }

        $this->productRepo->updateProduct($productId, $data);

        try {
            $this->handleImageUpload($request, $productId, $product['coverImage'] ?? null);
        } catch (\RuntimeException $e) {
            return $this->redirectWithFlash($request, $response, '/admin/products', 'error', $e->getMessage());
        }

        return $this->redirectWithFlash($request, $response, '/admin/products', 'success', 'Produk berhasil diperbarui.');
    }

    /**
     * POST /admin/products/{productId}/delete
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->productRepo->deleteProduct((string) $args['productId']);
        return $this->redirectWithFlash($request, $response, '/admin/products', 'success', 'Produk dihapus.');
    }

    /**
     * POST /admin/products/{productId}/sizes
     */
    public function storeSize(Request $request, Response $response, array $args): Response
    {
        $productId = (string) $args['productId'];
        $body = (array) $request->getParsedBody();

        $label = trim((string) ($body['label'] ?? ''));
        $fullLabel = trim((string) ($body['fullLabel'] ?? ''));
        $price = (int) ($body['price'] ?? 0);

        if ($label === '' || $fullLabel === '') {
            return $this->redirectWithFlash($request, $response, '/admin/products', 'error', 'Label ukuran wajib diisi.');
        }
        if ($this->productRepo->findById($productId) === null) {
            return $this->redirectWithFlash($request, $response, '/admin/products', 'error', 'Produk tidak ditemukan.');
        }

        $this->productRepo->createSize($productId, [
            'label' => $label,
            'fullLabel' => $fullLabel,
            'price' => $price,
        ]);

        return $this->redirectWithFlash($request, $response, '/admin/products', 'success', 'Ukuran ditambahkan.');
    }

    /**
     * POST /admin/products/{productId}/sizes/{sizeId}/delete
     */
    public function destroySize(Request $request, Response $response, array $args): Response
    {
        $this->productRepo->deleteSize((string) $args['sizeId']);
        return $this->redirectWithFlash($request, $response, '/admin/products', 'success', 'Ukuran dihapus.');
    }

    /**
     * Validate and store an uploaded cover image, returning the new path (or null
     * when no file was submitted). Mirrors the API's UploadProductImageAction.
     *
     * @throws \RuntimeException on a validation failure.
     */
    private function handleImageUpload(Request $request, string $productId, ?string $oldCoverImage): ?string
    {
        $files = $request->getUploadedFiles();
        $file = $files['image'] ?? null;

        if (!$file instanceof UploadedFileInterface || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload gambar gagal.');
        }
        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            throw new \RuntimeException('Ukuran file maksimal 2MB.');
        }

        $ext = strtolower(pathinfo((string) $file->getClientFilename(), PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new \RuntimeException('Format file tidak didukung. Gunakan JPG, PNG, atau WebP.');
        }

        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        if (!empty($oldCoverImage) && str_starts_with($oldCoverImage, 'uploads/')) {
            $oldPath = __DIR__ . '/../../../public/' . $oldCoverImage;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $filename = $productId . '_' . uniqid() . '.' . $ext;
        $file->moveTo(self::UPLOAD_DIR . $filename);

        $coverImagePath = 'uploads/products/' . $filename;
        $this->productRepo->updateProduct($productId, ['coverImage' => $coverImagePath]);

        return $coverImagePath;
    }
}
