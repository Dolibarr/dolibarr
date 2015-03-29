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

-- Loan
create table llx_loan
(
  rowid							integer AUTO_INCREMENT PRIMARY KEY,
  entity						integer DEFAULT 1 NOT NULL,
  datec							datetime,
  tms							timestamp,
  label							varchar(80) NOT NULL,
  fk_bank						integer,
  capital						real     default 0 NOT NULL,
  datestart						date,
  dateend						date,
  nbterm						real,
  rate							double  NOT NULL,
  note_private                  text,
  note_public                   text,
  capital_position				real     default 0,
  date_position					date,
  paid							smallint default 0 NOT NULL,
  accountancy_account_capital	varchar(32),
  accountancy_account_insurance	varchar(32),
  accountancy_account_interest	varchar(32),
  fk_user_author				integer DEFAULT NULL,
  fk_user_modif					integer DEFAULT NULL,
  active						tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

create table llx_payment_loan
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  fk_loan			integer,
  datec				datetime,
  tms				timestamp,
  datep				datetime,
  amount_capital	real DEFAULT 0,
  amount_insurance	real DEFAULT 0,
  amount_interest	real DEFAULT 0,
  fk_typepayment	integer NOT NULL,
  num_payment		varchar(50),
  note_private      text,
  note_public       text,
  fk_bank			integer NOT NULL,
  fk_user_creat		integer,
  fk_user_modif		integer
)ENGINE=innodb;

ALTER TABLE llx_extrafields ADD COLUMN perms varchar(255) after fieldrequired;
ALTER TABLE llx_extrafields ADD COLUMN list integer DEFAULT 0 after perms;

ALTER TABLE llx_payment_salary ADD COLUMN salary real after datev;

UPDATE llx_projet_task_time SET task_datehour = task_date where task_datehour IS NULL;
ALTER TABLE llx_projet_task_time ADD COLUMN task_date_withhour integer DEFAULT 0 after task_datehour;

ALTER TABLE llx_projet_task MODIFY COLUMN duration_effective real DEFAULT 0 NULL;
ALTER TABLE llx_projet_task MODIFY COLUMN planned_workload real DEFAULT 0 NULL;


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
  fk_statut			integer NOT NULL,		-- 1=brouillon, 2=validé (attente approb), 4=annulé, 5=approuvé, 6=payed, 99=refusé
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


create table llx_commande_fournisseurdet_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_commande_fournisseurdet_extrafields ADD INDEX idx_commande_fournisseurdet_extrafields (fk_object);


create table llx_facture_fourn_det_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_facture_fourn_det_extrafields ADD INDEX idx_facture_fourn_det_extrafields (fk_object);

ALTER TABLE llx_facture_fourn_det ADD COLUMN special_code	 integer DEFAULT 0;
ALTER TABLE llx_facture_fourn_det ADD COLUMN rang integer DEFAULT 0;
ALTER TABLE llx_facture_fourn_det ADD COLUMN fk_parent_line integer NULL after fk_facture_fourn;

ALTER TABLE llx_commande_fournisseurdet ADD COLUMN special_code	 integer DEFAULT 0;
ALTER TABLE llx_commande_fournisseurdet ADD COLUMN rang integer DEFAULT 0;
ALTER TABLE llx_commande_fournisseurdet ADD COLUMN fk_parent_line integer NULL after fk_commande;

ALTER TABLE llx_projet ADD COLUMN date_close datetime DEFAULT NULL;    
ALTER TABLE llx_projet ADD COLUMN fk_user_close integer DEFAULT NULL;


  
-- Module AskPriceSupplier --
CREATE TABLE llx_askpricesupplier (
  rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ref varchar(30) NOT NULL,
  entity integer NOT NULL DEFAULT '1',
  ref_ext varchar(255) DEFAULT NULL,
  ref_int varchar(255) DEFAULT NULL,
  fk_soc integer DEFAULT NULL,
  fk_projet integer DEFAULT NULL,
  tms timestamp,
  datec datetime DEFAULT NULL,
  date_valid datetime DEFAULT NULL,
  date_cloture datetime DEFAULT NULL,
  fk_user_author integer DEFAULT NULL,
  fk_user_modif integer DEFAULT NULL,
  fk_user_valid integer DEFAULT NULL,
  fk_user_cloture integer DEFAULT NULL,
  fk_statut smallint NOT NULL DEFAULT '0',
  price double DEFAULT '0',
  remise_percent double DEFAULT '0',
  remise_absolue double DEFAULT '0',
  remise double DEFAULT '0',
  total_ht double(24,8) DEFAULT 0,
  tva double(24,8) DEFAULT 0,
  localtax1 double(24,8) DEFAULT 0,
  localtax2 double(24,8) DEFAULT 0,
  total double(24,8) DEFAULT 0,
  fk_account integer DEFAULT NULL,
  fk_currency varchar(3) DEFAULT NULL,
  fk_cond_reglement integer DEFAULT NULL,
  fk_mode_reglement integer DEFAULT NULL,
  note_private text,
  note_public text,
  model_pdf varchar(255) DEFAULT NULL,
  date_livraison date DEFAULT NULL,
  fk_shipping_method integer DEFAULT NULL,
  import_key varchar(14) DEFAULT NULL,
  extraparams varchar(255) DEFAULT NULL
) ENGINE=innodb;

