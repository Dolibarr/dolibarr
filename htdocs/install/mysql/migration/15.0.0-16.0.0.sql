--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 15.0.0 or higher.
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

UPDATE llx_rights_def SET perms = 'writeall' WHERE perms = 'writeall_advance' AND module = 'holiday';



-- v16

ALTER TABLE llx_projet_task_time ADD COLUMN fk_product integer NULL;

INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_MODIFY','Customer proposal modified','Executed when a customer proposal is modified','propal',2);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_MODIFY','Customer order modified','Executed when a customer order is set modified','commande',5);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_MODIFY','Customer invoice modified','Executed when a customer invoice is modified','facture',7);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_MODIFY','Price request modified','Executed when a commercial proposal is modified','proposal_supplier',10);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_MODIFY','Supplier order request modified','Executed when a supplier order is modified','order_supplier',13);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_MODIFY','Supplier invoice modified','Executed when a supplier invoice is modified','invoice_supplier',15);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_MODIFY','Contract modified','Executed when a contract is modified','contrat',18);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_MODIFY','Shipping modified','Executed when a shipping is modified','shipping',20);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_MODIFY','Intervention modify','Executed when a intervention is modify','ficheinter',30);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_MODIFY','Product or service modified','Executed when a product or sevice is modified','product',41);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_MODIFY','Expense report modified','Executed when an expense report is modified','expensereport',202);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_MODIFY','Expense report modified','Executed when an expense report is modified','expensereport',212);


CREATE TABLE llx_stock_mouvement_extrafields (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_object integer NOT NULL,
    import_key varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_product_attribute_value MODIFY COLUMN ref VARCHAR(180) NOT NULL;
ALTER TABLE llx_product_attribute_value MODIFY COLUMN value VARCHAR(255) NOT NULL;
ALTER TABLE llx_product_attribute_value ADD COLUMN position INTEGER NOT NULL DEFAULT 0;
ALTER TABLE llx_product_attribute CHANGE rang position INTEGER DEFAULT 0 NOT NULL;
