<?php
class Controller
{
    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require dirname(__DIR__) . '/views/layouts/header.php';
        require dirname(__DIR__) . '/views/' . $view . '.php';
        require dirname(__DIR__) . '/views/layouts/footer.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit();
    }

    protected function requireAuthentication(): void
    {
        if (($_SESSION['autenticado'] ?? '') !== 'SI') {
            $this->redirect('login');
        }
    }

    protected function verifyCsrf(): bool
    {
        return isset($_SESSION['csrf_token'], $_POST['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token']);
    }

    protected function showBadRequest(string $message = 'La solicitud no es válida.'): void
    {
        http_response_code(400);
        $this->render('errors/bad-request', ['message' => $message]);
    }
}
