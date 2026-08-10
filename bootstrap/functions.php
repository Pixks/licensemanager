<?php

declare(strict_types=1);

function base_path(string $path = ''): string
{
    $base = __DIR__ . '/..';
    return $path ? $base . '/' . ltrim($path, '/') : $base;
}
function config_path(string $path = ''): string
{
    return base_path('config' . ($path ? '/' . ltrim($path, '/') : ''));
}
function resource_path(string $path = ''): string
{
    return base_path('resources' . ($path ? '/' . ltrim($path, '/') : ''));
}
function storage_path(string $path = ''): string
{
    return base_path('storage' . ($path ? '/' . ltrim($path, '/') : ''));
}
function public_path(string $path = ''): string
{
    return base_path('public' . ($path ? '/' . ltrim($path, '/') : ''));
}
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
