<div class="auth-layout">
    <div style="width:100%;max-width:380px;padding:1rem">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3"
                 style="width:52px;height:52px;background:#4361ee1a">
                <i class="bi bi-shield-check fs-4" style="color:#4361ee"></i>
            </div>
            <h1 class="h5 fw-bold mb-1">License Manager</h1>
            <p class="text-muted small">Zaloguj się do panelu administratora</p>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="/login">
                    <input type="hidden" name="<?= e($csrfTokenName) ?>" value="<?= e($csrfToken) ?>">
                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control" required autofocus placeholder="admin@example.com">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Hasło</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Zaloguj się
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
