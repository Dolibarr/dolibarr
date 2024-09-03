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

-- VMYSQL4.3 ALTER TABLE llx_emailcollector_emailcollector MODIFY COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_accounting_account DROP FOREIGN KEY fk_accounting_account_fk_pcg_version;
ALTER TABLE llx_accounting_system MODIFY COLUMN pcg_version varchar(32) NOT NULL;
ALTER TABLE llx_accounting_account ADD CONSTRAINT fk_accounting_account_fk_pcg_version FOREIGN KEY (fk_pcg_version) REFERENCES llx_accounting_system (pcg_version);

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

ALTER TABLE llx_overwrite_trans DROP INDEX uk_overwrite_trans;
ALTER TABLE llx_overwrite_trans ADD UNIQUE INDEX uk_overwrite_trans(lang, transkey, entity);

-- v17

ALTER TABLE llx_mailing_cibles MODIFY COLUMN source_type varchar(32); 

ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_percent (percent);

UPDATE llx_c_paiement SET code = 'BANCON' WHERE code = 'BAN' AND libelle = 'Bancontact';

ALTER TABLE llx_partnership DROP FOREIGN KEY llx_partnership_fk_user_creat;
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
ALTER TABLE llx_user ADD last_main_doc VARCHAR(255) NULL AFTER model_pdf;

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

ALTER TABLE llx_projet ADD COLUMN extraparams varchar(255);

DELETE FROM llx_const WHERE name = 'TICKET_CREATE_THIRD_PARTY_WITH_CONTACT_IF_NOT_EXIST';

INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΠΡΩΤΟΧΡΟΝΙΑ', 0, 102, '', 0,  1,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΘΕΟΦΑΝΕΙΑ', 0, 102, '', 0,  1,  6, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-25Η ΜΑΡΤΙΟΥ', 0, 102, '', 0,  3,  25, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΠΡΩΤΟΜΑΓΙΑ', 0, 102, '', 0,  5,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΚΑΘΑΡΑ ΔΕΥΤΕΡΑ', 0, 102, 'ΚΑΘΑΡΑ_ΔΕΥΤΕΡΑ', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΜΕΓΑΛΗ ΠΑΡΑΣΚΕΥΗ', 0, 102, 'ΜΕΓΑΛΗ_ΠΑΡΑΣΚΕΥΗ', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΔΕΥΤΕΡΑ ΤΟΥ ΠΑΣΧΑ', 0, 102, 'ΔΕΥΤΕΡΑ_ΤΟΥ_ΠΑΣΧΑ', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΤΟΥ ΑΓΙΟΥ ΠΝΕΥΜΑΤΟΣ', 0, 102, 'ΤΟΥ_ΑΓΙΟΥ_ΠΝΕΥΜΑΤΟΣ', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΚΟΙΜΗΣΗ ΤΗΣ ΘΕΟΤΟΚΟΥ', 0, 102, '', 0,  8,  15, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-28Η ΟΚΤΩΒΡΙΟΥ', 0, 102, '', 0,  10, 28, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΧΡΙΣΤΟΥΓΕΝΝΑ', 0, 102, '', 0,  12, 25, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΣΥΝΑΞΗ ΘΕΟΤΟΚΟΥ', 0, 102, '', 0, 12, 26, 1);

UPDATE llx_menu SET url = '/fourn/paiement/list.php?mainmenu=billing&leftmenu=suppliers_bills_payment' WHERE leftmenu = 'suppliers_bills_payment';

