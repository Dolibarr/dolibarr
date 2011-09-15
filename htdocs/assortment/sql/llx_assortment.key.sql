-- ========================================================================
-- Copyright (C) 2011 Florian HENRY <florian.henry.mail@gmail.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id: llx_assortment.sql,v 3.0 2011/01/01
--
-- Assortment
-- ========================================================================


ALTER TABLE llx_assortment ADD INDEX idx_assortment_fk_soc (fk_soc);
ALTER TABLE llx_assortment ADD INDEX idx_assortment_fk_product (fk_prod);
ALTER TABLE llx_assortment ADD UNIQUE uk_assortment_field(fk_soc, fk_prod);
ALTER TABLE llx_assortment ADD CONSTRAINT fk_assortment_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE llx_assortment ADD CONSTRAINT fk_assortment_fk_prod FOREIGN KEY (fk_prod) REFERENCES llx_product(rowid) ON DELETE CASCADE ON UPDATE CASCADE;
