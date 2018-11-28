-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Types de charges
--

--
-- France
--
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (  1, 'Allocations familiales', 1,1,'TAXFAM'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (  2, 'CSG Deductible',         1,1,'TAXCSGD'  ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (  3, 'CSG/CRDS NON Deductible',0,1,'TAXCSGND' ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values ( 10, 'Taxe apprentissage',     0,1,'TAXAPP'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values ( 11, 'Taxe professionnelle',   0,1,'TAXPRO'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values ( 12, 'Cotisation fonciere des entreprises', 0,1,'TAXCFE' ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values ( 13, 'Cotisation sur la valeur ajoutee des entreprises', 0,1,'TAXCVAE' ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values ( 20, 'Impots locaux/fonciers', 0,1,'TAXFON'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values ( 25, 'Impots revenus',         0,1,'TAXREV'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values ( 30, 'Assurance Sante',        0,1,'TAXSECU'  ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values ( 40, 'Mutuelle',               0,1,'TAXMUT'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values ( 50, 'Assurance vieillesse',   0,1,'TAXRET'   ,'1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values ( 60, 'Assurance Chomage',      0,1,'TAXCHOM'  ,'1');

--
-- Belgique
--
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (201, 'ONSS',						1,1,'TAXBEONSS'   ,'2');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (210, 'Precompte professionnel', 	1,1,'TAXBEPREPRO' ,'2');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (220, 'Prime existence',    		1,1,'TAXBEPRIEXI' ,'2');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (230, 'Precompte immobilier',      1,1,'TAXBEPREIMMO','2');

--
-- Austria
--
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4101, 'Krankenversicherung',				1,1,'TAXATKV'   ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4102, 'Unfallversicherung',				1,1,'TAXATUV'   ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4103, 'Pensionsversicherung',				1,1,'TAXATPV'   ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4104, 'Arbeitslosenversicherung',			1,1,'TAXATAV'   ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4105, 'Insolvenzentgeltsicherungsfond',   1,1,'TAXATIESG' ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4106, 'Wohnbauförderung',					1,1,'TAXATWF'   ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4107, 'Arbeiterkammerumlage',				1,1,'TAXATAK'   ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4108, 'Mitarbeitervorsorgekasse',			1,1,'TAXATMVK'  ,'41');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (4109, 'Familienlastenausgleichsfond',		1,1,'TAXATFLAF' ,'41');