CREATE TABLE llx_askpricesupplierdet (
  rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fk_askpricesupplier integer NOT NULL,
  fk_parent_line integer DEFAULT NULL,
  fk_product integer DEFAULT NULL,
  label varchar(255) DEFAULT NULL,
  description text,
  fk_remise_except integer DEFAULT NULL,
  tva_tx double(6,3) DEFAULT 0,
  localtax1_tx double(6,3) DEFAULT 0,
  localtax1_type varchar(10) DEFAULT NULL,
  localtax2_tx double(6,3) DEFAULT 0,
  localtax2_type varchar(10) DEFAULT NULL,
  qty double DEFAULT NULL,
  remise_percent double DEFAULT '0',
  remise double DEFAULT '0',
  price double DEFAULT NULL,
  subprice double(24,8) DEFAULT 0,
  total_ht double(24,8) DEFAULT 0,
  total_tva double(24,8) DEFAULT 0,
  total_localtax1 double(24,8) DEFAULT 0,
  total_localtax2 double(24,8) DEFAULT 0,
  total_ttc double(24,8) DEFAULT 0,
  product_type integer DEFAULT 0,
  info_bits integer DEFAULT 0,
  buy_price_ht double(24,8) DEFAULT 0,
  fk_product_fournisseur_price integer DEFAULT NULL,
  special_code integer DEFAULT 0,
  rang integer DEFAULT 0,
  ref_fourn varchar(30) DEFAULT NULL
) ENGINE=innodb;

CREATE TABLE llx_askpricesupplier_extrafields (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  tms timestamp,
  fk_object integer NOT NULL,
  import_key varchar(14) DEFAULT NULL
) ENGINE=innodb;

CREATE TABLE llx_askpricesupplierdet_extrafields (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  tms timestamp,
  fk_object integer NOT NULL,
  import_key varchar(14) DEFAULT NULL
) ENGINE=innodb;
-- End Module AskPriceSupplier --


ALTER TABLE llx_societe ADD COLUMN fk_incoterms integer;
ALTER TABLE llx_societe ADD COLUMN location_incoterms varchar(255);
ALTER TABLE llx_propal ADD COLUMN fk_incoterms integer;
ALTER TABLE llx_propal ADD COLUMN location_incoterms varchar(255);
ALTER TABLE llx_commande ADD COLUMN fk_incoterms integer;
ALTER TABLE llx_commande ADD COLUMN location_incoterms varchar(255);
ALTER TABLE llx_commande_fournisseur ADD COLUMN fk_incoterms integer;
ALTER TABLE llx_commande_fournisseur ADD COLUMN location_incoterms varchar(255);
ALTER TABLE llx_facture ADD COLUMN fk_incoterms integer;
ALTER TABLE llx_facture ADD COLUMN location_incoterms varchar(255);
ALTER TABLE llx_facture_fourn ADD COLUMN fk_incoterms integer;
ALTER TABLE llx_facture_fourn ADD COLUMN location_incoterms varchar(255);
ALTER TABLE llx_expedition ADD COLUMN fk_incoterms integer;
ALTER TABLE llx_expedition ADD COLUMN location_incoterms varchar(255);
ALTER TABLE llx_livraison ADD COLUMN 	fk_incoterms integer;
ALTER TABLE llx_livraison ADD COLUMN 	location_incoterms varchar(255);

