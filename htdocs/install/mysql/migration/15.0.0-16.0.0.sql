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

-- Facture fourn rec
CREATE TABLE llx_facture_fourn_rec
(
    rowid                       integer AUTO_INCREMENT PRIMARY KEY,
    titre                       varchar(200)        NOT NULL,
    ref_supplier			    varchar(180) NOT NULL,
    entity                      integer   DEFAULT 1 NOT NULL,
    fk_soc                      integer             NOT NULL,
    datec                       datetime,
    tms                         timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    suspended                   integer   DEFAULT 0,
    libelle				        varchar(255),
    amount                      double(24, 8) DEFAULT 0 NOT NULL,
    remise                      real     DEFAULT 0,
    vat_src_code		        varchar(10)  DEFAULT '',
    localtax1				    double(24,8)     DEFAULT 0,
    localtax2				    double(24,8)     DEFAULT 0,
    total_ht				    double(24,8)     DEFAULT 0,
    total_tva				    double(24,8)     DEFAULT 0,
    total_ttc				    double(24,8)     DEFAULT 0,
    fk_user_author              integer,
    fk_user_modif               integer,
    fk_projet                   integer,
    fk_account                  integer,
    fk_cond_reglement		    integer,
    fk_mode_reglement		    integer,
    date_lim_reglement 	        date,
    note_private                text,
    note_public                 text,
    modelpdf                    varchar(255),
    fk_multicurrency            integer,
    multicurrency_code          varchar(3),
    multicurrency_tx            double(24,8) DEFAULT 1,
    multicurrency_total_ht      double(24,8) DEFAULT 0,
    multicurrency_total_tva     double(24,8) DEFAULT 0,
    multicurrency_total_ttc     double(24,8) DEFAULT 0,
    usenewprice                 integer DEFAULT 0,
    frequency                   integer,
    unit_frequency              varchar(2) DEFAULT 'm',
    date_when                   datetime DEFAULT NULL,
    date_last_gen               datetime DEFAULT NULL,
    nb_gen_done                 integer DEFAULT NULL,
    nb_gen_max                  integer DEFAULT NULL,
    auto_validate               integer DEFAULT 0,
    generate_pdf                integer DEFAULT 1

)ENGINE=innodb;

ALTER TABLE llx_facture_fourn_rec ADD UNIQUE INDEX uk_facture_fourn_rec_ref (titre, entity);
ALTER TABLE llx_facture_fourn_rec ADD UNIQUE INDEX uk_facture_fourn_rec_ref_supplier (ref_supplier, fk_soc, entity);
ALTER TABLE llx_facture_fourn_rec ADD INDEX idx_facture_fourn_rec_date_lim_reglement (date_lim_reglement);
ALTER TABLE llx_facture_fourn_rec ADD INDEX idx_facture_fourn_rec_fk_soc (fk_soc);
ALTER TABLE llx_facture_fourn_rec ADD INDEX idx_facture_fourn_rec_fk_user_author (fk_user_author);
ALTER TABLE llx_facture_fourn_rec ADD INDEX idx_facture_fourn_rec_fk_projet (fk_projet);
ALTER TABLE llx_facture_fourn_rec ADD CONSTRAINT fk_facture_fourn_rec_fk_soc            FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_facture_fourn_rec ADD CONSTRAINT fk_facture_fourn_rec_fk_user_author    FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
ALTER TABLE llx_facture_fourn_rec ADD CONSTRAINT fk_facture_fourn_rec_fk_projet         FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);

CREATE TABLE llx_facture_fourn_rec_extrafields
(
    rowid                     integer AUTO_INCREMENT PRIMARY KEY,
    tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_object                 integer NOT NULL,
    import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_facture_fourn_rec_extrafields ADD INDEX idx_facture_fourn_rec_extrafields (fk_object);

CREATE TABLE llx_facture_fourn_det_rec
(
    rowid				integer AUTO_INCREMENT PRIMARY KEY,
    fk_facture_fourn		integer NOT NULL,
    fk_parent_line	integer NULL,
    fk_product		integer NULL,
    ref               varchar(50),
    label				varchar(255) DEFAULT NULL,
    description		text,
    pu_ht             double(24,8),
  pu_ttc            double(24,8),
  qty               real,
  remise_percent	real       DEFAULT 0,
  fk_remise_except	integer    NULL,
  vat_src_code					varchar(10)  DEFAULT '',
  tva_tx			double(7,4),
  localtax1_tx      double(7,4) DEFAULT 0,
  localtax1_type	varchar(10) NULL,
  localtax2_tx      double(7,4) DEFAULT 0,
  localtax2_type	varchar(10)	 NULL,
  total_ht			double(24,8),
  total_tva			double(24,8),
  total_localtax1	double(24,8) DEFAULT 0,
  total_localtax2	double(24,8) DEFAULT 0,
  total_ttc			double(24,8),
  product_type		integer DEFAULT 0,
  date_start        integer   DEFAULT NULL,
  date_end          integer   DEFAULT NULL,
  info_bits			integer DEFAULT 0,
  special_code		integer UNSIGNED DEFAULT 0,
  rang				integer DEFAULT 0,
  fk_unit           integer    DEFAULT NULL,
  import_key		varchar(14),
  fk_user_author	integer,
  fk_user_modif     integer,
  fk_multicurrency          integer,
  multicurrency_code        varchar(3),
  multicurrency_subprice    double(24,8) DEFAULT 0,
  multicurrency_total_ht    double(24,8) DEFAULT 0,
  multicurrency_total_tva   double(24,8) DEFAULT 0,
  multicurrency_total_ttc   double(24,8) DEFAULT 0
)ENGINE=innodb;

ALTER TABLE llx_facture_fourn_det_rec ADD CONSTRAINT fk_facture_fourn_det_rec_fk_unit FOREIGN KEY (fk_unit) REFERENCES llx_c_units (rowid);

CREATE TABLE llx_facture_fourn_det_rec_extrafields
(
    rowid            integer AUTO_INCREMENT PRIMARY KEY,
    tms              timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_object        integer NOT NULL,    -- object id
    import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_facture_fourn_det_rec_extrafields ADD INDEX idx_facture_fourn_det_rec_extrafields (fk_object);

ALTER TABLE llx_facture_fourn ADD fk_fac_rec_source integer;
