-- ===================================================================
-- $Id$
-- $Source$
-- ===================================================================


create table llx_bookmark
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc         int,
  author         varchar(255),
  dateb          datetime
);
