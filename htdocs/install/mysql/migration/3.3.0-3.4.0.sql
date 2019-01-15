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
-- To make pk to be auto increment (postgres) VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE

-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


ALTER TABLE llx_menu MODIFY COLUMN leftmenu varchar(100);

create table llx_adherent_type_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;
ALTER TABLE llx_adherent_type_extrafields ADD INDEX idx_adherent_type_extrafields (fk_object);

UPDATE llx_const set value = __ENCRYPT('eldy_menu.php')__ where __DECRYPT('value')__ = 'eldy_backoffice.php';
UPDATE llx_const set value = __ENCRYPT('eldy_menu.php')__ where __DECRYPT('value')__ = 'eldy_frontoffice.php';
UPDATE llx_const set value = __ENCRYPT('auguria_menu.php')__ where __DECRYPT('value')__ = 'auguria_backoffice.php';
UPDATE llx_const set value = __ENCRYPT('auguria_menu.php')__ where __DECRYPT('value')__ = 'auguria_frontoffice.php';
UPDATE llx_const set value = __ENCRYPT('smartphone_menu.php')__ where __DECRYPT('value')__ = 'smartphone_backoffice.php';
UPDATE llx_const set value = __ENCRYPT('smartphone_menu.php')__ where __DECRYPT('value')__ = 'smartphone_frontoffice.php';
UPDATE llx_const set name = __ENCRYPT('MAIN_INFO_SOCIETE_ADDRESS')__ where __DECRYPT('name')__ = 'MAIN_INFO_SOCIETE_ADRESSE';
UPDATE llx_const set name = __ENCRYPT('MAIN_INFO_SOCIETE_TOWN')__ where __DECRYPT('name')__ = 'MAIN_INFO_SOCIETE_VILLE';
UPDATE llx_const set name = __ENCRYPT('MAIN_INFO_SOCIETE_ZIP')__ where __DECRYPT('name')__ = 'MAIN_INFO_SOCIETE_CP';
UPDATE llx_const set name = __ENCRYPT('MAIN_INFO_SOCIETE_COUNTRY')__ where __DECRYPT('name')__ = 'MAIN_INFO_SOCIETE_PAYS';
UPDATE llx_const set name = __ENCRYPT('MAIN_INFO_SOCIETE_STATE')__ where __DECRYPT('name')__ = 'MAIN_INFO_SOCIETE_DEPARTEMENT';
UPDATE llx_const set name = __ENCRYPT('LIVRAISON_ADDON_NUMBER')__ where __DECRYPT('name')__ = 'LIVRAISON_ADDON';

ALTER TABLE llx_user add COLUMN fk_user integer;

-- margin on contracts
alter table llx_contratdet add column fk_product_fournisseur_price integer after info_bits;
alter table llx_contratdet add column buy_price_ht double(24,8) DEFAULT 0 after fk_product_fournisseur_price;

-- serialised array, to store value of select list choices for example
alter table llx_extrafields add column param text after pos;

-- numbering on supplier invoice
ALTER TABLE llx_facture_fourn ADD COLUMN ref varchar(30) after rowid;
ALTER TABLE llx_facture_fourn MODIFY COLUMN ref varchar(30);
ALTER TABLE llx_facture_fourn DROP INDEX uk_facture_fourn;
ALTER TABLE llx_facture_fourn DROP INDEX uk_facture_fourn_ref;
UPDATE llx_facture_fourn set ref = NULL where ref = '';
ALTER TABLE llx_facture_fourn ADD UNIQUE INDEX uk_facture_fourn_ref (ref, entity);
ALTER TABLE llx_facture_fourn CHANGE COLUMN facnumber ref_supplier varchar(50);
ALTER TABLE llx_facture_fourn ADD UNIQUE INDEX uk_facture_fourn_ref_supplier (ref_supplier, fk_soc, entity);


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
alter table llx_adherent CHANGE COLUMN naiss birth date;
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


-- remove constraint and index before rename field
ALTER TABLE llx_expedition DROP FOREIGN KEY fk_expedition_fk_expedition_methode;
ALTER TABLE llx_expedition DROP FOREIGN KEY fk_expedition_fk_shipping_method;
ALTER TABLE llx_expedition DROP INDEX idx_expedition_fk_expedition_methode;
ALTER TABLE llx_expedition CHANGE COLUMN fk_expedition_methode fk_shipping_method integer;

ALTER TABLE llx_c_shipment_mode ADD COLUMN tracking VARCHAR(255) NOT NULL DEFAULT '' AFTER description;

