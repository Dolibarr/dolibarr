--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 8.0.0 or higher.
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
-- To make pk to be auto increment (mysql):    -- VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres): -- VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE
-- To set a field as NULL:                     -- VMYSQL4.3 ALTER TABLE llx_table MODIFY COLUMN name varchar(60) NULL;
-- To set a field as NULL:                     -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as NOT NULL:                 -- VMYSQL4.3 ALTER TABLE llx_table MODIFY COLUMN name varchar(60) NOT NULL;
-- To set a field as NOT NULL:                 -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET NOT NULL;
-- To set a field as default NULL:             -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- Note: fields with type BLOB/TEXT can't have default value.


-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);



-- Forgot in 7.0

-- VMYSQL4.1 ALTER TABLE llx_product_association ADD COLUMN rowid integer AUTO_INCREMENT PRIMARY KEY;

ALTER TABLE llx_website_page ADD COLUMN fk_user_create integer;
ALTER TABLE llx_website_page ADD COLUMN fk_user_modif integer; 
ALTER TABLE llx_website_page ADD COLUMN type_container varchar(16) NOT NULL DEFAULT 'page';



-- For 8.0

ALTER TABLE llx_product_fournisseur_price DROP COLUMN unitcharges;

ALTER TABLE llx_societe ADD COLUMN fk_entrepot integer DEFAULT 0;
ALTER TABLE llx_projet ADD COLUMN bill_time integer DEFAULT 0;

ALTER TABLE llx_societe ADD COLUMN order_min_amount double(24,8) DEFAULT NULL AFTER outstanding_limit;
ALTER TABLE llx_societe ADD COLUMN supplier_order_min_amount double(24,8) DEFAULT NULL AFTER order_min_amount;


create table llx_c_type_container
(
  rowid      	integer AUTO_INCREMENT PRIMARY KEY,
  code          varchar(32) NOT NULL,
  entity		integer	DEFAULT 1 NOT NULL,	-- multi company id
  label 	    varchar(64)	NOT NULL,
  module     	varchar(32) NULL,
  active  	    tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_c_type_container ADD UNIQUE INDEX uk_c_type_container_id (code, entity);


ALTER TABLE llx_societe_remise_except ADD COLUMN discount_type integer DEFAULT 0 NOT NULL AFTER fk_soc;
ALTER TABLE llx_societe_remise_except ADD INDEX idx_societe_remise_except_discount_type (discount_type);
ALTER TABLE llx_societe ADD COLUMN remise_supplier real DEFAULT 0 AFTER remise_client;
CREATE TABLE llx_societe_remise_supplier
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,			-- multi company id
  fk_soc			integer NOT NULL,
  tms				timestamp,
  datec				datetime,							-- creation date
  fk_user_author	integer,							-- creation user
  remise_supplier	double(6,3)  DEFAULT 0 NOT NULL,	-- discount
  note				text
)ENGINE=innodb;
insert into llx_c_type_container (code,label,module,active) values ('page',     'Page',     'system', 1);
insert into llx_c_type_container (code,label,module,active) values ('banner',   'Banner',   'system', 1);
insert into llx_c_type_container (code,label,module,active) values ('blogpost', 'BlogPost', 'system', 1);
insert into llx_c_type_container (code,label,module,active) values ('other',    'Other',    'system', 1);

-- For supplier product buy price in multicurency
ALTER TABLE llx_product_fournisseur_price CHANGE COLUMN multicurrency_price_ttc multicurrency_unitprice DOUBLE(24,8) NULL DEFAULT NULL;
ALTER TABLE llx_product_fournisseur_price_log CHANGE COLUMN multicurrency_price_ttc multicurrency_unitprice DOUBLE(24,8) NULL DEFAULT NULL;

ALTER TABLE llx_expensereport_det ADD COLUMN docnumber varchar(128) after fk_expensereport;

ALTER TABLE llx_website_page ADD COLUMN aliasalt varchar(255) after pageurl;

-- Add missing keys and primary key
DELETE FROM llx_c_paiement WHERE code = '' or code = '-' or id = 0;
ALTER TABLE llx_c_paiement DROP INDEX uk_c_paiement;
ALTER TABLE llx_c_paiement ADD UNIQUE INDEX uk_c_paiement_code(entity, code);
ALTER TABLE llx_c_paiement CHANGE COLUMN id id INTEGER AUTO_INCREMENT PRIMARY KEY;

-- Add missing keys and primary key
ALTER TABLE llx_c_payment_term DROP INDEX uk_c_payment_term;
ALTER TABLE llx_c_payment_term CHANGE COLUMN rowid rowid INTEGER AUTO_INCREMENT PRIMARY KEY;
ALTER TABLE llx_c_payment_term ADD UNIQUE INDEX uk_c_payment_term_code(entity, code);

ALTER TABLE llx_oauth_token ADD COLUMN tokenstring text;

