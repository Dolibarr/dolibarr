--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.8.0 or higher. 
--

-- Add unique key
ALTER TABLE llx_product_stock ADD UNIQUE INDEX uk_product_stock (fk_product,fk_entrepot);

ALTER TABLE llx_product_stock drop column location;

-- Add missing table llx_product_association
create table llx_product_association
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  fk_product_pere       integer NOT NULL DEFAULT 0,
  fk_product_fils       integer NOT NULL DEFAULT 0,
  qty                   double NULL
)ENGINE=innodb;


ALTER TABLE llx_product_association ADD UNIQUE INDEX uk_product_association (fk_product_pere, fk_product_fils);

ALTER TABLE llx_product_association ADD INDEX idx_product_association_fils (fk_product_fils);



ALTER TABLE llx_product ADD INDEX idx_product_label (label);

-- V4.1 DELETE FROM llx_projet_task WHERE fk_projet NOT IN (SELECT rowid from llx_projet);
-- V4.1 UPDATE llx_projet_task set fk_user_creat=NULL WHERE fk_user_creat IS NOT NULL AND fk_user_creat NOT IN (SELECT rowid from llx_user);
-- V4.1 UPDATE llx_projet_task set fk_user_valid=NULL WHERE fk_user_valid IS NOT NULL AND fk_user_valid NOT IN (SELECT rowid from llx_user);

ALTER table llx_bank_account ADD COLUMN fk_pays        integer        DEFAULT 0 NOT NULL after domiciliation;
ALTER TABLE llx_bank_account ADD COLUMN fk_departement integer        DEFAULT NULL after domiciliation;
ALTER TABLE llx_socpeople ADD COLUMN fk_departement integer        DEFAULT NULL after ville;
ALTER TABLE llx_adherent  ADD COLUMN fk_departement integer        DEFAULT NULL after ville;
ALTER TABLE llx_entrepot  ADD COLUMN fk_departement integer        DEFAULT NULL after ville;

ALTER TABLE llx_bookmark ADD COLUMN position integer        DEFAULT 0;

-- Rename llx_product_det
ALTER TABLE llx_product_det RENAME TO llx_product_lang;
ALTER TABLE llx_product_lang ADD UNIQUE INDEX uk_product_lang (fk_product, lang);
-- V4.1 DELETE FROM llx_product_lang WHERE fk_product NOT IN (SELECT rowid from llx_product);
ALTER TABLE llx_product_lang ADD CONSTRAINT fk_product_lang_fk_product 	FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);

ALTER TABLE llx_product ADD COLUMN virtual tinyint DEFAULT 0 NOT NULL AFTER tms;
ALTER TABLE llx_product ADD COLUMN fk_parent integer DEFAULT 0 AFTER virtual;

alter table llx_societe add column   default_lang   varchar(6) after price_level;
alter table llx_socpeople add column   default_lang   varchar(6) after note;


alter table llx_mailing add column   joined_file1       varchar(255);
alter table llx_mailing add column   joined_file2       varchar(255);
alter table llx_mailing add column   joined_file3       varchar(255);
alter table llx_mailing add column   joined_file4       varchar(255);

update llx_facture_fourn set fk_statut=2 where fk_statut=1 AND paye=1;

alter table llx_facture_fourn add column close_code          varchar(16) after remise;
alter table llx_facture_fourn add column close_note          varchar(128) after close_code;

-- Add local taxes
alter table llx_facture add column localtax1 double(24,8) DEFAULT 0 after tva;
alter table llx_facture add column localtax2 double(24,8) DEFAULT 0 after localtax1;
alter table llx_facturedet add column localtax1_tx double(6,3) DEFAULT 0 after tva_tx;
alter table llx_facturedet add column localtax2_tx double(6,3) DEFAULT 0 after localtax1_tx;
alter table llx_facturedet add column total_localtax1 double(24,8) DEFAULT 0 after total_tva;
alter table llx_facturedet add column total_localtax2 double(24,8) DEFAULT 0 after total_localtax1;

