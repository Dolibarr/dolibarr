--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 18.0.0 or higher.

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


-- Missing in v17 or lower

-- VMYSQL4.3 ALTER TABLE llx_emailcollector_emailcollector MODIFY COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- VMYSQL4.3 ALTER TABLE llx_hrm_skillrank CHANGE COLUMN `rank` rankorder integer;
-- VPGSQL8.2 ALTER TABLE llx_hrm_skillrank CHANGE COLUMN rank rankorder integer;

ALTER TABLE llx_projet_task ADD COLUMN fk_user_modif integer after fk_user_creat;

ALTER TABLE llx_accounting_system CHANGE COLUMN fk_pays fk_country integer;

ALTER TABLE llx_commande_fournisseurdet MODIFY COLUMN ref varchar(128);
ALTER TABLE llx_facture_fourn_det MODIFY COLUMN ref varchar(128);

ALTER TABLE llx_projet ADD COLUMN extraparams varchar(255);

DELETE FROM llx_boxes WHERE box_id IN (select rowid FROM llx_boxes_def WHERE file IN ('box_bom.php@bom', 'box_bom.php', 'box_members.php', 'box_last_modified_ticket', 'box_members_last_subscriptions', 'box_members_last_modified', 'box_members_subscriptions_by_year'));
DELETE FROM llx_boxes_def WHERE file IN ('box_bom.php@bom', 'box_bom.php', 'box_members.php', 'box_last_modified_ticket', 'box_members_last_subscriptions', 'box_members_last_modified', 'box_members_subscriptions_by_year');


-- v18

ALTER TABLE llx_notify_def ADD COLUMN email varchar(255);
ALTER TABLE llx_notify_def ADD COLUMN threshold double(24,8);
ALTER TABLE llx_notify_def ADD COLUMN context varchar(128);

ALTER TABLE llx_c_action_trigger ADD COLUMN contexts varchar(255) NULL;

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_CLOSE','Project closed','Executed when a project is closed','project',145);

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILLREC_CREATE','Template invoices created','Executed when a Template invoices is created','facturerec',900);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILLREC_MODIFY','Template invoices update','Executed when a Template invoices is updated','facturerec',901);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILLREC_DELETE','Template invoices deleted','Executed when a Template invoices is deleted','facturerec',902);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILLREC_AUTOCREATEBILL','Template invoices use to create invoices with auto batch','Executed when a Template invoices is use to create invoice with auto batch','facturerec',903);

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PARTNERSHIP_CREATE','Partnership created','Executed when a partnership is created','partnership',58000);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PARTNERSHIP_MODIFY','Partnership modified','Executed when a partnership is modified','partnership',58002);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PARTNERSHIP_SENTBYMAIL','Mails sent from partnership file','Executed when you send email from partnership file','partnership',58004);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PARTNERSHIP_DELETE','Partnership deleted','Executed when a partnership is deleted','partnership',58006);


-- amount was removed in v12
ALTER TABLE llx_facture DROP COLUMN amount;

-- Rename prospect level on contact
ALTER TABLE llx_socpeople CHANGE fk_prospectcontactlevel fk_prospectlevel varchar(12);

ALTER TABLE llx_facture ADD COLUMN prorata_discount	real DEFAULT NULL;

ALTER TABLE llx_facture MODIFY COLUMN situation_cycle_ref integer;

ALTER TABLE llx_payment_salary MODIFY COLUMN datep datetime;

INSERT INTO llx_c_tva(rowid,fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active) values (1179, 117, 'I-28'  , 28,   0, '0',   0, '0', 0, 'IGST',      1);
INSERT INTO llx_c_tva(rowid,fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active) values (1176, 117, 'C+S-18',  0,   9, '1',   9, '1', 0, 'CGST+SGST - Same state sales', 1);

ALTER TABLE llx_user ADD COLUMN flagdelsessionsbefore datetime DEFAULT NULL;

ALTER TABLE llx_website ADD COLUMN pageviews_previous_month BIGINT UNSIGNED DEFAULT 0;

ALTER TABLE llx_product_stock ADD CONSTRAINT fk_product_product_rowid FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
ALTER TABLE llx_product_stock ADD CONSTRAINT fk_entrepot_entrepot_rowid FOREIGN KEY (fk_entrepot) REFERENCES llx_entrepot (rowid);


ALTER TABLE llx_bank_account ADD COLUMN owner_zip varchar(25);
ALTER TABLE llx_bank_account ADD COLUMN owner_town varchar(50);
ALTER TABLE llx_bank_account ADD COLUMN owner_country_id integer DEFAULT NULL;

ALTER TABLE llx_prelevement_bons ADD COLUMN fk_bank_account integer DEFAULT NULL;

ALTER TABLE llx_supplier_proposal ADD UNIQUE INDEX uk_supplier_proposal_ref (ref, entity);

ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_soc (fk_soc);
ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_user_author (fk_user_author);
ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_user_valid (fk_user_valid);
ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_projet (fk_projet);
ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_account(fk_account);

ALTER TABLE llx_ecm_files ADD COLUMN share_pass varchar(32) after share;

ALTER TABLE llx_prelevement_demande ADD COLUMN type varchar(12) DEFAULT '';
UPDATE llx_prelevement_demande SET type = 'ban' WHERE ext_payment_id IS NULL AND type = '';

ALTER TABLE llx_recruitment_recruitmentcandidature ADD COLUMN fk_user integer;

ALTER TABLE llx_bordereau_cheque ADD COLUMN type VARCHAR(6) DEFAULT 'CHQ';

-- Element time
ALTER TABLE llx_projet_task_time RENAME TO llx_element_time;

-- VPGSQL8.2 ALTER SEQUENCE llx_projet_task_time_rowid_seq RENAME TO llx_element_time_rowid_seq;

-- VMYSQL4.1 SET sql_mode = 'ALLOW_INVALID_DATES';
-- VMYSQL4.1 update llx_element_time set task_date = NULL where DATE(STR_TO_DATE(task_date, '%Y-%m-%d')) IS NULL;
-- VMYSQL4.1 SET sql_mode = 'NO_ZERO_DATE';
-- VMYSQL4.1 update llx_element_time set task_date = NULL where DATE(STR_TO_DATE(task_date, '%Y-%m-%d')) IS NULL;

