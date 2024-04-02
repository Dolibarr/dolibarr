--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 15.0.0 or higher.
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



-- Missing in v15 or lower

-- VMYSQL4.3 ALTER TABLE llx_c_civility ADD PRIMARY KEY(rowid);
-- VMYSQL4.3 ALTER TABLE llx_c_civility CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;

-- VMYSQL4.3 ALTER TABLE llx_c_payment_term ADD PRIMARY KEY(rowid);
-- VMYSQL4.3 ALTER TABLE llx_c_payment_term CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;

-- VPGSQL8.2 CREATE SEQUENCE __DATABASE__.llx_c_civility_rowid_seq OWNED BY llx_c_civility.rowid;
-- VPGSQL8.2 ALTER TABLE llx_c_civility ADD PRIMARY KEY (rowid);
-- VPGSQL8.2 ALTER TABLE llx_c_civility ALTER COLUMN rowid SET DEFAULT nextval('llx_c_civility_rowid_seq');
-- VPGSQL8.2 SELECT setval('llx_c_civility_rowid_seq', MAX(rowid)) FROM llx_c_civility;

-- VPGSQL8.2 CREATE SEQUENCE __DATABASE__.llx_c_payment_term_rowid_seq OWNED BY llx_c_payment_term.rowid;
-- VPGSQL8.2 ALTER TABLE llx_c_payment_term ADD PRIMARY KEY (rowid);
-- VPGSQL8.2 ALTER TABLE llx_c_payment_term ALTER COLUMN rowid SET DEFAULT nextval('llx_c_payment_term_rowid_seq');
-- VPGSQL8.2 SELECT setval('llx_c_payment_term_rowid_seq', MAX(rowid)) FROM llx_c_payment_term;

-- VMYSQL4.3 ALTER TABLE llx_emailcollector_emailcollector MODIFY COLUMN tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE llx_entrepot ADD COLUMN barcode  varchar(180) DEFAULT NULL;
ALTER TABLE llx_entrepot ADD COLUMN fk_barcode_type integer      DEFAULT NULL;

ALTER TABLE llx_c_transport_mode ADD UNIQUE INDEX uk_c_transport_mode (code, entity);

ALTER TABLE llx_c_shipment_mode MODIFY COLUMN tracking varchar(255) NULL;

ALTER TABLE llx_holiday ADD COLUMN nb_open_day double(24,8) DEFAULT NULL;

ALTER TABLE llx_element_tag ADD COLUMN fk_categorie INTEGER;


insert into llx_c_type_resource (code, label, active) values ('RES_ROOMS', 'Rooms',  1);
insert into llx_c_type_resource (code, label, active) values ('RES_CARS',  'Cars',  1);

ALTER TABLE llx_c_actioncomm MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_availability MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_barcode_type MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_chargesociales MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_civility MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_country MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_currencies MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_effectif MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_exp_tax_cat MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_hrm_department MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_hrm_function MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_input_method MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_input_reason MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_lead_status MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_paiement MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_paper_format MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_partnership_type MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_product_nature MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_productbatch_qcstatus MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_propalst MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_prospectcontactlevel MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_prospectlevel MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_recruitment_origin MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_shipment_mode MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_shipment_package_type MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_stcomm MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_stcommcontact MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_type_contact MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_type_container MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_type_fees MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_type_resource MODIFY COLUMN label varchar(128);
ALTER TABLE llx_c_typent MODIFY COLUMN libelle varchar(128);
ALTER TABLE llx_c_units MODIFY COLUMN label varchar(128);


UPDATE llx_rights_def SET perms = 'writeall' WHERE perms = 'writeall_advance' AND module = 'holiday';

-- Insert company legal forms of Mexico   
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15401', '601 - General de Ley Personas Morales', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15402', '603 - Personas Morales con Fines no Lucrativos', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15403', '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15404', '606 - Arrendamiento', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15405', '607 - Régimen de Enajenación o Adquisición de Bienes', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15406', '608 - Demás ingresos', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15407', '610 - Residentes en el Extranjero sin Establecimiento Permanente en México', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15408', '611 - Ingresos por Dividendos (socios y accionistas)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15409', '612 - Personas Físicas con Actividades Empresariales y Profesionales', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15410', '614 - Ingresos por intereses', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15411', '615 - Régimen de los ingresos por obtención de premios', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15412', '616 - Sin obligaciones fiscales', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15413', '620 - Sociedades Cooperativas de Producción que optan por diferir sus ingresos', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15414', '621 - Incorporación Fiscal', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15415', '622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15416', '623 - Opcional para Grupos de Sociedades', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15417', '624 - Coordinados', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15418', '625 - Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15419', '626 - Régimen Simplificado de Confianza', 1);


ALTER TABLE llx_partnership ADD UNIQUE INDEX uk_fk_type_fk_soc (fk_type, fk_soc, date_partnership_start);
ALTER TABLE llx_partnership ADD UNIQUE INDEX uk_fk_type_fk_member (fk_type, fk_member, date_partnership_start);


-- Add column to help to fix a very critical bug when transferring into accounting bank record of a bank account into another currency.
-- Idea is to update this column manually in v15 with value in currency of company for bank that are not into the main currency and the transfer
-- into accounting will use it in priority if value is not null. The script repair.sql contains the sequence to fix datas in llx_bank.
ALTER TABLE llx_bank ADD COLUMN amount_main_currency double(24,8) NULL;



-- v16

ALTER TABLE llx_element_contact DROP FOREIGN KEY fk_element_contact_fk_c_type_contact;
ALTER TABLE llx_societe_contacts DROP FOREIGN KEY fk_societe_contacts_fk_c_type_contact;

