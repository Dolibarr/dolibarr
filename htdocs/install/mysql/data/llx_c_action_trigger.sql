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
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (5,'ORDER_VALIDATE','Validation commande client','Executed when a customer order is validated','commande');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (6,'PROPAL_VALIDATE','Validation proposition client','Executed when a commercial proposal is validated','propal');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (7,'WITHDRAW_TRANSMIT','Transmission prélèvement','Executed when a withdrawal is transmited','withdraw');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (8,'WITHDRAW_CREDIT','Créditer prélèvement','Executed when a withdrawal is credited','withdraw');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (9,'WITHDRAW_EMIT','Emission prélèvement','Executed when a withdrawal is emited','withdraw');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (10,'COMPANY_CREATE','Third party created','Executed when a third party is created','societe');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (11,'CONTRACT_VALIDATE','Contract validated','Executed when a contract is validated','contrat');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (12,'PROPAL_SENTBYMAIL','Commercial proposal sent by mail','Executed when a commercial proposal is sent by mail','propal');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (13,'ORDER_SENTBYMAIL','Customer order sent by mail','Executed when a customer order is sent by mail ','commande');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (14,'BILL_PAYED','Customer invoice payed','Executed when a customer invoice is payed','facture');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (15,'BILL_CANCEL','Customer invoice canceled','Executed when a customer invoice is conceled','facture');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (16,'BILL_SENTBYMAIL','Customer invoice sent by mail','Executed when a customer invoice is sent by mail','facture');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (17,'ORDER_SUPPLIER_VALIDATE','Supplier order validated','Executed when a supplier order is validated','order_supplier');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (18,'ORDER_SUPPLIER_SENTBYMAIL','Supplier order sent by mail','Executed when a supplier order is sent by mail','order_supplier');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (19,'BILL_SUPPLIER_VALIDATE','Supplier invoice validated','Executed when a supplier invoice is validated','invoice_supplier');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (20,'BILL_SUPPLIER_PAYED','Supplier invoice payed','Executed when a supplier invoice is payed','invoice_supplier');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (21,'BILL_SUPPLIER_SENTBYMAIL','Supplier invoice sent by mail','Executed when a supplier invoice is sent by mail','invoice_supplier');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (22,'SHIPPING_VALIDATE','Shipping validated','Executed when a shipping is validated','shipping');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (23,'SHIPPING_SENTBYMAIL','Shipping sent by mail','Executed when a shipping is sent by mail','shipping');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (24,'MEMBER_VALIDATE','Member validated','Executed when a member is validated','member');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (25,'MEMBER_SUBSCRIPTION','Member subscribed','Executed when a member is subscribed','member');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (26,'MEMBER_RESILIATE','Member resiliated','Executed when a member is resiliated','member');
insert into llx_c_action_trigger (rowid,code,label,description,elementtype) values (27,'MEMBER_DELETE','Member deleted','Executed when a member is deleted','member');
