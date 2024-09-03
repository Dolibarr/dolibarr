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
-- Copyright (C) 2021-2022  Anthony Berton          <anthony.berton@bb2a.fr>
-- Copyright (C) 2023       William Mead            <william.mead@manchenumerique.fr>
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
-- Do not put any comment at end of lines.
--

--
-- List of all managed triggered events (used for trigger agenda automatic events and for notification)
--
delete from llx_c_action_trigger;
-- actions enabled by default (constant created for that) when we enable module agenda
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_CREATE','Third party created','Executed when a third party is created','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_MODIFY','Third party update','Executed when you update third party','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_SENTBYMAIL','Mails sent from third party card','Executed when you send email from third party card','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_DELETE','Third party deleted','Executed when you delete third party','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_RIB_CREATE','Third party payment information created','Executed when a third party payment information is created','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_RIB_MODIFY','Third party payment information updated','Executed when a third party payment information is updated','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('COMPANY_RIB_DELETE','Third party payment information deleted','Executed when a third party payment information is deleted','societe',1);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_VALIDATE','Customer proposal validated','Executed when a commercial proposal is validated','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_MODIFY','Customer proposal modified','Executed when a customer proposal is modified','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_SENTBYMAIL','Commercial proposal sent by mail','Executed when a commercial proposal is sent by mail','propal',3);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CLOSE_SIGNED','Customer proposal closed signed','Executed when a customer proposal is closed signed','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CLOSE_REFUSED','Customer proposal closed refused','Executed when a customer proposal is closed refused','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_CLASSIFY_BILLED','Customer proposal set billed','Executed when a customer proposal is set to billed','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPAL_DELETE','Customer proposal deleted','Executed when a customer proposal is deleted','propal',2);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_VALIDATE','Customer order validate','Executed when a customer order is validated','commande',4);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_CLOSE','Customer order classify delivered','Executed when a customer order is set delivered','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_MODIFY','Customer order modified','Executed when a customer order is set modified','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_CLASSIFY_BILLED','Customer order classify billed','Executed when a customer order is set to billed','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_CANCEL','Customer order canceled','Executed when a customer order is canceled','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SENTBYMAIL','Customer order sent by mail','Executed when a customer order is sent by mail ','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_DELETE','Customer order deleted','Executed when a customer order is deleted','commande',5);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_VALIDATE','Customer invoice validated','Executed when a customer invoice is approved','facture',6);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_MODIFY','Customer invoice modified','Executed when a customer invoice is modified','facture',7);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_PAYED','Customer invoice payed','Executed when a customer invoice is payed','facture',7);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_CANCEL','Customer invoice canceled','Executed when a customer invoice is canceled','facture',8);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SENTBYMAIL','Customer invoice sent by mail','Executed when a customer invoice is sent by mail','facture',9);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_UNVALIDATE','Customer invoice unvalidated','Executed when a customer invoice status set back to draft','facture',9);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_DELETE','Customer invoice deleted','Executed when a customer invoice is deleted','facture',9);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_VALIDATE','Price request validated','Executed when a commercial proposal is validated','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_MODIFY','Price request modified','Executed when a commercial proposal is modified','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_SENTBYMAIL','Price request sent by mail','Executed when a commercial proposal is sent by mail','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_CLOSE_SIGNED','Price request closed signed','Executed when a customer proposal is closed signed','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_CLOSE_REFUSED','Price request closed refused','Executed when a customer proposal is closed refused','proposal_supplier',10);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROPOSAL_SUPPLIER_DELETE','Price request deleted','Executed when a customer proposal delete','proposal_supplier',10);
--insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_CREATE','Supplier order created','Executed when a supplier order is created','order_supplier',11);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_VALIDATE','Supplier order validated','Executed when a supplier order is validated','order_supplier',12);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_APPROVE','Supplier order request approved','Executed when a supplier order is approved','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_MODIFY','Supplier order request modified','Executed when a supplier order is modified','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_SUBMIT','Supplier order request submited','Executed when a supplier order is approved','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_RECEIVE','Supplier order request received','Executed when a supplier order is received','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_REFUSE','Supplier order request refused','Executed when a supplier order is refused','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_CANCEL','Supplier order request canceled','Executed when a supplier order is canceled','order_supplier',13);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_SENTBYMAIL','Supplier order sent by mail','Executed when a supplier order is sent by mail','order_supplier',14);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_CLASSIFY_BILLED','Supplier order set billed','Executed when a supplier order is set as billed','order_supplier',14);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ORDER_SUPPLIER_DELETE','Supplier order deleted','Executed when a supplier order is deleted','order_supplier',14);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_VALIDATE','Supplier invoice validated','Executed when a supplier invoice is validated','invoice_supplier',15);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_MODIFY','Supplier invoice modified','Executed when a supplier invoice is modified','invoice_supplier',15);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_UNVALIDATE','Supplier invoice unvalidated','Executed when a supplier invoice status is set back to draft','invoice_supplier',15);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_PAYED','Supplier invoice payed','Executed when a supplier invoice is payed','invoice_supplier',16);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_SENTBYMAIL','Supplier invoice sent by mail','Executed when a supplier invoice is sent by mail','invoice_supplier',17);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_CANCELED','Supplier invoice cancelled','Executed when a supplier invoice is cancelled','invoice_supplier',17);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILL_SUPPLIER_DELETE','Supplier invoice deleted','Executed when a supplier invoice is deleted','invoice_supplier',17);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_VALIDATE','Contract validated','Executed when a contract is validated','contrat',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_MODIFY','Contract modified','Executed when a contract is modified','contrat',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_SENTBYMAIL','Contract sent by mail','Executed when a contract is sent by mail','contrat',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTRACT_DELETE','Contract deleted','Executed when a contract is deleted','contrat',18);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_VALIDATE','Shipping validated','Executed when a shipping is validated','shipping',20);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_MODIFY','Shipping modified','Executed when a shipping is modified','shipping',20);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_SENTBYMAIL','Shipping sent by mail','Executed when a shipping is sent by mail','shipping',21);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('SHIPPING_DELETE','Shipping sent is deleted','Executed when a shipping is deleted','shipping',21);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECEPTION_VALIDATE','Reception validated','Executed when a reception is validated','reception',22);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECEPTION_SENTBYMAIL','Reception sent by mail','Executed when a reception is sent by mail','reception',22);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_VALIDATE','Member validated','Executed when a member is validated','member',22);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_MODIFY','Member modified','Executed when a member is modified','member',23);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SENTBYMAIL','Mails sent from member card','Executed when you send email from member card','member',23);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SUBSCRIPTION_CREATE','Member subscribtion recorded','Executed when a member subscribtion is deleted','member',24);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SUBSCRIPTION_MODIFY','Member subscribtion modified','Executed when a member subscribtion is modified','member',24);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_SUBSCRIPTION_DELETE','Member subscribtion deleted','Executed when a member subscribtion is deleted','member',24);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_RESILIATE','Member resiliated','Executed when a member is resiliated','member',25);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_DELETE','Member deleted','Executed when a member is deleted','member',26);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MEMBER_EXCLUDE','Member excluded','Executed when a member is excluded','member',27);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_VALIDATE','Intervention validated','Executed when a intervention is validated','ficheinter',30);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_MODIFY','Intervention modify','Executed when a intervention is modify','ficheinter',30);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_CLASSIFY_BILLED','Intervention set billed','Executed when a intervention is set to billed (when option FICHINTER_CLASSIFY_BILLED is set)','ficheinter',32);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_CLASSIFY_UNBILLED','Intervention set unbilled','Executed when a intervention is set to unbilled (when option FICHINTER_CLASSIFY_BILLED is set)','ficheinter',33);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_REOPEN','Intervention opened','Executed when a intervention is re-opened','ficheinter',34);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_SENTBYMAIL','Intervention sent by mail','Executed when a intervention is sent by mail','ficheinter',35);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_DELETE','Intervention is deleted','Executed when a intervention is deleted','ficheinter',35);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_CLOSE','Intervention is done','Executed when a intervention is done','ficheinter',36);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_CREATE','Product or service created','Executed when a product or sevice is created','product',40);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_MODIFY','Product or service modified','Executed when a product or sevice is modified','product',41);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PRODUCT_DELETE','Product or service deleted','Executed when a product or sevice is deleted','product',42);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_CREATE','Expense report created','Executed when an expense report is created','expensereport',201);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_VALIDATE','Expense report validated','Executed when an expense report is validated','expensereport',202);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_MODIFY','Expense report modified','Executed when an expense report is modified','expensereport',202);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_APPROVE','Expense report approved','Executed when an expense report is approved','expensereport',203);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_PAID','Expense report billed','Executed when an expense report is set as billed','expensereport',204);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('EXPENSE_REPORT_DELETE','Expense report deleted','Executed when an expense report is deleted','expensereport',205);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_CREATE','Project creation','Executed when a project is created','project',140);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_VALIDATE','Project validation','Executed when a project is validated','project',141);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_MODIFY','Project modified','Executed when a project is modified','project',142);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_DELETE','Project deleted','Executed when a project is deleted','project',143);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_SENTBYMAIL','Project sent by mail','Executed when a project is sent by email','project',144);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PROJECT_CLOSE','Project closed','Executed when a project is closed','project',145);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_CREATE','Ticket created','Executed when a ticket is created','ticket',161);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_MODIFY','Ticket modified','Executed when a ticket is modified','ticket',163);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_ASSIGNED','Ticket assigned','Executed when a ticket is modified','ticket',164);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_CLOSE','Ticket closed','Executed when a ticket is closed','ticket',165);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_SENTBYMAIL','Ticket message sent by email','Executed when a message is sent from the ticket record','ticket',166);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TICKET_DELETE','Ticket deleted','Executed when a ticket is deleted','ticket',167);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('USER_SENTBYMAIL','Email sent','Executed when an email is sent from user card','user',300);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('USER_CREATE','User created','Executed when a user is created','user',301);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('USER_MODIFY','User update','Executed when a user is updated','user',302);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('USER_DELETE','User update','Executed when a user is deleted','user',303);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('USER_NEW_PASSWORD','User update','Executed when a user is change password','user',304);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('USER_ENABLEDISABLE','User update','Executed when a user is enable or disable','user',305);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('FICHINTER_MODIFY','Intervention modified','Executed when a intervention is modified','ficheinter',19);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_VALIDATE','BOM validated','Executed when a BOM is validated','bom',650);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_UNVALIDATE','BOM unvalidated','Executed when a BOM is unvalidated','bom',651);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_CLOSE','BOM disabled','Executed when a BOM is disabled','bom',652);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_REOPEN','BOM reopen','Executed when a BOM is re-open','bom',653);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BOM_DELETE','BOM deleted','Executed when a BOM deleted','bom',654);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MRP_MO_VALIDATE','MO validated','Executed when a MO is validated','mrp',660);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MRP_MO_PRODUCED','MO produced','Executed when a MO is produced','mrp',661);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MRP_MO_DELETE','MO deleted','Executed when a MO is deleted','mrp',662);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MRP_MO_CANCEL','MO canceled','Executed when a MO is canceled','mrp',663);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_CREATE','Contact address created','Executed when a contact is created','contact',50);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_MODIFY','Contact address update','Executed when a contact is updated','contact',51);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_SENTBYMAIL','Mails sent from third party card','Executed when you send email from contact address record','contact',52);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('CONTACT_DELETE','Contact address deleted','Executed when a contact is deleted','contact',53);

