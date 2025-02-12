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


-- V19 and - forgotten

UPDATE llx_paiement SET ref = rowid WHERE ref IS NULL OR ref = '';

ALTER TABLE llx_c_holiday_types ADD COLUMN block_if_negative integer NOT NULL DEFAULT 0 AFTER fk_country;
ALTER TABLE llx_c_holiday_types ADD COLUMN sortorder smallint;


-- Clean very old temporary tables (created during v9 migration or repair)

DROP TABLE tmp_llx_accouting_account;
DROP TABLE tmp_llx_accounting_account;


-- Previous version instruction forgotten

-- missing entity field
ALTER TABLE llx_c_holiday_types DROP INDEX uk_c_holiday_types;
ALTER TABLE llx_c_holiday_types ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_c_holiday_types ADD UNIQUE INDEX uk_c_holiday_types (entity, code);

ALTER TABLE llx_hrm_evaluation ADD COLUMN model_pdf varchar(255) DEFAULT NULL;

-- Add ref_ext to asset_model to use various CommonOjbect methods
ALTER TABLE llx_asset_model ADD COLUMN ref_ext varchar(255) AFTER ref;


-- V21 migration

ALTER TABLE llx_product MODIFY COLUMN note_public mediumtext;
ALTER TABLE llx_product MODIFY COLUMN note mediumtext;
ALTER TABLE llx_product_lang MODIFY COLUMN note mediumtext;


CREATE TABLE llx_categorie_fichinter
(
  fk_categorie  integer NOT NULL,
  fk_fichinter  integer NOT NULL,
  import_key    varchar(14)
)ENGINE=innodb;

-- VMYSQL4.3 ALTER TABLE llx_categorie_fichinter ADD PRIMARY KEY pk_categorie_fichinter(fk_categorie, fk_fichinter);
-- VPGSQL8.2 ALTER TABLE llx_categorie_fichinter ADD PRIMARY KEY pk_categorie_fichinter (fk_categorie, fk_fichinter);

ALTER TABLE llx_categorie_fichinter ADD INDEX idx_categorie_fichinter_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_fichinter ADD INDEX idx_categorie_fichinter_fk_fichinter (fk_fichinter);

ALTER TABLE llx_categorie_fichinter ADD CONSTRAINT fk_categorie_fichinter_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_fichinter ADD CONSTRAINT fk_categorie_fichinter_fk_fichinter    FOREIGN KEY (fk_fichinter) REFERENCES llx_fichinter (rowid);


ALTER TABLE llx_blockedlog DROP INDEX entity_action;
ALTER TABLE llx_blockedlog ADD INDEX entity_rowid (entity, rowid);

ALTER TABLE llx_ecm_files MODIFY COLUMN description varchar(255);
ALTER TABLE llx_ecm_files MODIFY COLUMN cover varchar(32);
ALTER TABLE llx_ecm_files ADD COLUMN content text;

ALTER TABLE llx_product DROP FOREIGN KEY fk_product_default_warehouse;

DROP TABLE llx_contratdet_log;

ALTER TABLE llx_societe_rib MODIFY COLUMN iban_prefix varchar(80);
ALTER TABLE llx_bank_account MODIFY COLUMN iban_prefix varchar(80);
ALTER TABLE llx_user_rib MODIFY COLUMN iban_prefix varchar(80);

ALTER TABLE llx_bom_bom ADD COLUMN last_main_doc varchar(255) AFTER model_pdf;

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

ALTER TABLE llx_notify_def ADD COLUMN entity integer DEFAULT 1;

-- A dictionary can not have entity = 0
ALTER TABLE llx_c_hrm_public_holiday DROP INDEX uk_c_hrm_public_holiday;
ALTER TABLE llx_c_hrm_public_holiday DROP INDEX uk_c_hrm_public_holiday2;
ALTER TABLE llx_c_hrm_public_holiday MODIFY COLUMN entity integer DEFAULT 1 NOT NULL;
UPDATE llx_c_hrm_public_holiday SET entity = 1 WHERE entity = 0;
ALTER TABLE llx_c_hrm_public_holiday ADD UNIQUE INDEX uk_c_hrm_public_holiday(entity, code);
ALTER TABLE llx_c_hrm_public_holiday ADD UNIQUE INDEX uk_c_hrm_public_holiday2(entity, fk_country, dayrule, day, month, year);