--ALTER TABLE llx_c_shipment_mode DROP COLUMN CASCADE;
--ALTER TABLE llx_c_shipment_mode ADD COLUMN rowid INTEGER AUTO_INCREMENT PRIMARY KEY;
--ALTER TABLE llc_c_shipment_mode ADD COLUMN rowid SERIAL PRIMARY KEY;
--ALTER TABLE llx_c_shipment_mode ADD COLUMN rowid INTEGER AUTO_INCREMENT PRIMARY KEY;

-- VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- VPGSQL8.2 DROP table llx_c_shipment_mode;
-- VPGSQL8.2 CREATE TABLE llx_c_shipment_mode (rowid SERIAL PRIMARY KEY, tms timestamp, code varchar(30) NOT NULL, libelle varchar(50) NOT NULL, description text, tracking varchar(256) NOT NULL, active integer DEFAULT 0, module varchar(32) NULL);
-- VPGSQL8.2 INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (1,'CATCH','Catch','Catch by client','',1);
-- VPGSQL8.2 INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (2,'TRANS','Transporter','Generic transporter','',1);
-- VPGSQL8.2 INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (3,'COLSUI','Colissimo Suivi','Colissimo Suivi','',0);
-- VPGSQL8.2 INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (4,'LETTREMAX','Lettre Max','Courrier Suivi et Lettre Max','',0);
-- VPGSQL8.2 INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (5,'UPS','UPS','United Parcel Service','http://wwwapps.ups.com/etracking/tracking.cgi?InquiryNumber2=&InquiryNumber3=&tracknums_displayed=3&loc=fr_FR&TypeOfInquiryNumber=T&HTMLVersion=4.0&InquiryNumber22=&InquiryNumber32=&track=Track&Suivi.x=64&Suivi.y=7&Suivi=Valider&InquiryNumber1={TRACKID}',0);
-- VPGSQL8.2 INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (6,'KIALA','KIALA','Relais Kiala','http://www.kiala.fr/tnt/delivery/{TRACKID}',0);
-- VPGSQL8.2 INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (7,'GLS','GLS','General Logistics Systems','http://www.gls-group.eu/276-I-PORTAL-WEB/content/GLS/FR01/FR/5004.htm?txtAction=71000&txtRefNo={TRACKID}',0);
-- VPGSQL8.2 INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (8,'CHRONO','Chronopost','Chronopost','http://www.chronopost.fr/expedier/inputLTNumbersNoJahia.do?listeNumeros={TRACKID}',0);

-- and create the new index and constraint
ALTER TABLE llx_expedition ADD INDEX idx_expedition_fk_shipping_method (fk_shipping_method);
ALTER TABLE llx_expedition ADD CONSTRAINT fk_expedition_fk_shipping_method FOREIGN KEY (fk_shipping_method) REFERENCES llx_c_shipment_mode (rowid);



ALTER TABLE llx_stock_mouvement MODIFY COLUMN value real;

create table llx_propal_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;
ALTER TABLE llx_propal_extrafields ADD INDEX idx_propal_extrafields (fk_object);

create table llx_facture_extrafields
(
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  tms timestamp,
  fk_object integer NOT NULL,
  import_key varchar(14) -- import key
) ENGINE=innodb;
ALTER TABLE llx_facture_extrafields ADD INDEX idx_facture_extrafields (fk_object);
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

ALTER TABLE llx_c_tva MODIFY COLUMN localtax1_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_c_tva MODIFY COLUMN localtax2_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_commande_fournisseurdet MODIFY COLUMN localtax1_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_commande_fournisseurdet MODIFY COLUMN localtax2_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_commandedet MODIFY COLUMN localtax1_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_commandedet MODIFY COLUMN localtax2_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_contratdet MODIFY COLUMN localtax1_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_contratdet MODIFY COLUMN localtax2_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_facture_fourn_det MODIFY COLUMN localtax1_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_facture_fourn_det MODIFY COLUMN localtax2_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_facturedet_rec MODIFY COLUMN localtax1_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_facturedet_rec MODIFY COLUMN localtax2_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_facturedet MODIFY COLUMN localtax1_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_facturedet MODIFY COLUMN localtax2_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_propaldet MODIFY COLUMN localtax1_type varchar(10) DEFAULT NULL;
ALTER TABLE llx_propaldet MODIFY COLUMN localtax2_type varchar(10) DEFAULT NULL;
-- No more use type 7, use revenuse stamp instead
UPDATE llx_c_tva set localtax1=0, localtax1_type='0' where localtax1_type = '7';
UPDATE llx_c_tva set localtax2=0, localtax2_type='0' where localtax2_type = '7';

