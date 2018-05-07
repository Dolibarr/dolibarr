--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 5.0.0 or higher.
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- 							-- VPGSQL8.2 ALTER SEQUENCE IF EXISTS llx_table_rowid_seq RENAME TO llx_table_new_rowid_seq;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To set a DEFAULT value:  ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT (0|NULL|...);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To drop an index:        -- VMYSQL4.0 DROP INDEX nomindex on llx_table
-- To drop an index:        -- VPGSQL8.0 DROP INDEX nomindex
-- To restrict request to Mysql version x.y minimum use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y minimum use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):    -- VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres): -- VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE
-- To set a field as NULL:                     -- VMYSQL4.3 ALTER TABLE llx_table MODIFY COLUMN name varchar(60) NULL;
-- To set a field as NULL:                     -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as NOT NULL:                 -- VMYSQL4.3 ALTER TABLE llx_table MODIFY COLUMN name varchar(60) NOT NULL;
-- To set a field as NOT NULL:                 -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET NOT NULL;
-- Note: fields with type BLOB/TEXT can't have default value.
-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


-- after changing const name, please insure that old constant was rename
UPDATE llx_const SET name = __ENCRYPT('THIRDPARTY_DEFAULT_CREATE_CONTACT')__ WHERE name = __ENCRYPT('MAIN_THIRPARTY_CREATION_INDIVIDUAL')__;  -- under 3.9.0
UPDATE llx_const SET name = __ENCRYPT('THIRDPARTY_DEFAULT_CREATE_CONTACT')__ WHERE name = __ENCRYPT('MAIN_THIRDPARTY_CREATION_INDIVIDUAL')__; -- under 4.0.1

-- VPGSQL8.2 ALTER TABLE llx_product_lot ALTER COLUMN entity SET DEFAULT 1;
ALTER TABLE llx_product_lot MODIFY COLUMN entity integer DEFAULT 1;
UPDATE llx_product_lot SET entity = 1 WHERE entity IS NULL;

ALTER TABLE llx_bank_account ADD COLUMN extraparams		varchar(255);	

ALTER TABLE llx_societe ALTER COLUMN fk_stcomm SET DEFAULT 0;

ALTER TABLE llx_c_actioncomm ADD COLUMN picto varchar(48);

ALTER TABLE llx_facturedet ADD INDEX idx_facturedet_fk_code_ventilation (fk_code_ventilation);
ALTER TABLE llx_facture_fourn_det ADD INDEX idx_facture_fourn_det_fk_code_ventilation (fk_code_ventilation);

ALTER TABLE llx_facture_fourn_det ADD INDEX idx_facture_fourn_det_fk_product (fk_product);

ALTER TABLE llx_facture_rec ADD COLUMN fk_user_modif integer;
ALTER TABLE llx_expedition ADD COLUMN fk_user_modif integer;
ALTER TABLE llx_projet ADD COLUMN fk_user_modif integer;

ALTER TABLE llx_adherent ADD COLUMN model_pdf varchar(255);

ALTER TABLE llx_don ADD COLUMN date_valid datetime;

DELETE FROM llx_menu where module='expensereport';

ALTER TABLE llx_facturedet ADD COLUMN fk_user_author integer after fk_unit;
ALTER TABLE llx_facturedet ADD COLUMN fk_user_modif integer after fk_unit;

ALTER TABLE llx_user DROP COLUMN phenix_login;
ALTER TABLE llx_user DROP COLUMN phenix_pass;
ALTER TABLE llx_user ADD COLUMN dateemployment datetime;

ALTER TABLE llx_user MODIFY login varchar(50) NOT NULL;

ALTER TABLE llx_societe ADD COLUMN fk_account integer;

ALTER TABLE llx_commandedet ADD COLUMN fk_commandefourndet integer DEFAULT NULL after import_key;   -- link to detail line of commande fourn (resplenish)
ALTER TABLE llx_commandedet MODIFY COLUMN fk_commandefourndet integer DEFAULT NULL;

ALTER TABLE llx_website ADD COLUMN virtualhost varchar(255) after fk_default_home;

ALTER TABLE llx_chargesociales ADD COLUMN fk_account integer after fk_type;
ALTER TABLE llx_chargesociales ADD COLUMN fk_mode_reglement integer after fk_account;
ALTER TABLE llx_chargesociales ADD COLUMN fk_user_author		integer;
ALTER TABLE llx_chargesociales ADD COLUMN fk_user_modif         integer;
ALTER TABLE llx_chargesociales ADD COLUMN fk_user_valid			integer;


ALTER TABLE llx_ecm_files ADD COLUMN gen_or_uploaded varchar(12) after cover; 

DROP TABLE llx_document_generator;
DROP TABLE llx_ecm_documents;
DROP TABLE llx_holiday_events;
DROP TABLE llx_holiday_types;

ALTER TABLE llx_notify ADD COLUMN type_target varchar(16) NULL;

