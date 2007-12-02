-- ============================================================================
-- Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ============================================================================

create table llx_entrepot
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  tms             timestamp,
  label           varchar(255) UNIQUE NOT NULL,
  description     text,
  lieu            varchar(64),       -- résumé lieu situation
  address         varchar(255),
  cp              varchar(10),
  ville           varchar(50),
  fk_pays         integer DEFAULT 0,
  statut          tinyint DEFAULT 1, -- 1 ouvert, 0 fermé
  valo_pmp        float(12,4),    -- valoristaion du stock en PMP
  fk_user_author  integer
)type=innodb;

