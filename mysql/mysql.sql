
create table llx_service
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  tms             timestamp,
  ref             varchar(15),
  label           varchar(255),
  description     text,
  price           smallint,
  duration        varchar(32),
  debut_comm      datetime,
  fin_comm        datetime,
  fk_user_author  integer,
  fk_user_modif   integer,

  UNIQUE INDEX(ref)
);


create table actioncomm
(
  id             integer AUTO_INCREMENT PRIMARY KEY,
  datea          datetime,           -- action date
  fk_action      integer,
  label          varchar(50),        -- libelle de l'action
  fk_soc         integer,
  fk_contact     integer default 0,
  fk_user_action integer,            -- id de la personne qui doit effectuer l'action
  fk_user_author integer,
  priority       smallint,
  percent        smallint,
  note           text,
  propalrowid    integer,
  fk_facture     integer
);





create table llx_transaction_bplc
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  tms               timestamp,
  ipclient          varchar(20),
  num_transaction   varchar(10), 
  date_transaction  varchar(10), 
  heure_transaction varchar(10), 
  num_autorisation  varchar(10),
  cle_acceptation   varchar(5),
  code_retour       integer,
  ref_commande      integer

);

create table llx_todocomm
(
  id             integer AUTO_INCREMENT PRIMARY KEY,
  datea          datetime,     -- date de l'action
  label          varchar(50),  -- libelle de l'action
  fk_user_action integer,      -- id de la personne qui doit effectuer l'action
  fk_user_author integer,      -- id auteur de l'action
  fk_soc         integer,      -- id de la societe auquel est rattachee l'action
  fk_contact     integer,      -- id du contact sur laquelle l'action 
                               --    doit etre effectuee
  note           text
);


create table llx_adherent
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  statut           smallint NOT NULL DEFAULT 0,
  public           smallint NOT NULL DEFAULT 0, -- certain champ de la fiche sont ils public ou pas ?
  fk_adherent_type smallint,
  morphy           enum('mor','phy') NOT NULL, -- personne morale / personne physique
  datevalid        datetime,  -- date de validation
  datec            datetime,  -- date de creation
  prenom           varchar(50),
  nom              varchar(50),
  societe          varchar(50),
  adresse          text,
  cp               varchar(30),
  ville            varchar(50),
  pays             varchar(50),
  email            varchar(255),
  login            varchar(50),      -- login utilise pour editer sa fiche
  pass             varchar(50),      -- pass utilise pour editer sa fiche
  naiss            date,             -- date de naissance
  photo		   varchar(255),     -- url vers la photo de l'adherent
  fk_user_author   integer NOT NULL,
  fk_user_mod      integer NOT NULL,
  fk_user_valid    integer NOT NULL,
  datefin          datetime NOT NULL, -- date de fin de validité de la cotisation
  note             text,

  UNIQUE INDEX(login)
);

create table llx_don
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  fk_statut       smallint NOT NULL DEFAULT 0,-- etat du don promesse/valid
  datec           datetime,         -- date de création de l'enregistrement
  datedon         datetime,         -- date du don/promesse
  amount          real default 0,
  fk_paiement     integer,
  prenom          varchar(50),
  nom             varchar(50),
  societe         varchar(50),
  adresse         text,
  cp              varchar(30),
  ville           varchar(50),
  pays            varchar(50),
  email           varchar(255),
  public          smallint NOT NULL DEFAULT 1, -- le don est-il public (0,1)
  fk_don_projet   integer NOT NULL, -- projet auquel est fait le don
  fk_user_author  integer NOT NULL,
  fk_user_valid   integer NOT NULL,
  note            text
);

create table llx_facture_fourn
(
  rowid      integer AUTO_INCREMENT PRIMARY KEY,
  facnumber  varchar(50) NOT NULL,
  fk_soc     integer NOT NULL,
  datec      datetime,    -- date de creation de la facture
  datef      date,        -- date de la facture
  libelle    varchar(255),
  paye       smallint default 0 NOT NULL,
  amount     real     default 0 NOT NULL,
  remise     real     default 0,
  tva        real     default 0,
  total      real     default 0,
  total_ht   real     default 0,
  total_tva  real     default 0,
  total_ttc  real     default 0,

  fk_statut  smallint default 0 NOT NULL,

  fk_user_author  integer,   -- createur de la facture
  fk_user_valid   integer,   -- valideur de la facture

  note       text,

  UNIQUE INDEX (facnumber)
);

