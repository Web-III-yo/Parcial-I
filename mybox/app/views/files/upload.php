<section class="panel datos1 w-full">
    <div class="panel-body p-4 sm:p-8">
        <a class="btn btn-outline-secondary btn-sm mb-4" href="<?= escape(url('files' . ($folderId === null ? '' : '?folder=' . $folderId))) ?>">← Volver a mis archivos</a>
        <div class="mb-6">
            <p class="eyebrow mb-2">Almacenamiento seguro</p>
            <h1 class="auth-heading mb-2">Agregar un archivo</h1>
            <p class="auth-copy mb-0">El archivo se guardará directamente en la base de datos MySQL.</p>
        </div>
        <?php if ($error !== null): ?>
            <div class="alert alert-danger rounded-3" role="alert"><?= escape($error) ?></div>
        <?php endif; ?>
        <form action="<?= escape(url('files/upload' . ($folderId === null ? '' : '?folder=' . $folderId))) ?>" method="post" enctype="multipart/form-data" class="flex flex-col gap-4">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
            <input type="hidden" name="folder_id" value="<?= escape((string) ($folderId ?? '')) ?>">
            <div class="upload-dropzone">
                <label class="form-label fw-semibold" for="txtArchi">Selecciona un archivo</label>
                <input class="form-control" id="txtArchi" name="txtArchi" type="file" required>
                <p class="form-text mb-0 mt-2">Tamaño máximo: 20 MB.</p>
            </div>
            <div>
                <label class="form-label fw-semibold" for="comentario">Comentario <span class="text-slate-500 fw-normal">(opcional, máximo 50 caracteres)</span></label>
                <input class="form-control" id="comentario" name="comentario" type="text" maxlength="50" placeholder="Describe brevemente el archivo">
            </div>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <a class="btn btn-light border px-4" href="<?= escape(url('files' . ($folderId === null ? '' : '?folder=' . $folderId))) ?>">Cancelar</a>
                <button class="btn btn-primary px-4" type="submit">Guardar archivo</button>
            </div>
        </form>
    </div>
</section>
