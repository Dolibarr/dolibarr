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
UPDATE llx_c_country SET sepa = 1 WHERE code IN ('AD','AT','BE','BG','CH','CY','CZ','DE','DK','EE','ES','FI','FR','GR','HR','HU','IE','IT','LT','LU','LV','MC','MT','NL','PL','PT','RO','SE','SI','SK','SM','VA');

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

ALTER TABLE `llx_c_country` ADD COLUMN `phone_code` varchar(8) DEFAULT NULL;
UPDATE llx_c_country SET phone_code = '+33' WHERE code = 'FR';
UPDATE llx_c_country SET phone_code = '+32' WHERE code = 'BE';
UPDATE llx_c_country SET phone_code = '+39' WHERE code = 'IT';
UPDATE llx_c_country SET phone_code = '+34' WHERE code = 'ES';
UPDATE llx_c_country SET phone_code = '+49' WHERE code = 'DE';
UPDATE llx_c_country SET phone_code = '+41' WHERE code = 'CH';
UPDATE llx_c_country SET phone_code = '+44' WHERE code = 'GB';
UPDATE llx_c_country SET phone_code = '+353' WHERE code = 'IE';
UPDATE llx_c_country SET phone_code = '+86' WHERE code = 'CN';
UPDATE llx_c_country SET phone_code = '+216' WHERE code = 'TN';
UPDATE llx_c_country SET phone_code = '+1' WHERE code = 'US';
UPDATE llx_c_country SET phone_code = '+212' WHERE code = 'MA';
UPDATE llx_c_country SET phone_code = '+213' WHERE code = 'DZ';
UPDATE llx_c_country SET phone_code = '+1' WHERE code = 'CA';
UPDATE llx_c_country SET phone_code = '+228' WHERE code = 'TG';
UPDATE llx_c_country SET phone_code = '+241' WHERE code = 'GA';
UPDATE llx_c_country SET phone_code = '+31' WHERE code = 'NL';
UPDATE llx_c_country SET phone_code = '+36' WHERE code = 'HU';
UPDATE llx_c_country SET phone_code = '+7' WHERE code = 'RU';
UPDATE llx_c_country SET phone_code = '+46' WHERE code = 'SE';
UPDATE llx_c_country SET phone_code = '+225' WHERE code = 'CI';
UPDATE llx_c_country SET phone_code = '+221' WHERE code = 'SN';
UPDATE llx_c_country SET phone_code = '+54' WHERE code = 'AR';
UPDATE llx_c_country SET phone_code = '+237' WHERE code = 'CM';
UPDATE llx_c_country SET phone_code = '+351' WHERE code = 'PT';
UPDATE llx_c_country SET phone_code = '+966' WHERE code = 'SA';
UPDATE llx_c_country SET phone_code = '+377' WHERE code = 'MC';
UPDATE llx_c_country SET phone_code = '+61' WHERE code = 'AU';
UPDATE llx_c_country SET phone_code = '+65' WHERE code = 'SG';
UPDATE llx_c_country SET phone_code = '+93' WHERE code = 'AF';
UPDATE llx_c_country SET phone_code = '+358' WHERE code = 'AX';
UPDATE llx_c_country SET phone_code = '+355' WHERE code = 'AL';
UPDATE llx_c_country SET phone_code = '+1-684' WHERE code = 'AS';
UPDATE llx_c_country SET phone_code = '+376' WHERE code = 'AD';
UPDATE llx_c_country SET phone_code = '+244' WHERE code = 'AO';
UPDATE llx_c_country SET phone_code = '+1-264' WHERE code = 'AI';
UPDATE llx_c_country SET phone_code = '' WHERE code = 'AQ';
UPDATE llx_c_country SET phone_code = '+1-268' WHERE code = 'AG';
UPDATE llx_c_country SET phone_code = '+374' WHERE code = 'AM';
UPDATE llx_c_country SET phone_code = '+297' WHERE code = 'AW';
UPDATE llx_c_country SET phone_code = '+43' WHERE code = 'AT';
UPDATE llx_c_country SET phone_code = '+994' WHERE code = 'AZ';
UPDATE llx_c_country SET phone_code = '+1-242' WHERE code = 'BS';
UPDATE llx_c_country SET phone_code = '+973' WHERE code = 'BH';
UPDATE llx_c_country SET phone_code = '+880' WHERE code = 'BD';
UPDATE llx_c_country SET phone_code = '+1-246' WHERE code = 'BB';
UPDATE llx_c_country SET phone_code = '+375' WHERE code = 'BY';
UPDATE llx_c_country SET phone_code = '+501' WHERE code = 'BZ';
UPDATE llx_c_country SET phone_code = '+229' WHERE code = 'BJ';
UPDATE llx_c_country SET phone_code = '+1-441' WHERE code = 'BM';
UPDATE llx_c_country SET phone_code = '+975' WHERE code = 'BT';
UPDATE llx_c_country SET phone_code = '+591' WHERE code = 'BO';
UPDATE llx_c_country SET phone_code = '+387' WHERE code = 'BA';
UPDATE llx_c_country SET phone_code = '+267' WHERE code = 'BW';
UPDATE llx_c_country SET phone_code = '' WHERE code = 'BV';
UPDATE llx_c_country SET phone_code = '+55' WHERE code = 'BR';
UPDATE llx_c_country SET phone_code = '+246' WHERE code = 'IO';
UPDATE llx_c_country SET phone_code = '+673' WHERE code = 'BN';
UPDATE llx_c_country SET phone_code = '+359' WHERE code = 'BG';
UPDATE llx_c_country SET phone_code = '+226' WHERE code = 'BF';
UPDATE llx_c_country SET phone_code = '+257' WHERE code = 'BI';
UPDATE llx_c_country SET phone_code = '+855' WHERE code = 'KH';
UPDATE llx_c_country SET phone_code = '+238' WHERE code = 'CV';
UPDATE llx_c_country SET phone_code = '+1-345' WHERE code = 'KY';
UPDATE llx_c_country SET phone_code = '+236' WHERE code = 'CF';
UPDATE llx_c_country SET phone_code = '+235' WHERE code = 'TD';
UPDATE llx_c_country SET phone_code = '+56' WHERE code = 'CL';
UPDATE llx_c_country SET phone_code = '+61' WHERE code = 'CX';
UPDATE llx_c_country SET phone_code = '+61' WHERE code = 'CC';
UPDATE llx_c_country SET phone_code = '+57' WHERE code = 'CO';
UPDATE llx_c_country SET phone_code = '+269' WHERE code = 'KM';
UPDATE llx_c_country SET phone_code = '+242' WHERE code = 'CG';
UPDATE llx_c_country SET phone_code = '+243' WHERE code = 'CD';
UPDATE llx_c_country SET phone_code = '+682' WHERE code = 'CK';
UPDATE llx_c_country SET phone_code = '+506' WHERE code = 'CR';
UPDATE llx_c_country SET phone_code = '+385' WHERE code = 'HR';
UPDATE llx_c_country SET phone_code = '+53' WHERE code = 'CU';
UPDATE llx_c_country SET phone_code = '+357' WHERE code = 'CY';
UPDATE llx_c_country SET phone_code = '+420' WHERE code = 'CZ';
UPDATE llx_c_country SET phone_code = '+45' WHERE code = 'DK';
UPDATE llx_c_country SET phone_code = '+253' WHERE code = 'DJ';
UPDATE llx_c_country SET phone_code = '+1-767' WHERE code = 'DM';
UPDATE llx_c_country SET phone_code = '+1-809' WHERE code = 'DO';
UPDATE llx_c_country SET phone_code = '+593' WHERE code = 'EC';
UPDATE llx_c_country SET phone_code = '+20' WHERE code = 'EG';
UPDATE llx_c_country SET phone_code = '+503' WHERE code = 'SV';
UPDATE llx_c_country SET phone_code = '+240' WHERE code = 'GQ';
UPDATE llx_c_country SET phone_code = '+291' WHERE code = 'ER';
UPDATE llx_c_country SET phone_code = '+372' WHERE code = 'EE';
UPDATE llx_c_country SET phone_code = '+251' WHERE code = 'ET';
UPDATE llx_c_country SET phone_code = '+500' WHERE code = 'FK';
UPDATE llx_c_country SET phone_code = '+298' WHERE code = 'FO';
UPDATE llx_c_country SET phone_code = '+679' WHERE code = 'FJ';
UPDATE llx_c_country SET phone_code = '+358' WHERE code = 'FI';
UPDATE llx_c_country SET phone_code = '+594' WHERE code = 'GF';
UPDATE llx_c_country SET phone_code = '+689' WHERE code = 'PF';
UPDATE llx_c_country SET phone_code = '+262' WHERE code = 'TF';
UPDATE llx_c_country SET phone_code = '+220' WHERE code = 'GM';
UPDATE llx_c_country SET phone_code = '+995' WHERE code = 'GE';
UPDATE llx_c_country SET phone_code = '+233' WHERE code = 'GH';
UPDATE llx_c_country SET phone_code = '+350' WHERE code = 'GI';
UPDATE llx_c_country SET phone_code = '+30' WHERE code = 'GR';
UPDATE llx_c_country SET phone_code = '+299' WHERE code = 'GL';
UPDATE llx_c_country SET phone_code = '+1-473' WHERE code = 'GD';
UPDATE llx_c_country SET phone_code = '+1-671' WHERE code = 'GU';
UPDATE llx_c_country SET phone_code = '+502' WHERE code = 'GT';
UPDATE llx_c_country SET phone_code = '+224' WHERE code = 'GN';
UPDATE llx_c_country SET phone_code = '+245' WHERE code = 'GW';
UPDATE llx_c_country SET phone_code = '+509' WHERE code = 'HT';
UPDATE llx_c_country SET phone_code = '+672' WHERE code = 'HM';
UPDATE llx_c_country SET phone_code = '+379' WHERE code = 'VA';
UPDATE llx_c_country SET phone_code = '+504' WHERE code = 'HN';
UPDATE llx_c_country SET phone_code = '+852' WHERE code = 'HK';
UPDATE llx_c_country SET phone_code = '+354' WHERE code = 'IS';
UPDATE llx_c_country SET phone_code = '+91' WHERE code = 'IN';
UPDATE llx_c_country SET phone_code = '+62' WHERE code = 'ID';
UPDATE llx_c_country SET phone_code = '+98' WHERE code = 'IR';
UPDATE llx_c_country SET phone_code = '+964' WHERE code = 'IQ';
UPDATE llx_c_country SET phone_code = '+972' WHERE code = 'IL';
UPDATE llx_c_country SET phone_code = '+1-876' WHERE code = 'JM';
UPDATE llx_c_country SET phone_code = '+81' WHERE code = 'JP';
UPDATE llx_c_country SET phone_code = '+962' WHERE code = 'JO';
UPDATE llx_c_country SET phone_code = '+7' WHERE code = 'KZ';
UPDATE llx_c_country SET phone_code = '+254' WHERE code = 'KE';
UPDATE llx_c_country SET phone_code = '+686' WHERE code = 'KI';
UPDATE llx_c_country SET phone_code = '+850' WHERE code = 'KP';
UPDATE llx_c_country SET phone_code = '+82' WHERE code = 'KR';
UPDATE llx_c_country SET phone_code = '+965' WHERE code = 'KW';
UPDATE llx_c_country SET phone_code = '+996' WHERE code = 'KG';
UPDATE llx_c_country SET phone_code = '+856' WHERE code = 'LA';
UPDATE llx_c_country SET phone_code = '+371' WHERE code = 'LV';
UPDATE llx_c_country SET phone_code = '+961' WHERE code = 'LB';
UPDATE llx_c_country SET phone_code = '+266' WHERE code = 'LS';
UPDATE llx_c_country SET phone_code = '+231' WHERE code = 'LR';
UPDATE llx_c_country SET phone_code = '+218' WHERE code = 'LY';
UPDATE llx_c_country SET phone_code = '+423' WHERE code = 'LI';
UPDATE llx_c_country SET phone_code = '+370' WHERE code = 'LT';
UPDATE llx_c_country SET phone_code = '+352' WHERE code = 'LU';
UPDATE llx_c_country SET phone_code = '+853' WHERE code = 'MO';
UPDATE llx_c_country SET phone_code = '+389' WHERE code = 'MK';
UPDATE llx_c_country SET phone_code = '+261' WHERE code = 'MG';
UPDATE llx_c_country SET phone_code = '+265' WHERE code = 'MW';
UPDATE llx_c_country SET phone_code = '+60' WHERE code = 'MY';
UPDATE llx_c_country SET phone_code = '+960' WHERE code = 'MV';
UPDATE llx_c_country SET phone_code = '+223' WHERE code = 'ML';
UPDATE llx_c_country SET phone_code = '+356' WHERE code = 'MT';
UPDATE llx_c_country SET phone_code = '+692' WHERE code = 'MH';
UPDATE llx_c_country SET phone_code = '+222' WHERE code = 'MR';
UPDATE llx_c_country SET phone_code = '+230' WHERE code = 'MU';
UPDATE llx_c_country SET phone_code = '+262' WHERE code = 'YT';
UPDATE llx_c_country SET phone_code = '+52' WHERE code = 'MX';
UPDATE llx_c_country SET phone_code = '+691' WHERE code = 'FM';
UPDATE llx_c_country SET phone_code = '+373' WHERE code = 'MD';
UPDATE llx_c_country SET phone_code = '+976' WHERE code = 'MN';
UPDATE llx_c_country SET phone_code = '+1-664' WHERE code = 'MS';
UPDATE llx_c_country SET phone_code = '+258' WHERE code = 'MZ';
UPDATE llx_c_country SET phone_code = '+95' WHERE code = 'MM';
UPDATE llx_c_country SET phone_code = '+264' WHERE code = 'NA';
UPDATE llx_c_country SET phone_code = '+674' WHERE code = 'NR';
UPDATE llx_c_country SET phone_code = '+977' WHERE code = 'NP';
UPDATE llx_c_country SET phone_code = '+687' WHERE code = 'NC';
UPDATE llx_c_country SET phone_code = '+64' WHERE code = 'NZ';
UPDATE llx_c_country SET phone_code = '+505' WHERE code = 'NI';
UPDATE llx_c_country SET phone_code = '+227' WHERE code = 'NE';
UPDATE llx_c_country SET phone_code = '+234' WHERE code = 'NG';
UPDATE llx_c_country SET phone_code = '+683' WHERE code = 'NU';
UPDATE llx_c_country SET phone_code = '+672' WHERE code = 'NF';
UPDATE llx_c_country SET phone_code = '+1-670' WHERE code = 'MP';
UPDATE llx_c_country SET phone_code = '+47' WHERE code = 'NO';
UPDATE llx_c_country SET phone_code = '+968' WHERE code = 'OM';
UPDATE llx_c_country SET phone_code = '+92' WHERE code = 'PK';
UPDATE llx_c_country SET phone_code = '+680' WHERE code = 'PW';
UPDATE llx_c_country SET phone_code = '+970' WHERE code = 'PS';
UPDATE llx_c_country SET phone_code = '+507' WHERE code = 'PA';
UPDATE llx_c_country SET phone_code = '+675' WHERE code = 'PG';
UPDATE llx_c_country SET phone_code = '+595' WHERE code = 'PY';
UPDATE llx_c_country SET phone_code = '+51' WHERE code = 'PE';
UPDATE llx_c_country SET phone_code = '+63' WHERE code = 'PH';
UPDATE llx_c_country SET phone_code = '+64' WHERE code = 'PN';
UPDATE llx_c_country SET phone_code = '+48' WHERE code = 'PL';
UPDATE llx_c_country SET phone_code = '+1-787' WHERE code = 'PR';
UPDATE llx_c_country SET phone_code = '+974' WHERE code = 'QA';
UPDATE llx_c_country SET phone_code = '+40' WHERE code = 'RO';
UPDATE llx_c_country SET phone_code = '+250' WHERE code = 'RW';
UPDATE llx_c_country SET phone_code = '+290' WHERE code = 'SH';
UPDATE llx_c_country SET phone_code = '+1-869' WHERE code = 'KN';
UPDATE llx_c_country SET phone_code = '+1-758' WHERE code = 'LC';
UPDATE llx_c_country SET phone_code = '+508' WHERE code = 'PM';
UPDATE llx_c_country SET phone_code = '+1-784' WHERE code = 'VC';
UPDATE llx_c_country SET phone_code = '+685' WHERE code = 'WS';
UPDATE llx_c_country SET phone_code = '+378' WHERE code = 'SM';
UPDATE llx_c_country SET phone_code = '+239' WHERE code = 'ST';
UPDATE llx_c_country SET phone_code = '+381' WHERE code = 'RS';
UPDATE llx_c_country SET phone_code = '+248' WHERE code = 'SC';
UPDATE llx_c_country SET phone_code = '+232' WHERE code = 'SL';
UPDATE llx_c_country SET phone_code = '+421' WHERE code = 'SK';
UPDATE llx_c_country SET phone_code = '+386' WHERE code = 'SI';
UPDATE llx_c_country SET phone_code = '+677' WHERE code = 'SB';
UPDATE llx_c_country SET phone_code = '+252' WHERE code = 'SO';
UPDATE llx_c_country SET phone_code = '+27' WHERE code = 'ZA';
UPDATE llx_c_country SET phone_code = '+500' WHERE code = 'GS';
UPDATE llx_c_country SET phone_code = '+94' WHERE code = 'LK';
UPDATE llx_c_country SET phone_code = '+249' WHERE code = 'SD';
UPDATE llx_c_country SET phone_code = '+597' WHERE code = 'SR';
UPDATE llx_c_country SET phone_code = '+47' WHERE code = 'SJ';
UPDATE llx_c_country SET phone_code = '+268' WHERE code = 'SZ';
UPDATE llx_c_country SET phone_code = '+963' WHERE code = 'SY';
UPDATE llx_c_country SET phone_code = '+886' WHERE code = 'TW';
UPDATE llx_c_country SET phone_code = '+992' WHERE code = 'TJ';
UPDATE llx_c_country SET phone_code = '+255' WHERE code = 'TZ';
UPDATE llx_c_country SET phone_code = '+66' WHERE code = 'TH';
UPDATE llx_c_country SET phone_code = '+670' WHERE code = 'TL';
UPDATE llx_c_country SET phone_code = '+690' WHERE code = 'TK';
UPDATE llx_c_country SET phone_code = '+676' WHERE code = 'TO';
UPDATE llx_c_country SET phone_code = '+1-868' WHERE code = 'TT';
UPDATE llx_c_country SET phone_code = '+90' WHERE code = 'TR';
UPDATE llx_c_country SET phone_code = '+993' WHERE code = 'TM';
UPDATE llx_c_country SET phone_code = '+1-649' WHERE code = 'TC';
UPDATE llx_c_country SET phone_code = '+688' WHERE code = 'TV';
UPDATE llx_c_country SET phone_code = '+256' WHERE code = 'UG';
UPDATE llx_c_country SET phone_code = '+380' WHERE code = 'UA';
UPDATE llx_c_country SET phone_code = '+971' WHERE code = 'AE';
UPDATE llx_c_country SET phone_code = '+1' WHERE code = 'UM';
UPDATE llx_c_country SET phone_code = '+598' WHERE code = 'UY';
UPDATE llx_c_country SET phone_code = '+998' WHERE code = 'UZ';
UPDATE llx_c_country SET phone_code = '+678' WHERE code = 'VU';
UPDATE llx_c_country SET phone_code = '+58' WHERE code = 'VE';
UPDATE llx_c_country SET phone_code = '+84' WHERE code = 'VN';
UPDATE llx_c_country SET phone_code = '+1-284' WHERE code = 'VG';
UPDATE llx_c_country SET phone_code = '+1-340' WHERE code = 'VI';
UPDATE llx_c_country SET phone_code = '+681' WHERE code = 'WF';
UPDATE llx_c_country SET phone_code = '+212' WHERE code = 'EH';
UPDATE llx_c_country SET phone_code = '+967' WHERE code = 'YE';
UPDATE llx_c_country SET phone_code = '+260' WHERE code = 'ZM';
UPDATE llx_c_country SET phone_code = '+263' WHERE code = 'ZW';
UPDATE llx_c_country SET phone_code = '+44' WHERE code = 'GG';
UPDATE llx_c_country SET phone_code = '+44' WHERE code = 'IM';
UPDATE llx_c_country SET phone_code = '+44' WHERE code = 'JE';
UPDATE llx_c_country SET phone_code = '+382' WHERE code = 'ME';
UPDATE llx_c_country SET phone_code = '+590' WHERE code = 'BL';
UPDATE llx_c_country SET phone_code = '+590' WHERE code = 'MF';
UPDATE llx_c_country SET phone_code = '+383' WHERE code = 'XK';
UPDATE llx_c_country SET phone_code = '+599' WHERE code = 'CW';
UPDATE llx_c_country SET phone_code = '+1-721' WHERE code = 'SX';
