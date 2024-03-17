--
-- This file is executed by calling /install/index.php page
-- when current version is higher than the name of this file.
-- Be carefull in the position of each SQL request.
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


-- V18 forgotten

UPDATE llx_paiement SET ref = rowid WHERE ref IS NULL OR ref = '';


-- V19 forgotten

ALTER TABLE llx_resource ADD COLUMN phone varchar(255) DEFAULT NULL AFTER max_users;
ALTER TABLE llx_resource ADD COLUMN email varchar(255) DEFAULT NULL AFTER phone;
ALTER TABLE llx_resource ADD COLUMN url varchar(255) DEFAULT NULL AFTER email;
ALTER TABLE llx_resource ADD COLUMN fk_state integer DEFAULT NULL AFTER fk_country;
ALTER TABLE llx_resource ADD INDEX idx_resource_fk_state (fk_state);

UPDATE llx_c_type_contact SET element = 'stocktransfer' WHERE element = 'StockTransfer';


-- Use unique keys for extrafields
ALTER TABLE llx_actioncomm_extrafields DROP INDEX idx_actioncomm_extrafields;
ALTER TABLE llx_actioncomm_extrafields ADD UNIQUE INDEX uk_actioncomm_extrafields (fk_object);
ALTER TABLE llx_adherent_extrafields DROP INDEX idx_adherent_extrafields;
ALTER TABLE llx_adherent_extrafields ADD UNIQUE INDEX uk_adherent_extrafields (fk_object);
ALTER TABLE llx_adherent_type_extrafields DROP INDEX idx_adherent_type_extrafields;
ALTER TABLE llx_adherent_type_extrafields ADD UNIQUE INDEX uk_adherent_type_extrafields (fk_object);
ALTER TABLE llx_asset_model_extrafields DROP INDEX idx_asset_model_extrafields;
ALTER TABLE llx_asset_model_extrafields ADD UNIQUE INDEX uk_asset_model_extrafields (fk_object);
ALTER TABLE llx_bank_account_extrafields DROP INDEX idx_bank_account_extrafields;
ALTER TABLE llx_bank_account_extrafields ADD UNIQUE INDEX uk_bank_account_extrafields (fk_object);
ALTER TABLE llx_bank_extrafields DROP INDEX idx_bank_extrafields;
ALTER TABLE llx_bank_extrafields ADD UNIQUE INDEX uk_bank_extrafields (fk_object);
ALTER TABLE llx_bom_bom_extrafields DROP INDEX idx_bom_bom_extrafields_fk_object;
ALTER TABLE llx_bom_bom_extrafields ADD UNIQUE INDEX uk_bom_bom_extrafields_fk_object (fk_object);
ALTER TABLE llx_categories_extrafields DROP INDEX idx_categories_extrafields;
ALTER TABLE llx_categories_extrafields ADD UNIQUE INDEX uk_categories_extrafields (fk_object);
ALTER TABLE llx_commande_extrafields DROP INDEX idx_commande_extrafields;
ALTER TABLE llx_commande_extrafields ADD UNIQUE INDEX uk_commande_extrafields (fk_object);
ALTER TABLE llx_commande_fournisseur_dispatch_extrafields DROP INDEX idx_commande_fournisseur_dispatch_extrafields;
ALTER TABLE llx_commande_fournisseur_dispatch_extrafields ADD UNIQUE INDEX uk_commande_fournisseur_dispatch_extrafields (fk_object);
ALTER TABLE llx_commande_fournisseur_extrafields DROP INDEX idx_commande_fournisseur_extrafields;
ALTER TABLE llx_commande_fournisseur_extrafields ADD UNIQUE INDEX uk_commande_fournisseur_extrafields (fk_object);
ALTER TABLE llx_commande_fournisseurdet_extrafields DROP INDEX idx_commande_fournisseurdet_extrafields;
ALTER TABLE llx_commande_fournisseurdet_extrafields ADD UNIQUE INDEX uk_commande_fournisseurdet_extrafields (fk_object);
ALTER TABLE llx_commandedet_extrafields DROP INDEX idx_commandedet_extrafields;
ALTER TABLE llx_commandedet_extrafields ADD UNIQUE INDEX uk_commandedet_extrafields (fk_object);
ALTER TABLE llx_contrat_extrafields DROP INDEX idx_contrat_extrafields;
ALTER TABLE llx_contrat_extrafields ADD UNIQUE INDEX uk_contrat_extrafields (fk_object);
ALTER TABLE llx_contratdet_extrafields DROP INDEX idx_contratdet_extrafields;
ALTER TABLE llx_contratdet_extrafields ADD UNIQUE INDEX uk_contratdet_extrafields (fk_object);
ALTER TABLE llx_delivery_extrafields DROP INDEX idx_delivery_extrafields;
ALTER TABLE llx_delivery_extrafields ADD UNIQUE INDEX uk_delivery_extrafields (fk_object);
ALTER TABLE llx_deliverydet_extrafields DROP INDEX idx_deliverydet_extrafields;
ALTER TABLE llx_deliverydet_extrafields ADD UNIQUE INDEX uk_deliverydet_extrafields (fk_object);
ALTER TABLE llx_ecm_directories_extrafields DROP INDEX idx_ecm_directories_extrafields;
ALTER TABLE llx_ecm_directories_extrafields ADD UNIQUE INDEX uk_ecm_directories_extrafields (fk_object);
ALTER TABLE llx_ecm_files_extrafields DROP INDEX idx_ecm_files_extrafields;
ALTER TABLE llx_ecm_files_extrafields ADD UNIQUE INDEX uk_ecm_files_extrafields (fk_object);
ALTER TABLE llx_entrepot_extrafields DROP INDEX idx_entrepot_extrafields;
ALTER TABLE llx_entrepot_extrafields ADD UNIQUE INDEX uk_entrepot_extrafields (fk_object);
ALTER TABLE llx_eventorganization_conferenceorboothattendee_extrafields DROP INDEX idx_conferenceorboothattendee_fk_object;
ALTER TABLE llx_eventorganization_conferenceorboothattendee_extrafields ADD UNIQUE INDEX uk_conferenceorboothattendee_fk_object (fk_object);
ALTER TABLE llx_expedition_extrafields DROP INDEX idx_expedition_extrafields;
ALTER TABLE llx_expedition_extrafields ADD UNIQUE INDEX uk_expedition_extrafields (fk_object);
ALTER TABLE llx_expeditiondet_extrafields DROP INDEX idx_expeditiondet_extrafields;
ALTER TABLE llx_expeditiondet_extrafields ADD UNIQUE INDEX uk_expeditiondet_extrafields (fk_object);
ALTER TABLE llx_expensereport_extrafields DROP INDEX idx_expensereport_extrafields;
ALTER TABLE llx_expensereport_extrafields ADD UNIQUE INDEX uk_expensereport_extrafields (fk_object);
ALTER TABLE llx_facture_extrafields DROP INDEX idx_facture_extrafields;
ALTER TABLE llx_facture_extrafields ADD UNIQUE INDEX uk_facture_extrafields (fk_object);
ALTER TABLE llx_facture_fourn_det_extrafields DROP INDEX idx_facture_fourn_det_extrafields;
ALTER TABLE llx_facture_fourn_det_extrafields ADD UNIQUE INDEX uk_facture_fourn_det_extrafields (fk_object);
ALTER TABLE llx_facture_fourn_det_rec_extrafields DROP INDEX idx_facture_fourn_det_rec_extrafields;
ALTER TABLE llx_facture_fourn_det_rec_extrafields ADD UNIQUE INDEX uk_facture_fourn_det_rec_extrafields (fk_object);
ALTER TABLE llx_facture_fourn_extrafields DROP INDEX idx_facture_fourn_extrafields;
ALTER TABLE llx_facture_fourn_extrafields ADD UNIQUE INDEX uk_facture_fourn_extrafields (fk_object);
ALTER TABLE llx_facture_fourn_rec_extrafields DROP INDEX idx_facture_fourn_rec_extrafields;
ALTER TABLE llx_facture_fourn_rec_extrafields ADD UNIQUE INDEX uk_facture_fourn_rec_extrafields (fk_object);
ALTER TABLE llx_facture_rec_extrafields DROP INDEX idx_facture_rec_extrafields;
ALTER TABLE llx_facture_rec_extrafields ADD UNIQUE INDEX uk_facture_rec_extrafields (fk_object);
ALTER TABLE llx_facturedet_extrafields DROP INDEX idx_facturedet_extrafields;
ALTER TABLE llx_facturedet_extrafields ADD UNIQUE INDEX uk_facturedet_extrafields (fk_object);
ALTER TABLE llx_facturedet_rec_extrafields DROP INDEX idx_facturedet_rec_extrafields;
ALTER TABLE llx_facturedet_rec_extrafields ADD UNIQUE INDEX uk_facturedet_rec_extrafields (fk_object);
ALTER TABLE llx_fichinter_extrafields DROP INDEX idx_ficheinter_extrafields;
ALTER TABLE llx_fichinter_extrafields ADD UNIQUE INDEX uk_ficheinter_extrafields (fk_object);
ALTER TABLE llx_fichinterdet_extrafields DROP INDEX idx_ficheinterdet_extrafields;
ALTER TABLE llx_fichinterdet_extrafields ADD UNIQUE INDEX uk_ficheinterdet_extrafields (fk_object);
ALTER TABLE llx_holiday_extrafields DROP INDEX idx_holiday_extrafields;
ALTER TABLE llx_holiday_extrafields ADD UNIQUE INDEX uk_holiday_extrafields (fk_object);
ALTER TABLE llx_hrm_evaluation_extrafields DROP INDEX idx_evaluation_fk_object;
ALTER TABLE llx_hrm_evaluation_extrafields ADD UNIQUE INDEX uk_evaluation_fk_object (fk_object);
ALTER TABLE llx_hrm_evaluationdet_extrafields DROP INDEX idx_evaluationdet_fk_object;
ALTER TABLE llx_hrm_evaluationdet_extrafields ADD UNIQUE INDEX uk_evaluationdet_fk_object (fk_object);
ALTER TABLE llx_hrm_job_extrafields DROP INDEX idx_job_fk_object;
ALTER TABLE llx_hrm_job_extrafields ADD UNIQUE INDEX uk_job_fk_object (fk_object);
ALTER TABLE llx_hrm_skill_extrafields DROP INDEX idx_skill_fk_object;
ALTER TABLE llx_hrm_skill_extrafields ADD UNIQUE INDEX uk_skill_fk_object (fk_object);
ALTER TABLE llx_inventory_extrafields DROP INDEX idx_inventory_extrafields;
ALTER TABLE llx_inventory_extrafields ADD UNIQUE INDEX uk_inventory_extrafields (fk_object);
ALTER TABLE llx_knowledgemanagement_knowledgerecord_extrafields DROP INDEX idx_knowledgerecord_fk_object;
ALTER TABLE llx_knowledgemanagement_knowledgerecord_extrafields ADD UNIQUE INDEX uk_knowledgerecord_fk_object (fk_object);
ALTER TABLE llx_mrp_mo_extrafields DROP INDEX idx_mrp_mo_fk_object;
ALTER TABLE llx_mrp_mo_extrafields ADD UNIQUE INDEX uk_mrp_mo_fk_object (fk_object);
ALTER TABLE llx_mrp_production_extrafields DROP INDEX idx_mrp_production_fk_object;
ALTER TABLE llx_mrp_production_extrafields ADD UNIQUE INDEX uk_mrp_production_fk_object (fk_object);
ALTER TABLE llx_partnership_extrafields DROP INDEX idx_partnership_extrafields;
ALTER TABLE llx_partnership_extrafields ADD UNIQUE INDEX uk_partnership_extrafields (fk_object);
ALTER TABLE llx_product_extrafields DROP INDEX idx_product_extrafields;
ALTER TABLE llx_product_extrafields ADD UNIQUE INDEX uk_product_extrafields (fk_object);
ALTER TABLE llx_product_fournisseur_price_extrafields DROP INDEX idx_product_fournisseur_price_extrafields;
ALTER TABLE llx_product_fournisseur_price_extrafields ADD UNIQUE INDEX uk_product_fournisseur_price_extrafields (fk_object);
ALTER TABLE llx_product_lot_extrafields DROP INDEX idx_product_lot_extrafields;
ALTER TABLE llx_product_lot_extrafields ADD UNIQUE INDEX uk_product_lot_extrafields (fk_object);
ALTER TABLE llx_projet_extrafields DROP INDEX idx_projet_extrafields;
ALTER TABLE llx_projet_extrafields ADD UNIQUE INDEX uk_projet_extrafields (fk_object);
ALTER TABLE llx_projet_task_extrafields DROP INDEX idx_projet_task_extrafields;
ALTER TABLE llx_projet_task_extrafields ADD UNIQUE INDEX uk_projet_task_extrafields (fk_object);
ALTER TABLE llx_propal_extrafields DROP INDEX idx_propal_extrafields;
ALTER TABLE llx_propal_extrafields ADD UNIQUE INDEX uk_propal_extrafields (fk_object);
ALTER TABLE llx_propaldet_extrafields DROP INDEX idx_propaldet_extrafields;
ALTER TABLE llx_propaldet_extrafields ADD UNIQUE INDEX uk_propaldet_extrafields (fk_object);
ALTER TABLE llx_reception_extrafields DROP INDEX idx_reception_extrafields;
ALTER TABLE llx_reception_extrafields ADD UNIQUE INDEX uk_reception_extrafields (fk_object);
ALTER TABLE llx_recruitment_recruitmentcandidature_extrafields DROP INDEX idx_recruitmentcandidature_fk_object;
ALTER TABLE llx_recruitment_recruitmentcandidature_extrafields ADD UNIQUE INDEX uk_recruitmentcandidature_fk_object (fk_object);
ALTER TABLE llx_recruitment_recruitmentjobposition_extrafields DROP INDEX idx_recruitmentjobposition_fk_object;
ALTER TABLE llx_recruitment_recruitmentjobposition_extrafields ADD UNIQUE INDEX uk_recruitmentjobposition_fk_object (fk_object);
ALTER TABLE llx_resource_extrafields DROP INDEX idx_resource_extrafields;
ALTER TABLE llx_resource_extrafields ADD UNIQUE INDEX uk_resource_extrafields (fk_object);
ALTER TABLE llx_salary_extrafields DROP INDEX idx_salary_extrafields;
ALTER TABLE llx_salary_extrafields ADD UNIQUE INDEX uk_salary_extrafields (fk_object);
ALTER TABLE llx_socpeople_extrafields DROP INDEX idx_socpeople_extrafields;
ALTER TABLE llx_socpeople_extrafields ADD UNIQUE INDEX uk_socpeople_extrafields (fk_object);
ALTER TABLE llx_stock_mouvement_extrafields DROP INDEX idx_stock_mouvement_extrafields;
ALTER TABLE llx_stock_mouvement_extrafields ADD UNIQUE INDEX uk_stock_mouvement_extrafields (fk_object);
ALTER TABLE llx_supplier_proposal_extrafields DROP INDEX idx_supplier_proposal_extrafields;
ALTER TABLE llx_supplier_proposal_extrafields ADD UNIQUE INDEX uk_supplier_proposal_extrafields (fk_object);
ALTER TABLE llx_supplier_proposaldet_extrafields DROP INDEX idx_supplier_proposaldet_extrafields;
ALTER TABLE llx_supplier_proposaldet_extrafields ADD UNIQUE INDEX uk_supplier_proposaldet_extrafields (fk_object);
ALTER TABLE llx_ticket_extrafields DROP INDEX idx_ticket_extrafields;
ALTER TABLE llx_ticket_extrafields ADD UNIQUE INDEX uk_ticket_extrafields (fk_object);
ALTER TABLE llx_user_extrafields DROP INDEX idx_user_extrafields;
ALTER TABLE llx_user_extrafields ADD UNIQUE INDEX uk_user_extrafields (fk_object);
ALTER TABLE llx_usergroup_extrafields DROP INDEX idx_usergroup_extrafields;
ALTER TABLE llx_usergroup_extrafields ADD UNIQUE INDEX uk_usergroup_extrafields (fk_object);

