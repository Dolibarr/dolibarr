-- ============================================================================
-- Copyright (C) 2005      Brice Davoleau    <e1davole@iu-vannes.fr>
-- Copyright (C) 2005      Matthieu Valleton <mv@seeschloss.org>
-- Copyright (C) 2005-2008 Regis Houssin     <regis@dolibarr.fr>		
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
-- ============================================================================

create table llx_categorie
(
	rowid 		    integer AUTO_INCREMENT PRIMARY KEY,
	label 		    VARCHAR(255),                       -- category name
	description 	text,                               -- description of the category
	fk_soc        integer DEFAULT 0,						  		-- attribution of the category has a company (for product only)
	visible       tinyint DEFAULT 1 NOT NULL,         -- determine if the products are visible or not
	type	        tinyint DEFAULT 1 NOT NULL          -- category type (product, supplier, customer)
)type=innodb;

-- 
-- List of codes for the field type
--
-- 0 : product
-- 1 : supplier
-- 2 : customer
--