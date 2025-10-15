--
-- This file is executed by calling /install/index.php page
-- when current version is higher than the name of this file.
-- Be carefull in the position of each SQL request.
--
-- To restrict request to Mysql version x.y minimum use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y minimum use -- VPGSQLx.y
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
--                          Note that "RENAME TO" is both compatible with mysql/postgesql, not the "RENAME" alone.
--                          Also you must complete with renaming the sequence for PGSQL with -- VPGSQL8.2 ALTER SEQUENCE llx_table_rowid_seq RENAME TO llx_table_new_rowid_seq;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key or constraint:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To create a unique index:              ALTER TABLE llx_table ADD UNIQUE INDEX uk_table_field (field);
-- To drop an index:        -- VMYSQL4.1 DROP INDEX nomindex ON llx_table;
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
-- To rebuild sequence for postgresql after insert, by forcing id autoincrement fields:
-- -- VPGSQL8.2 SELECT dol_util_rebuild_sequences();


-- V22 forgotten

ALTER TABLE llx_extrafields ADD module varchar(64) AFTER enabled;

ALTER TABLE llx_opensurvey_user_studs ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;


-- V23 migration

ALTER TABLE llx_document_model ADD COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_ticket ADD COLUMN note_public text after resolution;
ALTER TABLE llx_ticket ADD COLUMN note_private text after resolution;
ALTER TABLE llx_ticket ADD COLUMN fk_user_modif integer after resolution;


CREATE TABLE llx_paiement_extrafields (
	rowid                     integer AUTO_INCREMENT PRIMARY KEY,
	tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_object                 integer NOT NULL,
	import_key                varchar(14)                                 -- import key
) ENGINE=innodb;

ALTER TABLE llx_paiement_extrafields ADD UNIQUE INDEX uk_paiement_extrafields (fk_object);

ALTER TABLE llx_c_country ADD COLUMN phone_code varchar(8) DEFAULT NULL;

