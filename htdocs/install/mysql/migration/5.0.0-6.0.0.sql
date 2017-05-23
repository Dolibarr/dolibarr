--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 5.0.0 or higher.
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

ALTER TABLE llx_opensurvey_sondage MODIFY COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE llx_opensurvey_sondage ADD COLUMN fk_user_creat integer NOT NULL DEFAULT 0;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN status integer DEFAULT 1 after date_fin;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN entity integer DEFAULT 1 NOT NULL;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN allow_comments tinyint NOT NULL DEFAULT 1;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN allow_spy tinyint NOT NULL DEFAULT 1 AFTER allow_comments;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN sujet TEXT;


create table llx_notify_def_object
(
  id				integer AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,		-- multi company id
  objet_type		varchar(16),					-- 'actioncomm'
  objet_id			integer NOT NULL,				-- id of parent key
  type_notif		varchar(16) DEFAULT 'browser',	-- 'browser', 'email', 'sms', 'webservice', ...
  date_notif		datetime,						-- date notification
  user_id			integer,						-- notification is for this user
  moreparam			varchar(255)
)ENGINE=innodb;

ALTER TABLE llx_facturedet_rec ADD COLUMN vat_src_code varchar(10) DEFAULT '' AFTER tva_tx;

ALTER TABLE llx_extrafields ADD COLUMN langs varchar(24);

ALTER TABLE llx_supplier_proposaldet ADD COLUMN fk_unit integer DEFAULT NULL;

ALTER TABLE llx_ecm_files ADD COLUMN ref varchar(128) AFTER rowid;
ALTER TABLE llx_ecm_files CHANGE COLUMN fullpath filepath varchar(255);
ALTER TABLE llx_ecm_files CHANGE COLUMN filepath filepath varchar(255);
ALTER TABLE llx_ecm_files ADD COLUMN position integer;
ALTER TABLE llx_ecm_files ADD COLUMN keyword varchar(750);
ALTER TABLE llx_ecm_files CHANGE COLUMN keyword keyword varchar(750);
ALTER TABLE llx_ecm_files ADD COLUMN gen_or_uploaded varchar(12);

ALTER TABLE llx_ecm_files DROP INDEX uk_ecm_files;
ALTER TABLE llx_ecm_files ADD UNIQUE INDEX uk_ecm_files (filepath, filename, entity);

ALTER TABLE llx_ecm_files ADD INDEX idx_ecm_files_label (label);


ALTER TABLE llx_holiday ADD COLUMN import_key				varchar(14);
ALTER TABLE llx_holiday ADD COLUMN extraparams				varchar(255);	

ALTER TABLE llx_expedition ADD COLUMN fk_projet integer DEFAULT NULL after fk_soc;

ALTER TABLE llx_expensereport ADD COLUMN import_key			varchar(14);
ALTER TABLE llx_expensereport ADD COLUMN extraparams		varchar(255);	

ALTER TABLE llx_bank_account ADD COLUMN extraparams		varchar(255);	

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_CREATE','Product or service created','Executed when a product or sevice is created','product',30);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_MODIFY','Product or service modified','Executed when a product or sevice is modified','product',30);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_DELETE','Product or service deleted','Executed when a product or sevice is deleted','product',30);

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_CREATE','Expense report created','Executed when an expense report is created','expense_report',201);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_VALIDATE','Expense report validated','Executed when an expense report is validated','expense_report',202);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_APPROVE','Expense report approved','Executed when an expense report is approved','expense_report',203);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_PAYED','Expense report billed','Executed when an expense report is set as billed','expense_report',204);

ALTER TABLE llx_c_email_templates ADD COLUMN content_lines text;

ALTER TABLE llx_loan ADD COLUMN fk_projet integer DEFAULT NULL;

ALTER TABLE llx_holiday ADD COLUMN fk_user_modif integer;

ALTER TABLE llx_projet_task_time ADD COLUMN datec date;
ALTER TABLE llx_projet_task_time ADD COLUMN tms timestamp;

ALTER TABLE llx_product_price_by_qty ADD COLUMN fk_user_creat integer;
ALTER TABLE llx_product_price_by_qty ADD COLUMN fk_user_modif integer;
ALTER TABLE llx_product_price_by_qty DROP COLUMN date_price;
ALTER TABLE llx_product_price_by_qty ADD COLUMN tms timestamp;
ALTER TABLE llx_product_price_by_qty ADD COLUMN import_key varchar(14);

ALTER TABLE llx_user ADD COLUMN import_key varchar(14);


