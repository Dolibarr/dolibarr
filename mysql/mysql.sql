
create table llx_bank_url
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_bank         integer,
  url_id          integer,
  url             varchar(255),
  label           varchar(255)
);

create table llx_actioncomm
(
  id             integer AUTO_INCREMENT PRIMARY KEY,
  datea          datetime,           
  fk_action      integer,
  label          varchar(50),        
  fk_soc         integer,
  fk_contact     integer default 0,
  fk_user_action integer,            
  fk_user_author integer,
  priority       smallint,
  percent        smallint,
  note           text,
  propalrowid    integer,
  fk_facture     integer
);





create table llx_societe
(
  idp            integer AUTO_INCREMENT PRIMARY KEY,
  id             varchar(32),                         
  active         smallint       default 0,            
  parent         integer        default 0,            
  tms            timestamp,
  datec	         datetime,                            
  datea	         datetime,                            
  nom            varchar(60),                         
  address        varchar(255),                        
  cp             varchar(10),                         
  ville          varchar(50),                         
  fk_pays        integer        default 0,            
  tel            varchar(20),                         
  fax            varchar(20),                         
  url            varchar(255),                        
  fk_secteur     integer        default 0,            
  fk_effectif    integer        default 0,            
  fk_typent      integer        default 0,            
  siren	         varchar(9),                          
  description    text,                                
  fk_stcomm      smallint       default 0,            
  note           text,                                
  services       integer        default 0,            
  prefix_comm    varchar(5),                          
  client         smallint       default 0,            
  fournisseur    smallint       default 0,            

  UNIQUE INDEX(prefix_comm)
);


create table llx_socstatutlog
(
  id          integer AUTO_INCREMENT PRIMARY KEY,
  datel       datetime,
  fk_soc      integer,
  fk_statut   integer,
  author      varchar(30)
);

create table llx_socpeople
(
  idp          integer AUTO_INCREMENT PRIMARY KEY,
  datec        datetime,
  fk_soc       integer,
  name         varchar(50),
  firstname    varchar(50),
  address      varchar(255),
  poste        varchar(80),
  phone        varchar(30),
  phone_perso  varchar(30),
  phone_mobile varchar(30),
  fax          varchar(30),
  email        varchar(255),
  jabberid     varchar(255),
  fk_user      integer default 0,
  note         text
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

create table llx_user_rights
(
  fk_user       integer NOT NULL,
  fk_id         integer NOT NULL,
  UNIQUE(fk_user,fk_id)
);


create table llx_todocomm
(
  id             integer AUTO_INCREMENT PRIMARY KEY,
  datea          datetime,     
  label          varchar(50),  
  fk_user_action integer,      
  fk_user_author integer,      
  fk_soc         integer,      
  fk_contact     integer,      
                               
  note           text
);


create table llx_adherent
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  statut           smallint NOT NULL DEFAULT 0,
  public           smallint NOT NULL DEFAULT 0, 
  fk_adherent_type smallint,
  morphy           enum('mor','phy') NOT NULL, 
  datevalid        datetime,  
  datec            datetime,  
  prenom           varchar(50),
  nom              varchar(50),
  societe          varchar(50),
  adresse          text,
  cp               varchar(30),
  ville            varchar(50),
  pays             varchar(50),
  email            varchar(255),
  login            varchar(50) NOT NULL,      
  pass             varchar(50),      
  naiss            date,             
  photo		   varchar(255),     
  fk_user_author   integer NOT NULL,
  fk_user_mod      integer NOT NULL,
  fk_user_valid    integer NOT NULL,
  datefin          datetime NOT NULL, 
  note             text,

  UNIQUE INDEX(login)
);

create table llx_don
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  fk_statut       smallint NOT NULL DEFAULT 0,
  datec           datetime,         
  datedon         datetime,         
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
  public          smallint NOT NULL DEFAULT 1, 
  fk_don_projet   integer NOT NULL, 
  fk_user_author  integer NOT NULL,
  fk_user_valid   integer NOT NULL,
  note            text
);

create table llx_facture_fourn
(
  rowid      integer AUTO_INCREMENT PRIMARY KEY,
  facnumber  varchar(50) NOT NULL,
  fk_soc     integer NOT NULL,
  datec      datetime,    
  datef      date,        
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

  fk_user_author  integer,   
  fk_user_valid   integer,   

  note       text,

  UNIQUE INDEX (facnumber)
);

