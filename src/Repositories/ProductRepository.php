<?php

namespace App\Repositories;

use PDO;

class ProductRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM products WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }
    public function findAll(): array
{
    $stmt = $this->db->query(
        'SELECT * FROM products ORDER BY id'
    );

    return $stmt->fetchAll();
}


    public function create(array $data): int
    {
        $sql = 'INSERT INTO products (sku, name, description, price) VALUES (:sku, :name, :description, :price)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'sku' => $data['sku'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
{
    $sql = 'UPDATE products SET name = :name, price = :price WHERE id = :id';
    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        'id' => $id,
        'name' => $data['name'],
        'price' => $data['price'],
    ]);

    return $stmt->rowCount() > 0;
}

public function delete(int $id): bool
{
    $stmt = $this->db->prepare(
        'DELETE FROM products WHERE id = :id'
    );
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}

public function search(
    ?string $name,
    int $limit,
    int $offset
): array {

    $sql = 'SELECT * FROM products';

    if ($name !== null) {
        $sql .= ' WHERE name LIKE :pattern';
    }

    $sql .= ' ORDER BY id ASC LIMIT :limit OFFSET :offset';

    $stmt = $this->db->prepare($sql);

    if ($name !== null) {
        $stmt->bindValue(
            ':pattern',
            "%{$name}%",
            PDO::PARAM_STR
        );
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetchAll();
}
public function count(?string $name): int
{
    $sql = 'SELECT COUNT(*) FROM products';

    if ($name !== null) {
        $sql .= ' WHERE name LIKE :pattern';
    }

    $stmt = $this->db->prepare($sql);

    if ($name !== null) {
        $stmt->bindValue(
            ':pattern',
            "%{$name}%",
            PDO::PARAM_STR
        );
    }

    $stmt->execute();

    return (int) $stmt->fetchColumn();
}




}
