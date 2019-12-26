--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.7.0 or higher.
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

INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (1, '60', 'Entreprise Individuelle à Responsabilité Limitée (EIRL)', 1);

--insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_MODIFY','Intervention modified','Executed when a intervention is modified','ficheinter',19);
--insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_DELETE','Intervention delete','Executed when a intervention is delete','ficheinter',19);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_CLASSIFY_BILLED','Intervention set billed','Executed when a intervention is set to billed (when option FICHINTER_CLASSIFY_BILLED is set)','ficheinter',19);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_CLASSIFY_UNBILLED','Intervention set unbilled','Executed when a intervention is set to unbilled (when option FICHINTER_CLASSIFY_BILLED is set)','ficheinter',19);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_SENTBYMAIL','Intervention sent by mail','Executed when a intervention is sent by mail','ficheinter',19);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_REOPEN','Intervention opened','Executed when a intervention is re-opened','ficheinter',19);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CLASSIFY_BILLED','Customer proposal set billed','Executed when a customer proposal is set to billed','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_CLOSE','Customer order classify delivered','Executed when a customer order is set delivered','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_CLASSIFY_BILLED','Customer order classify billed','Executed when a customer order is set to billed','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_CANCEL','Customer order canceled','Executed when a customer order is canceled','commande',5);

-- VPGSQL8.2 ALTER TABLE llx_contrat ALTER COLUMN fk_commercial_signature DROP NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_contrat ALTER COLUMN fk_commercial_suivi DROP NOT NULL;
ALTER TABLE llx_contrat MODIFY fk_commercial_signature integer NULL;
ALTER TABLE llx_contrat MODIFY fk_commercial_suivi integer NULL;

ALTER TABLE llx_notify ADD COLUMN fk_soc integer NULL after fk_action;
ALTER TABLE llx_notify ADD COLUMN type varchar(16) DEFAULT 'email' after fk_soc;

ALTER TABLE llx_bank_account ADD COLUMN fk_user_author integer;

ALTER TABLE llx_c_actioncomm ADD COLUMN color varchar(9);

ALTER TABLE llx_propal ADD COLUMN fk_user_modif integer after fk_user_author;
ALTER TABLE llx_commande ADD COLUMN fk_user_modif integer after fk_user_author;
ALTER TABLE llx_facture ADD COLUMN fk_user_modif integer after fk_user_author;
ALTER TABLE llx_product ADD COLUMN fk_user_modif integer after fk_user_author;
ALTER TABLE llx_fichinter ADD COLUMN fk_user_modif integer after fk_user_author;
ALTER TABLE llx_commande_fournisseur ADD COLUMN fk_user_modif integer after fk_user_author;
ALTER TABLE llx_facture_fourn ADD COLUMN fk_user_modif integer after fk_user_author;
ALTER TABLE llx_bank_account ADD COLUMN fk_user_modif integer after fk_user_author;


ALTER TABLE llx_fichinter ADD COLUMN ref_ext 	varchar(255);


-- Defined only to have specific list for countries that can't use generic list (like argentina that need type A or B)
ALTER TABLE llx_c_typent ADD COLUMN fk_country integer NULL AFTER libelle;

INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, position) values (11,'AC_INT','system','Intervention on site',NULL, 1, 4);

ALTER TABLE llx_user ADD COLUMN fk_user_creat integer AFTER tms;
ALTER TABLE llx_user ADD COLUMN fk_user_modif integer AFTER fk_user_creat;

-- Add module accounting Expert
--ALTER TABLE llx_bookkeeping RENAME TO llx_accounting_bookkeeping; -- To update old user of module Accounting Expert -> Line should be added into file sql/x.y.z-a.b.c.sql of module. 


CREATE TABLE llx_accounting_bookkeeping
(
  rowid				integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  doc_date			date NOT NULL,
  doc_type			varchar(30) NOT NULL,	-- facture_client/reglement_client/facture_fournisseur/reglement_fournisseur
  doc_ref			varchar(30) NOT NULL,	-- facture_client/reglement_client/... reference number
  fk_doc			integer NOT NULL,		-- facture_client/reglement_client/... rowid
  fk_docdet			integer NOT NULL,		-- facture_client/reglement_client/... line rowid
  code_tiers		varchar(24),			-- code tiers
  numero_compte		varchar(32) DEFAULT NULL,
  label_compte		varchar(128) NOT NULL,
  debit				double NOT NULL,
  credit			double NOT NULL,
  montant			double NOT NULL,
  sens				varchar(1) DEFAULT NULL,
  fk_user_author	integer NOT NULL,
  import_key		varchar(14),
  code_journal		varchar(10) DEFAULT NULL,
  piece_num		integer NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_c_paiement ADD COLUMN accountancy_code varchar(32) DEFAULT NULL AFTER active;
ALTER TABLE llx_bank_account ADD COLUMN accountancy_journal varchar(3) DEFAULT NULL AFTER account_number;

ALTER TABLE llx_accountingaccount add column entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_accountingaccount add column datec datetime AFTER entity;
ALTER TABLE llx_accountingaccount add column fk_user_author integer DEFAULT NULL AFTER label;
ALTER TABLE llx_accountingaccount add column fk_user_modif integer DEFAULT NULL AFTER fk_user_author;

-- Qual
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_MODE')__ WHERE __DECRYPT('name')__ = 'COMPTA_MODE';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_ACCOUNT_CUSTOMER')__ WHERE __DECRYPT('name')__ = 'COMPTA_ACCOUNT_CUSTOMER';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_ACCOUNT_SUPPLIER')__ WHERE __DECRYPT('name')__ = 'COMPTA_ACCOUNT_SUPPLIER';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_PRODUCT_BUY_ACCOUNT')__ WHERE __DECRYPT('name')__ = 'COMPTA_PRODUCT_BUY_ACCOUNT';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_PRODUCT_SOLD_ACCOUNT')__ WHERE __DECRYPT('name')__ = 'COMPTA_PRODUCT_SOLD_ACCOUNT';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_SERVICE_BUY_ACCOUNT')__ WHERE __DECRYPT('name')__ = 'COMPTA_SERVICE_BUY_ACCOUNT';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_SERVICE_SOLD_ACCOUNT')__ WHERE __DECRYPT('name')__ = 'COMPTA_SERVICE_SOLD_ACCOUNT';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_VAT_ACCOUNT')__ WHERE __DECRYPT('name')__ = 'COMPTA_VAT_ACCOUNT';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_VAT_BUY_ACCOUNT')__ WHERE __DECRYPT('name')__ = 'COMPTA_VAT_BUY_ACCOUNT';

-- Compatibility with module Accounting Expert
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_EXPORT_MODELCSV')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_MODELCSV';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_EXPORT_SEPARATORCSV')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_SEPARATORCSV';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_EXPORT_DATE')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_EXP_DATE';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_EXPORT_PIECE')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_EXP_PIECE';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_EXPORT_GLOBAL_ACCOUNT')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_EXP_GLOBAL_ACCOUNT';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_EXPORT_LABEL')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_EXP_LABEL';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_EXPORT_AMOUNT')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_EXP_AMOUNT';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_EXPORT_DEVISE')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_EXP_DEVISE';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_ACCOUNT_SUSPENSE')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_ACCOUNT_SUSPENSE';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_SELL_JOURNAL')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_SELL_JOURNAL';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_PURCHASE_JOURNAL')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_PURCHASE_JOURNAL';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_SOCIAL_JOURNAL')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_SOCIAL_JOURNAL';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_MISCELLANEOUS_JOURNAL')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_MISCELLANEOUS_JOURNAL';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_ACCOUNT_TRANSFER_CASH')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_ACCOUNT_TRANSFER_CASH';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_LENGTH_GACCOUNT')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_LENGTH_GACCOUNT';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_LENGTH_AACCOUNT')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_LENGTH_AACCOUNT';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_LIMIT_LIST_VENTILATION')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_LIMIT_LIST_VENTILATION';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_LIST_SORT_VENTILATION_TODO')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_LIST_SORT_VENTILATION_TODO';
UPDATE llx_const SET name = __ENCRYPT('ACCOUNTING_LIST_SORT_VENTILATION_DONE')__ WHERE __DECRYPT('name')__ = 'ACCOUNTINGEX_LIST_SORT_VENTILATION_DONE';

-- Drop old table
DROP TABLE llx_compta;
DROP TABLE llx_compta_account;
DROP TABLE llx_compta_compte_generaux;

-- Align size for accounting account
ALTER TABLE llx_accountingaccount MODIFY COLUMN account_number varchar(32);
ALTER TABLE llx_accountingaccount MODIFY COLUMN account_parent varchar(32);
ALTER TABLE llx_accountingaccount add column tms timestamp AFTER datec;
ALTER TABLE llx_accountingdebcred MODIFY COLUMN account_number varchar(32);
ALTER TABLE llx_bank_account MODIFY COLUMN account_number varchar(32);
ALTER TABLE llx_c_chargesociales MODIFY COLUMN accountancy_code varchar(32);
ALTER TABLE llx_c_revenuestamp MODIFY COLUMN accountancy_code_sell varchar(32);
ALTER TABLE llx_c_revenuestamp MODIFY COLUMN accountancy_code_buy varchar(32);
ALTER TABLE llx_c_tva MODIFY COLUMN accountancy_code_sell varchar(32);
ALTER TABLE llx_c_tva MODIFY COLUMN accountancy_code_buy varchar(32);
ALTER TABLE llx_product MODIFY COLUMN accountancy_code_sell varchar(32);
ALTER TABLE llx_product MODIFY COLUMN accountancy_code_buy varchar(32);
ALTER TABLE llx_user MODIFY COLUMN accountancy_code varchar(32);


ALTER TABLE llx_user ADD COLUMN thm double(24,8);
ALTER TABLE llx_user ADD COLUMN tjm double(24,8);
ALTER TABLE llx_user ADD COLUMN salary double(24,8);
ALTER TABLE llx_user ADD COLUMN salaryextra double(24,8);
ALTER TABLE llx_user ADD COLUMN weeklyhours double(16,8);


ALTER TABLE llx_projet_task_time ADD COLUMN task_datehour datetime after task_date;

ALTER TABLE llx_actioncomm_resources CHANGE COLUMN transparent transparency smallint default 1;

ALTER TABLE llx_actioncomm_resources DROP INDEX idx_actioncomm_resources_idx1;
ALTER TABLE llx_actioncomm_resources ADD UNIQUE INDEX uk_actioncomm_resources(fk_actioncomm, element_type, fk_element);


-- Localtaxes by thirds
ALTER TABLE llx_c_tva MODIFY COLUMN localtax1 varchar(20);
ALTER TABLE llx_c_tva MODIFY COLUMN localtax2 varchar(20);
ALTER TABLE llx_localtax ADD COLUMN localtaxtype tinyint after entity;
ALTER TABLE llx_societe ADD COLUMN localtax1_value double(6,3) after localtax1_assuj;
ALTER TABLE llx_societe ADD COLUMN localtax2_value double(6,3) after localtax2_assuj;

