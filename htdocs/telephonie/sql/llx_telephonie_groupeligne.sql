--
-- Groupe de lignes
--
--
--
create table llx_telephonie_groupeligne (
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  nom              varchar(255),

  UNIQUE INDEX(nom)
)type=innodb;


create table llx_telephonie_groupe_ligne (
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  fk_groupe        integer NOT NULL,  -- groupe
  fk_ligne         integer NOT NULL,  -- ligne
  fk_user          integer NOT NULL,

  UNIQUE INDEX(fk_groupe, fk_ligne)
)type=innodb;


ALTER TABLE llx_telephonie_groupe_ligne ADD INDEX (fk_groupe);
ALTER TABLE llx_telephonie_groupe_ligne ADD INDEX (fk_ligne);
ALTER TABLE llx_telephonie_groupe_ligne ADD INDEX (fk_user);

ALTER TABLE llx_telephonie_groupe_ligne ADD FOREIGN KEY (fk_groupe) REFERENCES llx_telephonie_groupeligne(rowid);
ALTER TABLE llx_telephonie_groupe_ligne ADD FOREIGN KEY (fk_ligne)  REFERENCES llx_telephonie_societe_ligne(rowid);

ALTER TABLE llx_telephonie_groupe_ligne ADD FOREIGN KEY (fk_user)   REFERENCES llx_user(rowid);


