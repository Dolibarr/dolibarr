--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 11.0.0 or higher.
--
-- To restrict request to Mysql version x.y minimum use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y minimum use -- VPGSQLx.y
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To create a unique index ALTER TABLE llx_table ADD UNIQUE INDEX uk_table_field (field);
-- To drop an index:        -- VMYSQL4.1 DROP INDEX nomindex on llx_table
-- To drop an index:        -- VPGSQL8.2 DROP INDEX nomindex
-- To make pk to be auto increment (mysql):    -- VMYSQL4.3 ALTER TABLE llx_table CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
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


-- Missing in v10
ALTER TABLE llx_account_bookkeeping ADD COLUMN date_export datetime DEFAULT NULL;
ALTER TABLE llx_expensereport ADD COLUMN paid smallint default 0 NOT NULL;
UPDATE llx_expensereport set paid = 1 WHERE fk_statut = 6 and paid = 0;

create table llx_entrepot_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_entrepot_extrafields ADD INDEX idx_entrepot_extrafields (fk_object);


ALTER TABLE llx_facture ADD COLUMN retained_warranty real DEFAULT NULL after situation_final;
ALTER TABLE llx_facture ADD COLUMN retained_warranty_date_limit	date DEFAULT NULL after retained_warranty;
ALTER TABLE llx_facture ADD COLUMN retained_warranty_fk_cond_reglement	integer  DEFAULT NULL after retained_warranty_date_limit;


ALTER TABLE llx_c_shipment_mode ADD COLUMN entity integer DEFAULT 1 NOT NULL;

ALTER TABLE llx_c_shipment_mode DROP INDEX uk_c_shipment_mode;
ALTER TABLE llx_c_shipment_mode ADD UNIQUE INDEX uk_c_shipment_mode (code, entity);

ALTER TABLE llx_facture_fourn DROP COLUMN total;

ALTER TABLE llx_user ADD COLUMN iplastlogin         varchar(250);
ALTER TABLE llx_user ADD COLUMN ippreviouslogin     varchar(250);

ALTER TABLE llx_events ADD COLUMN prefix_session varchar(255) NULL;

create table llx_payment_salary_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- salary payment id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_payment_salary_extrafields ADD INDEX idx_payment_salary_extrafields (fk_object);

ALTER TABLE llx_c_price_expression MODIFY COLUMN expression varchar(255) NOT NULL;

UPDATE llx_bank_url set url = REPLACE( url, 'compta/salaries/', 'salaries/');

ALTER TABLE llx_stock_mouvement ADD COLUMN fk_projet INTEGER NOT NULL DEFAULT 0 AFTER model_pdf;
