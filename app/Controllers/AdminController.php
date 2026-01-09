<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Payment;

class AdminController extends Controller
{
    /**
     * Dashboard
     */
    public function index($request, $response)
    {
        $userModel = new User();
        $productModel = new Product();
        $orderModel = new Order();
        $paymentModel = new Payment();
        
        $stats = [
            'total_users' => $userModel->count(),
            'total_products' => $productModel->count(),
            'total_orders' => $orderModel->count(),
            'pending_orders' => $orderModel->countByStatus('pending'),
            'success_orders' => $orderModel->countByStatus('success'),
            'total_revenue' => $orderModel->getTotalPaid(),
        ];
        
        // Recent orders - Lebih clean pakai method dari model
        $recentOrders = $orderModel->getRecent(5);
        
        return $this->render($response, 'admins/index.twig', [
            'stats' => $stats,
            'recent_orders' => $recentOrders
        ]);
    }
    
    /**
     * Orders List
     */
    public function orders($request, $response)
    {
        $orderModel = new Order();
        $page = $request->getQueryParam('page', 1);
        $status = $request->getQueryParam('status', '');
        
        $filters = [];
        if ($status) {
            $filters['status'] = $status;
        }
        
        $result = $orderModel->getWithUser($page, 20, $filters);
        
        return $this->render($response, 'admins/orders.twig', [
            'orders' => $result['data'],
            'pagination' => $result,
            'current_status' => $status
        ]);
    }
    
    /**
     * Order Detail
     */
    public function orderDetail($request, $response, $args)
    {
        $orderId = $args['id'];
        $orderModel = new Order();
        
        $order = $orderModel->getWithItems($orderId);
        
        if (!$order) {
            $_SESSION['error'] = 'Order tidak ditemukan!';
            return $this->redirect($response, 'admin.orders');
        }
        
        // Get user info
        $userModel = new User();
        $user = $userModel->find($order['user_id']);
        
        // Get payment info
        $paymentModel = new Payment();
        $payment = $paymentModel->findByOrderId($orderId);
        
        return $this->render($response, 'admins/order-detail.twig', [
            'order' => $order,
            'user' => $user,
            'payment' => $payment
        ]);
    }
    
    /**
     * Payments List
     */
    public function payments($request, $response)
    {
        $paymentModel = new Payment();
        $page = $request->getQueryParam('page', 1);
        $status = $request->getQueryParam('status', '');
        
        $filters = [];
        if ($status) {
            $filters['status'] = $status;
        }
        
        $result = $paymentModel->getWithOrder($page, 20, $filters);
        
        return $this->render($response, 'admins/payment.twig', [
            'payments' => $result['data'],
            'pagination' => $result,
            'current_status' => $status
        ]);
    }
    
    /**
     * Users List
     */
    public function users($request, $response)
    {
        $userModel = new User();
        $page = $request->getQueryParam('page', 1);
        $role = $request->getQueryParam('role', '');
        
        $where = [];
        if ($role) {
            $where['role'] = $role;
        }
        
        $result = $userModel->paginate($page, 20, '*', $where);
        
        return $this->render($response, 'admins/users.twig', [
            'users' => $result['data'],
            'pagination' => $result,
            'current_role' => $role
        ]);
    }
    
    /**
     * Products List
     */
    public function products($request, $response)
    {
        $productModel = new Product();
        $page = $request->getQueryParam('page', 1);
        $search = $request->getQueryParam('search', '');
        
        $where = [];
        
        if ($search) {
            $where['OR'] = [
                'product_name[~]' => $search,
                'description[~]' => $search
            ];
        }
        
        $result = $productModel->paginate($page, 12, '*', $where);
        
        return $this->render($response, 'admins/product.twig', [
            'products' => $result['data'],
            'pagination' => $result,
            'search' => $search
        ]);
    }
    
    /**
     * Show Create Product Form
     */
    public function createProductForm($request, $response)
    {
        return $this->render($response, 'admins/product-form.twig', [
            'mode' => 'create'
        ]);
    }
    
