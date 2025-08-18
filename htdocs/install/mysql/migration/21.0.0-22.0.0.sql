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


-- V21 forgotten

ALTER TABLE llx_societe_rib MODIFY COLUMN label varchar(180);
ALTER TABLE llx_societe_rib MODIFY COLUMN iban_prefix varchar(100);

ALTER TABLE llx_societe_account DROP INDEX uk_societe_account_login_website_soc;
ALTER TABLE llx_societe_account ADD UNIQUE INDEX uk_societe_account_login_website(entity, login, site, fk_website);


-- V22 migration

ALTER TABLE llx_c_country ADD COLUMN sepa tinyint DEFAULT 0 NOT NULL;
UPDATE llx_c_country SET sepa = 1 WHERE code IN ('AL','AD','AT','BE','BG','CH','CY','CZ','DE','DK','EE','ES','FI','FR','GB','GG','GR','HR','HU','IE','IM','IS','IT','JE','LI','LT','LU','LV','MC','MD','ME','MK','MT','NL','NO','PL','PM','PT','RO','RS','SE','SI','SK','SM','VA');

-- fix element
UPDATE llx_c_type_contact set element='shipping' WHERE element='expedition';
-- Shipment / Expedition
INSERT INTO llx_c_type_contact (element, source, code, libelle, active ) VALUES ('shipping', 'internal', 'SALESREPFOLL',  'Representative following-up shipping', 1);
INSERT INTO llx_c_type_contact (element, source, code, libelle, active ) VALUES ('shipping', 'external', 'BILLING',       'Customer invoice contact', 1);
INSERT INTO llx_c_type_contact (element, source, code, libelle, active ) VALUES ('shipping', 'external', 'CUSTOMER',      'Customer shipping contact', 1);
INSERT INTO llx_c_type_contact (element, source, code, libelle, active ) VALUES ('shipping', 'external', 'SHIPPING',      'Loading facility', 1);
INSERT INTO llx_c_type_contact (element, source, code, libelle, active ) VALUES ('shipping', 'external', 'DELIVERY',      'Delivery facility', 1);

ALTER TABLE llx_holiday_config DROP INDEX idx_holiday_config;
ALTER TABLE llx_holiday_config ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_holiday_config ADD UNIQUE INDEX idx_holiday_config (entity, name);

ALTER TABLE llx_societe_account ADD COLUMN ip varchar(250);

ALTER TABLE llx_product ADD COLUMN packaging float(24,8) DEFAULT NULL;

-- mailing
UPDATE llx_const SET visible = 0 WHERE name='MAILING_LIMIT_SENDBYWEB';

ALTER TABLE llx_categorie_member ADD COLUMN import_key varchar(14);
ALTER TABLE llx_category_bankline ADD COLUMN import_key varchar(14);


create table llx_categorie_order
(
  fk_categorie integer NOT NULL,
  fk_order     integer NOT NULL,
  import_key   varchar(14)
)ENGINE=innodb;

--noqa:disable=PRS
ALTER TABLE llx_categorie_order ADD PRIMARY KEY pk_categorie_order(fk_categorie, fk_order);
--noqa:enable=PRS
ALTER TABLE llx_categorie_order ADD INDEX idx_categorie_order_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_order ADD INDEX idx_categorie_order_fk_order (fk_order);

ALTER TABLE llx_categorie_order ADD CONSTRAINT fk_categorie_order_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_order ADD CONSTRAINT fk_categorie_order_fk_order_rowid FOREIGN KEY (fk_order) REFERENCES llx_commande (rowid);


create table llx_categorie_invoice
(
  fk_categorie integer NOT NULL,
  fk_invoice   integer NOT NULL,
  import_key   varchar(14)
)ENGINE=innodb;

--noqa:disable=PRS
ALTER TABLE llx_categorie_invoice ADD PRIMARY KEY pk_categorie_invoice(fk_categorie, fk_invoice);
--noqa:enable=PRS
ALTER TABLE llx_categorie_invoice ADD INDEX idx_categorie_invoice_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_invoice ADD INDEX idx_categorie_invoice_fk_invoice (fk_invoice);

