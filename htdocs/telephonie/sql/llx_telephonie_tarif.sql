create table llx_telephonie_tarif (
  rowid     integer AUTO_INCREMENT PRIMARY KEY,
  libelle   varchar(255),

  UNIQUE INDEX(libelle)
)type=innodb;

