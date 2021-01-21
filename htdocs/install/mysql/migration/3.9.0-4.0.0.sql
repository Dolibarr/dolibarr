--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 4.0.0 or higher.
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To drop an index:        -- VMYSQL4.0 DROP INDEX nomindex on llx_table
-- To drop an index:        -- VPGSQL8.0 DROP INDEX nomindex
-- To restrict request to Mysql version x.y minimum use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y minimum use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):    VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres): VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE
-- To set a field as NULL:                     VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as default NULL:             VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- Note: fields with type BLOB/TEXT can't have default value.
-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);

-- Drop old table not used (Informations are already presents in llx_accounting_bookkeeping)
DROP TABLE llx_accountingtransaction;
DROP TABLE llx_accountingdebcred;

-- Already into 3.9 but we do it again to be sure
ALTER TABLE llx_product ADD COLUMN localtax1_type varchar(10)  NOT NULL DEFAULT '0' after localtax1_tx; 
ALTER TABLE llx_product ADD COLUMN localtax2_type varchar(10)  NOT NULL DEFAULT '0' after localtax2_tx; 
ALTER TABLE llx_product_price ADD COLUMN localtax1_type varchar(10)  NOT NULL DEFAULT '0' after localtax1_tx; 
ALTER TABLE llx_product_price ADD COLUMN localtax2_type varchar(10)  NOT NULL DEFAULT '0' after localtax2_tx; 
ALTER TABLE llx_product_customer_price ADD COLUMN localtax1_type varchar(10)  NOT NULL DEFAULT '0' after localtax1_tx; 
ALTER TABLE llx_product_customer_price ADD COLUMN localtax2_type varchar(10)  NOT NULL DEFAULT '0' after localtax2_tx; 
ALTER TABLE llx_product_customer_price_log ADD COLUMN localtax1_type varchar(10)  NOT NULL DEFAULT '0' after localtax1_tx; 
ALTER TABLE llx_product_customer_price_log ADD COLUMN localtax2_type varchar(10)  NOT NULL DEFAULT '0' after localtax2_tx; 
ALTER TABLE llx_supplier_proposaldet CHANGE COLUMN fk_askpricesupplier fk_supplier_proposal integer NOT NULL;

ALTER TABLE llx_opensurvey_sondage ADD COLUMN status integer DEFAULT 1 after date_fin;

ALTER TABLE llx_expedition ADD COLUMN billed smallint DEFAULT 0;

insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (150, 'dolresource','internal', 'USERINCHARGE',     'In charge of resource', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (151, 'dolresource','external', 'THIRDINCHARGE',    'In charge of resource', 1);

DELETE FROM llx_user_param where param = 'MAIN_THEME' and value in ('auguria', 'amarok', 'cameleo');

-- DROP TABLE llx_product_lot;
CREATE TABLE llx_product_lot (
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  entity          integer DEFAULT 1,
  fk_product      integer NOT NULL,				-- Id of product
  batch           varchar(30) DEFAULT NULL,		-- Lot or serial number
  eatby           date DEFAULT NULL,			-- Eatby date
  sellby          date DEFAULT NULL, 			-- Sellby date
  datec         datetime,
  tms           timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat integer,
  fk_user_modif integer,
  import_key    integer
) ENGINE=InnoDB;

ALTER TABLE llx_product_lot ADD UNIQUE INDEX uk_product_lot(fk_product, batch);

-- VPGSQL8.2 ALTER TABLE llx_product_lot ALTER COLUMN entity SET DEFAULT 1;
ALTER TABLE llx_product_lot MODIFY COLUMN entity integer DEFAULT 1;
UPDATE llx_product_lot SET entity = 1 WHERE entity IS NULL;

DROP TABLE llx_stock_serial;

ALTER TABLE llx_product ADD COLUMN note_public text;
ALTER TABLE llx_user ADD COLUMN note_public text;

ALTER TABLE llx_c_type_contact ADD COLUMN position integer NOT NULL DEFAULT 0;


ALTER TABLE llx_product ADD COLUMN  model_pdf	varchar(255) default '';

ALTER TABLE llx_product ADD COLUMN width		float        DEFAULT NULL;
ALTER TABLE llx_product ADD COLUMN width_units	tinyint      DEFAULT NULL;
ALTER TABLE llx_product ADD COLUMN height		float        DEFAULT NULL;
ALTER TABLE llx_product ADD COLUMN height_units tinyint      DEFAULT NULL;

ALTER TABLE llx_product ADD COLUMN default_vat_code	varchar(10) after cost_price;

ALTER TABLE llx_product MODIFY COLUMN stock	real;

CREATE TABLE llx_categorie_user 
(
  fk_categorie 	integer NOT NULL,
  fk_user 		integer NOT NULL,
  import_key 	varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_categorie_user ADD PRIMARY KEY pk_categorie_user (fk_categorie, fk_user);
ALTER TABLE llx_categorie_user ADD INDEX idx_categorie_user_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_user ADD INDEX idx_categorie_user_fk_user (fk_user);

ALTER TABLE llx_categorie_user ADD CONSTRAINT fk_categorie_user_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_user ADD CONSTRAINT fk_categorie_user_fk_user FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);



ALTER TABLE llx_accounting_bookkeeping ADD COLUMN validated tinyint DEFAULT 0 NOT NULL;
ALTER TABLE llx_bank_account MODIFY COLUMN accountancy_journal varchar(16) DEFAULT NULL;

ALTER TABLE llx_fichinter ADD COLUMN datet date  after duree;
ALTER TABLE llx_fichinter ADD COLUMN datee date  after duree;
ALTER TABLE llx_fichinter ADD COLUMN dateo date  after duree;

ALTER TABLE llx_projet ADD COLUMN opp_percent double(5,2) after fk_opp_status;
UPDATE llx_projet as p set opp_percent = (SELECT percent from llx_c_lead_status as cls where cls.rowid = p.fk_opp_status) where opp_percent IS NULL;

ALTER TABLE llx_overwrite_trans ADD UNIQUE INDEX uk_overwrite_trans(lang, transkey);

ALTER TABLE llx_cronjob MODIFY COLUMN unitfrequency	varchar(255) NOT NULL DEFAULT '3600';
ALTER TABLE llx_cronjob ADD COLUMN test varchar(255) DEFAULT '1';

ALTER TABLE llx_facture ADD INDEX idx_facture_fk_statut (fk_statut);

ALTER TABLE llx_facture ADD COLUMN date_pointoftax date DEFAULT NULL;

UPDATE llx_projet as p set p.opp_percent = (SELECT percent FROM llx_c_lead_status as cls WHERE cls.rowid = p.fk_opp_status)  WHERE p.opp_percent IS NULL AND p.fk_opp_status IS NOT NULL;
 
ALTER TABLE llx_facturedet ADD COLUMN fk_contract_line  integer NULL AFTER rang;
ALTER TABLE llx_facturedet_rec ADD COLUMN import_key varchar(14);

ALTER TABLE llx_chargesociales ADD COLUMN import_key varchar(14);
ALTER TABLE llx_tva ADD COLUMN import_key varchar(14);

--DROP TABLE llx_website_page;
--DROP TABLE llx_website;
CREATE TABLE llx_website
(
	rowid         integer AUTO_INCREMENT NOT NULL PRIMARY KEY,
	entity        integer DEFAULT 1,
	ref		      varchar(24) NOT NULL,
	description   varchar(255),
	status		  integer,
	fk_default_home integer,
    date_creation     datetime,
    date_modification datetime,
	tms           timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;
ALTER TABLE llx_website ADD COLUMN fk_default_home integer;
ALTER TABLE llx_website CHANGE COLUMN shortname ref varchar(24) NOT NULL;
ALTER TABLE llx_website ADD UNIQUE INDEX uk_website_ref (ref, entity);

CREATE TABLE llx_website_page
(
	rowid         integer AUTO_INCREMENT NOT NULL PRIMARY KEY,
	fk_website    integer,
	pageurl       varchar(16) NOT NULL,
	title         varchar(255),						
	description   varchar(255),						
	keywords      varchar(255),
	content		  text,
    status        integer,
    date_creation     datetime,
    date_modification datetime,
	tms           timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;

ALTER TABLE llx_website_page ADD UNIQUE INDEX uk_website_page_url (fk_website,pageurl);

ALTER TABLE llx_website_page ADD CONSTRAINT fk_website_page_website FOREIGN KEY (fk_website) REFERENCES llx_website (rowid);

CREATE TABLE llx_c_format_cards
(
  rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code varchar(50) NOT NULL,
  name varchar(50) NOT NULL,
  paper_size varchar(20) NOT NULL,
  orientation varchar(1) NOT NULL,
  metric varchar(5) NOT NULL,
  leftmargin double(24,8) NOT NULL,
  topmargin double(24,8) NOT NULL,
  nx integer NOT NULL,
  ny integer NOT NULL,
  spacex double(24,8) NOT NULL,
  spacey double(24,8) NOT NULL,
  width double(24,8) NOT NULL,
  height double(24,8) NOT NULL,
  font_size integer NOT NULL,
  custom_x double(24,8) NOT NULL,
  custom_y double(24,8) NOT NULL,
  active integer NOT NULL
) ENGINE=InnoDB;

INSERT INTO llx_c_format_cards (rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active) VALUES (1, '5160', 'Avery-5160, WL-875WX', 'letter', 'P', 'mm', 5.58165000, 12.70000000, 3, 10, 3.55600000, 0.00000000, 65.87490000, 25.40000000, 7, 0.00000000, 0.00000000, 1);
INSERT INTO llx_c_format_cards (rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active) VALUES (2, '5161', 'Avery-5161, WL-75WX', 'letter', 'P', 'mm', 4.44500000, 12.70000000, 2, 10, 3.96800000, 0.00000000, 101.60000000, 25.40000000, 7, 0.00000000, 0.00000000, 1);
INSERT INTO llx_c_format_cards (rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active) VALUES (3, '5162', 'Avery-5162, WL-100WX', 'letter', 'P', 'mm', 3.87350000, 22.35200000, 2, 7, 4.95400000, 0.00000000, 101.60000000, 33.78100000, 8, 0.00000000, 0.00000000, 1);
INSERT INTO llx_c_format_cards (rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active) VALUES (4, '5163', 'Avery-5163, WL-125WX', 'letter', 'P', 'mm', 4.57200000, 12.70000000, 2, 5, 3.55600000, 0.00000000, 101.60000000, 50.80000000, 10, 0.00000000, 0.00000000, 1);
INSERT INTO llx_c_format_cards (rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active) VALUES (5, '5164', '5164 (Letter)', 'letter', 'P', 'in', 0.14800000, 0.50000000, 2, 3, 0.20310000, 0.00000000, 4.00000000, 3.33000000, 12, 0.00000000, 0.00000000, 0);
INSERT INTO llx_c_format_cards (rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active) VALUES (6, '8600', 'Avery-8600', 'letter', 'P', 'mm', 7.10000000, 19.00000000, 3, 10, 9.50000000, 3.10000000, 66.60000000, 25.40000000, 7, 0.00000000, 0.00000000, 1);
INSERT INTO llx_c_format_cards (rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active) VALUES (7, '99012', 'DYMO 99012 89*36mm', 'custom', 'L', 'mm', 1.00000000, 1.00000000, 1, 1, 0.00000000, 0.00000000, 36.00000000, 89.00000000, 10, 36.00000000, 89.00000000, 1);
INSERT INTO llx_c_format_cards (rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active) VALUES (8, '99014', 'DYMO 99014 101*54mm', 'custom', 'L', 'mm', 1.00000000, 1.00000000, 1, 1, 0.00000000, 0.00000000, 54.00000000, 101.00000000, 10, 54.00000000, 101.00000000, 1);
INSERT INTO llx_c_format_cards (rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active) VALUES (9, 'AVERYC32010', 'Avery-C32010', 'A4', 'P', 'mm', 15.00000000, 13.00000000, 2, 5, 10.00000000, 0.00000000, 85.00000000, 54.00000000, 10, 0.00000000, 0.00000000, 1);
INSERT INTO llx_c_format_cards (rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active) VALUES (10, 'CARD', 'Dolibarr Business cards', 'A4', 'P', 'mm', 15.00000000, 15.00000000, 2, 5, 0.00000000, 0.00000000, 85.00000000, 54.00000000, 10, 0.00000000, 0.00000000, 1);
INSERT INTO llx_c_format_cards (rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active) VALUES (11, 'L7163', 'Avery-L7163', 'A4', 'P', 'mm', 5.00000000, 15.00000000, 2, 7, 2.50000000, 0.00000000, 99.10000000, 38.10000000, 8, 0.00000000, 0.00000000, 1);

ALTER TABLE llx_extrafields ADD COLUMN ishidden integer DEFAULT 0;


ALTER TABLE llx_paiementfourn ADD COLUMN ref varchar(30) AFTER rowid;
ALTER TABLE llx_paiementfourn ADD COLUMN entity integer DEFAULT 1 AFTER ref;


CREATE TABLE llx_multicurrency 
( 
	rowid integer AUTO_INCREMENT PRIMARY KEY, 
	date_create datetime DEFAULT NULL, 
	code varchar(255) DEFAULT NULL, 
	name varchar(255) DEFAULT NULL, 
	entity integer DEFAULT 1,
	fk_user integer DEFAULT NULL
) ENGINE=innodb;

CREATE TABLE llx_multicurrency_rate 
( 
	rowid integer AUTO_INCREMENT PRIMARY KEY, 
	date_sync datetime DEFAULT NULL,  
	rate double NOT NULL DEFAULT 0, 
	fk_multicurrency integer NOT NULL 
) ENGINE=innodb;

ALTER TABLE llx_societe ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_societe ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_societe ADD COLUMN fk_shipping_method integer AFTER cond_reglement_supplier;

ALTER TABLE llx_product_price ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_product_price ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_product_price ADD COLUMN multicurrency_price double(24,8) DEFAULT 0;

ALTER TABLE llx_commande ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_commande ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_commande ADD COLUMN multicurrency_tx double(24,8) DEFAULT 1;
ALTER TABLE llx_commande ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_commande ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_commande ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_commandedet ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_commandedet ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_commandedet ADD COLUMN multicurrency_subprice double(24,8) DEFAULT 0;
ALTER TABLE llx_commandedet ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_commandedet ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_commandedet ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_commande_fournisseur ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_commande_fournisseur ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_commande_fournisseur ADD COLUMN multicurrency_tx double(24,8) DEFAULT 1;
ALTER TABLE llx_commande_fournisseur ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_commande_fournisseur ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_commande_fournisseur ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_commande_fournisseurdet ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_commande_fournisseurdet ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_commande_fournisseurdet ADD COLUMN multicurrency_subprice double(24,8) DEFAULT 0;
ALTER TABLE llx_commande_fournisseurdet ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_commande_fournisseurdet ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_commande_fournisseurdet ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_facture_fourn ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_facture_fourn ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_facture_fourn ADD COLUMN multicurrency_tx double(24,8) DEFAULT 1;
ALTER TABLE llx_facture_fourn ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_facture_fourn ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_facture_fourn ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_facture_fourn_det ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_facture_fourn_det ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_facture_fourn_det ADD COLUMN multicurrency_subprice double(24,8) DEFAULT 0;
ALTER TABLE llx_facture_fourn_det ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_facture_fourn_det ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_facture_fourn_det ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_facture ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_facture ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_facture ADD COLUMN multicurrency_tx double(24,8) DEFAULT 1;
ALTER TABLE llx_facture ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_facture ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_facture ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_facturedet ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_facturedet ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_facturedet ADD COLUMN multicurrency_subprice double(24,8) DEFAULT 0;
ALTER TABLE llx_facturedet ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_facturedet ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_facturedet ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_propal ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_propal ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_propal ADD COLUMN multicurrency_tx double(24,8) DEFAULT 1;
ALTER TABLE llx_propal ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_propal ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_propal ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_propaldet ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_propaldet ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_propaldet ADD COLUMN multicurrency_subprice double(24,8) DEFAULT 0;
ALTER TABLE llx_propaldet ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_propaldet ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_propaldet ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

 
-- Add for recurring template invoices

ALTER TABLE llx_facture_rec ADD COLUMN auto_validate integer DEFAULT 0;
ALTER TABLE llx_facture_rec ADD COLUMN fk_account integer DEFAULT 0;

ALTER TABLE llx_facture_rec ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_facture_rec ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_facture_rec ADD COLUMN multicurrency_tx double(24,8) DEFAULT 1;
ALTER TABLE llx_facture_rec ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_facture_rec ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_facture_rec ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_facturedet_rec ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_facturedet_rec ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_facturedet_rec ADD COLUMN multicurrency_subprice double(24,8) DEFAULT 0;
ALTER TABLE llx_facturedet_rec ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_facturedet_rec ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_facturedet_rec ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_contratdet ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_contratdet ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_contratdet ADD COLUMN multicurrency_subprice double(24,8) DEFAULT 0;
ALTER TABLE llx_contratdet ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_contratdet ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_contratdet ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_paiement ADD COLUMN multicurrency_amount double(24,8) DEFAULT 0;
ALTER TABLE llx_paiement_facture ADD COLUMN multicurrency_amount double(24,8) DEFAULT 0;
ALTER TABLE llx_paiementfourn ADD COLUMN multicurrency_amount double(24,8) DEFAULT 0;
ALTER TABLE llx_paiementfourn_facturefourn ADD COLUMN multicurrency_amount double(24,8) DEFAULT 0;

ALTER TABLE llx_societe_remise ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;

ALTER TABLE llx_societe_remise_except ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_societe_remise_except ADD COLUMN multicurrency_amount_ht double(24,8) DEFAULT 0 NOT NULL;
ALTER TABLE llx_societe_remise_except ADD COLUMN multicurrency_amount_tva double(24,8) DEFAULT 0 NOT NULL;
ALTER TABLE llx_societe_remise_except ADD COLUMN multicurrency_amount_ttc double(24,8) DEFAULT 0 NOT NULL;

ALTER TABLE llx_supplier_proposal ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_supplier_proposal ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_supplier_proposal ADD COLUMN multicurrency_tx double(24,8) DEFAULT 1;
ALTER TABLE llx_supplier_proposal ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_supplier_proposal ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_supplier_proposal ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_supplier_proposaldet ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_supplier_proposaldet ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_supplier_proposaldet ADD COLUMN multicurrency_subprice double(24,8) DEFAULT 0;
ALTER TABLE llx_supplier_proposaldet ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_supplier_proposaldet ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_supplier_proposaldet ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_expensereport ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_expensereport ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_expensereport ADD COLUMN multicurrency_tx double(24,8) DEFAULT 1;
ALTER TABLE llx_expensereport ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_expensereport ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_expensereport ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_expensereport_det ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_expensereport_det ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_expensereport_det ADD COLUMN multicurrency_subprice double(24,8) DEFAULT 0;
ALTER TABLE llx_expensereport_det ADD COLUMN multicurrency_total_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_expensereport_det ADD COLUMN multicurrency_total_tva double(24,8) DEFAULT 0;
ALTER TABLE llx_expensereport_det ADD COLUMN multicurrency_total_ttc double(24,8) DEFAULT 0;

ALTER TABLE llx_expensereport_det ADD COLUMN fk_facture	integer DEFAULT 0;

ALTER TABLE llx_product_lang ADD COLUMN import_key varchar(14) DEFAULT NULL;

ALTER TABLE llx_actioncomm MODIFY COLUMN elementtype varchar(255) DEFAULT NULL;


ALTER TABLE llx_accounting_system DROP COLUMN fk_pays;
ALTER TABLE llx_accounting_account ADD COLUMN fk_accounting_category integer DEFAULT 0 after label;

CREATE TABLE llx_c_accounting_category (
  rowid 			integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code 				varchar(16) NOT NULL,
  label 			varchar(255) NOT NULL,
  range_account		varchar(255) NOT NULL,
  sens 				tinyint NOT NULL DEFAULT '0', -- For international accounting  0 : credit - debit / 1 : debit - credit
  category_type		tinyint NOT NULL DEFAULT '0', -- Field calculated or not
  formula			varchar(255) NOT NULL,			 -- Example : 1 + 2 (rowid of the category)
  position    		integer DEFAULT 0,
  fk_country 		integer DEFAULT NULL,			 -- This category is dedicated to a country
  active 			integer DEFAULT 1
) ENGINE=innodb;

ALTER TABLE llx_c_accounting_category ADD UNIQUE INDEX uk_c_accounting_category(code);

INSERT INTO llx_c_accounting_category (rowid, code, label, range_account, sens, category_type, formula, position, fk_country, active) VALUES (  1,'VTE',  'Ventes de marchandises', '707xxx', 0, 0, '', '10', 1, 1);
INSERT INTO llx_c_accounting_category (rowid, code, label, range_account, sens, category_type, formula, position, fk_country, active) VALUES (  2,'MAR',  'Coût achats marchandises vendues', '603xxx | 607xxx | 609xxx', 0, 0, '', '20', 1, 1);
INSERT INTO llx_c_accounting_category (rowid, code, label, range_account, sens, category_type, formula, position, fk_country, active) VALUES (  3,'MARGE','Marge commerciale', '', 0, 1, '1 + 2', '30', 1, 1);

UPDATE llx_accounting_account SET account_parent = '0' WHERE account_parent = '';
-- VMYSQL4.1 ALTER TABLE llx_accounting_account MODIFY COLUMN account_parent varchar(32) DEFAULT '0';
-- VPGSQL8.2 ALTER TABLE llx_accounting_account ALTER COLUMN account_parent SET DEFAULT '0';

CREATE TABLE llx_accounting_journal
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  code       		varchar(32) NOT NULL,
  label             varchar(128) NOT NULL,
  nature			smallint DEFAULT 0 NOT NULL,			-- type of journals (Sale / purchase / bank / various operations)
  active            smallint DEFAULT 0
)ENGINE=innodb;

ALTER TABLE llx_accounting_journal ADD UNIQUE INDEX uk_accounting_journal_code (code);

-- VMYSQL4.1 DROP INDEX uk_bordereau_cheque ON llx_bordereau_cheque;
-- VPGSQL8.2 DROP INDEX uk_bordereau_cheque;
ALTER TABLE llx_bordereau_cheque CHANGE COLUMN number ref VARCHAR(30) NOT NULL;
CREATE UNIQUE INDEX uk_bordereau_cheque ON llx_bordereau_cheque (ref, entity);


ALTER TABLE llx_societe_rib ADD COLUMN date_rum	date after rum;

-- Add more action to log
update llx_c_action_trigger set rang = 140 where code = 'PROJECT_CREATE';
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_MODIFY','Project modified','Executed when a project is modified','project',141);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_DELETE','Project deleted','Executed when a project is deleted','project',142);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_CREATE','Supplier order validated','Executed when a supplier order is validated','order_supplier',11);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_SUBMIT','Supplier order request submited','Executed when a supplier order is approved','order_supplier',12);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_RECEIVE','Supplier order request received','Executed when a supplier order is received','order_supplier',12);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_CLASSIFY_BILLED','Supplier order set billed','Executed when a supplier order is set as billed','order_supplier',14);

