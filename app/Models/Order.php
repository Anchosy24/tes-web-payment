<?php

namespace App\Models;

class Order extends BaseModel
{
    protected $table = "orders";
    public function getRecent($limit)
    {
        return self::db()->select($this->table, [
            'id',
            'user_id',
            'total_amount',
            'status',
            'created_at'
        ], [
            'ORDER' => ['created_at' => 'DESC'],
            'LIMIT' => $limit
        ]);
    }

    public function getByStatus($status, $columns = '*')
    {
        return self::db()->select($this->table, $columns, [
            'status' => $status,
            'ORDER' => ['created_at' => 'DESC']
        ]);
    }

    public function countByStatus($status)
    {
        return $this->count(['status' => $status]);
    }

    public function getTotalPaid()
    {
        return $this->sum('total_amount', ['status' => 'success']);
    }

    public function getTotalUnpaid()
    {
        return $this->sum('total_amount', ['status' => ['pending', 'failed']]);
    }

    public function getWithUser($page = 1, $perPage = 20, $filters = [])
    {
        $offset = ($page - 1) * $perPage;
        
        $where = [
            'LIMIT' => [$offset, $perPage],
            'ORDER' => ['orders.created_at' => 'DESC']
        ];
        
        if (!empty($filters['status'])) {
            $where['orders.status'] = $filters['status'];
        }
        
        $data = self::db()->select($this->table, [
            '[>]users' => ['user_id' => 'id']
        ], [
            'orders.id',
            'orders.order_number',
            'orders.total_amount',
            'orders.status',
            'orders.created_at',
            'users.username',
            'users.email'
        ], $where);
        
        $countWhere = $where;
        unset($countWhere['LIMIT'], $countWhere['ORDER']);
        $total = self::db()->count($this->table, $countWhere);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    public function getWithItems($orderId)
    {
        $order = $this->find($orderId);
        
        if (!$order) {
            return null;
        }
        
        $items = self::db()->select('order_items', '*', [
            'order_id' => $orderId
        ]);
        
        $order['items'] = $items;
        
        return $order;
    }
}