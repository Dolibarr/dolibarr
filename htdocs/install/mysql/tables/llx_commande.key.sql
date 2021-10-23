-- ============================================================================
-- Copyright (C) 2006		Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2009-2012	Regis Houssin		<regis.houssin@inodbox.com>
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


-- Delete orphans
-- V4 DELETE llx_commande FROM llx_commande LEFT JOIN llx_societe ON llx_commande.fk_soc = llx_societe.rowid WHERE llx_societe.rowid IS NULL; 

ALTER TABLE llx_commande ADD UNIQUE INDEX uk_commande_ref (ref, entity);

ALTER TABLE llx_commande ADD INDEX idx_commande_fk_soc (fk_soc);
ALTER TABLE llx_commande ADD INDEX idx_commande_fk_user_author (fk_user_author);
ALTER TABLE llx_commande ADD INDEX idx_commande_fk_user_valid (fk_user_valid);
ALTER TABLE llx_commande ADD INDEX idx_commande_fk_user_cloture (fk_user_cloture);
ALTER TABLE llx_commande ADD INDEX idx_commande_fk_projet (fk_projet);
ALTER TABLE llx_commande ADD INDEX idx_commande_fk_account(fk_account);
ALTER TABLE llx_commande ADD INDEX idx_commande_fk_currency(fk_currency);

ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_fk_soc			FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_fk_user_author	FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_fk_user_valid	FOREIGN KEY (fk_user_valid)  REFERENCES llx_user (rowid);
ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_fk_user_cloture	FOREIGN KEY (fk_user_cloture) REFERENCES llx_user (rowid);
ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_fk_projet		FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);
