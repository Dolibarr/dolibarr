-- ============================================================================
-- Copyright (C) 2013 Jean-François Ferry <jfefe@aternatik.fr>
-- Copyright (C) 2015 Alexandre Spangaro  <aspangaro@open-dsi.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.
--
-- ============================================================================
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(1, 5,'EXECBOARD', 'Executive board', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(2, 10, 'MANAGDIR', 'Managing director', 1, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(3, 15, 'ACCOUNTMANAG', 'Account manager', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(4, 20, 'ENGAGDIR', 'Engagement director', 1, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(5, 25, 'DIRECTOR', 'Director', 1, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(6, 30, 'PROJMANAG', 'Project manager', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(7, 35, 'DEPHEAD', 'Department head', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(8, 40, 'SECRETAR', 'Secretary', 0, 1);
INSERT INTO llx_c_hrm_function (rowid, pos, code, label, c_level, active) VALUES(9, 45, 'EMPLOYEE', 'Department employee', 0, 1);
