
--
-- Mise à jour de la version 0.1.5 à 0.1.6
--

create table llx_notify_def
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  datec           date,           -- date de paiement
  fk_action       integer NOT NULL,
  fk_soc          integer NOT NULL,
  fk_contact      integer NOT NULL
);

create table llx_notify
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  daten           datetime,           -- date de la notification
  fk_action       integer NOT NULL,
  fk_contact      integer NOT NULL,
  objet_type      enum('ficheinter','facture','propale'),
  objet_id        integer NOT NULL
);


create table llx_action_def
(
  rowid           integer NOT NULL PRIMARY KEY,
  tms             timestamp,
  titre           varchar(255) NOT NULL,
  description     text,
  objet_type      enum('ficheinter','facture','propale')
);


insert into llx_action_def (rowid,titre,description,objet_type) VALUES (1,'Validation fiche intervention','Déclenché lors de la validation d\'une fiche d\'intervention','ficheinter');
insert into llx_action_def (rowid,titre,description,objet_type) VALUES (2,'Validation facture','Déclenché lors de la validation d\'une facture','facture');
