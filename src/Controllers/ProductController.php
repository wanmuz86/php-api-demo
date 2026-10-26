<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use JsonException;

class ProductController
{
    public function __construct(
        private ProductService $service
    ) {}

    private function body(): ?array
    {
        $raw = file_get_contents('php://input');

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Response::json(['error' => 'Malformed JSON'], 400);
            return null;
        }

        if (!is_array($data) || array_is_list($data)) {
            Response::json(['error' => 'Expected a JSON object'], 400);
            return null;
        }

        return $data;
    }

    private function validate(array $data, bool $partial = false): array
    {
        $errors = [];
        $fields = ['name', 'description', 'price', 'stock', 'sku'];

        foreach ($fields as $field) {
            if (!$partial && !array_key_exists($field, $data)) {
                $errors[$field] = 'Field is required';
            }
        }

        foreach (array_keys($data) as $field) {
            if (!in_array($field, $fields, true)) {
                $errors[$field] = 'Unknown field';
            }
        }

        if (array_key_exists('name', $data) && (!is_string($data['name']) || trim($data['name']) === '')) {
            $errors['name'] = 'Must be a non-empty string';
        }

        if (array_key_exists('description', $data) && !is_string($data['description'])) {
            $errors['description'] = 'Must be a string';
        }

        if (array_key_exists('price', $data) && (!is_numeric($data['price']) || !is_int($data['price']) && !is_float($data['price']) || !is_finite((float) $data['price']) || $data['price'] < 0)) {
            $errors['price'] = 'Must be a non-negative number';
        }

        if (array_key_exists('stock', $data) && (!is_int($data['stock']) || $data['stock'] < 0)) {
            $errors['stock'] = 'Must be a non-negative integer';
        }

        return $errors;
    }

    public function index(): void
{
    // 1. Read query parameters
    $name = $_GET['name'] ?? null;
    $pageInput = $_GET['page'] ?? '1';
    $limitInput = $_GET['limit'] ?? '10';

    // 2. Validate the name filter
    if ($name !== null) {
        if (!is_string($name)) {
            Response::json([
                'error' => 'Invalid name parameter'
            ], 422);
            return;
        }

        $name = trim($name);

        // Empty search means no filtering
        if ($name === '') {
            $name = null;
        }
    }

    // 3. Validate page and limit as integers
    $page = filter_var(
        $pageInput,
        FILTER_VALIDATE_INT
    );

    $limit = filter_var(
        $limitInput,
        FILTER_VALIDATE_INT
    );

    if ($page === false || $limit === false) {
        Response::json([
            'error' => 'Invalid pagination parameters',
            'details' => [
                'page and limit must be integers'
            ]
        ], 422);
        return;
    }

    // 4. Enforce pagination bounds
    $maxLimit = 100;

    if ($page < 1 || $limit < 1 || $limit > $maxLimit) {
        Response::json([
            'error' => 'Invalid pagination parameters',
            'details' => [
                'page must be at least 1',
                'limit must be between 1 and 100'
            ]
        ], 422);
        return;
    }

    // 5. Prevent integer overflow
    if ($page - 1 > intdiv(PHP_INT_MAX, $limit)) {
        Response::json([
            'error' => 'Page number is too large'
        ], 422);
        return;
    }

    // 6. Calculate offset
    $offset = ($page - 1) * $limit;

    // 7. Retrieve matching products
    $products = $this->service->search(
        $name,
        $limit,
        $offset
    );

    // 8. Count matching products
    $total = $this->service->count($name);

    // 9. Calculate total pages
    $totalPages = (int) ceil($total / $limit);

    // 10. Return JSON response
    Response::json([
        'data' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $totalPages
        ]
    ], 200);
}


public function show(int $id): void
{
    header('Content-Type: application/json');

    $product = $this->service->getById($id);

    if (!$product) {
        http_response_code(404);

        echo json_encode([
            'error' => 'Product not found'
        ]);
        return;
    }

    http_response_code(200);
    echo json_encode($product);
}



    public function store(): void
    {
        $data = $this->body();
        if ($data === null) {
            return;
        }

        $errors = $this->validate($data);
        if ($errors !== []) {
            Response::json([
                'error'   => 'Validation failed',
                'details' => $errors
            ], 422);
            return;
        }

        $productId = $this->service->create($data);
        $product = $this->service->getById($productId);

        header('Location: /api/products/' . $productId);

    Response::json([
        'data' => $product
    ], 201);
    }

    public function update(string $id): void
    {
        $data = $this->body();
        if ($data === null) {
            return;
        }

        $errors = $this->validate($data);
        if ($errors !== []) {
            Response::json([
                'error'   => 'Validation failed',
                'details' => $errors
            ], 422);
            return;
        }

        $product = $this->service->update((int) $id, $data);
        if ($product === null) {
            Response::json(['error' => 'Product not found'], 404);
            return;
        }

        Response::json(['data' => $product]);
    }

    public function patch(string $id): void
    {
        $data = $this->body();
        if ($data === null) {
            return;
        }

        $errors = $this->validate($data, true);
        if ($errors !== []) {
            Response::json([
                'error'   => 'Validation failed',
                'details' => $errors
            ], 422);
            return;
        }

        $product = $this->service->update((int) $id, $data);
        if ($product === null) {
            Response::json(['error' => 'Product not found'], 404);
            return;
        }

        Response::json(['data' => $product]);
    }

public function destroy(int $id): void
{
    try {
        $deleted = $this->service->delete($id);

        if (!$deleted) {
            http_response_code(404);
            header('Content-Type: application/json');

            echo json_encode([
                'error' => 'Product not found'
            ]);
            return;
        }

        http_response_code(204);

    } catch (\PDOException $e) {
        $driverCode = (int) (
            $e->errorInfo[1] ?? 0
        );

        if ($driverCode === 1451) {
            http_response_code(409);
            header('Content-Type: application/json');

            echo json_encode([
                'error' => 'PRODUCT_IN_USE',
                'message' =>
                    'Product has related inventory movements'
            ]);
            return;
        }

        error_log((string) $e);

        http_response_code(500);
        header('Content-Type: application/json');

        echo json_encode([
            'error' => 'Internal server error'
        ]);
    }
}

}
