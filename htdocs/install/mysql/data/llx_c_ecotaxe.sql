-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2018 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
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
-- Eco-Taxes
--

-- France (Organisme Eco-systèmes)
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (1, '25040', 'PETIT APPAREILS MENAGERS', 0.25000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (2, '25050', 'TRES PETIT APPAREILS MENAGERS', 0.08000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (3, '32070', 'ECRAN POIDS < 5 KG', 2.08000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (4, '32080', 'ECRAN POIDS > 5 KG', 1.25000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (5, '32051', 'ORDINATEUR PORTABLE', 0.42000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (6, '32061', 'TABLETTE INFORMATIQUE', 0.84000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (7, '36011', 'ORDINATEUR FIXE (UC)', 1.15000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (8, '36021', 'IMPRIMANTES', 0.83000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (9, '36030', 'IT (INFORMATIQUE ET TELECOMS)', 0.83000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (10, '36040', 'PETIT IT (CLAVIERS / SOURIS)', 0.08000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (11, '36050', 'TELEPHONIE MOBILE', 0.02000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (12, '36060', 'CONNECTIQUE CABLES', 0.02000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (13, '45010', 'GROS MATERIEL GRAND PUBLIC (TELEAGRANDISSEURS)', 1.67000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (14, '45020', 'MOYEN MATERIEL GRAND PUBLIC (LOUPES ELECTRONIQUES)', 0.42000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (15, '45030', 'PETIT MATERIEL GRAND PUBLIC (VIE QUOTIDIENNE)', 0.08000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (16, '75030', 'JOUETS < 0,5 KG', 0.08000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (17, '75040', 'JOUETS ENTRE 0,5 KG ET 10 KG', 0.17000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (18, '74050', 'JOUETS > 10 KG', 1.67000000, 'Eco-systèmes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, label, price, organization, fk_pays, active) VALUES (19, '85010', 'EQUIPEMENT MEDICAL < 0,5 KG', 0.08000000, 'Eco-systèmes', 1, 1);
