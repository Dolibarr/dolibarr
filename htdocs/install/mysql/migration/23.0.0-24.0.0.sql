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


--noqa:disable=LT09
--noqa:disable=RF03


-- Vxx forgotten

ALTER TABLE llx_c_email_templates ADD COLUMN content_lines text;


-- V23 forgotten

ALTER TABLE llx_categorie_project_task DROP FOREIGN KEY fk_categorie_project_task_rowid;
-- VMYSQL4.1 DROP INDEX idx_categorie_project_fk_task ON llx_categorie_project_task;
-- VPGSQL8.2 DROP INDEX idx_categorie_project_fk_task;
ALTER TABLE llx_categorie_project_task ADD INDEX idx_categorie_project_fk_task (fk_project_task);
ALTER TABLE llx_categorie_project_task ADD CONSTRAINT fk_categorie_project_task_rowid FOREIGN KEY (fk_project_task) REFERENCES llx_projet_task (rowid);

-- V24 migration
ALTER TABLE llx_expensereport_det ADD COLUMN tcheck_file	integer DEFAULT NULL after fk_ecm_files;
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
ALTER TABLE llx_blockedlog ADD COLUMN signature_backward varchar(100) DEFAULT '';

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
UPDATE llx_document_model SET nom = 'sponge' WHERE nom = 'crabe' AND type = 'invoice' AND NOT EXISTS (SELECT subquery.nom FROM (SELECT nom, entity FROM llx_document_model WHERE nom = 'sponge' AND type = 'invoice') as subquery WHERE subquery.entity = entity);
DELETE FROM llx_document_model WHERE nom = 'crabe' AND type = 'invoice';

ALTER TABLE llx_salary ADD COLUMN model_pdf varchar(255) DEFAULT NULL;

ALTER TABLE llx_extrafields ADD COLUMN showintooltip integer DEFAULT 0;

ALTER TABLE llx_societe_remise_except ADD COLUMN amount_localtax1 double(24,8) DEFAULT 0 NOT NULL AFTER amount_tva;
ALTER TABLE llx_societe_remise_except ADD COLUMN amount_localtax2 double(24,8) DEFAULT 0 NOT NULL AFTER amount_localtax1;
ALTER TABLE llx_societe_remise_except ADD COLUMN localtax1_tx double(7,4)  DEFAULT 0 NOT NULL AFTER tva_tx;
ALTER TABLE llx_societe_remise_except ADD COLUMN localtax1_type varchar(10)  NULL AFTER localtax1_tx;
ALTER TABLE llx_societe_remise_except ADD COLUMN localtax2_tx double(7,4)  DEFAULT 0 NOT NULL AFTER localtax1_type;
ALTER TABLE llx_societe_remise_except ADD COLUMN localtax2_type varchar(10)  NULL AFTER localtax2_tx;

INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, content, content_lines, joinfiles) VALUES (0, 'holiday', 'holiday', '', 0, null, null, '(HolidayHrInformationsPreviousMonth)', 100,'isModEnabled("holiday")', 1, '__(HolidayHrInformationsPreviousMonthTopic)__', '__(Hello)__<br><br>__(HolidayHrInformationsPreviousMonthContent)__:<br>__HOLIDAY_ARRAY_PER_EMPLOYEE_FOR_PERIOD__<br><br>__SENDEREMAIL_SIGNATURE__', null, 0);

INSERT IGNORE INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('STOCKTRANSFER_CREATE','Stock transfer created','Executed when a stock transfer is created','stocktransfer',670);
INSERT IGNORE INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('STOCKTRANSFER_MODIFY','Stock transfer modified','Executed when a stock transfer is modified','stocktransfer',671);
INSERT IGNORE INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('STOCKTRANSFER_VALIDATE','Stock transfer validated','Executed when a stock transfer is validated','stocktransfer',672);
INSERT IGNORE INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('STOCKTRANSFER_UNVALIDATE','Stock transfer back to draft','Executed when a stock transfer is set back to draft','stocktransfer',673);
INSERT IGNORE INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('STOCKTRANSFER_CLOSE','Stock transfer closed','Executed when a stock transfer is closed after destination stock increment','stocktransfer',676);

