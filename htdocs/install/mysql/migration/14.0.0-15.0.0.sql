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


-- Missing in v14 or lower

-- VMYSQL4.3 ALTER TABLE llx_partnership MODIFY COLUMN date_partnership_end date NULL;
-- VPGSQL8.2 ALTER TABLE llx_partnership ALTER COLUMN date_partnership_end DROP NOT NULL;

ALTER TABLE llx_accounting_bookkeeping ADD COLUMN date_export datetime DEFAULT NULL;

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


-- VMYSQL4.3 ALTER TABLE llx_eventorganization_conferenceorboothattendee MODIFY COLUMN fk_actioncomm integer NULL;
-- VPGSQL8.2 ALTER TABLE llx_eventorganization_conferenceorboothattendee ALTER COLUMN fk_actioncomm DROP NOT NULL;

ALTER TABLE llx_mrp_mo ADD COLUMN last_main_doc varchar(255);

UPDATE llx_extrafields SET elementtype = 'salary' WHERE elementtype = 'payment_salary';
ALTER TABLE llx_payment_salary_extrafields RENAME TO llx_salary_extrafields;
-- VMYSQL4.1 DROP INDEX idx_payment_salary_extrafields on llx_salary_extrafields;
-- VPGSQL8.2 DROP INDEX idx_payment_salary_extrafields;
ALTER TABLE llx_salary_extrafields ADD INDEX idx_salary_extrafields (fk_object);


INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationEmailAskConf)',       10, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailAskConf)__', '__(Hello)__,<br /><br />__(OrganizationEventConfRequestWasReceived)__<br /><br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationEmailAskBooth)',      20, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailAskBooth)__', '__(Hello)__,<br /><br />__(OrganizationEventBoothRequestWasReceived)__<br /><br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
-- TODO Add message for registration only to event  __ONLINE_PAYMENT_TEXT_AND_URL__
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationEmailSubsBooth)',     30, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailBoothPayment)__', '__(Hello)__,<br /><br />__(OrganizationEventPaymentOfBoothWasReceived)__<br /><br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationEmailSubsEvent)',     40, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailRegistrationPayment)__', '__(Hello)__,<br /><br />__(OrganizationEventPaymentOfRegistrationWasReceived)__<br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationMassEmailAttendees)', 50, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationMassEmailAttendees)__', '__(Hello)__,<br /><br />__(OrganizationEventBulkMailToAttendees)__<br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationMassEmailSpeakers)',  60, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationMassEmailSpeakers)__', '__(Hello)__,<br /><br />__(OrganizationEventBulkMailToSpeakers)__<br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);


-- v15

ALTER TABLE llx_c_holiday_types CHANGE COLUMN newByMonth newbymonth double(8,5) DEFAULT 0 NOT NULL;

ALTER TABLE llx_product ADD COLUMN mandatory_period tinyint NULL DEFAULT 0;

ALTER TABLE llx_holiday ADD COLUMN date_approve   DATETIME DEFAULT NULL;
ALTER TABLE llx_holiday ADD COLUMN fk_user_approve integer DEFAULT NULL;

ALTER TABLE llx_ticket MODIFY COLUMN progress integer;


ALTER TABLE llx_emailcollector_emailcollectoraction MODIFY COLUMN actionparam TEXT;

ALTER TABLE llx_knowledgemanagement_knowledgerecord ADD COLUMN lang varchar(6);
ALTER TABLE llx_knowledgemanagement_knowledgerecord ADD COLUMN entity integer DEFAULT 1;

