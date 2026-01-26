# SCCIT ERP SaaS - نظام SaaS متعدد المستأجرين

تحويل شامل لـ SCCIT ERP ليصبح منصة SaaS (Software as a Service) متعددة المستأجرين مع نظام الاشتراكات والفواتير والدفع المتكامل.

## المميزات الرئيسية

### 1. نظام متعدد المستأجرين (Multi-Tenancy)
- **عزل البيانات الكامل** بين كل مستأجر
- **قاعدة بيانات مشتركة** مع عزل على مستوى التطبيق
- **إدارة سهلة** للمستأجرين من لوحة تحكم موحدة
- **أداء محسّن** من خلال فهارس وتحسينات الاستعلامات

### 2. نظام الاشتراكات والفواتير
- **خطط متعددة**: Starter, Professional, Enterprise
- **فواتير تلقائية** عند كل دورة اشتراك
- **إدارة الدفعات** مع تتبع شامل
- **إعادة محاولة تلقائية** للدفعات الفاشلة
- **تقارير مفصلة** عن الإيرادات والاشتراكات

### 3. نظام المصادقة والتفويض
- **مصادقة آمنة** باستخدام bcrypt
- **تفويض قائم على الأدوار** (Admin, Manager, User, Viewer)
- **المصادقة الثنائية** (2FA)
- **إدارة الجلسات** مع timeout آمن
- **إعادة تعيين كلمة المرور** الآمنة

### 4. بوابات الدفع المتكاملة
- **Stripe**: لمعالجة بطاقات الائتمان بأمان
- **PayPal**: للدفع عبر حسابات PayPal
- **معالجة آمنة** لبيانات الدفع
- **معالجة الأخطاء** والعودة إلى الحالة السابقة
- **ملخص دفع شامل** للمستأجر

### 5. النطاقات المخصصة
- **نطاقات فرعية ديناميكية**: `tenant.saas.sccit-erp.com`
- **نطاقات مخصصة**: `custom.domain.com`
- **شهادات SSL** تلقائية
- **توجيه سلس** للنطاقات المختلفة

### 6. قابلية التوسع والأداء
- **تصميم سلمي**: يدعم ملايين المستأجرين
- **فهرسة محسّنة**: لاستعلامات سريعة
- **تخزين مؤقت**: لتحسين الأداء
- **قوائم انتظار الخلفية**: لمعالجة المهام الثقيلة
- **نسخ احتياطية تلقائية**: لحفظ البيانات

## بنية المشروع

```
/saas
├── core/
│   ├── TenantManager.php       # إدارة المستأجرين
│   ├── SaasBootstrap.php       # ملف البدء الرئيسي
│   └── README.md
├── auth/
│   ├── AuthenticationManager.php # إدارة المصادقة
│   └── README.md
├── billing/
│   ├── SubscriptionManager.php    # إدارة الاشتراكات
│   ├── InvoiceManager.php         # إدارة الفواتير
│   └── README.md
├── payments/
│   ├── PaymentProcessor.php       # معالج الدفع
│   └── README.md
├── domains/
│   ├── DomainManager.php          # إدارة النطاقات
│   └── README.md
├── api/
│   ├── ApiEndpoints.php           # نقاط نهاية API
│   └── README.md
├── admin/
│   ├── DashboardController.php    # لوحة التحكم
│   └── README.md
├── migrations/
│   ├── 001_create_saas_tables.sql # قاعدة البيانات
│   └── migrate.php
├── config/
│   ├── saas.config.php           # الإعدادات
│   └── README.md
└── storage/
    ├── logs/                      # السجلات
    ├── backups/                   # النسخ الاحتياطية
    └── uploads/                   # الملفات المرفوعة
```

## التثبيت والإعداد

### 1. المتطلبات
- PHP >= 8.0
- MySQL >= 5.7 أو MariaDB >= 10.2
- Composer
- OpenSSL

### 2. التثبيت

```bash
# استنساخ المستودع
git clone https://github.com/SCCIT/erp-saas.git
cd erp-saas

# تثبيت المكتبات
composer install

# نسخ ملف الإعدادات
cp saas/.env.example saas/.env

# تحرير الإعدادات
nano saas/.env
```

