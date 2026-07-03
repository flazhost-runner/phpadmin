<?php

declare(strict_types=1);

namespace PHPAdmin\Core;

/**
 * Registry that maps named routes to their HTTP method + path,
 * and supports reverse URL generation.
 */
class RouteRegistry
{
    private static ?self $instance = null;

    /**
     * @var array<string, array{method: string, path: string}>
     */
    private array $routes = [];

    /**
     * Reverse map: 'METHOD:/path' => 'route_name'
     *
     * @var array<string, string>
     */
    private array $reverseMap = [];

    public function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a named route.
     */
    public function register(string $name, string $method, string $path): void
    {
        $method = strtoupper($method);
        $this->routes[$name] = ['method' => $method, 'path' => $path];
        $this->reverseMap[$method . ':' . $path] = $name;
    }

    /**
     * Register a GET route. Handler is ignored at registry level (routing only).
     *
     * @param array<int, mixed> $handler
     */
    public function get(string $path, array $handler, string $name): void
    {
        $this->register($name, 'GET', $path);
    }

    /**
     * Register a POST route.
     *
     * @param array<int, mixed> $handler
     */
    public function post(string $path, array $handler, string $name): void
    {
        $this->register($name, 'POST', $path);
    }

    /**
     * Register a PUT route.
     *
     * @param array<int, mixed> $handler
     */
    public function put(string $path, array $handler, string $name): void
    {
        $this->register($name, 'PUT', $path);
    }

    /**
     * Register a PATCH route.
     *
     * @param array<int, mixed> $handler
     */
    public function patch(string $path, array $handler, string $name): void
    {
        $this->register($name, 'PATCH', $path);
    }

    /**
     * Register a DELETE route.
     *
     * @param array<int, mixed> $handler
     */
    public function delete(string $path, array $handler, string $name): void
    {
        $this->register($name, 'DELETE', $path);
    }

    /**
     * Generate a URL for a named route by substituting {param} placeholders.
     *
     * @param array<string, string> $params
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->routes[$name])) {
            throw new \InvalidArgumentException("Route not found: {$name}");
        }

        $path = $this->routes[$name]['path'];

        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', rawurlencode((string)$value), $path);
        }

        // Remove any leftover optional placeholders like {param?}
        $path = preg_replace('/\{[^}]+\?\}/', '', $path) ?? $path;
        $path = preg_replace('/\{[^}]+\}/', '', $path) ?? $path;

        return $path;
    }

    /**
     * Find a route name by path and HTTP method (exact match first, then pattern).
     */
    public function getNameByPathAndMethod(string $path, string $method): ?string
    {
        $method = strtoupper($method);
        $key    = $method . ':' . $path;

        // Exact match
        if (isset($this->reverseMap[$key])) {
            return $this->reverseMap[$key];
        }

        // Pattern match: convert {param} to regex
        foreach ($this->routes as $name => $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $regex = preg_replace('/\{[^}]+\}/', '[^/]+', $route['path']);
            if ($regex === null) {
                continue;
            }
            if (preg_match('#^' . $regex . '$#', $path)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @return array<string, array{method: string, path: string}>
     */
    public function all(): array
    {
        return $this->routes;
    }

    public function hasRoute(string $name): bool
    {
        return isset($this->routes[$name]);
    }
}