create table socpeople
(
  idp         integer AUTO_INCREMENT PRIMARY KEY,
  datec       datetime,
  fk_soc      integer,
  name        varchar(50),
  firstname   varchar(50),
  address     varchar(255),
  poste       varchar(80),
  phone       varchar(30),
  fax         varchar(30),
  email       varchar(255),
  note        text
);

create table socstatutlog
(
  id          integer AUTO_INCREMENT PRIMARY KEY,
  datel       datetime,
  fk_soc      integer,
  fk_statut   integer,
  author      varchar(30)
);

create table llx_livre_to_auteur
(
  fk_livre       integer NOT NULL,
  fk_auteur      integer NOT NULL
);

alter table  llx_livre_to_auteur add unique key (fk_livre, fk_auteur);

create table llx_fichinter
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc          integer NOT NULL,
  fk_projet       integer default 0,     -- projet auquel est rattache la fiche
  ref             varchar(30) NOT NULL,  -- number
  datec           datetime,              -- date de creation 
  date_valid      datetime,              -- date de validation
  datei           date,                  -- date de l'intervention
  fk_user_author  integer,               -- createur de la fiche
  fk_user_valid   integer,               -- valideur de la fiche
  fk_statut       smallint  default 0,
  duree           real,
  note            text,

  UNIQUE INDEX (ref)
);

create table llx_const
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  name        varchar(255),
  value       text, -- max 65535 caracteres
  type        enum('yesno','texte','chaine'),
  visible     tinyint DEFAULT 1 NOT NULL,
  note        text,

  UNIQUE INDEX(name)
);

create table llx_compta
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  datec             datetime,
  datev             date,           -- date de valeur
  amount            real NOT NULL default 0,
  label             varchar(255),
  fk_compta_account integer,
  fk_user_author    integer,
  fk_user_valid     integer,
  valid             tinyint default 0,
  note              text

);

create table soc_events
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,  -- public id
  fk_soc        int          NOT NULL,            --
  dateb	        datetime    NOT NULL,            -- begin date
  datee	        datetime    NOT NULL,            -- end date
  title         varchar(100) NOT NULL,
  url           varchar(255),
  description   text
);

create table llx_compta_account
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  datec             datetime,
  number            varchar(12),
  label             varchar(255),
  fk_user_author    integer,
  note              text

);

create table llx_bank_account
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
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
  domiciliation  varchar(50),
  courant        smallint default 0 not null,
  clos           smallint default 0 not null
);



create table llx_user
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  datec         datetime,
  tms           timestamp,
  login         varchar(8),
  pass          varchar(32),
  name          varchar(50),
  firstname     varchar(50),
  code          varchar(4),
  email         varchar(255),
  admin         smallint default 0,
  webcal_login  varchar(25),
  module_comm   smallint default 1,
  module_compta smallint default 1,
  fk_societe    integer default 0,
  note          text,

  UNIQUE INDEX(login)
);

create table llx_voyage
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,

  dateo           date,                    -- date operation
  date_depart     datetime,                -- date du voyage
  date_arrivee    datetime,                -- date du voyage
  amount          real NOT NULL default 0, -- prix du billet
  reduction       real NOT NULL default 0, -- montant de la reduction obtenue
  depart          varchar(255),
  arrivee         varchar(255),
  fk_type         smallint,                -- Train, Avion, Bateaux
  fk_reduc        integer,
  distance        integer,                 -- distance en kilometre
  dossier         varchar(50),             -- numero de dossier
  note            text
);



create table llx_appro
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  tms             timestamp,
  fk_product      integer NOT NULL, 
  quantity        smallint unsigned NOT NULL,
  price           real,
  fk_user_author  integer
);


create table llx_voyage_reduc
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  datev           date,           -- date de valeur
  date_debut      date,           -- date operation
  date_fin        date,
  amount          real NOT NULL default 0,
  label           varchar(255),
  numero          varchar(255),
  fk_type         smallint,       -- Train, Avion, Bateaux
  note            text
);

create table c_paiement
(
  id         integer PRIMARY KEY,
  libelle    varchar(30),
  type       smallint	
);




create table llx_tva
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  datep           date,           -- date de paiement
  datev           date,           -- date de valeur
  amount          real NOT NULL default 0,
  label           varchar(255),
  note            text
);

