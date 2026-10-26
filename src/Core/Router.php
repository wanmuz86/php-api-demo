<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
private array $routes = [];

public function add(string $method, string $path, callable $handler): void
{
    $this->routes[] = [
        'method'  => strtoupper($method),
        'path'    => $path,
        'handler' => $handler
    ];
}

public function dispatch(string $method, string $uri): void {
    $path = parse_url($uri, PHP_URL_PATH);
    $allowedMethods = [];

    foreach ($this->routes as $route) {
        $pattern = preg_replace(
            '/\{[a-zA-Z_][a-zA-Z0-9_]*\}/',
            '([0-9]+)',
            $route['path']
        );
        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $path, $matches)) {
            continue;
        }

        if ($route['method'] !== strtoupper($method)) {
            $allowedMethods[] = $route['method'];
            continue;
        }

        array_shift($matches);
        call_user_func_array(
            $route['handler'],
            $matches
        );
        return;
    }

    if ($allowedMethods !== []) {
        header(
            'Allow: ' . implode(
                ', ',
                array_unique($allowedMethods)
            )
        );
        Response::json(
            ['error' => 'Method Not Allowed'],
            405
        );
        return;
    }

    Response::json(
        ['error' => 'Endpoint Not Found'],
        404
    );
}
}