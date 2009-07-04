--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.6.0 or higher. 
--

-- Create new table for import module
create table llx_import_model
(
  	rowid         integer AUTO_INCREMENT PRIMARY KEY,
	fk_user		  integer DEFAULT 0 NOT NULL,
  	label         varchar(50) NOT NULL,
  	type		  varchar(20) NOT NULL,
  	field         text NOT NULL
)type=innodb;

update llx_bank_url set type='banktransfert' where type='?' and label='(banktransfert)';

ALTER TABLE llx_import_model ADD UNIQUE INDEX uk_import_model (label,type);

delete from llx_const where name = 'FACTURE_ENABLE_RECUR';

alter table llx_facturedet_rec add column  product_type		  integer    DEFAULT 0 after fk_product;

-- Usage of llx_menu_const and llx_menu_constraint is too complicated
-- so we made first change to remove it
alter table llx_menu_const drop foreign key fk_menu_const_fk_menu;
update llx_menu_constraint set action = '$conf->societe->enabled' where action = '$conf->commercial->enabled';

ALTER TABLE llx_adherent ADD UNIQUE INDEX uk_adherent_fk_soc (fk_soc);

alter table llx_facture add column  tms timestamp after date_valid;
alter table llx_facture_fourn add column  tms timestamp after datef;
update llx_facture set tms = datec where tms <= 0;
update llx_facture_fourn set tms = datec where tms <= 0;


-- Clean no more required parameters
delete from llx_const where name = 'MAIN_MODULE_COMMERCIAL';
delete from llx_const where name like 'MAIN_MODULE_%_DIR_OUTPUT';
delete from llx_const where name like 'MAIN_MODULE_%_DIR_TEMP';
delete from llx_const where name like 'PRODUIT_CONFIRM_DELETE_LINE';
delete from llx_const where name = 'MAIN_MODULE_SYSLOG' and entity = 2;
delete from llx_const where name = 'SYSLOG_FILE' and entity = 2;
delete from llx_const where name = 'SYSLOG_LEVEL' and entity = 2;

alter table llx_societe add column import_key varchar(14);

 
-- V4.1 delete from llx_paiementfourn where rowid not in (select fk_paiementfourn from llx_paiementfourn_facturefourn);
-- V4.1 delete from llx_paiementfourn_facturefourn where fk_facturefourn not in (select rowid from llx_facture_fourn);


 
-- Multi company
ALTER TABLE llx_rights_def ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER module;
ALTER TABLE llx_events ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER type;
ALTER TABLE llx_boxes_def ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER file;
ALTER TABLE llx_user_param ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER fk_user;
ALTER TABLE llx_societe ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER nom;
ALTER TABLE llx_socpeople ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER fk_soc;
ALTER TABLE llx_product ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;
ALTER TABLE llx_entrepot ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER label;
ALTER TABLE llx_chargesociales ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER libelle;
ALTER TABLE llx_tva ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER label;
ALTER TABLE llx_bank_account ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER label;
ALTER TABLE llx_document_model ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER nom;
ALTER TABLE llx_menu ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER menu_handler;
ALTER TABLE llx_ecm_directories ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER label;
ALTER TABLE llx_mailing ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER titre;
ALTER TABLE llx_categorie ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER label;
ALTER TABLE llx_propal ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;
ALTER TABLE llx_commande ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;
ALTER TABLE llx_commande_fournisseur ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;
ALTER TABLE llx_product_fournisseur ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref_fourn;
ALTER TABLE llx_facture ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER facnumber;
ALTER TABLE llx_expedition ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;
ALTER TABLE llx_facture_fourn ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER facnumber;
ALTER TABLE llx_livraison ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;
ALTER TABLE llx_fichinter ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;
ALTER TABLE llx_contrat ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;
ALTER TABLE llx_c_barcode_type ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER code;
ALTER TABLE llx_dolibarr_modules ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER numero;
ALTER TABLE llx_bank_categ ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER label;
ALTER TABLE llx_bordereau_cheque ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER number;
ALTER TABLE llx_prelevement_bons ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;
ALTER TABLE llx_projet ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;

