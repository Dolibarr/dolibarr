--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.9.0 or higher. 
--
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To change type of field: ALTER TABLE llx_table MODIFY name varchar(60);
-- To remove a column:      ALTER TABLE llx_table DROP COLUMN colname;

ALTER TABLE llx_paiement MODIFY amount double(24,8); 
ALTER TABLE llx_paiement_facture MODIFY amount double(24,8); 

-- Fix bad old data
UPDATE llx_bank_url SET type='payment' WHERE type='?' AND label='(payment)' AND url LIKE '%compta/paiement/fiche.php%';

-- Add recuperableonly field
ALTER TABLE llx_product       add COLUMN recuperableonly integer NOT NULL DEFAULT '0' after tva_tx;
ALTER TABLE llx_product_price add COLUMN recuperableonly integer NOT NULL DEFAULT '0' after tva_tx;

-- Rename envente into tosell and add tobuy
ALTER TABLE llx_product CHANGE COLUMN envente tosell tinyint DEFAULT 1;
ALTER TABLE llx_product add COLUMN tobuy tinyint DEFAULT 1 after tosell;
ALTER TABLE llx_product_price CHANGE COLUMN envente tosell tinyint DEFAULT 1;
 
ALTER TABLE llx_bank MODIFY COLUMN fk_type varchar(6);

ALTER TABLE llx_boxes_def DROP INDEX uk_boxes_def;
ALTER TABLE llx_boxes_def MODIFY file varchar(200) NOT NULL;
ALTER TABLE llx_boxes_def MODIFY note varchar(130);
ALTER TABLE llx_boxes_def ADD UNIQUE INDEX uk_boxes_def (file, entity, note);

ALTER TABLE llx_notify_def MODIFY fk_contact integer NULL;
ALTER TABLE llx_notify_def ADD COLUMN fk_user integer NULL after fk_contact;
ALTER TABLE llx_notify_def ADD COLUMN type varchar(16) DEFAULT 'email';

ALTER TABLE llx_notify MODIFY fk_contact integer NULL;
ALTER TABLE llx_notify ADD COLUMN fk_user integer NULL after fk_contact;
ALTER TABLE llx_notify ADD COLUMN type varchar(16) DEFAULT 'email';

ALTER TABLE llx_actioncomm MODIFY label varchar(128) NOT NULL;

ALTER TABLE llx_expedition MODIFY date_expedition datetime;
ALTER TABLE llx_expedition MODIFY date_delivery datetime NULL;

ALTER TABLE llx_societe ADD COLUMN canvas varchar(32) NULL AFTER default_lang;
ALTER TABLE llx_societe MODIFY canvas varchar(32) NULL;

ALTER TABLE llx_cond_reglement RENAME TO llx_c_payment_term;
ALTER TABLE llx_expedition_methode RENAME TO llx_c_shipment_mode;

ALTER TABLE llx_facturedet_rec ADD COLUMN special_code integer UNSIGNED DEFAULT 0 AFTER total_ttc;
ALTER TABLE llx_facturedet_rec ADD COLUMN rang integer DEFAULT 0 AFTER special_code;

ALTER TABLE llx_actioncomm ADD COLUMN fk_supplier_order   integer;
ALTER TABLE llx_actioncomm ADD COLUMN fk_supplier_invoice integer;

ALTER TABLE llx_propaldet ADD COLUMN fk_parent_line	integer NULL AFTER fk_propal;
ALTER TABLE llx_commandedet ADD COLUMN fk_parent_line integer NULL AFTER fk_commande;
ALTER TABLE llx_facturedet ADD COLUMN fk_parent_line integer NULL AFTER fk_facture;
ALTER TABLE llx_facturedet_rec ADD COLUMN fk_parent_line integer NULL AFTER fk_facture;

-- Remove old Spanish TVA
UPDATE llx_c_tva SET taux = '18' WHERE rowid = 41;
UPDATE llx_c_tva SET taux = '8' WHERE rowid = 42;
DELETE FROM llx_c_tva WHERE rowid = 45;
DELETE FROM llx_c_tva WHERE rowid = 46;


ALTER TABLE llx_adherent  ADD COLUMN import_key varchar(14);
ALTER TABLE llx_categorie ADD COLUMN import_key varchar(14);


ALTER TABLE llx_product ADD COLUMN customcode varchar(32) after note;
ALTER TABLE llx_product ADD COLUMN fk_country integer after customcode; 


ALTER TABLE llx_ecm_directories ADD UNIQUE INDEX idx_ecm_directories (label, fk_parent, entity);
ALTER TABLE llx_ecm_documents ADD UNIQUE INDEX idx_ecm_documents (fullpath_dol);

-- Add modules facture fournisseur
INSERT INTO llx_const (name, value, type, note, visible) values ('INVOICE_SUPPLIER_ADDON_PDF', 'canelle','chaine','',0);
ALTER TABLE llx_facture_fourn ADD COLUMN model_pdf varchar(50) after note_public;

CREATE TABLE llx_c_ziptown
(
  rowid				integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code				varchar(5) DEFAULT NULL,
  fk_county			integer NOT NULL,
  zip	 			varchar(10) NOT NULL,
  town				varchar(255) NOT NULL,
  active 			tinyint NOT NULL DEFAULT 1
) ENGINE=innodb;

