<?php
namespace Core;

class Request
{
    /**
     * Authenticated user info (JWT payload)
     */
    public $user = null;
    private string $method;
    private string $path;
    private array $headers;
    private array $query;
    private array $body;

    public function __construct(string $method, string $path, array $headers = [], array $query = [], array $body = [])
    {
        $this->method = strtoupper($method);
        $this->path = '/' . ltrim($path, '/');
        $this->headers = $headers;
        $this->query = $query;
        $this->body = $body;
    }

    public static function fromGlobals(): self
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $input = file_get_contents('php://input');
        $body = $_POST ?: (json_decode($input, true) ?? []);
        return new self($method, $path, $headers, $_GET, $body);
    }

    public function getMethod(): string { return $this->method; }
    public function getPath(): string { return $this->path; }
    public function getHeaders(): array { return $this->headers; }
    public function getHeader(string $name, $default = null) { return $this->headers[$name] ?? $this->headers[strtolower($name)] ?? $default; }
    public function getQuery(): array { return $this->query; }
    public function getBody(): array { return $this->body; }
}