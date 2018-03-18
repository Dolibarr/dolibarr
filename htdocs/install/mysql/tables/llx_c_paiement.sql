-- ========================================================================
-- Copyright (C) 2001-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
-- Copyright (C) 2004-2014	Laurent Destailleur		<eldy@users.sourceforge.net>
-- Copyright (C) 2014		Alexandre Spangaro		<aspangaro.dolibarr@gmail.com>
-- Copyright (C) 2017		Regis Houssin			<regis.houssin@capnetworks.com>
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
-- ========================================================================

create table llx_c_paiement
(
  id					integer AUTO_INCREMENT PRIMARY KEY,
  entity				integer	DEFAULT 1 NOT NULL,	-- multi company id
  code       		varchar(6)  NOT NULL,
  libelle    		varchar(62),
  type       		smallint,	-- 0: input money, 1: output money, 2: input and output, 3: other
  active     		tinyint DEFAULT 1  NOT NULL,
  accountancy_code	varchar(32) NULL,
  module     		varchar(32) NULL,
  position			integer NOT NULL DEFAULT 0
)ENGINE=innodb;
