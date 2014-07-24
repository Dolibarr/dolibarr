--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.7.0 or higher.
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):    VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres): VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE
-- To set a field as NULL:                     VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as defailt NULL:             VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


ALTER TABLE llx_c_paiement ADD COLUMN accountancy_code varchar(32) DEFAULT NULL AFTER active;

-- Defined only to have specific list for countries that can't use generic list (like argentina that need type A or B)
ALTER TABLE llx_c_typent ADD COLUMN fk_country integer NULL AFTER libelle;

INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (29,'FICHINTER_CLASSIFY_BILLED','Classify intervention as billed','Executed when a intervention is classified as billed (when option FICHINTER_DISABLE_DETAILS is set)','ficheinter',19);

INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, position) values (11,'AC_INT','system','Intervention on site',NULL, 1, 4);



ALTER TABLE llx_accountingaccount add column entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_accountingaccount add column datec datetime NOT NULL AFTER entity;
ALTER TABLE llx_accountingaccount add column tms timestamp AFTER datec;
ALTER TABLE llx_accountingaccount add column fk_user_author integer DEFAULT NULL AFTER label;
ALTER TABLE llx_accountingaccount add column fk_user_modif integer DEFAULT NULL AFTER fk_user_author;


-- Drop old table
DROP TABLE llx_compta;
DROP TABLE llx_compta_account;
DROP TABLE llx_compta_compte_generaux;

-- Align size for accounting account
ALTER TABLE llx_accountingaccount MODIFY COLUMN account_number varchar(32);
ALTER TABLE llx_accountingaccount MODIFY COLUMN account_parent varchar(32);
ALTER TABLE llx_accountingdebcred MODIFY COLUMN account_number varchar(32);
ALTER TABLE llx_bank_account MODIFY COLUMN account_number varchar(32);
ALTER TABLE llx_c_chargesociales MODIFY COLUMN accountancy_code varchar(32);
ALTER TABLE llx_c_revenuestamp MODIFY COLUMN accountancy_code_sell varchar(32);
ALTER TABLE llx_c_revenuestamp MODIFY COLUMN accountancy_code_buy varchar(32);
ALTER TABLE llx_c_tva MODIFY COLUMN accountancy_code_sell varchar(32);
ALTER TABLE llx_c_tva MODIFY COLUMN accountancy_code_buy varchar(32);
ALTER TABLE llx_product MODIFY COLUMN accountancy_code_sell varchar(32);
ALTER TABLE llx_product MODIFY COLUMN accountancy_code_buy varchar(32);
ALTER TABLE llx_user MODIFY COLUMN accountancy_code varchar(32);


ALTER TABLE llx_bank_account ADD COLUMN accountancy_journal varchar(3) DEFAULT NULL AFTER account_number;

ALTER TABLE llx_projet_task_time ADD COLUMN task_datehour datetime after task_date;


-- Localtaxes by thirds
ALTER TABLE llx_c_tva MODIFY COLUMN localtax1 varchar(10);
ALTER TABLE llx_c_tva MODIFY COLUMN localtax2 varchar(10);
ALTER TABLE llx_localtax ADD COLUMN localtaxtype tinyint(4) after entity;
ALTER TABLE llx_societe ADD COLUMN localtax1_value double(6,3) after localtax1_assuj;
ALTER TABLE llx_societe ADD COLUMN localtax2_value double(6,3) after localtax2_assuj;



-- Added missing relations of llx_product
-- fk_country
ALTER TABLE llx_product MODIFY COLUMN fk_country INTEGER NULL DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product ALTER COLUMN fk_country DROP NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product ALTER COLUMN fk_country SET DEFAULT NULL;
UPDATE llx_product SET fk_country = NULL WHERE fk_country = 0;
ALTER TABLE llx_product ADD INDEX idx_product_fk_country (fk_country);
ALTER TABLE llx_product ADD CONSTRAINT fk_product_fk_country FOREIGN KEY (fk_country) REFERENCES  llx_c_pays (rowid);
-- fk_user_author
ALTER TABLE llx_product MODIFY COLUMN fk_user_author INTEGER NULL DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product ALTER COLUMN fk_user_author DROP NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product ALTER COLUMN fk_user_author SET DEFAULT NULL;
ALTER TABLE llx_product ADD INDEX idx_product_fk_user_author (fk_user_author);
-- fk_barcode_type
ALTER TABLE llx_product MODIFY COLUMN fk_barcode_type INTEGER NULL DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product ALTER COLUMN fk_barcode_type DROP NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product ALTER COLUMN fk_barcode_type SET DEFAULT NULL;
UPDATE llx_product SET fk_barcode_type = NULL WHERE fk_barcode_type = 0;
ALTER TABLE llx_product ADD INDEX idx_product_fk_barcode_type (fk_barcode_type);
ALTER TABLE llx_product ADD CONSTRAINT fk_product_barcode_type FOREIGN KEY (fk_barcode_type) REFERENCES  llx_c_barcode_type (rowid);


-- Added missing relations of llx_product_price
-- fk_user_author
ALTER TABLE  llx_product_price ADD INDEX idx_product_price_fk_user_author (fk_user_author);
UPDATE llx_product_price set fk_user_author = null where fk_user_author = 0;
ALTER TABLE  llx_product_price ADD CONSTRAINT fk_product_price_user_author FOREIGN KEY (fk_user_author) REFERENCES  llx_user (rowid);
-- fk_user_author
ALTER TABLE  llx_product_price ADD INDEX idx_product_price_fk_product (fk_product);
DELETE from llx_product_price where fk_product NOT IN (SELECT rowid from llx_product);
ALTER TABLE  llx_product_price ADD CONSTRAINT fk_product_price_product FOREIGN KEY (fk_product) REFERENCES  llx_product (rowid);

ALTER TABLE llx_commande_fournisseur ADD COLUMN fk_account integer AFTER date_livraison;
ALTER TABLE llx_facture_fourn ADD COLUMN fk_account integer AFTER fk_projet;

-- Fiscal years
create table llx_accounting_fiscalyear
(
	rowid			integer AUTO_INCREMENT PRIMARY KEY,
	label			varchar(128) NOT NULL,
	date_start		date,
	date_end		date,
	statut			tinyint DEFAULT 0 NOT NULL,
	entity			integer DEFAULT 1 NOT NULL,	  -- multi company id
	datec			datetime NOT NULL,
	tms				timestamp,
	fk_user_author	integer NULL,
	fk_user_modif	integer NULL
)ENGINE=innodb;

ALTER TABLE llx_contrat ADD COLUMN ref_ext varchar(30) after ref;
