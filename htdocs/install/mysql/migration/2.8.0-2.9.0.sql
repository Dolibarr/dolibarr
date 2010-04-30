--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.8.0 or higher. 
--


-- V4.1 DELETE FROM llx_projet_task WHERE fk_projet NOT IN (SELECT rowid from llx_projet);
-- V4.1 UPDATE llx_projet_task set fk_user_creat=NULL WHERE fk_user_creat IS NOT NULL AND fk_user_creat NOT IN (SELECT rowid from llx_user);
-- V4.1 UPDATE llx_projet_task set fk_user_valid=NULL WHERE fk_user_valid IS NOT NULL AND fk_user_valid NOT IN (SELECT rowid from llx_user);

-- rename llx_product_det
ALTER TABLE llx_product_det RENAME TO llx_product_lang;
ALTER TABLE llx_product_lang ADD UNIQUE INDEX uk_product_lang (fk_product, lang);
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

--add local taxes
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

alter table llx_product add column   hidden             tinyint      DEFAULT 0;

alter table llx_product add column   length             float        DEFAULT NULL after weight_units;
alter table llx_product add column   length_units       tinyint      DEFAULT NULL after length;
alter table llx_product add column   surface            float        DEFAULT NULL after length_units;
alter table llx_product add column   surface_units      tinyint      DEFAULT NULL after surface;

alter table llx_product add column   accountancy_code_sell       varchar(15) after fk_barcode_type;
alter table llx_product add column   accountancy_code_buy        varchar(15) after accountancy_code_sell;

ALTER TABLE llx_product drop column stock_loc;
ALTER TABLE llx_product_stock add column location        varchar(32);

ALTER TABLE llx_expedition ADD COLUMN ref_customer varchar(30) AFTER entity;
ALTER TABLE llx_expedition ADD COLUMN date_delivery date DEFAULT NULL AFTER date_expedition;

ALTER TABLE llx_livraison change ref_client ref_customer varchar(30);
ALTER TABLE llx_livraison change date_livraison date_delivery date		DEFAULT NULL;

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

-- custom list
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
)type=innodb;

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

-- add milestone module
DROP TABLE llx_projet_milestone;
create table llx_milestone
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  label					varchar(255) NOT NULL,
  description			text,
  datec					datetime,
  tms					timestamp,
  dateo					datetime,
  datee					datetime,
  priority				integer	DEFAULT 0,
  fk_user_creat			integer,
  rang					integer	DEFAULT 0
)type=innodb;

ALTER TABLE llx_milestone ADD INDEX idx_milestone_fk_user_creat (fk_user_creat);
ALTER TABLE llx_milestone ADD CONSTRAINT fk_milestone_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user (rowid);

create table llx_element_milestone
(
  rowid           	integer AUTO_INCREMENT PRIMARY KEY,  
  fk_element		integer NOT NULL,
  elementtype		varchar(16) NOT NULL,
  fk_milestone		integer NOT NULL
) type=innodb;

ALTER TABLE llx_element_milestone ADD UNIQUE INDEX idx_element_milestone_idx1 (fk_element, elementtype, fk_milestone);
ALTER TABLE llx_element_milestone ADD INDEX idx_element_milestone_fk_milestone (fk_milestone);
ALTER TABLE llx_element_milestone ADD CONSTRAINT fk_element_milestone_fk_milestone FOREIGN KEY (fk_milestone) REFERENCES llx_milestone(rowid);

ALTER TABLE llx_deplacement ADD COLUMN fk_statut INTEGER DEFAULT 1  NOT NULL after type;

drop table llx_appro;

ALTER TABLE llx_events MODIFY COLUMN user_agent     varchar(255) NULL;

create table llx_categorie_member
(
  fk_categorie  integer NOT NULL,
  fk_member     integer NOT NULL
)type=innodb;

ALTER TABLE llx_categorie_member ADD PRIMARY KEY (fk_categorie, fk_member);
ALTER TABLE llx_categorie_member ADD INDEX idx_categorie_member_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_member ADD INDEX idx_categorie_member_fk_member (fk_member);

ALTER TABLE llx_categorie_member ADD CONSTRAINT fk_categorie_member_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_member ADD CONSTRAINT fk_categorie_member_member_rowid   FOREIGN KEY (fk_member) REFERENCES llx_adherent (rowid);
