
alter table llx_propal add fin_validite datetime ;

alter table llx_entrepot add statut tinyint default 1;

alter table  llx_product add stock_propale integer default 0;
alter table  llx_product add stock_commande integer default 0;

alter table  llx_product add seuil_stock_alerte integer default 0;

alter table `llx_groupart` add `description` text after `groupart` ;


alter table llx_socpeople add phone_perso varchar(30) after phone ;
alter table llx_socpeople add phone_mobile varchar(30) after phone_perso ;

alter table llx_socpeople add jabberid varchar(255) after email ;

alter table llx_socpeople add birthday date after address ;


create table llx_birthday_alert
(
  rowid        integer AUTO_INCREMENT PRIMARY KEY,
  fk_contact   integer,
  fk_user      integer
);

alter table llx_facture_fourn drop index facnumber ;
alter table llx_facture_fourn add unique index (facnumber, fk_soc) ;


alter table llx_paiement add fk_bank integer NOT NULL after note ;
alter table llx_paiementfourn add fk_bank integer NOT NULL after note ;

create table llx_co_fa
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande integer NOT NULL,
  fk_facture  integer NOT NULL,

  key(fk_commande),
  key(fk_facture)
);

