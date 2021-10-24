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
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parse lors
-- de l'install et tous les sigles '--' sont supprimes.
--

--
-- Types de charges
--

insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values ( 1, 'Allocations familiales', 1,1,'TAXFAM'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values ( 2, 'CSG Deductible',         1,1,'TAXCSGD'  ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values ( 3, 'CSG/CRDS NON Deductible',0,1,'TAXCSGND' ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (10, 'Taxe apprenttissage',    0,1,'TAXAPP'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (11, 'Taxe professionnelle',   0,1,'TAXPRO'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (20, 'Impots locaux/fonciers', 0,1,'TAXFON'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (25, 'Impots revenus',         0,1,'TAXREV'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (30, 'Assurance Sante',        0,1,'TAXSECU'  ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (40, 'Mutuelle',               0,1,'TAXMUT'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (50, 'Assurance vieillesse',   0,1,'TAXRET'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (60, 'Assurance Chomage',      0,1,'TAXCHOM'  ,'1');
