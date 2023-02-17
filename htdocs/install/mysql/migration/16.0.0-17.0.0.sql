--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 16.0.0 or higher.
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


-- Missing in v16 or lower

ALTER TABLE llx_accounting_system MODIFY COLUMN pcg_version varchar(32) NOT NULL;

ALTER TABLE llx_c_action_trigger MODIFY elementtype VARCHAR(64);

ALTER TABLE llx_c_email_templates ADD COLUMN joinfiles text;
ALTER TABLE llx_c_email_templates ADD COLUMN email_from varchar(255);
ALTER TABLE llx_c_email_templates ADD COLUMN email_to varchar(255);
ALTER TABLE llx_c_email_templates ADD COLUMN email_tocc varchar(255);
ALTER TABLE llx_c_email_templates ADD COLUMN email_tobcc varchar(255);
ALTER TABLE llx_c_email_templates ADD COLUMN content_lines text;
ALTER TABLE llx_c_email_templates ADD COLUMN enabled varchar(255) DEFAULT '1';

ALTER TABLE llx_expedition ADD COLUMN billed smallint    DEFAULT 0;

ALTER TABLE llx_user DROP COLUMN idpers1;
ALTER TABLE llx_user DROP COLUMN idpers2;
ALTER TABLE llx_user DROP COLUMN idpers3;

UPDATE llx_c_actioncomm SET type = 'system' WHERE code = 'AC_OTH';

ALTER TABLE llx_opensurvey_user_studs MODIFY reponses VARCHAR(200) NOT NULL;

-- v17

ALTER TABLE llx_mailing_cibles MODIFY COLUMN source_type varchar(32); 

ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_percent (percent);

UPDATE llx_c_paiement SET code = 'BANCON' WHERE code = 'BAN' AND libelle = 'Bancontact';

-- VMYSQL4.3 ALTER TABLE llx_partnership MODIFY COLUMN fk_user_creat integer NULL;
-- VPGSQL8.2 ALTER TABLE llx_partnership ALTER COLUMN fk_user_creat DROP NOT NULL;

ALTER TABLE llx_partnership ADD COLUMN ip varchar(250);
ALTER TABLE llx_adherent ADD COLUMN ip varchar(250);
ALTER TABLE llx_projet ADD COLUMN ip varchar(250);
ALTER TABLE llx_actioncomm ADD COLUMN ip varchar(250);
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD COLUMN ip varchar(250);
ALTER TABLE llx_opensurvey_user_studs ADD COLUMN ip varchar(250);
ALTER TABLE llx_opensurvey_comments ADD COLUMN ip varchar(250);

ALTER TABLE llx_fichinterdet_rec DROP COLUMN remise;
ALTER TABLE llx_fichinterdet_rec DROP COLUMN fk_export_commpta;

UPDATE llx_const set name = 'ADHERENT_MAILMAN_ADMIN_PASSWORD' WHERE name = 'ADHERENT_MAILMAN_ADMINPW';

ALTER TABLE llx_oauth_token ADD COLUMN state text after tokenstring;

ALTER TABLE llx_adherent ADD COLUMN default_lang VARCHAR(6) DEFAULT NULL AFTER datefin;

ALTER TABLE llx_adherent_type ADD COLUMN caneditamount integer DEFAULT 0 AFTER amount;

ALTER TABLE llx_holiday CHANGE COLUMN date_approve date_approval datetime;

UPDATE llx_holiday SET date_approval = date_valid WHERE statut = 3 AND date_approval IS NULL;
UPDATE llx_holiday SET fk_user_approve = fk_user_valid WHERE statut = 3 AND fk_user_approve IS NULL;

ALTER TABLE llx_inventory ADD COLUMN categories_product VARCHAR(255) DEFAULT NULL AFTER fk_product;

ALTER TABLE llx_societe ADD last_main_doc VARCHAR(255) NULL AFTER model_pdf;

ALTER TABLE llx_emailcollector_emailcollector MODIFY COLUMN lastresult text;
ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN port varchar(10) DEFAULT '993';
ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN acces_type integer DEFAULT 0;
ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN oauth_service varchar(128) DEFAULT NULL;


ALTER TABLE llx_bank ADD COLUMN position integer DEFAULT 0;

ALTER TABLE llx_commande_fournisseur_dispatch ADD INDEX idx_commande_fournisseur_dispatch_fk_product (fk_product);

INSERT INTO llx_const (name, entity, value, type, visible) VALUES ('MAIN_SECURITY_MAX_IMG_IN_HTML_CONTENT', 1, 1000, 'int', 0);

ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN port varchar(10) DEFAULT '993';

ALTER TABLE llx_facture ADD COLUMN close_missing_amount double(24, 8) after close_code;

ALTER TABLE llx_facture_fourn ADD COLUMN close_missing_amount double(24, 8) after close_code;

ALTER TABLE llx_inventory ADD COLUMN categories_product VARCHAR(255) DEFAULT NULL AFTER fk_product;

ALTER TABLE llx_product ADD COLUMN sell_or_eat_by_mandatory tinyint DEFAULT 0 NOT NULL AFTER tobatch;
  -- Make sell-by or eat-by date mandatory

ALTER TABLE llx_recruitment_recruitmentcandidature ADD email_date datetime after email_msgid;

ALTER TABLE llx_ticket ADD COLUMN ip varchar(250);

ALTER TABLE llx_ticket ADD email_date datetime after email_msgid;

ALTER TABLE llx_ticket MODIFY COLUMN message mediumtext;

ALTER TABLE llx_cronjob ADD COLUMN pid integer;

INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-VICTORYDAY',  0, 2, '', 0,  5,  8, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-NATIONALDAY', 0, 2, '', 0,  7, 21, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-ASSOMPTION',  0, 2, '', 0,  8, 15, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-TOUSSAINT',   0, 2, '', 0, 11,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-ARMISTICE',   0, 2, '', 0, 11, 11, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-EASTER',      0, 2, 'eastermonday', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-ASCENSION',   0, 2, 'ascension', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-PENTECOST',   0, 2, 'pentecost', 0, 0, 0, 1);

ALTER TABLE llx_societe_rib ADD COLUMN state_id integer AFTER default_rib;
ALTER TABLE llx_societe_rib ADD COLUMN fk_country integer AFTER state_id;
ALTER TABLE llx_societe_rib ADD COLUMN currency_code varchar(3) AFTER fk_country;

DELETE FROM llx_societe_rib WHERE fk_soc = 0;
ALTER TABLE llx_societe_rib ADD CONSTRAINT llx_societe_rib_fk_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);

ALTER TABLE llx_user_rib ADD COLUMN state_id integer AFTER owner_address;
ALTER TABLE llx_user_rib ADD COLUMN fk_country integer AFTER state_id;
ALTER TABLE llx_user_rib ADD COLUMN currency_code varchar(3) AFTER fk_country;

