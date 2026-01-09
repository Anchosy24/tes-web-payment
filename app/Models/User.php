<?php

namespace App\Models;

/**
 * User Model
 */
class User extends BaseModel
{
    protected $table = 'users';
    protected $fillable = [
        'username',
        'email',
        'password',
        'role'
    ];
    
    /**
     * Find user by username
     */
    public function findByUsername($username)
    {
        return $this->findBy(['username' => $username]);
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email)
    {
        return $this->findBy(['email' => $email]);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($email, $password)
    {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        return password_verify($password, $user['password']) ? $user : false;
    }
    
    /**
     * Get active users only
     */
    public function getActive($columns = '*')
    {
        return $this->all($columns, ['is_active' => 1]);
    }
    
    /**
     * Get users by role
     */
    public function getByRole($role, $columns = '*')
    {
        return $this->all($columns, ['role' => $role, 'is_active' => 1]);
    }
    
    /**
     * Count by role
     */
    public function countByRole($role)
    {
        return $this->count(['role' => $role, 'is_active' => 1]);
    }
    
    /**
     * Check if username exists
     */
    public function usernameExists($username, $excludeId = null)
    {
        $where = ['username' => $username];
        
        if ($excludeId) {
            $where['id[!]'] = $excludeId;
        }
        
        return $this->exists($where);
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null)
    {
        $where = ['email' => $email];
        
        if ($excludeId) {
            $where['id[!]'] = $excludeId;
        }
        
        return $this->exists($where);
    }
}