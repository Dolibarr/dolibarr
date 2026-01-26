<?php
/**
 * SCCIT ERP SaaS - ملف التكوين الرئيسي
 * 
 * يحتوي على جميع إعدادات نظام SaaS
 */

return array(
    /**
     * إعدادات قاعدة البيانات
     */
    'database' => array(
        'driver' => 'mysql',
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: 3306,
        'name' => getenv('DB_NAME') ?: 'sccit_erp_saas',
        'user' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci'
    ),
    
    /**
     * إعدادات التطبيق
     */
    'app' => array(
        'name' => 'SCCIT ERP SaaS',
        'version' => '1.0.0',
        'environment' => getenv('APP_ENV') ?: 'production',
        'debug' => getenv('APP_DEBUG') === 'true',
        'timezone' => 'UTC',
        'locale' => 'ar_SA'
    ),
    
    /**
     * إعدادات النطاق
     */
    'domain' => array(
        'main' => getenv('MAIN_DOMAIN') ?: 'saas.sccit-erp.com',
        'protocol' => getenv('APP_PROTOCOL') ?: 'https',
        'admin_subdomain' => 'admin'
    ),
    
    /**
     * إعدادات المصادقة
     */
    'auth' => array(
        'session_timeout' => 3600,
        'password_min_length' => 8,
        'password_require_uppercase' => true,
        'password_require_numbers' => true,
        'password_require_special' => true,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 دقيقة
        'two_factor_enabled' => true,
        'jwt_secret' => getenv('JWT_SECRET') ?: 'your-secret-key-here',
        'jwt_expiry' => 86400 // 24 ساعة
    ),
    
    /**
     * إعدادات الاشتراكات والفواتير
     */
    'billing' => array(
        'trial_days' => 14,
        'invoice_prefix' => 'INV',
        'invoice_due_days' => 30,
        'currency' => 'USD',
        'tax_rate' => 0.0,
        'invoice_from_email' => getenv('INVOICE_FROM_EMAIL') ?: 'invoices@sccit-erp.com',
        'invoice_from_name' => 'SCCIT ERP SaaS'
    ),
    
    /**
     * إعدادات الدفع
     */
    'payment' => array(
        'gateways' => array('stripe', 'paypal'),
        'stripe' => array(
            'enabled' => true,
            'secret_key' => getenv('STRIPE_SECRET_KEY') ?: '',
            'publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY') ?: '',
            'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: ''
        ),
        'paypal' => array(
            'enabled' => true,
            'client_id' => getenv('PAYPAL_CLIENT_ID') ?: '',
            'client_secret' => getenv('PAYPAL_CLIENT_SECRET') ?: '',
            'mode' => getenv('PAYPAL_MODE') ?: 'sandbox'
        )
    ),
    
    /**
     * إعدادات البريد الإلكتروني
     */
    'mail' => array(
        'driver' => getenv('MAIL_DRIVER') ?: 'smtp',
        'host' => getenv('MAIL_HOST') ?: 'smtp.mailtrap.io',
        'port' => getenv('MAIL_PORT') ?: 465,
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'from' => array(
            'address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@sccit-erp.com',
            'name' => 'SCCIT ERP SaaS'
        ),
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls'
    ),
    
    /**
     * إعدادات التخزين
     */
    'storage' => array(
        'driver' => getenv('STORAGE_DRIVER') ?: 'local',
        'local' => array(
            'path' => __DIR__ . '/../../storage'
        ),
        's3' => array(
            'key' => getenv('AWS_ACCESS_KEY_ID') ?: '',
            'secret' => getenv('AWS_SECRET_ACCESS_KEY') ?: '',
            'region' => getenv('AWS_DEFAULT_REGION') ?: 'us-east-1',
            'bucket' => getenv('AWS_BUCKET') ?: ''
        ),
        'max_file_size' => 104857600, // 100 MB
        'allowed_extensions' => array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'png', 'gif')
    ),
    
    /**
     * إعدادات API
     */
    'api' => array(
        'enabled' => true,
        'version' => 'v1',
        'rate_limit' => 1000, // طلبات في الساعة
        'api_key_prefix' => 'sk_',
        'cors_enabled' => true,
        'cors_origins' => array('*')
    ),
    
    /**
     * إعدادات السجلات
     */
    'logging' => array(
        'level' => getenv('LOG_LEVEL') ?: 'info',
        'path' => __DIR__ . '/../../storage/logs',
        'max_size' => 10485760, // 10 MB
        'max_files' => 10,
        'channels' => array(
            'activity' => true,
            'error' => true,
            'payment' => true,
            'api' => true
        )
    ),
    
    /**
     * إعدادات النسخ الاحتياطية
     */
    'backup' => array(
        'enabled' => true,
        'schedule' => 'daily', // daily, weekly, monthly
        'retention_days' => 30,
        'backup_to_s3' => true,
        'backup_path' => __DIR__ . '/../../storage/backups'
    ),
    
    /**
     * إعدادات الأمان
     */
    'security' => array(
        'https_only' => true,
        'hsts_enabled' => true,
        'csrf_protection' => true,
        'rate_limiting' => true,
        'ip_whitelist' => array(),
        'ip_blacklist' => array()
    ),
    
    /**
     * إعدادات الخطط
     */
    'plans' => array(
        'starter' => array(
            'name' => 'Starter',
            'price' => 29.99,
            'currency' => 'USD',
            'billing_period' => 'monthly',
            'max_users' => 3,
            'max_invoices' => 100,
            'max_documents' => 500,
            'storage_gb' => 5,
            'api_calls_per_month' => 10000,
            'features' => array(
                'basic_crm',
                'invoicing',
                'basic_reports',
                'email_support'
            )
        ),
        'professional' => array(
            'name' => 'Professional',
            'price' => 79.99,
            'currency' => 'USD',
            'billing_period' => 'monthly',
            'max_users' => 10,
            'max_invoices' => 1000,
            'max_documents' => 5000,
            'storage_gb' => 50,
            'api_calls_per_month' => 100000,
            'features' => array(
                'advanced_crm',
                'invoicing',
                'advanced_reports',
                'inventory',
                'workflows',
                'priority_support'
            )
        ),
        'enterprise' => array(
            'name' => 'Enterprise',
            'price' => 299.99,
            'currency' => 'USD',
            'billing_period' => 'monthly',
            'max_users' => -1,
            'max_invoices' => -1,
            'max_documents' => -1,
            'storage_gb' => 500,
            'api_calls_per_month' => -1,
            'features' => array(
                'all_features',
                'api_access',
                'white_label',
                'custom_integrations',
                'phone_support',
                'dedicated_account_manager'
            )
        )
    )
);
?>
