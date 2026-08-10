<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Validation\Validator;
use InvalidArgumentException;

final class ProductController extends Controller
{
    public function index(Request $request): Response { return $this->view('admin/products/index', ['pageTitle' => 'Produkty', 'products' => $this->app->productService()->listProducts()]); }
    public function create(Request $request): Response { return $this->view('admin/products/form', ['pageTitle' => 'Nowy produkt', 'product' => null, 'versions' => []]); }
    public function store(Request $request): Response
    {
        try { Validator::validate($request->all(), ['name' => 'required|string|max:255', 'slug' => 'required|string|max:120', 'default_channel' => 'required|in:stable,beta']); }
        catch (InvalidArgumentException $e) { return $this->redirect('/admin/products/create', 'Błąd walidacji: ' . $e->getMessage(), 'error'); }
        $product = $this->app->productService()->createProduct($request->all());
        $this->app->auditLogService()->log($this->app->auth()->user()['id'] ?? null, 'product.created', 'product', (int) $product['id'], [], $product, $request->ip());
        return $this->redirect('/admin/products/' . $product['id'], 'Produkt został utworzony.');
    }
    public function show(Request $request, array $params): Response
    {
        $product = $this->app->productService()->getById((int) $params['id']);
        return $this->view('admin/products/form', ['pageTitle' => 'Produkt: ' . ($product['name'] ?? ''), 'product' => $product, 'versions' => $product ? $this->app->productService()->versionsForProduct((int) $product['id']) : []]);
    }
    public function update(Request $request, array $params): Response
    {
        $this->app->productService()->updateProduct((int) $params['id'], $request->all());
        $this->app->auditLogService()->log($this->app->auth()->user()['id'] ?? null, 'product.updated', 'product', (int) $params['id'], [], $request->all(), $request->ip());
        return $this->redirect('/admin/products/' . $params['id'], 'Produkt został zapisany.');
    }
    public function storeVersion(Request $request, array $params): Response
    {
        try { Validator::validate($request->all(), ['version' => 'required|semver', 'channel' => 'required|in:stable,beta', 'release_status' => 'required|in:draft,published,archived', 'published_at' => 'nullable|date']); }
        catch (InvalidArgumentException $e) { return $this->redirect('/admin/products/' . $params['id'], 'Błąd walidacji wersji: ' . $e->getMessage(), 'error'); }
        $product = $this->app->productService()->getById((int) $params['id']); if (!$product) return $this->redirect('/admin/products', 'Nie znaleziono produktu.', 'error');
        $upload = $this->app->uploadService()->storeZip($request->file('zip_file') ?? [], (string) $product['slug'], (string) $request->input('version'));
        $version = $this->app->productService()->addVersion((int) $product['id'], array_merge($request->all(), $upload));
        $this->app->auditLogService()->log($this->app->auth()->user()['id'] ?? null, 'product.version.created', 'product_version', (int) $version['id'], [], $version, $request->ip());
        return $this->redirect('/admin/products/' . $params['id'], 'Wersja została dodana.');
    }
}
