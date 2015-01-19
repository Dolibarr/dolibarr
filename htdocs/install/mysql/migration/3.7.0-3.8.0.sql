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

--create table for price expressions and add column in product supplier
create table llx_c_price_expression
(
  rowid     integer AUTO_INCREMENT PRIMARY KEY,
  title     varchar(20) NOT NULL,
  expression    varchar(80) NOT NULL
)ENGINE=innodb;

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

ALTER TABLE llx_product_fournisseur_price ADD fk_price_expression integer DEFAULT NULL;

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

