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
-- To drop an index:        -- VMYSQL4.1 DROP INDEX nomindex on llx_table
-- To drop an index:        -- VPGSQL8.2 DROP INDEX nomindex
-- To restrict request to Mysql version x.y minimum use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y minimum use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):    -- VMYSQL4.3 ALTER TABLE llx_table CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
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


-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);



-- Forgot in 7.0

-- VMYSQL4.1 ALTER TABLE llx_product_association ADD COLUMN rowid integer AUTO_INCREMENT PRIMARY KEY;

ALTER TABLE llx_website_page ADD COLUMN fk_user_create integer;
ALTER TABLE llx_website_page ADD COLUMN fk_user_modif integer; 
ALTER TABLE llx_website_page ADD COLUMN type_container varchar(16) NOT NULL DEFAULT 'page';

-- drop very old table (bad name)
DROP TABLE llx_c_accountancy_category;
DROP TABLE llx_c_accountingaccount;

update llx_propal set fk_statut = 1 where fk_statut = -1;

ALTER TABLE llx_inventory ADD COLUMN fk_user_creat integer;
ALTER TABLE llx_inventory ADD COLUMN fk_user_modif integer;
ALTER TABLE llx_inventory ADD COLUMN fk_user_valid integer;
ALTER TABLE llx_inventory ADD COLUMN import_key varchar(14);

-- Missing Chart of accounts in migration 7.0.0
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  1, 'PCG14-DEV', 'The developed accountancy french plan 2014', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  6, 'PCG_SUISSE', 'Switzerland plan', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (140, 'PCN-LUXEMBURG', 'Plan comptable normalisé Luxembourgeois', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 80, 'DK-STD', 'Standardkontoplan fra SKAT', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 10, 'PCT', 'The Tunisia plan', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 12, 'PCG', 'The Moroccan chart of accounts', 1);

INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 67,'PC-MIPYME', 'The PYME accountancy Chile plan', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  7,'ENG-BASE',  'England plan', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 49,'SYSCOHADA-BJ', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 60,'SYSCOHADA-BF', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 24,'SYSCOHADA-CM', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 65,'SYSCOHADA-CF', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 71,'SYSCOHADA-KM', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 72,'SYSCOHADA-CG', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 21,'SYSCOHADA-CI', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 16,'SYSCOHADA-GA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 87,'SYSCOHADA-GQ', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (147,'SYSCOHADA-ML', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (168,'SYSCOHADA-NE', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 73,'SYSCOHADA-CD', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 22,'SYSCOHADA-SN', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 66,'SYSCOHADA-TD', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 15,'SYSCOHADA-TG', 'Plan comptable Ouest-Africain', 1);

-- For 8.0

-- delete old permission no more used
DELETE FROM llx_rights_def WHERE perms = 'main' and module = 'commercial';

delete from llx_rights_def where perms IS NULL;
delete from llx_user_rights where fk_user not IN (select rowid from llx_user);
delete from llx_usergroup_rights where fk_usergroup not in (select rowid from llx_usergroup);
delete from llx_usergroup_rights where fk_id not in (select id from llx_rights_def);

ALTER TABLE llx_inventory ADD COLUMN fk_product integer DEFAULT NULL;
ALTER TABLE llx_inventory MODIFY COLUMN fk_warehouse integer DEFAULT NULL;

ALTER TABLE llx_c_type_fees ADD COLUMN llx_c_type_fees integer DEFAULT 0;

ALTER TABLE llx_product_fournisseur_price DROP COLUMN unitcharges;

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


ALTER TABLE llx_societe_remise_except ADD COLUMN discount_type integer DEFAULT 0 NOT NULL AFTER fk_soc;
ALTER TABLE llx_societe_remise_except ADD INDEX idx_societe_remise_except_discount_type (discount_type);
ALTER TABLE llx_societe ADD COLUMN remise_supplier real DEFAULT 0 AFTER remise_client;
CREATE TABLE llx_societe_remise_supplier
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,			-- multi company id
  fk_soc			integer NOT NULL,
  tms				timestamp,
  datec				datetime,							-- creation date
  fk_user_author	integer,							-- creation user
  remise_supplier	double(6,3)  DEFAULT 0 NOT NULL,	-- discount
  note				text
)ENGINE=innodb;
insert into llx_c_type_container (code,label,module,active) values ('page',     'Page',     'system', 1);
insert into llx_c_type_container (code,label,module,active) values ('banner',   'Banner',   'system', 1);
insert into llx_c_type_container (code,label,module,active) values ('blogpost', 'BlogPost', 'system', 1);
insert into llx_c_type_container (code,label,module,active) values ('other',    'Other',    'system', 1);

