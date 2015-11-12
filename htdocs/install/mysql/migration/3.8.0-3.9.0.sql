--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.9.0 or higher.
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):    VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres): VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE
-- To set a field as NULL:                     VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as default NULL:             VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


-- Was done into a 3.8 fix, so we must do it also in 3.9 
ALTER TABLE llx_don ADD COLUMN fk_country integer NOT NULL DEFAULT 0 after country;


-- Fix bad data
update llx_opensurvey_sondage set format = 'D' where format = 'D+';
update llx_opensurvey_sondage set format = 'A' where format = 'A+';

INSERT INTO llx_const (name, value, type, note, visible) values (__ENCRYPT('MAIN_DELAY_EXPENSEREPORTS_TO_PAY')__,__ENCRYPT('31')__,'chaine','Tolérance de retard avant alerte (en jours) sur les notes de frais impayées',0);
INSERT INTO llx_const (name, value, type, note, visible) values ('MAIN_SIZE_SHORTLISTE_LIMIT','4','chaine','Longueur maximum des listes courtes (fiche client)',0);

ALTER TABLE llx_accounting_system MODIFY COLUMN pcg_version varchar(32);
ALTER TABLE llx_accountingaccount MODIFY COLUMN fk_pcg_version varchar(32);
ALTER TABLE llx_accountingaccount RENAME TO llx_accounting_account;
ALTER TABLE llx_accounting_account ADD INDEX idx_accounting_account_account_number (account_number);

UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_EXPORT_PREFIX_SPEC')__ WHERE __DECRYPT('name')__ = 'EXPORT_PREFIX_SPEC';

UPDATE llx_const set value = __ENCRYPT('eldy')__ WHERE __DECRYPT('value')__ = 'auguria';
UPDATE llx_const set value = __ENCRYPT('eldy')__ WHERE __DECRYPT('value')__ = 'bureau2crea';
UPDATE llx_const set value = __ENCRYPT('eldy')__ WHERE __DECRYPT('value')__ = 'amarok';
UPDATE llx_const set value = __ENCRYPT('eldy')__ WHERE __DECRYPT('value')__ = 'cameleo';

ALTER TABLE llx_societe ADD COLUMN model_pdf varchar(255);

ALTER TABLE llx_societe_commerciaux ADD COLUMN import_key varchar(14) AFTER fk_user;

ALTER TABLE llx_categorie ADD COLUMN color varchar(8);

create table llx_overwrite_trans
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  lang            varchar(5),	-- en_US, fr_FR ...
  transkey	      varchar(128),
  transvalue      text
)ENGINE=innodb;

ALTER TABLE llx_payment_salary ADD COLUMN datec datetime AFTER tms;
ALTER TABLE llx_payment_salary CHANGE COLUMN fk_user_creat fk_user_author integer;

ALTER TABLE llx_adherent ADD COLUMN pass_crypted varchar(128) AFTER pass;

ALTER TABLE llx_paiement ADD COLUMN ref varchar(30) NOT NULL DEFAULT '' AFTER rowid;

ALTER TABLE llx_socpeople ADD COLUMN photo varchar(255) AFTER skype;

ALTER TABLE llx_user_param MODIFY COLUMN param varchar(255) NOT NULL DEFAULT '';
ALTER TABLE llx_user_param MODIFY COLUMN value text NOT NULL DEFAULT '';

ALTER TABLE llx_expedition ADD COLUMN import_key varchar(14);
ALTER TABLE llx_expedition ADD COLUMN extraparams varchar(255);

ALTER TABLE llx_bank_account MODIFY COLUMN code_banque varchar(128);
ALTER TABLE llx_prelevement_facture_demande MODIFY COLUMN code_banque varchar(128);
ALTER TABLE llx_prelevement_lignes MODIFY COLUMN code_banque varchar(128);
ALTER TABLE llx_societe_rib MODIFY COLUMN code_banque varchar(128);

ALTER TABLE llx_contrat ADD COLUMN ref_customer varchar(30);
ALTER TABLE llx_commande ADD COLUMN fk_warehouse integer DEFAULT NULL AFTER fk_shipping_method;

ALTER TABLE llx_ecm_directories MODIFY COLUMN fullpath varchar(750);
ALTER TABLE llx_ecm_directories DROP INDEX idx_ecm_directories;
ALTER TABLE llx_ecm_directories ADD UNIQUE INDEX uk_ecm_directories (label, fk_parent, entity);
--ALTER TABLE llx_ecm_directories ADD UNIQUE INDEX uk_ecm_directories_fullpath(fullpath);


