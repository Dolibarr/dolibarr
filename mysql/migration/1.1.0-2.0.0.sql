-- $Revision$
--
-- Attention à l ordre des requetes
-- ce fichier doit être chargé sur une version 1.1.0 
-- sans AUCUNE erreur ni warning
-- ;


drop table if exists llx_c_tva;

create table llx_c_tva
(
  rowid             integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fk_pays           integer NOT NULL,
  taux              double NOT NULL,
  recuperableonly   integer DEFAULT 0,
  note              varchar(128),
  active            tinyint DEFAULT 1 NOT NULL

)type=innodb;

insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1,1,   '0','0','Taux TVA non applicable (France, TOM)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (2,1, '5.5','0','Taux à 5.5 (France hors DOM-TOM)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (3,1, '8.5','0','Taux à 8.5 (DOM sauf Guyane et Saint-Martin)',0);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (4,1, '8.5','1','Taux à 8.5 (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par l\'acheteur',0);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (5,1,'19.6','0','Taux à 19.6 (France hors DOM-TOM)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (6,2,   '0','0','Taux TVA non applicable',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (7,2,   '6','0','Taux à 6',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (8,2,  '21','0','Taux à 21',1);


create table llx_user_clicktodial
(
  fk_user       integer PRIMARY KEY,
  login         varchar(32),
  pass          varchar(64),
  poste         varchar(20)

)type=innodb;

create table llx_export_compta
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  ref              varchar(12) NOT NULL,
  date_export      datetime NOT NULL,        -- date de creation
  fk_user          integer NOT NULL,
  note             text

)type=innodb;

create table llx_expeditiondet
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_expedition     integer NOT NULL,
  fk_commande_ligne integer NOT NULL,
  qty               real,              -- quantité

  key(fk_expedition),
  key(fk_commande_ligne)
)type=innodb;

create table llx_expedition_methode
(
  rowid            integer PRIMARY KEY,
  tms              timestamp,
  code             varchar(30) NOT NULL,
  libelle          varchar(50) NOT NULL,
  description      text,
  statut           tinyint DEFAULT 0
)type=innodb;

create table llx_expedition
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  ref                   varchar(30) NOT NULL,
  fk_commande           integer,
  date_creation         datetime,              -- date de creation 
  date_valid            datetime,              -- date de validation
  date_expedition       date,                  -- date de l'expedition
  fk_user_author        integer,               -- createur
  fk_user_valid         integer,               -- valideur
  fk_entrepot           integer,
  fk_expedition_methode integer,
  fk_statut             smallint  DEFAULT 0,
  note                  text,
  model_pdf             varchar(50),

  UNIQUE INDEX (ref),
  key(fk_expedition_methode),
  key(fk_commande)
)type=innodb;

create table llx_contratdet_log
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  fk_contratdet         integer NOT NULL,
  date                  datetime NOT NULL,
  statut                smallint NOT NULL,
  fk_user_author        integer NOT NULL,
  commentaire           text

)type=innodb;

create table llx_compta_compte_generaux
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  date_creation   datetime,
  numero          varchar(50),
  intitule        varchar(255),
  fk_user_author  integer,
  note            text,

  UNIQUE(numero)
)type=innodb;

create table llx_categorie_association
(
  fk_categorie_mere   integer NOT NULL,
  fk_categorie_fille  integer NOT NULL
)type=innodb;


create table llx_categorie_product
(
  fk_categorie  integer NOT NULL,
  fk_product    integer NOT NULL
)type=innodb;

create table llx_c_methode_commande_fournisseur
(
  rowid    integer AUTO_INCREMENT PRIMARY KEY,
  code     varchar(30),
  libelle  varchar(60),
  active   tinyint default 1  NOT NULL,

  UNIQUE INDEX(code)
)type=innodb;


create table llx_bookmark4u_login
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_user       integer,
  bk4u_uid      integer,

  UNIQUE INDEX(fk_user)
)type=innodb;


create table llx_categorie
(
	rowid 		integer AUTO_INCREMENT PRIMARY KEY,
	label 		VARCHAR(255),
	description 	text	
)type=innodb;


create table llx_product_fournisseur_price
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  tms             timestamp,
  fk_product      integer,
  fk_soc          integer,
  price           real,
  quantity        real,
  fk_user         integer

)type=innodb;

create table llx_product_fournisseur_price_log
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  fk_product      integer,
  fk_soc          integer,
  price           real,
  quantity        real,
  fk_user         integer

)type=innodb;


create table llx_usergroup
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  datec         datetime,
  tms           timestamp,
  nom           varchar(255) NOT NULL UNIQUE,
  note          text

)type=innodb;

create table llx_usergroup_rights
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_usergroup  integer NOT NULL,
  fk_id         integer NOT NULL,

  UNIQUE(fk_usergroup,fk_id)
)type=innodb;


create table llx_usergroup_user
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_user       integer NOT NULL,
  fk_usergroup  integer NOT NULL,

  UNIQUE(fk_user,fk_usergroup)
)type=innodb;

DELETE llx_usergroup_rights FROM llx_usergroup_rights LEFT JOIN llx_usergroup ON llx_usergroup_rights.fk_usergroup = llx_usergroup.rowid WHERE llx_usergroup.rowid IS NULL;
ALTER TABLE llx_usergroup_rights ADD FOREIGN KEY (fk_usergroup)    REFERENCES llx_usergroup (rowid);


alter table llx_facture add  increment           varchar(10);
alter table llx_facture drop column author;
alter table llx_facture drop column fk_user;

alter table llx_facture_rec add frequency char(2) DEFAULT NULL;
alter table llx_facture_rec add last_gen varchar(7) DEFAULT NULL;

create table llx_societe_commerciaux
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc        integer,
  fk_user       integer,

  UNIQUE INDEX(fk_soc, fk_user)
)type=innodb;


alter table llx_action_def add code varchar(24) NOT NULL after rowid;

alter table llx_action_def modify objet_type enum('ficheinter','facture','propale','mailing') default NULL;

update llx_action_def set code='NOTIFY_VAL_FICHINTER' where titre='Validation fiche intervention';
update llx_action_def set code='NOTIFY_VAL_FAC' where titre='Validation facture';


delete from llx_const where name like '%_OUTPUT_URL';
delete from llx_const where name like 'MAIN_START_YEAR';

alter table llx_boxes add box_order smallint default 0 NOT NULL;

alter table llx_user drop column module_comm;
alter table llx_user drop column module_compta;
alter table llx_user add datelastaccess datetime;
alter table llx_user add office_phone varchar(20);
alter table llx_user add office_fax varchar(20);
alter table llx_user add user_mobile varchar(20);
alter table llx_user modify login varchar(24);

alter table llx_user_rights add rowid integer AUTO_INCREMENT PRIMARY KEY;

-- Commenté car semble déjà présent dans la base 1.1.0
-- alter table llx_facture add fk_cond_reglement integer DEFAULT 1 NOT NULL;

alter table llx_facture add fk_mode_reglement integer after fk_cond_reglement ;

alter table llx_cond_reglement change column actif active tinyint(4);
alter table llx_cond_reglement add code varchar(16) after rowid;
update llx_cond_reglement set code='RECEP' where libelle='A réception' and code IS NULL;
update llx_cond_reglement set code='30D' where libelle='30 jours' and code IS NULL;
update llx_cond_reglement set code='30DENDMONTH' where libelle='30 jours fin de mois' and code IS NULL;
update llx_cond_reglement set code='60D' where libelle='60 jours' and code IS NULL;
update llx_cond_reglement set code='60DENDMONTH' where libelle='60 jours fin de mois' and code IS NULL;
 
alter table llx_socpeople add cp varchar(25) after address;
alter table llx_socpeople add ville varchar(255) after cp;
alter table llx_socpeople add fk_pays integer DEFAULT 0 after ville;

alter table llx_paiement add statut smallint DEFAULT 0 NOT NULL ;
alter table llx_facturedet add fk_export_compta integer DEFAULT 0 NOT NULL ;
alter table llx_paiement add fk_export_compta integer DEFAULT 0 NOT NULL ;

alter table llx_facturedet add rang integer DEFAULT 0 NOT NULL;