alter table llx_facture_rec add column localtax1 double(24,8) DEFAULT 0 after tva;
alter table llx_facture_rec add column localtax2 double(24,8) DEFAULT 0 after localtax1;
alter table llx_facturedet_rec add column localtax1_tx double(6,3) DEFAULT 0 after tva_tx;
alter table llx_facturedet_rec add column localtax2_tx double(6,3) DEFAULT 0 after localtax1_tx;
alter table llx_facturedet_rec add column total_localtax1 double(24,8) DEFAULT 0 after total_tva;
alter table llx_facturedet_rec add column total_localtax2 double(24,8) DEFAULT 0 after total_localtax1;

alter table llx_c_tva add column localtax1 double NOT NULL DEFAULT 0 after taux;
alter table llx_c_tva add column localtax2 double NOT NULL DEFAULT 0 after localtax1;

alter table llx_propal add column localtax1 double(24,8) DEFAULT 0 after tva;
alter table llx_propal add column localtax2 double(24,8) DEFAULT 0 after localtax1;
alter table llx_propaldet add column localtax1_tx double(6,3) DEFAULT 0 after tva_tx;
alter table llx_propaldet add column localtax2_tx double(6,3) DEFAULT 0 after localtax1_tx;
alter table llx_propaldet add column total_localtax1 double(24,8) DEFAULT 0 after total_tva;
alter table llx_propaldet add column total_localtax2 double(24,8) DEFAULT 0 after total_localtax1;

alter table llx_commande add column localtax1 double(24,8) DEFAULT 0 after tva;
alter table llx_commande add column localtax2 double(24,8) DEFAULT 0 after localtax1;
alter table llx_commandedet add column localtax1_tx double(6,3) DEFAULT 0 after tva_tx;
alter table llx_commandedet add column localtax2_tx double(6,3) DEFAULT 0 after localtax1_tx;
alter table llx_commandedet add column total_localtax1 double(24,8) DEFAULT 0 after total_tva;
alter table llx_commandedet add column total_localtax2 double(24,8) DEFAULT 0 after total_localtax1;

alter table llx_commande_fournisseur add column localtax1 double(24,8) DEFAULT 0 after tva;
alter table llx_commande_fournisseur add column localtax2 double(24,8) DEFAULT 0 after localtax1;
alter table llx_commande_fournisseurdet add column localtax1_tx double(6,3) DEFAULT 0 after tva_tx;
alter table llx_commande_fournisseurdet add column localtax2_tx double(6,3) DEFAULT 0 after localtax1_tx;
alter table llx_commande_fournisseurdet add column total_localtax1 double(24,8) DEFAULT 0 after total_tva;
alter table llx_commande_fournisseurdet add column total_localtax2 double(24,8) DEFAULT 0 after total_localtax1;

alter table llx_facture_fourn add column localtax1 double(24,8) DEFAULT 0 after tva;
alter table llx_facture_fourn add column localtax2 double(24,8) DEFAULT 0 after localtax1;
alter table llx_facture_fourn_det add column localtax1_tx double(6,3) DEFAULT 0 after tva_tx;
alter table llx_facture_fourn_det add column localtax2_tx double(6,3) DEFAULT 0 after localtax1_tx;
alter table llx_facture_fourn_det add column total_localtax1 double(24,8) DEFAULT 0 after tva;
alter table llx_facture_fourn_det add column total_localtax2 double(24,8) DEFAULT 0 after total_localtax1;

alter table llx_product add column localtax1_tx double(6,3) DEFAULT 0 after tva_tx;
alter table llx_product add column localtax2_tx double(6,3) DEFAULT 0 after localtax1_tx;
alter table llx_product_price add column localtax1_tx double(6,3) DEFAULT 0 after tva_tx;
alter table llx_product_price add column localtax2_tx double(6,3) DEFAULT 0 after localtax1_tx;

alter table llx_contratdet add column localtax1_tx double(6,3) DEFAULT 0 after tva_tx;
alter table llx_contratdet add column localtax2_tx double(6,3) DEFAULT 0 after localtax1_tx;
alter table llx_contratdet add column total_localtax1 double(24,8) DEFAULT 0 after total_tva;
alter table llx_contratdet add column total_localtax2 double(24,8) DEFAULT 0 after total_localtax1;

alter table llx_product add column   hidden             tinyint      DEFAULT 0;

alter table llx_product add column   length             float        DEFAULT NULL after weight_units;
alter table llx_product add column   length_units       tinyint      DEFAULT NULL after length;
alter table llx_product add column   surface            float        DEFAULT NULL after length_units;
alter table llx_product add column   surface_units      tinyint      DEFAULT NULL after surface;