-- Change on table c_pays
ALTER TABLE llx_c_pays RENAME TO llx_c_country;

ALTER TABLE llx_c_country CHANGE COLUMN libelle label VARCHAR(50);

ALTER TABLE llx_c_ziptown ADD CONSTRAINT fk_c_ziptown_fk_pays FOREIGN KEY (fk_pays) REFERENCES llx_c_country (rowid);
ALTER TABLE llx_c_regions ADD CONSTRAINT fk_c_regions_fk_pays FOREIGN KEY (fk_pays) REFERENCES llx_c_country (rowid);


-- Added missing relations of llx_product
-- fk_country
ALTER TABLE llx_product MODIFY COLUMN fk_country INTEGER NULL DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product ALTER COLUMN fk_country DROP NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product ALTER COLUMN fk_country SET DEFAULT NULL;
UPDATE llx_product SET fk_country = NULL WHERE fk_country = 0;
ALTER TABLE llx_product ADD INDEX idx_product_fk_country (fk_country);
ALTER TABLE llx_product ADD CONSTRAINT fk_product_fk_country FOREIGN KEY (fk_country) REFERENCES  llx_c_country (rowid);
-- fk_user_author
ALTER TABLE llx_product MODIFY COLUMN fk_user_author INTEGER NULL DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product ALTER COLUMN fk_user_author DROP NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product ALTER COLUMN fk_user_author SET DEFAULT NULL;
ALTER TABLE llx_product ADD INDEX idx_product_fk_user_author (fk_user_author);
-- fk_barcode_type
ALTER TABLE llx_product MODIFY COLUMN fk_barcode_type INTEGER NULL DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product ALTER COLUMN fk_barcode_type DROP NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product ALTER COLUMN fk_barcode_type SET DEFAULT NULL;
UPDATE llx_product SET fk_barcode_type = NULL WHERE fk_barcode_type = 0;
ALTER TABLE llx_product ADD INDEX idx_product_fk_barcode_type (fk_barcode_type);
UPDATE llx_product SET fk_barcode_type = NULL WHERE fk_barcode_type NOT IN (SELECT rowid from llx_c_barcode_type);


-- Added missing relations of llx_product_price
-- fk_user_author
ALTER TABLE  llx_product_price ADD INDEX idx_product_price_fk_user_author (fk_user_author);
UPDATE llx_product_price set fk_user_author = null where fk_user_author = 0;
UPDATE llx_product_price set fk_user_author = null where fk_user_author not in (select rowid from llx_user);
-- drop foreign key for avoid a mysql crash
ALTER TABLE  llx_product_price DROP FOREIGN KEY fk_product_price_user_author;
ALTER TABLE  llx_product_price ADD CONSTRAINT fk_product_price_user_author FOREIGN KEY (fk_user_author) REFERENCES  llx_user (rowid);
-- fk_product
ALTER TABLE  llx_product_price ADD INDEX idx_product_price_fk_product (fk_product);
DELETE from llx_product_price where fk_product NOT IN (SELECT rowid from llx_product);
-- drop foreign key for avoid a mysql crash
ALTER TABLE  llx_product_price DROP FOREIGN KEY fk_product_price_product;
ALTER TABLE  llx_product_price ADD CONSTRAINT fk_product_price_product FOREIGN KEY (fk_product) REFERENCES  llx_product (rowid);

ALTER TABLE llx_commande_fournisseur MODIFY COLUMN date_livraison datetime; 

ALTER TABLE llx_commande_fournisseur ADD COLUMN fk_account integer AFTER date_livraison;
ALTER TABLE llx_facture_fourn ADD COLUMN fk_account integer AFTER fk_projet;

-- Fiscal years
create table llx_accounting_fiscalyear
(
	rowid			integer AUTO_INCREMENT PRIMARY KEY,
	label			varchar(128) NOT NULL,
	date_start		date,
	date_end		date,
	statut			tinyint DEFAULT 0 NOT NULL,
	entity			integer DEFAULT 1 NOT NULL,	  -- multi company id
	datec			datetime NOT NULL,
	tms				timestamp,
	fk_user_author	integer NULL,
	fk_user_modif	integer NULL
)ENGINE=innodb;

ALTER TABLE llx_contrat ADD COLUMN ref_supplier varchar(30) after ref;
ALTER TABLE llx_contrat ADD COLUMN ref_ext varchar(30) after ref_supplier;

ALTER TABLE llx_propal ADD COLUMN fk_shipping_method integer AFTER date_livraison;
ALTER TABLE llx_commande ADD COLUMN fk_shipping_method integer AFTER date_livraison;

ALTER TABLE llx_adherents MODIFY COLUMN societe VARCHAR(60);

--
-- Descriptif des plans comptables ES PCG08-PYME
--

INSERT INTO llx_accounting_system (rowid, pcg_version, fk_pays, label, active) VALUES (4, 'PCG08-PYME', '4', 'The PYME accountancy spanish plan', '1');

INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4001,'PCG08-PYME','FINANCIACION', 'XXXXXX', '1', '', 'Financiación básica', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4002,'PCG08-PYME','ACTIVO',  'XXXXXX', '2', '', 'Activo no corriente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4003,'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '3', '', 'Existencias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4004,'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4', '', 'Acreedores y deudores por operaciones comerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4005,'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5', '', 'Cuentas financieras', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4006,'PCG08-PYME','COMPRAS_Y_GASTOS','XXXXXX', '6', '', 'Compras y gastos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4007,'PCG08-PYME','VENTAS_E_INGRESOS',  'XXXXXX', '7', '', 'Ventas e ingresos', '1');

INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4008, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '10', '4001', 'CAPITAL', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4009, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '100', '4008', 'Capital social', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4010, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '101', '4008', 'Fondo social', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4011, 'PCG08-PYME','FINANCIACION', 'CAPITAL', '102', '4008', 'Capital', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4012, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '103', '4008', 'Socios por desembolsos no exigidos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4013, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1030', '4012', 'Socios por desembolsos no exigidos capital social', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4014, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1034', '4012', 'Socios por desembolsos no exigidos capital pendiente de inscripción', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4015, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '104', '4008', 'Socios por aportaciones no dineradas pendientes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4016, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1040', '4015', 'Socios por aportaciones no dineradas pendientes capital social', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4017, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1044', '4015', 'Socios por aportaciones no dineradas pendientes capital pendiente de inscripción', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4018, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '108', '4008', 'Acciones o participaciones propias en situaciones especiales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4019, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '109', '4008', 'Acciones o participaciones propias para reducción de capital', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4020, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '11', '4001', 'Reservas y otros instrumentos de patrimonio', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4021, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '110', '4020', 'Prima de emisión o asunción', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4022, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '111', '4020', 'Otros instrumentos de patrimonio neto', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4023, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1110', '4022', 'Patrimonio neto por emisión de instrumentos financieros compuestos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4024, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1111', '4022', 'Resto de instrumentos de patrimoio neto', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4025, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '112', '4020', 'Reserva legal', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4026, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '113', '4020', 'Reservas voluntarias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4027, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '114', '4020', 'Reservas especiales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4028, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1140', '4027', 'Reservas para acciones o participaciones de la sociedad dominante', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4029, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1141', '4027', 'Reservas estatutarias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4030, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1142', '4027', 'Reservas por capital amortizado', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4031, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1143', '4027', 'Reservas por fondo de comercio', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4032, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1144', '4028', 'Reservas por acciones propias aceptadas en garantía', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4033, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '115', '4020', 'Reservas por pérdidas y ganancias actuariales y otros ajustes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4034, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '118', '4020', 'Aportaciones de socios o propietarios', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4035, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '119', '4020', 'Diferencias por ajuste del capital a euros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4036, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '12', '4001', 'Resultados pendientes de aplicación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4037, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '120', '4036', 'Remanente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4038, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '121', '4036', 'Resultados negativos de ejercicios anteriores', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4039, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '129', '4036', 'Resultado del ejercicio', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4040, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '13', '4001', 'Subvenciones, donaciones y ajustes por cambio de valor', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4041, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '130', '4040', 'Subvenciones oficiales de capital', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4042, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '131', '4040', 'Donaciones y legados de capital', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4043, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '132', '4040', 'Otras subvenciones, donaciones y legados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4044, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '133', '4040', 'Ajustes por valoración en activos financieros disponibles para la venta', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4045, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '134', '4040', 'Operaciones de cobertura', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4046, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1340', '4045', 'Cobertura de flujos de efectivo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4047, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1341', '4045', 'Cobertura de una inversión neta en un negocio extranjero', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4048, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '135', '4040', 'Diferencias de conversión', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4049, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '136', '4040', 'Ajustes por valoración en activos no corrientes y grupos enajenables de elementos mantenidos para la venta', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4050, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '137', '4040', 'Ingresos fiscales a distribuir en varios ejercicios', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4051, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1370', '4050', 'Ingresos fiscales por diferencias permanentes a distribuir en varios ejercicios', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4052, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1371', '4050', 'Ingresos fiscales por deducciones y bonificaciones a distribuir en varios ejercicios', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4053, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '14', '4001', 'Provisiones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4054, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '141', '4053', 'Provisión para impuestos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4055, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '142', '4053', 'Provisión para otras responsabilidades', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4056, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '143', '4053', 'Provisión por desmantelamiento, retiro o rehabilitación del inmovilizado', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4057, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '145', '4053', 'Provisión para actuaciones medioambientales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4058, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '15', '4001', 'Deudas a largo plazo con características especiales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4059, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '150', '4058', 'Acciones o participaciones a largo plazo consideradas como pasivos financieros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4060, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '153', '4058', 'Desembolsos no exigidos por acciones o participaciones consideradas como pasivos financieros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4061, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1533', '4060', 'Desembolsos no exigidos empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4062, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1534', '4060', 'Desembolsos no exigidos empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4063, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1535', '4060', 'Desembolsos no exigidos otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4064, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1536', '4060', 'Otros desembolsos no exigidos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4065, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '154', '4058', 'Aportaciones no dinerarias pendientes por acciones o participaciones consideradas como pasivos financieros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4066, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1543', '4065', 'Aportaciones no dinerarias pendientes empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4067, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1544', '4065', 'Aportaciones no dinerarias pendientes empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4068, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1545', '4065', 'Aportaciones no dinerarias pendientes otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4069, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1546', '4065', 'Otras aportaciones no dinerarias pendientes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4070, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '16', '4001', 'Deudas a largo plazo con partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4071, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '160', '4070', 'Deudas a largo plazo con entidades de crédito vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4072, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1603', '4071', 'Deudas a largo plazo con entidades de crédito empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4073, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1604', '4071', 'Deudas a largo plazo con entidades de crédito empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4074, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1605', '4071', 'Deudas a largo plazo con otras entidades de crédito vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4075, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '161', '4070', 'Proveedores de inmovilizado a largo plazo partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4076, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1613', '4075', 'Proveedores de inmovilizado a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4077, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1614', '4075', 'Proveedores de inmovilizado a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4078, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1615', '4075', 'Proveedores de inmovilizado a largo plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4079, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '162', '4070', 'Acreedores por arrendamiento financiero a largo plazo partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4080, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1623', '4079', 'Acreedores por arrendamiento financiero a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4081, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1624', '4080', 'Acreedores por arrendamiento financiero a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4082, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1625', '4080', 'Acreedores por arrendamiento financiero a largo plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4083, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '163', '4070', 'Otras deudas a largo plazo con partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4084, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1633', '4083', 'Otras deudas a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4085, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1634', '4083', 'Otras deudas a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4086, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '1635', '4083', 'Otras deudas a largo plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4087, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '17', '4001', 'Deudas a largo plazo por préstamos recibidos empresitos y otros conceptos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4088, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '170', '4087', 'Deudas a largo plazo con entidades de crédito', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4089, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '171', '4087', 'Deudas a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4090, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '172', '4087', 'Deudas a largo plazo transformables en suvbenciones donaciones y legados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4091, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '173', '4087', 'Proveedores de inmovilizado a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4092, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '174', '4087', 'Acreedores por arrendamiento financiero a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4093, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '175', '4087', 'Efectos a pagar a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4094, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '176', '4087', 'Pasivos por derivados financieros a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4095, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '177', '4087', 'Obligaciones y bonos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4096, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '179', '4087', 'Deudas representadas en otros valores negociables', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4097, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '18', '4001', 'Pasivos por fianzas garantias y otros conceptos a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4098, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '180', '4097', 'Fianzas recibidas a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4099, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '181', '4097', 'Anticipos recibidos por ventas o prestaciones de servicios a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4100, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '185', '4097', 'Depositos recibidos a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4101, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '19', '4001', 'Situaciones transitorias de financiación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4102, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '190', '4101', 'Acciones o participaciones emitidas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4103, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '192', '4101', 'Suscriptores de acciones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4104, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '194', '4101', 'Capital emitido pendiente de inscripción', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4105, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '195', '4101', 'Acciones o participaciones emitidas consideradas como pasivos financieros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4106, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '197', '4101', 'Suscriptores de acciones consideradas como pasivos financieros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4107, 'PCG08-PYME','FINANCIACION', 'XXXXXX', '199', '4101', 'Acciones o participaciones emitidas consideradas como pasivos financieros pendientes de inscripción', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4108, 'PCG08-PYME','ACTIVO', 'XXXXXX', '20', '4002', 'Inmovilizaciones intangibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4109, 'PCG08-PYME','ACTIVO', 'XXXXXX', '200', '4108', 'Investigación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4110, 'PCG08-PYME','ACTIVO', 'XXXXXX', '201', '4108', 'Desarrollo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4111, 'PCG08-PYME','ACTIVO', 'XXXXXX', '202', '4108', 'Concesiones administrativas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4112, 'PCG08-PYME','ACTIVO', 'XXXXXX', '203', '4108', 'Propiedad industrial', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4113, 'PCG08-PYME','ACTIVO', 'XXXXXX', '205', '4108', 'Derechos de transpaso', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4114, 'PCG08-PYME','ACTIVO', 'XXXXXX', '206', '4108', 'Aplicaciones informáticas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4115, 'PCG08-PYME','ACTIVO', 'XXXXXX', '209', '4108', 'Anticipos para inmovilizaciones intangibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4116, 'PCG08-PYME','ACTIVO', 'XXXXXX', '21', '4002', 'Inmovilizaciones materiales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4117, 'PCG08-PYME','ACTIVO', 'XXXXXX', '210', '4116', 'Terrenos y bienes naturales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4118, 'PCG08-PYME','ACTIVO', 'XXXXXX', '211', '4116', 'Construcciones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4119, 'PCG08-PYME','ACTIVO', 'XXXXXX', '212', '4116', 'Instalaciones técnicas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4120, 'PCG08-PYME','ACTIVO', 'XXXXXX', '213', '4116', 'Maquinaria', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4121, 'PCG08-PYME','ACTIVO', 'XXXXXX', '214', '4116', 'Utillaje', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4122, 'PCG08-PYME','ACTIVO', 'XXXXXX', '215', '4116', 'Otras instalaciones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4123, 'PCG08-PYME','ACTIVO', 'XXXXXX', '216', '4116', 'Mobiliario', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4124, 'PCG08-PYME','ACTIVO', 'XXXXXX', '217', '4116', 'Equipos para procesos de información', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4125, 'PCG08-PYME','ACTIVO', 'XXXXXX', '218', '4116', 'Elementos de transporte', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4126, 'PCG08-PYME','ACTIVO', 'XXXXXX', '219', '4116', 'Otro inmovilizado material', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4127, 'PCG08-PYME','ACTIVO', 'XXXXXX', '22', '4002', 'Inversiones inmobiliarias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4128, 'PCG08-PYME','ACTIVO', 'XXXXXX', '220', '4127', 'Inversiones en terreons y bienes naturales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4129, 'PCG08-PYME','ACTIVO', 'XXXXXX', '221', '4127', 'Inversiones en construcciones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4130, 'PCG08-PYME','ACTIVO', 'XXXXXX', '23', '4002', 'Inmovilizaciones materiales en curso', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4131, 'PCG08-PYME','ACTIVO', 'XXXXXX', '230', '4130', 'Adaptación de terrenos y bienes naturales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4132, 'PCG08-PYME','ACTIVO', 'XXXXXX', '231', '4130', 'Construcciones en curso', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4133, 'PCG08-PYME','ACTIVO', 'XXXXXX', '232', '4130', 'Instalaciones técnicas en montaje', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4134, 'PCG08-PYME','ACTIVO', 'XXXXXX', '233', '4130', 'Maquinaria en montaje', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4135, 'PCG08-PYME','ACTIVO', 'XXXXXX', '237', '4130', 'Equipos para procesos de información en montaje', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4136, 'PCG08-PYME','ACTIVO', 'XXXXXX', '239', '4130', 'Anticipos para inmovilizaciones materiales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4137, 'PCG08-PYME','ACTIVO', 'XXXXXX', '24', '4002', 'Inversiones financieras a largo plazo en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4138, 'PCG08-PYME','ACTIVO', 'XXXXXX', '240', '4137', 'Participaciones a largo plazo en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4139, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2403', '4138', 'Participaciones a largo plazo en empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4140, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2404', '4138', 'Participaciones a largo plazo en empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4141, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2405', '4138', 'Participaciones a largo plazo en otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4142, 'PCG08-PYME','ACTIVO', 'XXXXXX', '241', '4137', 'Valores representativos de deuda a largo plazo de partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4143, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2413', '4142', 'Valores representativos de deuda a largo plazo de empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4144, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2414', '4142', 'Valores representativos de deuda a largo plazo de empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4145, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2415', '4142', 'Valores representativos de deuda a largo plazo de otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4146, 'PCG08-PYME','ACTIVO', 'XXXXXX', '242', '4137', 'Créditos a largo plazo a partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4147, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2423', '4146', 'Créditos a largo plazo a empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4148, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2424', '4146', 'Créditos a largo plazo a empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4149, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2425', '4146', 'Créditos a largo plazo a otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4150, 'PCG08-PYME','ACTIVO', 'XXXXXX', '249', '4137', 'Desembolsos pendientes sobre participaciones a largo plazo en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4151, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2493', '4150', 'Desembolsos pendientes sobre participaciones a largo plazo en empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4152, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2494', '4150', 'Desembolsos pendientes sobre participaciones a largo plazo en empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4153, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2495', '4150', 'Desembolsos pendientes sobre participaciones a largo plazo en otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4154, 'PCG08-PYME','ACTIVO', 'XXXXXX', '25', '4002', 'Otras inversiones financieras a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4155, 'PCG08-PYME','ACTIVO', 'XXXXXX', '250', '4154', 'Inversiones financieras a largo plazo en instrumentos de patrimonio', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4156, 'PCG08-PYME','ACTIVO', 'XXXXXX', '251', '4154', 'Valores representativos de deuda a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4157, 'PCG08-PYME','ACTIVO', 'XXXXXX', '252', '4154', 'Créditos a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4158, 'PCG08-PYME','ACTIVO', 'XXXXXX', '253', '4154', 'Créditos a largo plazo por enajenación de inmovilizado', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4159, 'PCG08-PYME','ACTIVO', 'XXXXXX', '254', '4154', 'Créditos a largo plazo al personal', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4160, 'PCG08-PYME','ACTIVO', 'XXXXXX', '255', '4154', 'Activos por derivados financieros a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4161, 'PCG08-PYME','ACTIVO', 'XXXXXX', '258', '4154', 'Imposiciones a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4162, 'PCG08-PYME','ACTIVO', 'XXXXXX', '259', '4154', 'Desembolsos pendientes sobre participaciones en el patrimonio neto a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4163, 'PCG08-PYME','ACTIVO', 'XXXXXX', '26', '4002', 'Fianzas y depósitos constituidos a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4164, 'PCG08-PYME','ACTIVO', 'XXXXXX', '260', '4163', 'Fianzas constituidas a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4165, 'PCG08-PYME','ACTIVO', 'XXXXXX', '261', '4163', 'Depósitos constituidos a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4166, 'PCG08-PYME','ACTIVO', 'XXXXXX', '28', '4002', 'Amortización acumulada del inmovilizado', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4167, 'PCG08-PYME','ACTIVO', 'XXXXXX', '280', '4166', 'Amortización acumulado del inmovilizado intangible', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4168, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2800', '4167', 'Amortización acumulada de investigación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4169, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2801', '4167', 'Amortización acumulada de desarrollo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4170, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2802', '4167', 'Amortización acumulada de concesiones administrativas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4171, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2803', '4167', 'Amortización acumulada de propiedad industrial', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4172, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2805', '4167', 'Amortización acumulada de derechos de transpaso', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4173, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2806', '4167', 'Amortización acumulada de aplicaciones informáticas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4174, 'PCG08-PYME','ACTIVO', 'XXXXXX', '281', '4166', 'Amortización acumulado del inmovilizado material', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4175, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2811', '4174', 'Amortización acumulada de construcciones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4176, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2812', '4174', 'Amortización acumulada de instalaciones técnicas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4177, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2813', '4174', 'Amortización acumulada de maquinaria', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4178, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2814', '4174', 'Amortización acumulada de utillaje', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4179, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2815', '4174', 'Amortización acumulada de otras instalaciones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4180, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2816', '4174', 'Amortización acumulada de mobiliario', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4181, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2817', '4174', 'Amortización acumulada de equipos para proceso de información', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4182, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2818', '4174', 'Amortización acumulada de elementos de transporte', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4183, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2819', '4175', 'Amortización acumulada de otro inmovilizado material', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4184, 'PCG08-PYME','ACTIVO', 'XXXXXX', '282', '4166', 'Amortización acumulada de las inversiones inmobiliarias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4185, 'PCG08-PYME','ACTIVO', 'XXXXXX', '29', '4002', 'Deterioro de valor de activos no corrientes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4186, 'PCG08-PYME','ACTIVO', 'XXXXXX', '290', '4185', 'Deterioro de valor del inmovilizado intangible', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4187, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2900', '4186', 'Deterioro de valor de investigación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4188, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2901', '4186', 'Deterioro de valor de desarrollo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4189, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2902', '4186', 'Deterioro de valor de concesiones administrativas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4190, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2903', '4186', 'Deterioro de valor de propiedad industrial', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4191, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2905', '4186', 'Deterioro de valor de derechos de transpaso', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4192, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2906', '4186', 'Deterioro de valor de aplicaciones informáticas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4193, 'PCG08-PYME','ACTIVO', 'XXXXXX', '291', '4185', 'Deterioro de valor del inmovilizado material', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4194, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2910', '4193', 'Deterioro de valor de terrenos y bienes naturales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4195, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2911', '4193', 'Deterioro de valor de construcciones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4196, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2912', '4193', 'Deterioro de valor de instalaciones técnicas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4197, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2913', '4193', 'Deterioro de valor de maquinaria', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4198, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2914', '4193', 'Deterioro de valor de utillajes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4199, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2915', '4194', 'Deterioro de valor de otras instalaciones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4200, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2916', '4194', 'Deterioro de valor de mobiliario', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4201, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2917', '4194', 'Deterioro de valor de equipos para proceso de información', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4202, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2918', '4194', 'Deterioro de valor de elementos de transporte', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4203, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2919', '4194', 'Deterioro de valor de otro inmovilizado material', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4204, 'PCG08-PYME','ACTIVO', 'XXXXXX', '292', '4185', 'Deterioro de valor de las inversiones inmobiliarias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4205, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2920', '4204', 'Deterioro de valor de terrenos y bienes naturales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4206, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2921', '4204', 'Deterioro de valor de construcciones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4207, 'PCG08-PYME','ACTIVO', 'XXXXXX', '293', '4185', 'Deterioro de valor de participaciones a largo plazo en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4208, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2933', '4207', 'Deterioro de valor de participaciones a largo plazo en empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4209, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2934', '4207', 'Deterioro de valor de sobre participaciones a largo plazo en empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4210, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2935', '4207', 'Deterioro de valor de sobre participaciones a largo plazo en otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4211, 'PCG08-PYME','ACTIVO', 'XXXXXX', '294', '4185', 'Deterioro de valor de valores representativos de deuda a largo plazo en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4212, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2943', '4211', 'Deterioro de valor de valores representativos de deuda a largo plazo en empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4213, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2944', '4211', 'Deterioro de valor de valores representativos de deuda a largo plazo en empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4214, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2945', '4211', 'Deterioro de valor de valores representativos de deuda a largo plazo en otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4215, 'PCG08-PYME','ACTIVO', 'XXXXXX', '295', '4185', 'Deterioro de valor de créditos a largo plazo a partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4216, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2953', '4215', 'Deterioro de valor de créditos a largo plazo a empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4217, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2954', '4215', 'Deterioro de valor de créditos a largo plazo a empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4218, 'PCG08-PYME','ACTIVO', 'XXXXXX', '2955', '4215', 'Deterioro de valor de créditos a largo plazo a otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4219, 'PCG08-PYME','ACTIVO', 'XXXXXX', '296', '4185', 'Deterioro de valor de participaciones en el patrimonio netoa largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4220, 'PCG08-PYME','ACTIVO', 'XXXXXX', '297', '4185', 'Deterioro de valor de valores representativos de deuda a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4221, 'PCG08-PYME','ACTIVO', 'XXXXXX', '298', '4185', 'Deterioro de valor de créditos a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4222, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '30', '4003', 'Comerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4223, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '300', '4222', 'Mercaderías A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4224, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '301', '4222', 'Mercaderías B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4225, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '31', '4003', 'Materias primas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4226, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '310', '4225', 'Materias primas A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4227, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '311', '4225', 'Materias primas B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4228, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '32', '4003', 'Otros aprovisionamientos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4229, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '320', '4228', 'Elementos y conjuntos incorporables', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4230, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '321', '4228', 'Combustibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4231, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '322', '4228', 'Repuestos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4232, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '325', '4228', 'Materiales diversos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4233, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '326', '4228', 'Embalajes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4234, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '327', '4228', 'Envases', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4235, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '328', '4229', 'Material de oficina', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4236, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '33', '4003', 'Productos en curso', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4237, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '330', '4236', 'Productos en curos A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4238, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '331', '4236', 'Productos en curso B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4239, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '34', '4003', 'Productos semiterminados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4240, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '340', '4239', 'Productos semiterminados A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4241, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '341', '4239', 'Productos semiterminados B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4242, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '35', '4003', 'Productos terminados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4243, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '350', '4242', 'Productos terminados A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4244, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '351', '4242', 'Productos terminados B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4245, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '36', '4003', 'Subproductos, residuos y materiales recuperados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4246, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '360', '4245', 'Subproductos A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4247, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '361', '4245', 'Subproductos B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4248, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '365', '4245', 'Residuos A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4249, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '366', '4245', 'Residuos B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4250, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '368', '4245', 'Materiales recuperados A', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4251, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '369', '4245', 'Materiales recuperados B', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4252, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '39', '4003', 'Deterioro de valor de las existencias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4253, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '390', '4252', 'Deterioro de valor de las mercaderías', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4254, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '391', '4252', 'Deterioro de valor de las materias primas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4255, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '392', '4252', 'Deterioro de valor de otros aprovisionamientos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4256, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '393', '4252', 'Deterioro de valor de los productos en curso', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4257, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '394', '4252', 'Deterioro de valor de los productos semiterminados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4258, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '395', '4252', 'Deterioro de valor de los productos terminados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4259, 'PCG08-PYME','EXISTENCIAS', 'XXXXXX', '396', '4252', 'Deterioro de valor de los subproductos, residuos y materiales recuperados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4260, 'PCG08-PYME','ACREEDORES_DEUDORES', 'PROVEEDORES', '40', '4004', 'Proveedores', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4261, 'PCG08-PYME','ACREEDORES_DEUDORES', 'PROVEEDORES', '400', '4260', 'Proveedores', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4262, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4000', '4261', 'Proveedores euros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4263, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4004', '4261', 'Proveedores moneda extranjera', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4264, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4009', '4261', 'Proveedores facturas pendientes de recibir o formalizar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4265, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '401', '4260', 'Proveedores efectos comerciales a pagar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4266, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '403', '4260', 'Proveedores empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4267, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4030', '4266', 'Proveedores empresas del grupo euros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4268, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4031', '4266', 'Efectos comerciales a pagar empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4269, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4034', '4266', 'Proveedores empresas del grupo moneda extranjera', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4270, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4036', '4266', 'Envases y embalajes a devolver a proveedores empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4271, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4039', '4266', 'Proveedores empresas del grupo facturas pendientes de recibir o de formalizar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4272, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '404', '4260', 'Proveedores empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4273, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '405', '4260', 'Proveedores otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4274, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '406', '4260', 'Envases y embalajes a devolver a proveedores', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4275, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '407', '4260', 'Anticipos a proveedores', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4276, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '41', '4004', 'Acreedores varios', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4277, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '410', '4276', 'Acreedores por prestaciones de servicios', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4278, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4100', '4277', 'Acreedores por prestaciones de servicios euros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4279, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4104', '4277', 'Acreedores por prestaciones de servicios moneda extranjera', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4280, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4109', '4277', 'Acreedores por prestaciones de servicios facturas pendientes de recibir o formalizar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4281, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '411', '4276', 'Acreedores efectos comerciales a pagar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4282, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '419', '4276', 'Acreedores por operaciones en común', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4283, 'PCG08-PYME','ACREEDORES_DEUDORES', 'CLIENTES', '43', '4004', 'Clientes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4284, 'PCG08-PYME','ACREEDORES_DEUDORES', 'CLIENTES', '430', '4283', 'Clientes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4285, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4300', '4284', 'Clientes euros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4286, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4304', '4284', 'Clientes moneda extranjera', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4287, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4309', '4284', 'Clientes facturas pendientes de formalizar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4288, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '431', '4283', 'Clientes efectos comerciales a cobrar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4289, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4310', '4288', 'Efectos comerciales en cartera', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4290, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4311', '4288', 'Efectos comerciales descontados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4291, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4312', '4288', 'Efectos comerciales en gestión de cobro', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4292, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4315', '4288', 'Efectos comerciales impagados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4293, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '432', '4283', 'Clientes operaciones de factoring', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4294, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '433', '4283', 'Clientes empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4295, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4330', '4294', 'Clientes empresas del grupo euros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4296, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4331', '4294', 'Efectos comerciales a cobrar empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4297, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4332', '4294', 'Clientes empresas del grupo operaciones de factoring', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4298, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4334', '4294', 'Clientes empresas del grupo moneda extranjera', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4299, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4336', '4294', 'Clientes empresas del grupo dudoso cobro', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4300, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4337', '4294', 'Envases y embalajes a devolver a clientes empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4301, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4339', '4294', 'Clientes empresas del grupo facturas pendientes de formalizar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4302, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '434', '4283', 'Clientes empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4303, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '435', '4283', 'Clientes otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4304, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '436', '4283', 'Clientes de dudoso cobro', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4305, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '437', '4283', 'Envases y embalajes a devolver por clientes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4306, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '438', '4283', 'Anticipos de clientes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4307, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '44', '4004', 'Deudores varios', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4308, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '440', '4307', 'Deudores', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4309, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4400', '4308', 'Deudores euros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4310, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4404', '4308', 'Deudores moneda extranjera', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4311, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4409', '4308', 'Deudores facturas pendientes de formalizar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4312, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '441', '4307', 'Deudores efectos comerciales a cobrar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4313, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4410', '4312', 'Deudores efectos comerciales en cartera', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4314, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4411', '4312', 'Deudores efectos comerciales descontados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4315, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4412', '4312', 'Deudores efectos comerciales en gestión de cobro', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4316, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4415', '4312', 'Deudores efectos comerciales impagados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4317, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '446', '4307', 'Deudores de dusoso cobro', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4318, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '449', '4307', 'Deudores por operaciones en común', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4319, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '46', '4004', 'Personal', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4320, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '460', '4319', 'Anticipos de renumeraciones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4321, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '465', '4319', 'Renumeraciones pendientes de pago', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4322, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '47', '4004', 'Administraciones Públicas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4323, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '470', '4322', 'Hacienda Pública deudora por diversos conceptos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4324, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4700', '4323', 'Hacienda Pública deudora por IVA', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4325, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4708', '4323', 'Hacienda Pública deudora por subvenciones concedidas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4326, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4709', '4323', 'Hacienda Pública deudora por devolución de impuestos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4327, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '471', '4322', 'Organismos de la Seguridad Social deudores', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4328, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '472', '4322', 'Hacienda Pública IVA soportado', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4329, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '473', '4322', 'Hacienda Pública retenciones y pagos a cuenta', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4330, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '474', '4322', 'Activos por impuesto diferido', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4331, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4740', '4330', 'Activos por diferencias temporarias deducibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4332, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4742', '4330', 'Derechos por deducciones y bonificaciones pendientes de aplicar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4333, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4745', '4330', 'Crédito por pérdidasa compensar del ejercicio', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4334, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '475', '4322', 'Hacienda Pública acreedora por conceptos fiscales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4335, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4750', '4334', 'Hacienda Pública acreedora por IVA', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4336, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4751', '4334', 'Hacienda Pública acreedora por retenciones practicadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4337, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4752', '4334', 'Hacienda Pública acreedora por impuesto sobre sociedades', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4338, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4758', '4334', 'Hacienda Pública acreedora por subvenciones a integrar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4339, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '476', '4322', 'Organismos de la Seguridad Social acreedores', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4340, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '477', '4322', 'Hacienda Pública IVA repercutido', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4341, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '479', '4322', 'Pasivos por diferencias temporarias imponibles', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4342, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '48', '4004', 'Ajustes por periodificación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4343, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '480', '4342', 'Gastos anticipados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4344, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '485', '4342', 'Ingresos anticipados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4345, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '49', '4004', 'Deterioro de valor de créditos comerciales y provisiones a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4346, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '490', '4345', 'Deterioro de valor de créditos por operaciones comerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4347, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '493', '4345', 'Deterioro de valor de créditos por operaciones comerciales con partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4348, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4933', '4347', 'Deterioro de valor de créditos por operaciones comerciales con empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4349, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4934', '4347', 'Deterioro de valor de créditos por operaciones comerciales con empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4350, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4935', '4347', 'Deterioro de valor de créditos por operaciones comerciales con otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4351, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '499', '4345', 'Provisiones por operaciones comerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4352, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4994', '4351', 'Provisión para contratos anerosos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4353, 'PCG08-PYME','ACREEDORES_DEUDORES', 'XXXXXX', '4999', '4351', 'Provisión para otras operaciones comerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4354, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '50', '4005', 'Emprésitos deudas con características especiales y otras emisiones análogas a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4355, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '500', '4354', 'Obligaciones y bonos a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4356, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '502', '4354', 'Acciones o participaciones a corto plazo consideradas como pasivos financieros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4357, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '505', '4354', 'Deudas representadas en otros valores negociables a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4358, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '506', '4354', 'Intereses a corto plazo de emprésitos y otras emisiones analógicas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4359, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '507', '4354', 'Dividendos de acciones o participaciones consideradas como pasivos financieros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4360, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '509', '4354', 'Valores negociables amortizados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4361, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5090', '4360', 'Obligaciones y bonos amortizados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4362, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5095', '4360', 'Otros valores negociables amortizados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4363, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '51', '4005', 'Deudas a corto plazo con partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4364, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '510', '4363', 'Deudas a corto plazo con entidades de crédito vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4365, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5103', '4364', 'Deudas a corto plazo con entidades de crédito empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4366, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5104', '4364', 'Deudas a corto plazo con entidades de crédito empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4367, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5105', '4364', 'Deudas a corto plazo con otras entidades de crédito vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4368, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '511', '4363', 'Proveedores de inmovilizado a corto plazo partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4369, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5113', '4368', 'Proveedores de inmovilizado a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4370, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5114', '4368', 'Proveedores de inmovilizado a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4371, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5115', '4368', 'Proveedores de inmovilizado a corto plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4372, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '512', '4363', 'Acreedores por arrendamiento financiero a corto plazo partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4373, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5123', '4372', 'Acreedores por arrendamiento financiero a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4374, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5124', '4372', 'Acreedores por arrendamiento financiero a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4375, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5125', '4372', 'Acreedores por arrendamiento financiero a corto plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4376, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '513', '4363', 'Otras deudas a corto plazo con partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4377, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5133', '4376', 'Otras deudas a corto plazo con empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4378, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5134', '4376', 'Otras deudas a corto plazo con empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4379, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5135', '4376', 'Otras deudas a corto plazo con partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4380, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '514', '4363', 'Intereses a corto plazo con partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4381, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5143', '4380', 'Intereses a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4382, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5144', '4380', 'Intereses a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4383, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5145', '4380', 'Intereses deudas a corto plazo partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4384, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '52', '4005', 'Deudas a corto plazo por préstamos recibidos y otros conceptos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4385, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '520', '4384', 'Deudas a corto plazo con entidades de crédito', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4386, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5200', '4385', 'Préstamos a corto plazo de entidades de crédito', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4387, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5201', '4385', 'Deudas a corto plazo por crédito dispuesto', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4388, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5208', '4385', 'Deudas por efectos descontados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4389, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5209', '4385', 'Deudas por operaciones de factoring', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4390, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '521', '4384', 'Deudas a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4391, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '522', '4384', 'Deudas a corto plazo transformables en subvenciones donaciones y legados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4392, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '523', '4384', 'Proveedores de inmovilizado a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4393, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '526', '4384', 'Dividendo activo a pagar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4394, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '527', '4384', 'Intereses a corto plazo de deudas con entidades de crédito', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4395, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '528', '4384', 'Intereses a corto plazo de deudas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4396, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '529', '4384', 'Provisiones a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4397, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5291', '4396', 'Provisión a corto plazo para impuestos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4398, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5292', '4396', 'Provisión a corto plazo para otras responsabilidades', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4399, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5293', '4396', 'Provisión a corto plazo por desmantelamiento retiro o rehabilitación del inmovilizado', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4400, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5295', '4396', 'Provisión a corto plazo para actuaciones medioambientales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4401, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '53', '4005', 'Inversiones financieras a corto plazo en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4402, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '530', '4401', 'Participaciones a corto plazo en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4403, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5303', '4402', 'Participaciones a corto plazo en empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4404, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5304', '4402', 'Participaciones a corto plazo en empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4405, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5305', '4402', 'Participaciones a corto plazo en otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4406, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '531', '4401', 'Valores representativos de deuda a corto plazo de partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4407, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5313', '4406', 'Valores representativos de deuda a corto plazo de empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4408, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5314', '4406', 'Valores representativos de deuda a corto plazo de empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4409, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5315', '4406', 'Valores representativos de deuda a corto plazo de otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4410, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '532', '4401', 'Créditos a corto plazo a partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4411, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5323', '4410', 'Créditos a corto plazo a empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4412, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5324', '4410', 'Créditos a corto plazo a empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4413, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5325', '4410', 'Créditos a corto plazo a otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4414, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '533', '4401', 'Intereses a corto plazo de valores representativos de deuda de partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4415, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5333', '4414', 'Intereses a corto plazo de valores representativos de deuda en empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4416, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5334', '4414', 'Intereses a corto plazo de valores representativos de deuda en empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4417, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5335', '4414', 'Intereses a corto plazo de valores representativos de deuda en otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4418, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '534', '4401', 'Intereses a corto plazo de créditos a partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4419, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5343', '4418', 'Intereses a corto plazo de créditos a empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4420, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5344', '4418', 'Intereses a corto plazo de créditos a empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4421, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5345', '4418', 'Intereses a corto plazo de créditos a otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4422, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '535', '4401', 'Dividendo a cobrar de inversiones financieras en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4423, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5353', '4422', 'Dividendo a cobrar de empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4424, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5354', '4422', 'Dividendo a cobrar de empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4425, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5355', '4422', 'Dividendo a cobrar de otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4426, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '539', '4401', 'Desembolsos pendientes sobre participaciones a corto plazo en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4427, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5393', '4426', 'Desembolsos pendientes sobre participaciones a corto plazo en empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4428, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5394', '4426', 'Desembolsos pendientes sobre participaciones a corto plazo en empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4429, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5395', '4426', 'Desembolsos pendientes sobre participaciones a corto plazo en otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4430, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '54', '4005', 'Otras inversiones financieras a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4431, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '540', '4430', 'Inversiones financieras a corto plazo en instrumentos de patrimonio', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4432, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '541', '4430', 'Valores representativos de deuda a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4433, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '542', '4430', 'Créditos a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4434, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '543', '4430', 'Créditos a corto plazo por enejenación de inmovilizado', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4435, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '544', '4430', 'Créditos a corto plazo al personal', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4436, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '545', '4430', 'Dividendo a cobrar', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4437, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '546', '4430', 'Intereses a corto plazo de valores reprsentativos de deuda', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4438, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '547', '4430', 'Intereses a corto plazo de créditos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4439, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '548', '4430', 'Imposiciones a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4440, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '549', '4430', 'Desembolsos pendientes sobre participaciones en el patrimonio neto a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4441, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '55', '4005', 'Otras cuentas no bancarias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4442, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '550', '4441', 'Titular de la explotación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4443, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '551', '4441', 'Cuenta corriente con socios y administradores', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4444, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '552', '4441', 'Cuenta corriente otras personas y entidades vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4445, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5523', '4444', 'Cuenta corriente con empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4446, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5524', '4444', 'Cuenta corriente con empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4447, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5525', '4444', 'Cuenta corriente con otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4448, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '554', '4441', 'Cuenta corriente con uniones temporales de empresas y comunidades de bienes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4449, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '555', '4441', 'Partidas pendientes de aplicación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4450, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '556', '4441', 'Desembolsos exigidos sobre participaciones en el patrimonio neto', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4451, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5563', '4450', 'Desembolsos exigidos sobre participaciones empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4452, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5564', '4450', 'Desembolsos exigidos sobre participaciones empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4453, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5565', '4450', 'Desembolsos exigidos sobre participaciones otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4454, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5566', '4450', 'Desembolsos exigidos sobre participaciones otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4455, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '557', '4441', 'Dividendo activo a cuenta', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4456, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '558', '4441', 'Socios por desembolsos exigidos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4457, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5580', '4456', 'Socios por desembolsos exigidos sobre acciones o participaciones ordinarias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4458, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5585', '4456', 'Socios por desembolsos exigidos sobre acciones o participaciones consideradas como pasivos financieros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4459, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '559', '4441', 'Derivados financieros a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4460, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5590', '4459', 'Activos por derivados financieros a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4461, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5595', '4459', 'Pasivos por derivados financieros a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4462, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '56', '4005', 'Finanzas y depósitos recibidos y constituidos a corto plazo y ajustes por periodificación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4463, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '560', '4462', 'Finanzas recibidas a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4464, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '561', '4462', 'Depósitos recibidos a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4465, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '565', '4462', 'Finanzas constituidas a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4466, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '566', '4462', 'Depósitos constituidos a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4467, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '567', '4462', 'Intereses pagados por anticipado', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4468, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '568', '4462', 'Intereses cobrados a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4469, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '57', '4005', 'Tesorería', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4470, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'CAJA', '570', '4469', 'Caja euros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4471, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '571', '4469', 'Caja moneda extranjera', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4472, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'BANCOS', '572', '4469', 'Bancos e instituciones de crédito cc vista euros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4473, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '573', '4469', 'Bancos e instituciones de crédito cc vista moneda extranjera', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4474, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '574', '4469', 'Bancos e instituciones de crédito cuentas de ahorro euros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4475, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '575', '4469', 'Bancos e instituciones de crédito cuentas de ahorro moneda extranjera', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4476, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '576', '4469', 'Inversiones a corto plazo de gran liquidez', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4477, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '59', '4005', 'Deterioro del valor de las inversiones financieras a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4478, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '593', '4477', 'Deterioro del valor de participaciones a corto plazo en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4479, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5933', '4478', 'Deterioro del valor de participaciones a corto plazo en empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4480, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5934', '4478', 'Deterioro del valor de participaciones a corto plazo en empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4481, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5935', '4478', 'Deterioro del valor de participaciones a corto plazo en otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4482, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '594', '4477', 'Deterioro del valor de valores representativos de deuda a corto plazo en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4483, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5943', '4482', 'Deterioro del valor de valores representativos de deuda a corto plazo en empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4484, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5944', '4482', 'Deterioro del valor de valores representativos de deuda a corto plazo en empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4485, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5945', '4482', 'Deterioro del valor de valores representativos de deuda a corto plazo en otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4486, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '595', '4477', 'Deterioro del valor de créditos a corto plazo en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4487, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5953', '4486', 'Deterioro del valor de créditos a corto plazo en empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4488, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5954', '4486', 'Deterioro del valor de créditos a corto plazo en empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4489, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '5955', '4486', 'Deterioro del valor de créditos a corto plazo en otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4490, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '596', '4477', 'Deterioro del valor de participaciones a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4491, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '597', '4477', 'Deterioro del valor de valores representativos de deuda a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4492, 'PCG08-PYME','CUENTAS_FINANCIERAS', 'XXXXXX', '598', '4477', 'Deterioro de valor de créditos a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4493, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '60', '4006', 'Compras', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4494, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'COMPRAS', '600', '4493', 'Compras de mercaderías', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4495, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'COMPRAS', '601', '4493', 'Compras de materias primas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4496, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '602', '4493', 'Compras de otros aprovisionamientos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4497, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '606', '4493', 'Descuentos sobre compras por pronto pago', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4498, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6060', '4497', 'Descuentos sobre compras por pronto pago de mercaderías', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4499, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6061', '4497', 'Descuentos sobre compras por pronto pago de materias primas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4500, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6062', '4497', 'Descuentos sobre compras por pronto pago de otros aprovisionamientos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4501, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'COMPRAS', '607', '4493', 'Trabajos realizados por otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4502, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '608', '4493', 'Devoluciones de compras y operaciones similares', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4503, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6080', '4502', 'Devoluciones de compras de mercaderías', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4504, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6081', '4502', 'Devoluciones de compras de materias primas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4505, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6082', '4502', 'Devoluciones de compras de otros aprovisionamientos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4506, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '609', '4493', 'Rappels por compras', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4507, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6090', '4506', 'Rappels por compras de mercaderías', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4508, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6091', '4506', 'Rappels por compras de materias primas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4509, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6092', '4506', 'Rappels por compras de otros aprovisionamientos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4510, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '61', '4006', 'Variación de existencias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4511, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '610', '4510', 'Variación de existencias de mercaderías', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4512, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '611', '4510', 'Variación de existencias de materias primas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4513, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '612', '4510', 'Variación de existencias de otros aprovisionamientos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4514, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '62', '4006', 'Servicios exteriores', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4515, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '620', '4514', 'Gastos en investigación y desarrollo del ejercicio', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4516, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '621', '4514', 'Arrendamientos y cánones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4517, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '622', '4514', 'Reparaciones y conservación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4518, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '623', '4514', 'Servicios profesionales independientes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4519, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '624', '4514', 'Transportes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4520, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '625', '4514', 'Primas de seguros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4521, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '626', '4514', 'Servicios bancarios y similares', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4522, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '627', '4514', 'Publicidad, propaganda y relaciones públicas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4523, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '628', '4514', 'Suministros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4524, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '629', '4514', 'Otros servicios', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4525, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '63', '4006', 'Tributos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4526, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '630', '4525', 'Impuesto sobre benecifios', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4527, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6300', '4526', 'Impuesto corriente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4528, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6301', '4526', 'Impuesto diferido', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4529, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '631', '4525', 'Otros tributos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4530, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '633', '4525', 'Ajustes negativos en la imposición sobre beneficios', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4531, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '634', '4525', 'Ajustes negativos en la imposición indirecta', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4532, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6341', '4531', 'Ajustes negativos en IVA de activo corriente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4533, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6342', '4531', 'Ajustes negativos en IVA de inversiones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4534, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '636', '4525', 'Devolución de impuestos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4535, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '638', '4525', 'Ajustes positivos en la imposición sobre beneficios', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4536, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '639', '4525', 'Ajustes positivos en la imposición directa', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4537, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6391', '4536', 'Ajustes positivos en IVA de activo corriente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4538, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6392', '4536', 'Ajustes positivos en IVA de inversiones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4539, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '64', '4006', 'Gastos de personal', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4540, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '640', '4539', 'Sueldos y salarios', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4541, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '641', '4539', 'Indemnizaciones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4542, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '642', '4539', 'Seguridad social a cargo de la empresa', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4543, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '649', '4539', 'Otros gastos sociales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4544, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '65', '4006', 'Otros gastos de gestión', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4545, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '650', '4544', 'Pérdidas de créditos comerciales incobrables', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4546, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '651', '4544', 'Resultados de operaciones en común', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4547, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6510', '4546', 'Beneficio transferido gestor', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4548, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6511', '4546', 'Pérdida soportada participe o asociado no gestor', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4549, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '659', '4544', 'Otras pérdidas en gestión corriente', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4550, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '66', '4006', 'Gastos financieros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4551, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '660', '4550', 'Gastos financieros por actualización de provisiones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4552, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '661', '4550', 'Intereses de obligaciones y bonos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4553, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6610', '4452', 'Intereses de obligaciones y bonos a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4554, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6611', '4452', 'Intereses de obligaciones y bonos a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4555, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6612', '4452', 'Intereses de obligaciones y bonos a largo plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4556, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6613', '4452', 'Intereses de obligaciones y bonos a largo plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4557, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6615', '4452', 'Intereses de obligaciones y bonos a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4558, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6616', '4452', 'Intereses de obligaciones y bonos a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4559, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6617', '4452', 'Intereses de obligaciones y bonos a corto plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4560, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6618', '4452', 'Intereses de obligaciones y bonos a corto plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4561, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '662', '4550', 'Intereses de deudas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4562, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6620', '4561', 'Intereses de deudas empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4563, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6621', '4561', 'Intereses de deudas empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4564, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6622', '4561', 'Intereses de deudas otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4565, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6623', '4561', 'Intereses de deudas con entidades de crédito', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4566, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6624', '4561', 'Intereses de deudas otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4567, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '663', '4550', 'Pérdidas por valorización de activos y pasivos financieros por su valor razonable', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4568, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '664', '4550', 'Gastos por dividendos de acciones o participaciones consideradas como pasivos financieros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4569, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6640', '4568', 'Dividendos de pasivos empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4570, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6641', '4568', 'Dividendos de pasivos empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4571, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6642', '4568', 'Dividendos de pasivos otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4572, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6643', '4568', 'Dividendos de pasivos otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4573, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '665', '4550', 'Intereses por descuento de efectos y operaciones de factoring', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4574, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6650', '4573', 'Intereses por descuento de efectos en entidades de crédito del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4575, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6651', '4573', 'Intereses por descuento de efectos en entidades de crédito asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4576, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6652', '4573', 'Intereses por descuento de efectos en entidades de crédito vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4577, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6653', '4573', 'Intereses por descuento de efectos en otras entidades de crédito', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4578, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6654', '4573', 'Intereses por operaciones de factoring con entidades de crédito del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4579, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6655', '4573', 'Intereses por operaciones de factoring con entidades de crédito asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4580, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6656', '4573', 'Intereses por operaciones de factoring con otras entidades de crédito vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4581, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6657', '4573', 'Intereses por operaciones de factoring con otras entidades de crédito', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4582, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '666', '4550', 'Pérdidas en participaciones y valores representativos de deuda', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4583, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6660', '4582', 'Pérdidas en valores representativos de deuda a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4584, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6661', '4582', 'Pérdidas en valores representativos de deuda a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4585, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6662', '4582', 'Pérdidas en valores representativos de deuda a largo plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4586, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6663', '4582', 'Pérdidas en participaciones y valores representativos de deuda a largo plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4587, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6665', '4582', 'Pérdidas en participaciones y valores representativos de deuda a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4588, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6666', '4582', 'Pérdidas en participaciones y valores representativos de deuda a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4589, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6667', '4582', 'Pérdidas en valores representativos de deuda a corto plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4590, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6668', '4582', 'Pérdidas en valores representativos de deuda a corto plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4591, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '667', '4550', 'Pérdidas de créditos no comerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4592, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6670', '4591', 'Pérdidas de créditos a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4593, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6671', '4591', 'Pérdidas de créditos a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4594, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6672', '4591', 'Pérdidas de créditos a largo plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4595, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6673', '4591', 'Pérdidas de créditos a largo plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4596, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6675', '4591', 'Pérdidas de créditos a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4597, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6676', '4591', 'Pérdidas de créditos a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4598, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6677', '4591', 'Pérdidas de créditos a corto plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4599, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6678', '4591', 'Pérdidas de créditos a corto plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4600, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '668', '4550', 'Diferencias negativas de cambio', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4601, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '669', '4550', 'Otros gastos financieros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4602, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '67', '4006', 'Pérdidas procedentes de activos no corrientes y gastos excepcionales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4603, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '670', '4602', 'Pérdidas procedentes del inmovilizado intangible', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4604, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '671', '4602', 'Pérdidas procedentes del inmovilizado material', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4605, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '672', '4602', 'Pérdidas procedentes de las inversiones inmobiliarias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4607, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '673', '4602', 'Pérdidas procedentes de participaciones a largo plazo en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4608, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6733', '4607', 'Pérdidas procedentes de participaciones a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4609, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6734', '4607', 'Pérdidas procedentes de participaciones a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4610, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6735', '4607', 'Pérdidas procedentes de participaciones a largo plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4611, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '675', '4602', 'Pérdidas por operaciones con obligaciones propias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4612, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '678', '4602', 'Gastos excepcionales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4613, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '68', '4006', 'Dotaciones para amortizaciones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4614, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '680', '4613', 'Amortización del inmovilizado intangible', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4615, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '681', '4613', 'Amortización del inmovilizado material', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4616, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '682', '4613', 'Amortización de las inversiones inmobiliarias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4617, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '69', '4006', 'Pérdidas por deterioro y otras dotaciones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4618, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '690', '4617', 'Pérdidas por deterioro del inmovilizado intangible', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4619, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '691', '4617', 'Pérdidas por deterioro del inmovilizado material', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4620, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '692', '4617', 'Pérdidas por deterioro de las inversiones inmobiliarias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4621, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '693', '4617', 'Pérdidas por deterioro de existencias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4622, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6930', '4621', 'Pérdidas por deterioro de productos terminados y en curso de fabricación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4623, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6931', '4621', 'Pérdidas por deterioro de mercaderías', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4624, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6932', '4621', 'Pérdidas por deterioro de materias primas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4625, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6933', '4621', 'Pérdidas por deterioro de otros aprovisionamientos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4626, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '694', '4617', 'Pérdidas por deterioro de créditos por operaciones comerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4627, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '695', '4617', 'Dotación a la provisión por operaciones comerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4628, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6954', '4627', 'Dotación a la provisión por contratos onerosos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4629, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6959', '4628', 'Dotación a la provisión para otras operaciones comerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4630, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '696', '4617', 'Pérdidas por deterioro de participaciones y valores representativos de deuda a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4631, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6960', '4630', 'Pérdidas por deterioro de participaciones en instrumentos de patrimonio neto a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4632, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6961', '4630', 'Pérdidas por deterioro de participaciones en instrumentos de patrimonio neto a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4633, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6962', '4630', 'Pérdidas por deterioro de participaciones en instrumentos de patrimonio neto a largo plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4634, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6963', '4630', 'Pérdidas por deterioro de participaciones en instrumentos de patrimonio neto a largo plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4635, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6965', '4630', 'Pérdidas por deterioro en valores representativos de deuda a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4636, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6966', '4630', 'Pérdidas por deterioro en valores representativos de deuda a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4637, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6967', '4630', 'Pérdidas por deterioro en valores representativos de deuda a largo plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4638, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6968', '4630', 'Pérdidas por deterioro en valores representativos de deuda a largo plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4639, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '697', '4617', 'Pérdidas por deterioro de créditos a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4640, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6970', '4639', 'Pérdidas por deterioro de créditos a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4641, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6971', '4639', 'Pérdidas por deterioro de créditos a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4642, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6972', '4639', 'Pérdidas por deterioro de créditos a largo plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4643, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6973', '4639', 'Pérdidas por deterioro de créditos a largo plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4644, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '698', '4617', 'Pérdidas por deterioro de participaciones y valores representativos de deuda a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4645, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6980', '4644', 'Pérdidas por deterioro de participaciones en instrumentos de patrimonio neto a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4646, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6981', '4644', 'Pérdidas por deterioro de participaciones en instrumentos de patrimonio neto a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4647, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6985', '4644', 'Pérdidas por deterioro en valores representativos de deuda a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4648, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6986', '4644', 'Pérdidas por deterioro en valores representativos de deuda a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4649, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6988', '4644', 'Pérdidas por deterioro en valores representativos de deuda a corto plazo de otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4650, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '699', '4617', 'Pérdidas por deterioro de crédito a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4651, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6990', '4650', 'Pérdidas por deterioro de crédito a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4652, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6991', '4650', 'Pérdidas por deterioro de crédito a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4653, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6992', '4650', 'Pérdidas por deterioro de crédito a corto plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4654, 'PCG08-PYME','COMPRAS_Y_GASTOS', 'XXXXXX', '6993', '4650', 'Pérdidas por deterioro de crédito a corto plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4655, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '70', '4007', 'Ventas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4656, 'PCG08-PYME','VENTAS_E_INGRESOS', 'VENTAS', '700', '4655', 'Ventas de mercaderías', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4657, 'PCG08-PYME','VENTAS_E_INGRESOS', 'VENTAS', '701', '4655', 'Ventas de productos terminados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4658, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '702', '4655', 'Ventas de productos semiterminados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4659, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '703', '4655', 'Ventas de subproductos y residuos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4660, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '704', '4655', 'Ventas de envases y embalajes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4661, 'PCG08-PYME','VENTAS_E_INGRESOS', 'VENTAS', '705', '4655', 'Prestaciones de servicios', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4662, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '706', '4655', 'Descuentos sobre ventas por pronto pago', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4663, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7060', '4662', 'Descuentos sobre ventas por pronto pago de mercaderías', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4664, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7061', '4662', 'Descuentos sobre ventas por pronto pago de productos terminados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4665, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7062', '4662', 'Descuentos sobre ventas por pronto pago de productos semiterminados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4666, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7063', '4662', 'Descuentos sobre ventas por pronto pago de subproductos y residuos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4667, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '708', '4655', 'Devoluciones de ventas y operacioes similares', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4668, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7080', '4667', 'Devoluciones de ventas de mercaderías', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4669, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7081', '4667', 'Devoluciones de ventas de productos terminados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4670, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7082', '4667', 'Devoluciones de ventas de productos semiterminados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4671, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7083', '4667', 'Devoluciones de ventas de subproductos y residuos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4672, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7084', '4667', 'Devoluciones de ventas de envases y embalajes', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4673, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '71', '4007', 'Variación de existencias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4674, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '710', '4673', 'Variación de existencias de productos en curso', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4675, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '711', '4673', 'Variación de existencias de productos semiterminados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4676, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '712', '4673', 'Variación de existencias de productos terminados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4677, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '713', '4673', 'Variación de existencias de subproductos, residuos y materiales recuperados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4678, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '73', '4007', 'Trabajos realizados para la empresa', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4679, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '730', '4678', 'Trabajos realizados para el inmovilizado intangible', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4680, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '731', '4678', 'Trabajos realizados para el inmovilizado tangible', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4681, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '732', '4678', 'Trabajos realizados en inversiones inmobiliarias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4682, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '733', '4678', 'Trabajos realizados para el inmovilizado material en curso', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4683, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '74', '4007', 'Subvenciones, donaciones y legados', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4684, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '740', '4683', 'Subvenciones, donaciones y legados a la explotación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4685, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '746', '4683', 'Subvenciones, donaciones y legados de capital transferidos al resultado del ejercicio', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4686, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '747', '4683', 'Otras subvenciones, donaciones y legados transferidos al resultado del ejercicio', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4687, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '75', '4007', 'Otros ingresos de gestión', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4688, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '751', '4687', 'Resultados de operaciones en común', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4689, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7510', '4688', 'Pérdida transferida gestor', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4690, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7511', '4688', 'Beneficio atribuido participe o asociado no gestor', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4691, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '752', '4687', 'Ingreso por arrendamiento', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4692, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '753', '4687', 'Ingresos de propiedad industrial cedida en explotación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4693, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '754', '4687', 'Ingresos por comisiones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4694, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '755', '4687', 'Ingresos por servicios al personal', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4695, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '759', '4687', 'Ingresos por servicios diversos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4696, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '76', '4007', 'Ingresos financieros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4697, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '760', '4696', 'Ingresos de participaciones en instrumentos de patrimonio', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4698, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7600', '4697', 'Ingresos de participaciones en instrumentos de patrimonio empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4699, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7601', '4697', 'Ingresos de participaciones en instrumentos de patrimonio empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4700, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7602', '4697', 'Ingresos de participaciones en instrumentos de patrimonio otras partes asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4701, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7603', '4697', 'Ingresos de participaciones en instrumentos de patrimonio otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4702, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '761', '4696', 'Ingresos de valores representativos de deuda', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4703, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7610', '4702', 'Ingresos de valores representativos de deuda empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4704, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7611', '4702', 'Ingresos de valores representativos de deuda empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4705, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7612', '4702', 'Ingresos de valores representativos de deuda otras partes asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4706, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7613', '4702', 'Ingresos de valores representativos de deuda otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4707, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '762', '4696', 'Ingresos de créditos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4708, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7620', '4707', 'Ingresos de créditos a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4709, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '76200', '4708', 'Ingresos de crédito a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4710, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '76201', '4708', 'Ingresos de crédito a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4711, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '76202', '4708', 'Ingresos de crédito a largo plazo otras partes asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4712, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '76203', '4708', 'Ingresos de crédito a largo plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4713, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7621', '4707', 'Ingresos de créditos a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4714, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '76210', '4713', 'Ingresos de crédito a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4715, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '76211', '4713', 'Ingresos de crédito a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4716, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '76212', '4713', 'Ingresos de crédito a corto plazo otras partes asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4717, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '76213', '4713', 'Ingresos de crédito a corto plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4718, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '763', '4696', 'Beneficios por valorización de activos y pasivos financieros por su valor razonable', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4719, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '766', '4696', 'Beneficios en participaciones y valores representativos de deuda', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4720, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7660', '4719', 'Beneficios en participaciones y valores representativos de deuda a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4721, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7661', '4719', 'Beneficios en participaciones y valores representativos de deuda a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4722, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7662', '4719', 'Beneficios en participaciones y valores representativos de deuda a largo plazo otras partes asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4723, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7663', '4719', 'Beneficios en participaciones y valores representativos de deuda a largo plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4724, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7665', '4719', 'Beneficios en participaciones y valores representativos de deuda a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4725, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7666', '4719', 'Beneficios en participaciones y valores representativos de deuda a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4726, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7667', '4719', 'Beneficios en participaciones y valores representativos de deuda a corto plazo otras partes asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4727, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7668', '4719', 'Beneficios en participaciones y valores representativos de deuda a corto plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4728, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '768', '4696', 'Diferencias positivas de cambio', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4729, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '769', '4696', 'Otros ingresos financieros', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4730, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '77', '4007', 'Beneficios procedentes de activos no corrientes e ingresos excepcionales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4731, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '770', '4730', 'Beneficios procedentes del inmovilizado intangible', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4732, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '771', '4730', 'Beneficios procedentes del inmovilizado material', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4733, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '772', '4730', 'Beneficios procedentes de las inversiones inmobiliarias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4734, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '773', '4730', 'Beneficios procedentes de participaciones a largo plazo en partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4735, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7733', '4734', 'Beneficios procedentes de participaciones a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4736, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7734', '4734', 'Beneficios procedentes de participaciones a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4737, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7735', '4734', 'Beneficios procedentes de participaciones a largo plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4738, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '775', '4730', 'Beneficios por operaciones con obligaciones propias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4739, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '778', '4730', 'Ingresos excepcionales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4741, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '79', '4007', 'Excesos y aplicaciones de provisiones y pérdidas por deterioro', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4742, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '790', '4741', 'Revisión del deterioro del inmovilizado intangible', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4743, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '791', '4741', 'Revisión del deterioro del inmovilizado material', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4744, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '792', '4741', 'Revisión del deterioro de las inversiones inmobiliarias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4745, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '793', '4741', 'Revisión del deterioro de las existencias', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4746, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7930', '4745', 'Revisión del deterioro de productos terminados y en curso de fabricación', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4747, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7931', '4745', 'Revisión del deterioro de mercaderías', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4748, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7932', '4745', 'Revisión del deterioro de materias primas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4749, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7933', '4745', 'Revisión del deterioro de otros aprovisionamientos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4750, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '794', '4741', 'Revisión del deterioro de créditos por operaciones comerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4751, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '795', '4741', 'Exceso de provisiones', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4752, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7951', '4751', 'Exceso de provisión para impuestos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4753, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7952', '4751', 'Exceso de provisión para otras responsabilidades', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4755, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7954', '4751', 'Exceso de provisión para operaciones comerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4756, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '79544', '4755', 'Exceso de provisión por contratos onerosos', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4757, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '79549', '4755', 'Exceso de provisión para otras operaciones comerciales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4758, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7955', '4751', 'Exceso de provisión para actuaciones medioambienteales', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4759, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '796', '4741', 'Revisión del deterioro de participaciones y valores representativos de deuda a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4760, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7960', '4759', 'Revisión del deterioro de participaciones en instrumentos de patrimonio neto a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4761, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7961', '4759', 'Revisión del deterioro de participaciones en instrumentos de patrimonio neto a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4762, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7962', '4759', 'Revisión del deterioro de participaciones en instrumentos de patrimonio neto a largo plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4763, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7963', '4759', 'Revisión del deterioro de participaciones en instrumentos de patrimonio neto a largo plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4764, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7965', '4759', 'Revisión del deterioro de valores representativos a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4765, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7966', '4759', 'Revisión del deterioro de valores representativos a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4766, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7967', '4759', 'Revisión del deterioro de valores representativos a largo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4767, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7968', '4759', 'Revisión del deterioro de valores representativos a largo plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4768, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '797', '4741', 'Revisión del deterioro de créditos a largo plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4769, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7970', '4768', 'Revisión del deterioro de créditos a largo plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4770, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7971', '4768', 'Revisión del deterioro de créditos a largo plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4771, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7972', '4768', 'Revisión del deterioro de créditos a largo plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4772, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7973', '4768', 'Revisión del deterioro de créditos a largo plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4773, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '798', '4741', 'Revisión del deterioro de participaciones y valores representativos de deuda a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4774, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7980', '4773', 'Revisión del deterioro de participaciones en instrumentos de patrimonio neto a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4775, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7981', '4773', 'Revisión del deterioro de participaciones en instrumentos de patrimonio neto a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4776, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7985', '4773', 'Revisión del deterioro de valores representativos de deuda a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4777, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7986', '4773', 'Revisión del deterioro de valores representativos de deuda a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4778, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7987', '4773', 'Revisión del deterioro de valores representativos de deuda a corto plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4779, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7988', '4773', 'Revisión del deterioro de valores representativos de deuda a corto plazo otras empresas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4780, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '799', '4741', 'Revisión del deterioro de créditos a corto plazo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4781, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7990', '4780', 'Revisión del deterioro de créditos a corto plazo empresas del grupo', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4782, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7991', '4780', 'Revisión del deterioro de créditos a corto plazo empresas asociadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4783, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7992', '4780', 'Revisión del deterioro de créditos a corto plazo otras partes vinculadas', '1');
INSERT INTO llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (4784, 'PCG08-PYME','VENTAS_E_INGRESOS', 'XXXXXX', '7993', '4780', 'Revisión del deterioro de créditos a corto plazo otras empresas', '1');