alter table llx_rights_def add perms varchar(255) after module;
alter table llx_rights_def add subperms varchar(255) after perms;
UPDATE llx_rights_def set perms=NULL, subperms=NULL                    where id="10";
UPDATE llx_rights_def set perms="lire", subperms=NULL                  where id="11";
UPDATE llx_rights_def set perms="creer", subperms=NULL                 where id="12";
UPDATE llx_rights_def set perms="valider", subperms=NULL               where id="14";
UPDATE llx_rights_def set perms="envoyer", subperms=NULL               where id="15";
UPDATE llx_rights_def set perms="paiement", subperms=NULL              where id="16";
UPDATE llx_rights_def set perms="supprimer", subperms=NULL             where id="19";
UPDATE llx_rights_def set perms=NULL, subperms=NULL                    where id="20";
UPDATE llx_rights_def set perms="lire", subperms=NULL                  where id="21";
UPDATE llx_rights_def set perms="creer", subperms=NULL                 where id="22";
UPDATE llx_rights_def set perms="valider", subperms=NULL               where id="24";
UPDATE llx_rights_def set perms="envoyer", subperms=NULL               where id="25";
UPDATE llx_rights_def set perms="cloturer", subperms=NULL              where id="26";
UPDATE llx_rights_def set perms="supprimer", subperms=NULL             where id="27";
UPDATE llx_rights_def set perms=NULL, subperms=NULL                    where id="30";
UPDATE llx_rights_def set perms="lire", subperms=NULL                  where id="31";
UPDATE llx_rights_def set perms="creer", subperms=NULL                 where id="32";
UPDATE llx_rights_def set perms="commander", subperms=NULL             where id="33";
UPDATE llx_rights_def set perms="supprimer", subperms=NULL             where id="34";
UPDATE llx_rights_def set perms=NULL, subperms=NULL                    where id="40";
UPDATE llx_rights_def set perms="lire", subperms=NULL                  where id="41";
UPDATE llx_rights_def set perms="creer", subperms=NULL                 where id="42";
UPDATE llx_rights_def set perms="supprimer", subperms=NULL             where id="44";
UPDATE llx_rights_def set perms=NULL, subperms=NULL                    where id="70";
UPDATE llx_rights_def set perms="lire", subperms=NULL                  where id="71";
UPDATE llx_rights_def set perms="creer", subperms=NULL                 where id="72";
UPDATE llx_rights_def set perms="modifier", subperms=NULL              where id="73";
UPDATE llx_rights_def set perms="supprimer", subperms=NULL             where id="74";
UPDATE llx_rights_def set perms=NULL, subperms=NULL                    where id="80";
UPDATE llx_rights_def set perms="lire", subperms=NULL                  where id="81";
UPDATE llx_rights_def set perms="creer", subperms=NULL                 where id="82";
UPDATE llx_rights_def set perms="valider", subperms=NULL               where id="84";
UPDATE llx_rights_def set perms="supprimer", subperms=NULL             where id="89";
UPDATE llx_rights_def set perms=NULL, subperms=NULL                    where id="90";
UPDATE llx_rights_def set perms="charges", subperms="lire"             where id="91";
UPDATE llx_rights_def set perms="charges", subperms="creer"            where id="92";
UPDATE llx_rights_def set perms="charges", subperms="supprimer"        where id="93";
UPDATE llx_rights_def set perms="resultat", subperms="lire"            where id="95";
UPDATE llx_rights_def set perms="ventilation", subperms="parametrer"   where id="96";
UPDATE llx_rights_def set perms="ventilation", subperms="creer"        where id="97";
UPDATE llx_rights_def set perms= NULL, subperms=NULL                   where id="100";
UPDATE llx_rights_def set perms= "lire", subperms=NULL                 where id="101";
UPDATE llx_rights_def set perms= "creer", subperms=NULL                where id="102";
UPDATE llx_rights_def set perms= "valider", subperms=NULL              where id="104";
UPDATE llx_rights_def set perms= "supprimer", subperms=NULL            where id="109";
UPDATE llx_rights_def set perms= NULL, subperms=NULL                   where id="110";
UPDATE llx_rights_def set perms= "lire", subperms=NULL                 where id="111";
UPDATE llx_rights_def set perms= "modifier", subperms=NULL             where id="112";
UPDATE llx_rights_def set perms= "configurer", subperms=NULL           where id="113";
UPDATE llx_rights_def set perms= NULL, subperms=NULL                   where id="120";
UPDATE llx_rights_def set perms= "lire", subperms=NULL                 where id="121";
UPDATE llx_rights_def set perms= "creer", subperms=NULL                where id="122";
UPDATE llx_rights_def set perms= "supprimer", subperms=NULL            where id="129";
UPDATE llx_rights_def set perms= NULL, subperms=NULL                   where id="140";
UPDATE llx_rights_def set perms= "lire", subperms=NULL                 where id="141";
UPDATE llx_rights_def set perms= "ligne_commander", subperms=NULL      where id="142";
UPDATE llx_rights_def set perms= "ligne_activer", subperms=NULL        where id="143";
UPDATE llx_rights_def set perms= NULL, subperms=NULL                   where id="144";
UPDATE llx_rights_def set perms= "fournisseur", subperms="config"      where id="145";
UPDATE llx_rights_def set perms= NULL, subperms=NULL                   where id="150";
UPDATE llx_rights_def set perms= "lire", subperms=NULL                 where id="151";
UPDATE llx_rights_def set perms= "configurer", subperms=NULL           where id="152";
UPDATE llx_rights_def set perms= NULL, subperms=NULL                   where id="160";
UPDATE llx_rights_def set perms= "lire", subperms=NULL                 where id="161";
UPDATE llx_rights_def set perms= "creer", subperms=NULL                where id="162";
UPDATE llx_rights_def set perms= "activer", subperms=NULL              where id="163";
UPDATE llx_rights_def set perms= "desactiver", subperms=NULL           where id="164";
UPDATE llx_rights_def set perms= NULL, subperms=NULL                   where id="170";
UPDATE llx_rights_def set perms= NULL, subperms=NULL                   where id="180";
UPDATE llx_rights_def set perms= "commande", subperms="lire"           where id="181";
UPDATE llx_rights_def set perms= "commande", subperms="creer"          where id="182";
UPDATE llx_rights_def set perms= "commande", subperms="valider"        where id="183";
UPDATE llx_rights_def set perms= "commande", subperms="approuver"      where id="184";
UPDATE llx_rights_def set perms= "commande", subperms="commander"      where id="185";
UPDATE llx_rights_def set perms= "commande", subperms="cloturer"       where id="186";
UPDATE llx_rights_def set perms= "ligne", subperms="creer"             where id="192";
UPDATE llx_rights_def set perms= "adsl", subperms="creer"              where id="202";
UPDATE llx_rights_def set perms= "adsl", subperms="requete"            where id="203";
UPDATE llx_rights_def set perms= "adsl", subperms="commander"          where id="204";
UPDATE llx_rights_def set perms= "adsl", subperms="gerer"              where id="205";
UPDATE llx_rights_def set perms= "contrat", subperms="paiement"        where id="215";
delete from llx_rights_def where perms is null and subperms is null;
delete from llx_rights_def where id=73;

alter table llx_facturedet add fk_code_ventilation integer NOT NULL DEFAULT 0;


alter table llx_contrat change fk_user_cloture  fk_user_cloture integer;
alter table llx_contrat change fk_user_mise_en_service fk_user_mise_en_service integer;
alter table llx_contrat change enservice statut smallint(6) default 0;
alter table llx_contrat add   datec             datetime after tms;
alter table llx_contrat add   date_contrat      datetime after datec;
alter table llx_contrat add   fk_projet         integer after fk_soc;
alter table llx_contrat add   fk_commercial_signature integer NOT NULL after fk_projet;
alter table llx_contrat add   fk_commercial_suivi     integer NOT NULL after fk_commercial_signature;
alter table llx_contrat add   facture           smallint(6) default 0;
alter table llx_contrat add   ref         	    varchar(30) after rowid;

  
alter table llx_facturedet add date_start date;
alter table llx_facturedet add date_end   date;

alter table llx_user add egroupware_id integer;
alter table llx_societe add code_client              varchar(15) after nom;
alter table llx_societe add code_fournisseur         varchar(15) after code_client;
alter table llx_societe add code_compta              varchar(15) after code_fournisseur;
alter table llx_societe add code_compta_fournisseur  varchar(15) after code_compta;
alter table llx_societe add siret     varchar(14) after siren;
alter table llx_societe add ape       varchar(4) after siret;
alter table llx_societe add tva_intra varchar(20) after ape;
alter table llx_societe add capital real after tva_intra;
alter table llx_societe add rubrique varchar(255);
alter table llx_societe add remise_client real default 0;

update llx_societe set prefix_comm = null where prefix_comm = '';
update llx_societe set code_client = null where code_client = '';
ALTER TABLE llx_societe ADD UNIQUE uk_societe_prefix_comm(prefix_comm);
ALTER TABLE llx_societe ADD UNIQUE uk_societe_code_client(code_client);

insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_REQUIRED','1','yesno','Le mail est obligatoire pour créer un adhérent',0);

alter table llx_societe add fk_forme_juridique integer default 0 after fk_typent;

alter table llx_societe add fk_departement integer default 0 after ville;

alter table llx_societe add fk_user_creat integer;
alter table llx_societe add fk_user_modif integer;

alter table llx_socpeople add civilite varchar(6);
alter table llx_socpeople add fk_user_modif integer;


alter table llx_paiement add tms timestamp after datec;
alter table llx_paiement add fk_user_creat integer;
alter table llx_paiement add fk_user_modif integer;

alter table llx_propal add fin_validite datetime ;
alter table llx_propal add fk_cond_reglement integer after total;
alter table llx_propal add fk_mode_reglement integer after total;

alter table llx_entrepot add statut  tinyint default 1;
alter table llx_entrepot add lieu    varchar(64);
alter table llx_entrepot add address varchar(255);
alter table llx_entrepot add cp      varchar(10);
alter table llx_entrepot add ville   varchar(50);
alter table llx_entrepot add fk_pays integer DEFAULT 0;

alter table llx_product add stock_propale integer default 0;
alter table llx_product add stock_commande integer default 0;
alter table llx_product add seuil_stock_alerte integer default 0;
update llx_product set ref=substr(label,0,15) where ref is null;
alter table llx_product modify ref varchar(15) UNIQUE NOT NULL;
alter table llx_product add note text after description;

alter table llx_product_stock change value reel integer;
alter table llx_product_stock change fk_stock fk_entrepot integer;

alter table llx_groupart add description text after groupart ;

alter table llx_socpeople add phone_perso varchar(30) after phone ;
alter table llx_socpeople add phone_mobile varchar(30) after phone_perso ;
alter table llx_socpeople add jabberid varchar(255) after email ;
alter table llx_socpeople add birthday date after address ;
alter table llx_socpeople add tms timestamp after datec ;

alter table llx_facture_fourn drop index facnumber ;
alter table llx_facture_fourn add unique index (facnumber, fk_soc) ;

alter table llx_facture_fourn add fk_projet integer ;
alter table llx_facture_fourn add fk_cond_reglement integer DEFAULT 1 NOT NULL ;
alter table llx_facture_fourn add date_lim_reglement  date;

ALTER TABLE llx_facture_fourn ADD INDEX idx_facture_fourn_fk_soc (fk_soc);
ALTER TABLE llx_facture_fourn ADD INDEX idx_facture_fourn_fk_user_author (fk_user_author);
ALTER TABLE llx_facture_fourn ADD INDEX idx_facture_fourn_fk_user_valid (fk_user_valid);
ALTER TABLE llx_facture_fourn ADD INDEX idx_facture_fourn_fk_projet (fk_projet);

ALTER TABLE llx_facture_fourn ADD FOREIGN KEY (fk_soc) REFERENCES llx_societe (idp);
ALTER TABLE llx_facture_fourn ADD FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
ALTER TABLE llx_facture_fourn ADD FOREIGN KEY (fk_user_valid) REFERENCES llx_user (rowid);
ALTER TABLE llx_facture_fourn ADD FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);

