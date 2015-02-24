--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.8.0 or higher.
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
-- To set a field as default NULL:             VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


ALTER TABLE llx_commande_fournisseur MODIFY COLUMN date_livraison datetime; 

-- Add id commandefourndet in llx_commande_fournisseur_dispatch to correct /fourn/commande/dispatch.php display when several times same product in supplier order
ALTER TABLE llx_commande_fournisseur_dispatch ADD COLUMN fk_commandefourndet INTEGER NOT NULL DEFAULT 0 AFTER fk_product;


-- Remove menu entries of removed or renamed modules
DELETE FROM llx_menu where module = 'printipp';


ALTER TABLE llx_bank ADD INDEX idx_bank_num_releve(num_releve);


--create table for price expressions and add column in product supplier
create table llx_c_price_expression
(
  rowid      integer AUTO_INCREMENT PRIMARY KEY,
  title      varchar(20) NOT NULL,
  expression varchar(80) NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_supplier_price_expression integer DEFAULT NULL;
ALTER TABLE llx_product ADD COLUMN fk_price_expression integer DEFAULT NULL;
ALTER TABLE llx_product_price ADD COLUMN fk_price_expression integer DEFAULT NULL;


--create table for user conf of printing driver
CREATE TABLE llx_printing 
(
 rowid integer AUTO_INCREMENT PRIMARY KEY,
 tms timestamp,
 datec datetime,
 printer_name text NOT NULL, 
 printer_location text NOT NULL,
 printer_id varchar(255) NOT NULL,
 copy integer NOT NULL DEFAULT '1',
 module varchar(16) NOT NULL,
 driver varchar(16) NOT NULL,
 userid integer
)ENGINE=innodb;

ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_price_expression integer DEFAULT NULL;

-- Taiwan VAT Rates
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 2131, 213, '5', '0', 'VAT 5%', 1);

-- Add situation invoices
ALTER TABLE llx_facture ADD COLUMN situation_cycle_ref smallint;
ALTER TABLE llx_facture ADD COLUMN situation_counter smallint;
ALTER TABLE llx_facture ADD COLUMN situation_final smallint;
ALTER TABLE llx_facturedet ADD COLUMN situation_percent real;
ALTER TABLE llx_facturedet ADD COLUMN fk_prev_id integer;

-- Convert SMTP config to main entity, so new entities don't get the old values
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SENDMODE";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SMTP_PORT";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SMTP_SERVER";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SMTPS_ID";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SMTPS_PW";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_EMAIL_TLS";


create table llx_bank_account_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;


ALTER TABLE llx_stock_mouvement MODIFY COLUMN label varchar(255);
ALTER TABLE llx_stock_mouvement ADD COLUMN inventorycode varchar(128);

ALTER TABLE llx_product_association ADD COLUMN incdec integer DEFAULT 1;



ALTER TABLE llx_bank_account_extrafields ADD INDEX idx_bank_account_extrafields (fk_object);


create table llx_contratdet_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_contratdet_extrafields ADD INDEX idx_contratdet_extrafields (fk_object);

ALTER TABLE llx_product_fournisseur_price ADD COLUMN delivery_time_days integer;

ALTER TABLE llx_commande_fournisseur_dispatch ADD COLUMN comment	varchar(255);
ALTER TABLE llx_commande_fournisseur_dispatch ADD COLUMN status integer;
ALTER TABLE llx_commande_fournisseur_dispatch ADD COLUMN tms timestamp;
ALTER TABLE llx_commande_fournisseur_dispatch ADD COLUMN batch varchar(30) DEFAULT NULL;
ALTER TABLE llx_commande_fournisseur_dispatch ADD COLUMN eatby date DEFAULT NULL;
ALTER TABLE llx_commande_fournisseur_dispatch ADD COLUMN sellby date DEFAULT NULL;
ALTER TABLE llx_stock_mouvement ADD COLUMN batch varchar(30) DEFAULT NULL;
ALTER TABLE llx_stock_mouvement ADD COLUMN eatby date DEFAULT NULL;
ALTER TABLE llx_stock_mouvement ADD COLUMN sellby date DEFAULT NULL;



