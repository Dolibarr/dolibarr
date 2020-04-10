-- Copyright (C) 2016	Laurent Destailleur	<eldy@users.sourceforge.net>
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

ALTER TABLE llx_societe_log
  ADD PRIMARY KEY (id),
  ADD KEY fk_soc (fk_soc),
  ADD KEY fk_statut (fk_statut),
  ADD KEY fk_user (fk_user);

ALTER TABLE llx_societe_log
  ADD CONSTRAINT llx_societe_log_fk_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid),
  ADD CONSTRAINT llx_societe_log_fk_user FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);
