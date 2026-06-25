<?php

declare(strict_types=1);

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\MeAction;
use App\Actions\Auth\RegisterAction;
use App\Actions\Auth\UpdateProfileAction;
use App\Actions\Cart\AddCartItemAction;
use App\Actions\Cart\ClearCartAction;
use App\Actions\Cart\GetCartAction;
use App\Actions\Cart\RemoveCartItemAction;
use App\Actions\Cart\UpdateCartItemAction;
use App\Actions\Cart\UpdateCartQuantityAction;
use App\Actions\Order\CreateOrderAction;
use App\Actions\Order\GetOrderAction;
use App\Actions\Order\ListOrdersAction;
use App\Actions\Order\MarkOrderPaidAction;
use App\Actions\Order\TrackOrderAction;
use App\Actions\Payment\GenerateQrisAction;
use App\Actions\Payment\PaymentStatusAction;
use App\Actions\Payment\PaymentWebhookAction;
use App\Actions\Product\GetProductAction;
use App\Actions\Product\ListProductsAction;
use App\Actions\Store\GetProfileAction;
use App\Actions\Admin\DashboardAction;
use App\Actions\Admin\ListOrdersAction as AdminListOrdersAction;
use App\Actions\Admin\GetOrderAction as AdminGetOrderAction;
use App\Actions\Admin\UpdateOrderStatusAction;
use App\Actions\Admin\UpdatePaymentStatusAction;
use App\Actions\Admin\ListCustomersAction;
use App\Actions\Admin\CreateProductAction;
use App\Actions\Admin\UpdateProductAction;
use App\Actions\Admin\DeleteProductAction;
use App\Actions\Admin\CreateProductSizeAction;
use App\Actions\Admin\UpdateProductSizeAction;
use App\Actions\Admin\DeleteProductSizeAction;
use App\Actions\Admin\UploadProductImageAction;
use App\Admin\Controllers\CustomerController;
use App\Admin\Controllers\DashboardController;
use App\Admin\Controllers\OrderController;
use App\Admin\Controllers\ProductController;
use App\Admin\Controllers\SessionController;
use App\Admin\Middleware\AdminAuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthRequiredMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    // CORS Pre-Flight
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        return $response;
    });

    // Health check
    $app->get('/', function (Request $request, Response $response) {
        $data = json_encode(['ok' => true, 'message' => 'Hanaka Cake API']);
        $response->getBody()->write($data);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // API routes
    $app->group('/api', function (Group $api) {

        // Auth
        $api->group('/auth', function (Group $auth) {
            $auth->post('/register', RegisterAction::class);
            $auth->post('/login', LoginAction::class);
            $auth->post('/logout', LogoutAction::class)->add(AuthRequiredMiddleware::class);
            $auth->get('/me', MeAction::class)->add(AuthRequiredMiddleware::class);
            $auth->put('/profile', UpdateProfileAction::class)->add(AuthRequiredMiddleware::class);
        });

        // Products (public)
        $api->group('/products', function (Group $products) {
            $products->get('', ListProductsAction::class);
            $products->get('/{productId}', GetProductAction::class);
        });

        // Cart
        $api->group('/cart', function (Group $cart) {
            $cart->get('', GetCartAction::class);
            $cart->post('/items', AddCartItemAction::class);
            $cart->put('/items/{itemId}', UpdateCartItemAction::class);
            $cart->patch('/items/{itemId}/quantity', UpdateCartQuantityAction::class);
            $cart->delete('/items/{itemId}', RemoveCartItemAction::class);
            $cart->delete('', ClearCartAction::class);
        });

        // Orders
        $api->group('/orders', function (Group $orders) {
            $orders->post('', CreateOrderAction::class);
            // Public order tracking — must be declared before /{orderId} so the
            // literal "track" segment is matched before the wildcard.
            $orders->get('/track', TrackOrderAction::class);
            $orders->get('', ListOrdersAction::class)->add(AuthRequiredMiddleware::class);
            $orders->get('/{orderId}', GetOrderAction::class);
            $orders->patch('/{orderId}/pay', MarkOrderPaidAction::class);
        });

        // Payment
        $api->post('/payments/qris', GenerateQrisAction::class);
        $api->get('/payments/qris/status', PaymentStatusAction::class);
        $api->post('/payments/webhook', PaymentWebhookAction::class);

        // Store
        $api->get('/store/profile', GetProfileAction::class);

        // ── Admin Routes ──
        $api->group('/admin', function (Group $admin) {
            // Dashboard
            $admin->get('/dashboard', DashboardAction::class);

            // Order management
            $admin->get('/orders', AdminListOrdersAction::class);
            $admin->get('/orders/{orderId}', AdminGetOrderAction::class);
            $admin->patch('/orders/{orderId}/status', UpdateOrderStatusAction::class);
            $admin->patch('/orders/{orderId}/payment-status', UpdatePaymentStatusAction::class);

            // Customer management
            $admin->get('/customers', ListCustomersAction::class);

            // Product management
            $admin->post('/products', CreateProductAction::class);
            $admin->put('/products/{productId}', UpdateProductAction::class);
            $admin->delete('/products/{productId}', DeleteProductAction::class);
            $admin->post('/products/{productId}/image', UploadProductImageAction::class);

            // Product size management
            $admin->post('/products/{productId}/sizes', CreateProductSizeAction::class);
            $admin->put('/products/{productId}/sizes/{sizeId}', UpdateProductSizeAction::class);
            $admin->delete('/products/{productId}/sizes/{sizeId}', DeleteProductSizeAction::class);
        })->add(AdminMiddleware::class);
    });

    // ── Server-rendered Admin Panel (web, not /api) ──
    // Admins log in on the React frontend, then get handed off here with their
    // JWT. The panel renders PHP views and talks to the repositories directly.

    // Auth handoff — outside the guard so the cookie can be (re)set / cleared.
    $app->get('/admin/login', [SessionController::class, 'login']);
    $app->get('/admin/logout', [SessionController::class, 'logout']);

    $app->group('/admin', function (Group $admin) {
        $admin->get('', function (Request $request, Response $response) {
            return $response->withHeader('Location', '/admin/dashboard')->withStatus(302);
        });

        $admin->get('/dashboard', [DashboardController::class, 'index']);

        // Orders
        $admin->get('/orders', [OrderController::class, 'index']);
        $admin->get('/orders/{orderId}', [OrderController::class, 'show']);
        $admin->post('/orders/{orderId}/status', [OrderController::class, 'updateStatus']);
        $admin->post('/orders/{orderId}/payment-status', [OrderController::class, 'updatePaymentStatus']);

        // Customers
        $admin->get('/customers', [CustomerController::class, 'index']);

        // Products
        $admin->get('/products', [ProductController::class, 'index']);
        $admin->post('/products', [ProductController::class, 'store']);
        $admin->post('/products/{productId}', [ProductController::class, 'update']);
        $admin->post('/products/{productId}/delete', [ProductController::class, 'destroy']);
        $admin->post('/products/{productId}/sizes', [ProductController::class, 'storeSize']);
        $admin->post('/products/{productId}/sizes/{sizeId}/delete', [ProductController::class, 'destroySize']);
    })->add(AdminAuthMiddleware::class);
};
