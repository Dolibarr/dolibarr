# CHANGELOG ANEXUM FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 1.5.0
ADD Contract / contact open-ticket status dot + tooltip on lists and cards (ClickUp 8694apzek)
ADD Ticket "human-only" toggle on the messaging view, hides API-bot rows (internal cockpit subtask)
ADD ticketcard presend hook that prefills the CC field with ANEXUM_TICKET_CC_MONITORING (default monitoring@anexum.at, ClickUp 869b7ck49)
ADD completeSubstitutionsArray hook exposing `__EXTRAFIELD_<NAME>_LABEL__` for every sellist extrafield and `__ORDER_REF__` on contract templates (ClickUp 869ab3xx9)
ADD bin/update_fertigstellung_template.php CLI script that tokenizes the Fertigstellungsmeldung template content
ADD new hook contexts: contractlist, contractcard, contactcard, thirdpartycontract, ticketmessaging

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
