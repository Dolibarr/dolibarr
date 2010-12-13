-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
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
-- Types action comm
--

delete from llx_c_actioncomm where id in (1,2,3,4,5,8,9,50);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 1, 'AC_TEL',  'system', 'Phone call' ,NULL);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 2, 'AC_FAX',  'system', 'Fax send'          ,NULL);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 3, 'AC_PROP', 'system', 'Send commercial proposal by email'  ,'propal');
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 4, 'AC_EMAIL','system', 'Send Email'        ,NULL);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 5, 'AC_RDV',  'system', 'Rendez-vous'        ,NULL);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 8, 'AC_COM',  'system', 'Send customer order by email'     ,'order');
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 9, 'AC_FAC',  'system', 'Send customer invoice by email'      ,'invoice');
insert into llx_c_actioncomm (id, code, type, libelle, module) values (30, 'AC_SUP_ORD',  'system', 'Send supplier invoice by email'      ,'order_supplier');
insert into llx_c_actioncomm (id, code, type, libelle, module) values (31, 'AC_SUP_INV',  'system', 'Send supplier invoice by email'      ,'invoice_supplier');
insert into llx_c_actioncomm (id, code, type, libelle, module) values (50, 'AC_OTH',  'system', 'Other'              ,NULL);
