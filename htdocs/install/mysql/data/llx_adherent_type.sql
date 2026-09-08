-- Copyright (C) 2026 Laurent Destailleur  <eldy@users.sourceforge.net>
--
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
-- Do not put a comment at the end of the line, this file is parsed during the
-- install and all '--' symbols are removed.
--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

INSERT INTO llx_adherent_type (entity, statut, libelle, morphy, duration, subscription, amount, caneditamount, minimumamount, amountformuladescription, vote, note, mail_valid) VALUES(1, 1, 'Type standard', '', '1y', '1', NULL, 0, NULL, '', '1', '', '');
