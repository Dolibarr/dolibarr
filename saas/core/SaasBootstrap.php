<?php
/**
 * SaasBootstrap - ملف البدء الرئيسي لنظام SaaS
 * 
 * يقوم بتحميل جميع المكونات الضرورية لنظام SaaS
 */

namespace SCCIT\ERP\Saas;

use SCCIT\ERP\Saas\Core\TenantManager;
use SCCIT\ERP\Saas\Auth\AuthenticationManager;
use SCCIT\ERP\Saas\Billing\SubscriptionManager;
use SCCIT\ERP\Saas\Billing\InvoiceManager;
use SCCIT\ERP\Saas\Payments\PaymentProcessor;
use SCCIT\ERP\Saas\Domains\DomainManager;

class SaasBootstrap
{
    protected static $instance;
    protected $db;
    protected $config;
    protected $tenantManager;
    protected $authManager;
    protected $subscriptionManager;
    protected $invoiceManager;
    protected $paymentProcessor;
    protected $domainManager;
    protected $currentTenant;
    protected $currentUser;
    
    /**
     * الحصول على نسخة Singleton
     * 
     * @return self
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize SaaS
     * 
     * @param object $db Database connection
     * @param array $config Configuration
     */
    public function initialize($db, $config = array())
    {
        $this->db = $db;
        $this->config = $this->loadConfig($config);
        
        // تحميل المديرين
        $this->tenantManager = new TenantManager($db, $this->config);
        $this->authManager = new AuthenticationManager($db);
        $this->subscriptionManager = new SubscriptionManager($db);
        $this->invoiceManager = new InvoiceManager($db, $this->config);
        $this->paymentProcessor = new PaymentProcessor($db, $this->config);
        $this->domainManager = new DomainManager($db, $this->config['main_domain'] ?? 'saas.sccit-erp.com');
        
        // تحديد المستأجر الحالي من النطاق
        $this->resolveTenant();
    }
    
    /**
     * تحميل الإعدادات
     * 
     * @param array $config
     * @return array
     */
    protected function loadConfig($config = array())
    {
        $defaults = array(
            'main_domain' => 'saas.sccit-erp.com',
            'app_name' => 'SCCIT ERP SaaS',
            'app_version' => '1.0.0',
            'environment' => 'production',
            'log_level' => 'info',
            'stripe_secret_key' => '',
            'stripe_publishable_key' => '',
            'paypal' => array(
                'client_id' => '',
                'client_secret' => ''
            ),
            'jwt_secret' => '',
            'session_timeout' => 3600,
            'trial_days' => 14,
            'max_api_requests_per_hour' => 1000
        );
        
        return array_merge($defaults, $config);
    }
    
    /**
     * تحديد المستأجر الحالي من النطاق
     */
    protected function resolveTenant()
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $host = strtolower($host);
        
        // إزالة البادئة www إن وجدت
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }
        
        $tenant = $this->domainManager->getTenantByDomain($host);
        
        if ($tenant) {
            $this->currentTenant = $tenant;
            $this->tenantManager->setCurrentTenant($tenant);
        }
    }
    
    /**
     * التحقق من وجود مستأجر في الجلسة الحالية
     * 
     * @return bool
     */
    public function hasTenant()
    {
        return $this->currentTenant !== null;
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
     * تعيين المستخدم الحالي
     * 
     * @param array $user
     */
    public function setCurrentUser($user)
    {
        $this->currentUser = $user;
    }
    
    /**
     * الحصول على المستخدم الحالي
     * 
     * @return array|null
     */
    public function getCurrentUser()
    {
        return $this->currentUser;
    }
    
    /**
     * الحصول على مدير المستأجرين
     * 
     * @return TenantManager
     */
    public function getTenantManager()
    {
        return $this->tenantManager;
    }
    
    /**
     * الحصول على مدير المصادقة
     * 
     * @return AuthenticationManager
     */
    public function getAuthManager()
    {
        return $this->authManager;
    }
    
    /**
     * الحصول على مدير الاشتراكات
     * 
     * @return SubscriptionManager
     */
    public function getSubscriptionManager()
    {
        return $this->subscriptionManager;
    }
    
    /**
     * الحصول على مدير الفواتير
     * 
     * @return InvoiceManager
     */
    public function getInvoiceManager()
    {
        return $this->invoiceManager;
    }
    
    /**
     * الحصول على معالج الدفع
     * 
     * @return PaymentProcessor
     */
    public function getPaymentProcessor()
    {
        return $this->paymentProcessor;
    }
    
    /**
     * الحصول على مدير النطاقات
     * 
     * @return DomainManager
     */
    public function getDomainManager()
    {
        return $this->domainManager;
    }
    
    /**
     * الحصول على الإعدادات
     * 
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
}
?>
