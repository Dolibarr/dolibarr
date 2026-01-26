<?php
/**
 * TenantManager - إدارة المستأجرين (Tenants)
 * 
 * يدير كل عمليات المستأجرين والعزل بين البيانات
 */

namespace SCCIT\ERP\Saas\Core;

class TenantManager
{
    protected $db;
    protected $currentTenant;
    protected $config;
    
    /**
     * Constructor
     * 
     * @param object $db Database connection
     * @param array $config Configuration array
     */
    public function __construct($db, $config = array())
    {
        $this->db = $db;
        $this->config = $config;
        $this->currentTenant = null;
    }
    
    /**
     * إنشاء مستأجر جديد
     * 
     * @param array $data
     * @return int|false
     */
    public function createTenant($data)
    {
        $sql = "INSERT INTO saas_tenants (";
        $sql .= "company_name, email, phone, address, city, postal_code, country_code, ";
        $sql .= "domain_name, database_name, status, created_at, updated_at";
        $sql .= ") VALUES (";
        $sql .= "'" . $this->db->escape($data['company_name']) . "', ";
        $sql .= "'" . $this->db->escape($data['email']) . "', ";
        $sql .= "'" . $this->db->escape($data['phone'] ?? '') . "', ";
        $sql .= "'" . $this->db->escape($data['address'] ?? '') . "', ";
        $sql .= "'" . $this->db->escape($data['city'] ?? '') . "', ";
        $sql .= "'" . $this->db->escape($data['postal_code'] ?? '') . "', ";
        $sql .= "'" . $this->db->escape($data['country_code'] ?? '') . "', ";
        $sql .= "'" . $this->db->escape($data['domain_name']) . "', ";
        $sql .= "'" . $this->db->escape($data['database_name'] ?? '') . "', ";
        $sql .= "'active', NOW(), NOW())";
        
        if ($this->db->query($sql)) {
            return $this->db->last_insert_id();
        }
        return false;
    }
    
    /**
     * الحصول على معلومات المستأجر من المعرف
     * 
     * @param int $tenantId
     * @return array|false
     */
    public function getTenantById($tenantId)
    {
        $sql = "SELECT * FROM saas_tenants WHERE id = " . (int)$tenantId;
        $result = $this->db->query($sql);
        
        if ($result && $this->db->num_rows($result) > 0) {
            return $this->db->fetch_assoc($result);
        }
        return false;
    }
    
    /**
     * الحصول على معلومات المستأجر من اسم المجال
     * 
     * @param string $domain
     * @return array|false
     */
    public function getTenantByDomain($domain)
    {
        $sql = "SELECT * FROM saas_tenants WHERE domain_name = '" . $this->db->escape($domain) . "'";
        $result = $this->db->query($sql);
        
        if ($result && $this->db->num_rows($result) > 0) {
            return $this->db->fetch_assoc($result);
        }
        return false;
    }
    
    /**
     * تحديث معلومات المستأجر
     * 
     * @param int $tenantId
     * @param array $data
     * @return bool
     */
    public function updateTenant($tenantId, $data)
    {
        $updates = array();
        
        if (isset($data['company_name'])) {
            $updates[] = "company_name = '" . $this->db->escape($data['company_name']) . "'";
        }
        if (isset($data['email'])) {
            $updates[] = "email = '" . $this->db->escape($data['email']) . "'";
        }
        if (isset($data['status'])) {
            $updates[] = "status = '" . $this->db->escape($data['status']) . "'";
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $updates[] = "updated_at = NOW()";
        
        $sql = "UPDATE saas_tenants SET " . implode(", ", $updates) . " WHERE id = " . (int)$tenantId;
        
        return (bool)$this->db->query($sql);
    }
    
    /**
     * حذف مستأجر
     * 
     * @param int $tenantId
     * @return bool
     */
    public function deleteTenant($tenantId)
    {
        $sql = "DELETE FROM saas_tenants WHERE id = " . (int)$tenantId;
        return (bool)$this->db->query($sql);
    }
    
    /**
     * تعيين المستأجر الحالي
     * 
     * @param array $tenant
     * @return void
     */
    public function setCurrentTenant($tenant)
    {
        $this->currentTenant = $tenant;
    }
    
    /**
     * الحصول على المستأجر الحالي
     * 
     * @return array|null
     */
    public function getCurrentTenant()
    {
        return $this->currentTenant;
    }
    
    /**
     * التحقق من أن المستأجر نشط
     * 
     * @param int $tenantId
     * @return bool
     */
    public function isTenantActive($tenantId)
    {
        $tenant = $this->getTenantById($tenantId);
        return ($tenant && $tenant['status'] === 'active');
    }
    
    /**
     * تعليق حساب المستأجر
     * 
     * @param int $tenantId
     * @param string $reason
     * @return bool
     */
    public function suspendTenant($tenantId, $reason = '')
    {
        return $this->updateTenant($tenantId, array('status' => 'suspended'));
    }
    
    /**
     * حذف جميع بيانات المستأجر
     * 
     * @param int $tenantId
     * @return bool
     */
    public function purgeAllTenantData($tenantId)
    {
        // حذف جميع البيانات المرتبطة بالمستأجر
        $tables = array(
            'saas_tenant_users',
            'saas_tenant_subscriptions',
            'saas_tenant_invoices',
            'saas_tenant_data'
        );
        
        foreach ($tables as $table) {
            $sql = "DELETE FROM " . $table . " WHERE tenant_id = " . (int)$tenantId;
            $this->db->query($sql);
        }
        
        return true;
    }
}
?>