CREATE TABLE llx_ecm_files
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  label				varchar(64) NOT NULL,
  entity			integer DEFAULT 1 NOT NULL,		-- multi company id
  filename          varchar(255) NOT NULL,			-- file name only without any directory
  fullpath    		varchar(750) NOT NULL,	        -- relative to dolibarr document dir. example abc/def/myfile. restricted to 750 because of unique key index on it.
  fullpath_orig		varchar(2048),    	            -- full path of original filename, when file is uploaded from a local computer
  description		text,
  keywords          text,                           -- list of keywords, separated with comma
  cover             text,                           -- is this file a file to use for a cover
  extraparams		varchar(255),					-- for stock other parameters with json format
  date_c			datetime,
  date_m			timestamp,
  fk_user_c			integer,
  fk_user_m			integer,
  acl				text							-- for future permission 'per file'
) ENGINE=innodb;

ALTER TABLE llx_ecm_files ADD UNIQUE INDEX uk_ecm_files (label, entity);
--ALTER TABLE llx_ecm_files ADD UNIQUE INDEX uk_ecm_files_fullpath(fullpath);


ALTER TABLE llx_product ADD COLUMN onportal smallint DEFAULT 0 AFTER tobuy;


ALTER TABLE llx_user ADD COLUMN employee smallint DEFAULT 1 AFTER ref_int;
ALTER TABLE llx_user ADD COLUMN fk_establishment integer DEFAULT 0 AFTER employee;


CREATE TABLE IF NOT EXISTS llx_c_hrm_function
(
  rowid     integer     PRIMARY KEY,
  pos   	smallint DEFAULT 0 NOT NULL,
  code    	varchar(16) NOT NULL,
  label 	varchar(50),
  c_level   smallint DEFAULT 0 NOT NULL,
  active  	smallint DEFAULT 1  NOT NULL
)ENGINE=innodb;

INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(1,  5, 'EXECBOARD', 'Executive board', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(2, 10, 'MANAGDIR', 'Managing director', 1, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(3, 15, 'ACCOUNTMANAG', 'Account manager', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(4, 20, 'ENGAGDIR', 'Engagement director', 1, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(5, 25, 'DIRECTOR', 'Director', 1, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(6, 30, 'PROJMANAG', 'Project manager', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(7, 35, 'DEPHEAD', 'Department head', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(8, 40, 'SECRETAR', 'Secretary', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(9, 45, 'EMPLOYEE', 'Department employee', 0, 1);

CREATE TABLE IF NOT EXISTS llx_c_hrm_department
(
  rowid      	integer     PRIMARY KEY,
  pos   		smallint DEFAULT 0 NOT NULL,
  code    		varchar(16) NOT NULL,
  label 		varchar(50),
  active  		smallint DEFAULT 1  NOT NULL
)ENGINE=innodb;

INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(1, 5,'MANAGEMENT', 'Management', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(2, 10,'GESTION', 'Gestion', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(3, 15,'TRAINING', 'Training', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(4, 20,'IT', 'Inform. Technology (IT)', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(5, 25,'MARKETING', 'Marketing', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(6, 30,'SALES', 'Sales', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(7, 35,'LEGAL', 'Legal', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(8, 40,'FINANCIAL', 'Financial accounting', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(9, 45,'HUMANRES', 'Human resources', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(10, 50,'PURCHASING', 'Purchasing', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(11, 55,'SERVICES', 'Services', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(12, 60,'CUSTOMSERV', 'Customer service', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(13, 65,'CONSULTING', 'Consulting', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(14, 70,'LOGISTIC', 'Logistics', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(15, 75,'CONSTRUCT', 'Engineering/design', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(16, 80,'PRODUCTION', 'Manufacturing', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(17, 85,'QUALITY', 'Quality assurance', 1);
INSERT INTO llx_c_hrm_department (rowid, pos, code, label, active) VALUES(18, 85,'MAINT', 'Plant assurance', 1);

CREATE TABLE IF NOT EXISTS llx_establishment (
  rowid 			integer NOT NULL auto_increment PRIMARY KEY,
  entity 			integer NOT NULL DEFAULT 1,
  name				varchar(50),
  address           varchar(255),
  zip               varchar(25),
  town              varchar(50),
  fk_state          integer DEFAULT 0,
  fk_country        integer DEFAULT 0,
  profid1			varchar(20),
  profid2			varchar(20),
  profid3			varchar(20),
  phone				varchar(20),
  fk_user_author 	integer NOT NULL,
  fk_user_mod		integer NOT NULL,
  datec				datetime NOT NULL,
  tms				timestamp NOT NULL,
  status            smallint DEFAULT 1
) ENGINE=InnoDB;



ALTER TABLE llx_projet_task_time ADD COLUMN invoice_id integer DEFAULT NULL;
ALTER TABLE llx_projet_task_time ADD COLUMN invoice_line_id integer DEFAULT NULL;


create table llx_stock_lotserial
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  entity          integer,
  fk_product      integer NOT NULL,				-- Id of product
  batch           varchar(30) DEFAULT NULL,		-- Lot or serial number
  eatby           date DEFAULT NULL,			-- Eatby date
  sellby          date DEFAULT NULL, 			-- Sellby date
  datec         datetime,
  tms           timestamp,
  fk_user_creat integer,
  fk_user_modif integer,
  import_key    integer  
) ENGINE=innodb;


-- Add budget tables

create table llx_budget
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  entity		integer NOT NULL DEFAULT 1,
  label         varchar(255) NOT NULL,
  status        integer,
  note			text,	
  date_start	date,
  date_end		date,
  datec         datetime,
  tms           timestamp,
  fk_user_creat integer,
  fk_user_modif integer,
  import_key    integer  
)ENGINE=innodb;


create table llx_budget_lines
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  fk_budget     integer NOT NULL,
  fk_project_ids	varchar(255) NOT NULL,		-- List of project ids related to this budget. If budget is dedicated to projects not yet started, we recommand to create a project "Projects to come".
  amount		double(24,8) NOT NULL,
  datec         datetime,
  tms           timestamp,
  fk_user_creat integer,
  fk_user_modif integer,
  import_key    integer  
)ENGINE=innodb;

ALTER TABLE llx_budget_lines ADD UNIQUE INDEX uk_budget_lines (fk_budget, fk_project_ids);

-- Supprime orphelins pour permettre montee de la cle
-- MYSQL V4 DELETE llx_budget_lines FROM llx_budget_lines LEFT JOIN llx_budget ON llx_budget.rowid = llx_budget_lines.fk_budget WHERE llx_budget_lines.rowid IS NULL;
-- POSTGRESQL V8 DELETE FROM llx_budget_lines USING llx_budget WHERE llx_budget_lines.fk_budget NOT IN (SELECT llx_budget.rowid FROM llx_budget);

ALTER TABLE llx_budget_lines ADD CONSTRAINT fk_budget_lines_budget FOREIGN KEY (fk_budget) REFERENCES llx_budget (rowid);


-- Add position field
ALTER TABLE llx_c_typent ADD COLUMN position integer NOT NULL DEFAULT 0;
ALTER TABLE llx_c_forme_juridique ADD COLUMN position integer NOT NULL DEFAULT 0;
ALTER TABLE llx_c_type_fees ADD COLUMN position integer NOT NULL DEFAULT 0;


-- NEW Level multiprice generator based on per cent variations over base price
CREATE TABLE llx_product_pricerules
(
    rowid INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    level INT NOT NULL, -- Which price level is this rule for?
    fk_level INT NOT NULL, -- Price variations are made over price of X
    var_percent FLOAT NOT NULL, -- Price variation over based price
    var_min_percent FLOAT NOT NULL -- Min price discount over general price
);
ALTER TABLE llx_product ADD COLUMN price_autogen smallint DEFAULT 0;
ALTER TABLE llx_product_pricerules ADD CONSTRAINT unique_level UNIQUE (level);


-- Delete deprecated fields
ALTER TABLE llx_opensurvey_sondage DROP COLUMN survey_link_visible;
ALTER TABLE llx_opensurvey_sondage DROP INDEX idx_id_sondage_admin;
ALTER TABLE llx_opensurvey_sondage DROP COLUMN id_sondage_admin;
ALTER TABLE llx_opensurvey_sondage DROP COLUMN canedit;
ALTER TABLE llx_opensurvey_sondage DROP COLUMN origin;

DROP TABLE llx_opensurvey_sujet_studs;

CREATE TABLE llx_opensurvey_formquestions (
	rowid INTEGER AUTO_INCREMENT NOT NULL PRIMARY KEY,
	id_sondage VARCHAR(16),
	question TEXT,
    available_answers TEXT								-- List of available answers
) ENGINE=InnoDB;

CREATE TABLE llx_opensurvey_user_formanswers (
    fk_user_survey INTEGER NOT NULL,
    fk_question INTEGER NOT NULL,
    reponses TEXT
) ENGINE=InnoDB;




create table llx_categorie_project
(
  fk_categorie  integer NOT NULL,
  fk_project    integer NOT NULL,
  import_key    varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_categorie_project ADD PRIMARY KEY pk_categorie_project (fk_categorie, fk_project);
ALTER TABLE llx_categorie_project ADD INDEX idx_categorie_project_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_project ADD INDEX idx_categorie_project_fk_project (fk_project);

ALTER TABLE llx_categorie_project ADD CONSTRAINT fk_categorie_project_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_project ADD CONSTRAINT fk_categorie_project_fk_project   FOREIGN KEY (fk_project) REFERENCES llx_projet (rowid);





