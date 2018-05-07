-- Copyright (C) 2017 ATM Consulting      <contact@atm-consulting.fr>
-- Copyright (C) 2017 Pierre-Henry Favre  <phf@atm-consulting.fr>
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
-- Coef expense
--

INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (1,4, 1, 0.41, 0);
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (2,4, 2, 0.244, 824);
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (3,4, 3, 0.286, 0);

INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (4,5, 4, 0.493, 0);
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (5,5, 5, 0.277, 1082);
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (6,5, 6, 0.332, 0); 

INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (7,6, 7, 0.543, 0); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (8,6, 8, 0.305, 1180); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (9,6, 9, 0.364, 0); 

INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (10,7, 10, 0.568, 0); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (11,7, 11, 0.32, 1244); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (12,7, 12, 0.382, 0); 

INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (13,8, 13, 0.595, 0); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (14,8, 14, 0.337, 1288); 
INSERT INTO llx_expensereport_ik (rowid,fk_c_exp_tax_cat, fk_range, coef, ikoffset) values (15,8, 15, 0.401, 0); 