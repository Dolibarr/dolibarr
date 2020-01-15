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

-- Category type
CREATE TABLE llx_c_type_category
(
  rowid         integer PRIMARY KEY,
  code          varchar(32) NOT NULL,
  element_key   varchar(255) NOT NULL,
  element_table varchar(255) NOT NULL,
  object_class  varchar(255) NOT NULL,
  object_table  varchar(255) NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_c_type_category ADD UNIQUE INDEX uk_c_type_category(code);

INSERT INTO llx_c_type_category (rowid, code, element_key, element_table, object_class, object_table) values (0, 'product',      'product',   'product',     'Product',     'product');
INSERT INTO llx_c_type_category (rowid, code, element_key, element_table, object_class, object_table) values (1, 'supplier',     'soc',       'fournisseur', 'Fournisseur', 'societe');
INSERT INTO llx_c_type_category (rowid, code, element_key, element_table, object_class, object_table) values (2, 'customer',     'soc',       'societe',     'Societe',     'societe');
INSERT INTO llx_c_type_category (rowid, code, element_key, element_table, object_class, object_table) values (3, 'member',       'member',    'member',      'Adherent',    'adherent');
INSERT INTO llx_c_type_category (rowid, code, element_key, element_table, object_class, object_table) values (4, 'contact',      'socpeople', 'contact',     'Contact',     'socpeople');
INSERT INTO llx_c_type_category (rowid, code, element_key, element_table, object_class, object_table) values (5, 'bank_account', 'account',   'account',     'Account',     'bank_account');
INSERT INTO llx_c_type_category (rowid, code, element_key, element_table, object_class, object_table) values (6, 'project',      'project',   'project',     'Project',     'projet');
INSERT INTO llx_c_type_category (rowid, code, element_key, element_table, object_class, object_table) values (7, 'user',         'user',      'user',        'User',        'user');
INSERT INTO llx_c_type_category (rowid, code, element_key, element_table, object_class, object_table) values (8, 'bank_line',    'account',   'account',     'Account',     'bank_account');
INSERT INTO llx_c_type_category (rowid, code, element_key, element_table, object_class, object_table) values (9, 'warehouse',    'warehouse', 'warehouse',   'Entrepot',    'entrepot');

ALTER TABLE llx_categorie CHANGE type type integer NOT NULL DEFAULT '1';
