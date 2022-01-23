-- ============================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
-- Copyright (C) 2005 Marc Barilley / Ocebo <marc@ocebo.com>
-- Copyright (C) 2005 Laurent Destailleur   <eldy@users.sourceforge.net>
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
-- ===========================================================================

create table llx_paiementfourn_facturefourn
(
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  fk_paiementfourn INTEGER DEFAULT NULL,
  fk_facturefourn  INTEGER DEFAULT NULL,
  amount double(24,8) DEFAULT 0,
  
  multicurrency_code	varchar(3),
  multicurrency_tx		double(24,8) DEFAULT 1,
  multicurrency_amount	double(24,8) DEFAULT 0
)ENGINE=innodb;
