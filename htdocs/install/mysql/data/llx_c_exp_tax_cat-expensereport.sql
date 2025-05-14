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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--


--
-- Categories expense
--

INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (1,'ExpAutoCat', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (2,'ExpCycloCat', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (3,'ExpMotoCat', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (4,'ExpAuto3CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (5,'ExpAuto4CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (6,'ExpAuto5CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (7,'ExpAuto6CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (8,'ExpAuto7CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (9,'ExpAuto8CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (10,'ExpAuto9CV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (11,'ExpAuto10CV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (12,'ExpAuto11CV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (13,'ExpAuto12CV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (14,'ExpAuto3PCV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (15,'ExpAuto4PCV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (16,'ExpAuto5PCV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (17,'ExpAuto6PCV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (18,'ExpAuto7PCV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (19,'ExpAuto8PCV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (20,'ExpAuto9PCV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (21,'ExpAuto10PCV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (22,'ExpAuto11PCV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (23,'ExpAuto12PCV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (24,'ExpAuto13PCV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (25,'ExpCyclo', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (26,'ExpMoto12CV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (27,'ExpMoto345CV', 1, 0);
INSERT INTO llx_c_exp_tax_cat (rowid, label, entity, active) values (28,'ExpMoto5PCV', 1, 0);
