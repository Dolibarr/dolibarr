
--
-- Mise à jour de la version 0.2.3 à 0.3.0
--

--
-- Attention sur un catalogue produit important ce script peut-être long
-- à s'éxécuter
--

alter table llx_product modify tva_tx double default 19.6 ;

create table llx_cond_reglement
(
  rowid           integer PRIMARY KEY,
  sortorder       smallint,
  actif           tinyint default 1,
  libelle         varchar(255),
  libelle_facture text,
  fdm             tinyint,    -- reglement fin de mois
  nbjour          smallint
);

alter table llx_facture add fk_cond_reglement integer; 

alter table llx_facture add date_lim_reglement date ;

insert into llx_cond_reglement values (1,1,1, "A réception","Réception de facture",0,0);
insert into llx_cond_reglement values (2,2,1, "30 jours","Réglement à 30 jours",0,30);
insert into llx_cond_reglement values (3,3,1, "30 jours fin de mois","Réglement à 30 jours fin de mois",1,30);
insert into llx_cond_reglement values (4,4,1, "60 jours","Réglement à 60 jours",0,60);
insert into llx_cond_reglement values (5,5,1, "60 jours fin de mois","Réglement à 60 jours fin de mois",1,60);

update llx_facture set fk_cond_reglement = 1 where fk_cond_reglement IS NULL ;
update llx_facture set date_lim_reglement = datef where date_lim_reglement IS NULL ;

alter table llx_livre add frais_de_port tinyint default 1 ;