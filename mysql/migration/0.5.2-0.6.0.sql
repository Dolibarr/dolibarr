
--
-- Mise à jour de la version 0.5.2 à 0.6.0
--

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

insert into llx_const(name, value, type, note, visible) values ('ADHERENT_BANK_USE','0','yesno','Utilisation de la gestion banquaire',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_BANK_USE_AUTO','0','yesno','Insertion automatique des cotisation dans le compte banquaire',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_BANK_ACCOUNT','','string','ID du Compte banquaire utilise',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_BANK_CATEGORIE','','string','ID de la categorie banquaire des cotisations',0);

alter table llx_cotisation ADD fk_bank int(11);
