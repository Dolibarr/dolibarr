-- Copyright (C) 2004-2024 Laurent Destailleur  <eldy@users.sourceforge.net>
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

-- Do not place a comment at the end of the line, this file is parsed
-- during installation and lines with '--' are removed.
--

--
-- Value of tax stamps
-- Source of rates: ...
--

delete from llx_c_revenuestamp;

-- TUNISIA (id country=10) --
insert into llx_c_revenuestamp(rowid,fk_pays,taux,revenuestamp_type,note,active) values (101, 10, 0.4, 'fixed', 'Revenue stamp tunisia', 1);

-- GREECE (id country=102) --
insert into llx_c_revenuestamp(rowid,fk_pays,taux,revenuestamp_type,note,active) values (1021, 102, 1.2, 'percent', 'Συντελεστής 1,2 %', 1);
insert into llx_c_revenuestamp(rowid,fk_pays,taux,revenuestamp_type,note,active) values (1022, 102, 2.4, 'percent', 'Συντελεστής 2,4 %', 1);
insert into llx_c_revenuestamp(rowid,fk_pays,taux,revenuestamp_type,note,active) values (1023, 102, 3.6, 'percent', 'Συντελεστής 3,6 %', 1);
insert into llx_c_revenuestamp(rowid,fk_pays,taux,revenuestamp_type,note,active) values (1024, 102, 1.0, 'fixed', 'Λοιπές περιπτώσεις Χαρτοσήμου', 1);

-- MEXICO (id country=154) --
insert into llx_c_revenuestamp(rowid,fk_pays,taux,revenuestamp_type,note,active) values (1541, 154, 1.5, 'percent', 'Revenue stamp mexico', 1);
insert into llx_c_revenuestamp(rowid,fk_pays,taux,revenuestamp_type,note,active) values (1542, 154,   3, 'percent', 'Revenue stamp mexico', 1);

-- Turkiye (Turkey) (id country=221) --
insert into llx_c_revenuestamp(rowid,fk_pays,taux,revenuestamp_type,note,active) values (22101,221,0.00948,'percent','Mukavelenameler Damga Vergisi',1);
insert into llx_c_revenuestamp(rowid,fk_pays,taux,revenuestamp_type,note,active) values (22102,221,0.00189,'percent','Kira mukavelenameleri Damga Vergisi',1);
