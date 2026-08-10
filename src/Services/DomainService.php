<?php

declare(strict_types=1);

namespace App\Services;

final class DomainService
{
    public function __construct(private readonly array $config) {}
    public function canonicalize(string $domainOrUrl): string
    {
        $value = trim(mb_strtolower($domainOrUrl));
        if ($value === '') return '';
        if (!str_contains($value, '://')) $value = 'https://' . $value;
        $host = parse_url($value, PHP_URL_HOST) ?: $value;
        $host = preg_replace('/^www\./', '', (string) $host) ?: '';
        return trim($host, '.');
    }
    public function normalizeSiteUrl(string $url): string { return rtrim(trim($url), '/'); }
    public function isDevelopmentDomain(string $canonicalDomain): bool
    {
        if ($canonicalDomain === 'localhost' || str_ends_with($canonicalDomain, '.local') || str_ends_with($canonicalDomain, '.test')) return (bool) ($this->config['allow_localhost'] ?? true);
        foreach (($this->config['allow_staging_keywords'] ?? []) as $keyword) if ($keyword !== '' && str_contains($canonicalDomain, mb_strtolower((string) $keyword))) return true;
        return false;
    }
    public function validate(string $canonicalDomain): bool
    {
        if ($canonicalDomain === '') return false;
        if ($this->isDevelopmentDomain($canonicalDomain)) return true;
        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $canonicalDomain);
    }
    public function matchesRule(string $canonicalDomain, string $pattern): bool
    {
        $pattern = mb_strtolower(trim($pattern));
        if ($pattern === '') return false;
        if (str_starts_with($pattern, '*.')) { $base = substr($pattern, 2); return $canonicalDomain === $base || str_ends_with($canonicalDomain, '.' . $base); }
        return $canonicalDomain === $pattern;
    }
}
