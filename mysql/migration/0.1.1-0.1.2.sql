
--
-- table llx_product
--

alter table llx_product modify price real ;

alter table llx_product add tva_tx real ;

update llx_product set tva_tx = 19.6 ;