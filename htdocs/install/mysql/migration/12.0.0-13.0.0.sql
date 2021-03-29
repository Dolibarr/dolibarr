--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 13.0.0 or higher.
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


-- Missing in v12 or lower

ALTER TABLE llx_payment_salary MODIFY COLUMN ref varchar(30) NULL;
ALTER TABLE llx_payment_various MODIFY COLUMN ref varchar(30) NULL;

ALTER TABLE llx_prelevement_bons ADD COLUMN type varchar(16) DEFAULT 'debit-order';

ALTER TABLE llx_prelevement_facture CHANGE COLUMN fk_facture_foun fk_facture_fourn integer NULL;

ALTER TABLE llx_prelevement_facture_demande ADD INDEX idx_prelevement_facture_demande_fk_facture (fk_facture);
ALTER TABLE llx_prelevement_facture_demande ADD INDEX idx_prelevement_facture_demande_fk_facture_fourn (fk_facture_fourn);

ALTER TABLE llx_document_model MODIFY COLUMN type varchar(64);

ALTER TABLE llx_bom_bom MODIFY COLUMN duration double(24,8);

ALTER TABLE llx_bom_bom_extrafields ADD INDEX idx_bom_bom_extrafields_fk_object (fk_object);

create table llx_mrp_mo_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                                 -- import key
) ENGINE=innodb;

ALTER TABLE llx_mrp_mo_extrafields DROP INDEX idx_fk_object;

ALTER TABLE llx_mrp_mo_extrafields ADD INDEX idx_mrp_mo_fk_object(fk_object);


-- For v13

insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (111,11,     '0','0','No Sales Tax',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (112,11,     '4','0','Sales Tax 4%',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (113,11,     '6','0','Sales Tax 6%',1);

ALTER TABLE llx_bom_bom ADD COLUMN bomtype integer DEFAULT 0;

UPDATE llx_emailcollector_emailcollector SET ref = 'Collect_Ticket_Requests' WHERE ref = 'Collect_Ticket_Requets';
UPDATE llx_emailcollector_emailcollector SET ref = 'Collect_Responses_In' WHERE ref = 'Collect_Responses';


UPDATE llx_document_model set nom = 'standard' where nom = 'Standard' and type ='stock';
UPDATE llx_document_model set nom = 'stdmovement', type = 'movement' where nom = 'StdMouvement' and type ='mouvement';


UPDATE llx_const SET value = 0 WHERE name = 'FACTURE_TVAOPTION' and value = 'franchise';
UPDATE llx_const SET value = 1 WHERE name = 'FACTURE_TVAOPTION' and value <> 'franchise' AND value <> '0' AND value <> '1';

ALTER TABLE llx_commande MODIFY COLUMN date_livraison DATETIME;

ALTER TABLE llx_website ADD COLUMN position integer DEFAULT 0;

ALTER TABLE llx_establishment ADD COLUMN ref varchar(30);
ALTER TABLE llx_establishment ADD COLUMN label varchar(128);
UPDATE llx_establishment SET ref = rowid WHERE ref IS NULL;
ALTER TABLE llx_establishment MODIFY COLUMN ref varchar(30) NOT NULL;
ALTER TABLE llx_establishment MODIFY COLUMN label varchar(128);

INSERT INTO llx_const (name, entity, value, type, visible) VALUES ('PRODUCT_PRICE_BASE_TYPE', 0, 'HT', 'string', 0);

ALTER TABLE llx_subscription MODIFY COLUMN datef DATETIME;

ALTER TABLE llx_loan_schedule ADD column fk_payment_loan INTEGER;


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

ALTER TABLE llx_user ADD COLUMN datelastpassvalidation datetime;
ALTER TABLE llx_user ADD COLUMN datestartvalidity datetime;
ALTER TABLE llx_user ADD COLUMN dateendvalidity   datetime;

ALTER TABLE llx_user ADD COLUMN idpers1 varchar(128);
ALTER TABLE llx_user ADD COLUMN idpers2	varchar(128);
ALTER TABLE llx_user ADD COLUMN idpers3	varchar(128);


-- Intracomm Report
CREATE TABLE llx_c_transport_mode (
  rowid     integer AUTO_INCREMENT PRIMARY KEY,
  entity    integer	DEFAULT 1 NOT NULL,	-- multi company id
  code      varchar(3) NOT NULL,
  label     varchar(255) NOT NULL,
  active    tinyint DEFAULT 1  NOT NULL
) ENGINE=innodb;
ALTER TABLE llx_c_transport_mode ADD UNIQUE INDEX uk_c_transport_mode (code, entity);

INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('MAR', 'Transport maritime (y compris camions ou wagons sur bateau)', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('TRA', 'Transport par chemin de fer (y compris camions sur wagon)', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('ROU', 'Transport par route', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('AIR', 'Transport par air', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('POS', 'Envois postaux', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('OLE', 'Installations de transport fixe (oléoduc)', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('NAV', 'Transport par navigation intérieure', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('PRO', 'Propulsion propre', 1);

ALTER TABLE llx_facture ADD COLUMN fk_transport_mode integer after location_incoterms;
ALTER TABLE llx_facture_fourn ADD COLUMN fk_transport_mode integer after location_incoterms;

ALTER TABLE llx_societe ADD COLUMN transport_mode tinyint after cond_reglement;
ALTER TABLE llx_societe ADD COLUMN transport_mode_supplier tinyint after cond_reglement_supplier;

CREATE TABLE llx_intracommreport
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,

  ref				varchar(30)        NOT NULL,			-- report reference number
  entity			integer  DEFAULT 1 NOT NULL,			-- multi company id
  type_declaration	varchar(32),
  periods			varchar(32),
  mode				varchar(32),
  content_xml		text,
  type_export		varchar(10),
  datec             datetime,
  tms               timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=innodb;

ALTER TABLE llx_c_incoterms ADD COLUMN label varchar(100) NULL;

CREATE TABLE llx_recruitment_recruitmentjobposition(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	ref varchar(128) DEFAULT '(PROV)' NOT NULL,
	entity INTEGER DEFAULT 1 NOT NULL,
	label varchar(255) NOT NULL,
	qty integer DEFAULT 1 NOT NULL,
	fk_soc integer,
	fk_project integer,
	fk_user_recruiter integer,
	fk_user_supervisor integer,
	fk_establishment integer,
	date_planned date,
	remuneration_suggested varchar(255),
	description text,
	note_public text,
	note_private text,
	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	last_main_doc varchar(255),
	import_key varchar(14),
	model_pdf varchar(255),
	status smallint NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_recruitment_recruitmentjobposition ADD INDEX idx_recruitment_recruitmentjobposition_rowid (rowid);
ALTER TABLE llx_recruitment_recruitmentjobposition ADD INDEX idx_recruitment_recruitmentjobposition_ref (ref);
ALTER TABLE llx_recruitment_recruitmentjobposition ADD INDEX idx_recruitment_recruitmentjobposition_fk_soc (fk_soc);
ALTER TABLE llx_recruitment_recruitmentjobposition ADD INDEX idx_recruitment_recruitmentjobposition_fk_project (fk_project);
ALTER TABLE llx_recruitment_recruitmentjobposition ADD CONSTRAINT llx_recruitment_recruitmentjobposition_fk_user_recruiter FOREIGN KEY (fk_user_recruiter) REFERENCES llx_user(rowid);
ALTER TABLE llx_recruitment_recruitmentjobposition ADD CONSTRAINT llx_recruitment_recruitmentjobposition_fk_user_supervisor FOREIGN KEY (fk_user_supervisor) REFERENCES llx_user(rowid);
ALTER TABLE llx_recruitment_recruitmentjobposition ADD CONSTRAINT llx_recruitment_recruitmentjobposition_fk_establishment FOREIGN KEY (fk_establishment) REFERENCES llx_establishment(rowid);
ALTER TABLE llx_recruitment_recruitmentjobposition ADD CONSTRAINT llx_recruitment_recruitmentjobposition_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_recruitment_recruitmentjobposition ADD INDEX idx_recruitment_recruitmentjobposition_status (status);

ALTER TABLE llx_recruitment_recruitmentjobposition ADD COLUMN email_recruiter varchar(255);
ALTER TABLE llx_recruitment_recruitmentjobposition ADD COLUMN entity INTEGER DEFAULT 1 NOT NULL;
ALTER TABLE llx_recruitment_recruitmentjobposition ADD COLUMN remuneration_suggested varchar(255);

create table llx_recruitment_recruitmentjobposition_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_recruitment_recruitmentjobposition_extrafields ADD INDEX idx_recruitmentjobposition_fk_object(fk_object);



CREATE TABLE llx_recruitment_recruitmentcandidature(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity integer NOT NULL DEFAULT 1,
	fk_recruitmentjobposition INTEGER NULL,
	ref varchar(128) DEFAULT '(PROV)' NOT NULL,
	description text,
	note_public text,
	note_private text,
	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	import_key varchar(14),
	model_pdf varchar(255),
	status smallint NOT NULL,
	firstname varchar(128),
	lastname varchar(128),
	email varchar(255),
	phone varchar(64),
	date_birth date,
	remuneration_requested integer,
	remuneration_proposed integer,
	email_msgid varchar(255),
	fk_recruitment_origin INTEGER NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE llx_recruitment_recruitmentcandidature ADD COLUMN entity integer NOT NULL DEFAULT 1;
ALTER TABLE llx_recruitment_recruitmentcandidature ADD COLUMN email_msgid varchar(255);
ALTER TABLE llx_recruitment_recruitmentcandidature ADD COLUMN fk_recruitment_origin INTEGER NULL;
ALTER TABLE llx_recruitment_recruitmentcandidature ADD COLUMN date_birth date;

ALTER TABLE llx_recruitment_recruitmentcandidature ADD INDEX idx_recruitment_recruitmentcandidature_rowid (rowid);
ALTER TABLE llx_recruitment_recruitmentcandidature ADD INDEX idx_recruitment_recruitmentcandidature_ref (ref);
ALTER TABLE llx_recruitment_recruitmentcandidature ADD CONSTRAINT llx_recruitment_recruitmentcandidature_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_recruitment_recruitmentcandidature ADD INDEX idx_recruitment_recruitmentcandidature_status (status);

create table llx_recruitment_recruitmentcandidature_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_recruitment_recruitmentcandidature_extrafields ADD INDEX idx_recruitmentcandidature_fk_object(fk_object);

ALTER TABLE llx_recruitment_recruitmentcandidature ADD UNIQUE INDEX uk_recruitmentcandidature_email_msgid(email_msgid);


ALTER TABLE llx_product_attribute ADD COLUMN ref_ext VARCHAR(255) after ref;
ALTER TABLE llx_product_attribute_combination ADD COLUMN variation_ref_ext varchar(255) AFTER variation_weight;


CREATE TABLE llx_product_attribute_combination_price_level
(
  rowid INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  fk_product_attribute_combination INTEGER DEFAULT 1 NOT NULL,
  fk_price_level INTEGER DEFAULT 1 NOT NULL,
  variation_price DOUBLE(24,8) NOT NULL,
  variation_price_percentage INTEGER NULL
)ENGINE=innodb;

ALTER TABLE llx_product_attribute_combination_price_level ADD UNIQUE( fk_product_attribute_combination, fk_price_level);



-- Add dictionary for prospect level and action commercial on contacts (Using this is not recommanded)

create table llx_c_prospectcontactlevel
(
  code            varchar(12) PRIMARY KEY,
  label           varchar(30),
  sortorder       smallint,
  active          smallint    DEFAULT 1 NOT NULL,
  module          varchar(32) NULL
) ENGINE=innodb;
insert into llx_c_prospectcontactlevel (code,label,sortorder) values ('PL_NONE',      'None',     1);
insert into llx_c_prospectcontactlevel (code,label,sortorder) values ('PL_LOW',       'Low',      2);
insert into llx_c_prospectcontactlevel (code,label,sortorder) values ('PL_MEDIUM',    'Medium',   3);
insert into llx_c_prospectcontactlevel (code,label,sortorder) values ('PL_HIGH',      'High',     4);

create table llx_c_stcommcontact
(
  id       integer      PRIMARY KEY,
  code     varchar(12)  NOT NULL,
  libelle  varchar(30),
  picto    varchar(128),
  active   tinyint default 1  NOT NULL
)ENGINE=innodb;
ALTER TABLE llx_c_stcommcontact ADD UNIQUE INDEX uk_c_stcommcontact(code);

insert into llx_c_stcommcontact (id,code,libelle) values (-1, 'ST_NO',    'Do not contact');
insert into llx_c_stcommcontact (id,code,libelle) values ( 0, 'ST_NEVER', 'Never contacted');
insert into llx_c_stcommcontact (id,code,libelle) values ( 1, 'ST_TODO',  'To contact');
insert into llx_c_stcommcontact (id,code,libelle) values ( 2, 'ST_PEND',  'Contact in progress');
insert into llx_c_stcommcontact (id,code,libelle) values ( 3, 'ST_DONE',  'Contacted');

ALTER TABLE llx_socpeople ADD COLUMN fk_prospectcontactlevel varchar(12) AFTER priv;
ALTER TABLE llx_socpeople ADD COLUMN fk_stcommcontact integer DEFAULT 0 NOT NULL AFTER fk_prospectcontactlevel;

create table llx_c_recruitment_origin
(
  rowid      	integer AUTO_INCREMENT PRIMARY KEY,
  code          varchar(32) NOT NULL,
  label 	    varchar(64)	NOT NULL,
  active  	    tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;


ALTER TABLE llx_recruitment_recruitmentcandidature ADD UNIQUE INDEX uk_recruitmentcandidature_email_msgid(email_msgid);


ALTER TABLE llx_product MODIFY COLUMN seuil_stock_alerte float;
ALTER TABLE llx_product MODIFY COLUMN desiredstock float;

ALTER TABLE llx_product_warehouse_properties MODIFY COLUMN seuil_stock_alerte float;
ALTER TABLE llx_product_warehouse_properties MODIFY COLUMN desiredstock float;

ALTER TABLE llx_product ADD COLUMN fk_state integer DEFAULT NULL AFTER fk_country;

ALTER TABLE llx_projet ADD COLUMN email_msgid varchar(255);
ALTER TABLE llx_ticket ADD COLUMN email_msgid varchar(255);
ALTER TABLE llx_actioncomm ADD COLUMN reply_to varchar(255);

ALTER TABLE llx_paiement ADD pos_change DOUBLE(24,8) DEFAULT 0 AFTER fk_export_compta;

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_CREATE','Contact address created','Executed when a contact is created','contact',50);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_SENTBYMAIL','Mails sent from third party card','Executed when you send email from contact adress card','contact',51);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_DELETE','Contact address deleted','Executed when a contact is deleted','contact',52);

ALTER TABLE llx_opensurvey_sondage CHANGE COLUMN tms tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_ecm_directories CHANGE COLUMN date_m tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_ecm_files CHANGE COLUMN date_m tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'recruitment','recruitmentcandidature_send','',0,null,null,'(AnswerCandidature)'                    ,100,'$conf->recruitment->enabled',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourCandidature)__',       '__(Hello)__ __CANDIDATE_FULLNAME__,<br><br>\n\n__(YourCandidatureAnswer)__<br>\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 0);

ALTER TABLE llx_c_action_trigger MODIFY COLUMN code varchar(64) NOT NULL;

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTJOBPOSITION_CREATE','Job created','Executed when a job is created','recruitment',7500);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTJOBPOSITION_MODIFY','Job modified','Executed when a job is modified','recruitment',7502);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTJOBPOSITION_SENTBYMAIL','Mails sent from job record','Executed when you send email from job record','recruitment',7504);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTJOBPOSITION_DELETE','Job deleted','Executed when a job is deleted','recruitment',7506);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTCANDIDATURE_CREATE','Candidature created','Executed when a candidature is created','recruitment',7510);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTCANDIDATURE_MODIFY','Candidature modified','Executed when a candidature is modified','recruitment',7512);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTCANDIDATURE_SENTBYMAIL','Mails sent from candidature record','Executed when you send email from candidature record','recruitment',7514);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTCANDIDATURE_DELETE','Candidature deleted','Executed when a candidature is deleted','recruitment',7516);

ALTER TABLE llx_actioncomm_reminder ADD COLUMN entity integer NOT NULL DEFAULT 1;
ALTER TABLE llx_actioncomm_reminder ADD COLUMN fk_actioncomm integer NOT NULL;
ALTER TABLE llx_actioncomm_reminder ADD COLUMN fk_email_template integer;
ALTER TABLE llx_actioncomm_reminder ADD COLUMN lasterror varchar(128) NULL;

ALTER TABLE llx_actioncomm_reminder DROP INDEX uk_actioncomm_reminder_unique;
ALTER TABLE llx_actioncomm_reminder ADD UNIQUE uk_actioncomm_reminder_unique (fk_user, typeremind, offsetvalue, offsetunit, fk_actioncomm);

ALTER TABLE llx_actioncomm_reminder ADD INDEX idx_actioncomm_reminder_status (status);


ALTER TABLE llx_inventorydet ADD UNIQUE uk_inventorydet(fk_inventory, fk_warehouse, fk_product, batch);

ALTER TABLE llx_commandedet ADD COLUMN ref_ext varchar(255) AFTER label;
ALTER TABLE llx_facturedet ADD COLUMN ref_ext varchar(255) AFTER multicurrency_total_ttc;

ALTER TABLE llx_c_ticket_category ADD COLUMN fk_parent integer DEFAULT 0 NOT NULL;
ALTER TABLE llx_c_ticket_category ADD COLUMN force_severity varchar(32) NULL;

ALTER TABLE llx_c_ticket_severity CHANGE color color VARCHAR(10) NULL;

ALTER TABLE llx_expensereport ADD COLUMN fk_user_creat integer NULL;

ALTER TABLE llx_expensereport_ik ADD COLUMN ikoffset double DEFAULT 0 NOT NULL;

ALTER TABLE llx_paiement ADD COLUMN ref_ext varchar(255) AFTER ref;

ALTER TABLE llx_bank ADD COLUMN origin_id integer;
ALTER TABLE llx_bank ADD COLUMN origin_type varchar(64) NULL;
ALTER TABLE llx_bank ADD COLUMN import_key varchar(14);

ALTER TABLE llx_menu MODIFY COLUMN enabled text;

CREATE TABLE llx_ecm_files_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                             -- import key
) ENGINE=innodb;

ALTER TABLE llx_ecm_files_extrafields ADD INDEX idx_ecm_files_extrafields (fk_object);

CREATE TABLE llx_ecm_directories_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                             -- import key
) ENGINE=innodb;

ALTER TABLE llx_ecm_directories_extrafields ADD INDEX idx_ecm_directories_extrafields (fk_object);
ALTER TABLE llx_website_page ADD COLUMN allowed_in_frames integer DEFAULT 0;
ALTER TABLE llx_website_page ADD COLUMN object_type varchar(255);
ALTER TABLE llx_website_page ADD COLUMN fk_object varchar(255);

DELETE FROM llx_const WHERE name in ('MAIN_INCLUDE_ZERO_VAT_IN_REPORTS');


-- VMYSQL4.1 UPDATE llx_projet_task_time SET tms = null WHERE tms = 0;

ALTER TABLE llx_projet_task_time CHANGE COLUMN tms tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_projet_task_time MODIFY COLUMN datec datetime;


DELETE FROM llx_user_rights WHERE fk_id IN (SELECT id FROM llx_rights_def where module = 'holiday' and perms = 'lire_tous');
DELETE FROM llx_rights_def where module = 'holiday' and perms = 'lire_tous';
UPDATE llx_rights_def set perms = 'readall' WHERE perms = 'read_all' and module = 'holiday';

CREATE TABLE llx_c_product_nature (
      rowid integer AUTO_INCREMENT PRIMARY KEY,
      code tinyint NOT NULL,
      label varchar(100),
      active tinyint DEFAULT 1  NOT NULL
) ENGINE=innodb;


ALTER TABLE llx_product DROP FOREIGN KEY fk_product_finished;

-- VMYSQL4.1 DROP INDEX uk_c_product_nature on llx_c_product_nature;
-- VPGSQL8.2 DROP INDEX uk_c_product_nature;

ALTER TABLE llx_c_product_nature ADD UNIQUE INDEX uk_c_product_nature(code);

INSERT INTO llx_c_product_nature (code, label, active) VALUES (0, 'RowMaterial', 1);
INSERT INTO llx_c_product_nature (code, label, active) VALUES (1, 'Finished', 1);

ALTER TABLE llx_product MODIFY COLUMN finished tinyint DEFAULT NULL;

ALTER TABLE llx_product ADD CONSTRAINT fk_product_finished FOREIGN KEY (finished) REFERENCES llx_c_product_nature (code);

-- MIGRATION TO DO AFTER RENAMING AN OBJECT

-- drop constraint
ALTER TABLE llx_livraison DROP FOREIGN KEY  fk_livraison_fk_soc;
ALTER TABLE llx_livraison DROP FOREIGN KEY  fk_livraison_fk_user_author;
ALTER TABLE llx_livraison DROP FOREIGN KEY  fk_livraison_fk_user_valid;

-- rename Table
ALTER TABLE llx_livraison RENAME TO llx_delivery;
ALTER TABLE llx_livraison_extrafields RENAME TO llx_delivery_extrafields;
ALTER TABLE llx_livraisondet RENAME TO llx_deliverydet;
ALTER TABLE llx_livraisondet_extrafields RENAME TO llx_deliverydet_extrafields;

-- rename index
ALTER TABLE llx_delivery DROP INDEX idx_livraison_uk_ref;
ALTER TABLE llx_delivery ADD UNIQUE INDEX idx_delivery_uk_ref (ref, entity);
ALTER TABLE llx_delivery DROP INDEX idx_livraison_fk_soc;
ALTER TABLE llx_delivery ADD INDEX idx_delivery_fk_soc (fk_soc);
ALTER TABLE llx_delivery DROP INDEX idx_livraison_fk_user_author;
ALTER TABLE llx_delivery ADD INDEX idx_delivery_fk_user_author (fk_user_author);
ALTER TABLE llx_delivery DROP INDEX idx_livraison_fk_user_valid;
ALTER TABLE llx_delivery ADD INDEX idx_delivery_fk_user_valid (fk_user_valid);

-- drop constraint
ALTER TABLE llx_delivery DROP FOREIGN KEY  fk_livraison_fk_soc;
ALTER TABLE llx_delivery DROP FOREIGN KEY  fk_livraison_fk_user_author;
ALTER TABLE llx_delivery DROP FOREIGN KEY  fk_livraison_fk_user_valid;

-- add constraint
ALTER TABLE llx_delivery ADD CONSTRAINT fk_delivery_fk_soc			FOREIGN KEY (fk_soc)			REFERENCES llx_societe (rowid);
ALTER TABLE llx_delivery ADD CONSTRAINT fk_delivery_fk_user_author	FOREIGN KEY (fk_user_author)	REFERENCES llx_user (rowid);
ALTER TABLE llx_delivery ADD CONSTRAINT fk_delivery_fk_user_valid	FOREIGN KEY (fk_user_valid)	REFERENCES llx_user (rowid);

ALTER TABLE llx_deliverydet DROP FOREIGN KEY  fk_livraisondet_fk_livraison;
ALTER TABLE llx_deliverydet DROP INDEX idx_livraisondet_fk_expedition;
ALTER TABLE llx_deliverydet CHANGE COLUMN fk_livraison fk_delivery integer;
ALTER TABLE llx_deliverydet ADD INDEX idx_deliverydet_fk_delivery (fk_delivery);
ALTER TABLE llx_deliverydet ADD CONSTRAINT fk_deliverydet_fk_delivery FOREIGN KEY (fk_delivery) REFERENCES llx_delivery (rowid);

UPDATE llx_extrafields SET elementtype = 'delivery' WHERE elementtype = 'livraison';
UPDATE llx_extrafields SET elementtype = 'deliverydet' WHERE elementtype = 'livraisondet';

-- update llx_ecm_files
UPDATE llx_ecm_files SET src_object_type = 'delivery' WHERE src_object_type = 'livraison';

-- update llx_links
UPDATE llx_links SET objecttype = 'delivery' WHERE objecttype = 'livraison';

-- update llx_document_model
UPDATE llx_document_model SET type = 'delivery' WHERE type = 'livraison';

-- update llx_object_lang
UPDATE llx_object_lang SET type_object = 'delivery' WHERE type_object = 'livraison';

-- update llx_c_type_contact
UPDATE llx_c_type_contact SET element = 'delivery' WHERE element = 'livraison';

-- update llx_c_email_template
UPDATE llx_c_email_template SET type_template = 'delivery' WHERE type_template = 'livraison';

-- update llx_element_element
UPDATE llx_element_element SET sourcetype = 'delivery' WHERE sourcetype = 'livraison';
UPDATE llx_element_element SET targettype = 'delivery' WHERE targettype = 'livraison';

-- update llx_actioncomm
UPDATE llx_actioncomm SET element_type = 'delivery' WHERE element_type = 'livraison';

-- update llx_const
UPDATE llx_const set name = 'DELIVERY_ADDON_NUMBER' WHERE name = 'LIVRAISON_ADDON_NUMBER';
UPDATE llx_const set value = 'mod_delivery_jade' WHERE value = 'mod_livraison_jade' AND name = 'DELIVERY_ADDON_NUMBER';
UPDATE llx_const set value = 'mod_delivery_saphir' WHERE value = 'mod_livraison_saphir' AND name = 'DELIVERY_ADDON_NUMBER';

-- update llx_rights_def
UPDATE llx_rights_def set perms = 'delivery' WHERE perms = 'livraison' and module = 'expedition';
UPDATE llx_rights_def set perms = 'delivery_advance' WHERE perms = 'livraison_advance' and module = 'expedition';


ALTER TABLE llx_commande_fournisseurdet ADD INDEX idx_commande_fournisseurdet_fk_commande (fk_commande);
ALTER TABLE llx_commande_fournisseurdet ADD INDEX idx_commande_fournisseurdet_fk_product (fk_product);


CREATE TABLE llx_zapier_hook(
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    entity integer DEFAULT 1 NOT NULL,
    url varchar(255),
    event varchar(255),
    module varchar(128),
    action varchar(128),
    status integer,
    date_creation datetime NOT NULL,
    fk_user integer NOT NULL,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    import_key varchar(14)
) ENGINE=innodb;


CREATE TABLE llx_session(
  session_id varchar(50) PRIMARY KEY,
  session_variable text,
  last_accessed datetime NOT NULL,
  fk_user integer NOT NULL,
  remote_ip varchar(64) NULL,
  user_agent varchar(128) NULL
)ENGINE=innodb;

INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'github', 'Github', 'https://github.com/{socialid}', 'fa-github', 1);

-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_funnel_of_prospection.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_funnel_of_prospection.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_customers_outstanding_bill_reached.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_customers_outstanding_bill_reached.php' AND entity = 1);
-- VMYSQL4.1 INSERT INTO llx_boxes_def (file, entity) SELECT  'box_scheduled_jobs.php', 1 FROM DUAL WHERE NOT EXISTS (SELECT * FROM llx_boxes_def WHERE file = 'box_scheduled_jobs.php' AND entity = 1);

ALTER TABLE llx_product_fournisseur_price ADD COLUMN packaging varchar(64);

ALTER TABLE llx_projet ADD COLUMN fk_opp_status_end integer DEFAULT NULL;

UPDATE llx_c_action_trigger SET elementtype = 'expensereport' where elementtype = 'expense_report' AND code like 'EXPENSE_%';
UPDATE llx_c_action_trigger SET code = 'EXPENSE_REPORT_PAID' where code = 'EXPENSE_REPORT_PAYED';
UPDATE llx_c_action_trigger SET code = 'EXPENSE_REPORT_DELETE' where code = 'EXPENSE_DELETE';
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_CREATE','Expense report created','Executed when an expense report is created','expensereport',201);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_VALIDATE','Expense report validated','Executed when an expense report is validated','expensereport',202);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_APPROVE','Expense report approved','Executed when an expense report is approved','expensereport',203);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_PAID','Expense report billed','Executed when an expense report is set as billed','expensereport',204);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_DELETE','Expense report deleted','Executed when an expense report is deleted','expensereport',205);

-- Removed no more used function
-- VPGSQL8.2 DROP FUNCTION IF EXISTS update_modified_column_date_m() CASCADE;

insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 6,'AC_EMAIL_IN','system','reception Email',NULL, 1, 4);


-- VMYSQL4.3 ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN montant double(24,8) NULL;
-- VPGSQL8.2 ALTER TABLE llx_accounting_bookkeeping ALTER COLUMN montant DROP NOT NULL;


