--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.5.0 or higher. 
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):   VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres) VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE

-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


DELETE FROM llx_menu where module='holiday';

ALTER TABLE llx_projet_task ADD COLUMN planned_workload	real DEFAULT 0 NOT NULL AFTER duration_effective;

ALTER TABLE llx_socpeople ADD COLUMN statut tinyint DEFAULT 1 NOT NULL AFTER import_key;

create table llx_fichinter_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;

ALTER TABLE llx_fichinter_extrafields ADD INDEX idx_ficheinter_extrafields (fk_object);
ALTER TABLE llx_product ADD COLUMN desiredstock integer DEFAULT 0;


create table llx_commandedet_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    
  import_key       varchar(14)      	
)ENGINE=innodb;

ALTER TABLE llx_commandedet_extrafields ADD INDEX idx_commandedet_extrafields (fk_object);


ALTER TABLE llx_facturedet_rec ADD COLUMN info_bits	integer DEFAULT 0 after total_ttc;	-- TVA NPR ou non


create table llx_facturedet_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_facturedet_extrafields ADD INDEX idx_facturedet_extrafields (fk_object);

create table llx_propaldet_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_propaldet_extrafields ADD INDEX idx_propaldet_extrafields (fk_object);


DROP table llx_adherent_options;
DROP table llx_adherent_options_label;

ALTER TABLE llx_user ADD accountancy_code VARCHAR( 24 ) NULL;

DELETE FROM llx_boxes where box_id IN (SELECT rowid FROM llx_boxes_def where file='box_activity.php' AND note IS NULL);
DELETE FROM llx_boxes_def where file='box_activity.php' AND note IS NULL;
  
ALTER TABLE llx_cronjob ADD libname VARCHAR(255);

INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (30,'PROJECT_CREATE','Project creation','Executed when a project is created','project',30);

create table llx_categorie_contact
(
  fk_categorie  integer NOT NULL,
  fk_socpeople  integer NOT NULL,
  import_key    varchar(14)
)ENGINE=innodb;


ALTER TABLE llx_categorie_contact ADD PRIMARY KEY pk_categorie_contact (fk_categorie, fk_socpeople);
ALTER TABLE llx_categorie_contact ADD INDEX idx_categorie_contact_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_contact ADD INDEX idx_categorie_contact_fk_socpeople (fk_socpeople);

ALTER TABLE llx_categorie_contact ADD CONSTRAINT fk_categorie_contact_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_contact ADD CONSTRAINT fk_categorie_contact_fk_socpeople   FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople (rowid);

insert into llx_const (name, value, type, note, visible, entity) values ('PROJECT_TASK_ADDON_PDF','','chaine','Name of PDF/ODT tasks manager class',0,1);
insert into llx_const (name, value, type, note, visible, entity) values ('PROJECT_TASK_ADDON','mod_task_simple','chaine','Name of Numbering Rule task manager class',0,1);
insert into llx_const (name, value, type, note, visible, entity) values ('PROJECT_TASK_ADDON_PDF_ODT_PATH','DOL_DATA_ROOT/doctemplates/tasks','chaine','',0,1);

ALTER TABLE llx_projet_task ADD COLUMN ref varchar(50) AFTER rowid;
UPDATE llx_projet_task SET ref=rowid;
ALTER TABLE llx_projet_task ADD COLUMN  model_pdf varchar(255);

INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1301, 13, 1301, '', 0, 'Algerie', 1);

INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1201, 12, 1201, '', 0, 'Tanger-Tétouan', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1202, 12, 1202, '', 0, 'Gharb-Chrarda-Beni Hssen', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1203, 12, 1203, '', 0, 'Taza-Al Hoceima-Taounate', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1204, 12, 1204, '', 0, 'L''Oriental', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1205, 12, 1205, '', 0, 'Fès-Boulemane', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1206, 12, 1206, '', 0, 'Meknès-Tafialet', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1207, 12, 1207, '', 0, 'Rabat-Salé-Zemour-Zaër', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1208, 12, 1208, '', 0, 'Grand Cassablanca', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1209, 12, 1209, '', 0, 'Chaouia-Ouardigha', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1210, 12, 1210, '', 0, 'Doukahla-Adba', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1211, 12, 1211, '', 0, 'Marrakech-Tensift-Al Haouz', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1212, 12, 1212, '', 0, 'Tadla-Azilal', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1213, 12, 1213, '', 0, 'Sous-Massa-Drâa', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1214, 12, 1214, '', 0, 'Guelmim-Es Smara', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1215, 12, 1215, '', 0, 'Laâyoune-Boujdour-Sakia el Hamra', 1);
INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1216, 12, 1216, '', 0, 'Oued Ed-Dahab Lagouira', 1);

