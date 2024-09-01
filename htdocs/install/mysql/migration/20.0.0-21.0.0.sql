--
-- This file is executed by calling /install/index.php page
-- when current version is higher than the name of this file.
-- Be carefull in the position of each SQL request.
--
-- To restrict request to Mysql version x.y minimum use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y minimum use -- VPGSQLx.y
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new; -- Note that "RENAME TO" is both compatible mysql/postgesql, not "RENAME" alone.
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key or constraint:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To create a unique index:              ALTER TABLE llx_table ADD UNIQUE INDEX uk_table_field (field);
-- To drop an index:        -- VMYSQL4.1 DROP INDEX nomindex ON llx_table;
-- To drop an index:        -- VPGSQL8.2 DROP INDEX nomindex;
-- To make pk to be auto increment (mysql):
-- -- VMYSQL4.3 ALTER TABLE llx_table ADD PRIMARY KEY(rowid);
-- -- VMYSQL4.3 ALTER TABLE llx_table CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres):
-- -- VPGSQL8.2 CREATE SEQUENCE llx_table_rowid_seq OWNED BY llx_table.rowid;
-- -- VPGSQL8.2 ALTER TABLE llx_table ADD PRIMARY KEY (rowid);
-- -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN rowid SET DEFAULT nextval('llx_table_rowid_seq');
-- -- VPGSQL8.2 SELECT setval('llx_table_rowid_seq', MAX(rowid)) FROM llx_table;
-- To set a field as NULL:                     -- VMYSQL4.3 ALTER TABLE llx_table MODIFY COLUMN name varchar(60) NULL;
-- To set a field as NULL:                     -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as NOT NULL:                 -- VMYSQL4.3 ALTER TABLE llx_table MODIFY COLUMN name varchar(60) NOT NULL;
-- To set a field as NOT NULL:                 -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET NOT NULL;
-- To set a field as default NULL:             -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- Note: fields with type BLOB/TEXT can't have default value.
-- To rebuild sequence for postgresql after insert, by forcing id autoincrement fields:
-- -- VPGSQL8.2 SELECT dol_util_rebuild_sequences();


-- Previous version instruction forgotten

-- missing entity field
ALTER TABLE llx_c_holiday_types DROP INDEX uk_c_holiday_types;
ALTER TABLE llx_c_holiday_types ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_c_holiday_types ADD UNIQUE INDEX uk_c_holiday_types (entity, code);


-- V21 migration

DROP TABLE llx_contratdet_log;


-- add billable attribute to project task
ALTER TABLE llx_projet_task ADD COLUMN billable smallint DEFAULT 1;

ALTER TABLE llx_inventory DROP COLUMN datec;

UPDATE llx_document_model SET nom='standard_expensereport' WHERE nom='standard' AND type='expensereport';
UPDATE llx_document_model SET nom='standard_stock' WHERE nom='standard' AND type='stock';
UPDATE llx_document_model SET name='standard_movementstock' WHERE nom='standard' AND type='mouvement';
UPDATE llx_document_model SET nom='standard_evaluation' WHERE nom='standard' AND type='evaluation';
UPDATE llx_document_model SET nom='standard_supplierpayment' WHERE nom='standard' AND type='supplier_payment';
UPDATE llx_document_model SET nom='standard_member' WHERE nom='standard' AND type='member';
-- if rename failed delete old models
DELETE FROM llx_document_model WHERE nom='standard' AND type='expensereport';
DELETE FROM llx_document_model WHERE nom='standard' AND type='stock';
DELETE FROM llx_document_model WHERE nom='standard' AND type='mouvement';
DELETE FROM llx_document_model WHERE nom='standard' AND type='evaluation';
DELETE FROM llx_document_model WHERE nom='standard' AND type='supplier_payment';
DELETE FROM llx_document_model WHERE nom='standard' AND type='member';

ALTER TABLE llx_contrat ADD COLUMN total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_contrat ADD COLUMN localtax1 double(24,8) DEFAULT 0;
ALTER TABLE llx_contrat ADD COLUMN localtax2 double(24,8) DEFAULT 0;
ALTER TABLE llx_contrat ADD COLUMN revenuestamp double(24,8) DEFAULT 0;
ALTER TABLE llx_contrat ADD COLUMN total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_contrat ADD COLUMN total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_expedition_package MODIFY COLUMN dangerous_goods varchar(60) DEFAULT '0';

ALTER TABLE llx_propal ADD COLUMN model_pdf_pos_sign VARCHAR(32) DEFAULT NULL AFTER model_pdf;

ALTER TABLE llx_commande ADD COLUMN signed_status smallint DEFAULT NULL AFTER total_ttc;


-- A dictionary can not have entity = 0
ALTER TABLE llx_c_hrm_public_holiday DROP INDEX uk_c_hrm_public_holiday;
ALTER TABLE llx_c_hrm_public_holiday DROP INDEX uk_c_hrm_public_holiday2;
ALTER TABLE llx_c_hrm_public_holiday MODIFY COLUMN entity integer DEFAULT 1 NOT NULL;
UPDATE llx_c_hrm_public_holiday SET entity = 1 WHERE entity = 0;
ALTER TABLE llx_c_hrm_public_holiday ADD UNIQUE INDEX uk_c_hrm_public_holiday(entity, code);
ALTER TABLE llx_c_hrm_public_holiday ADD UNIQUE INDEX uk_c_hrm_public_holiday2(entity, fk_country, dayrule, day, month, year);

ALTER TABLE llx_societe_account ADD COLUMN date_last_reset_password datetime after date_previous_login;

-- Rename of bank table
ALTER TABLE llx_bank_categ RENAME TO llx_category_bank;		-- TODO Move content into llx_categorie instead of renaming it
ALTER TABLE llx_bank_class RENAME TO llx_category_bankline;


create table llx_paymentexpensereport_expensereport
(
  rowid            		integer AUTO_INCREMENT PRIMARY KEY,
  fk_payment       		integer,
  fk_expensereport 		integer,
  amount           		double(24,8)     DEFAULT 0,

  multicurrency_code	varchar(3),
  multicurrency_tx		double(24,8) DEFAULT 1,
  multicurrency_amount	double(24,8) DEFAULT 0
)ENGINE=innodb;
