--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.6.0 or higher. 
--


ALTER TABLE llx_don ADD COLUMN ref varchar(30) DEFAULT NULL AFTER rowid;
ALTER TABLE llx_don ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER ref;
