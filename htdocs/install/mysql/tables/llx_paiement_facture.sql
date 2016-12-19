-- ===================================================================
-- Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================

create table llx_paiement_facture
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_paiement     integer,
  fk_facture      integer,
  amount          double(24,8)     DEFAULT 0,
  
  multicurrency_code		varchar(255),
  multicurrency_tx		double(24,8) DEFAULT 1,
  multicurrency_amount	double(24,8) DEFAULT 0
)ENGINE=innodb;
