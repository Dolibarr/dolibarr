--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 11.0.0 or higher.
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


-- Missing in v10
ALTER TABLE llx_account_bookkeeping ADD COLUMN date_export datetime DEFAULT NULL;
ALTER TABLE llx_expensereport ADD COLUMN paid smallint default 0 NOT NULL;
UPDATE llx_expensereport set paid = 1 WHERE fk_statut = 6 and paid = 0;


-- For v11

create table llx_holiday_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_holiday_extrafields ADD INDEX idx_holiday_extrafields (fk_object);

ALTER TABLE llx_societe_rib MODIFY label varchar(200);

insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('USER_SENTBYMAIL','Email sent','Executed when an email is sent from user card','user',300);

create table llx_entrepot_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_entrepot_extrafields ADD INDEX idx_entrepot_extrafields (fk_object);

ALTER TABLE llx_extrafields ADD COLUMN printable boolean DEFAULT FALSE;

ALTER TABLE llx_facture ADD COLUMN retained_warranty real DEFAULT NULL after situation_final;
ALTER TABLE llx_facture ADD COLUMN retained_warranty_date_limit	date DEFAULT NULL after retained_warranty;
ALTER TABLE llx_facture ADD COLUMN retained_warranty_fk_cond_reglement	integer  DEFAULT NULL after retained_warranty_date_limit;


ALTER TABLE llx_c_shipment_mode ADD COLUMN entity integer DEFAULT 1 NOT NULL;

ALTER TABLE llx_c_shipment_mode DROP INDEX uk_c_shipment_mode;
ALTER TABLE llx_c_shipment_mode ADD UNIQUE INDEX uk_c_shipment_mode (code, entity);

ALTER TABLE llx_facture_fourn DROP COLUMN total;

ALTER TABLE llx_user ADD COLUMN iplastlogin         varchar(250);
ALTER TABLE llx_user ADD COLUMN ippreviouslogin     varchar(250);

ALTER TABLE llx_events ADD COLUMN prefix_session varchar(255) NULL;

create table llx_payment_salary_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- salary payment id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_payment_salary_extrafields ADD INDEX idx_payment_salary_extrafields (fk_object);

ALTER TABLE llx_c_price_expression MODIFY COLUMN expression varchar(255) NOT NULL;

UPDATE llx_bank_url set url = REPLACE( url, 'compta/salaries/', 'salaries/');

ALTER TABLE llx_stock_mouvement ADD COLUMN fk_projet INTEGER NOT NULL DEFAULT 0 AFTER model_pdf;

ALTER TABLE llx_oauth_token ADD COLUMN fk_soc integer DEFAULT NULL after token;

ALTER TABLE llx_mailing ADD COLUMN tms timestamp;
ALTER TABLE llx_mailing_cibles ADD COLUMN tms timestamp;

ALTER TABLE llx_projet ADD COLUMN usage_opportunity integer DEFAULT 0;
ALTER TABLE llx_projet ADD COLUMN usage_task integer DEFAULT 1;
ALTER TABLE llx_projet CHANGE COLUMN bill_time usage_bill_time integer DEFAULT 0;		-- rename existing field
ALTER TABLE llx_projet ADD COLUMN usage_organize_event integer DEFAULT 0;

UPDATE llx_projet set usage_opportunity = 1 WHERE fk_opp_status > 0;

ALTER TABLE llx_accounting_account MODIFY COLUMN rowid bigint AUTO_INCREMENT;


ALTER TABLE llx_supplier_proposaldet ADD COLUMN  date_start	datetime   DEFAULT NULL;
ALTER TABLE llx_supplier_proposaldet ADD COLUMN  date_end	datetime   DEFAULT NULL;
  

create table llx_c_hrm_public_holiday
(
  id					integer AUTO_INCREMENT PRIMARY KEY,
  entity				integer	DEFAULT 0 NOT NULL,	-- multi company id, 0 = all
  fk_country			integer,			
  code		    		varchar(62),
  dayrule               varchar(64) DEFAULT '', 	-- 'easter', 'eastermonday', ...
  day					integer,
  month					integer,
  year					integer,					-- 0 for all years
  active				integer DEFAULT 1,
  import_key			varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_c_hrm_public_holiday ADD UNIQUE INDEX uk_c_hrm_public_holiday(entity, code);
ALTER TABLE llx_c_hrm_public_holiday ADD UNIQUE INDEX uk_c_hrm_public_holiday2(entity, fk_country, dayrule, day, month, year);


-- A lot of countries
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('NEWYEARDAY1',    0, 0, 0,  1,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('LABORDAY1',      0, 0, 0,  5,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('ASSOMPTIONDAY1', 0, 0, 0,  8, 15, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('CHRISTMASDAY1',  0, 0, 0, 12, 25, 1);

-- France only (1)
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-VICTORYDAY',  0, 1, '', 0,  5,  8, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-NATIONALDAY', 0, 1, '', 0,  7, 14, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-ASSOMPTION',  0, 1, '', 0,  8, 15, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-TOUSSAINT',   0, 1, '', 0, 11,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-ARMISTICE',   0, 1, '', 0, 11, 11, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-EASTER',      0, 1, 'eastermonday', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-ASCENSION',   0, 1, 'ascension', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-PENTECOST',   0, 1, 'pentecost', 0, 0, 0, 1);

-- Italy (3)
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('IT-LIBEAZIONE',     0, 3, 0,  4, 25, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('IT-EPIPHANY',       0, 3, 0,  6,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('IT-REPUBBLICA',     0, 3, 0,  6,  2, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('IT-TUTTISANTIT',    0, 3, 0, 11,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('IT-IMMACULE',       0, 3, 0, 12,  8, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('IT-SAINTSTEFAN',    0, 3, 0, 12, 26, 1);

-- Spain (4)
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('ES-EASTER',        0, 4, 'easter', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('ES-REYE',          0, 4,       '', 0,  6,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('ES-HISPANIDAD',    0, 4,       '', 0, 10, 12, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('ES-TOUSSAINT',     0, 4,       '', 0, 11,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('ES-CONSTITUIZION', 0, 4,       '', 0, 12,  6, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('ES-IMMACULE',      0, 4,       '', 0, 12,  8, 1);

-- Austria (41)
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-EASTER',       0, 41, 'eastermonday', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-ASCENSION',    0, 41,    'ascension', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-PENTECOST',    0, 41,    'pentecost', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-FRONLEICHNAM', 0, 41, 'fronleichnam', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-KONEGIE',      0, 41,             '', 0,  6,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-26OKT',        0, 41,             '', 0, 10, 26, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-TOUSSAINT',    0, 41,             '', 0, 11,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-IMMACULE',     0, 41,             '', 0, 12,  8, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-24DEC',        0, 41,             '', 0, 12, 24, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-SAINTSTEFAN',  0, 41,             '', 0, 12, 26, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-Silvester',    0, 41,             '', 0, 12, 31, 1);

-- India (117)
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('IN-REPUBLICDAY',  0, 117, '', 0,  1, 26, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('IN-GANDI',        0, 117, '', 0, 10,  2, 1);


