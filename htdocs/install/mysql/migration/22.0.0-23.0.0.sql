--
-- This file is executed by calling /install/index.php page
-- when current version is higher than the name of this file.
-- Be carefull in the position of each SQL request.
--
-- To restrict request to Mysql version x.y minimum use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y minimum use -- VPGSQLx.y
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
--                          Note that "RENAME TO" is both compatible with mysql/postgesql, not the "RENAME" alone.
--                          Also you must complete with renaming the sequence for PGSQL with -- VPGSQL8.2 ALTER SEQUENCE llx_table_rowid_seq RENAME TO llx_table_new_rowid_seq;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key or constraint:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To create a unique index:              ALTER TABLE llx_table ADD UNIQUE INDEX uk_table_field (field);
-- To drop an index:        -- VMYSQL4.1 DROP INDEX nomindex ON llx_table;
-- To drop an index:        -- VPGSQL8.2 DROP INDEX nomindex;
-- To make pk to be auto increment (mysql):
-- -- VMYSQL4.3 ALTER TABLE llx_table ADD PRIMARY KEY(rowid);
-- -- VMYSQL4.3 ALTER TABLE llx_table CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
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
-- To rebuild sequence for postgresql after insert, by forcing id autoincrement fields:
-- -- VPGSQL8.2 SELECT dol_util_rebuild_sequences();


-- V22 forgotten

ALTER TABLE llx_extrafields ADD module varchar(64) AFTER enabled;

ALTER TABLE llx_opensurvey_user_studs ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;


-- V23 migration


ALTER TABLE llx_ticket ADD COLUMN note_public text after resolution;
ALTER TABLE llx_ticket ADD COLUMN note_private text after resolution;
ALTER TABLE llx_ticket ADD COLUMN fk_user_modif integer after resolution;


CREATE TABLE llx_paiement_extrafields (
	rowid                     integer AUTO_INCREMENT PRIMARY KEY,
	tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_object                 integer NOT NULL,
	import_key                varchar(14)                                 -- import key
) ENGINE=innodb;

ALTER TABLE llx_paiement_extrafields ADD UNIQUE INDEX uk_paiement_extrafields (fk_object);

