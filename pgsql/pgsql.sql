-- ============================================================================
-- Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- $Source$
--
-- ============================================================================


create table c_actioncomm
(
  id         SERIAL PRIMARY KEY,
  libelle    varchar(30),
  todo       int
);


create table c_chargesociales
(
  id          SERIAL PRIMARY KEY,
  libelle     varchar(80),
  deductible  smallint NOT NULL default 0
);




create table c_effectif
(
  id SERIAL PRIMARY KEY,
  libelle varchar(30)
);



create table c_paiement
(
  id         SERIAL PRIMARY KEY,
  libelle    varchar(30),
  type       smallint	
);




create table c_pays
(
  id       SERIAL PRIMARY KEY,
  libelle  varchar(25),
  code     char(2)      NOT NULL
);



create table c_propalst
(
  id              SERIAL PRIMARY KEY,
  label           varchar(30)
);


create table c_stcomm
(
  id       SERIAL PRIMARY KEY,
  libelle  varchar(30)
);


create table c_typent
(
  id        SERIAL PRIMARY KEY,
  libelle   varchar(30)
);


create table llx_adherent
(
  rowid            SERIAL PRIMARY KEY,
  tms              timestamp,
  statut           smallint NOT NULL DEFAULT 0,
  public           smallint NOT NULL DEFAULT 0, -- certain champ de la fiche sont ils public ou pas ?
  fk_adherent_type smallint,
  datevalid        timestamp,   -- date de validation
  datec            timestamp,  -- date de creation
  prenom           varchar(50),
  nom              varchar(50),
  societe          varchar(50),
  adresse          text,
  cp               varchar(30),
  ville            varchar(50),
  pays             varchar(50),
  email            varchar(255),
  login            varchar(50) NOT NULL,      -- login utilise pour editer sa fiche
  pass             varchar(50),      -- pass utilise pour editer sa fiche
  naiss            date,             -- date de naissance
  fk_user_author   integer NOT NULL,
  fk_user_valid    integer NOT NULL,
  datefin          timestamp NOT NULL, -- date de fin de validité de la cotisation
  note             text
);




create table llx_adherent_type
(
  rowid            SERIAL PRIMARY KEY,
  tms              timestamp,
  statut           smallint NOT NULL DEFAULT 0,
  libelle          varchar(50),
  cotisation	   CHAR(3) CHECK (cotisation IN ('yes','no')) NOT NULL DEFAULT 'yes',
  vote             CHAR(3) CHECK (vote IN ('yes','no')) NOT NULL DEFAULT 'yes',
  note             text,
  mail_valid       text -- mail envoye a la validation
);

create table llx_album
(
  rowid           serial PRIMARY KEY,
  osc_id          integer NOT NULL,
  tms             timestamp,
  ref		  varchar(12),
  title		  varchar(64),
  annee		  smallint, -- pourquoi smallint(64)
  description     text,
  collectif       smallint,
  fk_user_author  integer
);



create table llx_bank
(
  rowid           SERIAL PRIMARY KEY,
  datec           timestamp,
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
  rappro          int default 0,
  note            text,


  author          varchar(40) -- a supprimer apres migration
);


