--
--
-- Attention à l ordre des requetes
-- ce fichier doit être chargé sur une version 1.1.0 
-- sans AUCUNE erreur ni warning
-- 
alter table llx_contrat add fk_facturedet integer NOT NULL default 0 after fk_facture;
alter table llx_contrat change fk_user_cloture fk_user_cloture integer;
alter table llx_contrat change fk_user_mise_en_service fk_user_mise_en_service integer;

alter table llx_facturedet add date_start date;
alter table llx_facturedet add date_end   date;

alter table llx_user add egroupware_id integer;
alter table llx_societe add code_client  varchar(15) after nom;
alter table llx_societe add siret     varchar(14) after siren;
alter table llx_societe add ape       varchar(4) after siret;
alter table llx_societe add tva_intra varchar(20) after ape;
alter table llx_societe add capital real after tva_intra;
alter table llx_societe add rubrique varchar(255);
alter table llx_societe add remise_client real default 0;

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

alter table llx_entrepot add statut tinyint default 1;

alter table llx_product add stock_propale integer default 0;
alter table llx_product add stock_commande integer default 0;
alter table llx_product add seuil_stock_alerte integer default 0;

alter table llx_groupart add description text after groupart ;

alter table llx_socpeople add phone_perso varchar(30) after phone ;
alter table llx_socpeople add phone_mobile varchar(30) after phone_perso ;
alter table llx_socpeople add jabberid varchar(255) after email ;
alter table llx_socpeople add birthday date after address ;
alter table llx_socpeople add tms timestamp after datec ;

alter table llx_facture_fourn drop index facnumber ;
alter table llx_facture_fourn add unique index (facnumber, fk_soc) ;

alter table llx_bank_account modify bank varchar(60);
alter table llx_bank_account modify domiciliation varchar(255);
alter table llx_bank_account add proprio varchar(60) after domiciliation ;
alter table llx_bank_account add adresse_proprio varchar(255) after proprio ;
alter table llx_bank_account add account_number varchar(8) after clos ;
update llx_bank_account set account_number = '51' where account_number is null;

alter table llx_paiement add fk_bank integer NOT NULL after note ;
alter table llx_paiementfourn add fk_bank integer NOT NULL after note ;


alter table c_actioncomm     rename llx_c_actioncomm ;
alter table c_chargesociales rename llx_c_chargesociales ;
alter table c_effectif       rename llx_c_effectif ;
alter table c_paiement       rename llx_c_paiement ;
alter table c_pays           rename llx_c_pays ;
alter table c_propalst       rename llx_c_propalst ;
alter table c_stcomm         rename llx_c_stcomm ;
alter table c_typent         rename llx_c_typent ;



alter table llx_c_actioncomm add type varchar(10) not null default 'system' after id;
alter table llx_c_actioncomm add active tinyint default 1 NOT NULL after libelle;

alter table llx_c_paiement add code varchar(6) after id;



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

create table llx_paiement_facture
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_paiement     integer,
  fk_facture      integer,
  amount          real default 0,
  
  key (fk_paiement),
  key( fk_facture)
)type=innodb;


insert into llx_const(name, value, type, note, visible) values ('MAIN_UPLOAD_DOC','1','chaine','Authorise l\'upload de document',1);
insert into llx_const(name, value, type, note, visible) values ('MAIN_SEARCHFORM_PRODUITSERVICE','1','yesno','Affichage formulaire de recherche des Produits et Services dans la barre de gauche',0);
delete from llx_const where name = 'COMPTA_BANK_FACTURES';

update llx_bank set fk_type = 'VAD' where fk_type = 'WWW';

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


update llx_const set visible=0 where name like 'ADHERENT%';
update llx_const set visible=0 where name like 'PROPALE_ADDON%';

create table llx_user_param
(
  fk_user       integer,
  page          varchar(255),
  param         varchar(255),
  value         varchar(255),
  UNIQUE (fk_user,page,param)
)type=innodb;

create table llx_cash_account
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  datec          datetime,
  tms            timestamp,
  label          varchar(30),
  courant        smallint default 0 not null,
  clos           smallint default 0 not null,
  account_number varchar(8)
)type=innodb;

create table llx_cash
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  dateo           date NOT NULL,
  amount          real NOT NULL default 0,
  label           varchar(255),
  fk_account      integer,
  fk_user_author  integer,
  fk_type         varchar(4),
  note            text
)type=innodb;


update llx_bank set datev=dateo where datev is null;

update llx_chargesociales set periode=date_ech where periode is null or periode = '0000-00-00';



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


