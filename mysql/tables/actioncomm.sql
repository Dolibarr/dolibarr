-- ========================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- $Id$
-- $Source$
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- Actions commerciales
--
-- ========================================================================

create table actioncomm
(
  id             integer AUTO_INCREMENT PRIMARY KEY,
  datea          datetime,           -- action date
  fk_action      integer,
  label          varchar(50),        -- libelle de l'action
  fk_soc         integer,
  fk_contact     integer default 0,
  fk_user_action integer,            -- id de la personne qui doit effectuer l'action
  fk_user_author integer,
  priority       smallint,
  percent        smallint,
  note           text,
  propalrowid    integer,
  fk_facture     integer
);




