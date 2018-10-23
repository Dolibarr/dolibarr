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


-- For 9.0
ALTER TABLE llx_extrafields ADD COLUMN help text NULL;
ALTER TABLE llx_extrafields ADD COLUMN totalizable boolean DEFAULT FALSE after list;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN desc_fourn text after ref_fourn;


ALTER TABLE llx_user ADD COLUMN dateemploymentend date after dateemployment;


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

ALTER TABLE llx_payment_salary ADD COLUMN fk_projet integer DEFAULT NULL after amount;

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


CREATE TABLE llx_takepos_floor_tables(
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    entity integer DEFAULT 1 NOT NULL,
    label varchar(255),
    leftpos float,
    toppos float,
    floor smallint
) ENGINE=innodb;


UPDATE llx_c_payment_term SET decalage = nbjour, nbjour = 0 where decalage IS NULL AND type_cdr = 2;

-- Reception

ALTER TABLE llx_commande_fournisseur_dispatch ADD COLUMN fk_reception integer DEFAULT NULL;
ALTER TABLE llx_commande_fournisseur_dispatch CHANGE comment comment TEXT;
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECEPTION_VALIDATE','Reception validated','Executed when a reception is validated','reception',22);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECEPTION_SENTBYMAIL','Reception sent by mail','Executed when a reception is sent by mail','reception',22);

create table llx_commande_fournisseur_dispatch_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_commande_fournisseur_dispatch_extrafields ADD INDEX idx_commande_fournisseur_dispatch_extrafields (fk_object);


create table llx_reception
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  ref                   varchar(30)        NOT NULL,
  entity                integer  DEFAULT 1 NOT NULL,	-- multi company id
  fk_soc                integer            NOT NULL,
  fk_projet  		integer  DEFAULT NULL,
  
  ref_ext               varchar(30),					-- reference into an external system (not used by dolibarr)
  ref_int				varchar(30),					-- reference into an internal system (used by dolibarr to store extern id like paypal info)
  ref_supplier          varchar(30),					-- customer number
  
  date_creation         datetime,						-- date de creation
  fk_user_author        integer,						-- author of creation
  fk_user_modif         integer,						-- author of last change
  date_valid            datetime,						-- date de validation
  fk_user_valid         integer,						-- valideur
  date_delivery			datetime	DEFAULT NULL,		-- date planned of delivery
  date_reception       datetime,						
  fk_shipping_method    integer,
  tracking_number       varchar(50),
  fk_statut             smallint	DEFAULT 0,			-- 0 = draft, 1 = validated, 2 = billed or closed depending on WORKFLOW_BILL_ON_SHIPMENT option
  billed                smallint    DEFAULT 0,
  
  height                float,							-- height
  width                 float,							-- with
  size_units            integer,						-- unit of all sizes (height, width, depth)
  size                  float,							-- depth
  weight_units          integer,						-- unit of weight
  weight                float,							-- weight
  note_private          text,
  note_public           text,
  model_pdf             varchar(255),
  fk_incoterms          integer,						-- for incoterms
  location_incoterms    varchar(255),					-- for incoterms
  
  import_key			varchar(14),
  extraparams			varchar(255)							-- for other parameters with json format
)ENGINE=innodb;

ALTER TABLE llx_reception ADD UNIQUE INDEX idx_reception_uk_ref (ref, entity);

ALTER TABLE llx_reception ADD INDEX idx_reception_fk_soc (fk_soc);
ALTER TABLE llx_reception ADD INDEX idx_reception_fk_user_author (fk_user_author);
ALTER TABLE llx_reception ADD INDEX idx_reception_fk_user_valid (fk_user_valid);
ALTER TABLE llx_reception ADD INDEX idx_reception_fk_shipping_method (fk_shipping_method);

ALTER TABLE llx_reception ADD CONSTRAINT fk_reception_fk_soc				FOREIGN KEY (fk_soc)			 REFERENCES llx_societe (rowid);
ALTER TABLE llx_reception ADD CONSTRAINT fk_reception_fk_user_author		FOREIGN KEY (fk_user_author)	 REFERENCES llx_user (rowid);
ALTER TABLE llx_reception ADD CONSTRAINT fk_reception_fk_user_valid 		FOREIGN KEY (fk_user_valid)		 REFERENCES llx_user (rowid);
ALTER TABLE llx_reception ADD CONSTRAINT fk_reception_fk_shipping_method 	FOREIGN KEY (fk_shipping_method) REFERENCES llx_c_shipment_mode (rowid);

create table llx_reception_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_reception_extrafields ADD INDEX idx_reception_extrafields (fk_object);

ALTER TABLE llx_commande_fournisseur_dispatch ADD INDEX idx_commande_fournisseur_dispatch_fk_reception (fk_reception);
ALTER TABLE llx_commande_fournisseur_dispatch ADD CONSTRAINT fk_commande_fournisseur_dispatch_fk_reception FOREIGN KEY (fk_reception) REFERENCES llx_reception (rowid);


