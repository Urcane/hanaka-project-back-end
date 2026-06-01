<?php

declare(strict_types=1);

use App\Infrastructure\Repositories\CartRepository;
use App\Infrastructure\Repositories\OrderRepository;
use App\Infrastructure\Repositories\ProductRepository;
use App\Infrastructure\Repositories\UserRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        UserRepository::class => \DI\autowire(UserRepository::class),
        ProductRepository::class => \DI\autowire(ProductRepository::class),
        CartRepository::class => \DI\autowire(CartRepository::class),
        OrderRepository::class => \DI\autowire(OrderRepository::class),
    ]);
};
