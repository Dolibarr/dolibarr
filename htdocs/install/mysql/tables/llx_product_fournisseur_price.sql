-- ============================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================

create table llx_product_fournisseur_price
(
  rowid                       integer AUTO_INCREMENT PRIMARY KEY,
  datec                       datetime,
  tms                         timestamp,
  fk_product_fournisseur      integer      NOT NULL,	-- deprecated
  fk_product				  integer,
  fk_soc                      integer,
  ref_fourn                   varchar(30),
  fk_availability             integer,	   
  price                       double(24,8) DEFAULT 0,
  quantity                    double,
  unitprice                   double(24,8) DEFAULT 0,
  fk_user                     integer,
  entity				      integer DEFAULT 1 NOT NULL,	   -- multi company id
)ENGINE=innodb;
