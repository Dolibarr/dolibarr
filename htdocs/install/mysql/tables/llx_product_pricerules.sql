-- ============================================================================
-- Copyright (C) 2015      Marcos García <marcosgdf@gmail.com>
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
-- ===========================================================================

-- Table used only when PRODUIT_MULTIPRICES is set (prices per level) + PRODUIT_MULTIPRICES_ALLOW_AUTOCALC_PRICELEVEL is set
-- A checkbox become available on product to allow to set price rules

CREATE TABLE llx_product_pricerules
(
    rowid INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
    level INTEGER NOT NULL, -- Which price level is this rule for?
    fk_level INTEGER NOT NULL, -- Price variations are made over price of X
    var_percent REAL NOT NULL, -- Price variation over based price
    var_min_percent REAL NOT NULL -- Min price discount over general price
)ENGINE=innodb;
