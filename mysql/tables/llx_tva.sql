-- ===================================================================
-- Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- $Id$
-- ===================================================================

create table llx_tva
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  datep           date,           -- date de paiement
  datev           date,           -- date de valeur
  amount          real NOT NULL DEFAULT 0,
  label           varchar(255),
  note            text,
  fk_bank         integer NOT NULL,  
  fk_user_creat   integer,            -- utilisateur qui a créé l'info
  fk_user_modif   integer             -- utilisateur qui a modifié l'info
)type=innodb;