-- VMYSQL4.3 ALTER TABLE llx_c_type_contact ADD PRIMARY KEY(rowid);
-- VMYSQL4.3 ALTER TABLE llx_c_type_contact CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;

-- VPGSQL8.2 CREATE SEQUENCE __DATABASE__.llx_c_type_contact_rowid_seq OWNED BY llx_c_type_contact.rowid;
-- VPGSQL8.2 ALTER TABLE llx_c_type_contact ADD PRIMARY KEY (rowid);
-- VPGSQL8.2 ALTER TABLE llx_c_type_contact ALTER COLUMN rowid SET DEFAULT nextval('llx_c_type_contact_rowid_seq');
-- VPGSQL8.2 SELECT setval('llx_c_type_contact_rowid_seq', MAX(rowid)) FROM llx_c_type_contact;

insert into llx_c_type_contact(element, source, code, libelle, active ) values ('conferenceorbooth', 'internal', 'MANAGER',  'Conference or Booth manager', 1);
insert into llx_c_type_contact(element, source, code, libelle, active ) values ('conferenceorbooth', 'external', 'SPEAKER',   'Conference Speaker', 1);
insert into llx_c_type_contact(element, source, code, libelle, active ) values ('conferenceorbooth', 'external', 'RESPONSIBLE',   'Booth responsible', 1);

ALTER TABLE llx_element_contact ADD CONSTRAINT fk_element_contact_fk_c_type_contact FOREIGN KEY (fk_c_type_contact)     REFERENCES llx_c_type_contact(rowid);
ALTER TABLE llx_societe_contacts ADD CONSTRAINT fk_societe_contacts_fk_c_type_contact FOREIGN KEY (fk_c_type_contact)  REFERENCES llx_c_type_contact(rowid);


DROP TABLE llx_payment_salary_extrafields;
DROP TABLE llx_asset_model_extrafields;
DROP TABLE llx_asset_type_extrafields;

ALTER TABLE llx_projet_task_time ADD COLUMN intervention_id integer DEFAULT NULL;
ALTER TABLE llx_projet_task_time ADD COLUMN intervention_line_id integer DEFAULT NULL;

ALTER TABLE llx_c_stcomm MODIFY COLUMN code VARCHAR(24) NOT NULL;
ALTER TABLE llx_societe_account DROP FOREIGN KEY llx_societe_account_fk_website;

UPDATE llx_cronjob set label = 'RecurringInvoicesJob' where label = 'RecurringInvoices';
UPDATE llx_cronjob set label = 'RecurringSupplierInvoicesJob' where label = 'RecurringSupplierInvoices';

ALTER TABLE llx_facture ADD INDEX idx_facture_datef (datef);

ALTER TABLE llx_projet_task_time ADD COLUMN fk_product integer NULL;

ALTER TABLE llx_c_action_trigger MODIFY elementtype VARCHAR(64);

INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_MODIFY','Customer proposal modified','Executed when a customer proposal is modified','propal',2);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_MODIFY','Customer order modified','Executed when a customer order is set modified','commande',5);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_MODIFY','Customer invoice modified','Executed when a customer invoice is modified','facture',7);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_MODIFY','Price request modified','Executed when a commercial proposal is modified','proposal_supplier',10);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_MODIFY','Supplier order request modified','Executed when a supplier order is modified','order_supplier',13);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_MODIFY','Supplier invoice modified','Executed when a supplier invoice is modified','invoice_supplier',15);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_MODIFY','Contract modified','Executed when a contract is modified','contrat',18);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_MODIFY','Shipping modified','Executed when a shipping is modified','shipping',20);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_MODIFY','Intervention modify','Executed when a intervention is modify','ficheinter',30);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_MODIFY','Product or service modified','Executed when a product or sevice is modified','product',41);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_MODIFY','Expense report modified','Executed when an expense report is modified','expensereport',202);
INSERT INTO llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_MODIFY','Expense report modified','Executed when an expense report is modified','expensereport',212);

ALTER TABLE llx_ticket ADD COLUMN date_last_msg_sent datetime AFTER date_read;

UPDATE llx_const SET name = 'WORKFLOW_TICKET_LINK_CONTRACT' WHERE name = 'TICKET_AUTO_ASSIGN_CONTRACT_CREATE';
UPDATE llx_const SET name = 'WORKFLOW_TICKET_CREATE_INTERVENTION' WHERE name = 'TICKET_AUTO_CREATE_FICHINTER_CREATE';

CREATE TABLE llx_stock_mouvement_extrafields (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_object integer NOT NULL,
    import_key varchar(14)
)ENGINE=innodb;

ALTER TABLE llx_stock_mouvement_extrafields ADD INDEX idx_stock_mouvement_extrafields (fk_object);


