<?php
namespace App\Core;

class Request
{
    public string $method;
    public string $uri;
    public array $query = [];
    public array $body = [];
    public array $params = []; // route params (e.g. {id})
    public array $headers = [];

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query = $_GET ?? [];
        $this->headers = self::getAllHeaders();

        $contentType = $this->headers['Content-Type'] ?? $this->headers['content-type'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            $this->body = is_array($decoded) ? $decoded : [];
        } elseif (str_contains($contentType, 'multipart/form-data') || str_contains($contentType, 'application/x-www-form-urlencoded')) {
            $this->body = $_POST ?? [];
        } else {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            $this->body = is_array($decoded) ? $decoded : ($_POST ?? []);
        }
    }

    private static function getAllHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $h = getallheaders();
            return $h ?: [];
        }
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->headers['Authorization'] ?? $this->headers['authorization'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $auth, $m)) {
            return $m[1];
        }
        return null;
    }

    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function param(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }
}
