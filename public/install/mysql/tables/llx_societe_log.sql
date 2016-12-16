-- ========================================================================
-- Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2007      Laurent Destailleur  <eldy@users.sourceforge.net>
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

create table llx_societe_log
(
  id          integer AUTO_INCREMENT PRIMARY KEY,
  datel       datetime,
  fk_soc      integer,			-- Ne pas mettre de controle d'integrite sur les tables de logs
  fk_statut   integer,			-- Ne pas mettre de controle d'integrite sur les tables de logs
  fk_user     integer,			-- Ne pas mettre de controle d'integrite sur les tables de logs
  author      varchar(30),
  label       varchar(128)
)ENGINE=innodb;
