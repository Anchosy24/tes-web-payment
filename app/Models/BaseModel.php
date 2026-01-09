<?php

namespace App\Models;

use Medoo\Medoo;
use PDO;

class BaseModel
{
    protected static $db;
    protected $table;
    protected $primaryKey = 'id';

    public static function db(): Medoo
    {
        if (!self::$db) {
            $config = require __DIR__ . '/../../config/database.php';

            self::$db = new Medoo([
                'database_type' => 'mysql',
                'server' => $config['host'],
                'database_name' => $config['dbname'],
                'username' => $config['username'],
                'password' => $config['password'],
                'port' => $config['port'] ?? 3306,
                'charset' => $config['charset'] ?? 'utf8mb4',
                'collation' => 'utf8mb4_general_ci',
                'option' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            ]);
        }

        return self::$db;
    }

    public function find($id, $columns = '*')
    {
        return self::db()->get($this->table, $columns, [$this->primaryKey => $id]);
    }

    public function findBy(array $where, $columns = '*')
    {
        return self::db()->get($this->table, $columns, $where);
    }

    public function all($columns = '*', array $where = [])
    {
        return self::db()->select($this->table, $columns, $where);
    }

    public function exists(array $where)
    {
        return self::db()->has($this->table, $where);
    }

    public function count(array $where = [])
    {
        return self::db()->count($this->table, $where);
    }

    public function create(array $data)
    {
        self::db()->insert($this->table, $data);
        return self::db()->id();
    }

    public function update($id, array $data)
    {
        $result = self::db()->update($this->table, $data, [$this->primaryKey => $id]);
        return $result->rowCount();
    }

    public function delete($id)
    {
        $result = self::db()->delete($this->table, [$this->primaryKey => $id]);
        return $result->rowCount();
    }

    public function sum($column, array $where = [])
    {
        return self::db()->sum($this->table, $column, $where) ?? 0;
    }

    public function paginate($page = 1, $perPage = 10, $columns = '*', array $where = [])
    {
        $offset = ($page - 1) * $perPage;
        
        $where['LIMIT'] = [$offset, $perPage];
        
        if (!isset($where['ORDER'])) {
            $where['ORDER'] = [$this->primaryKey => 'DESC'];
        }
        
        $data = self::db()->select($this->table, $columns, $where);
        
        $countWhere = $where;
        unset($countWhere['LIMIT'], $countWhere['ORDER']);
        
        $total = $this->count($countWhere);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
            'has_more' => ($page * $perPage) < $total
        ];
    }
}