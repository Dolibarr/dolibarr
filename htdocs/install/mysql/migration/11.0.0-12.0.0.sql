--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 12.0.0 or higher.
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


-- Missing in v11

create table llx_commande_fournisseur_dispatch_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_commande_fournisseur_dispatch_extrafields ADD INDEX idx_commande_fournisseur_dispatch_extrafields (fk_object);

UPDATE llx_accounting_system SET fk_country = NULL, active = 0 WHERE pcg_version = 'SYSCOHADA';



-- For v12

UPDATE llx_website SET lang = 'en' WHERE lang like 'en_%';
UPDATE llx_website SET lang = 'fr' WHERE lang like 'fr_%';
UPDATE llx_website SET lang = 'es' WHERE lang like 'es_%';
UPDATE llx_website SET lang = 'de' WHERE lang like 'de_%';
UPDATE llx_website SET lang = 'it' WHERE lang like 'it_%';
UPDATE llx_website SET lang = 'pt' WHERE lang like 'pt_%';
UPDATE llx_website_page SET lang = 'en' WHERE lang like 'en_%';
UPDATE llx_website_page SET lang = 'fr' WHERE lang like 'fr_%';
UPDATE llx_website_page SET lang = 'es' WHERE lang like 'es_%';
UPDATE llx_website_page SET lang = 'de' WHERE lang like 'de_%';
UPDATE llx_website_page SET lang = 'it' WHERE lang like 'it_%';
UPDATE llx_website_page SET lang = 'pt' WHERE lang like 'pt_%';

ALTER TABLE llx_website ADD COLUMN lang varchar(8);
ALTER TABLE llx_website ADD COLUMN otherlang varchar(255); 

ALTER TABLE llx_website_page ADD COLUMN author_alias varchar(64);

ALTER TABLE llx_holiday_users DROP INDEX uk_holiday_users;
ALTER TABLE llx_holiday_users ADD UNIQUE INDEX uk_holiday_users(fk_user, fk_type);

ALTER TABLE llx_ticket ADD COLUMN import_key varchar(14);

--ALTER TABLE llx_facturerec DROP COLUMN vat_src_code;


-- Migration to the new regions (France)
UPDATE llx_c_regions set nom = 'Centre-Val de Loire' WHERE fk_pays = 1 AND code_region = 24;
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 27, '21231', 0, 'Bourgogne-Franche-Comté');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 28, '76540', 0, 'Normandie');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 32, '59350', 4, 'Hauts-de-France');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 44, '67482', 2, 'Grand Est');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 75, '33063', 0, 'Nouvelle-Aquitaine');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 76, '31355', 1, 'Occitanie');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (1, 84, '69123', 1, 'Auvergne-Rhône-Alpes');

UPDATE llx_c_departements set fk_region = 27 WHERE fk_region = 26 OR fk_region = 43;
UPDATE llx_c_departements set fk_region = 28 WHERE fk_region = 25 OR fk_region = 23;
UPDATE llx_c_departements set fk_region = 32 WHERE fk_region = 22 OR fk_region = 31;
UPDATE llx_c_departements set fk_region = 44 WHERE fk_region = 21 OR fk_region = 41 OR fk_region = 42;
UPDATE llx_c_departements set fk_region = 75 WHERE fk_region = 54 OR fk_region = 74 OR fk_region = 72;
UPDATE llx_c_departements set fk_region = 76 WHERE fk_region = 73 OR fk_region = 91;
UPDATE llx_c_departements set fk_region = 84 WHERE fk_region = 82 OR fk_region = 83;

DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 21;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 22;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 23;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 25;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 26;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 31;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 41;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 42;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 43;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 54;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 72;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 73;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 74;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 82;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 83;
DELETE FROM llx_c_regions WHERE fk_pays = 1 AND code_region = 91;

ALTER TABLE llx_bookmark DROP INDEX uk_bookmark_url;
ALTER TABLE llx_bookmark DROP INDEX uk_bookmark_title;

ALTER TABLE llx_bookmark MODIFY COLUMN url TEXT;

ALTER TABLE llx_bookmark ADD UNIQUE uk_bookmark_title (fk_user, entity, title);


ALTER TABLE llx_societe_rib ADD COLUMN stripe_account varchar(128);

