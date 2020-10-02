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

UPDATE llx_c_units SET short_label = 'i' WHERE code = 'MI';
UPDATE llx_c_units SET unit_type = 'weight', short_label = 'kg', scale = 0 WHERE code = 'KG';
UPDATE llx_c_units SET unit_type = 'weight', short_label = 'g', scale = -3 WHERE code = 'G';
UPDATE llx_c_units SET unit_type = 'time' WHERE code IN ('S','H','D');
UPDATE llx_c_units SET unit_type = 'size' WHERE code IN ('M','LM');
UPDATE llx_c_units SET label = 'SizeUnitm', scale = 0 WHERE code IN ('M');
UPDATE llx_c_units SET active = 0, scale = 0 WHERE code IN ('LM');
UPDATE llx_c_units SET unit_type = 'surface', scale = 0 WHERE code IN ('M2');
UPDATE llx_c_units SET unit_type = 'volume', scale = 0 WHERE code IN ('M3','L');
UPDATE llx_c_units SET scale = -3, active = 0 WHERE code IN ('L');
UPDATE llx_c_units SET label = 'VolumeUnitm3' WHERE code IN ('M3');
UPDATE llx_c_units SET label = 'SurfaceUnitm2' WHERE code IN ('M2');

ALTER TABLE llx_adherent_type ADD UNIQUE INDEX uk_adherent_type_libelle (libelle, entity);

ALTER TABLE llx_mailing_cibles MODIFY COLUMN lastname varchar(160);
ALTER TABLE llx_mailing_cibles MODIFY COLUMN firstname varchar(160);

ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN login varchar(128);
ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN codelastresult varchar(16);
ALTER TABLE llx_emailcollector_emailcollectoraction ADD COLUMN position integer DEFAULT 0;



-- For v11


ALTER TABLE llx_product_price MODIFY COLUMN tva_tx double(6,3) DEFAULT 0 NOT NULL;

ALTER TABLE llx_facturedet MODIFY COLUMN situation_percent real DEFAULT 100;
UPDATE llx_facturedet SET situation_percent = 100 WHERE situation_percent IS NULL AND fk_prev_id IS NULL;

-- Set country to null for deprecated accounting system (there is now one per country)
UPDATE llx_accounting_system SET fk_country = NULL, active = 0 WHERE pcg_version = 'SYSCOHADA';
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 20, 'BAS-K1-MINI', 'The Swedish mini chart of accounts', 1);

ALTER TABLE llx_c_action_trigger MODIFY COLUMN elementtype varchar(64) NOT NULL;

ALTER TABLE llx_societe_account ADD COLUMN site_account varchar(128);

UPDATE llx_holiday SET ref = rowid WHERE ref IS NULL;
-- VMYSQL4.3 ALTER TABLE llx_holiday MODIFY COLUMN ref varchar(30) NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_holiday ALTER COLUMN ref SET NOT NULL;

ALTER TABLE llx_c_email_senderprofile MODIFY COLUMN active tinyint DEFAULT 1 NOT NULL;

insert into llx_c_type_container (code,label,module,active) values ('menu',     'Menu',     'system', 1);

INSERT INTO llx_c_ticket_type (code, pos, label, active, use_default, description) VALUES('HELP',    '15', 'Request for functionnal help',  1, 0, NULL);

ALTER TABLE llx_don ADD COLUMN fk_user_modif integer;

ALTER TABLE llx_expeditiondet ADD INDEX idx_expeditiondet_fk_origin_line (fk_origin_line);

ALTER TABLE llx_rights_def ADD COLUMN module_position INTEGER NOT NULL DEFAULT 0;
ALTER TABLE llx_rights_def ADD COLUMN family_position INTEGER NOT NULL DEFAULT 0;

UPDATE llx_rights_def SET subperms = 'write' WHERE perms = 'fiscalyear' AND module = 'accounting' AND subperms IS NULL;

