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
ALTER TABLE llx_usergroup ADD color VARCHAR(6) AFTER tms;

create table llx_categorie_project_task (
  fk_categorie  	integer NOT NULL,
  fk_project_task   integer NOT NULL,
  import_key    	varchar(14)
) ENGINE=innodb;

--noqa:disable=PRS
ALTER TABLE llx_categorie_project_task ADD PRIMARY KEY pk_categorie_propal (fk_categorie, fk_project_task);
--noqa:enable=PRS
ALTER TABLE llx_categorie_project_task ADD INDEX idx_categorie_project_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_project_task ADD INDEX idx_categorie_project_fk_task (fk_project_task);

ALTER TABLE llx_categorie_project_task ADD CONSTRAINT fk_categorie_project_task_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_project_task ADD CONSTRAINT fk_categorie_project_task_rowid FOREIGN KEY (fk_project_task) REFERENCES llx_projet (rowid);

UPDATE llx_actioncomm SET elementtype = 'project_task' WHERE elementtype = 'task';

ALTER TABLE llx_document_model ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

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
ALTER TABLE llx_facture ADD COLUMN pos_print_counter integer DEFAULT 0;
ALTER TABLE llx_facture ADD COLUMN email_sent_counter integer DEFAULT 0;

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

CREATE TABLE llx_webhook_history(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	--ref varchar(128) NOT NULL,
	trigger_code varchar(128) NOT NULL,
	trigger_data text NOT NULL,
	fk_target integer NOT NULL,
	url varchar(255) NOT NULL,
	error_message text,
	--note_public text,
	note_private text,
	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	--fk_user_modif integer,
	import_key varchar(14),
	status integer DEFAULT 1 NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE llx_webhook_history ADD COLUMN trigger_code varchar(128) NOT NULL DEFAULT 'UNKOWN';
ALTER TABLE llx_webhook_history ADD COLUMN error_message text;


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
ALTER TABLE llx_user ADD COLUMN access_hours varchar(128) DEFAULT NULL;

ALTER TABLE llx_adherent ADD COLUMN birth_place varchar(64) after birth;

ALTER TABLE llx_societe ADD COLUMN birth date DEFAULT NULL after fk_forme_juridique;
ALTER TABLE llx_societe ADD vatexemptcode varchar(24) DEFAULT NULL;

-- Remove deprecated permissions
DELETE FROM llx_user_rights WHERE fk_id IN (SELECT id FROM llx_rights_def WHERE module = 'webhook' AND perms = 'webhook_target');
DELETE FROM llx_usergroup_rights WHERE fk_id IN (SELECT id FROM llx_rights_def WHERE module = 'webhook' AND perms = 'webhook_target');
DELETE FROM llx_rights_def WHERE module = 'webhook' AND perms = 'webhook_target';

DELETE FROM llx_user_rights WHERE fk_id IN (SELECT id FROM llx_rights_def WHERE module = 'eventorganization');
DELETE FROM llx_usergroup_rights WHERE fk_id IN (SELECT id FROM llx_rights_def WHERE module = 'eventorganization');
DELETE FROM llx_rights_def WHERE module = 'eventorganization';

ALTER TABLE llx_rights_def ADD COLUMN family VARCHAR(64) AFTER module_position;

-- Reorder some permission
UPDATE llx_rights_def SET module_position = 64 WHERE module = 'intracommreport' AND module_position <> 64;
UPDATE llx_rights_def SET module_position = 62 WHERE module = 'accounting' AND module_position <> 62;

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

ALTER TABLE llx_subscription ADD COLUMN ref_ext varchar(128);
ALTER TABLE llx_subscription ADD COLUMN note_private text;

ALTER TABLE llx_bank_import ADD COLUMN fitid varchar(255) NULL after id_account; -- OFX Financial Institution Transaction ID "FITID"

ALTER TABLE llx_element_contact ADD mandatory_signature TINYINT AFTER element_id;

-- default deposit % if payment term needs it on supplier
ALTER TABLE llx_supplier_proposal ADD COLUMN deposit_percent varchar(63) DEFAULT NULL AFTER fk_cond_reglement;
ALTER TABLE llx_commande_fournisseur ADD COLUMN deposit_percent varchar(63) DEFAULT NULL AFTER fk_cond_reglement;


-- import key for subscriptions
ALTER TABLE llx_subscription ADD COLUMN import_key varchar(14) NULL;

ALTER TABLE llx_categorie ADD COLUMN extraparams varchar(255) AFTER fk_soc;

CREATE TABLE llx_categorie_propal
(
  fk_categorie integer NOT NULL,
  fk_propal integer NOT NULL,
  import_key varchar(14)
) ENGINE=innodb;
--noqa:disable=PRS
ALTER TABLE llx_categorie_propal ADD PRIMARY KEY pk_categorie_propal (fk_categorie, fk_propal);
--noqa:enable=PRS
ALTER TABLE llx_categorie_propal ADD INDEX idx_categorie_propal_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_propal ADD INDEX idx_categorie_propal_fk_propal (fk_propal);
ALTER TABLE llx_categorie_propal ADD CONSTRAINT fk_categorie_propal_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_propal ADD CONSTRAINT fk_categorie_propal_fk_propal_rowid FOREIGN KEY (fk_propal) REFERENCES llx_propal (rowid);

create table llx_categorie_supplier_proposal
(
  fk_categorie        integer NOT NULL,
  fk_supplier_proposal integer NOT NULL,
  import_key          varchar(14)
)ENGINE=innodb;

CREATE TABLE llx_accounting_bookkeeping_piece
(
	rowid               integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
	entity              integer DEFAULT 1 NOT NULL,
	ref             	varchar(128),
	tms					timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	datec				datetime,
	datep				date NOT NULL,
	statut				smallint DEFAULT 0,

	note_private		text,
	note_public			text,

	fk_user_author		integer,
	fk_user_modif		integer,
	fk_user_valid		integer,
	fk_user_closing		integer,

	import_key          varchar(14),
	extraparams         varchar(255)
) ENGINE=innodb;

ALTER TABLE llx_accounting_bookkeeping_piece ADD UNIQUE INDEX uk_accounting_bookkeeping_piece_ref (ref, entity);

ALTER TABLE llx_accounting_bookkeeping_piece ADD INDEX idx_accounting_bookkeeping_piece_fk_user_author (fk_user_author);
ALTER TABLE llx_accounting_bookkeeping_piece ADD INDEX idx_accounting_bookkeeping_piece_fk_user_modif (fk_user_modif);
ALTER TABLE llx_accounting_bookkeeping_piece ADD INDEX idx_accounting_bookkeeping_piece_fk_user_valid (fk_user_valid);
ALTER TABLE llx_accounting_bookkeeping_piece ADD INDEX idx_accounting_bookkeeping_piece_fk_user_closing (fk_user_closing);

ALTER TABLE llx_mailing ADD COLUMN fk_project integer DEFAULT NULL;
UPDATE llx_c_units SET label = 'unitP' WHERE code = 'P';

ALTER TABLE llx_receptiondet_batch ADD COLUMN description text AFTER fk_product;
ALTER TABLE llx_receptiondet_batch ADD COLUMN fk_unit integer AFTER qty;
ALTER TABLE llx_receptiondet_batch ADD COLUMN rang integer DEFAULT 0 AFTER cost_price;

ALTER TABLE llx_ecm_files ADD INDEX idx_ecm_files_src_object_type_id (src_object_type, src_object_id);

INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (5, '511', 'eGbR - eingetragene Gesellschaft bürgerlichen Rechts', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (5, '512', 'Einzelunternehmen', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (5, '513', 'PartG - Partnerschaftsgesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (5, '514', 'PartG mbB - Partnerschaftsgesellschaft mit beschränkter Berufshaftung', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (5, '515', 'KGaA - Kommanditgesellschaft auf Aktien', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (5, '516', 'GmbH & Co. KGaA - Gesellschaft mit beschränkter Haftung & Compagnie Kommanditgesellschaft auf Aktien', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (5, '517', 'SE - Societas Europaea', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (5, '518', 'Stiftung', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (5, '519', 'gGmbH - gemeinnützige Gesellschaft mit beschränkter Haftung', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (5, '520', 'gUG - gemeinnützige Unternehmergesellschaft (haftungsbeschränkt)', 1);

ALTER TABLE llx_oauth_token ADD COLUMN tokenstring_refresh text NULL AFTER tokenstring;
ALTER TABLE llx_oauth_token ADD COLUMN expire_at datetime NULL AFTER lastaccess;

ALTER TABLE llx_blockedlog ADD COLUMN linktoref varchar(255);
ALTER TABLE llx_blockedlog ADD COLUMN linktype varchar(16);
ALTER TABLE llx_blockedlog ADD COLUMN vat double(24,8) DEFAULT NULL;


-- Incoterms 2025 and specific terms
-- DAT is replaced by DPU - but not deactivating for existing installations
-- UPDATE llx_c_incoterms SET active = 0 WHERE code = 'DAT';

-- Add new 2025 Incoterms and specific terms when they do not exist
ALTER TABLE llx_c_incoterms MODIFY code varchar(8) NOT NULL;

-- For MySQL and MariaDB:
INSERT INTO llx_c_incoterms (code, label, libelle, active) VALUES ('DPU', 'Delivered at Place Unloaded', 'Delivered at Place Unloaded, marchandises déchargées et livrées au lieu de destination désigné (remplace DAT, élargit les lieux de livraison possibles)', 1);
INSERT INTO llx_c_incoterms (code, label, libelle, active) VALUES ('DTP', 'Delivered at Terminal Paid', 'Delivered at Terminal Paid, marchandises livrées et dédouanées dans un terminal du pays de destination', 0);
INSERT INTO llx_c_incoterms (code, label, libelle, active) VALUES ('DPP', 'Delivered at Place Paid', 'Delivered at Place Paid, marchandises livrées et dédouanées à une adresse précise du pays de destination', 0);
INSERT INTO llx_c_incoterms (code, label, libelle, active) VALUES ('DTP(DHL)', 'Duties and Taxes Paid', 'Duties and Taxes Paid (service DHL) : l''expéditeur paie les droits de douane et taxes à l''importation (spécifique à DHL)', 0);

-- Update existing Incoterms descriptions
UPDATE llx_c_incoterms
SET libelle = 'Cost and Freight, chargé dans le bateau, livraison au port de départ, frais payés jusqu''au port d''arrivée, sans assurance pour le transport, non déchargé du navire à destination (les frais de déchargement sont inclus ou non au port d''arrivée)'
WHERE code = 'CFR';

UPDATE llx_c_incoterms
SET libelle = 'Cost, Insurance and Freight, chargé sur le bateau, frais jusqu''au port d''arrivée, avec l''assurance marchandise transportée souscrite par le vendeur pour le compte de l''acheteur (couverture standard, 10% de la valeur commerciale)'
WHERE code = 'CIF';

UPDATE llx_c_incoterms
SET libelle = 'Carriage and Insurance Paid to, idem CPT, avec assurance marchandise transportée souscrite par le vendeur pour le compte de l''acheteur (couverture tous risques)'
WHERE code = 'CIP';



-- Fix a wrong migration script
UPDATE llx_oauth_token SET tokenstring = token, token = NULL WHERE service = 'dolibarr_rest_api' AND tokenstring IS NULL AND token IS NOT NULL;


ALTER TABLE llx_categorie_supplier_proposal ADD PRIMARY KEY pk_categorie_supplier_proposal (fk_categorie, fk_supplier_proposal);
ALTER TABLE llx_categorie_supplier_proposal ADD INDEX idx_categorie_supplier_proposal_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_supplier_proposal ADD INDEX idx_categorie_supplier_proposal_fk_supplier_proposal (fk_supplier_proposal);

ALTER TABLE llx_categorie_supplier_proposal ADD CONSTRAINT fk_categorie_supplier_proposal_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_supplier_proposal ADD CONSTRAINT fk_categorie_supplier_proposal_fk_supplier_proposal_rowid FOREIGN KEY (fk_supplier_proposal) REFERENCES llx_supplier_proposal (rowid);

ALTER TABLE llx_blockedlog DROP INDEX entity;
ALTER TABLE llx_blockedlog DROP INDEX entity_action_certified;
ALTER TABLE llx_blockedlog ADD INDEX idx_entity_action (entity,action);

ALTER TABLE llx_accounting_bookkeeping ADD COLUMN matching_general tinyint DEFAULT 0 NOT NULL AFTER multicurrency_code;
ALTER TABLE llx_accounting_bookkeeping_tmp ADD COLUMN matching_general tinyint DEFAULT 0 NOT NULL AFTER multicurrency_code;

INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CDF', '[70,67]', 1, 'Congolese Franc');

ALTER TABLE llx_societe MODIFY COLUMN mode_reglement integer;

ALTER TABLE llx_blockedlog DROP COLUMN signature_line;


ALTER TABLE llx_ecm_files ADD COLUMN geolat double(24,8) DEFAULT NULL;
ALTER TABLE llx_ecm_files ADD COLUMN geolong double(24,8) DEFAULT NULL;
ALTER TABLE llx_ecm_files ADD COLUMN geopoint point DEFAULT NULL;
ALTER TABLE llx_ecm_files ADD COLUMN georesultcode varchar(16) NULL;

-- Add table for extrafields lines support on expensereport module
CREATE TABLE llx_expensereport_det_extrafields
(
	rowid                     integer AUTO_INCREMENT PRIMARY KEY,
	tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_object                 integer NOT NULL,
	import_key                varchar(14)
) ENGINE=innodb;


ALTER TABLE llx_blockedlog ADD INDEX idx_ref_object (ref_object);
ALTER TABLE llx_blockedlog ADD CONSTRAINT fk_linktoref FOREIGN KEY (linktoref) REFERENCES llx_blockedlog(ref_object);

ALTER TABLE llx_fichinterdet ADD COLUMN special_code integer DEFAULT 0 AFTER fk_parent_line;
ALTER TABLE llx_fichinterdet ADD COLUMN product_type integer DEFAULT 0 AFTER special_code;

ALTER TABLE llx_pos_cash_fence ADD COLUMN hour_close INTEGER DEFAULT null after year_close;
ALTER TABLE llx_pos_cash_fence ADD COLUMN min_close INTEGER DEFAULT null after hour_close;
ALTER TABLE llx_pos_cash_fence ADD COLUMN sec_close INTEGER DEFAULT null after min_close;

-- end of migration
