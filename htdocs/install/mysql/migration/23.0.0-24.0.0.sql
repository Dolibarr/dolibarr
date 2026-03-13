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

-- V23 forgotten


-- V24 migration

ALTER TABLE llx_actioncomm_reminder MODIFY COLUMN fk_user integer DEFAULT NULL;
ALTER TABLE llx_actioncomm_reminder ADD COLUMN fk_soc integer DEFAULT NULL AFTER fk_user;
ALTER TABLE llx_actioncomm_reminder ADD COLUMN fk_contact integer DEFAULT NULL AFTER fk_soc;
ALTER TABLE llx_actioncomm_reminder ADD INDEX idx_actioncomm_reminder_fk_soc (fk_soc);
ALTER TABLE llx_actioncomm_reminder ADD INDEX idx_actioncomm_reminder_fk_contact (fk_contact);
ALTER TABLE llx_actioncomm_reminder DROP INDEX uk_actioncomm_reminder_unique;
ALTER TABLE llx_actioncomm_reminder ADD UNIQUE INDEX uk_actioncomm_reminder_unique(fk_actioncomm, fk_user, fk_soc, fk_contact, typeremind, offsetvalue, offsetunit);
ALTER TABLE llx_multicurrency_rate ADD COLUMN rate_direct double DEFAULT 0 AFTER rate;

