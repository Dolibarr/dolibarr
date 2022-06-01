--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 12.0.0 or higher.
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


-- Missing in v11

UPDATE llx_c_units set scale = 3600 where code  = 'H' and unit_type = 'time';
UPDATE llx_c_units set scale = 86400 where code = 'D' and unit_type = 'time';

create table llx_commande_fournisseur_dispatch_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_commande_fournisseur_dispatch_extrafields ADD INDEX idx_commande_fournisseur_dispatch_extrafields (fk_object);

ALTER TABLE llx_product_fournisseur_price ADD COLUMN packaging double(24,8) DEFAULT 1;

UPDATE llx_accounting_system SET fk_country = NULL, active = 0 WHERE pcg_version = 'SYSCOHADA';

create table llx_c_shipment_package_type
(
    rowid        integer  AUTO_INCREMENT PRIMARY KEY,
    label        varchar(50) NOT NULL,  -- Short name
    description	 varchar(255), -- Description
    active       integer DEFAULT 1 NOT NULL, -- Active or not
    entity       integer DEFAULT 1 NOT NULL -- Multi company id
)ENGINE=innodb;

create table llx_facturedet_rec_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_facturedet_rec_extrafields ADD INDEX idx_facturedet_rec_extrafields (fk_object);

ALTER TABLE llx_facture_rec MODIFY COLUMN titre varchar(200) NOT NULL;

create table llx_mrp_mo_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                                 -- import key
) ENGINE=innodb;

ALTER TABLE llx_mrp_mo_extrafields DROP INDEX idx_fk_object;

ALTER TABLE llx_mrp_mo_extrafields ADD INDEX idx_mrp_mo_fk_object(fk_object);



-- This var is per entity now, so we remove const if global if exists
delete from llx_const where name in ('PROJECT_HIDE_TASKS', 'MAIN_BUGTRACK_ENABLELINK', 'MAIN_HELP_DISABLELINK') and entity = 0;

-- For v12

ALTER TABLE llx_bom_bom MODIFY COLUMN duration double(24,8);

ALTER TABLE llx_prelevement_bons ADD COLUMN type varchar(16) DEFAULT 'debit-order';

ALTER TABLE llx_ecm_files MODIFY COLUMN src_object_type varchar(64);

ALTER TABLE llx_document_model MODIFY COLUMN type varchar(64);


-- Delete an old index that is duplicated
-- VMYSQL4.1 DROP INDEX ix_fk_product_stock on llx_product_batch;
-- VPGSQL8.2 DROP INDEX ix_fk_product_stock;

ALTER TABLE llx_actioncomm DROP COLUMN punctual;

DELETE FROM llx_menu where module='supplier_proposal';

UPDATE llx_website SET lang = 'en' WHERE lang like 'en_%';
UPDATE llx_website SET lang = 'fr' WHERE lang like 'fr_%';
UPDATE llx_website SET lang = 'es' WHERE lang like 'es_%';
UPDATE llx_website SET lang = 'de' WHERE lang like 'de_%';
UPDATE llx_website SET lang = 'it' WHERE lang like 'it_%';
UPDATE llx_website SET lang = 'pt' WHERE lang like 'pt_%';
UPDATE llx_website_page SET lang = 'en' WHERE lang like 'en_%';
UPDATE llx_website_page SET lang = 'fr' WHERE lang like 'fr_%';
UPDATE llx_website_page SET lang = 'es' WHERE lang like 'es_%';
UPDATE llx_website_page SET lang = 'de' WHERE lang like 'de_%';
UPDATE llx_website_page SET lang = 'it' WHERE lang like 'it_%';
UPDATE llx_website_page SET lang = 'pt' WHERE lang like 'pt_%';

ALTER TABLE llx_website ADD COLUMN lang varchar(8);
ALTER TABLE llx_website ADD COLUMN otherlang varchar(255);

ALTER TABLE llx_website_page ADD COLUMN author_alias varchar(64);

UPDATE llx_rights_def SET perms = 'order_advance', subperms = 'close' WHERE module = 'commande' AND perms = 'cloturer';
UPDATE llx_rights_def SET perms = 'propal_advance', subperms = 'close' WHERE module = 'propale' AND perms = 'cloturer';

ALTER TABLE llx_holiday_users DROP INDEX uk_holiday_users;
ALTER TABLE llx_holiday_users ADD UNIQUE INDEX uk_holiday_users(fk_user, fk_type);

ALTER TABLE llx_ticket ADD COLUMN import_key varchar(14);