CREATE TABLE llx_bank_extrafields
(
  rowid      integer AUTO_INCREMENT PRIMARY KEY,
  tms        timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object  integer NOT NULL,
  import_key varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_bank_extrafields ADD INDEX idx_bank_extrafields (fk_object);

ALTER TABLE llx_product_lot ADD COLUMN note_public text DEFAULT NULL after batch;
ALTER TABLE llx_product_lot ADD COLUMN note_private text DEFAULT NULL after note_public;

ALTER TABLE llx_user CHANGE COLUMN note note_private text;

UPDATE llx_c_effectif SET code='EF101-500', libelle='101 - 500' WHERE code='EF100-500';


ALTER TABLE llx_product ADD COLUMN fk_default_workstation integer DEFAULT NULL;
ALTER TABLE llx_bom_bomline ADD COLUMN fk_unit integer DEFAULT NULL;

ALTER TABLE llx_rights_def ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE llx_establishment SET name='' WHERE name IS NULL;
ALTER TABLE llx_establishment CHANGE name label varchar(255) NOT NULL;

ALTER TABLE llx_don ADD UNIQUE INDEX idx_don_uk_ref (ref, entity);

ALTER TABLE llx_don ADD INDEX idx_don_fk_soc (fk_soc);
ALTER TABLE llx_don ADD INDEX idx_don_fk_project (fk_projet);
ALTER TABLE llx_don ADD INDEX idx_don_fk_user_author (fk_user_author);
ALTER TABLE llx_don ADD INDEX idx_don_fk_user_valid (fk_user_valid);

ALTER TABLE llx_commande ADD COLUMN revenuestamp double(24,8) DEFAULT 0 after localtax2;

create table llx_element_categorie
(
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  fk_categorie  integer NOT NULL,
  fk_element  integer NOT NULL,
  import_key    varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_element_categorie ADD UNIQUE INDEX idx_element_categorie_idx (fk_element, fk_categorie);

ALTER TABLE llx_element_categorie ADD CONSTRAINT fk_element_categorie_fk_categorie FOREIGN KEY (fk_categorie) REFERENCES llx_categorie(rowid);

INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) VALUES ('PROJECT_SENTBYMAIL','Project sent by mail','Executed when a project is sent by email','project',144);

ALTER TABLE llx_socpeople ADD INDEX idx_socpeople_lastname (lastname);

ALTER TABLE llx_societe ADD INDEX idx_societe_nom(nom);

ALTER TABLE llx_extrafields MODIFY COLUMN fielddefault text;

ALTER TABLE llx_bank_url ADD INDEX idx_bank_url_url_id (url_id);

ALTER TABLE llx_societe_remise_except ADD COLUMN multicurrency_code varchar(3) NULL;
ALTER TABLE llx_societe_remise_except ADD COLUMN multicurrency_tx double(24,8) NULL;

-- VMYSQL4.3 ALTER TABLE llx_hrm_evaluationdet CHANGE COLUMN `rank` rankorder integer;
-- VPGSQL8.2 ALTER TABLE llx_hrm_evaluationdet CHANGE COLUMN rank rankorder integer;
-- VMYSQL4.3 ALTER TABLE llx_hrm_skillrank CHANGE COLUMN `rank` rankorder integer;
-- VPGSQL8.2 ALTER TABLE llx_hrm_skillrank CHANGE COLUMN rank rankorder integer;


-- Rename const to hide public and private notes (fix allow notes const was used to hide)
UPDATE llx_const SET name = 'MAIN_LIST_HIDE_PUBLIC_NOTES' WHERE name = 'MAIN_LIST_ALLOW_PUBLIC_NOTES';
UPDATE llx_const SET name = 'MAIN_LIST_HIDE_PRIVATE_NOTES' WHERE name = 'MAIN_LIST_ALLOW_PRIVATE_NOTES';


ALTER TABLE llx_projet ADD COLUMN date_start_event datetime;
ALTER TABLE llx_projet ADD COLUMN date_end_event   datetime;
ALTER TABLE llx_projet ADD COLUMN location         varchar(255);


ALTER TABLE llx_c_action_trigger MODIFY COLUMN code varchar(128);

ALTER TABLE llx_overwrite_trans DROP INDEX uk_overwrite_trans;
ALTER TABLE llx_overwrite_trans ADD UNIQUE INDEX uk_overwrite_trans(entity, lang, transkey);

--
-- List of all managed triggered events (used for trigger agenda automatic events and for notification)
--

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_CREATE','Third party created','Executed when a third party is created','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_MODIFY','Third party update','Executed when you update third party','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_SENTBYMAIL','Mails sent from third party card','Executed when you send email from third party card','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_DELETE','Third party deleted','Executed when you delete third party','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_VALIDATE','Customer proposal validated','Executed when a commercial proposal is validated','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_MODIFY','Customer proposal modified','Executed when a customer proposal is modified','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_SENTBYMAIL','Commercial proposal sent by mail','Executed when a commercial proposal is sent by mail','propal',3);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CLOSE_SIGNED','Customer proposal closed signed','Executed when a customer proposal is closed signed','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CLOSE_REFUSED','Customer proposal closed refused','Executed when a customer proposal is closed refused','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CLASSIFY_BILLED','Customer proposal set billed','Executed when a customer proposal is set to billed','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_DELETE','Customer proposal deleted','Executed when a customer proposal is deleted','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_VALIDATE','Customer order validate','Executed when a customer order is validated','commande',4);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_CLOSE','Customer order classify delivered','Executed when a customer order is set delivered','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_MODIFY','Customer order modified','Executed when a customer order is set modified','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_CLASSIFY_BILLED','Customer order classify billed','Executed when a customer order is set to billed','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_CANCEL','Customer order canceled','Executed when a customer order is canceled','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SENTBYMAIL','Customer order sent by mail','Executed when a customer order is sent by mail ','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_DELETE','Customer order deleted','Executed when a customer order is deleted','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_VALIDATE','Customer invoice validated','Executed when a customer invoice is approved','facture',6);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_MODIFY','Customer invoice modified','Executed when a customer invoice is modified','facture',7);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_PAYED','Customer invoice payed','Executed when a customer invoice is payed','facture',7);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_CANCEL','Customer invoice canceled','Executed when a customer invoice is conceled','facture',8);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SENTBYMAIL','Customer invoice sent by mail','Executed when a customer invoice is sent by mail','facture',9);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_UNVALIDATE','Customer invoice unvalidated','Executed when a customer invoice status set back to draft','facture',9);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_DELETE','Customer invoice deleted','Executed when a customer invoice is deleted','facture',9);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_VALIDATE','Price request validated','Executed when a commercial proposal is validated','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_MODIFY','Price request modified','Executed when a commercial proposal is modified','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_SENTBYMAIL','Price request sent by mail','Executed when a commercial proposal is sent by mail','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_CLOSE_SIGNED','Price request closed signed','Executed when a customer proposal is closed signed','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_CLOSE_REFUSED','Price request closed refused','Executed when a customer proposal is closed refused','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_DELETE','Price request deleted','Executed when a customer proposal delete','proposal_supplier',10);
--insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_CREATE','Supplier order created','Executed when a supplier order is created','order_supplier',11);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_VALIDATE','Supplier order validated','Executed when a supplier order is validated','order_supplier',12);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_APPROVE','Supplier order request approved','Executed when a supplier order is approved','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_MODIFY','Supplier order request modified','Executed when a supplier order is modified','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_SUBMIT','Supplier order request submited','Executed when a supplier order is approved','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_RECEIVE','Supplier order request received','Executed when a supplier order is received','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_REFUSE','Supplier order request refused','Executed when a supplier order is refused','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_CANCEL','Supplier order request canceled','Executed when a supplier order is canceled','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_SENTBYMAIL','Supplier order sent by mail','Executed when a supplier order is sent by mail','order_supplier',14);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_CLASSIFY_BILLED','Supplier order set billed','Executed when a supplier order is set as billed','order_supplier',14);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_DELETE','Supplier order deleted','Executed when a supplier order is deleted','order_supplier',14);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_VALIDATE','Supplier invoice validated','Executed when a supplier invoice is validated','invoice_supplier',15);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_MODIFY','Supplier invoice modified','Executed when a supplier invoice is modified','invoice_supplier',15);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_UNVALIDATE','Supplier invoice unvalidated','Executed when a supplier invoice status is set back to draft','invoice_supplier',15);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_PAYED','Supplier invoice payed','Executed when a supplier invoice is payed','invoice_supplier',16);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_SENTBYMAIL','Supplier invoice sent by mail','Executed when a supplier invoice is sent by mail','invoice_supplier',17);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_CANCELED','Supplier invoice cancelled','Executed when a supplier invoice is cancelled','invoice_supplier',17);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_DELETE','Supplier invoice deleted','Executed when a supplier invoice is deleted','invoice_supplier',17);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_VALIDATE','Contract validated','Executed when a contract is validated','contrat',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_MODIFY','Contract modified','Executed when a contract is modified','contrat',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_SENTBYMAIL','Contract sent by mail','Executed when a contract is sent by mail','contrat',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_DELETE','Contract deleted','Executed when a contract is deleted','contrat',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_VALIDATE','Shipping validated','Executed when a shipping is validated','shipping',20);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_MODIFY','Shipping modified','Executed when a shipping is modified','shipping',20);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_SENTBYMAIL','Shipping sent by mail','Executed when a shipping is sent by mail','shipping',21);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_DELETE','Shipping sent is deleted','Executed when a shipping is deleted','shipping',21);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECEPTION_VALIDATE','Reception validated','Executed when a reception is validated','reception',22);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECEPTION_SENTBYMAIL','Reception sent by mail','Executed when a reception is sent by mail','reception',22);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_VALIDATE','Member validated','Executed when a member is validated','member',22);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_MODIFY','Member modified','Executed when a member is modified','member',23);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SENTBYMAIL','Mails sent from member card','Executed when you send email from member card','member',23);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SUBSCRIPTION_CREATE','Member subscribtion recorded','Executed when a member subscribtion is deleted','member',24);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SUBSCRIPTION_MODIFY','Member subscribtion modified','Executed when a member subscribtion is modified','member',24);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SUBSCRIPTION_DELETE','Member subscribtion deleted','Executed when a member subscribtion is deleted','member',24);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_RESILIATE','Member resiliated','Executed when a member is resiliated','member',25);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_DELETE','Member deleted','Executed when a member is deleted','member',26);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_EXCLUDE','Member excluded','Executed when a member is excluded','member',27);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_VALIDATE','Intervention validated','Executed when a intervention is validated','ficheinter',30);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_MODIFY','Intervention modify','Executed when a intervention is modify','ficheinter',30);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_CLASSIFY_BILLED','Intervention set billed','Executed when a intervention is set to billed (when option FICHINTER_CLASSIFY_BILLED is set)','ficheinter',32);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_CLASSIFY_UNBILLED','Intervention set unbilled','Executed when a intervention is set to unbilled (when option FICHINTER_CLASSIFY_BILLED is set)','ficheinter',33);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_REOPEN','Intervention opened','Executed when a intervention is re-opened','ficheinter',34);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_SENTBYMAIL','Intervention sent by mail','Executed when a intervention is sent by mail','ficheinter',35);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_DELETE','Intervention is deleted','Executed when a intervention is deleted','ficheinter',35);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_CREATE','Product or service created','Executed when a product or sevice is created','product',40);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_MODIFY','Product or service modified','Executed when a product or sevice is modified','product',41);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_DELETE','Product or service deleted','Executed when a product or sevice is deleted','product',42);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_CREATE','Expense report created','Executed when an expense report is created','expensereport',201);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_VALIDATE','Expense report validated','Executed when an expense report is validated','expensereport',202);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_MODIFY','Expense report modified','Executed when an expense report is modified','expensereport',202);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_APPROVE','Expense report approved','Executed when an expense report is approved','expensereport',203);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_PAID','Expense report billed','Executed when an expense report is set as billed','expensereport',204);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_DELETE','Expense report deleted','Executed when an expense report is deleted','expensereport',205);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_VALIDATE','Expense report validated','Executed when an expense report is validated','expensereport',211);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_MODIFY','Expense report modified','Executed when an expense report is modified','expensereport',212);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_APPROVE','Expense report approved','Executed when an expense report is approved','expensereport',212);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_CREATE','Project creation','Executed when a project is created','project',140);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_VALIDATE','Project validation','Executed when a project is validated','project',141);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_MODIFY','Project modified','Executed when a project is modified','project',142);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_DELETE','Project deleted','Executed when a project is deleted','project',143);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_SENTBYMAIL','Project sent by mail','Executed when a project is sent by email','project',144);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_CREATE','Ticket created','Executed when a ticket is created','ticket',161);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_MODIFY','Ticket modified','Executed when a ticket is modified','ticket',163);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_ASSIGNED','Ticket assigned','Executed when a ticket is modified','ticket',164);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_CLOSE','Ticket closed','Executed when a ticket is closed','ticket',165);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_SENTBYMAIL','Ticket message sent by email','Executed when a message is sent from the ticket record','ticket',166);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_DELETE','Ticket deleted','Executed when a ticket is deleted','ticket',167);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('USER_SENTBYMAIL','Email sent','Executed when an email is sent from user card','user',300);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('USER_CREATE','User created','Executed when a user is created','user',301);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('USER_MODIFY','User update','Executed when a user is updated','user',302);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('USER_DELETE','User update','Executed when a user is deleted','user',303);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('USER_NEW_PASSWORD','User update','Executed when a user is change password','user',304);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('USER_ENABLEDISABLE','User update','Executed when a user is enable or disable','user',305);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_MODIFY','Intervention modified','Executed when a intervention is modified','ficheinter',19);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_VALIDATE','BOM validated','Executed when a BOM is validated','bom',650);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_UNVALIDATE','BOM unvalidated','Executed when a BOM is unvalidated','bom',651);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_CLOSE','BOM disabled','Executed when a BOM is disabled','bom',652);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_REOPEN','BOM reopen','Executed when a BOM is re-open','bom',653);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_DELETE','BOM deleted','Executed when a BOM deleted','bom',654);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MRP_MO_VALIDATE','MO validated','Executed when a MO is validated','mrp',660);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MRP_MO_PRODUCED','MO produced','Executed when a MO is produced','mrp',661);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MRP_MO_DELETE','MO deleted','Executed when a MO is deleted','mrp',662);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MRP_MO_CANCEL','MO canceled','Executed when a MO is canceled','mrp',663);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_CREATE','Contact address created','Executed when a contact is created','contact',50);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_MODIFY','Contact address update','Executed when a contact is updated','contact',51);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_SENTBYMAIL','Mails sent from third party card','Executed when you send email from contact address record','contact',52);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_DELETE','Contact address deleted','Executed when a contact is deleted','contact',53);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTJOBPOSITION_CREATE','Job created','Executed when a job is created','recruitment',7500);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTJOBPOSITION_MODIFY','Job modified','Executed when a job is modified','recruitment',7502);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTJOBPOSITION_SENTBYMAIL','Mails sent from job record','Executed when you send email from job record','recruitment',7504);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTJOBPOSITION_DELETE','Job deleted','Executed when a job is deleted','recruitment',7506);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTCANDIDATURE_CREATE','Candidature created','Executed when a candidature is created','recruitment',7510);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTCANDIDATURE_MODIFY','Candidature modified','Executed when a candidature is modified','recruitment',7512);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTCANDIDATURE_SENTBYMAIL','Mails sent from candidature record','Executed when you send email from candidature record','recruitment',7514);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTCANDIDATURE_DELETE','Candidature deleted','Executed when a candidature is deleted','recruitment',7516);

