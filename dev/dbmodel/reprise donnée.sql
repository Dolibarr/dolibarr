TRUNCATE TABLE llx_c_payment_term;
INSERT INTO llx_c_payment_term (rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour, decalage, module) VALUES
(1, 'RECEP', 1, 1, 'A réception de facture', 'Réception de facture', 0, 0, NULL, NULL),
(2, '30D', 2, 1, '30 jours', 'Réglement à 30 jours', 0, 30, NULL, NULL),
(3, '30DENDMONTH', 3, 0, '30 jours fin de mois', 'Réglement à 30 jours fin de mois', 1, 30, NULL, NULL),
(4, '60D', 4, 1, '60 jours', 'Réglement à 60 jours', 0, 60, NULL, NULL),
(5, '60DENDMONTH', 5, 0, '60 jours fin de mois', 'Réglement à 60 jours fin de mois', 1, 60, NULL, NULL),
(6, 'PT_ORDER', 6, 0, 'A réception de commande', 'A réception de commande', 0, 0, NULL, NULL),
(7, 'PT_DELIVERY', 7, 0, 'Livraison', 'Règlement à la livraison', 0, 0, NULL, NULL),
(8, 'PT_5050', 8, 0, '50 et 50', 'Règlement 50% à la commande, 50% à la livraison', 0, 0, NULL, NULL),
(9, '45D', NULL, 1, '45 jours', 'Règlement à 45 jours', 0, 45, NULL, NULL),
(10, '90D', NULL, 1, '90 jours', 'Règlement à 90 jours', 0, 90, NULL, NULL),
(11, '85D', NULL, 1, '85 jours', 'Règlement à 85 jours', 0, 85, NULL, NULL),
(12, '75D', NULL, 1, '75 jours', 'Règlement à 75 jours', 0, 75, NULL, NULL),
(13, '100D', NULL, 1, '100 jours', 'Règlement à 100 jours', 0, 100, NULL, NULL),
(14, '70D', NULL, 1, '70 jours', 'Règlement à 70 jours', 0, 70, NULL, NULL);

ALTER TABLE llx_societe MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_societe MODIFY COLUMN nom varchar(80);
ALTER TABLE llx_socpeople MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_product MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_agefodd_stagiaire MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_agefodd_session_stagiaire MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_propal MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_facture MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_facturedet MODIFY COLUMN import_key varchar(36);
ALTER TABLE llx_agefodd_place ADD COLUMN import_key varchar(36) DEFAULT NULL;
ALTER TABLE llx_agefodd_formation_catalogue ADD COLUMN import_key varchar(36) DEFAULT NULL;
ALTER TABLE llx_user ADD COLUMN import_key varchar(36) DEFAULT NULL;

ALTER TABLE llx_societe ADD INDEX idx_llx_societe_import_key (import_key);
ALTER TABLE llx_propal ADD INDEX idx_llx_propal_import_key (import_key);
ALTER TABLE llx_agefodd_session ADD INDEX idx_llx_agefodd_session_import_key (import_key);
ALTER TABLE llx_user ADD INDEX idx_llx_user_import_key (import_key);
ALTER TABLE llx_socpeople ADD INDEX idx_llx_socpeople_import_key (import_key);


