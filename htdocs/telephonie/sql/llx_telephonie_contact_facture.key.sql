--
--
--
--

ALTER TABLE llx_telephonie_contact_facture ADD INDEX (fk_contact);
ALTER TABLE llx_telephonie_contact_facture ADD INDEX (fk_ligne);

ALTER TABLE llx_telephonie_contact_facture ADD CONSTRAINT fk_telephonie_contact_facture_contact FOREIGN KEY (fk_contact) REFERENCES llx_socpeople (rowid);
ALTER TABLE llx_telephonie_contact_facture ADD CONSTRAINT fk_telephonie_contact_facture_ligne FOREIGN KEY (fk_ligne) REFERENCES llx_telephonie_societe_ligne (rowid);
