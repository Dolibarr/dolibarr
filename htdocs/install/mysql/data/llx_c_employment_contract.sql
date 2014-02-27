-- ===================================================================
-- Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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
-- ===================================================================
--
-- Employment_contract
--

-- France
INSERT INTO llx_c_employment_contract (rowid, code, label, fk_pays, active) VALUES ( 1, 'CDD', "Contrat à durée déterminée", 1, 1);
INSERT INTO llx_c_employment_contract (rowid, code, label, fk_pays, active) VALUES ( 2, 'CDI', "Contrat à durée indéterminée", 1, 1);
INSERT INTO llx_c_employment_contract (rowid, code, label, fk_pays, active) VALUES ( 3, 'CTT', "Contrat de travail temporaire", 1, 1);
INSERT INTO llx_c_employment_contract (rowid, code, label, fk_pays, active) VALUES ( 4, 'CA10', "Contrat d'apprentissage entreprises artisanales ou de 10 salariés au plus", 1, 1);
INSERT INTO llx_c_employment_contract (rowid, code, label, fk_pays, active) VALUES ( 5, 'CA', "Contrat d'apprentissage entreprises non artisanales de plus de 10 salariés", 1, 1);
INSERT INTO llx_c_employment_contract (rowid, code, label, fk_pays, active) VALUES ( 6, 'CDDO', "Contrat à durée déterminée à objet défini", 1, 1);
INSERT INTO llx_c_employment_contract (rowid, code, label, fk_pays, active) VALUES ( 7, 'CDDS', "Contrat à durée déterminée des séniors", 1, 1);
INSERT INTO llx_c_employment_contract (rowid, code, label, fk_pays, active) VALUES ( 8, 'CS', "Convention de stage", 1, 1);
INSERT INTO llx_c_employment_contract (rowid, code, label, fk_pays, active) VALUES ( 9, 'CVA', "Convention volontaire associatif", 1, 1);
INSERT INTO llx_c_employment_contract (rowid, code, label, fk_pays, active) VALUES (10, 'ESC', "Engagement de service civique", 1, 1);
INSERT INTO llx_c_employment_contract (rowid, code, label, fk_pays, active) VALUES (11, 'SSC', "Sans contrat de travail ou conventionnement", 1, 1);