-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2025 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2010-2016 Juanjo Menent        <jmenent@2byte.es>
-- Copyright (C) 2012      Sebastian Neuwert    <sebastian.neuwert@modula71.de>
-- Copyright (C) 2012	   Ricardo Schluter		<info@ripasch.nl>
-- Copyright (C) 2022	   Miro Sertić   		<miro.sertic0606@gmail.com>
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
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- FR:
-- Taux TVA
-- Source des taux: https://fr.wikipedia.org/wiki/Taxe_sur_la_valeur_ajout%C3%A9e
--
-- EN:
-- VAT - value-added tax
-- Source:  https://en.wikipedia.org/wiki/Value-added_tax
--

-- Warning : the rules for rowid (fk_pays+'1') is no longer used (Multicompany compatibility)

-- delete from llx_c_tva;

-- ALGERIA (id country=13)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (13,     '0','0','TVA 0%',   1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (13,  '9','0','TVA 9%',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (13,     '19','0','TVA 19%',   1,__ENTITY__);

-- ANGOLA (id country=35)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (35,   '0','0','VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (35,   '7','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (35,  '14','0','VAT rate - standard',1,__ENTITY__);

-- ARGENTINA (id country=23)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (23,   '0','0','IVA Rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (23,'10.5','0','IVA reduced rate',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (23,  '21','0','IVA standard rate',1,__ENTITY__);

-- AUSTRALIA (id country=28)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (28,   '0','0','VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (28,  '10','0','VAT rate - standard',1,__ENTITY__);

-- AUSTRIA (id country=41)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (41,   '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (41,  '10','0','VAT rate - reduced', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (41,  '20','0','VAT rate - standard',1,__ENTITY__);

-- BRASIL (id country=56)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (56,  '0','0','VAT rate - reduced',1,__ENTITY__);

-- BULGARIA (id country=59)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (59,   '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (59,   '7','0','VAT rate - reduced', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (59,  '20','0','VAT rate - standard',1,__ENTITY__);

-- BELGIUM (id country=2)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (2,   '0','0','VAT rate 0 ou non applicable',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (2,   '6','0','VAT rate - reduced', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (2,  '21','0','VAT rate - standard',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (2,  '12','0','VAT rate - reduced', 1,__ENTITY__);

-- CANADA (id country=14)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (14,   '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (14,   '7','0','VAT rate - standard',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,localtax1,localtax1_type,note,active,entity) values (14,'5','0','9.975','1','GST/TPS and PST/TVQ rate for Province',1,__ENTITY__);
-- insert into llx_c_tva(fk_pays,taux,recuperableonly,localtax1,localtax1_type,note,active,entity) values (14,'5','0','9.975','1','GST/TPS and PST/TVQ rate for Quebec',1,__ENTITY__);
-- insert into llx_c_tva(fk_pays,taux,recuperableonly,localtax1,localtax1_type,note,active,entity) values (14,'5','0','7','1','GST/TPS and PST/TVQ rate for British Columbia',1,__ENTITY__);
-- insert into llx_c_tva(fk_pays,taux,recuperableonly,localtax1,localtax1_type,note,active,entity) values (14,'5','0',null,null,'GST/TPS and PST/TVQ rate for Yukon',1,__ENTITY__);


-- CAMEROUN (id country=24)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (24,     '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (24, '19.25','0','VAT rate - standard',1,__ENTITY__);

-- CHILE (id country=67)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (67,   '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (67,  '19','0','VAT rate - standard',1,__ENTITY__);

-- CHINA (id country=9)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (9,    '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (9,   '13','0','VAT rate - reduced 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (9,    '3','0','VAT rate -  super-reduced 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (9,   '17','0','VAT rate - standard',1,__ENTITY__);

-- CONGO = REPUBLIQUE DU CONGO (id country=72)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (72,    '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,localtax1,localtax1_type,note,active,entity) values (72,   '18','0', '0.9', '1', 'VAT rate 18+0.9', 1,__ENTITY__);

-- CROATIA (id country=76)
insert into llx_c_tva(fk_pays,taux,note,active,entity) values (76, '25','PDV 25%', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,note,active,entity) values (76, '13','PDV 13%', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,note,active,entity) values (76,  '5', 'PDV 5%', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,note,active,entity) values (76,  '0', 'PDV 0%', 1,__ENTITY__);

-- CYPRUS (id country=78)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (78,    '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (78,    '9','0','VAT rate 9',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (78,    '5','0','VAT rate 5',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (78,   '19','0','VAT rate - standard',1,__ENTITY__);

-- DANMERK (id country=80)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (80,    '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (80,   '25','0','VAT rate - standard',1,__ENTITY__);

-- FRANCE (id country=1)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (1,   '0','0','VAT rate 0 ou non applicable',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (1,  '20','0','VAT rate - standard (France hors DOM-TOM)',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (1,  '10','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (1, '5.5','0','VAT rate - reduced (France hors DOM-TOM)',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (1, '2.1','0','VAT rate - super-reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,code,recuperableonly,note,active,entity)                                                   values (1, '8.5', '85',         '0', 'VAT rate - standard (DOM sauf Guyane et Saint-Martin)',0,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,code,recuperableonly,note,active,entity)                                                   values (1, '8.5', '85NPR',      '1', 'VAT rate - standard (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par acheteur',0,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,code,recuperableonly,localtax1,localtax1_type,note,active,entity)                          values (1, '8.5', '85NPROM',    '1', 2, 3, 'VAT rate - standard (DOM sauf Guyane et Saint-Martin), NPR, Octroi de Mer',0,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,code,recuperableonly,localtax1,localtax1_type,localtax2,localtax2_type,note,active,entity) values (1, '8.5', '85NPROMOMR', '1', 2, 3, 2.5, 3, 'VAT rate - standard (DOM sauf Guyane et Saint-Martin), NPR, Octroi de Mer et Octroi de Mer Regional',0,__ENTITY__);

-- GABON (id country=16)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values ( 16,    '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (16, 'TPS95',  10,   0, '0', 0, '0', 0, 'VAT 9.5', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (16, 'TPS95C', 10,   1, '1', 0, '0', 0, 'VAT 9.5+CSS', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (16, 'TPS10',  10,   0, '0', 0, '0', 0, 'VAT 10', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (16, 'TPS10C', 10,   1, '1', 0, '0', 0, 'VAT 10+CSS', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (16, 'TPS18',  18,   0, '0', 0, '0', 0, 'VAT 18', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (16, 'TPS18C', 18,   1, '1', 0, '0', 0, 'VAT 18+CSS', 1,__ENTITY__);


-- GERMANY (id country=5)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (5,     '0','0','No VAT', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (5,   '7.0','0','ermäßigte USt.', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (5,   '5.5','0','USt. Forst', 0,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (5,  '10.7','0','USt. Landwirtschaft', 0,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (5,  '19.0','0','allgemeine Ust.',1,__ENTITY__);

-- GREECE (id country=102)
insert into llx_c_tva(fk_pays,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (102,   0, 0, '0', 0, '0', 0, 'Μηδενικό Φ.Π.Α.', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (102,  24, 0, '0', 0, '0', 0, 'Κανονικός Φ.Π.Α.', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (102,  13, 0, '0', 0, '0', 0, 'Μειωμένος Φ.Π.Α.', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (102,   6, 0, '0', 0, '0', 0, 'Υπερμειωμένος Φ.Π.Α.', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (102,   3, 0, '0', 0, '0', 0, 'Νήσων υπερμειωμένος Φ.Π.Α.', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (102,   9, 0, '0', 0, '0', 0, 'Νήσων μειωμένος Φ.Π.Α.', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (102,   4, 0, '0', 0, '0', 0, 'Νήσων υπερμειωμένος Φ.Π.Α.', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (102,  17, 0, '0', 0, '0', 0, 'Νήσων υπερμειωμένος Φ.Π.Α.', 1,__ENTITY__);

-- ICELAND (id country=116)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (116,   '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (116,'25.5','0','VAT rate - standard',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (116,   '7','0','VAT rate - reduced',1,__ENTITY__);

-- INDIA (id country=117)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (117,    '0','0','VAT rate 0',            0,__ENTITY__);

insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (117, 'C+S-5' ,  0, 2.5, '1', 2.5, '1', 0, 'CGST+SGST - Same state sales', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (117, 'I-5'   ,  5,   0, '0',   0, '0', 0, 'IGST',      1,__ENTITY__);
insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (117, 'C+S-12',  0,   6, '1',   6, '1', 0, 'CGST+SGST - Same state sales', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (117, 'I-12'  , 12,   0, '0',   0, '0', 0, 'IGST',      1,__ENTITY__);
insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (117, 'C+S-18',  0,   9, '1',   9, '1', 0, 'CGST+SGST - Same state sales', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (117, 'I-18'  , 18,   0, '0',   0, '0', 0, 'IGST',      1,__ENTITY__);
insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (117, 'C+S-28',  0,  14, '1',  14, '1', 0, 'CGST+SGST - Same state sales', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,code,taux,localtax1,localtax1_type,localtax2,localtax2_type,recuperableonly,note,active,entity) values (117, 'I-28'  , 28,   0, '0',   0, '0', 0, 'IGST',      1,__ENTITY__);

-- IRELAND (id country=8)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (8,    '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (8,   '23','0','VAT rate - standard',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (8, '13.5','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (8,    '9','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (8,  '4.8','0','VAT rate - reduced',1,__ENTITY__);

-- ITALY (id country=3)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (3,   '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (3,  '10','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (3,   '4','0','VAT rate - super-reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (3,  '22','0','VAT rate - standard',1,__ENTITY__);

-- IVORY COST (id country=21)
insert into llx_c_tva(fk_pays,taux,recuperableonly,localtax1,localtax1_type,localtax2,localtax2_type,note,active,entity) values (21,  '0','0',0,0,0,0,'IVA Rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,localtax1,localtax1_type,localtax2,localtax2_type,note,active,entity) values (21, '18','0',7.5,2,0,0,'IVA standard rate',1,__ENTITY__);

-- JAPAN (id country=123)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (123, '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (123, '5','0','VAT rate 5',1,__ENTITY__);

-- LUXEMBOURG (id country=140)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (140,  '0','0','VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (140, '14','0','VAT rate - intermediary',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (140,  '8','0','VAT rate - reduced', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (140,  '3','0','VAT rate - super-reduced', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (140, '16','0','VAT rate - standard',1,__ENTITY__);

-- MALI (id country=147)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (147,  '0','0','VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (147, '18','0','VAT rate - standard', 1,__ENTITY__);

-- MONACO (id country=27)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (27,   '0','0','VAT rate 0 ou non applicable',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (27, '8.5','0','VAT rate - standard (DOM sauf Guyane et Saint-Martin)',0,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (27, '8.5','1','VAT rate - standard (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par acheteur',0,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (27, '5.5','0','VAT rate - reduced (France hors DOM-TOM)',0,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (27,'19.6','0','VAT rate - standard (France hors DOM-TOM)',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (27, '2.1','0','VAT rate - super-reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (27,   '7','0','VAT rate - reduced',1,__ENTITY__);

-- MAROCO (id country=12)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (12,  '0','0','VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (12, '14','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (12, '10','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (12,  '7','0','VAT rate - super-reduced', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (12, '20','0','VAT rate - standard',1,__ENTITY__);

-- MALTA (id country=148)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (148,  '0','0','VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (148,  '7','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (148,  '5','0','VAT rate - super-reduced', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (148, '18','0','VAT rate - standard',1,__ENTITY__);

-- NEDERLAND (id country=17)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (17,   '0','0','0 BTW tarief', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (17,   '6','0','Verlaagd BTW tarief', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (17,  '19','0','Algemeen BTW tarief',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (17,  '21','0','Algemeen BTW tarief (vanaf 1 oktober 2012)',0,__ENTITY__);

-- NEW CALEDONIA (id country=165)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (165,   '0','0','VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (165,   '3','0','VAT standard 3', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (165,   '6','0','VAT standard 6', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (165,  '11','0','VAT rate - standard', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (165,  '22','0','VAT standard high', 1,__ENTITY__);

-- NEW ZEALAND (id country=166)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (166,   '0','0','VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (166,  '15','0','VAT rate - standard', 1,__ENTITY__);

-- NIGERIA (id country=169)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (169,   '0','0','VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (169,   '5','0','VAT rate - standard', 1,__ENTITY__);

-- NORWAY (id country=173)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (173,   '0','0','VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (173,  '14','0','VAT rate - reduced', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (173,   '8','0','VAT rate - reduced', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (173,  '25','0','VAT rate - standard', 1,__ENTITY__);

-- PANAMA (id country=178)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (178,   '0','0','ITBMS Rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (178,   '7','0','ITBMS standard rate',1,__ENTITY__);

-- PERU (id country=181)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (181,   '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (181,  '18','0','VAT rate - standard',1,__ENTITY__);

-- POLAND (id country=184)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (184,   '0','0','VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (184,   '8','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (184,   '3','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (184,  '23','0','VAT rate - standard',1,__ENTITY__);

-- PORTUGAL (id country=25)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (25,   '0','0','VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (25,  '13','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (25,  '23','0','VAT rate - standard',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (25,   '6','0','VAT rate - reduced',1,__ENTITY__);

-- ROMANIA (id country=188)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (188,   '0','0','VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (188,   '9','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (188,  '19','0','VAT rate - standard',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (188,   '5','0','VAT rate - reduced',1,__ENTITY__);

-- SAUDI ARABIA (id country=26)
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (26,   '0', '0', 'VAT rate 0', 1,__ENTITY__);
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (26,   '5', '0', 'VAT rate 5', 1,__ENTITY__);

-- SAN SALVADOR (id country=86)
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (86,  '0', '0', 'SIN IVA', 1,__ENTITY__);
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (86, '13', '0', 'IVA 13', 1,__ENTITY__);

-- SENEGAL (id country=22)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (22,  '0', '0', 'VAT rate 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (22, '10', '0', 'VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (22, '18', '0', 'VAT rate - standard',1,__ENTITY__);

-- SLOVAKIA (id country=201)
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (201,  '0', '0', 'VAT rate 0', 1,__ENTITY__);
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (201, '10', '0', 'VAT rate - reduced', 1,__ENTITY__);
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (201, '20', '0', 'VAT rate - standard', 1,__ENTITY__);

-- SLOVENIA (id country=202)
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (202,  '0', '0', 'VAT rate 0', 1,__ENTITY__);
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (202,'9.5', '0', 'VAT rate - reduced', 1,__ENTITY__);
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (202, '22', '0', 'VAT rate - standard', 1,__ENTITY__);

-- SOUTH AFRICA (id country=205)
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (205,  '0', '0', 'VAT rate 0', 1,__ENTITY__);
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (205, '15', '0', 'VAT rate - standard', 1,__ENTITY__);

-- SPAIN (id country=4)
insert into llx_c_tva(fk_pays,taux,recuperableonly,localtax1,localtax1_type,localtax2,localtax2_type,note,active,entity) values (4, '0','0',  '0','3','-19:-15:-9','5','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,localtax1,localtax1_type,localtax2,localtax2_type,note,active,entity) values (4,'10','0','1.4','3','-19:-15:-9','5','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,localtax1,localtax1_type,localtax2,localtax2_type,note,active,entity) values (4, '4','0','0.5','3','-19:-15:-9','5','VAT rate - super-reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,localtax1,localtax1_type,localtax2,localtax2_type,note,active,entity) values (4,'21','0','5.2','3','-19:-15:-9','5','VAT rate - standard',1,__ENTITY__);

-- SWEDEN (id country=20)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (20,   '0','0','VAT rate 0',  1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (20,  '12','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (20,   '6','0','VAT rate - super-reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (20,  '25','0','VAT rate - standard',1,__ENTITY__);

-- SWITZERLAND (id country=6)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (6,   '0','0','VAT rate 0',  1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (6, '3.8','0','VAT rate - reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (6, '2.6','0','VAT rate - super-reduced',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (6, '8.1','0','VAT rate - standard',1,__ENTITY__);

-- SRI LANKA (id country=207)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (207,   '0','0','VAT 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (207,  '15','0','VAT 15%', 1,__ENTITY__);

-- TAIWAN (id country=213)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (213,   '0','0','VAT 0', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (213,   '5','0','VAT 5%',1,__ENTITY__);

-- TUNISIA (id country=10)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (10,    '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (10,   '12','0','VAT 12%',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (10,   '18','0','VAT 18%',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (10,  '7.5','0','VAT 6% Majoré à 25% (7.5%)',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (10,   '15','0','VAT 12% Majoré à 25% (15%)',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (10, '22.5','0','VAT 18% Majoré à 25% (22.5%)',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (10,    '6','0','VAT 6%', 1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,localtax1,localtax1_type,localtax2,localtax2_type,entity) values (10,'18.18','0','VAT 18%+FODEC', 1, 1, '4', 0, 0,__ENTITY__);

-- UKRAINE (id country=226)
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (226,   '0','0','VAT rate 0',1,__ENTITY__);
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (226,  '20','0','VAT standart rate',1,__ENTITY__);

-- UNITED OF KINGDOM (id country=7)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (7,     '0','0','VAT rate 0',   1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (7,  '17.5','0','VAT rate - standard before 2011',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (7,     '5','0','VAT rate - reduced',   1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (7,  	 '20','0','VAT rate - standard',1,__ENTITY__);

-- UNITED STATES (id country=11)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (11,     '0','0','No Sales Tax',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (11,     '4','0','Sales Tax 4%',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (11,     '6','0','Sales Tax 6%',1,__ENTITY__);


-- Pour les DOM-TOM, il faut utiliser le pays FRANCE (Sinon pb avec regles de TVA et autres regles propres aux pays et europe)

-- SAINT PIERRE ET MIQUELON (id country=19)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (193,  '0','0','No VAT in SPM',1,__ENTITY__);

-- SAINT MARTIN (id country=24)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (246,  '0','0','VAT rate 0',1,__ENTITY__);


-- MAURITANIA (id country=151)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (151,  '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (151, '14','0','VAT rate 14',1,__ENTITY__);

-- MAURITIUS (id country=152)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (152,  '0','0','VAT rate 0',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (152, '15','0','VAT rate 15',1,__ENTITY__);

-- HONDURAS (id country=114)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (114,     '0','0','No ISV',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (114,    '12','0','ISV 12%',1,__ENTITY__);

-- MEXIQUE (id country=154)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (154,     '0','0','No VAT',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (154,    '16','0','VAT 16%',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (154,    '10','0','VAT Frontero',1,__ENTITY__);

-- BARBADOS (id country=46)
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (46,     '0','0','No VAT',1,__ENTITY__);
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (46,    '15','0','VAT 15%',1,__ENTITY__);
INSERT INTO llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) VALUES (46,   '7.5','0','VAT 7.5%',1,__ENTITY__);

-- VENEZUELA (id country=232)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (232,     '0','0','No VAT',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (232,    '12','0','VAT 12%',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (232,     '8','0','VAT 8%',1,__ENTITY__);

-- VIETNAM (id country=233)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (233,     '0','0','Thuế GTGT đươc khấu trừ 0%',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (233,    '5','0','Thuế GTGT đươc khấu trừ 5%',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (233,     '8','0','Thuế GTGT đươc khấu trừ 8%',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (233,     '10','0','Thuế GTGT đươc khấu trừ 10%',1,__ENTITY__);

-- BURUNDI (id country=61) -- https://www.objectif-import-export.fr/fr/marches-internationaux/fiche-pays/burundi/presentation-fiscalite
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (61,     '0','0','No VAT',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (61,    '10','0','VAT 10%',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (61,    '18','0','VAT 18%',1,__ENTITY__);

-- Turkiye (Turkey) (id country=221)
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (221,     '0','0','No VAT',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (221,    '1','0','VAT 1%',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (221,    '8','0','VAT 8%',1,__ENTITY__);
insert into llx_c_tva(fk_pays,taux,recuperableonly,note,active,entity) values (221,    '18','0','VAT 18%',1,__ENTITY__);