ALTER TABLE llx_c_ticket_category ADD COLUMN fk_ticket_type integer NULL;

ALTER TABLE llx_prelevement_bons ADD COLUMN fk_user_modif integer;

ALTER TABLE llx_prelevement_lignes ADD COLUMN fk_prelevement_demande integer DEFAULT 0;


UPDATE llx_cronjob set test = 'isModEnabled("agenda")' WHERE test = '$conf->agenda->enabled';
UPDATE llx_cronjob set test = 'isModEnabled("invoice")' WHERE test = '$conf->facture->enabled';
UPDATE llx_cronjob set test = 'isModEnabled("holiday")' WHERE test = '$conf->holiday->enabled';
UPDATE llx_cronjob set test = 'isModEnabled("member")' WHERE test = '$conf->adherent->enabled';
UPDATE llx_cronjob set test = 'isModEnabled("partnership")' WHERE test = '$conf->partnership->enabled';
UPDATE llx_cronjob set test = 'isModEnabled("emailcollector")' WHERE test = '$conf->emailcollector->enabled';
UPDATE llx_cronjob set test = 'isModEnabled("project")' WHERE test = '$conf->projet->enabled';
-- Work only with very recent version of mysql UPDATE llx_cronjob SET test = REGEXP_REPLACE(test, '\\$conf->([^ ]+)->enabled', 'isModEnabled("$1")');
UPDATE llx_cronjob set test = 'isModEnabled("sellyoursaas")' WHERE test = '$conf->sellyoursaas->enabled';
UPDATE llx_cronjob set test = 'isModEnabled("scaninvoices")' WHERE test = '$conf->scaninvoices->enabled';

ALTER TABLE llx_categorie_project_task DROP FOREIGN KEY fk_categorie_project_task_rowid;
ALTER TABLE llx_categorie_project_task ADD CONSTRAINT fk_categorie_project_task_rowid FOREIGN KEY (fk_project_task) REFERENCES llx_projet_task (rowid);


create table llx_product_lang_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          -- import key
) ENGINE=innodb;
ALTER TABLE llx_product_lang_extrafields ADD INDEX idx_product_lang_fk_object(fk_object);

CREATE TABLE llx_categorie_lang_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;
ALTER TABLE llx_categorie_lang_extrafields ADD INDEX idx_categorie_lang_fk_object(fk_object);

ALTER TABLE llx_adherent_type ADD COLUMN minimumamount double(24,8) DEFAULT NULL AFTER caneditamount;
ALTER TABLE llx_adherent_type ADD COLUMN amountformuladescription text AFTER minimumamount;

-- Add phone_code column to country table
ALTER TABLE llx_c_country ADD COLUMN phone_code integer;

