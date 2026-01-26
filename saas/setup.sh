#!/bin/bash
#
# SCCIT ERP SaaS - سكريبت البدء السريع
# يساعد في إعداد البيئة والبدء بسرعة

set -e

echo "╔════════════════════════════════════════════════════════╗"
echo "║     SCCIT ERP SaaS - نظام إعداد البيئة والبدء           ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""

# التحقق من المتطلبات
echo "📋 التحقق من المتطلبات..."

# التحقق من PHP
if ! command -v php &> /dev/null; then
    echo "✗ PHP غير مثبت"
    exit 1
fi
echo "✓ PHP: $(php -v | head -n 1)"

# التحقق من MySQL
if ! command -v mysql &> /dev/null; then
    echo "⚠ MySQL غير مثبت (اختياري - يمكن استخدام Docker)"
fi

# التحقق من Composer
if ! command -v composer &> /dev/null; then
    echo "✗ Composer غير مثبت"
    exit 1
fi
echo "✓ Composer مثبت"

# التحقق من Git
if ! command -v git &> /dev/null; then
    echo "⚠ Git غير مثبت (اختياري)"
fi

echo ""
echo "🔧 إعداد البيئة..."

# نسخ ملف الإعدادات
if [ ! -f "saas/.env" ]; then
    echo "  • نسخ ملف الإعدادات..."
    cp saas/.env.example saas/.env
    echo "  ✓ تم إنشاء saas/.env"
else
    echo "  • ملف الإعدادات موجود بالفعل"
fi

# تثبيت المكتبات
echo "  • تثبيت المكتبات (قد يستغرق دقائق)..."
composer install --prefer-dist

echo ""
echo "🐳 هل تريد استخدام Docker؟ [y/n]"
read -r use_docker

if [ "$use_docker" = "y" ] || [ "$use_docker" = "Y" ]; then
    echo ""
    echo "📦 بدء خدمات Docker..."
    
    # التحقق من Docker
    if ! command -v docker &> /dev/null; then
        echo "✗ Docker غير مثبت"
        exit 1
    fi
    
    # بدء الخدمات
    docker-compose up -d
    
    echo "✓ تم بدء خدمات Docker"
    echo ""
    echo "الخدمات المتاحة:"
    echo "  • تطبيق الويب: https://saas.sccit-erp.local"
    echo "  • phpMyAdmin: http://localhost:8081"
    echo "  • MailHog: http://localhost:8025"
    
    # الانتظار قليلاً لتشغيل الخدمات
    sleep 3
    
    # تشغيل الهجرات
    echo ""
    echo "💾 تشغيل هجرات قاعدة البيانات..."
    docker-compose exec -T php php saas/migrations/migrate.php
    
else
    echo ""
    echo "⚙ إعداد قاعدة البيانات المحلية..."
    echo ""
    echo "يرجى إدخال بيانات قاعدة البيانات:"
    read -p "عنوان المضيف [localhost]: " db_host
    db_host=${db_host:-localhost}
    
    read -p "اسم المستخدم [root]: " db_user
    db_user=${db_user:-root}
    
    read -sp "كلمة المرور: " db_password
    echo ""
    
    read -p "اسم قاعدة البيانات [sccit_erp_saas]: " db_name
    db_name=${db_name:-sccit_erp_saas}
    
    # تحديث ملف الإعدادات
    sed -i.bak "s/DB_HOST=.*/DB_HOST=$db_host/" saas/.env
    sed -i.bak "s/DB_USER=.*/DB_USER=$db_user/" saas/.env
    sed -i.bak "s/DB_PASSWORD=.*/DB_PASSWORD=$db_password/" saas/.env
    sed -i.bak "s/DB_NAME=.*/DB_NAME=$db_name/" saas/.env
    
    echo ""
    echo "✓ تم تحديث ملف الإعدادات"
fi

echo ""
echo "✨ نجح إعداد البيئة!"
echo ""
echo "الخطوات التالية:"
echo "  1. اختبر التطبيق: https://saas.sccit-erp.local"
echo "  2. البيانات الافتراضية:"
echo "     البريد: admin@sccit-erp.local"
echo "     كلمة المرور: Admin123!"
echo "  3. اقرأ التوثيق: saas/README.md"
echo ""
echo "للمساعدة: https://www.sccit-erp.com/forum"
echo ""
