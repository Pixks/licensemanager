<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Validation\Validator;
use InvalidArgumentException;
use RuntimeException;

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
        $versions = $product ? $this->app->productService()->versionsForProduct((int) $product['id']) : [];
        $editingVersion = null;
        $requestedVersionId = (int) $request->query('edit_version', 0);
        if ($requestedVersionId > 0) {
            foreach ($versions as $version) {
                if ((int) $version['id'] === $requestedVersionId) {
                    $editingVersion = $version;
                    break;
                }
            }
        }
        return $this->view('admin/products/form', ['pageTitle' => 'Produkt: ' . ($product['name'] ?? ''), 'product' => $product, 'versions' => $versions, 'editingVersion' => $editingVersion, 'canDeleteProduct' => $product ? !$this->app->productService()->hasProductDependencies((int) $product['id']) : false]);
    }
    public function update(Request $request, array $params): Response
    {
        $before = $this->app->productService()->getById((int) $params['id']) ?? [];
        $this->app->productService()->updateProduct((int) $params['id'], $request->all());
        $after = $this->app->productService()->getById((int) $params['id']) ?? [];
        $this->app->auditLogService()->log($this->app->auth()->user()['id'] ?? null, 'product.updated', 'product', (int) $params['id'], $before, $after, $request->ip());
        return $this->redirect('/admin/products/' . $params['id'], 'Produkt został zapisany.');
    }
    public function storeVersion(Request $request, array $params): Response
    {
        try { Validator::validate($request->all(), ['version' => 'required|semver', 'channel' => 'required|in:stable,beta', 'release_status' => 'required|in:draft,published,archived', 'published_at' => 'nullable|date']); }
        catch (InvalidArgumentException $e) { return $this->redirect('/admin/products/' . $params['id'], 'Błąd walidacji wersji: ' . $e->getMessage(), 'error'); }
        $product = $this->app->productService()->getById((int) $params['id']); if (!$product) return $this->redirect('/admin/products', 'Nie znaleziono produktu.', 'error');
        try { $upload = $this->app->uploadService()->storeZip($request->file('zip_file') ?? [], (string) $product['slug'], (string) $request->input('version')); }
        catch (RuntimeException $e) { return $this->redirect('/admin/products/' . $params['id'], $e->getMessage(), 'error'); }
        $version = $this->app->productService()->addVersion((int) $product['id'], array_merge($request->all(), $upload));
        $this->app->auditLogService()->log($this->app->auth()->user()['id'] ?? null, 'product.version.created', 'product_version', (int) $version['id'], [], $version, $request->ip());
        return $this->redirect('/admin/products/' . $params['id'], 'Wersja została dodana.');
    }
    public function updateVersion(Request $request, array $params): Response
    {
        try { Validator::validate($request->all(), ['version' => 'required|semver', 'channel' => 'required|in:stable,beta', 'release_status' => 'required|in:draft,published,archived', 'published_at' => 'nullable|date']); }
        catch (InvalidArgumentException $e) { return $this->redirect('/admin/products/' . $params['id'] . '?edit_version=' . $params['versionId'], 'Błąd walidacji wersji: ' . $e->getMessage(), 'error'); }
        $product = $this->app->productService()->getById((int) $params['id']); if (!$product) return $this->redirect('/admin/products', 'Nie znaleziono produktu.', 'error');
        $before = $this->app->productService()->getVersionById((int) $params['id'], (int) $params['versionId']);
        if (!$before) return $this->redirect('/admin/products/' . $params['id'], 'Nie znaleziono wersji.', 'error');
        $payload = $request->all();
        $file = $request->file('zip_file') ?? [];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try { $payload = array_merge($payload, $this->app->uploadService()->storeZip($file, (string) $product['slug'], (string) $request->input('version'))); }
            catch (RuntimeException $e) { return $this->redirect('/admin/products/' . $params['id'] . '?edit_version=' . $params['versionId'], $e->getMessage(), 'error'); }
        }
        $after = $this->app->productService()->updateVersion((int) $params['id'], (int) $params['versionId'], $payload);
        if (!$after) return $this->redirect('/admin/products/' . $params['id'], 'Nie znaleziono wersji.', 'error');
        $this->app->auditLogService()->log($this->app->auth()->user()['id'] ?? null, 'product.version.updated', 'product_version', (int) $params['versionId'], $before, $after, $request->ip());
        return $this->redirect('/admin/products/' . $params['id'], 'Wersja została zaktualizowana.');
    }
    public function deleteVersion(Request $request, array $params): Response
    {
        $before = $this->app->productService()->getVersionById((int) $params['id'], (int) $params['versionId']);
        if (!$before) return $this->redirect('/admin/products/' . $params['id'], 'Nie znaleziono wersji.', 'error');
        $this->app->productService()->deleteVersion((int) $params['id'], (int) $params['versionId']);
        $this->app->auditLogService()->log($this->app->auth()->user()['id'] ?? null, 'product.version.deleted', 'product_version', (int) $params['versionId'], $before, ['deleted' => true], $request->ip());
        return $this->redirect('/admin/products/' . $params['id'], 'Wersja została usunięta.');
    }
    public function destroy(Request $request, array $params): Response
    {
        try { $deleted = $this->app->productService()->deleteProduct((int) $params['id']); }
        catch (RuntimeException $e) {
            if ($e->getMessage() === 'product_has_licenses') return $this->redirect('/admin/products/' . $params['id'], 'Nie można usunąć produktu z przypisanymi licencjami.', 'error');
            throw $e;
        }
        if (!$deleted) return $this->redirect('/admin/products', 'Nie znaleziono produktu.', 'error');
        $this->app->auditLogService()->log($this->app->auth()->user()['id'] ?? null, 'product.deleted', 'product', (int) $params['id'], $deleted['product'], ['deleted_versions' => count($deleted['versions'])], $request->ip());
        return $this->redirect('/admin/products', 'Produkt został usunięty.');
    }
}
