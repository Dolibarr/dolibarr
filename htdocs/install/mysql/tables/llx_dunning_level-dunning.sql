-- Copyright (C) 2026		Maxime DION				<contact@guiltek.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.
--
-- Levels of the reminders of the module Reminders (dunning)


CREATE TABLE llx_dunning_level(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity integer DEFAULT 1 NOT NULL,
	element varchar(32) DEFAULT 'facture' NOT NULL,	-- 'facture' only for now, the reference date follows the element
	label varchar(128) NOT NULL,
	nbdays integer NOT NULL,						-- days after the due date, negative = before
	fk_c_type_contact integer DEFAULT 0 NOT NULL,	-- external contact type of the element (llx_c_type_contact), 0 = email of the thirdparty
	percustomer tinyint DEFAULT 0 NOT NULL,			-- 1 = one email per customer with all its invoices due at this level
	byhand tinyint DEFAULT 0 NOT NULL,				-- 1 = never sent by the job: the reminder waits to be checked and sent by a user
	fk_email_template integer NOT NULL,				-- llx_c_email_templates (type facture_send), its label finds the version in the customer language
	active tinyint DEFAULT 1 NOT NULL
) ENGINE=innodb;