CREATE TABLE llx_product_attribute
(
  rowid INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  ref VARCHAR(255) NOT NULL,
  label VARCHAR(255) NOT NULL,
  rang INT DEFAULT 0 NOT NULL,
  entity INT DEFAULT 1 NOT NULL
);
ALTER TABLE llx_product_attribute ADD CONSTRAINT unique_ref UNIQUE (ref);

CREATE TABLE llx_product_attribute_value
(
  rowid INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  fk_product_attribute INT NOT NULL,
  ref VARCHAR(255) DEFAULT NULL,
  value VARCHAR(255) DEFAULT NULL,
  entity INT DEFAULT 1 NOT NULL
);
ALTER TABLE llx_product_attribute_value ADD CONSTRAINT unique_ref UNIQUE (fk_product_attribute,ref);

CREATE TABLE llx_product_attribute_combination2val
(
  rowid INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  fk_prod_combination INT NOT NULL,
  fk_prod_attr INT NOT NULL,
  fk_prod_attr_val INT NOT NULL
);
CREATE TABLE llx_product_attribute_combination
(
  rowid INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  fk_product_parent INT NOT NULL,
  fk_product_child INT NOT NULL,
  variation_price FLOAT NOT NULL,
  variation_price_percentage INT NULL,
  variation_weight FLOAT NOT NULL,
  entity INT DEFAULT 1 NOT NULL
);


ALTER TABLE llx_bank_account drop foreign key bank_fk_accountancy_journal;

-- Add journal entries
INSERT INTO llx_accounting_journal (rowid, code, label, nature, active) VALUES (1,'VT', 'Sale journal', 2, 1);
INSERT INTO llx_accounting_journal (rowid, code, label, nature, active) VALUES (2,'AC', 'Purchase journal', 3, 1);
INSERT INTO llx_accounting_journal (rowid, code, label, nature, active) VALUES (3,'BQ', 'Bank journal', 4, 1);
INSERT INTO llx_accounting_journal (rowid, code, label, nature, active) VALUES (4,'OD', 'Other journal', 1, 1);
INSERT INTO llx_accounting_journal (rowid, code, label, nature, active) VALUES (5,'AN', 'Has new journal', 9, 1);
-- Fix old entries
UPDATE llx_accounting_journal SET nature = 1 where code = 'OD' and nature = 0;
UPDATE llx_accounting_journal SET nature = 2 where code = 'VT' and nature = 1;
UPDATE llx_accounting_journal SET nature = 3 where code = 'AC' and nature = 2;
UPDATE llx_accounting_journal SET nature = 4 where (code = 'BK' or code = 'BQ') and nature = 3;

UPDATE llx_bank_account as ba set accountancy_journal = 'BQ' where accountancy_journal = 'BK';
UPDATE llx_bank_account as ba set accountancy_journal = 'OD' where accountancy_journal IS NULL;

ALTER TABLE llx_bank_account ADD COLUMN fk_accountancy_journal integer;
ALTER TABLE llx_bank_account ADD INDEX idx_fk_accountancy_journal (fk_accountancy_journal);

UPDATE llx_bank_account as ba set fk_accountancy_journal = (SELECT rowid FROM llx_accounting_journal as aj where ba.accountancy_journal = aj.code) where accountancy_journal not in ('1', '2', '3', '4', '5', '6', '5', '8', '9', '10', '11', '12', '13', '14', '15');
ALTER TABLE llx_bank_account ADD CONSTRAINT fk_bank_account_accountancy_journal FOREIGN KEY (fk_accountancy_journal) REFERENCES llx_accounting_journal (rowid);

--Update general ledger for FEC format & harmonization
ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN code_tiers varchar(32);
ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN label_compte varchar(255);
ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN code_journal varchar(32);
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN thirdparty_label varchar(255) AFTER code_tiers;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN label_operation varchar(255) AFTER label_compte;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN multicurrency_amount double AFTER sens;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN multicurrency_code varchar(255) AFTER multicurrency_amount;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN lettering_code varchar(255) AFTER multicurrency_code;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN date_lettering datetime AFTER lettering_code;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN journal_label varchar(255) AFTER code_journal;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN date_validated datetime AFTER validated;

