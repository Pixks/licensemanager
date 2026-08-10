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
        $content = $this->renderFile($templateFile, $data);
        $layoutFile = $this->basePath . '/' . $layout . '.php';
        if (!is_file($layoutFile)) return $content;
        return $this->renderFile($layoutFile, array_merge($data, ['content' => $content]));
    }

    private function renderFile(string $file, array $data): string
    {
        ob_start();
        (static function (string $__file, array $__data): void {
            extract($__data, EXTR_SKIP);
            include $__file;
        })($file, $data);
        return (string) ob_get_clean();
    }
}