ALTER TABLE llx_ticket ADD UNIQUE uk_ticket_ref (ref, entity);
ALTER TABLE llx_ticket ADD INDEX idx_ticket_entity (entity);
ALTER TABLE llx_ticket ADD INDEX idx_ticket_fk_soc (fk_soc);
ALTER TABLE llx_ticket ADD INDEX idx_ticket_fk_user_assign (fk_user_assign);
ALTER TABLE llx_ticket ADD INDEX idx_ticket_fk_project (fk_project);
ALTER TABLE llx_ticket ADD INDEX idx_ticket_fk_statut (fk_statut);


--ALTER TABLE llx_facturerec DROP COLUMN vat_src_code;


-- Migration to the new regions (France)
UPDATE llx_c_regions set nom = 'Centre-Val de Loire' WHERE fk_pays = 1 AND code_region = 24;
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 27, '21231', 0, 'Bourgogne-Franche-Comté');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 28, '76540', 0, 'Normandie');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 32, '59350', 4, 'Hauts-de-France');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 44, '67482', 2, 'Grand Est');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 75, '33063', 0, 'Nouvelle-Aquitaine');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 76, '31355', 1, 'Occitanie');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 84, '69123', 1, 'Auvergne-Rhône-Alpes');

UPDATE llx_c_departements set fk_region = 27 WHERE fk_region = 26 OR fk_region = 43;
UPDATE llx_c_departements set fk_region = 28 WHERE fk_region = 25 OR fk_region = 23;
UPDATE llx_c_departements set fk_region = 32 WHERE fk_region = 22 OR fk_region = 31;
UPDATE llx_c_departements set fk_region = 44 WHERE fk_region = 21 OR fk_region = 41 OR fk_region = 42;
UPDATE llx_c_departements set fk_region = 75 WHERE fk_region = 54 OR fk_region = 74 OR fk_region = 72;
UPDATE llx_c_departements set fk_region = 76 WHERE fk_region = 73 OR fk_region = 91;
UPDATE llx_c_departements set fk_region = 84 WHERE fk_region = 82 OR fk_region = 83;

DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 21;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 22;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 23;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 25;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 26;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 31;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 41;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 42;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 43;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 54;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 72;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 73;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 74;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 82;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 83;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 91;

ALTER TABLE llx_bookmark DROP INDEX uk_bookmark_url;
ALTER TABLE llx_bookmark DROP INDEX uk_bookmark_title;

ALTER TABLE llx_bookmark MODIFY COLUMN url TEXT;

ALTER TABLE llx_bookmark ADD UNIQUE uk_bookmark_title (fk_user, entity, title);

ALTER TABLE llx_societe_rib MODIFY COLUMN owner_address  varchar(255);
ALTER TABLE llx_societe_rib MODIFY COLUMN default_rib smallint NOT NULL DEFAULT 0;

ALTER TABLE llx_societe_rib ADD COLUMN stripe_account varchar(128);

create table llx_object_lang
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_object      integer      DEFAULT 0 NOT NULL,
  type_object    varchar(32)  NOT NULL,				-- value found into $object->element
  property       varchar(32)  NOT NULL,
  lang           varchar(5)   DEFAULT 0 NOT NULL,
  value          text,
  import_key varchar(14) DEFAULT NULL
)ENGINE=innodb;


ALTER TABLE llx_object_lang ADD UNIQUE INDEX uk_object_lang (fk_object, type_object, property, lang);


CREATE TABLE llx_categorie_actioncomm
(
  fk_categorie integer NOT NULL,
  fk_actioncomm integer NOT NULL,
  import_key varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_categorie_actioncomm ADD PRIMARY KEY pk_categorie_actioncomm (fk_categorie, fk_actioncomm);
ALTER TABLE llx_categorie_actioncomm ADD INDEX idx_categorie_actioncomm_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_actioncomm ADD INDEX idx_categorie_actioncomm_fk_actioncomm (fk_actioncomm);

ALTER TABLE llx_categorie_actioncomm ADD CONSTRAINT fk_categorie_actioncomm_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_actioncomm ADD CONSTRAINT fk_categorie_actioncomm_fk_actioncomm FOREIGN KEY (fk_actioncomm) REFERENCES llx_actioncomm (id);


ALTER TABLE llx_accounting_account ADD COLUMN labelshort varchar(255) DEFAULT NULL after label;

ALTER TABLE llx_subscription ADD COLUMN fk_user_creat   integer DEFAULT NULL;
ALTER TABLE llx_subscription ADD COLUMN fk_user_valid   integer DEFAULT NULL;

UPDATE llx_c_forme_juridique set libelle = 'SRL - Société à responsabilité limitée' WHERE code = '201';

ALTER TABLE llx_c_country ADD COLUMN eec integer;
UPDATE llx_c_country SET eec = 1 WHERE code IN ('AT','BE','BG','CY','CZ','DE','DK','EE','ES','FI','FR','GB','GR','HR','NL','HU','IE','IM','IT','LT','LU','LV','MC','MT','PL','PT','RO','SE','SK','SI','UK');

INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  1, 'PCG18-ASSOC', 'French foundation chart of accounts 2018', 1);

INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  1, 'PCGAFR14-DEV', 'The developed farm accountancy french plan 2014', 1);

INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 41, 'AT-BASE', 'Plan Austria', 1);



create table llx_c_ticket_resolution
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  entity		integer DEFAULT 1,
  code			varchar(32)				NOT NULL,
  pos			varchar(32)				NOT NULL,
  label			varchar(128)			NOT NULL,
  active		integer DEFAULT 1,
  use_default	integer DEFAULT 1,
  description	varchar(255)
)ENGINE=innodb;

ALTER TABLE llx_c_ticket_resolution ADD UNIQUE INDEX uk_code (code, entity);

INSERT INTO llx_c_ticket_resolution (code, pos, label, active, use_default, description) VALUES('SOLVED',   '10', 'Solved',    1, 0, NULL);
INSERT INTO llx_c_ticket_resolution (code, pos, label, active, use_default, description) VALUES('CANCELED', '50', 'Canceled',  1, 0, NULL);
INSERT INTO llx_c_ticket_resolution (code, pos, label, active, use_default, description) VALUES('OTHER',    '90', 'Other',     1, 0, NULL);

DELETE FROM llx_const WHERE name = __ENCRYPT('DONATION_ART885')__;

-- VMYSQL4.1 ALTER TABLE llx_extrafields MODIFY COLUMN printable integer DEFAULT 0;
-- VPGSQL8.2 ALTER TABLE llx_extrafields ALTER COLUMN printable DROP DEFAULT;
-- VPGSQL8.2 ALTER TABLE llx_extrafields MODIFY COLUMN printable integer USING printable::integer;
-- VPGSQL8.2 ALTER TABLE llx_extrafields ALTER COLUMN printable SET DEFAULT 0;
ALTER TABLE llx_extrafields ADD COLUMN printable integer DEFAULT 0;

UPDATE llx_const SET name = 'INVOICE_USE_RETAINED_WARRANTY' WHERE name = 'INVOICE_USE_SITUATION_RETAINED_WARRANTY';

ALTER TABLE llx_accounting_account DROP COLUMN pcg_subtype;

ALTER TABLE llx_product ADD COLUMN accountancy_code_buy_intra varchar(32) AFTER accountancy_code_buy;
ALTER TABLE llx_product ADD COLUMN accountancy_code_buy_export varchar(32) AFTER accountancy_code_buy_intra;

ALTER TABLE llx_entrepot ADD COLUMN fax varchar(20) DEFAULT NULL;
ALTER TABLE llx_entrepot ADD COLUMN phone varchar(20) DEFAULT NULL;

ALTER TABLE llx_accounting_account ADD COLUMN reconcilable tinyint DEFAULT 0 NOT NULL after active;

ALTER TABLE llx_categorie MODIFY type integer NOT NULL DEFAULT 1;

ALTER TABLE llx_societe_remise_except ADD COLUMN vat_src_code varchar(10) DEFAULT '';

ALTER TABLE llx_blockedlog MODIFY COLUMN object_data mediumtext;
ALTER TABLE llx_blockedlog ADD COLUMN object_version varchar(32) DEFAULT '';

ALTER TABLE llx_product_lot MODIFY COLUMN batch varchar(128);
ALTER TABLE llx_product_batch MODIFY COLUMN batch varchar(128);
ALTER TABLE llx_expeditiondet_batch MODIFY COLUMN batch varchar(128);
ALTER TABLE llx_commande_fournisseur_dispatch MODIFY COLUMN batch varchar(128);
ALTER TABLE llx_stock_mouvement MODIFY COLUMN batch varchar(128);
ALTER TABLE llx_mrp_production MODIFY COLUMN batch varchar(128);
ALTER TABLE llx_mrp_production MODIFY qty real NOT NULL DEFAULT 1;
ALTER TABLE llx_expeditiondet_batch MODIFY COLUMN batch varchar(128);

