# CHANGELOG ANEXUM FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 1.4.0
ADD CLI script to reset stuck cron jobs (bin/reset_stuck_cron.php)
ADD CronjobAnexum class for internal Dolibarr cron scheduler (class/cronjob_anexum.class.php)
ADD Scheduled job to automatically reset stuck cron jobs every 5 minutes

## 1.3.0
FIX filter for invoice list
ADD filter for external user contact list

## 1.2.2
FIX Extrafields where hidden in invoice / propal / order cards when editing

## 1.2.1
Add Invoice List and Card Context

## 1.2
Hide Draft Propals and Orders for users with category extern

## 1.1
Migrate to PHP 8

## 1.0

Initial version