create table llx_notify
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  daten           datetime,           
  fk_action       integer NOT NULL,
  fk_contact      integer NOT NULL,
  objet_type      enum('ficheinter','facture','propale'),
  objet_id        integer NOT NULL
);

create table llx_livre_to_auteur
(
  fk_livre       integer NOT NULL,
  fk_auteur      integer NOT NULL
);

alter table  llx_livre_to_auteur add unique key (fk_livre, fk_auteur);

create table llx_contrat
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  enservice       smallint default 0,
  mise_en_service datetime,
  fin_validite    datetime,
  date_cloture    datetime,
  fk_soc          integer NOT NULL,
  fk_product      integer NOT NULL,
  fk_facture      integer NOT NULL default 0,
  fk_user_author  integer NOT NULL,
  fk_user_mise_en_service integer NOT NULL,
  fk_user_cloture integer NOT NULL
);


create table llx_fichinter
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc          integer NOT NULL,
  fk_projet       integer default 0,     
  ref             varchar(30) NOT NULL,  
  datec           datetime,              
  date_valid      datetime,              
  datei           date,                  
  fk_user_author  integer,               
  fk_user_valid   integer,               
  fk_statut       smallint  default 0,
  duree           real,
  note            text,

  UNIQUE INDEX (ref)
);

create table llx_action_def
(
  rowid           integer NOT NULL PRIMARY KEY,
  tms             timestamp,
  titre           varchar(255) NOT NULL,
  description     text,
  objet_type      enum('ficheinter','facture','propale')
);

create table llx_soc_events
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,  
  fk_soc        int          NOT NULL,            
  dateb	        datetime    NOT NULL,            
  datee	        datetime    NOT NULL,            
  title         varchar(100) NOT NULL,
  url           varchar(255),
  description   text
);

create table llx_const
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  name        varchar(255),
  value       text, 
  type        enum('yesno','texte','chaine'),
  visible     tinyint DEFAULT 1 NOT NULL,
  note        text,

  UNIQUE INDEX(name)
);

create table llx_compta
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  datec             datetime,
  datev             date,           
  amount            real NOT NULL default 0,
  label             varchar(255),
  fk_compta_account integer,
  fk_user_author    integer,
  fk_user_valid     integer,
  valid             tinyint default 0,
  note              text

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

create table llx_notify_def
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  datec           date,             
  fk_action       integer NOT NULL,
  fk_soc          integer NOT NULL,
  fk_contact      integer NOT NULL
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



create table llx_cond_reglement
(
  rowid           integer PRIMARY KEY,
  sortorder       smallint,
  actif           tinyint default 1,
  libelle         varchar(255),
  libelle_facture text,
  fdm             tinyint,    
  nbjour          smallint
);

create table llx_propal_model_pdf
(
  nom         varchar(50) PRIMARY KEY,
  libelle     varchar(255),
  description text
);

create table llx_entrepot
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  tms             timestamp,
  label           varchar(255),
  description     text,
  statut          tinyint default 1, 
  fk_user_author  integer
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
  fk_socpeople  integer default 0,
  note          text,

  UNIQUE INDEX(login)
);

create table llx_rights_def
(
  id            integer PRIMARY KEY,
  libelle       varchar(255),
  module        varchar(12),
  type          enum('r','w','m','d','a'),
  bydefault     tinyint default 0
);


create table llx_facture_tva_sum
(
  fk_facture    integer NOT NULL,
  amount        real  NOT NULL,
  tva_tx        real  NOT NULL,

  KEY(fk_facture)
);

create table llx_deplacement
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime NOT NULL,
  tms             timestamp,
  dated           datetime,
  fk_user	  integer NOT NULL,
  fk_user_author  integer,
  type            smallint NOT NULL,
  km              smallint,
  fk_soc          integer,
  note            text
);

create table llx_voyage
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,

  dateo           date,                    
  date_depart     datetime,                
  date_arrivee    datetime,                
  amount          real NOT NULL default 0, 
  reduction       real NOT NULL default 0, 
  depart          varchar(255),
  arrivee         varchar(255),
  fk_type         smallint,                
  fk_reduc        integer,
  distance        integer,                 
  dossier         varchar(50),             
  note            text
);



