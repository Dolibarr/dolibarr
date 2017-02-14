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


insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_CREATE','Product or service created','Executed when a product or sevice is created','product',30);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_MODIFY','Product or service modified','Executed when a product or sevice is modified','product',30);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_DELETE','Product or service deleted','Executed when a product or sevice is deleted','product',30);

ALTER TABLE llx_c_email_templates ADD COLUMN content_lines text;

ALTER TABLE llx_loan ADD COLUMN fk_projet integer DEFAULT NULL;

ALTER TABLE llx_holiday ADD COLUMN fk_user_modif integer;

ALTER TABLE llx_projet_task_time ADD COLUMN datec date;
ALTER TABLE llx_projet_task_time ADD COLUMN tms timestamp;

ALTER TABLE llx_product_price_by_qty ADD COLUMN fk_user_creat integer;
ALTER TABLE llx_product_price_by_qty ADD COLUMN fk_user_modif integer;
ALTER TABLE llx_product_price_by_qty DROP COLUMN date_price;
ALTER TABLE llx_product_price_by_qty ADD COLUMN tms timestamp;
ALTER TABLE llx_product_price_by_qty ADD COLUMN import_key integer;



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

ALTER TABLE llx_paiementfourn ADD COLUMN model_pdf varchar(255);