-- Populate phone codes (ITU-T country calling codes)
UPDATE llx_c_country SET phone_code = 33 WHERE code = 'FR';
UPDATE llx_c_country SET phone_code = 32 WHERE code = 'BE';
UPDATE llx_c_country SET phone_code = 39 WHERE code = 'IT';
UPDATE llx_c_country SET phone_code = 34 WHERE code = 'ES';
UPDATE llx_c_country SET phone_code = 49 WHERE code = 'DE';
UPDATE llx_c_country SET phone_code = 41 WHERE code = 'CH';
UPDATE llx_c_country SET phone_code = 44 WHERE code = 'GB';
UPDATE llx_c_country SET phone_code = 353 WHERE code = 'IE';
UPDATE llx_c_country SET phone_code = 86 WHERE code = 'CN';
UPDATE llx_c_country SET phone_code = 216 WHERE code = 'TN';
UPDATE llx_c_country SET phone_code = 1 WHERE code = 'US';
UPDATE llx_c_country SET phone_code = 212 WHERE code = 'MA';
UPDATE llx_c_country SET phone_code = 213 WHERE code = 'DZ';
UPDATE llx_c_country SET phone_code = 1 WHERE code = 'CA';
UPDATE llx_c_country SET phone_code = 228 WHERE code = 'TG';
UPDATE llx_c_country SET phone_code = 241 WHERE code = 'GA';
UPDATE llx_c_country SET phone_code = 31 WHERE code = 'NL';
UPDATE llx_c_country SET phone_code = 36 WHERE code = 'HU';
UPDATE llx_c_country SET phone_code = 7 WHERE code = 'RU';
UPDATE llx_c_country SET phone_code = 46 WHERE code = 'SE';
UPDATE llx_c_country SET phone_code = 225 WHERE code = 'CI';
UPDATE llx_c_country SET phone_code = 221 WHERE code = 'SN';
UPDATE llx_c_country SET phone_code = 54 WHERE code = 'AR';
UPDATE llx_c_country SET phone_code = 237 WHERE code = 'CM';
UPDATE llx_c_country SET phone_code = 351 WHERE code = 'PT';
UPDATE llx_c_country SET phone_code = 966 WHERE code = 'SA';
UPDATE llx_c_country SET phone_code = 377 WHERE code = 'MC';
UPDATE llx_c_country SET phone_code = 61 WHERE code = 'AU';
UPDATE llx_c_country SET phone_code = 65 WHERE code = 'SG';
UPDATE llx_c_country SET phone_code = 93 WHERE code = 'AF';
UPDATE llx_c_country SET phone_code = 358 WHERE code = 'AX';
UPDATE llx_c_country SET phone_code = 355 WHERE code = 'AL';
UPDATE llx_c_country SET phone_code = 1684 WHERE code = 'AS';
UPDATE llx_c_country SET phone_code = 376 WHERE code = 'AD';
UPDATE llx_c_country SET phone_code = 244 WHERE code = 'AO';
UPDATE llx_c_country SET phone_code = 1264 WHERE code = 'AI';
UPDATE llx_c_country SET phone_code = 1268 WHERE code = 'AG';
UPDATE llx_c_country SET phone_code = 374 WHERE code = 'AM';
UPDATE llx_c_country SET phone_code = 297 WHERE code = 'AW';
UPDATE llx_c_country SET phone_code = 43 WHERE code = 'AT';
UPDATE llx_c_country SET phone_code = 994 WHERE code = 'AZ';
UPDATE llx_c_country SET phone_code = 1242 WHERE code = 'BS';
UPDATE llx_c_country SET phone_code = 973 WHERE code = 'BH';
UPDATE llx_c_country SET phone_code = 880 WHERE code = 'BD';
UPDATE llx_c_country SET phone_code = 1246 WHERE code = 'BB';
UPDATE llx_c_country SET phone_code = 375 WHERE code = 'BY';
UPDATE llx_c_country SET phone_code = 501 WHERE code = 'BZ';
UPDATE llx_c_country SET phone_code = 229 WHERE code = 'BJ';
UPDATE llx_c_country SET phone_code = 1441 WHERE code = 'BM';
UPDATE llx_c_country SET phone_code = 975 WHERE code = 'BT';
UPDATE llx_c_country SET phone_code = 591 WHERE code = 'BO';
UPDATE llx_c_country SET phone_code = 387 WHERE code = 'BA';
UPDATE llx_c_country SET phone_code = 267 WHERE code = 'BW';
UPDATE llx_c_country SET phone_code = 55 WHERE code = 'BR';
UPDATE llx_c_country SET phone_code = 246 WHERE code = 'IO';
UPDATE llx_c_country SET phone_code = 673 WHERE code = 'BN';
UPDATE llx_c_country SET phone_code = 359 WHERE code = 'BG';
UPDATE llx_c_country SET phone_code = 226 WHERE code = 'BF';
UPDATE llx_c_country SET phone_code = 257 WHERE code = 'BI';
UPDATE llx_c_country SET phone_code = 855 WHERE code = 'KH';
UPDATE llx_c_country SET phone_code = 238 WHERE code = 'CV';
UPDATE llx_c_country SET phone_code = 1345 WHERE code = 'KY';
UPDATE llx_c_country SET phone_code = 236 WHERE code = 'CF';
UPDATE llx_c_country SET phone_code = 235 WHERE code = 'TD';
UPDATE llx_c_country SET phone_code = 56 WHERE code = 'CL';
UPDATE llx_c_country SET phone_code = 61 WHERE code = 'CX';
UPDATE llx_c_country SET phone_code = 61 WHERE code = 'CC';
UPDATE llx_c_country SET phone_code = 57 WHERE code = 'CO';
UPDATE llx_c_country SET phone_code = 269 WHERE code = 'KM';
UPDATE llx_c_country SET phone_code = 242 WHERE code = 'CG';
UPDATE llx_c_country SET phone_code = 243 WHERE code = 'CD';
UPDATE llx_c_country SET phone_code = 682 WHERE code = 'CK';
UPDATE llx_c_country SET phone_code = 506 WHERE code = 'CR';
UPDATE llx_c_country SET phone_code = 385 WHERE code = 'HR';
UPDATE llx_c_country SET phone_code = 53 WHERE code = 'CU';
UPDATE llx_c_country SET phone_code = 357 WHERE code = 'CY';
UPDATE llx_c_country SET phone_code = 420 WHERE code = 'CZ';
UPDATE llx_c_country SET phone_code = 45 WHERE code = 'DK';
UPDATE llx_c_country SET phone_code = 253 WHERE code = 'DJ';
UPDATE llx_c_country SET phone_code = 1767 WHERE code = 'DM';
UPDATE llx_c_country SET phone_code = 1809 WHERE code = 'DO';
UPDATE llx_c_country SET phone_code = 593 WHERE code = 'EC';
UPDATE llx_c_country SET phone_code = 20 WHERE code = 'EG';
UPDATE llx_c_country SET phone_code = 503 WHERE code = 'SV';
UPDATE llx_c_country SET phone_code = 240 WHERE code = 'GQ';
UPDATE llx_c_country SET phone_code = 291 WHERE code = 'ER';
UPDATE llx_c_country SET phone_code = 372 WHERE code = 'EE';
UPDATE llx_c_country SET phone_code = 251 WHERE code = 'ET';
UPDATE llx_c_country SET phone_code = 500 WHERE code = 'FK';
UPDATE llx_c_country SET phone_code = 298 WHERE code = 'FO';
UPDATE llx_c_country SET phone_code = 679 WHERE code = 'FJ';
UPDATE llx_c_country SET phone_code = 358 WHERE code = 'FI';
UPDATE llx_c_country SET phone_code = 594 WHERE code = 'GF';
UPDATE llx_c_country SET phone_code = 689 WHERE code = 'PF';
UPDATE llx_c_country SET phone_code = 220 WHERE code = 'GM';
UPDATE llx_c_country SET phone_code = 995 WHERE code = 'GE';
UPDATE llx_c_country SET phone_code = 233 WHERE code = 'GH';
UPDATE llx_c_country SET phone_code = 350 WHERE code = 'GI';
UPDATE llx_c_country SET phone_code = 30 WHERE code = 'GR';
UPDATE llx_c_country SET phone_code = 299 WHERE code = 'GL';
UPDATE llx_c_country SET phone_code = 1473 WHERE code = 'GD';
UPDATE llx_c_country SET phone_code = 1671 WHERE code = 'GU';
UPDATE llx_c_country SET phone_code = 502 WHERE code = 'GT';
UPDATE llx_c_country SET phone_code = 224 WHERE code = 'GN';
UPDATE llx_c_country SET phone_code = 245 WHERE code = 'GW';
UPDATE llx_c_country SET phone_code = 509 WHERE code = 'HT';
UPDATE llx_c_country SET phone_code = 379 WHERE code = 'VA';
UPDATE llx_c_country SET phone_code = 504 WHERE code = 'HN';
UPDATE llx_c_country SET phone_code = 852 WHERE code = 'HK';
UPDATE llx_c_country SET phone_code = 354 WHERE code = 'IS';
UPDATE llx_c_country SET phone_code = 91 WHERE code = 'IN';
UPDATE llx_c_country SET phone_code = 62 WHERE code = 'ID';
UPDATE llx_c_country SET phone_code = 98 WHERE code = 'IR';
UPDATE llx_c_country SET phone_code = 964 WHERE code = 'IQ';
UPDATE llx_c_country SET phone_code = 972 WHERE code = 'IL';
UPDATE llx_c_country SET phone_code = 1876 WHERE code = 'JM';
UPDATE llx_c_country SET phone_code = 81 WHERE code = 'JP';
UPDATE llx_c_country SET phone_code = 962 WHERE code = 'JO';
UPDATE llx_c_country SET phone_code = 7 WHERE code = 'KZ';
UPDATE llx_c_country SET phone_code = 254 WHERE code = 'KE';
UPDATE llx_c_country SET phone_code = 686 WHERE code = 'KI';
UPDATE llx_c_country SET phone_code = 850 WHERE code = 'KP';
UPDATE llx_c_country SET phone_code = 82 WHERE code = 'KR';
UPDATE llx_c_country SET phone_code = 965 WHERE code = 'KW';
UPDATE llx_c_country SET phone_code = 996 WHERE code = 'KG';
UPDATE llx_c_country SET phone_code = 856 WHERE code = 'LA';
UPDATE llx_c_country SET phone_code = 371 WHERE code = 'LV';
UPDATE llx_c_country SET phone_code = 961 WHERE code = 'LB';
UPDATE llx_c_country SET phone_code = 266 WHERE code = 'LS';
UPDATE llx_c_country SET phone_code = 231 WHERE code = 'LR';
UPDATE llx_c_country SET phone_code = 218 WHERE code = 'LY';
UPDATE llx_c_country SET phone_code = 423 WHERE code = 'LI';
UPDATE llx_c_country SET phone_code = 370 WHERE code = 'LT';
UPDATE llx_c_country SET phone_code = 352 WHERE code = 'LU';
UPDATE llx_c_country SET phone_code = 853 WHERE code = 'MO';
UPDATE llx_c_country SET phone_code = 389 WHERE code = 'MK';
UPDATE llx_c_country SET phone_code = 261 WHERE code = 'MG';
UPDATE llx_c_country SET phone_code = 265 WHERE code = 'GH';
UPDATE llx_c_country SET phone_code = 350 WHERE code = 'GI';
UPDATE llx_c_country SET phone_code = 30 WHERE code = 'MW';
UPDATE llx_c_country SET phone_code = 60 WHERE code = 'MY';
UPDATE llx_c_country SET phone_code = 960 WHERE code = 'MV';
UPDATE llx_c_country SET phone_code = 223 WHERE code = 'ML';
UPDATE llx_c_country SET phone_code = 356 WHERE code = 'MT';
UPDATE llx_c_country SET phone_code = 692 WHERE code = 'MH';
UPDATE llx_c_country SET phone_code = 222 WHERE code = 'MR';
UPDATE llx_c_country SET phone_code = 230 WHERE code = 'MU';
UPDATE llx_c_country SET phone_code = 262 WHERE code = 'YT';
UPDATE llx_c_country SET phone_code = 52 WHERE code = 'MX';
UPDATE llx_c_country SET phone_code = 691 WHERE code = 'FM';
UPDATE llx_c_country SET phone_code = 373 WHERE code = 'MD';
UPDATE llx_c_country SET phone_code = 976 WHERE code = 'MN';
UPDATE llx_c_country SET phone_code = 1664 WHERE code = 'MS';
UPDATE llx_c_country SET phone_code = 258 WHERE code = 'MZ';
UPDATE llx_c_country SET phone_code = 95 WHERE code = 'MM';
UPDATE llx_c_country SET phone_code = 264 WHERE code = 'NA';
UPDATE llx_c_country SET phone_code = 674 WHERE code = 'NR';
UPDATE llx_c_country SET phone_code = 977 WHERE code = 'NP';
UPDATE llx_c_country SET phone_code = 599 WHERE code = 'CW';
UPDATE llx_c_country SET phone_code = 1721 WHERE code = 'SX';
UPDATE llx_c_country SET phone_code = 687 WHERE code = 'NC';
UPDATE llx_c_country SET phone_code = 64 WHERE code = 'NZ';
UPDATE llx_c_country SET phone_code = 505 WHERE code = 'NI';
UPDATE llx_c_country SET phone_code = 227 WHERE code = 'NE';
UPDATE llx_c_country SET phone_code = 234 WHERE code = 'NG';
UPDATE llx_c_country SET phone_code = 683 WHERE code = 'NU';
UPDATE llx_c_country SET phone_code = 672 WHERE code = 'NF';
UPDATE llx_c_country SET phone_code = 1670 WHERE code = 'MP';
UPDATE llx_c_country SET phone_code = 47 WHERE code = 'NO';
UPDATE llx_c_country SET phone_code = 968 WHERE code = 'OM';
UPDATE llx_c_country SET phone_code = 92 WHERE code = 'PK';
UPDATE llx_c_country SET phone_code = 680 WHERE code = 'PW';
UPDATE llx_c_country SET phone_code = 970 WHERE code = 'PS';
UPDATE llx_c_country SET phone_code = 507 WHERE code = 'PA';
UPDATE llx_c_country SET phone_code = 675 WHERE code = 'PG';
UPDATE llx_c_country SET phone_code = 595 WHERE code = 'PY';
UPDATE llx_c_country SET phone_code = 51 WHERE code = 'PE';
UPDATE llx_c_country SET phone_code = 63 WHERE code = 'PH';
UPDATE llx_c_country SET phone_code = 48 WHERE code = 'PL';
UPDATE llx_c_country SET phone_code = 1787 WHERE code = 'PR';
UPDATE llx_c_country SET phone_code = 974 WHERE code = 'QA';
UPDATE llx_c_country SET phone_code = 40 WHERE code = 'RO';
UPDATE llx_c_country SET phone_code = 250 WHERE code = 'RW';
UPDATE llx_c_country SET phone_code = 290 WHERE code = 'SH';
UPDATE llx_c_country SET phone_code = 1869 WHERE code = 'KN';
UPDATE llx_c_country SET phone_code = 1758 WHERE code = 'LC';
UPDATE llx_c_country SET phone_code = 508 WHERE code = 'PM';
UPDATE llx_c_country SET phone_code = 1784 WHERE code = 'VC';
UPDATE llx_c_country SET phone_code = 685 WHERE code = 'WS';
UPDATE llx_c_country SET phone_code = 378 WHERE code = 'SM';
UPDATE llx_c_country SET phone_code = 239 WHERE code = 'ST';
UPDATE llx_c_country SET phone_code = 381 WHERE code = 'RS';
UPDATE llx_c_country SET phone_code = 248 WHERE code = 'SC';
UPDATE llx_c_country SET phone_code = 232 WHERE code = 'SL';
UPDATE llx_c_country SET phone_code = 421 WHERE code = 'SK';
UPDATE llx_c_country SET phone_code = 386 WHERE code = 'SI';
UPDATE llx_c_country SET phone_code = 677 WHERE code = 'SB';
UPDATE llx_c_country SET phone_code = 252 WHERE code = 'SO';
UPDATE llx_c_country SET phone_code = 27 WHERE code = 'ZA';
UPDATE llx_c_country SET phone_code = 94 WHERE code = 'LK';
UPDATE llx_c_country SET phone_code = 249 WHERE code = 'SD';
UPDATE llx_c_country SET phone_code = 597 WHERE code = 'SR';
UPDATE llx_c_country SET phone_code = 47 WHERE code = 'SJ';
UPDATE llx_c_country SET phone_code = 268 WHERE code = 'SZ';
UPDATE llx_c_country SET phone_code = 963 WHERE code = 'SY';
UPDATE llx_c_country SET phone_code = 886 WHERE code = 'TW';
UPDATE llx_c_country SET phone_code = 992 WHERE code = 'TJ';
UPDATE llx_c_country SET phone_code = 255 WHERE code = 'TZ';
UPDATE llx_c_country SET phone_code = 66 WHERE code = 'TH';
UPDATE llx_c_country SET phone_code = 670 WHERE code = 'TL';
UPDATE llx_c_country SET phone_code = 690 WHERE code = 'TK';
UPDATE llx_c_country SET phone_code = 676 WHERE code = 'TO';
UPDATE llx_c_country SET phone_code = 1868 WHERE code = 'TT';
UPDATE llx_c_country SET phone_code = 90 WHERE code = 'TR';
UPDATE llx_c_country SET phone_code = 993 WHERE code = 'TM';
UPDATE llx_c_country SET phone_code = 1649 WHERE code = 'TC';
UPDATE llx_c_country SET phone_code = 688 WHERE code = 'TV';
UPDATE llx_c_country SET phone_code = 256 WHERE code = 'UG';
UPDATE llx_c_country SET phone_code = 380 WHERE code = 'UA';
UPDATE llx_c_country SET phone_code = 971 WHERE code = 'AE';
UPDATE llx_c_country SET phone_code = 598 WHERE code = 'UY';
UPDATE llx_c_country SET phone_code = 998 WHERE code = 'UZ';
UPDATE llx_c_country SET phone_code = 678 WHERE code = 'VU';
UPDATE llx_c_country SET phone_code = 58 WHERE code = 'VE';
UPDATE llx_c_country SET phone_code = 84 WHERE code = 'VN';
UPDATE llx_c_country SET phone_code = 1284 WHERE code = 'VG';
UPDATE llx_c_country SET phone_code = 1340 WHERE code = 'VI';
UPDATE llx_c_country SET phone_code = 681 WHERE code = 'WF';
UPDATE llx_c_country SET phone_code = 967 WHERE code = 'YE';
UPDATE llx_c_country SET phone_code = 260 WHERE code = 'ZM';
UPDATE llx_c_country SET phone_code = 263 WHERE code = 'ZW';
UPDATE llx_c_country SET phone_code = 44 WHERE code = 'GG';
UPDATE llx_c_country SET phone_code = 44 WHERE code = 'IM';
UPDATE llx_c_country SET phone_code = 44 WHERE code = 'JE';
UPDATE llx_c_country SET phone_code = 382 WHERE code = 'ME';
UPDATE llx_c_country SET phone_code = 590 WHERE code = 'BL';
UPDATE llx_c_country SET phone_code = 590 WHERE code = 'MF';
UPDATE llx_c_country SET phone_code = 383 WHERE code = 'XK';