ALTER TABLE llx_product_fournisseur_price ADD supplier_reputation varchar(10) NULL;

ALTER TABLE llx_product ADD COLUMN default_vat_code varchar(10) after cost_price;

CREATE TABLE llx_categorie_account
(
  fk_categorie  integer NOT NULL,
  fk_account    integer NOT NULL,
  import_key    varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_categorie_account ADD PRIMARY KEY pk_categorie_account (fk_categorie, fk_account);
ALTER TABLE llx_categorie_account ADD INDEX idx_categorie_account_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_account ADD INDEX idx_categorie_account_fk_account (fk_account);

ALTER TABLE llx_categorie_account ADD CONSTRAINT fk_categorie_account_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_account ADD CONSTRAINT fk_categorie_account_fk_account FOREIGN KEY (fk_account) REFERENCES llx_bank_account (rowid);

-- Delete old deprecated field
ALTER TABLE llx_product_stock DROP COLUMN pmp;

ALTER TABLE llx_resource ADD COLUMN asset_number    varchar(255) after ref;
ALTER TABLE llx_resource ADD COLUMN datec           datetime DEFAULT NULL;
ALTER TABLE llx_resource ADD COLUMN date_valid      datetime DEFAULT NULL;
ALTER TABLE llx_resource ADD COLUMN fk_user_author  integer DEFAULT NULL;
ALTER TABLE llx_resource ADD COLUMN fk_user_modif   integer DEFAULT NULL;
ALTER TABLE llx_resource ADD COLUMN fk_user_valid   integer DEFAULT NULL;
ALTER TABLE llx_resource ADD COLUMN fk_statut       smallint NOT NULL DEFAULT '0';
ALTER TABLE llx_resource ADD COLUMN import_key			varchar(14);
ALTER TABLE llx_resource ADD COLUMN extraparams			varchar(255);	
 
ALTER TABLE llx_element_resources ADD COLUMN duree real;          -- total duration of using ressource

UPDATE llx_element_resources SET resource_type = 'dolresource' WHERE resource_type = 'resource';

CREATE TABLE llx_advtargetemailing
(
  rowid integer NOT NULL auto_increment PRIMARY KEY,
  name varchar(200) NOT NULL,
  entity integer NOT NULL DEFAULT 1,
  fk_mailing	integer NOT NULL,
  filtervalue	text,
  fk_user_author integer NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod integer NOT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=InnoDB;

ALTER TABLE llx_advtargetemailing ADD UNIQUE INDEX uk_advtargetemailing_name (name);


update llx_product_batch set batch = '000000' where batch = 'Non d&eacute;fini';
update llx_product_batch set batch = '000000' where batch = 'Non défini';
update llx_product_batch set batch = '000000' where batch = 'Undefined';

update llx_product_batch set batch = '000000' where batch = '';
update llx_product_batch set batch = '000000' where batch = '';
update llx_product_batch set batch = '000000' where batch = '';

update llx_product_lot set batch = '000000' where batch = 'Undefined';
update llx_stock_mouvement set batch = '000000' where batch = 'Undefined';

ALTER TABLE llx_import_model MODIFY COLUMN type varchar(50);


UPDATE llx_projet set fk_opp_status = NULL where fk_opp_status = -1;
UPDATE llx_c_lead_status set code = 'WON' where code = 'WIN';
UPDATE llx_c_lead_status set percent = 100 where code = 'WON';


CREATE TABLE llx_oauth_token (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    service varchar(36),
    token text,
    fk_user integer,
    fk_adherent integer,
    entity integer DEFAULT 1
)ENGINE=InnoDB;

CREATE TABLE llx_oauth_state (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    service varchar(36),
    state varchar(128),
    fk_user integer,
    fk_adherent integer,
    entity integer DEFAuLT 1
)ENGINE=InnoDB;

-- At end (higher risk of error)

-- VMYSQL4.1 ALTER TABLE llx_c_type_resource CHANGE COLUMN rowid rowid integer NOT NULL AUTO_INCREMENT;

ALTER TABLE llx_product_batch ADD UNIQUE INDEX uk_product_batch (fk_product_stock, batch);

-- Panama datas
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1781, 178,  '7','0','ITBMS standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1782, 178,   '0','0','ITBMS Rate 0',1);
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom, active) values (  178, 17801, '', 0, 'Panama', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-1', 17801, '', 0, '', 'Bocas del Toro', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-2', 17801, '', 0, '', 'Coclé', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-3', 17801, '', 0, '', 'Colón', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-4', 17801, '', 0, '', 'Chiriquí', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-5', 17801, '', 0, '', 'Darién', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-6', 17801, '', 0, '', 'Herrera', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-7', 17801, '', 0, '', 'Los Santos', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-8', 17801, '', 0, '', 'Panamá', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-9', 17801, '', 0, '', 'Veraguas', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-13', 17801, '', 0, '', 'Panamá Oeste', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (178, '17801', 'Empresa individual', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (178, '17802', 'Asociación General', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (178, '17803', 'Sociedad de Responsabilidad Limitada', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (178, '17804', 'Sociedad Civil', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (178, '17805', 'Sociedad Anónima', 1);


-- VMYSQL4.1 ALTER TABLE llx_establishment CHANGE COLUMN fk_user_mod fk_user_mod integer NULL;
-- VPGSQL8.2 ALTER TABLE llx_establishment ALTER COLUMN fk_user_mod DROP NOT NULL;

ALTER TABLE llx_multicurrency_rate ADD COLUMN entity integer DEFAULT 1;

ALTER TABLE llx_user MODIFY COLUMN login varchar(50);