INSERT INTO  llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES(1001, 10, 1001, '', 0, 'Algerie', 1);

INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL01', 1301, '', 0, '', 'Wilaya d''Adrar', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL02', 1301, '', 0, '', 'Wilaya de Chlef', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL03', 1301, '', 0, '', 'Wilaya de Laghouat', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL04', 1301, '', 0, '', 'Wilaya d''Oum El Bouaghi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL05', 1301, '', 0, '', 'Wilaya de Batna', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL06', 1301, '', 0, '', 'Wilaya de Béjaïa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL07', 1301, '', 0, '', 'Wilaya de Biskra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL08', 1301, '', 0, '', 'Wilaya de Béchar', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL09', 1301, '', 0, '', 'Wilaya de Blida', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL11', 1301, '', 0, '', 'Wilaya de Bouira', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL12', 1301, '', 0, '', 'Wilaya de Tamanrasset', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL13', 1301, '', 0, '', 'Wilaya de Tébessa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL14', 1301, '', 0, '', 'Wilaya de Tlemcen', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL15', 1301, '', 0, '', 'Wilaya de Tiaret', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL16', 1301, '', 0, '', 'Wilaya de Tizi Ouzou', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL17', 1301, '', 0, '', 'Wilaya d''Alger', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL18', 1301, '', 0, '', 'Wilaya de Djelfa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL19', 1301, '', 0, '', 'Wilaya de Jijel', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL20', 1301, '', 0, '', 'Wilaya de Sétif	', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL21', 1301, '', 0, '', 'Wilaya de Saïda', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL22', 1301, '', 0, '', 'Wilaya de Skikda', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL23', 1301, '', 0, '', 'Wilaya de Sidi Bel Abbès', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL24', 1301, '', 0, '', 'Wilaya d''Annaba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL25', 1301, '', 0, '', 'Wilaya de Guelma', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL26', 1301, '', 0, '', 'Wilaya de Constantine', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL27', 1301, '', 0, '', 'Wilaya de Médéa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL28', 1301, '', 0, '', 'Wilaya de Mostaganem', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL29', 1301, '', 0, '', 'Wilaya de M''Sila', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL30', 1301, '', 0, '', 'Wilaya de Mascara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL31', 1301, '', 0, '', 'Wilaya d''Ouargla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL32', 1301, '', 0, '', 'Wilaya d''Oran', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL33', 1301, '', 0, '', 'Wilaya d''El Bayadh', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL34', 1301, '', 0, '', 'Wilaya d''Illizi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL35', 1301, '', 0, '', 'Wilaya de Bordj Bou Arreridj', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL36', 1301, '', 0, '', 'Wilaya de Boumerdès', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL37', 1301, '', 0, '', 'Wilaya d''El Tarf', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL38', 1301, '', 0, '', 'Wilaya de Tindouf', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL39', 1301, '', 0, '', 'Wilaya de Tissemsilt', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL40', 1301, '', 0, '', 'Wilaya d''El Oued', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL41', 1301, '', 0, '', 'Wilaya de Khenchela', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL42', 1301, '', 0, '', 'Wilaya de Souk Ahras', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL43', 1301, '', 0, '', 'Wilaya de Tipaza', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL44', 1301, '', 0, '', 'Wilaya de Mila', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL45', 1301, '', 0, '', 'Wilaya d''Aïn Defla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL46', 1301, '', 0, '', 'Wilaya de Naâma', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL47', 1301, '', 0, '', 'Wilaya d''Aïn Témouchent', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL48', 1301, '', 0, '', 'Wilaya de Ghardaia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('AL49', 1301, '', 0, '', 'Wilaya de Relizane', 1);

INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA', 1209, '', 0, '', 'Province de Benslimane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA1', 1209, '', 0, '', 'Province de Berrechid', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA2', 1209, '', 0, '', 'Province de Khouribga', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA3', 1209, '', 0, '', 'Province de Settat', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA4', 1210, '', 0, '', 'Province d''El Jadida', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA5', 1210, '', 0, '', 'Province de Safi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA6', 1210, '', 0, '', 'Province de Sidi Bennour', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA7', 1210, '', 0, '', 'Province de Youssoufia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA6B', 1205, '', 0, '', 'Préfecture de Fès', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA7B', 1205, '', 0, '', 'Province de Boulemane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA8', 1205, '', 0, '', 'Province de Moulay Yacoub', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA9', 1205, '', 0, '', 'Province de Sefrou', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA8A', 1202, '', 0, '', 'Province de Kénitra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA9A', 1202, '', 0, '', 'Province de Sidi Kacem', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA10', 1202, '', 0, '', 'Province de Sidi Slimane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA11', 1208, '', 0, '', 'Préfecture de Casablanca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA12', 1208, '', 0, '', 'Préfecture de Mohammédia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA13', 1208, '', 0, '', 'Province de Médiouna', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA14', 1208, '', 0, '', 'Province de Nouaceur', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA15', 1214, '', 0, '', 'Province d''Assa-Zag', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA16', 1214, '', 0, '', 'Province d''Es-Semara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA17A', 1214, '', 0, '', 'Province de Guelmim', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA18', 1214, '', 0, '', 'Province de Tata', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA19', 1214, '', 0, '', 'Province de Tan-Tan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA15', 1215, '', 0, '', 'Province de Boujdour', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA16', 1215, '', 0, '', 'Province de Lâayoune', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA17', 1215, '', 0, '', 'Province de Tarfaya', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA18', 1211, '', 0, '', 'Préfecture de Marrakech', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA19', 1211, '', 0, '', 'Province d''Al Haouz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA20', 1211, '', 0, '', 'Province de Chichaoua', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA21', 1211, '', 0, '', 'Province d''El Kelâa des Sraghna', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA22', 1211, '', 0, '', 'Province d''Essaouira', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA23', 1211, '', 0, '', 'Province de Rehamna', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA24', 1206, '', 0, '', 'Préfecture de Meknès', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA25', 1206, '', 0, '', 'Province d’El Hajeb', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA26', 1206, '', 0, '', 'Province d''Errachidia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA27', 1206, '', 0, '', 'Province d’Ifrane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA28', 1206, '', 0, '', 'Province de Khénifra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA29', 1206, '', 0, '', 'Province de Midelt', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA30', 1204, '', 0, '', 'Préfecture d''Oujda-Angad', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA31', 1204, '', 0, '', 'Province de Berkane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA32', 1204, '', 0, '', 'Province de Driouch', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA33', 1204, '', 0, '', 'Province de Figuig', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA34', 1204, '', 0, '', 'Province de Jerada', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA35', 1204, '', 0, '', 'Province de Nadorgg', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA36', 1204, '', 0, '', 'Province de Taourirt', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA37', 1216, '', 0, '', 'Province d''Aousserd', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA38', 1216, '', 0, '', 'Province d''Oued Ed-Dahab', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA39', 1207, '', 0, '', 'Préfecture de Rabat', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA40', 1207, '', 0, '', 'Préfecture de Skhirat-Témara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA41', 1207, '', 0, '', 'Préfecture de Salé', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA42', 1207, '', 0, '', 'Province de Khémisset', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA43', 1213, '', 0, '', 'Préfecture d''Agadir Ida-Outanane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA44', 1213, '', 0, '', 'Préfecture d''Inezgane-Aït Melloul', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA45', 1213, '', 0, '', 'Province de Chtouka-Aït Baha', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA46', 1213, '', 0, '', 'Province d''Ouarzazate', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA47', 1213, '', 0, '', 'Province de Sidi Ifni', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA48', 1213, '', 0, '', 'Province de Taroudant', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA49', 1213, '', 0, '', 'Province de Tinghir', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA50', 1213, '', 0, '', 'Province de Tiznit', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA51', 1213, '', 0, '', 'Province de Zagora', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA52', 1212, '', 0, '', 'Province d''Azilal', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA53', 1212, '', 0, '', 'Province de Beni Mellal', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA54', 1212, '', 0, '', 'Province de Fquih Ben Salah', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA55', 1201, '', 0, '', 'Préfecture de M''diq-Fnideq', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA56', 1201, '', 0, '', 'Préfecture de Tanger-Asilah', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA57', 1201, '', 0, '', 'Province de Chefchaouen', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA58', 1201, '', 0, '', 'Province de Fahs-Anjra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA59', 1201, '', 0, '', 'Province de Larache', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA60', 1201, '', 0, '', 'Province d''Ouezzane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA61', 1201, '', 0, '', 'Province de Tétouan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA62', 1203, '', 0, '', 'Province de Guercif', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA63', 1203, '', 0, '', 'Province d''Al Hoceïma', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA64', 1203, '', 0, '', 'Province de Taounate', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA65', 1203, '', 0, '', 'Province de Taza', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA6A', 1205, '', 0, '', 'Préfecture de Fès', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA7A', 1205, '', 0, '', 'Province de Boulemane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA15A', 1214, '', 0, '', 'Province d''Assa-Zag', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA16A', 1214, '', 0, '', 'Province d''Es-Semara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA18A', 1211, '', 0, '', 'Préfecture de Marrakech', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA19A', 1214, '', 0, '', 'Province de Tan-Tan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA19B', 1214, '', 0, '', 'Province de Tan-Tan', 1);

INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN01', 1001, '', 0, '', 'Ariana', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN02', 1001, '', 0, '', 'Béja', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN03', 1001, '', 0, '', 'Ben Arous', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN04', 1001, '', 0, '', 'Bizerte', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN05', 1001, '', 0, '', 'Gabès', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN06', 1001, '', 0, '', 'Gafsa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN07', 1001, '', 0, '', 'Jendouba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN08', 1001, '', 0, '', 'Kairouan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN09', 1001, '', 0, '', 'Kasserine', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN10', 1001, '', 0, '', 'Kébili', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN11', 1001, '', 0, '', 'La Manouba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN12', 1001, '', 0, '', 'Le Kef', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN13', 1001, '', 0, '', 'Mahdia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN14', 1001, '', 0, '', 'Médenine', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN15', 1001, '', 0, '', 'Monastir', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN16', 1001, '', 0, '', 'Nabeul', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN17', 1001, '', 0, '', 'Sfax', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN18', 1001, '', 0, '', 'Sidi Bouzid', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN19', 1001, '', 0, '', 'Siliana', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN20', 1001, '', 0, '', 'Sousse', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN21', 1001, '', 0, '', 'Tataouine', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN22', 1001, '', 0, '', 'Tozeur', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN23', 1001, '', 0, '', 'Tunis', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN24', 1001, '', 0, '', 'Zaghouan', 1);

-- Add ref_ext on bordereau_cheque
ALTER TABLE llx_bordereau_cheque ADD ref_ext VARCHAR(255);
ALTER TABLE llx_bordereau_cheque ADD tms timestamp;


-- Task 1011
ALTER TABLE llx_societe ADD mode_reglement_supplier integer NULL AFTER cond_reglement;
ALTER TABLE llx_societe ADD cond_reglement_supplier integer NULL AFTER mode_reglement_supplier;

ALTER TABLE llx_facture_fourn ADD fk_mode_reglement integer NULL AFTER fk_cond_reglement;

ALTER TABLE llx_facture_fourn MODIFY COLUMN fk_mode_reglement	integer NULL;
ALTER TABLE llx_facture_fourn MODIFY COLUMN fk_cond_reglement	integer NULL;


INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values (9,'COMPANY_SENTBYMAIL','Mails sent from third party card','Executed when you send email from third party card','societe',1);


ALTER TABLE llx_contratdet ADD column product_type integer DEFAULT 1 after total_ttc;

create table llx_contrat_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;


-- add outstanding bill
ALTER TABLE llx_societe ADD outstanding_limit double(24,8) DEFAULT NULL AFTER mode_reglement_supplier;

