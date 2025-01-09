-- ===================================================================
-- Copyright (C) 2009-2011 Regis Houssin  <regis.houssin@inodbox.com>
-- Copyright (C) 2012      Cédric Salvador      <csalvador@gpcsolutions.fr>
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



ALTER TABLE llx_supplier_proposaldet ADD INDEX idx_supplier_proposaldet_fk_supplier_proposal (fk_supplier_proposal);
ALTER TABLE llx_supplier_proposaldet ADD INDEX idx_supplier_proposaldet_fk_product (fk_product);

ALTER TABLE llx_supplier_proposaldet ADD CONSTRAINT fk_supplier_proposaldet_fk_unit FOREIGN KEY (fk_unit) REFERENCES llx_c_units (rowid);
ALTER TABLE llx_supplier_proposaldet ADD CONSTRAINT fk_supplier_proposaldet_fk_supplier_proposal FOREIGN KEY (fk_supplier_proposal) REFERENCES llx_supplier_proposal (rowid);
