<?php
/**
 * Migration Script - سكريبت الهجرة
 * 
 * ينفذ جميع الهجرات المطلوبة لإعداد قاعدة البيانات
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$migrationDir = __DIR__;
$sqlFile = $migrationDir . '/001_create_saas_tables.sql';

// الاتصال بقاعدة البيانات
$config = require __DIR__ . '/../config/saas.config.php';

try {
    // اتصال MySQL
    $dsn = 'mysql:host=' . $config['database']['host'] . 
           ';port=' . $config['database']['port'];
    
    $pdo = new PDO($dsn, 
        $config['database']['user'], 
        $config['database']['password']
    );
    
    echo "✓ متصل بخادم MySQL\n";
    
    // إنشاء قاعدة البيانات إن لم تكن موجودة
    $dbName = $config['database']['name'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . $dbName . "` 
               CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ تم إنشاء/التحقق من قاعدة البيانات: " . $dbName . "\n";
    
    // اختيار قاعدة البيانات
    $pdo->exec("USE `" . $dbName . "`");
    
    // قراءة ملف SQL
    if (!file_exists($sqlFile)) {
        throw new Exception("ملف الهجرة غير موجود: " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    
    // تقسيم الاستعلامات
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    $count = 0;
    foreach ($queries as $query) {
        if (!empty($query)) {
            try {
                $pdo->exec($query);
                $count++;
            } catch (PDOException $e) {
                // تجاهل الأخطاء إذا كانت الجداول موجودة بالفعل
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠ تحذير: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✓ تم تنفيذ " . $count . " استعلام SQL\n";
    
    // إضافة بيانات افتراضية
    addDefaultData($pdo);
    
    echo "\n✓ نجحت الهجرة! قاعدة البيانات جاهزة للاستخدام\n";
    
} catch (Exception $e) {
    echo "✗ خطأ: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * إضافة بيانات افتراضية
 */
function addDefaultData($pdo)
{
    // التحقق من وجود بيانات موجودة
    $result = $pdo->query("SELECT COUNT(*) as count FROM saas_tenants");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    
    if ($row['count'] > 0) {
        echo "ℹ قاعدة البيانات تحتوي على بيانات موجودة بالفعل، لن نضيف بيانات افتراضية\n";
        return;
    }
    
    echo "• إضافة بيانات افتراضية...\n";
    
    // إضافة مستأجر اختباري
    $adminEmail = 'admin@sccit-erp.local';
    $adminPassword = password_hash('Admin123!', PASSWORD_BCRYPT, array('cost' => 12));
    
    // التحقق من عدم وجود المستأجر بالفعل
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM saas_tenants WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] === 0) {
        // إضافة مستأجر اختباري
        $stmt = $pdo->prepare("
            INSERT INTO saas_tenants (
                company_name, email, domain_name, status, created_at
            ) VALUES (?, ?, ?, 'active', NOW())
        ");
        
        $stmt->execute([
            'SCCIT ERP Demo',
            $adminEmail,
            'demo'
        ]);
        
        $tenantId = $pdo->lastInsertId();
        echo "  ✓ تم إنشاء مستأجر اختباري (ID: " . $tenantId . ")\n";
        
        // إضافة مستخدم إداري
        $stmt = $pdo->prepare("
            INSERT INTO saas_users (
                tenant_id, first_name, last_name, email, password_hash, 
                role, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $tenantId,
            'مسؤول',
            'النظام',
            $adminEmail,
            $adminPassword,
            'admin',
            'active'
        ]);
        
        echo "  ✓ تم إنشاء مستخدم إداري\n";
        
        // إضافة اشتراك تجريبي
        $stmt = $pdo->prepare("
            INSERT INTO saas_subscriptions (
                tenant_id, plan_id, status, start_date, auto_renew, created_at
            ) VALUES (?, ?, ?, NOW(), ?, NOW())
        ");
        
        $stmt->execute([
            $tenantId,
            'professional',
            'trial',
            1
        ]);
        
        echo "  ✓ تم إنشاء اشتراك تجريبي\n";
        
        // إضافة جدول استخدام الموارد
        $stmt = $pdo->prepare("
            INSERT INTO saas_resource_usage (
                tenant_id, total_users, active_users
            ) VALUES (?, ?, ?)
        ");
        
        $stmt->execute([$tenantId, 1, 1]);
        
        echo "  ✓ تم إنشاء جدول استخدام الموارد\n";
        
        echo "\n📧 بيانات الدخول التجريبية:\n";
        echo "  البريد الإلكتروني: " . $adminEmail . "\n";
        echo "  كلمة المرور: Admin123!\n";
        echo "  النطاق: demo.saas.sccit-erp.local\n";
    }
}
?>