ALTER TABLE llx_entrepot DROP COLUMN valo_pmp;

ALTER TABLE llx_notify_def MODIFY COLUMN fk_soc integer NULL;
-- VPGSQL8.2 ALTER TABLE llx_notify_def ALTER COLUMN fk_soc SET DEFAULT NULL;


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
ALTER TABLE llx_categorie_project ADD CONSTRAINT fk_categorie_project_fk_project_rowid FOREIGN KEY (fk_project) REFERENCES llx_projet (rowid);

ALTER TABLE llx_societe_remise_except ADD COLUMN entity	integer DEFAULT 1 NOT NULL after rowid;
ALTER TABLE llx_societe_remise ADD COLUMN entity	integer DEFAULT 1 NOT NULL after rowid;


create table llx_expensereport_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_expensereport_extrafields ADD INDEX idx_expensereport_extrafields (fk_object);

ALTER TABLE llx_cotisation RENAME TO llx_subscription;
-- VPGSQL8.2 ALTER SEQUENCE IF EXISTS llx_cotisation_rowid_seq RENAME TO llx_subscription_rowid_seq;

ALTER TABLE llx_subscription ADD UNIQUE INDEX uk_subscription (fk_adherent,dateadh);
ALTER TABLE llx_subscription CHANGE COLUMN cotisation subscription real;
ALTER TABLE llx_adherent_type CHANGE COLUMN cotisation subscription varchar(3) NOT NULL DEFAULT '1';

UPDATE llx_adherent_type SET subscription = '1' WHERE subscription = 'yes';

CREATE TABLE llx_product_lot_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_product_lot_extrafields ADD INDEX idx_product_lot_extrafields (fk_object);

ALTER TABLE llx_website_page MODIFY COLUMN content MEDIUMTEXT;

CREATE TABLE llx_product_warehouse_properties
(
  rowid           		integer AUTO_INCREMENT PRIMARY KEY,
  tms             		timestamp,
  fk_product      		integer NOT NULL,
  fk_entrepot     		integer NOT NULL,
  seuil_stock_alerte    integer DEFAULT 0,
  desiredstock    		integer DEFAULT 0,
  import_key      		varchar(14)               -- Import key
)ENGINE=innodb;

ALTER TABLE llx_accounting_bookkeeping ADD COLUMN entity integer DEFAULT 1 NOT NULL;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN fk_user_modif     integer;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN date_creation		datetime;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN tms               timestamp;
-- VMYSQL4.3 ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN numero_compte varchar(32) NOT NULL;
-- VMYSQL4.3 ALTER TABLE llx_accounting_bookkeeping MODIFY COLUMN code_journal varchar(32) NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_accounting_bookkeeping ALTER COLUMN numero_compte SET NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_accounting_bookkeeping ALTER COLUMN code_journal SET NOT NULL;

ALTER TABLE llx_accounting_account ADD UNIQUE INDEX uk_accounting_account (account_number, entity, fk_pcg_version);

ALTER TABLE llx_expensereport_det ADD COLUMN fk_code_ventilation integer DEFAULT 0;

ALTER TABLE llx_c_payment_term CHANGE COLUMN fdm type_cdr tinyint;


ALTER TABLE llx_facturedet ADD COLUMN vat_src_code varchar(10) DEFAULT '' AFTER tva_tx;
ALTER TABLE llx_facturedet_rec ADD COLUMN vat_src_code varchar(10) DEFAULT '' AFTER tva_tx;
ALTER TABLE llx_facture_fourn_det ADD COLUMN vat_src_code varchar(10) DEFAULT '' AFTER tva_tx;
ALTER TABLE llx_commandedet ADD COLUMN vat_src_code varchar(10) DEFAULT '' AFTER tva_tx;
ALTER TABLE llx_commande_fournisseurdet ADD COLUMN vat_src_code varchar(10) DEFAULT '' AFTER tva_tx;
ALTER TABLE llx_propaldet ADD COLUMN vat_src_code varchar(10) DEFAULT '' AFTER tva_tx;
ALTER TABLE llx_supplier_proposaldet ADD COLUMN vat_src_code varchar(10) DEFAULT '' AFTER tva_tx;
ALTER TABLE llx_supplier_proposaldet ADD COLUMN fk_unit integer DEFAULT NULL;
ALTER TABLE llx_contratdet ADD COLUMN vat_src_code varchar(10) DEFAULT '' AFTER tva_tx;

ALTER TABLE llx_c_payment_term CHANGE COLUMN fdm type_cdr TINYINT;

ALTER TABLE llx_entrepot ADD COLUMN fk_parent integer DEFAULT 0;

create table llx_resource_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_resource_extrafields ADD INDEX idx_resource_extrafields (fk_object);

INSERT INTO llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_SIZE_SHORTLIST_LIMIT')__, __ENCRYPT('3')__, 'chaine', 'Max length for small lists (tabs)', 0, 0);

