<?php
class FileController extends Controller
{
    private function manager(): FileManager
    {
        return new FileManager();
    }

    public function index(): void
    {
        $this->requireAuthentication();
        $manager = $this->manager();
        if (!$manager->userExists((string) $_SESSION['usuario'])) {
            unset($_SESSION['autenticado'], $_SESSION['usuario'], $_SESSION['nombre']);
            $this->redirect('login');
        }

        $folderValue = $_GET['folder'] ?? null;
        $folderId = $folderValue === null || $folderValue === '' ? null : filter_var($folderValue, FILTER_VALIDATE_INT);
        if ($folderValue !== null && $folderValue !== '' && ($folderId === false || $folderId < 1)) {
            $this->showBadRequest('La carpeta solicitada no es válida.');
            return;
        }

        try {
            $files = $manager->listElements(
                (string) $_SESSION['usuario'],
                $folderId === null ? null : (int) $folderId
            );
        } catch (InvalidArgumentException $exception) {
            $this->showBadRequest($exception->getMessage());
            return;
        }
        $this->render('files/index', [
            'files' => $files,
            'folderId' => $folderId === null ? null : (int) $folderId,
            'canManageDirectory' => $manager->canManageDirectory(
                (string) $_SESSION['usuario'],
                $folderId === null ? null : (int) $folderId
            ),
            'title' => 'Mis archivos',
        ]);
    }

    public function upload(): void
    {
        $this->requireAuthentication();
        $requestTooLarge = $_SERVER['REQUEST_METHOD'] === 'POST' && $this->requestExceedsPostLimit();
        try {
            $folderId = $_SERVER['REQUEST_METHOD'] === 'POST' && !$requestTooLarge
                ? $this->postedFolderId()
                : $this->queryFolderId();
        } catch (InvalidArgumentException $exception) {
            $this->showBadRequest($exception->getMessage());
            return;
        }
        if (!$this->manager()->canManageDirectory((string) $_SESSION['usuario'], $folderId)) {
            $this->showBadRequest('Solo el propietario puede cargar archivos en esta carpeta compartida.');
            return;
        }
        $error = null;
        if ($requestTooLarge) {
            http_response_code(413);
            $error = 'El archivo supera el tamaño máximo de 20 MB. Elige un archivo más pequeño.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$requestTooLarge) {
            if (!$this->verifyCsrf()) {
                $this->showBadRequest('La sesión del formulario expiró. Inténtalo de nuevo.');
                return;
            }

            $upload = $_FILES['txtArchi'] ?? null;
            if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $error = $this->uploadError($upload);
            } else {
                try {
                    $this->manager()->upload(
                        (string) $_SESSION['usuario'],
                        (string) $upload['tmp_name'],
                        (string) $upload['name'],
                        (string) ($_POST['comentario'] ?? ''),
                        $folderId
                    );
                    $this->redirect($this->filesUrl($folderId));
                } catch (InvalidArgumentException | RuntimeException $exception) {
                    $error = $exception->getMessage();
                }
            }
        }

