
--
-- Mise à jour de la version 1.0.0-RC1 à 1.0.0-RC2
--

create table llx_facture_tva_sum
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture    integer NOT NULL,
  amount        real,
  tva_tx        real default 19.6,

  KEY(fk_facture)
);
