-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2010 	   Juanjo Menent        <jmenent@2byte.es>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Taux TVA
-- Source des taux: http://fr.wikipedia.org/wiki/Taxe_sur_la_valeur_ajout%C3%A9e
--

delete from llx_c_tva;

-- ARGENTINA (id 23)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (231, 23,  '21','0','IVA standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (232, 23,'10.5','0','IVA reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (233, 23,   '0','0','IVA Rate 0', 1);

-- AUSTRALIA (id 28)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (281, 28,  '10','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (282, 28,   '0','0','VAT Rate 0', 1);

-- AUSTRIA (id 41)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (411, 41,  '20','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (412, 41,  '10','0','VAT reduced rate', 1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (413, 41,   '0','0','VAT Rate 0',1);

-- BULGARIA (id 59)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (591, 59,  '20','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (592, 59,   '7','0','VAT reduced rate', 1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (593, 59,   '0','0','VAT Rate 0',1);

-- BELGIUM (id 2)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 21,  2,  '21','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 22,  2,   '6','0','VAT reduced rate', 1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 23,  2,   '0','0','VAT Rate 0 ou non applicable',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 24,  2,  '12','0','VAT reduced rate', 1);

-- CANADA (id 14)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (141, 14,   '7','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (142, 14,   '0','0','VAT Rate 0',1);

-- CHILE (id 67)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (671, 67,  '19','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (672, 67,   '0','0','VAT Rate 0',1);

-- CHINA (id 9)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 91, 9,   '17','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 92, 9,   '13','0','VAT reduced rate 0',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 93, 9,    '3','0','VAT super reduced rate 0',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 94, 9,    '0','0','VAT Rate 0',1);

-- DANMERK (id 80)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (801,80,   '25','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (802,80,    '0','0','VAT Rate 0',1);

-- FRANCE (id 1)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 11,  1,'19.6','0','VAT standard rate (France hors DOM-TOM)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 12,  1, '8.5','0','VAT standard rate (DOM sauf Guyane et Saint-Martin)',0);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 13,  1, '8.5','1','VAT standard rate (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par acheteur',0);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 14,  1, '5.5','0','VAT reduced rate (France hors DOM-TOM)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 15,  1,   '0','0','VAT Rate 0 ou non applicable',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 16,  1, '2.1','0','VAT super-reduced rate',1);

-- GERMANY (id 5)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 51,  5,  '19','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 52,  5,   '7','0','VAT reduced rate', 1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 53,  5,   '0','0','VAT Rate 0', 1);

-- ITALY (id 3)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 31,  3,  '20','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 32,  3,  '10','0','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 33,  3,   '4','0','VAT super-reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 34,  3,   '0','0','VAT Rate 0',1);

-- INDIA (id 117)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1171, 117,  '12.5','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1172, 117,  '4','0','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1173, 117,  '1','0','VAT super-reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1174, 117,  '0','0','VAT Rate 0',1);

-- JAPAN (id 123)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1231, 123, '0','0','VAT Rate 0',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1232, 123, '5','0','VAT Rate 5',1);

-- LUXEMBOURG (id 140)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1401, 140, '15','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1402, 140, '12','0','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1403, 140,  '6','0','VAT reduced rate', 1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1404, 140,  '3','0','VAT super-reduced rate', 1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1405, 140,  '0','0','VAT Rate 0', 1);

-- MAROCO (id 12)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (121,  12, '20','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (122,  12, '14','0','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (123,  12, '10','0','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (124,  12,  '7','0','VAT super-reduced rate', 1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (125,  12,  '0','0','VAT Rate 0', 1);

-- NEDERLAND (id 17)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (171, 17,  '19','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (172, 17,   '6','0','VAT reduced rate', 1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (173, 17,   '0','0','VAT Rate 0', 1);

-- POLAND (id 184)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1841, 184,  '20','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1842, 184,   '7','0','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1843, 184,   '3','0','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1844, 184,   '0','0','VAT Rate 0', 1);

-- PORTUGAL (id 25)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (251, 25,  '20','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (252, 25,  '12','0','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (253, 25,   '0','0','VAT Rate 0', 1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (254, 25,   '5','0','VAT reduced rate',1);

-- ROMANIA (id 188)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1881,188,  '24','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1882,188,   '9','0','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1884,188,   '5','0','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1883,188,   '0','0','VAT Rate 0', 1);

-- SAN SALVADOR (id 86)
INSERT INTO llx_c_tva(rowid, fk_pays, taux, recuperableonly, note, active) VALUES (861, 86, '13', '0', 'IVA 13', 1);
INSERT INTO llx_c_tva(rowid, fk_pays, taux, recuperableonly, note, active) VALUES (862, 86, '0', '0', 'SIN IVA', 1);

-- SLOVAKIA (id 201)
INSERT INTO llx_c_tva(rowid, fk_pays, taux, recuperableonly, note, active) VALUES (2011, 201, '19', '0', 'VAT standard rate', 1);
INSERT INTO llx_c_tva(rowid, fk_pays, taux, recuperableonly, note, active) VALUES (2012, 201, '10', '0', 'VAT reduced rate', 1);
INSERT INTO llx_c_tva(rowid, fk_pays, taux, recuperableonly, note, active) VALUES (2013, 201,  '0', '0', 'VAT Rate 0', 1);

-- SLOVENIA (id 202)
INSERT INTO llx_c_tva(rowid, fk_pays, taux, recuperableonly, note, active) VALUES (2021, 202, '20', '0', 'VAT standard rate', 1);
INSERT INTO llx_c_tva(rowid, fk_pays, taux, recuperableonly, note, active) VALUES (2022, 202,'8.5', '0', 'VAT reduced rate', 1);
INSERT INTO llx_c_tva(rowid, fk_pays, taux, recuperableonly, note, active) VALUES (2023, 202,  '0', '0', 'VAT Rate 0', 1);

-- SPAIN (id 4)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,localtax1,note,active) values ( 41, 4,  '16','0','4','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,localtax1,note,active) values ( 42, 4,   '7','0','1','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,localtax1,note,active) values ( 43, 4,   '4','0','0.5','VAT super-reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active)           values ( 44, 4,   '0','0','VAT Rate 0',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,localtax1,note,active) values ( 45, 4,  '18','0','4','VAT standard rate from July 2010',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,localtax1,note,active) values ( 46, 4,   '8','0','1','VAT reduced rate from July 2010',1);

-- SWEDEN (id 20)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (201,20,  '25','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (202,20,  '12','0','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (203,20,   '6','0','VAT super-reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (204,20,   '0','0','VAT Rate 0',  1);

-- SWITZERLAND (id 6)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 61, 6, '7.6','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 62, 6, '3.6','0','VAT reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 63, 6, '2.4','0','VAT super-reduced rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 64, 6,   '0','0','VAT Rate 0',  1);

-- TUNISIA (id 10)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (101,10,    '6','0','VAT 6%', 1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (102,10,   '12','0','VAT 12%',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (103,10,   '18','0','VAT 18%',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (104,10,  '7.5','0','VAT 6% Majoré à 25% (7.5%)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (105,10,   '15','0','VAT 12% Majoré à 25% (15%)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (106,10, '22.5','0','VAT 18% Majoré à 25% (22.5%)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (107,10,    '0','0','VAT Rate 0',  1);

-- UNITED OF KINGDOM (id 7)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 71, 7,  '17.5','0','VAT standard rate',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 72, 7,     '5','0','VAT reduced rate',   1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 73, 7,     '0','0','VAT Rate 0',   1);

-- UNITED STATES (id 11)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (111,11,     '0','0','No VAT',1);


-- Pour les DOM-TOM, il faut utiliser le pays FRANCE (Sinon pb avec regles de TVA et autres regles propres aux pays et europe)

-- SAINT PIERRE ET MIQUELON
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1931,193,  '0','0','No VAT in SPM',1);

-- SAINT MARTIN
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (2461,246,  '0','0','VAT Rate 0',1);

-- MAURITIUS
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1521,152,  '0','0','VAT Rate 0',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1522,152,  '15','0','VAT Rate 15',1);
