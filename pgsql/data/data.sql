--
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- $Id$
-- $Source$
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
--
-- Valeurs pour les bases de langues francaises
--

delete from llx_c_chargesociales;
insert into llx_c_chargesociales (id,libelle,deductible) values ( 1, 'Allocations familiales',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 2, 'GSG Deductible',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 3, 'GSG/CRDS NON Deductible',0);


delete from llx_c_actioncomm;
insert into llx_c_actioncomm (id,libelle) values ( 0, '-');
insert into llx_c_actioncomm (id,libelle) values ( 1, 'Appel Téléphonique');
insert into llx_c_actioncomm (id,libelle) values ( 2, 'Envoi Fax');
insert into llx_c_actioncomm (id,libelle) values ( 3, 'Envoi propal par mail');
insert into llx_c_actioncomm (id,libelle) values ( 4, 'Envoi d\'un email'); 
insert into llx_c_actioncomm (id,libelle) values ( 5, 'Rendez-vous'); 
insert into llx_c_actioncomm (id,libelle) values ( 9, 'Envoi Facture');
insert into llx_c_actioncomm (id,libelle) values (10, 'Relance effectuée');
insert into llx_c_actioncomm (id,libelle) values (11, 'Clôture');

delete from llx_c_stcomm;
insert into llx_c_stcomm (id,libelle) values (-1, 'NE PAS CONTACTER');
insert into llx_c_stcomm (id,libelle) values ( 0, 'Jamais contacté');
insert into llx_c_stcomm (id,libelle) values ( 1, 'A contacter');
insert into llx_c_stcomm (id,libelle) values ( 2, 'Contact en cours');
insert into llx_c_stcomm (id,libelle) values ( 3, 'Contactée');

delete from llx_c_typent;
insert into llx_c_typent (id,libelle) values (  0, 'Indifférent');
insert into llx_c_typent (id,libelle) values (  1, 'Start-up');
insert into llx_c_typent (id,libelle) values (  2, 'Grand groupe');
insert into llx_c_typent (id,libelle) values (  3, 'PME/PMI');
insert into llx_c_typent (id,libelle) values (  4, 'Administration');
insert into llx_c_typent (id,libelle) values (100, 'Autres');

delete from llx_c_pays;
insert into llx_c_pays (id,libelle,code) values (0, 'France',          'FR');
insert into llx_c_pays (id,libelle,code) values (2, 'Belgique',        'BE');
insert into llx_c_pays (id,libelle,code) values (3, 'Italie',          'IT');
insert into llx_c_pays (id,libelle,code) values (4, 'Espagne',         'ES');
insert into llx_c_pays (id,libelle,code) values (5, 'Allemagne',       'DE');
insert into llx_c_pays (id,libelle,code) values (6, 'Suisse',          'CH');
insert into llx_c_pays (id,libelle,code) values (7, 'Royaume uni',     'GB');
insert into llx_c_pays (id,libelle,code) values (8, 'Irlande',         'IE');
insert into llx_c_pays (id,libelle,code) values (9, 'Chine',           'CN');
insert into llx_c_pays (id,libelle,code) values (10, 'Tunisie',        'TN');
insert into llx_c_pays (id,libelle,code) values (11, 'Etats Unis',     'US');
insert into llx_c_pays (id,libelle,code) values (12, 'Maroc',          'MA');
insert into llx_c_pays (id,libelle,code) values (13, 'Algérie',        'DZ');
insert into llx_c_pays (id,libelle,code) values (14, 'Canada',         'CA');
insert into llx_c_pays (id,libelle,code) values (15, 'Togo',           'TG');
insert into llx_c_pays (id,libelle,code) values (16, 'Gabon',          'GA');
insert into llx_c_pays (id,libelle,code) values (17, 'Pays Bas',       'NL');
insert into llx_c_pays (id,libelle,code) values (18, 'Hongrie',        'HU');
insert into llx_c_pays (id,libelle,code) values (19, 'Russie',         'RU');
insert into llx_c_pays (id,libelle,code) values (20, 'Suède',          'SE');
insert into llx_c_pays (id,libelle,code) values (21, 'Côte d\'Ivoire', 'CI');
insert into llx_c_pays (id,libelle,code) values (23, 'Sénégal',        'SN');
insert into llx_c_pays (id,libelle,code) values (24, 'Argentine',      'AR');
insert into llx_c_pays (id,libelle,code) values (25, 'Cameroun',       'CM');

delete from llx_c_effectif;
insert into llx_c_effectif (id,libelle) values (0,  'Non spécifié');
insert into llx_c_effectif (id,libelle) values (1,  '1 - 5');
insert into llx_c_effectif (id,libelle) values (2,  '6 - 10');
insert into llx_c_effectif (id,libelle) values (3,  '11 - 50');
insert into llx_c_effectif (id,libelle) values (4,  '51 - 100');
insert into llx_c_effectif (id,libelle) values (5,  '100 - 500');
insert into llx_c_effectif (id,libelle) values (6,  '> 500');

delete from llx_c_paiement;
insert into llx_c_paiement (id,libelle,type) values (0, '-', 3);
insert into llx_c_paiement (id,libelle,type) values (1, 'TIP', 1);
insert into llx_c_paiement (id,libelle,type) values (2, 'Virement', 2);
insert into llx_c_paiement (id,libelle,type) values (3, 'Prélèvement', 1);
insert into llx_c_paiement (id,libelle,type) values (4, 'Liquide', 0);
insert into llx_c_paiement (id,libelle,type) values (5, 'Paiement en ligne', 0);
insert into llx_c_paiement (id,libelle,type) values (6, 'CB', 1);
insert into llx_c_paiement (id,libelle,type) values (7, 'Chèque', 2);

delete from llx_c_propalst;
insert into llx_c_propalst (id,label) values (0, 'Brouillon');
insert into llx_c_propalst (id,label) values (1, 'Ouverte');
insert into llx_c_propalst (id,label) values (2, 'Signée');
insert into llx_c_propalst (id,label) values (3, 'Non Signée');
insert into llx_c_propalst (id,label) values (4, 'Facturée');

--
-- Utilisateur
--
insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta,admin,webcal_login)
values ('Quiedeville','Rodolphe','RQ','rodo','CRnN0Tam/s7z.',1,1,1,'rodo');