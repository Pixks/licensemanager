<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class LogController extends Controller
{
    public function index(Request $request): Response
    {
        $pdo = $this->app->db();
        return $this->view('admin/logs/index', ['pageTitle' => 'Logi i audyt', 'apiLogs' => $pdo->query('SELECT * FROM api_request_logs ORDER BY id DESC LIMIT 100')->fetchAll() ?: [], 'downloadLogs' => $pdo->query('SELECT * FROM download_logs ORDER BY id DESC LIMIT 100')->fetchAll() ?: [], 'auditLogs' => $pdo->query('SELECT * FROM audit_logs ORDER BY id DESC LIMIT 100')->fetchAll() ?: [], 'securityEvents' => $pdo->query('SELECT * FROM security_events ORDER BY id DESC LIMIT 100')->fetchAll() ?: []]);
    }
}
