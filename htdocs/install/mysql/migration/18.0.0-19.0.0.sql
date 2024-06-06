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


-- v18

-- VPGSQL8.2 ALTER SEQUENCE llx_projet_task_time_rowid_seq RENAME TO llx_element_time_rowid_seq;

ALTER TABLE llx_product_perentity ADD COLUMN pmp double(24,8);

ALTER TABLE llx_projet_task ADD COLUMN fk_user_modif integer after fk_user_creat;

UPDATE llx_paiement SET ref = rowid WHERE ref IS NULL OR ref = '';


-- v19

-- VAT multientity
-- VMYSQL4.1 DROP INDEX uk_c_tva_id ON llx_c_tva;
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

ALTER TABLE llx_user ADD COLUMN email_oauth2 varchar(255);
ALTER TABLE llx_user ADD COLUMN last_main_doc varchar(255);

ALTER TABLE llx_prelevement_demande ADD INDEX idx_prelevement_demande_ext_payment_id (ext_payment_id);

ALTER TABLE llx_actioncomm CHANGE COLUMN fk_bookcal_availability fk_bookcal_calendar integer;
ALTER TABLE llx_actioncomm ADD COLUMN fk_bookcal_calendar integer DEFAULT NULL;

ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_entity (entity);

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

ALTER TABLE llx_accounting_account MODIFY COLUMN pcg_type varchar(60);

-- Drop the composite unique index that exists on llx_links to rebuild a new one with objecttype included.
-- The old design did not allow same label on different objects with same id.
-- VMYSQL4.1 DROP INDEX uk_links on llx_links;
-- VPGSQL8.2 DROP INDEX uk_links;
ALTER TABLE llx_links ADD UNIQUE INDEX uk_links (objectid, objecttype,label);

ALTER TABLE llx_facture_fourn ADD COLUMN subtype smallint DEFAULT NULL;

ALTER TABLE llx_c_invoice_subtype DROP INDEX uk_c_invoice_subtype;
ALTER TABLE llx_c_invoice_subtype ADD UNIQUE INDEX uk_c_invoice_subtype (entity, code, fk_country);
ALTER TABLE llx_c_invoice_subtype MODIFY COLUMN entity integer DEFAULT 1 NOT NULL;
ALTER TABLE llx_c_invoice_subtype MODIFY COLUMN code varchar(5) NOT NULL;

insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '5.1', 'Πιστωτικό Τιμολόγιο / Συσχετιζόμενο', 0);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '5.2', 'Πιστωτικό Τιμολόγιο / Μη Συσχετιζόμενο', 1);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '11.4', 'Πιστωτικό Στοιχ. Λιανικής', 1);

-- Product/service managed in stock
ALTER TABLE llx_product ADD COLUMN stockable_product integer DEFAULT 1 NOT NULL;
UPDATE llx_product set stockable_product = 0 WHERE fk_product_type = 1;

ALTER TABLE llx_prelevement_lignes ADD COLUMN fk_user integer NULL;

ALTER TABLE llx_hrm_evaluation ADD COLUMN last_main_doc varchar(255);
ALTER TABLE llx_hrm_evaluationdet ADD COLUMN comment TEXT;

ALTER TABLE llx_resource ADD COLUMN address varchar(255) DEFAULT NULL AFTER fk_code_type_resource;
ALTER TABLE llx_resource ADD COLUMN zip varchar(25) DEFAULT NULL AFTER address;
ALTER TABLE llx_resource ADD COLUMN town varchar(50) DEFAULT NULL AFTER zip;
ALTER TABLE llx_resource ADD COLUMN photo_filename varchar(255) DEFAULT NULL AFTER town;
ALTER TABLE llx_resource ADD COLUMN max_users integer DEFAULT NULL AFTER photo_filename;
ALTER TABLE llx_resource ADD COLUMN phone varchar(255) DEFAULT NULL AFTER max_users;
ALTER TABLE llx_resource ADD COLUMN email varchar(255) DEFAULT NULL AFTER phone;
ALTER TABLE llx_resource ADD COLUMN url varchar(255) DEFAULT NULL AFTER email;
ALTER TABLE llx_resource ADD COLUMN fk_state integer DEFAULT NULL AFTER fk_country;
ALTER TABLE llx_resource ADD INDEX idx_resource_fk_state (fk_state);
--ALTER TABLE llx_resource ADD CONSTRAINT fk_resource_fk_state FOREIGN KEY (fk_state) REFERENCES llx_c_departements (rowid);


