-- ===================================================================
-- $Id$
-- $Source$
-- ===================================================================

create table llx_bank_class
(
  lineid   integer not null,
  fk_categ integer not null,

  INDEX(lineid)
);
