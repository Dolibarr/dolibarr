--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.4.0 or higher. 
--

alter table llx_product add column   price_min          double(24,8) DEFAULT 0;
alter table llx_product add column   price_min_ttc      double(24,8) DEFAULT 0;

alter table llx_product_price   add column price_min              double(24,8) default NULL;
alter table llx_product_price   add column price_min_ttc          double(24,8) default NULL;

alter table llx_societe add column gencod			 varchar(255);

delete from llx_user_param where page <> '';

alter table llx_expedition add column tracking_number varchar(50) after fk_expedition_methode;

alter table llx_actioncomm add column location varchar(128) after percent;

-- remove enum type
alter table llx_adherent_type modify column cotisation       varchar(3) NOT NULL DEFAULT 'yes';
alter table llx_adherent_type modify column vote             varchar(3) NOT NULL DEFAULT 'yes';
alter table llx_adherent modify column morphy           varchar(3) NOT NULL;
alter table llx_c_paper_format modify column unit     varchar(5)                       NOT NULL;
alter table llx_const modify column type        varchar(6);
alter table llx_menu modify column type			varchar(4) NOT NULL;
alter table llx_notify modify column objet_type      varchar(24) NOT NULL;
alter table llx_projet_task_actors modify column role           varchar(5) DEFAULT 'admin';
alter table llx_projet_task modify column statut             varchar(6) DEFAULT 'open';
alter table llx_rights_def modify column   type          varchar(1);

ALTER TABLE `llx_commandedet` ADD column `date_start` DATETIME DEFAULT NULL, ADD `date_end` DATETIME DEFAULT NULL ;

alter table llx_categorie add column fk_soc integer DEFAULT NULL after description;

alter table llx_product drop column nbvente;

alter table llx_product     add column import_key         varchar(14);
alter table llx_socpeople   add column import_key         varchar(14);
alter table llx_facture     add column import_key         varchar(14);
alter table llx_facturedet  add column import_key         varchar(14);
alter table llx_commande    add column import_key         varchar(14);
alter table llx_commandedet add column import_key         varchar(14);
alter table llx_facture_fourn     add column import_key         varchar(14);
alter table llx_facture_fourn_det add column import_key         varchar(14);

alter table llx_commande    modify column source smallint NULL;

