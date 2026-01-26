<?php
/**
 * InvoiceManager - إدارة الفواتير
 * 
 * ينشئ ويدير فواتير الاشتراك
 */

namespace SCCIT\ERP\Saas\Billing;

class InvoiceManager
{
    protected $db;
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
    }
    
    /**
     * إنشاء فاتورة اشتراك
     * 
     * @param int $tenantId
     * @param int $subscriptionId
     * @param float $amount
     * @param string $currency
     * @return int|false
     */
    public function createSubscriptionInvoice($tenantId, $subscriptionId, $amount, $currency = 'USD')
    {
        $invoiceNumber = $this->generateInvoiceNumber($tenantId);
        
        $sql = "INSERT INTO saas_invoices (";
        $sql .= "tenant_id, subscription_id, invoice_number, amount, currency, ";
        $sql .= "status, invoice_date, due_date, created_at";
        $sql .= ") VALUES (";
        $sql .= (int)$tenantId . ", ";
        $sql .= (int)$subscriptionId . ", ";
        $sql .= "'" . $this->db->escape($invoiceNumber) . "', ";
        $sql .= (float)$amount . ", ";
        $sql .= "'" . $this->db->escape($currency) . "', ";
        $sql .= "'issued', NOW(), ";
        $sql .= "'" . date('Y-m-d', strtotime('+30 days')) . "', NOW())";
        
        if ($this->db->query($sql)) {
            return $this->db->last_insert_id();
        }
        return false;
    }
    
    /**
     * توليد رقم فاتورة فريد
     * 
     * @param int $tenantId
     * @return string
     */
    protected function generateInvoiceNumber($tenantId)
    {
        $year = date('Y');
        $month = date('m');
        
        $sql = "SELECT COUNT(*) as count FROM saas_invoices WHERE tenant_id = " . (int)$tenantId;
        $sql .= " AND YEAR(created_at) = " . (int)$year;
        
        $result = $this->db->query($sql);
        $row = $this->db->fetch_assoc($result);
        $sequenceNumber = (int)$row['count'] + 1;
        
        return 'INV-' . $tenantId . '-' . $year . '-' . str_pad($sequenceNumber, 5, '0', STR_PAD_LEFT);
    }
    
    /**
     * الحصول على الفاتورة
     * 
     * @param int $invoiceId
     * @return array|false
     */
    public function getInvoice($invoiceId)
    {
        $sql = "SELECT * FROM saas_invoices WHERE id = " . (int)$invoiceId;
        $result = $this->db->query($sql);
        
        if ($result && $this->db->num_rows($result) > 0) {
            return $this->db->fetch_assoc($result);
        }
        return false;
    }
    
    /**
     * الحصول على فواتير المستأجر
     * 
     * @param int $tenantId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getTenantInvoices($tenantId, $limit = 50, $offset = 0)
    {
        $sql = "SELECT * FROM saas_invoices WHERE tenant_id = " . (int)$tenantId;
        $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $result = $this->db->query($sql);
        $invoices = array();
        
        if ($result) {
            while ($row = $this->db->fetch_assoc($result)) {
                $invoices[] = $row;
            }
        }
        
        return $invoices;
    }
    
    /**
     * تحديث حالة الفاتورة
     * 
     * @param int $invoiceId
     * @param string $status
     * @return bool
     */
    public function updateInvoiceStatus($invoiceId, $status)
    {
        $sql = "UPDATE saas_invoices SET status = '" . $this->db->escape($status) . "' WHERE id = " . (int)$invoiceId;
        return (bool)$this->db->query($sql);
    }
    
    /**
     * وضع علامة على الفاتورة كمدفوعة
     * 
     * @param int $invoiceId
     * @param float $paidAmount
     * @param string $paymentMethod
     * @return bool
     */
    public function markAsPaid($invoiceId, $paidAmount, $paymentMethod = '')
    {
        $sql = "UPDATE saas_invoices SET status = 'paid', paid_amount = " . (float)$paidAmount;
        if ($paymentMethod) {
            $sql .= ", payment_method = '" . $this->db->escape($paymentMethod) . "'";
        }
        $sql .= ", paid_date = NOW() WHERE id = " . (int)$invoiceId;
        
        return (bool)$this->db->query($sql);
    }
    
    /**
     * حساب الفواتير المتأخرة
     * 
     * @param int $tenantId
     * @return array
     */
    public function getOverdueInvoices($tenantId)
    {
        $sql = "SELECT * FROM saas_invoices WHERE tenant_id = " . (int)$tenantId;
        $sql .= " AND status NOT IN ('paid', 'cancelled') AND due_date < NOW()";
        
        $result = $this->db->query($sql);
        $invoices = array();
        
        if ($result) {
            while ($row = $this->db->fetch_assoc($result)) {
                $invoices[] = $row;
            }
        }
        
        return $invoices;
    }
    
    /**
     * إرسال تذكير الفاتورة
     * 
     * @param int $invoiceId
     * @return bool
     */
    public function sendReminderEmail($invoiceId)
    {
        $invoice = $this->getInvoice($invoiceId);
        
        if (!$invoice) {
            return false;
        }
        
        // الحصول على بيانات المستأجر
        $tenantSql = "SELECT * FROM saas_tenants WHERE id = " . (int)$invoice['tenant_id'];
        $tenantResult = $this->db->query($tenantSql);
        
        if (!$tenantResult || $this->db->num_rows($tenantResult) === 0) {
            return false;
        }
        
        $tenant = $this->db->fetch_assoc($tenantResult);
        
        // إرسال البريد الإلكتروني (يمكن استخدام مكتبة بريد)
        $subject = 'Invoice Reminder - ' . $invoice['invoice_number'];
        $message = 'Dear ' . $tenant['company_name'] . ',\n\n';
        $message .= 'This is a reminder that your invoice ' . $invoice['invoice_number'] . ' is due.\n';
        $message .= 'Amount: ' . $invoice['amount'] . ' ' . $invoice['currency'] . '\n';
        $message .= 'Due Date: ' . $invoice['due_date'] . '\n\n';
        $message .= 'Please pay as soon as possible.';
        
        // توقيع البريد في السجلات
        $sql = "INSERT INTO saas_invoice_reminders (invoice_id, sent_at) VALUES ";
        $sql .= "(" . (int)$invoiceId . ", NOW())";
        
        return (bool)$this->db->query($sql);
    }
}
?>
