
--
-- Mise à jour de la version 0.2.3 à 0.2.4
--

--
-- Attention sur un catalogue produit important ce script peut-être long
-- à s'éxécuter
--

alter table llx_product modify tva_tx double default 19.6 ;

create table llx_cond_reglement
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  sortorder       smallint,
  actif           tinyint default 1,
  libelle         varchar(255),
  libelle_facture text,
  fdm             tinyint,    -- reglement fin de mois
  nbjour          smallint
);
