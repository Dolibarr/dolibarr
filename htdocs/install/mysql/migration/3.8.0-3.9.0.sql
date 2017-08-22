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
-- To drop an index:        -- VMYSQL4.0 DROP INDEX nomindex on llx_table
-- To drop an index:        -- VPGSQL8.0 DROP INDEX nomindex
-- To restrict request to Mysql version x.y minimum use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y minimum use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):    VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres): VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE
-- To set a field as NULL:                     VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as default NULL:             VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- Note: fields with type BLOB/TEXT can't have default value.
-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_ENABLE_LOG_TO_HTML')__,__ENCRYPT('0')__,'chaine','If this option is set to 1, it is possible to see log output at end of HTML sources by adding paramater logtohtml=1 on URL',1,0);


-- Was done into a 3.8 fix, so we must do it also in 3.9 
ALTER TABLE llx_don ADD COLUMN fk_country integer NOT NULL DEFAULT 0 after country;


ALTER TABLE llx_product ADD COLUMN localtax1_type varchar(10)  NOT NULL DEFAULT '0' after localtax1_tx; 
ALTER TABLE llx_product ADD COLUMN localtax2_type varchar(10)  NOT NULL DEFAULT '0' after localtax2_tx; 
ALTER TABLE llx_product_price ADD COLUMN localtax1_type varchar(10)  NOT NULL DEFAULT '0' after localtax1_tx; 
ALTER TABLE llx_product_price ADD COLUMN localtax2_type varchar(10)  NOT NULL DEFAULT '0' after localtax2_tx; 
ALTER TABLE llx_product_customer_price ADD COLUMN localtax1_type varchar(10)  NOT NULL DEFAULT '0' after localtax1_tx; 
ALTER TABLE llx_product_customer_price ADD COLUMN localtax2_type varchar(10)  NOT NULL DEFAULT '0' after localtax2_tx; 
ALTER TABLE llx_product_customer_price_log ADD COLUMN localtax1_type varchar(10)  NOT NULL DEFAULT '0' after localtax1_tx; 
ALTER TABLE llx_product_customer_price_log ADD COLUMN localtax2_type varchar(10)  NOT NULL DEFAULT '0' after localtax2_tx; 


UPDATE llx_user set api_key = null where api_key = '';


ALTER TABLE llx_actioncomm ADD COLUMN email_subject varchar(256) after email_msgid;
ALTER TABLE llx_actioncomm ADD COLUMN email_tocc varchar(256) after email_to;
ALTER TABLE llx_actioncomm ADD COLUMN email_tobcc varchar(256) after email_tocc;


ALTER TABLE llx_user MODIFY COLUMN pass varchar(128);
ALTER TABLE llx_user MODIFY COLUMN pass_temp varchar(128); 

ALTER TABLE llx_askpricesupplier RENAME TO llx_supplier_proposal;
ALTER TABLE llx_askpricesupplierdet RENAME TO llx_supplier_proposaldet;
ALTER TABLE llx_askpricesupplier_extrafields RENAME TO llx_supplier_proposal_extrafields;
ALTER TABLE llx_askpricesupplierdet_extrafields RENAME TO llx_supplier_proposaldet_extrafields;
ALTER TABLE llx_supplier_proposaldet CHANGE COLUMN fk_askpricesupplier fk_supplier_proposal integer NOT NULL;

-- Fix bad data
update llx_opensurvey_sondage set format = 'D' where format = 'D+';
update llx_opensurvey_sondage set format = 'A' where format = 'A+';

INSERT INTO llx_const (name, value, type, note, visible) values (__ENCRYPT('MAIN_DELAY_EXPENSEREPORTS_TO_PAY')__,__ENCRYPT('31')__,'chaine','Tolérance de retard avant alerte (en jours) sur les notes de frais impayées',0);
INSERT INTO llx_const (name, value, type, note, visible) values (__ENCRYPT('MAIN_SIZE_SHORTLISTE_LIMIT')__,__ENCRYPT('3')__,'chaine','Max length for small lists (tabs)',0);

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

