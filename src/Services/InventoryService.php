<?php

namespace App\Services;

use PDO;
use Throwable;
use DomainException;
use RuntimeException;

class InventoryService
{
    public function __construct(
        private PDO $db
    ) {}

    public function adjustStock(
        int $productId,
        int $quantityChange,
        string $reason
    ): array {

        // 1. Validate the adjustment quantity
        if ($quantityChange === 0) {
            throw new DomainException(
                'Quantity change cannot be zero'
            );
        }

        // 2. Start database transaction
        $this->db->beginTransaction();

        try {

            // 3. Retrieve and lock product row
            $stmt = $this->db->prepare(
                'SELECT stock_quantity
                 FROM products
                 WHERE id = :id
                 FOR UPDATE'
            );

            $stmt->execute([
                'id' => $productId
            ]);

            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            // 4. Check if product exists
            if (!$product) {
                throw new DomainException(
                    'Product not found'
                );
            }

            // 5. Calculate updated stock
            $previousStock =
                (int) $product['stock_quantity'];

            $newStock =
                $previousStock + $quantityChange;

            // 6. Prevent negative stock
            if ($newStock < 0) {
                throw new DomainException(
                    'Insufficient stock'
                );
            }

            // Prevent values beyond the INT UNSIGNED column range
            if ($newStock > 4294967295) {
                throw new DomainException(
                    'Stock quantity exceeds allowed maximum'
                );
            }

            // 7. Update product stock
            $update = $this->db->prepare(
                'UPDATE products
                 SET stock_quantity = :stock
                 WHERE id = :id'
            );

            $update->execute([
                'stock' => $newStock,
                'id' => $productId
            ]);

            // 8. Record inventory movement
            $movement = $this->db->prepare(
                'INSERT INTO inventory_movements
                    (product_id, quantity_change, reason)
                 VALUES
                    (:product_id, :change, :reason)'
            );

            $movement->execute([
                'product_id' => $productId,
                'change' => $quantityChange,
                'reason' => $reason
            ]);

            // 9. Commit both operations
            $this->db->commit();

            // 10. Return adjustment information
            return [
                'product_id' => $productId,
                'previous_stock' => $previousStock,
                'quantity_change' => $quantityChange,
                'new_stock' => $newStock
            ];

        } catch (Throwable $e) {

            // Undo uncommitted database changes
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            // Pass error to the controller
            throw $e;
        }
    }
}