ALTER TABLE llx_facture_fourn_det ADD COLUMN info_bits integer NOT NULL DEFAULT 0 after date_end;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN info_bits integer NOT NULL DEFAULT 0 after tva_tx;

ALTER TABLE llx_actioncomm ADD COLUMN code varchar(32) NULL after fk_action;


ALTER TABLE llx_holiday ADD COLUMN note text; 
ALTER TABLE llx_holiday ADD COLUMN note_public text;

-- Add new trigger on Invoice BILL_UNVALIDATE + Index 
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (28,'BILL_UNVALIDATE','Customer invoice unvalidated','Executed when a customer invoice status set back to draft','facture',10);
ALTER TABLE llx_c_action_trigger ADD INDEX idx_action_trigger_rang (rang); 


ALTER TABLE llx_facture_fourn_det ADD COLUMN fk_code_ventilation integer DEFAULT 0 NOT NULL;
ALTER TABLE llx_facturedet DROP COLUMN fk_export_compta;

CREATE TABLE llx_cronjob 
(
	rowid 			integer AUTO_INCREMENT PRIMARY KEY,
	tms 			timestamp,
	datec 			datetime,
	jobtype			varchar(10) NOT NULL,
  	label 			text NOT NULL,
	command			varchar(255),
  	classesname 		varchar(255),
  	objectname		varchar(255),
  	methodename		varchar(255),
  	params 			text NOT NULL,
	md5params 		varchar(32),
  	module_name 		varchar(255),
  	priority 		integer DEFAULT 0,
  	datelastrun 		datetime,
  	datenextrun 		datetime,
  	datestart		datetime,
  	dateend			datetime,
  	datelastresult      	datetime,
  	lastresult      	text,
  	lastoutput      	text,
  	unitfrequency	 	integer NOT NULL DEFAULT 0,
  	frequency 		integer NOT NULL DEFAULT 0,
	nbrun			integer,
  	status 			integer NOT NULL DEFAULT 1,
  	fk_user_author 		integer DEFAULT NULL,
  	fk_user_mod 		integer DEFAULT NULL,
	note text
)ENGINE=innodb;


ALTER TABLE llx_societe MODIFY COLUMN zip varchar(25);

ALTER TABLE llx_user ADD COLUMN address           varchar(255);
ALTER TABLE llx_user ADD COLUMN zip               varchar(25);
ALTER TABLE llx_user ADD COLUMN town              varchar(50);
ALTER TABLE llx_user ADD COLUMN fk_state          integer        DEFAULT 0;
ALTER TABLE llx_user ADD COLUMN fk_country        integer        DEFAULT 0;
ALTER TABLE llx_user ADD COLUMN color             varchar(6);

ALTER TABLE llx_product_price ADD COLUMN import_key varchar(14) AFTER price_by_qty;

DROP TABLE llx_printer_ipp;
CREATE TABLE llx_printer_ipp 
(
	rowid integer AUTO_INCREMENT PRIMARY KEY,
	tms 	timestamp,
	datec 	datetime,
	printer_name text NOT NULL, 
	printer_location text NOT NULL,
	printer_uri varchar(255) NOT NULL,
	copy integer NOT NULL DEFAULT '1',
	module varchar(16) NOT NULL,
	login varchar(32) NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_socpeople ADD COLUMN ref_ext varchar(128) after entity;
ALTER TABLE llx_adherent MODIFY COLUMN ref_ext varchar(128);

create table llx_commande_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)
) ENGINE=innodb;
ALTER TABLE llx_commande_extrafields ADD INDEX idx_commande_extrafields (fk_object);

ALTER TABLE llx_socpeople ADD COLUMN note_public text after note;
ALTER TABLE llx_societe ADD COLUMN note_public text after note;

ALTER TABLE llx_actioncomm ADD COLUMN transparency integer after fk_user_action;

INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (29,'FICHINTER_SENTBYMAIL','Intervention sent by mail','Executed when a intervention is sent by mail','ficheinter',29);

ALTER TABLE llx_adherent ADD COLUMN canvas varchar(32) after fk_user_valid; 

