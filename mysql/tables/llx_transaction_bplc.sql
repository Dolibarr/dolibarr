-- ===================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ===================================================================

create table llx_transaction_bplc
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  tms               timestamp,
  ipclient          varchar(20),
  num_transaction   varchar(10), 
  date_transaction  varchar(10), 
  heure_transaction varchar(10), 
  num_autorisation  varchar(10),
  cle_acceptation   varchar(5),
  code_retour       integer,
  ref_commande      integer

)type=innodb;
