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
-- To drop an index:        -- VMYSQL4.1 DROP INDEX nomindex on llx_table
-- To drop an index:        -- VPGSQL8.2 DROP INDEX nomindex
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


-- Missing in v13 or lower

ALTER TABLE llx_asset CHANGE COLUMN amount amount_ht double(24,8) DEFAULT NULL;
ALTER TABLE llx_asset ADD COLUMN amount_vat double(24,8) DEFAULT NULL;

ALTER TABLE llx_supplier_proposal_extrafields ADD INDEX idx_supplier_proposal_extrafields (fk_object);
ALTER TABLE llx_supplier_proposaldet_extrafields ADD INDEX idx_supplier_proposaldet_extrafields (fk_object);

ALTER TABLE llx_asset_extrafields ADD INDEX idx_asset_extrafields (fk_object);

insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 6,'AC_EMAIL_IN','system','reception Email',NULL, 1, 4);

-- VMYSQL4.3 ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN montant double(24,8) NULL;
-- VPGSQL8.2 ALTER TABLE llx_accounting_bookkeeping ALTER COLUMN montant DROP NOT NULL;

UPDATE llx_c_country SET eec = 1 WHERE code IN ('AT','BE','BG','CY','CZ','DE','DK','EE','ES','FI','FR','GR','HR','NL','HU','IE','IM','IT','LT','LU','LV','MC','MT','PL','PT','RO','SE','SK','SI');


-- For v14

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
	tms timestamp,
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
	tms timestamp,
	fk_resource integer,
	fk_workstation integer
) ENGINE=innodb;

CREATE TABLE llx_workstation_workstation_usergroup(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	tms timestamp,
	fk_usergroup integer,
	fk_workstation integer
) ENGINE=innodb;


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

create table llx_payment_vat
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_tva          integer,
  datec           datetime,           -- date de creation
  tms             timestamp,
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


INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, tms, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'eventorganization_send', '', 0, null, null, '2021-02-14 14:42:41', 'EventOrganizationEmailAskConf', 10, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailAskConf)__', '__(Hello)__ __THIRDPARTY_NAME__,<br /><br />__(ThisIsContentOfYourOrganizationEventConfRequestWasReceived)__<br /><br />__ONLINE_PAYMENT_TEXT_AND_URL__<br /><br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, tms, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'eventorganization_send', '', 0, null, null, '2021-02-14 14:42:41', 'EventOrganizationEmailAskBooth', 20, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailAskBooth)__', '__(Hello)__ __THIRDPARTY_NAME__,<br /><br />__(ThisIsContentOfYourOrganizationEventBoothRequestWasReceived)__<br /><br />__ONLINE_PAYMENT_TEXT_AND_URL__<br /><br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, tms, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'eventorganization_send', '', 0, null, null, '2021-02-14 14:42:41', 'EventOrganizationEmailSubsBooth', 30, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailSubsBooth)__', '__(Hello)__ __THIRDPARTY_NAME__,<br /><br />__(ThisIsContentOfYourOrganizationEventBoothSubscriptionWasReceived)__<br /><br />__ONLINE_PAYMENT_TEXT_AND_URL__<br /><br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, tms, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'eventorganization_send', '', 0, null, null, '2021-02-14 14:42:41', 'EventOrganizationEmailSubsEvent', 40, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailSubsEvent)__', '__(Hello)__ __THIRDPARTY_NAME__,<br /><br />__(ThisIsContentOfYourOrganizationEventEventSubscriptionWasReceived)__<br /><br />__ONLINE_PAYMENT_TEXT_AND_URL__<br /><br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, tms, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'eventorganization_send', '', 0, null, null, '2021-02-14 14:42:41', 'EventOrganizationMassEmailAttendes', 50, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationMassEmailAttendes)__', '__(Hello)__ __THIRDPARTY_NAME__,<br /><br />__(ThisIsContentOfYourOrganizationEventBulkMailToAttendees)__<br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, tms, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'eventorganization_send', '', 0, null, null, '2021-02-14 14:42:41', 'EventOrganizationMassEmailSpeakers', 60, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationMassEmailSpeakers)__', '__(Hello)__ __THIRDPARTY_NAME__,<br /><br />__(ThisIsContentOfYourOrganizationEventBulkMailToSpeakers)__<br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);

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
  tms             timestamp,
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

ALTER TABLE llx_payment_salary CHANGE COLUMN fk_user fk_user integer NULL;
ALTER TABLE llx_payment_salary ADD COLUMN fk_salary integer;

INSERT INTO llx_salary (rowid, ref, fk_user, amount, fk_projet, fk_typepayment, label, datesp, dateep, entity, note, fk_bank, paye) SELECT ps.rowid, ps.rowid, ps.fk_user, ps.amount, ps.fk_projet, ps.fk_typepayment, ps.label, ps.datesp, ps.dateep, ps.entity, ps.note, ps.fk_bank, 1 FROM llx_payment_salary ps WHERE ps.fk_salary IS NULL;
UPDATE llx_payment_salary as ps SET ps.fk_salary = ps.rowid WHERE ps.fk_salary IS NULL;
UPDATE llx_payment_salary as ps SET ps.ref = ps.rowid WHERE ps.ref IS NULL;

ALTER TABLE llx_salary CHANGE paye paye smallint default 0 NOT NULL;


DELETE FROM llx_boxes WHERE box_id IN (SELECT rowid FROM llx_boxes_def WHERE file IN ('box_graph_ticket_by_severity', 'box_ticket_by_severity.php', 'box_nb_ticket_last_x_days.php', 'box_nb_tickets_type.php', 'box_new_vs_close_ticket.php'));
DELETE FROM llx_boxes_def WHERE file IN ('box_graph_ticket_by_severity', 'box_ticket_by_severity.php', 'box_nb_ticket_last_x_days.php', 'box_nb_tickets_type.php', 'box_new_vs_close_ticket.php');

-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_graph_ticket_by_severity.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_graph_ticket_by_severity.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_graph_nb_ticket_last_x_days.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_graph_nb_ticket_last_x_days.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_graph_nb_tickets_type.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_graph_nb_tickets_type.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_graph_new_vs_close_ticket.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_graph_new_vs_close_ticket.php' AND entity = 1);