CREATE TABLE llx_categorie_ticket
(
  fk_categorie  integer NOT NULL,
  fk_ticket    integer NOT NULL,
  import_key    varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_categorie_ticket ADD PRIMARY KEY pk_categorie_ticket (fk_categorie, fk_ticket);
ALTER TABLE llx_categorie_ticket ADD INDEX idx_categorie_ticket_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_ticket ADD INDEX idx_categorie_ticket_fk_ticket (fk_ticket);

ALTER TABLE llx_categorie_ticket ADD CONSTRAINT fk_categorie_ticket_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_ticket ADD CONSTRAINT fk_categorie_ticket_ticket_rowid   FOREIGN KEY (fk_ticket) REFERENCES llx_ticket (rowid);
ALTER TABLE llx_product_fournisseur_price MODIFY COLUMN ref_fourn varchar(128);
ALTER TABLE llx_product_customer_price MODIFY COLUMN ref_customer varchar(128);
ALTER TABLE llx_product_association ADD COLUMN rang integer DEFAULT 0;

-- -- add action trigger
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('ORDER_SUPPLIER_CANCEL','Supplier order request canceled','Executed when a supplier order is canceled','order_supplier',13);

ALTER TABLE llx_product ADD COLUMN fk_default_bom integer DEFAULT NULL;

ALTER TABLE llx_mrp_mo ADD COLUMN mrptype integer DEFAULT 0;

DELETE FROM llx_menu WHERE type = 'top' AND module = 'cashdesk' AND mainmenu = 'cashdesk';

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang) values ('MEMBER_EXCLUDE', 'Member excluded', 'Executed when a member is excluded', 'member', 27);

CREATE TABLE llx_categorie_knowledgemanagement
(
  fk_categorie  integer NOT NULL,
  fk_knowledgemanagement    integer NOT NULL,
  import_key    varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_categorie_knowledgemanagement ADD PRIMARY KEY pk_categorie_knowledgemanagement (fk_categorie, fk_knowledgemanagement);
ALTER TABLE llx_categorie_knowledgemanagement ADD INDEX idx_categorie_knowledgemanagement_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_knowledgemanagement ADD INDEX idx_categorie_knowledgemanagement_fk_knowledgemanagement (fk_knowledgemanagement);

ALTER TABLE llx_categorie_knowledgemanagement ADD CONSTRAINT fk_categorie_knowledgemanagement_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_knowledgemanagement ADD CONSTRAINT fk_categorie_knowledgemanagement_knowledgemanagement_rowid   FOREIGN KEY (fk_knowledgemanagement) REFERENCES llx_knowledgemanagement_knowledgerecord (rowid);

ALTER TABLE llx_product_lot ADD COLUMN barcode varchar(180) DEFAULT NULL;
ALTER TABLE llx_product_lot ADD COLUMN fk_barcode_type integer DEFAULT NULL;

ALTER TABLE llx_projet ADD COLUMN max_attendees integer DEFAULT 0;

ALTER TABLE llx_commande_fournisseur_dispatch ADD COLUMN cost_price double(24,8) DEFAULT 0;

INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2001', 'Aktiebolag');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2002', 'Publikt aktiebolag (AB publ)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2003', 'Ekonomisk förening (ek. för.)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2004', 'Bostadsrättsförening (BRF)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2005', 'Hyresrättsförening (HRF)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2006', 'Kooperativ');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2007', 'Enskild firma (EF)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2008', 'Handelsbolag (HB)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2009', 'Kommanditbolag (KB)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2010', 'Enkelt bolag');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2011', 'Ideell förening');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2012', 'Stiftelse');

-- START  GRH/HRM MODULE


CREATE TABLE llx_hrm_evaluation
(
    rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    ref varchar(128) DEFAULT '(PROV)' NOT NULL,
    label varchar(255),
    description text,
    note_public text,
    note_private text,
    date_creation datetime NOT NULL,
    tms timestamp,
    fk_user_creat integer NOT NULL,
    fk_user_modif integer,
    import_key varchar(14),
    status smallint NOT NULL,
    date_eval date,
    fk_user integer NOT NULL,
    fk_job integer NOT NULL
) ENGINE=innodb;
ALTER TABLE llx_hrm_evaluation ADD INDEX idx_hrm_evaluation_rowid (rowid);
ALTER TABLE llx_hrm_evaluation ADD INDEX idx_hrm_evaluation_ref (ref);
ALTER TABLE llx_hrm_evaluation ADD CONSTRAINT llx_hrm_evaluation_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_hrm_evaluation ADD INDEX idx_hrm_evaluation_status (status);


