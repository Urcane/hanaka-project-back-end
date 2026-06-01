<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\ProductRepository;
use Psr\Http\Message\ResponseInterface as Response;

class ListProductsAction extends BaseAction
{
    private ProductRepository $productRepo;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepo = $productRepo;
    }

    protected function action(): Response
    {
        $queryParams = $this->request->getQueryParams();
        $featured = null;

        if (isset($queryParams['featured'])) {
            $featured = filter_var($queryParams['featured'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $products = $this->productRepo->findAll($featured);

        return $this->successResponse([
            'products' => $products,
        ]);
    }
}
