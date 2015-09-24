--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.9.0 or higher.
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


INSERT INTO llx_const (name, value, type, note, visible) values ('MAIN_DELAY_EXPENSEREPORTS_TO_PAY','31','chaine','Tolérance de retard avant alerte (en jours) sur les notes de frais impayées',0);

ALTER TABLE llx_accounting_system MODIFY COLUMN pcg_version varchar(32);
ALTER TABLE llx_accountingaccount MODIFY COLUMN fk_pcg_version varchar(32);

UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_EXPORT_PREFIX_SPEC')__ WHERE __DECRYPT('name')__ = 'EXPORT_PREFIX_SPEC';

ALTER TABLE llx_accountingaccount RENAME TO llx_accounting_account;

ALTER TABLE llx_societe ADD COLUMN model_pdf varchar(255);

ALTER TABLE llx_societe_commerciaux ADD COLUMN import_key varchar(14) AFTER fk_user;

ALTER TABLE llx_categorie ADD COLUMN color varchar(8);

create table llx_overwrite_trans
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  lang            varchar(5),	-- en_US, fr_FR ...
  transkey	      varchar(128),
  transvalue      text
)ENGINE=innodb;

CREATE TABLE llx_dashboardlines (
  rowid          integer      AUTO_INCREMENT PRIMARY KEY,
  module         varchar(255) NOT NULL,
  class_file     varchar(255) NOT NULL,
  class_name     varchar(255) NOT NULL,
  class_func     varchar(255) NOT NULL,
  extra_param    varchar(255) DEFAULT NULL,
  allow_external smallint     DEFAULT 0 NOT NULL,
  perm           varchar(255) DEFAULT NULL,
  entity         integer      DEFAULT 1 NOT NULL
)ENGINE=innodb;
