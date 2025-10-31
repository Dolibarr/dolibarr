-- Copyright (C) 2025 Florian HENRY  <florian.henry@scopen.fr>
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
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Price Type
--
INSERT INTO llx_c_product_price_type (rowid,code,label,active) VALUES (1,'RECOM','Recommended price',1);
INSERT INTO llx_c_product_price_type (rowid,code,label,active) VALUES (2,'FACTO','Factory price', 1);
INSERT INTO llx_c_product_price_type (rowid,code,label,active) VALUES (3,'MANUF','Manufacturer price', 1);
