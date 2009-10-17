--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.6.0 or higher. 
--

alter table llx_tmp_caisse modify fk_tva int(11) NOT NULL;

drop table llx_facture_stats;
drop table llx_stock_valorisation;
drop table llx_entrepot_valorisation;
drop table llx_groupesociete_remise;
drop table llx_groupesociete;

update llx_actioncomm set datep = datec where datep is null and datec is not null;

-- Create new table for import module
create table llx_import_model
(
  	rowid         integer AUTO_INCREMENT PRIMARY KEY,
	fk_user		  integer DEFAULT 0 NOT NULL,
  	label         varchar(50) NOT NULL,
  	type		  varchar(20) NOT NULL,
  	field         text NOT NULL
)type=innodb;

-- 2 forgotten tables
create table llx_product_cnv_livre
(
  rowid              integer PRIMARY KEY,
  isbn               varchar(13),
  ean                varchar(13),
  format             varchar(7),
  px_feuillet        float(12,4),
  px_reliure         float(12,4),
  px_couverture      float(12,4),
  px_revient         float(12,4),
  stock_loc          varchar(5),
  pages              smallint UNSIGNED,
  fk_couverture      integer,
  fk_contrat         integer,
  fk_auteur          integer DEFAULT 0
)type=innodb;

create table llx_product_cnv_livre_contrat
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  fk_cnv_livre       integer,
  quantite           integer,
  taux               float(3,2),
  date_app           datetime,
  duree              varchar(50),
  fk_user            integer,
  locked             tinyint default 0
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
alter table llx_facture_fourn add column  fk_facture_source   integer after fk_user_valid;
  
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
ALTER TABLE llx_dolibarr_modules DROP PRIMARY KEY;

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
ALTER TABLE llx_prelevement_bons DROP INDEX ref;
ALTER TABLE llx_projet DROP INDEX ref;
ALTER TABLE llx_boxes_def DROP INDEX uk_boxes_def;