create table llx_object_lang
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_object      integer      DEFAULT 0 NOT NULL,
  type_object    varchar(32)  NOT NULL,
  property       varchar(32)  NOT NULL,
  lang           varchar(5)   DEFAULT 0 NOT NULL,
  value          text,
  import_key varchar(14) DEFAULT NULL
)ENGINE=innodb;


ALTER TABLE llx_object_lang ADD UNIQUE INDEX uk_object_lang (fk_object, type_object, property, lang);


CREATE TABLE llx_categorie_actioncomm
(
  fk_categorie integer NOT NULL,
  fk_actioncomm integer NOT NULL,
  import_key varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_categorie_actioncomm ADD PRIMARY KEY pk_categorie_actioncomm (fk_categorie, fk_actioncomm);
ALTER TABLE llx_categorie_actioncomm ADD INDEX idx_categorie_actioncomm_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_actioncomm ADD INDEX idx_categorie_actioncomm_fk_actioncomm (fk_actioncomm);

ALTER TABLE llx_categorie_actioncomm ADD CONSTRAINT fk_categorie_actioncomm_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_actioncomm ADD CONSTRAINT fk_categorie_actioncomm_fk_actioncomm FOREIGN KEY (fk_actioncomm) REFERENCES llx_actioncomm (id);


ALTER TABLE llx_accounting_account ADD COLUMN labelshort varchar(255) DEFAULT NULL after label;

ALTER TABLE llx_subscription ADD COLUMN fk_user_creat   integer DEFAULT NULL;
ALTER TABLE llx_subscription ADD COLUMN fk_user_valid   integer DEFAULT NULL;

UPDATE llx_c_forme_juridique set libelle = 'SRL - Société à responsabilité limitée' WHERE code = '201';

ALTER TABLE llx_c_country ADD COLUMN eec integer;
UPDATE llx_c_country SET eec = 1 WHERE code IN ('AT','BE','BG','CY','CZ','DE','DK','EE','ES','FI','FR','GB','GR','HR','NL','HU','IE','IM','IT','LT','LU','LV','MC','MT','PL','PT','RO','SE','SK','SI','UK');

INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  1, 'PCG18-ASSOC', 'French foundation chart of accounts 2018', 1);

INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  1, 'PCGAFR14-DEV', 'The developed farm accountancy french plan 2014', 1);

INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 41, 'AT-BASE', 'Plan Austria', 1);



create table llx_c_ticket_resolution
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  entity		integer DEFAULT 1,
  code			varchar(32)				NOT NULL,
  pos			varchar(32)				NOT NULL,
  label			varchar(128)			NOT NULL,
  active		integer DEFAULT 1,
  use_default	integer DEFAULT 1,
  description	varchar(255)
)ENGINE=innodb;

ALTER TABLE llx_c_ticket_resolution ADD UNIQUE INDEX uk_code (code, entity);

INSERT INTO llx_c_ticket_resolution (code, pos, label, active, use_default, description) VALUES('SOLVED',   '10', 'Solved',    1, 0, NULL);
INSERT INTO llx_c_ticket_resolution (code, pos, label, active, use_default, description) VALUES('CANCELED', '50', 'Canceled',  1, 0, NULL);
INSERT INTO llx_c_ticket_resolution (code, pos, label, active, use_default, description) VALUES('OTHER',    '90', 'Other',     1, 0, NULL);

DELETE FROM llx_const WHERE name = __ENCRYPT('DONATION_ART885')__;

ALTER TABLE llx_extrafields MODIFY COLUMN printable integer DEFAULT 0;
ALTER TABLE llx_extrafields ADD COLUMN printable integer DEFAULT 0;

ALTER TABLE llx_accounting_account DROP COLUMN pcg_subtype;

ALTER TABLE llx_product ADD COLUMN accountancy_code_buy_intra varchar(32) AFTER accountancy_code_buy;
ALTER TABLE llx_product ADD COLUMN accountancy_code_buy_export varchar(32) AFTER accountancy_code_buy_intra;

ALTER TABLE llx_accounting_account ADD COLUMN reconciliable tinyint DEFAULT 0 NOT NULL after active;

ALTER TABLE llx_entrepot ADD COLUMN fax varchar(20) DEFAULT NULL AFTER fk_pays;
ALTER TABLE llx_entrepot ADD COLUMN phone varchar(20) DEFAULT NULL AFTER fk_pays;