ALTER TABLE llx_societe_account ADD COLUMN date_last_reset_password datetime after date_previous_login;
ALTER TABLE llx_user_rib ADD COLUMN default_rib smallint NOT NULL DEFAULT 0;
ALTER TABLE llx_prelevement_demande ADD COLUMN fk_societe_rib integer DEFAULT NULL after fk_user_demande;

-- Rename of bank table
ALTER TABLE llx_bank_categ RENAME TO llx_category_bank;		-- TODO Move content into llx_categorie instead of renaming it
ALTER TABLE llx_bank_class RENAME TO llx_category_bankline;


ALTER TABLE llx_bank_account MODIFY COLUMN label varchar(50);


CREATE TABLE llx_bank_import
(
  rowid                 integer         AUTO_INCREMENT PRIMARY KEY,
  id_account			integer			NOT NULL,
  record_type 			varchar(64)   	NULL,
  label         		varchar(255)  	NOT NULL,
  record_type_origin  	varchar(255)  	NOT NULL,
  label_origin  		varchar(255)  	NOT NULL,
  comment				text			NULL,
  note				    text			NULL,
  bdate					date			NULL,
  vdate					date			NULL,
  date_scraped			datetime		NULL,
  original_amount		double(24,8)	NULL,
  original_currency		varchar(255)	NULL,
  amount_debit			double(24,8)	NOT NULL,
  amount_credit       	double(24,8)  NOT NULL,
  deleted_date			datetime		NULL,
  fk_duplicate_of		integer			NULL,
  status				smallint		NOT NULL,
  datec					datetime		NOT NULL,
  tms					timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_author	    integer         NOT NULL,
  fk_user_modif		    integer,
  import_key			varchar(14),
  datas					text			NOT NULL
)ENGINE=innodb;


CREATE TABLE llx_paymentexpensereport_expensereport
(
  rowid            		integer AUTO_INCREMENT PRIMARY KEY,
  fk_payment       		integer,
  fk_expensereport 		integer,
  amount           		double(24,8)     DEFAULT 0,

  multicurrency_code	varchar(3),
  multicurrency_tx		double(24,8) DEFAULT 1,
  multicurrency_amount	double(24,8) DEFAULT 0
)ENGINE=innodb;


ALTER TABLE llx_contrat ADD COLUMN denormalized_lower_planned_end_date datetime;

-- Missing field vat_reverse_charge with constant MAIN_COMPANY_PERENTITY_SHARED
ALTER TABLE llx_societe_perentity ADD COLUMN vat_reverse_charge tinyint DEFAULT 0;


ALTER TABLE llx_actioncomm_reminder ADD COLUMN datedone datetime NULL;


-- Product attribut combination2val
ALTER TABLE llx_product_attribute_combination2val ADD INDEX idx_product_att_com2v_prod_combination (fk_prod_combination);
ALTER TABLE llx_product_attribute_combination2val ADD INDEX idx_product_att_com2v_prod_attr (fk_prod_attr);
ALTER TABLE llx_product_attribute_combination2val ADD INDEX idx_product_att_com2v_prod_attr_val (fk_prod_attr_val);

ALTER TABLE llx_societe ADD COLUMN ip varchar(250);
ALTER TABLE llx_recruitment_recruitmentcandidature ADD COLUMN ip varchar(250);
ALTER TABLE llx_socpeople ADD COLUMN ip varchar(250);

ALTER TABLE llx_webhook_target ADD COLUMN trigger_stack text;

ALTER TABLE llx_recruitment_recruitmentcandidature MODIFY fk_user_creat integer NULL;

ALTER TABLE llx_ecm_files ADD COLUMN agenda_id integer;

