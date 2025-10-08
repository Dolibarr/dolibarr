-- ===================================================================
-- Copyright (C) 2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2008	Regis Houssin			<regis.houssin@inodbox.com>
-- Copyright (C) 2011	Laurent Destailleur		<eldy@users.sourceforge.net>
-- Copyright (C) 2025	Nick Fragoulis
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
-- ===================================================================


create table llx_receptiondet
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_reception     	integer NOT NULL,  						    		-- ID of parent object
  fk_element        integer,           						    		-- ID of main source object
  fk_elementdet     integer,           						    		-- ID of line of source object (Vendor proposals, Purchase order)
  element_type   	varchar(50) DEFAULT 'supplier_order' NOT NULL,		-- Type of source object ('supplier_order', ...)
  fk_product        integer,  								    		-- ID of product. If empty, you can retreive it using fk_element/element_type link, but it may be empty too if line is a non predefined product line.
  fk_parent         integer,                                    		-- ID of parent line. For a hierarchy of lines.
  qty               real,              						    		-- Quantity
  fk_unit           integer, 				                    		-- ID of unit code
  fk_entrepot       integer,           						    		-- Warehouse for reception of product
  description		text,												-- Product description/label of non origin
  rang              integer  DEFAULT 0,									-- Position of line
  extraparams		varchar(255)				 						-- To save other parameters in json format
)ENGINE=innodb;
