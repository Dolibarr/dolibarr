--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.4.0 or higher. 
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y


-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);

create table llx_adherent_type_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;
ALTER TABLE llx_adherent_type_extrafields ADD INDEX idx_adherent_type_extrafields (fk_object);

UPDATE llx_const set value='eldy_menu.php' where value='eldy_backoffice.php';
UPDATE llx_const set value='eldy_menu.php' where value='eldy_frontoffice.php';
UPDATE llx_const set value='auguria_menu.php' where value='auguria_backoffice.php';
UPDATE llx_const set value='auguria_menu.php' where value='auguria_frontoffice.php';
UPDATE llx_const set value='smartphone_menu.php' where value='smartphone_backoffice.php';
UPDATE llx_const set value='smartphone_menu.php' where value='smartphone_frontoffice.php';

