-- ===================================================================
-- $Id$
-- $Source$
-- ===================================================================

create table llx_propaldet
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_propal     integer,
  fk_product    integer,
  price         real
);