ALTER TABLE llx_website ADD COLUMN name_template varchar(255) NULL;
ALTER TABLE llx_website ADD COLUMN lastpageid integer DEFAULT 0;

UPDATE llx_categorie SET date_creation = tms, tms = tms WHERE date_creation IS NULL AND tms IS NOT NULL;

ALTER TABLE llx_product_price ADD COLUMN price_label varchar(255) AFTER fk_user_author;
ALTER TABLE llx_product_customer_price_log ADD COLUMN price_label varchar(255) AFTER fk_user;
ALTER TABLE llx_product_customer_price ADD COLUMN price_label varchar(255) AFTER fk_user;
ALTER TABLE llx_product ADD COLUMN price_label varchar(255) AFTER price_base_type;


CREATE TABLE llx_product_thirdparty
(
    rowid                               integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    fk_product                          integer NOT NULL,
    fk_soc                              integer NOT NULL,
    fk_product_thirdparty_relation_type integer NOT NULL,
    date_start                          datetime,
    date_end                            datetime,
    fk_project                          integer,
    description                         text,
    note_public                         text,
    note_private                        text,
    date_creation                       datetime NOT NULL,
    tms                                 timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat                       integer NOT NULL,
    fk_user_modif                       integer,
    last_main_doc                       varchar(255),
    import_key                          varchar(14),
    model_pdf                           varchar(255),
    status                              integer DEFAULT 1 NOT NULL
) ENGINE = innodb;