-- Facture fourn rec
CREATE TABLE llx_facture_fourn_rec
(
    rowid                       integer AUTO_INCREMENT PRIMARY KEY,
    titre                       varchar(200)             NOT NULL,
    ref_supplier                varchar(180)             NOT NULL,
    entity                      integer       DEFAULT 1  NOT NULL,
    fk_soc                      integer                  NOT NULL,
    datec                       datetime,
    tms                         timestamp     DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
    suspended                   integer       DEFAULT 0,
    libelle                     varchar(255),
    amount                      double(24,8)  DEFAULT 0  NOT NULL,
    remise                      real          DEFAULT 0,
    vat_src_code                varchar(10)   DEFAULT '',
    localtax1                   double(24,8)  DEFAULT 0,
    localtax2                   double(24,8)  DEFAULT 0,
    total_ht                    double(24,8)  DEFAULT 0,
    total_tva                   double(24,8)  DEFAULT 0,
    total_ttc                   double(24,8)  DEFAULT 0,
    fk_user_author              integer,
    fk_user_modif               integer,
    fk_projet                   integer,
    fk_account                  integer,
    fk_cond_reglement           integer,
    fk_mode_reglement           integer,
    date_lim_reglement 	        date,
    note_private                text,
    note_public                 text,
    modelpdf                    varchar(255),
    fk_multicurrency            integer,
    multicurrency_code          varchar(3),
    multicurrency_tx            double(24,8)  DEFAULT 1,
    multicurrency_total_ht      double(24,8)  DEFAULT 0,
    multicurrency_total_tva     double(24,8)  DEFAULT 0,
    multicurrency_total_ttc     double(24,8)  DEFAULT 0,
    usenewprice                 integer       DEFAULT 0,
    frequency                   integer,
    unit_frequency              varchar(2)    DEFAULT 'm',
    date_when                   datetime      DEFAULT NULL,
    date_last_gen               datetime      DEFAULT NULL,
    nb_gen_done                 integer       DEFAULT NULL,
    nb_gen_max                  integer       DEFAULT NULL,
    auto_validate               integer       DEFAULT 0,
    generate_pdf                integer       DEFAULT 1
) ENGINE=innodb;

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
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture_fourn  	integer NOT NULL,
  fk_parent_line        integer NULL,
  fk_product            integer NULL,
  ref                   varchar(50),
  label                 varchar(255)  DEFAULT NULL,
  description           text,
  pu_ht                 double(24,8),
  pu_ttc                double(24,8),
  qty                   real,
  remise_percent        real          DEFAULT 0,
  fk_remise_except      integer       NULL,
  vat_src_code          varchar(10)   DEFAULT '',
  tva_tx                double(7,4),
  localtax1_tx          double(7,4)   DEFAULT 0,
  localtax1_type        varchar(10)   NULL,
  localtax2_tx          double(7,4)   DEFAULT 0,
  localtax2_type        varchar(10)   NULL,
  total_ht              double(24,8),
  total_tva             double(24,8),
  total_localtax1       double(24,8)  DEFAULT 0,
  total_localtax2       double(24,8)  DEFAULT 0,
  total_ttc             double(24,8),
  product_type          integer   DEFAULT 0,
  date_start            integer   DEFAULT NULL,
  date_end              integer   DEFAULT NULL,
  info_bits             integer   DEFAULT 0,
  special_code          integer  UNSIGNED DEFAULT 0,
  rang                  integer   DEFAULT 0,
  fk_unit               integer   DEFAULT NULL,
  import_key            varchar(14),
  fk_user_author        integer,
  fk_user_modif         integer,
  fk_multicurrency      integer,
  multicurrency_code        varchar(3),
  multicurrency_subprice    double(24,8) DEFAULT 0,
  multicurrency_total_ht    double(24,8) DEFAULT 0,
  multicurrency_total_tva   double(24,8) DEFAULT 0,
  multicurrency_total_ttc   double(24,8) DEFAULT 0
) ENGINE=innodb;


ALTER TABLE llx_facture_fourn_det_rec ADD CONSTRAINT fk_facture_fourn_det_rec_fk_unit FOREIGN KEY (fk_unit) REFERENCES llx_c_units (rowid);


CREATE TABLE llx_facture_fourn_det_rec_extrafields
(
    rowid            integer AUTO_INCREMENT PRIMARY KEY,
    tms              timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_object        integer NOT NULL,    -- object id
    import_key       varchar(14)      	  -- import key
) ENGINE=innodb;


ALTER TABLE llx_facture_fourn_det_rec_extrafields ADD INDEX idx_facture_fourn_det_rec_extrafields (fk_object);

ALTER TABLE llx_facture_fourn ADD COLUMN fk_fac_rec_source integer;

ALTER TABLE llx_mrp_mo ADD COLUMN fk_parent_line integer;

ALTER TABLE llx_projet_task ADD COLUMN status integer DEFAULT 1 NOT NULL;

ALTER TABLE llx_product_attribute_value MODIFY COLUMN ref VARCHAR(180) NOT NULL;
ALTER TABLE llx_product_attribute_value MODIFY COLUMN value VARCHAR(255) NOT NULL;
ALTER TABLE llx_product_attribute_value ADD COLUMN position INTEGER NOT NULL DEFAULT 0;
ALTER TABLE llx_product_attribute CHANGE rang position INTEGER DEFAULT 0 NOT NULL;

ALTER TABLE llx_emailcollector_emailcollector ADD COLUMN position INTEGER NOT NULL DEFAULT 0;

ALTER TABLE llx_advtargetemailing RENAME TO llx_mailing_advtarget;

ALTER TABLE llx_mailing ADD UNIQUE INDEX uk_mailing (titre, entity);


create table llx_inventory_extrafields
(
    rowid                     integer AUTO_INCREMENT PRIMARY KEY,
    tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_object                 integer NOT NULL,
    import_key                varchar(14)                          		-- import key
) ENGINE=innodb;


ALTER TABLE llx_inventory_extrafields ADD INDEX idx_inventory_extrafields (fk_object);

ALTER TABLE llx_reception MODIFY COLUMN ref_supplier varchar(128);

ALTER TABLE llx_bank_account ADD COLUMN pti_in_ctti smallint DEFAULT 0 AFTER domiciliation;

-- Set default ticket type to OTHER if no default exists
UPDATE llx_c_ticket_type SET use_default=1 WHERE code='OTHER' AND NOT EXISTS(SELECT * FROM (SELECT * FROM llx_c_ticket_type) AS t WHERE use_default=1);


-- Assets - New module