ALTER TABLE llx_element_time CHANGE COLUMN fk_task fk_element integer NOT NULL;
ALTER TABLE llx_element_time CHANGE COLUMN task_date element_date date;
ALTER TABLE llx_element_time CHANGE COLUMN task_datehour element_datehour datetime;
ALTER TABLE llx_element_time CHANGE COLUMN task_date_withhour element_date_withhour integer;
ALTER TABLE llx_element_time CHANGE COLUMN task_duration element_duration double;
ALTER TABLE llx_element_time ADD COLUMN elementtype varchar(32) NOT NULL DEFAULT 'task' AFTER fk_element;

-- VMYSQL4.1 DROP INDEX idx_projet_task_time_task on llx_element_time;
-- VMYSQL4.1 DROP INDEX idx_projet_task_time_date on llx_element_time;
-- VMYSQL4.1 DROP INDEX idx_projet_task_time_datehour on llx_element_time;
-- VPGSQL8.2 DROP INDEX idx_projet_task_time_task;
-- VPGSQL8.2 DROP INDEX idx_projet_task_time_date;
-- VPGSQL8.2 DROP INDEX idx_projet_task_time_datehour;

ALTER TABLE llx_element_time ADD INDEX idx_element_time_task (fk_element);
ALTER TABLE llx_element_time ADD INDEX idx_element_time_date (element_date);
ALTER TABLE llx_element_time ADD INDEX idx_element_time_datehour (element_datehour);


ALTER TABLE llx_c_country ADD COLUMN numeric_code VARCHAR(3);