-- actions not enabled by default : they are excluded when we enable the module Agenda (except TASK_...)
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TASK_CREATE','Task created','Executed when a project task is created','project',150);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TASK_MODIFY','Task modified','Executed when a project task is modified','project',151);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TASK_DELETE','Task deleted','Executed when a project task is deleted','project',152);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ACTION_CREATE','Action added','Executed when an action is added to the agenda','agenda',700);

-- holiday
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_CREATE','Holiday created','Executed when a holiday is created','holiday',800);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_MODIFY','Holiday modified','Executed when a holiday is modified','holiday',801);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_VALIDATE','Holiday validated','Executed when a holiday is validated','holiday',802);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_APPROVE','Holiday aprouved','Executed when a holiday is aprouved','holiday',803);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_CANCEL','Holiday canceled','Executed when a holiday is canceled','holiday',802);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_DELETE','Holiday deleted','Executed when a holiday is deleted','holiday',804);

-- facture rec
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILLREC_CREATE','Template invoices created','Executed when a Template invoices is created','facturerec',900);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILLREC_MODIFY','Template invoices update','Executed when a Template invoices is updated','facturerec',901);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILLREC_DELETE','Template invoices deleted','Executed when a Template invoices is deleted','facturerec',902);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILLREC_AUTOCREATEBILL','Template invoices use to create invoices with auto batch','Executed when a Template invoices is use to create invoice with auto batch','facturerec',903);


