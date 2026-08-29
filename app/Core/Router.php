<?php
namespace App\Core;

/**
 * Minimal regex-based router. Supports {param} placeholders and
 * {param:*} wildcard placeholders (matches the rest of the path,
 * used for our SPA "catch-all" routes like /{slug}portal/*).
 */
class Router
{
    private array $routes = []; // [method][] = ['pattern' => regex, 'params' => [...], 'handler' => callable, 'middlewares' => []]

    public function add(string $method, string $pattern, callable $handler, array $middlewares = []): void
    {
        [$regex, $paramNames] = $this->compile($pattern);
        $this->routes[strtoupper($method)][] = [
            'regex'       => $regex,
            'params'      => $paramNames,
            'handler'     => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function get(string $pattern, callable $handler, array $mw = []): void { $this->add('GET', $pattern, $handler, $mw); }
    public function post(string $pattern, callable $handler, array $mw = []): void { $this->add('POST', $pattern, $handler, $mw); }
    public function put(string $pattern, callable $handler, array $mw = []): void { $this->add('PUT', $pattern, $handler, $mw); }
    public function delete(string $pattern, callable $handler, array $mw = []): void { $this->add('DELETE', $pattern, $handler, $mw); }
    public function any(string $pattern, callable $handler, array $mw = []): void
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $m) $this->add($m, $pattern, $handler, $mw);
    }

    private function compile(string $pattern): array
    {
        $paramNames = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_]+)(:\*)?\}#', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return isset($m[2]) ? '(.*)' : '([^/]+)';
        }, $pattern);
        $regex = '#^' . $regex . '$#';
        return [$regex, $paramNames];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method === 'OPTIONS' ? 'OPTIONS' : $request->method;

        if ($method === 'OPTIONS') {
            // CORS preflight support
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            http_response_code(204);
            exit;
        }

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        $uri = rtrim($request->uri, '/');
        if ($uri === '') $uri = '/';

        $candidates = $this->routes[$method] ?? [];
        foreach ($candidates as $route) {
            if (preg_match($route['regex'], $uri, $matches)) {
                array_shift($matches);
                $params = array_combine($route['params'], $matches);
                $request->params = $params;

                foreach ($route['middlewares'] as $mw) {
                    $result = $mw($request);
                    if ($result === false) return; // middleware already sent a response
                }

                call_user_func($route['handler'], $request);
                return;
            }
        }

        Response::error('Route not found: ' . $uri, 404);
    }
}