alter table llx_product add column   accountancy_code_sell       varchar(15) after fk_barcode_type;
alter table llx_product add column   accountancy_code_buy        varchar(15) after accountancy_code_sell;

ALTER TABLE llx_product drop column stock_loc;
ALTER TABLE llx_product_stock add column location        varchar(32);

ALTER TABLE llx_expedition DROP FOREIGN KEY fk_expedition_fk_adresse_livraison;
ALTER TABLE llx_expedition DROP INDEX idx_expedition_fk_adresse_livraison;
ALTER TABLE llx_expedition ADD COLUMN ref_customer varchar(30) AFTER entity;
ALTER TABLE llx_expedition ADD COLUMN date_delivery date DEFAULT NULL AFTER date_expedition;
ALTER TABLE llx_expedition CHANGE COLUMN fk_adresse_livraison fk_address integer DEFAULT NULL;

ALTER TABLE llx_livraison DROP FOREIGN KEY fk_livraison_fk_adresse_livraison;
ALTER TABLE llx_livraison DROP INDEX idx_livraison_fk_adresse_livraison;
ALTER TABLE llx_livraison change ref_client ref_customer varchar(30);
ALTER TABLE llx_livraison change date_livraison date_delivery date		DEFAULT NULL;
ALTER TABLE llx_livraison CHANGE COLUMN fk_adresse_livraison fk_address integer DEFAULT NULL;

ALTER TABLE llx_c_actioncomm MODIFY libelle    varchar(48) NOT NULL;

ALTER TABLE llx_facture MODIFY tva double(24,8) DEFAULT 0;
ALTER TABLE llx_facture MODIFY total double(24,8) DEFAULT 0;
ALTER TABLE llx_facture MODIFY total_ttc double(24,8) DEFAULT 0;
ALTER TABLE llx_facture MODIFY amount double(24,8) DEFAULT 0 NOT NULL;

ALTER TABLE llx_facturedet MODIFY tva_tx double(6,3);
ALTER TABLE llx_facturedet MODIFY subprice double(24,8);
ALTER TABLE llx_facturedet MODIFY price double(24,8);
ALTER TABLE llx_facturedet MODIFY total_ht double(24,8);
ALTER TABLE llx_facturedet MODIFY total_tva double(24,8);
ALTER TABLE llx_facturedet MODIFY total_ttc double(24,8);

ALTER TABLE llx_facture_rec MODIFY tva double(24,8) DEFAULT 0;
ALTER TABLE llx_facture_rec MODIFY total double(24,8) DEFAULT 0;
ALTER TABLE llx_facture_rec MODIFY total_ttc double(24,8) DEFAULT 0;
ALTER TABLE llx_facture_rec MODIFY amount double(24,8) DEFAULT 0 NOT NULL;

ALTER TABLE llx_facturedet_rec MODIFY tva_tx double(6,3);
ALTER TABLE llx_facturedet_rec MODIFY subprice double(24,8);
ALTER TABLE llx_facturedet_rec MODIFY price double(24,8);
ALTER TABLE llx_facturedet_rec MODIFY total_ht double(24,8);
ALTER TABLE llx_facturedet_rec MODIFY total_tva double(24,8);
ALTER TABLE llx_facturedet_rec MODIFY total_ttc double(24,8);


ALTER TABLE llx_adherent ADD COLUMN civilite varchar(6) after entity;

ALTER TABLE llx_deplacement ADD COLUMN fk_projet integer DEFAULT 0 after fk_soc;

-- Custom list
DROP TABLE llx_c_field_list;
create table llx_c_field_list
(
  rowid			integer  AUTO_INCREMENT PRIMARY KEY,
  tms			timestamp,
  element		varchar(64)        			NOT NULL,
  entity		integer			DEFAULT 1 	NOT NULL,
  name			varchar(32)        			NOT NULL,
  alias			varchar(32)					NOT NULL,
  title			varchar(32)        			NOT NULL,
  align			varchar(6)		DEFAULT 'left',
  sort			tinyint 		DEFAULT 1  	NOT NULL,
  search		tinyint 		DEFAULT 0  	NOT NULL,
  enabled       varchar(255)	DEFAULT 1,
  rang      	integer 		DEFAULT 0
)ENGINE=innodb;