CREATE TABLE llx_expensereport (
  rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ref        		varchar(50) NOT NULL,
  entity 			integer DEFAULT 1 NOT NULL,		-- multi company id
  ref_number_int 	integer DEFAULT NULL,
  ref_ext 			integer,
  total_ht 			double(24,8) DEFAULT 0,
  total_tva 		double(24,8) DEFAULT 0,
  localtax1			double(24,8) DEFAULT 0,				-- amount total localtax1
  localtax2			double(24,8) DEFAULT 0,				-- amount total localtax2	
  total_ttc 		double(24,8) DEFAULT 0,
  date_debut 		date NOT NULL,
  date_fin 			date NOT NULL,
  date_create 		datetime NOT NULL,
  date_valid 		datetime,
  date_approve		datetime,
  date_refuse 		datetime,
  date_cancel 		datetime,
  date_paiement 	datetime,
  tms 		 		timestamp,
  fk_user_author 	integer NOT NULL,
  fk_user_modif 	integer DEFAULT NULL,
  fk_user_valid 	integer DEFAULT NULL,
  fk_user_validator integer DEFAULT NULL,
  fk_user_approve   integer DEFAULT NULL,
  fk_user_refuse 	integer DEFAULT NULL,
  fk_user_cancel 	integer DEFAULT NULL,
  fk_user_paid 		integer DEFAULT NULL,
  fk_c_expensereport_statuts integer NOT NULL,		-- 1=brouillon, 2=validé (attente approb), 4=annulé, 5=approuvé, 6=payed, 99=refusé
  fk_c_paiement 	integer DEFAULT NULL,
  note_public		text,
  note_private 		text,
  detail_refuse 	varchar(255) DEFAULT NULL,
  detail_cancel 	varchar(255) DEFAULT NULL,
  integration_compta integer DEFAULT NULL,		-- not used
  fk_bank_account 	integer DEFAULT NULL,
  model_pdf 		varchar(50) DEFAULT NULL
) ENGINE=innodb;


CREATE TABLE llx_expensereport_det
(
   rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
   fk_expensereport integer NOT NULL,
   fk_c_type_fees integer NOT NULL,
   fk_projet integer NOT NULL,
   fk_c_tva integer NOT NULL,
   comments text NOT NULL,
   product_type integer DEFAULT -1,
   qty real NOT NULL,
   value_unit real NOT NULL,
   remise_percent real,
   tva_tx						double(6,3),						    -- Vat rat
   localtax1_tx               	double(6,3)  DEFAULT 0,    		 	-- localtax1 rate
   localtax1_type			 	varchar(10)	  	 NULL, 				 	-- localtax1 type
   localtax2_tx               	double(6,3)  DEFAULT 0,    		 	-- localtax2 rate
   localtax2_type			 	varchar(10)	  	 NULL, 				 	-- localtax2 type
   total_ht double(24,8) DEFAULT 0 NOT NULL,
   total_tva double(24,8) DEFAULT 0 NOT NULL,
   total_localtax1				double(24,8)  	DEFAULT 0,		-- Total LocalTax1 for total quantity of line
   total_localtax2				double(24,8)	DEFAULT 0,		-- total LocalTax2 for total quantity of line
   total_ttc double(24,8) DEFAULT 0 NOT NULL,
   date date NOT NULL,
   info_bits					integer DEFAULT 0,				-- TVA NPR ou non
   special_code					integer DEFAULT 0,			    -- code pour les lignes speciales
   rang							integer DEFAULT 0,				-- position of line
   import_key					varchar(14)
) ENGINE=innodb;


ALTER TABLE llx_projet ADD COLUMN budget_amount double(24,8);