UPDATE llx_c_country SET numeric_code = '004' WHERE code_iso = 'AFG';
UPDATE llx_c_country SET numeric_code = '248' WHERE code_iso = 'ALA';
UPDATE llx_c_country SET numeric_code = '008' WHERE code_iso = 'ALB';
UPDATE llx_c_country SET numeric_code = '276' WHERE code_iso = 'DEU';
UPDATE llx_c_country SET numeric_code = '020' WHERE code_iso = 'AND';
UPDATE llx_c_country SET numeric_code = '024' WHERE code_iso = 'AGO';
UPDATE llx_c_country SET numeric_code = '660' WHERE code_iso = 'AIA';
UPDATE llx_c_country SET numeric_code = '010' WHERE code_iso = 'ATA';
UPDATE llx_c_country SET numeric_code = '028' WHERE code_iso = 'ATG';
UPDATE llx_c_country SET numeric_code = '682' WHERE code_iso = 'SAU';
UPDATE llx_c_country SET numeric_code = '012' WHERE code_iso = 'DZA';
UPDATE llx_c_country SET numeric_code = '032' WHERE code_iso = 'ARG';
UPDATE llx_c_country SET numeric_code = '051' WHERE code_iso = 'ARM';
UPDATE llx_c_country SET numeric_code = '533' WHERE code_iso = 'ABW';
UPDATE llx_c_country SET numeric_code = '036' WHERE code_iso = 'AUS';
UPDATE llx_c_country SET numeric_code = '040' WHERE code_iso = 'AUT';
UPDATE llx_c_country SET numeric_code = '031' WHERE code_iso = 'AZE';
UPDATE llx_c_country SET numeric_code = '044' WHERE code_iso = 'BHS';
UPDATE llx_c_country SET numeric_code = '050' WHERE code_iso = 'BGD';
UPDATE llx_c_country SET numeric_code = '052' WHERE code_iso = 'BRB';
UPDATE llx_c_country SET numeric_code = '048' WHERE code_iso = 'BHR';
UPDATE llx_c_country SET numeric_code = '056' WHERE code_iso = 'BEL';
UPDATE llx_c_country SET numeric_code = '084' WHERE code_iso = 'BLZ';
UPDATE llx_c_country SET numeric_code = '204' WHERE code_iso = 'BEN';
UPDATE llx_c_country SET numeric_code = '060' WHERE code_iso = 'BMU';
UPDATE llx_c_country SET numeric_code = '112' WHERE code_iso = 'BLR';
UPDATE llx_c_country SET numeric_code = '068' WHERE code_iso = 'BOL';
UPDATE llx_c_country SET numeric_code = '535' WHERE code_iso = 'BES';
UPDATE llx_c_country SET numeric_code = '070' WHERE code_iso = 'BIH';
UPDATE llx_c_country SET numeric_code = '072' WHERE code_iso = 'BWA';
UPDATE llx_c_country SET numeric_code = '076' WHERE code_iso = 'BRA';
UPDATE llx_c_country SET numeric_code = '096' WHERE code_iso = 'BRN';
UPDATE llx_c_country SET numeric_code = '100' WHERE code_iso = 'BGR';
UPDATE llx_c_country SET numeric_code = '854' WHERE code_iso = 'BFA';
UPDATE llx_c_country SET numeric_code = '108' WHERE code_iso = 'BDI';
UPDATE llx_c_country SET numeric_code = '064' WHERE code_iso = 'BTN';
UPDATE llx_c_country SET numeric_code = '132' WHERE code_iso = 'CPV';
UPDATE llx_c_country SET numeric_code = '116' WHERE code_iso = 'KHM';
UPDATE llx_c_country SET numeric_code = '120' WHERE code_iso = 'CMR';
UPDATE llx_c_country SET numeric_code = '124' WHERE code_iso = 'CAN';
UPDATE llx_c_country SET numeric_code = '634' WHERE code_iso = 'QAT';
UPDATE llx_c_country SET numeric_code = '148' WHERE code_iso = 'TCD';
UPDATE llx_c_country SET numeric_code = '152' WHERE code_iso = 'CHL';
UPDATE llx_c_country SET numeric_code = '156' WHERE code_iso = 'CHN';
UPDATE llx_c_country SET numeric_code = '196' WHERE code_iso = 'CYP';
UPDATE llx_c_country SET numeric_code = '170' WHERE code_iso = 'COL';
UPDATE llx_c_country SET numeric_code = '174' WHERE code_iso = 'COM';
UPDATE llx_c_country SET numeric_code = '408' WHERE code_iso = 'PRK';
UPDATE llx_c_country SET numeric_code = '410' WHERE code_iso = 'KOR';
UPDATE llx_c_country SET numeric_code = '384' WHERE code_iso = 'CIV';
UPDATE llx_c_country SET numeric_code = '188' WHERE code_iso = 'CRI';
UPDATE llx_c_country SET numeric_code = '191' WHERE code_iso = 'HRV';
UPDATE llx_c_country SET numeric_code = '192' WHERE code_iso = 'CUB';
UPDATE llx_c_country SET numeric_code = '531' WHERE code_iso = 'CUW';
UPDATE llx_c_country SET numeric_code = '208' WHERE code_iso = 'DNK';
UPDATE llx_c_country SET numeric_code = '212' WHERE code_iso = 'DMA';
UPDATE llx_c_country SET numeric_code = '218' WHERE code_iso = 'ECU';
UPDATE llx_c_country SET numeric_code = '818' WHERE code_iso = 'EGY';
UPDATE llx_c_country SET numeric_code = '222' WHERE code_iso = 'SLV';
UPDATE llx_c_country SET numeric_code = '784' WHERE code_iso = 'ARE';
UPDATE llx_c_country SET numeric_code = '232' WHERE code_iso = 'ERI';
UPDATE llx_c_country SET numeric_code = '703' WHERE code_iso = 'SVK';
UPDATE llx_c_country SET numeric_code = '705' WHERE code_iso = 'SVN';
UPDATE llx_c_country SET numeric_code = '724' WHERE code_iso = 'ESP';
UPDATE llx_c_country SET numeric_code = '840' WHERE code_iso = 'USA';
UPDATE llx_c_country SET numeric_code = '233' WHERE code_iso = 'EST';
UPDATE llx_c_country SET numeric_code = '231' WHERE code_iso = 'ETH';
UPDATE llx_c_country SET numeric_code = '608' WHERE code_iso = 'PHL';
UPDATE llx_c_country SET numeric_code = '246' WHERE code_iso = 'FIN';
UPDATE llx_c_country SET numeric_code = '242' WHERE code_iso = 'FJI';
UPDATE llx_c_country SET numeric_code = '250' WHERE code_iso = 'FRA';
UPDATE llx_c_country SET numeric_code = '266' WHERE code_iso = 'GAB';
UPDATE llx_c_country SET numeric_code = '270' WHERE code_iso = 'GMB';
UPDATE llx_c_country SET numeric_code = '268' WHERE code_iso = 'GEO';
UPDATE llx_c_country SET numeric_code = '288' WHERE code_iso = 'GHA';
UPDATE llx_c_country SET numeric_code = '292' WHERE code_iso = 'GIB';
UPDATE llx_c_country SET numeric_code = '308' WHERE code_iso = 'GRD';
UPDATE llx_c_country SET numeric_code = '300' WHERE code_iso = 'GRC';
UPDATE llx_c_country SET numeric_code = '304' WHERE code_iso = 'GRL';
UPDATE llx_c_country SET numeric_code = '312' WHERE code_iso = 'GLP';
UPDATE llx_c_country SET numeric_code = '316' WHERE code_iso = 'GUM';
UPDATE llx_c_country SET numeric_code = '320' WHERE code_iso = 'GTM';
UPDATE llx_c_country SET numeric_code = '254' WHERE code_iso = 'GUF';
UPDATE llx_c_country SET numeric_code = '831' WHERE code_iso = 'GGY';
UPDATE llx_c_country SET numeric_code = '324' WHERE code_iso = 'GIN';
UPDATE llx_c_country SET numeric_code = '624' WHERE code_iso = 'GNB';
UPDATE llx_c_country SET numeric_code = '226' WHERE code_iso = 'GNQ';
UPDATE llx_c_country SET numeric_code = '328' WHERE code_iso = 'GUY';
UPDATE llx_c_country SET numeric_code = '332' WHERE code_iso = 'HTI';
UPDATE llx_c_country SET numeric_code = '340' WHERE code_iso = 'HND';
UPDATE llx_c_country SET numeric_code = '344' WHERE code_iso = 'HKG';
UPDATE llx_c_country SET numeric_code = '348' WHERE code_iso = 'HUN';
UPDATE llx_c_country SET numeric_code = '356' WHERE code_iso = 'IND';
UPDATE llx_c_country SET numeric_code = '360' WHERE code_iso = 'IDN';
UPDATE llx_c_country SET numeric_code = '368' WHERE code_iso = 'IRQ';
UPDATE llx_c_country SET numeric_code = '364' WHERE code_iso = 'IRN';
UPDATE llx_c_country SET numeric_code = '372' WHERE code_iso = 'IRL';
UPDATE llx_c_country SET numeric_code = '074' WHERE code_iso = 'BVT';
UPDATE llx_c_country SET numeric_code = '833' WHERE code_iso = 'IMN';
UPDATE llx_c_country SET numeric_code = '162' WHERE code_iso = 'CXR';
UPDATE llx_c_country SET numeric_code = '352' WHERE code_iso = 'ISL';
UPDATE llx_c_country SET numeric_code = '136' WHERE code_iso = 'CYM';
UPDATE llx_c_country SET numeric_code = '166' WHERE code_iso = 'CCK';
UPDATE llx_c_country SET numeric_code = '184' WHERE code_iso = 'COK';
UPDATE llx_c_country SET numeric_code = '234' WHERE code_iso = 'FRO';
UPDATE llx_c_country SET numeric_code = '239' WHERE code_iso = 'SGS';
UPDATE llx_c_country SET numeric_code = '334' WHERE code_iso = 'HMD';
UPDATE llx_c_country SET numeric_code = '238' WHERE code_iso = 'FLK';
UPDATE llx_c_country SET numeric_code = '580' WHERE code_iso = 'MNP';
UPDATE llx_c_country SET numeric_code = '584' WHERE code_iso = 'MHL';
UPDATE llx_c_country SET numeric_code = '612' WHERE code_iso = 'PCN';
UPDATE llx_c_country SET numeric_code = '090' WHERE code_iso = 'SLB';
UPDATE llx_c_country SET numeric_code = '796' WHERE code_iso = 'TCA';
UPDATE llx_c_country SET numeric_code = '581' WHERE code_iso = 'UMI';
UPDATE llx_c_country SET numeric_code = '092' WHERE code_iso = 'VGB';
UPDATE llx_c_country SET numeric_code = '850' WHERE code_iso = 'VIR';
UPDATE llx_c_country SET numeric_code = '376' WHERE code_iso = 'ISR';
UPDATE llx_c_country SET numeric_code = '380' WHERE code_iso = 'ITA';
UPDATE llx_c_country SET numeric_code = '388' WHERE code_iso = 'JAM';
UPDATE llx_c_country SET numeric_code = '392' WHERE code_iso = 'JPN';
UPDATE llx_c_country SET numeric_code = '832' WHERE code_iso = 'JEY';
UPDATE llx_c_country SET numeric_code = '400' WHERE code_iso = 'JOR';
UPDATE llx_c_country SET numeric_code = '398' WHERE code_iso = 'KAZ';
UPDATE llx_c_country SET numeric_code = '404' WHERE code_iso = 'KEN';
UPDATE llx_c_country SET numeric_code = '417' WHERE code_iso = 'KGZ';
UPDATE llx_c_country SET numeric_code = '296' WHERE code_iso = 'KIR';
UPDATE llx_c_country SET numeric_code = '414' WHERE code_iso = 'KWT';
UPDATE llx_c_country SET numeric_code = '418' WHERE code_iso = 'LAO';
UPDATE llx_c_country SET numeric_code = '426' WHERE code_iso = 'LSO';
UPDATE llx_c_country SET numeric_code = '428' WHERE code_iso = 'LVA';
UPDATE llx_c_country SET numeric_code = '422' WHERE code_iso = 'LBN';
UPDATE llx_c_country SET numeric_code = '430' WHERE code_iso = 'LBR';
UPDATE llx_c_country SET numeric_code = '434' WHERE code_iso = 'LBY';
UPDATE llx_c_country SET numeric_code = '438' WHERE code_iso = 'LIE';
UPDATE llx_c_country SET numeric_code = '440' WHERE code_iso = 'LTU';
UPDATE llx_c_country SET numeric_code = '442' WHERE code_iso = 'LUX';
UPDATE llx_c_country SET numeric_code = '446' WHERE code_iso = 'MAC';
UPDATE llx_c_country SET numeric_code = '807' WHERE code_iso = 'MKD';
UPDATE llx_c_country SET numeric_code = '450' WHERE code_iso = 'MDG';
UPDATE llx_c_country SET numeric_code = '458' WHERE code_iso = 'MYS';
UPDATE llx_c_country SET numeric_code = '454' WHERE code_iso = 'MWI';
UPDATE llx_c_country SET numeric_code = '462' WHERE code_iso = 'MDV';
UPDATE llx_c_country SET numeric_code = '466' WHERE code_iso = 'MLI';
UPDATE llx_c_country SET numeric_code = '470' WHERE code_iso = 'MLT';
UPDATE llx_c_country SET numeric_code = '504' WHERE code_iso = 'MAR';
UPDATE llx_c_country SET numeric_code = '474' WHERE code_iso = 'MTQ';
UPDATE llx_c_country SET numeric_code = '480' WHERE code_iso = 'MUS';
UPDATE llx_c_country SET numeric_code = '478' WHERE code_iso = 'MRT';
UPDATE llx_c_country SET numeric_code = '175' WHERE code_iso = 'MYT';
UPDATE llx_c_country SET numeric_code = '484' WHERE code_iso = 'MEX';
UPDATE llx_c_country SET numeric_code = '583' WHERE code_iso = 'FSM';
UPDATE llx_c_country SET numeric_code = '498' WHERE code_iso = 'MDA';
UPDATE llx_c_country SET numeric_code = '492' WHERE code_iso = 'MCO';
UPDATE llx_c_country SET numeric_code = '496' WHERE code_iso = 'MNG';
UPDATE llx_c_country SET numeric_code = '499' WHERE code_iso = 'MNE';
UPDATE llx_c_country SET numeric_code = '500' WHERE code_iso = 'MSR';
UPDATE llx_c_country SET numeric_code = '508' WHERE code_iso = 'MOZ';
UPDATE llx_c_country SET numeric_code = '104' WHERE code_iso = 'MMR';
UPDATE llx_c_country SET numeric_code = '516' WHERE code_iso = 'NAM';
UPDATE llx_c_country SET numeric_code = '520' WHERE code_iso = 'NRU';
UPDATE llx_c_country SET numeric_code = '524' WHERE code_iso = 'NPL';
UPDATE llx_c_country SET numeric_code = '558' WHERE code_iso = 'NIC';
UPDATE llx_c_country SET numeric_code = '562' WHERE code_iso = 'NER';
UPDATE llx_c_country SET numeric_code = '566' WHERE code_iso = 'NGA';
UPDATE llx_c_country SET numeric_code = '570' WHERE code_iso = 'NIU';
UPDATE llx_c_country SET numeric_code = '574' WHERE code_iso = 'NFK';
UPDATE llx_c_country SET numeric_code = '578' WHERE code_iso = 'NOR';
UPDATE llx_c_country SET numeric_code = '540' WHERE code_iso = 'NCL';
UPDATE llx_c_country SET numeric_code = '554' WHERE code_iso = 'NZL';
UPDATE llx_c_country SET numeric_code = '512' WHERE code_iso = 'OMN';
UPDATE llx_c_country SET numeric_code = '528' WHERE code_iso = 'NLD';
UPDATE llx_c_country SET numeric_code = '586' WHERE code_iso = 'PAK';
UPDATE llx_c_country SET numeric_code = '585' WHERE code_iso = 'PLW';
UPDATE llx_c_country SET numeric_code = '275' WHERE code_iso = 'PSE';
UPDATE llx_c_country SET numeric_code = '591' WHERE code_iso = 'PAN';
UPDATE llx_c_country SET numeric_code = '598' WHERE code_iso = 'PNG';
UPDATE llx_c_country SET numeric_code = '600' WHERE code_iso = 'PRY';
UPDATE llx_c_country SET numeric_code = '604' WHERE code_iso = 'PER';
UPDATE llx_c_country SET numeric_code = '258' WHERE code_iso = 'PYF';
UPDATE llx_c_country SET numeric_code = '616' WHERE code_iso = 'POL';
UPDATE llx_c_country SET numeric_code = '620' WHERE code_iso = 'PRT';
UPDATE llx_c_country SET numeric_code = '630' WHERE code_iso = 'PRI';
UPDATE llx_c_country SET numeric_code = '826' WHERE code_iso = 'GBR';
UPDATE llx_c_country SET numeric_code = '732' WHERE code_iso = 'ESH';
UPDATE llx_c_country SET numeric_code = '140' WHERE code_iso = 'CAF';
UPDATE llx_c_country SET numeric_code = '203' WHERE code_iso = 'CZE';
UPDATE llx_c_country SET numeric_code = '178' WHERE code_iso = 'COG';
UPDATE llx_c_country SET numeric_code = '180' WHERE code_iso = 'COD';
UPDATE llx_c_country SET numeric_code = '214' WHERE code_iso = 'DOM';
UPDATE llx_c_country SET numeric_code = '638' WHERE code_iso = 'REU';
UPDATE llx_c_country SET numeric_code = '646' WHERE code_iso = 'RWA';
UPDATE llx_c_country SET numeric_code = '642' WHERE code_iso = 'ROU';
UPDATE llx_c_country SET numeric_code = '643' WHERE code_iso = 'RUS';
UPDATE llx_c_country SET numeric_code = '882' WHERE code_iso = 'WSM';
UPDATE llx_c_country SET numeric_code = '016' WHERE code_iso = 'ASM';
UPDATE llx_c_country SET numeric_code = '652' WHERE code_iso = 'BLM';
UPDATE llx_c_country SET numeric_code = '659' WHERE code_iso = 'KNA';
UPDATE llx_c_country SET numeric_code = '674' WHERE code_iso = 'SMR';
UPDATE llx_c_country SET numeric_code = '663' WHERE code_iso = 'MAF';
UPDATE llx_c_country SET numeric_code = '666' WHERE code_iso = 'SPM';
UPDATE llx_c_country SET numeric_code = '670' WHERE code_iso = 'VCT';
UPDATE llx_c_country SET numeric_code = '654' WHERE code_iso = 'SHN';
UPDATE llx_c_country SET numeric_code = '662' WHERE code_iso = 'LCA';
UPDATE llx_c_country SET numeric_code = '678' WHERE code_iso = 'STP';
UPDATE llx_c_country SET numeric_code = '686' WHERE code_iso = 'SEN';
UPDATE llx_c_country SET numeric_code = '688' WHERE code_iso = 'SRB';
UPDATE llx_c_country SET numeric_code = '690' WHERE code_iso = 'SYC';
UPDATE llx_c_country SET numeric_code = '694' WHERE code_iso = 'SLE';
UPDATE llx_c_country SET numeric_code = '702' WHERE code_iso = 'SGP';
UPDATE llx_c_country SET numeric_code = '534' WHERE code_iso = 'SXM';
UPDATE llx_c_country SET numeric_code = '760' WHERE code_iso = 'SYR';
UPDATE llx_c_country SET numeric_code = '706' WHERE code_iso = 'SOM';
UPDATE llx_c_country SET numeric_code = '144' WHERE code_iso = 'LKA';
UPDATE llx_c_country SET numeric_code = '748' WHERE code_iso = 'SWZ';
UPDATE llx_c_country SET numeric_code = '710' WHERE code_iso = 'ZAF';
UPDATE llx_c_country SET numeric_code = '729' WHERE code_iso = 'SDN';
UPDATE llx_c_country SET numeric_code = '728' WHERE code_iso = 'SSD';
UPDATE llx_c_country SET numeric_code = '752' WHERE code_iso = 'SWE';
UPDATE llx_c_country SET numeric_code = '756' WHERE code_iso = 'CHE';
UPDATE llx_c_country SET numeric_code = '740' WHERE code_iso = 'SUR';
UPDATE llx_c_country SET numeric_code = '744' WHERE code_iso = 'SJM';
UPDATE llx_c_country SET numeric_code = '764' WHERE code_iso = 'THA';
UPDATE llx_c_country SET numeric_code = '158' WHERE code_iso = 'TWN';
UPDATE llx_c_country SET numeric_code = '834' WHERE code_iso = 'TZA';
UPDATE llx_c_country SET numeric_code = '762' WHERE code_iso = 'TJK';
UPDATE llx_c_country SET numeric_code = '086' WHERE code_iso = 'IOT';
UPDATE llx_c_country SET numeric_code = '260' WHERE code_iso = 'ATF';
UPDATE llx_c_country SET numeric_code = '626' WHERE code_iso = 'TLS';
UPDATE llx_c_country SET numeric_code = '768' WHERE code_iso = 'TGO';
UPDATE llx_c_country SET numeric_code = '772' WHERE code_iso = 'TKL';
UPDATE llx_c_country SET numeric_code = '776' WHERE code_iso = 'TON';
UPDATE llx_c_country SET numeric_code = '780' WHERE code_iso = 'TTO';
UPDATE llx_c_country SET numeric_code = '788' WHERE code_iso = 'TUN';
UPDATE llx_c_country SET numeric_code = '795' WHERE code_iso = 'TKM';
UPDATE llx_c_country SET numeric_code = '792' WHERE code_iso = 'TUR';
UPDATE llx_c_country SET numeric_code = '798' WHERE code_iso = 'TUV';
UPDATE llx_c_country SET numeric_code = '804' WHERE code_iso = 'UKR';
UPDATE llx_c_country SET numeric_code = '800' WHERE code_iso = 'UGA';
UPDATE llx_c_country SET numeric_code = '858' WHERE code_iso = 'URY';
UPDATE llx_c_country SET numeric_code = '860' WHERE code_iso = 'UZB';
UPDATE llx_c_country SET numeric_code = '548' WHERE code_iso = 'VUT';
UPDATE llx_c_country SET numeric_code = '336' WHERE code_iso = 'VAT';
UPDATE llx_c_country SET numeric_code = '862' WHERE code_iso = 'VEN';
UPDATE llx_c_country SET numeric_code = '704' WHERE code_iso = 'VNM';
UPDATE llx_c_country SET numeric_code = '876' WHERE code_iso = 'WLF';
UPDATE llx_c_country SET numeric_code = '887' WHERE code_iso = 'YEM';
UPDATE llx_c_country SET numeric_code = '262' WHERE code_iso = 'DJI';
UPDATE llx_c_country SET numeric_code = '894' WHERE code_iso = 'ZMB';
UPDATE llx_c_country SET numeric_code = '716' WHERE code_iso = 'ZWE';

