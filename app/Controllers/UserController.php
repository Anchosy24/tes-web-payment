<?php

namespace App\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Payment;

class UserController extends Controller
{
    /**
     * Home - Product List
     */
    public function index($request, $response)
    {
        $productModel = new Product();
        $page = $request->getQueryParam('page', 1);
        $search = $request->getQueryParam('search', '');
        
        $where = [];
        
        // Filter hanya produk yang stock > 0
        $where['stock[>]'] = 0;
        
        if ($search) {
            $where['OR'] = [
                'product_name[~]' => $search,
                'description[~]' => $search
            ];
        }
        
        if (!isset($where['ORDER'])) {
            $where['ORDER'] = ['created_at' => 'DESC'];
        }
        
        $result = $productModel->paginate($page, 12, '*', $where);
        
        return $this->render($response, 'customers/index.twig', [
            'products' => $result['data'],
            'pagination' => $result,
            'search' => $search
        ]);
    }
    
    /**
     * Product Detail
     */
    public function productDetail($request, $response, $args)
    {
        $productId = $args['id'];
        $productModel = new Product();
        
        $product = $productModel->find($productId);
        
        if (!$product) {
            $_SESSION['error'] = 'Produk tidak ditemukan!';
            return $this->redirect($response, 'customer.home');
        }
        
        return $this->render($response, 'customers/product-detail.twig', [
            'product' => $product
        ]);
    }
    
    /**
     * Add to Cart (Create Order)
     */
    public function addToCart($request, $response)
    {
        $data = $request->getParsedBody();
        $productId = (int)($data['product_id'] ?? 0);
        $quantity = (int)($data['quantity'] ?? 1);
        
        if ($quantity <= 0) {
            $_SESSION['error'] = 'Jumlah produk tidak valid!';
            return $this->redirect($response, 'customer.home');
        }
        
        $productModel = new Product();
        $product = $productModel->find($productId);
        
        if (!$product) {
            $_SESSION['error'] = 'Produk tidak ditemukan!';
            return $this->redirect($response, 'customer.home');
        }
        
        if ($product['stock'] < $quantity) {
            $_SESSION['error'] = 'Stock tidak mencukupi!';
            return $this->redirect($response, 'customer.product.detail', ['id' => $productId]);
        }
        
        // Initialize cart in session
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Add or update cart item
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = [
                'product_id' => $product['id'],
                'product_name' => $product['product_name'],
                'price' => (int)$product['price'],
                'quantity' => $quantity,
                'image_url' => $product['image_url']
            ];
        }
        
        // Check stock again
        if ($_SESSION['cart'][$productId]['quantity'] > $product['stock']) {
            $_SESSION['cart'][$productId]['quantity'] = $product['stock'];
            $_SESSION['warning'] = 'Jumlah disesuaikan dengan stock yang tersedia!';
        } else {
            $_SESSION['success'] = 'Produk berhasil ditambahkan ke keranjang!';
        }
        
