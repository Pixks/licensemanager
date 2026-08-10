<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    public function __construct(public string $body = '', public int $status = 200, public array $headers = []) {}
    public static function html(string $body, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        return new self($body, $status, $headers);
    }
    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=UTF-8';
        return new self((string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), $status, $headers);
    }
    public static function redirect(string $location, int $status = 302): self { return new self('', $status, ['Location' => $location]); }
    public static function download(string $filePath, string $downloadName, array $headers = []): self
    {
        $headers = array_merge([
            'Content-Type' => mime_content_type($filePath) ?: 'application/zip',
            'Content-Length' => (string) filesize($filePath),
            'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
            'X-Accel-Buffering' => 'no',
        ], $headers);
        return new self((string) file_get_contents($filePath), 200, $headers);
    }
    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }
        echo $this->body;
    }
}
