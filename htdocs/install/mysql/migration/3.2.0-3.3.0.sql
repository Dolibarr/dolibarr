--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.2.0 or higher. 
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y


-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);

DROP TABLE llx_product_ca;
DROP TABLE llx_document;
DROP TABLE llx_dolibarr_modules;
DROP TABLE llx_product_fournisseur;

ALTER TABLE llx_adherent ADD COLUMN canvas varchar(32);

ALTER TABLE llx_societe_rib MODIFY COLUMN bic varchar(20);

ALTER TABLE llx_facture_rec ADD COLUMN usenewprice        integer;

ALTER TABLE llx_facture_fourn_det ADD COLUMN remise_percent	real       DEFAULT 0 after qty;

ALTER TABLE llx_extrafields MODIFY COLUMN size varchar(8) DEFAULT NULL;

ALTER TABLE llx_menu MODIFY COLUMN fk_mainmenu   varchar(24);
ALTER TABLE llx_menu MODIFY COLUMN fk_leftmenu   varchar(24);

ALTER TABLE llx_societe ADD COLUMN idprof6 varchar(128) after idprof5;
ALTER TABLE llx_societe DROP COLUMN fk_secteur;
ALTER TABLE llx_societe DROP COLUMN description;
ALTER TABLE llx_societe DROP COLUMN services;
ALTER TABLE llx_societe MODIFY COLUMN ref_ext varchar(128);

ALTER TABLE llx_bank ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP after datec;
  
-- Monaco VAT Rates
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 271,  27,'19.6','0','VAT standard rate (France hors DOM-TOM)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 272,  27, '8.5','0','VAT standard rate (DOM sauf Guyane et Saint-Martin)',0);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 273,  27, '8.5','1','VAT standard rate (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par acheteur',0);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 274,  27, '5.5','0','VAT reduced rate (France hors DOM-TOM)',0);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 275,  27,   '0','0','VAT Rate 0 ou non applicable',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 276,  27, '2.1','0','VAT super-reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 277,  27,   '7','0','VAT reduced rate',1);

INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES ( 8, 'SRC_WOM',        'Word of mouth', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES ( 9, 'SRC_PARTNER',    'Partner', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES (10, 'SRC_EMPLOYEE',   'Employee', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES (11, 'SRC_SPONSORING', 'Sponsoring', 1);

ALTER TABLE llx_commande_fournisseur CHANGE COLUMN date_cloture date_approve datetime;
ALTER TABLE llx_commande_fournisseur CHANGE COLUMN fk_user_cloture fk_user_approve integer;

ALTER TABLE llx_mailing MODIFY COLUMN body mediumtext;
ALTER TABLE llx_mailing ADD COLUMN extraparams varchar(255);


ALTER TABLE llx_product MODIFY COLUMN ref varchar(128)  NOT NULL;
ALTER TABLE llx_product MODIFY COLUMN ref_ext varchar(128);

ALTER TABLE llx_product_fournisseur_price DROP COLUMN fk_product_fournisseur;
ALTER TABLE llx_product_fournisseur_price ADD charges DOUBLE( 24, 8 ) DEFAULT 0 AFTER unitprice;
ALTER TABLE llx_product_fournisseur_price ADD unitcharges DOUBLE( 24, 8 ) DEFAULT 0 AFTER charges;

alter table llx_commandedet add column fk_product_fournisseur_price integer after info_bits;
alter table llx_commandedet add column buy_price_ht double(24,8) DEFAULT 0 after fk_product_fournisseur_price;
alter table llx_commandedet drop column marge_tx;
alter table llx_commandedet drop column marque_tx;

alter table llx_facturedet add column fk_product_fournisseur_price integer after info_bits;
alter table llx_facturedet add column buy_price_ht double(24,8) DEFAULT 0 after fk_product_fournisseur_price;

alter table llx_propaldet add column fk_product_fournisseur_price integer after info_bits;
alter table llx_propaldet add column buy_price_ht double(24,8) DEFAULT 0 after fk_product_fournisseur_price;
alter table llx_propaldet drop column pa_ht;
alter table llx_propaldet drop column marge_tx;
alter table llx_propaldet drop column marque_tx;

alter table llx_expedition add column height_unit integer after height;

ALTER TABLE llx_commande CHANGE COLUMN fk_demand_reason fk_input_reason integer NULL DEFAULT NULL;
ALTER TABLE llx_propal CHANGE COLUMN fk_demand_reason fk_input_reason integer NULL DEFAULT NULL;
ALTER TABLE llx_commande_fournisseur CHANGE COLUMN fk_methode_commande fk_input_method integer NULL DEFAULT 0;

INSERT INTO llx_const (name, value, type, note, visible) values (__ENCRYPT('PRODUCT_CODEPRODUCT_ADDON')__, __ENCRYPT('mod_codeproduct_leopard')__, 'yesno', 'Module to control product codes', 0);

ALTER TABLE llx_c_barcode_type ADD UNIQUE INDEX uk_c_barcode_type(code, entity);

ALTER TABLE llx_socpeople ADD column no_email SMALLINT NOT NULL DEFAULT 0 AFTER priv;

ALTER TABLE llx_commande_fournisseur ADD COLUMN date_livraison date NULL;

ALTER TABLE llx_propaldet ADD COLUMN label varchar(255) DEFAULT NULL AFTER fk_product;
ALTER TABLE llx_commandedet ADD COLUMN label varchar(255) DEFAULT NULL AFTER fk_product;
ALTER TABLE llx_facturedet ADD COLUMN label varchar(255) DEFAULT NULL AFTER fk_product;
ALTER TABLE llx_facturedet_rec ADD COLUMN label varchar(255) DEFAULT NULL AFTER product_type;

ALTER TABLE llx_actioncomm MODIFY elementtype VARCHAR(32);

ALTER TABLE llx_ecm_directories MODIFY COLUMN label varchar(64) NOT NULL;
ALTER TABLE llx_ecm_directories ADD COLUMN fullpath varchar(255) AFTER cachenbofdoc;
ALTER TABLE llx_ecm_directories MODIFY COLUMN fullpath varchar(255);
ALTER TABLE llx_ecm_directories ADD COLUMN extraparams varchar(255) AFTER fullpath;
ALTER TABLE llx_ecm_directories ADD COLUMN acl text;
ALTER TABLE llx_ecm_directories ADD INDEX idx_ecm_directories_fk_user_c (fk_user_c);
ALTER TABLE llx_ecm_directories ADD INDEX idx_ecm_directories_fk_user_m (fk_user_m);
ALTER TABLE llx_ecm_directories ADD CONSTRAINT fk_ecm_directories_fk_user_c FOREIGN KEY (fk_user_c) REFERENCES llx_user (rowid);
ALTER TABLE llx_ecm_directories ADD CONSTRAINT fk_ecm_directories_fk_user_m FOREIGN KEY (fk_user_m) REFERENCES llx_user (rowid);

create table llx_element_tag
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,
  lang				varchar(5) NOT NULL,
  tag				varchar(255) NOT NULL,
  fk_element		integer NOT NULL,
  element			varchar(64) NOT NULL
  
)ENGINE=innodb;

ALTER TABLE llx_element_tag ADD UNIQUE INDEX uk_element_tag (entity, lang, tag, fk_element, element);


CREATE TABLE llx_holiday_config 
(
rowid    integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
name     varchar(255) NOT NULL UNIQUE,
value    text NULL
) 
ENGINE=innodb;

CREATE TABLE llx_holiday_events 
(
rowid    integer NOT NULL PRIMARY KEY AUTO_INCREMENT,
entity   integer DEFAULT 1 NOT NULL,
name     varchar(255) NOT NULL,
value    text NOT NULL
) 
ENGINE=innodb;
ALTER TABLE llx_holiday_events ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_holiday_events ADD UNIQUE INDEX uk_holiday_name (name, entity);

CREATE TABLE llx_holiday_logs 
(
rowid             integer NOT NULL AUTO_INCREMENT PRIMARY KEY ,
date_action       datetime NOT NULL ,
fk_user_action    integer NOT NULL ,
fk_user_update    integer NOT NULL ,
type_action       varchar(255) NOT NULL ,
prev_solde        varchar(255) NOT NULL ,
new_solde         varchar(255) NOT NULL
) 
ENGINE=innodb;

CREATE TABLE llx_holiday_users 
(
fk_user     integer NOT NULL PRIMARY KEY,
nb_holiday  real NOT NULL DEFAULT '0'
) 
ENGINE=innodb;
ALTER TABLE llx_holiday_users MODIFY COLUMN nb_holiday real NOT NULL DEFAULT '0';

CREATE TABLE llx_holiday 
(
rowid          integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
fk_user        integer NOT NULL ,
date_create    datetime NOT NULL ,
description    varchar(255) NOT NULL ,
date_debut     date NOT NULL ,
date_fin       date NOT NULL ,
halfday        integer DEFAULT 0,
statut         integer NOT NULL DEFAULT '1',
fk_validator   integer NOT NULL ,
date_valid     datetime DEFAULT NULL ,
fk_user_valid  integer DEFAULT NULL ,
date_refuse    datetime DEFAULT NULL ,
fk_user_refuse integer DEFAULT NULL ,
date_cancel    datetime DEFAULT NULL ,
fk_user_cancel integer DEFAULT NULL,
detail_refuse  varchar(250) DEFAULT NULL
) 
ENGINE=innodb;
ALTER TABLE llx_holiday ADD COLUMN halfday        integer DEFAULT 0 after date_fin;


ALTER TABLE llx_holiday ADD INDEX idx_holiday_fk_user (fk_user);
ALTER TABLE llx_holiday ADD INDEX idx_holiday_date_debut (date_debut);
ALTER TABLE llx_holiday ADD INDEX idx_holiday_date_fin (date_fin);

INSERT INTO llx_holiday_config (name ,value) VALUES ('userGroup', NULL);
INSERT INTO llx_holiday_config (name ,value) VALUES ('lastUpdate', NULL);
INSERT INTO llx_holiday_config (name ,value) VALUES ('nbUser', NULL);
INSERT INTO llx_holiday_config (name ,value) VALUES ('delayForRequest', '31');
INSERT INTO llx_holiday_config (name ,value) VALUES ('AlertValidatorDelay', '0');
INSERT INTO llx_holiday_config (name ,value) VALUES ('AlertValidatorSolde', '0');
INSERT INTO llx_holiday_config (name ,value) VALUES ('nbHolidayDeducted', '1');
INSERT INTO llx_holiday_config (name ,value) VALUES ('nbHolidayEveryMonth', '2.08334');


insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (80, 'agenda',  'internal', 'ACTOR', 'Responsable', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (81, 'agenda',  'internal', 'GUEST', 'Guest', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (85, 'agenda',  'external', 'ACTOR', 'Responsable', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (86, 'agenda',  'external', 'GUEST', 'Guest', 1);


DELETE FROM llx_document_model WHERE (nom = 'oursin' AND type ='invoice') OR (nom = 'edison' AND type ='order') OR (nom = 'jaune' AND type ='propal');

ALTER TABLE llx_boxes DROP INDEX uk_boxes;
ALTER TABLE llx_boxes ADD COLUMN entity integer NOT NULL DEFAULT 1 AFTER rowid;
ALTER TABLE llx_boxes ADD UNIQUE INDEX uk_boxes (entity, box_id, position, fk_user);
UPDATE llx_boxes as b SET b.entity = (SELECT bd.entity FROM llx_boxes_def as bd WHERE bd.rowid = b.box_id);

-- TASK #204
ALTER TABLE llx_c_tva ADD COLUMN localtax1_type varchar(1) default '0' after localtax1;
ALTER TABLE llx_c_tva ADD COLUMN localtax2_type varchar(1) default '0' after localtax2;
ALTER TABLE llx_c_tva MODIFY COLUMN localtax1_type varchar(1);
ALTER TABLE llx_c_tva MODIFY COLUMN localtax2_type varchar(1);

alter table llx_commande_fournisseurdet add column localtax1_type varchar(1) after localtax1_tx;
alter table llx_commande_fournisseurdet add column localtax2_type varchar(1) after localtax2_tx;
ALTER TABLE llx_commande_fournisseurdet MODIFY COLUMN localtax1_type varchar(1);
ALTER TABLE llx_commande_fournisseurdet MODIFY COLUMN localtax2_type varchar(1);

alter table llx_commandedet add column localtax1_type varchar(1) after localtax1_tx;
alter table llx_commandedet add column localtax2_type varchar(1) after localtax2_tx;
ALTER TABLE llx_commandedet MODIFY COLUMN localtax1_type varchar(1);
ALTER TABLE llx_commandedet MODIFY COLUMN localtax2_type varchar(1);

alter table llx_facture_fourn_det add column localtax1_type varchar(1) after localtax1_tx;
alter table llx_facture_fourn_det add column localtax2_type varchar(1) after localtax2_tx;
ALTER TABLE llx_facture_fourn_det MODIFY COLUMN localtax1_type varchar(1);
ALTER TABLE llx_facture_fourn_det MODIFY COLUMN localtax2_type varchar(1);

alter table llx_facturedet add column localtax1_type varchar(1) after localtax1_tx;
alter table llx_facturedet add column localtax2_type varchar(1) after localtax2_tx;
ALTER TABLE llx_facturedet MODIFY COLUMN localtax1_type varchar(1);
ALTER TABLE llx_facturedet MODIFY COLUMN localtax2_type varchar(1);

alter table llx_facturedet_rec add column localtax1_type varchar(1) after localtax1_tx;
alter table llx_facturedet_rec add column localtax2_type varchar(1) after localtax2_tx;
ALTER TABLE llx_facturedet_rec MODIFY COLUMN localtax1_type varchar(1);
ALTER TABLE llx_facturedet_rec MODIFY COLUMN localtax2_type varchar(1);

alter table llx_propaldet add column localtax1_type varchar(1) after localtax1_tx;
alter table llx_propaldet add column localtax2_type varchar(1) after localtax2_tx;
ALTER TABLE llx_propaldet MODIFY COLUMN localtax1_type varchar(1);
ALTER TABLE llx_propaldet MODIFY COLUMN localtax2_type varchar(1);

alter table llx_contratdet add column localtax1_type varchar(1) after localtax1_tx;
alter table llx_contratdet add column localtax2_type varchar(1) after localtax2_tx;
ALTER TABLE llx_contratdet MODIFY COLUMN localtax1_type varchar(1);
ALTER TABLE llx_contratdet MODIFY COLUMN localtax2_type varchar(1);
-- END TASK #204

ALTER TABLE llx_menu MODIFY COLUMN enabled varchar(255) NULL DEFAULT '1';

ALTER TABLE llx_extrafields ADD COLUMN fieldunique INTEGER DEFAULT 0;
ALTER TABLE llx_extrafields ADD COLUMN fieldrequired INTEGER DEFAULT 0;

create table llx_socpeople_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                                 -- import key
) ENGINE=innodb;
ALTER TABLE llx_socpeople_extrafields ADD INDEX idx_socpeople_extrafields (fk_object);

create table llx_actioncomm_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;
ALTER TABLE llx_actioncomm_extrafields ADD INDEX idx_actioncomm_extrafields (fk_object);




UPDATE llx_c_actioncomm set type = 'systemauto' where code IN ('AC_PROP','AC_COM','AC_FAC','AC_SHIP','AC_SUP_ORD','AC_SUP_INV');


-- update type of localtax1 for spain
UPDATE llx_c_tva SET taux='21', localtax1 = '5.2', localtax1_type = '3' WHERE rowid = 41 AND fk_pays = 4 AND (localtax1_type = '0' OR localtax1_type='1' OR localtax1_type='3');
UPDATE llx_c_tva SET taux='10', localtax1 = '1.4', localtax1_type = '3' WHERE rowid = 42 AND fk_pays = 4 AND (localtax1_type = '0' OR localtax1_type='1' OR localtax1_type='3');
UPDATE llx_c_tva SET taux='4',  localtax1 = '0.5', localtax1_type = '3' WHERE rowid = 43 AND fk_pays = 4 AND (localtax1_type = '0' OR localtax1_type='1' OR localtax1_type='3');

-- update type of localtax2 for spain
UPDATE llx_c_tva SET localtax2 = '-15', localtax2_type = '1' WHERE rowid = 41 AND fk_pays = 4 AND (localtax2_type = '0' OR localtax2_type = '1');
UPDATE llx_c_tva SET localtax2 = '-15', localtax2_type = '1' WHERE rowid = 42 AND fk_pays = 4 AND (localtax2_type = '0' OR localtax2_type = '1');
UPDATE llx_c_tva SET localtax2 = '-15', localtax2_type = '1' WHERE rowid = 43 AND fk_pays = 4 AND (localtax2_type = '0' OR localtax2_type = '1');

-- update type of localtax for tunisia
UPDATE llx_c_tva set localtax1 = 1, localtax1_type = '4', localtax2 = 0.4, localtax2_type = '7' where rowid= 101 and fk_pays= 10 AND localtax1_type='0';
UPDATE llx_c_tva set localtax1 = 1, localtax1_type = '4', localtax2 = 0.4, localtax2_type = '7' where rowid= 102 and fk_pays= 10 AND localtax1_type='0';
UPDATE llx_c_tva set localtax1 = 1, localtax1_type = '4', localtax2 = 0.4, localtax2_type = '7' where rowid= 103 and fk_pays= 10 AND localtax1_type='0';
UPDATE llx_c_tva set localtax1 = 1, localtax1_type = '4', localtax2 = 0.4, localtax2_type = '7' where rowid= 104 and fk_pays= 10 AND localtax1_type='0';
UPDATE llx_c_tva set localtax1 = 1, localtax1_type = '4', localtax2 = 0.4, localtax2_type = '7' where rowid= 105 and fk_pays= 10 AND localtax1_type='0';
UPDATE llx_c_tva set localtax1 = 1, localtax1_type = '4', localtax2 = 0.4, localtax2_type = '7' where rowid= 106 and fk_pays= 10 AND localtax1_type='0';
UPDATE llx_c_tva set localtax1 = 1, localtax1_type = '4', localtax2 = 0.4, localtax2_type = '7' where rowid= 107 and fk_pays= 10 AND localtax1_type='0';

-- Modify table for accountancy
ALTER TABLE llx_c_tva DROP COLUMN accountancy_code;
ALTER TABLE llx_c_tva ADD COLUMN accountancy_code_sell varchar(15) DEFAULT NULL AFTER active;
ALTER TABLE llx_c_tva ADD COLUMN accountancy_code_buy varchar(15) DEFAULT NULL AFTER accountancy_code_sell;
ALTER TABLE llx_c_chargesociales ADD COLUMN accountancy_code varchar(15) DEFAULT NULL AFTER code;

-- Tables for accountancy expert
DROP TABLE llx_accountingaccount;
DROP TABLE llx_accounting_account;
DROP TABLE llx_accountingsystem;
DROP TABLE llx_accounting_system;

create table llx_accounting_system
(
  rowid             integer         AUTO_INCREMENT PRIMARY KEY,
  pcg_version       varchar(12)     NOT NULL,
  fk_pays           integer         NOT NULL,
  label             varchar(128)    NOT NULL,
  active            smallint        DEFAULT 0
)ENGINE=innodb;

ALTER TABLE llx_accounting_system ADD UNIQUE INDEX uk_accounting_system_pcg_version (pcg_version);

create table llx_accountingaccount
(
  rowid           integer      AUTO_INCREMENT PRIMARY KEY,
  fk_pcg_version  varchar(12)  NOT NULL,
  pcg_type        varchar(20)  NOT NULL,
  pcg_subtype     varchar(20)  NOT NULL,
  account_number  varchar(20)  NOT NULL,
  account_parent  varchar(20),
  label           varchar(128) NOT NULL,
  active     	  tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_accountingaccount ADD INDEX idx_accountingaccount_fk_pcg_version (fk_pcg_version);
ALTER TABLE llx_accountingaccount ADD CONSTRAINT fk_accountingaccount_fk_pcg_version FOREIGN KEY (fk_pcg_version) REFERENCES llx_accounting_system (pcg_version);


-- Data for accountancy expert

insert into llx_accounting_system (rowid, pcg_version, fk_pays, label, active) VALUES (1,'PCG99-ABREGE', 1, 'The simple accountancy french plan', 1);

insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  1,'PCG99-ABREGE','CAPIT', 'CAPITAL', '101', '1', 'Capital', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  2,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '105', '1', 'Ecarts de réévaluation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  3,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1061', '1', 'Réserve légale', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  4,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1063', '1', 'Réserves statutaires ou contractuelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  5,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1064', '1', 'Réserves réglementées', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  6,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1068', '1', 'Autres réserves', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  7,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '108', '1', 'Compte de l''exploitant', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  8,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '12', '1', 'Résultat de l''exercice', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (  9,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '145', '1', 'Amortissements dérogatoires', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 10,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '146', '1', 'Provision spéciale de réévaluation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 11,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '147', '1', 'Plus-values réinvesties', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 12,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '148', '1', 'Autres provisions réglementées', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 13,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '15', '1', 'Provisions pour risques et charges', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 14,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '16', '1', 'Emprunts et dettes assimilees', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 15,'PCG99-ABREGE','IMMO',  'XXXXXX',   '20', '2', 'Immobilisations incorporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 16,'PCG99-ABREGE','IMMO',  'XXXXXX',  '201','20', 'Frais d''établissement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 17,'PCG99-ABREGE','IMMO',  'XXXXXX',  '206','20', 'Droit au bail', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 18,'PCG99-ABREGE','IMMO',  'XXXXXX',  '207','20', 'Fonds commercial', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 19,'PCG99-ABREGE','IMMO',  'XXXXXX',  '208','20', 'Autres immobilisations incorporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 20,'PCG99-ABREGE','IMMO',  'XXXXXX',   '21', '2', 'Immobilisations corporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 21,'PCG99-ABREGE','IMMO',  'XXXXXX',   '23', '2', 'Immobilisations en cours', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 22,'PCG99-ABREGE','IMMO',  'XXXXXX',   '27', '2', 'Autres immobilisations financieres', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 23,'PCG99-ABREGE','IMMO',  'XXXXXX',  '280', '2', 'Amortissements des immobilisations incorporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 24,'PCG99-ABREGE','IMMO',  'XXXXXX',  '281', '2', 'Amortissements des immobilisations corporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 25,'PCG99-ABREGE','IMMO',  'XXXXXX',  '290', '2', 'Provisions pour dépréciation des immobilisations incorporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 26,'PCG99-ABREGE','IMMO',  'XXXXXX',  '291', '2', 'Provisions pour dépréciation des immobilisations corporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 27,'PCG99-ABREGE','IMMO',  'XXXXXX',  '297', '2', 'Provisions pour dépréciation des autres immobilisations financières', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 28,'PCG99-ABREGE','STOCK', 'XXXXXX',   '31', '3', 'Matieres premières', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 29,'PCG99-ABREGE','STOCK', 'XXXXXX',   '32', '3', 'Autres approvisionnements', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 30,'PCG99-ABREGE','STOCK', 'XXXXXX',   '33', '3', 'En-cours de production de biens', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 31,'PCG99-ABREGE','STOCK', 'XXXXXX',   '34', '3', 'En-cours de production de services', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 32,'PCG99-ABREGE','STOCK', 'XXXXXX',   '35', '3', 'Stocks de produits', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 33,'PCG99-ABREGE','STOCK', 'XXXXXX',   '37', '3', 'Stocks de marchandises', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 34,'PCG99-ABREGE','STOCK', 'XXXXXX',  '391', '3', 'Provisions pour dépréciation des matières premières', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 35,'PCG99-ABREGE','STOCK', 'XXXXXX',  '392', '3', 'Provisions pour dépréciation des autres approvisionnements', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 36,'PCG99-ABREGE','STOCK', 'XXXXXX',  '393', '3', 'Provisions pour dépréciation des en-cours de production de biens', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 37,'PCG99-ABREGE','STOCK', 'XXXXXX',  '394', '3', 'Provisions pour dépréciation des en-cours de production de services', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 38,'PCG99-ABREGE','STOCK', 'XXXXXX',  '395', '3', 'Provisions pour dépréciation des stocks de produits', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 39,'PCG99-ABREGE','STOCK', 'XXXXXX',  '397', '3', 'Provisions pour dépréciation des stocks de marchandises', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 40,'PCG99-ABREGE','TIERS', 'SUPPLIER','400', '4', 'Fournisseurs et Comptes rattachés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 41,'PCG99-ABREGE','TIERS', 'XXXXXX',  '409', '4', 'Fournisseurs débiteurs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 42,'PCG99-ABREGE','TIERS', 'CUSTOMER','410', '4', 'Clients et Comptes rattachés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 43,'PCG99-ABREGE','TIERS', 'XXXXXX',  '419', '4', 'Clients créditeurs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 44,'PCG99-ABREGE','TIERS', 'XXXXXX',  '421', '4', 'Personnel', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 45,'PCG99-ABREGE','TIERS', 'XXXXXX',  '428', '4', 'Personnel', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 46,'PCG99-ABREGE','TIERS', 'XXXXXX',   '43', '4', 'Sécurité sociale et autres organismes sociaux', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 47,'PCG99-ABREGE','TIERS', 'XXXXXX',  '444', '4', 'Etat - impôts sur bénéfice', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 48,'PCG99-ABREGE','TIERS', 'XXXXXX',  '445', '4', 'Etat - Taxes sur chiffre affaires', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 49,'PCG99-ABREGE','TIERS', 'XXXXXX',  '447', '4', 'Autres impôts, taxes et versements assimilés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 50,'PCG99-ABREGE','TIERS', 'XXXXXX',   '45', '4', 'Groupe et associes', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 51,'PCG99-ABREGE','TIERS', 'XXXXXX',  '455','45', 'Associés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 52,'PCG99-ABREGE','TIERS', 'XXXXXX',   '46', '4', 'Débiteurs divers et créditeurs divers', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 53,'PCG99-ABREGE','TIERS', 'XXXXXX',   '47', '4', 'Comptes transitoires ou d''attente', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 54,'PCG99-ABREGE','TIERS', 'XXXXXX',  '481', '4', 'Charges à répartir sur plusieurs exercices', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 55,'PCG99-ABREGE','TIERS', 'XXXXXX',  '486', '4', 'Charges constatées d''avance', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 56,'PCG99-ABREGE','TIERS', 'XXXXXX',  '487', '4', 'Produits constatés d''avance', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 57,'PCG99-ABREGE','TIERS', 'XXXXXX',  '491', '4', 'Provisions pour dépréciation des comptes de clients', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 58,'PCG99-ABREGE','TIERS', 'XXXXXX',  '496', '4', 'Provisions pour dépréciation des comptes de débiteurs divers', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 59,'PCG99-ABREGE','FINAN', 'XXXXXX',   '50', '5', 'Valeurs mobilières de placement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 60,'PCG99-ABREGE','FINAN', 'BANK',     '51', '5', 'Banques, établissements financiers et assimilés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 61,'PCG99-ABREGE','FINAN', 'CASH',     '53', '5', 'Caisse', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 62,'PCG99-ABREGE','FINAN', 'XXXXXX',   '54', '5', 'Régies d''avance et accréditifs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 63,'PCG99-ABREGE','FINAN', 'XXXXXX',   '58', '5', 'Virements internes', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 64,'PCG99-ABREGE','FINAN', 'XXXXXX',  '590', '5', 'Provisions pour dépréciation des valeurs mobilières de placement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 65,'PCG99-ABREGE','CHARGE','PRODUCT',  '60', '6', 'Achats', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 66,'PCG99-ABREGE','CHARGE','XXXXXX',  '603','60', 'Variations des stocks', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 67,'PCG99-ABREGE','CHARGE','SERVICE',  '61', '6', 'Services extérieurs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 68,'PCG99-ABREGE','CHARGE','XXXXXX',   '62', '6', 'Autres services extérieurs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 69,'PCG99-ABREGE','CHARGE','XXXXXX',   '63', '6', 'Impôts, taxes et versements assimiles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 70,'PCG99-ABREGE','CHARGE','XXXXXX',  '641', '6', 'Rémunérations du personnel', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 71,'PCG99-ABREGE','CHARGE','XXXXXX',  '644', '6', 'Rémunération du travail de l''exploitant', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 72,'PCG99-ABREGE','CHARGE','SOCIAL',  '645', '6', 'Charges de sécurité sociale et de prévoyance', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 73,'PCG99-ABREGE','CHARGE','XXXXXX',  '646', '6', 'Cotisations sociales personnelles de l''exploitant', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 74,'PCG99-ABREGE','CHARGE','XXXXXX',   '65', '6', 'Autres charges de gestion courante', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 75,'PCG99-ABREGE','CHARGE','XXXXXX',   '66', '6', 'Charges financières', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 76,'PCG99-ABREGE','CHARGE','XXXXXX',   '67', '6', 'Charges exceptionnelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 77,'PCG99-ABREGE','CHARGE','XXXXXX',  '681', '6', 'Dotations aux amortissements et aux provisions', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 78,'PCG99-ABREGE','CHARGE','XXXXXX',  '686', '6', 'Dotations aux amortissements et aux provisions', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 79,'PCG99-ABREGE','CHARGE','XXXXXX',  '687', '6', 'Dotations aux amortissements et aux provisions', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 80,'PCG99-ABREGE','CHARGE','XXXXXX',  '691', '6', 'Participation des salariés aux résultats', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 81,'PCG99-ABREGE','CHARGE','XXXXXX',  '695', '6', 'Impôts sur les bénéfices', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 82,'PCG99-ABREGE','CHARGE','XXXXXX',  '697', '6', 'Imposition forfaitaire annuelle des sociétés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 83,'PCG99-ABREGE','CHARGE','XXXXXX',  '699', '6', 'Produits', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 84,'PCG99-ABREGE','PROD',  'PRODUCT', '701', '7', 'Ventes de produits finis', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 85,'PCG99-ABREGE','PROD',  'SERVICE', '706', '7', 'Prestations de services', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 86,'PCG99-ABREGE','PROD',  'PRODUCT', '707', '7', 'Ventes de marchandises', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 87,'PCG99-ABREGE','PROD',  'PRODUCT', '708', '7', 'Produits des activités annexes', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 88,'PCG99-ABREGE','PROD',  'XXXXXX',  '709', '7', 'Rabais, remises et ristournes accordés par l''entreprise', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 89,'PCG99-ABREGE','PROD',  'XXXXXX',  '713', '7', 'Variation des stocks', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 90,'PCG99-ABREGE','PROD',  'XXXXXX',   '72', '7', 'Production immobilisée', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 91,'PCG99-ABREGE','PROD',  'XXXXXX',   '73', '7', 'Produits nets partiels sur opérations à long terme', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 92,'PCG99-ABREGE','PROD',  'XXXXXX',   '74', '7', 'Subventions d''exploitation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 93,'PCG99-ABREGE','PROD',  'XXXXXX',   '75', '7', 'Autres produits de gestion courante', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 94,'PCG99-ABREGE','PROD',  'XXXXXX',  '753','75', 'Jetons de présence et rémunérations d''administrateurs, gérants,...', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 95,'PCG99-ABREGE','PROD',  'XXXXXX',  '754','75', 'Ristournes perçues des coopératives', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 96,'PCG99-ABREGE','PROD',  'XXXXXX',  '755','75', 'Quotes-parts de résultat sur opérations faites en commun', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 97,'PCG99-ABREGE','PROD',  'XXXXXX',   '76', '7', 'Produits financiers', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 98,'PCG99-ABREGE','PROD',  'XXXXXX',   '77', '7', 'Produits exceptionnels', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES ( 99,'PCG99-ABREGE','PROD',  'XXXXXX',  '781', '7', 'Reprises sur amortissements et provisions', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (100,'PCG99-ABREGE','PROD',  'XXXXXX',  '786', '7', 'Reprises sur provisions pour risques', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (101,'PCG99-ABREGE','PROD',  'XXXXXX',  '787', '7', 'Reprises sur provisions', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (102,'PCG99-ABREGE','PROD',  'XXXXXX',   '79', '7', 'Transferts de charges', '1');

insert into llx_accounting_system (rowid, pcg_version, fk_pays, label, active) VALUES (2,'PCG99-BASE', 1, 'The base accountancy french plan', 1);

insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (103,'PCG99-BASE','CAPIT', 'XXXXXX',   '10',  '1', 'Capital  et réserves', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (104,'PCG99-BASE','CAPIT', 'CAPITAL', '101', '10', 'Capital', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (105,'PCG99-BASE','CAPIT', 'XXXXXX',  '104', '10', 'Primes liées au capital social', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (106,'PCG99-BASE','CAPIT', 'XXXXXX',  '105', '10', 'Ecarts de réévaluation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (107,'PCG99-BASE','CAPIT', 'XXXXXX',  '106', '10', 'Réserves', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (108,'PCG99-BASE','CAPIT', 'XXXXXX',  '107', '10', 'Ecart d''equivalence', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (109,'PCG99-BASE','CAPIT', 'XXXXXX',  '108', '10', 'Compte de l''exploitant', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (110,'PCG99-BASE','CAPIT', 'XXXXXX',  '109', '10', 'Actionnaires : capital souscrit - non appelé', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (111,'PCG99-BASE','CAPIT', 'XXXXXX',   '11',  '1', 'Report à nouveau (solde créditeur ou débiteur)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (112,'PCG99-BASE','CAPIT', 'XXXXXX',  '110', '11', 'Report à nouveau (solde créditeur)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (113,'PCG99-BASE','CAPIT', 'XXXXXX',  '119', '11', 'Report à nouveau (solde débiteur)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (114,'PCG99-BASE','CAPIT', 'XXXXXX',   '12',  '1', 'Résultat de l''exercice (bénéfice ou perte)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (115,'PCG99-BASE','CAPIT', 'XXXXXX',  '120', '12', 'Résultat de l''exercice (bénéfice)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (116,'PCG99-BASE','CAPIT', 'XXXXXX',  '129', '12', 'Résultat de l''exercice (perte)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (117,'PCG99-BASE','CAPIT', 'XXXXXX',   '13',  '1', 'Subventions d''investissement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (118,'PCG99-BASE','CAPIT', 'XXXXXX',  '131', '13', 'Subventions d''équipement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (119,'PCG99-BASE','CAPIT', 'XXXXXX',  '138', '13', 'Autres subventions d''investissement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (120,'PCG99-BASE','CAPIT', 'XXXXXX',  '139', '13', 'Subventions d''investissement inscrites au compte de résultat', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (121,'PCG99-BASE','CAPIT', 'XXXXXX',   '14',  '1', 'Provisions réglementées', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (122,'PCG99-BASE','CAPIT', 'XXXXXX',  '142', '14', 'Provisions réglementées relatives aux immobilisations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (123,'PCG99-BASE','CAPIT', 'XXXXXX',  '143', '14', 'Provisions réglementées relatives aux stocks', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (124,'PCG99-BASE','CAPIT', 'XXXXXX',  '144', '14', 'Provisions réglementées relatives aux autres éléments de l''actif', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (125,'PCG99-BASE','CAPIT', 'XXXXXX',  '145', '14', 'Amortissements dérogatoires', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (126,'PCG99-BASE','CAPIT', 'XXXXXX',  '146', '14', 'Provision spéciale de réévaluation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (127,'PCG99-BASE','CAPIT', 'XXXXXX',  '147', '14', 'Plus-values réinvesties', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (128,'PCG99-BASE','CAPIT', 'XXXXXX',  '148', '14', 'Autres provisions réglementées', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (129,'PCG99-BASE','CAPIT', 'XXXXXX',   '15',  '1', 'Provisions pour risques et charges', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (130,'PCG99-BASE','CAPIT', 'XXXXXX',  '151', '15', 'Provisions pour risques', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (131,'PCG99-BASE','CAPIT', 'XXXXXX',  '153', '15', 'Provisions pour pensions et obligations similaires', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (132,'PCG99-BASE','CAPIT', 'XXXXXX',  '154', '15', 'Provisions pour restructurations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (133,'PCG99-BASE','CAPIT', 'XXXXXX',  '155', '15', 'Provisions pour impôts', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (134,'PCG99-BASE','CAPIT', 'XXXXXX',  '156', '15', 'Provisions pour renouvellement des immobilisations (entreprises concessionnaires)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (135,'PCG99-BASE','CAPIT', 'XXXXXX',  '157', '15', 'Provisions pour charges à répartir sur plusieurs exercices', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (136,'PCG99-BASE','CAPIT', 'XXXXXX',  '158', '15', 'Autres provisions pour charges', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (137,'PCG99-BASE','CAPIT', 'XXXXXX',   '16',  '1', 'Emprunts et dettes assimilees', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (138,'PCG99-BASE','CAPIT', 'XXXXXX',  '161', '16', 'Emprunts obligataires convertibles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (139,'PCG99-BASE','CAPIT', 'XXXXXX',  '163', '16', 'Autres emprunts obligataires', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (140,'PCG99-BASE','CAPIT', 'XXXXXX',  '164', '16', 'Emprunts auprès des établissements de crédit', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (141,'PCG99-BASE','CAPIT', 'XXXXXX',  '165', '16', 'Dépôts et cautionnements reçus', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (142,'PCG99-BASE','CAPIT', 'XXXXXX',  '166', '16', 'Participation des salariés aux résultats', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (143,'PCG99-BASE','CAPIT', 'XXXXXX',  '167', '16', 'Emprunts et dettes assortis de conditions particulières', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (144,'PCG99-BASE','CAPIT', 'XXXXXX',  '168', '16', 'Autres emprunts et dettes assimilées', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (145,'PCG99-BASE','CAPIT', 'XXXXXX',  '169', '16', 'Primes de remboursement des obligations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (146,'PCG99-BASE','CAPIT', 'XXXXXX',   '17',  '1', 'Dettes rattachées à des participations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (147,'PCG99-BASE','CAPIT', 'XXXXXX',  '171', '17', 'Dettes rattachées à des participations (groupe)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (148,'PCG99-BASE','CAPIT', 'XXXXXX',  '174', '17', 'Dettes rattachées à des participations (hors groupe)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (149,'PCG99-BASE','CAPIT', 'XXXXXX',  '178', '17', 'Dettes rattachées à des sociétés en participation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (150,'PCG99-BASE','CAPIT', 'XXXXXX',   '18',  '1', 'Comptes de liaison des établissements et sociétés en participation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (151,'PCG99-BASE','CAPIT', 'XXXXXX',  '181', '18', 'Comptes de liaison des établissements', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (152,'PCG99-BASE','CAPIT', 'XXXXXX',  '186', '18', 'Biens et prestations de services échangés entre établissements (charges)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (153,'PCG99-BASE','CAPIT', 'XXXXXX',  '187', '18', 'Biens et prestations de services échangés entre établissements (produits)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (154,'PCG99-BASE','CAPIT', 'XXXXXX',  '188', '18', 'Comptes de liaison des sociétés en participation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (155,'PCG99-BASE','IMMO',  'XXXXXX',   '20',  '2', 'Immobilisations incorporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (156,'PCG99-BASE','IMMO',  'XXXXXX',  '201', '20', 'Frais d''établissement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (157,'PCG99-BASE','IMMO',  'XXXXXX',  '203', '20', 'Frais de recherche et de développement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (158,'PCG99-BASE','IMMO',  'XXXXXX',  '205', '20', 'Concessions et droits similaires, brevets, licences, marques, procédés, logiciels, droits et valeurs similaires', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (159,'PCG99-BASE','IMMO',  'XXXXXX',  '206', '20', 'Droit au bail', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (160,'PCG99-BASE','IMMO',  'XXXXXX',  '207', '20', 'Fonds commercial', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (161,'PCG99-BASE','IMMO',  'XXXXXX',  '208', '20', 'Autres immobilisations incorporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (162,'PCG99-BASE','IMMO',  'XXXXXX',   '21',  '2', 'Immobilisations corporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (163,'PCG99-BASE','IMMO',  'XXXXXX',  '211', '21', 'Terrains', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (164,'PCG99-BASE','IMMO',  'XXXXXX',  '212', '21', 'Agencements et aménagements de terrains', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (165,'PCG99-BASE','IMMO',  'XXXXXX',  '213', '21', 'Constructions', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (166,'PCG99-BASE','IMMO',  'XXXXXX',  '214', '21', 'Constructions sur sol d''autrui', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (167,'PCG99-BASE','IMMO',  'XXXXXX',  '215', '21', 'Installations techniques, matériels et outillage industriels', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (168,'PCG99-BASE','IMMO',  'XXXXXX',  '218', '21', 'Autres immobilisations corporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (169,'PCG99-BASE','IMMO',  'XXXXXX',   '22',  '2', 'Immobilisations mises en concession', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (170,'PCG99-BASE','IMMO',  'XXXXXX',   '23',  '2', 'Immobilisations en cours', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (171,'PCG99-BASE','IMMO',  'XXXXXX',  '231', '23', 'Immobilisations corporelles en cours', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (172,'PCG99-BASE','IMMO',  'XXXXXX',  '232', '23', 'Immobilisations incorporelles en cours', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (173,'PCG99-BASE','IMMO',  'XXXXXX',  '237', '23', 'Avances et acomptes versés sur immobilisations incorporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (174,'PCG99-BASE','IMMO',  'XXXXXX',  '238', '23', 'Avances et acomptes versés sur commandes d''immobilisations corporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (175,'PCG99-BASE','IMMO',  'XXXXXX',   '25',  '2', 'Parts dans des entreprises liées et créances sur des entreprises liées', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (176,'PCG99-BASE','IMMO',  'XXXXXX',   '26',  '2', 'Participations et créances rattachées à des participations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (177,'PCG99-BASE','IMMO',  'XXXXXX',  '261', '26', 'Titres de participation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (178,'PCG99-BASE','IMMO',  'XXXXXX',  '266', '26', 'Autres formes de participation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (179,'PCG99-BASE','IMMO',  'XXXXXX',  '267', '26', 'Créances rattachées à des participations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (180,'PCG99-BASE','IMMO',  'XXXXXX',  '268', '26', 'Créances rattachées à des sociétés en participation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (181,'PCG99-BASE','IMMO',  'XXXXXX',  '269', '26', 'Versements restant à effectuer sur titres de participation non libérés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (182,'PCG99-BASE','IMMO',  'XXXXXX',   '27',  '2', 'Autres immobilisations financieres', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (183,'PCG99-BASE','IMMO',  'XXXXXX',  '271', '27', 'Titres immobilisés autres que les titres immobilisés de l''activité de portefeuille (droit de propriété)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (184,'PCG99-BASE','IMMO',  'XXXXXX',  '272', '27', 'Titres immobilisés (droit de créance)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (185,'PCG99-BASE','IMMO',  'XXXXXX',  '273', '27', 'Titres immobilisés de l''activité de portefeuille', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (186,'PCG99-BASE','IMMO',  'XXXXXX',  '274', '27', 'Prêts', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (187,'PCG99-BASE','IMMO',  'XXXXXX',  '275', '27', 'Dépôts et cautionnements versés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (188,'PCG99-BASE','IMMO',  'XXXXXX',  '276', '27', 'Autres créances immobilisées', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (189,'PCG99-BASE','IMMO',  'XXXXXX',  '277', '27', '(Actions propres ou parts propres)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (190,'PCG99-BASE','IMMO',  'XXXXXX',  '279', '27', 'Versements restant à effectuer sur titres immobilisés non libérés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (191,'PCG99-BASE','IMMO',  'XXXXXX',   '28',  '2', 'Amortissements des immobilisations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (192,'PCG99-BASE','IMMO',  'XXXXXX',  '280', '28', 'Amortissements des immobilisations incorporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (193,'PCG99-BASE','IMMO',  'XXXXXX',  '281', '28', 'Amortissements des immobilisations corporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (194,'PCG99-BASE','IMMO',  'XXXXXX',  '282', '28', 'Amortissements des immobilisations mises en concession', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (195,'PCG99-BASE','IMMO',  'XXXXXX',   '29',  '2', 'Dépréciations des immobilisations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (196,'PCG99-BASE','IMMO',  'XXXXXX',  '290', '29', 'Dépréciations des immobilisations incorporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (197,'PCG99-BASE','IMMO',  'XXXXXX',  '291', '29', 'Dépréciations des immobilisations corporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (198,'PCG99-BASE','IMMO',  'XXXXXX',  '292', '29', 'Dépréciations des immobilisations mises en concession', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (199,'PCG99-BASE','IMMO',  'XXXXXX',  '293', '29', 'Dépréciations des immobilisations en cours', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (200,'PCG99-BASE','IMMO',  'XXXXXX',  '296', '29', 'Provisions pour dépréciation des participations et créances rattachées à des participations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (201,'PCG99-BASE','IMMO',  'XXXXXX',  '297', '29', 'Provisions pour dépréciation des autres immobilisations financières', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (202,'PCG99-BASE','STOCK', 'XXXXXX',   '31',  '3', 'Matières premières (et fournitures)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (203,'PCG99-BASE','STOCK', 'XXXXXX',  '311', '31', 'Matières (ou groupe) A', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (204,'PCG99-BASE','STOCK', 'XXXXXX',  '312', '31', 'Matières (ou groupe) B', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (205,'PCG99-BASE','STOCK', 'XXXXXX',  '317', '31', 'Fournitures A, B, C,', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (206,'PCG99-BASE','STOCK', 'XXXXXX',   '32',  '3', 'Autres approvisionnements', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (207,'PCG99-BASE','STOCK', 'XXXXXX',  '321', '32', 'Matières consommables', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (208,'PCG99-BASE','STOCK', 'XXXXXX',  '322', '32', 'Fournitures consommables', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (209,'PCG99-BASE','STOCK', 'XXXXXX',  '326', '32', 'Emballages', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (210,'PCG99-BASE','STOCK', 'XXXXXX',   '33',  '3', 'En-cours de production de biens', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (211,'PCG99-BASE','STOCK', 'XXXXXX',  '331', '33', 'Produits en cours', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (212,'PCG99-BASE','STOCK', 'XXXXXX',  '335', '33', 'Travaux en cours', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (213,'PCG99-BASE','STOCK', 'XXXXXX',   '34',  '3', 'En-cours de production de services', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (214,'PCG99-BASE','STOCK', 'XXXXXX',  '341', '34', 'Etudes en cours', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (215,'PCG99-BASE','STOCK', 'XXXXXX',  '345', '34', 'Prestations de services en cours', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (216,'PCG99-BASE','STOCK', 'XXXXXX',   '35',  '3', 'Stocks de produits', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (217,'PCG99-BASE','STOCK', 'XXXXXX',  '351', '35', 'Produits intermédiaires', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (218,'PCG99-BASE','STOCK', 'XXXXXX',  '355', '35', 'Produits finis', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (219,'PCG99-BASE','STOCK', 'XXXXXX',  '358', '35', 'Produits résiduels (ou matières de récupération)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (220,'PCG99-BASE','STOCK', 'XXXXXX',   '37',  '3', 'Stocks de marchandises', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (221,'PCG99-BASE','STOCK', 'XXXXXX',  '371', '37', 'Marchandises (ou groupe) A', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (222,'PCG99-BASE','STOCK', 'XXXXXX',  '372', '37', 'Marchandises (ou groupe) B', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (223,'PCG99-BASE','STOCK', 'XXXXXX',   '39',  '3', 'Provisions pour dépréciation des stocks et en-cours', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (224,'PCG99-BASE','STOCK', 'XXXXXX',  '391', '39', 'Provisions pour dépréciation des matières premières', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (225,'PCG99-BASE','STOCK', 'XXXXXX',  '392', '39', 'Provisions pour dépréciation des autres approvisionnements', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (226,'PCG99-BASE','STOCK', 'XXXXXX',  '393', '39', 'Provisions pour dépréciation des en-cours de production de biens', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (227,'PCG99-BASE','STOCK', 'XXXXXX',  '394', '39', 'Provisions pour dépréciation des en-cours de production de services', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (228,'PCG99-BASE','STOCK', 'XXXXXX',  '395', '39', 'Provisions pour dépréciation des stocks de produits', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (229,'PCG99-BASE','STOCK', 'XXXXXX',  '397', '39', 'Provisions pour dépréciation des stocks de marchandises', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (230,'PCG99-BASE','TIERS', 'XXXXXX',   '40',  '4', 'Fournisseurs et Comptes rattachés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (231,'PCG99-BASE','TIERS', 'XXXXXX',  '400', '40', 'Fournisseurs et Comptes rattachés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (232,'PCG99-BASE','TIERS', 'SUPPLIER','401', '40', 'Fournisseurs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (233,'PCG99-BASE','TIERS', 'XXXXXX',  '403', '40', 'Fournisseurs - Effets à payer', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (234,'PCG99-BASE','TIERS', 'XXXXXX',  '404', '40', 'Fournisseurs d''immobilisations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (235,'PCG99-BASE','TIERS', 'XXXXXX',  '405', '40', 'Fournisseurs d''immobilisations - Effets à payer', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (236,'PCG99-BASE','TIERS', 'XXXXXX',  '408', '40', 'Fournisseurs - Factures non parvenues', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (237,'PCG99-BASE','TIERS', 'XXXXXX',  '409', '40', 'Fournisseurs débiteurs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (238,'PCG99-BASE','TIERS', 'XXXXXX',   '41',  '4', 'Clients et comptes rattachés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (239,'PCG99-BASE','TIERS', 'XXXXXX',  '410', '41', 'Clients et Comptes rattachés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (240,'PCG99-BASE','TIERS', 'CUSTOMER','411', '41', 'Clients', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (241,'PCG99-BASE','TIERS', 'XXXXXX',  '413', '41', 'Clients - Effets à recevoir', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (242,'PCG99-BASE','TIERS', 'XXXXXX',  '416', '41', 'Clients douteux ou litigieux', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (243,'PCG99-BASE','TIERS', 'XXXXXX',  '418', '41', 'Clients - Produits non encore facturés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (244,'PCG99-BASE','TIERS', 'XXXXXX',  '419', '41', 'Clients créditeurs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (245,'PCG99-BASE','TIERS', 'XXXXXX',   '42',  '4', 'Personnel et comptes rattachés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (246,'PCG99-BASE','TIERS', 'XXXXXX',  '421', '42', 'Personnel - Rémunérations dues', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (247,'PCG99-BASE','TIERS', 'XXXXXX',  '422', '42', 'Comités d''entreprises, d''établissement, ...', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (248,'PCG99-BASE','TIERS', 'XXXXXX',  '424', '42', 'Participation des salariés aux résultats', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (249,'PCG99-BASE','TIERS', 'XXXXXX',  '425', '42', 'Personnel - Avances et acomptes', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (250,'PCG99-BASE','TIERS', 'XXXXXX',  '426', '42', 'Personnel - Dépôts', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (251,'PCG99-BASE','TIERS', 'XXXXXX',  '427', '42', 'Personnel - Oppositions', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (252,'PCG99-BASE','TIERS', 'XXXXXX',  '428', '42', 'Personnel - Charges à payer et produits à recevoir', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (253,'PCG99-BASE','TIERS', 'XXXXXX',   '43',  '4', 'Sécurité sociale et autres organismes sociaux', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (254,'PCG99-BASE','TIERS', 'XXXXXX',  '431', '43', 'Sécurité sociale', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (255,'PCG99-BASE','TIERS', 'XXXXXX',  '437', '43', 'Autres organismes sociaux', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (256,'PCG99-BASE','TIERS', 'XXXXXX',  '438', '43', 'Organismes sociaux - Charges à payer et produits à recevoir', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (257,'PCG99-BASE','TIERS', 'XXXXXX',   '44',  '4', 'État et autres collectivités publiques', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (258,'PCG99-BASE','TIERS', 'XXXXXX',  '441', '44', 'État - Subventions à recevoir', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (259,'PCG99-BASE','TIERS', 'XXXXXX',  '442', '44', 'Etat - Impôts et taxes recouvrables sur des tiers', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (260,'PCG99-BASE','TIERS', 'XXXXXX',  '443', '44', 'Opérations particulières avec l''Etat, les collectivités publiques, les organismes internationaux', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (261,'PCG99-BASE','TIERS', 'XXXXXX',  '444', '44', 'Etat - Impôts sur les bénéfices', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (262,'PCG99-BASE','TIERS', 'XXXXXX',  '445', '44', 'Etat - Taxes sur le chiffre d''affaires', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (263,'PCG99-BASE','TIERS', 'XXXXXX',  '446', '44', 'Obligations cautionnées', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (264,'PCG99-BASE','TIERS', 'XXXXXX',  '447', '44', 'Autres impôts, taxes et versements assimilés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (265,'PCG99-BASE','TIERS', 'XXXXXX',  '448', '44', 'Etat - Charges à payer et produits à recevoir', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (266,'PCG99-BASE','TIERS', 'XXXXXX',  '449', '44', 'Quotas d''émission à restituer à l''Etat', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (267,'PCG99-BASE','TIERS', 'XXXXXX',   '45',  '4', 'Groupe et associes', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (268,'PCG99-BASE','TIERS', 'XXXXXX',  '451', '45', 'Groupe', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (269,'PCG99-BASE','TIERS', 'XXXXXX',  '455', '45', 'Associés - Comptes courants', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (270,'PCG99-BASE','TIERS', 'XXXXXX',  '456', '45', 'Associés - Opérations sur le capital', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (271,'PCG99-BASE','TIERS', 'XXXXXX',  '457', '45', 'Associés - Dividendes à payer', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (272,'PCG99-BASE','TIERS', 'XXXXXX',  '458', '45', 'Associés - Opérations faites en commun et en G.I.E.', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (273,'PCG99-BASE','TIERS', 'XXXXXX',   '46',  '4', 'Débiteurs divers et créditeurs divers', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (274,'PCG99-BASE','TIERS', 'XXXXXX',  '462', '46', 'Créances sur cessions d''immobilisations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (275,'PCG99-BASE','TIERS', 'XXXXXX',  '464', '46', 'Dettes sur acquisitions de valeurs mobilières de placement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (276,'PCG99-BASE','TIERS', 'XXXXXX',  '465', '46', 'Créances sur cessions de valeurs mobilières de placement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (277,'PCG99-BASE','TIERS', 'XXXXXX',  '467', '46', 'Autres comptes débiteurs ou créditeurs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (278,'PCG99-BASE','TIERS', 'XXXXXX',  '468', '46', 'Divers - Charges à payer et produits à recevoir', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (279,'PCG99-BASE','TIERS', 'XXXXXX',   '47',  '4', 'Comptes transitoires ou d''attente', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (280,'PCG99-BASE','TIERS', 'XXXXXX',  '471', '47', 'Comptes d''attente', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (281,'PCG99-BASE','TIERS', 'XXXXXX',  '476', '47', 'Différence de conversion - Actif', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (282,'PCG99-BASE','TIERS', 'XXXXXX',  '477', '47', 'Différences de conversion - Passif', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (283,'PCG99-BASE','TIERS', 'XXXXXX',  '478', '47', 'Autres comptes transitoires', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (284,'PCG99-BASE','TIERS', 'XXXXXX',   '48',  '4', 'Comptes de régularisation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (285,'PCG99-BASE','TIERS', 'XXXXXX',  '481', '48', 'Charges à répartir sur plusieurs exercices', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (286,'PCG99-BASE','TIERS', 'XXXXXX',  '486', '48', 'Charges constatées d''avance', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (287,'PCG99-BASE','TIERS', 'XXXXXX',  '487', '48', 'Produits constatés d''avance', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (288,'PCG99-BASE','TIERS', 'XXXXXX',  '488', '48', 'Comptes de répartition périodique des charges et des produits', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (289,'PCG99-BASE','TIERS', 'XXXXXX',  '489', '48', 'Quotas d''émission alloués par l''Etat', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (290,'PCG99-BASE','TIERS', 'XXXXXX',   '49',  '4', 'Provisions pour dépréciation des comptes de tiers', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (291,'PCG99-BASE','TIERS', 'XXXXXX',  '491', '49', 'Provisions pour dépréciation des comptes de clients', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (292,'PCG99-BASE','TIERS', 'XXXXXX',  '495', '49', 'Provisions pour dépréciation des comptes du groupe et des associés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (293,'PCG99-BASE','TIERS', 'XXXXXX',  '496', '49', 'Provisions pour dépréciation des comptes de débiteurs divers', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (294,'PCG99-BASE','FINAN', 'XXXXXX',   '50',  '5', 'Valeurs mobilières de placement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (295,'PCG99-BASE','FINAN', 'XXXXXX',  '501', '50', 'Parts dans des entreprises liées', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (296,'PCG99-BASE','FINAN', 'XXXXXX',  '502', '50', 'Actions propres', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (297,'PCG99-BASE','FINAN', 'XXXXXX',  '503', '50', 'Actions', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (298,'PCG99-BASE','FINAN', 'XXXXXX',  '504', '50', 'Autres titres conférant un droit de propriété', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (299,'PCG99-BASE','FINAN', 'XXXXXX',  '505', '50', 'Obligations et bons émis par la société et rachetés par elle', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (300,'PCG99-BASE','FINAN', 'XXXXXX',  '506', '50', 'Obligations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (301,'PCG99-BASE','FINAN', 'XXXXXX',  '507', '50', 'Bons du Trésor et bons de caisse à court terme', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (302,'PCG99-BASE','FINAN', 'XXXXXX',  '508', '50', 'Autres valeurs mobilières de placement et autres créances assimilées', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (303,'PCG99-BASE','FINAN', 'XXXXXX',  '509', '50', 'Versements restant à effectuer sur valeurs mobilières de placement non libérées', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (304,'PCG99-BASE','FINAN', 'XXXXXX',   '51',  '5', 'Banques, établissements financiers et assimilés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (305,'PCG99-BASE','FINAN', 'XXXXXX',  '511', '51', 'Valeurs à l''encaissement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (306,'PCG99-BASE','FINAN', 'BANK',    '512', '51', 'Banques', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (307,'PCG99-BASE','FINAN', 'XXXXXX',  '514', '51', 'Chèques postaux', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (308,'PCG99-BASE','FINAN', 'XXXXXX',  '515', '51', 'Caisses du Trésor et des établissements publics', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (309,'PCG99-BASE','FINAN', 'XXXXXX',  '516', '51', 'Sociétés de bourse', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (310,'PCG99-BASE','FINAN', 'XXXXXX',  '517', '51', 'Autres organismes financiers', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (311,'PCG99-BASE','FINAN', 'XXXXXX',  '518', '51', 'Intérêts courus', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (312,'PCG99-BASE','FINAN', 'XXXXXX',  '519', '51', 'Concours bancaires courants', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (313,'PCG99-BASE','FINAN', 'XXXXXX',   '52',  '5', 'Instruments de trésorerie', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (314,'PCG99-BASE','FINAN', 'CASH',     '53',  '5', 'Caisse', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (315,'PCG99-BASE','FINAN', 'XXXXXX',  '531', '53', 'Caisse siège social', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (316,'PCG99-BASE','FINAN', 'XXXXXX',  '532', '53', 'Caisse succursale (ou usine) A', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (317,'PCG99-BASE','FINAN', 'XXXXXX',  '533', '53', 'Caisse succursale (ou usine) B', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (318,'PCG99-BASE','FINAN', 'XXXXXX',   '54',  '5', 'Régies d''avance et accréditifs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (319,'PCG99-BASE','FINAN', 'XXXXXX',   '58',  '5', 'Virements internes', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (320,'PCG99-BASE','FINAN', 'XXXXXX',   '59',  '5', 'Provisions pour dépréciation des comptes financiers', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (321,'PCG99-BASE','FINAN', 'XXXXXX',  '590', '59', 'Provisions pour dépréciation des valeurs mobilières de placement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (322,'PCG99-BASE','CHARGE','PRODUCT',  '60', '6', 'Achats', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (323,'PCG99-BASE','CHARGE','XXXXXX',  '601','60', 'Achats stockés - Matières premières (et fournitures)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (324,'PCG99-BASE','CHARGE','XXXXXX',  '602','60', 'Achats stockés - Autres approvisionnements', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (325,'PCG99-BASE','CHARGE','XXXXXX',  '603','60', 'Variations des stocks (approvisionnements et marchandises)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (326,'PCG99-BASE','CHARGE','XXXXXX',  '604','60', 'Achats stockés - Matières premières (et fournitures)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (327,'PCG99-BASE','CHARGE','XXXXXX',  '605','60', 'Achats de matériel, équipements et travaux', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (328,'PCG99-BASE','CHARGE','XXXXXX',  '606','60', 'Achats non stockés de matière et fournitures', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (329,'PCG99-BASE','CHARGE','XXXXXX',  '607','60', 'Achats de marchandises', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (330,'PCG99-BASE','CHARGE','XXXXXX',  '608','60', '(Compte réservé, le cas échéant, à la récapitulation des frais accessoires incorporés aux achats)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (331,'PCG99-BASE','CHARGE','XXXXXX',  '609','60', 'Rabais, remises et ristournes obtenus sur achats', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (332,'PCG99-BASE','CHARGE','SERVICE',  '61', '6', 'Services extérieurs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (333,'PCG99-BASE','CHARGE','XXXXXX',  '611','61', 'Sous-traitance générale', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (334,'PCG99-BASE','CHARGE','XXXXXX',  '612','61', 'Redevances de crédit-bail', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (335,'PCG99-BASE','CHARGE','XXXXXX',  '613','61', 'Locations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (336,'PCG99-BASE','CHARGE','XXXXXX',  '614','61', 'Charges locatives et de copropriété', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (337,'PCG99-BASE','CHARGE','XXXXXX',  '615','61', 'Entretien et réparations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (338,'PCG99-BASE','CHARGE','XXXXXX',  '616','61', 'Primes d''assurances', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (339,'PCG99-BASE','CHARGE','XXXXXX',  '617','61', 'Etudes et recherches', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (340,'PCG99-BASE','CHARGE','XXXXXX',  '618','61', 'Divers', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (341,'PCG99-BASE','CHARGE','XXXXXX',  '619','61', 'Rabais, remises et ristournes obtenus sur services extérieurs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (342,'PCG99-BASE','CHARGE','XXXXXX',   '62', '6', 'Autres services extérieurs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (343,'PCG99-BASE','CHARGE','XXXXXX',  '621','62', 'Personnel extérieur à l''entreprise', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (344,'PCG99-BASE','CHARGE','XXXXXX',  '622','62', 'Rémunérations d''intermédiaires et honoraires', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (345,'PCG99-BASE','CHARGE','XXXXXX',  '623','62', 'Publicité, publications, relations publiques', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (346,'PCG99-BASE','CHARGE','XXXXXX',  '624','62', 'Transports de biens et transports collectifs du personnel', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (347,'PCG99-BASE','CHARGE','XXXXXX',  '625','62', 'Déplacements, missions et réceptions', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (348,'PCG99-BASE','CHARGE','XXXXXX',  '626','62', 'Frais postaux et de télécommunications', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (349,'PCG99-BASE','CHARGE','XXXXXX',  '627','62', 'Services bancaires et assimilés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (350,'PCG99-BASE','CHARGE','XXXXXX',  '628','62', 'Divers', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (351,'PCG99-BASE','CHARGE','XXXXXX',  '629','62', 'Rabais, remises et ristournes obtenus sur autres services extérieurs', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (352,'PCG99-BASE','CHARGE','XXXXXX',   '63', '6', 'Impôts, taxes et versements assimilés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (353,'PCG99-BASE','CHARGE','XXXXXX',  '631','63', 'Impôts, taxes et versements assimilés sur rémunérations (administrations des impôts)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (354,'PCG99-BASE','CHARGE','XXXXXX',  '633','63', 'Impôts, taxes et versements assimilés sur rémunérations (autres organismes)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (355,'PCG99-BASE','CHARGE','XXXXXX',  '635','63', 'Autres impôts, taxes et versements assimilés (administrations des impôts)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (356,'PCG99-BASE','CHARGE','XXXXXX',  '637','63', 'Autres impôts, taxes et versements assimilés (autres organismes)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (357,'PCG99-BASE','CHARGE','XXXXXX',   '64', '6', 'Charges de personnel', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (358,'PCG99-BASE','CHARGE','XXXXXX',  '641','64', 'Rémunérations du personnel', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (359,'PCG99-BASE','CHARGE','XXXXXX',  '644','64', 'Rémunération du travail de l''exploitant', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (360,'PCG99-BASE','CHARGE','SOCIAL',  '645','64', 'Charges de sécurité sociale et de prévoyance', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (361,'PCG99-BASE','CHARGE','XXXXXX',  '646','64', 'Cotisations sociales personnelles de l''exploitant', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (362,'PCG99-BASE','CHARGE','XXXXXX',  '647','64', 'Autres charges sociales', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (363,'PCG99-BASE','CHARGE','XXXXXX',  '648','64', 'Autres charges de personnel', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (364,'PCG99-BASE','CHARGE','XXXXXX',   '65', '6', 'Autres charges de gestion courante', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (365,'PCG99-BASE','CHARGE','XXXXXX',  '651','65', 'Redevances pour concessions, brevets, licences, marques, procédés, logiciels, droits et valeurs similaires', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (366,'PCG99-BASE','CHARGE','XXXXXX',  '653','65', 'Jetons de présence', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (367,'PCG99-BASE','CHARGE','XXXXXX',  '654','65', 'Pertes sur créances irrécouvrables', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (368,'PCG99-BASE','CHARGE','XXXXXX',  '655','65', 'Quote-part de résultat sur opérations faites en commun', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (369,'PCG99-BASE','CHARGE','XXXXXX',  '658','65', 'Charges diverses de gestion courante', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (370,'PCG99-BASE','CHARGE','XXXXXX',   '66', '6', 'Charges financières', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (371,'PCG99-BASE','CHARGE','XXXXXX',  '661','66', 'Charges d''intérêts', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (372,'PCG99-BASE','CHARGE','XXXXXX',  '664','66', 'Pertes sur créances liées à des participations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (373,'PCG99-BASE','CHARGE','XXXXXX',  '665','66', 'Escomptes accordés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (374,'PCG99-BASE','CHARGE','XXXXXX',  '666','66', 'Pertes de change', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (375,'PCG99-BASE','CHARGE','XXXXXX',  '667','66', 'Charges nettes sur cessions de valeurs mobilières de placement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (376,'PCG99-BASE','CHARGE','XXXXXX',  '668','66', 'Autres charges financières', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (377,'PCG99-BASE','CHARGE','XXXXXX',   '67', '6', 'Charges exceptionnelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (378,'PCG99-BASE','CHARGE','XXXXXX',  '671','67', 'Charges exceptionnelles sur opérations de gestion', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (379,'PCG99-BASE','CHARGE','XXXXXX',  '672','67', '(Compte à la disposition des entités pour enregistrer, en cours d''exercice, les charges sur exercices antérieurs)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (380,'PCG99-BASE','CHARGE','XXXXXX',  '675','67', 'Valeurs comptables des éléments d''actif cédés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (381,'PCG99-BASE','CHARGE','XXXXXX',  '678','67', 'Autres charges exceptionnelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (382,'PCG99-BASE','CHARGE','XXXXXX',   '68', '6', 'Dotations aux amortissements et aux provisions', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (383,'PCG99-BASE','CHARGE','XXXXXX',  '681','68', 'Dotations aux amortissements et aux provisions - Charges d''exploitation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (384,'PCG99-BASE','CHARGE','XXXXXX',  '686','68', 'Dotations aux amortissements et aux provisions - Charges financières', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (385,'PCG99-BASE','CHARGE','XXXXXX',  '687','68', 'Dotations aux amortissements et aux provisions - Charges exceptionnelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (386,'PCG99-BASE','CHARGE','XXXXXX',   '69', '6', 'Participation des salariés - impôts sur les bénéfices et assimiles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (387,'PCG99-BASE','CHARGE','XXXXXX',  '691','69', 'Participation des salariés aux résultats', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (388,'PCG99-BASE','CHARGE','XXXXXX',  '695','69', 'Impôts sur les bénéfices', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (389,'PCG99-BASE','CHARGE','XXXXXX',  '696','69', 'Suppléments d''impôt sur les sociétés liés aux distributions', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (390,'PCG99-BASE','CHARGE','XXXXXX',  '697','69', 'Imposition forfaitaire annuelle des sociétés', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (391,'PCG99-BASE','CHARGE','XXXXXX',  '698','69', 'Intégration fiscale', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (392,'PCG99-BASE','CHARGE','XXXXXX',  '699','69', 'Produits - Reports en arrière des déficits', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (393,'PCG99-BASE','PROD',  'XXXXXX',   '70', '7', 'Ventes de produits fabriqués, prestations de services, marchandises', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (394,'PCG99-BASE','PROD',  'PRODUCT',  '701','70', 'Ventes de produits finis', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (395,'PCG99-BASE','PROD',  'XXXXXX',   '702','70', 'Ventes de produits intermédiaires', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (396,'PCG99-BASE','PROD',  'XXXXXX',   '703','70', 'Ventes de produits résiduels', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (397,'PCG99-BASE','PROD',  'XXXXXX',   '704','70', 'Travaux', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (398,'PCG99-BASE','PROD',  'XXXXXX',   '705','70', 'Etudes', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (399,'PCG99-BASE','PROD',  'SERVICE',  '706','70', 'Prestations de services', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (400,'PCG99-BASE','PROD',  'PRODUCT',  '707','70', 'Ventes de marchandises', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (401,'PCG99-BASE','PROD',  'PRODUCT',  '708','70', 'Produits des activités annexes', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (402,'PCG99-BASE','PROD',  'XXXXXX',   '709','70', 'Rabais, remises et ristournes accordés par l''entreprise', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (403,'PCG99-BASE','PROD',  'XXXXXX',    '71', '7', 'Production stockée (ou déstockage)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (404,'PCG99-BASE','PROD',  'XXXXXX',   '713','71', 'Variation des stocks (en-cours de production, produits)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (405,'PCG99-BASE','PROD',  'XXXXXX',    '72', '7', 'Production immobilisée', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (406,'PCG99-BASE','PROD',  'XXXXXX',   '721','72', 'Immobilisations incorporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (407,'PCG99-BASE','PROD',  'XXXXXX',   '722','72', 'Immobilisations corporelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (408,'PCG99-BASE','PROD',  'XXXXXX',    '74', '7', 'Subventions d''exploitation', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (409,'PCG99-BASE','PROD',  'XXXXXX',    '75', '7', 'Autres produits de gestion courante', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (410,'PCG99-BASE','PROD',  'XXXXXX',   '751','75', 'Redevances pour concessions, brevets, licences, marques, procédés, logiciels, droits et valeurs similaires', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (411,'PCG99-BASE','PROD',  'XXXXXX',   '752','75', 'Revenus des immeubles non affectés à des activités professionnelles', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (412,'PCG99-BASE','PROD',  'XXXXXX',   '753','75', 'Jetons de présence et rémunérations d''administrateurs, gérants,...', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (413,'PCG99-BASE','PROD',  'XXXXXX',   '754','75', 'Ristournes perçues des coopératives (provenant des excédents)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (414,'PCG99-BASE','PROD',  'XXXXXX',   '755','75', 'Quotes-parts de résultat sur opérations faites en commun', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (415,'PCG99-BASE','PROD',  'XXXXXX',   '758','75', 'Produits divers de gestion courante', '1'); 
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (416,'PCG99-BASE','PROD',  'XXXXXX',    '76', '7', 'Produits financiers', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (417,'PCG99-BASE','PROD',  'XXXXXX',   '761','76', 'Produits de participations', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (418,'PCG99-BASE','PROD',  'XXXXXX',   '762','76', 'Produits des autres immobilisations financières', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (419,'PCG99-BASE','PROD',  'XXXXXX',   '763','76', 'Revenus des autres créances', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (420,'PCG99-BASE','PROD',  'XXXXXX',   '764','76', 'Revenus des valeurs mobilières de placement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (421,'PCG99-BASE','PROD',  'XXXXXX',   '765','76', 'Escomptes obtenus', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (422,'PCG99-BASE','PROD',  'XXXXXX',   '766','76', 'Gains de change', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (423,'PCG99-BASE','PROD',  'XXXXXX',   '767','76', 'Produits nets sur cessions de valeurs mobilières de placement', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (424,'PCG99-BASE','PROD',  'XXXXXX',   '768','76', 'Autres produits financiers', '1'); 
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (425,'PCG99-BASE','PROD',  'XXXXXX',    '77', '7', 'Produits exceptionnels', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (426,'PCG99-BASE','PROD',  'XXXXXX',   '771','77', 'Produits exceptionnels sur opérations de gestion', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (427,'PCG99-BASE','PROD',  'XXXXXX',   '772','77', '(Compte à la disposition des entités pour enregistrer, en cours d''exercice, les produits sur exercices antérieurs)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (428,'PCG99-BASE','PROD',  'XXXXXX',   '775','77', 'Produits des cessions d''éléments d''actif', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (429,'PCG99-BASE','PROD',  'XXXXXX',   '777','77', 'Quote-part des subventions d''investissement virée au résultat de l''exercice', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (430,'PCG99-BASE','PROD',  'XXXXXX',   '778','77', 'Autres produits exceptionnels', '1'); 
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (431,'PCG99-BASE','PROD',  'XXXXXX',    '78', '7', 'Reprises sur amortissements et provisions', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (432,'PCG99-BASE','PROD',  'XXXXXX',   '781','78', 'Reprises sur amortissements et provisions (à inscrire dans les produits d''exploitation)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (433,'PCG99-BASE','PROD',  'XXXXXX',   '786','78', 'Reprises sur provisions pour risques (à inscrire dans les produits financiers)', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (434,'PCG99-BASE','PROD',  'XXXXXX',   '787','78', 'Reprises sur provisions (à inscrire dans les produits exceptionnels)', '1'); 
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (435,'PCG99-BASE','PROD',  'XXXXXX',    '79', '7', 'Transferts de charges', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (436,'PCG99-BASE','PROD',  'XXXXXX',   '791','79', 'Transferts de charges d''exploitation ', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (437,'PCG99-BASE','PROD',  'XXXXXX',   '796','79', 'Transferts de charges financières', '1');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (438,'PCG99-BASE','PROD',  'XXXXXX',   '797','79', 'Transferts de charges exceptionnelles', '1');

-- Add discount in product supplier price
ALTER TABLE llx_product_fournisseur_price ADD COLUMN remise_percent DOUBLE NOT NULL DEFAULT 0 AFTER quantity;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN remise DOUBLE NOT NULL DEFAULT 0 AFTER remise_percent; 

-- Stock calculation on product
UPDATE llx_product p SET p.stock= (SELECT SUM(ps.reel) FROM llx_product_stock ps WHERE ps.fk_product = p.rowid);

-- Add possibility to defined position/job of a user
ALTER TABLE llx_user ADD COLUMN job varchar(128) AFTER firstname;


-- Use entity 0 for all entities
INSERT INTO llx_const(name, value, visible, entity) SELECT __ENCRYPT('SYSLOG_HANDLERS')__, __ENCRYPT('["mod_syslog_file"]')__, 0, 0 FROM llx_const WHERE __DECRYPT('name')__ = 'SYSLOG_FILE_ON' AND __DECRYPT('value')__ = '1';


-- New Imports
ALTER TABLE llx_commande_fournisseurdet ADD COLUMN import_key varchar(14) AFTER info_bits;
ALTER TABLE llx_entrepot ADD COLUMN import_key varchar(14) AFTER fk_user_author;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN import_key varchar(14) AFTER fk_user;
ALTER TABLE llx_product_stock ADD COLUMN import_key varchar(14) AFTER pmp;
ALTER TABLE llx_societe_rib ADD COLUMN import_key varchar(14) AFTER adresse_proprio;
ALTER TABLE llx_categorie_product ADD COLUMN import_key varchar(14) AFTER fk_product;
ALTER TABLE llx_categorie_societe ADD COLUMN import_key varchar(14) AFTER fk_societe;
ALTER TABLE llx_categorie_fournisseur ADD COLUMN import_key varchar(14) AFTER fk_societe;

-- Export filter
ALTER TABLE llx_export_model ADD COLUMN filter text AFTER field;

-- [ task #146 ] Remove table llx_categorie_association
ALTER TABLE llx_categorie_association DROP FOREIGN KEY fk_categorie_asso_fk_categorie_mere;
ALTER TABLE llx_categorie_association DROP FOREIGN KEY fk_categorie_asso_fk_categorie_fille;
ALTER TABLE llx_categorie DROP INDEX uk_categorie_ref;
ALTER TABLE llx_categorie ADD COLUMN fk_parent integer DEFAULT 0 NOT NULL AFTER rowid;
ALTER TABLE llx_categorie MODIFY COLUMN label varchar(255) NOT NULL;
ALTER TABLE llx_categorie ADD UNIQUE INDEX uk_categorie_ref (entity, fk_parent, label, type);
ALTER TABLE llx_categorie ADD INDEX idx_categorie_type (type);
ALTER TABLE llx_categorie ADD INDEX idx_categorie_label (label);

-- [ task #559 ] Price by quantity management
CREATE TABLE llx_product_price_by_qty
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  fk_product_price	integer NOT NULL,
  date_price		timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  price			double (24,8) DEFAULT 0,
  price_ttc		double (24,8) DEFAULT 0,
  qty_min		real DEFAULT 0
)ENGINE=innodb;

ALTER TABLE llx_product_price ADD COLUMN price_by_qty integer NOT NULL DEFAULT 0;

ALTER TABLE llx_product_price_by_qty ADD UNIQUE INDEX uk_product_price_by_qty_level (fk_product_price, qty_min);
ALTER TABLE llx_product_price_by_qty ADD INDEX idx_product_price_by_qty_fk_product_price (fk_product_price);
ALTER TABLE llx_product_price_by_qty ADD CONSTRAINT fk_product_price_by_qty_fk_product_price FOREIGN KEY (fk_product_price) REFERENCES llx_product_price (rowid);

ALTER TABLE llx_product_price_by_qty ADD remise_percent DOUBLE NOT NULL DEFAULT '0' AFTER price_ttc;
ALTER TABLE llx_product_price_by_qty ADD remise DOUBLE NOT NULL DEFAULT '0' AFTER remise_percent;
ALTER TABLE llx_product_price_by_qty ADD unitprice DOUBLE (24,8) NOT NULL DEFAULT '0' AFTER qty_min;
ALTER TABLE llx_product_price_by_qty CHANGE qty_min quantity DOUBLE NULL DEFAULT NULL;

-- Change index name to be compliant with SQL standard, index name must be unique in database schema
ALTER TABLE llx_c_actioncomm DROP INDEX code;
ALTER TABLE llx_c_actioncomm ADD UNIQUE INDEX uk_c_actioncomm(code);
ALTER TABLE llx_c_civilite DROP INDEX code;
ALTER TABLE llx_c_civilite ADD UNIQUE INDEX uk_c_civilite(code);
ALTER TABLE llx_c_propalst DROP INDEX code;
ALTER TABLE llx_c_propalst ADD UNIQUE INDEX uk_c_propalst(code);
ALTER TABLE llx_c_stcomm DROP INDEX code;
ALTER TABLE llx_c_stcomm ADD UNIQUE INDEX uk_c_stcomm(code);
ALTER TABLE llx_c_type_fees DROP INDEX code;
ALTER TABLE llx_c_type_fees ADD UNIQUE INDEX uk_c_type_fees(code);
ALTER TABLE llx_c_typent DROP INDEX code;
ALTER TABLE llx_c_typent ADD UNIQUE INDEX uk_c_typent(code);
ALTER TABLE llx_c_effectif DROP INDEX code;
ALTER TABLE llx_c_effectif ADD UNIQUE INDEX uk_c_effectif(code);
ALTER TABLE llx_c_paiement DROP INDEX code;
ALTER TABLE llx_c_paiement ADD UNIQUE INDEX uk_c_paiement(code);

delete from llx_c_actioncomm where id = 40;
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, position) values ( 40, 'AC_OTH_AUTO','systemauto', 'Other (automatically inserted events)' ,NULL, 20);
UPDATE llx_c_actioncomm SET libelle = 'Other (manually inserted events)' WHERE code = 'AC_OTH';
UPDATE llx_c_actioncomm SET active = 0 WHERE code in ('AC_PROP', 'AC_COM', 'AC_FAC', 'AC_SHIP', 'AC_SUP_ORD', 'AC_SUP_INV');

-- Update dictionary of table llx_c_paper_format
DELETE FROM llx_c_paper_format;

-- Europe
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (1,   'EU4A0',       'Format 4A0',                '1682', '2378', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (2,   'EU2A0',       'Format 2A0',                '1189', '1682', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (3,   'EUA0',        'Format A0',                 '840',  '1189', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (4,   'EUA1',        'Format A1',                 '594',  '840',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (5,   'EUA2',        'Format A2',                 '420',  '594',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (6,   'EUA3',        'Format A3',                 '297',  '420',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (7,   'EUA4',        'Format A4',                 '210',  '297',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (8,   'EUA5',        'Format A5',                 '148',  '210',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (9,   'EUA6',        'Format A6',                 '105',  '148',  'mm', 1);

-- US
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (100, 'USLetter',    'Format Letter (A)',         '216',  '279',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (105, 'USLegal',     'Format Legal',              '216',  '356',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (110, 'USExecutive', 'Format Executive',          '190',  '254',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (115, 'USLedger',    'Format Ledger/Tabloid (B)', '279',  '432',  'mm', 1);

-- Canadian
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (200, 'CAP1',        'Format Canadian P1',        '560',  '860',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (205, 'CAP2',        'Format Canadian P2',        '430',  '560',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (210, 'CAP3',        'Format Canadian P3',        '280',  '430',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (215, 'CAP4',        'Format Canadian P4',        '215',  '280',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (220, 'CAP5',        'Format Canadian P5',        '140',  '215',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (225, 'CAP6',        'Format Canadian P6',        '107',  '140',  'mm', 1);


-- increase field size
ALTER TABLE llx_bank_account MODIFY COLUMN code_banque varchar(8);

create table llx_user_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object        integer NOT NULL,    -- member id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_user_extrafields ADD INDEX idx_user_extrafields (fk_object);

ALTER TABLE llx_element_lock ADD COLUMN sessionid varchar(255) AFTER datem;
ALTER TABLE llx_element_lock MODIFY COLUMN elementtype varchar(32) NOT NULL;
ALTER TABLE llx_element_lock DROP COLUMN fk_user_modif;
ALTER TABLE llx_element_lock DROP COLUMN status;

DELETE FROM llx_c_action_trigger WHERE elementtype='withdraw';
UPDATE llx_c_action_trigger SET code='FICHINTER_VALIDATE' WHERE code='FICHEINTER_VALIDATE';

UPDATE llx_c_departements SET ncc='ALAVA', nom='Álava' WHERE code_departement='01' AND fk_region=419;

ALTER TABLE llx_product_fournisseur_price DROP FOREIGN KEY fk_product_fournisseur;

UPDATE llx_const SET name = __ENCRYPT('PRODUIT_MULTI_PRICES')__ WHERE __DECRYPT('name')__ = 'PRODUIT_MUTLI_PRICES';

DELETE FROM llx_const WHERE entity <> 0 AND __DECRYPT('name')__ IN ('SYSLOG_HANDLERS','SYSLOG_LEVEL');
UPDATE llx_const SET entity = 0 WHERE __DECRYPT('name')__ = 'SYSLOG_FILE';
