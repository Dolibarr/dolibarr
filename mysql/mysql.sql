
create table llx_action_def
(
  rowid           integer NOT NULL PRIMARY KEY,
  tms             timestamp,
  titre           varchar(255) NOT NULL,
  description     text,
  objet_type      enum('ficheinter','facture','propale')
)type=innodb;

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
)type=innodb;





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
  photo            varchar(255),     
  fk_user_author   integer NOT NULL,
  fk_user_mod      integer NOT NULL,
  fk_user_valid    integer NOT NULL,
  datefin          datetime NOT NULL, 
  note             text,
 
  UNIQUE INDEX(login)
)type=innodb;

create table llx_adherent_options
(
  optid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  adhid            integer NOT NULL, 

  UNIQUE INDEX(adhid)
)type=innodb;

create table llx_adherent_options_label
(
  name             varchar(64) PRIMARY KEY, 
  tms              timestamp,
  label            varchar(255) NOT NULL 
)type=innodb;

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
)type=innodb;

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
)type=innodb;


create table llx_album_to_groupart
(
  fk_album        integer NOT NULL,
  fk_groupart     integer NOT NULL,

  unique key(fk_album, fk_groupart)
)type=innodb;


create table llx_appro
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  tms             timestamp,
  fk_product      integer NOT NULL, 
  quantity        smallint unsigned NOT NULL,
  price           real,
  fk_user_author  integer
)type=innodb;


create table llx_auteur
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  oscid           integer NOT NULL,
  tms             timestamp,
  nom		  varchar(255),
  fk_user_author  integer
)type=innodb;

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
)type=innodb;

create table llx_bank_account
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  datec          datetime,
  tms            timestamp,
  label          varchar(30),
  bank           varchar(60),
  code_banque    varchar(7),
  code_guichet   varchar(6),
  number         varchar(255),
  cle_rib        varchar(5),
  bic            varchar(10),
  iban_prefix    varchar(5),
  domiciliation  varchar(255),
  proprio        varchar(60),
  adresse_proprio varchar(255),
  courant        smallint default 0 not null,
  clos           smallint default 0 not null
)type=innodb;

create table llx_bank_categ
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  label           varchar(255)
)type=innodb;

create table llx_bank_class
(
  lineid   integer not null,
  fk_categ integer not null,

  INDEX(lineid)
)type=innodb;

create table llx_bank_url
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_bank         integer,
  url_id          integer,
  url             varchar(255),
  label           varchar(255)
)type=innodb;

create table llx_birthday_alert
(
  rowid        integer AUTO_INCREMENT PRIMARY KEY,
  fk_contact   integer, 
  fk_user      integer
)type=innodb;

create table llx_bookmark
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc      integer,
  fk_user     integer,
  dateb       datetime
)type=innodb;

create table llx_boxes
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  box_id      integer NOT NULL,
  position    smallint NOT NULL

)type=innodb;

create table llx_boxes_def
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  name        varchar(255) NOT NULL,
  file        varchar(255) NOT NULL,
  note        text
)type=innodb;

create table llx_c_actioncomm
(
  id         integer PRIMARY KEY,
  libelle    varchar(30),
  todo       tinyint
)type=innodb;

create table llx_c_ape
(
  rowid       integer AUTO_INCREMENT UNIQUE,
  code_ape    varchar(5) PRIMARY KEY,
  libelle     varchar(255),
  active      tinyint default 1
)type=innodb;


create table llx_c_chargesociales
(
  id          integer PRIMARY KEY,
  libelle     varchar(80),
  deductible  smallint NOT NULL default 0
)type=innodb;




create table llx_c_departements
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  code_departement char(3),
  fk_region   integer,
  cheflieu    varchar(7),
  tncc        integer,
  ncc         varchar(50),
  nom         varchar(50),
  active      tinyint default 1,

  key (fk_region)
)type=innodb;




create table llx_c_effectif
(
  id      integer PRIMARY KEY,
  libelle varchar(30)
)type=innodb;


create table llx_c_forme_juridique
(
  rowid      integer AUTO_INCREMENT PRIMARY KEY,
  fk_pays    integer default 1,
  code       integer UNIQUE,
  libelle    varchar(255),
  active     tinyint default 1

)type=innodb;


