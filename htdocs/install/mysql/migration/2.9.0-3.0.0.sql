--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.8.0 or higher. 
--


alter table llx_product       add column recuperableonly integer NOT NULL DEFAULT '0' after tva_tx;

alter table llx_product_price add column recuperableonly integer NOT NULL DEFAULT '0' after tva_tx;

 
alter table llx_product change column envente tosell tinyint DEFAULT 1;
alter table llx_product add column tobuy tinyint DEFAULT 1 after tosell;
 