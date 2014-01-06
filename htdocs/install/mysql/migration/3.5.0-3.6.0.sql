--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.5.0 or higher. 
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):   VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres) VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE

-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);

ALTER TABLE llx_bookmark ADD COLUMN entity integer DEFAULT 1 NOT NULL;
ALTER TABLE  llx_opensurvey_sondage ADD COLUMN allow_comments TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT 1 AFTER  canedit ;
-- ALTER TABLE  llx_opensurvey_sondage DROP COLUMN survey_link_visible ;
ALTER TABLE  llx_opensurvey_sondage DROP INDEX  idx_id_sondage_admin ;
-- ALTER TABLE  llx_opensurvey_sondage DROP COLUMN id_sondage_admin ;
-- ALTER TABLE  llx_opensurvey_sondage DROP COLUMN canedit ;
ALTER TABLE  llx_opensurvey_sondage ADD COLUMN allow_spy TINYINT( 1 ) UNSIGNED NOT NULL AFTER  allow_comments ;
-- ALTER TABLE  llx_opensurvey_sondage DROP COLUMN origin ;
ALTER TABLE  llx_opensurvey_sondage ADD COLUMN fk_user_creat INT( 11 ) UNSIGNED NOT NULL AFTER  nom_admin ;
ALTER TABLE  llx_opensurvey_sondage CHANGE COLUMN mailsonde  mailsonde TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0';
ALTER TABLE  llx_opensurvey_sondage CHANGE COLUMN titre  titre TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE  llx_opensurvey_sondage CHANGE COLUMN date_fin  date_fin DATETIME NOT NULL;
ALTER TABLE  llx_opensurvey_sondage CHANGE COLUMN format  format VARCHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;