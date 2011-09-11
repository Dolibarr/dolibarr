--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.8.0 or higher. 
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To change type of field: ALTER TABLE llx_table MODIFY name varchar(60);
--

UPDATE llx_c_paper_format SET active=1 WHERE active=0;

ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_availability integer AFTER fk_product_fournisseur;

ALTER TABLE llx_element_element MODIFY COLUMN sourcetype varchar(32) NOT NULL;
ALTER TABLE llx_element_element MODIFY COLUMN targettype varchar(32) NOT NULL;

ALTER TABLE llx_user MODIFY ref_ext varchar(50);
ALTER TABLE llx_user ADD COLUMN ref_int varchar(50) AFTER ref_ext;

ALTER TABLE llx_societe MODIFY code_client varchar(24);
ALTER TABLE llx_societe MODIFY code_fournisseur varchar(24);

