
--
-- Mise à jour de la version 1.0.0 à 1.1.0
--

alter table llx_propaldet modify qty real;
alter table llx_facturedet modify qty real;

alter table llx_propaldet add remise_percent real default 0;
alter table llx_propaldet add remise real default 0;
alter table llx_propaldet add subprice real default 0;

alter table llx_facturedet add remise_percent real default 0;
alter table llx_facturedet add remise real default 0;
alter table llx_facturedet add subprice real default 0;
