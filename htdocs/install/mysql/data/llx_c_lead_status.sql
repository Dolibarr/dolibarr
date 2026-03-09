-- Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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

-- Opportunities status
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (1,'PROSP'  ,'Prospection',  10, 0,1);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (2,'QUAL'   ,'Qualification',20, 20,1);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (3,'PROPO'  ,'Proposal',     30, 40,1);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (4,'NEGO'   ,'Negotiation',  40, 60,1);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (5,'PENDING','Pending',      50, 50,0);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (6,'WON'    ,'Won',          60, 100,1);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (7,'LOST'   ,'Lost',         70, 0,1);

