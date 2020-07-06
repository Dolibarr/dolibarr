--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 13.0.0 or higher.
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


-- Missing in v12

ALTER TABLE llx_prelevement_bons ADD COLUMN type varchar(16) DEFAULT 'debit-order';

ALTER TABLE llx_prelevement_facture_demande ADD INDEX idx_prelevement_facture_demande_fk_facture (fk_facture);
ALTER TABLE llx_prelevement_facture_demande ADD INDEX idx_prelevement_facture_demande_fk_facture_fourn (fk_facture_fourn);

ALTER TABLE llx_bom_bom MODIFY COLUMN duration double(24,8);


-- For v13

UPDATE llx_const SET value = 0 WHERE name = 'FACTURE_TVAOPTION' and value = 'franchise';
UPDATE llx_const SET value = 1 WHERE name = 'FACTURE_TVAOPTION' and value <> 'franchise' AND value <> '0' AND value <> '1';

ALTER TABLE llx_commande MODIFY COLUMN date_livraison DATETIME;

ALTER TABLE llx_website ADD COLUMN position integer DEFAULT 0;

ALTER TABLE llx_establishment ADD COLUMN ref varchar(30);
ALTER TABLE llx_establishment ADD COLUMN name varchar(128);
UPDATE llx_establishment SET ref = rowid WHERE ref IS NULL;
ALTER TABLE llx_establishment MODIFY COLUMN ref varchar(30) NOT NULL;
ALTER TABLE llx_establishment MODIFY COLUMN name varchar(128);

INSERT INTO llx_const (name, entity, value, type, visible) VALUES ('PRODUCT_PRICE_BASE_TYPE', 0, 'HT', 'string', 0);

ALTER TABLE llx_subscription MODIFY COLUMN datef DATETIME;

ALTER TABLE llx_loan_schedule ADD column fk_payment_loan INTEGER;


ALTER TABLE llx_user DROP COLUMN jabberid;
ALTER TABLE llx_user DROP COLUMN skype;
ALTER TABLE llx_user DROP COLUMN twitter;
ALTER TABLE llx_user DROP COLUMN facebook;
ALTER TABLE llx_user DROP COLUMN linkedin;
ALTER TABLE llx_user DROP COLUMN instagram;
ALTER TABLE llx_user DROP COLUMN snapchat;
ALTER TABLE llx_user DROP COLUMN googleplus;
ALTER TABLE llx_user DROP COLUMN youtube;
ALTER TABLE llx_user DROP COLUMN whatsapp;

ALTER TABLE llx_user ADD COLUMN datestartvalidity datetime;
ALTER TABLE llx_user ADD COLUMN dateendvalidity   datetime;

-- Intracomm Report
CREATE TABLE llx_c_transport_mode (
  rowid     integer AUTO_INCREMENT PRIMARY KEY,
  entity    integer	DEFAULT 1 NOT NULL,	-- multi company id
  code      varchar(3) NOT NULL,
  label     varchar(255) NOT NULL,
  active    tinyint DEFAULT 1  NOT NULL
) ENGINE=innodb;

INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('MAR', 'Transport maritime (y compris camions ou wagons sur bateau)', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('TRA', 'Transport par chemin de fer (y compris camions sur wagon)', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('ROU', 'Transport par route', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('AIR', 'Transport par air', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('POS', 'Envois postaux', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('OLE', 'Installations de transport fixe (oléoduc)', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('NAV', 'Transport par navigation intérieure', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('PRO', 'Propulsion propre', 1);

ALTER TABLE llx_facture ADD COLUMN fk_transport_mode integer after location_incoterms;
ALTER TABLE llx_facture_fourn ADD COLUMN fk_transport_mode integer after location_incoterms;

ALTER TABLE llx_societe ADD COLUMN transport_mode tinyint after cond_reglement;
ALTER TABLE llx_societe ADD COLUMN transport_mode_supplier tinyint after cond_reglement_supplier;

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

