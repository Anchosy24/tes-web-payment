<?php

namespace App\Models;

class Payment extends BaseModel
{
    protected $table = "payments";

    public function findByOrderId($orderId)
    {
        return self::db()->get($this->table, '*', [
            'order_id' => $orderId
        ]);
    }

    public function getWithOrder($page = 1, $perPage = 20, $filters = [])
    {
        $offset = ($page - 1) * $perPage;
        
        $where = [
            'LIMIT' => [$offset, $perPage],
            'ORDER' => ['payments.created_at' => 'DESC']
        ];
        
        if (!empty($filters['status'])) {
            $where['payments.status'] = $filters['status'];
        }
        
        $data = self::db()->select($this->table, [
            '[>]orders' => ['order_id' => 'id'],
            '[>]users' => ['orders.user_id' => 'id']
        ], [
            'payments.id',
            'payments.transaction_id',
            'payments.payment_type',
            'payments.gross_amount',
            'payments.status',
            'payments.created_at',
            'orders.order_number',
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

    public function getTotalSuccess()
    {
        return $this->sum('gross_amount', ['status' => 'settlement']);
    }

    public function countByStatus($status)
    {
        return $this->count(['status' => $status]);
    }
}