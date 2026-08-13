<?php

declare(strict_types=1);

use App\Admin\Support\View;
use App\Application\Settings\SettingsInterface;
use App\Infrastructure\Repositories\AnnouncementRepository;
use App\Infrastructure\Repositories\OrderRepository;
use App\Infrastructure\Repositories\ProductRepository;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Services\JwtService;
use App\Infrastructure\Services\MidtransService;
use App\Middleware\JwtMiddleware;
use App\Owner\Controllers\AnnouncementController as OwnerAnnouncementController;
use App\Owner\Controllers\DashboardController as OwnerDashboardController;
use App\Owner\Controllers\ReportController as OwnerReportController;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },

        JwtService::class => function () {
            return new JwtService();
        },

        MidtransService::class => function () {
            return new MidtransService();
        },

        JwtMiddleware::class => function (ContainerInterface $c) {
            return new JwtMiddleware($c->get(JwtService::class));
        },

        // Server-rendered admin panel view renderer (plain PHP templates).
        View::class => function () {
            return new View(__DIR__ . '/../templates/admin');
        },

        // The owner panel shares the renderer but has its own template root
        // (own layout + sidebar), so it needs a second View instance. Owner
        // controllers are wired explicitly to receive this one.
        'owner.view' => function () {
            return new View(__DIR__ . '/../templates/owner');
        },

        OwnerDashboardController::class => function (ContainerInterface $c) {
            return new OwnerDashboardController(
                $c->get('owner.view'),
                $c->get(OrderRepository::class),
                $c->get(ProductRepository::class),
                $c->get(UserRepository::class),
                $c->get(AnnouncementRepository::class)
            );
        },

        OwnerReportController::class => function (ContainerInterface $c) {
            return new OwnerReportController(
                $c->get('owner.view'),
                $c->get(OrderRepository::class)
            );
        },

        OwnerAnnouncementController::class => function (ContainerInterface $c) {
            return new OwnerAnnouncementController(
                $c->get('owner.view'),
                $c->get(AnnouncementRepository::class)
            );
        },
    ]);
};
