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



-- For v12

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


ALTER TABLE llx_societe_rib ADD COLUMN stripe_account varchar(128);

create table llx_object_lang
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_object      integer      DEFAULT 0 NOT NULL,
  type_object    varchar(32)  NOT NULL,					-- 'thirdparty', 'contact', '...'
  property       varchar(32)  NOT NULL,
  lang           varchar(5)   DEFAULT 0 NOT NULL,
  value          text,
  import_key varchar(14) DEFAULT NULL
)ENGINE=innodb;



ALTER TABLE llx_object_lang ADD UNIQUE INDEX uk_object_lang (fk_object, type_object, property, lang);



