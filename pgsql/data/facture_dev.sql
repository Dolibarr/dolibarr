-- ===========================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
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
-- Dumping data for table 'llx_paiement'
--
-- $Id$
-- $Source$
--
--===========================================================================


INSERT INTO llx_paiement (rowid, fk_facture, datec, datep, amount, author, fk_paiement, num_paiement, note) VALUES (1,1,'2002-05-09 03:05:03','2001-05-19 12:00:00',11960,'rodo',0,'321654654','');
INSERT INTO llx_paiement (rowid, fk_facture, datec, datep, amount, author, fk_paiement, num_paiement, note) VALUES (2,2,'2002-05-09 03:18:10','2002-04-12 12:00:00',500,'rodo',0,'255555','');
INSERT INTO llx_paiement (rowid, fk_facture, datec, datep, amount, author, fk_paiement, num_paiement, note) VALUES (3,2,'2002-05-09 03:18:32','2002-05-02 12:00:00',588.36,'rodo',0,'25555','');
INSERT INTO llx_paiement (rowid, fk_facture, datec, datep, amount, author, fk_paiement, num_paiement, note) VALUES (4,3,'2002-05-09 03:21:43','2002-03-30 12:00:00',11.96,'rodo',0,'','');
INSERT INTO llx_paiement (rowid, fk_facture, datec, datep, amount, author, fk_paiement, num_paiement, note) VALUES (5,4,'2002-05-09 03:23:47','2002-02-20 12:00:00',11960,'rodo',0,'','');
INSERT INTO llx_paiement (rowid, fk_facture, datec, datep, amount, author, fk_paiement, num_paiement, note) VALUES (6,5,'2002-05-09 03:37:05','2002-02-09 12:00:00',23.92,'rodo',0,'','');
INSERT INTO llx_paiement (rowid, fk_facture, datec, datep, amount, author, fk_paiement, num_paiement, note) VALUES (7,6,'2002-05-09 03:40:58','2002-05-09 12:00:00',35.88,'rodo',0,'','');
INSERT INTO llx_paiement (rowid, fk_facture, datec, datep, amount, author, fk_paiement, num_paiement, note) VALUES (8,8,'2002-05-09 14:44:56','2002-03-12 12:00:00',5000,'rodo',0,'','');
INSERT INTO llx_paiement (rowid, fk_facture, datec, datep, amount, author, fk_paiement, num_paiement, note) VALUES (9,7,'2002-05-09 14:49:51','2002-05-09 12:00:00',23920,'rodo',0,'','');
INSERT INTO llx_paiement (rowid, fk_facture, datec, datep, amount, author, fk_paiement, num_paiement, note) VALUES (10,8,'2002-05-09 15:00:35','2002-05-09 12:00:00',9483.56,'rodo',0,'','');
INSERT INTO llx_paiement (rowid, fk_facture, datec, datep, amount, author, fk_paiement, num_paiement, note) VALUES (11,9,'2002-05-09 15:02:36','2002-05-09 12:00:00',43355,'rodo',0,'','');

--
-- Dumping data for table 'llx_facture'
--

insert into llx_facture (rowid, facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note)
values (1,'F-BO-010509',1,'2001-05-09 03:04:48','2001-05-09',1,10000,0,1960,11960,1,NULL,NULL,'');

INSERT INTO llx_facture (rowid, facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note)
VALUES (2,'F-DO-020410',3,'2002-05-09 03:17:44','2002-04-10',1,910,100,178.36,1088.36,1,NULL,NULL,'');

INSERT INTO llx_facture (rowid, facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note)
VALUES (3,'F-BO-020314',1,'2002-05-09 03:21:25','2002-03-14',1,10,0,1.96,11.96,1,NULL,NULL,'');

INSERT INTO llx_facture (rowid, facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note)
VALUES (4,'F-CU-020215',2,'2002-05-09 03:23:31','2002-02-15',1,10000,0,1960,11960,1,NULL,NULL,'');

INSERT INTO llx_facture (rowid, facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note)
VALUES (5,'F-BO-020117',1,'2002-05-09 03:36:43','2002-01-17',1,20,0,3.92,23.92,1,NULL,NULL,'');

INSERT INTO llx_facture (rowid, facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note)
VALUES (6,'F-BO-020119',1,'2002-05-09 03:40:26','2002-05-09',1,30,0,5.88,35.88,1,NULL,NULL,'');

INSERT INTO llx_facture (rowid, facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note)
VALUES (7,'F-CU-020509',2,'2002-05-09 03:46:54','2002-05-09',1,20000,0,3920,23920,1,NULL,NULL,'');

INSERT INTO llx_facture (rowid, facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note)
VALUES (8,'F-FOO-020309',4,'2002-05-09 14:44:34','2002-03-09',1,12110,0,2373.56,14483.56,1,NULL,NULL,'');

INSERT INTO llx_facture (rowid, facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note)
VALUES (9,'F-CU-020509.1',2,'2002-05-09 15:02:08','2002-05-09',1,36250,0,7105,43355,1,NULL,NULL,'');

insert into llx_facture (rowid, facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note)
values (10,'F-BO-010310',1,'2001-03-10 03:04:48','2001-03-10',1,20000,0,3920,13920,1,NULL,NULL,'');

insert into llx_facture (rowid, facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note)
VALUES (11,'F-DO-010410',3,'2001-04-10 03:17:44','2001-04-10',1,910,100,178.36,1088.36,1,NULL,NULL,'');

INSERT INTO llx_facture (rowid, facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note)
VALUES (12,'F-BO-010117',1,'2001-05-09 03:36:43','2001-01-17',1,20,0,3.92,23.92,1,NULL,NULL,'');

INSERT INTO llx_facture (rowid, facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note)
VALUES (13,'F-CU-010509.1',2,'2001-05-09 15:02:08','2001-05-09',1,36250,0,7105,43355,1,NULL,NULL,'');
