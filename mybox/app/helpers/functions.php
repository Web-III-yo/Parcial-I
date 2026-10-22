<?php
function url(string $path = ''): string
{
    $baseUrl = APP_CONFIG['base_url'];
    return $baseUrl . ($path === '' ? '/' : '/' . ltrim($path, '/'));
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function document_type_label(?string $mimeType, string $fileName): string
{
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $officeTypes = [
        'doc' => 'Word',
        'docx' => 'Word',
        'xls' => 'Excel',
        'xlsx' => 'Excel',
        'ppt' => 'PowerPoint',
        'pptx' => 'PowerPoint',
    ];
    if (isset($officeTypes[$extension])) {
        return $officeTypes[$extension];
    }

    $typeByMime = [
        'application/pdf' => 'PDF',
        'application/msword' => 'Word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word',
        'application/vnd.ms-excel' => 'Excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel',
        'application/vnd.ms-powerpoint' => 'PowerPoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PowerPoint',
        'text/plain' => 'Texto',
        'text/csv' => 'CSV',
        'image/jpeg' => 'Imagen JPEG',
        'image/png' => 'Imagen PNG',
        'image/gif' => 'Imagen GIF',
        'image/webp' => 'Imagen WebP',
        'application/zip' => 'ZIP',
    ];

    if ($mimeType !== null && isset($typeByMime[$mimeType])) {
        return $typeByMime[$mimeType];
    }

    if ($mimeType !== null && str_starts_with($mimeType, 'image/')) {
        return 'Imagen';
    }

    if ($mimeType !== null && str_starts_with($mimeType, 'audio/')) {
        return 'Audio';
    }

    if ($mimeType !== null && str_starts_with($mimeType, 'video/')) {
        return 'Video';
    }

    return $extension !== '' ? strtoupper($extension) : 'Desconocido';
}
