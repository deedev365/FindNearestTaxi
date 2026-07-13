<?php

namespace Taxi\Utils;

class Router
{
    private array $routes = [];
    private string $method;
    private string $path;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public function dispatch(): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->method) {
                continue;
            }

            $pattern = $this->pathToPattern($route['path']);
            if (preg_match($pattern, $this->path, $matches)) {
                // preg_match() with named groups returns each captured value
                // twice (once under its name, once under its numeric index).
                // Keep only the named entries, in match order, so the handler
                // receives plain positional arguments instead of a mixed
                // named/positional array (which PHP 8 rejects outright).
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[] = $value;
                    }
                }
                call_user_func_array($route['handler'], $params);
                return;
            }
        }

        $this->notFound();
    }

    private function pathToPattern(string $path): string
    {
        $path = str_replace('/', '\/', $path);
        $path = preg_replace('/\{(\w+)\}/', '(?P<$1>\d+)', $path);
        return "/^$path$/";
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
}