ALTER TABLE llx_rights_def DROP PRIMARY KEY;
ALTER TABLE llx_user_param DROP INDEX fk_user;
ALTER TABLE llx_societe DROP INDEX uk_societe_prefix_comm;
ALTER TABLE llx_societe DROP INDEX uk_societe_code_client;
ALTER TABLE llx_product DROP INDEX uk_product_ref;
ALTER TABLE llx_entrepot DROP INDEX label;
ALTER TABLE llx_bank_account DROP INDEX uk_bank_account_label;
ALTER TABLE llx_document_model DROP INDEX uk_document_model;
ALTER TABLE llx_menu DROP INDEX idx_menu_uk_menu;
ALTER TABLE llx_categorie DROP INDEX uk_categorie_ref;
ALTER TABLE llx_propal DROP INDEX ref;
ALTER TABLE llx_commande DROP INDEX ref;
ALTER TABLE llx_commande_fournisseur DROP INDEX uk_commande_fournisseur_ref;
ALTER TABLE llx_product_fournisseur DROP INDEX fk_product;
ALTER TABLE llx_product_fournisseur DROP INDEX fk_soc;
ALTER TABLE llx_facture DROP INDEX idx_facture_uk_facnumber;
ALTER TABLE llx_expedition DROP INDEX idx_expedition_uk_ref;
ALTER TABLE llx_facture_fourn DROP INDEX uk_facture_fourn_ref;
ALTER TABLE llx_livraison DROP INDEX idx_expedition_uk_ref;
ALTER TABLE llx_livraison DROP INDEX idx_livraison_uk_ref;
ALTER TABLE llx_fichinter DROP INDEX ref;
ALTER TABLE llx_dolibarr_modules DROP PRIMARY KEY;
ALTER TABLE llx_prelevement_bons DROP INDEX ref;
ALTER TABLE llx_projet DROP INDEX ref;

ALTER TABLE llx_rights_def ADD PRIMARY KEY (id, entity);
ALTER TABLE llx_user_param ADD UNIQUE INDEX uk_user_param (fk_user,param,entity);
ALTER TABLE llx_societe ADD UNIQUE INDEX uk_societe_prefix_comm (prefix_comm, entity);
ALTER TABLE llx_societe ADD UNIQUE INDEX uk_societe_code_client (code_client, entity);
ALTER TABLE llx_product ADD UNIQUE INDEX uk_product_ref (ref, entity);
ALTER TABLE llx_entrepot ADD UNIQUE INDEX uk_entrepot_label (label, entity);
ALTER TABLE llx_bank_account ADD UNIQUE INDEX uk_bank_account_label (label, entity);
ALTER TABLE llx_document_model ADD UNIQUE INDEX uk_document_model (nom, type, entity);
ALTER TABLE llx_menu ADD UNIQUE INDEX idx_menu_uk_menu (menu_handler, fk_menu, url, entity);
ALTER TABLE llx_categorie ADD UNIQUE INDEX uk_categorie_ref (label, type, entity);
ALTER TABLE llx_propal ADD UNIQUE INDEX uk_propal_ref (ref, entity);
ALTER TABLE llx_commande ADD UNIQUE INDEX uk_commande_ref (ref, entity);
ALTER TABLE llx_commande_fournisseur ADD UNIQUE INDEX uk_commande_fournisseur_ref (ref, fk_soc, entity);
ALTER TABLE llx_product_fournisseur ADD UNIQUE INDEX uk_product_fournisseur_ref (ref_fourn, fk_soc, entity);
ALTER TABLE llx_product_fournisseur ADD INDEX idx_product_fourn_fk_product (fk_product, entity);
ALTER TABLE llx_product_fournisseur ADD INDEX idx_product_fourn_fk_soc (fk_soc, entity);
ALTER TABLE llx_facture ADD UNIQUE INDEX idx_facture_uk_facnumber (facnumber, entity);
ALTER TABLE llx_expedition ADD UNIQUE INDEX idx_expedition_uk_ref (ref, entity);
ALTER TABLE llx_facture_fourn ADD UNIQUE INDEX uk_facture_fourn_ref (facnumber, fk_soc, entity);
ALTER TABLE llx_livraison ADD UNIQUE INDEX idx_livraison_uk_ref (ref, entity);
ALTER TABLE llx_fichinter ADD UNIQUE INDEX uk_fichinter_ref (ref, entity);
ALTER TABLE llx_contrat ADD UNIQUE INDEX uk_contrat_ref (ref, entity);
ALTER TABLE llx_dolibarr_modules ADD PRIMARY KEY pk_dolibarr_modules (numero, entity);
ALTER TABLE llx_bordereau_cheque ADD UNIQUE INDEX uk_bordereau_cheque (number, entity);
ALTER TABLE llx_prelevement_bons ADD UNIQUE INDEX uk_prelevement_bons_ref (ref, entity);
ALTER TABLE llx_projet ADD UNIQUE INDEX uk_projet_ref (ref, entity);

ALTER TABLE llx_projet ADD INDEX idx_projet_fk_soc (fk_soc);
ALTER TABLE llx_projet ADD CONSTRAINT fk_projet_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);


UPDATE llx_const SET entity=0 WHERE name='MAIN_MODULE_USER' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_POPUP_CALENDAR' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_MAIL_SMTP_SERVER' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_MAIL_SMTP_PORT' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_UPLOAD_DOC' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_FEATURES_LEVEL' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_SEARCHFORM_SOCIETE' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_SEARCHFORM_CONTACT' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_SEARCHFORM_PRODUITSERVICE' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_SEARCHFORM_ADHERENT' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_SIZE_LISTE_LIMIT' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='MAIN_SHOW_WORKBOARD' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='SOCIETE_NOLIST_COURRIER' AND entity=1;
UPDATE llx_const SET entity=0 WHERE name='GENBARCODE_LOCATION' AND entity=1;

