--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.8.0 or higher. 
--

ALTER TABLE llx_actioncomm CHANGE fk_projet fk_project integer;

ALTER TABLE llx_don ADD COLUMN ref varchar(30) DEFAULT NULL AFTER rowid;
ALTER TABLE llx_don ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;

ALTER TABLE llx_stock_mouvement ADD COLUMN label varchar(128);

ALTER TABLE llx_deplacement ADD COLUMN ref varchar(30) DEFAULT NULL AFTER rowid;
ALTER TABLE llx_deplacement ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;
ALTER TABLE llx_deplacement ADD COLUMN note_public text;

ALTER TABLE llx_element_element DROP INDEX idx_element_element_idx1;
ALTER TABLE llx_element_element DROP INDEX idx_element_element_targetid;
ALTER TABLE llx_element_element CHANGE sourceid fk_source integer NOT NULL;
ALTER TABLE llx_element_element CHANGE targetid fk_target integer NOT NULL;
ALTER TABLE llx_element_element ADD UNIQUE INDEX idx_element_element_idx1 (fk_source, sourcetype, fk_target, targettype);
ALTER TABLE llx_element_element ADD INDEX idx_element_element_fk_target (fk_target);

ALTER TABLE llx_ecm_document RENAME TO llx_ecm_documents;
ALTER TABLE llx_ecm_documents ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;
ALTER TABLE llx_ecm_documents ADD COLUMN crc varchar(32) DEFAULT '' NOT NULL AFTER private;
ALTER TABLE llx_ecm_documents ADD COLUMN cryptkey varchar(50) DEFAULT '' NOT NULL AFTER crc;
ALTER TABLE llx_ecm_documents ADD COLUMN cipher varchar(50) DEFAULT 'twofish' NOT NULL AFTER cryptkey;

ALTER TABLE llx_facture_fourn_det MODIFY COLUMN qty real;


ALTER TABLE llx_projet ADD COLUMN datee DATE AFTER dateo;

ALTER TABLE llx_notify ADD COLUMN email VARCHAR(255);

ALTER TABLE llx_c_currencies ADD COLUMN labelsing   varchar(64);
update llx_c_currencies set labelsing='Euro' where code_iso='EUR';
update llx_c_currencies set labelsing='Dollar' where code_iso='USD';

insert into llx_action_def (rowid,code,titre,description,objet_type) values (5,'NOTIFY_VAL_ORDER','Validation commande client','Executed when a customer order is validated','order');
insert into llx_action_def (rowid,code,titre,description,objet_type) values (6,'NOTIFY_VAL_PROPAL','Validation proposition client','Executed when a commercial proposal is validated','propal');

UPDATE llx_c_type_contact SET element='project' WHERE element='projet';

UPDATE llx_const set value='mail' where value='simplemail' and name='MAIN_MAIL_SENDMODE';

ALTER TABLE llx_projet ADD COLUMN model_pdf varchar(50) AFTER note;


-- Create table of extra fields
create table llx_extra_fields
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  entity                integer  DEFAULT 1 NOT NULL,
  object 				varchar(64) NOT NULL,
  assign 				integer,
  name 					varchar(64) NOT NULL,
  label					varchar(64) NOT NULL,
  format				varchar(8) 	NOT NULL,
  fieldsize 			integer,
  maxlength 			integer,
  options 				varchar(45),
  rank 					integer
)type=innodb;

ALTER TABLE llx_extra_fields ADD UNIQUE INDEX idx_extra_fields_name (name, entity);

-- Create table of possible values
create table llx_extra_fields_options
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  fk_extra_fields 		integer NOT NULL,
  value 				varchar(255) NOT NULL,
  rank 					integer
)type=innodb;

ALTER TABLE llx_extra_fields_options ADD INDEX idx_extra_fields_options_fk_extra_fields (fk_extra_fields);
ALTER TABLE llx_extra_fields_options ADD CONSTRAINT fk_extra_fields_options_fk_extra_fields FOREIGN KEY (fk_extra_fields) REFERENCES llx_extra_fields (rowid);

-- Create table of values
create table llx_extra_fields_values
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  entity                integer  DEFAULT 1 NOT NULL,	-- multi company id
  datec					datetime,
  datem					datetime,
  fk_object 			integer NOT NULL,
  fk_extra_fields		integer NOT NULL,
  value					varchar(255),
  fk_user_create 		integer,
  fk_user_modif 		integer
)type=innodb;

ALTER TABLE llx_extra_fields_values ADD INDEX idx_extra_fields_values_fk_extra_fields (fk_extra_fields, entity);
ALTER TABLE llx_extra_fields_values ADD CONSTRAINT fk_extra_fields_values_fk_extra_fields FOREIGN KEY (fk_extra_fields) REFERENCES llx_extra_fields (rowid);

ALTER TABLE llx_bank_class DROP INDEX idx_bank_class_lineid;
ALTER TABLE llx_bank_class DROP INDEX uk_bank_class_lineid;
ALTER TABLE llx_bank_class ADD UNIQUE INDEX uk_bank_class_lineid (lineid, fk_categ);

ALTER TABLE llx_rights_def MODIFY COLUMN module varchar(64);

-- Enhancement of project tasks
ALTER TABLE llx_projet_task ADD COLUMN datec datetime AFTER fk_task_parent;
ALTER TABLE llx_projet_task ADD COLUMN tms timestamp AFTER datec;
ALTER TABLE llx_projet_task ADD COLUMN dateo datetime AFTER tms;
ALTER TABLE llx_projet_task ADD COLUMN datee datetime AFTER dateo;
ALTER TABLE llx_projet_task ADD COLUMN datev datetime AFTER datee;
ALTER TABLE llx_projet_task CHANGE title label varchar(255) NOT NULL;
ALTER TABLE llx_projet_task ADD COLUMN description varchar(255) AFTER label;
ALTER TABLE llx_projet_task ADD COLUMN fk_user_modif integer AFTER fk_user_creat;
ALTER TABLE llx_projet_task ADD COLUMN fk_user_valid integer AFTER fk_user_modif;
UPDATE llx_projet_task SET statut='1' WHERE statut='open';
ALTER TABLE llx_projet_task CHANGE statut fk_statut smallint DEFAULT 0 NOT NULL;
ALTER TABLE llx_projet_task CHANGE note note_private text;
ALTER TABLE llx_projet_task ADD COLUMN note_public text AFTER note_private;

