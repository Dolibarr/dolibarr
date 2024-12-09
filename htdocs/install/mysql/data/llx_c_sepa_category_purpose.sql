-- Copyright (C) 2024 		Sylvain Legrand           <contact@infras.fr>
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
-- Category purpose of the Credit transfer (for SEPA file)
--

INSERT INTO llx_c_sepa_category_purpose (code, label, position, active) VALUES ('CORE', 'c_sepa_category_purposeCORE', 0, 1);
INSERT INTO llx_c_sepa_category_purpose (code, label, position, active) VALUES ('CORT', 'c_sepa_category_purposeCORT', 1, 1);
INSERT INTO llx_c_sepa_category_purpose (code, label, position, active) VALUES ('CASH', 'c_sepa_category_purposeCASH', 2, 1);
INSERT INTO llx_c_sepa_category_purpose (code, label, position, active) VALUES ('INST', 'c_sepa_category_purposeINST', 3, 1);
INSERT INTO llx_c_sepa_category_purpose (code, label, position, active) VALUES ('SUPP', 'c_sepa_category_purposeSUPP', 4, 1);
INSERT INTO llx_c_sepa_category_purpose (code, label, position, active) VALUES ('TREA', 'c_sepa_category_purposeTREA', 5, 1);
