
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

alter table llx_facturedet modify fk_product integer NOT NULL default 0;

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

create table llx_facture_rec
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  titre              varchar(50) NOT NULL,
  fk_soc             integer NOT NULL,
  datec              datetime,  -- date de creation

  amount             real     default 0 NOT NULL,
  remise             real     default 0,
  remise_percent     real     default 0,
  tva                real     default 0,
  total              real     default 0,
  total_ttc          real     default 0,

  fk_user_author     integer,   -- createur
  fk_projet          integer,   -- projet auquel est associé la facture
  fk_cond_reglement  integer,   -- condition de reglement

  note               text,

  INDEX fksoc (fk_soc)
);


create table llx_facturedet_rec
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture      integer NOT NULL,
  fk_product      integer,
  description     text,
  tva_taux       real default 19.6, -- taux tva
  qty		 real,              -- quantité
  remise_percent real default 0,    -- pourcentage de remise
  remise         real default 0,    -- montant de la remise
  subprice       real,              -- prix avant remise
  price          real               -- prix final
);