ALTER TABLE llx_categorie_invoice ADD CONSTRAINT fk_categorie_invoice_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_invoice ADD CONSTRAINT fk_categorie_invoice_fk_invoice_rowid FOREIGN KEY (fk_invoice) REFERENCES llx_facture (rowid);


create table llx_categorie_supplier_order
(
  fk_categorie      integer NOT NULL,
  fk_supplier_order integer NOT NULL,
  import_key        varchar(14)
)ENGINE=innodb;

--noqa:disable=PRS
ALTER TABLE llx_categorie_supplier_order ADD PRIMARY KEY pk_categorie_supplier_order(fk_categorie, fk_supplier_order);
--noqa:enable=PRS
ALTER TABLE llx_categorie_supplier_order ADD INDEX idx_categorie_supplier_order_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_supplier_order ADD INDEX idx_categorie_supplier_order_fk_supplier_order (fk_supplier_order);

ALTER TABLE llx_categorie_supplier_order ADD CONSTRAINT fk_categorie_supplier_order_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_supplier_order ADD CONSTRAINT fk_categorie_supplier_order_fk_supplier_order_rowid FOREIGN KEY (fk_supplier_order) REFERENCES llx_commande_fournisseur (rowid);


create table llx_categorie_supplier_invoice
(
  fk_categorie        integer NOT NULL,
  fk_supplier_invoice integer NOT NULL,
  import_key          varchar(14)
)ENGINE=innodb;

--noqa:disable=PRS
ALTER TABLE llx_categorie_supplier_invoice ADD PRIMARY KEY pk_categorie_supplier_invoice(fk_categorie, fk_supplier_invoice);
--noqa:enable=PRS

ALTER TABLE llx_categorie_supplier_invoice ADD INDEX idx_categorie_supplier_invoice_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_supplier_invoice ADD INDEX idx_categorie_supplier_invoice_fk_supplier_invoice (fk_supplier_invoice);

ALTER TABLE llx_categorie_supplier_invoice ADD CONSTRAINT fk_categorie_supplier_invoice_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_supplier_invoice ADD CONSTRAINT fk_categorie_supplier_invoice_fk_supplier_invoice_rowid FOREIGN KEY (fk_supplier_invoice) REFERENCES llx_facture_fourn (rowid);