    /**
     * Store Product
     */
    public function storeProduct($request, $response)
    {
        $data = $request->getParsedBody();
        
        $productName = trim($data['product_name'] ?? '');
        $description = trim($data['description'] ?? '');
        $price = (int)($data['price'] ?? 0);
        $stock = (int)($data['stock'] ?? 0);
        $imageUrl = trim($data['image_url'] ?? '');
        
        // Validation
        if (empty($productName)) {
            $_SESSION['error'] = 'Nama produk wajib diisi!';
            return $this->redirect($response, 'admin.products.create');
        }
        
        if ($price <= 0) {
            $_SESSION['error'] = 'Harga harus lebih dari 0!';
            return $this->redirect($response, 'admin.products.create');
        }
        
        if ($stock < 0) {
            $_SESSION['error'] = 'Stock tidak boleh negatif!';
            return $this->redirect($response, 'admin.products.create');
        }
        
        try {
            $productModel = new Product();
            
            $productId = $productModel->create([
                'product_name' => $productName,
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'image_url' => $imageUrl ?: null
            ]);
            
            $_SESSION['success'] = 'Produk berhasil ditambahkan!';
            return $this->redirect($response, 'admin.products');
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal menambahkan produk: ' . $e->getMessage();
            return $this->redirect($response, 'admin.products.create');
        }
    }
    
    /**
     * Show Edit Product Form
     */
    public function editProductForm($request, $response, $args)
    {
        $productId = $args['id'];
        $productModel = new Product();
        
        $product = $productModel->find($productId);
        
        if (!$product) {
            $_SESSION['error'] = 'Produk tidak ditemukan!';
            return $this->redirect($response, 'admin.products');
        }
        
        return $this->render($response, 'admins/product-form.twig', [
            'mode' => 'edit',
            'product' => $product
        ]);
    }
    
    /**
     * Update Product
     */
    public function updateProduct($request, $response, $args)
    {
        $productId = $args['id'];
        $data = $request->getParsedBody();
        
        $productName = trim($data['product_name'] ?? '');
        $description = trim($data['description'] ?? '');
        $price = (int)($data['price'] ?? 0);
        $stock = (int)($data['stock'] ?? 0);
        $imageUrl = trim($data['image_url'] ?? '');
        
        // Validation
        if (empty($productName)) {
            $_SESSION['error'] = 'Nama produk wajib diisi!';
            return $this->redirect($response, 'admin.products.edit', ['id' => $productId]);
        }
        
        if ($price <= 0) {
            $_SESSION['error'] = 'Harga harus lebih dari 0!';
            return $this->redirect($response, 'admin.products.edit', ['id' => $productId]);
        }
        
        if ($stock < 0) {
            $_SESSION['error'] = 'Stock tidak boleh negatif!';
            return $this->redirect($response, 'admin.products.edit', ['id' => $productId]);
        }
        
        try {
            $productModel = new Product();
            
            $productModel->update($productId, [
                'product_name' => $productName,
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'image_url' => $imageUrl ?: null
            ]);
            
            $_SESSION['success'] = 'Produk berhasil diupdate!';
            return $this->redirect($response, 'admin.products');
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal mengupdate produk: ' . $e->getMessage();
            return $this->redirect($response, 'admin.products.edit', ['id' => $productId]);
        }
    }
    
    /**
     * Delete Product
     */
    public function deleteProduct($request, $response, $args)
    {
        $productId = $args['id'];
        
        try {
            $productModel = new Product();
            
            $product = $productModel->find($productId);
            
            if (!$product) {
                $_SESSION['error'] = 'Produk tidak ditemukan!';
                return $this->redirect($response, 'admin.products');
            }
            
            $productModel->delete($productId);
            
            $_SESSION['success'] = 'Produk berhasil dihapus!';
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal menghapus produk: ' . $e->getMessage();
        }
        
        return $this->redirect($response, 'admin.products');
    }
}