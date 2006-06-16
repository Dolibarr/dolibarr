-- $Revision$
--
-- Attention à l ordre des requetes
-- ce fichier doit être chargé sur une version 2.0.0 
-- sans AUCUNE erreur ni warning
-- 


create table llx_paiementfourn_facturefourn
(
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  fk_paiementfourn INT(11) DEFAULT NULL,
  fk_facturefourn  INT(11) DEFAULT NULL,
  amount DOUBLE DEFAULT '0'
) TYPE=innodb;
ALTER TABLE llx_paiementfourn_facturefourn ADD INDEX idx_paiementfourn_facturefourn_fk_facture(fk_facturefourn);
ALTER TABLE llx_paiementfourn_facturefourn ADD INDEX idx_paiementfourn_facturefourn_fk_paiement(fk_paiementfourn);


drop table if exists llx_commande_model_pdf;
drop table if exists llx_commande_fournisseur_model_pdf;

alter table llx_commande add column note_public text after note;

alter table llx_contrat add column note text;
alter table llx_contrat add column note_public text after note;

alter table llx_facture add column note_public text after note;
alter table llx_facture add column remise_absolue real DEFAULT 0 after remise_percent;

alter table llx_propal add column note_public text after note;
alter table llx_propal add column remise_absolue real DEFAULT 0 after remise_percent;

alter table llx_commande add column remise_absolue real DEFAULT 0 after remise_percent;

ALTER TABLE llx_societe add mode_reglement tinyint;
ALTER TABLE llx_societe add cond_reglement tinyint;
ALTER TABLE llx_societe add tva_assuj      tinyint DEFAULT '1';
ALTER TABLE llx_societe add email          varchar(128) after url;


ALTER TABLE llx_societe change active statut tinyint DEFAULT 0;

ALTER TABLE llx_societe modify mode_reglement     tinyint NULL;
ALTER TABLE llx_societe modify cond_reglement     tinyint NULL;
ALTER TABLE llx_societe modify cond_reglement     tinyint NULL;
ALTER TABLE llx_societe modify fk_stcomm          tinyint        DEFAULT 0;
ALTER TABLE llx_societe modify services           tinyint        DEFAULT 0;
ALTER TABLE llx_societe modify client             tinyint        DEFAULT 0;
ALTER TABLE llx_societe modify fournisseur        tinyint        DEFAULT 0;

ALTER TABLE llx_societe drop column id;

ALTER TABLE llx_societe modify parent             integer;
UPDATE llx_societe set parent = null where parent = 0;

alter table llx_product add gencode varchar(255) DEFAULT NULL;

