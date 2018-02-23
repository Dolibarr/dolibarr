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


-- For 8.0

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

insert into llx_c_type_container (code,label,module,active) values ('page',     'Page',     'system', 1);
insert into llx_c_type_container (code,label,module,active) values ('banner',   'Banner',   'system', 1);
insert into llx_c_type_container (code,label,module,active) values ('blogpost', 'BlogPost', 'system', 1);
insert into llx_c_type_container (code,label,module,active) values ('other',    'Other',    'system', 1);


ALTER TABLE llx_expensereport_det ADD COLUMN docnumber varchar(128) after fk_expensereport;


