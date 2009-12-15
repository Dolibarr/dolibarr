--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.7.0 or higher. 
--


ALTER TABLE llx_don ADD COLUMN ref varchar(30) DEFAULT NULL AFTER rowid;
ALTER TABLE llx_don ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;

ALTER TABLE llx_stock_mouvement ADD COLUMN label varchar(128);

ALTER TABLE llx_deplacement ADD COLUMN ref varchar(30) DEFAULT NULL AFTER rowid;
ALTER TABLE llx_deplacement ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;

ALTER TABLE llx_element_element DROP INDEX idx_element_element_idx1;
ALTER TABLE llx_element_element DROP INDEX idx_element_element_targetid;
ALTER TABLE llx_element_element CHANGE sourceid fk_source integer NOT NULL;
ALTER TABLE llx_element_element CHANGE targetid fk_target integer NOT NULL;
ALTER TABLE llx_element_element ADD UNIQUE INDEX idx_element_element_idx1 (fk_source, sourcetype, fk_target, targettype);
ALTER TABLE llx_element_element ADD INDEX idx_element_element_fk_target (fk_target);

ALTER TABLE llx_ecm_document RENAME TO llx_ecm_documents;
ALTER TABLE llx_ecm_documents ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;
ALTER TABLE llx_ecm_documents ADD COLUMN crc varchar(32) DEFAULT '' NOT NULL AFTER private;
ALTER TABLE llx_ecm_documents ADD COLUMN cryptkey varchar(50) DEFAULT '' NOT NULL AFTER crc;
ALTER TABLE llx_ecm_documents ADD COLUMN cipher varchar(50) DEFAULT 'twofish' NOT NULL AFTER cryptkey;