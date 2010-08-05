-- ===================================================================
-- Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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

create table llx_extra_fields
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  entity                integer  DEFAULT 1 NOT NULL,	-- multi company id
  
  object 				varchar(64) NOT NULL,           -- type of link 'invoice', 'order', 'proposal', 'supplier_invoice', 'supplier_order'
  name 					varchar(64) NOT NULL,           -- code name of field
  label					varchar(64) NOT NULL,
  format				varchar(8) 	NOT NULL,           -- date, string, integer, float
  fieldsize 			integer,
  maxlength 			integer,
  options 				varchar(255),
  rank 					integer,
  
  assign                integer         -- ???
)type=innodb;
