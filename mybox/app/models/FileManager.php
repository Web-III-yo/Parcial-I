<?php
class FileManager
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::connection();
    }

    public function userExists(string $username): bool
    {
        $this->validateUsername($username);
        return $this->findUserId($username) !== null;
    }

    public function listElements(string $username, ?int $parentId = null): array
    {
        $this->validateUsername($username);
        $userId = $this->findUserId($username);
        if ($userId === null) {
            throw new RuntimeException('No se encontró el usuario asociado a la sesión.');
        }

        if ($parentId === null) {
            $ownedItems = $this->fetchElements(
                'e.usuario_id = :owner_id AND e.parent_id IS NULL',
                ['owner_id' => $userId],
                false
            );
            $sharedItems = $this->fetchElements(
                'e.id IN (
                    SELECT c.elemento_id
                    FROM compartidos c
                    WHERE c.compartido_con = :recipient_id
                ) AND e.usuario_id <> :recipient_owner_id',
                ['recipient_id' => $userId, 'recipient_owner_id' => $userId],
                true
            );

            return $this->sortElements(array_merge($ownedItems, $sharedItems));
        }

        $directory = $this->findAccessibleDirectory($userId, $parentId);
        if ($directory === null) {
            throw new InvalidArgumentException('La carpeta no existe o no tienes permiso para abrirla.');
        }

        $items = $this->fetchElements(
            'e.usuario_id = :owner_id AND e.parent_id = :parent_id',
            ['owner_id' => (int) $directory['usuario_id'], 'parent_id' => $parentId],
            (bool) $directory['shared_with_me']
        );

        return $this->sortElements($items);
    }

    public function canManageDirectory(string $username, ?int $directoryId): bool
    {
        if ($directoryId === null) {
            return true;
        }

        $userId = $this->findUserId($username);
        if ($userId === null) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT id FROM elementos
             WHERE id = :id AND usuario_id = :usuario_id AND es_directorio = 1'
        );
        $statement->execute(['id' => $directoryId, 'usuario_id' => $userId]);

        return $statement->fetchColumn() !== false;
    }

    public function upload(
        string $username,
        string $temporaryPath,
        string $fileName,
        string $comment = '',
        ?int $parentId = null
    ): void
    {
        $this->validateUsername($username);
        $fileName = str_replace(' ', '_', basename(str_replace('\\', '/', $fileName)));
        $this->validateFileName($fileName);
        $comment = trim($comment);
        if (mb_strlen($comment, 'UTF-8') > 50) {
            throw new InvalidArgumentException('El comentario no puede superar los 50 caracteres.');
        }

        $fileSize = filesize($temporaryPath);
        if ($fileSize === false) {
            throw new RuntimeException('No se pudo leer el archivo temporal.');
        }
        if ($fileSize > APP_CONFIG['max_upload_bytes']) {
            throw new InvalidArgumentException('El archivo supera el límite permitido de 20 MB.');
        }

        $contents = file_get_contents($temporaryPath);
        if ($contents === false) {
            throw new RuntimeException('No se pudo leer el contenido del archivo.');
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($temporaryPath);
        if ($mimeType === false) {
            throw new RuntimeException('No se pudo determinar el tipo de archivo.');
        }

        $this->connection->beginTransaction();
        try {
            $userId = $this->findUserId($username);
            if ($userId === null) {
                throw new RuntimeException('No se encontró el usuario asociado a la sesión.');
            }
            if ($parentId !== null) {
                $this->requireOwnedDirectory($userId, $parentId);
            }

            $existingFile = $this->connection->prepare(
                'SELECT id
                 FROM elementos
                 WHERE usuario_id = :usuario_id
                   AND parent_id <=> :parent_id
                   AND es_directorio = 0
                   AND nombre = :nombre
                 ORDER BY id DESC
                 LIMIT 1'
            );
            $existingFile->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            if ($parentId === null) {
                $existingFile->bindValue(':parent_id', null, PDO::PARAM_NULL);
            } else {
                $existingFile->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
            }
            $existingFile->bindValue(':nombre', $fileName, PDO::PARAM_STR);
            $existingFile->execute();
            $elementId = $existingFile->fetchColumn();
            $sizeInMegabytes = $fileSize / (1024 * 1024);

            if ($elementId !== false) {
                $statement = $this->connection->prepare(
                    'UPDATE elementos
                     SET comentario = :comentario,
                         tipo_documento = :tipo_documento,
                         peso_mb = :peso_mb,
                         fecha_almacenado = CURRENT_TIMESTAMP,
                         archivo_blob = :archivo_blob,
                         mime_type = :mime_type
                     WHERE id = :id AND usuario_id = :usuario_id'
                );
                $statement->bindValue(':id', (int) $elementId, PDO::PARAM_INT);
                $statement->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            } else {
                $statement = $this->connection->prepare(
                    'INSERT INTO elementos
                        (usuario_id, parent_id, es_directorio, nombre, comentario, tipo_documento,
                         peso_mb, fecha_almacenado, archivo_blob, mime_type)
                     VALUES
                        (:usuario_id, :parent_id, 0, :nombre, :comentario, :tipo_documento,
                         :peso_mb, CURRENT_TIMESTAMP, :archivo_blob, :mime_type)'
                );
                $statement->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
                if ($parentId === null) {
                    $statement->bindValue(':parent_id', null, PDO::PARAM_NULL);
                } else {
                    $statement->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
                }
                $statement->bindValue(':nombre', $fileName, PDO::PARAM_STR);
            }

            $statement->bindValue(':comentario', $comment, PDO::PARAM_STR);
            $statement->bindValue(':tipo_documento', substr(document_type_label($mimeType, $fileName), 0, 50), PDO::PARAM_STR);
            $statement->bindValue(':peso_mb', $sizeInMegabytes);
            $statement->bindValue(':archivo_blob', $contents, PDO::PARAM_LOB);
            $statement->bindValue(':mime_type', substr($mimeType, 0, 100), PDO::PARAM_STR);
            $statement->execute();
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $exception;
        }
    }

    public function findFile(string $username, int $elementId): ?array
    {
        $this->validateUsername($username);
        if ($elementId < 1) {
            throw new InvalidArgumentException('El identificador del archivo no es válido.');
        }

        $userId = $this->findUserId($username);
        if ($userId === null) {
            return null;
        }

        $access = $this->connection->prepare(
            'SELECT usuario_id FROM elementos WHERE id = :element_id AND es_directorio = 0'
        );
        $access->execute(['element_id' => $elementId]);
        $ownerId = $access->fetchColumn();
        if ($ownerId === false
            || ((int) $ownerId !== $userId && !$this->hasSharedAccess($elementId, $userId))
        ) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT nombre AS file_name, mime_type,
                    OCTET_LENGTH(archivo_blob) AS file_size, archivo_blob AS file_data
             FROM elementos WHERE id = :element_id AND es_directorio = 0'
        );
        $statement->bindValue(':element_id', $elementId, PDO::PARAM_INT);
        $statement->execute();
        $file = $statement->fetch();

        if ($file === false) {
            return null;
        }

        if (is_resource($file['file_data'])) {
            $contents = stream_get_contents($file['file_data']);
            if ($contents === false) {
                throw new RuntimeException('No se pudo recuperar el contenido del archivo.');
            }
            $file['file_data'] = $contents;
        }

        return $file;
    }

    public function delete(string $username, int $elementId): bool
    {
        $this->validateUsername($username);
        if ($elementId < 1) {
            throw new InvalidArgumentException('El identificador del elemento no es válido.');
        }

        $statement = $this->connection->prepare(
            'DELETE e
             FROM elementos e
             INNER JOIN usuarios u ON u.id = e.usuario_id
             WHERE u.usuario = :username
               AND e.id = :element_id'
        );
        $statement->bindValue(':username', $username, PDO::PARAM_STR);
        $statement->bindValue(':element_id', $elementId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() > 0;
    }

    public function createDirectory(string $username, string $name, ?int $parentId = null): void
    {
        $this->validateUsername($username);
        $name = trim($name);
        $this->validateFileName($name);
        if (mb_strlen($name, 'UTF-8') > 255) {
            throw new InvalidArgumentException('El nombre de la carpeta no puede superar los 255 caracteres.');
        }

        $userId = $this->findUserId($username);
        if ($userId === null) {
            throw new RuntimeException('No se encontró el usuario asociado a la sesión.');
        }
        if ($parentId !== null) {
            $this->requireOwnedDirectory($userId, $parentId);
        }

        $statement = $this->connection->prepare(
            'INSERT INTO elementos (usuario_id, parent_id, es_directorio, nombre, fecha_almacenado)
             VALUES (:usuario_id, :parent_id, 1, :nombre, CURRENT_TIMESTAMP)'
        );
        $statement->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        if ($parentId === null) {
            $statement->bindValue(':parent_id', null, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
        }
        $statement->bindValue(':nombre', $name, PDO::PARAM_STR);
        $statement->execute();
    }

    public function share(string $ownerUsername, int $elementId, string $recipientUsername): void
    {
        $this->validateUsername($ownerUsername);
        $this->validateUsername($recipientUsername);
        if ($elementId < 1 || $ownerUsername === $recipientUsername) {
            throw new InvalidArgumentException('Selecciona un archivo o carpeta y otro usuario para compartirlo.');
        }

        $ownerId = $this->findUserId($ownerUsername);
        $recipientId = $this->findUserId($recipientUsername);
        if ($ownerId === null || $recipientId === null) {
            throw new InvalidArgumentException('No se encontró el usuario destinatario.');
        }

        $element = $this->connection->prepare(
            'SELECT id FROM elementos WHERE id = :id AND usuario_id = :usuario_id'
        );
        $element->execute(['id' => $elementId, 'usuario_id' => $ownerId]);
        if ($element->fetchColumn() === false) {
            throw new InvalidArgumentException('Solo puedes compartir tus propios archivos o carpetas.');
        }

        $share = $this->connection->prepare(
            'INSERT INTO compartidos (elemento_id, compartido_por, compartido_con)
             SELECT :elemento_id, :owner_id, :recipient_id
             WHERE NOT EXISTS (
                 SELECT 1 FROM compartidos
                 WHERE elemento_id = :existing_element
                   AND compartido_con = :existing_recipient
             )'
        );
        $share->execute([
            'elemento_id' => $elementId,
            'owner_id' => $ownerId,
            'recipient_id' => $recipientId,
            'existing_element' => $elementId,
            'existing_recipient' => $recipientId,
        ]);
    }

    private function validateUsername(string $username): void
    {
        if (!preg_match('/^[A-Za-z0-9_.-]{1,50}$/', $username)) {
            throw new InvalidArgumentException('El nombre de usuario no es válido.');
        }
    }

    private function findUserId(string $username): ?int
    {
        $statement = $this->connection->prepare(
            'SELECT id FROM usuarios WHERE usuario = :username LIMIT 1'
        );
        $statement->bindValue(':username', $username, PDO::PARAM_STR);
        $statement->execute();
        $userId = $statement->fetchColumn();

        return $userId === false ? null : (int) $userId;
    }

    private function requireOwnedDirectory(int $userId, int $directoryId): void
    {
        $statement = $this->connection->prepare(
            'SELECT id
             FROM elementos
             WHERE id = :id AND usuario_id = :usuario_id AND es_directorio = 1'
        );
        $statement->execute(['id' => $directoryId, 'usuario_id' => $userId]);
        if ($statement->fetchColumn() === false) {
            throw new InvalidArgumentException('La carpeta seleccionada no existe o no te pertenece.');
        }
    }

    private function findAccessibleDirectory(int $userId, int $directoryId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, usuario_id FROM elementos WHERE id = :id AND es_directorio = 1'
        );
        $statement->execute(['id' => $directoryId]);
        $directory = $statement->fetch();
        if ($directory === false) {
            return null;
        }

        $sharedWithMe = (int) $directory['usuario_id'] !== $userId;
        if ($sharedWithMe && !$this->hasSharedAccess($directoryId, $userId)) {
            return null;
        }
        $directory['shared_with_me'] = $sharedWithMe;

        return $directory;
    }

    private function hasSharedAccess(int $elementId, int $recipientId): bool
    {
        $currentId = $elementId;
        $visited = [];

        while ($currentId > 0 && !isset($visited[$currentId])) {
            $visited[$currentId] = true;
            $statement = $this->connection->prepare(
                'SELECT e.parent_id,
                        EXISTS(
                            SELECT 1 FROM compartidos c
                            WHERE c.elemento_id = e.id AND c.compartido_con = :recipient_id
                        ) AS shared_with_recipient
                 FROM elementos e
                 WHERE e.id = :element_id'
            );
            $statement->execute([
                'recipient_id' => $recipientId,
                'element_id' => $currentId,
            ]);
            $element = $statement->fetch();
            if ($element === false) {
                return false;
            }
            if ((bool) $element['shared_with_recipient']) {
                return true;
            }
            $currentId = $element['parent_id'] === null ? 0 : (int) $element['parent_id'];
        }

        return false;
    }

    private function fetchElements(string $condition, array $parameters, bool $sharedWithMe): array
    {
        $statement = $this->connection->prepare(
            'SELECT e.id, e.nombre AS name, e.comentario, e.tipo_documento, e.mime_type,
                    COALESCE(OCTET_LENGTH(e.archivo_blob) / 1048576, e.peso_mb, 0) AS peso_mb,
                    UNIX_TIMESTAMP(e.fecha_almacenado) AS uploaded_at,
                    e.es_directorio, e.usuario_id, u.usuario AS owner_username,
                    EXISTS(
                        SELECT 1 FROM compartidos c WHERE c.elemento_id = e.id
                    ) AS directly_shared,
                    :shared_with_me AS shared_with_me
             FROM elementos e
             INNER JOIN usuarios u ON u.id = e.usuario_id
             WHERE ' . $condition
        );
        $statement->execute($parameters + ['shared_with_me' => $sharedWithMe ? 1 : 0]);
        $elements = $statement->fetchAll();

        foreach ($elements as &$element) {
            $element['is_shared'] = (bool) $element['directly_shared'] || $sharedWithMe;
            unset($element['directly_shared']);
        }
        unset($element);

        return $elements;
    }

    private function sortElements(array $elements): array
    {
        usort($elements, static function (array $left, array $right): int {
            $directoryOrder = (int) $right['es_directorio'] <=> (int) $left['es_directorio'];
            return $directoryOrder !== 0
                ? $directoryOrder
                : strcasecmp((string) $left['name'], (string) $right['name']);
        });

        return $elements;
    }

    private function validateFileName(string $fileName): void
    {
        if ($fileName === ''
            || strlen($fileName) > 255
            || preg_match('//u', $fileName) !== 1
            || basename(str_replace('\\', '/', $fileName)) !== $fileName
            || str_contains($fileName, "\0")
        ) {
            throw new InvalidArgumentException('El nombre del archivo no es válido.');
        }
    }
}
