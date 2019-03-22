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
-- ADD 18000000 to rowid # Do no remove this comment --

INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6000, 'BAS-K1-MINI', 'Anläggningstillgångar', '', '1000', '', 'Immateriella anläggningstillgångar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6001, 'BAS-K1-MINI', 'Anläggningstillgångar', '', '1110', '', 'Byggnader och markanläggningar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6002, 'BAS-K1-MINI', 'Anläggningstillgångar', '', '1130', '', 'Mark och andra tillgångar som inte får skrivas av', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6003, 'BAS-K1-MINI', 'Anläggningstillgångar', '', '1220', '', 'Maskiner och inventarier', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6004, 'BAS-K1-MINI', 'Anläggningstillgångar', '', '1300', '', 'Övriga anläggningstillgångar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6005, 'BAS-K1-MINI', 'Omsättningstillgångar', '', '1400', '', 'Varulager', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6006, 'BAS-K1-MINI', 'Omsättningstillgångar', '', '1500', '', 'Kundfordringar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6007, 'BAS-K1-MINI', 'Omsättningstillgångar', '', '1600', '', 'Övriga fordringar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6008, 'BAS-K1-MINI', 'Omsättningstillgångar', '', '1920', '', 'Kassa och bank', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6009, 'BAS-K1-MINI', 'Eget kapital', '', '2010', '', 'Eget kapital', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6010, 'BAS-K1-MINI', 'Skulder', '', '2330', '', 'Låneskulder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6011, 'BAS-K1-MINI', 'Skulder', '', '2610', '', 'Skatteskulder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6012, 'BAS-K1-MINI', 'Skulder', '', '2440', '', 'Leverantörsskulder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6013, 'BAS-K1-MINI', 'Skulder', '', '2900', '', 'Övriga skulder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6014, 'BAS-K1-MINI', 'Intäkter', '', '3000', '', 'Försäljning och utfört arbete samt övriga momspliktiga intäkter', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6015, 'BAS-K1-MINI', 'Intäkter', '', '3100', '', 'Momsfria intäkter', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6016, 'BAS-K1-MINI', 'Intäkter', '', '3200', '', 'Bil- och bostadsförmån m.m.', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6017, 'BAS-K1-MINI', 'Intäkter', '', '8310', '', 'Ränteintäkter', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6018, 'BAS-K1-MINI', 'Kostnader', '', '4000', '', 'Varor, material och tjänster', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6019, 'BAS-K1-MINI', 'Kostnader', '', '6900', '', 'Övriga externa kostnader', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6020, 'BAS-K1-MINI', 'Kostnader', '', '7000', '', 'Anställd personal', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6021, 'BAS-K1-MINI', 'Kostnader', '', '8410', '', 'Räntekostnader m.m.', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6022, 'BAS-K1-MINI', 'Avskrivningar och nedskrivningar', '', '7820', '', 'Avskrivningar och nedskrivningar av byggnader och markanläggningar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6023, 'BAS-K1-MINI', 'Avskrivningar och nedskrivningar', '', '7810', '', 'Avskrivningar och nedskrivningar av maskiner och inventarier och immateriella tillgångar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6024, 'BAS-K1-MINI', 'Upplysningar', '', '2080', '', 'Periodiseringsfonder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6025, 'BAS-K1-MINI', 'Upplysningar', '', '2050', '', 'Expansionsfond', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6026, 'BAS-K1-MINI', 'Upplysningar', '', '2060', '', 'Ersättningsfonder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 6027, 'BAS-K1-MINI', 'Upplysningar', '', '2070', '', 'Insatsemissioner, skogskonto, upphovsmannakonto, avbetalningsplan på skog o.d.', 1);