-- Add trunk_prefix column to llx_c_country
ALTER TABLE llx_c_country ADD COLUMN trunk_prefix varchar(5);

-- Populate trunk_prefix for countries with trunk prefix '0'
UPDATE llx_c_country SET trunk_prefix = '0' WHERE code IN ('FR','BE','DE','CH','GB','IE','NL','SE','AT','GR','FI','HR','BA','BG','RO','RS','ME','MK','AL','SK','SI','LT','MD','UA','GE','XK','TR','CN','IN','ID','JP','KR','TH','VN','MY','PH','BD','PK','AF','IR','IQ','JO','LB','SA','AE','IL','SY','YE','AM','AZ','KH','LA','MM','NP','LK','TW','MN','KG','UZ','PS','KP','MA','DZ','EG','ZA','NG','GH','KE','ET','TZ','UG','SD','LY','NA','SL','ZM','ZW','ER','CD','AO','EH','AU','NZ','AR','BR','CU','VE','PE','EC','BO','PY','SR','GF','YT','PM','BL','MF','GG','JE','IM','AX');
-- Populate trunk_prefix for post-Soviet countries with trunk prefix '8'
UPDATE llx_c_country SET trunk_prefix = '8' WHERE code IN ('RU','BY','KZ','TJ','TM');
-- Populate trunk_prefix for Hungary with trunk prefix '06'
UPDATE llx_c_country SET trunk_prefix = '06' WHERE code = 'HU';
-- Populate trunk_prefix for NANP countries with trunk prefix '1'
UPDATE llx_c_country SET trunk_prefix = '1' WHERE code IN ('US','CA','PR','VI','GU','AS','MP','JM','BS','BB','AG','DM','GD','KN','LC','VC','TT','DO','AI','BM','VG','KY','MS','TC','SX','MH','FM');

