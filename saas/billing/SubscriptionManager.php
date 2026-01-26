<?php
/**
 * SubscriptionManager - إدارة الاشتراكات
 * 
 * يدير خطط الاشتراكات والعضويات
 */

namespace SCCIT\ERP\Saas\Billing;

class SubscriptionManager
{
    protected $db;
    protected $planConfig;
    
    /**
     * Plans (الخطط)
     */
    protected $plans = array(
        'starter' => array(
            'name' => 'Starter',
            'price' => 29.99,
            'currency' => 'USD',
            'billing_period' => 'monthly',
            'max_users' => 3,
            'max_invoices' => 100,
            'max_documents' => 500,
            'features' => array('basic_crm', 'invoicing', 'basic_reports'),
            'support' => 'email'
        ),
        'professional' => array(
            'name' => 'Professional',
            'price' => 79.99,
            'currency' => 'USD',
            'billing_period' => 'monthly',
            'max_users' => 10,
            'max_invoices' => 1000,
            'max_documents' => 5000,
            'features' => array('advanced_crm', 'invoicing', 'advanced_reports', 'inventory', 'workflows'),
            'support' => 'priority_email'
        ),
        'enterprise' => array(
            'name' => 'Enterprise',
            'price' => 299.99,
            'currency' => 'USD',
            'billing_period' => 'monthly',
            'max_users' => -1,
            'max_invoices' => -1,
            'max_documents' => -1,
            'features' => array('all_features', 'api_access', 'white_label', 'custom_integrations'),
            'support' => 'phone_email'
        )
    );
    
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
     * الحصول على الخطط المتاحة
     * 
     * @return array
     */
    public function getAvailablePlans()
    {
        return $this->plans;
    }
    
    /**
     * الحصول على تفاصيل الخطة
     * 
     * @param string $planId
     * @return array|false
     */
    public function getPlanDetails($planId)
    {
        return isset($this->plans[$planId]) ? $this->plans[$planId] : false;
    }
    
    /**
     * إنشاء اشتراك جديد
     * 
     * @param array $subscriptionData
     * @return int|false
     */
    public function createSubscription($subscriptionData)
    {
        // التحقق من صحة الخطة
        if (!isset($this->plans[$subscriptionData['plan_id']])) {
            return false;
        }
        
        $plan = $this->plans[$subscriptionData['plan_id']];
        
        // حساب تاريخ التجديد التالي
        $renewalDate = date('Y-m-d H:i:s', strtotime('+1 ' . $plan['billing_period']));
        
        $sql = "INSERT INTO saas_subscriptions (";
        $sql .= "tenant_id, plan_id, status, start_date, renewal_date, auto_renew, created_at";
        $sql .= ") VALUES (";
        $sql .= (int)$subscriptionData['tenant_id'] . ", ";
        $sql .= "'" . $this->db->escape($subscriptionData['plan_id']) . "', ";
        $sql .= "'active', NOW(), '" . $renewalDate . "', ";
        $sql .= (isset($subscriptionData['auto_renew']) && $subscriptionData['auto_renew']) ? "1" : "0";
        $sql .= ", NOW())";
        
        if ($this->db->query($sql)) {
            return $this->db->last_insert_id();
        }
        return false;
    }
    
    /**
     * الحصول على الاشتراك الحالي للمستأجر
     * 
     * @param int $tenantId
     * @return array|false
     */
    public function getActiveSubscription($tenantId)
    {
        $sql = "SELECT * FROM saas_subscriptions ";
        $sql .= "WHERE tenant_id = " . (int)$tenantId . " AND status = 'active'";
        $result = $this->db->query($sql);
        
        if ($result && $this->db->num_rows($result) > 0) {
            return $this->db->fetch_assoc($result);
        }
        return false;
    }
    
    /**
     * ترقية الاشتراك
     * 
     * @param int $subscriptionId
     * @param string $newPlanId
     * @return bool
     */
    public function upgradeSubscription($subscriptionId, $newPlanId)
    {
        if (!isset($this->plans[$newPlanId])) {
            return false;
        }
        
        $sql = "UPDATE saas_subscriptions SET plan_id = '" . $this->db->escape($newPlanId) . "' ";
        $sql .= "WHERE id = " . (int)$subscriptionId;
        
        return (bool)$this->db->query($sql);
    }
    
    /**
     * تجميد الاشتراك
     * 
     * @param int $subscriptionId
     * @return bool
     */
    public function freezeSubscription($subscriptionId)
    {
        $sql = "UPDATE saas_subscriptions SET status = 'frozen' WHERE id = " . (int)$subscriptionId;
        return (bool)$this->db->query($sql);
    }
    
    /**
     * إلغاء الاشتراك
     * 
     * @param int $subscriptionId
     * @param string $reason
     * @return bool
     */
    public function cancelSubscription($subscriptionId, $reason = '')
    {
        $sql = "UPDATE saas_subscriptions SET status = 'cancelled', cancellation_date = NOW() ";
        if ($reason) {
            $sql .= ", cancellation_reason = '" . $this->db->escape($reason) . "' ";
        }
        $sql .= "WHERE id = " . (int)$subscriptionId;
        
        return (bool)$this->db->query($sql);
    }
    
    /**
     * التحقق من حدود الخطة
     * 
     * @param int $tenantId
     * @param string $feature
     * @param int $currentUsage
     * @return bool
     */
    public function checkPlanLimit($tenantId, $feature, $currentUsage)
    {
        $subscription = $this->getActiveSubscription($tenantId);
        if (!$subscription) {
            return false;
        }
        
        $plan = $this->plans[$subscription['plan_id']];
        $limitKey = 'max_' . $feature;
        
        if (!isset($plan[$limitKey])) {
            return true;
        }
        
        $maxLimit = $plan[$limitKey];
        
        // -1 يعني غير محدود
        return ($maxLimit === -1 || $currentUsage < $maxLimit);
    }
    
    /**
     * الحصول على استخدام الموارد
     * 
     * @param int $tenantId
     * @return array
     */
    public function getResourceUsage($tenantId)
    {
        $usage = array(
            'users' => 0,
            'invoices' => 0,
            'documents' => 0
        );
        
        // عد المستخدمين
        $sql = "SELECT COUNT(*) as count FROM saas_users WHERE tenant_id = " . (int)$tenantId;
        $result = $this->db->query($sql);
        if ($result && $this->db->num_rows($result) > 0) {
            $row = $this->db->fetch_assoc($result);
            $usage['users'] = $row['count'];
        }
        
        return $usage;
    }
}
?>