CREATE TABLE llx_asset(
    rowid                   integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    ref                     varchar(128)    NOT NULL,
    entity                  integer         DEFAULT 1 NOT NULL,
    label                   varchar(255),

    fk_asset_model          integer,

    reversal_amount_ht      double(24,8),
    acquisition_value_ht    double(24,8)    DEFAULT NULL,
    recovered_vat           double(24,8),

    reversal_date           date,

    date_acquisition        date            NOT NULL,
    date_start              date            NOT NULL,

    qty                     real            DEFAULT 1 NOT NULL,

    acquisition_type        smallint        DEFAULT 0 NOT NULL,
    asset_type              smallint        DEFAULT 0 NOT NULL,

    not_depreciated         integer         DEFAULT 0,

    disposal_date           date,
    disposal_amount_ht      double(24,8),
    fk_disposal_type        integer,
    disposal_depreciated    integer         DEFAULT 0,
    disposal_subject_to_vat integer         DEFAULT 0,

    note_public             text,
    note_private            text,

    date_creation           datetime        NOT NULL,
    tms                     timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat           integer         NOT NULL,
    fk_user_modif           integer,
    last_main_doc           varchar(255),
    import_key              varchar(14),
    model_pdf               varchar(255),
    status                  integer         NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_asset DROP FOREIGN KEY fk_asset_asset_type;
ALTER TABLE llx_asset DROP INDEX idx_asset_fk_asset_type;

ALTER TABLE llx_asset CHANGE COLUMN amount_ht acquisition_value_ht double(24,8) NOT NULL;
ALTER TABLE llx_asset CHANGE COLUMN amount_vat recovered_vat double(24,8);

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
ALTER TABLE llx_asset ADD COLUMN not_depreciated integer DEFAULT 0 AFTER asset_type;
ALTER TABLE llx_asset ADD COLUMN disposal_date date AFTER not_depreciated;
ALTER TABLE llx_asset ADD COLUMN disposal_amount_ht double(24,8) AFTER disposal_date;
ALTER TABLE llx_asset ADD COLUMN fk_disposal_type integer AFTER disposal_amount_ht;
ALTER TABLE llx_asset ADD COLUMN disposal_depreciated integer DEFAULT 0 AFTER fk_disposal_type;
ALTER TABLE llx_asset ADD COLUMN disposal_subject_to_vat integer DEFAULT 0 AFTER disposal_depreciated;
ALTER TABLE llx_asset ADD COLUMN last_main_doc varchar(255) AFTER fk_user_modif;
ALTER TABLE llx_asset ADD COLUMN model_pdf varchar(255) AFTER import_key;

DROP TABLE llx_asset_type;


CREATE TABLE llx_c_asset_disposal_type
(
    rowid 			integer 	AUTO_INCREMENT  PRIMARY KEY,
    entity 			integer 	NOT NULL  DEFAULT 1,
    code 			varchar(16) 	NOT NULL,
    label 			varchar(50) 	NOT NULL,
    active 			integer 	NOT NULL  DEFAULT 1 
) ENGINE=innodb;


CREATE TABLE llx_asset_accountancy_codes_economic
(
    rowid			integer		AUTO_INCREMENT  PRIMARY KEY  NOT NULL,
    fk_asset			integer,
    fk_asset_model		integer,

    asset			varchar(32),
    depreciation_asset		varchar(32),
    depreciation_expense	varchar(32),
    value_asset_sold		varchar(32),
    receivable_on_assignment	varchar(32),
    proceeds_from_sales		varchar(32),
    vat_collected		varchar(32),
    vat_deductible		varchar(32),
    tms				timestamp       DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
    fk_user_modif		integer
) ENGINE=innodb;


CREATE TABLE llx_asset_accountancy_codes_fiscal
(
    rowid					integer		AUTO_INCREMENT  PRIMARY KEY  NOT NULL,
    fk_asset					integer,
    fk_asset_model				integer,

    accelerated_depreciation			varchar(32),
    endowment_accelerated_depreciation		varchar(32),
    provision_accelerated_depreciation		varchar(32),

    tms 					timestamp       DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
    fk_user_modif				integer
) ENGINE=innodb;


CREATE TABLE llx_asset_depreciation_options_economic
(
    rowid					integer			AUTO_INCREMENT  PRIMARY KEY  NOT NULL,
    fk_asset					integer,
    fk_asset_model				integer,

    depreciation_type				smallint		DEFAULT 0  NOT NULL,		-- 0:linear, 1:degressive, 2:exceptional
    accelerated_depreciation_option		integer,						-- activate accelerated depreciation mode (fiscal)

    degressive_coefficient			double(24,8),
    duration					smallint			   NOT NULL,
    duration_type				smallint		DEFAULT 0  NOT NULL,		-- 0:annual, 1:monthly, 2:daily

    amount_base_depreciation_ht			double(24,8),
    amount_base_deductible_ht			double(24,8),
    total_amount_last_depreciation_ht		double(24,8),

    tms                                 	timestamp       	DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
    fk_user_modif				integer
) ENGINE=innodb;


CREATE TABLE llx_asset_depreciation_options_fiscal
(
    rowid					integer			AUTO_INCREMENT  PRIMARY KEY  NOT NULL,
    fk_asset					integer,
    fk_asset_model				integer,

    depreciation_type				smallint		DEFAULT 0  NOT NULL,		-- 0:linear, 1:degressive, 2:exceptional

    degressive_coefficient			double(24,8),
    duration					smallint		NOT NULL,
    duration_type				smallint		DEFAULT 0  NOT NULL,		-- 0:annual, 1:monthly, 2:daily

    amount_base_depreciation_ht			double(24,8),
    amount_base_deductible_ht			double(24,8),
    total_amount_last_depreciation_ht		double(24,8),

    tms						timestamp 		DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
    fk_user_modif				integer
) ENGINE=innodb;


CREATE TABLE llx_asset_depreciation
(
    rowid					integer		AUTO_INCREMENT  PRIMARY KEY  NOT NULL,

    fk_asset					integer		NOT NULL,
    depreciation_mode				varchar(255)	NOT NULL,		-- (economic, fiscal or other)

    ref 					varchar(255)	NOT NULL,
    depreciation_date				datetime	NOT NULL,
    depreciation_ht				double(24,8)	NOT NULL,
    cumulative_depreciation_ht			double(24,8)	NOT NULL,

    accountancy_code_debit			varchar(32),
    accountancy_code_credit			varchar(32),

    tms                                		timestamp       DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
    fk_user_modif				integer
) ENGINE=innodb;


CREATE TABLE llx_asset_model(
    rowid					integer		AUTO_INCREMENT  PRIMARY KEY  NOT NULL,
    entity					integer		DEFAULT 1  NOT NULL,  	-- multi company id
    ref						varchar(128)	NOT NULL,
    label					varchar(255)	NOT NULL,

    asset_type					smallint	NOT NULL,
    fk_pays					integer 	DEFAULT 0,

    note_public					text,
    note_private				text,
    date_creation				datetime	NOT NULL,
    tms                     			timestamp       DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat				integer		NOT NULL,
    fk_user_modif				integer,
    import_key					varchar(14),
    status					smallint	NOT NULL
) ENGINE=innodb;

CREATE TABLE llx_asset_model_extrafields
(
    rowid                     integer       AUTO_INCREMENT  PRIMARY KEY,
    tms                       timestamp     DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
    fk_object                 integer       NOT NULL,
    import_key                varchar(14)                          		-- import key
) ENGINE=innodb;


ALTER TABLE llx_c_asset_disposal_type ADD UNIQUE INDEX uk_c_asset_disposal_type(code, entity);

ALTER TABLE llx_asset ADD INDEX idx_asset_fk_asset_model (fk_asset_model);
ALTER TABLE llx_asset ADD INDEX idx_asset_fk_disposal_type (fk_disposal_type);

ALTER TABLE llx_asset ADD CONSTRAINT fk_asset_asset_model	FOREIGN KEY (fk_asset_model)		REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset ADD CONSTRAINT fk_asset_disposal_type	FOREIGN KEY (fk_disposal_type)		REFERENCES llx_c_asset_disposal_type (rowid);
ALTER TABLE llx_asset ADD CONSTRAINT fk_asset_user_creat	FOREIGN KEY (fk_user_creat)		REFERENCES llx_user (rowid);
ALTER TABLE llx_asset ADD CONSTRAINT fk_asset_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_accountancy_codes_economic ADD INDEX idx_asset_ace_rowid (rowid);
ALTER TABLE llx_asset_accountancy_codes_economic ADD UNIQUE uk_asset_ace_fk_asset (fk_asset);
ALTER TABLE llx_asset_accountancy_codes_economic ADD UNIQUE uk_asset_ace_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset_accountancy_codes_economic ADD CONSTRAINT fk_asset_ace_asset		FOREIGN KEY (fk_asset)		REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_accountancy_codes_economic ADD CONSTRAINT fk_asset_ace_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset_accountancy_codes_economic ADD CONSTRAINT fk_asset_ace_user_modif		FOREIGN KEY (fk_user_modif)	REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_accountancy_codes_fiscal ADD INDEX idx_asset_acf_rowid (rowid);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD UNIQUE uk_asset_acf_fk_asset (fk_asset);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD UNIQUE uk_asset_acf_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset_accountancy_codes_fiscal ADD CONSTRAINT fk_asset_acf_asset		FOREIGN KEY (fk_asset)		REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD CONSTRAINT fk_asset_acf_asset_model		FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD CONSTRAINT fk_asset_acf_user_modif		FOREIGN KEY (fk_user_modif)	REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_depreciation_options_economic ADD INDEX idx_asset_doe_rowid (rowid);
ALTER TABLE llx_asset_depreciation_options_economic ADD UNIQUE uk_asset_doe_fk_asset (fk_asset);
ALTER TABLE llx_asset_depreciation_options_economic ADD UNIQUE uk_asset_doe_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset_depreciation_options_economic ADD CONSTRAINT fk_asset_doe_asset		FOREIGN KEY (fk_asset)		REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_depreciation_options_economic ADD CONSTRAINT fk_asset_doe_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset_depreciation_options_economic ADD CONSTRAINT fk_asset_doe_user_modif	FOREIGN KEY (fk_user_modif)	REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_depreciation_options_fiscal ADD INDEX idx_asset_dof_rowid (rowid);
ALTER TABLE llx_asset_depreciation_options_fiscal ADD UNIQUE uk_asset_dof_fk_asset (fk_asset);
ALTER TABLE llx_asset_depreciation_options_fiscal ADD UNIQUE uk_asset_dof_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset_depreciation_options_fiscal ADD CONSTRAINT fk_asset_dof_asset		FOREIGN KEY (fk_asset)		REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_depreciation_options_fiscal ADD CONSTRAINT fk_asset_dof_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset_depreciation_options_fiscal ADD CONSTRAINT fk_asset_dof_user_modif	FOREIGN KEY (fk_user_modif)	REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_depreciation ADD INDEX idx_asset_depreciation_rowid (rowid);
ALTER TABLE llx_asset_depreciation ADD INDEX idx_asset_depreciation_fk_asset (fk_asset);
ALTER TABLE llx_asset_depreciation ADD INDEX idx_asset_depreciation_depreciation_mode (depreciation_mode);
ALTER TABLE llx_asset_depreciation ADD INDEX idx_asset_depreciation_ref (ref);
ALTER TABLE llx_asset_depreciation ADD UNIQUE uk_asset_depreciation_fk_asset (fk_asset, depreciation_mode, ref);

ALTER TABLE llx_asset_depreciation ADD CONSTRAINT fk_asset_depreciation_asset			FOREIGN KEY (fk_asset)		REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_depreciation ADD CONSTRAINT fk_asset_depreciation_user_modif		FOREIGN KEY (fk_user_modif)	REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_rowid (rowid);
ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_ref (ref);
ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_pays (fk_pays);
ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_entity (entity);
ALTER TABLE llx_asset_model ADD UNIQUE INDEX uk_asset_model (entity, ref);

ALTER TABLE llx_asset_model ADD CONSTRAINT fk_asset_model_user_creat		FOREIGN KEY (fk_user_creat)		REFERENCES llx_user (rowid);
ALTER TABLE llx_asset_model ADD CONSTRAINT fk_asset_model_user_modif		FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);

