-- ============================================================================
-- Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ============================================================================

create table llx_product_fournisseur_price_log
(
  rowid                       integer AUTO_INCREMENT PRIMARY KEY,
  datec                       datetime,
  fk_product_fournisseur      integer      NOT NULL,
  price                       double(24,8) DEFAULT 0,
  quantity                    double,
  fk_user                     integer,

  fk_multicurrency			integer,
  multicurrency_code		varchar(3),
  multicurrency_tx			double(24,8) DEFAULT 1,
  multicurrency_unitprice	double(24,8) DEFAULT NULL,		-- unit price without tax
  multicurrency_price		double(24,8) DEFAULT NULL
)ENGINE=innodb;
