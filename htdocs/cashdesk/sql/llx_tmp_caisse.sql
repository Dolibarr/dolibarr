
-- --------------------------------------------------------

-- 
-- Structure de la table `llx_tmp_caisse`
-- 

CREATE TABLE llx_tmp_caisse (
  id integer NOT NULL auto_increment,
  fk_article integer NOT NULL,
  qte integer NOT NULL,
  fk_tva integer NOT NULL,
  remise_percent integer NOT NULL,
  remise float NOT NULL,
  total_ht float NOT NULL,
  total_ttc float NOT NULL,
  PRIMARY KEY (id)
) ENGINE=innodb;
