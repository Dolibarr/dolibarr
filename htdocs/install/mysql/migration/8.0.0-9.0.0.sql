--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 9.0.0 or higher.
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


-- Missing in 8.0
ALTER TABLE llx_contrat_extrafields ADD INDEX idx_contrat_extrafields (fk_object);
ALTER TABLE llx_facture_rec_extrafields ADD INDEX idx_facture_rec_extrafields (fk_object);

ALTER TABLE llx_accounting_account DROP FOREIGN KEY fk_accounting_account_fk_pcg_version;
ALTER TABLE llx_accounting_account MODIFY COLUMN fk_pcg_version varchar(32) NOT NULL;
ALTER TABLE llx_accounting_system MODIFY COLUMN pcg_version varchar(32) NOT NULL;
ALTER TABLE llx_accounting_account ADD CONSTRAINT fk_accounting_account_fk_pcg_version    FOREIGN KEY (fk_pcg_version)    REFERENCES llx_accounting_system (pcg_version);

ALTER TABLE llx_facture ADD COLUMN module_source varchar(32);
ALTER TABLE llx_facture ADD COLUMN pos_source varchar(32);

create table llx_facture_rec_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_actioncomm ADD COLUMN email_subject varchar(255) after email_msgid;
ALTER TABLE llx_actioncomm ADD COLUMN email_tocc varchar(255) after email_to;
ALTER TABLE llx_actioncomm ADD COLUMN email_tobcc varchar(255) after email_tocc;

ALTER TABLE llx_actioncomm MODIFY COLUMN code varchar(50);


-- For 9.0
ALTER TABLE llx_extrafields ADD COLUMN help text NULL;
ALTER TABLE llx_extrafields ADD COLUMN totalizable boolean DEFAULT FALSE after list;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN desc_fourn text after ref_fourn;


ALTER TABLE llx_user ADD COLUMN dateemploymentend date after dateemployment;

ALTER TABLE llx_stock_mouvement ADD COLUMN fk_project integer;
ALTER TABLE llx_c_action_trigger MODIFY COLUMN elementtype varchar(32);
ALTER TABLE llx_c_field_list ADD COLUMN visible tinyint	DEFAULT 1 NOT NULL AFTER search;


insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_DELETE','Third party deleted','Executed when you delete third party','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_DELETE','Customer proposal deleted','Executed when a customer proposal is deleted','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_DELETE','Customer order deleted','Executed when a customer order is deleted','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_DELETE','Customer invoice deleted','Executed when a customer invoice is deleted','facture',9);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_DELETE','Price request deleted','Executed when a customer proposal delete','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_DELETE','Supplier order deleted','Executed when a supplier order is deleted','order_supplier',14);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_DELETE','Supplier invoice deleted','Executed when a supplier invoice is deleted','invoice_supplier',17);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_DELETE','Contract deleted','Executed when a contract is deleted','contrat',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_DELETE','Intervention is deleted','Executed when a intervention is deleted','ficheinter',35);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_DELETE','Expense report deleted','Executed when an expense report is deleted','expensereport',204);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_VALIDATE','Expense report validated','Executed when an expense report is validated','expensereport',202);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_APPROVE','Expense report approved','Executed when an expense report is approved','expensereport',203);

INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8001', 'Aktieselvskab A/S');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8002', 'Anparts Selvskab ApS');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8003', 'Personlig ejet selvskab');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8004', 'Iværksætterselvskab IVS');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8005', 'Interessentskab I/S');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8006', 'Holdingselskab');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8007', 'Selskab Med Begrænset Hæftelse SMBA');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8008', 'Kommanditselskab K/S');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8009', 'SPE-selskab');

ALTER TABLE llx_payment_salary ADD COLUMN ref varchar(30) NULL after rowid;
ALTER TABLE llx_payment_salary ADD COLUMN fk_projet integer DEFAULT NULL after amount;

ALTER TABLE llx_payment_various ADD COLUMN ref varchar(30) NULL after rowid;

ALTER TABLE llx_categorie ADD COLUMN ref_ext varchar(255);

ALTER TABLE llx_paiement ADD COLUMN ext_payment_id varchar(128);
ALTER TABLE llx_paiement ADD COLUMN ext_payment_site varchar(128);

ALTER TABLE llx_societe ADD COLUMN twitter  varchar(255) after skype;
ALTER TABLE llx_societe ADD COLUMN facebook varchar(255) after skype;
ALTER TABLE llx_societe ADD COLUMN instagram  varchar(255) after skype;
ALTER TABLE llx_societe ADD COLUMN snapchat  varchar(255) after skype;
ALTER TABLE llx_societe ADD COLUMN googleplus  varchar(255) after skype;
ALTER TABLE llx_societe ADD COLUMN youtube  varchar(255) after skype;
ALTER TABLE llx_societe ADD COLUMN whatsapp  varchar(255) after skype;

