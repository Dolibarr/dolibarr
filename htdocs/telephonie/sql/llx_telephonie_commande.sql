
--
--
--
--
create table llx_telephonie_commande (
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  datec          datetime NOT NULL,
  fk_user_creat  integer NOT NULL,
  fk_fournisseur integer NOT NULL,
  filename       varchar(255) NOT NULL


)type=innodb;

ALTER TABLE llx_telephonie_commande ADD INDEX (fk_user_creat);
ALTER TABLE llx_telephonie_commande ADD INDEX (fk_fournisseur);

ALTER TABLE llx_telephonie_commande ADD FOREIGN KEY (fk_user_creat) REFERENCES llx_user (rowid);
ALTER TABLE llx_telephonie_commande ADD FOREIGN KEY (fk_fournisseur) REFERENCES llx_telephonie_fournisseur (rowid);
