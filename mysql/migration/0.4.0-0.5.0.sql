
--
-- Mise à jour de la version 0.4.0 à 0.5.0
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


create table llx_user_rights
(
  fk_user       integer NOT NULL,
  fk_id         integer NOT NULL,
  UNIQUE(fk_user,fk_id)
);