ALTER TABLE llx_paiementfourn ADD COLUMN model_pdf varchar(255);

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_CREATE','Expense report created','Executed when an expense report is created','expensereport',201);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_VALIDATE','Expense report validated','Executed when an expense report is validated','expensereport',202);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_APPROVE','Expense report approved','Executed when an expense report is approved','expensereport',203);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_PAYED','Expense report billed','Executed when an expense report is set as billed','expensereport',204);

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_CREATE'  ,'Leave request created','Executed when a leave request is created','holiday',221);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_VALIDATE','Leave request validated','Executed when a leave request is validated','holiday',222);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_APPROVE' ,'Leave request approved','Executed when a leave request is approved','holiday',223);


ALTER TABLE llx_societe_remise_except ADD COLUMN fk_invoice_supplier_line	integer;
ALTER TABLE llx_societe_remise_except ADD COLUMN fk_invoice_supplier		integer;
ALTER TABLE llx_societe_remise_except ADD COLUMN fk_invoice_supplier_source	integer;

ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_soc_remise_fk_invoice_supplier_line       FOREIGN KEY (fk_invoice_supplier_line) REFERENCES llx_facture_fourn_det (rowid);
ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_societe_remise_fk_invoice_supplier        FOREIGN KEY (fk_invoice_supplier)      REFERENCES llx_facture_fourn (rowid);
ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_societe_remise_fk_invoice_supplier_source FOREIGN KEY (fk_invoice_supplier)      REFERENCES llx_facture_fourn (rowid);

ALTER TABLE llx_facture_rec ADD COLUMN vat_src_code	varchar(10) DEFAULT '';

DELETE FROM llx_const where name = 'ADHERENT_BANK_USE_AUTO';

UPDATE llx_const set value='moono-lisa' where value = 'moono' AND name = 'FCKEDITOR_SKIN';

ALTER TABLE llx_product_price ADD COLUMN default_vat_code	varchar(10) after tva_tx;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN default_vat_code	varchar(10) after tva_tx;

ALTER TABLE llx_user ADD COLUMN model_pdf varchar(255);
ALTER TABLE llx_usergroup ADD COLUMN model_pdf varchar(255);

INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('PRODUCT_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/products', 'chaine', 0, '');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('CONTRACT_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/contracts', 'chaine', 0, '');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('USERGROUP_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/usergroups', 'chaine', 0, '');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('USER_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/users', 'chaine', 0, '');

INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('MAIN_ENABLE_OVERWRITE_TRANSLATION', 1, '1', 'chaine', 0, 'Enable overwrote of translation');

ALTER TABLE llx_chargesociales ADD COLUMN ref varchar(16);
ALTER TABLE llx_chargesociales ADD COLUMN fk_projet integer DEFAULT NULL;

ALTER TABLE llx_cronjob ADD COLUMN processing integer NOT NULL DEFAULT 0;

ALTER TABLE llx_website ADD COLUMN fk_user_create integer;
ALTER TABLE llx_website ADD COLUMN fk_user_modif integer;

-- Add missing fields making not possible to enter reference price of products into another currency
ALTER TABLE llx_product_fournisseur_price ADD COLUMN multicurrency_tx			double(24,8) DEFAULT 1;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN multicurrency_price_ttc	double(24,8) DEFAULT NULL;

ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_multicurrency		 integer;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN multicurrency_code		 varchar(255);
ALTER TABLE llx_product_fournisseur_price ADD COLUMN multicurrency_tx	     double(24,8) DEFAULT 1;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN multicurrency_price	 double(24,8) DEFAULT NULL;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN multicurrency_price_ttc double(24,8) DEFAULT NULL;

ALTER TABLE llx_product_fournisseur_price_log ADD COLUMN fk_multicurrency		 integer;
ALTER TABLE llx_product_fournisseur_price_log ADD COLUMN multicurrency_code		 varchar(255);
ALTER TABLE llx_product_fournisseur_price_log ADD COLUMN multicurrency_tx	     double(24,8) DEFAULT 1;
ALTER TABLE llx_product_fournisseur_price_log ADD COLUMN multicurrency_price	 double(24,8) DEFAULT NULL;
ALTER TABLE llx_product_fournisseur_price_log ADD COLUMN multicurrency_price_ttc double(24,8) DEFAULT NULL;


create table llx_payment_various
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  datec                 datetime,
  datep                 date,
  datev                 date,
  sens                  smallint DEFAULT 0 NOT NULL,
  amount                double(24,8) DEFAULT 0 NOT NULL,
  fk_typepayment        integer NOT NULL,
  num_payment           varchar(50),
  label                 varchar(255),
  accountancy_code		varchar(32),
  entity                integer DEFAULT 1 NOT NULL,
  note                  text,
  fk_bank               integer,
  fk_user_author        integer,
  fk_user_modif         integer
)ENGINE=innodb;


