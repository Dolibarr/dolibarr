-- ===================================================================
-- $Id$
-- $Source$
--
-- Societes a recontacter
--
-- ===================================================================
create table llx_soc_recontact
(
  rowid      integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc     integer,
  datere     datetime,
  author     varchar(15)
);