create table llx_c_paiement
(
  id         integer PRIMARY KEY,
  libelle    varchar(30),
  type       smallint	
)type=innodb;




create table llx_c_pays
(
  rowid    integer PRIMARY KEY,
  libelle  varchar(25)  NOT NULL,
  code     char(2)      NOT NULL,
  active      tinyint default 1  NOT NULL
)type=innodb;



create table llx_c_propalst
(
  id              smallint PRIMARY KEY,
  label           varchar(30)
)type=innodb;


create table llx_c_regions
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  code_region integer UNIQUE,
  fk_pays     integer default 1,
  cheflieu    varchar(7),
  tncc        integer,
  nom         varchar(50),
  active      tinyint default 1
)type=innodb;


create table llx_c_stcomm
(
  id       integer PRIMARY KEY,
  libelle  varchar(30)
)type=innodb;


create table llx_c_typent
(
  id        integer PRIMARY KEY,
  libelle   varchar(30)
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
)type=innodb;




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

)type=innodb;

create table llx_compta_account
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  datec             datetime,
  number            varchar(12),
  label             varchar(255),
  fk_user_author    integer,
  note              text

)type=innodb;

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
)type=innodb;


create table llx_cond_reglement
(
  rowid           integer PRIMARY KEY,
  sortorder       smallint,
  actif           tinyint default 1,
  libelle         varchar(255),
  libelle_facture text,
  fdm             tinyint,    
  nbjour          smallint
)type=innodb;

create table llx_const
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  name        varchar(255),
  value       text, 
  type        enum('yesno','texte','chaine'),
  visible     tinyint DEFAULT 1 NOT NULL,
  note        text,

  UNIQUE INDEX(name)
)type=innodb;

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
  fk_facturedet   integer NOT NULL default 0,
  fk_user_author  integer NOT NULL default 0,
  fk_user_mise_en_service integer,
  fk_user_cloture integer
)type=innodb;


create table llx_cotisation
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  datec           datetime,
  fk_adherent     integer,
  dateadh         datetime,
  cotisation      real,
  fk_bank         int(11) default NULL,
  note            text
)type=innodb;

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
)type=innodb;

create table llx_domain
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  label           varchar(255),
  note            text
)type=innodb;


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
)type=innodb;

create table llx_don_projet
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  datec           datetime,
  libelle         varchar(255),
  fk_user_author  integer NOT NULL,
  note            text
)type=innodb;

create table llx_editeur
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  oscid           integer NOT NULL,
  tms             timestamp,
  nom		  varchar(255),
  fk_user_author  integer
)type=innodb;


create table llx_entrepot
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  tms             timestamp,
  label           varchar(255) NOT NULL,
  description     text,
  statut          tinyint default 1, 
  fk_user_author  integer
)type=innodb;


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
)type=innodb;

create table llx_expedition_methode
(
  rowid            integer PRIMARY KEY,
  tms              timestamp,
  code             varchar(30) NOT NULL,
  libelle          varchar(50) NOT NULL,
  description      text,
  statut           tinyint default 0
)type=innodb;

create table llx_expeditiondet
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_expedition     integer not null,
  fk_commande_ligne integer not null,
  qty               real,              

  key(fk_expedition),
  key(fk_commande_ligne)
)type=innodb;

create table llx_fa_pr
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture integer,
  fk_propal  integer
)type=innodb;

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
)type=innodb;

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

  UNIQUE INDEX (facnumber, fk_soc)
)type=innodb;

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

)type=innodb;

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
)type=innodb;

create table llx_facture_tva_sum
(
  fk_facture    integer NOT NULL,
  amount        real  NOT NULL,
  tva_tx        real  NOT NULL,

  KEY(fk_facture)
)type=innodb;

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
  price           real,              
  date_start      datetime,          
  date_end        datetime           
)type=innodb;

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
)type=innodb;

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
)type=innodb;

create table llx_groupart
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  osc_id          integer NOT NULL,
  tms             timestamp,
  nom		  varchar(64),
  groupart        enum("artiste","groupe") NOT NULL,
  description     text NOT NULL,
  fk_user_author  integer
)type=innodb;


