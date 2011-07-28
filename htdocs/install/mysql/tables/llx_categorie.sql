-- ============================================================================
-- Copyright (C) 2005      Brice Davoleau    <e1davole@iu-vannes.fr>
-- Copyright (C) 2005      Matthieu Valleton <mv@seeschloss.org>
-- Copyright (C) 2005-2009 Regis Houssin     <regis@dolibarr.fr>		
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
-- $Id: llx_categorie.sql,v 1.4 2011/08/03 01:25:33 eldy Exp $
-- ============================================================================

create table llx_categorie
(
	rowid 		    integer AUTO_INCREMENT PRIMARY KEY,
	label 		    varchar(255),                       -- category name
	type	        tinyint DEFAULT 1 NOT NULL,         -- category type (product, supplier, customer)
	entity          integer DEFAULT 1 NOT NULL,	        -- multi company id
	description 	text,                               -- description of the category
	fk_soc          integer DEFAULT NULL,					-- attribution of the category has a company (for product only)
	visible         tinyint DEFAULT 1 NOT NULL,           -- determine if the products are visible or not
    import_key      varchar(14)                  -- Import key
)ENGINE=innodb;

-- 
-- List of codes for the field type
--
-- 0 : product
-- 1 : supplier
-- 2 : customer
--

--
-- List of codes for the field entity
--
-- 1 : first company category type
-- 2 : second company category type
-- 3 : etc...
--