create table llx_default_values
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  entity          integer DEFAULT 1 NOT NULL,		-- multi company id
  type			  varchar(10),                      -- 'createform', 'filters', 'sortorder'
  user_id         integer DEFAULT 0 NOT NULL,       -- 0 or user id
  page            varchar(255),                     -- relative url of page
  param           varchar(255),                     -- parameter
  value		      varchar(128)                      -- value
)ENGINE=innodb;

ALTER TABLE llx_default_values ADD UNIQUE INDEX uk_default_values(type, entity, user_id, page, param);


ALTER TABLE llx_supplier_proposaldet ADD INDEX idx_supplier_proposaldet_fk_supplier_proposal (fk_supplier_proposal);
ALTER TABLE llx_supplier_proposaldet ADD INDEX idx_supplier_proposaldet_fk_product (fk_product);

UPDATE llx_supplier_proposaldet SET fk_unit = NULL where fk_unit not in (SELECT rowid from llx_c_units);
ALTER TABLE llx_supplier_proposaldet ADD CONSTRAINT fk_supplier_proposaldet_fk_unit FOREIGN KEY (fk_unit) REFERENCES llx_c_units (rowid);

ALTER TABLE llx_supplier_proposaldet ADD CONSTRAINT fk_supplier_proposaldet_fk_supplier_proposal FOREIGN KEY (fk_supplier_proposal) REFERENCES llx_supplier_proposal (rowid);

-- NEW inventory module
CREATE TABLE llx_inventory 
( 
rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY, 
datec datetime DEFAULT NULL,
tms timestamp, 
fk_warehouse integer DEFAULT 0, 
entity integer DEFAULT 0, 
status integer DEFAULT 0, 
title varchar(255) NOT NULL, 
date_inventory datetime DEFAULT NULL
) 
ENGINE=InnoDB;

CREATE TABLE llx_inventorydet 
( 
rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY, 
datec datetime DEFAULT NULL,
tms timestamp, 
fk_inventory integer DEFAULT 0, 
fk_warehouse integer DEFAULT 0,
fk_product integer DEFAULT 0,  
batch varchar(30) DEFAULT NULL,
qty_view double DEFAULT NULL,
qty_stock double DEFAULT NULL,
qty_regulated double DEFAULT NULL,
pmp double DEFAULT 0, 
pa double DEFAULT 0, 
new_pmp double DEFAULT 0
) 
ENGINE=InnoDB;

ALTER TABLE llx_inventory ADD INDEX idx_inventory_tms (tms);
ALTER TABLE llx_inventory ADD INDEX idx_inventory_datec (datec);
ALTER TABLE llx_inventorydet ADD INDEX idx_inventorydet_tms (tms);
ALTER TABLE llx_inventorydet ADD INDEX idx_inventorydet_datec (datec);
ALTER TABLE llx_inventorydet ADD INDEX idx_inventorydet_fk_inventory (fk_inventory);

insert into llx_c_tva(fk_pays,taux,code,recuperableonly,note,active)                                                   values (1, '8.5', '85', '0','VAT standard rate (DOM sauf Guyane et Saint-Martin)',0);
insert into llx_c_tva(fk_pays,taux,code,recuperableonly,note,active)                                                   values (1, '8.5', '85NPR', '1','VAT standard rate (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par acheteur',0);
insert into llx_c_tva(fk_pays,taux,code,recuperableonly,localtax1,localtax1_type,note,active)                          values (1, '8.5', '85NPROM', '1', 2, 3, 'VAT standard rate (DOM sauf Guyane et Saint-Martin), NPR, Octroi de Mer',0);
insert into llx_c_tva(fk_pays,taux,code,recuperableonly,localtax1,localtax1_type,localtax2,localtax2_type,note,active) values (1, '8.5', '85NPROMOMR', '1', 2, 3, 2.5, 3, 'VAT standard rate (DOM sauf Guyane et Saint-Martin), NPR, Octroi de Mer et Octroi de Mer Regional',0);

ALTER TABLE llx_events MODIFY COLUMN ip varchar(250);

ALTER TABLE llx_facture ADD COLUMN fk_fac_rec_source integer;

DELETE from llx_c_actioncomm where code in ('AC_PROP','AC_COM','AC_FAC','AC_SHIP','AC_SUP_ORD','AC_SUP_INV') AND id NOT IN (SELECT DISTINCT fk_action FROM llx_actioncomm);