-- Generate documents on product batch
ALTER TABLE llx_product_lot ADD COLUMN model_pdf varchar(255) AFTER scrapping_date;
ALTER TABLE llx_product_lot ADD COLUMN last_main_doc varchar(255) AFTER model_pdf;


ALTER TABLE llx_product_fournisseur_price ADD COLUMN status integer DEFAULT 1;

ALTER TABLE llx_product_fournisseur_price_log ADD INDEX idx_product_fournisseur_price_log_fk_product_fournisseur (fk_product_fournisseur);
ALTER TABLE llx_product_fournisseur_price_log ADD INDEX idx_product_fournisseur_price_log_fk_user (fk_user);
--ALTER TABLE llx_product_fournisseur_price_log ADD INDEX idx_product_fournisseur_price_log_fk_multicurrency (fk_multicurrency);

ALTER TABLE llx_bordereau_cheque ADD COLUMN label varchar(255) AFTER ref;

ALTER TABLE llx_societe ADD COLUMN vat_reverse_charge tinyint DEFAULT 0 AFTER tva_assuj;
ALTER TABLE llx_facture_fourn ADD COLUMN vat_reverse_charge tinyint DEFAULT 0 AFTER close_note;

ALTER TABLE llx_c_email_templates add COLUMN defaultfortype smallint DEFAULT 0;

