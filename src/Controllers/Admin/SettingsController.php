<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

final class SettingsController extends Controller
{
    public function edit(Request $request): Response { return $this->view('admin/settings/edit', ['pageTitle' => 'Ustawienia', 'settings' => $this->app->settingsService()->all()]); }
    public function update(Request $request): Response
    {
        foreach (['grace_period_days', 'notification_email', 'default_channel'] as $key) if ($request->input($key) !== null) $this->app->settingsService()->set($key, (string) $request->input($key));
        return $this->redirect('/admin/settings', 'Ustawienia zostały zapisane.');
    }
}
