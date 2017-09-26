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


-- Missing in 5.0
ALTER TABLE llx_user MODIFY login varchar(50) NOT NULL;

-- Missing in 6.0 ?
ALTER TABLE llx_product_price ADD COLUMN fk_multicurrency integer;
ALTER TABLE llx_product_price ADD COLUMN multicurrency_code	varchar(255);
ALTER TABLE llx_product_price ADD COLUMN multicurrency_tx double(24,8) DEFAULT 1;
ALTER TABLE llx_product_price ADD COLUMN multicurrency_price double(24,8) DEFAULT NULL;
ALTER TABLE llx_product_price ADD COLUMN multicurrency_price_ttc double(24,8) DEFAULT NULL;

ALTER TABLE llx_website_page ADD COLUMN fk_user_create integer;
ALTER TABLE llx_website_page ADD COLUMN fk_user_modif integer; 


-- For 7.0

ALTER TABLE llx_product_fournisseur_price ADD COLUMN localtax1_tx double(6,3) DEFAULT 0;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN localtax1_type varchar(10)  NOT NULL DEFAULT '0';
ALTER TABLE llx_product_fournisseur_price ADD COLUMN localtax2_tx double(6,3) DEFAULT 0;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN localtax2_type varchar(10)  NOT NULL DEFAULT '0';


insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SENTBYMAIL','Mails sent from member card','Executed when you send email from member card','member',23);

ALTER TABLE llx_ecm_files MODIFY label varchar(128) NOT NULL;
ALTER TABLE llx_ecm_files ADD COLUMN share varchar(128) NULL after label;


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

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,active,topic,content,content_lines) VALUES (0,null,'member',null,0,null,null,'(SendAnEMailToMember)',1,1,'__(CardContent)__','__(ThisIsContentOfYourCard)__<br />\n__(ID)__ : __ID__<br />\n__(Civiliyty)__ : __MEMBER_CIVILITY__<br />\n__(Firstname)__ : __MEMBER_FIRSTNAME__<br />\n__(Lastname)__ : __MEMBER_LASTNAME__<br />\n__(Fullname)__ : __MEMBER_FULLNAME__<br />\n__(Company)__ : __MEMBER_COMPANY__<br />\n__(Address)__ : __MEMBER_ADDRESS__<br />\n__(Zip)__ : __MEMBER_ZIP__<br />\n__(Town)__ : __MEMBER_TOWN__<br />\n__(Country)__ : __MEMBER_COUNTRY__<br />\n__(Email)__ : __MEMBER_EMAIL__<br />\n__(Birthday)__ : __MEMBER_BIRTH__<br />\n__(Photo)__ : __MEMBER_PHOTO__<br />\n__(Login)__ : __MEMBER_LOGIN__<br />\n__(Password)__ : __MEMBER_PASSWORD__<br />\n__(Phone)__ : __MEMBER_PHONE__<br />\n__(PhonePerso)__ : __MEMBER_PHONEPRO__<br />\n__(PhoneMobile)__ : __MEMBER_PHONEMOBILE__',null);

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

ALTER TABLE llx_accounting_bookkeeping ADD COLUMN date_lim_reglement datetime;
ALTER TABLE llx_accounting_bookkeeping ADD COLUMN fk_user integer NULL;

CREATE TABLE IF NOT EXISTS llx_expensereport_ik (
    rowid           integer  AUTO_INCREMENT PRIMARY KEY,
    datec           datetime  DEFAULT NULL,
    tms             timestamp,
    fk_c_exp_tax_cat integer DEFAULT 0 NOT NULL,
    fk_range        integer DEFAULT 0 NOT NULL,
    coef            double DEFAULT 0 NOT NULL,
    offset          double DEFAULT 0 NOT NULL
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

INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (1,4, 1, 0.41, 0);
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (2,4, 2, 0.244, 824);
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (3,4, 3, 0.286, 0);
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (4,5, 4, 0.493, 0);
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (5,5, 5, 0.277, 1082);
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (6,5, 6, 0.332, 0); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (7,6, 7, 0.543, 0); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (8,6, 8, 0.305, 1180); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (9,6, 9, 0.364, 0); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (10,7, 10, 0.568, 0); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (11,7, 11, 0.32, 1244); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (12,7, 12, 0.382, 0); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (13,8, 13, 0.595, 0); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (14,8, 14, 0.337, 1288); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, offset) values (15,8, 15, 0.401, 0); 


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
);

