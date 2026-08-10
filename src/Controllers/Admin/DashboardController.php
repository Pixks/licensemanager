<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $pdo = $this->app->db();
        $stats = ['products' => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE deleted_at IS NULL')->fetchColumn(), 'licenses' => (int) $pdo->query('SELECT COUNT(*) FROM licenses WHERE deleted_at IS NULL')->fetchColumn(), 'activations' => (int) $pdo->query('SELECT COUNT(*) FROM license_activations WHERE activation_status = "active" AND deleted_at IS NULL')->fetchColumn(), 'downloads' => (int) $pdo->query('SELECT COUNT(*) FROM download_logs')->fetchColumn()];
        $recentLogs = $pdo->query('SELECT * FROM api_request_logs ORDER BY id DESC LIMIT 10')->fetchAll() ?: [];
        return $this->view('admin/dashboard', ['pageTitle' => 'Dashboard', 'stats' => $stats, 'recentLogs' => $recentLogs]);
    }
}