create table llx_contact_facture
(
  idp          integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc       integer NOT NULL,
  fk_contact   integer NOT NULL,   -- point sur llx_socpeople

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

create table llx_c_accountingsystem
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_pays         integer      NOT NULL,
  pcg_version     varchar(12)  NOT NULL,
  pcg_type        varchar(20)  NOT NULL,
  pcg_subtype     varchar(20)  NOT NULL,
  label           varchar(128) NOT NULL,
  account_number  varchar(20)  NOT NULL,
  account_parent  varchar(20)
)type=innodb;

delete from llx_c_accountingsystem;
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  1,1,'PCG99-ABREGE','CAPIT', 'CAPITAL', '101', '1', 'Capital');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  2,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '105', '1', 'Ecarts de réévaluation');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  3,1,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1061', '1', 'Réserve légale');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  4,1,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1063', '1', 'Réserves statutaires ou contractuelles');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  5,1,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1064', '1', 'Réserves réglementées');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  6,1,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1068', '1', 'Autres réserves');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  7,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '108', '1', 'Compte de l''exploitant');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  8,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '12', '1', 'Résultat de l''exercice');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  9,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '145', '1', 'Amortissements dérogatoires');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 10,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '146', '1', 'Provision spéciale de réévaluation');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 11,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '147', '1', 'Plus-values réinvesties');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 12,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '148', '1', 'Autres provisions réglementées');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 13,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '15', '1', 'Provisions pour risques et charges');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 14,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '16', '1', 'Emprunts et dettes assimilees');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 15,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   '20', '2', 'Immobilisations incorporelles');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 16,1,'PCG99-ABREGE','IMMO',  'XXXXXX',  '201','20', 'Frais d''établissement');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 17,1,'PCG99-ABREGE','IMMO',  'XXXXXX',  '206','20', 'Droit au bail');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 18,1,'PCG99-ABREGE','IMMO',  'XXXXXX',  '207','20', 'Fonds commercial');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 19,1,'PCG99-ABREGE','IMMO',  'XXXXXX',  '208','20', 'Autres immobilisations incorporelles');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 20,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   '21', '2', 'Immobilisations corporelles');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 21,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   '23', '2', 'Immobilisations en cours');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 22,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   '27', '2', 'Autres immobilisations financieres');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 23,1,'PCG99-ABREGE','IMMO',  'XXXXXX',  '280', '2', 'Amortissements des immobilisations incorporelles');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 24,1,'PCG99-ABREGE','IMMO',  'XXXXXX',  '281', '2', 'Amortissements des immobilisations corporelles');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 25,1,'PCG99-ABREGE','IMMO',  'XXXXXX',  '290', '2', 'Provisions pour dépréciation des immobilisations incorporelles');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 26,1,'PCG99-ABREGE','IMMO',  'XXXXXX',  '291', '2', 'Provisions pour dépréciation des immobilisations corporelles');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 27,1,'PCG99-ABREGE','IMMO',  'XXXXXX',  '297', '2', 'Provisions pour dépréciation des autres immobilisations financières');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 28,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   '31', '3', 'Matieres premières');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 29,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   '32', '3', 'Autres approvisionnements');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 30,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   '33', '3', 'En-cours de production de biens');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 31,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   '34', '3', 'En-cours de production de services');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 32,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   '35', '3', 'Stocks de produits');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 33,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   '37', '3', 'Stocks de marchandises');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 34,1,'PCG99-ABREGE','STOCK', 'XXXXXX',  '391', '3', 'Provisions pour dépréciation des matières premières');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 35,1,'PCG99-ABREGE','STOCK', 'XXXXXX',  '392', '3', 'Provisions pour dépréciation des autres approvisionnements');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 36,1,'PCG99-ABREGE','STOCK', 'XXXXXX',  '393', '3', 'Provisions pour dépréciation des en-cours de production de biens');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 37,1,'PCG99-ABREGE','STOCK', 'XXXXXX',  '394', '3', 'Provisions pour dépréciation des en-cours de production de services');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 38,1,'PCG99-ABREGE','STOCK', 'XXXXXX',  '395', '3', 'Provisions pour dépréciation des stocks de produits');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 39,1,'PCG99-ABREGE','STOCK', 'XXXXXX',  '397', '3', 'Provisions pour dépréciation des stocks de marchandises');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 40,1,'PCG99-ABREGE','TIERS', 'SUPPLIER','400', '4', 'Fournisseurs et Comptes rattachés');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 41,1,'PCG99-ABREGE','TIERS', 'XXXXXX',  '409', '4', 'Fournisseurs débiteurs');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 42,1,'PCG99-ABREGE','TIERS', 'CUSTOMER','410', '4', 'Clients et Comptes rattachés');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 43,1,'PCG99-ABREGE','TIERS', 'XXXXXX',  '419', '4', 'Clients créditeurs');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 44,1,'PCG99-ABREGE','TIERS', 'XXXXXX',  '421', '4', 'Personnel');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 45,1,'PCG99-ABREGE','TIERS', 'XXXXXX',  '428', '4', 'Personnel');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 46,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   '43', '4', 'Sécurité sociale et autres organismes sociaux');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 47,1,'PCG99-ABREGE','TIERS', 'XXXXXX',  '444', '4', 'Etat - impôts sur bénéfice');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 48,1,'PCG99-ABREGE','TIERS', 'XXXXXX',  '445', '4', 'Etat - Taxes sur chiffre affaire');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 49,1,'PCG99-ABREGE','TIERS', 'XXXXXX',  '447', '4', 'Autres impôts, taxes et versements assimilés');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 50,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   '45', '4', 'Groupe et associes');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 51,1,'PCG99-ABREGE','TIERS', 'XXXXXX',  '455','45', 'Associés');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 52,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   '46', '4', 'Débiteurs divers et créditeurs divers');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 53,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   '47', '4', 'Comptes transitoires ou d''attente');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 54,1,'PCG99-ABREGE','TIERS', 'XXXXXX',  '481', '4', 'Charges à répartir sur plusieurs exercices');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 55,1,'PCG99-ABREGE','TIERS', 'XXXXXX',  '486', '4', 'Charges constatées d''avance');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 56,1,'PCG99-ABREGE','TIERS', 'XXXXXX',  '487', '4', 'Produits constatés d''avance');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 57,1,'PCG99-ABREGE','TIERS', 'XXXXXX',  '491', '4', 'Provisions pour dépréciation des comptes de clients');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 58,1,'PCG99-ABREGE','TIERS', 'XXXXXX',  '496', '4', 'Provisions pour dépréciation des comptes de débiteurs divers');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 59,1,'PCG99-ABREGE','FINAN', 'XXXXXX',   '50', '5', 'Valeurs mobilières de placement');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 60,1,'PCG99-ABREGE','FINAN', 'BANK',     '51', '5', 'Banques, établissements financiers et assimilés');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 61,1,'PCG99-ABREGE','FINAN', 'CASH',     '53', '5', 'Caisse');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 62,1,'PCG99-ABREGE','FINAN', 'XXXXXX',   '54', '5', 'Régies d''avance et accréditifs');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 63,1,'PCG99-ABREGE','FINAN', 'XXXXXX',   '58', '5', 'Virements internes');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 64,1,'PCG99-ABREGE','FINAN', 'XXXXXX',  '590', '5', 'Provisions pour dépréciation des valeurs mobilières de placement');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 65,1,'PCG99-ABREGE','CHARGE','PRODUCT',  '60', '6', 'Achats');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 66,1,'PCG99-ABREGE','CHARGE','XXXXXX',  '603','60', 'Variations des stocks');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 67,1,'PCG99-ABREGE','CHARGE','SERVICE',  '61', '6', 'Services extérieurs');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 68,1,'PCG99-ABREGE','CHARGE','XXXXXX',   '62', '6', 'Autres services extérieurs');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 69,1,'PCG99-ABREGE','CHARGE','XXXXXX',   '63', '6', 'Impôts, taxes et versements assimiles');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 70,1,'PCG99-ABREGE','CHARGE','XXXXXX',  '641', '6', 'Rémunérations du personnel');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 71,1,'PCG99-ABREGE','CHARGE','XXXXXX',  '644', '6', 'Rémunération du travail de l''exploitant');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 72,1,'PCG99-ABREGE','CHARGE','SOCIAL',  '645', '6', 'Charges de sécurité sociale et de prévoyance');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 73,1,'PCG99-ABREGE','CHARGE','XXXXXX',  '646', '6', 'Cotisations sociales personnelles de l''exploitant');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 74,1,'PCG99-ABREGE','CHARGE','XXXXXX',   '65', '6', 'Autres charges de gestion courante');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 75,1,'PCG99-ABREGE','CHARGE','XXXXXX',   '66', '6', 'Charges financières');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 76,1,'PCG99-ABREGE','CHARGE','XXXXXX',   '67', '6', 'Charges exceptionnelles');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 77,1,'PCG99-ABREGE','CHARGE','XXXXXX',  '681', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 78,1,'PCG99-ABREGE','CHARGE','XXXXXX',  '686', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 79,1,'PCG99-ABREGE','CHARGE','XXXXXX',  '687', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 80,1,'PCG99-ABREGE','CHARGE','XXXXXX',  '691', '6', 'Participation des salariés aux résultats');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 81,1,'PCG99-ABREGE','CHARGE','XXXXXX',  '695', '6', 'Impôts sur les bénéfices');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 82,1,'PCG99-ABREGE','CHARGE','XXXXXX',  '697', '6', 'Imposition forfaitaire annuelle des sociétés');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 83,1,'PCG99-ABREGE','CHARGE','XXXXXX',  '699', '6', 'Produits');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 84,1,'PCG99-ABREGE','PROD',  'PRODUCT', '701', '7', 'Ventes de produits finis');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 85,1,'PCG99-ABREGE','PROD',  'SERVICE', '706', '7', 'Prestations de services');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 86,1,'PCG99-ABREGE','PROD',  'PRODUCT', '707', '7', 'Ventes de marchandises');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 87,1,'PCG99-ABREGE','PROD',  'PRODUCT', '708', '7', 'Produits des activités annexes');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 88,1,'PCG99-ABREGE','PROD',  'XXXXXX',  '709', '7', 'Rabais, remises et ristournes accordés par l''entreprise');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 89,1,'PCG99-ABREGE','PROD',  'XXXXXX',  '713', '7', 'Variation des stocks');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 90,1,'PCG99-ABREGE','PROD',  'XXXXXX',   '72', '7', 'Production immobilisée');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 91,1,'PCG99-ABREGE','PROD',  'XXXXXX',   '73', '7', 'Produits nets partiels sur opérations à long terme');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 92,1,'PCG99-ABREGE','PROD',  'XXXXXX',   '74', '7', 'Subventions d''exploitation');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 93,1,'PCG99-ABREGE','PROD',  'XXXXXX',   '75', '7', 'Autres produits de gestion courante');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 94,1,'PCG99-ABREGE','PROD',  'XXXXXX',  '753','75', 'Jetons de présence et rémunérations d''administrateurs, gérants,...');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 95,1,'PCG99-ABREGE','PROD',  'XXXXXX',  '754','75', 'Ristournes perçues des coopératives');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 96,1,'PCG99-ABREGE','PROD',  'XXXXXX',  '755','75', 'Quotes-parts de résultat sur opérations faites en commun');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 97,1,'PCG99-ABREGE','PROD',  'XXXXXX',   '76', '7', 'Produits financiers');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 98,1,'PCG99-ABREGE','PROD',  'XXXXXX',   '77', '7', 'Produits exceptionnels');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 99,1,'PCG99-ABREGE','PROD',  'XXXXXX',  '781', '7', 'Reprises sur amortissements et provisions');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (100,1,'PCG99-ABREGE','PROD',  'XXXXXX',  '786', '7', 'Reprises sur provisions pour risques');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (101,1,'PCG99-ABREGE','PROD',  'XXXXXX',  '787', '7', 'Reprises sur provisions');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (102,1,'PCG99-ABREGE','PROD',  'XXXXXX',   '79', '7', 'Transferts de charges');


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