create table llx_categorie_website_page
(
  fk_categorie  	integer NOT NULL,
  fk_website_page   integer NOT NULL,
  import_key    	varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_categorie_website_page ADD PRIMARY KEY pk_categorie_website_page (fk_categorie, fk_website_page);
ALTER TABLE llx_categorie_website_page ADD INDEX idx_categorie_website_page_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_website_page ADD INDEX idx_categorie_website_page_fk_website_page (fk_website_page);

ALTER TABLE llx_categorie_website_page ADD CONSTRAINT fk_categorie_website_page_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_website_page ADD CONSTRAINT fk_categorie_website_page_website_page_rowid FOREIGN KEY (fk_website_page) REFERENCES llx_website_page (rowid);

ALTER TABLE llx_categorie ADD COLUMN date_creation	datetime;
ALTER TABLE llx_categorie ADD COLUMN tms     		timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_categorie ADD COLUMN fk_user_creat	integer;
ALTER TABLE llx_categorie ADD COLUMN fk_user_modif	integer;

ALTER TABLE llx_commandedet ADD CONSTRAINT fk_commandedet_fk_commandefourndet FOREIGN KEY (fk_commandefourndet) REFERENCES llx_commande_fournisseurdet (rowid);

-- VMYSQL4.3 ALTER TABLE llx_prelevement_facture_demande MODIFY COLUMN fk_facture INTEGER NULL;
-- VPGSQL8.2 ALTER TABLE llx_prelevement_facture_demande ALTER COLUMN fk_facture DROP NOT NULL;
ALTER TABLE llx_prelevement_facture_demande ADD COLUMN fk_facture_fourn INTEGER NULL;

-- VMYSQL4.3 ALTER TABLE llx_prelevement_facture MODIFY COLUMN fk_facture INTEGER NULL;
-- VPGSQL8.2 ALTER TABLE llx_prelevement_facture ALTER COLUMN fk_facture DROP NOT NULL;
ALTER TABLE llx_prelevement_facture ADD COLUMN fk_facture_fourn INTEGER NULL;

ALTER TABLE llx_menu MODIFY COLUMN module varchar(255);

UPDATE llx_actioncomm SET fk_action = 50 where fk_action = 40 AND code = 'TICKET_MSG';

ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN hostcharset varchar(16) DEFAULT 'UTF-8';

ALTER TABLE llx_adherent_type MODIFY subscription varchar(3) NOT NULL DEFAULT '1';
ALTER TABLE llx_adherent_type MODIFY vote varchar(3) NOT NULL DEFAULT '1';
  
UPDATE llx_prelevement_facture_demande SET entity = 1 WHERE entity IS NULL;

ALTER TABLE llx_prelevement_facture_demande ADD INDEX idx_prelevement_facture_demande_fk_facture (fk_facture);
ALTER TABLE llx_prelevement_facture_demande ADD INDEX idx_prelevement_facture_demande_fk_facture_fourn (fk_facture_fourn);

insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (721, 72,    '0','0','VAT Rate 0',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,localtax1,localtax1_type,note,active) values (722, 72,   '18','0', '0.9', '1', 'VAT Rate 18+0.9', 1);

ALTER TABLE llx_expedition ADD COLUMN billed smallint    DEFAULT 0;

-- VMYSQL4.3 ALTER TABLE llx_mrp_mo MODIFY COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_commande_fournisseurdet ADD INDEX idx_commande_fournisseurdet_fk_commande (fk_commande);
ALTER TABLE llx_commande_fournisseurdet ADD INDEX idx_commande_fournisseurdet_fk_product (fk_product);


-- VMYSQL4.3 ALTER TABLE llx_c_shipment_mode MODIFY COLUMN tracking varchar(255) NULL;
-- VPGSQL8.2 ALTER TABLE llx_c_shipment_mode ALTER COLUMN tracking DROP NOT NULL;

INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (9,'INPERSON', 'In person at your site', NULL, NULL, 0);
INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (10,'FEDEX', 'Fedex', NULL, 'https://www.fedex.com/apps/fedextrack/index.html?tracknumbers={TRACKID}', 0);
INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (11,'TNT', 'TNT', NULL, 'https://www.tnt.com/express/fr_fr/site/outils-expedition/suivi.html?searchType=con&cons=={TRACKID}', 0);
INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (12,'DHL', 'DHL', NULL, 'https://www.dhl.com/fr-fr/home/tracking/tracking-global-forwarding.html?submit=1&tracking-id={TRACKID}', 0);
INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (13,'DPD', 'DPD', NULL, 'https://www.dpd.fr/trace/{TRACKID}', 0);
INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (14,'MAINFREIGHT', 'Mainfreight', NULL, 'https://www.mainfreight.com/track?{TRACKID}', 0);


UPDATE llx_menu SET perms = '$user->rights->societe->creer' WHERE titre = 'MenuNewThirdParty' AND url = '/societe/card.php?mainmenu=companies&amp;action=create';
UPDATE llx_menu SET url = '/societe/list.php?mainmenu=companies&amp;leftmenu=thirdparties' WHERE titre = 'List' AND url = '/societe/list.php?mainmenu=companies&amp;action=create';