ALTER TABLE llx_bom_bom ADD COLUMN duration double(24,8) DEFAULT NULL;
ALTER TABLE llx_bom_bom ADD COLUMN fk_warehouse integer;
ALTER TABLE llx_bom_bomline ADD COLUMN position integer NOT NULL DEFAULT 0;
ALTER TABLE llx_bom_bomline ADD COLUMN qty_frozen smallint DEFAULT 0;
ALTER TABLE llx_bom_bomline ADD COLUMN disable_stock_change smallint DEFAULT 0;

-- VMYSQL4.1 ALTER TABLE llx_bom_bomline DROP COLUMN `rank`;
-- VPGSQL8.2 ALTER TABLE llx_bom_bomline DROP COLUMN rank;

create table llx_categorie_warehouse
(
  fk_categorie  integer NOT NULL,
  fk_warehouse  integer NOT NULL,
  import_key    varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_categorie_warehouse ADD PRIMARY KEY pk_categorie_warehouse (fk_categorie, fk_warehouse);
ALTER TABLE llx_categorie_warehouse ADD INDEX idx_categorie_warehouse_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_warehouse ADD INDEX idx_categorie_warehouse_fk_warehouse (fk_warehouse);

ALTER TABLE llx_categorie_warehouse ADD CONSTRAINT fk_categorie_warehouse_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_warehouse ADD CONSTRAINT fk_categorie_warehouse_fk_warehouse_rowid FOREIGN KEY (fk_warehouse) REFERENCES llx_entrepot (rowid);


create table llx_holiday_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_holiday_extrafields ADD INDEX idx_holiday_extrafields (fk_object);

ALTER TABLE llx_societe_rib MODIFY label varchar(200);

ALTER TABLE llx_societe ADD COLUMN logo_squarred varchar(255);

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
ALTER TABLE llx_facture ADD COLUMN date_closing datetime DEFAULT NULL after date_valid;
ALTER TABLE llx_facture ADD COLUMN fk_user_closing integer DEFAULT NULL after fk_user_valid;

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


ALTER TABLE llx_adherent_type ADD COLUMN duration varchar(6) DEFAULT NULL after morphy;

ALTER TABLE llx_mailing ADD COLUMN tms timestamp;
ALTER TABLE llx_mailing_cibles ADD COLUMN tms timestamp;

ALTER TABLE llx_projet ADD COLUMN usage_opportunity integer DEFAULT 0;
ALTER TABLE llx_projet ADD COLUMN usage_task integer DEFAULT 1;
ALTER TABLE llx_projet CHANGE COLUMN bill_time usage_bill_time integer DEFAULT 0;		-- rename existing field
ALTER TABLE llx_projet ADD COLUMN usage_organize_event integer DEFAULT 0;

UPDATE llx_projet set usage_opportunity = 1 WHERE fk_opp_status > 0;

create table llx_societe_contacts
(
    rowid           integer AUTO_INCREMENT PRIMARY KEY,
    entity          integer DEFAULT 1 NOT NULL,
    date_creation           datetime NOT NULL,
    fk_soc		        integer NOT NULL,
    fk_c_type_contact	int NOT NULL,
    fk_socpeople        integer NOT NULL,
    tms TIMESTAMP,
    import_key VARCHAR(14)
)ENGINE=innodb;

ALTER TABLE llx_societe_contacts ADD UNIQUE INDEX idx_societe_contacts_idx1 (entity, fk_soc, fk_c_type_contact, fk_socpeople);
ALTER TABLE llx_societe_contacts ADD CONSTRAINT fk_societe_contacts_fk_c_type_contact FOREIGN KEY (fk_c_type_contact)  REFERENCES llx_c_type_contact(rowid);
ALTER TABLE llx_societe_contacts ADD CONSTRAINT fk_societe_contacts_fk_soc FOREIGN KEY (fk_soc)  REFERENCES llx_societe(rowid);
ALTER TABLE llx_societe_contacts ADD CONSTRAINT fk_societe_contacts_fk_socpeople FOREIGN KEY (fk_socpeople)  REFERENCES llx_socpeople(rowid);

-- VMYSQL4.3 ALTER TABLE llx_accounting_account MODIFY COLUMN rowid bigint AUTO_INCREMENT;
-- VPGSQL8.2 ALTER TABLE llx_accounting_account MODIFY COLUMN rowid bigint;



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


create table llx_c_socialnetworks
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  entity      integer DEFAULT 1 NOT NULL,
  code        varchar(100),
  label       varchar(150),
  url         text,
  icon        varchar(20),
  active      tinyint DEFAULT 1 NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_c_socialnetworks ADD UNIQUE INDEX idx_c_socialnetworks_code (code);

INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'facebook', 'Facebook', 'https://www.facebook.com/{socialid}', 'fa-facebook', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'skype', 'Skype', 'https://www.skype.com/{socialid}', 'fa-skype', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'twitter', 'Twitter', 'https://www.twitter.com/{socialid}', 'fa-twitter', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'linkedin', 'LinkedIn', 'https://www.linkedin.com/{socialid}', 'fa-linkedin', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'instagram', 'Instagram', 'https://www.instagram.com/{socialid}', 'fa-instagram', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'snapchat', 'Snapchat', '{socialid}', 'fa-snapchat', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'googleplus', 'GooglePlus', 'https://www.googleplus.com/{socialid}', 'fa-google-plus-g', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'youtube', 'Youtube', 'https://www.youtube.com/{socialid}', 'fa-youtube', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'whatsapp', 'Whatsapp', '{socialid}', 'fa-whatsapp', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'tumblr', 'Tumblr', 'https://www.tumblr.com/{socialid}', 'fa-tumblr', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'vero', 'Vero', 'https://vero.co/{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'viadeo', 'Viadeo', 'https://fr.viadeo.com/fr/{socialid}', 'fa-viadeo', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'slack', 'Slack', '{socialid}', 'fa-slack', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'xing', 'Xing', '{socialid}', 'fa-xing', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'meetup', 'Meetup', '{socialid}', 'fa-meetup', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'pinterest', 'Pinterest', '{socialid}', 'fa-pinterest', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'flickr', 'Flickr', '{socialid}', 'fa-flickr', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, '500px', '500px', '{socialid}', 'fa-500px', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'giphy', 'Giphy', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'gifycat', 'Gificat', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'dailymotion', 'Dailymotion', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'vimeo', 'Vimeo', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'periscope', 'Periscope', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'twitch', 'Twitch', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'discord', 'Discord', '{socialid}', 'fa-discord', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'wikipedia', 'Wikipedia', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'reddit', 'Reddit', '{socialid}', 'fa-reddit', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'quora', 'Quora', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'tripadvisor', 'Tripadvisor', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'mastodon', 'Mastodon', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'diaspora', 'Diaspora', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES(1, 'viber', 'Viber', '{socialid}', '', 0);

ALTER TABLE llx_adherent ADD COLUMN socialnetworks text DEFAULT NULL AFTER email;
ALTER TABLE llx_societe ADD COLUMN socialnetworks text DEFAULT NULL AFTER email;
ALTER TABLE llx_socpeople ADD COLUMN socialnetworks text DEFAULT NULL AFTER email;
ALTER TABLE llx_user ADD COLUMN socialnetworks text DEFAULT NULL AFTER personal_email;

ALTER TABLE llx_product ADD COLUMN net_measure         float;
ALTER TABLE llx_product ADD COLUMN net_measure_units     tinyint;

create table llx_adherent_type_lang
(
	rowid          integer AUTO_INCREMENT PRIMARY KEY,
	fk_type        integer      DEFAULT 0 NOT NULL,
	lang           varchar(5)   DEFAULT 0 NOT NULL,
	label          varchar(255) NOT NULL,
	description    text,
	email          text,
	import_key varchar(14) DEFAULT NULL
)ENGINE=innodb;

create table llx_fichinter_rec
(
	rowid				integer AUTO_INCREMENT PRIMARY KEY,
	titre				varchar(50) NOT NULL,
	entity				integer DEFAULT 1 NOT NULL,	 -- multi company id
	fk_soc				integer DEFAULT NULL,
	datec				datetime,  -- date de creation
	fk_contrat			integer DEFAULT 0,          -- contrat auquel est rattache la fiche
	fk_user_author		integer,                    -- createur
	fk_projet			integer,                    -- projet auquel est associe la facture
	duree				real,                       -- duree totale de l'intervention
	description			text,
	modelpdf			varchar(50),
	note_private		text,
	note_public			text,
	frequency			integer,					-- frequency (for example: 3 for every 3 month)
	unit_frequency		varchar(2) DEFAULT 'm',		-- 'm' for month (date_when must be a day <= 28), 'y' for year, ...
	date_when			datetime DEFAULT NULL,		-- date for next gen (when an invoice is generated, this field must be updated with next date)
	date_last_gen		datetime DEFAULT NULL,		-- date for last gen (date with last successfull generation of invoice)
	nb_gen_done			integer DEFAULT NULL,		-- nb of generation done (when an invoice is generated, this field must incremented)
	nb_gen_max			integer DEFAULT NULL,		-- maximum number of generation
	auto_validate		integer NULL DEFAULT NULL	-- statut of the generated intervention

)ENGINE=innodb;

ALTER TABLE llx_fichinter_rec ADD UNIQUE INDEX idx_fichinter_rec_uk_titre (titre, entity);
ALTER TABLE llx_fichinter_rec ADD INDEX idx_fichinter_rec_fk_soc (fk_soc);
ALTER TABLE llx_fichinter_rec ADD INDEX idx_fichinter_rec_fk_user_author (fk_user_author);
ALTER TABLE llx_fichinter_rec ADD INDEX idx_fichinter_rec_fk_projet (fk_projet);
ALTER TABLE llx_fichinter_rec ADD CONSTRAINT fk_fichinter_rec_fk_user_author    FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
ALTER TABLE llx_fichinter_rec ADD CONSTRAINT fk_fichinter_rec_fk_projet         FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);

