--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 7.0.0 or higher.
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
-- To make pk to be auto increment (mysql):    -- VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres): -- VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE
-- To set a field as NULL:                     -- VMYSQL4.3 ALTER TABLE llx_table MODIFY COLUMN name varchar(60) NULL;
-- To set a field as NULL:                     -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as NOT NULL:                 -- VMYSQL4.3 ALTER TABLE llx_table MODIFY COLUMN name varchar(60) NOT NULL;
-- To set a field as NOT NULL:                 -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET NOT NULL;
-- To set a field as default NULL:             -- VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- Note: fields with type BLOB/TEXT can't have default value.
-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


-- Must be before the utf8 pagecode fix
ALTER TABLE llx_product ADD COLUMN accountancy_code_sell_intra varchar(32) AFTER accountancy_code_sell;
ALTER TABLE llx_product ADD COLUMN accountancy_code_sell_export varchar(32) AFTER accountancy_code_sell_intra;


-- Drop old key with old name
ALTER TABLE llx_accounting_account DROP FOREIGN KEY fk_accountingaccount_fk_pcg_version;

-- Drop foreign key, so next alter will be a success
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_account DROP FOREIGN KEY fk_accounting_account_fk_pcg_version;

-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_account MODIFY fk_pcg_version VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_account MODIFY fk_pcg_version VARCHAR(20) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_system MODIFY pcg_version VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_system MODIFY pcg_version VARCHAR(20) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_account MODIFY account_number VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_account MODIFY account_number VARCHAR(20) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_bookkeeping MODIFY numero_compte VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_bookkeeping MODIFY numero_compte VARCHAR(20) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_journal MODIFY code VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_journal MODIFY code VARCHAR(20) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_bank_account MODIFY accountancy_journal VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_bank_account MODIFY accountancy_journal VARCHAR(20) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_stock_mouvement MODIFY batch VARCHAR(30) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_stock_mouvement MODIFY batch VARCHAR(30) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product_lot MODIFY batch VARCHAR(30) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product_lot MODIFY batch VARCHAR(30) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_expeditiondet_batch MODIFY batch VARCHAR(30) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_expeditiondet_batch MODIFY batch VARCHAR(30) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product_batch MODIFY batch VARCHAR(30) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product_batch MODIFY batch VARCHAR(30) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_sell VARCHAR(32) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_sell VARCHAR(32) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_sell_intra VARCHAR(32) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_sell_intra VARCHAR(32) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_sell_export VARCHAR(32) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_sell_export VARCHAR(32) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_buy VARCHAR(32) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_buy VARCHAR(32) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_c_type_fees MODIFY accountancy_code VARCHAR(32) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_c_type_fees MODIFY accountancy_code VARCHAR(32) COLLATE utf8_unicode_ci;

-- Restore dropped foreign key
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_account ADD CONSTRAINT fk_accounting_account_fk_pcg_version FOREIGN KEY (fk_pcg_version) REFERENCES llx_accounting_system (pcg_version);



-- Missing in 5.0
ALTER TABLE llx_user MODIFY login varchar(50) NOT NULL;

-- Missing in 6.0 ?
ALTER TABLE llx_product_price ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_product_price ADD COLUMN multicurrency_code	varchar(255);
ALTER TABLE llx_product_price ADD COLUMN multicurrency_tx double(24,8) DEFAULT 1;
ALTER TABLE llx_product_price ADD COLUMN multicurrency_price double(24,8) DEFAULT NULL;
ALTER TABLE llx_product_price ADD COLUMN multicurrency_price_ttc double(24,8) DEFAULT NULL;

ALTER TABLE llx_product_customer_price_log ADD COLUMN default_vat_code varchar(10);
ALTER TABLE llx_product_price ADD COLUMN default_vat_code	varchar(10) AFTER tva_tx;
ALTER TABLE llx_product_customer_price ADD COLUMN default_vat_code	varchar(10) AFTER tva_tx;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN default_vat_code	varchar(10) AFTER tva_tx;

ALTER TABLE llx_website_page ADD COLUMN fk_user_create integer;
ALTER TABLE llx_website_page ADD COLUMN fk_user_modif integer; 
ALTER TABLE llx_website_page ADD COLUMN type_container varchar(16) NOT NULL DEFAULT 'page';


-- For 7.0

ALTER TABLE llx_product_attribute_value DROP INDEX unique_ref;
ALTER TABLE llx_product_attribute_value ADD UNIQUE INDEX uk_product_attribute_value (fk_product_attribute, ref);


ALTER TABLE llx_product_price_by_qty ADD COLUMN quantity double DEFAULT NULL;
ALTER TABLE llx_product_price_by_qty ADD COLUMN unitprice double(24,8) DEFAULT 0;

