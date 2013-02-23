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

ALTER TABLE llx_user add COLUMN fk_user integer;

-- margin on contracts
alter table llx_contratdet add column fk_product_fournisseur_price integer after info_bits;
alter table llx_contratdet add column buy_price_ht double(24,8) DEFAULT 0 after fk_product_fournisseur_price;

-- serialised array, to store value of select list choices for example
alter table llx_extrafields add column param text after pos;


alter table llx_propal   CHANGE COLUMN fk_adresse_livraison fk_delivery_address integer;
alter table llx_commande CHANGE COLUMN fk_adresse_livraison fk_delivery_address integer;
alter table llx_don      CHANGE COLUMN adresse address text;
alter table llx_adherent CHANGE COLUMN adresse address text;

ALTER TABLE llx_c_shipment_mode ADD COLUMN tracking VARCHAR(256) NOT NULL AFTER description;
ALTER TABLE llx_c_shipment_mode MODIFY COLUMN rowid INT(11) NOT NULL AUTO_INCREMENT;

INSERT INTO llx_c_shipment_mode (code,libelle,description,tracking,active) VALUES ('UPS','UPS','United Parcel Service','http://wwwapps.ups.com/etracking/tracking.cgi?InquiryNumber2=&InquiryNumber3=&tracknums_displayed=3&loc=fr_FR&TypeOfInquiryNumber=T&HTMLVersion=4.0&InquiryNumber22=&InquiryNumber32=&track=Track&Suivi.x=64&Suivi.y=7&Suivi=Valider&InquiryNumber1={TRACKID}',0);
INSERT INTO llx_c_shipment_mode (code,libelle,description,tracking,active) VALUES ('KIALA','KIALA','Relais Kiala','http://www.kiala.fr/tnt/delivery/{TRACKID}',0);
INSERT INTO llx_c_shipment_mode (code,libelle,description,tracking,active) VALUES ('GLS','GLS','General Logistics Systems','http://www.gls-group.eu/276-I-PORTAL-WEB/content/GLS/FR01/FR/5004.htm?txtAction=71000&txtRefNo={TRACKID}',0);
INSERT INTO llx_c_shipment_mode (code,libelle,description,tracking,active) VALUES ('CHRONO','Chronopost','Chronopost','http://www.chronopost.fr/expedier/inputLTNumbersNoJahia.do?listeNumeros={TRACKID}',0);
