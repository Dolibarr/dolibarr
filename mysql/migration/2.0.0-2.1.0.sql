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
ALTER TABLE llx_societe ADD tva_assuj tinyint;
ALTER TABLE llx_societe MODIFY tva_assuj tinyint;

alter table llx_product add gencode varchar(255) DEFAULT NULL;

insert into llx_cond_reglement(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (6,'PROFORMA',    6,1, 'Proforma','Réglement avant livraison',0,0);

alter table llx_commande add fk_cond_reglement int(11) DEFAULT NULL;
alter table llx_commande add fk_mode_reglement int(11) DEFAULT NULL;

create table llx_comfourn_facfourn
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande integer NOT NULL,
  fk_facture  integer NOT NULL,

  key(fk_commande),
  key(fk_facture)
)type=innodb;


create table llx_commande_fournisseur_model_pdf
(
  nom         varchar(50) PRIMARY KEY,
  libelle     varchar(255),
  description text
)type=innodb;

alter table llx_categorie add fk_statut smallint DEFAULT 0;


alter table llx_actioncomm modify datea datetime;
alter table llx_actioncomm add column datec datetime after id;
alter table llx_actioncomm add column datep datetime after datec;
alter table llx_actioncomm add column tms timestamp after datea;
update llx_actioncomm set datec = datea where datec is null;
update llx_actioncomm set datep = datea where datep is null;


create table llx_expedition_model_pdf
(
  nom         varchar(50) PRIMARY KEY,
  libelle     varchar(255),
  description text
)type=innodb;

create table llx_product_det
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_product     integer      DEFAULT 0 NOT NULL,
  lang           varchar(5)   DEFAULT 0 NOT NULL,
  label          varchar(128),
  description    varchar(255),
  note           text
)type=innodb;

ALTER TABLE `llx_propal` ADD `date_livraison` DATE;
ALTER TABLE `llx_commande` ADD `date_livraison` DATE;

ALTER TABLE llx_facture_fourn_det ADD INDEX idx_facture_fourn_det_fk_facture (fk_facture_fourn);
ALTER TABLE llx_facture_fourn_det ADD CONSTRAINT fk_facture_fourn_det_fk_facture FOREIGN KEY (fk_facture_fourn) REFERENCES llx_facture_fourn (rowid);


ALTER TABLE llx_commande ADD INDEX idx_commande_fk_soc (fk_soc);
ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe (idp);

ALTER TABLE llx_commande_fournisseur ADD INDEX idx_commande_fournisseur_fk_soc (fk_soc);
ALTER TABLE llx_commande_fournisseur ADD CONSTRAINT fk_commande_fournisseur_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe (idp);


alter table llx_commande_fournisseur add note_public text after note;


create table llx_avoir_model_pdf
(
  nom         varchar(50) PRIMARY KEY,
  libelle     varchar(255),
  description text
)type=innodb;


drop table if exists llx_soc_recontact;
