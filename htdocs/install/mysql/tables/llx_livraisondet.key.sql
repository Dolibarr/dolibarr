-- ===================================================================
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
-- Copyright (C) 2008 Regis Houssin        <regis.houssin@capnetworks.com>
=======
-- Copyright (C) 2008 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
-- ===================================================================


ALTER TABLE llx_livraisondet ADD INDEX idx_livraisondet_fk_expedition (fk_livraison);
ALTER TABLE llx_livraisondet ADD CONSTRAINT fk_livraisondet_fk_livraison FOREIGN KEY (fk_livraison) REFERENCES llx_livraison (rowid);