ALTER TABLE llx_asset_model_extrafields ADD INDEX idx_asset_model_extrafields (fk_object);

ALTER TABLE llx_user DROP COLUMN webcal_login;
ALTER TABLE llx_user DROP COLUMN module_comm;
ALTER TABLE llx_user DROP COLUMN module_compta;
ALTER TABLE llx_user DROP COLUMN ref_int;

ALTER TABLE llx_user ADD COLUMN ref_employee varchar(50) DEFAULT NULL AFTER entity;
ALTER TABLE llx_user ADD COLUMN national_registration_number varchar(50) DEFAULT NULL;


ALTER TABLE llx_propal ADD last_main_doc VARCHAR(255) NULL AFTER model_pdf;

UPDATE llx_c_country SET eec=0 WHERE eec IS NULL;
ALTER TABLE llx_c_country MODIFY COLUMN eec tinyint DEFAULT 0 NOT NULL;
ALTER TABLE llx_inventorydet ADD COLUMN pmp_real double DEFAULT NULL;
ALTER TABLE llx_inventorydet ADD COLUMN pmp_expected double DEFAULT NULL;



ALTER TABLE llx_chargesociales ADD COLUMN note_private text;
ALTER TABLE llx_chargesociales ADD COLUMN note_public text;

ALTER TABLE llx_c_availability ADD COLUMN type_duration varchar(1);
ALTER TABLE llx_c_availability ADD COLUMN qty real DEFAULT 0;