CREATE TABLE llx_accounting_analytic_axis (
	rowid               integer         AUTO_INCREMENT PRIMARY KEY,
	label               varchar(255)    NOT NULL,
	code                varchar(32)     NOT NULL,
	active              integer         DEFAULT 1,
	entity              integer         DEFAULT 1 NOT NULL,
	datec               datetime,
	tms                 timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_author      integer         NOT NULL,
	fk_user_modif       integer,
	import_key          varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_accounting_analytic_axis ADD UNIQUE INDEX uk_accounting_analytic_axis(code, entity);

CREATE TABLE llx_accounting_analytic_account (
	rowid               integer         AUTO_INCREMENT PRIMARY KEY,
	label               varchar(255)    NOT NULL,
	code                varchar(32)     NOT NULL,
	description			text,
	fk_axis				integer			NOT NULL,
	active              integer         DEFAULT 1,
	entity              integer         DEFAULT 1 NOT NULL,
	date_start			date,
	date_end			date,
	datec               datetime,
	tms                 timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_author      integer         NOT NULL,
	fk_user_modif       integer,
	import_key          varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_accounting_analytic_account ADD UNIQUE INDEX uk_accounting_analytic_account(code, entity);
ALTER TABLE llx_accounting_analytic_account ADD CONSTRAINT fk_accounting_analytic_account_fk_axis FOREIGN KEY (fk_axis) REFERENCES llx_accounting_analytic_axis (rowid);


CREATE TABLE llx_accounting_analytic_distribution (
	rowid				integer			AUTO_INCREMENT PRIMARY KEY,
	fk_source_line		integer			NOT NULL,		-- id de la ligne source (facture, écriture, etc.)
	sourcetype			varchar(32)		NOT NULL,		-- ex: 'facturedet', 'accounting_line'
	fk_analytic_account integer			NOT NULL,
	percentage			real			DEFAULT 100,	-- ex: 50.0 pour 50%
	amount				double(24,8),					-- optional, if you prefer to work on the amount
	entity				integer			DEFAULT 1,
	datec               datetime,
	tms                 timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_author      integer         NOT NULL,
	fk_user_modif       integer,
	import_key          varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_accounting_analytic_distribution ADD CONSTRAINT fk_accounting_analytic_distribution_fk_analytic_account FOREIGN KEY (fk_analytic_account) REFERENCES llx_accounting_analytic_account (rowid);

ALTER TABLE llx_facture ADD COLUMN dispute_status integer DEFAULT 0 after payment_reference;
ALTER TABLE llx_facture ADD COLUMN ip varchar(250);

ALTER TABLE llx_commande ADD COLUMN ip varchar(250);
ALTER TABLE llx_commande ADD COLUMN user_agent varchar(255);

ALTER TABLE llx_commande_fournisseur ADD COLUMN date_reception datetime default NULL;

ALTER TABLE llx_c_currencies ADD COLUMN max_decimal_unit tinyint NULL;	-- Number of decimal in this currency for unit prices
ALTER TABLE llx_c_currencies ADD COLUMN max_decimal_tot tinyint NULL;	-- Number of decimal in this currency for total prices

ALTER TABLE llx_oauth_token ADD COLUMN lastaccess    	datetime NULL;						-- updated at each api access
ALTER TABLE llx_oauth_token ADD COLUMN apicount_previous_month BIGINT UNSIGNED DEFAULT 0;
ALTER TABLE llx_oauth_token ADD COLUMN apicount_month BIGINT UNSIGNED DEFAULT 0;			-- increased by 1 at each page access, saved into pageviews_previous_month when on different month than lastaccess
ALTER TABLE llx_oauth_token ADD COLUMN apicount_total BIGINT UNSIGNED DEFAULT 0;			-- increased by 1 at each page access, no reset

ALTER TABLE llx_webhook_target ADD COLUMN entity integer DEFAULT 1 NOT NULL;

ALTER TABLE llx_extrafields ADD COLUMN emptyonclone integer DEFAULT 0 AFTER alwayseditable;

ALTER TABLE llx_blockedlog ADD COLUMN object_format varchar(16) DEFAULT 'V1' AFTER object_version;
UPDATE llx_blockedlog SET object_format = '' WHERE object_version = '18' OR object_version = ''  OR object_version IS NULL;

INSERT INTO llx_c_country (rowid, code, code_iso, label, active, favorite, numeric_code) VALUES (248, 'BQ', 'BES', 'Bonaire, Sint Eustatius and Saba', 1, 0, 535);
INSERT INTO llx_c_country (rowid, code, code_iso, label, active, favorite, numeric_code) VALUES (249, 'GP', 'GLP', 'Guadeloupe', 0, 0, 312);
INSERT INTO llx_c_country (rowid, code, code_iso, label, active, favorite, numeric_code) VALUES (250, 'GY', 'GUY', 'Guyana', 0, 0, 328);
INSERT INTO llx_c_country (rowid, code, code_iso, label, active, favorite, numeric_code) VALUES (251, 'MQ', 'MTQ', 'Martinique', 0, 0, 474);
INSERT INTO llx_c_country (rowid, code, code_iso, label, active, favorite, numeric_code) VALUES (252, 'RE', 'REU', 'Reunion', 0, 0, 638);
INSERT INTO llx_c_country (rowid, code, code_iso, label, active, favorite, numeric_code) VALUES (253, 'SS', 'SSD', 'South Sudan', 1, 0, 728);

UPDATE llx_c_country SET sepa = 1 WHERE code IN ('AD','AL','AT','AX','BE','BG','BL','CH','CY','CZ','DE','DK','EE','ES','FI','FR','GB','GF','GG','GI','GP','GR','HR','HU','IE','IM','IS','IT','JE','LI','LT','LU','LV','MC','MD','ME','MF','MK','MQ','MT','NL','NO','PL','PM','PT','RE','RO','RS','SE','SI','SK','SM','VA','YT');

ALTER TABLE llx_user DROP COLUMN egroupware_id;

ALTER TABLE llx_adherent ADD COLUMN birth_place varchar(64) after birth;

ALTER TABLE llx_societe ADD COLUMN birth date DEFAULT NULL after fk_forme_juridique;

DELETE FROM llx_user_rights WHERE fk_id IN (SELECT id FROM llx_rights_def WHERE module = 'webhook' AND perms = 'webhook_target');
DELETE FROM llx_usergroup_rights WHERE fk_id IN (SELECT id FROM llx_rights_def WHERE module = 'webhook' AND perms = 'webhook_target');

DELETE FROM llx_rights_def WHERE module = 'webhook' AND perms = 'webhook_target';

ALTER TABLE llx_prelevement_lignes ADD COLUMN bic   varchar(11);   -- 11 according to ISO 9362
ALTER TABLE llx_prelevement_lignes ADD COLUMN iban	varchar(80);   -- full iban. 34 according to ISO 13616 but we set 80 to allow to store it with encryption information
ALTER TABLE llx_prelevement_lignes ADD COLUMN rum	varchar(32);   -- rum used


ALTER TABLE llx_product_customer_price CHANGE COLUMN localtax1_tx localtax1_tx varchar(20) DEFAULT '0';
ALTER TABLE llx_product_customer_price CHANGE COLUMN localtax2_tx localtax2_tx varchar(20) DEFAULT '0';
ALTER TABLE llx_product_customer_price_log CHANGE COLUMN localtax1_tx localtax1_tx varchar(20) DEFAULT '0';
ALTER TABLE llx_product_customer_price_log CHANGE COLUMN localtax2_tx localtax2_tx varchar(20) DEFAULT '0';

ALTER TABLE llx_subscription DROP INDEX idx_subscription_fk_adherent;
ALTER TABLE llx_subscription DROP INDEX idx_subscription_fk_bank;
ALTER TABLE llx_subscription DROP INDEX idx_subscription_dateadh;
ALTER TABLE llx_subscription ADD INDEX idx_subscription_fk_adherent (fk_adherent);
ALTER TABLE llx_subscription ADD INDEX idx_subscription_fk_bank (fk_bank);
ALTER TABLE llx_subscription ADD INDEX idx_subscription_dateadh (dateadh);

ALTER TABLE llx_bank_import ADD COLUMN fitid varchar(255) NULL after id_account; -- OFX Financial Institution Transaction ID "FITID"

ALTER TABLE llx_element_contact ADD mandatory_signature TINYINT AFTER element_id;

-- default deposit % if payment term needs it on supplier
ALTER TABLE llx_supplier_proposal ADD COLUMN deposit_percent varchar(63) DEFAULT NULL AFTER fk_cond_reglement;
ALTER TABLE llx_commande_fournisseur ADD COLUMN deposit_percent varchar(63) DEFAULT NULL AFTER fk_cond_reglement;

-- end of migration
