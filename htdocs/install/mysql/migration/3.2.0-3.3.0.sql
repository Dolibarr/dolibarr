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

ALTER TABLE llx_menu MODIFY COLUMN fk_mainmenu   varchar(24);
ALTER TABLE llx_menu MODIFY COLUMN fk_leftmenu   varchar(24);

ALTER TABLE llx_societe ADD COLUMN idprof6 varchar(128) after idprof5;
ALTER TABLE llx_societe DROP COLUMN fk_secteur;
ALTER TABLE llx_societe DROP COLUMN description;
ALTER TABLE llx_societe DROP COLUMN services;

ALTER TABLE llx_bank ADD COLUMN tms timestamp after datec;
  
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

alter table llx_commandedet add column fk_product_fournisseur_price int(11) after info_bits;
alter table llx_commandedet add column buy_price_ht double(24,8) DEFAULT 0 after fk_product_fournisseur_price;
alter table llx_commandedet drop column marge_tx;
alter table llx_commandedet drop column marque_tx;

alter table llx_facturedet add column fk_product_fournisseur_price int(11) after info_bits;
alter table llx_facturedet add column buy_price_ht double(24,8) DEFAULT 0 after fk_product_fournisseur_price;

alter table llx_propaldet add column fk_product_fournisseur_price int(11) after info_bits;
alter table llx_propaldet add column buy_price_ht double(24,8) DEFAULT 0 after fk_product_fournisseur_price;
alter table llx_propaldet drop column pa_ht;
alter table llx_propaldet drop column marge_tx;
alter table llx_propaldet drop column marque_tx;

ALTER TABLE llx_commande CHANGE COLUMN fk_demand_reason fk_input_reason INT(11) NULL DEFAULT NULL;
ALTER TABLE llx_propal CHANGE COLUMN fk_demand_reason fk_input_reason INT(11) NULL DEFAULT NULL;
ALTER TABLE llx_commande_fournisseur CHANGE COLUMN fk_methode_commande fk_input_method INT(11) NULL DEFAULT 0;

INSERT INTO llx_const (name, value, type, note, visible) values ('PRODUCT_CODEPRODUCT_ADDON','mod_codeproduct_leopard','yesno','Module to control product codes',0);

ALTER TABLE llx_c_barcode_type ADD UNIQUE INDEX uk_c_barcode_type(code, entity);

ALTER TABLE llx_socpeople ADD column no_email SMALLINT NOT NULL DEFAULT 0 AFTER priv;

ALTER TABLE llx_commande_fournisseur ADD COLUMN date_livraison date NULL;

ALTER TABLE llx_propaldet ADD COLUMN label varchar(255) DEFAULT NULL AFTER fk_product;
ALTER TABLE llx_commandedet ADD COLUMN label varchar(255) DEFAULT NULL AFTER fk_product;
ALTER TABLE llx_facturedet ADD COLUMN label varchar(255) DEFAULT NULL AFTER fk_product;
ALTER TABLE llx_facturedet_rec ADD COLUMN label varchar(255) DEFAULT NULL AFTER product_type;

ALTER TABLE llx_accountingaccount  ADD COLUMN active tinyint DEFAULT 1 NOT NULL AFTER label;

ALTER TABLE llx_actioncomm MODIFY elementtype VARCHAR(32);

-- TASK #107
ALTER TABLE llx_ecm_directories MODIFY COLUMN label varchar(64) NOT NULL;
ALTER TABLE llx_ecm_directories ADD COLUMN fullpath text AFTER cachenbofdoc;
ALTER TABLE llx_ecm_directories MODIFY COLUMN fullpath text;
ALTER TABLE llx_ecm_directories ADD COLUMN extraparams varchar(255) AFTER fullpath;
ALTER TABLE llx_ecm_directories ADD COLUMN acl text;
ALTER TABLE llx_ecm_directories ADD INDEX idx_ecm_directories_fk_user_c (fk_user_c);
ALTER TABLE llx_ecm_directories ADD INDEX idx_ecm_directories_fk_user_m (fk_user_m);
ALTER TABLE llx_ecm_directories ADD CONSTRAINT fk_ecm_directories_fk_user_c FOREIGN KEY (fk_user_c) REFERENCES llx_user (rowid);
ALTER TABLE llx_ecm_directories ADD CONSTRAINT fk_ecm_directories_fk_user_m FOREIGN KEY (fk_user_m) REFERENCES llx_user (rowid);