-- Add accountancy code general on user / customer / supplier subledger
ALTER TABLE llx_user ADD COLUMN accountancy_code_user_general varchar(32) DEFAULT NULL AFTER fk_barcode_type;
ALTER TABLE llx_societe ADD COLUMN accountancy_code_customer_general varchar(32) DEFAULT NULL AFTER code_fournisseur;
ALTER TABLE llx_societe ADD COLUMN accountancy_code_supplier_general varchar(32) DEFAULT NULL AFTER code_compta;
ALTER TABLE llx_societe_perentity ADD COLUMN accountancy_code_customer_general varchar(32) DEFAULT NULL AFTER entity;
ALTER TABLE llx_societe_perentity ADD COLUMN accountancy_code_supplier_general varchar(32) DEFAULT NULL AFTER accountancy_code_customer;

-- Uniformize length of accountancy account
ALTER TABLE llx_societe MODIFY COLUMN code_compta varchar(32);
ALTER TABLE llx_societe MODIFY COLUMN code_compta_fournisseur varchar(32);
ALTER TABLE llx_societe_perentity MODIFY COLUMN accountancy_code_customer varchar(32);
ALTER TABLE llx_societe_perentity MODIFY COLUMN accountancy_code_supplier varchar(32);


ALTER TABLE llx_multicurrency ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_multicurrency_rate ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_extrafields ADD COLUMN module varchar(64);


-- Copy categories from llx_category_bank into llx_categorie

INSERT INTO llx_categorie (entity, fk_parent, label, type, description, color, position, visible, date_creation)
SELECT
  llx_category_bank.entity,
  0 AS fk_parent,
  llx_category_bank.label,
  8 AS type,
  '' AS description,
  '' AS color,
  0 AS position,
  1 AS visible,
  NOW() AS date_creation
FROM llx_category_bank
LEFT JOIN llx_categorie
  ON llx_category_bank.label = llx_categorie.label
  AND llx_category_bank.entity = llx_categorie.entity
  AND llx_categorie.type = 8
WHERE llx_categorie.rowid IS NULL;

-- Update llx_category_bankline with the new rowid from llx_categorie
UPDATE llx_category_bankline AS bl
INNER JOIN llx_category_bank AS b
  ON bl.fk_categ = b.rowid
INNER JOIN llx_categorie AS c
  ON b.label = c.label
  AND b.entity = c.entity
  AND c.type = 8
SET bl.fk_categ = c.rowid
WHERE c.rowid IS NOT NULL;

INSERT INTO llx_categorie (entity, fk_parent, label, type, description, color, position, visible, date_creation)
SELECT
  llx_bank_categ.entity,
  0 AS fk_parent,
  llx_bank_categ.label,
  8 AS type,
  '' AS description,
  '' AS color,
  0 AS position,
  1 AS visible,
  NOW() AS date_creation
FROM llx_bank_categ
LEFT JOIN llx_categorie
  ON llx_bank_categ.label = llx_categorie.label
  AND llx_bank_categ.entity = llx_categorie.entity
  AND llx_categorie.type = 8
WHERE llx_categorie.rowid IS NULL;

-- Update llx_category_bankline with the new rowid from llx_categorie
UPDATE llx_category_bankline AS bl
INNER JOIN llx_bank_categ AS b
  ON bl.fk_categ = b.rowid
INNER JOIN llx_categorie AS c
  ON b.label = c.label
  AND b.entity = c.entity
  AND c.type = 8
SET bl.fk_categ = c.rowid
WHERE c.rowid IS NOT NULL;

-- Accounting - Add personalized multi-report
create table llx_c_accounting_report
(
  rowid 				integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entity 				integer NOT NULL DEFAULT 1,
  code 					varchar(16) NOT NULL,
  label 				varchar(255) NOT NULL,
  fk_country 			integer DEFAULT NULL,
  active 				integer DEFAULT 1
) ENGINE=innodb;

ALTER TABLE llx_c_accounting_report ADD UNIQUE INDEX uk_c_accounting_report (code,entity);

INSERT INTO llx_c_accounting_report (code, label, active) VALUES ('REP', 'Report personalized', 1);


