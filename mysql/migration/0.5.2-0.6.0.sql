
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
