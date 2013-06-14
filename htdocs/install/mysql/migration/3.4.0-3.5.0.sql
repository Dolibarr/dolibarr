--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.5.0 or higher. 
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):   VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres) VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE

-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


DELETE FROM llx_menu where module='holiday';

ALTER TABLE llx_projet_task ADD COLUMN planned_workload	real DEFAULT 0 NOT NULL AFTER duration_effective;

create table llx_fichinter_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_fichinter_extrafields ADD INDEX idx_ficheinter_extrafields (fk_object);

create table llx_commandedet_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    
  import_key       varchar(14)      	
)ENGINE=innodb;

ALTER TABLE llx_commandedet_extrafields ADD INDEX idx_commandedet_extrafields (fk_object);

create table llx_facturedet_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_facturedet_extrafields ADD INDEX idx_facturedet_extrafields (fk_object);

create table llx_propaldet_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_propaldet_extrafields ADD INDEX idx_propaldet_extrafields (fk_object);


DROP table llx_adherent_options;
DROP table llx_adherent_options_label;


