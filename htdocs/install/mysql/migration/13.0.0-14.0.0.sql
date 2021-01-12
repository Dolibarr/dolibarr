--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 14.0.0 or higher.
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


-- Missing in v13 or lower

-- For v14

ALTER TABLE llx_c_availability ADD COLUMN position integer NOT NULL DEFAULT 0;

ALTER TABLE llx_adherent ADD COLUMN ref varchar(30) AFTER rowid;
UPDATE llx_adherent SET ref = rowid WHERE ref = '' or ref IS NULL;
ALTER TABLE llx_adherent MODIFY COLUMN ref varchar(30) NOT NULL;
ALTER TABLE llx_adherent ADD UNIQUE INDEX uk_adherent_ref (ref, entity);

ALTER TABLE llx_societe ADD COLUMN accountancy_code_sell varchar(32) AFTER webservices_key;
ALTER TABLE llx_societe ADD COLUMN accountancy_code_buy varchar(32) AFTER accountancy_code_sell;

ALTER TABLE llx_bank_account ADD COLUMN ics varchar(32) NULL;
ALTER TABLE llx_bank_account ADD COLUMN ics_transfer varchar(32) NULL;

ALTER TABLE llx_facture MODIFY COLUMN date_valid DATETIME NULL DEFAULT NULL;

ALTER TABLE llx_website ADD COLUMN lastaccess datetime NULL;
ALTER TABLE llx_website ADD COLUMN pageviews_month BIGINT UNSIGNED DEFAULT 0;
ALTER TABLE llx_website ADD COLUMN pageviews_total BIGINT UNSIGNED DEFAULT 0;

ALTER TABLE llx_propal ADD COLUMN fk_warehouse integer DEFAULT NULL AFTER fk_shipping_method;
ALTER TABLE llx_propal ADD CONSTRAINT llx_propal_fk_warehouse FOREIGN KEY (fk_warehouse) REFERENCES llx_entrepot(rowid);
ALTER TABLE llx_propal ADD INDEX idx_propal_fk_warehouse(fk_warehouse);