        $this->render('files/upload', [
            'error' => $error,
            'folderId' => $folderId,
            'title' => 'Agregar archivo',
        ]);
    }

    private function uploadError(mixed $upload): string
    {
        if (!is_array($upload)) {
            return 'Selecciona un archivo para cargar.';
        }

        return match ($upload['error'] ?? UPLOAD_ERR_NO_FILE) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido de 20 MB.',
            UPLOAD_ERR_PARTIAL => 'El archivo se recibió parcialmente. Inténtalo de nuevo.',
            UPLOAD_ERR_NO_FILE => 'Selecciona un archivo para cargar.',
            UPLOAD_ERR_NO_TMP_DIR => 'El servidor no tiene configurada una carpeta temporal para archivos.',
            UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo guardar el archivo temporal.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la carga del archivo.',
            default => 'No se pudo recibir el archivo. Inténtalo de nuevo.',
        };
    }

    private function requestExceedsPostLimit(): bool
    {
        $contentLength = filter_var($_SERVER['CONTENT_LENGTH'] ?? null, FILTER_VALIDATE_INT);
        $postMaxSize = trim((string) ini_get('post_max_size'));
        if ($contentLength === false || $contentLength === null || $postMaxSize === '' || $postMaxSize === '0') {
            return false;
        }

        if (!preg_match('/^(\d+)\s*([KMG])?$/i', $postMaxSize, $matches)) {
            throw new RuntimeException('No se pudo interpretar post_max_size de PHP.');
        }

        $multiplier = match (strtoupper($matches[2] ?? '')) {
            'G' => 1024 ** 3,
            'M' => 1024 ** 2,
            'K' => 1024,
            default => 1,
        };

        return $contentLength > (int) $matches[1] * $multiplier;
    }

    public function download(): void
    {
        $this->requireAuthentication();
        $elementId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($elementId === false || $elementId === null || $elementId < 1) {
            $this->showBadRequest('El archivo solicitado no es válido.');
            return;
        }

        try {
            $file = $this->manager()->findFile((string) $_SESSION['usuario'], (int) $elementId);
        } catch (InvalidArgumentException $exception) {
            $this->showBadRequest($exception->getMessage());
            return;
        }

        if ($file === null) {
            http_response_code(404);
            $this->render('errors/not-found', ['message' => 'El archivo solicitado no existe.']);
            return;
        }

        $storedMime = (string) ($file['mime_type'] ?? '');
        $mime = preg_match('/^[A-Za-z0-9.+-]+\/[A-Za-z0-9.+-]+$/', $storedMime)
            ? $storedMime
            : 'application/octet-stream';
        $showInline = $mime === 'application/pdf' || str_starts_with($mime, 'image/');

        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . $file['file_size']);
        header(
            'Content-Disposition: ' . ($showInline ? 'inline' : 'attachment')
            . '; filename*=UTF-8\'\'' . rawurlencode($file['file_name'])
        );
        if ($mime === 'image/svg+xml') {
            header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
        }
        echo $file['file_data'];
    }

    public function delete(): void
    {
        $this->requireAuthentication();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->showBadRequest('La eliminación de archivos requiere una solicitud POST.');
            return;
        }
        if (!$this->verifyCsrf()) {
            $this->showBadRequest('La sesión del formulario expiró. Inténtalo de nuevo.');
            return;
        }

        try {
            $elementId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($elementId === false || $elementId === null || $elementId < 1) {
                $this->showBadRequest('El elemento que intentas borrar no es válido.');
                return;
            }
            $deleted = $this->manager()->delete((string) $_SESSION['usuario'], (int) $elementId);
            if (!$deleted) {
                http_response_code(404);
                $this->render('errors/not-found', ['message' => 'El archivo solicitado no existe.']);
                return;
            }
            $this->redirect($this->filesUrl($this->postedFolderId()));
        } catch (InvalidArgumentException $exception) {
            $this->showBadRequest($exception->getMessage());
        } catch (RuntimeException $exception) {
            http_response_code(404);
            $this->render('errors/not-found', ['message' => $exception->getMessage()]);
        }
    }

    public function createFolder(): void
    {
        $this->requireAuthentication();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->showBadRequest('La solicitud para crear la carpeta no es válida.');
            return;
        }

        try {
            $parentId = $this->postedFolderId();
            $this->manager()->createDirectory(
                (string) $_SESSION['usuario'],
                (string) ($_POST['folder_name'] ?? ''),
                $parentId
            );
            $this->redirect($this->filesUrl($parentId));
        } catch (InvalidArgumentException $exception) {
            $this->showBadRequest($exception->getMessage());
        }
    }

    public function share(): void
    {
        $this->requireAuthentication();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->showBadRequest('La solicitud para compartir no es válida.');
            return;
        }

        $elementId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($elementId === false || $elementId === null || $elementId < 1) {
            $this->showBadRequest('El archivo que intentas compartir no es válido.');
            return;
        }

        try {
            $this->manager()->share(
                (string) $_SESSION['usuario'],
                (int) $elementId,
                trim((string) ($_POST['recipient'] ?? ''))
            );
            $this->redirect($this->filesUrl($this->postedFolderId()));
        } catch (InvalidArgumentException $exception) {
            $this->showBadRequest($exception->getMessage());
        }
    }

    private function queryFolderId(): ?int
    {
        $value = $_GET['folder'] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        $folderId = filter_var($value, FILTER_VALIDATE_INT);
        if ($folderId === false || $folderId < 1) {
            throw new InvalidArgumentException('La carpeta solicitada no es válida.');
        }

        return (int) $folderId;
    }

    private function postedFolderId(): ?int
    {
        $value = $_POST['folder_id'] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        $folderId = filter_var($value, FILTER_VALIDATE_INT);
        if ($folderId === false || $folderId < 1) {
            throw new InvalidArgumentException('La carpeta seleccionada no es válida.');
        }

        return (int) $folderId;
    }

    private function filesUrl(?int $folderId): string
    {
        return $folderId === null ? 'files' : 'files?folder=' . $folderId;
    }
}
