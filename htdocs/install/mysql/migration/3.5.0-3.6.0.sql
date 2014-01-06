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
ALTER TABLE llx_product ADD COLUMN todluo tinyint DEFAULT 0 NOT NULL;

CREATE TABLE IF NOT EXISTS `llx_product_dluo` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_product_stock` int(11) NOT NULL,
  `dluo` datetime DEFAULT NULL,
  `dlc` datetime DEFAULT NULL,
  `lot` varchar(30) DEFAULT NULL,
  `qty` double NOT NULL DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  KEY `ix_fk_product_stock` (`fk_product_stock`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `llx_expeditiondet_dluo` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `fk_expeditiondet` int(11) NOT NULL,
  `dlc` date DEFAULT NULL,
  `dluo` date DEFAULT NULL,
  `lot` varchar(30) DEFAULT NULL,
  `qty` double NOT NULL DEFAULT '0',
  `fk_origin_stock` int(11) NOT NULL,
  KEY `ix_fk_expeditiondet` (`fk_expeditiondet`)
) ENGINE=InnoDB;
