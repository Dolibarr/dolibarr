
--
-- Mise à jour de la version 0.3.0 à 0.4.0
--

alter table llx_product add fk_product_type integer default 0 ;
alter table llx_product add duration varchar(6) ;

create table llx_contrat
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  enservice       smallint default 0,
  mise_en_service datetime,
  fin_validite    datetime,
  date_cloture    datetime,
  fk_soc          integer NOT NULL,
  fk_product      integer NOT NULL,
  fk_facture      integer NOT NULL default 0,
  fk_user_author  integer NOT NULL,
  fk_user_mise_en_service integer NOT NULL,
  fk_user_cloture integer NOT NULL
);