-- Strip formatting sugar from existing phone numbers in llx_socpeople
UPDATE llx_socpeople SET phone = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '.', ''), '(', ''), ')', '') WHERE phone IS NOT NULL AND phone != '';
UPDATE llx_socpeople SET phone_perso = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone_perso, ' ', ''), '-', ''), '.', ''), '(', ''), ')', '') WHERE phone_perso IS NOT NULL AND phone_perso != '';
UPDATE llx_socpeople SET phone_mobile = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone_mobile, ' ', ''), '-', ''), '.', ''), '(', ''), ')', '') WHERE phone_mobile IS NOT NULL AND phone_mobile != '';
UPDATE llx_socpeople SET fax = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(fax, ' ', ''), '-', ''), '.', ''), '(', ''), ')', '') WHERE fax IS NOT NULL AND fax != '';


--ALTER TABLE llx_facture ADD COLUMN retained_warranty_amount double(24,8) DEFAULT NULL AFTER retained_warranty;

-- Add personal_data flag on extrafields for GDPR/nLPD/LGPD compliance
ALTER TABLE llx_extrafields ADD COLUMN personal_data tinyint DEFAULT 0 AFTER csslist;


-- Add missing index in llx_product_warehouse_properties table
ALTER TABLE llx_product_warehouse_properties ADD INDEX idx_product_warehouse_properties_fk_product (fk_product);
ALTER TABLE llx_product_warehouse_properties ADD INDEX idx_product_warehouse_properties_fk_entrepot (fk_entrepot);