ALTER TABLE llx_c_ziptown ADD INDEX idx_c_ziptown_fk_county (fk_county);
ALTER TABLE llx_c_ziptown ADD CONSTRAINT fk_c_ziptown_fk_county		FOREIGN KEY (fk_county)   REFERENCES llx_c_departements (rowid);

ALTER TABLE llx_socpeople ADD COLUMN canvas varchar(32) NULL after default_lang;
ALTER TABLE llx_socpeople MODIFY canvas varchar(32) NULL;

UPDATE llx_socpeople SET canvas = 'default' WHERE canvas = 'default@contact';
UPDATE llx_societe SET canvas = 'default' WHERE canvas = 'default@thirdparty';
UPDATE llx_societe SET canvas = 'individual' WHERE canvas = 'individual@thirdparty';

INSERT INTO llx_const (name, value, type, note, visible) values ('MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS','7','chaine','Tolérance de retard avant alerte (en jours) sur commandes fournisseurs non traitées',0);

ALTER TABLE llx_actioncomm ADD COLUMN fulldayevent smallint NOT NULL default 0 after priority;

-- Enhance POS module
DROP TABLE llx_tmp_caisse;
CREATE TABLE llx_pos_tmp (
  id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fk_article integer NOT NULL,
  qte real NOT NULL,
  fk_tva integer NOT NULL,
  remise_percent real NOT NULL,
  remise real NOT NULL,
  total_ht double(24,8) NOT NULL,
  total_tva double(24,8) NOT NULL,
  total_ttc double(24,8) NOT NULL
) ENGINE=innodb;

-- Add external ref
ALTER TABLE llx_facture  ADD COLUMN ref_ext varchar(30) after entity;
ALTER TABLE llx_commande ADD COLUMN ref_ext varchar(30) after entity;
ALTER TABLE llx_propal   ADD COLUMN ref_ext varchar(30) after entity;
ALTER TABLE llx_user     ADD COLUMN ref_ext varchar(30) after entity;
ALTER TABLE llx_societe  ADD COLUMN ref_ext varchar(60) after entity;
ALTER TABLE llx_product  ADD COLUMN ref_ext varchar(30) after entity;


ALTER TABLE llx_mailing_cibles CHANGE COLUMN url source_url integer;
ALTER TABLE llx_mailing_cibles MODIFY source_url varchar(160);
ALTER TABLE llx_mailing_cibles ADD COLUMN source_id integer after source_url;
ALTER TABLE llx_mailing_cibles ADD COLUMN source_type varchar(16) after source_id;

ALTER TABLE llx_facture_rec DROP COLUMN frequency;
ALTER TABLE llx_facture_rec ADD COLUMN frequency          integer;
ALTER TABLE llx_facture_rec ADD COLUMN unit_frequency     varchar(2) DEFAULT 'd';
ALTER TABLE llx_facture_rec ADD COLUMN date_when          datetime DEFAULT NULL;
ALTER TABLE llx_facture_rec ADD COLUMN date_last_gen      datetime DEFAULT NULL;
ALTER TABLE llx_facture_rec ADD COLUMN nb_gen_done        integer DEFAULT NULL;
ALTER TABLE llx_facture_rec ADD COLUMN nb_gen_max         integer DEFAULT NULL;


ALTER TABLE llx_user ADD COLUMN openid varchar(255);

-- Enhance Withdrawal module
INSERT INTO llx_action_def (rowid,code,titre,description,objet_type) values (7,'NOTIFY_TRN_WITHDRAW','Transmit withdraw','Executed when a withdrawal is transmited','withdraw');
INSERT INTO llx_action_def (rowid,code,titre,description,objet_type) values (8,'NOTIFY_CRD_WITHDRAW','Credite withdraw','Executed when a withdrawal is credited','withdraw');
INSERT INTO llx_action_def (rowid,code,titre,description,objet_type) values (9,'NOTIFY_EMT_WITHDRAW','Emit withdraw','Executed when a withdrawal is emited','withdraw');

ALTER TABLE llx_prelevement_notifications MODIFY action varchar(32);

ALTER TABLE llx_c_tva ADD COLUMN accountancy_code varchar(15) DEFAULT NULL;


UPDATE llx_c_actioncomm set module='invoice_supplier' WHERE module='supplier_invoice';
UPDATE llx_c_actioncomm set module='order_supplier' WHERE module='supplier_order';
UPDATE llx_documentmodel set type='invoice_supplier' WHERE type='supplier_invoice';
UPDATE llx_documentmodel set type='order_supplier' WHERE type='supplier_order';
UPDATE llx_c_type_contact set element='invoice_supplier' WHERE element='facture_fourn';
UPDATE llx_c_type_contact set module='invoice_supplier' WHERE module='supplier_invoice';
UPDATE llx_c_type_contact set module='order_supplier' WHERE module='supplier_order';

ALTER TABLE llx_facturedet DROP INDEX uk_fk_remise_except;
ALTER TABLE llx_facturedet ADD UNIQUE INDEX uk_fk_remise_except (fk_remise_except, fk_facture);

ALTER TABLE llx_societe_remise MODIFY remise_client double(6,3) DEFAULT 0 NOT NULL;
