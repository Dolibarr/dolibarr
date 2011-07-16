-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2010-2011 Herve Prot           <herve.prot@symeos.com>
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
-- Types action st
--

delete from llx_c_stcomm;
insert into llx_c_stcomm (id,code,libelle,type) values (-1, 'ST_NO',    'Ne pas contacter',0);
insert into llx_c_stcomm (id,code,libelle,type) values ( 0, 'ST_NEVER', 'Jamais contacté',0);
insert into llx_c_stcomm (id,code,libelle,type) values ( 1, 'ST_TODO',  'A contacter',0);
insert into llx_c_stcomm (id,code,libelle,type) values ( 2, 'ST_PEND',  'Contact en cours',0);
insert into llx_c_stcomm (id,code,libelle,type) values ( 3, 'ST_DONE',  'Contactée',0);
insert into llx_c_stcomm (id,code,libelle,active,type) VALUES (4, 'ST_PFROI', 'Prospect froid', 1, 0);
insert into llx_c_stcomm (id,code,libelle,active,type) VALUES (5, 'ST_PTIED', 'Prospect tiède', 0, 0);
insert into llx_c_stcomm (id,code,libelle,active,type) VALUES (6, 'ST_PCHAU', 'Prospect chaud', 0, 0);
insert into llx_c_stcomm (id,code,libelle,active,type) VALUES (7, 'ST_CINF3', 'Client -3 mois', 0, 1);
insert into llx_c_stcomm (id,code,libelle,active,type) VALUES (8, 'ST_CREC', 'Client récurrent', 0, 1);
insert into llx_c_stcomm (id,code,libelle,active,type) VALUES (9, 'ST_CFID', 'Client fidèle', 1, 1);
insert into llx_c_stcomm (id,code,libelle,active,type) VALUES (10, 'ST_CPAR', 'Client partenaire', 0, 1);


