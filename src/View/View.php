<?php

declare(strict_types=1);

namespace App\View;

final class View
{
    public function __construct(private readonly string $basePath) {}
    public function render(string $template, array $data = [], string $layout = 'layouts/app'): string
    {
        $templateFile = $this->basePath . '/' . $template . '.php';
        if (!is_file($templateFile)) throw new \RuntimeException('View not found: ' . $template);
        extract($data, EXTR_SKIP);
        ob_start(); include $templateFile; $content = (string) ob_get_clean();
        $layoutFile = $this->basePath . '/' . $layout . '.php';
        if (!is_file($layoutFile)) return $content;
        ob_start(); include $layoutFile; return (string) ob_get_clean();
    }
}