create table llx_hrm_evaluation_extrafields
(
    rowid                     integer AUTO_INCREMENT PRIMARY KEY,
    tms                       timestamp,
    fk_object                 integer NOT NULL,
    import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_hrm_evaluation_extrafields ADD INDEX idx_evaluation_fk_object(fk_object);


CREATE TABLE llx_hrm_evaluationdet
(
    -- BEGIN MODULEBUILDER FIELDS
    rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    date_creation datetime NOT NULL,
    tms timestamp,
    fk_user_creat integer NOT NULL,
    fk_user_modif integer,
    fk_skill integer NOT NULL,
    fk_evaluation integer NOT NULL,
    rankorder integer NOT NULL,
    required_rank integer NOT NULL,
    import_key varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_hrm_evaluationdet ADD INDEX idx_hrm_evaluationdet_rowid (rowid);
ALTER TABLE llx_hrm_evaluationdet ADD CONSTRAINT llx_hrm_evaluationdet_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_hrm_evaluationdet ADD INDEX idx_hrm_evaluationdet_fk_skill (fk_skill);
ALTER TABLE llx_hrm_evaluationdet ADD INDEX idx_hrm_evaluationdet_fk_evaluation (fk_evaluation);


create table llx_hrm_evaluationdet_extrafields
(
    rowid                     integer AUTO_INCREMENT PRIMARY KEY,
    tms                       timestamp,
    fk_object                 integer NOT NULL,
    import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_hrm_evaluationdet_extrafields ADD INDEX idx_evaluationdet_fk_object(fk_object);



CREATE TABLE llx_hrm_job
(
    rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    label varchar(255) NOT NULL,
    description text,
    date_creation datetime NOT NULL,
    tms timestamp,
    deplacement varchar(255),
    note_public text,
    note_private text,
    fk_user_creat integer,
    fk_user_modif integer
) ENGINE=innodb;

ALTER TABLE llx_hrm_job ADD INDEX idx_hrm_job_rowid (rowid);
ALTER TABLE llx_hrm_job ADD INDEX idx_hrm_job_label (label);


create table llx_hrm_job_extrafields
(
    rowid                     integer AUTO_INCREMENT PRIMARY KEY,
    tms                       timestamp,
    fk_object                 integer NOT NULL,
    import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_hrm_job_extrafields ADD INDEX idx_job_fk_object(fk_object);



CREATE TABLE llx_hrm_job_user(
    rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    -- ref varchar(128) NOT NULL,
    description text,
    date_creation datetime NOT NULL,
    tms timestamp,
    fk_contrat integer,
    fk_user integer NOT NULL,
    fk_job integer NOT NULL,
    date_start date,
    date_end date,
    abort_comment varchar(255),
    note_public text,
    note_private text,
    fk_user_creat integer,
    fk_user_modif integer
) ENGINE=innodb;

ALTER TABLE llx_hrm_job_user ADD INDEX idx_hrm_job_user_rowid (rowid);
-- ALTER TABLE llx_hrm_job_user ADD INDEX idx_hrm_job_user_ref (ref);


create table llx_hrm_job_user_extrafields
(
    rowid                     integer AUTO_INCREMENT PRIMARY KEY,
    tms                       timestamp,
    fk_object                 integer NOT NULL,
    import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_hrm_job_user_extrafields ADD INDEX idx_position_fk_object(fk_object);



CREATE TABLE llx_hrm_skill
(
    rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    label varchar(255),
    description text,
    date_creation datetime NOT NULL,
    tms timestamp,
    fk_user_creat integer NOT NULL,
    fk_user_modif integer,
    required_level integer NOT NULL,
    date_validite integer NOT NULL,
    temps_theorique double(24,8) NOT NULL,
    skill_type integer NOT NULL,
    note_public text,
    note_private text
) ENGINE=innodb;

ALTER TABLE llx_hrm_skill ADD INDEX idx_hrm_skill_rowid (rowid);
ALTER TABLE llx_hrm_skill ADD CONSTRAINT llx_hrm_skill_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_hrm_skill ADD INDEX idx_hrm_skill_skill_type (skill_type);

create table llx_hrm_skill_extrafields
(
    rowid                     integer AUTO_INCREMENT PRIMARY KEY,
    tms                       timestamp,
    fk_object                 integer NOT NULL,
    import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_hrm_skill_extrafields ADD INDEX idx_skill_fk_object(fk_object);


CREATE TABLE llx_hrm_skilldet
(
    rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    description text,
    fk_user_creat integer NOT NULL,
    fk_user_modif integer,
    fk_skill integer NOT NULL,
    rankorder integer
) ENGINE=innodb;

ALTER TABLE llx_hrm_skilldet ADD INDEX idx_hrm_skilldet_rowid (rowid);
ALTER TABLE llx_hrm_skilldet ADD CONSTRAINT llx_hrm_skilldet_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);

create table llx_hrm_skilldet_extrafields
(
    rowid                     integer AUTO_INCREMENT PRIMARY KEY,
    tms                       timestamp,
    fk_object                 integer NOT NULL,
    import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_hrm_skilldet_extrafields ADD INDEX idx_skilldet_fk_object(fk_object);


CREATE TABLE llx_hrm_skillrank
(
    rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    fk_skill integer NOT NULL,
    rankorder integer NOT NULL,
    fk_object integer NOT NULL,
    date_creation datetime NOT NULL,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat integer NOT NULL,
    fk_user_modif integer,
    objecttype varchar(128) NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_hrm_skillrank ADD INDEX idx_hrm_skillrank_rowid (rowid);
ALTER TABLE llx_hrm_skillrank ADD INDEX idx_hrm_skillrank_fk_skill (fk_skill);
ALTER TABLE llx_hrm_skillrank ADD CONSTRAINT llx_hrm_skillrank_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);

--END  GRH/HRM MODULE

ALTER TABLE llx_c_units ADD COLUMN sortorder smallint AFTER code;

-- Manage accountancy auxiliary account for thirdparties per entity
ALTER TABLE llx_societe_perentity ADD COLUMN accountancy_code_customer varchar(24) AFTER entity;    -- equivalent to code_compta in llx_societe
ALTER TABLE llx_societe_perentity ADD COLUMN accountancy_code_supplier varchar(24) AFTER accountancy_code_customer; -- equivalent to code_compta_supplier in llx_societe

ALTER TABLE llx_projet_task ADD COLUMN budget_amount double(24,8) AFTER priority;

-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_graph_ticket_by_severity.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_graph_ticket_by_severity.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_graph_nb_ticket_last_x_days.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_graph_nb_ticket_last_x_days.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_graph_nb_tickets_type.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_graph_nb_tickets_type.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_graph_new_vs_close_ticket.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_graph_new_vs_close_ticket.php' AND entity = 1);

ALTER TABLE llx_user DROP COLUMN jabberid;
ALTER TABLE llx_user DROP COLUMN skype;
ALTER TABLE llx_user DROP COLUMN twitter;
ALTER TABLE llx_user DROP COLUMN facebook;
ALTER TABLE llx_user DROP COLUMN linkedin;
ALTER TABLE llx_user DROP COLUMN instagram;
ALTER TABLE llx_user DROP COLUMN snapchat;
ALTER TABLE llx_user DROP COLUMN googleplus;
ALTER TABLE llx_user DROP COLUMN youtube;
ALTER TABLE llx_user DROP COLUMN whatsapp;

ALTER TABLE llx_adherent DROP COLUMN jabberid;
ALTER TABLE llx_adherent DROP COLUMN skype;
ALTER TABLE llx_adherent DROP COLUMN twitter;
ALTER TABLE llx_adherent DROP COLUMN facebook;
ALTER TABLE llx_adherent DROP COLUMN linkedin;
ALTER TABLE llx_adherent DROP COLUMN instagram;
ALTER TABLE llx_adherent DROP COLUMN snapchat;
ALTER TABLE llx_adherent DROP COLUMN googleplus;
ALTER TABLE llx_adherent DROP COLUMN youtube;
ALTER TABLE llx_adherent DROP COLUMN whatsapp;

ALTER TABLE llx_societe DROP COLUMN jabberid;
ALTER TABLE llx_societe DROP COLUMN skype;
ALTER TABLE llx_societe DROP COLUMN twitter;
ALTER TABLE llx_societe DROP COLUMN facebook;
ALTER TABLE llx_societe DROP COLUMN linkedin;
ALTER TABLE llx_societe DROP COLUMN instagram;
ALTER TABLE llx_societe DROP COLUMN snapchat;
ALTER TABLE llx_societe DROP COLUMN googleplus;
ALTER TABLE llx_societe DROP COLUMN youtube;
ALTER TABLE llx_societe DROP COLUMN whatsapp;

ALTER TABLE llx_socpeople DROP COLUMN jabberid;
ALTER TABLE llx_socpeople DROP COLUMN skype;
ALTER TABLE llx_socpeople DROP COLUMN twitter;
ALTER TABLE llx_socpeople DROP COLUMN facebook;
ALTER TABLE llx_socpeople DROP COLUMN linkedin;
ALTER TABLE llx_socpeople DROP COLUMN instagram;
ALTER TABLE llx_socpeople DROP COLUMN snapchat;
ALTER TABLE llx_socpeople DROP COLUMN googleplus;
ALTER TABLE llx_socpeople DROP COLUMN youtube;
ALTER TABLE llx_socpeople DROP COLUMN whatsapp;

-- ---------------------
-- Assets
-- ---------------------
DROP TABLE llx_asset_type;

ALTER TABLE llx_asset DROP INDEX idx_asset_fk_asset_type;
ALTER TABLE llx_asset DROP FOREIGN KEY fk_asset_asset_type;

ALTER TABLE llx_asset CHANGE COLUMN amount_ht fk_asset_asset_type double(24,8) NOT NULL;
ALTER TABLE llx_asset CHANGE COLUMN amount_vat recovered_vat double(24,8) NOT NULL;
DELETE FROM llx_asset WHERE fk_asset_type IS NOT NULL;
ALTER TABLE llx_asset DROP COLUMN fk_asset_type;
ALTER TABLE llx_asset DROP COLUMN description;
ALTER TABLE llx_asset ADD COLUMN fk_asset_model integer NOT NULL AFTER label;
ALTER TABLE llx_asset ADD COLUMN date_acquisition date NOT NULL AFTER recovered_vat;
ALTER TABLE llx_asset ADD COLUMN date_start date NOT NULL AFTER date_acquisition;
ALTER TABLE llx_asset ADD COLUMN qty real DEFAULT 1 NOT NULL AFTER date_start;
ALTER TABLE llx_asset ADD COLUMN acquisition_type smallint DEFAULT 0 NOT NULL AFTER qty;
ALTER TABLE llx_asset ADD COLUMN asset_type smallint DEFAULT 0 NOT NULL AFTER acquisition_type;
ALTER TABLE llx_asset ADD COLUMN not_depreciated integer(1) DEFAULT 0 AFTER asset_type;
ALTER TABLE llx_asset ADD COLUMN last_main_doc varchar(255) AFTER fk_user_modif;
ALTER TABLE llx_asset ADD COLUMN model_pdf varchar(255) AFTER import_key;

CREATE TABLE llx_asset_accountancy_codes_economic(
	rowid						integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_asset					integer,
	fk_asset_model				integer,

 	asset						varchar(32),
    depreciation_asset			varchar(32),
    depreciation_expense		varchar(32),
    value_asset_sold			varchar(32),
    receivable_on_assignment	varchar(32),
    proceeds_from_sales			varchar(32),
    vat_collected				varchar(32),
    vat_deductible				varchar(32),

	tms							timestamp,
	fk_user_modif				integer
) ENGINE=innodb;

CREATE TABLE llx_asset_accountancy_codes_fiscal(
	rowid									integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_asset								integer,
	fk_asset_model							integer,

    accelerated_depreciation				varchar(32),
    endowment_accelerated_depreciation		varchar(32),
    provision_accelerated_depreciation		varchar(32),

	tms										timestamp,
	fk_user_modif							integer
) ENGINE=innodb;

CREATE TABLE llx_asset_depreciation_options_economic(
	rowid								integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_asset							integer,
	fk_asset_model						integer,

	depreciation_type					smallint		DEFAULT 0 NOT NULL,		-- 0:linear, 1:degressive, 2:exceptional
	accelerated_depreciation_option		integer(1),								-- activate accelerated depreciation mode (fiscal)

    degressive_coefficient				double(24,8),
    duration							smallint		NOT NULL,
    duration_type						smallint		DEFAULT 0  NOT NULL,	-- 0:annual, 1:monthly, 2:daily

	depreciation_reversal_date			date,
	depreciation_reversal_amount_ht		double(24,8),
	amount_base_depreciation_ht			double(24,8),
	amount_base_deductible_ht			double(24,8),
	total_amount_last_depreciation_ht	double(24,8),

	tms									timestamp,
	fk_user_modif						integer
) ENGINE=innodb;

CREATE TABLE llx_asset_depreciation_options_fiscal(
	rowid								integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_asset							integer,
	fk_asset_model						integer,

	depreciation_type					smallint		DEFAULT 0 NOT NULL,		-- 0:linear, 1:degressive, 2:exceptional

    degressive_coefficient				double(24,8),
    duration							smallint		NOT NULL,
    duration_type						smallint		DEFAULT 0  NOT NULL,	-- 0:annual, 1:monthly, 2:daily

	depreciation_reversal_date			date,
	depreciation_reversal_amount_ht		double(24,8),
	amount_base_depreciation_ht			double(24,8),
	amount_base_deductible_ht			double(24,8),
	total_amount_last_depreciation_ht	double(24,8),

	tms									timestamp,
	fk_user_modif						integer
) ENGINE=innodb;

CREATE TABLE llx_asset_depreciation(
	rowid								integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,

	fk_asset							integer			NOT NULL,
	depreciation_mode					varchar(255)	NOT NULL,	-- (economic, fiscal or other)

	ref 								varchar(255)	NOT NULL,
	depreciation_date					datetime		NOT NULL,
	depreciation_ht						double(24,8)	NOT NULL,
	cumulative_depreciation_ht			double(24,8)	NOT NULL,

	tms									timestamp,
	fk_user_modif						integer
) ENGINE=innodb;

CREATE TABLE llx_asset_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

CREATE TABLE llx_asset_model(
	rowid					integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity					integer			DEFAULT 1 NOT NULL,  -- multi company id
	ref						varchar(128)	NOT NULL,
	label					varchar(255)	NOT NULL,

	asset_type				smallint		NOT NULL,

	note_public				text,
	note_private			text,
	date_creation			datetime		NOT NULL,
	tms						timestamp,
	fk_user_creat			integer			NOT NULL,
	fk_user_modif			integer,
	import_key				varchar(14),
	status					smallint		NOT NULL
) ENGINE=innodb;

CREATE TABLE llx_asset_model_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_asset ADD INDEX idx_asset_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset ADD CONSTRAINT fk_asset_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset ADD CONSTRAINT fk_asset_user_creat	FOREIGN KEY (fk_user_creat)		REFERENCES llx_user (rowid);
ALTER TABLE llx_asset ADD CONSTRAINT fk_asset_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_accountancy_codes_economic ADD INDEX idx_asset_ace_rowid (rowid);
ALTER TABLE llx_asset_accountancy_codes_economic ADD UNIQUE uk_asset_ace_fk_asset (fk_asset);
ALTER TABLE llx_asset_accountancy_codes_economic ADD UNIQUE uk_asset_ace_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset_accountancy_codes_economic ADD CONSTRAINT fk_asset_ace_asset			FOREIGN KEY (fk_asset)			REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_accountancy_codes_economic ADD CONSTRAINT fk_asset_ace_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset_accountancy_codes_economic ADD CONSTRAINT fk_asset_ace_user_modif		FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_accountancy_codes_fiscal ADD INDEX idx_asset_acf_rowid (rowid);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD UNIQUE uk_asset_acf_fk_asset (fk_asset);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD UNIQUE uk_asset_acf_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset_accountancy_codes_fiscal ADD CONSTRAINT fk_asset_acf_asset		FOREIGN KEY (fk_asset)			REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD CONSTRAINT fk_asset_acf_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD CONSTRAINT fk_asset_acf_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_depreciation_options_economic ADD INDEX idx_asset_doe_rowid (rowid);
ALTER TABLE llx_asset_depreciation_options_economic ADD UNIQUE uk_asset_doe_fk_asset (fk_asset);
ALTER TABLE llx_asset_depreciation_options_economic ADD UNIQUE uk_asset_doe_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset_depreciation_options_economic ADD CONSTRAINT fk_asset_doe_asset		FOREIGN KEY (fk_asset)			REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_depreciation_options_economic ADD CONSTRAINT fk_asset_doe_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset_depreciation_options_economic ADD CONSTRAINT fk_asset_doe_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_depreciation_options_fiscal ADD INDEX idx_asset_dof_rowid (rowid);
ALTER TABLE llx_asset_depreciation_options_fiscal ADD UNIQUE uk_asset_dof_fk_asset (fk_asset);
ALTER TABLE llx_asset_depreciation_options_fiscal ADD UNIQUE uk_asset_dof_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset_depreciation_options_fiscal ADD CONSTRAINT fk_asset_dof_asset			FOREIGN KEY (fk_asset)			REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_depreciation_options_fiscal ADD CONSTRAINT fk_asset_dof_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset_depreciation_options_fiscal ADD CONSTRAINT fk_asset_dof_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_depreciation ADD INDEX idx_asset_depreciation_rowid (rowid);
ALTER TABLE llx_asset_depreciation ADD INDEX idx_asset_depreciation_rowid (fk_asset);
ALTER TABLE llx_asset_depreciation ADD INDEX idx_asset_depreciation_rowid (depreciation_mode);
ALTER TABLE llx_asset_depreciation ADD INDEX idx_asset_depreciation_rowid (ref);
ALTER TABLE llx_asset_depreciation ADD UNIQUE uk_asset_depreciation_fk_asset (fk_asset, depreciation_mode, ref);

ALTER TABLE llx_asset_depreciation ADD CONSTRAINT fk_asset_depreciation_asset		FOREIGN KEY (fk_asset)			REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_depreciation ADD CONSTRAINT fk_asset_depreciation_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_extrafields ADD INDEX idx_asset_extrafields (fk_object);

ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_rowid (rowid);
ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_ref (ref);
ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_entity (entity);
ALTER TABLE llx_asset_model ADD UNIQUE INDEX uk_asset_model (entity, ref);

ALTER TABLE llx_asset_model ADD CONSTRAINT fk_asset_model_user_creat	FOREIGN KEY (fk_user_creat)		REFERENCES llx_user (rowid);
ALTER TABLE llx_asset_model ADD CONSTRAINT fk_asset_model_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_model_extrafields ADD INDEX idx_asset_model_extrafields (fk_object);