UPDATE llx_c_availability SET type_duration = null, qty = 0 WHERE code = 'AV_NOW';
UPDATE llx_c_availability SET type_duration = 'w', qty = 1 WHERE code = 'AV_1W';
UPDATE llx_c_availability SET type_duration = 'w', qty = 2 WHERE code = 'AV_2W';
UPDATE llx_c_availability SET type_duration = 'w', qty = 3 WHERE code = 'AV_3W';
UPDATE llx_c_availability SET type_duration = 'w', qty = 4 WHERE code = 'AV_4W';


-- Deposit generation helper with specific payment terms
ALTER TABLE llx_c_payment_term 	ADD COLUMN deposit_percent VARCHAR(63) DEFAULT NULL AFTER decalage;
ALTER TABLE llx_societe 	ADD COLUMN deposit_percent VARCHAR(63) DEFAULT NULL AFTER cond_reglement;
ALTER TABLE llx_propal 		ADD COLUMN deposit_percent VARCHAR(63) DEFAULT NULL AFTER fk_cond_reglement;
ALTER TABLE llx_commande 	ADD COLUMN deposit_percent VARCHAR(63) DEFAULT NULL AFTER fk_cond_reglement;

INSERT INTO llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent) values ('DEP30PCTDEL', 13, 0, '__DEPOSIT_PERCENT__% deposit', '__DEPOSIT_PERCENT__% deposit, remainder on delivery', 0, 1, '30');


ALTER TABLE llx_boxes_def ADD COLUMN fk_user integer DEFAULT 0 NOT NULL;

ALTER TABLE llx_contratdet ADD COLUMN rang integer DEFAULT 0 AFTER info_bits;

ALTER TABLE llx_actioncomm MODIFY COLUMN note mediumtext;

DELETE FROM llx_boxes WHERE box_id IN (select rowid FROM llx_boxes_def WHERE file IN ('box_bom.php@bom', 'box_bom.php', 'box_members.php', 'box_last_modified_ticket', 'box_members_last_subscriptions', 'box_members_last_modified', 'box_members_subscriptions_by_year'));
DELETE FROM llx_boxes_def WHERE file IN ('box_bom.php@bom', 'box_bom.php', 'box_members.php', 'box_last_modified_ticket', 'box_members_last_subscriptions', 'box_members_last_modified', 'box_members_subscriptions_by_year');

ALTER TABLE llx_takepos_floor_tables ADD UNIQUE INDEX uk_takepos_floor_tables (entity,label);

ALTER TABLE llx_partnership 		ADD COLUMN url_to_check varchar(255);
ALTER TABLE llx_c_partnership_type 	ADD COLUMN keyword	varchar(128);


ALTER TABLE llx_eventorganization_conferenceorboothattendee 	ADD COLUMN firstname 	 varchar(100);
ALTER TABLE llx_eventorganization_conferenceorboothattendee 	ADD COLUMN lastname 	 varchar(100);
ALTER TABLE llx_eventorganization_conferenceorboothattendee 	ADD COLUMN email_company varchar(128) after email;

-- VMYSQL4.3 ALTER TABLE llx_eventorganization_conferenceorboothattendee MODIFY COLUMN fk_user_creat integer NULL;
-- VPGSQL8.2 ALTER TABLE llx_eventorganization_conferenceorboothattendee ALTER COLUMN fk_user_creat DROP NOT NULL;


