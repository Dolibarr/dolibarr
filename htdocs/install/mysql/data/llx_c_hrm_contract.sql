-- ============================================================================
-- Copyright (C) 2015 Alexandre Spangaro  <aspangaro.dolibarr@gmail.com>
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
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

INSERT INTO llx_c_hrm_contract (rowid, pos, code, label, active) VALUES(1, 1,'CDI', 'contrat duree indeterminee', 1);
INSERT INTO llx_c_hrm_contract (rowid, pos, code, label, active) VALUES(2, 2,'CDD', 'contra duree determinee', 1);
INSERT INTO llx_c_hrm_contract (rowid, pos, code, label, active) VALUES(3, 3,'CA', 'contrat apprentissage', 1);
INSERT INTO llx_c_hrm_contract (rowid, pos, code, label, active) VALUES(4, 4,'CP', 'contrat professionalisation', 1);