create table llx_domain
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  label           varchar(255),
  note            text
);


create table societe
(
  idp            integer AUTO_INCREMENT PRIMARY KEY,
  id             varchar(32),                         -- private id
  active         smallint       default 0,            --
  parent         integer        default 0,            --
  tms            timestamp,
  datec	         datetime,                            -- creation date
  datea	         datetime,                            -- activation date
  nom            varchar(60),                         -- company name
  address        varchar(255),                        -- company adresse
  cp             varchar(10),                         -- zipcode
  ville          varchar(50),                         -- town
  fk_pays        integer        default 0,            --
  tel            varchar(20),                         -- phone number
  fax            varchar(20),                         -- fax number
  url            varchar(255),                        --
  fk_secteur     integer        default 0,            --
  fk_effectif    integer        default 0,            --
  fk_typent      integer        default 0,            --
  siren	         varchar(9),                          --
  description    text,                                --
  fk_stcomm      smallint       default 0,            -- commercial statut
  note           text,                                --
  services       integer        default 0,            --
  prefix_comm    varchar(5),                          -- prefix commercial
  client         smallint       default 0,            -- client oui/non
  fournisseur    smallint       default 0,            -- fournisseur oui/non

  UNIQUE INDEX(prefix_comm)
);

create table c_actioncomm
(
  id         integer PRIMARY KEY,
  libelle    varchar(30),
  todo       tinyint
);

create table llx_boxes
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  box_id      integer NOT NULL,
  position    smallint NOT NULL

);

create table llx_bank
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  datev           date,           -- date de valeur
  dateo           date,           -- date operation
  amount          real NOT NULL default 0,
  label           varchar(255),
  fk_account      integer,
  fk_user_author  integer,
  fk_user_rappro  integer,
  fk_type         varchar(4),     -- CB, Virement, cheque
  num_releve      varchar(50),
  num_chq         int,
  rappro          tinyint default 0,
  note            text,


  author          varchar(40) -- a supprimer apres migration
);

create table llx_bank_categ
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  label           varchar(255)
);

create table c_effectif
(
  id integer PRIMARY KEY,
  libelle varchar(30)
);


create table c_pays
(
  id       integer PRIMARY KEY,
  libelle  varchar(25),
  code     char(2)      NOT NULL
);



create table llx_adherent_options
(
  optid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  adhid            integer NOT NULL, -- id de l'adherent auquel correspond ces attributs optionnel 

  UNIQUE INDEX(adhid)
);

create table c_chargesociales
(
  id          integer PRIMARY KEY,
  libelle     varchar(80),
  deductible  smallint NOT NULL default 0
);




create table llx_bank_class
(
  lineid   integer not null,
  fk_categ integer not null,

  INDEX(lineid)
);

create table llx_album
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  osc_id          integer NOT NULL,
  tms             timestamp,
  ref		  varchar(12),
  title		  varchar(64),
  annee		  smallint(64),
  description     text,
  collectif       tinyint,
  fk_user_author  integer
);


create table c_typent
(
  id        integer PRIMARY KEY,
  libelle   varchar(30)
);

create table llx_projet
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc           integer  NOT NULL,
  fk_statut        smallint NOT NULL,
  tms              timestamp,
  dateo            date,  -- date d'ouverture du projet
  ref              varchar(50),
  title            varchar(255),
  fk_user_resp     integer,   -- responsable du projet
  fk_user_creat    integer,   -- createur du projet
  note             text,

  UNIQUE INDEX(ref)

);

create table c_stcomm
(
  id       integer PRIMARY KEY,
  libelle  varchar(30)
);


create table llx_groupart
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  osc_id          integer NOT NULL,
  tms             timestamp,
  nom		  varchar(64),
  groupart        enum("artiste","groupe") NOT NULL,
  fk_user_author  integer
);


create table llx_chargesociales
(
  rowid      integer AUTO_INCREMENT PRIMARY KEY,
  date_ech   datetime NOT NULL, -- date d'echeance
  date_pai   datetime, -- date de paiements
  libelle    varchar(80),
  fk_type    integer,
  amount     real     default 0 NOT NULL,
  paye       smallint default 0 NOT NULL,
  periode    date
);




create table c_propalst
(
  id              smallint PRIMARY KEY,
  label           varchar(30)
);