-- recruitment module
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTJOBPOSITION_CREATE','Job created','Executed when a job is created','recruitment',7500);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTJOBPOSITION_MODIFY','Job modified','Executed when a job is modified','recruitment',7502);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTJOBPOSITION_SENTBYMAIL','Mails sent from job record','Executed when you send email from job record','recruitment',7504);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTJOBPOSITION_DELETE','Job deleted','Executed when a job is deleted','recruitment',7506);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTCANDIDATURE_CREATE','Candidature created','Executed when a candidature is created','recruitment',7510);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTCANDIDATURE_MODIFY','Candidature modified','Executed when a candidature is modified','recruitment',7512);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTCANDIDATURE_SENTBYMAIL','Mails sent from candidature record','Executed when you send email from candidature record','recruitment',7514);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECRUITMENTCANDIDATURE_DELETE','Candidature deleted','Executed when a candidature is deleted','recruitment',7516);

-- actions not enabled by default : they are excluded when we enable the module Agenda (except TASK_...)
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TASK_CREATE','Task created','Executed when a project task is created','project',150);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TASK_MODIFY','Task modified','Executed when a project task is modified','project',151);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('TASK_DELETE','Task deleted','Executed when a project task is deleted','project',152);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('ACTION_CREATE','Action added','Executed when an action is added to the agenda','agenda',700);

