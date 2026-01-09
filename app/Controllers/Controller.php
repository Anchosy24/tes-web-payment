<?php

namespace App\Controllers;

class Controller
{
    protected $container;
    protected $router;

    public function __construct($container)
    {
        $this->container = $container;
        $this->router = $container->router;
    }

    protected function render($response, $view, $data = [])
    {
        // Merge alert data into view data
        $alertData = $this->getAlertData();
        $data = array_merge($data, $alertData);
        return $this->container->view->render($response, $view, $data);
    }

    protected function redirect($response, $routeName, $params = [])
    {
        return $response->withRedirect(
            $this->router->pathFor($routeName, $params)
        );
    }

    protected function getAlertData()
    {
        $alertData = [];
        
        if (isset($_SESSION['success'])) {
            $alertData['alert'] = [
                'type' => 'success',
                'message' => $_SESSION['success']
            ];
            unset($_SESSION['success']);
        } elseif (isset($_SESSION['error'])) {
            $alertData['alert'] = [
                'type' => 'error',
                'message' => $_SESSION['error']
            ];
            unset($_SESSION['error']);
        }
        
        return $alertData;
    }

    protected function isAuth()
    {
        return isset($_SESSION['user']);
    }

    protected function isAdmin()
    {
        return $this->isAuth() && $_SESSION['user']['role'] === 'admin';
    }
}
