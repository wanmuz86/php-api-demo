<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class ReviewService {
    public function __construct(
        private string $file
    ) {}

    private function read(): array {
        $contents = file_get_contents($this->file);
        if ($contents === false) {
            throw new RuntimeException(
                'Unable to read review data'
            );
        }
        return json_decode(
            $contents,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    private function write(array $reviews): void {
        $json = json_encode(
            $reviews,
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
        );
        if (file_put_contents(
            $this->file,
            $json,
            LOCK_EX
        ) === false) {
            throw new RuntimeException(
                'Unable to save review data'
            );
        }
    }

    public function findByProduct(int $productId): array {
        return array_values(
            array_filter(
                $this->read(),
                fn($review) => $review['product_id'] === $productId
            )
        );
    }

    public function find(
        int $productId,
        int $reviewId
    ): ?array {
        foreach ($this->read() as $review) {
            if (
                $review['id'] === $reviewId &&
                $review['product_id'] === $productId
            ) {
                return $review;
            }
        }
        return null;
    }

    public function create(
        int $productId,
        array $data
    ): array {
        $reviews = $this->read();
        $id = empty($reviews)
            ? 1
            : max(array_column($reviews, 'id')) + 1;

        $review = [
            'id' => $id,
            'product_id' => $productId,
            'reviewer' => $data['reviewer'],
            'rating' => $data['rating'],
            'comment' => $data['comment']
        ];

        $reviews[] = $review;
        $this->write($reviews);

        return $review;
    }

    public function delete(
        int $productId,
        int $reviewId
    ): bool {
        $reviews = $this->read();
        $filtered = array_values(
            array_filter(
                $reviews,
                fn($review) => !(
                    $review['id'] === $reviewId &&
                    $review['product_id'] === $productId
                )
            )
        );

        if (count($filtered) === count($reviews)) {
            return false;
        }

        $this->write($filtered);
        return true;
    }
}
