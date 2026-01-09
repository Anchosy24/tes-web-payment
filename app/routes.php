<?php

use App\Controllers\AuthController;
use App\Controllers\AdminController;
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
$app->group('/admin', function () use ($app) {

    // Dashboard
    $app->get('', AdminController::class . ':index')
        ->setName('admin.dashboard');

    // Products
    $app->get('/products', AdminController::class . ':products')
        ->setName('admin.products');
    
    $app->get('/products/create', AdminController::class . ':createProductForm')
        ->setName('admin.products.create');
    
    $app->post('/products', AdminController::class . ':storeProduct')
        ->setName('admin.products.store');
    
    $app->get('/products/{id}/edit', AdminController::class . ':editProductForm')
        ->setName('admin.products.edit');
    
    $app->post('/products/{id}', AdminController::class . ':updateProduct')
        ->setName('admin.products.update');
    
    $app->post('/products/{id}/delete', AdminController::class . ':deleteProduct')
        ->setName('admin.products.delete');

    // Orders
    $app->get('/orders', AdminController::class . ':orders')
        ->setName('admin.orders');
    
    $app->get('/orders/{id}', AdminController::class . ':orderDetail')
        ->setName('admin.order.detail');

    // Payment History
    $app->get('/payment', AdminController::class . ':payments')
        ->setName('admin.payment');

    // Users Management
    $app->get('/users', AdminController::class . ':users')
        ->setName('admin.users');

})->add(new AdminMiddleware());

/*
|--------------------------------------------------------------------------
| CUSTOMER / USER ROUTES
|--------------------------------------------------------------------------
*/
$app->group('/customer', function () use ($app) {

    // Home
    $app->get('', function ($req, $res) {
        return $this->view->render($res, 'customers/index.twig');
    })->setName('customer.home');

    // Orders
    $app->get('/orders', function ($req, $res) {
        return $this->view->render($res, 'customers/myorders.twig');
    })->setName('customer.orders');

    // Payment
    $app->get('/payment', function ($req, $res) {
        return $this->view->render($res, 'customers/mypayment.twig');
    })->setName('customer.payment');

})->add(new UserMiddleware());