create table llx_product_price
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  fk_product      integer NOT NULL,
  date_price      datetime NOT NULL,
  price           double,
  tva_tx          double default 19.6,
  fk_user_author  integer,
  envente         tinyint default 1
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
  datev           date,           
  date_debut      date,           
  date_fin        date,
  amount          real NOT NULL default 0,
  label           varchar(255),
  numero          varchar(255),
  fk_type         smallint,       
  note            text
);

create table llx_c_paiement
(
  id         integer PRIMARY KEY,
  libelle    varchar(30),
  type       smallint	
);




create table llx_tva
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  datep           date,           
  datev           date,           
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


create table llx_c_actioncomm
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
  datev           date,           
  dateo           date,           
  amount          real NOT NULL default 0,
  label           varchar(255),
  fk_account      integer,
  fk_user_author  integer,
  fk_user_rappro  integer,
  fk_type         varchar(4),     
  num_releve      varchar(50),
  num_chq         int,
  rappro          tinyint default 0,
  note            text,


  author          varchar(40) 
);

create table llx_co_pr
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande integer,
  fk_propale  integer
);

create table llx_bank_categ
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  label           varchar(255)
);

create table llx_c_effectif
(
  id integer PRIMARY KEY,
  libelle varchar(30)
);


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
);

create table llx_c_pays
(
  id       integer PRIMARY KEY,
  libelle  varchar(25),
  code     char(2)      NOT NULL
);



create table llx_adherent_options
(
  optid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  adhid            integer NOT NULL, 

  UNIQUE INDEX(adhid)
);

create table llx_c_chargesociales
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


create table llx_c_typent
(
  id        integer PRIMARY KEY,
  libelle   varchar(30)
);

create table llx_co_fa
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande integer NOT NULL,
  fk_facture  integer NOT NULL,

  key(fk_commande),
  key(fk_facture)
);

create table llx_projet
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc           integer  NOT NULL,
  fk_statut        smallint NOT NULL,
  tms              timestamp,
  dateo            date,  
  ref              varchar(50),
  title            varchar(255),
  fk_user_resp     integer,   
  fk_user_creat    integer,   
  note             text,

  UNIQUE INDEX(ref)

);

create table llx_c_stcomm
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


create table llx_boxes_def
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  name        varchar(255) NOT NULL,
  file        varchar(255) NOT NULL,
  note        text
);

create table llx_facture_rec
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  titre              varchar(50) NOT NULL,
  fk_soc             integer NOT NULL,
  datec              datetime,  

  amount             real     default 0 NOT NULL,
  remise             real     default 0,
  remise_percent     real     default 0,
  tva                real     default 0,
  total              real     default 0,
  total_ttc          real     default 0,

  fk_user_author     integer,   
  fk_projet          integer,   
  fk_cond_reglement  integer,   

  note               text,

  INDEX fksoc (fk_soc)
);

create table llx_chargesociales
(
  rowid      integer AUTO_INCREMENT PRIMARY KEY,
  date_ech   datetime NOT NULL, 
  date_pai   datetime, 
  libelle    varchar(80),
  fk_type    integer,
  amount     real     default 0 NOT NULL,
  paye       smallint default 0 NOT NULL,
  periode    date
);




create table llx_c_propalst
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
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  facnumber          varchar(50) NOT NULL,
  fk_soc             integer NOT NULL,
  datec              datetime,  
  datef              date,      
  paye               smallint default 0 NOT NULL,
  amount             real     default 0 NOT NULL,
  remise             real     default 0,
  remise_percent     real     default 0,
  tva                real     default 0,
  total              real     default 0,
  total_ttc          real     default 0,
  fk_statut          smallint default 0 NOT NULL,
  author             varchar(50),
  fk_user            integer,   
  fk_user_author     integer,   
  fk_user_valid      integer,   
  fk_projet          integer,   
  fk_cond_reglement  integer,   
  date_lim_reglement date,      
  note               text,

  UNIQUE INDEX (facnumber),
  INDEX fksoc (fk_soc)
);

create table llx_facturedet
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture      integer NOT NULL,
  fk_product      integer NOT NULL default 0,
  description     text,
  tva_taux        real default 19.6, 
  qty		  real,              
  remise_percent  real default 0,    
  remise          real default 0,    
  subprice        real,              
  price           real               
);

