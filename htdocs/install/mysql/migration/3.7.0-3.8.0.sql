--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.8.0 or higher.
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):    VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres): VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE
-- To set a field as NULL:                     VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as default NULL:             VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


ALTER TABLE llx_bank ADD INDEX idx_bank_num_releve(num_releve);


--create table for price expressions and add column in product supplier
create table llx_c_price_expression
(
  rowid      integer AUTO_INCREMENT PRIMARY KEY,
  title      varchar(20) NOT NULL,
  expression varchar(80) NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_supplier_price_expression integer DEFAULT NULL;
ALTER TABLE llx_product ADD COLUMN fk_price_expression integer DEFAULT NULL;
ALTER TABLE llx_product_price ADD COLUMN fk_price_expression integer DEFAULT NULL;


--create table for user conf of printing driver
CREATE TABLE llx_printing 
(
 rowid integer AUTO_INCREMENT PRIMARY KEY,
 tms timestamp,
 datec datetime,
 printer_name text NOT NULL, 
 printer_location text NOT NULL,
 printer_id varchar(255) NOT NULL,
 copy integer NOT NULL DEFAULT '1',
 module varchar(16) NOT NULL,
 driver varchar(16) NOT NULL,
 userid integer
)ENGINE=innodb;

ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_price_expression integer DEFAULT NULL;

-- Taiwan VAT Rates
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 2131, 213, '5', '0', 'VAT 5%', 1);

-- Add situation invoices
ALTER TABLE llx_facture ADD COLUMN situation_cycle_ref smallint;
ALTER TABLE llx_facture ADD COLUMN situation_counter smallint;
ALTER TABLE llx_facture ADD COLUMN situation_final smallint;
ALTER TABLE llx_facturedet ADD COLUMN situation_percent real;
ALTER TABLE llx_facturedet ADD COLUMN fk_prev_id integer;

-- Convert SMTP config to main entity, so new entities don't get the old values
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SENDMODE";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SMTP_PORT";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SMTP_SERVER";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SMTPS_ID";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SMTPS_PW";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_EMAIL_TLS";


create table llx_bank_account_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;


ALTER TABLE llx_stock_mouvement MODIFY COLUMN label varchar(255);
ALTER TABLE llx_stock_mouvement ADD COLUMN inventorycode varchar(128);

ALTER TABLE llx_product_association ADD COLUMN incdec integer DEFAULT 1;



ALTER TABLE llx_bank_account_extrafields ADD INDEX idx_bank_account_extrafields (fk_object);


create table llx_contratdet_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_contratdet_extrafields ADD INDEX idx_contratdet_extrafields (fk_object);

ALTER TABLE llx_product_fournisseur_price ADD COLUMN delivery_time_days integer;