ALTER TABLE llx_facture ADD INDEX idx_facture_fk_projet (fk_projet);

ALTER TABLE llx_facture ADD FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);


alter table llx_bank_account modify bank varchar(60);
alter table llx_bank_account modify domiciliation varchar(255);
alter table llx_bank_account add proprio varchar(60) after domiciliation ;
alter table llx_bank_account add adresse_proprio varchar(255) after proprio ;
alter table llx_bank_account add account_number varchar(8) after clos ;
alter table llx_bank_account add rappro smallint DEFAULT 1 after clos;
alter table llx_bank_account modify label varchar(30) unique;
update llx_bank_account set account_number = '51' where account_number is null;

alter table llx_paiement add fk_bank integer NOT NULL after note ;
alter table llx_paiementfourn add fk_bank integer NOT NULL after note ;


alter table c_actioncomm     rename llx_c_actioncomm ;
alter table c_effectif       rename llx_c_effectif ;
alter table c_paiement       rename llx_c_paiement ;
alter table c_pays           rename llx_c_pays ;
alter table c_propalst       rename llx_c_propalst ;
alter table c_stcomm         rename llx_c_stcomm ;
alter table c_typent         rename llx_c_typent ;

alter table llx_c_actioncomm add type varchar(10) not null default 'system' after id;
alter table llx_c_actioncomm add active tinyint default 1 NOT NULL after libelle;

alter table llx_c_paiement add code varchar(6) after id;


create table llx_prelevement_facture
(
  rowid                  integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture             integer NOT NULL,
  fk_prelevement_lignes  integer NOT NULL

)type=innodb;


create table llx_prelevement_facture_demande
(
  rowid               integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture          integer NOT NULL,
  amount              real NOT NULL,
  date_demande        datetime NOT NULL,
  traite              smallint DEFAULT 0,
  date_traite         datetime,
  fk_prelevement_bons integer,
  fk_user_demande     integer NOT NULL,

  code_banque         varchar(7),
  code_guichet        varchar(6),
  number              varchar(255),
  cle_rib             varchar(5)

)type=innodb;


create table llx_prelevement_bons
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  ref            varchar(12),
  datec          datetime,
  amount         real DEFAULT 0,
  statut         smallint DEFAULT 0,
  credite        smallint DEFAULT 0,
  note           text,
  date_trans     datetime,  
  method_trans   smallint,  
  fk_user_trans  integer,   
  date_credit    datetime,  
  fk_user_credit integer,   
  
  UNIQUE(ref)
)type=innodb;


create table llx_prelevement_lignes
(
  rowid               integer AUTO_INCREMENT PRIMARY KEY,
  fk_prelevement_bons integer,
  fk_soc              integer NOT NULL,
  statut              smallint DEFAULT 0,

  client_nom          varchar(255),
  amount              real DEFAULT 0,
  code_banque         varchar(7),
  code_guichet        varchar(6),
  number              varchar(255),
  cle_rib             varchar(5),

  note                text

)type=innodb;


create table llx_prelevement_rejet
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  fk_prelevement_lignes integer,
  date_rejet            datetime,
  motif                 integer,
  date_creation         datetime,
  fk_user_creation      integer,
  note                  text

)type=innodb;


ALTER TABLE llx_prelevement_facture ADD INDEX (fk_prelevement_lignes);
ALTER TABLE llx_prelevement_facture ADD FOREIGN KEY (fk_prelevement_lignes) REFERENCES llx_prelevement_lignes (rowid);
ALTER TABLE llx_prelevement_lignes ADD INDEX (fk_prelevement_bons);
ALTER TABLE llx_prelevement_lignes ADD FOREIGN KEY (fk_prelevement_bons) REFERENCES llx_prelevement_bons (rowid);


create table llx_prelevement_notifications
(
  rowid     integer AUTO_INCREMENT PRIMARY KEY,
  fk_user   integer NOT NULL,
  action    varchar(2) 

)type=innodb;



create table llx_mailing
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,

  statut             smallint       DEFAULT 0,   

  date_envoi         datetime,                   
  titre              varchar(60),                
  sujet              varchar(60),                
  body               text,
  cible              varchar(60),

  nbemail            integer,

  email_from         varchar(160),               
  email_replyto      varchar(160),               
  email_errorsto     varchar(160),               

  date_creat         datetime,                   
  date_valid         datetime,                   
  date_appro         datetime,                   

  fk_user_creat      integer,                    
  fk_user_valid      integer,                    
  fk_user_appro      integer                     

)type=innodb;


create table llx_mailing_cibles
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  fk_mailing         integer NOT NULL,
  fk_contact         integer NOT NULL,
  nom                varchar(160),
  prenom             varchar(160),
  email              varchar(160) NOT NULL,
  statut             smallint NOT NULL DEFAULT 0,
  date_envoi         datetime

)type=innodb;

alter table llx_mailing_cibles ADD url varchar(160);

alter table llx_mailing_cibles ADD UNIQUE uk_mailing_cibles (fk_mailing, email);

--
--create table llx_stock_mouvement
--(
--  rowid           integer AUTO_INCREMENT PRIMARY KEY,
--  tms             timestamp,
--  datem           datetime,
--  fk_product      integer NOT NULL,
--  value           integer,
--  type_mouvement  smallint,
--  fk_user_author  integer,
--  key(fk_product),
--  key(fk_entrepot)
--)type=innodb;

alter table llx_stock_mouvement ADD fk_entrepot     integer NOT NULL after fk_product;


create table llx_birthday_alert
(
  rowid        integer AUTO_INCREMENT PRIMARY KEY,
  fk_contact   integer,
  fk_user      integer
)type=innodb;


alter table llx_birthday_alert rename llx_user_alert ;
alter table llx_user_alert add type integer after rowid;
update llx_user_alert set type=1 where type is null;


create table llx_co_fa
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande integer NOT NULL,
  fk_facture  integer NOT NULL,

  key(fk_commande),
  key(fk_facture)
)type=innodb;


create table llx_co_pr
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande integer,
  fk_propale  integer
)type=innodb;


create table llx_paiement_facture
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_paiement     integer,
  fk_facture      integer,
  amount          real default 0,
  
  key (fk_paiement),
  key( fk_facture)
)type=innodb;


insert into llx_const(name, value, type, note, visible) values ('MAIN_UPLOAD_DOC','1','chaine','Authorise l\'upload de document',0);
insert into llx_const(name, value, type, note, visible) values ('MAIN_SEARCHFORM_PRODUITSERVICE','1','yesno','Affichage formulaire de recherche des Produits et Services dans la barre de gauche',0);
delete from llx_const where name = 'COMPTA_BANK_FACTURES';
update llx_const set visible='0' where name='MAIN_UPLOAD_DOC';
update llx_const set visible='0' where name='MAIN_TITLE';

update llx_bank set fk_type = 'VAD' where fk_type = 'WWW';
update llx_bank set fk_type = 'LIQ' where fk_type = 'DEP';

alter table llx_socpeople change civilite civilite varchar(6);

update llx_paiement set author = null where author = '';

create table llx_paiementcharge
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_charge       integer,
  datec           datetime,
  tms             timestamp,
  datep           datetime,
  amount          real default 0,
  fk_typepaiement integer NOT NULL,
  num_paiement    varchar(50),
  note            text,
  fk_bank         integer NOT NULL,
  fk_user_creat   integer,
  fk_user_modif   integer

)type=innodb;


update llx_const set visible=0 where name like 'DONS_FORM';
update llx_const set visible=0 where name like 'ADHERENT%';
update llx_const set visible=0 where name like 'PROPALE_ADDON%';


create table llx_user_param
(
  fk_user       integer      NOT NULL,
  page          varchar(255) NOT NULL,
  param         varchar(64)  NOT NULL,
  value         varchar(255) NOT NULL,

  UNIQUE (fk_user,page,param)
)type=innodb;

alter table llx_user_param modify fk_user       integer      NOT NULL;
alter table llx_user_param modify page          varchar(255) NOT NULL;
alter table llx_user_param modify param         varchar(64)  NOT NULL;
alter table llx_user_param modify value         varchar(255) NOT NULL;


update llx_bank set datev=dateo where datev is null;

update llx_chargesociales set periode=date_ech where periode is null or periode = '0000-00-00';

-- pour virer les doublons de llx_bank_url (dus à un ancien bug)
alter ignore table llx_bank_url add unique index(fk_bank,url_id);
alter table llx_bank_url add type enum("company","payment","member","donation","charge");
  
create table llx_societe_remise
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc          integer NOT NULL,
  tms             timestamp,
  datec	          datetime,                            
  fk_user_author  integer,                             
  remise_client   real           default 0,            
  note            text

)type=innodb;


create table llx_societe_remise_except
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc          integer NOT NULL,
  datec           datetime,
  amount_ht       real NOT NULL,
  fk_user         integer NOT NULL,
  fk_facture      integer,
  description     text

)type=innodb;

create table llx_contact_facture
(
  idp          integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc       integer NOT NULL,
  fk_contact   integer NOT NULL,

  UNIQUE (fk_soc, fk_contact)
)type=innodb;


--
--
--
--

create table llx_so_gr
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc      integer,
  fk_groupe   integer,

  UNIQUE(fk_soc, fk_groupe)
)type=innodb;

create table llx_groupesociete_remise
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_groupe       integer NOT NULL,
  tms             timestamp,
  datec	          datetime,                            
  fk_user_author  integer,                             
  remise          real           default 0,            
  note            text

)type=innodb;

create table llx_groupesociete
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  parent          integer UNIQUE,
  tms             timestamp,
  datec	          datetime,                            
  nom             varchar(60),                         
  note            text,                                
  remise          real           default 0,
  fk_user_author  integer

)type=innodb;

--
--
--
--

