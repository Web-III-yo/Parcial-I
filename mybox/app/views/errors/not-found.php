<section class="alert alert-warning" role="alert">
    <h1>Error 404</h1>
    <p><?= escape($message ?? 'No se encontró el recurso solicitado.') ?></p>
    <a href="<?= escape(url()) ?>">Volver al inicio</a>
</section>