CREATE TABLE llx_c_product_thirdparty_relation_type
(
    rowid  integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    code   varchar(24) NOT NULL,
    label  varchar(128),
    active tinyint default 1 NOT NULL
) ENGINE = innodb;


ALTER TABLE llx_c_tva ADD COLUMN type_vat smallint NOT NULL DEFAULT 0 AFTER fk_pays;

ALTER TABLE llx_categorie ADD COLUMN position integer DEFAULT 0 AFTER color;

ALTER TABLE llx_product DROP COLUMN onportal;

ALTER TABLE llx_product ADD COLUMN last_main_doc varchar(255);

ALTER TABLE llx_knowledgemanagement_knowledgerecord MODIFY COLUMN answer longtext;

-- Rename const to add customer categories on not customer/prospect third-party if enabled
UPDATE llx_const SET name = 'THIRDPARTY_CAN_HAVE_CUSTOMER_CATEGORY_EVEN_IF_NOT_CUSTOMER_PROSPECT' WHERE name = 'THIRDPARTY_CAN_HAVE_CATEGORY_EVEN_IF_NOT_CUSTOMER_PROSPECT_SUPPLIER';

ALTER TABLE llx_fichinter ADD COLUMN signed_status smallint DEFAULT NULL AFTER duree;
ALTER TABLE llx_contrat ADD COLUMN signed_status smallint DEFAULT NULL AFTER date_contrat;