create table llx_lieu_concert
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  nom              varchar(64) NOT NULL,
  description      text,
  ville            varchar(64) NOT NULL,
  fk_user_author   integer
)type=innodb;


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
)type=innodb;


create table llx_livre_to_auteur
(
  fk_livre       integer NOT NULL,
  fk_auteur      integer NOT NULL,

  unique index (fk_livre, fk_auteur)
)type=innodb;

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
)type=innodb;


create table llx_notify
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  daten           datetime,           
  fk_action       integer NOT NULL,
  fk_contact      integer NOT NULL,
  objet_type      enum('ficheinter','facture','propale'),
  objet_id        integer NOT NULL
)type=innodb;

create table llx_notify_def
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  datec           date,             
  fk_action       integer NOT NULL,
  fk_soc          integer NOT NULL,
  fk_contact      integer NOT NULL
)type=innodb;

create table llx_paiement
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture      integer,
  datec           datetime,           
  tms             timestamp,
  datep           datetime,           
  amount          real default 0,
  author          varchar(50),
  fk_paiement     integer NOT NULL,
  num_paiement    varchar(50),
  note            text,
  fk_bank         integer NOT NULL,
  fk_user_creat   integer,            
  fk_user_modif   integer             

)type=innodb;

create table llx_paiement_facture
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_paiement     integer,
  fk_facture      integer,
  amount          real     default 0,
  
  key (fk_paiement),
  key (fk_facture)

)type=innodb;

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
  note              text,
  fk_bank           integer NOT NULL
)type=innodb;

create table llx_pointmort
(
  month        datetime,
  amount       real
)type=innodb;


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

)type=innodb;




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
)type=innodb;


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
)type=innodb;


create table llx_product_stock
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  fk_product      integer NOT NULL,
  fk_entrepot     integer NOT NULL,
  reel            integer,  

  key(fk_product),
  key(fk_entrepot)
)type=innodb;


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

)type=innodb;

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
)type=innodb;

create table llx_propal_model_pdf
(
  nom         varchar(50) PRIMARY KEY,
  libelle     varchar(255),
  description text
)type=innodb;

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
)type=innodb;

create table llx_rights_def
(
  id            integer PRIMARY KEY,
  libelle       varchar(255),
  module        varchar(12),
  type          enum('r','w','m','d','a'),
  bydefault     tinyint default 0
)type=innodb;


create table llx_soc_events
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,  
  fk_soc        int          NOT NULL,            
  dateb	        datetime    NOT NULL,            
  datee	        datetime    NOT NULL,            
  title         varchar(100) NOT NULL,
  url           varchar(255),
  description   text
)type=innodb;

create table llx_soc_recontact
(
  rowid      integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc     integer,
  datere     datetime,
  author     varchar(15)
)type=innodb;

create table llx_societe
(
  idp             integer AUTO_INCREMENT PRIMARY KEY,
  id              varchar(32),                         
  active          smallint       default 0,            
  parent          integer        default 0,            
  tms             timestamp,
  datec	          datetime,                            
  datea	          datetime,                            
  nom             varchar(60),                         
  address         varchar(255),                        
  cp              varchar(10),                         
  ville           varchar(50),                         
  fk_departement  integer        default 0,            
  fk_pays         integer        default 0,            
  tel             varchar(20),                         
  fax             varchar(20),                         
  url             varchar(255),                        
  fk_secteur         integer        default 0,         
  fk_effectif        integer        default 0,         
  fk_typent          integer        default 0,         
  fk_forme_juridique integer        default 0,         
  siren	          varchar(9),                          
  siret           varchar(14),                         
  ape             varchar(4),                          
  tva_intra       varchar(20),                         
  capital         real,                                
  description     text,                                
  fk_stcomm       smallint       default 0,            
  note            text,                                
  services        integer        default 0,            
  prefix_comm     varchar(5),                          
  client          int            default 0,            
  fournisseur     smallint       default 0,            
  rubrique        varchar(255),                        
  fk_user_creat   integer,                       
  fk_user_modif   integer,                       

  UNIQUE INDEX(prefix_comm)
)type=innodb;