DROP TABLE llx_texts;


DROP TABLE llx_c_email_templates;
create table llx_c_email_templates
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  entity		  integer DEFAULT 1 NOT NULL,	  -- multi company id
  module          varchar(32),                    -- Nom du module en rapport avec le modele
  type_template   varchar(32),  				  -- template for which type of email (send invoice by email, send order, ...)
  lang            varchar(6),
  private         smallint DEFAULT 0 NOT NULL,    -- Template public or private
  fk_user         integer,                        -- Id utilisateur si modele prive, sinon null
  datec           datetime,
  tms             timestamp,
  label           varchar(255),					  -- Label of predefined email
  position        smallint,					      -- Position
  active          tinyint DEFAULT 1  NOT NULL,
  topic			  text,                           -- Predefined topic
  content         text                            -- Predefined text
) ENGINE=innodb;


ALTER TABLE llx_c_departements DROP FOREIGN KEY fk_departements_fk_region;
--UPDATE llx_c_regions SET rowid = 0 where rowid = 1;

ALTER TABLE llx_c_regions ADD UNIQUE INDEX uk_code_region (code_region);

DELETE FROM llx_c_departements WHERE fk_region NOT IN (select code_region from llx_c_regions) AND fk_region IS NOT NULL AND fk_region <> 0;