create table llx_commande
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_soc           integer,
  fk_soc_contact   integer,
  fk_projet        integer default 0,
  ref              varchar(30) NOT NULL,
  date_creation    datetime,            
  date_valid       datetime,            
  date_cloture     datetime,            
  date_commande    date,                
  fk_user_author   integer,             
  fk_user_valid    integer,             
  fk_user_cloture  integer,             
  source           smallint NOT NULL,
  fk_statut        smallint  default 0,
  amount_ht        real      default 0,
  remise_percent   real      default 0,
  remise           real      default 0,
  tva              real      default 0,
  total_ht         real      default 0,
  total_ttc        real      default 0,
  note             text,
  model_pdf        varchar(50),
  facture          tinyint default 0,   
  UNIQUE INDEX (ref)
)type=innodb;

update llx_commande set date_cloture=tms where date_cloture is null and fk_statut > 2;

create table llx_commandedet
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande    integer,
  fk_product     integer,
  label          varchar(255),
  description    text,
  tva_tx         real default 19.6,
  qty		 real,             
  remise_percent real default 0,
  remise         real default 0,
  subprice       real,          
  price          real           
)type=innodb;

create table llx_commande_fournisseur
(
  rowid               integer AUTO_INCREMENT PRIMARY KEY,
  tms                 timestamp,
  fk_soc              integer,
  fk_soc_contact      integer,
  fk_projet           integer DEFAULT 0,   
  ref                 varchar(30) NOT NULL,
  date_creation       datetime,            
  date_valid          datetime,            
  date_cloture        datetime,            
  date_commande       date,                
  fk_methode_commande integer default 0,
  fk_user_author      integer,             
  fk_user_valid       integer,             
  fk_user_cloture     integer,             
  source              smallint NOT NULL,
  fk_statut           smallint  default 0,
  amount_ht           real      default 0,
  remise_percent      real      default 0,
  remise              real      default 0,
  tva                 real      default 0,
  total_ht            real      default 0,
  total_ttc           real      default 0,
  note                text,
  model_pdf           varchar(50),

  UNIQUE INDEX (ref)
)type=innodb;

create table llx_commande_fournisseur_log
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  datelog          datetime NOT NULL,
  fk_commande      integer NOT NULL,
  fk_statut        smallint NOT NULL,
  fk_user          integer NOT NULL
)type=innodb;

create table llx_commande_fournisseurdet
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande    integer,
  fk_product     integer,
  ref            varchar(50),
  label          varchar(255),
  description    text,
  tva_tx         real DEFAULT 19.6,
  qty            real,             
  remise_percent real DEFAULT 0,
  remise         real DEFAULT 0,
  subprice       real,          
  price          real           
)type=innodb;


create table llx_societe_rib
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc         integer NOT NULL,
  datec          datetime,
  tms            timestamp,
  label          varchar(30),
  bank           varchar(255),
  code_banque    varchar(7),
  code_guichet   varchar(6),
  number         varchar(255),
  cle_rib        varchar(5),
  bic            varchar(10),
  iban_prefix    varchar(5),
  domiciliation  varchar(255),
  proprio        varchar(60),
  adresse_proprio varchar(255)


)type=innodb;




drop table if exists llx_c_accountingsystem;

drop table if exists llx_accountingsystem_det;
drop table if exists llx_accountingsystem;

create table llx_accountingsystem
(
  pcg_version       varchar(12)     PRIMARY KEY,
  fk_pays           integer         NOT NULL,
  label             varchar(128)    NOT NULL,
  datec             varchar(12)     NOT NULL,
  fk_author         varchar(20),
  tms               timestamp,
  active            smallint        DEFAULT 0
)type=innodb;

create table llx_accountingsystem_det
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_pcg_version    varchar(12)  NOT NULL,
  pcg_type          varchar(20)  NOT NULL,
  pcg_subtype       varchar(20)  NOT NULL,
  label             varchar(128) NOT NULL,
  account_number    varchar(20)  NOT NULL,
  account_parent    varchar(20)
)type=innodb;



ALTER TABLE llx_accountingsystem_det ADD INDEX idx_accountingsystem_det_fk_pcg_version (fk_pcg_version);


ALTER TABLE llx_accountingsystem_det ADD FOREIGN KEY (fk_pcg_version)    REFERENCES llx_accountingsystem (pcg_version);


delete from llx_accountingsystem_det;
delete from llx_accountingsystem;

insert into llx_accountingsystem (pcg_version, fk_pays, label, datec, fk_author, active) VALUES ('PCG99-ABREGE', 1, 'Plan de compte standard français abrégé', sysdate(), null, 0);

insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  1,'PCG99-ABREGE','CAPIT', 'CAPITAL', '101', '1', 'Capital');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  2,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '105', '1', 'Ecarts de réévaluation');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  3,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1061', '1', 'Réserve légale');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  4,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1063', '1', 'Réserves statutaires ou contractuelles');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  5,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1064', '1', 'Réserves réglementées');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  6,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1068', '1', 'Autres réserves');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  7,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '108', '1', 'Compte de l''exploitant');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  8,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '12', '1', 'Résultat de l''exercice');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  9,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '145', '1', 'Amortissements dérogatoires');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 10,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '146', '1', 'Provision spéciale de réévaluation');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 11,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '147', '1', 'Plus-values réinvesties');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 12,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '148', '1', 'Autres provisions réglementées');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 13,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '15', '1', 'Provisions pour risques et charges');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 14,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '16', '1', 'Emprunts et dettes assimilees');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 15,'PCG99-ABREGE','IMMO',  'XXXXXX',   '20', '2', 'Immobilisations incorporelles');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 16,'PCG99-ABREGE','IMMO',  'XXXXXX',  '201','20', 'Frais d''établissement');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 17,'PCG99-ABREGE','IMMO',  'XXXXXX',  '206','20', 'Droit au bail');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 18,'PCG99-ABREGE','IMMO',  'XXXXXX',  '207','20', 'Fonds commercial');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 19,'PCG99-ABREGE','IMMO',  'XXXXXX',  '208','20', 'Autres immobilisations incorporelles');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 20,'PCG99-ABREGE','IMMO',  'XXXXXX',   '21', '2', 'Immobilisations corporelles');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 21,'PCG99-ABREGE','IMMO',  'XXXXXX',   '23', '2', 'Immobilisations en cours');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 22,'PCG99-ABREGE','IMMO',  'XXXXXX',   '27', '2', 'Autres immobilisations financieres');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 23,'PCG99-ABREGE','IMMO',  'XXXXXX',  '280', '2', 'Amortissements des immobilisations incorporelles');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 24,'PCG99-ABREGE','IMMO',  'XXXXXX',  '281', '2', 'Amortissements des immobilisations corporelles');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 25,'PCG99-ABREGE','IMMO',  'XXXXXX',  '290', '2', 'Provisions pour dépréciation des immobilisations incorporelles');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 26,'PCG99-ABREGE','IMMO',  'XXXXXX',  '291', '2', 'Provisions pour dépréciation des immobilisations corporelles');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 27,'PCG99-ABREGE','IMMO',  'XXXXXX',  '297', '2', 'Provisions pour dépréciation des autres immobilisations financières');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 28,'PCG99-ABREGE','STOCK', 'XXXXXX',   '31', '3', 'Matieres premières');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 29,'PCG99-ABREGE','STOCK', 'XXXXXX',   '32', '3', 'Autres approvisionnements');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 30,'PCG99-ABREGE','STOCK', 'XXXXXX',   '33', '3', 'En-cours de production de biens');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 31,'PCG99-ABREGE','STOCK', 'XXXXXX',   '34', '3', 'En-cours de production de services');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 32,'PCG99-ABREGE','STOCK', 'XXXXXX',   '35', '3', 'Stocks de produits');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 33,'PCG99-ABREGE','STOCK', 'XXXXXX',   '37', '3', 'Stocks de marchandises');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 34,'PCG99-ABREGE','STOCK', 'XXXXXX',  '391', '3', 'Provisions pour dépréciation des matières premières');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 35,'PCG99-ABREGE','STOCK', 'XXXXXX',  '392', '3', 'Provisions pour dépréciation des autres approvisionnements');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 36,'PCG99-ABREGE','STOCK', 'XXXXXX',  '393', '3', 'Provisions pour dépréciation des en-cours de production de biens');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 37,'PCG99-ABREGE','STOCK', 'XXXXXX',  '394', '3', 'Provisions pour dépréciation des en-cours de production de services');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 38,'PCG99-ABREGE','STOCK', 'XXXXXX',  '395', '3', 'Provisions pour dépréciation des stocks de produits');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 39,'PCG99-ABREGE','STOCK', 'XXXXXX',  '397', '3', 'Provisions pour dépréciation des stocks de marchandises');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 40,'PCG99-ABREGE','TIERS', 'SUPPLIER','400', '4', 'Fournisseurs et Comptes rattachés');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 41,'PCG99-ABREGE','TIERS', 'XXXXXX',  '409', '4', 'Fournisseurs débiteurs');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 42,'PCG99-ABREGE','TIERS', 'CUSTOMER','410', '4', 'Clients et Comptes rattachés');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 43,'PCG99-ABREGE','TIERS', 'XXXXXX',  '419', '4', 'Clients créditeurs');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 44,'PCG99-ABREGE','TIERS', 'XXXXXX',  '421', '4', 'Personnel');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 45,'PCG99-ABREGE','TIERS', 'XXXXXX',  '428', '4', 'Personnel');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 46,'PCG99-ABREGE','TIERS', 'XXXXXX',   '43', '4', 'Sécurité sociale et autres organismes sociaux');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 47,'PCG99-ABREGE','TIERS', 'XXXXXX',  '444', '4', 'Etat - impôts sur bénéfice');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 48,'PCG99-ABREGE','TIERS', 'XXXXXX',  '445', '4', 'Etat - Taxes sur chiffre affaire');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 49,'PCG99-ABREGE','TIERS', 'XXXXXX',  '447', '4', 'Autres impôts, taxes et versements assimilés');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 50,'PCG99-ABREGE','TIERS', 'XXXXXX',   '45', '4', 'Groupe et associes');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 51,'PCG99-ABREGE','TIERS', 'XXXXXX',  '455','45', 'Associés');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 52,'PCG99-ABREGE','TIERS', 'XXXXXX',   '46', '4', 'Débiteurs divers et créditeurs divers');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 53,'PCG99-ABREGE','TIERS', 'XXXXXX',   '47', '4', 'Comptes transitoires ou d''attente');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 54,'PCG99-ABREGE','TIERS', 'XXXXXX',  '481', '4', 'Charges à répartir sur plusieurs exercices');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 55,'PCG99-ABREGE','TIERS', 'XXXXXX',  '486', '4', 'Charges constatées d''avance');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 56,'PCG99-ABREGE','TIERS', 'XXXXXX',  '487', '4', 'Produits constatés d''avance');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 57,'PCG99-ABREGE','TIERS', 'XXXXXX',  '491', '4', 'Provisions pour dépréciation des comptes de clients');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 58,'PCG99-ABREGE','TIERS', 'XXXXXX',  '496', '4', 'Provisions pour dépréciation des comptes de débiteurs divers');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 59,'PCG99-ABREGE','FINAN', 'XXXXXX',   '50', '5', 'Valeurs mobilières de placement');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 60,'PCG99-ABREGE','FINAN', 'BANK',     '51', '5', 'Banques, établissements financiers et assimilés');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 61,'PCG99-ABREGE','FINAN', 'CASH',     '53', '5', 'Caisse');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 62,'PCG99-ABREGE','FINAN', 'XXXXXX',   '54', '5', 'Régies d''avance et accréditifs');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 63,'PCG99-ABREGE','FINAN', 'XXXXXX',   '58', '5', 'Virements internes');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 64,'PCG99-ABREGE','FINAN', 'XXXXXX',  '590', '5', 'Provisions pour dépréciation des valeurs mobilières de placement');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 65,'PCG99-ABREGE','CHARGE','PRODUCT',  '60', '6', 'Achats');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 66,'PCG99-ABREGE','CHARGE','XXXXXX',  '603','60', 'Variations des stocks');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 67,'PCG99-ABREGE','CHARGE','SERVICE',  '61', '6', 'Services extérieurs');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 68,'PCG99-ABREGE','CHARGE','XXXXXX',   '62', '6', 'Autres services extérieurs');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 69,'PCG99-ABREGE','CHARGE','XXXXXX',   '63', '6', 'Impôts, taxes et versements assimiles');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 70,'PCG99-ABREGE','CHARGE','XXXXXX',  '641', '6', 'Rémunérations du personnel');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 71,'PCG99-ABREGE','CHARGE','XXXXXX',  '644', '6', 'Rémunération du travail de l''exploitant');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 72,'PCG99-ABREGE','CHARGE','SOCIAL',  '645', '6', 'Charges de sécurité sociale et de prévoyance');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 73,'PCG99-ABREGE','CHARGE','XXXXXX',  '646', '6', 'Cotisations sociales personnelles de l''exploitant');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 74,'PCG99-ABREGE','CHARGE','XXXXXX',   '65', '6', 'Autres charges de gestion courante');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 75,'PCG99-ABREGE','CHARGE','XXXXXX',   '66', '6', 'Charges financières');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 76,'PCG99-ABREGE','CHARGE','XXXXXX',   '67', '6', 'Charges exceptionnelles');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 77,'PCG99-ABREGE','CHARGE','XXXXXX',  '681', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 78,'PCG99-ABREGE','CHARGE','XXXXXX',  '686', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 79,'PCG99-ABREGE','CHARGE','XXXXXX',  '687', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 80,'PCG99-ABREGE','CHARGE','XXXXXX',  '691', '6', 'Participation des salariés aux résultats');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 81,'PCG99-ABREGE','CHARGE','XXXXXX',  '695', '6', 'Impôts sur les bénéfices');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 82,'PCG99-ABREGE','CHARGE','XXXXXX',  '697', '6', 'Imposition forfaitaire annuelle des sociétés');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 83,'PCG99-ABREGE','CHARGE','XXXXXX',  '699', '6', 'Produits');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 84,'PCG99-ABREGE','PROD',  'PRODUCT', '701', '7', 'Ventes de produits finis');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 85,'PCG99-ABREGE','PROD',  'SERVICE', '706', '7', 'Prestations de services');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 86,'PCG99-ABREGE','PROD',  'PRODUCT', '707', '7', 'Ventes de marchandises');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 87,'PCG99-ABREGE','PROD',  'PRODUCT', '708', '7', 'Produits des activités annexes');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 88,'PCG99-ABREGE','PROD',  'XXXXXX',  '709', '7', 'Rabais, remises et ristournes accordés par l''entreprise');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 89,'PCG99-ABREGE','PROD',  'XXXXXX',  '713', '7', 'Variation des stocks');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 90,'PCG99-ABREGE','PROD',  'XXXXXX',   '72', '7', 'Production immobilisée');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 91,'PCG99-ABREGE','PROD',  'XXXXXX',   '73', '7', 'Produits nets partiels sur opérations à long terme');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 92,'PCG99-ABREGE','PROD',  'XXXXXX',   '74', '7', 'Subventions d''exploitation');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 93,'PCG99-ABREGE','PROD',  'XXXXXX',   '75', '7', 'Autres produits de gestion courante');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 94,'PCG99-ABREGE','PROD',  'XXXXXX',  '753','75', 'Jetons de présence et rémunérations d''administrateurs, gérants,...');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 95,'PCG99-ABREGE','PROD',  'XXXXXX',  '754','75', 'Ristournes perçues des coopératives');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 96,'PCG99-ABREGE','PROD',  'XXXXXX',  '755','75', 'Quotes-parts de résultat sur opérations faites en commun');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 97,'PCG99-ABREGE','PROD',  'XXXXXX',   '76', '7', 'Produits financiers');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 98,'PCG99-ABREGE','PROD',  'XXXXXX',   '77', '7', 'Produits exceptionnels');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 99,'PCG99-ABREGE','PROD',  'XXXXXX',  '781', '7', 'Reprises sur amortissements et provisions');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (100,'PCG99-ABREGE','PROD',  'XXXXXX',  '786', '7', 'Reprises sur provisions pour risques');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (101,'PCG99-ABREGE','PROD',  'XXXXXX',  '787', '7', 'Reprises sur provisions');
insert into llx_accountingsystem_det (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (102,'PCG99-ABREGE','PROD',  'XXXXXX',   '79', '7', 'Transferts de charges');



drop table if exists llx_c_actioncomm;

create table llx_c_actioncomm
(
  id         integer     PRIMARY KEY,
  code       varchar(12)  UNIQUE NOT NULL,
  type       varchar(10) default 'system' not null,
  libelle    varchar(30) NOT NULL,
  active     tinyint default 1  NOT NULL,
  todo       tinyint
)type=innodb;

delete from llx_c_actioncomm;
insert into llx_c_actioncomm (id, code, type, libelle) values ( 1, 'AC_TEL',  'system', 'Appel Téléphonique');
insert into llx_c_actioncomm (id, code, type, libelle) values ( 2, 'AC_FAX',  'system', 'Envoi Fax');
insert into llx_c_actioncomm (id, code, type, libelle) values ( 3, 'AC_PROP', 'system', 'Envoi Proposition');
insert into llx_c_actioncomm (id, code, type, libelle) values ( 4, 'AC_EMAIL','system', 'Envoi Email');
insert into llx_c_actioncomm (id, code, type, libelle) values ( 5, 'AC_RDV',  'system', 'Prendre rendez-vous');
insert into llx_c_actioncomm (id, code, type, libelle) values ( 9, 'AC_FAC',  'system', 'Envoi Facture');
insert into llx_c_actioncomm (id, code, type, libelle) values (10, 'AC_REL',  'system', 'Relance effectuée');
insert into llx_c_actioncomm (id, code, type, libelle) values (11, 'AC_CLO',  'system', 'Clôture');

alter table llx_actioncomm change percent percent smallint NOT NULL default 0;
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_datea (datea);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_fk_soc (fk_soc);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_fk_contact (fk_contact);
ALTER TABLE llx_actioncomm ADD INDEX idx_actioncomm_fk_facture (fk_facture);


drop table if exists llx_c_ape;

create table llx_c_ape
(
  rowid       integer      AUTO_INCREMENT UNIQUE,
  code_ape    varchar(5)   PRIMARY KEY,
  libelle     varchar(255),
  active      tinyint default 1  NOT NULL
)type=innodb;


delete from llx_c_ape;


create table llx_c_chargesociales
(
  id          integer AUTO_INCREMENT PRIMARY KEY,
  libelle     varchar(80),
  deductible  smallint NOT NULL default 0,
  active      tinyint default 1  NOT NULL
)type=innodb;

insert into llx_c_chargesociales (id,libelle,deductible) values ( 1, 'Allocations familiales',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 2, 'GSG Deductible',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 3, 'GSG/CRDS NON Deductible',0);

drop table c_chargesociales;


drop table if exists llx_c_civilite;

create table llx_c_civilite
(
  rowid       integer    PRIMARY KEY,
  code        varchar(6) UNIQUE NOT NULL,
  civilite	  varchar(50),
  active      tinyint default 1  NOT NULL
)type=innodb;

delete from llx_c_civilite;
insert into llx_c_civilite (rowid, code, civilite, active) values (1 , 'MME',  'Madame', 1);
insert into llx_c_civilite (rowid, code, civilite, active) values (3 , 'MR',   'Monsieur', 1);
insert into llx_c_civilite (rowid, code, civilite, active) values (5 , 'MLE',  'Mademoiselle', 1);
insert into llx_c_civilite (rowid, code, civilite, active) values (7 , 'MTRE', 'Maître', 1);


drop table if exists llx_c_departements;

create table llx_c_departements
(
  rowid       integer         AUTO_INCREMENT PRIMARY KEY,
  code_departement varchar(6) NOT NULL,
  fk_region   integer,
  cheflieu    varchar(7),
  tncc        integer,
  ncc         varchar(50),
  nom         varchar(50),
  active      tinyint default 1  NOT NULL
)type=innodb;

ALTER TABLE llx_c_departements ADD UNIQUE uk_departements (code_departement,fk_region);
ALTER TABLE llx_c_departements ADD INDEX idx_departements_fk_region (fk_region);

delete from llx_c_departements;
insert into llx_c_departements (rowid, fk_region, code_departement,cheflieu,tncc,ncc,nom) values (0,0,'0','0',0,'-','-');

insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'01','01053',5,'AIN','Ain');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (22,'02','02408',5,'AISNE','Aisne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'03','03190',5,'ALLIER','Allier');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'04','04070',4,'ALPES-DE-HAUTE-PROVENCE','Alpes-de-Haute-Provence');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'05','05061',4,'HAUTES-ALPES','Hautes-Alpes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'06','06088',4,'ALPES-MARITIMES','Alpes-Maritimes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'07','07186',5,'ARDECHE','Ardèche');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'08','08105',4,'ARDENNES','Ardennes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'09','09122',5,'ARIEGE','Ariège');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'10','10387',5,'AUBE','Aube');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'11','11069',5,'AUDE','Aude');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'12','12202',5,'AVEYRON','Aveyron');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'13','13055',4,'BOUCHES-DU-RHONE','Bouches-du-Rhône');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (25,'14','14118',2,'CALVADOS','Calvados');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'15','15014',2,'CANTAL','Cantal');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (54,'16','16015',3,'CHARENTE','Charente');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (54,'17','17300',3,'CHARENTE-MARITIME','Charente-Maritime');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'18','18033',2,'CHER','Cher');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (74,'19','19272',3,'CORREZE','Corrèze');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (94,'2A','2A004',3,'CORSE-DU-SUD','Corse-du-Sud');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (94,'2B','2B033',3,'HAUTE-CORSE','Haute-Corse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (26,'21','21231',3,'COTE-D\'OR','Côte-d\'Or');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'22','22278',4,'COTES-D\'ARMOR','Côtes-d\'Armor');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (74,'23','23096',3,'CREUSE','Creuse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'24','24322',3,'DORDOGNE','Dordogne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (43,'25','25056',2,'DOUBS','Doubs');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'26','26362',3,'DROME','Drôme');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (23,'27','27229',5,'EURE','Eure');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'28','28085',1,'EURE-ET-LOIR','Eure-et-Loir');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'29','29232',2,'FINISTERE','Finistère');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'30','30189',2,'GARD','Gard');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'31','31555',3,'HAUTE-GARONNE','Haute-Garonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'32','32013',2,'GERS','Gers');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'33','33063',3,'GIRONDE','Gironde');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'34','34172',5,'HERAULT','Hérault');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'35','35238',1,'ILLE-ET-VILAINE','Ille-et-Vilaine');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'36','36044',5,'INDRE','Indre');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'37','37261',1,'INDRE-ET-LOIRE','Indre-et-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'38','38185',5,'ISERE','Isère');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (43,'39','39300',2,'JURA','Jura');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'40','40192',4,'LANDES','Landes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'41','41018',0,'LOIR-ET-CHER','Loir-et-Cher');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'42','42218',3,'LOIRE','Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'43','43157',3,'HAUTE-LOIRE','Haute-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'44','44109',3,'LOIRE-ATLANTIQUE','Loire-Atlantique');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'45','45234',2,'LOIRET','Loiret');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'46','46042',2,'LOT','Lot');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'47','47001',0,'LOT-ET-GARONNE','Lot-et-Garonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'48','48095',3,'LOZERE','Lozère');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'49','49007',0,'MAINE-ET-LOIRE','Maine-et-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (25,'50','50502',3,'MANCHE','Manche');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'51','51108',3,'MARNE','Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'52','52121',3,'HAUTE-MARNE','Haute-Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'53','53130',3,'MAYENNE','Mayenne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (41,'54','54395',0,'MEURTHE-ET-MOSELLE','Meurthe-et-Moselle');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (41,'55','55029',3,'MEUSE','Meuse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'56','56260',2,'MORBIHAN','Morbihan');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (41,'57','57463',3,'MOSELLE','Moselle');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (26,'58','58194',3,'NIEVRE','Nièvre');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (31,'59','59350',2,'NORD','Nord');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (22,'60','60057',5,'OISE','Oise');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (25,'61','61001',5,'ORNE','Orne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (31,'62','62041',2,'PAS-DE-CALAIS','Pas-de-Calais');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'63','63113',2,'PUY-DE-DOME','Puy-de-Dôme');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'64','64445',4,'PYRENEES-ATLANTIQUES','Pyrénées-Atlantiques');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'65','65440',4,'HAUTES-PYRENEES','Hautes-Pyrénées');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'66','66136',4,'PYRENEES-ORIENTALES','Pyrénées-Orientales');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (42,'67','67482',2,'BAS-RHIN','Bas-Rhin');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (42,'68','68066',2,'HAUT-RHIN','Haut-Rhin');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'69','69123',2,'RHONE','Rhône');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (43,'70','70550',3,'HAUTE-SAONE','Haute-Saône');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (26,'71','71270',0,'SAONE-ET-LOIRE','Saône-et-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'72','72181',3,'SARTHE','Sarthe');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'73','73065',3,'SAVOIE','Savoie');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'74','74010',3,'HAUTE-SAVOIE','Haute-Savoie');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'75','75056',0,'PARIS','Paris');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (23,'76','76540',3,'SEINE-MARITIME','Seine-Maritime');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'77','77288',0,'SEINE-ET-MARNE','Seine-et-Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'78','78646',4,'YVELINES','Yvelines');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (54,'79','79191',4,'DEUX-SEVRES','Deux-Sèvres');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (22,'80','80021',3,'SOMME','Somme');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'81','81004',2,'TARN','Tarn');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'82','82121',0,'TARN-ET-GARONNE','Tarn-et-Garonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'83','83137',2,'VAR','Var');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'84','84007',0,'VAUCLUSE','Vaucluse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'85','85191',3,'VENDEE','Vendée');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (54,'86','86194',3,'VIENNE','Vienne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (74,'87','87085',3,'HAUTE-VIENNE','Haute-Vienne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (41,'88','88160',4,'VOSGES','Vosges');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (26,'89','89024',5,'YONNE','Yonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (43,'90','90010',0,'TERRITOIRE DE BELFORT','Territoire de Belfort');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'91','91228',5,'ESSONNE','Essonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'92','92050',4,'HAUTS-DE-SEINE','Hauts-de-Seine');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'93','93008',3,'SEINE-SAINT-DENIS','Seine-Saint-Denis');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'94','94028',2,'VAL-DE-MARNE','Val-de-Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'95','95500',2,'VAL-D\'OISE','Val-d\'Oise');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values ( 1,'971','97105',3,'GUADELOUPE','Guadeloupe');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values ( 2,'972','97209',3,'MARTINIQUE','Martinique');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values ( 3,'973','97302',3,'GUYANE','Guyane');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values ( 4,'974','97411',3,'REUNION','Réunion');

insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'01','',1,'ANVERS','Anvers');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (203,'02','',3,'BRUXELLES-CAPITALE','Bruxelles-Capitale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'03','',2,'BRABANT-WALLON','Brabant-Wallon');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'04','',1,'BRABANT-FLAMAND','Brabant-Flamand');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'05','',1,'FLANDRE-OCCIDENTALE','Flandre-Occidentale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'06','',1,'FLANDRE-ORIENTALE','Flandre-Orientale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'07','',2,'HAINAUT','Hainaut');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'08','',2,'LIEGE','Liège');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'09','',1,'LIMBOURG','Limbourg');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'10','',2,'LUXEMBOURG','Luxembourg');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'11','',2,'NAMUR','Namur');


drop table if exists llx_c_effectif;

create table llx_c_effectif
(
  id      integer     PRIMARY KEY,
  code    varchar(12)  UNIQUE NOT NULL,
  libelle varchar(30),
  active  tinyint default 1  NOT NULL
)type=innodb;

delete from llx_c_effectif;
insert into llx_c_effectif (id,code,libelle) values (0, 'EF0',       '-');
insert into llx_c_effectif (id,code,libelle) values (1, 'EF1-5',     '1 - 5');
insert into llx_c_effectif (id,code,libelle) values (2, 'EF6-10',    '6 - 10');
insert into llx_c_effectif (id,code,libelle) values (3, 'EF11-50',   '11 - 50');
insert into llx_c_effectif (id,code,libelle) values (4, 'EF51-100',  '51 - 100');
insert into llx_c_effectif (id,code,libelle) values (5, 'EF100-500', '100 - 500');
insert into llx_c_effectif (id,code,libelle) values (6, 'EF500-',    '> 500');


drop table if exists llx_c_forme_juridique;

create table llx_c_forme_juridique
(
  rowid      integer       AUTO_INCREMENT PRIMARY KEY,
  code       varchar(12)   UNIQUE NOT NULL,
  fk_pays    integer       NOT NULL,
  libelle    varchar(255),
  active     tinyint default 1  NOT NULL

)type=innodb;

delete from llx_c_forme_juridique;

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (0, '0','-');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'11','Artisan Commerçant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'12','Commerçant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'13','Artisan');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'14','Officier public ou ministériel');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'15','Profession libérale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'16','Exploitant agricole');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'17','Agent commercial');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'18','Associé Gérant de société');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'19','(Autre) personne physique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'21','Indivision');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'22','Société créée de fait');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'23','Société en participation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'27','Paroisse hors zone concordataire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'29','Autre groupement de droit privé non doté de la personnalité morale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'31','Personne morale de droit étranger, immatriculée au RCS');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'32','Personne morale de droit étranger, non immatriculée au RCS');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'41','Établissement public ou régie à caractère industriel ou commercial');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'51','Société coopérative commerciale particulière');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'52','Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'53','Société en commandite');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'54','Société à responsabilité limité (SARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'55','Société anonyme à conseil d\'administration');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'56','Société anonyme à directoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'57','Société par actions simplifiée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'58','Entreprise Unipersonnelle à Responsabilité Limitée (EURL)');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'61','Caisse d\'épargne et de prévoyance');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'62','Groupement d\'intérêt économique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'63','Société coopérative agricole');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'64','Société non commerciale d\'assurances');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'65','Société civile');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'69','Autres personnes de droit privé inscrites au registre du commerce et des sociétés');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'71','Administration de l\'état');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'72','Collectivité territoriale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'73','Établissement public administratif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'74','Autre personne morale de droit public administratif');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'81','Organisme gérant un régime de protection social à adhésion obligatoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'82','Organisme mutualiste');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'83','Comité d\'entreprise');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'84','Organisme professionnel');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'85','Organisme de retraite à adhésion non obligatoire');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'91','Syndicat de propriétaires');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'92','Association loi 1901 ou assimilé');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'93','Fondation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'99','Autre personne morale de droit privé');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,'100','Indépendant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,'101','SPRL - Société à responsabilité limitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,'102','SA   - Société Anonyme');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,'103','SCRL - Société coopérative à responsabilité limitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,'104','ASBL - Association sans but Lucratif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,'105','SCRI - Société coopérative à responsabilité illimitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,'106','SCS  - Société en comanndite simple');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,'107','SCA  - Société en commandite par action');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,'108','SNC  - Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,'109','GIE  - Groupement d\'intérêt économique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,'110','GEIE - Groupement européen d\'intérêt économique');

