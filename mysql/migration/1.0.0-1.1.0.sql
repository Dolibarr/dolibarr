
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

alter table llx_facturedet modify fk_product NOT NULL default 0;

create table llx_product_fournisseur
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  tms             timestamp,
  fk_product      integer,
  fk_soc          integer,
  ref_fourn       varchar(30),
  fk_user_author  integer,

  key(fk_product),
  key(fk_soc)
);