ALTER TABLE llx_product_price_by_qty ADD COLUMN price_base_type	varchar(3) DEFAULT 'HT';
ALTER TABLE llx_product_price_by_qty ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_product_price_by_qty ADD COLUMN multicurrency_code varchar(255);
ALTER TABLE llx_product_price_by_qty ADD COLUMN multicurrency_tx double(24,8) DEFAULT 1;
ALTER TABLE llx_product_price_by_qty ADD COLUMN multicurrency_price	double(24,8) DEFAULT NULL;
ALTER TABLE llx_product_price_by_qty ADD COLUMN multicurrency_price_ttc	double(24,8) DEFAULT NULL;

-- VMYSQL4.0 DROP INDEX uk_product_price_by_qty_level on llx_product_price_by_qty;
-- VPGSQL8.0 DROP INDEX uk_product_price_by_qty_level;

ALTER TABLE llx_product_price_by_qty ADD UNIQUE INDEX uk_product_price_by_qty_level (fk_product_price, quantity);

  
ALTER TABLE llx_accounting_bookkeeping ADD INDEX idx_accounting_bookkeeping_fk_doc (fk_doc);

ALTER TABLE llx_c_revenuestamp ADD COLUMN revenuestamp_type  varchar(16) DEFAULT 'fixed' NOT NULL;

UPDATE llx_contrat SET ref = rowid WHERE ref IS NULL OR ref = '';
ALTER TABLE llx_contratdet ADD COLUMN vat_src_code varchar(10) DEFAULT '';

INSERT INTO llx_c_type_contact(rowid, element, source, code, libelle, active ) values (42, 'propal',  'external', 'SHIPPING', 'Customer contact for delivery', 1);

ALTER TABLE llx_inventory ADD date_validation datetime DEFAULT NULL;
ALTER TABLE llx_inventory CHANGE COLUMN datec date_creation datetime DEFAULT NULL;
ALTER TABLE llx_inventory CHANGE COLUMN fk_user_author fk_user_creat integer;
ALTER TABLE llx_inventory ADD UNIQUE INDEX uk_inventory_ref (ref, entity);

ALTER table llx_entrepot CHANGE COLUMN label ref varchar(255);

UPDATE llx_paiementfourn SET ref = rowid WHERE ref IS NULL;
UPDATE llx_paiementfourn SET entity = 1 WHERE entity IS NULL;

UPDATE llx_website SET entity = 1 WHERE entity IS NULL;
-- VMYSQL4.3 ALTER TABLE llx_website MODIFY COLUMN entity integer NOT NULL DEFAULT 1;
-- VPGSQL8.2 ALTER TABLE llx_website ALTER COLUMN entity SET NOT NULL;

ALTER TABLE llx_user ADD COLUMN birth date;

-- VMYSQL4.1 ALTER TABLE llx_holiday_users DROP PRIMARY KEY;

ALTER TABLE llx_holiday_users ADD UNIQUE INDEX uk_holiday_users(fk_user, fk_type, nb_holiday);

ALTER TABLE llx_product_fournisseur_price ADD COLUMN localtax1_tx double(6,3) DEFAULT 0;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN localtax1_type varchar(10)  NOT NULL DEFAULT '0';
ALTER TABLE llx_product_fournisseur_price ADD COLUMN localtax2_tx double(6,3) DEFAULT 0;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN localtax2_type varchar(10)  NOT NULL DEFAULT '0';


insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SENTBYMAIL','Mails sent from member card','Executed when you send email from member card','member',23);

ALTER TABLE llx_ecm_files MODIFY label varchar(128) NOT NULL;
ALTER TABLE llx_ecm_files ADD COLUMN share varchar(128) NULL after label;
ALTER TABLE llx_ecm_files ADD COLUMN src_object_type varchar(32);
ALTER TABLE llx_ecm_files ADD COLUMN src_object_id integer;


ALTER TABLE llx_propal ADD COLUMN last_main_doc varchar(255);
ALTER TABLE llx_commande ADD COLUMN last_main_doc varchar(255);
ALTER TABLE llx_facture ADD COLUMN last_main_doc varchar(255);
ALTER TABLE llx_contrat ADD COLUMN last_main_doc varchar(255);
ALTER TABLE llx_expedition ADD COLUMN last_main_doc varchar(255);
ALTER TABLE llx_fichinter ADD COLUMN last_main_doc varchar(255);
ALTER TABLE llx_livraison ADD COLUMN last_main_doc varchar(255);
ALTER TABLE llx_supplier_proposal ADD COLUMN last_main_doc varchar(255);
ALTER TABLE llx_facture_fourn ADD COLUMN last_main_doc varchar(255);
ALTER TABLE llx_commande_fournisseur ADD COLUMN last_main_doc varchar(255);


ALTER TABLE llx_c_paiement        ADD COLUMN position        integer NOT NULL DEFAULT 0;
ALTER TABLE llx_c_payment_term    ADD COLUMN position        integer NOT NULL DEFAULT 0;

ALTER TABLE llx_product MODIFY COLUMN seuil_stock_alerte integer DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_product ALTER COLUMN seuil_stock_alerte SET DEFAULT NULL;

ALTER TABLE llx_facture_rec ADD COLUMN suspended integer DEFAULT 0;

