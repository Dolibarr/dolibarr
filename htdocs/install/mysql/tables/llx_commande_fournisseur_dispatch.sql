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
-- 
-- Detail of each lines of a reception (qty, batch and into which warehouse must be
-- received or has been receveived a purchase order line).
-- This table should have been named llx_receptiondet_batch
-- ===================================================================

create table llx_commande_fournisseur_dispatch
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_product     integer,
  fk_commande    integer,
  fk_commandefourndet integer,
  fk_projet  	 integer  DEFAULT NULL,
  fk_reception 	 integer  DEFAULT NULL,
  qty            float,             			-- qty to move
  fk_entrepot    integer,						-- ID of warehouse to use for the stock change
  comment		 varchar(255),					-- comment on movement
  batch          varchar(128) DEFAULT NULL,		-- serial/lot number
  eatby          date DEFAULT NULL,
  sellby         date DEFAULT NULL,
  status         integer,
  fk_user        integer,
  datec          datetime,
  tms            timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  cost_price     double(24,8) DEFAULT 0
)ENGINE=innodb;
