--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 16.0.0 or higher.
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


-- Missing in v16 or lower

ALTER TABLE llx_accounting_system MODIFY COLUMN pcg_version varchar(32) NOT NULL;

ALTER TABLE llx_c_action_trigger MODIFY elementtype VARCHAR(64);

ALTER TABLE llx_c_email_templates ADD COLUMN joinfiles text;
ALTER TABLE llx_c_email_templates ADD COLUMN email_from varchar(255);
ALTER TABLE llx_c_email_templates ADD COLUMN email_to varchar(255);
ALTER TABLE llx_c_email_templates ADD COLUMN email_tocc varchar(255);
ALTER TABLE llx_c_email_templates ADD COLUMN email_tobcc varchar(255);
ALTER TABLE llx_c_email_templates ADD COLUMN content_lines text;
ALTER TABLE llx_c_email_templates ADD COLUMN enabled varchar(255) DEFAULT '1';

ALTER TABLE llx_expedition ADD COLUMN billed smallint    DEFAULT 0;

ALTER TABLE llx_user DROP COLUMN idpers1;
ALTER TABLE llx_user DROP COLUMN idpers2;
ALTER TABLE llx_user DROP COLUMN idpers3;


-- v17

UPDATE llx_c_paiement SET code = 'BANCON' WHERE code = 'BAN' AND libelle = 'Bancontact';

-- VMYSQL4.3 ALTER TABLE llx_partnership MODIFY COLUMN fk_user_creat integer NULL;
-- VPGSQL8.2 ALTER TABLE llx_partnership ALTER COLUMN fk_user_creat DROP NOT NULL;

ALTER TABLE llx_partnership ADD COLUMN ip varchar(250);
ALTER TABLE llx_adherent ADD COLUMN ip varchar(250);

ALTER TABLE llx_fichinterdet_rec DROP COLUMN remise;
ALTER TABLE llx_fichinterdet_rec DROP COLUMN fk_export_commpta;

UPDATE llx_const set name = 'ADHERENT_MAILMAN_ADMIN_PASSWORD' WHERE name = 'ADHERENT_MAILMAN_ADMINPW';

ALTER TABLE llx_oauth_token ADD COLUMN state text after tokenstring;

ALTER TABLE llx_adherent ADD COLUMN default_lang VARCHAR(6) DEFAULT NULL AFTER datefin;

ALTER TABLE llx_adherent_type ADD COLUMN caneditamount integer DEFAULT 0 AFTER amount;

ALTER TABLE llx_holiday CHANGE COLUMN date_approve date_approval datetime;

UPDATE llx_holiday SET date_approval = date_valid WHERE statut = 3 AND date_approval IS NULL;
UPDATE llx_holiday SET fk_user_approve = fk_user_valid WHERE statut = 3 AND fk_user_approve IS NULL;

ALTER TABLE llx_inventory ADD COLUMN categories_product VARCHAR(255) DEFAULT NULL AFTER fk_product;

ALTER TABLE llx_ticket ADD COLUMN ip varchar(250);

ALTER TABLE llx_societe ADD last_main_doc VARCHAR(255) NULL AFTER model_pdf;

ALTER TABLE llx_emailcollector_emailcollector MODIFY COLUMN lastresult text;
ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN port varchar(10) DEFAULT '993';
ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN acces_type integer DEFAULT 0;
ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN oauth_service varchar(128) DEFAULT NULL;


ALTER TABLE llx_bank ADD COLUMN position integer DEFAULT 0;

ALTER TABLE llx_commande_fournisseur_dispatch ADD INDEX idx_commande_fournisseur_dispatch_fk_product (fk_product);

INSERT INTO llx_const (name, entity, value, type, visible) VALUES ('MAIN_SECURITY_MAX_IMG_IN_HTML_CONTENT', 1, 1000, 'int', 0);

ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN port varchar(10) DEFAULT '993';

ALTER TABLE llx_facture ADD COLUMN close_missing_amount double(24, 8) after close_code;

ALTER TABLE llx_facture_fourn ADD COLUMN close_missing_amount double(24, 8) after close_code;

ALTER TABLE llx_inventory ADD COLUMN categories_product VARCHAR(255) DEFAULT NULL AFTER fk_product;

ALTER TABLE llx_product ADD COLUMN sell_or_eat_by_mandatory tinyint DEFAULT 0 NOT NULL AFTER tobatch;
  -- Make sell-by or eat-by date mandatory

ALTER TABLE llx_recruitment_recruitmentcandidature ADD email_date datetime after email_msgid;

ALTER TABLE llx_societe ADD last_main_doc VARCHAR(255) NULL AFTER model_pdf;

ALTER TABLE llx_ticket ADD COLUMN ip varchar(250);

