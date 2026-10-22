<section class="panel datos2">
    <div class="panel-body">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="files-heading">
                <a
                    class="back-link"
                    href="<?= escape(url('logout')) ?>"
                    aria-label="Cerrar sesión y regresar al inicio de sesión"
                >
                    <span aria-hidden="true">←</span> Volver al inicio de sesión
                </a>
                <p class="eyebrow mb-1">MyBOX</p>
                <h1 class="dashboard-title mb-1"><?= $folderId === null ? 'Mis archivos' : 'Contenido de la carpeta' ?></h1>
                <p class="auth-description mb-0">Hola, <?= escape((string) ($_SESSION['nombre'] ?? $_SESSION['usuario'])) ?>.</p>
            </div>
            <span class="file-meta">Archivos guardados en MySQL</span>
        </div>

        <?php if ($canManageDirectory): ?>
            <div class="mb-3 flex flex-col gap-2 rounded-3 border bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                <a class="btn btn-primary px-3" href="<?= escape(url('files/upload' . ($folderId === null ? '' : '?folder=' . $folderId))) ?>">+ Agregar archivo</a>
                <form action="<?= escape(url('files/folder')) ?>" method="post" class="folder-create-form flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                    <input type="hidden" name="folder_id" value="<?= escape((string) ($folderId ?? '')) ?>">
                    <input class="form-control" type="text" name="folder_name" maxlength="255" placeholder="Nombre de carpeta" aria-label="Nombre de carpeta" required>
                    <button class="btn btn-outline-primary text-nowrap" type="submit">Crear carpeta</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info rounded-3 py-2" role="status">Esta carpeta fue compartida contigo. Puedes ver y descargar su contenido.</div>
        <?php endif; ?>

        <?php if ($files === []): ?>
            <div class="rounded-3 border bg-slate-50 px-4 py-10 text-center">
                <h2 class="h6 fw-semibold text-slate-800">No hay archivos en esta ubicación</h2>
                <p class="mb-0 text-slate-500"><?= $canManageDirectory ? 'Agrega un archivo o crea una carpeta.' : 'Los archivos compartidos aparecerán aquí.' ?></p>
            </div>
        <?php else: ?>
            <div class="file-list">
                <div class="file-list-heading" aria-hidden="true">
                    <span>Nombre y comentario</span>
                    <span>Documento / peso</span>
                    <span>Fecha</span>
                    <span>Compartido</span>
                    <span>Acciones</span>
                </div>
                <?php foreach ($files as $file): ?>
                    <?php
                    $isDirectory = (bool) $file['es_directorio'];
                    $isSharedWithMe = (bool) $file['shared_with_me'];
                    ?>
                    <article class="file-row">
                        <div class="file-main">
                            <?php if ($isDirectory): ?>
                                <a class="file-link" href="<?= escape(url('files?folder=' . (int) $file['id'])) ?>">
                                    <svg class="file-icon file-icon-folder" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3.5 7.5A1.5 1.5 0 0 1 5 6h5l2 2h7a1.5 1.5 0 0 1 1.5 1.5v8A1.5 1.5 0 0 1 19 19H5a1.5 1.5 0 0 1-1.5-1.5v-10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                    <?= escape($file['name']) ?>
                                </a>
                            <?php else: ?>
                                <a class="file-link" href="<?= escape(url('files/download?id=' . (int) $file['id'])) ?>">
                                    <svg class="file-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3.75h6l4.25 4.5v12H7a1.25 1.25 0 0 1-1.25-1.25V5A1.25 1.25 0 0 1 7 3.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M13 4v5h4.5M8.5 13h6M8.5 16.5h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                    <?= escape($file['name']) ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($isSharedWithMe): ?>
                                <span class="file-shared-owner">Compartido por <?= escape($file['owner_username']) ?></span>
                            <?php endif; ?>
                            <?php if (trim((string) ($file['comentario'] ?? '')) !== ''): ?>
                                <span class="file-comment"><?= escape($file['comentario']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="file-details">
                            <span class="file-tag"><?= $isDirectory ? 'Carpeta' : escape(document_type_label($file['mime_type'], $file['name'])) ?></span>
                            <?php if (!$isDirectory): ?>
                                <span class="file-tag"><?= number_format((float) ($file['peso_mb'] ?? 0), 4) ?> MB</span>
                            <?php endif; ?>
                        </div>
                        <div class="file-date file-meta"><?= escape(date('d/m/Y H:i', (int) $file['uploaded_at'])) ?></div>
                        <div class="file-sharing">
                            <span class="file-tag <?= (bool) $file['is_shared'] ? 'file-tag-shared' : '' ?>">Compartido: <?= (bool) $file['is_shared'] ? 'Sí' : 'No' ?></span>
                            <span class="file-tag <?= $isSharedWithMe ? 'file-tag-received' : '' ?>">Conmigo: <?= $isSharedWithMe ? 'Sí' : 'No' ?></span>
                        </div>
                        <div class="file-actions">
                            <?php if (!$isSharedWithMe): ?>
                                <div class="action-stack">
                                    <form action="<?= escape(url('files/share')) ?>" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $file['id'] ?>">
                                        <input type="hidden" name="folder_id" value="<?= escape((string) ($folderId ?? '')) ?>">
                                        <input class="form-control form-control-sm" type="text" name="recipient" maxlength="50" placeholder="Usuario" aria-label="Usuario con quien compartir" required>
                                        <button class="btn btn-sm btn-outline-primary text-nowrap" type="submit">Compartir</button>
                                    </form>
                                    <form action="<?= escape(url('files/delete')) ?>" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $file['id'] ?>">
                                        <input type="hidden" name="folder_id" value="<?= escape((string) ($folderId ?? '')) ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Borrar</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="file-meta">Solo lectura</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
