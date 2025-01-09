--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 14.0.0 or higher.
--
-- To restrict request to Mysql version x.y minimum use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y minimum use -- VPGSQLx.y
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To create a unique index ALTER TABLE llx_table ADD UNIQUE INDEX uk_table_field (field);
-- To drop an index:        -- VMYSQL4.1 DROP INDEX nomindex on llx_table;
-- To drop an index:        -- VPGSQL8.2 DROP INDEX nomindex;
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
-- To rebuild sequence for postgresql after insert by forcing id autoincrement fields: 
-- -- VPGSQL8.2 SELECT dol_util_rebuild_sequences();


-- Missing in v13 or lower

-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_propal set tms = datec where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_propal set tms = null where DATE(STR_TO_DATE(tms, '%Y-%m-%d')) IS NULL;

-- VPGSQL8.2 DROP TRIGGER update_customer_modtime ON llx_ecm_directories;
-- VPGSQL8.2 DROP TRIGGER update_customer_modtime ON llx_ecm_files;
-- VPGSQL8.2 CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_ecm_directories FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
-- VPGSQL8.2 CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_ecm_files FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();

-- VMYSQL4.3 ALTER TABLE llx_emailcollector_emailcollector MODIFY COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_ecm_files ADD COLUMN note_private text AFTER fk_user_m;
ALTER TABLE llx_ecm_files ADD COLUMN note_public text AFTER note_private;

ALTER TABLE llx_accounting_bookkeeping DROP INDEX idx_accounting_bookkeeping_numero_compte;
ALTER TABLE llx_accounting_bookkeeping DROP INDEX idx_accounting_bookkeeping_code_journal;

ALTER TABLE llx_accounting_bookkeeping ADD INDEX idx_accounting_bookkeeping_fk_docdet (fk_docdet);
ALTER TABLE llx_accounting_bookkeeping ADD INDEX idx_accounting_bookkeeping_doc_date (doc_date);
ALTER TABLE llx_accounting_bookkeeping ADD INDEX idx_accounting_bookkeeping_numero_compte (numero_compte, entity);
ALTER TABLE llx_accounting_bookkeeping ADD INDEX idx_accounting_bookkeeping_code_journal (code_journal, entity);

ALTER TABLE llx_accounting_bookkeeping ADD INDEX idx_accounting_bookkeeping_piece_num (piece_num, entity);

ALTER TABLE llx_recruitment_recruitmentcandidature MODIFY COLUMN email_msgid VARCHAR(175);

ALTER TABLE llx_asset CHANGE COLUMN amount amount_ht double(24,8) DEFAULT NULL;
ALTER TABLE llx_asset ADD COLUMN amount_vat double(24,8) DEFAULT NULL;

ALTER TABLE llx_supplier_proposal_extrafields ADD INDEX idx_supplier_proposal_extrafields (fk_object);
ALTER TABLE llx_supplier_proposaldet_extrafields ADD INDEX idx_supplier_proposaldet_extrafields (fk_object);

ALTER TABLE llx_asset_extrafields ADD INDEX idx_asset_extrafields (fk_object);

insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 6,'AC_EMAIL_IN','system','reception Email',NULL, 1, 4);

-- VMYSQL4.3 ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN montant double(24,8) NULL;
-- VPGSQL8.2 ALTER TABLE llx_accounting_bookkeeping ALTER COLUMN montant DROP NOT NULL;

UPDATE llx_c_country SET eec = 1 WHERE code IN ('AT','BE','BG','CY','CZ','DE','DK','EE','ES','FI','FR','GR','HR','NL','HU','IE','IM','IT','LT','LU','LV','MC','MT','PL','PT','RO','SE','SK','SI');

INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  11, 'US-BASE', 'USA basic chart of accounts', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 14, 'CA-ENG-BASE', 'Canadian basic chart of accounts - English', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 154, 'SAT/24-2019', 'Catalogo y codigo agrupador fiscal del 2019', 1);

UPDATE llx_accounting_system SET fk_country = 1 WHERE fk_country IS NULL;

UPDATE llx_const set value = __ENCRYPT('eldy')__ WHERE __DECRYPT('value')__ = 'auguria';
UPDATE llx_const set value = __ENCRYPT('eldy')__ WHERE __DECRYPT('value')__ = 'bureau2crea';
UPDATE llx_const set value = __ENCRYPT('eldy')__ WHERE __DECRYPT('value')__ = 'amarok';
UPDATE llx_const set value = __ENCRYPT('eldy')__ WHERE __DECRYPT('value')__ = 'cameleo';
DELETE FROM llx_user_param where param = 'MAIN_THEME' and value in ('auguria', 'amarok', 'cameleo');

ALTER TABLE llx_product_fournisseur_price ADD COLUMN packaging real DEFAULT NULL;
-- VMYSQL4.3 ALTER TABLE llx_product_fournisseur_price MODIFY COLUMN packaging real DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product_fournisseur_price MODIFY COLUMN packaging real DEFAULT NULL USING packaging::real;
-- VPGSQL8.2 ALTER TABLE llx_product_fournisseur_price ALTER COLUMN packaging DROP DEFAULT;


-- For v14