CREATE TABLE llx_accounting_transaction_template (
	rowid			integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity          integer DEFAULT 1 NOT NULL,
	code			varchar(128) NOT NULL,
	label			varchar(255),
	date_creation	datetime NOT NULL,
	tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat	integer NOT NULL,
	fk_user_modif	integer,
	import_key		varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_accounting_transaction_template ADD INDEX idx_accounting_transaction_template_rowid (rowid);
ALTER TABLE llx_accounting_transaction_template ADD INDEX idx_accounting_transaction_template_code (code);

ALTER TABLE llx_accounting_transaction_template ADD UNIQUE INDEX uk_accounting_transaction_template_code (code, entity);

CREATE TABLE llx_accounting_transaction_template_det (
	rowid					integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_transaction_template	integer NOT NULL,
	general_account			varchar(32) NOT NULL,
	general_label			varchar(255) NOT NULL,
	subledger_account		varchar(32),
	subledger_label			varchar(255),
	operation_label			varchar(255),
	debit					double(24,8),
	credit					double(24,8)
) ENGINE=innodb;

ALTER TABLE llx_accounting_transaction_template_det ADD INDEX idx_accounting_transaction_template_det_rowid (rowid);
ALTER TABLE llx_accounting_transaction_template_det ADD CONSTRAINT llx_accounting_transaction_template_det_fk_transaction_template FOREIGN KEY (fk_transaction_template) REFERENCES llx_accounting_transaction_template(rowid);

create table llx_categorie_mo
(
  fk_categorie integer NOT NULL,
  fk_mo        integer NOT NULL,
  import_key   varchar(14)
)ENGINE=innodb;

--noqa:disable=PRS
ALTER TABLE llx_categorie_mo ADD PRIMARY KEY pk_categorie_mo (fk_categorie, fk_mo);
--noqa:enable=PRS
ALTER TABLE llx_categorie_mo ADD INDEX idx_categorie_mo_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_mo ADD INDEX idx_categorie_mo_fk_mo (fk_mo);

ALTER TABLE llx_categorie_mo ADD CONSTRAINT fk_categorie_mo_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_mo ADD CONSTRAINT fk_categorie_mo_fk_mo_rowid FOREIGN KEY (fk_mo) REFERENCES llx_mrp_mo (rowid);


ALTER TABLE llx_facture ADD COLUMN fk_thirdparty_rib_id integer NULL;
ALTER TABLE llx_facture_fourn ADD COLUMN fk_thirdparty_rib_id integer NULL;

ALTER TABLE llx_facture_fourn ADD COLUMN payment_reference varchar(25);
ALTER TABLE llx_facture_fourn ADD COLUMN dispute_status	integer DEFAULT 0;

ALTER TABLE llx_facture_rec ADD COLUMN fk_email_template integer DEFAULT NULL;

ALTER TABLE llx_holiday_users ADD COLUMN import_key varchar(14);

ALTER TABLE llx_societe ADD COLUMN euid varchar (64);

CREATE TABLE llx_ai_request_log
(
  rowid             	  		integer AUTO_INCREMENT PRIMARY KEY,
  entity				        integer DEFAULT 1 NOT NULL,
  date_request			    	datetime,
  fk_user     			    	integer NOT NULL,
  query_text        	  		text,
  tool_name     		    	varchar(255),
  provider   			      	varchar(50),
  execution_time    	  		float,
  confidence        	  		float,
  status            	  		varchar(50),
  error_msg         	  		text,
  raw_request_payload   		MEDIUMTEXT,
  raw_response_payload			MEDIUMTEXT
)ENGINE=innodb;

ALTER TABLE llx_prelevement_bons ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_ai_request_log ADD INDEX idx_ai_request_log_entity (entity);
ALTER TABLE llx_ai_request_log ADD INDEX idx_ai_request_log_date (date_request);
ALTER TABLE llx_ai_request_log ADD INDEX idx_ai_request_log_user (fk_user);
ALTER TABLE llx_ai_request_log ADD INDEX idx_ai_request_log_status (status);

-- Add parent group support for usergroup inheritance
ALTER TABLE llx_usergroup ADD COLUMN fk_parent integer DEFAULT NULL AFTER entity;
ALTER TABLE llx_usergroup ADD INDEX idx_usergroup_fk_parent (fk_parent);
ALTER TABLE llx_usergroup ADD CONSTRAINT fk_usergroup_parent FOREIGN KEY (fk_parent) REFERENCES llx_usergroup (rowid);

-- Force change password next time
ALTER TABLE llx_user ADD COLUMN force_pass_change TINYINT DEFAULT 0 AFTER pass_temp;

-- Ticket
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, content, content_lines, joinfiles) VALUES (0, 'ticket', 'ticket_send', '', 0, null, null, '(SendingAdminEmailMessage)', 100, 'isModEnabled("ticket")', 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __TICKET_EMAIL_SUBJECT__', '__TICKET_EMAIL_BODY__<br><ul><li>(Title) : __TICKET_SUBJECT__</li><li>(Type) : __TICKET_TYPE__</li><li>(TicketCategory) : __TICKET_CATEGORY__</li><li>(Severity) : __TICKET_SEVERITY__</li><li>(From) : __TICKET_USER_ASSIGN__</li><li>(Company) : __THIRDPARTY_NAME__</li></ul><p>(Message) : <br><br>__TICKET_MESSAGE__ </p><br><p><a href="__TICKET_URL__">(SeeThisTicketIntomanagementInterface)</a></p>', null, 0);

ALTER TABLE llx_adherent_type ADD COLUMN minimumamount    double(24,8) DEFAULT NULL;
ALTER TABLE llx_adherent_type ADD COLUMN amountformuladescription text;

ALTER TABLE llx_blockedlog ADD COLUMN pos_source varchar(32) DEFAULT '';

ALTER TABLE llx_website_page ADD COLUMN keep_history integer DEFAULT 5;
ALTER TABLE llx_website_page ADD COLUMN metarobots varchar(128) after keywords;


CREATE TABLE llx_accounting_balance_snapshot (
	rowid              integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
	entity             integer DEFAULT 1 NOT NULL,
	fk_fiscalyear      integer NOT NULL,
	account_number     varchar(32) NOT NULL,
	account_label      varchar(255) NOT NULL,
	subledger_account  varchar(32),
	subledger_label    varchar(255),
	debit              double(24,8) NOT NULL default 0,
	credit             double(24,8) NOT NULL default 0,
	date_snapshot      datetime,
	tms                timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;

ALTER TABLE llx_accounting_balance_snapshot ADD UNIQUE INDEX uk_accounting_balance_snapshot(entity, fk_fiscalyear, account_number, subledger_account);

ALTER TABLE llx_accounting_balance_snapshot ADD INDEX idx_accounting_balance_snapshot_account (entity, fk_fiscalyear, account_number, debit, credit);
ALTER TABLE llx_accounting_balance_snapshot ADD INDEX idx_accounting_balance_snapshot_subaccount (entity, fk_fiscalyear, subledger_account, debit, credit);

UPDATE llx_rights_def SET perms = 'manage_advance' WHERE module = 'ticket' AND perms = 'manage';

-- Switch all crabe templates into sponge
UPDATE llx_facture SET model_pdf = 'sponge' WHERE model_pdf = 'crabe';
UPDATE llx_facture_rec SET modelpdf = 'sponge' WHERE modelpdf = 'crabe';
UPDATE llx_const SET value = 'sponge' WHERE value = 'crabe' AND name ='FACTURE_ADDON_PDF';
UPDATE llx_document_model as dm SET nom = 'sponge' WHERE nom = 'crabe' AND type ='invoice' AND NOT EXISTS (SELECT nom FROM llx_document_model AS dm2 WHERE nom = 'sponge' AND type = 'invoice' and dm2.entity = dm.entity);

ALTER TABLE llx_societe_remise_except ADD COLUMN amount_localtax1 double(24,8) DEFAULT 0 NOT NULL AFTER amount_tva;
ALTER TABLE llx_societe_remise_except ADD COLUMN amount_localtax2 double(24,8) DEFAULT 0 NOT NULL AFTER amount_localtax1;
ALTER TABLE llx_societe_remise_except ADD COLUMN localtax1_tx double(7,4)  DEFAULT 0 NOT NULL AFTER tva_tx;
ALTER TABLE llx_societe_remise_except ADD COLUMN localtax1_type varchar(10)  NULL AFTER localtax1_tx;
ALTER TABLE llx_societe_remise_except ADD COLUMN localtax2_tx double(7,4)  DEFAULT 0 NOT NULL AFTER localtax1_type;
ALTER TABLE llx_societe_remise_except ADD COLUMN localtax2_type varchar(10)  NULL AFTER localtax2_tx;

-- end of migration