UPDATE llx_c_country SET phone_code = '+33' WHERE code = 'FR';
UPDATE llx_c_country SET phone_code = '+32' WHERE code = 'BE';
UPDATE llx_c_country SET phone_code = '+39' WHERE code = 'IT';
UPDATE llx_c_country SET phone_code = '+34' WHERE code = 'ES';
UPDATE llx_c_country SET phone_code = '+49' WHERE code = 'DE';
UPDATE llx_c_country SET phone_code = '+41' WHERE code = 'CH';
UPDATE llx_c_country SET phone_code = '+44' WHERE code = 'GB';
UPDATE llx_c_country SET phone_code = '+353' WHERE code = 'IE';
UPDATE llx_c_country SET phone_code = '+86' WHERE code = 'CN';
UPDATE llx_c_country SET phone_code = '+216' WHERE code = 'TN';
UPDATE llx_c_country SET phone_code = '+1' WHERE code = 'US';
UPDATE llx_c_country SET phone_code = '+212' WHERE code = 'MA';
UPDATE llx_c_country SET phone_code = '+213' WHERE code = 'DZ';
UPDATE llx_c_country SET phone_code = '+1' WHERE code = 'CA';
UPDATE llx_c_country SET phone_code = '+228' WHERE code = 'TG';
UPDATE llx_c_country SET phone_code = '+241' WHERE code = 'GA';
UPDATE llx_c_country SET phone_code = '+31' WHERE code = 'NL';
UPDATE llx_c_country SET phone_code = '+36' WHERE code = 'HU';
UPDATE llx_c_country SET phone_code = '+7' WHERE code = 'RU';
UPDATE llx_c_country SET phone_code = '+46' WHERE code = 'SE';
UPDATE llx_c_country SET phone_code = '+225' WHERE code = 'CI';
UPDATE llx_c_country SET phone_code = '+221' WHERE code = 'SN';
UPDATE llx_c_country SET phone_code = '+54' WHERE code = 'AR';
UPDATE llx_c_country SET phone_code = '+237' WHERE code = 'CM';
UPDATE llx_c_country SET phone_code = '+351' WHERE code = 'PT';
UPDATE llx_c_country SET phone_code = '+966' WHERE code = 'SA';
UPDATE llx_c_country SET phone_code = '+377' WHERE code = 'MC';
UPDATE llx_c_country SET phone_code = '+61' WHERE code = 'AU';
UPDATE llx_c_country SET phone_code = '+65' WHERE code = 'SG';
UPDATE llx_c_country SET phone_code = '+93' WHERE code = 'AF';
UPDATE llx_c_country SET phone_code = '+358' WHERE code = 'AX';
UPDATE llx_c_country SET phone_code = '+355' WHERE code = 'AL';
UPDATE llx_c_country SET phone_code = '+1-684' WHERE code = 'AS';
UPDATE llx_c_country SET phone_code = '+376' WHERE code = 'AD';
UPDATE llx_c_country SET phone_code = '+244' WHERE code = 'AO';
UPDATE llx_c_country SET phone_code = '+1-264' WHERE code = 'AI';
UPDATE llx_c_country SET phone_code = '' WHERE code = 'AQ';
UPDATE llx_c_country SET phone_code = '+1-268' WHERE code = 'AG';
UPDATE llx_c_country SET phone_code = '+374' WHERE code = 'AM';
UPDATE llx_c_country SET phone_code = '+297' WHERE code = 'AW';
UPDATE llx_c_country SET phone_code = '+43' WHERE code = 'AT';
UPDATE llx_c_country SET phone_code = '+994' WHERE code = 'AZ';
UPDATE llx_c_country SET phone_code = '+1-242' WHERE code = 'BS';
UPDATE llx_c_country SET phone_code = '+973' WHERE code = 'BH';
UPDATE llx_c_country SET phone_code = '+880' WHERE code = 'BD';
UPDATE llx_c_country SET phone_code = '+1-246' WHERE code = 'BB';
UPDATE llx_c_country SET phone_code = '+375' WHERE code = 'BY';
UPDATE llx_c_country SET phone_code = '+501' WHERE code = 'BZ';
UPDATE llx_c_country SET phone_code = '+229' WHERE code = 'BJ';
UPDATE llx_c_country SET phone_code = '+1-441' WHERE code = 'BM';
UPDATE llx_c_country SET phone_code = '+975' WHERE code = 'BT';
UPDATE llx_c_country SET phone_code = '+591' WHERE code = 'BO';
UPDATE llx_c_country SET phone_code = '+387' WHERE code = 'BA';
UPDATE llx_c_country SET phone_code = '+267' WHERE code = 'BW';
UPDATE llx_c_country SET phone_code = '' WHERE code = 'BV';
UPDATE llx_c_country SET phone_code = '+55' WHERE code = 'BR';
UPDATE llx_c_country SET phone_code = '+246' WHERE code = 'IO';
UPDATE llx_c_country SET phone_code = '+673' WHERE code = 'BN';
UPDATE llx_c_country SET phone_code = '+359' WHERE code = 'BG';
UPDATE llx_c_country SET phone_code = '+226' WHERE code = 'BF';
UPDATE llx_c_country SET phone_code = '+257' WHERE code = 'BI';
UPDATE llx_c_country SET phone_code = '+855' WHERE code = 'KH';
UPDATE llx_c_country SET phone_code = '+238' WHERE code = 'CV';
UPDATE llx_c_country SET phone_code = '+1-345' WHERE code = 'KY';
UPDATE llx_c_country SET phone_code = '+236' WHERE code = 'CF';
UPDATE llx_c_country SET phone_code = '+235' WHERE code = 'TD';
UPDATE llx_c_country SET phone_code = '+56' WHERE code = 'CL';
UPDATE llx_c_country SET phone_code = '+61' WHERE code = 'CX';
UPDATE llx_c_country SET phone_code = '+61' WHERE code = 'CC';
UPDATE llx_c_country SET phone_code = '+57' WHERE code = 'CO';
UPDATE llx_c_country SET phone_code = '+269' WHERE code = 'KM';
UPDATE llx_c_country SET phone_code = '+242' WHERE code = 'CG';
UPDATE llx_c_country SET phone_code = '+243' WHERE code = 'CD';
UPDATE llx_c_country SET phone_code = '+682' WHERE code = 'CK';
UPDATE llx_c_country SET phone_code = '+506' WHERE code = 'CR';
UPDATE llx_c_country SET phone_code = '+385' WHERE code = 'HR';
UPDATE llx_c_country SET phone_code = '+53' WHERE code = 'CU';
UPDATE llx_c_country SET phone_code = '+357' WHERE code = 'CY';
UPDATE llx_c_country SET phone_code = '+420' WHERE code = 'CZ';
UPDATE llx_c_country SET phone_code = '+45' WHERE code = 'DK';
UPDATE llx_c_country SET phone_code = '+253' WHERE code = 'DJ';
UPDATE llx_c_country SET phone_code = '+1-767' WHERE code = 'DM';
UPDATE llx_c_country SET phone_code = '+1-809' WHERE code = 'DO';
UPDATE llx_c_country SET phone_code = '+593' WHERE code = 'EC';
UPDATE llx_c_country SET phone_code = '+20' WHERE code = 'EG';
UPDATE llx_c_country SET phone_code = '+503' WHERE code = 'SV';
UPDATE llx_c_country SET phone_code = '+240' WHERE code = 'GQ';
UPDATE llx_c_country SET phone_code = '+291' WHERE code = 'ER';
UPDATE llx_c_country SET phone_code = '+372' WHERE code = 'EE';
UPDATE llx_c_country SET phone_code = '+251' WHERE code = 'ET';
UPDATE llx_c_country SET phone_code = '+500' WHERE code = 'FK';
UPDATE llx_c_country SET phone_code = '+298' WHERE code = 'FO';
UPDATE llx_c_country SET phone_code = '+679' WHERE code = 'FJ';
UPDATE llx_c_country SET phone_code = '+358' WHERE code = 'FI';
UPDATE llx_c_country SET phone_code = '+594' WHERE code = 'GF';
UPDATE llx_c_country SET phone_code = '+689' WHERE code = 'PF';
UPDATE llx_c_country SET phone_code = '+262' WHERE code = 'TF';
UPDATE llx_c_country SET phone_code = '+220' WHERE code = 'GM';
UPDATE llx_c_country SET phone_code = '+995' WHERE code = 'GE';
UPDATE llx_c_country SET phone_code = '+233' WHERE code = 'GH';
UPDATE llx_c_country SET phone_code = '+350' WHERE code = 'GI';
UPDATE llx_c_country SET phone_code = '+30' WHERE code = 'GR';
UPDATE llx_c_country SET phone_code = '+299' WHERE code = 'GL';
UPDATE llx_c_country SET phone_code = '+1-473' WHERE code = 'GD';
UPDATE llx_c_country SET phone_code = '+1-671' WHERE code = 'GU';
UPDATE llx_c_country SET phone_code = '+502' WHERE code = 'GT';
UPDATE llx_c_country SET phone_code = '+224' WHERE code = 'GN';
UPDATE llx_c_country SET phone_code = '+245' WHERE code = 'GW';
UPDATE llx_c_country SET phone_code = '+509' WHERE code = 'HT';
UPDATE llx_c_country SET phone_code = '+672' WHERE code = 'HM';
UPDATE llx_c_country SET phone_code = '+379' WHERE code = 'VA';
UPDATE llx_c_country SET phone_code = '+504' WHERE code = 'HN';
UPDATE llx_c_country SET phone_code = '+852' WHERE code = 'HK';
UPDATE llx_c_country SET phone_code = '+354' WHERE code = 'IS';
UPDATE llx_c_country SET phone_code = '+91' WHERE code = 'IN';
UPDATE llx_c_country SET phone_code = '+62' WHERE code = 'ID';
UPDATE llx_c_country SET phone_code = '+98' WHERE code = 'IR';
UPDATE llx_c_country SET phone_code = '+964' WHERE code = 'IQ';
UPDATE llx_c_country SET phone_code = '+972' WHERE code = 'IL';
UPDATE llx_c_country SET phone_code = '+1-876' WHERE code = 'JM';
UPDATE llx_c_country SET phone_code = '+81' WHERE code = 'JP';
UPDATE llx_c_country SET phone_code = '+962' WHERE code = 'JO';
UPDATE llx_c_country SET phone_code = '+7' WHERE code = 'KZ';
UPDATE llx_c_country SET phone_code = '+254' WHERE code = 'KE';
UPDATE llx_c_country SET phone_code = '+686' WHERE code = 'KI';
UPDATE llx_c_country SET phone_code = '+850' WHERE code = 'KP';
UPDATE llx_c_country SET phone_code = '+82' WHERE code = 'KR';
UPDATE llx_c_country SET phone_code = '+965' WHERE code = 'KW';
UPDATE llx_c_country SET phone_code = '+996' WHERE code = 'KG';
UPDATE llx_c_country SET phone_code = '+856' WHERE code = 'LA';
UPDATE llx_c_country SET phone_code = '+371' WHERE code = 'LV';
UPDATE llx_c_country SET phone_code = '+961' WHERE code = 'LB';
UPDATE llx_c_country SET phone_code = '+266' WHERE code = 'LS';
UPDATE llx_c_country SET phone_code = '+231' WHERE code = 'LR';
UPDATE llx_c_country SET phone_code = '+218' WHERE code = 'LY';
UPDATE llx_c_country SET phone_code = '+423' WHERE code = 'LI';
UPDATE llx_c_country SET phone_code = '+370' WHERE code = 'LT';
UPDATE llx_c_country SET phone_code = '+352' WHERE code = 'LU';
UPDATE llx_c_country SET phone_code = '+853' WHERE code = 'MO';
UPDATE llx_c_country SET phone_code = '+389' WHERE code = 'MK';
UPDATE llx_c_country SET phone_code = '+261' WHERE code = 'MG';
UPDATE llx_c_country SET phone_code = '+265' WHERE code = 'MW';
UPDATE llx_c_country SET phone_code = '+60' WHERE code = 'MY';
UPDATE llx_c_country SET phone_code = '+960' WHERE code = 'MV';
UPDATE llx_c_country SET phone_code = '+223' WHERE code = 'ML';
UPDATE llx_c_country SET phone_code = '+356' WHERE code = 'MT';
UPDATE llx_c_country SET phone_code = '+692' WHERE code = 'MH';
UPDATE llx_c_country SET phone_code = '+222' WHERE code = 'MR';
UPDATE llx_c_country SET phone_code = '+230' WHERE code = 'MU';
UPDATE llx_c_country SET phone_code = '+262' WHERE code = 'YT';
UPDATE llx_c_country SET phone_code = '+52' WHERE code = 'MX';
UPDATE llx_c_country SET phone_code = '+691' WHERE code = 'FM';
UPDATE llx_c_country SET phone_code = '+373' WHERE code = 'MD';
UPDATE llx_c_country SET phone_code = '+976' WHERE code = 'MN';
UPDATE llx_c_country SET phone_code = '+1-664' WHERE code = 'MS';
UPDATE llx_c_country SET phone_code = '+258' WHERE code = 'MZ';
UPDATE llx_c_country SET phone_code = '+95' WHERE code = 'MM';
UPDATE llx_c_country SET phone_code = '+264' WHERE code = 'NA';
UPDATE llx_c_country SET phone_code = '+674' WHERE code = 'NR';
UPDATE llx_c_country SET phone_code = '+977' WHERE code = 'NP';
UPDATE llx_c_country SET phone_code = '+687' WHERE code = 'NC';
UPDATE llx_c_country SET phone_code = '+64' WHERE code = 'NZ';
UPDATE llx_c_country SET phone_code = '+505' WHERE code = 'NI';
UPDATE llx_c_country SET phone_code = '+227' WHERE code = 'NE';
UPDATE llx_c_country SET phone_code = '+234' WHERE code = 'NG';
UPDATE llx_c_country SET phone_code = '+683' WHERE code = 'NU';
UPDATE llx_c_country SET phone_code = '+672' WHERE code = 'NF';
UPDATE llx_c_country SET phone_code = '+1-670' WHERE code = 'MP';
UPDATE llx_c_country SET phone_code = '+47' WHERE code = 'NO';
UPDATE llx_c_country SET phone_code = '+968' WHERE code = 'OM';
UPDATE llx_c_country SET phone_code = '+92' WHERE code = 'PK';
UPDATE llx_c_country SET phone_code = '+680' WHERE code = 'PW';
UPDATE llx_c_country SET phone_code = '+970' WHERE code = 'PS';
UPDATE llx_c_country SET phone_code = '+507' WHERE code = 'PA';
UPDATE llx_c_country SET phone_code = '+675' WHERE code = 'PG';
UPDATE llx_c_country SET phone_code = '+595' WHERE code = 'PY';
UPDATE llx_c_country SET phone_code = '+51' WHERE code = 'PE';
UPDATE llx_c_country SET phone_code = '+63' WHERE code = 'PH';
UPDATE llx_c_country SET phone_code = '+64' WHERE code = 'PN';
UPDATE llx_c_country SET phone_code = '+48' WHERE code = 'PL';
UPDATE llx_c_country SET phone_code = '+1-787' WHERE code = 'PR';
UPDATE llx_c_country SET phone_code = '+974' WHERE code = 'QA';
UPDATE llx_c_country SET phone_code = '+40' WHERE code = 'RO';
UPDATE llx_c_country SET phone_code = '+250' WHERE code = 'RW';
UPDATE llx_c_country SET phone_code = '+290' WHERE code = 'SH';
UPDATE llx_c_country SET phone_code = '+1-869' WHERE code = 'KN';
UPDATE llx_c_country SET phone_code = '+1-758' WHERE code = 'LC';
UPDATE llx_c_country SET phone_code = '+508' WHERE code = 'PM';
UPDATE llx_c_country SET phone_code = '+1-784' WHERE code = 'VC';
UPDATE llx_c_country SET phone_code = '+685' WHERE code = 'WS';
UPDATE llx_c_country SET phone_code = '+378' WHERE code = 'SM';
UPDATE llx_c_country SET phone_code = '+239' WHERE code = 'ST';
UPDATE llx_c_country SET phone_code = '+381' WHERE code = 'RS';
UPDATE llx_c_country SET phone_code = '+248' WHERE code = 'SC';
UPDATE llx_c_country SET phone_code = '+232' WHERE code = 'SL';
UPDATE llx_c_country SET phone_code = '+421' WHERE code = 'SK';
UPDATE llx_c_country SET phone_code = '+386' WHERE code = 'SI';
UPDATE llx_c_country SET phone_code = '+677' WHERE code = 'SB';
UPDATE llx_c_country SET phone_code = '+252' WHERE code = 'SO';
UPDATE llx_c_country SET phone_code = '+27' WHERE code = 'ZA';
UPDATE llx_c_country SET phone_code = '+500' WHERE code = 'GS';
UPDATE llx_c_country SET phone_code = '+94' WHERE code = 'LK';
UPDATE llx_c_country SET phone_code = '+249' WHERE code = 'SD';
UPDATE llx_c_country SET phone_code = '+597' WHERE code = 'SR';
UPDATE llx_c_country SET phone_code = '+47' WHERE code = 'SJ';
UPDATE llx_c_country SET phone_code = '+268' WHERE code = 'SZ';
UPDATE llx_c_country SET phone_code = '+963' WHERE code = 'SY';
UPDATE llx_c_country SET phone_code = '+886' WHERE code = 'TW';
UPDATE llx_c_country SET phone_code = '+992' WHERE code = 'TJ';
UPDATE llx_c_country SET phone_code = '+255' WHERE code = 'TZ';
UPDATE llx_c_country SET phone_code = '+66' WHERE code = 'TH';
UPDATE llx_c_country SET phone_code = '+670' WHERE code = 'TL';
UPDATE llx_c_country SET phone_code = '+690' WHERE code = 'TK';
UPDATE llx_c_country SET phone_code = '+676' WHERE code = 'TO';
UPDATE llx_c_country SET phone_code = '+1-868' WHERE code = 'TT';
UPDATE llx_c_country SET phone_code = '+90' WHERE code = 'TR';
UPDATE llx_c_country SET phone_code = '+993' WHERE code = 'TM';
UPDATE llx_c_country SET phone_code = '+1-649' WHERE code = 'TC';
UPDATE llx_c_country SET phone_code = '+688' WHERE code = 'TV';
UPDATE llx_c_country SET phone_code = '+256' WHERE code = 'UG';
UPDATE llx_c_country SET phone_code = '+380' WHERE code = 'UA';
UPDATE llx_c_country SET phone_code = '+971' WHERE code = 'AE';
UPDATE llx_c_country SET phone_code = '+1' WHERE code = 'UM';
UPDATE llx_c_country SET phone_code = '+598' WHERE code = 'UY';
UPDATE llx_c_country SET phone_code = '+998' WHERE code = 'UZ';
UPDATE llx_c_country SET phone_code = '+678' WHERE code = 'VU';
UPDATE llx_c_country SET phone_code = '+58' WHERE code = 'VE';
UPDATE llx_c_country SET phone_code = '+84' WHERE code = 'VN';
UPDATE llx_c_country SET phone_code = '+1-284' WHERE code = 'VG';
UPDATE llx_c_country SET phone_code = '+1-340' WHERE code = 'VI';
UPDATE llx_c_country SET phone_code = '+681' WHERE code = 'WF';
UPDATE llx_c_country SET phone_code = '+212' WHERE code = 'EH';
UPDATE llx_c_country SET phone_code = '+967' WHERE code = 'YE';
UPDATE llx_c_country SET phone_code = '+260' WHERE code = 'ZM';
UPDATE llx_c_country SET phone_code = '+263' WHERE code = 'ZW';
UPDATE llx_c_country SET phone_code = '+44' WHERE code = 'GG';
UPDATE llx_c_country SET phone_code = '+44' WHERE code = 'IM';
UPDATE llx_c_country SET phone_code = '+44' WHERE code = 'JE';
UPDATE llx_c_country SET phone_code = '+382' WHERE code = 'ME';
UPDATE llx_c_country SET phone_code = '+590' WHERE code = 'BL';
UPDATE llx_c_country SET phone_code = '+590' WHERE code = 'MF';
UPDATE llx_c_country SET phone_code = '+383' WHERE code = 'XK';
UPDATE llx_c_country SET phone_code = '+599' WHERE code = 'CW';
UPDATE llx_c_country SET phone_code = '+1-721' WHERE code = 'SX';

