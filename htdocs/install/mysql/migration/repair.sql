--
-- $Id$
--
-- Script to repair some fatal errors due to database corruption
-- 
-- when current version is 2.6.0 or higher. 
--


-- Request to clean corrupted database
delete from llx_facture where facnumber = '(PROV)';
delete from llx_commande where ref = '(PROV)';
delete from llx_propal where ref = '(PROV)';
delete from llx_facture where facnumber = '';
-- V4.1 delete from llx_commandedet where fk_commande in (select rowid from llx_commande where ref = '');
delete from llx_commande where ref = '';
-- V4.1 delete from llx_propaldet where fk_propal in (select rowid from llx_propal where ref = '');
delete from llx_propal where ref = '';
