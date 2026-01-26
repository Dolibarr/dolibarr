<?php
/**
 * SaaS API بوابات التطبيق
 * 
 * توفر واجهات REST للتطبيقات الخارجية
 */

namespace SCCIT\ERP\Saas\API;

header('Content-Type: application/json');

class ApiBase
{
    protected $db;
    protected $saas;
    protected $method;
    protected $endpoint;
    protected $params;
    protected $response = array();
    protected $statusCode = 200;
    
    /**
     * Constructor
     * 
     * @param object $db Database connection
     * @param object $saas SaaS Bootstrap
     */
    public function __construct($db, $saas)
    {
        $this->db = $db;
        $this->saas = $saas;
        $this->parseRequest();
    }
    
    /**
     * تحليل الطلب
     */
    protected function parseRequest()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->params = $this->getRequestData();
        
        // حل النقطة النهائية من URL
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $parts = explode('/', trim($uri, '/'));
        $this->endpoint = $parts[count($parts) - 1] ?? '';
    }
    
    /**
     * الحصول على بيانات الطلب
     * 
     * @return array
     */
    protected function getRequestData()
    {
        if ($this->method === 'GET') {
            return $_GET;
        } elseif (in_array($this->method, array('POST', 'PUT', 'PATCH'))) {
            $input = file_get_contents('php://input');
            return json_decode($input, true) ?? array();
        }
        return array();
    }
    
    /**
     * التحقق من المصادقة
     * 
     * @return bool
     */
    protected function authenticate()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader)) {
            $this->sendError('Unauthorized', 401);
            return false;
        }
        
        if (strpos($authHeader, 'Bearer ') !== 0) {
            $this->sendError('Invalid authorization header', 401);
            return false;
        }
        
        $token = substr($authHeader, 7);
        
        // التحقق من الرمز (يمكن استخدام JWT)
        // هذا مثال بسيط
        
        return true;
    }
    
    /**
     * إرسال استجابة نجحت
     * 
     * @param array $data
     * @param int $statusCode
     */
    public function sendSuccess($data = array(), $statusCode = 200)
    {
        http_response_code($statusCode);
        echo json_encode(array(
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ));
        exit;
    }
    
    /**
     * إرسال استجابة الخطأ
     * 
     * @param string $message
     * @param int $statusCode
     */
    public function sendError($message = '', $statusCode = 400)
    {
        http_response_code($statusCode);
        echo json_encode(array(
            'success' => false,
            'error' => $message,
            'timestamp' => date('c')
        ));
        exit;
    }
    
    /**
     * التحقق من بيانات الطلب
     * 
     * @param array $required
     * @return bool
     */
    protected function validateRequired($required)
    {
        foreach ($required as $field) {
            if (empty($this->params[$field])) {
                $this->sendError('Missing required field: ' . $field, 400);
                return false;
            }
        }
        return true;
    }
    
    /**
     * معالجة الطلب
     */
    public function handle()
    {
        try {
            $methodName = 'action' . ucfirst($this->endpoint);
            
            if (method_exists($this, $methodName)) {
                $this->{$methodName}();
            } else {
                $this->sendError('Endpoint not found', 404);
            }
        } catch (\Exception $e) {
            $this->sendError('Internal server error: ' . $e->getMessage(), 500);
        }
    }
}

/**
 * Tenant API Endpoints
 */
class TenantApi extends ApiBase
{
    public function actionCreate()
    {
        if ($this->method !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $this->validateRequired(array('company_name', 'email', 'domain_name'));
        
        $tenantManager = $this->saas->getTenantManager();
        
        $tenantId = $tenantManager->createTenant(array(
            'company_name' => $this->params['company_name'],
            'email' => $this->params['email'],
            'domain_name' => $this->params['domain_name'],
            'phone' => $this->params['phone'] ?? '',
            'address' => $this->params['address'] ?? ''
        ));
        
        if ($tenantId) {
            $this->sendSuccess(array('tenant_id' => $tenantId, 'message' => 'Tenant created successfully'), 201);
        } else {
            $this->sendError('Failed to create tenant', 500);
        }
    }
    
    public function actionGet()
    {
        if ($this->method !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }
        
        $this->validateRequired(array('id'));
        
        $tenantManager = $this->saas->getTenantManager();
        $tenant = $tenantManager->getTenantById($this->params['id']);
        
        if ($tenant) {
            $this->sendSuccess($tenant);
        } else {
            $this->sendError('Tenant not found', 404);
        }
    }
}

/**
 * Subscription API Endpoints
 */
class SubscriptionApi extends ApiBase
{
    public function actionCreate()
    {
        if ($this->method !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $this->validateRequired(array('tenant_id', 'plan_id'));
        
        $subscriptionManager = $this->saas->getSubscriptionManager();
        
        $subscriptionId = $subscriptionManager->createSubscription(array(
            'tenant_id' => $this->params['tenant_id'],
            'plan_id' => $this->params['plan_id'],
            'auto_renew' => $this->params['auto_renew'] ?? true
        ));
        
        if ($subscriptionId) {
            $this->sendSuccess(array('subscription_id' => $subscriptionId), 201);
        } else {
            $this->sendError('Failed to create subscription', 500);
        }
    }
    
    public function actionPlans()
    {
        if ($this->method !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }
        
        $subscriptionManager = $this->saas->getSubscriptionManager();
        $plans = $subscriptionManager->getAvailablePlans();
        
        $this->sendSuccess($plans);
    }
}

/**
 * Invoice API Endpoints
 */
class InvoiceApi extends ApiBase
{
    public function actionList()
    {
        if ($this->method !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }
        
        $this->validateRequired(array('tenant_id'));
        
        $invoiceManager = $this->saas->getInvoiceManager();
        $limit = $this->params['limit'] ?? 50;
        $offset = $this->params['offset'] ?? 0;
        
        $invoices = $invoiceManager->getTenantInvoices($this->params['tenant_id'], $limit, $offset);
        
        $this->sendSuccess($invoices);
    }
    
    public function actionGet()
    {
        if ($this->method !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }
        
        $this->validateRequired(array('id'));
        
        $invoiceManager = $this->saas->getInvoiceManager();
        $invoice = $invoiceManager->getInvoice($this->params['id']);
        
        if ($invoice) {
            $this->sendSuccess($invoice);
        } else {
            $this->sendError('Invoice not found', 404);
        }
    }
}

/**
 * Payment API Endpoints
 */
class PaymentApi extends ApiBase
{
    public function actionProcess()
    {
        if ($this->method !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $this->validateRequired(array('tenant_id', 'subscription_id', 'amount', 'gateway'));
        
        $paymentProcessor = $this->saas->getPaymentProcessor();
        
        $paymentId = $paymentProcessor->processPayment(array(
            'tenant_id' => $this->params['tenant_id'],
            'subscription_id' => $this->params['subscription_id'],
            'amount' => $this->params['amount'],
            'currency' => $this->params['currency'] ?? 'USD',
            'payment_method' => $this->params['payment_method'],
            'gateway' => $this->params['gateway']
        ));
        
        if ($paymentId) {
            $this->sendSuccess(array('payment_id' => $paymentId), 201);
        } else {
            $this->sendError('Failed to process payment', 500);
        }
    }
}
?>
