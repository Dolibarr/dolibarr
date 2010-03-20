--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.8.0 or higher. 
--


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


ALTER TABLE llx_adherent ADD COLUMN   civilite       varchar(6) after entity;