create table llx_bank_account
(
  rowid          SERIAL PRIMARY KEY,
  datec          timestamp,
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

create table llx_bank_categ
(
  rowid           SERIAL PRIMARY KEY,
  label           varchar(255)
);

create table llx_bank_class
(
  lineid   SERIAL,
  fk_categ integer not null
);

create table llx_bank_url
(
  rowid           SERIAL PRIMARY KEY,
  fk_bank         integer,
  url_id          integer,
  url             varchar(255),
  label           varchar(255)
);


create table llx_bookmark
(
  rowid       SERIAL PRIMARY KEY,
  fk_soc      integer,
  fk_user     integer,
  dateb       timestamp
);

create table llx_boxes
(
  rowid       SERIAL PRIMARY KEY,
  box_id      integer NOT NULL,
  position    smallint NOT NULL

);

create table llx_boxes_def
(
  rowid       SERIAL PRIMARY KEY,
  name        varchar(255) NOT NULL,
  file        varchar(255) NOT NULL,
  note        text
);
create table llx_chargesociales
(
  rowid      SERIAL PRIMARY KEY,
  date_ech   timestamp NOT NULL, -- date d'echeance
  date_pai   timestamp, -- date de paiements
  libelle    varchar(80),
  fk_type    integer,
  amount     real     default 0 NOT NULL,
  paye       smallint default 0 NOT NULL,
  periode    date
);





create table llx_compta
(
  rowid             SERIAL PRIMARY KEY,
  datec             timestamp,
  datev             date,           -- date de valeur
  amount            real NOT NULL default 0,
  label             varchar(255),
  fk_compta_account integer,
  fk_user_author    integer,
  fk_user_valid     integer,
  valid             int default 0,
  note              text

);


create table llx_compta_account
(
  rowid             SERIAL PRIMARY KEY,
  datec             timestamp,
  number            varchar(12),
  label             varchar(255),
  fk_user_author    integer,
  note              text

);

create table llx_concert
(
  rowid            SERIAL PRIMARY KEY,
  tms              timestamp,
  date_concert	   timestamp,
  description      text,
  collectif        int DEFAULT 0 NOT NULL,
  fk_groupart      integer,
  fk_lieu_concert  integer,
  fk_user_author   integer
);


create table llx_cond_reglement
(
  rowid           SERIAL PRIMARY KEY,
  sortorder       smallint,
  actif           int default 1,
  libelle         varchar(255),
  libelle_facture text,
  fdm             smallint,    -- reglement fin de mois
  nbjour          smallint
);

create table llx_const
(
  rowid       SERIAL PRIMARY KEY,
  name        varchar(255),
  value       text, -- max 65535 caracteres
  type	      CHAR(6) CHECK (type IN ('yesno','texte','chaine')),
  visible     int DEFAULT 1 NOT NULL,
  note        text
);

CREATE UNIQUE INDEX llx_const_idx ON llx_const (name);

create table llx_contrat
(
  rowid           SERIAL PRIMARY KEY,
  tms             timestamp,
  enservice       smallint default 0,
  mise_en_service timestamp,
  fin_validite    timestamp,
  date_cloture    timestamp,
  fk_soc          integer NOT NULL,
  fk_product      integer NOT NULL,
  fk_facture      integer NOT NULL default 0,
  fk_user_author  integer NOT NULL,
  fk_user_mise_en_service integer NOT NULL,
  fk_user_cloture integer NOT NULL
);



create table llx_cotisation
(
  rowid           SERIAL PRIMARY KEY,
  tms             timestamp,
  datec           timestamp,
  fk_adherent     integer,
  dateadh         timestamp,
  cotisation      real,
  note            text
);

create table llx_deplacement
(
  rowid           SERIAL PRIMARY KEY,
  datec           timestamp,
  tms             timestamp,
  dated           timestamp,
  fk_user	  integer NOT NULL,
  fk_user_author  integer,
  type            smallint NOT NULL,
  km              smallint,
  fk_soc          integer,
  note            text
);

create table llx_domain
(
  rowid           SERIAL PRIMARY KEY,
  datec           timestamp,
  label           varchar(255),
  note            text
);



create table llx_don
(
  rowid           SERIAL PRIMARY KEY,
  tms             timestamp,
  fk_statut       smallint NOT NULL DEFAULT 0,-- etat du don promesse/valid
  datec           timestamp,         -- date de création de l'enregistrement
  datedon         timestamp,         -- date du don/promesse
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


create table llx_don_projet
(
  rowid           SERIAL PRIMARY KEY,
  tms             timestamp,
  datec           timestamp,
  libelle         varchar(255),
  fk_user_author  integer NOT NULL,
  note            text
);

create table llx_editeur
(
  rowid           SERIAL PRIMARY KEY,
  oscid           integer NOT NULL,
  tms             timestamp,
  nom		  varchar(255),
  fk_user_author  integer
);


create table llx_entrepot
(
  rowid           SERIAL PRIMARY KEY,
  datec           timestamp,
  tms             timestamp,
  label           varchar(255),
  description     text,
  fk_user_author  integer

);


create table llx_fa_pr
(
  rowid      SERIAL PRIMARY KEY,
  fk_facture integer,
  fk_propal  integer
);

create table llx_facture
(
  rowid           SERIAL PRIMARY KEY,
  facnumber       varchar(50) NOT NULL,
  fk_soc          integer NOT NULL,
  datec           timestamp,  -- date de creation de la facture
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
  fk_projet          integer,   -- projet auquel est associé la facture
  fk_cond_reglement  integer,   -- condition de reglement
  date_lim_reglement date,      -- date limite de reglement
  note       text
);

create unique index llx_facture_facnumber on llx_facture(facnumber);

create index llx_facture_fksoc on llx_facture(fk_soc);

create table llx_facture_fourn
(
  rowid      SERIAL PRIMARY KEY,
  facnumber  varchar(50) NOT NULL,
  fk_soc     integer NOT NULL,
  datec      timestamp,  -- date de creation de la facture
  datef      date,      -- date de la facture
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
  fk_user_author  integer,   -- createur de la propale
  fk_user_valid   integer,   -- valideur de la propale
  note       text
);

create unique index llx_facture_fourn_facnumber on llx_facture_fourn(facnumber);

create table llx_facture_fourn_det
(
  rowid             SERIAL PRIMARY KEY,
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

create table llx_facture_rec
(
  rowid              SERIAL PRIMARY KEY,
  titre              varchar(50) NOT NULL,
  fk_soc             integer NOT NULL,
  datec              timestamp,  -- date de creation
  amount             real     default 0 NOT NULL,
  remise             real     default 0,
  remise_percent     real     default 0,
  tva                real     default 0,
  total              real     default 0,
  total_ttc          real     default 0,
  fk_user_author     integer,   -- createur
  fk_projet          integer,   -- projet auquel est associé la facture
  fk_cond_reglement  integer,   -- condition de reglement
  note               text
);

CREATE INDEX llx_facture_rec_fksoc ON llx_facture_rec (fk_soc);

create table llx_facture_tva_sum
(
  fk_facture    integer NOT NULL,
  amount        real  NOT NULL,
  tva_tx        real  NOT NULL
);

CREATE INDEX llx_facture_tva_sum_fk_facture ON llx_facture_tva_sum (fk_facture);

create table llx_facturedet
(
  rowid           SERIAL PRIMARY KEY,
  fk_facture      integer NOT NULL,
  fk_product      integer NOT NULL DEFAULT 0,
  description     text,
  tva_taux        real default 19.6, -- taux tva
  qty		  real,              -- quantité
  remise_percent  real default 0,    -- pourcentage de remise
  remise          real default 0,    -- montant de la remise
  subprice        real,              -- prix avant remise
  price           real               -- prix final
);

CREATE INDEX llx_facturedet_fk_facture ON llx_facturedet (fk_facture);

create table llx_facturedet_rec
(
  rowid           SERIAL PRIMARY KEY,
  fk_facture      integer NOT NULL,
  fk_product      integer,
  description     text,
  tva_taux       real default 19.6, -- taux tva
  qty		 real,              -- quantité
  remise_percent real default 0,    -- pourcentage de remise
  remise         real default 0,    -- montant de la remise
  subprice       real,              -- prix avant remise
  price          real               -- prix final
);

create table llx_fichinter
(
  rowid           SERIAL PRIMARY KEY,
  fk_soc          integer NOT NULL,
  fk_projet       integer default 0,     -- projet auquel est rattache la fiche
  ref             varchar(30) NOT NULL,  -- number
  datec           timestamp,              -- date de creation
  date_valid      timestamp,              -- date de validation
  datei           date,                  -- date de l'intervention
  fk_user_author  integer,   -- createur de la fiche
  fk_user_valid   integer,   -- valideur de la fiche
  fk_statut       smallint  default 0,
  duree           real,
  note            text
);

CREATE UNIQUE INDEX llx_fichinter_ref ON llx_fichinter(ref);

CREATE INDEX llx_fichinter_fk_soc ON llx_fichinter(fk_soc);

create table llx_groupart
(
  rowid           SERIAL PRIMARY KEY,
  osc_id          integer NOT NULL,
  tms             timestamp,
  nom		  varchar(64),
  groupart	  CHAR(8) CHECK (groupart IN ('artiste','groupe')) NOT NULL,
  fk_user_author  integer
);


create table llx_lieu_concert
(
  rowid            SERIAL PRIMARY KEY,
  tms              timestamp,
  nom              varchar(64) NOT NULL,
  description      text,
  ville            varchar(64) NOT NULL,
  fk_user_author   integer
);


create table llx_livre
(
  rowid           SERIAL PRIMARY KEY,
  oscid           integer NOT NULL,
  tms             timestamp,
  status          smallint,
  date_ajout      timestamp,
  ref		  varchar(12),
  title		  varchar(64),
  annee		  smallint,
  description     text,
  prix            decimal(15,4),
  fk_editeur      integer,
  fk_user_author  integer,
  frais_de_port   smallint default 1,

UNIQUE(ref)

);




create table llx_livre_to_auteur
(
  fk_livre       integer NOT NULL,
  fk_auteur      integer NOT NULL,

  UNIQUE(fk_livre, fk_auteur)
);


create table llx_newsletter
(
  rowid              SERIAL PRIMARY KEY,
  datec              timestamp,
  tms                timestamp,
  email_subject      varchar(32) NOT NULL,
  email_from_name    varchar(255) NOT NULL,
  email_from_email   varchar(255) NOT NULL,
  email_replyto      varchar(255) NOT NULL,
  email_body         text,
  target             smallint,
  sql_target         text,
  status             smallint NOT NULL DEFAULT 0,
  date_send_request  timestamp,   -- debut de l'envoi demandé
  date_send_begin    timestamp,   -- debut de l'envoi
  date_send_end      timestamp,   -- fin de l'envoi
  nbsent             integer,    -- nombre de mails envoyés
  nberror            integer,    -- nombre de mails envoyés
  fk_user_author     integer,
  fk_user_valid      integer,
  fk_user_modif      integer
);

create table llx_notify
(
  rowid           SERIAL PRIMARY KEY,
  tms             timestamp,
  daten           timestamp,           -- date de la notification
  fk_action       integer NOT NULL,
  fk_contact      integer NOT NULL,
  objet_type	  CHAR(10) CHECK (objet_type IN ('ficheinter','facture','propale')),
  objet_id        integer NOT NULL
);

create table llx_notify_def
(
  rowid           SERIAL PRIMARY KEY,
  tms             timestamp,
  datec           date,             -- date de creation
  fk_action       integer NOT NULL,
  fk_soc          integer NOT NULL,
  fk_contact      integer NOT NULL
);

create table llx_paiement
(
  rowid           SERIAL PRIMARY KEY,
  fk_facture      integer,
  datec           timestamp,
  datep           timestamp,           -- payment date
  amount          real default 0,
  author          varchar(50),
  fk_paiement     integer NOT NULL,
  num_paiement    varchar(50),
  note            text
);

create table llx_paiementfourn
(
  rowid             SERIAL PRIMARY KEY,
  tms               timestamp,
  datec             timestamp,          -- date de creation de l'enregistrement
  fk_facture_fourn  integer,           -- facture
  datep             timestamp,          -- date de paiement
  amount            real default 0,    -- montant
  fk_user_author    integer,           -- auteur
  fk_paiement       integer NOT NULL,  -- moyen de paiement
  num_paiement      varchar(50),       -- numéro de paiement (cheque)
  note              text
);

create table llx_pointmort
(
  month        timestamp,
  amount       real
);


create table llx_product
(
  rowid           SERIAL PRIMARY KEY,
  datec           timestamp,
  tms             timestamp,
  ref             varchar(15),
  label           varchar(255),
  description     text,
  price           double precision,
  tva_tx          double precision default 19.6,
  fk_user_author  integer,
  envente         smallint default 1,
  nbvente         integer default 0,
  fk_product_type integer default 0,
  duration        varchar(6)
);


create table llx_product_fournisseur
(
  rowid           SERIAL PRIMARY KEY,
  datec           timestamp,
  tms             timestamp,
  fk_product      integer,
  fk_soc          integer,
  ref_fourn       varchar(30),
  fk_user_author  integer
);

CREATE INDEX llx_product_fournisseur_fk_product ON llx_product_fournisseur (fk_product);

CREATE INDEX llx_product_fournisseur_fk_soc ON llx_product_fournisseur (fk_soc);

create table llx_product_price
(
  rowid           SERIAL PRIMARY KEY,
  tms             timestamp,
  fk_product      integer NOT NULL,
  date_price      timestamp NOT NULL,
  price           double precision,
  tva_tx          double precision default 19.6,
  fk_user_author  integer,
  envente         smallint default 1
);


create table llx_product_stock
(
  rowid           SERIAL PRIMARY KEY,
  tms             timestamp,
  fk_product      integer NOT NULL,
  fk_stock        integer NOT NULL,
  value           integer
);

CREATE INDEX llx_product_stock_fk_product ON llx_product_stock (fk_product);

CREATE INDEX llx_product_stock_fk_stock ON llx_product_stock (fk_stock);


create table llx_projet
(
  rowid            SERIAL PRIMARY KEY,
  fk_soc           integer  NOT NULL,
  fk_statut        smallint NOT NULL,
  tms              timestamp,
  dateo            date,  -- date d'ouverture du projet
  ref              varchar(50),
  title            varchar(255),
  fk_user_resp     integer,   -- responsable du projet
  fk_user_creat    integer,   -- createur du projet
  note             text
);

create unique index llx_projet_ref on llx_projet(ref);

create table llx_propal
(
  rowid           SERIAL PRIMARY KEY,
  fk_soc          integer,
  fk_soc_contact  integer,
  fk_projet       integer default 0, -- projet auquel est rattache la propale
  ref             varchar(30) NOT NULL,  -- propal number
  datec           timestamp,              -- date de creation
  date_valid      timestamp,              -- date de validation
  date_cloture    timestamp,              -- date de cloture
  datep           date,                  -- date de la propal
  fk_user_author  integer,   -- createur de la propale
  fk_user_valid   integer,   -- valideur de la propale
  fk_user_cloture integer,   -- cloture de la propale signee ou non signee
  fk_statut       smallint  default 0,
  price           real      default 0,
  remise_percent  real      default 0,
  remise          real      default 0,
  tva             real      default 0,
  total           real      default 0,
  note            text,
  model_pdf       varchar(50)
);

create unique index llx_propal_ref on llx_propal(ref);

create index llx_propal_fk_soc on llx_propal(fk_soc);

create table llx_propal_model_pdf
(
  nom         varchar(50) PRIMARY KEY,
  libelle     varchar(255),
  description text
);

create table llx_propaldet
(
  rowid         SERIAL PRIMARY KEY,
  fk_propal     integer,
  fk_product    integer,
  description    text,
  tva_tx         real default 19.6, -- taux tva
  qty		 real,              -- quantité
  remise_percent real default 0,    -- pourcentage de remise
  remise         real default 0,    -- montant de la remise
  subprice       real,              -- prix avant remise
  price          real               -- prix final
);

create table llx_rights_def
(
  id            integer PRIMARY KEY,
  libelle       varchar(255),
  module        varchar(12),
  type		CHAR CHECK (type IN ('r','w','m','d','a')),
  bydefault     smallint default 0
);


create table llx_service
(
  rowid           SERIAL PRIMARY KEY,
  datec           timestamp,
  tms             timestamp,
  ref             varchar(15),
  label           varchar(255),
  description     text,
  price           smallint,
  duration        varchar(32),
  debut_comm      timestamp,
  fin_comm        timestamp,
  fk_user_author  integer,
  fk_user_modif   integer
);

create unique index llx_service_ref on llx_service(ref);

create table llx_soc_events
(
  rowid         SERIAL PRIMARY KEY,
  fk_soc        int          NOT NULL,            --
  dateb	        timestamp    NOT NULL,            -- begin date
  datee	        timestamp    NOT NULL,            -- end date
  title         varchar(100) NOT NULL,
  url           varchar(255),
  description   text
);

create table llx_soc_recontact
(
  rowid      SERIAL PRIMARY KEY,
  fk_soc     integer,
  datere     timestamp,
  author     varchar(15)
);

create table llx_societe
(
  idp            SERIAL PRIMARY KEY,
  id             varchar(32),                         -- private id
  active         smallint       default 0,            --
  parent         integer        default 0,            --
  tms            timestamp,
  datec	         timestamp,                            -- creation date
  datea	         timestamp,                            -- activation date
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
  fournisseur    smallint       default 0             -- fournisseur oui/non
);

create unique index llx_societe_prefix_comm on llx_societe(prefix_comm);

create table llx_socpeople
(
  idp         SERIAL PRIMARY KEY,
  datec       timestamp,
  fk_soc      integer,
  name        varchar(50),
  firstname   varchar(50),
  address     varchar(255),
  poste       varchar(80),
  phone       varchar(30),
  fax         varchar(30),
  email       varchar(255),
  fk_user     integer default 0,
  note        text
);

CREATE INDEX llx_socpeople_fk_soc ON llx_socpeople(fk_soc);

create table llx_socstatutlog
(
  id          SERIAL PRIMARY KEY,
  datel       timestamp,
  fk_soc      integer,
  fk_statut   integer,
  author      varchar(30)
);

create table llx_sqltables
(
  rowid    SERIAL PRIMARY KEY,
  name     varchar(255),
  loaded   smallint
);


create table llx_stock_mouvement
(
  rowid           SERIAL PRIMARY KEY,
  tms             timestamp,
  datem           timestamp,
  fk_product      integer NOT NULL,
  fk_stock        integer NOT NULL,
  value           integer,
  type_mouvement  smallint,
  fk_user_author  integer
);

  CREATE INDEX llx_stock_mouvement_fk_product ON llx_stock_mouvement (fk_product);

  CREATE INDEX llx_stock_mouvement_fk_stock ON llx_stock_mouvement (fk_stock);

create table llx_todocomm
(
  id             SERIAL PRIMARY KEY,
  datea          timestamp,     -- date de l'action
  label          varchar(50),  -- libelle de l'action
  fk_user_action integer,      -- id de la personne qui doit effectuer l'action
  fk_user_author integer,      -- id auteur de l'action
  fk_soc         integer,      -- id de la societe auquel est rattachee l'action
  fk_contact     integer,      -- id du contact sur laquelle l'action
                               --    doit etre effectuee
  note           text
);



create table llx_transaction_bplc
(
  rowid             SERIAL PRIMARY KEY,
  tms               timestamp,
  ipclient          varchar(20),
  num_transaction   varchar(10),
  date_transaction  varchar(10),
  heure_transaction varchar(10),
  num_autorisation  varchar(10),
  cle_acceptation   varchar(5),
  code_retour       varchar(4),
  ref_commande      integer
);

create table llx_tva
(
  rowid           SERIAL PRIMARY KEY,
  tms             timestamp,
  datep           date,           -- date de paiement
  datev           date,           -- date de valeur
  amount          real NOT NULL default 0,
  label           varchar(255),
  note            text
);

create table llx_user
(
  rowid         SERIAL PRIMARY KEY,
  datec         timestamp,
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
  fk_societe    integer default 0,
  fk_socpeople  integer default 0,
  note          text
);

create unique index llx_user_login on llx_user(login);


create table llx_user_rights
(
  fk_user       integer NOT NULL,
  fk_id         integer NOT NULL,

  UNIQUE(fk_user,fk_id)
);

create table llx_ventes
(
  rowid         SERIAL PRIMARY KEY,
  fk_soc        integer NOT NULL,
  fk_product    integer NOT NULL,
  dated         timestamp,         -- date debut
  datef         timestamp,         -- date fin
  price         real,
  author	varchar(30),
  active        smallint DEFAULT 0 NOT NULL,
  note          varchar(255)
);

create table llx_voyage
(
  rowid           SERIAL PRIMARY KEY,
  datec           timestamp,
  dateo           date,                    -- date operation
  date_depart     timestamp,                -- date du voyage
  date_arrivee    timestamp,                -- date du voyage
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
  rowid           SERIAL PRIMARY KEY,
  datec           timestamp,
  datev           date,           -- date de valeur
  date_debut      date,           -- date operation
  date_fin        date,
  amount          real NOT NULL default 0,
  label           varchar(255),
  numero          varchar(255),
  fk_type         smallint,       -- Train, Avion, Bateaux
  note            text
);

create table llx_appro
(
  rowid           SERIAL PRIMARY KEY,
  datec           timestamp,
  tms             timestamp,
  fk_product      integer NOT NULL,
  quantity        smallint NOT NULL,
  price           real,
  fk_user_author  integer
);


create table llx_auteur
(
  rowid           SERIAL PRIMARY KEY,
  oscid           integer NOT NULL,
  tms             timestamp,
  nom		  varchar(255),
  fk_user_author  integer
);

create table llx_action_def
(
  rowid           SERIAL PRIMARY KEY,
  tms             timestamp,
  titre           varchar(255) NOT NULL,
  description     text,
  objet_type	  CHAR(10) CHECK (objet_type IN ('ficheinter','facture','propale'))
);

create table llx_adherent_options
(
  optid            SERIAL PRIMARY KEY,
  tms              timestamp,
  adhid            integer NOT NULL -- id de l'adherent auquel correspond ces attributs optionnel
);

CREATE UNIQUE INDEX llx_adherent_options_adhid ON llx_adherent_options (adhid);

create table llx_adherent_options_label
(
  name             SERIAL PRIMARY KEY, -- nom de l'attribut
  tms              timestamp,
  label            varchar(255) NOT NULL -- label correspondant a l'attribut
);

create table llx_album_to_groupart
(
  fk_album        integer NOT NULL,
  fk_groupart     integer NOT NULL,

  UNIQUE (fk_album, fk_groupart)
);

create table llx_actioncomm
(
  id             SERIAL PRIMARY KEY,
  datea          timestamp,           -- action date
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





insert into llx_cond_reglement values (1,1,1, 'A réception','Réception de facture',0,0);
insert into llx_cond_reglement values (2,2,1, '30 jours','Réglement à 30 jours',0,30);
insert into llx_cond_reglement values (3,3,1, '30 jours fin de mois','Réglement à 30 jours fin de mois',1,30);
insert into llx_cond_reglement values (4,4,1, '60 jours','Réglement à 60 jours',0,60);
insert into llx_cond_reglement values (5,5,1, '60 jours fin de mois','Réglement à 60 jours fin de mois',1,60);


insert into llx_sqltables (name, loaded) values ('llx_album',0);

delete from llx_action_def;
insert into llx_action_def (rowid,titre,description,objet_type) VALUES (1,'Validation fiche intervention','Déclenché lors de la validation d\'une fiche d\'intervention','ficheinter');
insert into llx_action_def (rowid,titre,description,objet_type) VALUES (2,'Validation facture','Déclenché lors de la validation d\'une facture','facture');

delete from llx_boxes_def;

delete from llx_boxes;
insert into llx_const(name, value, type, note) values ('MAIN_MONNAIE','euros','chaine','Monnaie');
insert into llx_const(name, value, type, note) values ('MAIN_NOT_INSTALLED','1','chaine','Test d\'installation');

insert into llx_const(name, value, type, note) values ('MAIN_START_YEAR','2004','chaine','Année de départ');

INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_THEME','yellow','chaine','Thème par défaut');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_TITLE','Dolibarr','chaine','Titre des pages');


insert into llx_const(name, value, type) values ('DONS_FORM','fsfe.fr.php','chaine');



insert into llx_const(name, value, type, note) values ('MAIN_SEARCHFORM_SOCIETE','1','yesno','Affichage du formulaire de recherche des sociétés dans la barre de gauche');
insert into llx_const(name, value, type, note) values ('MAIN_SEARCHFORM_CONTACT','1','yesno','Affichage du formulaire de recherche des contacts dans la barre de gauche');

insert into llx_const(name, value, type, note) values ('COMPTA_ONLINE_PAYMENT_BPLC','1','yesno','Système de gestion de la banque populaire de Lorraine');

insert into llx_const(name, value, type, note) values ('COMPTA_BANK_FACTURES','1','yesno','Menu factures dans la partie bank');


INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_RESIL','Votre adhesion sur %SERVEUR% vient d\'etre resilie.\r\nNous esperons vous revoir tres bientot','texte','Mail de Resiliation');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_VALID','MAIN\r\nVotre adhesion vient d\'etre validee. \r\nVoici le rappel de vos coordonnees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante : \r\n%SERVEUR%public/adherents/','texte','Mail de validation');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_EDIT','Voici le rappel des coordonnees que vous avez modifiees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail d\'edition');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_NEW','Merci de votre inscription. Votre adhesion devrait etre rapidement validee.\r\nVoici le rappel des coordonnees que vous avez rentrees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail de nouvel inscription');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_COTIS','Bonjour %PRENOM%,\r\nMerci de votre inscription.\r\nCet email confirme que votre cotisation a ete recue et enregistree.\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail de validation de cotisation');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_VALID_SUBJECT','Votre adhésion a ete validée sur %SERVEUR%','chaine','sujet du mail de validation');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_RESIL_SUBJECT','Resiliation de votre adhesion sur %SERVEUR%','chaine','sujet du mail de resiliation');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_COTIS_SUBJECT','Recu de votre cotisation','chaine','sujet du mail de validation de cotisation');
INSERT INTO llx_const (name, value, type, note) VALUES ('SIZE_LISTE_LIMIT','20','chaine','Taille des listes');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_NEW_SUBJECT','Bienvenue sur %SERVEUR%','chaine','Sujet du mail de nouvelle adhesion');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_EDIT_SUBJECT','Votre fiche a ete editee sur %SERVEUR%','chaine','Sujet du mail d\'edition');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_USE_MAILMAN','0','yesno','Utilisation de Mailman');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAILMAN_UNSUB_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&user=%EMAIL%','chaine','Url de desinscription aux listes mailman');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAILMAN_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%','chaine','url pour les inscriptions mailman');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAILMAN_LISTS','test-test,test-test2','chaine','Listes auxquelles inscrire les nouveaux adherents');
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_MAILMAN_ADMINPW','','chaine','Mot de passe Admin des liste mailman',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_MAILMAN_SERVER','lists.domain.com','chaine','Serveur hebergeant les interfaces d\'Admin des listes mailman',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_MAILMAN_LISTS_COTISANT','','chaine','Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement',0);

INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_DEBUG','1','yesno','Debug ..');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_USE_GLASNOST','0','yesno','utilisation de glasnost ?');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_GLASNOST_SERVEUR','glasnost.j1b.org','chaine','serveur glasnost');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_GLASNOST_USER','user','chaine','Administrateur glasnost');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_GLASNOST_PASS','password','chaine','password de l\'administrateur');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_USE_GLASNOST_AUTO','0','yesno','inscription automatique a glasnost ?');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_USE_SPIP','0','yesno','Utilisation de SPIP ?');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_USE_SPIP_AUTO','0','yesno','Utilisation de SPIP automatiquement');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_SPIP_USER','user','chaine','user spip');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_SPIP_PASS','pass','chaine','Pass de connection');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_SPIP_SERVEUR','localhost','chaine','serveur spip');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_SPIP_DB','spip','chaine','db spip');
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_TEXT_NEW_ADH','','texte','Texte d\'entete du formaulaire d\'adhesion en ligne',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_CARD_HEADER_TEXT','%ANNEE%','chaine','Texte imprime sur le haut de la carte adherent',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_CARD_FOOTER_TEXT','Association FreeLUG http://www.freelug.org/','chaine','Texte imprime sur le bas de la carte adherent',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_CARD_TEXT','%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','texte','Texte imprime sur la carte adherent',0);
INSERT INTO llx_const(name, value, type) VALUES ('DB_NAME_OSC','catalog','chaine');
INSERT INTO llx_const(name, value, type) VALUES ('OSC_LANGUAGE_ID','1','chaine');
INSERT INTO llx_const(name, value, type) VALUES ('OSC_CATALOG_URL','http://osc.lafrere.lan/','chaine');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_FROM','adherents@domain.com','chaine','From des mails');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_FROM','adherents@domain.com','chaine','From des mails adherents');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MENU_BARRETOP','default.php','chaine','Module commande');


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

insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta,admin)
values ('Admin','Admin','ADM','admin','admin',1,1,1);

