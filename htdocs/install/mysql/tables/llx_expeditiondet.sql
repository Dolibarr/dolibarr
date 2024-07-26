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
-- ===================================================================

-- Note: does not contains the product and batch, the table on supplier side llx_receptiondet_batch does.

create table llx_expeditiondet
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_expedition     integer NOT NULL,  						-- ID of parent object
  fk_element        integer,           						-- ID of main source object
  fk_elementdet     integer,           						-- ID of line of source object (proposal, sale order)
  element_type   	varchar(50) DEFAULT 'commande' NOT NULL,	-- Type of source object ('commande', ...)
  fk_product        integer,  								-- ID of product. If empy, you can retreive it using fk_element/element_type link
  qty               real,              						-- Quantity
  fk_entrepot       integer,           						-- Warehouse for departure of product
  rang              integer  DEFAULT 0
)ENGINE=innodb;
