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


-- Missing in v12

ALTER TABLE llx_prelevement_bons ADD COLUMN type varchar(16) DEFAULT 'debit-order';

ALTER TABLE llx_prelevement_facture_demande ADD INDEX idx_prelevement_facture_demande_fk_facture (fk_facture);
ALTER TABLE llx_prelevement_facture_demande ADD INDEX idx_prelevement_facture_demande_fk_facture_fourn (fk_facture_fourn);

ALTER TABLE llx_bom_bom MODIFY COLUMN duration double(24,8);

ALTER TABLE llx_document_model MODIFY COLUMN type varchar(64);


-- For v13

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

ALTER TABLE llx_user ADD COLUMN datestartvalidity datetime;
ALTER TABLE llx_user ADD COLUMN dateendvalidity   datetime;

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
	tms timestamp, 
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
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_recruitment_recruitmentjobposition_extrafields ADD INDEX idx_fk_object(fk_object);



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
	tms timestamp, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	import_key varchar(14), 
	model_pdf varchar(255), 
	status smallint NOT NULL, 
	firstname varchar(128), 
	lastname varchar(128),
	email varchar(255),
	phone varchar(64),
	remuneration_requested integer, 
	remuneration_proposed integer,
	email_msgid varchar(255),
	fk_recruitment_origin INTEGER NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE llx_recruitment_recruitmentcandidature ADD COLUMN entity integer NOT NULL DEFAULT 1;
ALTER TABLE llx_recruitment_recruitmentcandidature ADD COLUMN email_msgid varchar(255);
ALTER TABLE llx_recruitment_recruitmentcandidature ADD COLUMN fk_recruitment_origin INTEGER NULL;

ALTER TABLE llx_recruitment_recruitmentcandidature ADD INDEX idx_recruitment_recruitmentcandidature_rowid (rowid);
ALTER TABLE llx_recruitment_recruitmentcandidature ADD INDEX idx_recruitment_recruitmentcandidature_ref (ref);
ALTER TABLE llx_recruitment_recruitmentcandidature ADD CONSTRAINT llx_recruitment_recruitmentcandidature_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_recruitment_recruitmentcandidature ADD INDEX idx_recruitment_recruitmentcandidature_status (status);

create table llx_recruitment_recruitmentcandidature_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_recruitment_recruitmentcandidature_extrafields ADD INDEX idx_fk_object(fk_object);

ALTER TABLE llx_recruitment_recruitmentcandidature ADD UNIQUE INDEX uk_recruitmentcandidature_email_msgid(email_msgid);



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


ALTER TABLE llx_projet ADD COLUMN email_msgid varchar(255);
ALTER TABLE llx_ticket ADD COLUMN email_msgid varchar(255);
ALTER TABLE llx_actioncomm ADD COLUMN reply_to varchar(255);

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_CREATE','Contact address created','Executed when a contact is created','contact',50);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_SENTBYMAIL','Mails sent from third party card','Executed when you send email from contact adress card','contact',51);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_DELETE','Contact address deleted','Executed when a contact is deleted','contact',52);

ALTER TABLE llx_ecm_directories CHANGE COLUMN date_m tms timestamp;
ALTER TABLE llx_ecm_files CHANGE COLUMN date_m tms timestamp;

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
