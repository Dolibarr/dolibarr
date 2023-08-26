-- Copyright (C) 2011 Philippe GRAND      <philippe.grand@atoo-net.com>
-- Copyright (C) 2020 Alexandre SPANGARO  <aspangaro@open-dsi.fr>
-- Copyright (C) 2022 Udo Tamm, dolibit   <dev@dolibit.de>
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

-- WARNING 
-- Do not place a comment at the end of the line, this file is parsed during
-- installation and all '--' symbols are removed.
--

-- ATTENTION
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Availability type
--

INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_NOW', 'Immediate', null, 0, 1,  10);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_1D',   '1 day',    'd',  1, 1,  11);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_2D',   '2 days',   'd',  2, 1,  12);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_3D',   '3 days',   'd',  3, 1,  13);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_4D',   '4 days',   'd',  4, 1,  14);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_5D',   '5 days',   'd',  5, 1,  15);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_1W',   '1 week',   'w',  1, 1,  20);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_2W',   '2 weeks',  'w',  2, 1,  30);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_3W',   '3 weeks',  'w',  3, 1,  40);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_4W',   '4 weeks',  'w',  4, 1,  50);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_5W',   '5 weeks',  'w',  5, 1,  60);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_6W',   '6 weeks',  'w',  6, 1,  70);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_8W',   '8 weeks',  'w',  8, 1,  80);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_10W', '10 weeks',  'w', 10, 1,  90);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_12W', '12 weeks',  'w', 12, 1, 100);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_14W', '14 weeks',  'w', 14, 1, 110);
INSERT INTO llx_c_availability (code, label, type_duration, qty, active, position) VALUES ('AV_16W', '16 weeks',  'w', 16, 1, 120);