create table llx_socpeople
(
  idp            integer AUTO_INCREMENT PRIMARY KEY,
  datec          datetime,
  tms            timestamp,
  fk_soc         integer,
  civilite       smallint,           
  name           varchar(50),
  firstname      varchar(50),
  address        varchar(255),
  birthday       date,
  poste          varchar(80),
  phone          varchar(30),
  phone_perso    varchar(30),
  phone_mobile   varchar(30),
  fax            varchar(30),
  email          varchar(255),
  jabberid       varchar(255),
  fk_user        integer default 0, 
  fk_user_modif  integer,
  note           text
)type=innodb;

create table llx_socstatutlog
(
  id          integer AUTO_INCREMENT PRIMARY KEY,
  datel       datetime,
  fk_soc      integer,
  fk_statut   integer,
  author      varchar(30)
)type=innodb;

create table llx_sqltables
(
  rowid    integer AUTO_INCREMENT PRIMARY KEY,
  name     varchar(255),
  loaded   tinyint(1)
)type=innodb;


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
)type=innodb;


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
)type=innodb;


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

)type=innodb;

create table llx_tva
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  datep           date,           
  datev           date,           
  amount          real NOT NULL default 0,
  label           varchar(255),
  note            text
)type=innodb;

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
)type=innodb;

create table llx_user_param
(
  fk_user       integer,
  page          varchar(255),
  param         varchar(64),
  value         varchar(255),
  UNIQUE (fk_user,page,param)
)type=innodb;

create table llx_user_rights
(
  fk_user       integer NOT NULL,
  fk_id         integer NOT NULL,
  UNIQUE(fk_user,fk_id)
)type=innodb;


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
)type=innodb;


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
)type=innodb;

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
)type=innodb;

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

create table llx_c_civilite
(
  rowid       integer PRIMARY KEY,
  fk_pays     integer default 1,
  civilite		varchar(50),
  active      tinyint default 1
)type=innodb;



insert into llx_cond_reglement values (1,1,1, "A réception","Réception de facture",0,0);
insert into llx_cond_reglement values (2,2,1, "30 jours","Réglement à 30 jours",0,30);
insert into llx_cond_reglement values (3,3,1, "30 jours fin de mois","Réglement à 30 jours fin de mois",1,30);
insert into llx_cond_reglement values (4,4,1, "60 jours","Réglement à 60 jours",0,60);
insert into llx_cond_reglement values (5,5,1, "60 jours fin de mois","Réglement à 60 jours fin de mois",1,60);


insert into llx_sqltables (name, loaded) values ('llx_album',0);

delete from llx_action_def;
insert into llx_action_def (rowid,titre,description,objet_type) values (1,'Validation fiche intervention','Déclenché lors de la validation d\'une fiche d\'intervention','ficheinter');
insert into llx_action_def (rowid,titre,description,objet_type) values (2,'Validation facture','Déclenché lors de la validation d\'une facture','facture');

delete from llx_boxes_def;

delete from llx_boxes;

insert into llx_const (name, value, type, note) values ('MAIN_MONNAIE','euros','chaine','Monnaie');
insert into llx_const (name, value, type, note) values ('MAIN_UPLOAD_DOC','1','chaine','Authorise l\'upload de document');
insert into llx_const (name, value, type, note) values ('MAIN_NOT_INSTALLED','1','chaine','Test d\'installation');
insert into llx_const (name, value, type, note) values ('MAIN_MAIL_FROM','dolibarr-robot@domain.com','chaine','EMail emetteur pour les notifications automatiques Dolibarr');

insert into llx_const (name, value, type, note) values ('MAIN_START_YEAR','2004','chaine','Année de départ');

insert into llx_const (name, value, type, note) values ('MAIN_TITLE','Dolibarr','chaine','Titre des pages');
insert into llx_const (name, value, type, note) values ('MAIN_DEBUG','1','yesno','Debug ..');

insert into llx_const (name, value, type, note, visible) values ('COMPTA_BANK_FACTURES','1','yesno','Menu factures dans la partie bank',0);
insert into llx_const (name, value, type, note, visible) values ('COMPTA_ONLINE_PAYMENT_BPLC','1','yesno','Système de gestion de la banque populaire de Lorraine',0);

