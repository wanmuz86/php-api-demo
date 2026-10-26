<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\HealthController;
use App\Database\Connection;

header('Content-Type: application/json; charset=utf-8');


use App\Core\Router;
use App\Core\Response;
use App\Controllers\ProductController;
use App\Services\ProductService;
use App\Controllers\ReviewController;
use App\Services\ReviewService;
use App\Repositories\ProductRepository;
use App\Services\InventoryService;
use App\Controllers\InventoryController;


require_once __DIR__ . '/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();

$router = new Router();
$pdo = Connection::create();

$productRepository = new ProductRepository($pdo);

$productService = new ProductService(
    $productRepository
);

$productController = new ProductController(
    $productService
);

$controller = new ProductController($productService);

$reviewService = new ReviewService(
    __DIR__ . '/../storage/reviews.json'
);
$reviewController = new ReviewController(
    $reviewService,
    $productService
);


// Create inventory service
$inventoryService = new InventoryService($pdo);

// Create inventory controller
$inventoryController = new InventoryController(
    $inventoryService
);


$router->add('GET', '/api/health', function () {
    $health = new HealthController();
    Response::json($health->index());
});

$router->add('GET', '/api/products', [$controller, 'index']);
$router->add('GET', '/api/products/{id}', [$controller, 'show']);
$router->add('POST', '/api/products', [$controller, 'store']);
$router->add('PUT', '/api/products/{id}', [$controller, 'update']);
$router->add('PATCH', '/api/products/{id}', [$controller, 'patch']);
$router->add('DELETE', '/api/products/{id}', [$controller, 'destroy']);

$router->add(
'GET',
'/api/products/{id}/reviews',
[$reviewController, 'index']
);
$router->add(
'GET',
'/api/products/{id}/reviews/{reviewId}',
[$reviewController, 'show']
);
$router->add(
'POST',
'/api/products/{id}/reviews',
[$reviewController, 'store']
);
$router->add(
'DELETE',
'/api/products/{id}/reviews/{reviewId}',
[$reviewController, 'destroy']
);

$router->add(
    'POST',
    '/api/products/{id}/inventory',
    function ($id) use ($inventoryController) {

        // Validate route parameter before casting
        if (
            !ctype_digit((string) $id) ||
            (int) $id < 1
        ) {
            Response::json([
                'error' => 'Invalid product ID'
            ], 422);
            return;
        }

        $inventoryController->adjust((int) $id);
    }
);


$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
