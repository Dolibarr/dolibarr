-- ===================================================================
-- Copyright (C) 2022      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===================================================================


ALTER TABLE llx_don ADD UNIQUE INDEX idx_don_uk_ref (ref, entity);

ALTER TABLE llx_don ADD INDEX idx_don_fk_soc (fk_soc);
ALTER TABLE llx_don ADD INDEX idx_don_fk_project (fk_projet);
ALTER TABLE llx_don ADD INDEX idx_don_fk_user_author (fk_user_author);
ALTER TABLE llx_don ADD INDEX idx_don_fk_user_valid (fk_user_valid);

--ALTER TABLE llx_don ADD CONSTRAINT fk_don_fk_soc			FOREIGN KEY (fk_soc)			REFERENCES llx_societe (rowid);
--ALTER TABLE llx_don ADD CONSTRAINT fk_don_fk_user_author	FOREIGN KEY (fk_user_author)	REFERENCES llx_user (rowid);
--ALTER TABLE llx_don ADD CONSTRAINT fk_don_fk_user_valid	    FOREIGN KEY (fk_user_valid)	    REFERENCES llx_user (rowid);
