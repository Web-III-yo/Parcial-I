<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="cache-control" content="no-cache">
    <title><?= escape($title ?? 'MyBOX') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        window.tailwind = {config: {corePlugins: {preflight: false}}};
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= escape(url('css/style_01.css')) ?>">
</head>
<body class="bg-slate-50 text-slate-800">
<div class="site-shell">
    <header class="site-header">
        <nav class="site-nav container py-2" aria-label="Navegación principal">
            <a class="navbar-brand flex items-center gap-3 m-0" href="<?= escape(url()) ?>" aria-label="MyBOX, inicio">
                <img class="utn-logo" src="<?= escape(url('img/encabe.png')) ?>" alt="Universidad Técnica Nacional">
                <span class="brand-divider" aria-hidden="true"></span>
                <span class="brand-name">MyBOX<small>Gestor de archivos</small></span>
            </a>
            <div class="site-nav-links">
                <?php if (($_SESSION['autenticado'] ?? '') === 'SI'): ?>
                    <a class="site-nav-link" href="<?= escape(url('files')) ?>">Mis archivos</a>
                    <a class="site-nav-link site-nav-logout" href="<?= escape(url('logout')) ?>">Cerrar sesión</a>
                <?php else: ?>
                    <a class="site-nav-link" href="<?= escape(url('register')) ?>">Crear cuenta</a>
                    <a class="site-nav-login" href="<?= escape(url('login')) ?>">Iniciar sesión</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <main class="site-main container flex-grow-1 py-4">
