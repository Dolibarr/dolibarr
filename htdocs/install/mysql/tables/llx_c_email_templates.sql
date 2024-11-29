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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- Table with templates of emails
-- ===================================================================

create table llx_c_email_templates
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  entity		  integer DEFAULT 1 NOT NULL,	  -- multi company id
  module          varchar(32),                    -- Nom du module en rapport avec le modele
  type_template   varchar(32),  				  -- template for which type of email (send invoice by email, send order, ...)
  lang            varchar(6) DEFAULT '',		  -- We use a default to '' so the unique index that include this field will work
  private         smallint DEFAULT 0 NOT NULL,    -- Template public or private
  fk_user         integer,                        -- Id user owner if template is private, or null
  datec           datetime,
  tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  label           varchar(180),					  -- Label of predefined email
  position        smallint,					      -- Position
  defaultfortype  smallint DEFAULT 0,			  -- 1=Use this template by default when creating a new email for this type
  enabled         varchar(255) DEFAULT '1',		  -- Condition to have this module visible
  active          tinyint DEFAULT 1  NOT NULL,
  email_from	  varchar(255),					  -- default email from
  email_to		  varchar(255),					  -- default email to
  email_tocc	  varchar(255),					  -- default email to cc
  email_tobcc	  varchar(255),					  -- default email to bcc
  topic			  text,                           -- Predefined topic
  joinfiles		  text,                           -- Files to attach
  content         mediumtext,                     -- Predefined text
  content_lines   text                            -- Predefined text to use to generate the string concatenated with all lines
)ENGINE=innodb;
