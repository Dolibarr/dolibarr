-- ============================================================================
-- Copyright (C) 2005		Brice Davoleau		<e1davole@iu-vannes.fr>
-- Copyright (C) 2005		Matthieu Valleton	<mv@seeschloss.org>
-- Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>		
-- Copyright (C) 2017       Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ============================================================================

create table llx_categorie
(
	rowid 		    integer AUTO_INCREMENT PRIMARY KEY,
	entity          integer DEFAULT 1 NOT NULL,			-- multi company id
	fk_parent		integer DEFAULT 0 NOT NULL,
	label 		    varchar(180) NOT NULL,				-- category name
	type	        tinyint DEFAULT 1 NOT NULL,			-- category type (product, supplier, customer, member)
	description 	text,								-- description of the category
    color           varchar(8),                         -- color
	fk_soc          integer DEFAULT NULL,				-- not used by default. Used when option CATEGORY_ASSIGNED_TO_A_CUSTOMER is set.
	visible         tinyint DEFAULT 1 NOT NULL,			-- determine if the products are visible or not
    import_key      varchar(14)							-- Import key
)ENGINE=innodb;

-- 
-- List of codes for the field type
--
-- 0 : product
-- 1 : supplier
-- 2 : customer
--
