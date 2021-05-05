-- Copyright (C) 2019-2020  Open-DSI			<support@open-dsi.fr>
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
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--


INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('MAR', 'Transport maritime (y compris camions ou wagons sur bateau)', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('TRA', 'Transport par chemin de fer (y compris camions sur wagon)', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('ROU', 'Transport par route', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('AIR', 'Transport par air', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('POS', 'Envois postaux', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('OLE', 'Installations de transport fixe (oléoduc)', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('NAV', 'Transport par navigation intérieure', 1);
INSERT INTO llx_c_transport_mode (code, label, active) VALUES ('PRO', 'Propulsion propre', 1);