ALTER TABLE llx_mailing ADD COLUMN note_private text;
ALTER TABLE llx_mailing ADD COLUMN note_public text;

ALTER TABLE llx_user_rib ADD COLUMN bic_intermediate varchar(11) AFTER bic;
ALTER TABLE llx_bank_account ADD COLUMN bic_intermediate varchar(11) AFTER bic;
ALTER TABLE llx_societe_rib ADD COLUMN bic_intermediate varchar(11) AFTER bic;

UPDATE llx_menu SET url = '/fourn/paiement/list.php?mainmenu=billing&leftmenu=suppliers_bills_payment' WHERE leftmenu = 'suppliers_bills_payment';

ALTER TABLE llx_facture_rec ADD INDEX idx_facture_rec_datec(datec);

ALTER TABLE llx_facturedet ADD COLUMN batch varchar(128) NULL;		-- To store the batch to consume in stock when using a POS module
ALTER TABLE llx_facturedet ADD COLUMN fk_warehouse integer NULL;	-- To store the warehouse where to consume stock when using a POS module

ALTER TABLE llx_multicurrency_rate ADD COLUMN rate_indirect double DEFAULT 0;

ALTER TABLE llx_mrp_production ADD COLUMN fk_unit integer DEFAULT NULL;
-- VMYSQL4.1 UPDATE llx_mrp_production as mp INNER JOIN llx_bom_bomline as bbl ON mp.origin_id = bbl.rowid SET mp.fk_unit = bbl.fk_unit WHERE mp.origin_type = 'bomline' AND mk.fk_unit IS NULL;
-- VMYSQL4.1 UPDATE llx_bom_bomline as bbl INNER JOIN llx_product as p ON p.rowid = bbl.fk_product SET bbl.fk_unit = p.fk_unit WHERE bbl.fk_unit IS NULL;

ALTER TABLE llx_facture_rec ADD COLUMN subtype smallint DEFAULT NULL AFTER entity;
ALTER TABLE llx_facture_fourn_rec ADD COLUMN subtype smallint DEFAULT NULL AFTER entity;

CREATE TABLE llx_mrp_production_extrafields
(
    rowid                     integer AUTO_INCREMENT PRIMARY KEY,
    tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_object                 integer NOT NULL,
    import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_mrp_production_extrafields ADD INDEX idx_mrp_production_fk_object(fk_object);

ALTER TABLE llx_salary ADD COLUMN ref_ext varchar(255);
ALTER TABLE llx_salary ADD COLUMN note_public text;

ALTER TABLE llx_commande_fournisseur_dispatch ADD COLUMN element_type varchar(50) DEFAULT 'supplier_order' NOT NULL;

-- VMYSQL4.1 DROP INDEX idx_expensereport_fk_refuse ON llx_expensereport;
-- VPGSQL8.2 DROP INDEX idx_expensereport_fk_refuse;

ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_fk_user_refuse(fk_user_refuse);

INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (1,'66','Société publique locale');

ALTER TABLE llx_prelevement_lignes ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_bom_bomline ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE llx_c_type_contact SET element = 'stocktransfer' WHERE element = 'StockTransfer';

UPDATE llx_c_units SET scale = 1 WHERE code = 'S';

UPDATE llx_c_tva SET taux = 3, note = 'Νήσων υπερμειωμένος Φ.Π.Α.' WHERE fk_pays = 102 AND taux = 16;
