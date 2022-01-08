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
-- To drop an index:        -- VMYSQL4.1 DROP INDEX nomindex on llx_table;
-- To drop an index:        -- VPGSQL8.2 DROP INDEX nomindex;
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
-- To rebuild sequence for postgresql after insert by forcing id autoincrement fields:
-- -- VPGSQL8.2 SELECT dol_util_rebuild_sequences();

-- Missing in v15 or lower

ALTER TABLE llx_c_availability MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_civility MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_country MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_currencies MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_effectif MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_exp_tax_cat MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_hrm_department MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_hrm_function MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_input_reason MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_lead_status MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_paper_format MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_partnership_type MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_product_nature MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_productbatch_qcstatus MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_propalst MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_prospectcontactlevel MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_prospectlevel MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_recruitment_origin MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_shipment_package_type MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_type_container MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_type_fees MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_type_resource MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_units MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_actioncomm MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_barcode_type MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_chargesociales MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_input_method MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_paiement MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_shipment_mode MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_stcomm MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_stcommcontact MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_type_contact MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_typent MODIFY COLUMN libelle varchar(128);

