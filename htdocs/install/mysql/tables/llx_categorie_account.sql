-- ============================================================================
-- Copyright (C) 2016       Charlie Benke       <charlie@patas-monkey.com>
-- Copyright (C) 2016       Frédéric France     <frederic.france@free.fr>
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
-- Table to link bank tag/categories with bank accounts
-- ===========================================================================

CREATE TABLE llx_categorie_account
(
  fk_categorie  integer NOT NULL,
  fk_account    integer NOT NULL,
  import_key    varchar(14)
) ENGINE=innodb;