ALTER TABLE llx_ticket ADD email_date datetime after email_msgid;

ALTER TABLE llx_cronjob ADD COLUMN pid integer;

INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-VICTORYDAY',  0, 2, '', 0,  5,  8, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-NATIONALDAY', 0, 2, '', 0,  7, 21, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-ASSOMPTION',  0, 2, '', 0,  8, 15, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-TOUSSAINT',   0, 2, '', 0, 11,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-ARMISTICE',   0, 2, '', 0, 11, 11, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-EASTER',      0, 2, 'eastermonday', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-ASCENSION',   0, 2, 'ascension', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-PENTECOST',   0, 2, 'pentecost', 0, 0, 0, 1);

ALTER TABLE llx_societe_rib ADD COLUMN state_id integer AFTER default_rib;
ALTER TABLE llx_societe_rib ADD COLUMN fk_country integer AFTER state_id;
ALTER TABLE llx_societe_rib ADD COLUMN currency_code varchar(3) AFTER fk_country;

ALTER TABLE llx_user_rib ADD COLUMN state_id integer AFTER owner_address;
ALTER TABLE llx_user_rib ADD COLUMN fk_country integer AFTER state_id;
ALTER TABLE llx_user_rib ADD COLUMN currency_code varchar(3) AFTER fk_country;

CREATE TABLE llx_bank_extrafields
(
  rowid      integer AUTO_INCREMENT PRIMARY KEY,
  tms        timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object  integer NOT NULL,
  import_key varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_bank_extrafields ADD INDEX idx_bank_extrafields (fk_object);

ALTER TABLE llx_product_lot ADD COLUMN note_public text DEFAULT NULL after batch;
ALTER TABLE llx_product_lot ADD COLUMN note_private text DEFAULT NULL after note_public;

ALTER TABLE llx_user CHANGE COLUMN note note_private text;

UPDATE llx_c_effectif SET code='EF101-500', libelle='101 - 500' WHERE code='EF100-500';


ALTER TABLE llx_product ADD COLUMN fk_default_workstation integer DEFAULT NULL;
ALTER TABLE llx_bom_bomline ADD COLUMN fk_unit integer DEFAULT NULL;

ALTER TABLE llx_rights_def ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE llx_establishment SET name='' WHERE name IS NULL;
ALTER TABLE llx_establishment CHANGE name label varchar(255) NOT NULL;

ALTER TABLE llx_don ADD UNIQUE INDEX idx_don_uk_ref (ref, entity);

ALTER TABLE llx_don ADD INDEX idx_don_fk_soc (fk_soc);
ALTER TABLE llx_don ADD INDEX idx_don_fk_project (fk_projet);
ALTER TABLE llx_don ADD INDEX idx_don_fk_user_author (fk_user_author);
ALTER TABLE llx_don ADD INDEX idx_don_fk_user_valid (fk_user_valid);

ALTER TABLE llx_commande ADD COLUMN revenuestamp double(24,8) DEFAULT 0 after localtax2;

create table llx_element_categorie
(
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  fk_categorie  integer NOT NULL,
  fk_element  integer NOT NULL,
  import_key    varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_element_categorie ADD UNIQUE INDEX idx_element_categorie_idx (fk_element, fk_categorie);

ALTER TABLE llx_element_categorie ADD CONSTRAINT fk_element_categorie_fk_categorie FOREIGN KEY (fk_categorie) REFERENCES llx_categorie(rowid);

INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('PROJECT_SENTBYMAIL','Project sent by mail','Executed when a project is sent by email','project',144);

ALTER TABLE llx_socpeople ADD INDEX idx_socpeople_lastname (lastname);

ALTER TABLE llx_societe ADD INDEX idx_societe_nom(nom);

ALTER TABLE llx_extrafields MODIFY COLUMN fielddefault text;

ALTER TABLE llx_bank_url ADD INDEX idx_bank_url_url_id (url_id);

ALTER TABLE llx_societe_remise_except ADD COLUMN multicurrency_code varchar(3) NULL;
ALTER TABLE llx_societe_remise_except ADD COLUMN multicurrency_tx double(24,8) NULL;

ALTER TABLE llx_hrm_evaluationdet CHANGE COLUMN `rank` rankorder integer;

-- Rename const to hide public and private notes (fix allow notes const was used to hide)
UPDATE llx_const SET name = 'MAIN_LIST_HIDE_PUBLIC_NOTES' WHERE name = 'MAIN_LIST_ALLOW_PUBLIC_NOTES';
UPDATE llx_const SET name = 'MAIN_LIST_HIDE_PRIVATE_NOTES' WHERE name = 'MAIN_LIST_ALLOW_PRIVATE_NOTES';