drop table if exists llx_c_paiement;

create table llx_c_paiement
(
  id         integer     PRIMARY KEY,
  code       varchar(6)  UNIQUE NOT NULL,
  libelle    varchar(30),
  type       smallint,	
  active     tinyint default 1  NOT NULL
)type=innodb;

delete from llx_c_paiement;
insert into llx_c_paiement (id,code,libelle,type,active) values (0, '',    '-',                 3,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (1, 'TIP', 'TIP',               2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (2, 'VIR', 'Virement',          2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (3, 'PRE', 'Prélèvement',       2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (4, 'LIQ', 'Liquide',           2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (5, 'VAD', 'Paiement en ligne', 2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (6, 'CB',  'Carte Bancaire',    2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (7, 'CHQ', 'Chèque',            2,1);

drop table if exists llx_c_pays;

create table llx_c_pays
(
  rowid    integer     PRIMARY KEY,
  code     varchar(6)  UNIQUE NOT NULL,
  libelle  varchar(25)        NOT NULL,
  active   tinyint default 1  NOT NULL
)type=innodb;

delete from llx_c_pays;
insert into llx_c_pays (rowid,code,libelle) values (0,  ''  , '-'              );
insert into llx_c_pays (rowid,code,libelle) values (1,  'FR', 'France'         );
insert into llx_c_pays (rowid,code,libelle) values (2,  'BE', 'Belgique'       );
insert into llx_c_pays (rowid,code,libelle) values (3,  'IT', 'Italie'         );
insert into llx_c_pays (rowid,code,libelle) values (4,  'ES', 'Espagne'        );
insert into llx_c_pays (rowid,code,libelle) values (5,  'DE', 'Allemagne'      );
insert into llx_c_pays (rowid,code,libelle) values (6,  'CH', 'Suisse'         );
insert into llx_c_pays (rowid,code,libelle) values (7,  'GB', 'Royaume uni'    );
insert into llx_c_pays (rowid,code,libelle) values (8,  'IE', 'Irlande'        );
insert into llx_c_pays (rowid,code,libelle) values (9,  'CN', 'Chine'          );
insert into llx_c_pays (rowid,code,libelle) values (10, 'TN', 'Tunisie'        );
insert into llx_c_pays (rowid,code,libelle) values (11, 'US', 'Etats Unis'     );
insert into llx_c_pays (rowid,code,libelle) values (12, 'MA', 'Maroc'          );
insert into llx_c_pays (rowid,code,libelle) values (13, 'DZ', 'Algérie'        );
insert into llx_c_pays (rowid,code,libelle) values (14, 'CA', 'Canada'         );
insert into llx_c_pays (rowid,code,libelle) values (15, 'TG', 'Togo'           );
insert into llx_c_pays (rowid,code,libelle) values (16, 'GA', 'Gabon'          );
insert into llx_c_pays (rowid,code,libelle) values (17, 'NL', 'Pays Bas'       );
insert into llx_c_pays (rowid,code,libelle) values (18, 'HU', 'Hongrie'        );
insert into llx_c_pays (rowid,code,libelle) values (19, 'RU', 'Russie'         );
insert into llx_c_pays (rowid,code,libelle) values (20, 'SE', 'Suède'          );
insert into llx_c_pays (rowid,code,libelle) values (21, 'CI', 'Côte d\'Ivoire' );
insert into llx_c_pays (rowid,code,libelle) values (23, 'SN', 'Sénégal'        );
insert into llx_c_pays (rowid,code,libelle) values (24, 'AR', 'Argentine'      );
insert into llx_c_pays (rowid,code,libelle) values (25, 'CM', 'Cameroun'       );

drop table if exists llx_c_propalst;

create table llx_c_propalst
(
  id              smallint    PRIMARY KEY,
  code            varchar(12)  UNIQUE NOT NULL,
  label           varchar(30),
  active          tinyint default 1  NOT NULL
)type=innodb;

delete from llx_c_propalst;
insert into llx_c_propalst (id,code,label) values (0, 'PR_DRAFT',     'Brouillon');
insert into llx_c_propalst (id,code,label) values (1, 'PR_OPEN',      'Ouverte');
insert into llx_c_propalst (id,code,label) values (2, 'PR_SIGNED',    'Signée');
insert into llx_c_propalst (id,code,label) values (3, 'PR_NOTSIGNED', 'Non Signée');
insert into llx_c_propalst (id,code,label) values (4, 'PR_FAC',       'Facturée');

drop table if exists llx_c_stcomm;

create table llx_c_stcomm
(
  id       integer     PRIMARY KEY,
  code     varchar(12)  UNIQUE NOT NULL,
  libelle  varchar(30),
  active   tinyint default 1  NOT NULL
)type=innodb;

delete from llx_c_stcomm;
insert into llx_c_stcomm (id,code,libelle) values (-1, 'ST_NO',    'Ne pas contacter');
insert into llx_c_stcomm (id,code,libelle) values ( 0, 'ST_NEVER', 'Jamais contacté');
insert into llx_c_stcomm (id,code,libelle) values ( 1, 'ST_TODO',  'A contacter');
insert into llx_c_stcomm (id,code,libelle) values ( 2, 'ST_PEND',  'Contact en cours');
insert into llx_c_stcomm (id,code,libelle) values ( 3, 'ST_DONE',  'Contactée');

drop table if exists llx_c_typent;

create table llx_c_typent
(
  id        integer     PRIMARY KEY,
  code      varchar(12)  UNIQUE NOT NULL,
  libelle   varchar(30),
  active    tinyint default 1  NOT NULL
)type=innodb;

delete from llx_c_typent;
insert into llx_c_typent (id,code,libelle) values (  0, 'TE_UNKNOWN', '-');
insert into llx_c_typent (id,code,libelle) values (  1, 'TE_STARTUP', 'Start-up');
insert into llx_c_typent (id,code,libelle) values (  2, 'TE_GROUP',   'Grand groupe');
insert into llx_c_typent (id,code,libelle) values (  3, 'TE_MEDIUM',  'PME/PMI');
insert into llx_c_typent (id,code,libelle) values (  4, 'TE_ADMIN',   'Administration');
insert into llx_c_typent (id,code,libelle) values (100, 'TE_OTHER',   'Autres');

drop table if exists llx_c_regions;

create table llx_c_regions
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  code_region integer UNIQUE NOT NULL,
  fk_pays     integer NOT NULL,
  cheflieu    varchar(7),
  tncc        integer,
  nom         varchar(50),
  active      tinyint default 1 NOT NULL
)type=innodb;

delete from llx_c_regions;
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (0,0,0,'0',0,'-');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (101,1,  1,'97105',3,'Guadeloupe');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (102,1,  2,'97209',3,'Martinique');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (103,1,  3,'97302',3,'Guyane');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (104,1,  4,'97411',3,'Réunion');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (105,1, 11,'75056',1,'Île-de-France');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (106,1, 21,'51108',0,'Champagne-Ardenne');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (107,1, 22,'80021',0,'Picardie');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (108,1, 23,'76540',0,'Haute-Normandie');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (109,1, 24,'45234',2,'Centre');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (110,1, 25,'14118',0,'Basse-Normandie');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (111,1, 26,'21231',0,'Bourgogne');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (112,1, 31,'59350',2,'Nord-Pas-de-Calais');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (113,1, 41,'57463',0,'Lorraine');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (114,1, 42,'67482',1,'Alsace');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (115,1, 43,'25056',0,'Franche-Comté');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (116,1, 52,'44109',4,'Pays de la Loire');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (117,1, 53,'35238',0,'Bretagne');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (118,1, 54,'86194',2,'Poitou-Charentes');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (119,1, 72,'33063',1,'Aquitaine');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (120,1, 73,'31555',0,'Midi-Pyrénées');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (121,1, 74,'87085',2,'Limousin');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (122,1, 82,'69123',2,'Rhône-Alpes');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (123,1, 83,'63113',1,'Auvergne');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (124,1, 91,'34172',2,'Languedoc-Roussillon');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (125,1, 93,'13055',0,'Provence-Alpes-Côte d\'Azur');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (126,1, 94,'2A004',0,'Corse');

insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (201,2,201,'',1,'Flandre');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (202,2,202,'',2,'Wallonie');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (203,2,203,'',3,'Bruxelles-Capitale');


create table llx_c_currencies
(
  code        varchar(2)   UNIQUE PRIMARY KEY,
  code_iso    varchar(3)   UNIQUE NOT NULL,
  label       varchar(64),
  active      tinyint DEFAULT 1  NOT NULL
)type=innodb;

insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'BT', 'THB', 1, 'Bath thailandais'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CD', 'DKK', 1, 'Couronnes dannoises'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CN', 'NOK', 1, 'Couronnes norvegiennes'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CS', 'SEK', 1, 'Couronnes suedoises'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CZ', 'CZK', 1, 'Couronnes tcheques'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DA', 'AUD', 1, 'Dollars australiens'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DC', 'CAD', 1, 'Dollars canadiens'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DH', 'HKD', 1, 'Dollars hong kong'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DS', 'SGD', 1, 'Dollars singapour'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DU', 'USD', 1, 'Dollars us'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'EC', 'XEU', 1, 'Ecus'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'ES', 'PTE', 1, 'Escudos'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FB', 'BEF', 1, 'Francs belges'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FF', 'FRF', 1, 'Francs francais'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FL', 'LUF', 1, 'Francs luxembourgeois'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FO', 'NLG', 1, 'Florins'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FS', 'CHF', 1, 'Francs suisses'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LI', 'IEP', 1, 'Livres irlandaises'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LR', 'ITL', 1, 'Lires'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LS', 'GBP', 1, 'Livres sterling'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'MA', 'DEM', 1, 'Deutsch mark'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'MF', 'FIM', 1, 'Mark finlandais'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'PA', 'ARP', 1, 'Pesos argentins'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'PC', 'CLP', 1, 'Pesos chilien'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'PE', 'ESP', 1, 'Pesete'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'PL', 'PLN', 1, 'Zlotys polonais'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'SA', 'ATS', 1, 'Shiliing autrichiens'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'TW', 'TWD', 1, 'Dollar taiwanais'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'YE', 'JPY', 1, 'Yens'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'ZA', 'ZAR', 1, 'Rand africa'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DR', 'GRD', 1, 'Drachme (grece)'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'EU', 'EUR', 1, 'Euros'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'RB', 'BRL', 1, 'Real bresilien'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'SK', 'SKK', 1, 'Couronnes slovaques'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'YC', 'CNY', 1, 'Yuang chinois');
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'AE', 'AED', 1, 'Arabes emirats dirham');
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CF', 'XAF', 1, 'Francs cfa beac');
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'EG', 'EGP', 1, 'Livre egyptienne');
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'KR', 'KRW', 1, 'Won coree du sud');
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'NZ', 'NZD', 1, 'Dollar neo-zelandais');
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'TR', 'TRL', 1, 'Livre turque');
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'ID', 'IDR', 1, 'Rupiahs d''indonesie');
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'IN', 'INR', 1, 'Roupie indienne'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LT', 'LTL', 1, 'Litas');
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'RU', 'SUR', 1, 'Rouble');
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FH', 'HUF', 1, 'Forint hongrois');
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LK', 'LKR', 1, 'Roupie sri lanka'); 

