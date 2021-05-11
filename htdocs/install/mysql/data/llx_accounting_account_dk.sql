-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2011-2017 Alexandre Spangaro   <aspangaro@zendsi.com>
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

-- Description of the accounts in DK-STD
-- ADD 8000000 to rowid # Do no remove this comment --

INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5082, 'DK-STD', 'Indtægter', '', '1000', '', 'Salg af varer/ydelser m. moms', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5083, 'DK-STD', 'Indtægter', '', '1010', '', 'Salg af varer EU', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5084, 'DK-STD', 'Indtægter', '', '1020', '', 'Salg af ydelser EU', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5085, 'DK-STD', 'Indtægter', '', '1030', '', 'Ej momspligtigt salg', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5086, 'DK-STD', 'Indtægter', '', '1040', '', 'Regulering igangværende arbejder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5087, 'DK-STD', 'Omkostninger', '', '1100', '', 'Varekøb med moms', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5088, 'DK-STD', 'Omkostninger', '', '1110', '', 'Køb af varer EU', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5089, 'DK-STD', 'Omkostninger', '', '1120', '', 'Køb af ydelser EU', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5090, 'DK-STD', 'Omkostninger', '', '1130', '', 'Varelagerregulering', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5091, 'DK-STD', 'Omkostninger', '', '1140', '', 'Eget vareforbrug', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5092, 'DK-STD', 'Omkostninger', '', '1300', '', 'Repræsentation', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5093, 'DK-STD', 'Omkostninger', '', '1310', '', 'Annoncer/reklame', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5094, 'DK-STD', 'Omkostninger', '', '1320', '', 'Rejseudgifter', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5095, 'DK-STD', 'Omkostninger', '', '1330', '', 'Aviser og blade', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5096, 'DK-STD', 'Omkostninger', '', '1400', '', 'Brændstof', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5097, 'DK-STD', 'Omkostninger', '', '1410', '', 'Bilforsikring', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5098, 'DK-STD', 'Omkostninger', '', '1420', '', 'Vedligeholdelse', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5099, 'DK-STD', 'Omkostninger', '', '1430', '', 'Grøn ejerafgift mv.', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5100, 'DK-STD', 'Omkostninger', '', '1440', '', 'Leje og leasing', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5101, 'DK-STD', 'Omkostninger', '', '1450', '', 'Bilvask og pleje af bil', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5102, 'DK-STD', 'Omkostninger', '', '1460', '', 'Parkeringsudgifter', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5103, 'DK-STD', 'Omkostninger', '', '1470', '', 'Biludgifter efter statens takster', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5104, 'DK-STD', 'Omkostninger', '', '1480', '', 'Fri bil', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5105, 'DK-STD', 'Omkostninger', '', '1490', '', 'Privat andel af biludgifter', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5106, 'DK-STD', 'Omkostninger', '', '1600', '', 'Husleje uden forbrug', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5107, 'DK-STD', 'Omkostninger', '', '1610', '', 'Forbrugsudgifter (el, vand, gas og varme mv)', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5108, 'DK-STD', 'Omkostninger', '', '1620', '', 'Ejendomsskatter og forsikringer', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5109, 'DK-STD', 'Omkostninger', '', '1630', '', 'Vedligeholdelse af lokaler', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5110, 'DK-STD', 'Omkostninger', '', '1700', '', 'Kontorartikler og tryksager', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5111, 'DK-STD', 'Omkostninger', '', '1705', '', 'Telefon og internet', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5112, 'DK-STD', 'Omkostninger', '', '1710', '', 'Fri telefon privat andel', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5113, 'DK-STD', 'Omkostninger', '', '1715', '', 'Anskaffelse af småaktiver', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5114, 'DK-STD', 'Omkostninger', '', '1720', '', 'Arbejdstøj', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5115, 'DK-STD', 'Omkostninger', '', '1725', '', 'Rådgiverudgifter', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5116, 'DK-STD', 'Omkostninger', '', '1730', '', 'Porto og gebyrer', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5117, 'DK-STD', 'Omkostninger', '', '1735', '', 'Forsikringer', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5118, 'DK-STD', 'Omkostninger', '', '1740', '', 'Bøger og faglitteratur', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5119, 'DK-STD', 'Omkostninger', '', '1745', '', 'Konstaterede tab på debitorer', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5120, 'DK-STD', 'Omkostninger', '', '1750', '', 'Kassedifferencer', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5121, 'DK-STD', 'Afskrivninger', '', '2000', '', 'Afskrivning driftsmidler og inventar', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5122, 'DK-STD', 'Afskrivninger', '', '2010', '', 'Afskrivning blandede driftsmidler', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5123, 'DK-STD', 'Afskrivninger', '', '2020', '', 'Afskrivninger privat andel', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5124, 'DK-STD', 'Afskrivninger', '', '2030', '', 'Gevinst og tab ved salg af aktiver', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5125, 'DK-STD', 'Finansielle poster', '', '2400', '', 'Renteindtægter bank', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5126, 'DK-STD', 'Finansielle poster', '', '2410', '', 'Renteindtægter kunder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5127, 'DK-STD', 'Finansielle poster', '', '2500', '', 'Renteudgifter bank og realkreditinstitut', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5128, 'DK-STD', 'Finansielle poster', '', '2510', '', 'Renteudgifter leverandører', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5129, 'DK-STD', 'Finansielle poster', '', '2520', '', 'Fradragsberettigede låneomkostninger', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5130, 'DK-STD', 'Finansielle poster', '', '2530', '', 'Fradragsberettigede renteudgifter til det offentlige', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5131, 'DK-STD', 'Balance', '', '3000', '', 'Afskrivningsgrundlag primo', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5132, 'DK-STD', 'Balance', '', '3010', '', 'Årets køb', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5133, 'DK-STD', 'Balance', '', '3015', '', 'Årets forbedringer', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5134, 'DK-STD', 'Balance', '', '3020', '', 'Årest salg', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5135, 'DK-STD', 'Balance', '', '3030', '', 'Årets afskrivning', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5136, 'DK-STD', 'Balance', '', '3040', '', 'Gevinst og tab', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5137, 'DK-STD', 'Balance', '', '3100', '', 'Afskrivningsgrundlag primo', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5138, 'DK-STD', 'Balance', '', '3110', '', 'Årets køb', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5139, 'DK-STD', 'Balance', '', '3115', '', 'Årets forbedringer', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5140, 'DK-STD', 'Balance', '', '3120', '', 'Årets salg', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5141, 'DK-STD', 'Balance', '', '3130', '', 'Årets afskrivning', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5142, 'DK-STD', 'Balance', '', '3140', '', 'Gevinst og tab', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5143, 'DK-STD', 'Balance', '', '3900', '', 'Huslejedepositum', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5144, 'DK-STD', 'Balance', '', '4000', '', 'Kasse', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5145, 'DK-STD', 'Balance', '', '4010', '', 'Bank', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5146, 'DK-STD', 'Balance', '', '4020', '', 'Forudbetalte omkostninger', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5147, 'DK-STD', 'Balance', '', '4030', '', 'Debitorer', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5148, 'DK-STD', 'Balance', '', '4040', '', 'Varelager', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5149, 'DK-STD', 'Balance', '', '4050', '', 'Igangværende arbejder', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5150, 'DK-STD', 'Balance', '', '5000', '', 'Egenkapital primo', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5151, 'DK-STD', 'Balance', '', '5010', '', 'Årets resultat', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5152, 'DK-STD', 'Balance', '', '5020', '', 'Privat hævet', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5153, 'DK-STD', 'Balance', '', '5030', '', 'Fri bil', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5154, 'DK-STD', 'Balance', '', '5040', '', 'Statens takster', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5155, 'DK-STD', 'Balance', '', '5050', '', 'Fri telefon m.v.', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5156, 'DK-STD', 'Balance', '', '5060', '', 'Private andele', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5157, 'DK-STD', 'Balance', '', '5065', '', 'Private afskrivninger', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5158, 'DK-STD', 'Balance', '', '5070', '', 'Driftsudgifter u/fradrag', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5159, 'DK-STD', 'Balance', '', '5080', '', 'Privat udlæg', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5160, 'DK-STD', 'Balance', '', '5090', '', 'Indskud', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5161, 'DK-STD', 'Balance', '', '7000', '', 'Gæld bank og realkreditinstitut', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5162, 'DK-STD', 'Balance', '', '7010', '', 'Gæld til leverandører af varer og tjenesteydelser', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5163, 'DK-STD', 'Balance', '', '7020', '', 'Periodeafgrænsningsposter', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5164, 'DK-STD', 'Balance', '', '7030', '', 'Anden gæld', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5165, 'DK-STD', 'Balance', '', '8000', '', 'Salgsmoms', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5166, 'DK-STD', 'Balance', '', '8010', '', 'Købsmoms', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5167, 'DK-STD', 'Balance', '', '8020', '', 'Konto for afgift af varekøb i EU', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5168, 'DK-STD', 'Balance', '', '8030', '', 'Konto for afgift af købte ydelser i EU', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5169, 'DK-STD', 'Balance', '', '8040', '', 'El- afgift', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5170, 'DK-STD', 'Balance', '', '8050', '', 'Øvrige energiafgifter', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 5171, 'DK-STD', 'Balance', '', '8060', '', 'Betalt moms', 1);
