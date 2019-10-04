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

ALTER TABLE llx_actioncomm MODIFY COLUMN code varchar(50);

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

UPDATE llx_chargesociales SET date_creation = tms WHERE date_creation IS NULL;

DROP TABLE llx_cotisation;
ALTER TABLE llx_accounting_bookkeeping DROP COLUMN validated;
ALTER TABLE llx_accounting_bookkeeping_tmp DROP COLUMN validated;

ALTER TABLE llx_loan ADD COLUMN insurance_amount double(24,8) DEFAULT 0;

ALTER TABLE llx_facture DROP INDEX idx_facture_uk_facnumber;
ALTER TABLE llx_facture CHANGE facnumber ref VARCHAR(30) NOT NULL;
ALTER TABLE llx_facture ADD UNIQUE INDEX uk_facture_ref (ref, entity);

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_CREATE','Ticket created','Executed when a ticket is created','ticket',161);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_MODIFY','Ticket modified','Executed when a ticket is modified','ticket',163);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_ASSIGNED','Ticket assigned','Executed when a ticket is assigned to another user','ticket',164);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_CLOSE','Ticket closed','Executed when a ticket is closed','ticket',165);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_SENTBYMAIL','Ticket message sent by email','Executed when a message is sent from the ticket record','ticket',166);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_DELETE','Ticket deleted','Executed when a ticket is deleted','ticket',167);

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
ALTER TABLE llx_adherent_type ADD morphy VARCHAR(3);
ALTER TABLE llx_subscription ADD fk_type integer;

UPDATE llx_subscription as s SET fk_type = (SELECT fk_adherent_type FROM llx_adherent as a where a.rowid = s.fk_adherent) where fk_type IS NULL; 

-- Add url_id into unique index of bank_url
ALTER TABLE llx_bank_url DROP INDEX uk_bank_url;
ALTER TABLE llx_bank_url ADD UNIQUE INDEX uk_bank_url (fk_bank, url_id, type);

ALTER TABLE llx_actioncomm ADD COLUMN calling_duration integer;
ALTER TABLE llx_actioncomm ADD COLUMN visibility varchar(12) DEFAULT 'default';

DROP TABLE llx_ticket_msg;

ALTER TABLE llx_don ADD COLUMN fk_soc integer NULL;

ALTER TABLE llx_payment_various ADD COLUMN subledger_account varchar(32);

