-- ===================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- $Id$
-- $Source$
-- ===================================================================

create table llx_propal
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc          integer,
  fk_soc_contact  integer,
  fk_projet       integer default 0, -- projet auquel est rattache la propale
  ref             varchar(30) NOT NULL,  -- propal number
  datec           datetime,              -- date de creation de l'enregistrement
  datep           date,                  -- date de la propal
  author          varchar(30),
  fk_user_author  integer,   -- createur de la propale
  fk_statut       smallint  default 0,
  price           real      default 0,
  remise          real      default 0,
  tva             real      default 0,
  total           real      default 0,
  note            text,

  UNIQUE INDEX (ref)
);