insert into llx_const (name, value, type, note, visible) values ('MAIN_THEME','yellow','chaine','Thème par défaut',0);
insert into llx_const (name, value, type, note, visible) values ('SIZE_LISTE_LIMIT','20','chaine','Taille des listes',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_MENU_BARRETOP','default.php','chaine','Module de gestion de la barre de menu du haut',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_LANG_DEFAULT','fr','chaine','Langue par défaut pour les écrans Dolibarr',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_SEARCHFORM_CONTACT','1','yesno','Affichage formulaire de recherche des Contacts dans la barre de gauche',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_SEARCHFORM_SOCIETE','1','yesno','Affichage formulaire de recherche des Sociétés dans la barre de gauche',0);

insert into llx_const(name, value, type) values ('DONS_FORM','fsfe.fr.php','chaine');

insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_FROM','adherents@domain.com','chaine','From des mails adherents',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_RESIL','Votre adhesion sur %SERVEUR% vient d\'etre resilie.\r\nNous esperons vous revoir tres bientot','texte','Mail de Resiliation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_VALID','MAIN\r\nVotre adhesion vient d\'etre validee. \r\nVoici le rappel de vos coordonnees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante : \r\n%SERVEUR%public/adherents/','texte','Mail de validation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_EDIT','Voici le rappel des coordonnees que vous avez modifiees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail d\'edition',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_NEW','Merci de votre inscription. Votre adhesion devrait etre rapidement validee.\r\nVoici le rappel des coordonnees que vous avez rentrees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail de nouvel inscription',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_COTIS','Bonjour %PRENOM%,\r\nMerci de votre inscription.\r\nCet email confirme que votre cotisation a ete recue et enregistree.\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail de validation de cotisation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_VALID_SUBJECT','Votre adhésion a ete validée sur %SERVEUR%','chaine','sujet du mail de validation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_RESIL_SUBJECT','Resiliation de votre adhesion sur %SERVEUR%','chaine','sujet du mail de resiliation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_COTIS_SUBJECT','Recu de votre cotisation','chaine','sujet du mail de validation de cotisation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_NEW_SUBJECT','Bienvenue sur %SERVEUR%','chaine','Sujet du mail de nouvelle adhesion',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_EDIT_SUBJECT','Votre fiche a ete editee sur %SERVEUR%','chaine','Sujet du mail d\'edition',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_USE_MAILMAN','0','yesno','Utilisation de Mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_UNSUB_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&user=%EMAIL%','chaine','Url de desinscription aux listes mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%','chaine','url pour les inscriptions mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_LISTS','test-test,test-test2','chaine','Listes auxquelles inscrire les nouveaux adherents',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_ADMINPW','','string','Mot de passe Admin des liste mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_SERVER','lists.domain.com','string','Serveur hebergeant les interfaces d\'Admin des listes mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_LISTS_COTISANT','','string','Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_USE_GLASNOST','0','yesno','utilisation de glasnost ?',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_GLASNOST_SERVEUR','glasnost.j1b.org','chaine','serveur glasnost',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_GLASNOST_USER','user','chaine','Administrateur glasnost',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_GLASNOST_PASS','password','chaine','password de l\'administrateur',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_USE_GLASNOST_AUTO','0','yesno','inscription automatique a glasnost ?',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_USE_SPIP','0','yesno','Utilisation de SPIP ?',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_USE_SPIP_AUTO','0','yesno','Utilisation de SPIP automatiquement',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_SPIP_USER','user','chaine','user spip',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_SPIP_PASS','pass','chaine','Pass de connection',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_SPIP_SERVEUR','localhost','chaine','serveur spip',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_SPIP_DB','spip','chaine','db spip',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_TEXT_NEW_ADH','','texte','Texte d\'entete du formaulaire d\'adhesion en ligne',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_HEADER_TEXT','%ANNEE%','string','Texte imprime sur le haut de la carte adherent',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_FOOTER_TEXT','Association FreeLUG http://www.freelug.org/','string','Texte imprime sur le bas de la carte adherent',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_TEXT','%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','texte','Texte imprime sur la carte adherent',0);

insert into llx_const (name, value, type) values ('DB_NAME_OSC','catalog','chaine');
insert into llx_const (name, value, type) values ('OSC_LANGUAGE_ID','1','chaine');
insert into llx_const (name, value, type) values ('OSC_CATALOG_URL','http://osc.lafrere.lan/','chaine');

