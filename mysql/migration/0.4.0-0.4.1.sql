
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

insert into llx_rights_def values (1,'Tous les droits','all','a',0);
insert into llx_rights_def values (10,'Tous les droits sur les factures','facture','a',0);
insert into llx_rights_def values (11,'Lire les factures','facture','r',1);
insert into llx_rights_def values (12,'Créer modifier les factures','facture','w',0);
insert into llx_rights_def values (13,'Modifier les factures d\'autrui','facture','m',0);
insert into llx_rights_def values (14,'Supprimer les factures','facture','d',0);

insert into llx_rights_def values (20,'Tous les droits sur les propositions commerciales','propale','a',0);
insert into llx_rights_def values (21,'Lire les propositions commerciales','propale','r',1);
insert into llx_rights_def values (22,'Créer modifier les propositions commerciales','propale','w',0);
insert into llx_rights_def values (23,'Modifier les propositions commerciales d\'autrui','propale','m',0);
insert into llx_rights_def values (24,'Supprimer les propositions commerciales','propale','d',0);

insert into llx_rights_def values (30,'Tous les droits sur les produits','produit','a',0);
insert into llx_rights_def values (31,'Lire les produits','produit','r',1);
insert into llx_rights_def values (32,'Créer modifier les produits','produit','w',0);
insert into llx_rights_def values (33,'Modifier les produits d\'autrui','produit','m',0);
insert into llx_rights_def values (34,'Supprimer les produits','produit','d',0);

insert into llx_rights_def values (40,'Tous les droits sur les projets','projet','a',0);
insert into llx_rights_def values (41,'Lire les projets','projet','r',1);
insert into llx_rights_def values (42,'Créer modifier les projets','projet','w',0);
insert into llx_rights_def values (43,'Modifier les projets d\'autrui','projet','m',0);
insert into llx_rights_def values (44,'Supprimer les projets','projet','d',0);

insert into llx_rights_def values (50,'Tous les droits sur les utilisateurs','utilisateur','a',0);
insert into llx_rights_def values (51,'Lire les utilisateurs','utilisateur','r',1);
insert into llx_rights_def values (52,'Créer modifier les utilisateurs','utilisateur','w',0);
insert into llx_rights_def values (53,'Modifier les utilisateurs d\'autrui','utilisateur','m',0);
insert into llx_rights_def values (54,'Supprimer les utilisateurs','utilisateur','d',0);

create table llx_user_rights
(
  fk_user       integer NOT NULL,
  fk_id         integer NOT NULL,
  UNIQUE(fk_user,fk_id)
);

