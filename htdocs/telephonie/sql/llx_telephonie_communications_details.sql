
create table llx_telephonie_communications_details (
  fk_ligne         integer,
  ligne            varchar(255) NOT NULL,
  date             datetime,
  numero           varchar(255),
  dest             varchar(255),
  dureetext        varchar(255),
  fourn_cout       varchar(255),
  fourn_montant    real,
  duree            integer,        -- duree en secondes
  tarif_achat_temp real,  
  tarif_achat_fixe real,  
  tarif_vente_temp real,  
  tarif_vente_fixe real,  
  cout_achat       real,
  cout_vente       real,
  remise           real,
  fichier_cdr      varchar(255),
  fk_fournisseur   integer,

  key (fk_fournisseur)

)type=innodb;