-- Add field for payment modes
ALTER TABLE llx_societe_rib ADD COLUMN type varchar(32) DEFAULT 'ban' after rowid;
ALTER TABLE llx_societe_rib ADD COLUMN last_four varchar(4);
ALTER TABLE llx_societe_rib ADD COLUMN card_type varchar(255);
ALTER TABLE llx_societe_rib ADD COLUMN cvn varchar(255);										
ALTER TABLE llx_societe_rib ADD COLUMN exp_date_month INTEGER;
ALTER TABLE llx_societe_rib ADD COLUMN exp_date_year INTEGER;
ALTER TABLE llx_societe_rib ADD COLUMN country_code varchar(10);
ALTER TABLE llx_societe_rib ADD COLUMN approved integer DEFAULT 0;
ALTER TABLE llx_societe_rib ADD COLUMN email varchar(255);
ALTER TABLE llx_societe_rib ADD COLUMN ending_date date;
ALTER TABLE llx_societe_rib ADD COLUMN max_total_amount_of_all_payments double(24,8);
ALTER TABLE llx_societe_rib ADD COLUMN preapproval_key varchar(255);
ALTER TABLE llx_societe_rib ADD COLUMN starting_date date;
ALTER TABLE llx_societe_rib ADD COLUMN total_amount_of_all_payments double(24,8);
ALTER TABLE llx_societe_rib ADD COLUMN stripe_card_ref varchar(128);
ALTER TABLE llx_societe_rib ADD COLUMN status integer NOT NULL DEFAULT 1;
   
CREATE TABLE llx_ticketsup
(
	rowid       integer AUTO_INCREMENT PRIMARY KEY,
	entity		integer DEFAULT 1,
    ref         varchar(128) NOT NULL,
	track_id    varchar(128) NOT NULL,
	fk_soc		integer DEFAULT 0,
	fk_project	integer DEFAULT 0,
	origin_email   varchar(128),
	fk_user_create	integer,
	fk_user_assign	integer,
	subject	varchar(255),
	message	text,
	fk_statut integer,
	resolution integer,
	progress varchar(100),
	timing varchar(20),
	type_code varchar(32),
	category_code varchar(32),
	severity_code varchar(32),
	datec datetime,
	date_read datetime,
	date_close datetime,
	tms timestamp
)ENGINE=innodb;

ALTER TABLE llx_ticketsup ADD COLUMN notify_tiers_at_create integer;
ALTER TABLE llx_ticketsup ADD UNIQUE uk_ticketsup_rowid_track_id (rowid, track_id);
ALTER TABLE llx_ticketsup ADD INDEX id_ticketsup_track_id (track_id);

CREATE TABLE llx_ticketsup_msg
(
	rowid       integer AUTO_INCREMENT PRIMARY KEY,
	entity		integer DEFAULT 1,
	fk_track_id   varchar(128),
	fk_user_action	integer,
	datec datetime,
	message	text,
	private		integer DEFAULT 0
)ENGINE=innodb;


ALTER TABLE llx_ticketsup_msg ADD CONSTRAINT fk_ticketsup_msg_fk_track_id FOREIGN KEY (fk_track_id) REFERENCES llx_ticketsup (track_id);

CREATE TABLE llx_ticketsup_logs
(
	rowid       integer AUTO_INCREMENT PRIMARY KEY,
	entity		integer DEFAULT 1,
	fk_track_id   varchar(128),
	fk_user_create	integer,
	datec datetime,
	message	text
)ENGINE=innodb;

ALTER TABLE llx_ticketsup_logs ADD CONSTRAINT fk_ticketsup_logs_fk_track_id FOREIGN KEY (fk_track_id) REFERENCES llx_ticketsup (track_id);

CREATE TABLE llx_ticketsup_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    
  import_key       varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_c_ticketsup_category ADD INDEX idx_code (code);

CREATE TABLE llx_c_ticketsup_category
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  code			varchar(32)				NOT NULL,
  pos			varchar(32)				NOT NULL,
  label			varchar(128)			NOT NULL,
  active		integer DEFAULT 1,
  use_default	integer DEFAULT 1,
  description	varchar(255)
)ENGINE=innodb;

ALTER TABLE llx_c_ticketsup_severity ADD INDEX idx_code (code);

CREATE TABLE llx_c_ticketsup_severity
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  code			varchar(32)				NOT NULL,
  pos			varchar(32)				NOT NULL,
  label			varchar(128)			NOT NULL,
  color			varchar(10)				NOT NULL,
  active		integer DEFAULT 1,
  use_default	integer DEFAULT 1,
  description	varchar(255)
)ENGINE=innodb;

ALTER TABLE llx_c_ticketsup_type ADD INDEX idx_code (code);

CREATE TABLE llx_c_ticketsup_type
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  code			varchar(32)				NOT NULL,
  pos			varchar(32)				NOT NULL,
  label			varchar(128)			NOT NULL,
  active		integer DEFAULT 1,
  use_default	integer DEFAULT 1,
  description	varchar(255)
)ENGINE=innodb;

