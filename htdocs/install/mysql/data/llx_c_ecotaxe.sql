-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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

-- France (Organisme ERP)
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES ( 1, 'ER-A-A', 'Materiels electriques < 0,2kg', 0.01000000, 'ERP', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES ( 2, 'ER-A-B', 'Materiels electriques >= 0,2 kg et < 0,5 kg', 0.03000000, 'ERP', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES ( 3, 'ER-A-C', 'Materiels electriques >= 0,5 kg et < 1 kg', 0.04000000, 'ERP', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES ( 4, 'ER-A-D', 'Materiels electriques >= 1 kg et < 2 kg', 0.13000000, 'ERP', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES ( 5, 'ER-A-E', 'Materiels electriques >= 2 kg et < 4kg', 0.21000000, 'ERP', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES ( 6, 'ER-A-F', 'Materiels electriques >= 4 kg et < 8 kg', 0.42000000, 'ERP', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES ( 7, 'ER-A-G', 'Materiels electriques >= 8 kg et < 15 kg', 0.84000000, 'ERP', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES ( 8, 'ER-A-H', 'Materiels electriques >= 15 kg et < 20 kg', 1.25000000, 'ERP', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES ( 9, 'ER-A-I', 'Materiels electriques >= 20 kg et < 30 kg', 1.88000000, 'ERP', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (10, 'ER-A-J', 'Materiels electriques >= 30 kg', 3.34000000, 'ERP', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (11, 'ER-M-1', 'TV, Moniteurs < 9kg', 0.84000000, 'ERP', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (12, 'ER-M-2', 'TV, Moniteurs >= 9kg et < 15kg', 1.67000000, 'ERP', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (13, 'ER-M-3', 'TV, Moniteurs >= 15kg et < 30kg', 3.34000000, 'ERP', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (14, 'ER-M-4', 'TV, Moniteurs >= 30 kg', 6.69000000, 'ERP', 1, 1);

-- France (Organisme Ecologic)
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (15, 'EC-A-A', 'Materiels electriques  0,2 kg max', 0.00840000, 'Ecologic', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (16, 'EC-A-B', 'Materiels electriques 0,21 kg min - 0,50 kg max', 0.02500000, 'Ecologic', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (17, 'EC-A-C', 'Materiels electriques  0,51 kg min - 1 kg max', 0.04000000, 'Ecologic', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (18, 'EC-A-D', 'Materiels electriques  1,01 kg min - 2,5 kg max', 0.13000000, 'Ecologic', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (19, 'EC-A-E', 'Materiels electriques  2,51 kg min - 4 kg max', 0.21000000, 'Ecologic', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (20, 'EC-A-F', 'Materiels electriques 4,01 kg min - 8 kg max', 0.42000000, 'Ecologic', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (21, 'EC-A-G', 'Materiels electriques  8,01 kg min - 12 kg max', 0.63000000, 'Ecologic', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (22, 'EC-A-H', 'Materiels electriques 12,01 kg min - 20 kg max', 1.05000000, 'Ecologic', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (23, 'EC-A-I', 'Materiels electriques  20,01 kg min', 1.88000000, 'Ecologic', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (24, 'EC-M-1', 'TV, Moniteurs 9 kg max', 0.84000000, 'Ecologic', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (25, 'EC-M-2', 'TV, Moniteurs 9,01 kg min - 18 kg max', 1.67000000, 'Ecologic', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (26, 'EC-M-3', 'TV, Moniteurs 18,01 kg min - 36 kg max', 3.34000000, 'Ecologic', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (27, 'EC-M-4', 'TV, Moniteurs 36,01 kg min', 6.69000000, 'Ecologic', 1, 1);

-- France (Organisme Eco-systemes)
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (28, 'ES-M-1', 'TV, Moniteurs <= 20 pouces', 0.84000000, 'Eco-systemes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (29, 'ES-M-2', 'TV, Moniteurs > 20 pouces et <= 32 pouces', 3.34000000, 'Eco-systemes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (30, 'ES-M-3', 'TV, Moniteurs > 32 pouces et autres grands ecrans', 6.69000000, 'Eco-systemes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (31, 'ES-A-A', 'Ordinateur fixe, Audio home systems (HIFI), elements hifi separes', 0.84000000, 'Eco-systemes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (32, 'ES-A-B', 'Ordinateur portable, CD-RCR, VCR, lecteurs et enregistreurs DVD, instruments de musique et caisses de resonance, haut parleurs...', 0.25000000, 'Eco-systemes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (33, 'ES-A-C', 'Imprimante, photocopieur, telecopieur', 0.42000000, 'Eco-systemes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (34, 'ES-A-D', 'Accessoires, clavier, souris, PDA, imprimante photo, appareil photo, gps, telephone, repondeur, telephone sans fil, modem, telecommande, casque, camescope, baladeur mp3, radio portable, radio K7 et CD portable, radio reveil', 0.08400000, 'Eco-systemes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (35, 'ES-A-E', 'GSM', 0.00840000, 'Eco-systemes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (36, 'ES-A-F', 'Jouets et equipements de loisirs et de sports < 0,5 kg', 0.04200000, 'Eco-systemes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (37, 'ES-A-G', 'Jouets et equipements de loisirs et de sports > 0,5 kg', 0.17000000, 'Eco-systemes', 1, 1);
INSERT INTO llx_c_ecotaxe (rowid, code, libelle, price, organization, fk_pays, active) VALUES (38, 'ES-A-H', 'Jouets et equipements de loisirs et de sports > 10 kg', 1.25000000, 'Eco-systemes', 1, 1);
