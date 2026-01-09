<?php

use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\UserMiddleware;
use App\Models\BaseModel;

/*
|--------------------------------------------------------------------------
| PUBLIC / AUTH ROUTES
|--------------------------------------------------------------------------
*/

$app->get('/', AuthController::class . ':showLoginForm')
    ->setName('login');

$app->post('/login', AuthController::class . ':login')
    ->setName('login.post');

$app->get('/registrasi', AuthController::class . ':showRegisterForm')
    ->setName('register');

$app->post('/register', AuthController::class . ':register')
    ->setName('register.post');

$app->get('/logout', AuthController::class . ':logout')
    ->setName('logout');

/*
|--------------------------------------------------------------------------
| TEST DB
|--------------------------------------------------------------------------
*/
$app->get('/test-db', function ($req, $res) {
    BaseModel::db()->query('SELECT 1');
    return $res->write('DB Connected!');
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
$app->group('/admin', function () {

    // Dashboard
    $this->get('', function ($req, $res) {
        return $this->view->render($res, 'admins/index.twig');
    })->setName('admin.dashboard');

    // Orders
    $this->get('/orders', function ($req, $res) {
        return $this->view->render($res, 'admins/orders.twig');
    })->setName('admin.orders');

    // Payment History
    $this->get('/payment', function ($req, $res) {
        return $this->view->render($res, 'admins/payment.twig');
    })->setName('admin.payment');

    // Users Management
    $this->get('/users', function ($req, $res) {
        return $this->view->render($res, 'admins/users.twig');
    })->setName('admin.users');

})->add(new AdminMiddleware());

/*
|--------------------------------------------------------------------------
| CUSTOMER / USER ROUTES
|--------------------------------------------------------------------------
*/
$app->group('/customer', function () {

    // Home
    $this->get('', function ($req, $res) {
        return $this->view->render($res, 'customers/index.twig');
    })->setName('customer.home');

    // Orders
    $this->get('/orders', function ($req, $res) {
        return $this->view->render($res, 'customers/myorders.twig');
    })->setName('customer.orders');

    // Payment
    $this->get('/payment', function ($req, $res) {
        return $this->view->render($res, 'customers/mypayment.twig');
    })->setName('customer.payment');

})->add(new UserMiddleware());