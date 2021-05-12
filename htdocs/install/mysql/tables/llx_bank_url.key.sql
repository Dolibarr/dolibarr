-- ===================================================================
<<<<<<< HEAD
-- Copyright (C) 2005-2007 Laurent Destailleur <eldy@users.sourceforge.net>
=======
-- Copyright (C) 2005-2019 Laurent Destailleur <eldy@users.sourceforge.net>
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


<<<<<<< HEAD
ALTER TABLE llx_bank_url ADD UNIQUE INDEX uk_bank_url (fk_bank,type);

--ALTER TABLE llx_bank_url ADD INDEX idx_bank_url_fk_bank (fk_bank);
=======
ALTER TABLE llx_bank_url ADD UNIQUE INDEX uk_bank_url (fk_bank, url_id, type);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