create table llx_paiement
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture      integer,
  datec           datetime,
  datep           datetime,           
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
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  datec              datetime,
  tms                timestamp,
  ref                varchar(15) UNIQUE,
  label              varchar(255),
  description        text,
  price              double,
  tva_tx             double default 19.6,
  fk_user_author     integer,
  envente            tinyint default 1,
  nbvente            integer default 0,
  fk_product_type    integer default 0,
  duration           varchar(6),
  stock_propale      integer default 0,
  stock_commande     integer default 0,
  seuil_stock_alerte integer default 0

);




create table llx_propal
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc          integer,
  fk_soc_contact  integer,
  fk_projet       integer default 0,     
  ref             varchar(30) NOT NULL,  
  datec           datetime,              
  fin_validite    datetime,              
  date_valid      datetime,              
  date_cloture    datetime,              
  datep           date,                  
  fk_user_author  integer,               
  fk_user_valid   integer,               
  fk_user_cloture integer,               
  fk_statut       smallint  default 0,
  price           real      default 0,
  remise_percent  real      default 0,
  remise          real      default 0,
  tva             real      default 0,
  total           real      default 0,
  note            text,
  model_pdf       varchar(50),
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
  dated         datetime,         
  datef         datetime,         
  price         real,
  author	varchar(30),
  active        smallint DEFAULT 0 NOT NULL,
  note          varchar(255)
);

create table llx_propaldet
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_propal      integer,
  fk_product     integer,
  description    text,
  tva_tx         real default 19.6, 
  qty		 real,              
  remise_percent real default 0,    
  remise         real default 0,    
  subprice       real,              
  price          real               
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

create table llx_stock_mouvement
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  datem           datetime,
  fk_product      integer NOT NULL,
  fk_entrepot     integer NOT NULL,
  value           integer,
  type_mouvement  smallint,
  fk_user_author  integer,

  key(fk_product),
  key(fk_entrepot)
);


create table llx_product_stock
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  fk_product      integer NOT NULL,
  fk_entrepot     integer NOT NULL,
  reel            integer,  

  key(fk_product),
  key(fk_entrepot)
);


create table llx_product_fournisseur
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  tms             timestamp,
  fk_product      integer,
  fk_soc          integer,
  ref_fourn       varchar(30),
  fk_user_author  integer,

  key(fk_product),
  key(fk_soc)
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
  mail_valid       text 
);

create table llx_facturedet_rec
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture      integer NOT NULL,
  fk_product      integer,
  description     text,
  tva_taux       real default 19.6, 
  qty		 real,              
  remise_percent real default 0,    
  remise         real default 0,    
  subprice       real,              
  price          real               
);

create table llx_adherent_options_label
(
  name             varchar(64) PRIMARY KEY, 
  tms              timestamp,
  label            varchar(255) NOT NULL 
);

create table llx_expedition_methode
(
  rowid            integer PRIMARY KEY,
  tms              timestamp,
  code             varchar(30) NOT NULL,
  libelle          varchar(50) NOT NULL,
  description      text,
  statut           tinyint default 0
);

create table llx_birthday_alert
(
  rowid        integer AUTO_INCREMENT PRIMARY KEY,
  date_alert   date,
  fk_contact   integer, 
  fk_user      integer
);

create table llx_expedition
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  ref                   varchar(30) NOT NULL,
  fk_commande           integer,
  date_creation         datetime,              
  date_valid            datetime,              
  date_expedition       date,                  
  fk_user_author        integer,               
  fk_user_valid         integer,               
  fk_entrepot           integer,
  fk_expedition_methode integer,
  fk_statut             smallint  default 0,
  note                  text,
  model_pdf             varchar(50),

  UNIQUE INDEX (ref),
  key(fk_expedition_methode),
  key(fk_commande)
);

create table llx_expeditiondet
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_expedition     integer not null,
  fk_commande_ligne integer not null,
  qty               real,              

  key(fk_expedition),
  key(fk_commande_ligne)
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
  frais_de_port   tinyint default 1,

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
  date_send_request  datetime,   
  date_send_begin    datetime,   
  date_send_end      datetime,   
  nbsent             integer,    
  nberror            integer,    
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

create table llx_paiementfourn
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  tms               timestamp,
  datec             datetime,          
  fk_facture_fourn  integer,           
  datep             datetime,          
  amount            real default 0,    
  fk_user_author    integer,           
  fk_paiement       integer NOT NULL,  
  num_paiement      varchar(50),       
  note              text
);

