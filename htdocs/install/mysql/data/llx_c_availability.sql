-- Copyright (C) 2011 Philippe GRAND      <philippe.grand@atoo-net.com>
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

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Availability type
--

delete from llx_c_availability;
INSERT INTO llx_c_availability (rowid,code,label,active) VALUES (1, 'AV_NOW', 'Immediate', 1);
INSERT INTO llx_c_availability (rowid,code,label,active) VALUES (2, 'AV_1W',  '1 week', 1);
INSERT INTO llx_c_availability (rowid,code,label,active) VALUES (3, 'AV_2W',  '2 weeks', 1);
INSERT INTO llx_c_availability (rowid,code,label,active) VALUES (4, 'AV_3W',  '3 weeks', 1);