INSERT INTO llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('EXPEDITION_ADDON_NUMBER')__, __ENCRYPT('mod_expedition_safor')__, 'chaine','Name for numbering manager for shipments',0,1);

ALTER TABLE llx_bank_account ADD COLUMN note_public     		text;
ALTER TABLE llx_bank_account ADD COLUMN model_pdf       		varchar(255);
ALTER TABLE llx_bank_account ADD COLUMN import_key      		varchar(14);

ALTER TABLE llx_projet ADD COLUMN import_key      	        	varchar(14);
ALTER TABLE llx_projet_task ADD COLUMN import_key      		    varchar(14);
ALTER TABLE llx_projet_task_time ADD COLUMN import_key      	varchar(14);


ALTER TABLE llx_overwrite_trans ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;

ALTER TABLE llx_mailing_cibles ADD COLUMN error_text varchar(255);

ALTER TABLE llx_c_actioncomm MODIFY COLUMN type varchar(50) DEFAULT 'system' NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_c_actioncomm ALTER COLUMN type SET DEFAULT 'system';
-- VPGSQL8.2 ALTER TABLE llx_c_actioncomm ALTER COLUMN type SET NOT NULL;

create table llx_user_employment
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  entity            integer DEFAULT 1 NOT NULL, -- multi company id
  ref				varchar(50),				-- reference
  ref_ext			varchar(50),				-- reference into an external system (not used by dolibarr)
  fk_user			integer,
  datec             datetime,
  tms               timestamp,
  fk_user_creat     integer,
  fk_user_modif     integer,
  job				varchar(128),				-- job position. may be a dictionary
  status            integer NOT NULL,			-- draft, active, closed
  salary			double(24,8),				-- last and current value stored into llx_user
  salaryextra		double(24,8),				-- last and current value stored into llx_user
  weeklyhours		double(16,8),				-- last and current value stored into llx_user
  dateemployment    date,						-- last and current value stored into llx_user
  dateemploymentend date						-- last and current value stored into llx_user
)ENGINE=innodb;


ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_date_debut (date_debut);
ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_date_fin (date_fin);
ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_fk_statut (fk_statut);

ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_fk_user_author (fk_user_author);
ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_fk_user_valid (fk_user_valid);
ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_fk_user_approve (fk_user_approve);
ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_fk_refuse (fk_user_approve);

DELETE FROM llx_actioncomm_resources WHERE fk_actioncomm not in (select id from llx_actioncomm);

-- Sequence to removed duplicated values of llx_links. Use serveral times if you still have duplicate.
DROP TABLE tmp_links_double;
--select objectid, label, max(rowid) as max_rowid, count(rowid) as count_rowid from llx_links where label is not null group by objectid, label having count(rowid) >= 2;
CREATE TABLE tmp_links_double AS (SELECT objectid, label, MAX(rowid) AS max_rowid, COUNT(rowid) AS count_rowid FROM llx_links WHERE label IS NOT NULL GROUP BY objectid, label HAVING COUNT(rowid) >= 2);
--select * from tmp_links_double;
DELETE FROM llx_links WHERE (rowid, label) IN (SELECT max_rowid, label FROM tmp_links_double);	--update to avoid duplicate, delete to delete
DROP TABLE tmp_links_double;

ALTER TABLE llx_links ADD UNIQUE INDEX uk_links (objectid,label);

ALTER TABLE llx_expensereport ADD UNIQUE INDEX idx_expensereport_uk_ref (ref, entity);

UPDATE llx_projet_task SET ref = NULL WHERE ref = '';
ALTER TABLE llx_projet_task ADD UNIQUE INDEX uk_projet_task_ref (ref, entity);

ALTER TABLE llx_contrat ADD COLUMN fk_user_modif integer;

UPDATE llx_accounting_account SET account_parent = 0 WHERE account_parent = '';

-- VMYSQL4.3 ALTER TABLE llx_product_price MODIFY COLUMN date_price DATETIME NULL;
-- VPGSQL8.2 ALTER TABLE llx_product_price ALTER COLUMN date_price DROP NOT NULL;
ALTER TABLE llx_product_price ALTER COLUMN date_price SET DEFAULT NULL;
 
ALTER TABLE llx_product_price ADD COLUMN default_vat_code	varchar(10) after tva_tx;
ALTER TABLE llx_product_customer_price ADD COLUMN default_vat_code	varchar(10) after tva_tx;
ALTER TABLE llx_product_customer_price_log ADD COLUMN default_vat_code	varchar(10) after tva_tx;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN default_vat_code	varchar(10) after tva_tx;

ALTER TABLE llx_events MODIFY COLUMN ip varchar(250);

UPDATE llx_bank SET label= '(SupplierInvoicePayment)' WHERE label= 'Règlement fournisseur';
UPDATE llx_bank SET label= '(CustomerInvoicePayment)' WHERE label= 'Règlement client';

