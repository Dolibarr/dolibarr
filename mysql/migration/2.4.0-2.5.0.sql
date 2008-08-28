--
-- $Id$
--
-- Attention à l ordre des requetes.
-- Ce fichier doit être chargé sur une version 2.4.0 
--

alter table llx_product add column   price_min          double(24,8) DEFAULT 0;
alter table llx_product add column   price_min_ttc      double(24,8) DEFAULT 0;

alter table llx_product_price   add column price_min              double(24,8) default NULL;
alter table llx_product_price   add column price_min_ttc          double(24,8) default NULL;

alter table llx_societe add column gencod			 varchar(255);


