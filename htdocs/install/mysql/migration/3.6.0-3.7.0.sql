--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.7.0 or higher.
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):   VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres) VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE

-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


ALTER TABLE llx_c_paiement ADD COLUMN accountancy_code varchar(32) DEFAULT NULL AFTER active;


ALTER TABLE llx_propal ADD COLUMN currency_rate double(24,8) DEFAULT 1 AFTER fk_currency;
ALTER TABLE llx_commande ADD COLUMN currency_rate double(24,8) DEFAULT 1 AFTER fk_currency;
ALTER TABLE llx_facture ADD COLUMN currency_rate double(24,8) DEFAULT 1 AFTER fk_currency;

ALTER TABLE llx_commande_fournisseur ADD COLUMN fk_account integer AFTER date_livraison;
ALTER TABLE llx_commande_fournisseur ADD COLUMN fk_currency varchar(3) AFTER fk_account;
ALTER TABLE llx_commande_fournisseur ADD COLUMN currency_rate double(24,8) DEFAULT 1 AFTER fk_currency;

ALTER TABLE llx_facture_fourn ADD COLUMN fk_account integer AFTER fk_projet;
ALTER TABLE llx_facture_fourn ADD COLUMN fk_currency varchar(3) AFTER fk_account;
ALTER TABLE llx_facture_fourn ADD COLUMN currency_rate double(24,8) DEFAULT 1 AFTER fk_currency;


ALTER TABLE llx_accountingaccount add column entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_accountingaccount add column datec datetime NOT NULL AFTER entity;
ALTER TABLE llx_accountingaccount add column tms timestamp DEFAULT NULL AFTER datec;
ALTER TABLE llx_accountingaccount add column fk_user_author integer DEFAULT NULL AFTER label;
ALTER TABLE llx_accountingaccount add column fk_user_modif integer DEFAULT NULL AFTER fk_user_author;


-- Drop old table
DROP TABLE llx_compta;
DROP TABLE llx_compta_account;
DROP TABLE llx_compta_compte_generaux;

-- Align size for accounting account
ALTER TABLE llx_accountingaccount MODIFY COLUMN account_number varchar(32);
ALTER TABLE llx_accountingaccount MODIFY COLUMN account_parent varchar(32);
ALTER TABLE llx_accountingdebcred MODIFY COLUMN account_number varchar(32);
ALTER TABLE llx_bank_account MODIFY COLUMN account_number varchar(32);
ALTER TABLE llx_c_chargesociales MODIFY COLUMN accountancy_code varchar(32);
ALTER TABLE llx_c_revenuestamp MODIFY COLUMN accountancy_code_sell varchar(32);
ALTER TABLE llx_c_revenuestamp MODIFY COLUMN accountancy_code_buy varchar(32);
ALTER TABLE llx_c_tva MODIFY COLUMN accountancy_code_sell varchar(32);
ALTER TABLE llx_c_tva MODIFY COLUMN accountancy_code_buy varchar(32);
ALTER TABLE llx_c_product MODIFY COLUMN accountancy_code_sell varchar(32);
ALTER TABLE llx_c_product MODIFY COLUMN accountancy_code_buy varchar(32);
ALTER TABLE llx_user MODIFY COLUMN accountancy_code varchar(32);


ALTER TABLE llx_bank_account ADD COLUMN accountancy_journal varchar(3) DEFAULT NULL AFTER account_number;

ALTER TABLE llx_projet_task_time ADD COLUMN task_datehour datetime after task_date;

-- Localtaxes by thirds
ALTER TABLE llx_c_tva MODIFY COLUMN localtax1 varchar(10);
ALTER TABLE llx_c_tva MODIFY COLUMN localtax2 varchar(10);
ALTER TABLE llx_localtax ADD COLUMN localtaxtype tinyint(4) after entity;
ALTER TABLE llx_societe ADD COLUMN localtax1_value double(6,3) after localtax1_assuj;
ALTER TABLE llx_societe ADD COLUMN localtax2_value double(6,3) after localtax2_assuj;