ALTER TABLE llx_rights_def ADD PRIMARY KEY pk_rights_def (id, entity);
ALTER TABLE llx_dolibarr_modules ADD PRIMARY KEY pk_dolibarr_modules (numero, entity);

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
ALTER TABLE llx_bordereau_cheque ADD UNIQUE INDEX uk_bordereau_cheque (number, entity);
ALTER TABLE llx_prelevement_bons ADD UNIQUE INDEX uk_prelevement_bons_ref (ref, entity);
ALTER TABLE llx_projet ADD UNIQUE INDEX uk_projet_ref (ref, entity);
ALTER TABLE llx_boxes_def ADD UNIQUE INDEX uk_boxes_def (file, entity);

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
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (1101, 11, 1101, '', 0, 'United-States', 1);
--
-- Provinces US
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AL', 1101, '', 0, 'ALABAMA', 'Alabama', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AK', 1101, '', 0, 'ALASKA', 'Alaska', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AZ', 1101, '', 0, 'ARIZONA', 'Arizona', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AR', 1101, '', 0, 'ARKANSAS', 'Arkansas', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('CA', 1101, '', 0, 'CALIFORNIA', 'California', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('CO', 1101, '', 0, 'COLORADO', 'Colorado', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('CT', 1101, '', 0, 'CONNECTICUT', 'Connecticut', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('DE', 1101, '', 0, 'DELAWARE', 'Delaware', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('FL', 1101, '', 0, 'FLORIDA', 'Florida', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('GA', 1101, '', 0, 'GEORGIA', 'Georgia', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('HI', 1101, '', 0, 'HAWAII', 'Hawaii', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('ID', 1101, '', 0, 'IDAHO', 'Idaho', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('IL', 1101, '', 0, 'ILLINOIS','Illinois', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('IN', 1101, '', 0, 'INDIANA', 'Indiana', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('IA', 1101, '', 0, 'IOWA', 'Iowa', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('KS', 1101, '', 0, 'KANSAS', 'Kansas', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('KY', 1101, '', 0, 'KENTUCKY', 'Kentucky', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('LA', 1101, '', 0, 'LOUISIANA', 'Louisiana', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('ME', 1101, '', 0, 'MAINE', 'Maine', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MD', 1101, '', 0, 'MARYLAND', 'Maryland', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MA', 1101, '', 0, 'MASSACHUSSETTS', 'Massachusetts', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MI', 1101, '', 0, 'MICHIGAN', 'Michigan', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MN', 1101, '', 0, 'MINNESOTA', 'Minnesota', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MS', 1101, '', 0, 'MISSISSIPPI', 'Mississippi', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MO', 1101, '', 0, 'MISSOURI', 'Missouri', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MT', 1101, '', 0, 'MONTANA', 'Montana', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NE', 1101, '', 0, 'NEBRASKA', 'Nebraska', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NV', 1101, '', 0, 'NEVADA', 'Nevada', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NH', 1101, '', 0, 'NEW HAMPSHIRE', 'New Hampshire', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NJ', 1101, '', 0, 'NEW JERSEY', 'New Jersey', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NM', 1101, '', 0, 'NEW MEXICO', 'New Mexico', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NY', 1101, '', 0, 'NEW YORK', 'New York', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NC', 1101, '', 0, 'NORTH CAROLINA', 'North Carolina', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('ND', 1101, '', 0, 'NORTH DAKOTA', 'North Dakota', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('OH', 1101, '', 0, 'OHIO', 'Ohio', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('OK', 1101, '', 0, 'OKLAHOMA', 'Oklahoma', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('OR', 1101, '', 0, 'OREGON', 'Oregon', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('PA', 1101, '', 0, 'PENNSYLVANIA', 'Pennsylvania', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('RI', 1101, '', 0, 'RHODE ISLAND', 'Rhode Island', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('SC', 1101, '', 0, 'SOUTH CAROLINA', 'South Carolina', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('SD', 1101, '', 0, 'SOUTH DAKOTA', 'South Dakota', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('TN', 1101, '', 0, 'TENNESSEE', 'Tennessee', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('TX', 1101, '', 0, 'TEXAS', 'Texas', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('UT', 1101, '', 0, 'UTAH', 'Utah', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('VT', 1101, '', 0, 'VERMONT', 'Vermont', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('VA', 1101, '', 0, 'VIRGINIA', 'Virginia', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WA', 1101, '', 0, 'WASHINGTON', 'Washington', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WV', 1101, '', 0, 'WEST VIRGINIA', 'West Virginia', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WI', 1101, '', 0, 'WISCONSIN', 'Wisconsin', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WY', 1101, '', 0, 'WYOMING', 'Wyoming', 1);

alter table llx_facture_fourn_det add column ref               varchar(50) after fk_product;
alter table llx_facture_fourn_det add column label             varchar(255) after ref;

alter table llx_societe_rib modify column iban_prefix varchar(34);
alter table llx_bank_account modify column iban_prefix varchar(34);


alter table llx_projet add column datec date after fk_statut;

delete from llx_action_def;
insert into llx_action_def (rowid,code,titre,description,objet_type) values (1,'NOTIFY_VAL_FICHINTER','Validation fiche intervention','Executed when a intervention is validated','ficheinter');
insert into llx_action_def (rowid,code,titre,description,objet_type) values (2,'NOTIFY_VAL_FAC','Validation facture client','Executed when a customer invoice is approved','facture');
insert into llx_action_def (rowid,code,titre,description,objet_type) values (3,'NOTIFY_APP_ORDER_SUPPLIER','Approbation commande fournisseur','Executed when a supplier order is approved','order_supplier');
insert into llx_action_def (rowid,code,titre,description,objet_type) values (4,'NOTIFY_REF_ORDER_SUPPLIER','Refus commande fournisseur','Executed when a supplier order is refused','order_supplier');


insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 51, 5,  '19','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 52, 5,   '7','0','VAT reduced rate', 1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 53, 5,   '0','0','VAT Rate 0', 1); 

-- Add rule to avoid duplicate use of discount
update llx_facturedet set fk_remise_except = null where fk_remise_except = 0;
ALTER TABLE llx_facturedet ADD UNIQUE INDEX uk_fk_remise_except (fk_remise_except);

-- Add Mauritius
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15201', 'Mauritius Private Company Limited By Shares', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15202', 'Mauritius Company Limited By Guarantee', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15203', 'Mauritius Public Company Limited By Shares', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15204', 'Mauritius Foreign Company', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15205', 'Mauritius GBC1 (Offshore Company)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15206', 'Mauritius GBC2 (International Company)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15207', 'Mauritius General Partnership', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15208', 'Mauritius Limited Partnership', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15209', 'Mauritius Sole Proprietorship', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15210', 'Mauritius Trusts', 1);

insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'MU', 'MUR', 1, 'Roupies mauritiennes');

insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1521,152,  '0','0','VAT Rate 0',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1522,152,  '15','0','VAT Rate 15',1);

INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15201, 152, 15201, '', 0, 'Rivière Noire', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15202, 152, 15202, '', 0, 'Flacq', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15203, 152, 15203, '', 0, 'Grand Port', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15204, 152, 15204, '', 0, 'Moka', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15205, 152, 15205, '', 0, 'Pamplemousses', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15206, 152, 15206, '', 0, 'Plaines Wilhems', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15207, 152, 15207, '', 0, 'Port-Louis', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15208, 152, 15208, '', 0, 'Rivière du Rempart', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15209, 152, 15209, '', 0, 'Savanne', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15210, 152, 15210, '', 0, 'Rodrigues', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15211, 152, 15211, '', 0, 'Les îles Agaléga', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15212, 152, 15212, '', 0, 'Les écueils des Cargados Carajos', 1);

alter table llx_const modify column name        varchar(255) NOT NULL;
alter table llx_const modify column value       text NOT NULL;

-- SWEDEN (id 20)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (201,20,  '25','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (202,20,  '12','0','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (203,20,   '6','0','VAT super-reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (204,20,   '0','0','VAT Rate 0',  1);

-- Regions Suisse (id pays=6) 
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (601, 6, 601, '', 1, 'Cantons', 1);

-- Cantons Suisse 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'AG','ARGOVIE','Argovie',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'AI','APPENZELL RHODES INTERIEURES','Appenzell Rhodes intérieures',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'AR','APPENZELL RHODES EXTERIEURES','Appenzell Rhodes extérieures',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'BE','BERNE','Berne',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'BL','BALE CAMPAGNE','Bâle Campagne',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'BS','BALE VILLE','Bâle Ville',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'FR','FRIBOURG','Fribourg',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'GE','GENEVE','Genève',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'GL','GLARIS','Glaris',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'GR','GRISONS','Grisons',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'JU','JURA','Jura',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'LU','LUCERNE','Lucerne',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'NE','NEUCHATEL','Neuchâtel',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'NW','NIDWALD','Nidwald',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'OW','OBWALD','Obwald',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'SG','SAINT-GALL','Saint-Gall',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'SH','SCHAFFHOUSE','Schaffhouse',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'SO','SOLEURE','Soleure',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'SZ','SCHWYZ','Schwyz',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'TG','THURGOVIE','Thurgovie',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'TI','TESSIN','Tessin',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'UR','URI','Uri',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'VD','VAUD','Vaud',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'VS','VALAIS','Valais',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'ZG','ZUG','Zug',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'ZH','ZURICH','Zürich',1);

-- Regions spain (id pays=4)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (401,  4, 401, '', 0, 'Andalucia', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (402,  4, 402, '', 0, 'Aragón', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (403,  4, 403, '', 0, 'Castilla y León', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (404,  4, 404, '', 0, 'Castilla la Mancha', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (405,  4, 405, '', 0, 'Canarias', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (406,  4, 406, '', 0, 'Cataluña', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (407,  4, 407, '', 0, 'Comunidad de Ceuta', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (408,  4, 408, '', 0, 'Comunidad Foral de Navarra', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (409,  4, 409, '', 0, 'Comunidad de Melilla', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (410,  4, 410, '', 0, 'Cantabria', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (411,  4, 411, '', 0, 'Comunidad Valenciana', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (412,  4, 412, '', 0, 'Extemadura', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (413,  4, 413, '', 0, 'Galicia', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (414,  4, 414, '', 0, 'Islas Baleares', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (415,  4, 415, '', 0, 'La Rioja', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (416,  4, 416, '', 0, 'Comunidad de Madrid', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (417,  4, 417, '', 0, 'Región de Murcia', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (418,  4, 418, '', 0, 'Principado de Asturias', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (419,  4, 419, '', 0, 'Pais Vasco', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (420,  4, 420, '', 0, 'Otros', 1);

-- Provinces Spain
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('01', 419, '', 19, 'PAIS VASCO', 'País Vasco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('02', 404, '', 4, 'ALBACETE', 'Albacete', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('03', 411, '', 11, 'ALICANTE', 'Alicante', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('04', 401, '', 1, 'ALMERIA', 'Almería', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('05', 403, '', 3, 'AVILA', 'Avila', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('06', 412, '', 12, 'BADAJOZ', 'Badajoz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('07', 414, '', 14, 'ISLAS BALEARES', 'Islas Baleares', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('08', 406, '', 6, 'BARCELONA', 'Barcelona', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('09', 403, '', 8, 'BURGOS', 'Burgos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('10', 412, '', 12, 'CACERES', 'Cáceres', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('11', 401, '', 1, 'CADIz', 'Cádiz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('12', 411, '', 11, 'CASTELLON', 'Castellón', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('13', 404, '', 4, 'CIUDAD REAL', 'Ciudad Real', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('14', 401, '', 1, 'CORDOBA', 'Córdoba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('15', 413, '', 13, 'LA CORUÑA', 'La Coruña', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('16', 404, '', 4, 'CUENCA', 'Cuenca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('17', 406, '', 6, 'GERONA', 'Gerona', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('18', 401, '', 1, 'GRANADA', 'Granada', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('19', 404, '', 4, 'GUADALAJARA', 'Guadalajara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('20', 419, '', 19, 'GUIPUZCOA', 'Guipúzcoa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('21', 401, '', 1, 'HUELVA', 'Huelva', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('22', 402, '', 2, 'HUESCA', 'Huesca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('23', 401, '', 1, 'JAEN', 'Jaén', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('24', 403, '', 3, 'LEON', 'León', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('25', 406, '', 6, 'LERIDA', 'Lérida', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('26', 415, '', 15, 'LA RIOJA', 'La Rioja', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('27', 413, '', 13, 'LUGO', 'Lugo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('28', 416, '', 16, 'MADRID', 'Madrid', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('29', 401, '', 1, 'MALAGA', 'Málaga', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('30', 417, '', 17, 'MURCIA', 'Murcia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('31', 408, '', 8, 'NAVARRA', 'Navarra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('32', 413, '', 13, 'ORENSE', 'Orense', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('33', 418, '', 18, 'ASTURIAS', 'Asturias', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('34', 403, '', 3, 'PALENCIA', 'Palencia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('35', 405, '', 5, 'LAS PALMAS', 'Las Palmas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('36', 413, '', 13, 'PONTEVEDRA', 'Pontevedra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('37', 403, '', 3, 'SALAMANCA', 'Salamanca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('38', 405, '', 5, 'STA. CRUZ DE TENERIFE', 'Sta. Cruz de Tenerife', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('39', 410, '', 10, 'CANTABRIA', 'Cantabria', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('40', 403, '', 3, 'SEGOVIA', 'Segovia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('41', 401, '', 1, 'SEVILLA', 'Sevilla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('42', 403, '', 3, 'SORIA', 'Soria', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('43', 406, '', 6, 'TARRAGONA', 'Tarragona', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('44', 402, '', 2, 'TERUEL', 'Teruel', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('45', 404, '', 5, 'TOLEDO', 'Toledo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('46', 411, '', 11, 'VALENCIA', 'Valencia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('47', 403, '', 3, 'VALLADOLID', 'Valladolid', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('48', 419, '', 19, 'VIZCAYA', 'Vizcaya', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('49', 403, '', 3, 'ZAMORA', 'Zamora', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('50', 402, '', 1, 'ZARAGOZA', 'Zaragoza', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('51', 407, '', 7, 'CEUTA', 'Ceuta', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('52', 409, '', 9, 'MELILLA', 'Melilla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('53', 420, '', 20, 'OTROS', 'Otros', 1);

