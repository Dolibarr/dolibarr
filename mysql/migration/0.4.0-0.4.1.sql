
--
-- Mise à jour de la version 0.4.0 à 0.4.1
--

alter table llx_user add fk_socpeople integer default 0;
alter table llx_socpeople add fk_user integer default 0;



create table llx_rights_def
(
  id            integer PRIMARY KEY,
  libelle       varchar(255),
  module        varchar(12),
  type          enum('r','w','m','d','a'),
  bydefault     tinyint default 0
);



insert into llx_rights_def values (1,'Tous les droits','all','a');
insert into llx_rights_def values (10,'Tous les droits sur les factures','facture','a');
insert into llx_rights_def values (11,'Lire les factures','facture','r');
insert into llx_rights_def values (12,'Créer modifier les factures','facture','w');
insert into llx_rights_def values (13,'Modifier les factures d\'autrui','facture','m');
insert into llx_rights_def values (14,'Supprimer les factures','facture','d');

insert into llx_rights_def values (20,'Tous les droits sur les propositions commerciales','propale','a');
insert into llx_rights_def values (21,'Lire les propositions commerciales','propale','r');
insert into llx_rights_def values (22,'Créer modifier les propositions commerciales','propale','w');
insert into llx_rights_def values (23,'Modifier les propositions commerciales d\'autrui','propale','m');
insert into llx_rights_def values (24,'Supprimer les propositions commerciales','propale','d');

create table llx_user_rights
(
  fk_user       integer NOT NULL,
  fk_id         integer NOT NULL,
  UNIQUE(fk_user,fk_id)
);

insert into llx_rights_def values (30,'Tous les droits sur les produits','produit','a');
insert into llx_rights_def values (31,'Lire les produits','produit','r');
insert into llx_rights_def values (32,'Créer modifier les produits','produit','w');
insert into llx_rights_def values (33,'Modifier les produits d\'autrui','produit','m');
insert into llx_rights_def values (34,'Supprimer les produits','produit','d');

insert into llx_rights_def values (40,'Tous les droits sur les projets','projet','a');
insert into llx_rights_def values (41,'Lire les projets','projet','r');
insert into llx_rights_def values (42,'Créer modifier les projets','projet','w');
insert into llx_rights_def values (43,'Modifier les projets d\'autrui','projet','m');
insert into llx_rights_def values (44,'Supprimer les projets','projet','d');