create table llx_bookmark
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc      integer,
  fk_user     integer,
  dateb       datetime
);

create table llx_fa_pr
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture integer,
  fk_propal  integer
);

create table llx_facture
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  facnumber       varchar(50) NOT NULL,
  fk_soc          integer NOT NULL,
  datec           datetime,  -- date de creation de la facture
  datef           date,      -- date de la facture
  paye            smallint default 0 NOT NULL,
  amount          real     default 0 NOT NULL,
  remise          real     default 0,
  tva             real     default 0,
  total           real     default 0,
  total_ttc       real     default 0,
  fk_statut       smallint default 0 NOT NULL,
  author          varchar(50),
  fk_user         integer,   -- createur de la facture
  fk_user_author  integer,   -- createur de la propale
  fk_user_valid   integer,   -- valideur de la propale
  note       text,

  UNIQUE INDEX (facnumber)
);

create table llx_facturedet
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture      integer NOT NULL,
  fk_product      integer,
  description     text,
  price           real default 0,
  qty             smallint,
  tva_taux        real default 19.6
);

create table llx_paiement
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture      integer,
  datec           datetime,
  datep           datetime,           -- payment date
  amount          real default 0,
  author          varchar(50),
  fk_paiement     integer NOT NULL,
  num_paiement    varchar(50),
  note            text
);

create table llx_pointmort
(
  month        datetime,
  amount       real
);


create table llx_product
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  tms             timestamp,
  ref             varchar(15) UNIQUE,
  label           varchar(255),
  description     text,
  price           smallint,
  fk_user_author  integer
);


create table llx_propal
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc          integer,
  fk_soc_contact  integer,
  fk_projet       integer default 0,     -- projet auquel est rattache la propale
  ref             varchar(30) NOT NULL,  -- propal number
  datec           datetime,              -- date de creation 
  date_valid      datetime,              -- date de validation
  date_cloture    datetime,              -- date de cloture
  datep           date,                  -- date de la propal
  fk_user_author  integer,               -- createur de la propale
  fk_user_valid   integer,               -- valideur de la propale
  fk_user_cloture integer,               -- cloture de la propale signee ou non signee
  fk_statut       smallint  default 0,
  price           real      default 0,
  remise          real      default 0,
  tva             real      default 0,
  total           real      default 0,
  note            text,

  UNIQUE INDEX (ref)
);

create table llx_soc_recontact
(
  rowid      integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc     integer,
  datere     datetime,
  author     varchar(15)
);

create table llx_ventes
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc        integer NOT NULL,
  fk_product    integer NOT NULL,
  dated         datetime,         -- date debut
  datef         datetime,         -- date fin
  price         real,
  author	varchar(30),
  active        smallint DEFAULT 0 NOT NULL,
  note          varchar(255)
);

create table llx_propaldet
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_propal     integer,
  fk_product    integer,
  qty		smallint,
  price         real
);

create table llx_don_projet
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  datec           datetime,
  libelle         varchar(255),
  fk_user_author  integer NOT NULL,
  note            text
);

create table llx_cotisation
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  datec           datetime,
  fk_adherent     integer,
  dateadh         datetime,
  cotisation      real,
  note            text
);

create table llx_adherent_type
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  statut           smallint NOT NULL DEFAULT 0,
  libelle          varchar(50),
  cotisation       enum('yes','no') NOT NULL DEFAULT 'yes',
  vote             enum('yes','no') NOT NULL DEFAULT 'yes',
  note             text,
  mail_valid       text -- mail envoye a la validation
);

create table llx_boxes_def
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  name        varchar(255) NOT NULL,
  file        varchar(255) NOT NULL,
  note        text
);

create table llx_adherent_options_label
(
  name             varchar(64) PRIMARY KEY, -- nom de l'attribut
  tms              timestamp,
  label            varchar(255) NOT NULL -- label correspondant a l'attribut
);

create table llx_sqltables
(
  rowid    integer AUTO_INCREMENT PRIMARY KEY,
  name     varchar(255),
  loaded   tinyint(1)
);


create table llx_concert
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  date_concert	   datetime NOT NULL,
  description      text,
  collectif        tinyint DEFAULT 0 NOT NULL,
  fk_groupart      integer,
  fk_lieu_concert  integer,
  fk_user_author   integer
);


