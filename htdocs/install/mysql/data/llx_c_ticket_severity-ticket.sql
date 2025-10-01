-- Copyright (C) 2018	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2024	Regis Houssin		<regis.houssin@inodbox.com>
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
-- Contenu de la table llx_c_ticket_severity
--

INSERT INTO llx_c_ticket_severity (code, pos, label, color, active, use_default, description, entity) VALUES ('LOW',      '10', 'Low',                 '', 1, 0, NULL, __ENTITY__);
INSERT INTO llx_c_ticket_severity (code, pos, label, color, active, use_default, description, entity) VALUES ('NORMAL',   '20', 'Normal',              '', 1, 1, NULL, __ENTITY__);
INSERT INTO llx_c_ticket_severity (code, pos, label, color, active, use_default, description, entity) VALUES ('HIGH',     '30', 'High',                '', 1, 0, NULL, __ENTITY__);
INSERT INTO llx_c_ticket_severity (code, pos, label, color, active, use_default, description, entity) VALUES ('BLOCKING', '40', 'Critical / blocking', '', 1, 0, NULL, __ENTITY__);