-- For supplier product buy price in multicurency
ALTER TABLE llx_product_fournisseur_price CHANGE COLUMN multicurrency_price_ttc multicurrency_unitprice DOUBLE(24,8) NULL DEFAULT NULL;
ALTER TABLE llx_product_fournisseur_price_log CHANGE COLUMN multicurrency_price_ttc multicurrency_unitprice DOUBLE(24,8) NULL DEFAULT NULL;

ALTER TABLE llx_expensereport_det ADD COLUMN docnumber varchar(128) after fk_expensereport;

ALTER TABLE llx_website_page ADD COLUMN aliasalt varchar(255) after pageurl;

-- Add missing keys and primary key
DELETE FROM llx_c_paiement WHERE code = '' or code = '-' or id = 0;
ALTER TABLE llx_c_paiement DROP INDEX uk_c_paiement;
ALTER TABLE llx_c_paiement ADD UNIQUE INDEX uk_c_paiement_code(entity, code);
-- VMYSQL4.3 ALTER TABLE llx_c_paiement CHANGE COLUMN id id INTEGER AUTO_INCREMENT PRIMARY KEY;
-- VPGSQL8.2 CREATE SEQUENCE llx_c_paiement_id_seq OWNED BY llx_c_paiement.id;
-- VPGSQL8.2 ALTER TABLE llx_c_paiement ADD PRIMARY KEY (id);
-- VPGSQL8.2 ALTER TABLE llx_c_paiement ALTER COLUMN id SET DEFAULT nextval('llx_c_paiement_id_seq');
-- VPGSQL8.2 SELECT setval('llx_c_paiement_id_seq', MAX(id)) FROM llx_c_paiement;

-- Add missing keys and primary key
ALTER TABLE llx_c_payment_term DROP INDEX uk_c_payment_term;
ALTER TABLE llx_c_payment_term ADD UNIQUE INDEX uk_c_payment_term_code(entity, code);
-- VMYSQL4.3 ALTER TABLE llx_c_payment_term CHANGE COLUMN rowid rowid INTEGER AUTO_INCREMENT PRIMARY KEY;
-- VPGSQL8.2 CREATE SEQUENCE llx_c_payment_term_rowid_seq OWNED BY llx_c_payment_term.rowid;
-- VPGSQL8.2 ALTER TABLE llx_c_payment_term ADD PRIMARY KEY (rowid);
-- VPGSQL8.2 ALTER TABLE llx_c_payment_term ALTER COLUMN rowid SET DEFAULT nextval('llx_c_payment_term_rowid_seq');
-- VPGSQL8.2 SELECT setval('llx_c_payment_term_rowid_seq', MAX(rowid)) FROM llx_c_payment_term;

ALTER TABLE llx_oauth_token ADD COLUMN tokenstring text;

-- Add field for payment modes
ALTER TABLE llx_societe_rib ADD COLUMN type varchar(32) DEFAULT 'ban' after rowid;
ALTER TABLE llx_societe_rib ADD COLUMN last_four varchar(4);
ALTER TABLE llx_societe_rib ADD COLUMN card_type varchar(255);
ALTER TABLE llx_societe_rib ADD COLUMN cvn varchar(255);										
ALTER TABLE llx_societe_rib ADD COLUMN exp_date_month INTEGER;
ALTER TABLE llx_societe_rib ADD COLUMN exp_date_year INTEGER;
ALTER TABLE llx_societe_rib ADD COLUMN country_code varchar(10);
ALTER TABLE llx_societe_rib ADD COLUMN approved integer DEFAULT 0;
ALTER TABLE llx_societe_rib ADD COLUMN email varchar(255);
ALTER TABLE llx_societe_rib ADD COLUMN ending_date date;
ALTER TABLE llx_societe_rib ADD COLUMN max_total_amount_of_all_payments double(24,8);
ALTER TABLE llx_societe_rib ADD COLUMN preapproval_key varchar(255);
ALTER TABLE llx_societe_rib ADD COLUMN starting_date date;
ALTER TABLE llx_societe_rib ADD COLUMN total_amount_of_all_payments double(24,8);
ALTER TABLE llx_societe_rib ADD COLUMN stripe_card_ref varchar(128);
ALTER TABLE llx_societe_rib ADD COLUMN status integer NOT NULL DEFAULT 1;

