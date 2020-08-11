-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2011-2017 Alexandre Spangaro   <aspangaro@open-dsi.fr>
-- Copyright (C) 2019      swedebugia	        <swedebugia@riseup.net>
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
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

-- Description of the accounts in BAS-K1-MINI
-- ADD 2000000 to rowid # Do no remove this comment --

INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__,  1, 'BAS-K1-MINI', 'IMMO',    '', '1000', '0', 'Immateriella anläggningstillgångar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__,  2, 'BAS-K1-MINI', 'IMMO',    '', '1110', '0', 'Byggnader och markanläggningar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__,  3, 'BAS-K1-MINI', 'IMMO',    '', '1130', '0', 'Mark och andra tillgångar som inte får skrivas av', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__,  4, 'BAS-K1-MINI', 'IMMO',    '', '1220', '0', 'Maskiner och inventarier', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__,  5, 'BAS-K1-MINI', 'IMMO',    '', '1300', '0', 'Övriga anläggningstillgångar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__,  6, 'BAS-K1-MINI', 'CAPIT',   '', '1400', '0', 'Varulager', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__,  7, 'BAS-K1-MINI', 'CAPIT',   '', '1500', '0', 'Kundfordringar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__,  8, 'BAS-K1-MINI', 'CAPIT',   '', '1600', '0', 'Övriga fordringar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__,  9, 'BAS-K1-MINI', 'CAPIT',   '', '1920', '0', 'Kassa och bank', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 10, 'BAS-K1-MINI', 'FINAN',   '', '2010', '0', 'Eget kapital', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 11, 'BAS-K1-MINI', 'FINAN',   '', '2330', '0', 'Låneskulder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 12, 'BAS-K1-MINI', 'FINAN',   '', '2610', '0', 'Skatteskulder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 13, 'BAS-K1-MINI', 'FINAN',   '', '2440', '0', 'Leverantörsskulder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 14, 'BAS-K1-MINI', 'FINAN',   '', '2900', '0', 'Övriga skulder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 15, 'BAS-K1-MINI', 'INCOME',  '', '3000', '0', 'Försäljning och utfört arbete samt övriga momspliktiga intäkter', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 16, 'BAS-K1-MINI', 'INCOME',  '', '3100', '0', 'Momsfria intäkter', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 17, 'BAS-K1-MINI', 'INCOME',  '', '3200', '0', 'Bil- och bostadsförmån m.m.', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 18, 'BAS-K1-MINI', 'INCOME',  '', '8310', '0', 'Ränteintäkter', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 19, 'BAS-K1-MINI', 'EXPENSE', '', '4000', '0', 'Varor, material och tjänster', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 20, 'BAS-K1-MINI', 'EXPENSE', '', '6900', '0', 'Övriga externa kostnader', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 21, 'BAS-K1-MINI', 'EXPENSE', '', '7000', '0', 'Anställd personal', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 22, 'BAS-K1-MINI', 'EXPENSE', '', '8410', '0', 'Räntekostnader m.m.', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 23, 'BAS-K1-MINI', 'OTHER',   '', '7820', '0', 'Avskrivningar och nedskrivningar av byggnader och markanläggningar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 24, 'BAS-K1-MINI', 'OTHER',   '', '7810', '0', 'Avskrivningar och nedskrivningar av maskiner och inventarier och immateriella tillgångar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 25, 'BAS-K1-MINI', 'OTHER',   '', '2080', '0', 'Periodiseringsfonder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 26, 'BAS-K1-MINI', 'OTHER',   '', '2050', '0', 'Expansionsfond', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 27, 'BAS-K1-MINI', 'OTHER',   '', '2060', '0', 'Ersättningsfonder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 28, 'BAS-K1-MINI', 'OTHER',   '', '2070', '0', 'Insatsemissioner, skogskonto, upphovsmannakonto, avbetalningsplan på skog o.d.', 1);
