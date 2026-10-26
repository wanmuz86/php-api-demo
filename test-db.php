<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Database\Connection;
use App\Repositories\ProductRepository;

// Load .env configuration
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    // Step 1: Connect to MySQL
    $pdo = Connection::create();
    echo "Database connection successful!\n";

    // Step 2: Create repository
    $repository = new ProductRepository($pdo);
    echo "ProductRepository initialized!\n";

    // Step 3: Retrieve product
    $product = $repository->findById(1);

    // Step 4: Display result
    if ($product === null || $product === false) {
        echo "No product found with ID 1.\n";
        echo "Check the products table in phpMyAdmin.\n";
    } else {
        echo "Product retrieved successfully:\n";
        var_dump($product);
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Throwable $e) {
    echo "Test failed: " . $e->getMessage() . "\n";
    exit(1);
}