create table llx_fichinterdet_rec
(
	rowid				integer AUTO_INCREMENT PRIMARY KEY,
	fk_fichinter		integer NOT NULL,
	date				datetime,				-- date de la ligne d'intervention
	description			text,					-- description de la ligne d'intervention
	duree				integer,				-- duree de la ligne d'intervention
	rang				integer DEFAULT 0,		-- ordre affichage sur la fiche
	total_ht			DOUBLE(24, 8) NULL DEFAULT NULL,
	subprice			DOUBLE(24, 8) NULL DEFAULT NULL,
	fk_parent_line		integer NULL DEFAULT NULL,
	fk_product			integer NULL DEFAULT NULL,
	label				varchar(255) NULL DEFAULT NULL,
	tva_tx				DOUBLE(6, 3) NULL DEFAULT NULL,
	localtax1_tx		DOUBLE(6, 3) NULL DEFAULT 0,
	localtax1_type		VARCHAR(1) NULL DEFAULT NULL,
	localtax2_tx		DOUBLE(6, 3) NULL DEFAULT 0,
	localtax2_type		VARCHAR(1) NULL DEFAULT NULL,
	qty					double NULL DEFAULT NULL,
	remise_percent		double NULL DEFAULT 0,
	remise				double NULL DEFAULT 0,
	fk_remise_except	integer NULL DEFAULT NULL,
	price				DOUBLE(24, 8) NULL DEFAULT NULL,
	total_tva			DOUBLE(24, 8) NULL DEFAULT NULL,
	total_localtax1		DOUBLE(24, 8) NULL DEFAULT 0,
	total_localtax2		DOUBLE(24, 8) NULL DEFAULT 0,
	total_ttc			DOUBLE(24, 8) NULL DEFAULT NULL,
	product_type		INTEGER NULL DEFAULT 0,
	date_start			datetime NULL DEFAULT NULL,
	date_end			datetime NULL DEFAULT NULL,
	info_bits			INTEGER NULL DEFAULT 0,
	buy_price_ht		DOUBLE(24, 8) NULL DEFAULT 0,
	fk_product_fournisseur_price	integer NULL DEFAULT NULL,
	fk_code_ventilation	integer NOT NULL DEFAULT 0,
	fk_export_commpta	integer NOT NULL DEFAULT 0,
	special_code		integer UNSIGNED NULL DEFAULT 0,
	fk_unit				integer NULL DEFAULT NULL,
	import_key			varchar(14) NULL DEFAULT NULL
)ENGINE=innodb;