ALTER TABLE llx_mailing ADD COLUMN messtype	varchar(16) DEFAULT 'email' after rowid;

ALTER TABLE llx_ticket ADD COLUMN model_pdf varchar(255);
ALTER TABLE llx_ticket ADD COLUMN last_main_doc varchar(255);
ALTER TABLE llx_ticket ADD COLUMN extraparams varchar(255);
ALTER TABLE llx_ticket ADD COLUMN origin_replyto varchar(128);

ALTER TABLE llx_expensereport MODIFY COLUMN model_pdf varchar(255) DEFAULT NULL;
ALTER TABLE llx_fichinter_rec MODIFY COLUMN modelpdf varchar(255) DEFAULT NULL;
ALTER TABLE llx_societe ADD COLUMN geolat double(24,8) DEFAULT NULL;
ALTER TABLE llx_societe ADD COLUMN geolong double(24,8) DEFAULT NULL;
ALTER TABLE llx_societe ADD COLUMN geopoint point DEFAULT NULL;
ALTER TABLE llx_societe ADD COLUMN georesultcode varchar(16) NULL;

ALTER TABLE llx_socpeople ADD COLUMN geolat double(24,8) DEFAULT NULL;
ALTER TABLE llx_socpeople ADD COLUMN geolong double(24,8) DEFAULT NULL;
ALTER TABLE llx_socpeople ADD COLUMN geopoint point DEFAULT NULL;
ALTER TABLE llx_socpeople ADD COLUMN georesultcode varchar(16) NULL;

