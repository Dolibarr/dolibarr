-- ===================================================================
-- Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2008-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
-- ===================================================================


ALTER TABLE llx_expensereport ADD UNIQUE INDEX idx_expensereport_uk_ref (ref, entity);

ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_date_debut (date_debut);
ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_date_fin (date_fin);
ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_fk_statut (fk_statut);

ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_fk_user_author (fk_user_author);
ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_fk_user_valid (fk_user_valid);
ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_fk_user_approve (fk_user_approve);
ALTER TABLE llx_expensereport ADD INDEX idx_expensereport_fk_refuse (fk_user_approve);

--ALTER TABLE llx_expensereport ADD CONSTRAINT fk_expensereport_fk_user_author		FOREIGN KEY (fk_user_author)	 REFERENCES llx_user (rowid);
--ALTER TABLE llx_expensereport ADD CONSTRAINT fk_expensereport_fk_user_valid 		FOREIGN KEY (fk_user_valid)		 REFERENCES llx_user (rowid);
--ALTER TABLE llx_expensereport ADD CONSTRAINT fk_expensereport_fk_user_approve 		FOREIGN KEY (fk_user_approve)	 REFERENCES llx_user (rowid);
--ALTER TABLE llx_expensereport ADD CONSTRAINT fk_expensereport_fk_refuse				FOREIGN KEY (fk_user_refuse)     REFERENCES llx_user (rowid);

