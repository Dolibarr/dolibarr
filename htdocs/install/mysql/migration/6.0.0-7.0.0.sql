--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 6.0.0 or higher.
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To drop an index:        -- VMYSQL4.0 DROP INDEX nomindex on llx_table
-- To drop an index:        -- VPGSQL8.0 DROP INDEX nomindex
-- To restrict request to Mysql version x.y minimum use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y minimum use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):    -- VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres): -- VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE
-- To set a field as NULL:                     -- VMYSQL4.3 ALTER TABLE llx_table MODIFY COLUMN name varchar(60) NULL;
-- To set a field as NULL:                     -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as NOT NULL:                 -- VMYSQL4.3 ALTER TABLE llx_table MODIFY COLUMN name varchar(60) NOT NULL;
-- To set a field as NOT NULL:                 -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET NOT NULL;
-- To set a field as default NULL:             -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- Note: fields with type BLOB/TEXT can't have default value.
-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


-- Clean corrupted values for tms
-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_opensurvey_sondage set tms = date_fin where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_opensurvey_sondage set tms = date_fin where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;
-- Remove default not null on date_fin
-- VMYSQL4.3 ALTER TABLE llx_opensurvey_sondage MODIFY COLUMN date_fin DATETIME NULL DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_opensurvey_sondage ALTER COLUMN date_fin DROP NOT NULL;

-- Move real to numeric for more precision for storing monetary amounts (no rouding)
-- https://wiki.dolibarr.org/index.php/Langages_et_normes#Structure_des_tables_et_champs
ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN debit numeric(24,8);
ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN credit numeric(24,8);
ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN montant numeric(24,8);
ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN multicurrency_amount numeric(24,8);
ALTER TABLE llx_blockedlog MODIFY COLUMN amounts numeric(24,8);
ALTER TABLE llx_chargessociales MODIFY COLUMN amount numeric(24,8);
ALTER TABLE llx_commande MODIFY COLUMN amount_ht numeric(24,8);
ALTER TABLE llx_commande_fournisseur MODIFY COLUMN amount_ht numeric(24,8);
ALTER TABLE llx_don MODIFY COLUMN amount numeric(24,8);
ALTER TABLE llx_loan_schedule MODIFY COLUMN amount_capital numeric(24,8);
ALTER TABLE llx_loan_schedule MODIFY COLUMN amount_insurance numeric(24,8);
ALTER TABLE llx_loan_schedule MODIFY COLUMN amount_interest numeric(24,8);
ALTER TABLE llx_paiementcharge MODIFY COLUMN amount numeric(24,8);
ALTER TABLE llx_paiementfourn MODIFY COLUMN amount numeric(24,8);
ALTER TABLE llx_payment_donation MODIFY COLUMN amount numeric(24,8);
ALTER TABLE llx_payment_expensereport MODIFY COLUMN amount numeric(24,8);
ALTER TABLE llx_payment_loan MODIFY COLUMN amount_capital numeric(24,8);
ALTER TABLE llx_payment_loan MODIFY COLUMN amount_insurance numeric(24,8);
ALTER TABLE llx_payment_loan MODIFY COLUMN amount_interest numeric(24,8);
ALTER TABLE llx_payment_salary MODIFY COLUMN salary numeric(24,8);
ALTER TABLE llx_payment_salary MODIFY COLUMN amount numeric(24,8);
ALTER TABLE llx_prelevement_bons MODIFY COLUMN amount numeric(24,8);
ALTER TABLE llx_prelevement_facture_demande MODIFY COLUMN amount numeric(24,8);
ALTER TABLE llx_prelevement_lignes MODIFY COLUMN amount numeric(24,8);
ALTER TABLE llx_societe MODIFY COLUMN capital numeric(24,8);
ALTER TABLE llx_tva MODIFY COLUMN amount numeric(24,8);
