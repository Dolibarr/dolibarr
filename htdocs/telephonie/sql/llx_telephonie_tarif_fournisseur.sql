
--
--
--
--
create table llx_telephonie_tarif_fournisseur (
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_tarif       integer NOT NULL,
  fk_fournisseur integer NOT NULL,
  temporel       real default 0,
  fixe           real default 0,

  UNIQUE INDEX(fk_tarif, fk_fournisseur)
)type=innodb;

ALTER TABLE llx_telephonie_tarif_fournisseur ADD INDEX (fk_tarif);
ALTER TABLE llx_telephonie_tarif_fournisseur ADD INDEX (fk_fournisseur);

ALTER TABLE llx_telephonie_tarif_fournisseur ADD FOREIGN KEY (fk_tarif) REFERENCES llx_telephonie_tarif (rowid);
ALTER TABLE llx_telephonie_tarif_fournisseur ADD FOREIGN KEY (fk_fournisseur) REFERENCES llx_telephonie_fournisseur (rowid);
--
