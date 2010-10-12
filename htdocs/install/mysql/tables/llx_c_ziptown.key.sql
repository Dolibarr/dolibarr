-- ========================================================================
-- Copyright (C) 2010 Regis Houssin <regis@dolibarr.fr>
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
-- $Id$
-- ========================================================================


ALTER TABLE llx_c_ziptown ADD INDEX idx_c_ziptown_fk_country (fk_country);
ALTER TABLE llx_c_ziptown ADD INDEX idx_c_ziptown_fk_county (fk_county);

ALTER TABLE llx_c_ziptown ADD CONSTRAINT fk_c_ziptown_fk_country	FOREIGN KEY (fk_country)  REFERENCES llx_c_pays (rowid);
ALTER TABLE llx_c_ziptown ADD CONSTRAINT fk_c_ziptown_fk_county		FOREIGN KEY (fk_county)   REFERENCES llx_c_departements (rowid);