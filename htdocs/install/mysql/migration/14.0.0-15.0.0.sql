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


CREATE TABLE llx_hrm_evaluation(
    -- BEGIN MODULEBUILDER FIELDS
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
    -- END MODULEBUILDER FIELDS
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


CREATE TABLE llx_hrm_evaluationdet(
    -- BEGIN MODULEBUILDER FIELDS
                                      rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
                                      date_creation datetime NOT NULL,
                                      tms timestamp,
                                      fk_user_creat integer NOT NULL,
                                      fk_user_modif integer,
                                      fk_skill integer NOT NULL,
                                      fk_evaluation integer NOT NULL,
                                      rank integer NOT NULL,
                                      required_rank integer NOT NULL,
                                      import_key varchar(14)
    -- END MODULEBUILDER FIELDS
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




CREATE TABLE llx_hrm_job(

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
    -- BEGIN MODULEBUILDER FIELDS
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
                                 commentaire_abandon varchar(255),
                                 note_public text,
                                 note_private text,
                                 fk_user_creat integer,
                                 fk_user_modif integer
    -- END MODULEBUILDER FIELDS
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



CREATE TABLE llx_hrm_skill(
    -- BEGIN MODULEBUILDER FIELDS
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
    -- END MODULEBUILDER FIELDS
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


CREATE TABLE llx_hrm_skilldet(
    -- BEGIN MODULEBUILDER FIELDS
                                 rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
                                 description text,
                                 fk_user_creat integer NOT NULL,
                                 fk_user_modif integer,
                                 fk_skill integer NOT NULL,
                                 rank integer
    -- END MODULEBUILDER FIELDS
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


CREATE TABLE llx_hrm_skillrank(
    -- BEGIN MODULEBUILDER FIELDS
                                  rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
                                  fk_skill integer NOT NULL,
                                  rank integer NOT NULL,
                                  fk_object integer NOT NULL,
                                  date_creation datetime NOT NULL,
                                  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                  fk_user_creat integer NOT NULL,
                                  fk_user_modif integer,
                                  objecttype varchar(128) NOT NULL
    -- END MODULEBUILDER FIELDS
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
