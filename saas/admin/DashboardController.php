<?php
/**
 * SaaS Admin Dashboard متحكم لوحة المراقبة
 * 
 * يدير لوحة التحكم الإدارية
 */

namespace SCCIT\ERP\Saas\Admin;

class DashboardController
{
    protected $db;
    protected $saas;
    protected $tenantId;
    
    /**
     * Constructor
     * 
     * @param object $db Database connection
     * @param object $saas SaaS Bootstrap
     * @param int $tenantId Current tenant ID
     */
    public function __construct($db, $saas, $tenantId)
    {
        $this->db = $db;
        $this->saas = $saas;
        $this->tenantId = $tenantId;
    }
    
    /**
     * عرض لوحة المعلومات الرئيسية
     * 
     * @return array
     */
    public function getDashboardData()
    {
        $tenantManager = $this->saas->getTenantManager();
        $subscriptionManager = $this->saas->getSubscriptionManager();
        $invoiceManager = $this->saas->getInvoiceManager();
        
        $tenant = $tenantManager->getTenantById($this->tenantId);
        $subscription = $subscriptionManager->getActiveSubscription($this->tenantId);
        $resourceUsage = $subscriptionManager->getResourceUsage($this->tenantId);
        
        // آخر الفواتير
        $recentInvoices = $invoiceManager->getTenantInvoices($this->tenantId, 5);
        
        // الفواتير المتأخرة
        $overdueInvoices = $invoiceManager->getOverdueInvoices($this->tenantId);
        
        return array(
            'tenant' => $tenant,
            'subscription' => $subscription,
            'resource_usage' => $resourceUsage,
            'recent_invoices' => $recentInvoices,
            'overdue_invoices' => $overdueInvoices,
            'dashboard_stats' => $this->getStatistics()
        );
    }
    
    /**
     * الحصول على إحصائيات اللوحة
     * 
     * @return array
     */
    protected function getStatistics()
    {
        $stats = array(
            'total_revenue' => 0,
            'active_tenants' => 0,
            'total_users' => 0,
            'active_subscriptions' => 0
        );
        
        // إجمالي الإيرادات
        $sql = "SELECT SUM(amount) as total FROM saas_payments WHERE status = 'completed'";
        $result = $this->db->query($sql);
        if ($result && $this->db->num_rows($result) > 0) {
            $row = $this->db->fetch_assoc($result);
            $stats['total_revenue'] = $row['total'] ?? 0;
        }
        
        // عد المستأجرين النشطين
        $sql = "SELECT COUNT(*) as count FROM saas_tenants WHERE status = 'active'";
        $result = $this->db->query($sql);
        if ($result && $this->db->num_rows($result) > 0) {
            $row = $this->db->fetch_assoc($result);
            $stats['active_tenants'] = $row['count'] ?? 0;
        }
        
        // عد المستخدمين
        $sql = "SELECT COUNT(*) as count FROM saas_users WHERE status = 'active'";
        $result = $this->db->query($sql);
        if ($result && $this->db->num_rows($result) > 0) {
            $row = $this->db->fetch_assoc($result);
            $stats['total_users'] = $row['count'] ?? 0;
        }
        
        // عد الاشتراكات النشطة
        $sql = "SELECT COUNT(*) as count FROM saas_subscriptions WHERE status = 'active'";
        $result = $this->db->query($sql);
        if ($result && $this->db->num_rows($result) > 0) {
            $row = $this->db->fetch_assoc($result);
            $stats['active_subscriptions'] = $row['count'] ?? 0;
        }
        
        return $stats;
    }
    
    /**
     * إدارة المستأجرين
     * 
     * @return array
     */
    public function manageTenants()
    {
        $sql = "SELECT * FROM saas_tenants ORDER BY created_at DESC";
        $result = $this->db->query($sql);
        
        $tenants = array();
        
        if ($result) {
            while ($row = $this->db->fetch_assoc($result)) {
                $tenants[] = $row;
            }
        }
        
        return $tenants;
    }
    
    /**
     * إدارة المستخدمين
     * 
     * @return array
     */
    public function manageUsers()
    {
        $sql = "SELECT * FROM saas_users WHERE tenant_id = " . (int)$this->tenantId . " ORDER BY created_at DESC";
        $result = $this->db->query($sql);
        
        $users = array();
        
        if ($result) {
            while ($row = $this->db->fetch_assoc($result)) {
                // إزالة كلمة المرور من النتيجة
                unset($row['password_hash']);
                $users[] = $row;
            }
        }
        
        return $users;
    }
    
    /**
     * سجل النشاط
     * 
     * @param int $limit
     * @return array
     */
    public function getActivityLog($limit = 100)
    {
        $sql = "SELECT * FROM saas_activity_logs WHERE tenant_id = " . (int)$this->tenantId;
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
     * تقارير الإيرادات
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getRevenueReport($startDate, $endDate)
    {
        $sql = "SELECT ";
        $sql .= "DATE(created_at) as date, ";
        $sql .= "COUNT(*) as total_payments, ";
        $sql .= "SUM(amount) as total_amount ";
        $sql .= "FROM saas_payments ";
        $sql .= "WHERE status = 'completed' ";
        $sql .= "AND created_at BETWEEN '" . $this->db->escape($startDate) . "' AND '" . $this->db->escape($endDate) . "' ";
        $sql .= "GROUP BY DATE(created_at) ";
        $sql .= "ORDER BY date DESC";
        
        $result = $this->db->query($sql);
        $report = array();
        
        if ($result) {
            while ($row = $this->db->fetch_assoc($result)) {
                $report[] = $row;
            }
        }
        
        return $report;
    }
}
?>