CREATE TABLE llx_accounting_analytic_axis (
	rowid               integer         AUTO_INCREMENT PRIMARY KEY,
	label               varchar(255)    NOT NULL,
	code                varchar(32)     NOT NULL,
	active              integer         DEFAULT 1,
	entity              integer         DEFAULT 1 NOT NULL,
	datec               datetime,
	tms                 timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_author      integer         NOT NULL,
	fk_user_modif       integer,
	import_key          varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_accounting_analytic_axis ADD UNIQUE INDEX uk_accounting_analytic_axis(code, entity);

CREATE TABLE llx_accounting_analytic_account (
	rowid               integer         AUTO_INCREMENT PRIMARY KEY,
	label               varchar(255)    NOT NULL,
	code                varchar(32)     NOT NULL,
	description			text,
	fk_axis				integer			NOT NULL,
	active              integer         DEFAULT 1,
	entity              integer         DEFAULT 1 NOT NULL,
	date_start			date,
	date_end			date,
	datec               datetime,
	tms                 timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_author      integer         NOT NULL,
	fk_user_modif       integer,
	import_key          varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_accounting_analytic_account ADD UNIQUE INDEX uk_accounting_analytic_account(code, entity);
ALTER TABLE llx_accounting_analytic_account ADD CONSTRAINT fk_accounting_analytic_account_fk_axis FOREIGN KEY (fk_axis) REFERENCES llx_accounting_analytic_axis (rowid);


CREATE TABLE llx_accounting_analytic_distribution (
	rowid				integer			AUTO_INCREMENT PRIMARY KEY,
	fk_source_line		integer			NOT NULL,		-- id de la ligne source (facture, écriture, etc.)
	sourcetype			varchar(32)		NOT NULL,		-- ex: 'facturedet', 'accounting_line'
	fk_analytic_account integer			NOT NULL,
	percentage			real			DEFAULT 100,	-- ex: 50.0 pour 50%
	amount				double(24,8),					-- optional, if you prefer to work on the amount
	entity				integer			DEFAULT 1,
	datec               datetime,
	tms                 timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_author      integer         NOT NULL,
	fk_user_modif       integer,
	import_key          varchar(14)
) ENGINE=innodb;

ALTER TABLE llx_accounting_analytic_distribution ADD CONSTRAINT fk_accounting_analytic_distribution_fk_analytic_account FOREIGN KEY (fk_analytic_account) REFERENCES llx_accounting_analytic_account (rowid);

ALTER TABLE llx_facture ADD COLUMN dispute_status integer DEFAULT 0 after payment_reference;
ALTER TABLE llx_facture ADD COLUMN ip varchar(250);

ALTER TABLE llx_commande ADD COLUMN ip varchar(250);
ALTER TABLE llx_commande ADD COLUMN user_agent varchar(255);

ALTER TABLE llx_commande_fournisseur ADD COLUMN date_reception datetime default NULL;

ALTER TABLE llx_c_currencies ADD COLUMN max_decimal_unit tinyint NULL;	-- Number of decimal in this currency for unit prices
ALTER TABLE llx_c_currencies ADD COLUMN max_decimal_tot tinyint NULL;	-- Number of decimal in this currency for total prices

ALTER TABLE llx_oauth_token ADD COLUMN lastaccess    	datetime NULL;						-- updated at each api access
ALTER TABLE llx_oauth_token ADD COLUMN apicount_previous_month BIGINT UNSIGNED DEFAULT 0;
ALTER TABLE llx_oauth_token ADD COLUMN apicount_month BIGINT UNSIGNED DEFAULT 0;			-- increased by 1 at each page access, saved into pageviews_previous_month when on different month than lastaccess
ALTER TABLE llx_oauth_token ADD COLUMN apicount_total BIGINT UNSIGNED DEFAULT 0;			-- increased by 1 at each page access, no reset

ALTER TABLE llx_webhook_target ADD COLUMN entity integer DEFAULT 1 NOT NULL;

ALTER TABLE llx_extrafields ADD COLUMN emptyonclone integer DEFAULT 0 AFTER alwayseditable;

ALTER TABLE llx_blockedlog ADD COLUMN object_format varchar(16) DEFAULT 'V1' AFTER object_version;
UPDATE llx_blockedlog SET object_format = '' WHERE object_version = '18' OR object_version = ''  OR object_version IS NULL;

INSERT INTO llx_c_country (rowid, code, code_iso, label, active, favorite, numeric_code) VALUES (248, 'BQ', 'BES', 'Bonaire, Sint Eustatius and Saba', 1, 0, 535);
INSERT INTO llx_c_country (rowid, code, code_iso, label, active, favorite, numeric_code) VALUES (249, 'GP', 'GLP', 'Guadeloupe', 0, 0, 312);
INSERT INTO llx_c_country (rowid, code, code_iso, label, active, favorite, numeric_code) VALUES (250, 'GY', 'GUY', 'Guyana', 0, 0, 328);
INSERT INTO llx_c_country (rowid, code, code_iso, label, active, favorite, numeric_code) VALUES (251, 'MQ', 'MTQ', 'Martinique', 0, 0, 474);
INSERT INTO llx_c_country (rowid, code, code_iso, label, active, favorite, numeric_code) VALUES (252, 'RE', 'REU', 'Reunion', 0, 0, 638);
INSERT INTO llx_c_country (rowid, code, code_iso, label, active, favorite, numeric_code) VALUES (253, 'SS', 'SSD', 'South Sudan', 1, 0, 728);

UPDATE llx_c_country SET sepa = 1 WHERE code IN ('AD','AL','AT','AX','BE','BG','BL','CH','CY','CZ','DE','DK','EE','ES','FI','FR','GB','GF','GG','GI','GP','GR','HR','HU','IE','IM','IS','IT','JE','LI','LT','LU','LV','MC','MD','ME','MF','MK','MQ','MT','NL','NO','PL','PM','PT','RE','RO','RS','SE','SI','SK','SM','VA','YT');

ALTER TABLE llx_user DROP COLUMN egroupware_id;

ALTER TABLE llx_adherent ADD COLUMN birth_place varchar(64) after birth;

ALTER TABLE llx_societe ADD COLUMN birth date DEFAULT NULL after fk_forme_juridique;

DELETE FROM llx_user_rights WHERE fk_id IN (SELECT id FROM llx_rights_def WHERE module = 'webhook' AND perms = 'webhook_target');
DELETE FROM llx_usergroup_rights WHERE fk_id IN (SELECT id FROM llx_rights_def WHERE module = 'webhook' AND perms = 'webhook_target');

DELETE FROM llx_rights_def WHERE module = 'webhook' AND perms = 'webhook_target';

ALTER TABLE llx_prelevement_lignes ADD COLUMN bic   varchar(11);   -- 11 according to ISO 9362
ALTER TABLE llx_prelevement_lignes ADD COLUMN iban	varchar(80);   -- full iban. 34 according to ISO 13616 but we set 80 to allow to store it with encryption information
ALTER TABLE llx_prelevement_lignes ADD COLUMN rum	varchar(32);   -- rum used


ALTER TABLE llx_product_customer_price CHANGE COLUMN localtax1_tx localtax1_tx varchar(20) DEFAULT '0';
ALTER TABLE llx_product_customer_price CHANGE COLUMN localtax2_tx localtax2_tx varchar(20) DEFAULT '0';
ALTER TABLE llx_product_customer_price_log CHANGE COLUMN localtax1_tx localtax1_tx varchar(20) DEFAULT '0';
ALTER TABLE llx_product_customer_price_log CHANGE COLUMN localtax2_tx localtax2_tx varchar(20) DEFAULT '0';

ALTER TABLE llx_subscription DROP INDEX idx_subscription_fk_adherent;
ALTER TABLE llx_subscription DROP INDEX idx_subscription_fk_bank;
ALTER TABLE llx_subscription DROP INDEX idx_subscription_dateadh;
ALTER TABLE llx_subscription ADD INDEX idx_subscription_fk_adherent (fk_adherent);
ALTER TABLE llx_subscription ADD INDEX idx_subscription_fk_bank (fk_bank);
ALTER TABLE llx_subscription ADD INDEX idx_subscription_dateadh (dateadh);

ALTER TABLE llx_subscription ADD COLUMN ref_ext varchar(128);
ALTER TABLE llx_subscription ADD COLUMN note_private text;

ALTER TABLE llx_bank_import ADD COLUMN fitid varchar(255) NULL after id_account; -- OFX Financial Institution Transaction ID "FITID"

ALTER TABLE llx_element_contact ADD mandatory_signature TINYINT AFTER element_id;

-- default deposit % if payment term needs it on supplier
ALTER TABLE llx_supplier_proposal ADD COLUMN deposit_percent varchar(63) DEFAULT NULL AFTER fk_cond_reglement;
ALTER TABLE llx_commande_fournisseur ADD COLUMN deposit_percent varchar(63) DEFAULT NULL AFTER fk_cond_reglement;
CREATE TABLE llx_categorie_propal
(
  fk_categorie integer NOT NULL,
  fk_propal integer NOT NULL,
  import_key varchar(14)
) ENGINE=innodb;
--noqa:disable=PRS
ALTER TABLE llx_categorie_propal ADD PRIMARY KEY pk_categorie_propal (fk_categorie, fk_propal);
--noqa:enable=PRS
ALTER TABLE llx_categorie_propal ADD INDEX idx_categorie_propal_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_propal ADD INDEX idx_categorie_propal_fk_propal (fk_propal);
ALTER TABLE llx_categorie_propal ADD CONSTRAINT fk_categorie_propal_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_propal ADD CONSTRAINT fk_categorie_propal_fk_propal_rowid FOREIGN KEY (fk_propal) REFERENCES llx_propal (rowid);

create table llx_categorie_supplier_proposal
(
  fk_categorie        integer NOT NULL,
  fk_supplier_proposal integer NOT NULL,
  import_key          varchar(14)
)ENGINE=innodb;

CREATE TABLE llx_accounting_bookkeeping_piece
(
	rowid               integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
	entity              integer DEFAULT 1 NOT NULL,
	ref             	varchar(128),
	tms					timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	datec				datetime,
	datep				date NOT NULL,
	statut				smallint DEFAULT 0,

	note_private		text,
	note_public			text,

	fk_user_author		integer,
	fk_user_modif		integer,
	fk_user_valid		integer,
	fk_user_closing		integer,

	import_key          varchar(14),
	extraparams         varchar(255)
) ENGINE=innodb;

ALTER TABLE llx_accounting_bookkeeping_piece ADD UNIQUE INDEX uk_accounting_bookkeeping_piece_ref (ref, entity);

ALTER TABLE llx_accounting_bookkeeping_piece ADD INDEX idx_accounting_bookkeeping_piece_fk_user_author (fk_user_author);
ALTER TABLE llx_accounting_bookkeeping_piece ADD INDEX idx_accounting_bookkeeping_piece_fk_user_modif (fk_user_modif);
ALTER TABLE llx_accounting_bookkeeping_piece ADD INDEX idx_accounting_bookkeeping_piece_fk_user_valid (fk_user_valid);
ALTER TABLE llx_accounting_bookkeeping_piece ADD INDEX idx_accounting_bookkeeping_piece_fk_user_closing (fk_user_closing);

ALTER TABLE llx_mailing ADD COLUMN fk_project integer DEFAULT NULL;
UPDATE llx_c_units SET label = 'unitP' WHERE code = 'P';

ALTER TABLE llx_receptiondet_batch ADD COLUMN description text AFTER fk_product;
ALTER TABLE llx_receptiondet_batch ADD COLUMN fk_unit integer AFTER qty;
ALTER TABLE llx_receptiondet_batch ADD COLUMN rang integer DEFAULT 0 AFTER cost_price;

ALTER TABLE llx_ecm_files ADD INDEX idx_ecm_files_src_object_type_id (src_object_type, src_object_id);

-- end of migration