UPDATE llx_societe_rib set type = 'ban' where type = '' OR type IS NULL;
-- VMYSQL4.3 ALTER TABLE llx_societe_rib MODIFY COLUMN type varchar(32) NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_societe_rib ALTER COLUMN type SET NOT NULL;
   
CREATE TABLE llx_ticketsup
(
	rowid       integer AUTO_INCREMENT PRIMARY KEY,
	entity		integer DEFAULT 1,
    ref         varchar(128) NOT NULL,
	track_id    varchar(128) NOT NULL,
	fk_soc		integer DEFAULT 0,
	fk_project	integer DEFAULT 0,
	origin_email   varchar(128),
	fk_user_create	integer,
	fk_user_assign	integer,
	subject	varchar(255),
	message	text,
	fk_statut integer,
	resolution integer,
	progress varchar(100),
	timing varchar(20),
	type_code varchar(32),
	category_code varchar(32),
	severity_code varchar(32),
	datec datetime,
	date_read datetime,
	date_close datetime,
	notify_tiers_at_create tinyint,
	tms timestamp
)ENGINE=innodb;

ALTER TABLE llx_ticketsup ADD COLUMN notify_tiers_at_create integer;
ALTER TABLE llx_ticketsup DROP INDEX uk_ticketsup_rowid_track_id;
ALTER TABLE llx_ticketsup ADD UNIQUE uk_ticketsup_track_id (track_id);

CREATE TABLE llx_ticketsup_msg
(
	rowid       integer AUTO_INCREMENT PRIMARY KEY,
	entity		integer DEFAULT 1,
	fk_track_id   varchar(128),
	fk_user_action	integer,
	datec datetime,
	message	text,
	private		integer DEFAULT 0
)ENGINE=innodb;


ALTER TABLE llx_ticketsup_msg ADD CONSTRAINT fk_ticketsup_msg_fk_track_id FOREIGN KEY (fk_track_id) REFERENCES llx_ticketsup (track_id);

CREATE TABLE llx_ticketsup_logs
(
	rowid       integer AUTO_INCREMENT PRIMARY KEY,
	entity		integer DEFAULT 1,
	fk_track_id   varchar(128),
	fk_user_create	integer,
	datec datetime,
	message	text
)ENGINE=innodb;

ALTER TABLE llx_ticketsup_logs ADD CONSTRAINT fk_ticketsup_logs_fk_track_id FOREIGN KEY (fk_track_id) REFERENCES llx_ticketsup (track_id);

CREATE TABLE llx_ticketsup_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    
  import_key       varchar(14)
)ENGINE=innodb;



-- Create dictionaries tables for ticket
create table llx_c_ticketsup_severity
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  entity		integer DEFAULT 1,
  code			varchar(32)				NOT NULL,
  pos			varchar(32)				NOT NULL,
  label			varchar(128)			NOT NULL,
  color			varchar(10)				NOT NULL,
  active		integer DEFAULT 1,
  use_default	integer DEFAULT 1,
  description	varchar(255)
)ENGINE=innodb;

create table llx_c_ticketsup_type
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  entity		integer DEFAULT 1,
  code			varchar(32)				NOT NULL,
  pos			varchar(32)				NOT NULL,
  label			varchar(128)			NOT NULL,
  active		integer DEFAULT 1,
  use_default	integer DEFAULT 1,
  description	varchar(255)
)ENGINE=innodb;

create table llx_c_ticketsup_category
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  entity		integer DEFAULT 1,
  code			varchar(32)				NOT NULL,
  pos			varchar(32)				NOT NULL,
  label			varchar(128)			NOT NULL,
  active		integer DEFAULT 1,
  use_default	integer DEFAULT 1,
  description	varchar(255)
)ENGINE=innodb;

ALTER TABLE llx_c_ticketsup_category ADD UNIQUE INDEX uk_code (code, entity);
ALTER TABLE llx_c_ticketsup_severity ADD UNIQUE INDEX uk_code (code, entity);
ALTER TABLE llx_c_ticketsup_type     ADD UNIQUE INDEX uk_code (code, entity);



