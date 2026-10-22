<?php
class Core
{
    private const ROUTES = [
        '' => ['AuthController', 'login'],
        'login' => ['AuthController', 'login'],
        'register' => ['AuthController', 'register'],
        'files' => ['FileController', 'index'],
        'carpetas' => ['FileController', 'index'],
        'carpetas.php' => ['FileController', 'index'],
        'files/upload' => ['FileController', 'upload'],
        'files/download' => ['FileController', 'download'],
        'files/delete' => ['FileController', 'delete'],
        'files/folder' => ['FileController', 'createFolder'],
        'files/share' => ['FileController', 'share'],
        'logout' => ['AuthController', 'logout'],
    ];

    public function dispatch(): void
    {
        $path = trim((string) ($_GET['page'] ?? ''), '/');

        if (!isset(self::ROUTES[$path])) {
            http_response_code(404);
            (new PageController())->notFound();
            return;
        }

        [$controllerName, $method] = self::ROUTES[$path];
        $controller = new $controllerName();
        $controller->$method();
    }
}
