-- ===================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008 Regis Houssin        <regis.houssin@inodbox.com>
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

create table llx_deliverydet
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_delivery       integer,
  fk_origin_line    integer,                         -- Correspondance de la ligne avec le document d'origine (propal, commande)
  fk_product        integer,
  description       text,
  qty               real,                            -- quantity
  subprice          double(24,8) DEFAULT 0,          -- prix unitaire
  total_ht          double(24,8) DEFAULT 0,          -- Total HT de la ligne toute quantite
  rang              integer      DEFAULT 0
)ENGINE=innodb;
