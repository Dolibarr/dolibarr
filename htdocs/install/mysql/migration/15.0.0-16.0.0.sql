--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 14.0.0 or higher.
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
-- To rebuild sequence for postgresql after insert by forcing id autoincrement fields:
-- -- VPGSQL8.2 SELECT dol_util_rebuild_sequences();


-- Missing in v15 or lower

UPDATE llx_rights_def SET perms = 'writeall' WHERE perms = 'writeall_advance' AND module = 'holiday';


-- v16

ALTER TABLE llx_projet_task_time ADD COLUMN fk_product integer NULL;

-- ---------------------
-- Assets
-- ---------------------

ALTER TABLE llx_asset DROP FOREIGN KEY fk_asset_asset_type;
ALTER TABLE llx_asset DROP INDEX idx_asset_fk_asset_type;

ALTER TABLE llx_asset CHANGE COLUMN amount_ht acquisition_value_ht double(24,8) NOT NULL;
ALTER TABLE llx_asset CHANGE COLUMN amount_vat recovered_vat double(24,8) NOT NULL;
DELETE FROM llx_asset WHERE fk_asset_type IS NOT NULL;
ALTER TABLE llx_asset DROP COLUMN fk_asset_type;
ALTER TABLE llx_asset DROP COLUMN description;
ALTER TABLE llx_asset ADD COLUMN fk_asset_model integer AFTER label;
ALTER TABLE llx_asset ADD COLUMN reversal_amount_ht double(24,8) AFTER fk_asset_model;
ALTER TABLE llx_asset ADD COLUMN reversal_date date AFTER recovered_vat;
ALTER TABLE llx_asset ADD COLUMN date_acquisition date NOT NULL AFTER reversal_date;
ALTER TABLE llx_asset ADD COLUMN date_start date NOT NULL AFTER date_acquisition;
ALTER TABLE llx_asset ADD COLUMN qty real DEFAULT 1 NOT NULL AFTER date_start;
ALTER TABLE llx_asset ADD COLUMN acquisition_type smallint DEFAULT 0 NOT NULL AFTER qty;
ALTER TABLE llx_asset ADD COLUMN asset_type smallint DEFAULT 0 NOT NULL AFTER acquisition_type;
ALTER TABLE llx_asset ADD COLUMN not_depreciated integer(1) DEFAULT 0 AFTER asset_type;
ALTER TABLE llx_asset ADD COLUMN disposal_date date AFTER not_depreciated;
ALTER TABLE llx_asset ADD COLUMN disposal_amount_ht double(24,8) AFTER disposal_date;
ALTER TABLE llx_asset ADD COLUMN fk_disposal_type integer AFTER disposal_amount_ht;
ALTER TABLE llx_asset ADD COLUMN disposal_depreciated integer(1) DEFAULT 0 AFTER fk_disposal_type;
ALTER TABLE llx_asset ADD COLUMN disposal_subject_to_vat integer(1) DEFAULT 0 AFTER disposal_depreciated;
ALTER TABLE llx_asset ADD COLUMN last_main_doc varchar(255) AFTER fk_user_modif;
ALTER TABLE llx_asset ADD COLUMN model_pdf varchar(255) AFTER import_key;

DROP TABLE llx_asset_type;

CREATE TABLE llx_c_asset_disposal_type
(
  rowid    integer            AUTO_INCREMENT PRIMARY KEY,
  entity 	 integer NOT NULL DEFAULT 1,
  code     varchar(16)        NOT NULL,
  label    varchar(50)        NOT NULL,
  active   integer  DEFAULT 1 NOT NULL
)ENGINE=innodb;

CREATE TABLE llx_asset_accountancy_codes_economic(
	rowid						integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_asset					integer,
	fk_asset_model				integer,

 	asset						varchar(32),
    depreciation_asset			varchar(32),
    depreciation_expense		varchar(32),
    value_asset_sold			varchar(32),
    receivable_on_assignment	varchar(32),
    proceeds_from_sales			varchar(32),
    vat_collected				varchar(32),
    vat_deductible				varchar(32),

	tms							timestamp,
	fk_user_modif				integer
) ENGINE=innodb;

