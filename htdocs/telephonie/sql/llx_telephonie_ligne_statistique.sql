--
--
--
--
create table llx_telephonie_ligne_statistique (
  dates          date,
  statut         smallint,
  nb             integer,

  UNIQUE (dates, statut)
)type=innodb;

