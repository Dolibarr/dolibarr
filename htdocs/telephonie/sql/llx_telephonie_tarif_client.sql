--
create table llx_telephonie_tarif_client (
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_tarif       integer NOT NULL,
  fk_client      integer NOT NULL,
  temporel       real default 0,
  fixe           real default 0,
  fk_user        integer NOT NULL,

  UNIQUE INDEX(fk_tarif, fk_client)
)type=innodb;

ALTER TABLE llx_telephonie_tarif_client ADD INDEX (fk_tarif);
ALTER TABLE llx_telephonie_tarif_client ADD INDEX (fk_client);
ALTER TABLE llx_telephonie_tarif_client ADD INDEX (fk_user);

ALTER TABLE llx_telephonie_tarif_client ADD FOREIGN KEY (fk_tarif) REFERENCES llx_telephonie_tarif (rowid);
ALTER TABLE llx_telephonie_tarif_client ADD FOREIGN KEY (fk_client) REFERENCES llx_societe (idp);
ALTER TABLE llx_telephonie_tarif_client ADD FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);
