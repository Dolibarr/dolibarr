
--
-- Mise à jour de la version 1.1.0-RC1 à 1.1.0-RC2
--

delete from llx_boxes_def;

delete from llx_boxes;

update llx_facturedet set subprice = price where remise_percent = 0 ;

drop table if exists llx_entrepot;
drop table if exists llx_product_stock;
drop table if exists llx_stock;
drop table if exists llx_stock_mouvement;

create table llx_bank_url
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_bank         integer,
  url_id          integer,
  url             varchar(255),
  label           varchar(255)
);