--Fix bad sign on multicompany column for customer invoice lines
UPDATE llx_facturedet SET multicurrency_subprice = -multicurrency_subprice WHERE ((multicurrency_subprice < 0 and subprice > 0) OR (multicurrency_subprice > 0 and subprice < 0));
UPDATE llx_facturedet SET multicurrency_total_ht = -multicurrency_total_ht WHERE ((multicurrency_total_ht < 0 and total_ht > 0) OR (multicurrency_total_ht > 0 and total_ht < 0));
UPDATE llx_facturedet SET multicurrency_total_tva = -multicurrency_total_tva WHERE ((multicurrency_total_tva < 0 and total_tva > 0) OR (multicurrency_total_tva > 0 and total_tva < 0)); 
UPDATE llx_facturedet SET multicurrency_total_ttc = -multicurrency_total_ttc WHERE ((multicurrency_total_ttc < 0 and total_ttc > 0) OR (multicurrency_total_ttc > 0 and total_ttc < 0));  
--Fix bad sign on multicompany column for customer invoices
UPDATE llx_facture SET multicurrency_total_ht = -multicurrency_total_ht WHERE ((multicurrency_total_ht < 0 and total_ht > 0) OR (multicurrency_total_ht > 0 and total_ht < 0));  
UPDATE llx_facture SET multicurrency_total_tva = -multicurrency_total_tva WHERE ((multicurrency_total_tva < 0 and total_tva > 0) OR (multicurrency_total_tva > 0 and total_tva < 0));  
UPDATE llx_facture SET multicurrency_total_ttc = -multicurrency_total_ttc WHERE ((multicurrency_total_ttc < 0 and total_ttc > 0) OR (multicurrency_total_ttc > 0 and total_ttc < 0));  
--Fix bad sign on multicurrency column for supplier invoice lines
UPDATE llx_facture_fourn_det SET multicurrency_subprice = -multicurrency_subprice WHERE ((multicurrency_subprice < 0 and pu_ht > 0) OR (multicurrency_subprice > 0 and pu_ht < 0));
UPDATE llx_facture_fourn_det SET multicurrency_total_ht = -multicurrency_total_ht WHERE ((multicurrency_total_ht < 0 and total_ht > 0) OR (multicurrency_total_ht > 0 and total_ht < 0));
UPDATE llx_facture_fourn_det SET multicurrency_total_tva = -multicurrency_total_tva WHERE ((multicurrency_total_tva < 0 and tva > 0) OR (multicurrency_total_tva > 0 and tva < 0)); 
UPDATE llx_facture_fourn_det SET multicurrency_total_ttc = -multicurrency_total_ttc WHERE ((multicurrency_total_ttc < 0 and total_ttc > 0) OR (multicurrency_total_ttc > 0 and total_ttc < 0));  
--Fix bad sign on multicompany column for customer invoices
UPDATE llx_facture_fourn SET multicurrency_total_ht = -multicurrency_total_ht WHERE ((multicurrency_total_ht < 0 and total_ht > 0) OR (multicurrency_total_ht > 0 and total_ht < 0));  
UPDATE llx_facture_fourn SET multicurrency_total_tva = -multicurrency_total_tva WHERE ((multicurrency_total_tva < 0 and total_tva > 0) OR (multicurrency_total_tva > 0 and total_tva < 0));  
UPDATE llx_facture_fourn SET multicurrency_total_ttc = -multicurrency_total_ttc WHERE ((multicurrency_total_ttc < 0 and total_ttc > 0) OR (multicurrency_total_ttc > 0 and total_ttc < 0));  


UPDATE llx_c_ticket_type set label = 'Issue or bug' WHERE code = 'ISSUE';
INSERT INTO llx_c_ticket_type (code, pos, label, active, use_default, description) VALUES('PROBLEM', '22', 'Problem', 0, 0, NULL);

ALTER TABLE llx_import_model MODIFY COLUMN type varchar(64);
ALTER TABLE llx_export_model MODIFY COLUMN type varchar(64);

ALTER TABLE llx_import_model ADD COLUMN entity integer DEFAULT 0 NOT NULL;
ALTER TABLE llx_export_model ADD COLUMN entity integer DEFAULT 0 NOT NULL;

ALTER TABLE llx_product_lot ADD COLUMN eol_date datetime NULL;
ALTER TABLE llx_product_lot ADD COLUMN manufacturing_date datetime NULL;
ALTER TABLE llx_product_lot ADD COLUMN scrapping_date datetime NULL;

create table llx_accounting_groups_account
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  fk_accounting_account		INTEGER NOT NULL,
  fk_c_accounting_category	INTEGER NOT NULL
)ENGINE=innodb;


ALTER TABLE llx_oauth_token ADD COLUMN restricted_ips varchar(200);
ALTER TABLE llx_oauth_token ADD COLUMN datec datetime DEFAULT NULL;
ALTER TABLE llx_oauth_token ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_events ADD COLUMN authentication_method varchar(64) NULL;
ALTER TABLE llx_events ADD COLUMN fk_oauth_token integer NULL;

ALTER TABLE llx_mailing_cibles MODIFY COLUMN tag varchar(64) NULL;
ALTER TABLE llx_mailing_cibles ADD INDEX idx_mailing_cibles_tag (tag);


