<?php
class AuthController extends Controller
{
    public function login(): void
    {
        if (($_SESSION['autenticado'] ?? '') === 'SI') {
            $this->redirect('files');
        }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf()) {
                $this->showBadRequest('La sesión del formulario expiró. Inténtalo de nuevo.');
                return;
            }

            $username = trim((string) ($_POST['txtUsua'] ?? ''));
            $password = (string) ($_POST['txtContra'] ?? '');
            $user = (new User())->authenticate($username, $password);

            if ($user === null) {
                $error = 'Usuario o contraseña incorrectos.';
            } else {
                session_regenerate_id(true);
                $_SESSION['autenticado'] = 'SI';
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['usuario'] = $user['usuario'];
                $this->redirect('files');
            }
        }

        $this->render('auth/login', ['error' => $error, 'title' => 'Iniciar sesión | MyBOX']);
    }

    public function register(): void
    {
        if (($_SESSION['autenticado'] ?? '') === 'SI') {
            $this->redirect('files');
        }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf()) {
                $this->showBadRequest('La sesión del formulario expiró. Inténtalo de nuevo.');
                return;
            }

            $username = trim((string) ($_POST['txtUsua'] ?? ''));
            $password = (string) ($_POST['txtContra'] ?? '');
            $name = trim((string) ($_POST['txtNomb'] ?? ''));
            $email = trim((string) ($_POST['txtEmail'] ?? ''));

            if (!preg_match('/^[A-Za-z0-9_.-]{1,15}$/', $username)) {
                $error = 'El usuario debe tener hasta 15 caracteres: letras, números, punto, guion o guion bajo.';
            } elseif ($password === '' || strlen($password) > 72) {
                $error = 'La contraseña es obligatoria y no puede superar 72 caracteres.';
            } elseif ($name === '' || strlen($name) > 30) {
                $error = 'El nombre es obligatorio y no puede superar 30 caracteres.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 50) {
                $error = 'Ingresa un correo electrónico válido de hasta 50 caracteres.';
            } else {
                try {
                    (new User())->create($username, $password, $name, $email);
                    session_regenerate_id(true);
                    $_SESSION['autenticado'] = 'SI';
                    $_SESSION['nombre'] = $name;
                    $_SESSION['usuario'] = $username;
                    $this->redirect('files');
                } catch (PDOException $exception) {
                    if ($exception->getCode() !== '23000') {
                        throw $exception;
                    }
                    $error = 'Ese nombre de usuario ya está registrado.';
                }
            }
        }

        $this->render('auth/register', ['error' => $error, 'title' => 'Crear cuenta | MyBOX']);
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        $this->redirect('login');
    }
}
