--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 4.0.0 or higher.
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
-- To make pk to be auto increment (mysql):    VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres): VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE
-- To set a field as NULL:                     VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as default NULL:             VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- Note: fields with type BLOB/TEXT can't have default value.
-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


ALTER TABLE llx_user DROP COLUMN phenix_login;
ALTER TABLE llx_user DROP COLUMN phenix_pass;

ALTER TABLE llx_societe ADD COLUMN fk_account integer;

ALTER TABLE llx_website ADD COLUMN virtualhost varchar(255) after fk_default_home;

ALTER TABLE llx_chargesociales ADD COLUMN fk_account integer after fk_type;
ALTER TABLE llx_chargesociales ADD COLUMN fk_mode_reglement integer after fk_account;

ALTER TABLE llx_ecm_files ADD COLUMN gen_or_uploaded varchar(12) after cover; 

DROP TABLE llx_document_generator;
DROP TABLE llx_ecm_documents;

ALTER TABLE llx_notify ADD COLUMN type_target varchar(16) NULL;

-- Product attributes
CREATE TABLE llx_product_attribute
(
  rowid INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  ref VARCHAR(255) NOT NULL,
  label VARCHAR(255) NOT NULL,
  rang INT DEFAULT 0 NOT NULL,
  entity INT DEFAULT 1 NOT NULL
);
ALTER TABLE llx_product_attribute ADD CONSTRAINT unique_ref UNIQUE (ref);
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
CREATE TABLE llx_product_attribute_combination2val
(
  rowid INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  fk_prod_combination INT NOT NULL,
  fk_prod_attr INT NOT NULL,
  fk_prod_attr_val INT NOT NULL
);
CREATE TABLE llx_product_attribute_value
(
  rowid INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  fk_product_attribute INT NOT NULL,
  ref VARCHAR(255) DEFAULT NULL,
  value VARCHAR(255) DEFAULT NULL,
  entity INT DEFAULT 1 NOT NULL
);
ALTER TABLE llx_product_attribute_value ADD CONSTRAINT unique_ref UNIQUE (fk_product_attribute,ref);