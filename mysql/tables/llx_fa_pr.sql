-- ===================================================================
-- $Id$
-- $Source$
-- ===================================================================

create table llx_fa_pr
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture integer,
  fk_propal  integer
);
