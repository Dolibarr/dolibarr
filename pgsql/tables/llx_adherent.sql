-- ===================================================================
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
-- ===================================================================

-- statut
-- 0 : non adherent
-- 1 : adherent

create table llx_adherent
(
  rowid            SERIAL,
  tms              timestamp,
  statut           smallint NOT NULL DEFAULT 0,
  fk_adherent_type smallint,
  datec            timestamp,
  prenom           varchar(50),
  nom              varchar(50),
  societe          varchar(50),
  adresse          text,
  cp               varchar(30),
  ville            varchar(50),
  pays             varchar(50),
  email            varchar(255),
  fk_user_author   integer NOT NULL,
  fk_user_valid    integer NOT NULL,
  datefin          timestamp NOT NULL, -- date de fin de validité de la cotisation
  note             text
);

