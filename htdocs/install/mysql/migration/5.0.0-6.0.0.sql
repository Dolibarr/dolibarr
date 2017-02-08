--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 5.0.0 or higher.
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


ALTER TABLE llx_ecm_files ADD COLUMN ref varchar(128) AFTER rowid;
ALTER TABLE llx_ecm_files CHANGE COLUMN fullpath filepath varchar(255);
ALTER TABLE llx_ecm_files CHANGE COLUMN filepath filepath varchar(255);
ALTER TABLE llx_ecm_files ADD COLUMN position integer;
ALTER TABLE llx_ecm_files ADD COLUMN keyword varchar(750);
ALTER TABLE llx_ecm_files CHANGE COLUMN keyword keyword varchar(750);
ALTER TABLE llx_ecm_files ADD COLUMN gen_or_uploaded varchar(12);

ALTER TABLE llx_ecm_files DROP INDEX uk_ecm_files;
ALTER TABLE llx_ecm_files ADD UNIQUE INDEX uk_ecm_files (filepath, filename, entity);

ALTER TABLE llx_ecm_files ADD INDEX idx_ecm_files_label (label);


insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_CREATE','Product or service created','Executed when a product or sevice is created','product',30);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_MODIFY','Product or service modified','Executed when a product or sevice is modified','product',30);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_DELETE','Product or service deleted','Executed when a product or sevice is deleted','product',30);

ALTER TABLE llx_c_email_templates ADD COLUMN content_lines text;

ALTER TABLE llx_loan ADD COLUMN fk_projet integer DEFAULT NULL;

ALTER TABLE llx_resource ADD available INTEGER;
ALTER TABLE llx_resource ADD management_type integer DEFAULT 0 NOT NULL;
ALTER TABLE llx_resource ADD duration varchar(6) NOT NULL;
ALTER TABLE llx_resource ADD starting_hour INTEGER;
ALTER TABLE llx_resource ADD fk_country integer DEFAULT NULL;
ALTER TABLE llx_resource ADD INDEX idx_resource_fk_country (fk_country);
ALTER TABLE llx_resource ADD CONSTRAINT fk_resource_fk_country FOREIGN KEY (fk_country) REFERENCES llx_c_country (rowid);
ALTER TABLE llx_element_resources DROP COLUMN busy;
ALTER TABLE llx_element_resources DROP INDEX idx_element_resources_idx1;
ALTER TABLE llx_element_resources DROP INDEX idx_element_element_element_id;
ALTER TABLE llx_element_resources ADD INDEX idx_element_resources_resource (resource_id, resource_type);
ALTER TABLE llx_element_resources ADD INDEX idx_element_resources_element (element_id, element_type);
ALTER TABLE llx_element_resources ADD fk_parent INTEGER DEFAULT 0 NOT NULL AFTER rowid;
ALTER TABLE llx_element_resources ADD dependency INTEGER DEFAULT 0 NOT NULL;

CREATE TABLE llx_resource_schedule
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  entity          integer DEFAULT 1 NOT NULL,	-- multi company id
  fk_resource     integer NOT NULL,
  schedule_year   integer NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_resource_schedule ADD UNIQUE INDEX uk_llx_resource_schedule (fk_resource, schedule_year);

CREATE TABLE llx_resource_schedule_section
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_schedule     integer NOT NULL,
  date_start      integer NOT NULL,
  date_end        integer NOT NULL,
  status          integer NOT NULL,
  status_manual   integer NOT NULL,
  booker_id       integer,           -- id of booker
  booker_type     varchar(64),       -- booker type
  booker_count    integer DEFAULT 0 NOT NULL   -- booker count
)ENGINE=innodb;

ALTER TABLE llx_resource_schedule_section ADD UNIQUE INDEX uk_llx_resource_schedule_section (fk_schedule, date_start, date_end);
ALTER TABLE llx_resource_schedule_section ADD INDEX idx_llx_resource_schedule_section_status (status);

CREATE TABLE llx_resource_placement
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  entity            integer DEFAULT 1 NOT NULL, -- multi company id
  ref_client        varchar(255),               -- reference for customer
  fk_soc            integer NOT NULL,           -- thirdparty id
  fk_resource       integer NOT NULL,
  fk_user           integer NOT NULL,
  date_creation     datetime NOT NULL,
  date_start        datetime NOT NULL,
  date_end          datetime NOT NULL
)ENGINE=innodb;

CREATE TABLE llx_resource_log
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_resource     integer NOT NULL,
  fk_user         integer NOT NULL,
  booker_id       integer,           -- id of booker
  booker_type     varchar(64),       -- booker type
  date_creation   datetime NOT NULL,
  date_start      datetime NOT NULL,
  date_end        datetime NOT NULL,
  status          integer NOT NULL,
  action          integer NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_resource_log ADD INDEX idx_llx_resource_schedule (fk_resource);
