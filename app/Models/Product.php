<?php

namespace App\Models;

class Product extends BaseModel
{
    protected $table = "products";

    public function getAll($columns = '*', array $where = [])
    {
        if (!isset($where['ORDER'])) {
            $where['ORDER'] = ['created_at' => 'DESC'];
        }
        return $this->all($columns, $where);
    }

    public function getInStock($columns = '*')
    {
        return self::db()->select($this->table, $columns, [
            'stock[>]' => 0,
            'ORDER' => ['product_name' => 'ASC']
        ]);
    }

    public function getLowStock($threshold = 10, $columns = '*')
    {
        return self::db()->select($this->table, $columns, [
            'stock[<]' => $threshold,
            'stock[>]' => 0,
            'ORDER' => ['stock' => 'ASC']
        ]);
    }

    public function hasStock($productId, $quantity)
    {
        $product = $this->find($productId, ['stock']);
        return $product && $product['stock'] >= $quantity;
    }

    public function decreaseStock($productId, $quantity)
    {
        $result = self::db()->update($this->table, [
            'stock[-]' => $quantity
        ], [
            'id' => $productId
        ]);
        
        return $result->rowCount() > 0;
    }

    public function increaseStock($productId, $quantity)
    {
        $result = self::db()->update($this->table, [
            'stock[+]' => $quantity
        ], [
            'id' => $productId
        ]);
        
        return $result->rowCount() > 0;
    }

    public function search($keyword, $columns = '*')
    {
        return self::db()->select($this->table, $columns, [
            'OR' => [
                'product_name[~]' => $keyword,
                'description[~]' => $keyword
            ],
            'ORDER' => ['product_name' => 'ASC']
        ]);
    }

    public function getTotalValue()
    {
        $products = $this->all(['price', 'stock']);
        
        $total = 0;
        foreach ($products as $product) {
            $total += ($product['price'] * $product['stock']);
        }
        
        return $total;
    }
}