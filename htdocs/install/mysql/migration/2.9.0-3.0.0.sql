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
ALTER TABLE llx_boxes_def MODIFY note varchar(255);
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
--UPDATE llx_societe SET canvas='default' WHERE canvas IS NULL;
--UPDATE llx_societe SET canvas='default' WHERE fk_typent <> 8;
--UPDATE llx_societe SET canvas='individual' WHERE fk_typent = 8;

RENAME TABLE llx_cond_reglement TO llx_c_payment_term;
