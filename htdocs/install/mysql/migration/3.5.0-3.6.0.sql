--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.6.0 or higher.
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





ALTER TABLE llx_expedition MODIFY COLUMN height float;
ALTER TABLE llx_expedition MODIFY COLUMN width float;
ALTER TABLE llx_expedition MODIFY COLUMN size float;
ALTER TABLE llx_expedition MODIFY COLUMN weight float;

 
ALTER TABLE llx_societe DROP COLUMN datea;

ALTER TABLE llx_holiday ADD COLUMN fk_user_create integer;
ALTER TABLE llx_holiday ADD INDEX idx_holiday_fk_user_create (fk_user_create);
ALTER TABLE llx_holiday ADD INDEX idx_holiday_date_create (date_create);
ALTER TABLE llx_holiday ADD INDEX idx_holiday_fk_validator (fk_validator);

create table llx_c_email_templates
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  entity		  integer DEFAULT 1 NOT NULL,	  -- multi company id
  type_template   varchar(32),  -- template for which type of email (send invoice by email, send order, ...)
  datec           datetime,
  label           varchar(255),
  content         text
)ENGINE=innodb;


ALTER TABLE llx_bank_account MODIFY COLUMN account_number varchar(24);


-- delete foreign key that should never exists
ALTER TABLE llx_propal DROP FOREIGN KEY fk_propal_fk_currency;
ALTER TABLE llx_commande DROP FOREIGN KEY fk_commande_fk_currency;
ALTER TABLE llx_facture DROP FOREIGN KEY fk_facture_fk_currency;
ALTER TABLE llx_facture DROP FOREIGN KEY fk_societe_fk_currency;

ALTER TABLE llx_propal MODIFY COLUMN fk_currency varchar(3) NULL;
ALTER TABLE llx_commande MODIFY COLUMN fk_currency varchar(3) NULL;
ALTER TABLE llx_facture MODIFY COLUMN fk_currency varchar(3) NULL;
ALTER TABLE llx_societe MODIFY COLUMN fk_currency varchar(3) NULL;

ALTER TABLE llx_bookmark ADD COLUMN entity integer DEFAULT 1 NOT NULL;
ALTER TABLE llx_bookmark MODIFY COLUMN url varchar(255) NOT NULL;


-- VMYSQL4.1 ALTER TABLE llx_opensurvey_sondage MODIFY COLUMN tms timestamp DEFAULT '2001-01-01 00:00:00';

-- Clean corrupted values for tms
-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_opensurvey_sondage set tms = date_fin where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_opensurvey_sondage set tms = date_fin where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;
-- Remove default not null on date_fin
-- VMYSQL4.3 ALTER TABLE llx_opensurvey_sondage MODIFY COLUMN date_fin DATETIME NULL DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_opensurvey_sondage ALTER COLUMN date_fin DROP NOT NULL;

-- VMYSQL4.1 ALTER TABLE llx_opensurvey_sondage MODIFY COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP;


ALTER TABLE llx_opensurvey_sondage ADD COLUMN entity integer DEFAULT 1 NOT NULL;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN allow_comments tinyint NOT NULL DEFAULT 1;
-- ALTER TABLE llx_opensurvey_sondage DROP COLUMN survey_link_visible;
-- ALTER TABLE llx_opensurvey_sondage DROP INDEX idx_id_sondage_admin;
-- ALTER TABLE llx_opensurvey_sondage DROP COLUMN id_sondage_admin;
-- ALTER TABLE llx_opensurvey_sondage DROP COLUMN canedit;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN allow_spy tinyint NOT NULL DEFAULT 1 AFTER allow_comments;
-- ALTER TABLE llx_opensurvey_sondage DROP COLUMN origin;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN fk_user_creat integer AFTER nom_admin;
ALTER TABLE llx_opensurvey_sondage CHANGE COLUMN mailsonde mailsonde tinyint NOT NULL DEFAULT 0;
ALTER TABLE llx_opensurvey_sondage CHANGE COLUMN titre titre TEXT NOT NULL;
ALTER TABLE llx_opensurvey_sondage CHANGE COLUMN date_fin date_fin DATETIME NOT NULL;
ALTER TABLE llx_opensurvey_sondage CHANGE COLUMN format format VARCHAR(2) NOT NULL;
ALTER TABLE llx_opensurvey_sondage ADD COLUMN sujet TEXT;

ALTER TABLE llx_facture_rec CHANGE COLUMN usenewprice usenewprice INTEGER DEFAULT 0;

-- Uniformize index name to match http://wiki.dolibarr.org/index.php/Language_and_development_rules#SQL_rules
ALTER TABLE llx_c_type_contact DROP index idx_c_type_contact_uk;
ALTER TABLE llx_c_type_contact ADD UNIQUE INDEX uk_c_type_contact_id (element, source, code);
ALTER TABLE llx_c_tva ADD UNIQUE INDEX uk_c_tva_id (fk_pays, taux, recuperableonly);

ALTER TABLE llx_accountingaccount MODIFY COLUMN label varchar(255);

ALTER TABLE llx_projet_task ADD COLUMN  entity integer DEFAULT 1 NOT NULL AFTER ref;

create table llx_product_customer_price
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  entity				integer DEFAULT 1 NOT NULL,	   -- multi company id
  datec					datetime,
  tms					timestamp,
  fk_product			integer NOT NULL,
  fk_soc				integer NOT NULL,
  price						double(24,8) DEFAULT 0,
  price_ttc					double(24,8) DEFAULT 0,
  price_min					double(24,8) DEFAULT 0,
  price_min_ttc				double(24,8) DEFAULT 0,
  price_base_type			varchar(3)   DEFAULT 'HT',
  tva_tx					double(6,3),
  recuperableonly           integer NOT NULL DEFAULT '0',   -- Other NPR VAT
  localtax1_tx				double(6,3)  DEFAULT 0,         -- Other local VAT 1
  localtax2_tx				double(6,3)  DEFAULT 0,         -- Other local VAT 2
  fk_user				    integer,
  import_key			    varchar(14)                  -- Import key
)ENGINE=innodb;

ALTER TABLE llx_product_customer_price ADD INDEX idx_product_customer_price_fk_user (fk_user);
ALTER TABLE llx_product_customer_price ADD INDEX idx_product_customer_price_fk_soc (fk_soc);
ALTER TABLE llx_product_customer_price ADD UNIQUE INDEX uk_customer_price_fk_product_fk_soc (fk_product, fk_soc);

ALTER TABLE llx_product_customer_price ADD CONSTRAINT fk_product_customer_price_fk_user FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);
ALTER TABLE llx_product_customer_price ADD CONSTRAINT fk_customer_price_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product(rowid);
ALTER TABLE llx_product_customer_price ADD CONSTRAINT fk_customer_price_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);

ALTER TABLE llx_user ADD COLUMN barcode varchar(255) DEFAULT NULL;
ALTER TABLE llx_user ADD COLUMN fk_barcode_type integer DEFAULT 0;
ALTER TABLE llx_user ADD COLUMN nb_holiday integer DEFAULT 0;
ALTER TABLE llx_user ADD COLUMN salary double(24,8) DEFAULT NULL;

ALTER TABLE llx_product ADD COLUMN url varchar(255);

create table llx_product_customer_price_log
(
  rowid                       integer AUTO_INCREMENT PRIMARY KEY,
  entity				integer DEFAULT 1 NOT NULL,	   -- multi company id
  datec                       datetime,
  fk_product			integer NOT NULL,
  fk_soc				integer NOT NULL,
  price						double(24,8) DEFAULT 0,
  price_ttc					double(24,8) DEFAULT 0,
  price_min					double(24,8) DEFAULT 0,
  price_min_ttc				double(24,8) DEFAULT 0,
  price_base_type			varchar(3)   DEFAULT 'HT',
  tva_tx					double(6,3),
  recuperableonly           integer NOT NULL DEFAULT 0,   -- Other NPR VAT
  localtax1_tx				double(6,3)  DEFAULT 0,         -- Other local VAT 1
  localtax2_tx				double(6,3)  DEFAULT 0,         -- Other local VAT 2
  fk_user				integer,
 import_key			varchar(14)                  -- Import key
)ENGINE=innodb;

-- Batch number management
ALTER TABLE llx_product ADD COLUMN tobatch tinyint DEFAULT 0 NOT NULL;

CREATE TABLE llx_product_batch (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  tms timestamp,
  fk_product_stock integer NOT NULL,
  eatby datetime DEFAULT NULL,
  sellby datetime DEFAULT NULL,
  batch varchar(30) DEFAULT NULL,
  qty double NOT NULL DEFAULT 0,
  import_key varchar(14) DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE llx_expeditiondet_batch (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  fk_expeditiondet integer NOT NULL,
  eatby date DEFAULT NULL,
  sellby date DEFAULT NULL,
  batch varchar(30) DEFAULT NULL,
  qty double NOT NULL DEFAULT 0,
  fk_origin_stock integer NOT NULL
) ENGINE=InnoDB;

-- Salary payment in tax module
--DROP TABLE llx_payment_salary
CREATE TABLE llx_payment_salary (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  tms timestamp,
  fk_user integer NOT NULL,
  datep date,
  datev date,
  amount real NOT NULL DEFAULT 0,
  fk_typepayment integer NOT NULL,
  num_payment varchar(50),
  label varchar(255),
  datesp date,                       -- date de début de la période
  dateep date,                       -- date de fin de la période
  entity integer DEFAULT 1 NOT NULL,	-- multi company id
  note text,
  fk_bank integer,
  fk_user_creat integer,
  fk_user_modif integer
)ENGINE=innodb;


DELETE FROM llx_product_batch where fk_product_stock NOT IN (SELECT rowid from llx_product_stock);

ALTER TABLE llx_product_batch ADD INDEX idx_fk_product_stock (fk_product_stock);
ALTER TABLE llx_product_batch ADD CONSTRAINT fk_product_batch_fk_product_stock FOREIGN KEY (fk_product_stock) REFERENCES llx_product_stock (rowid);

DELETE FROM llx_expeditiondet_batch where fk_expeditiondet NOT IN (SELECT rowid from llx_expeditiondet);

ALTER TABLE llx_expeditiondet_batch ADD INDEX idx_fk_expeditiondet (fk_expeditiondet);
ALTER TABLE llx_expeditiondet_batch ADD CONSTRAINT fk_expeditiondet_batch_fk_expeditiondet FOREIGN KEY (fk_expeditiondet) REFERENCES llx_expeditiondet(rowid);


-- New 1074 : Stock mouvement link to origin
ALTER TABLE llx_stock_mouvement ADD fk_origin integer;
ALTER TABLE llx_stock_mouvement ADD origintype VARCHAR(32);

-- New 1300 : Add THM on user
ALTER TABLE llx_user ADD thm double(24,8) AFTER fk_user;
ALTER TABLE llx_projet_task_time ADD thm double(24,8) AFTER fk_user;


-- New : extrafield on categories
create table llx_categories_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_categories_extrafields ADD INDEX idx_categories_extrafields (fk_object);

update llx_product set barcode = null where barcode in ('', '-1', '0');
update llx_societe set barcode = null where barcode in ('', '-1', '0');

-- Add missing unique keys
ALTER TABLE llx_product ADD INDEX idx_product_barcode (barcode);
ALTER TABLE llx_product ADD UNIQUE INDEX uk_product_barcode (barcode, fk_barcode_type, entity);
ALTER TABLE llx_societe ADD INDEX idx_societe_barcode (barcode);
ALTER TABLE llx_societe ADD UNIQUE INDEX uk_societe_barcode (barcode, fk_barcode_type, entity);


ALTER TABLE llx_tva ADD COLUMN fk_typepayment integer NULL;	-- table may already contains data
ALTER TABLE llx_tva ADD COLUMN num_payment varchar(50);

-- Add missing action triggers
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (31,'PROPAL_CLOSE_SIGNED','Customer proposal closed signed','Executed when a customer proposal is closed signed','propal',31);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (32,'PROPAL_CLOSE_REFUSED','Customer proposal closed refused','Executed when a customer proposal is closed refused','propal',32);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (33,'BILL_SUPPLIER_CANCELED','Supplier invoice cancelled','Executed when a supplier invoice is cancelled','invoice_supplier',33);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (34,'MEMBER_MODIFY','Member modified','Executed when a member is modified','member',34);

-- Automatic events for tasks
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (35,'TASK_CREATE','Task created','Executed when a project task is created','project',35);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (36,'TASK_MODIFY','Task modified','Executed when a project task is modified','project',36);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (37,'TASK_DELETE','Task deleted','Executed when a project task is deleted','project',37);

-- New : category translation
create table llx_categorie_lang
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_category    integer      DEFAULT 0 NOT NULL,
  lang           varchar(5)   DEFAULT 0 NOT NULL,
  label          varchar(255) NOT NULL,
  description    text
)ENGINE=innodb;

ALTER TABLE llx_categorie_lang ADD UNIQUE INDEX uk_category_lang (fk_category, lang);
ALTER TABLE llx_categorie_lang ADD CONSTRAINT fk_category_lang_fk_category 	FOREIGN KEY (fk_category) REFERENCES llx_categorie (rowid);

-- Resource module
CREATE TABLE llx_resource
(
  rowid           		integer AUTO_INCREMENT PRIMARY KEY,
  entity          		integer,
  ref             		varchar(255),
  description     		text,
  fk_code_type_resource varchar(32),
  note_public     		text,
  note_private    		text,
  tms         			timestamp
)ENGINE=innodb;

ALTER TABLE llx_resource ADD INDEX fk_code_type_resource_idx (fk_code_type_resource);

CREATE TABLE llx_element_resources
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  resource_id     integer,
  resource_type	  varchar(64),
  element_id	  integer,
  element_type    varchar(64),
  busy			  integer,
  mandatory		  integer,
  fk_user_create   integer,
  tms             timestamp
)ENGINE=innodb;

ALTER TABLE llx_element_resources ADD UNIQUE INDEX idx_element_resources_idx1 (resource_id, resource_type, element_id, element_type);
ALTER TABLE llx_element_resources ADD INDEX idx_element_element_element_id (element_id);
-- Pas de contrainte sur resource_id et element_id car pointe sur differentes tables

