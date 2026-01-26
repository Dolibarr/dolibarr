# SCCIT ERP SaaS - دليل التطوير والمساهمة

## البيئة الإنمائية

### المتطلبات
- PHP >= 8.0
- MySQL >= 5.7
- Composer
- Git

### التثبيت

```bash
# استنساخ المستودع
git clone https://github.com/SCCIT/erp-saas.git
cd erp-saas

# إنشاء فرع تطوير
git checkout -b develop

# تثبيت المكتبات
composer install

# إنشاء ملف الإعدادات
cp saas/.env.example saas/.env

# تعديل الإعدادات البيئية
nano saas/.env
```

### إعداد قاعدة البيانات

```bash
# إنشاء قاعدة البيانات
mysql -u root -p -e "CREATE DATABASE sccit_erp_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# تشغيل الهجرات
php saas/migrations/migrate.php
```

## معايير الكود

### معايير PHP

```bash
# فحص المعايير
phpcs saas/

# تصحيح الأخطاء تلقائياً
phpcbf saas/
```

### تحليل ثابت

```bash
# تشغيل PHPStan
phpstan analyze saas/
```

### الاختبارات

```bash
# تشغيل جميع الاختبارات
phpunit

# اختبار ملف محدد
phpunit test/phpunit/SaasTestSuite.php

# اختبار مع تغطية الكود
phpunit --coverage-html coverage/
```

## البنية والتصميم

### Pattern: Singleton
استخدام Singleton في `SaasBootstrap` لضمان وجود نسخة واحدة فقط.

### Pattern: Manager
كل مكون له مدير مخصص (TenantManager, SubscriptionManager, إلخ).

### Pattern: Repository
استخدام فئات منفصلة للوصول إلى البيانات.

## المتغيرات والترجمات

### دعم اللغات
- العربية (الافتراضية)
- الإنجليزية
- الفرنسية

### إضافة لغة جديدة

```bash
mkdir -p saas/lang/new_lang
cp saas/lang/en_US/* saas/lang/new_lang/
nano saas/lang/new_lang/lang.php
```

## الأداء والتحسينات

### الفهرسة
- فهارس على جداول البحث الكثيفة
- فهارس مركبة للاستعلامات المشتركة

### التخزين المؤقت
- تخزين مؤقت لبيانات المستأجرين
- تخزين مؤقت لبيانات الاشتراكات

### قوائم الانتظار
- معالجة الفواتير في الخلفية
- معالجة رسائل البريد الإلكتروني غير المتزامنة

## الأمان

### رموز المصادقة
```php
// استخدام JWT للعلامات الآمنة
$token = JWT::encode(['user_id' => $userId], $secret, 'HS256');
```

### التشفير
```php
// تشفير البيانات الحساسة
$encrypted = openssl_encrypt($data, 'AES-256-CBC', $key);
```

## نماذج الطلب

### شكل الطلب المقبول

```php
{
    "tenant_id": 1,
    "plan_id": "professional",
    "auto_renew": true
}
```

### شكل الاستجابة

```php
{
    "success": true,
    "data": {
        "subscription_id": 1,
        "plan_id": "professional",
        "status": "active"
    },
    "timestamp": "2026-01-26T10:00:00+00:00"
}
```

## رفع التغييرات

### خطوات رفع PR

1. Fork المستودع
2. إنشاء فرع جديد
   ```bash
   git checkout -b feature/my-feature
   ```

3. إجراء التغييرات واختبارها
   ```bash
   git add .
   git commit -m "feat: إضافة ميزة جديدة"
   ```

4. Push للفرع
   ```bash
   git push origin feature/my-feature
   ```

5. فتح Pull Request على GitHub

### معايير الرسالة

```
<type>: <subject>

<body>

<footer>
```

الأنواع المدعومة:
- `feat`: ميزة جديدة
- `fix`: إصلاح خطأ
- `docs`: توثيق
- `style`: تنسيق الكود
- `refactor`: إعادة هيكلة
- `test`: إضافة اختبارات
- `chore`: صيانة

## التوثيق

### طريقة التوثيق

استخدام PHPDoc:
```php
/**
 * الوصف المختصر
 * 
 * الوصف التفصيلي (اختياري)
 * 
 * @param int $id معرّف العنصر
 * @param string $name اسم العنصر
 * @return array البيانات المرجعة
 * @throws Exception عند حدوث خطأ
 */
public function getElement($id, $name)
{
    // الكود هنا
}
```

## التغييرات المستقبلية

### ميزات مخطط لها
- [ ] تطبيق ويب React
- [ ] تطبيق الهاتف المحمول React Native
- [ ] نظام تنبيهات متقدم
- [ ] تقارير ذكية بالذكاء الاصطناعي

## الدعم والمساعدة

- **Discord**: [رابط السيرفر](https://discord.gg/sccit-erp)
- **Forum**: [ملتقى SCCIT ERP](https://www.sccit-erp.com/forum)
- **Wiki**: [موسوعة SCCIT ERP](https://wiki.sccit-erp.com)

## الترخيص

جميع المساهمات يجب أن تتوافق مع GNU GPL v3.0

---

شكراً لمساهمتك في SCCIT ERP SaaS!