INSERT INTO llx_c_field_list (rowid, element, entity, name, alias, title, align, sort, search, enabled, rang) VALUES
(1, 'product_default', 1, 'p.ref', 'ref', 'Ref', 'left', 1, 1, '1', 1),
(2, 'product_default', 1, 'p.label', 'label', 'Label', 'left', 1, 1, '1', 2),
(3, 'product_default', 1, 'p.barcode', 'barcode', 'BarCode', 'center', 1, 1, '$conf->barcode->enabled', 3),
(4, 'product_default', 1, 'p.tms', 'datem', 'DateModification', 'center', 1, 0, '1', 4),
(5, 'product_default', 1, 'p.price', 'price', 'SellingPriceHT', 'right', 1, 0, '1', 5),
(6, 'product_default', 1, 'p.price_ttc', 'price_ttc', 'SellingPriceTTC', 'right', 1, 0, '1', 6),
(7, 'product_default', 1, 'p.stock', 'stock', 'Stock', 'right', 0, 0, '$conf->stock->enabled', 7),
(8, 'product_default', 1, 'p.envente', 'status', 'Status', 'right', 1, 0, '1', 8);


UPDATE llx_adherent SET pays = null where pays <= '0' and pays != '0';
ALTER table llx_adherent MODIFY pays integer;

-- Drop old tables
DROP TABLE llx_projet_milestone;
ALTER TABLE llx_projet drop column fk_milestone;

ALTER TABLE llx_deplacement ADD COLUMN fk_statut INTEGER DEFAULT 1  NOT NULL after type;

drop table llx_appro;

ALTER TABLE llx_events MODIFY COLUMN user_agent     varchar(255) NULL;

create table llx_categorie_member
(
  fk_categorie  integer NOT NULL,
  fk_member     integer NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_categorie_member ADD PRIMARY KEY (fk_categorie, fk_member);
ALTER TABLE llx_categorie_member ADD INDEX idx_categorie_member_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_member ADD INDEX idx_categorie_member_fk_member (fk_member);

ALTER TABLE llx_categorie_member ADD CONSTRAINT fk_categorie_member_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_member ADD CONSTRAINT fk_categorie_member_member_rowid   FOREIGN KEY (fk_member) REFERENCES llx_adherent (rowid);

ALTER TABLE llx_product ADD COLUMN    canvas varchar(32) DEFAULT 'default@product';
ALTER TABLE llx_product MODIFY COLUMN canvas varchar(32) DEFAULT 'default@product';
UPDATE llx_product SET canvas = 'default@product' WHERE fk_product_type = 0 AND (canvas = '' OR canvas = 'default');
UPDATE llx_product SET canvas = 'service@product' WHERE fk_product_type = 1 AND (canvas = '' OR canvas = 'service');
UPDATE llx_product SET canvas = 'livre@droitpret' WHERE canvas = 'livre';
UPDATE llx_product SET canvas = 'livrecontrat@droitpret' WHERE canvas = 'livrecontrat';
UPDATE llx_product SET canvas = 'livrecouverture@droitpret' WHERE canvas = 'livrecouverture';


ALTER TABLE llx_menu DROP INDEX idx_menu_uk_menu; 

ALTER TABLE llx_menu ADD UNIQUE INDEX idx_menu_uk_menu (menu_handler, fk_menu, position, url, entity);

UPDATE llx_const SET name = 'MAIN_MODULE_PRODUCT' WHERE name = 'MAIN_MODULE_PRODUIT';

UPDATE llx_expedition set ref_customer = NULL where ref_customer = '';

-- Add more predefined action codes --
insert into llx_c_actioncomm (id, code, type, libelle, module) values (30, 'AC_SUP_ORD',  'system', 'Send supplier order by email'        ,'supplier_order');
insert into llx_c_actioncomm (id, code, type, libelle, module) values (31, 'AC_SUP_INV',  'system', 'Send supplier invoice by email'      ,'supplier_invoice');

-- Rename llx_societe_adresse_livraison
ALTER TABLE llx_societe_adresse_livraison RENAME TO llx_societe_address;
ALTER TABLE llx_societe_address CHANGE COLUMN nom name varchar(60);
ALTER TABLE llx_societe_address CHANGE COLUMN fk_societe fk_soc integer DEFAULT 0;

-- Add new spanish VAT from July 2010
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,localtax1,note,active) values ( 45, 4,  '18','0','4','VAT standard rate from July 2010',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,localtax1,note,active) values ( 46, 4,   '8','0','1','VAT reduced rate from July 2010',1);