-- holiday
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_CREATE','Holiday created','Executed when a holiday is created','holiday',800);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_MODIFY','Holiday modified','Executed when a holiday is modified','holiday',801);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_VALIDATE','Holiday validated','Executed when a holiday is validated','holiday',802);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_APPROVE','Holiday approved','Executed when a holiday is aprouved','holiday',803);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_CANCEL','Holiday canceled','Executed when a holiday is canceled','holiday',802);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HOLIDAY_DELETE','Holiday deleted','Executed when a holiday is deleted','holiday',804);

-- hrm
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HRM_EVALUATION_CREATE', 'HR Evaluation created', 'Executed when an evaluation is created', 'hrm', 4000);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HRM_EVALUATION_MODIFY', 'HR Evaluation modified', 'Executed when an evaluation is modified', 'hrm', 4001);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HRM_EVALUATION_VALIDATE', 'HR Evaluation validated', 'Executed when an evaluation is validated', 'hrm', 4002);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HRM_EVALUATION_UNVALIDATE', 'HR Evaluation back to draft', 'Executed when an evaluation is back to draft', 'hrm', 4003);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('HRM_EVALUATION_DELETE', 'HR Evaluation deleted', 'Executed when an evaluation is dleted', 'hrm', 4005);

-- facture rec
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILLREC_CREATE','Template invoices created','Executed when a Template invoices is created','facturerec',900);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILLREC_MODIFY','Template invoices update','Executed when a Template invoices is updated','facturerec',901);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('BILLREC_DELETE','Template invoices deleted','Executed when a Template invoices is deleted','facturerec',902);