ALTER TABLE llx_c_departements ADD CONSTRAINT fk_departements_code_region FOREIGN KEY (fk_region) REFERENCES llx_c_regions (code_region);


CREATE TABLE llx_holiday_types (
  rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  label varchar(45) NOT NULL,
  description varchar(255) NOT NULL,
  affect integer NOT NULL,
  delay integer NOT NULL,
  insertAt DATETIME NOT NULL,
  updateAt DATETIME,
  deleteAt DATETIME,
  nbCongesDeducted varchar(255) NOT NULL,
  nbCongesEveryMonth varchar(255) NOT NULL
) ENGINE=innodb;

-- Change on table c_civilite
ALTER TABLE llx_c_civilite DROP INDEX uk_c_civilite;
ALTER TABLE llx_c_civilite RENAME TO llx_c_civility;
ALTER TABLE llx_c_civility CHANGE COLUMN civilite label VARCHAR(50);
ALTER TABLE llx_c_civility ADD UNIQUE INDEX uk_c_civility(code);
ALTER TABLE llx_adherent CHANGE COLUMN civilite civility VARCHAR(6);
ALTER TABLE llx_socpeople CHANGE COLUMN civilite civility VARCHAR(6);
ALTER TABLE llx_user CHANGE COLUMN civilite civility VARCHAR(6);

