<?php
/**
 * ActivityLogger - تسجيل أنشطة المستخدمين
 * 
 * يسجل جميع أنشطة المستخدمين للمراجعة والامتثال
 */

namespace SCCIT\ERP\Saas\Core;

class ActivityLogger
{
    protected $db;
    
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
     * تسجيل نشاط
     * 
     * @param int $tenantId
     * @param int $userId
     * @param string $action
     * @param string $entityType
     * @param int $entityId
     * @param string $description
     * @return int|false
     */
    public function log($tenantId, $userId, $action, $entityType, $entityId, $description = '')
    {
        $sql = "INSERT INTO saas_activity_logs (";
        $sql .= "tenant_id, user_id, action, entity_type, entity_id, ";
        $sql .= "description, ip_address, user_agent, created_at";
        $sql .= ") VALUES (";
        $sql .= (int)$tenantId . ", ";
        $sql .= (int)$userId . ", ";
        $sql .= "'" . $this->db->escape($action) . "', ";
        $sql .= "'" . $this->db->escape($entityType) . "', ";
        $sql .= (int)$entityId . ", ";
        $sql .= "'" . $this->db->escape($description) . "', ";
        $sql .= "'" . $this->db->escape($this->getClientIp()) . "', ";
        $sql .= "'" . $this->db->escape($_SERVER['HTTP_USER_AGENT'] ?? '') . "', ";
        $sql .= "NOW())";
        
        if ($this->db->query($sql)) {
            return $this->db->last_insert_id();
        }
        return false;
    }
    
    /**
     * الحصول على سجل الأنشطة
     * 
     * @param int $tenantId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getActivityLog($tenantId, $limit = 50, $offset = 0)
    {
        $sql = "SELECT * FROM saas_activity_logs WHERE tenant_id = " . (int)$tenantId;
        $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $result = $this->db->query($sql);
        $logs = array();
        
        if ($result) {
            while ($row = $this->db->fetch_assoc($result)) {
                $logs[] = $row;
            }
        }
        
        return $logs;
    }
    
    /**
     * الحصول على أنشطة مستخدم محدد
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUserActivities($userId, $limit = 50)
    {
        $sql = "SELECT * FROM saas_activity_logs WHERE user_id = " . (int)$userId;
        $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;
        
        $result = $this->db->query($sql);
        $logs = array();
        
        if ($result) {
            while ($row = $this->db->fetch_assoc($result)) {
                $logs[] = $row;
            }
        }
        
        return $logs;
    }
    
    /**
     * حذف السجلات القديمة
     * 
     * @param int $daysOld حذف السجلات الأقدم من N يوم
     * @return int عدد السجلات المحذوفة
     */
    public function cleanup($daysOld = 90)
    {
        $date = date('Y-m-d', strtotime('-' . $daysOld . ' days'));
        
        $sql = "DELETE FROM saas_activity_logs WHERE created_at < '" . $date . "'";
        
        $this->db->query($sql);
        
        return $this->db->affected_rows();
    }
    
    /**
     * الحصول على عنوان IP للعميل
     * 
     * @return string
     */
    protected function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
}
?>
