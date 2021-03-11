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
-- llx_c_paiement
--
insert into llx_c_paiement (code,libelle,type,active,entity) values ('TIP', 'TIP',				2,0,__ENTITY__);
insert into llx_c_paiement (code,libelle,type,active,entity) values ('VIR', 'Transfer',		2,1,__ENTITY__);
insert into llx_c_paiement (code,libelle,type,active,entity) values ('PRE', 'Debit order',		2,1,__ENTITY__);
insert into llx_c_paiement (code,libelle,type,active,entity) values ('LIQ', 'Cash',			2,1,__ENTITY__);
insert into llx_c_paiement (code,libelle,type,active,entity) values ('CB',  'Credit card',		2,1,__ENTITY__);
insert into llx_c_paiement (code,libelle,type,active,entity) values ('CHQ', 'Cheque',			2,1,__ENTITY__);
insert into llx_c_paiement (code,libelle,type,active,entity) values ('VAD', 'Online payment',	2,0,__ENTITY__);
insert into llx_c_paiement (code,libelle,type,active,entity) values ('TRA', 'Traite',			2,0,__ENTITY__);
insert into llx_c_paiement (code,libelle,type,active,entity) values ('LCR', 'LCR',				2,0,__ENTITY__);
insert into llx_c_paiement (code,libelle,type,active,entity) values ('FAC', 'Factor',			2,0,__ENTITY__);
