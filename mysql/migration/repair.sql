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
delete from llx_commande where ref = '';
delete from llx_propal where ref = '';
