<section class="auth-layout">
    <div class="auth-panel">
        <div class="auth-copy-block">
            <p class="eyebrow mb-2">MyBOX · UTN</p>
            <h1 class="auth-heading mb-2">Iniciar sesión</h1>
            <p class="auth-description mb-0">Accede a tus archivos y carpetas.</p>
        </div>
        <?php if ($error !== null): ?>
            <div class="alert alert-danger rounded-3" role="alert"><?= escape($error) ?></div>
        <?php endif; ?>
        <form action="<?= escape(url('login')) ?>" method="post" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
            <div>
                <label class="form-label fw-semibold" for="txtUsua">Usuario</label>
                <input class="form-control" id="txtUsua" type="text" name="txtUsua" maxlength="15" autocomplete="username" required>
            </div>
            <div>
                <label class="form-label fw-semibold" for="txtContra">Contraseña</label>
                <input class="form-control" id="txtContra" type="password" name="txtContra" autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary w-full" type="submit">Entrar</button>
        </form>
        <p class="auth-switch mb-0">¿No tienes cuenta? <a href="<?= escape(url('register')) ?>">Regístrate</a></p>
    </div>
    <aside class="auth-visual" aria-label="Organización de archivos">
        <img class="auth-illustration" src="<?= escape(url('img/archivos-compartidos.jpg')) ?>" alt="Ilustración de archivos organizados y compartidos">
        <p class="auth-visual-caption">Tus documentos en un solo lugar.</p>
    </aside>
</section>
