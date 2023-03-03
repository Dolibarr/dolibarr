--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 18.0.0 or higher.
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
-- To rebuild sequence for postgresql after insert by forcing id autoincrement fields:
-- -- VPGSQL8.2 SELECT dol_util_rebuild_sequences();


-- v17

-- VMYSQL4.3 ALTER TABLE llx_hrm_skillrank CHANGE COLUMN `rank` rankorder integer;
-- VPGSQL8.2 ALTER TABLE llx_hrm_skillrank CHANGE COLUMN rank rankorder integer;

ALTER TABLE llx_accounting_system CHANGE COLUMN fk_pays fk_country integer; 

ALTER TABLE llx_commande_fournisseurdet MODIFY COLUMN ref varchar(128);
ALTER TABLE llx_facture_fourn_det MODIFY COLUMN ref varchar(128);


-- v18

ALTER TABLE llx_notify_def ADD COLUMN email varchar(255);
ALTER TABLE llx_notify_def ADD COLUMN threshold double(24,8);
ALTER TABLE llx_notify_def ADD COLUMN context varchar(128);

ALTER TABLE llx_c_action_trigger ADD COLUMN contexts varchar(255) NULL;

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_CLOSE','Project closed','Executed when a project is closed','project',145);

-- amount was removed in v12
ALTER TABLE llx_facture DROP COLUMN amount;

-- Rename prospect level on contact
ALTER TABLE llx_socpeople CHANGE fk_prospectcontactlevel fk_prospectlevel varchar(12);

ALTER TABLE llx_facture ADD COLUMN prorata_discount	real DEFAULT NULL;

ALTER TABLE llx_payment_salary MODIFY COLUMN datep datetime;

INSERT INTO llx_c_tva(rowid,fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active) values (1179, 117, 'I-28'  , 28,   0, '0',   0, '0', 0, 'IGST',      1);
INSERT INTO llx_c_tva(rowid,fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active) values (1176, 117, 'C+S-18',  0,   9, '1',   9, '1', 0, 'CGST+SGST - Same state sales', 1);


ALTER TABLE llx_user ADD COLUMN flagdelsessionsbefore datetime DEFAULT NULL;

ALTER TABLE llx_website ADD COLUMN pageviews_previous_month BIGINT UNSIGNED DEFAULT 0;

ALTER TABLE llx_product_stock ADD CONSTRAINT fk_product_product_rowid FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
ALTER TABLE llx_product_stock ADD CONSTRAINT fk_entrepot_entrepot_rowid FOREIGN KEY (fk_entrepot) REFERENCES llx_entrepot (rowid);


ALTER TABLE llx_bank_account ADD COLUMN owner_zip varchar(25);
ALTER TABLE llx_bank_account ADD COLUMN owner_town varchar(50);
ALTER TABLE llx_bank_account ADD COLUMN owner_country_id integer DEFAULT NULL;

ALTER TABLE llx_prelevement_bons ADD COLUMN fk_bank_account integer DEFAULT NULL;

ALTER TABLE llx_supplier_proposal ADD UNIQUE INDEX uk_supplier_proposal_ref (ref, entity);

ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_soc (fk_soc);
ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_user_author (fk_user_author);
ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_user_valid (fk_user_valid);
ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_projet (fk_projet);
ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_account(fk_account);

ALTER TABLE llx_ecm_files ADD COLUMN share_pass varchar(32) after share;

ALTER TABLE llx_prelevement_demande ADD COLUMN type varchar(12) DEFAULT '';
UPDATE llx_prelevement_demande SET type = 'ban' WHERE ext_payment_id IS NULL AND type = '';

ALTER TABLE llx_recruitment_recruitmentcandidature ADD COLUMN fk_user integer;

-- Virtual products (kits) with shipment dispatcher
CREATE TABLE llx_expeditiondet_dispatch
(
    rowid             integer AUTO_INCREMENT PRIMARY KEY,
    fk_expeditiondet  integer NOT NULL,
    fk_product        integer NOT NULL,
    fk_product_parent integer NOT NULL,
    fk_entrepot       integer NOT NULL,
    qty               real
)ENGINE=innodb;
ALTER TABLE llx_expeditiondet_dispatch ADD INDEX idx_expeditiondet_dispatch_fk_expeditiondet (fk_expeditiondet);
ALTER TABLE llx_expeditiondet_dispatch ADD INDEX idx_expeditiondet_dispatch_fk_product (fk_product);
ALTER TABLE llx_expeditiondet_dispatch ADD INDEX idx_expeditiondet_dispatch_fk_product_parent (fk_product_parent);
ALTER TABLE llx_expeditiondet_dispatch ADD INDEX idx_expeditiondet_dispatch_fk_entrepot (fk_entrepot);
ALTER TABLE llx_expeditiondet_dispatch ADD CONSTRAINT fk_expeditiondet_dispatch_fk_expeditiondet FOREIGN KEY (fk_expeditiondet) REFERENCES llx_expeditiondet (rowid);
ALTER TABLE llx_expeditiondet_dispatch ADD CONSTRAINT fk_expeditiondet_dispatch_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
ALTER TABLE llx_expeditiondet_dispatch ADD CONSTRAINT fk_expeditiondet_dispatch_fk_product_parent FOREIGN KEY (fk_product_parent) REFERENCES llx_product (rowid);
ALTER TABLE llx_expeditiondet_dispatch ADD CONSTRAINT fk_expeditiondet_dispatch_fk_entrepot FOREIGN KEY (fk_entrepot) REFERENCES llx_entrepot (rowid);

-- Remove indec column in virtual products (kits)
ALTER TABLE llx_product_association DROP COLUMN incdec;