ALTER TABLE llx_facture_rec MODIFY COLUMN titre VARCHAR(100);

ALTER TABLE llx_contrat MODIFY COLUMN ref varchar(50);
ALTER TABLE llx_contrat MODIFY COLUMN ref_customer varchar(50);
ALTER TABLE llx_contrat MODIFY COLUMN ref_supplier varchar(50);
ALTER TABLE llx_contrat MODIFY COLUMN ref_ext varchar(50);


UPDATE llx_c_email_templates SET position = 0 WHERE position IS NULL;
UPDATE llx_c_email_templates SET lang = '' WHERE lang IS NULL;

ALTER TABLE llx_c_email_templates ADD COLUMN enabled varchar(255) DEFAULT '1';
ALTER TABLE llx_c_email_templates ADD COLUMN joinfiles varchar(255) DEFAULT '1';
ALTER TABLE llx_c_email_templates MODIFY COLUMN content mediumtext;

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines) VALUES (0,'adherent','member','',0,null,null,'(SendAnEMailToMember)',1,1,1,'__(CardContent)__','__(Hello)__,<br><br>\n\n__(ThisIsContentOfYourCard)__<br>\n__(ID)__ : __ID__<br>\n__(Civiliyty)__ : __MEMBER_CIVILITY__<br>\n__(Firstname)__ : __MEMBER_FIRSTNAME__<br>\n__(Lastname)__ : __MEMBER_LASTNAME__<br>\n__(Fullname)__ : __MEMBER_FULLNAME__<br>\n__(Company)__ : __MEMBER_COMPANY__<br>\n__(Address)__ : __MEMBER_ADDRESS__<br>\n__(Zip)__ : __MEMBER_ZIP__<br>\n__(Town)__ : __MEMBER_TOWN__<br>\n__(Country)__ : __MEMBER_COUNTRY__<br>\n__(Email)__ : __MEMBER_EMAIL__<br>\n__(Birthday)__ : __MEMBER_BIRTH__<br>\n__(Photo)__ : __MEMBER_PHOTO__<br>\n__(Login)__ : __MEMBER_LOGIN__<br>\n__(Password)__ : __MEMBER_PASSWORD__<br>\n__(Phone)__ : __MEMBER_PHONE__<br>\n__(PhonePerso)__ : __MEMBER_PHONEPRO__<br>\n__(PhoneMobile)__ : __MEMBER_PHONEMOBILE__<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null);
INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines) VALUES (0,'banque','thirdparty','',0,null,null,'(YourSEPAMandate)',1,1,0,'__(YourSEPAMandate)__','__(Hello)__,<br><br>\n\n__(FindYourSEPAMandate)__ :<br>\n__MYCOMPANY_NAME__<br>\n__MYCOMPANY_FULLADDRESS__<br><br>\n__(Sincerely)__<br>\n__USER_SIGNATURE__',null);

INSERT INTO llx_c_accounting_category (rowid, code, label, range_account, sens, category_type, formula, position, fk_country, active) VALUES (  1, 'VENTES',    'Income of products/services',               'Exemple: 7xxxxx', 0, 0, '',                '10', 1, 1);
INSERT INTO llx_c_accounting_category (rowid, code, label, range_account, sens, category_type, formula, position, fk_country, active) VALUES (  2, 'DEPENSES',  'Expenses of products/services',             'Exemple: 6xxxxx', 0, 0, '',                '20', 1, 1);
INSERT INTO llx_c_accounting_category (rowid, code, label, range_account, sens, category_type, formula, position, fk_country, active) VALUES (  3, 'PROFIT',    'Balance',                                   '',                0, 1, 'VENTES+DEPENSES', '30', 1, 1);

UPDATE llx_c_accounting_category set code = 'VENTES',   range_account='7xxxxx' where code = 'VTE';
UPDATE llx_c_accounting_category set code = 'DEPENSES', range_account='6xxxxx' where code = 'MAR';
UPDATE llx_c_accounting_category set code = 'PROFIT',   range_account='Balance', formula = 'VENTES+DEPENSES' where code = 'MARGE';

ALTER TABLE llx_menu MODIFY COLUMN perms text;

ALTER TABLE llx_mailing MODIFY COLUMN titre varchar(128);
ALTER TABLE llx_mailing MODIFY COLUMN sujet varchar(128);

ALTER TABLE llx_mailing MODIFY COLUMN langs varchar(64);

ALTER TABLE llx_facture_fourn ADD COLUMN date_pointoftax	date DEFAULT NULL;
ALTER TABLE llx_facture_fourn ADD COLUMN date_valid		date;

ALTER TABLE llx_bookmark DROP COLUMN fk_soc;
 
ALTER TABLE llx_website MODIFY COLUMN ref varchar(128);

