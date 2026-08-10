<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class ActivationController extends Controller
{
    public function index(Request $request): Response
    {
        $statement = $this->app->db()->query('SELECT la.*, l.masked_key, p.name AS product_name FROM license_activations la INNER JOIN licenses l ON l.id = la.license_id INNER JOIN products p ON p.id = la.product_id WHERE la.deleted_at IS NULL ORDER BY la.id DESC');
        return $this->view('admin/activations/index', ['pageTitle' => 'Aktywacje', 'activations' => $statement->fetchAll() ?: []]);
    }
    public function release(Request $request, array $params): Response { $this->app->licenseService()->releaseActivation((int) $params['id']); return $this->redirect('/admin/activations', 'Aktywacja została zwolniona.'); }
}