ALTER TABLE llx_societe MODIFY COLUMN nom varchar(128);
ALTER TABLE llx_adherent MODIFY COLUMN societe varchar(128);

ALTER TABLE llx_c_type_fees CHANGE COLUMN libelle label VARCHAR(30);
ALTER TABLE llx_c_type_fees ADD COLUMN accountancy_code varchar(32) DEFAULT NULL AFTER label;

ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_fk_element (fk_element);

ALTER TABLE llx_projet_task_time ADD INDEX idx_projet_task_time_task (fk_task);
ALTER TABLE llx_projet_task_time ADD INDEX idx_projet_task_time_date (task_date);
ALTER TABLE llx_projet_task_time ADD INDEX idx_projet_task_time_datehour (task_datehour);

ALTER TABLE llx_projet_task MODIFY COLUMN duration_effective real DEFAULT 0 NULL;
ALTER TABLE llx_projet_task MODIFY COLUMN planned_workload real DEFAULT 0 NULL;
  
-- VPGSQL8.2 ALTER TABLE llx_projet_task ALTER COLUMN planned_workload DROP NOT NULL;

-- add extrafield on ficheinter lines
CREATE TABLE llx_fichinterdet_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_fichinterdet_extrafields ADD INDEX idx_ficheinterdet_extrafields (fk_object);

