-- $Revision$
--
-- Attention à l ordre des requetes
-- ce fichier doit être chargé sur une version 2.0.0 
-- sans AUCUNE erreur ni warning
-- 


create table llx_commande_model_pdf
(
  nom         varchar(50) PRIMARY KEY,
  libelle     varchar(255),
  description text
)type=innodb;


alter table llx_commande add column note_public text after note;

alter table llx_contrat add column note text;
alter table llx_contrat add column note_public text after note;

alter table llx_facture add column note_public text after note;

alter table llx_propal add column note_public text after note;

ALTER TABLE llx_societe ADD mode_reglement INT( 11 ) DEFAULT NULL ;

ALTER TABLE llx_societe ADD cond_reglement INT( 11 ) DEFAULT '1' NOT NULL ;