ALTER TABLE llx_mailing ADD COLUMN fk_user_modif integer AFTER fk_user_creat;
ALTER TABLE llx_mailing ADD COLUMN evenunsubscribe smallint DEFAULT 0;
ALTER TABLE llx_mailing ADD COLUMN name_from varchar(128) AFTER email_from;

ALTER TABLE llx_bom_bomline ADD COLUMN fk_default_workstation integer DEFAULT NULL;
ALTER TABLE llx_mrp_production ADD COLUMN fk_default_workstation integer DEFAULT NULL;

ALTER TABLE llx_facture ADD COLUMN subtype smallint DEFAULT NULL;

CREATE TABLE llx_c_invoice_subtype (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  entity integer DEFAULT 1,
  fk_country integer NOT NULL,
  code varchar(4) NOT NULL,
  label varchar(100),
  active tinyint DEFAULT 1 NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_c_invoice_subtype MODIFY COLUMN code varchar(4);
ALTER TABLE llx_c_invoice_subtype ADD UNIQUE INDEX uk_c_invoice_subtype (entity, code);

ALTER TABLE llx_projet ADD COLUMN fk_project integer DEFAULT NULL;

-- Upgrade default PDF models to the 'new' ones (eproved since 4 dolibarr versions from now)
--UPDATE llx_const SET value='eratosthene' WHERE name='COMMANDE_ADDON_PDF' and value='einstein';
--UPDATE llx_const SET value='sponge' WHERE name='FACTURE_ADDON_PDF' and value='crabe';
--UPDATE llx_const SET value='espadon' WHERE name='EXPEDITION_ADDON_PDF' and value='merou';
--UPDATE llx_const SET value='cyan' WHERE name='PROPALE_ADDON_PDF' and value='azur';
--UPDATE llx_const SET value='storm' WHERE name IN ('DELIVERY_ADDON_PDF','LIVRAISON_ADDON_PDF') and value='typhon';
--UPDATE llx_const SET value='cornas' WHERE name='COMMANDE_SUPPLIER_ADDON_PDF' and value='muscadet';


ALTER TABLE llx_c_propalst ADD COLUMN sortorder smallint DEFAULT 0;
ALTER TABLE llx_c_stcomm ADD COLUMN sortorder smallint DEFAULT 0;

ALTER TABLE llx_element_time ADD COLUMN ref_ext varchar(32);

ALTER TABLE llx_c_ziptown ADD COLUMN town_up varchar(180);


-- Email Collector
ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN imap_encryption varchar(16) DEFAULT 'ssl' AFTER hostcharset;
ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN norsh integer DEFAULT 0 AFTER imap_encryption;

insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '1.1', 'Τιμολόγιο Πώλησης', 1);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '1.2', 'Τιμολόγιο Πώλησης / Ενδοκοινοτικές Παραδόσεις', 1);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '1.3', 'Τιμολόγιο Πώλησης / Παραδόσεις Τρίτων Χωρών', 1);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '1.4', 'Τιμολόγιο Πώλησης / Πώληση για Λογαριασμό Τρίτων', 0);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '1.5', 'Τιμολόγιο Πώλησης / Εκκαθάριση Πωλήσεων Τρίτων - Αμοιβή από Πωλήσεις Τρίτων', 0);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '1.6', 'Τιμολόγιο Πώλησης / Συμπληρωματικό Παραστατικό', 0);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '2.1', 'Τιμολόγιο Παροχής', 1);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '2.2', 'Τιμολόγιο Παροχής / Ενδοκοινοτική Παροχή Υπηρεσιών', 1);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '2.3', 'Τιμολόγιο Παροχής / Παροχή Υπηρεσιών σε λήπτη Τρίτης Χώρας', 1);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '2.4', 'Τιμολόγιο Παροχής / Συμπληρωματικό Παραστατικό', 0);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '3.1', 'Τίτλος Κτήσης (μη υπόχρεος Εκδότης)', 0);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '3.2', 'Τίτλος Κτήσης (άρνηση έκδοσης από υπόχρεο Εκδότη)', 0);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '6.1', 'Στοιχείο Αυτοπαράδοσης', 0);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '6.2', 'Στοιχείο Ιδιοχρησιμοποίησης', 0);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '7.1', 'Συμβόλαιο - Έσοδο', 0);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '8.1', 'Ενοίκια - Έσοδο', 0);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '8.2', 'Ειδικό Στοιχείο – Απόδειξης Είσπραξης Φόρου Διαμονής', 0);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '11.1', 'ΑΛΠ', 1);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '11.2', 'ΑΠΥ', 1);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '11.3', 'Απλοποιημένο Τιμολόγιο', 0);
insert into llx_c_invoice_subtype (entity, fk_country, code, label, active) VALUES (1, 102, '11.5', 'Απόδειξη Λιανικής Πώλησης για Λογ/σμό Τρίτων', 0);

