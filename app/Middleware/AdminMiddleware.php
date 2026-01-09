<?php

namespace App\Middleware;

class AdminMiddleware
{
    private $container;

    public function __construct($container = null)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $next)
    {
        // Pastikan session sudah dimulai
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            !isset($_SESSION['user']) ||
            $_SESSION['user']['role'] !== 'admin'
        ) {
            return $response->withRedirect('/');
        }

        return $next($request, $response);
    }
}