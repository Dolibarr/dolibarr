
alter table llx_propal add fin_validite datetime ;

alter table llx_entrepot add statut tinyint default 1;

alter table  llx_product add stock_propale integer default 0;
alter table  llx_product add stock_commande integer default 0;

alter table  llx_product add seuil_stock_alerte integer default 0;

ALTER TABLE `llx_groupart` ADD `description` TEXT AFTER `groupart` ;