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
    fk_bordereau 	integer NOT NULL,						-- id of the bordereau
    fk_bank 		integer,								-- id of the bankentry
    fk_paiement 	integer,								-- id of the payment
    type_line		varchar(50),							-- type of the line (can be "bank" or "payment")
    emetteur 		varchar(255),							-- payment emitter
    amount 			double(28,8) DEFAULT 0,
    num_chq			varchar(50),							-- number of the check
    banque			varchar(255),							-- bank of the emitter
    datec			datetime,								-- date of the payment
) ENGINE = InnoDB;
