-- ===================================================================
-- $Id$
-- $Source$
-- ===================================================================


create table llx_voyage
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  datev           date,           -- date de valeur
  dateo           date,           -- date operation
  amount          real NOT NULL default 0,
  label           varchar(255),
  author          varchar(50),
  fk_type         smallint,       -- CB, Virement, cheque
  fk_account	integer,
  num_releve      varchar(50),
  num_chq         int,
  rappro          tinyint default 0,
  note            text
);
