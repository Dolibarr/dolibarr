-- Copyright (C) 2009-2018 Regis Houssin  <regis.houssin@inodbox.com>
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
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--


--
-- llx_c_payment_term
--

insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values ('RECEP',       1,1, 'Due upon receipt','Due upon receipt',0,1,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values ('30D',         2,1, '30 days','Due in 30 days',0,30,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values ('30DENDMONTH', 3,1, '30 days end of month','Due in 30 days, end of month',1,30,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values ('60D',         4,1, '60 days','Due in 60 days, end of month',0,60,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values ('60DENDMONTH', 5,1, '60 days end of month','Due in 60 days, end of month',1,60,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values ('PT_ORDER',    6,1, 'Due on order','Due on order',0,1,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values ('PT_DELIVERY', 7,1, 'Due on delivery','Due on delivery',0,1,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values ('PT_5050',     8,1, '50 and 50','50% on order, 50% on delivery',0,1,__ENTITY__);

-- Add additional payment terms often needed in Austria
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values ('10D',         9,1,  '10 days','Due in 10 days',0,10,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values ('10DENDMONTH', 10,1, '10 days end of month','Due in 10 days, end of month',1,10,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values ('14D',         11,1, '14 days','Due in 14 days',0,14,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values ('14DENDMONTH', 12,1, '14 days end of month','Due in 14 days, end of month',1,14,__ENTITY__);
