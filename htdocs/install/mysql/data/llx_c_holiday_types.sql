-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2012 	   Tommaso Basilici       <t.basilici@19.coop>
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
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

-- Generic to all countries
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, 'LEAVE_SICK',    'Sick leave',    0, 0, 0,    NULL, 1, 1);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, 'LEAVE_OTHER',   'Other leave',   0, 0, 0,    NULL, 2, 1);

-- Not enabled by default, we prefer to have an entrey dedicated to country
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, 'LEAVE_PAID',    'Paid vacation', 1, 7, 0,    NULL, 3, 0);

-- Leaves specific to France
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, 'LEAVE_RTT_FR',  'RTT'          , 1,  7, 0.83,    1, 4, 1);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, 'LEAVE_PAID_FR', 'Paid vacation', 1, 30, 2.08334, 1, 5, 1);

-- Leaves specific to Greece - info from https://www.kepea.gr/
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '5D1Y', 'Κανονική άδεια(Πενθήμερο 1ο έτος)', 1,  0, 1.667, 102, 6, 1);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '5D2Y', 'Κανονική άδεια(Πενθήμερο 2ο έτος)', 1,  0, 1.75, 102, 7, 1);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '5D3-10Y', 'Κανονική άδεια(Πενθήμερο 3ο έως 10ο έτος)', 1, 0, 1.833, 102, 8, 1);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '5D10-25Y', 'Κανονική άδεια(Πενθήμερο 10ο έως 25ο έτος)', 1, 0, 2.083,    102, 9, 1);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '5D25+Y', 'Κανονική άδεια(Πενθήμερο 25+ έτη)', 1, 0, 2.166,    102, 10, 1);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '6D1Y', 'Κανονική άδεια(Εξαήμερο 1ο έτος)', 1, 0, 2.00,    102, 11, 1);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '6D2Y', 'Κανονική άδεια(Εξαήμερο 2ο έτος)', 1, 0, 2.083,    102, 12, 1);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '6D3-10Y', 'Κανονική άδεια(Εξαήμερο 3ο έως 10ο έτος)', 1, 0, 2.166,    102, 13, 1);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '6D10-25Y', 'Κανονική άδεια(Εξαήμερο 10ο έως 25ο έτος)', 1, 0, 2.083,    102, 14, 1);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '6D25+Y', 'Κανονική άδεια(Εξαήμερο 25+ έτη)', 1, 0, 2.166,    102, 15, 1);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '5D-WED', 'Πενθήμερη άδεια γάμου(με αποδοχές)', 0, 0, 0, 102, 16, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '6D-WED', 'Εξαήμερη άδεια γάμου(με αποδοχές)', 0, 0, 0, 102, 17, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '7D-AR', 'Επταήμερη άδεια ιατρικώς υποβοηθούμενης αναπαραγωγής(με αποδοχές)', 0, 0, 0, 102, 18, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '1D-BC', 'Μονοήμερη άδεια προγεννητικών εξετάσεων(με αποδοχές)', 0, 0, 0, 102, 19, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '1D-GYN', 'Μονοήμερη άδεια γυναικολογικού ελέγχου(με αποδοχές)', 0,  0, 0, 102, 20, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '149D-ML', 'Άδεια Μητρότητας (Άδεια κύησης – λοχείας)56 ημέρες πριν-93 ημέρες μετα(με αποδοχές)', 0,  0, 0,    102, 21, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '14D-PL', '14ήμερη Άδεια πατρότητας(με αποδοχές)', 0,  0, 0, 102, 22, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '1-2H-CC', 'Άδεια φροντίδας παιδιών (μειωμένο ωράριο  https://www.kepea.gr/aarticle.php?id=1984)', 0,  0, 0, 102, 23, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '9M-M', 'Ειδική άδεια προστασίας μητρότητας 9 μηνών(χωρίς αποδοχές)', 0,  0, 0, 102, 24, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '4M-M', 'Τετράμηνη γονική Άδεια Ανατροφής Τέκνων(χωρίς αποδοχές)', 0,  0, 0, 102, 25, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '6-8D-SP', 'Εξαήμερη ή Οκταήμερη Άδεια για μονογονεϊκές οικογένειες(με αποδοχές)', 0, 0, 0, 102, 26, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '6-8-14D-FC', 'Άδεια για ασθένεια μελών οικογένειας(χωρίς αποδοχές, 6 ημέρες/έτος ένα παιδί - 8 ημέρες/έτος δύο παιδιά και σε 14 ημέρες/έτος τρία (3) παιδιά και πάνω', 0, 0, 0, 102, 27, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '10D-CD', 'Δεκαήμερη Γονική Άδεια για παιδί με σοβαρά νοσήματα και λόγω νοσηλείας παιδιών(με αποδοχές)', 0, 0, 0, 102, 28, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '30D-CD', 'Άδεια λόγω νοσηλείας των παιδιών(έως 30 ημέρες/έτος χωρίς αποδοχές)', 0, 0, 0, 102, 29, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '5D-CG', 'Άδεια φροντιστή(έως 5 ημέρες/έτος χωρίς αποδοχές)', 0, 0, 0, 102, 30, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '2D-CG', 'Άδεια απουσίας από την εργασία για λόγους ανωτέρας βίας(έως 2 ημέρες/έτος με αποδοχές)', 0,  0, 0, 102, 31, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '2D-SC', 'Άδεια για παρακολούθηση σχολικής επίδοσης(έως 2 ημέρες/έτος με αποδοχές)', 0, 0, 0, 102, 32, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '1D-BD', 'Μονοήμερη άδεια αιμοδοσίας(με αποδοχές)', 0,  0, 0, 102, 33, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '22D-BT', 'Άδεια για μετάγγιση αίματος & αιμοκάθαρση(έως 22 ημέρες/έτος με αποδοχές)', 0, 0, 0, 102, 34, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '30D-HIV', 'Άδεια λόγω AIDS(έως ένα (1) μήνα/έτος με αποδοχές)', 0, 0, 0, 102, 35, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '20D-CD', 'Άδεια πενθούντων γονέων(20 ημέρες με αποδοχές)', 0, 0, 0, 102, 36, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, '2D-FD', 'Άδεια λόγω θανάτου συγγενούς(2 ημέρες με αποδοχές)', 0, 0, 0, 102, 37, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, 'DIS', 'Άδειες αναπήρων(30 ημέρες με αποδοχές)', 0, 0, 0, 102, 38, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, 'SE', 'Άδεια εξετάσεων μαθητών, σπουδαστών, φοιτητών(30 ημέρες χωρίς αποδοχές)', 0, 0, 0, 102, 39, 0);
insert into llx_c_holiday_types(entity, code, label, affect, delay, newbymonth, fk_country, sortorder, active) values (__ENTITY__, 'NOT PAID', 'Άδεια άνευ αποδοχών(έως ένα (1) έτος)', 0, 0, 0, 102, 40, 0);