ALTER TABLE llx_accounting_system ADD COLUMN date_creation datetime;
ALTER TABLE llx_accounting_system ADD COLUMN fk_user_author integer;


ALTER TABLE llx_c_accounting_category ADD COLUMN fk_report integer NOT NULL DEFAULT 1 AFTER entity;

ALTER TABLE llx_c_accounting_category DROP INDEX uk_c_accounting_category;
ALTER TABLE llx_c_accounting_category ADD UNIQUE INDEX uk_c_accounting_category (code,entity,fk_report);

CREATE TABLE llx_accounting_category_account
(
  rowid           			integer AUTO_INCREMENT PRIMARY KEY,
  fk_accounting_category	integer,
  fk_accounting_account		bigint
) ENGINE=innodb;

ALTER TABLE llx_accounting_category_account ADD INDEX idx_accounting_category_account_fk_accounting_category (fk_accounting_category);
ALTER TABLE llx_accounting_category_account ADD CONSTRAINT fk_accounting_category_account_fk_accounting_category FOREIGN KEY (fk_accounting_category) REFERENCES llx_c_accounting_category (rowid);

ALTER TABLE llx_accounting_category_account ADD INDEX idx_accounting_category_account_fk_accounting_account (fk_accounting_account);
ALTER TABLE llx_accounting_category_account ADD CONSTRAINT fk_accounting_category_account_fk_accounting_account FOREIGN KEY (fk_accounting_account) REFERENCES llx_accounting_account (rowid);

ALTER TABLE llx_accounting_category_account ADD UNIQUE INDEX uk_accounting_category_account(fk_accounting_category, fk_accounting_account);

CREATE TABLE llx_product_price_extrafields (
	rowid               integer AUTO_INCREMENT PRIMARY KEY,
	tms                 timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_object           integer NOT NULL,
	import_key          varchar(14) -- import key
) ENGINE=InnoDB;

ALTER TABLE llx_product_price_extrafields ADD UNIQUE INDEX uk_product_price_extrafields (fk_object);

CREATE TABLE llx_product_customer_price_extrafields (
	rowid               integer AUTO_INCREMENT PRIMARY KEY,
	tms                 timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_object           integer NOT NULL,
	import_key          varchar(14) -- import key
) ENGINE=innodb;

ALTER TABLE llx_product_customer_price_extrafields ADD UNIQUE INDEX uk_product_customer_price_extrafields (fk_object);
ALTER TABLE llx_facture ADD COLUMN payment_reference varchar(25) AFTER date_lim_reglement;
ALTER TABLE llx_societe ADD COLUMN tp_payment_reference varchar(25) AFTER code_fournisseur;

ALTER TABLE llx_actioncomm ADD COLUMN fk_task integer;

