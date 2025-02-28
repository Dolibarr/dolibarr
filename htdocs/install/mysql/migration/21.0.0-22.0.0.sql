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
ALTER TABLE llx_product_customer_price DROP CONSTRAINT fk_product_customer_price_fk_product;
ALTER TABLE llx_product_customer_price DROP CONSTRAINT fk_product_customer_price_fk_soc;
ALTER TABLE llx_product_customer_price DROP INDEX uk_customer_price_fk_product_fk_soc;
ALTER TABLE llx_product_customer_price ADD UNIQUE INDEX uk_customer_price_fk_product_fk_soc (fk_product, fk_soc, date_begin);
ALTER TABLE llx_product_customer_price ADD CONSTRAINT fk_product_customer_price_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product(rowid);
ALTER TABLE llx_product_customer_price ADD CONSTRAINT fk_product_customer_price_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);
UPDATE llx_product_customer_price SET date_begin = datec WHERE date_begin IS NULL;
UPDATE llx_product_customer_price_log SET date_begin = datec WHERE date_begin IS NULL;
