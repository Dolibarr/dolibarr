-- ===================================================================
-- $Id$
-- $Source$
-- ===================================================================

create table llx_product
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  ref             varchar(15),
  label           varchar(255),
  description     text,
  price           smallint,
  duration        varchar(32)
);