ALTER TABLE llx_c_availability ADD COLUMN position integer NOT NULL DEFAULT 0;

ALTER TABLE llx_adherent ADD COLUMN ref varchar(30) AFTER rowid;
UPDATE llx_adherent SET ref = rowid WHERE ref = '' or ref IS NULL;
ALTER TABLE llx_adherent MODIFY COLUMN ref varchar(30) NOT NULL;
ALTER TABLE llx_adherent ADD UNIQUE INDEX uk_adherent_ref (ref, entity);

ALTER TABLE llx_societe ADD COLUMN accountancy_code_sell varchar(32) AFTER webservices_key;
ALTER TABLE llx_societe ADD COLUMN accountancy_code_buy varchar(32) AFTER accountancy_code_sell;

ALTER TABLE llx_bank_account ADD COLUMN ics varchar(32) NULL;
ALTER TABLE llx_bank_account ADD COLUMN ics_transfer varchar(32) NULL;

ALTER TABLE llx_facture MODIFY COLUMN date_valid DATETIME NULL DEFAULT NULL;


-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_dolibarr_state_board.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_dolibarr_state_board.php' AND entity = 1);

-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_members_last_modified.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_members_last_modified.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_members_last_subscriptions.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_members_last_subscriptions.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_members_subscriptions_by_year.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_members_subscriptions_by_year.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_members_by_type.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_members_by_type.php' AND entity = 1);


ALTER TABLE llx_website ADD COLUMN lastaccess datetime NULL;
ALTER TABLE llx_website ADD COLUMN pageviews_month BIGINT UNSIGNED DEFAULT 0;
ALTER TABLE llx_website ADD COLUMN pageviews_total BIGINT UNSIGNED DEFAULT 0;


-- Drop foreign key with bad name or not required
ALTER TABLE llx_workstation_workstation DROP FOREIGN KEY llx_workstation_workstation_fk_user_creat;
ALTER TABLE llx_propal DROP FOREIGN KEY llx_propal_fk_warehouse;


