-- Copyright (C) 2011 Philippe GRAND      <philippe.grand@atoo-net.com>
-- Copyright (C) 2020 Alexandre SPANGARO  <aspangaro@open-dsi.fr>
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
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Availability type
--

INSERT INTO llx_c_availability (code,label,type_duration,number,active,position) VALUES ('AV_NOW', 'Immediate', null, 0, 1, 10);
INSERT INTO llx_c_availability (code,label,type_duration,number,active,position) VALUES ('AV_1W',  '1 week', 'w', 1, 1, 20);
INSERT INTO llx_c_availability (code,label,type_duration,number,active,position) VALUES ('AV_2W',  '2 weeks', 'w', 2, 1, 30);
INSERT INTO llx_c_availability (code,label,type_duration,number,active,position) VALUES ('AV_3W',  '3 weeks', 'w', 3, 1, 40);
INSERT INTO llx_c_availability (code,label,type_duration,number,active,position) VALUES ('AV_4W',  '4 weeks', 'w', 4, 1, 50);