insert into llx_cond_reglement values (1,1,1, "A réception","Réception de facture",0,0);
insert into llx_cond_reglement values (2,2,1, "30 jours","Réglement à 30 jours",0,30);
insert into llx_cond_reglement values (3,3,1, "30 jours fin de mois","Réglement à 30 jours fin de mois",1,30);
insert into llx_cond_reglement values (4,4,1, "60 jours","Réglement à 60 jours",0,60);
insert into llx_cond_reglement values (5,5,1, "60 jours fin de mois","Réglement à 60 jours fin de mois",1,60);


insert into llx_sqltables (name, loaded) values ('llx_album',0);

delete from llx_action_def;
insert into llx_action_def (rowid,titre,description,objet_type) VALUES (1,'Validation fiche intervention','Déclenché lors de la validation d\'une fiche d\'intervention','ficheinter');
insert into llx_action_def (rowid,titre,description,objet_type) VALUES (2,'Validation facture','Déclenché lors de la validation d\'une facture','facture');

delete from llx_boxes_def;

delete from llx_boxes;
insert into llx_const(name, value, type, note) values ('MAIN_MONNAIE','euros','chaine','Monnaie');
insert into llx_const(name, value, type, note) values ('MAIN_NOT_INSTALLED','1','chaine','Test d\'installation');

insert into llx_const(name, value, type, note) values ('MAIN_START_YEAR','2003','chaine','Année de départ');

insert into llx_const (name, value, type, note) VALUES ('MAIN_THEME','yellow','chaine','Thème par défaut');
insert into llx_const (name, value, type, note) VALUES ('MAIN_TITLE','Dolibarr','chaine','Titre des pages');


insert into llx_const(name, value, type) values ('DONS_FORM','fsfe.fr.php','chaine');



insert into llx_const(name, value, type, note) values ('MAIN_SEARCHFORM_SOCIETE','1','yesno','Affichage du formulaire de recherche des sociétés dans la barre de gauche');
insert into llx_const(name, value, type, note) values ('MAIN_SEARCHFORM_CONTACT','1','yesno','Affichage du formulaire de recherche des contacts dans la barre de gauche');

insert into llx_const(name, value, type, note) values ('COMPTA_ONLINE_PAYMENT_BPLC','1','yesno','Système de gestion de la banque populaire de Lorraine');

insert into llx_const(name, value, type, note) values ('COMPTA_BANK_FACTURES','1','yesno','Menu factures dans la partie bank');


insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_RESIL','Votre adhesion sur %SERVEUR% vient d\'etre resilie.\r\nNous esperons vous revoir tres bientot','texte','Mail de Resiliation');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_VALID','MAIN\r\nVotre adhesion vient d\'etre validee. \r\nVoici le rappel de vos coordonnees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante : \r\n%SERVEUR%public/adherents/','texte','Mail de validation');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_EDIT','Voici le rappel des coordonnees que vous avez modifiees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail d\'edition');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_NEW','Merci de votre inscription. Votre adhesion devrait etre rapidement validee.\r\nVoici le rappel des coordonnees que vous avez rentrees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail de nouvel inscription');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_COTIS','Bonjour %PRENOM%,\r\nMerci de votre inscription.\r\nCet email confirme que votre cotisation a ete recue et enregistree.\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail de validation de cotisation');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_VALID_SUBJECT','Votre adhésion a ete validée sur %SERVEUR%','chaine','sujet du mail de validation');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_RESIL_SUBJECT','Resiliation de votre adhesion sur %SERVEUR%','chaine','sujet du mail de resiliation');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_COTIS_SUBJECT','Recu de votre cotisation','chaine','sujet du mail de validation de cotisation');
insert into llx_const (name, value, type, note) VALUES ('SIZE_LISTE_LIMIT','20','chaine','Taille des listes');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_NEW_SUBJECT','Bienvenue sur %SERVEUR%','chaine','Sujet du mail de nouvelle adhesion');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_EDIT_SUBJECT','Votre fiche a ete editee sur %SERVEUR%','chaine','Sujet du mail d\'edition');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_USE_MAILMAN','0','yesno','Utilisation de Mailman');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAILMAN_UNSUB_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&user=%EMAIL%','chaine','Url de desinscription aux listes mailman');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAILMAN_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%','chaine','url pour les inscriptions mailman');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAILMAN_LISTS','test-test,test-test2','chaine','Listes auxquelles inscrire les nouveaux adherents');
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_MAILMAN_ADMINPW','','string','Mot de passe Admin des liste mailman',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_MAILMAN_SERVER','lists.domain.com','string','Serveur hebergeant les interfaces d\'Admin des listes mailman',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_MAILMAN_LISTS_COTISANT','','string','Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement',0);