CREATE TABLE llx_bank_record
(
  rowid             integer     AUTO_INCREMENT PRIMARY KEY,
  ref 				varchar(50) NOT NULL,
  fk_bank			integer		NOT NULL,
  dt_from			date		NOT NULL,
  dt_to				date		NOT NULL,
  date_creation datetime NOT NULL,
  date_valid datetime NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=innodb;

ALTER TABLE llx_bank_record ADD CONSTRAINT bank_record_fk_bank FOREIGN KEY (fk_bank) REFERENCES llx_bank_account (rowid);

CREATE TABLE llx_bank_record_link
(
  rowid             integer     AUTO_INCREMENT PRIMARY KEY,
  fk_bank_record	integer		NOT NULL,
  fk_bank_import	integer		NOT NULL
)ENGINE=innodb;

CREATE TABLE llx_bank_import
(
  rowid                 integer         AUTO_INCREMENT PRIMARY KEY,
  id_account			integer			NOT NULL,              	-- bank account ID in Dolibarr
  record_type 			varchar(64)   	NULL,                  	-- OFX Type of transaction: DIRECTDEBIT, XFER, OTHER or code/type of operation
  label         		varchar(255)  	NOT NULL,               -- label of operation
  record_type_origin  	varchar(255)  	NOT NULL,               -- operation code/type origin
  label_origin  		varchar(255)  	NOT NULL,               -- label of operation origin
  comment				text			NULL,                   -- Comment/Motif
  note				    text			NULL,                   -- Notes like "References"
  bdate					date			NULL,                   -- date operation
  vdate					date			NULL,                   -- date value
  date_scraped			datetime		NULL,                  	-- date discarded
  original_amount		double(24,8)	NULL,                	-- OFX amount
  original_currency		varchar(255)	NULL,              		-- OFX Currency
  amount_debit			double(24,8)	NOT NULL,          		-- money spent. For statement using debit/credit. For statement using 1 amount, use original_amount.
  amount_credit       	double(24,8)  NOT NULL,          		-- money received. For statement using debit/credit. For statement using 1 amount, use original_amount.
  deleted_date			datetime		NULL,                  	-- to flag this record as deleted
  fk_duplicate_of		integer			NULL,                  	-- to flag this record as a duplicate of another one
  status				smallint		NOT NULL,               -- 0=just imported
  datec					datetime		NOT NULL,		        -- date creation
  tms					timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,	-- date of last modification
  fk_user_author	    integer         NOT NULL, 		   		-- user who created the record
  fk_user_modif		    integer,					            -- user who modified the record
  import_key			varchar(14),					        -- import key
  datas					text			NOT NULL                -- full record/line coming from source
)ENGINE=innodb;


ALTER TABLE llx_bank_record_link ADD CONSTRAINT fk_bank_record_bank_record FOREIGN KEY (fk_bank_record) REFERENCES llx_bank_record (rowid);
ALTER TABLE llx_bank_record_link ADD CONSTRAINT fk_bank_import_bank_import FOREIGN KEY (fk_bank_import) REFERENCES llx_bank_import (rowid);

ALTER TABLE llx_commandedet ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_commande_fournisseurdet ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_contratdet ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_deliverydet ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_expeditiondet ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_facturedet ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_facturedet_rec ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_facture_fourn_det ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_facture_fourn_det_rec ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_fichinterdet ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_fichinterdet_rec ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_propaldet ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_receptiondet_batch ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_supplier_proposaldet ADD COLUMN extraparams varchar(255);

ALTER TABLE llx_facture_rec ADD COLUMN rule_for_lines_dates varchar(255) DEFAULT 'prepaid';

ALTER TABLE llx_product_customer_price ADD COLUMN date_begin date AFTER ref_customer;
ALTER TABLE llx_product_customer_price ADD COLUMN date_end date AFTER date_begin;
ALTER TABLE llx_product_customer_price ADD COLUMN discount_percent real DEFAULT 0 AFTER localtax2_type;
ALTER TABLE llx_product_customer_price_log ADD COLUMN date_begin date AFTER ref_customer;
ALTER TABLE llx_product_customer_price_log ADD COLUMN date_end date AFTER date_begin;
ALTER TABLE llx_product_customer_price_log ADD COLUMN discount_percent real DEFAULT 0 AFTER localtax2_type;
ALTER TABLE llx_product_customer_price DROP FOREIGN KEY fk_product_customer_price_fk_product;
ALTER TABLE llx_product_customer_price DROP FOREIGN KEY fk_product_customer_price_fk_soc;
ALTER TABLE llx_product_customer_price DROP FOREIGN KEY fk_customer_price_fk_product;
ALTER TABLE llx_product_customer_price DROP FOREIGN KEY fk_customer_price_fk_soc;
ALTER TABLE llx_product_customer_price DROP INDEX uk_customer_price_fk_product_fk_soc;
ALTER TABLE llx_product_customer_price ADD UNIQUE INDEX uk_customer_price_fk_product_fk_soc (fk_product, fk_soc, date_begin);
ALTER TABLE llx_product_customer_price ADD CONSTRAINT fk_product_customer_price_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product(rowid);
ALTER TABLE llx_product_customer_price ADD CONSTRAINT fk_product_customer_price_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);
UPDATE llx_product_customer_price SET date_begin = datec WHERE date_begin IS NULL;
UPDATE llx_product_customer_price_log SET date_begin = datec WHERE date_begin IS NULL;