        return $this->redirect($response, 'customer.cart');
    }
    
    /**
     * View Cart
     */
    public function cart($request, $response)
    {
        $cart = $_SESSION['cart'] ?? [];
        
        $total = 0;
        foreach ($cart as $item) {
            $total += (int)$item['price'] * (int)$item['quantity'];
        }
        
        return $this->render($response, 'customers/cart.twig', [
            'cart' => $cart,
            'total' => $total
        ]);
    }
    
    /**
     * Update Cart
     */
    public function updateCart($request, $response)
    {
        $data = $request->getParsedBody();
        $productId = (int)($data['product_id'] ?? 0);
        $quantity = (int)($data['quantity'] ?? 1);
        
        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['error'] = 'Item tidak ditemukan di keranjang!';
            return $this->redirect($response, 'customer.cart');
        }
        
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
            $_SESSION['success'] = 'Item berhasil dihapus dari keranjang!';
            return $this->redirect($response, 'customer.cart');
        }
        
        // Check stock
        $productModel = new Product();
        $product = $productModel->find($productId);
        
        if ($product && $quantity > $product['stock']) {
            $_SESSION['error'] = 'Stock tidak mencukupi!';
            return $this->redirect($response, 'customer.cart');
        }
        
        $_SESSION['cart'][$productId]['quantity'] = $quantity;
        $_SESSION['success'] = 'Keranjang berhasil diupdate!';
        
        return $this->redirect($response, 'customer.cart');
    }
    
    /**
     * Remove from Cart
     */
    public function removeFromCart($request, $response, $args)
    {
        $productId = (int)$args['id'];
        
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            $_SESSION['success'] = 'Item berhasil dihapus dari keranjang!';
        }
        
        return $this->redirect($response, 'customer.cart');
    }
    
    /**
     * Checkout - Create Order
     */
    public function checkout($request, $response)
    {
        $cart = $_SESSION['cart'] ?? [];
        
        if (empty($cart)) {
            $_SESSION['error'] = 'Keranjang kosong!';
            return $this->redirect($response, 'customer.home');
        }
        
        try {
            $db = \App\Models\BaseModel::db();
            $db->pdo->beginTransaction();
            
            // Calculate total (int, bukan decimal)
            $totalAmount = 0;
            foreach ($cart as $item) {
                $totalAmount += (int)$item['price'] * (int)$item['quantity'];
            }
            
            // Create Order
            $orderModel = new Order();
            $orderNumber = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            $orderId = $orderModel->create([
                'user_id' => $_SESSION['user']['id'],
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'status' => 'pending'
            ]);
            
            // Create Order Items & Decrease Stock
            $orderItemsModel = new OrderItems();
            $productModel = new Product();
            
            foreach ($cart as $item) {
                // Check stock again
                $product = $productModel->find($item['product_id']);
                if (!$product || $product['stock'] < $item['quantity']) {
                    throw new \Exception('Stock tidak mencukupi untuk ' . $item['product_name']);
                }
                
                $subtotal = (int)$item['price'] * (int)$item['quantity'];
                
                // Create order item
                $orderItemsModel->create([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => (int)$item['quantity'],
                    'price' => (int)$item['price'],
                    'subtotal' => $subtotal
                ]);
                
                // Decrease stock
                $productModel->decreaseStock($item['product_id'], $item['quantity']);
            }
            
            $db->pdo->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            
            $_SESSION['success'] = 'Order berhasil dibuat!';
            
            // Redirect to payment
            return $this->redirect($response, 'customer.payment', ['id' => $orderId]);
            
        } catch (\Exception $e) {
            $db->pdo->rollBack();
            $_SESSION['error'] = 'Gagal membuat order: ' . $e->getMessage();
            return $this->redirect($response, 'customer.cart');
        }
    }
    
    /**
     * Payment Page
     */
    public function payment($request, $response, $args)
    {
        $orderId = $args['id'];
        $orderModel = new Order();
        
        $order = $orderModel->getWithItems($orderId);
        
        if (!$order || $order['user_id'] != $_SESSION['user']['id']) {
            $_SESSION['error'] = 'Order tidak ditemukan!';
            return $this->redirect($response, 'customer.orders');
        }
        
        // Check if payment already exists
        $paymentModel = new Payment();
        $payment = $paymentModel->findByOrderId($orderId);
        
        return $this->render($response, 'customers/mypayment.twig', [
            'order' => $order,
            'payment' => $payment
        ]);
    }
    
    /**
     * Process Payment (Midtrans)
     */
    public function processPayment($request, $response, $args)
    {
        $orderId = $args['id'];
        $orderModel = new Order();
        
        $order = $orderModel->find($orderId);
        
        if (!$order || $order['user_id'] != $_SESSION['user']['id']) {
            return $response->withJson([
                'success' => false,
                'message' => 'Order tidak ditemukan!'
            ], 404);
        }
        
        if ($order['status'] != 'pending') {
            return $response->withJson([
                'success' => false,
                'message' => 'Order sudah diproses!'
            ], 400);
        }
        
        try {
            // Midtrans Configuration
            \Midtrans\Config::$serverKey = $_ENV['MIDTRANS_SERVER_KEY'] ?? 'SB-Mid-server-YOUR_SERVER_KEY';
            \Midtrans\Config::$isProduction = false;
            \Midtrans\Config::$isSanitized = true;
            \Midtrans\Config::$is3ds = true;
            
            // Transaction details (total_amount sudah int)
            $transactionDetails = [
                'order_id' => $order['order_number'],
                'gross_amount' => (int)$order['total_amount']
            ];
            
            // Customer details
            $customerDetails = [
                'first_name' => $_SESSION['user']['username'],
                'email' => $_SESSION['user']['email']
            ];
            
            // Transaction data
            $transaction = [
                'transaction_details' => $transactionDetails,
                'customer_details' => $customerDetails
            ];
            
            // Get Snap Token
            $snapToken = \Midtrans\Snap::getSnapToken($transaction);
            
            // Save payment record
            $paymentModel = new Payment();
            
            // Check if payment already exists
            $existingPayment = $paymentModel->findByOrderId($orderId);
            
            if ($existingPayment) {
                // Update snap token
                $paymentModel->update($existingPayment['id'], [
                    'snap_token' => $snapToken
                ]);
            } else {
                // Create new payment
                $paymentModel->create([
                    'order_id' => $orderId,
                    'transaction_id' => $order['order_number'],
                    'payment_type' => 'midtrans',
                    'gross_amount' => (int)$order['total_amount'],
                    'status' => 'pending',
                    'snap_token' => $snapToken
                ]);
            }
            
            return $response->withJson([
                'success' => true,
                'snap_token' => $snapToken
            ]);
            
        } catch (\Exception $e) {
            error_log("Process Payment Error: " . $e->getMessage());
            return $response->withJson([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * My Orders
     */
    public function myOrders($request, $response)
    {
        $orderModel = new Order();
        $userId = $_SESSION['user']['id'];
        
        $orders = $orderModel->all('*', [
            'user_id' => $userId,
            'ORDER' => ['created_at' => 'DESC']
        ]);
        
        return $this->render($response, 'customers/myorders.twig', [
            'orders' => $orders
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
        
        if (!$order || $order['user_id'] != $_SESSION['user']['id']) {
            $_SESSION['error'] = 'Order tidak ditemukan!';
            return $this->redirect($response, 'customer.orders');
        }
        
        // Get payment info
        $paymentModel = new Payment();
        $payment = $paymentModel->findByOrderId($orderId);
        
        return $this->render($response, 'customers/order-detail.twig', [
            'order' => $order,
            'payment' => $payment
        ]);
    }
    
    /**
     * Payment Callback (Webhook from Midtrans)
     */
    public function paymentCallback($request, $response)
    {
        try {
            // Get JSON input
            $json = file_get_contents('php://input');
            $notification = json_decode($json, true); // Decode as array
            
            // Log raw notification
            error_log("Payment Callback Raw: " . $json);
            
            \Midtrans\Config::$serverKey = $_ENV['MIDTRANS_SERVER_KEY'] ?? 'SB-Mid-server-YOUR_SERVER_KEY';
            \Midtrans\Config::$isProduction = false;
            
            // Get transaction status safely
            $transactionStatus = $notification['transaction_status'] ?? 'pending';
            $orderNumber = $notification['order_id'] ?? null;
            $fraudStatus = $notification['fraud_status'] ?? 'accept';
            $paymentType = $notification['payment_type'] ?? 'unknown';
            $grossAmount = isset($notification['gross_amount']) ? (int)$notification['gross_amount'] : 0;
            
            if (!$orderNumber) {
                error_log("Payment Callback: Invalid order_id");
                return $response->withJson(['message' => 'Invalid order_id'], 400);
            }
            
            // Find order
            $orderModel = new Order();
            $order = $orderModel->findBy(['order_number' => $orderNumber]);
            
            if (!$order) {
                error_log("Payment Callback: Order not found for order_number: {$orderNumber}");
                return $response->withJson(['message' => 'Order not found'], 404);
            }
            
            $paymentModel = new Payment();
            $payment = $paymentModel->findByOrderId($order['id']);
            
            // Determine status based on transaction_status
            // Mapping ke 3 status: pending, success, failed (sesuai requirement)
            $orderStatus = 'pending';
            $paymentStatus = 'pending';
            
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    $orderStatus = 'success';
                    $paymentStatus = 'success'; // ✅ success
                } else {
                    $orderStatus = 'failed';
                    $paymentStatus = 'failed'; // ✅ failed (fraud)
                }
            } else if ($transactionStatus == 'settlement') {
                $orderStatus = 'success';
                $paymentStatus = 'success'; // ✅ success
            } else if ($transactionStatus == 'pending') {
                $orderStatus = 'pending';
                $paymentStatus = 'pending'; // ✅ pending
            } else if ($transactionStatus == 'deny' || $transactionStatus == 'cancel' || $transactionStatus == 'expire') {
                $orderStatus = 'failed';
                $paymentStatus = 'failed'; // ✅ failed
            }
            
            // Update order status
            $orderModel->update($order['id'], ['status' => $orderStatus]);
            
            // Update payment status
            if ($payment) {
                $paymentModel->update($payment['id'], [
                    'status' => $paymentStatus,
                    'payment_type' => $paymentType,
                    'payment_response' => $json // Simpan raw response
                ]);
            }
            
            // Log untuk debugging
            error_log("Payment Callback Success: Order #{$order['id']} - Midtrans Status: {$transactionStatus} - Order Status: {$orderStatus} - Payment Status: {$paymentStatus}");
            
            return $response->withJson(['message' => 'OK']);
            
        } catch (\Exception $e) {
            error_log("Payment Callback Error: " . $e->getMessage());
            return $response->withJson(['message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Check Payment Status (Manual Check via API)
     */
    public function checkPaymentStatus($request, $response, $args)
    {
        $orderId = $args['id'];
        $orderModel = new Order();
        
        $order = $orderModel->find($orderId);
        
        if (!$order || $order['user_id'] != $_SESSION['user']['id']) {
            return $response->withJson([
                'success' => false,
                'message' => 'Order tidak ditemukan!'
            ], 404);
        }
        
        try {
            \Midtrans\Config::$serverKey = $_ENV['MIDTRANS_SERVER_KEY'] ?? 'SB-Mid-server-YOUR_SERVER_KEY';
            \Midtrans\Config::$isProduction = false;
            
            // Get transaction status from Midtrans
            $statusResponse = \Midtrans\Transaction::status($order['order_number']);
            
            // Convert object to array for safer access
            $statusArray = json_decode(json_encode($statusResponse), true);
            
            $transactionStatus = $statusArray['transaction_status'] ?? 'pending';
            $fraudStatus = $statusArray['fraud_status'] ?? 'accept';
            $paymentType = $statusArray['payment_type'] ?? 'unknown';
            $grossAmount = isset($statusArray['gross_amount']) ? (int)$statusArray['gross_amount'] : 0;
            
            // Determine status - Mapping ke 3 status: pending, success, failed
            $orderStatus = 'pending';
            $paymentStatus = 'pending';
            
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    $orderStatus = 'success';
                    $paymentStatus = 'success'; // ✅ success
                } else {
                    $orderStatus = 'failed';
                    $paymentStatus = 'failed'; // ✅ failed (fraud)
                }
            } else if ($transactionStatus == 'settlement') {
                $orderStatus = 'success';
                $paymentStatus = 'success'; // ✅ success
            } else if ($transactionStatus == 'pending') {
                $orderStatus = 'pending';
                $paymentStatus = 'pending'; // ✅ pending
            } else if ($transactionStatus == 'deny' || $transactionStatus == 'cancel' || $transactionStatus == 'expire') {
                $orderStatus = 'failed';
                $paymentStatus = 'failed'; // ✅ failed
            }
            
            // Update order
            $orderModel->update($orderId, ['status' => $orderStatus]);
            
            // Update payment
            $paymentModel = new Payment();
            $payment = $paymentModel->findByOrderId($orderId);
            
            if ($payment) {
                $paymentModel->update($payment['id'], [
                    'status' => $paymentStatus,
                    'payment_type' => $paymentType,
                    'payment_response' => json_encode($statusArray) // Simpan response
                ]);
            }
            
            error_log("Check Payment Status Success: Order #{$orderId} - Midtrans: {$transactionStatus} - Order Status: {$orderStatus} - Payment Status: {$paymentStatus}");
            
            return $response->withJson([
                'success' => true,
                'order_status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'transaction_status' => $transactionStatus
            ]);
            
        } catch (\Exception $e) {
            error_log("Check Payment Status Error: " . $e->getMessage());
            return $response->withJson([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}