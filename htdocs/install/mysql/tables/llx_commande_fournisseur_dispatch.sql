-- ===================================================================
-- Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- This table is just an history table to track all receiving done for a 
-- particular supplier order. A movement with same information is also done
-- into stock_movement so this table may be useless.
-- ===================================================================

create table llx_commande_fournisseur_dispatch
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande    integer,
  fk_product     integer,
  fk_commandefourndet integer,
  fk_projet  	 integer  DEFAULT NULL,
  fk_reception 	 integer  DEFAULT NULL,
  qty            float,              -- qty
  fk_entrepot    integer,
  fk_user        integer,
  comment		 varchar(255),		  -- comment on movement
  batch          varchar(30) DEFAULT NULL,
  eatby          date DEFAULT NULL,
  sellby         date DEFAULT NULL,
  status         integer,
  datec          datetime,
  tms            timestamp
)ENGINE=innodb;
