
--
-- Mise à jour de la version 1.0.0-RC1 à 1.0.0-RC2
--

create table llx_facture_tva_sum
(
  fk_facture    integer NOT NULL,
  amount        real  NOT NULL,
  tva_tx        real  NOT NULL,

  KEY(fk_facture)
);