CREATE TABLE llx_workstation_workstation(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	ref varchar(128) DEFAULT '(PROV)' NOT NULL,
    label varchar(255),
    type varchar(7),
    note_public text,
	entity int DEFAULT 1,
	note_private text,
	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	import_key varchar(14),
	status smallint NOT NULL,
	nb_operators_required integer,
	thm_operator_estimated double,
	thm_machine_estimated double
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE llx_workstation_workstation ADD INDEX idx_workstation_workstation_rowid (rowid);
ALTER TABLE llx_workstation_workstation ADD INDEX idx_workstation_workstation_ref (ref);
ALTER TABLE llx_workstation_workstation ADD CONSTRAINT fk_workstation_workstation_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_workstation_workstation ADD INDEX idx_workstation_workstation_status (status);

CREATE TABLE llx_workstation_workstation_resource(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_resource integer,
	fk_workstation integer
) ENGINE=innodb;

CREATE TABLE llx_workstation_workstation_usergroup(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_usergroup integer,
	fk_workstation integer
) ENGINE=innodb;

DROP TABLE llx_c_producbatch_qcstatus;		-- delete table with bad name

CREATE TABLE llx_c_productbatch_qcstatus(
  rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
  entity   integer NOT NULL DEFAULT 1,
  code     varchar(16)        NOT NULL,
  label    varchar(50)        NOT NULL,
  active   integer  DEFAULT 1 NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_c_productbatch_qcstatus ADD UNIQUE INDEX uk_c_productbatch_qcstatus(code, entity);

INSERT INTO llx_c_productbatch_qcstatus (code, label, active) VALUES ('OK', 'InWorkingOrder', 1);
INSERT INTO llx_c_productbatch_qcstatus (code, label, active) VALUES ('KO', 'OutOfOrder', 1);

ALTER TABLE llx_product_customer_price ADD COLUMN ref_customer varchar(30);
ALTER TABLE llx_product_customer_price_log ADD COLUMN ref_customer varchar(30);

ALTER TABLE llx_propal ADD COLUMN fk_warehouse integer DEFAULT NULL AFTER fk_shipping_method;
--ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_warehouse FOREIGN KEY (fk_warehouse) REFERENCES llx_entrepot(rowid);
ALTER TABLE llx_propal ADD INDEX idx_propal_fk_warehouse(fk_warehouse);

ALTER TABLE llx_societe DROP INDEX idx_societe_entrepot;
ALTER TABLE llx_societe CHANGE fk_entrepot fk_warehouse INTEGER DEFAULT NULL;
--ALTER TABLE llx_societe ADD CONSTRAINT fk_propal_fk_warehouse FOREIGN KEY (fk_warehouse) REFERENCES llx_entrepot(rowid);
ALTER TABLE llx_societe ADD INDEX idx_societe_warehouse(fk_warehouse);

-- VMYSQL4.3 ALTER TABLE llx_societe MODIFY COLUMN fk_typent integer NULL;
-- VPGSQL8.2 ALTER TABLE llx_societe ALTER COLUMN fk_typent DROP NOT NULL;
UPDATE llx_societe SET fk_typent=NULL, tms=tms WHERE fk_typent=0;

DELETE FROM llx_c_typent WHERE code='TE_UNKNOWN';

ALTER TABLE llx_socpeople MODIFY poste varchar(255);

ALTER TABLE llx_menu ADD COLUMN prefix varchar(255) NULL AFTER titre;

ALTER TABLE llx_chargesociales ADD COLUMN fk_user integer DEFAULT NULL;

ALTER TABLE llx_mrp_production ADD COLUMN origin_id integer AFTER fk_mo;
ALTER TABLE llx_mrp_production ADD COLUMN origin_type varchar(10) AFTER origin_id;

ALTER TABLE llx_fichinter ADD COLUMN last_main_doc varchar(255) AFTER model_pdf;
ALTER TABLE llx_projet ADD COLUMN last_main_doc varchar(255) AFTER model_pdf;
ALTER TABLE llx_expensereport ADD COLUMN last_main_doc varchar(255) DEFAULT NULL AFTER model_pdf;

create table llx_payment_vat
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_tva          integer,
  datec           datetime,           -- date de creation
  tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datep           datetime,           -- payment date
  amount          double(24,8) DEFAULT 0,
  fk_typepaiement integer NOT NULL,
  num_paiement    varchar(50),
  note            text,
  fk_bank         integer NOT NULL,
  fk_user_creat   integer,            -- creation user
  fk_user_modif   integer             -- last modification user

)ENGINE=innodb;

ALTER TABLE llx_tva ADD COLUMN paye smallint default 1 NOT NULL;
ALTER TABLE llx_tva ADD COLUMN fk_account integer;

INSERT INTO llx_payment_vat (rowid, fk_tva, datec, datep, amount, fk_typepaiement, num_paiement, note, fk_bank, fk_user_creat, fk_user_modif) SELECT rowid, rowid, NOW(), datep, amount, COALESCE(fk_typepayment, 0), num_payment, 'Created automatically by migration v13 to v14', fk_bank, fk_user_creat, fk_user_modif FROM llx_tva WHERE fk_bank IS NOT NULL;
--UPDATE llx_bank_url as url INNER JOIN llx_tva tva ON tva.rowid = url.url_id SET url.type = 'vat', url.label = CONCAT('(', tva.label, ')') WHERE type = 'payment_vat';
--INSERT INTO llx_bank_url (fk_bank, url_id, url, label, type) SELECT b.fk_bank, ptva.rowid, REPLACE(b.url, 'tva/card.php', 'payment_vat/card.php'), '(paiement)', 'payment_vat' FROM llx_bank_url b INNER JOIN llx_tva tva ON (tva.fk_bank = b.fk_bank) INNER JOIN llx_payment_vat ptva on (ptva.fk_bank = b.fk_bank) WHERE type = 'vat';

--ALTER TABLE llx_tva DROP COLUMN fk_bank;

ALTER TABLE llx_tva ALTER COLUMN paye SET DEFAULT 0;


-- Event organization 
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationEmailAskConf)',       10, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailAskConf)__', '__(Hello)__,<br /><br />__(OrganizationEventConfRequestWasReceived)__<br /><br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationEmailAskBooth)',      20, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailAskBooth)__', '__(Hello)__,<br /><br />__(OrganizationEventBoothRequestWasReceived)__<br /><br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
-- TODO Add message for registration only to event  __ONLINE_PAYMENT_TEXT_AND_URL__
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationEmailSubsBooth)',     30, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailBoothPayment)__', '__(Hello)__,<br /><br />__(OrganizationEventPaymentOfBoothWasReceived)__<br /><br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationEmailSubsEvent)',     40, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailRegistrationPayment)__', '__(Hello)__,<br /><br />__(OrganizationEventPaymentOfRegistrationWasReceived)__<br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationMassEmailAttendees)', 50, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationMassEmailAttendees)__', '__(Hello)__,<br /><br />__(OrganizationEventBulkMailToAttendees)__<br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationMassEmailSpeakers)',  60, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationMassEmailSpeakers)__', '__(Hello)__,<br /><br />__(OrganizationEventBulkMailToSpeakers)__<br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);

ALTER TABLE llx_projet ADD COLUMN accept_conference_suggestions integer DEFAULT 0;
ALTER TABLE llx_projet ADD COLUMN accept_booth_suggestions integer DEFAULT 0;
ALTER TABLE llx_projet ADD COLUMN price_registration double(24,8);
ALTER TABLE llx_projet ADD COLUMN price_booth double(24,8);

ALTER TABLE llx_actioncomm ADD COLUMN num_vote integer DEFAULT NULL AFTER reply_to;
ALTER TABLE llx_actioncomm ADD COLUMN event_paid smallint NOT NULL DEFAULT 0 AFTER num_vote;
ALTER TABLE llx_actioncomm ADD COLUMN status smallint NOT NULL DEFAULT 0 AFTER event_paid;
ALTER TABLE llx_actioncomm ADD COLUMN ref varchar(30) AFTER id;
UPDATE llx_actioncomm SET ref = id WHERE ref = '' OR ref IS NULL;
ALTER TABLE llx_actioncomm MODIFY COLUMN ref varchar(30) NOT NULL;
ALTER TABLE llx_actioncomm ADD UNIQUE INDEX uk_actioncomm_ref (ref, entity);

