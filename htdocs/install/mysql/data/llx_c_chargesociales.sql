-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2021      Udo Tamm             <dev@dolibit.de>
--
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
-- Types de charges 
--

-- insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (id of country, id of social charges = fk_pays id & free numbering, label, ...); 

--
-- France
--
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 1,  1, 'Securite sociale (URSSAF / MSA)', 1, 1, 'TAXSECU');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 1,  2, 'Securite sociale des indépendants (URSSAF)', 1, 1, 'TAXSSI');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 1, 10, 'Taxe apprentissage', 1, 1, 'TAXAPP');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 1, 11, 'Formation professionnelle continue', 1, 1, 'TAXFPC');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 1, 12, 'Cotisation fonciere des entreprises (CFE)', 1, 1, 'TAXCFE');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 1, 13, 'Cotisation sur la valeur ajoutee des entreprises (CVAE)', 1, 1, 'TAXCVAE');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 1, 20, 'Taxe fonciere', 1, 1, 'TAXFON');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 1, 25, 'Prelevement à la source (PAS)', 0, 1, 'TAXPAS');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 1, 30, 'Prevoyance', 1, 1,'TAXPREV');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 1, 40, 'Mutuelle', 1, 1,'TAXMUT');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 1, 50, 'Retraite', 1, 1,'TAXRET');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 1, 60, 'Taxe sur vehicule societe (TVS)', 0, 1, 'TAXTVS');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values ( 1, 70, 'impôts sur les sociétés (IS)', 0, 1, 'TAXIS');

--
-- Belgique
--
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (2, 201, 'ONSS',						1,1,'TAXBEONSS');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (2, 210, 'Precompte professionnel', 	1,1,'TAXBEPREPRO');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (2, 220, 'Prime existence',    		1,1,'TAXBEPRIEXI');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (2, 230, 'Precompte immobilier',      1,1,'TAXBEPREIMMO');

--
-- Austria
--
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (41, 4101, 'Krankenversicherung',				1,1,'TAXATKV');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (41, 4102, 'Unfallversicherung',				1,1,'TAXATUV');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (41, 4103, 'Pensionsversicherung',				1,1,'TAXATPV');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (41, 4104, 'Arbeitslosenversicherung',			1,1,'TAXATAV');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (41, 4105, 'Insolvenzentgeltsicherungsfond',   1,1,'TAXATIESG');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (41, 4106, 'Wohnbauförderung',					1,1,'TAXATWF');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (41, 4107, 'Arbeiterkammerumlage',				1,1,'TAXATAK');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (41, 4108, 'Mitarbeitervorsorgekasse',			1,1,'TAXATMVK');
insert into llx_c_chargesociales (fk_pays, id, libelle, deductible, active, code) values (41, 4109, 'Familienlastenausgleichsfond',		1,1,'TAXATFLAF');