ALTER TABLE llx_website_page MODIFY COLUMN pageurl varchar(255);
ALTER TABLE llx_website_page ADD COLUMN lang varchar(6);
ALTER TABLE llx_website_page ADD COLUMN fk_page integer;
ALTER TABLE llx_website_page ADD COLUMN grabbed_from varchar(255);
ALTER TABLE llx_website_page ADD COLUMN htmlheader mediumtext;
ALTER TABLE llx_website_page MODIFY COLUMN htmlheader mediumtext;

ALTER TABLE llx_website_page MODIFY COLUMN status INTEGER DEFAULT 1;
UPDATE llx_website_page set status = 1 WHERE status IS NULL;

ALTER TABLE llx_website ADD COLUMN import_key varchar(14);
ALTER TABLE llx_website_page ADD COLUMN import_key varchar(14);
ALTER TABLE llx_fichinter ADD COLUMN import_key varchar(14);
ALTER TABLE llx_livraison ADD COLUMN import_key varchar(14);
ALTER TABLE llx_livraison ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_don ADD COLUMN extraparams varchar(255);

ALTER TABLE llx_accounting_account ADD COLUMN import_key varchar(14);
ALTER TABLE llx_accounting_account ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN import_key varchar(14);
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_accounting_bookkeeping_tmp ADD COLUMN extraparams varchar(255);

ALTER TABLE llx_accounting_bookkeeping ADD COLUMN date_lim_reglement datetime DEFAULT NULL;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN fk_user integer NULL;
ALTER TABLE llx_accounting_bookkeeping_tmp ADD COLUMN date_lim_reglement datetime DEFAULT NULL;
ALTER TABLE llx_accounting_bookkeeping_tmp ADD COLUMN fk_user integer NULL;


ALTER TABLE llx_menu MODIFY fk_mainmenu varchar(100);
ALTER TABLE llx_menu MODIFY fk_leftmenu varchar(100); 


CREATE TABLE llx_website_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_website_extrafields ADD INDEX idx_website_extrafields (fk_object);


CREATE TABLE llx_website_account(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	login             varchar(64) NOT NULL, 
	pass_encoding     varchar(24) NOT NULL,
    pass_crypted      varchar(128),
    pass_temp         varchar(128),			    -- temporary password when asked for forget password
    fk_soc integer,
	fk_website          integer NOT NULL,
	note_private        text,
    date_last_login     datetime,
    date_previous_login datetime,
	date_creation       datetime NOT NULL, 
	tms                 timestamp NOT NULL, 
	fk_user_creat       integer NOT NULL, 
	fk_user_modif       integer, 
	import_key          varchar(14), 
	status integer 
) ENGINE=innodb;


ALTER TABLE llx_website_account ADD INDEX idx_website_account_rowid (rowid);
ALTER TABLE llx_website_account ADD INDEX idx_website_account_login (login);
ALTER TABLE llx_website_account ADD INDEX idx_website_account_import_key (import_key);
ALTER TABLE llx_website_account ADD INDEX idx_website_account_status (status);
ALTER TABLE llx_website_account ADD INDEX idx_website_account_fk_soc (fk_soc);

ALTER TABLE llx_website_account ADD UNIQUE INDEX uk_website_account_login_website_soc(login, fk_website, fk_soc);

ALTER TABLE llx_website_account ADD CONSTRAINT llx_website_account_fk_website FOREIGN KEY (fk_website) REFERENCES llx_website(rowid);

CREATE TABLE llx_website_account_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_website_account_extrafields ADD INDEX idx_website_account_extrafields (fk_object);





alter table llx_user add column pass_encoding varchar(24) NULL;




CREATE TABLE IF NOT EXISTS llx_expensereport_ik (
    rowid           integer  AUTO_INCREMENT PRIMARY KEY,
    datec           datetime  DEFAULT NULL,
    tms             timestamp,
    fk_c_exp_tax_cat integer DEFAULT 0 NOT NULL,
    fk_range        integer DEFAULT 0 NOT NULL,
    coef            double DEFAULT 0 NOT NULL,
    ikoffset          double DEFAULT 0 NOT NULL,
    active          integer DEFAULT 1
)ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_c_exp_tax_cat (
    rowid       integer  AUTO_INCREMENT PRIMARY KEY,
    label       varchar(48) NOT NULL,
    entity      integer DEFAULT 1 NOT NULL,
    active      integer DEFAULT 1 NOT NULL
)ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_c_exp_tax_range (
    rowid       integer  AUTO_INCREMENT PRIMARY KEY,
    fk_c_exp_tax_cat integer DEFAULT 1 NOT NULL,
    range_ik    double DEFAULT 0 NOT NULL,
    entity      integer DEFAULT 1 NOT NULL,
    active      integer DEFAULT 1 NOT NULL
)ENGINE=innodb;

