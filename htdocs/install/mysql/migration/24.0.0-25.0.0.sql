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

-- Fix #37658 - subprice_ttc (pu_ttc for supplier invoices) now flags a line entered including tax (0 when
-- entered excluding tax). Supplier lines used to store it unconditionally (even for lines entered excluding
-- tax), so reset it on existing supplier lines to avoid them being wrongly treated as entered including tax
-- on clone/edit/bulk actions. A line can be re-entered including tax to set the value again.
-- Guarded on the upgrade source version (MAIN_VERSION_LAST_UPGRADE is still the source version at this point,
-- updated only at the end of step5) so re-running the migration on a 25.x base does NOT wipe values set since.
UPDATE llx_commande_fournisseurdet SET subprice_ttc = 0 WHERE subprice_ttc <> 0 AND EXISTS (SELECT c.rowid FROM llx_const as c WHERE c.name = 'MAIN_VERSION_LAST_UPGRADE' AND c.value < '25.0.0');
UPDATE llx_facture_fourn_det SET pu_ttc = 0 WHERE pu_ttc <> 0 AND EXISTS (SELECT c.rowid FROM llx_const as c WHERE c.name = 'MAIN_VERSION_LAST_UPGRADE' AND c.value < '25.0.0');
UPDATE llx_supplier_proposaldet SET subprice_ttc = 0 WHERE subprice_ttc <> 0 AND EXISTS (SELECT c.rowid FROM llx_const as c WHERE c.name = 'MAIN_VERSION_LAST_UPGRADE' AND c.value < '25.0.0');

-- Fixed sell prices per currency for a product, independent from the exchange rate (issue #32379).
-- The table must be created here too: the upgrade path runs migrations only, it does not replay the tables/ files.
create table llx_product_price_currency
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  entity				integer   DEFAULT 1 NOT NULL,
  tms					timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_product			integer NOT NULL,
  fk_soc				integer   DEFAULT 0 NOT NULL,
  price_level			smallint  DEFAULT 1 NOT NULL,
  fk_multicurrency		integer,
  multicurrency_code	varchar(3) NOT NULL,
  multicurrency_tx		double(24,8) DEFAULT 1,
  price					double(24,8) DEFAULT NULL,
  price_ttc				double(24,8) DEFAULT NULL,
  price_base_type		varchar(3) DEFAULT 'HT',
  date_price			datetime NOT NULL,
  fk_user_author		integer,
  import_key			varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_product_price_currency ADD UNIQUE INDEX uk_product_price_currency (fk_product, fk_soc, price_level, multicurrency_code, entity);
ALTER TABLE llx_product_price_currency ADD INDEX idx_product_price_currency_fk_product (fk_product);
ALTER TABLE llx_product_price_currency ADD INDEX idx_product_price_currency_fk_user_author (fk_user_author);
ALTER TABLE llx_product_price_currency ADD CONSTRAINT fk_product_price_currency_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);

-- Flag a document line whose currency unit price comes from a fixed per-currency product price (issue #32379)
ALTER TABLE llx_propaldet ADD COLUMN multicurrency_subprice_source tinyint NOT NULL DEFAULT 0;
ALTER TABLE llx_commandedet ADD COLUMN multicurrency_subprice_source tinyint NOT NULL DEFAULT 0;
ALTER TABLE llx_facturedet ADD COLUMN multicurrency_subprice_source tinyint NOT NULL DEFAULT 0;
ALTER TABLE llx_facturedet_rec ADD COLUMN multicurrency_subprice_source tinyint NOT NULL DEFAULT 0;

-- end of migration
