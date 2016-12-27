-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2012 	   Tommaso Basilici       <t.basilici@19.coop>
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

insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour) values (1,'RECEP',       1,1, 'Due Upon Receipt','Due Upon Receipt',0,1);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour) values (2,'30D',         2,1, '30 jours','Réglement à 30 jours',0,30);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour) values (3,'30DENDMONTH', 3,1, '30 jours fin de mois','Réglement à 30 jours fin de mois',1,30);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour) values (4,'60D',         4,1, '60 jours','Réglement à 60 jours',0,60);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour) values (5,'60DENDMONTH', 5,1, '60 jours fin de mois','Réglement à 60 jours fin de mois',1,60);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour) values (6,'PT_ORDER',    6,1, 'A réception de commande','A réception de commande',0,1);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour) values (7,'PT_DELIVERY', 7,1, 'Livraison','Règlement à la livraison',0,1);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour) values (8,'PT_5050',     8,1, '50 et 50','Règlement 50% à la commande, 50% à la livraison',0,1);
