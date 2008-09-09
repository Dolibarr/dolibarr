--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.4.0 or higher. 
--

alter table llx_product add column   price_min          double(24,8) DEFAULT 0;
alter table llx_product add column   price_min_ttc      double(24,8) DEFAULT 0;

alter table llx_product_price   add column price_min              double(24,8) default NULL;
alter table llx_product_price   add column price_min_ttc          double(24,8) default NULL;

alter table llx_societe add column gencod			 varchar(255);

delete from llx_user_param where page <> '';

alter table llx_expedition add tracking_number varchar(50) after fk_expedition_methode;

alter table llx_actioncomm add column location varchar(128) after percent;


