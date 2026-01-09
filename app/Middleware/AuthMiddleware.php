<?php

namespace App\Middleware;

class AuthMiddleware
{
    protected $router;

    public function __construct($router)
    {
        $this->router = $router;
    }

    public function __invoke($request, $response, $next)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('login')
            );
        }

        return $next($request, $response);
    }
}
