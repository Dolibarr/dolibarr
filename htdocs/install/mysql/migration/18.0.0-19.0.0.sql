--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 19.0.0 or higher.
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
-- To make pk to be auto increment (mysql):
-- -- VMYSQL4.3 ALTER TABLE llx_table ADD PRIMARY KEY(rowid);
-- -- VMYSQL4.3 ALTER TABLE llx_table CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
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

-- v18


-- v19

CREATE TABLE llx_c_category
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
  id          integer NOT NULL,
  classname   varchar(32) NOT NULL,
  module      varchar(32) NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_c_category ADD UNIQUE INDEX uk_c_module(module);
ALTER TABLE llx_c_category ADD UNIQUE INDEX idx_id_idx (id);

INSERT INTO llx_c_category (id,module,classname) VALUES (0, 'product', 'Product');
INSERT INTO llx_c_category (id,module,classname) VALUES (1, 'supplier', 'Fournisseur');
INSERT INTO llx_c_category (id,module,classname) VALUES (2, 'customer', 'Societe');
INSERT INTO llx_c_category (id,module,classname) VALUES (3, 'member', 'Adherent');
INSERT INTO llx_c_category (id,module,classname) VALUES (4, 'contact', 'Contact');
INSERT INTO llx_c_category (id,module,classname) VALUES (5, 'bank_account', 'Account');
INSERT INTO llx_c_category (id,module,classname) VALUES (6, 'project', 'Project');
INSERT INTO llx_c_category (id,module,classname) VALUES (7, 'user', 'User');
INSERT INTO llx_c_category (id,module,classname) VALUES (8, 'bank_line', 'AccountLine');
INSERT INTO llx_c_category (id,module,classname) VALUES (9, 'warehouse', 'Entrepot');
INSERT INTO llx_c_category (id,module,classname) VALUES (10, 'actioncomm', 'Actioncomm');
INSERT INTO llx_c_category (id,module,classname) VALUES (11, 'website_page', 'WebsitePage');
INSERT INTO llx_c_category (id,module,classname) VALUES (12, 'ticket', 'Ticket');
INSERT INTO llx_c_category (id,module,classname) VALUES (13, 'knowledgemanagement', 'KnowledgeRecord');
INSERT INTO llx_c_category (id,module,classname) VALUES (14, 'invoice', 'Facture');
INSERT INTO llx_c_category (id,module,classname) VALUES (15, 'supplier_invoice', 'FactureFournisseur');
INSERT INTO llx_c_category (id,module,classname) VALUES (16, 'order', 'Commande');
INSERT INTO llx_c_category (id,module,classname) VALUES (17, 'supplier_order', 'CommandeFournisseur');

-- VMYSQL4.1 DROP INDEX idx_element_categorie_idx  on llx_element_categorie;
-- VPGSQL8.2 DROP INDEX idx_element_categorie_idx;
ALTER TABLE llx_element_categorie DROP FOREIGN KEY fk_element_categorie_fk_categorie;
ALTER TABLE llx_element_categorie CHANGE COLUMN fk_categorie fk_category integer NOT NULL;
ALTER TABLE llx_element_categorie RENAME TO llx_element_category;

ALTER TABLE llx_element_category ADD UNIQUE INDEX idx_element_category_idx (fk_element, fk_category);
ALTER TABLE llx_element_category ADD CONSTRAINT fk_element_category_fk_category FOREIGN KEY (fk_category) REFERENCES llx_categorie(rowid);



-- VAT multientity
-- VMYSQL4.1 DROP INDEX uk_c_tva_id on llx_c_tva;
-- VPGSQL8.2 DROP INDEX uk_c_tva_id;
ALTER TABLE llx_c_tva ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_c_tva ADD UNIQUE INDEX uk_c_tva_id (entity, fk_pays, code, taux, recuperableonly);

ALTER TABLE llx_ticket ADD COLUMN fk_contract integer DEFAULT 0 after fk_project;

UPDATE llx_product_lot SET manufacturing_date = datec WHERE manufacturing_date IS NULL;

UPDATE llx_societe_rib SET frstrecur = 'RCUR' WHERE frstrecur = 'RECUR';

-- Tip to copy vat rate into entity 2.
-- INSERT INTO llx_c_tva (entity, fk_pays, code, taux, localtax1, localtax1_type, localtax2, localtax2_type, use_default, recuperableonly, note, active, accountancy_code_sell, accountancy_code_buy) SELECT 2, fk_pays, code, taux, localtax1, localtax1_type, localtax2, localtax2_type, use_default, recuperableonly, note, active, accountancy_code_sell, accountancy_code_buy FROM llx_c_tva WHERE entity = 1;
