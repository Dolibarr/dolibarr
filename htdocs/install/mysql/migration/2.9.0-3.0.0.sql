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

-- Add recuperableonly field
alter table llx_product       add column recuperableonly integer NOT NULL DEFAULT '0' after tva_tx;
alter table llx_product_price add column recuperableonly integer NOT NULL DEFAULT '0' after tva_tx;

-- Rename envente into tosell and add tobuy
alter table llx_product change column envente tosell tinyint DEFAULT 1;
alter table llx_product add column tobuy tinyint DEFAULT 1 after tosell;
alter table llx_product_price change column envente tosell tinyint DEFAULT 1;
 