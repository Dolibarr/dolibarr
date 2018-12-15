-- Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
-- Copyright (C) 2003       Jean-Louis Bergamo      <jlb@j1b.org>
-- Copyright (C) 2004-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
-- Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
-- Copyright (C) 2004       Guillaume Delecourt     <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2011  Regis Houssin           <regis.houssin@inodbox.com>
-- Copyright (C) 2007       Patrick Raguin          <patrick.raguin@gmail.com>
-- Copyright (C) 2010-2013  Juanjo Menent           <jmenent@2byte.es>
-- Copyright (C) 2013       Cedric Gross            <c.gross@kreiz-it.fr>
-- Copyright (C) 2014       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
-- Copyright (C) 2015       Bahfir Abbes            <bafbes@gmail.com>
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
-- Do not put any comment at end of lines.
--

--
-- List of all managed triggered events (used for trigger agenda automatic events and for notification)
--
delete from llx_c_action_trigger;
-- actions enabled by default (constant created for that) when we enable module agenda
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_CREATE','Third party created','Executed when a third party is created','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_SENTBYMAIL','Mails sent from third party card','Executed when you send email from third party card','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_DELETE','Third party deleted','Executed when you delete third party','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_VALIDATE','Customer proposal validated','Executed when a commercial proposal is validated','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_SENTBYMAIL','Commercial proposal sent by mail','Executed when a commercial proposal is sent by mail','propal',3);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CLOSE_SIGNED','Customer proposal closed signed','Executed when a customer proposal is closed signed','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CLOSE_REFUSED','Customer proposal closed refused','Executed when a customer proposal is closed refused','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CLASSIFY_BILLED','Customer proposal set billed','Executed when a customer proposal is set to billed','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_DELETE','Customer proposal deleted','Executed when a customer proposal is deleted','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_VALIDATE','Customer order validate','Executed when a customer order is validated','commande',4);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_CLOSE','Customer order classify delivered','Executed when a customer order is set delivered','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_CLASSIFY_BILLED','Customer order classify billed','Executed when a customer order is set to billed','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_CANCEL','Customer order canceled','Executed when a customer order is canceled','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SENTBYMAIL','Customer order sent by mail','Executed when a customer order is sent by mail ','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_DELETE','Customer order deleted','Executed when a customer order is deleted','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_VALIDATE','Customer invoice validated','Executed when a customer invoice is approved','facture',6);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_PAYED','Customer invoice payed','Executed when a customer invoice is payed','facture',7);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_CANCEL','Customer invoice canceled','Executed when a customer invoice is conceled','facture',8);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SENTBYMAIL','Customer invoice sent by mail','Executed when a customer invoice is sent by mail','facture',9);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_UNVALIDATE','Customer invoice unvalidated','Executed when a customer invoice status set back to draft','facture',9);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_DELETE','Customer invoice deleted','Executed when a customer invoice is deleted','facture',9);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_VALIDATE','Price request validated','Executed when a commercial proposal is validated','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_SENTBYMAIL','Price request sent by mail','Executed when a commercial proposal is sent by mail','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_CLOSE_SIGNED','Price request closed signed','Executed when a customer proposal is closed signed','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_CLOSE_REFUSED','Price request closed refused','Executed when a customer proposal is closed refused','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_DELETE','Price request deleted','Executed when a customer proposal delete','proposal_supplier',10);
--insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_CREATE','Supplier order created','Executed when a supplier order is created','order_supplier',11);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_VALIDATE','Supplier order validated','Executed when a supplier order is validated','order_supplier',12);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_APPROVE','Supplier order request approved','Executed when a supplier order is approved','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_SUBMIT','Supplier order request submited','Executed when a supplier order is approved','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_RECEIVE','Supplier order request received','Executed when a supplier order is received','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_REFUSE','Supplier order request refused','Executed when a supplier order is refused','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_SENTBYMAIL','Supplier order sent by mail','Executed when a supplier order is sent by mail','order_supplier',14);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_CLASSIFY_BILLED','Supplier order set billed','Executed when a supplier order is set as billed','order_supplier',14);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_DELETE','Supplier order deleted','Executed when a supplier order is deleted','order_supplier',14);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_VALIDATE','Supplier invoice validated','Executed when a supplier invoice is validated','invoice_supplier',15);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_UNVALIDATE','Supplier invoice unvalidated','Executed when a supplier invoice status is set back to draft','invoice_supplier',15);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_PAYED','Supplier invoice payed','Executed when a supplier invoice is payed','invoice_supplier',16);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_SENTBYMAIL','Supplier invoice sent by mail','Executed when a supplier invoice is sent by mail','invoice_supplier',17);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_CANCELED','Supplier invoice cancelled','Executed when a supplier invoice is cancelled','invoice_supplier',17);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_DELETE','Supplier invoice deleted','Executed when a supplier invoice is deleted','invoice_supplier',17);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_VALIDATE','Contract validated','Executed when a contract is validated','contrat',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_SENTBYMAIL','Contract sent by mail','Executed when a contract is sent by mail','contrat',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_DELETE','Contract deleted','Executed when a contract is deleted','contrat',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_VALIDATE','Shipping validated','Executed when a shipping is validated','shipping',20);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_SENTBYMAIL','Shipping sent by mail','Executed when a shipping is sent by mail','shipping',21);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_DELETE','Shipping sent is deleted','Executed when a shipping is deleted','shipping',21);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECEPTION_VALIDATE','Reception validated','Executed when a reception is validated','reception',22);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECEPTION_SENTBYMAIL','Reception sent by mail','Executed when a reception is sent by mail','reception',22);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_VALIDATE','Member validated','Executed when a member is validated','member',22);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SENTBYMAIL','Mails sent from member card','Executed when you send email from member card','member',23);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SUBSCRIPTION_CREATE','Member subscribtion recorded','Executed when a member subscribtion is deleted','member',24);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SUBSCRIPTION_MODIFY','Member subscribtion modified','Executed when a member subscribtion is modified','member',24);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SUBSCRIPTION_DELETE','Member subscribtion deleted','Executed when a member subscribtion is deleted','member',24);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_RESILIATE','Member resiliated','Executed when a member is resiliated','member',25);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_DELETE','Member deleted','Executed when a member is deleted','member',26);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_VALIDATE','Intervention validated','Executed when a intervention is validated','ficheinter',30);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_CLASSIFY_BILLED','Intervention set billed','Executed when a intervention is set to billed (when option FICHINTER_CLASSIFY_BILLED is set)','ficheinter',32);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_CLASSIFY_UNBILLED','Intervention set unbilled','Executed when a intervention is set to unbilled (when option FICHINTER_CLASSIFY_BILLED is set)','ficheinter',33);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_REOPEN','Intervention opened','Executed when a intervention is re-opened','ficheinter',34);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_SENTBYMAIL','Intervention sent by mail','Executed when a intervention is sent by mail','ficheinter',35);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_DELETE','Intervention is deleted','Executed when a intervention is deleted','ficheinter',35);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_CREATE','Product or service created','Executed when a product or sevice is created','product',40);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_DELETE','Product or service deleted','Executed when a product or sevice is deleted','product',42);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_CREATE','Expense report created','Executed when an expense report is created','expensereport',201);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_VALIDATE','Expense report validated','Executed when an expense report is validated','expensereport',202);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_APPROVE','Expense report approved','Executed when an expense report is approved','expensereport',203);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_PAYED','Expense report billed','Executed when an expense report is set as billed','expensereport',204);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_DELETE','Expense report deleted','Executed when an expense report is deleted','expensereport',204);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_VALIDATE','Expense report validated','Executed when an expense report is validated','expensereport',202);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_APPROVE','Expense report approved','Executed when an expense report is approved','expensereport',203);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_VALIDATE','Project validation','Executed when a project is validated','project',141);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_DELETE','Project deleted','Executed when a project is deleted','project',143);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_CREATE','Ticket created','Executed when a ticket is created','ticket',161);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_MODIFY','Ticket modified','Executed when a ticket is modified','ticket',163);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_DELETE','Ticket deleted','Executed when a ticket is deleted','ticket',164);
-- actions not enabled by default (no constant created for that) when we enable module agenda 
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_MODIFY','Product or service modified','Executed when a product or sevice is modified','product',41);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_MODIFY','Member modified','Executed when a member is modified','member',23);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_MODIFY','Intervention modified','Executed when a intervention is modified','ficheinter',31);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_CREATE','Project creation','Executed when a project is created','project',140);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_MODIFY','Project modified','Executed when a project is modified','project',142);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TASK_CREATE','Task created','Executed when a project task is created','project',150);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TASK_MODIFY','Task modified','Executed when a project task is modified','project',151);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TASK_DELETE','Task deleted','Executed when a project task is deleted','project',152);
