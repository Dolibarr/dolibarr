--
-- Script to repair some fatal errors due to database corruption
-- when current version is 2.6.0 or higher. 
--


-- Requests to clean corrupted database

delete from llx_facturedet where fk_facture in (select rowid from llx_facture where facnumber = '(PROV)');
delete from llx_facture where facnumber = '(PROV)';
delete from llx_commandedet where fk_commande in (select rowid from llx_commande where ref = '(PROV)');
delete from llx_commande where ref = '(PROV)';
delete from llx_propaldet where fk_propal in (select rowid from llx_propal where ref = '(PROV)');
delete from llx_propal where ref = '(PROV)';
delete from llx_facturedet where fk_facture in (select rowid from llx_facture where facnumber = '');
delete from llx_facture where facnumber = '';
delete from llx_commandedet where fk_commande in (select rowid from llx_commande where ref = '');
delete from llx_commande where ref = '';
delete from llx_propaldet where fk_propal in (select rowid from llx_propal where ref = '');
delete from llx_propal where ref = '';

update llx_deplacement set dated='2010-01-01' where dated < '2000-01-01';

update llx_cotisation set fk_bank = null where fk_bank not in (select rowid from llx_bank);

update llx_propal set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_commande set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_facture set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_commande_fournisseur set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_contrat set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_deplacement set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_facture_fourn set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_facture_rec set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_fichinter set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_projet_task set fk_projet = null where fk_projet not in (select rowid from llx_projet);