UPDATE llx_const SET entity=0 WHERE name='MAIN_MODULE_SYSLOG';
UPDATE llx_const SET entity=0 WHERE name='SYSLOG_FILE';
UPDATE llx_const SET entity=0 WHERE name='SYSLOG_LEVEL';


-- Fix to solve forgoten names on keys
ALTER TABLE llx_dolibarr_modules drop primary KEY;
ALTER TABLE llx_dolibarr_modules ADD PRIMARY KEY pk_dolibarr_modules (numero, entity);


alter table llx_commande_fournisseur add column   ref_supplier        varchar(30) after entity;

alter table llx_mailing add column bgcolor  varchar(8) after body;
alter table llx_mailing add column bgimage  varchar(255) after bgcolor;

-- Added US states
INSERT INTO llx_c_regions (code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (110, 11, '', 0, 'United-States', 1);
--
-- Provinces US
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AL', 110, '', 0, 'ALABAMA', 'Alabama', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AK', 110, '', 0, 'ALASKA', 'Alaska', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AZ', 110, '', 0, 'ARIZONA', 'Arizona', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AR', 110, '', 0, 'ARKANSAS', 'Arkansas', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('CA', 110, '', 0, 'CALIFORNIA', 'California', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('CO', 110, '', 0, 'COLORADO', 'Colorado', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('CT', 110, '', 0, 'CONNECTICUT', 'Connecticut', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('DE', 110, '', 0, 'DELAWARE', 'Delaware', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('FL', 110, '', 0, 'FLORIDA', 'Florida', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('GA', 110, '', 0, 'GEORGIA', 'Georgia', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('HI', 110, '', 0, 'HAWAI', 'Hawaii', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('ID', 110, '', 0, 'IDAHO', 'Idaho', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('IL', 110, '', 0, 'ILLINOIS','Illinois', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('IN', 110, '', 0, 'INDIANA', 'Indiana', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('IA', 110, '', 0, 'IOWA', 'Iowa', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('KS', 110, '', 0, 'KANSAS', 'Kansas', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('KY', 110, '', 0, 'KENTUCKY', 'Kentucky', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('LA', 110, '', 0, 'LOUISIANA', 'Louisiana', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('ME', 110, '', 0, 'MAINE', 'Maine', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MD', 110, '', 0, 'MARYLAND', 'Maryland', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MA', 110, '', 0, 'MASSACHUSSETTS', 'Massachusetts', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MI', 110, '', 0, 'MICHIGAN', 'Michigan', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MN', 110, '', 0, 'MINNESOTA', 'Minnesota', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MS', 110, '', 0, 'MISSISSIPI', 'Mississippi', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MO', 110, '', 0, 'MISSOURI', 'Missouri', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MT', 110, '', 0, 'MONTANA', 'Montana', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NE', 110, '', 0, 'NEBRASKA', 'Nebraska', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NV', 110, '', 0, 'NEVADA', 'Nevada', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NH', 110, '', 0, 'NEW HAMPSHIRE', 'New Hampshire', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NJ', 110, '', 0, 'NEW JERSEY', 'New Jersey', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NM', 110, '', 0, 'NEW MEXICO', 'New Mexico', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NY', 110, '', 0, 'NEW YORK', 'New York', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NC', 110, '', 0, 'NORTH CAROLIAN', 'North Carolina', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('ND', 110, '', 0, 'NORTH DAKOTA', 'North Dakota', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('OH', 110, '', 0, 'OHIO', 'Ohio', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('OK', 110, '', 0, 'OKLAHOMA', 'Oklahoma', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('OR', 110, '', 0, 'OREGON', 'Oregon', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('PA', 110, '', 0, 'PENNSYLVANIA', 'Pennsylvania', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('RI', 110, '', 0, 'RHODE ISLAND', 'Rhode Island', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('SC', 110, '', 0, 'SOUTH CAROLINA', 'South Carolina', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('SD', 110, '', 0, 'SOUTH DAKOTA', 'South Dakota', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('TN', 110, '', 0, 'TENNESSEE', 'Tennessee', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('TX', 110, '', 0, 'TEXAS', 'Texas', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('UT', 110, '', 0, 'UTAH', 'Utah', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('VT', 110, '', 0, 'VERMONT', 'Vermont', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('VA', 110, '', 0, 'VIRGINIA', 'Virginia', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WA', 110, '', 0, 'WASHINGTON', 'Washington', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WV', 110, '', 0, 'WEST VIRGINIA', 'West Virginia', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WI', 110, '', 0, 'WISCONSIN', 'Wisconsin', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WY', 110, '', 0, 'WYOMONG', 'Wyoming', 1);

alter table llx_facture_fourn_det add column ref               varchar(50) after fk_product;
alter table llx_facture_fourn_det add column label             varchar(255) after ref;

