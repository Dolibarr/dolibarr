-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2021 	   Udo Tamm             <dev@dolibit.de>
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
-- Notes
--
-- Do not place a comment at the end of the line, this file is parsed when
-- of the install and all the acronyms '-' are removed.
--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- The types of contact of an element
--
-- The unique key is set on (element, source, code)
--

-- Contract / Contrat
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('contrat', 'internal', 'SALESREPFOLL',  'Contract manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('contrat', 'external', 'CUSTOMER',      'Customer contract manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('contrat', 'external', 'BILLING',       'Customer billing manager', 1);

insert into llx_c_type_contact (element, source, code, libelle, active ) values ('contrat', 'internal', 'SALESREPSIGN',  'Contract signatory', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('contrat', 'external', 'SALESREPSIGN',  'Contract signatory customer', 1);

-- Proposal / Propal
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('propal',  'internal', 'SALESREPFOLL',  'Pricing and quotation manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('propal',  'external', 'CUSTOMER',      'Customer pricing and quotation manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('propal',  'external', 'BILLING',       'Customer billing manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('propal',  'external', 'SHIPPING',      'Customer shipping manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('propal',  'external', 'SERVICE',       'Customer contact performing the service', 0);

-- Customer Invoice / Facture
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('facture', 'internal', 'SALESREPFOLL',  'Billing manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('facture', 'external', 'BILLING',       'Customer billing manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('facture', 'external', 'SHIPPING',      'Customer shipping manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('facture', 'external', 'SERVICE',       'Customer contact performing the service', 0);

-- Supplier Invoice
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('invoice_supplier', 'internal', 'SALESREPFOLL',  'Billing manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('invoice_supplier', 'external', 'BILLING',       'Supplier billing manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('invoice_supplier', 'external', 'SHIPPING',      'Supplier shipping manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('invoice_supplier', 'external', 'SERVICE',       'Supplier contact performing the service', 0);

-- Agenda
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('agenda', 'internal', 'ACTOR', 'Owner', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('agenda', 'internal', 'GUEST', 'Guest', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('agenda', 'external', 'ACTOR', 'Owner', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('agenda', 'external', 'GUEST', 'Guest', 1);

-- Customer Order / Commande
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('commande', 'internal', 'SALESREPFOLL',  'Order fulfillment manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('commande', 'external', 'CUSTOMER',      'Customer order fulfillment manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('commande', 'external', 'BILLING',       'Customer billing manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('commande', 'external', 'SHIPPING',      'Customer shipping manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('commande', 'external', 'SERVICE',       'Customer contact performing the service', 0);

-- Shipment / Expedition
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('shipping', 'internal', 'SALESREPFOLL',  'Shipping operations manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('shipping', 'external', 'CUSTOMER',      'Customer shipping operations manager', 1);
INSERT INTO llx_c_type_contact (element, source, code, libelle, active ) VALUES ('shipping', 'external', 'BILLING',       'Customer invoice contact', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('shipping', 'external', 'SHIPPING',      'Customer loading contact', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('shipping', 'external', 'DELIVERY',      'Delivery facility', 0);

-- Intervention / Fichinter
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('fichinter', 'internal', 'INTERREPFOLL',  'Intervention manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('fichinter', 'internal', 'INTERVENING',   'Intervenant', 0);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('fichinter', 'external', 'BILLING',       'Customer billing manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('fichinter', 'external', 'CUSTOMER',      'Customer intervention manager', 1);

-- Supplier Order
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('order_supplier', 'internal', 'SALESREPFOLL',  'Purchase order fulfillment manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('order_supplier', 'internal', 'SHIPPING',      'Purchase order shipping manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('order_supplier', 'external', 'CUSTOMER',      'Purchase order fulfillment manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('order_supplier', 'external', 'BILLING',       'Supplier billing manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('order_supplier', 'external', 'SHIPPING',      'Supplier shipping manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('order_supplier', 'external', 'SERVICE',       'Supplier contact performing the service', 0);
-- Resource
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('dolresource', 'internal', 'USERINCHARGE',    'In charge of resource', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('dolresource', 'external', 'THIRDINCHARGE',   'In charge of resource', 1);

-- Tickets
insert into llx_c_type_contact (element, source, code, libelle, active, module) values ('ticket', 'internal', 'SUPPORTTEC',  'Ticket manager', 1, NULL);
insert into llx_c_type_contact (element, source, code, libelle, active, module) values ('ticket', 'internal', 'CONTRIBUTOR', 'Intervenant', 1, NULL);
insert into llx_c_type_contact (element, source, code, libelle, active, module) values ('ticket', 'external', 'SUPPORTCLI',  'Customer ticket manager', 1, NULL);
insert into llx_c_type_contact (element, source, code, libelle, active, module) values ('ticket', 'external', 'CONTRIBUTOR', 'Intervenant', 1, NULL);

-- Product / Service
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('product', 'internal', 'SALESREPFOLL',  'Product manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('product', 'internal', 'BILLING',       'Billing manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('product', 'external', 'CUSTOMER',      'Thirdparty product contact', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('product', 'external', 'SHIPPING',      'Shipping manager', 1);

-- Projects / Projet - All project code can start with 'PROJECT'
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('project', 'internal', 'PROJECTLEADER',      'Project leader', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('project', 'internal', 'PROJECTCONTRIBUTOR', 'Intervenant', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('project', 'external', 'PROJECTLEADER',      'Project leader', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('project', 'external', 'PROJECTCONTRIBUTOR', 'Intervenant', 1);

-- Project Tasks - All task code can start with 'TASK'
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('project_task', 'internal', 'TASKEXECUTIVE',   'Responsable', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('project_task', 'internal', 'TASKCONTRIBUTOR', 'Intervenant', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('project_task', 'external', 'TASKEXECUTIVE',   'Responsable', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('project_task', 'external', 'TASKCONTRIBUTOR', 'Intervenant', 1);

-- Supplier proposal
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('supplier_proposal', 'internal', 'SALESREPFOLL',  'Pricing and Quotation manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('supplier_proposal', 'external', 'BILLING',       'Supplier billing manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('supplier_proposal', 'external', 'CUSTOMER',      'Pricing and quotation manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('supplier_proposal', 'external', 'SHIPPING',      'Supplier shipping manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('supplier_proposal', 'external', 'SERVICE',       'Supplier contact performing the service', 0);

-- Event Organization
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('conferenceorbooth', 'internal', 'MANAGER',      'Conference or Booth manager', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('conferenceorbooth', 'external', 'SPEAKER',      'Conference Speaker', 1);
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('conferenceorbooth', 'external', 'RESPONSIBLE',  'Booth responsible', 1);

-- Thirdparty
insert into llx_c_type_contact (element, source, code, libelle, active ) values ('societe', 'external', 'SALESREPTHIRD',  'Sales Representative', 1);