create table llx_contratdet
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,

  fk_contrat            integer NOT NULL,
  fk_product            integer NOT NULL,

  statut                smallint DEFAULT 0,

  label                 text, -- libellé du produit
  description           text,

  date_commande         datetime,
  date_ouverture_prevue datetime,
  date_ouverture        datetime, -- date d'ouverture du service chez le client
  date_fin_validite     datetime,
  date_cloture          datetime,

  tva_tx                real DEFAULT 19.6, -- taux tva
  qty                   real,              -- quantité
  remise_percent        real DEFAULT 0,    -- pourcentage de remise
  remise                real DEFAULT 0,    -- montant de la remise
  subprice              real,              -- prix avant remise
  price_ht              real,              -- prix final

  fk_user_author        integer NOT NULL default 0,
  fk_user_ouverture     integer,
  fk_user_cloture       integer,
  commentaire           text

)type=innodb;

create table llx_dolibarr_modules
(
  numero         integer     PRIMARY KEY,
  active         tinyint     DEFAULT 0 NOT NULL,
  active_date    datetime    NOT NULL,
  active_version varchar(25) NOT NULL

)type=innodb;


insert into llx_const (name, value, type, visible, note) VALUES ('MAIN_FORCE_SETLOCALE_LC_ALL',      'MAIN_FORCE_SETLOCALE_LC_ALL', 'chaine', 1, 'Pour forcer LC_ALL si pb de locale');
insert into llx_const (name, value, type, visible, note) VALUES ('MAIN_FORCE_SETLOCALE_LC_TIME',     'MAIN_FORCE_SETLOCALE_LC_TIME', 'chaine', 1, 'Pour forcer LC_TIME si pb de locale');
insert into llx_const (name, value, type, visible, note) VALUES ('MAIN_FORCE_SETLOCALE_LC_MONETARY', 'MAIN_FORCE_SETLOCALE_LC_MONETARY', 'chaine', 1, 'Pour forcer LC_MONETARY si pb de locale');
insert into llx_const (name, value, type, visible, note) VALUES ('MAIN_FORCE_SETLOCALE_LC_NUMERIC',  'MAIN_FORCE_SETLOCALE_LC_NUMERIC', 'chaine', 1, 'Mettre la valeur C si problème de centimes');


update llx_const set name='OSC_DB_NAME' where name='DB_NAME_OSC';
update llx_const set name='MAIN_EMAIL_FROM' where name='MAIN_MAIL_FROM';

alter table llx_bookmark add url         varchar(128);
alter table llx_bookmark add target      varchar(16);
alter table llx_bookmark add title       varchar(64);
alter table llx_bookmark add favicon     varchar(24);



create table llx_energie_compteur
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  libelle         varchar(50),
  fk_energie      integer NOT NULL,
  datec           datetime,
  fk_user_author  integer NOT NULL,

  note            text
)type=innodb;

create table llx_energie_compteur_groupe
(
  fk_energie_compteur integer NOT NULL,
  fk_energie_groupe   integer NOT NULL
)type=innodb;

create table llx_energie_compteur_releve
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_compteur     integer NOT NULL,
  date_releve     datetime,
  valeur          real,
  datec           datetime,
  fk_user_author  integer NOT NULL,

  note            text
)type=innodb;


create table llx_energie_groupe
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  libelle         varchar(100),
  datec           datetime,
  fk_user_author  integer NOT NULL,

  note            text
)type=innodb;

create table llx_contrat_service
(
  rowid                   integer AUTO_INCREMENT PRIMARY KEY,
  tms                     timestamp,
  enservice               smallint default 0,
  mise_en_service         datetime,
  fin_validite            datetime,
  date_cloture            datetime,
  fk_contrat              integer NOT NULL,
  fk_product              integer NOT NULL,

  fk_facture              integer NOT NULL default 0,
  fk_facturedet           integer NOT NULL default 0,

  fk_user_mise_en_service integer,
  fk_user_cloture         integer

)type=innodb;

create table llx_projet_task
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  fk_projet          integer NOT NULL,
  fk_task_parent     integer NOT NULL,
  title              varchar(255),
  duration_effective real NOT NULL,
  fk_user_creat      integer,      -- createur
  statut             enum('open','closed') DEFAULT 'open',
  note               text,

  key(fk_projet),
  key(statut),
  key(fk_user_creat)
  
)type=innodb;

create table llx_projet_task_time
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  fk_task          integer  NOT NULL,
  task_date        date,
  task_duration    real UNSIGNED,
  fk_user          integer,
  note             text,

  key(fk_task),
  key(fk_user)

)type=innodb;

create table llx_projet_task_actors
(
  fk_projet_task integer NOT NULL,
  fk_user        integer NOT NULL,
  role           enum ('admin','read','acto','info') DEFAULT 'admin',

  UNIQUE (fk_projet_task, fk_user),
  key (role)

)type=innodb;


create table llx_societe_perms
(
  fk_soc    integer,
  fk_user   integer,
  pread     tinyint unsigned DEFAULT 0, 
  pwrite    tinyint unsigned DEFAULT 0,
  pperms    tinyint unsigned DEFAULT 0, 

  UNIQUE INDEX(fk_soc, fk_user)
)type=innodb;


drop table if exists llx_element_contact;
drop table if exists llx_c_type_contact;

create table llx_c_type_contact
(
  rowid      	integer     PRIMARY KEY,
  element       varchar(30) NOT NULL,
  source        varchar(8)  DEFAULT 'external' NOT NULL,
  code          varchar(16) NOT NULL,
  libelle 	    varchar(64)	NOT NULL,
  active  	    tinyint DEFAULT 1  NOT NULL
)type=innodb;


ALTER TABLE llx_c_type_contact 
	ADD UNIQUE INDEX idx_c_type_contact_uk (element, source, code);


create table llx_element_contact
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,  
  datecreate      datetime NULL, 			-- date de creation de l'enregistrement
  statut          smallint DEFAULT 5, 		-- 5 inactif, 4 actif
  
  element_id		int NOT NULL, 		    -- la reference de l'element.
  fk_c_type_contact	int NOT NULL,	        -- nature du contact.
  fk_socpeople      integer NOT NULL
)type=innodb;


ALTER TABLE llx_element_contact 
	ADD UNIQUE INDEX idx_element_contact_idx1 (element_id, fk_c_type_contact, fk_socpeople);
	
ALTER TABLE llx_element_contact 
	ADD CONSTRAINT idx_element_contact_fk_c_type_contact		
	FOREIGN KEY (fk_c_type_contact)     REFERENCES llx_c_type_contact(rowid);
	
	
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (10, 'contrat', 'internal', 'SALESREPSIGN',  'Commercial signataire du contrat', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (11, 'contrat', 'internal', 'SALESREPFOLL',  'Commercial suivi du contrat', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (20, 'contrat', 'external', 'BILLING',       'Contact client facturation contrat', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (21, 'contrat', 'external', 'CUSTOMER',      'Contact client suivi contrat', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (22, 'contrat', 'external', 'SALESREPSIGN',  'Contact client signataire contrat', 1);
                                                                                                    
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (30, 'propal',  'internal', 'SALESREPSIGN',  'Commercial signataire de la propale', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (31, 'propal',  'internal', 'SALESREPFOLL',  'Commercial suivi de la propale', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (40, 'propal',  'external', 'BILLING',       'Contact client facturation propale', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (41, 'propal',  'external', 'CUSTOMER',      'Contact client suivi propale', 1);
                                                                                                    
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (80, 'projet',  'internal', 'PROJECTLEADER', 'Chef de Projet', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (81, 'projet',  'external', 'PROJECTLEADER', 'Chef de Projet', 1);

	
alter table llx_commande add ref_client varchar(30) after ref;
alter table llx_facture add ref_client varchar(30) after facnumber;
alter table llx_facture add date_valid date after datef;
alter table llx_facture add model varchar(50) after note;
