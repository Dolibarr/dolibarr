-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2010 	   Juanjo Menent        <jmenent@2byte.es>
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
-- Définition des actions de workflow
--
delete from llx_c_action_trigger;
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (1,'FICHEINTER_VALIDATE','Validation fiche intervention','Executed when a intervention is validated','ficheinter');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (2,'BILL_VALIDATE','Validation facture client','Executed when a customer invoice is approved','facture');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (3,'ORDER_SUPPLIER_APPROVE','Approbation commande fournisseur','Executed when a supplier order is approved','order_supplier');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (4,'ORDER_SUPPLIER_REFUSE','Refus commande fournisseur','Executed when a supplier order is refused','order_supplier');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (5,'ORDER_VALIDATE','Validation commande client','Executed when a customer order is validated','order');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (6,'PROPAL_VALIDATE','Validation proposition client','Executed when a commercial proposal is validated','propal');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (7,'WITHDRAW_TRANSMIT','Transmission prélèvement','Executed when a withdrawal is transmited','withdraw');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (8,'WITHDRAW_CREDIT','Créditer prélèvement','Executed when a withdrawal is credited','withdraw');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (9,'WITHDRAW_EMIT','Emission prélèvement','Executed when a withdrawal is emited','withdraw');