ALTER TABLE llx_socpeople ADD COLUMN twitter  varchar(255) after skype;
ALTER TABLE llx_socpeople ADD COLUMN facebook varchar(255) after skype;
ALTER TABLE llx_socpeople ADD COLUMN instagram  varchar(255) after skype;
ALTER TABLE llx_socpeople ADD COLUMN snapchat  varchar(255) after skype;
ALTER TABLE llx_socpeople ADD COLUMN googleplus  varchar(255) after skype;
ALTER TABLE llx_socpeople ADD COLUMN youtube  varchar(255) after skype;
ALTER TABLE llx_socpeople ADD COLUMN whatsapp  varchar(255) after skype;

ALTER TABLE llx_adherent ADD COLUMN skype  varchar(255);
ALTER TABLE llx_adherent ADD COLUMN twitter  varchar(255);
ALTER TABLE llx_adherent ADD COLUMN facebook varchar(255);
ALTER TABLE llx_adherent ADD COLUMN instagram  varchar(255);
ALTER TABLE llx_adherent ADD COLUMN snapchat  varchar(255);
ALTER TABLE llx_adherent ADD COLUMN googleplus  varchar(255);
ALTER TABLE llx_adherent ADD COLUMN youtube  varchar(255);
ALTER TABLE llx_adherent ADD COLUMN whatsapp  varchar(255);

ALTER TABLE llx_user ADD COLUMN skype  varchar(255);
ALTER TABLE llx_user ADD COLUMN twitter  varchar(255);
ALTER TABLE llx_user ADD COLUMN facebook varchar(255);
ALTER TABLE llx_user ADD COLUMN instagram  varchar(255);
ALTER TABLE llx_user ADD COLUMN snapchat  varchar(255);
ALTER TABLE llx_user ADD COLUMN googleplus  varchar(255);
ALTER TABLE llx_user ADD COLUMN youtube  varchar(255);
ALTER TABLE llx_user ADD COLUMN whatsapp  varchar(255);


ALTER TABLE llx_website CHANGE COLUMN fk_user_create fk_user_creat integer;
ALTER TABLE llx_website_page CHANGE COLUMN fk_user_create fk_user_creat integer;

ALTER TABLE llx_website ADD COLUMN maincolor varchar(16);
ALTER TABLE llx_website ADD COLUMN maincolorbis varchar(16);

ALTER TABLE llx_website_page ADD COLUMN image varchar(255);

CREATE TABLE llx_takepos_floor_tables(
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    entity integer DEFAULT 1 NOT NULL,
    label varchar(255),
    leftpos float,
    toppos float,
    floor smallint
) ENGINE=innodb;


UPDATE llx_c_payment_term SET decalage = nbjour, nbjour = 0 where decalage IS NULL AND type_cdr = 2;


UPDATE llx_holiday SET ref = rowid WHERE ref IS NULL;


-- DROP TABLE llx_emailcollector_emailcollectorfilter;
-- DROP TABLE llx_emailcollector_emailcollectoraction;
-- DROP TABLE llx_emailcollector_emailcollector;