-- partnership module
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PARTNERSHIP_CREATE','Partnership created','Executed when a partnership is created','partnership',58000);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PARTNERSHIP_MODIFY','Partnership modified','Executed when a partnership is modified','partnership',58002);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PARTNERSHIP_SENTBYMAIL','Mails sent from partnership file','Executed when you send email from partnership file','partnership',58004);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('PARTNERSHIP_DELETE','Partnership deleted','Executed when a partnership is deleted','partnership',58006);

-- knowledgemanagement module
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('KNOWLEDGERECORD_CREATE','Knowledgerecord created','Executed when a knowledgerecord is created','knowledgemanagement',57001);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('KNOWLEDGERECORD_MODIFY','Knowledgerecord modified','Executed when a knowledgerecord is modified','knowledgemanagement',57002);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('KNOWLEDGERECORD_VALIDATE','Knowledgerecord Evaluation validated','Executed when an evaluation is validated','knowledgemanagement',57004);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('KNOWLEDGERECORD_REOPEN','Knowledgerecord reopen','Executed when an evaluation is back to draft','knowledgemanagement',57004);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('KNOWLEDGERECORD_UNVALIDATE','Knowledgerecord unvalidated','Executed when an evaluation is back to draft','knowledgemanagement',57004);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('KNOWLEDGERECORD_CANCEL','Knowledgerecord cancel','Executed when an evaluation to cancel','knowledgemanagement',57004);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('KNOWLEDGERECORD_SENTBYMAIL','Mails sent from knowledgerecord file','knowledgerecord when you send email from knowledgerecord file','knowledgemanagement',57004);
insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('KNOWLEDGERECORD_DELETE','Knowledgerecord deleted','Executed when a knowledgerecord is deleted','knowledgemanagement',57006);
