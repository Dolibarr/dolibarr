--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 11.0.0 or higher.
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

-- Missing in 10.0


-- For 11.0

-- Intracomm Report
CREATE TABLE llx_c_transport_mode (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  code varchar(3) NOT NULL,
  label varchar(255) NOT NULL,
  active tinyint DEFAULT 1  NOT NULL
) ENGINE=innodb;

INSERT INTO llx_c_transport_mode (code, libelle, active) VALUES ('MAR', 'Transport maritime (y compris camions ou wagons sur bateau)', 1);
INSERT INTO llx_c_transport_mode (code, libelle, active) VALUES ('TRA', 'Transport par chemin de fer (y compris camions sur wagon)', 1);
INSERT INTO llx_c_transport_mode (code, libelle, active) VALUES ('ROU', 'Transport par route', 1);
INSERT INTO llx_c_transport_mode (code, libelle, active) VALUES ('AIR', 'Transport par air', 1);
INSERT INTO llx_c_transport_mode (code, libelle, active) VALUES ('POS', 'Envois postaux', 1);
INSERT INTO llx_c_transport_mode (code, libelle, active) VALUES ('OLE', 'Installations de transport fixe (oléoduc)', 1);
INSERT INTO llx_c_transport_mode (code, libelle, active) VALUES ('NAV', 'Transport par navigation intérieure', 1);
INSERT INTO llx_c_transport_mode (code, libelle, active) VALUES ('PRO', 'Propulsion propre', 1);

ALTER TABLE llx_facture ADD COLUMN fk_mode_transport integer after location_incoterms;
ALTER TABLE llx_facture_fourn ADD COLUMN fk_mode_transport integer after location_incoterms;

CREATE TABLE llx_intracommreport
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  
  ref				varchar(30)        NOT NULL,			-- report reference number
  entity			integer  DEFAULT 1 NOT NULL,			-- multi company id
  type_declaration	varchar(32),
  period			varchar(32),
  mode				varchar(32),
  content_xml		text,
  type_export		varchar(10),
  datec             datetime,
  tms               timestamp
)ENGINE=innodb;

create table llx_entrepot_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_entrepot_extrafields ADD INDEX idx_entrepot_extrafields (fk_object);


ALTER TABLE llx_c_shipment_mode ADD COLUMN entity integer DEFAULT 1 NOT NULL;

ALTER TABLE llx_c_shipment_mode DROP INDEX uk_c_shipment_mode;
ALTER TABLE llx_c_shipment_mode ADD UNIQUE INDEX uk_c_shipment_mode (code, entity);