ALTER TABLE llx_prelevement_facture_demande ADD COLUMN entity integer;
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

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('T','3','WeightUnitton','T', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('KG','0','WeightUnitkg','kg', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('G','-3','WeightUnitg','g', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MG','-6','WeightUnitmg','mg', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('OZ','98','WeightUnitounce','Oz', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('LB','99','WeightUnitpound','lb', 'weight', 1);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('M','0','SizeUnitm','m', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('DM','-1','SizeUnitdm','dm', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('CM','-2','SizeUnitcm','cm', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MM','-3','SizeUnitmm','mm', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('FT','98','SizeUnitfoot','ft', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('IN','99','SizeUnitinch','in', 'size', 1);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('M2','0','SurfaceUnitm2','m2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('DM2','-2','SurfaceUnitdm2','dm2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('CM2','-4','SurfaceUnitcm2','cm2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MM2','-6','SurfaceUnitmm2','mm2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('FT2','98','SurfaceUnitfoot2','ft2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('IN2','99','SurfaceUnitinch2','in2', 'surface', 1);


INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('M3','0','VolumeUnitm3','m3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('DM3','-3','VolumeUnitdm3','dm3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('CM3','-6','VolumeUnitcm3','cm3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MM3','-9','VolumeUnitmm3','mm3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('FT3','88','VolumeUnitfoot3','ft3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('IN3','89','VolumeUnitinch3','in3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('OZ3','97','VolumeUnitounce','Oz', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('L',  '98','VolumeUnitlitre','L', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('GAL','99','VolumeUnitgallon','gal', 'volume', 1);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('P','0','Piece','p', 'qty', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('SET', '0','Set','set', 'qty', 1);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('S','0','second','s', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MI','60','minute','i', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('H','3600','hour','h', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('D','86400','day','d', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('W','604800','week','w', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MO','2629800','month','m', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('Y','31557600','year','y', 'time', 1);

UPDATE llx_c_units SET short_label = 'i' WHERE code = 'MI';
UPDATE llx_c_units SET unit_type = 'weight', short_label = 'kg', scale = 0 WHERE code = 'KG';
UPDATE llx_c_units SET unit_type = 'weight', short_label = 'g', scale = -3 WHERE code = 'G';
UPDATE llx_c_units SET unit_type = 'time' WHERE code IN ('S','H','D');
UPDATE llx_c_units SET unit_type = 'size' WHERE code IN ('M','LM');
UPDATE llx_c_units SET label = 'SizeUnitm', scale = 0 WHERE code IN ('M');
UPDATE llx_c_units SET active = 0, scale = 0 WHERE code IN ('LM');
UPDATE llx_c_units SET unit_type = 'surface', scale = 0 WHERE code IN ('M2');
UPDATE llx_c_units SET unit_type = 'volume', scale = 0 WHERE code IN ('M3','L');
UPDATE llx_c_units SET scale = -3, active = 0 WHERE code IN ('L');
UPDATE llx_c_units SET label = 'VolumeUnitm3' WHERE code IN ('M3');
UPDATE llx_c_units SET label = 'SurfaceUnitm2' WHERE code IN ('M2');


-- Default Warehouse id for a user
ALTER TABLE llx_user ADD COLUMN fk_warehouse INTEGER NULL;

-- Save informations for online / API shopping and push to invoice
ALTER TABLE llx_commande ADD COLUMN module_source varchar(32);
ALTER TABLE llx_commande ADD COLUMN pos_source varchar(32);


ALTER TABLE llx_societe ADD COLUMN linkedin  varchar(255) after whatsapp;
ALTER TABLE llx_socpeople ADD COLUMN linkedin  varchar(255) after whatsapp;
ALTER TABLE llx_adherent ADD COLUMN linkedin  varchar(255) after whatsapp;
ALTER TABLE llx_user ADD COLUMN linkedin  varchar(255) after whatsapp;

ALTER TABLE llx_expensereport_det ADD COLUMN fk_ecm_files integer DEFAULT NULL;

ALTER TABLE llx_expensereport ADD COLUMN paid smallint default 0 NOT NULL;
UPDATE llx_expensereport set paid = 1 WHERE fk_statut = 6 and paid = 0;


CREATE TABLE llx_bom_bom(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity integer DEFAULT 1 NOT NULL,
	ref varchar(128) NOT NULL, 
	label varchar(255), 
	description text, 
	note_public text, 
	note_private text, 
	fk_product integer, 
	qty double(24,8),
	efficiency double(8,4),
	date_creation datetime NOT NULL, 
	tms timestamp, 
	date_valid datetime, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	fk_user_valid integer, 
	import_key varchar(14), 
	status integer NOT NULL 
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE llx_bom_bom ADD COLUMN efficiency double(8,4) DEFAULT 1;
ALTER TABLE llx_bom_bom ADD COLUMN entity integer DEFAULT 1 NOT NULL;
ALTER TABLE llx_bom_bom ADD COLUMN date_valid datetime;

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
	fk_bom integer NOT NULL, 
	fk_product integer NOT NULL,
	fk_bom_child integer NULL, 
	description text, 
	import_key varchar(14), 
	qty double(24,8) NOT NULL, 
	efficiency double(8,4) NOT NULL DEFAULT 1,
	position integer NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE llx_bom_bomline ADD COLUMN efficiency double(8,4) DEFAULT 1;
ALTER TABLE llx_bom_bomline ADD COLUMN fk_bom_child integer NULL;
ALTER TABLE llx_bom_bomline ADD COLUMN position integer NOT NULL;

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

ALTER TABLE llx_bom_bom ADD UNIQUE INDEX uk_bom_bom_ref(ref, entity);
ALTER TABLE llx_bom_bomline ADD CONSTRAINT llx_bom_bomline_fk_bom FOREIGN KEY (fk_bom) REFERENCES llx_bom_bom(rowid);


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

ALTER TABLE llx_user ADD COLUMN fk_user_expense_validator integer after fk_user;
ALTER TABLE llx_user ADD COLUMN fk_user_holiday_validator integer after fk_user_expense_validator;
ALTER TABLE llx_user ADD COLUMN personal_email varchar(255) after email;
ALTER TABLE llx_user ADD COLUMN personal_mobile varchar(20) after user_mobile;

ALTER TABLE llx_product ADD COLUMN fk_project integer DEFAULT NULL;
ALTER TABLE llx_product ADD INDEX idx_product_fk_project (fk_project);

ALTER TABLE llx_actioncomm ADD COLUMN calling_duration integer;

ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN datelastok datetime;
ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN maxemailpercollect integer DEFAULT 100;

DELETE FROM llx_const WHERE name = __ENCRYPT('THEME_ELDY_USE_HOVER')__ AND value = __ENCRYPT('0')__;
DELETE FROM llx_const WHERE name = __ENCRYPT('THEME_ELDY_USE_CHECKED')__ AND value = __ENCRYPT('0')__;

ALTER TABLE llx_inventorydet DROP COLUMN pmp; 
ALTER TABLE llx_inventorydet DROP COLUMN pa; 
ALTER TABLE llx_inventorydet DROP COLUMN new_pmp;

UPDATE llx_c_shipment_mode SET label = 'https://www.laposte.fr/outils/suivre-vos-envois?code={TRACKID}' WHERE code IN ('COLSUI');
UPDATE llx_c_shipment_mode SET label = 'https://www.laposte.fr/outils/suivre-vos-envois?code={TRACKID}' WHERE code IN ('LETTREMAX');



create table llx_reception
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  ref                   varchar(30)        NOT NULL,
  entity                integer  DEFAULT 1 NOT NULL,	-- multi company id
  fk_soc                integer            NOT NULL,
  fk_projet  		integer  DEFAULT NULL,
  
  ref_ext               varchar(30),					-- reference into an external system (not used by dolibarr)
  ref_int				varchar(30),					-- reference into an internal system (used by dolibarr to store extern id like paypal info)
  ref_supplier          varchar(30),					-- customer number
  
  date_creation         datetime,						-- date de creation
  fk_user_author        integer,						-- author of creation
  fk_user_modif         integer,						-- author of last change
  date_valid            datetime,						-- date de validation
  fk_user_valid         integer,						-- valideur
  date_delivery			datetime	DEFAULT NULL,		-- date planned of delivery
  date_reception       datetime,						
  fk_shipping_method    integer,
  tracking_number       varchar(50),
  fk_statut             smallint	DEFAULT 0,			-- 0 = draft, 1 = validated, 2 = billed or closed depending on WORKFLOW_BILL_ON_SHIPMENT option
  billed                smallint    DEFAULT 0,
  
  height                float,							-- height
  width                 float,							-- with
  size_units            integer,						-- unit of all sizes (height, width, depth)
  size                  float,							-- depth
  weight_units          integer,						-- unit of weight
  weight                float,							-- weight
  note_private          text,
  note_public           text,
  model_pdf             varchar(255),
  fk_incoterms          integer,						-- for incoterms
  location_incoterms    varchar(255),					-- for incoterms
  
  import_key			varchar(14),
  extraparams			varchar(255)							-- for other parameters with json format
)ENGINE=innodb;

ALTER TABLE llx_reception ADD UNIQUE INDEX idx_reception_uk_ref (ref, entity);

ALTER TABLE llx_reception ADD INDEX idx_reception_fk_soc (fk_soc);
ALTER TABLE llx_reception ADD INDEX idx_reception_fk_user_author (fk_user_author);
ALTER TABLE llx_reception ADD INDEX idx_reception_fk_user_valid (fk_user_valid);
ALTER TABLE llx_reception ADD INDEX idx_reception_fk_shipping_method (fk_shipping_method);

create table llx_reception_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_reception_extrafields ADD INDEX idx_reception_extrafields (fk_object);

ALTER TABLE llx_commande_fournisseur_dispatch ADD COLUMN fk_projet integer DEFAULT NULL;
ALTER TABLE llx_commande_fournisseur_dispatch ADD COLUMN fk_reception integer DEFAULT NULL;

ALTER TABLE llx_accounting_bookkeeping ADD COLUMN date_export datetime DEFAULT NULL after date_validated;

insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (110, 'supplier_proposal', 'internal', 'SALESREPFOLL',  'Responsable suivi de la demande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (111, 'supplier_proposal', 'external', 'BILLING',       'Contact fournisseur facturation', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (112, 'supplier_proposal', 'external', 'SHIPPING',      'Contact fournisseur livraison', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (113, 'supplier_proposal', 'external', 'SERVICE',       'Contact fournisseur prestation', 1);

ALTER TABLE llx_ticket_extrafields ADD INDEX idx_ticket_extrafields (fk_object);

-- Use special_code=3 in Takepos
-- VMYSQL4.1 UPDATE llx_facturedet AS fd LEFT JOIN llx_facture AS f ON f.rowid = fd.fk_facture SET fd.special_code = 4 WHERE f.module_source = 'takepos' AND fd.special_code = 3;

UPDATE llx_website_page set fk_user_creat = fk_user_modif WHERE fk_user_creat IS NULL and fk_user_modif IS NOT NULL;

