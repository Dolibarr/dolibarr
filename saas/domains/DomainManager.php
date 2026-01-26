<?php
/**
 * DomainManager - إدارة النطاقات المخصصة
 * 
 * يدير النطاقات المخصصة لكل مستأجر
 */

namespace SCCIT\ERP\Saas\Domains;

class DomainManager
{
    protected $db;
    protected $mainDomain;
    
    /**
     * Constructor
     * 
     * @param object $db Database connection
     * @param string $mainDomain Domain principal of the SaaS
     */
    public function __construct($db, $mainDomain = 'saas.sccit-erp.com')
    {
        $this->db = $db;
        $this->mainDomain = $mainDomain;
    }
    
    /**
     * إضافة نطاق مخصص للمستأجر
     * 
     * @param int $tenantId
     * @param string $domain
     * @param string $sslCertificate
     * @return int|false
     */
    public function addDomain($tenantId, $domain, $sslCertificate = '')
    {
        // التحقق من أن النطاق غير مستخدم
        if ($this->domainExists($domain)) {
            return false;
        }
        
        $sql = "INSERT INTO saas_domains (";
        $sql .= "tenant_id, domain_name, ssl_certificate, status, verified, created_at";
        $sql .= ") VALUES (";
        $sql .= (int)$tenantId . ", ";
        $sql .= "'" . $this->db->escape(strtolower($domain)) . "', ";
        $sql .= "'" . $this->db->escape($sslCertificate) . "', ";
        $sql .= "'pending', 0, NOW())";
        
        if ($this->db->query($sql)) {
            return $this->db->last_insert_id();
        }
        return false;
    }
    
    /**
     * التحقق من وجود نطاق
     * 
     * @param string $domain
     * @return bool
     */
    public function domainExists($domain)
    {
        $sql = "SELECT id FROM saas_domains WHERE domain_name = '" . $this->db->escape(strtolower($domain)) . "'";
        $result = $this->db->query($sql);
        
        return ($result && $this->db->num_rows($result) > 0);
    }
    
    /**
     * الحصول على المستأجر من النطاق
     * 
     * @param string $domain
     * @return array|false
     */
    public function getTenantByDomain($domain)
    {
        $domainLower = strtolower($domain);
        
        // البحث في النطاقات المخصصة
        $sql = "SELECT t.* FROM saas_tenants t ";
        $sql .= "INNER JOIN saas_domains d ON t.id = d.tenant_id ";
        $sql .= "WHERE d.domain_name = '" . $this->db->escape($domainLower) . "' ";
        $sql .= "AND d.status = 'active' AND d.verified = 1";
        
        $result = $this->db->query($sql);
        
        if ($result && $this->db->num_rows($result) > 0) {
            return $this->db->fetch_assoc($result);
        }
        
        // البحث في النطاق الرئيسي والنطاقات الفرعية
        if (preg_match('/^([a-zA-Z0-9-]+)\.' . preg_quote($this->mainDomain, '/') . '$/', $domainLower, $matches)) {
            $subdomain = $matches[1];
            $sql = "SELECT * FROM saas_tenants WHERE domain_name = '" . $this->db->escape($subdomain) . "'";
            $result = $this->db->query($sql);
            
            if ($result && $this->db->num_rows($result) > 0) {
                return $this->db->fetch_assoc($result);
            }
        }
        
        return false;
    }
    
    /**
     * التحقق من النطاق
     * 
     * @param int $domainId
     * @return bool
     */
    public function verifyDomain($domainId)
    {
        $sql = "UPDATE saas_domains SET verified = 1, status = 'active' WHERE id = " . (int)$domainId;
        return (bool)$this->db->query($sql);
    }
    
    /**
     * حذف النطاق
     * 
     * @param int $domainId
     * @return bool
     */
    public function removeDomain($domainId)
    {
        $sql = "DELETE FROM saas_domains WHERE id = " . (int)$domainId;
        return (bool)$this->db->query($sql);
    }
    
    /**
     * الحصول على نطاقات المستأجر
     * 
     * @param int $tenantId
     * @return array
     */
    public function getTenantDomains($tenantId)
    {
        $sql = "SELECT * FROM saas_domains WHERE tenant_id = " . (int)$tenantId;
        $result = $this->db->query($sql);
        
        $domains = array();
        
        if ($result) {
            while ($row = $this->db->fetch_assoc($result)) {
                $domains[] = $row;
            }
        }
        
        return $domains;
    }
    
    /**
     * تحديث شهادة SSL
     * 
     * @param int $domainId
     * @param string $certificate
     * @return bool
     */
    public function updateSSLCertificate($domainId, $certificate)
    {
        $sql = "UPDATE saas_domains SET ssl_certificate = '" . $this->db->escape($certificate) . "' ";
        $sql .= "WHERE id = " . (int)$domainId;
        
        return (bool)$this->db->query($sql);
    }
    
    /**
     * التحقق من صحة اسم النطاق
     * 
     * @param string $domain
     * @return bool
     */
    public function isValidDomain($domain)
    {
        // التحقق من صيغة النطاق
        $pattern = '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i';
        return (bool)preg_match($pattern, $domain);
    }
}
?>