ALTER TABLE llx_ecm_documents DROP FOREIGN KEY fk_ecm_documents_fk_directory;
ALTER TABLE llx_ecm_documents DROP INDEX idx_ecm_documents_ref;
ALTER TABLE llx_ecm_documents DROP INDEX idx_ecm_documents;
ALTER TABLE llx_ecm_documents DROP COLUMN manualkeyword;
ALTER TABLE llx_ecm_documents DROP COLUMN fullpath_orig;
ALTER TABLE llx_ecm_documents DROP COLUMN private;
ALTER TABLE llx_ecm_documents DROP COLUMN crc;
ALTER TABLE llx_ecm_documents DROP COLUMN cryptkey;
ALTER TABLE llx_ecm_documents DROP COLUMN cipher;
ALTER TABLE llx_ecm_documents CHANGE COLUMN fullpath_dol fullpath text;
ALTER TABLE llx_ecm_documents MODIFY COLUMN ref varchar(32) NOT NULL;
ALTER TABLE llx_ecm_documents MODIFY COLUMN fullpath text;
ALTER TABLE llx_ecm_documents MODIFY COLUMN filemime varchar(128) NOT NULL;
ALTER TABLE llx_ecm_documents ADD COLUMN metadata text after description;
ALTER TABLE llx_ecm_documents ADD COLUMN extraparams varchar(255) AFTER fk_directory;
ALTER TABLE llx_ecm_documents ADD UNIQUE INDEX idx_ecm_documents_ref (ref, entity);
ALTER TABLE llx_ecm_documents ADD INDEX idx_ecm_documents_fk_create (fk_create);
ALTER TABLE llx_ecm_documents ADD INDEX idx_ecm_documents_fk_update (fk_update);
ALTER TABLE llx_ecm_documents ADD CONSTRAINT fk_ecm_documents_fk_create FOREIGN KEY (fk_create) REFERENCES llx_user (rowid);
ALTER TABLE llx_ecm_documents ADD CONSTRAINT fk_ecm_documents_fk_update FOREIGN KEY (fk_update) REFERENCES llx_user (rowid);

create table llx_element_tag
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,			-- multi company id
  lang				varchar(5) NOT NULL,
  tag				varchar(255) NOT NULL,
  fk_element		integer NOT NULL,
  element			varchar(64) NOT NULL
  
)ENGINE=innodb;

ALTER TABLE llx_element_tag ADD UNIQUE INDEX uk_element_tag (entity, lang, tag, fk_element, element);
-- END TASK #107


CREATE TABLE llx_holiday_config 
(
rowid    INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
name     VARCHAR( 255 ) NOT NULL UNIQUE,
value    TEXT NULL
) 
ENGINE=innodb;

CREATE TABLE llx_holiday_events 
(
rowid    INT( 11 ) NOT NULL PRIMARY KEY AUTO_INCREMENT ,
name     VARCHAR( 255 ) NOT NULL ,
value    TEXT NOT NULL
) 
ENGINE=innodb;

CREATE TABLE llx_holiday_logs 
(
rowid             INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
date_action       DATETIME NOT NULL ,
fk_user_action    INT( 11 ) NOT NULL ,
fk_user_update    INT( 11 ) NOT NULL ,
type_action       VARCHAR( 255 ) NOT NULL ,
prev_solde        VARCHAR( 255 ) NOT NULL ,
new_solde         VARCHAR( 255 ) NOT NULL
) 
ENGINE=innodb;

CREATE TABLE llx_holiday_users 
(
fk_user     INT( 11 ) NOT NULL PRIMARY KEY,
nb_holiday   FLOAT( 5 ) NOT NULL DEFAULT '0'
) 
ENGINE=innodb;

CREATE TABLE llx_holiday 
(
rowid          INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
fk_user        INT( 11 ) NOT NULL ,
date_create    DATETIME NOT NULL ,
description    VARCHAR( 255 ) NOT NULL ,
date_debut     DATE NOT NULL ,
date_fin       DATE NOT NULL ,
statut         INT( 11 ) NOT NULL DEFAULT '1',
fk_validator   INT( 11 ) NOT NULL ,
date_valid     DATETIME NULL DEFAULT NULL ,
fk_user_valid  INT( 11 ) NULL DEFAULT NULL ,
date_refuse    DATETIME NULL DEFAULT NULL ,
fk_user_refuse INT( 11 ) NULL DEFAULT NULL ,
date_cancel    DATETIME NULL DEFAULT NULL ,
fk_user_cancel INT( 11 ) NULL DEFAULT NULL,
detail_refuse  varchar( 250 ) NULL DEFAULT NULL
) 
ENGINE=innodb;


INSERT INTO llx_holiday_config (rowid ,name ,value) VALUES (NULL , 'userGroup', NULL);
INSERT INTO llx_holiday_config (rowid ,name ,value) VALUES (NULL , 'lastUpdate', NULL);
INSERT INTO llx_holiday_config (rowid ,name ,value) VALUES (NULL , 'nbUser', NULL);
INSERT INTO llx_holiday_config (rowid ,name ,value) VALUES (NULL , 'delayForRequest', '31');
INSERT INTO llx_holiday_config (rowid ,name ,value) VALUES (NULL , 'AlertValidatorDelay', '0');
INSERT INTO llx_holiday_config (rowid ,name ,value) VALUES (NULL , 'AlertValidatorSolde', '0');
INSERT INTO llx_holiday_config (rowid ,name ,value) VALUES (NULL , 'nbHolidayDeducted', '1');
INSERT INTO llx_holiday_config (rowid ,name ,value) VALUES (NULL , 'nbHolidayEveryMonth', '2.08334');


DELETE FROM llx_document_model WHERE (nom = 'oursin' AND type ='invoice') OR (nom = 'edison' AND type ='order') OR (nom = 'jaune' AND type ='propal');

ALTER TABLE llx_boxes DROP INDEX uk_boxes;
ALTER TABLE llx_boxes ADD COLUMN entity integer NOT NULL DEFAULT 1 AFTER rowid;
ALTER TABLE llx_boxes ADD UNIQUE INDEX uk_boxes (entity, box_id, position, fk_user);
UPDATE llx_boxes as b SET b.entity = (SELECT bd.entity FROM llx_boxes_def as bd WHERE bd.rowid = b.box_id);