CREATE TABLE llx_usergroup_extrafields (
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_usergroup_extrafields ADD INDEX idx_usergroup_extrafields (fk_object);

ALTER TABLE llx_contrat ADD COLUMN model_pdf varchar(255) DEFAULT NULL AFTER note_public;

ALTER TABLE llx_c_country ADD COLUMN favorite tinyint DEFAULT 0 AFTER active;
UPDATE llx_c_country SET favorite = '1' WHERE rowid = '0';

ALTER TABLE llx_c_email_templates DROP INDEX uk_c_email_templates;
ALTER TABLE llx_c_email_templates ADD UNIQUE INDEX uk_c_email_templates(entity, label, lang);
ALTER TABLE llx_c_email_templates ADD INDEX idx_type(type_template);

-- Remove OSC module
DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_MODULE_BOUTIQUE';
DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'OSC_DB_HOST';
DELETE FROM llx_menu WHERE __DECRYPT('module')__ = 'boutique';

-- Add option always editable on extrafield
ALTER TABLE llx_extrafields ADD alwayseditable INTEGER DEFAULT 0 AFTER pos;

-- add supplier webservice fields
ALTER TABLE llx_societe ADD webservices_url varchar(255) DEFAULT NULL;
ALTER TABLE llx_societe ADD webservices_key varchar(128) DEFAULT NULL;

-- changes size of ref in commande_fourn and facture_fourn
ALTER TABLE llx_commande_fournisseur MODIFY COLUMN ref VARCHAR(255);
ALTER TABLE llx_commande_fournisseur MODIFY COLUMN ref_ext VARCHAR(255);
ALTER TABLE llx_commande_fournisseur MODIFY COLUMN ref_supplier VARCHAR(255);

ALTER TABLE llx_facture_fourn MODIFY COLUMN ref VARCHAR(255);
ALTER TABLE llx_facture_fourn MODIFY COLUMN ref_ext VARCHAR(255);
ALTER TABLE llx_facture_fourn MODIFY COLUMN ref_supplier VARCHAR(255);

UPDATE llx_facture_fourn SET ref = rowid WHERE ref IS NULL or ref = '';

ALTER TABLE llx_facture_rec ADD COLUMN revenuestamp double(24,8) DEFAULT 0;
ALTER TABLE llx_facturedet_rec MODIFY COLUMN tva_tx double(6,3);
ALTER TABLE llx_facturedet_rec ADD COLUMN fk_contract_line integer NULL;

ALTER TABLE llx_resource MODIFY COLUMN entity integer DEFAULT 1 NOT NULL;

-- This request make mysql drop (mysql bug, so we add it at end):
ALTER TABLE llx_product ADD CONSTRAINT fk_product_barcode_type FOREIGN KEY (fk_barcode_type) REFERENCES llx_c_barcode_type(rowid);

-- this update change the old formated url on llx_bank_url
UPDATE llx_bank_url set url = REPLACE( url, 'fiche.php', 'card.php');

-- Add id commandefourndet in llx_commande_fournisseur_dispatch to correct /fourn/commande/dispatch.php display when several times same product in supplier order
ALTER TABLE llx_commande_fournisseur_dispatch ADD COLUMN fk_commandefourndet INTEGER NOT NULL DEFAULT 0 AFTER fk_product;


-- Not into official 3.7 but must be into migration for 3.7 when migration is done by 3.8 code 
ALTER TABLE llx_extrafields ADD COLUMN perms varchar(255) after fieldrequired;
ALTER TABLE llx_extrafields ADD COLUMN list integer DEFAULT 0 after perms;

-- IVORY COST (id country=21)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,localtax1,localtax1_type,localtax2,localtax2_type,note,active) values (211, 21,  '0','0',0,0,0,0,'IVA Rate 0',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,localtax1,localtax1_type,localtax2,localtax2_type,note,active) values (212, 21,  '18','0',7.5,2,0,0,'IVA standard rate',1);

ALTER TABLE llx_livraison MODIFY COLUMN date_delivery DATETIME NULL DEFAULT NULL;

-- This constant is for compatibility if user come from 3.6 or lower. Must not be enabled on 3.7.0 or +
INSERT INTO llx_const (name, value, type, note, visible, entity) SELECT __ENCRYPT('PRODUCT_USE_OLD_PATH_FOR_PHOTO')__,__ENCRYPT('1')__,'chaine','Use old path for products images',1,0 FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_VERSION_LAST_INSTALL' AND __DECRYPT('value')__ < '3.7.0'; 
