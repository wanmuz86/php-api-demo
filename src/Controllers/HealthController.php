<?php

declare(strict_types=1);

namespace App\Controllers;

class HealthController
{
    public function index(): array
    {
        return [
            'status' => 'ok',
            'message' => 'PHP REST API is running',
            'timestamp' => gmdate('c')
        ];
    }
}
