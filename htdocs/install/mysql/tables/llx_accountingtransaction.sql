-- ============================================================================
-- Copyright (C) 2004-2006 Laurent Destailleur <eldy@users.sourceforge.net>
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
-- $Id: llx_accountingtransaction.sql,v 1.4 2011/08/03 01:25:25 eldy Exp $
-- Table of "transactions" for accountancy expert module (1 transaction = 2+ records)
-- ============================================================================

create table llx_accountingtransaction
(
	rowid           integer 		AUTO_INCREMENT PRIMARY KEY,
	label           varchar(128)    NOT NULL,
	datec           date 			NOT NULL,
	fk_author       varchar(20)		NOT NULL,
	tms             timestamp,
    fk_source       integer NOT NULL,
    sourcetype      varchar(16) NOT NULL,
    url             varchar(255)
)ENGINE=innodb;