create table llx_c_type_resource
(
  rowid      	integer  AUTO_INCREMENT PRIMARY KEY,
  code          varchar(32) NOT NULL,
  label 	    varchar(64)	NOT NULL,
  active  	    tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

-- Fix llx_c_type_resource when you update from a 3.6-beta
ALTER TABLE llx_c_type_resource CHANGE libelle label VARCHAR(64) NOT NULL;

ALTER TABLE llx_c_type_resource ADD UNIQUE INDEX uk_c_type_resource_id (label, code);

-- Fix :: account_parent must be an int, not an account number
DELETE FROM llx_accountingaccount;

INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  1,'PCG99-ABREGE','CAPIT', 'CAPITAL', '101', '1401', 'Capital', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  2,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '105', '1401', 'Ecarts de réévaluation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  3,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1061', '1401', 'Réserve légale', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  4,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1063', '1401', 'Réserves statutaires ou contractuelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  5,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1064', '1401', 'Réserves réglementées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  6,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1068', '1401', 'Autres réserves', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  7,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '108', '1401', 'Compte de l''exploitant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  8,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '12', '1401', 'Résultat de l''exercice', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  9,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '145', '1401', 'Amortissements dérogatoires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 10,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '146', '1401', 'Provision spéciale de réévaluation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 11,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '147', '1401', 'Plus-values réinvesties', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 12,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '148', '1401', 'Autres provisions réglementées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 13,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '15', '1401', 'Provisions pour risques et charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 14,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '16', '1401', 'Emprunts et dettes assimilees', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 15,'PCG99-ABREGE','IMMO',  'XXXXXX',   '20', '1402', 'Immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 16,'PCG99-ABREGE','IMMO',  'XXXXXX',  '201',	'15', 'Frais d''établissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 17,'PCG99-ABREGE','IMMO',  'XXXXXX',  '206',	'15', 'Droit au bail', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 18,'PCG99-ABREGE','IMMO',  'XXXXXX',  '207',	'15', 'Fonds commercial', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 19,'PCG99-ABREGE','IMMO',  'XXXXXX',  '208',	'15', 'Autres immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 20,'PCG99-ABREGE','IMMO',  'XXXXXX',   '21', '1402', 'Immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 21,'PCG99-ABREGE','IMMO',  'XXXXXX',   '23', '1402', 'Immobilisations en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 22,'PCG99-ABREGE','IMMO',  'XXXXXX',   '27', '1402', 'Autres immobilisations financieres', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 23,'PCG99-ABREGE','IMMO',  'XXXXXX',  '280', '1402', 'Amortissements des immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 24,'PCG99-ABREGE','IMMO',  'XXXXXX',  '281', '1402', 'Amortissements des immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 25,'PCG99-ABREGE','IMMO',  'XXXXXX',  '290', '1402', 'Provisions pour dépréciation des immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 26,'PCG99-ABREGE','IMMO',  'XXXXXX',  '291', '1402', 'Provisions pour dépréciation des immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 27,'PCG99-ABREGE','IMMO',  'XXXXXX',  '297', '1402', 'Provisions pour dépréciation des autres immobilisations financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 28,'PCG99-ABREGE','STOCK', 'XXXXXX',   '31', '1403', 'Matieres premières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 29,'PCG99-ABREGE','STOCK', 'XXXXXX',   '32', '1403', 'Autres approvisionnements', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 30,'PCG99-ABREGE','STOCK', 'XXXXXX',   '33', '1403', 'En-cours de production de biens', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 31,'PCG99-ABREGE','STOCK', 'XXXXXX',   '34', '1403', 'En-cours de production de services', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 32,'PCG99-ABREGE','STOCK', 'XXXXXX',   '35', '1403', 'Stocks de produits', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 33,'PCG99-ABREGE','STOCK', 'XXXXXX',   '37', '1403', 'Stocks de marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 34,'PCG99-ABREGE','STOCK', 'XXXXXX',  '391', '1403', 'Provisions pour dépréciation des matières premières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 35,'PCG99-ABREGE','STOCK', 'XXXXXX',  '392', '1403', 'Provisions pour dépréciation des autres approvisionnements', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 36,'PCG99-ABREGE','STOCK', 'XXXXXX',  '393', '1403', 'Provisions pour dépréciation des en-cours de production de biens', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 37,'PCG99-ABREGE','STOCK', 'XXXXXX',  '394', '1403', 'Provisions pour dépréciation des en-cours de production de services', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 38,'PCG99-ABREGE','STOCK', 'XXXXXX',  '395', '1403', 'Provisions pour dépréciation des stocks de produits', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 39,'PCG99-ABREGE','STOCK', 'XXXXXX',  '397', '1403', 'Provisions pour dépréciation des stocks de marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 40,'PCG99-ABREGE','TIERS', 'SUPPLIER','400', '1404', 'Fournisseurs et Comptes rattachés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 41,'PCG99-ABREGE','TIERS', 'XXXXXX',  '409', '1404', 'Fournisseurs débiteurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 42,'PCG99-ABREGE','TIERS', 'CUSTOMER','410', '1404', 'Clients et Comptes rattachés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 43,'PCG99-ABREGE','TIERS', 'XXXXXX',  '419', '1404', 'Clients créditeurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 44,'PCG99-ABREGE','TIERS', 'XXXXXX',  '421', '1404', 'Personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 45,'PCG99-ABREGE','TIERS', 'XXXXXX',  '428', '1404', 'Personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 46,'PCG99-ABREGE','TIERS', 'XXXXXX',   '43', '1404', 'Sécurité sociale et autres organismes sociaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 47,'PCG99-ABREGE','TIERS', 'XXXXXX',  '444', '1404', 'Etat - impôts sur bénéfice', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 48,'PCG99-ABREGE','TIERS', 'XXXXXX',  '445', '1404', 'Etat - Taxes sur chiffre affaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 49,'PCG99-ABREGE','TIERS', 'XXXXXX',  '447', '1404', 'Autres impôts, taxes et versements assimilés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 50,'PCG99-ABREGE','TIERS', 'XXXXXX',   '45', '1404', 'Groupe et associes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 51,'PCG99-ABREGE','TIERS', 'XXXXXX',  '455',	'50', 'Associés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 52,'PCG99-ABREGE','TIERS', 'XXXXXX',   '46', '1404', 'Débiteurs divers et créditeurs divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 53,'PCG99-ABREGE','TIERS', 'XXXXXX',   '47', '1404', 'Comptes transitoires ou d''attente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 54,'PCG99-ABREGE','TIERS', 'XXXXXX',  '481', '1404', 'Charges à répartir sur plusieurs exercices', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 55,'PCG99-ABREGE','TIERS', 'XXXXXX',  '486', '1404', 'Charges constatées d''avance', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 56,'PCG99-ABREGE','TIERS', 'XXXXXX',  '487', '1404', 'Produits constatés d''avance', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 57,'PCG99-ABREGE','TIERS', 'XXXXXX',  '491', '1404', 'Provisions pour dépréciation des comptes de clients', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 58,'PCG99-ABREGE','TIERS', 'XXXXXX',  '496', '1404', 'Provisions pour dépréciation des comptes de débiteurs divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 59,'PCG99-ABREGE','FINAN', 'XXXXXX',   '50', '1405', 'Valeurs mobilières de placement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 60,'PCG99-ABREGE','FINAN', 'BANK',     '51', '1405', 'Banques, établissements financiers et assimilés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 61,'PCG99-ABREGE','FINAN', 'CASH',     '53', '1405', 'Caisse', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 62,'PCG99-ABREGE','FINAN', 'XXXXXX',   '54', '1405', 'Régies d''avance et accréditifs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 63,'PCG99-ABREGE','FINAN', 'XXXXXX',   '58', '1405', 'Virements internes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 64,'PCG99-ABREGE','FINAN', 'XXXXXX',  '590', '1405', 'Provisions pour dépréciation des valeurs mobilières de placement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 65,'PCG99-ABREGE','CHARGE','PRODUCT',  '60', '1406', 'Achats', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 66,'PCG99-ABREGE','CHARGE','XXXXXX',  '603',	'65', 'Variations des stocks', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 67,'PCG99-ABREGE','CHARGE','SERVICE',  '61', '1406', 'Services extérieurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 68,'PCG99-ABREGE','CHARGE','XXXXXX',   '62', '1406', 'Autres services extérieurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 69,'PCG99-ABREGE','CHARGE','XXXXXX',   '63', '1406', 'Impôts, taxes et versements assimiles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 70,'PCG99-ABREGE','CHARGE','XXXXXX',  '641', '1406', 'Rémunérations du personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 71,'PCG99-ABREGE','CHARGE','XXXXXX',  '644', '1406', 'Rémunération du travail de l''exploitant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 72,'PCG99-ABREGE','CHARGE','SOCIAL',  '645', '1406', 'Charges de sécurité sociale et de prévoyance', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 73,'PCG99-ABREGE','CHARGE','XXXXXX',  '646', '1406', 'Cotisations sociales personnelles de l''exploitant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 74,'PCG99-ABREGE','CHARGE','XXXXXX',   '65', '1406', 'Autres charges de gestion courante', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 75,'PCG99-ABREGE','CHARGE','XXXXXX',   '66', '1406', 'Charges financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 76,'PCG99-ABREGE','CHARGE','XXXXXX',   '67', '1406', 'Charges exceptionnelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 77,'PCG99-ABREGE','CHARGE','XXXXXX',  '681', '1406', 'Dotations aux amortissements et aux provisions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 78,'PCG99-ABREGE','CHARGE','XXXXXX',  '686', '1406', 'Dotations aux amortissements et aux provisions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 79,'PCG99-ABREGE','CHARGE','XXXXXX',  '687', '1406', 'Dotations aux amortissements et aux provisions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 80,'PCG99-ABREGE','CHARGE','XXXXXX',  '691', '1406', 'Participation des salariés aux résultats', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 81,'PCG99-ABREGE','CHARGE','XXXXXX',  '695', '1406', 'Impôts sur les bénéfices', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 82,'PCG99-ABREGE','CHARGE','XXXXXX',  '697', '1406', 'Imposition forfaitaire annuelle des sociétés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 83,'PCG99-ABREGE','CHARGE','XXXXXX',  '699', '1406', 'Produits', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 84,'PCG99-ABREGE','PROD',  'PRODUCT', '701', '1407', 'Ventes de produits finis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 85,'PCG99-ABREGE','PROD',  'SERVICE', '706', '1407', 'Prestations de services', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 86,'PCG99-ABREGE','PROD',  'PRODUCT', '707', '1407', 'Ventes de marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 87,'PCG99-ABREGE','PROD',  'PRODUCT', '708', '1407', 'Produits des activités annexes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 88,'PCG99-ABREGE','PROD',  'XXXXXX',  '709', '1407', 'Rabais, remises et ristournes accordés par l''entreprise', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 89,'PCG99-ABREGE','PROD',  'XXXXXX',  '713', '1407', 'Variation des stocks', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 90,'PCG99-ABREGE','PROD',  'XXXXXX',   '72', '1407', 'Production immobilisée', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 91,'PCG99-ABREGE','PROD',  'XXXXXX',   '73', '1407', 'Produits nets partiels sur opérations à long terme', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 92,'PCG99-ABREGE','PROD',  'XXXXXX',   '74', '1407', 'Subventions d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 93,'PCG99-ABREGE','PROD',  'XXXXXX',   '75', '1407', 'Autres produits de gestion courante', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 94,'PCG99-ABREGE','PROD',  'XXXXXX',  '753', 	'93', 'Jetons de présence et rémunérations d''administrateurs, gérants,...', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 95,'PCG99-ABREGE','PROD',  'XXXXXX',  '754',	'93', 'Ristournes perçues des coopératives', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 96,'PCG99-ABREGE','PROD',  'XXXXXX',  '755',	'93', 'Quotes-parts de résultat sur opérations faites en commun', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 97,'PCG99-ABREGE','PROD',  'XXXXXX',   '76', '1407', 'Produits financiers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 98,'PCG99-ABREGE','PROD',  'XXXXXX',   '77', '1407', 'Produits exceptionnels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 99,'PCG99-ABREGE','PROD',  'XXXXXX',  '781', '1407', 'Reprises sur amortissements et provisions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (100,'PCG99-ABREGE','PROD',  'XXXXXX',  '786', '1407', 'Reprises sur provisions pour risques', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (101,'PCG99-ABREGE','PROD',  'XXXXXX',  '787', '1407', 'Reprises sur provisions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (102,'PCG99-ABREGE','PROD',  'XXXXXX',   '79', '1407', 'Transferts de charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1401,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1', '', 'Fonds propres, provisions pour risques et charges et dettes à plus d''un an', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1402,'PCG99-ABREGE','IMMO',  'XXXXXX', '2', '', 'Frais d''établissement. Actifs immobilisés et créances à plus d''un an', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1403,'PCG99-ABREGE','STOCK', 'XXXXXX', '3', '', 'Stock et commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1404,'PCG99-ABREGE','TIERS', 'XXXXXX', '4', '', 'Créances et dettes à un an au plus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1405,'PCG99-ABREGE','FINAN', 'XXXXXX', '5', '', 'Placement de trésorerie et de valeurs disponibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1406,'PCG99-ABREGE','CHARGE','XXXXXX', '6', '', 'Charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1407,'PCG99-ABREGE','PROD',  'XXXXXX', '7', '', 'Produits', '1');

INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (103,'PCG99-BASE','CAPIT', 'XXXXXX',   '10','1501', 'Capital  et réserves', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (104,'PCG99-BASE','CAPIT', 'CAPITAL', '101', '103', 'Capital', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (105,'PCG99-BASE','CAPIT', 'XXXXXX',  '104', '103', 'Primes liées au capital social', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (106,'PCG99-BASE','CAPIT', 'XXXXXX',  '105', '103', 'Ecarts de réévaluation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (107,'PCG99-BASE','CAPIT', 'XXXXXX',  '106', '103', 'Réserves', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (108,'PCG99-BASE','CAPIT', 'XXXXXX',  '107', '103', 'Ecart d''equivalence', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (109,'PCG99-BASE','CAPIT', 'XXXXXX',  '108', '103', 'Compte de l''exploitant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (110,'PCG99-BASE','CAPIT', 'XXXXXX',  '109', '103', 'Actionnaires : capital souscrit - non appelé', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (111,'PCG99-BASE','CAPIT', 'XXXXXX',   '11','1501', 'Report à nouveau (solde créditeur ou débiteur)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (112,'PCG99-BASE','CAPIT', 'XXXXXX',  '110', '111', 'Report à nouveau (solde créditeur)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (113,'PCG99-BASE','CAPIT', 'XXXXXX',  '119', '111', 'Report à nouveau (solde débiteur)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (114,'PCG99-BASE','CAPIT', 'XXXXXX',   '12','1501', 'Résultat de l''exercice (bénéfice ou perte)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (115,'PCG99-BASE','CAPIT', 'XXXXXX',  '120', '114', 'Résultat de l''exercice (bénéfice)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (116,'PCG99-BASE','CAPIT', 'XXXXXX',  '129', '114', 'Résultat de l''exercice (perte)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (117,'PCG99-BASE','CAPIT', 'XXXXXX',   '13','1501', 'Subventions d''investissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (118,'PCG99-BASE','CAPIT', 'XXXXXX',  '131', '117', 'Subventions d''équipement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (119,'PCG99-BASE','CAPIT', 'XXXXXX',  '138', '117', 'Autres subventions d''investissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (120,'PCG99-BASE','CAPIT', 'XXXXXX',  '139', '117', 'Subventions d''investissement inscrites au compte de résultat', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (121,'PCG99-BASE','CAPIT', 'XXXXXX',   '14','1501', 'Provisions réglementées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (122,'PCG99-BASE','CAPIT', 'XXXXXX',  '142', '121', 'Provisions réglementées relatives aux immobilisations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (123,'PCG99-BASE','CAPIT', 'XXXXXX',  '143', '121', 'Provisions réglementées relatives aux stocks', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (124,'PCG99-BASE','CAPIT', 'XXXXXX',  '144', '121', 'Provisions réglementées relatives aux autres éléments de l''actif', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (125,'PCG99-BASE','CAPIT', 'XXXXXX',  '145', '121', 'Amortissements dérogatoires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (126,'PCG99-BASE','CAPIT', 'XXXXXX',  '146', '121', 'Provision spéciale de réévaluation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (127,'PCG99-BASE','CAPIT', 'XXXXXX',  '147', '121', 'Plus-values réinvesties', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (128,'PCG99-BASE','CAPIT', 'XXXXXX',  '148', '121', 'Autres provisions réglementées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (129,'PCG99-BASE','CAPIT', 'XXXXXX',   '15','1501', 'Provisions pour risques et charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (130,'PCG99-BASE','CAPIT', 'XXXXXX',  '151', '129', 'Provisions pour risques', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (131,'PCG99-BASE','CAPIT', 'XXXXXX',  '153', '129', 'Provisions pour pensions et obligations similaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (132,'PCG99-BASE','CAPIT', 'XXXXXX',  '154', '129', 'Provisions pour restructurations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (133,'PCG99-BASE','CAPIT', 'XXXXXX',  '155', '129', 'Provisions pour impôts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (134,'PCG99-BASE','CAPIT', 'XXXXXX',  '156', '129', 'Provisions pour renouvellement des immobilisations (entreprises concessionnaires)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (135,'PCG99-BASE','CAPIT', 'XXXXXX',  '157', '129', 'Provisions pour charges à répartir sur plusieurs exercices', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (136,'PCG99-BASE','CAPIT', 'XXXXXX',  '158', '129', 'Autres provisions pour charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (137,'PCG99-BASE','CAPIT', 'XXXXXX',   '16','1501', 'Emprunts et dettes assimilees', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (138,'PCG99-BASE','CAPIT', 'XXXXXX',  '161', '137', 'Emprunts obligataires convertibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (139,'PCG99-BASE','CAPIT', 'XXXXXX',  '163', '137', 'Autres emprunts obligataires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (140,'PCG99-BASE','CAPIT', 'XXXXXX',  '164', '137', 'Emprunts auprès des établissements de crédit', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (141,'PCG99-BASE','CAPIT', 'XXXXXX',  '165', '137', 'Dépôts et cautionnements reçus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (142,'PCG99-BASE','CAPIT', 'XXXXXX',  '166', '137', 'Participation des salariés aux résultats', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (143,'PCG99-BASE','CAPIT', 'XXXXXX',  '167', '137', 'Emprunts et dettes assortis de conditions particulières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (144,'PCG99-BASE','CAPIT', 'XXXXXX',  '168', '137', 'Autres emprunts et dettes assimilées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (145,'PCG99-BASE','CAPIT', 'XXXXXX',  '169', '137', 'Primes de remboursement des obligations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (146,'PCG99-BASE','CAPIT', 'XXXXXX',   '17','1501', 'Dettes rattachées à des participations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (147,'PCG99-BASE','CAPIT', 'XXXXXX',  '171', '146', 'Dettes rattachées à des participations (groupe)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (148,'PCG99-BASE','CAPIT', 'XXXXXX',  '174', '146', 'Dettes rattachées à des participations (hors groupe)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (149,'PCG99-BASE','CAPIT', 'XXXXXX',  '178', '146', 'Dettes rattachées à des sociétés en participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (150,'PCG99-BASE','CAPIT', 'XXXXXX',   '18','1501', 'Comptes de liaison des établissements et sociétés en participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (151,'PCG99-BASE','CAPIT', 'XXXXXX',  '181', '150', 'Comptes de liaison des établissements', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (152,'PCG99-BASE','CAPIT', 'XXXXXX',  '186', '150', 'Biens et prestations de services échangés entre établissements (charges)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (153,'PCG99-BASE','CAPIT', 'XXXXXX',  '187', '150', 'Biens et prestations de services échangés entre établissements (produits)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (154,'PCG99-BASE','CAPIT', 'XXXXXX',  '188', '150', 'Comptes de liaison des sociétés en participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (155,'PCG99-BASE','IMMO',  'XXXXXX',   '20','1502', 'Immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (156,'PCG99-BASE','IMMO',  'XXXXXX',  '201', '155', 'Frais d''établissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (157,'PCG99-BASE','IMMO',  'XXXXXX',  '203', '155', 'Frais de recherche et de développement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (158,'PCG99-BASE','IMMO',  'XXXXXX',  '205', '155', 'Concessions et droits similaires, brevets, licences, marques, procédés, logiciels, droits et valeurs similaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (159,'PCG99-BASE','IMMO',  'XXXXXX',  '206', '155', 'Droit au bail', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (160,'PCG99-BASE','IMMO',  'XXXXXX',  '207', '155', 'Fonds commercial', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (161,'PCG99-BASE','IMMO',  'XXXXXX',  '208', '155', 'Autres immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (162,'PCG99-BASE','IMMO',  'XXXXXX',   '21','1502', 'Immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (163,'PCG99-BASE','IMMO',  'XXXXXX',  '211', '162', 'Terrains', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (164,'PCG99-BASE','IMMO',  'XXXXXX',  '212', '162', 'Agencements et aménagements de terrains', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (165,'PCG99-BASE','IMMO',  'XXXXXX',  '213', '162', 'Constructions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (166,'PCG99-BASE','IMMO',  'XXXXXX',  '214', '162', 'Constructions sur sol d''autrui', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (167,'PCG99-BASE','IMMO',  'XXXXXX',  '215', '162', 'Installations techniques, matériels et outillage industriels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (168,'PCG99-BASE','IMMO',  'XXXXXX',  '218', '162', 'Autres immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (169,'PCG99-BASE','IMMO',  'XXXXXX',   '22','1502', 'Immobilisations mises en concession', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (170,'PCG99-BASE','IMMO',  'XXXXXX',   '23','1502', 'Immobilisations en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (171,'PCG99-BASE','IMMO',  'XXXXXX',  '231', '170', 'Immobilisations corporelles en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (172,'PCG99-BASE','IMMO',  'XXXXXX',  '232', '170', 'Immobilisations incorporelles en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (173,'PCG99-BASE','IMMO',  'XXXXXX',  '237', '170', 'Avances et acomptes versés sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (174,'PCG99-BASE','IMMO',  'XXXXXX',  '238', '170', 'Avances et acomptes versés sur commandes d''immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (175,'PCG99-BASE','IMMO',  'XXXXXX',   '25','1502', 'Parts dans des entreprises liées et créances sur des entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (176,'PCG99-BASE','IMMO',  'XXXXXX',   '26','1502', 'Participations et créances rattachées à des participations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (177,'PCG99-BASE','IMMO',  'XXXXXX',  '261', '176', 'Titres de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (178,'PCG99-BASE','IMMO',  'XXXXXX',  '266', '176', 'Autres formes de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (179,'PCG99-BASE','IMMO',  'XXXXXX',  '267', '176', 'Créances rattachées à des participations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (180,'PCG99-BASE','IMMO',  'XXXXXX',  '268', '176', 'Créances rattachées à des sociétés en participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (181,'PCG99-BASE','IMMO',  'XXXXXX',  '269', '176', 'Versements restant à effectuer sur titres de participation non libérés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (182,'PCG99-BASE','IMMO',  'XXXXXX',   '27','1502', 'Autres immobilisations financieres', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (183,'PCG99-BASE','IMMO',  'XXXXXX',  '271', '183', 'Titres immobilisés autres que les titres immobilisés de l''activité de portefeuille (droit de propriété)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (184,'PCG99-BASE','IMMO',  'XXXXXX',  '272', '183', 'Titres immobilisés (droit de créance)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (185,'PCG99-BASE','IMMO',  'XXXXXX',  '273', '183', 'Titres immobilisés de l''activité de portefeuille', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (186,'PCG99-BASE','IMMO',  'XXXXXX',  '274', '183', 'Prêts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (187,'PCG99-BASE','IMMO',  'XXXXXX',  '275', '183', 'Dépôts et cautionnements versés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (188,'PCG99-BASE','IMMO',  'XXXXXX',  '276', '183', 'Autres créances immobilisées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (189,'PCG99-BASE','IMMO',  'XXXXXX',  '277', '183', '(Actions propres ou parts propres)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (190,'PCG99-BASE','IMMO',  'XXXXXX',  '279', '183', 'Versements restant à effectuer sur titres immobilisés non libérés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (191,'PCG99-BASE','IMMO',  'XXXXXX',   '28','1502', 'Amortissements des immobilisations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (192,'PCG99-BASE','IMMO',  'XXXXXX',  '280', '191', 'Amortissements des immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (193,'PCG99-BASE','IMMO',  'XXXXXX',  '281', '191', 'Amortissements des immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (194,'PCG99-BASE','IMMO',  'XXXXXX',  '282', '191', 'Amortissements des immobilisations mises en concession', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (195,'PCG99-BASE','IMMO',  'XXXXXX',   '29','1502', 'Dépréciations des immobilisations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (196,'PCG99-BASE','IMMO',  'XXXXXX',  '290', '195', 'Dépréciations des immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (197,'PCG99-BASE','IMMO',  'XXXXXX',  '291', '195', 'Dépréciations des immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (198,'PCG99-BASE','IMMO',  'XXXXXX',  '292', '195', 'Dépréciations des immobilisations mises en concession', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (199,'PCG99-BASE','IMMO',  'XXXXXX',  '293', '195', 'Dépréciations des immobilisations en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (200,'PCG99-BASE','IMMO',  'XXXXXX',  '296', '195', 'Provisions pour dépréciation des participations et créances rattachées à des participations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (201,'PCG99-BASE','IMMO',  'XXXXXX',  '297', '195', 'Provisions pour dépréciation des autres immobilisations financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (202,'PCG99-BASE','STOCK', 'XXXXXX',   '31','1503', 'Matières premières (et fournitures)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (203,'PCG99-BASE','STOCK', 'XXXXXX',  '311', '202', 'Matières (ou groupe) A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (204,'PCG99-BASE','STOCK', 'XXXXXX',  '312', '202', 'Matières (ou groupe) B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (205,'PCG99-BASE','STOCK', 'XXXXXX',  '317', '202', 'Fournitures A, B, C,', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (206,'PCG99-BASE','STOCK', 'XXXXXX',   '32','1503', 'Autres approvisionnements', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (207,'PCG99-BASE','STOCK', 'XXXXXX',  '321', '206', 'Matières consommables', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (208,'PCG99-BASE','STOCK', 'XXXXXX',  '322', '206', 'Fournitures consommables', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (209,'PCG99-BASE','STOCK', 'XXXXXX',  '326', '206', 'Emballages', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (210,'PCG99-BASE','STOCK', 'XXXXXX',   '33','1503', 'En-cours de production de biens', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (211,'PCG99-BASE','STOCK', 'XXXXXX',  '331', '210', 'Produits en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (212,'PCG99-BASE','STOCK', 'XXXXXX',  '335', '210', 'Travaux en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (213,'PCG99-BASE','STOCK', 'XXXXXX',   '34','1503', 'En-cours de production de services', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (214,'PCG99-BASE','STOCK', 'XXXXXX',  '341', '213', 'Etudes en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (215,'PCG99-BASE','STOCK', 'XXXXXX',  '345', '213', 'Prestations de services en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (216,'PCG99-BASE','STOCK', 'XXXXXX',   '35','1503', 'Stocks de produits', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (217,'PCG99-BASE','STOCK', 'XXXXXX',  '351', '216', 'Produits intermédiaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (218,'PCG99-BASE','STOCK', 'XXXXXX',  '355', '216', 'Produits finis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (219,'PCG99-BASE','STOCK', 'XXXXXX',  '358', '216', 'Produits résiduels (ou matières de récupération)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (220,'PCG99-BASE','STOCK', 'XXXXXX',   '37','1503', 'Stocks de marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (221,'PCG99-BASE','STOCK', 'XXXXXX',  '371', '220', 'Marchandises (ou groupe) A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (222,'PCG99-BASE','STOCK', 'XXXXXX',  '372', '220', 'Marchandises (ou groupe) B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (223,'PCG99-BASE','STOCK', 'XXXXXX',   '39','1503', 'Provisions pour dépréciation des stocks et en-cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (224,'PCG99-BASE','STOCK', 'XXXXXX',  '391', '223', 'Provisions pour dépréciation des matières premières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (225,'PCG99-BASE','STOCK', 'XXXXXX',  '392', '223', 'Provisions pour dépréciation des autres approvisionnements', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (226,'PCG99-BASE','STOCK', 'XXXXXX',  '393', '223', 'Provisions pour dépréciation des en-cours de production de biens', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (227,'PCG99-BASE','STOCK', 'XXXXXX',  '394', '223', 'Provisions pour dépréciation des en-cours de production de services', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (228,'PCG99-BASE','STOCK', 'XXXXXX',  '395', '223', 'Provisions pour dépréciation des stocks de produits', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (229,'PCG99-BASE','STOCK', 'XXXXXX',  '397', '223', 'Provisions pour dépréciation des stocks de marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (230,'PCG99-BASE','TIERS', 'XXXXXX',   '40','1504', 'Fournisseurs et Comptes rattachés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (231,'PCG99-BASE','TIERS', 'XXXXXX',  '400', '230', 'Fournisseurs et Comptes rattachés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (232,'PCG99-BASE','TIERS', 'SUPPLIER','401', '230', 'Fournisseurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (233,'PCG99-BASE','TIERS', 'XXXXXX',  '403', '230', 'Fournisseurs - Effets à payer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (234,'PCG99-BASE','TIERS', 'XXXXXX',  '404', '230', 'Fournisseurs d''immobilisations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (235,'PCG99-BASE','TIERS', 'XXXXXX',  '405', '230', 'Fournisseurs d''immobilisations - Effets à payer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (236,'PCG99-BASE','TIERS', 'XXXXXX',  '408', '230', 'Fournisseurs - Factures non parvenues', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (237,'PCG99-BASE','TIERS', 'XXXXXX',  '409', '230', 'Fournisseurs débiteurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (238,'PCG99-BASE','TIERS', 'XXXXXX',   '41','1504', 'Clients et comptes rattachés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (239,'PCG99-BASE','TIERS', 'XXXXXX',  '410', '238', 'Clients et Comptes rattachés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (240,'PCG99-BASE','TIERS', 'CUSTOMER','411', '238', 'Clients', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (241,'PCG99-BASE','TIERS', 'XXXXXX',  '413', '238', 'Clients - Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (242,'PCG99-BASE','TIERS', 'XXXXXX',  '416', '238', 'Clients douteux ou litigieux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (243,'PCG99-BASE','TIERS', 'XXXXXX',  '418', '238', 'Clients - Produits non encore facturés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (244,'PCG99-BASE','TIERS', 'XXXXXX',  '419', '238', 'Clients créditeurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (245,'PCG99-BASE','TIERS', 'XXXXXX',   '42','1504', 'Personnel et comptes rattachés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (246,'PCG99-BASE','TIERS', 'XXXXXX',  '421', '245', 'Personnel - Rémunérations dues', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (247,'PCG99-BASE','TIERS', 'XXXXXX',  '422', '245', 'Comités d''entreprises, d''établissement, ...', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (248,'PCG99-BASE','TIERS', 'XXXXXX',  '424', '245', 'Participation des salariés aux résultats', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (249,'PCG99-BASE','TIERS', 'XXXXXX',  '425', '245', 'Personnel - Avances et acomptes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (250,'PCG99-BASE','TIERS', 'XXXXXX',  '426', '245', 'Personnel - Dépôts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (251,'PCG99-BASE','TIERS', 'XXXXXX',  '427', '245', 'Personnel - Oppositions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (252,'PCG99-BASE','TIERS', 'XXXXXX',  '428', '245', 'Personnel - Charges à payer et produits à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (253,'PCG99-BASE','TIERS', 'XXXXXX',   '43','1504', 'Sécurité sociale et autres organismes sociaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (254,'PCG99-BASE','TIERS', 'XXXXXX',  '431', '253', 'Sécurité sociale', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (255,'PCG99-BASE','TIERS', 'XXXXXX',  '437', '253', 'Autres organismes sociaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (256,'PCG99-BASE','TIERS', 'XXXXXX',  '438', '253', 'Organismes sociaux - Charges à payer et produits à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (257,'PCG99-BASE','TIERS', 'XXXXXX',   '44','1504', 'État et autres collectivités publiques', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (258,'PCG99-BASE','TIERS', 'XXXXXX',  '441', '257', 'État - Subventions à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (259,'PCG99-BASE','TIERS', 'XXXXXX',  '442', '257', 'Etat - Impôts et taxes recouvrables sur des tiers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (260,'PCG99-BASE','TIERS', 'XXXXXX',  '443', '257', 'Opérations particulières avec l''Etat, les collectivités publiques, les organismes internationaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (261,'PCG99-BASE','TIERS', 'XXXXXX',  '444', '257', 'Etat - Impôts sur les bénéfices', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (262,'PCG99-BASE','TIERS', 'XXXXXX',  '445', '257', 'Etat - Taxes sur le chiffre d''affaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (263,'PCG99-BASE','TIERS', 'XXXXXX',  '446', '257', 'Obligations cautionnées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (264,'PCG99-BASE','TIERS', 'XXXXXX',  '447', '257', 'Autres impôts, taxes et versements assimilés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (265,'PCG99-BASE','TIERS', 'XXXXXX',  '448', '257', 'Etat - Charges à payer et produits à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (266,'PCG99-BASE','TIERS', 'XXXXXX',  '449', '257', 'Quotas d''émission à restituer à l''Etat', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (267,'PCG99-BASE','TIERS', 'XXXXXX',   '45','1504', 'Groupe et associes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (268,'PCG99-BASE','TIERS', 'XXXXXX',  '451', '267', 'Groupe', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (269,'PCG99-BASE','TIERS', 'XXXXXX',  '455', '267', 'Associés - Comptes courants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (270,'PCG99-BASE','TIERS', 'XXXXXX',  '456', '267', 'Associés - Opérations sur le capital', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (271,'PCG99-BASE','TIERS', 'XXXXXX',  '457', '267', 'Associés - Dividendes à payer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (272,'PCG99-BASE','TIERS', 'XXXXXX',  '458', '267', 'Associés - Opérations faites en commun et en G.I.E.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (273,'PCG99-BASE','TIERS', 'XXXXXX',   '46','1504', 'Débiteurs divers et créditeurs divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (274,'PCG99-BASE','TIERS', 'XXXXXX',  '462', '273', 'Créances sur cessions d''immobilisations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (275,'PCG99-BASE','TIERS', 'XXXXXX',  '464', '273', 'Dettes sur acquisitions de valeurs mobilières de placement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (276,'PCG99-BASE','TIERS', 'XXXXXX',  '465', '273', 'Créances sur cessions de valeurs mobilières de placement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (277,'PCG99-BASE','TIERS', 'XXXXXX',  '467', '273', 'Autres comptes débiteurs ou créditeurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (278,'PCG99-BASE','TIERS', 'XXXXXX',  '468', '273', 'Divers - Charges à payer et produits à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (279,'PCG99-BASE','TIERS', 'XXXXXX',   '47','1504', 'Comptes transitoires ou d''attente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (280,'PCG99-BASE','TIERS', 'XXXXXX',  '471', '279', 'Comptes d''attente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (281,'PCG99-BASE','TIERS', 'XXXXXX',  '476', '279', 'Différence de conversion - Actif', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (282,'PCG99-BASE','TIERS', 'XXXXXX',  '477', '279', 'Différences de conversion - Passif', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (283,'PCG99-BASE','TIERS', 'XXXXXX',  '478', '279', 'Autres comptes transitoires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (284,'PCG99-BASE','TIERS', 'XXXXXX',   '48','1504', 'Comptes de régularisation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (285,'PCG99-BASE','TIERS', 'XXXXXX',  '481', '284', 'Charges à répartir sur plusieurs exercices', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (286,'PCG99-BASE','TIERS', 'XXXXXX',  '486', '284', 'Charges constatées d''avance', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (287,'PCG99-BASE','TIERS', 'XXXXXX',  '487', '284', 'Produits constatés d''avance', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (288,'PCG99-BASE','TIERS', 'XXXXXX',  '488', '284', 'Comptes de répartition périodique des charges et des produits', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (289,'PCG99-BASE','TIERS', 'XXXXXX',  '489', '284', 'Quotas d''émission alloués par l''Etat', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (290,'PCG99-BASE','TIERS', 'XXXXXX',   '49','1504', 'Provisions pour dépréciation des comptes de tiers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (291,'PCG99-BASE','TIERS', 'XXXXXX',  '491', '290', 'Provisions pour dépréciation des comptes de clients', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (292,'PCG99-BASE','TIERS', 'XXXXXX',  '495', '290', 'Provisions pour dépréciation des comptes du groupe et des associés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (293,'PCG99-BASE','TIERS', 'XXXXXX',  '496', '290', 'Provisions pour dépréciation des comptes de débiteurs divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (294,'PCG99-BASE','FINAN', 'XXXXXX',   '50','1505', 'Valeurs mobilières de placement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (295,'PCG99-BASE','FINAN', 'XXXXXX',  '501', '294', 'Parts dans des entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (296,'PCG99-BASE','FINAN', 'XXXXXX',  '502', '294', 'Actions propres', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (297,'PCG99-BASE','FINAN', 'XXXXXX',  '503', '294', 'Actions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (298,'PCG99-BASE','FINAN', 'XXXXXX',  '504', '294', 'Autres titres conférant un droit de propriété', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (299,'PCG99-BASE','FINAN', 'XXXXXX',  '505', '294', 'Obligations et bons émis par la société et rachetés par elle', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (300,'PCG99-BASE','FINAN', 'XXXXXX',  '506', '294', 'Obligations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (301,'PCG99-BASE','FINAN', 'XXXXXX',  '507', '294', 'Bons du Trésor et bons de caisse à court terme', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (302,'PCG99-BASE','FINAN', 'XXXXXX',  '508', '294', 'Autres valeurs mobilières de placement et autres créances assimilées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (303,'PCG99-BASE','FINAN', 'XXXXXX',  '509', '294', 'Versements restant à effectuer sur valeurs mobilières de placement non libérées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (304,'PCG99-BASE','FINAN', 'XXXXXX',   '51','1505', 'Banques, établissements financiers et assimilés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (305,'PCG99-BASE','FINAN', 'XXXXXX',  '511', '304', 'Valeurs à l''encaissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (306,'PCG99-BASE','FINAN', 'BANK',    '512', '304', 'Banques', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (307,'PCG99-BASE','FINAN', 'XXXXXX',  '514', '304', 'Chèques postaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (308,'PCG99-BASE','FINAN', 'XXXXXX',  '515', '304', 'Caisses du Trésor et des établissements publics', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (309,'PCG99-BASE','FINAN', 'XXXXXX',  '516', '304', 'Sociétés de bourse', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (310,'PCG99-BASE','FINAN', 'XXXXXX',  '517', '304', 'Autres organismes financiers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (311,'PCG99-BASE','FINAN', 'XXXXXX',  '518', '304', 'Intérêts courus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (312,'PCG99-BASE','FINAN', 'XXXXXX',  '519', '304', 'Concours bancaires courants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (313,'PCG99-BASE','FINAN', 'XXXXXX',   '52','1505', 'Instruments de trésorerie', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (314,'PCG99-BASE','FINAN', 'CASH',     '53','1505', 'Caisse', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (315,'PCG99-BASE','FINAN', 'XXXXXX',  '531', '314', 'Caisse siège social', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (316,'PCG99-BASE','FINAN', 'XXXXXX',  '532', '314', 'Caisse succursale (ou usine) A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (317,'PCG99-BASE','FINAN', 'XXXXXX',  '533', '314', 'Caisse succursale (ou usine) B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (318,'PCG99-BASE','FINAN', 'XXXXXX',   '54','1505', 'Régies d''avance et accréditifs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (319,'PCG99-BASE','FINAN', 'XXXXXX',   '58','1505', 'Virements internes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (320,'PCG99-BASE','FINAN', 'XXXXXX',   '59','1505', 'Provisions pour dépréciation des comptes financiers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (321,'PCG99-BASE','FINAN', 'XXXXXX',  '590', '320', 'Provisions pour dépréciation des valeurs mobilières de placement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (322,'PCG99-BASE','CHARGE','PRODUCT',  '60','1506', 'Achats', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (323,'PCG99-BASE','CHARGE','XXXXXX',  '601', '322', 'Achats stockés - Matières premières (et fournitures)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (324,'PCG99-BASE','CHARGE','XXXXXX',  '602', '322', 'Achats stockés - Autres approvisionnements', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (325,'PCG99-BASE','CHARGE','XXXXXX',  '603', '322', 'Variations des stocks (approvisionnements et marchandises)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (326,'PCG99-BASE','CHARGE','XXXXXX',  '604', '322', 'Achats stockés - Matières premières (et fournitures)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (327,'PCG99-BASE','CHARGE','XXXXXX',  '605', '322', 'Achats de matériel, équipements et travaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (328,'PCG99-BASE','CHARGE','XXXXXX',  '606', '322', 'Achats non stockés de matière et fournitures', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (329,'PCG99-BASE','CHARGE','XXXXXX',  '607', '322', 'Achats de marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (330,'PCG99-BASE','CHARGE','XXXXXX',  '608', '322', '(Compte réservé, le cas échéant, à la récapitulation des frais accessoires incorporés aux achats)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (331,'PCG99-BASE','CHARGE','XXXXXX',  '609', '322', 'Rabais, remises et ristournes obtenus sur achats', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (332,'PCG99-BASE','CHARGE','SERVICE',  '61','1506', 'Services extérieurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (333,'PCG99-BASE','CHARGE','XXXXXX',  '611', '332', 'Sous-traitance générale', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (334,'PCG99-BASE','CHARGE','XXXXXX',  '612', '332', 'Redevances de crédit-bail', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (335,'PCG99-BASE','CHARGE','XXXXXX',  '613', '332', 'Locations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (336,'PCG99-BASE','CHARGE','XXXXXX',  '614', '332', 'Charges locatives et de copropriété', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (337,'PCG99-BASE','CHARGE','XXXXXX',  '615', '332', 'Entretien et réparations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (338,'PCG99-BASE','CHARGE','XXXXXX',  '616', '332', 'Primes d''assurances', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (339,'PCG99-BASE','CHARGE','XXXXXX',  '617', '332', 'Etudes et recherches', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (340,'PCG99-BASE','CHARGE','XXXXXX',  '618', '332', 'Divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (341,'PCG99-BASE','CHARGE','XXXXXX',  '619', '332', 'Rabais, remises et ristournes obtenus sur services extérieurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (342,'PCG99-BASE','CHARGE','XXXXXX',   '62','1506', 'Autres services extérieurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (343,'PCG99-BASE','CHARGE','XXXXXX',  '621', '342', 'Personnel extérieur à l''entreprise', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (344,'PCG99-BASE','CHARGE','XXXXXX',  '622', '342', 'Rémunérations d''intermédiaires et honoraires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (345,'PCG99-BASE','CHARGE','XXXXXX',  '623', '342', 'Publicité, publications, relations publiques', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (346,'PCG99-BASE','CHARGE','XXXXXX',  '624', '342', 'Transports de biens et transports collectifs du personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (347,'PCG99-BASE','CHARGE','XXXXXX',  '625', '342', 'Déplacements, missions et réceptions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (348,'PCG99-BASE','CHARGE','XXXXXX',  '626', '342', 'Frais postaux et de télécommunications', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (349,'PCG99-BASE','CHARGE','XXXXXX',  '627', '342', 'Services bancaires et assimilés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (350,'PCG99-BASE','CHARGE','XXXXXX',  '628', '342', 'Divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (351,'PCG99-BASE','CHARGE','XXXXXX',  '629', '342', 'Rabais, remises et ristournes obtenus sur autres services extérieurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (352,'PCG99-BASE','CHARGE','XXXXXX',   '63','1506', 'Impôts, taxes et versements assimilés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (353,'PCG99-BASE','CHARGE','XXXXXX',  '631', '352', 'Impôts, taxes et versements assimilés sur rémunérations (administrations des impôts)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (354,'PCG99-BASE','CHARGE','XXXXXX',  '633', '352', 'Impôts, taxes et versements assimilés sur rémunérations (autres organismes)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (355,'PCG99-BASE','CHARGE','XXXXXX',  '635', '352', 'Autres impôts, taxes et versements assimilés (administrations des impôts)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (356,'PCG99-BASE','CHARGE','XXXXXX',  '637', '352', 'Autres impôts, taxes et versements assimilés (autres organismes)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (357,'PCG99-BASE','CHARGE','XXXXXX',   '64','1506', 'Charges de personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (358,'PCG99-BASE','CHARGE','XXXXXX',  '641', '357', 'Rémunérations du personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (359,'PCG99-BASE','CHARGE','XXXXXX',  '644', '357', 'Rémunération du travail de l''exploitant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (360,'PCG99-BASE','CHARGE','SOCIAL',  '645', '357', 'Charges de sécurité sociale et de prévoyance', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (361,'PCG99-BASE','CHARGE','XXXXXX',  '646', '357', 'Cotisations sociales personnelles de l''exploitant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (362,'PCG99-BASE','CHARGE','XXXXXX',  '647', '357', 'Autres charges sociales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (363,'PCG99-BASE','CHARGE','XXXXXX',  '648', '357', 'Autres charges de personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (364,'PCG99-BASE','CHARGE','XXXXXX',   '65','1506', 'Autres charges de gestion courante', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (365,'PCG99-BASE','CHARGE','XXXXXX',  '651', '364', 'Redevances pour concessions, brevets, licences, marques, procédés, logiciels, droits et valeurs similaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (366,'PCG99-BASE','CHARGE','XXXXXX',  '653', '364', 'Jetons de présence', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (367,'PCG99-BASE','CHARGE','XXXXXX',  '654', '364', 'Pertes sur créances irrécouvrables', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (368,'PCG99-BASE','CHARGE','XXXXXX',  '655', '364', 'Quote-part de résultat sur opérations faites en commun', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (369,'PCG99-BASE','CHARGE','XXXXXX',  '658', '364', 'Charges diverses de gestion courante', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (370,'PCG99-BASE','CHARGE','XXXXXX',   '66','1506', 'Charges financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (371,'PCG99-BASE','CHARGE','XXXXXX',  '661', '370', 'Charges d''intérêts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (372,'PCG99-BASE','CHARGE','XXXXXX',  '664', '370', 'Pertes sur créances liées à des participations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (373,'PCG99-BASE','CHARGE','XXXXXX',  '665', '370', 'Escomptes accordés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (374,'PCG99-BASE','CHARGE','XXXXXX',  '666', '370', 'Pertes de change', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (375,'PCG99-BASE','CHARGE','XXXXXX',  '667', '370', 'Charges nettes sur cessions de valeurs mobilières de placement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (376,'PCG99-BASE','CHARGE','XXXXXX',  '668', '370', 'Autres charges financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (377,'PCG99-BASE','CHARGE','XXXXXX',   '67','1506', 'Charges exceptionnelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (378,'PCG99-BASE','CHARGE','XXXXXX',  '671', '377', 'Charges exceptionnelles sur opérations de gestion', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (379,'PCG99-BASE','CHARGE','XXXXXX',  '672', '377', '(Compte à la disposition des entités pour enregistrer, en cours d''exercice, les charges sur exercices antérieurs)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (380,'PCG99-BASE','CHARGE','XXXXXX',  '675', '377', 'Valeurs comptables des éléments d''actif cédés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (381,'PCG99-BASE','CHARGE','XXXXXX',  '678', '377', 'Autres charges exceptionnelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (382,'PCG99-BASE','CHARGE','XXXXXX',   '68','1506', 'Dotations aux amortissements et aux provisions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (383,'PCG99-BASE','CHARGE','XXXXXX',  '681', '382', 'Dotations aux amortissements et aux provisions - Charges d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (384,'PCG99-BASE','CHARGE','XXXXXX',  '686', '382', 'Dotations aux amortissements et aux provisions - Charges financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (385,'PCG99-BASE','CHARGE','XXXXXX',  '687', '382', 'Dotations aux amortissements et aux provisions - Charges exceptionnelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (386,'PCG99-BASE','CHARGE','XXXXXX',   '69','1506', 'Participation des salariés - impôts sur les bénéfices et assimiles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (387,'PCG99-BASE','CHARGE','XXXXXX',  '691', '386', 'Participation des salariés aux résultats', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (388,'PCG99-BASE','CHARGE','XXXXXX',  '695', '386', 'Impôts sur les bénéfices', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (389,'PCG99-BASE','CHARGE','XXXXXX',  '696', '386', 'Suppléments d''impôt sur les sociétés liés aux distributions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (390,'PCG99-BASE','CHARGE','XXXXXX',  '697', '386', 'Imposition forfaitaire annuelle des sociétés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (391,'PCG99-BASE','CHARGE','XXXXXX',  '698', '386', 'Intégration fiscale', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (392,'PCG99-BASE','CHARGE','XXXXXX',  '699', '386', 'Produits - Reports en arrière des déficits', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (393,'PCG99-BASE','PROD',  'XXXXXX',   '70','1507', 'Ventes de produits fabriqués, prestations de services, marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (394,'PCG99-BASE','PROD',  'PRODUCT', '701', '393', 'Ventes de produits finis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (395,'PCG99-BASE','PROD',  'XXXXXX',  '702', '393', 'Ventes de produits intermédiaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (396,'PCG99-BASE','PROD',  'XXXXXX',  '703', '393', 'Ventes de produits résiduels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (397,'PCG99-BASE','PROD',  'XXXXXX',  '704', '393', 'Travaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (398,'PCG99-BASE','PROD',  'XXXXXX',  '705', '393', 'Etudes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (399,'PCG99-BASE','PROD',  'SERVICE', '706', '393', 'Prestations de services', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (400,'PCG99-BASE','PROD',  'PRODUCT', '707', '393', 'Ventes de marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (401,'PCG99-BASE','PROD',  'PRODUCT', '708', '393', 'Produits des activités annexes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (402,'PCG99-BASE','PROD',  'XXXXXX',  '709', '393', 'Rabais, remises et ristournes accordés par l''entreprise', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (403,'PCG99-BASE','PROD',  'XXXXXX',   '71','1507', 'Production stockée (ou déstockage)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (404,'PCG99-BASE','PROD',  'XXXXXX',  '713', '403', 'Variation des stocks (en-cours de production, produits)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (405,'PCG99-BASE','PROD',  'XXXXXX',   '72','1507', 'Production immobilisée', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (406,'PCG99-BASE','PROD',  'XXXXXX',  '721', '405', 'Immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (407,'PCG99-BASE','PROD',  'XXXXXX',  '722', '405', 'Immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (408,'PCG99-BASE','PROD',  'XXXXXX',   '74','1507', 'Subventions d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (409,'PCG99-BASE','PROD',  'XXXXXX',   '75','1507', 'Autres produits de gestion courante', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (410,'PCG99-BASE','PROD',  'XXXXXX',  '751', '409', 'Redevances pour concessions, brevets, licences, marques, procédés, logiciels, droits et valeurs similaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (411,'PCG99-BASE','PROD',  'XXXXXX',  '752', '409', 'Revenus des immeubles non affectés à des activités professionnelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (412,'PCG99-BASE','PROD',  'XXXXXX',  '753', '409', 'Jetons de présence et rémunérations d''administrateurs, gérants,...', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (413,'PCG99-BASE','PROD',  'XXXXXX',  '754', '409', 'Ristournes perçues des coopératives (provenant des excédents)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (414,'PCG99-BASE','PROD',  'XXXXXX',  '755', '409', 'Quotes-parts de résultat sur opérations faites en commun', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (415,'PCG99-BASE','PROD',  'XXXXXX',  '758', '409', 'Produits divers de gestion courante', '1'); 
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (416,'PCG99-BASE','PROD',  'XXXXXX',   '76','1507', 'Produits financiers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (417,'PCG99-BASE','PROD',  'XXXXXX',  '761', '416', 'Produits de participations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (418,'PCG99-BASE','PROD',  'XXXXXX',  '762', '416', 'Produits des autres immobilisations financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (419,'PCG99-BASE','PROD',  'XXXXXX',  '763', '416', 'Revenus des autres créances', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (420,'PCG99-BASE','PROD',  'XXXXXX',  '764', '416', 'Revenus des valeurs mobilières de placement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (421,'PCG99-BASE','PROD',  'XXXXXX',  '765', '416', 'Escomptes obtenus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (422,'PCG99-BASE','PROD',  'XXXXXX',  '766', '416', 'Gains de change', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (423,'PCG99-BASE','PROD',  'XXXXXX',  '767', '416', 'Produits nets sur cessions de valeurs mobilières de placement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (424,'PCG99-BASE','PROD',  'XXXXXX',  '768', '416', 'Autres produits financiers', '1'); 
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (425,'PCG99-BASE','PROD',  'XXXXXX',   '77','1507', 'Produits exceptionnels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (426,'PCG99-BASE','PROD',  'XXXXXX',  '771', '425', 'Produits exceptionnels sur opérations de gestion', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (427,'PCG99-BASE','PROD',  'XXXXXX',  '772', '425', '(Compte à la disposition des entités pour enregistrer, en cours d''exercice, les produits sur exercices antérieurs)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (428,'PCG99-BASE','PROD',  'XXXXXX',  '775', '425', 'Produits des cessions d''éléments d''actif', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (429,'PCG99-BASE','PROD',  'XXXXXX',  '777', '425', 'Quote-part des subventions d''investissement virée au résultat de l''exercice', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (430,'PCG99-BASE','PROD',  'XXXXXX',  '778', '425', 'Autres produits exceptionnels', '1'); 
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (431,'PCG99-BASE','PROD',  'XXXXXX',   '78','1507', 'Reprises sur amortissements et provisions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (432,'PCG99-BASE','PROD',  'XXXXXX',  '781', '431', 'Reprises sur amortissements et provisions (à inscrire dans les produits d''exploitation)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (433,'PCG99-BASE','PROD',  'XXXXXX',  '786', '431', 'Reprises sur provisions pour risques (à inscrire dans les produits financiers)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (434,'PCG99-BASE','PROD',  'XXXXXX',  '787', '431', 'Reprises sur provisions (à inscrire dans les produits exceptionnels)', '1'); 
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (435,'PCG99-BASE','PROD',  'XXXXXX',   '79','1507', 'Transferts de charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (436,'PCG99-BASE','PROD',  'XXXXXX',  '791', '435', 'Transferts de charges d''exploitation ', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (437,'PCG99-BASE','PROD',  'XXXXXX',  '796', '435', 'Transferts de charges financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (438,'PCG99-BASE','PROD',  'XXXXXX',  '797', '435', 'Transferts de charges exceptionnelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1501,'PCG99-BASE','CAPIT', 'XXXXXX', '1', '', 'Fonds propres, provisions pour risques et charges et dettes à plus d''un an', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1502,'PCG99-BASE','IMMO',  'XXXXXX', '2', '', 'Frais d''établissement. Actifs immobilisés et créances à plus d''un an', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1503,'PCG99-BASE','STOCK', 'XXXXXX', '3', '', 'Stock et commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1504,'PCG99-BASE','TIERS', 'XXXXXX', '4', '', 'Créances et dettes à un an au plus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1505,'PCG99-BASE','FINAN', 'XXXXXX', '5', '', 'Placement de trésorerie et de valeurs disponibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1506,'PCG99-BASE','CHARGE','XXXXXX', '6', '', 'Charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1507,'PCG99-BASE','PROD',  'XXXXXX', '7', '', 'Produits', '1');

-- Plan comptable BE PCMN-BASE
INSERT INTO llx_accounting_system (rowid, pcg_version, fk_pays, label, active) VALUES (3, 'PCMN-BASE', '2', 'The base accountancy belgium plan', '1');

INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (439, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '10', '1351', 'Capital', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (440, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '100', '439', 'Capital souscrit ou capital personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (441, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1000', '440', 'Capital non amorti', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (442, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1001', '440', 'Capital amorti', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (443, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '101', '439', 'Capital non appelé', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (444, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '109', '439', 'Compte de l''exploitant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (445, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1090', '444', 'Opérations courantes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (446, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1091', '444', 'Impôts personnels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (447, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1092', '444', 'Rémunérations et autres avantages', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (448, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '11', '1351', 'Primes d''émission', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (449, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '12', '1351', 'Plus-values de réévaluation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (450, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '120', '449', 'Plus-values de réévaluation sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (451, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1200', '450', 'Plus-values de réévaluation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (452, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1201', '450', 'Reprises de réductions de valeur', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (453, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '121', '449', 'Plus-values de réévaluation sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (454, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1210', '453', 'Plus-values de réévaluation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (455, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1211', '453', 'Reprises de réductions de valeur', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (456, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '122', '449', 'Plus-values de réévaluation sur immobilisations financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (457, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1220', '456', 'Plus-values de réévaluation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (458, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1221', '456', 'Reprises de réductions de valeur', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (459, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '123', '449', 'Plus-values de réévaluation sur stocks', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (460, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '124', '449', 'Reprises de réductions de valeur sur placements de trésorerie', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (461, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '13', '1351', 'Réserve', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (462, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '130', '461', 'Réserve légale', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (463, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '131', '461', 'Réserves indisponibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (464, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1310', '463', 'Réserve pour actions propres', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (465, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1311', '463', 'Autres réserves indisponibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (466, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '132', '461', 'Réserves immunisées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (467, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '133', '461', 'Réserves disponibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (468, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1330', '467', 'Réserve pour régularisation de dividendes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (469, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1331', '467', 'Réserve pour renouvellement des immobilisations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (470, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1332', '467', 'Réserve pour installations en faveur du personnel 1333 Réserves libres', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (471, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '14', '1351', 'Bénéfice reporté (ou perte reportée)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (472, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '15', '1351', 'Subsides en capital', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (473, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '150', '472', 'Montants obtenus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (474, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '151', '472', 'Montants transférés aux résultats', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (475, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '16', '1351', 'Provisions pour risques et charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (476, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '160', '475', 'Provisions pour pensions et obligations similaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (477, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '161', '475', 'Provisions pour charges fiscales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (478, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '162', '475', 'Provisions pour grosses réparations et gros entretiens', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (479, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '163', '475', 'à 169 Provisions pour autres risques et charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (480, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '164', '475', 'Provisions pour sûretés personnelles ou réelles constituées à l''appui de dettes et d''engagements de tiers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (481, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '165', '475', 'Provisions pour engagements relatifs à l''acquisition ou à la cession d''immobilisations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (482, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '166', '475', 'Provisions pour exécution de commandes passées ou reçues', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (483, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '167', '475', 'Provisions pour positions et marchés à terme en devises ou positions et marchés à terme en marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (484, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '168', '475', 'Provisions pour garanties techniques attachées aux ventes et prestations déjà effectuées par l''entreprise', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (485, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '169', '475', 'Provisions pour autres risques et charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (486, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1690', '485', 'Pour litiges en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (487, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1691', '485', 'Pour amendes, doubles droits et pénalités', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (488, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1692', '485', 'Pour propre assureur', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (489, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1693', '485', 'Pour risques inhérents aux opérations de crédits à moyen ou long terme', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (490, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1695', '485', 'Provision pour charge de liquidation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (491, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1696', '485', 'Provision pour départ de personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (492, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1699', '485', 'Pour risques divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (493, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17', '1351', 'Dettes à plus d''un an', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (494, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '170', '493', 'Emprunts subordonnés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (495, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1700', '494', 'Convertibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (496, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1701', '494', 'Non convertibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (497, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '171', '493', 'Emprunts obligataires non subordonnés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (498, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1710', '498', 'Convertibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (499, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1711', '498', 'Non convertibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (500, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '172', '493', 'Dettes de location-financement et assimilés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (501, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1720', '500', 'Dettes de location-financement de biens immobiliers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (502, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1721', '500', 'Dettes de location-financement de biens mobiliers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (503, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1722', '500', 'Dettes sur droits réels sur immeubles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (504, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '173', '493', 'Etablissements de crédit', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (505, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1730', '504', 'Dettes en compte', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (506, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17300', '505', 'Banque A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (507, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17301', '505', 'Banque B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (508, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17302', '505', 'Banque C', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (509, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17303', '505', 'Banque D', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (510, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1731', '504', 'Promesses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (511, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17310', '510', 'Banque A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (512, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17311', '510', 'Banque B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (513, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17312', '510', 'Banque C', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (514, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17313', '510', 'Banque D', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (515, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1732', '504', 'Crédits d''acceptation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (516, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17320', '515', 'Banque A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (517, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17321', '515', 'Banque B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (518, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17322', '515', 'Banque C', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (519, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17323', '515', 'Banque D', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (520, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '174', '493', 'Autres emprunts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (521, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '175', '493', 'Dettes commerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (522, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1750', '521', 'Fournisseurs : dettes en compte', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (523, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17500', '522', 'Entreprises apparentées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (524, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '175000', '523', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (525, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '175001', '523', 'Entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (526, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17501', '522', 'Fournisseurs ordinaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (527, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '175010', '526', 'Fournisseurs belges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (528, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '175011', '526', 'Fournisseurs C.E.E.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (529, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '175012', '526', 'Fournisseurs importation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (530, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1751', '521', 'Effets à payer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (531, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17510', '530', 'Entreprises apparentées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (532, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '175100', '531', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (533, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '175101', '531', 'Entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (534, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '17511', '530', 'Fournisseurs ordinaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (535, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '175110', '534', 'Fournisseurs belges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (536, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '175111', '534', 'Fournisseurs C.E.E.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (537, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '175112', '534', 'Fournisseurs importation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (538, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '176', '493', 'Acomptes reçus sur commandes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (539, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '178', '493', 'Cautionnements reçus en numéraires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (540, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '179', '493', 'Dettes diverses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (541, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1790', '540', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (542, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1791', '540', 'Autres entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (543, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1792', '540', 'Administrateurs, gérants et associés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (544, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1794', '540', 'Rentes viagères capitalisées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (545, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1798', '540', 'Dettes envers les coparticipants des associations momentanées et en participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (546, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1799', '540', 'Autres dettes diverses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (547, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '18', '1351', 'Comptes de liaison des établissements et succursales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (548, 'PCMN-BASE', 'IMMO', 'XXXXXX', '20', '1352', 'Frais d''établissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (549, 'PCMN-BASE', 'IMMO', 'XXXXXX', '200', '548', 'Frais de constitution et d''augmentation de capital', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (550, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2000', '549', 'Frais de constitution et d''augmentation de capital', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (551, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2009', '549', 'Amortissements sur frais de constitution et d''augmentation de capital', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (552, 'PCMN-BASE', 'IMMO', 'XXXXXX', '201', '548', 'Frais d''émission d''emprunts et primes de remboursement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (553, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2010', '552', 'Agios sur emprunts et frais d''émission d''emprunts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (554, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2019', '552', 'Amortissements sur agios sur emprunts et frais d''émission d''emprunts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (555, 'PCMN-BASE', 'IMMO', 'XXXXXX', '202', '548', 'Autres frais d''établissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (556, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2020', '555', 'Autres frais d''établissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (557, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2029', '555', 'Amortissements sur autres frais d''établissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (558, 'PCMN-BASE', 'IMMO', 'XXXXXX', '203', '548', 'Intérêts intercalaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (559, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2030', '558', 'Intérêts intercalaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (560, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2039', '558', 'Amortissements sur intérêts intercalaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (561, 'PCMN-BASE', 'IMMO', 'XXXXXX', '204', '548', 'Frais de restructuration', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (562, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2040', '561', 'Coût des frais de restructuration', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (563, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2049', '561', 'Amortissements sur frais de restructuration', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (564, 'PCMN-BASE', 'IMMO', 'XXXXXX', '21', '1352', 'Immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (565, 'PCMN-BASE', 'IMMO', 'XXXXXX', '210', '564', 'Frais de recherche et de développement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (566, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2100', '565', 'Frais de recherche et de mise au point', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (567, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2108', '565', 'Plus-values actées sur frais de recherche et de mise au point', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (568, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2109', '565', 'Amortissements sur frais de recherche et de mise au point', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (569, 'PCMN-BASE', 'IMMO', 'XXXXXX', '211', '564', 'Concessions, brevets, licences, savoir-faire, marque et droits similaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (570, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2110', '569', 'Concessions, brevets, licences, marques, etc', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (571, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2118', '569', 'Plus-values actées sur concessions, etc', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (572, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2119', '569', 'Amortissements sur concessions, etc', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (573, 'PCMN-BASE', 'IMMO', 'XXXXXX', '212', '564', 'Goodwill', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (574, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2120', '573', 'Coût d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (575, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2128', '573', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (576, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2129', '573', 'Amortissements sur goodwill', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (577, 'PCMN-BASE', 'IMMO', 'XXXXXX', '213', '564', 'Acomptes versés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (578, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22', '1352', 'Terrains et constructions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (579, 'PCMN-BASE', 'IMMO', 'XXXXXX', '220', '578', 'Terrains', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (580, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2200', '579', 'Terrains', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (581, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2201', '579', 'Frais d''acquisition sur terrains', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (582, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2208', '579', 'Plus-values actées sur terrains', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (583, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2209', '579', 'Amortissements et réductions de valeur', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (584, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22090', '583', 'Amortissements sur frais d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (585, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22091', '583', 'Réductions de valeur sur terrains', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (586, 'PCMN-BASE', 'IMMO', 'XXXXXX', '221', '578', 'Constructions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (587, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2210', '586', 'Bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (588, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2211', '586', 'Bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (589, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2212', '586', 'Autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (590, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2213', '586', 'Voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (591, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2215', '586', 'Constructions sur sol d''autrui', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (592, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2216', '586', 'Frais d''acquisition sur constructions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (593, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2218', '586', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (594, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22180', '593', 'Sur bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (595, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22181', '593', 'Sur bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (596, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22182', '593', 'Sur autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (597, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22184', '593', 'Sur voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (598, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2219', '586', 'Amortissements sur constructions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (599, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22190', '598', 'Sur bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (600, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22191', '598', 'Sur bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (601, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22192', '598', 'Sur autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (602, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22194', '598', 'Sur voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (603, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22195', '598', 'Sur constructions sur sol d''autrui', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (604, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22196', '598', 'Sur frais d''acquisition sur constructions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (605, 'PCMN-BASE', 'IMMO', 'XXXXXX', '222', '578', 'Terrains bâtis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (606, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2220', '605', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (607, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22200', '606', 'Bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (608, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22201', '606', 'Bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (609, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22202', '606', 'Autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (610, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22203', '606', 'Voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (611, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22204', '606', 'Frais d''acquisition des terrains à bâtir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (612, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2228', '605', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (613, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22280', '612', 'Sur bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (614, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22281', '612', 'Sur bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (615, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22282', '612', 'Sur autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (616, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22283', '612', 'Sur voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (617, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2229', '605', 'Amortissements sur terrains bâtis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (618, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22290', '617', 'Sur bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (619, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22291', '617', 'Sur bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (620, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22292', '617', 'Sur autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (621, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22293', '617', 'Sur voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (622, 'PCMN-BASE', 'IMMO', 'XXXXXX', '22294', '617', 'Sur frais d''acquisition des terrains bâtis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (623, 'PCMN-BASE', 'IMMO', 'XXXXXX', '223', '578', 'Autres droits réels sur des immeubles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (624, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2230', '623', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (625, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2238', '623', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (626, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2239', '623', 'Amortissements', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (627, 'PCMN-BASE', 'IMMO', 'XXXXXX', '23', '1352', 'Installations, machines et outillages', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (628, 'PCMN-BASE', 'IMMO', 'XXXXXX', '230', '627', 'Installations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (629, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2300', '628', 'Installations bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (630, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2301', '628', 'Installations bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (631, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2302', '628', 'Installations bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (632, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2303', '628', 'Installations voies de transport et ouvrages d''art', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (633, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2300', '628', 'Installation d''eau', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (634, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2301', '628', 'Installation d''électricité', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (635, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2302', '628', 'Installation de vapeur', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (636, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2303', '628', 'Installation de gaz', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (637, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2304', '628', 'Installation de chauffage', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (638, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2305', '628', 'Installation de conditionnement d''air', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (639, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2306', '628', 'Installation de chargement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (640, 'PCMN-BASE', 'IMMO', 'XXXXXX', '231', '627', 'Machines', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (641, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2310', '640', 'Division A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (642, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2311', '640', 'Division B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (643, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2312', '640', 'Division C', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (644, 'PCMN-BASE', 'IMMO', 'XXXXXX', '237', '627', 'Outillage', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (645, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2370', '644', 'Division A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (646, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2371', '644', 'Division B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (647, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2372', '644', 'Division C', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (648, 'PCMN-BASE', 'IMMO', 'XXXXXX', '238', '627', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (649, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2380', '648', 'Sur installations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (650, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2381', '648', 'Sur machines', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (651, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2382', '648', 'Sur outillage', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (652, 'PCMN-BASE', 'IMMO', 'XXXXXX', '239', '627', 'Amortissements', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (653, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2390', '652', 'Sur installations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (654, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2391', '652', 'Sur machines', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (655, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2392', '652', 'Sur outillage', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (656, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24', '1352', 'Mobilier et matériel roulant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (657, 'PCMN-BASE', 'IMMO', 'XXXXXX', '240', '656', 'Mobilier', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (658, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2400', '656', 'Mobilier', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (659, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24000', '658', 'Mobilier des bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (660, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24001', '658', 'Mobilier des bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (661, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24002', '658', 'Mobilier des autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (662, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24003', '658', 'Mobilier oeuvres sociales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (663, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2401', '657', 'Matériel de bureau et de service social', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (664, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24010', '663', 'Des bâtiments industriels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (665, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24011', '663', 'Des bâtiments administratifs et commerciaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (666, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24012', '663', 'Des autres bâtiments d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (667, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24013', '663', 'Des oeuvres sociales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (668, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2408', '657', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (669, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24080', '668', 'Plus-values actées sur mobilier', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (670, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24081', '668', 'Plus-values actées sur matériel de bureau et service social', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (671, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2409', '657', 'Amortissements', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (672, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24090', '671', 'Amortissements sur mobilier', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (673, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24091', '671', 'Amortissements sur matériel de bureau et service social', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (674, 'PCMN-BASE', 'IMMO', 'XXXXXX', '241', '656', 'Matériel roulant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (675, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2410', '674', 'Matériel automobile', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (676, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24100', '675', 'Voitures', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (677, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24105', '675', 'Camions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (678, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2411', '674', 'Matériel ferroviaire', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (679, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2412', '674', 'Matériel fluvial', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (680, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2413', '674', 'Matériel naval', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (681, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2414', '674', 'Matériel aérien', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (682, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2418', '674', 'Plus-values sur matériel roulant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (683, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24180', '682', 'Plus-values sur matériel automobile', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (684, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24181', '682', 'Idem sur matériel ferroviaire', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (685, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24182', '682', 'Idem sur matériel fluvial', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (686, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24183', '682', 'Idem sur matériel naval', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (687, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24184', '682', 'Idem sur matériel aérien', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (688, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2419', '674', 'Amortissements sur matériel roulant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (689, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24190', '688', 'Amortissements sur matériel automobile', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (690, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24191', '688', 'Idem sur matériel ferroviaire', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (691, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24192', '688', 'Idem sur matériel fluvial', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (692, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24193', '688', 'Idem sur matériel naval', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (693, 'PCMN-BASE', 'IMMO', 'XXXXXX', '24194', '688', 'Idem sur matériel aérien', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (694, 'PCMN-BASE', 'IMMO', 'XXXXXX', '25', '1352', 'Immobilisation détenues en location-financement et droits similaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (695, 'PCMN-BASE', 'IMMO', 'XXXXXX', '250', '694', 'Terrains et constructions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (696, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2500', '695', 'Terrains', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (697, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2501', '695', 'Constructions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (698, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2508', '695', 'Plus-values sur emphytéose,  leasing et droits similaires : terrains et constructions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (699, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2509', '695', 'Amortissements et réductions de valeur sur terrains et constructions en leasing', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (700, 'PCMN-BASE', 'IMMO', 'XXXXXX', '251', '694', 'Installations,  machines et outillage', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (701, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2510', '700', 'Installations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (702, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2511', '700', 'Machines', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (703, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2512', '700', 'Outillage', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (704, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2518', '700', 'Plus-values actées sur installations machines et outillage pris en leasing', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (705, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2519', '700', 'Amortissements sur installations machines et outillage pris en leasing', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (706, 'PCMN-BASE', 'IMMO', 'XXXXXX', '252', '694', 'Mobilier et matériel roulant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (707, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2520', '706', 'Mobilier', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (708, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2521', '706', 'Matériel roulant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (709, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2528', '706', 'Plus-values actées sur mobilier et matériel roulant en leasing', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (710, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2529', '706', 'Amortissements sur mobilier et matériel roulant en leasing', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (711, 'PCMN-BASE', 'IMMO', 'XXXXXX', '26', '1352', 'Autres immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (712, 'PCMN-BASE', 'IMMO', 'XXXXXX', '260', '711', 'Frais d''aménagements de locaux pris en location', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (713, 'PCMN-BASE', 'IMMO', 'XXXXXX', '261', '711', 'Maison d''habitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (714, 'PCMN-BASE', 'IMMO', 'XXXXXX', '262', '711', 'Réserve immobilière', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (715, 'PCMN-BASE', 'IMMO', 'XXXXXX', '263', '711', 'Matériel d''emballage', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (716, 'PCMN-BASE', 'IMMO', 'XXXXXX', '264', '711', 'Emballages récupérables', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (717, 'PCMN-BASE', 'IMMO', 'XXXXXX', '268', '711', 'Plus-values actées sur autres immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (718, 'PCMN-BASE', 'IMMO', 'XXXXXX', '269', '711', 'Amortissements sur autres immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (719, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2690', '718', 'Amortissements sur frais d''aménagement des locaux pris en location', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (720, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2691', '718', 'Amortissements sur maison d''habitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (721, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2692', '718', 'Amortissements sur réserve immobilière', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (722, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2693', '718', 'Amortissements sur matériel d''emballage', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (723, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2694', '718', 'Amortissements sur emballages récupérables', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (724, 'PCMN-BASE', 'IMMO', 'XXXXXX', '27', '1352', 'Immobilisations corporelles en cours et acomptes versés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (725, 'PCMN-BASE', 'IMMO', 'XXXXXX', '270', '724', 'Immobilisations en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (726, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2700', '725', 'Constructions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (727, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2701', '725', 'Installations machines et outillage', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (728, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2702', '725', 'Mobilier et matériel roulant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (729, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2703', '725', 'Autres immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (730, 'PCMN-BASE', 'IMMO', 'XXXXXX', '271', '724', 'Avances et acomptes versés sur immobilisations en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (731, 'PCMN-BASE', 'IMMO', 'XXXXXX', '28', '1352', 'Immobilisations financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (732, 'PCMN-BASE', 'IMMO', 'XXXXXX', '280', '731', 'Participations dans des entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (733, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2800', '732', 'Valeur d''acquisition (peut être subdivisé par participation)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (734, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2801', '732', 'Montants non appelés (idem)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (735, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2808', '732', 'Plus-values actées (idem)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (736, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2809', '732', 'Réductions de valeurs actées (idem)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (737, 'PCMN-BASE', 'IMMO', 'XXXXXX', '281', '731', 'Créances sur des entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (738, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2810', '737', 'Créances en compte', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (739, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2811', '737', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (740, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2812', '737', 'Titres à revenu fixes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (741, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2817', '737', 'Créances douteuses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (742, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2819', '737', 'Réductions de valeurs actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (743, 'PCMN-BASE', 'IMMO', 'XXXXXX', '282', '731', 'Participations dans des entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (744, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2820', '743', 'Valeur d''acquisition (peut être subdivisé par participation)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (745, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2821', '743', 'Montants non appelés (idem)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (746, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2828', '743', 'Plus-values actées (idem)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (747, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2829', '743', 'Réductions de valeurs actées (idem)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (748, 'PCMN-BASE', 'IMMO', 'XXXXXX', '283', '731', 'Créances sur des entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (749, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2830', '748', 'Créances en compte', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (750, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2831', '748', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (751, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2832', '748', 'Titres à revenu fixe', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (752, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2837', '748', 'Créances douteuses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (753, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2839', '748', 'Réductions de valeurs actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (754, 'PCMN-BASE', 'IMMO', 'XXXXXX', '284', '731', 'Autres actions et parts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (755, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2840', '754', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (756, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2841', '754', 'Montants non appelés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (757, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2848', '754', 'Plus-values actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (758, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2849', '754', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (759, 'PCMN-BASE', 'IMMO', 'XXXXXX', '285', '731', 'Autres créances', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (760, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2850', '759', 'Créances en compte', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (761, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2851', '759', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (762, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2852', '759', 'Titres à revenu fixe', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (763, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2857', '759', 'Créances douteuses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (764, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2859', '759', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (765, 'PCMN-BASE', 'IMMO', 'XXXXXX', '288', '731', 'Cautionnements versés en numéraires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (766, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2880', '765', 'Téléphone, téléfax, télex', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (767, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2881', '765', 'Gaz', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (768, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2882', '765', 'Eau', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (769, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2883', '765', 'Electricité', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (770, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2887', '765', 'Autres cautionnements versés en numéraires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (771, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29', '1352', 'Créances à plus d''un an', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (772, 'PCMN-BASE', 'IMMO', 'XXXXXX', '290', '771', 'Créances commerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (773, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2900', '772', 'Clients', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (774, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29000', '773', 'Créances en compte sur entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (775, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29001', '773', 'Sur entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (776, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29002', '773', 'Sur clients Belgique', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (777, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29003', '773', 'Sur clients C.E.E.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (778, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29004', '773', 'Sur clients exportation hors C.E.E.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (779, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29005', '773', 'Créances sur les coparticipants (associations momentanées)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (780, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2901', '772', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (781, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29010', '780', 'Sur entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (782, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29011', '780', 'Sur entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (783, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29012', '780', 'Sur clients Belgique', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (784, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29013', '780', 'Sur clients C.E.E.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (785, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29014', '780', 'Sur clients exportation hors C.E.E.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (786, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2905', '772', 'Retenues sur garanties', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (787, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2906', '772', 'Acomptes versés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (788, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2907', '772', 'Créances douteuses (à ventiler comme clients 2900)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (789, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2909', '772', 'Réductions de valeur actées (à ventiler comme clients 2900)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (790, 'PCMN-BASE', 'IMMO', 'XXXXXX', '291', '771', 'Autres créances', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (791, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2910', '790', 'Créances en compte', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (792, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29100', '791', 'Sur entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (793, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29101', '791', 'Sur entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (794, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29102', '791', 'Sur autres débiteurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (795, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2911', '790', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (796, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29110', '795', 'Sur entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (797, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29111', '795', 'Sur entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (798, 'PCMN-BASE', 'IMMO', 'XXXXXX', '29112', '795', 'Sur autres débiteurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (799, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2912', '790', 'Créances résultant de la cession d''immobilisations données en leasing', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (800, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2917', '790', 'Créances douteuses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (801, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2919', '790', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (802, 'PCMN-BASE', 'STOCK', 'XXXXXX', '30', '1353', 'Approvisionnements - matières premières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (803, 'PCMN-BASE', 'STOCK', 'XXXXXX', '300', '802', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (804, 'PCMN-BASE', 'STOCK', 'XXXXXX', '309', '802', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (805, 'PCMN-BASE', 'STOCK', 'XXXXXX', '31', '1353', 'Approvsionnements et fournitures', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (806, 'PCMN-BASE', 'STOCK', 'XXXXXX', '310', '805', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (807, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3100', '806', 'Matières d''approvisionnement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (808, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3101', '806', 'Energie, charbon, coke, mazout, essence, propane', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (809, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3102', '806', 'Produits d''entretien', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (810, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3103', '806', 'Fournitures diverses et petit outillage', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (811, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3104', '806', 'Imprimés et fournitures de bureau', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (812, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3105', '806', 'Fournitures de services sociaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (813, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3106', '806', 'Emballages commerciaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (814, 'PCMN-BASE', 'STOCK', 'XXXXXX', '31060', '813', 'Emballages perdus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (815, 'PCMN-BASE', 'STOCK', 'XXXXXX', '31061', '813', 'Emballages récupérables', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (816, 'PCMN-BASE', 'STOCK', 'XXXXXX', '319', '805', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (817, 'PCMN-BASE', 'STOCK', 'XXXXXX', '32', '1353', 'En cours de fabrication', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (818, 'PCMN-BASE', 'STOCK', 'XXXXXX', '320', '817', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (819, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3200', '818', 'Produits semi-ouvrés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (820, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3201', '818', 'Produits en cours de fabrication', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (821, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3202', '818', 'Travaux en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (822, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3205', '818', 'Déchets', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (823, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3206', '818', 'Rebuts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (824, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3209', '818', 'Travaux en association momentanée', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (825, 'PCMN-BASE', 'STOCK', 'XXXXXX', '329', '817', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (826, 'PCMN-BASE', 'STOCK', 'XXXXXX', '33', '1353', 'Produits finis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (827, 'PCMN-BASE', 'STOCK', 'XXXXXX', '330', '826', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (828, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3300', '827', 'Produits finis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (829, 'PCMN-BASE', 'STOCK', 'XXXXXX', '339', '826', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (830, 'PCMN-BASE', 'STOCK', 'XXXXXX', '34', '1353', 'Marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (831, 'PCMN-BASE', 'STOCK', 'XXXXXX', '340', '830', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (832, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3400', '831', 'Groupe A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (833, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3401', '831', 'Groupe B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (834, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3402', '831', 'Groupe C', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (835, 'PCMN-BASE', 'STOCK', 'XXXXXX', '349', '830', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (836, 'PCMN-BASE', 'STOCK', 'XXXXXX', '35', '1353', 'Immeubles destinés à la vente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (837, 'PCMN-BASE', 'STOCK', 'XXXXXX', '350', '836', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (838, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3500', '837', 'Immeuble A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (839, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3501', '837', 'Immeuble B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (840, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3502', '837', 'Immeuble C', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (841, 'PCMN-BASE', 'STOCK', 'XXXXXX', '351', '836', 'Immeubles construits en vue de leur revente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (842, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3510', '841', 'Immeuble A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (843, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3511', '841', 'Immeuble B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (844, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3512', '841', 'Immeuble C', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (845, 'PCMN-BASE', 'STOCK', 'XXXXXX', '359', '836', 'Réductions de valeurs actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (846, 'PCMN-BASE', 'STOCK', 'XXXXXX', '36', '1353', 'Acomptes versés sur achats pour stocks', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (847, 'PCMN-BASE', 'STOCK', 'XXXXXX', '360', '846', 'Acomptes versés (à ventiler éventuellement par catégorie)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (848, 'PCMN-BASE', 'STOCK', 'XXXXXX', '369', '846', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (849, 'PCMN-BASE', 'STOCK', 'XXXXXX', '37', '1353', 'Commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (850, 'PCMN-BASE', 'STOCK', 'XXXXXX', '370', '849', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (851, 'PCMN-BASE', 'STOCK', 'XXXXXX', '371', '849', 'Bénéfice pris en compte', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (852, 'PCMN-BASE', 'STOCK', 'XXXXXX', '379', '849', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (853, 'PCMN-BASE', 'TIERS', 'XXXXXX', '40', '1354', 'Créances commerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (854, 'PCMN-BASE', 'TIERS', 'XXXXXX', '400', '853', 'Clients', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (855, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4007', '854', 'Rabais, remises et  ristournes à accorder et autres notes de crédit à établir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (856, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4008', '854', 'Créances résultant de livraisons de biens (associations momentanées)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (857, 'PCMN-BASE', 'TIERS', 'XXXXXX', '401', '853', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (858, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4010', '857', 'Effets à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (859, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4013', '857', 'Effets à l''encaissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (860, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4015', '857', 'Effets à l''escompte', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (861, 'PCMN-BASE', 'TIERS', 'XXXXXX', '402', '853', 'Clients, créances courantes, entreprises apparentées, administrateurs et gérants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (862, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4020', '861', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (863, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4021', '861', 'Autres entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (864, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4022', '861', 'Administrateurs et gérants d''entreprise', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (865, 'PCMN-BASE', 'TIERS', 'XXXXXX', '403', '853', 'Effets à recevoir sur entreprises apparentées et administrateurs et gérants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (866, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4030', '865', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (867, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4031', '865', 'Autres entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (868, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4032', '865', 'Administrateurs et gérants de l''entreprise', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (869, 'PCMN-BASE', 'TIERS', 'XXXXXX', '404', '853', 'Produits à recevoir (factures à établir)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (870, 'PCMN-BASE', 'TIERS', 'XXXXXX', '405', '853', 'Clients : retenues sur garanties', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (871, 'PCMN-BASE', 'TIERS', 'XXXXXX', '406', '853', 'Acomptes versés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (872, 'PCMN-BASE', 'TIERS', 'XXXXXX', '407', '853', 'Créances douteuses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (873, 'PCMN-BASE', 'TIERS', 'XXXXXX', '408', '853', 'Compensation clients', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (874, 'PCMN-BASE', 'TIERS', 'XXXXXX', '409', '853', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (875, 'PCMN-BASE', 'TIERS', 'XXXXXX', '41', '1354', 'Autres créances', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (876, 'PCMN-BASE', 'TIERS', 'XXXXXX', '410', '875', 'Capital appelé, non versé', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (877, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4100', '876', 'Appels de fonds', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (878, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4101', '876', 'Actionnaires défaillants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (879, 'PCMN-BASE', 'TIERS', 'XXXXXX', '411', '875', 'T.V.A. à récupérer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (880, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4110', '879', 'T.V.A. due', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (881, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4111', '879', 'T.V.A. déductible', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (882, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4112', '879', 'Compte courant administration T.V.A.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (883, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4118', '879', 'Taxe d''égalisation due', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (884, 'PCMN-BASE', 'TIERS', 'XXXXXX', '412', '875', 'Impôts et versements fiscaux à récupérer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (885, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4120', '884', 'Impôts belges sur le résultat', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (886, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4125', '884', 'Autres impôts belges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (887, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4128', '884', 'Impôts étrangers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (888, 'PCMN-BASE', 'TIERS', 'XXXXXX', '414', '875', 'Produits à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (889, 'PCMN-BASE', 'TIERS', 'XXXXXX', '416', '875', 'Créances diverses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (890, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4160', '889', 'Associés (compte d''apport en société)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (891, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4161', '889', 'Avances et prêts au personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (892, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4162', '889', 'Compte courant des associés en S.P.R.L.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (893, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4163', '889', 'Compte courant des administrateurs et gérants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (894, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4164', '889', 'Créances sur sociétés apparentées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (895, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4166', '889', 'Emballages et matériel à rendre', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (896, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4167', '889', 'Etat et établissements publics', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (897, 'PCMN-BASE', 'TIERS', 'XXXXXX', '41670', '896', 'Subsides à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (898, 'PCMN-BASE', 'TIERS', 'XXXXXX', '41671', '896', 'Autres créances', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (899, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4168', '889', 'Rabais, ristournes et remises à obtenir et autres avoirs non encore reçus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (900, 'PCMN-BASE', 'TIERS', 'XXXXXX', '417', '875', 'Créances douteuses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (901, 'PCMN-BASE', 'TIERS', 'XXXXXX', '418', '875', 'Cautionnements versés en numéraires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (902, 'PCMN-BASE', 'TIERS', 'XXXXXX', '419', '875', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (903, 'PCMN-BASE', 'TIERS', 'XXXXXX', '42', '1354', 'Dettes à plus d''un an échéant dans l''année', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (904, 'PCMN-BASE', 'TIERS', 'XXXXXX', '420', '903', 'Emprunts subordonnés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (905, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4200', '904', 'Convertibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (906, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4201', '904', 'Non convertibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (907, 'PCMN-BASE', 'TIERS', 'XXXXXX', '421', '903', 'Emprunts obligataires non subordonnés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (908, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4210', '907', 'Convertibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (909, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4211', '907', 'Non convertibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (910, 'PCMN-BASE', 'TIERS', 'XXXXXX', '422', '903', 'Dettes de location-financement et assimilées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (911, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4220', '910', 'Financement de biens immobiliers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (912, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4221', '910', 'Financement de biens mobiliers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (913, 'PCMN-BASE', 'TIERS', 'XXXXXX', '423', '903', 'Etablissements de crédit', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (914, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4230', '913', 'Dettes en compte', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (915, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4231', '913', 'Promesses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (916, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4232', '913', 'Crédits d''acceptation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (917, 'PCMN-BASE', 'TIERS', 'XXXXXX', '424', '903', 'Autres emprunts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (918, 'PCMN-BASE', 'TIERS', 'XXXXXX', '425', '903', 'Dettes commerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (919, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4250', '918', 'Fournisseurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (920, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4251', '918', 'Effets à payer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (921, 'PCMN-BASE', 'TIERS', 'XXXXXX', '426', '903', 'Cautionnements reçus en numéraires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (922, 'PCMN-BASE', 'TIERS', 'XXXXXX', '429', '903', 'Dettes diverses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (923, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4290', '922', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (924, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4291', '922', 'Entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (925, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4292', '922', 'Administrateurs, gérants, associés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (926, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4299', '922', 'Autres dettes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (927, 'PCMN-BASE', 'TIERS', 'XXXXXX', '43', '1354', 'Dettes financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (928, 'PCMN-BASE', 'TIERS', 'XXXXXX', '430', '927', 'Etablissements de crédit. Emprunts en compte à terme fixe', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (929, 'PCMN-BASE', 'TIERS', 'XXXXXX', '431', '927', 'Etablissements de crédit. Promesses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (930, 'PCMN-BASE', 'TIERS', 'XXXXXX', '432', '927', 'Etablissements de crédit. Crédits d''acceptation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (931, 'PCMN-BASE', 'TIERS', 'XXXXXX', '433', '927', 'Etablissements de crédit. Dettes en compte courant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (932, 'PCMN-BASE', 'TIERS', 'XXXXXX', '439', '927', 'Autres emprunts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (933, 'PCMN-BASE', 'TIERS', 'XXXXXX', '44', '1354', 'Dettes commerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (934, 'PCMN-BASE', 'TIERS', 'XXXXXX', '440', '933', 'Fournisseurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (935, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4400', '934', 'Entreprises apparentées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (936, 'PCMN-BASE', 'TIERS', 'XXXXXX', '44000', '935', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (937, 'PCMN-BASE', 'TIERS', 'XXXXXX', '44001', '935', 'Entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (938, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4401', '934', 'Fournisseurs ordinaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (939, 'PCMN-BASE', 'TIERS', 'XXXXXX', '44010', '938', 'Fournisseurs belges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (940, 'PCMN-BASE', 'TIERS', 'XXXXXX', '44011', '938', 'Fournisseurs CEE', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (941, 'PCMN-BASE', 'TIERS', 'XXXXXX', '44012', '938', 'Fournisseurs importation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (942, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4402', '934', 'Dettes envers les coparticipants (associations momentanées)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (943, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4403', '934', 'Fournisseurs - retenues de garanties', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (944, 'PCMN-BASE', 'TIERS', 'XXXXXX', '441', '933', 'Effets à payer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (945, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4410', '944', 'Entreprises apparentées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (946, 'PCMN-BASE', 'TIERS', 'XXXXXX', '44100', '945', 'Entreprises liées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (947, 'PCMN-BASE', 'TIERS', 'XXXXXX', '44101', '945', 'Entreprises avec lesquelles il existe un lien de participation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (948, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4411', '944', 'Fournisseurs ordinaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (949, 'PCMN-BASE', 'TIERS', 'XXXXXX', '44110', '948', 'Fournisseurs belges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (950, 'PCMN-BASE', 'TIERS', 'XXXXXX', '44111', '948', 'Fournisseurs CEE', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (951, 'PCMN-BASE', 'TIERS', 'XXXXXX', '44112', '948', 'Fournisseurs importation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (952, 'PCMN-BASE', 'TIERS', 'XXXXXX', '444', '933', 'Factures à recevoir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (953, 'PCMN-BASE', 'TIERS', 'XXXXXX', '446', '933', 'Acomptes reçus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (954, 'PCMN-BASE', 'TIERS', 'XXXXXX', '448', '933', 'Compensations fournisseurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (955, 'PCMN-BASE', 'TIERS', 'XXXXXX', '45', '1354', 'Dettes fiscales, salariales et sociales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (956, 'PCMN-BASE', 'TIERS', 'XXXXXX', '450', '955', 'Dettes fiscales estimées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (957, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4501', '956', 'Impôts sur le résultat', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (958, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4505', '956', 'Autres impôts en Belgique', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (959, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4508', '956', 'Impôts à l''étranger', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (960, 'PCMN-BASE', 'TIERS', 'XXXXXX', '451', '955', 'T.V.A. à payer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (961, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4510', '960', 'T.V.A. due', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (962, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4511', '960', 'T.V.A. déductible', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (963, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4512', '960', 'Compte courant administration T.V.A.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (964, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4518', '960', 'Taxe d''égalisation due', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (965, 'PCMN-BASE', 'TIERS', 'XXXXXX', '452', '955', 'Impôts et taxes à payer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (966, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4520', '965', 'Autres impôts sur le résultat', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (967, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4525', '965', 'Autres impôts et taxes en Belgique', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (968, 'PCMN-BASE', 'TIERS', 'XXXXXX', '45250', '967', 'Précompte immobilier', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (969, 'PCMN-BASE', 'TIERS', 'XXXXXX', '45251', '967', 'Impôts communaux à payer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (970, 'PCMN-BASE', 'TIERS', 'XXXXXX', '45252', '967', 'Impôts provinciaux à payer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (971, 'PCMN-BASE', 'TIERS', 'XXXXXX', '45253', '967', 'Autres impôts et taxes à payer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (972, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4528', '965', 'Impôts et taxes à l''étranger', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (973, 'PCMN-BASE', 'TIERS', 'XXXXXX', '453', '955', 'Précomptes retenus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (974, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4530', '973', 'Précompte professionnel retenu sur rémunérations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (975, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4531', '973', 'Précompte professionnel retenu sur tantièmes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (976, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4532', '973', 'Précompte mobilier retenu sur dividendes attribués', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (977, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4533', '973', 'Précompte mobilier retenu sur intérêts payés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (978, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4538', '973', 'Autres précomptes retenus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (979, 'PCMN-BASE', 'TIERS', 'XXXXXX', '454', '955', 'Office National de la Sécurité Sociale', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (980, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4540', '979', 'Arriérés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (981, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4541', '979', '1er trimestre', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (982, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4542', '979', '2ème trimestre', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (983, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4543', '979', '3ème trimestre', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (984, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4544', '979', '4ème trimestre', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (985, 'PCMN-BASE', 'TIERS', 'XXXXXX', '455', '955', 'Rémunérations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (986, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4550', '985', 'Administrateurs,  gérants et commissaires (non réviseurs)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (987, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4551', '985', 'Direction', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (988, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4552', '985', 'Employés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (989, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4553', '985', 'Ouvriers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (990, 'PCMN-BASE', 'TIERS', 'XXXXXX', '456', '955', 'Pécules de vacances', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (991, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4560', '990', 'Direction', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (992, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4561', '990', 'Employés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (993, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4562', '990', 'Ouvriers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (994, 'PCMN-BASE', 'TIERS', 'XXXXXX', '459', '955', 'Autres dettes sociales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (995, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4590', '994', 'Provision pour gratifications de fin d''année', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (996, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4591', '994', 'Départs de personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (997, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4592', '994', 'Oppositions sur rémunérations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (998, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4593', '994', 'Assurances relatives au personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (999, 'PCMN-BASE', 'TIERS', 'XXXXXX', '45930', '998', 'Assurance loi', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1000, 'PCMN-BASE', 'TIERS', 'XXXXXX', '45931', '998', 'Assurance salaire garanti', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1001, 'PCMN-BASE', 'TIERS', 'XXXXXX', '45932', '998', 'Assurance groupe', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1002, 'PCMN-BASE', 'TIERS', 'XXXXXX', '45933', '998', 'Assurances individuelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1003, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4594', '994', 'Caisse d''assurances sociales pour travailleurs indépendants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1004, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4597', '994', 'Dettes et provisions sociales diverses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1005, 'PCMN-BASE', 'TIERS', 'XXXXXX', '46', '1354', 'Acomptes reçus sur commande', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1006, 'PCMN-BASE', 'TIERS', 'XXXXXX', '47', '1354', 'Dettes découlant de l''affectation des résultats', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1007, 'PCMN-BASE', 'TIERS', 'XXXXXX', '470', '1006', 'Dividendes et tantièmes d''exercices antérieurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1008, 'PCMN-BASE', 'TIERS', 'XXXXXX', '471', '1006', 'Dividendes de l''exercice', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1009, 'PCMN-BASE', 'TIERS', 'XXXXXX', '472', '1006', 'Tantièmes de l''exercice', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1010, 'PCMN-BASE', 'TIERS', 'XXXXXX', '473', '1006', 'Autres allocataires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1011, 'PCMN-BASE', 'TIERS', 'XXXXXX', '48', '4', 'Dettes diverses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1012, 'PCMN-BASE', 'TIERS', 'XXXXXX', '480', '1011', 'Obligations et coupons échus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1013, 'PCMN-BASE', 'TIERS', 'XXXXXX', '481', '1011', 'Actionnaires - capital à rembourser', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1014, 'PCMN-BASE', 'TIERS', 'XXXXXX', '482', '1011', 'Participation du personnel à payer', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1015, 'PCMN-BASE', 'TIERS', 'XXXXXX', '483', '1011', 'Acomptes reçus d''autres tiers à moins d''un an', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1016, 'PCMN-BASE', 'TIERS', 'XXXXXX', '486', '1011', 'Emballages et matériel consignés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1017, 'PCMN-BASE', 'TIERS', 'XXXXXX', '488', '1011', 'Cautionnements reçus en numéraires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1018, 'PCMN-BASE', 'TIERS', 'XXXXXX', '489', '1011', 'Autres dettes diverses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1019, 'PCMN-BASE', 'TIERS', 'XXXXXX', '49', '1354', 'Comptes de régularisation et compte d''attente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1020, 'PCMN-BASE', 'TIERS', 'XXXXXX', '490', '1019', 'Charges à reporter (à subdiviser par catégorie de charges)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1021, 'PCMN-BASE', 'TIERS', 'XXXXXX', '491', '1019', 'Produits acquis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1022, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4910', '1021', 'Produits d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1023, 'PCMN-BASE', 'TIERS', 'XXXXXX', '49100', '1022', 'Ristournes et rabais à obtenir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1024, 'PCMN-BASE', 'TIERS', 'XXXXXX', '49101', '1022', 'Commissions à obtenir', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1025, 'PCMN-BASE', 'TIERS', 'XXXXXX', '49102', '1022', 'Autres produits d''exploitation (redevances par exemple)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1026, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4911', '1021', 'Produits financiers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1027, 'PCMN-BASE', 'TIERS', 'XXXXXX', '49110', '1026', 'Intérêts courus et non échus sur prêts et débits', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1028, 'PCMN-BASE', 'TIERS', 'XXXXXX', '49111', '1026', 'Autres produits financiers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1029, 'PCMN-BASE', 'TIERS', 'XXXXXX', '492', '1019', 'Charges à imputer (à subdiviser par catégorie de charges)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1030, 'PCMN-BASE', 'TIERS', 'XXXXXX', '493', '1019', 'Produits à reporter', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1031, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4930', '1030', 'Produits d''exploitation à reporter', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1032, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4931', '1030', 'Produits financiers à reporter', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1033, 'PCMN-BASE', 'TIERS', 'XXXXXX', '499', '1019', 'Comptes d''attente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1034, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4990', '1033', 'Compte d''attente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1035, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4991', '1033', 'Compte de répartition périodique des charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1036, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4999', '1033', 'Transferts d''exercice', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1037, 'PCMN-BASE', 'FINAN', 'XXXXXX', '50', '1355', 'Actions propres', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1038, 'PCMN-BASE', 'FINAN', 'XXXXXX', '51', '1355', 'Actions et parts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1039, 'PCMN-BASE', 'FINAN', 'XXXXXX', '510', '1038', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1040, 'PCMN-BASE', 'FINAN', 'XXXXXX', '511', '1038', 'Montants non appelés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1041, 'PCMN-BASE', 'FINAN', 'XXXXXX', '519', '1038', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1042, 'PCMN-BASE', 'FINAN', 'XXXXXX', '52', '1355', 'Titres à revenus fixes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1043, 'PCMN-BASE', 'FINAN', 'XXXXXX', '520', '1042', 'Valeur d''acquisition', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1044, 'PCMN-BASE', 'FINAN', 'XXXXXX', '529', '1042', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1045, 'PCMN-BASE', 'FINAN', 'XXXXXX', '53', '1355', 'Dépots à terme', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1046, 'PCMN-BASE', 'FINAN', 'XXXXXX', '530', '1045', 'De plus d''un an', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1047, 'PCMN-BASE', 'FINAN', 'XXXXXX', '531', '1045', 'De plus d''un mois et à un an au plus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1048, 'PCMN-BASE', 'FINAN', 'XXXXXX', '532', '1045', 'd''un mois au plus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1049, 'PCMN-BASE', 'FINAN', 'XXXXXX', '539', '1045', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1050, 'PCMN-BASE', 'FINAN', 'XXXXXX', '54', '1355', 'Valeurs échues à l''encaissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1051, 'PCMN-BASE', 'FINAN', 'XXXXXX', '540', '1050', 'Chèques à encaisser', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1052, 'PCMN-BASE', 'FINAN', 'XXXXXX', '541', '1050', 'Coupons à encaisser', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1053, 'PCMN-BASE', 'FINAN', 'XXXXXX', '55', '1355', 'Etablissements de crédit - Comptes ouverts auprès des divers établissements.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1054, 'PCMN-BASE', 'FINAN', 'XXXXXX', '550', '1053', 'Comptes courants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1055, 'PCMN-BASE', 'FINAN', 'XXXXXX', '551', '1053', 'Chèques émis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1056, 'PCMN-BASE', 'FINAN', 'XXXXXX', '559', '1053', 'Réductions de valeur actées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1057, 'PCMN-BASE', 'FINAN', 'XXXXXX', '56', '1355', 'Office des chèques postaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1058, 'PCMN-BASE', 'FINAN', 'XXXXXX', '560', '1057', 'Compte courant', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1059, 'PCMN-BASE', 'FINAN', 'XXXXXX', '561', '1057', 'Chèques émis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1060, 'PCMN-BASE', 'FINAN', 'XXXXXX', '57', '1355', 'Caisses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1061, 'PCMN-BASE', 'FINAN', 'XXXXXX', '570', '1060', 'à 577 Caisses - espèces ( 0 - centrale ; 7 - succursales et agences)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1062, 'PCMN-BASE', 'FINAN', 'XXXXXX', '578', '1060', 'Caisses - timbres ( 0 - fiscaux ; 1 - postaux)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1063, 'PCMN-BASE', 'FINAN', 'XXXXXX', '58', '1355', 'Virements internes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1064, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '60', '1356', 'Approvisionnements et marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1065, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '600', '1064', 'Achats de matières premières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1066, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '601', '1064', 'Achats de fournitures', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1067, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '602', '1064', 'Achats de services, travaux et études', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1068, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '603', '1064', 'Sous-traitances générales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1069, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '604', '1064', 'Achats de marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1070, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '605', '1064', 'Achats d''immeubles destinés à la revente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1071, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '608', '1064', 'Remises , ristournes et rabais obtenus sur achats', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1072, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '609', '1064', 'Variations de stocks', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1073, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6090', '1072', 'De matières premières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1074, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6091', '1072', 'De fournitures', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1075, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6094', '1072', 'De marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1076, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6095', '1072', 'd''immeubles destinés à la vente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1077, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61', '1356', 'Services et biens divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1078, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '610', '1077', 'Loyers et charges locatives', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1079, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6100', '1078', 'Loyers divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1080, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6101', '1078', 'Charges locatives (assurances, frais de confort,etc)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1081, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '611', '1077', 'Entretien et réparation (fournitures et prestations)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1082, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '612', '1077', 'Fournitures faites à l''entreprise', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1083, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6120', '1082', 'Eau, gaz, électricité, vapeur', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1084, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61200', '1083', 'Eau', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1085, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61201', '1083', 'Gaz', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1086, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61202', '1083', 'Electricité', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1087, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61203', '1083', 'Vapeur', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1088, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6121', '1082', 'Téléphone, télégrammes, télex, téléfax, frais postaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1089, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61210', '1088', 'Téléphone', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1090, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61211', '1088', 'Télégrammes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1091, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61212', '1088', 'Télex et téléfax', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1092, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61213', '1088', 'Frais postaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1093, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6122', '1082', 'Livres, bibliothèque', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1094, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6123', '1082', 'Imprimés et fournitures de bureau (si non comptabilisé au 601)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1095, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '613', '1077', 'Rétributions de tiers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1096, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6130', '1095', 'Redevances et royalties', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1097, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61300', '1096', 'Redevances pour brevets, licences, marques et accessoires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1098, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61301', '1096', 'Autres redevances (procédés de fabrication)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1099, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6131', '1095', 'Assurances non relatives au personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1100, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61310', '1099', 'Assurance incendie', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1101, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61311', '1099', 'Assurance vol', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1102, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61312', '1099', 'Assurance autos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1103, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61313', '1099', 'Assurance crédit', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1104, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61314', '1099', 'Assurances frais généraux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1105, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6132', '1095', 'Divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1106, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61320', '1105', 'Commissions aux tiers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1107, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61321', '1105', 'Honoraires d''avocats, d''experts, etc', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1108, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61322', '1105', 'Cotisations aux groupements professionnels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1109, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61323', '1105', 'Dons, libéralités, etc', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1110, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61324', '1105', 'Frais de contentieux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1111, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61325', '1105', 'Publications légales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1112, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6133', '1095', 'Transports et déplacements', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1113, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61330', '1112', 'Transports de personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1114, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '61331', '1112', 'Voyages, déplacements et représentations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1115, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6134', '1095', 'Personnel intérimaire', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1116, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '614', '1077', 'Annonces, publicité, propagande et documentation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1117, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6140', '1116', 'Annonces et insertions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1118, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6141', '1116', 'Catalogues et imprimés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1119, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6142', '1116', 'Echantillons', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1120, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6143', '1116', 'Foires et expositions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1121, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6144', '1116', 'Primes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1122, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6145', '1116', 'Cadeaux à la clientèle', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1123, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6146', '1116', 'Missions et réceptions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1124, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6147', '1116', 'Documentation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1125, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '615', '1077', 'Sous-traitants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1126, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6150', '1125', 'Sous-traitants pour activités propres', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1127, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6151', '1125', 'Sous-traitants d''associations momentanées (coparticipants)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1128, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6152', '1125', 'Quote-part bénéficiaire des coparticipants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1129, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '617', '1077', 'Personnel intérimaire et personnes mises à la disposition de l''entreprise', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1130, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '618', '1077', 'Rémunérations, primes pour assurances extralégales, pensions de retraite et de survie des administrateurs, gérants et associés actifs qui ne sont pas attribuées en vertu d''un contrat de travail', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1131, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '62', '1356', 'Rémunérations, charges sociales et pensions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1132, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '620', '1131', 'Rémunérations et avantages sociaux directs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1133, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6200', '1132', 'Administrateurs ou gérants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1134, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6201', '1132', 'Personnel de direction', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1135, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6202', '1132', 'Employés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1136, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6203', '1132', 'Ouvriers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1137, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6204', '1132', 'Autres membres du personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1138, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '621', '1131', 'Cotisations patronales d''assurances sociales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1139, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6210', '1138', 'Sur salaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1140, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6211', '1138', 'Sur appointements et commissions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1141, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '622', '1131', 'Primes patronales pour assurances extralégales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1142, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '623', '1131', 'Autres frais de personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1143, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6230', '1142', 'Assurances du personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1144, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '62300', '1143', 'Assurances loi, responsabilité civile, chemin du travail', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1145, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '62301', '1143', 'Assurance salaire garanti', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1146, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '62302', '1143', 'Assurances individuelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1147, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6231', '1142', 'Charges sociales diverses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1148, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '62310', '1147', 'Jours fériés payés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1149, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '62311', '1147', 'Salaire hebdomadaire garanti', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1150, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '62312', '1147', 'Allocations familiales complémentaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1151, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6232', '1142', 'Charges sociales des administrateurs, gérants et commissaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1152, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '62320', '1151', 'Allocations familiales complémentaires pour non salariés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1153, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '62321', '1151', 'Lois sociales pour indépendants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1154, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '62322', '1151', 'Divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1155, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '624', '1131', 'Pensions de retraite et de survie', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1156, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6240', '1155', 'Administrateurs et gérants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1157, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6241', '1155', 'Personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1158, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '625', '1131', 'Provision pour pécule de vacances', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1159, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6250', '1158', 'Dotations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1160, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6251', '1158', 'Utilisations et reprises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1161, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '63', '1356', 'Amortissements, réductions de valeur et provisions pour risques et charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1162, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '630', '1161', 'Dotations aux amortissements et aux réductions de valeur sur immobilisations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1163, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6300', '1162', 'Dotations aux amortissements sur frais d''établissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1164, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6301', '1162', 'Dotations aux amortissements sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1165, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6302', '1162', 'Dotations aux amortissements sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1166, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6308', '1162', 'Dotations aux réductions de valeur sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1167, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6309', '1162', 'Dotations aux réductions de valeur sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1168, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '631', '1161', 'Réductions de valeur sur stocks', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1169, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6310', '1168', 'Dotations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1170, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6311', '1168', 'Reprises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1171, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '632', '1161', 'Réductions de valeur sur commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1172, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6320', '1171', 'Dotations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1173, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6321', '1171', 'Reprises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1174, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '633', '1161', 'Réductions de valeur sur créances commerciales à plus d''un an', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1175, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6330', '1174', 'Dotations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1176, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6331', '1174', 'Reprises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1177, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '634', '1161', 'Réductions de valeur sur créances commerciales à un an au plus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1178, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6340', '1177', 'Dotations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1179, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6341', '1177', 'Reprises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1180, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '635', '1161', 'Provisions pour pensions et obligations similaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1181, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6350', '1180', 'Dotations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1182, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6351', '1180', 'Utilisations et reprises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1183, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '636', '11613', 'Provisions pour grosses réparations et gros entretiens', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1184, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6360', '1183', 'Dotations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1185, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6361', '1183', 'Utilisations et reprises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1186, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '637', '1161', 'Provisions pour autres risques et charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1187, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6370', '1186', 'Dotations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1188, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6371', '1186', 'Utilisations et reprises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1189, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '64', '1356', 'Autres charges d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1190, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '640', '1189', 'Charges fiscales d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1191, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6400', '1190', 'Taxes et impôts directs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1192, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '64000', '1191', 'Taxes sur autos et camions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1193, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6401', '1190', 'Taxes et impôts indirects', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1194, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '64010', '1193', 'Timbres fiscaux pris en charge par la firme', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1195, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '64011', '1193', 'Droits d''enregistrement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1196, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '64012', '1193', 'T.V.A. non déductible', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1197, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6402', '1190', 'Impôts provinciaux et communaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1198, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '64020', '1197', 'Taxe sur la force motrice', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1199, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '64021', '1197', 'Taxe sur le personnel occupé', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1200, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6403', '1190', 'Taxes diverses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1201, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '641', '1189', 'Moins-values sur réalisations courantes d''immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1202, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '642', '1189', 'Moins-values sur réalisations de créances commerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1203, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '643', '1189', 'à 648 Charges d''exploitations diverses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1204, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '649', '1189', 'Charges d''exploitation portées à l''actif au titre de restructuration', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1205, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '65', '1356', 'Charges financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1206, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '650', '1205', 'Charges des dettes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1207, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6500', '1206', 'Intérêts, commissions et frais afférents aux dettes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1208, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6501', '1206', 'Amortissements des agios et frais d''émission d''emprunts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1209, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6502', '1206', 'Autres charges de dettes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1210, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6503', '1206', 'Intérêts intercalaires portés à l''actif', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1211, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '651', '1205', 'Réductions de valeur sur actifs circulants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1212, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6510', '1211', 'Dotations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1213, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6511', '1211', 'Reprises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1214, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '652', '1205', 'Moins-values sur réalisation d''actifs circulants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1215, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '653', '1205', 'Charges d''escompte de créances', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1216, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '654', '1205', 'Différences de change', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1217, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '655', '1205', 'Ecarts de conversion des devises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1218, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '656', '1205', 'Frais de banques, de chèques postaux', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1219, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '657', '1205', 'Commissions sur ouvertures de crédit, cautions et avals', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1220, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '658', '1205', 'Frais de vente des titres', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1221, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '66', '1356', 'Charges exceptionnelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1222, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '660', '1221', 'Amortissements et réductions de valeur exceptionnels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1223, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6600', '1222', 'Sur frais d''établissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1224, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6601', '1222', 'Sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1225, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6602', '1222', 'Sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1226, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '661', '1221', 'Réductions de valeur sur immobilisations financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1227, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '662', '1221', 'Provisions pour risques et charges exceptionnels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1228, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '663', '1221', 'Moins-values sur réalisation d''actifs immobilisés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1229, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6630', '1228', 'Sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1230, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6631', '1228', 'Sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1231, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6632', '1228', 'Sur immobilisations détenues en location-financement et droits similaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1232, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6633', '1228', 'Sur immobilisations financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1233, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6634', '1228', 'Sur immeubles acquis ou construits en vue de la revente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1234, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '664', '1221', 'à 668 Autres charges exceptionnelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1235, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '664', '1221', 'Pénalités et amendes diverses', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1236, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '665', '1221', 'Différence de charge', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1237, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '669', '1221', 'Charges exceptionnelles transférées à l''actif en frais de restructuration', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1238, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '67', '1356', 'Impôts sur le résultat', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1239, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '670', '1238', 'Impôts belges sur le résultat de l''exercice', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1240, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6700', '1239', 'Impôts et précomptes dus ou versés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1241, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6701', '1239', 'Excédent de versements d''impôts et précomptes porté à l''actif', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1242, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6702', '1239', 'Charges fiscales estimées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1243, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '671', '1238', 'Impôts belges sur le résultat d''exercices antérieurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1244, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6710', '1243', 'Suppléments d''impôts dus ou versés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1245, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6711', '1243', 'Suppléments d''impôts estimés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1246, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6712', '1243', 'Provisions fiscales constituées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1247, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '672', '1238', 'Impôts étrangers sur le résultat de l''exercice', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1248, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '673', '1238', 'Impôts étrangers sur le résultat d''exercices antérieurs', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1249, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '68', '1356', 'Transferts aux réserves immunisées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1250, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '69', '1356', 'Affectation des résultats', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1251, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '690', '1250', 'Perte reportée de l''exercice précédent', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1252, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '691', '1250', 'Dotation à la réserve légale', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1253, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '692', '1250', 'Dotation aux autres réserves', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1254, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '693', '1250', 'Bénéfice à reporter', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1255, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '694', '1250', 'Rémunération du capital', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1256, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '695', '1250', 'Administrateurs ou gérants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1257, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '696', '1250', 'Autres allocataires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1258, 'PCMN-BASE', 'PROD', 'XXXXXX', '70', '1357', 'Chiffre d''affaires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1260, 'PCMN-BASE', 'PROD', 'XXXXXX', '700', '1258', 'Ventes de marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1261, 'PCMN-BASE', 'PROD', 'XXXXXX', '7000', '1260', 'Ventes en Belgique', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1262, 'PCMN-BASE', 'PROD', 'XXXXXX', '7001', '1260', 'Ventes dans les pays membres de la C.E.E.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1263, 'PCMN-BASE', 'PROD', 'XXXXXX', '7002', '1260', 'Ventes à l''exportation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1264, 'PCMN-BASE', 'PROD', 'XXXXXX', '701', '1258', 'Ventes de produits finis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1265, 'PCMN-BASE', 'PROD', 'XXXXXX', '7010', '1264', 'Ventes en Belgique', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1266, 'PCMN-BASE', 'PROD', 'XXXXXX', '7011', '1264', 'Ventes dans les pays membres de la C.E.E.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1267, 'PCMN-BASE', 'PROD', 'XXXXXX', '7012', '1264', 'Ventes à l''exportation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1268, 'PCMN-BASE', 'PROD', 'XXXXXX', '702', '1258', 'Ventes de déchets et rebuts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1269, 'PCMN-BASE', 'PROD', 'XXXXXX', '7020', '1268', 'Ventes en Belgique', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1270, 'PCMN-BASE', 'PROD', 'XXXXXX', '7021', '1268', 'Ventes dans les pays membres de la C.E.E.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1271, 'PCMN-BASE', 'PROD', 'XXXXXX', '7022', '1268', 'Ventes à l''exportation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1272, 'PCMN-BASE', 'PROD', 'XXXXXX', '703', '1258', 'Ventes d''emballages récupérables', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1273, 'PCMN-BASE', 'PROD', 'XXXXXX', '704', '1258', 'Facturations des travaux en cours (associations momentanées)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1274, 'PCMN-BASE', 'PROD', 'XXXXXX', '705', '1258', 'Prestations de services', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1275, 'PCMN-BASE', 'PROD', 'XXXXXX', '7050', '1274', 'Prestations de services en Belgique', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1276, 'PCMN-BASE', 'PROD', 'XXXXXX', '7051', '1274', 'Prestations de services dans les pays membres de la C.E.E.', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1277, 'PCMN-BASE', 'PROD', 'XXXXXX', '7052', '1274', 'Prestations de services en vue de l''exportation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1278, 'PCMN-BASE', 'PROD', 'XXXXXX', '706', '1258', 'Pénalités et dédits obtenus par l''entreprise', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1279, 'PCMN-BASE', 'PROD', 'XXXXXX', '708', '1258', 'Remises, ristournes et rabais accordés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1280, 'PCMN-BASE', 'PROD', 'XXXXXX', '7080', '1279', 'Sur ventes de marchandises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1281, 'PCMN-BASE', 'PROD', 'XXXXXX', '7081', '1279', 'Sur ventes de produits finis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1282, 'PCMN-BASE', 'PROD', 'XXXXXX', '7082', '1279', 'Sur ventes de déchets et rebuts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1283, 'PCMN-BASE', 'PROD', 'XXXXXX', '7083', '1279', 'Sur prestations de services', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1284, 'PCMN-BASE', 'PROD', 'XXXXXX', '7084', '1279', 'Mali sur travaux facturés aux associations momentanées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1285, 'PCMN-BASE', 'PROD', 'XXXXXX', '71', '1357', 'Variation des stocks et des commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1286, 'PCMN-BASE', 'PROD', 'XXXXXX', '712', '1285', 'Des en cours de fabrication', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1287, 'PCMN-BASE', 'PROD', 'XXXXXX', '713', '1285', 'Des produits finis', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1288, 'PCMN-BASE', 'PROD', 'XXXXXX', '715', '1285', 'Des immeubles construits destinés à la vente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1289, 'PCMN-BASE', 'PROD', 'XXXXXX', '717', '1285', 'Des commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1290, 'PCMN-BASE', 'PROD', 'XXXXXX', '7170', '1289', 'Commandes en cours - Coût de revient', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1291, 'PCMN-BASE', 'PROD', 'XXXXXX', '71700', '1290', 'Coût des commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1292, 'PCMN-BASE', 'PROD', 'XXXXXX', '71701', '1290', 'Coût des travaux en cours des associations momentanées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1293, 'PCMN-BASE', 'PROD', 'XXXXXX', '7171', '1289', 'Bénéfices portés en compte sur commandes en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1294, 'PCMN-BASE', 'PROD', 'XXXXXX', '71710', '1293', 'Sur commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1295, 'PCMN-BASE', 'PROD', 'XXXXXX', '71711', '1293', 'Sur travaux en cours des associations momentanées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1296, 'PCMN-BASE', 'PROD', 'XXXXXX', '72', '1357', 'Production immobilisée', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1297, 'PCMN-BASE', 'PROD', 'XXXXXX', '720', '1296', 'En frais d''établissement', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1298, 'PCMN-BASE', 'PROD', 'XXXXXX', '721', '1296', 'En immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1299, 'PCMN-BASE', 'PROD', 'XXXXXX', '722', '1296', 'En immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1300, 'PCMN-BASE', 'PROD', 'XXXXXX', '723', '1296', 'En immobilisations en cours', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1301, 'PCMN-BASE', 'PROD', 'XXXXXX', '74', '1357', 'Autres produits d''exploitation', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1302, 'PCMN-BASE', 'PROD', 'XXXXXX', '740', '1301', 'Subsides d''exploitation et montants compensatoires', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1303, 'PCMN-BASE', 'PROD', 'XXXXXX', '741', '1301', 'Plus-values sur réalisations courantes d''immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1304, 'PCMN-BASE', 'PROD', 'XXXXXX', '742', '1301', 'Plus-values sur réalisations de créances commerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1305, 'PCMN-BASE', 'PROD', 'XXXXXX', '743', '1301', 'à 749 Produits d''exploitation divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1306, 'PCMN-BASE', 'PROD', 'XXXXXX', '743', '1301', 'Produits de services exploités dans l''intérêt du personnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1307, 'PCMN-BASE', 'PROD', 'XXXXXX', '744', '1301', 'Commissions et courtages', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1308, 'PCMN-BASE', 'PROD', 'XXXXXX', '745', '1301', 'Redevances pour brevets et licences', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1309, 'PCMN-BASE', 'PROD', 'XXXXXX', '746', '1301', 'Prestations de services (transports, études, etc)', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1310, 'PCMN-BASE', 'PROD', 'XXXXXX', '747', '1301', 'Revenus des immeubles affectés aux activités non professionnelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1311, 'PCMN-BASE', 'PROD', 'XXXXXX', '748', '1301', 'Locations diverses à caractère professionnel', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1312, 'PCMN-BASE', 'PROD', 'XXXXXX', '749', '1301', 'Produits divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1313, 'PCMN-BASE', 'PROD', 'XXXXXX', '7490', '1312', 'Bonis sur reprises d''emballages consignés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1314, 'PCMN-BASE', 'PROD', 'XXXXXX', '7491', '1312', 'Bonis sur travaux en associations momentanées', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1315, 'PCMN-BASE', 'PROD', 'XXXXXX', '75', '1357', 'Produits financiers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1316, 'PCMN-BASE', 'PROD', 'XXXXXX', '750', '1315', 'Produits des immobilisations financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1317, 'PCMN-BASE', 'PROD', 'XXXXXX', '7500', '1316', 'Revenus des actions', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1318, 'PCMN-BASE', 'PROD', 'XXXXXX', '7501', '1316', 'Revenus des obligations', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1319, 'PCMN-BASE', 'PROD', 'XXXXXX', '7502', '1316', 'Revenus des créances à plus d''un an', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1320, 'PCMN-BASE', 'PROD', 'XXXXXX', '751', '1315', 'Produits des actifs circulants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1321, 'PCMN-BASE', 'PROD', 'XXXXXX', '752', '1315', 'Plus-values sur réalisations d''actifs circulants', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1322, 'PCMN-BASE', 'PROD', 'XXXXXX', '753', '1315', 'Subsides en capital et en intérêts', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1323, 'PCMN-BASE', 'PROD', 'XXXXXX', '754', '1315', 'Différences de change', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1324, 'PCMN-BASE', 'PROD', 'XXXXXX', '755', '1315', 'Ecarts de conversion des devises', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1325, 'PCMN-BASE', 'PROD', 'XXXXXX', '756', '1315', 'à 759 Produits financiers divers', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1326, 'PCMN-BASE', 'PROD', 'XXXXXX', '756', '1315', 'Produits des autres créances', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1327, 'PCMN-BASE', 'PROD', 'XXXXXX', '757', '1315', 'Escomptes obtenus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1328, 'PCMN-BASE', 'PROD', 'XXXXXX', '76', '1357', 'Produits exceptionnels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1329, 'PCMN-BASE', 'PROD', 'XXXXXX', '760', '1328', 'Reprises d''amortissements et de réductions de valeur', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1330, 'PCMN-BASE', 'PROD', 'XXXXXX', '7600', '1329', 'Sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1331, 'PCMN-BASE', 'PROD', 'XXXXXX', '7601', '1329', 'Sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1332, 'PCMN-BASE', 'PROD', 'XXXXXX', '761', '1328', 'Reprises de réductions de valeur sur immobilisations financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1333, 'PCMN-BASE', 'PROD', 'XXXXXX', '762', '1328', 'Reprises de provisions pour risques et charges exceptionnelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1334, 'PCMN-BASE', 'PROD', 'XXXXXX', '763', '1328', 'Plus-values sur réalisation d''actifs immobilisés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1335, 'PCMN-BASE', 'PROD', 'XXXXXX', '7630', '1334', 'Sur immobilisations incorporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1336, 'PCMN-BASE', 'PROD', 'XXXXXX', '7631', '1334', 'Sur immobilisations corporelles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1337, 'PCMN-BASE', 'PROD', 'XXXXXX', '7632', '1334', 'Sur immobilisations financières', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1338, 'PCMN-BASE', 'PROD', 'XXXXXX', '764', '1328', 'Autres produits exceptionnels', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1339, 'PCMN-BASE', 'PROD', 'XXXXXX', '77', '1357', 'Régularisations d''impôts et reprises de provisions fiscales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1340, 'PCMN-BASE', 'PROD', 'XXXXXX', '771', '1339', 'Impôts belges sur le résultat', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1341, 'PCMN-BASE', 'PROD', 'XXXXXX', '7710', '1340', 'Régularisations d''impôts dus ou versés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1342, 'PCMN-BASE', 'PROD', 'XXXXXX', '7711', '1340', 'Régularisations d''impôts estimés', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1343, 'PCMN-BASE', 'PROD', 'XXXXXX', '7712', '1340', 'Reprises de provisions fiscales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1344, 'PCMN-BASE', 'PROD', 'XXXXXX', '773', '1339', 'Impôts étrangers sur le résultat', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1345, 'PCMN-BASE', 'PROD', 'XXXXXX', '79', '1357', 'Affectation aux résultats', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1346, 'PCMN-BASE', 'PROD', 'XXXXXX', '790', '1345', 'Bénéfice reporté de l''exercice précédent', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1347, 'PCMN-BASE', 'PROD', 'XXXXXX', '791', '1345', 'Prélèvement sur le capital et les primes d''émission', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1348, 'PCMN-BASE', 'PROD', 'XXXXXX', '792', '1345', 'Prélèvement sur les réserves', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1349, 'PCMN-BASE', 'PROD', 'XXXXXX', '793', '1345', 'Perte à reporter', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1350, 'PCMN-BASE', 'PROD', 'XXXXXX', '794', '1345', 'Intervention d''associés (ou du propriétaire) dans la perte', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1351, 'PCMN-BASE', 'CAPIT', 'XXXXXX', '1', '', 'Fonds propres, provisions pour risques et charges et dettes à plus d''un an', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1352, 'PCMN-BASE', 'IMMO', 'XXXXXX', '2', '', 'Frais d''établissement. Actifs immobilisés et créances à plus d''un an', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1353, 'PCMN-BASE', 'STOCK', 'XXXXXX', '3', '', 'Stock et commandes en cours d''exécution', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1354, 'PCMN-BASE', 'TIERS', 'XXXXXX', '4', '', 'Créances et dettes à un an au plus', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1355, 'PCMN-BASE', 'FINAN', 'XXXXXX', '5', '', 'Placement de trésorerie et de valeurs disponibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1356, 'PCMN-BASE', 'CHARGE', 'XXXXXX', '6', '', 'Charges', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (1357, 'PCMN-BASE', 'PROD', 'XXXXXX', '7', '', 'Produits', '1');

-- Fix: Missing instruction not correctly done into 3.5
-- VPGSQL8.2 ALTER TABLE llx_facture_fourn ALTER fk_mode_reglement DROP NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_facture_fourn ALTER fk_cond_reglement DROP NOT NULL;

UPDATE llx_actioncomm set fk_user_action = fk_user_done where fk_user_done > 0 and (fk_user_action is null or fk_user_action = 0);
UPDATE llx_actioncomm set fk_user_action = fk_user_author where fk_user_author > 0 and (fk_user_action is null or fk_user_action = 0);

