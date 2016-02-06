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
-- To make pk to be auto increment (mysql):    VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres): VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE
-- To set a field as NULL:                     VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as default NULL:             VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- Note: fields with type BLOB/TEXT can't have default value.
-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


ALTER TABLE llx_accounting_bookkeeping ADD COLUMN validated tinyint DEFAULT 0 NOT NULL;
ALTER TABLE llx_bank_account MODIFY COLUMN accountancy_journal varchar(16) DEFAULT NULL;

ALTER TABLE llx_fichinter ADD COLUMN datet date  after duree;
ALTER TABLE llx_fichinter ADD COLUMN datee date  after duree;
ALTER TABLE llx_fichinter ADD COLUMN dateo date  after duree;

ALTER TABLE llx_projet ADD COLUMN opp_percent double(5,2) after fk_opp_status;
UPDATE llx_projet as p set opp_percent = (SELECT percent from llx_c_lead_status as cls where cls.rowid = p.fk_opp_status) where opp_percent IS NULL;

ALTER TABLE llx_overwrite_trans ADD UNIQUE INDEX uk_overwrite_trans(lang, transkey);

ALTER TABLE llx_cronjob MODIFY COLUMN unitfrequency	varchar(255) NOT NULL DEFAULT '3600';
ALTER TABLE llx_cronjob ADD COLUMN test varchar(255) DEFAULT '1';

ALTER TABLE llx_facture ADD INDEX idx_facture_fk_statut (fk_statut);

UPDATE llx_projet as p set p.opp_percent = (SELECT percent FROM llx_c_lead_status as cls WHERE cls.rowid = p.fk_opp_status)  WHERE p.opp_percent IS NULL AND p.fk_opp_status IS NOT NULL;
 
 

CREATE TABLE llx_website
(
	rowid         integer AUTO_INCREMENT NOT NULL PRIMARY KEY,
	entity        integer DEFAULT 1,
	shortname     varchar(24) NOT NULL,
	description   varchar(255),
	status		  integer,
    date_creation     datetime,
    date_modification datetime,
	tms           timestamp
) ENGINE=innodb;
 
ALTER TABLE llx_website ADD UNIQUE INDEX uk_website_shortname (shortname, entity);

CREATE TABLE llx_website_page
(
	rowid         integer AUTO_INCREMENT NOT NULL PRIMARY KEY,
	fk_website    integer,
	pageurl       varchar(16) NOT NULL,
	title         varchar(255),						
	description   varchar(255),						
	keywords      varchar(255),
	content		  text,
    status        integer,
    date_creation     datetime,
    date_modification datetime,
	tms           timestamp
) ENGINE=innodb;

ALTER TABLE llx_website_page ADD UNIQUE INDEX uk_website_page_url (fk_website,pageurl);

ALTER TABLE llx_website_page ADD CONSTRAINT fk_website_page_website FOREIGN KEY (fk_website) REFERENCES llx_website (rowid);

ALTER TABLE llx_extrafields ADD COLUMN ishidden integer DEFAULT 0;

ALTER TABLE llx_paiementfourn ADD COLUMN ref varchar(30) AFTER rowid;
ALTER TABLE llx_paiementfourn ADD COLUMN entity integer AFTER ref;
 