insert into llx_const (name, value, type, note) VALUES ('MAIN_DEBUG','1','yesno','Debug ..');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_USE_GLASNOST','0','yesno','utilisation de glasnost ?');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_GLASNOST_SERVEUR','glasnost.j1b.org','chaine','serveur glasnost');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_GLASNOST_USER','user','chaine','Administrateur glasnost');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_GLASNOST_PASS','password','chaine','password de l\'administrateur');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_USE_GLASNOST_AUTO','0','yesno','inscription automatique a glasnost ?');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_USE_SPIP','0','yesno','Utilisation de SPIP ?');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_USE_SPIP_AUTO','0','yesno','Utilisation de SPIP automatiquement');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_SPIP_USER','user','chaine','user spip');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_SPIP_PASS','pass','chaine','Pass de connection');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_SPIP_SERVEUR','localhost','chaine','serveur spip');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_SPIP_DB','spip','chaine','db spip');
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_TEXT_NEW_ADH','','texte','Texte d\'entete du formaulaire d\'adhesion en ligne',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_CARD_HEADER_TEXT','%ANNEE%','string','Texte imprime sur le haut de la carte adherent',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_CARD_FOOTER_TEXT','Association FreeLUG http://www.freelug.org/','string','Texte imprime sur le bas de la carte adherent',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_CARD_TEXT','%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','texte','Texte imprime sur la carte adherent',0);
insert into llx_const(name, value, type) VALUES ('DB_NAME_OSC','catalog','chaine');
insert into llx_const(name, value, type) VALUES ('OSC_LANGUAGE_ID','1','chaine');
insert into llx_const(name, value, type) VALUES ('OSC_CATALOG_URL','http://osc.lafrere.lan/','chaine');
insert into llx_const (name, value, type, note) VALUES ('MAIN_MAIL_FROM','dolibarr-robot@domain.com','chaine','EMail emetteur pour les notifications automatiques Dolibarr');
insert into llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_FROM','adherents@domain.com','chaine','From des mails adherents');
insert into llx_const (name, value, type, note) VALUES ('MAIN_MENU_BARRETOP','default.php','chaine','Module de gestion de la barre de menu du haut');


delete from llx_c_chargesociales;
insert into llx_c_chargesociales (id,libelle,deductible) values ( 1, 'Allocations familiales',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 2, 'GSG Deductible',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 3, 'GSG/CRDS NON Deductible',0);


delete from llx_c_actioncomm;
insert into llx_c_actioncomm (id,libelle) values ( 0, '-');
insert into llx_c_actioncomm (id,libelle) values ( 1, 'Appel Téléphonique');
insert into llx_c_actioncomm (id,libelle) values ( 2, 'Envoi Fax');
insert into llx_c_actioncomm (id,libelle) values ( 3, 'Envoi propal par mail');
insert into llx_c_actioncomm (id,libelle) values ( 4, 'Envoi d\'un email'); 
insert into llx_c_actioncomm (id,libelle) values ( 5, 'Rendez-vous'); 
insert into llx_c_actioncomm (id,libelle) values ( 9, 'Envoi Facture');
insert into llx_c_actioncomm (id,libelle) values (10, 'Relance effectuée');
insert into llx_c_actioncomm (id,libelle) values (11, 'Clôture');

delete from llx_c_stcomm;
insert into llx_c_stcomm (id,libelle) values (-1, 'NE PAS CONTACTER');
insert into llx_c_stcomm (id,libelle) values ( 0, 'Jamais contacté');
insert into llx_c_stcomm (id,libelle) values ( 1, 'A contacter');
insert into llx_c_stcomm (id,libelle) values ( 2, 'Contact en cours');
insert into llx_c_stcomm (id,libelle) values ( 3, 'Contactée');

