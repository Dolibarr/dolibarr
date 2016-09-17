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
-- To make pk to be auto increment (mysql):    -- VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres): -- VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE
-- To set a field as NULL:                     -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as default NULL:             -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- Note: fields with type BLOB/TEXT can't have default value.
-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


-- VPGSQL8.2 ALTER TABLE llx_product_lot ALTER COLUMN entity SET DEFAULT 1;
ALTER TABLE llx_product_lot MODIFY COLUMN entity integer DEFAULT 1;
UPDATE llx_product_lot SET entity = 1 WHERE entity IS NULL;


DELETE FROM llx_menu where module='expensereport';

ALTER TABLE llx_user DROP COLUMN phenix_login;
ALTER TABLE llx_user DROP COLUMN phenix_pass;

ALTER TABLE llx_societe ADD COLUMN fk_account integer;

ALTER TABLE llx_commandedet ADD COLUMN fk_commandefourndet integer DEFAULT NULL after import_key;   -- link to detail line of commande fourn (resplenish)
ALTER TABLE llx_commandedet MODIFY COLUMN fk_commandefourndet integer DEFAULT NULL;

ALTER TABLE llx_website ADD COLUMN virtualhost varchar(255) after fk_default_home;

ALTER TABLE llx_chargesociales ADD COLUMN fk_account integer after fk_type;
ALTER TABLE llx_chargesociales ADD COLUMN fk_mode_reglement integer after fk_account;

ALTER TABLE llx_ecm_files ADD COLUMN gen_or_uploaded varchar(12) after cover; 

DROP TABLE llx_document_generator;
DROP TABLE llx_ecm_documents;
DROP TABLE llx_holiday_events;
DROP TABLE llx_holiday_types;

ALTER TABLE llx_notify ADD COLUMN type_target varchar(16) NULL;

ALTER TABLE llx_entrepot DROP COLUMN valo_pmp;

ALTER TABLE llx_notify_def MODIFY COLUMN fk_soc integer NULL;
-- VPGSQL8.2 ALTER TABLE llx_notify_def ALTER COLUMN fk_soc SET DEFAULT NULL;


create table llx_categorie_project
(
  fk_categorie  integer NOT NULL,
  fk_project    integer NOT NULL,
  import_key    varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_categorie_project ADD PRIMARY KEY pk_categorie_project (fk_categorie, fk_project);
ALTER TABLE llx_categorie_project ADD INDEX idx_categorie_project_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_project ADD INDEX idx_categorie_project_fk_project (fk_project);

ALTER TABLE llx_categorie_project ADD CONSTRAINT fk_categorie_project_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_project ADD CONSTRAINT fk_categorie_project_fk_project_rowid FOREIGN KEY (fk_project) REFERENCES llx_projet (rowid);

ALTER TABLE llx_societe_remise_except ADD COLUMN entity	integer DEFAULT 1 NOT NULL after rowid;
ALTER TABLE llx_societe_remise ADD COLUMN entity	integer DEFAULT 1 NOT NULL after rowid;


create table llx_expensereport_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_expensereport_extrafields ADD INDEX idx_expensereport_extrafields (fk_object);

ALTER TABLE llx_cotisation RENAME TO llx_subscription;
ALTER TABLE llx_subscription ADD UNIQUE INDEX uk_subscription (fk_adherent,dateadh);
ALTER TABLE llx_subscription CHANGE COLUMN cotisation subscription real;
ALTER TABLE llx_adherent_type CHANGE COLUMN cotisation subscription varchar(3) NOT NULL DEFAULT 'yes';

create table llx_product_lot_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_product_lot_extrafields ADD INDEX idx_product_lot_extrafields (fk_object);


