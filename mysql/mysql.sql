
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
  datea          datetime,         -- action date
  fk_action      integer,

  label          varchar(50),  -- libelle de l'action

  fk_soc         integer,
  fk_contact     integer default 0,

  fk_user_action integer,      -- id de la personne qui doit effectuer l'action
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
  login            varchar(50), -- login utilise pour editer sa fiche
  pass             varchar(50), -- pass utilise pour editer sa fiche
  naiss            date, -- date de naissance
  photo		   varchar(255), -- url vers la photo de l'adherent
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
  datec      datetime,  -- date de creation de la facture
  datef      date,      -- date de la facture
  libelle    varchar(255),
  paye       smallint default 0 NOT NULL,
  amount     real     default 0 NOT NULL,
  remise     real     default 0,
  tva        real     default 0,
  total      real     default 0,
  fk_statut  smallint default 0 NOT NULL,

  fk_user_author  integer,   -- createur de la propale
  fk_user_valid   integer,   -- valideur de la propale

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

create table llx_fichinter
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc          integer NOT NULL,
  fk_projet       integer default 0,     -- projet auquel est rattache la fiche
  ref             varchar(30) NOT NULL,  -- number

  datec           datetime,              -- date de creation 
  date_valid      datetime,              -- date de validation

  datei           date,                  -- date de l'intervention

  fk_user_author  integer,   -- createur de la fiche

  fk_user_valid   integer,   -- valideur de la fiche

  fk_statut       smallint  default 0,

  duree           real,

  note            text,

  UNIQUE INDEX (ref)
);

create table llx_const
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  name        varchar(255),
  value       varchar(255),
  type        enum('yesno'),
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
  webcal_login   varchar(25),
  module_comm   smallint default 1,
  module_compta smallint default 1,
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
  qty             smallint

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
  ref             varchar(15),
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
  fk_projet       integer default 0, -- projet auquel est rattache la propale
  ref             varchar(30) NOT NULL,  -- propal number

  datec           datetime,              -- date de creation 
  date_valid      datetime,              -- date de validation
  date_cloture    datetime,              -- date de cloture

  datep           date,                  -- date de la propal

  fk_user_author  integer,   -- createur de la propale

  fk_user_valid   integer,   -- valideur de la propale

  fk_user_cloture integer,   -- cloture de la propale signee ou non signee


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
);-- ===================================================================

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
  note             text
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

insert into llx_boxes_def (name, file) values ('Factures','box_factures.php');
insert into llx_boxes_def (name, file) values ('Factures impayées','box_factures_imp.php');
insert into llx_boxes_def (name, file) values ('Propales','box_propales.php');

insert into llx_const(name, value) values ('DONS_FORM','fsfe.fr.php');

insert into llx_const(name, value, type, note) values ('MAIN_SEARCHFORM_SOCIETE','1','yesno','Affichage du formulaire de recherche des sociétés dans la barre de gauche');
insert into llx_const(name, value, type, note) values ('MAIN_SEARCHFORM_CONTACT','1','yesno','Affichage du formulaire de recherche des contacts dans la barre de gauche');

insert into llx_const(name, value, type, note) values ('COMPTA_ONLINE_PAYMENT_BPLC','1','yesno','Système de gestion de la banque populaire de Lorraine');



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


