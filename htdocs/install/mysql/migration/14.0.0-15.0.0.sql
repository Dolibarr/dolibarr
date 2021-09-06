--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 14.0.0 or higher.
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
-- To rebuild sequence for postgresql after insert by forcing id autoincrement fields:
-- -- VPGSQL8.2 SELECT dol_util_rebuild_sequences();


-- Missing in v14 or lower

-- VMYSQL4.3 ALTER TABLE llx_partnership MODIFY COLUMN date_partnership_end date NULL;
-- VPGSQL8.2 ALTER TABLE llx_partnership ALTER COLUMN date_partnership_end DROP NOT NULL;


-- v15

ALTER TABLE llx_emailcollector_emailcollectoraction MODIFY COLUMN actionparam TEXT;

ALTER TABLE llx_knowledgemanagement_knowledgerecord ADD lang varchar(6);
ALTER TABLE llx_knowledgemanagement_knowledgerecord ADD COLUMN entity integer DEFAULT 1;

CREATE TABLE llx_categorie_ticket
(
  fk_categorie  integer NOT NULL,
  fk_ticket    integer NOT NULL,
  import_key    varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_categorie_ticket ADD PRIMARY KEY pk_categorie_ticket (fk_categorie, fk_ticket);
ALTER TABLE llx_categorie_ticket ADD INDEX idx_categorie_ticket_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_ticket ADD INDEX idx_categorie_ticket_fk_ticket (fk_ticket);

ALTER TABLE llx_categorie_ticket ADD CONSTRAINT fk_categorie_ticket_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_ticket ADD CONSTRAINT fk_categorie_ticket_ticket_rowid   FOREIGN KEY (fk_ticket) REFERENCES llx_ticket (rowid);
ALTER TABLE llx_product_fournisseur_price MODIFY COLUMN ref_fourn varchar(128);
ALTER TABLE llx_product_customer_price MODIFY COLUMN ref_customer varchar(128);

-- -- add action trigger
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('ORDER_SUPPLIER_CANCEL','Supplier order request canceled','Executed when a supplier order is canceled','order_supplier',13);

ALTER TABLE llx_product ADD COLUMN fk_default_bom integer DEFAULT NULL;

ALTER TABLE llx_mrp_mo ADD COLUMN mrptype integer DEFAULT 0;

DELETE FROM llx_menu WHERE type = 'top' AND module = 'cashdesk' AND mainmenu = 'cashdesk';


INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang) values ('MEMBER_EXCLUDE', 'Member excluded', 'Executed when a member is excluded', 'member', 27);

-- Stock transfers

CREATE TABLE llx_stocktransfer_stocktransfer(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    entity integer  DEFAULT 1 NOT NULL,
	ref varchar(128) DEFAULT '(PROV)' NOT NULL, 
	label varchar(255), 
	fk_soc integer, 
	fk_project integer,
    fk_warehouse_source integer,
    fk_warehouse_destination integer,
    description text,
	note_public text, 
	note_private text,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    date_creation datetime NOT NULL,
    date_prevue_depart date DEFAULT NULL,
    date_reelle_depart date DEFAULT NULL,
    date_prevue_arrivee date DEFAULT NULL,
    date_reelle_arrivee date DEFAULT NULL,
    lead_time_for_warning integer DEFAULT NULL,
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	import_key varchar(14), 
	model_pdf varchar(255), 
	last_main_doc varchar(255),
	status smallint NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE llx_stocktransfer_stocktransfer ADD INDEX idx_stocktransfer_stocktransfer_rowid (rowid);
ALTER TABLE llx_stocktransfer_stocktransfer ADD INDEX idx_stocktransfer_stocktransfer_ref (ref);
ALTER TABLE llx_stocktransfer_stocktransfer ADD INDEX idx_stocktransfer_stocktransfer_fk_soc (fk_soc);
ALTER TABLE llx_stocktransfer_stocktransfer ADD INDEX idx_stocktransfer_stocktransfer_fk_project (fk_project);
ALTER TABLE llx_stocktransfer_stocktransfer ADD CONSTRAINT llx_stocktransfer_stocktransfer_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_stocktransfer_stocktransfer ADD INDEX idx_stocktransfer_stocktransfer_status (status);

CREATE TABLE llx_stocktransfer_stocktransferline(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	amount double DEFAULT NULL, 
	qty real,
    fk_warehouse_source integer NOT NULL,
    fk_warehouse_destination integer NOT NULL,
	fk_stocktransfer integer NOT NULL, 
	fk_product integer NOT NULL,
    batch varchar(128) DEFAULT NULL,	-- Lot or serial number
    pmp double,
    rang integer DEFAULT 0,
    fk_parent_line integer NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE llx_stocktransfer_stocktransferline ADD INDEX idx_stocktransfer_stocktransferline_rowid (rowid);

create table llx_stocktransfer_stocktransfer_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_stocktransfer_stocktransfer_extrafields ADD INDEX idx_fk_object(fk_object);

create table llx_stocktransfer_stocktransferline_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_stocktransfer_stocktransferline_extrafields ADD INDEX idx_fk_object(fk_object);

ALTER TABLE llx_stock_mouvement CHANGE origintype origintype VARCHAR(64)