CREATE TABLE llx_c_incoterms (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  code varchar(3) NOT NULL,
  libelle varchar(255) NOT NULL,
  active tinyint DEFAULT 1  NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_c_incoterms ADD UNIQUE INDEX uk_c_incoterms (code);

INSERT INTO llx_c_incoterms (code, libelle, active) VALUES ('EXW', 'Ex Works, au départ non chargé, non dédouané sortie d''usine (uniquement adapté aux flux domestiques, nationaux)', 1);
INSERT INTO llx_c_incoterms (code, libelle, active) VALUES ('FCA', 'Free Carrier, marchandises dédouanées et chargées dans le pays de départ, chez le vendeur ou chez le commissionnaire de transport de l''acheteur', 1);
INSERT INTO llx_c_incoterms (code, libelle, active) VALUES ('FAS', 'Free Alongside Ship, sur le quai du port de départ', 1);
INSERT INTO llx_c_incoterms (code, libelle, active) VALUES ('FOB', 'Free On Board, chargé sur le bateau, les frais de chargement dans celui-ci étant fonction du liner term indiqué par la compagnie maritime (à la charge du vendeur)', 1);
INSERT INTO llx_c_incoterms (code, libelle, active) VALUES ('CFR', 'Cost and Freight, chargé dans le bateau, livraison au port de départ, frais payés jusqu''au port d''arrivée, sans assurance pour le transport, non déchargé du navire à destination (les frais de déchargement sont inclus ou non au port d''arrivée)', 1);
INSERT INTO llx_c_incoterms (code, libelle, active) VALUES ('CIF', 'Cost, Insurance and Freight, chargé sur le bateau, frais jusqu''au port d''arrivée, avec l''assurance marchandise transportée souscrite par le vendeur pour le compte de l''acheteur', 1);
INSERT INTO llx_c_incoterms (code, libelle, active) VALUES ('CPT', 'Carriage Paid To, livraison au premier transporteur, frais jusqu''au déchargement du mode de transport, sans assurance pour le transport', 1);
INSERT INTO llx_c_incoterms (code, libelle, active) VALUES ('CIP', 'Carriage and Insurance Paid to, idem CPT, avec assurance marchandise transportée souscrite par le vendeur pour le compte de l''acheteur', 1);
INSERT INTO llx_c_incoterms (code, libelle, active) VALUES ('DAT', 'Delivered At Terminal, marchandises (déchargées) livrées sur quai, dans un terminal maritime, fluvial, aérien, routier ou ferroviaire désigné (dédouanement import, et post-acheminement payés par l''acheteur)', 1);
INSERT INTO llx_c_incoterms (code, libelle, active) VALUES ('DAP', 'Delivered At Place, marchandises (non déchargées) mises à disposition de l''acheteur dans le pays d''importation au lieu précisé dans le contrat (déchargement, dédouanement import payé par l''acheteur)', 1);
INSERT INTO llx_c_incoterms (code, libelle, active) VALUES ('DDP', 'Delivered Duty Paid, marchandises (non déchargées) livrées à destination finale, dédouanement import et taxes à la charge du vendeur ; l''acheteur prend en charge uniquement le déchargement (si exclusion des taxes type TVA, le préciser clairement)', 1);

-- Extrafields fk_object must be unique (1-1 relation)
ALTER TABLE llx_societe_extrafields DROP INDEX idx_societe_extrafields;
ALTER TABLE llx_societe_extrafields ADD UNIQUE INDEX uk_societe_extrafields (fk_object);

-- Module Donation
ALTER TABLE llx_don ADD COLUMN fk_country integer NOT NULL after country;
ALTER TABLE llx_don CHANGE COLUMN fk_paiement fk_payment integer;
ALTER TABLE llx_don ADD COLUMN paid smallint default 0 NOT NULL after fk_payment;
ALTER TABLE llx_don CHANGE COLUMN fk_don_projet fk_project integer NULL;

create table llx_don_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_don_extrafields ADD INDEX idx_don_extrafields (fk_object);

create table llx_payment_donation
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_donation     integer,
  datec           datetime,           -- date de creation
  tms             timestamp,
  datep           datetime,           -- payment date
  amount          real DEFAULT 0,
  fk_typepayment  integer NOT NULL,
  num_payment     varchar(50),
  note            text,
  fk_bank         integer NOT NULL,
  fk_user_creat   integer,            -- creation user
  fk_user_modif   integer             -- last modification user
)ENGINE=innodb;

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_VALIDATE','Customer invoice validated','Executed when a customer invoice is approved','facture',6);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_APPROVE','Supplier order request approved','Executed when a supplier order is approved','order_supplier',12);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_REFUSE','Supplier order request refused','Executed when a supplier order is refused','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_VALIDATE','Customer order validate','Executed when a customer order is validated','commande',4);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_VALIDATE','Customer proposal validated','Executed when a commercial proposal is validated','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_SENTBYMAIL','Mails sent from third party card','Executed when you send email from third party card','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_CREATE','Third party created','Executed when a third party is created','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_VALIDATE','Contract validated','Executed when a contract is validated','contrat',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_SENTBYMAIL','Commercial proposal sent by mail','Executed when a commercial proposal is sent by mail','propal',3);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SENTBYMAIL','Customer order sent by mail','Executed when a customer order is sent by mail ','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_PAYED','Customer invoice payed','Executed when a customer invoice is payed','facture',7);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_CANCEL','Customer invoice canceled','Executed when a customer invoice is conceled','facture',8);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SENTBYMAIL','Customer invoice sent by mail','Executed when a customer invoice is sent by mail','facture',9);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_VALIDATE','Supplier order validated','Executed when a supplier order is validated','order_supplier',11);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_SENTBYMAIL','Supplier order sent by mail','Executed when a supplier order is sent by mail','order_supplier',14);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_VALIDATE','Supplier invoice validated','Executed when a supplier invoice is validated','invoice_supplier',15);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_PAYED','Supplier invoice payed','Executed when a supplier invoice is payed','invoice_supplier',16);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_SENTBYMAIL','Supplier invoice sent by mail','Executed when a supplier invoice is sent by mail','invoice_supplier',17);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_CANCELED','Supplier invoice cancelled','Executed when a supplier invoice is cancelled','invoice_supplier',17);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_VALIDATE','Shipping validated','Executed when a shipping is validated','shipping',20);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_SENTBYMAIL','Shipping sent by mail','Executed when a shipping is sent by mail','shipping',21);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_VALIDATE','Member validated','Executed when a member is validated','member',22);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SUBSCRIPTION','Member subscribed','Executed when a member is subscribed','member',23);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_RESILIATE','Member resiliated','Executed when a member is resiliated','member',24);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_MODIFY','Member modified','Executed when a member is modified','member',24);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_DELETE','Member deleted','Executed when a member is deleted','member',25);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_UNVALIDATE','Customer invoice unvalidated','Executed when a customer invoice status set back to draft','facture',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_VALIDATE','Intervention validated','Executed when a intervention is validated','ficheinter',19);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_CLASSIFY_BILLED','Intervention set billed','Executed when a intervention is set to billed (when option FICHINTER_CLASSIFY_BILLED is set)','ficheinter',19);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_CLASSIFY_UNBILLED','Intervention set unbilled','Executed when a intervention is set to unbilled (when option FICHINTER_CLASSIFY_BILLED is set)','ficheinter',19);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_REOPEN','Intervention opened','Executed when a intervention is re-opened','ficheinter',19);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_SENTBYMAIL','Intervention sent by mail','Executed when a intervention is sent by mail','ficheinter',19);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_CREATE','Project creation','Executed when a project is created','project',30);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CLOSE_SIGNED','Customer proposal closed signed','Executed when a customer proposal is closed signed','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CLOSE_REFUSED','Customer proposal closed refused','Executed when a customer proposal is closed refused','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CLASSIFY_BILLED','Customer proposal set billed','Executed when a customer proposal is set to billed','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TASK_CREATE','Task created','Executed when a project task is created','project',35);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TASK_MODIFY','Task modified','Executed when a project task is modified','project',36);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TASK_DELETE','Task deleted','Executed when a project task is deleted','project',37);

create table llx_c_price_global_variable
(
	rowid					integer AUTO_INCREMENT PRIMARY KEY,
	code					varchar(20) NOT NULL,
	description		text DEFAULT NULL,
	value					double(24,8) DEFAULT 0
)ENGINE=innodb;

create table llx_c_price_global_variable_updater
(
	rowid						integer AUTO_INCREMENT PRIMARY KEY,
	type						integer NOT NULL,
	description			text DEFAULT NULL,
	parameters			text DEFAULT NULL,
	fk_variable			integer NOT NULL,
	update_interval	integer DEFAULT 0,
	next_update			integer DEFAULT 0,
	last_status			text DEFAULT NULL
)ENGINE=innodb;
