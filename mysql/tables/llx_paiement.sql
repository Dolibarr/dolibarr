-- ===================================================================
-- $Id$
-- $Source$
-- ===================================================================


create table llx_paiement
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture      integer,
  datec           datetime,
  datep           datetime,           -- payment date
  amount          real default 0,
  author          varchar(50),
  fk_paiement     integer NOT NULL,
  num_paiement    varchar(50),
  note            text
);
