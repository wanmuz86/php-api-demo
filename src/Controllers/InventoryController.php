<?php

namespace App\Controllers;

use App\Core\Response;
use App\Services\InventoryService;
use DomainException;
use Throwable;

class InventoryController
{
    public function __construct(
        private InventoryService $service
    ) {}

    public function adjust(int $productId): void
    {
        // 1. Validate product ID
        if ($productId < 1) {
            Response::json([
                'error' => 'Invalid product ID'
            ], 422);
            return;
        }

        // 2. Read JSON request body
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        // 3. Validate JSON structure
        if (
            json_last_error() !== JSON_ERROR_NONE ||
            !is_array($data) ||
            array_is_list($data)
        ) {
            Response::json([
                'error' => 'Invalid JSON request body'
            ], 400);
            return;
        }

        // 4. Retrieve quantity_change
        $quantity = $data['quantity_change'] ?? null;

        // 5. Validate quantity_change
        if (
            !is_int($quantity) ||
            $quantity === 0 ||
            $quantity < -2147483648 ||
            $quantity > 2147483647
        ) {
            Response::json([
                'error' => 'Validation failed',
                'details' => [
                    'quantity_change must be a non-zero 32-bit integer'
                ]
            ], 422);
            return;
        }

        // 6. Retrieve reason
        $reason = $data['reason'] ?? null;

        // 7. Validate reason
        if (
            !is_string($reason) ||
            trim($reason) === '' ||
            mb_strlen(trim($reason), 'UTF-8') > 100
        ) {
            Response::json([
                'error' => 'Validation failed',
                'details' => [
                    'reason must contain between 1 and 100 characters'
                ]
            ], 422);
            return;
        }

        $reason = trim($reason);

        // 8. Execute inventory adjustment
        try {

            $result = $this->service->adjustStock(
                $productId,
                $quantity,
                $reason
            );

            // 9. Return success response
            Response::json([
                'message' => 'Stock adjusted successfully',
                'data' => $result
            ], 200);

        } catch (DomainException $e) {

            // 10. Handle known business errors
            $message = $e->getMessage();

            $status = match ($message) {
                'Product not found' => 404,
                'Insufficient stock' => 409,
                default => 422
            };

            Response::json([
                'error' => $message
            ], $status);

        } catch (Throwable $e) {

            // 11. Handle unexpected errors
            error_log((string) $e);

            Response::json([
                'error' => 'Unable to adjust stock'
            ], 500);
        }
    }
}