ALTER TABLE llx_socpeople ADD COLUMN name_alias varchar(255) NULL;

-- Supplier
INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, content, content_lines, joinfiles) VALUES (0, 'supplier_invoice','invoice_supplier_send','',0,null,null,'(SendingReminderEmailOnUnpaidSupplierInvoice)',100, 'isModEnabled("supplier_invoice")',1,'[__[MAIN_INFO_SOCIETE_NOM]__] - __(SupplierInvoice)__','__(Hello)__,<br /><br />__(SupplierInvoiceUnpaidContent)__<br />__URL_SUPPLIER_INVOICE__<br /><br />__(Sincerely)__<br />__USER_SIGNATURE__',null, 0);


ALTER TABLE llx_societe ADD COLUMN phone_mobile varchar(20) after phone;

ALTER TABLE llx_facture ADD INDEX idx_facture_tms (tms);
ALTER TABLE llx_facture_fourn ADD INDEX idx_facture_fourn_tms (tms);

ALTER TABLE llx_element_element MODIFY COLUMN sourcetype VARCHAR(64) NOT NULL;
ALTER TABLE llx_element_element MODIFY COLUMN targettype VARCHAR(64) NOT NULL;
ALTER TABLE llx_c_type_contact MODIFY COLUMN element VARCHAR(64) NOT NULL;

ALTER TABLE llx_product_association ADD COLUMN import_key varchar(14) DEFAULT NULL;

ALTER TABLE llx_ticket ADD COLUMN barcode varchar(255) DEFAULT NULL after extraparams;
ALTER TABLE llx_ticket ADD COLUMN fk_barcode_type integer DEFAULT NULL after barcode;

ALTER TABLE llx_ticket ADD UNIQUE INDEX uk_ticket_barcode_barcode_type (barcode, fk_barcode_type, entity);
ALTER TABLE llx_ticket ADD CONSTRAINT llx_ticket_fk_product_barcode_type FOREIGN KEY (fk_barcode_type) REFERENCES  llx_c_barcode_type (rowid);

-- Force INVOICE_USE_SITUATION to value 2 if exist
UPDATE llx_const SET value = 2 WHERE __DECRYPT('name')__ = 'INVOICE_USE_SITUATION' AND __DECRYPT('value')__ = '1';