INSERT INTO llx_c_type_fees (code, label, active, accountancy_code) VALUES
('EX_KME', 'ExpLabelKm', 1, '625100'),
('EX_FUE', 'ExpLabelFuelCV', 0, '606150'),
('EX_HOT', 'ExpLabelHotel', 0, '625160'),
('EX_PAR', 'ExpLabelParkingCV', 0, '625160'),
('EX_TOL', 'ExpLabelTollCV', 0, '625160'),
('EX_TAX', 'ExpLabelVariousTaxes', 0, '637800'),
('EX_IND', 'ExpLabelIndemnityTranspSub', 0, '648100'),
('EX_SUM', 'ExpLabelMaintenanceSupply', 0, '606300'),
('EX_SUO', 'ExpLabelOfficeSupplies', 0, '606400'),
('EX_CAR', 'ExpLabelCarRental', 0, '613000'),
('EX_DOC', 'ExpLabelDocumentation', 0, '618100'),
('EX_CUR', 'ExpLabelCustomersReceiving', 0, '625710'),
('EX_OTR', 'ExpLabelOtherReceiving', 0, '625700'),
('EX_POS', 'ExpLabelPostage', 0, '626100'),
('EX_CAM', 'ExpLabelMaintenanceRepairCV', 0, '615300'),
('EX_EMM', 'ExpLabelEmployeesMeal', 0, '625160'),
('EX_GUM', 'ExpLabelGuestsMeal', 0, '625160'),
('EX_BRE', 'ExpLabelBreakfast', 0, '625160'),
('EX_FUE_VP', 'ExpLabelFuelPV', 0, '606150'),
('EX_TOL_VP', 'ExpLabelTollPV', 0, '625160'),
('EX_PAR_VP', 'ExpLabelParkingPV', 0, '625160'),
('EX_CAM_VP', 'ExpLabelMaintenanceRepairPV', 0, '615300');

INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (1,4, 1, 0.41, 0);
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (2,4, 2, 0.244, 824);
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (3,4, 3, 0.286, 0);
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (4,5, 4, 0.493, 0);
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (5,5, 5, 0.277, 1082);
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (6,5, 6, 0.332, 0); 
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (7,6, 7, 0.543, 0); 
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (8,6, 8, 0.305, 1180); 
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (9,6, 9, 0.364, 0); 
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (10,7, 10, 0.568, 0); 
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (11,7, 11, 0.32, 1244); 
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (12,7, 12, 0.382, 0); 
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (13,8, 13, 0.595, 0); 
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (14,8, 14, 0.337, 1288); 
INSERT INTO llx_expensereport_ik (rowid, fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (15,8, 15, 0.401, 0); 


INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (1,'ExpAutoCat', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (2,'ExpCycloCat', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (3,'ExpMotoCat', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (4,'ExpAuto3CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (5,'ExpAuto4CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (6,'ExpAuto5CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (7,'ExpAuto6CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (8,'ExpAuto7CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (9,'ExpAuto8CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (10,'ExpAuto9CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (11,'ExpAuto10CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (12,'ExpAuto11CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (13,'ExpAuto12CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (14,'ExpAuto3PCV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (15,'ExpAuto4PCV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (16,'ExpAuto5PCV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (17,'ExpAuto6PCV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (18,'ExpAuto7PCV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (19,'ExpAuto8PCV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (20,'ExpAuto9PCV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (21,'ExpAuto10PCV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (22,'ExpAuto11PCV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (23,'ExpAuto12PCV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (24,'ExpAuto13PCV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (25,'ExpCyclo', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (26,'ExpMoto12CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (27,'ExpMoto345CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (28,'ExpMoto5PCV', 1, 1);


INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (1,4, 0, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (2,4, 5000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (3,4, 20000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (4,5, 0, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (5,5, 5000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (6,5, 20000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (7,6, 0, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (8,6, 5000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (9,6, 20000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (10,7, 0, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (11,7, 5000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (12,7, 20000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (13,8, 0, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (14,8, 5000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (15,8, 20000, 1, 1);

CREATE TABLE llx_expensereport_rules (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    datec datetime  DEFAULT NULL,
    tms timestamp,
    dates datetime NOT NULL,
    datee datetime NOT NULL,
    amount numeric(24,8) NOT NULL,
    restrictive tinyint NOT NULL,
    fk_user integer DEFAULT NULL,
    fk_usergroup integer DEFAULT NULL,
    fk_c_type_fees integer NOT NULL,
    code_expense_rules_type varchar(50) NOT NULL,
    is_for_all tinyint DEFAULT '0',
    entity integer DEFAULT 1
)ENGINE=innodb;

ALTER TABLE llx_expensereport_det ADD COLUMN rule_warning_message text;
ALTER TABLE llx_expensereport_det ADD COLUMN fk_c_exp_tax_cat integer;

ALTER TABLE llx_user ADD COLUMN default_range integer;
ALTER TABLE llx_user ADD COLUMN default_c_exp_tax_cat integer;

ALTER TABLE llx_extrafields ADD COLUMN fk_user_author integer;
ALTER TABLE llx_extrafields ADD COLUMN fk_user_modif integer;
ALTER TABLE llx_extrafields ADD COLUMN datec datetime;
ALTER TABLE llx_extrafields ADD COLUMN enabled varchar(255) DEFAULT '1';
ALTER TABLE llx_extrafields ADD COLUMN tms timestamp;

-- We fix value of 'list' from 0 to 1 for all extrafields created before this migration
UPDATE llx_extrafields SET list = 1 WHERE list = 0 AND fk_user_author IS NULL and fk_user_modif IS NULL and datec IS NULL;		
UPDATE llx_extrafields SET list = 3 WHERE type = 'separate' AND list != 3;		

ALTER TABLE llx_extrafields MODIFY COLUMN list integer DEFAULT 1;
--VPGSQL8.2 ALTER TABLE llx_extrafields ALTER COLUMN list SET DEFAULT 1;

ALTER TABLE llx_extrafields MODIFY COLUMN langs varchar(64);

ALTER TABLE llx_holiday_config MODIFY COLUMN name varchar(128);
ALTER TABLE llx_holiday_config ADD UNIQUE INDEX idx_holiday_config (name);

ALTER TABLE llx_societe MODIFY COLUMN ref_ext varchar(255);
ALTER TABLE llx_socpeople MODIFY COLUMN ref_ext varchar(255);
ALTER TABLE llx_actioncomm MODIFY COLUMN ref_ext varchar(255);
ALTER TABLE llx_expedition MODIFY COLUMN ref_ext varchar(255);
ALTER TABLE llx_livraison MODIFY COLUMN ref_ext varchar(255);
ALTER TABLE llx_contrat MODIFY COLUMN ref_ext varchar(255);

ALTER TABLE llx_actioncomm MODIFY COLUMN label varchar(255) NOT NULL;

ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_fk_user_action (fk_user_action);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_fk_project (fk_project);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_datep (datep);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_datep2 (datep2);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_recurid (recurid);

ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_ref_ext (ref_ext);

ALTER TABLE llx_payment_various ADD COLUMN fk_projet integer DEFAULT NULL after accountancy_code;

UPDATE llx_const set name = __ENCRYPT('ONLINE_PAYMENT_MESSAGE_OK')__  where name = __ENCRYPT('PAYPAL_MESSAGE_OK')__;
UPDATE llx_const set name = __ENCRYPT('ONLINE_PAYMENT_MESSAGE_KO')__  where name = __ENCRYPT('PAYPAL_MESSAGE_KO')__;
UPDATE llx_const set name = __ENCRYPT('ONLINE_PAYMENT_CREDITOR')__    where name = __ENCRYPT('PAYPAL_CREDITOR')__;
UPDATE llx_const set name = __ENCRYPT('ONLINE_PAYMENT_CSS_URL')__     where name = __ENCRYPT('PAYPAL_CSS_URL')__;
UPDATE llx_const set name = __ENCRYPT('ONLINE_PAYMENT_NEWFORMTEXT')__ where name = __ENCRYPT('PAYPAL_NEWFORMTEXT')__;
UPDATE llx_const set name = __ENCRYPT('ONLINE_PAYMENT_LOGO')__        where name = __ENCRYPT('PAYPAL_LOGO')__;

ALTER TABLE llx_accounting_system ADD COLUMN fk_country integer;

UPDATE llx_accounting_account SET pcg_type = 'INCOME'  where pcg_type = 'PROD';
UPDATE llx_accounting_account SET pcg_type = 'EXPENSE' where pcg_type = 'CHARGE';
UPDATE llx_accounting_account SET pcg_type = 'INCOME'  where pcg_type = 'VENTAS_E_INGRESOS';
UPDATE llx_accounting_account SET pcg_type = 'EXPENSE' where pcg_type = 'COMPRAS_GASTOS';

ALTER TABLE llx_c_action_trigger MODIFY COLUMN elementtype varchar(24) NOT NULL;

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_SENTBYMAIL','Contract sent by mail','Executed when a contract is sent by mail','contrat',18);

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_VALIDATE','Price request validated','Executed when a commercial proposal is validated','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_SENTBYMAIL','Price request sent by mail','Executed when a commercial proposal is sent by mail','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_CLOSE_SIGNED','Price request closed signed','Executed when a customer proposal is closed signed','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_CLOSE_REFUSED','Price request closed refused','Executed when a customer proposal is closed refused','proposal_supplier',10);

DROP TABLE llx_projet_task_comment;

CREATE TABLE llx_comment (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    datec datetime  DEFAULT NULL,
    tms timestamp,
    description text NOT NULL,
    fk_user_author integer DEFAULT NULL,
    fk_element integer DEFAULT NULL,
    element_type varchar(50) DEFAULT NULL,
    entity integer DEFAULT 1,
    import_key varchar(125) DEFAULT NULL
)ENGINE=innodb;

DELETE FROM llx_const where name = __ENCRYPT('MAIN_SHOW_WORKBOARD')__;

-- Accountancy - Remove old constants
DELETE FROM llx_const WHERE name = __ENCRYPT('ACCOUNTING_SELL_JOURNAL')__;
DELETE FROM llx_const WHERE name = __ENCRYPT('ACCOUNTING_PURCHASE_JOURNAL')__;
DELETE FROM llx_const WHERE name = __ENCRYPT('ACCOUNTING_SOCIAL_JOURNAL')__;
DELETE FROM llx_const WHERE name = __ENCRYPT('ACCOUNTING_MISCELLANEOUS_JOURNAL')__;
DELETE FROM llx_const WHERE name = __ENCRYPT('ACCOUNTING_GROUPBYACCOUNT')__;
DELETE FROM llx_const WHERE name = __ENCRYPT('ACCOUNTING_EXPORT_GLOBAL_ACCOUNT')__;
DELETE FROM llx_const WHERE name = __ENCRYPT('ACCOUNTING_EXPORT_LABEL')__;
DELETE FROM llx_const WHERE name = __ENCRYPT('ACCOUNTING_EXPORT_AMOUNT')__;
DELETE FROM llx_const WHERE name = __ENCRYPT('ACCOUNTING_EXPORT_DEVISE')__;
DELETE FROM llx_const WHERE name = __ENCRYPT('ACCOUNTING_EXPORT_PIECE')__;
DELETE FROM llx_const WHERE name = __ENCRYPT('ACCOUNTING_EXPENSEREPORT_JOURNAL')__;

-- VMYSQL4.1 ALTER TABLE llx_c_paiement DROP PRIMARY KEY;
ALTER TABLE llx_c_paiement ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER id;
ALTER TABLE llx_c_paiement DROP INDEX uk_c_paiement;
ALTER TABLE llx_c_paiement ADD UNIQUE INDEX uk_c_paiement(id, entity, code);

-- VMYSQL4.1 ALTER TABLE llx_c_payment_term DROP PRIMARY KEY;
ALTER TABLE llx_c_payment_term ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_c_payment_term ADD UNIQUE INDEX uk_c_payment_term(rowid, entity, code);

ALTER TABLE llx_projet CHANGE datec datec datetime;

create table llx_c_email_senderprofile
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  entity		  integer DEFAULT 1 NOT NULL,	  -- multi company id
  private         smallint DEFAULT 0 NOT NULL,    -- Template public or private
  date_creation   datetime,
  tms             timestamp,
  label           varchar(255),					  -- Label of predefined email
  email           varchar(255),					  -- Email
  signature		  text,                           -- Predefined signature
  position        smallint,					      -- Position
  active          tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_c_email_senderprofile ADD UNIQUE INDEX uk_c_email_senderprofile(entity, label, email);


-- Add new chart of account entries
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 67,'PC-MIPYME', 'The PYME accountancy Chile plan', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  7,'ENG-BASE',  'England plan', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 49,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 60,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 24,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 65,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 71,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 72,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 21,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 16,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 87,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (147,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (168,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 73,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 22,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 66,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 15,'SYSCOHADA', 'Plan comptable Ouest-Africain', 1);


-- Update old chart of account entries
UPDATE llx_accounting_system SET fk_country =  1 WHERE pcg_version = 'PCG99-ABREGE';
UPDATE llx_accounting_system SET fk_country =  1 WHERE pcg_version = 'PCG99-BASE';
UPDATE llx_accounting_system SET fk_country =  1 WHERE pcg_version = 'PCG14-DEV';
UPDATE llx_accounting_system SET fk_country =  2 WHERE pcg_version = 'PCMN-BASE';
UPDATE llx_accounting_system SET fk_country =  4 WHERE pcg_version = 'PCG08-PYME';
UPDATE llx_accounting_system SET fk_country = 10 WHERE pcg_version = 'PCT';
UPDATE llx_accounting_system SET fk_country = 80 WHERE pcg_version = 'DK-STD';
UPDATE llx_accounting_system SET fk_country = 67 WHERE pcg_version = 'PC-MIPYME';
UPDATE llx_accounting_system SET fk_country =  6 WHERE pcg_version = 'PCG_SUISSE';
UPDATE llx_accounting_system SET fk_country =140 WHERE pcg_version = 'PCN-LUXEMBURG';
UPDATE llx_accounting_system SET fk_country = 12 WHERE pcg_version = 'PCG';


CREATE TABLE llx_actioncomm_reminder(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	dateremind datetime NOT NULL, 
	typeremind varchar(32) NOT NULL, 
	fk_user integer NOT NULL, 
	offsetvalue integer NOT NULL, 
	offsetunit varchar(1) NOT NULL,
	status integer NOT NULL DEFAULT 0
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE llx_actioncomm_reminder ADD INDEX idx_actioncomm_reminder_rowid (rowid);
ALTER TABLE llx_actioncomm_reminder ADD INDEX idx_actioncomm_reminder_dateremind (dateremind);
ALTER TABLE llx_actioncomm_reminder ADD INDEX idx_actioncomm_reminder_fk_user (fk_user);

ALTER TABLE llx_actioncomm_reminder ADD UNIQUE INDEX uk_actioncomm_reminder_unique(fk_user, typeremind, offsetvalue, offsetunit);

UPDATE llx_tva SET datec = tms where datec IS NULL;

-- VPGSQL8.2 CREATE SEQUENCE llx_supplier_proposal_rowid_seq;
-- VPGSQL8.2 ALTER TABLE llx_supplier_proposal ALTER COLUMN rowid SET DEFAULT nextval('llx_supplier_proposal_rowid_seq');
-- VPGSQL8.2 ALTER TABLE llx_supplier_proposal ALTER COLUMN rowid SET NOT NULL;
-- VPGSQL8.2 SELECT setval('llx_supplier_proposal_rowid_seq', (SELECT MAX(rowid) FROM llx_supplier_proposal));

-- VPGSQL8.2 CREATE SEQUENCE llx_supplier_proposaldet_rowid_seq;
-- VPGSQL8.2 ALTER TABLE llx_supplier_proposaldet ALTER COLUMN rowid SET DEFAULT nextval('llx_supplier_proposaldet_rowid_seq');
-- VPGSQL8.2 ALTER TABLE llx_supplier_proposaldet ALTER COLUMN rowid SET NOT NULL;
-- VPGSQL8.2 SELECT setval('llx_supplier_proposaldet_rowid_seq', (SELECT MAX(rowid) FROM llx_supplier_proposaldet));


create table llx_onlinesignature
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  entity                    integer DEFAULT 1 NOT NULL,
  object_type               varchar(32) NOT NULL,
  object_id					integer NOT NULL,
  datec                     datetime NOT NULL,
  tms                       timestamp,
  name						varchar(255) NOT NULL,
  ip						varchar(128),
  pathoffile				varchar(255)
)ENGINE=innodb;



-- May have error due to duplicate keys
ALTER TABLE llx_resource ADD UNIQUE INDEX uk_resource_ref (ref, entity);

ALTER TABLE llx_facture_rec ADD COLUMN modelpdf varchar(255) AFTER note_public;
ALTER TABLE llx_facture_rec ADD COLUMN generate_pdf integer DEFAULT 1 AFTER auto_validate;

ALTER TABLE llx_blockedlog ADD COLUMN date_creation	datetime;
ALTER TABLE llx_blockedlog ADD COLUMN user_fullname	varchar(255);
ALTER TABLE llx_blockedlog MODIFY COLUMN ref_object varchar(255);

-- SPEC : use database type 'double' to store monetary values
ALTER TABLE llx_blockedlog MODIFY COLUMN amounts double(24,8);
ALTER TABLE llx_chargessociales MODIFY COLUMN amount double(24,8);
ALTER TABLE llx_commande MODIFY COLUMN amount_ht double(24,8);
ALTER TABLE llx_commande_fournisseur MODIFY COLUMN amount_ht double(24,8);
ALTER TABLE llx_don MODIFY COLUMN amount double(24,8);
ALTER TABLE llx_expensereport_rules MODIFY COLUMN amount double(24,8);
ALTER TABLE llx_loan MODIFY COLUMN capital double(24,8);
ALTER TABLE llx_loan MODIFY COLUMN capital_position double(24,8);
ALTER TABLE llx_loan_schedule MODIFY COLUMN amount_capital double(24,8);
ALTER TABLE llx_loan_schedule MODIFY COLUMN amount_insurance double(24,8);
ALTER TABLE llx_loan_schedule MODIFY COLUMN amount_interest double(24,8);
ALTER TABLE llx_paiementcharge MODIFY COLUMN amount double(24,8);
ALTER TABLE llx_paiementfourn MODIFY COLUMN amount double(24,8);
ALTER TABLE llx_payment_donation MODIFY COLUMN amount double(24,8);
ALTER TABLE llx_payment_expensereport MODIFY COLUMN amount double(24,8);
ALTER TABLE llx_payment_loan MODIFY COLUMN amount_capital double(24,8);
ALTER TABLE llx_payment_loan MODIFY COLUMN amount_insurance double(24,8);
ALTER TABLE llx_payment_loan MODIFY COLUMN amount_interest double(24,8);
ALTER TABLE llx_payment_salary MODIFY COLUMN salary double(24,8);
ALTER TABLE llx_payment_salary MODIFY COLUMN amount double(24,8);
ALTER TABLE llx_prelevement_bons MODIFY COLUMN amount double(24,8);
ALTER TABLE llx_prelevement_facture_demande MODIFY COLUMN amount double(24,8);
ALTER TABLE llx_prelevement_lignes MODIFY COLUMN amount double(24,8);
ALTER TABLE llx_societe MODIFY COLUMN capital double(24,8);
ALTER TABLE llx_tva MODIFY COLUMN amount double(24,8);
ALTER TABLE llx_subscription MODIFY COLUMN subscription double(24,8);

ALTER TABLE llx_resource ADD fk_country integer DEFAULT NULL;
ALTER TABLE llx_resource ADD INDEX idx_resource_fk_country (fk_country);
ALTER TABLE llx_resource ADD CONSTRAINT fk_resource_fk_country FOREIGN KEY (fk_country) REFERENCES llx_c_country (rowid);

