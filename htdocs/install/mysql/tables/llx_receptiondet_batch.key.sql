-- ===================================================================
-- Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

ALTER TABLE llx_receptiondet_batch ADD INDEX idx_receptiondet_batch_fk_element (fk_element);
ALTER TABLE llx_receptiondet_batch ADD INDEX idx_receptiondet_batch_fk_reception (fk_reception);
ALTER TABLE llx_receptiondet_batch ADD CONSTRAINT fk_receptiondet_batch_fk_reception FOREIGN KEY (fk_reception) REFERENCES llx_reception (rowid);
ALTER TABLE llx_receptiondet_batch ADD INDEX idx_receptiondet_batch_fk_product (fk_product);
ALTER TABLE llx_receptiondet_batch ADD INDEX idx_receptiondet_batch_fk_elementdet (fk_elementdet);
