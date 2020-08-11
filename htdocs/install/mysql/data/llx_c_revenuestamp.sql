-- Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- Valeur des timbres fiscaux
-- Source des taux: ...
--

delete from llx_c_revenuestamp;

-- TUNISIA (id country=10) --
insert into llx_c_revenuestamp(rowid,fk_pays,taux,revenuestamp_type,note,active) values (101, 10, 0.4, 'fixed', 'Revenue stamp tunisia', 1);


-- MEXICO (id country=154) --
insert into llx_c_revenuestamp(rowid,fk_pays,taux,revenuestamp_type,note,active) values (1541, 154, 1.5, 'percent', 'Revenue stamp mexico', 1);
insert into llx_c_revenuestamp(rowid,fk_pays,taux,revenuestamp_type,note,active) values (1542, 154,   3, 'percent', 'Revenue stamp mexico', 1);

