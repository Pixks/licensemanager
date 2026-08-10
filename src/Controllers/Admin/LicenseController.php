<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class LicenseController extends Controller
{
    public function index(Request $request): Response { return $this->view('admin/licenses/index', ['pageTitle' => 'Licencje', 'licenses' => $this->app->licenseService()->searchLicenses($request->all()), 'products' => $this->app->productService()->listProducts(), 'filters' => $request->all()]); }
    public function create(Request $request): Response { return $this->view('admin/licenses/form', ['pageTitle' => 'Generator licencji', 'products' => $this->app->productService()->listProducts()]); }
    public function store(Request $request): Response
    {
        $licenses = $this->app->licenseService()->generateLicenses(['product_id' => (int) $request->input('product_id'), 'plan_name' => (string) $request->input('plan_name', 'default'), 'status' => (string) $request->input('status', 'active'), 'activation_limit' => (int) $request->input('activation_limit', 1), 'expires_at' => $request->input('expires_at') ?: null, 'updates_expires_at' => $request->input('updates_expires_at') ?: null, 'support_expires_at' => $request->input('support_expires_at') ?: null, 'is_lifetime' => $request->input('is_lifetime') === '1', 'customer_email' => $request->input('customer_email') ?: null, 'admin_note' => $request->input('admin_note') ?: null, 'updates_allowed' => $request->input('updates_allowed') === '1', 'support_active' => $request->input('support_active') === '1'], max(1, (int) $request->input('quantity', 1)));
        $_SESSION['_generated_licenses'] = array_map(static fn (array $item): array => ['plain_key' => $item['plain_key'], 'masked_key' => $item['record']['masked_key']], $licenses);
        return $this->redirect('/admin/licenses', 'Wygenerowano ' . count($licenses) . ' licencji.');
    }
    public function show(Request $request, array $params): Response
    {
        $pdo = $this->app->db(); $s = $pdo->prepare('SELECT l.*, p.name AS product_name FROM licenses l INNER JOIN products p ON p.id = l.product_id WHERE l.id = :id LIMIT 1'); $s->execute(['id' => (int) $params['id']]); $license = $s->fetch();
        $activations = $rules = $history = [];
        if ($license) {
            $a = $pdo->prepare('SELECT * FROM license_activations WHERE license_id = :license_id AND deleted_at IS NULL ORDER BY id DESC'); $a->execute(['license_id' => $license['id']]); $activations = $a->fetchAll() ?: [];
            $r = $pdo->prepare('SELECT * FROM license_domain_rules WHERE license_id = :license_id AND deleted_at IS NULL ORDER BY id DESC'); $r->execute(['license_id' => $license['id']]); $rules = $r->fetchAll() ?: [];
            $h = $pdo->prepare('SELECT * FROM audit_logs WHERE target_type = "license" AND target_id = :license_id ORDER BY id DESC'); $h->execute(['license_id' => $license['id']]); $history = $h->fetchAll() ?: [];
        }
        return $this->view('admin/licenses/show', ['pageTitle' => 'Licencja #' . $params['id'], 'license' => $license, 'activations' => $activations, 'rules' => $rules, 'history' => $history]);
    }
    public function updateStatus(Request $request, array $params): Response
    {
        $pdo = $this->app->db(); $before = $pdo->prepare('SELECT * FROM licenses WHERE id = :id LIMIT 1'); $before->execute(['id' => $params['id']]); $current = $before->fetch() ?: [];
        \App\Models\License::updateById($pdo, (int) $params['id'], ['status' => (string) $request->input('status', 'active'), 'activation_limit' => (int) $request->input('activation_limit', $current['activation_limit'] ?? 1), 'expires_at' => $request->input('expires_at') ?: null, 'updates_expires_at' => $request->input('updates_expires_at') ?: null, 'support_expires_at' => $request->input('support_expires_at') ?: null, 'updates_allowed' => $request->input('updates_allowed') === '1' ? 1 : 0, 'support_active' => $request->input('support_active') === '1' ? 1 : 0, 'customer_email' => $request->input('customer_email') ?: null, 'admin_note' => $request->input('admin_note') ?: null, 'updated_at' => date('Y-m-d H:i:s')]);
        $after = $pdo->prepare('SELECT * FROM licenses WHERE id = :id LIMIT 1'); $after->execute(['id' => $params['id']]);
        $this->app->auditLogService()->log($this->app->auth()->user()['id'] ?? null, 'license.updated', 'license', (int) $params['id'], $current, $after->fetch() ?: [], $request->ip());
        return $this->redirect('/admin/licenses/' . $params['id'], 'Licencja została zaktualizowana.');
    }
    public function addDomainRule(Request $request, array $params): Response
    {
        \App\Models\LicenseDomainRule::create($this->app->db(), ['license_id' => (int) $params['id'], 'rule_type' => (string) $request->input('rule_type', 'allow'), 'pattern' => (string) $request->input('pattern'), 'notes' => $request->input('notes') ?: null, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'deleted_at' => null]);
        return $this->redirect('/admin/licenses/' . $params['id'], 'Reguła domeny została dodana.');
    }
    public function exportCsv(Request $request): Response
    {
        $licenses = $this->app->licenseService()->searchLicenses($request->all()); $lines = [['id', 'product', 'status', 'masked_key', 'email', 'activation_limit', 'activations_in_use', 'expires_at']];
        foreach ($licenses as $license) $lines[] = [$license['id'], $license['product_name'], $license['status'], $license['masked_key'], $license['customer_email'], $license['activation_limit'], $license['activations_in_use'], $license['expires_at']];
        $body = ''; foreach ($lines as $line) $body .= implode(',', array_map(fn ($value): string => '"' . str_replace('"', '""', $this->sanitizeCsvCell((string) $value)) . '"', $line)) . "\n";
        return new Response($body, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="licenses.csv"']);
    }

    private function sanitizeCsvCell(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) ? "\t" . $value : $value;
    }
}
