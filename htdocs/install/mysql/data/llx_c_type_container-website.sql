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
-- Type of website page/container
--

insert into llx_c_type_container (code,label,module,active) values ('page',     'Page',     'system', 1);
insert into llx_c_type_container (code,label,module,active) values ('banner',   'Banner',   'system', 1);
insert into llx_c_type_container (code,label,module,active) values ('blogpost', 'BlogPost', 'system', 1);
insert into llx_c_type_container (code,label,module,active) values ('menu',     'Menu',     'system', 1);
insert into llx_c_type_container (code,label,module,active) values ('other',    'Other',    'system', 1);

INSERT INTO llx_c_type_container(code, label, active, module, position, typecontainer) VALUES ('service', 'Service (ajax or api)', 1, 'system', 300, 'library');
INSERT INTO llx_c_type_container(code, label, active, module, position, typecontainer) VALUES ('library', 'Library (functions)', 1, 'system', 400, 'library');
