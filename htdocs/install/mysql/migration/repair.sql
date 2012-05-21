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

update llx_commande set fk_user_author = null where fk_user_author not in (select rowid from llx_user);

delete from llx_societe_extrafields where fk_object not in (select rowid from llx_societe);
delete from llx_adherent_extrafields where fk_object not in (select rowid from llx_adherent);
delete from llx_product_extrafields where fk_object not in (select rowid from llx_product);
--delete from llx_societe_commerciaux where fk_soc not in (select rowid from llx_societe);


DELETE FROM llx_boxes where box_id NOT IN (SELECT rowid FROM llx_boxes_def);

-- VMYSQL4.1 DELETE T1 FROM llx_boxes_def as T1, llx_boxes_def as T2 where T1.entity = T2.entity AND T1.file = T2.file AND T1.note = T2.note and T1.rowid > T2.rowid
-- VPGSQL8.2 DELETE FROM llx_boxes_def as T1 WHERE rowid NOT IN (SELECT min(rowid) FROM llx_boxes_def GROUP BY file, entity, note)


-- Requests to clean old tables or fields

-- DROP TABLE llx_c_methode_commande_fournisseur;
-- DROP TABLE llx_c_source;
-- DROP TABLE llx_cond_reglement;
-- DROP TABLE llx_expedition_methode;
-- DROP TABLE llx_product_fournisseur;
-- ALTER TABLE llx_product_fournisseur_price DROP COLUMN fk_product_fournisseur;

