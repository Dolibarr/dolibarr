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

ALTER TABLE llx_c_actioncomm add COLUMN position integer NOT NULL DEFAULT 0;

-- Delete old constants
DELETE from llx_const where NAME = 'MAIN_MENU_BARRETOP';
DELETE from llx_const where NAME = 'MAIN_MENUFRONT_BARRETOP';
DELETE from llx_const where NAME = 'MAIN_MENU_BARRELEFT';
DELETE from llx_const where NAME = 'MAIN_MENUFRONT_BARRELEFT';


