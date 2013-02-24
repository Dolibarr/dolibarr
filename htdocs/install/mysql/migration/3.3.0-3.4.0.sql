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
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y


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
UPDATE llx_const set value='MAIN_INFO_SOCIETE_ADDRESS' where value='MAIN_INFO_SOCIETE_ADRESSE';
UPDATE llx_const set value='MAIN_INFO_SOCIETE_TOWN' where value='MAIN_INFO_SOCIETE_VILLE';
UPDATE llx_const set value='MAIN_INFO_SOCIETE_ZIP' where value='MAIN_INFO_SOCIETE_CP';

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
alter table llx_don 	 CHANGE COLUMN cp zip varchar(10);
alter table llx_adherent CHANGE COLUMN adresse address text;
alter table llx_adherent CHANGE COLUMN nom lastname varchar(50);
alter table llx_adherent CHANGE COLUMN prenom firstname varchar(50);
alter table llx_adherent CHANGE COLUMN ville town text;
alter table llx_adherent CHANGE COLUMN cp zip varchar(10);
alter table llx_mailing_cibles CHANGE COLUMN nom lastname varchar(50);
alter table llx_mailing_cibles CHANGE COLUMN prenom firstname varchar(50);
alter table llx_user     CHANGE COLUMN name lastname varchar(50);
alter table llx_entrepot CHANGE COLUMN ville town text;
alter table llx_entrepot CHANGE COLUMN cp zip varchar(10);
alter table llx_societe  CHANGE COLUMN ville town text;
alter table llx_societe  CHANGE COLUMN cp zip varchar(10);
alter table llx_socpeople  CHANGE COLUMN name lastname varchar(50);
alter table llx_socpeople  CHANGE COLUMN ville town text;
alter table llx_socpeople  CHANGE COLUMN cp zip varchar(10);
alter table llx_bank_account CHANGE COLUMN adresse_proprio owner_address text;
alter table llx_societe_rib CHANGE COLUMN adresse_proprio owner_address text;
alter table llx_societe_address CHANGE COLUMN ville town text;
alter table llx_societe_address CHANGE COLUMN cp zip varchar(10);

ALTER TABLE llx_c_shipment_mode ADD COLUMN tracking VARCHAR(256) NOT NULL AFTER description;
ALTER TABLE llx_c_shipment_mode MODIFY COLUMN rowid INT(11) NOT NULL AUTO_INCREMENT;