ALTER TABLE llx_c_actioncomm MODIFY code varchar(50) NOT NULL;
ALTER TABLE llx_c_actioncomm MODIFY module varchar(50) DEFAULT NULL;

INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, position) VALUES ( 60,'AC_EO_ONLINECONF','module','Online/Virtual conference','conference@eventorganization', 1, 60);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, position) VALUES ( 61,'AC_EO_INDOORCONF','module','Indoor conference','conference@eventorganization', 1, 61);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, position) VALUES ( 62,'AC_EO_ONLINEBOOTH','module','Online/Virtual booth','booth@eventorganization', 1, 62);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, position) VALUES ( 63,'AC_EO_INDOORBOOTH','module','Indoor booth','booth@eventorganization', 1, 63);
-- Code enhanced - Standardize field name
ALTER TABLE llx_commande CHANGE COLUMN tva total_tva double(24,8) default 0;
ALTER TABLE llx_supplier_proposal CHANGE COLUMN tva total_tva double(24,8) default 0;
ALTER TABLE llx_supplier_proposal CHANGE COLUMN total total_ttc double(24,8) default 0;
ALTER TABLE llx_propal CHANGE COLUMN tva total_tva double(24,8) default 0;
ALTER TABLE llx_propal CHANGE COLUMN total total_ttc double(24,8) default 0;
ALTER TABLE llx_facture CHANGE COLUMN tva total_tva double(24,8) default 0;
ALTER TABLE llx_facture CHANGE COLUMN total total_ht double(24,8) default 0;
ALTER TABLE llx_facture_rec CHANGE COLUMN tva total_tva double(24,8) default 0;
ALTER TABLE llx_facture_rec CHANGE COLUMN total total_ht double(24,8) default 0;
ALTER TABLE llx_commande_fournisseur CHANGE COLUMN tva total_tva double(24,8) default 0;


--VMYSQL4.3 ALTER TABLE llx_c_civility CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
--VPGSQL8.2 CREATE SEQUENCE llx_c_civility_rowid_seq OWNED BY llx_c_civility.rowid;
--VPGSQL8.2 ALTER TABLE llx_c_civility ALTER COLUMN rowid SET DEFAULT nextval('llx_c_civility_rowid_seq');
--VPGSQL8.2 SELECT setval('llx_c_civility_rowid_seq', MAX(rowid)) FROM llx_c_civility;


-- Change for salary intent table
create table llx_salary
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  ref             varchar(30) NULL,           -- payment reference number (currently NULL because there is no numbering manager yet)
  label           varchar(255),
  tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datec           datetime,                   -- Create date
  fk_user         integer NOT NULL,
  datep           date,                       -- payment date
  datev           date,                       -- value date (this field should not be here, only into bank tables)
  salary          double(24,8),               -- salary of user when payment was done
  amount          double(24,8) NOT NULL DEFAULT 0,
  fk_projet       integer DEFAULT NULL,
  datesp          date,                       -- date start period
  dateep          date,                       -- date end period
  entity          integer DEFAULT 1 NOT NULL, -- multi company id
  note            text,
  fk_bank         integer,
  paye            smallint default 1 NOT NULL,
  fk_typepayment  integer NOT NULL,			  -- default payment mode for payment
  fk_account      integer,					  -- default bank account for payment
  fk_user_author  integer,                    -- user creating
  fk_user_modif   integer                     -- user making last change
) ENGINE=innodb;

-- VMYSQL4.1 ALTER TABLE llx_payment_salary CHANGE COLUMN fk_user fk_user integer NULL;
-- VPGSQL8.2 ALTER TABLE llx_payment_salary ALTER COLUMN fk_user DROP NOT NULL;
ALTER TABLE llx_payment_salary ADD COLUMN fk_salary integer;

INSERT INTO llx_salary (rowid, ref, fk_user, amount, fk_projet, fk_typepayment, label, datesp, dateep, entity, note, fk_bank, paye) SELECT ps.rowid, ps.rowid, ps.fk_user, ps.amount, ps.fk_projet, ps.fk_typepayment, ps.label, ps.datesp, ps.dateep, ps.entity, ps.note, ps.fk_bank, 1 FROM llx_payment_salary ps WHERE ps.fk_salary IS NULL;
UPDATE llx_payment_salary SET fk_salary = rowid WHERE fk_salary IS NULL;
UPDATE llx_payment_salary SET ref = rowid WHERE ref IS NULL;

ALTER TABLE llx_salary ALTER COLUMN paye set default 0;

UPDATE llx_extrafields SET elementtype = 'salary' WHERE elementtype = 'payment_salary';
ALTER TABLE llx_payment_salary_extrafields RENAME TO llx_salary_extrafields;
-- VMYSQL4.1 DROP INDEX idx_payment_salary_extrafields on llx_salary_extrafields;
-- VPGSQL8.2 DROP INDEX idx_payment_salary_extrafields;
ALTER TABLE llx_salary_extrafields ADD INDEX idx_salary_extrafields (fk_object);