create table llx_album_to_groupart
(
  fk_album        integer NOT NULL,
  fk_groupart     integer NOT NULL
);

alter table  llx_album_to_groupart add unique key (fk_album, fk_groupart);

create table llx_lieu_concert
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  nom              varchar(64) NOT NULL,
  description      text,
  ville            varchar(64) NOT NULL,
  fk_user_author   integer
);


create table llx_livre
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  oscid           integer NOT NULL,
  tms             timestamp,
  status          tinyint,
  date_ajout      datetime,
  ref		  varchar(12),
  title		  varchar(64),
  annee		  smallint(64),
  description     text,
  prix            decimal(15,4),
  fk_editeur      integer,
  fk_user_author  integer,

  UNIQUE(ref)
);


create table llx_auteur
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  oscid           integer NOT NULL,
  tms             timestamp,
  nom		  varchar(255),
  fk_user_author  integer
);

create table llx_editeur
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  oscid           integer NOT NULL,
  tms             timestamp,
  nom		  varchar(255),
  fk_user_author  integer
);


create table llx_newsletter
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  datec              datetime,
  tms                timestamp,
  email_subject      varchar(32) NOT NULL,
  email_from_name    varchar(255) NOT NULL,
  email_from_email   varchar(255) NOT NULL,
  email_replyto      varchar(255) NOT NULL,
  email_body         text,
  target             smallint,
  sql_target         text,
  status             smallint NOT NULL DEFAULT 0,
  date_send_request  datetime,   -- debut de l'envoi demandé
  date_send_begin    datetime,   -- debut de l'envoi
  date_send_end      datetime,   -- fin de l'envoi
  nbsent             integer,    -- nombre de mails envoyés
  nberror            integer,    -- nombre de mails envoyés
  fk_user_author     integer,
  fk_user_valid      integer,
  fk_user_modif      integer
);


create table llx_facture_fourn_det
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture_fourn  integer NOT NULL,
  fk_product        integer NULL,
  description       text,
  pu_ht             real default 0,
  qty               smallint default 1,
  total_ht          real default 0,
  tva_taux          real default 0,
  tva               real default 0,
  total_ttc         real default 0

);

insert into llx_sqltables (name, loaded) values ('llx_album',0);

insert into llx_boxes_def (name, file) values ('Factures','box_factures.php');
insert into llx_boxes_def (name, file) values ('Factures impayées','box_factures_imp.php');
insert into llx_boxes_def (name, file) values ('Propales','box_propales.php');

insert into llx_const(name, value, type) values ('DONS_FORM','fsfe.fr.php','chaine');

insert into llx_const(name, value, type) values ('FACTURE_ADDON','venus','chaine');

insert into llx_const(name, value, type, note) values ('MAIN_SEARCHFORM_SOCIETE','1','yesno','Affichage du formulaire de recherche des sociétés dans la barre de gauche');
insert into llx_const(name, value, type, note) values ('MAIN_SEARCHFORM_CONTACT','1','yesno','Affichage du formulaire de recherche des contacts dans la barre de gauche');

insert into llx_const(name, value, type, note) values ('COMPTA_ONLINE_PAYMENT_BPLC','1','yesno','Système de gestion de la banque populaire de Lorraine');

INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_THEME','dolibarr','chaine','theme principal');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_TITLE','Dolibarr','chaine','Titre des pages');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_RESIL','Votre adhesion sur %SERVEUR% vient d\'etre resilie.\r\nNous esperons vous revoir tres bientot','texte','Mail de Resiliation');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_VALID','MAIN\r\nVotre adhesion vient d\'etre validee. \r\nVoici le rappel de vos coordonnees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante : \r\n%SERVEUR%public/adherents/','texte','Mail de validation');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_EDIT','Voici le rappel des coordonnees que vous avez modifiees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail d\'edition');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_NEW','Merci de votre inscription. Votre adhesion devrait etre rapidement validee.\r\nVoici le rappel des coordonnees que vous avez rentrees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail de nouvel inscription');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_VALID_SUBJECT','Votre adhésion a ete validée sur %SERVEUR%','chaine','sujet du mail de validation');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_RESIL_SUBJECT','Resiliation de votre adhesion sur %SERVEUR%','chaine','sujet du mail de resiliation');
INSERT INTO llx_const (name, value, type, note) VALUES ('SIZE_LISTE_LIMIT','50','chaine','Taille des listes');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_NEW_SUBJECT','Bienvenue sur %SERVEUR%','chaine','Sujet du mail de nouvelle adhesion');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_EDIT_SUBJECT','Votre fiche a ete editee sur %SERVEUR%','chaine','Sujet du mail d\'edition');

INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_USE_MAILMAN','1','yesno','Utilisation de Mailman');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAILMAN_UNSUB_URL','http://lists.ipsyn.net/cgi-bin/mailman/handle_opts/%LISTE%/%EMAIL%?upw=%PASS%&unsub=Unsubscribe','chaine','Url de desinscription aux listes mailman');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAILMAN_URL','http://lists.ipsyn.net/cgi-bin/mailman/subscribe/%LISTE%/?email=%EMAIL%&pw=%PASS%&pw-conf=%PASS%','chaine','url pour les inscriptions mailman');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAILMAN_LISTS','test-test,test-test2','chaine','Listes auxquelles inscrire les nouveaux adherents');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_DEBUG','1','yesno','Debug ..');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_USE_GLASNOST','0','yesno','utilisation de glasnost ?');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_GLASNOST_SERVEUR','glasnost.j1b.org','chaine','serveur glasnost');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_GLASNOST_USER','user','chaine','Administrateur glasnost');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_GLASNOST_PASS','password','chaine','password de l\'administrateur');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_USE_GLASNOST_AUTO','1','yesno','inscription automatique a glasnost ?');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_USE_SPIP','1','yesno','Utilisation de SPIP ?');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_USE_SPIP_AUTO','1','yesno','Utilisation de SPIP automatiquement');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_SPIP_USER','user','chaine','user spip');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_SPIP_PASS','pass','chaine','Pass de connection');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_SPIP_SERVEUR','localhost','chaine','serveur spip');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_SPIP_DB','spip','chaine','db spip');

INSERT INTO llx_const(name, value, type) VALUES ('DB_NAME_OSC','catalog','chaine');
INSERT INTO llx_const(name, value, type) VALUES ('OSC_LANGUAGE_ID','1','chaine');
INSERT INTO llx_const(name, value, type) VALUES ('OSC_CATALOG_URL','http://osc.lafrere.lan/','chaine');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_FROM','adherents@domain.com','chaine','From des mails');

INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MENU_BARRETOP','default.php','chaine','Module commande');


INSERT INTO llx_const (name, value, type, visible) VALUES ('MAIN_MODULE_COMMANDE','0','yesno',0);
INSERT INTO llx_const (name, value, type, visible) VALUES ('MAIN_MODULE_DON','0','yesno',0);
INSERT INTO llx_const (name, value, type, visible) VALUES ('MAIN_MODULE_ADHERENT','0','yesno',0);

INSERT INTO llx_const (name, value, type, visible) VALUES ('BOUTIQUE_LIVRE','0','yesno',0);
INSERT INTO llx_const (name, value, type, visible) VALUES ('BOUTIQUE_ALBUM','0','yesno',0);

delete from c_chargesociales;
insert into c_chargesociales (id,libelle,deductible) values ( 1, 'Allocations familiales',1);
insert into c_chargesociales (id,libelle,deductible) values ( 2, 'GSG Deductible',1);
insert into c_chargesociales (id,libelle,deductible) values ( 3, 'GSG/CRDS NON Deductible',0);


delete from c_actioncomm;
insert into c_actioncomm (id,libelle) values ( 0, '-');
insert into c_actioncomm (id,libelle) values ( 1, 'Appel Téléphonique');
insert into c_actioncomm (id,libelle) values ( 2, 'Envoi Fax');
insert into c_actioncomm (id,libelle) values ( 3, 'Envoi propal par mail');
insert into c_actioncomm (id,libelle) values ( 4, 'Envoi d\'un email'); 
insert into c_actioncomm (id,libelle) values ( 5, 'Rendez-vous'); 
insert into c_actioncomm (id,libelle) values ( 9, 'Envoi Facture');
insert into c_actioncomm (id,libelle) values (10, 'Relance effectuée');
insert into c_actioncomm (id,libelle) values (11, 'Clôture');