delete from llx_c_chargesociales;
insert into llx_c_chargesociales (id,libelle,deductible) values ( 1, 'Allocations familiales',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 2, 'GSG Deductible',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 3, 'GSG/CRDS NON Deductible',0);

delete from llx_c_actioncomm;
insert into llx_c_actioncomm (id,libelle) values ( 0, '-');
insert into llx_c_actioncomm (id,libelle) values ( 1, 'Appel Téléphonique');
insert into llx_c_actioncomm (id,libelle) values ( 2, 'Envoi Fax');
insert into llx_c_actioncomm (id,libelle) values ( 3, 'Envoi Proposition');
insert into llx_c_actioncomm (id,libelle) values ( 4, 'Envoi Email'); 
insert into llx_c_actioncomm (id,libelle) values ( 5, 'Prendre rendez-vous');
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

insert into llx_c_pays (rowid,libelle,code) values (1, 'France',          'FR');
insert into llx_c_pays (rowid,libelle,code) values (2, 'Belgique',        'BE');
insert into llx_c_pays (rowid,libelle,code) values (3, 'Italie',          'IT');
insert into llx_c_pays (rowid,libelle,code) values (4, 'Espagne',         'ES');
insert into llx_c_pays (rowid,libelle,code) values (5, 'Allemagne',       'DE');
insert into llx_c_pays (rowid,libelle,code) values (6, 'Suisse',          'CH');
insert into llx_c_pays (rowid,libelle,code) values (7, 'Royaume uni',     'GB');
insert into llx_c_pays (rowid,libelle,code) values (8, 'Irlande',         'IE');
insert into llx_c_pays (rowid,libelle,code) values (9, 'Chine',           'CN');
insert into llx_c_pays (rowid,libelle,code) values (10, 'Tunisie',        'TN');
insert into llx_c_pays (rowid,libelle,code) values (11, 'Etats Unis',     'US');
insert into llx_c_pays (rowid,libelle,code) values (12, 'Maroc',          'MA');
insert into llx_c_pays (rowid,libelle,code) values (13, 'Algérie',        'DZ');
insert into llx_c_pays (rowid,libelle,code) values (14, 'Canada',         'CA');
insert into llx_c_pays (rowid,libelle,code) values (15, 'Togo',           'TG');
insert into llx_c_pays (rowid,libelle,code) values (16, 'Gabon',          'GA');
insert into llx_c_pays (rowid,libelle,code) values (17, 'Pays Bas',       'NL');
insert into llx_c_pays (rowid,libelle,code) values (18, 'Hongrie',        'HU');
insert into llx_c_pays (rowid,libelle,code) values (19, 'Russie',         'RU');
insert into llx_c_pays (rowid,libelle,code) values (20, 'Suède',          'SE');
insert into llx_c_pays (rowid,libelle,code) values (21, 'Côte d\'Ivoire', 'CI');
insert into llx_c_pays (rowid,libelle,code) values (23, 'Sénégal',        'SN');
insert into llx_c_pays (rowid,libelle,code) values (24, 'Argentine',      'AR');
insert into llx_c_pays (rowid,libelle,code) values (25, 'Cameroun',       'CM');

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

insert into llx_c_departements (rowid, fk_region, code_departement,cheflieu,tncc,ncc,nom) values (0,0,0,'0',0,'-','-');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'01','01053',5,'AIN','Ain');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (22,'02','02408',5,'AISNE','Aisne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'03','03190',5,'ALLIER','Allier');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'04','04070',4,'ALPES-DE-HAUTE-PROVENCE','Alpes-de-Haute-Provence');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'05','05061',4,'HAUTES-ALPES','Hautes-Alpes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'06','06088',4,'ALPES-MARITIMES','Alpes-Maritimes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'07','07186',5,'ARDECHE','Ardèche');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'08','08105',4,'ARDENNES','Ardennes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) VALUES (73,'09','09122',5,'ARIEGE','Ariège');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) VALUES (21,'10','10387',5,'AUBE','Aube');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) VALUES (91,'11','11069',5,'AUDE','Aude');
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
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) VALUES (202,'10','',2,'LUXEMBOURG','Luxembourg');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) VALUES (201,'11','',2,'NAMUR','Namur');

insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'01','',1,'ANTWERP','Antwerp');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (203,'02','',3,'BRUXELLES-CAPITALE','Bruxelles-Capitale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'04','',1,'VLAMS-BRABANT','Vlams-Brabant');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'05','',1,'WEST-VLANDEREN','West-Vlanderen');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'06','',1,'OOST-VLANDEREN','Oost-Vlanderen');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'09','',1,'LIMBURG','Limburg');


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
insert into llx_c_paiement (id,libelle,type) values (6, 'Carte Bancaire', 1);
insert into llx_c_paiement (id,libelle,type) values (7, 'Chèque', 2);

delete from llx_c_propalst;
insert into llx_c_propalst (id,label) values (0, 'Brouillon');
insert into llx_c_propalst (id,label) values (1, 'Ouverte');
insert into llx_c_propalst (id,label) values (2, 'Signée');
insert into llx_c_propalst (id,label) values (3, 'Non Signée');
insert into llx_c_propalst (id,label) values (4, 'Facturée');


insert into llx_c_forme_juridique (fk_pays, code, libelle) values (0, 0,'Non renseignée');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,11,'Artisan Commerçant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,12,'Commerçant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,13,'Artisan');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,14,'Officier public ou ministériel');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,15,'Profession libérale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,16,'Exploitant agricole');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,17,'Agent commercial');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,18,'Associé Gérant de société');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,19,'(Autre) personne physique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,21,'Indivision');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,22,'Société créée de fait');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,23,'Société en participation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,27,'Paroisse hors zone concordataire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,29,'Autre groupement de droit privé non doté de la personnalité morale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,31,'Personne morale de droit étranger, immatriculée au RCS (registre du commerce et des sociétés)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,32,'Personne morale de droit étranger, non immatriculée au RCS');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,41,'Établissement public ou régie à caractère industriel ou commercial');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,51,'Société coopérative commerciale particulière');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,52,'Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,53,'Société en commandite');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,54,'Société à responsabilité limité (SARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,55,'Société anonyme à conseil d\'administration');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,56,'Société anonyme à directoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,57,'Société par actions simplifiée');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,61,'Caisse d\'épargne et de prévoyance');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,62,'Groupement d\'intérêt économique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,63,'Société coopérative agricole');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,64,'Société non commerciale d\'assurances');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,65,'Société civile');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,69,'Autres personnes de droit privé inscrites au registre du commerce et des sociétés');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,71,'Administration de l\'état');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,72,'Collectivité territoriale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,73,'Établissement public administratif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,74,'Autre personne morale de droit public administratif');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,81,'Organisme gérant un régime de protection social à adhésion obligatoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,82,'Organisme mutualiste');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,83,'Comité d\'entreprise');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,84,'Organisme professionnel');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,85,'Organisme de retraite à adhésion non obligatoire');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,91,'Syndicat de propriétaires');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,92,'Association loi 1901 ou assimilé');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,93,'Fondation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,99,'Autre personne morale de droit privé');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,100,'Indépendant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,101,'SC - Coopérative');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,102,'SCRL - Coopérative à responsabilité limitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,103,'SPRL - Société à responsabilité limitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,104,'SA - Société Anonyme');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,105,'Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,106,'Société en commandite');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,107,'Administration publique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,108,'Syndicat de propriétaires');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,109,'Fondations');

INSERT INTO llx_c_civilite (rowid, fk_pays, civilite, active) VALUES (1, 2, 'Madame', 1);
INSERT INTO llx_c_civilite (rowid, fk_pays, civilite, active) VALUES (2, 2, 'Mevrouw', 1);
INSERT INTO llx_c_civilite (rowid, fk_pays, civilite, active) VALUES (3, 2, 'Monsieur', 1);
INSERT INTO llx_c_civilite (rowid, fk_pays, civilite, active) VALUES (4, 2, 'Meneer', 1);
INSERT INTO llx_c_civilite (rowid, fk_pays, civilite, active) VALUES (5, 2, 'Mademoiselle', 1);
INSERT INTO llx_c_civilite (rowid, fk_pays, civilite, active) VALUES (6, 2, 'Juffrouw', 1);
INSERT INTO llx_c_civilite (rowid, fk_pays, civilite, active) VALUES (7, 2, 'Maître', 1);
