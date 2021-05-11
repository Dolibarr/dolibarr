-- ============================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
-- Copyright (C) 2005 Marc Barilley / Ocebo <marc@ocebo.com>
-- Copyright (C) 2005 Laurent Destailleur   <eldy@users.sourceforge.net>
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
-- ===========================================================================

ALTER TABLE llx_paiementfourn_facturefourn ADD INDEX idx_paiementfourn_facturefourn_fk_facture(fk_facturefourn);
ALTER TABLE llx_paiementfourn_facturefourn ADD INDEX idx_paiementfourn_facturefourn_fk_paiement(fk_paiementfourn);


ALTER TABLE llx_paiementfourn_facturefourn ADD UNIQUE INDEX uk_paiementfourn_facturefourn(fk_paiementfourn, fk_facturefourn);
