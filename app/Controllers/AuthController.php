<?php

namespace App\Controllers;

use App\Models\User;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm($request, $response)
    {
        // Jika sudah login, redirect berdasarkan role
        if ($this->isAuth()) {
            if ($this->isAdmin()) {
                return $response->withRedirect(
                    $this->router->pathFor('admin.dashboard')
                );
            }
            return $response->withRedirect(
                $this->router->pathFor('customer.home')
            );
        }

        return $this->render($response, 'auths/login.twig');
    }

    /**
     * Process login
     */
    public function login($request, $response)
    {
        $data = $request->getParsedBody();
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            $_SESSION['error'] = 'Email dan password wajib diisi!';
            return $this->redirect($response, 'login');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['error'] = 'Email atau password salah!';
            return $this->redirect($response, 'login');
        }

        if (!$user['is_active']) {
            $_SESSION['error'] = 'Akun tidak aktif!';
            return $this->redirect($response, 'login');
        }

        // Set session
        $_SESSION['user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'role'     => $user['role'],
        ];

        $_SESSION['success'] = 'Login berhasil! Selamat datang, ' . $user['username'];

        if ($user['role'] === 'admin') {
            return $this->redirect($response, 'admin.dashboard');
        }

        return $this->redirect($response, 'customer.home');
    }

    /**
     * Show register form
     */
    public function showRegisterForm($request, $response)
    {
        // Jika sudah login, redirect berdasarkan role
        if ($this->isAuth()) {
            if ($this->isAdmin()) {
                return $response->withRedirect(
                    $this->router->pathFor('admin.dashboard')
                );
            }
            return $response->withRedirect(
                $this->router->pathFor('customer.home')
            );
        }

        return $this->render($response, 'auths/register.twig');
    }

    /**
     * Process registration
     */
    public function register($request, $response)
    {
        $data = $request->getParsedBody();
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirmation'] ?? '';

        $errors = [];

        if (empty($username)) {
            $errors[] = 'Username wajib diisi!';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username minimal 3 karakter!';
        }

        if (empty($email)) {
            $errors[] = 'Email wajib diisi!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid!';
        }

        if (empty($password)) {
            $errors[] = 'Password wajib diisi!';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter!';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Password dan konfirmasi password tidak cocok!';
        }

        if ($errors) {
            $_SESSION['error'] = implode('<br>', $errors);
            return $this->redirect($response, 'register');
        }

        try {
            $userModel = new User();

            if ($userModel->findByUsername($username)) {
                $_SESSION['error'] = 'Username sudah digunakan!';
                return $this->redirect($response, 'register');
            }

            if ($userModel->findByEmail($email)) {
                $_SESSION['error'] = 'Email sudah terdaftar!';
                return $this->redirect($response, 'register');
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $userId = $userModel->create([
                'username'  => $username,
                'email'     => $email,
                'password'  => $hashedPassword,
                'role'      => 'user',
                'is_active' => 1
            ]);

            $_SESSION['user'] = [
                'id'       => $userId,
                'username' => $username,
                'email'    => $email,
                'role'     => 'user'
            ];

            $_SESSION['success'] = 'Registrasi berhasil! Selamat datang, ' . $username;

            // Redirect ke halaman customer
            return $this->redirect($response, 'customer.home');

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Terjadi kesalahan saat registrasi: ' . $e->getMessage();
            return $this->redirect($response, 'register');
        }
    }

    /**
     * Logout
     */
    public function logout($request, $response){
        $_SESSION['success'] = 'Logout berhasil!';
        
        // Hapus semua session kecuali success message
        $successMsg = $_SESSION['success'];
        $_SESSION = [];
        $_SESSION['success'] = $successMsg;

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        // Redirect ke LOGIN
        return $this->redirect($response, 'login');
    }

}