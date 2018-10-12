-- ===================================================================
-- Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
--
-- ===================================================================

--
-- Lignes de bordereaux de remise de cheque
--
CREATE TABLE llx_bordereau_chequedet
(
    rowid 			integer AUTO_INCREMENT PRIMARY KEY,
    fk_bordereau 	integer NOT NULL,
    fk_bank 		integer,
    fk_paiement 	integer,
    emetteur 		varchar(255),
    amount 			double(28,8) DEFAULT 0
) ENGINE = InnoDB;
