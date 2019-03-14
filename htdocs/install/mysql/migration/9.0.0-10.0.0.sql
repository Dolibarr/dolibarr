--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 10.0.0 or higher.
--
-- To restrict request to Mysql version x.y minimum use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y minimum use -- VPGSQLx.y
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To create a unique index ALTER TABLE llx_table ADD UNIQUE INDEX uk_table_field (field);
-- To drop an index:        -- VMYSQL4.1 DROP INDEX nomindex on llx_table
-- To drop an index:        -- VPGSQL8.2 DROP INDEX nomindex
-- To make pk to be auto increment (mysql):    -- VMYSQL4.3 ALTER TABLE llx_table CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres):
-- -- VPGSQL8.2 CREATE SEQUENCE llx_table_rowid_seq OWNED BY llx_table.rowid;
-- -- VPGSQL8.2 ALTER TABLE llx_table ADD PRIMARY KEY (rowid);
-- -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN rowid SET DEFAULT nextval('llx_table_rowid_seq');
-- -- VPGSQL8.2 SELECT setval('llx_table_rowid_seq', MAX(rowid)) FROM llx_table;
-- To set a field as NULL:                     -- VMYSQL4.3 ALTER TABLE llx_table MODIFY COLUMN name varchar(60) NULL;
-- To set a field as NULL:                     -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as NOT NULL:                 -- VMYSQL4.3 ALTER TABLE llx_table MODIFY COLUMN name varchar(60) NOT NULL;
-- To set a field as NOT NULL:                 -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET NOT NULL;
-- To set a field as default NULL:             -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- Note: fields with type BLOB/TEXT can't have default value.

-- Missing in 9.0

DROP TABLE llx_ticket_logs;

CREATE TABLE llx_pos_cash_fence(
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
	entity INTEGER DEFAULT 1 NOT NULL,
	ref VARCHAR(64),
	label VARCHAR(255),
	opening double(24,8) default 0,
	cash double(24,8) default 0,
	card double(24,8) default 0,
	cheque double(24,8) default 0,
	status INTEGER,
	date_creation DATETIME NOT NULL,
	date_valid DATETIME,
	day_close INTEGER,
	month_close INTEGER,
	year_close INTEGER,
	posmodule VARCHAR(30),
	posnumber VARCHAR(30),
	fk_user_creat integer,
	fk_user_valid integer,
	tms TIMESTAMP NOT NULL,
	import_key VARCHAR(14)
) ENGINE=innodb;



-- For 10.0

DROP TABLE llx_cotisation;

ALTER TABLE llx_loan ADD COLUMN insurance_amount double(24,8) DEFAULT 0;

ALTER TABLE llx_facture DROP INDEX idx_facture_uk_facnumber;
ALTER TABLE llx_facture CHANGE facnumber ref VARCHAR(30) NOT NULL;
ALTER TABLE llx_facture ADD UNIQUE INDEX uk_facture_ref (ref, entity);

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_CREATE','Ticket created','Executed when a ticket is created','ticket',161);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_MODIFY','Ticket modified','Executed when a ticket is modified','ticket',163);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_DELETE','Ticket deleted','Executed when a ticket is deleted','ticket',164);

create table llx_mailing_unsubscribe
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,	         -- multi company id
  email				varchar(255),
  unsubscribegroup	varchar(128) DEFAULT '',
  ip				varchar(128),
  date_creat		datetime,                            -- creation date
  tms               timestamp
)ENGINE=innodb;

ALTER TABLE llx_mailing_unsubscribe ADD UNIQUE uk_mailing_unsubscribe(email, entity, unsubscribegroup);

ALTER TABLE llx_adherent ADD gender VARCHAR(10);
ALTER TABLE llx_subscription ADD fk_type integer(11);

-- Add url_id into unique index of bank_url
ALTER TABLE llx_bank_url DROP INDEX uk_bank_url;
ALTER TABLE llx_bank_url ADD UNIQUE INDEX uk_bank_url (fk_bank, url_id, type);

ALTER TABLE llx_actioncomm ADD COLUMN calling_duration integer;

ALTER TABLE llx_don ADD COLUMN fk_soc integer NULL;

ALTER TABLE llx_payment_various ADD COLUMN subledger_account varchar(32);

ALTER TABLE llx_prelevement_facture_demande ADD COLUMN entity integer(11);
ALTER TABLE llx_prelevement_facture_demande ADD COLUMN sourcetype varchar(32);
ALTER TABLE llx_prelevement_facture_demande ADD COLUMN ext_payment_id varchar(128) NULL;
ALTER TABLE llx_prelevement_facture_demande ADD COLUMN ext_payment_site varchar(128) NULL;

-- Fix if table exists
ALTER TABLE llx_c_units DROP INDEX uk_c_units_code;
ALTER TABLE llx_c_units ADD COLUMN scale integer;
ALTER TABLE llx_c_units ADD COLUMN unit_type varchar(10);

