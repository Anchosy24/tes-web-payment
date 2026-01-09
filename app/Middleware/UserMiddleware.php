<?php

namespace App\Middleware;

class UserMiddleware
{
    public function __invoke($request, $response, $next)
    {
        // Pastikan session sudah dimulai
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            !isset($_SESSION['user']) ||
            $_SESSION['user']['role'] !== 'user'
        ) {
            return $response->withRedirect('/');
        }

        return $next($request, $response);
    }
}