drop table if exists llx_c_ape;

create table llx_c_ape
(
  rowid       integer      AUTO_INCREMENT UNIQUE,
  code_ape    varchar(5)   PRIMARY KEY,
  libelle     varchar(255),
  active      tinyint default 1  NOT NULL
)type=innodb;


delete from llx_c_ape;


drop table if exists llx_c_chargesociales;

create table llx_c_chargesociales
(
  id          integer PRIMARY KEY,
  libelle     varchar(80),
  deductible  smallint NOT NULL default 0,
  active      tinyint default 1  NOT NULL
)type=innodb;

delete from llx_c_chargesociales;
insert into llx_c_chargesociales (id,libelle,deductible) values ( 1, 'Allocations familiales',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 2, 'GSG Deductible',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 3, 'GSG/CRDS NON Deductible',0);


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
  active      tinyint default 1  NOT NULL,

  key (fk_region)
)type=innodb;

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
insert into llx_c_paiement (id,code,libelle,type) values (0, '',    '-', 3);
insert into llx_c_paiement (id,code,libelle,type) values (1, 'TIP', 'TIP', 1);
insert into llx_c_paiement (id,code,libelle,type) values (2, 'VIR', 'Virement', 2);
insert into llx_c_paiement (id,code,libelle,type) values (3, 'PRE', 'Prélèvement', 1);
insert into llx_c_paiement (id,code,libelle,type) values (4, 'LIQ', 'Liquide', 0);
insert into llx_c_paiement (id,code,libelle,type) values (5, 'VAD', 'Paiement en ligne', 0);
insert into llx_c_paiement (id,code,libelle,type) values (6, 'CB',  'Carte Bancaire', 1);
insert into llx_c_paiement (id,code,libelle,type) values (7, 'CHQ', 'Chèque', 2);

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
insert into llx_c_typent (id,code,libelle) values (  0, 'TE_UNKNOWN', 'Indifférent');
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