ALTER TABLE llx_expensereport_det ADD COLUMN rule_warning_message text;
ALTER TABLE llx_expensereport_det ADD COLUMN fk_c_exp_tax_cat integer;

ALTER TABLE llx_user ADD COLUMN default_range integer;
ALTER TABLE llx_user ADD COLUMN default_c_exp_tax_cat integer;

ALTER TABLE llx_extrafields ADD COLUMN fk_user_author integer;
ALTER TABLE llx_extrafields ADD COLUMN fk_user_modif integer;
ALTER TABLE llx_extrafields ADD COLUMN datec datetime;
ALTER TABLE llx_extrafields ADD COLUMN tms timestamp;

ALTER TABLE llx_extrafields MODIFY COLUMN langs varchar(64);

ALTER TABLE llx_holiday_config MODIFY COLUMN name varchar(128);
ALTER TABLE llx_holiday_config ADD UNIQUE INDEX idx_holiday_config (name);

ALTER TABLE llx_actioncomm MODIFY COLUMN label varchar(255) NOT NULL;

ALTER TABLE llx_payment_various ADD COLUMN fk_projet integer DEFAULT NULL after accountancy_code;

UPDATE llx_const set name = 'ONLINE_PAYMENT_MESSAGE_OK'  where name = 'PAYPAL_MESSAGE_OK';
UPDATE llx_const set name = 'ONLINE_PAYMENT_MESSAGE_KO'  where name = 'PAYPAL_MESSAGE_KO';
UPDATE llx_const set name = 'ONLINE_PAYMENT_CREDITOR'    where name = 'PAYPAL_CREDITOR';
UPDATE llx_const set name = 'ONLINE_PAYMENT_CSS_URL'     where name = 'PAYPAL_CSS_URL';
UPDATE llx_const set name = 'ONLINE_PAYMENT_NEWFORMTEXT' where name = 'PAYPAL_NEWFORMTEXT';
UPDATE llx_const set name = 'ONLINE_PAYMENT_LOGO'        where name = 'PAYPAL_LOGO';

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


CREATE TABLE llx_projet_task_comment (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    datec datetime  DEFAULT NULL,
    tms timestamp,
    description text NOT NULL,
    fk_user integer DEFAULT NULL,
    fk_task integer DEFAULT NULL,
    entity integer DEFAULT 1,
    import_key varchar(125) DEFAULT NULL
)ENGINE=innodb;

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

-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_account MODIFY account_number VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_account MODIFY account_number VARCHAR(20) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_bookkeeping MODIFY numero_compte VARCHAR(20) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_accounting_bookkeeping MODIFY numero_compte VARCHAR(20) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_stock_mouvement MODIFY batch VARCHAR(30) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_stock_mouvement MODIFY batch VARCHAR(30) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product_lot MODIFY batch VARCHAR(30) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product_lot MODIFY batch VARCHAR(30) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product_batch MODIFY batch VARCHAR(30) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product_batch MODIFY batch VARCHAR(30) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_sell VARCHAR(32) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_sell VARCHAR(32) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_buy VARCHAR(32) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_product MODIFY accountancy_code_buy VARCHAR(32) COLLATE utf8_unicode_ci;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_c_type_fees MODIFY accountancy_code VARCHAR(32) CHARACTER SET utf8;
-- VMYSQLUTF8UNICODECI ALTER TABLE llx_c_type_fees MODIFY accountancy_code VARCHAR(32) COLLATE utf8_unicode_ci;