CREATE TABLE llx_emailcollector_emailcollector(
        -- BEGIN MODULEBUILDER FIELDS
        rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
        entity integer DEFAULT 1 NOT NULL,
        ref varchar(128) NOT NULL,
        label varchar(255),
        description text,
        host varchar(255),
        login varchar(128),
        password varchar(128),
        source_directory varchar(255) NOT NULL,
        target_directory varchar(255),
        datelastresult datetime,
        codelastresult varchar(16),
        lastresult varchar(255),
        note_public text,
        note_private text,
        date_creation datetime NOT NULL,
        tms timestamp NOT NULL,
        fk_user_creat integer NOT NULL,
        fk_user_modif integer,
        import_key varchar(14),
        status integer NOT NULL
        -- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN login varchar(128);
ALTER TABLE llx_emailcollector_emailcollector ADD INDEX idx_emailcollector_entity (entity);
ALTER TABLE llx_emailcollector_emailcollector ADD INDEX idx_emailcollector_status (status);


CREATE TABLE llx_emailcollector_emailcollectorfilter(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_emailcollector INTEGER NOT NULL,
	type varchar(128) NOT NULL,
	rulevalue varchar(128) NULL,
	date_creation datetime NOT NULL,
	tms timestamp NOT NULL,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	import_key varchar(14),
	status integer NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

CREATE TABLE llx_emailcollector_emailcollectoraction(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_emailcollector INTEGER NOT NULL,
	type varchar(128) NOT NULL,
	actionparam varchar(255) NULL,
	date_creation datetime NOT NULL,
	tms timestamp NOT NULL,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	position integer DEFAULT 0,
	import_key varchar(14),
	status integer NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE llx_emailcollector_emailcollectorfilter ADD INDEX idx_emailcollector_fk_emailcollector (fk_emailcollector);
ALTER TABLE llx_emailcollector_emailcollectorfilter ADD CONSTRAINT fk_emailcollectorfilter_fk_emailcollector FOREIGN KEY (fk_emailcollector) REFERENCES llx_emailcollector_emailcollector(rowid);

ALTER TABLE llx_emailcollector_emailcollectoraction ADD INDEX idx_emailcollector_fk_emailcollector (fk_emailcollector);
ALTER TABLE llx_emailcollector_emailcollectoraction ADD CONSTRAINT fk_emailcollectoraction_fk_emailcollector FOREIGN KEY (fk_emailcollector) REFERENCES llx_emailcollector_emailcollector(rowid);


ALTER TABLE llx_emailcollector_emailcollectorfilter ADD UNIQUE INDEX uk_emailcollector_emailcollectorfilter (fk_emailcollector, type, rulevalue);
ALTER TABLE llx_emailcollector_emailcollectoraction ADD UNIQUE INDEX uk_emailcollector_emailcollectoraction (fk_emailcollector, type);

ALTER TABLE llx_societe_rib ADD COLUMN   comment        varchar(255);
ALTER TABLE llx_societe_rib ADD COLUMN   ipaddress      varchar(68);

DROP TABLE llx_ticket_logs;


CREATE TABLE llx_pos_cash_fence(
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
	entity INTEGER DEFAULT 1 NOT NULL,
	ref VARCHAR(64),
	label VARCHAR(255),
	opening double(24,8) default 0,
	cash double(24,8) default 0,
	card double(24,8) default 0,
	cheque double(24,8) default 0,
	status INTEGER,
	date_creation DATETIME NOT NULL,
	date_valid DATETIME,
	day_close INTEGER,
	month_close INTEGER,
	year_close INTEGER,
	posmodule VARCHAR(30),
	posnumber VARCHAR(30),
	fk_user_creat integer,
	fk_user_valid integer,
	tms TIMESTAMP NOT NULL,
	import_key VARCHAR(14)
) ENGINE=innodb;

-- VMYSQL4.3 ALTER TABLE llx_accounting_account MODIFY COLUMN account_number varchar(32) NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_accounting_account ALTER COLUMN account_number SET NOT NULL;


-- Withdrawals / Prelevements
UPDATE llx_const set name = __ENCRYPT('PRELEVEMENT_END_TO_END')__ where name = __ENCRYPT('END_TO_END')__;
UPDATE llx_const set name = __ENCRYPT('PRELEVEMENT_USTRD')__ where name = __ENCRYPT('USTRD')__;


-- Delete duplicate accounting account, but only if not used
DROP TABLE tmp_llx_accouting_account;
CREATE TABLE tmp_llx_accouting_account AS SELECT MIN(rowid) as MINID, MAX(rowid) as MAXID, account_number, entity, fk_pcg_version, count(*) AS NB FROM llx_accounting_account group BY account_number, entity, fk_pcg_version HAVING count(*) >= 2 order by account_number, entity, fk_pcg_version;
--SELECT * from tmp_llx_accouting_account;
DELETE from llx_accounting_account where rowid in (select minid from tmp_llx_accouting_account where minid NOT IN (SELECT fk_code_ventilation from llx_facturedet) AND minid NOT IN (SELECT fk_code_ventilation from llx_facture_fourn_det) AND minid NOT IN (SELECT fk_code_ventilation from llx_expensereport_det));

-- If there is record in tmp_llx_accouting_account, make a look on each line to do
--update llx_facturedet        set fk_code_ventilation = maxid WHERE fk_code_ventilation = minid;
--update llx_facture_fourn_det set fk_code_ventilation = maxid WHERE fk_code_ventilation = minid;
--update llx_expensereport_det set fk_code_ventilation = maxid WHERE fk_code_ventilation = minid;

ALTER TABLE llx_accounting_account DROP INDEX uk_accounting_account;
ALTER TABLE llx_accounting_account ADD UNIQUE INDEX uk_accounting_account (account_number, entity, fk_pcg_version);

UPDATE llx_projet SET fk_opp_status = NULL WHERE fk_opp_status = -1;

