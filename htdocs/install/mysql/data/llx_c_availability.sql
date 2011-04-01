-- Copyright (C) 2011 Philippe GRAND      <philippe.grand@atoo-net.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Availability type
--

delete from llx_c_availability;
insert into llx_c_availability (rowid,code,label,active) values (1, 'DSP', 'Disponible', 1);
insert into llx_c_availability (rowid,code,label,active) values (2, 'USM', 'Une semaine', 1);
insert into llx_c_availability (rowid,code,label,active) values (3, 'DSM', 'Deux semaines', 1);
insert into llx_c_availability (rowid,code,label,active) values (4, 'TSM', 'Trois semaines', 1);
