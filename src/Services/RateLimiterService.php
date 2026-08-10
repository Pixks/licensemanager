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
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            return true;
        }

        flock($handle, LOCK_EX);
        $contents = stream_get_contents($handle) ?: '';
        $entries = json_decode($contents, true) ?: [];
        $entries = array_values(array_filter($entries, static fn (int $timestamp): bool => $timestamp > $now - $window));

        if (count($entries) >= $limit) {
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) json_encode($entries));
            fflush($handle);
            flock($handle, LOCK_UN);
            fclose($handle);
            return false;
        }

        $entries[] = $now;
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) json_encode($entries));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return true;
    }
}