ALTER TABLE llx_partnership ADD COLUMN email_partnership varchar(64) after fk_member;

ALTER TABLE llx_contratdet ADD INDEX idx_contratdet_statut (statut);

ALTER TABLE fk_product_price_product DROP FOREIGN KEY fk_product_price_product;

ALTER TABLE llx_societe_rib ADD COLUMN ext_payment_site varchar(128);

-- Drop the composite unique index that exists on llx_commande_fournisseur to rebuild a new one without the fk_soc.
-- The old design allowed for a duplicate reference as long as fk_soc was not the same.
-- VMYSQL4.1 DROP INDEX uk_commande_fournisseur_ref on llx_commande_fournisseur;
-- VPGSQL8.2 DROP INDEX uk_commande_fournisseur_ref;
ALTER TABLE llx_commande_fournisseur ADD UNIQUE INDEX uk_commande_fournisseur_ref (ref, entity);

-- Drop the composite unique index that exists on llx_actioncomm to rebuild a new one without unique feature.
-- The old design introduced a deadlock over traffic intense Dolibarr instance.
-- VMYSQL4.1 DROP INDEX uk_actioncomm_ref on llx_actioncomm;
-- VPGSQL8.2 DROP INDEX uk_actioncomm_ref;
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_ref (ref, entity);

