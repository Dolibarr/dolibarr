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

ALTER TABLE llx_adherent MODIFY login varchar(50);

ALTER TABLE llx_c_actioncomm add COLUMN position integer NOT NULL DEFAULT 0;

ALTER TABLE llx_commande_fournisseur MODIFY model_pdf varchar(255);
ALTER TABLE llx_commande MODIFY model_pdf varchar(255);
ALTER TABLE llx_don MODIFY model_pdf varchar(255);
ALTER TABLE llx_expedition MODIFY model_pdf varchar(255);
ALTER TABLE llx_facture_fourn MODIFY model_pdf varchar(255);
ALTER TABLE llx_facture MODIFY model_pdf varchar(255);
ALTER TABLE llx_fichinter MODIFY model_pdf varchar(255);
ALTER TABLE llx_livraison MODIFY model_pdf varchar(255);
ALTER TABLE llx_projet MODIFY model_pdf varchar(255);
ALTER TABLE llx_propal MODIFY model_pdf varchar(255);


-- Delete old constants
DELETE from llx_const where NAME = 'MAIN_MENU_BARRETOP';
DELETE from llx_const where NAME = 'MAIN_MENUFRONT_BARRETOP';
DELETE from llx_const where NAME = 'MAIN_MENU_BARRELEFT';
DELETE from llx_const where NAME = 'MAIN_MENUFRONT_BARRELEFT';

ALTER TABLE llx_facture_fourn ADD column ref_ext varchar(30) after entity;
ALTER TABLE llx_commande_fournisseur ADD column ref_ext varchar(30) after entity;


ALTER TABLE llx_facturedet DROP INDEX uk_fk_remise_except;
ALTER TABLE llx_facturedet ADD UNIQUE INDEX uk_fk_remise_except (fk_remise_except, fk_facture);


