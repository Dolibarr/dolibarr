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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- $Id: llx_c_action_trigger.sql,v 1.5 2011/08/03 01:25:45 eldy Exp $
--

--
-- Do not put any comment at end of lines.
--

--
-- List of all managed triggered events (used for trigger agenda and for notification)
--
delete from llx_c_action_trigger;
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (1,'FICHEINTER_VALIDATE','Intervention validated','Executed when a intervention is validated','ficheinter',18);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (2,'BILL_VALIDATE','Customer invoice validated','Executed when a customer invoice is approved','facture',6);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (3,'ORDER_SUPPLIER_APPROVE','Supplier order request approved','Executed when a supplier order is approved','order_supplier',11);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (4,'ORDER_SUPPLIER_REFUSE','Supplier order request refused','Executed when a supplier order is refused','order_supplier',12);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (5,'ORDER_VALIDATE','Customer order validate','Executed when a customer order is validated','commande',4);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (6,'PROPAL_VALIDATE','Customer proposal validated','Executed when a commercial proposal is validated','propal',2);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (7,'WITHDRAW_TRANSMIT','Withdraw command transmitted','Executed when a withdrawal command is transmited','withdraw',25);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (8,'WITHDRAW_CREDIT','Withdraw credited','Executed when a withdrawal is credited','withdraw',26);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (9,'WITHDRAW_EMIT','Withdraw emit','Executed when a withdrawal is emited','withdraw',27);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (10,'COMPANY_CREATE','Third party created','Executed when a third party is created','societe',1);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (11,'CONTRACT_VALIDATE','Contract validated','Executed when a contract is validated','contrat',17);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (12,'PROPAL_SENTBYMAIL','Commercial proposal sent by mail','Executed when a commercial proposal is sent by mail','propal',3);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (13,'ORDER_SENTBYMAIL','Customer order sent by mail','Executed when a customer order is sent by mail ','commande',5);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (14,'BILL_PAYED','Customer invoice payed','Executed when a customer invoice is payed','facture',7);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (15,'BILL_CANCEL','Customer invoice canceled','Executed when a customer invoice is conceled','facture',8);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (16,'BILL_SENTBYMAIL','Customer invoice sent by mail','Executed when a customer invoice is sent by mail','facture',9);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (17,'ORDER_SUPPLIER_VALIDATE','Supplier order validated','Executed when a supplier order is validated','order_supplier',10);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (18,'ORDER_SUPPLIER_SENTBYMAIL','Supplier order sent by mail','Executed when a supplier order is sent by mail','order_supplier',13);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (19,'BILL_SUPPLIER_VALIDATE','Supplier invoice validated','Executed when a supplier invoice is validated','invoice_supplier',14);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (20,'BILL_SUPPLIER_PAYED','Supplier invoice payed','Executed when a supplier invoice is payed','invoice_supplier',15);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (21,'BILL_SUPPLIER_SENTBYMAIL','Supplier invoice sent by mail','Executed when a supplier invoice is sent by mail','invoice_supplier',16);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (22,'SHIPPING_VALIDATE','Shipping validated','Executed when a shipping is validated','shipping',19);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (23,'SHIPPING_SENTBYMAIL','Shipping sent by mail','Executed when a shipping is sent by mail','shipping',20);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (24,'MEMBER_VALIDATE','Member validated','Executed when a member is validated','member',21);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (25,'MEMBER_SUBSCRIPTION','Member subscribed','Executed when a member is subscribed','member',22);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (26,'MEMBER_RESILIATE','Member resiliated','Executed when a member is resiliated','member',23);
insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (27,'MEMBER_DELETE','Member deleted','Executed when a member is deleted','member',24);