ALTER TABLE llx_commande_fournisseurdet ADD COLUMN subprice_ttc double(24,8) DEFAULT 0 after subprice;
ALTER TABLE llx_commande_fournisseurdet ADD COLUMN multicurrency_subprice_ttc double(24,8) DEFAULT 0 after multicurrency_subprice;
ALTER TABLE llx_commandedet ADD COLUMN subprice_ttc double(24,8) DEFAULT 0 after subprice;
ALTER TABLE llx_commandedet ADD COLUMN multicurrency_subprice_ttc double(24,8) DEFAULT 0 after multicurrency_subprice;
ALTER TABLE llx_contratdet ADD COLUMN subprice_ttc double(24,8) DEFAULT 0 after subprice;
ALTER TABLE llx_contratdet ADD COLUMN multicurrency_subprice_ttc double(24,8) DEFAULT 0 after multicurrency_subprice;
ALTER TABLE llx_deliverydet ADD COLUMN subprice_ttc double(24,8) DEFAULT 0 after subprice;
ALTER TABLE llx_expensereport_det ADD COLUMN subprice_ttc double(24,8) DEFAULT 0 after subprice;
ALTER TABLE llx_expensereport_det ADD COLUMN multicurrency_subprice_ttc double(24,8) DEFAULT 0 after multicurrency_subprice;
ALTER TABLE llx_facture_fourn_det ADD COLUMN multicurrency_subprice_ttc double(24,8) DEFAULT 0 after multicurrency_subprice;
ALTER TABLE llx_facture_fourn_det_rec ADD COLUMN multicurrency_subprice_ttc double(24,8) DEFAULT 0 after multicurrency_subprice;
ALTER TABLE llx_facturedet ADD COLUMN subprice_ttc double(24,8) DEFAULT 0 after subprice;
ALTER TABLE llx_facturedet ADD COLUMN multicurrency_subprice_ttc double(24,8) DEFAULT 0 after multicurrency_subprice;
ALTER TABLE llx_facturedet_rec ADD COLUMN subprice_ttc double(24,8) DEFAULT 0 after subprice;
ALTER TABLE llx_facturedet_rec ADD COLUMN multicurrency_subprice_ttc double(24,8) DEFAULT 0 after multicurrency_subprice;
ALTER TABLE llx_fichinterdet_rec ADD COLUMN subprice_ttc double(24,8) DEFAULT 0 after subprice;
ALTER TABLE llx_propaldet ADD COLUMN subprice_ttc double(24,8) DEFAULT 0 after subprice;
ALTER TABLE llx_propaldet ADD COLUMN multicurrency_subprice_ttc double(24,8) DEFAULT 0 after multicurrency_subprice;
ALTER TABLE llx_supplier_proposaldet ADD COLUMN subprice_ttc double(24,8) DEFAULT 0 after subprice;
ALTER TABLE llx_supplier_proposaldet ADD COLUMN multicurrency_subprice_ttc double(24,8) DEFAULT 0 after multicurrency_subprice;

-- Add VAT by department
ALTER TABLE llx_c_tva ADD COLUMN fk_department_buyer integer DEFAULT NULL AFTER fk_pays;
ALTER TABLE llx_c_tva ADD INDEX idx_tva_fk_department_buyer (fk_department_buyer);
ALTER TABLE llx_c_tva ADD CONSTRAINT fk_tva_fk_department_buyer FOREIGN KEY (fk_department_buyer) REFERENCES llx_c_departements (rowid);

ALTER TABLE llx_expeditiondet ADD COLUMN fk_unit integer AFTER qty;
INSERT INTO llx_c_type_contact (element, source, code, libelle, active ) values ('expedition', 'external', 'SHIPPING',      'Loading facility', 1);
INSERT INTO llx_c_type_contact (element, source, code, libelle, active ) values ('expedition', 'external', 'SHIPPING',      'Delivery facility', 1);
INSERT INTO llx_c_type_contact (element, source, code, libelle, active ) values ('expedition', 'external', 'SHIPPING',      'Customer shipping contact', 1);

ALTER TABLE llx_facture_rec ADD COLUMN fk_societe_rib integer DEFAULT NULL;

ALTER TABLE llx_facture ADD COLUMN is_also_delivery_note tinyint DEFAULT 0 NOT NULL;
ALTER TABLE llx_user MODIFY COLUMN signature LONGTEXT;


ALTER TABLE llx_societe_rib MODIFY COLUMN label varchar(180);	-- 200 is too long to allow index after
ALTER TABLE llx_societe_rib MODIFY COLUMN iban_prefix varchar(100);

-- Add entity field
ALTER TABLE llx_societe_rib DROP INDEX uk_societe_rib;
ALTER TABLE llx_societe_rib ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
-- select entity, label, fk_soc, default_rib, MIN(iban_prefix), MAX(iban_prefix), MIN(rowid), MAX(rowid), COUNT(rowid) from llx_societe_rib GROUP BY entity, label, fk_soc, default_rib HAVING COUNT(rowid) > 1;
ALTER TABLE llx_societe_rib ADD UNIQUE INDEX uk_societe_rib(entity, label, fk_soc);


ALTER TABLE llx_societe_account DROP INDEX uk_societe_account_login_website_soc;
ALTER TABLE llx_societe_account ADD UNIQUE INDEX uk_societe_account_login_website(entity, login, site, fk_website);
