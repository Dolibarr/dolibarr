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

UPDATE llx_projet as p set p.opp_percent = (SELECT percent FROM llx_c_lead_status as cls WHERE cls.rowid = p.fk_opp_status)  WHERE p.opp_percent IS NULL AND p.fk_opp_status IS NOT NULL;
 
 

CREATE TABLE llx_website
(
	rowid         integer AUTO_INCREMENT NOT NULL PRIMARY KEY,
	entity        integer DEFAULT 1,
	shortname     varchar(24) NOT NULL,
	description   varchar(255),
	status		  integer,
    date_creation     datetime,
    date_modification datetime,
	tms           timestamp
) ENGINE=innodb;
 
ALTER TABLE llx_website ADD UNIQUE INDEX uk_website_shortname (shortname, entity);

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
	tms           timestamp
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

CREATE TABLE IF NOT EXISTS llx_multicurrency 
( 
	rowid integer AUTO_INCREMENT PRIMARY KEY, 
	date_create datetime DEFAULT NULL, 
	code varchar(255) DEFAULT NULL, 
	name varchar(255) DEFAULT NULL, 
	entity integer DEFAULT NULL,
	fk_user integer DEFAULT NULL,
	KEY code (code)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_multicurrency_rate 
( 
	rowid integer AUTO_INCREMENT PRIMARY KEY, 
	date_sync datetime DEFAULT NULL,  
	rate double NOT NULL DEFAULT '0', 
	fk_multicurrency integer NOT NULL DEFAULT '0', 
	entity integer NOT NULL DEFAULT '0', 
	KEY fk_multicurrency (fk_multicurrency), 
	KEY entity (entity) 
) ENGINE=innodb;

ALTER TABLE llx_societe ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_societe ADD COLUMN multicurrency_code varchar(255);

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
 
 