### 3. إعداد قاعدة البيانات

```bash
# إنشاء قاعدة البيانات
mysql -u root -p -e "CREATE DATABASE sccit_erp_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# تشغيل الهجرات
php saas/migrations/migrate.php
```

### 4. إعداد النطاق

```bash
# إضافة النطاق الرئيسي إلى hosts
echo "127.0.0.1 saas.sccit-erp.local" >> /etc/hosts
echo "127.0.0.1 *.saas.sccit-erp.local" >> /etc/hosts
```

## الاستخدام

### إنشاء مستأجر جديد

```php
<?php
require 'saas/core/SaasBootstrap.php';

$saas = \Dolibarr\Saas\Core\SaasBootstrap::getInstance();
$saas->initialize($db, $config);

$tenantManager = $saas->getTenantManager();
$tenantId = $tenantManager->createTenant(array(
    'company_name' => 'شركة XYZ',
    'email' => 'info@xyz.com',
    'domain_name' => 'xyz',
    'phone' => '+966501234567',
    'address' => 'الرياض',
    'city' => 'الرياض',
    'country_code' => 'SA'
));
?>
```

### تسجيل مستخدم جديد

```php
<?php
$authManager = $saas->getAuthManager();
$userId = $authManager->registerUser(array(
    'tenant_id' => $tenantId,
    'first_name' => 'محمد',
    'last_name' => 'أحمد',
    'email' => 'user@example.com',
    'password' => 'SecurePassword123!',
    'role' => 'admin'
));
?>
```

### إنشاء اشتراك

```php
<?php
$subscriptionManager = $saas->getSubscriptionManager();
$subscriptionId = $subscriptionManager->createSubscription(array(
    'tenant_id' => $tenantId,
    'plan_id' => 'professional',
    'auto_renew' => true
));

// إنشاء فاتورة
$invoiceManager = $saas->getInvoiceManager();
$invoiceId = $invoiceManager->createSubscriptionInvoice(
    $tenantId,
    $subscriptionId,
    79.99,
    'USD'
);
?>
```

### معالجة الدفع

```php
<?php
$paymentProcessor = $saas->getPaymentProcessor();
$paymentId = $paymentProcessor->processPayment(array(
    'tenant_id' => $tenantId,
    'subscription_id' => $subscriptionId,
    'amount' => 79.99,
    'currency' => 'USD',
    'payment_method' => 'card',
    'gateway' => 'stripe',
    'invoice_id' => $invoiceId
));
?>
```

## واجهات API

### إنشاء مستأجر جديد

```http
POST /api/v1/tenants/create
Content-Type: application/json

{
  "company_name": "شركة ABC",
  "email": "admin@abc.com",
  "domain_name": "abc",
  "phone": "+966501234567"
}
```

### الحصول على الخطط المتاحة

```http
GET /api/v1/subscriptions/plans
```

### معالجة الدفع

```http
POST /api/v1/payments/process
Content-Type: application/json
Authorization: Bearer YOUR_API_TOKEN

{
  "tenant_id": 1,
  "subscription_id": 1,
  "amount": 79.99,
  "currency": "USD",
  "payment_method": "card",
  "gateway": "stripe"
}
```

## جداول قاعدة البيانات

### saas_tenants
- المستأجرون الرئيسيون
- معلومات الشركة والنطاق

### saas_users
- مستخدمو كل مستأجر
- بيانات المصادقة والأدوار

### saas_subscriptions
- الاشتراكات النشطة
- معلومات الخطة والتجديد

### saas_invoices
- الفواتير المصدرة
- حالة الدفع والمبالغ

### saas_payments
- جميع معاملات الدفع
- معلومات البوابة وحالة الدفع

### saas_domains
- النطاقات المخصصة
- شهادات SSL

### saas_activity_logs
- سجل جميع الأنشطة
- المراجعة والامتثال

### saas_resource_usage
- تتبع استخدام الموارد
- حدود الخطة

## الأمان