ALTER TABLE llx_prelevement_facture RENAME TO llx_prelevement;
ALTER TABLE llx_prelevement_facture_demande RENAME TO llx_prelevement_demande;

ALTER TABLE llx_prelevement ADD COLUMN fk_salary INTEGER NULL AFTER fk_facture_fourn;
ALTER TABLE llx_prelevement_demande ADD COLUMN fk_salary INTEGER NULL AFTER fk_facture_fourn;

ALTER TABLE llx_user ADD COLUMN birth_place varchar(64);

ALTER TABLE llx_opensurvey_user_studs ADD COLUMN date_creation datetime NULL;
ALTER TABLE llx_opensurvey_comments ADD COLUMN date_creation datetime NULL;

ALTER TABLE llx_c_tva ADD COLUMN use_default tinyint DEFAULT 0;

ALTER TABLE llx_commande_fournisseurdet MODIFY COLUMN ref varchar(128);
ALTER TABLE llx_facture_fourn_det MODIFY COLUMN ref varchar(128);

ALTER TABLE llx_c_country ADD COLUMN alpha2_code CHAR(2) NOT NULL;
ALTER TABLE llx_c_country ADD COLUMN alpha3_code CHAR(3) NOT NULL;
ALTER TABLE llx_c_country ADD COLUMN numeric_code INT NOT NULL;