-- Add Argentina Data
-- Regions Argentina (id pays=23)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (2301, 23, 2301, '', 0, 'Norte', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (2302, 23, 2302, '', 0, 'Litoral', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (2303, 23, 2303, '', 0, 'Cuyana', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (2304, 23, 2304, '', 0, 'Central', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (2305, 23, 2305, '', 0, 'Patagonia', 1);

-- Provinces Argentina
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2301', 2301, '', 01, 'CATAMARCA', 'Catamarca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2302', 2301, '', 02, 'YUJUY', 'Yujuy', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2303', 2301, '', 03, 'TUCAMAN', 'Tucamán', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2304', 2301, '', 04, 'SANTIAGO DEL ESTERO', 'Santiago del Estero', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2305', 2301, '', 05, 'SALTA', 'Salta', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2306', 2302, '', 06, 'CHACO', 'Chaco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2307', 2302, '', 07, 'CORRIENTES', 'Corrientes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2308', 2302, '', 08, 'ENTRE RIOS', 'Entre Ríos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2309', 2302, '', 09, 'FORMOSA MISIONES', 'Formosa Misiones', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2310', 2302, '', 10, 'SANTA FE', 'Santa Fe', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2311', 2303, '', 11, 'LA RIOJA', 'La Rioja', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2312', 2303, '', 12, 'MENDOZA', 'Mendoza', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2313', 2303, '', 13, 'SAN JUAN', 'San Juan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2314', 2303, '', 14, 'SAN LUIS', 'San Luis', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2315', 2304, '', 15, 'CORDOBA', 'Córdoba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2316', 2304, '', 16, 'BUENOS AIRES', 'Buenos Aires', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2317', 2304, '', 17, 'CABA', 'Caba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2318', 2305, '', 18, 'LA PAMPA', 'La Pampa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2319', 2305, '', 19, 'NEUQUEN', 'Neuquén', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2320', 2305, '', 20, 'RIO NEGRO', 'Río Negro', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2321', 2305, '', 21, 'CHUBUT', 'Chubut', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2322', 2305, '', 22, 'SANTA CRUZ', 'Santa Cruz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2323', 2305, '', 23, 'TIERRA DEL FUEGO', 'Tierra del Fuego', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2324', 2305, '', 24, 'ISLAS MALVINAS', 'Islas Malvinas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2325', 2305, '', 25, 'ANTARTIDA', 'Antártida', 1);

-- Juridical status Argentina
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2301', 'Monotributista', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2302', 'Sociedad Civil', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2303', 'Sociedades Comerciales', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2304', 'Sociedades de Hecho', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2305', 'Sociedades Irregulares', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2306', 'Sociedad Colectiva', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2307', 'Sociedad en Comandita Simple', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2308', 'Sociedad de Capital e Industria', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2309', 'Sociedad Accidental o en participación', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2310', 'Sociedad de Responsabilidad Limitada', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2311', 'Sociedad Anónima', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2312', 'Sociedad Anónima con Participación Estatal Mayoritaria', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2313', 'Sociedad en Comandita por Acciones (arts. 315 a 324, LSC)', 1);


DELETE from llx_const where name='USER_PASSWORD_GENERATED' and value='default';


ALTER TABLE llx_boxes_def DROP INDEX uk_boxes_def;
ALTER TABLE llx_boxes_def MODIFY file varchar(200) NOT NULL;
ALTER TABLE llx_boxes_def MODIFY note varchar(130);
ALTER TABLE llx_boxes_def ADD UNIQUE INDEX uk_boxes_def (file, entity, note);

-- Fix bad old data
UPDATE llx_bank_url SET type='payment' WHERE type='?' AND label='(payment)' AND url LIKE '%compta/paiement/fiche.php%';


update llx_const set value ='eldy' where name = 'MAIN_THEME' and (value= 'rodolphe' or value='dev' or value='bluelagoon');
update llx_user_param set value ='eldy' where param = 'MAIN_THEME' and (value= 'rodolphe' or value='dev' or value='bluelagoon');


ALTER TABLE llx_tmp_caisse MODIFY fk_article integer NOT NULL;