ALTER TABLE llx_c_email_templates ADD COLUMN joinfiles text;
ALTER TABLE llx_c_email_templates ADD COLUMN email_from varchar(255);
ALTER TABLE llx_c_email_templates ADD COLUMN email_to varchar(255);
ALTER TABLE llx_c_email_templates ADD COLUMN email_tocc varchar(255);
ALTER TABLE llx_c_email_templates ADD COLUMN email_tobcc varchar(255);

ALTER TABLE llx_fichinter ADD COLUMN ref_client varchar(255) after ref_ext;

ALTER TABLE llx_c_holiday_types ADD COLUMN block_if_negative integer NOT NULL DEFAULT 0 AFTER fk_country;
ALTER TABLE llx_c_holiday_types ADD COLUMN sortorder smallint;

ALTER TABLE llx_expedition MODIFY COLUMN ref_customer varchar(255);

ALTER TABLE llx_extrafields ADD COLUMN css varchar(128);
ALTER TABLE llx_extrafields ADD COLUMN cssview varchar(128);
ALTER TABLE llx_extrafields ADD COLUMN csslist varchar(128);

ALTER TABLE llx_cronjob ADD COLUMN email_alert varchar(128);

ALTER TABLE llx_paiement MODIFY COLUMN ext_payment_id varchar(255);
ALTER TABLE llx_payment_donation MODIFY COLUMN ext_payment_id varchar(255);
ALTER TABLE llx_prelevement_facture_demande MODIFY COLUMN ext_payment_id varchar(255);

INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (140, 'PCN2020-LUXEMBURG', 'Plan comptable normalisé 2020 Luxembourgeois', 1);

ALTER TABLE llx_cronjob MODIFY COLUMN label varchar(255) NOT NULL;

-- We need to keep only the PurgeDeleteTemporaryFilesShort with params = 'tempfilsold+logfiles'
DELETE FROM llx_cronjob WHERE label = 'PurgeDeleteTemporaryFilesShort' AND params = 'tempfilesold';

ALTER TABLE llx_cronjob DROP INDEX uk_cronjob;
ALTER TABLE llx_cronjob ADD UNIQUE INDEX uk_cronjob (label, entity);

ALTER TABLE llx_expedition ADD COLUMN billed smallint    DEFAULT 0;

ALTER TABLE llx_loan_schedule ADD UNIQUE INDEX uk_loan_schedule_ref (fk_loan, datep);

-- We need when upgrade 15 to 16 with Dolibarr v17+ for upgrade2 function migrate_user_photospath2()
ALTER TABLE llx_user CHANGE COLUMN note note_private text;

ALTER TABLE llx_overwrite_trans DROP INDEX uk_overwrite_trans;
ALTER TABLE llx_overwrite_trans ADD UNIQUE INDEX uk_overwrite_trans(lang, transkey, entity);


-- Bank Thirdparty
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, content, content_lines, joinfiles) VALUES (0, 'banque','thirdparty','',0,null,null,'(YourSEPAMandate)',1,'isModEnabled("societe") && isModEnabled("banque") && isModEnabled("prelevement")',0,'__(YourSEPAMandate)__','__(Hello)__,<br><br>\n\n__(FindYourSEPAMandate)__ :<br>\n__MYCOMPANY_NAME__<br>\n__MYCOMPANY_FULLADDRESS__<br><br>\n__(Sincerely)__<br>\n__USER_SIGNATURE__',null, 0);

-- Members
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, content, content_lines, joinfiles) VALUES (0, 'adherent','member','',0,null,null,'(SendingEmailOnAutoSubscription)'       ,10, 'isModEnabled("adherent")',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourMembershipRequestWasReceived)__','__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(ThisIsContentOfYourMembershipRequestWasReceived)__<br>\n<br>__ONLINE_PAYMENT_TEXT_AND_URL__<br>\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 0);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, content, content_lines, joinfiles) VALUES (0, 'adherent','member','',0,null,null,'(SendingEmailOnMemberValidation)'       ,20, 'isModEnabled("adherent")',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourMembershipWasValidated)__',      '__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(ThisIsContentOfYourMembershipWasValidated)__<br>__(FirstName)__ : __MEMBER_FIRSTNAME__<br>__(LastName)__ : __MEMBER_LASTNAME__<br>__(ID)__ : __MEMBER_ID__<br>\n<br>__ONLINE_PAYMENT_TEXT_AND_URL__<br>\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 0);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, content, content_lines, joinfiles) VALUES (0, 'adherent','member','',0,null,null,'(SendingEmailOnNewSubscription)'        ,30, 'isModEnabled("adherent")',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourSubscriptionWasRecorded)__',     '__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(ThisIsContentOfYourSubscriptionWasRecorded)__<br>\n\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 1);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, content, content_lines, joinfiles) VALUES (0, 'adherent','member','',0,null,null,'(SendingReminderForExpiredSubscription)',40, 'isModEnabled("adherent")',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(SubscriptionReminderEmail)__',       '__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(ThisIsContentOfSubscriptionReminderEmail)__<br>\n<br>__ONLINE_PAYMENT_TEXT_AND_URL__<br>\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 0);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, content, content_lines, joinfiles) VALUES (0, 'adherent','member','',0,null,null,'(SendingEmailOnCancelation)'            ,50, 'isModEnabled("adherent")',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourMembershipWasCanceled)__',       '__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(YourMembershipWasCanceled)__<br>\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 0);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, content, content_lines, joinfiles) VALUES (0, 'adherent','member','',0,null,null,'(SendingAnEMailToMember)'               ,60, 'isModEnabled("adherent")',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(CardContent)__',                     '__(Hello)__,<br><br>\n\n__(ThisIsContentOfYourCard)__<br>\n__(ID)__ : __ID__<br>\n__(Civility)__ : __MEMBER_CIVILITY__<br>\n__(Firstname)__ : __MEMBER_FIRSTNAME__<br>\n__(Lastname)__ : __MEMBER_LASTNAME__<br>\n__(Fullname)__ : __MEMBER_FULLNAME__<br>\n__(Company)__ : __MEMBER_COMPANY__<br>\n__(Address)__ : __MEMBER_ADDRESS__<br>\n__(Zip)__ : __MEMBER_ZIP__<br>\n__(Town)__ : __MEMBER_TOWN__<br>\n__(Country)__ : __MEMBER_COUNTRY__<br>\n__(Email)__ : __MEMBER_EMAIL__<br>\n__(Birthday)__ : __MEMBER_BIRTH__<br>\n__(Photo)__ : __MEMBER_PHOTO__<br>\n__(Login)__ : __MEMBER_LOGIN__<br>\n__(Phone)__ : __MEMBER_PHONE__<br>\n__(PhonePerso)__ : __MEMBER_PHONEPRO__<br>\n__(PhoneMobile)__ : __MEMBER_PHONEMOBILE__<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 0);