DELETE FROM llx_boxes WHERE box_id IN (SELECT rowid FROM llx_boxes_def WHERE file IN ('box_graph_ticket_by_severity', 'box_ticket_by_severity.php', 'box_nb_ticket_last_x_days.php', 'box_nb_tickets_type.php', 'box_new_vs_close_ticket.php'));
DELETE FROM llx_boxes_def WHERE file IN ('box_graph_ticket_by_severity', 'box_ticket_by_severity.php', 'box_nb_ticket_last_x_days.php', 'box_nb_tickets_type.php', 'box_new_vs_close_ticket.php');

-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_graph_ticket_by_severity.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_graph_ticket_by_severity.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_graph_nb_ticket_last_x_days.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_graph_nb_ticket_last_x_days.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_graph_nb_tickets_type.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_graph_nb_tickets_type.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_graph_new_vs_close_ticket.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_graph_new_vs_close_ticket.php' AND entity = 1);

create table llx_product_perentity
(
    rowid         				integer AUTO_INCREMENT PRIMARY KEY,
    fk_product	   				integer,
    entity             			integer DEFAULT 1 NOT NULL,      	-- multi company id
    accountancy_code_sell         varchar(32),                        -- Selling accountancy code
    accountancy_code_sell_intra   varchar(32),                        -- Selling accountancy code for vat intracommunity
    accountancy_code_sell_export  varchar(32),                        -- Selling accountancy code for vat export
    accountancy_code_buy          varchar(32),                        -- Buying accountancy code
    accountancy_code_buy_intra    varchar(32),                        -- Buying accountancy code for vat intracommunity
    accountancy_code_buy_export   varchar(32),                     	  -- Buying accountancy code for vat import
    pmp double(24,8)
)ENGINE=innodb;

ALTER TABLE llx_product_perentity ADD INDEX idx_product_perentity_fk_product (fk_product);
ALTER TABLE llx_product_perentity ADD UNIQUE INDEX uk_product_perentity (fk_product, entity);

create table llx_societe_perentity
(
    rowid         			integer AUTO_INCREMENT PRIMARY KEY,
    fk_soc        			integer,
    entity             		integer DEFAULT 1 NOT NULL,             -- multi company id
--  code_compta            	varchar(24),                         	-- code compta client
--  code_compta_fournisseur varchar(24),                         	-- code compta founisseur
    accountancy_code_sell		varchar(32),                            -- Selling accountancy code
    accountancy_code_buy		varchar(32)                             -- Buying accountancy code
)ENGINE=innodb;

ALTER TABLE llx_societe_perentity ADD INDEX idx_societe_perentity_fk_soc (fk_soc);
ALTER TABLE llx_societe_perentity ADD UNIQUE INDEX uk_societe_perentity (fk_soc, entity);

CREATE TABLE llx_eventorganization_conferenceorboothattendee(
    rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    ref varchar(128) NOT NULL,
    fk_soc integer,
    fk_actioncomm integer,
    fk_project integer NOT NULL,
    fk_invoice integer NULL,
    email varchar(100),
    date_subscription datetime,
    amount double DEFAULT NULL,
    note_public text,
    note_private text,
    date_creation datetime NOT NULL,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat integer,
    fk_user_modif integer,
    last_main_doc varchar(255),
    import_key varchar(14),
    model_pdf varchar(255),
    status smallint NOT NULL
) ENGINE=innodb;

-- VMYSQL4.3 ALTER TABLE llx_eventorganization_conferenceorboothattendee MODIFY COLUMN fk_actioncomm integer NULL;
-- VPGSQL8.2 ALTER TABLE llx_eventorganization_conferenceorboothattendee ALTER COLUMN fk_actioncomm DROP NOT NULL;

ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD COLUMN fk_project integer NOT NULL;
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD COLUMN fk_invoice integer NULL;

ALTER TABLE llx_eventorganization_conferenceorboothattendee DROP FOREIGN KEY fx_eventorganization_conferenceorboothattendee_fk_soc;
ALTER TABLE llx_eventorganization_conferenceorboothattendee DROP FOREIGN KEY fx_eventorganization_conferenceorboothattendee_fk_actioncomm;
ALTER TABLE llx_eventorganization_conferenceorboothattendee DROP FOREIGN KEY fx_eventorganization_conferenceorboothattendee_fk_project;

ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD INDEX idx_eventorganization_conferenceorboothattendee_rowid (rowid);
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD INDEX idx_eventorganization_conferenceorboothattendee_ref (ref);
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD INDEX idx_eventorganization_conferenceorboothattendee_fk_soc (fk_soc);
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD INDEX idx_eventorganization_conferenceorboothattendee_fk_actioncomm (fk_actioncomm);
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD INDEX idx_eventorganization_conferenceorboothattendee_email (email);
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD INDEX idx_eventorganization_conferenceorboothattendee_status (status);

-- VMYSQL4.1 DROP INDEX uk_eventorganization_conferenceorboothattendee on llx_eventorganization_conferenceorboothattendee;
-- VPGSQL8.2 DROP INDEX uk_eventorganization_conferenceorboothattendee;

ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD UNIQUE INDEX uk_eventorganization_conferenceorboothattendee(fk_project, email, fk_actioncomm);


