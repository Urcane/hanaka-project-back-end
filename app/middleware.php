<?php

declare(strict_types=1);

use App\Middleware\CorsMiddleware;
use App\Middleware\JwtMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use Slim\App;

return function (App $app) {
    $app->add(SecurityHeadersMiddleware::class);
    $app->add($app->getContainer()->get(JwtMiddleware::class));
    $app->add(CorsMiddleware::class);
};
