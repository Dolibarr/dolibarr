-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2019 	   Markus Welters       <markus@welters.de>
-- Copyright (C) 2022 	   Joachim Kueter       <jkueter@gmx.de>
-- Copyright (C) 2022 	   Nick Fragoulis

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


-- LIST ON https://fr.wikipedia.org/wiki/Jour_férié


-- A lot of countries
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('NEWYEARDAY1',    __ENTITY__, 0, 0,  1,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('LABORDAY1',      __ENTITY__, 0, 0,  5,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('ASSOMPTIONDAY1', __ENTITY__, 0, 0,  8, 15, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('CHRISTMASDAY1',  __ENTITY__, 0, 0, 12, 25, 1);

-- France only (1)
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-VICTORYDAY',  __ENTITY__, 1, '', 0,  5,  8, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-NATIONALDAY', __ENTITY__, 1, '', 0,  7, 14, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-ASSOMPTION',  __ENTITY__, 1, '', 0,  8, 15, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-TOUSSAINT',   __ENTITY__, 1, '', 0, 11,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-ARMISTICE',   __ENTITY__, 1, '', 0, 11, 11, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-EASTER',      __ENTITY__, 1, 'eastermonday', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-ASCENSION',   __ENTITY__, 1, 'ascension', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('FR-PENTECOST',   __ENTITY__, 1, 'pentecost', 0, 0, 0, 1);

-- Belgium only (2)
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-VICTORYDAY',  __ENTITY__, 2, '', 0,  5,  8, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-NATIONALDAY', __ENTITY__, 2, '', 0,  7, 21, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-ASSOMPTION',  __ENTITY__, 2, '', 0,  8, 15, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-TOUSSAINT',   __ENTITY__, 2, '', 0, 11,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-ARMISTICE',   __ENTITY__, 2, '', 0, 11, 11, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-EASTER',      __ENTITY__, 2, 'eastermonday', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-ASCENSION',   __ENTITY__, 2, 'ascension', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('BE-PENTECOST',   __ENTITY__, 2, 'pentecost', 0, 0, 0, 1);

-- Italy (3)
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('IT-LIBEAZIONE',     __ENTITY__, 3, 0,  4, 25, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('IT-EPIPHANY',       __ENTITY__, 3, 0,  6,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('IT-REPUBBLICA',     __ENTITY__, 3, 0,  6,  2, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('IT-TUTTISANTIT',    __ENTITY__, 3, 0, 11,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('IT-IMMACULE',       __ENTITY__, 3, 0, 12,  8, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, year, month, day, active) VALUES('IT-SAINTSTEFAN',    __ENTITY__, 3, 0, 12, 26, 1);

-- Spain (4)
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('ES-EASTER',        __ENTITY__, 4, 'easter', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('ES-REYE',          __ENTITY__, 4,       '', 0,  6,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('ES-HISPANIDAD',    __ENTITY__, 4,       '', 0, 10, 12, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('ES-TOUSSAINT',     __ENTITY__, 4,       '', 0, 11,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('ES-CONSTITUIZION', __ENTITY__, 4,       '', 0, 12,  6, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('ES-IMMACULE',      __ENTITY__, 4,       '', 0, 12,  8, 1);

-- Germany (5)
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-NEUJAHR',              __ENTITY__, 5,                '', 0,  1,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-HL3KOEN--TLW',         __ENTITY__, 5,                '', 0,  1,  6, 0);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-INTFRAUENTAG--TLW',    __ENTITY__, 5,                '', 0,  3,  8, 0);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-KARFREITAG',           __ENTITY__, 5,      'goodfriday', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-OSTERMONTAG',          __ENTITY__, 5,    'eastermonday', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-TAGDERARBEIT',         __ENTITY__, 5,                '', 0,  5,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-HIMMELFAHRT',          __ENTITY__, 5,       'ascension', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-PFINGSTEN',            __ENTITY__, 5, 'pentecotemonday', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-FRONLEICHNAM--TLW',    __ENTITY__, 5,    'fronleichnam', 0,  0,  0, 0);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-MARIAEHIMMEL--TLW',    __ENTITY__, 5,                '', 0,  8, 15, 0);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-WELTKINDERTAG--TLW',   __ENTITY__, 5,                '', 0,  9, 20, 0);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-TAGDERDEUTEINHEIT',    __ENTITY__, 5,                '', 0, 10,  3, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-REFORMATIONSTAG--TLW', __ENTITY__, 5,                '', 0, 10, 31, 0);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-ALLERHEILIGEN--TLW',   __ENTITY__, 5,                '', 0, 11,  1, 0);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-WEIHNACHTSTAG1',       __ENTITY__, 5,                '', 0, 12, 25, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('DE-WEIHNACHTSTAG2',       __ENTITY__, 5,                '', 0, 12, 26, 1);

-- Austria (41)
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-EASTER',       __ENTITY__, 41, 'eastermonday', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-ASCENSION',    __ENTITY__, 41,    'ascension', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-PENTECOST',    __ENTITY__, 41,    'pentecost', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-FRONLEICHNAM', __ENTITY__, 41, 'fronleichnam', 0, 0, 0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-KONEGIE',      __ENTITY__, 41,             '', 0,  6,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-26OKT',        __ENTITY__, 41,             '', 0, 10, 26, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-TOUSSAINT',    __ENTITY__, 41,             '', 0, 11,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-IMMACULE',     __ENTITY__, 41,             '', 0, 12,  8, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-24DEC',        __ENTITY__, 41,             '', 0, 12, 24, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-SAINTSTEFAN',  __ENTITY__, 41,             '', 0, 12, 26, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('AT-Silvester',    __ENTITY__, 41,             '', 0, 12, 31, 1);

-- Greece (102)
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΠΡΩΤΟΧΡΟΝΙΑ', __ENTITY__, 102, '', 0,  1,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΘΕΟΦΑΝΕΙΑ', __ENTITY__, 102, '', 0,  1,  6, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-25Η ΜΑΡΤΙΟΥ', __ENTITY__, 102, '', 0,  3,  25, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΠΡΩΤΟΜΑΓΙΑ', __ENTITY__, 102, '', 0,  5,  1, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΚΑΘΑΡΑ ΔΕΥΤΕΡΑ', __ENTITY__, 102, 'ΚΑΘΑΡΑ_ΔΕΥΤΕΡΑ', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΜΕΓΑΛΗ ΠΑΡΑΣΚΕΥΗ', __ENTITY__, 102, 'ΜΕΓΑΛΗ_ΠΑΡΑΣΚΕΥΗ', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΔΕΥΤΕΡΑ ΤΟΥ ΠΑΣΧΑ', __ENTITY__, 102, 'ΔΕΥΤΕΡΑ_ΤΟΥ_ΠΑΣΧΑ', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΤΟΥ ΑΓΙΟΥ ΠΝΕΥΜΑΤΟΣ', __ENTITY__, 102, 'ΤΟΥ_ΑΓΙΟΥ_ΠΝΕΥΜΑΤΟΣ', 0,  0,  0, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΚΟΙΜΗΣΗ ΤΗΣ ΘΕΟΤΟΚΟΥ', __ENTITY__, 102, '', 0,  8,  15, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-28Η ΟΚΤΩΒΡΙΟΥ', __ENTITY__, 102, '', 0,  10, 28, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΧΡΙΣΤΟΥΓΕΝΝΑ', __ENTITY__, 102, '', 0,  12, 25, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('GR-ΣΥΝΑΞΗ ΘΕΟΤΟΚΟΥ', __ENTITY__, 102, '', 0, 12, 26, 1);

-- India (117)
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('IN-REPUBLICDAY',  __ENTITY__, 117, '', 0,  1, 26, 1);
INSERT INTO llx_c_hrm_public_holiday (code, entity, fk_country, dayrule, year, month, day, active) VALUES('IN-GANDI',        __ENTITY__, 117, '', 0, 10,  2, 1);


