-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2011-2018 Alexandre Spangaro   <aspangaro@zendsi.com>
-- Copyright (C) 2015-2017 Juanjo Menent        <jmenent@2byte.es>
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


INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('VT', 'Sale Journal',           2, 1, 1);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('AC', 'Purchase Journal',       3, 1, 1);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('BQ', 'Bank Journal',           4, 1, 1);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('OD', 'Other Journal',          1, 1, 1);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('AN', 'Has new Journal',        9, 1, 1);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('ER', 'Expense Report Journal', 5, 1, 1);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('INV', 'Inventory Journal'    , 8, 1, 1);


-- Description of chart of account FR PCG99-ABREGE
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  1, 'PCG99-ABREGE', 'The simple accountancy french plan', 1);

-- Description of chart of account FR PCG99-BASE
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  1, 'PCG99-BASE', 'The base accountancy french plan', 1);

-- Description of chart of account FR PCG14-DEV
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  1, 'PCG14-DEV', 'The developed accountancy french plan 2014', 1);

-- Description of chart of account BE PCMN-BASE
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  2, 'PCMN-BASE', 'The base accountancy belgium plan', 1);

-- Description of chart of account ES PCG08-PYME
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  4, 'PCG08-PYME', 'The PYME accountancy spanish plan', 1);

-- Description of chart of account CH PCG_SUISSE
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  6, 'PCG_SUISSE', 'Switzerland plan', 1);

-- Description of chart of account GB ENG-BASE
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (  7, 'ENG-BASE', 'England plan', 1);

-- Description of chart of account TN PCT
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 10, 'PCT', 'The Tunisia plan', 1);

-- Description of chart of account CL CL-PYME
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 67, 'PC-MIPYME', 'The PYME accountancy Chile plan', 1);

-- Description of chart of account DK DK-STD
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 80, 'DK-STD', 'Standardkontoplan fra SKAT', 1);

-- Description of chart of account LU PCN-LUXEMBURG
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (140, 'PCN-LUXEMBURG', 'Plan comptable normalisé Luxembourgeois', 1);

-- Description of chart of account MA PCG
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 12, 'PCG', 'The Moroccan chart of accounts', 1);

-- Description of chart of account BJ SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 49,'SYSCOHADA-BJ', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account BF SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 60,'SYSCOHADA-BF', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account CM SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 24,'SYSCOHADA-CM', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account CF SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 65,'SYSCOHADA-CF', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account KM SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 71,'SYSCOHADA-KM', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account CG SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 72,'SYSCOHADA-CG', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account CI SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 21,'SYSCOHADA-CI', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account GA SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 16,'SYSCOHADA-GA', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account GQ SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 87,'SYSCOHADA-GQ', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account ML SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (147,'SYSCOHADA-ML', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account NE SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES (168,'SYSCOHADA-NE', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account CD SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 73,'SYSCOHADA-CD', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account SN SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 22,'SYSCOHADA-SN', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account TD SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 66,'SYSCOHADA-TD', 'Plan comptable Ouest-Africain', 1);

-- Description of chart of account TG SYSCOHADA
INSERT INTO llx_accounting_system (fk_country, pcg_version, label, active) VALUES ( 15,'SYSCOHADA-TG', 'Plan comptable Ouest-Africain', 1);

