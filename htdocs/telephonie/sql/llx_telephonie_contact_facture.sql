--
--
--
--
create table llx_telephonie_contact_facture (
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_contact     integer NOT NULL,
  fk_ligne       integer NOT NULL,

  UNIQUE (fk_contact, fk_ligne)
)type=innodb;

ALTER TABLE llx_telephonie_contact_facture ADD INDEX (fk_contact);
ALTER TABLE llx_telephonie_contact_facture ADD INDEX (fk_ligne);

ALTER TABLE llx_telephonie_contact_facture ADD FOREIGN KEY (fk_contact) REFERENCES llx_socpeople (idp);
ALTER TABLE llx_telephonie_contact_facture ADD FOREIGN KEY (fk_ligne) REFERENCES llx_telephonie_societe_ligne (rowid);