ALTER TABLE llx_supplier_proposaldet ADD COLUMN date_start datetime DEFAULT NULL AFTER product_type;
ALTER TABLE llx_supplier_proposaldet ADD COLUMN date_end datetime DEFAULT NULL AFTER date_start;

--List of parcels details related to an expedition
create table llx_expedition_package
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_expedition     integer NOT NULL,
  description       varchar(255),    --Description of goods in the package (required by the custom)
  value             double(24,8)     DEFAULT 0,--Value (Price of the content, for insurance & custom)
  fk_parcel_type    integer,           -- Type or package, linked to llx_c_shipment_parcel_type (eg: 1=enveloppe, 2=package, 3=palette, 4=other)
  height            float,	       -- height
  width             float,	       -- width
  size              float,	       -- depth
  size_units        integer,	       -- unit of all sizes (height, width, depth)
  weight            float,	       -- weight
  weight_units      integer,	       -- unit of weight
  dangerous_goods   smallint          DEFAULT 0, -- 0 = no dangerous goods or 1 = Explosives, 2 = Flammable Gases, 3 = Flammable Liquids, 4 = Flammable solids, 5 = Oxidizing, 6 = Toxic & Infectious, 7 = Radioactive, 8 = Corrosives, 9 = Miscellaneous (see https://en.wikipedia.org/wiki/Dangerous_goods). I'm not sure if just register 0 (no) or 1 (yes) is enough.
  tail_lift         smallint          DEFAULT 0, -- 0 = no tail lift required to load/unload package(s), 1 = a tail lift is required to load/unload package(s). Sometime tail lift load can be different than tail lift delivery so maybe adding a new table line.
  rang              integer  DEFAULT 0
)ENGINE=innodb;

