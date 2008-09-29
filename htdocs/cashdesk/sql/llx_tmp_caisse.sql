
-- --------------------------------------------------------

-- 
-- Structure de la table `llx_tmp_caisse`
-- 

CREATE TABLE `llx_tmp_caisse` (
  `id` int(11) NOT NULL auto_increment,
  `fk_article` tinyint(4) NOT NULL,
  `qte` int(11) NOT NULL,
  `fk_tva` tinyint(4) NOT NULL,
  `remise_percent` int(11) NOT NULL,
  `remise` float NOT NULL,
  `total_ht` float NOT NULL,
  `total_ttc` float NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=innodb AUTO_INCREMENT=3 ;