insert into llx_cond_reglement(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (6,'PROFORMA',    6,1, 'Proforma','Réglement avant livraison',0,0);

alter table llx_cond_reglement add (decalage smallint(6) default 0);

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



alter table llx_categorie drop column fk_statut;
alter table llx_categorie add visible tinyint DEFAULT 1 NOT NULL;


alter table llx_actioncomm modify datea datetime;
alter table llx_actioncomm add column datec datetime after id;
alter table llx_actioncomm add column datep datetime after datec;
alter table llx_actioncomm add column tms timestamp after datea;
update llx_actioncomm set datec = datea where datec is null;
update llx_actioncomm set datep = datea where datep is null;


drop table if exists llx_expedition_model_pdf;


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
update llx_commande set date_livraison = null where date_livraison = '0000-00-00';
update llx_commande set date_livraison = null where date_livraison = '1970-01-01';

ALTER TABLE llx_facture_fourn_det ADD INDEX idx_facture_fourn_det_fk_facture (fk_facture_fourn);
ALTER TABLE llx_facture_fourn_det ADD CONSTRAINT fk_facture_fourn_det_fk_facture FOREIGN KEY (fk_facture_fourn) REFERENCES llx_facture_fourn (rowid);


ALTER TABLE llx_facturedet ADD COLUMN fk_remise_except	integer NULL AFTER remise;
ALTER TABLE llx_facturedet ADD COLUMN total_ht        real AFTER price;
ALTER TABLE llx_facturedet ADD COLUMN total_tva       real AFTER total_ht;
ALTER TABLE llx_facturedet ADD COLUMN total_ttc       real AFTER total_tva;
ALTER TABLE llx_facturedet ADD COLUMN info_bits		  integer DEFAULT 0 AFTER date_end;

ALTER TABLE llx_propaldet ADD COLUMN total_ht        real AFTER price;
ALTER TABLE llx_propaldet ADD COLUMN total_tva       real AFTER total_ht;
ALTER TABLE llx_propaldet ADD COLUMN total_ttc       real AFTER total_tva;
ALTER TABLE llx_propaldet ADD COLUMN info_bits		 integer DEFAULT 0 AFTER total_ttc;

ALTER TABLE llx_commande ADD INDEX idx_commande_fk_soc (fk_soc);
ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe (idp);

ALTER TABLE llx_commande_fournisseur ADD INDEX idx_commande_fournisseur_fk_soc (fk_soc);
ALTER TABLE llx_commande_fournisseur ADD CONSTRAINT fk_commande_fournisseur_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe (idp);


alter table llx_commande_fournisseur add note_public text after note;


drop table if exists llx_avoir_model_pdf;


drop table if exists llx_soc_recontact;


update llx_const set name='PRODUIT_CHANGE_PROD_DESC' where name='CHANGE_PROD_DESC';
update llx_const set name='COMMANDE_ADD_PROD_DESC' where name='COM_ADD_PROD_DESC';
update llx_const set name='PROPALE_ADD_PROD_DESC' where name='PROP_ADD_PROD_DESC';
update llx_const set name='DON_FORM' where name='DONS_FORM';
update llx_const set name='MAIN_SIZE_LISTE_LIMIT' where name='SIZE_LISTE_LIMIT';
update llx_const set name='SOCIETE_FISCAL_MONTH_START' where name='FISCAL_MONTH_START';
update llx_const set visible=0 where name='FACTURE_DISABLE_RECUR';
update llx_const set visible=0 where name='MAILING_EMAIL_FROM';

insert into llx_const(name,value,type,visible,note) values('MAIN_SHOW_DEVELOPMENT_MODULES','0','yesno',1,'Make development modules visible');


alter table llx_paiementfourn add statut smallint(6) NOT NULL DEFAULT 0;


update llx_bank_url set type = 'payment_supplier' where label = '(paiement)' and type='payment' and url like '%/fourn/%';


create table llx_societe_adresse_livraison
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  datec	             datetime,
  tms                timestamp,
  fk_societe         integer        DEFAULT 0,
  nom                varchar(60),
  address            varchar(255),
  cp                 varchar(10),
  ville              varchar(50),
  fk_departement     integer        DEFAULT 0,
  fk_pays            integer        DEFAULT 0,
  note               text,
  fk_user_creat      integer,
  fk_user_modif      integer
)type=innodb;

alter table llx_societe_adresse_livraison add column label varchar(30) after tms;

alter table llx_propal add column fk_adresse_livraison integer after date_livraison;
alter table llx_commande add column fk_adresse_livraison integer after date_livraison;


insert into llx_c_pays (rowid,code,libelle) values (29, 'AU', 'Australie'      );
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (2901,29,2901,     '',0,'Australia');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901,'NSW','',1,'','New South Wales');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901,'VIC','',1,'','Victoria');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901,'QLD','',1,'','Queensland');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901, 'SA','',1,'','South Australia');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901,'ACT','',1,'','Australia Capital Territory');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901,'TAS','',1,'','Tasmania');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901, 'WA','',1,'','Western Australia');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2901, 'NT','',1,'','Northern Territory');
delete from llx_c_tva where rowid='291' and fk_pays='5';
delete from llx_c_tva where rowid='292' and fk_pays='5';
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (291, 29,  '10','0','VAT Rate 10',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (292, 29,   '0','0','VAT Rate 0',1);


update llx_const set value='neptune' where value='pluton' and name = 'FACTURE_ADDON';
update llx_const set value='azur' where value='orange' and name = 'PROPALE_ADDON';
update llx_const set value='mod_commande_diamant' where value='mod_commande_jade' and name ='COMMANDE_ADDON';

alter table llx_propal_model_pdf rename to llx_document_model;
alter table llx_document_model DROP PRIMARY KEY;
alter table llx_document_model add column rowid integer AUTO_INCREMENT PRIMARY KEY FIRST;
alter table llx_document_model add column type varchar(20) NOT NULL after nom;
update llx_document_model set type='propal' where type='';

delete from llx_document_model where nom='adytek';
delete from llx_document_model where nom='rouge' and type='order';
delete from llx_document_model where nom='azur' and type='order';
delete from llx_document_model where nom='orange' and type='propal';
delete from llx_document_model where nom='transporteur' and type='shipping';


alter table llx_actioncomm add column fk_commande integer after propalrowid;


ALTER TABLE llx_facture ADD UNIQUE INDEX idx_facture_uk_facnumber (facnumber);


ALTER TABLE llx_facture_rec ADD INDEX idx_facture_rec_fk_soc (fk_soc);
ALTER TABLE llx_facture_rec ADD INDEX idx_facture_rec_fk_user_author (fk_user_author);
ALTER TABLE llx_facture_rec ADD INDEX idx_facture_rec_fk_projet (fk_projet);

ALTER TABLE llx_facture_rec ADD CONSTRAINT fk_facture_rec_fk_user_author    FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
ALTER TABLE llx_facture_rec ADD CONSTRAINT fk_facture_rec_fk_projet         FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);

