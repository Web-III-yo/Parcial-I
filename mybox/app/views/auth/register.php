<section class="auth-layout">
    <div class="auth-panel">
        <div class="auth-copy-block">
            <p class="eyebrow mb-2">MyBOX · UTN</p>
            <h1 class="auth-heading mb-2">Crear cuenta</h1>
            <p class="auth-description mb-0">Regístrate para guardar y compartir tus archivos.</p>
        </div>
        <?php if ($error !== null): ?>
            <div class="alert alert-danger rounded-3" role="alert"><?= escape($error) ?></div>
        <?php endif; ?>
        <form action="<?= escape(url('register')) ?>" method="post" class="auth-form auth-form-register">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
            <div>
                <label class="form-label fw-semibold" for="txtUsua">Usuario</label>
                <input class="form-control" id="txtUsua" type="text" name="txtUsua" maxlength="15" autocomplete="username" required>
            </div>
            <div>
                <label class="form-label fw-semibold" for="txtContra">Contraseña</label>
                <input class="form-control" id="txtContra" type="password" name="txtContra" autocomplete="new-password" required>
            </div>
            <div>
                <label class="form-label fw-semibold" for="txtNomb">Nombre completo</label>
                <input class="form-control" id="txtNomb" type="text" name="txtNomb" maxlength="30" autocomplete="name" required>
            </div>
            <div>
                <label class="form-label fw-semibold" for="txtEmail">Correo electrónico</label>
                <input class="form-control" id="txtEmail" type="email" name="txtEmail" maxlength="50" autocomplete="email" required>
            </div>
            <button class="btn btn-primary w-full" type="submit">Crear cuenta</button>
        </form>
        <p class="auth-switch mb-0">¿Ya tienes cuenta? <a href="<?= escape(url('login')) ?>">Inicia sesión</a></p>
    </div>
    <aside class="auth-visual" aria-label="Organización de carpetas">
        <img class="auth-illustration auth-illustration-folder" src="<?= escape(url('img/carpetas.avif')) ?>" alt="Ilustración de carpetas digitales">
        <p class="auth-visual-caption">Crea tu espacio y mantén tus documentos organizados.</p>
    </aside>
</section>