delete from llx_c_typent;
insert into llx_c_typent (id,libelle) values (  0, 'Indifférent');
insert into llx_c_typent (id,libelle) values (  1, 'Start-up');
insert into llx_c_typent (id,libelle) values (  2, 'Grand groupe');
insert into llx_c_typent (id,libelle) values (  3, 'PME/PMI');
insert into llx_c_typent (id,libelle) values (  4, 'Administration');
insert into llx_c_typent (id,libelle) values (100, 'Autres');

delete from llx_c_pays;
insert into llx_c_pays (id,libelle,code) values (0, 'France',          'FR');
insert into llx_c_pays (id,libelle,code) values (2, 'Belgique',        'BE');
insert into llx_c_pays (id,libelle,code) values (3, 'Italie',          'IT');
insert into llx_c_pays (id,libelle,code) values (4, 'Espagne',         'ES');
insert into llx_c_pays (id,libelle,code) values (5, 'Allemagne',       'DE');
insert into llx_c_pays (id,libelle,code) values (6, 'Suisse',          'CH');
insert into llx_c_pays (id,libelle,code) values (7, 'Royaume uni',     'GB');
insert into llx_c_pays (id,libelle,code) values (8, 'Irlande',         'IE');
insert into llx_c_pays (id,libelle,code) values (9, 'Chine',           'CN');
insert into llx_c_pays (id,libelle,code) values (10, 'Tunisie',        'TN');
insert into llx_c_pays (id,libelle,code) values (11, 'Etats Unis',     'US');
insert into llx_c_pays (id,libelle,code) values (12, 'Maroc',          'MA');
insert into llx_c_pays (id,libelle,code) values (13, 'Algérie',        'DZ');
insert into llx_c_pays (id,libelle,code) values (14, 'Canada',         'CA');
insert into llx_c_pays (id,libelle,code) values (15, 'Togo',           'TG');
insert into llx_c_pays (id,libelle,code) values (16, 'Gabon',          'GA');
insert into llx_c_pays (id,libelle,code) values (17, 'Pays Bas',       'NL');
insert into llx_c_pays (id,libelle,code) values (18, 'Hongrie',        'HU');
insert into llx_c_pays (id,libelle,code) values (19, 'Russie',         'RU');
insert into llx_c_pays (id,libelle,code) values (20, 'Suède',          'SE');
insert into llx_c_pays (id,libelle,code) values (21, 'Côte d\'Ivoire', 'CI');
insert into llx_c_pays (id,libelle,code) values (23, 'Sénégal',        'SN');
insert into llx_c_pays (id,libelle,code) values (24, 'Argentine',      'AR');
insert into llx_c_pays (id,libelle,code) values (25, 'Cameroun',       'CM');

delete from llx_c_effectif;
insert into llx_c_effectif (id,libelle) values (0,  'Non spécifié');
insert into llx_c_effectif (id,libelle) values (1,  '1 - 5');
insert into llx_c_effectif (id,libelle) values (2,  '6 - 10');
insert into llx_c_effectif (id,libelle) values (3,  '11 - 50');
insert into llx_c_effectif (id,libelle) values (4,  '51 - 100');
insert into llx_c_effectif (id,libelle) values (5,  '100 - 500');
insert into llx_c_effectif (id,libelle) values (6,  '> 500');

delete from llx_c_paiement;
insert into llx_c_paiement (id,libelle,type) values (0, '-', 3);
insert into llx_c_paiement (id,libelle,type) values (1, 'TIP', 1);
insert into llx_c_paiement (id,libelle,type) values (2, 'Virement', 2);
insert into llx_c_paiement (id,libelle,type) values (3, 'Prélèvement', 1);
insert into llx_c_paiement (id,libelle,type) values (4, 'Liquide', 0);
insert into llx_c_paiement (id,libelle,type) values (5, 'Paiement en ligne', 0);
insert into llx_c_paiement (id,libelle,type) values (6, 'CB', 1);
insert into llx_c_paiement (id,libelle,type) values (7, 'Chèque', 2);

delete from llx_c_propalst;
insert into llx_c_propalst (id,label) values (0, 'Brouillon');
insert into llx_c_propalst (id,label) values (1, 'Ouverte');
insert into llx_c_propalst (id,label) values (2, 'Signée');
insert into llx_c_propalst (id,label) values (3, 'Non Signée');
insert into llx_c_propalst (id,label) values (4, 'Facturée');


insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta,admin)
values ('Admin','Admin','ADM','admin','admin',1,1,1);