-- Load data
INSERT INTO llx_c_ticketsup_severity (code, pos, label, color, active, use_default, description) VALUES('LOW',      '10', 'Low',                 '', 1, 0, NULL);
INSERT INTO llx_c_ticketsup_severity (code, pos, label, color, active, use_default, description) VALUES('NORMAL',   '20', 'Normal',              '', 1, 1, NULL);
INSERT INTO llx_c_ticketsup_severity (code, pos, label, color, active, use_default, description) VALUES('HIGH',     '30', 'High',                '', 1, 0, NULL);
INSERT INTO llx_c_ticketsup_severity (code, pos, label, color, active, use_default, description) VALUES('BLOCKING', '40', 'Critical / blocking', '', 1, 0, NULL);

INSERT INTO llx_c_ticketsup_type (code, pos, label, active, use_default, description) VALUES('COM',     '10', 'Commercial question',           1, 1, NULL);
INSERT INTO llx_c_ticketsup_type (code, pos, label, active, use_default, description) VALUES('ISSUE',   '20', 'Issue or problem'  ,            1, 0, NULL);
INSERT INTO llx_c_ticketsup_type (code, pos, label, active, use_default, description) VALUES('REQUEST', '25', 'Change or enhancement request', 1, 0, NULL);
INSERT INTO llx_c_ticketsup_type (code, pos, label, active, use_default, description) VALUES('PROJECT', '30', 'Project', 0, 0, NULL);
INSERT INTO llx_c_ticketsup_type (code, pos, label, active, use_default, description) VALUES('OTHER',   '40', 'Other',   1, 0, NULL);

INSERT INTO llx_c_ticketsup_category (code, pos, label, active, use_default, description) VALUES('OTHER', '10', 'Other',           1, 1, NULL);





ALTER TABLE llx_facturedet_rec ADD COLUMN date_start_fill integer DEFAULT 0;
ALTER TABLE llx_facturedet_rec ADD COLUMN date_end_fill integer DEFAULT 0;



CREATE TABLE llx_societe_account(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity	integer DEFAULT 1, 
	key_account       varchar(128),
	login             varchar(128) NOT NULL, 
    pass_encoding     varchar(24),
    pass_crypted      varchar(128),
    pass_temp         varchar(128),			    -- temporary password when asked for forget password
    fk_soc integer,
	site              varchar(128),
	fk_website        integer,
	note_private      text,
    date_last_login   datetime,
    date_previous_login datetime,
	date_creation datetime NOT NULL, 
	tms timestamp NOT NULL, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	import_key varchar(14), 
	status integer 
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

-- VMYSQL4.3 ALTER TABLE llx_societe_account MODIFY COLUMN pass_encoding varchar(24) NULL;

ALTER TABLE llx_const MODIFY type varchar(64) DEFAULT 'string';

UPDATE llx_const set type = 'text' where type = 'texte';
UPDATE llx_const set type = 'html' where name in (__ENCRYPT('ADHERENT_AUTOREGISTER_NOTIF_MAIL')__,__ENCRYPT('ADHERENT_AUTOREGISTER_MAIL')__,__ENCRYPT('ADHERENT_MAIL_VALID')__,__ENCRYPT('ADHERENT_MAIL_COTIS')__,__ENCRYPT('ADHERENT_MAIL_RESIL')__);

--UPDATE llx_const SET value = '', type = 'emailtemplate:member' WHERE name = __ENCRYPT('ADHERENT_AUTOREGISTER_MAIL')__ AND type != 'emailtemplate:member';
--UPDATE llx_const SET value = '', type = 'emailtemplate:member' WHERE name = __ENCRYPT('ADHERENT_MAIL_VALID')__ AND type != 'emailtemplate:member';
--UPDATE llx_const SET value = '', type = 'emailtemplate:member' WHERE name = __ENCRYPT('ADHERENT_MAIL_COTIS')__ AND type != 'emailtemplate:member';
--UPDATE llx_const SET value = '', type = 'emailtemplate:member' WHERE name = __ENCRYPT('ADHERENT_MAIL_RESIL')__ AND type != 'emailtemplate:member';

ALTER TABLE llx_societe_account ADD COLUMN key_account varchar(128);

ALTER TABLE llx_societe_account ADD INDEX idx_societe_account_rowid (rowid);
ALTER TABLE llx_societe_account ADD INDEX idx_societe_account_login (login);
ALTER TABLE llx_societe_account ADD INDEX idx_societe_account_status (status);
ALTER TABLE llx_societe_account ADD INDEX idx_societe_account_fk_website (fk_website);
ALTER TABLE llx_societe_account ADD INDEX idx_societe_account_fk_soc (fk_soc);

ALTER TABLE llx_societe_account ADD UNIQUE INDEX uk_societe_account_login_website_soc(entity, fk_soc, login, site, fk_website);
ALTER TABLE llx_societe_account ADD UNIQUE INDEX uk_societe_account_key_account_soc(entity, fk_soc, key_account, site, fk_website);

ALTER TABLE llx_societe_account ADD CONSTRAINT llx_societe_account_fk_website FOREIGN KEY (fk_website) REFERENCES llx_website(rowid);
ALTER TABLE llx_societe_account ADD CONSTRAINT llx_societe_account_fk_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);


