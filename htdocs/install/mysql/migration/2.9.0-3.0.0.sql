--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.8.0 or higher. 
--
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To change type of field: ALTER TABLE llx_table MODIFY name varchar(60);
--

-- Fix bad old data
UPDATE llx_bank_url SET type='payment' WHERE type='?' AND label='(payment)' AND url LIKE '%compta/paiement/fiche.php%';

-- Add recuperableonly field
alter table llx_product       add column recuperableonly integer NOT NULL DEFAULT '0' after tva_tx;
alter table llx_product_price add column recuperableonly integer NOT NULL DEFAULT '0' after tva_tx;

-- Rename envente into tosell and add tobuy
alter table llx_product change column envente tosell tinyint DEFAULT 1;
alter table llx_product add column tobuy tinyint DEFAULT 1 after tosell;
alter table llx_product_price change column envente tosell tinyint DEFAULT 1;
 

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

ALTER TABLE llx_societe ADD COLUMN canvas varchar(32) DEFAULT NULL AFTER default_lang;

ALTER TABLE llx_cond_reglement RENAME TO llx_c_payment_term;
ALTER TABLE llx_expedition_methode RENAME TO llx_c_shipment_mode;

ALTER TABLE llx_facturedet_rec ADD COLUMN special_code integer UNSIGNED DEFAULT 0 AFTER total_ttc;
ALTER TABLE llx_facturedet_rec ADD COLUMN rang integer DEFAULT 0 AFTER special_code;

ALTER TABLE llx_actioncomm ADD COLUMN fk_supplier_order   integer;
ALTER TABLE llx_actioncomm ADD COLUMN fk_supplier_invoice integer;


ALTER TABLE llx_tmp_caisse MODIFY fk_article integer NOT NULL;

ALTER TABLE llx_propaldet ADD COLUMN fk_parent_line	integer NULL AFTER fk_propal;
ALTER TABLE llx_commandedet ADD COLUMN fk_parent_line integer NULL AFTER fk_commande;
ALTER TABLE llx_facturedet ADD COLUMN fk_parent_line integer NULL AFTER fk_facture;
ALTER TABLE llx_facturedet_rec ADD COLUMN fk_parent_line integer NULL AFTER fk_facture;
