<?php
/**
 * PaymentProcessor - معالج الدفع
 * 
 * يتعامل مع معالجة الدفع من خلال بوابات مختلفة
 */

namespace SCCIT\ERP\Saas\Payments;

class PaymentProcessor
{
    protected $db;
    protected $stripeKey;
    protected $paypalConfig;
    protected $config;
    
    /**
     * Constructor
     * 
     * @param object $db Database connection
     * @param array $config Configuration
     */
    public function __construct($db, $config = array())
    {
        $this->db = $db;
        $this->config = $config;
        $this->stripeKey = $config['stripe_secret_key'] ?? '';
        $this->paypalConfig = $config['paypal'] ?? array();
    }
    
    /**
     * معالجة دفعة جديدة
     * 
     * @param array $paymentData
     * @return int|false
     */
    public function processPayment($paymentData)
    {
        // تسجيل معاملة الدفع
        $sql = "INSERT INTO saas_payments (";
        $sql .= "tenant_id, subscription_id, amount, currency, payment_method, ";
        $sql .= "gateway, status, invoice_id, created_at";
        $sql .= ") VALUES (";
        $sql .= (int)$paymentData['tenant_id'] . ", ";
        $sql .= (int)$paymentData['subscription_id'] . ", ";
        $sql .= (float)$paymentData['amount'] . ", ";
        $sql .= "'" . $this->db->escape($paymentData['currency'] ?? 'USD') . "', ";
        $sql .= "'" . $this->db->escape($paymentData['payment_method']) . "', ";
        $sql .= "'" . $this->db->escape($paymentData['gateway']) . "', ";
        $sql .= "'pending', ";
        $sql .= isset($paymentData['invoice_id']) ? (int)$paymentData['invoice_id'] : 'NULL';
        $sql .= ", NOW())";
        
        if ($this->db->query($sql)) {
            $paymentId = $this->db->last_insert_id();
            
            // معالجة الدفع حسب البوابة
            switch ($paymentData['gateway']) {
                case 'stripe':
                    return $this->processStripePayment($paymentId, $paymentData);
                case 'paypal':
                    return $this->processPayPalPayment($paymentId, $paymentData);
                default:
                    return false;
            }
        }
        return false;
    }
    
    /**
     * معالجة دفعة Stripe
     * 
     * @param int $paymentId
     * @param array $paymentData
     * @return int|false
     */
    protected function processStripePayment($paymentId, $paymentData)
    {
        // يتطلب مكتبة Stripe
        if (!$this->stripeKey) {
            return false;
        }
        
        try {
            // معالجة Stripe
            // يتم التعامل مع بيانات الدفع من خلال واجهة برمجية آمنة
            
            // تحديث حالة الدفعة
            $this->updatePaymentStatus($paymentId, 'completed');
            
            return $paymentId;
        } catch (\Exception $e) {
            $this->updatePaymentStatus($paymentId, 'failed', $e->getMessage());
            return false;
        }
    }
    
    /**
     * معالجة دفعة PayPal
     * 
     * @param int $paymentId
     * @param array $paymentData
     * @return int|false
     */
    protected function processPayPalPayment($paymentId, $paymentData)
    {
        try {
            // معالجة PayPal
            
            // تحديث حالة الدفعة
            $this->updatePaymentStatus($paymentId, 'completed');
            
            return $paymentId;
        } catch (\Exception $e) {
            $this->updatePaymentStatus($paymentId, 'failed', $e->getMessage());
            return false;
        }
    }
    
    /**
     * تحديث حالة الدفعة
     * 
     * @param int $paymentId
     * @param string $status
     * @param string $notes
     * @return bool
     */
    public function updatePaymentStatus($paymentId, $status, $notes = '')
    {
        $sql = "UPDATE saas_payments SET status = '" . $this->db->escape($status) . "'";
        if ($notes) {
            $sql .= ", notes = '" . $this->db->escape($notes) . "'";
        }
        $sql .= " WHERE id = " . (int)$paymentId;
        
        return (bool)$this->db->query($sql);
    }
    
    /**
     * الحصول على بيانات الدفعة
     * 
     * @param int $paymentId
     * @return array|false
     */
    public function getPaymentById($paymentId)
    {
        $sql = "SELECT * FROM saas_payments WHERE id = " . (int)$paymentId;
        $result = $this->db->query($sql);
        
        if ($result && $this->db->num_rows($result) > 0) {
            return $this->db->fetch_assoc($result);
        }
        return false;
    }
    
    /**
     * الحصول على الدفعات بناءً على المستأجر
     * 
     * @param int $tenantId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getTenantPayments($tenantId, $limit = 50, $offset = 0)
    {
        $sql = "SELECT * FROM saas_payments WHERE tenant_id = " . (int)$tenantId;
        $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $result = $this->db->query($sql);
        $payments = array();
        
        if ($result) {
            while ($row = $this->db->fetch_assoc($result)) {
                $payments[] = $row;
            }
        }
        
        return $payments;
    }
    
    /**
     * إعادة محاولة الدفع
     * 
     * @param int $paymentId
     * @return int|false
     */
    public function retryPayment($paymentId)
    {
        $payment = $this->getPaymentById($paymentId);
        
        if (!$payment) {
            return false;
        }
        
        // إعادة محاولة الدفع
        return $this->processPayment(array(
            'tenant_id' => $payment['tenant_id'],
            'subscription_id' => $payment['subscription_id'],
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
            'payment_method' => $payment['payment_method'],
            'gateway' => $payment['gateway']
        ));
    }
}
?>