-- Create if table dos not exists
CREATE TABLE llx_c_units(
	rowid integer AUTO_INCREMENT PRIMARY KEY,
	code varchar(3),
	scale integer,
	label varchar(50),
	short_label varchar(5),
	unit_type varchar(10),
	active tinyint DEFAULT 1 NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_c_units ADD UNIQUE uk_c_units_code(code);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('T',  '3','WeightUnitton','T', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('KG', '0','WeightUnitkg','Kg', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('G', '-3','WeightUnitg','g', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MG','-6','WeightUnitmg','mg', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('OZ','98','WeightUnitounce','Oz', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('LB','99','WeightUnitpound','lb', 'weight', 1);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('M',  '0','SizeUnitm','m', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('DM','-1','SizeUnitdm','dm', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('CM','-2','SizeUnitcm','cm', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MM','-3','SizeUnitmm','mm', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('FT','98','SizeUnitfoot','ft', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('IN','99','SizeUnitinch','in', 'size', 1);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('M2',  '0','SurfaceUnitm2','m2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('DM2','-2','SurfaceUnitdm2','dm2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('CM2','-4','SurfaceUnitcm2','cm2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MM2','-6','SurfaceUnitmm2','mm2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('FT2','98','SurfaceUnitfoot2','ft2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('IN2','99','SurfaceUnitinch2','in2', 'surface', 1);


INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('M3',  '0','VolumeUnitm3','m3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('DM3','-3','VolumeUnitdm3','dm3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('CM3','-6','VolumeUnitcm3','cm3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MM3','-9','VolumeUnitmm3','mm3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('FT3','88','VolumeUnitfoot3','ft3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('IN3','89','VolumeUnitinch3','in3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('OZ3','97','VolumeUnitounce','Oz', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('L',  '98','VolumeUnitlitre','L', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('GAL','99','VolumeUnitgallon','gal', 'volume', 1);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('P',   '0','Piece','p', 'qty', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('SET', '0','Set','set', 'qty', 1);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('S',       '0','second','s', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MI',     '60','minute','m', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('H',    '3600','hour','h', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('D','12960000','day','d', 'time', 1);

-- Default Warehouse id for a user
ALTER TABLE llx_user ADD COLUMN fk_warehouse INTEGER NULL;

-- Save informations for online / API shopping and push to invoice
ALTER TABLE llx_commande ADD COLUMN module_source varchar(32);
ALTER TABLE llx_commande ADD COLUMN pos_source varchar(32);


ALTER TABLE llx_societe ADD COLUMN linkedin  varchar(255) after whatsapp;
ALTER TABLE llx_socpeople ADD COLUMN linkedin  varchar(255) after whatsapp;
ALTER TABLE llx_adherent ADD COLUMN linkedin  varchar(255) after whatsapp;
ALTER TABLE llx_user ADD COLUMN linkedin  varchar(255) after whatsapp;




CREATE TABLE llx_bom_bom(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity integer DEFAULT 1 NOT NULL,
	ref varchar(128) NOT NULL, 
	label varchar(255), 
	description text, 
	note_public text, 
	note_private text, 
	date_creation datetime NOT NULL, 
	tms timestamp NOT NULL, 
	date_valid datetime, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	fk_user_valid integer, 
	import_key varchar(14), 
	status integer NOT NULL, 
	fk_product integer, 
	qty double(24,8)
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

create table llx_bom_bom_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

CREATE TABLE llx_bom_bomline(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	description text, 
	import_key varchar(14), 
	qty double(24,8), 
	fk_product integer, 
	fk_bom integer, 
	rank integer NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

create table llx_bom_bomline_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_bom_bom ADD INDEX idx_bom_bom_rowid (rowid);
ALTER TABLE llx_bom_bom ADD INDEX idx_bom_bom_ref (ref);
ALTER TABLE llx_bom_bom ADD CONSTRAINT llx_bom_bom_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_bom_bom ADD INDEX idx_bom_bom_status (status);
ALTER TABLE llx_bom_bom ADD INDEX idx_bom_bom_fk_product (fk_product);

ALTER TABLE llx_bom_bomline ADD INDEX idx_bom_bomline_rowid (rowid);
ALTER TABLE llx_bom_bomline ADD INDEX idx_bom_bomline_fk_product (fk_product);
ALTER TABLE llx_bom_bomline ADD INDEX idx_bom_bomline_fk_bom (fk_bom);

ALTER TABLE llx_product_fournisseur_price ADD COLUMN barcode varchar(180) DEFAULT NULL;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_barcode_type integer DEFAULT NULL;
ALTER TABLE llx_product_fournisseur_price ADD INDEX idx_product_barcode (barcode);
ALTER TABLE llx_product_fournisseur_price ADD INDEX idx_product_fk_barcode_type (fk_barcode_type);
ALTER TABLE llx_product_fournisseur_price ADD UNIQUE INDEX uk_product_barcode (barcode, fk_barcode_type, entity);
ALTER TABLE llx_product_fournisseur_price ADD CONSTRAINT fk_product_fournisseur_price_barcode_type FOREIGN KEY (fk_barcode_type) REFERENCES llx_c_barcode_type (rowid);

ALTER TABLE llx_facturedet_rec ADD COLUMN buy_price_ht double(24,8) DEFAULT 0;
ALTER TABLE llx_facturedet_rec ADD COLUMN fk_product_fournisseur_price integer DEFAULT NULL;

ALTER TABLE llx_facturedet_rec ADD COLUMN fk_user_author integer;
ALTER TABLE llx_facturedet_rec ADD COLUMN fk_user_modif integer;

ALTER TABLE llx_expensereport_det MODIFY COLUMN value_unit double(24,8) NOT NULL;
ALTER TABLE llx_expensereport_det ADD COLUMN subprice double(24,8) DEFAULT 0 NOT NULL after qty;

ALTER TABLE llx_product_attribute_combination ADD INDEX idx_product_att_com_product_parent (fk_product_parent);
ALTER TABLE llx_product_attribute_combination ADD INDEX idx_product_att_com_product_child (fk_product_child);

