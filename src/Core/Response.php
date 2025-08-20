<?php
namespace Core;

class Response
{
    private int $status = 200;
    private array $headers = ['Content-Type' => 'application/json; charset=utf-8'];
    private string $body = '';

    public function json($data, int $status = 200): self
    {
        $this->status = $status;
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        $this->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $this;
    }

    public function text(string $text, int $status = 200, string $contentType = 'text/plain'): self
    {
        $this->status = $status;
        $this->headers['Content-Type'] = $contentType . '; charset=utf-8';
        $this->body = $text;
        return $this;
    }

    public function header(string $name, string $value): self { $this->headers[$name] = $value; return $this; }
    public function status(int $code): self { $this->status = $code; return $this; }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $k => $v) header($k . ': ' . $v);
        echo $this->body;
    }
}