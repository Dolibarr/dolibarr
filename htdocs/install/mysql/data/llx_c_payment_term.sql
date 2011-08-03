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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- $Id: llx_c_payment_term.sql,v 1.3 2011/08/03 01:25:46 eldy Exp $
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (1,'RECEP',       1,1, 'A réception','Réception de facture',0,0);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (2,'30D',         2,1, '30 jours','Réglement à 30 jours',0,30);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (3,'30DENDMONTH', 3,1, '30 jours fin de mois','Réglement à 30 jours fin de mois',1,30);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (4,'60D',         4,1, '60 jours','Réglement à 60 jours',0,60);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (5,'60DENDMONTH', 5,1, '60 jours fin de mois','Réglement à 60 jours fin de mois',1,60);
