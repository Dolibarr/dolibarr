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

ALTER TABLE llx_fichinter ADD COLUMN datet date  after duree;
ALTER TABLE llx_fichinter ADD COLUMN datee date  after duree;
ALTER TABLE llx_fichinter ADD COLUMN dateo date  after duree;

ALTER TABLE llx_projet ADD COLUMN opp_percent double(5,2) after fk_opp_status;
UPDATE llx_projet as p set opp_percent = (SELECT percent from llx_c_lead_status as cls where cls.rowid = p.fk_opp_status) where opp_percent IS NULL;

ALTER TABLE llx_overwrite_trans ADD UNIQUE INDEX uk_overwrite_trans(lang, transkey);

ALTER TABLE llx_cronjob MODIFY COLUMN unitfrequency	varchar(255) NOT NULL DEFAULT '3600';

ALTER TABLE llx_facture ADD INDEX idx_facture_fk_statut (fk_statut);


CREATE TABLE IF NOT EXISTS `llx_multicurrency` 
( 
	`rowid` integer AUTO_INCREMENT PRIMARY KEY, 
	`date_create` datetime DEFAULT NULL, 
	`code` varchar(255) DEFAULT NULL, 
	`name` varchar(255) DEFAULT NULL, 
	`entity` integer DEFAULT NULL,
	`fk_user` integer DEFAULT NULL,
	KEY `code` (`code`)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS `llx_multicurrency_rate` 
( 
	`rowid` integer AUTO_INCREMENT PRIMARY KEY, 
	`date_sync` datetime DEFAULT NULL,  
	`rate` double NOT NULL DEFAULT '0', 
	`fk_multicurrency` integer NOT NULL DEFAULT '0', 
	`entity` integer NOT NULL DEFAULT '0', 
	KEY `fk_multicurrency` (`fk_multicurrency`), 
	KEY `entity` (`entity`) 
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