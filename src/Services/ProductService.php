<?php

declare(strict_types=1);

namespace App\Services;
use App\Repositories\ProductRepository;
use RuntimeException;

class ProductService
{
    

public function __construct(
    private ProductRepository $repository
) {}


    // private function read(): array
    // {
    //     $contents = file_get_contents($this->file);
    //     if ($contents === false) {
    //         throw new RuntimeException('Unable to read product data');
    //     }

    //     return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    // }

    // private function write(array $products): void
    // {
    //     $json = json_encode($products, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    //     if (file_put_contents($this->file, $json, LOCK_EX) === false) {
    //         throw new RuntimeException('Unable to save product data');
    //     }
    // }

    public function all(): array
    {
        return $this->repository->findAll();
    }
public function getById(int $id): ?array
{
    return $this->repository->findById($id);
}

public function create(array $data): int
{
    return $this->repository->create($data);
}

public function update(int $id, array $data): bool
{
    return $this->repository->update($id, $data);
}

public function delete(int $id): bool
{
    return $this->repository->delete($id);
}

public function search(
    ?string $name,
    int $limit,
    int $offset
): array {
    return $this->repository->search(
        $name,
        $limit,
        $offset
    );
}

public function count(?string $name): int
{
    return $this->repository->count($name);
}

}
