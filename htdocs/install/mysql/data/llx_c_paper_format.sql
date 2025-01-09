-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Formats de papier
--

-- Europe
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (1,   'EU4A0',       'Format 4A0',                '1682', '2378', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (2,   'EU2A0',       'Format 2A0',                '1189', '1682', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (3,   'EUA0',        'Format A0',                 '840',  '1189', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (4,   'EUA1',        'Format A1',                 '594',  '840',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (5,   'EUA2',        'Format A2',                 '420',  '594',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (6,   'EUA3',        'Format A3',                 '297',  '420',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (7,   'EUA4',        'Format A4',                 '210',  '297',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (8,   'EUA5',        'Format A5',                 '148',  '210',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (9,   'EUA6',        'Format A6',                 '105',  '148',  'mm', 1);

-- US
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (100, 'USLetter',    'Format Letter (A)',         '216',  '279',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (105, 'USLegal',     'Format Legal',              '216',  '356',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (110, 'USExecutive', 'Format Executive',          '190',  '254',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (115, 'USLedger',    'Format Ledger/Tabloid (B)', '279',  '432',  'mm', 1);

-- Canadian
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (200, 'CAP1',        'Format Canadian P1',        '560',  '860',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (205, 'CAP2',        'Format Canadian P2',        '430',  '560',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (210, 'CAP3',        'Format Canadian P3',        '280',  '430',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (215, 'CAP4',        'Format Canadian P4',        '215',  '280',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (220, 'CAP5',        'Format Canadian P5',        '140',  '215',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (225, 'CAP6',        'Format Canadian P6',        '107',  '140',  'mm', 1);
