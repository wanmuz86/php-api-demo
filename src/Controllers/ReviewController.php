<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Services\ProductService;
use App\Services\ReviewService;
use JsonException;

class ReviewController {
public function __construct(
private ReviewService $reviews,
private ProductService $products
) {}

private function productExists(int $id): bool {
    return $this->products->find($id) !== null;
}

private function body(): ?array {
    $raw = file_get_contents('php://input');
    try {
        $data = json_decode(
            $raw,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        Response::json(
            ['error' => 'Malformed JSON'],
            400
        );
        return null;
    }

    if (!is_array($data) || array_is_list($data)) {
        Response::json(
            ['error' => 'Expected a JSON object'],
            400
        );
        return null;
    }

    return $data;
}

private function validate(array $data): array {
    $errors = [];
    $fields = ['reviewer', 'rating', 'comment'];

    foreach ($fields as $field) {
        if (!array_key_exists($field, $data)) {
            $errors[$field] = 'Field is required';
        }
    }

    foreach (array_keys($data) as $field) {
        if (!in_array($field, $fields, true)) {
            $errors[$field] = 'Unknown field';
        }
    }

    foreach (['reviewer', 'comment'] as $field) {
        if (
            array_key_exists($field, $data) &&
            (
                !is_string($data[$field]) ||
                trim($data[$field]) === ''
            )
        ) {
            $errors[$field] = 'Must be a non-empty string';
        }
    }

    if (
        array_key_exists('rating', $data) &&
        (
            !is_int($data['rating']) ||
            $data['rating'] < 1 ||
            $data['rating'] > 5
        )
    ) {
        $errors['rating'] = 'Must be an integer from 1 to 5';
    }

    return $errors;
}

public function index(string $id): void {
    $productId = (int) $id;
    if (!$this->productExists($productId)) {
        Response::json(
            ['error' => 'Product not found'],
            404
        );
        return;
    }

    Response::json([
        'data' => $this->reviews->findByProduct($productId)
    ]);
}

public function show(
    string $id,
    string $reviewId
): void {
    $productId = (int) $id;
    if (!$this->productExists($productId)) {
        Response::json(
            ['error' => 'Product not found'],
            404
        );
        return;
    }

    $review = $this->reviews->find(
        $productId,
        (int) $reviewId
    );

    if ($review === null) {
        Response::json(
            ['error' => 'Review not found'],
            404
        );
        return;
    }

    Response::json(['data' => $review]);
}

public function store(string $id): void {
    $productId = (int) $id;
    if (!$this->productExists($productId)) {
        Response::json(
            ['error' => 'Product not found'],
            404
        );
        return;
    }

    $data = $this->body();
    if ($data === null) {
        return;
    }

    $errors = $this->validate($data);
    if ($errors !== []) {
        Response::json([
            'error' => 'Validation failed',
            'details' => $errors
        ], 422);
        return;
    }

    $review = $this->reviews->create(
        $productId,
        $data
    );

    header(
        'Location: /api/products/' . $productId . '/reviews/' . $review['id']
    );
    Response::json(['data' => $review], 201);
}

public function destroy(
    string $id,
    string $reviewId
): void {
    $productId = (int) $id;
    if (!$this->productExists($productId)) {
        Response::json(
            ['error' => 'Product not found'],
            404
        );
        return;
    }

    $deleted = $this->reviews->delete(
        $productId,
        (int) $reviewId
    );

    if (!$deleted) {
        Response::json(
            ['error' => 'Review not found'],
            404
        );
        return;
    }

    Response::json(null, 204);
}

}