TRUNCATE TABLE llx_extrafields;
INSERT INTO `llx_extrafields` ( `name`, `entity`, `elementtype`, `tms`, `label`, `type`, `size`, `fieldunique`, `fieldrequired`, `pos`, `param`) VALUES
( 'ts_partenaire', 1, 'societe', '2013-10-27 08:52:49', 'Partenaire', 'select', '', 0, 0, 2, 'a:1:{s:7:"options";a:7:{s:0:"";N;s:4:"FORM";s:24:"Prestataire de formation";s:4:"UNIV";s:11:"Université";s:3:"MOB";s:24:"Mobilité internationale";s:5:"OUTIL";s:6:"Outils";s:3:"COM";s:13:"Communication";s:3:"AUT";s:6:"Autres";}}'),
( 'ts_prospection', 1, 'societe', '2013-11-05 10:24:26', 'Chargé de prospection', 'sellist', '', 0, 0, 4, 'a:1:{s:7:"options";a:1:{s:76:"user:CONCAT_WS('' '',main.lastname,main.firstname):rowid:extra.u_prospection=1";N;}}'),
( 'ts_secteur', 1, 'societe', '2013-10-25 23:34:03', 'Secteur d''activité', 'select', '', 0, 0, 1, 'a:1:{s:7:"options";a:19:{s:0:"";N;s:4:"AERO";s:12:"Aeronautique";s:4:"AGRO";s:16:" Agroalimentaire";s:4:"ASSO";s:11:"Association";s:4:"AUTO";s:10:"Automobile";s:3:"BTP";s:3:"BTP";s:4:"CHIM";s:6:"Chimie";s:4:"CONS";s:7:"Conseil";s:5:"CONSO";s:21:"Biens de consommation";s:6:"DISTRI";s:12:"Distribution";s:4:"EDUC";s:9:"Education";s:5:"ENERG";s:7:"Energie";s:3:"FIN";s:7:"Finance";s:3:"LUX";s:4:"Luxe";s:3:"PUB";s:13:"Communication";s:2:"SP";s:16:"Services publics";s:3:"TIC";s:12:"Informatique";s:7:"TOURISM";s:9:"Tourisme ";s:6:"TRANSP";s:9:"Transport";}}'),
( 'ts_logistique', 1, 'societe', '2013-11-05 10:30:44', 'Gestionnaire logistique', 'sellist', '', 0, 0, 5, 'a:1:{s:7:"options";a:1:{s:75:"user:CONCAT_WS('' '',main.lastname,main.firstname):rowid:extra.u_logistique=1";N;}}'),
( 'ts_payeur', 1, 'societe', '2013-10-27 08:36:45', 'Payeur', 'boolean', '', 0, 0, 3, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'ct_principal', 1, 'socpeople', '2013-10-25 14:15:30', 'Contact principal', 'boolean', '', 0, 0, 6, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'ct_magellan', 1, 'socpeople', '2013-10-25 14:21:17', 'Membre de Magellan', 'boolean', '', 0, 0, 13, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'ct_mailing_papier', 1, 'socpeople', '2013-10-25 14:16:12', 'Destinataire mailing papier', 'boolean', '', 0, 0, 8, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'ct_invitation', 1, 'socpeople', '2013-10-25 14:15:52', 'Destinataire invitation', 'boolean', '', 0, 0, 7, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'ct_pays', 1, 'socpeople', '2013-10-25 14:21:43', 'Zones géographiques', 'checkbox', '', 0, 0, 15, 'a:1:{s:7:"options";a:14:{i:1;s:11:"Afrique Est";i:2;s:13:"Afrique Ouest";i:3;s:11:"Afrique Sud";i:4;s:14:"Amérique Nord";i:5;s:13:"Amérique Sud";i:6;s:15:"Asie Ext Orient";i:7;s:12:"Asie Sud Est";i:8;s:7:"Balkans";i:9;s:10:"Europe Est";i:10;s:11:"Europe Nord";i:11;s:12:"Europe Ouest";i:12;s:7:"Maghreb";i:13;s:8:"Océanie";s:0:"";N;}}'),
( 'ct_service', 1, 'socpeople', '2013-10-26 08:22:25', 'Fonction/Service', 'select', '', 0, 0, 5, 'a:1:{s:7:"options";a:28:{s:3:"DFO";s:19:"Directeur Formation";s:3:"RFO";s:21:"Responsable Formation";s:3:"AFO";s:21:" Assistante Formation";s:3:"DRH";s:29:"Directeur Ressources Humaines";s:3:"RRH";s:31:"Responsable Ressources humaines";s:3:"ARH";s:30:"Assistante Ressources Humaines";s:3:"RGC";s:33:"Responsable Gestion de carrières";s:3:"DMI";s:34:"Directeur Mobilité Internationale";s:3:"RMI";s:36:"Responsable Mobilité Internationale";s:3:"AMI";s:35:"Assistante Mobilité Internationale";s:3:"C&B";s:33:"Compensation and Benefits Manager";s:3:"ASS";s:10:"Assistante";s:3:"DDA";s:6:"Achats";s:3:"DBU";s:13:"Business Unit";s:3:"DCO";s:10:"Commercial";s:3:"DCM";s:14:" Communication";s:2:"DG";s:21:" Direction Générale";s:3:"RDI";s:10:"Diversité";s:3:"DOP";s:27:" Exploitation & Opérations";s:3:"DFI";s:7:"Finance";s:3:"DEX";s:13:"Import/Export";s:3:"DJU";s:9:"Juridique";s:3:"DLO";s:10:"Logistique";s:3:"DMA";s:9:"Marketing";s:3:"DTE";s:10:"Production";s:3:"DRD";s:26:"Recherche & Développement";s:3:"DSI";s:22:"Système d''information";s:3:"AUT";s:6:"Autres";}}'),
( 'ct_origine', 1, 'socpeople', '2013-10-25 14:14:16', 'Origine du contact', 'select', '', 0, 0, 3, 'a:1:{s:7:"options";a:8:{s:5:"EMAIL";s:8:"Emailing";s:8:"INTERNET";s:19:"Moteur de recherche";s:9:"TELEPHONE";s:26:"Prospection téléphonique";s:9:"CATALOGUE";s:9:"Catalogue";s:14:"RECOMMANDATION";s:14:"Recommandation";s:3:"EVT";s:10:"Evènement";s:10:"PARTENAIRE";s:10:"Partenaire";s:0:"";N;}}'),
( 'ct_email_commercial', 1, 'socpeople', '2013-10-25 14:19:53', 'Envoyer emailing commercial', 'boolean', '', 0, 0, 9, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'ct_email_news', 1, 'socpeople', '2013-10-25 14:20:17', 'Envoyer emailing news', 'boolean', '', 0, 0, 10, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'ct_email_invitation', 1, 'socpeople', '2013-10-25 14:20:29', 'Envoyer emailing invitation', 'boolean', '', 0, 0, 11, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'ct_precision_origine', 1, 'socpeople', '2013-10-25 14:14:34', 'Précision sur l''origine du contact', 'varchar', '255', 0, 0, 4, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'ct_sujets', 1, 'socpeople', '2013-10-25 14:21:32', 'Sujets d''intérêt', 'checkbox', '', 0, 0, 14, 'a:1:{s:7:"options";a:21:{i:1;s:5:"Achat";i:2;s:10:"A distance";i:3;s:10:"Changement";i:4;s:10:"Commercial";i:5;s:13:"Communication";i:6;s:8:"Conflits";i:7;s:16:"Dévpt personnel";i:8;s:10:"Diversité";i:9;s:12:"Expatriation";i:10;s:6:"Export";i:11;s:7:"Finance";i:12;s:14:"Interculturel ";i:13;s:9:"Juridique";i:14;s:10:"Management";i:15;s:9:"Marketing";i:16;s:13:"Négociation ";i:17;s:4:"Pays";i:18;s:6:"Projet";i:19;s:12:"Retour expat";i:20;s:2:"RH";s:0:"";N;}}'),
( 'ct_motif_inactif', 1, 'socpeople', '2013-10-25 14:38:38', 'Commentaire', 'varchar', '255', 0, 0, 2, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'ct_anglais', 1, 'socpeople', '2013-10-25 14:20:40', 'Langue anglaise', 'boolean', '', 0, 0, 12, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'u_interentreprises', 1, 'user', '2013-11-01 22:24:18', 'Chargé des Interentreprises', 'boolean', '', 0, 0, 0, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'u_prospection', 1, 'user', '2013-10-31 16:05:18', 'Chargé de prospection', 'boolean', '', 0, 0, 0, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'u_commercial', 1, 'user', '2013-11-01 22:18:01', 'Commercial', 'boolean', '', 0, 0, 0, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'u_logistique', 1, 'user', '2013-11-01 22:17:14', 'Gestionnaire logistique', 'boolean', '', 0, 0, 0, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'u_communication', 1, 'user', '2013-11-01 22:25:03', 'Chargé de communication', 'boolean', '', 0, 0, 0, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'comment', 1, 'propal', '2013-09-01 11:14:27', 'Commentaire', 'text', '2000', 0, 0, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
( 'cd_domaine', 1, 'agefodd_formation_catalogue', '2013-10-20 12:05:28', 'Domaine', 'select', '', 0, 0, 1, 'a:1:{s:7:"options";a:16:{s:5:"00000";s:5:"00000";s:3:"COA";s:3:"COA";s:3:"COF";s:3:"COF";s:5:"COMER";s:10:"Commercial";s:4:"COMM";s:13:"Communication";s:3:"CON";s:3:"CON";s:5:"EFPRO";s:27:"Efficacité professionnelle";s:5:"EXPAT";s:12:"Expatriation";s:3:"FOR";s:3:"FOR";s:3:"FRF";s:24:"Formations de formateurs";s:4:"JURI";s:9:"Juridique";s:6:"MARKET";s:9:"Marketing";s:4:"MGNT";s:10:"Management";s:4:"PAYS";s:14:"Expertise pays";s:2:"RH";s:19:"Ressources humaines";s:3:"SIC";s:24:"Interculturel transverse";}}'),
('ts_prospection', 1, 'agefodd_session', '2013-11-15 12:10:59', 'Chargé de prospection', 'sellist', '', 0, 0, 0, 'a:1:{s:7:"options";a:1:{s:76:"user:CONCAT_WS('' '',main.lastname,main.firstname):rowid:extra.u_prospection=1";N;}}'),
('ts_logistique', 1, 'agefodd_session', '2013-11-15 12:11:41', ' Gestionnaire logistique', 'sellist', '', 0, 0, 0, 'a:1:{s:7:"options";a:1:{s:75:"user:CONCAT_WS('' '',main.lastname,main.firstname):rowid:extra.u_logistique=1";N;}}'),
('ct_catalogue', 1, 'socpeople', '2013-11-11 10:47:04', 'Destinataire catalogue', 'boolean', '', 0, 0, 7, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}'),
('cd_ss_domaine', 1, 'agefodd_formation_catalogue', '2013-11-15 18:41:20', 'Sous-domaine', 'select', '', 0, 0, 2, 'a:1:{s:7:"options";a:11:{s:0:"";N;s:5:"WPAYS";s:13:"Business pays";s:5:"EXPAT";s:12:"Expatriation";s:6:"LEADER";s:10:"Leadership";s:5:"EQUIP";s:7:"Equipes";s:4:"DIST";s:19:"Travail à distance";s:3:"SIC";s:31:"Sensibilisation interculturelle";s:4:"NEGO";s:12:"Négociation";s:2:"RH";s:2:"RH";s:6:"EXPORT";s:6:"Export";s:3:"DIV";s:10:"Diversité";}}');

ALTER TABLE llx_societe_extrafields ADD COLUMN ts_partenaire text DEFAULT NULL;
ALTER TABLE llx_societe_extrafields ADD COLUMN ts_prospection text DEFAULT NULL;
ALTER TABLE llx_societe_extrafields ADD COLUMN ts_secteur text DEFAULT NULL;
ALTER TABLE llx_societe_extrafields ADD COLUMN ts_logistique text DEFAULT NULL;
ALTER TABLE llx_societe_extrafields ADD COLUMN ts_payeur int(1) DEFAULT NULL;

ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_principal int(1) DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_magellan int(1) DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_mailing_papier int(1) DEFAULT 0;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_invitation int(1) DEFAULT 0;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_service text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_origine text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_email_commercial int(1) DEFAULT 1;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_email_news int(1) DEFAULT 1;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_email_invitation int(1) DEFAULT 1;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_precision_origine varchar(255) DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_sujets text DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_motif_inactif varchar(255) DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_anglais int(1) DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_pays int(1) DEFAULT NULL;
ALTER TABLE llx_socpeople_extrafields ADD COLUMN ct_catalogue int(1) DEFAULT NULL;

ALTER TABLE llx_user_extrafields ADD COLUMN u_interentreprises int(1) DEFAULT NULL;
ALTER TABLE llx_user_extrafields ADD COLUMN u_prospection int(1) DEFAULT NULL;
ALTER TABLE llx_user_extrafields ADD COLUMN u_commercial int(1) DEFAULT NULL;
ALTER TABLE llx_user_extrafields ADD COLUMN u_logistique int(1) DEFAULT NULL;
ALTER TABLE llx_user_extrafields ADD COLUMN u_communication int(1) DEFAULT NULL;

ALTER TABLE llx_propal_extrafields ADD COLUMN comment text DEFAULT NULL;
ALTER TABLE llx_agefodd_formation_catalogue_extrafields ADD COLUMN cd_domaine text DEFAULT NULL;
ALTER TABLE llx_agefodd_formation_catalogue_extrafields ADD COLUMN cd_ss_domaine text DEFAULT NULL;

ALTER TABLE llx_agefodd_session_extrafields ADD COLUMN ts_prospection text DEFAULT NULL;
ALTER TABLE llx_agefodd_session_extrafields ADD COLUMN ts_logistique text DEFAULT NULL;

TRUNCATE TABLE llx_const;
INSERT INTO `llx_const` (`rowid`, `name`, `entity`, `value`, `type`, `visible`, `note`, `tms`) VALUES
(3, 'MAILING_LIMIT_SENDBYWEB', 0, '25', 'chaine', 1, 'Number of targets to defined packet size when sending mass email', '2013-07-24 11:52:18'),
(4, 'SYSLOG_HANDLERS', 0, '["mod_syslog_file"]', 'chaine', 0, 'Which logger to use', '2013-07-24 11:52:18'),
(5, 'SYSLOG_FILE', 0, 'DOL_DATA_ROOT/dolibarr.log', 'chaine', 0, 'Directory where to write log file', '2013-07-24 11:52:18'),
(6, 'SYSLOG_LEVEL', 0, '7', 'chaine', 0, 'Level of debug info to show', '2013-07-24 11:52:18'),
(9, 'MAIN_UPLOAD_DOC', 0, '2048', 'chaine', 0, 'Max size for file upload (0 means no upload allowed)', '2013-07-24 11:52:18'),
(12, 'MAIN_SIZE_LISTE_LIMIT', 0, '25', 'chaine', 0, 'Longueur maximum des listes', '2013-07-24 11:52:18'),
(13, 'MAIN_SHOW_WORKBOARD', 0, '1', 'yesno', 0, 'Affichage tableau de bord de travail Dolibarr', '2013-07-24 11:52:18'),
(18, 'MAIN_DELAY_ACTIONS_TODO', 1, '7', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur actions planifiées non réalisées', '2013-07-24 11:52:18'),
(19, 'MAIN_DELAY_ORDERS_TO_PROCESS', 1, '2', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur commandes clients non traitées', '2013-07-24 11:52:18'),
(20, 'MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS', 1, '7', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur commandes fournisseurs non traitées', '2013-07-24 11:52:18'),
(21, 'MAIN_DELAY_PROPALS_TO_CLOSE', 1, '31', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur propales à cloturer', '2013-07-24 11:52:18'),
(22, 'MAIN_DELAY_PROPALS_TO_BILL', 1, '7', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur propales non facturées', '2013-07-24 11:52:18'),
(23, 'MAIN_DELAY_CUSTOMER_BILLS_UNPAYED', 1, '31', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur factures client impayées', '2013-07-24 11:52:18'),
(24, 'MAIN_DELAY_SUPPLIER_BILLS_TO_PAY', 1, '2', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur factures fournisseur impayées', '2013-07-24 11:52:18'),
(25, 'MAIN_DELAY_NOT_ACTIVATED_SERVICES', 1, '0', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur services à activer', '2013-07-24 11:52:18'),
(26, 'MAIN_DELAY_RUNNING_SERVICES', 1, '0', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur services expirés', '2013-07-24 11:52:18'),
(27, 'MAIN_DELAY_MEMBERS', 1, '31', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur cotisations adhérent en retard', '2013-07-24 11:52:18'),
(28, 'MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE', 1, '62', 'chaine', 0, 'Tolérance de retard avant alerte (en jours) sur rapprochements bancaires à faire', '2013-07-24 11:52:18'),
(29, 'MAIN_FIX_FOR_BUGGED_MTA', 1, '1', 'chaine', 1, 'Set constant to fix email ending from PHP with some linux ike system', '2013-07-24 11:52:18'),
(32, 'MAIN_VERSION_LAST_INSTALL', 0, '3.4.1', 'chaine', 0, 'Dolibarr version when install', '2013-07-24 11:52:57'),
(53, 'MAIN_INFO_SOCIETE_LOGO', 1, 'logo_bande_fr.png', 'chaine', 0, '', '2013-07-24 11:57:08'),
(54, 'MAIN_INFO_SOCIETE_LOGO_SMALL', 1, 'logo_bande_fr_small.png', 'chaine', 0, '', '2013-07-24 11:57:08'),
(55, 'MAIN_INFO_SOCIETE_LOGO_MINI', 1, 'logo_bande_fr_mini.png', 'chaine', 0, '', '2013-07-24 11:57:08'),
(68, 'COMPANY_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/thirdparties', 'chaine', 0, NULL, '2013-07-24 11:57:18'),
(70, 'PROPALE_ADDON_PDF', 1, 'azur', 'chaine', 0, 'Nom du gestionnaire de generation des propales en PDF', '2013-07-24 11:57:20'),
(71, 'PROPALE_ADDON', 1, 'mod_propale_marbre', 'chaine', 0, 'Nom du gestionnaire de numerotation des propales', '2013-07-24 11:57:20'),
(72, 'PROPALE_VALIDITY_DURATION', 1, '15', 'chaine', 0, 'Duration of validity of business proposals', '2013-07-24 11:57:20'),
(73, 'PROPALE_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/proposals', 'chaine', 0, NULL, '2013-07-24 11:57:20'),
(77, 'FACTURE_ADDON_PDF', 1, 'crabe', 'chaine', 0, 'Name of PDF model of invoice', '2013-07-24 11:57:33'),
(78, 'FACTURE_ADDON', 1, 'mod_facture_terre', 'chaine', 0, 'Name of numbering numerotation rules of invoice', '2013-07-24 11:57:33'),
(79, 'FACTURE_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/invoices', 'chaine', 0, NULL, '2013-07-24 11:57:33'),
(89, 'AGF_USE_STAGIAIRE_TYPE', 1, '', 'yesno', 0, 'Use trainee type', '2013-07-25 19:02:41'),
(90, 'AGF_DEFAULT_STAGIAIRE_TYPE', 1, '2', 'chaine', 0, 'Type of  trainee funding', '2013-07-25 19:02:41'),
(91, 'AGF_UNIVERSAL_MASK', 1, '', 'chaine', 0, 'Mask of training number ref', '2013-07-25 19:02:41'),
(92, 'AGF_ADDON', 1, 'mod_agefodd_simple', 'chaine', 0, 'Use simple mask for training ref', '2013-07-25 19:02:41'),
(93, 'AGF_ORGANISME_PREF', 1, '', 'chaine', 0, 'Prefecture d''enregistrement', '2013-07-25 19:02:41'),
(94, 'AGF_ORGANISME_NUM', 1, '', 'chaine', 0, 'Numerot d''enregistrement a la prefecture', '2013-07-25 19:02:41'),
(95, 'AGF_ORGANISME_REPRESENTANT', 1, '', 'chaine', 0, 'Representant de la societé de formation', '2013-07-25 19:02:41'),
(100, 'AGF_STAGTYPE_USE_SEARCH_TO_SELECT', 1, '', 'yesno', 0, 'Search stagiaire type with combobox', '2013-07-25 19:02:41'),
(106, 'AGF_LINK_OPCA_ADRR_TO_CONTACT', 1, '', 'yesno', 0, 'Display OPCA adress from OPCA contact rather than OPCA', '2013-07-25 19:02:41'),
(107, 'AGF_TEXT_COLOR', 1, '000000', 'chaine', 0, 'Text color of PDF in hexadecimal', '2013-07-25 19:02:41'),
(108, 'AGF_HEAD_COLOR', 1, 'CB4619', 'chaine', 0, 'Text color header in hexadecimal', '2013-07-25 19:02:41'),
(109, 'AGF_FOOT_COLOR', 1, 'BEBEBE', 'chaine', 0, 'Text color of PDF footer, in hexadccimal', '2013-07-25 19:02:41'),
(112, 'AGF_MANAGE_OPCA', 1, '1', 'yesno', 0, 'Manage Opca', '2013-07-25 19:02:41'),
(113, 'AGF_CERTIF_ADDON', 1, 'mod_agefoddcertif_simple', 'chaine', 0, 'Use simple mask for certif ref', '2013-07-25 19:02:41'),
(114, 'AGF_CERTIF_UNIVERSAL_MASK', 1, '', 'chaine', 0, 'Mask of certificate code', '2013-07-25 19:02:41'),
(120, 'COMMANDE_ADDON_PDF', 1, 'einstein', 'chaine', 0, 'Name of PDF model of order', '2013-07-25 19:02:41'),
(121, 'COMMANDE_ADDON', 1, 'mod_commande_marbre', 'chaine', 0, 'Name of numbering numerotation rules of order', '2013-07-25 19:02:41'),
(122, 'COMMANDE_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/orders', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(132, 'COMMANDE_SUPPLIER_ADDON_PDF', 1, 'muscadet', 'chaine', 0, 'Nom du gestionnaire de generation des bons de commande en PDF', '2013-07-25 19:02:41'),
(133, 'COMMANDE_SUPPLIER_ADDON_NUMBER', 1, 'mod_commande_fournisseur_muguet', 'chaine', 0, 'Nom du gestionnaire de numerotation des commandes fournisseur', '2013-07-25 19:02:41'),
(134, 'INVOICE_SUPPLIER_ADDON_PDF', 1, 'canelle', 'chaine', 0, 'Nom du gestionnaire de generation des factures fournisseur en PDF', '2013-07-25 19:02:41'),
(135, 'INVOICE_SUPPLIER_ADDON_NUMBER', 1, 'mod_facture_fournisseur_cactus', 'chaine', 0, 'Nom du gestionnaire de numerotation des factures fournisseur', '2013-07-25 19:02:41'),
(139, 'MAIN_AGENDA_ACTIONAUTO_COMPANY_CREATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(140, 'MAIN_AGENDA_ACTIONAUTO_CONTRACT_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(141, 'MAIN_AGENDA_ACTIONAUTO_PROPAL_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(142, 'MAIN_AGENDA_ACTIONAUTO_PROPAL_SENTBYMAIL', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(143, 'MAIN_AGENDA_ACTIONAUTO_ORDER_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(144, 'MAIN_AGENDA_ACTIONAUTO_ORDER_SENTBYMAIL', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(145, 'MAIN_AGENDA_ACTIONAUTO_BILL_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(146, 'MAIN_AGENDA_ACTIONAUTO_BILL_PAYED', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(147, 'MAIN_AGENDA_ACTIONAUTO_BILL_CANCEL', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(148, 'MAIN_AGENDA_ACTIONAUTO_BILL_SENTBYMAIL', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(149, 'MAIN_AGENDA_ACTIONAUTO_ORDER_SUPPLIER_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(150, 'MAIN_AGENDA_ACTIONAUTO_BILL_SUPPLIER_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(151, 'MAIN_AGENDA_ACTIONAUTO_SHIPPING_VALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(152, 'MAIN_AGENDA_ACTIONAUTO_SHIPPING_SENTBYMAIL', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(153, 'MAIN_AGENDA_ACTIONAUTO_BILL_UNVALIDATE', 1, '1', 'chaine', 0, NULL, '2013-07-25 19:02:41'),
(154, 'AGF_USE_LOGO_CLIENT', 1, '1', 'chaine', 0, '', '2013-07-25 19:02:50'),
(155, 'AGF_USE_FAC_WITHOUT_ORDER', 1, '1', 'chaine', 0, '', '2013-07-25 19:02:52'),
(156, 'AGF_CONTACT_DOL_SESSION', 1, '1', 'chaine', 0, '', '2013-07-25 19:02:57'),
(157, 'AGF_TRAINING_USE_SEARCH_TO_SELECT', 1, '1', 'chaine', 0, '', '2013-07-25 19:03:00'),
(158, 'AGF_TRAINER_USE_SEARCH_TO_SELECT', 1, '1', 'chaine', 0, '', '2013-07-25 19:03:00'),
(159, 'AGF_TRAINEE_USE_SEARCH_TO_SELECT', 1, '1', 'chaine', 0, '', '2013-07-25 19:03:01'),
(160, 'AGF_SITE_USE_SEARCH_TO_SELECT', 1, '1', 'chaine', 0, '', '2013-07-25 19:03:01'),
(161, 'AGF_DOL_AGENDA', 1, '1', 'chaine', 0, '', '2013-07-25 19:03:02'),
(162, 'COMPANY_USE_SEARCH_TO_SELECT', 1, '2', 'chaine', 0, '', '2013-07-25 19:03:24'),
(163, 'CONTACT_USE_SEARCH_TO_SELECT', 1, '2', 'chaine', 0, '', '2013-07-25 19:03:26'),
(165, 'SOCIETE_CODECOMPTA_ADDON', 1, 'mod_codecompta_panicum', 'chaine', 0, '', '2013-07-25 19:03:30'),
(166, 'AGF_CONTACT_USE_SEARCH_TO_SELECT', 1, '1', 'chaine', 0, '', '2013-07-25 19:05:13'),
(167, 'MAIN_USE_COMPANY_NAME_OF_CONTACT', 1, '1', 'chaine', 0, '', '2013-07-25 19:05:15'),
(168, 'AGF_FCKEDITOR_ENABLE_TRAINING', 1, '1', 'chaine', 0, '', '2013-07-25 19:05:19'),
(169, 'AGF_SESSION_TRAINEE_STATUS_AUTO', 1, '1', 'chaine', 0, '', '2013-07-25 19:05:24'),
(231, 'SOCIETE_CODECLIENT_ADDON', 1, 'mod_codeclient_elephant', 'chaine', 0, '', '2013-08-21 13:03:52'),
(249, 'PROJECT_ADDON_PDF', 1, 'baleine', 'chaine', 0, 'Nom du gestionnaire de generation des projets en PDF', '2013-08-23 12:32:47'),
(250, 'PROJECT_ADDON', 1, 'mod_project_simple', 'chaine', 0, 'Nom du gestionnaire de numerotation des projets', '2013-08-23 12:32:47'),
(251, 'PROJECT_ADDON_PDF_ODT_PATH', 1, 'DOL_DATA_ROOT/doctemplates/projects', 'chaine', 0, NULL, '2013-08-23 12:32:47'),
(269, 'PRODUIT_MULTIPRICES', 1, '1', 'chaine', 0, '', '2013-08-23 13:43:31'),
(274, 'MAILING_EMAIL_UNSUBSCRIBE', 1, '1', 'chaine', 0, '', '2013-08-23 14:37:46'),
(275, 'MAILING_EMAIL_FROM', 1, 'dolibarr@domain.com', 'chaine', 0, '', '2013-08-23 14:37:49'),
(276, 'MAILING_EMAIL_UNSUBSCRIBE_KEY', 1, '9203b218961d2941eeb534522e47b539', 'chaine', 0, '', '2013-08-23 14:37:49'),
(278, 'FCKEDITOR_ENABLE_SOCIETE', 1, '1', 'yesno', 0, 'WYSIWIG for description and note (except products/services)', '2013-08-23 14:38:53'),
(279, 'FCKEDITOR_ENABLE_PRODUCTDESC', 1, '1', 'yesno', 0, 'WYSIWIG for products/services description and note', '2013-08-23 14:38:53'),
(280, 'FCKEDITOR_ENABLE_MAILING', 1, '1', 'yesno', 0, 'WYSIWIG for mass emailings', '2013-08-23 14:38:53'),
(282, 'FCKEDITOR_ENABLE_USERSIGN', 1, '1', 'yesno', 0, 'WYSIWIG for products details lines for all entities', '2013-08-23 14:38:53'),
(283, 'FCKEDITOR_ENABLE_MAIL', 1, '1', 'yesno', 0, 'WYSIWIG for products details lines for all entities', '2013-08-23 14:38:53'),
(404, 'MAIN_MENU_STANDARD', 1, 'eldy_menu.php', 'chaine', 0, '', '2013-08-24 03:56:19'),
(405, 'MAIN_MENU_SMARTPHONE', 1, 'eldy_menu.php', 'chaine', 0, '', '2013-08-24 03:56:19'),
(406, 'MAIN_MENUFRONT_STANDARD', 1, 'eldy_menu.php', 'chaine', 0, '', '2013-08-24 03:56:19'),
(407, 'MAIN_MENUFRONT_SMARTPHONE', 1, 'eldy_menu.php', 'chaine', 0, '', '2013-08-24 03:56:19'),
(408, 'MAIN_INFO_SOCIETE_COUNTRY', 1, '1:FR:France', 'chaine', 0, '', '2013-08-24 04:03:49'),
(409, 'MAIN_INFO_SOCIETE_NOM', 1, 'Akteos', 'chaine', 0, '', '2013-08-24 04:03:49'),
(410, 'MAIN_INFO_SOCIETE_ADDRESS', 1, '6 Rue du 4 Septembre', 'chaine', 0, '', '2013-08-24 04:03:49'),
(411, 'MAIN_INFO_SOCIETE_TOWN', 1, 'Issy-les-Moulineaux', 'chaine', 0, '', '2013-08-24 04:03:49'),
(412, 'MAIN_INFO_SOCIETE_ZIP', 1, '92130', 'chaine', 0, '', '2013-08-24 04:03:49'),
(413, 'MAIN_INFO_SOCIETE_STATE', 1, '94', 'chaine', 0, '', '2013-08-24 04:03:49'),
(414, 'MAIN_MONNAIE', 1, 'EUR', 'chaine', 0, '', '2013-08-24 04:03:49'),
(415, 'MAIN_INFO_SOCIETE_TEL', 1, '01 55 95 85 10', 'chaine', 0, '', '2013-08-24 04:03:49'),
(416, 'MAIN_INFO_SOCIETE_FAX', 1, '01 55 95 85 11', 'chaine', 0, '', '2013-08-24 04:03:49'),
(417, 'MAIN_INFO_SOCIETE_MAIL', 1, 'conseil@akteos.fr', 'chaine', 0, '', '2013-08-24 04:03:49'),
(418, 'MAIN_INFO_SOCIETE_WEB', 1, 'http://www.akteos.fr', 'chaine', 0, '', '2013-08-24 04:03:49'),
(419, 'MAIN_INFO_CAPITAL', 1, '37000', 'chaine', 0, '', '2013-08-24 04:03:49'),
(420, 'MAIN_INFO_SOCIETE_FORME_JURIDIQUE', 1, '57', 'chaine', 0, '', '2013-08-24 04:03:49'),
(421, 'MAIN_INFO_SIREN', 1, '412056152', 'chaine', 0, '', '2013-08-24 04:03:49'),
(422, 'MAIN_INFO_SIRET', 1, ' 41205615200033 ', 'chaine', 0, '', '2013-08-24 04:03:49'),
(423, 'MAIN_INFO_APE', 1, '6420Z', 'chaine', 0, '', '2013-08-24 04:03:49'),
(424, 'MAIN_INFO_RCS', 1, 'Nanterre B 412 056 152 ', 'chaine', 0, '', '2013-08-24 04:03:49'),
(425, 'MAIN_INFO_TVAINTRA', 1, 'FR80412056152', 'chaine', 0, '', '2013-08-24 04:03:49'),
(426, 'SOCIETE_FISCAL_MONTH_START', 1, '1', 'chaine', 0, '', '2013-08-24 04:03:49'),
(427, 'FACTURE_TVAOPTION', 1, 'reel', 'chaine', 0, '', '2013-08-24 04:03:49'),
(428, 'COMPANY_ELEPHANT_MASK_CUSTOMER', 1, 'C{00000}', 'chaine', 0, '', '2013-08-24 04:04:39'),
(429, 'COMPANY_ELEPHANT_MASK_SUPPLIER', 1, 'F{00000}', 'chaine', 0, '', '2013-08-24 04:04:39'),
(437, 'MAIN_MENU_HIDE_UNAUTHORIZED', 1, '1', 'chaine', 1, '', '2013-08-26 13:09:10'),
(438, 'AGENDA_USE_EVENT_TYPE', 1, '1', 'chaine', 0, '', '2013-08-26 13:24:44'),
(439, 'MAIN_USE_ADVANCED_PERMS', 1, '1', 'chaine', 1, '', '2013-08-27 09:31:08'),
(440, 'SOCIETE_ADD_REF_IN_LIST', 1, '1', 'yesno', 0, '', '2013-08-27 19:06:27'),
(441, 'MAIN_MODULE_AGEFODD', 1, '1', NULL, 0, NULL, '2013-08-27 19:11:17'),
(442, 'MAIN_MODULE_AGEFODD_TABS_0', 1, 'order:+tabAgefodd:AgfMenuSess:agefodd@agefodd:/agefodd/session/list_fin.php?search_orderid=__ID__', 'chaine', 0, NULL, '2013-08-27 19:11:17'),
(443, 'MAIN_MODULE_AGEFODD_TABS_1', 1, 'invoice:+tabAgefodd:AgfMenuSess:agefodd@agefodd:/agefodd/session/list_fin.php?search_invoiceid=__ID__', 'chaine', 0, NULL, '2013-08-27 19:11:17'),
(444, 'MAIN_MODULE_AGEFODD_TABS_2', 1, 'propal:+tabAgefodd:AgfMenuSess:agefodd@agefodd:/agefodd/session/list_fin.php?search_propalid=__ID__', 'chaine', 0, NULL, '2013-08-27 19:11:17'),
(445, 'MAIN_MODULE_AGEFODD_TRIGGERS', 1, '1', 'chaine', 0, NULL, '2013-08-27 19:11:17'),
(446, 'AGF_LAST_VERION_INSTALL', 1, '2.1.5', 'chaine', 0, 'Last version installed to know change table to execute', '2013-08-27 19:11:17'),
(447, 'AGF_MANAGE_CERTIF', 1, '', 'yesno', 0, 'Manage certification', '2013-08-27 19:11:17'),
(449, 'MAIN_MODULE_PROPALE', 1, '1', NULL, 0, NULL, '2013-08-27 19:11:17'),
(453, 'MAIN_MODULE_COMPTABILITE', 1, '1', NULL, 0, NULL, '2013-08-27 19:11:17'),
(459, 'MAIN_MODULE_BANQUE', 1, '1', NULL, 0, NULL, '2013-08-27 19:11:18'),
(464, 'PRODUIT_MULTIPRICES_LIMIT', 1, '3', 'chaine', 0, '', '2013-08-31 18:54:58'),
(486, 'MAIN_MODULE_FCKEDITOR', 1, '1', NULL, 0, NULL, '2013-10-31 12:12:01'),
(487, 'FCKEDITOR_ENABLE_DETAILS', 1, '1', 'yesno', 0, 'WYSIWIG for products details lines for all entities', '2013-10-31 12:12:01'),
(489, 'MAIN_MODULE_EXPORT', 1, '1', NULL, 0, NULL, '2013-10-31 12:12:20'),
(490, 'COMPANY_HIDE_INACTIVE_IN_COMBOBOX', 1, '1', 'chaine', 0, '', '2013-10-31 12:13:36'),
(491, 'USER_HIDE_INACTIVE_IN_COMBOBOX', 1, '1', 'chaine', 0, '', '2013-10-31 12:13:45'),
(492, 'PRODUIT_DESC_IN_FORM', 1, '1', 'chaine', 1, '', '2013-10-31 12:15:02'),
(493, 'MAIN_LANG_DEFAULT', 1, 'auto', 'chaine', 0, '', '2013-10-31 12:15:19'),
(494, 'MAIN_MULTILANGS', 1, '1', 'chaine', 0, '', '2013-10-31 12:15:19'),
(495, 'MAIN_SIZE_LISTE_LIMIT', 1, '50', 'chaine', 0, '', '2013-10-31 12:15:19'),
(496, 'MAIN_DISABLE_JAVASCRIPT', 1, '0', 'chaine', 0, '', '2013-10-31 12:15:19'),
(497, 'MAIN_BUTTON_HIDE_UNAUTHORIZED', 1, '1', 'chaine', 0, '', '2013-10-31 12:15:19'),
(498, 'MAIN_START_WEEK', 1, '1', 'chaine', 0, '', '2013-10-31 12:15:19'),
(499, 'MAIN_SHOW_LOGO', 1, '0', 'chaine', 0, '', '2013-10-31 12:15:19'),
(500, 'MAIN_FIRSTNAME_NAME_POSITION', 1, '0', 'chaine', 0, '', '2013-10-31 12:15:19'),
(501, 'MAIN_THEME', 1, 'eldy', 'chaine', 0, '', '2013-10-31 12:15:19'),
(502, 'MAIN_SEARCHFORM_CONTACT', 1, '1', 'chaine', 0, '', '2013-10-31 12:15:19'),
(503, 'MAIN_SEARCHFORM_SOCIETE', 1, '1', 'chaine', 0, '', '2013-10-31 12:15:19'),
(504, 'MAIN_SEARCHFORM_PRODUITSERVICE', 1, '0', 'chaine', 0, '', '2013-10-31 12:15:19'),
(505, 'MAIN_SEARCHFORM_ADHERENT', 1, '0', 'chaine', 0, '', '2013-10-31 12:15:20'),
(506, 'MAIN_HELPCENTER_DISABLELINK', 0, '0', 'chaine', 0, '', '2013-10-31 12:15:20'),
(507, 'MAIN_HELP_DISABLELINK', 0, '0', 'chaine', 0, '', '2013-10-31 12:15:20'),
(508, 'MAIN_USE_PREVIEW_TABS', 1, '0', 'chaine', 0, '', '2013-10-31 12:15:20'),
(509, 'CONTACT_HIDE_INACTIVE_IN_COMBOBOX', 1, '1', 'chaine', 1, '', '2013-10-31 12:16:47'),
(510, 'MAIN_FEATURES_LEVEL', 0, '2', 'chaine', 1, 'Level of features to show (0=stable only, 1=stable+experimental, 2=stable+experimental+development', '2013-10-31 12:17:10'),
(511, 'COMPTA_ACCOUNT_CUSTOMER', 1, '445719', 'string', 0, '', '2013-10-31 13:30:07'),
(512, 'MAIN_MODULE_AGENDA', 1, '1', NULL, 0, NULL, '2013-11-05 09:16:16'),
(513, 'MAIN_MODULE_SOCIETE', 1, '1', NULL, 0, NULL, '2013-11-05 09:16:16'),
(514, 'MAIN_MODULE_SERVICE', 1, '1', NULL, 0, NULL, '2013-11-05 09:16:16'),
(515, 'MAIN_MODULE_COMMANDE', 1, '1', NULL, 0, NULL, '2013-11-05 09:16:16'),
(516, 'MAIN_MODULE_FACTURE', 1, '1', NULL, 0, NULL, '2013-11-05 09:16:16'),
(517, 'MAIN_MODULE_FOURNISSEUR', 1, '1', NULL, 0, NULL, '2013-11-05 09:16:16'),
(518, 'MAIN_MODULE_USER', 0, '1', NULL, 0, NULL, '2013-11-05 09:16:16'),
(519, 'MAIN_VERSION_LAST_UPGRADE', 0, '3.4.2', 'chaine', 0, 'Dolibarr version for last upgrade', '2013-11-05 09:19:03'),
(521, 'MAIN_MODULE_SYSLOG', 0, '1', NULL, 0, NULL, '2013-11-05 09:23:09'),
(524, 'MAIN_MAIL_SMTP_PORT', 0, '465', 'chaine', 0, '', '2013-11-05 09:47:29'),
(525, 'MAIN_MAIL_SMTP_SERVER', 0, 'smtp.googlemail.com', 'chaine', 0, '', '2013-11-05 09:47:29'),
(528, 'MAIN_MAIL_EMAIL_TLS', 0, '1', 'chaine', 0, '', '2013-11-05 09:47:30'),
(530, 'MAIN_DISABLE_ALL_MAILS', 1, '0', 'chaine', 0, '', '2013-11-05 10:34:55'),
(531, 'MAIN_MAIL_SENDMODE', 0, 'mail', 'chaine', 0, '', '2013-11-05 10:34:55'),
(534, 'MAIN_MAIL_EMAIL_FROM', 1, 'robot@domain.com', 'chaine', 0, '', '2013-11-05 10:34:55'),
(535, 'MAIN_MODULE_MAILING', 1, '1', NULL, 0, NULL, '2013-11-05 17:18:53'),
(536, 'MAIN_MODULE_CATEGORIE', 1, '1', NULL, 0, NULL, '2013-11-05 17:24:49'),
(537, 'MAIN_MODULE_CATEGORYCONTACT', 1, '1', NULL, 0, NULL, '2013-11-05 17:25:34'),
(538, 'MAIN_MODULE_CATEGORYCONTACT_TABS_0', 1, 'contact:+tabCategorie:Category:categories:/categorycontact/categorycontact/categorie.php?id=__ID__&type=4', 'chaine', 0, NULL, '2013-11-05 17:25:34'),
(539, 'MAIN_MODULE_CATEGORYCONTACT_MODELS', 1, '1', 'chaine', 0, NULL, '2013-11-05 17:25:34'),
(540, 'MAIN_MODULE_WEBSERVICES', 1, '1', NULL, 0, NULL, '2013-11-05 17:33:25'),
(541, 'WEBSERVICES_KEY', 1, '90c753e6c2919142caf7a63051ebc7ff', 'chaine', 0, '', '2013-11-05 17:33:30'),
(542, 'MAIN_MODULE_HOLIDAY', 1, '1', NULL, 0, NULL, '2013-11-05 17:34:48'),
(543, 'MAIN_MODULE_HOLIDAY_TABS_0', 1, 'user:+paidholidays:CPTitreMenu:holiday:$user->rights->holiday->write:/holiday/index.php?mainmenu=holiday&id=__ID__', 'chaine', 0, NULL, '2013-11-05 17:34:48'),
(544, 'MAIN_COMPANY_CONTROL_DBL', 1, '1', NULL, 0, NULL, '2013-11-05 17:24:49'),
(545, 'MAIN_CONTACT_CONTROL_DBL', 1, '1', NULL, 0, NULL, '2013-11-05 17:24:49');







UPDATE account SET modreg='CHQ' WHERE modreg='CH';
UPDATE account SET modreg='CHQ' WHERE modreg='CHG';
UPDATE account SET modreg='VIR' WHERE modreg='VRG';

UPDATE account SET tvaintracom= REPLACE(tvaintracom,'TVA Intracommunautaire : ','');
UPDATE account SET tvaintracom= REPLACE(tvaintracom,'TVA Intracommunautaire: ','');

UPDATE account SET pays=1 WHERE pays='FRANCE';
UPDATE account SET pays=5 WHERE pays='ALLEMAGNE';
UPDATE account SET pays=5 WHERE pays='GERMANY';
UPDATE account SET pays=184 WHERE pays='POLOGNE';
UPDATE account SET pays=3 WHERE pays='ITALIE';
UPDATE account SET pays=12 WHERE pays='MAROC';
UPDATE account SET pays=6 WHERE pays='SUISSE';
UPDATE account SET pays=7 WHERE pays='GRANDE BRETAGNE';
UPDATE account SET pays=7 WHERE pays='UNITED KINGDOM';
UPDATE account SET pays=115 WHERE pays='HONG KONG';
UPDATE account SET pays=85 WHERE pays='EGYPTE';
UPDATE account SET pays=201 WHERE pays='SLOVAQUIE';
UPDATE account SET pays=11 WHERE pays='USA';
UPDATE account SET pays=20 WHERE pays IN ('SWEDEN','SUEDE');
UPDATE account SET pays=17 WHERE pays='PAYS BAS';
UPDATE account SET pays=2 WHERE pays='BELGIQUE';
UPDATE account SET pays=123 WHERE pays='JAPAN';
UPDATE account SET pays=4 WHERE pays='ESPAGNE';
UPDATE account SET pays=9 WHERE pays='CHINE';
UPDATE account SET pays=1 WHERE pays='MARTINIQUE';
UPDATE account SET pays=14 WHERE pays='CANADA';
UPDATE account SET pays=11 WHERE pays='ETATS UNIS';
UPDATE account SET pays=227 WHERE pays='UNITED ARAB EMIRATES';
UPDATE account SET pays=188 WHERE pays='ROUMANIE';
UPDATE account SET pays=29 WHERE pays='SINGAPOUR';
UPDATE account SET pays=221 WHERE pays='TUIRQUIE';
UPDATE account SET pays=13 WHERE pays='ALGERIE';
UPDATE account SET pays=117 WHERE pays='INDE';
UPDATE account SET pays=17 WHERE pays='PAYS-BAS';
UPDATE account SET pays=7 WHERE pays='ENGLAND';
UPDATE account SET pays=7 WHERE pays IN ('GRANDE-BRETAGNE','UK');
UPDATE account SET pays=7 WHERE pays='ANGLETERRE';
UPDATE account SET pays=1 WHERE pays='FRA?CE';
UPDATE account SET pays=1 WHERE pays='FRABNCE';
UPDATE account SET pays=17 WHERE pays='PAYS - BAS';
UPDATE account SET pays=227 WHERE pays='EMIRATS ARABES UNIS';
UPDATE account SET pays=227 WHERE pays='EMIRATS ARABE UNIS';
UPDATE account SET pays=2 WHERE pays='BELGIUM';
UPDATE account SET pays=2 WHERE pays='BELGIQUE BELGIE';
UPDATE account SET pays=11 WHERE pays='ETATS UNIS USA';
UPDATE account SET pays=17 WHERE pays='HOLLAND';
UPDATE account SET pays=7 WHERE pays IN ('ROYAUME UNI','ROYAUME-UNI');
UPDATE account SET pays=6 WHERE pays='SWITZERLAND';
UPDATE account SET pays=80 WHERE pays='DANEMARK';
UPDATE account SET pays=140 WHERE pays='LUXEMBOURG';
UPDATE account SET pays=35 WHERE pays='ANGOLA';
UPDATE account SET pays=81 WHERE pays='DJIBOUTI';
UPDATE account SET pays=27 WHERE pays='MONACO';
UPDATE account SET pays=28 WHERE pays='AUSTRALIE';
UPDATE account SET pays=56 WHERE pays='BRESIL';
UPDATE account SET pays=143 WHERE pays='MADAGASCAR';
UPDATE account SET pays=25 WHERE pays='PORTUGAL';
UPDATE account SET pays=22 WHERE pays='SENEGAL';
UPDATE account SET pays=10 WHERE pays='TUNISIE';
UPDATE account SET pays=79 WHERE pays IN ('CZECH REPUBLIC','CSECH REPUBLIC','REPUBLIQUE TCHEQUE');
UPDATE account SET pays=221 WHERE pays='TURQUIE';
UPDATE account SET pays=41 WHERE pays='AUTRICHE';
UPDATE account SET pays=21 WHERE pays='Côte d''Ivoire';
UPDATE account SET pays=24 WHERE pays='CAMEROUN';
UPDATE account SET pays=17 WHERE pays='THE NETHERLANDS';
UPDATE account SET pays=1 WHERE pays='ANGERS CEDEX 01';
UPDATE account SET pays=29 WHERE pays='SINGAPORE';
UPDATE account SET pays=117 WHERE pays='INDIA';
UPDATE account SET pays=3 WHERE pays IN ('ITALY','ITALIA');
UPDATE account SET pays=94 WHERE pays='FINLANDE';
UPDATE account SET pays=80 WHERE pays='DENMARK (DANEMARK)';
UPDATE account SET pays=1 WHERE pays='FRANCE Cedex 01';
UPDATE account SET pays=102 WHERE pays='GREECE';
UPDATE account SET pays=102 WHERE pays='GRECE';
UPDATE account SET pays=19 WHERE pays IN ('RUSSIA','RUSSIE');
UPDATE account SET pays=216 WHERE pays='THAILANDE';
UPDATE account SET pays=124 WHERE pays='JORDANIE';
UPDATE account SET pays=134 WHERE pays='LIBAN';
UPDATE account SET pays=173 WHERE pays='NORWAY';
UPDATE account SET pays=129 WHERE pays='COREE DU SUD';
UPDATE account SET pays=44 WHERE pays='BAHREIN';
UPDATE account SET pays=18 WHERE pays='HONGRIE';
UPDATE account SET pays=78 WHERE pays='CYPRUS';
UPDATE account SET pays=165 WHERE pays='NOUVELLE CALEDONIE';
UPDATE account SET pays=NULL WHERE pays='';
UPDATE account SET pays=1 WHERE pays='MONTAUBAN DE BRETAGN';

UPDATE interv SET pays=1 WHERE pays='FRANCE';
UPDATE interv SET pays=5 WHERE pays='ALLEMAGNE';
UPDATE interv SET pays=5 WHERE pays='GERMANY';
UPDATE interv SET pays=184 WHERE pays='POLOGNE';
UPDATE interv SET pays=3 WHERE pays='ITALIE';
UPDATE interv SET pays=12 WHERE pays='MAROC';
UPDATE interv SET pays=6 WHERE pays='SUISSE';
UPDATE interv SET pays=7 WHERE pays='GRANDE BRETAGNE';
UPDATE interv SET pays=7 WHERE pays='UNITED KINGDOM';
UPDATE interv SET pays=115 WHERE pays IN ('HONG KONG','HONG-KONG');
UPDATE interv SET pays=85 WHERE pays='EGYPTE';
UPDATE interv SET pays=201 WHERE pays='SLOVAQUIE';
UPDATE interv SET pays=11 WHERE pays='USA';
UPDATE interv SET pays=20 WHERE pays IN ('SWEDEN','SUEDE');
UPDATE interv SET pays=17 WHERE pays='PAYS BAS';
UPDATE interv SET pays=2 WHERE pays='BELGIQUE';
UPDATE interv SET pays=123 WHERE pays='JAPAN';
UPDATE interv SET pays=4 WHERE pays='ESPAGNE';
UPDATE interv SET pays=9 WHERE pays='CHINE';
UPDATE interv SET pays=1 WHERE pays='MARTINIQUE';
UPDATE interv SET pays=14 WHERE pays='CANADA';
UPDATE interv SET pays=11 WHERE pays='ETATS UNIS';
UPDATE interv SET pays=227 WHERE pays='UNITED ARAB EMIRATES';
UPDATE interv SET pays=188 WHERE pays='ROUMANIE';
UPDATE interv SET pays=29 WHERE pays='SINGAPOUR';
UPDATE interv SET pays=221 WHERE pays='TUIRQUIE';
UPDATE interv SET pays=13 WHERE pays='ALGERIE';
UPDATE interv SET pays=117 WHERE pays='INDE';
UPDATE interv SET pays=17 WHERE pays='PAYS-BAS';
UPDATE interv SET pays=7 WHERE pays='ENGLAND';
UPDATE interv SET pays=7 WHERE pays IN ('GRANDE-BRETAGNE','UK');
UPDATE interv SET pays=7 WHERE pays='ANGLETERRE';
UPDATE interv SET pays=1 WHERE pays='FRA?CE';
UPDATE interv SET pays=1 WHERE pays='FRABNCE';
UPDATE interv SET pays=17 WHERE pays='PAYS - BAS';
UPDATE interv SET pays=227 WHERE pays='EMIRATS ARABES UNIS';
UPDATE interv SET pays=227 WHERE pays='EMIRATS ARABE UNIS';
UPDATE interv SET pays=2 WHERE pays='BELGIUM';
UPDATE interv SET pays=2 WHERE pays='BELGIQUE BELGIE';
UPDATE interv SET pays=11 WHERE pays='ETATS UNIS USA';
UPDATE interv SET pays=17 WHERE pays='HOLLAND';
UPDATE interv SET pays=7 WHERE pays IN ('ROYAUME UNI','ROYAUME-UNI','United  Kingdom','UK');
UPDATE interv SET pays=6 WHERE pays='SWITZERLAND';
UPDATE interv SET pays=80 WHERE pays='DANEMARK';
UPDATE interv SET pays=140 WHERE pays='LUXEMBOURG';
UPDATE interv SET pays=35 WHERE pays='ANGOLA';
UPDATE interv SET pays=81 WHERE pays='DJIBOUTI';
UPDATE interv SET pays=27 WHERE pays='MONACO';
UPDATE interv SET pays=28 WHERE pays='AUSTRALIE';
UPDATE interv SET pays=56 WHERE pays='BRESIL';
UPDATE interv SET pays=143 WHERE pays='MADAGASCAR';
UPDATE interv SET pays=25 WHERE pays='PORTUGAL';
UPDATE interv SET pays=22 WHERE pays='SENEGAL';
UPDATE interv SET pays=10 WHERE pays='TUNISIE';
UPDATE interv SET pays=79 WHERE pays IN ('CZECH REPUBLIC','CSECH REPUBLIC','REPUBLIQUE TCHEQUE');
UPDATE interv SET pays=221 WHERE pays='TURQUIE';
UPDATE interv SET pays=41 WHERE pays='AUTRICHE';
UPDATE interv SET pays=21 WHERE pays='Côte d''Ivoire';
UPDATE interv SET pays=24 WHERE pays='CAMEROUN';
UPDATE interv SET pays=17 WHERE pays='THE NETHERLANDS';
UPDATE interv SET pays=1 WHERE pays='ANGERS CEDEX 01';
UPDATE interv SET pays=29 WHERE pays='SINGAPORE';
UPDATE interv SET pays=117 WHERE pays='INDIA';
UPDATE interv SET pays=3 WHERE pays IN ('ITALY','ITALIA');
UPDATE interv SET pays=94 WHERE pays='FINLANDE';
UPDATE interv SET pays=80 WHERE pays='DENMARK (DANEMARK)';
UPDATE interv SET pays=1 WHERE pays='FRANCE Cedex 01';
UPDATE interv SET pays=102 WHERE pays='GREECE';
UPDATE interv SET pays=102 WHERE pays='GRECE';
UPDATE interv SET pays=19 WHERE pays IN ('RUSSIA','RUSSIE');
UPDATE interv SET pays=216 WHERE pays='THAILANDE';
UPDATE interv SET pays=124 WHERE pays='JORDANIE';
UPDATE interv SET pays=134 WHERE pays='LIBAN';
UPDATE interv SET pays=173 WHERE pays IN ('NORWAY','NORVEGE');
UPDATE interv SET pays=129 WHERE pays='COREE DU SUD';
UPDATE interv SET pays=44 WHERE pays='BAHREIN';
UPDATE interv SET pays=18 WHERE pays='HONGRIE';
UPDATE interv SET pays=78 WHERE pays='CYPRUS';
UPDATE interv SET pays=165 WHERE pays='NOUVELLE CALEDONIE';
UPDATE interv SET pays=NULL WHERE pays='';
UPDATE interv SET pays=121 WHERE pays='ISRAEL';
UPDATE interv SET pays=123 WHERE pays='JAPON';
UPDATE interv SET pays=213 WHERE pays='TAIWAN';
UPDATE interv SET pays=118 WHERE pays='INDONESIE';
UPDATE interv SET pays=154 WHERE pays='MEXIQUE';
UPDATE interv SET pays=166 WHERE pays='NOUVELLE ZELANDE';
UPDATE interv SET pays=1 WHERE pays='MONTAUBAN DE BRETAGN';


UPDATE contact SET titre='MR' WHERE titre='M.';
UPDATE contact SET titre='MME' WHERE titre='Mme';
UPDATE contact SET titre='MLE' WHERE titre='Mlle';

UPDATE interv SET civilite='MR' WHERE civilite='M.';
UPDATE interv SET civilite='MME' WHERE civilite='Mme';
UPDATE interv SET civilite='MLE' WHERE civilite='Mlle';

UPDATE eleves SET civilite='MR' WHERE civilite='M.';
UPDATE eleves SET civilite='MME' WHERE civilite='Mme';
UPDATE eleves SET civilite='MLE' WHERE civilite='Mlle';


--Affect Charle de rostand old user to new user
UPDATE account set com_id='dfbfc33c-039f-102c-b0fb-001aa0790251' WHERE com_id='dfbfc922-039f-102c-b0fb-001aa0790251';
--Set user email to get all coherent data
UPDATE sf_user SET email_address='mclement@akteos.fr' where id='c84283c0-039f-102c-b0fb-001aa0790251'

SET foreign_key_checks = 0;
TRUNCATE TABLE llx_user;
INSERT INTO `llx_user` (`rowid`, `entity`, `ref_ext`, `ref_int`, `datec`, `tms`, `login`, `pass`, `pass_crypted`, `pass_temp`, `civilite`, `lastname`, `firstname`, `address`, `zip`, `town`, `fk_state`, `fk_country`, `job`, `office_phone`, `office_fax`, `user_mobile`, `email`, `signature`, `admin`, `module_comm`, `module_compta`, `fk_societe`, `fk_socpeople`, `fk_member`, `fk_user`, `note`, `datelastlogin`, `datepreviouslogin`, `egroupware_id`, `ldap_sid`, `openid`, `statut`, `photo`, `lang`, `color`, `import_key`) VALUES
(1, 0, NULL, NULL, '2013-10-20 14:48:56', '2013-10-20 12:48:57', 'admin_akteos', 'yc382tsz', 'abda7c036c49e6a511efe382b279ecae', NULL, NULL, 'SuperAdmin', '', '', '', '', NULL, NULL, '', '', '', '', '', '', 1, 1, 1, NULL, NULL, NULL, NULL, '', '2013-11-05 12:43:39', '2013-11-05 11:51:25', NULL, '', NULL, 1, NULL, NULL, NULL, NULL),
(2, 1, NULL, NULL, '2011-05-01 09:34:56', '2013-10-20 13:24:53', 'cmigeot', 'test', NULL, NULL, NULL, 'MIGEOT', 'Caroline', '', '', '', NULL, NULL, 'Assistante', '01 55 95 85 17', '01 55 95 85 11', '', 'cmigeot@akteos.fr', '', 0, 0, 0, NULL, NULL, NULL, 11, '', '2013-09-25 08:24:31', '2013-09-02 07:49:50', NULL, NULL, NULL, 1, NULL, NULL, NULL, 'c1b16bb9-49a6-401b-acd2-2945df7c0962'),
(3, 1, NULL, NULL, '2009-08-07 13:25:20', '2013-11-02 08:00:32', 'jandrian', 'test', NULL, NULL, NULL, 'ANDRIAN', 'Janieva', '', '', '', NULL, NULL, 'Chargée de formation', '01 55 95 85 13', '01 55 95 85 11', '', 'jandrian@akteos.fr', 'Bien cordialement<br />\r\n<br />\r\nJanieva ANDRIAN<br />\r\n+33 (0)1 55 95 85 13<br />\r\n<br />\r\n<table border="0" cellpadding="0" cellspacing="0">\r\n	<tbody>\r\n		<tr>\r\n			<td style="width:66px;">\r\n				<a href="http://www.akteos.fr/"><img alt="Description&nbsp;: LOGO AKTEOS - Courrier electronique extra petit (48x44)_sans phrase" border="0" height="44" id="Image_x0020_4" src="cid:image001.jpg@01CED555.AE23A0B0" width="48" /></a></td>\r\n			<td style="width:255px;">\r\n				Le Leader du Management Interculturel<br />\r\n				6, rue du Quatre Septembre<br />\r\n				F - 92130 Issy les Moulineaux<br />\r\n				<a href="http://www.akteos.fr/">www.akteos.fr</a></td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n&nbsp;<br />', 0, 0, 0, NULL, NULL, NULL, 8, '', '2013-09-21 10:29:04', '2013-08-24 21:23:35', NULL, NULL, NULL, 1, NULL, NULL, NULL, '615d09b2-66e3-4c64-98dd-4c8130f7b975'),
(4, 1, NULL, NULL, '2010-05-06 17:57:52', '2013-10-20 13:24:53', 'ldarrieux', 'test', NULL, NULL, NULL, 'DARRIEUX', 'Laurence', '', '', '', NULL, NULL, 'Chef de projet', '01 55 95 84 66', '01 55 95 85 11', '', 'ldarrieux@akteos.fr', '', 0, 0, 0, NULL, NULL, NULL, 8, '', '2013-09-02 07:43:18', NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, '6d381997-0fee-4a14-9e39-654fb71208e6'),
(5, 1, NULL, NULL, '2013-02-07 17:32:57', '2013-08-27 13:56:43', 'mclement', 'test', NULL, NULL, NULL, 'CLEMENT', 'Mehdi', '', '', '', NULL, NULL, 'Attaché commercial', '01 55 95 84 65', '', '', 'mclement@akteos.fr', '', 0, 0, 0, NULL, NULL, NULL, 8, '', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 'c84283c0-039f-102c-b0fb-001aa0790251'),
(6, 1, NULL, NULL, '2012-03-01 11:55:18', '2013-10-20 13:24:53', 'dnguyen', 'test', NULL, NULL, NULL, 'NGUYEN', 'David', '', '', '', NULL, NULL, 'Attaché commercial', '01 55 95 84 69', '01 55 95 85 11', '', 'dnguyen@akteos.fr', '', 0, 0, 0, NULL, NULL, NULL, 8, '', '2013-10-30 09:47:36', '2013-08-21 17:02:14', NULL, NULL, NULL, 1, NULL, NULL, NULL, '0b9cc699-eb5f-4000-a84b-6a55a84a50be'),
(8, 1, NULL, NULL, NULL, '2013-11-01 21:31:12', 'crostand', 'test', NULL, NULL, NULL, 'ROSTAND', 'Charles', '', '', '', NULL, NULL, 'Directeur Général', '01 55 95 85 10', '01 55 95 85 11', '06 80 26 18 81', 'crostand@akteos.fr', 'Bien cordialement<br />\r\n<br />\r\nCharles Rostand<br />\r\nDirecteur G&eacute;n&eacute;ral<br />', 1, 0, 0, NULL, NULL, NULL, NULL, '', '2013-08-29 08:17:11', NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 'c8428226-039f-102c-b0fb-001aa0790251'),
(10, 1, NULL, NULL, NULL, '2013-10-20 13:24:53', 'lrostand', 'test', NULL, NULL, NULL, 'ROSTAND', 'Laure', '', '', '', NULL, NULL, 'Présidente', '01 55 95 85 15', '01 55 95 85 16', '06 60 34 31 10', 'lrostand@akteos.fr', 'Bien cordialement<br />\r\n<br />\r\nLaure Rostand<br />\r\nPr&eacute;sidente<br />\r\n<br />\r\n01 55 95 85 15<br />\r\n06 60 34 31 10', 1, 0, 0, NULL, NULL, NULL, NULL, '', '2013-11-04 23:49:27', '2013-11-04 23:30:46', NULL, NULL, NULL, 1, NULL, NULL, NULL, 'c8427bb4-039f-102c-b0fb-001aa0790251'),
(11, 1, NULL, NULL, NULL, '2013-10-20 13:24:53', 'phabourdin', 'test', NULL, NULL, NULL, 'HABOURDIN', 'Pascale', '', '', '', NULL, NULL, 'Chef de Projet', '01 55 95 85 12', '01 55 95 85 11', '', 'phabourdin@akteos.fr', '', 0, 0, 0, NULL, NULL, NULL, 8, '', '2013-10-25 14:53:40', '2013-10-25 14:52:03', NULL, NULL, NULL, 1, NULL, NULL, NULL, 'c8428078-039f-102c-b0fb-001aa0790251'),
(13, 1, NULL, NULL, '2013-08-24 08:50:48', '2013-11-19 21:41:30', 'hlefebvre', 'test', '098f6bcd4621d373cade4e832627b4f6', NULL, NULL, 'LEFEBVRE', 'Hélène', '', '', '', NULL, NULL, 'Assitante', '', '', '', 'hlefebvre@akteos.fr', '', 0, 1, 1, NULL, NULL, NULL, 4, '', NULL, NULL, NULL, '', NULL, 1, NULL, NULL, NULL, '5a6c9f40-9e93-48ae-9298-cf5e0c5b104f'),
(14, 1, NULL, NULL, '2013-08-24 08:52:11', '2013-11-19 21:41:43', 'cmontaud', 'test', '098f6bcd4621d373cade4e832627b4f6', NULL, NULL, 'MONTAUD', 'Claire', '', '', '', NULL, NULL, 'Assistante', '', '', '', 'cmontaud@akteos.fr', '', 0, 1, 1, NULL, NULL, NULL, 8, '', '2013-09-02 08:11:24', '2013-09-02 07:45:33', NULL, '', NULL, 1, NULL, NULL, NULL, '1670409a-198c-4d9a-86f9-e0d0fbf9d6cb'),
(15, 1, NULL, NULL, '2013-08-24 08:53:33', '2013-11-19 21:41:55', 'ctesson', 'test', '098f6bcd4621d373cade4e832627b4f6', NULL, NULL, 'TESSON', 'Cécile', '', '', '', NULL, NULL, 'Chargée de communication', '', '', '', 'ctesson@akteos.fr', '', 0, 1, 1, NULL, NULL, NULL, 10, '', '2013-09-02 07:42:19', '2013-09-01 23:00:32', NULL, '', NULL, 1, NULL, NULL, NULL, 'ae0039da-2646-4883-bd4f-637a1e4744b4'),
(16, 1, NULL, NULL, '2010-09-07 16:09:53', '2010-09-07 14:10:03', 'EVM', 'test', NULL, NULL, NULL, 'Evenements', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '01718fa8-1e9a-4b1f-bf7d-cf9e3729cf53'),
(17, 1, NULL, NULL, '2009-04-27 18:28:26', '2010-04-14 17:37:21', 'CB', 'test', NULL, NULL, NULL, 'Borlet', 'Christophe', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cborlet@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '0dc9343b-6fa3-4c2b-bf7b-da4c55c08952'),
(18, 1, NULL, NULL, '2011-03-22 09:21:11', '2011-08-31 14:17:12', 'NOMAD', 'test', NULL, NULL, NULL, 'Nomad', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nomad@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2225e4d8-128a-45a0-85a0-0ba49a0708f4'),
(19, 1, NULL, NULL, '2011-05-01 09:10:49', '2012-07-26 13:21:47', 'NF', 'test', NULL, NULL, NULL, 'Forté', 'Nathalie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nforte@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '283f2798-80cb-49f7-a9be-513dfefdd0bd'),
(20, 1, NULL, NULL, '2011-03-08 17:16:23', '2011-08-31 14:16:47', 'CONSEIL', 'test', NULL, NULL, NULL, 'Conseil', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'conseil@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '373377aa-daff-468e-875a-9662a2dbfaa6'),
(21, 1, NULL, NULL, '2010-04-14 19:39:22', '2010-09-08 14:50:57', 'JBI', 'test', NULL, NULL, NULL, 'Bidou', 'Julie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'jbidou@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '58f5ca7a-8dc8-4bd4-b5d1-1be0572706a1'),
(22, 1, NULL, NULL, '2009-09-01 12:35:28', '2010-04-14 17:37:14', 'EH', 'test', NULL, NULL, NULL, 'Hauw', 'Elisabeth', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '6f70587b-8f8f-4b02-827e-168a097f56b9'),
(23, 1, NULL, NULL, '2011-03-10 19:15:46', '2011-08-31 14:16:35', 'AKTEOS', 'test', NULL, NULL, NULL, 'Akteos', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'akteos@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '979c21e3-6ad2-45c0-82c0-76133a63a596'),
(24, 1, NULL, NULL, '2009-11-22 21:33:41', '2010-09-08 14:50:50', 'JB', 'test', NULL, NULL, NULL, 'Bons', 'Julien', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'jbons@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'a32f81fb-6a39-4e71-8d15-6f4969fc1187'),
(25, 1, NULL, NULL, '2011-01-11 15:42:01', '2011-03-28 13:40:45', 'WC', 'test', NULL, NULL, NULL, 'Cabrera', 'William', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'wcabrera@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'a72692dd-ce6e-4835-8f6b-e3697bb2f240'),
(26, 1, NULL, NULL, '2010-11-02 08:52:37', '2011-01-11 14:41:43', 'KG', 'test', NULL, NULL, NULL, 'Gazo', 'Kevin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'kgazo@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'a77d7323-0c45-455f-abba-d89c1d7a61b8'),
(27, 1, NULL, NULL, NULL, '2011-08-31 14:17:28', 'SIE', 'test', NULL, NULL, NULL, 'Admin', 'Administrateur', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'c8427b3c-039f-102c-b0fb-001aa0790251'),
(28, 1, NULL, NULL, NULL, '2010-09-08 14:51:03', 'PG', 'test', NULL, NULL, NULL, 'Yon', 'Anne-Catherine', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'acyon@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'c8428000-039f-102c-b0fb-001aa0790251'),
(29, 1, NULL, NULL, NULL, '2010-09-08 14:51:23', 'VH', 'test', NULL, NULL, NULL, 'Sok', 'Many', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'msok@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'c84280e6-039f-102c-b0fb-001aa0790251'),
(30, 1, NULL, NULL, NULL, '2012-02-08 09:49:39', 'BC', 'test', NULL, NULL, NULL, 'de Carné', 'Bérengère', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bdecarne@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'c8428154-039f-102c-b0fb-001aa0790251'),
(31, 1, NULL, NULL, NULL, '2010-04-14 17:36:57', 'JS', 'test', NULL, NULL, NULL, 'Slowikowska', 'Joanna', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'c84281b8-039f-102c-b0fb-001aa0790251'),
(32, 1, NULL, NULL, NULL, '2010-04-14 17:36:48', 'MM', 'test', NULL, NULL, NULL, 'Merchiakh', 'Marie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'c842828a-039f-102c-b0fb-001aa0790251'),
(33, 1, NULL, NULL, NULL, '2010-04-14 17:37:02', 'JL', 'test', NULL, NULL, NULL, 'Legodec', 'Julie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'c842835c-039f-102c-b0fb-001aa0790251'),
(34, 1, NULL, NULL, NULL, '2010-04-14 17:40:14', 'NB', 'test', NULL, NULL, NULL, 'Nadia', 'Ben Bella', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'c842842e-039f-102c-b0fb-001aa0790251'),
(35, 1, NULL, NULL, NULL, '2011-08-31 14:17:40', 'TC', 'test', NULL, NULL, NULL, 'Cordalija', 'Tima', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tcordalija@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'c8428492-039f-102c-b0fb-001aa0790251'),
(36, 1, NULL, NULL, '2009-10-19 12:43:43', '2010-04-14 17:36:31', 'RC', 'test', NULL, NULL, NULL, 'Canet', 'Romain', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'ea94a3e2-4aac-4686-bfdb-45d620a7d23f'),
(37, 1, NULL, NULL, '2012-06-10 16:23:15', '2012-07-26 13:21:53', 'MG', 'test', NULL, NULL, NULL, 'Gripon', 'Mathieu', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'mgripon@akteos.fr', NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'fffcb945-ee3f-4ac1-861d-bd33112cf57b');


--Insert inactive user

/*

UPDATE llx_user as doluser, sf_user as stuser SET  doluser.import_key=stuser.id where doluser.email=stuser.email_address;
INSERT INTO llx_user (
entity,
ref_ext,
ref_int,
datec,
tms,
login,
pass,
pass_crypted,
pass_temp,
civilite,
lastname,
firstname,
address,
zip,
town,
fk_state,
fk_country,
job,
office_phone,
office_fax,
user_mobile,
email,
signature,
admin,
module_comm,
module_compta,
fk_societe,
fk_socpeople,
fk_member,
fk_user,
note,
datelastlogin,
datepreviouslogin,
egroupware_id,
ldap_sid,
openid,
statut,
photo,
lang,
color,
import_key) 
SELECT DISTINCT
1,--entity,
sf_user.external_ref, --ref_ext,
NULL, --ref_int,
sf_user.created, --datec,
sf_user.modified, --tms,
sf_user.login, --login,
'test', --pass,
NULL,  --pass_crypted,
NULL, --pass_temp,
NULL, --civilite,
sf_user.lastname, --lastname,
sf_user.firstname,--firstname,
NULL, --address,
NULL, --zip,
NULL, --town,
NULL, --fk_state,
NULL, --fk_country,
NULL, --job,
NULL, --office_phone,
NULL, --office_fax,
NULL, --user_mobile,
TRIM(sf_user.email_address), --email,
NULL, --signature,
0, --admin,
0, --module_comm,
0, --module_compta,
NULL, --fk_societe,
NULL, --fk_socpeople,
NULL, --fk_member,
NULL, --fk_user,
NULL, --note,
NULL, --datelastlogin,
NULL, --datepreviouslogin,
NULL, --egroupware_id,
NULL, --ldap_sid,
NULL, --openid,
0, --statut,
NULL, --photo,
NULL, --lang,
NULL, --color,
sf_user.id -- import_key
FROM sf_user 
WHERE sf_user.state='disabled';
*/



--Import Category

TRUNCATE TABLE `llx_categorie`;
INSERT INTO `llx_categorie` (`rowid`, `entity`, `fk_parent`, `label`, `type`, `description`, `fk_soc`, `visible`, `import_key`) VALUES
(1, 1, 0, 'Produit', 0, 'Tous les produits vendus par Akteos, &agrave; l&#39;exception des frais', NULL, 0, NULL),
(3, 1, 0, 'Frais', 0, 'Tous les frais g&eacute;n&eacute;r&eacute;s par les interventions d&#39;Akteos (d&eacute;pacement, h&eacute;bergement, restauration, salle,...) &agrave; l&#39;exception des consultants, et refactur&eacute;s aux clients (en g&eacute;n&eacute;ral &agrave; l&#39;euro pr&egrave;s).', NULL, 0, NULL),
(6, 1, 1, 'Produit pédagogique', 0, 'Tous les produits en rapport avec les formations &agrave; prendre en consid&eacute;ration pour le Bilan P&eacute;dagogique et Financier', NULL, 0, NULL),
(7, 1, 1, 'Produit non pédagogique', 0, 'Tous les produits qui ne sont pas consid&eacute;r&eacute;s comme de la formation et ne sont pas comptabilis&eacute;s dans le Bilan P&eacute;dagogique et Financier', NULL, 0, NULL),
(8, 1, 6, 'Conception', 0, '', NULL, 0, NULL),
(9, 1, 6, 'Intervention', 0, '', NULL, 0, NULL),
(10, 1, 6, 'Outil pédagogique', 0, '', NULL, 0, NULL),
(30, 1, 31, 'Particulier', 4, '', NULL, 0, NULL),
(12, 1, 0, 'Consultant', 1, '', NULL, 0, NULL),
(15, 1, 12, 'Consultant Etranger', 1, '', NULL, 0, NULL),
(16, 1, 12, 'Consultant France', 1, '', NULL, 0, NULL),
(17, 1, 12, 'Consultant Suppléant', 1, '', NULL, 0, NULL),
(28, 1, 31, 'Salarié bénéficiant d''un financement par l''employeur', 4, '', NULL, 0, NULL),
(21, 1, 0, 'Logistique', 1, 'Hotel, Restaurant, Agence de voyage, Imprimeur,...', NULL, 0, NULL),
(22, 1, 0, 'Communication', 1, '', NULL, 0, NULL),
(23, 1, 1, 'Produit HT', 0, '', NULL, 0, NULL),
(24, 1, 6, 'On Line', 0, '', NULL, 0, NULL),
(25, 1, 3, 'Frais HT', 0, '', NULL, 0, NULL),
(29, 1, 31, 'Demandeur d''emploi', 4, '', NULL, 0, NULL),
(31, 1, 0, 'Participant', 4, '', NULL, 0, NULL),
(32, 1, 0, 'Consultant', 4, '', NULL, 0, NULL),
(33, 1, 32, 'Travailleur indépendant', 4, '', NULL, 0, NULL),
(34, 1, 32, 'CDI', 4, '', NULL, 0, NULL),
(35, 1, 32, 'CDD', 4, '', NULL, 0, NULL),
(36, 1, 32, 'Formateur occasionnel', 4, '', NULL, 0, NULL),
(37, 1, 32, 'Bénévole', 4, '', NULL, 0, NULL);



TRUNCATE TABLE llx_c_typent;
INSERT INTO `llx_c_typent` (`id`, `code`, `libelle`, `active`, `module`) VALUES
(0, 'TE_UNKNOWN', '-', 1, NULL),
(1, 'TE_STARTUP', 'Start-up', 0, NULL),
(2, 'TE_GROUP', 'Entreprise française', 0, NULL),
(3, 'TE_PAY', 'Payeur', 1, NULL),
(4, 'TE_SMALL', 'TPE', 0, NULL),
(5, 'TE_ADMIN', 'Pouvoirs publics', 1, NULL),
(6, 'TE_WHOLE', 'Grossiste', 0, NULL),
(7, 'TE_RETAIL', 'Revendeur', 0, NULL),
(8, 'TE_PRIVATE', 'Particulier', 1, NULL),
(100, 'TE_OTHER', 'Autres', 0, NULL),
(101, 'TE_ENT_FR', 'Entreprise française', 1, NULL),
(102, 'TE_ENT_ET', 'Entreprise étrangère', 1, NULL),
(103, 'TE_OPCA', 'OPCA', 1, NULL),
(104, 'TE_FORMATION', 'Prestataire de formation', 1, NULL);


--Insert customer typed account into thridparty
TRUNCATE TABLE  llx_societe;
INSERT INTO llx_societe(nom, 
entity, 
ref_ext, 
ref_int, 
statut, 
parent, 
tms, 
datec, 
datea, 
status, 
code_client, 
code_fournisseur, 
code_compta, 
code_compta_fournisseur, 
address, 
zip, 
town, 
fk_departement, 
fk_pays, 
phone, 
fax, 
url, 
email, 
fk_effectif, 
fk_typent, 
fk_forme_juridique, 
fk_currency, 
siren, 
siret, 
ape, 
idprof4, 
idprof5, 
idprof6, 
tva_intra, 
capital, 
fk_stcomm, 
note_private, 
note_public, 
prefix_comm, 
client, 
fournisseur, 
supplier_account, 
fk_prospectlevel, 
customer_bad, 
customer_rate, 
supplier_rate,
 fk_user_creat, 
fk_user_modif, 
remise_client, 
mode_reglement, 
cond_reglement, 
tva_assuj, 
localtax1_assuj, 
localtax2_assuj, 
barcode, 
fk_barcode_type, 
price_level, 
default_lang, 
logo, 
canvas, 
import_key)
SELECT DISTINCT act.nom,
	1, 
	act.accountcode,
	act.leadcode, 
	0,
	NULL,
	act.modified,
	act.created,
	act.created,
	act.disabled,
	act.accountcode,
	NULL,
	LEFT(leg.piece,3),
NULL,
CONCAT_WS(' ',act.adresse, act.adresse2),
act.codepostal,
act.ville,
NULL,
act.pays,
act.tel,
act.fax,
act.siteweb,
TRIM(act.email),
NULL,
0,
NULL,
0,
NULL,
act.siret,
ref_sect.secteur,
NULL,
NULL, 
NULL,
LEFT(act.tvaintracom,20),
NULL,
0,
act.remarque,
NULL,
NULL,
act.client,
0,
NULL,
NULL,
0,
0, 
0,
IFNULL(usercrea.rowid,1), 
IFNULL(usermod.rowid,1), 
0,
modpay.id,
payterm.rowid,
act.tva,
NULL,
NULL,
NULL,
0,
NULL,
NULL,
NULL,
NULL,
act.id
FROM  thirdparty as so 
INNER JOIN account as act ON so.account_id=act.id AND so.type='account'
LEFT OUTER JOIN legacy_mvt as leg ON so.id=leg.thirdparty_id
LEFT OUTER JOIN ref_sect ON ref_sect.code=act.secteur
LEFT OUTER JOIN llx_c_paiement as modpay ON modpay.code = act.modreg
LEFT OUTER JOIN llx_c_payment_term as payterm ON payterm.nbjour = act.jourreg AND payterm.active=1
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=act.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=act.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
WHERE act.type IN ('ENT','EE','OP','PAY','PP','IND', 'PAR');

--Import supplier
INSERT INTO llx_societe(nom, 
entity, 
ref_ext, 
ref_int, 
statut, 
parent, 
tms, 
datec, 
datea, 
status, 
code_client, 
code_fournisseur, 
code_compta, 
code_compta_fournisseur, 
address, 
zip, 
town, 
fk_departement, 
fk_pays, 
phone, 
fax, 
url, 
email, 
fk_effectif, 
fk_typent, 
fk_forme_juridique, 
fk_currency, 
siren, 
siret, 
ape, 
idprof4, 
idprof5, 
idprof6, 
tva_intra, 
capital, 
fk_stcomm, 
note_private, 
note_public, 
prefix_comm, 
client, 
fournisseur, 
supplier_account, 
fk_prospectlevel, 
customer_bad, 
customer_rate, 
supplier_rate,
 fk_user_creat, 
fk_user_modif, 
remise_client, 
mode_reglement, 
cond_reglement, 
tva_assuj, 
localtax1_assuj, 
localtax2_assuj, 
barcode, 
fk_barcode_type, 
price_level, 
default_lang, 
logo, 
canvas, 
import_key)
SELECT DISTINCT act.nom,
	1, 
	act.accountcode,
	act.leadcode, 
	0,
	NULL,
	act.modified,
	act.created,
	act.created,
	act.disabled,
	act.accountcode,
	NULL,
	LEFT(leg.piece,3),
NULL,
CONCAT_WS(' ',act.adresse, act.adresse2),
act.codepostal,
act.ville,
NULL,
act.pays,
act.tel,
act.fax,
act.siteweb,
TRIM(act.email),
NULL,
0,
NULL,
0,
NULL,
act.siret,
ref_sect.secteur,
NULL,
NULL, 
NULL,
LEFT(act.tvaintracom,20),
NULL,
0,
act.remarque,
NULL,
NULL,
0,
1,
NULL,
NULL,
0,
0, 
0,
IFNULL(usercrea.rowid,1), 
IFNULL(usermod.rowid,1), 
0,
modpay.id,
payterm.rowid,
act.tva,
NULL,
NULL,
NULL,
0,
NULL,
NULL,
NULL,
NULL,
act.id
FROM  thirdparty as so 
INNER JOIN account as act ON so.account_id=act.id AND so.type='account'
LEFT OUTER JOIN legacy_mvt as leg ON so.id=leg.thirdparty_id
LEFT OUTER JOIN ref_sect ON ref_sect.code=act.secteur
LEFT OUTER JOIN llx_c_paiement as modpay ON modpay.code = act.modreg
LEFT OUTER JOIN llx_c_payment_term as payterm ON payterm.nbjour = act.jourreg AND payterm.active=1
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=act.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=act.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
WHERE act.type='FOU';

--Insert prospect
INSERT INTO llx_societe(nom, 
entity, 
ref_ext, 
ref_int, 
statut, 
parent, 
tms, 
datec, 
datea, 
status, 
code_client, 
code_fournisseur, 
code_compta, 
code_compta_fournisseur, 
address, 
zip, 
town, 
fk_departement, 
fk_pays, 
phone, 
fax, 
url, 
email, 
fk_effectif, 
fk_typent, 
fk_forme_juridique, 
fk_currency, 
siren, 
siret, 
ape, 
idprof4, 
idprof5, 
idprof6, 
tva_intra, 
capital, 
fk_stcomm, 
note_private, 
note_public, 
prefix_comm, 
client, 
fournisseur, 
supplier_account, 
fk_prospectlevel, 
customer_bad, 
customer_rate, 
supplier_rate,
 fk_user_creat, 
fk_user_modif, 
remise_client, 
mode_reglement, 
cond_reglement, 
tva_assuj, 
localtax1_assuj, 
localtax2_assuj, 
barcode, 
fk_barcode_type, 
price_level, 
default_lang, 
logo, 
canvas, 
import_key)
SELECT DISTINCT act.nom,
	1, 
	act.accountcode,
	act.leadcode, 
	0,
	NULL,
	act.modified,
	act.created,
	act.created,
	act.disabled,
	act.accountcode,
	NULL,
	NULL,
NULL,
CONCAT_WS(' ',act.adresse, act.adresse2),
act.codepostal,
act.ville,
NULL,
act.pays,
act.tel,
act.fax,
act.siteweb,
TRIM(act.email),
NULL,
0,
NULL,
0,
NULL,
act.siret,
ref_sect.secteur,
NULL,
NULL, 
NULL,
LEFT(act.tvaintracom,20),
NULL,
0,
act.remarque,
NULL,
NULL,
2,
0,
NULL,
NULL,
0,
0, 
0,
IFNULL(usercrea.rowid,1), 
IFNULL(usermod.rowid,1), 
0,
modpay.id,
payterm.rowid,
act.tva,
NULL,
NULL,
NULL,
0,
NULL,
NULL,
NULL,
NULL,
act.id
FROM  account as act 
LEFT OUTER JOIN ref_sect ON ref_sect.code=act.secteur
LEFT OUTER JOIN llx_c_paiement as modpay ON modpay.code = act.modreg
LEFT OUTER JOIN llx_c_payment_term as payterm ON payterm.nbjour = act.jourreg AND payterm.active=1
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=act.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=act.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
WHERE act.id NOT IN (SELECT account_id from thirdparty WHERE account_id IS NOT NULL )
AND act.type IN ('ENT','EE','OP','PAY','PP','IND', 'PAR');


UPDATE llx_societe SET fk_typent=101 WHERE import_key IN (SELECT id from account where type='ENT');
UPDATE llx_societe SET fk_typent=102 WHERE import_key IN (SELECT id from account where type='EE');
UPDATE llx_societe SET fk_typent=103 WHERE import_key IN (SELECT id from account where type='OP');
UPDATE llx_societe SET fk_typent=3 WHERE import_key IN (SELECT id from account where type='PAY');
UPDATE llx_societe SET fk_typent=5 WHERE import_key IN (SELECT id from account where type='PP');
UPDATE llx_societe SET fk_typent=8 WHERE import_key IN (SELECT id from account where type='IND');

UPDATE llx_societe SET fk_prospectlevel='PL_HIGH' WHERE import_key IN (SELECT id from account where cycle_vie='01');
UPDATE llx_societe SET fk_prospectlevel='PL_MEDIUM' WHERE import_key IN (SELECT id from account where cycle_vie='02');
UPDATE llx_societe SET fk_prospectlevel='PL_LOW' WHERE import_key IN (SELECT id from account where cycle_vie='03');
UPDATE llx_societe SET fk_prospectlevel='PL_NONE' WHERE import_key IN (SELECT id from account where cycle_vie='04');
UPDATE llx_societe SET fk_prospectlevel='PL_NONE' WHERE import_key IN (SELECT id from account where cycle_vie='05');



INSERT INTO llx_societe_extrafields (fk_object,ts_secteur,ts_prospection)
SELECT 
llx_societe.rowid,
act.secteur,
CASE WHEN (act.approche='MM') THEN (select rowid from llx_user where login='jandrian')
	WHEN (act.approche='NAD') THEN (select rowid from llx_user where login='dnguyen')
	WHEN (act.approche='MC') THEN (select rowid from llx_user where login='mclement')
	ELSE null END
FROM  account as act INNER JOIN llx_societe ON llx_societe.import_key=act.id AND act.secteur IS NOT NULL;

UPDATE llx_societe_extrafields SET ts_secteur='AERO' WHERE ts_secteur IN ('09');
UPDATE llx_societe_extrafields SET ts_secteur='ALIM' WHERE ts_secteur IN ('23');
UPDATE llx_societe_extrafields SET ts_secteur='AUTO' WHERE ts_secteur IN ('18');
UPDATE llx_societe_extrafields SET ts_secteur='BTP' WHERE ts_secteur IN ('31', '08');
UPDATE llx_societe_extrafields SET ts_secteur='CHIM' WHERE ts_secteur IN ('21', '38', '40');
UPDATE llx_societe_extrafields SET ts_secteur='CONS' WHERE ts_secteur IN ('26', '71');
UPDATE llx_societe_extrafields SET ts_secteur='CONSO' WHERE ts_secteur IN ('04');
UPDATE llx_societe_extrafields SET ts_secteur='DISTRI' WHERE ts_secteur IN ('10');
UPDATE llx_societe_extrafields SET ts_secteur='EDUC' WHERE ts_secteur IN ('29');
UPDATE llx_societe_extrafields SET ts_secteur='ENERG' WHERE ts_secteur IN ('15', '33', '36', '41');
UPDATE llx_societe_extrafields SET ts_secteur='FIN' WHERE ts_secteur IN ('06', '42');
UPDATE llx_societe_extrafields SET ts_secteur='INGEN' WHERE ts_secteur IN ('13', '37');
UPDATE llx_societe_extrafields SET ts_secteur='LUX' WHERE ts_secteur IN ('32');
UPDATE llx_societe_extrafields SET ts_secteur='PUB' WHERE ts_secteur IN ('12');
UPDATE llx_societe_extrafields SET ts_secteur='SP' WHERE ts_secteur IN ('02');
UPDATE llx_societe_extrafields SET ts_secteur='TIC' WHERE ts_secteur IN ('22', '28');
UPDATE llx_societe_extrafields SET ts_secteur='TOURISM' WHERE ts_secteur IN ('01');
UPDATE llx_societe_extrafields SET ts_secteur='TRANSP' WHERE ts_secteur IN ('30');
UPDATE llx_societe_extrafields SET ts_secteur=NULL WHERE ts_secteur IN ('00', '05', '27', '34', '35', '39', '43');





TRUNCATE TABLE llx_socpeople;

--Insert Contact of thirdparty
INSERT INTO llx_socpeople (datec,
tms,
fk_soc,
entity,
ref_ext,
civilite,
lastname,
firstname,
address,
zip,
town,
fk_departement,
fk_pays,
birthday,
poste,
phone,
phone_perso,
phone_mobile,
fax,
email,
jabberid,
no_email,
priv,
fk_user_creat,
fk_user_modif,
note_private,
note_public,
default_lang,
canvas,
import_key,
statut) 
SELECT 
contact.created, 
contact.modified, 
soc.rowid, 
1,
contact.external_ref,
civ.code,
contact.nom, 
contact.prenom,
CONCAT_WS(' ',contact.adresse, contact.adresse2),
contact.codepostal,
contact.ville,
NULL,
soc.fk_pays,
NULL,
LEFT(contact.vendredi, 80),--fonc.libelle,
contact.tel,
NULL,
contact.portable,
contact.fax,
TRIM(contact.email),
NULL,
0,
0,
IFNULL(usercrea.rowid,1), 
IFNULL(usermod.rowid,1), 
CONCAT_WS(' ',contact.remarque, contact.mardi),
NULL,
NULL,
NULL,
contact.id,
CASE WHEN (contact.disabled=1) THEN 0 ELSE 1 END
FROM contact
INNER JOIN account ON contact.account_id = account.id
INNER JOIN llx_societe as soc ON soc.import_key=account.id
LEFT OUTER JOIN llx_c_civilite as civ ON civ.code=contact.titre
LEFT OUTER JOIN ref_fonc as fonc ON fonc.fonction=contact.fonction
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=contact.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=contact.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
WHERE contact.nom IS NOT NULL AND TRIM(contact.nom)<>'';


INSERT INTO llx_socpeople_extrafields  (fk_object,ct_anglais,ct_service, ct_magellan) 
SELECT cont.rowid,
CASE WHEN (contact.samedi='EN') THEN 1 ELSE 0 END,
fonc.fonction,
CASE WHEN (contact.lundi='Magellan' OR contact.lundi='Cercle Magellan') THEN 1 ELSE 0 END
FROM llx_socpeople as cont 
INNER JOIN contact ON cont.import_key=contact.id
LEFT OUTER JOIN ref_fonc as fonc ON fonc.fonction=contact.fonction;

UPDATE llx_socpeople_extrafields SET ct_service='DMA' WHERE ct_service='CDP';
UPDATE llx_socpeople_extrafields SET ct_service='AUT' WHERE ct_service='CF';
UPDATE llx_socpeople_extrafields SET ct_service='DFI' WHERE ct_service='COM';
UPDATE llx_socpeople_extrafields SET ct_service='DEX' WHERE ct_service='DDI';
UPDATE llx_socpeople_extrafields SET ct_service='DG' WHERE ct_service='DGA';
UPDATE llx_socpeople_extrafields SET ct_service='DG' WHERE ct_service='DGN';
UPDATE llx_socpeople_extrafields SET ct_service='DRD' WHERE ct_service='DIN';
UPDATE llx_socpeople_extrafields SET ct_service='DJU' WHERE ct_service='DJ';
UPDATE llx_socpeople_extrafields SET ct_service='AUT' WHERE ct_service='DMK';
UPDATE llx_socpeople_extrafields SET ct_service='DBU' WHERE ct_service='DOP';
UPDATE llx_socpeople_extrafields SET ct_service=null WHERE ct_service='DPE';
UPDATE llx_socpeople_extrafields SET ct_service='DBU' WHERE ct_service='DRE';
UPDATE llx_socpeople_extrafields SET ct_service='DG' WHERE ct_service='DST';
UPDATE llx_socpeople_extrafields SET ct_service=null WHERE ct_service='DTC';
UPDATE llx_socpeople_extrafields SET ct_service='AUT' WHERE ct_service='GEN';
UPDATE llx_socpeople_extrafields SET ct_service='RMI' WHERE ct_service='INT';
UPDATE llx_socpeople_extrafields SET ct_service='RGC' WHERE ct_service='MAG';
UPDATE llx_socpeople_extrafields SET ct_service='AUT' WHERE ct_service='PAR';
UPDATE llx_socpeople_extrafields SET ct_service='DG' WHERE ct_service='PDG';
UPDATE llx_socpeople_extrafields SET ct_service='DG' WHERE ct_service='PRE';
UPDATE llx_socpeople_extrafields SET ct_service='AUT' WHERE ct_service='RDD';
UPDATE llx_socpeople_extrafields SET ct_service='RDI' WHERE ct_service='RDI';
UPDATE llx_socpeople_extrafields SET ct_service='RRH' WHERE ct_service='RDP';
UPDATE llx_socpeople_extrafields SET ct_service='DFO' WHERE ct_service='REL';
UPDATE llx_socpeople_extrafields SET ct_service='RRH' WHERE ct_service='REP';
UPDATE llx_socpeople_extrafields SET ct_service='RMI' WHERE ct_service='REX';
UPDATE llx_socpeople_extrafields SET ct_service='RGC' WHERE ct_service='RGC';
UPDATE llx_socpeople_extrafields SET ct_service='RMI' WHERE ct_service='RIM';
UPDATE llx_socpeople_extrafields SET ct_service='DFI' WHERE ct_service='RPA';
UPDATE llx_socpeople_extrafields SET ct_service='RRH' WHERE ct_service='RRC';
UPDATE llx_socpeople_extrafields SET ct_service='ASS' WHERE ct_service='SEC';
UPDATE llx_socpeople_extrafields SET ct_service='DG' WHERE ct_service='SGN';
UPDATE llx_socpeople_extrafields SET ct_service='ASS' WHERE ct_service='STA';
UPDATE llx_socpeople_extrafields SET ct_service='DG' WHERE ct_service='VPR';




--update commercial on customer
TRUNCATE TABLE  llx_societe_commerciaux;

INSERT INTO llx_societe_commerciaux (fk_soc,fk_user)
SELECT soc.rowid,usr.rowid
FROM llx_societe as soc 
INNER JOIN account ON soc.import_key=account.id
INNER JOIN com ON com.id=account.com_id
INNER JOIN llx_user as usr ON com.email=usr.email;

--Update Mother company
UPDATE llx_societe as soc,account as child, account as parent,llx_societe as socparent 
SET soc.parent=socparent.rowid,
soc.tms=soc.tms
WHERE soc.import_key=child.id
AND child.groupe_account_id=parent.id
AND socparent.import_key=parent.id
AND child.groupe_account_id<>child.id;

--Create asktoes as thirdparty to import place/room
INSERT INTO llx_societe (nom,entity,tms,datec,datea,status,address,zip,town,fk_pays,client,fournisseur,import_key)
VALUES ('Akteos',1,NOW(),NOW(),NOW(),1,'6 rue du quatre septembre','92130','ISSY LES MOULINEAUX',1,0,0,'akteos');

--Create asktoes as thirdparty to import place/room
INSERT INTO llx_societe (nom,entity,tms,datec,datea,status,address,zip,town,fk_pays,client,fournisseur,import_key)
VALUES ('Inconnue',1,NOW(),NOW(),NOW(),1,'6 rue du quatre septembre','92130','ISSY LES MOULINEAUX',1,0,0,'inconnue');

--import place into agefodd
TRUNCATE TABLE llx_agefodd_place;
INSERT INTO llx_agefodd_place (
ref_interne,
adresse,
cp,
ville,
fk_pays,
tel,
fk_societe,
notes,
acces_site,
note1,
archive,
fk_reg_interieur,
fk_user_author,
datec,
fk_user_mod,
tms,
entity,
import_key) 
SELECT 
CONCAT_WS('-',room.code, room.adr1), --ref_interne,
room.adr2, --adresse,
room.cp, --cp
room.ville,
1, --fk_pays,
NULL, --tel,
soc.rowid, --fk_societe,
NULL, --notes,
room.adr3, --acces_site,
NULL, --note1,
0, --archive,
NULL, --fk_reg_interieur,
IFNULL(usercrea.rowid,1), --fk_user_author
NOW(), --datec,
IFNULL(usermod.rowid,1),  --fk_user_mod,
room.modified,--tms,
1, --entity
room.id -- importkey
FROM (room
, llx_societe as soc)
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=room.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=room.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
WHERE soc.import_key='inconnue'
AND room.code NOT LIKE 'Z9999%';

---Vérification manuel si +sieur matires par stage
/*
SELECT stage_id from trainingprogramdiscipline 
GROUP BY stage_id HAVING count(stage_id)>1

DELETE fROM trainingprogramdiscipline WHERE stage_id='5d9ef8bb-a906-45d6-a39e-7cbc0c546a07' AND matiere='02MIC'
*/

--import analytics training category
TRUNCATE TABLE llx_agefodd_formation_catalogue_type;
INSERT INTO llx_agefodd_formation_catalogue_type (code,intitule,sort,active,tms)
SELECT DISTINCT analyt.code,
analyt.intitule,
0,
CASE WHEN (analyt.intitule='NE PAS UTILISER') THEN 0 ELSE 1 END,
NOW()
FROM stage 
INNER JOIN analyt ON stage.analyt=analyt.code
ORDER BY analyt.intitule;

UPDATE llx_agefodd_formation_catalogue_type SET sort=rowid;

--import training catalogue
TRUNCATE TABLE llx_agefodd_formation_catalogue;
INSERT INTO llx_agefodd_formation_catalogue (
ref,
ref_interne,
entity,
intitule,
duree,
public,
methode,
prerequis,
but,
programme,
note1,
note2,
archive,
fk_user_author,
datec,
fk_user_mod,
note_private,
note_public,
fk_product,
nb_subscribe_min,
tms,
import_key,
fk_c_category) 
SELECT
stage.numstage,--ref
stage.numstage, --ref_interne
1, --entity
CASE WHEN (TRIM(stage.intlong)='') THEN stage.intitule ELSE TRIM(REPLACE(stage.intlong,'Formation :','')) END ,--intitule
IFNULL(stage.nbhr,0), --duree
typcours.intitule, --public
NULL, --methode
NULL, --prerequis
matiere.intitule, --but
NULL, --programme,
matiere.memo, --note1,
NULL, --note2,
stage.disabled, --archive,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(stage.created,NOW()), --datec,
IFNULL(usermod.rowid,1),  --fk_user_mod,
NULL, --note_private,
NULL, --note_public,
NULL, --fk_product,
NULL, --nb_subscribe_min,
stage.modified, --tms
stage.id,
cattype.rowid
FROM stage 
INNER JOIN typcours ON typcours.code=stage.typcours
LEFT OUTER JOIN trainingprogramdiscipline as but ON but.stage_id=stage.id
LEFT OUTER JOIN matiere ON matiere.matiere=but.matiere
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=stage.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=stage.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
LEFT OUTER JOIN llx_agefodd_formation_catalogue_type as cattype ON cattype.code=stage.analyt
WHERE typcours.intitule <> 'NE PAS UTILISER';

UPDATE llx_agefodd_formation_catalogue SET ref=CONCAT_WS('','FOR_', date_format(datec,'%y%m'),'-', LPAD(rowid,4,'0'));


TRUNCATE TABLE `llx_agefodd_session_admlevel`;
INSERT INTO `llx_agefodd_session_admlevel` (`rowid`, `level_rank`, `fk_parent_level`, `indice`, `intitule`, `delais_alerte`, `fk_user_author`, `datec`, `fk_user_mod`, `tms`) VALUES
(18, 0, 0, 100, 'CONSULTANT', -15, 1, '2013-11-19 21:53:30', 1, '2013-11-19 20:54:28'),
(19, 1, 18, 101, 'Réservation consultant', -15, 1, '2013-11-19 21:53:43', 1, '2013-11-19 20:54:23'),
(20, 1, 18, 102, 'Confirmation consultant envoyée', -15, 1, '2013-11-19 21:54:18', 1, '2013-11-19 20:54:18'),
(21, 1, 18, 103, 'Support prêt ou imprimé', -1, 1, '2013-11-19 21:54:45', 1, '2013-11-19 20:54:45'),
(22, 1, 18, 104, 'Feuilles d''émargement et d''évaluation éditées', -1, 1, '2013-11-19 21:54:57', 1, '2013-11-19 20:54:57'),
(23, 0, 0, 200, 'LOGISTIQUE', -15, 1, '2013-11-19 21:55:10', 1, '2013-11-19 20:55:10'),
(24, 1, 23, 201, 'Déplacements organisés', -15, 1, '2013-11-19 21:55:21', 1, '2013-11-19 20:55:21'),
(25, 1, 23, 202, 'Réservation salle et/ou repas (option)', -15, 1, '2013-11-19 21:55:32', 1, '2013-11-19 20:55:32'),
(26, 1, 23, 203, 'Réservation salle et/ou repas (confirmée)', -15, 1, '2013-11-19 21:55:44', 1, '2013-11-19 20:55:44'),
(27, 0, 0, 300, 'CLIENT', -15, 1, '2013-11-19 21:55:56', 1, '2013-11-19 20:55:56'),
(28, 1, 27, 301, 'Date confirmée', -15, 1, '2013-11-19 21:56:08', 1, '2013-11-19 20:56:08'),
(29, 1, 27, 302, 'Lieu confirmé', -15, 1, '2013-11-19 21:56:18', 1, '2013-11-19 20:56:18'),
(30, 1, 27, 303, 'Convention envoyée', -15, 1, '2013-11-19 21:56:33', 1, '2013-11-19 20:56:33'),
(31, 1, 27, 304, 'Convention retournée', -10, 1, '2013-11-19 21:56:43', 1, '2013-11-19 20:56:43'),
(32, 0, 0, 400, 'PARTICIPANT', -15, 1, '2013-11-19 21:56:53', 1, '2013-11-19 20:56:53'),
(33, 1, 32, 401, 'Convocation participant envoyée (inscriptions RN)', -15, 1, '2013-11-19 21:57:07', 1, '2013-11-19 20:57:07'),
(34, 1, 32, 402, 'Support et/ou attestation en ligne', -15, 1, '2013-11-19 21:57:17', 1, '2013-11-19 20:57:17'),
(35, 0, 0, 500, 'FACTURE FOURNISSEUR (TRANSMISE ET SAISIE)', 5, 1, '2013-11-19 21:57:32', 1, '2013-11-19 20:57:32'),
(36, 1, 35, 501, 'Agence de voyage', 5, 1, '2013-11-19 21:57:42', 1, '2013-11-19 20:57:42'),
(37, 1, 35, 502, 'Consultant', 5, 1, '2013-11-19 21:57:52', 1, '2013-11-19 20:57:52'),
(38, 1, 35, 503, 'Frais techniques', 5, 1, '2013-11-19 21:58:02', 1, '2013-11-19 20:58:02'),
(39, 0, 0, 600, 'SESSION CLOTUREE', 0, 1, '2013-11-19 21:58:14', 1, '2013-11-19 20:58:14'),
(40, 1, 39, 601, 'Feuilles d''émargement et d''évaluation scannées', 5, 1, '2013-11-19 21:58:35', 1, '2013-11-19 20:58:35'),
(41, 1, 39, 602, 'Feuilles d''émargement et d''évaluation envoyées', 5, 1, '2013-11-19 21:58:57', 1, '2013-11-19 20:58:57'),
(42, 1, 39, 603, 'Facture client envoyée', 5, 1, '2013-11-19 21:59:10', 1, '2013-11-19 20:59:10');




INSERT INTO llx_agefodd_training_admlevel(fk_agefodd_training_admlevel,fk_training,level_rank,fk_parent_level,indice,intitule,delais_alerte,fk_user_author,datec,fk_user_mod) 
SELECT DISTINCT seesadm.rowid,training.rowid, seesadm.level_rank, seesadm.fk_parent_level,seesadm.indice, seesadm.intitule,seesadm.delais_alerte,seesadm.fk_user_author,seesadm.datec,seesadm.fk_user_mod 
FROM llx_agefodd_session_admlevel as seesadm, llx_agefodd_formation_catalogue as training;

UPDATE llx_agefodd_training_admlevel as ori, llx_agefodd_training_admlevel as upd SET upd.fk_parent_level=ori.rowid WHERE upd.fk_parent_level=ori.fk_agefodd_training_admlevel AND upd.level_rank<>0 AND upd.fk_training=ori.fk_training;

--Insert domaine extrafield
INSERT INTO llx_agefodd_formation_catalogue_extrafields(fk_object, cd_domaine)
SELECT llx_agefodd_formation_catalogue.rowid, 
'DVI'
FROM llx_agefodd_formation_catalogue INNER JOIN stage ON llx_agefodd_formation_catalogue.import_key=stage.id
WHERE stage.domaine IN ('COMER','SIC','MARKET');

INSERT INTO llx_agefodd_formation_catalogue_extrafields(fk_object, cd_domaine)
SELECT llx_agefodd_formation_catalogue.rowid, 
'PAYS'
FROM llx_agefodd_formation_catalogue INNER JOIN stage ON llx_agefodd_formation_catalogue.import_key=stage.id
WHERE stage.domaine IN ('EXPAT','PAYS');

INSERT INTO llx_agefodd_formation_catalogue_extrafields(fk_object, cd_domaine)
SELECT llx_agefodd_formation_catalogue.rowid, 
'MAGNT'
FROM llx_agefodd_formation_catalogue INNER JOIN stage ON llx_agefodd_formation_catalogue.import_key=stage.id
WHERE stage.domaine IN ('RH');

INSERT INTO llx_agefodd_formation_catalogue_extrafields(fk_object, cd_domaine)
SELECT llx_agefodd_formation_catalogue.rowid, 
'COM'
FROM llx_agefodd_formation_catalogue INNER JOIN stage ON llx_agefodd_formation_catalogue.import_key=stage.id
WHERE stage.domaine IN ('COMM');

--import bank
TRUNCATE TABLE llx_bank_account;
INSERT INTO llx_bank_account (rowid,datec,tms,ref,label,entity,bank,code_banque,code_guichet,number,cle_rib,bic,iban_prefix,country_iban,cle_iban,domiciliation,state_id,fk_pays,proprio,owner_address,courant,clos,rappro,url,account_number,currency_code,min_allowed,min_desired,comment) VALUES (1,{ts '2013-08-13 21:46:03.'},{ts '2013-08-13 21:46:39.'},'CIC','CIC',1,'CIC','30066','10021','00010327101','','','',null,null,'',null,1,'','',1,0,1,null,'','EUR',0,0,'');

/*
--import product
TRUNCATE TABLE llx_product;
INSERT INTO llx_product (
ref,
entity,
ref_ext,
datec,
tms,
virtual,
fk_parent,
label,
description,
note,
customcode,
fk_country,
price,
price_ttc,
price_min,
price_min_ttc,
price_base_type,
tva_tx,
recuperableonly,
localtax1_tx,
localtax2_tx,
fk_user_author,
tosell,
tobuy,
fk_product_type,
duration,
seuil_stock_alerte,
barcode,
fk_barcode_type,
accountancy_code_sell,
accountancy_code_buy,
partnumber,
weight,
weight_units,
length,
length_units,
surface,
surface_units,
volume,
volume_units,
stock,
pmp,
canvas,
finished,
hidden,
import_key) 
SELECT 
produit.codprod, --ref,
1, --entity,
NULL, --ref_ext,
NOW(),
NOW(),
0, --virtual,
NULL, --fk_parent,
produit.intitule, --label,
NULL, --description,
NULL, --note,
NULL, --customcode,
NULL, --fk_country,
0, --price,
0, --price_ttc,
0, --price_min,
0, --price_min_ttc,
'TTC', --price_base_type,
19.6, --tva_tx,
0, --recuperableonly,
0, --localtax1_tx,
0, --localtax2_tx,
1, --fk_user_author,
1, --tosell,
0, --tobuy,
1, --fk_product_type,
NULL, --duration,
NULL, --seuil_stock_alerte,
NULL, --barcode,
NULL, --fk_barcode_type,
produit.compte, --accountancy_code_sell,
NULL, --accountancy_code_buy,
NULL, --partnumber,
NULL, --weight,
NULL, --weight_units,
NULL, --length,
NULL, --length_units,
NULL, --surface,
NULL, --surface_units,
NULL, --volume,
NULL, --volume_units,
NULL, --stock,
0, --pmp,
NULL, --canvas,
0, --finished,
0, --hidden,
NULL --import_key
FROM produit;

--Update product id into training
UPDATE llx_agefodd_formation_catalogue as cat, llx_product as prod , stage SET cat.fk_product=prod.rowid
WHERE stage.specific_product=prod.ref AND stage.numstage=cat.ref_interne;
*/
TRUNCATE TABLE `llx_product`;
INSERT INTO `llx_product` (`rowid`, `ref`, `entity`, `ref_ext`, `datec`, `tms`, `virtual`, `fk_parent`, `label`, `description`, `note`, `customcode`, `fk_country`, `price`, `price_ttc`, `price_min`, `price_min_ttc`, `price_base_type`, `tva_tx`, `recuperableonly`, `localtax1_tx`, `localtax2_tx`, `fk_user_author`, `tosell`, `tobuy`, `fk_product_type`, `duration`, `seuil_stock_alerte`, `barcode`, `fk_barcode_type`, `accountancy_code_sell`, `accountancy_code_buy`, `partnumber`, `weight`, `weight_units`, `length`, `length_units`, `surface`, `surface_units`, `volume`, `volume_units`, `stock`, `pmp`, `canvas`, `finished`, `hidden`, `import_key`) VALUES
(154, 's01_ind.1j', 1, NULL, '2013-11-16 23:48:26', '2013-11-16 23:49:07', 0, 0, 'Formation individuelle', '', '', '', NULL, 2300.00000000, 2750.80000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '1d', NULL, NULL, 0, '70610000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(119, 's01_intra_ind.1j', 1, NULL, '2013-11-14 22:52:37', '2013-11-16 23:45:02', 0, 0, 'Formation individuelle', '', 'Formation organis&eacute;e pour une personne ou un couple', '', NULL, 2300.00000000, 2750.80000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '1d', NULL, NULL, 0, '70610000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(123, 's01_team', 1, NULL, '2013-11-14 22:59:45', '2013-11-14 22:59:58', 0, 0, 'Team Building', '', '', '', NULL, 2600.00000000, 3109.60000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '1d', NULL, NULL, 0, '70610000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(127, 's02_pnomad_solo', 1, NULL, '2013-11-14 23:14:20', '2013-11-14 23:14:43', 0, 0, 'Profil Nomad'' Solo', '', '', '', NULL, 100.00000000, 119.60000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, 1, '6m', NULL, NULL, 0, '70750000', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(138, 's09_conf', 1, NULL, '2013-11-14 23:46:33', '2013-11-14 23:46:48', 0, 0, 'Conférence', '', '', '', NULL, 1600.00000000, 1913.60000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '2h', NULL, NULL, 0, '70620000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(141, 's09_maj', 1, NULL, '2013-11-14 23:54:44', '2013-11-14 23:55:04', 0, 0, 'Majoration', '', '', '', NULL, 2000.00000000, 2392.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '1d', NULL, NULL, 0, '70620000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(143, 's09_cons', 1, NULL, '2013-11-15 00:00:24', '2013-11-15 00:00:46', 0, 0, 'Mission de conseil', '', '', '', NULL, 2000.00000000, 2392.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '1d', NULL, NULL, 0, '70620000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(142, 's10_dev', 1, NULL, '2013-11-14 23:57:15', '2013-11-14 23:58:57', 0, 0, 'Développement d''outil', '', '', '', NULL, 2000.00000000, 2392.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '1d', NULL, NULL, 0, '70630000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(153, 'sht09_conf', 1, NULL, '2013-11-15 00:48:34', '2013-11-16 14:47:28', 0, 0, 'Conférence', '', '', '', NULL, 1600.00000000, 1600.00000000, 0.00000000, 0.00000000, 'HT', 0.000, 0, 0.000, 0.000, 10, 1, 1, 1, '2h', NULL, NULL, 0, '70620900', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(151, 'sht05_frais', 1, NULL, '2013-11-15 00:42:40', '2013-11-16 14:48:34', 0, 0, 'Frais de mission', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, '', 0.000, 0, 0.000, 0.000, 10, 1, 1, 1, '', NULL, NULL, 0, '70871000', '62265100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(135, 's06_webinar', 1, NULL, '2013-11-14 23:38:11', '2013-11-14 23:38:28', 0, 0, 'Webinar', '', '', '', NULL, 1000.00000000, 1196.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '2h', NULL, NULL, 0, '70760000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(144, 's10_lcp_pres', 1, NULL, '2013-11-15 00:02:35', '2013-11-15 00:02:59', 0, 0, 'LCP / PRESENTIEL', 'Questionnaire par Internet<br />\r\nEnvoi d&#39;un rapport d&eacute;taill&eacute;<br />\r\nD&eacute;briefing avec un coach certifi&eacute; en pr&eacute;sentiel', '', '', NULL, 650.00000000, 777.40000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '2h', NULL, NULL, 0, '70760000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(116, 's01_atelier', 1, NULL, '2013-11-14 22:43:19', '2013-11-18 18:20:59', 0, 0, 'Atelier interculturel', '', '', '', NULL, 1600.00000000, 1913.60000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '3h', NULL, NULL, 0, '70610000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(120, 's01_intra_ind.2J', 1, NULL, '2013-11-14 22:54:59', '2013-11-16 23:45:20', 0, 0, 'Formation individuelle', 'Formation organis&eacute;e pour une personne ou un couple', '', '', NULL, 4500.00000000, 5382.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '2d', NULL, NULL, 0, '70610000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(118, 's01_intra_gr.2j', 1, NULL, '2013-11-14 22:50:58', '2013-11-16 23:44:33', 0, 0, 'Formation pour un groupe', '', '', '', NULL, 5000.00000000, 5980.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '2d', NULL, NULL, 0, '70610000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(117, 's01_1intra_gr.1j', 1, NULL, '2013-11-14 22:47:33', '2013-11-18 18:22:42', 0, 0, 'Formation pour un groupe', '', '', '', NULL, 2600.00000000, 3109.60000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '1d', NULL, NULL, 0, '70610000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(124, 's01_xenf', 1, NULL, '2013-11-14 23:01:11', '2013-11-14 23:17:17', 0, 0, 'Formation enfant', '', '', '', NULL, 1200.00000000, 1435.20000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '3h', NULL, NULL, 0, '70610000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(125, 's02_xnol', 1, NULL, '2013-11-14 23:08:10', '2013-11-14 23:09:11', 0, 0, 'Nomad Online', 'Acc&egrave;s au Profil Nomad&#39; pendant 6 mois<br />\r\nD&eacute;briefing par t&eacute;l&eacute;phone pendant 1 heure avec un consultant certifi&eacute;<br />\r\nFocus sur un pays au choix', '', '', NULL, 450.00000000, 538.20000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '6m', NULL, NULL, 0, '70750000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(130, 's05_frais_mission', 1, NULL, '2013-11-14 23:24:52', '2013-11-14 23:25:14', 0, 0, 'Frais de mission', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, '', 0.000, 0, 0.000, 0.000, 10, 1, 1, 1, '', NULL, NULL, 0, '70870000', '62265100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(133, 's06_tip', 1, NULL, '2013-11-14 23:31:21', '2013-11-14 23:32:35', 0, 0, 'The International Profilerr', '', '', '', NULL, 650.00000000, 777.40000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '2h', NULL, NULL, 0, '70760000', '70769000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(132, 's06_rmedia', 1, NULL, '2013-11-14 23:27:52', '2013-11-14 23:29:55', 0, 0, 'Rich Media', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, '', 0.000, 0, 0.000, 0.000, 10, 1, 1, 1, '', NULL, NULL, 0, '70760000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(146, 's10_lcp_lic', 1, NULL, '2013-11-15 00:17:15', '2013-11-15 00:19:19', 0, 0, 'Licence LCP', '', '', '', NULL, 9000.00000000, 10764.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '1y', NULL, NULL, 0, '70760000', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(147, 'sht01_inter', 1, NULL, '2013-11-15 00:27:24', '2013-11-15 00:27:45', 0, 0, 'Formation interentreprises', '', '', '', NULL, 1490.00000000, 1490.00000000, 0.00000000, 0.00000000, 'HT', 0.000, 0, 0.000, 0.000, 10, 1, 1, 1, '2d', NULL, NULL, 0, '70611000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(145, 's10_lcp_tel', 1, NULL, '2013-11-15 00:07:24', '2013-11-15 00:10:16', 0, 0, 'LCP / TEL', 'Questionnaire par internet<br />\r\nEnvoi d&#39;un rapport d&eacute;taill&eacute;<br />\r\nD&eacute;briefing avec un coach certifi&eacute; par t&eacute;l&eacute;phone', '', '', NULL, 500.00000000, 598.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '2h', NULL, NULL, 0, '70760000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(126, 's02_pnomad', 1, NULL, '2013-11-14 23:12:01', '2013-11-14 23:15:50', 0, 0, 'Profil Nomad''', '', '', '', NULL, 50.00000000, 59.80000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, 1, '6m', NULL, NULL, 0, '70750000', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(137, 's09_coach', 1, NULL, '2013-11-14 23:43:11', '2013-11-14 23:43:25', 0, 0, 'Coaching', '', '', '', NULL, 400.00000000, 478.40000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '1h', NULL, NULL, 0, '70620000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(129, 's06_doc', 1, NULL, '2013-11-14 23:23:27', '2013-11-14 23:23:42', 0, 0, 'Documentation', '', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, '', 0.000, 0, 0.000, 0.000, 10, 1, 1, 1, '', NULL, NULL, 0, '70641000', '61800000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(131, 's05_frais_tech', 1, NULL, '2013-11-14 23:26:17', '2013-11-14 23:26:29', 0, 0, 'Frais techniques', 'Salle &eacute;quip&eacute;e', '', '', NULL, 0.00000000, 0.00000000, 0.00000000, 0.00000000, '', 0.000, 0, 0.000, 0.000, 10, 1, 1, 1, '', NULL, NULL, 0, '70801000', '62265100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(115, 's01_inter', 1, NULL, '2013-11-14 22:30:13', '2013-11-14 22:31:02', 0, 0, 'Formation interentreprises', '', '', '', NULL, 1490.00000000, 1782.04000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 1, 1, '2d', NULL, NULL, 0, '70610000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(149, 'sht01_intra', 1, NULL, '2013-11-15 00:35:02', '2013-11-15 00:36:11', 0, 0, 'Formation intra-entreprise', '', '', '', NULL, 2600.00000000, 2600.00000000, 0.00000000, 0.00000000, 'HT', 0.000, 0, 0.000, 0.000, 10, 1, 1, 1, '1d', NULL, NULL, 0, '70611000', '62265000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL),
(152, 'sht02_pnomad', 1, NULL, '2013-11-15 00:45:44', '2013-11-16 14:46:06', 0, 0, 'Profil Nomad''', '', '', '', NULL, 50.00000000, 50.00000000, 0.00000000, 0.00000000, 'HT', 0.000, 0, 0.000, 0.000, 10, 1, 0, 1, '6m', NULL, NULL, 0, '70759000', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00000000, '', NULL, 0, NULL);

TRUNCATE TABLE `llx_product_lang`;
INSERT INTO `llx_product_lang` (`rowid`, `fk_product`, `lang`, `label`, `description`, `note`) VALUES
(63, 116, 'fr_FR', 'Atelier interculturel', '', ''),
(66, 119, 'fr_FR', 'Formation individuelle', '', 'Formation organis&eacute;e pour une personne ou un couple'),
(71, 124, 'fr_FR', 'Formation enfant', '', ''),
(74, 127, 'fr_FR', 'Profil Nomad'' Solo', '', ''),
(72, 125, 'fr_FR', 'Nomad Online', 'Acc&egrave;s au Profil Nomad&#39; pendant 6 mois<br />\r\nD&eacute;briefing par t&eacute;l&eacute;phone pendant 1 heure avec un consultant certifi&eacute;<br />\r\nFocus sur un pays au choix', ''),
(80, 133, 'fr_FR', 'The International Profilerr', '', ''),
(79, 132, 'fr_FR', 'Rich Media', '', ''),
(85, 138, 'fr_FR', 'Conférence', '', ''),
(88, 141, 'fr_FR', 'Majoration', '', ''),
(91, 144, 'fr_FR', 'LCP / PRESENTIEL', 'Questionnaire par Internet<br />\r\nEnvoi d&#39;un rapport d&eacute;taill&eacute;<br />\r\nD&eacute;briefing avec un coach certifi&eacute; en pr&eacute;sentiel', ''),
(90, 143, 'fr_FR', 'Mission de conseil', '', ''),
(89, 142, 'fr_FR', 'Développement d''outil', '', ''),
(96, 149, 'fr_FR', 'Formation intra-entreprise', '', ''),
(100, 153, 'fr_FR', 'Conférence', '', ''),
(77, 130, 'fr_FR', 'Frais de mission', '', ''),
(99, 152, 'fr_FR', 'Profil Nomad''', '', ''),
(84, 137, 'fr_FR', 'Coaching', '', ''),
(82, 135, 'fr_FR', 'Webinar', '', ''),
(101, 154, 'fr_FR', 'Formation individuelle', '', ''),
(67, 120, 'fr_FR', 'Formation individuelle', 'Formation organis&eacute;e pour une personne ou un couple', ''),
(70, 123, 'fr_FR', 'Team Building', '', ''),
(65, 118, 'fr_FR', 'Formation pour un groupe', '', ''),
(64, 117, 'fr_FR', 'Formation pour un groupe', '', ''),
(93, 146, 'fr_FR', 'Licence LCP', '', ''),
(94, 147, 'fr_FR', 'Formation interentreprises', '', ''),
(98, 151, 'fr_FR', 'Frais de mission', '', ''),
(92, 145, 'fr_FR', 'LCP / TEL', 'Questionnaire par internet<br />\r\nEnvoi d&#39;un rapport d&eacute;taill&eacute;<br />\r\nD&eacute;briefing avec un coach certifi&eacute; par t&eacute;l&eacute;phone', ''),
(73, 126, 'fr_FR', 'Profil Nomad''', '', ''),
(76, 129, 'fr_FR', 'Documentation', '', ''),
(78, 131, 'fr_FR', 'Frais techniques', 'Salle &eacute;quip&eacute;e', ''),
(62, 115, 'fr_FR', 'Formation interentreprises', '', '');

TRUNCATE tABLE `llx_product_price`;
INSERT INTO `llx_product_price` (`rowid`, `entity`, `tms`, `fk_product`, `date_price`, `price_level`, `price`, `price_ttc`, `price_min`, `price_min_ttc`, `price_base_type`, `tva_tx`, `recuperableonly`, `localtax1_tx`, `localtax2_tx`, `fk_user_author`, `tosell`, `price_by_qty`, `import_key`) VALUES
(145, 1, '2013-11-14 22:43:19', 116, '2013-11-14 22:43:19', 1, 1600.00000000, 1913.60000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(148, 1, '2013-11-14 22:52:37', 119, '2013-11-14 22:52:37', 1, 2300.00000000, 2750.80000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(149, 1, '2013-11-14 22:54:59', 120, '2013-11-14 22:54:59', 1, 4500.00000000, 5382.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(147, 1, '2013-11-14 22:50:58', 118, '2013-11-14 22:50:58', 1, 5000.00000000, 5980.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(155, 1, '2013-11-14 23:08:10', 125, '2013-11-14 23:08:10', 1, 450.00000000, 538.20000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(163, 1, '2013-11-14 23:31:21', 133, '2013-11-14 23:31:21', 1, 650.00000000, 777.40000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(162, 1, '2013-11-14 23:27:52', 132, '2013-11-14 23:27:52', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, '', 0.000, 0, 0.000, 0.000, 10, 0, 0, NULL),
(187, 1, '2013-11-15 00:45:44', 152, '2013-11-15 00:45:44', 1, 50.00000000, 50.00000000, 0.00000000, 0.00000000, 'HT', 0.000, 0, 0.000, 0.000, 10, 0, 0, NULL),
(169, 1, '2013-11-14 23:46:33', 138, '2013-11-14 23:46:33', 1, 1600.00000000, 1913.60000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(174, 1, '2013-11-14 23:54:44', 141, '2013-11-14 23:54:44', 1, 2000.00000000, 2392.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(180, 1, '2013-11-15 00:27:24', 147, '2013-11-15 00:27:24', 1, 1490.00000000, 1490.00000000, 0.00000000, 0.00000000, 'HT', 0.000, 0, 0.000, 0.000, 10, 0, 0, NULL),
(177, 1, '2013-11-15 00:02:35', 144, '2013-11-15 00:02:35', 1, 650.00000000, 777.40000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(176, 1, '2013-11-15 00:00:24', 143, '2013-11-15 00:00:24', 1, 2000.00000000, 2392.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(175, 1, '2013-11-14 23:57:15', 142, '2013-11-14 23:57:15', 1, 2000.00000000, 2392.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(186, 1, '2013-11-15 00:42:40', 151, '2013-11-15 00:42:40', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, '', 0.000, 0, 0.000, 0.000, 10, 0, 0, NULL),
(183, 1, '2013-11-15 00:35:02', 149, '2013-11-15 00:35:02', 1, 2600.00000000, 2600.00000000, 0.00000000, 0.00000000, 'HT', 0.000, 0, 0.000, 0.000, 10, 0, 0, NULL),
(153, 1, '2013-11-14 22:59:45', 123, '2013-11-14 22:59:45', 1, 2600.00000000, 3109.60000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(188, 1, '2013-11-15 00:48:34', 153, '2013-11-15 00:48:34', 1, 1600.00000000, 1600.00000000, 0.00000000, 0.00000000, 'HT', 0.000, 0, 0.000, 0.000, 10, 0, 0, NULL),
(160, 1, '2013-11-14 23:24:52', 130, '2013-11-14 23:24:52', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, '', 0.000, 0, 0.000, 0.000, 10, 0, 0, NULL),
(179, 1, '2013-11-15 00:17:15', 146, '2013-11-15 00:17:15', 1, 9000.00000000, 10764.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(168, 1, '2013-11-14 23:43:11', 137, '2013-11-14 23:43:11', 1, 400.00000000, 478.40000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(166, 1, '2013-11-14 23:38:11', 135, '2013-11-14 23:38:11', 1, 1000.00000000, 1196.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(189, 1, '2013-11-16 23:48:26', 154, '2013-11-16 23:48:26', 1, 2300.00000000, 2750.80000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(146, 1, '2013-11-14 22:47:33', 117, '2013-11-14 22:47:33', 1, 2600.00000000, 3109.60000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 1, 0, NULL),
(154, 1, '2013-11-14 23:01:11', 124, '2013-11-14 23:01:11', 1, 1200.00000000, 1435.20000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(157, 1, '2013-11-14 23:14:20', 127, '2013-11-14 23:14:20', 1, 100.00000000, 119.60000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(178, 1, '2013-11-15 00:07:24', 145, '2013-11-15 00:07:24', 1, 500.00000000, 598.00000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(156, 1, '2013-11-14 23:12:01', 126, '2013-11-14 23:12:01', 1, 50.00000000, 59.80000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL),
(159, 1, '2013-11-14 23:23:27', 129, '2013-11-14 23:23:27', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, '', 0.000, 0, 0.000, 0.000, 10, 0, 0, NULL),
(161, 1, '2013-11-14 23:26:17', 131, '2013-11-14 23:26:17', 1, 0.00000000, 0.00000000, 0.00000000, 0.00000000, '', 0.000, 0, 0.000, 0.000, 10, 0, 0, NULL),
(144, 1, '2013-11-14 22:30:13', 115, '2013-11-14 22:30:13', 1, 1490.00000000, 1782.04000000, 0.00000000, 0.00000000, 'HT', 19.600, 0, 0.000, 0.000, 10, 0, 0, NULL);


--import session link with convention
TRUNCATE TABLE llx_agefodd_session;
INSERT INTO llx_agefodd_session (
entity,
fk_soc,
fk_formation_catalogue,
fk_session_place,
type_session,
nb_place,
nb_stagiaire,
force_nb_stagiaire,
nb_subscribe_min,
dated,
datef,
notes,
color,
cost_trainer,
cost_site,
cost_trip,
sell_price,
is_date_res_site,
date_res_site,
is_date_res_trainer,
date_res_trainer,
date_ask_OPCA,
is_date_ask_OPCA,
is_OPCA,
fk_soc_OPCA,
fk_socpeople_OPCA,
num_OPCA_soc,
num_OPCA_file,
fk_user_author,
datec,
fk_user_mod,
tms,
archive,
status,
duree_session,
intitule_custo,
import_key) 
SELECT 
1, --entity,
CASE WHEN (sess.inter=0) THEN soc.rowid ELSE NULL END, --fk_soc,
cat.rowid, --fk_formation_catalogue,
IFNULL(place.rowid,(SELECT rowid from llx_agefodd_place WHERE ref_interne='ENT-Dans l''entreprise')), --fk_session_place,
sess.inter, --type_session,
0, --nb_place,
0, --nb_stagiaire,
0, --force_nb_stagiaire,
sess.nbrmin, --nb_subscribe_min,
IFNULL(MIN(convct.datdeb),sess.datdeb), --dated,
IFNULL(MAX(convct.datfin),sess.datfin), --datef,
'', --notes,
NULL, --color,
SUM(coutconsultant.montant),  --cost_trainer,
SUM(coutsalle.montant), --cost_site,
SUM(couttrip.montant), --cost_trip,
sess.prxft, --sell_price,
0, --is_date_res_site,
NULL, --date_res_site,
0, --is_date_res_trainer,
NULL, --date_res_trainer,
NULL, --date_ask_OPCA,
0,--is_date_ask_OPCA,
0,--is_OPCA,
NULL, --fk_soc_OPCA,
NULL, --fk_socpeople_OPCA,
NULL, --num_OPCA_soc,
NULL, --num_OPCA_file,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(sess.created,NOW()), --datec,
IFNULL(usermod.rowid,1), --fk_user_author
sess.modified, --tms,
CASE WHEN IFNULL(MAX(convct.datfin),sess.datfin)<NOW() THEN 1 ELSE 0 END, --archive
4,--status CONVOQUE,
cat.duree,
cat.intitule,
sess.id --import_key
FROM session as sess
INNER JOIN stage ON stage.id=sess.stage_id
INNER JOIN llx_agefodd_formation_catalogue as cat ON cat.ref_interne=stage.numstage
LEFT OUTER JOIN llx_agefodd_place as place ON place.import_key=sess.room_id
LEFT OUTER JOIN sesfr as coutconsultant ON coutconsultant.session_id=sess.id AND coutconsultant.typfr='CON'
LEFT OUTER JOIN sesfr as coutsalle ON coutsalle.session_id=sess.id AND coutsalle.typfr='SAL'
LEFT OUTER JOIN sesfr as couttrip ON couttrip.session_id=sess.id AND couttrip.typfr='DEP'
INNER JOIN convct ON sess.id=convct.session_id
LEFT OUTER JOIN proct ON sess.id=proct.session_id 
LEFT OUTER JOIN account ON account.id=proct.account_id
LEFT OUTER JOIN llx_societe as soc ON soc.import_key=account.id
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=sess.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=sess.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
GROUP BY sess.id;


--import session without convention
INSERT INTO llx_agefodd_session (
entity,
fk_soc,
fk_formation_catalogue,
fk_session_place,
type_session,
nb_place,
nb_stagiaire,
force_nb_stagiaire,
nb_subscribe_min,
dated,
datef,
notes,
color,
cost_trainer,
cost_site,
cost_trip,
sell_price,
is_date_res_site,
date_res_site,
is_date_res_trainer,
date_res_trainer,
date_ask_OPCA,
is_date_ask_OPCA,
is_OPCA,
fk_soc_OPCA,
fk_socpeople_OPCA,
num_OPCA_soc,
num_OPCA_file,
fk_user_author,
datec,
fk_user_mod,
tms,
archive,
status,
import_key) 
SELECT 
1, --entity,
CASE WHEN (sess.inter=0) THEN soc.rowid ELSE NULL END, --fk_soc,
cat.rowid, --fk_formation_catalogue,
IFNULL(place.rowid,(SELECT rowid from llx_agefodd_place WHERE ref_interne='ENT-Dans l''entreprise')), --fk_session_place,
sess.inter, --type_session,
0, --nb_place,
0, --nb_stagiaire,
0, --force_nb_stagiaire,
sess.nbrmin, --nb_subscribe_min,
IFNULL(MIN(convct.datdeb),sess.datdeb), --dated,
IFNULL(MAX(convct.datfin),sess.datfin), --datef,
'', --notes,
NULL, --color,
SUM(coutconsultant.montant),  --cost_trainer,
SUM(coutsalle.montant), --cost_site,
SUM(couttrip.montant), --cost_trip,
sess.prxft, --sell_price,
0, --is_date_res_site,
NULL, --date_res_site,
0, --is_date_res_trainer,
NULL, --date_res_trainer,
NULL, --date_ask_OPCA,
0,--is_date_ask_OPCA,
0,--is_OPCA,
NULL, --fk_soc_OPCA,
NULL, --fk_socpeople_OPCA,
NULL, --num_OPCA_soc,
NULL, --num_OPCA_file,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(sess.created,NOW()), --datec,
IFNULL(usermod.rowid,1), --fk_user_author
sess.modified, --tms,
CASE WHEN IFNULL(MAX(convct.datfin),sess.datfin)<NOW() THEN 1 ELSE 0 END, --archive
1,--status DRAFT
sess.id --import_key
FROM session as sess
INNER JOIN stage ON stage.id=sess.stage_id
INNER JOIN llx_agefodd_formation_catalogue as cat ON cat.ref_interne=stage.numstage
LEFT OUTER JOIN llx_agefodd_place as place ON place.import_key=sess.room_id
LEFT OUTER JOIN sesfr as coutconsultant ON coutconsultant.session_id=sess.id AND coutconsultant.typfr='CON'
LEFT OUTER JOIN sesfr as coutsalle ON coutsalle.session_id=sess.id AND coutsalle.typfr='SAL'
LEFT OUTER JOIN sesfr as couttrip ON couttrip.session_id=sess.id AND couttrip.typfr='DEP'
LEFT OUTER JOIN convct ON sess.id=convct.session_id
LEFT OUTER JOIN proct ON sess.id=proct.session_id 
LEFT OUTER JOIN account ON account.id=proct.account_id
LEFT OUTER JOIN llx_societe as soc ON soc.import_key=account.id
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=sess.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=sess.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
WHERE convct.id IS NULL AND YEAR(sess.datdeb)>2000
GROUP BY sess.id;

--Import session customer contact
TRUNCATE TABLE llx_agefodd_contact;
INSERT INTO llx_agefodd_contact (entity,fk_socpeople,archive,fk_user_author,datec,fk_user_mod,tms)
SELECT DISTINCT
1,
llx_socpeople.rowid,
0,
1,
NOW(),
1,
NOW()
FROM convct
INNER JOIN contact on convct.recipient_contact_id=contact.id
INNER JOIN llx_socpeople ON llx_socpeople.import_key=contact.id;

TRUNCATE TABLE llx_agefodd_session_contact;
INSERT INTO llx_agefodd_session_contact (fk_session_agefodd,fk_agefodd_contact,fk_user_author,datec,fk_user_mod,tms)
SELECT DISTINCT
llx_agefodd_session.rowid,
llx_agefodd_contact.rowid,
1,
NOW(),
1,
NOW()
FROM convct
INNER JOIN llx_agefodd_session ON llx_agefodd_session.import_key=convct.session_id
INNER JOIN contact on convct.recipient_contact_id=contact.id
INNER JOIN llx_socpeople ON llx_socpeople.import_key=contact.id
INNER JOIN llx_agefodd_contact ON llx_socpeople.rowid=llx_agefodd_contact.fk_socpeople;

--Commerciale
TRUNCATE TABLE llx_agefodd_session_commercial;
INSERT INTO llx_agefodd_session_commercial (fk_session_agefodd, fk_user_com, fk_user_author,fk_user_mod, datec)
SELECT DISTINCT
llx_agefodd_session.rowid,
llx_user.rowid,
1,
1,
NOW()
FROM convct
INNER JOIN llx_agefodd_session ON llx_agefodd_session.import_key=convct.session_id
INNER JOIN contact on convct.recipient_contact_id=contact.id
INNER JOIN com ON com.id=convct.com_id
INNER JOIN llx_user ON llx_user.email=com.email;


-- le gestionnaire logistique qui est le « Type » dans la convention 
INSERT INTO llx_agefodd_session_extrafields (fk_object,ts_logistique)
SELECT DISTINCT
llx_agefodd_session.rowid,
llx_user.rowid
FROM convct
INNER JOIN llx_agefodd_session ON llx_agefodd_session.import_key=convct.session_id
INNER JOIN sf_user ON sf_user.login=convct.contrat
INNER JOIN llx_user ON llx_user.email=sf_user.email_address;



--Import trainer as supplier and contact
INSERT INTO llx_societe(nom, 
entity, 
ref_ext, 
ref_int, 
statut, 
parent, 
tms, 
datec, 
datea, 
status, 
code_client, 
code_fournisseur, 
code_compta, 
code_compta_fournisseur, 
address, 
zip, 
town, 
fk_departement, 
fk_pays, 
phone, 
fax, 
url, 
email, 
fk_effectif, 
fk_typent, 
fk_forme_juridique, 
fk_currency, 
siren, 
siret, 
ape, 
idprof4, 
idprof5, 
idprof6, 
tva_intra, 
capital, 
fk_stcomm, 
note_private, 
note_public, 
prefix_comm, 
client, 
fournisseur, 
supplier_account, 
fk_prospectlevel, 
customer_bad, 
customer_rate, 
supplier_rate,
 fk_user_creat, 
fk_user_modif, 
remise_client, 
mode_reglement, 
cond_reglement, 
tva_assuj, 
localtax1_assuj, 
localtax2_assuj, 
barcode, 
fk_barcode_type, 
price_level, 
default_lang, 
logo, 
canvas, 
import_key)
SELECT DISTINCT CONCAT_WS(' ',interv.nom, interv.prenom),
	1, 
	NULL,
	NULL, 
	0,
	NULL,
	interv.modified,
	interv.created,
	interv.created,
	interv.actif,
	NULL,
	NULL,
	NULL,
NULL,
CONCAT_WS(' ',interv.adr1, interv.commune),
interv.cp,
interv.bureau,
NULL,
interv.pays,
interv.telephone,
interv.fax,
NULL,
TRIM(interv.email),
NULL,
8,
NULL,
0,
NULL,
interv.siret,
interv.urssaf,
NULL,
NULL, 
NULL,
NULL,
NULL,
0,
interv.memo,
NULL,
NULL,
0,
1,
NULL,
NULL,
0,
0, 
0,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(usermod.rowid,1), --fk_user_mod
0,
NULL,
NULL,
NULL,
NULL,
NULL,
NULL,
0,
NULL,
NULL,
NULL,
NULL,
interv.id
FROM  interv
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=interv.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=interv.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email;


UPDATE llx_societe SET code_fournisseur=CONCAT_WS('','F', LPAD(rowid,5,'0')), tms=tms WHERE fournisseur=1;
UPDATE llx_societe SET code_client=CONCAT_WS('','C', LPAD(rowid,5,'0')), tms=tms WHERE client=1;

--Insert Consultant categorie
TRUNCATE TABLE llx_categorie_fournisseur;
INSERT INTO  llx_categorie_fournisseur (fk_categorie,fk_societe)
SELECT 
12,
llx_societe.rowid
FROM llx_societe INNER JOIN interv ON interv.id=llx_societe.import_key;

INSERT INTO llx_socpeople (datec,
tms,
fk_soc,
entity,
ref_ext,
civilite,
lastname,
firstname,
address,
zip,
town,
fk_departement,
fk_pays,
birthday,
poste,
phone,
phone_perso,
phone_mobile,
fax,
email,
jabberid,
no_email,
priv,
fk_user_creat,
fk_user_modif,
note_private,
note_public,
default_lang,
canvas,
import_key,
statut) 
SELECT 
interv.created, 
interv.modified, 
soc.rowid, 
1,
interv.external_ref,
civ.code,
interv.nom, 
interv.prenom,
CONCAT_WS(' ',interv.adr1, interv.commune),
interv.cp,
interv.bureau,
NULL,
soc.fk_pays,
NULL,
NULL,
interv.telephone,
NULL,
interv.portable,
interv.fax,
TRIM(interv.email),
NULL,
0,
0,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(usermod.rowid,1), --fk_user_mod
CONCAT_WS(' ',interv.memo, interv.contrat),
NULL,
NULL,
NULL,
interv.id,
interv.actif
FROM interv
INNER JOIN llx_societe as soc ON soc.import_key=interv.id
LEFT OUTER JOIN llx_c_civilite as civ ON civ.code=interv.civilite
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=interv.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=interv.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email;

--Insert Consultant categorie for contact
TRUNCATE TABLE llx_categorie_contact;
INSERT INTO  llx_categorie_contact (fk_categorie,fk_socpeople)
SELECT 
32,
llx_socpeople.rowid
FROM llx_socpeople INNER JOIN interv ON interv.id=llx_socpeople.import_key;

--Import trainer from dolibarr contact
TRUNCATE TABLE llx_agefodd_formateur;
INSERT INTO llx_agefodd_formateur (
entity,
fk_socpeople,
fk_user,
type_trainer,
archive,
fk_user_author,
datec,
fk_user_mod,
tms)
SELECT 
1, --entity,
llx_socpeople.rowid, --fk_socpeople,
NULL, --fk_user,
'socpeople', --type_trainer, 
interv.disabled, --archive,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(interv.created,NOW()), --datec,
IFNULL(usermod.rowid,1), --fk_user_mod
IFNULL(interv.modified,NOW()) --tms
FROM llx_socpeople 
INNER JOIN interv ON interv.id=llx_socpeople.import_key
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=interv.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=interv.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email


--Import trainer into session
TRUNCATE TABLE llx_agefodd_session_formateur;
INSERT INTO llx_agefodd_session_formateur (
fk_session,
fk_agefodd_formateur,
fk_user_author,
datec,
fk_user_mod,
tms) 
SELECT DISTINCT
llx_agefodd_session.rowid,
llx_agefodd_formateur.rowid,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(pls.created,NOW()),
IFNULL(usermod.rowid,1), --fk_user_mod
IFNULL(pls.modified,NOW())
FROM pls 
INNER JOIN llx_agefodd_session ON llx_agefodd_session.import_key=pls.session_id
INNER JOIN llx_socpeople ON llx_socpeople.import_key=pls.interv_id
INNER JOIN llx_agefodd_formateur ON llx_agefodd_formateur.fk_socpeople=llx_socpeople.rowid
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=pls.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=pls.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
WHERE pls.status='confirmed';


--import trainer times and cost into session
TRUNCATE TABLE llx_agefodd_session_formateur_calendrier;
INSERT INTO llx_agefodd_session_formateur_calendrier(
entity,
  fk_agefodd_session_formateur,
  date_session,
  heured,
  heuref,
  --trainer_cost,
  fk_actioncomm,
  fk_user_author,
  datec,
  fk_user_mod,
  tms)
 SELECT 
 1,--entity,
 llx_agefodd_session_formateur.rowid, --fk_agefodd_session_formateur,
 pls.datec,--date_session,
 DATE_ADD(DATE(pls.datec),INTERVAL DATE_FORMAT(pls.hrdeb,'%H:%i') HOUR_MINUTE),--heured,
 DATE_ADD(DATE(pls.datec),INTERVAL DATE_FORMAT(pls.hrfin,'%H:%i') HOUR_MINUTE), --heuref,
 --coutconsultant.montant/pls.nbhr,--trainer_cost,
 NULL,--fk_actioncomm,
 IFNULL(usercrea.rowid,1), --fk_user_author
 IFNULL(pls.created,NOW()),-- datec,
 IFNULL(usermod.rowid,1), --fk_user_mod
 IFNULL(pls.modified,NOW())--tms
 FROM pls 
INNER JOIN llx_agefodd_session ON llx_agefodd_session.import_key=pls.session_id
INNER JOIN llx_socpeople ON llx_socpeople.import_key=pls.interv_id
INNER JOIN llx_agefodd_formateur ON llx_agefodd_formateur.fk_socpeople=llx_socpeople.rowid
INNER JOIN llx_agefodd_session_formateur ON llx_agefodd_session.rowid=llx_agefodd_session_formateur.fk_session AND llx_agefodd_formateur.rowid=llx_agefodd_session_formateur.fk_agefodd_formateur
LEFT OUTER JOIN sesfr as coutconsultant ON coutconsultant.session_id=pls.session_id AND coutconsultant.typfr='CON' AND coutconsultant.interv_id=pls.interv_id
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=pls.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=pls.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
WHERE pls.status='confirmed';

--import trainee
TRUNCATE TABLE llx_agefodd_stagiaire;
INSERT INTO llx_agefodd_stagiaire (
entity,
nom,
prenom,
civilite,
fk_user_author,
fk_user_mod,
datec,
tms,
fk_soc,
fk_socpeople,
fonction,
tel1,
tel2,
mail,
date_birth,
place_birth,
note,
import_key) 
SELECT DISTINCT
1, --entity,
TRIM(eleves.nom),
IFNULL(TRIM(eleves.prenom),''),
eleves.civilite,
 IFNULL(usercrea.rowid,1), --fk_user_author
 IFNULL(usermod.rowid,1), --fk_user_mod
IFNULL(eleves.created,NOW()), --datec,
eleves.modified, --tms,
IFNULL(soc.rowid,(SELECT rowid from llx_societe where nom='Inconnue')), --fk_soc,
NULL, --fk_socpeople,
NULL, --fonction,
eleves.telephone, --tel1,
NULL,
eleves.email, --mail,
eleves.datnais, --date_birth,
NULL, --place_birth,
eleves.texte, --note,
eleves.id --import_key
FROM eleves 
INNER JOIN point ON eleves.id=point.eleves_id
INNER JOIN session as sess ON sess.id=point.session_id
LEFT OUTER JOIN llx_societe as soc ON soc.import_key=eleves.account_id
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=eleves.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=eleves.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email;

--Add trainee to session
TRUNCATE TABLE llx_agefodd_session_stagiaire;
INSERT INTO llx_agefodd_session_stagiaire (
fk_session_agefodd,
fk_stagiaire,
fk_agefodd_stagiaire_type,
status_in_session,
fk_user_author,
datec,
fk_user_mod,
tms,
import_key)
SELECT 
llx_agefodd_session.rowid,
llx_agefodd_stagiaire.rowid,
1,
3,
 IFNULL(usercrea.rowid,1), --fk_user_author
NOW(),
 IFNULL(usermod.rowid,1), --fk_user_mod
NOW(),
NULL
FROM eleves 
INNER JOIN point ON eleves.id=point.eleves_id
INNER JOIN session as sess ON sess.id=point.session_id
INNER JOIN llx_agefodd_session ON sess.id=llx_agefodd_session.import_key
INNER JOIN llx_agefodd_stagiaire ON llx_agefodd_stagiaire.import_key=point.eleves_id
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=eleves.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=eleves.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email;

--Update number of trainee per session
UPDATE llx_agefodd_session SET nb_stagiaire=(SELECT count(rowid) FROM llx_agefodd_session_stagiaire WHERE fk_session_agefodd = llx_agefodd_session.rowid), tms=tms WHERE (llx_agefodd_session.force_nb_stagiaire=0 OR llx_agefodd_session.force_nb_stagiaire IS NULL);

--Insert Session calendar
TRUNCATE TABLE  llx_agefodd_session_calendrier;
INSERT INTO llx_agefodd_session_calendrier (
fk_agefodd_session,
date_session,
heured,
heuref,
fk_actioncomm,
fk_user_author,
datec,
fk_user_mod,
tms)
SELECT 
agesess.rowid,
DATE(pls.datec),
DATE_ADD(DATE(pls.datec),INTERVAL DATE_FORMAT(pls.hrdeb,'%H:%i') HOUR_MINUTE),
DATE_ADD(DATE(pls.datec),INTERVAL DATE_FORMAT(pls.hrfin,'%H:%i') HOUR_MINUTE),
NULL,
1,
IFNULL(pls.created,NOW()),
1,
IFNULL(pls.modified,NOW())
FROM llx_agefodd_session as agesess
INNER JOIN pls ON agesess.import_key=pls.session_id;

--Insert Propal
TRUNCATE TABLE llx_propaldet;
TRUNCATE TABLE llx_propal;

INSERT INTO llx_propal (
ref,
entity,
ref_ext,
ref_int,
ref_client,
fk_soc,
fk_projet,
tms,
datec,
datep,
fin_validite,
date_valid,
date_cloture,
fk_user_author,
fk_user_valid,
fk_user_cloture,
fk_statut,
price,
remise_percent,
remise_absolue,
remise,
total_ht,
tva,
localtax1,
localtax2,
total,
fk_account,
fk_currency,
fk_cond_reglement,
fk_mode_reglement,
note_private,
note_public,
model_pdf,
date_livraison,
fk_availability,
fk_input_reason,
import_key,
extraparams,
fk_delivery_address) 
SELECT
proct.numct,  --ref,
1,  --entity,
NULL, --ref_ext,
NULL, --ref_int,
NULL, --ref_client,
soc.rowid,  --fk_soc,
NULL,  --fk_projet,
IFNULL(proct.modified,NOW()),  --tms,
IFNULL(proct.created,NOW()), --datec,
IFNULL(proct.created,NOW()),  --datep,
proct.dateps,  --fin_validite,
proct.dates,  --date_valid,
proct.dates,  --date_cloture,
 IFNULL(usercrea.rowid,1), --fk_user_author
1,    --fk_user_valid,
1,  --fk_user_cloture,
CASE WHEN proct.signe THEN 2 ELSE 3 END, --fk_statut,
0,  --price,
NULL,  --remise_percent,
NULL,  --remise_absolue,
0,  --remise,
0, --total_ht,
0,  --tva,
0,  --localtax1,
0,  --localtax2,
0,  --total,
NULL,  --fk_account,
NULL,  --fk_currency,
NULL,  --fk_cond_reglement,
NULL,  --fk_mode_reglement,
NULL,  --note_private,
NULL,  --note_public,
'azur',  --model_pdf,
NULL, --date_livraison,
0, --fk_availability,
0, --fk_input_reason,
proct.id, --import_key,
NULL, --extraparams,
NULL  --fk_delivery_address
FROM proct 
INNER JOIN llx_societe as soc ON soc.import_key=proct.account_id
INNER JOIN prolig ON prolig.proct_id=proct.id
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=proct.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
GROUP BY proct.id;


--Insert Propal det
INSERT INTO llx_propaldet (
fk_propal,
fk_parent_line,
fk_product,
label,
description,
fk_remise_except,
tva_tx,
localtax1_tx,
localtax1_type,
localtax2_tx,
localtax2_type,
qty,
remise_percent,
remise,
price,
subprice,
total_ht,
total_tva,
total_localtax1,
total_localtax2,
total_ttc,
product_type,
date_start,
date_end,
info_bits,
buy_price_ht,
fk_product_fournisseur_price,
special_code,
rang) 
SELECT 
prop.rowid, --fk_propal,
NULL,  --fk_parent_line,
NULL,  --fk_product,
NULL,  --label,
prolig.intitule,  --description,
NULL,  --fk_remise_except,
CASE WHEN prolig.ctva=1 THEN 19.600 ELSE 0 END,  --tva_tx,
0,  --localtax1_tx,
0,  --localtax1_type,
0,  --localtax2_tx,
0,  --localtax2_type,
prolig.nbjr, --qty,
0,  --remise_percent,
0,  --remise,
prolig.taux,  --price,
prolig.taux,  --subprice,
CASE WHEN prolig.ctva=1 THEN prolig.mont-(prolig.mont*0.196) ELSE prolig.mont END,  --total_ht,
CASE WHEN prolig.ctva=1 THEN prolig.mont*0.196 ELSE 0 END,  --total_tva,
0,  --total_localtax1,
0,  --total_localtax2,
prolig.mont,  --total_ttc,
1,  --product_type,
NULL,  --date_start,
NULL,  --date_end,
0,  --info_bits,
0,  --buy_price_ht,
NULL,  --fk_product_fournisseur_price,
0,  --special_code,
prolig.numlig  --rang
FROM prolig INNER JOIN llx_propal as prop ON prop.import_key=prolig.proct_id;


--Add propal contact
TRUNCATE TABLE llx_element_contact;
INSERT INTO llx_element_contact (
datecreate,
statut,
element_id,
fk_c_type_contact,
fk_socpeople)
SELECT DISTINCT
proct.created,
4,
llx_propal.rowid,
40,
llx_socpeople.rowid
FROM proct
INNER JOIN llx_propal ON llx_propal.import_key=proct.id
INNER JOIN contact ON proct.contact_id=contact.id
INNER JOIN llx_socpeople ON  llx_socpeople.import_key=contact.id;


--Update propal total amount
UPDATE llx_propal 
SET llx_propal.total_ht=(SELECT SUM(total_ht) FROM llx_propaldet WHERE llx_propaldet.fk_propal=llx_propal.rowid GROUP BY llx_propaldet.fk_propal),
llx_propal.tva=(SELECT SUM(total_tva) FROM llx_propaldet WHERE llx_propaldet.fk_propal=llx_propal.rowid  GROUP BY llx_propaldet.fk_propal),
llx_propal.total=(SELECT SUM(total) FROM llx_propaldet WHERE llx_propaldet.fk_propal=llx_propal.rowid  GROUP BY llx_propaldet.fk_propal),
llx_propal.tms=llx_propal.tms;

--Lier propal Session/client
TRUNCATE TABLE llx_agefodd_session_element;
INSERT INTO llx_agefodd_session_element(
fk_session_agefodd,
fk_soc,
element_type,
fk_element,
fk_user_author,
datec,
fk_user_mod,
tms)
SELECT 
llx_agefodd_session.rowid,
llx_societe.rowid,
'propal',
llx_propal.rowid,
1,
NOW(),
1,
NOW()
FROM llx_propal
INNER JOIN proct ON proct.id=llx_propal.import_key
INNER JOIN session as sess ON sess.id=proct.session_id
INNER JOIN llx_agefodd_session ON llx_agefodd_session.import_key=sess.id
INNER JOIN account ON account.id=proct.account_id
INNER JOIN llx_societe ON llx_societe.import_key=account.id;


--Import Invoice payé
TRUNCATE TABLE llx_facture;
TRUNCATE TABLE llx_facturedet;
INSERT INTO llx_facture (
facnumber,
entity,
ref_ext,
ref_int,
ref_client,
type,
increment,
fk_soc,datec,
datef,
date_valid,
tms,
paye,
amount,
remise_percent,
remise_absolue,
remise,
close_code,
close_note,
tva,
localtax1,
localtax2,
revenuestamp,
total,
total_ttc,
fk_statut,
fk_user_author,
fk_user_valid,
fk_facture_source,
fk_projet,
fk_account,
fk_currency,
fk_cond_reglement,
fk_mode_reglement,
date_lim_reglement,
note_private,
note_public,
model_pdf,
import_key,
extraparams)
SELECT 
lettrage.nopfact,  --facnumber
1,  --entity,
NULL,  --ref_ext,
NULL,  --ref_int,
NULL,  --ref_client,
0,  --type,
NULL,  --increment,
llx_societe.rowid,  --fk_soc,
IFNULL(lettrage.datefact,NOW()),  --datec,
IFNULL(lettrage.datefact,NOW()),  --datef,
IFNULL(lettrage.datefact,NOW()),  --date_valid,
NOW(),  --tms,
1,  --paye,
0,  --amount,
NULL,  --remise_percent,
NULL,  --remise_absolue,
0,  --remise,
NULL,  --close_code,
NULL,  --close_note,
0,  --tva,
0,  --localtax1,
0,  --localtax2,
0,  --revenuestamp,
0,  --total,
0,  --total_ttc,
2,  --fk_statut,
1,  --fk_user_author,
1,  --fk_user_valid,
NULL,  --fk_facture_source,
NULL,  --fk_projet,
NULL,  --fk_account,
NULL,  --fk_currency,
llx_societe.cond_reglement,  --fk_cond_reglement,
llx_societe.mode_reglement,  --fk_mode_reglement,
MAX(tempfact.dateche),  --date_lim_reglement,
NULL,  --note_private,
NULL,  --note_public,
'crabe',  --model_pdf,
lettrage.id,  --import_key,
NULL  --extraparams
FROM lettrage
INNER JOIN tempfact ON tempfact.numfact=lettrage.nopfact
INNER JOIN thirdparty ON thirdparty.id=lettrage.thirdparty_id
INNER JOIN account ON account.id=thirdparty.account_id
INNER JOIN llx_societe ON llx_societe.import_key=account.id
INNER JOIN convct ON convct.id=lettrage.convct_id
WHERE convct.supprime=0
GROUP BY lettrage.nopfact;

--import facture impeye
INSERT INTO llx_facture (
facnumber,
entity,
ref_ext,
ref_int,
ref_client,
type,
increment,
fk_soc,datec,
datef,
date_valid,
tms,
paye,
amount,
remise_percent,
remise_absolue,
remise,
close_code,
close_note,
tva,
localtax1,
localtax2,
revenuestamp,
total,
total_ttc,
fk_statut,
fk_user_author,
fk_user_valid,
fk_facture_source,
fk_projet,
fk_account,
fk_currency,
fk_cond_reglement,
fk_mode_reglement,
date_lim_reglement,
note_private,
note_public,
model_pdf,
import_key,
extraparams)
SELECT 
mvt.piece,  --facnumber
1,  --entity,
NULL,  --ref_ext,
NULL,  --ref_int,
NULL,  --ref_client,
0,  --type,
NULL,  --increment,
llx_societe.rowid,  --fk_soc,
IFNULL(mvt.datefact,NOW()),  --datec,
IFNULL(mvt.datefact,NOW()),  --datef,
IFNULL(mvt.datefact,NOW()),  --date_valid,
NOW(),  --tms,
0,  --paye,
0,  --amount,
NULL,  --remise_percent,
NULL,  --remise_absolue,
0,  --remise,
NULL,  --close_code,
NULL,  --close_note,
0,  --tva,
0,  --localtax1,
0,  --localtax2,
0,  --revenuestamp,
0,  --total,
0,  --total_ttc,
1,  --fk_statut,
1,  --fk_user_author,
1,  --fk_user_valid,
NULL,  --fk_facture_source,
NULL,  --fk_projet,
NULL,  --fk_account,
NULL,  --fk_currency,
llx_societe.cond_reglement,  --fk_cond_reglement,
llx_societe.mode_reglement,  --fk_mode_reglement,
MAX(tempfact.dateche),  --date_lim_reglement,
NULL,  --note_private,
NULL,  --note_public,
'crabe',  --model_pdf,
mvt.id,  --import_key,
NULL  --extraparams
FROM mvt
INNER JOIN tempfact ON tempfact.numfact=mvt.piece
INNER JOIN thirdparty ON thirdparty.id=mvt.thirdparty_id
INNER JOIN account ON account.id=thirdparty.account_id
INNER JOIN llx_societe ON llx_societe.import_key=account.id
INNER JOIN convct ON convct.id=mvt.convct_id
WHERE convct.supprime=0
AND mvt.piece NOT IN (SELECT llx_facture.facnumber FROM llx_facture)
GROUP BY mvt.piece;

--Import Invoice line
INSERT INTO llx_facturedet (
fk_facture,
fk_parent_line,
fk_product,
label,
description,
tva_tx,
localtax1_tx,
localtax1_type,
localtax2_tx,
localtax2_type,
qty,
remise_percent,
remise,
fk_remise_except,
subprice,
price,
total_ht,
total_tva,
total_localtax1,
total_localtax2,
total_ttc,
product_type,
date_start,
date_end,
info_bits,
buy_price_ht,
fk_product_fournisseur_price,
fk_code_ventilation,
special_code,
rang,
import_key) 
SELECT DISTINCT
llx_facture.rowid,  --fk_facture,
NULL,  --fk_parent_line,
NULL,  --fk_product,
NULL,  --label,
tempfact.intitule,  --description,
tempfact.tauxtva,  --tva_tx,
0,  --localtax1_tx,
0,  --localtax1_type,
0,  --localtax2_tx,
0,  --localtax2_type,
tempfact.nbjr,  --qty,
0,  --remise_percent,
0,  --remise,
NULL,  --fk_remise_except,
tempfact.taux,  --subprice,
NULL,  --price,
tempfact.montant,  --total_ht,
tempfact.mttva,  --total_tva,
0,  --total_localtax1,
0,  --total_localtax2,
tempfact.mtttc,  --total_ttc,
1,  --product_type,
NULL,  --date_start,
NULL,  --date_end,
0,  --info_bits,
0,  --buy_price_ht,
NULL,  --fk_product_fournisseur_price,
0,  --fk_code_ventilation,
0,  --special_code,
tempfact.numlig,  --rang,
NULL  --import_key
FROM llx_facture
INNER JOIN tempfact ON tempfact.numfact=llx_facture.facnumber
INNER JOIN thirdparty ON thirdparty.id=tempfact.thirdparty_id
INNER JOIN account ON account.id=thirdparty.account_id
INNER JOIN llx_societe ON llx_societe.import_key=account.id
INNER JOIN convct ON convct.id=tempfact.convct_id;


--Update invoice header amount
UPDATE llx_facture
SET llx_facture.total_ttc=(SELECT SUM(total_ttc) FROM llx_facturedet WHERE llx_facturedet.fk_facture=llx_facture.rowid GROUP BY llx_facturedet.fk_facture),
llx_facture.tva=(SELECT SUM(total_tva) FROM llx_facturedet WHERE llx_facturedet.fk_facture=llx_facture.rowid  GROUP BY llx_facturedet.fk_facture),
llx_facture.total=(SELECT SUM(total) FROM llx_facturedet WHERE llx_facturedet.fk_facture=llx_facture.rowid  GROUP BY llx_facturedet.fk_facture),
llx_facture.tms=llx_facture.tms;


--Add invoice contact
INSERT INTO llx_element_contact (
datecreate,
statut,
element_id,
fk_c_type_contact,
fk_socpeople)
SELECT DISTINCT
convct.created,
4,
llx_facture.rowid,
60,
llx_socpeople.rowid
FROM tempfact
INNER JOIN llx_facture ON tempfact.numfact=llx_facture.facnumber
INNER JOIN convct ON convct.id=tempfact.convct_id
INNER JOIN contact ON convct.recipient_contact_id=contact.id
INNER JOIN llx_socpeople ON  llx_socpeople.import_key=contact.id;


--Lier facture Session/client
INSERT INTO llx_agefodd_session_element(
fk_session_agefodd,
fk_soc,
element_type,
fk_element,
fk_user_author,
datec,
fk_user_mod,
tms)
SELECT DISTINCT
llx_agefodd_session.rowid,
llx_societe.rowid,
'invoice',
llx_facture.rowid,
1,
NOW(),
1,
NOW()
FROM llx_facture
INNER JOIN tempfact ON tempfact.numfact=llx_facture.facnumber
INNER JOIN convct ON convct.id=tempfact.convct_id
INNER JOIN session as sess ON sess.id=convct.session_id
INNER JOIN llx_agefodd_session ON llx_agefodd_session.import_key=sess.id
INNER JOIN account ON account.id=convct.ent_account_id
INNER JOIN llx_societe ON llx_societe.import_key=account.id





TRUNCATE tABLE `llx_categorie_product`;
INSERT INTO `llx_categorie_product` (`fk_categorie`, `fk_product`, `import_key`) VALUES
(24, 133, NULL),
(24, 132, NULL),
(7, 138, NULL),
(7, 141, NULL),
(7, 144, NULL),
(7, 143, NULL),
(7, 142, NULL),
(9, 116, NULL),
(9, 119, NULL),
(9, 118, NULL),
(9, 117, NULL),
(9, 115, NULL),
(9, 124, NULL),
(24, 125, NULL),
(10, 126, NULL),
(3, 130, NULL),
(9, 154, NULL),
(10, 127, NULL),
(7, 137, NULL),
(24, 135, NULL),
(7, 145, NULL),
(9, 120, NULL),
(7, 153, NULL),
(9, 123, NULL),
(10, 129, NULL),
(25, 131, NULL),
(23, 147, NULL);

TRUNCATE TABLE `llx_usergroup`;
INSERT INTO `llx_usergroup` (`rowid`, `nom`, `entity`, `datec`, `tms`, `note`) VALUES
(1, 'Direction', 1, '2013-10-24 07:34:32', '2013-10-24 07:34:32', ''),
(2, 'Général', 1, '2013-10-24 07:35:41', '2013-10-24 07:35:41', ''),
(3, 'Spécifique', 1, '2013-10-24 07:37:05', '2013-10-24 07:37:05', '');

TRUNCATE TABLE `llx_usergroup_user`;
INSERT INTO `llx_usergroup_user` (`rowid`, `entity`, `fk_user`, `fk_usergroup`) VALUES
(7, 1, 2, 2),
(10, 1, 3, 3),
(4, 1, 4, 2),
(3, 1, 5, 2),
(9, 1, 6, 2),
(1, 1, 8, 1),
(2, 1, 10, 1),
(5, 1, 11, 2),
(6, 1, 13, 2),
(8, 1, 14, 2),
(11, 1, 15, 3),
(12, 1, 38, 1);


TRUNCATE TABLE `llx_usergroup_rights`;
INSERT INTO `llx_usergroup_rights` (`rowid`, `fk_usergroup`, `fk_id`) VALUES
(40, 1, 11),
(41, 1, 12),
(42, 1, 13),
(43, 1, 14),
(44, 1, 15),
(45, 1, 16),
(46, 1, 19),
(77, 1, 21),
(78, 1, 22),
(79, 1, 24),
(80, 1, 25),
(81, 1, 26),
(82, 1, 27),
(83, 1, 28),
(26, 1, 81),
(27, 1, 82),
(28, 1, 84),
(29, 1, 86),
(30, 1, 87),
(31, 1, 88),
(32, 1, 89),
(34, 1, 95),
(35, 1, 96),
(36, 1, 97),
(37, 1, 98),
(16, 1, 111),
(17, 1, 112),
(18, 1, 113),
(19, 1, 114),
(20, 1, 115),
(21, 1, 116),
(22, 1, 117),
(88, 1, 121),
(89, 1, 122),
(90, 1, 125),
(91, 1, 126),
(75, 1, 221),
(74, 1, 222),
(73, 1, 223),
(72, 1, 229),
(71, 1, 237),
(70, 1, 238),
(76, 1, 239),
(23, 1, 241),
(24, 1, 242),
(25, 1, 243),
(97, 1, 251),
(98, 1, 252),
(99, 1, 253),
(100, 1, 254),
(101, 1, 255),
(102, 1, 256),
(92, 1, 262),
(93, 1, 281),
(94, 1, 282),
(95, 1, 283),
(96, 1, 286),
(103, 1, 341),
(104, 1, 342),
(105, 1, 343),
(106, 1, 344),
(107, 1, 351),
(108, 1, 352),
(109, 1, 353),
(110, 1, 354),
(111, 1, 358),
(278, 1, 531),
(85, 1, 532),
(86, 1, 534),
(87, 1, 538),
(48, 1, 1181),
(49, 1, 1182),
(50, 1, 1183),
(51, 1, 1184),
(52, 1, 1185),
(53, 1, 1186),
(54, 1, 1187),
(55, 1, 1188),
(39, 1, 1201),
(38, 1, 1202),
(56, 1, 1231),
(57, 1, 1232),
(58, 1, 1233),
(59, 1, 1234),
(60, 1, 1235),
(61, 1, 1236),
(62, 1, 1237),
(69, 1, 1251),
(47, 1, 1321),
(33, 1, 1421),
(9, 1, 2401),
(10, 1, 2402),
(11, 1, 2403),
(12, 1, 2411),
(13, 1, 2412),
(14, 1, 2413),
(15, 1, 2414),
(63, 1, 20001),
(64, 1, 20002),
(65, 1, 20003),
(66, 1, 20004),
(67, 1, 20005),
(68, 1, 20006),
(1, 1, 103001),
(2, 1, 103002),
(3, 1, 103003),
(4, 1, 103004),
(5, 1, 103005),
(6, 1, 103006),
(7, 1, 103007),
(8, 1, 103008),
(131, 2, 11),
(132, 2, 12),
(133, 2, 13),
(134, 2, 14),
(135, 2, 15),
(136, 2, 16),
(157, 2, 21),
(158, 2, 22),
(159, 2, 24),
(160, 2, 25),
(161, 2, 26),
(162, 2, 27),
(163, 2, 28),
(168, 2, 121),
(169, 2, 122),
(170, 2, 125),
(129, 2, 241),
(173, 2, 281),
(174, 2, 282),
(175, 2, 283),
(176, 2, 286),
(164, 2, 531),
(139, 2, 1181),
(140, 2, 1182),
(141, 2, 1183),
(142, 2, 1184),
(143, 2, 1185),
(144, 2, 1186),
(145, 2, 1187),
(146, 2, 1188),
(147, 2, 1231),
(148, 2, 1232),
(149, 2, 1233),
(150, 2, 1234),
(151, 2, 1235),
(152, 2, 1236),
(153, 2, 1237),
(154, 2, 1321),
(120, 2, 2401),
(121, 2, 2402),
(122, 2, 2403),
(126, 2, 2414),
(155, 2, 20001),
(156, 2, 20006),
(283, 2, 103001),
(254, 2, 103005),
(256, 2, 103007),
(258, 2, 103008),
(203, 3, 11),
(204, 3, 12),
(205, 3, 13),
(206, 3, 14),
(207, 3, 15),
(208, 3, 16),
(209, 3, 19),
(235, 3, 21),
(236, 3, 22),
(237, 3, 24),
(238, 3, 25),
(239, 3, 26),
(240, 3, 27),
(241, 3, 28),
(268, 3, 121),
(269, 3, 122),
(270, 3, 125),
(271, 3, 126),
(233, 3, 221),
(232, 3, 222),
(231, 3, 223),
(230, 3, 229),
(229, 3, 237),
(228, 3, 238),
(234, 3, 239),
(192, 3, 241),
(272, 3, 262),
(273, 3, 281),
(274, 3, 282),
(275, 3, 283),
(276, 3, 286),
(242, 3, 531),
(211, 3, 1181),
(212, 3, 1182),
(213, 3, 1183),
(214, 3, 1184),
(215, 3, 1185),
(216, 3, 1186),
(217, 3, 1187),
(218, 3, 1188),
(202, 3, 1201),
(201, 3, 1202),
(219, 3, 1231),
(220, 3, 1232),
(221, 3, 1233),
(222, 3, 1234),
(223, 3, 1235),
(224, 3, 1236),
(225, 3, 1237),
(210, 3, 1321),
(185, 3, 2401),
(186, 3, 2402),
(187, 3, 2403),
(191, 3, 2414),
(226, 3, 20001),
(177, 3, 103001),
(178, 3, 103002),
(179, 3, 103003),
(180, 3, 103004),
(181, 3, 103005),
(182, 3, 103006),
(183, 3, 103007),
(184, 3, 103008),
(279, 1, 2354687),
(280, 2, 103002),
(282, 2, 103003);

TRUNCATE TABLE `llx_user_rights`;
INSERT INTO `llx_user_rights` (`rowid`, `fk_user`, `fk_id`) VALUES
(1198, 1, 11),
(1173, 1, 12),
(1179, 1, 13),
(1182, 1, 14),
(1188, 1, 15),
(1191, 1, 16),
(1197, 1, 19),
(1104, 1, 21),
(1078, 1, 22),
(1083, 1, 24),
(1089, 1, 25),
(1093, 1, 26),
(1099, 1, 27),
(1105, 1, 28),
(499, 1, 81),
(471, 1, 82),
(477, 1, 84),
(483, 1, 86),
(486, 1, 87),
(492, 1, 88),
(498, 1, 89),
(1155, 1, 95),
(1158, 1, 96),
(1165, 1, 97),
(1164, 1, 98),
(1285, 1, 111),
(1254, 1, 112),
(1260, 1, 113),
(1266, 1, 114),
(1272, 1, 115),
(1278, 1, 116),
(1284, 1, 117),
(1383, 1, 121),
(1372, 1, 122),
(1378, 1, 125),
(1384, 1, 126),
(249, 1, 221),
(238, 1, 222),
(244, 1, 223),
(250, 1, 229),
(255, 1, 237),
(258, 1, 238),
(261, 1, 239),
(273, 1, 241),
(268, 1, 242),
(274, 1, 243),
(1389, 1, 262),
(1407, 1, 281),
(1396, 1, 282),
(1402, 1, 283),
(1408, 1, 286),
(1, 1, 341),
(2, 1, 342),
(3, 1, 343),
(4, 1, 344),
(1465, 1, 531),
(933, 1, 532),
(939, 1, 534),
(945, 1, 538),
(1290, 1, 1181),
(1362, 1, 1182),
(1297, 1, 1183),
(1303, 1, 1184),
(1309, 1, 1185),
(1315, 1, 1186),
(1321, 1, 1187),
(1326, 1, 1188),
(1356, 1, 1231),
(1336, 1, 1232),
(1342, 1, 1233),
(1348, 1, 1234),
(1353, 1, 1235),
(1357, 1, 1236),
(1363, 1, 1237),
(1203, 1, 1321),
(504, 1, 1421),
(960, 1, 2401),
(955, 1, 2402),
(961, 1, 2403),
(975, 1, 2411),
(970, 1, 2412),
(976, 1, 2413),
(981, 1, 2414),
(279, 1, 20001),
(282, 1, 20002),
(285, 1, 20003),
(288, 1, 20004),
(291, 1, 20005),
(294, 1, 20006),
(336, 1, 103001),
(300, 1, 103002),
(306, 1, 103003),
(312, 1, 103004),
(318, 1, 103005),
(324, 1, 103006),
(331, 1, 103007),
(337, 1, 103008),
(251, 8, 221),
(240, 8, 222),
(246, 8, 223),
(252, 8, 229),
(256, 8, 237),
(259, 8, 238),
(262, 8, 239),
(275, 8, 241),
(270, 8, 242),
(276, 8, 243),
(280, 8, 20001),
(283, 8, 20002),
(286, 8, 20003),
(289, 8, 20004),
(292, 8, 20005),
(295, 8, 20006),
(253, 10, 221),
(242, 10, 222),
(248, 10, 223),
(254, 10, 229),
(257, 10, 237),
(260, 10, 238),
(263, 10, 239),
(277, 10, 241),
(272, 10, 242),
(278, 10, 243),
(281, 10, 20001),
(284, 10, 20002),
(287, 10, 20003),
(290, 10, 20004),
(293, 10, 20005),
(296, 10, 20006),
(338, 8, 103001),
(340, 10, 103001),
(302, 8, 103002),
(304, 10, 103002),
(308, 8, 103003),
(310, 10, 103003),
(314, 8, 103004),
(316, 10, 103004),
(320, 8, 103005),
(322, 10, 103005),
(326, 8, 103006),
(328, 10, 103006),
(333, 8, 103007),
(335, 10, 103007),
(339, 8, 103008),
(341, 10, 103008),
(1385, 8, 121),
(1387, 10, 121),
(1374, 8, 122),
(1376, 10, 122),
(1380, 8, 125),
(1382, 10, 125),
(1386, 8, 126),
(1388, 10, 126),
(1390, 8, 262),
(1391, 10, 262),
(1409, 8, 281),
(1411, 10, 281),
(1398, 8, 282),
(1400, 10, 282),
(1404, 8, 283),
(1406, 10, 283),
(1410, 8, 286),
(1412, 10, 286),
(1106, 8, 21),
(1108, 10, 21),
(1080, 8, 22),
(1082, 10, 22),
(1085, 8, 24),
(1087, 10, 24),
(1090, 8, 25),
(1091, 10, 25),
(1095, 8, 26),
(1097, 10, 26),
(1101, 8, 27),
(1103, 10, 27),
(1107, 8, 28),
(1109, 10, 28),
(501, 8, 81),
(503, 10, 81),
(473, 8, 82),
(475, 10, 82),
(479, 8, 84),
(481, 10, 84),
(484, 8, 86),
(485, 10, 86),
(488, 8, 87),
(490, 10, 87),
(494, 8, 88),
(496, 10, 88),
(500, 8, 89),
(502, 10, 89),
(505, 8, 1421),
(506, 10, 1421),
(1156, 8, 95),
(1157, 10, 95),
(1159, 8, 96),
(1160, 10, 96),
(1167, 8, 97),
(1169, 10, 97),
(1166, 8, 98),
(1168, 10, 98),
(1200, 8, 11),
(1202, 10, 11),
(1175, 8, 12),
(1177, 10, 12),
(1180, 8, 13),
(1181, 10, 13),
(1184, 8, 14),
(1186, 10, 14),
(1189, 8, 15),
(1190, 10, 15),
(1193, 8, 16),
(1195, 10, 16),
(1199, 8, 19),
(1201, 10, 19),
(1204, 8, 1321),
(1205, 10, 1321),
(1287, 8, 111),
(1289, 10, 111),
(1256, 8, 112),
(1258, 10, 112),
(1262, 8, 113),
(1264, 10, 113),
(1268, 8, 114),
(1270, 10, 114),
(1274, 8, 115),
(1276, 10, 115),
(1280, 8, 116),
(1282, 10, 116),
(1286, 8, 117),
(1288, 10, 117),
(1291, 8, 1181),
(1292, 10, 1181),
(1364, 8, 1182),
(1366, 10, 1182),
(1299, 8, 1183),
(1301, 10, 1183),
(1305, 8, 1184),
(1307, 10, 1184),
(1311, 8, 1185),
(1313, 10, 1185),
(1317, 8, 1186),
(1319, 10, 1186),
(1323, 8, 1187),
(1325, 10, 1187),
(1328, 8, 1188),
(1330, 10, 1188),
(1358, 8, 1231),
(1360, 10, 1231),
(1338, 8, 1232),
(1340, 10, 1232),
(1344, 8, 1233),
(1346, 10, 1233),
(1350, 8, 1234),
(1352, 10, 1234),
(1354, 8, 1235),
(1355, 10, 1235),
(1359, 8, 1236),
(1361, 10, 1236),
(1365, 8, 1237),
(1367, 10, 1237),
(948, 8, 531),
(1467, 10, 531),
(935, 8, 532),
(937, 10, 532),
(941, 8, 534),
(943, 10, 534),
(947, 8, 538),
(949, 10, 538),
(962, 8, 2401),
(964, 10, 2401),
(957, 8, 2402),
(959, 10, 2402),
(963, 8, 2403),
(965, 10, 2403),
(977, 8, 2411),
(979, 10, 2411),
(972, 8, 2412),
(974, 10, 2412),
(978, 8, 2413),
(980, 10, 2413),
(982, 8, 2414),
(983, 10, 2414),
(1422, 1, 41),
(1424, 8, 41),
(1426, 10, 41),
(1417, 1, 42),
(1419, 8, 42),
(1421, 10, 42),
(1423, 1, 44),
(1425, 8, 44),
(1427, 10, 44),
(1437, 1, 141),
(1439, 8, 141),
(1441, 10, 141),
(1432, 1, 142),
(1434, 8, 142),
(1436, 10, 142),
(1438, 1, 144),
(1440, 8, 144),
(1442, 10, 144),
(1443, 38, 1181),
(1444, 38, 1231),
(1445, 38, 121),
(1446, 38, 262),
(1447, 38, 281),
(1448, 38, 21),
(1449, 38, 1182),
(1450, 38, 221),
(1451, 38, 241),
(1452, 38, 341),
(1453, 38, 342),
(1454, 38, 343),
(1455, 38, 344),
(1456, 38, 531),
(1457, 38, 11),
(1458, 38, 95),
(1459, 38, 97),
(1460, 38, 2401),
(1461, 38, 20001),
(1462, 38, 103001),
(1463, 38, 111),
(1464, 38, 1201),
(1466, 1, 2354687),
(1468, 10, 2354687);




TRUNCATE TABLE `llx_user_extrafields`;
INSERT INTO `llx_user_extrafields` (`rowid`, `tms`, `fk_object`, `import_key`, `u_prospection`, `u_commercial`, `u_logistique`, `u_interentreprises`, `u_communication`) VALUES
(10, '2013-11-01 22:26:31', 6, NULL, 1, 1, NULL, NULL, NULL),
(2, '2013-11-01 22:19:19', 14, NULL, NULL, NULL, 1, NULL, NULL),
(4, '2013-11-01 22:20:13', 13, NULL, NULL, NULL, 1, NULL, NULL),
(5, '2013-11-01 22:20:58', 11, NULL, NULL, 1, NULL, NULL, NULL),
(6, '2013-11-01 22:21:44', 5, NULL, 1, 1, NULL, NULL, NULL),
(7, '2013-11-01 22:22:12', 4, NULL, NULL, 1, NULL, NULL, NULL),
(15, '2013-11-02 09:00:32', 3, NULL, 1, 1, NULL, 1, NULL),
(9, '2013-11-01 22:25:31', 15, NULL, NULL, NULL, NULL, NULL, 1),
(12, '2013-11-01 22:27:50', 2, NULL, NULL, NULL, 1, NULL, NULL),
(13, '2013-11-01 22:31:12', 8, NULL, NULL, 1, NULL, NULL, NULL);

TRUNCATE TABLE llx_rights_def;
INSERT INTO `llx_rights_def` (`id`, `libelle`, `module`, `entity`, `perms`, `subperms`, `type`, `bydefault`) VALUES
(11, 'Lire les factures', 'facture', 1, 'lire', NULL, 'a', 1),
(12, 'Creer/modifier les factures', 'facture', 1, 'creer', NULL, 'a', 0),
(13, 'Dévalider les factures', 'facture', 1, 'invoice_advance', 'unvalidate', 'a', 0),
(14, 'Valider les factures', 'facture', 1, 'valider', NULL, 'a', 0),
(15, 'Envoyer les factures par mail', 'facture', 1, 'invoice_advance', 'send', 'a', 0),
(16, 'Emettre des paiements sur les factures', 'facture', 1, 'paiement', NULL, 'a', 0),
(19, 'Supprimer les factures', 'facture', 1, 'supprimer', NULL, 'a', 0),
(21, 'Lire les propositions commerciales', 'propale', 1, 'lire', NULL, 'r', 1),
(22, 'Creer/modifier les propositions commerciales', 'propale', 1, 'creer', NULL, 'w', 0),
(24, 'Valider les propositions commerciales', 'propale', 1, 'valider', NULL, 'd', 0),
(25, 'Envoyer les propositions commerciales aux clients', 'propale', 1, 'propal_advance', 'send', 'd', 0),
(26, 'Cloturer les propositions commerciales', 'propale', 1, 'cloturer', NULL, 'd', 0),
(27, 'Supprimer les propositions commerciales', 'propale', 1, 'supprimer', NULL, 'd', 0),
(28, 'Exporter les propositions commerciales et attributs', 'propale', 1, 'export', NULL, 'r', 0),
(81, 'Lire les commandes clients', 'commande', 1, 'lire', NULL, 'r', 1),
(82, 'Creer/modifier les commandes clients', 'commande', 1, 'creer', NULL, 'w', 0),
(84, 'Valider les commandes clients', 'commande', 1, 'valider', NULL, 'd', 0),
(86, 'Envoyer les commandes clients', 'commande', 1, 'order_advance', 'send', 'd', 0),
(87, 'Cloturer les commandes clients', 'commande', 1, 'cloturer', NULL, 'd', 0),
(88, 'Annuler les commandes clients', 'commande', 1, 'annuler', NULL, 'd', 0),
(89, 'Supprimer les commandes clients', 'commande', 1, 'supprimer', NULL, 'd', 0),
(95, 'Lire CA, bilans, resultats', 'compta', 1, 'resultat', 'lire', 'r', 1),
(96, 'Parametrer la ventilation', 'compta', 1, 'ventilation', 'parametrer', 'r', 0),
(97, 'Lire les ventilations de factures', 'compta', 1, 'ventilation', 'lire', 'r', 1),
(98, 'Ventiler les lignes de factures', 'compta', 1, 'ventilation', 'creer', 'r', 0),
(111, 'Lire les comptes bancaires', 'banque', 1, 'lire', NULL, 'r', 1),
(112, 'Creer/modifier montant/supprimer ecriture bancaire', 'banque', 1, 'modifier', NULL, 'w', 0),
(113, 'Configurer les comptes bancaires (creer, gerer categories)', 'banque', 1, 'configurer', NULL, 'a', 0),
(114, 'Rapprocher les ecritures bancaires', 'banque', 1, 'consolidate', NULL, 'w', 0),
(115, 'Exporter transactions et releves', 'banque', 1, 'export', NULL, 'r', 0),
(116, 'Virements entre comptes', 'banque', 1, 'transfer', NULL, 'w', 0),
(117, 'Gerer les envois de cheques', 'banque', 1, 'cheque', NULL, 'w', 0),
(121, 'Lire les societes', 'societe', 1, 'lire', NULL, 'r', 1),
(122, 'Creer modifier les societes', 'societe', 1, 'creer', NULL, 'w', 0),
(125, 'Supprimer les societes', 'societe', 1, 'supprimer', NULL, 'd', 0),
(126, 'Exporter les societes', 'societe', 1, 'export', NULL, 'r', 0),
(221, 'Consulter les mailings', 'mailing', 1, 'lire', NULL, 'r', 1),
(222, 'Creer/modifier les mailings (sujet, destinataires...)', 'mailing', 1, 'creer', NULL, 'w', 0),
(223, 'Valider les mailings (permet leur envoi)', 'mailing', 1, 'valider', NULL, 'w', 0),
(229, 'Supprimer les mailings', 'mailing', 1, 'supprimer', NULL, 'd', 0),
(237, 'View recipients and info', 'mailing', 1, 'mailing_advance', 'recipient', 'r', 0),
(238, 'Manually send mailings', 'mailing', 1, 'mailing_advance', 'send', 'w', 0),
(239, 'Delete mailings after validation and/or sent', 'mailing', 1, 'mailing_advance', 'delete', 'd', 0),
(241, 'Lire les categories', 'categorie', 1, 'lire', NULL, 'r', 1),
(242, 'Creer/modifier les categories', 'categorie', 1, 'creer', NULL, 'w', 0),
(243, 'Supprimer les categories', 'categorie', 1, 'supprimer', NULL, 'd', 0),
(251, 'Consulter les autres utilisateurs', 'user', 1, 'user', 'lire', 'r', 0),
(252, 'Consulter les permissions des autres utilisateurs', 'user', 1, 'user_advance', 'readperms', 'r', 0),
(253, 'Creer/modifier utilisateurs internes et externes', 'user', 1, 'user', 'creer', 'w', 0),
(254, 'Creer/modifier utilisateurs externes seulement', 'user', 1, 'user_advance', 'write', 'w', 0),
(255, 'Modifier le mot de passe des autres utilisateurs', 'user', 1, 'user', 'password', 'w', 0),
(256, 'Supprimer ou desactiver les autres utilisateurs', 'user', 1, 'user', 'supprimer', 'd', 0),
(262, 'Consulter tous les tiers par utilisateurs internes (sinon uniquement si contact commercial). Non effectif pour utilisateurs externes (tjs limités à eux-meme).', 'societe', 1, 'client', 'voir', 'r', 1),
(281, 'Lire les contacts', 'societe', 1, 'contact', 'lire', 'r', 1),
(282, 'Creer modifier les contacts', 'societe', 1, 'contact', 'creer', 'w', 0),
(283, 'Supprimer les contacts', 'societe', 1, 'contact', 'supprimer', 'd', 0),
(286, 'Exporter les contacts', 'societe', 1, 'contact', 'export', 'd', 0),
(341, 'Consulter ses propres permissions', 'user', 1, 'self_advance', 'readperms', 'r', 1),
(342, 'Creer/modifier ses propres infos utilisateur', 'user', 1, 'self', 'creer', 'w', 1),
(343, 'Modifier son propre mot de passe', 'user', 1, 'self', 'password', 'w', 1),
(344, 'Modifier ses propres permissions', 'user', 1, 'self_advance', 'writeperms', 'w', 1),
(351, 'Consulter les groupes', 'user', 1, 'group_advance', 'read', 'r', 0),
(352, 'Consulter les permissions des groupes', 'user', 1, 'group_advance', 'readperms', 'r', 0),
(353, 'Creer/modifier les groupes et leurs permissions', 'user', 1, 'group_advance', 'write', 'w', 0),
(354, 'Supprimer ou desactiver les groupes', 'user', 1, 'group_advance', 'delete', 'd', 0),
(358, 'Exporter les utilisateurs', 'user', 1, 'user', 'export', 'r', 0),
(531, 'Lire les services', 'service', 1, 'lire', NULL, 'r', 1),
(532, 'Creer/modifier les services', 'service', 1, 'creer', NULL, 'w', 0),
(534, 'Supprimer les services', 'service', 1, 'supprimer', NULL, 'd', 0),
(538, 'Exporter les services', 'service', 1, 'export', NULL, 'r', 0),
(1181, 'Consulter les fournisseurs', 'fournisseur', 1, 'lire', NULL, 'r', 1),
(1182, 'Consulter les commandes fournisseur', 'fournisseur', 1, 'commande', 'lire', 'r', 1),
(1183, 'Creer une commande fournisseur', 'fournisseur', 1, 'commande', 'creer', 'w', 0),
(1184, 'Valider une commande fournisseur', 'fournisseur', 1, 'commande', 'valider', 'w', 0),
(1185, 'Approuver une commande fournisseur', 'fournisseur', 1, 'commande', 'approuver', 'w', 0),
(1186, 'Commander une commande fournisseur', 'fournisseur', 1, 'commande', 'commander', 'w', 0),
(1187, 'Receptionner une commande fournisseur', 'fournisseur', 1, 'commande', 'receptionner', 'd', 0),
(1188, 'Supprimer une commande fournisseur', 'fournisseur', 1, 'commande', 'supprimer', 'd', 0),
(1231, 'Consulter les factures fournisseur', 'fournisseur', 1, 'facture', 'lire', 'r', 1),
(1232, 'Creer une facture fournisseur', 'fournisseur', 1, 'facture', 'creer', 'w', 0),
(1233, 'Valider une facture fournisseur', 'fournisseur', 1, 'facture', 'valider', 'w', 0),
(1234, 'Supprimer une facture fournisseur', 'fournisseur', 1, 'facture', 'supprimer', 'd', 0),
(1235, 'Envoyer les factures par mail', 'fournisseur', 1, 'supplier_invoice_advance', 'send', 'a', 0),
(1236, 'Exporter les factures fournisseurs, attributs et reglements', 'fournisseur', 1, 'facture', 'export', 'r', 0),
(1237, 'Exporter les commande fournisseurs, attributs', 'fournisseur', 1, 'commande', 'export', 'r', 0),
(1321, 'Exporter les factures clients, attributs et reglements', 'facture', 1, 'facture', 'export', 'r', 0),
(1421, 'Exporter les commandes clients et attributs', 'commande', 1, 'commande', 'export', 'r', 0),
(2401, 'Read actions/tasks linked to his account', 'agenda', 1, 'myactions', 'read', 'r', 1),
(2402, 'Create/modify actions/tasks linked to his account', 'agenda', 1, 'myactions', 'create', 'w', 0),
(2403, 'Delete actions/tasks linked to his account', 'agenda', 1, 'myactions', 'delete', 'w', 0),
(2411, 'Read actions/tasks of others', 'agenda', 1, 'allactions', 'read', 'r', 0),
(2412, 'Create/modify actions/tasks of others', 'agenda', 1, 'allactions', 'create', 'w', 0),
(2413, 'Delete actions/tasks of others', 'agenda', 1, 'allactions', 'delete', 'w', 0),
(2414, 'Export actions/tasks of others', 'agenda', 1, 'export', NULL, 'w', 0),
(20001, 'Lire/créer/modifier ses demandes de congés payés', 'holiday', 1, 'write', NULL, 'w', 1),
(20002, 'Lire/créer/modifier toutes les demandes de congés payés', 'holiday', 1, 'lire_tous', NULL, 'w', 0),
(20003, 'Supprimer des demandes de congés payés', 'holiday', 1, 'delete', NULL, 'w', 0),
(20004, 'Définir les congés payés des utilisateurs', 'holiday', 1, 'define_holiday', NULL, 'w', 0),
(20005, 'Voir les logs de modification des congés payés', 'holiday', 1, 'view_log', NULL, 'w', 0),
(20006, 'Accéder au rapport mensuel des congés payés', 'holiday', 1, 'month_report', NULL, 'w', 0),
(103001, 'Voir les sesisons', 'agefodd', 1, 'lire', NULL, 'w', 1),
(103002, 'Modifier les sessions', 'agefodd', 1, 'modifier', NULL, 'w', 0),
(103003, 'Creer les sessions', 'agefodd', 1, 'creer', NULL, 'w', 0),
(103004, 'Suppression des sessions', 'agefodd', 1, 'supprimer', NULL, 'w', 0),
(103005, 'Voir stats', 'agefodd', 1, 'viewstats', NULL, 'w', 0),
(103006, 'export', 'agefodd', 1, 'export', NULL, 'w', 0),
(103007, 'agenda', 'agefodd', 1, 'agenda', NULL, 'w', 0),
(103008, 'agendatrainer', 'agefodd', 1, 'agendatrainer', NULL, 'w', 0),
(103009, 'Voir les formations du catalogue', 'agefodd', 1, 'agefodd_formation_catalogue', 'lire', 'r', 1),
(103010, 'Creer/Modifier les formations du catalogue', 'agefodd', 1, 'agefodd_formation_catalogue', 'creer', 'r', 1),
(103011, 'Supprimer les formations du catalogue', 'agefodd', 1, 'agefodd_formation_catalogue', 'supprimer', 'r', 1),
(103012, 'Voir les sites (lieux)', 'agefodd', 1, 'agefodd_place', 'lire', 'r', 1),
(103013, 'Creer/Modifier les sites (lieux)', 'agefodd', 1, 'agefodd_place', 'creer', 'r', 1),
(103014, 'Supprimer les sites (lieux)', 'agefodd', 1, 'agefodd_place', 'supprimer', 'r', 1),
(265486, 'Modifier les commercieux d''un tiers', 'societe', 1, 'client', 'comm', 'r', 1),
(2354687, 'Mettre ajour les prix produits par client', 'service', 1, 'custprix', NULL, 'w', 0);

--Set prospect thirdparty with no invoicie
UPDATE llx_societe SET client=6, tms=tms WHERE fournisseur=0;
UPDATE llx_societe SET client=2, tms=tms WHERE rowid NOT IN (select fk_soc from llx_facture) AND fournisseur=0;--Prospect
UPDATE llx_societe SET client=1, tms=tms WHERE rowid IN (select fk_soc from llx_facture) AND fournisseur=0;--Customer
UPDATE llx_societe SET client=3, tms=tms WHERE rowid IN (select fk_soc from llx_facture WHERE fk_soc=llx_societe.rowid HAVING MAX(date_valid) < DATE_ADD(NOW(), INTERVAL -1 YEAR)) AND fournisseur=0;--Prospect/Customer
UPDATE llx_societe SET client=0, tms=tms WHERE client=6 AND fournisseur=0;--All Other

UPDATE llx_societe SET code_fournisseur=NULL, tms=tms WHERE fournisseur=0;
UPDATE llx_societe SET code_client=CONCAT_WS('','C', LPAD(rowid,5,'0')), tms=tms WHERE client IN (1,2,3);

TRUNCATE TABLE llx_c_actioncomm;
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES
(1, 'AC_TEL', 'system', 'Phone call', NULL, 1, NULL, 2),
(2, 'AC_FAX', 'systemauto', 'Send Fax', NULL, 1, NULL, 3),
(3, 'AC_PROP', 'systemauto', 'Send commercial proposal by email', 'propal', 0, NULL, 10),
(4, 'AC_EMAIL', 'system', 'Send Email', NULL, 1, NULL, 4),
(5, 'AC_RDV', 'system', 'Rendez-vous', NULL, 1, NULL, 1),
(8, 'AC_COM', 'systemauto', 'Send customer order by email', 'order', 0, NULL, 8),
(9, 'AC_FAC', 'systemauto', 'Send customer invoice by email', 'invoice', 0, NULL, 6),
(10, 'AC_SHIP', 'systemauto', 'Send shipping by email', 'shipping', 0, NULL, 11),
(30, 'AC_SUP_ORD', 'systemauto', 'Send supplier order by email', 'order_supplier', 0, NULL, 9),
(31, 'AC_SUP_INV', 'systemauto', 'Send supplier invoice by email', 'invoice_supplier', 0, NULL, 7),
(40, 'AC_OTH_AUTO', 'systemauto', 'Other (automatically inserted events)', NULL, 1, NULL, 20),
(50, 'AC_OTH', 'systemauto', 'Other (manually inserted events)', NULL, 1, NULL, 5),
(1030008, 'AC_AGF_CONSE', 'systemauto', 'Send Advise document by mail', 'agefodd', 1, NULL, 80),
(1030007, 'AC_AGF_CLOT', 'systemauto', 'Send dossier cloture by mail', 'agefodd', 1, NULL, 70),
(1030006, 'AC_AGF_ATTES', 'systemauto', 'Send attestation by mail', 'agefodd', 1, NULL, 60),
(1030005, 'AC_AGF_PRES', 'systemauto', 'Send Fiche présence by mail', 'agefodd', 1, NULL, 50),
(1030004, 'AC_AGF_PEDAG', 'systemauto', 'Send Fiche pédagogique by mail', 'agefodd', 1, NULL, 40),
(1030003, 'AC_AGF_CONVO', 'systemauto', 'Send Convocation by mail', 'agefodd', 1, NULL, 30),
(1030002, 'AC_AGF_CONVE', 'systemauto', 'Send Convention by mail', 'agefodd', 1, NULL, 20),
(1030001, 'AC_AGF_SESS', 'systemauto', 'Link to Training', 'agefodd', 1, NULL, 10),
(1030009, 'AC_AGF_ACCUE', 'systemauto', 'Send welcome document by mail', 'agefodd', 1, NULL, 90),
(1030010, 'AC_AGF_SESST', 'systemauto', 'Link to Training for trainer', 'agefodd', 1, NULL, 15),
(1030011, 'AC_COUR', 'user', 'Envoi courrier (doc, catalogue, ...)', NULL, 1, NULL, 5),
(1030013, 'AC_PROJ', 'user', 'Projet', NULL, 1, NULL, 6),
(1030014, 'AC_ENTR', 'user', 'Demande entrante', NULL, 1, NULL, 9),
(1030015, 'AC_EVT', 'user', 'Participation à un évènement Akteos', NULL, 1, NULL, 10);


TRUNCATE TABLE llx_c_typent;
INSERT INTO `llx_c_typent` (`id`, `code`, `libelle`, `active`, `module`) VALUES
(0, 'TE_UNKNOWN', '-', 1, NULL),
(1, 'TE_STARTUP', 'Start-up', 0, NULL),
(2, 'TE_GROUP', 'Entreprise française', 0, NULL),
(3, 'TE_PAY', 'Payeur', 1, NULL),
(4, 'TE_SMALL', 'TPE', 0, NULL),
(5, 'TE_ADMIN', 'Pouvoirs publics', 1, NULL),
(6, 'TE_WHOLE', 'Grossiste', 0, NULL),
(7, 'TE_RETAIL', 'Revendeur', 0, NULL),
(8, 'TE_PRIVATE', 'Particulier', 1, NULL),
(100, 'TE_OTHER', 'Autres', 0, NULL),
(101, 'TE_ENT_FR', 'Entreprise française', 1, NULL),
(102, 'TE_ENT_ET', 'Entreprise étrangère', 1, NULL),
(103, 'TE_OPCA', 'OPCA', 1, NULL),
(104, 'TE_FORMATION', 'Prestataire de formation', 1, NULL);

TRUNCATE TABLE llx_c_effectif;
INSERT INTO `llx_c_effectif` (`id`, `code`, `libelle`, `active`, `module`) VALUES
(0, 'EF0', '-', 1, NULL),
(1, 'EF1-19', '1 - 19', 1, NULL),
(2, 'EF20-99', '20 - 99', 1, NULL),
(3, 'EF100-499', '100 - 499', 1, NULL),
(4, 'EF500-999', '500 - 999', 1, NULL),
(6, 'EF1000-', '> 1000', 1, NULL);


--insert event type Demande entrante
TRUNCATE TABLE llx_actioncomm;
INSERT INTO llx_actioncomm (
ref_ext,
entity,
datep,
datep2,
fk_action,
code,
label,
note,
datec,
tms,
fk_user_author,
fk_user_mod,
fk_soc,
fk_contact,
fk_user_action,
transparency,
fk_user_done,
priority,
fulldayevent,
punctual,
percent,
location,
durationp,
durationa,
fk_element,
elementtype)
SELECT DISTINCT
planningitem.id, --ref_ext
1, --entity,
IFNULL(planningitem.begin_datetime,planningitem.end_datetime), --datep,
planningitem.end_datetime, --datep2,
1030014, --fk_action
'AC_ENTR', --code,
CASE WHEN (TRIM(planningitem.summary)='') THEN 'Demande entrante (renseignements, besoins)' ELSE planningitem.summary END, --label,
planningitem.description, --note,
planningitem.created, --datec,
planningitem.modified, --tms,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(usermod.rowid,1), --fk_user_mod
llx_societe.rowid, --fk_soc,
llx_socpeople.rowid, --fk_contact,
IFNULL(usercrea.rowid,1), --fk_user_action,
0, --transparency,
IFNULL(usercrea.rowid,1), --fk_user_done,
0, --priority,
0, --fulldayevent,
1, --punctual,
-1, --percent,
null, --location
planningitem.end_datetime-planningitem.begin_datetime, --durationp,
null, --durationa,
null, --fk_element
null --elementtype
FROM planningitem
LEFT OUTER JOIN planningitem_uobject as contactsoc ON planningitem.id=contactsoc.planningitem_id
LEFT OUTER JOIN planningitem_uobject as contctlink ON contctlink.planningitem_uobject_id=contactsoc.id
LEFT OUTER JOIN llx_socpeople ON llx_socpeople.import_key=contctlink.uobject_id
LEFT OUTER JOIN llx_societe ON llx_societe.import_key=contactsoc.uobject_id
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=planningitem.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=planningitem.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
WHERE planningitem.planningitemtype_code='BES'
AND llx_societe.rowid IS NOT NULL AND planningitem.deleted=0;

--insert event type Demande entrante
INSERT INTO llx_actioncomm (
ref_ext,
entity,
datep,
datep2,
fk_action,
code,
label,
note,
datec,
tms,
fk_user_author,
fk_user_mod,
fk_soc,
fk_contact,
fk_user_action,
transparency,
fk_user_done,
priority,
fulldayevent,
punctual,
percent,
location,
durationp,
durationa,
fk_element,
elementtype)
SELECT DISTINCT
planningitem.id, --ref_ext
1, --entity,
IFNULL(planningitem.begin_datetime,planningitem.end_datetime), --datep,
planningitem.end_datetime, --datep2,
1030011, --fk_action
'AC_COUR', --code,
CASE WHEN (TRIM(planningitem.summary)='') THEN 'Envoi courrier (doc, catalogue, ...)' ELSE planningitem.summary END, --label,
planningitem.description, --note,
planningitem.created, --datec,
planningitem.modified, --tms,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(usermod.rowid,1), --fk_user_mod
llx_societe.rowid, --fk_soc,
llx_socpeople.rowid, --fk_contact,
IFNULL(usercrea.rowid,1), --fk_user_action,
0, --transparency,
IFNULL(usercrea.rowid,1), --fk_user_done,
0, --priority,
0, --fulldayevent,
1, --punctual,
-1, --percent,
null, --location
planningitem.end_datetime-planningitem.begin_datetime, --durationp,
null, --durationa,
null, --fk_element
null --elementtype
FROM planningitem
LEFT OUTER JOIN planningitem_uobject as contactsoc ON planningitem.id=contactsoc.planningitem_id
LEFT OUTER JOIN planningitem_uobject as contctlink ON contctlink.planningitem_uobject_id=contactsoc.id
LEFT OUTER JOIN llx_socpeople ON llx_socpeople.import_key=contctlink.uobject_id
LEFT OUTER JOIN llx_societe ON llx_societe.import_key=contactsoc.uobject_id
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=planningitem.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=planningitem.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
WHERE planningitem.planningitemtype_code='DOC'
AND llx_societe.rowid IS NOT NULL
AND planningitem.deleted=0;

--Insert Projet
INSERT INTO llx_actioncomm (
ref_ext,
entity,
datep,
datep2,
fk_action,
code,
label,
note,
datec,
tms,
fk_user_author,
fk_user_mod,
fk_soc,
fk_contact,
fk_user_action,
transparency,
fk_user_done,
priority,
fulldayevent,
punctual,
percent,
location,
durationp,
durationa,
fk_element,
elementtype)
SELECT DISTINCT
planningitem.id, --ref_ext
1, --entity,
IFNULL(planningitem.begin_datetime,planningitem.end_datetime), --datep,
planningitem.end_datetime, --datep2,
1030013, --fk_action
'AC_PROJ', --code,
CASE WHEN (TRIM(planningitem.summary)='') THEN 'Envoi courrier (doc, catalogue, ...)' ELSE LEFT(planningitem.summary,128) END, --label,
planningitem.description, --note,
planningitem.created, --datec,
planningitem.modified, --tms,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(usermod.rowid,1), --fk_user_mod
llx_societe.rowid, --fk_soc,
llx_socpeople.rowid, --fk_contact,
IFNULL(usercrea.rowid,1), --fk_user_action,
0, --transparency,
IFNULL(usercrea.rowid,1), --fk_user_done,
0, --priority,
0, --fulldayevent,
1, --punctual,
-1, --percent,
null, --location
planningitem.end_datetime-planningitem.begin_datetime, --durationp,
null, --durationa,
null, --fk_element
null --elementtype
FROM planningitem
LEFT OUTER JOIN planningitem_uobject as contactsoc ON planningitem.id=contactsoc.planningitem_id
LEFT OUTER JOIN planningitem_uobject as contctlink ON contctlink.planningitem_uobject_id=contactsoc.id
LEFT OUTER JOIN llx_socpeople ON llx_socpeople.import_key=contctlink.uobject_id
LEFT OUTER JOIN llx_societe ON llx_societe.import_key=contactsoc.uobject_id
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=planningitem.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=planningitem.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
WHERE planningitem.planningitemtype_code='PROJET'
AND llx_societe.rowid IS NOT NULL
AND planningitem.deleted=0;


INSERT INTO llx_actioncomm (
ref_ext,
entity,
datep,
datep2,
fk_action,
code,
label,
note,
datec,
tms,
fk_user_author,
fk_user_mod,
fk_soc,
fk_contact,
fk_user_action,
transparency,
fk_user_done,
priority,
fulldayevent,
punctual,
percent,
location,
durationp,
durationa,
fk_element,
elementtype)
SELECT DISTINCT
planningitem.id, --ref_ext
1, --entity,
IFNULL(planningitem.begin_datetime,planningitem.end_datetime), --datep,
planningitem.end_datetime, --datep2,
1030013, --fk_action
'AC_PROJ', --code,
CASE WHEN (TRIM(planningitem.summary)='') THEN 'Envoi courrier (doc, catalogue, ...)' ELSE LEFT(planningitem.summary,128) END, --label,
planningitem.description, --note,
planningitem.created, --datec,
planningitem.modified, --tms,
IFNULL(usercrea.rowid,1), --fk_user_author
IFNULL(usermod.rowid,1), --fk_user_mod
llx_societe.rowid, --fk_soc,
llx_socpeople.rowid, --fk_contact,
IFNULL(usercrea.rowid,1), --fk_user_action,
0, --transparency,
IFNULL(usercrea.rowid,1), --fk_user_done,
0, --priority,
0, --fulldayevent,
1, --punctual,
-1, --percent,
null, --location
planningitem.end_datetime-planningitem.begin_datetime, --durationp,
null, --durationa,
null, --fk_element
null --elementtype
FROM planningitem
LEFT OUTER JOIN planningitem_uobject as contactsoc ON planningitem.id=contactsoc.planningitem_id
LEFT OUTER JOIN planningitem_uobject as contctlink ON contctlink.planningitem_uobject_id=contactsoc.id
LEFT OUTER JOIN llx_socpeople ON llx_socpeople.import_key=contctlink.uobject_id
LEFT OUTER JOIN llx_societe ON llx_societe.import_key=contactsoc.uobject_id
LEFT OUTER JOIN sf_user as usercreast ON usercreast.id=planningitem.created_by_sf_user_id
LEFT OUTER JOIN llx_user as usercrea ON usercreast.email_address=usercrea.email
LEFT OUTER JOIN sf_user as usermodst ON usermodst.id=planningitem.modified_by_sf_user_id
LEFT OUTER JOIN llx_user as usermod ON usermodst.email_address=usermod.email
WHERE planningitem.planningitemtype_code='PROJET'
AND llx_societe.rowid IS NOT NULL
AND planningitem.deleted=0;
------
---import Agenda
------
--select count(*) from hisplan where session_id IS NOT NULL
-- Match sur session id
--Mettre dans text lib tel quel, formateur : nom, éléves : nomelv, comment :comment
--date debut : hjour+heuredeb / date fin hjour + heurefin 
--Différent de haction='Old'
--Type=Session AGF

--Hist mail
--deleted IS NULL
--Affecter à com_id
--contact_id et account_id
--type='Autre'
--dater=date de fin

--Remove temporarie data
ALTER TABLE llx_societe DROP INDEX idx_llx_societe_import_key;
ALTER TABLE llx_agefodd_session DROP INDEX idx_llx_agefodd_session_import_key;
ALTER TABLE llx_propal DROP INDEX idx_llx_propal_import_key;
ALTER TABLE llx_user DROP INDEX idx_llx_user_import_key;
ALTER TABLE llx_socpeople DROP INDEX idx_llx_socpeople_import_key;
SET foreign_key_checks = 1;
