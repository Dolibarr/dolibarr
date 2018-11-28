-- ============================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
-- ============================================================================


-- Delete orphans
-- V4 DELETE llx_contratdet FROM llx_contratdet, llx_contrat LEFT JOIN llx_societe ON llx_contrat.fk_soc = llx_societe.rowid WHERE llx_contratdet.fk_contrat = llx_contrat.rowid AND llx_societe.rowid IS NULL; 
-- V4 DELETE llx_contrat FROM llx_contrat LEFT JOIN llx_societe ON llx_contrat.fk_soc = llx_societe.rowid WHERE llx_societe.rowid IS NULL;
-- V4 DELETE llx_contrat FROM llx_contrat LEFT JOIN llx_user ON llx_contrat.fk_user_author = llx_user.rowid WHERE llx_user.rowid IS NULL;

ALTER TABLE llx_contrat ADD UNIQUE INDEX uk_contrat_ref (ref, entity);

ALTER TABLE llx_contrat ADD INDEX idx_contrat_fk_soc (fk_soc);
ALTER TABLE llx_contrat ADD INDEX idx_contrat_fk_user_author (fk_user_author);

ALTER TABLE llx_contrat ADD CONSTRAINT fk_contrat_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_contrat ADD CONSTRAINT fk_contrat_user_author FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);