ALTER TABLE llx_facture_rec ADD UNIQUE INDEX idx_facture_rec_uk_titre (titre);

ALTER TABLE llx_commandedet ADD COLUMN fk_remise_except	integer NULL AFTER remise;
ALTER TABLE llx_commandedet ADD COLUMN coef real;

ALTER TABLE llx_propaldet ADD COLUMN fk_remise_except	integer NULL AFTER remise;
ALTER TABLE llx_propaldet ADD COLUMN coef real after price;

ALTER TABLE llx_contratdet ADD COLUMN fk_remise_except	integer NULL AFTER remise;

create table llx_livraison
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  fk_commande           integer DEFAULT 0,
  fk_expedition         integer,
  ref                   varchar(30) NOT NULL,
  date_creation         datetime,
  date_valid            datetime,
  fk_user_author        integer,
  fk_user_valid         integer,
  fk_statut             smallint  default 0,
  note                  text,
  note_public           text,
  model_pdf             varchar(50),
  date_livraison 	      date 	  default NULL,
  fk_adresse_livraison  integer,

  UNIQUE INDEX (ref),
  key(fk_commande)
)type=innodb;

alter table llx_livraison drop foreign key fk_livraison_societe;
alter table llx_livraison drop column fk_soc;
alter table llx_livraison drop column fk_soc_contact;

create table llx_livraisondet
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_livraison      integer,
  fk_commande_ligne integer NOT NULL,
  qty               real,
  key(fk_livraison),
  key(fk_commande_ligne)
)type=innodb;


insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (90, 'commande',  'internal', 'SALESREPSIGN',  'Commercial signataire de la commande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (91, 'commande',  'internal', 'SALESREPFOLL',  'Commercial suivi de la commande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (100, 'commande',  'external', 'BILLING',       'Contact client facturation commande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (101, 'commande',  'external', 'CUSTOMER',      'Contact client suivi commande', 1);

insert into llx_c_pays (rowid,code,libelle) values (30, 'SG', 'Singapoure');


alter table llx_bank_account add column ref varchar(12) NOT NULL;


alter table llx_accountingsystem_det rename to llx_accountingaccount;


insert into llx_rights_def (id, libelle, module, type, bydefault, subperms, perms) values (262,'Consulter tous les clients','commercial','r',1,'voir','client');
insert into llx_user_rights(fk_user,fk_id) select distinct fk_user, '262' from llx_user_rights where fk_id = 261;
update llx_rights_def set subperms='creer' where subperms='supprimer' AND module='user' AND perms='self' AND id=255;

alter table llx_commandedet add column rang integer DEFAULT 0;
alter table llx_propaldet add column rang integer DEFAULT 0;

alter table llx_facture drop column model;
alter table llx_facture add column model_pdf varchar(50) after note_public;


update llx_societe_remise_except set description='Remise sans description' where description is NULL or description ='';
alter table llx_societe_remise_except modify description varchar(255) NOT NULL;

insert into llx_c_actioncomm (id, code, type, libelle) values ( 8, 'AC_COM',  'system', 'Envoi Commande');
update llx_actioncomm set fk_action = '8' where fk_action =  '3' and label = 'Envoi commande par mail';

insert into llx_const (name, value, type, visible, note) VALUES ('PROPALE_VALIDITY_DURATION', '15', 'chaine', 0, 'Durée de validitée des propales');

alter table llx_propal add column ref_client varchar(30) after ref;