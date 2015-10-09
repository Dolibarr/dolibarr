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

CREATE TABLE IF NOT EXISTS llx_c_hrm_function
(
  rowid     integer     PRIMARY KEY,
  pos   	tinyint DEFAULT 0 NOT NULL,
  code    	varchar(16) NOT NULL,
  label 	varchar(50),
  c_level   tinyint DEFAULT 0 NOT NULL,
  active  	tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(1, 5,'EXECBOARD', 'Executive board', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(2, 10, 'MANAGDIR', 'Managing director', 1, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(3, 15, 'ACCOUNTMANAG', 'Account manager', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(3, 20, 'ENGAGDIR', 'Engagement director', 1, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(4, 25, 'DIRECTOR', 'Director', 1, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(5, 30, 'PROJMANAG', 'Project manager', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(6, 35, 'DEPHEAD', 'Department head', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(7, 40, 'SECRETAR', 'Secretary', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(8, 45, 'EMPLOYEE', 'Department employee', 0, 1);

CREATE TABLE IF NOT EXISTS llx_c_hrm_department
(
  rowid      	integer     PRIMARY KEY,
  pos   		tinyint DEFAULT 0 NOT NULL,
  code    		varchar(16) NOT NULL,
  label 		varchar(50),
  active  		tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(1, 5,'MANAGEMENT', 'Management', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(2, 10,'GESTION', 'Gestion', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(3, 15,'TRAINING', 'Training', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(4, 20,'IT', 'Inform. Technology (IT)', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(5, 25,'MARKETING', 'Marketing', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(6, 30,'SALES', 'Sales', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(7, 35,'Legal', 'Legal', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(8, 40,'FINANCIAL', 'Financial accounting', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(9, 45,'HUMANRES', 'Human resources', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(10, 50,'PURCHASING', 'Purchasing', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(11, 55,'SERVICES', 'Services', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(12, 60,'CUSTOMSERV', 'Customer service', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(13, 65,'CONSULTING', 'Consulting', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(14, 70,'LOGISTIC', 'Logistics', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(15, 75,'CONSTRUCT', 'Engineering/design', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(16, 80,'PRODUCTION', 'Manufacturing', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(17, 85,'QUALITY', 'Quality assurance', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(18, 85,'MAINT', 'Plant assurance', 1);

CREATE TABLE IF NOT EXISTS llx_establishment (
  rowid 			integer NOT NULL auto_increment PRIMARY KEY,
  entity 			integer NOT NULL DEFAULT 1,
  name				varchar(50),
  address           varchar(255),
  zip               varchar(25),
  town              varchar(50),
  fk_state          integer DEFAULT 0,
  fk_country        integer DEFAULT 0,
  profid1			varchar(20),
  profid2			varchar(20),
  profid3			varchar(20),
  phone				varchar(20),
  fk_user_author 	integer NOT NULL,
  fk_user_mod		integer NOT NULL,
  datec				datetime NOT NULL,
  tms				timestamp NOT NULL,
  statut            tinyint DEFAULT 1,
) ENGINE=InnoDB;

