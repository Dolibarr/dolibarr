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
-- Range expense
--

INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (1,4, 0, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (2,4, 5000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (3,4, 20000, 1, 1);

INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (4,5, 0, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (5,5, 5000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (6,5, 20000, 1, 1);

INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (7,6, 0, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (8,6, 5000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (9,6, 20000, 1, 1);

INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (10,7, 0, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (11,7, 5000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (12,7, 20000, 1, 1);

INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (13,8, 0, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (14,8, 5000, 1, 1);
INSERT INTO llx_c_exp_tax_range (rowid,fk_c_exp_tax_cat,range_ik, entity, active) values (15,8, 20000, 1, 1);