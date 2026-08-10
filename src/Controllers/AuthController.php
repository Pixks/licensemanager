<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;

final class AuthController extends Controller
{
    public function showLogin(Request $request): Response { return $this->view('auth/login', ['pageTitle' => 'Logowanie']); }
    public function login(Request $request): Response
    {
        $email = (string) $request->input('email'); $password = (string) $request->input('password');
        if (!$this->app->auth()->attempt($email, $password)) {
            $this->app->securityService()->log('failed_login', ['email' => $email, 'ip' => $request->ip(), 'user_agent' => $request->userAgent()]);
            return $this->redirect('/login', 'Nieprawidłowy e-mail lub hasło.', 'error');
        }
        return $this->redirect('/admin', 'Zalogowano pomyślnie.');
    }
    public function logout(Request $request): Response { $this->app->auth()->logout(); return $this->redirect('/login', 'Wylogowano.'); }
}