ALTER TABLE llx_cronjob ADD COLUMN maxrun     integer NOT NULL DEFAULT 0;
ALTER TABLE llx_cronjob ADD COLUMN autodelete integer DEFAULT 0;
ALTER TABLE llx_cronjob ADD COLUMN fk_mailing integer DEFAULT NULL;


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

UPDATE llx_user_param SET param='ToDelete' WHERE param IS NULL;
ALTER TABLE llx_user_param MODIFY COLUMN param varchar(255) NOT NULL;
ALTER TABLE llx_user_param MODIFY COLUMN value text NOT NULL;

ALTER TABLE llx_expedition ADD COLUMN import_key varchar(14);
ALTER TABLE llx_expedition ADD COLUMN extraparams varchar(255);

ALTER TABLE llx_bank_account MODIFY COLUMN code_banque varchar(128);
ALTER TABLE llx_prelevement_facture_demande MODIFY COLUMN code_banque varchar(128);
ALTER TABLE llx_prelevement_lignes MODIFY COLUMN code_banque varchar(128);
ALTER TABLE llx_societe_rib MODIFY COLUMN code_banque varchar(128);

ALTER TABLE llx_contrat ADD COLUMN ref_customer varchar(30);
ALTER TABLE llx_commande ADD COLUMN fk_warehouse integer DEFAULT NULL AFTER fk_shipping_method;

ALTER TABLE llx_commande_fournisseur ADD COLUMN billed smallint DEFAULT 0 AFTER fk_statut;
ALTER TABLE llx_commande_fournisseur ADD INDEX billed (billed);

UPDATE llx_commande_fournisseur set billed=1 where statut = 8;
UPDATE llx_commande_fournisseur set statut=5 where statut = 8 and billed=1;

ALTER TABLE llx_product ADD COLUMN cost_price	double(24,8) DEFAULT NULL;

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

CREATE TABLE IF NOT EXISTS llx_user_rib (
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_user        integer      NOT NULL,
  entity         integer DEFAULT 1 NOT NULL,	-- multi company id
  datec          datetime,
  tms            timestamp,
  label          varchar(30),
  bank           varchar(255),  -- bank name
  code_banque    varchar(128),  -- bank code
  code_guichet   varchar(6),    -- desk code
  number         varchar(255),  -- account number
  cle_rib        varchar(5),    -- key of bank account
  bic            varchar(11),   -- 11 according to ISO 9362
  iban_prefix    varchar(34),	-- full iban. 34 according to ISO 13616
  domiciliation  varchar(255),
  proprio        varchar(60),
  owner_address  varchar(255)
)ENGINE=innodb;

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
  fk_project_ids	varchar(255) NOT NULL,		-- List of project ids related to this budget. If budget is dedicated to projects not yet started, we recommand to create a project 'Projects to come'.
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




-- Extrafields Expedition (shipment)
create table llx_expedition_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_expedition_extrafields ADD INDEX idx_expedition_extrafields (fk_object);

create table llx_expeditiondet_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_expeditiondet_extrafields ADD INDEX idx_expeditiondet_extrafields (fk_object);



-- Extrafields Expedition (delivery receipts)
create table llx_livraison_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_livraison_extrafields ADD INDEX idx_livraison_extrafields (fk_object);

create table llx_livraisondet_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_livraisondet_extrafields ADD INDEX idx_livraisondet_extrafields (fk_object);


ALTER TABLE llx_categorie_project ADD PRIMARY KEY pk_categorie_project (fk_categorie, fk_project);
ALTER TABLE llx_categorie_project ADD INDEX idx_categorie_project_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_project ADD INDEX idx_categorie_project_fk_project (fk_project);

ALTER TABLE llx_categorie_project ADD CONSTRAINT fk_categorie_project_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_project ADD CONSTRAINT fk_categorie_project_fk_project   FOREIGN KEY (fk_project) REFERENCES llx_projet (rowid);


