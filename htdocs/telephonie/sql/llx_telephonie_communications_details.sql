
create table llx_telephonie_communications_details (
  ligne            varchar(255) NOT NULL,
  date             datetime,
  numero           varchar(255),
  dest             varchar(255),
  dureetext        varchar(255),
  fourn_cout       varchar(255),
  fourn_montant    real,
  duree            integer,        -- duree en secondes
  tarif_achat_temp real,           -- fournisseur tarif temporel
  tarif_achat_fixe real,           -- fournisseur tarif temporel
  tarif_vente_temp real,           -- fournisseur tarif temporel
  tarif_vente_fixe real,           -- fournisseur tarif temporel
  cout_achat       real,
  cout_vente       real,
  remise           real

)type=innodb;

