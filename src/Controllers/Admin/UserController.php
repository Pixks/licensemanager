<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class UserController extends Controller
{
    public function index(Request $request): Response { return $this->view('admin/users/index', ['pageTitle' => 'Użytkownicy', 'users' => $this->app->userService()->all(), 'roles' => $this->app->db()->query('SELECT * FROM roles ORDER BY id ASC')->fetchAll() ?: []]); }
    public function store(Request $request): Response { $this->app->userService()->createOrUpdateAdmin((string) $request->input('name'), (string) $request->input('email'), (string) $request->input('password'), (string) $request->input('role', 'support')); return $this->redirect('/admin/users', 'Użytkownik został zapisany.'); }
}