-- Recruiting
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, content, content_lines, joinfiles) VALUES (0, 'recruitment','recruitmentcandidature_send','',0,null,null,'(AnswerCandidature)'       ,100,'isModEnabled("recruitment")',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourCandidature)__',       '__(Hello)__ __CANDIDATE_FULLNAME__,<br><br>\n\n__(YourCandidatureAnswerMessage)__<br>__ONLINE_INTERVIEW_SCHEDULER_TEXT_AND_URL__\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 0);

-- Event organization 
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationEmailAskConf)',       10, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailAskConf)__', '__(Hello)__,<br /><br />__(OrganizationEventConfRequestWasReceived)__<br /><br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationEmailAskBooth)',      20, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailAskBooth)__', '__(Hello)__,<br /><br />__(OrganizationEventBoothRequestWasReceived)__<br /><br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
-- TODO Add message for registration only to event  __ONLINE_PAYMENT_TEXT_AND_URL__
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationEmailBoothPayment)',        30, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailBoothPayment)__', '__(Hello)__,<br /><br />__(OrganizationEventPaymentOfBoothWasReceived)__<br /><br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationEmailRegistrationPayment)', 40, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationEmailRegistrationPayment)__', '__(Hello)__,<br /><br />__(OrganizationEventPaymentOfRegistrationWasReceived)__<br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
--
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationMassEmailAttendees)', 50, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationMassEmailAttendees)__', '__(Hello)__,<br /><br />__(OrganizationEventBulkMailToAttendees)__<br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, content_lines, enabled, joinfiles) values (0, '', 'conferenceorbooth', '', 0, null, null, '(EventOrganizationMassEmailSpeakers)',  60, 1, '[__[MAIN_INFO_SOCIETE_NOM]__] __(EventOrganizationMassEmailSpeakers)__', '__(Hello)__,<br /><br />__(OrganizationEventBulkMailToSpeakers)__<br /><br />__(Sincerely)__<br />__USER_SIGNATURE__', null, '1', null);

-- Partnership
INSERT INTO llx_c_email_templates (entity, module, type_template, label, lang, position, topic, joinfiles, content) VALUES (0, 'partnership', 'partnership_send', '(SendingEmailOnPartnershipWillSoonBeCanceled)', '', 100, '[__[MAIN_INFO_SOCIETE_NOM]__] - __(YourPartnershipWillSoonBeCanceledTopic)__', 0, '<body>\n <p>__(Hello)__,<br><br>\n__(YourPartnershipWillSoonBeCanceledContent)__</p>\n<br />\n\n<br />\n\n            __(Sincerely)__ <br />\n            __[MAIN_INFO_SOCIETE_NOM]__ <br />\n </body>\n');
INSERT INTO llx_c_email_templates (entity, module, type_template, label, lang, position, topic, joinfiles, content) VALUES (0, 'partnership', 'partnership_send', '(SendingEmailOnPartnershipCanceled)', '', 100, '[__[MAIN_INFO_SOCIETE_NOM]__] - __(YourPartnershipCanceledTopic)__', 0, '<body>\n <p>__(Hello)__,<br><br>\n__(YourPartnershipCanceledContent)__</p>\n<br />\n\n<br />\n\n            __(Sincerely)__ <br />\n            __[MAIN_INFO_SOCIETE_NOM]__ <br />\n </body>\n');
INSERT INTO llx_c_email_templates (entity, module, type_template, label, lang, position, topic, joinfiles, content) VALUES (0, 'partnership', 'partnership_send', '(SendingEmailOnPartnershipRefused)', '', 100, '[__[MAIN_INFO_SOCIETE_NOM]__] - __(YourPartnershipRefusedTopic)__', 0, '<body>\n <p>__(Hello)__,<br><br>\n__(YourPartnershipRefusedContent)__</p>\n<br />\n\n<br />\n\n            __(Sincerely)__ <br />\n            __[MAIN_INFO_SOCIETE_NOM]__ <br />\n </body>\n');
INSERT INTO llx_c_email_templates (entity, module, type_template, label, lang, position, topic, joinfiles, content) VALUES (0, 'partnership', 'partnership_send', '(SendingEmailOnPartnershipAccepted)', '', 100, '[__[MAIN_INFO_SOCIETE_NOM]__] - __(YourPartnershipAcceptedTopic)__', 0, '<body>\n <p>__(Hello)__,<br><br>\n__(YourPartnershipAcceptedContent)__</p>\n<br />\n\n<br />\n\n            __(Sincerely)__ <br />\n            __[MAIN_INFO_SOCIETE_NOM]__ <br />\n </body>\n');
