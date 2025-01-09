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



-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_journal MODIFY code VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_journal MODIFY code VARCHAR(20) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_bank_account MODIFY accountancy_journal VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_bank_account MODIFY accountancy_journal VARCHAR(20) COLLATE utf8_unicode_ci;


ALTER TABLE llx_holiday_config MODIFY COLUMN name varchar(128);

ALTER TABLE llx_supplier_proposaldet CHANGE COLUMN fk_askpricesupplier fk_supplier_proposal integer NOT NULL;

-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- -- VMYSQL4.1 ALTER TABLE llx_adherent MODIFY COLUMN datefin datetime DEFAULT '2001-01-01 00:00:00';
-- VMYSQL4.1 update llx_adherent set datefin = NULL where DATE(STR_TO_DATE(datefin, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_adherent set datefin = NULL where DATE(STR_TO_DATE(datefin, '%Y-%m-%d')) IS NULL;

-- VMYSQL4.1 ALTER TABLE llx_opensurvey_sondage MODIFY COLUMN tms timestamp DEFAULT '2001-01-01 00:00:00';
-- VMYSQL4.1 ALTER TABLE llx_adherent MODIFY COLUMN datefin datetime NULL;

-- To remove a default value for date that is not valid when field is not null
-- VMYSQL4.1 ALTER TABLE llx_chargesociales MODIFY COLUMN date_ech datetime DEFAULT NULL;
-- VMYSQL4.1 ALTER TABLE llx_chargesociales MODIFY COLUMN date_ech datetime NOT NULL;




-- Clean corrupted values for tms
-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_opensurvey_sondage set tms = date_fin where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_opensurvey_sondage set tms = date_fin where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;
-- Remove default not null on date_fin
-- VMYSQL4.3 ALTER TABLE llx_opensurvey_sondage MODIFY COLUMN date_fin DATETIME NULL DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_opensurvey_sondage ALTER COLUMN date_fin DROP NOT NULL;

-- VMYSQL4.1 ALTER TABLE llx_opensurvey_sondage MODIFY COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_opensurvey_sondage ADD COLUMN fk_user_creat integer NOT NULL DEFAULT 0;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN status integer DEFAULT 1 after date_fin;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN entity integer DEFAULT 1 NOT NULL;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN allow_comments tinyint NOT NULL DEFAULT 1;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN allow_spy tinyint NOT NULL DEFAULT 1 AFTER allow_comments;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN sujet TEXT;


ALTER TABLE llx_socpeople MODIFY COLUMN zip varchar(25);


ALTER TABLE llx_extrafields ADD COLUMN fieldcomputed text;
ALTER TABLE llx_extrafields ADD COLUMN fielddefault varchar(255);

ALTER TABLE llx_c_typent MODIFY COLUMN libelle varchar(64); 


ALTER TABLE llx_holiday ADD COLUMN ref	varchar(30) NULL;
ALTER TABLE llx_holiday ADD COLUMN ref_ext	varchar(255);


CREATE TABLE llx_notify_def_object
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


ALTER TABLE llx_expedition ADD COLUMN fk_projet integer DEFAULT NULL after fk_soc;


ALTER TABLE llx_holiday ADD COLUMN import_key				varchar(14);
ALTER TABLE llx_holiday ADD COLUMN extraparams				varchar(255);	

ALTER TABLE llx_expensereport ADD COLUMN import_key			varchar(14);
ALTER TABLE llx_expensereport ADD COLUMN extraparams		varchar(255);	

ALTER TABLE llx_actioncomm ADD COLUMN import_key			varchar(14);
ALTER TABLE llx_actioncomm ADD COLUMN extraparams			varchar(255);	


ALTER TABLE llx_bank_account ADD COLUMN extraparams		varchar(255);	

ALTER TABLE llx_bank ADD COLUMN numero_compte varchar(32) NULL; 

-- VMYSQL4.1 ALTER TABLE llx_bank_account MODIFY COLUMN state_id integer DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_bank_account ALTER COLUMN state_id DROP DEFAULT;
-- VPGSQL8.2 ALTER TABLE llx_bank_account MODIFY COLUMN state_id integer USING state_id::integer;
-- VPGSQL8.2 ALTER TABLE llx_bank_account ALTER COLUMN state_id SET DEFAULT NULL;
 
-- VMYSQL4.1 ALTER TABLE llx_adherent MODIFY COLUMN state_id integer DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_adherent ALTER COLUMN state_id DROP DEFAULT;
-- VPGSQL8.2 ALTER TABLE llx_adherent MODIFY COLUMN state_id integer USING state_id::integer;
-- VMYSQL4.1 ALTER TABLE llx_adherent MODIFY COLUMN country integer DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_adherent ALTER COLUMN country DROP DEFAULT;
-- VPGSQL8.2 ALTER TABLE llx_adherent MODIFY COLUMN country integer USING country::integer;

INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('PRODUCT_CREATE','Product or service created','Executed when a product or sevice is created','product',30);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('PRODUCT_MODIFY','Product or service modified','Executed when a product or sevice is modified','product',30);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('PRODUCT_DELETE','Product or service deleted','Executed when a product or sevice is deleted','product',30);

INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('EXPENSE_REPORT_CREATE','Expense report created','Executed when an expense report is created','expense_report',201);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('EXPENSE_REPORT_CREATE','Expense report created','Executed when an expense report is created','expense_report',201);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('EXPENSE_REPORT_VALIDATE','Expense report validated','Executed when an expense report is validated','expense_report',202);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('EXPENSE_REPORT_APPROVE','Expense report approved','Executed when an expense report is approved','expense_report',203);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('EXPENSE_REPORT_PAYED','Expense report billed','Executed when an expense report is set as billed','expense_report',204);

ALTER TABLE llx_c_email_templates ADD COLUMN content_lines text;

ALTER TABLE llx_loan ADD COLUMN fk_projet integer DEFAULT NULL;

ALTER TABLE llx_holiday ADD COLUMN fk_user_modif integer;
ALTER TABLE llx_projet ADD COLUMN fk_user_modif integer;
ALTER TABLE llx_projet_task ADD COLUMN fk_user_modif integer;

ALTER TABLE llx_projet_task_time ADD COLUMN datec date;
ALTER TABLE llx_projet_task_time ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_product_price ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_product_price ADD COLUMN multicurrency_code	varchar(255);
ALTER TABLE llx_product_price ADD COLUMN multicurrency_tx double(24,8) DEFAULT 1;
ALTER TABLE llx_product_price ADD COLUMN multicurrency_price double(24,8) DEFAULT NULL;
ALTER TABLE llx_product_price ADD COLUMN multicurrency_price_ttc double(24,8) DEFAULT NULL;

ALTER TABLE llx_product_price_by_qty ADD COLUMN fk_user_creat integer;
ALTER TABLE llx_product_price_by_qty ADD COLUMN fk_user_modif integer;
ALTER TABLE llx_product_price_by_qty DROP COLUMN date_price;
ALTER TABLE llx_product_price_by_qty ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_product_price_by_qty ADD COLUMN import_key varchar(14);

ALTER TABLE llx_user ADD COLUMN import_key varchar(14);

ALTER TABLE llx_facture_rec ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
UPDATE llx_facture_rec SET tms = datec where tms < '2000-01-01';

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


ALTER TABLE llx_bank_account DROP FOREIGN KEY bank_fk_accountancy_journal;

-- Fix missing entity column after init demo
ALTER TABLE llx_accounting_journal ADD COLUMN entity integer DEFAULT 1;

-- Add journal entries
INSERT INTO llx_accounting_journal (rowid, code, label, nature, active) VALUES (1,'VT', 'Sale journal', 2, 1);
INSERT INTO llx_accounting_journal (rowid, code, label, nature, active) VALUES (2,'AC', 'Purchase journal', 3, 1);
INSERT INTO llx_accounting_journal (rowid, code, label, nature, active) VALUES (3,'BQ', 'Bank journal', 4, 1);
INSERT INTO llx_accounting_journal (rowid, code, label, nature, active) VALUES (4,'OD', 'Other journal', 1, 1);
INSERT INTO llx_accounting_journal (rowid, code, label, nature, active) VALUES (5,'AN', 'Has new journal', 9, 1);
INSERT INTO llx_accounting_journal (rowid, code, label, nature, active) VALUES (6,'ER', 'Expense report journal', 5, 1);
-- Fix old entries
UPDATE llx_accounting_journal SET nature = 1 WHERE code = 'OD' AND nature = 0;
UPDATE llx_accounting_journal SET nature = 2 WHERE code = 'VT' AND nature = 1;
UPDATE llx_accounting_journal SET nature = 3 WHERE code = 'AC' AND nature = 2;
UPDATE llx_accounting_journal SET nature = 4 WHERE (code = 'BK' OR code = 'BQ') AND nature = 3;

UPDATE llx_bank_account SET accountancy_journal = 'BQ' WHERE accountancy_journal = 'BK';
UPDATE llx_bank_account SET accountancy_journal = 'OD' WHERE accountancy_journal IS NULL;

ALTER TABLE llx_bank_account ADD COLUMN fk_accountancy_journal integer;
ALTER TABLE llx_bank_account ADD INDEX idx_fk_accountancy_journal (fk_accountancy_journal);

UPDATE llx_bank_account AS ba SET fk_accountancy_journal = (SELECT rowid FROM llx_accounting_journal AS aj WHERE ba.accountancy_journal = aj.code AND aj.entity = ba.entity) WHERE accountancy_journal NOT IN ('1', '2', '3', '4', '5', '6', '5', '8', '9', '10', '11', '12', '13', '14', '15');
ALTER TABLE llx_bank_account ADD CONSTRAINT fk_bank_account_accountancy_journal FOREIGN KEY (fk_accountancy_journal) REFERENCES llx_accounting_journal (rowid);

--Update general ledger for FEC format & harmonization

ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN code_tiers varchar(32);
ALTER TABLE llx_accounting_bookkeeping CHANGE COLUMN code_tiers thirdparty_code varchar(32);

--Subledger account
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN subledger_account varchar(32);
ALTER TABLE llx_accounting_bookkeeping CHANGE COLUMN thirdparty_label subledger_label varchar(255);    	-- If field was already created, rename it	
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN subledger_label varchar(255) AFTER subledger_account;	-- If field dod not exists yet

UPDATE llx_accounting_bookkeeping SET subledger_account = numero_compte WHERE subledger_account IS NULL;

ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN label_compte varchar(255);
ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN code_journal varchar(32);

ALTER TABLE llx_accounting_bookkeeping ADD COLUMN label_operation varchar(255) AFTER label_compte;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN multicurrency_amount double AFTER sens;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN multicurrency_code varchar(255) AFTER multicurrency_amount;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN lettering_code varchar(255) AFTER multicurrency_code;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN date_lettering datetime AFTER lettering_code;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN journal_label varchar(255) AFTER code_journal;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN date_validated datetime AFTER validated;

DROP TABLE llx_accounting_bookkeeping_tmp;
CREATE TABLE llx_accounting_bookkeeping_tmp 
(
  rowid                 integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entity                integer DEFAULT 1 NOT NULL,	-- 					| multi company id
  doc_date              date NOT NULL,				-- FEC:PieceDate
  doc_type              varchar(30) NOT NULL,		-- FEC:PieceRef		| facture_client/reglement_client/facture_fournisseur/reglement_fournisseur
  doc_ref               varchar(300) NOT NULL,		-- 					| facture_client/reglement_client/... reference number
  fk_doc                integer NOT NULL,			-- 					| facture_client/reglement_client/... rowid
  fk_docdet             integer NOT NULL,			-- 					| facture_client/reglement_client/... line rowid
  thirdparty_code       varchar(32),				-- Third party code (customer or supplier) when record is saved (may help debug) 
  subledger_account     varchar(32),				-- FEC:CompAuxNum	| account number of subledger account
  subledger_label       varchar(255),				-- FEC:CompAuxLib	| label of subledger account
  numero_compte         varchar(32),				-- FEC:CompteNum	| account number
  label_compte          varchar(255) NOT NULL,		-- FEC:CompteLib	| label of account
  label_operation       varchar(255),				-- FEC:EcritureLib	| label of the operation
  debit                 double(24,8) NOT NULL,		-- FEC:Debit
  credit                double(24,8) NOT NULL,		-- FEC:Credit
  montant               double(24,8) NOT NULL,		-- FEC:Montant (Not necessary)
  sens                  varchar(1) DEFAULT NULL,	-- FEC:Sens (Not necessary)
  multicurrency_amount  double(24,8),				-- FEC:Montantdevise
  multicurrency_code    varchar(255),				-- FEC:Idevise
  lettering_code        varchar(255),				-- FEC:EcritureLet
  date_lettering        datetime,					-- FEC:DateLet
  fk_user_author        integer NOT NULL,			-- 					| user creating
  fk_user_modif         integer,					-- 					| user making last change
  date_creation         datetime,					-- FEC:EcritureDate	| creation date
  tms                   timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,					--					| date last modification 
  import_key            varchar(14),
  code_journal          varchar(32) NOT NULL,		-- FEC:JournalCode
  journal_label         varchar(255),				-- FEC:JournalLib
  piece_num             integer NOT NULL,			-- FEC:EcritureNum
  validated             tinyint DEFAULT 0 NOT NULL,	-- 					| 0 line not validated / 1 line validated (No deleting / No modification) 
  date_validated        datetime					-- FEC:ValidDate
) ENGINE=innodb;

ALTER TABLE llx_accounting_bookkeeping_tmp ADD INDEX idx_accounting_bookkeeping_tmp_doc_date (doc_date);
ALTER TABLE llx_accounting_bookkeeping_tmp ADD INDEX idx_accounting_bookkeeping_tmp_fk_docdet (fk_docdet);
ALTER TABLE llx_accounting_bookkeeping_tmp ADD INDEX idx_accounting_bookkeeping_tmp_numero_compte (numero_compte);
ALTER TABLE llx_accounting_bookkeeping_tmp ADD INDEX idx_accounting_bookkeeping_tmp_code_journal (code_journal);


ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN debit double(24,8);
ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN credit double(24,8);
ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN montant double(24,8);
ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN multicurrency_amount double(24,8);


ALTER TABLE llx_paiementfourn ADD COLUMN model_pdf varchar(255);
ALTER TABLE llx_paiementfourn ADD COLUMN fk_user_modif integer AFTER fk_user_author;

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
ALTER TABLE llx_expensereport_det ADD COLUMN vat_src_code varchar(10)  DEFAULT '';

DELETE FROM llx_const WHERE name = __ENCRYPT('ADHERENT_BANK_USE_AUTO')__;

UPDATE llx_const SET value = __ENCRYPT('moono-lisa')__   WHERE value = __ENCRYPT('moono')__       AND name = __ENCRYPT('FCKEDITOR_SKIN')__;
DELETE FROM llx_document_model where nom = 'fsfe.fr.php' and type='donation';

ALTER TABLE llx_product_price ADD COLUMN default_vat_code	varchar(10) AFTER tva_tx;
ALTER TABLE llx_product_customer_price ADD COLUMN default_vat_code	varchar(10) AFTER tva_tx;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN default_vat_code	varchar(10) AFTER tva_tx;

ALTER TABLE llx_user ADD COLUMN model_pdf varchar(255);
ALTER TABLE llx_usergroup ADD COLUMN model_pdf varchar(255);

INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES (__ENCRYPT('PRODUCT_ADDON_PDF_ODT_PATH')__, 1, __ENCRYPT('DOL_DATA_ROOT/doctemplates/products')__, 'chaine', 0, '');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES (__ENCRYPT('CONTRACT_ADDON_PDF_ODT_PATH')__, 1, __ENCRYPT('DOL_DATA_ROOT/doctemplates/contracts')__, 'chaine', 0, '');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES (__ENCRYPT('USERGROUP_ADDON_PDF_ODT_PATH')__, 1, __ENCRYPT('DOL_DATA_ROOT/doctemplates/usergroups')__, 'chaine', 0, '');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES (__ENCRYPT('USER_ADDON_PDF_ODT_PATH')__, 1, __ENCRYPT('DOL_DATA_ROOT/doctemplates/users')__, 'chaine', 0, '');

INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES (__ENCRYPT('MAIN_ENABLE_OVERWRITE_TRANSLATION')__, 1, __ENCRYPT('1')__, 'chaine', 0, 'Enable overwrote of translation');

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

ALTER TABLE llx_product_customer_price_log ADD COLUMN default_vat_code varchar(10);

UPDATE llx_contrat SET ref = rowid WHERE ref IS NULL OR ref = '';
ALTER TABLE llx_contratdet ADD COLUMN vat_src_code varchar(10) DEFAULT '';

CREATE TABLE llx_payment_various
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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


CREATE TABLE llx_default_values
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
entity integer DEFAULT 0, 
ref varchar(48),
datec datetime DEFAULT NULL,
tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
fk_user_author	integer,
fk_user_modif     integer,
fk_user_valid		integer,
fk_warehouse integer DEFAULT 0, 
status integer DEFAULT 0, 
title varchar(255) NOT NULL, 
date_inventory datetime DEFAULT NULL,
import_key  varchar(14)
)ENGINE=InnoDB;

CREATE TABLE llx_inventorydet 
( 
rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY, 
datec datetime DEFAULT NULL,
tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
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
)ENGINE=InnoDB;

ALTER TABLE llx_inventory ADD COLUMN datec datetime DEFAULT NULL;
ALTER TABLE llx_inventory ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_inventory ADD INDEX idx_inventory_tms (tms);
ALTER TABLE llx_inventory ADD INDEX idx_inventory_datec (datec);
ALTER TABLE llx_inventorydet ADD INDEX idx_inventorydet_tms (tms);
ALTER TABLE llx_inventorydet ADD INDEX idx_inventorydet_datec (datec);
ALTER TABLE llx_inventorydet ADD INDEX idx_inventorydet_fk_inventory (fk_inventory);

INSERT INTO llx_c_tva(fk_pays,taux,code,recuperableonly,note,active)                                                   VALUES (1, '8.5', '85', '0','VAT standard rate (DOM sauf Guyane et Saint-Martin)',0);
INSERT INTO llx_c_tva(fk_pays,taux,code,recuperableonly,note,active)                                                   VALUES (1, '8.5', '85NPR', '1','VAT standard rate (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par acheteur',0);
INSERT INTO llx_c_tva(fk_pays,taux,code,recuperableonly,localtax1,localtax1_type,note,active)                          VALUES (1, '8.5', '85NPROM', '1', 2, 3, 'VAT standard rate (DOM sauf Guyane et Saint-Martin), NPR, Octroi de Mer',0);
INSERT INTO llx_c_tva(fk_pays,taux,code,recuperableonly,localtax1,localtax1_type,localtax2,localtax2_type,note,active) VALUES (1, '8.5', '85NPROMOMR', '1', 2, 3, 2.5, 3, 'VAT standard rate (DOM sauf Guyane et Saint-Martin), NPR, Octroi de Mer et Octroi de Mer Regional',0);

ALTER TABLE llx_events MODIFY COLUMN ip varchar(250);

ALTER TABLE llx_facture ADD COLUMN fk_fac_rec_source integer;

DELETE FROM llx_c_actioncomm WHERE code IN ('AC_PROP','AC_COM','AC_FAC','AC_SHIP','AC_SUP_ORD','AC_SUP_INV') AND id NOT IN (SELECT DISTINCT fk_action FROM llx_actioncomm);

-- Fix: delete orphelin category.
DELETE FROM llx_categorie_product WHERE fk_categorie NOT IN (SELECT rowid FROM llx_categorie WHERE type = 0);
DELETE FROM llx_categorie_societe WHERE fk_categorie NOT IN (SELECT rowid FROM llx_categorie WHERE type IN (1, 2));
DELETE FROM llx_categorie_member WHERE fk_categorie NOT IN (SELECT rowid FROM llx_categorie WHERE type = 3);
DELETE FROM llx_categorie_contact WHERE fk_categorie NOT IN (SELECT rowid FROM llx_categorie WHERE type = 4);
DELETE FROM llx_categorie_project WHERE fk_categorie NOT IN (SELECT rowid FROM llx_categorie WHERE type = 6);

ALTER TABLE llx_inventory ADD COLUMN ref varchar(48);

-- VPGSQL8.2 ALTER TABLE llx_projet_task ALTER COLUMN planned_workload DROP NOT NULL;

CREATE TABLE llx_loan_schedule
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  fk_loan			integer,
  datec				datetime,         
  tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datep				datetime,         
  amount_capital	real DEFAULT 0,
  amount_insurance	real DEFAULT 0,
  amount_interest	real DEFAULT 0,
  fk_typepayment	integer NOT NULL,
  num_payment		varchar(50),
  note_private      text,
  note_public       text,
  fk_bank			integer NOT NULL,
  fk_user_creat		integer,          
  fk_user_modif		integer           
)ENGINE=innodb;

