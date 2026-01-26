#!/bin/bash
#
# Makefile - تحسين سير العمل
#

.PHONY: help install migrate test lint format clean docker-up docker-down

help:
	@echo "╔════════════════════════════════════════════════════════╗"
	@echo "║     SCCIT ERP SaaS - أوامر المساعدة                    ║"
	@echo "╚════════════════════════════════════════════════════════╝"
	@echo ""
	@echo "Commands available:"
	@echo "  make install      - تثبيت المكتبات"
	@echo "  make migrate      - تشغيل الهجرات"
	@echo "  make test         - تشغيل الاختبارات"
	@echo "  make lint         - فحص معايير الكود"
	@echo "  make format       - تنسيق الكود"
	@echo "  make clean        - تنظيف الملفات المؤقتة"
	@echo "  make docker-up    - بدء خدمات Docker"
	@echo "  make docker-down  - إيقاف خدمات Docker"
	@echo "  make setup        - إعداد البيئة الكاملة"
	@echo ""

install:
	@echo "📦 تثبيت المكتبات..."
	composer install --prefer-dist

migrate:
	@echo "💾 تشغيل الهجرات..."
	php saas/migrations/migrate.php

test:
	@echo "🧪 تشغيل الاختبارات..."
	phpunit

lint:
	@echo "🔍 فحص معايير الكود..."
	phpstan analyze saas/
	phpcs saas/

format:
	@echo "🎨 تنسيق الكود..."
	phpcbf saas/

clean:
	@echo "🗑 تنظيف الملفات المؤقتة..."
	rm -rf .phpunit.cache
	rm -rf storage/logs/*
	rm -rf coverage/

docker-up:
	@echo "🐳 بدء خدمات Docker..."
	docker-compose up -d

docker-down:
	@echo "⛔ إيقاف خدمات Docker..."
	docker-compose down

docker-logs:
	@echo "📋 عرض السجلات..."
	docker-compose logs -f

setup: install docker-up migrate
	@echo ""
	@echo "✨ نجح الإعداد!"
	@echo "الخدمات:"
	@echo "  • تطبيق الويب: https://saas.sccit-erp.local"
	@echo "  • phpMyAdmin: http://localhost:8081"
	@echo "  • MailHog: http://localhost:8025"
	@echo ""

.DEFAULT_GOAL := help
