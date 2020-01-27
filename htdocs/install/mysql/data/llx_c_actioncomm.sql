-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
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
-- Types action comm
--

delete from llx_c_actioncomm where id in (1,2,3,4,5,8,9,10,30,31,40,50);
-- Code used from 3.3+ when type of event is used
insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 1,'AC_TEL','system','Phone call',NULL, 1, 2);
insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 2,'AC_FAX','system','Send Fax',NULL, 1, 3);
insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 4,'AC_EMAIL','system','Send Email',NULL, 1, 4);
insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 5,'AC_RDV','system','Rendez-vous',NULL, 1, 1);
insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values (11,'AC_INT','system','Intervention on site',NULL, 1, 4);
-- Code kept for backward compatibility < 3.3 
--insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 3,'AC_PROP','systemauto', 'Send commercial proposal by email','propal',0,10);
--insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 8,'AC_COM','systemauto','Send customer order by email','order', 0,8);
--insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 9,'AC_FAC','systemauto', 'Send customer invoice by email','invoice',0,6);
--insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 10,'AC_SHIP','systemauto', 'Send shipping by email','shipping',0,11);
--insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 30,'AC_SUP_ORD','systemauto','Send supplier order by email','order_supplier',0,9);
--insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 31,'AC_SUP_INV','systemauto','Send supplier invoice by email','invoice_supplier',0,7);
-- Code used from 3.3+ when type of event is not used
insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 40,'AC_OTH_AUTO','systemauto','Other (automatically inserted events)',NULL, 1, 20);
insert into llx_c_actioncomm (id, code, type, libelle, module, active, position) values ( 50,'AC_OTH','system','Other (manually inserted events)',NULL, 1, 5);