ALTER TABLE llx_tva ADD COLUMN datec date AFTER tms;

ALTER TABLE llx_user_rights ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_user_rights DROP FOREIGN KEY fk_user_rights_fk_user_user;
ALTER TABLE llx_user_rights DROP INDEX uk_user_rights;
ALTER TABLE llx_user_rights DROP INDEX fk_user;
ALTER TABLE llx_user_rights ADD UNIQUE INDEX uk_user_rights (entity, fk_user, fk_id);
DELETE FROM llx_user_rights WHERE fk_user NOT IN (select rowid from llx_user);
ALTER TABLE llx_user_rights ADD CONSTRAINT fk_user_rights_fk_user_user FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);

ALTER TABLE llx_usergroup_rights ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_usergroup_rights DROP FOREIGN KEY fk_usergroup_rights_fk_usergroup;
ALTER TABLE llx_usergroup_rights DROP INDEX fk_usergroup;
ALTER TABLE llx_usergroup_rights ADD UNIQUE INDEX uk_usergroup_rights (entity, fk_usergroup, fk_id);
ALTER TABLE llx_usergroup_rights ADD CONSTRAINT fk_usergroup_rights_fk_usergroup FOREIGN KEY (fk_usergroup) REFERENCES llx_usergroup (rowid);

-- For new module website

