-- ============================================================================
-- Copyright (C) 2005 Brice Davoleau       <e1davole@iu-vannes.fr>
-- Copyright (C) 2005 Matthieu Valleton    <mv@seeschloss.org>		
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- $Id: llx_categorie_association.key.sql,v 1.2 2011/08/03 01:25:26 eldy Exp $
-- ============================================================================


ALTER TABLE llx_categorie_association ADD UNIQUE INDEX uk_categorie_association (fk_categorie_mere, fk_categorie_fille);
ALTER TABLE llx_categorie_association ADD UNIQUE INDEX uk_categorie_association_fk_categorie_fille (fk_categorie_fille);

ALTER TABLE llx_categorie_association ADD CONSTRAINT fk_categorie_asso_fk_categorie_mere  FOREIGN KEY (fk_categorie_mere) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_association ADD CONSTRAINT fk_categorie_asso_fk_categorie_fille FOREIGN KEY (fk_categorie_fille) REFERENCES llx_categorie (rowid);