--Dictionary of package type
create table llx_c_shipment_package_type
(
    rowid        integer  AUTO_INCREMENT PRIMARY KEY,
    label        varchar(50) NOT NULL,  -- Short name
    description	 varchar(255), -- Description
    active       integer DEFAULT 1 NOT NULL, -- Active or not
    entity       integer DEFAULT 1 NOT NULL -- Multi company id
)ENGINE=innodb;

CREATE TABLE llx_product_fournisseur_price_extrafields (
	rowid               integer AUTO_INCREMENT PRIMARY KEY,
	tms                 timestamp,
	fk_object           integer NOT NULL,
	import_key          varchar(14) -- import key
) ENGINE=innodb;

ALTER TABLE llx_product_fournisseur_price_extrafields ADD INDEX idx_product_fournisseur_price_extrafields (fk_object);

CREATE TABLE llx_mrp_mo(
    -- BEGIN MODULEBUILDER FIELDS
    rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    ref varchar(128) DEFAULT '(PROV)' NOT NULL,
    entity integer DEFAULT 1 NOT NULL,
    label varchar(255),
    qty real NOT NULL,
    fk_warehouse integer,
    fk_soc integer,
    note_public text,
    note_private text,
    date_creation datetime NOT NULL,
    date_valid datetime NULL,
    tms timestamp,
    fk_user_creat integer NOT NULL,
    fk_user_modif integer,
    fk_user_valid integer,
    model_pdf varchar(255),
    import_key varchar(14),
    status integer NOT NULL,
    fk_product integer NOT NULL,
    date_start_planned datetime,
    date_end_planned datetime,
    fk_bom integer,
    fk_project integer
    -- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE llx_mrp_mo ADD COLUMN date_valid datetime NULL;
ALTER TABLE llx_mrp_mo ADD COLUMN fk_user_valid integer;

ALTER TABLE llx_bom_bom ADD COLUMN model_pdf varchar(255);
ALTER TABLE llx_mrp_mo ADD COLUMN model_pdf varchar(255);

ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_ref (ref);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_entity (entity);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_fk_soc (fk_soc);
ALTER TABLE llx_mrp_mo ADD CONSTRAINT fk_mrp_mo_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_status (status);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_fk_product (fk_product);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_date_start_planned (date_start_planned);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_date_end_planned (date_end_planned);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_fk_bom (fk_bom);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_fk_project (fk_project);


create table llx_mrp_mo_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                                 -- import key
) ENGINE=innodb;

