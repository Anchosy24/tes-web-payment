<?php

namespace App\Models;

use Medoo\Medoo;
use PDO;

class BaseModel
{
    protected static $db;
    protected $table;

    public static function db(): Medoo
    {
        if (!self::$db) {
            $config = require __DIR__ . '/../../config/database.php';

            self::$db = new Medoo([
                'database_type'     => 'mysql',
                'server'            => $config['host'],
                'database_name'     => $config['dbname'],
                'username'          => $config['username'],
                'password'          => $config['password'],
                'port'              => $config['port'] ?? 3306,
                'charset'           => $config['charset'] ?? 'utf8mb4',
                'collation'         => 'utf8mb4_general_ci',
                'option'            => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            ]);
        }

        return self::$db;
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
}