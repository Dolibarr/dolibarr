-- ================================================
-- قاعدة بيانات نظام SaaS لـ SCCIT ERP
-- ================================================

-- ================================================
-- جدول المستأجرين (Tenants)
-- ================================================
CREATE TABLE IF NOT EXISTS saas_tenants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    country_code VARCHAR(2),
    domain_name VARCHAR(100) UNIQUE,
    database_name VARCHAR(100) UNIQUE,
    status ENUM('active', 'suspended', 'cancelled', 'pending_setup') DEFAULT 'pending_setup',
    trial_end_date DATE,
    stripe_customer_id VARCHAR(255),
    paypal_customer_id VARCHAR(255),
    total_users INT DEFAULT 0,
    storage_usage BIGINT DEFAULT 0,
    last_activity TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_domain (domain_name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- جدول المستخدمين
-- ================================================
CREATE TABLE IF NOT EXISTS saas_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'user', 'viewer') DEFAULT 'user',
    status ENUM('active', 'disabled', 'pending_verification') DEFAULT 'active',
    email_verified TINYINT DEFAULT 0,
    last_login TIMESTAMP,
    password_reset_token VARCHAR(255),
    password_reset_expires TIMESTAMP,
    two_fa_enabled TINYINT DEFAULT 0,
    two_fa_secret VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_email_per_tenant (tenant_id, email),
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- جدول الاشتراكات
-- ================================================
CREATE TABLE IF NOT EXISTS saas_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL UNIQUE,
    plan_id VARCHAR(50) NOT NULL,
    status ENUM('active', 'trial', 'frozen', 'cancelled', 'expired') DEFAULT 'trial',
    start_date TIMESTAMP,
    renewal_date DATE,
    cancellation_date DATE,
    cancellation_reason TEXT,
    auto_renew TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_plan (plan_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- جدول الفواتير
-- ================================================
CREATE TABLE IF NOT EXISTS saas_invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    subscription_id INT,
    invoice_number VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    paid_amount DECIMAL(10, 2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('issued', 'paid', 'overdue', 'cancelled') DEFAULT 'issued',
    invoice_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date DATE,
    paid_date DATE,
    payment_method VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES saas_subscriptions(id),
    UNIQUE KEY unique_invoice_number (invoice_number),
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- جدول الدفعات
-- ================================================
CREATE TABLE IF NOT EXISTS saas_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    subscription_id INT,
    invoice_id INT,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method VARCHAR(50) NOT NULL,
    gateway VARCHAR(50) NOT NULL,
    gateway_transaction_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES saas_subscriptions(id),
    FOREIGN KEY (invoice_id) REFERENCES saas_invoices(id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (status),
    INDEX idx_gateway_transaction (gateway_transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- جدول النطاقات المخصصة
-- ================================================
CREATE TABLE IF NOT EXISTS saas_domains (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    domain_name VARCHAR(255) NOT NULL UNIQUE,
    ssl_certificate LONGTEXT,
    status ENUM('pending', 'active', 'suspended', 'failed') DEFAULT 'pending',
    verified TINYINT DEFAULT 0,
    verification_token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- جدول تنبيهات الفواتير
-- ================================================
CREATE TABLE IF NOT EXISTS saas_invoice_reminders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reminder_type ENUM('first', 'second', 'final') DEFAULT 'first',
    FOREIGN KEY (invoice_id) REFERENCES saas_invoices(id) ON DELETE CASCADE,
    INDEX idx_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- جدول سجلات الأنشطة
-- ================================================
CREATE TABLE IF NOT EXISTS saas_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT,
    user_id INT,
    action VARCHAR(100),
    entity_type VARCHAR(50),
    entity_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES saas_users(id) ON DELETE SET NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- جدول استخدام الموارد
-- ================================================
CREATE TABLE IF NOT EXISTS saas_resource_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL UNIQUE,
    total_users INT DEFAULT 0,
    active_users INT DEFAULT 0,
    total_invoices INT DEFAULT 0,
    total_documents INT DEFAULT 0,
    storage_bytes BIGINT DEFAULT 0,
    api_calls_month INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- جدول إعدادات المستأجر
-- ================================================
CREATE TABLE IF NOT EXISTS saas_tenant_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value LONGTEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_key (tenant_id, setting_key),
    INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- جدول جلسات المستخدمين
-- ================================================
CREATE TABLE IF NOT EXISTS saas_sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    tenant_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES saas_users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- جدول سجلات الأخطاء
-- ================================================
CREATE TABLE IF NOT EXISTS saas_error_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT,
    user_id INT,
    error_type VARCHAR(100),
    error_message TEXT,
    error_code VARCHAR(20),
    stack_trace LONGTEXT,
    request_data LONGTEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES saas_users(id) ON DELETE SET NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_error_type (error_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- جدول نسخ احتياطية
-- ================================================
CREATE TABLE IF NOT EXISTS saas_backups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    backup_type ENUM('full', 'incremental', 'manual') DEFAULT 'incremental',
    backup_size BIGINT,
    backup_location VARCHAR(255),
    status ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP,
    retention_days INT DEFAULT 30,
    FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء الفهارس الإضافية للأداء
CREATE INDEX idx_saas_subscriptions_renewal ON saas_subscriptions(renewal_date);
CREATE INDEX idx_saas_invoices_due_date ON saas_invoices(due_date);
CREATE INDEX idx_saas_invoices_status_tenant ON saas_invoices(status, tenant_id);
CREATE INDEX idx_saas_payments_status_tenant ON saas_payments(status, tenant_id);
CREATE INDEX idx_saas_users_email ON saas_users(email);
CREATE INDEX idx_saas_activity_logs_action_date ON saas_activity_logs(action, created_at);
