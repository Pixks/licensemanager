<?php

declare(strict_types=1);

namespace App\Services;

final class RateLimiterService
{
    public function __construct(private readonly string $storagePath) {}
    public function attempt(string $key, int $limit, int $window): bool
    {
        $file = $this->storagePath . '/' . sha1($key) . '.json';
        $now = time();
        $entries = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        $entries = array_values(array_filter($entries, static fn (int $timestamp): bool => $timestamp > $now - $window));
        if (count($entries) >= $limit) { file_put_contents($file, json_encode($entries)); return false; }
        $entries[] = $now; file_put_contents($file, json_encode($entries)); return true;
    }
}
