--
-- This file is executed by calling /install/index.php page
-- when current version is 19.0.0 or higher.
-- Be carefull in the position of each SQL request.
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


-- v18




-- v19

-- VAT multientity
-- VMYSQL4.1 DROP INDEX uk_c_tva_id on llx_c_tva;
-- VPGSQL8.2 DROP INDEX uk_c_tva_id;
ALTER TABLE llx_c_tva ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_c_tva ADD UNIQUE INDEX uk_c_tva_id (entity, fk_pays, code, taux, recuperableonly);
-- Tip to copy vat rate into entity 2.
-- INSERT INTO llx_c_tva (entity, fk_pays, code, taux, localtax1, localtax1_type, localtax2, localtax2_type, use_default, recuperableonly, note, active, accountancy_code_sell, accountancy_code_buy) SELECT 2, fk_pays, code, taux, localtax1, localtax1_type, localtax2, localtax2_type, use_default, recuperableonly, note, active, accountancy_code_sell, accountancy_code_buy FROM llx_c_tva WHERE entity = 1;

ALTER TABLE llx_ticket ADD COLUMN fk_contract integer DEFAULT 0 after fk_project;

UPDATE llx_product_lot SET manufacturing_date = datec WHERE manufacturing_date IS NULL;

UPDATE llx_societe_rib SET frstrecur = 'RCUR' WHERE frstrecur = 'RECUR';


INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_RIB_CREATE','Third party payment information created','Executed when a third party payment information is created','societe',1);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_RIB_MODIFY','Third party payment information updated','Executed when a third party payment information is updated','societe',1);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_RIB_DELETE','Third party payment information deleted','Executed when a third party payment information is deleted','societe',1);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_CLOSE','Intervention is done','Executed when a intervention is done','ficheinter',36);

UPDATE llx_bank_url SET type = 'direct-debit' WHERE type = 'withdraw' AND url like '%compta/prelevement/card%';

ALTER TABLE llx_facture_fourn ADD COLUMN revenuestamp double(24,8) DEFAULT 0;

ALTER TABLE llx_societe_rib ADD COLUMN extraparams varchar(255);

ALTER TABLE llx_c_type_container ADD COLUMN position integer DEFAULT 0;

ALTER TABLE llx_user DROP COLUMN skype;
ALTER TABLE llx_user DROP COLUMN twitter;
ALTER TABLE llx_user DROP COLUMN facebook;
ALTER TABLE llx_user DROP COLUMN instagram;
ALTER TABLE llx_user DROP COLUMN snapchat;
ALTER TABLE llx_user DROP COLUMN googleplus;
ALTER TABLE llx_user DROP COLUMN youtube;
ALTER TABLE llx_user DROP COLUMN whatsapp;

ALTER TABLE llx_adherent DROP COLUMN skype;
ALTER TABLE llx_adherent DROP COLUMN twitter;
ALTER TABLE llx_adherent DROP COLUMN facebook;
ALTER TABLE llx_adherent DROP COLUMN instagram;
ALTER TABLE llx_adherent DROP COLUMN snapchat;
ALTER TABLE llx_adherent DROP COLUMN googleplus;
ALTER TABLE llx_adherent DROP COLUMN youtube;
ALTER TABLE llx_adherent DROP COLUMN whatsapp;

ALTER TABLE llx_societe DROP COLUMN skype;

ALTER TABLE llx_prelevement_demande ADD INDEX idx_prelevement_demande_ext_payment_id (ext_payment_id);

ALTER TABLE llx_actioncomm ADD COLUMN fk_bookcal_availability integer DEFAULT NULL;

ALTER TABLE llx_product_lot ADD COLUMN qc_frequency integer DEFAULT NULL;
ALTER TABLE llx_product_lot ADD COLUMN lifetime integer DEFAULT NULL;

ALTER TABLE llx_societe_rib ADD COLUMN model_pdf varchar(255) AFTER currency_code;
ALTER TABLE llx_societe_rib ADD COLUMN last_main_doc varchar(255) AFTER model_pdf;
ALTER TABLE llx_societe_rib ADD COLUMN date_signature datetime AFTER stripe_account;
ALTER TABLE llx_societe_rib ADD COLUMN online_sign_ip varchar(48) AFTER date_signature;
ALTER TABLE llx_societe_rib ADD COLUMN online_sign_name		varchar(64) AFTER online_sign_ip;

INSERT INTO llx_const (name, entity, value, type, visible) VALUES ('PROPOSAL_ALLOW_ONLINESIGN', 1, '1', 'string', 0);

ALTER TABLE llx_bookcal_availabilities ADD COLUMN fk_bookcal_calendar integer NOT NULL;
ALTER TABLE llx_bookcal_calendar ADD COLUMN visibility integer NOT NULL DEFAULT 1;

ALTER TABLE llx_expeditiondet_batch ADD COLUMN fk_warehouse integer DEFAULT NULL;

ALTER TABLE llx_commande_fournisseur_dispatch ADD INDEX idx_commande_fournisseur_dispatch_fk_commandefourndet (fk_commandefourndet);

-- Update website type
UPDATE llx_societe_account SET site = 'dolibarr_website' WHERE fk_website > 0 AND site IS NULL;
ALTER TABLE llx_societe_account MODIFY COLUMN site varchar(128) NOT NULL;

ALTER TABLE llx_accounting_account MODIFY COLUMN pcg_type varchar(32);

-- Drop the composite unique index that exists on llx_links to rebuild a new one with objecttype included.
-- The old design did not allow same label on different objects with same id.
-- VMYSQL4.1 DROP INDEX uk_links on llx_links;
-- VPGSQL8.2 DROP INDEX uk_links;
ALTER TABLE llx_links ADD UNIQUE INDEX uk_links (objectid, objecttype,label);

ALTER TABLE llx_c_invoice_subtype MODIFY COLUMN entity integer DEFAULT 1 NOT NULL;

