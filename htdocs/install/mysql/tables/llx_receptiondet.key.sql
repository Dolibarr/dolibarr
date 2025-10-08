-- ===================================================================
-- Copyright (C) 2005	Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2008	Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2025	Nick Fragoulis
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


ALTER TABLE llx_receptiondet ADD INDEX idx_receptiondet_fk_reception (fk_reception);
ALTER TABLE llx_receptiondet ADD INDEX idx_receptiondet_fk_elementdet (fk_elementdet);
ALTER TABLE llx_receptiondet ADD INDEX idx_receptiondet_fk_product (fk_product);
ALTER TABLE llx_receptiondet ADD INDEX idx_receptiondet_fk_parent (fk_parent);

ALTER TABLE llx_receptiondet ADD CONSTRAINT fk_receptiondet_fk_reception FOREIGN KEY (fk_reception) REFERENCES llx_reception (rowid);