-- Bump llx_reception.ref_supplier to allow up to 255 characters to match llx_commande_fournisseur.ref_supplier.
-- See: https://github.com/Dolibarr/dolibarr/pull/25034
ALTER TABLE llx_reception MODIFY COLUMN ref_supplier varchar(255);

insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 102, 10201, 'Αναλυτική Περιοδική Δήλωση (ΑΠΔ)', 1, 1, 'ΑΠΔ');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 102, 10202, 'Φόρος Μισθωτών Υπηρεσιών (ΦΜΥ)', 1, 1, 'ΦΜΥ');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 102, 10203, 'Ασφαλιστικές εισφορές (ΕΦΚΑ)', 1, 1, 'ΕΦΚΑ');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 102, 10204, 'Προκαταβολή Φόρου Εισοδήματος', 0, 1, 'ΕΦΟΡΙΑ');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 102, 10205, 'Ενιαίος Φόρος Ιδιοκτησίας Ακινήτων (ΕΝ.Φ.Ι.Α) ', 0, 1, 'ΕΝΦΙΑ');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 102, 10206, 'Ετήσιο τέλος διατήρησης Μερίδας στο
Γ.Ε.ΜΗ.', 1, 1, 'ΓΕΜΗ');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 102, 10207, 'Επαγγελματικό Επιμελητήριο', 1, 1, 'ΕΕ');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 102, 10208, 'Εμπορικό και Βιομηχανικό Επιμελητηρίο', 1, 1, 'ΕΒΕ');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 102, 10209, 'Τέλη Κυκλοφορίας', 1, 1,'ΤΕΛΗ');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 102, 10210, 'Ασφάλιση οχήματος', 1, 1,'ΑΣΦΑΛΕΙΑ');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 102, 10211, 'Ενοίκιο', 1, 1,'ΕΝΟΙΚΙΟ');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 102, 10212, 'Κοινόχρηστα', 1, 1, 'ΚΟΙΝΟ');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 102, 10213, 'Ηλεκτροδότηση', 1, 1, 'ΡΕΥΜΑ');

