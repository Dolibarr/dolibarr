-- ========================================================================
-- SGBD : PostgreSQL 6.5.3
-- $Id$
-- $Source$
-- ========================================================================
create table socstatutlog
(
  id          serial,
  datel       datetime,
  fk_soc      integer,
  fk_statut   integer,
  author      varchar(30)
);