ALTER TABLE llx_product_warehouse_properties ADD CONSTRAINT fk_product_warehouse_properties_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
ALTER TABLE llx_product_warehouse_properties ADD CONSTRAINT fk_product_warehouse_properties_fk_entrepot FOREIGN KEY (fk_entrepot) REFERENCES llx_entrepot (rowid);

ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD COLUMN fk_replacement integer DEFAULT NULL;
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD UNIQUE INDEX uk_eventorganization_confboothattendee_replacement (fk_replacement);

-- Add extrafields on various payment
CREATE TABLE llx_payment_various_extrafields
(
	rowid                     integer AUTO_INCREMENT PRIMARY KEY,
	tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_object                 integer NOT NULL,
	import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_payment_various_extrafields ADD UNIQUE INDEX uk_payment_various_extrafields (fk_object);

ALTER TABLE llx_actioncomm ADD COLUMN max_participants integer DEFAULT NULL AFTER status;
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_max_participants (max_participants);

ALTER TABLE llx_c_tva ADD COLUMN einvoice_vatex	varchar(32);


-- SQL with disabled check must be at end
--noqa:disable=PRS
DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_MENU_BARRETOP';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTANCY_AUXACCOUNT_USE_SEARCH_TO_SELECT')__ WHERE __DECRYPT('name')__ = 'ACCOUNTANCY_COMBO_FOR_AUX';
--noqa:enable=PRS


-- end of migration