-- Leaves specific to Greece - info from https://www.kepea.gr/
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('5D1Y', 'Κανονική άδεια(Πενθήμερο 1ο έτος)', 1,  0, 1.667, 102, 6, 1);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('5D2Y', 'Κανονική άδεια(Πενθήμερο 2ο έτος)', 1,  0, 1.75, 102, 7, 1);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('5D3-10Y', 'Κανονική άδεια(Πενθήμερο 3ο έως 10ο έτος)', 1, 0, 1.833, 102, 8, 1);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('5D10-25Y', 'Κανονική άδεια(Πενθήμερο 10ο έως 25ο έτος)', 1, 0, 2.083,    102, 9, 1);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('5D25+Y', 'Κανονική άδεια(Πενθήμερο 25+ έτη)', 1, 0, 2.166,    102, 10, 1);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('6D1Y', 'Κανονική άδεια(Εξαήμερο 1ο έτος)', 1, 0, 2.00,    102, 11, 1);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('6D2Y', 'Κανονική άδεια(Εξαήμερο 2ο έτος)', 1, 0, 2.083,    102, 12, 1);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('6D3-10Y', 'Κανονική άδεια(Εξαήμερο 3ο έως 10ο έτος)', 1, 0, 2.166,    102, 13, 1);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('6D10-25Y', 'Κανονική άδεια(Εξαήμερο 10ο έως 25ο έτος)', 1, 0, 2.083,    102, 14, 1);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('6D25+Y', 'Κανονική άδεια(Εξαήμερο 25+ έτη)', 1, 0, 2.166,    102, 15, 1);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('5D-WED', 'Πενθήμερη άδεια γάμου(με αποδοχές)', 0, 0, 0, 102, 16, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('6D-WED', 'Εξαήμερη άδεια γάμου(με αποδοχές)', 0, 0, 0, 102, 17, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('7D-AR', 'Επταήμερη άδεια ιατρικώς υποβοηθούμενης αναπαραγωγής(με αποδοχές)', 0, 0, 0, 102, 18, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('1D-BC', 'Μονοήμερη άδεια προγεννητικών εξετάσεων(με αποδοχές)', 0, 0, 0, 102, 19, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('1D-GYN', 'Μονοήμερη άδεια γυναικολογικού ελέγχου(με αποδοχές)', 0,  0, 0, 102, 20, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('149D-ML', 'Άδεια Μητρότητας (Άδεια κύησης – λοχείας)56 ημέρες πριν-93 ημέρες μετα(με αποδοχές)', 0,  0, 0,    102, 21, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('14D-PL', '14ήμερη Άδεια πατρότητας(με αποδοχές)', 0,  0, 0, 102, 22, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('1-2H-CC', 'Άδεια φροντίδας παιδιών (μειωμένο ωράριο  https://www.kepea.gr/aarticle.php?id=1984)', 0,  0, 0, 102, 23, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('9M-M', 'Ειδική άδεια προστασίας μητρότητας 9 μηνών(χωρίς αποδοχές)', 0,  0, 0, 102, 24, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('4M-M', 'Τετράμηνη γονική Άδεια Ανατροφής Τέκνων(χωρίς αποδοχές)', 0,  0, 0, 102, 25, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('6-8D-SP', 'Εξαήμερη ή Οκταήμερη Άδεια για μονογονεϊκές οικογένειες(με αποδοχές)', 0, 0, 0, 102, 26, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('6-8-14D-FC', 'Άδεια για ασθένεια μελών οικογένειας(χωρίς αποδοχές, 6 ημέρες/έτος ένα παιδί - 8 ημέρες/έτος δύο παιδιά και σε 14 ημέρες/έτος τρία (3) παιδιά και πάνω', 0, 0, 0, 102, 27, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('10D-CD', 'Δεκαήμερη Γονική Άδεια για παιδί με σοβαρά νοσήματα και λόγω νοσηλείας παιδιών(με αποδοχές)', 0, 0, 0, 102, 28, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('30D-CD', 'Άδεια λόγω νοσηλείας των παιδιών(έως 30 ημέρες/έτος χωρίς αποδοχές)', 0, 0, 0, 102, 29, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('5D-CG', 'Άδεια φροντιστή(έως 5 ημέρες/έτος χωρίς αποδοχές)', 0, 0, 0, 102, 30, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('2D-CG', 'Άδεια απουσίας από την εργασία για λόγους ανωτέρας βίας(έως 2 ημέρες/έτος με αποδοχές)', 0,  0, 0, 102, 31, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('2D-SC', 'Άδεια για παρακολούθηση σχολικής επίδοσης(έως 2 ημέρες/έτος με αποδοχές)', 0, 0, 0, 102, 32, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('1D-BD', 'Μονοήμερη άδεια αιμοδοσίας(με αποδοχές)', 0,  0, 0, 102, 33, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('22D-BT', 'Άδεια για μετάγγιση αίματος & αιμοκάθαρση(έως 22 ημέρες/έτος με αποδοχές)', 0, 0, 0, 102, 34, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('30D-HIV', 'Άδεια λόγω AIDS(έως ένα (1) μήνα/έτος με αποδοχές)', 0, 0, 0, 102, 35, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('20D-CD', 'Άδεια πενθούντων γονέων(20 ημέρες με αποδοχές)', 0, 0, 0, 102, 36, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('2D-FD', 'Άδεια λόγω θανάτου συγγενούς(2 ημέρες με αποδοχές)', 0, 0, 0, 102, 37, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('DIS', 'Άδειες αναπήρων(30 ημέρες με αποδοχές)', 0, 0, 0, 102, 38, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('SE', 'Άδεια εξετάσεων μαθητών, σπουδαστών, φοιτητών(30 ημέρες χωρίς αποδοχές)', 0, 0, 0, 102, 39, 0);
insert into llx_c_holiday_types(code, label, affect, delay, newbymonth, fk_country, sortorder, active) values ('NOT PAID', 'Άδεια άνευ αποδοχών(έως ένα (1) έτος)', 0, 0, 0, 102, 40, 0);

-- hrm triggers
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HRM_EVALUATION_CREATE', 'HR Evaluation created', 'Executed when an evaluation is created', 'hrm', 4000);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HRM_EVALUATION_MODIFY', 'HR Evaluation modified', 'Executed when an evaluation is modified', 'hrm', 4001);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HRM_EVALUATION_VALIDATE', 'HR Evaluation validated', 'Executed when an evaluation is validated', 'hrm', 4002);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HRM_EVALUATION_UNVALIDATE', 'HR Evaluation back to draft', 'Executed when an evaluation is back to draft', 'hrm', 4003);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HRM_EVALUATION_DELETE', 'HR Evaluation deleted', 'Executed when an evaluation is dleted', 'hrm', 4005);

UPDATE llx_menu SET url = '/fourn/paiement/list.php?mainmenu=billing&leftmenu=suppliers_bills_payment' WHERE leftmenu = 'suppliers_bills_payment';

UPDATE llx_paiement SET ref = rowid WHERE ref IS NULL OR ref = '';

-- rename const WORKFLOW_EXPEDITION_CLASSIFY_CLOSED_INVOICE to WORKFLOW_RECEPTION_CLASSIFY_CLOSED_INVOICE
UPDATE llx_const SET name = 'WORKFLOW_RECEPTION_CLASSIFY_CLOSED_INVOICE' WHERE name = 'WORKFLOW_EXPEDITION_CLASSIFY_CLOSED_INVOICE';