ALTER TABLE llx_societe_rib MODIFY COLUMN max_total_amount_of_all_payments double(24,8);
ALTER TABLE llx_societe_rib MODIFY COLUMN total_amount_of_all_payments double(24,8);


INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'adherent','member','',0,null,null,'(SendingEmailOnAutoSubscription)'       ,10,1,1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourMembershipRequestWasReceived)__','__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(ThisIsContentOfYourMembershipRequestWasReceived)__<br>\n<br>__ONLINE_PAYMENT_TEXT_AND_URL__<br>\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 1);
INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'adherent','member','',0,null,null,'(SendingEmailOnMemberValidation)'       ,20,1,1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourMembershipWasValidated)__',      '__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(ThisIsContentOfYourMembershipWasValidated)__<br>\n<br>__ONLINE_PAYMENT_TEXT_AND_URL__<br>\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 1);
INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'adherent','member','',0,null,null,'(SendingEmailOnNewSubscription)'        ,30,1,1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourSubscriptionWasRecorded)__',     '__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(ThisIsContentOfYourSubscriptionWasRecorded)__<br>\n\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 1);
INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'adherent','member','',0,null,null,'(SendingReminderForExpiredSubscription)',40,1,1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(SubscriptionReminderEmail)__',       '__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(ThisIsContentOfSubscriptionReminderEmail)__<br>\n<br>__ONLINE_PAYMENT_TEXT_AND_URL__<br>\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 1);
INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'adherent','member','',0,null,null,'(SendingEmailOnCancelation)'            ,50,1,1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourMembershipWasCanceled)__',       '__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(YourMembershipWasCanceled)__<br>\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 1);
INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'adherent','member','',0,null,null,'(SendingAnEMailToMember)'               ,60,1,1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(CardContent)__',                     '__(Hello)__,<br><br>\n\n__(ThisIsContentOfYourCard)__<br>\n__(ID)__ : __ID__<br>\n__(Civiliyty)__ : __MEMBER_CIVILITY__<br>\n__(Firstname)__ : __MEMBER_FIRSTNAME__<br>\n__(Lastname)__ : __MEMBER_LASTNAME__<br>\n__(Fullname)__ : __MEMBER_FULLNAME__<br>\n__(Company)__ : __MEMBER_COMPANY__<br>\n__(Address)__ : __MEMBER_ADDRESS__<br>\n__(Zip)__ : __MEMBER_ZIP__<br>\n__(Town)__ : __MEMBER_TOWN__<br>\n__(Country)__ : __MEMBER_COUNTRY__<br>\n__(Email)__ : __MEMBER_EMAIL__<br>\n__(Birthday)__ : __MEMBER_BIRTH__<br>\n__(Photo)__ : __MEMBER_PHOTO__<br>\n__(Login)__ : __MEMBER_LOGIN__<br>\n__(Password)__ : __MEMBER_PASSWORD__<br>\n__(Phone)__ : __MEMBER_PHONE__<br>\n__(PhonePerso)__ : __MEMBER_PHONEPRO__<br>\n__(PhoneMobile)__ : __MEMBER_PHONEMOBILE__<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 1);