CREATE TABLE llx_asset_accountancy_codes_fiscal(
	rowid									integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_asset								integer,
	fk_asset_model							integer,

    accelerated_depreciation				varchar(32),
    endowment_accelerated_depreciation		varchar(32),
    provision_accelerated_depreciation		varchar(32),

	tms										timestamp,
	fk_user_modif							integer
) ENGINE=innodb;

CREATE TABLE llx_asset_depreciation_options_economic(
	rowid								integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_asset							integer,
	fk_asset_model						integer,

	depreciation_type					smallint		DEFAULT 0 NOT NULL,		-- 0:linear, 1:degressive, 2:exceptional
	accelerated_depreciation_option		integer(1),								-- activate accelerated depreciation mode (fiscal)

    degressive_coefficient				double(24,8),
    duration							smallint		NOT NULL,
    duration_type						smallint		DEFAULT 0  NOT NULL,	-- 0:annual, 1:monthly, 2:daily

	amount_base_depreciation_ht			double(24,8),
	amount_base_deductible_ht			double(24,8),
	total_amount_last_depreciation_ht	double(24,8),

	tms									timestamp,
	fk_user_modif						integer
) ENGINE=innodb;

CREATE TABLE llx_asset_depreciation_options_fiscal(
	rowid								integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_asset							integer,
	fk_asset_model						integer,

	depreciation_type					smallint		DEFAULT 0 NOT NULL,		-- 0:linear, 1:degressive, 2:exceptional

    degressive_coefficient				double(24,8),
    duration							smallint		NOT NULL,
    duration_type						smallint		DEFAULT 0  NOT NULL,	-- 0:annual, 1:monthly, 2:daily

	amount_base_depreciation_ht			double(24,8),
	amount_base_deductible_ht			double(24,8),
	total_amount_last_depreciation_ht	double(24,8),

	tms									timestamp,
	fk_user_modif						integer
) ENGINE=innodb;

CREATE TABLE llx_asset_depreciation(
	rowid								integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,

	fk_asset							integer			NOT NULL,
	depreciation_mode					varchar(255)	NOT NULL,	-- (economic, fiscal or other)

	ref 								varchar(255)	NOT NULL,
	depreciation_date					datetime		NOT NULL,
	depreciation_ht						double(24,8)	NOT NULL,
	cumulative_depreciation_ht			double(24,8)	NOT NULL,

    accountancy_code_debit				varchar(32),
    accountancy_code_credit				varchar(32),

	tms									timestamp,
	fk_user_modif						integer
) ENGINE=innodb;

CREATE TABLE llx_asset_model(
	rowid					integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity					integer			DEFAULT 1 NOT NULL,  -- multi company id
	ref						varchar(128)	NOT NULL,
	label					varchar(255)	NOT NULL,

	asset_type				smallint		NOT NULL,

	note_public				text,
	note_private			text,
	date_creation			datetime		NOT NULL,
	tms						timestamp,
	fk_user_creat			integer			NOT NULL,
	fk_user_modif			integer,
	import_key				varchar(14),
	status					smallint		NOT NULL
) ENGINE=innodb;

CREATE TABLE llx_asset_model_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_c_asset_disposal_type ADD UNIQUE INDEX uk_c_asset_disposal_type(code, entity);

ALTER TABLE llx_asset ADD INDEX idx_asset_fk_asset_model (fk_asset_model);
ALTER TABLE llx_asset ADD INDEX idx_asset_fk_disposal_type (fk_disposal_type);

