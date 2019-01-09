-- Copyright (C) 2018 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- Contenu de la table llx_c_ticket_type
--

INSERT INTO llx_c_ticket_type (code, pos, label, active, use_default, description) VALUES('COM',     '10', 'Commercial question',           1, 1, NULL);
INSERT INTO llx_c_ticket_type (code, pos, label, active, use_default, description) VALUES('ISSUE',   '20', 'Issue or problem'  ,            1, 0, NULL);
INSERT INTO llx_c_ticket_type (code, pos, label, active, use_default, description) VALUES('REQUEST', '25', 'Change or enhancement request', 1, 0, NULL);
INSERT INTO llx_c_ticket_type (code, pos, label, active, use_default, description) VALUES('PROJECT', '30', 'Project', 0, 0, NULL);
INSERT INTO llx_c_ticket_type (code, pos, label, active, use_default, description) VALUES('OTHER',   '40', 'Other',   1, 0, NULL);
