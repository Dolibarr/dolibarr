-- ===================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- Table with templates of emails
-- ===================================================================

create table llx_c_email_templates
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  entity		  integer DEFAULT 1 NOT NULL,	  -- multi company id
  type_template   varchar(32),  -- template for wich type of email (send invoice by email, send order, ...)
  datec           datetime,
  label           varchar(255),
  content         text
)ENGINE=innodb;
