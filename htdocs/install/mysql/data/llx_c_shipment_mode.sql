-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
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

--
-- Sending method
--
INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (1,'CATCH','In-Store Collection','In-store collection by the customer','',1);
INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (2,'TRANS','Courier Service','Courier Service','',1);
INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (3,'COLSUI','Colissimo Suivi','Colissimo Suivi','http://www.colissimo.fr/portail_colissimo/suivre.do?colispart={TRACKID}',0);
INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (4,'LETTREMAX','Lettre Max','Courrier Suivi et Lettre Max','',0);
INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (5,'UPS','UPS','United Parcel Service','http://wwwapps.ups.com/etracking/tracking.cgi?InquiryNumber2=&InquiryNumber3=&tracknums_displayed=3&loc=fr_FR&TypeOfInquiryNumber=T&HTMLVersion=4.0&InquiryNumber22=&InquiryNumber32=&track=Track&Suivi.x=64&Suivi.y=7&Suivi=Valider&InquiryNumber1={TRACKID}',0);
INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (6,'KIALA','KIALA','Relais Kiala','http://www.kiala.fr/tnt/delivery/{TRACKID}',0);
INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (7,'GLS','GLS','General Logistics Systems','https://gls-group.eu/FR/fr/suivi-colis?match={TRACKID}',0);
INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,tracking,active) VALUES (8,'CHRONO','Chronopost','Chronopost','http://www.chronopost.fr/expedier/inputLTNumbersNoJahia.do?listeNumeros={TRACKID}',0);