ALTER TABLE llx_mrp_mo_extrafields ADD INDEX idx_fk_object(fk_object);


insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_VALIDATE','BOM validated','Executed when a BOM is validated','bom',650);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_UNVALIDATE','BOM unvalidated','Executed when a BOM is unvalidated','bom',651);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_CLOSE','BOM disabled','Executed when a BOM is disabled','bom',652);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_REOPEN','BOM reopen','Executed when a BOM is re-open','bom',653);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_DELETE','BOM deleted','Executed when a BOM deleted','bom',654);

DELETE FROM llx_c_action_trigger where code LIKE 'MO_%';
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MRP_MO_VALIDATE','MO validated','Executed when a MO is validated','bom',660);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MRP_MO_PRODUCED','MO produced','Executed when a MO is produced','bom',661);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MRP_MO_DELETE','MO deleted','Executed when a MO is deleted','bom',662);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MRP_MO_CANCEL','MO canceled','Executed when a MO is canceled','bom',663);

ALTER TABLE llx_comment ADD COLUMN fk_user_modif  integer DEFAULT NULL;


CREATE TABLE llx_mrp_production(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_mo integer NOT NULL,
	position integer NOT NULL DEFAULT 0,
	fk_product integer NOT NULL,
	fk_warehouse integer,
	qty real NOT NULL DEFAULT 1,
    qty_frozen smallint DEFAULT 0,
    disable_stock_change smallint DEFAULT 0,
	batch varchar(30),
	role varchar(10),      			-- 'toconsume' or 'toproduce' (initialized at MO creation), 'consumed' or 'produced' (added after MO validation)
	fk_mrp_production integer,		-- if role = 'consumed', id of line with role 'toconsume', if role = 'produced' id of line with role 'toproduce'
	fk_stock_movement integer,		-- id of stock movement when movements are validated
	date_creation datetime NOT NULL,
	tms timestamp,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	import_key varchar(14)
) ENGINE=innodb;
ALTER TABLE llx_mrp_production MODIFY COLUMN qty real NOT NULL DEFAULT 1;

ALTER TABLE llx_mrp_production ADD COLUMN qty_frozen smallint DEFAULT 0;
ALTER TABLE llx_mrp_production ADD COLUMN disable_stock_change smallint DEFAULT 0;

ALTER TABLE llx_mrp_production ADD CONSTRAINT fk_mrp_production_mo FOREIGN KEY (fk_mo) REFERENCES llx_mrp_mo (rowid);
ALTER TABLE llx_mrp_production ADD CONSTRAINT fk_mrp_production_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
ALTER TABLE llx_mrp_production ADD CONSTRAINT fk_mrp_production_stock_movement FOREIGN KEY (fk_stock_movement) REFERENCES llx_stock_mouvement (rowid);

ALTER TABLE llx_mrp_production ADD INDEX idx_mrp_production_fk_mo (fk_mo);

ALTER TABLE llx_emailcollector_emailcollector ADD UNIQUE INDEX uk_emailcollector_emailcollector_ref(ref, entity);

ALTER TABLE llx_website ADD COLUMN use_manifest integer;

ALTER TABLE llx_facture_rec MODIFY COLUMN fk_cond_reglement integer NOT NULL DEFAULT 1;

create table llx_commande_fournisseur_dispatch_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_commande_fournisseur_dispatch_extrafields ADD INDEX idx_commande_fournisseur_dispatch_extrafields (fk_object);


create table llx_facturedet_rec_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_facturedet_rec_extrafields ADD INDEX idx_facturedet_rec_extrafields (fk_object);

