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


create table llx_don
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  fk_statut       smallint NOT NULL DEFAULT 0,-- etat du don promesse/valid
  datec           datetime,         -- date de création de l'enregistrement
  datedon         datetime,         -- date du don/promesse
  amount          real default 0,
  mode            varchar(20),
  nom             varchar(255),
  adresse         text,
  cp              varchar(30),
  ville           varchar(50),
  pays            varchar(50),
  public          smallint NOT NULL DEFAULT 1, -- le don est-il public (0,1)
  fk_don_projet   integer NOT NULL, -- projet auquel est fait le don
  fk_user_author  integer NOT NULL,
  fk_user_valid   integer NOT NULL,
  note            text
);