delete from c_stcomm;
insert into c_stcomm (id,libelle) values (-1, 'NE PAS CONTACTER');
insert into c_stcomm (id,libelle) values ( 0, 'Jamais contacté');
insert into c_stcomm (id,libelle) values ( 1, 'A contacter');
insert into c_stcomm (id,libelle) values ( 2, 'Contact en cours');
insert into c_stcomm (id,libelle) values ( 3, 'Contactée');

delete from c_typent;
insert into c_typent (id,libelle) values (  0, 'Indifférent');
insert into c_typent (id,libelle) values (  1, 'Start-up');
insert into c_typent (id,libelle) values (  2, 'Grand groupe');
insert into c_typent (id,libelle) values (  3, 'PME/PMI');
insert into c_typent (id,libelle) values (  4, 'Administration');
insert into c_typent (id,libelle) values (100, 'Autres');

delete from c_pays;
insert into c_pays (id,libelle,code) values (0, 'France',          'FR');
insert into c_pays (id,libelle,code) values (2, 'Belgique',        'BE');
insert into c_pays (id,libelle,code) values (3, 'Italie',          'IT');
insert into c_pays (id,libelle,code) values (4, 'Espagne',         'ES');
insert into c_pays (id,libelle,code) values (5, 'Allemagne',       'DE');
insert into c_pays (id,libelle,code) values (6, 'Suisse',          'CH');
insert into c_pays (id,libelle,code) values (7, 'Royaume uni',     'GB');
insert into c_pays (id,libelle,code) values (8, 'Irlande',         'IE');
insert into c_pays (id,libelle,code) values (9, 'Chine',           'CN');
insert into c_pays (id,libelle,code) values (10, 'Tunisie',        'TN');
insert into c_pays (id,libelle,code) values (11, 'Etats Unis',     'US');
insert into c_pays (id,libelle,code) values (12, 'Maroc',          'MA');
insert into c_pays (id,libelle,code) values (13, 'Algérie',        'DZ');
insert into c_pays (id,libelle,code) values (14, 'Canada',         'CA');
insert into c_pays (id,libelle,code) values (15, 'Togo',           'TG');
insert into c_pays (id,libelle,code) values (16, 'Gabon',          'GA');
insert into c_pays (id,libelle,code) values (17, 'Pays Bas',       'NL');
insert into c_pays (id,libelle,code) values (18, 'Hongrie',        'HU');
insert into c_pays (id,libelle,code) values (19, 'Russie',         'RU');
insert into c_pays (id,libelle,code) values (20, 'Suède',          'SE');
insert into c_pays (id,libelle,code) values (21, 'Côte d\'Ivoire', 'CI');
insert into c_pays (id,libelle,code) values (23, 'Sénégal',        'SN');
insert into c_pays (id,libelle,code) values (24, 'Argentine',      'AR');
insert into c_pays (id,libelle,code) values (25, 'Cameroun',       'CM');

delete from c_effectif;
insert into c_effectif (id,libelle) values (0,  'Non spécifié');
insert into c_effectif (id,libelle) values (1,  '1 - 5');
insert into c_effectif (id,libelle) values (2,  '6 - 10');
insert into c_effectif (id,libelle) values (3,  '11 - 50');
insert into c_effectif (id,libelle) values (4,  '51 - 100');
insert into c_effectif (id,libelle) values (5,  '100 - 500');
insert into c_effectif (id,libelle) values (6,  '> 500');

delete from c_paiement;
insert into c_paiement (id,libelle,type) values (0, '-', 3);
insert into c_paiement (id,libelle,type) values (1, 'TIP', 1);
insert into c_paiement (id,libelle,type) values (2, 'Virement', 2);
insert into c_paiement (id,libelle,type) values (3, 'Prélèvement', 1);
insert into c_paiement (id,libelle,type) values (4, 'Liquide', 0);
insert into c_paiement (id,libelle,type) values (5, 'Paiement en ligne', 0);
insert into c_paiement (id,libelle,type) values (6, 'CB', 1);
insert into c_paiement (id,libelle,type) values (7, 'Chèque', 2);

delete from c_propalst;
insert into c_propalst (id,label) values (0, 'Brouillon');
insert into c_propalst (id,label) values (1, 'Ouverte');
insert into c_propalst (id,label) values (2, 'Signée');
insert into c_propalst (id,label) values (3, 'Non Signée');
insert into c_propalst (id,label) values (4, 'Facturée');


