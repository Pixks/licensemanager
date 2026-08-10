<?php

declare(strict_types=1);

namespace App\Services;

final class LicenseKeyService
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    public function __construct(private readonly string $appKey) {}
    public function generate(int $groups = 5, int $charsPerGroup = 4): string
    {
        $segments = []; $max = strlen(self::ALPHABET) - 1;
        for ($group = 0; $group < $groups; $group++) {
            $segment = '';
            for ($i = 0; $i < $charsPerGroup; $i++) $segment .= self::ALPHABET[random_int(0, $max)];
            $segments[] = $segment;
        }
        return implode('-', $segments);
    }
    public function normalize(string $key): string { return strtoupper(trim(preg_replace('/[^A-Z0-9]/i', '', $key) ?? '')); }
    public function hash(string $key): string { return hash_hmac('sha256', $this->normalize($key), $this->appKey); }
    public function prefix(string $key): string { return substr($this->normalize($key), 0, 8); }
    public function suffix(string $key): string { return substr($this->normalize($key), -4); }
    public function mask(string $key): string
    {
        $chunks = str_split($this->normalize($key), 4);
        foreach ($chunks as $index => $chunk) if ($index > 1 && $index < count($chunks) - 1) $chunks[$index] = str_repeat('*', strlen($chunk));
        return implode('-', $chunks);
    }
}
