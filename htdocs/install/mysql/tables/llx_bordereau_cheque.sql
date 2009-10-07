-- ===================================================================
-- Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009 Regis Houssin        <regis@dolibarr.fr>
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
--
-- $Id$
-- ===================================================================

--
-- Bordereaux de remise de cheque
--
create table llx_bordereau_cheque
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  datec             datetime NOT NULL,
  date_bordereau    date,					-- A quoi sert cette date ?
  number            varchar(16) NOT NULL,
  entity            integer DEFAULT 1 NOT NULL,	-- multi company id
  amount            double(24,8) NOT NULL,
  nbcheque          smallint NOT NULL,
  fk_bank_account   integer,
  fk_user_author    integer,
  note              text,
  statut            smallint(1) NOT NULL DEFAULT 0
  
)type=innodb;
