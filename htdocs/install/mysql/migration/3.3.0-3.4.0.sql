--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.4.0 or higher. 
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):   VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres) VPGSQL8.2 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid INTEGER SERIAL PRIMARY KEY;


-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);

create table llx_adherent_type_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;
ALTER TABLE llx_adherent_type_extrafields ADD INDEX idx_adherent_type_extrafields (fk_object);

UPDATE llx_const set value='eldy_menu.php' where value='eldy_backoffice.php';
UPDATE llx_const set value='eldy_menu.php' where value='eldy_frontoffice.php';
UPDATE llx_const set value='auguria_menu.php' where value='auguria_backoffice.php';
UPDATE llx_const set value='auguria_menu.php' where value='auguria_frontoffice.php';
UPDATE llx_const set value='smartphone_menu.php' where value='smartphone_backoffice.php';
UPDATE llx_const set value='smartphone_menu.php' where value='smartphone_frontoffice.php';
UPDATE llx_const set name='MAIN_INFO_SOCIETE_ADDRESS' where name='MAIN_INFO_SOCIETE_ADRESSE';
UPDATE llx_const set name='MAIN_INFO_SOCIETE_TOWN' where name='MAIN_INFO_SOCIETE_VILLE';
UPDATE llx_const set name='MAIN_INFO_SOCIETE_ZIP' where name='MAIN_INFO_SOCIETE_CP';
UPDATE llx_const set name='MAIN_INFO_SOCIETE_COUNTRY' where name='MAIN_INFO_SOCIETE_PAYS';
UPDATE llx_const set name='MAIN_INFO_SOCIETE_STATE' where name='MAIN_INFO_SOCIETE_DEPARTEMENT';

ALTER TABLE llx_user add COLUMN fk_user integer;

-- margin on contracts
alter table llx_contratdet add column fk_product_fournisseur_price integer after info_bits;
alter table llx_contratdet add column buy_price_ht double(24,8) DEFAULT 0 after fk_product_fournisseur_price;

-- serialised array, to store value of select list choices for example
alter table llx_extrafields add column param text after pos;


alter table llx_propal   CHANGE COLUMN fk_adresse_livraison fk_delivery_address integer;
alter table llx_commande CHANGE COLUMN fk_adresse_livraison fk_delivery_address integer;
alter table llx_don      CHANGE COLUMN adresse address text;
alter table llx_don      CHANGE COLUMN ville town text;
alter table llx_don      CHANGE COLUMN prenom firstname varchar(50);
alter table llx_don      CHANGE COLUMN nom lastname varchar(50);
alter table llx_don 	  CHANGE COLUMN cp zip varchar(10);
alter table llx_don      CHANGE COLUMN pays country varchar(50);
alter table llx_adherent CHANGE COLUMN adresse address text;
alter table llx_adherent CHANGE COLUMN nom lastname varchar(50);
alter table llx_adherent CHANGE COLUMN prenom firstname varchar(50);
alter table llx_adherent CHANGE COLUMN ville town text;
alter table llx_adherent CHANGE COLUMN cp zip varchar(10);
alter table llx_adherent CHANGE COLUMN pays country varchar(50);
alter table llx_adherent CHANGE COLUMN fk_departement state_id varchar(50);
alter table llx_bank_account CHANGE COLUMN adresse_proprio owner_address text;
alter table llx_bank_account CHANGE COLUMN fk_departement state_id varchar(50);
alter table llx_mailing_cibles CHANGE COLUMN nom lastname varchar(50);
alter table llx_mailing_cibles CHANGE COLUMN prenom firstname varchar(50);
alter table llx_user     CHANGE COLUMN name lastname varchar(50);
alter table llx_entrepot CHANGE COLUMN ville town text;
alter table llx_entrepot CHANGE COLUMN cp zip varchar(10);
alter table llx_societe  CHANGE COLUMN ville town text;
alter table llx_societe  CHANGE COLUMN cp zip varchar(10);
alter table llx_societe  CHANGE COLUMN tel phone varchar(20);
alter table llx_socpeople  CHANGE COLUMN name lastname varchar(50);
alter table llx_socpeople  CHANGE COLUMN ville town text;
alter table llx_socpeople  CHANGE COLUMN cp zip varchar(10);
alter table llx_societe_rib CHANGE COLUMN adresse_proprio owner_address text;
alter table llx_societe_address CHANGE COLUMN ville town text;
alter table llx_societe_address CHANGE COLUMN cp zip varchar(10);

ALTER TABLE llx_c_shipment_mode ADD COLUMN tracking VARCHAR(256) NOT NULL DEFAULT '' AFTER description;

ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL;
-- VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- VPGSQL8.2 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid INTEGER SERIAL PRIMARY KEY;

ALTER TABLE llx_stock_mouvement MODIFY COLUMN value real;

ALTER TABLE llx_facture ADD COLUMN revenuestamp double(24,8) DEFAULT 0 AFTER localtax2;

CREATE TABLE llx_c_revenuestamp
(
  rowid             integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fk_pays           integer NOT NULL,
  taux              double  NOT NULL,
  note              varchar(128),
  active            tinyint DEFAULT 1 NOT NULL,
  accountancy_code_sell	varchar(15) DEFAULT NULL,
  accountancy_code_buy	varchar(15) DEFAULT NULL
) ENGINE=innodb;

insert into llx_c_revenuestamp(rowid,fk_pays,taux,note,active) values (101, 10, '0.4', 'Timbre fiscal', 1);

ALTER TABLE llx_actioncomm ADD COLUMN code varchar(32) NULL after fk_action;

ALTER TABLE llx_c_tva MODIFY COLUMN localtax1_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_c_tva MODIFY COLUMN localtax2_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_commande_fournisseurdet MODIFY COLUMN localtax1_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_commande_fournisseurdet MODIFY COLUMN localtax2_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_commandedet MODIFY COLUMN localtax1_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_commandedet MODIFY COLUMN localtax2_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_contratdet MODIFY COLUMN localtax1_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_contratdet MODIFY COLUMN localtax2_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_facture_fourn_det MODIFY COLUMN localtax1_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_facture_fourn_det MODIFY COLUMN localtax2_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_facturedet_rec MODIFY COLUMN localtax1_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_facturedet_rec MODIFY COLUMN localtax2_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_facturedet MODIFY COLUMN localtax1_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_facturedet MODIFY COLUMN localtax2_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_propaldet MODIFY COLUMN localtax1_type varchar(10)	NOT NULL DEFAULT '0';
ALTER TABLE llx_propaldet MODIFY COLUMN localtax2_type varchar(10)	NOT NULL DEFAULT '0';

ALTER TABLE llx_holiday ADD COLUMN note text; 
ALTER TABLE llx_holiday ADD COLUMN note_public text;