ALTER TABLE llx_asset ADD CONSTRAINT fk_asset_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset ADD CONSTRAINT fk_asset_disposal_type	FOREIGN KEY (fk_disposal_type)	REFERENCES llx_c_asset_disposal_type (rowid);
ALTER TABLE llx_asset ADD CONSTRAINT fk_asset_user_creat	FOREIGN KEY (fk_user_creat)		REFERENCES llx_user (rowid);
ALTER TABLE llx_asset ADD CONSTRAINT fk_asset_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_accountancy_codes_economic ADD INDEX idx_asset_ace_rowid (rowid);
ALTER TABLE llx_asset_accountancy_codes_economic ADD UNIQUE uk_asset_ace_fk_asset (fk_asset);
ALTER TABLE llx_asset_accountancy_codes_economic ADD UNIQUE uk_asset_ace_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset_accountancy_codes_economic ADD CONSTRAINT fk_asset_ace_asset			FOREIGN KEY (fk_asset)			REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_accountancy_codes_economic ADD CONSTRAINT fk_asset_ace_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset_accountancy_codes_economic ADD CONSTRAINT fk_asset_ace_user_modif		FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_accountancy_codes_fiscal ADD INDEX idx_asset_acf_rowid (rowid);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD UNIQUE uk_asset_acf_fk_asset (fk_asset);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD UNIQUE uk_asset_acf_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset_accountancy_codes_fiscal ADD CONSTRAINT fk_asset_acf_asset		FOREIGN KEY (fk_asset)			REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD CONSTRAINT fk_asset_acf_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD CONSTRAINT fk_asset_acf_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_depreciation_options_economic ADD INDEX idx_asset_doe_rowid (rowid);
ALTER TABLE llx_asset_depreciation_options_economic ADD UNIQUE uk_asset_doe_fk_asset (fk_asset);
ALTER TABLE llx_asset_depreciation_options_economic ADD UNIQUE uk_asset_doe_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset_depreciation_options_economic ADD CONSTRAINT fk_asset_doe_asset		FOREIGN KEY (fk_asset)			REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_depreciation_options_economic ADD CONSTRAINT fk_asset_doe_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset_depreciation_options_economic ADD CONSTRAINT fk_asset_doe_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_depreciation_options_fiscal ADD INDEX idx_asset_dof_rowid (rowid);
ALTER TABLE llx_asset_depreciation_options_fiscal ADD UNIQUE uk_asset_dof_fk_asset (fk_asset);
ALTER TABLE llx_asset_depreciation_options_fiscal ADD UNIQUE uk_asset_dof_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset_depreciation_options_fiscal ADD CONSTRAINT fk_asset_dof_asset			FOREIGN KEY (fk_asset)			REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_depreciation_options_fiscal ADD CONSTRAINT fk_asset_dof_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset_depreciation_options_fiscal ADD CONSTRAINT fk_asset_dof_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_depreciation ADD INDEX idx_asset_depreciation_rowid (rowid);
ALTER TABLE llx_asset_depreciation ADD INDEX idx_asset_depreciation_fk_asset (fk_asset);
ALTER TABLE llx_asset_depreciation ADD INDEX idx_asset_depreciation_depreciation_mode (depreciation_mode);
ALTER TABLE llx_asset_depreciation ADD INDEX idx_asset_depreciation_ref (ref);
ALTER TABLE llx_asset_depreciation ADD UNIQUE uk_asset_depreciation_fk_asset (fk_asset, depreciation_mode, ref);

ALTER TABLE llx_asset_depreciation ADD CONSTRAINT fk_asset_depreciation_asset		FOREIGN KEY (fk_asset)			REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_depreciation ADD CONSTRAINT fk_asset_depreciation_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_extrafields ADD INDEX idx_asset_extrafields (fk_object);

ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_rowid (rowid);
ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_ref (ref);
ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_entity (entity);
ALTER TABLE llx_asset_model ADD UNIQUE INDEX uk_asset_model (entity, ref);

ALTER TABLE llx_asset_model ADD CONSTRAINT fk_asset_model_user_creat	FOREIGN KEY (fk_user_creat)		REFERENCES llx_user (rowid);
ALTER TABLE llx_asset_model ADD CONSTRAINT fk_asset_model_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_model_extrafields ADD INDEX idx_asset_model_extrafields (fk_object);

ALTER TABLE llx_asset CHANGE COLUMN recovered_vat recovered_vat double(24,8);
ALTER TABLE llx_asset_model ADD COLUMN fk_pays integer DEFAULT 0 AFTER asset_type;
ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_pays (fk_pays);