-- Module AskPriceSupplier --
CREATE TABLE llx_askpricesupplier (
  rowid integer NOT NULL AUTO_INCREMENT,
  ref varchar(30) NOT NULL,
  entity integer NOT NULL DEFAULT '1',
  ref_ext varchar(255) DEFAULT NULL,
  ref_int varchar(255) DEFAULT NULL,
  fk_soc integer DEFAULT NULL,
  fk_projet integer DEFAULT NULL,
  tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datec datetime DEFAULT NULL,
  date_valid datetime DEFAULT NULL,
  date_cloture datetime DEFAULT NULL,
  fk_user_author integer DEFAULT NULL,
  fk_user_modif integer DEFAULT NULL,
  fk_user_valid integer DEFAULT NULL,
  fk_user_cloture integer DEFAULT NULL,
  fk_statut smallint NOT NULL DEFAULT '0',
  price double DEFAULT '0',
  remise_percent double DEFAULT '0',
  remise_absolue double DEFAULT '0',
  remise double DEFAULT '0',
  total_ht double(24,8) DEFAULT '0.00000000',
  tva double(24,8) DEFAULT '0.00000000',
  localtax1 double(24,8) DEFAULT '0.00000000',
  localtax2 double(24,8) DEFAULT '0.00000000',
  total double(24,8) DEFAULT '0.00000000',
  fk_account integer DEFAULT NULL,
  fk_currency varchar(3) DEFAULT NULL,
  fk_cond_reglement integer DEFAULT NULL,
  fk_mode_reglement integer DEFAULT NULL,
  note_private text,
  note_public text,
  model_pdf varchar(255) DEFAULT NULL,
  date_livraison date DEFAULT NULL,
  fk_shipping_method integer DEFAULT NULL,
  import_key varchar(14) DEFAULT NULL,
  extraparams varchar(255) DEFAULT NULL,
  PRIMARY KEY (rowid),
  UNIQUE KEY uk_askpricesupplier_ref (ref,entity),
  KEY idx_askpricesupplier_fk_soc (fk_soc),
  KEY idx_askpricesupplier_fk_user_author (fk_user_author),
  KEY idx_askpricesupplier_fk_user_valid (fk_user_valid),
  KEY idx_askpricesupplier_fk_user_cloture (fk_user_cloture),
  KEY idx_askpricesupplier_fk_projet (fk_projet),
  KEY idx_askpricesupplier_fk_account (fk_account),
  KEY idx_askpricesupplier_fk_currency (fk_currency),
  CONSTRAINT fk_askpricesupplier_fk_projet FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid),
  CONSTRAINT fk_askpricesupplier_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid),
  CONSTRAINT fk_askpricesupplier_fk_user_author FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid),
  CONSTRAINT fk_askpricesupplier_fk_user_cloture FOREIGN KEY (fk_user_cloture) REFERENCES llx_user (rowid),
  CONSTRAINT fk_askpricesupplier_fk_user_valid FOREIGN KEY (fk_user_valid) REFERENCES llx_user (rowid)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE llx_askpricesupplierdet (
  rowid integer NOT NULL AUTO_INCREMENT,
  fk_askpricesupplier integer NOT NULL,
  fk_parent_line integer DEFAULT NULL,
  fk_product integer DEFAULT NULL,
  label varchar(255) DEFAULT NULL,
  description text,
  fk_remise_except integer DEFAULT NULL,
  tva_tx double(6,3) DEFAULT '0.000',
  localtax1_tx double(6,3) DEFAULT '0.000',
  localtax1_type varchar(10) DEFAULT NULL,
  localtax2_tx double(6,3) DEFAULT '0.000',
  localtax2_type varchar(10) DEFAULT NULL,
  qty double DEFAULT NULL,
  remise_percent double DEFAULT '0',
  remise double DEFAULT '0',
  price double DEFAULT NULL,
  subprice double(24,8) DEFAULT '0.00000000',
  total_ht double(24,8) DEFAULT '0.00000000',
  total_tva double(24,8) DEFAULT '0.00000000',
  total_localtax1 double(24,8) DEFAULT '0.00000000',
  total_localtax2 double(24,8) DEFAULT '0.00000000',
  total_ttc double(24,8) DEFAULT '0.00000000',
  product_type integer DEFAULT '0',
  info_bits integer DEFAULT '0',
  buy_price_ht double(24,8) DEFAULT '0.00000000',
  fk_product_fournisseur_price integer DEFAULT NULL,
  special_code integer DEFAULT '0',
  rang integer DEFAULT '0',
  ref_fourn varchar(30) DEFAULT NULL,
  PRIMARY KEY (rowid),
  KEY idx_askpricesupplierdet_fk_askpricesupplierdet (fk_askpricesupplier),
  KEY idx_askpricesupplierdet_fk_product (fk_product),
  CONSTRAINT fk_askpricesupplierdet_fk_askpricesupplier FOREIGN KEY (fk_askpricesupplier) REFERENCES llx_askpricesupplier (rowid)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE llx_askpricesupplier_extrafields (
  rowid integer NOT NULL AUTO_INCREMENT,
  tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object integer NOT NULL,
  import_key varchar(14) DEFAULT NULL,
  PRIMARY KEY (rowid),
  KEY idx_askpricesupplier_extrafields (fk_object)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE llx_askpricesupplierdet_extrafields (
  rowid integer NOT NULL AUTO_INCREMENT,
  tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object integer NOT NULL,
  import_key varchar(14) DEFAULT NULL,
  PRIMARY KEY (rowid),
  KEY idx_askpricesupplierdet_extrafields (fk_object)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
-- End Module AskPriceSupplier --