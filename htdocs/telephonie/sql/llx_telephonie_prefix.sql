

create table llx_telephonie_prefix (
  rowid     integer AUTO_INCREMENT PRIMARY KEY,
  fk_tarif  integer NOT NULL,
  prefix    varchar(10),

  UNIQUE INDEX(prefix)
)type=innodb;

ALTER TABLE llx_telephonie_prefix ADD INDEX (fk_tarif);

ALTER TABLE llx_telephonie_prefix ADD FOREIGN KEY (fk_tarif) REFERENCES llx_telephonie_tarif (rowid);
