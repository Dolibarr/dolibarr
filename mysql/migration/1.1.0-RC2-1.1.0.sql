
--
-- Mise à jour de la version 1.1.0-RC2 à 1.1.0
--

insert into llx_const(name, value, type, note) values ('MAIN_MONNAIE','euros','chaine','Monnaie');

drop table if exists llx_entrepot;
drop table if exists llx_product_stock;
drop table if exists llx_stock;
drop table if exists llx_stock_mouvement;