ALTER TABLE llx_accounting_bookkeeping ADD COLUMN ref VARCHAR(30) AFTER rowid;
ALTER TABLE llx_accounting_bookkeeping_tmp ADD COLUMN ref VARCHAR(30) AFTER rowid;

ALTER TABLE llx_accounting_bookkeeping ADD INDEX idx_accounting_bookkeeping_ref (ref);
ALTER TABLE llx_accounting_bookkeeping_tmp ADD INDEX idx_accounting_bookkeeping_tmp_ref (ref);

ALTER TABLE llx_session ADD COLUMN date_creation datetime AFTER session_variable;
UPDATE llx_session SET date_creation = NOW() WHERE date_creation IS NULL;
-- VMYSQL4.3 ALTER TABLE llx_session MODIFY COLUMN date_creation datetime NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_session ALTER COLUMN date_creation SET NOT NULL;

ALTER TABLE llx_accounting_account ADD COLUMN centralized tinyint DEFAULT 0 NOT NULL AFTER active;
UPDATE llx_accounting_account as acc SET acc.centralized = 1 WHERE acc.account_number in (SELECT value  FROM llx_const WHERE name IN (__ENCRYPT('ACCOUNTING_ACCOUNT_CUSTOMER')__,__ENCRYPT('ACCOUNTING_ACCOUNT_SUPPLIER')__,__ENCRYPT('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT')__,__ENCRYPT('ACCOUNTING_ACCOUNT_EXPENSEREPORT')__));

-- invert constant STOCK_ALLOW_NEGATIVE_TRANSFER because it was automatically set to 1, deleting the user config.
INSERT INTO llx_const (name, entity, value, type, visible, note) SELECT DISTINCT 'STOCK_DISALLOW_NEGATIVE_TRANSFER', entity, 1, 'chaine', 0, '' FROM llx_const c1 WHERE NOT EXISTS (SELECT rowid FROM llx_const c2 WHERE c2.name = 'STOCK_ALLOW_NEGATIVE_TRANSFER' AND c2.value = '1' AND c2.entity = c1.entity);
UPDATE llx_const SET name = 'STOCK_DISALLOW_NEGATIVE_TRANSFER', value = 1 WHERE name = 'STOCK_ALLOW_NEGATIVE_TRANSFER' AND value = '0';
DELETE FROM llx_const WHERE name = 'STOCK_ALLOW_NEGATIVE_TRANSFER' AND value = '1';

ALTER TABLE llx_links ADD COLUMN  share varchar(128) NULL AFTER objectid;
ALTER TABLE llx_links ADD COLUMN  share_pass varchar(32) NULL AFTER share;


ALTER TABLE llx_expeditiondet ADD COLUMN fk_parent integer NULL AFTER fk_product;	-- for sublines
ALTER TABLE llx_expeditiondet ADD INDEX idx_expeditiondet_fk_parent (fk_parent);
--ALTER TABLE llx_expeditiondet ADD CONSTRAINT fk_expeditiondet_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
--ALTER TABLE llx_expeditiondet ADD CONSTRAINT fk_expeditiondet_fk_parent FOREIGN KEY (fk_parent) REFERENCES llx_expeditiondet (rowid);

UPDATE llx_expeditiondet as ed SET ed.fk_product = (SELECT cd.fk_product FROM llx_commandedet as cd WHERE cd.rowid = ed.fk_elementdet AND ed.element_type = 'commande') WHERE ed.fk_product IS NULL;

ALTER TABLE llx_webhook_target ADD COLUMN type integer DEFAULT 0 NOT NULL AFTER label;

-- remove foreign keys we should not have (bad name and bad use)
ALTER TABLE llx_webhook_target DROP FOREIGN KEY llx_webhook_target_fk_user_creat;
ALTER TABLE llx_webhook_target DROP FOREIGN KEY fk_webhook_target_fk_user_creat;

INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES (__ENTITY__, 'pixelfed', 'Pixelfed', '{socialid}', 'fa-pixelfed', 0);

