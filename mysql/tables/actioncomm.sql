-- ========================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- $Id$
-- $Source$
--
-- Actions commerciales
--
-- ========================================================================
create table actioncomm
(
  id             integer AUTO_INCREMENT PRIMARY KEY,
  datea          datetime,         -- action date
  fk_action      integer,
  fk_soc         integer,
  author         varchar(30),
  fk_user_author integer,
  fk_contact     integer,
  note           text,
  propalrowid    integer
);

