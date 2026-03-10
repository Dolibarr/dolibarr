--
-- This file is executed when upgrading from 22.x to 23.x.
--
-- Add capital_currency to societe: currency for registered capital (independent from third-party default currency).
-- When empty, UI falls back to third-party default currency for display (backward compatible).
--

-- Add column capital_currency to llx_societe (currency code, e.g. SGD, USD)
-- VMYSQL4.1 ALTER TABLE llx_societe ADD COLUMN capital_currency varchar(3) DEFAULT NULL AFTER capital;
-- VPGSQL8.2 ALTER TABLE llx_societe ADD COLUMN capital_currency varchar(3) DEFAULT NULL;