create table llx_eventorganization_conferenceorboothattendee_extrafields
(
    rowid                     integer AUTO_INCREMENT PRIMARY KEY,
    tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_object                 integer NOT NULL,
    import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_eventorganization_conferenceorboothattendee_extrafields ADD INDEX idx_conferenceorboothattendee_fk_object(fk_object);

ALTER TABLE llx_c_ticket_category ADD COLUMN public integer DEFAULT 0;

-- VPGSQL8.2 ALTER TABLE llx_c_ticket_category ALTER COLUMN pos TYPE INTEGER USING pos::INTEGER;
-- VPGSQL8.2 ALTER TABLE llx_c_ticket_category ALTER COLUMN pos SET NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_c_ticket_category ALTER COLUMN pos SET DEFAULT 0;
ALTER TABLE llx_c_ticket_category MODIFY COLUMN pos	integer DEFAULT 0 NOT NULL;


ALTER TABLE llx_propal ADD COLUMN date_signature datetime AFTER date_valid;
ALTER TABLE llx_propal ADD COLUMN fk_user_signature integer AFTER fk_user_valid;
ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_user_signature FOREIGN KEY (fk_user_signature) REFERENCES llx_user (rowid);

UPDATE llx_propal SET fk_user_signature = fk_user_cloture WHERE fk_user_signature IS NULL AND fk_user_cloture IS NOT NULL;
UPDATE llx_propal SET date_signature = date_cloture WHERE date_signature IS NULL AND date_cloture IS NOT NULL;


ALTER TABLE llx_product ADD COLUMN batch_mask VARCHAR(32) DEFAULT NULL;
ALTER TABLE llx_product ADD COLUMN lifetime INTEGER NULL;
ALTER TABLE llx_product ADD COLUMN qc_frequency INTEGER NULL;

insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (210, 'conferenceorbooth', 'internal', 'MANAGER',  'Conference or Booth manager', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (211, 'conferenceorbooth', 'external', 'SPEAKER',   'Conference Speaker', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (212, 'conferenceorbooth', 'external', 'RESPONSIBLE',   'Booth responsible', 1);


CREATE TABLE llx_partnership(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) DEFAULT '(PROV)' NOT NULL, 
	status smallint NOT NULL DEFAULT '0', 
	fk_soc integer, 
	fk_member integer, 
	date_partnership_start date NOT NULL, 
	date_partnership_end date NULL, 
	entity integer	DEFAULT 1 NOT NULL,	-- multi company id, 0 = all
	reason_decline_or_cancel text NULL,
	date_creation datetime NOT NULL, 
	fk_user_creat integer NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_modif integer, 
	note_private text, 
	note_public text, 
	last_main_doc varchar(255), 
	count_last_url_check_error integer DEFAULT '0',
	last_check_backlink datetime NULL,
	import_key varchar(14),
	model_pdf varchar(255)
) ENGINE=innodb;

ALTER TABLE llx_partnership ADD COLUMN last_check_backlink datetime NULL;

ALTER TABLE llx_partnership ADD INDEX idx_partnership_rowid (rowid);
ALTER TABLE llx_partnership ADD INDEX idx_partnership_ref (ref);
ALTER TABLE llx_partnership ADD INDEX idx_partnership_fk_soc (fk_soc);
ALTER TABLE llx_partnership ADD CONSTRAINT llx_partnership_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_partnership ADD INDEX idx_partnership_status (status);
ALTER TABLE llx_partnership ADD INDEX idx_partnership_fk_member (fk_member);

create table llx_partnership_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_partnership_extrafields ADD INDEX idx_partnership_fk_object(fk_object);

INSERT INTO llx_c_email_templates (entity,module,type_template,label,lang,position,topic,joinfiles,content) VALUES (0, 'partnership', 'partnership_send', '(SendingEmailOnPartnershipWillSoonBeCanceled)', '', 100, '[__[MAIN_INFO_SOCIETE_NOM]__] - __(YourPartnershipWillSoonBeCanceledTopic)__', 0, '<body>\n <p>Hello,<br><br>\n__(YourPartnershipWillSoonBeCanceledContent)__</p>\n<br />\n\n<br />\n\n            __(Sincerely)__ <br />\n            __[MAIN_INFO_SOCIETE_NOM]__ <br />\n </body>\n');
INSERT INTO llx_c_email_templates (entity,module,type_template,label,lang,position,topic,joinfiles,content) VALUES (0, 'partnership', 'partnership_send', '(SendingEmailOnPartnershipCanceled)', '', 100, '[__[MAIN_INFO_SOCIETE_NOM]__] - __(YourPartnershipCanceledTopic)__', 0, '<body>\n <p>Hello,<br><br>\n__(YourPartnershipCanceledContent)__</p>\n<br />\n\n<br />\n\n            __(Sincerely)__ <br />\n            __[MAIN_INFO_SOCIETE_NOM]__ <br />\n </body>\n');
INSERT INTO llx_c_email_templates (entity,module,type_template,label,lang,position,topic,joinfiles,content) VALUES (0, 'partnership', 'partnership_send', '(SendingEmailOnPartnershipRefused)', '', 100, '[__[MAIN_INFO_SOCIETE_NOM]__] - __(YourPartnershipRefusedTopic)__', 0, '<body>\n <p>Hello,<br><br>\n__(YourPartnershipRefusedContent)__</p>\n<br />\n\n<br />\n\n            __(Sincerely)__ <br />\n            __[MAIN_INFO_SOCIETE_NOM]__ <br />\n </body>\n');
INSERT INTO llx_c_email_templates (entity,module,type_template,label,lang,position,topic,joinfiles,content) VALUES (0, 'partnership', 'partnership_send', '(SendingEmailOnPartnershipAccepted)', '', 100, '[__[MAIN_INFO_SOCIETE_NOM]__] - __(YourPartnershipAcceptedTopic)__', 0, '<body>\n <p>Hello,<br><br>\n__(YourPartnershipAcceptedContent)__</p>\n<br />\n\n<br />\n\n            __(Sincerely)__ <br />\n            __[MAIN_INFO_SOCIETE_NOM]__ <br />\n </body>\n');
ALTER TABLE llx_adherent ADD COLUMN url varchar(255) NULL AFTER email;
ALTER TABLE llx_facture_fourn ADD COLUMN date_closing datetime DEFAULT NULL after date_valid;

ALTER TABLE llx_facture_fourn ADD COLUMN fk_user_closing integer DEFAULT NULL after fk_user_valid;

ALTER TABLE llx_entrepot ADD COLUMN fk_project INTEGER DEFAULT NULL AFTER entity; -- project associated to warehouse if any

-- Add external payment support for donation
ALTER TABLE llx_payment_donation ADD COLUMN ext_payment_site  varchar(128) AFTER note;
ALTER TABLE llx_payment_donation ADD COLUMN ext_payment_id  varchar(128) AFTER note;

-- Rebuild sequence for postgres only after query INSERT INTO llx_salary(rowid, ...
-- VPGSQL8.2 SELECT dol_util_rebuild_sequences();

UPDATE llx_const SET type = 'chaine', value = __ENCRYPT('github')__ WHERE __DECRYPT('name')__ = 'MAIN_BUGTRACK_ENABLELINK' AND __DECRYPT('value')__ = '1';

ALTER TABLE llx_facture_fourn_det ADD COLUMN fk_remise_except integer DEFAULT NULL after remise_percent;
ALTER TABLE llx_facture_fourn_det ADD UNIQUE INDEX uk_fk_remise_except (fk_remise_except, fk_facture_fourn);

CREATE TABLE llx_knowledgemanagement_knowledgerecord(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) NOT NULL, 
	date_creation datetime NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	last_main_doc varchar(255), 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	fk_user_valid integer, 
	import_key varchar(14), 
	model_pdf varchar(255), 
	question text NOT NULL, 
	answer text,
	url varchar(255),
	fk_ticket integer,
	fk_c_ticket_category integer,
	status integer NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE llx_knowledgemanagement_knowledgerecord ADD COLUMN fk_ticket integer;
ALTER TABLE llx_knowledgemanagement_knowledgerecord ADD COLUMN fk_c_ticket_category integer;
ALTER TABLE llx_knowledgemanagement_knowledgerecord ADD COLUMN url varchar(255);


create table llx_knowledgemanagement_knowledgerecord_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

-- add default amount by member type
ALTER TABLE llx_adherent_type ADD COLUMN amount DOUBLE(24,8) NULL DEFAULT NULL AFTER subscription;

-- add action trigger
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('COMPANY_MODIFY','Third party update','Executed when you update third party','societe',1);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('CONTACT_MODIFY','Contact address update','Executed when a contact is updated','contact',51);


create table llx_c_partnership_type
(
  rowid      	integer AUTO_INCREMENT PRIMARY KEY,
  entity        integer DEFAULT 1 NOT NULL,
  code          varchar(32) NOT NULL,
  label 	    varchar(64)	NOT NULL,
  active  	    tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

DELETE FROM llx_rights_def WHERE module = 'hrm' AND perms = 'employee';


CREATE TABLE llx_ecm_directories_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                             -- import key
) ENGINE=innodb;

DROP TABLE llx_categorie_association;
DROP TABLE llx_cond_reglement;
DROP TABLE llx_zapier_hook_extrafields;

CREATE TABLE llx_onlinesignature
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  entity                    integer DEFAULT 1 NOT NULL,
  object_type               varchar(32) NOT NULL,
  object_id					integer NOT NULL,
  datec                     datetime NOT NULL,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  name						varchar(255) NOT NULL,
  ip						varchar(128),
  pathoffile				varchar(255)
)ENGINE=innodb;

-- VMYSQL4.3 ALTER TABLE llx_partnership MODIFY COLUMN date_partnership_end date NULL;
-- VPGSQL8.2 ALTER TABLE llx_partnership ALTER COLUMN date_partnership_end DROP NOT NULL;

ALTER TABLE llx_facture_fourn CHANGE COLUMN fk_mode_transport fk_transport_mode integer;

ALTER TABLE llx_c_socialnetworks DROP INDEX idx_c_socialnetworks_code;
ALTER TABLE llx_c_socialnetworks ADD UNIQUE INDEX idx_c_socialnetworks_code_entity (code, entity);

ALTER TABLE llx_propaldet ADD COLUMN import_key varchar(14);