ALTER TABLE llx_c_tva ADD COLUMN code varchar(10) DEFAULT '' after fk_pays;
-- VMYSQL4.0 DROP INDEX uk_c_tva_id ON llx_c_tva;
-- VPGSQL8.0 DROP INDEX uk_c_tva_id;
ALTER TABLE llx_c_tva ADD UNIQUE INDEX uk_c_tva_id (fk_pays, code, taux, recuperableonly);

-- Regions Bolivia (id country=52)
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom, active) values ( 52, 5201, '', 0, 'Chuquisaca', 1);
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom, active) values ( 52, 5202, '', 0, 'La Paz', 1);
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom, active) values ( 52, 5203, '', 0, 'Cochabamba', 1);
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom, active) values ( 52, 5204, '', 0, 'Oruro', 1);
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom, active) values ( 52, 5205, '', 0, 'Potosí', 1);
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom, active) values ( 52, 5206, '', 0, 'Tarija', 1);
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom, active) values ( 52, 5207, '', 0, 'Santa Cruz', 1);
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom, active) values ( 52, 5208, '', 0, 'El Beni', 1);
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom, active) values ( 52, 5209, '', 0, 'Pando', 1);

-- Provinces Bolivia (id country=52)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('001', 5201, '', 0, '', 'Belisario Boeto', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('002', 5201, '', 0, '', 'Hernando Siles', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('003', 5201, '', 0, '', 'Jaime Zudáñez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('004', 5201, '', 0, '', 'Juana Azurduy de Padilla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('005', 5201, '', 0, '', 'Luis Calvo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('006', 5201, '', 0, '', 'Nor Cinti', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('007', 5201, '', 0, '', 'Oropeza', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('008', 5201, '', 0, '', 'Sud Cinti', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('009', 5201, '', 0, '', 'Tomina', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('010', 5201, '', 0, '', 'Yamparáez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('011', 5202, '', 0, '', 'Abel Iturralde', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('012', 5202, '', 0, '', 'Aroma', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('013', 5202, '', 0, '', 'Bautista Saavedra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('014', 5202, '', 0, '', 'Caranavi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('015', 5202, '', 0, '', 'Eliodoro Camacho', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('016', 5202, '', 0, '', 'Franz Tamayo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('017', 5202, '', 0, '', 'Gualberto Villarroel', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('018', 5202, '', 0, '', 'Ingaví', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('019', 5202, '', 0, '', 'Inquisivi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('020', 5202, '', 0, '', 'José Ramón Loayza', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('021', 5202, '', 0, '', 'Larecaja', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('022', 5202, '', 0, '', 'Los Andes (Bolivia)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('023', 5202, '', 0, '', 'Manco Kapac', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('024', 5202, '', 0, '', 'Muñecas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('025', 5202, '', 0, '', 'Nor Yungas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('026', 5202, '', 0, '', 'Omasuyos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('027', 5202, '', 0, '', 'Pacajes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('028', 5202, '', 0, '', 'Pedro Domingo Murillo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('029', 5202, '', 0, '', 'Sud Yungas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('030', 5202, '', 0, '', 'General José Manuel Pando', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('031', 5203, '', 0, '', 'Arani', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('032', 5203, '', 0, '', 'Arque', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('033', 5203, '', 0, '', 'Ayopaya', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('034', 5203, '', 0, '', 'Bolívar (Bolivia)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('035', 5203, '', 0, '', 'Campero', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('036', 5203, '', 0, '', 'Capinota', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('037', 5203, '', 0, '', 'Cercado (Cochabamba)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('038', 5203, '', 0, '', 'Esteban Arze', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('039', 5203, '', 0, '', 'Germán Jordán', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('040', 5203, '', 0, '', 'José Carrasco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('041', 5203, '', 0, '', 'Mizque', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('042', 5203, '', 0, '', 'Punata', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('043', 5203, '', 0, '', 'Quillacollo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('044', 5203, '', 0, '', 'Tapacarí', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('045', 5203, '', 0, '', 'Tiraque', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('046', 5203, '', 0, '', 'Chapare', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('047', 5204, '', 0, '', 'Carangas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('048', 5204, '', 0, '', 'Cercado (Oruro)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('049', 5204, '', 0, '', 'Eduardo Avaroa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('050', 5204, '', 0, '', 'Ladislao Cabrera', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('051', 5204, '', 0, '', 'Litoral de Atacama', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('052', 5204, '', 0, '', 'Mejillones', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('053', 5204, '', 0, '', 'Nor Carangas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('054', 5204, '', 0, '', 'Pantaleón Dalence', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('055', 5204, '', 0, '', 'Poopó', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('056', 5204, '', 0, '', 'Sabaya', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('057', 5204, '', 0, '', 'Sajama', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('058', 5204, '', 0, '', 'San Pedro de Totora', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('059', 5204, '', 0, '', 'Saucarí', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('060', 5204, '', 0, '', 'Sebastián Pagador', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('061', 5204, '', 0, '', 'Sud Carangas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('062', 5204, '', 0, '', 'Tomás Barrón', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('063', 5205, '', 0, '', 'Alonso de Ibáñez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('064', 5205, '', 0, '', 'Antonio Quijarro', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('065', 5205, '', 0, '', 'Bernardino Bilbao', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('066', 5205, '', 0, '', 'Charcas (Potosí)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('067', 5205, '', 0, '', 'Chayanta', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('068', 5205, '', 0, '', 'Cornelio Saavedra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('069', 5205, '', 0, '', 'Daniel Campos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('070', 5205, '', 0, '', 'Enrique Baldivieso', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('071', 5205, '', 0, '', 'José María Linares', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('072', 5205, '', 0, '', 'Modesto Omiste', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('073', 5205, '', 0, '', 'Nor Chichas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('074', 5205, '', 0, '', 'Nor Lípez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('075', 5205, '', 0, '', 'Rafael Bustillo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('076', 5205, '', 0, '', 'Sud Chichas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('077', 5205, '', 0, '', 'Sud Lípez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('078', 5205, '', 0, '', 'Tomás Frías', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('079', 5206, '', 0, '', 'Aniceto Arce', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('080', 5206, '', 0, '', 'Burdet O''Connor', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('081', 5206, '', 0, '', 'Cercado (Tarija)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('082', 5206, '', 0, '', 'Eustaquio Méndez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('083', 5206, '', 0, '', 'José María Avilés', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('084', 5206, '', 0, '', 'Gran Chaco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('085', 5207, '', 0, '', 'Andrés Ibáñez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('086', 5207, '', 0, '', 'Caballero', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('087', 5207, '', 0, '', 'Chiquitos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('088', 5207, '', 0, '', 'Cordillera (Bolivia)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('089', 5207, '', 0, '', 'Florida', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('090', 5207, '', 0, '', 'Germán Busch', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('091', 5207, '', 0, '', 'Guarayos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('092', 5207, '', 0, '', 'Ichilo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('093', 5207, '', 0, '', 'Obispo Santistevan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('094', 5207, '', 0, '', 'Sara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('095', 5207, '', 0, '', 'Vallegrande', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('096', 5207, '', 0, '', 'Velasco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('097', 5207, '', 0, '', 'Warnes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('098', 5207, '', 0, '', 'Ángel Sandóval', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('099', 5207, '', 0, '', 'Ñuflo de Chaves', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('100', 5208, '', 0, '', 'Cercado (Beni)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('101', 5208, '', 0, '', 'Iténez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('102', 5208, '', 0, '', 'Mamoré', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('103', 5208, '', 0, '', 'Marbán', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('104', 5208, '', 0, '', 'Moxos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('105', 5208, '', 0, '', 'Vaca Díez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('106', 5208, '', 0, '', 'Yacuma', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('107', 5208, '', 0, '', 'General José Ballivián Segurola', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('108', 5209, '', 0, '', 'Abuná', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('109', 5209, '', 0, '', 'Madre de Dios', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('110', 5209, '', 0, '', 'Manuripi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('111', 5209, '', 0, '', 'Nicolás Suárez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('112', 5209, '', 0, '', 'General Federico Román', 1);



-- Regions Austria (id country=41)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom, active) values (  41, 4101, '', 0, 'Österreich', 1);

-- Provinces Austria (id country=41)
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101,'B','BURGENLAND','Burgenland',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101,'K','KAERNTEN','Kärnten',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101,'N','NIEDEROESTERREICH','Niederösterreich',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101,'O','OBEROESTERREICH','Oberösterreich',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101,'S','SALZBURG','Salzburg',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101,'ST','STEIERMARK','Steiermark',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101,'T','TIROL','Tirol',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101,'V','VORARLBERG','Vorarlberg',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101,'W','WIEN','Wien',1);


-- Juridical status Austria
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4100', 'GmbH - Gesellschaft mit beschränkter Haftung', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4101', 'GesmbH - Gesellschaft mit beschränkter Haftung', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4102', 'AG - Aktiengesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4103', 'EWIV - Europäische wirtschaftliche Interessenvereinigung', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4104', 'KEG - Kommanditerwerbsgesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4105', 'OEG - Offene Erwerbsgesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4106', 'OHG - Offene Handelsgesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4107', 'AG & Co KG - Kommanditgesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4108', 'GmbH & Co KG - Kommanditgesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4109', 'KG - Kommanditgesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4110', 'OG - Offene Gesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4111', 'GbR - Gesellschaft nach bürgerlichem Recht', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4112', 'GesbR - Gesellschaft nach bürgerlichem Recht', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4113', 'GesnbR - Gesellschaft nach bürgerlichem Recht', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4114', 'e.U. - eingetragener Einzelunternehmer', 1);

-- Social contributions Austria
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4101, 'Krankenversicherung',				1,1,'TAXATKV'   ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4102, 'Unfallversicherung',				1,1,'TAXATUV'   ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4103, 'Pensionsversicherung',				1,1,'TAXATPV'   ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4104, 'Arbeitslosenversicherung',			1,1,'TAXATAV'   ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4105, 'Insolvenzentgeltsicherungsfond',   1,1,'TAXATIESG' ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4106, 'Wohnbauförderung',					1,1,'TAXATWF'   ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4107, 'Arbeiterkammerumlage',				1,1,'TAXATAK'   ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4108, 'Mitarbeitervorsorgekasse',			1,1,'TAXATMVK'  ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4109, 'Familienlastenausgleichsfond',		1,1,'TAXATFLAF' ,'41');

ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN doc_ref varchar(300) NOT NULL;

ALTER TABLE llx_holiday ADD COLUMN tms timestamp;
ALTER TABLE llx_holiday ADD COLUMN entity integer DEFAULT 1 NOT NULL;
ALTER TABLE llx_holiday ADD INDEX idx_holiday_entity (entity);

-- Fix Argentina provences
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2326', 2305, '', 0, 'MISIONES', 'Misiones', 1);
UPDATE llx_c_departements SET ncc = 'FORMOSA', nom = 'Formosa' WHERE nom = 'Formosa Misiones';

-- MALTA VATS (id country=148)
INSERT INTO llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) VALUES (1481,  148, '18','0','VAT standard rate',1);
INSERT INTO llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) VALUES (1482,  148, '7','0','VAT reduced rate',1);
INSERT INTO llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) VALUES (1483,  148, '5','0','VAT super-reduced rate', 1);
INSERT INTO llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) VALUES (1484,  148, '0','0','VAT Rate 0', 1);

-- VMYSQL4.1 ALTER TABLE llx_c_type_resource CHANGE COLUMN rowid rowid integer NOT NULL AUTO_INCREMENT;

ALTER TABLE llx_import_model MODIFY COLUMN type varchar(50);

-- Negative buying prices

UPDATE llx_facturedet SET buy_price_ht = ABS(buy_price_ht)