CREATE TABLE llx_website_page
(
	rowid         integer AUTO_INCREMENT NOT NULL PRIMARY KEY,
	fk_website    integer NOT NULL,
	pageurl       varchar(16) NOT NULL,
	title         varchar(255),						
	description   varchar(255),						
	keywords      varchar(255),
	content		  mediumtext,		-- text is not enough in size
    status        integer,
    fk_user_create integer,
    fk_user_modif  integer,
    date_creation  datetime,
	tms            timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;

ALTER TABLE llx_website_page ADD UNIQUE INDEX uk_website_page_url (fk_website,pageurl);

ALTER TABLE llx_website_page ADD CONSTRAINT fk_website_page_website FOREIGN KEY (fk_website) REFERENCES llx_website (rowid);

ALTER TABLE llx_website_page ADD COLUMN fk_user_create integer;
ALTER TABLE llx_website_page ADD COLUMN fk_user_modif integer; 


UPDATE llx_extrafields set elementtype='categorie' where elementtype='categories';


-- For new module blockedlog

CREATE TABLE llx_blockedlog 
( 
	rowid integer AUTO_INCREMENT PRIMARY KEY, 
	tms	timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	action varchar(50), 
	amounts real NOT NULL, 
	signature varchar(100) NOT NULL, 
	signature_line varchar(100) NOT NULL, 
	element varchar(50), 
	fk_object integer,
	ref_object varchar(100), 
	date_object	datetime,
	object_data	text,
	fk_user	integer,
	entity integer DEFAULT 1 NOT NULL, 
	certified integer
) ENGINE=innodb;

ALTER TABLE llx_blockedlog ADD INDEX signature (signature);
ALTER TABLE llx_blockedlog ADD INDEX fk_object_element (fk_object,element);
ALTER TABLE llx_blockedlog ADD INDEX entity (entity);
ALTER TABLE llx_blockedlog ADD INDEX fk_user (fk_user); 
ALTER TABLE llx_blockedlog ADD INDEX entity_action (entity,action);
ALTER TABLE llx_blockedlog ADD INDEX entity_action_certified (entity,action,certified);

CREATE TABLE llx_blockedlog_authority 
( 
	rowid integer AUTO_INCREMENT PRIMARY KEY, 
	blockchain longtext NOT NULL,
	signature varchar(100) NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;

ALTER TABLE llx_blockedlog_authority ADD INDEX signature (signature);

-- VMYSQL4.1 INSERT IGNORE INTO llx_product_lot (entity, fk_product, batch, eatby, sellby, datec, fk_user_creat, fk_user_modif) SELECT DISTINCT e.entity, ps.fk_product, pb.batch, pb.eatby, pb.sellby, pb.tms, e.fk_user_author, e.fk_user_author from llx_product_batch as pb, llx_product_stock as ps, llx_entrepot as e WHERE pb.fk_product_stock = ps.rowid AND ps.fk_entrepot = e.rowid;

UPDATE llx_bank SET label= '(SupplierInvoicePayment)' WHERE label= 'Règlement fournisseur';
UPDATE llx_bank SET label= '(CustomerInvoicePayment)' WHERE label= 'Règlement client';
UPDATE llx_bank SET label= '(payment_salary)' WHERE label LIKE 'Règlement salaire';

ALTER TABLE llx_mailing_cibles MODIFY COLUMN source_url varchar(255);

-- VPGSQL8.2 CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_website FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
-- VPGSQL8.2 CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_website_page FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();


CREATE TABLE llx_facture_rec_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_facture_rec_extrafields ADD INDEX idx_facture_rec_extrafields (fk_object);

CREATE TABLE llx_facturedet_rec_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object        integer NOT NULL,
  import_key       varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_facturedet_rec_extrafields ADD INDEX idx_facturedet_rec_extrafields (fk_object);

insert into llx_c_tva(rowid,fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active) values (1178, 117, 'C+S-5',   0, 2.5, '1', 2.5, '1', 0, 'CGST+SGST - Same state sales', 1);
insert into llx_c_tva(rowid,fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active) values (1179, 117, 'I-5'     ,   5,   0, '0',   0, '0', 0, 'IGST',      1);
insert into llx_c_tva(rowid,fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active) values (1180, 117, 'C+S-12',   0,   6, '1',   6, '1', 0, 'CGST+SGST - Same state sales', 1);
insert into llx_c_tva(rowid,fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active) values (1181, 117, 'I-12'     ,  12,   0, '0',   0, '0', 0, 'IGST',      1);
insert into llx_c_tva(rowid,fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active) values (1176, 117, 'C+S-18',  0,   9, '1',   9, '1', 0, 'CGST+SGST - Same state sales', 1);
insert into llx_c_tva(rowid,fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active) values (1177, 117, 'I-18'     , 18,   0, '0',   0, '0', 0, 'IGST',      1);
insert into llx_c_tva(rowid,fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active) values (1182, 117, 'C+S-28',  0,  14, '1',  14, '1', 0, 'CGST+SGST - Same state sales', 1);
insert into llx_c_tva(rowid,fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active) values (1183, 117, 'I-28'     , 28,   0, '0',   0, '0', 0, 'IGST',      1);
