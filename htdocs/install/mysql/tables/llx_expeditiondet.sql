-- ===================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2011 Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ===================================================================

create table llx_expeditiondet
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_expedition     integer NOT NULL,
  fk_origin_line    integer,           -- Correspondance de la ligne avec le document d'origine (propal, commande)
  fk_entrepot       integer,           -- Entrepot de depart du produit
  qty               real,              -- Quantity
  rang              integer  DEFAULT 0
)ENGINE=innodb;
