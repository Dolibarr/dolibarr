-- ===================================================================
-- $Id$
-- $Source$
-- ===================================================================

create table llx_facturedet
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture      integer,
  fk_product      integer,
  datec           datetime,
  note            varchar(255),
  price           smallint
);
