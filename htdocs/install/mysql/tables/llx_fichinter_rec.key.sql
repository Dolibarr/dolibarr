-- ============================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2018      Charlene Benke        <charlie@patas-monkey.com>
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
-- ============================================================================


ALTER TABLE llx_fichinter_rec ADD UNIQUE INDEX idx_fichinter_rec_uk_titre (titre, entity);

ALTER TABLE llx_fichinter_rec ADD INDEX idx_fichinter_rec_fk_soc (fk_soc);
ALTER TABLE llx_fichinter_rec ADD INDEX idx_fichinter_rec_fk_user_author (fk_user_author);
ALTER TABLE llx_fichinter_rec ADD INDEX idx_fichinter_rec_fk_projet (fk_projet);

ALTER TABLE llx_fichinter_rec ADD CONSTRAINT fk_fichinter_rec_fk_user_author    FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
ALTER TABLE llx_fichinter_rec ADD CONSTRAINT fk_fichinter_rec_fk_projet         FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);
