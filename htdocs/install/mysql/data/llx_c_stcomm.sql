-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- Types action st
--

delete from llx_c_stcomm;
insert into llx_c_stcomm (id,code,libelle) values (-1, 'ST_NO',    'Do not contact');
insert into llx_c_stcomm (id,code,libelle) values ( 0, 'ST_NEVER', 'Never contacted');
insert into llx_c_stcomm (id,code,libelle) values ( 1, 'ST_TODO',  'To contact');
insert into llx_c_stcomm (id,code,libelle) values ( 2, 'ST_PEND',  'Contact in progress');
insert into llx_c_stcomm (id,code,libelle) values ( 3, 'ST_DONE',  'Contacted');