ALTER TABLE llx_product ADD COLUMN fk_default_warehouse integer DEFAULT NULL;
ALTER TABLE llx_product ADD CONSTRAINT fk_product_default_warehouse FOREIGN KEY (fk_default_warehouse) REFERENCES llx_entrepot (rowid);

-- Assets
CREATE TABLE llx_asset(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	ref varchar(128) NOT NULL,
	entity integer DEFAULT 1 NOT NULL,
	label varchar(255),
	amount_ht double(24,8) DEFAULT NULL,
	amount_vat double(24,8) DEFAULT NULL,
	fk_asset_type integer NOT NULL,
	description text,
	note_public text,
	note_private text,
	date_creation datetime NOT NULL,
	tms timestamp NOT NULL,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	import_key varchar(14),
	status integer NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_asset ADD INDEX idx_asset_rowid (rowid);
ALTER TABLE llx_asset ADD INDEX idx_asset_ref (ref);
ALTER TABLE llx_asset ADD INDEX idx_asset_entity (entity);

ALTER TABLE llx_asset ADD INDEX idx_asset_fk_asset_type (fk_asset_type);

create table llx_asset_extrafields
(
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  tms timestamp,
  fk_object integer NOT NULL,
  import_key varchar(14)
) ENGINE=innodb;

create table llx_asset_type
(
  rowid                                 integer AUTO_INCREMENT PRIMARY KEY,
  entity                                integer DEFAULT 1 NOT NULL,	-- multi company id
  tms                                   timestamp,
  label                                 varchar(50) NOT NULL,
  accountancy_code_asset                varchar(32),
  accountancy_code_depreciation_asset   varchar(32),
  accountancy_code_depreciation_expense varchar(32),
  note                                  text
)ENGINE=innodb;

ALTER TABLE llx_asset_type ADD UNIQUE INDEX uk_asset_type_label (label, entity);

ALTER TABLE llx_asset ADD CONSTRAINT fk_asset_asset_type FOREIGN KEY (fk_asset_type)    REFERENCES llx_asset_type (rowid);

create table llx_asset_type_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_asset_type_extrafields ADD INDEX idx_asset_type_extrafields (fk_object);

INSERT INTO llx_accounting_journal (rowid, code, label, nature, active) VALUES (7,'INV', 'Inventory journal', 8, 1);

UPDATE llx_accounting_account set account_parent = 0 WHERE account_parent = '' OR account_parent IS NULL;
-- VMYSQL4.1 ALTER TABLE llx_accounting_account MODIFY COLUMN account_parent integer DEFAULT 0;
-- VPGSQL8.2 ALTER TABLE llx_accounting_account ALTER COLUMN account_parent DROP DEFAULT;
-- VPGSQL8.2 ALTER TABLE llx_accounting_account MODIFY COLUMN account_parent integer USING account_parent::integer;
-- VPGSQL8.2 ALTER TABLE llx_accounting_account ALTER COLUMN account_parent SET DEFAULT 0;
ALTER TABLE llx_accounting_account ADD INDEX idx_accounting_account_account_parent (account_parent);

UPDATE llx_accounting_bookkeeping set date_creation = tms where date_creation IS NULL;

ALTER TABLE llx_extrafields MODIFY COLUMN list VARCHAR(128);

UPDATE llx_rights_def set module = 'asset' where module = 'assets';

ALTER TABLE llx_c_accounting_category ADD COLUMN entity integer NOT NULL DEFAULT 1 AFTER rowid;
-- VMYSQL4.1 DROP INDEX uk_c_accounting_category on llx_c_accounting_category;
-- VPGSQL8.2 DROP INDEX uk_c_accounting_category;
ALTER TABLE llx_c_accounting_category ADD UNIQUE INDEX uk_c_accounting_category(code,entity);
-- VMYSQL4.1 DROP INDEX uk_accounting_journal_code on llx_accounting_journal;
-- VPGSQL8.2 DROP INDEX uk_accounting_journal_code;
ALTER TABLE llx_accounting_journal ADD UNIQUE INDEX uk_accounting_journal_code (code,entity);

UPDATE llx_c_email_templates SET lang = '' WHERE lang IS NULL;

-- Warehouse
ALTER TABLE llx_entrepot ADD COLUMN model_pdf VARCHAR(255) AFTER fk_user_author;
ALTER TABLE llx_stock_mouvement ADD COLUMN model_pdf VARCHAR(255) AFTER origintype;

