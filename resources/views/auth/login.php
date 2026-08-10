<div class="row justify-content-center">
    <div class="col-lg-4 col-md-6">
        <div class="card shadow-sm"><div class="card-body p-4">
            <h1 class="h4 mb-3">Logowanie administratora</h1>
            <form method="post" action="/login">
                <input type="hidden" name="<?= e($csrfTokenName) ?>" value="<?= e($csrfToken) ?>">
                <div class="mb-3"><label class="form-label">E-mail</label><input type="email" name="email" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Hasło</label><input type="password" name="password" class="form-control" required></div>
                <button class="btn btn-primary w-100">Zaloguj się</button>
            </form>
        </div></div>
    </div>
</div>