### مميزات الأمان المدمجة
- **تشفير كلمات المرور**: bcrypt مع cost=12
- **HTTPS إجباري**: في بيئة الإنتاج
- **CSRF Protection**: حماية ضد الهجمات
- **Rate Limiting**: حماية ضد الهجمات بالقوة
- **SQL Injection Protection**: استخدام Prepared Statements
- **XSS Protection**: تنظيف الإدخال والإخراج
- **جدار حماية (WAF)**: لحماية الطبقة الأولى

## السجلات والمراجعة

### سجلات النشاط
```php
$activityLog = array(
    'tenant_id' => $tenantId,
    'user_id' => $userId,
    'action' => 'invoice_created',
    'entity_type' => 'invoice',
    'entity_id' => $invoiceId,
    'description' => 'تم إنشاء فاتورة جديدة',
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'timestamp' => date('Y-m-d H:i:s')
);
```

## النسخ الاحتياطية

### نسخ احتياطية تلقائية
- **يومية**: نسخة احتياطية كاملة كل يوم
- **أسبوعية**: نسخة احتياطية شاملة
- **شهرية**: احتفظ بنسخ من 30 يوماً

### الاستعادة

```bash
php saas/backup/restore.php --backup-id=123
```

## التكامل مع Stripe

### الإعداد

1. الحصول على مفاتيح Stripe من https://dashboard.stripe.com
2. إضافة المفاتيح إلى ملف `.env`
3. إعداد webhooks في لوحة Stripe

### معالجة الدفع
```php
$paymentProcessor = $saas->getPaymentProcessor();
$paymentId = $paymentProcessor->processPayment(array(
    'tenant_id' => $tenantId,
    'subscription_id' => $subscriptionId,
    'amount' => 79.99,
    'gateway' => 'stripe'
));
```

## التكامل مع PayPal

### الإعداد

1. الحصول على بيانات PayPal
2. إضافة المفاتيح إلى ملف `.env`
3. إعداد webhooks في لوحة PayPal

## المراقبة والتقارير

### لوحة التحكم
- إحصائيات الإيرادات
- عدد المستأجرين النشطين
- استخدام الموارد
- سجل الأنشطة

### التقارير
- تقارير الإيرادات حسب الفترة
- تقارير الاشتراكات
- تقارير الدفعات الفاشلة
- تقارير استخدام الموارد

## الصيانة والتطوير

### الاختبارات

```bash
# تشغيل الاختبارات
phpunit

# فحص الكود
phpstan analyze

# فحص معايير الكود
phpcs
```

### التحديثات

```bash
# سحب التحديثات
git pull origin develop

# تحديث المكتبات
composer update

# تشغيل الهجرات
php saas/migrations/migrate.php
```

## المساهمة

نرحب بالمساهمات! يرجى اتباع الخطوات التالية:

1. Fork المستودع
2. إنشاء فرع جديد (`git checkout -b feature/amazing-feature`)
3. Commit التغييرات (`git commit -m 'Add amazing feature'`)
4. Push للفرع (`git push origin feature/amazing-feature`)
5. فتح Pull Request

## الترخيص

هذا المشروع مرخص تحت GNU General Public License v3.0 - انظر ملف [COPYING](../../COPYING) للتفاصيل.

## الدعم

- **الملتقى**: https://www.sccit-erp.com/forum
- **التوثيق**: https://docs.sccit-erp.com
- **التقارير**: https://github.com/SCCIT/erp-saas/issues

## الخارطة الطريقية

### المرحلة الأولى (مكتملة)
- ✅ نظام متعدد المستأجرين
- ✅ نظام المصادقة والتفويض
- ✅ إدارة الاشتراكات
- ✅ إدارة الفواتير
- ✅ معالج الدفع

### المرحلة الثانية (جارية)
- 🔄 تطبيق ويب لوحة التحكم
- 🔄 تطبيق الهاتف المحمول
- 🔄 تكاملات إضافية

### المرحلة الثالثة (مخطط)
- ⏳ التقارير المتقدمة
- ⏳ الذكاء الاصطناعي والتعلم الآلي
- ⏳ الأتمتة المتقدمة

## الشكر والتقدير

شكر خاص لمجتمع Dolibarr على الدعم والتعاون المستمر.

---

**آخر تحديث**: يناير 2026
**الإصدار**: 1.0.0
**الحالة**: جاهز للإنتاج (Production Ready)
