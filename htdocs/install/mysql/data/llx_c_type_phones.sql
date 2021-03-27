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
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

-- type_phones

INSERT INTO llx_c_type_phones (code, label, active, position) VALUES ('PHONE', 'PhoneStd', 1, 10);
INSERT INTO llx_c_type_phones (code, label, active, position) VALUES ('PHONEMOBILE', 'PhoneMobile', 1, 20);
INSERT INTO llx_c_type_phones (code, label, active, position) VALUES ('PHONEFAX', 'PhoneFax', 1, 30);
INSERT INTO llx_c_type_phones (code, label, active, position) VALUES ('PHONEPAGER', 'PhonePager', 1, 40);
