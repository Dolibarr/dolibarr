-- ========================================================================
-- Copyright (C) 2023 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ========================================================================

CREATE TABLE llx_c_invoice_subtype 
(
	rowid		integer AUTO_INCREMENT PRIMARY KEY,
	entity		integer DEFAULT 1 NOT NULL,	-- multi company id
	fk_country	integer NOT NULL,
	code		varchar(4) NOT NULL,
	label		varchar(100),
	active		tinyint DEFAULT 1 NOT NULL

) ENGINE=innodb;

