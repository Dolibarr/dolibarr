-- ===================================================================
-- $Id$
-- $Source$
-- ===================================================================

create table llx_ventes
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc        integer NOT NULL,
  fk_product    integer NOT NULL,
  dated         datetime,         -- date debut
  datef         datetime,         -- date fin
  price         real,
  author	varchar(30),
  active        smallint DEFAULT 0 NOT NULL,
  note          varchar(255)
);
