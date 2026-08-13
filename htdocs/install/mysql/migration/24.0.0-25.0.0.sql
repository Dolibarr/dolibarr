--
-- This file is executed by calling /install/index.php page
-- when current version is higher than the name of this file.
-- Be carefull in the position of each SQL request.
--
-- To restrict request to Mysql version x.y minimum use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y minimum use -- VPGSQLx.y
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
--                          Note that "RENAME TO" is both compatible with mysql/postgesql, not the "RENAME" alone.
--                          Also you must complete with renaming the sequence for PGSQL with -- VPGSQL8.2 ALTER SEQUENCE llx_table_rowid_seq RENAME TO llx_table_new_rowid_seq;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key or constraint:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To create a unique index:              ALTER TABLE llx_table ADD UNIQUE INDEX uk_table_field (field);
-- To drop an index:        -- VMYSQL4.1 DROP INDEX nomindex ON llx_table;
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
-- To rebuild sequence for postgresql after insert, by forcing id autoincrement fields:
-- -- VPGSQL8.2 SELECT dol_util_rebuild_sequences();


--noqa:disable=LT09
--noqa:disable=RF03


-- V24 forgotten


-- v25 migration

-- Add per entity payment terms/modes and bank account (issue #39146)
ALTER TABLE llx_societe_perentity ADD COLUMN fk_account integer DEFAULT NULL;
ALTER TABLE llx_societe_perentity ADD COLUMN mode_reglement integer DEFAULT NULL;
ALTER TABLE llx_societe_perentity ADD COLUMN cond_reglement tinyint DEFAULT NULL;
ALTER TABLE llx_societe_perentity ADD COLUMN mode_reglement_supplier tinyint DEFAULT NULL;
ALTER TABLE llx_societe_perentity ADD COLUMN cond_reglement_supplier tinyint DEFAULT NULL;

-- extrafields for links
CREATE TABLE llx_links_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                             -- import key
) ENGINE=innodb;
ALTER TABLE llx_links_extrafields ADD UNIQUE INDEX uk_links_extrafields (fk_object);

-- Add user/tms information to element_element
ALTER TABLE llx_element_element ADD COLUMN fk_user_creat integer;
ALTER TABLE llx_element_element ADD COLUMN date_creation datetime;
ALTER TABLE llx_element_element ADD COLUMN fk_user_modif integer;
ALTER TABLE llx_element_element ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_c_action_trigger ADD COLUMN enabled varchar(255);

-- Fix #37658 - pu_ttc for supplier invoices now flags a line entered including tax (0 when entered excluding
-- tax). Before this change, SupplierInvoiceLine::insert() stored pu_ttc = total_ttc / qty unconditionally, so
-- every existing supplier invoice line has a non-zero pu_ttc that would now be wrongly treated as entered
-- including tax on clone/edit/bulk actions. Reset it once on existing lines so they keep behaving as excluding
-- tax; the value is set again when a line is re-entered including tax.
-- (Customer objects, supplier orders and supplier proposals never persisted the marker before -> nothing to
-- reset for them.) Guarded by a one-shot constant so the reset runs exactly once per database, whatever the
-- upgrade path.
UPDATE llx_facture_fourn_det SET pu_ttc = 0 WHERE pu_ttc <> 0 AND NOT EXISTS (SELECT rowid FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_SUPPLIER_TTC_ENTRY_MODE_RESET_DONE');
INSERT INTO llx_const (name, entity, value, type, visible, note) SELECT DISTINCT __ENCRYPT('MAIN_SUPPLIER_TTC_ENTRY_MODE_RESET_DONE')__, 0, __ENCRYPT('1')__, 'chaine', 0, 'Supplier invoice pu_ttc reset done once (fix #37658)' FROM llx_const WHERE NOT EXISTS (SELECT rowid FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_SUPPLIER_TTC_ENTRY_MODE_RESET_DONE');

-- end of migration
