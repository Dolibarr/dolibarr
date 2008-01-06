--
-- $Id$
--
-- Attention à l ordre des requetes.
-- Ce fichier doit être chargé sur une version 2.2.0 
--

delete from llx_const where name='MAIN_GRAPH_LIBRARY' and (value like 'phplot%' or value like 'artichow%');

ALTER TABLE llx_societe_adresse_livraison ADD COLUMN tel varchar(20) after fk_pays;
ALTER TABLE llx_societe_adresse_livraison ADD COLUMN fax varchar(20) after tel;

alter table llx_c_barcode_type modify coder varchar(16) NOT NULL;
update llx_c_barcode_type set coder = 0 where coder in (1,2);

update llx_const set value='' where name='MAIN_FORCE_SETLOCALE_LC_ALL'      and value='MAIN_FORCE_SETLOCALE_LC_ALL';
update llx_const set value='' where name='MAIN_FORCE_SETLOCALE_LC_MONETARY' and value='MAIN_FORCE_SETLOCALE_LC_MONETARY';
update llx_const set value='' where name='MAIN_FORCE_SETLOCALE_LC_NUMERIC'  and value='MAIN_FORCE_SETLOCALE_LC_NUMERIC';
update llx_const set value='' where name='MAIN_FORCE_SETLOCALE_LC_TIME'     and value='MAIN_FORCE_SETLOCALE_LC_TIME';