UPDATE llx_c_country SET alpha2_code = "AF", alpha3_code = "AFG", numeric_code = 4 WHERE code_iso = "AFG";
UPDATE llx_c_country SET alpha2_code = "AX", alpha3_code = "ALA", numeric_code = 248 WHERE code_iso = "ALA";
UPDATE llx_c_country SET alpha2_code = "AL", alpha3_code = "ALB", numeric_code = 8 WHERE code_iso = "ALB";
UPDATE llx_c_country SET alpha2_code = "DE", alpha3_code = "DEU", numeric_code = 276 WHERE code_iso = "DEU";
UPDATE llx_c_country SET alpha2_code = "AD", alpha3_code = "AND", numeric_code = 20 WHERE code_iso = "AND";
UPDATE llx_c_country SET alpha2_code = "AO", alpha3_code = "AGO", numeric_code = 24 WHERE code_iso = "AGO";
UPDATE llx_c_country SET alpha2_code = "AI", alpha3_code = "AIA", numeric_code = 660 WHERE code_iso = "AIA";
UPDATE llx_c_country SET alpha2_code = "AQ", alpha3_code = "ATA", numeric_code = 10 WHERE code_iso = "ATA";
UPDATE llx_c_country SET alpha2_code = "AG", alpha3_code = "ATG", numeric_code = 28 WHERE code_iso = "ATG";
UPDATE llx_c_country SET alpha2_code = "SA", alpha3_code = "SAU", numeric_code = 682 WHERE code_iso = "SAU";
UPDATE llx_c_country SET alpha2_code = "DZ", alpha3_code = "DZA", numeric_code = 12 WHERE code_iso = "DZA";
UPDATE llx_c_country SET alpha2_code = "AR", alpha3_code = "ARG", numeric_code = 32 WHERE code_iso = "ARG";
UPDATE llx_c_country SET alpha2_code = "AM", alpha3_code = "ARM", numeric_code = 51 WHERE code_iso = "ARM";
UPDATE llx_c_country SET alpha2_code = "AW", alpha3_code = "ABW", numeric_code = 533 WHERE code_iso = "ABW";
UPDATE llx_c_country SET alpha2_code = "AU", alpha3_code = "AUS", numeric_code = 36 WHERE code_iso = "AUS";
UPDATE llx_c_country SET alpha2_code = "AT", alpha3_code = "AUT", numeric_code = 40 WHERE code_iso = "AUT";
UPDATE llx_c_country SET alpha2_code = "AZ", alpha3_code = "AZE", numeric_code = 31 WHERE code_iso = "AZE";
UPDATE llx_c_country SET alpha2_code = "BS", alpha3_code = "BHS", numeric_code = 44 WHERE code_iso = "BHS";
UPDATE llx_c_country SET alpha2_code = "BD", alpha3_code = "BGD", numeric_code = 50 WHERE code_iso = "BGD";
UPDATE llx_c_country SET alpha2_code = "BB", alpha3_code = "BRB", numeric_code = 52 WHERE code_iso = "BRB";
UPDATE llx_c_country SET alpha2_code = "BH", alpha3_code = "BHR", numeric_code = 48 WHERE code_iso = "BHR";
UPDATE llx_c_country SET alpha2_code = "BE", alpha3_code = "BEL", numeric_code = 56 WHERE code_iso = "BEL";
UPDATE llx_c_country SET alpha2_code = "BZ", alpha3_code = "BLZ", numeric_code = 84 WHERE code_iso = "BLZ";
UPDATE llx_c_country SET alpha2_code = "BJ", alpha3_code = "BEN", numeric_code = 204 WHERE code_iso = "BEN";
UPDATE llx_c_country SET alpha2_code = "BM", alpha3_code = "BMU", numeric_code = 60 WHERE code_iso = "BMU";
UPDATE llx_c_country SET alpha2_code = "BY", alpha3_code = "BLR", numeric_code = 112 WHERE code_iso = "BLR";
UPDATE llx_c_country SET alpha2_code = "BO", alpha3_code = "BOL", numeric_code = 68 WHERE code_iso = "BOL";
UPDATE llx_c_country SET alpha2_code = "BQ", alpha3_code = "BES", numeric_code = 535 WHERE code_iso = "BES";
UPDATE llx_c_country SET alpha2_code = "BA", alpha3_code = "BIH", numeric_code = 70 WHERE code_iso = "BIH";
UPDATE llx_c_country SET alpha2_code = "BW", alpha3_code = "BWA", numeric_code = 72 WHERE code_iso = "BWA";
UPDATE llx_c_country SET alpha2_code = "BR", alpha3_code = "BRA", numeric_code = 76 WHERE code_iso = "BRA";
UPDATE llx_c_country SET alpha2_code = "BN", alpha3_code = "BRN", numeric_code = 96 WHERE code_iso = "BRN";
UPDATE llx_c_country SET alpha2_code = "BG", alpha3_code = "BGR", numeric_code = 100 WHERE code_iso = "BGR";
UPDATE llx_c_country SET alpha2_code = "BF", alpha3_code = "BFA", numeric_code = 854 WHERE code_iso = "BFA";
UPDATE llx_c_country SET alpha2_code = "BI", alpha3_code = "BDI", numeric_code = 108 WHERE code_iso = "BDI";
UPDATE llx_c_country SET alpha2_code = "BT", alpha3_code = "BTN", numeric_code = 64 WHERE code_iso = "BTN";
UPDATE llx_c_country SET alpha2_code = "CV", alpha3_code = "CPV", numeric_code = 132 WHERE code_iso = "CPV";
UPDATE llx_c_country SET alpha2_code = "KH", alpha3_code = "KHM", numeric_code = 116 WHERE code_iso = "KHM";
UPDATE llx_c_country SET alpha2_code = "CM", alpha3_code = "CMR", numeric_code = 120 WHERE code_iso = "CMR";
UPDATE llx_c_country SET alpha2_code = "CA", alpha3_code = "CAN", numeric_code = 124 WHERE code_iso = "CAN";
UPDATE llx_c_country SET alpha2_code = "QA", alpha3_code = "QAT", numeric_code = 634 WHERE code_iso = "QAT";
UPDATE llx_c_country SET alpha2_code = "TD", alpha3_code = "TCD", numeric_code = 148 WHERE code_iso = "TCD";
UPDATE llx_c_country SET alpha2_code = "CL", alpha3_code = "CHL", numeric_code = 152 WHERE code_iso = "CHL";
UPDATE llx_c_country SET alpha2_code = "CN", alpha3_code = "CHN", numeric_code = 156 WHERE code_iso = "CHN";
UPDATE llx_c_country SET alpha2_code = "CY", alpha3_code = "CYP", numeric_code = 196 WHERE code_iso = "CYP";
UPDATE llx_c_country SET alpha2_code = "CO", alpha3_code = "COL", numeric_code = 170 WHERE code_iso = "COL";
UPDATE llx_c_country SET alpha2_code = "KM", alpha3_code = "COM", numeric_code = 174 WHERE code_iso = "COM";
UPDATE llx_c_country SET alpha2_code = "KP", alpha3_code = "PRK", numeric_code = 408 WHERE code_iso = "PRK";
UPDATE llx_c_country SET alpha2_code = "KR", alpha3_code = "KOR", numeric_code = 410 WHERE code_iso = "KOR";
UPDATE llx_c_country SET alpha2_code = "CI", alpha3_code = "CIV", numeric_code = 384 WHERE code_iso = "CIV";
UPDATE llx_c_country SET alpha2_code = "CR", alpha3_code = "CRI", numeric_code = 188 WHERE code_iso = "CRI";
UPDATE llx_c_country SET alpha2_code = "HR", alpha3_code = "HRV", numeric_code = 191 WHERE code_iso = "HRV";
UPDATE llx_c_country SET alpha2_code = "CU", alpha3_code = "CUB", numeric_code = 192 WHERE code_iso = "CUB";
UPDATE llx_c_country SET alpha2_code = "CW", alpha3_code = "CUW", numeric_code = 531 WHERE code_iso = "CUW";
UPDATE llx_c_country SET alpha2_code = "DK", alpha3_code = "DNK", numeric_code = 208 WHERE code_iso = "DNK";
UPDATE llx_c_country SET alpha2_code = "DM", alpha3_code = "DMA", numeric_code = 212 WHERE code_iso = "DMA";
UPDATE llx_c_country SET alpha2_code = "EC", alpha3_code = "ECU", numeric_code = 218 WHERE code_iso = "ECU";
UPDATE llx_c_country SET alpha2_code = "EG", alpha3_code = "EGY", numeric_code = 818 WHERE code_iso = "EGY";
UPDATE llx_c_country SET alpha2_code = "SV", alpha3_code = "SLV", numeric_code = 222 WHERE code_iso = "SLV";
UPDATE llx_c_country SET alpha2_code = "AE", alpha3_code = "ARE", numeric_code = 784 WHERE code_iso = "ARE";
UPDATE llx_c_country SET alpha2_code = "ER", alpha3_code = "ERI", numeric_code = 232 WHERE code_iso = "ERI";
UPDATE llx_c_country SET alpha2_code = "SK", alpha3_code = "SVK", numeric_code = 703 WHERE code_iso = "SVK";
UPDATE llx_c_country SET alpha2_code = "SI", alpha3_code = "SVN", numeric_code = 705 WHERE code_iso = "SVN";
UPDATE llx_c_country SET alpha2_code = "ES", alpha3_code = "ESP", numeric_code = 724 WHERE code_iso = "ESP";
UPDATE llx_c_country SET alpha2_code = "US", alpha3_code = "USA", numeric_code = 840 WHERE code_iso = "USA";
UPDATE llx_c_country SET alpha2_code = "EE", alpha3_code = "EST", numeric_code = 233 WHERE code_iso = "EST";
UPDATE llx_c_country SET alpha2_code = "ET", alpha3_code = "ETH", numeric_code = 231 WHERE code_iso = "ETH";
UPDATE llx_c_country SET alpha2_code = "PH", alpha3_code = "PHL", numeric_code = 608 WHERE code_iso = "PHL";
UPDATE llx_c_country SET alpha2_code = "FI", alpha3_code = "FIN", numeric_code = 246 WHERE code_iso = "FIN";
UPDATE llx_c_country SET alpha2_code = "FJ", alpha3_code = "FJI", numeric_code = 242 WHERE code_iso = "FJI";
UPDATE llx_c_country SET alpha2_code = "FR", alpha3_code = "FRA", numeric_code = 250 WHERE code_iso = "FRA";
UPDATE llx_c_country SET alpha2_code = "GA", alpha3_code = "GAB", numeric_code = 266 WHERE code_iso = "GAB";
UPDATE llx_c_country SET alpha2_code = "GM", alpha3_code = "GMB", numeric_code = 270 WHERE code_iso = "GMB";
UPDATE llx_c_country SET alpha2_code = "GE", alpha3_code = "GEO", numeric_code = 268 WHERE code_iso = "GEO";
UPDATE llx_c_country SET alpha2_code = "GH", alpha3_code = "GHA", numeric_code = 288 WHERE code_iso = "GHA";
UPDATE llx_c_country SET alpha2_code = "GI", alpha3_code = "GIB", numeric_code = 292 WHERE code_iso = "GIB";
UPDATE llx_c_country SET alpha2_code = "GD", alpha3_code = "GRD", numeric_code = 308 WHERE code_iso = "GRD";
UPDATE llx_c_country SET alpha2_code = "GR", alpha3_code = "GRC", numeric_code = 300 WHERE code_iso = "GRC";
UPDATE llx_c_country SET alpha2_code = "GL", alpha3_code = "GRL", numeric_code = 304 WHERE code_iso = "GRL";
UPDATE llx_c_country SET alpha2_code = "GP", alpha3_code = "GLP", numeric_code = 312 WHERE code_iso = "GLP";
UPDATE llx_c_country SET alpha2_code = "GU", alpha3_code = "GUM", numeric_code = 316 WHERE code_iso = "GUM";
UPDATE llx_c_country SET alpha2_code = "GT", alpha3_code = "GTM", numeric_code = 320 WHERE code_iso = "GTM";
UPDATE llx_c_country SET alpha2_code = "GF", alpha3_code = "GUF", numeric_code = 254 WHERE code_iso = "GUF";
UPDATE llx_c_country SET alpha2_code = "GG", alpha3_code = "GGY", numeric_code = 831 WHERE code_iso = "GGY";
UPDATE llx_c_country SET alpha2_code = "GN", alpha3_code = "GIN", numeric_code = 324 WHERE code_iso = "GIN";
UPDATE llx_c_country SET alpha2_code = "GW", alpha3_code = "GNB", numeric_code = 624 WHERE code_iso = "GNB";
UPDATE llx_c_country SET alpha2_code = "GQ", alpha3_code = "GNQ", numeric_code = 226 WHERE code_iso = "GNQ";
UPDATE llx_c_country SET alpha2_code = "GY", alpha3_code = "GUY", numeric_code = 328 WHERE code_iso = "GUY";
UPDATE llx_c_country SET alpha2_code = "HT", alpha3_code = "HTI", numeric_code = 332 WHERE code_iso = "HTI";
UPDATE llx_c_country SET alpha2_code = "HN", alpha3_code = "HND", numeric_code = 340 WHERE code_iso = "HND";
UPDATE llx_c_country SET alpha2_code = "HK", alpha3_code = "HKG", numeric_code = 344 WHERE code_iso = "HKG";
UPDATE llx_c_country SET alpha2_code = "HU", alpha3_code = "HUN", numeric_code = 348 WHERE code_iso = "HUN";
UPDATE llx_c_country SET alpha2_code = "IN", alpha3_code = "IND", numeric_code = 356 WHERE code_iso = "IND";
UPDATE llx_c_country SET alpha2_code = "ID", alpha3_code = "IDN", numeric_code = 360 WHERE code_iso = "IDN";
UPDATE llx_c_country SET alpha2_code = "IQ", alpha3_code = "IRQ", numeric_code = 368 WHERE code_iso = "IRQ";
UPDATE llx_c_country SET alpha2_code = "IR", alpha3_code = "IRN", numeric_code = 364 WHERE code_iso = "IRN";
UPDATE llx_c_country SET alpha2_code = "IE", alpha3_code = "IRL", numeric_code = 372 WHERE code_iso = "IRL";
UPDATE llx_c_country SET alpha2_code = "BV", alpha3_code = "BVT", numeric_code = 74 WHERE code_iso = "BVT";
UPDATE llx_c_country SET alpha2_code = "IM", alpha3_code = "IMN", numeric_code = 833 WHERE code_iso = "IMN";
UPDATE llx_c_country SET alpha2_code = "CX", alpha3_code = "CXR", numeric_code = 162 WHERE code_iso = "CXR";
UPDATE llx_c_country SET alpha2_code = "IS", alpha3_code = "ISL", numeric_code = 352 WHERE code_iso = "ISL";
UPDATE llx_c_country SET alpha2_code = "KY", alpha3_code = "CYM", numeric_code = 136 WHERE code_iso = "CYM";
UPDATE llx_c_country SET alpha2_code = "CC", alpha3_code = "CCK", numeric_code = 166 WHERE code_iso = "CCK";
UPDATE llx_c_country SET alpha2_code = "CK", alpha3_code = "COK", numeric_code = 184 WHERE code_iso = "COK";
UPDATE llx_c_country SET alpha2_code = "FO", alpha3_code = "FRO", numeric_code = 234 WHERE code_iso = "FRO";
UPDATE llx_c_country SET alpha2_code = "GS", alpha3_code = "SGS", numeric_code = 239 WHERE code_iso = "SGS";
UPDATE llx_c_country SET alpha2_code = "HM", alpha3_code = "HMD", numeric_code = 334 WHERE code_iso = "HMD";
UPDATE llx_c_country SET alpha2_code = "FK", alpha3_code = "FLK", numeric_code = 238 WHERE code_iso = "FLK";
UPDATE llx_c_country SET alpha2_code = "MP", alpha3_code = "MNP", numeric_code = 580 WHERE code_iso = "MNP";
UPDATE llx_c_country SET alpha2_code = "MH", alpha3_code = "MHL", numeric_code = 584 WHERE code_iso = "MHL";
UPDATE llx_c_country SET alpha2_code = "PN", alpha3_code = "PCN", numeric_code = 612 WHERE code_iso = "PCN";
UPDATE llx_c_country SET alpha2_code = "SB", alpha3_code = "SLB", numeric_code = 90 WHERE code_iso = "SLB";
UPDATE llx_c_country SET alpha2_code = "TC", alpha3_code = "TCA", numeric_code = 796 WHERE code_iso = "TCA";
UPDATE llx_c_country SET alpha2_code = "UM", alpha3_code = "UMI", numeric_code = 581 WHERE code_iso = "UMI";
UPDATE llx_c_country SET alpha2_code = "VG", alpha3_code = "VGB", numeric_code = 92 WHERE code_iso = "VGB";
UPDATE llx_c_country SET alpha2_code = "VI", alpha3_code = "VIR", numeric_code = 850 WHERE code_iso = "VIR";
UPDATE llx_c_country SET alpha2_code = "IL", alpha3_code = "ISR", numeric_code = 376 WHERE code_iso = "ISR";
UPDATE llx_c_country SET alpha2_code = "IT", alpha3_code = "ITA", numeric_code = 380 WHERE code_iso = "ITA";
UPDATE llx_c_country SET alpha2_code = "JM", alpha3_code = "JAM", numeric_code = 388 WHERE code_iso = "JAM";
UPDATE llx_c_country SET alpha2_code = "JP", alpha3_code = "JPN", numeric_code = 392 WHERE code_iso = "JPN";
UPDATE llx_c_country SET alpha2_code = "JE", alpha3_code = "JEY", numeric_code = 832 WHERE code_iso = "JEY";
UPDATE llx_c_country SET alpha2_code = "JO", alpha3_code = "JOR", numeric_code = 400 WHERE code_iso = "JOR";
UPDATE llx_c_country SET alpha2_code = "KZ", alpha3_code = "KAZ", numeric_code = 398 WHERE code_iso = "KAZ";
UPDATE llx_c_country SET alpha2_code = "KE", alpha3_code = "KEN", numeric_code = 404 WHERE code_iso = "KEN";
UPDATE llx_c_country SET alpha2_code = "KG", alpha3_code = "KGZ", numeric_code = 417 WHERE code_iso = "KGZ";
UPDATE llx_c_country SET alpha2_code = "KI", alpha3_code = "KIR", numeric_code = 296 WHERE code_iso = "KIR";
UPDATE llx_c_country SET alpha2_code = "KW", alpha3_code = "KWT", numeric_code = 414 WHERE code_iso = "KWT";
UPDATE llx_c_country SET alpha2_code = "LA", alpha3_code = "LAO", numeric_code = 418 WHERE code_iso = "LAO";
UPDATE llx_c_country SET alpha2_code = "LS", alpha3_code = "LSO", numeric_code = 426 WHERE code_iso = "LSO";
UPDATE llx_c_country SET alpha2_code = "LV", alpha3_code = "LVA", numeric_code = 428 WHERE code_iso = "LVA";
UPDATE llx_c_country SET alpha2_code = "LB", alpha3_code = "LBN", numeric_code = 422 WHERE code_iso = "LBN";
UPDATE llx_c_country SET alpha2_code = "LR", alpha3_code = "LBR", numeric_code = 430 WHERE code_iso = "LBR";
UPDATE llx_c_country SET alpha2_code = "LY", alpha3_code = "LBY", numeric_code = 434 WHERE code_iso = "LBY";
UPDATE llx_c_country SET alpha2_code = "LI", alpha3_code = "LIE", numeric_code = 438 WHERE code_iso = "LIE";
UPDATE llx_c_country SET alpha2_code = "LT", alpha3_code = "LTU", numeric_code = 440 WHERE code_iso = "LTU";
UPDATE llx_c_country SET alpha2_code = "LU", alpha3_code = "LUX", numeric_code = 442 WHERE code_iso = "LUX";
UPDATE llx_c_country SET alpha2_code = "MO", alpha3_code = "MAC", numeric_code = 446 WHERE code_iso = "MAC";
UPDATE llx_c_country SET alpha2_code = "MK", alpha3_code = "MKD", numeric_code = 807 WHERE code_iso = "MKD";
UPDATE llx_c_country SET alpha2_code = "MG", alpha3_code = "MDG", numeric_code = 450 WHERE code_iso = "MDG";
UPDATE llx_c_country SET alpha2_code = "MY", alpha3_code = "MYS", numeric_code = 458 WHERE code_iso = "MYS";
UPDATE llx_c_country SET alpha2_code = "MW", alpha3_code = "MWI", numeric_code = 454 WHERE code_iso = "MWI";
UPDATE llx_c_country SET alpha2_code = "MV", alpha3_code = "MDV", numeric_code = 462 WHERE code_iso = "MDV";
UPDATE llx_c_country SET alpha2_code = "ML", alpha3_code = "MLI", numeric_code = 466 WHERE code_iso = "MLI";
UPDATE llx_c_country SET alpha2_code = "MT", alpha3_code = "MLT", numeric_code = 470 WHERE code_iso = "MLT";
UPDATE llx_c_country SET alpha2_code = "MA", alpha3_code = "MAR", numeric_code = 504 WHERE code_iso = "MAR";
UPDATE llx_c_country SET alpha2_code = "MQ", alpha3_code = "MTQ", numeric_code = 474 WHERE code_iso = "MTQ";
UPDATE llx_c_country SET alpha2_code = "MU", alpha3_code = "MUS", numeric_code = 480 WHERE code_iso = "MUS";
UPDATE llx_c_country SET alpha2_code = "MR", alpha3_code = "MRT", numeric_code = 478 WHERE code_iso = "MRT";
UPDATE llx_c_country SET alpha2_code = "YT", alpha3_code = "MYT", numeric_code = 175 WHERE code_iso = "MYT";
UPDATE llx_c_country SET alpha2_code = "MX", alpha3_code = "MEX", numeric_code = 484 WHERE code_iso = "MEX";
UPDATE llx_c_country SET alpha2_code = "FM", alpha3_code = "FSM", numeric_code = 583 WHERE code_iso = "FSM";
UPDATE llx_c_country SET alpha2_code = "MD", alpha3_code = "MDA", numeric_code = 498 WHERE code_iso = "MDA";
UPDATE llx_c_country SET alpha2_code = "MC", alpha3_code = "MCO", numeric_code = 492 WHERE code_iso = "MCO";
UPDATE llx_c_country SET alpha2_code = "MN", alpha3_code = "MNG", numeric_code = 496 WHERE code_iso = "MNG";
UPDATE llx_c_country SET alpha2_code = "ME", alpha3_code = "MNE", numeric_code = 499 WHERE code_iso = "MNE";
UPDATE llx_c_country SET alpha2_code = "MS", alpha3_code = "MSR", numeric_code = 500 WHERE code_iso = "MSR";
UPDATE llx_c_country SET alpha2_code = "MZ", alpha3_code = "MOZ", numeric_code = 508 WHERE code_iso = "MOZ";
UPDATE llx_c_country SET alpha2_code = "MM", alpha3_code = "MMR", numeric_code = 104 WHERE code_iso = "MMR";
UPDATE llx_c_country SET alpha2_code = "NA", alpha3_code = "NAM", numeric_code = 516 WHERE code_iso = "NAM";
UPDATE llx_c_country SET alpha2_code = "NR", alpha3_code = "NRU", numeric_code = 520 WHERE code_iso = "NRU";
UPDATE llx_c_country SET alpha2_code = "NP", alpha3_code = "NPL", numeric_code = 524 WHERE code_iso = "NPL";
UPDATE llx_c_country SET alpha2_code = "NI", alpha3_code = "NIC", numeric_code = 558 WHERE code_iso = "NIC";
UPDATE llx_c_country SET alpha2_code = "NE", alpha3_code = "NER", numeric_code = 562 WHERE code_iso = "NER";
UPDATE llx_c_country SET alpha2_code = "NG", alpha3_code = "NGA", numeric_code = 566 WHERE code_iso = "NGA";
UPDATE llx_c_country SET alpha2_code = "NU", alpha3_code = "NIU", numeric_code = 570 WHERE code_iso = "NIU";
UPDATE llx_c_country SET alpha2_code = "NF", alpha3_code = "NFK", numeric_code = 574 WHERE code_iso = "NFK";
UPDATE llx_c_country SET alpha2_code = "NO", alpha3_code = "NOR", numeric_code = 578 WHERE code_iso = "NOR";
UPDATE llx_c_country SET alpha2_code = "NC", alpha3_code = "NCL", numeric_code = 540 WHERE code_iso = "NCL";
UPDATE llx_c_country SET alpha2_code = "NZ", alpha3_code = "NZL", numeric_code = 554 WHERE code_iso = "NZL";
UPDATE llx_c_country SET alpha2_code = "OM", alpha3_code = "OMN", numeric_code = 512 WHERE code_iso = "OMN";
UPDATE llx_c_country SET alpha2_code = "NL", alpha3_code = "NLD", numeric_code = 528 WHERE code_iso = "NLD";
UPDATE llx_c_country SET alpha2_code = "PK", alpha3_code = "PAK", numeric_code = 586 WHERE code_iso = "PAK";
UPDATE llx_c_country SET alpha2_code = "PW", alpha3_code = "PLW", numeric_code = 585 WHERE code_iso = "PLW";
UPDATE llx_c_country SET alpha2_code = "PS", alpha3_code = "PSE", numeric_code = 275 WHERE code_iso = "PSE";
UPDATE llx_c_country SET alpha2_code = "PA", alpha3_code = "PAN", numeric_code = 591 WHERE code_iso = "PAN";
UPDATE llx_c_country SET alpha2_code = "PG", alpha3_code = "PNG", numeric_code = 598 WHERE code_iso = "PNG";
UPDATE llx_c_country SET alpha2_code = "PY", alpha3_code = "PRY", numeric_code = 600 WHERE code_iso = "PRY";
UPDATE llx_c_country SET alpha2_code = "PE", alpha3_code = "PER", numeric_code = 604 WHERE code_iso = "PER";
UPDATE llx_c_country SET alpha2_code = "PF", alpha3_code = "PYF", numeric_code = 258 WHERE code_iso = "PYF";
UPDATE llx_c_country SET alpha2_code = "PL", alpha3_code = "POL", numeric_code = 616 WHERE code_iso = "POL";
UPDATE llx_c_country SET alpha2_code = "PT", alpha3_code = "PRT", numeric_code = 620 WHERE code_iso = "PRT";
UPDATE llx_c_country SET alpha2_code = "PR", alpha3_code = "PRI", numeric_code = 630 WHERE code_iso = "PRI";
UPDATE llx_c_country SET alpha2_code = "GB", alpha3_code = "GBR", numeric_code = 826 WHERE code_iso = "GBR";
UPDATE llx_c_country SET alpha2_code = "EH", alpha3_code = "ESH", numeric_code = 732 WHERE code_iso = "ESH";
UPDATE llx_c_country SET alpha2_code = "CF", alpha3_code = "CAF", numeric_code = 140 WHERE code_iso = "CAF";
UPDATE llx_c_country SET alpha2_code = "CZ", alpha3_code = "CZE", numeric_code = 203 WHERE code_iso = "CZE";
UPDATE llx_c_country SET alpha2_code = "CG", alpha3_code = "COG", numeric_code = 178 WHERE code_iso = "COG";
UPDATE llx_c_country SET alpha2_code = "CD", alpha3_code = "COD", numeric_code = 180 WHERE code_iso = "COD";
UPDATE llx_c_country SET alpha2_code = "DO", alpha3_code = "DOM", numeric_code = 214 WHERE code_iso = "DOM";
UPDATE llx_c_country SET alpha2_code = "RE", alpha3_code = "REU", numeric_code = 638 WHERE code_iso = "REU";
UPDATE llx_c_country SET alpha2_code = "RW", alpha3_code = "RWA", numeric_code = 646 WHERE code_iso = "RWA";
UPDATE llx_c_country SET alpha2_code = "RO", alpha3_code = "ROU", numeric_code = 642 WHERE code_iso = "ROU";
UPDATE llx_c_country SET alpha2_code = "RU", alpha3_code = "RUS", numeric_code = 643 WHERE code_iso = "RUS";
UPDATE llx_c_country SET alpha2_code = "WS", alpha3_code = "WSM", numeric_code = 882 WHERE code_iso = "WSM";
UPDATE llx_c_country SET alpha2_code = "AS", alpha3_code = "ASM", numeric_code = 16 WHERE code_iso = "ASM";
UPDATE llx_c_country SET alpha2_code = "BL", alpha3_code = "BLM", numeric_code = 652 WHERE code_iso = "BLM";
UPDATE llx_c_country SET alpha2_code = "KN", alpha3_code = "KNA", numeric_code = 659 WHERE code_iso = "KNA";
UPDATE llx_c_country SET alpha2_code = "SM", alpha3_code = "SMR", numeric_code = 674 WHERE code_iso = "SMR";
UPDATE llx_c_country SET alpha2_code = "MF", alpha3_code = "MAF", numeric_code = 663 WHERE code_iso = "MAF";
UPDATE llx_c_country SET alpha2_code = "PM", alpha3_code = "SPM", numeric_code = 666 WHERE code_iso = "SPM";
UPDATE llx_c_country SET alpha2_code = "VC", alpha3_code = "VCT", numeric_code = 670 WHERE code_iso = "VCT";
UPDATE llx_c_country SET alpha2_code = "SH", alpha3_code = "SHN", numeric_code = 654 WHERE code_iso = "SHN";
UPDATE llx_c_country SET alpha2_code = "LC", alpha3_code = "LCA", numeric_code = 662 WHERE code_iso = "LCA";
UPDATE llx_c_country SET alpha2_code = "ST", alpha3_code = "STP", numeric_code = 678 WHERE code_iso = "STP";
UPDATE llx_c_country SET alpha2_code = "SN", alpha3_code = "SEN", numeric_code = 686 WHERE code_iso = "SEN";
UPDATE llx_c_country SET alpha2_code = "RS", alpha3_code = "SRB", numeric_code = 688 WHERE code_iso = "SRB";
UPDATE llx_c_country SET alpha2_code = "SC", alpha3_code = "SYC", numeric_code = 690 WHERE code_iso = "SYC";
UPDATE llx_c_country SET alpha2_code = "SL", alpha3_code = "SLE", numeric_code = 694 WHERE code_iso = "SLE";
UPDATE llx_c_country SET alpha2_code = "SG", alpha3_code = "SGP", numeric_code = 702 WHERE code_iso = "SGP";
UPDATE llx_c_country SET alpha2_code = "SX", alpha3_code = "SXM", numeric_code = 534 WHERE code_iso = "SXM";
UPDATE llx_c_country SET alpha2_code = "SY", alpha3_code = "SYR", numeric_code = 760 WHERE code_iso = "SYR";
UPDATE llx_c_country SET alpha2_code = "SO", alpha3_code = "SOM", numeric_code = 706 WHERE code_iso = "SOM";
UPDATE llx_c_country SET alpha2_code = "LK", alpha3_code = "LKA", numeric_code = 144 WHERE code_iso = "LKA";
UPDATE llx_c_country SET alpha2_code = "SZ", alpha3_code = "SWZ", numeric_code = 748 WHERE code_iso = "SWZ";
UPDATE llx_c_country SET alpha2_code = "ZA", alpha3_code = "ZAF", numeric_code = 710 WHERE code_iso = "ZAF";
UPDATE llx_c_country SET alpha2_code = "SD", alpha3_code = "SDN", numeric_code = 729 WHERE code_iso = "SDN";
UPDATE llx_c_country SET alpha2_code = "SS", alpha3_code = "SSD", numeric_code = 728 WHERE code_iso = "SSD";
UPDATE llx_c_country SET alpha2_code = "SE", alpha3_code = "SWE", numeric_code = 752 WHERE code_iso = "SWE";
UPDATE llx_c_country SET alpha2_code = "CH", alpha3_code = "CHE", numeric_code = 756 WHERE code_iso = "CHE";
UPDATE llx_c_country SET alpha2_code = "SR", alpha3_code = "SUR", numeric_code = 740 WHERE code_iso = "SUR";
UPDATE llx_c_country SET alpha2_code = "SJ", alpha3_code = "SJM", numeric_code = 744 WHERE code_iso = "SJM";
UPDATE llx_c_country SET alpha2_code = "TH", alpha3_code = "THA", numeric_code = 764 WHERE code_iso = "THA";
UPDATE llx_c_country SET alpha2_code = "TW", alpha3_code = "TWN", numeric_code = 158 WHERE code_iso = "TWN";
UPDATE llx_c_country SET alpha2_code = "TZ", alpha3_code = "TZA", numeric_code = 834 WHERE code_iso = "TZA";
UPDATE llx_c_country SET alpha2_code = "TJ", alpha3_code = "TJK", numeric_code = 762 WHERE code_iso = "TJK";
UPDATE llx_c_country SET alpha2_code = "IO", alpha3_code = "IOT", numeric_code = 86 WHERE code_iso = "IOT";
UPDATE llx_c_country SET alpha2_code = "TF", alpha3_code = "ATF", numeric_code = 260 WHERE code_iso = "ATF";
UPDATE llx_c_country SET alpha2_code = "TL", alpha3_code = "TLS", numeric_code = 626 WHERE code_iso = "TLS";
UPDATE llx_c_country SET alpha2_code = "TG", alpha3_code = "TGO", numeric_code = 768 WHERE code_iso = "TGO";
UPDATE llx_c_country SET alpha2_code = "TK", alpha3_code = "TKL", numeric_code = 772 WHERE code_iso = "TKL";
UPDATE llx_c_country SET alpha2_code = "TO", alpha3_code = "TON", numeric_code = 776 WHERE code_iso = "TON";
UPDATE llx_c_country SET alpha2_code = "TT", alpha3_code = "TTO", numeric_code = 780 WHERE code_iso = "TTO";
UPDATE llx_c_country SET alpha2_code = "TN", alpha3_code = "TUN", numeric_code = 788 WHERE code_iso = "TUN";
UPDATE llx_c_country SET alpha2_code = "TM", alpha3_code = "TKM", numeric_code = 795 WHERE code_iso = "TKM";
UPDATE llx_c_country SET alpha2_code = "TR", alpha3_code = "TUR", numeric_code = 792 WHERE code_iso = "TUR";
UPDATE llx_c_country SET alpha2_code = "TV", alpha3_code = "TUV", numeric_code = 798 WHERE code_iso = "TUV";
UPDATE llx_c_country SET alpha2_code = "UA", alpha3_code = "UKR", numeric_code = 804 WHERE code_iso = "UKR";
UPDATE llx_c_country SET alpha2_code = "UG", alpha3_code = "UGA", numeric_code = 800 WHERE code_iso = "UGA";
UPDATE llx_c_country SET alpha2_code = "UY", alpha3_code = "URY", numeric_code = 858 WHERE code_iso = "URY";
UPDATE llx_c_country SET alpha2_code = "UZ", alpha3_code = "UZB", numeric_code = 860 WHERE code_iso = "UZB";
UPDATE llx_c_country SET alpha2_code = "VU", alpha3_code = "VUT", numeric_code = 548 WHERE code_iso = "VUT";
UPDATE llx_c_country SET alpha2_code = "VA", alpha3_code = "VAT", numeric_code = 336 WHERE code_iso = "VAT";
UPDATE llx_c_country SET alpha2_code = "VE", alpha3_code = "VEN", numeric_code = 862 WHERE code_iso = "VEN";
UPDATE llx_c_country SET alpha2_code = "VN", alpha3_code = "VNM", numeric_code = 704 WHERE code_iso = "VNM";
UPDATE llx_c_country SET alpha2_code = "WF", alpha3_code = "WLF", numeric_code = 876 WHERE code_iso = "WLF";
UPDATE llx_c_country SET alpha2_code = "YE", alpha3_code = "YEM", numeric_code = 887 WHERE code_iso = "YEM";
UPDATE llx_c_country SET alpha2_code = "DJ", alpha3_code = "DJI", numeric_code = 262 WHERE code_iso = "DJI";
UPDATE llx_c_country SET alpha2_code = "ZM", alpha3_code = "ZMB", numeric_code = 894 WHERE code_iso = "ZMB";
UPDATE llx_c_country SET alpha2_code = "ZW", alpha3_code = "ZWE", numeric_code = 716 WHERE code_iso = "ZWE";