ALTER TABLE llx_expedition CHANGE COLUMN note note_private text;
ALTER TABLE llx_expedition ADD COLUMN note_public text after note_private;
ALTER TABLE llx_livraison CHANGE COLUMN note note_private text;
ALTER TABLE llx_facture CHANGE COLUMN note note_private text;
ALTER TABLE llx_commande CHANGE COLUMN note note_private text;
ALTER TABLE llx_propal CHANGE COLUMN note note_private text;
ALTER TABLE llx_commande_fournisseur CHANGE COLUMN note note_private text;
ALTER TABLE llx_contrat CHANGE COLUMN note note_private text;
ALTER TABLE llx_deplacement CHANGE COLUMN note note_private text;
ALTER TABLE llx_don CHANGE COLUMN note note_private text;
ALTER TABLE llx_facture_fourn CHANGE COLUMN note note_private text;
ALTER TABLE llx_facture_rec CHANGE COLUMN note note_private text;
ALTER TABLE llx_holiday CHANGE COLUMN note note_private text;
ALTER TABLE llx_societe CHANGE COLUMN note note_private text;
ALTER TABLE llx_socpeople CHANGE COLUMN note note_private text;

create table llx_projet_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;
ALTER TABLE llx_projet_extrafields ADD INDEX idx_projet_extrafields (fk_object);

create table llx_projet_task_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;
ALTER TABLE llx_projet_task_extrafields ADD INDEX idx_projet_task_extrafields (fk_object);


CREATE TABLE llx_opensurvey_comments (
    id_comment INTEGER unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_sondage CHAR(16) NOT NULL,
    comment text NOT NULL,
    tms timestamp,
    usercomment text
) ENGINE=InnoDB;

CREATE TABLE llx_opensurvey_sondage (
       id_sondage VARCHAR(16) PRIMARY KEY,
       id_sondage_admin CHAR(24),
       commentaires text,
       mail_admin VARCHAR(128),
       nom_admin VARCHAR(64),
       titre text,
       date_fin datetime,
       format VARCHAR(2),
       mailsonde varchar(2) DEFAULT '0',
       survey_link_visible integer DEFAULT 1,
	   canedit integer DEFAULT 0,
       origin varchar(64),
       tms timestamp,
	   sujet TEXT
) ENGINE=InnoDB;
CREATE TABLE llx_opensurvey_user_studs (
    id_users INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(64) NOT NULL,
    id_sondage VARCHAR(16) NOT NULL,
    reponses VARCHAR(100) NOT NULL,
    tms timestamp
) ENGINE=InnoDB;

ALTER TABLE llx_opensurvey_sondage ADD COLUMN id_sondage_admin CHAR(24);

ALTER TABLE llx_opensurvey_comments ADD INDEX idx_id_comment (id_comment);
ALTER TABLE llx_opensurvey_comments ADD INDEX idx_id_sondage (id_sondage);
ALTER TABLE llx_opensurvey_sondage ADD INDEX idx_id_sondage_admin (id_sondage_admin);
ALTER TABLE llx_opensurvey_sondage ADD INDEX idx_date_fin (date_fin);
ALTER TABLE llx_opensurvey_user_studs ADD INDEX idx_opensurvey_user_studs_id_users (id_users);
ALTER TABLE llx_opensurvey_user_studs ADD INDEX idx_opensurvey_user_studs_nom (nom);
ALTER TABLE llx_opensurvey_user_studs ADD INDEX idx_opensurvey_user_studs_id_sondage (id_sondage);

ALTER TABLE llx_boxes ADD COLUMN params varchar(255);

UPDATE llx_extrafields SET elementtype='socpeople' WHERE elementtype='contact';
UPDATE llx_extrafields SET elementtype='actioncomm' WHERE elementtype='action';
UPDATE llx_extrafields SET elementtype='adherent' WHERE elementtype='member';
UPDATE llx_extrafields SET elementtype='societe' WHERE elementtype='company';

create table llx_commande_fournisseur_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)
) ENGINE=innodb;
ALTER TABLE llx_commande_fournisseur_extrafields ADD INDEX idx_commande_fournisseur_extrafields (fk_object);

create table llx_facture_fourn_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;
ALTER TABLE llx_facture_fourn_extrafields ADD INDEX idx_facture_fourn_extrafields (fk_object);

ALTER TABLE llx_user_clicktodial ADD COLUMN url varchar(255) AFTER fk_user;

ALTER TABLE llx_fichinterdet ADD COLUMN fk_parent_line integer NULL AFTER fk_fichinter;

ALTER TABLE llx_societe_address CHANGE COLUMN tel phone varchar(20);

insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,localtax1,localtax1_type,note,active) values (143, 14,'5','0','9.975','1','TPS and TVQ rate',1);

DELETE FROM llx_document_model WHERE nom ='elevement' AND type='delivery';
DELETE FROM llx_document_model WHERE nom ='' AND type='delivery';
