
--
-- Mise à jour de la version 0.6.0 à 0.7.0
--

alter table llx_propal add remise_percent real default 0;
alter table llx_facture add remise_percent real default 0;

create table llx_product_price
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  fk_product      integer NOT NULL,
  date_price      datetime NOT NULL,
  price           double,
  tva_tx          double default 19.6,
  fk_user_author  integer,
  envente         tinyint default 1
);

REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_NEED_UPDATE',  '1','chaine',1);