-- Add input reason on invoice
ALTER TABLE llx_facture ADD COLUMN fk_input_reason integer NULL DEFAULT NULL AFTER last_main_doc;
ALTER TABLE llx_facture ADD INDEX idx_facture_fk_input_reason (fk_input_reason);
ALTER TABLE llx_facture ADD CONSTRAINT fk_facture_fk_input_reason FOREIGN KEY (fk_input_reason) REFERENCES llx_c_input_reason (rowid);
ALTER TABLE llx_website ADD COLUMN paymentframemode integer DEFAULT 0;
ALTER TABLE llx_contratdet DROP COLUMN price_ht;
ALTER TABLE llx_contratdet DROP COLUMN remise;

ALTER TABLE llx_extrafields ADD COLUMN aiprompt text;

ALTER TABLE llx_menu ADD COLUMN showtopmenuinframe integer DEFAULT 0;

ALTER TABLE llx_entrepot MODIFY COLUMN phone varchar(30);
ALTER TABLE llx_entrepot MODIFY COLUMN fax varchar(30);
ALTER TABLE llx_establishment MODIFY COLUMN phone varchar(30);
ALTER TABLE llx_resource MODIFY COLUMN phone varchar(30);
ALTER TABLE llx_societe MODIFY COLUMN phone varchar(30);
ALTER TABLE llx_societe MODIFY COLUMN phone_mobile varchar(30);
ALTER TABLE llx_societe MODIFY COLUMN fax varchar(30);
ALTER TABLE llx_user MODIFY COLUMN office_phone varchar(30);
ALTER TABLE llx_user MODIFY COLUMN office_fax varchar(30);
ALTER TABLE llx_user MODIFY COLUMN user_mobile varchar(30);
ALTER TABLE llx_user MODIFY COLUMN personal_mobile varchar(30);
ALTER TABLE llx_asset ADD COLUMN fk_user_valid integer;
ALTER TABLE llx_asset ADD COLUMN date_valid datetime;

CREATE TABLE llx_webhook_history(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	trigger_data text NOT NULL,
	fk_target integer NOT NULL,
	url integer NOT NULL,
	note_private text,
	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	import_key varchar(14),
	status integer DEFAULT 1 NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_societe_rib ADD COLUMN cci varchar(100) after iban_prefix;    -- Interbank code for some countries like Chile

-- Move permission thirdparty_paymentinformation out of advanced rights
UPDATE llx_rights_def SET perms = 'thirdparty_paymentinformation' WHERE perms = 'thirdparty_paymentinformation_advance';

ALTER TABLE llx_eventorganization_conferenceorboothattendee DROP INDEX idx_eventorganization_conferenceorboothattendee_ref;
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD UNIQUE INDEX uk_eventorganization_confboothattendee(ref);

ALTER TABLE llx_facture_rec ADD COLUMN usenewcurrencyrate integer DEFAULT 0;
ALTER TABLE llx_facture_fourn_rec ADD COLUMN usenewcurrencyrate integer DEFAULT 0;

ALTER TABLE llx_don ADD COLUMN ip varchar(250);

ALTER TABLE llx_expeditiondet ADD COLUMN description text AFTER fk_entrepot;

INSERT INTO llx_c_type_container (code, label, active, module, position, typecontainer, entity) VALUES ('setup', 'Setup screen', 1, 'system', 500, 'library', __ENTITY__);

ALTER TABLE llx_mrp_mo ADD COLUMN extraparams varchar(255) DEFAULT NULL;

UPDATE llx_actioncomm set code = 'AC_PAYMENT_STRIPE_IPN_SEPA_KO' where code = 'AC_IPN' and label like 'Payment error (SEPA%';

ALTER TABLE llx_blockedlog ADD COLUMN debuginfo mediumtext;

ALTER TABLE llx_webhook_history ADD COLUMN trigger_code text NOT NULL;
ALTER TABLE llx_webhook_history ADD COLUMN error_message text;
ALTER TABLE llx_webhook_history MODIFY COLUMN url varchar(255);
