-- ============================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- $Id$
-- $Source$
--
--
-- ============================================================================
create table socpeople
(
  idp         integer AUTO_INCREMENT PRIMARY KEY,
  datec       datetime,
  fk_soc      integer,
  name        varchar(50),
  firstname   varchar(50),
  address     varchar(255),
  poste       varchar(80),
  phone       varchar(30),
  fax         varchar(30),
  email       varchar(255),
  note        text
);
