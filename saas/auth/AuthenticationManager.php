<?php
/**
 * AuthenticationManager - إدارة المصادقة
 * 
 * نظام تسجيل الدخول والمصادقة متعدد المستأجرين
 */

namespace SCCIT\ERP\Saas\Auth;

class AuthenticationManager
{
    protected $db;
    protected $sessionTimeout = 3600; // ساعة واحدة
    protected $passwordHashAlgo = PASSWORD_BCRYPT;
    protected $passwordHashOptions = array('cost' => 12);
    
    /**
     * Constructor
     * 
     * @param object $db Database connection
     */
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    /**
     * تسجيل مستخدم جديد
     * 
     * @param array $userData
     * @return int|false
     */
    public function registerUser($userData)
    {
        // التحقق من أن البريد الإلكتروني غير موجود
        if ($this->userExists($userData['email'])) {
            return false;
        }
        
        $hashedPassword = password_hash($userData['password'], $this->passwordHashAlgo, $this->passwordHashOptions);
        
        $sql = "INSERT INTO saas_users (";
        $sql .= "tenant_id, first_name, last_name, email, password_hash, role, status, created_at";
        $sql .= ") VALUES (";
        $sql .= (int)$userData['tenant_id'] . ", ";
        $sql .= "'" . $this->db->escape($userData['first_name'] ?? '') . "', ";
        $sql .= "'" . $this->db->escape($userData['last_name'] ?? '') . "', ";
        $sql .= "'" . $this->db->escape($userData['email']) . "', ";
        $sql .= "'" . $this->db->escape($hashedPassword) . "', ";
        $sql .= "'" . $this->db->escape($userData['role'] ?? 'user') . "', ";
        $sql .= "'active', NOW())";
        
        if ($this->db->query($sql)) {
            return $this->db->last_insert_id();
        }
        return false;
    }
    
    /**
     * التحقق من بيانات المستخدم
     * 
     * @param string $email
     * @param string $password
     * @return array|false
     */
    public function authenticate($email, $password)
    {
        $sql = "SELECT * FROM saas_users WHERE email = '" . $this->db->escape($email) . "'";
        $result = $this->db->query($sql);
        
        if ($result && $this->db->num_rows($result) > 0) {
            $user = $this->db->fetch_assoc($result);
            
            // التحقق من حالة المستخدم
            if ($user['status'] !== 'active') {
                return false;
            }
            
            // التحقق من كلمة المرور
            if (password_verify($password, $user['password_hash'])) {
                // تحديث آخر تسجيل دخول
                $updateSql = "UPDATE saas_users SET last_login = NOW() WHERE id = " . (int)$user['id'];
                $this->db->query($updateSql);
                
                return $user;
            }
        }
        return false;
    }
    
    /**
     * التحقق من وجود مستخدم
     * 
     * @param string $email
     * @return bool
     */
    public function userExists($email)
    {
        $sql = "SELECT id FROM saas_users WHERE email = '" . $this->db->escape($email) . "'";
        $result = $this->db->query($sql);
        
        return ($result && $this->db->num_rows($result) > 0);
    }
    
    /**
     * تحديث كلمة المرور
     * 
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
    public function updatePassword($userId, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, $this->passwordHashAlgo, $this->passwordHashOptions);
        
        $sql = "UPDATE saas_users SET password_hash = '" . $this->db->escape($hashedPassword) . "' WHERE id = " . (int)$userId;
        return (bool)$this->db->query($sql);
    }
    
    /**
     * تعطيل حساب المستخدم
     * 
     * @param int $userId
     * @return bool
     */
    public function disableUser($userId)
    {
        $sql = "UPDATE saas_users SET status = 'disabled' WHERE id = " . (int)$userId;
        return (bool)$this->db->query($sql);
    }
    
    /**
     * تفعيل حساب المستخدم
     * 
     * @param int $userId
     * @return bool
     */
    public function enableUser($userId)
    {
        $sql = "UPDATE saas_users SET status = 'active' WHERE id = " . (int)$userId;
        return (bool)$this->db->query($sql);
    }
    
    /**
     * الحصول على بيانات المستخدم
     * 
     * @param int $userId
     * @return array|false
     */
    public function getUserById($userId)
    {
        $sql = "SELECT * FROM saas_users WHERE id = " . (int)$userId;
        $result = $this->db->query($sql);
        
        if ($result && $this->db->num_rows($result) > 0) {
            return $this->db->fetch_assoc($result);
        }
        return false;
    }
    
    /**
     * إنشاء رمز إعادة تعيين كلمة المرور
     * 
     * @param int $userId
     * @return string
     */
    public function generatePasswordResetToken($userId)
    {
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);
        $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $sql = "UPDATE saas_users SET password_reset_token = '" . $this->db->escape($hashedToken) . "', ";
        $sql .= "password_reset_expires = '" . $expiryTime . "' WHERE id = " . (int)$userId;
        
        if ($this->db->query($sql)) {
            return $token;
        }
        return false;
    }
    
    /**
     * التحقق من رمز إعادة تعيين كلمة المرور
     * 
     * @param string $token
     * @param int $userId
     * @return bool
     */
    public function verifyPasswordResetToken($token, $userId)
    {
        $hashedToken = hash('sha256', $token);
        
        $sql = "SELECT id FROM saas_users WHERE id = " . (int)$userId . " ";
        $sql .= "AND password_reset_token = '" . $this->db->escape($hashedToken) . "' ";
        $sql .= "AND password_reset_expires > NOW()";
        
        $result = $this->db->query($sql);
        
        return ($result && $this->db->num_rows($result) > 0);
    }
}
?>
