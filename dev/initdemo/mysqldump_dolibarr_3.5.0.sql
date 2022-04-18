-- MySQL dump 10.13  Distrib 5.5.35, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: dolibarr35
-- ------------------------------------------------------
-- Server version	5.5.35-0ubuntu0.12.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `llx_accounting_system`
--

DROP TABLE IF EXISTS `llx_accounting_system`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_accounting_system` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `pcg_version` varchar(12) NOT NULL,
  `fk_pays` int(11) NOT NULL,
  `label` varchar(128) NOT NULL,
  `active` smallint(6) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_accounting_system_pcg_version` (`pcg_version`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_accounting_system`
--

LOCK TABLES `llx_accounting_system` WRITE;
/*!40000 ALTER TABLE `llx_accounting_system` DISABLE KEYS */;
INSERT INTO `llx_accounting_system` VALUES (1,'PCG99-ABREGE',1,'The simple accountancy french plan',1),(2,'PCG99-BASE',1,'The base accountancy french plan',1);
/*!40000 ALTER TABLE `llx_accounting_system` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_accountingaccount`
--

DROP TABLE IF EXISTS `llx_accountingaccount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_accountingaccount` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_pcg_version` varchar(12) NOT NULL,
  `pcg_type` varchar(20) NOT NULL,
  `pcg_subtype` varchar(20) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `account_parent` varchar(20) DEFAULT NULL,
  `label` varchar(128) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  KEY `idx_accountingaccount_fk_pcg_version` (`fk_pcg_version`),
  CONSTRAINT `fk_accountingaccount_fk_pcg_version` FOREIGN KEY (`fk_pcg_version`) REFERENCES `llx_accounting_system` (`pcg_version`)
) ENGINE=InnoDB AUTO_INCREMENT=439 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_accountingaccount`
--

LOCK TABLES `llx_accountingaccount` WRITE;
/*!40000 ALTER TABLE `llx_accountingaccount` DISABLE KEYS */;
INSERT INTO `llx_accountingaccount` VALUES (1,'PCG99-ABREGE','CAPIT','CAPITAL','101','1','Capital',1),(2,'PCG99-ABREGE','CAPIT','XXXXXX','105','1','Ecarts de réévaluation',1),(3,'PCG99-ABREGE','CAPIT','XXXXXX','1061','1','Réserve légale',1),(4,'PCG99-ABREGE','CAPIT','XXXXXX','1063','1','Réserves statutaires ou contractuelles',1),(5,'PCG99-ABREGE','CAPIT','XXXXXX','1064','1','Réserves réglementées',1),(6,'PCG99-ABREGE','CAPIT','XXXXXX','1068','1','Autres réserves',1),(7,'PCG99-ABREGE','CAPIT','XXXXXX','108','1','Compte de l\'exploitant',1),(8,'PCG99-ABREGE','CAPIT','XXXXXX','12','1','Résultat de l\'exercice',1),(9,'PCG99-ABREGE','CAPIT','XXXXXX','145','1','Amortissements dérogatoires',1),(10,'PCG99-ABREGE','CAPIT','XXXXXX','146','1','Provision spéciale de réévaluation',1),(11,'PCG99-ABREGE','CAPIT','XXXXXX','147','1','Plus-values réinvesties',1),(12,'PCG99-ABREGE','CAPIT','XXXXXX','148','1','Autres provisions réglementées',1),(13,'PCG99-ABREGE','CAPIT','XXXXXX','15','1','Provisions pour risques et charges',1),(14,'PCG99-ABREGE','CAPIT','XXXXXX','16','1','Emprunts et dettes assimilees',1),(15,'PCG99-ABREGE','IMMO','XXXXXX','20','2','Immobilisations incorporelles',1),(16,'PCG99-ABREGE','IMMO','XXXXXX','201','20','Frais d\'établissement',1),(17,'PCG99-ABREGE','IMMO','XXXXXX','206','20','Droit au bail',1),(18,'PCG99-ABREGE','IMMO','XXXXXX','207','20','Fonds commercial',1),(19,'PCG99-ABREGE','IMMO','XXXXXX','208','20','Autres immobilisations incorporelles',1),(20,'PCG99-ABREGE','IMMO','XXXXXX','21','2','Immobilisations corporelles',1),(21,'PCG99-ABREGE','IMMO','XXXXXX','23','2','Immobilisations en cours',1),(22,'PCG99-ABREGE','IMMO','XXXXXX','27','2','Autres immobilisations financieres',1),(23,'PCG99-ABREGE','IMMO','XXXXXX','280','2','Amortissements des immobilisations incorporelles',1),(24,'PCG99-ABREGE','IMMO','XXXXXX','281','2','Amortissements des immobilisations corporelles',1),(25,'PCG99-ABREGE','IMMO','XXXXXX','290','2','Provisions pour dépréciation des immobilisations incorporelles',1),(26,'PCG99-ABREGE','IMMO','XXXXXX','291','2','Provisions pour dépréciation des immobilisations corporelles',1),(27,'PCG99-ABREGE','IMMO','XXXXXX','297','2','Provisions pour dépréciation des autres immobilisations financières',1),(28,'PCG99-ABREGE','STOCK','XXXXXX','31','3','Matieres premières',1),(29,'PCG99-ABREGE','STOCK','XXXXXX','32','3','Autres approvisionnements',1),(30,'PCG99-ABREGE','STOCK','XXXXXX','33','3','En-cours de production de biens',1),(31,'PCG99-ABREGE','STOCK','XXXXXX','34','3','En-cours de production de services',1),(32,'PCG99-ABREGE','STOCK','XXXXXX','35','3','Stocks de produits',1),(33,'PCG99-ABREGE','STOCK','XXXXXX','37','3','Stocks de marchandises',1),(34,'PCG99-ABREGE','STOCK','XXXXXX','391','3','Provisions pour dépréciation des matières premières',1),(35,'PCG99-ABREGE','STOCK','XXXXXX','392','3','Provisions pour dépréciation des autres approvisionnements',1),(36,'PCG99-ABREGE','STOCK','XXXXXX','393','3','Provisions pour dépréciation des en-cours de production de biens',1),(37,'PCG99-ABREGE','STOCK','XXXXXX','394','3','Provisions pour dépréciation des en-cours de production de services',1),(38,'PCG99-ABREGE','STOCK','XXXXXX','395','3','Provisions pour dépréciation des stocks de produits',1),(39,'PCG99-ABREGE','STOCK','XXXXXX','397','3','Provisions pour dépréciation des stocks de marchandises',1),(40,'PCG99-ABREGE','TIERS','SUPPLIER','400','4','Fournisseurs et Comptes rattachés',1),(41,'PCG99-ABREGE','TIERS','XXXXXX','409','4','Fournisseurs débiteurs',1),(42,'PCG99-ABREGE','TIERS','CUSTOMER','410','4','Clients et Comptes rattachés',1),(43,'PCG99-ABREGE','TIERS','XXXXXX','419','4','Clients créditeurs',1),(44,'PCG99-ABREGE','TIERS','XXXXXX','421','4','Personnel',1),(45,'PCG99-ABREGE','TIERS','XXXXXX','428','4','Personnel',1),(46,'PCG99-ABREGE','TIERS','XXXXXX','43','4','Sécurité sociale et autres organismes sociaux',1),(47,'PCG99-ABREGE','TIERS','XXXXXX','444','4','Etat - impôts sur bénéfice',1),(48,'PCG99-ABREGE','TIERS','XXXXXX','445','4','Etat - Taxes sur chiffre affaires',1),(49,'PCG99-ABREGE','TIERS','XXXXXX','447','4','Autres impôts, taxes et versements assimilés',1),(50,'PCG99-ABREGE','TIERS','XXXXXX','45','4','Groupe et associes',1),(51,'PCG99-ABREGE','TIERS','XXXXXX','455','45','Associés',1),(52,'PCG99-ABREGE','TIERS','XXXXXX','46','4','Débiteurs divers et créditeurs divers',1),(53,'PCG99-ABREGE','TIERS','XXXXXX','47','4','Comptes transitoires ou d\'attente',1),(54,'PCG99-ABREGE','TIERS','XXXXXX','481','4','Charges à répartir sur plusieurs exercices',1),(55,'PCG99-ABREGE','TIERS','XXXXXX','486','4','Charges constatées d\'avance',1),(56,'PCG99-ABREGE','TIERS','XXXXXX','487','4','Produits constatés d\'avance',1),(57,'PCG99-ABREGE','TIERS','XXXXXX','491','4','Provisions pour dépréciation des comptes de clients',1),(58,'PCG99-ABREGE','TIERS','XXXXXX','496','4','Provisions pour dépréciation des comptes de débiteurs divers',1),(59,'PCG99-ABREGE','FINAN','XXXXXX','50','5','Valeurs mobilières de placement',1),(60,'PCG99-ABREGE','FINAN','BANK','51','5','Banques, établissements financiers et assimilés',1),(61,'PCG99-ABREGE','FINAN','CASH','53','5','Caisse',1),(62,'PCG99-ABREGE','FINAN','XXXXXX','54','5','Régies d\'avance et accréditifs',1),(63,'PCG99-ABREGE','FINAN','XXXXXX','58','5','Virements internes',1),(64,'PCG99-ABREGE','FINAN','XXXXXX','590','5','Provisions pour dépréciation des valeurs mobilières de placement',1),(65,'PCG99-ABREGE','CHARGE','PRODUCT','60','6','Achats',1),(66,'PCG99-ABREGE','CHARGE','XXXXXX','603','60','Variations des stocks',1),(67,'PCG99-ABREGE','CHARGE','SERVICE','61','6','Services extérieurs',1),(68,'PCG99-ABREGE','CHARGE','XXXXXX','62','6','Autres services extérieurs',1),(69,'PCG99-ABREGE','CHARGE','XXXXXX','63','6','Impôts, taxes et versements assimiles',1),(70,'PCG99-ABREGE','CHARGE','XXXXXX','641','6','Rémunérations du personnel',1),(71,'PCG99-ABREGE','CHARGE','XXXXXX','644','6','Rémunération du travail de l\'exploitant',1),(72,'PCG99-ABREGE','CHARGE','SOCIAL','645','6','Charges de sécurité sociale et de prévoyance',1),(73,'PCG99-ABREGE','CHARGE','XXXXXX','646','6','Cotisations sociales personnelles de l\'exploitant',1),(74,'PCG99-ABREGE','CHARGE','XXXXXX','65','6','Autres charges de gestion courante',1),(75,'PCG99-ABREGE','CHARGE','XXXXXX','66','6','Charges financières',1),(76,'PCG99-ABREGE','CHARGE','XXXXXX','67','6','Charges exceptionnelles',1),(77,'PCG99-ABREGE','CHARGE','XXXXXX','681','6','Dotations aux amortissements et aux provisions',1),(78,'PCG99-ABREGE','CHARGE','XXXXXX','686','6','Dotations aux amortissements et aux provisions',1),(79,'PCG99-ABREGE','CHARGE','XXXXXX','687','6','Dotations aux amortissements et aux provisions',1),(80,'PCG99-ABREGE','CHARGE','XXXXXX','691','6','Participation des salariés aux résultats',1),(81,'PCG99-ABREGE','CHARGE','XXXXXX','695','6','Impôts sur les bénéfices',1),(82,'PCG99-ABREGE','CHARGE','XXXXXX','697','6','Imposition forfaitaire annuelle des sociétés',1),(83,'PCG99-ABREGE','CHARGE','XXXXXX','699','6','Produits',1),(84,'PCG99-ABREGE','PROD','PRODUCT','701','7','Ventes de produits finis',1),(85,'PCG99-ABREGE','PROD','SERVICE','706','7','Prestations de services',1),(86,'PCG99-ABREGE','PROD','PRODUCT','707','7','Ventes de marchandises',1),(87,'PCG99-ABREGE','PROD','PRODUCT','708','7','Produits des activités annexes',1),(88,'PCG99-ABREGE','PROD','XXXXXX','709','7','Rabais, remises et ristournes accordés par l\'entreprise',1),(89,'PCG99-ABREGE','PROD','XXXXXX','713','7','Variation des stocks',1),(90,'PCG99-ABREGE','PROD','XXXXXX','72','7','Production immobilisée',1),(91,'PCG99-ABREGE','PROD','XXXXXX','73','7','Produits nets partiels sur opérations à long terme',1),(92,'PCG99-ABREGE','PROD','XXXXXX','74','7','Subventions d\'exploitation',1),(93,'PCG99-ABREGE','PROD','XXXXXX','75','7','Autres produits de gestion courante',1),(94,'PCG99-ABREGE','PROD','XXXXXX','753','75','Jetons de présence et rémunérations d\'administrateurs, gérants,...',1),(95,'PCG99-ABREGE','PROD','XXXXXX','754','75','Ristournes perçues des coopératives',1),(96,'PCG99-ABREGE','PROD','XXXXXX','755','75','Quotes-parts de résultat sur opérations faites en commun',1),(97,'PCG99-ABREGE','PROD','XXXXXX','76','7','Produits financiers',1),(98,'PCG99-ABREGE','PROD','XXXXXX','77','7','Produits exceptionnels',1),(99,'PCG99-ABREGE','PROD','XXXXXX','781','7','Reprises sur amortissements et provisions',1),(100,'PCG99-ABREGE','PROD','XXXXXX','786','7','Reprises sur provisions pour risques',1),(101,'PCG99-ABREGE','PROD','XXXXXX','787','7','Reprises sur provisions',1),(102,'PCG99-ABREGE','PROD','XXXXXX','79','7','Transferts de charges',1),(103,'PCG99-BASE','CAPIT','XXXXXX','10','1','Capital  et réserves',1),(104,'PCG99-BASE','CAPIT','CAPITAL','101','10','Capital',1),(105,'PCG99-BASE','CAPIT','XXXXXX','104','10','Primes liées au capital social',1),(106,'PCG99-BASE','CAPIT','XXXXXX','105','10','Ecarts de réévaluation',1),(107,'PCG99-BASE','CAPIT','XXXXXX','106','10','Réserves',1),(108,'PCG99-BASE','CAPIT','XXXXXX','107','10','Ecart d\'equivalence',1),(109,'PCG99-BASE','CAPIT','XXXXXX','108','10','Compte de l\'exploitant',1),(110,'PCG99-BASE','CAPIT','XXXXXX','109','10','Actionnaires : capital souscrit - non appelé',1),(111,'PCG99-BASE','CAPIT','XXXXXX','11','1','Report à nouveau (solde créditeur ou débiteur)',1),(112,'PCG99-BASE','CAPIT','XXXXXX','110','11','Report à nouveau (solde créditeur)',1),(113,'PCG99-BASE','CAPIT','XXXXXX','119','11','Report à nouveau (solde débiteur)',1),(114,'PCG99-BASE','CAPIT','XXXXXX','12','1','Résultat de l\'exercice (bénéfice ou perte)',1),(115,'PCG99-BASE','CAPIT','XXXXXX','120','12','Résultat de l\'exercice (bénéfice)',1),(116,'PCG99-BASE','CAPIT','XXXXXX','129','12','Résultat de l\'exercice (perte)',1),(117,'PCG99-BASE','CAPIT','XXXXXX','13','1','Subventions d\'investissement',1),(118,'PCG99-BASE','CAPIT','XXXXXX','131','13','Subventions d\'équipement',1),(119,'PCG99-BASE','CAPIT','XXXXXX','138','13','Autres subventions d\'investissement',1),(120,'PCG99-BASE','CAPIT','XXXXXX','139','13','Subventions d\'investissement inscrites au compte de résultat',1),(121,'PCG99-BASE','CAPIT','XXXXXX','14','1','Provisions réglementées',1),(122,'PCG99-BASE','CAPIT','XXXXXX','142','14','Provisions réglementées relatives aux immobilisations',1),(123,'PCG99-BASE','CAPIT','XXXXXX','143','14','Provisions réglementées relatives aux stocks',1),(124,'PCG99-BASE','CAPIT','XXXXXX','144','14','Provisions réglementées relatives aux autres éléments de l\'actif',1),(125,'PCG99-BASE','CAPIT','XXXXXX','145','14','Amortissements dérogatoires',1),(126,'PCG99-BASE','CAPIT','XXXXXX','146','14','Provision spéciale de réévaluation',1),(127,'PCG99-BASE','CAPIT','XXXXXX','147','14','Plus-values réinvesties',1),(128,'PCG99-BASE','CAPIT','XXXXXX','148','14','Autres provisions réglementées',1),(129,'PCG99-BASE','CAPIT','XXXXXX','15','1','Provisions pour risques et charges',1),(130,'PCG99-BASE','CAPIT','XXXXXX','151','15','Provisions pour risques',1),(131,'PCG99-BASE','CAPIT','XXXXXX','153','15','Provisions pour pensions et obligations similaires',1),(132,'PCG99-BASE','CAPIT','XXXXXX','154','15','Provisions pour restructurations',1),(133,'PCG99-BASE','CAPIT','XXXXXX','155','15','Provisions pour impôts',1),(134,'PCG99-BASE','CAPIT','XXXXXX','156','15','Provisions pour renouvellement des immobilisations (entreprises concessionnaires)',1),(135,'PCG99-BASE','CAPIT','XXXXXX','157','15','Provisions pour charges à répartir sur plusieurs exercices',1),(136,'PCG99-BASE','CAPIT','XXXXXX','158','15','Autres provisions pour charges',1),(137,'PCG99-BASE','CAPIT','XXXXXX','16','1','Emprunts et dettes assimilees',1),(138,'PCG99-BASE','CAPIT','XXXXXX','161','16','Emprunts obligataires convertibles',1),(139,'PCG99-BASE','CAPIT','XXXXXX','163','16','Autres emprunts obligataires',1),(140,'PCG99-BASE','CAPIT','XXXXXX','164','16','Emprunts auprès des établissements de crédit',1),(141,'PCG99-BASE','CAPIT','XXXXXX','165','16','Dépôts et cautionnements reçus',1),(142,'PCG99-BASE','CAPIT','XXXXXX','166','16','Participation des salariés aux résultats',1),(143,'PCG99-BASE','CAPIT','XXXXXX','167','16','Emprunts et dettes assortis de conditions particulières',1),(144,'PCG99-BASE','CAPIT','XXXXXX','168','16','Autres emprunts et dettes assimilées',1),(145,'PCG99-BASE','CAPIT','XXXXXX','169','16','Primes de remboursement des obligations',1),(146,'PCG99-BASE','CAPIT','XXXXXX','17','1','Dettes rattachées à des participations',1),(147,'PCG99-BASE','CAPIT','XXXXXX','171','17','Dettes rattachées à des participations (groupe)',1),(148,'PCG99-BASE','CAPIT','XXXXXX','174','17','Dettes rattachées à des participations (hors groupe)',1),(149,'PCG99-BASE','CAPIT','XXXXXX','178','17','Dettes rattachées à des sociétés en participation',1),(150,'PCG99-BASE','CAPIT','XXXXXX','18','1','Comptes de liaison des établissements et sociétés en participation',1),(151,'PCG99-BASE','CAPIT','XXXXXX','181','18','Comptes de liaison des établissements',1),(152,'PCG99-BASE','CAPIT','XXXXXX','186','18','Biens et prestations de services échangés entre établissements (charges)',1),(153,'PCG99-BASE','CAPIT','XXXXXX','187','18','Biens et prestations de services échangés entre établissements (produits)',1),(154,'PCG99-BASE','CAPIT','XXXXXX','188','18','Comptes de liaison des sociétés en participation',1),(155,'PCG99-BASE','IMMO','XXXXXX','20','2','Immobilisations incorporelles',1),(156,'PCG99-BASE','IMMO','XXXXXX','201','20','Frais d\'établissement',1),(157,'PCG99-BASE','IMMO','XXXXXX','203','20','Frais de recherche et de développement',1),(158,'PCG99-BASE','IMMO','XXXXXX','205','20','Concessions et droits similaires, brevets, licences, marques, procédés, logiciels, droits et valeurs similaires',1),(159,'PCG99-BASE','IMMO','XXXXXX','206','20','Droit au bail',1),(160,'PCG99-BASE','IMMO','XXXXXX','207','20','Fonds commercial',1),(161,'PCG99-BASE','IMMO','XXXXXX','208','20','Autres immobilisations incorporelles',1),(162,'PCG99-BASE','IMMO','XXXXXX','21','2','Immobilisations corporelles',1),(163,'PCG99-BASE','IMMO','XXXXXX','211','21','Terrains',1),(164,'PCG99-BASE','IMMO','XXXXXX','212','21','Agencements et aménagements de terrains',1),(165,'PCG99-BASE','IMMO','XXXXXX','213','21','Constructions',1),(166,'PCG99-BASE','IMMO','XXXXXX','214','21','Constructions sur sol d\'autrui',1),(167,'PCG99-BASE','IMMO','XXXXXX','215','21','Installations techniques, matériels et outillage industriels',1),(168,'PCG99-BASE','IMMO','XXXXXX','218','21','Autres immobilisations corporelles',1),(169,'PCG99-BASE','IMMO','XXXXXX','22','2','Immobilisations mises en concession',1),(170,'PCG99-BASE','IMMO','XXXXXX','23','2','Immobilisations en cours',1),(171,'PCG99-BASE','IMMO','XXXXXX','231','23','Immobilisations corporelles en cours',1),(172,'PCG99-BASE','IMMO','XXXXXX','232','23','Immobilisations incorporelles en cours',1),(173,'PCG99-BASE','IMMO','XXXXXX','237','23','Avances et acomptes versés sur immobilisations incorporelles',1),(174,'PCG99-BASE','IMMO','XXXXXX','238','23','Avances et acomptes versés sur commandes d\'immobilisations corporelles',1),(175,'PCG99-BASE','IMMO','XXXXXX','25','2','Parts dans des entreprises liées et créances sur des entreprises liées',1),(176,'PCG99-BASE','IMMO','XXXXXX','26','2','Participations et créances rattachées à des participations',1),(177,'PCG99-BASE','IMMO','XXXXXX','261','26','Titres de participation',1),(178,'PCG99-BASE','IMMO','XXXXXX','266','26','Autres formes de participation',1),(179,'PCG99-BASE','IMMO','XXXXXX','267','26','Créances rattachées à des participations',1),(180,'PCG99-BASE','IMMO','XXXXXX','268','26','Créances rattachées à des sociétés en participation',1),(181,'PCG99-BASE','IMMO','XXXXXX','269','26','Versements restant à effectuer sur titres de participation non libérés',1),(182,'PCG99-BASE','IMMO','XXXXXX','27','2','Autres immobilisations financieres',1),(183,'PCG99-BASE','IMMO','XXXXXX','271','27','Titres immobilisés autres que les titres immobilisés de l\'activité de portefeuille (droit de propriété)',1),(184,'PCG99-BASE','IMMO','XXXXXX','272','27','Titres immobilisés (droit de créance)',1),(185,'PCG99-BASE','IMMO','XXXXXX','273','27','Titres immobilisés de l\'activité de portefeuille',1),(186,'PCG99-BASE','IMMO','XXXXXX','274','27','Prêts',1),(187,'PCG99-BASE','IMMO','XXXXXX','275','27','Dépôts et cautionnements versés',1),(188,'PCG99-BASE','IMMO','XXXXXX','276','27','Autres créances immobilisées',1),(189,'PCG99-BASE','IMMO','XXXXXX','277','27','(Actions propres ou parts propres)',1),(190,'PCG99-BASE','IMMO','XXXXXX','279','27','Versements restant à effectuer sur titres immobilisés non libérés',1),(191,'PCG99-BASE','IMMO','XXXXXX','28','2','Amortissements des immobilisations',1),(192,'PCG99-BASE','IMMO','XXXXXX','280','28','Amortissements des immobilisations incorporelles',1),(193,'PCG99-BASE','IMMO','XXXXXX','281','28','Amortissements des immobilisations corporelles',1),(194,'PCG99-BASE','IMMO','XXXXXX','282','28','Amortissements des immobilisations mises en concession',1),(195,'PCG99-BASE','IMMO','XXXXXX','29','2','Dépréciations des immobilisations',1),(196,'PCG99-BASE','IMMO','XXXXXX','290','29','Dépréciations des immobilisations incorporelles',1),(197,'PCG99-BASE','IMMO','XXXXXX','291','29','Dépréciations des immobilisations corporelles',1),(198,'PCG99-BASE','IMMO','XXXXXX','292','29','Dépréciations des immobilisations mises en concession',1),(199,'PCG99-BASE','IMMO','XXXXXX','293','29','Dépréciations des immobilisations en cours',1),(200,'PCG99-BASE','IMMO','XXXXXX','296','29','Provisions pour dépréciation des participations et créances rattachées à des participations',1),(201,'PCG99-BASE','IMMO','XXXXXX','297','29','Provisions pour dépréciation des autres immobilisations financières',1),(202,'PCG99-BASE','STOCK','XXXXXX','31','3','Matières premières (et fournitures)',1),(203,'PCG99-BASE','STOCK','XXXXXX','311','31','Matières (ou groupe) A',1),(204,'PCG99-BASE','STOCK','XXXXXX','312','31','Matières (ou groupe) B',1),(205,'PCG99-BASE','STOCK','XXXXXX','317','31','Fournitures A, B, C,',1),(206,'PCG99-BASE','STOCK','XXXXXX','32','3','Autres approvisionnements',1),(207,'PCG99-BASE','STOCK','XXXXXX','321','32','Matières consommables',1),(208,'PCG99-BASE','STOCK','XXXXXX','322','32','Fournitures consommables',1),(209,'PCG99-BASE','STOCK','XXXXXX','326','32','Emballages',1),(210,'PCG99-BASE','STOCK','XXXXXX','33','3','En-cours de production de biens',1),(211,'PCG99-BASE','STOCK','XXXXXX','331','33','Produits en cours',1),(212,'PCG99-BASE','STOCK','XXXXXX','335','33','Travaux en cours',1),(213,'PCG99-BASE','STOCK','XXXXXX','34','3','En-cours de production de services',1),(214,'PCG99-BASE','STOCK','XXXXXX','341','34','Etudes en cours',1),(215,'PCG99-BASE','STOCK','XXXXXX','345','34','Prestations de services en cours',1),(216,'PCG99-BASE','STOCK','XXXXXX','35','3','Stocks de produits',1),(217,'PCG99-BASE','STOCK','XXXXXX','351','35','Produits intermédiaires',1),(218,'PCG99-BASE','STOCK','XXXXXX','355','35','Produits finis',1),(219,'PCG99-BASE','STOCK','XXXXXX','358','35','Produits résiduels (ou matières de récupération)',1),(220,'PCG99-BASE','STOCK','XXXXXX','37','3','Stocks de marchandises',1),(221,'PCG99-BASE','STOCK','XXXXXX','371','37','Marchandises (ou groupe) A',1),(222,'PCG99-BASE','STOCK','XXXXXX','372','37','Marchandises (ou groupe) B',1),(223,'PCG99-BASE','STOCK','XXXXXX','39','3','Provisions pour dépréciation des stocks et en-cours',1),(224,'PCG99-BASE','STOCK','XXXXXX','391','39','Provisions pour dépréciation des matières premières',1),(225,'PCG99-BASE','STOCK','XXXXXX','392','39','Provisions pour dépréciation des autres approvisionnements',1),(226,'PCG99-BASE','STOCK','XXXXXX','393','39','Provisions pour dépréciation des en-cours de production de biens',1),(227,'PCG99-BASE','STOCK','XXXXXX','394','39','Provisions pour dépréciation des en-cours de production de services',1),(228,'PCG99-BASE','STOCK','XXXXXX','395','39','Provisions pour dépréciation des stocks de produits',1),(229,'PCG99-BASE','STOCK','XXXXXX','397','39','Provisions pour dépréciation des stocks de marchandises',1),(230,'PCG99-BASE','TIERS','XXXXXX','40','4','Fournisseurs et Comptes rattachés',1),(231,'PCG99-BASE','TIERS','XXXXXX','400','40','Fournisseurs et Comptes rattachés',1),(232,'PCG99-BASE','TIERS','SUPPLIER','401','40','Fournisseurs',1),(233,'PCG99-BASE','TIERS','XXXXXX','403','40','Fournisseurs - Effets à payer',1),(234,'PCG99-BASE','TIERS','XXXXXX','404','40','Fournisseurs d\'immobilisations',1),(235,'PCG99-BASE','TIERS','XXXXXX','405','40','Fournisseurs d\'immobilisations - Effets à payer',1),(236,'PCG99-BASE','TIERS','XXXXXX','408','40','Fournisseurs - Factures non parvenues',1),(237,'PCG99-BASE','TIERS','XXXXXX','409','40','Fournisseurs débiteurs',1),(238,'PCG99-BASE','TIERS','XXXXXX','41','4','Clients et comptes rattachés',1),(239,'PCG99-BASE','TIERS','XXXXXX','410','41','Clients et Comptes rattachés',1),(240,'PCG99-BASE','TIERS','CUSTOMER','411','41','Clients',1),(241,'PCG99-BASE','TIERS','XXXXXX','413','41','Clients - Effets à recevoir',1),(242,'PCG99-BASE','TIERS','XXXXXX','416','41','Clients douteux ou litigieux',1),(243,'PCG99-BASE','TIERS','XXXXXX','418','41','Clients - Produits non encore facturés',1),(244,'PCG99-BASE','TIERS','XXXXXX','419','41','Clients créditeurs',1),(245,'PCG99-BASE','TIERS','XXXXXX','42','4','Personnel et comptes rattachés',1),(246,'PCG99-BASE','TIERS','XXXXXX','421','42','Personnel - Rémunérations dues',1),(247,'PCG99-BASE','TIERS','XXXXXX','422','42','Comités d\'entreprises, d\'établissement, ...',1),(248,'PCG99-BASE','TIERS','XXXXXX','424','42','Participation des salariés aux résultats',1),(249,'PCG99-BASE','TIERS','XXXXXX','425','42','Personnel - Avances et acomptes',1),(250,'PCG99-BASE','TIERS','XXXXXX','426','42','Personnel - Dépôts',1),(251,'PCG99-BASE','TIERS','XXXXXX','427','42','Personnel - Oppositions',1),(252,'PCG99-BASE','TIERS','XXXXXX','428','42','Personnel - Charges à payer et produits à recevoir',1),(253,'PCG99-BASE','TIERS','XXXXXX','43','4','Sécurité sociale et autres organismes sociaux',1),(254,'PCG99-BASE','TIERS','XXXXXX','431','43','Sécurité sociale',1),(255,'PCG99-BASE','TIERS','XXXXXX','437','43','Autres organismes sociaux',1),(256,'PCG99-BASE','TIERS','XXXXXX','438','43','Organismes sociaux - Charges à payer et produits à recevoir',1),(257,'PCG99-BASE','TIERS','XXXXXX','44','4','État et autres collectivités publiques',1),(258,'PCG99-BASE','TIERS','XXXXXX','441','44','État - Subventions à recevoir',1),(259,'PCG99-BASE','TIERS','XXXXXX','442','44','Etat - Impôts et taxes recouvrables sur des tiers',1),(260,'PCG99-BASE','TIERS','XXXXXX','443','44','Opérations particulières avec l\'Etat, les collectivités publiques, les organismes internationaux',1),(261,'PCG99-BASE','TIERS','XXXXXX','444','44','Etat - Impôts sur les bénéfices',1),(262,'PCG99-BASE','TIERS','XXXXXX','445','44','Etat - Taxes sur le chiffre d\'affaires',1),(263,'PCG99-BASE','TIERS','XXXXXX','446','44','Obligations cautionnées',1),(264,'PCG99-BASE','TIERS','XXXXXX','447','44','Autres impôts, taxes et versements assimilés',1),(265,'PCG99-BASE','TIERS','XXXXXX','448','44','Etat - Charges à payer et produits à recevoir',1),(266,'PCG99-BASE','TIERS','XXXXXX','449','44','Quotas d\'émission à restituer à l\'Etat',1),(267,'PCG99-BASE','TIERS','XXXXXX','45','4','Groupe et associes',1),(268,'PCG99-BASE','TIERS','XXXXXX','451','45','Groupe',1),(269,'PCG99-BASE','TIERS','XXXXXX','455','45','Associés - Comptes courants',1),(270,'PCG99-BASE','TIERS','XXXXXX','456','45','Associés - Opérations sur le capital',1),(271,'PCG99-BASE','TIERS','XXXXXX','457','45','Associés - Dividendes à payer',1),(272,'PCG99-BASE','TIERS','XXXXXX','458','45','Associés - Opérations faites en commun et en G.I.E.',1),(273,'PCG99-BASE','TIERS','XXXXXX','46','4','Débiteurs divers et créditeurs divers',1),(274,'PCG99-BASE','TIERS','XXXXXX','462','46','Créances sur cessions d\'immobilisations',1),(275,'PCG99-BASE','TIERS','XXXXXX','464','46','Dettes sur acquisitions de valeurs mobilières de placement',1),(276,'PCG99-BASE','TIERS','XXXXXX','465','46','Créances sur cessions de valeurs mobilières de placement',1),(277,'PCG99-BASE','TIERS','XXXXXX','467','46','Autres comptes débiteurs ou créditeurs',1),(278,'PCG99-BASE','TIERS','XXXXXX','468','46','Divers - Charges à payer et produits à recevoir',1),(279,'PCG99-BASE','TIERS','XXXXXX','47','4','Comptes transitoires ou d\'attente',1),(280,'PCG99-BASE','TIERS','XXXXXX','471','47','Comptes d\'attente',1),(281,'PCG99-BASE','TIERS','XXXXXX','476','47','Différence de conversion - Actif',1),(282,'PCG99-BASE','TIERS','XXXXXX','477','47','Différences de conversion - Passif',1),(283,'PCG99-BASE','TIERS','XXXXXX','478','47','Autres comptes transitoires',1),(284,'PCG99-BASE','TIERS','XXXXXX','48','4','Comptes de régularisation',1),(285,'PCG99-BASE','TIERS','XXXXXX','481','48','Charges à répartir sur plusieurs exercices',1),(286,'PCG99-BASE','TIERS','XXXXXX','486','48','Charges constatées d\'avance',1),(287,'PCG99-BASE','TIERS','XXXXXX','487','48','Produits constatés d\'avance',1),(288,'PCG99-BASE','TIERS','XXXXXX','488','48','Comptes de répartition périodique des charges et des produits',1),(289,'PCG99-BASE','TIERS','XXXXXX','489','48','Quotas d\'émission alloués par l\'Etat',1),(290,'PCG99-BASE','TIERS','XXXXXX','49','4','Provisions pour dépréciation des comptes de tiers',1),(291,'PCG99-BASE','TIERS','XXXXXX','491','49','Provisions pour dépréciation des comptes de clients',1),(292,'PCG99-BASE','TIERS','XXXXXX','495','49','Provisions pour dépréciation des comptes du groupe et des associés',1),(293,'PCG99-BASE','TIERS','XXXXXX','496','49','Provisions pour dépréciation des comptes de débiteurs divers',1),(294,'PCG99-BASE','FINAN','XXXXXX','50','5','Valeurs mobilières de placement',1),(295,'PCG99-BASE','FINAN','XXXXXX','501','50','Parts dans des entreprises liées',1),(296,'PCG99-BASE','FINAN','XXXXXX','502','50','Actions propres',1),(297,'PCG99-BASE','FINAN','XXXXXX','503','50','Actions',1),(298,'PCG99-BASE','FINAN','XXXXXX','504','50','Autres titres conférant un droit de propriété',1),(299,'PCG99-BASE','FINAN','XXXXXX','505','50','Obligations et bons émis par la société et rachetés par elle',1),(300,'PCG99-BASE','FINAN','XXXXXX','506','50','Obligations',1),(301,'PCG99-BASE','FINAN','XXXXXX','507','50','Bons du Trésor et bons de caisse à court terme',1),(302,'PCG99-BASE','FINAN','XXXXXX','508','50','Autres valeurs mobilières de placement et autres créances assimilées',1),(303,'PCG99-BASE','FINAN','XXXXXX','509','50','Versements restant à effectuer sur valeurs mobilières de placement non libérées',1),(304,'PCG99-BASE','FINAN','XXXXXX','51','5','Banques, établissements financiers et assimilés',1),(305,'PCG99-BASE','FINAN','XXXXXX','511','51','Valeurs à l\'encaissement',1),(306,'PCG99-BASE','FINAN','BANK','512','51','Banques',1),(307,'PCG99-BASE','FINAN','XXXXXX','514','51','Chèques postaux',1),(308,'PCG99-BASE','FINAN','XXXXXX','515','51','\"Caisses\" du Trésor et des établissements publics',1),(309,'PCG99-BASE','FINAN','XXXXXX','516','51','Sociétés de bourse',1),(310,'PCG99-BASE','FINAN','XXXXXX','517','51','Autres organismes financiers',1),(311,'PCG99-BASE','FINAN','XXXXXX','518','51','Intérêts courus',1),(312,'PCG99-BASE','FINAN','XXXXXX','519','51','Concours bancaires courants',1),(313,'PCG99-BASE','FINAN','XXXXXX','52','5','Instruments de trésorerie',1),(314,'PCG99-BASE','FINAN','CASH','53','5','Caisse',1),(315,'PCG99-BASE','FINAN','XXXXXX','531','53','Caisse siège social',1),(316,'PCG99-BASE','FINAN','XXXXXX','532','53','Caisse succursale (ou usine) A',1),(317,'PCG99-BASE','FINAN','XXXXXX','533','53','Caisse succursale (ou usine) B',1),(318,'PCG99-BASE','FINAN','XXXXXX','54','5','Régies d\'avance et accréditifs',1),(319,'PCG99-BASE','FINAN','XXXXXX','58','5','Virements internes',1),(320,'PCG99-BASE','FINAN','XXXXXX','59','5','Provisions pour dépréciation des comptes financiers',1),(321,'PCG99-BASE','FINAN','XXXXXX','590','59','Provisions pour dépréciation des valeurs mobilières de placement',1),(322,'PCG99-BASE','CHARGE','PRODUCT','60','6','Achats',1),(323,'PCG99-BASE','CHARGE','XXXXXX','601','60','Achats stockés - Matières premières (et fournitures)',1),(324,'PCG99-BASE','CHARGE','XXXXXX','602','60','Achats stockés - Autres approvisionnements',1),(325,'PCG99-BASE','CHARGE','XXXXXX','603','60','Variations des stocks (approvisionnements et marchandises)',1),(326,'PCG99-BASE','CHARGE','XXXXXX','604','60','Achats stockés - Matières premières (et fournitures)',1),(327,'PCG99-BASE','CHARGE','XXXXXX','605','60','Achats de matériel, équipements et travaux',1),(328,'PCG99-BASE','CHARGE','XXXXXX','606','60','Achats non stockés de matière et fournitures',1),(329,'PCG99-BASE','CHARGE','XXXXXX','607','60','Achats de marchandises',1),(330,'PCG99-BASE','CHARGE','XXXXXX','608','60','(Compte réservé, le cas échéant, à la récapitulation des frais accessoires incorporés aux achats)',1),(331,'PCG99-BASE','CHARGE','XXXXXX','609','60','Rabais, remises et ristournes obtenus sur achats',1),(332,'PCG99-BASE','CHARGE','SERVICE','61','6','Services extérieurs',1),(333,'PCG99-BASE','CHARGE','XXXXXX','611','61','Sous-traitance générale',1),(334,'PCG99-BASE','CHARGE','XXXXXX','612','61','Redevances de crédit-bail',1),(335,'PCG99-BASE','CHARGE','XXXXXX','613','61','Locations',1),(336,'PCG99-BASE','CHARGE','XXXXXX','614','61','Charges locatives et de copropriété',1),(337,'PCG99-BASE','CHARGE','XXXXXX','615','61','Entretien et réparations',1),(338,'PCG99-BASE','CHARGE','XXXXXX','616','61','Primes d\'assurances',1),(339,'PCG99-BASE','CHARGE','XXXXXX','617','61','Etudes et recherches',1),(340,'PCG99-BASE','CHARGE','XXXXXX','618','61','Divers',1),(341,'PCG99-BASE','CHARGE','XXXXXX','619','61','Rabais, remises et ristournes obtenus sur services extérieurs',1),(342,'PCG99-BASE','CHARGE','XXXXXX','62','6','Autres services extérieurs',1),(343,'PCG99-BASE','CHARGE','XXXXXX','621','62','Personnel extérieur à l\'entreprise',1),(344,'PCG99-BASE','CHARGE','XXXXXX','622','62','Rémunérations d\'intermédiaires et honoraires',1),(345,'PCG99-BASE','CHARGE','XXXXXX','623','62','Publicité, publications, relations publiques',1),(346,'PCG99-BASE','CHARGE','XXXXXX','624','62','Transports de biens et transports collectifs du personnel',1),(347,'PCG99-BASE','CHARGE','XXXXXX','625','62','Déplacements, missions et réceptions',1),(348,'PCG99-BASE','CHARGE','XXXXXX','626','62','Frais postaux et de télécommunications',1),(349,'PCG99-BASE','CHARGE','XXXXXX','627','62','Services bancaires et assimilés',1),(350,'PCG99-BASE','CHARGE','XXXXXX','628','62','Divers',1),(351,'PCG99-BASE','CHARGE','XXXXXX','629','62','Rabais, remises et ristournes obtenus sur autres services extérieurs',1),(352,'PCG99-BASE','CHARGE','XXXXXX','63','6','Impôts, taxes et versements assimilés',1),(353,'PCG99-BASE','CHARGE','XXXXXX','631','63','Impôts, taxes et versements assimilés sur rémunérations (administrations des impôts)',1),(354,'PCG99-BASE','CHARGE','XXXXXX','633','63','Impôts, taxes et versements assimilés sur rémunérations (autres organismes)',1),(355,'PCG99-BASE','CHARGE','XXXXXX','635','63','Autres impôts, taxes et versements assimilés (administrations des impôts)',1),(356,'PCG99-BASE','CHARGE','XXXXXX','637','63','Autres impôts, taxes et versements assimilés (autres organismes)',1),(357,'PCG99-BASE','CHARGE','XXXXXX','64','6','Charges de personnel',1),(358,'PCG99-BASE','CHARGE','XXXXXX','641','64','Rémunérations du personnel',1),(359,'PCG99-BASE','CHARGE','XXXXXX','644','64','Rémunération du travail de l\'exploitant',1),(360,'PCG99-BASE','CHARGE','SOCIAL','645','64','Charges de sécurité sociale et de prévoyance',1),(361,'PCG99-BASE','CHARGE','XXXXXX','646','64','Cotisations sociales personnelles de l\'exploitant',1),(362,'PCG99-BASE','CHARGE','XXXXXX','647','64','Autres charges sociales',1),(363,'PCG99-BASE','CHARGE','XXXXXX','648','64','Autres charges de personnel',1),(364,'PCG99-BASE','CHARGE','XXXXXX','65','6','Autres charges de gestion courante',1),(365,'PCG99-BASE','CHARGE','XXXXXX','651','65','Redevances pour concessions, brevets, licences, marques, procédés, logiciels, droits et valeurs similaires',1),(366,'PCG99-BASE','CHARGE','XXXXXX','653','65','Jetons de présence',1),(367,'PCG99-BASE','CHARGE','XXXXXX','654','65','Pertes sur créances irrécouvrables',1),(368,'PCG99-BASE','CHARGE','XXXXXX','655','65','Quote-part de résultat sur opérations faites en commun',1),(369,'PCG99-BASE','CHARGE','XXXXXX','658','65','Charges diverses de gestion courante',1),(370,'PCG99-BASE','CHARGE','XXXXXX','66','6','Charges financières',1),(371,'PCG99-BASE','CHARGE','XXXXXX','661','66','Charges d\'intérêts',1),(372,'PCG99-BASE','CHARGE','XXXXXX','664','66','Pertes sur créances liées à des participations',1),(373,'PCG99-BASE','CHARGE','XXXXXX','665','66','Escomptes accordés',1),(374,'PCG99-BASE','CHARGE','XXXXXX','666','66','Pertes de change',1),(375,'PCG99-BASE','CHARGE','XXXXXX','667','66','Charges nettes sur cessions de valeurs mobilières de placement',1),(376,'PCG99-BASE','CHARGE','XXXXXX','668','66','Autres charges financières',1),(377,'PCG99-BASE','CHARGE','XXXXXX','67','6','Charges exceptionnelles',1),(378,'PCG99-BASE','CHARGE','XXXXXX','671','67','Charges exceptionnelles sur opérations de gestion',1),(379,'PCG99-BASE','CHARGE','XXXXXX','672','67','(Compte à la disposition des entités pour enregistrer, en cours d\'exercice, les charges sur exercices antérieurs)',1),(380,'PCG99-BASE','CHARGE','XXXXXX','675','67','Valeurs comptables des éléments d\'actif cédés',1),(381,'PCG99-BASE','CHARGE','XXXXXX','678','67','Autres charges exceptionnelles',1),(382,'PCG99-BASE','CHARGE','XXXXXX','68','6','Dotations aux amortissements et aux provisions',1),(383,'PCG99-BASE','CHARGE','XXXXXX','681','68','Dotations aux amortissements et aux provisions - Charges d\'exploitation',1),(384,'PCG99-BASE','CHARGE','XXXXXX','686','68','Dotations aux amortissements et aux provisions - Charges financières',1),(385,'PCG99-BASE','CHARGE','XXXXXX','687','68','Dotations aux amortissements et aux provisions - Charges exceptionnelles',1),(386,'PCG99-BASE','CHARGE','XXXXXX','69','6','Participation des salariés - impôts sur les bénéfices et assimiles',1),(387,'PCG99-BASE','CHARGE','XXXXXX','691','69','Participation des salariés aux résultats',1),(388,'PCG99-BASE','CHARGE','XXXXXX','695','69','Impôts sur les bénéfices',1),(389,'PCG99-BASE','CHARGE','XXXXXX','696','69','Suppléments d\'impôt sur les sociétés liés aux distributions',1),(390,'PCG99-BASE','CHARGE','XXXXXX','697','69','Imposition forfaitaire annuelle des sociétés',1),(391,'PCG99-BASE','CHARGE','XXXXXX','698','69','Intégration fiscale',1),(392,'PCG99-BASE','CHARGE','XXXXXX','699','69','Produits - Reports en arrière des déficits',1),(393,'PCG99-BASE','PROD','XXXXXX','70','7','Ventes de produits fabriqués, prestations de services, marchandises',1),(394,'PCG99-BASE','PROD','PRODUCT','701','70','Ventes de produits finis',1),(395,'PCG99-BASE','PROD','XXXXXX','702','70','Ventes de produits intermédiaires',1),(396,'PCG99-BASE','PROD','XXXXXX','703','70','Ventes de produits résiduels',1),(397,'PCG99-BASE','PROD','XXXXXX','704','70','Travaux',1),(398,'PCG99-BASE','PROD','XXXXXX','705','70','Etudes',1),(399,'PCG99-BASE','PROD','SERVICE','706','70','Prestations de services',1),(400,'PCG99-BASE','PROD','PRODUCT','707','70','Ventes de marchandises',1),(401,'PCG99-BASE','PROD','PRODUCT','708','70','Produits des activités annexes',1),(402,'PCG99-BASE','PROD','XXXXXX','709','70','Rabais, remises et ristournes accordés par l\'entreprise',1),(403,'PCG99-BASE','PROD','XXXXXX','71','7','Production stockée (ou déstockage)',1),(404,'PCG99-BASE','PROD','XXXXXX','713','71','Variation des stocks (en-cours de production, produits)',1),(405,'PCG99-BASE','PROD','XXXXXX','72','7','Production immobilisée',1),(406,'PCG99-BASE','PROD','XXXXXX','721','72','Immobilisations incorporelles',1),(407,'PCG99-BASE','PROD','XXXXXX','722','72','Immobilisations corporelles',1),(408,'PCG99-BASE','PROD','XXXXXX','74','7','Subventions d\'exploitation',1),(409,'PCG99-BASE','PROD','XXXXXX','75','7','Autres produits de gestion courante',1),(410,'PCG99-BASE','PROD','XXXXXX','751','75','Redevances pour concessions, brevets, licences, marques, procédés, logiciels, droits et valeurs similaires',1),(411,'PCG99-BASE','PROD','XXXXXX','752','75','Revenus des immeubles non affectés à des activités professionnelles',1),(412,'PCG99-BASE','PROD','XXXXXX','753','75','Jetons de présence et rémunérations d\'administrateurs, gérants,...',1),(413,'PCG99-BASE','PROD','XXXXXX','754','75','Ristournes perçues des coopératives (provenant des excédents)',1),(414,'PCG99-BASE','PROD','XXXXXX','755','75','Quotes-parts de résultat sur opérations faites en commun',1),(415,'PCG99-BASE','PROD','XXXXXX','758','75','Produits divers de gestion courante',1),(416,'PCG99-BASE','PROD','XXXXXX','76','7','Produits financiers',1),(417,'PCG99-BASE','PROD','XXXXXX','761','76','Produits de participations',1),(418,'PCG99-BASE','PROD','XXXXXX','762','76','Produits des autres immobilisations financières',1),(419,'PCG99-BASE','PROD','XXXXXX','763','76','Revenus des autres créances',1),(420,'PCG99-BASE','PROD','XXXXXX','764','76','Revenus des valeurs mobilières de placement',1),(421,'PCG99-BASE','PROD','XXXXXX','765','76','Escomptes obtenus',1),(422,'PCG99-BASE','PROD','XXXXXX','766','76','Gains de change',1),(423,'PCG99-BASE','PROD','XXXXXX','767','76','Produits nets sur cessions de valeurs mobilières de placement',1),(424,'PCG99-BASE','PROD','XXXXXX','768','76','Autres produits financiers',1),(425,'PCG99-BASE','PROD','XXXXXX','77','7','Produits exceptionnels',1),(426,'PCG99-BASE','PROD','XXXXXX','771','77','Produits exceptionnels sur opérations de gestion',1),(427,'PCG99-BASE','PROD','XXXXXX','772','77','(Compte à la disposition des entités pour enregistrer, en cours d\'exercice, les produits sur exercices antérieurs)',1),(428,'PCG99-BASE','PROD','XXXXXX','775','77','Produits des cessions d\'éléments d\'actif',1),(429,'PCG99-BASE','PROD','XXXXXX','777','77','Quote-part des subventions d\'investissement virée au résultat de l\'exercice',1),(430,'PCG99-BASE','PROD','XXXXXX','778','77','Autres produits exceptionnels',1),(431,'PCG99-BASE','PROD','XXXXXX','78','7','Reprises sur amortissements et provisions',1),(432,'PCG99-BASE','PROD','XXXXXX','781','78','Reprises sur amortissements et provisions (à inscrire dans les produits d\'exploitation)',1),(433,'PCG99-BASE','PROD','XXXXXX','786','78','Reprises sur provisions pour risques (à inscrire dans les produits financiers)',1),(434,'PCG99-BASE','PROD','XXXXXX','787','78','Reprises sur provisions (à inscrire dans les produits exceptionnels)',1),(435,'PCG99-BASE','PROD','XXXXXX','79','7','Transferts de charges',1),(436,'PCG99-BASE','PROD','XXXXXX','791','79','Transferts de charges d\'exploitation ',1),(437,'PCG99-BASE','PROD','XXXXXX','796','79','Transferts de charges financières',1),(438,'PCG99-BASE','PROD','XXXXXX','797','79','Transferts de charges exceptionnelles',1);
/*!40000 ALTER TABLE `llx_accountingaccount` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_accountingdebcred`
--

DROP TABLE IF EXISTS `llx_accountingdebcred`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_accountingdebcred` (
  `fk_transaction` int(11) NOT NULL,
  `fk_account` int(11) NOT NULL,
  `amount` double NOT NULL,
  `direction` varchar(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_accountingdebcred`
--

LOCK TABLES `llx_accountingdebcred` WRITE;
/*!40000 ALTER TABLE `llx_accountingdebcred` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_accountingdebcred` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_accountingtransaction`
--

DROP TABLE IF EXISTS `llx_accountingtransaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_accountingtransaction` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(128) NOT NULL,
  `datec` date NOT NULL,
  `fk_author` varchar(20) NOT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_facture` int(11) DEFAULT NULL,
  `fk_facture_fourn` int(11) DEFAULT NULL,
  `fk_paiement` int(11) DEFAULT NULL,
  `fk_paiement_fourn` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_accountingtransaction`
--

LOCK TABLES `llx_accountingtransaction` WRITE;
/*!40000 ALTER TABLE `llx_accountingtransaction` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_accountingtransaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_actioncomm`
--

DROP TABLE IF EXISTS `llx_actioncomm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_actioncomm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ref_ext` varchar(128) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datep` datetime DEFAULT NULL,
  `datep2` datetime DEFAULT NULL,
  `datea` datetime DEFAULT NULL,
  `datea2` datetime DEFAULT NULL,
  `fk_action` int(11) DEFAULT NULL,
  `code` varchar(32) DEFAULT NULL,
  `label` varchar(128) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_mod` int(11) DEFAULT NULL,
  `fk_project` int(11) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_contact` int(11) DEFAULT NULL,
  `fk_parent` int(11) NOT NULL DEFAULT '0',
  `fk_user_action` int(11) DEFAULT NULL,
  `transparency` int(11) DEFAULT NULL,
  `fk_user_done` int(11) DEFAULT NULL,
  `priority` smallint(6) DEFAULT NULL,
  `fulldayevent` smallint(6) NOT NULL DEFAULT '0',
  `punctual` smallint(6) NOT NULL DEFAULT '1',
  `percent` smallint(6) NOT NULL DEFAULT '0',
  `location` varchar(128) DEFAULT NULL,
  `durationp` double DEFAULT NULL,
  `durationa` double DEFAULT NULL,
  `note` text,
  `fk_element` int(11) DEFAULT NULL,
  `elementtype` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_actioncomm_datea` (`datea`),
  KEY `idx_actioncomm_fk_soc` (`fk_soc`),
  KEY `idx_actioncomm_fk_contact` (`fk_contact`)
) ENGINE=InnoDB AUTO_INCREMENT=320 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_actioncomm`
--

LOCK TABLES `llx_actioncomm` WRITE;
/*!40000 ALTER TABLE `llx_actioncomm` DISABLE KEYS */;
INSERT INTO `llx_actioncomm` VALUES (1,NULL,1,'2010-07-08 14:21:44','2010-07-08 14:21:44',NULL,NULL,50,NULL,'Company AAA and Co added into Dolibarr','2010-07-08 14:21:44','2010-07-08 12:21:44',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Company AAA and Co added into Dolibarr\nAuthor: admin',NULL,NULL),(2,NULL,1,'2010-07-08 14:23:48','2010-07-08 14:23:48',NULL,NULL,50,NULL,'Company Belin SARL added into Dolibarr','2010-07-08 14:23:48','2010-07-08 12:23:48',1,NULL,NULL,2,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Company Belin SARL added into Dolibarr\nAuthor: admin',NULL,NULL),(3,NULL,1,'2010-07-08 22:42:12','2010-07-08 22:42:12',NULL,NULL,50,NULL,'Company Spanish Comp added into Dolibarr','2010-07-08 22:42:12','2010-07-08 20:42:12',1,NULL,NULL,3,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Company Spanish Comp added into Dolibarr\nAuthor: admin',NULL,NULL),(4,NULL,1,'2010-07-08 22:48:18','2010-07-08 22:48:18',NULL,NULL,50,NULL,'Company Prospector Vaalen added into Dolibarr','2010-07-08 22:48:18','2010-07-08 20:48:18',1,NULL,NULL,4,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Company Prospector Vaalen added into Dolibarr\nAuthor: admin',NULL,NULL),(5,NULL,1,'2010-07-08 23:22:57','2010-07-08 23:22:57',NULL,NULL,50,NULL,'Company NoCountry Co added into Dolibarr','2010-07-08 23:22:57','2010-07-08 21:22:57',1,NULL,NULL,5,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Company NoCountry Co added into Dolibarr\nAuthor: admin',NULL,NULL),(6,NULL,1,'2010-07-09 00:15:09','2010-07-09 00:15:09',NULL,NULL,50,NULL,'Company Swiss customer added into Dolibarr','2010-07-09 00:15:09','2010-07-08 22:15:09',1,NULL,NULL,6,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Company Swiss customer added into Dolibarr\nAuthor: admin',NULL,NULL),(7,NULL,1,'2010-07-09 01:24:26','2010-07-09 01:24:26',NULL,NULL,50,NULL,'Company Generic customer added into Dolibarr','2010-07-09 01:24:26','2010-07-08 23:24:26',1,NULL,NULL,7,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Company Generic customer added into Dolibarr\nAuthor: admin',NULL,NULL),(8,NULL,1,'2010-07-10 14:54:27','2010-07-10 14:54:27',NULL,NULL,50,NULL,'Société Client salon ajoutée dans Dolibarr','2010-07-10 14:54:27','2010-07-10 12:54:27',1,NULL,NULL,8,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Société Client salon ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL),(9,NULL,1,'2010-07-10 14:54:44','2010-07-10 14:54:44',NULL,NULL,50,NULL,'Société Client salon invidivdu ajoutée dans Doliba','2010-07-10 14:54:44','2010-07-10 12:54:44',1,NULL,NULL,9,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Société Client salon invidivdu ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL),(10,NULL,1,'2010-07-10 14:56:10','2010-07-10 14:56:10',NULL,NULL,50,NULL,'Facture FA1007-0001 validée dans Dolibarr','2010-07-10 14:56:10','2011-07-18 17:29:22',1,NULL,NULL,9,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Facture FA1007-0001 validée dans Dolibarr\nAuteur: admin',1,'invoice'),(11,NULL,1,'2010-07-10 14:58:53','2010-07-10 14:58:53',NULL,NULL,50,NULL,'Facture FA1007-0001 validée dans Dolibarr','2010-07-10 14:58:53','2011-07-18 17:29:22',1,NULL,NULL,9,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Facture FA1007-0001 validée dans Dolibarr\nAuteur: admin',1,'invoice'),(12,NULL,1,'2010-07-10 15:00:55','2010-07-10 15:00:55',NULL,NULL,50,NULL,'Facture FA1007-0001 passée à payée dans Dolibarr','2010-07-10 15:00:55','2011-07-18 17:29:22',1,NULL,NULL,9,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Facture FA1007-0001 passée à payée dans Dolibarr\nAuteur: admin',1,'invoice'),(13,NULL,1,'2010-07-10 15:13:08','2010-07-10 15:13:08',NULL,NULL,50,NULL,'Société Smith Vick ajoutée dans Dolibarr','2010-07-10 15:13:08','2010-07-10 13:13:08',1,NULL,NULL,10,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Société Smith Vick ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL),(14,NULL,1,'2010-07-10 15:21:00','2010-07-10 16:21:00',NULL,NULL,5,NULL,'RDV avec mon chef','2010-07-10 15:21:48','2010-07-10 13:21:48',1,NULL,NULL,NULL,NULL,0,1,NULL,NULL,0,0,1,0,'',3600,NULL,'',NULL,NULL),(15,NULL,1,'2010-07-10 18:18:16','2010-07-10 18:18:16',NULL,NULL,50,NULL,'Contrat CONTRAT1 validé dans Dolibarr','2010-07-10 18:18:16','2010-07-10 16:18:16',1,NULL,NULL,2,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Contrat CONTRAT1 validé dans Dolibarr\nAuteur: admin',NULL,NULL),(16,NULL,1,'2010-07-10 18:35:57','2010-07-10 18:35:57',NULL,NULL,50,NULL,'Société Mon client ajoutée dans Dolibarr','2010-07-10 18:35:57','2010-07-10 16:35:57',1,NULL,NULL,11,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Société Mon client ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL),(17,NULL,1,'2010-07-11 16:18:08','2010-07-11 16:18:08',NULL,NULL,50,NULL,'Société Dupont Alain ajoutée dans Dolibarr','2010-07-11 16:18:08','2010-07-11 14:18:08',1,NULL,NULL,12,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Société Dupont Alain ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL),(18,NULL,1,'2010-07-11 17:11:00','2010-07-11 17:17:00',NULL,NULL,5,NULL,'Rendez-vous','2010-07-11 17:11:22','2010-07-11 15:11:22',1,NULL,NULL,NULL,NULL,0,1,NULL,NULL,0,0,1,0,'gfgdfgdf',360,NULL,'',NULL,NULL),(19,NULL,1,'2010-07-11 17:13:20','2010-07-11 17:13:20',NULL,NULL,50,NULL,'Société Vendeur de chips ajoutée dans Dolibarr','2010-07-11 17:13:20','2010-07-11 15:13:20',1,NULL,NULL,13,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Société Vendeur de chips ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL),(20,NULL,1,'2010-07-11 17:15:42','2010-07-11 17:15:42',NULL,NULL,50,NULL,'Commande CF1007-0001 validée','2010-07-11 17:15:42','2010-07-11 15:15:42',1,NULL,NULL,13,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Commande CF1007-0001 validée\nAuteur: admin',NULL,NULL),(21,NULL,1,'2010-07-11 18:47:33','2010-07-11 18:47:33',NULL,NULL,50,NULL,'Commande CF1007-0002 validée','2010-07-11 18:47:33','2010-07-11 16:47:33',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Commande CF1007-0002 validée\nAuteur: admin',NULL,NULL),(22,NULL,1,'2010-07-18 11:36:18','2010-07-18 11:36:18',NULL,NULL,50,NULL,'Proposition PR1007-0003 validée','2010-07-18 11:36:18','2011-07-18 17:29:22',1,NULL,NULL,4,NULL,0,NULL,NULL,1,0,0,1,100,'',NULL,NULL,'Proposition PR1007-0003 validée\nAuteur: admin',3,'propal'),(23,NULL,1,'2011-07-18 20:49:58','2011-07-18 20:49:58',NULL,NULL,50,NULL,'Invoice FA1007-0002 validated in Dolibarr','2011-07-18 20:49:58','2011-07-18 18:49:58',1,NULL,NULL,2,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1007-0002 validated in Dolibarr\nAuthor: admin',2,'invoice'),(24,NULL,1,'2011-07-28 01:37:00',NULL,NULL,NULL,1,NULL,'Phone call','2011-07-28 01:37:48','2011-07-27 23:37:48',1,NULL,NULL,NULL,2,0,1,NULL,NULL,0,0,1,-1,'',NULL,NULL,'',NULL,NULL),(25,NULL,1,'2011-08-01 02:31:24','2011-08-01 02:31:24',NULL,NULL,50,NULL,'Company mmm added into Dolibarr','2011-08-01 02:31:24','2011-08-01 00:31:24',1,NULL,NULL,15,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Company mmm added into Dolibarr\nAuthor: admin',15,'societe'),(26,NULL,1,'2011-08-01 02:31:43','2011-08-01 02:31:43',NULL,NULL,50,NULL,'Company ppp added into Dolibarr','2011-08-01 02:31:43','2011-08-01 00:31:43',1,NULL,NULL,16,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Company ppp added into Dolibarr\nAuthor: admin',16,'societe'),(27,NULL,1,'2011-08-01 02:41:26','2011-08-01 02:41:26',NULL,NULL,50,NULL,'Company aaa added into Dolibarr','2011-08-01 02:41:26','2011-08-01 00:41:26',1,NULL,NULL,17,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Company aaa added into Dolibarr\nAuthor: admin',17,'societe'),(28,NULL,1,'2011-08-01 03:34:11','2011-08-01 03:34:11',NULL,NULL,50,NULL,'Invoice FA1108-0003 validated in Dolibarr','2011-08-01 03:34:11','2011-08-01 01:34:11',1,NULL,NULL,7,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0003 validated in Dolibarr\nAuthor: admin',5,'invoice'),(29,NULL,1,'2011-08-01 03:34:11','2011-08-01 03:34:11',NULL,NULL,50,NULL,'Invoice FA1108-0003 validated in Dolibarr','2011-08-01 03:34:11','2011-08-01 01:34:11',1,NULL,NULL,7,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0003 changed to paid in Dolibarr\nAuthor: admin',5,'invoice'),(30,NULL,1,'2011-08-06 20:33:54','2011-08-06 20:33:54',NULL,NULL,50,NULL,'Invoice FA1108-0004 validated in Dolibarr','2011-08-06 20:33:54','2011-08-06 18:33:54',1,NULL,NULL,7,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0004 validated in Dolibarr\nAuthor: admin',6,'invoice'),(31,NULL,1,'2011-08-06 20:33:54','2011-08-06 20:33:54',NULL,NULL,50,NULL,'Invoice FA1108-0004 validated in Dolibarr','2011-08-06 20:33:54','2011-08-06 18:33:54',1,NULL,NULL,7,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0004 changed to paid in Dolibarr\nAuthor: admin',6,'invoice'),(38,NULL,1,'2011-08-08 02:41:55','2011-08-08 02:41:55',NULL,NULL,50,NULL,'Invoice FA1108-0005 validated in Dolibarr','2011-08-08 02:41:55','2011-08-08 00:41:55',1,NULL,NULL,2,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0005 validated in Dolibarr\nAuthor: admin',8,'invoice'),(40,NULL,1,'2011-08-08 02:53:40','2011-08-08 02:53:40',NULL,NULL,50,NULL,'Invoice FA1108-0005 changed to paid in Dolibarr','2011-08-08 02:53:40','2011-08-08 00:53:40',1,NULL,NULL,2,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0005 changed to paid in Dolibarr\nAuthor: admin',8,'invoice'),(41,NULL,1,'2011-08-08 02:54:05','2011-08-08 02:54:05',NULL,NULL,50,NULL,'Invoice FA1007-0002 changed to paid in Dolibarr','2011-08-08 02:54:05','2011-08-08 00:54:05',1,NULL,NULL,2,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1007-0002 changed to paid in Dolibarr\nAuthor: admin',2,'invoice'),(42,NULL,1,'2011-08-08 02:55:04','2011-08-08 02:55:04',NULL,NULL,50,NULL,'Invoice FA1107-0006 validated in Dolibarr','2011-08-08 02:55:04','2011-08-08 00:55:04',1,NULL,NULL,10,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1107-0006 validated in Dolibarr\nAuthor: admin',3,'invoice'),(43,NULL,1,'2011-08-08 02:55:26','2011-08-08 02:55:26',NULL,NULL,50,NULL,'Invoice FA1108-0007 validated in Dolibarr','2011-08-08 02:55:26','2011-08-08 00:55:26',1,NULL,NULL,10,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1108-0007 validated in Dolibarr\nAuthor: admin',9,'invoice'),(44,NULL,1,'2011-08-08 02:55:58','2011-08-08 02:55:58',NULL,NULL,50,NULL,'Invoice FA1107-0006 changed to paid in Dolibarr','2011-08-08 02:55:58','2011-08-08 00:55:58',1,NULL,NULL,10,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Invoice FA1107-0006 changed to paid in Dolibarr\nAuthor: admin',3,'invoice'),(45,NULL,1,'2011-08-08 03:04:22','2011-08-08 03:04:22',NULL,NULL,50,NULL,'Order CO1108-0001 validated','2011-08-08 03:04:22','2011-08-08 01:04:22',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Order CO1108-0001 validated\nAuthor: admin',5,'order'),(46,NULL,1,'2011-08-08 13:59:09','2011-08-08 13:59:09',NULL,NULL,50,NULL,'Order CO1107-0002 validated','2011-08-08 13:59:10','2011-08-08 11:59:10',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Order CO1107-0002 validated\nAuthor: admin',1,'order'),(47,NULL,1,'2011-08-08 14:24:18','2011-08-08 14:24:18',NULL,NULL,50,NULL,'Proposal PR1007-0001 validated','2011-08-08 14:24:18','2011-08-08 12:24:18',1,NULL,NULL,2,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Proposal PR1007-0001 validated\nAuthor: admin',1,'propal'),(48,NULL,1,'2011-08-08 14:24:24','2011-08-08 14:24:24',NULL,NULL,50,NULL,'Proposal PR1108-0004 validated','2011-08-08 14:24:24','2011-08-08 12:24:24',1,NULL,NULL,17,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Proposal PR1108-0004 validated\nAuthor: admin',4,'propal'),(49,NULL,1,'2011-08-08 15:04:37','2011-08-08 15:04:37',NULL,NULL,50,NULL,'Order CF1108-0003 validated','2011-08-08 15:04:37','2011-08-08 13:04:37',1,NULL,NULL,17,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Order CF1108-0003 validated\nAuthor: admin',6,'order_supplier'),(50,NULL,1,'2012-12-08 17:56:47','2012-12-08 17:56:47',NULL,NULL,40,NULL,'Facture AV1212-0001 validée dans Dolibarr','2012-12-08 17:56:47','2012-12-08 16:56:47',1,NULL,NULL,10,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture AV1212-0001 validée dans Dolibarr\nAuteur: admin',10,'invoice'),(51,NULL,1,'2012-12-08 17:57:11','2012-12-08 17:57:11',NULL,NULL,40,NULL,'Facture AV1212-0001 validée dans Dolibarr','2012-12-08 17:57:11','2012-12-08 16:57:11',1,NULL,NULL,10,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture AV1212-0001 validée dans Dolibarr\nAuteur: admin',10,'invoice'),(52,NULL,1,'2012-12-08 17:58:27','2012-12-08 17:58:27',NULL,NULL,40,NULL,'Facture FA1212-0008 validée dans Dolibarr','2012-12-08 17:58:27','2012-12-08 16:58:27',1,NULL,NULL,10,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1212-0008 validée dans Dolibarr\nAuteur: admin',11,'invoice'),(53,NULL,1,'2012-12-08 18:20:49','2012-12-08 18:20:49',NULL,NULL,40,NULL,'Facture AV1212-0002 validée dans Dolibarr','2012-12-08 18:20:49','2012-12-08 17:20:49',1,NULL,NULL,10,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture AV1212-0002 validée dans Dolibarr\nAuteur: admin',12,'invoice'),(54,NULL,1,'2012-12-09 18:35:07','2012-12-09 18:35:07',NULL,NULL,40,NULL,'Facture AV1212-0002 passée à payée dans Dolibarr','2012-12-09 18:35:07','2012-12-09 17:35:07',1,NULL,NULL,10,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture AV1212-0002 passée à payée dans Dolibarr\nAuteur: admin',12,'invoice'),(55,NULL,1,'2012-12-09 20:14:42','2012-12-09 20:14:42',NULL,NULL,40,NULL,'Société doe john ajoutée dans Dolibarr','2012-12-09 20:14:42','2012-12-09 19:14:42',1,NULL,NULL,18,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Société doe john ajoutée dans Dolibarr\nAuteur: admin',18,'societe'),(56,NULL,1,'2012-12-12 18:54:19','2012-12-12 18:54:19',NULL,NULL,40,NULL,'Facture FA1212-0009 validée dans Dolibarr','2012-12-12 18:54:19','2012-12-12 17:54:19',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1212-0009 validée dans Dolibarr\nAuteur: admin',55,'invoice'),(121,NULL,1,'2012-12-06 10:00:00',NULL,NULL,NULL,50,NULL,'aaab','2012-12-21 17:48:08','2012-12-21 16:54:07',3,1,NULL,NULL,NULL,0,3,NULL,NULL,1,0,1,-1,NULL,NULL,NULL,NULL,NULL,NULL),(122,NULL,1,'2012-12-21 18:09:52','2012-12-21 18:09:52',NULL,NULL,40,NULL,'Facture client FA1007-0001 envoyée par EMail','2012-12-21 18:09:52','2012-12-21 17:09:52',1,NULL,NULL,9,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Mail envoyé par Firstname SuperAdminName <bidon@mydomain.com> à laurent@mydomain.com.\nSujet du mail: Envoi facture FA1007-0001\nCorps du mail:\nVeuillez trouver ci-joint la facture FA1007-0001\r\n\r\nVous pouvez cliquer sur le lien sécurisé ci-dessous pour effectuer votre paiement via Paypal\r\n\r\nhttp://localhost/dolibarrnew/public/paypal/newpayment.php?source=invoice&ref=FA1007-0001&securekey=50c82fab36bb3b6aa83e2a50691803b2\r\n\r\nCordialement',1,'invoice'),(123,NULL,1,'2013-01-06 13:13:57','2013-01-06 13:13:57',NULL,NULL,40,NULL,'Facture 16 validée dans Dolibarr','2013-01-06 13:13:57','2013-01-06 12:13:57',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture 16 validée dans Dolibarr\nAuteur: admin',16,'invoice_supplier'),(124,NULL,1,'2013-01-12 12:23:05','2013-01-12 12:23:05',NULL,NULL,40,NULL,'Patient aaa ajouté','2013-01-12 12:23:05','2013-01-12 11:23:05',1,NULL,NULL,19,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Patient aaa ajouté\nAuteur: admin',19,'societe'),(125,NULL,1,'2013-01-12 12:52:20','2013-01-12 12:52:20',NULL,NULL,40,NULL,'Patient pppoo ajouté','2013-01-12 12:52:20','2013-01-12 11:52:20',1,NULL,NULL,20,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Patient pppoo ajouté\nAuteur: admin',20,'societe'),(127,NULL,1,'2013-01-19 18:22:48','2013-01-19 18:22:48',NULL,NULL,40,NULL,'Facture FS1301-0001 validée dans Dolibarr','2013-01-19 18:22:48','2013-01-19 17:22:48',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FS1301-0001 validée dans Dolibarr\nAuteur: admin',148,'invoice'),(128,NULL,1,'2013-01-19 18:31:10','2013-01-19 18:31:10',NULL,NULL,40,NULL,'Facture FA6801-0010 validée dans Dolibarr','2013-01-19 18:31:10','2013-01-19 17:31:10',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA6801-0010 validée dans Dolibarr\nAuteur: admin',150,'invoice'),(129,NULL,1,'2013-01-19 18:31:10','2013-01-19 18:31:10',NULL,NULL,40,NULL,'Facture FA6801-0010 passée à payée dans Dolibarr','2013-01-19 18:31:10','2013-01-19 17:31:10',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA6801-0010 passée à payée dans Dolibarr\nAuteur: admin',150,'invoice'),(130,NULL,1,'2013-01-19 18:31:58','2013-01-19 18:31:58',NULL,NULL,40,NULL,'Facture FS1301-0002 validée dans Dolibarr','2013-01-19 18:31:58','2013-01-19 17:31:58',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FS1301-0002 validée dans Dolibarr\nAuteur: admin',151,'invoice'),(131,NULL,1,'2013-01-19 18:31:58','2013-01-19 18:31:58',NULL,NULL,40,NULL,'Facture FS1301-0002 passée à payée dans Dolibarr','2013-01-19 18:31:58','2013-01-19 17:31:58',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FS1301-0002 passée à payée dans Dolibarr\nAuteur: admin',151,'invoice'),(132,NULL,1,'2013-01-23 15:07:54','2013-01-23 15:07:54',NULL,NULL,50,NULL,'Consultation 24 saisie (aaa)','2013-01-23 15:07:54','2013-01-23 14:07:54',1,NULL,NULL,19,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Consultation 24 saisie (aaa)\nAuteur: admin',24,'cabinetmed_cons'),(133,NULL,1,'2013-01-23 16:56:58','2013-01-23 16:56:58',NULL,NULL,40,NULL,'Patient pa ajouté','2013-01-23 16:56:58','2013-01-23 15:56:58',1,NULL,NULL,21,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Patient pa ajouté\nAuteur: admin',21,'societe'),(134,NULL,1,'2013-01-23 17:34:00',NULL,NULL,NULL,50,NULL,'bbcv','2013-01-23 17:35:21','2013-01-23 16:35:21',1,NULL,1,2,NULL,0,1,NULL,NULL,0,0,1,-1,'',NULL,NULL,'',NULL,NULL),(135,NULL,1,'2013-02-12 15:54:00','2013-02-12 15:54:00',NULL,NULL,40,NULL,'Facture FA1212-0011 validée dans Dolibarr','2013-02-12 15:54:37','2013-02-20 20:11:54',1,1,NULL,7,NULL,0,NULL,NULL,1,0,0,1,50,NULL,NULL,NULL,'Facture FA1212-0011 valid&eacute;e dans Dolibarr<br />\r\nAuteur: admin',13,'invoice'),(136,NULL,1,'2013-02-12 17:06:51','2013-02-12 17:06:51',NULL,NULL,40,NULL,'Commande CO1107-0003 validée','2013-02-12 17:06:51','2013-02-12 16:06:51',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Commande CO1107-0003 validée\nAuteur: admin',2,'order'),(137,NULL,1,'2013-02-17 16:22:10','2013-02-17 16:22:10',NULL,NULL,40,NULL,'Proposition PR1302-0009 validée','2013-02-17 16:22:10','2013-02-17 15:22:10',1,NULL,NULL,19,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Proposition PR1302-0009 validée\nAuteur: admin',9,'propal'),(138,NULL,1,'2013-02-17 16:27:00','2013-02-17 16:27:00',NULL,NULL,40,NULL,'Facture FA1302-0012 validée dans Dolibarr','2013-02-17 16:27:00','2013-02-17 15:27:00',1,NULL,NULL,18,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1302-0012 validée dans Dolibarr\nAuteur: admin',152,'invoice'),(139,NULL,1,'2013-02-17 16:27:29','2013-02-17 16:27:29',NULL,NULL,40,NULL,'Proposition PR1302-0010 validée','2013-02-17 16:27:29','2013-02-17 15:27:29',1,NULL,NULL,18,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Proposition PR1302-0010 validée\nAuteur: admin',11,'propal'),(140,NULL,1,'2013-02-17 18:27:56','2013-02-17 18:27:56',NULL,NULL,40,NULL,'Commande CO1107-0004 validée','2013-02-17 18:27:56','2013-02-17 17:27:56',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Commande CO1107-0004 validée\nAuteur: admin',3,'order'),(141,NULL,1,'2013-02-17 18:38:14','2013-02-17 18:38:14',NULL,NULL,40,NULL,'Commande CO1302-0005 validée','2013-02-17 18:38:14','2013-02-17 17:38:14',1,NULL,NULL,18,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Commande CO1302-0005 validée\nAuteur: admin',7,'order'),(142,NULL,1,'2013-02-26 22:57:50','2013-02-26 22:57:50',NULL,NULL,40,NULL,'Company pppp added into Dolibarr','2013-02-26 22:57:50','2013-02-26 21:57:50',1,NULL,NULL,22,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Company pppp added into Dolibarr\nAuthor: admin',22,'societe'),(143,NULL,1,'2013-02-26 22:58:13','2013-02-26 22:58:13',NULL,NULL,40,NULL,'Company ttttt added into Dolibarr','2013-02-26 22:58:13','2013-02-26 21:58:13',1,NULL,NULL,23,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Company ttttt added into Dolibarr\nAuthor: admin',23,'societe'),(144,NULL,1,'2013-02-27 10:00:00','2013-02-27 19:20:00',NULL,NULL,5,NULL,'Rendez-vous','2013-02-27 19:20:53','2013-02-27 18:20:53',1,NULL,NULL,NULL,NULL,0,1,NULL,1,0,0,1,-1,'',33600,NULL,'',NULL,NULL),(145,NULL,1,'2013-02-27 19:28:00',NULL,NULL,NULL,2,NULL,'fdsfsd','2013-02-27 19:28:48','2013-02-27 18:29:53',1,1,NULL,NULL,NULL,0,1,NULL,1,0,0,1,-1,NULL,NULL,NULL,NULL,NULL,NULL),(146,NULL,1,'2013-03-06 10:05:07','2013-03-06 10:05:07',NULL,NULL,40,NULL,'Contrat (PROV3) validé dans Dolibarr','2013-03-06 10:05:07','2013-03-06 09:05:07',1,NULL,NULL,19,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Contrat (PROV3) validé dans Dolibarr\nAuteur: admin',3,'contract'),(147,NULL,1,'2013-03-06 16:43:37','2013-03-06 16:43:37',NULL,NULL,40,NULL,'Facture FA1307-0013 validée dans Dolibarr','2013-03-06 16:43:37','2013-03-06 15:43:37',1,NULL,NULL,12,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1307-0013 validée dans Dolibarr\nAuteur: admin',158,'invoice'),(148,NULL,1,'2013-03-06 16:44:12','2013-03-06 16:44:12',NULL,NULL,40,NULL,'Facture FA1407-0014 validée dans Dolibarr','2013-03-06 16:44:12','2013-03-06 15:44:12',1,NULL,NULL,12,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1407-0014 validée dans Dolibarr\nAuteur: admin',159,'invoice'),(149,NULL,1,'2013-03-06 16:47:48','2013-03-06 16:47:48',NULL,NULL,40,NULL,'Facture FA1507-0015 validée dans Dolibarr','2013-03-06 16:47:48','2013-03-06 15:47:48',1,NULL,NULL,12,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1507-0015 validée dans Dolibarr\nAuteur: admin',160,'invoice'),(150,NULL,1,'2013-03-06 16:48:16','2013-03-06 16:48:16',NULL,NULL,40,NULL,'Facture FA1607-0016 validée dans Dolibarr','2013-03-06 16:48:16','2013-03-06 15:48:16',1,NULL,NULL,12,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1607-0016 validée dans Dolibarr\nAuteur: admin',161,'invoice'),(151,NULL,1,'2013-03-06 17:13:59','2013-03-06 17:13:59',NULL,NULL,40,NULL,'Société smith smith ajoutée dans Dolibarr','2013-03-06 17:13:59','2013-03-06 16:13:59',1,NULL,NULL,24,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Société smith smith ajoutée dans Dolibarr\nAuteur: admin',24,'societe'),(152,NULL,1,'2013-03-08 10:02:22','2013-03-08 10:02:22',NULL,NULL,40,NULL,'Proposition (PROV12) validée dans Dolibarr','2013-03-08 10:02:22','2013-03-08 09:02:22',1,NULL,NULL,23,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Proposition (PROV12) validée dans Dolibarr\nAuteur: admin',12,'propal'),(203,NULL,1,'2013-03-09 19:39:27','2013-03-09 19:39:27',NULL,NULL,40,'AC_ORDER_SUPPLIER_VALIDATE','Commande CF1303-0004 validée','2013-03-09 19:39:27','2013-03-09 18:39:27',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Commande CF1303-0004 validée\nAuteur: admin',13,'order_supplier'),(204,NULL,1,'2013-03-10 15:47:37','2013-03-10 15:47:37',NULL,NULL,40,'AC_COMPANY_CREATE','Patient créé','2013-03-10 15:47:37','2013-03-10 14:47:37',1,NULL,NULL,25,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Patient créé\nAuteur: admin',25,'societe'),(205,NULL,1,'2013-03-10 15:57:32','2013-03-10 15:57:32',NULL,NULL,40,'AC_COMPANY_CREATE','Tiers créé','2013-03-10 15:57:32','2013-03-10 14:57:32',1,NULL,NULL,26,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Tiers créé\nAuteur: admin',26,'societe'),(206,NULL,1,'2013-03-10 15:58:28','2013-03-10 15:58:28',NULL,NULL,40,'AC_BILL_VALIDATE','Facture FA1303-0017 validée','2013-03-10 15:58:28','2013-03-10 14:58:28',1,NULL,NULL,26,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1303-0017 validée\nAuteur: admin',208,'invoice'),(207,NULL,1,'2013-03-19 09:38:10','2013-03-19 09:38:10',NULL,NULL,40,'AC_BILL_VALIDATE','Facture FA1303-0018 validée','2013-03-19 09:38:10','2013-03-19 08:38:10',1,NULL,NULL,19,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1303-0018 validée\nAuteur: admin',209,'invoice'),(208,NULL,1,'2013-03-20 14:30:11','2013-03-20 14:30:11',NULL,NULL,40,'AC_BILL_VALIDATE','Facture FA1107-0019 validée','2013-03-20 14:30:11','2013-03-20 13:30:11',1,NULL,NULL,10,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1107-0019 validée\nAuteur: admin',210,'invoice'),(209,NULL,1,'2013-03-22 09:40:25','2013-03-22 09:40:25',NULL,NULL,40,'AC_BILL_VALIDATE','Facture FA1303-0020 validée','2013-03-22 09:40:25','2013-03-22 08:40:25',1,NULL,NULL,19,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1303-0020 validée\nAuteur: admin',211,'invoice'),(210,NULL,1,'2013-03-23 17:16:25','2013-03-23 17:16:25',NULL,NULL,40,'AC_BILL_VALIDATE','Facture FA1303-0020 validée','2013-03-23 17:16:25','2013-03-23 16:16:25',1,NULL,NULL,19,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1303-0020 validée\nAuteur: admin',211,'invoice'),(211,NULL,1,'2013-03-23 18:08:27','2013-03-23 18:08:27',NULL,NULL,40,'AC_BILL_VALIDATE','Facture FA1307-0013 validée','2013-03-23 18:08:27','2013-03-23 17:08:27',1,NULL,NULL,12,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1307-0013 validée\nAuteur: admin',158,'invoice'),(212,NULL,1,'2013-03-24 15:54:00','2013-03-24 15:54:00',NULL,NULL,40,'AC_BILL_VALIDATE','Facture FA1212-0021 validée','2013-03-24 15:54:00','2013-03-24 14:54:00',1,NULL,NULL,1,NULL,0,NULL,NULL,1,0,0,1,-1,'',NULL,NULL,'Facture FA1212-0021 validée\nAuteur: admin',32,'invoice'),(213,NULL,1,'2013-11-07 01:02:39','2013-11-07 01:02:39',NULL,NULL,40,'AC_COMPANY_CREATE','Third party created','2013-11-07 01:02:39','2013-11-07 00:02:39',1,NULL,NULL,27,NULL,0,NULL,0,1,0,0,1,-1,'',NULL,NULL,'Third party created\nAuthor: admin',27,'societe'),(214,NULL,1,'2013-11-07 01:05:22','2013-11-07 01:05:22',NULL,NULL,40,'AC_COMPANY_CREATE','Third party created','2013-11-07 01:05:22','2013-11-07 00:05:22',1,NULL,NULL,28,NULL,0,NULL,0,1,0,0,1,-1,'',NULL,NULL,'Third party created\nAuthor: admin',28,'societe'),(215,NULL,1,'2013-11-07 01:07:07','2013-11-07 01:07:07',NULL,NULL,40,'AC_COMPANY_CREATE','Third party created','2013-11-07 01:07:07','2013-11-07 00:07:07',1,NULL,NULL,29,NULL,0,NULL,0,1,0,0,1,-1,'',NULL,NULL,'Third party created\nAuthor: admin',29,'societe'),(216,NULL,1,'2013-11-07 01:07:58','2013-11-07 01:07:58',NULL,NULL,40,'AC_COMPANY_CREATE','Third party created','2013-11-07 01:07:58','2013-11-07 00:07:58',1,NULL,NULL,30,NULL,0,NULL,0,1,0,0,1,-1,'',NULL,NULL,'Third party created\nAuthor: admin',30,'societe'),(217,NULL,1,'2013-11-07 01:10:09','2013-11-07 01:10:09',NULL,NULL,40,'AC_COMPANY_CREATE','Third party created','2013-11-07 01:10:09','2013-11-07 00:10:09',1,NULL,NULL,31,NULL,0,NULL,0,1,0,0,1,-1,'',NULL,NULL,'Third party created\nAuthor: admin',31,'societe'),(218,NULL,1,'2013-11-07 01:15:57','2013-11-07 01:15:57',NULL,NULL,40,'AC_COMPANY_CREATE','Third party created','2013-11-07 01:15:57','2013-11-07 00:15:57',1,NULL,NULL,32,NULL,0,NULL,0,1,0,0,1,-1,'',NULL,NULL,'Third party created\nAuthor: admin',32,'societe'),(219,NULL,1,'2013-11-07 01:16:51','2013-11-07 01:16:51',NULL,NULL,40,'AC_COMPANY_CREATE','Third party created','2013-11-07 01:16:51','2013-11-07 00:16:51',1,NULL,NULL,33,NULL,0,NULL,0,1,0,0,1,-1,'',NULL,NULL,'Third party created\nAuthor: admin',33,'societe');
/*!40000 ALTER TABLE `llx_actioncomm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_actioncomm_extrafields`
--

DROP TABLE IF EXISTS `llx_actioncomm_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_actioncomm_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_actioncomm_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_actioncomm_extrafields`
--

LOCK TABLES `llx_actioncomm_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_actioncomm_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_actioncomm_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_actioncomm_resources`
--

DROP TABLE IF EXISTS `llx_actioncomm_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_actioncomm_resources` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_actioncomm` int(11) NOT NULL,
  `element_type` varchar(50) NOT NULL,
  `fk_element` int(11) NOT NULL,
  `answer_status` varchar(50) DEFAULT NULL,
  `mandatory` smallint(6) DEFAULT NULL,
  `transparent` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_actioncomm_resources_idx1` (`fk_actioncomm`,`element_type`,`fk_element`),
  KEY `idx_actioncomm_resources_fk_element` (`fk_element`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_actioncomm_resources`
--

LOCK TABLES `llx_actioncomm_resources` WRITE;
/*!40000 ALTER TABLE `llx_actioncomm_resources` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_actioncomm_resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent`
--

DROP TABLE IF EXISTS `llx_adherent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_adherent` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(128) DEFAULT NULL,
  `civilite` varchar(6) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `login` varchar(50) DEFAULT NULL,
  `pass` varchar(50) DEFAULT NULL,
  `fk_adherent_type` int(11) NOT NULL,
  `morphy` varchar(3) NOT NULL,
  `societe` varchar(50) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `address` text,
  `zip` varchar(10) DEFAULT NULL,
  `town` varchar(50) DEFAULT NULL,
  `state_id` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `skype` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `phone_perso` varchar(30) DEFAULT NULL,
  `phone_mobile` varchar(30) DEFAULT NULL,
  `birth` date DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  `public` smallint(6) NOT NULL DEFAULT '0',
  `datefin` datetime DEFAULT NULL,
  `note` text,
  `datevalid` datetime DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_mod` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `canvas` varchar(32) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_adherent_login` (`login`,`entity`),
  UNIQUE KEY `uk_adherent_fk_soc` (`fk_soc`),
  KEY `idx_adherent_fk_adherent_type` (`fk_adherent_type`),
  CONSTRAINT `adherent_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_adherent_adherent_type` FOREIGN KEY (`fk_adherent_type`) REFERENCES `llx_adherent_type` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_adherent`
--

LOCK TABLES `llx_adherent` WRITE;
/*!40000 ALTER TABLE `llx_adherent` DISABLE KEYS */;
INSERT INTO `llx_adherent` VALUES (1,1,NULL,NULL,'Smith','Vick','vsmith','vsx1n8tf',2,'phy',NULL,10,NULL,NULL,NULL,NULL,'102','vsmith@email.com',NULL,NULL,NULL,NULL,'1960-07-07',NULL,1,0,'2012-07-09 00:00:00',NULL,'2010-07-10 15:12:56','2010-07-08 23:50:00','2013-03-20 13:30:11',1,1,1,NULL,NULL),(2,1,NULL,NULL,'Dupont','Alain','adupont','adupont',2,'phy',NULL,12,NULL,NULL,NULL,NULL,'1','toto@aa.com',NULL,NULL,NULL,NULL,'1972-07-08',NULL,1,1,'2017-07-17 00:00:00',NULL,'2010-07-10 15:03:32','2010-07-10 15:03:09','2013-03-06 15:48:16',1,1,1,NULL,NULL),(3,1,NULL,NULL,'john','doe','john','8bs6gty5',2,'phy',NULL,18,NULL,NULL,NULL,NULL,'1','johndoe@email.com',NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,'2011-07-18 21:28:00','2011-07-18 21:10:09','2012-12-09 19:14:42',1,1,1,NULL,NULL),(4,1,NULL,NULL,'smith','smith','Smith','s6hjp10f',2,'phy',NULL,24,NULL,NULL,NULL,NULL,'11','smith@email.com',NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,'2011-07-18 21:27:52','2011-07-18 21:27:44','2013-03-06 16:13:59',1,1,1,NULL,NULL);
/*!40000 ALTER TABLE `llx_adherent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent_extrafields`
--

DROP TABLE IF EXISTS `llx_adherent_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_adherent_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `zzz` varchar(125) DEFAULT NULL,
  `aaa` varchar(255) DEFAULT NULL,
  `sssss` varchar(255) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_adherent_options` (`fk_object`),
  KEY `idx_adherent_extrafields` (`fk_object`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_adherent_extrafields`
--

LOCK TABLES `llx_adherent_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_adherent_extrafields` DISABLE KEYS */;
INSERT INTO `llx_adherent_extrafields` VALUES (2,'2011-06-19 12:03:23',12,'aaa',NULL,NULL,NULL),(3,'2011-06-19 14:19:32',13,NULL,NULL,NULL,NULL),(8,'2011-06-19 18:08:09',7,'zzz',NULL,NULL,NULL),(34,'2011-06-22 10:06:51',14,'moo',NULL,NULL,NULL),(37,'2011-06-22 10:43:55',16,'z',NULL,NULL,NULL),(40,'2011-06-22 10:55:37',17,NULL,NULL,NULL,NULL),(41,'2011-06-22 10:56:07',18,'l',NULL,NULL,NULL),(43,'2011-06-23 07:40:56',19,NULL,NULL,NULL,NULL),(44,'2011-06-26 18:13:20',20,'gdfgdf',NULL,NULL,NULL),(46,'2011-06-26 19:29:23',22,'gdfgdf',NULL,NULL,NULL),(47,'2011-07-03 16:17:56',23,NULL,NULL,NULL,NULL),(48,'2011-07-03 16:21:05',24,NULL,NULL,NULL,NULL),(49,'2011-07-03 16:30:54',25,NULL,NULL,NULL,NULL),(50,'2011-07-03 16:48:13',26,NULL,NULL,NULL,NULL),(51,'2011-07-03 16:51:36',27,NULL,NULL,NULL,NULL),(52,'2011-07-03 16:53:37',28,NULL,NULL,NULL,NULL),(53,'2011-07-03 16:54:24',29,NULL,NULL,NULL,NULL),(54,'2011-07-05 08:21:35',30,NULL,NULL,NULL,NULL),(55,'2011-07-05 08:26:15',31,NULL,NULL,NULL,NULL),(59,'2011-07-13 11:18:55',46,NULL,NULL,NULL,NULL),(61,'2011-07-13 11:50:36',47,NULL,NULL,NULL,NULL),(62,'2011-07-18 19:10:09',3,NULL,NULL,NULL,NULL),(63,'2011-07-18 19:27:44',4,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_adherent_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent_type`
--

DROP TABLE IF EXISTS `llx_adherent_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_adherent_type` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  `libelle` varchar(50) NOT NULL,
  `cotisation` varchar(3) NOT NULL DEFAULT 'yes',
  `vote` varchar(3) NOT NULL DEFAULT 'yes',
  `note` text,
  `mail_valid` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_adherent_type_libelle` (`libelle`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_adherent_type`
--

LOCK TABLES `llx_adherent_type` WRITE;
/*!40000 ALTER TABLE `llx_adherent_type` DISABLE KEYS */;
INSERT INTO `llx_adherent_type` VALUES (1,1,'2010-07-08 21:41:55',1,'Board members','1','1','','<br />'),(2,1,'2010-07-08 21:41:43',1,'Standard members','1','0','','<br />');
/*!40000 ALTER TABLE `llx_adherent_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent_type_extrafields`
--

DROP TABLE IF EXISTS `llx_adherent_type_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_adherent_type_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_adherent_type_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_adherent_type_extrafields`
--

LOCK TABLES `llx_adherent_type_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_adherent_type_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_adherent_type_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_advanced_extrafields`
--

DROP TABLE IF EXISTS `llx_advanced_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_advanced_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `entity` int(11) NOT NULL DEFAULT '1',
  `elementtype` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `label` varchar(64) NOT NULL,
  `format` varchar(8) NOT NULL,
  `fieldsize` int(11) DEFAULT NULL,
  `maxlength` int(11) DEFAULT NULL,
  `options` varchar(255) DEFAULT NULL,
  `rang` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_advanced_extrafields_name` (`elementtype`,`entity`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_advanced_extrafields`
--

LOCK TABLES `llx_advanced_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_advanced_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_advanced_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_advanced_extrafields_options`
--

DROP TABLE IF EXISTS `llx_advanced_extrafields_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_advanced_extrafields_options` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_extrafields` int(11) NOT NULL,
  `value` varchar(255) NOT NULL,
  `rang` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_advanced_extrafields_options_fk_advanced_extrafields` (`fk_extrafields`),
  CONSTRAINT `fk_advanced_extrafields_options_fk_advanced_extrafields` FOREIGN KEY (`fk_extrafields`) REFERENCES `llx_advanced_extrafields` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_advanced_extrafields_options`
--

LOCK TABLES `llx_advanced_extrafields_options` WRITE;
/*!40000 ALTER TABLE `llx_advanced_extrafields_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_advanced_extrafields_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_advanced_extrafields_values`
--

DROP TABLE IF EXISTS `llx_advanced_extrafields_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_advanced_extrafields_values` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime DEFAULT NULL,
  `datem` datetime DEFAULT NULL,
  `fk_element` int(11) NOT NULL,
  `fk_extrafields` int(11) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `fk_user_create` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_advanced_extrafields_values_fk_advanced_extrafields` (`fk_extrafields`,`entity`),
  CONSTRAINT `fk_advanced_extrafields_values_fk_advanced_extrafields` FOREIGN KEY (`fk_extrafields`) REFERENCES `llx_advanced_extrafields` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_advanced_extrafields_values`
--

LOCK TABLES `llx_advanced_extrafields_values` WRITE;
/*!40000 ALTER TABLE `llx_advanced_extrafields_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_advanced_extrafields_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank`
--

DROP TABLE IF EXISTS `llx_bank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bank` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datev` date DEFAULT NULL,
  `dateo` date DEFAULT NULL,
  `amount` double(24,8) NOT NULL DEFAULT '0.00000000',
  `label` varchar(255) DEFAULT NULL,
  `fk_account` int(11) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_rappro` int(11) DEFAULT NULL,
  `fk_type` varchar(6) DEFAULT NULL,
  `num_releve` varchar(50) DEFAULT NULL,
  `num_chq` varchar(50) DEFAULT NULL,
  `rappro` tinyint(4) DEFAULT '0',
  `note` text,
  `fk_bordereau` int(11) DEFAULT '0',
  `banque` varchar(255) DEFAULT NULL,
  `emetteur` varchar(255) DEFAULT NULL,
  `author` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_bank_datev` (`datev`),
  KEY `idx_bank_dateo` (`dateo`),
  KEY `idx_bank_fk_account` (`fk_account`),
  KEY `idx_bank_rappro` (`rappro`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bank`
--

LOCK TABLES `llx_bank` WRITE;
/*!40000 ALTER TABLE `llx_bank` DISABLE KEYS */;
INSERT INTO `llx_bank` VALUES (1,'2010-07-08 23:56:14','2013-03-07 21:28:51','2010-07-08','2010-07-08',2000.00000000,'(Initial balance)',1,NULL,1,'SOLD','201210',NULL,1,NULL,0,NULL,NULL,NULL),(2,'2010-07-09 00:00:24','2010-07-09 00:00:24','2010-07-09','2010-07-09',500.00000000,'(Initial balance)',2,NULL,NULL,'SOLD',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(3,'2010-07-10 13:33:42','2010-07-10 13:33:42','2010-07-10','2010-07-10',0.00000000,'(Solde initial)',3,NULL,NULL,'SOLD',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(4,'2010-07-10 14:59:41','2010-07-10 14:59:41','2010-07-10','2010-07-10',0.02000000,'(CustomerInvoicePayment)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,'Client salon invidivdu',NULL),(5,'2011-07-18 20:50:24','2011-07-18 20:50:24','2011-07-08','2011-07-08',20.00000000,'(CustomerInvoicePayment)',1,1,NULL,'CB','201107',NULL,1,NULL,0,NULL,NULL,NULL),(6,'2011-07-18 20:50:47','2011-07-18 20:50:47','2011-07-08','2011-07-08',10.00000000,'(CustomerInvoicePayment)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(8,'2011-08-01 03:34:11','2013-03-07 21:28:51','2011-08-01','2011-08-01',5.63000000,'(CustomerInvoicePayment)',1,1,1,'CB','201210',NULL,1,NULL,0,NULL,NULL,NULL),(12,'2011-08-05 23:11:37','2013-03-07 21:33:57','2011-08-05','2011-08-05',-10.00000000,'(SocialContributionPayment)',1,1,1,'VIR','201210',NULL,1,NULL,0,NULL,NULL,NULL),(13,'2011-08-06 20:33:54','2011-08-06 20:33:54','2011-08-06','2011-08-06',5.98000000,'(CustomerInvoicePayment)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(14,'2011-08-08 02:53:40','2011-08-08 02:53:40','2011-08-08','2011-08-08',26.10000000,'(CustomerInvoicePayment)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(15,'2011-08-08 02:55:58','2013-03-07 21:39:20','2011-08-08','2011-08-08',26.96000000,'(CustomerInvoicePayment)',1,1,1,'TIP','201211',NULL,1,NULL,0,NULL,NULL,NULL),(16,'2012-12-09 15:28:44','2012-12-09 14:28:44','2012-12-09','2012-12-09',2.00000000,'(CustomerInvoicePayment)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(17,'2012-12-09 15:28:53','2012-12-09 14:33:07','2012-12-09','2012-12-09',-2.00000000,'(CustomerInvoicePaymentBack)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(18,'2012-12-09 17:35:55','2012-12-09 16:35:55','2012-12-09','2012-12-09',-2.00000000,'(CustomerInvoicePaymentBack)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(19,'2012-12-09 17:37:02','2012-12-09 16:37:02','2012-12-09','2012-12-09',2.00000000,'(CustomerInvoicePayment)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(20,'2012-12-09 18:35:07','2012-12-09 17:35:07','2012-12-09','2012-12-09',-2.00000000,'(CustomerInvoicePaymentBack)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(21,'2012-12-12 18:54:33','2013-03-07 21:28:51','2012-12-12','2012-12-12',1.00000000,'(CustomerInvoicePayment)',1,1,1,'TIP','201210',NULL,1,NULL,0,NULL,NULL,NULL),(22,'2013-03-06 16:48:16','2013-03-06 15:48:16','2013-03-06','2013-03-06',20.00000000,'(SubscriptionPayment)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(23,'2013-03-20 14:30:11','2013-03-20 13:30:11','2013-03-20','2013-03-20',10.00000000,'(SubscriptionPayment)',1,1,NULL,'VIR',NULL,NULL,0,NULL,0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_bank` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank_account`
--

DROP TABLE IF EXISTS `llx_bank_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bank_account` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ref` varchar(12) NOT NULL,
  `label` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `bank` varchar(60) DEFAULT NULL,
  `code_banque` varchar(8) DEFAULT NULL,
  `code_guichet` varchar(6) DEFAULT NULL,
  `number` varchar(255) DEFAULT NULL,
  `cle_rib` varchar(5) DEFAULT NULL,
  `bic` varchar(11) DEFAULT NULL,
  `iban_prefix` varchar(34) DEFAULT NULL,
  `country_iban` varchar(2) DEFAULT NULL,
  `cle_iban` varchar(2) DEFAULT NULL,
  `domiciliation` varchar(255) DEFAULT NULL,
  `state_id` varchar(50) DEFAULT NULL,
  `fk_pays` int(11) NOT NULL,
  `proprio` varchar(60) DEFAULT NULL,
  `owner_address` text,
  `courant` smallint(6) NOT NULL DEFAULT '0',
  `clos` smallint(6) NOT NULL DEFAULT '0',
  `rappro` smallint(6) DEFAULT '1',
  `url` varchar(128) DEFAULT NULL,
  `account_number` varchar(8) DEFAULT NULL,
  `currency_code` varchar(3) NOT NULL,
  `min_allowed` int(11) DEFAULT '0',
  `min_desired` int(11) DEFAULT '0',
  `comment` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_bank_account_label` (`label`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bank_account`
--

LOCK TABLES `llx_bank_account` WRITE;
/*!40000 ALTER TABLE `llx_bank_account` DISABLE KEYS */;
INSERT INTO `llx_bank_account` VALUES (1,'2010-07-08 23:56:14','2013-03-24 14:53:09','SWIBAC','Swiss bank account',1,'gdfgdf','','','gdfgdfgdfg','','','',NULL,NULL,'gd fgdf g\r\ngdfgdfgdf\r\ngfdgdfgdf',NULL,169,'gdfgdfgd','dfgdfgd\r\ngdfgdfgdf\r\ngdfgdfg',1,0,1,NULL,'','EUR',1500,1500,''),(2,'2010-07-09 00:00:24','2013-03-24 14:50:40','SWIBAC2','Swiss bank account 2',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,169,NULL,NULL,1,1,1,NULL,'','EUR',200,400,''),(3,'2010-07-10 13:33:42','2010-07-10 11:33:42','ACCOUNTCASH','Account for cash',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'3',1,NULL,NULL,2,0,1,NULL,'','EUR',0,0,'<br />');
/*!40000 ALTER TABLE `llx_bank_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank_categ`
--

DROP TABLE IF EXISTS `llx_bank_categ`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bank_categ` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bank_categ`
--

LOCK TABLES `llx_bank_categ` WRITE;
/*!40000 ALTER TABLE `llx_bank_categ` DISABLE KEYS */;
INSERT INTO `llx_bank_categ` VALUES (1,'Bank category one',1),(2,'Bank category two',1);
/*!40000 ALTER TABLE `llx_bank_categ` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank_class`
--

DROP TABLE IF EXISTS `llx_bank_class`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bank_class` (
  `lineid` int(11) NOT NULL,
  `fk_categ` int(11) NOT NULL,
  UNIQUE KEY `uk_bank_class_lineid` (`lineid`,`fk_categ`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bank_class`
--

LOCK TABLES `llx_bank_class` WRITE;
/*!40000 ALTER TABLE `llx_bank_class` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_bank_class` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bank_url`
--

DROP TABLE IF EXISTS `llx_bank_url`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bank_url` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_bank` int(11) DEFAULT NULL,
  `url_id` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_bank_url` (`fk_bank`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bank_url`
--

LOCK TABLES `llx_bank_url` WRITE;
/*!40000 ALTER TABLE `llx_bank_url` DISABLE KEYS */;
INSERT INTO `llx_bank_url` VALUES (1,4,1,'/dolibarrnew/compta/paiement/fiche.php?id=','(paiement)','payment'),(2,4,9,'/dolibarrnew/compta/fiche.php?socid=','Client salon invidivdu','company'),(3,5,2,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(4,5,2,'/comm/fiche.php?socid=','Belin SARL','company'),(5,6,3,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(6,6,2,'/comm/fiche.php?socid=','Belin SARL','company'),(9,8,5,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(10,8,7,'/comm/fiche.php?socid=','Generic customer','company'),(17,12,4,'/compta/payment_sc/fiche.php?id=','(paiement)','payment_sc'),(18,12,4,'/compta/charges.php?id=','Assurance Chomage (fff)','sc'),(19,13,6,'/dolibarrnew/compta/paiement/fiche.php?id=','(paiement)','payment'),(20,13,7,'/dolibarrnew/comm/fiche.php?socid=','Generic customer','company'),(21,14,8,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(22,14,2,'/comm/fiche.php?socid=','Belin SARL','company'),(23,15,9,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(24,15,10,'/comm/fiche.php?socid=','Smith Vick','company'),(25,16,17,'/dolibarrnew/compta/paiement/fiche.php?id=','(paiement)','payment'),(26,16,10,'/dolibarrnew/comm/fiche.php?socid=','Smith Vick','company'),(27,17,18,'/dolibarrnew/compta/paiement/fiche.php?id=','(paiement)','payment'),(28,17,10,'/dolibarrnew/comm/fiche.php?socid=','Smith Vick','company'),(29,18,19,'/dolibarrnew/compta/paiement/fiche.php?id=','(paiement)','payment'),(30,18,10,'/dolibarrnew/comm/fiche.php?socid=','Smith Vick','company'),(31,19,20,'/dolibarrnew/compta/paiement/fiche.php?id=','(paiement)','payment'),(32,19,10,'/dolibarrnew/comm/fiche.php?socid=','Smith Vick','company'),(33,20,21,'/dolibarrnew/compta/paiement/fiche.php?id=','(paiement)','payment'),(34,20,10,'/dolibarrnew/comm/fiche.php?socid=','Smith Vick','company'),(35,21,23,'/compta/paiement/fiche.php?id=','(paiement)','payment'),(36,21,1,'/comm/fiche.php?socid=','ABC and Co','company'),(37,22,24,'/dolibarrnew/compta/paiement/fiche.php?id=','(paiement)','payment'),(38,22,12,'/dolibarrnew/comm/fiche.php?socid=','Dupont Alain','company'),(39,23,25,'/dolibarrnew/compta/paiement/fiche.php?id=','(paiement)','payment'),(40,23,10,'/dolibarrnew/comm/fiche.php?socid=','Smith Vick','company');
/*!40000 ALTER TABLE `llx_bank_url` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bookkeeping`
--

DROP TABLE IF EXISTS `llx_bookkeeping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bookkeeping` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `doc_date` date NOT NULL,
  `doc_type` varchar(30) NOT NULL,
  `doc_ref` varchar(30) NOT NULL,
  `fk_doc` int(11) NOT NULL,
  `fk_docdet` int(11) NOT NULL,
  `code_tiers` varchar(24) DEFAULT NULL,
  `numero_compte` varchar(50) DEFAULT NULL,
  `label_compte` varchar(128) NOT NULL,
  `debit` double NOT NULL,
  `credit` double NOT NULL,
  `montant` double NOT NULL,
  `sens` varchar(1) DEFAULT NULL,
  `fk_user_author` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  `code_journal` varchar(10) DEFAULT NULL,
  `piece_num` int(11) NOT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bookkeeping`
--

LOCK TABLES `llx_bookkeeping` WRITE;
/*!40000 ALTER TABLE `llx_bookkeeping` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_bookkeeping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bookmark`
--

DROP TABLE IF EXISTS `llx_bookmark`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bookmark` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_user` int(11) NOT NULL,
  `dateb` datetime DEFAULT NULL,
  `url` varchar(128) NOT NULL,
  `target` varchar(16) DEFAULT NULL,
  `title` varchar(64) DEFAULT NULL,
  `favicon` varchar(24) DEFAULT NULL,
  `position` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_bookmark_url` (`fk_user`,`url`),
  UNIQUE KEY `uk_bookmark_title` (`fk_user`,`title`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bookmark`
--

LOCK TABLES `llx_bookmark` WRITE;
/*!40000 ALTER TABLE `llx_bookmark` DISABLE KEYS */;
INSERT INTO `llx_bookmark` VALUES (1,NULL,0,'2010-07-09 01:29:03','http://wiki.dolibarr.org','1','Online documentation','none',1),(2,NULL,0,'2010-07-09 01:30:15','http://www.dolibarr.org','1','Official portal','none',2),(3,NULL,0,'2010-07-09 01:30:53','http://www.dolistore.com','1','DoliStore','none',10),(4,NULL,0,'2010-07-09 01:31:35','http://asso.dolibarr.org/index.php/Main_Page','1','The foundation','none',0);
/*!40000 ALTER TABLE `llx_bookmark` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_bordereau_cheque`
--

DROP TABLE IF EXISTS `llx_bordereau_cheque`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bordereau_cheque` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime NOT NULL,
  `date_bordereau` date DEFAULT NULL,
  `number` varchar(16) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `amount` double(24,8) NOT NULL,
  `nbcheque` smallint(6) NOT NULL,
  `fk_bank_account` int(11) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `note` text,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  `ref_ext` varchar(255) DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_bordereau_cheque` (`number`,`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bordereau_cheque`
--

LOCK TABLES `llx_bordereau_cheque` WRITE;
/*!40000 ALTER TABLE `llx_bordereau_cheque` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_bordereau_cheque` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_boxes`
--

DROP TABLE IF EXISTS `llx_boxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_boxes` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `box_id` int(11) NOT NULL,
  `position` smallint(6) NOT NULL,
  `box_order` varchar(3) NOT NULL,
  `fk_user` int(11) NOT NULL DEFAULT '0',
  `maxline` int(11) DEFAULT NULL,
  `params` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_boxes` (`entity`,`box_id`,`position`,`fk_user`),
  KEY `idx_boxes_boxid` (`box_id`),
  KEY `idx_boxes_fk_user` (`fk_user`),
  CONSTRAINT `fk_boxes_box_id` FOREIGN KEY (`box_id`) REFERENCES `llx_boxes_def` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=594 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_boxes`
--

LOCK TABLES `llx_boxes` WRITE;
/*!40000 ALTER TABLE `llx_boxes` DISABLE KEYS */;
INSERT INTO `llx_boxes` VALUES (12,1,20,0,'A09',0,NULL,NULL),(73,1,21,0,'B16',0,NULL,NULL),(153,1,288,0,'A21',0,NULL,NULL),(154,1,289,0,'A07',0,NULL,NULL),(155,1,290,0,'A17',0,NULL,NULL),(160,1,295,0,'A13',0,NULL,NULL),(161,1,296,0,'A15',0,NULL,NULL),(162,1,297,0,'B12',0,NULL,NULL),(163,1,298,0,'B20',0,NULL,NULL),(164,1,299,0,'B10',0,NULL,NULL),(177,1,309,0,'B18',0,NULL,NULL),(240,1,316,0,'A05',0,NULL,NULL),(241,1,317,0,'B04',0,NULL,NULL),(253,2,323,0,'0',0,NULL,NULL),(304,2,324,0,'0',0,NULL,NULL),(305,2,325,0,'0',0,NULL,NULL),(306,2,326,0,'0',0,NULL,NULL),(307,2,327,0,'0',0,NULL,NULL),(308,2,328,0,'0',0,NULL,NULL),(309,2,329,0,'0',0,NULL,NULL),(310,2,330,0,'0',0,NULL,NULL),(311,2,331,0,'0',0,NULL,NULL),(312,2,332,0,'0',0,NULL,NULL),(313,2,333,0,'0',0,NULL,NULL),(372,1,334,0,'0',0,NULL,NULL),(373,1,335,0,'0',0,NULL,NULL),(374,1,336,0,'0',0,NULL,NULL),(439,1,337,0,'0',0,NULL,NULL),(440,1,338,0,'0',0,NULL,NULL),(441,1,339,0,'0',0,NULL,NULL),(442,1,340,0,'0',0,NULL,NULL),(521,1,339,0,'A01',1,NULL,NULL),(522,1,317,0,'A02',1,NULL,NULL),(523,1,299,0,'A03',1,NULL,NULL),(524,1,289,0,'A04',1,NULL,NULL),(525,1,340,0,'B01',1,NULL,NULL),(526,1,298,0,'B02',1,NULL,NULL),(527,1,295,0,'B03',1,NULL,NULL),(528,1,290,0,'B04',1,NULL,NULL),(529,1,297,0,'B05',1,NULL,NULL),(530,1,20,0,'B06',1,NULL,NULL),(531,1,341,0,'0',0,NULL,NULL),(532,1,342,0,'0',0,NULL,NULL),(533,1,343,0,'0',0,NULL,NULL),(534,1,344,0,'0',0,NULL,NULL),(535,1,345,0,'0',0,NULL,NULL);
/*!40000 ALTER TABLE `llx_boxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_boxes_def`
--

DROP TABLE IF EXISTS `llx_boxes_def`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_boxes_def` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(200) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `note` varchar(130) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_boxes_def` (`file`,`entity`,`note`)
) ENGINE=InnoDB AUTO_INCREMENT=405 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_boxes_def`
--

LOCK TABLES `llx_boxes_def` WRITE;
/*!40000 ALTER TABLE `llx_boxes_def` DISABLE KEYS */;
INSERT INTO `llx_boxes_def` VALUES (20,'box_actions.php',1,'2010-07-08 11:29:29',NULL),(21,'box_bookmarks.php',1,'2010-07-08 11:30:03',NULL),(188,'box_services_vendus.php',1,'2011-08-05 20:40:27',NULL),(288,'box_clients.php',1,'2012-12-21 17:08:59',NULL),(289,'box_prospect.php',1,'2012-12-21 17:08:59',NULL),(290,'box_contacts.php',1,'2012-12-21 17:08:59',NULL),(295,'box_propales.php',1,'2013-01-02 20:33:16',NULL),(296,'box_contracts.php',1,'2013-01-02 20:33:17',NULL),(297,'box_services_expired.php',1,'2013-01-02 20:33:17',NULL),(298,'box_services_contracts.php',1,'2013-01-02 20:33:17',NULL),(299,'box_commandes.php',1,'2013-01-02 20:33:19',NULL),(309,'box_activity.php',1,'2013-01-16 15:37:16','(WarningUsingThisBoxSlowDown)'),(316,'box_produits.php',1,'2013-01-19 17:16:10',NULL),(317,'box_produits_alerte_stock.php',1,'2013-01-19 17:16:10',NULL),(323,'box_actions.php',2,'2013-03-13 15:29:19',NULL),(324,'box_clients.php',2,'2013-03-13 20:21:35',NULL),(325,'box_prospect.php',2,'2013-03-13 20:21:35',NULL),(326,'box_contacts.php',2,'2013-03-13 20:21:35',NULL),(327,'box_activity.php',2,'2013-03-13 20:21:35','(WarningUsingThisBoxSlowDown)'),(328,'box_propales.php',2,'2013-03-13 20:32:38',NULL),(329,'box_comptes.php',2,'2013-03-13 20:33:09',NULL),(330,'box_factures_imp.php',2,'2013-03-13 20:33:09',NULL),(331,'box_factures.php',2,'2013-03-13 20:33:09',NULL),(332,'box_produits.php',2,'2013-03-13 20:33:09',NULL),(333,'box_produits_alerte_stock.php',2,'2013-03-13 20:33:09',NULL),(334,'box_factures_imp.php',1,'2013-03-20 20:04:28',NULL),(335,'box_factures.php',1,'2013-03-20 20:04:28',NULL),(336,'box_comptes.php',1,'2013-03-20 20:04:28',NULL),(337,'box_fournisseurs.php',1,'2013-03-22 09:24:29',NULL),(338,'box_factures_fourn_imp.php',1,'2013-03-22 09:24:29',NULL),(339,'box_factures_fourn.php',1,'2013-03-22 09:24:29',NULL),(340,'box_supplier_orders.php',1,'2013-03-22 09:24:29',NULL),(341,'box_graph_product_distribution.php',1,'2013-11-06 23:35:12',NULL),(342,'box_graph_orders_permonth.php',1,'2013-11-06 23:35:12',NULL),(343,'box_graph_invoices_permonth.php',1,'2013-11-06 23:35:12',NULL),(344,'box_graph_invoices_supplier_permonth.php',1,'2013-11-06 23:35:12',NULL),(345,'box_graph_orders_supplier_permonth.php',1,'2013-11-06 23:35:12',NULL),(346,'box_googlemaps@google',1,'2013-11-07 00:01:39',NULL);
/*!40000 ALTER TABLE `llx_boxes_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_action_trigger`
--

DROP TABLE IF EXISTS `llx_c_action_trigger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_action_trigger` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `label` varchar(128) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `elementtype` varchar(16) NOT NULL,
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_action_trigger_code` (`code`),
  KEY `idx_action_trigger_rang` (`rang`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_action_trigger`
--

LOCK TABLES `llx_c_action_trigger` WRITE;
/*!40000 ALTER TABLE `llx_c_action_trigger` DISABLE KEYS */;
INSERT INTO `llx_c_action_trigger` VALUES (1,'FICHINTER_VALIDATE','Validation fiche intervention','Executed when a intervention is validated','ficheinter',18),(2,'BILL_VALIDATE','Validation facture client','Executed when a customer invoice is approved','facture',6),(3,'ORDER_SUPPLIER_APPROVE','Approbation commande fournisseur','Executed when a supplier order is approved','order_supplier',11),(4,'ORDER_SUPPLIER_REFUSE','Refus commande fournisseur','Executed when a supplier order is refused','order_supplier',12),(5,'ORDER_VALIDATE','Validation commande client','Executed when a customer order is validated','commande',4),(6,'PROPAL_VALIDATE','Validation proposition client','Executed when a commercial proposal is validated','propal',2),(9,'COMPANY_SENTBYMAIL','Mails sent from third party card','Executed when you send email from third party card','societe',1),(10,'COMPANY_CREATE','Third party created','Executed when a third party is created','societe',1),(11,'CONTRACT_VALIDATE','Contract validated','Executed when a contract is validated','contrat',17),(12,'PROPAL_SENTBYMAIL','Commercial proposal sent by mail','Executed when a commercial proposal is sent by mail','propal',3),(13,'ORDER_SENTBYMAIL','Customer order sent by mail','Executed when a customer order is sent by mail ','commande',5),(14,'BILL_PAYED','Customer invoice payed','Executed when a customer invoice is payed','facture',7),(15,'BILL_CANCEL','Customer invoice canceled','Executed when a customer invoice is conceled','facture',8),(16,'BILL_SENTBYMAIL','Customer invoice sent by mail','Executed when a customer invoice is sent by mail','facture',9),(17,'ORDER_SUPPLIER_VALIDATE','Supplier order validated','Executed when a supplier order is validated','order_supplier',10),(18,'ORDER_SUPPLIER_SENTBYMAIL','Supplier order sent by mail','Executed when a supplier order is sent by mail','order_supplier',13),(19,'BILL_SUPPLIER_VALIDATE','Supplier invoice validated','Executed when a supplier invoice is validated','invoice_supplier',14),(20,'BILL_SUPPLIER_PAYED','Supplier invoice payed','Executed when a supplier invoice is payed','invoice_supplier',15),(21,'BILL_SUPPLIER_SENTBYMAIL','Supplier invoice sent by mail','Executed when a supplier invoice is sent by mail','invoice_supplier',16),(22,'SHIPPING_VALIDATE','Shipping validated','Executed when a shipping is validated','shipping',19),(23,'SHIPPING_SENTBYMAIL','Shipping sent by mail','Executed when a shipping is sent by mail','shipping',20),(24,'MEMBER_VALIDATE','Member validated','Executed when a member is validated','member',21),(25,'MEMBER_SUBSCRIPTION','Member subscribed','Executed when a member is subscribed','member',22),(26,'MEMBER_RESILIATE','Member resiliated','Executed when a member is resiliated','member',23),(27,'MEMBER_DELETE','Member deleted','Executed when a member is deleted','member',24),(28,'BILL_UNVALIDATE','Customer invoice unvalidated','Executed when a customer invoice status set back to draft','facture',10),(29,'FICHINTER_SENTBYMAIL','Intervention sent by mail','Executed when a intervention is sent by mail','ficheinter',29),(30,'PROJECT_CREATE','Project creation','Executed when a project is created','project',30);
/*!40000 ALTER TABLE `llx_c_action_trigger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_actioncomm`
--

DROP TABLE IF EXISTS `llx_c_actioncomm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_actioncomm` (
  `id` int(11) NOT NULL,
  `code` varchar(12) NOT NULL,
  `type` varchar(10) NOT NULL DEFAULT 'system',
  `libelle` varchar(48) NOT NULL,
  `module` varchar(16) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `todo` tinyint(4) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_c_actioncomm` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_actioncomm`
--

LOCK TABLES `llx_c_actioncomm` WRITE;
/*!40000 ALTER TABLE `llx_c_actioncomm` DISABLE KEYS */;
INSERT INTO `llx_c_actioncomm` VALUES (1,'AC_TEL','system','Phone call',NULL,1,NULL,2),(2,'AC_FAX','system','Send Fax',NULL,1,NULL,3),(3,'AC_PROP','systemauto','Send commercial proposal by email','propal',0,NULL,10),(4,'AC_EMAIL','system','Send Email',NULL,1,NULL,4),(5,'AC_RDV','system','Rendez-vous',NULL,1,NULL,1),(8,'AC_COM','systemauto','Send customer order by email','order',0,NULL,8),(9,'AC_FAC','systemauto','Send customer invoice by email','invoice',0,NULL,6),(10,'AC_SHIP','systemauto','Send shipping by email','shipping',0,NULL,11),(30,'AC_SUP_ORD','systemauto','Send supplier order by email','order_supplier',0,NULL,9),(31,'AC_SUP_INV','systemauto','Send supplier invoice by email','invoice_supplier',0,NULL,7),(40,'AC_OTH_AUTO','systemauto','Other (automatically inserted events)',NULL,1,NULL,20),(50,'AC_OTH','system','Other (manually inserted events)',NULL,1,NULL,5),(100700,'AC_CABMED','module','Send document by email','cabinetmed',0,NULL,100);
/*!40000 ALTER TABLE `llx_c_actioncomm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_availability`
--

DROP TABLE IF EXISTS `llx_c_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_availability` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `label` varchar(60) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_availability` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_availability`
--

LOCK TABLES `llx_c_availability` WRITE;
/*!40000 ALTER TABLE `llx_c_availability` DISABLE KEYS */;
INSERT INTO `llx_c_availability` VALUES (1,'AV_NOW','Immediate',1),(2,'AV_1W','1 week',1),(3,'AV_2W','2 weeks',1),(4,'AV_3W','3 weeks',1);
/*!40000 ALTER TABLE `llx_c_availability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_barcode_type`
--

DROP TABLE IF EXISTS `llx_c_barcode_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_barcode_type` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(16) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `libelle` varchar(50) NOT NULL,
  `coder` varchar(16) NOT NULL,
  `example` varchar(16) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_barcode_type` (`code`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_barcode_type`
--

LOCK TABLES `llx_c_barcode_type` WRITE;
/*!40000 ALTER TABLE `llx_c_barcode_type` DISABLE KEYS */;
INSERT INTO `llx_c_barcode_type` VALUES (1,'EAN8',1,'EAN8','0','1234567'),(2,'EAN13',1,'EAN13','0','123456789012'),(3,'UPC',1,'UPC','0','123456789012'),(4,'ISBN',1,'ISBN','0','123456789'),(5,'C39',1,'Code 39','0','1234567890'),(6,'C128',1,'Code 128','0','ABCD1234567890');
/*!40000 ALTER TABLE `llx_c_barcode_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_chargesociales`
--

DROP TABLE IF EXISTS `llx_c_chargesociales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_chargesociales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(80) DEFAULT NULL,
  `deductible` smallint(6) NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `code` varchar(12) NOT NULL,
  `fk_pays` int(11) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  `accountancy_code` varchar(24) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=231 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_chargesociales`
--

LOCK TABLES `llx_c_chargesociales` WRITE;
/*!40000 ALTER TABLE `llx_c_chargesociales` DISABLE KEYS */;
INSERT INTO `llx_c_chargesociales` VALUES (1,'Allocations familiales',1,1,'TAXFAM',1,NULL,NULL),(2,'CSG Deductible',1,1,'TAXCSGD',1,NULL,NULL),(3,'CSG/CRDS NON Deductible',0,1,'TAXCSGND',1,NULL,NULL),(10,'Taxe apprentissage',0,1,'TAXAPP',1,NULL,NULL),(11,'Taxe professionnelle',0,1,'TAXPRO',1,NULL,NULL),(12,'Cotisation foncière des entreprises',0,1,'TAXCFE',1,NULL,NULL),(13,'Cotisation sur la valeur ajoutée des entreprises',0,1,'TAXCVAE',1,NULL,NULL),(20,'Impots locaux/fonciers',0,1,'TAXFON',1,NULL,NULL),(25,'Impots revenus',0,1,'TAXREV',1,NULL,NULL),(30,'Assurance Sante',0,1,'TAXSECU',1,NULL,NULL),(40,'Mutuelle',0,1,'TAXMUT',1,NULL,NULL),(50,'Assurance vieillesse',0,1,'TAXRET',1,NULL,NULL),(60,'Assurance Chomage',0,1,'TAXCHOM',1,NULL,NULL),(201,'ONSS',1,1,'TAXBEONSS',2,NULL,NULL),(210,'Precompte professionnel',1,1,'TAXBEPREPRO',2,NULL,NULL),(220,'Prime d\'existence',1,1,'TAXBEPRIEXI',2,NULL,NULL),(230,'Precompte immobilier',1,1,'TAXBEPREIMMO',2,NULL,NULL);
/*!40000 ALTER TABLE `llx_c_chargesociales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_civilite`
--

DROP TABLE IF EXISTS `llx_c_civilite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_civilite` (
  `rowid` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `civilite` varchar(50) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_civilite` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_civilite`
--

LOCK TABLES `llx_c_civilite` WRITE;
/*!40000 ALTER TABLE `llx_c_civilite` DISABLE KEYS */;
INSERT INTO `llx_c_civilite` VALUES (1,'MME','Madame',1,NULL),(3,'MR','Monsieur',1,NULL),(5,'MLE','Mademoiselle',1,NULL),(7,'MTRE','Maître',1,NULL),(8,'DR','Docteur',1,NULL);
/*!40000 ALTER TABLE `llx_c_civilite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_currencies`
--

DROP TABLE IF EXISTS `llx_c_currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_currencies` (
  `code_iso` varchar(3) NOT NULL,
  `label` varchar(64) NOT NULL,
  `unicode` varchar(32) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`code_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_currencies`
--

LOCK TABLES `llx_c_currencies` WRITE;
/*!40000 ALTER TABLE `llx_c_currencies` DISABLE KEYS */;
INSERT INTO `llx_c_currencies` VALUES ('AED','United Arab Emirates Dirham',NULL,1),('AFN','Afghanistan Afghani','[1547]',1),('ALL','Albania Leklll','[76,101,107]',1),('ANG','Netherlands Antilles Guilder','[402]',1),('ARP','Pesos argentins',NULL,0),('ARS','Argentino Peso','[36]',1),('ATS','Shiliing autrichiens',NULL,0),('AUD','Australia Dollar','[36]',1),('AWG','Aruba Guilder','[402]',1),('AZN','Azerbaijan New Manat','[1084,1072,1085]',1),('BAM','Bosnia and Herzegovina Convertible Marka','[75,77]',1),('BBD','Barbados Dollar','[36]',1),('BEF','Francs belges',NULL,0),('BGN','Bulgaria Lev','[1083,1074]',1),('BMD','Bermuda Dollar','[36]',1),('BND','Brunei Darussalam Dollar','[36]',1),('BOB','Bolivia Boliviano','[36,98]',1),('BRL','Brazil Real','[82,36]',1),('BSD','Bahamas Dollar','[36]',1),('BWP','Botswana Pula','[80]',1),('BYR','Belarus Ruble','[112,46]',1),('BZD','Belize Dollar','[66,90,36]',1),('CAD','Canada Dollar','[36]',1),('CHF','Switzerland Franc','[67,72,70]',1),('CLP','Chile Peso','[36]',1),('CNY','China Yuan Renminbi','[165]',1),('COP','Colombia Peso','[36]',1),('CRC','Costa Rica Colon','[8353]',1),('CUP','Cuba Peso','[8369]',1),('CZK','Czech Republic Koruna','[75,269]',1),('DEM','Deutsch mark',NULL,0),('DKK','Denmark Krone','[107,114]',1),('DOP','Dominican Republic Peso','[82,68,36]',1),('DZD','Algeria Dinar',NULL,1),('EEK','Estonia Kroon','[107,114]',1),('EGP','Egypt Pound','[163]',1),('ESP','Pesete',NULL,0),('EUR','Euro Member Countries','[8364]',1),('FIM','Mark finlandais',NULL,0),('FJD','Fiji Dollar','[36]',1),('FKP','Falkland Islands (Malvinas) Pound','[163]',1),('FRF','Francs francais',NULL,0),('GBP','United Kingdom Pound','[163]',1),('GGP','Guernsey Pound','[163]',1),('GHC','Ghana Cedis','[162]',1),('GIP','Gibraltar Pound','[163]',1),('GRD','Drachme (grece)',NULL,0),('GTQ','Guatemala Quetzal','[81]',1),('GYD','Guyana Dollar','[36]',1),('hhh','ddd','[]',1),('HKD','Hong Kong Dollar','[36]',1),('HNL','Honduras Lempira','[76]',1),('HRK','Croatia Kuna','[107,110]',1),('HUF','Hungary Forint','[70,116]',1),('IDR','Indonesia Rupiah','[82,112]',1),('IEP','Livres irlandaises',NULL,0),('ILS','Israel Shekel','[8362]',1),('IMP','Isle of Man Pound','[163]',1),('INR','India Rupee',NULL,1),('IRR','Iran Rial','[65020]',1),('ISK','Iceland Krona','[107,114]',1),('ITL','Lires',NULL,0),('JEP','Jersey Pound','[163]',1),('JMD','Jamaica Dollar','[74,36]',1),('JPY','Japan Yen','[165]',1),('KGS','Kyrgyzstan Som','[1083,1074]',1),('KHR','Cambodia Riel','[6107]',1),('KPW','Korea (North) Won','[8361]',1),('KRW','Korea (South) Won','[8361]',1),('KYD','Cayman Islands Dollar','[36]',1),('KZT','Kazakhstan Tenge','[1083,1074]',1),('LAK','Laos Kip','[8365]',1),('LBP','Lebanon Pound','[163]',1),('LKR','Sri Lanka Rupee','[8360]',1),('LRD','Liberia Dollar','[36]',1),('LTL','Lithuania Litas','[76,116]',1),('LUF','Francs luxembourgeois',NULL,0),('LVL','Latvia Lat','[76,115]',1),('MAD','Morocco Dirham',NULL,1),('MKD','Macedonia Denar','[1076,1077,1085]',1),('MNT','Mongolia Tughrik','[8366]',1),('MRO','Mauritania Ouguiya',NULL,1),('MUR','Mauritius Rupee','[8360]',1),('MXN','Mexico Peso','[36]',1),('MXP','Pesos Mexicans',NULL,0),('MYR','Malaysia Ringgit','[82,77]',1),('MZN','Mozambique Metical','[77,84]',1),('NAD','Namibia Dollar','[36]',1),('NGN','Nigeria Naira','[8358]',1),('NIO','Nicaragua Cordoba','[67,36]',1),('NLG','Florins',NULL,0),('NOK','Norway Krone','[107,114]',1),('NPR','Nepal Rupee','[8360]',1),('NZD','New Zealand Dollar','[36]',1),('OMR','Oman Rial','[65020]',1),('PAB','Panama Balboa','[66,47,46]',1),('PEN','Peru Nuevo Sol','[83,47,46]',1),('PHP','Philippines Peso','[8369]',1),('PKR','Pakistan Rupee','[8360]',1),('PLN','Poland Zloty','[122,322]',1),('PTE','Escudos',NULL,0),('PYG','Paraguay Guarani','[71,115]',1),('QAR','Qatar Riyal','[65020]',1),('RON','Romania New Leu','[108,101,105]',1),('RSD','Serbia Dinar','[1044,1080,1085,46]',1),('RUB','Russia Ruble','[1088,1091,1073]',1),('SAR','Saudi Arabia Riyal','[65020]',1),('SBD','Solomon Islands Dollar','[36]',1),('SCR','Seychelles Rupee','[8360]',1),('SEK','Sweden Krona','[107,114]',1),('SGD','Singapore Dollar','[36]',1),('SHP','Saint Helena Pound','[163]',1),('SKK','Couronnes slovaques',NULL,0),('SOS','Somalia Shilling','[83]',1),('SRD','Suriname Dollar','[36]',1),('SUR','Rouble',NULL,0),('SVC','El Salvador Colon','[36]',1),('SYP','Syria Pound','[163]',1),('THB','Thailand Baht','[3647]',1),('TND','Tunisia Dinar',NULL,1),('TRL','Turkey Lira','[84,76]',1),('TRY','Turkey Lira','[8356]',1),('TTD','Trinidad and Tobago Dollar','[84,84,36]',1),('TVD','Tuvalu Dollar','[36]',1),('TWD','Taiwan New Dollar','[78,84,36]',1),('UAH','Ukraine Hryvna','[8372]',1),('USD','United States Dollar','[36]',1),('UYU','Uruguay Peso','[36,85]',1),('UZS','Uzbekistan Som','[1083,1074]',1),('VEF','Venezuela Bolivar Fuerte','[66,115]',1),('VND','Viet Nam Dong','[8363]',1),('XAF','Communaute Financiere Africaine (BEAC) CFA Franc',NULL,1),('XCD','East Caribbean Dollar','[36]',1),('XEU','Ecus',NULL,0),('XOF','Communaute Financiere Africaine (BCEAO) Franc',NULL,1),('YER','Yemen Rial','[65020]',1),('ZAR','South Africa Rand','[82]',1),('ZWD','Zimbabwe Dollar','[90,36]',1);
/*!40000 ALTER TABLE `llx_c_currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_departements`
--

DROP TABLE IF EXISTS `llx_c_departements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_departements` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code_departement` varchar(6) NOT NULL,
  `fk_region` int(11) DEFAULT NULL,
  `cheflieu` varchar(50) DEFAULT NULL,
  `tncc` int(11) DEFAULT NULL,
  `ncc` varchar(50) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_departements` (`code_departement`,`fk_region`),
  KEY `idx_departements_fk_region` (`fk_region`)
) ENGINE=InnoDB AUTO_INCREMENT=2052 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_departements`
--

LOCK TABLES `llx_c_departements` WRITE;
/*!40000 ALTER TABLE `llx_c_departements` DISABLE KEYS */;
INSERT INTO `llx_c_departements` VALUES (1,'0',0,'0',0,'-','-',1),(2,'01',82,'01053',5,'AIN','Ain',1),(3,'02',22,'02408',5,'AISNE','Aisne',1),(4,'03',83,'03190',5,'ALLIER','Allier',1),(5,'04',93,'04070',4,'ALPES-DE-HAUTE-PROVENCE','Alpes-de-Haute-Provence',1),(6,'05',93,'05061',4,'HAUTES-ALPES','Hautes-Alpes',1),(7,'06',93,'06088',4,'ALPES-MARITIMES','Alpes-Maritimes',1),(8,'07',82,'07186',5,'ARDECHE','Ardèche',1),(9,'08',21,'08105',4,'ARDENNES','Ardennes',1),(10,'09',73,'09122',5,'ARIEGE','Ariège',1),(11,'10',21,'10387',5,'AUBE','Aube',1),(12,'11',91,'11069',5,'AUDE','Aude',1),(13,'12',73,'12202',5,'AVEYRON','Aveyron',1),(14,'13',93,'13055',4,'BOUCHES-DU-RHONE','Bouches-du-Rhône',1),(15,'14',25,'14118',2,'CALVADOS','Calvados',1),(16,'15',83,'15014',2,'CANTAL','Cantal',1),(17,'16',54,'16015',3,'CHARENTE','Charente',1),(18,'17',54,'17300',3,'CHARENTE-MARITIME','Charente-Maritime',1),(19,'18',24,'18033',2,'CHER','Cher',1),(20,'19',74,'19272',3,'CORREZE','Corrèze',1),(21,'2A',94,'2A004',3,'CORSE-DU-SUD','Corse-du-Sud',1),(22,'2B',94,'2B033',3,'HAUTE-CORSE','Haute-Corse',1),(23,'21',26,'21231',3,'COTE-D\'OR','Côte-d\'Or',1),(24,'22',53,'22278',4,'COTES-D\'ARMOR','Côtes-d\'Armor',1),(25,'23',74,'23096',3,'CREUSE','Creuse',1),(26,'24',72,'24322',3,'DORDOGNE','Dordogne',1),(27,'25',43,'25056',2,'DOUBS','Doubs',1),(28,'26',82,'26362',3,'DROME','Drôme',1),(29,'27',23,'27229',5,'EURE','Eure',1),(30,'28',24,'28085',1,'EURE-ET-LOIR','Eure-et-Loir',1),(31,'29',53,'29232',2,'FINISTERE','Finistère',1),(32,'30',91,'30189',2,'GARD','Gard',1),(33,'31',73,'31555',3,'HAUTE-GARONNE','Haute-Garonne',1),(34,'32',73,'32013',2,'GERS','Gers',1),(35,'33',72,'33063',3,'GIRONDE','Gironde',1),(36,'34',91,'34172',5,'HERAULT','Hérault',1),(37,'35',53,'35238',1,'ILLE-ET-VILAINE','Ille-et-Vilaine',1),(38,'36',24,'36044',5,'INDRE','Indre',1),(39,'37',24,'37261',1,'INDRE-ET-LOIRE','Indre-et-Loire',1),(40,'38',82,'38185',5,'ISERE','Isère',1),(41,'39',43,'39300',2,'JURA','Jura',1),(42,'40',72,'40192',4,'LANDES','Landes',1),(43,'41',24,'41018',0,'LOIR-ET-CHER','Loir-et-Cher',1),(44,'42',82,'42218',3,'LOIRE','Loire',1),(45,'43',83,'43157',3,'HAUTE-LOIRE','Haute-Loire',1),(46,'44',52,'44109',3,'LOIRE-ATLANTIQUE','Loire-Atlantique',1),(47,'45',24,'45234',2,'LOIRET','Loiret',1),(48,'46',73,'46042',2,'LOT','Lot',1),(49,'47',72,'47001',0,'LOT-ET-GARONNE','Lot-et-Garonne',1),(50,'48',91,'48095',3,'LOZERE','Lozère',1),(51,'49',52,'49007',0,'MAINE-ET-LOIRE','Maine-et-Loire',1),(52,'50',25,'50502',3,'MANCHE','Manche',1),(53,'51',21,'51108',3,'MARNE','Marne',1),(54,'52',21,'52121',3,'HAUTE-MARNE','Haute-Marne',1),(55,'53',52,'53130',3,'MAYENNE','Mayenne',1),(56,'54',41,'54395',0,'MEURTHE-ET-MOSELLE','Meurthe-et-Moselle',1),(57,'55',41,'55029',3,'MEUSE','Meuse',1),(58,'56',53,'56260',2,'MORBIHAN','Morbihan',1),(59,'57',41,'57463',3,'MOSELLE','Moselle',1),(60,'58',26,'58194',3,'NIEVRE','Nièvre',1),(61,'59',31,'59350',2,'NORD','Nord',1),(62,'60',22,'60057',5,'OISE','Oise',1),(63,'61',25,'61001',5,'ORNE','Orne',1),(64,'62',31,'62041',2,'PAS-DE-CALAIS','Pas-de-Calais',1),(65,'63',83,'63113',2,'PUY-DE-DOME','Puy-de-Dôme',1),(66,'64',72,'64445',4,'PYRENEES-ATLANTIQUES','Pyrénées-Atlantiques',1),(67,'65',73,'65440',4,'HAUTES-PYRENEES','Hautes-Pyrénées',1),(68,'66',91,'66136',4,'PYRENEES-ORIENTALES','Pyrénées-Orientales',1),(69,'67',42,'67482',2,'BAS-RHIN','Bas-Rhin',1),(70,'68',42,'68066',2,'HAUT-RHIN','Haut-Rhin',1),(71,'69',82,'69123',2,'RHONE','Rhône',1),(72,'70',43,'70550',3,'HAUTE-SAONE','Haute-Saône',1),(73,'71',26,'71270',0,'SAONE-ET-LOIRE','Saône-et-Loire',1),(74,'72',52,'72181',3,'SARTHE','Sarthe',1),(75,'73',82,'73065',3,'SAVOIE','Savoie',1),(76,'74',82,'74010',3,'HAUTE-SAVOIE','Haute-Savoie',1),(77,'75',11,'75056',0,'PARIS','Paris',1),(78,'76',23,'76540',3,'SEINE-MARITIME','Seine-Maritime',1),(79,'77',11,'77288',0,'SEINE-ET-MARNE','Seine-et-Marne',1),(80,'78',11,'78646',4,'YVELINES','Yvelines',1),(81,'79',54,'79191',4,'DEUX-SEVRES','Deux-Sèvres',1),(82,'80',22,'80021',3,'SOMME','Somme',1),(83,'81',73,'81004',2,'TARN','Tarn',1),(84,'82',73,'82121',0,'TARN-ET-GARONNE','Tarn-et-Garonne',1),(85,'83',93,'83137',2,'VAR','Var',1),(86,'84',93,'84007',0,'VAUCLUSE','Vaucluse',1),(87,'85',52,'85191',3,'VENDEE','Vendée',1),(88,'86',54,'86194',3,'VIENNE','Vienne',1),(89,'87',74,'87085',3,'HAUTE-VIENNE','Haute-Vienne',1),(90,'88',41,'88160',4,'VOSGES','Vosges',1),(91,'89',26,'89024',5,'YONNE','Yonne',1),(92,'90',43,'90010',0,'TERRITOIRE DE BELFORT','Territoire de Belfort',1),(93,'91',11,'91228',5,'ESSONNE','Essonne',1),(94,'92',11,'92050',4,'HAUTS-DE-SEINE','Hauts-de-Seine',1),(95,'93',11,'93008',3,'SEINE-SAINT-DENIS','Seine-Saint-Denis',1),(96,'94',11,'94028',2,'VAL-DE-MARNE','Val-de-Marne',1),(97,'95',11,'95500',2,'VAL-D\'OISE','Val-d\'Oise',1),(98,'971',1,'97105',3,'GUADELOUPE','Guadeloupe',1),(99,'972',2,'97209',3,'MARTINIQUE','Martinique',1),(100,'973',3,'97302',3,'GUYANE','Guyane',1),(101,'974',4,'97411',3,'REUNION','Réunion',1),(102,'01',201,'',1,'ANVERS','Anvers',1),(103,'02',203,'',3,'BRUXELLES-CAPITALE','Bruxelles-Capitale',1),(104,'03',202,'',2,'BRABANT-WALLON','Brabant-Wallon',1),(105,'04',201,'',1,'BRABANT-FLAMAND','Brabant-Flamand',1),(106,'05',201,'',1,'FLANDRE-OCCIDENTALE','Flandre-Occidentale',1),(107,'06',201,'',1,'FLANDRE-ORIENTALE','Flandre-Orientale',1),(108,'07',202,'',2,'HAINAUT','Hainaut',1),(109,'08',201,'',2,'LIEGE','Liège',1),(110,'09',202,'',1,'LIMBOURG','Limbourg',1),(111,'10',202,'',2,'LUXEMBOURG','Luxembourg',1),(112,'11',201,'',2,'NAMUR','Namur',1),(113,'NSW',2801,'',1,'','New South Wales',1),(114,'VIC',2801,'',1,'','Victoria',1),(115,'QLD',2801,'',1,'','Queensland',1),(116,'SA',2801,'',1,'','South Australia',1),(117,'ACT',2801,'',1,'','Australia Capital Territory',1),(118,'TAS',2801,'',1,'','Tasmania',1),(119,'WA',2801,'',1,'','Western Australia',1),(120,'NT',2801,'',1,'','Northern Territory',1),(121,'01',419,'',19,'ALAVA','Álava',1),(122,'02',404,'',4,'ALBACETE','Albacete',1),(123,'03',411,'',11,'ALICANTE','Alicante',1),(124,'04',401,'',1,'ALMERIA','Almería',1),(125,'05',403,'',3,'AVILA','Avila',1),(126,'06',412,'',12,'BADAJOZ','Badajoz',1),(127,'07',414,'',14,'ISLAS BALEARES','Islas Baleares',1),(128,'08',406,'',6,'BARCELONA','Barcelona',1),(129,'09',403,'',8,'BURGOS','Burgos',1),(130,'10',412,'',12,'CACERES','Cáceres',1),(131,'11',401,'',1,'CADIz','Cádiz',1),(132,'12',411,'',11,'CASTELLON','Castellón',1),(133,'13',404,'',4,'CIUDAD REAL','Ciudad Real',1),(134,'14',401,'',1,'CORDOBA','Córdoba',1),(135,'15',413,'',13,'LA CORUÑA','La Coruña',1),(136,'16',404,'',4,'CUENCA','Cuenca',1),(137,'17',406,'',6,'GERONA','Gerona',1),(138,'18',401,'',1,'GRANADA','Granada',1),(139,'19',404,'',4,'GUADALAJARA','Guadalajara',1),(140,'20',419,'',19,'GUIPUZCOA','Guipúzcoa',1),(141,'21',401,'',1,'HUELVA','Huelva',1),(142,'22',402,'',2,'HUESCA','Huesca',1),(143,'23',401,'',1,'JAEN','Jaén',1),(144,'24',403,'',3,'LEON','León',1),(145,'25',406,'',6,'LERIDA','Lérida',1),(146,'26',415,'',15,'LA RIOJA','La Rioja',1),(147,'27',413,'',13,'LUGO','Lugo',1),(148,'28',416,'',16,'MADRID','Madrid',1),(149,'29',401,'',1,'MALAGA','Málaga',1),(150,'30',417,'',17,'MURCIA','Murcia',1),(151,'31',408,'',8,'NAVARRA','Navarra',1),(152,'32',413,'',13,'ORENSE','Orense',1),(153,'33',418,'',18,'ASTURIAS','Asturias',1),(154,'34',403,'',3,'PALENCIA','Palencia',1),(155,'35',405,'',5,'LAS PALMAS','Las Palmas',1),(156,'36',413,'',13,'PONTEVEDRA','Pontevedra',1),(157,'37',403,'',3,'SALAMANCA','Salamanca',1),(158,'38',405,'',5,'STA. CRUZ DE TENERIFE','Sta. Cruz de Tenerife',1),(159,'39',410,'',10,'CANTABRIA','Cantabria',1),(160,'40',403,'',3,'SEGOVIA','Segovia',1),(161,'41',401,'',1,'SEVILLA','Sevilla',1),(162,'42',403,'',3,'SORIA','Soria',1),(163,'43',406,'',6,'TARRAGONA','Tarragona',1),(164,'44',402,'',2,'TERUEL','Teruel',1),(165,'45',404,'',5,'TOLEDO','Toledo',1),(166,'46',411,'',11,'VALENCIA','Valencia',1),(167,'47',403,'',3,'VALLADOLID','Valladolid',1),(168,'48',419,'',19,'VIZCAYA','Vizcaya',1),(169,'49',403,'',3,'ZAMORA','Zamora',1),(170,'50',402,'',1,'ZARAGOZA','Zaragoza',1),(171,'51',407,'',7,'CEUTA','Ceuta',1),(172,'52',409,'',9,'MELILLA','Melilla',1),(173,'53',420,'',20,'OTROS','Otros',1),(174,'AG',601,NULL,NULL,'ARGOVIE','Argovie',1),(175,'AI',601,NULL,NULL,'APPENZELL RHODES INTERIEURES','Appenzell Rhodes intérieures',1),(176,'AR',601,NULL,NULL,'APPENZELL RHODES EXTERIEURES','Appenzell Rhodes extérieures',1),(177,'BE',601,NULL,NULL,'BERNE','Berne',1),(178,'BL',601,NULL,NULL,'BALE CAMPAGNE','Bâle Campagne',1),(179,'BS',601,NULL,NULL,'BALE VILLE','Bâle Ville',1),(180,'FR',601,NULL,NULL,'FRIBOURG','Fribourg',1),(181,'GE',601,NULL,NULL,'GENEVE','Genève',1),(182,'GL',601,NULL,NULL,'GLARIS','Glaris',1),(183,'GR',601,NULL,NULL,'GRISONS','Grisons',1),(184,'JU',601,NULL,NULL,'JURA','Jura',1),(185,'LU',601,NULL,NULL,'LUCERNE','Lucerne',1),(186,'NE',601,NULL,NULL,'NEUCHATEL','Neuchâtel',1),(187,'NW',601,NULL,NULL,'NIDWALD','Nidwald',1),(188,'OW',601,NULL,NULL,'OBWALD','Obwald',1),(189,'SG',601,NULL,NULL,'SAINT-GALL','Saint-Gall',1),(190,'SH',601,NULL,NULL,'SCHAFFHOUSE','Schaffhouse',1),(191,'SO',601,NULL,NULL,'SOLEURE','Soleure',1),(192,'SZ',601,NULL,NULL,'SCHWYZ','Schwyz',1),(193,'TG',601,NULL,NULL,'THURGOVIE','Thurgovie',1),(194,'TI',601,NULL,NULL,'TESSIN','Tessin',1),(195,'UR',601,NULL,NULL,'URI','Uri',1),(196,'VD',601,NULL,NULL,'VAUD','Vaud',1),(197,'VS',601,NULL,NULL,'VALAIS','Valais',1),(198,'ZG',601,NULL,NULL,'ZUG','Zug',1),(199,'ZH',601,NULL,NULL,'ZURICH','Zürich',1),(200,'AL',1101,'',0,'ALABAMA','Alabama',1),(201,'AK',1101,'',0,'ALASKA','Alaska',1),(202,'AZ',1101,'',0,'ARIZONA','Arizona',1),(203,'AR',1101,'',0,'ARKANSAS','Arkansas',1),(204,'CA',1101,'',0,'CALIFORNIA','California',1),(205,'CO',1101,'',0,'COLORADO','Colorado',1),(206,'CT',1101,'',0,'CONNECTICUT','Connecticut',1),(207,'DE',1101,'',0,'DELAWARE','Delaware',1),(208,'FL',1101,'',0,'FLORIDA','Florida',1),(209,'GA',1101,'',0,'GEORGIA','Georgia',1),(210,'HI',1101,'',0,'HAWAII','Hawaii',1),(211,'ID',1101,'',0,'IDAHO','Idaho',1),(212,'IL',1101,'',0,'ILLINOIS','Illinois',1),(213,'IN',1101,'',0,'INDIANA','Indiana',1),(214,'IA',1101,'',0,'IOWA','Iowa',1),(215,'KS',1101,'',0,'KANSAS','Kansas',1),(216,'KY',1101,'',0,'KENTUCKY','Kentucky',1),(217,'LA',1101,'',0,'LOUISIANA','Louisiana',1),(218,'ME',1101,'',0,'MAINE','Maine',1),(219,'MD',1101,'',0,'MARYLAND','Maryland',1),(220,'MA',1101,'',0,'MASSACHUSSETTS','Massachusetts',1),(221,'MI',1101,'',0,'MICHIGAN','Michigan',1),(222,'MN',1101,'',0,'MINNESOTA','Minnesota',1),(223,'MS',1101,'',0,'MISSISSIPPI','Mississippi',1),(224,'MO',1101,'',0,'MISSOURI','Missouri',1),(225,'MT',1101,'',0,'MONTANA','Montana',1),(226,'NE',1101,'',0,'NEBRASKA','Nebraska',1),(227,'NV',1101,'',0,'NEVADA','Nevada',1),(228,'NH',1101,'',0,'NEW HAMPSHIRE','New Hampshire',1),(229,'NJ',1101,'',0,'NEW JERSEY','New Jersey',1),(230,'NM',1101,'',0,'NEW MEXICO','New Mexico',1),(231,'NY',1101,'',0,'NEW YORK','New York',1),(232,'NC',1101,'',0,'NORTH CAROLINA','North Carolina',1),(233,'ND',1101,'',0,'NORTH DAKOTA','North Dakota',1),(234,'OH',1101,'',0,'OHIO','Ohio',1),(235,'OK',1101,'',0,'OKLAHOMA','Oklahoma',1),(236,'OR',1101,'',0,'OREGON','Oregon',1),(237,'PA',1101,'',0,'PENNSYLVANIA','Pennsylvania',1),(238,'RI',1101,'',0,'RHODE ISLAND','Rhode Island',1),(239,'SC',1101,'',0,'SOUTH CAROLINA','South Carolina',1),(240,'SD',1101,'',0,'SOUTH DAKOTA','South Dakota',1),(241,'TN',1101,'',0,'TENNESSEE','Tennessee',1),(242,'TX',1101,'',0,'TEXAS','Texas',1),(243,'UT',1101,'',0,'UTAH','Utah',1),(244,'VT',1101,'',0,'VERMONT','Vermont',1),(245,'VA',1101,'',0,'VIRGINIA','Virginia',1),(246,'WA',1101,'',0,'WASHINGTON','Washington',1),(247,'WV',1101,'',0,'WEST VIRGINIA','West Virginia',1),(248,'WI',1101,'',0,'WISCONSIN','Wisconsin',1),(249,'WY',1101,'',0,'WYOMING','Wyoming',1),(250,'SS',8601,NULL,NULL,NULL,'San Salvador',1),(251,'SA',8603,NULL,NULL,NULL,'Santa Ana',1),(252,'AH',8603,NULL,NULL,NULL,'Ahuachapan',1),(253,'SO',8603,NULL,NULL,NULL,'Sonsonate',1),(254,'US',8602,NULL,NULL,NULL,'Usulutan',1),(255,'SM',8602,NULL,NULL,NULL,'San Miguel',1),(256,'MO',8602,NULL,NULL,NULL,'Morazan',1),(257,'LU',8602,NULL,NULL,NULL,'La Union',1),(258,'LL',8601,NULL,NULL,NULL,'La Libertad',1),(259,'CH',8601,NULL,NULL,NULL,'Chalatenango',1),(260,'CA',8601,NULL,NULL,NULL,'Cabañas',1),(261,'LP',8601,NULL,NULL,NULL,'La Paz',1),(262,'SV',8601,NULL,NULL,NULL,'San Vicente',1),(263,'CU',8601,NULL,NULL,NULL,'Cuscatlan',1),(264,'2301',2301,'',0,'CATAMARCA','Catamarca',1),(265,'2302',2301,'',0,'JUJUY','Jujuy',1),(266,'2303',2301,'',0,'TUCAMAN','Tucamán',1),(267,'2304',2301,'',0,'SANTIAGO DEL ESTERO','Santiago del Estero',1),(268,'2305',2301,'',0,'SALTA','Salta',1),(269,'2306',2302,'',0,'CHACO','Chaco',1),(270,'2307',2302,'',0,'CORRIENTES','Corrientes',1),(271,'2308',2302,'',0,'ENTRE RIOS','Entre Ríos',1),(272,'2309',2302,'',0,'FORMOSA MISIONES','Formosa Misiones',1),(273,'2310',2302,'',0,'SANTA FE','Santa Fe',1),(274,'2311',2303,'',0,'LA RIOJA','La Rioja',1),(275,'2312',2303,'',0,'MENDOZA','Mendoza',1),(276,'2313',2303,'',0,'SAN JUAN','San Juan',1),(277,'2314',2303,'',0,'SAN LUIS','San Luis',1),(278,'2315',2304,'',0,'CORDOBA','Córdoba',1),(279,'2316',2304,'',0,'BUENOS AIRES','Buenos Aires',1),(280,'2317',2304,'',0,'CABA','Caba',1),(281,'2318',2305,'',0,'LA PAMPA','La Pampa',1),(282,'2319',2305,'',0,'NEUQUEN','Neuquén',1),(283,'2320',2305,'',0,'RIO NEGRO','Río Negro',1),(284,'2321',2305,'',0,'CHUBUT','Chubut',1),(285,'2322',2305,'',0,'SANTA CRUZ','Santa Cruz',1),(286,'2323',2305,'',0,'TIERRA DEL FUEGO','Tierra del Fuego',1),(287,'2324',2305,'',0,'ISLAS MALVINAS','Islas Malvinas',1),(288,'2325',2305,'',0,'ANTARTIDA','Antártida',1),(289,'AN',11701,NULL,0,'AN','Andaman & Nicobar',1),(290,'AP',11701,NULL,0,'AP','Andhra Pradesh',1),(291,'AR',11701,NULL,0,'AR','Arunachal Pradesh',1),(292,'AS',11701,NULL,0,'AS','Assam',1),(293,'BR',11701,NULL,0,'BR','Bihar',1),(294,'CG',11701,NULL,0,'CG','Chattisgarh',1),(295,'CH',11701,NULL,0,'CH','Chandigarh',1),(296,'DD',11701,NULL,0,'DD','Daman & Diu',1),(297,'DL',11701,NULL,0,'DL','Delhi',1),(298,'DN',11701,NULL,0,'DN','Dadra and Nagar Haveli',1),(299,'GA',11701,NULL,0,'GA','Goa',1),(300,'GJ',11701,NULL,0,'GJ','Gujarat',1),(301,'HP',11701,NULL,0,'HP','Himachal Pradesh',1),(302,'HR',11701,NULL,0,'HR','Haryana',1),(303,'JH',11701,NULL,0,'JH','Jharkhand',1),(304,'JK',11701,NULL,0,'JK','Jammu & Kashmir',1),(305,'KA',11701,NULL,0,'KA','Karnataka',1),(306,'KL',11701,NULL,0,'KL','Kerala',1),(307,'LD',11701,NULL,0,'LD','Lakshadweep',1),(308,'MH',11701,NULL,0,'MH','Maharashtra',1),(309,'ML',11701,NULL,0,'ML','Meghalaya',1),(310,'MN',11701,NULL,0,'MN','Manipur',1),(311,'MP',11701,NULL,0,'MP','Madhya Pradesh',1),(312,'MZ',11701,NULL,0,'MZ','Mizoram',1),(313,'NL',11701,NULL,0,'NL','Nagaland',1),(314,'OR',11701,NULL,0,'OR','Orissa',1),(315,'PB',11701,NULL,0,'PB','Punjab',1),(316,'PY',11701,NULL,0,'PY','Puducherry',1),(317,'RJ',11701,NULL,0,'RJ','Rajasthan',1),(318,'SK',11701,NULL,0,'SK','Sikkim',1),(319,'TN',11701,NULL,0,'TN','Tamil Nadu',1),(320,'TR',11701,NULL,0,'TR','Tripura',1),(321,'UL',11701,NULL,0,'UL','Uttarakhand',1),(322,'UP',11701,NULL,0,'UP','Uttar Pradesh',1),(323,'WB',11701,NULL,0,'WB','West Bengal',1),(374,'151',6715,'',0,'151','Arica',1),(375,'152',6715,'',0,'152','Parinacota',1),(376,'011',6701,'',0,'011','Iquique',1),(377,'014',6701,'',0,'014','Tamarugal',1),(378,'021',6702,'',0,'021','Antofagasa',1),(379,'022',6702,'',0,'022','El Loa',1),(380,'023',6702,'',0,'023','Tocopilla',1),(381,'031',6703,'',0,'031','Copiapó',1),(382,'032',6703,'',0,'032','Chañaral',1),(383,'033',6703,'',0,'033','Huasco',1),(384,'041',6704,'',0,'041','Elqui',1),(385,'042',6704,'',0,'042','Choapa',1),(386,'043',6704,'',0,'043','Limarí',1),(387,'051',6705,'',0,'051','Valparaíso',1),(388,'052',6705,'',0,'052','Isla de Pascua',1),(389,'053',6705,'',0,'053','Los Andes',1),(390,'054',6705,'',0,'054','Petorca',1),(391,'055',6705,'',0,'055','Quillota',1),(392,'056',6705,'',0,'056','San Antonio',1),(393,'057',6705,'',0,'057','San Felipe de Aconcagua',1),(394,'058',6705,'',0,'058','Marga Marga',1),(395,'061',6706,'',0,'061','Cachapoal',1),(396,'062',6706,'',0,'062','Cardenal Caro',1),(397,'063',6706,'',0,'063','Colchagua',1),(398,'071',6707,'',0,'071','Talca',1),(399,'072',6707,'',0,'072','Cauquenes',1),(400,'073',6707,'',0,'073','Curicó',1),(401,'074',6707,'',0,'074','Linares',1),(402,'081',6708,'',0,'081','Concepción',1),(403,'082',6708,'',0,'082','Arauco',1),(404,'083',6708,'',0,'083','Biobío',1),(405,'084',6708,'',0,'084','Ñuble',1),(406,'091',6709,'',0,'091','Cautín',1),(407,'092',6709,'',0,'092','Malleco',1),(408,'141',6714,'',0,'141','Valdivia',1),(409,'142',6714,'',0,'142','Ranco',1),(410,'101',6710,'',0,'101','Llanquihue',1),(411,'102',6710,'',0,'102','Chiloé',1),(412,'103',6710,'',0,'103','Osorno',1),(413,'104',6710,'',0,'104','Palena',1),(414,'111',6711,'',0,'111','Coihaique',1),(415,'112',6711,'',0,'112','Aisén',1),(416,'113',6711,'',0,'113','Capitán Prat',1),(417,'114',6711,'',0,'114','General Carrera',1),(418,'121',6712,'',0,'121','Magallanes',1),(419,'122',6712,'',0,'122','Antártica Chilena',1),(420,'123',6712,'',0,'123','Tierra del Fuego',1),(421,'124',6712,'',0,'124','Última Esperanza',1),(422,'131',6713,'',0,'131','Santiago',1),(423,'132',6713,'',0,'132','Cordillera',1),(424,'133',6713,'',0,'133','Chacabuco',1),(425,'134',6713,'',0,'134','Maipo',1),(426,'135',6713,'',0,'135','Melipilla',1),(427,'136',6713,'',0,'136','Talagante',1),(428,'DIF',15401,'',0,'DIF','Distrito Federal',1),(429,'AGS',15401,'',0,'AGS','Aguascalientes',1),(430,'BCN',15401,'',0,'BCN','Baja California Norte',1),(431,'BCS',15401,'',0,'BCS','Baja California Sur',1),(432,'CAM',15401,'',0,'CAM','Campeche',1),(433,'CHP',15401,'',0,'CHP','Chiapas',1),(434,'CHI',15401,'',0,'CHI','Chihuahua',1),(435,'COA',15401,'',0,'COA','Coahuila',1),(436,'COL',15401,'',0,'COL','Colima',1),(437,'DUR',15401,'',0,'DUR','Durango',1),(438,'GTO',15401,'',0,'GTO','Guanajuato',1),(439,'GRO',15401,'',0,'GRO','Guerrero',1),(440,'HGO',15401,'',0,'HGO','Hidalgo',1),(441,'JAL',15401,'',0,'JAL','Jalisco',1),(442,'MEX',15401,'',0,'MEX','México',1),(443,'MIC',15401,'',0,'MIC','Michoacán de Ocampo',1),(444,'MOR',15401,'',0,'MOR','Morelos',1),(445,'NAY',15401,'',0,'NAY','Nayarit',1),(446,'NLE',15401,'',0,'NLE','Nuevo León',1),(447,'OAX',15401,'',0,'OAX','Oaxaca',1),(448,'PUE',15401,'',0,'PUE','Puebla',1),(449,'QRO',15401,'',0,'QRO','Querétaro',1),(451,'ROO',15401,'',0,'ROO','Quintana Roo',1),(452,'SLP',15401,'',0,'SLP','San Luis Potosí',1),(453,'SIN',15401,'',0,'SIN','Sinaloa',1),(454,'SON',15401,'',0,'SON','Sonora',1),(455,'TAB',15401,'',0,'TAB','Tabasco',1),(456,'TAM',15401,'',0,'TAM','Tamaulipas',1),(457,'TLX',15401,'',0,'TLX','Tlaxcala',1),(458,'VER',15401,'',0,'VER','Veracruz',1),(459,'YUC',15401,'',0,'YUC','Yucatán',1),(460,'ZAC',15401,'',0,'ZAC','Zacatecas',1),(461,'ANT',7001,'',0,'ANT','Antioquia',1),(462,'BOL',7001,'',0,'BOL','Bolívar',1),(463,'BOY',7001,'',0,'BOY','Boyacá',1),(464,'CAL',7001,'',0,'CAL','Caldas',1),(465,'CAU',7001,'',0,'CAU','Cauca',1),(466,'CUN',7001,'',0,'CUN','Cundinamarca',1),(467,'HUI',7001,'',0,'HUI','Huila',1),(468,'LAG',7001,'',0,'LAG','La Guajira',1),(469,'MET',7001,'',0,'MET','Meta',1),(470,'NAR',7001,'',0,'NAR','Nariño',1),(471,'NDS',7001,'',0,'NDS','Norte de Santander',1),(472,'SAN',7001,'',0,'SAN','Santander',1),(473,'SUC',7001,'',0,'SUC','Sucre',1),(474,'TOL',7001,'',0,'TOL','Tolima',1),(475,'VAC',7001,'',0,'VAC','Valle del Cauca',1),(476,'RIS',7001,'',0,'RIS','Risalda',1),(477,'ATL',7001,'',0,'ATL','Atlántico',1),(478,'COR',7001,'',0,'COR','Córdoba',1),(479,'SAP',7001,'',0,'SAP','San Andrés, Providencia y Santa Catalina',1),(480,'ARA',7001,'',0,'ARA','Arauca',1),(481,'CAS',7001,'',0,'CAS','Casanare',1),(482,'AMA',7001,'',0,'AMA','Amazonas',1),(483,'CAQ',7001,'',0,'CAQ','Caquetá',1),(484,'CHO',7001,'',0,'CHO','Chocó',1),(485,'GUA',7001,'',0,'GUA','Guainía',1),(486,'GUV',7001,'',0,'GUV','Guaviare',1),(487,'PUT',7001,'',0,'PUT','Putumayo',1),(488,'QUI',7001,'',0,'QUI','Quindío',1),(489,'VAU',7001,'',0,'VAU','Vaupés',1),(490,'BOG',7001,'',0,'BOG','Bogotá',1),(491,'VID',7001,'',0,'VID','Vichada',1),(492,'CES',7001,'',0,'CES','Cesar',1),(493,'MAG',7001,'',0,'MAG','Magdalena',1),(494,'AT',11401,'',0,'AT','Atlántida',1),(495,'CH',11401,'',0,'CH','Choluteca',1),(496,'CL',11401,'',0,'CL','Colón',1),(497,'CM',11401,'',0,'CM','Comayagua',1),(498,'CO',11401,'',0,'CO','Copán',1),(499,'CR',11401,'',0,'CR','Cortés',1),(500,'EP',11401,'',0,'EP','El Paraíso',1),(501,'FM',11401,'',0,'FM','Francisco Morazán',1),(502,'GD',11401,'',0,'GD','Gracias a Dios',1),(503,'IN',11401,'',0,'IN','Intibucá',1),(504,'IB',11401,'',0,'IB','Islas de la Bahía',1),(505,'LP',11401,'',0,'LP','La Paz',1),(506,'LM',11401,'',0,'LM','Lempira',1),(507,'OC',11401,'',0,'OC','Ocotepeque',1),(508,'OL',11401,'',0,'OL','Olancho',1),(509,'SB',11401,'',0,'SB','Santa Bárbara',1),(510,'VL',11401,'',0,'VL','Valle',1),(511,'YO',11401,'',0,'YO','Yoro',1),(512,'DC',11401,'',0,'DC','Distrito Central',1),(652,'CC',4601,'Oistins',0,'CC','Christ Church',1),(655,'SA',4601,'Greenland',0,'SA','Saint Andrew',1),(656,'SG',4601,'Bulkeley',0,'SG','Saint George',1),(657,'JA',4601,'Holetown',0,'JA','Saint James',1),(658,'SJ',4601,'Four Roads',0,'SJ','Saint John',1),(659,'SB',4601,'Bathsheba',0,'SB','Saint Joseph',1),(660,'SL',4601,'Crab Hill',0,'SL','Saint Lucy',1),(661,'SM',4601,'Bridgetown',0,'SM','Saint Michael',1),(662,'SP',4601,'Speightstown',0,'SP','Saint Peter',1),(663,'SC',4601,'Crane',0,'SC','Saint Philip',1),(664,'ST',4601,'Hillaby',0,'ST','Saint Thomas',1),(777,'AG',315,NULL,NULL,NULL,'AGRIGENTO',1),(778,'AL',312,NULL,NULL,NULL,'ALESSANDRIA',1),(779,'AN',310,NULL,NULL,NULL,'ANCONA',1),(780,'AO',319,NULL,NULL,NULL,'AOSTA',1),(781,'AR',316,NULL,NULL,NULL,'AREZZO',1),(782,'AP',310,NULL,NULL,NULL,'ASCOLI PICENO',1),(783,'AT',312,NULL,NULL,NULL,'ASTI',1),(784,'AV',304,NULL,NULL,NULL,'AVELLINO',1),(785,'BA',313,NULL,NULL,NULL,'BARI',1),(786,'BT',313,NULL,NULL,NULL,'BARLETTA-ANDRIA-TRANI',1),(787,'BL',320,NULL,NULL,NULL,'BELLUNO',1),(788,'BN',304,NULL,NULL,NULL,'BENEVENTO',1),(789,'BG',309,NULL,NULL,NULL,'BERGAMO',1),(790,'BI',312,NULL,NULL,NULL,'BIELLA',1),(791,'BO',305,NULL,NULL,NULL,'BOLOGNA',1),(792,'BZ',317,NULL,NULL,NULL,'BOLZANO',1),(793,'BS',309,NULL,NULL,NULL,'BRESCIA',1),(794,'BR',313,NULL,NULL,NULL,'BRINDISI',1),(795,'CA',314,NULL,NULL,NULL,'CAGLIARI',1),(796,'CL',315,NULL,NULL,NULL,'CALTANISSETTA',1),(797,'CB',311,NULL,NULL,NULL,'CAMPOBASSO',1),(798,'CI',314,NULL,NULL,NULL,'CARBONIA-IGLESIAS',1),(799,'CE',304,NULL,NULL,NULL,'CASERTA',1),(800,'CT',315,NULL,NULL,NULL,'CATANIA',1),(801,'CZ',303,NULL,NULL,NULL,'CATANZARO',1),(802,'CH',301,NULL,NULL,NULL,'CHIETI',1),(803,'CO',309,NULL,NULL,NULL,'COMO',1),(804,'CS',303,NULL,NULL,NULL,'COSENZA',1),(805,'CR',309,NULL,NULL,NULL,'CREMONA',1),(806,'KR',303,NULL,NULL,NULL,'CROTONE',1),(807,'CN',312,NULL,NULL,NULL,'CUNEO',1),(808,'EN',315,NULL,NULL,NULL,'ENNA',1),(809,'FM',310,NULL,NULL,NULL,'FERMO',1),(810,'FE',305,NULL,NULL,NULL,'FERRARA',1),(811,'FI',316,NULL,NULL,NULL,'FIRENZE',1),(812,'FG',313,NULL,NULL,NULL,'FOGGIA',1),(813,'FC',305,NULL,NULL,NULL,'FORLI-CESENA',1),(814,'FR',307,NULL,NULL,NULL,'FROSINONE',1),(815,'GE',308,NULL,NULL,NULL,'GENOVA',1),(816,'GO',306,NULL,NULL,NULL,'GORIZIA',1),(817,'GR',316,NULL,NULL,NULL,'GROSSETO',1),(818,'IM',308,NULL,NULL,NULL,'IMPERIA',1),(819,'IS',311,NULL,NULL,NULL,'ISERNIA',1),(820,'SP',308,NULL,NULL,NULL,'LA SPEZIA',1),(821,'AQ',301,NULL,NULL,NULL,'L AQUILA',1),(822,'LT',307,NULL,NULL,NULL,'LATINA',1),(823,'LE',313,NULL,NULL,NULL,'LECCE',1),(824,'LC',309,NULL,NULL,NULL,'LECCO',1),(825,'LI',314,NULL,NULL,NULL,'LIVORNO',1),(826,'LO',309,NULL,NULL,NULL,'LODI',1),(827,'LU',316,NULL,NULL,NULL,'LUCCA',1),(828,'MC',310,NULL,NULL,NULL,'MACERATA',1),(829,'MN',309,NULL,NULL,NULL,'MANTOVA',1),(830,'MS',316,NULL,NULL,NULL,'MASSA-CARRARA',1),(831,'MT',302,NULL,NULL,NULL,'MATERA',1),(832,'VS',314,NULL,NULL,NULL,'MEDIO CAMPIDANO',1),(833,'ME',315,NULL,NULL,NULL,'MESSINA',1),(834,'MI',309,NULL,NULL,NULL,'MILANO',1),(835,'MB',309,NULL,NULL,NULL,'MONZA e BRIANZA',1),(836,'MO',305,NULL,NULL,NULL,'MODENA',1),(837,'NA',304,NULL,NULL,NULL,'NAPOLI',1),(838,'NO',312,NULL,NULL,NULL,'NOVARA',1),(839,'NU',314,NULL,NULL,NULL,'NUORO',1),(840,'OG',314,NULL,NULL,NULL,'OGLIASTRA',1),(841,'OT',314,NULL,NULL,NULL,'OLBIA-TEMPIO',1),(842,'OR',314,NULL,NULL,NULL,'ORISTANO',1),(843,'PD',320,NULL,NULL,NULL,'PADOVA',1),(844,'PA',315,NULL,NULL,NULL,'PALERMO',1),(845,'PR',305,NULL,NULL,NULL,'PARMA',1),(846,'PV',309,NULL,NULL,NULL,'PAVIA',1),(847,'PG',318,NULL,NULL,NULL,'PERUGIA',1),(848,'PU',310,NULL,NULL,NULL,'PESARO e URBINO',1),(849,'PE',301,NULL,NULL,NULL,'PESCARA',1),(850,'PC',305,NULL,NULL,NULL,'PIACENZA',1),(851,'PI',316,NULL,NULL,NULL,'PISA',1),(852,'PT',316,NULL,NULL,NULL,'PISTOIA',1),(853,'PN',306,NULL,NULL,NULL,'PORDENONE',1),(854,'PZ',302,NULL,NULL,NULL,'POTENZA',1),(855,'PO',316,NULL,NULL,NULL,'PRATO',1),(856,'RG',315,NULL,NULL,NULL,'RAGUSA',1),(857,'RA',305,NULL,NULL,NULL,'RAVENNA',1),(858,'RC',303,NULL,NULL,NULL,'REGGIO CALABRIA',1),(859,'RE',305,NULL,NULL,NULL,'REGGIO NELL EMILIA',1),(860,'RI',307,NULL,NULL,NULL,'RIETI',1),(861,'RN',305,NULL,NULL,NULL,'RIMINI',1),(862,'RM',307,NULL,NULL,NULL,'ROMA',1),(863,'RO',320,NULL,NULL,NULL,'ROVIGO',1),(864,'SA',304,NULL,NULL,NULL,'SALERNO',1),(865,'SS',314,NULL,NULL,NULL,'SASSARI',1),(866,'SV',308,NULL,NULL,NULL,'SAVONA',1),(867,'SI',316,NULL,NULL,NULL,'SIENA',1),(868,'SR',315,NULL,NULL,NULL,'SIRACUSA',1),(869,'SO',309,NULL,NULL,NULL,'SONDRIO',1),(870,'TA',313,NULL,NULL,NULL,'TARANTO',1),(871,'TE',301,NULL,NULL,NULL,'TERAMO',1),(872,'TR',318,NULL,NULL,NULL,'TERNI',1),(873,'TO',312,NULL,NULL,NULL,'TORINO',1),(874,'TP',315,NULL,NULL,NULL,'TRAPANI',1),(875,'TN',317,NULL,NULL,NULL,'TRENTO',1),(876,'TV',320,NULL,NULL,NULL,'TREVISO',1),(877,'TS',306,NULL,NULL,NULL,'TRIESTE',1),(878,'UD',306,NULL,NULL,NULL,'UDINE',1),(879,'VA',309,NULL,NULL,NULL,'VARESE',1),(880,'VE',320,NULL,NULL,NULL,'VENEZIA',1),(881,'VB',312,NULL,NULL,NULL,'VERBANO-CUSIO-OSSOLA',1),(882,'VC',312,NULL,NULL,NULL,'VERCELLI',1),(883,'VR',320,NULL,NULL,NULL,'VERONA',1),(884,'VV',303,NULL,NULL,NULL,'VIBO VALENTIA',1),(885,'VI',320,NULL,NULL,NULL,'VICENZA',1),(886,'VT',307,NULL,NULL,NULL,'VITERBO',1),(1036,'VE-L',23201,'',0,'VE-L','Mérida',1),(1037,'VE-T',23201,'',0,'VE-T','Trujillo',1),(1038,'VE-E',23201,'',0,'VE-E','Barinas',1),(1039,'VE-M',23202,'',0,'VE-M','Miranda',1),(1040,'VE-W',23202,'',0,'VE-W','Vargas',1),(1041,'VE-A',23202,'',0,'VE-A','Distrito Capital',1),(1042,'VE-D',23203,'',0,'VE-D','Aragua',1),(1043,'VE-G',23203,'',0,'VE-G','Carabobo',1),(1044,'VE-I',23204,'',0,'VE-I','Falcón',1),(1045,'VE-K',23204,'',0,'VE-K','Lara',1),(1046,'VE-U',23204,'',0,'VE-U','Yaracuy',1),(1047,'VE-F',23205,'',0,'VE-F','Bolívar',1),(1048,'VE-X',23205,'',0,'VE-X','Amazonas',1),(1049,'VE-Y',23205,'',0,'VE-Y','Delta Amacuro',1),(1050,'VE-O',23206,'',0,'VE-O','Nueva Esparta',1),(1051,'VE-Z',23206,'',0,'VE-Z','Dependencias Federales',1),(1052,'VE-C',23207,'',0,'VE-C','Apure',1),(1053,'VE-J',23207,'',0,'VE-J','Guárico',1),(1054,'VE-H',23207,'',0,'VE-H','Cojedes',1),(1055,'VE-P',23207,'',0,'VE-P','Portuguesa',1),(1056,'VE-B',23208,'',0,'VE-B','Anzoátegui',1),(1057,'VE-N',23208,'',0,'VE-N','Monagas',1),(1058,'VE-R',23208,'',0,'VE-R','Sucre',1),(1059,'VE-V',23209,'',0,'VE-V','Zulia',1),(1060,'VE-S',23209,'',0,'VE-S','Táchira',1),(1061,'66',10201,NULL,NULL,NULL,'?????',1),(1062,'00',10205,NULL,NULL,NULL,'?????',1),(1063,'01',10205,NULL,NULL,NULL,'?????',1),(1064,'02',10205,NULL,NULL,NULL,'?????',1),(1065,'03',10205,NULL,NULL,NULL,'??????',1),(1066,'04',10205,NULL,NULL,NULL,'?????',1),(1067,'05',10205,NULL,NULL,NULL,'??????',1),(1068,'06',10203,NULL,NULL,NULL,'??????',1),(1069,'07',10203,NULL,NULL,NULL,'???????????',1),(1070,'08',10203,NULL,NULL,NULL,'??????',1),(1071,'09',10203,NULL,NULL,NULL,'?????',1),(1072,'10',10203,NULL,NULL,NULL,'??????',1),(1073,'11',10203,NULL,NULL,NULL,'??????',1),(1074,'12',10203,NULL,NULL,NULL,'?????????',1),(1075,'13',10206,NULL,NULL,NULL,'????',1),(1076,'14',10206,NULL,NULL,NULL,'?????????',1),(1077,'15',10206,NULL,NULL,NULL,'????????',1),(1078,'16',10206,NULL,NULL,NULL,'???????',1),(1079,'17',10213,NULL,NULL,NULL,'???????',1),(1080,'18',10213,NULL,NULL,NULL,'????????',1),(1081,'19',10213,NULL,NULL,NULL,'??????',1),(1082,'20',10213,NULL,NULL,NULL,'???????',1),(1083,'21',10212,NULL,NULL,NULL,'????????',1),(1084,'22',10212,NULL,NULL,NULL,'??????',1),(1085,'23',10212,NULL,NULL,NULL,'????????',1),(1086,'24',10212,NULL,NULL,NULL,'???????',1),(1087,'25',10212,NULL,NULL,NULL,'????????',1),(1088,'26',10212,NULL,NULL,NULL,'???????',1),(1089,'27',10202,NULL,NULL,NULL,'??????',1),(1090,'28',10202,NULL,NULL,NULL,'?????????',1),(1091,'29',10202,NULL,NULL,NULL,'????????',1),(1092,'30',10202,NULL,NULL,NULL,'??????',1),(1093,'31',10209,NULL,NULL,NULL,'????????',1),(1094,'32',10209,NULL,NULL,NULL,'???????',1),(1095,'33',10209,NULL,NULL,NULL,'????????',1),(1096,'34',10209,NULL,NULL,NULL,'???????',1),(1097,'35',10209,NULL,NULL,NULL,'????????',1),(1098,'36',10211,NULL,NULL,NULL,'???????????????',1),(1099,'37',10211,NULL,NULL,NULL,'?????',1),(1100,'38',10211,NULL,NULL,NULL,'?????',1),(1101,'39',10207,NULL,NULL,NULL,'????????',1),(1102,'40',10207,NULL,NULL,NULL,'???????',1),(1103,'41',10207,NULL,NULL,NULL,'??????????',1),(1104,'42',10207,NULL,NULL,NULL,'?????',1),(1105,'43',10207,NULL,NULL,NULL,'???????',1),(1106,'44',10208,NULL,NULL,NULL,'??????',1),(1107,'45',10208,NULL,NULL,NULL,'??????',1),(1108,'46',10208,NULL,NULL,NULL,'??????',1),(1109,'47',10208,NULL,NULL,NULL,'?????',1),(1110,'48',10208,NULL,NULL,NULL,'????',1),(1111,'49',10210,NULL,NULL,NULL,'??????',1),(1112,'50',10210,NULL,NULL,NULL,'????',1),(1113,'51',10210,NULL,NULL,NULL,'????????',1),(1114,'52',10210,NULL,NULL,NULL,'????????',1),(1115,'53',10210,NULL,NULL,NULL,'???-??????',1),(1116,'54',10210,NULL,NULL,NULL,'??',1),(1117,'55',10210,NULL,NULL,NULL,'?????',1),(1118,'56',10210,NULL,NULL,NULL,'???????',1),(1119,'57',10210,NULL,NULL,NULL,'?????',1),(1120,'58',10210,NULL,NULL,NULL,'?????',1),(1121,'59',10210,NULL,NULL,NULL,'?????',1),(1122,'60',10210,NULL,NULL,NULL,'?????',1),(1123,'61',10210,NULL,NULL,NULL,'?????',1),(1124,'62',10204,NULL,NULL,NULL,'????????',1),(1125,'63',10204,NULL,NULL,NULL,'??????',1),(1126,'64',10204,NULL,NULL,NULL,'???????',1),(1127,'65',10204,NULL,NULL,NULL,'?????',1),(1128,'AL01',1301,'',0,'','Wilaya d\'Adrar',1),(1129,'AL02',1301,'',0,'','Wilaya de Chlef',1),(1130,'AL03',1301,'',0,'','Wilaya de Laghouat',1),(1131,'AL04',1301,'',0,'','Wilaya d\'Oum El Bouaghi',1),(1132,'AL05',1301,'',0,'','Wilaya de Batna',1),(1133,'AL06',1301,'',0,'','Wilaya de Béjaïa',1),(1134,'AL07',1301,'',0,'','Wilaya de Biskra',1),(1135,'AL08',1301,'',0,'','Wilaya de Béchar',1),(1136,'AL09',1301,'',0,'','Wilaya de Blida',1),(1137,'AL11',1301,'',0,'','Wilaya de Bouira',1),(1138,'AL12',1301,'',0,'','Wilaya de Tamanrasset',1),(1139,'AL13',1301,'',0,'','Wilaya de Tébessa',1),(1140,'AL14',1301,'',0,'','Wilaya de Tlemcen',1),(1141,'AL15',1301,'',0,'','Wilaya de Tiaret',1),(1142,'AL16',1301,'',0,'','Wilaya de Tizi Ouzou',1),(1143,'AL17',1301,'',0,'','Wilaya d\'Alger',1),(1144,'AL18',1301,'',0,'','Wilaya de Djelfa',1),(1145,'AL19',1301,'',0,'','Wilaya de Jijel',1),(1146,'AL20',1301,'',0,'','Wilaya de Sétif	',1),(1147,'AL21',1301,'',0,'','Wilaya de Saïda',1),(1148,'AL22',1301,'',0,'','Wilaya de Skikda',1),(1149,'AL23',1301,'',0,'','Wilaya de Sidi Bel Abbès',1),(1150,'AL24',1301,'',0,'','Wilaya d\'Annaba',1),(1151,'AL25',1301,'',0,'','Wilaya de Guelma',1),(1152,'AL26',1301,'',0,'','Wilaya de Constantine',1),(1153,'AL27',1301,'',0,'','Wilaya de Médéa',1),(1154,'AL28',1301,'',0,'','Wilaya de Mostaganem',1),(1155,'AL29',1301,'',0,'','Wilaya de M\'Sila',1),(1156,'AL30',1301,'',0,'','Wilaya de Mascara',1),(1157,'AL31',1301,'',0,'','Wilaya d\'Ouargla',1),(1158,'AL32',1301,'',0,'','Wilaya d\'Oran',1),(1159,'AL33',1301,'',0,'','Wilaya d\'El Bayadh',1),(1160,'AL34',1301,'',0,'','Wilaya d\'Illizi',1),(1161,'AL35',1301,'',0,'','Wilaya de Bordj Bou Arreridj',1),(1162,'AL36',1301,'',0,'','Wilaya de Boumerdès',1),(1163,'AL37',1301,'',0,'','Wilaya d\'El Tarf',1),(1164,'AL38',1301,'',0,'','Wilaya de Tindouf',1),(1165,'AL39',1301,'',0,'','Wilaya de Tissemsilt',1),(1166,'AL40',1301,'',0,'','Wilaya d\'El Oued',1),(1167,'AL41',1301,'',0,'','Wilaya de Khenchela',1),(1168,'AL42',1301,'',0,'','Wilaya de Souk Ahras',1),(1169,'AL43',1301,'',0,'','Wilaya de Tipaza',1),(1170,'AL44',1301,'',0,'','Wilaya de Mila',1),(1171,'AL45',1301,'',0,'','Wilaya d\'Aïn Defla',1),(1172,'AL46',1301,'',0,'','Wilaya de Naâma',1),(1173,'AL47',1301,'',0,'','Wilaya d\'Aïn Témouchent',1),(1174,'AL48',1301,'',0,'','Wilaya de Ghardaia',1),(1175,'AL49',1301,'',0,'','Wilaya de Relizane',1),(1176,'MA',1209,'',0,'','Province de Benslimane',1),(1177,'MA1',1209,'',0,'','Province de Berrechid',1),(1178,'MA2',1209,'',0,'','Province de Khouribga',1),(1179,'MA3',1209,'',0,'','Province de Settat',1),(1180,'MA4',1210,'',0,'','Province d\'El Jadida',1),(1181,'MA5',1210,'',0,'','Province de Safi',1),(1182,'MA6',1210,'',0,'','Province de Sidi Bennour',1),(1183,'MA7',1210,'',0,'','Province de Youssoufia',1),(1184,'MA6B',1205,'',0,'','Préfecture de Fès',1),(1185,'MA7B',1205,'',0,'','Province de Boulemane',1),(1186,'MA8',1205,'',0,'','Province de Moulay Yacoub',1),(1187,'MA9',1205,'',0,'','Province de Sefrou',1),(1188,'MA8A',1202,'',0,'','Province de Kénitra',1),(1189,'MA9A',1202,'',0,'','Province de Sidi Kacem',1),(1190,'MA10',1202,'',0,'','Province de Sidi Slimane',1),(1191,'MA11',1208,'',0,'','Préfecture de Casablanca',1),(1192,'MA12',1208,'',0,'','Préfecture de Mohammédia',1),(1193,'MA13',1208,'',0,'','Province de Médiouna',1),(1194,'MA14',1208,'',0,'','Province de Nouaceur',1),(1195,'MA15',1214,'',0,'','Province d\'Assa-Zag',1),(1196,'MA16',1214,'',0,'','Province d\'Es-Semara',1),(1197,'MA17A',1214,'',0,'','Province de Guelmim',1),(1198,'MA18',1214,'',0,'','Province de Tata',1),(1199,'MA19',1214,'',0,'','Province de Tan-Tan',1),(1200,'MA15',1215,'',0,'','Province de Boujdour',1),(1201,'MA16',1215,'',0,'','Province de Lâayoune',1),(1202,'MA17',1215,'',0,'','Province de Tarfaya',1),(1203,'MA18',1211,'',0,'','Préfecture de Marrakech',1),(1204,'MA19',1211,'',0,'','Province d\'Al Haouz',1),(1205,'MA20',1211,'',0,'','Province de Chichaoua',1),(1206,'MA21',1211,'',0,'','Province d\'El Kelâa des Sraghna',1),(1207,'MA22',1211,'',0,'','Province d\'Essaouira',1),(1208,'MA23',1211,'',0,'','Province de Rehamna',1),(1209,'MA24',1206,'',0,'','Préfecture de Meknès',1),(1210,'MA25',1206,'',0,'','Province d’El Hajeb',1),(1211,'MA26',1206,'',0,'','Province d\'Errachidia',1),(1212,'MA27',1206,'',0,'','Province d’Ifrane',1),(1213,'MA28',1206,'',0,'','Province de Khénifra',1),(1214,'MA29',1206,'',0,'','Province de Midelt',1),(1215,'MA30',1204,'',0,'','Préfecture d\'Oujda-Angad',1),(1216,'MA31',1204,'',0,'','Province de Berkane',1),(1217,'MA32',1204,'',0,'','Province de Driouch',1),(1218,'MA33',1204,'',0,'','Province de Figuig',1),(1219,'MA34',1204,'',0,'','Province de Jerada',1),(1220,'MA35',1204,'',0,'','Province de Nadorgg',1),(1221,'MA36',1204,'',0,'','Province de Taourirt',1),(1222,'MA37',1216,'',0,'','Province d\'Aousserd',1),(1223,'MA38',1216,'',0,'','Province d\'Oued Ed-Dahab',1),(1224,'MA39',1207,'',0,'','Préfecture de Rabat',1),(1225,'MA40',1207,'',0,'','Préfecture de Skhirat-Témara',1),(1226,'MA41',1207,'',0,'','Préfecture de Salé',1),(1227,'MA42',1207,'',0,'','Province de Khémisset',1),(1228,'MA43',1213,'',0,'','Préfecture d\'Agadir Ida-Outanane',1),(1229,'MA44',1213,'',0,'','Préfecture d\'Inezgane-Aït Melloul',1),(1230,'MA45',1213,'',0,'','Province de Chtouka-Aït Baha',1),(1231,'MA46',1213,'',0,'','Province d\'Ouarzazate',1),(1232,'MA47',1213,'',0,'','Province de Sidi Ifni',1),(1233,'MA48',1213,'',0,'','Province de Taroudant',1),(1234,'MA49',1213,'',0,'','Province de Tinghir',1),(1235,'MA50',1213,'',0,'','Province de Tiznit',1),(1236,'MA51',1213,'',0,'','Province de Zagora',1),(1237,'MA52',1212,'',0,'','Province d\'Azilal',1),(1238,'MA53',1212,'',0,'','Province de Beni Mellal',1),(1239,'MA54',1212,'',0,'','Province de Fquih Ben Salah',1),(1240,'MA55',1201,'',0,'','Préfecture de M\'diq-Fnideq',1),(1241,'MA56',1201,'',0,'','Préfecture de Tanger-Asilah',1),(1242,'MA57',1201,'',0,'','Province de Chefchaouen',1),(1243,'MA58',1201,'',0,'','Province de Fahs-Anjra',1),(1244,'MA59',1201,'',0,'','Province de Larache',1),(1245,'MA60',1201,'',0,'','Province d\'Ouezzane',1),(1246,'MA61',1201,'',0,'','Province de Tétouan',1),(1247,'MA62',1203,'',0,'','Province de Guercif',1),(1248,'MA63',1203,'',0,'','Province d\'Al Hoceïma',1),(1249,'MA64',1203,'',0,'','Province de Taounate',1),(1250,'MA65',1203,'',0,'','Province de Taza',1),(1251,'MA6A',1205,'',0,'','Préfecture de Fès',1),(1252,'MA7A',1205,'',0,'','Province de Boulemane',1),(1253,'MA15A',1214,'',0,'','Province d\'Assa-Zag',1),(1254,'MA16A',1214,'',0,'','Province d\'Es-Semara',1),(1255,'MA18A',1211,'',0,'','Préfecture de Marrakech',1),(1256,'MA19A',1214,'',0,'','Province de Tan-Tan',1),(1257,'MA19B',1214,'',0,'','Province de Tan-Tan',1),(1258,'TN01',1001,'',0,'','Ariana',1),(1259,'TN02',1001,'',0,'','Béja',1),(1260,'TN03',1001,'',0,'','Ben Arous',1),(1261,'TN04',1001,'',0,'','Bizerte',1),(1262,'TN05',1001,'',0,'','Gabès',1),(1263,'TN06',1001,'',0,'','Gafsa',1),(1264,'TN07',1001,'',0,'','Jendouba',1),(1265,'TN08',1001,'',0,'','Kairouan',1),(1266,'TN09',1001,'',0,'','Kasserine',1),(1267,'TN10',1001,'',0,'','Kébili',1),(1268,'TN11',1001,'',0,'','La Manouba',1),(1269,'TN12',1001,'',0,'','Le Kef',1),(1270,'TN13',1001,'',0,'','Mahdia',1),(1271,'TN14',1001,'',0,'','Médenine',1),(1272,'TN15',1001,'',0,'','Monastir',1),(1273,'TN16',1001,'',0,'','Nabeul',1),(1274,'TN17',1001,'',0,'','Sfax',1),(1275,'TN18',1001,'',0,'','Sidi Bouzid',1),(1276,'TN19',1001,'',0,'','Siliana',1),(1277,'TN20',1001,'',0,'','Sousse',1),(1278,'TN21',1001,'',0,'','Tataouine',1),(1279,'TN22',1001,'',0,'','Tozeur',1),(1280,'TN23',1001,'',0,'','Tunis',1),(1281,'TN24',1001,'',0,'','Zaghouan',1);
/*!40000 ALTER TABLE `llx_c_departements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_ecotaxe`
--

DROP TABLE IF EXISTS `llx_c_ecotaxe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_ecotaxe` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL,
  `libelle` varchar(255) DEFAULT NULL,
  `price` double(24,8) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `fk_pays` int(11) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_ecotaxe` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_ecotaxe`
--

LOCK TABLES `llx_c_ecotaxe` WRITE;
/*!40000 ALTER TABLE `llx_c_ecotaxe` DISABLE KEYS */;
INSERT INTO `llx_c_ecotaxe` VALUES (1,'ER-A-A','Materiels electriques < 0,2kg',0.01000000,'ERP',1,1),(2,'ER-A-B','Materiels electriques >= 0,2 kg et < 0,5 kg',0.03000000,'ERP',1,1),(3,'ER-A-C','Materiels electriques >= 0,5 kg et < 1 kg',0.04000000,'ERP',1,1),(4,'ER-A-D','Materiels electriques >= 1 kg et < 2 kg',0.13000000,'ERP',1,1),(5,'ER-A-E','Materiels electriques >= 2 kg et < 4kg',0.21000000,'ERP',1,1),(6,'ER-A-F','Materiels electriques >= 4 kg et < 8 kg',0.42000000,'ERP',1,1),(7,'ER-A-G','Materiels electriques >= 8 kg et < 15 kg',0.84000000,'ERP',1,1),(8,'ER-A-H','Materiels electriques >= 15 kg et < 20 kg',1.25000000,'ERP',1,1),(9,'ER-A-I','Materiels electriques >= 20 kg et < 30 kg',1.88000000,'ERP',1,1),(10,'ER-A-J','Materiels electriques >= 30 kg',3.34000000,'ERP',1,1),(11,'ER-M-1','TV, Moniteurs < 9kg',0.84000000,'ERP',1,1),(12,'ER-M-2','TV, Moniteurs >= 9kg et < 15kg',1.67000000,'ERP',1,1),(13,'ER-M-3','TV, Moniteurs >= 15kg et < 30kg',3.34000000,'ERP',1,1),(14,'ER-M-4','TV, Moniteurs >= 30 kg',6.69000000,'ERP',1,1),(15,'EC-A-A','Materiels electriques  0,2 kg max',0.00840000,'Ecologic',1,1),(16,'EC-A-B','Materiels electriques 0,21 kg min - 0,50 kg max',0.02500000,'Ecologic',1,1),(17,'EC-A-C','Materiels electriques  0,51 kg min - 1 kg max',0.04000000,'Ecologic',1,1),(18,'EC-A-D','Materiels electriques  1,01 kg min - 2,5 kg max',0.13000000,'Ecologic',1,1),(19,'EC-A-E','Materiels electriques  2,51 kg min - 4 kg max',0.21000000,'Ecologic',1,1),(20,'EC-A-F','Materiels electriques 4,01 kg min - 8 kg max',0.42000000,'Ecologic',1,1),(21,'EC-A-G','Materiels electriques  8,01 kg min - 12 kg max',0.63000000,'Ecologic',1,1),(22,'EC-A-H','Materiels electriques 12,01 kg min - 20 kg max',1.05000000,'Ecologic',1,1),(23,'EC-A-I','Materiels electriques  20,01 kg min',1.88000000,'Ecologic',1,1),(24,'EC-M-1','TV, Moniteurs 9 kg max',0.84000000,'Ecologic',1,1),(25,'EC-M-2','TV, Moniteurs 9,01 kg min - 18 kg max',1.67000000,'Ecologic',1,1),(26,'EC-M-3','TV, Moniteurs 18,01 kg min - 36 kg max',3.34000000,'Ecologic',1,1),(27,'EC-M-4','TV, Moniteurs 36,01 kg min',6.69000000,'Ecologic',1,1),(28,'ES-M-1','TV, Moniteurs <= 20 pouces',0.84000000,'Eco-systemes',1,1),(29,'ES-M-2','TV, Moniteurs > 20 pouces et <= 32 pouces',3.34000000,'Eco-systemes',1,1),(30,'ES-M-3','TV, Moniteurs > 32 pouces et autres grands ecrans',6.69000000,'Eco-systemes',1,1),(31,'ES-A-A','Ordinateur fixe, Audio home systems (HIFI), elements hifi separes',0.84000000,'Eco-systemes',1,1),(32,'ES-A-B','Ordinateur portable, CD-RCR, VCR, lecteurs et enregistreurs DVD, instruments de musique et caisses de resonance, haut parleurs...',0.25000000,'Eco-systemes',1,1),(33,'ES-A-C','Imprimante, photocopieur, telecopieur',0.42000000,'Eco-systemes',1,1),(34,'ES-A-D','Accessoires, clavier, souris, PDA, imprimante photo, appareil photo, gps, telephone, repondeur, telephone sans fil, modem, telecommande, casque, camescope, baladeur mp3, radio portable, radio K7 et CD portable, radio reveil',0.08400000,'Eco-systemes',1,1),(35,'ES-A-E','GSM',0.00840000,'Eco-systemes',1,1),(36,'ES-A-F','Jouets et equipements de loisirs et de sports < 0,5 kg',0.04200000,'Eco-systemes',1,1),(37,'ES-A-G','Jouets et equipements de loisirs et de sports > 0,5 kg',0.17000000,'Eco-systemes',1,1),(38,'ES-A-H','Jouets et equipements de loisirs et de sports > 10 kg',1.25000000,'Eco-systemes',1,1);
/*!40000 ALTER TABLE `llx_c_ecotaxe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_effectif`
--

DROP TABLE IF EXISTS `llx_c_effectif`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_effectif` (
  `id` int(11) NOT NULL,
  `code` varchar(12) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_c_effectif` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_effectif`
--

LOCK TABLES `llx_c_effectif` WRITE;
/*!40000 ALTER TABLE `llx_c_effectif` DISABLE KEYS */;
INSERT INTO `llx_c_effectif` VALUES (0,'EF0','-',1,NULL),(1,'EF1-5','1 - 5',1,NULL),(2,'EF6-10','6 - 10',1,NULL),(3,'EF11-50','11 - 50',1,NULL),(4,'EF51-100','51 - 100',1,NULL),(5,'EF100-500','100 - 500',1,NULL),(6,'EF500-','> 500',1,NULL);
/*!40000 ALTER TABLE `llx_c_effectif` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_field_list`
--

DROP TABLE IF EXISTS `llx_c_field_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_field_list` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `element` varchar(64) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `name` varchar(32) NOT NULL,
  `alias` varchar(32) NOT NULL,
  `title` varchar(32) NOT NULL,
  `align` varchar(6) DEFAULT 'left',
  `sort` tinyint(4) NOT NULL DEFAULT '1',
  `search` tinyint(4) NOT NULL DEFAULT '0',
  `enabled` varchar(255) DEFAULT '1',
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_field_list`
--

LOCK TABLES `llx_c_field_list` WRITE;
/*!40000 ALTER TABLE `llx_c_field_list` DISABLE KEYS */;
INSERT INTO `llx_c_field_list` VALUES (1,'2011-02-06 11:18:30','product_default',1,'p.ref','ref','Ref','left',1,1,'1',1),(2,'2011-02-06 11:18:30','product_default',1,'p.label','label','Label','left',1,1,'1',2),(3,'2011-02-06 11:18:30','product_default',1,'p.barcode','barcode','BarCode','center',1,1,'$conf->barcode->enabled',3),(4,'2011-02-06 11:18:30','product_default',1,'p.tms','datem','DateModification','center',1,0,'1',4),(5,'2011-02-06 11:18:30','product_default',1,'p.price','price','SellingPriceHT','right',1,0,'1',5),(6,'2011-02-06 11:18:30','product_default',1,'p.price_ttc','price_ttc','SellingPriceTTC','right',1,0,'1',6),(7,'2011-02-06 11:18:30','product_default',1,'p.stock','stock','Stock','right',0,0,'$conf->stock->enabled',7),(8,'2011-02-06 11:18:30','product_default',1,'p.envente','status','Status','right',1,0,'1',8);
/*!40000 ALTER TABLE `llx_c_field_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_forme_juridique`
--

DROP TABLE IF EXISTS `llx_c_forme_juridique`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_forme_juridique` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` int(11) NOT NULL,
  `fk_pays` int(11) NOT NULL,
  `libelle` varchar(255) DEFAULT NULL,
  `isvatexempted` tinyint(4) NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_forme_juridique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=100008 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_forme_juridique`
--

LOCK TABLES `llx_c_forme_juridique` WRITE;
/*!40000 ALTER TABLE `llx_c_forme_juridique` DISABLE KEYS */;
INSERT INTO `llx_c_forme_juridique` VALUES (399,0,0,'-',0,1,NULL),(400,2301,23,'Monotributista',0,1,NULL),(401,2302,23,'Sociedad Civil',0,1,NULL),(402,2303,23,'Sociedades Comerciales',0,1,NULL),(403,2304,23,'Sociedades de Hecho',0,1,NULL),(404,2305,23,'Sociedades Irregulares',0,1,NULL),(405,2306,23,'Sociedad Colectiva',0,1,NULL),(406,2307,23,'Sociedad en Comandita Simple',0,1,NULL),(407,2308,23,'Sociedad de Capital e Industria',0,1,NULL),(408,2309,23,'Sociedad Accidental o en participación',0,1,NULL),(409,2310,23,'Sociedad de Responsabilidad Limitada',0,1,NULL),(410,2311,23,'Sociedad Anónima',0,1,NULL),(411,2312,23,'Sociedad Anónima con Participación Estatal Mayoritaria',0,1,NULL),(412,2313,23,'Sociedad en Comandita por Acciones (arts. 315 a 324, LSC)',0,1,NULL),(413,11,1,'Artisan Commerçant (EI)',0,1,NULL),(414,12,1,'Commerçant (EI)',0,1,NULL),(415,13,1,'Artisan (EI)',0,1,NULL),(416,14,1,'Officier public ou ministériel',0,1,NULL),(417,15,1,'Profession libérale (EI)',0,1,NULL),(418,16,1,'Exploitant agricole',0,1,NULL),(419,17,1,'Agent commercial',0,1,NULL),(420,18,1,'Associé Gérant de société',0,1,NULL),(421,19,1,'Personne physique',0,1,NULL),(422,21,1,'Indivision',0,1,NULL),(423,22,1,'Société créée de fait',0,1,NULL),(424,23,1,'Société en participation',0,1,NULL),(425,27,1,'Paroisse hors zone concordataire',0,1,NULL),(426,29,1,'Groupement de droit privé non doté de la personnalité morale',0,1,NULL),(427,31,1,'Personne morale de droit étranger, immatriculée au RCS',0,1,NULL),(428,32,1,'Personne morale de droit étranger, non immatriculée au RCS',0,1,NULL),(429,35,1,'Régime auto-entrepreneur',0,1,NULL),(430,41,1,'Établissement public ou régie à caractère industriel ou commercial',0,1,NULL),(431,51,1,'Société coopérative commerciale particulière',0,1,NULL),(432,52,1,'Société en nom collectif',0,1,NULL),(433,53,1,'Société en commandite',0,1,NULL),(434,54,1,'Société à responsabilité limitée (SARL)',0,1,NULL),(435,55,1,'Société anonyme à conseil d administration',0,1,NULL),(436,56,1,'Société anonyme à directoire',0,1,NULL),(437,57,1,'Société par actions simplifiée',0,1,NULL),(438,58,1,'Entreprise Unipersonnelle à Responsabilité Limitée (EURL)',0,1,NULL),(439,61,1,'Caisse d\'épargne et de prévoyance',0,1,NULL),(440,62,1,'Groupement d\'intérêt économique (GIE)',0,1,NULL),(441,63,1,'Société coopérative agricole',0,1,NULL),(442,64,1,'Société non commerciale d assurances',0,1,NULL),(443,65,1,'Société civile',0,1,NULL),(444,69,1,'Personnes de droit privé inscrites au RCS',0,1,NULL),(445,71,1,'Administration de l état',0,1,NULL),(446,72,1,'Collectivité territoriale',0,1,NULL),(447,73,1,'Établissement public administratif',0,1,NULL),(448,74,1,'Personne morale de droit public administratif',0,1,NULL),(449,81,1,'Organisme gérant régime de protection social à adhésion obligatoire',0,1,NULL),(450,82,1,'Organisme mutualiste',0,1,NULL),(451,83,1,'Comité d entreprise',0,1,NULL),(452,84,1,'Organisme professionnel',0,1,NULL),(453,85,1,'Organisme de retraite à adhésion non obligatoire',0,1,NULL),(454,91,1,'Syndicat de propriétaires',0,1,NULL),(455,92,1,'Association loi 1901 ou assimilé',0,1,NULL),(456,93,1,'Fondation',0,1,NULL),(457,99,1,'Personne morale de droit privé',0,1,NULL),(458,200,2,'Indépendant',0,1,NULL),(459,201,2,'SPRL - Société à responsabilité limitée',0,1,NULL),(460,202,2,'SA   - Société Anonyme',0,1,NULL),(461,203,2,'SCRL - Société coopérative à responsabilité limitée',0,1,NULL),(462,204,2,'ASBL - Association sans but Lucratif',0,1,NULL),(463,205,2,'SCRI - Société coopérative à responsabilité illimitée',0,1,NULL),(464,206,2,'SCS  - Société en commandite simple',0,1,NULL),(465,207,2,'SCA  - Société en commandite par action',0,1,NULL),(466,208,2,'SNC  - Société en nom collectif',0,1,NULL),(467,209,2,'GIE  - Groupement d intérêt économique',0,1,NULL),(468,210,2,'GEIE - Groupement européen d intérêt économique',0,1,NULL),(469,500,5,'Limited liability corporation (GmbH)',0,1,NULL),(470,501,5,'Stock corporation (AG)',0,1,NULL),(471,502,5,'Partnerships general or limited (GmbH & CO. KG)',0,1,NULL),(472,503,5,'Sole proprietor / Private business',0,1,NULL),(473,301,3,'Società semplice',0,1,NULL),(474,302,3,'Società in nome collettivo s.n.c.',0,1,NULL),(475,303,3,'Società in accomandita semplice s.a.s.',0,1,NULL),(476,304,3,'Società per azioni s.p.a.',0,1,NULL),(477,305,3,'Società a responsabilità limitata s.r.l.',0,1,NULL),(478,306,3,'Società in accomandita per azioni s.a.p.a.',0,1,NULL),(479,307,3,'Società cooperativa',0,1,NULL),(480,308,3,'Società consortile',0,1,NULL),(481,309,3,'Società europea',0,1,NULL),(482,310,3,'Società cooperativa europea',0,1,NULL),(483,311,3,'Società unipersonale',0,1,NULL),(484,312,3,'Società di professionisti',0,1,NULL),(485,313,3,'Società di fatto',0,1,NULL),(486,314,3,'Società occulta',0,1,NULL),(487,315,3,'Società apparente',0,1,NULL),(488,316,3,'Impresa individuale ',0,1,NULL),(489,317,3,'Impresa coniugale',0,1,NULL),(490,318,3,'Impresa familiare',0,1,NULL),(491,600,6,'Raison Individuelle',0,1,NULL),(492,601,6,'Société Simple',0,1,NULL),(493,602,6,'Société en nom collectif',0,1,NULL),(494,603,6,'Société en commandite',0,1,NULL),(495,604,6,'Société anonyme (SA)',0,1,NULL),(496,605,6,'Société en commandite par actions',0,1,NULL),(497,606,6,'Société à responsabilité limitée (SARL)',0,1,NULL),(498,607,6,'Société coopérative',0,1,NULL),(499,608,6,'Association',0,1,NULL),(500,609,6,'Fondation',0,1,NULL),(501,700,7,'Sole Trader',0,1,NULL),(502,701,7,'Partnership',0,1,NULL),(503,702,7,'Private Limited Company by shares (LTD)',0,1,NULL),(504,703,7,'Public Limited Company',0,1,NULL),(505,704,7,'Workers Cooperative',0,1,NULL),(506,705,7,'Limited Liability Partnership',0,1,NULL),(507,706,7,'Franchise',0,1,NULL),(508,1000,10,'Société à responsabilité limitée (SARL)',0,1,NULL),(509,1001,10,'Société en Nom Collectif (SNC)',0,1,NULL),(510,1002,10,'Société en Commandite Simple (SCS)',0,1,NULL),(511,1003,10,'société en participation',0,1,NULL),(512,1004,10,'Société Anonyme (SA)',0,1,NULL),(513,1005,10,'Société Unipersonnelle à Responsabilité Limitée (SUARL)',0,1,NULL),(514,1006,10,'Groupement d\'intérêt économique (GEI)',0,1,NULL),(515,1007,10,'Groupe de sociétés',0,1,NULL),(516,401,4,'Empresario Individual',0,1,NULL),(517,402,4,'Comunidad de Bienes',0,1,NULL),(518,403,4,'Sociedad Civil',0,1,NULL),(519,404,4,'Sociedad Colectiva',0,1,NULL),(520,405,4,'Sociedad Limitada',0,1,NULL),(521,406,4,'Sociedad Anónima',0,1,NULL),(522,407,4,'Sociedad Comandataria por Acciones',0,1,NULL),(523,408,4,'Sociedad Comandataria Simple',0,1,NULL),(524,409,4,'Sociedad Laboral',0,1,NULL),(525,410,4,'Sociedad Cooperativa',0,1,NULL),(526,411,4,'Sociedad de Garantía Recíproca',0,1,NULL),(527,412,4,'Entidad de Capital-Riesgo',0,1,NULL),(528,413,4,'Agrupación de Interés Económico',0,1,NULL),(529,414,4,'Sociedad de Inversión Mobiliaria',0,1,NULL),(530,415,4,'Agrupación sin Ánimo de Lucro',0,1,NULL),(531,15201,152,'Mauritius Private Company Limited By Shares',0,1,NULL),(532,15202,152,'Mauritius Company Limited By Guarantee',0,1,NULL),(533,15203,152,'Mauritius Public Company Limited By Shares',0,1,NULL),(534,15204,152,'Mauritius Foreign Company',0,1,NULL),(535,15205,152,'Mauritius GBC1 (Offshore Company)',0,1,NULL),(536,15206,152,'Mauritius GBC2 (International Company)',0,1,NULL),(537,15207,152,'Mauritius General Partnership',0,1,NULL),(538,15208,152,'Mauritius Limited Partnership',0,1,NULL),(539,15209,152,'Mauritius Sole Proprietorship',0,1,NULL),(540,15210,152,'Mauritius Trusts',0,1,NULL),(541,15401,154,'Sociedad en nombre colectivo',0,1,NULL),(542,15402,154,'Sociedad en comandita simple',0,1,NULL),(543,15403,154,'Sociedad de responsabilidad limitada',0,1,NULL),(544,15404,154,'Sociedad anónima',0,1,NULL),(545,15405,154,'Sociedad en comandita por acciones',0,1,NULL),(546,15406,154,'Sociedad cooperativa',0,1,NULL),(100001,100001,1,'Etudiant',0,0,'cabinetmed'),(100002,100002,1,'Retraité',0,0,'cabinetmed'),(100003,100003,1,'Artisan',0,0,'cabinetmed'),(100004,100004,1,'Femme de ménage',0,0,'cabinetmed'),(100005,100005,1,'Professeur',0,0,'cabinetmed'),(100006,100006,1,'Profession libérale',0,0,'cabinetmed'),(100007,100007,1,'Informaticien',0,0,'cabinetmed');
/*!40000 ALTER TABLE `llx_c_forme_juridique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_input_method`
--

DROP TABLE IF EXISTS `llx_c_input_method`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_input_method` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) DEFAULT NULL,
  `libelle` varchar(60) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_methode_commande_fournisseur` (`code`),
  UNIQUE KEY `uk_c_input_method` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_input_method`
--

LOCK TABLES `llx_c_input_method` WRITE;
/*!40000 ALTER TABLE `llx_c_input_method` DISABLE KEYS */;
INSERT INTO `llx_c_input_method` VALUES (1,'OrderByMail','Courrier',1,NULL),(2,'OrderByFax','Fax',1,NULL),(3,'OrderByEMail','EMail',1,NULL),(4,'OrderByPhone','Téléphone',1,NULL),(5,'OrderByWWW','En ligne',1,NULL);
/*!40000 ALTER TABLE `llx_c_input_method` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_input_reason`
--

DROP TABLE IF EXISTS `llx_c_input_reason`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_input_reason` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `label` varchar(60) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_input_reason` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_input_reason`
--

LOCK TABLES `llx_c_input_reason` WRITE;
/*!40000 ALTER TABLE `llx_c_input_reason` DISABLE KEYS */;
INSERT INTO `llx_c_input_reason` VALUES (1,'SRC_INTE','Web site',1,NULL),(2,'SRC_CAMP_MAIL','Mailing campaign',1,NULL),(3,'SRC_CAMP_PHO','Phone campaign',1,NULL),(4,'SRC_CAMP_FAX','Fax campaign',1,NULL),(5,'SRC_COMM','Commercial contact',1,NULL),(6,'SRC_SHOP','Shop contact',1,NULL),(7,'SRC_CAMP_EMAIL','EMailing campaign',1,NULL),(8,'SRC_WOM','Word of mouth',1,NULL),(9,'SRC_PARTNER','Partner',1,NULL),(10,'SRC_EMPLOYEE','Employee',1,NULL),(11,'SRC_SPONSORING','Sponsoring',1,NULL);
/*!40000 ALTER TABLE `llx_c_input_reason` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_methode_commande_fournisseur`
--

DROP TABLE IF EXISTS `llx_c_methode_commande_fournisseur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_methode_commande_fournisseur` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) DEFAULT NULL,
  `libelle` varchar(60) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_methode_commande_fournisseur` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_methode_commande_fournisseur`
--

LOCK TABLES `llx_c_methode_commande_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_c_methode_commande_fournisseur` DISABLE KEYS */;
INSERT INTO `llx_c_methode_commande_fournisseur` VALUES (1,'OrderByMail','Courrier',1),(2,'OrderByFax','Fax',1),(3,'OrderByEMail','EMail',1),(4,'OrderByPhone','Téléphone',1),(5,'OrderByWWW','En ligne',1);
/*!40000 ALTER TABLE `llx_c_methode_commande_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_paiement`
--

DROP TABLE IF EXISTS `llx_c_paiement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_paiement` (
  `id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `type` smallint(6) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_c_paiement` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_paiement`
--

LOCK TABLES `llx_c_paiement` WRITE;
/*!40000 ALTER TABLE `llx_c_paiement` DISABLE KEYS */;
INSERT INTO `llx_c_paiement` VALUES (0,'','-',3,1,NULL),(1,'TIP','TIP',2,1,NULL),(2,'VIR','Virement',2,1,NULL),(3,'PRE','Prélèvement',2,1,NULL),(4,'LIQ','Espèces',2,1,NULL),(6,'CB','Carte Bancaire',2,1,NULL),(7,'CHQ','Chèque',2,1,NULL),(50,'VAD','Paiement en ligne',2,0,NULL),(51,'TRA','Traite',2,0,NULL),(52,'LCR','LCR',2,0,NULL),(53,'FAC','Factor',2,0,NULL),(54,'PRO','Proforma',2,0,NULL);
/*!40000 ALTER TABLE `llx_c_paiement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_paper_format`
--

DROP TABLE IF EXISTS `llx_c_paper_format`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_paper_format` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(16) NOT NULL,
  `label` varchar(50) NOT NULL,
  `width` float(6,2) DEFAULT '0.00',
  `height` float(6,2) DEFAULT '0.00',
  `unit` varchar(5) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=226 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_paper_format`
--

LOCK TABLES `llx_c_paper_format` WRITE;
/*!40000 ALTER TABLE `llx_c_paper_format` DISABLE KEYS */;
INSERT INTO `llx_c_paper_format` VALUES (1,'EU4A0','Format 4A0',1682.00,2378.00,'mm',1,NULL),(2,'EU2A0','Format 2A0',1189.00,1682.00,'mm',1,NULL),(3,'EUA0','Format A0',840.00,1189.00,'mm',1,NULL),(4,'EUA1','Format A1',594.00,840.00,'mm',1,NULL),(5,'EUA2','Format A2',420.00,594.00,'mm',1,NULL),(6,'EUA3','Format A3',297.00,420.00,'mm',1,NULL),(7,'EUA4','Format A4',210.00,297.00,'mm',1,NULL),(8,'EUA5','Format A5',148.00,210.00,'mm',1,NULL),(9,'EUA6','Format A6',105.00,148.00,'mm',1,NULL),(100,'USLetter','Format Letter (A)',216.00,279.00,'mm',1,NULL),(105,'USLegal','Format Legal',216.00,356.00,'mm',1,NULL),(110,'USExecutive','Format Executive',190.00,254.00,'mm',1,NULL),(115,'USLedger','Format Ledger/Tabloid (B)',279.00,432.00,'mm',1,NULL),(200,'CAP1','Format Canadian P1',560.00,860.00,'mm',1,NULL),(205,'CAP2','Format Canadian P2',430.00,560.00,'mm',1,NULL),(210,'CAP3','Format Canadian P3',280.00,430.00,'mm',1,NULL),(215,'CAP4','Format Canadian P4',215.00,280.00,'mm',1,NULL),(220,'CAP5','Format Canadian P5',140.00,215.00,'mm',1,NULL),(225,'CAP6','Format Canadian P6',107.00,140.00,'mm',1,NULL);
/*!40000 ALTER TABLE `llx_c_paper_format` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_payment_term`
--

DROP TABLE IF EXISTS `llx_c_payment_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_payment_term` (
  `rowid` int(11) NOT NULL,
  `code` varchar(16) DEFAULT NULL,
  `sortorder` smallint(6) DEFAULT NULL,
  `active` tinyint(4) DEFAULT '1',
  `libelle` varchar(255) DEFAULT NULL,
  `libelle_facture` text,
  `fdm` tinyint(4) DEFAULT NULL,
  `nbjour` smallint(6) DEFAULT NULL,
  `decalage` smallint(6) DEFAULT NULL,
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_payment_term`
--

LOCK TABLES `llx_c_payment_term` WRITE;
/*!40000 ALTER TABLE `llx_c_payment_term` DISABLE KEYS */;
INSERT INTO `llx_c_payment_term` VALUES (1,'RECEP',1,1,'A réception','Réception de facture',0,0,NULL,NULL),(2,'30D',2,1,'30 jours','Réglement à 30 jours',0,30,NULL,NULL),(3,'30DENDMONTH',3,1,'30 jours fin de mois','Réglement à 30 jours fin de mois',1,30,NULL,NULL),(4,'60D',4,1,'60 jours','Réglement à 60 jours',0,60,NULL,NULL),(5,'60DENDMONTH',5,1,'60 jours fin de mois','Réglement à 60 jours fin de mois',1,60,NULL,NULL);
/*!40000 ALTER TABLE `llx_c_payment_term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_pays`
--

DROP TABLE IF EXISTS `llx_c_pays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_pays` (
  `rowid` int(11) NOT NULL,
  `code` varchar(2) NOT NULL,
  `code_iso` varchar(3) DEFAULT NULL,
  `libelle` varchar(50) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_c_pays_code` (`code`),
  UNIQUE KEY `idx_c_pays_libelle` (`libelle`),
  UNIQUE KEY `idx_c_pays_code_iso` (`code_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_pays`
--

LOCK TABLES `llx_c_pays` WRITE;
/*!40000 ALTER TABLE `llx_c_pays` DISABLE KEYS */;
INSERT INTO `llx_c_pays` VALUES (0,'',NULL,'-',1),(1,'FR',NULL,'France',1),(2,'BE',NULL,'Belgium',1),(3,'IT',NULL,'Italy',1),(4,'ES',NULL,'Spain',1),(5,'DE',NULL,'Germany',1),(6,'CH',NULL,'Suisse',1),(7,'GB',NULL,'United Kingdow',1),(8,'IE',NULL,'Irland',1),(9,'CN',NULL,'China',1),(10,'TN',NULL,'Tunisie',1),(11,'US',NULL,'United States',1),(12,'MA',NULL,'Maroc',1),(13,'DZ',NULL,'Algérie',1),(14,'CA',NULL,'Canada',1),(15,'TG',NULL,'Togo',1),(16,'GA',NULL,'Gabon',1),(17,'NL',NULL,'Nerderland',1),(18,'HU',NULL,'Hongrie',1),(19,'RU',NULL,'Russia',1),(20,'SE',NULL,'Sweden',1),(21,'CI',NULL,'Côte d\'Ivoire',1),(22,'SN',NULL,'Sénégal',1),(23,'AR',NULL,'Argentine',1),(24,'CM',NULL,'Cameroun',1),(25,'PT',NULL,'Portugal',1),(26,'SA',NULL,'Arabie Saoudite',1),(27,'MC',NULL,'Monaco',1),(28,'AU',NULL,'Australia',1),(29,'SG',NULL,'Singapour',1),(30,'AF',NULL,'Afghanistan',1),(31,'AX',NULL,'Iles Aland',1),(32,'AL',NULL,'Albanie',1),(33,'AS',NULL,'Samoa américaines',1),(34,'AD',NULL,'Andorre',1),(35,'AO',NULL,'Angola',1),(36,'AI',NULL,'Anguilla',1),(37,'AQ',NULL,'Antarctique',1),(38,'AG',NULL,'Antigua-et-Barbuda',1),(39,'AM',NULL,'Arménie',1),(40,'AW',NULL,'Aruba',1),(41,'AT',NULL,'Autriche',1),(42,'AZ',NULL,'Azerbaïdjan',1),(43,'BS',NULL,'Bahamas',1),(44,'BH',NULL,'Bahreïn',1),(45,'BD',NULL,'Bangladesh',1),(46,'BB',NULL,'Barbade',1),(47,'BY',NULL,'Biélorussie',1),(48,'BZ',NULL,'Belize',1),(49,'BJ',NULL,'Bénin',1),(50,'BM',NULL,'Bermudes',1),(51,'BT',NULL,'Bhoutan',1),(52,'BO',NULL,'Bolivie',1),(53,'BA',NULL,'Bosnie-Herzégovine',1),(54,'BW',NULL,'Botswana',1),(55,'BV',NULL,'Ile Bouvet',1),(56,'BR',NULL,'Brésil',1),(57,'IO',NULL,'Territoire britannique de l\'Océan Indien',1),(58,'BN',NULL,'Brunei',1),(59,'BG',NULL,'Bulgarie',1),(60,'BF',NULL,'Burkina Faso',1),(61,'BI',NULL,'Burundi',1),(62,'KH',NULL,'Cambodge',1),(63,'CV',NULL,'Cap-Vert',1),(64,'KY',NULL,'Iles Cayman',1),(65,'CF',NULL,'République centrafricaine',1),(66,'TD',NULL,'Tchad',1),(67,'CL',NULL,'Chili',1),(68,'CX',NULL,'Ile Christmas',1),(69,'CC',NULL,'Iles des Cocos (Keeling)',1),(70,'CO',NULL,'Colombie',1),(71,'KM',NULL,'Comores',1),(72,'CG',NULL,'Congo',1),(73,'CD',NULL,'République démocratique du Congo',1),(74,'CK',NULL,'Iles Cook',1),(75,'CR',NULL,'Costa Rica',1),(76,'HR',NULL,'Croatie',1),(77,'CU',NULL,'Cuba',1),(78,'CY',NULL,'Chypre',1),(79,'CZ',NULL,'République Tchèque',1),(80,'DK',NULL,'Danemark',1),(81,'DJ',NULL,'Djibouti',1),(82,'DM',NULL,'Dominique',1),(83,'DO',NULL,'République Dominicaine',1),(84,'EC',NULL,'Equateur',1),(85,'EG',NULL,'Egypte',1),(86,'SV',NULL,'Salvador',1),(87,'GQ',NULL,'Guinée Equatoriale',1),(88,'ER',NULL,'Erythrée',1),(89,'EE',NULL,'Estonie',1),(90,'ET',NULL,'Ethiopie',1),(91,'FK',NULL,'Iles Falkland',1),(92,'FO',NULL,'Iles Féroé',1),(93,'FJ',NULL,'Iles Fidji',1),(94,'FI',NULL,'Finlande',1),(95,'GF',NULL,'Guyane française',1),(96,'PF',NULL,'Polynésie française',1),(97,'TF',NULL,'Terres australes françaises',1),(98,'GM',NULL,'Gambie',1),(99,'GE',NULL,'Géorgie',1),(100,'GH',NULL,'Ghana',1),(101,'GI',NULL,'Gibraltar',1),(102,'GR',NULL,'Grèce',1),(103,'GL',NULL,'Groenland',1),(104,'GD',NULL,'Grenade',1),(105,'GP',NULL,'Guadeloupe',1),(106,'GU',NULL,'Guam',1),(107,'GT',NULL,'Guatemala',1),(108,'GN',NULL,'Guinée',1),(109,'GW',NULL,'Guinée-Bissao',1),(110,'GY',NULL,'Guyana',1),(111,'HT',NULL,'Haiti',1),(112,'HM',NULL,'Iles Heard et McDonald',1),(113,'VA',NULL,'Saint-Siège (Vatican)',1),(114,'HN',NULL,'Honduras',1),(115,'HK',NULL,'Hong Kong',1),(116,'IS',NULL,'Islande',1),(117,'IN',NULL,'India',1),(118,'ID',NULL,'Indonésie',1),(119,'IR',NULL,'Iran',1),(120,'IQ',NULL,'Iraq',1),(121,'IL',NULL,'Israel',1),(122,'JM',NULL,'Jamaïque',1),(123,'JP',NULL,'Japon',1),(124,'JO',NULL,'Jordanie',1),(125,'KZ',NULL,'Kazakhstan',1),(126,'KE',NULL,'Kenya',1),(127,'KI',NULL,'Kiribati',1),(128,'KP',NULL,'Corée du Nord',1),(129,'KR',NULL,'Corée du Sud',1),(130,'KW',NULL,'Koweït',1),(131,'KG',NULL,'Kirghizistan',1),(132,'LA',NULL,'Laos',1),(133,'LV',NULL,'Lettonie',1),(134,'LB',NULL,'Liban',1),(135,'LS',NULL,'Lesotho',1),(136,'LR',NULL,'Liberia',1),(137,'LY',NULL,'Libye',1),(138,'LI',NULL,'Liechtenstein',1),(139,'LT',NULL,'Lituanie',1),(140,'LU',NULL,'Luxembourg',1),(141,'MO',NULL,'Macao',1),(142,'MK',NULL,'ex-République yougoslave de Macédoine',1),(143,'MG',NULL,'Madagascar',1),(144,'MW',NULL,'Malawi',1),(145,'MY',NULL,'Malaisie',1),(146,'MV',NULL,'Maldives',1),(147,'ML',NULL,'Mali',1),(148,'MT',NULL,'Malte',1),(149,'MH',NULL,'Iles Marshall',1),(150,'MQ',NULL,'Martinique',1),(151,'MR',NULL,'Mauritanie',1),(152,'MU',NULL,'Maurice',1),(153,'YT',NULL,'Mayotte',1),(154,'MX',NULL,'Mexique',1),(155,'FM',NULL,'Micronésie',1),(156,'MD',NULL,'Moldavie',1),(157,'MN',NULL,'Mongolie',1),(158,'MS',NULL,'Monserrat',1),(159,'MZ',NULL,'Mozambique',1),(160,'MM',NULL,'Birmanie (Myanmar)',1),(161,'NA',NULL,'Namibie',1),(162,'NR',NULL,'Nauru',1),(163,'NP',NULL,'Népal',1),(164,'AN',NULL,'Antilles néerlandaises',1),(165,'NC',NULL,'Nouvelle-Calédonie',1),(166,'NZ',NULL,'Nouvelle-Zélande',1),(167,'NI',NULL,'Nicaragua',1),(168,'NE',NULL,'Niger',1),(169,'NG',NULL,'Nigeria',1),(170,'NU',NULL,'Nioué',1),(171,'NF',NULL,'Ile Norfolk',1),(172,'MP',NULL,'Mariannes du Nord',1),(173,'NO',NULL,'Norvège',1),(174,'OM',NULL,'Oman',1),(175,'PK',NULL,'Pakistan',1),(176,'PW',NULL,'Palaos',1),(177,'PS',NULL,'territoire Palestinien Occupé',1),(178,'PA',NULL,'Panama',1),(179,'PG',NULL,'Papouasie-Nouvelle-Guinée',1),(180,'PY',NULL,'Paraguay',1),(181,'PE',NULL,'Pérou',1),(182,'PH',NULL,'Philippines',1),(183,'PN',NULL,'Iles Pitcairn',1),(184,'PL',NULL,'Pologne',1),(185,'PR',NULL,'Porto Rico',1),(186,'QA',NULL,'Qatar',1),(187,'RE',NULL,'Réunion',1),(188,'RO',NULL,'Roumanie',1),(189,'RW',NULL,'Rwanda',1),(190,'SH',NULL,'Sainte-Hélène',1),(191,'KN',NULL,'Saint-Christophe-et-Niévès',1),(192,'LC',NULL,'Sainte-Lucie',1),(193,'PM',NULL,'Saint-Pierre-et-Miquelon',1),(194,'VC',NULL,'Saint-Vincent-et-les-Grenadines',1),(195,'WS',NULL,'Samoa',1),(196,'SM',NULL,'Saint-Marin',1),(197,'ST',NULL,'Sao Tomé-et-Principe',1),(198,'RS',NULL,'Serbie',1),(199,'SC',NULL,'Seychelles',1),(200,'SL',NULL,'Sierra Leone',1),(201,'SK',NULL,'Slovaquie',1),(202,'SI',NULL,'Slovénie',1),(203,'SB',NULL,'Iles Salomon',1),(204,'SO',NULL,'Somalie',1),(205,'ZA',NULL,'Afrique du Sud',1),(206,'GS',NULL,'Iles Géorgie du Sud et Sandwich du Sud',1),(207,'LK',NULL,'Sri Lanka',1),(208,'SD',NULL,'Soudan',1),(209,'SR',NULL,'Suriname',1),(210,'SJ',NULL,'Iles Svalbard et Jan Mayen',1),(211,'SZ',NULL,'Swaziland',1),(212,'SY',NULL,'Syrie',1),(213,'TW',NULL,'Taïwan',1),(214,'TJ',NULL,'Tadjikistan',1),(215,'TZ',NULL,'Tanzanie',1),(216,'TH',NULL,'Thaïlande',1),(217,'TL',NULL,'Timor Oriental',1),(218,'TK',NULL,'Tokélaou',1),(219,'TO',NULL,'Tonga',1),(220,'TT',NULL,'Trinité-et-Tobago',1),(221,'TR',NULL,'Turquie',1),(222,'TM',NULL,'Turkménistan',1),(223,'TC',NULL,'Iles Turks-et-Caicos',1),(224,'TV',NULL,'Tuvalu',1),(225,'UG',NULL,'Ouganda',1),(226,'UA',NULL,'Ukraine',1),(227,'AE',NULL,'Émirats arabes unis',1),(228,'UM',NULL,'Iles mineures éloignées des États-Unis',1),(229,'UY',NULL,'Uruguay',1),(230,'UZ',NULL,'Ouzbékistan',1),(231,'VU',NULL,'Vanuatu',1),(232,'VE',NULL,'Vénézuela',1),(233,'VN',NULL,'Viêt Nam',1),(234,'VG',NULL,'Iles Vierges britanniques',1),(235,'VI',NULL,'Iles Vierges américaines',1),(236,'WF',NULL,'Wallis-et-Futuna',1),(237,'EH',NULL,'Sahara occidental',1),(238,'YE',NULL,'Yémen',1),(239,'ZM',NULL,'Zambie',1),(240,'ZW',NULL,'Zimbabwe',1),(241,'GG',NULL,'Guernesey',1),(242,'IM',NULL,'Ile de Man',1),(243,'JE',NULL,'Jersey',1),(244,'ME',NULL,'Monténégro',1),(245,'BL',NULL,'Saint-Barthélemy',1),(246,'MF',NULL,'Saint-Martin',1);
/*!40000 ALTER TABLE `llx_c_pays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_propalst`
--

DROP TABLE IF EXISTS `llx_c_propalst`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_propalst` (
  `id` smallint(6) NOT NULL,
  `code` varchar(12) NOT NULL,
  `label` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_c_propalst` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_propalst`
--

LOCK TABLES `llx_c_propalst` WRITE;
/*!40000 ALTER TABLE `llx_c_propalst` DISABLE KEYS */;
INSERT INTO `llx_c_propalst` VALUES (0,'PR_DRAFT','Brouillon',1),(1,'PR_OPEN','Ouverte',1),(2,'PR_SIGNED','Signée',1),(3,'PR_NOTSIGNED','Non Signée',1),(4,'PR_FAC','Facturée',1);
/*!40000 ALTER TABLE `llx_c_propalst` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_prospectlevel`
--

DROP TABLE IF EXISTS `llx_c_prospectlevel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_prospectlevel` (
  `code` varchar(12) NOT NULL,
  `label` varchar(30) DEFAULT NULL,
  `sortorder` smallint(6) DEFAULT NULL,
  `active` smallint(6) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_prospectlevel`
--

LOCK TABLES `llx_c_prospectlevel` WRITE;
/*!40000 ALTER TABLE `llx_c_prospectlevel` DISABLE KEYS */;
INSERT INTO `llx_c_prospectlevel` VALUES ('PL_HIGH','High',4,1,NULL),('PL_LOW','Low',2,1,NULL),('PL_MEDIUM','Medium',3,1,NULL),('PL_NONE','None',1,1,NULL);
/*!40000 ALTER TABLE `llx_c_prospectlevel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_regions`
--

DROP TABLE IF EXISTS `llx_c_regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_regions` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code_region` int(11) NOT NULL,
  `fk_pays` int(11) NOT NULL,
  `cheflieu` varchar(50) DEFAULT NULL,
  `tncc` int(11) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `code_region` (`code_region`),
  KEY `idx_c_regions_fk_pays` (`fk_pays`),
  CONSTRAINT `fk_c_regions_fk_pays` FOREIGN KEY (`fk_pays`) REFERENCES `llx_c_pays` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=23210 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_regions`
--

LOCK TABLES `llx_c_regions` WRITE;
/*!40000 ALTER TABLE `llx_c_regions` DISABLE KEYS */;
INSERT INTO `llx_c_regions` VALUES (1,0,0,'0',0,'-',1),(101,1,1,'97105',3,'Guadeloupe',1),(102,2,1,'97209',3,'Martinique',1),(103,3,1,'97302',3,'Guyane',1),(104,4,1,'97411',3,'Réunion',1),(105,11,1,'75056',1,'Île-de-France',1),(106,21,1,'51108',0,'Champagne-Ardenne',1),(107,22,1,'80021',0,'Picardie',1),(108,23,1,'76540',0,'Haute-Normandie',1),(109,24,1,'45234',2,'Centre',1),(110,25,1,'14118',0,'Basse-Normandie',1),(111,26,1,'21231',0,'Bourgogne',1),(112,31,1,'59350',2,'Nord-Pas-de-Calais',1),(113,41,1,'57463',0,'Lorraine',1),(114,42,1,'67482',1,'Alsace',1),(115,43,1,'25056',0,'Franche-Comté',1),(116,52,1,'44109',4,'Pays de la Loire',1),(117,53,1,'35238',0,'Bretagne',1),(118,54,1,'86194',2,'Poitou-Charentes',1),(119,72,1,'33063',1,'Aquitaine',1),(120,73,1,'31555',0,'Midi-Pyrénées',1),(121,74,1,'87085',2,'Limousin',1),(122,82,1,'69123',2,'Rhône-Alpes',1),(123,83,1,'63113',1,'Auvergne',1),(124,91,1,'34172',2,'Languedoc-Roussillon',1),(125,93,1,'13055',0,'Provence-Alpes-Côte d\'Azur',1),(126,94,1,'2A004',0,'Corse',1),(201,201,2,'',1,'Flandre',1),(202,202,2,'',2,'Wallonie',1),(203,203,2,'',3,'Bruxelles-Capitale',1),(301,301,3,NULL,1,'Abruzzo',1),(302,302,3,NULL,1,'Basilicata',1),(303,303,3,NULL,1,'Calabria',1),(304,304,3,NULL,1,'Campania',1),(305,305,3,NULL,1,'Emilia-Romagna',1),(306,306,3,NULL,1,'Friuli-Venezia Giulia',1),(307,307,3,NULL,1,'Lazio',1),(308,308,3,NULL,1,'Liguria',1),(309,309,3,NULL,1,'Lombardia',1),(310,310,3,NULL,1,'Marche',1),(311,311,3,NULL,1,'Molise',1),(312,312,3,NULL,1,'Piemonte',1),(313,313,3,NULL,1,'Puglia',1),(314,314,3,NULL,1,'Sardegna',1),(315,315,3,NULL,1,'Sicilia',1),(316,316,3,NULL,1,'Toscana',1),(317,317,3,NULL,1,'Trentino-Alto Adige',1),(318,318,3,NULL,1,'Umbria',1),(319,319,3,NULL,1,'Valle d Aosta',1),(320,320,3,NULL,1,'Veneto',1),(401,401,4,'',0,'Andalucia',1),(402,402,4,'',0,'Aragón',1),(403,403,4,'',0,'Castilla y León',1),(404,404,4,'',0,'Castilla la Mancha',1),(405,405,4,'',0,'Canarias',1),(406,406,4,'',0,'Cataluña',1),(407,407,4,'',0,'Comunidad de Ceuta',1),(408,408,4,'',0,'Comunidad Foral de Navarra',1),(409,409,4,'',0,'Comunidad de Melilla',1),(410,410,4,'',0,'Cantabria',1),(411,411,4,'',0,'Comunidad Valenciana',1),(412,412,4,'',0,'Extemadura',1),(413,413,4,'',0,'Galicia',1),(414,414,4,'',0,'Islas Baleares',1),(415,415,4,'',0,'La Rioja',1),(416,416,4,'',0,'Comunidad de Madrid',1),(417,417,4,'',0,'Región de Murcia',1),(418,418,4,'',0,'Principado de Asturias',1),(419,419,4,'',0,'Pais Vasco',1),(420,420,4,'',0,'Otros',1),(601,601,6,'',1,'Cantons',1),(1001,1001,10,'',0,'Ariana',1),(1002,1002,10,'',0,'Béja',1),(1003,1003,10,'',0,'Ben Arous',1),(1004,1004,10,'',0,'Bizerte',1),(1005,1005,10,'',0,'Gabès',1),(1006,1006,10,'',0,'Gafsa',1),(1007,1007,10,'',0,'Jendouba',1),(1008,1008,10,'',0,'Kairouan',1),(1009,1009,10,'',0,'Kasserine',1),(1010,1010,10,'',0,'Kébili',1),(1011,1011,10,'',0,'La Manouba',1),(1012,1012,10,'',0,'Le Kef',1),(1013,1013,10,'',0,'Mahdia',1),(1014,1014,10,'',0,'Médenine',1),(1015,1015,10,'',0,'Monastir',1),(1016,1016,10,'',0,'Nabeul',1),(1017,1017,10,'',0,'Sfax',1),(1018,1018,10,'',0,'Sidi Bouzid',1),(1019,1019,10,'',0,'Siliana',1),(1020,1020,10,'',0,'Sousse',1),(1021,1021,10,'',0,'Tataouine',1),(1022,1022,10,'',0,'Tozeur',1),(1023,1023,10,'',0,'Tunis',1),(1024,1024,10,'',0,'Zaghouan',1),(1101,1101,11,'',0,'United-States',1),(1201,1201,12,'',0,'Tanger-Tétouan',1),(1202,1202,12,'',0,'Gharb-Chrarda-Beni Hssen',1),(1203,1203,12,'',0,'Taza-Al Hoceima-Taounate',1),(1204,1204,12,'',0,'L\'Oriental',1),(1205,1205,12,'',0,'Fès-Boulemane',1),(1206,1206,12,'',0,'Meknès-Tafialet',1),(1207,1207,12,'',0,'Rabat-Salé-Zemour-Zaër',1),(1208,1208,12,'',0,'Grand Cassablanca',1),(1209,1209,12,'',0,'Chaouia-Ouardigha',1),(1210,1210,12,'',0,'Doukahla-Adba',1),(1211,1211,12,'',0,'Marrakech-Tensift-Al Haouz',1),(1212,1212,12,'',0,'Tadla-Azilal',1),(1213,1213,12,'',0,'Sous-Massa-Drâa',1),(1214,1214,12,'',0,'Guelmim-Es Smara',1),(1215,1215,12,'',0,'Laâyoune-Boujdour-Sakia el Hamra',1),(1216,1216,12,'',0,'Oued Ed-Dahab Lagouira',1),(1301,1301,13,'',0,'Algerie',1),(2301,2301,23,'',0,'Norte',1),(2302,2302,23,'',0,'Litoral',1),(2303,2303,23,'',0,'Cuyana',1),(2304,2304,23,'',0,'Central',1),(2305,2305,23,'',0,'Patagonia',1),(2801,2801,28,'',0,'Australia',1),(4601,4601,46,'',0,'Barbados',1),(6701,6701,67,NULL,NULL,'Tarapacá',1),(6702,6702,67,NULL,NULL,'Antofagasta',1),(6703,6703,67,NULL,NULL,'Atacama',1),(6704,6704,67,NULL,NULL,'Coquimbo',1),(6705,6705,67,NULL,NULL,'Valparaíso',1),(6706,6706,67,NULL,NULL,'General Bernardo O Higgins',1),(6707,6707,67,NULL,NULL,'Maule',1),(6708,6708,67,NULL,NULL,'Biobío',1),(6709,6709,67,NULL,NULL,'Raucanía',1),(6710,6710,67,NULL,NULL,'Los Lagos',1),(6711,6711,67,NULL,NULL,'Aysén General Carlos Ibáñez del Campo',1),(6712,6712,67,NULL,NULL,'Magallanes y Antártica Chilena',1),(6713,6713,67,NULL,NULL,'Santiago',1),(6714,6714,67,NULL,NULL,'Los Ríos',1),(6715,6715,67,NULL,NULL,'Arica y Parinacota',1),(7001,7001,70,'',0,'Colombie',1),(8601,8601,86,NULL,NULL,'Central',1),(8602,8602,86,NULL,NULL,'Oriental',1),(8603,8603,86,NULL,NULL,'Occidental',1),(10201,10201,102,NULL,NULL,'??????',1),(10202,10202,102,NULL,NULL,'?????? ??????',1),(10203,10203,102,NULL,NULL,'???????? ?????????',1),(10204,10204,102,NULL,NULL,'?????',1),(10205,10205,102,NULL,NULL,'????????? ????????? ??? ?????',1),(10206,10206,102,NULL,NULL,'???????',1),(10207,10207,102,NULL,NULL,'????? ?????',1),(10208,10208,102,NULL,NULL,'?????? ??????',1),(10209,10209,102,NULL,NULL,'????????????',1),(10210,10210,102,NULL,NULL,'????? ??????',1),(10211,10211,102,NULL,NULL,'?????? ??????',1),(10212,10212,102,NULL,NULL,'????????',1),(10213,10213,102,NULL,NULL,'?????? ?????????',1),(11401,11401,114,'',0,'Honduras',1),(11701,11701,117,'',0,'India',1),(15201,15201,152,'',0,'Rivière Noire',1),(15202,15202,152,'',0,'Flacq',1),(15203,15203,152,'',0,'Grand Port',1),(15204,15204,152,'',0,'Moka',1),(15205,15205,152,'',0,'Pamplemousses',1),(15206,15206,152,'',0,'Plaines Wilhems',1),(15207,15207,152,'',0,'Port-Louis',1),(15208,15208,152,'',0,'Rivière du Rempart',1),(15209,15209,152,'',0,'Savanne',1),(15210,15210,152,'',0,'Rodrigues',1),(15211,15211,152,'',0,'Les îles Agaléga',1),(15212,15212,152,'',0,'Les écueils des Cargados Carajos',1),(15401,15401,154,'',0,'Mexique',1),(23201,23201,232,'',0,'Los Andes',1),(23202,23202,232,'',0,'Capital',1),(23203,23203,232,'',0,'Central',1),(23204,23204,232,'',0,'Cento Occidental',1),(23205,23205,232,'',0,'Guayana',1),(23206,23206,232,'',0,'Insular',1),(23207,23207,232,'',0,'Los Llanos',1),(23208,23208,232,'',0,'Nor-Oriental',1),(23209,23209,232,'',0,'Zuliana',1);
/*!40000 ALTER TABLE `llx_c_regions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_revenuestamp`
--

DROP TABLE IF EXISTS `llx_c_revenuestamp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_revenuestamp` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_pays` int(11) NOT NULL,
  `taux` double NOT NULL,
  `note` varchar(128) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `accountancy_code_sell` varchar(15) DEFAULT NULL,
  `accountancy_code_buy` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_revenuestamp`
--

LOCK TABLES `llx_c_revenuestamp` WRITE;
/*!40000 ALTER TABLE `llx_c_revenuestamp` DISABLE KEYS */;
INSERT INTO `llx_c_revenuestamp` VALUES (101,10,0.6,'Timbre fiscal1',1,'aa','bb'),(103,30,10,'111',1,'1111','1111'),(104,10,5,'fdsf',1,'dfd',NULL);
/*!40000 ALTER TABLE `llx_c_revenuestamp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_shipment_mode`
--

DROP TABLE IF EXISTS `llx_c_shipment_mode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_shipment_mode` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `code` varchar(30) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `description` text,
  `tracking` varchar(256) NOT NULL,
  `active` tinyint(4) DEFAULT '0',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_shipment_mode`
--

LOCK TABLES `llx_c_shipment_mode` WRITE;
/*!40000 ALTER TABLE `llx_c_shipment_mode` DISABLE KEYS */;
INSERT INTO `llx_c_shipment_mode` VALUES (1,'2010-10-09 23:43:16','CATCH','Catch','Catch by client','',1,NULL),(2,'2010-10-09 23:43:16','TRANS','Transporter','Generic transporter','',1,NULL),(3,'2010-10-09 23:43:16','COLSUI','Colissimo Suivi','Colissimo Suivi','',0,NULL),(4,'2011-07-18 17:28:27','LETTREMAX','Lettre Max','Courrier Suivi et Lettre Max','',0,NULL),(5,'2013-02-24 01:48:17','UPS','UPS','United Parcel Service','',0,NULL),(6,'2013-02-24 01:48:17','KIALA','KIALA','Relais Kiala','',0,NULL),(7,'2013-02-24 01:48:17','GLS','GLS','General Logistics Systems','',0,NULL),(8,'2013-02-24 01:48:17','CHRONO','Chronopost','Chronopost','',0,NULL),(9,'2013-02-24 01:48:18','UPS','UPS','United Parcel Service','http://wwwapps.ups.com/etracking/tracking.cgi?InquiryNumber2=&InquiryNumber3=&tracknums_displayed=3&loc=fr_FR&TypeOfInquiryNumber=T&HTMLVersion=4.0&InquiryNumber22=&InquiryNumber32=&track=Track&Suivi.x=64&Suivi.y=7&Suivi=Valider&InquiryNumber1={TRACKID}',0,NULL),(10,'2013-02-24 01:48:18','KIALA','KIALA','Relais Kiala','http://www.kiala.fr/tnt/delivery/{TRACKID}',0,NULL),(11,'2013-02-24 01:48:18','GLS','GLS','General Logistics Systems','http://www.gls-group.eu/276-I-PORTAL-WEB/content/GLS/FR01/FR/5004.htm?txtAction=71000&txtRefNo={TRACKID}',0,NULL),(12,'2013-02-24 01:48:18','CHRONO','Chronopost','Chronopost','http://www.chronopost.fr/expedier/inputLTNumbersNoJahia.do?listeNumeros={TRACKID}',0,NULL);
/*!40000 ALTER TABLE `llx_c_shipment_mode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_source`
--

DROP TABLE IF EXISTS `llx_c_source`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_source` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `label` varchar(60) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_source`
--

LOCK TABLES `llx_c_source` WRITE;
/*!40000 ALTER TABLE `llx_c_source` DISABLE KEYS */;
INSERT INTO `llx_c_source` VALUES (1,'SRC_00','Proposition commerciale',1),(2,'SRC_01','Internet',1),(3,'SRC_02','Campagne courrier',1),(4,'SRC_03','Campagne téléphone',1),(5,'SRC_04','Campagne fax',1),(6,'SRC_05','Commercial',1),(7,'SRC_06','Magasin',1);
/*!40000 ALTER TABLE `llx_c_source` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_stcomm`
--

DROP TABLE IF EXISTS `llx_c_stcomm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_stcomm` (
  `id` int(11) NOT NULL,
  `code` varchar(12) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_c_stcomm` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_stcomm`
--

LOCK TABLES `llx_c_stcomm` WRITE;
/*!40000 ALTER TABLE `llx_c_stcomm` DISABLE KEYS */;
INSERT INTO `llx_c_stcomm` VALUES (-1,'ST_NO','Ne pas contacter',1),(0,'ST_NEVER','Jamais contacté',1),(1,'ST_TODO','A contacter',1),(2,'ST_PEND','Contact en cours',1),(3,'ST_DONE','Contactée',1);
/*!40000 ALTER TABLE `llx_c_stcomm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_tva`
--

DROP TABLE IF EXISTS `llx_c_tva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_tva` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_pays` int(11) NOT NULL,
  `taux` double NOT NULL,
  `localtax1` double NOT NULL DEFAULT '0',
  `localtax1_type` varchar(10) NOT NULL DEFAULT '0',
  `localtax2` double NOT NULL DEFAULT '0',
  `localtax2_type` varchar(10) NOT NULL DEFAULT '0',
  `recuperableonly` int(11) NOT NULL DEFAULT '0',
  `note` varchar(128) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `accountancy_code_sell` varchar(15) DEFAULT NULL,
  `accountancy_code_buy` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2463 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_tva`
--

LOCK TABLES `llx_c_tva` WRITE;
/*!40000 ALTER TABLE `llx_c_tva` DISABLE KEYS */;
INSERT INTO `llx_c_tva` VALUES (11,1,20,0,'0',0,'0',0,'VAT standard rate (France hors DOM-TOM)',1,NULL,NULL),(12,1,8.5,0,'0',0,'0',0,'VAT standard rate (DOM sauf Guyane et Saint-Martin)',0,NULL,NULL),(13,1,8.5,0,'0',0,'0',1,'VAT standard rate (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par acheteur',0,NULL,NULL),(14,1,5.5,0,'0',0,'0',0,'VAT reduced rate (France hors DOM-TOM)',1,NULL,NULL),(15,1,0,0,'0',0,'0',0,'VAT Rate 0 ou non applicable',1,NULL,NULL),(16,1,2.1,0,'0',0,'0',0,'VAT super-reduced rate',1,NULL,NULL),(21,2,21,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(22,2,6,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(23,2,0,0,'0',0,'0',0,'VAT Rate 0 ou non applicable',1,NULL,NULL),(24,2,12,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(31,3,20,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(32,3,10,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(33,3,4,0,'0',0,'0',0,'VAT super-reduced rate',1,NULL,NULL),(34,3,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(41,4,21,5.2,'3',-19,'5',0,'VAT standard rate',1,NULL,NULL),(42,4,10,1.4,'3',-19,'5',0,'VAT reduced rate',1,NULL,NULL),(43,4,4,0.5,'3',-19,'5',0,'VAT super-reduced rate',1,NULL,NULL),(44,4,0,0,'3',-19,'5',0,'VAT Rate 0',1,NULL,NULL),(51,5,19,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(52,5,7,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(53,5,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(61,6,7.6,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(62,6,3.6,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(63,6,2.4,0,'0',0,'0',0,'VAT super-reduced rate',1,NULL,NULL),(64,6,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(71,7,20,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(72,7,17.5,0,'0',0,'0',0,'VAT standard rate before 2011',1,NULL,NULL),(73,7,5,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(74,7,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(91,9,17,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(92,9,13,0,'0',0,'0',0,'VAT reduced rate 0',1,NULL,NULL),(93,9,3,0,'0',0,'0',0,'VAT super reduced rate 0',1,NULL,NULL),(94,9,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(101,10,6,0,'0',0,'0',0,'VAT 6%',1,NULL,NULL),(102,10,12,1,'4',0,'0',0,'VAT 12%',1,NULL,NULL),(103,10,18,0,'0',0,'0',0,'VAT 18%',1,NULL,NULL),(104,10,7.5,1,'4',0,'0',0,'VAT 6% Majoré à 25% (7.5%)',1,NULL,NULL),(105,10,15,1,'4',0,'0',0,'VAT 12% Majoré à 25% (15%)',1,NULL,NULL),(106,10,22.5,1,'4',0,'0',0,'VAT 18% Majoré à 25% (22.5%)',1,NULL,NULL),(107,10,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(111,11,0,0,'0',0,'0',0,'No Sales Tax',1,NULL,NULL),(112,11,4,0,'0',0,'0',0,'Sales Tax 4%',1,NULL,NULL),(113,11,6,0,'0',0,'0',0,'Sales Tax 6%',1,NULL,NULL),(121,12,20,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(122,12,14,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(123,12,10,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(124,12,7,0,'0',0,'0',0,'VAT super-reduced rate',1,NULL,NULL),(125,12,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(141,14,7,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(142,14,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(143,14,5,9.975,'1',0,'0',0,'TPS and TVQ rate',1,NULL,NULL),(171,17,19,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(172,17,6,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(173,17,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(201,20,25,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(202,20,12,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(203,20,6,0,'0',0,'0',0,'VAT super-reduced rate',1,NULL,NULL),(204,20,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(231,23,21,0,'0',0,'0',0,'IVA standard rate',1,NULL,NULL),(232,23,10.5,0,'0',0,'0',0,'IVA reduced rate',1,NULL,NULL),(233,23,0,0,'0',0,'0',0,'IVA Rate 0',1,NULL,NULL),(251,25,20,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(252,25,12,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(253,25,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(254,25,5,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(271,27,20,0,'0',0,'0',0,'VAT standard rate (France hors DOM-TOM)',1,NULL,NULL),(272,27,8.5,0,'0',0,'0',0,'VAT standard rate (DOM sauf Guyane et Saint-Martin)',0,NULL,NULL),(273,27,8.5,0,'0',0,'0',1,'VAT standard rate (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par acheteur',0,NULL,NULL),(274,27,5.5,0,'0',0,'0',0,'VAT reduced rate (France hors DOM-TOM)',0,NULL,NULL),(275,27,0,0,'0',0,'0',0,'VAT Rate 0 ou non applicable',1,NULL,NULL),(276,27,2.1,0,'0',0,'0',0,'VAT super-reduced rate',1,NULL,NULL),(277,27,7,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(281,28,10,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(282,28,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(411,41,20,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(412,41,10,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(413,41,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(461,46,0,0,'0',0,'0',0,'No VAT',1,NULL,NULL),(462,46,15,0,'0',0,'0',0,'VAT 15%',1,NULL,NULL),(463,46,7.5,0,'0',0,'0',0,'VAT 7.5%',1,NULL,NULL),(591,59,20,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(592,59,7,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(593,59,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(671,67,19,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(672,67,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(801,80,25,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(802,80,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(861,86,13,0,'0',0,'0',0,'IVA 13',1,NULL,NULL),(862,86,0,0,'0',0,'0',0,'SIN IVA',1,NULL,NULL),(1141,114,0,0,'0',0,'0',0,'No ISV',1,NULL,NULL),(1142,114,12,0,'0',0,'0',0,'ISV 12%',1,NULL,NULL),(1161,116,25.5,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(1162,116,7,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(1163,116,0,0,'0',0,'0',0,'VAT rate 0',1,NULL,NULL),(1171,117,12.5,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(1172,117,4,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(1173,117,1,0,'0',0,'0',0,'VAT super-reduced rate',1,NULL,NULL),(1174,117,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(1231,123,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(1232,123,5,0,'0',0,'0',0,'VAT Rate 5',1,NULL,NULL),(1401,140,15,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(1402,140,12,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(1403,140,6,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(1404,140,3,0,'0',0,'0',0,'VAT super-reduced rate',1,NULL,NULL),(1405,140,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(1521,152,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(1522,152,15,0,'0',0,'0',0,'VAT Rate 15',1,NULL,NULL),(1541,154,0,0,'0',0,'0',0,'No VAT',1,NULL,NULL),(1542,154,16,0,'0',0,'0',0,'VAT 16%',1,NULL,NULL),(1543,154,10,0,'0',0,'0',0,'VAT Frontero',1,NULL,NULL),(1662,166,15,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(1663,166,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(1731,173,25,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(1732,173,14,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(1733,173,8,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(1734,173,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(1841,184,20,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(1842,184,7,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(1843,184,3,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(1844,184,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(1881,188,24,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(1882,188,9,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(1883,188,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(1884,188,5,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(1931,193,0,0,'0',0,'0',0,'No VAT in SPM',1,NULL,NULL),(2011,201,19,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(2012,201,10,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(2013,201,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(2021,202,20,0,'0',0,'0',0,'VAT standard rate',1,NULL,NULL),(2022,202,8.5,0,'0',0,'0',0,'VAT reduced rate',1,NULL,NULL),(2023,202,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(2321,232,0,0,'0',0,'0',0,'No VAT',1,NULL,NULL),(2322,232,12,0,'0',0,'0',0,'VAT 12%',1,NULL,NULL),(2323,232,8,0,'0',0,'0',0,'VAT 8%',1,NULL,NULL),(2461,246,0,0,'0',0,'0',0,'VAT Rate 0',1,NULL,NULL),(2462,4,15,0,'0',0,'0',0,'aaaa',1,NULL,NULL);
/*!40000 ALTER TABLE `llx_c_tva` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_type_contact`
--

DROP TABLE IF EXISTS `llx_c_type_contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_type_contact` (
  `rowid` int(11) NOT NULL,
  `element` varchar(30) NOT NULL,
  `source` varchar(8) NOT NULL DEFAULT 'external',
  `code` varchar(32) NOT NULL,
  `libelle` varchar(64) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_c_type_contact_uk` (`element`,`source`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_type_contact`
--

LOCK TABLES `llx_c_type_contact` WRITE;
/*!40000 ALTER TABLE `llx_c_type_contact` DISABLE KEYS */;
INSERT INTO `llx_c_type_contact` VALUES (10,'contrat','internal','SALESREPSIGN','Commercial signataire du contrat',1,NULL),(11,'contrat','internal','SALESREPFOLL','Commercial suivi du contrat',1,NULL),(20,'contrat','external','BILLING','Contact client facturation contrat',1,NULL),(21,'contrat','external','CUSTOMER','Contact client suivi contrat',1,NULL),(22,'contrat','external','SALESREPSIGN','Contact client signataire contrat',1,NULL),(31,'propal','internal','SALESREPFOLL','Commercial à l\'origine de la propale',1,NULL),(40,'propal','external','BILLING','Contact client facturation propale',1,NULL),(41,'propal','external','CUSTOMER','Contact client suivi propale',1,NULL),(50,'facture','internal','SALESREPFOLL','Responsable suivi du paiement',1,NULL),(60,'facture','external','BILLING','Contact client facturation',1,NULL),(61,'facture','external','SHIPPING','Contact client livraison',1,NULL),(62,'facture','external','SERVICE','Contact client prestation',1,NULL),(70,'invoice_supplier','internal','SALESREPFOLL','Responsable suivi du paiement',1,NULL),(71,'invoice_supplier','external','BILLING','Contact fournisseur facturation',1,NULL),(72,'invoice_supplier','external','SHIPPING','Contact fournisseur livraison',1,NULL),(73,'invoice_supplier','external','SERVICE','Contact fournisseur prestation',1,NULL),(80,'agenda','internal','ACTOR','Responsable',1,NULL),(81,'agenda','internal','GUEST','Guest',1,NULL),(85,'agenda','external','ACTOR','Responsable',1,NULL),(86,'agenda','external','GUEST','Guest',1,NULL),(91,'commande','internal','SALESREPFOLL','Responsable suivi de la commande',1,NULL),(100,'commande','external','BILLING','Contact client facturation commande',1,NULL),(101,'commande','external','CUSTOMER','Contact client suivi commande',1,NULL),(102,'commande','external','SHIPPING','Contact client livraison commande',1,NULL),(120,'fichinter','internal','INTERREPFOLL','Responsable suivi de l\'intervention',1,NULL),(121,'fichinter','internal','INTERVENING','Intervenant',1,NULL),(130,'fichinter','external','BILLING','Contact client facturation intervention',1,NULL),(131,'fichinter','external','CUSTOMER','Contact client suivi de l\'intervention',1,NULL),(140,'order_supplier','internal','SALESREPFOLL','Responsable suivi de la commande',1,NULL),(141,'order_supplier','internal','SHIPPING','Responsable réception de la commande',1,NULL),(142,'order_supplier','external','BILLING','Contact fournisseur facturation commande',1,NULL),(143,'order_supplier','external','CUSTOMER','Contact fournisseur suivi commande',1,NULL),(145,'order_supplier','external','SHIPPING','Contact fournisseur livraison commande',1,NULL),(160,'project','internal','PROJECTLEADER','Chef de Projet',1,NULL),(161,'project','internal','PROJECTCONTRIBUTOR','Intervenant',1,NULL),(170,'project','external','PROJECTLEADER','Chef de Projet',1,NULL),(171,'project','external','PROJECTCONTRIBUTOR','Intervenant',1,NULL),(180,'project_task','internal','TASKEXECUTIVE','Responsable',1,NULL),(181,'project_task','internal','TASKCONTRIBUTOR','Intervenant',1,NULL),(190,'project_task','external','TASKEXECUTIVE','Responsable',1,NULL),(191,'project_task','external','TASKCONTRIBUTOR','Intervenant',1,NULL),(200,'societe','external','GENERALREF','Généraliste (référent)',0,'cabinetmed'),(201,'societe','external','GENERALISTE','Généraliste',0,'cabinetmed'),(210,'societe','external','SPECCHIROR','Chirurgien ortho',0,'cabinetmed'),(211,'societe','external','SPECCHIROT','Chirurgien autre',0,'cabinetmed'),(220,'societe','external','SPECDERMA','Dermatologue',0,'cabinetmed'),(225,'societe','external','SPECENDOC','Endocrinologue',0,'cabinetmed'),(230,'societe','external','SPECGYNECO','Gynécologue',0,'cabinetmed'),(240,'societe','external','SPECGASTRO','Gastroantérologue',0,'cabinetmed'),(245,'societe','external','SPECINTERNE','Interniste',0,'cabinetmed'),(250,'societe','external','SPECCARDIO','Cardiologue',0,'cabinetmed'),(260,'societe','external','SPECNEPHRO','Néphrologue',0,'cabinetmed'),(263,'societe','external','SPECPNEUMO','Pneumologue',0,'cabinetmed'),(265,'societe','external','SPECNEURO','Neurologue',0,'cabinetmed'),(270,'societe','external','SPECRHUMATO','Rhumatologue',0,'cabinetmed'),(280,'societe','external','KINE','Kinésithérapeute',0,'cabinetmed');
/*!40000 ALTER TABLE `llx_c_type_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_type_fees`
--

DROP TABLE IF EXISTS `llx_c_type_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_type_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(12) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_c_type_fees` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_type_fees`
--

LOCK TABLES `llx_c_type_fees` WRITE;
/*!40000 ALTER TABLE `llx_c_type_fees` DISABLE KEYS */;
INSERT INTO `llx_c_type_fees` VALUES (1,'TF_OTHER','Other',1,NULL),(2,'TF_TRIP','Trip',1,NULL),(3,'TF_LUNCH','Lunch',1,NULL);
/*!40000 ALTER TABLE `llx_c_type_fees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_typent`
--

DROP TABLE IF EXISTS `llx_c_typent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_typent` (
  `id` int(11) NOT NULL,
  `code` varchar(12) NOT NULL,
  `libelle` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `module` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_c_typent` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_typent`
--

LOCK TABLES `llx_c_typent` WRITE;
/*!40000 ALTER TABLE `llx_c_typent` DISABLE KEYS */;
INSERT INTO `llx_c_typent` VALUES (0,'TE_UNKNOWN','-',1,NULL),(1,'TE_STARTUP','Start-up',1,NULL),(2,'TE_GROUP','Grand groupe',1,NULL),(3,'TE_MEDIUM','PME/PMI',1,NULL),(4,'TE_SMALL','TPE',1,NULL),(5,'TE_ADMIN','Administration',1,NULL),(6,'TE_WHOLE','Grossiste',1,NULL),(7,'TE_RETAIL','Revendeur',1,NULL),(8,'TE_PRIVATE','Particulier',1,NULL),(100,'TE_OTHER','Autres',1,NULL),(101,'TE_HOMME','Homme',0,'cabinetmed'),(102,'TE_FEMME','Femme',0,'cabinetmed');
/*!40000 ALTER TABLE `llx_c_typent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_ziptown`
--

DROP TABLE IF EXISTS `llx_c_ziptown`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_ziptown` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(5) DEFAULT NULL,
  `fk_county` int(11) DEFAULT NULL,
  `fk_pays` int(11) NOT NULL DEFAULT '0',
  `zip` varchar(10) NOT NULL,
  `town` varchar(255) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_ziptown_fk_pays` (`zip`,`town`,`fk_pays`),
  KEY `idx_c_ziptown_fk_county` (`fk_county`),
  KEY `idx_c_ziptown_fk_pays` (`fk_pays`),
  KEY `idx_c_ziptown_zip` (`zip`),
  CONSTRAINT `fk_c_ziptown_fk_county` FOREIGN KEY (`fk_county`) REFERENCES `llx_c_departements` (`rowid`),
  CONSTRAINT `fk_c_ziptown_fk_pays` FOREIGN KEY (`fk_pays`) REFERENCES `llx_c_pays` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_ziptown`
--

LOCK TABLES `llx_c_ziptown` WRITE;
/*!40000 ALTER TABLE `llx_c_ziptown` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_c_ziptown` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie`
--

DROP TABLE IF EXISTS `llx_categorie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_categorie` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_parent` int(11) NOT NULL DEFAULT '0',
  `label` varchar(255) NOT NULL,
  `type` tinyint(4) NOT NULL DEFAULT '1',
  `entity` int(11) NOT NULL DEFAULT '1',
  `description` text,
  `fk_soc` int(11) DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_categorie_ref` (`entity`,`fk_parent`,`label`,`type`),
  KEY `idx_categorie_type` (`type`),
  KEY `idx_categorie_label` (`label`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie`
--

LOCK TABLES `llx_categorie` WRITE;
/*!40000 ALTER TABLE `llx_categorie` DISABLE KEYS */;
INSERT INTO `llx_categorie` VALUES (1,0,'MySupplierCategory',1,1,'This is description of category MyCategory for suppliers<br />',NULL,0,NULL),(2,0,'MyCategory',1,1,'This is description of MyCategory for customer and prospects<br />',NULL,0,NULL),(3,7,'Hot products',1,1,'This is description of hot products<br />',NULL,0,NULL),(4,0,'Cold products',1,1,'This is a description of cold products<br />',NULL,0,NULL),(5,7,'ChildChild 2a x',0,1,'<br />',NULL,0,NULL),(6,7,'ChildChild 2a',0,1,'<br />',NULL,0,NULL),(7,9,'Child 2',0,1,'<br />',NULL,0,NULL),(8,7,'ChildChild 2b',0,1,'<br />',NULL,0,NULL),(9,0,'Parent',0,1,'<br />',NULL,0,NULL),(10,0,'XL Cutomers',0,1,'<br />',NULL,0,NULL),(11,9,'Child 1',0,1,'',NULL,0,NULL),(12,0,'cccc',2,1,'',NULL,0,NULL),(13,0,'ccc2',2,1,'gdfgdfgdf',NULL,0,NULL),(14,0,'ccc3',2,1,'',NULL,0,NULL),(15,13,'ccc2a',2,1,'',NULL,0,NULL),(16,15,'ccc2a1',2,1,'desc,b,nb,fhgfg hf',NULL,0,NULL);
/*!40000 ALTER TABLE `llx_categorie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_association`
--

DROP TABLE IF EXISTS `llx_categorie_association`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_categorie_association` (
  `fk_categorie_mere` int(11) NOT NULL,
  `fk_categorie_fille` int(11) NOT NULL,
  UNIQUE KEY `uk_categorie_association` (`fk_categorie_mere`,`fk_categorie_fille`),
  UNIQUE KEY `uk_categorie_association_fk_categorie_fille` (`fk_categorie_fille`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie_association`
--

LOCK TABLES `llx_categorie_association` WRITE;
/*!40000 ALTER TABLE `llx_categorie_association` DISABLE KEYS */;
INSERT INTO `llx_categorie_association` VALUES (3,5),(9,11);
/*!40000 ALTER TABLE `llx_categorie_association` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_contact`
--

DROP TABLE IF EXISTS `llx_categorie_contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_categorie_contact` (
  `fk_categorie` int(11) NOT NULL,
  `fk_socpeople` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`fk_categorie`,`fk_socpeople`),
  KEY `idx_categorie_contact_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_contact_fk_socpeople` (`fk_socpeople`),
  CONSTRAINT `fk_categorie_contact_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_contact_fk_socpeople` FOREIGN KEY (`fk_socpeople`) REFERENCES `llx_socpeople` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie_contact`
--

LOCK TABLES `llx_categorie_contact` WRITE;
/*!40000 ALTER TABLE `llx_categorie_contact` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_categorie_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_fournisseur`
--

DROP TABLE IF EXISTS `llx_categorie_fournisseur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_categorie_fournisseur` (
  `fk_categorie` int(11) NOT NULL,
  `fk_societe` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`fk_categorie`,`fk_societe`),
  KEY `idx_categorie_fournisseur_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_fournisseur_fk_societe` (`fk_societe`),
  CONSTRAINT `fk_categorie_fournisseur_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_fournisseur_fk_soc` FOREIGN KEY (`fk_societe`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie_fournisseur`
--

LOCK TABLES `llx_categorie_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_categorie_fournisseur` DISABLE KEYS */;
INSERT INTO `llx_categorie_fournisseur` VALUES (1,2,NULL),(9,2,NULL);
/*!40000 ALTER TABLE `llx_categorie_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_member`
--

DROP TABLE IF EXISTS `llx_categorie_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_categorie_member` (
  `fk_categorie` int(11) NOT NULL,
  `fk_member` int(11) NOT NULL,
  PRIMARY KEY (`fk_categorie`,`fk_member`),
  KEY `idx_categorie_member_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_member_fk_member` (`fk_member`),
  CONSTRAINT `fk_categorie_member_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_member_member_rowid` FOREIGN KEY (`fk_member`) REFERENCES `llx_adherent` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie_member`
--

LOCK TABLES `llx_categorie_member` WRITE;
/*!40000 ALTER TABLE `llx_categorie_member` DISABLE KEYS */;
INSERT INTO `llx_categorie_member` VALUES (7,2),(8,1);
/*!40000 ALTER TABLE `llx_categorie_member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_product`
--

DROP TABLE IF EXISTS `llx_categorie_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_categorie_product` (
  `fk_categorie` int(11) NOT NULL,
  `fk_product` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`fk_categorie`,`fk_product`),
  KEY `idx_categorie_product_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_product_fk_product` (`fk_product`),
  CONSTRAINT `fk_categorie_product_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_product_product_rowid` FOREIGN KEY (`fk_product`) REFERENCES `llx_product` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie_product`
--

LOCK TABLES `llx_categorie_product` WRITE;
/*!40000 ALTER TABLE `llx_categorie_product` DISABLE KEYS */;
INSERT INTO `llx_categorie_product` VALUES (5,1,NULL),(5,2,NULL),(5,3,NULL),(6,2,NULL),(6,3,NULL);
/*!40000 ALTER TABLE `llx_categorie_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_categorie_societe`
--

DROP TABLE IF EXISTS `llx_categorie_societe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_categorie_societe` (
  `fk_categorie` int(11) NOT NULL,
  `fk_societe` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`fk_categorie`,`fk_societe`),
  KEY `idx_categorie_societe_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_societe_fk_societe` (`fk_societe`),
  CONSTRAINT `fk_categorie_societe_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_societe_fk_soc` FOREIGN KEY (`fk_societe`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie_societe`
--

LOCK TABLES `llx_categorie_societe` WRITE;
/*!40000 ALTER TABLE `llx_categorie_societe` DISABLE KEYS */;
INSERT INTO `llx_categorie_societe` VALUES (2,2,NULL),(2,19,NULL),(10,4,NULL);
/*!40000 ALTER TABLE `llx_categorie_societe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_chargesociales`
--

DROP TABLE IF EXISTS `llx_chargesociales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_chargesociales` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `date_ech` datetime NOT NULL,
  `libelle` varchar(80) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_type` int(11) NOT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `paye` smallint(6) NOT NULL DEFAULT '0',
  `periode` date DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_creation` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_chargesociales`
--

LOCK TABLES `llx_chargesociales` WRITE;
/*!40000 ALTER TABLE `llx_chargesociales` DISABLE KEYS */;
INSERT INTO `llx_chargesociales` VALUES (4,'2011-08-09 00:00:00','fff',1,60,10,1,'2011-08-01','2012-12-08 13:11:10',NULL,NULL);
/*!40000 ALTER TABLE `llx_chargesociales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande`
--

DROP TABLE IF EXISTS `llx_commande`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commande` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_soc` int(11) NOT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(255) DEFAULT NULL,
  `ref_int` varchar(255) DEFAULT NULL,
  `ref_client` varchar(255) DEFAULT NULL,
  `date_creation` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `date_commande` date DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_user_cloture` int(11) DEFAULT NULL,
  `source` smallint(6) DEFAULT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `amount_ht` double DEFAULT '0',
  `remise_percent` double DEFAULT '0',
  `remise_absolue` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `tva` double(24,8) DEFAULT '0.00000000',
  `localtax1` double(24,8) DEFAULT '0.00000000',
  `localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  `facture` tinyint(4) DEFAULT '0',
  `fk_account` int(11) DEFAULT NULL,
  `fk_currency` varchar(2) DEFAULT NULL,
  `fk_cond_reglement` int(11) DEFAULT NULL,
  `fk_mode_reglement` int(11) DEFAULT NULL,
  `date_livraison` date DEFAULT NULL,
  `fk_availability` int(11) DEFAULT NULL,
  `fk_input_reason` int(11) DEFAULT NULL,
  `fk_delivery_address` int(11) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_commande_ref` (`ref`,`entity`),
  KEY `idx_commande_fk_soc` (`fk_soc`),
  KEY `idx_commande_fk_user_author` (`fk_user_author`),
  KEY `idx_commande_fk_user_valid` (`fk_user_valid`),
  KEY `idx_commande_fk_user_cloture` (`fk_user_cloture`),
  KEY `idx_commande_fk_projet` (`fk_projet`),
  KEY `idx_commande_fk_account` (`fk_account`),
  KEY `idx_commande_fk_currency` (`fk_currency`),
  CONSTRAINT `fk_commande_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_commande_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_commande_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_commande_fk_user_cloture` FOREIGN KEY (`fk_user_cloture`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_commande_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande`
--

LOCK TABLES `llx_commande` WRITE;
/*!40000 ALTER TABLE `llx_commande` DISABLE KEYS */;
INSERT INTO `llx_commande` VALUES (1,'2012-12-08 13:11:07',1,NULL,'CO1107-0002',1,NULL,NULL,'','2011-07-20 15:23:12','2011-08-08 13:59:09',NULL,'2011-07-20',1,1,NULL,NULL,1,0,0,NULL,0,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,'','','',0,NULL,NULL,1,1,NULL,0,NULL,NULL,NULL,NULL),(2,'2013-02-12 16:06:51',1,NULL,'CO1107-0003',1,NULL,NULL,'','2011-07-20 23:20:12','2013-02-12 17:06:51',NULL,'2011-07-21',1,1,NULL,NULL,1,0,0,NULL,0,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,'','','einstein',0,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL,NULL),(3,'2013-02-17 17:27:56',1,NULL,'CO1107-0004',1,NULL,NULL,'','2011-07-20 23:22:53','2013-02-17 18:27:56',NULL,'2011-07-21',1,1,NULL,NULL,1,0,0,NULL,0,0.00000000,0.00000000,0.00000000,30.00000000,30.00000000,'','','einstein',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,'2012-12-08 13:11:07',1,NULL,'CO1108-0001',1,NULL,NULL,'','2011-08-08 03:04:11','2011-08-08 03:04:21',NULL,'2011-08-08',1,1,NULL,NULL,2,0,0,NULL,0,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,'','','einstein',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,'2013-02-17 15:22:14',19,NULL,'(PROV6)',1,NULL,NULL,'','2013-02-17 16:22:14',NULL,NULL,'2013-02-17',1,NULL,NULL,NULL,0,0,0,NULL,0,11.76000000,0.00000000,0.00000000,60.00000000,71.76000000,'','','',0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,'2013-02-17 17:38:37',18,NULL,'CO1302-0005',1,NULL,NULL,'gfdf','2013-02-17 16:28:22','2013-02-17 18:38:14',NULL,'2013-02-17',1,1,NULL,NULL,2,0,0,NULL,0,3.22000000,0.00000000,0.00000000,20.00000000,23.22000000,'','','',0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,'2013-03-08 09:02:31',23,NULL,'(PROV8)',1,NULL,NULL,'fdfs','2013-03-08 10:02:31',NULL,NULL,'2013-03-08',1,NULL,NULL,NULL,0,0,0,NULL,0,0.00000000,0.00000000,0.00000000,5.00000000,5.00000000,'','','',0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_commande` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_extrafields`
--

DROP TABLE IF EXISTS `llx_commande_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commande_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_commande_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande_extrafields`
--

LOCK TABLES `llx_commande_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_commande_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_commande_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseur`
--

DROP TABLE IF EXISTS `llx_commande_fournisseur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commande_fournisseur` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_soc` int(11) NOT NULL,
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(30) DEFAULT NULL,
  `ref_supplier` varchar(30) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT '0',
  `date_creation` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `date_approve` datetime DEFAULT NULL,
  `date_commande` date DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_user_approve` int(11) DEFAULT NULL,
  `source` smallint(6) NOT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `amount_ht` double DEFAULT '0',
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `tva` double(24,8) DEFAULT '0.00000000',
  `localtax1` double(24,8) DEFAULT '0.00000000',
  `localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  `fk_input_method` int(11) DEFAULT '0',
  `fk_cond_reglement` int(11) DEFAULT '0',
  `fk_mode_reglement` int(11) DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  `date_livraison` date DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_commande_fournisseur_ref` (`ref`,`fk_soc`,`entity`),
  KEY `idx_commande_fournisseur_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_commande_fournisseur_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande_fournisseur`
--

LOCK TABLES `llx_commande_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseur` VALUES (1,'2012-12-08 13:11:07',13,'CF1007-0001',1,NULL,NULL,NULL,'2010-07-11 17:13:40','2010-07-11 17:15:42',NULL,'2010-07-11',1,1,NULL,0,5,0,0,0,39.20000000,0.00000000,0.00000000,200.00000000,239.20000000,NULL,NULL,'muscadet',2,0,0,NULL,NULL,NULL),(2,'2012-12-08 13:11:07',1,'CF1007-0002',1,NULL,NULL,NULL,'2010-07-11 18:46:28','2010-07-11 18:47:33',NULL,'2010-07-11',1,1,NULL,0,3,0,0,0,0.00000000,0.00000000,0.00000000,200.00000000,200.00000000,NULL,NULL,'muscadet',4,0,0,NULL,NULL,NULL),(3,'2012-12-08 13:11:07',17,'(PROV3)',1,NULL,NULL,NULL,'2011-08-04 23:00:52',NULL,NULL,NULL,1,NULL,NULL,0,0,0,0,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,'muscadet',0,0,0,NULL,NULL,NULL),(4,'2012-12-08 13:11:07',17,'(PROV4)',1,NULL,NULL,NULL,'2011-08-04 23:19:32',NULL,NULL,NULL,1,NULL,NULL,0,0,0,0,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,'muscadet',0,0,0,NULL,NULL,NULL),(5,'2012-12-08 13:11:07',17,'(PROV5)',1,NULL,NULL,NULL,'2011-08-04 23:22:16',NULL,NULL,NULL,1,NULL,NULL,0,0,0,0,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,'muscadet',0,0,0,NULL,NULL,NULL),(6,'2012-12-08 13:11:07',17,'CF1108-0003',1,NULL,NULL,NULL,'2011-08-04 23:22:54','2011-08-08 15:04:37',NULL,NULL,1,1,NULL,0,2,0,0,0,0.98000000,0.00000000,0.00000000,5.00000000,5.98000000,NULL,NULL,'muscadet',0,0,0,NULL,NULL,NULL),(7,'2012-12-08 13:11:07',17,'(PROV7)',1,NULL,NULL,NULL,'2011-08-04 23:23:29',NULL,NULL,NULL,1,NULL,NULL,0,0,0,0,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,'muscadet',0,0,0,NULL,NULL,NULL),(8,'2012-12-08 13:11:07',17,'(PROV8)',1,NULL,NULL,NULL,'2011-08-04 23:36:10',NULL,NULL,NULL,1,NULL,NULL,0,0,0,0,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,'muscadet',0,0,0,NULL,NULL,NULL),(13,'2013-03-09 18:39:41',1,'CF1303-0004',1,NULL,NULL,0,'2013-03-09 19:39:18','2013-03-09 19:39:27','2013-03-09 19:39:32','2013-03-09',1,1,1,0,3,0,0,0,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,NULL,NULL,'muscadet',1,0,0,NULL,NULL,NULL),(14,'2013-03-22 09:26:43',16,'(PROV14)',1,NULL,'gdfg',0,'2013-03-22 10:26:38',NULL,NULL,NULL,1,NULL,NULL,0,0,0,0,0,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,'','','muscadet',0,0,0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_commande_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseur_dispatch`
--

DROP TABLE IF EXISTS `llx_commande_fournisseur_dispatch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commande_fournisseur_dispatch` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_commande` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `qty` float DEFAULT NULL,
  `fk_entrepot` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_commande_fournisseur_dispatch_fk_commande` (`fk_commande`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande_fournisseur_dispatch`
--

LOCK TABLES `llx_commande_fournisseur_dispatch` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur_dispatch` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseur_dispatch` VALUES (1,2,4,2,1,1,'2010-07-11 18:49:44');
/*!40000 ALTER TABLE `llx_commande_fournisseur_dispatch` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseur_extrafields`
--

DROP TABLE IF EXISTS `llx_commande_fournisseur_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commande_fournisseur_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_commande_fournisseur_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande_fournisseur_extrafields`
--

LOCK TABLES `llx_commande_fournisseur_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_commande_fournisseur_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseur_log`
--

DROP TABLE IF EXISTS `llx_commande_fournisseur_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commande_fournisseur_log` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datelog` datetime NOT NULL,
  `fk_commande` int(11) NOT NULL,
  `fk_statut` smallint(6) NOT NULL,
  `fk_user` int(11) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande_fournisseur_log`
--

LOCK TABLES `llx_commande_fournisseur_log` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur_log` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseur_log` VALUES (1,'2010-07-11 15:13:40','2010-07-11 17:13:40',1,0,1,NULL),(2,'2010-07-11 15:15:42','2010-07-11 17:15:42',1,1,1,NULL),(3,'2010-07-11 15:16:28','2010-07-11 17:16:28',1,2,1,NULL),(4,'2010-07-11 15:19:14','2010-07-11 00:00:00',1,3,1,NULL),(5,'2010-07-11 15:19:36','2010-07-11 00:00:00',1,5,1,NULL),(6,'2010-07-11 16:46:28','2010-07-11 18:46:28',2,0,1,NULL),(7,'2010-07-11 16:47:33','2010-07-11 18:47:33',2,1,1,NULL),(8,'2010-07-11 16:47:41','2010-07-11 18:47:41',2,2,1,NULL),(9,'2010-07-11 16:48:00','2010-07-11 00:00:00',2,3,1,NULL),(10,'2011-08-04 21:00:52','2011-08-04 23:00:52',3,0,1,NULL),(11,'2011-08-04 21:19:32','2011-08-04 23:19:32',4,0,1,NULL),(12,'2011-08-04 21:22:16','2011-08-04 23:22:16',5,0,1,NULL),(13,'2011-08-04 21:22:54','2011-08-04 23:22:54',6,0,1,NULL),(14,'2011-08-04 21:23:29','2011-08-04 23:23:29',7,0,1,NULL),(15,'2011-08-04 21:36:10','2011-08-04 23:36:10',8,0,1,NULL),(19,'2011-08-08 13:04:37','2011-08-08 15:04:37',6,1,1,NULL),(20,'2011-08-08 13:04:38','2011-08-08 15:04:38',6,2,1,NULL),(29,'2013-03-09 18:39:18','2013-03-09 19:39:18',13,0,1,NULL),(30,'2013-03-09 18:39:27','2013-03-09 19:39:27',13,1,1,NULL),(31,'2013-03-09 18:39:32','2013-03-09 19:39:32',13,2,1,NULL),(32,'2013-03-09 18:39:41','2013-03-09 00:00:00',13,3,1,'hf'),(33,'2013-03-22 09:26:38','2013-03-22 10:26:38',14,0,1,NULL);
/*!40000 ALTER TABLE `llx_commande_fournisseur_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commande_fournisseurdet`
--

DROP TABLE IF EXISTS `llx_commande_fournisseurdet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commande_fournisseurdet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_commande` int(11) NOT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `ref` varchar(50) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `description` text,
  `tva_tx` double(6,3) DEFAULT '0.000',
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax1_type` varchar(10) NOT NULL DEFAULT '0',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `localtax2_type` varchar(10) NOT NULL DEFAULT '0',
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `subprice` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `info_bits` int(11) DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande_fournisseurdet`
--

LOCK TABLES `llx_commande_fournisseurdet` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseurdet` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseurdet` VALUES (1,1,NULL,'','','Chips',19.600,0.000,'',0.000,'',10,0,0,20.00000000,200.00000000,39.20000000,0.00000000,0.00000000,239.20000000,0,NULL,NULL,0,NULL),(2,2,4,'ABCD','Decapsuleur','',0.000,0.000,'',0.000,'',20,0,0,10.00000000,200.00000000,0.00000000,0.00000000,0.00000000,200.00000000,0,NULL,NULL,0,NULL),(3,6,NULL,'','','ljkljl',19.600,0.000,'',0.000,'',1,0,0,5.00000000,5.00000000,0.98000000,0.00000000,0.00000000,5.98000000,0,NULL,NULL,0,NULL),(6,13,NULL,'','','dfgdf',0.000,0.000,'0',0.000,'0',1,0,0,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL),(7,14,NULL,'','','gfdgd',0.000,0.000,'0',0.000,'0',1,0,0,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL);
/*!40000 ALTER TABLE `llx_commande_fournisseurdet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commandedet`
--

DROP TABLE IF EXISTS `llx_commandedet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commandedet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_commande` int(11) DEFAULT NULL,
  `fk_parent_line` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `description` text,
  `tva_tx` double(6,3) DEFAULT NULL,
  `localtax1_tx` double(6,3) DEFAULT NULL,
  `localtax1_type` varchar(10) NOT NULL DEFAULT '0',
  `localtax2_tx` double(6,3) DEFAULT NULL,
  `localtax2_type` varchar(10) NOT NULL DEFAULT '0',
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `fk_remise_except` int(11) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `subprice` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `info_bits` int(11) DEFAULT '0',
  `fk_product_fournisseur_price` int(11) DEFAULT NULL,
  `buy_price_ht` double(24,8) DEFAULT '0.00000000',
  `special_code` int(10) unsigned DEFAULT '0',
  `rang` int(11) DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_commandedet_fk_commande` (`fk_commande`),
  KEY `idx_commandedet_fk_product` (`fk_product`),
  CONSTRAINT `fk_commandedet_fk_commande` FOREIGN KEY (`fk_commande`) REFERENCES `llx_commande` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commandedet`
--

LOCK TABLES `llx_commandedet` WRITE;
/*!40000 ALTER TABLE `llx_commandedet` DISABLE KEYS */;
INSERT INTO `llx_commandedet` VALUES (1,1,NULL,NULL,NULL,'Product 1',0.000,0.000,'',0.000,'',1,0,0,NULL,10,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,1,NULL),(2,1,NULL,2,NULL,'',0.000,0.000,'',0.000,'',1,0,0,NULL,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,NULL,0.00000000,0,2,NULL),(3,1,NULL,5,NULL,'cccc',0.000,0.000,'',0.000,'',1,0,0,NULL,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,NULL,0.00000000,0,3,NULL),(4,2,NULL,NULL,NULL,'hgf',0.000,0.000,'',0.000,'',1,0,0,NULL,10,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,1,NULL),(10,5,NULL,NULL,NULL,'gfdgdf',0.000,0.000,'',0.000,'',1,0,0,NULL,10,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,1,NULL),(11,6,NULL,NULL,NULL,'gdfg',19.600,0.000,'',0.000,'',1,0,0,NULL,10,10.00000000,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,0,NULL,NULL,0,NULL,0.00000000,0,1,NULL),(12,6,NULL,NULL,NULL,'gfdgd',19.600,0.000,'',0.000,'',1,0,0,NULL,50,50.00000000,50.00000000,9.80000000,0.00000000,0.00000000,59.80000000,1,NULL,NULL,0,NULL,0.00000000,0,2,NULL),(13,7,NULL,NULL,NULL,'gfdg',19.600,0.000,'',0.000,'',1,0,0,NULL,10,10.00000000,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,0,NULL,NULL,0,NULL,0.00000000,0,1,NULL),(14,3,NULL,NULL,NULL,'gdfgdf',0.000,0.000,'',0.000,'',1,0,0,NULL,10,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,1,NULL,NULL,0,NULL,0.00000000,0,1,NULL),(15,3,NULL,NULL,NULL,'fghfgh',0.000,0.000,'',0.000,'',1,0,0,NULL,20,20.00000000,20.00000000,0.00000000,0.00000000,0.00000000,20.00000000,0,NULL,NULL,0,NULL,0.00000000,0,2,NULL),(16,7,NULL,4,NULL,'',12.500,0.000,'',0.000,'',1,0,0,NULL,5,5.00000000,5.00000000,0.63000000,0.00000000,0.00000000,5.63000000,0,NULL,NULL,0,NULL,0.00000000,0,2,NULL),(17,7,NULL,4,NULL,'eeee',12.500,0.000,'',0.000,'',1,0,0,NULL,5,5.00000000,5.00000000,0.63000000,0.00000000,0.00000000,5.63000000,0,NULL,NULL,0,NULL,0.00000000,0,3,NULL),(18,8,NULL,NULL,NULL,'fdsfs',0.000,0.000,'',0.000,'',1,0,0,NULL,10,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,1,NULL),(19,8,NULL,NULL,NULL,'fsdfsf',0.000,0.000,'',0.000,'',1,0,0,NULL,-5,-5.00000000,-5.00000000,0.00000000,0.00000000,0.00000000,-5.00000000,0,NULL,NULL,0,NULL,0.00000000,0,2,NULL);
/*!40000 ALTER TABLE `llx_commandedet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_commandedet_extrafields`
--

DROP TABLE IF EXISTS `llx_commandedet_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_commandedet_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_commandedet_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commandedet_extrafields`
--

LOCK TABLES `llx_commandedet_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_commandedet_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_commandedet_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_compta`
--

DROP TABLE IF EXISTS `llx_compta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_compta` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `datev` date DEFAULT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `label` varchar(255) DEFAULT NULL,
  `fk_compta_account` int(11) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `valid` tinyint(4) DEFAULT '0',
  `note` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_compta`
--

LOCK TABLES `llx_compta` WRITE;
/*!40000 ALTER TABLE `llx_compta` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_compta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_compta_account`
--

DROP TABLE IF EXISTS `llx_compta_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_compta_account` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `number` varchar(12) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_compta_account`
--

LOCK TABLES `llx_compta_account` WRITE;
/*!40000 ALTER TABLE `llx_compta_account` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_compta_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_compta_compte_generaux`
--

DROP TABLE IF EXISTS `llx_compta_compte_generaux`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_compta_compte_generaux` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `date_creation` datetime DEFAULT NULL,
  `numero` varchar(50) DEFAULT NULL,
  `intitule` varchar(255) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `numero` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_compta_compte_generaux`
--

LOCK TABLES `llx_compta_compte_generaux` WRITE;
/*!40000 ALTER TABLE `llx_compta_compte_generaux` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_compta_compte_generaux` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_cond_reglement`
--

DROP TABLE IF EXISTS `llx_cond_reglement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_cond_reglement` (
  `rowid` int(11) NOT NULL,
  `code` varchar(16) DEFAULT NULL,
  `sortorder` smallint(6) DEFAULT NULL,
  `active` tinyint(4) DEFAULT '1',
  `libelle` varchar(255) DEFAULT NULL,
  `libelle_facture` text,
  `fdm` tinyint(4) DEFAULT NULL,
  `nbjour` smallint(6) DEFAULT NULL,
  `decalage` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_cond_reglement`
--

LOCK TABLES `llx_cond_reglement` WRITE;
/*!40000 ALTER TABLE `llx_cond_reglement` DISABLE KEYS */;
INSERT INTO `llx_cond_reglement` VALUES (1,'RECEP',1,1,'A réception','Réception de facture',0,0,NULL),(2,'30D',2,1,'30 jours','Réglement à 30 jours',0,30,NULL),(3,'30DENDMONTH',3,1,'30 jours fin de mois','Réglement à 30 jours fin de mois',1,30,NULL),(4,'60D',4,1,'60 jours','Réglement à 60 jours',0,60,NULL),(5,'60DENDMONTH',5,1,'60 jours fin de mois','Réglement à 60 jours fin de mois',1,60,NULL);
/*!40000 ALTER TABLE `llx_cond_reglement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_congespayes`
--

DROP TABLE IF EXISTS `llx_congespayes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_congespayes` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `date_create` datetime NOT NULL,
  `description` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `statut` int(11) NOT NULL DEFAULT '1',
  `fk_validator` int(11) NOT NULL,
  `date_valid` datetime DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `date_refuse` datetime DEFAULT NULL,
  `fk_user_refuse` int(11) DEFAULT NULL,
  `date_cancel` datetime DEFAULT NULL,
  `fk_user_cancel` int(11) DEFAULT NULL,
  `detail_refuse` varchar(250) COLLATE latin1_german2_ci DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_congespayes`
--

LOCK TABLES `llx_congespayes` WRITE;
/*!40000 ALTER TABLE `llx_congespayes` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_congespayes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_congespayes_config`
--

DROP TABLE IF EXISTS `llx_congespayes_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_congespayes_config` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  `value` text COLLATE latin1_german2_ci,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_congespayes_config`
--

LOCK TABLES `llx_congespayes_config` WRITE;
/*!40000 ALTER TABLE `llx_congespayes_config` DISABLE KEYS */;
INSERT INTO `llx_congespayes_config` VALUES (1,'userGroup','2'),(2,'lastUpdate','1331893531'),(3,'nbUser','9'),(4,'delayForRequest','30'),(5,'AlertValidatorDelay','1'),(6,'AlertValidatorSolde','1'),(7,'nbCongesDeducted','1.20'),(8,'nbCongesEveryMonth','2.50');
/*!40000 ALTER TABLE `llx_congespayes_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_congespayes_events`
--

DROP TABLE IF EXISTS `llx_congespayes_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_congespayes_events` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  `value` text COLLATE latin1_german2_ci NOT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_congespayes_events`
--

LOCK TABLES `llx_congespayes_events` WRITE;
/*!40000 ALTER TABLE `llx_congespayes_events` DISABLE KEYS */;
INSERT INTO `llx_congespayes_events` VALUES (1,'Mariage','3.00');
/*!40000 ALTER TABLE `llx_congespayes_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_congespayes_logs`
--

DROP TABLE IF EXISTS `llx_congespayes_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_congespayes_logs` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `date_action` datetime NOT NULL,
  `fk_user_action` int(11) NOT NULL,
  `fk_user_update` int(11) NOT NULL,
  `type_action` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  `prev_solde` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  `new_solde` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_congespayes_logs`
--

LOCK TABLES `llx_congespayes_logs` WRITE;
/*!40000 ALTER TABLE `llx_congespayes_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_congespayes_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_congespayes_users`
--

DROP TABLE IF EXISTS `llx_congespayes_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_congespayes_users` (
  `fk_user` int(11) NOT NULL,
  `nb_conges` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`fk_user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_congespayes_users`
--

LOCK TABLES `llx_congespayes_users` WRITE;
/*!40000 ALTER TABLE `llx_congespayes_users` DISABLE KEYS */;
INSERT INTO `llx_congespayes_users` VALUES (1,0),(2,0),(3,0),(4,0),(5,0),(6,0),(7,0),(8,0),(9,0);
/*!40000 ALTER TABLE `llx_congespayes_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_const`
--

DROP TABLE IF EXISTS `llx_const`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_const` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `value` text NOT NULL,
  `type` varchar(6) DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `note` text,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_const` (`name`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=5357 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_const`
--

LOCK TABLES `llx_const` WRITE;
/*!40000 ALTER TABLE `llx_const` DISABLE KEYS */;
INSERT INTO `llx_const` VALUES (5,'SYSLOG_LEVEL',0,'7','chaine',0,'Level of debug info to show','2010-07-08 11:17:57'),(8,'MAIN_UPLOAD_DOC',0,'2048','chaine',0,'Max size for file upload (0 means no upload allowed)','2010-07-08 11:17:57'),(9,'MAIN_SEARCHFORM_SOCIETE',0,'1','yesno',0,'Show form for quick company search','2010-07-08 11:17:57'),(10,'MAIN_SEARCHFORM_CONTACT',0,'1','yesno',0,'Show form for quick contact search','2010-07-08 11:17:57'),(11,'MAIN_SEARCHFORM_PRODUITSERVICE',0,'1','yesno',0,'Show form for quick product search','2010-07-08 11:17:58'),(12,'MAIN_SEARCHFORM_ADHERENT',0,'1','yesno',0,'Show form for quick member search','2010-07-08 11:17:58'),(16,'MAIN_SIZE_LISTE_LIMIT',0,'25','chaine',0,'Longueur maximum des listes','2010-07-08 11:17:58'),(17,'MAIN_SHOW_WORKBOARD',0,'1','yesno',0,'Affichage tableau de bord de travail Dolibarr','2010-07-08 11:17:58'),(29,'MAIN_DELAY_NOT_ACTIVATED_SERVICES',1,'0','chaine',0,'Tolérance de retard avant alerte (en jours) sur services à activer','2010-07-08 11:17:58'),(33,'SOCIETE_NOLIST_COURRIER',0,'1','yesno',0,'Liste les fichiers du repertoire courrier','2010-07-08 11:17:58'),(35,'SOCIETE_CODECOMPTA_ADDON',1,'mod_codecompta_panicum','yesno',0,'Module to control third parties codes','2010-07-08 11:17:58'),(36,'ADHERENT_MAIL_REQUIRED',1,'1','yesno',0,'EMail required to create a new member','2010-07-08 11:17:58'),(37,'ADHERENT_MAIL_FROM',1,'adherents@domain.com','chaine',0,'Sender EMail for automatic emails','2010-07-08 11:17:58'),(38,'ADHERENT_MAIL_RESIL',1,'Your subscription has been resiliated.\r\nWe hope to see you soon again','texte',0,'Mail resiliation','2010-07-08 11:17:58'),(39,'ADHERENT_MAIL_VALID',1,'Your subscription has been validated.\r\nThis is a remind of your personal information :\r\n\r\n%INFOS%\r\n\r\n','texte',0,'Mail de validation','2010-07-08 11:17:59'),(40,'ADHERENT_MAIL_COTIS',1,'Hello %PRENOM%,\r\nThanks for your subscription.\r\nThis email confirms that your subscription has been received and processed.\r\n\r\n','texte',0,'Mail de validation de cotisation','2010-07-08 11:17:59'),(41,'ADHERENT_MAIL_VALID_SUBJECT',1,'Your subscription has been validated','chaine',0,'Sujet du mail de validation','2010-07-08 11:17:59'),(42,'ADHERENT_MAIL_RESIL_SUBJECT',1,'Resiliating your subscription','chaine',0,'Sujet du mail de resiliation','2010-07-08 11:17:59'),(43,'ADHERENT_MAIL_COTIS_SUBJECT',1,'Receipt of your subscription','chaine',0,'Sujet du mail de validation de cotisation','2010-07-08 11:17:59'),(44,'MAILING_EMAIL_FROM',1,'dolibarr@domain.com','chaine',0,'EMail emmetteur pour les envois d emailings','2010-07-08 11:17:59'),(45,'ADHERENT_USE_MAILMAN',1,'0','yesno',0,'Utilisation de Mailman','2010-07-08 11:17:59'),(46,'ADHERENT_MAILMAN_UNSUB_URL',1,'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&user=%EMAIL%','chaine',0,'Url de desinscription aux listes mailman','2010-07-08 11:17:59'),(47,'ADHERENT_MAILMAN_URL',1,'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%','chaine',0,'Url pour les inscriptions mailman','2010-07-08 11:17:59'),(48,'ADHERENT_MAILMAN_LISTS',1,'test-test,test-test2','chaine',0,'Listes auxquelles inscrire les nouveaux adherents','2010-07-08 11:17:59'),(49,'ADHERENT_MAILMAN_ADMINPW',1,'','chaine',0,'Mot de passe Admin des liste mailman','2010-07-08 11:17:59'),(50,'ADHERENT_MAILMAN_SERVER',1,'lists.domain.com','chaine',0,'Serveur hebergeant les interfaces d Admin des listes mailman','2010-07-08 11:17:59'),(51,'ADHERENT_MAILMAN_LISTS_COTISANT',1,'','chaine',0,'Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement','2010-07-08 11:17:59'),(52,'ADHERENT_USE_SPIP',1,'0','yesno',0,'Utilisation de SPIP ?','2010-07-08 11:17:59'),(53,'ADHERENT_USE_SPIP_AUTO',1,'0','yesno',0,'Utilisation de SPIP automatiquement','2010-07-08 11:17:59'),(54,'ADHERENT_SPIP_USER',1,'user','chaine',0,'user spip','2010-07-08 11:17:59'),(55,'ADHERENT_SPIP_PASS',1,'pass','chaine',0,'Pass de connection','2010-07-08 11:17:59'),(56,'ADHERENT_SPIP_SERVEUR',1,'localhost','chaine',0,'serveur spip','2010-07-08 11:17:59'),(57,'ADHERENT_SPIP_DB',1,'spip','chaine',0,'db spip','2010-07-08 11:17:59'),(58,'ADHERENT_CARD_HEADER_TEXT',1,'%ANNEE%','chaine',0,'Texte imprime sur le haut de la carte adherent','2010-07-08 11:17:59'),(59,'ADHERENT_CARD_FOOTER_TEXT',1,'Association AZERTY','chaine',0,'Texte imprime sur le bas de la carte adherent','2010-07-08 11:17:59'),(61,'FCKEDITOR_ENABLE_USER',1,'1','yesno',0,'Activation fckeditor sur notes utilisateurs','2010-07-08 11:17:59'),(62,'FCKEDITOR_ENABLE_SOCIETE',1,'1','yesno',0,'Activation fckeditor sur notes societe','2010-07-08 11:17:59'),(63,'FCKEDITOR_ENABLE_PRODUCTDESC',1,'1','yesno',0,'Activation fckeditor sur notes produits','2010-07-08 11:17:59'),(64,'FCKEDITOR_ENABLE_MEMBER',1,'1','yesno',0,'Activation fckeditor sur notes adherent','2010-07-08 11:17:59'),(65,'FCKEDITOR_ENABLE_MAILING',1,'1','yesno',0,'Activation fckeditor sur emailing','2010-07-08 11:17:59'),(66,'OSC_DB_HOST',1,'localhost','chaine',0,'Host for OSC database for OSCommerce module 1','2010-07-08 11:17:59'),(67,'DON_ADDON_MODEL',1,'html_cerfafr','chaine',0,'','2010-07-08 11:18:00'),(68,'PROPALE_ADDON',1,'mod_propale_marbre','chaine',0,'','2010-07-08 11:18:00'),(69,'PROPALE_ADDON_PDF',1,'azur','chaine',0,'','2010-07-08 11:18:00'),(70,'COMMANDE_ADDON',1,'mod_commande_marbre','chaine',0,'','2010-07-08 11:18:00'),(71,'COMMANDE_ADDON_PDF',1,'einstein','chaine',0,'','2010-07-08 11:18:00'),(72,'COMMANDE_SUPPLIER_ADDON',1,'mod_commande_fournisseur_muguet','chaine',0,'','2010-07-08 11:18:00'),(73,'COMMANDE_SUPPLIER_ADDON_PDF',1,'muscadet','chaine',0,'','2010-07-08 11:18:00'),(74,'EXPEDITION_ADDON',1,'enlevement','chaine',0,'','2010-07-08 11:18:00'),(76,'FICHEINTER_ADDON',1,'pacific','chaine',0,'','2010-07-08 11:18:00'),(77,'FICHEINTER_ADDON_PDF',1,'soleil','chaine',0,'','2010-07-08 11:18:00'),(79,'FACTURE_ADDON_PDF',1,'crabe','chaine',0,'','2010-07-08 11:18:00'),(80,'PROPALE_VALIDITY_DURATION',1,'15','chaine',0,'Durée de validitée des propales','2010-07-08 11:18:00'),(230,'COMPANY_ADDON_PDF_ODT_PATH',1,'DOL_DATA_ROOT/doctemplates/thirdparties','chaine',0,NULL,'2010-07-08 11:26:20'),(238,'LIVRAISON_ADDON_PDF',1,'typhon','chaine',0,'Nom du gestionnaire de generation des commandes en PDF','2010-07-08 11:26:27'),(239,'LIVRAISON_ADDON_NUMBER',1,'mod_livraison_jade','chaine',0,'Nom du gestionnaire de numerotation des bons de livraison','2013-03-20 13:17:36'),(242,'MAIN_SUBMODULE_EXPEDITION',1,'1','chaine',0,'','2010-07-08 11:26:34'),(245,'FACTURE_ADDON_PDF_ODT_PATH',1,'DOL_DATA_ROOT/doctemplates/invoices','chaine',0,NULL,'2010-07-08 11:28:53'),(249,'DON_FORM',1,'fsfe.fr.php','chaine',0,'Nom du gestionnaire de formulaire de dons','2010-07-08 11:29:00'),(252,'MAIN_MODULE_ADHERENT',1,'1',NULL,0,NULL,'2010-07-08 11:29:05'),(253,'ADHERENT_BANK_USE_AUTO',1,'','yesno',0,'Insertion automatique des cotisation dans le compte banquaire','2010-07-08 11:29:05'),(254,'ADHERENT_BANK_ACCOUNT',1,'','chaine',0,'ID du Compte banquaire utilise','2010-07-08 11:29:05'),(255,'ADHERENT_BANK_CATEGORIE',1,'','chaine',0,'ID de la categorie banquaire des cotisations','2010-07-08 11:29:05'),(256,'ADHERENT_ETIQUETTE_TYPE',1,'L7163','chaine',0,'Type d etiquette (pour impression de planche d etiquette)','2010-07-08 11:29:05'),(260,'MAIN_MODULE_STOCK',1,'1',NULL,0,NULL,'2010-07-08 11:29:18'),(269,'PROJECT_ADDON_PDF',1,'baleine','chaine',0,'Nom du gestionnaire de generation des projets en PDF','2010-07-08 11:29:33'),(270,'PROJECT_ADDON',1,'mod_project_simple','chaine',0,'Nom du gestionnaire de numerotation des projets','2010-07-08 11:29:33'),(271,'MAIN_MODULE_MAILING',1,'1',NULL,0,NULL,'2010-07-08 11:29:37'),(272,'MAIN_MODULE_EXPORT',1,'1',NULL,0,NULL,'2010-07-08 11:29:41'),(273,'MAIN_MODULE_IMPORT',1,'1',NULL,0,NULL,'2010-07-08 11:29:45'),(274,'MAIN_MODULE_CATEGORIE',1,'1',NULL,0,NULL,'2010-07-08 11:29:59'),(275,'MAIN_MODULE_BOOKMARK',1,'1',NULL,0,NULL,'2010-07-08 11:30:03'),(276,'MAIN_MODULE_WEBSERVICES',1,'1',NULL,0,NULL,'2010-07-08 11:30:30'),(278,'MAIN_MODULE_GEOIPMAXMIND',1,'1',NULL,0,NULL,'2010-07-08 11:30:36'),(279,'MAIN_MODULE_EXTERNALRSS',1,'1',NULL,0,NULL,'2010-07-08 11:30:38'),(292,'MAIN_MODULE_FCKEDITOR',1,'1',NULL,0,NULL,'2010-07-08 11:56:27'),(368,'STOCK_USERSTOCK_AUTOCREATE',1,'1','chaine',0,'','2010-07-08 22:44:59'),(369,'EXPEDITION_ADDON_PDF',1,'merou','chaine',0,'','2010-07-08 22:58:07'),(370,'MAIN_SUBMODULE_LIVRAISON',1,'1','chaine',0,'','2010-07-08 23:00:29'),(377,'FACTURE_ADDON',1,'mod_facture_terre','chaine',0,'','2010-07-08 23:08:12'),(380,'ADHERENT_CARD_TEXT',1,'%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','',0,'Texte imprime sur la carte adherent','2010-07-08 23:14:46'),(381,'ADHERENT_CARD_TEXT_RIGHT',1,'aaa','',0,'','2010-07-08 23:14:55'),(384,'PRODUIT_SOUSPRODUITS',1,'1','chaine',0,'','2010-07-08 23:22:12'),(385,'PRODUIT_USE_SEARCH_TO_SELECT',1,'1','chaine',0,'','2010-07-08 23:22:19'),(386,'STOCK_CALCULATE_ON_SHIPMENT',1,'1','chaine',0,'','2010-07-08 23:23:21'),(387,'STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER',1,'1','chaine',0,'','2010-07-08 23:23:26'),(392,'MAIN_AGENDA_XCAL_EXPORTKEY',1,'dolibarr','chaine',0,'','2010-07-08 23:27:50'),(393,'MAIN_AGENDA_EXPORT_PAST_DELAY',1,'100','chaine',0,'','2010-07-08 23:27:50'),(523,'MAIN_AGENDA_ACTIONAUTO_COMPANY_CREATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(524,'MAIN_AGENDA_ACTIONAUTO_CONTRACT_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(525,'MAIN_AGENDA_ACTIONAUTO_PROPAL_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(526,'MAIN_AGENDA_ACTIONAUTO_PROPAL_SENTBYMAIL',1,'1','chaine',0,'','2010-07-10 12:48:49'),(527,'MAIN_AGENDA_ACTIONAUTO_ORDER_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(528,'MAIN_AGENDA_ACTIONAUTO_ORDER_SENTBYMAIL',1,'1','chaine',0,'','2010-07-10 12:48:49'),(529,'MAIN_AGENDA_ACTIONAUTO_BILL_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(530,'MAIN_AGENDA_ACTIONAUTO_BILL_PAYED',1,'1','chaine',0,'','2010-07-10 12:48:49'),(531,'MAIN_AGENDA_ACTIONAUTO_BILL_CANCEL',1,'1','chaine',0,'','2010-07-10 12:48:49'),(532,'MAIN_AGENDA_ACTIONAUTO_BILL_SENTBYMAIL',1,'1','chaine',0,'','2010-07-10 12:48:49'),(533,'MAIN_AGENDA_ACTIONAUTO_ORDER_SUPPLIER_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:50'),(534,'MAIN_AGENDA_ACTIONAUTO_BILL_SUPPLIER_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:50'),(602,'MAIN_MODULE_PROJET',1,'1',NULL,0,NULL,'2010-07-11 13:26:54'),(610,'CASHDESK_ID_THIRDPARTY',1,'7','chaine',0,'','2010-07-11 17:08:18'),(611,'CASHDESK_ID_BANKACCOUNT_CASH',1,'3','chaine',0,'','2010-07-11 17:08:18'),(612,'CASHDESK_ID_BANKACCOUNT_CHEQUE',1,'1','chaine',0,'','2010-07-11 17:08:18'),(613,'CASHDESK_ID_BANKACCOUNT_CB',1,'1','chaine',0,'','2010-07-11 17:08:18'),(614,'CASHDESK_ID_WAREHOUSE',1,'2','chaine',0,'','2010-07-11 17:08:18'),(660,'LDAP_USER_DN',1,'ou=users,dc=my-domain,dc=com','chaine',0,NULL,'2010-07-18 10:25:27'),(661,'LDAP_GROUP_DN',1,'ou=groups,dc=my-domain,dc=com','chaine',0,NULL,'2010-07-18 10:25:27'),(662,'LDAP_FILTER_CONNECTION',1,'&(objectClass=user)(objectCategory=person)','chaine',0,NULL,'2010-07-18 10:25:27'),(663,'LDAP_FIELD_LOGIN',1,'uid','chaine',0,NULL,'2010-07-18 10:25:27'),(664,'LDAP_FIELD_FULLNAME',1,'cn','chaine',0,NULL,'2010-07-18 10:25:27'),(665,'LDAP_FIELD_NAME',1,'sn','chaine',0,NULL,'2010-07-18 10:25:27'),(666,'LDAP_FIELD_FIRSTNAME',1,'givenname','chaine',0,NULL,'2010-07-18 10:25:27'),(667,'LDAP_FIELD_MAIL',1,'mail','chaine',0,NULL,'2010-07-18 10:25:27'),(668,'LDAP_FIELD_PHONE',1,'telephonenumber','chaine',0,NULL,'2010-07-18 10:25:27'),(669,'LDAP_FIELD_FAX',1,'facsimiletelephonenumber','chaine',0,NULL,'2010-07-18 10:25:27'),(670,'LDAP_FIELD_MOBILE',1,'mobile','chaine',0,NULL,'2010-07-18 10:25:27'),(671,'LDAP_SERVER_TYPE',1,'openldap','chaine',0,'','2010-07-18 10:25:46'),(672,'LDAP_SERVER_PROTOCOLVERSION',1,'3','chaine',0,'','2010-07-18 10:25:47'),(673,'LDAP_SERVER_HOST',1,'localhost','chaine',0,'','2010-07-18 10:25:47'),(674,'LDAP_SERVER_PORT',1,'389','chaine',0,'','2010-07-18 10:25:47'),(675,'LDAP_SERVER_USE_TLS',1,'0','chaine',0,'','2010-07-18 10:25:47'),(676,'LDAP_SYNCHRO_ACTIVE',1,'dolibarr2ldap','chaine',0,'','2010-07-18 10:25:47'),(677,'LDAP_CONTACT_ACTIVE',1,'1','chaine',0,'','2010-07-18 10:25:47'),(678,'LDAP_MEMBER_ACTIVE',1,'1','chaine',0,'','2010-07-18 10:25:47'),(807,'MAIN_AGENDA_ACTIONAUTO_SHIPPING_VALIDATE',1,'1','chaine',0,NULL,'2011-07-18 17:27:52'),(808,'MAIN_AGENDA_ACTIONAUTO_SHIPPING_SENTBYMAIL',1,'1','chaine',0,NULL,'2011-07-18 17:27:52'),(834,'MAIN_MODULE_CASHDESK',1,'1',NULL,0,NULL,'2011-07-18 17:30:24'),(969,'MAIN_MODULE_PRELEVEMENT',1,'1',NULL,0,NULL,'2011-07-18 18:01:59'),(973,'MAIN_MODULE_WORKFLOW',1,'1',NULL,0,NULL,'2011-07-18 18:02:20'),(974,'MAIN_MODULE_WORKFLOW_TRIGGERS',1,'1','chaine',0,NULL,'2011-07-18 18:02:20'),(975,'WORKFLOW_PROPAL_AUTOCREATE_ORDER',1,'1','chaine',0,'','2011-07-18 18:02:24'),(978,'MAIN_MODULE_NOTIFICATION',1,'1',NULL,0,NULL,'2011-07-18 18:03:06'),(979,'PRELEVEMENT_USER',1,'1','chaine',0,'','2011-07-18 18:05:50'),(980,'PRELEVEMENT_NUMERO_NATIONAL_EMETTEUR',1,'1234567','chaine',0,'','2011-07-18 18:05:50'),(981,'PRELEVEMENT_ID_BANKACCOUNT',1,'1','chaine',0,'','2011-07-18 18:05:50'),(983,'FACTURE_RIB_NUMBER',1,'1','chaine',0,'','2011-07-18 18:35:14'),(984,'FACTURE_CHQ_NUMBER',1,'1','chaine',0,'','2011-07-18 18:35:14'),(1016,'GOOGLE_DUPLICATE_INTO_GCAL',1,'1','chaine',0,'','2011-07-18 21:40:20'),(1018,'MAIN_MODULE_SYSLOG',0,'1',NULL,0,NULL,'2011-07-20 11:36:47'),(1098,'MAIN_INFO_SOCIETE_LOGO',1,'dolibarr_125x125.png','chaine',0,'','2011-07-28 18:42:09'),(1099,'MAIN_INFO_SOCIETE_LOGO_SMALL',1,'dolibarr_125x125_small.png','chaine',0,'','2011-07-28 18:42:09'),(1100,'MAIN_INFO_SOCIETE_LOGO_MINI',1,'dolibarr_125x125_mini.png','chaine',0,'','2011-07-28 18:42:09'),(1138,'MAIN_VERSION_LAST_INSTALL',0,'3.1.0-beta','chaine',0,'Dolibarr version when install','2011-07-28 23:05:02'),(1152,'SOCIETE_CODECLIENT_ADDON',1,'mod_codeclient_monkey','chaine',0,'','2011-07-29 20:50:02'),(1231,'MAIN_UPLOAD_DOC',1,'2048','chaine',0,'','2011-07-29 21:04:00'),(1234,'MAIN_UMASK',1,'0664','chaine',0,'','2011-07-29 21:04:11'),(1240,'MAIN_LOGEVENTS_USER_LOGIN',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1241,'MAIN_LOGEVENTS_USER_LOGIN_FAILED',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1242,'MAIN_LOGEVENTS_USER_LOGOUT',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1243,'MAIN_LOGEVENTS_USER_CREATE',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1244,'MAIN_LOGEVENTS_USER_MODIFY',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1245,'MAIN_LOGEVENTS_USER_NEW_PASSWORD',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1246,'MAIN_LOGEVENTS_USER_ENABLEDISABLE',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1247,'MAIN_LOGEVENTS_USER_DELETE',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1248,'MAIN_LOGEVENTS_GROUP_CREATE',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1249,'MAIN_LOGEVENTS_GROUP_MODIFY',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1250,'MAIN_LOGEVENTS_GROUP_DELETE',1,'1','chaine',0,'','2011-07-29 21:05:01'),(1251,'MAIN_BOXES_MAXLINES',1,'5','',0,'','2011-07-29 21:05:42'),(1379,'CABINETMED_RHEUMATOLOGY_ON',1,'1','chaine',1,'Enable features for rheumatology','2011-08-01 21:47:53'),(1482,'EXPEDITION_ADDON_NUMBER',1,'mod_expedition_safor','chaine',0,'Nom du gestionnaire de numerotation des expeditions','2011-08-05 17:53:11'),(1490,'CONTRACT_ADDON',1,'mod_contract_serpis','chaine',0,'Nom du gestionnaire de numerotation des contrats','2011-08-05 18:11:58'),(1677,'COMMANDE_ADDON_PDF_ODT_PATH',1,'DOL_DATA_ROOT/doctemplates/orders','chaine',0,NULL,'2012-12-08 13:11:02'),(1698,'PRODUCT_CODEPRODUCT_ADDON',1,'mod_codeproduct_leopard','yesno',0,'Module to control product codes','2012-12-08 13:11:25'),(1718,'MAIN_MODULE_TAX',1,'1',NULL,0,NULL,'2012-12-08 13:12:41'),(1719,'ACCOUNTING_USEDICTTOEDIT',1,'1','chaine',1,'','2012-12-08 13:15:00'),(1724,'PROPALE_ADDON_PDF_ODT_PATH',1,'DOL_DATA_ROOT/doctemplates/proposals','chaine',0,NULL,'2012-12-08 13:17:14'),(1730,'OPENSTREETMAP_ENABLE_MAPS',1,'1','chaine',0,'','2012-12-08 13:22:47'),(1731,'OPENSTREETMAP_ENABLE_MAPS_CONTACTS',1,'1','chaine',0,'','2012-12-08 13:22:47'),(1732,'OPENSTREETMAP_ENABLE_MAPS_MEMBERS',1,'1','chaine',0,'','2012-12-08 13:22:47'),(1733,'OPENSTREETMAP_MAPS_ZOOM_LEVEL',1,'15','chaine',0,'','2012-12-08 13:22:47'),(1737,'MAIN_INFO_SOCIETE_COUNTRY',2,'1:FR:France','chaine',0,'','2013-02-26 21:56:28'),(1738,'MAIN_INFO_SOCIETE_NOM',2,'aaa','chaine',0,'','2012-12-08 14:08:14'),(1739,'MAIN_INFO_SOCIETE_STATE',2,'0','chaine',0,'','2013-02-27 14:20:27'),(1740,'MAIN_MONNAIE',2,'EUR','chaine',0,'','2012-12-08 14:08:14'),(1741,'MAIN_LANG_DEFAULT',2,'auto','chaine',0,'','2012-12-08 14:08:14'),(1742,'MAIN_MAIL_EMAIL_FROM',2,'dolibarr-robot@domain.com','chaine',0,'EMail emetteur pour les emails automatiques Dolibarr','2012-12-08 14:08:14'),(1743,'MAIN_MENU_STANDARD',2,'eldy_menu.php','chaine',0,'Module de gestion de la barre de menu du haut pour utilisateurs internes','2013-02-11 19:43:54'),(1744,'MAIN_MENUFRONT_STANDARD',2,'eldy_menu.php','chaine',0,'Module de gestion de la barre de menu du haut pour utilisateurs externes','2013-02-11 19:43:54'),(1745,'MAIN_MENU_SMARTPHONE',2,'iphone_backoffice.php','chaine',0,'Module de gestion de la barre de menu smartphone pour utilisateurs internes','2012-12-08 14:08:14'),(1746,'MAIN_MENUFRONT_SMARTPHONE',2,'iphone_frontoffice.php','chaine',0,'Module de gestion de la barre de menu smartphone pour utilisateurs externes','2012-12-08 14:08:14'),(1747,'MAIN_THEME',2,'eldy','chaine',0,'Default theme','2012-12-08 14:08:14'),(1748,'MAIN_DELAY_ACTIONS_TODO',2,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur actions planifiées non réalisées','2012-12-08 14:08:14'),(1749,'MAIN_DELAY_ORDERS_TO_PROCESS',2,'2','chaine',0,'Tolérance de retard avant alerte (en jours) sur commandes clients non traitées','2012-12-08 14:08:14'),(1750,'MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS',2,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur commandes fournisseurs non traitées','2012-12-08 14:08:14'),(1751,'MAIN_DELAY_PROPALS_TO_CLOSE',2,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur propales à cloturer','2012-12-08 14:08:14'),(1752,'MAIN_DELAY_PROPALS_TO_BILL',2,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur propales non facturées','2012-12-08 14:08:14'),(1753,'MAIN_DELAY_CUSTOMER_BILLS_UNPAYED',2,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur factures client impayées','2012-12-08 14:08:14'),(1754,'MAIN_DELAY_SUPPLIER_BILLS_TO_PAY',2,'2','chaine',0,'Tolérance de retard avant alerte (en jours) sur factures fournisseur impayées','2012-12-08 14:08:14'),(1755,'MAIN_DELAY_NOT_ACTIVATED_SERVICES',2,'0','chaine',0,'Tolérance de retard avant alerte (en jours) sur services à activer','2012-12-08 14:08:14'),(1756,'MAIN_DELAY_RUNNING_SERVICES',2,'0','chaine',0,'Tolérance de retard avant alerte (en jours) sur services expirés','2012-12-08 14:08:14'),(1757,'MAIN_DELAY_MEMBERS',2,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur cotisations adhérent en retard','2012-12-08 14:08:14'),(1758,'MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE',2,'62','chaine',0,'Tolérance de retard avant alerte (en jours) sur rapprochements bancaires à faire','2012-12-08 14:08:14'),(1759,'MAILING_EMAIL_FROM',2,'dolibarr@domain.com','chaine',0,'EMail emmetteur pour les envois d emailings','2012-12-08 14:08:14'),(1760,'MAIN_INFO_SOCIETE_COUNTRY',3,'1:FR:France','chaine',0,'','2013-02-26 21:56:28'),(1761,'MAIN_INFO_SOCIETE_NOM',3,'bbb','chaine',0,'','2012-12-08 14:08:20'),(1762,'MAIN_INFO_SOCIETE_STATE',3,'0','chaine',0,'','2013-02-27 14:20:27'),(1763,'MAIN_MONNAIE',3,'EUR','chaine',0,'','2012-12-08 14:08:20'),(1764,'MAIN_LANG_DEFAULT',3,'auto','chaine',0,'','2012-12-08 14:08:20'),(1765,'MAIN_MAIL_EMAIL_FROM',3,'dolibarr-robot@domain.com','chaine',0,'EMail emetteur pour les emails automatiques Dolibarr','2012-12-08 14:08:20'),(1766,'MAIN_MENU_STANDARD',3,'eldy_menu.php','chaine',0,'Module de gestion de la barre de menu du haut pour utilisateurs internes','2013-02-11 19:43:54'),(1767,'MAIN_MENUFRONT_STANDARD',3,'eldy_menu.php','chaine',0,'Module de gestion de la barre de menu du haut pour utilisateurs externes','2013-02-11 19:43:54'),(1768,'MAIN_MENU_SMARTPHONE',3,'iphone_backoffice.php','chaine',0,'Module de gestion de la barre de menu smartphone pour utilisateurs internes','2012-12-08 14:08:20'),(1769,'MAIN_MENUFRONT_SMARTPHONE',3,'iphone_frontoffice.php','chaine',0,'Module de gestion de la barre de menu smartphone pour utilisateurs externes','2012-12-08 14:08:20'),(1770,'MAIN_THEME',3,'eldy','chaine',0,'Default theme','2012-12-08 14:08:20'),(1771,'MAIN_DELAY_ACTIONS_TODO',3,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur actions planifiées non réalisées','2012-12-08 14:08:20'),(1772,'MAIN_DELAY_ORDERS_TO_PROCESS',3,'2','chaine',0,'Tolérance de retard avant alerte (en jours) sur commandes clients non traitées','2012-12-08 14:08:20'),(1773,'MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS',3,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur commandes fournisseurs non traitées','2012-12-08 14:08:20'),(1774,'MAIN_DELAY_PROPALS_TO_CLOSE',3,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur propales à cloturer','2012-12-08 14:08:20'),(1775,'MAIN_DELAY_PROPALS_TO_BILL',3,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur propales non facturées','2012-12-08 14:08:20'),(1776,'MAIN_DELAY_CUSTOMER_BILLS_UNPAYED',3,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur factures client impayées','2012-12-08 14:08:20'),(1777,'MAIN_DELAY_SUPPLIER_BILLS_TO_PAY',3,'2','chaine',0,'Tolérance de retard avant alerte (en jours) sur factures fournisseur impayées','2012-12-08 14:08:20'),(1778,'MAIN_DELAY_NOT_ACTIVATED_SERVICES',3,'0','chaine',0,'Tolérance de retard avant alerte (en jours) sur services à activer','2012-12-08 14:08:20'),(1779,'MAIN_DELAY_RUNNING_SERVICES',3,'0','chaine',0,'Tolérance de retard avant alerte (en jours) sur services expirés','2012-12-08 14:08:20'),(1780,'MAIN_DELAY_MEMBERS',3,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur cotisations adhérent en retard','2012-12-08 14:08:20'),(1781,'MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE',3,'62','chaine',0,'Tolérance de retard avant alerte (en jours) sur rapprochements bancaires à faire','2012-12-08 14:08:20'),(1782,'MAILING_EMAIL_FROM',3,'dolibarr@domain.com','chaine',0,'EMail emmetteur pour les envois d emailings','2012-12-08 14:08:20'),(1803,'SYSLOG_FILE',1,'DOL_DATA_ROOT/dolibarr.log','chaine',0,'','2012-12-08 14:15:08'),(1804,'SYSLOG_HANDLERS',1,'[\"mod_syslog_file\"]','chaine',0,'','2012-12-08 14:15:08'),(1805,'MAIN_MODULE_SKINCOLOREDITOR',3,'1',NULL,0,NULL,'2012-12-08 14:35:40'),(1806,'MAIN_MODULE_SKINCOLOREDITOR_TABS_0',3,'user:+tabskincoloreditors:ColorEditor:skincoloreditor@skincoloreditor:/skincoloreditor/usercolors.php?id=__ID__','chaine',0,NULL,'2012-12-08 14:35:40'),(1867,'MAIN_MODULE_PAYPAL',1,'1',NULL,0,NULL,'2012-12-11 22:53:56'),(1922,'PAYPAL_API_SANDBOX',1,'1','chaine',0,'','2012-12-12 12:11:05'),(1923,'PAYPAL_API_USER',1,'seller_1355312017_biz_api1.nltechno.com','chaine',0,'','2012-12-12 12:11:05'),(1924,'PAYPAL_API_PASSWORD',1,'1355312040','chaine',0,'','2012-12-12 12:11:05'),(1925,'PAYPAL_API_SIGNATURE',1,'AXqqdsWBzvfn0q5iNmbuiDv1y.3EAXIMWyl4C5KvDReR9HDwwAd6dQ4Q','chaine',0,'','2012-12-12 12:11:05'),(1926,'PAYPAL_API_INTEGRAL_OR_PAYPALONLY',1,'integral','chaine',0,'','2012-12-12 12:11:05'),(1927,'PAYPAL_SECURITY_TOKEN',1,'50c82fab36bb3b6aa83e2a50691803b2','chaine',0,'','2012-12-12 12:11:05'),(1928,'PAYPAL_SECURITY_TOKEN_UNIQUE',1,'0','chaine',0,'','2012-12-12 12:11:05'),(1929,'PAYPAL_ADD_PAYMENT_URL',1,'1','chaine',0,'','2012-12-12 12:11:05'),(1980,'MAIN_PDF_FORMAT',1,'EUA4','chaine',0,'','2012-12-12 19:58:05'),(1981,'MAIN_PROFID1_IN_ADDRESS',1,'0','chaine',0,'','2012-12-12 19:58:05'),(1982,'MAIN_PROFID2_IN_ADDRESS',1,'0','chaine',0,'','2012-12-12 19:58:05'),(1983,'MAIN_PROFID3_IN_ADDRESS',1,'0','chaine',0,'','2012-12-12 19:58:05'),(1984,'MAIN_PROFID4_IN_ADDRESS',1,'0','chaine',0,'','2012-12-12 19:58:05'),(1985,'MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT',1,'0','chaine',0,'','2012-12-12 19:58:05'),(1990,'MAIN_SMS_SENDMODE',1,'ovh','chaine',0,'This is to enable OVH SMS engine','2012-12-17 21:19:01'),(2040,'MAIN_MAIL_SMTP_PORT',0,'465','chaine',0,'','2012-12-19 12:58:10'),(2041,'MAIN_MAIL_SMTP_SERVER',0,'smtp.mail.com','chaine',0,'','2012-12-19 12:58:10'),(2044,'MAIN_MAIL_EMAIL_TLS',0,'1','chaine',0,'','2012-12-19 12:58:10'),(2251,'FCKEDITOR_TEST',1,'Test<br />\r\n<img alt=\"\" src=\"/dolibarrnew/viewimage.php?modulepart=fckeditor&amp;file=image/Julien-Lavergne_reference_medium.jpg\" style=\"width: 200px; height: 172px;\" />fdfs','chaine',0,'','2012-12-19 19:12:24'),(2293,'SYSTEMTOOLS_MYSQLDUMP',1,'/usr/bin/mysqldump','chaine',0,'','2012-12-27 02:02:00'),(2305,'MAIN_MODULE_PROPALE',1,'1',NULL,0,NULL,'2013-01-02 20:33:16'),(2307,'MAIN_MODULE_CONTRAT',1,'1',NULL,0,NULL,'2013-01-02 20:33:17'),(2310,'MAIN_MODULE_EXPEDITION',1,'1',NULL,0,NULL,'2013-01-02 20:33:18'),(2315,'MAIN_MODULE_FICHEINTER',1,'1',NULL,0,NULL,'2013-01-02 20:33:21'),(2321,'MAIN_MODULE_HOLIDAY',1,'1',NULL,0,NULL,'2013-01-02 20:33:24'),(2322,'MAIN_MODULE_HOLIDAY_TABS_0',1,'user:+paidholidays:CPTitreMenu:holiday:$user->rights->holiday->write:/holiday/index.php?mainmenu=holiday&id=__ID__','chaine',0,NULL,'2013-01-02 20:33:24'),(2786,'MAIN_SOAP_DEBUG',1,'1','chaine',1,'','2013-01-13 12:37:21'),(2835,'MAIN_USE_CONNECT_TIMEOUT',1,'10','chaine',0,'','2013-01-16 19:28:50'),(2836,'MAIN_USE_RESPONSE_TIMEOUT',1,'30','chaine',0,'','2013-01-16 19:28:50'),(2837,'MAIN_PROXY_USE',1,'0','chaine',0,'','2013-01-16 19:28:50'),(2838,'MAIN_PROXY_HOST',1,'localhost','chaine',0,'','2013-01-16 19:28:50'),(2839,'MAIN_PROXY_PORT',1,'8080','chaine',0,'','2013-01-16 19:28:50'),(2840,'MAIN_PROXY_USER',1,'aaa','chaine',0,'','2013-01-16 19:28:50'),(2841,'MAIN_PROXY_PASS',1,'bbb','chaine',0,'','2013-01-16 19:28:50'),(2848,'OVHSMS_NICK',1,'BN196-OVH','chaine',0,'','2013-01-16 19:32:36'),(2849,'OVHSMS_PASS',1,'bigone-10','chaine',0,'','2013-01-16 19:32:36'),(2850,'OVHSMS_SOAPURL',1,'https://www.ovh.com/soapi/soapi-re-1.55.wsdl','chaine',0,'','2013-01-16 19:32:36'),(2854,'THEME_ELDY_RGB',1,'bfbf00','chaine',0,'','2013-01-18 10:02:53'),(2855,'THEME_ELDY_ENABLE_PERSONALIZED',1,'0','chaine',0,'','2013-01-18 10:02:55'),(2858,'MAIN_SESSION_TIMEOUT',1,'2000','chaine',0,'','2013-01-19 17:01:53'),(2862,'TICKET_ADDON',1,'mod_ticket_avenc','chaine',0,'Nom du gestionnaire de numerotation des tickets','2013-01-19 17:16:10'),(2866,'MAIN_MODULE_PRODUCT',1,'1',NULL,0,NULL,'2013-01-19 17:16:10'),(2867,'FACSIM_ADDON',1,'mod_facsim_alcoy','chaine',0,'','2013-01-19 17:16:25'),(2868,'POS_SERVICES',1,'0','chaine',0,'','2013-01-19 17:16:51'),(2869,'POS_USE_TICKETS',1,'1','chaine',0,'','2013-01-19 17:16:51'),(2870,'POS_MAX_TTC',1,'100','chaine',0,'','2013-01-19 17:16:51'),(3190,'MAIN_MODULE_HOLIDAY',2,'1',NULL,0,NULL,'2013-02-01 08:52:34'),(3191,'MAIN_MODULE_HOLIDAY_TABS_0',2,'user:+paidholidays:CPTitreMenu:holiday:$user->rights->holiday->write:/holiday/index.php?mainmenu=holiday&id=__ID__','chaine',0,NULL,'2013-02-01 08:52:34'),(3195,'INVOICE_SUPPLIER_ADDON_PDF',1,'canelle','chaine',0,'','2013-02-10 19:50:27'),(3199,'MAIN_FORCE_RELOAD_PAGE',1,'1','chaine',0,NULL,'2013-02-12 16:22:55'),(3217,'MAIN_PDF_TITLE_BACKGROUND_COLOR',1,'240,240,240','chaine',1,'','2013-02-13 15:18:02'),(3223,'OVH_THIRDPARTY_IMPORT',1,'2','chaine',0,'','2013-02-13 16:20:18'),(3241,'COMPANY_USE_SEARCH_TO_SELECT',1,'2','chaine',0,'','2013-02-17 14:33:39'),(3409,'AGENDA_USE_EVENT_TYPE',1,'1','chaine',0,'','2013-02-27 18:12:24'),(3886,'MAIN_REMOVE_INSTALL_WARNING',1,'1','chaine',1,'','2013-03-02 18:32:50'),(4013,'MAIN_DELAY_ACTIONS_TODO',1,'7','chaine',0,'','2013-03-06 08:59:12'),(4014,'MAIN_DELAY_PROPALS_TO_CLOSE',1,'31','chaine',0,'','2013-03-06 08:59:12'),(4015,'MAIN_DELAY_PROPALS_TO_BILL',1,'7','chaine',0,'','2013-03-06 08:59:12'),(4016,'MAIN_DELAY_ORDERS_TO_PROCESS',1,'2','chaine',0,'','2013-03-06 08:59:12'),(4017,'MAIN_DELAY_CUSTOMER_BILLS_UNPAYED',1,'31','chaine',0,'','2013-03-06 08:59:12'),(4018,'MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS',1,'7','chaine',0,'','2013-03-06 08:59:12'),(4019,'MAIN_DELAY_SUPPLIER_BILLS_TO_PAY',1,'2','chaine',0,'','2013-03-06 08:59:12'),(4020,'MAIN_DELAY_RUNNING_SERVICES',1,'-15','chaine',0,'','2013-03-06 08:59:12'),(4021,'MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE',1,'62','chaine',0,'','2013-03-06 08:59:13'),(4022,'MAIN_DELAY_MEMBERS',1,'31','chaine',0,'','2013-03-06 08:59:13'),(4023,'MAIN_DISABLE_METEO',1,'0','chaine',0,'','2013-03-06 08:59:13'),(4044,'ADHERENT_VAT_FOR_SUBSCRIPTIONS',1,'0','',0,'','2013-03-06 16:06:38'),(4047,'ADHERENT_BANK_USE',1,'bankviainvoice','',0,'','2013-03-06 16:12:30'),(4049,'PHPSANE_SCANIMAGE',1,'/usr/bin/scanimage','chaine',0,'','2013-03-06 21:54:13'),(4050,'PHPSANE_PNMTOJPEG',1,'/usr/bin/pnmtojpeg','chaine',0,'','2013-03-06 21:54:13'),(4051,'PHPSANE_PNMTOTIFF',1,'/usr/bin/pnmtotiff','chaine',0,'','2013-03-06 21:54:13'),(4052,'PHPSANE_OCR',1,'/usr/bin/gocr','chaine',0,'','2013-03-06 21:54:13'),(4548,'ECM_AUTO_TREE_ENABLED',1,'1','chaine',0,'','2013-03-10 15:57:21'),(4555,'WEBSERVICES_KEY',1,'dolibarrkey','chaine',0,'','2013-03-13 10:19:31'),(4579,'MAIN_MODULE_AGENDA',2,'1',NULL,0,NULL,'2013-03-13 15:29:19'),(4580,'MAIN_AGENDA_ACTIONAUTO_COMPANY_CREATE',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4581,'MAIN_AGENDA_ACTIONAUTO_CONTRACT_VALIDATE',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4582,'MAIN_AGENDA_ACTIONAUTO_PROPAL_VALIDATE',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4583,'MAIN_AGENDA_ACTIONAUTO_PROPAL_SENTBYMAIL',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4584,'MAIN_AGENDA_ACTIONAUTO_ORDER_VALIDATE',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4585,'MAIN_AGENDA_ACTIONAUTO_ORDER_SENTBYMAIL',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4586,'MAIN_AGENDA_ACTIONAUTO_BILL_VALIDATE',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4587,'MAIN_AGENDA_ACTIONAUTO_BILL_PAYED',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4588,'MAIN_AGENDA_ACTIONAUTO_BILL_CANCEL',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4589,'MAIN_AGENDA_ACTIONAUTO_BILL_SENTBYMAIL',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4590,'MAIN_AGENDA_ACTIONAUTO_ORDER_SUPPLIER_VALIDATE',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4591,'MAIN_AGENDA_ACTIONAUTO_BILL_SUPPLIER_VALIDATE',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4592,'MAIN_AGENDA_ACTIONAUTO_SHIPPING_VALIDATE',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4593,'MAIN_AGENDA_ACTIONAUTO_SHIPPING_SENTBYMAIL',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4594,'MAIN_AGENDA_ACTIONAUTO_BILL_UNVALIDATE',2,'1','chaine',0,NULL,'2013-03-13 15:29:19'),(4595,'MAIN_MODULE_GOOGLE',2,'1',NULL,0,NULL,'2013-03-13 15:29:47'),(4596,'MAIN_MODULE_GOOGLE_TABS_0',2,'agenda:+gcal:MenuAgendaGoogle:google@google:$conf->google->enabled && $conf->global->GOOGLE_ENABLE_AGENDA:/google/index.php','chaine',0,NULL,'2013-03-13 15:29:47'),(4597,'MAIN_MODULE_GOOGLE_TABS_1',2,'user:+gsetup:GoogleUserConf:google@google:$conf->google->enabled && $conf->global->GOOGLE_DUPLICATE_INTO_GCAL:/google/admin/google_calsync_user.php?id=__ID__','chaine',0,NULL,'2013-03-13 15:29:47'),(4598,'MAIN_MODULE_GOOGLE_TRIGGERS',2,'1','chaine',0,NULL,'2013-03-13 15:29:47'),(4599,'MAIN_MODULE_GOOGLE_HOOKS',2,'[\"toprightmenu\"]','chaine',0,NULL,'2013-03-13 15:29:47'),(4688,'GOOGLE_ENABLE_AGENDA',2,'1','chaine',0,'','2013-03-13 15:36:29'),(4689,'GOOGLE_AGENDA_NAME1',2,'eldy','chaine',0,'','2013-03-13 15:36:29'),(4690,'GOOGLE_AGENDA_SRC1',2,'eldy10@mail.com','chaine',0,'','2013-03-13 15:36:29'),(4691,'GOOGLE_AGENDA_COLOR1',2,'BE6D00','chaine',0,'','2013-03-13 15:36:29'),(4692,'GOOGLE_AGENDA_COLOR2',2,'7A367A','chaine',0,'','2013-03-13 15:36:29'),(4693,'GOOGLE_AGENDA_COLOR3',2,'7A367A','chaine',0,'','2013-03-13 15:36:29'),(4694,'GOOGLE_AGENDA_COLOR4',2,'7A367A','chaine',0,'','2013-03-13 15:36:29'),(4695,'GOOGLE_AGENDA_COLOR5',2,'7A367A','chaine',0,'','2013-03-13 15:36:29'),(4696,'GOOGLE_AGENDA_TIMEZONE',2,'Europe/Paris','chaine',0,'','2013-03-13 15:36:29'),(4697,'GOOGLE_AGENDA_NB',2,'5','chaine',0,'','2013-03-13 15:36:29'),(4702,'MAIN_FEATURES_LEVEL',0,'1','chaine',1,'Level of features to show (0=stable only, 1=stable+experimental, 2=stable+experimental+development','2013-03-13 18:41:52'),(4711,'GOOGLE_ENABLE_AGENDA',1,'1','chaine',0,'','2013-03-13 19:37:38'),(4712,'GOOGLE_AGENDA_NAME1',1,'asso master','chaine',0,'','2013-03-13 19:37:38'),(4713,'GOOGLE_AGENDA_SRC1',1,'assodolibarr@mail.com','chaine',0,'','2013-03-13 19:37:38'),(4714,'GOOGLE_AGENDA_COLOR1',1,'1B887A','chaine',0,'','2013-03-13 19:37:38'),(4715,'GOOGLE_AGENDA_COLOR2',1,'7A367A','chaine',0,'','2013-03-13 19:37:38'),(4716,'GOOGLE_AGENDA_COLOR3',1,'7A367A','chaine',0,'','2013-03-13 19:37:38'),(4717,'GOOGLE_AGENDA_COLOR4',1,'7A367A','chaine',0,'','2013-03-13 19:37:38'),(4718,'GOOGLE_AGENDA_COLOR5',1,'7A367A','chaine',0,'','2013-03-13 19:37:38'),(4719,'GOOGLE_AGENDA_TIMEZONE',1,'Europe/Paris','chaine',0,'','2013-03-13 19:37:38'),(4720,'GOOGLE_AGENDA_NB',1,'5','chaine',0,'','2013-03-13 19:37:38'),(4725,'SOCIETE_CODECLIENT_ADDON',2,'mod_codeclient_leopard','chaine',0,'Module to control third parties codes','2013-03-13 20:21:35'),(4726,'SOCIETE_CODECOMPTA_ADDON',2,'mod_codecompta_panicum','chaine',0,'Module to control third parties codes','2013-03-13 20:21:35'),(4727,'SOCIETE_FISCAL_MONTH_START',2,'','chaine',0,'Mettre le numero du mois du debut d\\\'annee fiscale, ex: 9 pour septembre','2013-03-13 20:21:35'),(4728,'MAIN_SEARCHFORM_SOCIETE',2,'1','yesno',0,'Show form for quick company search','2013-03-13 20:21:35'),(4729,'MAIN_SEARCHFORM_CONTACT',2,'1','yesno',0,'Show form for quick contact search','2013-03-13 20:21:35'),(4730,'COMPANY_ADDON_PDF_ODT_PATH',2,'DOL_DATA_ROOT/doctemplates/thirdparties','chaine',0,NULL,'2013-03-13 20:21:35'),(4743,'MAIN_MODULE_CLICKTODIAL',2,'1',NULL,0,NULL,'2013-03-13 20:30:28'),(4744,'MAIN_MODULE_NOTIFICATION',2,'1',NULL,0,NULL,'2013-03-13 20:30:34'),(4745,'MAIN_MODULE_WEBSERVICES',2,'1',NULL,0,NULL,'2013-03-13 20:30:41'),(4746,'MAIN_MODULE_PROPALE',2,'1',NULL,0,NULL,'2013-03-13 20:32:38'),(4747,'PROPALE_ADDON_PDF',2,'azur','chaine',0,'Nom du gestionnaire de generation des propales en PDF','2013-03-13 20:32:38'),(4748,'PROPALE_ADDON',2,'mod_propale_marbre','chaine',0,'Nom du gestionnaire de numerotation des propales','2013-03-13 20:32:38'),(4749,'PROPALE_VALIDITY_DURATION',2,'15','chaine',0,'Duration of validity of business proposals','2013-03-13 20:32:38'),(4750,'PROPALE_ADDON_PDF_ODT_PATH',2,'DOL_DATA_ROOT/doctemplates/proposals','chaine',0,NULL,'2013-03-13 20:32:38'),(4752,'MAIN_MODULE_TAX',2,'1',NULL,0,NULL,'2013-03-13 20:32:47'),(4753,'MAIN_MODULE_DON',2,'1',NULL,0,NULL,'2013-03-13 20:32:54'),(4754,'DON_ADDON_MODEL',2,'html_cerfafr','chaine',0,'Nom du gestionnaire de generation de recu de dons','2013-03-13 20:32:54'),(4755,'POS_USE_TICKETS',2,'1','chaine',0,'','2013-03-13 20:33:09'),(4756,'POS_MAX_TTC',2,'100','chaine',0,'','2013-03-13 20:33:09'),(4757,'MAIN_MODULE_POS',2,'1',NULL,0,NULL,'2013-03-13 20:33:09'),(4758,'TICKET_ADDON',2,'mod_ticket_avenc','chaine',0,'Nom du gestionnaire de numerotation des tickets','2013-03-13 20:33:09'),(4759,'MAIN_MODULE_BANQUE',2,'1',NULL,0,NULL,'2013-03-13 20:33:09'),(4760,'MAIN_MODULE_FACTURE',2,'1',NULL,0,NULL,'2013-03-13 20:33:09'),(4761,'FACTURE_ADDON_PDF',2,'crabe','chaine',0,'Name of PDF model of invoice','2013-03-13 20:33:09'),(4762,'FACTURE_ADDON',2,'mod_facture_terre','chaine',0,'Name of numbering numerotation rules of invoice','2013-03-13 20:33:09'),(4763,'FACTURE_ADDON_PDF_ODT_PATH',2,'DOL_DATA_ROOT/doctemplates/invoices','chaine',0,NULL,'2013-03-13 20:33:09'),(4764,'MAIN_MODULE_SOCIETE',2,'1',NULL,0,NULL,'2013-03-13 20:33:09'),(4765,'MAIN_MODULE_PRODUCT',2,'1',NULL,0,NULL,'2013-03-13 20:33:09'),(4766,'PRODUCT_CODEPRODUCT_ADDON',2,'mod_codeproduct_leopard','chaine',0,'Module to control product codes','2013-03-13 20:33:09'),(4767,'MAIN_SEARCHFORM_PRODUITSERVICE',2,'1','yesno',0,'Show form for quick product search','2013-03-13 20:33:09'),(4772,'FACSIM_ADDON',2,'mod_facsim_alcoy','chaine',0,'','2013-03-13 20:33:32'),(4773,'MAIN_MODULE_MAILING',2,'1',NULL,0,NULL,'2013-03-13 20:33:37'),(4774,'MAIN_MODULE_OPENSURVEY',2,'1',NULL,0,NULL,'2013-03-13 20:33:42'),(4782,'AGENDA_USE_EVENT_TYPE',2,'1','chaine',0,'','2013-03-13 20:53:36'),(4884,'AGENDA_DISABLE_EXT',2,'1','chaine',0,'','2013-03-13 22:03:40'),(4919,'MAIN_MODULE_COMPTABILITE',1,'1',NULL,0,NULL,'2013-03-20 20:04:28'),(4922,'MAIN_MODULE_BANQUE',1,'1',NULL,0,NULL,'2013-03-20 20:04:28'),(4928,'COMMANDE_SUPPLIER_ADDON_NUMBER',1,'mod_commande_fournisseur_muguet','chaine',0,'Nom du gestionnaire de numerotation des commandes fournisseur','2013-03-22 09:24:29'),(4929,'INVOICE_SUPPLIER_ADDON_NUMBER',1,'mod_facture_fournisseur_cactus','chaine',0,'Nom du gestionnaire de numerotation des factures fournisseur','2013-03-22 09:24:29'),(4986,'MAIN_MODULE_CRON',1,'1',NULL,0,NULL,'2013-03-23 17:24:25'),(5001,'MAIN_CRON_KEY',0,'bc54582fe30d5d4a830c6f582ec28810','chaine',0,'','2013-03-23 17:54:53'),(5009,'CRON_KEY',0,'2c2e755c20be2014098f629865598006','chaine',0,'','2013-03-23 18:06:24'),(5075,'MAIN_MENU_STANDARD',1,'eldy_menu.php','chaine',0,'','2013-03-24 02:51:13'),(5076,'MAIN_MENU_SMARTPHONE',1,'eldy_menu.php','chaine',0,'','2013-03-24 02:51:13'),(5077,'MAIN_MENUFRONT_STANDARD',1,'eldy_menu.php','chaine',0,'','2013-03-24 02:51:13'),(5078,'MAIN_MENUFRONT_SMARTPHONE',1,'eldy_menu.php','chaine',0,'','2013-03-24 02:51:13'),(5079,'MAIN_MODULE_OPENSURVEY',1,'1',NULL,0,NULL,'2013-03-24 02:57:18'),(5083,'FCKEDITOR_ENABLE_USERSIGN',1,'1','chaine',1,'','2013-03-24 15:59:39'),(5102,'MAIN_INFO_SOCIETE_COUNTRY',1,'1:FR:France','chaine',0,'','2013-03-24 18:34:54'),(5103,'MAIN_INFO_SOCIETE_NOM',1,'MyBigCompany','chaine',0,'','2013-03-24 18:34:54'),(5104,'MAIN_INFO_SOCIETE_ADDRESS',1,'21 Jump street','chaine',0,'','2013-03-24 18:34:54'),(5105,'MAIN_INFO_SOCIETE_TOWN',1,'MyTown','chaine',0,'','2013-03-24 18:34:54'),(5106,'MAIN_INFO_SOCIETE_ZIP',1,'75500','chaine',0,'','2013-03-24 18:34:54'),(5107,'MAIN_INFO_SOCIETE_STATE',1,'0','chaine',0,'','2013-03-24 18:34:54'),(5108,'MAIN_MONNAIE',1,'EUR','chaine',0,'','2013-03-24 18:34:54'),(5109,'MAIN_INFO_SOCIETE_TEL',1,'09123123','chaine',0,'','2013-03-24 18:34:54'),(5110,'MAIN_INFO_SOCIETE_FAX',1,'09123124','chaine',0,'','2013-03-24 18:34:54'),(5111,'MAIN_INFO_SOCIETE_MAIL',1,'myemail@mybigcompany.com','chaine',0,'','2013-03-24 18:34:54'),(5112,'MAIN_INFO_SOCIETE_WEB',1,'http://www.dolibarr.org','chaine',0,'','2013-03-24 18:34:54'),(5113,'MAIN_INFO_SOCIETE_NOTE',1,'This is note about my company','chaine',0,'','2013-03-24 18:34:54'),(5114,'MAIN_INFO_CAPITAL',1,'10000','chaine',0,'','2013-03-24 18:34:54'),(5115,'MAIN_INFO_SOCIETE_FORME_JURIDIQUE',1,'0','chaine',0,'','2013-03-24 18:34:54'),(5116,'MAIN_INFO_TVAINTRA',1,'IN1234567','chaine',0,'','2013-03-24 18:34:54'),(5117,'SOCIETE_FISCAL_MONTH_START',1,'0','chaine',0,'','2013-03-24 18:34:54'),(5118,'FACTURE_TVAOPTION',1,'reel','chaine',0,'','2013-03-24 18:34:54'),(5119,'MAIN_LANG_DEFAULT',1,'en_US','chaine',0,'','2013-03-24 18:35:07'),(5120,'MAIN_MULTILANGS',1,'1','chaine',0,'','2013-03-24 18:35:07'),(5121,'MAIN_SIZE_LISTE_LIMIT',1,'25','chaine',0,'','2013-03-24 18:35:07'),(5122,'MAIN_DISABLE_JAVASCRIPT',1,'0','chaine',0,'','2013-03-24 18:35:07'),(5123,'MAIN_BUTTON_HIDE_UNAUTHORIZED',1,'0','chaine',0,'','2013-03-24 18:35:07'),(5124,'MAIN_START_WEEK',1,'1','chaine',0,'','2013-03-24 18:35:07'),(5125,'MAIN_SHOW_LOGO',1,'0','chaine',0,'','2013-03-24 18:35:07'),(5126,'MAIN_FIRSTNAME_NAME_POSITION',1,'0','chaine',0,'','2013-03-24 18:35:07'),(5127,'MAIN_THEME',1,'eldy','chaine',0,'','2013-03-24 18:35:07'),(5128,'MAIN_SEARCHFORM_CONTACT',1,'1','chaine',0,'','2013-03-24 18:35:07'),(5129,'MAIN_SEARCHFORM_SOCIETE',1,'1','chaine',0,'','2013-03-24 18:35:07'),(5130,'MAIN_SEARCHFORM_PRODUITSERVICE',1,'1','chaine',0,'','2013-03-24 18:35:07'),(5131,'MAIN_SEARCHFORM_ADHERENT',1,'1','chaine',0,'','2013-03-24 18:35:07'),(5132,'MAIN_HELPCENTER_DISABLELINK',0,'1','chaine',0,'','2013-03-24 18:35:07'),(5133,'MAIN_HOME',1,'<span style=\"font-size:11px;\">__(NoteSomeFeaturesAreDisabled)__<br />\r\n<br />\r\n__(SomeTranslationAreUncomplete)__</span>','chaine',0,'','2013-03-24 18:35:07'),(5134,'MAIN_HELP_DISABLELINK',0,'0','chaine',0,'','2013-03-24 18:35:07'),(5135,'MAIN_BUGTRACK_ENABLELINK',1,'0','chaine',0,'','2013-03-24 18:35:07'),(5137,'MAIN_AGENDA_ACTIONAUTO_BILL_UNVALIDATE',1,'1','chaine',0,NULL,'2013-09-08 23:06:08'),(5139,'SOCIETE_ADD_REF_IN_LIST',1,'','yesno',0,'Display customer ref into select list','2013-09-08 23:06:08'),(5150,'PROJECT_TASK_ADDON_PDF',1,'','chaine',0,'Name of PDF/ODT tasks manager class','2013-09-08 23:06:14'),(5151,'PROJECT_TASK_ADDON',1,'mod_task_simple','chaine',0,'Name of Numbering Rule task manager class','2013-09-08 23:06:14'),(5152,'PROJECT_TASK_ADDON_PDF_ODT_PATH',1,'DOL_DATA_ROOT/doctemplates/tasks','chaine',0,'','2013-09-08 23:06:14'),(5164,'MAIN_AGENDA_ACTIONAUTO_COMPANY_SENTBYMAIL',1,'1','chaine',0,NULL,'2013-11-06 23:35:12'),(5190,'MAIN_MODULE_GOOGLE',1,'1',NULL,0,NULL,'2013-11-07 00:01:39'),(5191,'MAIN_MODULE_GOOGLE_TABS_0',1,'agenda:+gcal:MenuAgendaGoogle:google@google:$conf->google->enabled && $conf->global->GOOGLE_ENABLE_AGENDA:/google/index.php','chaine',0,NULL,'2013-11-07 00:01:39'),(5192,'MAIN_MODULE_GOOGLE_TABS_1',1,'user:+gsetup:GoogleUserConf:google@google:$conf->google->enabled && $conf->global->GOOGLE_DUPLICATE_INTO_GCAL:/google/admin/google_calsync_user.php?id=__ID__','chaine',0,NULL,'2013-11-07 00:01:39'),(5193,'MAIN_MODULE_GOOGLE_TRIGGERS',1,'1','chaine',0,NULL,'2013-11-07 00:01:39'),(5194,'MAIN_MODULE_GOOGLE_HOOKS',1,'[\"toprightmenu\"]','chaine',0,NULL,'2013-11-07 00:01:39'),(5195,'GOOGLE_DUPLICATE_INTO_THIRDPARTIES',1,'1','chaine',0,'','2013-11-07 00:02:34'),(5196,'GOOGLE_DUPLICATE_INTO_CONTACTS',1,'0','chaine',0,'','2013-11-07 00:02:34'),(5197,'GOOGLE_DUPLICATE_INTO_MEMBERS',1,'0','chaine',0,'','2013-11-07 00:02:34'),(5198,'GOOGLE_CONTACT_LOGIN',1,'eldy10@mail.com','chaine',0,'','2013-11-07 00:02:34'),(5199,'GOOGLE_CONTACT_PASSWORD',1,'bidon','chaine',0,'','2013-11-07 00:02:34'),(5200,'GOOGLE_TAG_PREFIX',1,'Dolibarr (Thirdparties)','chaine',0,'','2013-11-07 00:02:34'),(5201,'GOOGLE_TAG_PREFIX_CONTACTS',1,'Dolibarr (Contacts/Addresses)','chaine',0,'','2013-11-07 00:02:34'),(5202,'GOOGLE_TAG_PREFIX_MEMBERS',1,'Dolibarr (Members)','chaine',0,'','2013-11-07 00:02:34'),(5203,'MODULE_GOOGLE_DEBUG',1,'1','chaine',1,'','2013-11-07 00:16:31'),(5221,'MAIN_MODULE_AGENDA',1,'1',NULL,0,NULL,'2014-04-05 14:19:21'),(5222,'MAIN_MODULE_SOCIETE',1,'1',NULL,0,NULL,'2014-04-05 14:19:21'),(5223,'MAIN_MODULE_SERVICE',1,'1',NULL,0,NULL,'2014-04-05 14:19:22'),(5224,'MAIN_MODULE_COMMANDE',1,'1',NULL,0,NULL,'2014-04-05 14:19:22'),(5225,'MAIN_MODULE_FACTURE',1,'1',NULL,0,NULL,'2014-04-05 14:19:22'),(5226,'MAIN_MODULE_FOURNISSEUR',1,'1',NULL,0,NULL,'2014-04-05 14:19:22'),(5227,'MAIN_MODULE_USER',0,'1',NULL,0,NULL,'2014-04-05 14:19:22'),(5228,'MAIN_MODULE_DEPLACEMENT',1,'1',NULL,0,NULL,'2014-04-05 14:19:22'),(5229,'MAIN_MODULE_DON',1,'1',NULL,0,NULL,'2014-04-05 14:19:22'),(5230,'MAIN_MODULE_ECM',1,'1',NULL,0,NULL,'2014-04-05 14:19:22'),(5231,'MAIN_VERSION_LAST_UPGRADE',0,'3.5.2','chaine',0,'Dolibarr version for last upgrade','2014-04-05 14:19:24'),(5233,'MAIN_DISABLE_ALL_MAILS',1,'1','chaine',0,'','2014-04-05 14:20:25'),(5234,'MAIN_MAIL_SENDMODE',0,'mail','chaine',0,'','2014-04-05 14:20:25'),(5235,'MAIN_MAIL_SMTPS_ID',0,'eldy10@mail.com','chaine',0,'','2014-04-05 14:20:25'),(5236,'MAIN_MAIL_SMTPS_PW',0,'bidon','chaine',0,'','2014-04-05 14:20:25');
/*!40000 ALTER TABLE `llx_const` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_contrat`
--

DROP TABLE IF EXISTS `llx_contrat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_contrat` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` datetime DEFAULT NULL,
  `date_contrat` datetime DEFAULT NULL,
  `statut` smallint(6) DEFAULT '0',
  `mise_en_service` datetime DEFAULT NULL,
  `fin_validite` datetime DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `fk_soc` int(11) NOT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `fk_commercial_signature` int(11) NOT NULL,
  `fk_commercial_suivi` int(11) NOT NULL,
  `fk_user_author` int(11) NOT NULL DEFAULT '0',
  `fk_user_mise_en_service` int(11) DEFAULT NULL,
  `fk_user_cloture` int(11) DEFAULT NULL,
  `note_private` text,
  `note_public` text,
  `import_key` varchar(14) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_contrat_ref` (`ref`,`entity`),
  KEY `idx_contrat_fk_soc` (`fk_soc`),
  KEY `idx_contrat_fk_user_author` (`fk_user_author`),
  CONSTRAINT `fk_contrat_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_contrat_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_contrat`
--

LOCK TABLES `llx_contrat` WRITE;
/*!40000 ALTER TABLE `llx_contrat` DISABLE KEYS */;
INSERT INTO `llx_contrat` VALUES (1,'CONTRACT1',1,'2010-07-08 23:53:55','2010-07-09 01:53:25','2010-07-09 00:00:00',1,NULL,NULL,NULL,3,NULL,2,2,1,NULL,NULL,NULL,NULL,NULL,NULL),(2,'CONTRAT1',1,'2010-07-10 16:18:16','2010-07-10 18:13:37','2010-07-10 00:00:00',1,NULL,NULL,NULL,2,NULL,2,2,1,NULL,NULL,NULL,NULL,NULL,NULL),(3,'CT1303-0001',1,'2013-03-06 09:05:07','2013-03-06 10:04:57','2013-03-06 00:00:00',1,NULL,NULL,NULL,19,NULL,1,1,1,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_contrat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_contrat_extrafields`
--

DROP TABLE IF EXISTS `llx_contrat_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_contrat_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_contrat_extrafields`
--

LOCK TABLES `llx_contrat_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_contrat_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_contrat_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_contratdet`
--

DROP TABLE IF EXISTS `llx_contratdet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_contratdet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_contrat` int(11) NOT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `statut` smallint(6) DEFAULT '0',
  `label` text,
  `description` text,
  `fk_remise_except` int(11) DEFAULT NULL,
  `date_commande` datetime DEFAULT NULL,
  `date_ouverture_prevue` datetime DEFAULT NULL,
  `date_ouverture` datetime DEFAULT NULL,
  `date_fin_validite` datetime DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `tva_tx` double(6,3) DEFAULT '0.000',
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax1_type` varchar(10) NOT NULL DEFAULT '0',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `localtax2_type` varchar(10) NOT NULL DEFAULT '0',
  `qty` double NOT NULL,
  `remise_percent` double DEFAULT '0',
  `subprice` double(24,8) DEFAULT '0.00000000',
  `price_ht` double DEFAULT NULL,
  `remise` double DEFAULT '0',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `product_type` int(11) DEFAULT '1',
  `info_bits` int(11) DEFAULT '0',
  `fk_product_fournisseur_price` int(11) DEFAULT NULL,
  `buy_price_ht` double(24,8) DEFAULT '0.00000000',
  `fk_user_author` int(11) NOT NULL DEFAULT '0',
  `fk_user_ouverture` int(11) DEFAULT NULL,
  `fk_user_cloture` int(11) DEFAULT NULL,
  `commentaire` text,
  PRIMARY KEY (`rowid`),
  KEY `idx_contratdet_fk_contrat` (`fk_contrat`),
  KEY `idx_contratdet_fk_product` (`fk_product`),
  KEY `idx_contratdet_date_ouverture_prevue` (`date_ouverture_prevue`),
  KEY `idx_contratdet_date_ouverture` (`date_ouverture`),
  KEY `idx_contratdet_date_fin_validite` (`date_fin_validite`),
  CONSTRAINT `fk_contratdet_fk_contrat` FOREIGN KEY (`fk_contrat`) REFERENCES `llx_contrat` (`rowid`),
  CONSTRAINT `fk_contratdet_fk_product` FOREIGN KEY (`fk_product`) REFERENCES `llx_product` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_contratdet`
--

LOCK TABLES `llx_contratdet` WRITE;
/*!40000 ALTER TABLE `llx_contratdet` DISABLE KEYS */;
INSERT INTO `llx_contratdet` VALUES (1,'2013-03-06 09:00:00',1,3,4,'','',NULL,NULL,'2010-07-09 00:00:00','2010-07-09 12:00:00','2013-03-15 00:00:00',NULL,0.000,0.000,'',0.000,'',1,0,0.00000000,0,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,1,0,0,0.00000000,0,1,1,''),(2,'2010-07-10 16:14:14',2,NULL,0,'','Abonnement annuel assurance',NULL,NULL,'2010-07-10 00:00:00',NULL,'2011-07-10 00:00:00',NULL,1.000,0.000,'',0.000,'',1,0,10.00000000,10,0,10.00000000,0.10000000,0.00000000,0.00000000,10.10000000,1,0,NULL,0.00000000,0,NULL,NULL,NULL),(3,'2013-03-05 10:20:58',2,3,5,'','gdfg',NULL,NULL,'2010-07-10 00:00:00','2010-07-10 12:00:00','2011-07-09 00:00:00','2013-03-06 12:00:00',4.000,0.000,'',0.000,'',1,0,0.00000000,0,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,1,0,0,0.00000000,0,1,1,''),(4,'2012-12-08 13:11:17',2,3,0,'','',NULL,NULL,'2010-07-10 00:00:00',NULL,NULL,NULL,0.000,0.000,'',0.000,'',1,10,40.00000000,40,NULL,36.00000000,0.00000000,0.00000000,0.00000000,36.00000000,1,0,NULL,0.00000000,0,NULL,1,''),(5,'2013-03-06 09:05:40',3,NULL,4,'','gfdg',NULL,NULL,NULL,'2013-03-06 12:00:00','2013-03-07 12:00:00',NULL,0.000,0.000,'',0.000,'',1,0,10.00000000,10,NULL,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,1,0,0,0.00000000,0,1,1,'');
/*!40000 ALTER TABLE `llx_contratdet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_contratdet_log`
--

DROP TABLE IF EXISTS `llx_contratdet_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_contratdet_log` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_contratdet` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `statut` smallint(6) NOT NULL,
  `fk_user_author` int(11) NOT NULL,
  `commentaire` text,
  PRIMARY KEY (`rowid`),
  KEY `idx_contratdet_log_fk_contratdet` (`fk_contratdet`),
  KEY `idx_contratdet_log_date` (`date`),
  CONSTRAINT `fk_contratdet_log_fk_contratdet` FOREIGN KEY (`fk_contratdet`) REFERENCES `llx_contratdet` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_contratdet_log`
--

LOCK TABLES `llx_contratdet_log` WRITE;
/*!40000 ALTER TABLE `llx_contratdet_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_contratdet_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_cotisation`
--

DROP TABLE IF EXISTS `llx_cotisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_cotisation` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` datetime DEFAULT NULL,
  `fk_adherent` int(11) DEFAULT NULL,
  `dateadh` datetime DEFAULT NULL,
  `datef` date DEFAULT NULL,
  `cotisation` double DEFAULT NULL,
  `fk_bank` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_cotisation` (`fk_adherent`,`dateadh`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_cotisation`
--

LOCK TABLES `llx_cotisation` WRITE;
/*!40000 ALTER TABLE `llx_cotisation` DISABLE KEYS */;
INSERT INTO `llx_cotisation` VALUES (1,'2010-07-10 13:05:30','2010-07-10 15:05:30',2,'2010-07-10 00:00:00','2011-07-10',20,NULL,'Adhésion/cotisation 2010'),(2,'2010-07-11 14:20:00','2010-07-11 16:20:00',2,'2011-07-11 00:00:00','2012-07-10',10,NULL,'Adhésion/cotisation 2011'),(3,'2010-07-18 10:20:33','2010-07-18 12:20:33',2,'2012-07-11 00:00:00','2013-07-17',10,NULL,'Adhésion/cotisation 2012'),(4,'2013-03-06 15:43:37','2013-03-06 16:43:37',2,'2013-07-18 00:00:00','2014-07-17',10,NULL,'Adhésion/cotisation 2013'),(5,'2013-03-06 15:44:12','2013-03-06 16:44:12',2,'2014-07-18 00:00:00','2015-07-17',11,NULL,'Adhésion/cotisation 2014'),(6,'2013-03-06 15:47:48','2013-03-06 16:47:48',2,'2015-07-18 00:00:00','2016-07-17',10,NULL,'Adhésion/cotisation 2015'),(7,'2013-03-06 15:48:16','2013-03-06 16:48:16',2,'2016-07-18 00:00:00','2017-07-17',20,22,'Adhésion/cotisation 2016'),(8,'2013-03-20 13:17:57','2013-03-20 14:17:57',1,'2010-07-10 00:00:00','2011-07-09',10,NULL,'Adhésion/cotisation 2010'),(10,'2013-03-20 13:30:11','2013-03-20 14:30:11',1,'2011-07-10 00:00:00','2012-07-09',10,23,'Adhésion/cotisation 2011');
/*!40000 ALTER TABLE `llx_cotisation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_cronjob`
--

DROP TABLE IF EXISTS `llx_cronjob`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_cronjob` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` datetime DEFAULT NULL,
  `jobtype` varchar(10) NOT NULL,
  `label` text NOT NULL,
  `command` varchar(255) DEFAULT NULL,
  `classesname` varchar(255) DEFAULT NULL,
  `objectname` varchar(255) DEFAULT NULL,
  `methodename` varchar(255) DEFAULT NULL,
  `params` text NOT NULL,
  `md5params` varchar(32) DEFAULT NULL,
  `module_name` varchar(255) DEFAULT NULL,
  `priority` int(11) DEFAULT '0',
  `datelastrun` datetime DEFAULT NULL,
  `datenextrun` datetime DEFAULT NULL,
  `datestart` datetime DEFAULT NULL,
  `dateend` datetime DEFAULT NULL,
  `datelastresult` datetime DEFAULT NULL,
  `lastresult` text,
  `lastoutput` text,
  `unitfrequency` int(11) NOT NULL DEFAULT '0',
  `frequency` int(11) NOT NULL DEFAULT '0',
  `nbrun` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_mod` int(11) DEFAULT NULL,
  `note` text,
  `libname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_cronjob`
--

LOCK TABLES `llx_cronjob` WRITE;
/*!40000 ALTER TABLE `llx_cronjob` DISABLE KEYS */;
INSERT INTO `llx_cronjob` VALUES (1,'2013-03-23 18:18:39','2013-03-23 19:18:39','command','aaa','aaaa','','','','','','',0,NULL,NULL,'2013-03-23 19:18:00',NULL,NULL,NULL,NULL,3600,3600,0,0,1,1,'',NULL);
/*!40000 ALTER TABLE `llx_cronjob` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_deplacement`
--

DROP TABLE IF EXISTS `llx_deplacement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_deplacement` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime NOT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dated` datetime DEFAULT NULL,
  `fk_user` int(11) NOT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  `type` varchar(12) NOT NULL,
  `fk_statut` int(11) NOT NULL DEFAULT '1',
  `km` double DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT '0',
  `note_private` text,
  `note_public` text,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_deplacement`
--

LOCK TABLES `llx_deplacement` WRITE;
/*!40000 ALTER TABLE `llx_deplacement` DISABLE KEYS */;
INSERT INTO `llx_deplacement` VALUES (1,NULL,1,'2010-07-09 01:58:04','2010-07-08 23:58:18','2010-07-09 12:00:00',2,1,NULL,'TF_LUNCH',1,10,2,1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_deplacement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_document_generator`
--

DROP TABLE IF EXISTS `llx_document_generator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_document_generator` (
  `rowid` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `classfile` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_document_generator`
--

LOCK TABLES `llx_document_generator` WRITE;
/*!40000 ALTER TABLE `llx_document_generator` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_document_generator` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_document_model`
--

DROP TABLE IF EXISTS `llx_document_model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_document_model` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `type` varchar(20) NOT NULL,
  `libelle` varchar(255) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_document_model` (`nom`,`type`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=242 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_document_model`
--

LOCK TABLES `llx_document_model` WRITE;
/*!40000 ALTER TABLE `llx_document_model` DISABLE KEYS */;
INSERT INTO `llx_document_model` VALUES (9,'merou',1,'shipping',NULL,NULL),(15,'fsfe.fr.php',1,'donation',NULL,NULL),(21,'baleine',1,'project',NULL,NULL),(174,'azur',1,'propal',NULL,NULL),(175,'rouget',1,'shipping',NULL,NULL),(176,'typhon',1,'delivery',NULL,NULL),(178,'soleil',1,'ficheinter',NULL,NULL),(181,'generic_invoice_odt',1,'invoice','ODT templates','FACTURE_ADDON_PDF_ODT_PATH'),(193,'canelle2',1,'invoice_supplier','canelle2',NULL),(195,'canelle',1,'invoice_supplier','canelle',NULL),(198,'azur',2,'propal',NULL,NULL),(199,'html_cerfafr',2,'donation',NULL,NULL),(200,'crabe',2,'invoice',NULL,NULL),(201,'generic_odt',1,'company','ODT templates','COMPANY_ADDON_PDF_ODT_PATH'),(220,'einstein',1,'order',NULL,NULL),(221,'crabe',1,'invoice',NULL,NULL),(222,'muscadet',1,'order_supplier',NULL,NULL),(223,'html_cerfafr',1,'donation',NULL,NULL);
/*!40000 ALTER TABLE `llx_document_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_dolicloud_emailstemplates`
--

DROP TABLE IF EXISTS `llx_dolicloud_emailstemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_dolicloud_emailstemplates` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `emailtype` varchar(128) NOT NULL,
  `lang` varchar(12) NOT NULL,
  `topic` varchar(256) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_dolicloud_emailstemplates` (`emailtype`,`lang`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_dolicloud_emailstemplates`
--

LOCK TABLES `llx_dolicloud_emailstemplates` WRITE;
/*!40000 ALTER TABLE `llx_dolicloud_emailstemplates` DISABLE KEYS */;
INSERT INTO `llx_dolicloud_emailstemplates` VALUES (1,'PasswordAssistance','en_US','DoliCloud (online Dolibarr ERP & CRM) - Password assistance: how to reset your password','<body>\n                <p>Dear ${person.firstName},</p>\n                <p>\n                    To continue the password reset process for the account ${person.email}\n                    click on the link below.<br />\n                </p>\n                <p><a href=\"${resetPasswordLink}\">${resetPasswordLink}</a></p>\n                <p>Note that this process is to reset the password for your dashboard, not for your application login. You may find more information on all different user/password reset process onto <a href=\"http://dolicloud.com/en/faq/139-i-forgot-my-login-or-password\">the following page</a>.\n                </p>\n                <p>If clicking doesn\'t seem to work, you can copy and paste the link into your browser\'s\n                   address window, or retype it there. Once you have returned to our site, we will give instructions for resetting your password.</p>\n                <p>If you did not request to have your password reset you can safely ignore this email.\n                   It is likely another user entered your email address by mistake while trying to reset a password. Rest assured your customer account is safe.</p>\n                <p>We will never e-mail you and ask you to disclose or verify your password, credit card, or banking account number. \n                   If you receive a suspicious e-mail with a link to update your account information,\n                   do not click on the link - instead, report the e-mail to us for investigation.</p>\n                <p>\n            Sincerly, <br />\n            The DoliCloud Team <br />\n            ----------------------------------------- <br />\n            EMail: support@dolicloud.com <br />\n            Web: http://www.dolicloud.com\n                </p>\n              </body>'),(2,'InstanceDeployed','en_US','Welcome to DoliCloud (online Dolibarr ERP & CRM) - Your instance is ready','               <body>\n                <p>Hello ${person.firstName},</p>\n                <p>\n                   We are delighted to welcome you as a user of DoliCloud, the Ondemand service of Dolibarr ERP & CRM.\n                </p>\n                <p>\n                   Your ${appPackage.name} is installed, setup and ready for you.\n                   Here are the details you need to get started:\n                </p>\n                <br /><strong>Your Dolibarr ERP & CRM :</strong>\n                <ul>\n                    <li>URL: <a href=\"${appInstance.url}?username=${appPackage.defaultUser}\">${appInstance.url}</a></li>\n                    <li>Login: ${appPackage.defaultUser}</li>\n                    <li>Password: ${appInstance.defaultPassword}</li>\n                </ul>\n                <br /><strong>Your Dolicloud dashboard :</strong>\n                <ul>\n                    <li>URL: <a href=\"https://www.on.dolicloud.com/\">https://www.on.dolicloud.com/</a></li>\n                    <li>Login: ${person.email}</li>\n                    <li>Password: ${appInstance.defaultPassword}</li>\n                </ul>                \n            \n            <br />\n            Sincerly, <br />\n            The DoliCloud Team <br />\n            ----------------------------------------- <br />\n            EMail: support@dolicloud.com <br />\n            Web: http://www.dolicloud.com\n            </body>\n            '),(3,'InvoiceFailure','en_US','DoliCloud (online Dolibarr ERP & CRM) - Invoice Payment Failure','            <body>\n              <p>Dear DoliCloud Customer,</p>\n              <p>\n                An attempt to take payment for invoice(s) owed has failed. Please update your payment method, or contact your bank or payment method provider.<br />\n                Should failure to take this payment continue, access to our service will be discontinued, and any data you have with us maybe lost.<br />\n<br />\nPlease login to your <a href=\"https://www.on.dolicloud.com/\">Dolicloud dashboard</a> to update and fix your credit card or paypal information as soon as possible to prevent any interuptions in service.<br />\nRemind: Your DoliCloud dashboard login is ${person.email}<br />\n              </p>\n              <p>\n                The error we received from your bank was: <br />\n                ${invoice.notes.collect{ it }.join(\' \')}\n              </p><br />\n\n            Sincerly, <br />\n            The DoliCloud Team <br />\n            ----------------------------------------- <br />\n            EMail: support@dolicloud.com <br />\n            Web: http://www.dolicloud.com\n            </body>\n          '),(4,'CustomerInstanceClosed','en_US','DoliCloud (online Dolibarr ERP & CRM) - Account Closure','            <body>\n              <p>Dear Customer,</p>\n              <p>\n                 We wish to inform you your account has now been closed. We are sorry to see you got, but thank you for your custom.\n                 We hope you will a customer of ours in the future.<br />\nIf you think this is an error, please contact us at support@dolicloud.com\n              </p><br />\n            Sincerly, <br />\n            The DoliCloud Team <br />\n            ----------------------------------------- <br />\n            EMail: support@dolicloud.com <br />\n            Web: http://www.dolicloud.com\n            </body>\n          '),(5,'CustomerInstanceClosureRequested','en_US','DoliCloud (online Dolibarr ERP & CRM) - Customer Account Closure Requested Confirmation','            <body>               <p>Dear Customer,</p>               <p>                 We are sorry to see you go, and appreciate the custom you have given us.               </p>               <p>                The closure of your account will be executed at the end of your current trialing or                 billing period <span class=\"highlight\">(${customerAccount.nextBillingDate.format(\'dd MMM yyyy\')})</span>.                Once the closure is complete your instance and its                related data will be <span class=\"emphasis\">destroyed and unretrievable</span>.<br />                 If you change your mind before that date you can halt the closure process,                and continue being our customer. For this, go to your <a href=\"https://www.on.dolicloud.com/\">Dolicloud dashboard</a>.<br />                Remind: Your DoliCloud dashboard login is ${person.email}<br />                </p><br />             Sincerly, <br />             The DoliCloud Team <br />             ----------------------------------------- <br />             EMail: support@dolicloud.com <br />             Web: http://www.dolicloud.com             </body>           '),(6,'CreditCardExpiring','en_US','DoliCloud (online Dolibarr ERP & CRM) - Urgent: Your credit card is expiring','          <body>\n            <p>Dear Customer,</p>\n            <p>\n              We wish to inform you that your payment method will soon expire.<br />\n              \n              Please login to your <a href=\"https://www.on.dolicloud.com/\">Dolicloud dashboard</a> to update your credit card information as soon as possible to prevent any interuptions in service.<br />\nRemind: Your DoliCloud dashboard login is ${person.email}<br />\n            </p>\n            <p>If you have any questions relating to the above please do not hesitate get in touch.</p>\n<br />\n\n            Sincerly, <br />\n            The DoliCloud Team <br />\n            ----------------------------------------- <br />\n            EMail: support@dolicloud.com <br />\n            Web: http://www.dolicloud.com\n          </body>\n        '),(7,'GentleTrialExpiringReminder','en_US','DoliCloud (online Dolibarr ERP & CRM) - Your Trial will soon expire','         <body>\n          <p>Hello ${person.firstName},</p>\n          <p>\n            Just a quick reminder that trial of your online Dolibarr ERP & CRM will expire soon. If you wish to continue using this service, please login to your DoliCloud console to add a payment method (credit card or paypal accepted).\n          </p>\n          <p>\nFor this, click to go on your DoliCloud dashboard: <a href=\"https://www.on.dolicloud.com/\">https://www.on.dolicloud.com/</a><br />\nRemind: Your DoliCloud dashboard login is ${person.email}<br />\n</p>\n          <br />\n            Sincerly, <br />\n            The DoliCloud Team <br />\n            ----------------------------------------- <br />\n            EMail: support@dolicloud.com <br />\n            Web: http://www.dolicloud.com\n        </body>\n       '),(8,'ChannelPartnerCreated','en_US','Channel Partner Created','         <body>\n          <p>Hello ${person.firstName},</p>\n          <p>\n             We are delighted to welcome you as a Channel Partner of ${appProvider.name}.\n          </p>\n          <p>\n             Your account has been setup for you.\n             Here are the details you need to get started:\n          </p>\n          <ul>\n              <li>Login link: ${serverURL}</li>\n              <li>Username: ${person.email}</li>\n              <li>Temporary Password: ${person.tmpPassword}</li>\n          </ul>                \n          <p>\n              Sincerly, <br />\n              The ${appProvider.name} Team\n          </p>\n        </body>\n        '),(9,'CustomerAccountSuspended','en_US','DoliCloud (online Dolibarr ERP & CRM) - Account Suspension','<body>\n <p>Dear Customer,</p> \n <p>We wish to inform you your account has been suspended. This is likely due to a payment problem. If you wish to engage with us in addressing this send an email to support@dolicloud.com </p><br />\n Sincerly, <br /> \n The DoliCloud Team <br />\n ========================================== <br />\n            EMail: support@dolicloud.com <br />\n            Web: http://www.dolicloud.com\n        </body>\n       '),(10,'CustomerInstanceUpdated','en_US','DoliCloud (online Dolibarr ERP & CRM) - Instance upgrade','<body>\n <p>Dear Customer,</p> \n <p>We wish to inform you your instance has been upgraded to last stable version. If you experience problem after this upgrade, you can contact us at support@dolicloud.com </p><br />\n Sincerly, <br /> \n The DoliCloud Team <br />\n ========================================== <br />\n            EMail: support@dolicloud.com <br />\n            Web: http://www.dolicloud.com\n        </body>\n       ');
/*!40000 ALTER TABLE `llx_dolicloud_emailstemplates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_domain`
--

DROP TABLE IF EXISTS `llx_domain`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_domain` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_domain`
--

LOCK TABLES `llx_domain` WRITE;
/*!40000 ALTER TABLE `llx_domain` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_domain` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_don`
--

DROP TABLE IF EXISTS `llx_don`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_don` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `datec` datetime DEFAULT NULL,
  `datedon` datetime DEFAULT NULL,
  `amount` double DEFAULT '0',
  `fk_paiement` int(11) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `societe` varchar(50) DEFAULT NULL,
  `address` text,
  `zip` varchar(10) DEFAULT NULL,
  `town` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(24) DEFAULT NULL,
  `phone_mobile` varchar(24) DEFAULT NULL,
  `public` smallint(6) NOT NULL DEFAULT '1',
  `fk_don_projet` int(11) DEFAULT NULL,
  `fk_user_author` int(11) NOT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_don`
--

LOCK TABLES `llx_don` WRITE;
/*!40000 ALTER TABLE `llx_don` DISABLE KEYS */;
INSERT INTO `llx_don` VALUES (1,NULL,1,'2010-07-08 23:57:17',1,'2010-07-09 01:55:33','2010-07-09 12:00:00',10,1,'Donator','','Guest company','','','','France','',NULL,NULL,1,1,1,1,'',NULL,'html_cerfafr',NULL);
/*!40000 ALTER TABLE `llx_don` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_ecm_directories`
--

DROP TABLE IF EXISTS `llx_ecm_directories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_ecm_directories` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(64) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_parent` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `cachenbofdoc` int(11) NOT NULL DEFAULT '0',
  `fullpath` varchar(255) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  `date_c` datetime DEFAULT NULL,
  `date_m` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_user_c` int(11) DEFAULT NULL,
  `fk_user_m` int(11) DEFAULT NULL,
  `acl` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_ecm_directories` (`label`,`fk_parent`,`entity`),
  KEY `idx_ecm_directories_fk_user_c` (`fk_user_c`),
  KEY `idx_ecm_directories_fk_user_m` (`fk_user_m`),
  CONSTRAINT `fk_ecm_directories_fk_user_c` FOREIGN KEY (`fk_user_c`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_ecm_directories_fk_user_m` FOREIGN KEY (`fk_user_m`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_ecm_directories`
--

LOCK TABLES `llx_ecm_directories` WRITE;
/*!40000 ALTER TABLE `llx_ecm_directories` DISABLE KEYS */;
INSERT INTO `llx_ecm_directories` VALUES (1,'Répertoire_1',1,0,'',1,NULL,NULL,'2010-07-11 16:27:26','2010-07-11 14:27:44',1,NULL,NULL),(2,'dddd',1,0,'',3,NULL,NULL,'2013-02-20 19:11:05','2013-02-20 18:11:05',1,NULL,NULL),(3,'bbb',1,2,'',0,NULL,NULL,'2013-02-20 19:11:05','2013-02-20 18:11:06',1,NULL,NULL),(4,'aaa',1,2,'',1,NULL,NULL,'2013-02-20 19:11:05','2013-02-20 18:11:06',1,NULL,NULL),(5,'gggggg',1,0,'',2,NULL,NULL,'2013-02-20 19:11:05','2013-02-20 18:11:05',1,NULL,NULL),(6,'mmm',1,0,'',0,NULL,NULL,'2013-02-20 19:11:05','2013-02-20 18:11:05',1,NULL,NULL),(7,'aaa',1,0,'',1,NULL,NULL,'2013-02-20 19:11:05','2013-02-20 18:11:05',1,NULL,NULL);
/*!40000 ALTER TABLE `llx_ecm_directories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_ecm_documents`
--

DROP TABLE IF EXISTS `llx_ecm_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_ecm_documents` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(16) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `filename` varchar(255) NOT NULL,
  `filesize` int(11) NOT NULL,
  `filemime` varchar(32) NOT NULL,
  `fullpath_dol` varchar(255) NOT NULL,
  `fullpath_orig` varchar(255) NOT NULL,
  `description` text,
  `manualkeyword` text,
  `fk_create` int(11) NOT NULL,
  `fk_update` int(11) DEFAULT NULL,
  `date_c` datetime NOT NULL,
  `date_u` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_directory` int(11) DEFAULT NULL,
  `fk_status` smallint(6) DEFAULT '0',
  `private` smallint(6) DEFAULT '0',
  `crc` varchar(32) NOT NULL DEFAULT '',
  `cryptkey` varchar(50) NOT NULL DEFAULT '',
  `cipher` varchar(50) NOT NULL DEFAULT 'twofish',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_ecm_documents` (`fullpath_dol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_ecm_documents`
--

LOCK TABLES `llx_ecm_documents` WRITE;
/*!40000 ALTER TABLE `llx_ecm_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_ecm_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_element_contact`
--

DROP TABLE IF EXISTS `llx_element_contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_element_contact` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datecreate` datetime DEFAULT NULL,
  `statut` smallint(6) DEFAULT '5',
  `element_id` int(11) NOT NULL,
  `fk_c_type_contact` int(11) NOT NULL,
  `fk_socpeople` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_element_contact_idx1` (`element_id`,`fk_c_type_contact`,`fk_socpeople`),
  KEY `fk_element_contact_fk_c_type_contact` (`fk_c_type_contact`),
  KEY `idx_element_contact_fk_socpeople` (`fk_socpeople`),
  CONSTRAINT `fk_element_contact_fk_c_type_contact` FOREIGN KEY (`fk_c_type_contact`) REFERENCES `llx_c_type_contact` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_element_contact`
--

LOCK TABLES `llx_element_contact` WRITE;
/*!40000 ALTER TABLE `llx_element_contact` DISABLE KEYS */;
INSERT INTO `llx_element_contact` VALUES (1,'2010-07-09 00:49:43',4,1,160,1),(2,'2010-07-09 00:49:56',4,2,160,1),(3,'2010-07-09 00:50:19',4,3,160,1),(4,'2010-07-09 00:50:42',4,4,160,1),(5,'2010-07-09 01:52:36',4,1,120,1),(6,'2010-07-09 01:53:25',4,1,10,2),(7,'2010-07-09 01:53:25',4,1,11,2),(8,'2010-07-10 18:13:37',4,2,10,2),(9,'2010-07-10 18:13:37',4,2,11,2),(10,'2010-07-11 15:15:55',4,1,180,1),(11,'2010-07-11 16:22:36',4,5,160,1),(12,'2010-07-11 16:23:53',4,2,180,1),(13,'2013-01-23 15:04:27',4,19,200,5),(14,'2013-01-23 16:06:37',4,19,210,2),(15,'2013-01-23 16:12:43',4,19,220,2),(16,'2013-03-06 10:04:57',4,3,10,1),(17,'2013-03-06 10:04:57',4,3,11,1);
/*!40000 ALTER TABLE `llx_element_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_element_element`
--

DROP TABLE IF EXISTS `llx_element_element`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_element_element` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_source` int(11) NOT NULL,
  `sourcetype` varchar(32) NOT NULL,
  `fk_target` int(11) NOT NULL,
  `targettype` varchar(32) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_element_element_idx1` (`fk_source`,`sourcetype`,`fk_target`,`targettype`),
  KEY `idx_element_element_fk_target` (`fk_target`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_element_element`
--

LOCK TABLES `llx_element_element` WRITE;
/*!40000 ALTER TABLE `llx_element_element` DISABLE KEYS */;
INSERT INTO `llx_element_element` VALUES (7,1,'shipping',154,'facture'),(8,1,'shipping',155,'facture'),(9,1,'shipping',156,'facture'),(1,2,'contrat',2,'facture'),(2,2,'propal',1,'commande'),(11,2,'shipping',157,'facture'),(3,5,'commande',1,'shipping'),(6,5,'commande',153,'facture'),(10,7,'commande',2,'shipping'),(4,9,'propal',6,'commande'),(5,11,'propal',7,'commande'),(12,12,'propal',8,'commande'),(13,12,'propal',162,'facture');
/*!40000 ALTER TABLE `llx_element_element` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_element_lock`
--

DROP TABLE IF EXISTS `llx_element_lock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_element_lock` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_element` int(11) NOT NULL,
  `elementtype` varchar(32) NOT NULL,
  `datel` datetime DEFAULT NULL,
  `datem` datetime DEFAULT NULL,
  `sessionid` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_element_lock`
--

LOCK TABLES `llx_element_lock` WRITE;
/*!40000 ALTER TABLE `llx_element_lock` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_element_lock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_element_rang`
--

DROP TABLE IF EXISTS `llx_element_rang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_element_rang` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_parent` int(11) NOT NULL,
  `parenttype` varchar(16) NOT NULL,
  `fk_child` int(11) NOT NULL,
  `childtype` varchar(16) NOT NULL,
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_element_rang_idx1` (`fk_parent`,`parenttype`,`fk_child`,`childtype`),
  KEY `idx_element_rang_fk_parent` (`fk_parent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_element_rang`
--

LOCK TABLES `llx_element_rang` WRITE;
/*!40000 ALTER TABLE `llx_element_rang` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_element_rang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_element_tag`
--

DROP TABLE IF EXISTS `llx_element_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_element_tag` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `lang` varchar(5) NOT NULL,
  `tag` varchar(255) NOT NULL,
  `fk_element` int(11) NOT NULL,
  `element` varchar(64) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_element_tag` (`entity`,`lang`,`tag`,`fk_element`,`element`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_element_tag`
--

LOCK TABLES `llx_element_tag` WRITE;
/*!40000 ALTER TABLE `llx_element_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_element_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_entity`
--

DROP TABLE IF EXISTS `llx_entity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_entity` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `label` varchar(255) NOT NULL,
  `description` text,
  `datec` datetime DEFAULT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `options` text,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  KEY `idx_entity_fk_user_creat` (`fk_user_creat`),
  CONSTRAINT `fk_entity_fk_user_creat` FOREIGN KEY (`fk_user_creat`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_entity`
--

LOCK TABLES `llx_entity` WRITE;
/*!40000 ALTER TABLE `llx_entity` DISABLE KEYS */;
INSERT INTO `llx_entity` VALUES (1,'2012-12-08 14:07:29','Master entity','Master entity, can not be deleted','2012-12-08 15:07:29',1,NULL,1,1),(2,'2012-12-08 14:08:27','aaa','','2012-12-08 15:08:14',1,'{\"referent\":null,\"sharings\":{\"product\":null,\"societe\":null,\"category\":null}}',1,1),(3,'2012-12-08 14:08:26','bbb','','2012-12-08 15:08:20',1,'{\"referent\":null,\"sharings\":{\"product\":null,\"societe\":null,\"category\":null}}',1,1);
/*!40000 ALTER TABLE `llx_entity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_entrepot`
--

DROP TABLE IF EXISTS `llx_entrepot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_entrepot` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `label` varchar(255) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `description` text,
  `lieu` varchar(64) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `town` varchar(50) DEFAULT NULL,
  `fk_departement` int(11) DEFAULT NULL,
  `fk_pays` int(11) DEFAULT '0',
  `statut` tinyint(4) DEFAULT '1',
  `valo_pmp` float(12,4) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_entrepot_label` (`label`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_entrepot`
--

LOCK TABLES `llx_entrepot` WRITE;
/*!40000 ALTER TABLE `llx_entrepot` DISABLE KEYS */;
INSERT INTO `llx_entrepot` VALUES (1,'2010-07-09 00:31:22','2010-07-08 22:40:36','WAREHOUSEHOUSTON',1,'Warehouse located at Houston','Warehouse houston','','','Houston',NULL,11,1,NULL,1,NULL),(2,'2010-07-09 00:41:03','2010-07-08 22:41:03','WAREHOUSEPARIS',1,'<br />','Warehouse Paris','','75000','Paris',NULL,1,1,NULL,1,NULL),(3,'2010-07-11 16:18:59','2010-07-11 14:18:59','Stock personnel Dupont',1,'Cet entrep&ocirc;t repr&eacute;sente le stock personnel de Alain Dupont','','','','',NULL,0,1,NULL,1,NULL),(4,'2013-01-23 17:52:27','2013-01-23 16:52:27','Stock personnel aaa',1,'Cet entrep&ocirc;t repr&eacute;sente le stock personnel de aaa','','','','',NULL,81,1,NULL,1,NULL),(5,'2013-01-23 17:52:37','2013-01-23 16:52:37','Stock personnel bbb',1,'Cet entrep&ocirc;t repr&eacute;sente le stock personnel de bbb','','','','',NULL,81,1,NULL,1,NULL),(6,'2013-02-16 20:22:40','2013-02-16 19:22:40','Stock personnel aaab',1,'Cet entrep&ocirc;t repr&eacute;sente le stock personnel de aaab','','','','',NULL,1,1,NULL,1,NULL),(7,'2013-02-16 20:48:15','2013-02-16 19:48:15','Stock personnel zzz',1,'Cet entrep&ocirc;t repr&eacute;sente le stock personnel de zzz','','','','',NULL,1,1,NULL,1,NULL),(8,'2013-02-16 20:50:07','2013-02-16 19:50:07','Stock personnel zzzg',1,'Cet entrep&ocirc;t repr&eacute;sente le stock personnel de zzzg','','','','',NULL,1,1,NULL,1,NULL);
/*!40000 ALTER TABLE `llx_entrepot` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_event_element`
--

DROP TABLE IF EXISTS `llx_event_element`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_event_element` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_source` int(11) NOT NULL,
  `fk_target` int(11) NOT NULL,
  `targettype` varchar(32) NOT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_event_element`
--

LOCK TABLES `llx_event_element` WRITE;
/*!40000 ALTER TABLE `llx_event_element` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_event_element` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_events`
--

DROP TABLE IF EXISTS `llx_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_events` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` varchar(32) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `dateevent` datetime DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `description` varchar(250) NOT NULL,
  `ip` varchar(32) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `fk_object` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_events_dateevent` (`dateevent`)
) ENGINE=InnoDB AUTO_INCREMENT=573 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_events`
--

LOCK TABLES `llx_events` WRITE;
/*!40000 ALTER TABLE `llx_events` DISABLE KEYS */;
INSERT INTO `llx_events` VALUES (30,'2011-07-18 18:23:06','USER_LOGOUT',1,'2011-07-18 20:23:06',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(31,'2011-07-18 18:23:12','USER_LOGIN_FAILED',1,'2011-07-18 20:23:12',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(32,'2011-07-18 18:23:17','USER_LOGIN',1,'2011-07-18 20:23:17',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(33,'2011-07-18 20:10:51','USER_LOGIN_FAILED',1,'2011-07-18 22:10:51',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(34,'2011-07-18 20:10:55','USER_LOGIN',1,'2011-07-18 22:10:55',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(35,'2011-07-18 21:18:57','USER_LOGIN',1,'2011-07-18 23:18:57',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(36,'2011-07-20 10:34:10','USER_LOGIN',1,'2011-07-20 12:34:10',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(37,'2011-07-20 12:36:44','USER_LOGIN',1,'2011-07-20 14:36:44',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(38,'2011-07-20 13:20:51','USER_LOGIN_FAILED',1,'2011-07-20 15:20:51',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(39,'2011-07-20 13:20:54','USER_LOGIN',1,'2011-07-20 15:20:54',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(40,'2011-07-20 15:03:46','USER_LOGIN_FAILED',1,'2011-07-20 17:03:46',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(41,'2011-07-20 15:03:55','USER_LOGIN',1,'2011-07-20 17:03:55',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(42,'2011-07-20 18:05:05','USER_LOGIN_FAILED',1,'2011-07-20 20:05:05',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(43,'2011-07-20 18:05:08','USER_LOGIN',1,'2011-07-20 20:05:08',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(44,'2011-07-20 21:08:53','USER_LOGIN_FAILED',1,'2011-07-20 23:08:53',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(45,'2011-07-20 21:08:56','USER_LOGIN',1,'2011-07-20 23:08:56',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(46,'2011-07-21 01:26:12','USER_LOGIN',1,'2011-07-21 03:26:12',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(47,'2011-07-21 22:35:45','USER_LOGIN_FAILED',1,'2011-07-22 00:35:45',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(48,'2011-07-21 22:35:49','USER_LOGIN',1,'2011-07-22 00:35:49',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(49,'2011-07-26 23:09:47','USER_LOGIN_FAILED',1,'2011-07-27 01:09:47',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(50,'2011-07-26 23:09:50','USER_LOGIN',1,'2011-07-27 01:09:50',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(51,'2011-07-27 17:02:27','USER_LOGIN_FAILED',1,'2011-07-27 19:02:27',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(52,'2011-07-27 17:02:32','USER_LOGIN',1,'2011-07-27 19:02:32',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(53,'2011-07-27 23:33:37','USER_LOGIN_FAILED',1,'2011-07-28 01:33:37',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(54,'2011-07-27 23:33:41','USER_LOGIN',1,'2011-07-28 01:33:41',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(55,'2011-07-28 18:20:36','USER_LOGIN_FAILED',1,'2011-07-28 20:20:36',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(56,'2011-07-28 18:20:38','USER_LOGIN',1,'2011-07-28 20:20:38',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(57,'2011-07-28 20:13:30','USER_LOGIN_FAILED',1,'2011-07-28 22:13:30',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(58,'2011-07-28 20:13:34','USER_LOGIN',1,'2011-07-28 22:13:34',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(59,'2011-07-28 20:22:51','USER_LOGIN',1,'2011-07-28 22:22:51',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(60,'2011-07-28 23:05:06','USER_LOGIN',1,'2011-07-29 01:05:06',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(61,'2011-07-29 20:15:50','USER_LOGIN_FAILED',1,'2011-07-29 22:15:50',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(62,'2011-07-29 20:15:53','USER_LOGIN',1,'2011-07-29 22:15:53',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(68,'2011-07-29 20:51:01','USER_LOGOUT',1,'2011-07-29 22:51:01',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(69,'2011-07-29 20:51:05','USER_LOGIN',1,'2011-07-29 22:51:05',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(70,'2011-07-30 08:46:20','USER_LOGIN_FAILED',1,'2011-07-30 10:46:20',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(71,'2011-07-30 08:46:38','USER_LOGIN_FAILED',1,'2011-07-30 10:46:38',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(72,'2011-07-30 08:46:42','USER_LOGIN',1,'2011-07-30 10:46:42',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(73,'2011-07-30 10:05:12','USER_LOGIN_FAILED',1,'2011-07-30 12:05:12',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(74,'2011-07-30 10:05:15','USER_LOGIN',1,'2011-07-30 12:05:15',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(75,'2011-07-30 12:15:46','USER_LOGIN',1,'2011-07-30 14:15:46',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(76,'2011-07-31 22:19:30','USER_LOGIN',1,'2011-08-01 00:19:30',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(77,'2011-07-31 23:32:52','USER_LOGIN',1,'2011-08-01 01:32:52',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(78,'2011-08-01 01:24:50','USER_LOGIN_FAILED',1,'2011-08-01 03:24:50',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(79,'2011-08-01 01:24:54','USER_LOGIN',1,'2011-08-01 03:24:54',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(80,'2011-08-01 19:31:36','USER_LOGIN_FAILED',1,'2011-08-01 21:31:35',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(81,'2011-08-01 19:31:39','USER_LOGIN',1,'2011-08-01 21:31:39',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(82,'2011-08-01 20:01:36','USER_LOGIN',1,'2011-08-01 22:01:36',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(83,'2011-08-01 20:52:54','USER_LOGIN_FAILED',1,'2011-08-01 22:52:54',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(84,'2011-08-01 20:52:58','USER_LOGIN',1,'2011-08-01 22:52:58',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(85,'2011-08-01 21:17:28','USER_LOGIN_FAILED',1,'2011-08-01 23:17:28',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(86,'2011-08-01 21:17:31','USER_LOGIN',1,'2011-08-01 23:17:31',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(87,'2011-08-04 11:55:17','USER_LOGIN',1,'2011-08-04 13:55:17',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(88,'2011-08-04 20:19:03','USER_LOGIN_FAILED',1,'2011-08-04 22:19:03',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(89,'2011-08-04 20:19:07','USER_LOGIN',1,'2011-08-04 22:19:07',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(90,'2011-08-05 17:51:42','USER_LOGIN_FAILED',1,'2011-08-05 19:51:42',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(91,'2011-08-05 17:51:47','USER_LOGIN',1,'2011-08-05 19:51:47',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(92,'2011-08-05 17:56:03','USER_LOGIN',1,'2011-08-05 19:56:03',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(93,'2011-08-05 17:59:10','USER_LOGIN',1,'2011-08-05 19:59:10',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.100 Safari/534.30',NULL),(94,'2011-08-05 18:01:58','USER_LOGIN',1,'2011-08-05 20:01:58',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.100 Safari/534.30',NULL),(95,'2011-08-05 19:59:56','USER_LOGIN',1,'2011-08-05 21:59:56',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(96,'2011-08-06 18:33:22','USER_LOGIN',1,'2011-08-06 20:33:22',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(97,'2011-08-07 00:56:59','USER_LOGIN',1,'2011-08-07 02:56:59',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(98,'2011-08-07 22:49:14','USER_LOGIN',1,'2011-08-08 00:49:14',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(99,'2011-08-07 23:05:18','USER_LOGOUT',1,'2011-08-08 01:05:18',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(105,'2011-08-08 00:41:09','USER_LOGIN',1,'2011-08-08 02:41:09',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(106,'2011-08-08 11:58:55','USER_LOGIN',1,'2011-08-08 13:58:55',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(107,'2011-08-08 14:35:48','USER_LOGIN',1,'2011-08-08 16:35:48',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(108,'2011-08-08 14:36:31','USER_LOGOUT',1,'2011-08-08 16:36:31',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(109,'2011-08-08 14:38:28','USER_LOGIN',1,'2011-08-08 16:38:28',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(110,'2011-08-08 14:39:02','USER_LOGOUT',1,'2011-08-08 16:39:02',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(111,'2011-08-08 14:39:10','USER_LOGIN',1,'2011-08-08 16:39:10',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(112,'2011-08-08 14:39:28','USER_LOGOUT',1,'2011-08-08 16:39:28',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(113,'2011-08-08 14:39:37','USER_LOGIN',1,'2011-08-08 16:39:37',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(114,'2011-08-08 14:50:02','USER_LOGOUT',1,'2011-08-08 16:50:02',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(115,'2011-08-08 14:51:45','USER_LOGIN_FAILED',1,'2011-08-08 16:51:45',NULL,'Identifiants login ou mot de passe incorrects - login=','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(116,'2011-08-08 14:51:52','USER_LOGIN',1,'2011-08-08 16:51:52',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(117,'2011-08-08 15:09:54','USER_LOGOUT',1,'2011-08-08 17:09:54',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(118,'2011-08-08 15:10:19','USER_LOGIN_FAILED',1,'2011-08-08 17:10:19',NULL,'Identifiants login ou mot de passe incorrects - login=','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(119,'2011-08-08 15:10:28','USER_LOGIN',1,'2011-08-08 17:10:28',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(121,'2011-08-08 15:14:58','USER_LOGOUT',1,'2011-08-08 17:14:58',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(122,'2011-08-08 15:15:00','USER_LOGIN_FAILED',1,'2011-08-08 17:15:00',NULL,'Identifiants login ou mot de passe incorrects - login=','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(123,'2011-08-08 15:17:57','USER_LOGIN',1,'2011-08-08 17:17:57',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(124,'2011-08-08 15:35:56','USER_LOGOUT',1,'2011-08-08 17:35:56',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(125,'2011-08-08 15:36:05','USER_LOGIN',1,'2011-08-08 17:36:05',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(126,'2011-08-08 17:32:42','USER_LOGIN',1,'2011-08-08 19:32:42',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0',NULL),(127,'2012-12-08 13:49:37','USER_LOGOUT',1,'2012-12-08 14:49:37',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(128,'2012-12-08 13:49:42','USER_LOGIN',1,'2012-12-08 14:49:42',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(129,'2012-12-08 13:50:12','USER_LOGOUT',1,'2012-12-08 14:50:12',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(130,'2012-12-08 13:50:14','USER_LOGIN',1,'2012-12-08 14:50:14',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(131,'2012-12-08 13:50:17','USER_LOGOUT',1,'2012-12-08 14:50:17',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(132,'2012-12-08 13:52:47','USER_LOGIN',1,'2012-12-08 14:52:47',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(133,'2012-12-08 13:53:08','USER_MODIFY',1,'2012-12-08 14:53:08',1,'User admin modified','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(134,'2012-12-08 14:08:45','USER_LOGOUT',1,'2012-12-08 15:08:45',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(135,'2012-12-08 14:09:09','USER_LOGIN',1,'2012-12-08 15:09:09',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(136,'2012-12-08 14:11:43','USER_LOGOUT',1,'2012-12-08 15:11:43',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(137,'2012-12-08 14:11:45','USER_LOGIN',1,'2012-12-08 15:11:45',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(138,'2012-12-08 14:22:53','USER_LOGOUT',1,'2012-12-08 15:22:53',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(139,'2012-12-08 14:22:54','USER_LOGIN',1,'2012-12-08 15:22:54',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(140,'2012-12-08 14:23:10','USER_LOGOUT',1,'2012-12-08 15:23:10',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(141,'2012-12-08 14:23:11','USER_LOGIN',1,'2012-12-08 15:23:11',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(142,'2012-12-08 14:23:49','USER_LOGOUT',1,'2012-12-08 15:23:49',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(143,'2012-12-08 14:23:50','USER_LOGIN',1,'2012-12-08 15:23:50',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(144,'2012-12-08 14:28:08','USER_LOGOUT',1,'2012-12-08 15:28:08',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(145,'2012-12-08 14:35:15','USER_LOGIN',1,'2012-12-08 15:35:15',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(146,'2012-12-08 14:35:18','USER_LOGOUT',1,'2012-12-08 15:35:18',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(147,'2012-12-08 14:36:07','USER_LOGIN',1,'2012-12-08 15:36:07',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(148,'2012-12-08 14:36:09','USER_LOGOUT',1,'2012-12-08 15:36:09',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(149,'2012-12-08 14:36:41','USER_LOGIN',1,'2012-12-08 15:36:41',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(150,'2012-12-08 15:59:13','USER_LOGIN',1,'2012-12-08 16:59:13',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(151,'2012-12-09 11:49:52','USER_LOGIN',1,'2012-12-09 12:49:52',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(152,'2012-12-09 13:46:31','USER_LOGIN',1,'2012-12-09 14:46:31',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(153,'2012-12-09 19:03:14','USER_LOGIN',1,'2012-12-09 20:03:14',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(154,'2012-12-10 00:16:31','USER_LOGIN',1,'2012-12-10 01:16:31',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(170,'2012-12-11 22:03:31','USER_LOGIN',1,'2012-12-11 23:03:31',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(171,'2012-12-12 00:32:39','USER_LOGIN',1,'2012-12-12 01:32:39',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(172,'2012-12-12 10:49:59','USER_LOGIN',1,'2012-12-12 11:49:59',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(175,'2012-12-12 10:57:40','USER_MODIFY',1,'2012-12-12 11:57:40',1,'Modification utilisateur admin','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(176,'2012-12-12 13:29:15','USER_LOGIN',1,'2012-12-12 14:29:15',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(177,'2012-12-12 13:30:15','USER_LOGIN',1,'2012-12-12 14:30:15',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(178,'2012-12-12 13:40:08','USER_LOGOUT',1,'2012-12-12 14:40:08',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(179,'2012-12-12 13:40:10','USER_LOGIN',1,'2012-12-12 14:40:10',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(180,'2012-12-12 13:40:26','USER_MODIFY',1,'2012-12-12 14:40:26',1,'Modification utilisateur admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(181,'2012-12-12 13:40:34','USER_LOGOUT',1,'2012-12-12 14:40:34',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(182,'2012-12-12 13:42:23','USER_LOGIN',1,'2012-12-12 14:42:23',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(183,'2012-12-12 13:43:02','USER_NEW_PASSWORD',1,'2012-12-12 14:43:02',NULL,'Changement mot de passe de admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(184,'2012-12-12 13:43:25','USER_LOGOUT',1,'2012-12-12 14:43:25',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(185,'2012-12-12 13:43:27','USER_LOGIN_FAILED',1,'2012-12-12 14:43:27',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(186,'2012-12-12 13:43:30','USER_LOGIN',1,'2012-12-12 14:43:30',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(187,'2012-12-12 14:52:11','USER_LOGIN',1,'2012-12-12 15:52:11',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11',NULL),(188,'2012-12-12 17:53:00','USER_LOGIN_FAILED',1,'2012-12-12 18:53:00',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(189,'2012-12-12 17:53:07','USER_LOGIN_FAILED',1,'2012-12-12 18:53:07',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(190,'2012-12-12 17:53:51','USER_NEW_PASSWORD',1,'2012-12-12 18:53:51',NULL,'Changement mot de passe de admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(191,'2012-12-12 17:54:00','USER_LOGIN',1,'2012-12-12 18:54:00',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(192,'2012-12-12 17:54:10','USER_NEW_PASSWORD',1,'2012-12-12 18:54:10',1,'Changement mot de passe de admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(193,'2012-12-12 17:54:10','USER_MODIFY',1,'2012-12-12 18:54:10',1,'Modification utilisateur admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(194,'2012-12-12 18:57:09','USER_LOGIN',1,'2012-12-12 19:57:09',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(195,'2012-12-12 23:04:08','USER_LOGIN',1,'2012-12-13 00:04:08',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(196,'2012-12-17 20:03:14','USER_LOGIN',1,'2012-12-17 21:03:14',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(197,'2012-12-17 21:18:45','USER_LOGIN',1,'2012-12-17 22:18:45',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(198,'2012-12-17 22:30:08','USER_LOGIN',1,'2012-12-17 23:30:08',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(199,'2012-12-18 23:32:03','USER_LOGIN',1,'2012-12-19 00:32:03',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(200,'2012-12-19 09:38:03','USER_LOGIN',1,'2012-12-19 10:38:03',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(201,'2012-12-19 11:23:35','USER_LOGIN',1,'2012-12-19 12:23:35',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(202,'2012-12-19 12:46:22','USER_LOGIN',1,'2012-12-19 13:46:22',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(214,'2012-12-19 19:11:31','USER_LOGIN',1,'2012-12-19 20:11:31',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(215,'2012-12-21 16:36:57','USER_LOGIN',1,'2012-12-21 17:36:57',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(216,'2012-12-21 16:38:43','USER_NEW_PASSWORD',1,'2012-12-21 17:38:43',1,'Changement mot de passe de adupont','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(217,'2012-12-21 16:38:43','USER_MODIFY',1,'2012-12-21 17:38:43',1,'Modification utilisateur adupont','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(218,'2012-12-21 16:38:51','USER_LOGOUT',1,'2012-12-21 17:38:51',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(219,'2012-12-21 16:38:55','USER_LOGIN',1,'2012-12-21 17:38:55',3,'(UserLogged,adupont)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(220,'2012-12-21 16:48:18','USER_LOGOUT',1,'2012-12-21 17:48:18',3,'(UserLogoff,adupont)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(221,'2012-12-21 16:48:20','USER_LOGIN',1,'2012-12-21 17:48:20',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(222,'2012-12-26 18:28:18','USER_LOGIN',1,'2012-12-26 19:28:18',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(223,'2012-12-26 20:00:24','USER_LOGIN',1,'2012-12-26 21:00:24',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(224,'2012-12-27 01:10:27','USER_LOGIN',1,'2012-12-27 02:10:27',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(225,'2012-12-28 19:12:08','USER_LOGIN',1,'2012-12-28 20:12:08',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(226,'2012-12-28 20:16:58','USER_LOGIN',1,'2012-12-28 21:16:58',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(227,'2012-12-29 14:35:46','USER_LOGIN',1,'2012-12-29 15:35:46',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(228,'2012-12-29 14:37:59','USER_LOGOUT',1,'2012-12-29 15:37:59',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(229,'2012-12-29 14:38:00','USER_LOGIN',1,'2012-12-29 15:38:00',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(230,'2012-12-29 17:16:48','USER_LOGIN',1,'2012-12-29 18:16:48',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(231,'2012-12-31 12:02:59','USER_LOGIN',1,'2012-12-31 13:02:59',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(232,'2013-01-02 20:32:51','USER_LOGIN',1,'2013-01-02 21:32:51',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:17.0) Gecko/20100101 Firefox/17.0',NULL),(233,'2013-01-02 20:58:59','USER_LOGIN',1,'2013-01-02 21:58:59',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(234,'2013-01-03 09:25:07','USER_LOGIN',1,'2013-01-03 10:25:07',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(235,'2013-01-03 19:39:31','USER_LOGIN',1,'2013-01-03 20:39:31',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(236,'2013-01-04 22:40:19','USER_LOGIN',1,'2013-01-04 23:40:19',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(237,'2013-01-05 12:59:59','USER_LOGIN',1,'2013-01-05 13:59:59',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(238,'2013-01-05 15:28:52','USER_LOGIN',1,'2013-01-05 16:28:52',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(239,'2013-01-05 17:02:08','USER_LOGIN',1,'2013-01-05 18:02:08',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(240,'2013-01-06 12:13:33','USER_LOGIN',1,'2013-01-06 13:13:33',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(241,'2013-01-07 01:21:15','USER_LOGIN',1,'2013-01-07 02:21:15',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(242,'2013-01-07 01:46:31','USER_LOGOUT',1,'2013-01-07 02:46:31',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(243,'2013-01-07 19:54:50','USER_LOGIN',1,'2013-01-07 20:54:50',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(244,'2013-01-08 21:55:01','USER_LOGIN',1,'2013-01-08 22:55:01',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(245,'2013-01-09 11:13:28','USER_LOGIN',1,'2013-01-09 12:13:28',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(246,'2013-01-10 18:30:46','USER_LOGIN',1,'2013-01-10 19:30:46',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(247,'2013-01-11 18:03:26','USER_LOGIN',1,'2013-01-11 19:03:26',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(248,'2013-01-12 11:15:04','USER_LOGIN',1,'2013-01-12 12:15:04',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(249,'2013-01-12 14:42:44','USER_LOGIN',1,'2013-01-12 15:42:44',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(250,'2013-01-13 12:07:17','USER_LOGIN',1,'2013-01-13 13:07:17',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(251,'2013-01-13 17:37:58','USER_LOGIN',1,'2013-01-13 18:37:58',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(252,'2013-01-13 19:24:21','USER_LOGIN',1,'2013-01-13 20:24:21',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(253,'2013-01-13 19:29:19','USER_LOGOUT',1,'2013-01-13 20:29:19',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(254,'2013-01-13 21:39:39','USER_LOGIN',1,'2013-01-13 22:39:39',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(255,'2013-01-14 00:52:21','USER_LOGIN',1,'2013-01-14 01:52:21',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',NULL),(256,'2013-01-16 11:34:31','USER_LOGIN',1,'2013-01-16 12:34:31',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(257,'2013-01-16 15:36:21','USER_LOGIN',1,'2013-01-16 16:36:21',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(258,'2013-01-16 19:17:36','USER_LOGIN',1,'2013-01-16 20:17:36',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(259,'2013-01-16 19:48:08','GROUP_CREATE',1,'2013-01-16 20:48:08',1,'Création groupe ggg','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(260,'2013-01-16 21:48:53','USER_LOGIN',1,'2013-01-16 22:48:53',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(261,'2013-01-17 19:55:53','USER_LOGIN',1,'2013-01-17 20:55:53',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(262,'2013-01-18 09:48:01','USER_LOGIN',1,'2013-01-18 10:48:01',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(263,'2013-01-18 13:22:36','USER_LOGIN',1,'2013-01-18 14:22:36',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(264,'2013-01-18 16:10:23','USER_LOGIN',1,'2013-01-18 17:10:22',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(265,'2013-01-18 17:41:40','USER_LOGIN',1,'2013-01-18 18:41:40',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(266,'2013-01-19 14:33:48','USER_LOGIN',1,'2013-01-19 15:33:48',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(267,'2013-01-19 16:47:43','USER_LOGIN',1,'2013-01-19 17:47:43',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(268,'2013-01-19 16:59:43','USER_LOGIN',1,'2013-01-19 17:59:43',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(269,'2013-01-19 17:00:22','USER_LOGIN',1,'2013-01-19 18:00:22',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(270,'2013-01-19 17:04:16','USER_LOGOUT',1,'2013-01-19 18:04:16',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(271,'2013-01-19 17:04:18','USER_LOGIN',1,'2013-01-19 18:04:18',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(272,'2013-01-20 00:34:19','USER_LOGIN',1,'2013-01-20 01:34:19',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(273,'2013-01-21 11:54:17','USER_LOGIN',1,'2013-01-21 12:54:17',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(274,'2013-01-21 13:48:15','USER_LOGIN',1,'2013-01-21 14:48:15',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(275,'2013-01-21 14:30:22','USER_LOGIN',1,'2013-01-21 15:30:22',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(276,'2013-01-21 15:10:46','USER_LOGIN',1,'2013-01-21 16:10:46',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(277,'2013-01-21 17:27:43','USER_LOGIN',1,'2013-01-21 18:27:43',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(278,'2013-01-21 21:48:15','USER_LOGIN',1,'2013-01-21 22:48:15',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(279,'2013-01-21 21:50:42','USER_LOGIN',1,'2013-01-21 22:50:42',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',NULL),(280,'2013-01-23 09:28:26','USER_LOGIN',1,'2013-01-23 10:28:26',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(281,'2013-01-23 13:21:57','USER_LOGIN',1,'2013-01-23 14:21:57',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(282,'2013-01-23 16:52:00','USER_LOGOUT',1,'2013-01-23 17:52:00',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(283,'2013-01-23 16:52:05','USER_LOGIN_FAILED',1,'2013-01-23 17:52:05',NULL,'Bad value for login or password - login=bbb','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(284,'2013-01-23 16:52:09','USER_LOGIN',1,'2013-01-23 17:52:09',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(285,'2013-01-23 16:52:27','USER_CREATE',1,'2013-01-23 17:52:27',1,'Création utilisateur aaa','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(286,'2013-01-23 16:52:27','USER_NEW_PASSWORD',1,'2013-01-23 17:52:27',1,'Changement mot de passe de aaa','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(287,'2013-01-23 16:52:37','USER_CREATE',1,'2013-01-23 17:52:37',1,'Création utilisateur bbb','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(288,'2013-01-23 16:52:37','USER_NEW_PASSWORD',1,'2013-01-23 17:52:37',1,'Changement mot de passe de bbb','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(289,'2013-01-23 16:53:15','USER_LOGOUT',1,'2013-01-23 17:53:15',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(290,'2013-01-23 16:53:20','USER_LOGIN',1,'2013-01-23 17:53:20',4,'(UserLogged,aaa)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(291,'2013-01-23 19:16:58','USER_LOGIN',1,'2013-01-23 20:16:58',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(292,'2013-01-26 10:54:07','USER_LOGIN',1,'2013-01-26 11:54:07',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(293,'2013-01-29 10:15:36','USER_LOGIN',1,'2013-01-29 11:15:36',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(294,'2013-01-30 17:42:50','USER_LOGIN',1,'2013-01-30 18:42:50',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.56 Safari/537.17',NULL),(295,'2013-02-01 08:49:55','USER_LOGIN',1,'2013-02-01 09:49:55',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(296,'2013-02-01 08:51:57','USER_LOGOUT',1,'2013-02-01 09:51:57',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(297,'2013-02-01 08:52:39','USER_LOGIN',1,'2013-02-01 09:52:39',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(298,'2013-02-01 21:03:01','USER_LOGIN',1,'2013-02-01 22:03:01',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(299,'2013-02-10 19:48:39','USER_LOGIN',1,'2013-02-10 20:48:39',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(300,'2013-02-10 20:46:48','USER_LOGIN',1,'2013-02-10 21:46:48',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(301,'2013-02-10 21:39:23','USER_LOGIN',1,'2013-02-10 22:39:23',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(302,'2013-02-11 19:00:13','USER_LOGIN',1,'2013-02-11 20:00:13',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(303,'2013-02-11 19:43:44','USER_LOGIN_FAILED',1,'2013-02-11 20:43:44',NULL,'Unknown column \'u.fk_user\' in \'field list\'','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(304,'2013-02-11 19:44:01','USER_LOGIN',1,'2013-02-11 20:44:01',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(305,'2013-02-12 00:27:35','USER_LOGIN',1,'2013-02-12 01:27:35',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(306,'2013-02-12 00:27:38','USER_LOGOUT',1,'2013-02-12 01:27:38',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(307,'2013-02-12 00:28:07','USER_LOGIN',1,'2013-02-12 01:28:07',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(308,'2013-02-12 00:28:09','USER_LOGOUT',1,'2013-02-12 01:28:09',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(309,'2013-02-12 00:28:26','USER_LOGIN',1,'2013-02-12 01:28:26',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(310,'2013-02-12 00:28:30','USER_LOGOUT',1,'2013-02-12 01:28:30',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(311,'2013-02-12 12:42:15','USER_LOGIN',1,'2013-02-12 13:42:15',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17',NULL),(312,'2013-02-12 13:46:16','USER_LOGIN',1,'2013-02-12 14:46:16',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(313,'2013-02-12 14:54:28','USER_LOGIN',1,'2013-02-12 15:54:28',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(314,'2013-02-12 16:04:46','USER_LOGIN',1,'2013-02-12 17:04:46',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(315,'2013-02-13 14:02:43','USER_LOGIN',1,'2013-02-13 15:02:43',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(316,'2013-02-13 14:48:30','USER_LOGIN',1,'2013-02-13 15:48:30',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(317,'2013-02-13 17:44:53','USER_LOGIN',1,'2013-02-13 18:44:53',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(318,'2013-02-15 08:44:36','USER_LOGIN',1,'2013-02-15 09:44:36',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(319,'2013-02-15 08:53:20','USER_LOGIN',1,'2013-02-15 09:53:20',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(320,'2013-02-16 19:10:28','USER_LOGIN',1,'2013-02-16 20:10:28',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(321,'2013-02-16 19:22:40','USER_CREATE',1,'2013-02-16 20:22:40',1,'Création utilisateur aaab','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(322,'2013-02-16 19:22:40','USER_NEW_PASSWORD',1,'2013-02-16 20:22:40',1,'Changement mot de passe de aaab','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(323,'2013-02-16 19:48:15','USER_CREATE',1,'2013-02-16 20:48:15',1,'Création utilisateur zzz','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(324,'2013-02-16 19:48:15','USER_NEW_PASSWORD',1,'2013-02-16 20:48:15',1,'Changement mot de passe de zzz','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(325,'2013-02-16 19:50:08','USER_CREATE',1,'2013-02-16 20:50:08',1,'Création utilisateur zzzg','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(326,'2013-02-16 19:50:08','USER_NEW_PASSWORD',1,'2013-02-16 20:50:08',1,'Changement mot de passe de zzzg','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(327,'2013-02-16 21:20:03','USER_LOGIN',1,'2013-02-16 22:20:03',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(328,'2013-02-17 14:30:51','USER_LOGIN',1,'2013-02-17 15:30:51',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(329,'2013-02-17 17:21:22','USER_LOGIN',1,'2013-02-17 18:21:22',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(330,'2013-02-17 17:48:43','USER_MODIFY',1,'2013-02-17 18:48:43',1,'Modification utilisateur aaa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(331,'2013-02-17 17:48:47','USER_MODIFY',1,'2013-02-17 18:48:47',1,'Modification utilisateur aaa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(332,'2013-02-17 17:48:51','USER_MODIFY',1,'2013-02-17 18:48:51',1,'Modification utilisateur aaa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(333,'2013-02-17 17:48:56','USER_MODIFY',1,'2013-02-17 18:48:56',1,'Modification utilisateur aaa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(334,'2013-02-18 22:00:01','USER_LOGIN',1,'2013-02-18 23:00:01',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(335,'2013-02-19 08:19:52','USER_LOGIN',1,'2013-02-19 09:19:52',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(336,'2013-02-19 22:00:52','USER_LOGIN',1,'2013-02-19 23:00:52',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(337,'2013-02-20 09:34:52','USER_LOGIN',1,'2013-02-20 10:34:52',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(338,'2013-02-20 13:12:28','USER_LOGIN',1,'2013-02-20 14:12:28',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(339,'2013-02-20 17:19:44','USER_LOGIN',1,'2013-02-20 18:19:44',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(340,'2013-02-20 19:07:21','USER_MODIFY',1,'2013-02-20 20:07:21',1,'Modification utilisateur adupont','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(341,'2013-02-20 19:47:17','USER_LOGIN',1,'2013-02-20 20:47:17',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(342,'2013-02-20 19:48:01','USER_MODIFY',1,'2013-02-20 20:48:01',1,'Modification utilisateur aaa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(343,'2013-02-21 08:27:07','USER_LOGIN',1,'2013-02-21 09:27:07',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(344,'2013-02-23 13:34:13','USER_LOGIN',1,'2013-02-23 14:34:13',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.69 Safari/537.17',NULL),(345,'2013-02-24 01:06:41','USER_LOGIN_FAILED',1,'2013-02-24 02:06:41',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(346,'2013-02-24 01:06:45','USER_LOGIN_FAILED',1,'2013-02-24 02:06:45',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(347,'2013-02-24 01:06:55','USER_LOGIN_FAILED',1,'2013-02-24 02:06:55',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(348,'2013-02-24 01:07:03','USER_LOGIN_FAILED',1,'2013-02-24 02:07:03',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(349,'2013-02-24 01:07:21','USER_LOGIN_FAILED',1,'2013-02-24 02:07:21',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(350,'2013-02-24 01:08:12','USER_LOGIN_FAILED',1,'2013-02-24 02:08:12',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(351,'2013-02-24 01:08:42','USER_LOGIN_FAILED',1,'2013-02-24 02:08:42',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(352,'2013-02-24 01:08:50','USER_LOGIN_FAILED',1,'2013-02-24 02:08:50',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(353,'2013-02-24 01:09:08','USER_LOGIN_FAILED',1,'2013-02-24 02:09:08',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(354,'2013-02-24 01:09:42','USER_LOGIN_FAILED',1,'2013-02-24 02:09:42',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(355,'2013-02-24 01:09:50','USER_LOGIN_FAILED',1,'2013-02-24 02:09:50',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(356,'2013-02-24 01:10:05','USER_LOGIN_FAILED',1,'2013-02-24 02:10:05',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(357,'2013-02-24 01:10:22','USER_LOGIN_FAILED',1,'2013-02-24 02:10:22',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(358,'2013-02-24 01:10:30','USER_LOGIN_FAILED',1,'2013-02-24 02:10:30',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(359,'2013-02-24 01:10:56','USER_LOGIN_FAILED',1,'2013-02-24 02:10:56',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(360,'2013-02-24 01:11:26','USER_LOGIN_FAILED',1,'2013-02-24 02:11:26',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(361,'2013-02-24 01:12:06','USER_LOGIN_FAILED',1,'2013-02-24 02:12:06',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(362,'2013-02-24 01:21:14','USER_LOGIN_FAILED',1,'2013-02-24 02:21:14',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(363,'2013-02-24 01:21:25','USER_LOGIN_FAILED',1,'2013-02-24 02:21:25',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(364,'2013-02-24 01:21:54','USER_LOGIN_FAILED',1,'2013-02-24 02:21:54',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(365,'2013-02-24 01:22:14','USER_LOGIN_FAILED',1,'2013-02-24 02:22:14',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(366,'2013-02-24 01:22:37','USER_LOGIN_FAILED',1,'2013-02-24 02:22:37',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(367,'2013-02-24 01:23:01','USER_LOGIN_FAILED',1,'2013-02-24 02:23:01',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(368,'2013-02-24 01:23:39','USER_LOGIN_FAILED',1,'2013-02-24 02:23:39',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(369,'2013-02-24 01:24:04','USER_LOGIN_FAILED',1,'2013-02-24 02:24:04',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(370,'2013-02-24 01:24:39','USER_LOGIN_FAILED',1,'2013-02-24 02:24:39',NULL,'Bad value for login or password - login=aa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(371,'2013-02-24 01:25:01','USER_LOGIN_FAILED',1,'2013-02-24 02:25:01',NULL,'Bad value for login or password - login=aa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(372,'2013-02-24 01:25:12','USER_LOGIN_FAILED',1,'2013-02-24 02:25:12',NULL,'Bad value for login or password - login=aa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(373,'2013-02-24 01:27:30','USER_LOGIN_FAILED',1,'2013-02-24 02:27:30',NULL,'Bad value for login or password - login=aa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(374,'2013-02-24 01:28:00','USER_LOGIN_FAILED',1,'2013-02-24 02:28:00',NULL,'Bad value for login or password - login=aa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(375,'2013-02-24 01:28:35','USER_LOGIN_FAILED',1,'2013-02-24 02:28:35',NULL,'Bad value for login or password - login=aa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(376,'2013-02-24 01:29:03','USER_LOGIN_FAILED',1,'2013-02-24 02:29:03',NULL,'Bad value for login or password - login=aa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(377,'2013-02-24 01:29:55','USER_LOGIN_FAILED',1,'2013-02-24 02:29:55',NULL,'Bad value for login or password - login=aa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(378,'2013-02-24 01:32:40','USER_LOGIN_FAILED',1,'2013-02-24 02:32:40',NULL,'Bad value for login or password - login=aa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(379,'2013-02-24 01:39:33','USER_LOGIN_FAILED',1,'2013-02-24 02:39:33',NULL,'Identifiants login ou mot de passe incorrects - login=aa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(380,'2013-02-24 01:39:38','USER_LOGIN_FAILED',1,'2013-02-24 02:39:38',NULL,'Identifiants login ou mot de passe incorrects - login=aa','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(381,'2013-02-24 01:39:47','USER_LOGIN_FAILED',1,'2013-02-24 02:39:47',NULL,'Identifiants login ou mot de passe incorrects - login=lmkm','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(382,'2013-02-24 01:40:54','USER_LOGIN_FAILED',1,'2013-02-24 02:40:54',NULL,'Identifiants login ou mot de passe incorrects - login=lmkm','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(383,'2013-02-24 01:47:57','USER_LOGIN_FAILED',1,'2013-02-24 02:47:57',NULL,'Identifiants login ou mot de passe incorrects - login=lmkm','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(384,'2013-02-24 01:48:05','USER_LOGIN_FAILED',1,'2013-02-24 02:48:05',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(385,'2013-02-24 01:48:07','USER_LOGIN_FAILED',1,'2013-02-24 02:48:07',NULL,'Unknown column \'u.lastname\' in \'field list\'','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(386,'2013-02-24 01:48:35','USER_LOGIN',1,'2013-02-24 02:48:35',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(387,'2013-02-24 01:56:32','USER_LOGIN',1,'2013-02-24 02:56:32',1,'(UserLogged,admin)','192.168.0.254','Mozilla/5.0 (Linux; U; Android 2.2; en-us; sdk Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',NULL),(388,'2013-02-24 02:05:55','USER_LOGOUT',1,'2013-02-24 03:05:55',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(389,'2013-02-24 02:39:52','USER_LOGIN',1,'2013-02-24 03:39:52',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(390,'2013-02-24 02:51:10','USER_LOGOUT',1,'2013-02-24 03:51:10',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(391,'2013-02-24 12:46:41','USER_LOGIN',1,'2013-02-24 13:46:41',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(392,'2013-02-24 12:46:52','USER_LOGOUT',1,'2013-02-24 13:46:52',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(393,'2013-02-24 12:46:56','USER_LOGIN',1,'2013-02-24 13:46:56',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(394,'2013-02-24 12:47:56','USER_LOGOUT',1,'2013-02-24 13:47:56',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(395,'2013-02-24 12:48:00','USER_LOGIN',1,'2013-02-24 13:48:00',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(396,'2013-02-24 12:48:11','USER_LOGOUT',1,'2013-02-24 13:48:11',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(397,'2013-02-24 12:48:32','USER_LOGIN',1,'2013-02-24 13:48:32',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(398,'2013-02-24 12:52:22','USER_LOGOUT',1,'2013-02-24 13:52:22',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(399,'2013-02-24 12:52:27','USER_LOGIN',1,'2013-02-24 13:52:27',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(400,'2013-02-24 12:52:54','USER_LOGOUT',1,'2013-02-24 13:52:54',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(401,'2013-02-24 12:52:59','USER_LOGIN',1,'2013-02-24 13:52:59',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(402,'2013-02-24 12:55:39','USER_LOGOUT',1,'2013-02-24 13:55:39',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(403,'2013-02-24 12:55:59','USER_LOGIN',1,'2013-02-24 13:55:59',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(404,'2013-02-24 12:56:07','USER_LOGOUT',1,'2013-02-24 13:56:07',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(405,'2013-02-24 12:56:23','USER_LOGIN',1,'2013-02-24 13:56:23',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(406,'2013-02-24 12:56:46','USER_LOGOUT',1,'2013-02-24 13:56:46',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(407,'2013-02-24 12:58:30','USER_LOGIN',1,'2013-02-24 13:58:30',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(408,'2013-02-24 12:58:33','USER_LOGOUT',1,'2013-02-24 13:58:33',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(409,'2013-02-24 12:58:51','USER_LOGIN',1,'2013-02-24 13:58:51',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(410,'2013-02-24 12:58:58','USER_LOGOUT',1,'2013-02-24 13:58:58',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(411,'2013-02-24 13:18:53','USER_LOGIN',1,'2013-02-24 14:18:53',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(412,'2013-02-24 13:19:52','USER_LOGOUT',1,'2013-02-24 14:19:52',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(413,'2013-02-24 15:39:31','USER_LOGIN_FAILED',1,'2013-02-24 16:39:31',NULL,'ErrorBadValueForCode - login=admin','127.0.0.1',NULL,NULL),(414,'2013-02-24 15:42:07','USER_LOGIN',1,'2013-02-24 16:42:07',1,'(UserLogged,admin)','127.0.0.1',NULL,NULL),(415,'2013-02-24 15:42:52','USER_LOGOUT',1,'2013-02-24 16:42:52',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/6531.22.7',NULL),(416,'2013-02-24 16:04:21','USER_LOGIN',1,'2013-02-24 17:04:21',1,'(UserLogged,admin)','192.168.0.254','Mozilla/5.0 (Linux; U; Android 2.2; en-us; sdk Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',NULL),(417,'2013-02-24 16:11:28','USER_LOGIN_FAILED',1,'2013-02-24 17:11:28',NULL,'ErrorBadValueForCode - login=admin','127.0.0.1','Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/6531.22.7',NULL),(418,'2013-02-24 16:11:37','USER_LOGIN',1,'2013-02-24 17:11:37',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/6531.22.7',NULL),(419,'2013-02-24 16:36:52','USER_LOGOUT',1,'2013-02-24 17:36:52',1,'(UserLogoff,admin)','192.168.0.254','Mozilla/5.0 (Linux; U; Android 2.2; en-us; sdk Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',NULL),(420,'2013-02-24 16:40:37','USER_LOGIN',1,'2013-02-24 17:40:37',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(421,'2013-02-24 16:57:16','USER_LOGIN',1,'2013-02-24 17:57:16',1,'(UserLogged,admin)','192.168.0.254','Mozilla/5.0 (Linux; U; Android 2.2; en-us; sdk Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1 - 2131034114',NULL),(422,'2013-02-24 17:01:30','USER_LOGOUT',1,'2013-02-24 18:01:30',1,'(UserLogoff,admin)','192.168.0.254','Mozilla/5.0 (Linux; U; Android 2.2; en-us; sdk Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1 - 2131034114',NULL),(423,'2013-02-24 17:02:33','USER_LOGIN',1,'2013-02-24 18:02:33',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(424,'2013-02-24 17:14:22','USER_LOGOUT',1,'2013-02-24 18:14:22',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(425,'2013-02-24 17:15:07','USER_LOGIN_FAILED',1,'2013-02-24 18:15:07',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(426,'2013-02-24 17:15:20','USER_LOGIN',1,'2013-02-24 18:15:20',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(427,'2013-02-24 17:20:14','USER_LOGIN',1,'2013-02-24 18:20:14',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(428,'2013-02-24 17:20:51','USER_LOGIN',1,'2013-02-24 18:20:51',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(429,'2013-02-24 17:20:54','USER_LOGOUT',1,'2013-02-24 18:20:54',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(430,'2013-02-24 17:21:19','USER_LOGIN',1,'2013-02-24 18:21:19',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(431,'2013-02-24 17:32:35','USER_LOGIN',1,'2013-02-24 18:32:35',1,'(UserLogged,admin)','192.168.0.254','Mozilla/5.0 (Linux; U; Android 2.2; en-us; sdk Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1 - 2131034114',NULL),(432,'2013-02-24 18:28:48','USER_LOGIN',1,'2013-02-24 19:28:48',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(433,'2013-02-24 18:29:27','USER_LOGOUT',1,'2013-02-24 19:29:27',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/6531.22.7',NULL),(434,'2013-02-24 18:29:32','USER_LOGIN',1,'2013-02-24 19:29:32',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/6531.22.7',NULL),(435,'2013-02-24 20:13:13','USER_LOGOUT',1,'2013-02-24 21:13:13',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(436,'2013-02-24 20:13:17','USER_LOGIN',1,'2013-02-24 21:13:17',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(437,'2013-02-25 08:57:16','USER_LOGIN',1,'2013-02-25 09:57:16',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(438,'2013-02-25 08:57:59','USER_LOGOUT',1,'2013-02-25 09:57:59',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(439,'2013-02-25 09:15:02','USER_LOGIN',1,'2013-02-25 10:15:02',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(440,'2013-02-25 09:15:50','USER_LOGOUT',1,'2013-02-25 10:15:50',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(441,'2013-02-25 09:15:57','USER_LOGIN',1,'2013-02-25 10:15:57',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(442,'2013-02-25 09:16:12','USER_LOGOUT',1,'2013-02-25 10:16:12',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(443,'2013-02-25 09:16:19','USER_LOGIN',1,'2013-02-25 10:16:19',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(444,'2013-02-25 09:16:25','USER_LOGOUT',1,'2013-02-25 10:16:25',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(445,'2013-02-25 09:16:39','USER_LOGIN_FAILED',1,'2013-02-25 10:16:39',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(446,'2013-02-25 09:16:42','USER_LOGIN_FAILED',1,'2013-02-25 10:16:42',NULL,'Bad value for login or password - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(447,'2013-02-25 09:16:54','USER_LOGIN_FAILED',1,'2013-02-25 10:16:54',NULL,'Identificadors d&#039;usuari o contrasenya incorrectes - login=gfdg','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(448,'2013-02-25 09:17:53','USER_LOGIN',1,'2013-02-25 10:17:53',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(449,'2013-02-25 09:18:37','USER_LOGOUT',1,'2013-02-25 10:18:37',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(450,'2013-02-25 09:18:41','USER_LOGIN',1,'2013-02-25 10:18:41',4,'(UserLogged,aaa)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(451,'2013-02-25 09:18:47','USER_LOGOUT',1,'2013-02-25 10:18:47',4,'(UserLogoff,aaa)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(452,'2013-02-25 10:05:34','USER_LOGIN',1,'2013-02-25 11:05:34',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(453,'2013-02-26 21:51:40','USER_LOGIN',1,'2013-02-26 22:51:40',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(454,'2013-02-26 23:30:06','USER_LOGIN',1,'2013-02-27 00:30:06',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(455,'2013-02-27 14:13:11','USER_LOGIN',1,'2013-02-27 15:13:11',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(456,'2013-02-27 18:12:06','USER_LOGIN_FAILED',1,'2013-02-27 19:12:06',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(457,'2013-02-27 18:12:10','USER_LOGIN',1,'2013-02-27 19:12:10',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(458,'2013-02-27 20:20:08','USER_LOGIN',1,'2013-02-27 21:20:08',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(459,'2013-03-01 22:12:03','USER_LOGIN',1,'2013-03-01 23:12:03',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(460,'2013-03-02 11:45:50','USER_LOGIN',1,'2013-03-02 12:45:50',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(461,'2013-03-02 15:53:51','USER_LOGIN_FAILED',1,'2013-03-02 16:53:51',NULL,'Identifiants login ou mot de passe incorrects - login=admin','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(462,'2013-03-02 15:53:53','USER_LOGIN',1,'2013-03-02 16:53:53',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(463,'2013-03-02 18:32:32','USER_LOGIN',1,'2013-03-02 19:32:32',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(464,'2013-03-02 22:59:36','USER_LOGIN',1,'2013-03-02 23:59:36',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(465,'2013-03-03 16:26:26','USER_LOGIN',1,'2013-03-03 17:26:26',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(466,'2013-03-03 22:50:27','USER_LOGIN',1,'2013-03-03 23:50:27',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(467,'2013-03-04 08:29:27','USER_LOGIN',1,'2013-03-04 09:29:27',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(468,'2013-03-04 18:27:28','USER_LOGIN',1,'2013-03-04 19:27:28',1,'(UserLogged,admin)','192.168.0.254','Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; NP06)',NULL),(469,'2013-03-04 19:27:23','USER_LOGIN',1,'2013-03-04 20:27:23',1,'(UserLogged,admin)','192.168.0.254','Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)',NULL),(470,'2013-03-04 19:35:14','USER_LOGIN',1,'2013-03-04 20:35:14',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(471,'2013-03-04 19:55:49','USER_LOGIN',1,'2013-03-04 20:55:49',1,'(UserLogged,admin)','192.168.0.254','Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)',NULL),(472,'2013-03-04 21:16:13','USER_LOGIN',1,'2013-03-04 22:16:13',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(473,'2013-03-05 10:17:30','USER_LOGIN',1,'2013-03-05 11:17:30',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(474,'2013-03-05 11:02:43','USER_LOGIN',1,'2013-03-05 12:02:43',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(475,'2013-03-05 23:14:39','USER_LOGIN',1,'2013-03-06 00:14:39',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(476,'2013-03-06 08:58:57','USER_LOGIN',1,'2013-03-06 09:58:57',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(477,'2013-03-06 14:29:40','USER_LOGIN',1,'2013-03-06 15:29:40',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(478,'2013-03-06 21:53:02','USER_LOGIN',1,'2013-03-06 22:53:02',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(479,'2013-03-07 21:14:39','USER_LOGIN',1,'2013-03-07 22:14:39',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(480,'2013-03-08 00:06:05','USER_LOGIN',1,'2013-03-08 01:06:05',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(481,'2013-03-08 01:38:13','USER_LOGIN',1,'2013-03-08 02:38:13',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(482,'2013-03-08 08:59:50','USER_LOGIN',1,'2013-03-08 09:59:50',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(483,'2013-03-09 12:08:51','USER_LOGIN',1,'2013-03-09 13:08:51',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(484,'2013-03-09 15:19:53','USER_LOGIN',1,'2013-03-09 16:19:53',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(495,'2013-03-09 18:06:21','USER_LOGIN',1,'2013-03-09 19:06:21',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(496,'2013-03-09 20:01:24','USER_LOGIN',1,'2013-03-09 21:01:24',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(497,'2013-03-09 23:36:45','USER_LOGIN',1,'2013-03-10 00:36:45',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(498,'2013-03-10 14:37:13','USER_LOGIN',1,'2013-03-10 15:37:13',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(499,'2013-03-10 17:54:12','USER_LOGIN',1,'2013-03-10 18:54:12',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(500,'2013-03-11 08:57:09','USER_LOGIN',1,'2013-03-11 09:57:09',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(501,'2013-03-11 22:05:13','USER_LOGIN',1,'2013-03-11 23:05:13',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(502,'2013-03-12 08:34:27','USER_LOGIN',1,'2013-03-12 09:34:27',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(503,'2013-03-13 09:11:02','USER_LOGIN',1,'2013-03-13 10:11:02',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(504,'2013-03-13 10:02:11','USER_LOGIN',1,'2013-03-13 11:02:11',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(505,'2013-03-13 13:20:58','USER_LOGIN',1,'2013-03-13 14:20:58',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(506,'2013-03-13 16:19:28','USER_LOGIN',1,'2013-03-13 17:19:28',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(507,'2013-03-13 18:34:30','USER_LOGIN',1,'2013-03-13 19:34:30',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(508,'2013-03-14 08:25:02','USER_LOGIN',1,'2013-03-14 09:25:02',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(509,'2013-03-14 19:15:22','USER_LOGIN',1,'2013-03-14 20:15:22',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(510,'2013-03-14 21:58:53','USER_LOGIN',1,'2013-03-14 22:58:53',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(511,'2013-03-14 21:58:59','USER_LOGOUT',1,'2013-03-14 22:58:59',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(512,'2013-03-14 21:59:07','USER_LOGIN',1,'2013-03-14 22:59:07',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(513,'2013-03-14 22:58:22','USER_LOGOUT',1,'2013-03-14 23:58:22',1,'(UserLogoff,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(514,'2013-03-14 23:00:25','USER_LOGIN',1,'2013-03-15 00:00:25',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(515,'2013-03-16 12:14:28','USER_LOGIN',1,'2013-03-16 13:14:28',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(516,'2013-03-16 16:09:01','USER_LOGIN',1,'2013-03-16 17:09:01',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(517,'2013-03-16 16:57:11','USER_LOGIN',1,'2013-03-16 17:57:11',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(518,'2013-03-16 19:31:31','USER_LOGIN',1,'2013-03-16 20:31:31',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.97 Safari/537.22',NULL),(519,'2013-03-17 17:44:39','USER_LOGIN',1,'2013-03-17 18:44:39',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(520,'2013-03-17 20:40:57','USER_LOGIN',1,'2013-03-17 21:40:57',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(521,'2013-03-17 23:14:05','USER_LOGIN',1,'2013-03-18 00:14:05',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(522,'2013-03-17 23:28:47','USER_LOGOUT',1,'2013-03-18 00:28:47',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(523,'2013-03-17 23:28:54','USER_LOGIN',1,'2013-03-18 00:28:54',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(524,'2013-03-18 17:37:30','USER_LOGIN',1,'2013-03-18 18:37:30',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(525,'2013-03-18 18:11:37','USER_LOGIN',1,'2013-03-18 19:11:37',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(526,'2013-03-19 08:35:08','USER_LOGIN',1,'2013-03-19 09:35:08',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(527,'2013-03-19 09:20:23','USER_LOGIN',1,'2013-03-19 10:20:23',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(528,'2013-03-20 13:17:13','USER_LOGIN',1,'2013-03-20 14:17:13',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(529,'2013-03-20 14:44:31','USER_LOGIN',1,'2013-03-20 15:44:31',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(530,'2013-03-20 18:24:25','USER_LOGIN',1,'2013-03-20 19:24:25',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(531,'2013-03-20 19:15:54','USER_LOGIN',1,'2013-03-20 20:15:54',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(532,'2013-03-21 18:40:47','USER_LOGIN',1,'2013-03-21 19:40:47',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(533,'2013-03-21 21:42:24','USER_LOGIN',1,'2013-03-21 22:42:24',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(534,'2013-03-22 08:39:23','USER_LOGIN',1,'2013-03-22 09:39:23',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(535,'2013-03-23 13:04:55','USER_LOGIN',1,'2013-03-23 14:04:55',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(536,'2013-03-23 15:47:43','USER_LOGIN',1,'2013-03-23 16:47:43',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(537,'2013-03-23 22:56:36','USER_LOGIN',1,'2013-03-23 23:56:36',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(538,'2013-03-24 01:22:32','USER_LOGIN',1,'2013-03-24 02:22:32',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(539,'2013-03-24 14:40:42','USER_LOGIN',1,'2013-03-24 15:40:42',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(540,'2013-03-24 15:30:26','USER_LOGOUT',1,'2013-03-24 16:30:26',1,'(UserLogoff,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(541,'2013-03-24 15:30:29','USER_LOGIN',1,'2013-03-24 16:30:29',2,'(UserLogged,demo)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(542,'2013-03-24 15:49:40','USER_LOGOUT',1,'2013-03-24 16:49:40',2,'(UserLogoff,demo)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(543,'2013-03-24 15:49:48','USER_LOGIN',1,'2013-03-24 16:49:48',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(544,'2013-03-24 15:52:35','USER_MODIFY',1,'2013-03-24 16:52:35',1,'Modification utilisateur zzzg','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(545,'2013-03-24 15:52:52','USER_MODIFY',1,'2013-03-24 16:52:52',1,'Modification utilisateur zzzg','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(546,'2013-03-24 15:53:09','USER_MODIFY',1,'2013-03-24 16:53:09',1,'Modification utilisateur zzzg','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(547,'2013-03-24 15:53:23','USER_MODIFY',1,'2013-03-24 16:53:23',1,'Modification utilisateur zzzg','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(548,'2013-03-24 16:00:04','USER_MODIFY',1,'2013-03-24 17:00:04',1,'Modification utilisateur zzzg','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(549,'2013-03-24 16:01:50','USER_MODIFY',1,'2013-03-24 17:01:50',1,'Modification utilisateur zzzg','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(550,'2013-03-24 16:10:14','USER_MODIFY',1,'2013-03-24 17:10:14',1,'Modification utilisateur zzzg','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(551,'2013-03-24 16:55:13','USER_LOGIN',1,'2013-03-24 17:55:13',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(552,'2013-03-24 17:44:29','USER_LOGIN',1,'2013-03-24 18:44:29',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22',NULL),(553,'2013-09-08 23:06:26','USER_LOGIN',1,'2013-09-09 01:06:26',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.57 Safari/537.36',NULL),(554,'2013-10-21 22:32:28','USER_LOGIN',1,'2013-10-22 00:32:28',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36',NULL),(555,'2013-10-21 22:32:48','USER_LOGIN',1,'2013-10-22 00:32:48',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36',NULL),(556,'2013-11-07 00:01:51','USER_LOGIN',1,'2013-11-07 01:01:51',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.114 Safari/537.36',NULL),(557,'2014-04-05 14:19:30','USER_LOGIN',1,'2014-04-05 16:19:30',1,'(UserLogged,admin)','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.152 Safari/537.36',NULL);
/*!40000 ALTER TABLE `llx_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_expedition`
--

DROP TABLE IF EXISTS `llx_expedition`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_expedition` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_customer` varchar(30) DEFAULT NULL,
  `fk_soc` int(11) NOT NULL,
  `ref_ext` varchar(30) DEFAULT NULL,
  `ref_int` varchar(30) DEFAULT NULL,
  `date_creation` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `date_expedition` datetime DEFAULT NULL,
  `date_delivery` datetime DEFAULT NULL,
  `fk_address` int(11) DEFAULT NULL,
  `fk_shipping_method` int(11) DEFAULT NULL,
  `tracking_number` varchar(50) DEFAULT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `height` int(11) DEFAULT NULL,
  `height_unit` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `size_units` int(11) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `weight_units` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_expedition_uk_ref` (`ref`,`entity`),
  KEY `idx_expedition_fk_soc` (`fk_soc`),
  KEY `idx_expedition_fk_user_author` (`fk_user_author`),
  KEY `idx_expedition_fk_user_valid` (`fk_user_valid`),
  KEY `idx_expedition_fk_shipping_method` (`fk_shipping_method`),
  CONSTRAINT `fk_expedition_fk_shipping_method` FOREIGN KEY (`fk_shipping_method`) REFERENCES `llx_c_shipment_mode` (`rowid`),
  CONSTRAINT `fk_expedition_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_expedition_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_expedition_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_expedition`
--

LOCK TABLES `llx_expedition` WRITE;
/*!40000 ALTER TABLE `llx_expedition` DISABLE KEYS */;
INSERT INTO `llx_expedition` VALUES (1,'2013-02-17 17:22:51','SH1302-0001',1,NULL,1,NULL,NULL,'2011-08-08 03:05:34',1,'2013-02-17 18:22:51',1,NULL,'2011-08-09 00:00:00',NULL,NULL,'',1,NULL,NULL,NULL,0,NULL,0,NULL,NULL,NULL,NULL),(2,'2013-02-17 17:38:47','SH1302-0002',1,'gfdf',18,NULL,NULL,'2013-02-17 18:38:37',1,'2013-02-17 18:38:47',1,NULL,NULL,NULL,NULL,'',1,NULL,NULL,NULL,0,NULL,0,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_expedition` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_expedition_methode`
--

DROP TABLE IF EXISTS `llx_expedition_methode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_expedition_methode` (
  `rowid` int(11) NOT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `code` varchar(30) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `description` text,
  `active` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_expedition_methode`
--

LOCK TABLES `llx_expedition_methode` WRITE;
/*!40000 ALTER TABLE `llx_expedition_methode` DISABLE KEYS */;
INSERT INTO `llx_expedition_methode` VALUES (1,'2010-07-08 11:18:00','CATCH','Catch','Catch by client',1),(2,'2010-07-08 11:18:00','TRANS','Transporter','Generic transporter',1),(3,'2010-07-08 11:18:01','COLSUI','Colissimo Suivi','Colissimo Suivi',0);
/*!40000 ALTER TABLE `llx_expedition_methode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_expeditiondet`
--

DROP TABLE IF EXISTS `llx_expeditiondet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_expeditiondet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_expedition` int(11) NOT NULL,
  `fk_origin_line` int(11) DEFAULT NULL,
  `fk_entrepot` int(11) DEFAULT NULL,
  `qty` double DEFAULT NULL,
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  KEY `idx_expeditiondet_fk_expedition` (`fk_expedition`),
  CONSTRAINT `fk_expeditiondet_fk_expedition` FOREIGN KEY (`fk_expedition`) REFERENCES `llx_expedition` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_expeditiondet`
--

LOCK TABLES `llx_expeditiondet` WRITE;
/*!40000 ALTER TABLE `llx_expeditiondet` DISABLE KEYS */;
INSERT INTO `llx_expeditiondet` VALUES (1,1,10,3,1,0),(2,2,13,NULL,1,0),(3,2,16,NULL,1,0),(4,2,17,NULL,1,0);
/*!40000 ALTER TABLE `llx_expeditiondet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_export_compta`
--

DROP TABLE IF EXISTS `llx_export_compta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_export_compta` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(12) NOT NULL,
  `date_export` datetime NOT NULL,
  `fk_user` int(11) NOT NULL,
  `note` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_export_compta`
--

LOCK TABLES `llx_export_compta` WRITE;
/*!40000 ALTER TABLE `llx_export_compta` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_export_compta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_export_model`
--

DROP TABLE IF EXISTS `llx_export_model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_export_model` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL DEFAULT '0',
  `label` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL,
  `field` text NOT NULL,
  `filter` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_export_model` (`label`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_export_model`
--

LOCK TABLES `llx_export_model` WRITE;
/*!40000 ALTER TABLE `llx_export_model` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_export_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_extrafields`
--

DROP TABLE IF EXISTS `llx_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `elementtype` varchar(64) NOT NULL DEFAULT 'member',
  `name` varchar(64) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `label` varchar(255) NOT NULL,
  `type` varchar(8) DEFAULT NULL,
  `size` varchar(8) DEFAULT NULL,
  `pos` int(11) DEFAULT '0',
  `param` text,
  `fieldunique` int(11) DEFAULT '0',
  `fieldrequired` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_extrafields_name` (`name`,`entity`,`elementtype`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_extrafields`
--

LOCK TABLES `llx_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_extrafields` DISABLE KEYS */;
INSERT INTO `llx_extrafields` VALUES (2,'adherent','zzz',1,'2013-09-08 23:04:20','zzz','varchar','255',0,NULL,0,0),(22,'societe','jjjj',1,'2013-09-08 23:04:20','jjj','varchar','255',0,NULL,0,0);
/*!40000 ALTER TABLE `llx_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture`
--

DROP TABLE IF EXISTS `llx_facture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facture` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `facnumber` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(255) DEFAULT NULL,
  `ref_int` varchar(255) DEFAULT NULL,
  `type` smallint(6) NOT NULL DEFAULT '0',
  `ref_client` varchar(255) DEFAULT NULL,
  `increment` varchar(10) DEFAULT NULL,
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `datef` date DEFAULT NULL,
  `date_valid` date DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `paye` smallint(6) NOT NULL DEFAULT '0',
  `amount` double(24,8) NOT NULL DEFAULT '0.00000000',
  `remise_percent` double DEFAULT '0',
  `remise_absolue` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `close_code` varchar(16) DEFAULT NULL,
  `close_note` varchar(128) DEFAULT NULL,
  `tva` double(24,8) DEFAULT '0.00000000',
  `localtax1` double(24,8) DEFAULT '0.00000000',
  `localtax2` double(24,8) DEFAULT '0.00000000',
  `revenuestamp` double(24,8) DEFAULT '0.00000000',
  `total` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_facture_source` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `fk_account` int(11) DEFAULT NULL,
  `fk_currency` varchar(2) DEFAULT NULL,
  `fk_cond_reglement` int(11) NOT NULL DEFAULT '1',
  `fk_mode_reglement` int(11) DEFAULT NULL,
  `date_lim_reglement` date DEFAULT NULL,
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_facture_uk_facnumber` (`facnumber`,`entity`),
  KEY `idx_facture_fk_soc` (`fk_soc`),
  KEY `idx_facture_fk_user_author` (`fk_user_author`),
  KEY `idx_facture_fk_user_valid` (`fk_user_valid`),
  KEY `idx_facture_fk_facture_source` (`fk_facture_source`),
  KEY `idx_facture_fk_projet` (`fk_projet`),
  KEY `idx_facture_fk_account` (`fk_account`),
  KEY `idx_facture_fk_currency` (`fk_currency`),
  CONSTRAINT `fk_facture_fk_facture_source` FOREIGN KEY (`fk_facture_source`) REFERENCES `llx_facture` (`rowid`),
  CONSTRAINT `fk_facture_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_facture_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_facture_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_facture_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=329 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facture`
--

LOCK TABLES `llx_facture` WRITE;
/*!40000 ALTER TABLE `llx_facture` DISABLE KEYS */;
INSERT INTO `llx_facture` VALUES (1,'FA1007-0001',1,NULL,NULL,0,NULL,NULL,9,'2010-07-10 14:55:26','2010-07-10',NULL,'2011-07-20 11:18:39',0,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.02000000,0.02000000,1,1,1,NULL,1,NULL,NULL,1,0,'2010-07-10',NULL,NULL,'crabe',NULL,NULL),(2,'FA1007-0002',1,NULL,NULL,0,NULL,NULL,2,'2010-07-10 18:20:13','2010-07-10',NULL,'2011-08-08 00:54:05',1,10.00000000,NULL,NULL,0,NULL,NULL,0.10000000,0.00000000,0.00000000,0.00000000,46.00000000,46.10000000,2,1,1,NULL,NULL,NULL,NULL,1,0,'2010-07-10',NULL,NULL,'crabe',NULL,NULL),(3,'FA1107-0006',1,NULL,NULL,0,NULL,NULL,10,'2011-07-18 20:33:35','2011-07-18',NULL,'2012-12-08 16:39:01',1,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,15.00000000,15.00000000,2,1,1,NULL,1,NULL,NULL,1,0,'2011-07-18',NULL,NULL,'crabe',NULL,NULL),(5,'FA1108-0003',1,NULL,NULL,0,NULL,NULL,7,'2011-08-01 03:34:11','2011-08-01',NULL,'2011-08-01 01:34:11',1,0.00000000,NULL,NULL,0,NULL,NULL,0.63000000,0.00000000,0.00000000,0.00000000,5.00000000,5.63000000,2,1,1,NULL,NULL,NULL,NULL,0,6,'2011-08-01',NULL,NULL,'',NULL,NULL),(6,'FA1108-0004',1,NULL,NULL,0,NULL,NULL,7,'2011-08-06 20:33:53','2011-08-06',NULL,'2011-08-06 18:35:13',1,0.00000000,NULL,NULL,0,NULL,NULL,0.98000000,0.00000000,0.00000000,0.00000000,5.00000000,5.98000000,2,1,1,NULL,NULL,NULL,NULL,0,4,'2011-08-06','Cash\nReceived : 6 EUR\nRendu : 0.02 EUR\n\n--------------------------------------',NULL,'crabe',NULL,NULL),(8,'FA1108-0005',1,NULL,NULL,3,NULL,NULL,2,'2011-08-08 02:41:44','2011-08-08',NULL,'2011-08-08 00:53:40',1,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,2,1,1,NULL,NULL,NULL,NULL,1,0,'2011-08-08',NULL,NULL,'crabe',NULL,NULL),(9,'FA1108-0007',1,NULL,NULL,3,NULL,NULL,10,'2011-08-08 02:55:14','2011-08-08',NULL,'2011-08-08 00:55:26',0,0.00000000,NULL,NULL,0,NULL,NULL,1.96000000,0.00000000,0.00000000,0.00000000,10.00000000,11.96000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2011-08-08',NULL,NULL,'crabe',NULL,NULL),(10,'AV1212-0001',1,NULL,NULL,2,NULL,NULL,10,'2012-12-08 17:45:20','2012-12-08','2012-12-08','2012-12-08 16:57:11',0,0.00000000,NULL,NULL,0,NULL,NULL,-0.63000000,0.00000000,0.00000000,0.00000000,-11.00000000,-11.63000000,1,1,1,3,NULL,NULL,NULL,0,0,'2012-12-08',NULL,NULL,'crabe',NULL,NULL),(11,'FA1212-0008',1,NULL,NULL,0,NULL,NULL,10,'2012-12-08 17:58:13','2012-12-08','2012-12-08','2012-12-08 16:58:27',0,0.00000000,NULL,NULL,0,NULL,NULL,0.63000000,0.00000000,0.00000000,0.00000000,5.00000000,5.63000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2012-12-08',NULL,NULL,'crabe',NULL,NULL),(12,'AV1212-0002',1,NULL,NULL,2,NULL,NULL,10,'2012-12-08 18:20:14','2012-12-08','2012-12-08','2012-12-09 17:35:07',1,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,-5.00000000,-5.00000000,2,1,1,3,NULL,NULL,NULL,0,0,'2012-12-08',NULL,NULL,'crabe',NULL,NULL),(13,'FA1212-0011',1,NULL,NULL,0,NULL,NULL,7,'2012-12-09 20:04:19','2012-12-09','2013-02-12','2013-02-12 14:54:37',0,0.00000000,NULL,NULL,0,NULL,NULL,2.74000000,0.00000000,0.00000000,0.00000000,14.00000000,16.74000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2012-12-09',NULL,NULL,'crabe',NULL,NULL),(32,'FA1212-0021',1,NULL,NULL,0,NULL,NULL,1,'2012-12-11 09:34:23','2012-12-11','2013-03-24','2013-03-24 14:54:00',0,0.00000000,NULL,NULL,0,NULL,NULL,90.00000000,0.00000000,0.00000000,0.60000000,520.00000000,610.60000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2012-12-11','This is a comment (private)','This is a comment (public)','crabe',NULL,NULL),(33,'(PROV33)',1,NULL,NULL,0,NULL,NULL,1,'2012-12-11 09:34:23','2012-12-11',NULL,'2012-12-11 08:34:23',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2012-12-11','This is a comment (private)','This is a comment (public)','',NULL,NULL),(55,'FA1212-0009',1,NULL,NULL,0,NULL,NULL,1,'2012-12-11 09:35:51','2012-12-11','2012-12-12','2012-12-12 17:54:19',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2012-12-11','This is a comment (private)','This is a comment (public)','',NULL,NULL),(56,'(PROV56)',1,NULL,NULL,0,NULL,NULL,1,'2012-12-11 09:35:52','2012-12-11',NULL,'2012-12-11 08:35:52',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2012-12-11','This is a comment (private)','This is a comment (public)','',NULL,NULL),(78,'(PROV78)',1,NULL,NULL,0,NULL,NULL,1,'2012-12-11 09:36:41','2012-12-11',NULL,'2012-12-11 08:36:41',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2012-12-11','This is a comment (private)','This is a comment (public)','',NULL,NULL),(79,'(PROV79)',1,NULL,NULL,0,NULL,NULL,1,'2012-12-11 09:36:41','2012-12-11',NULL,'2012-12-19 16:56:16',0,0.00000000,NULL,NULL,0,NULL,NULL,7.60000000,0.66000000,-3.00000000,0.00000000,50.00000000,55.26000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2012-12-11','This is a comment (private)','This is a comment (public)','',NULL,NULL),(121,'(PROV121)',1,NULL,NULL,0,NULL,NULL,1,'2012-12-19 18:39:38','2012-12-19',NULL,'2012-12-19 17:39:38',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2012-12-19','This is a comment (private)','This is a comment (public)','',NULL,NULL),(122,'(PROV122)',1,NULL,NULL,0,NULL,NULL,1,'2012-12-19 18:39:38','2012-12-19',NULL,'2012-12-19 17:39:38',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2012-12-19','This is a comment (private)','This is a comment (public)','',NULL,NULL),(146,'(PROV146)',1,NULL,NULL,0,NULL,NULL,1,'2012-12-19 18:48:41','2012-12-19',NULL,'2013-01-18 14:51:01',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2012-12-19','This is a comment (private)','This is a comment (public)','crabe',NULL,NULL),(147,'(PROV147)',1,NULL,NULL,0,NULL,NULL,1,'2012-12-19 18:48:42','2012-12-19',NULL,'2012-12-19 17:48:42',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2012-12-19','This is a comment (private)','This is a comment (public)','',NULL,NULL),(148,'FS1301-0001',1,NULL,NULL,0,NULL,NULL,1,'2013-01-19 18:22:48','2013-01-19','2013-01-19','2013-01-19 17:22:48',0,0.00000000,NULL,NULL,0,NULL,NULL,0.63000000,0.00000000,0.00000000,0.00000000,5.00000000,5.63000000,1,1,1,NULL,NULL,NULL,NULL,0,1,'2013-01-19',NULL,NULL,'',NULL,NULL),(149,'(PROV149)',1,NULL,NULL,0,NULL,NULL,1,'2013-01-19 18:30:05','2013-01-19',NULL,'2013-02-13 14:02:53',0,0.00000000,NULL,NULL,0,NULL,NULL,1.96000000,0.00000000,0.00000000,0.00000000,10.00000000,11.96000000,0,1,NULL,NULL,NULL,NULL,NULL,0,0,'2013-01-19',NULL,NULL,'crabe',NULL,NULL),(150,'FA6801-0010',1,NULL,NULL,0,NULL,NULL,1,'2013-01-19 18:31:10','2013-01-19','2013-01-19','2013-01-19 17:31:10',1,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,2,1,1,NULL,NULL,NULL,NULL,0,1,'2013-01-19',NULL,NULL,'',NULL,NULL),(151,'FS1301-0002',1,NULL,NULL,0,NULL,NULL,1,'2013-01-19 18:31:58','2013-01-19','2013-01-19','2013-01-19 17:31:58',1,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,2,1,1,NULL,NULL,NULL,NULL,0,1,'2013-01-19',NULL,NULL,'',NULL,NULL),(152,'FA1302-0012',1,NULL,NULL,0,NULL,NULL,18,'2013-02-17 16:26:53','2013-02-17','2013-02-17','2013-02-17 15:27:00',0,0.00000000,NULL,NULL,0,NULL,NULL,1.96000000,0.00000000,0.00000000,0.00000000,10.00000000,11.96000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2013-02-17',NULL,NULL,'crabe',NULL,NULL),(153,'(PROV153)',1,NULL,NULL,0,NULL,NULL,1,'2013-02-17 18:22:24','2013-02-17',NULL,'2013-02-17 17:22:24',0,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2013-02-17',NULL,NULL,'crabe',NULL,NULL),(154,'(PROV154)',1,NULL,NULL,0,NULL,NULL,1,'2013-02-17 18:24:21','2013-02-17',NULL,'2013-02-17 17:24:21',0,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2013-02-17',NULL,NULL,'crabe',NULL,NULL),(155,'(PROV155)',1,NULL,NULL,0,NULL,NULL,1,'2013-02-17 18:30:30','2013-02-17',NULL,'2013-02-17 17:30:30',0,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2013-02-17',NULL,NULL,'crabe',NULL,NULL),(156,'(PROV156)',1,NULL,NULL,0,NULL,NULL,1,'2013-02-17 18:37:01','2013-02-17',NULL,'2013-02-17 17:37:01',0,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2013-02-17',NULL,NULL,'crabe',NULL,NULL),(157,'(PROV157)',1,NULL,NULL,0,NULL,NULL,18,'2013-02-17 18:39:23','2013-02-17',NULL,'2013-02-17 17:39:23',0,0.00000000,NULL,NULL,0,NULL,NULL,3.22000000,0.00000000,0.00000000,0.00000000,20.00000000,23.22000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2013-02-17',NULL,NULL,'crabe',NULL,NULL),(158,'FA1307-0013',1,NULL,NULL,0,NULL,NULL,12,'2013-03-06 16:43:37','2013-07-18','2013-03-23','2013-03-23 17:23:03',0,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2013-07-18',NULL,NULL,'crabe',NULL,NULL),(159,'FA1407-0014',1,NULL,NULL,0,NULL,NULL,12,'2013-03-06 16:44:12','2014-07-18','2013-03-06','2013-03-06 15:44:12',0,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,11.00000000,11.00000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2014-07-18',NULL,NULL,'',NULL,NULL),(160,'FA1507-0015',1,NULL,NULL,0,NULL,NULL,12,'2013-03-06 16:47:48','2015-07-18','2013-03-06','2013-03-06 15:47:48',0,0.00000000,NULL,NULL,0,NULL,NULL,1.11000000,0.00000000,0.00000000,0.00000000,8.89000000,10.00000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2015-07-18',NULL,NULL,'',NULL,NULL),(161,'FA1607-0016',1,NULL,NULL,0,NULL,NULL,12,'2013-03-06 16:48:16','2016-07-18','2013-03-06','2013-03-06 15:48:16',0,0.00000000,NULL,NULL,0,NULL,NULL,2.22000000,0.00000000,0.00000000,0.00000000,17.78000000,20.00000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2016-07-18',NULL,NULL,'',NULL,NULL),(162,'(PROV162)',1,NULL,NULL,0,'fdfs',NULL,23,'2013-03-08 10:02:54','2013-03-08',NULL,'2013-03-08 09:02:54',0,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,5.00000000,5.00000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2013-03-08',NULL,NULL,'crabe',NULL,NULL),(184,'(PROV184)',1,NULL,NULL,0,NULL,NULL,1,'2013-03-09 18:19:36','2013-03-09',NULL,'2013-03-09 17:19:36',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2013-03-09','This is a comment (private)','This is a comment (public)','',NULL,NULL),(185,'(PROV185)',1,NULL,NULL,0,NULL,NULL,1,'2013-03-09 18:19:36','2013-03-09',NULL,'2013-03-09 17:19:36',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2013-03-09','This is a comment (private)','This is a comment (public)','',NULL,NULL),(186,'(PROV186)',1,NULL,NULL,0,NULL,NULL,1,'2013-03-09 18:26:56','2013-03-09',NULL,'2013-03-09 17:26:56',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2013-03-09','This is a comment (private)','This is a comment (public)','',NULL,NULL),(187,'(PROV187)',1,NULL,NULL,0,NULL,NULL,1,'2013-03-09 18:26:56','2013-03-09',NULL,'2013-03-09 17:26:56',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2013-03-09','This is a comment (private)','This is a comment (public)','',NULL,NULL),(206,'(PROV206)',1,NULL,NULL,0,NULL,NULL,1,'2013-03-09 18:34:05','2013-03-09',NULL,'2013-03-09 17:34:05',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2013-03-09','This is a comment (private)','This is a comment (public)','',NULL,NULL),(207,'(PROV207)',1,NULL,NULL,0,NULL,NULL,1,'2013-03-09 18:34:05','2013-03-09',NULL,'2013-03-10 14:45:36',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2013-03-09','This is a comment (private)','This is a comment (public)','generic_invoice_odt:/var/www/dolibarrnew/documents/doctemplates/invoices/template_invoice.odt',NULL,NULL),(208,'FA1303-0017',1,NULL,NULL,0,NULL,NULL,26,'2013-03-10 15:58:11','2013-03-10','2013-03-10','2013-03-10 14:58:34',0,0.00000000,NULL,NULL,0,NULL,NULL,1.25000000,0.00000000,0.00000000,0.00000000,10.00000000,11.25000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2013-03-10',NULL,NULL,'generic_invoice_odt:/var/www/dolibarrnew/documents/doctemplates/invoices/template_invoice.odt',NULL,NULL),(209,'FA1303-0018',1,NULL,NULL,0,NULL,NULL,19,'2013-03-19 09:37:51','2013-03-19','2013-03-19','2013-03-19 08:38:10',0,0.00000000,NULL,NULL,0,NULL,NULL,-1.25000000,0.00000000,0.00000000,0.00000000,10.00000000,8.75000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2013-03-19',NULL,NULL,'crabe',NULL,NULL),(210,'FA1107-0019',1,NULL,NULL,0,NULL,NULL,10,'2013-03-20 14:30:11','2011-07-10','2013-03-20','2013-03-20 13:30:11',0,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,10.00000000,10.00000000,1,1,1,NULL,NULL,NULL,NULL,1,0,'2011-07-10',NULL,NULL,'',NULL,NULL),(211,'FA1303-0020',1,NULL,NULL,0,NULL,NULL,19,'2013-03-22 09:40:10','2013-03-22','2013-03-23','2013-03-23 16:31:13',0,0.00000000,NULL,NULL,0,NULL,NULL,0.60000000,0.00000000,0.00000000,0.40000000,110.00000000,111.00000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2013-03-22',NULL,NULL,'crabe',NULL,NULL),(239,'(PROV239)',1,NULL,NULL,0,NULL,NULL,1,'2014-04-05 16:20:58','2014-04-05',NULL,'2014-04-05 14:20:58',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2014-04-05','This is a comment (private)','This is a comment (public)','',NULL,NULL),(240,'(PROV240)',1,NULL,NULL,0,NULL,NULL,1,'2014-04-05 16:20:58','2014-04-05',NULL,'2014-04-05 14:20:58',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2014-04-05','This is a comment (private)','This is a comment (public)','',NULL,NULL),(278,'(PROV278)',1,NULL,NULL,0,NULL,NULL,1,'2014-04-05 16:22:42','2014-04-05',NULL,'2014-04-05 14:22:42',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2014-04-05','This is a comment (private)','This is a comment (public)','',NULL,NULL),(279,'(PROV279)',1,NULL,NULL,0,NULL,NULL,1,'2014-04-05 16:22:42','2014-04-05',NULL,'2014-04-05 14:22:42',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2014-04-05','This is a comment (private)','This is a comment (public)','',NULL,NULL),(317,'(PROV317)',1,NULL,NULL,0,NULL,NULL,1,'2014-04-05 16:27:28','2014-04-05',NULL,'2014-04-05 14:27:28',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2014-04-05','This is a comment (private)','This is a comment (public)','',NULL,NULL),(318,'(PROV318)',1,NULL,NULL,0,NULL,NULL,1,'2014-04-05 16:27:28','2014-04-05',NULL,'2014-04-05 14:27:28',0,0.00000000,NULL,NULL,0,NULL,NULL,0.24000000,0.00000000,0.00000000,0.00000000,2.48000000,2.72000000,0,1,NULL,NULL,NULL,NULL,NULL,1,0,'2014-04-05','This is a comment (private)','This is a comment (public)','',NULL,NULL);
/*!40000 ALTER TABLE `llx_facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_extrafields`
--

DROP TABLE IF EXISTS `llx_facture_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facture_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_facture_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facture_extrafields`
--

LOCK TABLES `llx_facture_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_facture_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_facture_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_fourn`
--

DROP TABLE IF EXISTS `llx_facture_fourn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facture_fourn` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(30) DEFAULT NULL,
  `ref_supplier` varchar(30) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(30) DEFAULT NULL,
  `type` smallint(6) NOT NULL DEFAULT '0',
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `datef` date DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `libelle` varchar(255) DEFAULT NULL,
  `paye` smallint(6) NOT NULL DEFAULT '0',
  `amount` double(24,8) NOT NULL DEFAULT '0.00000000',
  `remise` double(24,8) DEFAULT '0.00000000',
  `close_code` varchar(16) DEFAULT NULL,
  `close_note` varchar(128) DEFAULT NULL,
  `tva` double(24,8) DEFAULT '0.00000000',
  `localtax1` double(24,8) DEFAULT '0.00000000',
  `localtax2` double(24,8) DEFAULT '0.00000000',
  `total` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_facture_source` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `fk_cond_reglement` int(11) DEFAULT NULL,
  `fk_mode_reglement` int(11) DEFAULT NULL,
  `date_lim_reglement` date DEFAULT NULL,
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_facture_fourn_ref_supplier` (`ref_supplier`,`fk_soc`,`entity`),
  UNIQUE KEY `uk_facture_fourn_ref` (`ref`,`entity`),
  KEY `idx_facture_fourn_date_lim_reglement` (`date_lim_reglement`),
  KEY `idx_facture_fourn_fk_soc` (`fk_soc`),
  KEY `idx_facture_fourn_fk_user_author` (`fk_user_author`),
  KEY `idx_facture_fourn_fk_user_valid` (`fk_user_valid`),
  KEY `idx_facture_fourn_fk_projet` (`fk_projet`),
  CONSTRAINT `fk_facture_fourn_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_facture_fourn_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_facture_fourn_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_facture_fourn_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facture_fourn`
--

LOCK TABLES `llx_facture_fourn` WRITE;
/*!40000 ALTER TABLE `llx_facture_fourn` DISABLE KEYS */;
INSERT INTO `llx_facture_fourn` VALUES (1,NULL,'aaa',1,NULL,0,17,'2011-08-04 22:21:18','2011-08-04','2012-12-09 19:03:52','',0,0.00000000,0.00000000,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,16.00000000,3.14000000,19.14000000,0,1,NULL,NULL,NULL,1,NULL,NULL,'','',NULL,NULL,NULL),(16,NULL,'FR70813',1,NULL,0,1,'2012-12-19 15:24:11','2003-04-11','2013-02-10 20:55:42','OVH FR70813',0,0.00000000,0.00000000,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,829.00000000,162.48000000,991.48000000,1,1,1,NULL,NULL,1,NULL,'2003-04-11','','',NULL,NULL,NULL),(17,NULL,'FR81385',1,NULL,0,1,'2013-02-13 17:19:35','2003-06-04','2013-02-13 16:19:35','OVH FR81385',0,0.00000000,0.00000000,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,26.00000000,5.10000000,31.10000000,0,1,NULL,NULL,NULL,1,NULL,'2003-06-04','','',NULL,NULL,NULL),(18,NULL,'FR81385',1,NULL,0,2,'2013-02-13 17:20:25','2003-06-04','2013-02-13 16:20:25','OVH FR81385',0,0.00000000,0.00000000,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,26.00000000,5.10000000,31.10000000,0,1,NULL,NULL,NULL,1,NULL,'2003-06-04','','',NULL,NULL,NULL),(19,NULL,'FR813852',1,NULL,0,2,'2013-03-16 17:59:02','2013-03-16','2013-03-16 16:59:11','OVH FR81385',0,0.00000000,0.00000000,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,26.00000000,5.10000000,31.10000000,0,1,NULL,NULL,NULL,1,NULL,NULL,'','',NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_facture_fourn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_fourn_det`
--

DROP TABLE IF EXISTS `llx_facture_fourn_det`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facture_fourn_det` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture_fourn` int(11) NOT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `ref` varchar(50) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `description` text,
  `pu_ht` double(24,8) DEFAULT NULL,
  `pu_ttc` double(24,8) DEFAULT NULL,
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `tva_tx` double(6,3) DEFAULT NULL,
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax1_type` varchar(10) NOT NULL DEFAULT '0',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `localtax2_type` varchar(10) NOT NULL DEFAULT '0',
  `total_ht` double(24,8) DEFAULT NULL,
  `tva` double(24,8) DEFAULT NULL,
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT NULL,
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `info_bits` int(11) NOT NULL DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  `fk_code_ventilation` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rowid`),
  KEY `idx_facture_fourn_det_fk_facture` (`fk_facture_fourn`),
  CONSTRAINT `fk_facture_fourn_det_fk_facture` FOREIGN KEY (`fk_facture_fourn`) REFERENCES `llx_facture_fourn` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facture_fourn_det`
--

LOCK TABLES `llx_facture_fourn_det` WRITE;
/*!40000 ALTER TABLE `llx_facture_fourn_det` DISABLE KEYS */;
INSERT INTO `llx_facture_fourn_det` VALUES (1,1,NULL,NULL,NULL,'aaa',10.00000000,11.96000000,1.6,0,19.600,0.000,'',0.000,'',16.00000000,3.14000000,0.00000000,0.00000000,19.14000000,0,NULL,NULL,0,NULL,0),(44,16,NULL,NULL,NULL,'<strong>ref :sd.loc.sp.512.6</strong><br>6 mois - Location de SuperPlan avec la connexion 512kbs<br>Du 11/04/2003 &agrave; 11/10/2003',414.00000000,495.14400000,1,0,19.600,0.000,'',0.000,'',414.00000000,81.14000000,0.00000000,0.00000000,495.14000000,0,NULL,NULL,0,NULL,0),(45,16,NULL,NULL,NULL,'<strong>ref :sd.loc.sp.512.6</strong><br>6 mois - Location de SuperPlan avec la connexion 512kbs<br>Du 11/10/2003 &agrave; 11/04/2004',414.00000000,495.14400000,1,0,19.600,0.000,'',0.000,'',414.00000000,81.14000000,0.00000000,0.00000000,495.14000000,0,NULL,NULL,0,NULL,0),(46,16,NULL,NULL,NULL,'<strong>ref :sd.installation.annuel</strong><br>Frais de mise en service d\'un serveur dédié pour un paiement annuel<br>',1.00000000,1.19600000,1,0,19.600,0.000,'',0.000,'',1.00000000,0.20000000,0.00000000,0.00000000,1.20000000,0,NULL,NULL,0,NULL,0),(47,17,NULL,NULL,NULL,'<strong>ref :bk.full250.creation</strong><br>Création du compte backup ftp 250Mo.<br>',1.00000000,1.19600000,1,0,19.600,0.000,'',0.000,'',1.00000000,0.20000000,0.00000000,0.00000000,1.20000000,0,NULL,NULL,0,NULL,0),(48,17,NULL,NULL,NULL,'<strong>ref :bk.full250.12</strong><br>Redevance pour un backup de 250Mo sur 12 mois<br>',25.00000000,29.90000000,1,0,19.600,0.000,'',0.000,'',25.00000000,4.90000000,0.00000000,0.00000000,29.90000000,0,NULL,NULL,0,NULL,0),(49,18,NULL,NULL,NULL,'<strong>ref :bk.full250.creation</strong><br>Création du compte backup ftp 250Mo.<br>',1.00000000,1.19600000,1,0,19.600,0.000,'',0.000,'',1.00000000,0.20000000,0.00000000,0.00000000,1.20000000,0,NULL,NULL,0,NULL,0),(50,18,NULL,NULL,NULL,'<strong>ref :bk.full250.12</strong><br>Redevance pour un backup de 250Mo sur 12 mois<br>',25.00000000,29.90000000,1,0,19.600,0.000,'',0.000,'',25.00000000,4.90000000,0.00000000,0.00000000,29.90000000,0,NULL,NULL,0,NULL,0),(51,19,NULL,NULL,NULL,'<strong>ref :bk.full250.creation</strong><br>Création du compte backup ftp 250Mo.<br>',1.00000000,1.19600000,1,0,19.600,0.000,'0',0.000,'0',1.00000000,0.20000000,0.00000000,0.00000000,1.20000000,0,NULL,NULL,0,NULL,0),(52,19,NULL,NULL,NULL,'<strong>ref :bk.full250.12</strong><br>Redevance pour un backup de 250Mo sur 12 mois<br>',25.00000000,29.90000000,1,0,19.600,0.000,'0',0.000,'0',25.00000000,4.90000000,0.00000000,0.00000000,29.90000000,0,NULL,NULL,0,NULL,0);
/*!40000 ALTER TABLE `llx_facture_fourn_det` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_fourn_extrafields`
--

DROP TABLE IF EXISTS `llx_facture_fourn_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facture_fourn_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_facture_fourn_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facture_fourn_extrafields`
--

LOCK TABLES `llx_facture_fourn_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_facture_fourn_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_facture_fourn_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_rec`
--

DROP TABLE IF EXISTS `llx_facture_rec`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facture_rec` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(50) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `amount` double(24,8) NOT NULL DEFAULT '0.00000000',
  `remise` double DEFAULT '0',
  `remise_percent` double DEFAULT '0',
  `remise_absolue` double DEFAULT '0',
  `tva` double(24,8) DEFAULT '0.00000000',
  `localtax1` double(24,8) DEFAULT '0.00000000',
  `localtax2` double(24,8) DEFAULT '0.00000000',
  `total` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `fk_cond_reglement` int(11) DEFAULT '0',
  `fk_mode_reglement` int(11) DEFAULT '0',
  `date_lim_reglement` date DEFAULT NULL,
  `note_private` text,
  `note_public` text,
  `last_gen` varchar(7) DEFAULT NULL,
  `unit_frequency` varchar(2) DEFAULT 'd',
  `date_when` datetime DEFAULT NULL,
  `date_last_gen` datetime DEFAULT NULL,
  `nb_gen_done` int(11) DEFAULT NULL,
  `nb_gen_max` int(11) DEFAULT NULL,
  `frequency` int(11) DEFAULT NULL,
  `usenewprice` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_facture_rec_uk_titre` (`titre`,`entity`),
  KEY `idx_facture_rec_fk_soc` (`fk_soc`),
  KEY `idx_facture_rec_fk_user_author` (`fk_user_author`),
  KEY `idx_facture_rec_fk_projet` (`fk_projet`),
  CONSTRAINT `fk_facture_rec_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_facture_rec_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_facture_rec_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facture_rec`
--

LOCK TABLES `llx_facture_rec` WRITE;
/*!40000 ALTER TABLE `llx_facture_rec` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_facture_rec` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facturedet`
--

DROP TABLE IF EXISTS `llx_facturedet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facturedet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL,
  `fk_parent_line` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `description` text,
  `tva_tx` double(6,3) DEFAULT NULL,
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax1_type` varchar(10) NOT NULL DEFAULT '0',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `localtax2_type` varchar(10) NOT NULL DEFAULT '0',
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `fk_remise_except` int(11) DEFAULT NULL,
  `subprice` double(24,8) DEFAULT NULL,
  `price` double(24,8) DEFAULT NULL,
  `total_ht` double(24,8) DEFAULT NULL,
  `total_tva` double(24,8) DEFAULT NULL,
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT NULL,
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `info_bits` int(11) DEFAULT '0',
  `fk_product_fournisseur_price` int(11) DEFAULT NULL,
  `buy_price_ht` double(24,8) DEFAULT '0.00000000',
  `fk_code_ventilation` int(11) NOT NULL DEFAULT '0',
  `special_code` int(10) unsigned DEFAULT '0',
  `rang` int(11) DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_fk_remise_except` (`fk_remise_except`,`fk_facture`),
  KEY `idx_facturedet_fk_facture` (`fk_facture`),
  KEY `idx_facturedet_fk_product` (`fk_product`),
  CONSTRAINT `fk_facturedet_fk_facture` FOREIGN KEY (`fk_facture`) REFERENCES `llx_facture` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=1622 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facturedet`
--

LOCK TABLES `llx_facturedet` WRITE;
/*!40000 ALTER TABLE `llx_facturedet` DISABLE KEYS */;
INSERT INTO `llx_facturedet` VALUES (1,1,NULL,4,NULL,'',0.000,0.000,'',0.000,'',2,0,0,NULL,0.01000000,0.01000000,0.02000000,0.00000000,0.00000000,0.00000000,0.02000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(3,2,NULL,3,NULL,'Service S1',0.000,0.000,'',0.000,'',1,10,4,NULL,40.00000000,36.00000000,36.00000000,0.00000000,0.00000000,0.00000000,36.00000000,1,'2010-07-10 00:00:00',NULL,0,NULL,0.00000000,0,0,2,NULL),(4,2,NULL,NULL,NULL,'Abonnement annuel assurance',1.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,10.00000000,10.00000000,0.10000000,0.00000000,0.00000000,10.10000000,0,'2010-07-10 00:00:00','2011-07-10 00:00:00',0,NULL,0.00000000,0,0,3,NULL),(11,3,NULL,4,NULL,'afsdfsdfsdfsdf',0.000,0.000,'',0.000,'',1,0,0,NULL,5.00000000,5.00000000,5.00000000,0.00000000,0.00000000,0.00000000,5.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,0,NULL),(12,3,NULL,NULL,NULL,'dfdfd',0.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(13,5,NULL,4,NULL,'Decapsuleur',12.500,0.000,'',0.000,'',1,0,0,NULL,5.00000000,5.00000000,5.00000000,0.63000000,0.00000000,0.00000000,5.63000000,0,NULL,NULL,0,NULL,0.00000000,0,0,0,NULL),(14,6,NULL,4,NULL,'Decapsuleur',19.600,0.000,'',0.000,'',1,0,0,NULL,5.00000000,5.00000000,5.00000000,0.98000000,0.00000000,0.00000000,5.98000000,0,NULL,NULL,0,NULL,0.00000000,0,0,0,NULL),(21,8,NULL,NULL,NULL,'dddd',0.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(22,9,NULL,NULL,NULL,'ggg',19.600,0.000,'',0.000,'',1,0,0,NULL,10.00000000,10.00000000,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(23,10,NULL,4,NULL,'',12.500,0.000,'',0.000,'',1,0,0,NULL,-5.00000000,NULL,-5.00000000,-0.63000000,0.00000000,0.00000000,-5.63000000,0,NULL,NULL,0,NULL,12.00000000,0,0,1,NULL),(24,10,NULL,1,NULL,'A beatifull pink dress\r\nlkm',0.000,0.000,'',0.000,'',1,0,0,NULL,-6.00000000,NULL,-6.00000000,0.00000000,0.00000000,0.00000000,-6.00000000,0,NULL,NULL,0,0,0.00000000,0,0,2,NULL),(25,11,NULL,4,NULL,'jljk',12.500,0.000,'',0.000,'',1,0,0,NULL,5.00000000,NULL,5.00000000,0.63000000,0.00000000,0.00000000,5.63000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(26,12,NULL,1,NULL,'A beatifull pink dress\r\nhfghf',0.000,0.000,'',0.000,'',1,0,0,NULL,-5.00000000,NULL,-5.00000000,0.00000000,0.00000000,0.00000000,-5.00000000,0,NULL,NULL,0,0,0.00000000,0,0,1,NULL),(27,13,NULL,NULL,NULL,'gdfgdf',19.600,0.000,'',0.000,'',1.4,0,0,NULL,10.00000000,NULL,14.00000000,2.74000000,0.00000000,0.00000000,16.74000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(137,33,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(138,33,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(256,55,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(257,55,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(258,56,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(259,56,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(377,78,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(378,78,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(385,79,NULL,NULL,NULL,'hfghfg',10.000,1.400,'',-15.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,1.00000000,0.14000000,-1.50000000,9.64000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(386,79,NULL,NULL,NULL,'gdfg',15.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,1.50000000,0.00000000,0.00000000,11.50000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(387,79,NULL,NULL,NULL,'fdsf',21.000,5.200,'',-15.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,2.10000000,0.52000000,-1.50000000,11.12000000,0,NULL,NULL,0,NULL,0.00000000,0,0,3,NULL),(388,79,NULL,NULL,NULL,'ghfgh',15.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,1.50000000,0.00000000,0.00000000,11.50000000,0,NULL,NULL,0,NULL,0.00000000,0,0,4,NULL),(389,79,NULL,NULL,NULL,'ghfgh',15.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,1.50000000,0.00000000,0.00000000,11.50000000,0,NULL,NULL,0,NULL,0.00000000,0,0,5,NULL),(618,121,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(619,121,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(620,122,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(621,122,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(749,146,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(750,146,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(751,147,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(752,147,NULL,NULL,NULL,'Desc',10.000,0.000,'',0.000,'',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(753,13,NULL,2,NULL,'(Pays d\'origine: Albanie)',0.000,0.000,'',0.000,'',1,0,0,NULL,0.00000000,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,0,0.00000000,0,0,2,NULL),(754,148,NULL,11,NULL,'hfghf',0.000,0.000,'',0.000,'',1,0,0,NULL,0.00000000,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(755,148,NULL,4,NULL,'Decapsuleur',12.500,0.000,'',0.000,'',1,0,0,NULL,5.00000000,NULL,5.00000000,0.63000000,0.00000000,0.00000000,5.63000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(756,149,NULL,5,NULL,'aaaa',19.600,0.000,'',0.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(757,150,NULL,2,NULL,'Product P1',12.500,0.000,'',0.000,'',1,0,0,NULL,0.00000000,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(758,151,NULL,2,NULL,'Product P1',12.500,0.000,'',0.000,'',1,0,0,NULL,0.00000000,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(759,152,NULL,NULL,NULL,'gdfgfd',19.600,0.000,'',0.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(760,153,NULL,NULL,NULL,'gfdgdf',0.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(761,154,NULL,NULL,NULL,'',0.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,0,NULL),(762,155,NULL,NULL,NULL,'',0.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,0,NULL),(763,156,NULL,NULL,NULL,'gfdgdf',0.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,0,NULL),(764,157,NULL,NULL,NULL,'gfdg',19.600,0.000,'',0.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,0,NULL,NULL,0,NULL,0.00000000,0,0,0,NULL),(765,157,NULL,4,NULL,'Decapsuleur',12.500,0.000,'',0.000,'',1,0,0,NULL,5.00000000,NULL,5.00000000,0.63000000,0.00000000,0.00000000,5.63000000,0,NULL,NULL,0,NULL,0.00000000,0,0,0,NULL),(766,157,NULL,4,NULL,'eeee',12.500,0.000,'',0.000,'',1,0,0,NULL,5.00000000,NULL,5.00000000,0.63000000,0.00000000,0.00000000,5.63000000,0,NULL,NULL,0,NULL,0.00000000,0,0,0,NULL),(768,32,NULL,NULL,NULL,'mlml',18.000,0.000,'',0.000,'',1,0,0,NULL,100.00000000,NULL,100.00000000,18.00000000,0.00000000,0.00000000,118.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,3,NULL),(769,32,NULL,NULL,NULL,'mlkml',18.000,0.000,'',0.000,'',1,0,0,NULL,400.00000000,NULL,400.00000000,72.00000000,0.00000000,0.00000000,472.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,4,NULL),(770,158,NULL,NULL,NULL,'Adhésion/cotisation 2013',0.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,1,'2013-07-18 00:00:00','2014-07-17 00:00:00',0,NULL,0.00000000,0,0,1,NULL),(771,159,NULL,NULL,NULL,'Adhésion/cotisation 2014',0.000,0.000,'',0.000,'',1,0,0,NULL,11.00000000,NULL,11.00000000,0.00000000,0.00000000,0.00000000,11.00000000,1,'2014-07-18 00:00:00','2015-07-17 00:00:00',0,NULL,0.00000000,0,0,1,NULL),(772,160,NULL,NULL,NULL,'Adhésion/cotisation 2015',12.500,0.000,'',0.000,'',1,0,0,NULL,8.88889000,NULL,8.89000000,1.11000000,0.00000000,0.00000000,10.00000000,1,'2015-07-18 00:00:00','2016-07-17 00:00:00',0,NULL,0.00000000,0,0,1,NULL),(773,161,NULL,NULL,NULL,'Adhésion/cotisation 2016',12.500,0.000,'',0.000,'',1,0,0,NULL,17.77778000,NULL,17.78000000,2.22000000,0.00000000,0.00000000,20.00000000,1,'2016-07-18 00:00:00','2017-07-17 00:00:00',0,NULL,0.00000000,0,0,1,NULL),(774,162,NULL,NULL,NULL,'fdsfs',0.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,NULL,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,3,NULL),(775,162,NULL,NULL,NULL,'fsdfsf',0.000,0.000,'',0.000,'',1,0,0,1,-5.00000000,NULL,-5.00000000,0.00000000,0.00000000,0.00000000,-5.00000000,0,NULL,NULL,2,NULL,0.00000000,0,0,1,NULL),(776,32,NULL,NULL,NULL,'fsdfsdfds',0.000,0.000,'0',0.000,'0',1,0,0,NULL,10.00000000,NULL,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,5,NULL),(777,32,NULL,NULL,NULL,'fsdfsdfds',0.000,0.000,'0',0.000,'0',1,0,0,NULL,10.00000000,NULL,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,6,NULL),(779,32,NULL,NULL,NULL,'fsdfds',0.000,0.000,'0',0.000,'0',0,0,0,NULL,0.00000000,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,9,NULL,NULL,0,0,0.00000000,0,0,2,NULL),(780,32,NULL,NULL,NULL,'ffsdf',0.000,0.000,'0',0.000,'0',0,0,0,NULL,0.00000000,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,9,NULL,NULL,0,NULL,0.00000000,0,1790,1,NULL),(781,162,NULL,NULL,NULL,'hh',0.000,0.000,'0',0.000,'0',0,0,0,NULL,0.00000000,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,9,NULL,NULL,0,NULL,0.00000000,0,1790,2,NULL),(899,184,NULL,NULL,NULL,'Desc',10.000,0.000,'0',0.000,'0',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(900,184,NULL,NULL,NULL,'Desc',10.000,0.000,'0',0.000,'0',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(901,185,NULL,NULL,NULL,'Desc',10.000,0.000,'0',0.000,'0',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(902,185,NULL,NULL,NULL,'Desc',10.000,0.000,'0',0.000,'0',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(903,186,NULL,NULL,NULL,'Desc',10.000,0.000,'0',0.000,'0',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(904,186,NULL,NULL,NULL,'Desc',10.000,0.000,'0',0.000,'0',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(905,187,NULL,NULL,NULL,'Desc',10.000,0.000,'0',0.000,'0',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(906,187,NULL,NULL,NULL,'Desc',10.000,0.000,'0',0.000,'0',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(1014,206,NULL,NULL,NULL,'Desc',10.000,0.000,'0',0.000,'0',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(1015,206,NULL,NULL,NULL,'Desc',10.000,0.000,'0',0.000,'0',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(1016,207,NULL,NULL,NULL,'Desc',10.000,0.000,'0',0.000,'0',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(1017,207,NULL,NULL,NULL,'Desc',10.000,0.000,'0',0.000,'0',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(1018,208,NULL,NULL,NULL,'ggdfg',12.500,0.000,'0',0.000,'0',1,0,0,NULL,10.00000000,NULL,10.00000000,1.25000000,0.00000000,0.00000000,11.25000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(1019,209,NULL,NULL,NULL,'hfghgf',12.500,0.000,'0',0.000,'0',1,0,0,2,-10.00000000,NULL,-10.00000000,-1.25000000,0.00000000,0.00000000,-11.25000000,0,NULL,NULL,2,NULL,0.00000000,0,0,-1,NULL),(1020,209,NULL,NULL,NULL,'fsdfsd',0.000,0.000,'0',0.000,'0',1,0,0,NULL,10.00000000,NULL,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,0,NULL),(1021,209,NULL,NULL,NULL,'hfg',0.000,0.000,'0',0.000,'0',1,0,0,NULL,10.00000000,NULL,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(1022,210,NULL,NULL,NULL,'Adhésion/cotisation 2011',0.000,0.000,'0',0.000,'0',1,0,0,NULL,10.00000000,NULL,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,1,'2011-07-10 00:00:00','2012-07-09 00:00:00',0,NULL,0.00000000,0,0,1,NULL),(1023,211,NULL,NULL,NULL,'gfdg',6.000,0.000,'0',0.000,'0',1,0,0,NULL,10.00000000,NULL,10.00000000,0.60000000,0.00000000,0.00000000,10.60000000,0,NULL,NULL,0,0,0.00000000,0,0,1,NULL),(1024,211,NULL,1,NULL,'A beatifull pink dress\nghkhgj',0.000,0.000,'0',0.000,'0',1,0,0,NULL,100.00000000,NULL,100.00000000,0.00000000,0.00000000,0.00000000,100.00000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(1186,239,NULL,NULL,NULL,'Desc',10.000,0.000,'3',0.000,'1',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(1187,239,NULL,NULL,NULL,'Desc',10.000,0.000,'3',0.000,'1',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(1188,240,NULL,NULL,NULL,'Desc',10.000,0.000,'3',0.000,'1',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(1189,240,NULL,NULL,NULL,'Desc',10.000,0.000,'3',0.000,'1',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(1385,278,NULL,NULL,NULL,'Desc',10.000,0.000,'3',0.000,'1',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(1386,278,NULL,NULL,NULL,'Desc',10.000,0.000,'3',0.000,'1',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(1387,279,NULL,NULL,NULL,'Desc',10.000,0.000,'3',0.000,'1',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(1388,279,NULL,NULL,NULL,'Desc',10.000,0.000,'3',0.000,'1',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(1584,317,NULL,NULL,NULL,'Desc',10.000,0.000,'3',0.000,'1',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(1585,317,NULL,NULL,NULL,'Desc',10.000,0.000,'3',0.000,'1',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL),(1586,318,NULL,NULL,NULL,'Desc',10.000,0.000,'3',0.000,'1',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,1,NULL),(1587,318,NULL,NULL,NULL,'Desc',10.000,0.000,'3',0.000,'1',1,0,0,NULL,1.24000000,NULL,1.24000000,0.12000000,0.00000000,0.00000000,1.36000000,0,NULL,NULL,0,NULL,0.00000000,0,0,2,NULL);
/*!40000 ALTER TABLE `llx_facturedet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facturedet_extrafields`
--

DROP TABLE IF EXISTS `llx_facturedet_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facturedet_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_facturedet_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facturedet_extrafields`
--

LOCK TABLES `llx_facturedet_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_facturedet_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_facturedet_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facturedet_rec`
--

DROP TABLE IF EXISTS `llx_facturedet_rec`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facturedet_rec` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL,
  `fk_parent_line` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `product_type` int(11) DEFAULT '0',
  `label` varchar(255) DEFAULT NULL,
  `description` text,
  `tva_tx` double(6,3) DEFAULT NULL,
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax1_type` varchar(10) NOT NULL DEFAULT '0',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `localtax2_type` varchar(10) NOT NULL DEFAULT '0',
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `subprice` double(24,8) DEFAULT NULL,
  `price` double(24,8) DEFAULT NULL,
  `total_ht` double(24,8) DEFAULT NULL,
  `total_tva` double(24,8) DEFAULT NULL,
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT NULL,
  `info_bits` int(11) DEFAULT '0',
  `special_code` int(10) unsigned DEFAULT '0',
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facturedet_rec`
--

LOCK TABLES `llx_facturedet_rec` WRITE;
/*!40000 ALTER TABLE `llx_facturedet_rec` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_facturedet_rec` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_fichinter`
--

DROP TABLE IF EXISTS `llx_fichinter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_fichinter` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) NOT NULL,
  `fk_projet` int(11) DEFAULT '0',
  `fk_contrat` int(11) DEFAULT '0',
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `datei` date DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `duree` double DEFAULT NULL,
  `description` text,
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_fichinter_ref` (`ref`,`entity`),
  KEY `idx_fichinter_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_fichinter_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_fichinter`
--

LOCK TABLES `llx_fichinter` WRITE;
/*!40000 ALTER TABLE `llx_fichinter` DISABLE KEYS */;
INSERT INTO `llx_fichinter` VALUES (1,2,1,0,'FI1007-0001',1,'2010-07-08 23:51:54','2010-07-09 01:42:41','2010-07-09 01:51:54',NULL,1,1,1,51000,NULL,NULL,NULL,'soleil',NULL),(2,1,NULL,0,'FI1007-0002',1,'2012-12-08 13:11:07','2010-07-11 16:07:51',NULL,NULL,1,NULL,0,3600,'ferfrefeferf',NULL,NULL,'soleil',NULL);
/*!40000 ALTER TABLE `llx_fichinter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_fichinter_extrafields`
--

DROP TABLE IF EXISTS `llx_fichinter_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_fichinter_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_ficheinter_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_fichinter_extrafields`
--

LOCK TABLES `llx_fichinter_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_fichinter_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_fichinter_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_fichinterdet`
--

DROP TABLE IF EXISTS `llx_fichinterdet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_fichinterdet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_fichinter` int(11) DEFAULT NULL,
  `fk_parent_line` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `description` text,
  `duree` int(11) DEFAULT NULL,
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  KEY `idx_fichinterdet_fk_fichinter` (`fk_fichinter`),
  CONSTRAINT `fk_fichinterdet_fk_fichinter` FOREIGN KEY (`fk_fichinter`) REFERENCES `llx_fichinter` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_fichinterdet`
--

LOCK TABLES `llx_fichinterdet` WRITE;
/*!40000 ALTER TABLE `llx_fichinterdet` DISABLE KEYS */;
INSERT INTO `llx_fichinterdet` VALUES (1,1,NULL,'2010-07-07 04:00:00','Intervention sur site',3600,0),(2,1,NULL,'2010-07-08 11:00:00','Autre',47400,0),(3,2,NULL,'2010-07-11 05:00:00','Pres',3600,0);
/*!40000 ALTER TABLE `llx_fichinterdet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_filemanager_roots`
--

DROP TABLE IF EXISTS `llx_filemanager_roots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_filemanager_roots` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `rootlabel` varchar(64) DEFAULT NULL,
  `rootpath` text,
  `note` text,
  `position` int(11) DEFAULT NULL,
  `entity` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_filemanager_root` (`rootlabel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_filemanager_roots`
--

LOCK TABLES `llx_filemanager_roots` WRITE;
/*!40000 ALTER TABLE `llx_filemanager_roots` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_filemanager_roots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_holiday`
--

DROP TABLE IF EXISTS `llx_holiday`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_holiday` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `date_create` datetime NOT NULL,
  `description` varchar(255) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `halfday` int(11) DEFAULT '0',
  `statut` int(11) NOT NULL DEFAULT '1',
  `fk_validator` int(11) NOT NULL,
  `date_valid` datetime DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `date_refuse` datetime DEFAULT NULL,
  `fk_user_refuse` int(11) DEFAULT NULL,
  `date_cancel` datetime DEFAULT NULL,
  `fk_user_cancel` int(11) DEFAULT NULL,
  `detail_refuse` varchar(250) DEFAULT NULL,
  `note_private` text,
  `note_public` text,
  `note` text,
  PRIMARY KEY (`rowid`),
  KEY `idx_holiday_fk_user` (`fk_user`),
  KEY `idx_holiday_date_debut` (`date_debut`),
  KEY `idx_holiday_date_fin` (`date_fin`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_holiday`
--

LOCK TABLES `llx_holiday` WRITE;
/*!40000 ALTER TABLE `llx_holiday` DISABLE KEYS */;
INSERT INTO `llx_holiday` VALUES (1,1,'2013-02-17 19:06:35','gdf','2013-02-10','2013-02-11',0,3,1,'2013-02-17 19:06:57',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_holiday` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_holiday_config`
--

DROP TABLE IF EXISTS `llx_holiday_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_holiday_config` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_holiday_config`
--

LOCK TABLES `llx_holiday_config` WRITE;
/*!40000 ALTER TABLE `llx_holiday_config` DISABLE KEYS */;
INSERT INTO `llx_holiday_config` VALUES (1,'userGroup','1'),(2,'lastUpdate','20130301231231'),(3,'nbUser','8'),(4,'delayForRequest','31'),(5,'AlertValidatorDelay','0'),(6,'AlertValidatorSolde','0'),(7,'nbHolidayDeducted','1'),(8,'nbHolidayEveryMonth','2.08334');
/*!40000 ALTER TABLE `llx_holiday_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_holiday_events`
--

DROP TABLE IF EXISTS `llx_holiday_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_holiday_events` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_holiday_name` (`name`,`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_holiday_events`
--

LOCK TABLES `llx_holiday_events` WRITE;
/*!40000 ALTER TABLE `llx_holiday_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_holiday_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_holiday_logs`
--

DROP TABLE IF EXISTS `llx_holiday_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_holiday_logs` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `date_action` datetime NOT NULL,
  `fk_user_action` int(11) NOT NULL,
  `fk_user_update` int(11) NOT NULL,
  `type_action` varchar(255) NOT NULL,
  `prev_solde` varchar(255) NOT NULL,
  `new_solde` varchar(255) NOT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_holiday_logs`
--

LOCK TABLES `llx_holiday_logs` WRITE;
/*!40000 ALTER TABLE `llx_holiday_logs` DISABLE KEYS */;
INSERT INTO `llx_holiday_logs` VALUES (1,'2013-01-17 21:03:15',1,1,'Event : Mise à jour mensuelle','0.00','2.08'),(2,'2013-01-17 21:03:15',1,2,'Event : Mise à jour mensuelle','0.00','2.08'),(3,'2013-01-17 21:03:15',1,3,'Event : Mise à jour mensuelle','0.00','2.08'),(4,'2013-02-01 09:53:26',1,1,'Event : Mise à jour mensuelle','2.08','4.16'),(5,'2013-02-01 09:53:26',1,2,'Event : Mise à jour mensuelle','2.08','4.16'),(6,'2013-02-01 09:53:26',1,3,'Event : Mise à jour mensuelle','2.08','4.16'),(7,'2013-02-01 09:53:26',1,1,'Event : Mise à jour mensuelle','4.17','6.25'),(8,'2013-02-01 09:53:26',1,2,'Event : Mise à jour mensuelle','4.17','6.25'),(9,'2013-02-01 09:53:26',1,3,'Event : Mise à jour mensuelle','4.17','6.25'),(10,'2013-02-01 09:53:26',1,4,'Event : Mise à jour mensuelle','0.00','2.08'),(11,'2013-02-01 09:53:31',1,1,'Event : Mise à jour mensuelle','6.25','8.33'),(12,'2013-02-01 09:53:31',1,2,'Event : Mise à jour mensuelle','6.25','8.33'),(13,'2013-02-01 09:53:31',1,3,'Event : Mise à jour mensuelle','6.25','8.33'),(14,'2013-02-01 09:53:31',1,4,'Event : Mise à jour mensuelle','2.08','4.16'),(15,'2013-02-01 09:53:31',1,1,'Event : Mise à jour mensuelle','8.33','10.41'),(16,'2013-02-01 09:53:31',1,2,'Event : Mise à jour mensuelle','8.33','10.41'),(17,'2013-02-01 09:53:31',1,3,'Event : Mise à jour mensuelle','8.33','10.41'),(18,'2013-02-01 09:53:31',1,4,'Event : Mise à jour mensuelle','4.17','6.25'),(19,'2013-02-01 09:53:33',1,1,'Event : Mise à jour mensuelle','10.42','12.50'),(20,'2013-02-01 09:53:33',1,2,'Event : Mise à jour mensuelle','10.42','12.50'),(21,'2013-02-01 09:53:33',1,3,'Event : Mise à jour mensuelle','10.42','12.50'),(22,'2013-02-01 09:53:33',1,4,'Event : Mise à jour mensuelle','6.25','8.33'),(23,'2013-02-01 09:53:34',1,1,'Event : Mise à jour mensuelle','12.50','14.58'),(24,'2013-02-01 09:53:34',1,2,'Event : Mise à jour mensuelle','12.50','14.58'),(25,'2013-02-01 09:53:34',1,3,'Event : Mise à jour mensuelle','12.50','14.58'),(26,'2013-02-01 09:53:34',1,4,'Event : Mise à jour mensuelle','8.33','10.41'),(27,'2013-02-01 09:53:34',1,1,'Event : Mise à jour mensuelle','14.58','16.66'),(28,'2013-02-01 09:53:34',1,2,'Event : Mise à jour mensuelle','14.58','16.66'),(29,'2013-02-01 09:53:34',1,3,'Event : Mise à jour mensuelle','14.58','16.66'),(30,'2013-02-01 09:53:34',1,4,'Event : Mise à jour mensuelle','10.42','12.50'),(31,'2013-02-01 09:53:36',1,1,'Event : Mise à jour mensuelle','16.67','18.75'),(32,'2013-02-01 09:53:36',1,2,'Event : Mise à jour mensuelle','16.67','18.75'),(33,'2013-02-01 09:53:36',1,3,'Event : Mise à jour mensuelle','16.67','18.75'),(34,'2013-02-01 09:53:36',1,4,'Event : Mise à jour mensuelle','12.50','14.58'),(35,'2013-02-01 09:53:36',1,1,'Event : Mise à jour mensuelle','18.75','20.83'),(36,'2013-02-01 09:53:36',1,2,'Event : Mise à jour mensuelle','18.75','20.83'),(37,'2013-02-01 09:53:36',1,3,'Event : Mise à jour mensuelle','18.75','20.83'),(38,'2013-02-01 09:53:36',1,4,'Event : Mise à jour mensuelle','14.58','16.66'),(39,'2013-02-01 09:53:37',1,1,'Event : Mise à jour mensuelle','20.83','22.91'),(40,'2013-02-01 09:53:37',1,2,'Event : Mise à jour mensuelle','20.83','22.91'),(41,'2013-02-01 09:53:37',1,3,'Event : Mise à jour mensuelle','20.83','22.91'),(42,'2013-02-01 09:53:37',1,4,'Event : Mise à jour mensuelle','16.67','18.75'),(43,'2013-02-01 09:53:37',1,1,'Event : Mise à jour mensuelle','22.92','25.00'),(44,'2013-02-01 09:53:37',1,2,'Event : Mise à jour mensuelle','22.92','25.00'),(45,'2013-02-01 09:53:37',1,3,'Event : Mise à jour mensuelle','22.92','25.00'),(46,'2013-02-01 09:53:37',1,4,'Event : Mise à jour mensuelle','18.75','20.83'),(47,'2013-02-01 09:53:44',1,1,'Event : Mise à jour mensuelle','25.00','27.08'),(48,'2013-02-01 09:53:44',1,2,'Event : Mise à jour mensuelle','25.00','27.08'),(49,'2013-02-01 09:53:44',1,3,'Event : Mise à jour mensuelle','25.00','27.08'),(50,'2013-02-01 09:53:44',1,4,'Event : Mise à jour mensuelle','20.83','22.91'),(51,'2013-02-01 09:53:47',1,1,'Event : Mise à jour mensuelle','27.08','29.16'),(52,'2013-02-01 09:53:47',1,2,'Event : Mise à jour mensuelle','27.08','29.16'),(53,'2013-02-01 09:53:47',1,3,'Event : Mise à jour mensuelle','27.08','29.16'),(54,'2013-02-01 09:53:47',1,4,'Event : Mise à jour mensuelle','22.92','25.00'),(55,'2013-02-01 09:53:47',1,1,'Event : Mise à jour mensuelle','29.17','31.25'),(56,'2013-02-01 09:53:47',1,2,'Event : Mise à jour mensuelle','29.17','31.25'),(57,'2013-02-01 09:53:47',1,3,'Event : Mise à jour mensuelle','29.17','31.25'),(58,'2013-02-01 09:53:47',1,4,'Event : Mise à jour mensuelle','25.00','27.08'),(59,'2013-02-01 09:53:49',1,1,'Event : Mise à jour mensuelle','31.25','33.33'),(60,'2013-02-01 09:53:49',1,2,'Event : Mise à jour mensuelle','31.25','33.33'),(61,'2013-02-01 09:53:49',1,3,'Event : Mise à jour mensuelle','31.25','33.33'),(62,'2013-02-01 09:53:49',1,4,'Event : Mise à jour mensuelle','27.08','29.16'),(63,'2013-02-01 09:53:52',1,1,'Event : Mise à jour mensuelle','33.33','35.41'),(64,'2013-02-01 09:53:52',1,2,'Event : Mise à jour mensuelle','33.33','35.41'),(65,'2013-02-01 09:53:52',1,3,'Event : Mise à jour mensuelle','33.33','35.41'),(66,'2013-02-01 09:53:52',1,4,'Event : Mise à jour mensuelle','29.17','31.25'),(67,'2013-02-01 09:53:52',1,1,'Event : Mise à jour mensuelle','35.42','37.50'),(68,'2013-02-01 09:53:52',1,2,'Event : Mise à jour mensuelle','35.42','37.50'),(69,'2013-02-01 09:53:52',1,3,'Event : Mise à jour mensuelle','35.42','37.50'),(70,'2013-02-01 09:53:52',1,4,'Event : Mise à jour mensuelle','31.25','33.33'),(71,'2013-02-01 09:53:53',1,1,'Event : Mise à jour mensuelle','37.50','39.58'),(72,'2013-02-01 09:53:53',1,2,'Event : Mise à jour mensuelle','37.50','39.58'),(73,'2013-02-01 09:53:53',1,3,'Event : Mise à jour mensuelle','37.50','39.58'),(74,'2013-02-01 09:53:53',1,4,'Event : Mise à jour mensuelle','33.33','35.41'),(75,'2013-02-01 09:53:54',1,1,'Event : Mise à jour mensuelle','39.58','41.66'),(76,'2013-02-01 09:53:54',1,2,'Event : Mise à jour mensuelle','39.58','41.66'),(77,'2013-02-01 09:53:54',1,3,'Event : Mise à jour mensuelle','39.58','41.66'),(78,'2013-02-01 09:53:54',1,4,'Event : Mise à jour mensuelle','35.42','37.50'),(79,'2013-02-01 09:53:54',1,1,'Event : Mise à jour mensuelle','41.67','43.75'),(80,'2013-02-01 09:53:54',1,2,'Event : Mise à jour mensuelle','41.67','43.75'),(81,'2013-02-01 09:53:54',1,3,'Event : Mise à jour mensuelle','41.67','43.75'),(82,'2013-02-01 09:53:54',1,4,'Event : Mise à jour mensuelle','37.50','39.58'),(83,'2013-02-01 09:55:49',1,1,'Event : Mise à jour mensuelle','43.75','45.83'),(84,'2013-02-01 09:55:49',1,2,'Event : Mise à jour mensuelle','43.75','45.83'),(85,'2013-02-01 09:55:49',1,3,'Event : Mise à jour mensuelle','43.75','45.83'),(86,'2013-02-01 09:55:49',1,4,'Event : Mise à jour mensuelle','39.58','41.66'),(87,'2013-02-01 09:55:56',1,1,'Event : Mise à jour mensuelle','45.83','47.91'),(88,'2013-02-01 09:55:56',1,2,'Event : Mise à jour mensuelle','45.83','47.91'),(89,'2013-02-01 09:55:56',1,3,'Event : Mise à jour mensuelle','45.83','47.91'),(90,'2013-02-01 09:55:56',1,4,'Event : Mise à jour mensuelle','41.67','43.75'),(91,'2013-02-01 09:56:01',1,1,'Event : Mise à jour mensuelle','47.92','50.00'),(92,'2013-02-01 09:56:01',1,2,'Event : Mise à jour mensuelle','47.92','50.00'),(93,'2013-02-01 09:56:01',1,3,'Event : Mise à jour mensuelle','47.92','50.00'),(94,'2013-02-01 09:56:01',1,4,'Event : Mise à jour mensuelle','43.75','45.83'),(95,'2013-02-01 09:56:01',1,1,'Event : Mise à jour mensuelle','50.00','52.08'),(96,'2013-02-01 09:56:01',1,2,'Event : Mise à jour mensuelle','50.00','52.08'),(97,'2013-02-01 09:56:01',1,3,'Event : Mise à jour mensuelle','50.00','52.08'),(98,'2013-02-01 09:56:01',1,4,'Event : Mise à jour mensuelle','45.83','47.91'),(99,'2013-02-01 09:56:03',1,1,'Event : Mise à jour mensuelle','52.08','54.16'),(100,'2013-02-01 09:56:03',1,2,'Event : Mise à jour mensuelle','52.08','54.16'),(101,'2013-02-01 09:56:03',1,3,'Event : Mise à jour mensuelle','52.08','54.16'),(102,'2013-02-01 09:56:03',1,4,'Event : Mise à jour mensuelle','47.92','50.00'),(103,'2013-02-01 09:56:03',1,1,'Event : Mise à jour mensuelle','54.17','56.25'),(104,'2013-02-01 09:56:03',1,2,'Event : Mise à jour mensuelle','54.17','56.25'),(105,'2013-02-01 09:56:03',1,3,'Event : Mise à jour mensuelle','54.17','56.25'),(106,'2013-02-01 09:56:03',1,4,'Event : Mise à jour mensuelle','50.00','52.08'),(107,'2013-02-01 09:56:05',1,1,'Event : Mise à jour mensuelle','56.25','58.33'),(108,'2013-02-01 09:56:05',1,2,'Event : Mise à jour mensuelle','56.25','58.33'),(109,'2013-02-01 09:56:05',1,3,'Event : Mise à jour mensuelle','56.25','58.33'),(110,'2013-02-01 09:56:05',1,4,'Event : Mise à jour mensuelle','52.08','54.16'),(111,'2013-02-01 09:56:06',1,1,'Event : Mise à jour mensuelle','58.33','60.41'),(112,'2013-02-01 09:56:06',1,2,'Event : Mise à jour mensuelle','58.33','60.41'),(113,'2013-02-01 09:56:06',1,3,'Event : Mise à jour mensuelle','58.33','60.41'),(114,'2013-02-01 09:56:06',1,4,'Event : Mise à jour mensuelle','54.17','56.25'),(115,'2013-02-01 09:56:06',1,1,'Event : Mise à jour mensuelle','60.42','62.50'),(116,'2013-02-01 09:56:06',1,2,'Event : Mise à jour mensuelle','60.42','62.50'),(117,'2013-02-01 09:56:06',1,3,'Event : Mise à jour mensuelle','60.42','62.50'),(118,'2013-02-01 09:56:06',1,4,'Event : Mise à jour mensuelle','56.25','58.33'),(119,'2013-02-01 09:56:07',1,1,'Event : Mise à jour mensuelle','62.50','64.58'),(120,'2013-02-01 09:56:07',1,2,'Event : Mise à jour mensuelle','62.50','64.58'),(121,'2013-02-01 09:56:07',1,3,'Event : Mise à jour mensuelle','62.50','64.58'),(122,'2013-02-01 09:56:07',1,4,'Event : Mise à jour mensuelle','58.33','60.41'),(123,'2013-02-01 09:56:07',1,1,'Event : Mise à jour mensuelle','64.58','66.66'),(124,'2013-02-01 09:56:07',1,2,'Event : Mise à jour mensuelle','64.58','66.66'),(125,'2013-02-01 09:56:07',1,3,'Event : Mise à jour mensuelle','64.58','66.66'),(126,'2013-02-01 09:56:07',1,4,'Event : Mise à jour mensuelle','60.42','62.50'),(127,'2013-02-01 09:56:50',1,1,'Event : Mise à jour mensuelle','66.67','68.75'),(128,'2013-02-01 09:56:50',1,2,'Event : Mise à jour mensuelle','66.67','68.75'),(129,'2013-02-01 09:56:50',1,3,'Event : Mise à jour mensuelle','66.67','68.75'),(130,'2013-02-01 09:56:50',1,4,'Event : Mise à jour mensuelle','62.50','64.58'),(131,'2013-02-01 09:56:50',1,1,'Event : Mise à jour mensuelle','68.75','70.83'),(132,'2013-02-01 09:56:50',1,2,'Event : Mise à jour mensuelle','68.75','70.83'),(133,'2013-02-01 09:56:50',1,3,'Event : Mise à jour mensuelle','68.75','70.83'),(134,'2013-02-01 09:56:50',1,4,'Event : Mise à jour mensuelle','64.58','66.66'),(135,'2013-02-17 18:49:21',1,1,'Event : Mise à jour mensuelle','70.83','72.91'),(136,'2013-02-17 18:49:21',1,2,'Event : Mise à jour mensuelle','70.83','72.91'),(137,'2013-02-17 18:49:21',1,3,'Event : Mise à jour mensuelle','70.83','72.91'),(138,'2013-02-17 18:49:21',1,4,'Event : Mise à jour mensuelle','66.67','68.75'),(139,'2013-02-17 19:06:57',1,1,'Event : Holiday','72.92','71.92'),(140,'2013-03-01 23:12:31',1,1,'Event : Mise à jour mensuelle','71.92','74.00'),(141,'2013-03-01 23:12:31',1,2,'Event : Mise à jour mensuelle','72.92','75.00'),(142,'2013-03-01 23:12:31',1,3,'Event : Mise à jour mensuelle','72.92','75.00'),(143,'2013-03-01 23:12:31',1,4,'Event : Mise à jour mensuelle','68.75','70.83'),(144,'2013-03-01 23:12:31',1,5,'Event : Mise à jour mensuelle','0.00','2.08');
/*!40000 ALTER TABLE `llx_holiday_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_holiday_users`
--

DROP TABLE IF EXISTS `llx_holiday_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_holiday_users` (
  `fk_user` int(11) NOT NULL,
  `nb_holiday` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`fk_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_holiday_users`
--

LOCK TABLES `llx_holiday_users` WRITE;
/*!40000 ALTER TABLE `llx_holiday_users` DISABLE KEYS */;
INSERT INTO `llx_holiday_users` VALUES (1,74.00334000000001),(2,75.00024000000003),(3,75.00024000000003),(4,70.83356000000002),(5,2.08334);
/*!40000 ALTER TABLE `llx_holiday_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_import_model`
--

DROP TABLE IF EXISTS `llx_import_model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_import_model` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL DEFAULT '0',
  `label` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL,
  `field` text NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_import_model` (`label`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_import_model`
--

LOCK TABLES `llx_import_model` WRITE;
/*!40000 ALTER TABLE `llx_import_model` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_import_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_links`
--

DROP TABLE IF EXISTS `llx_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_links` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datea` datetime NOT NULL,
  `url` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `objecttype` varchar(255) NOT NULL,
  `objectid` int(11) NOT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_links`
--

LOCK TABLES `llx_links` WRITE;
/*!40000 ALTER TABLE `llx_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_livraison`
--

DROP TABLE IF EXISTS `llx_livraison`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_livraison` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_customer` varchar(30) DEFAULT NULL,
  `fk_soc` int(11) NOT NULL,
  `ref_ext` varchar(30) DEFAULT NULL,
  `ref_int` varchar(30) DEFAULT NULL,
  `date_creation` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `date_delivery` date DEFAULT NULL,
  `fk_address` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_livraison_uk_ref` (`ref`,`entity`),
  KEY `idx_livraison_fk_soc` (`fk_soc`),
  KEY `idx_livraison_fk_user_author` (`fk_user_author`),
  KEY `idx_livraison_fk_user_valid` (`fk_user_valid`),
  CONSTRAINT `fk_livraison_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_livraison_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_livraison_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_livraison`
--

LOCK TABLES `llx_livraison` WRITE;
/*!40000 ALTER TABLE `llx_livraison` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_livraison` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_livraisondet`
--

DROP TABLE IF EXISTS `llx_livraisondet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_livraisondet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_livraison` int(11) DEFAULT NULL,
  `fk_origin_line` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `description` text,
  `qty` double DEFAULT NULL,
  `subprice` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  KEY `idx_livraisondet_fk_expedition` (`fk_livraison`),
  CONSTRAINT `fk_livraisondet_fk_livraison` FOREIGN KEY (`fk_livraison`) REFERENCES `llx_livraison` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_livraisondet`
--

LOCK TABLES `llx_livraisondet` WRITE;
/*!40000 ALTER TABLE `llx_livraisondet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_livraisondet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_localtax`
--

DROP TABLE IF EXISTS `llx_localtax`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_localtax` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datep` date DEFAULT NULL,
  `datev` date DEFAULT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `label` varchar(255) DEFAULT NULL,
  `note` text,
  `fk_bank` int(11) DEFAULT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_localtax`
--

LOCK TABLES `llx_localtax` WRITE;
/*!40000 ALTER TABLE `llx_localtax` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_localtax` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_mailing`
--

DROP TABLE IF EXISTS `llx_mailing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_mailing` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `statut` smallint(6) DEFAULT '0',
  `titre` varchar(60) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `sujet` varchar(60) DEFAULT NULL,
  `body` mediumtext,
  `bgcolor` varchar(8) DEFAULT NULL,
  `bgimage` varchar(255) DEFAULT NULL,
  `cible` varchar(60) DEFAULT NULL,
  `nbemail` int(11) DEFAULT NULL,
  `email_from` varchar(160) DEFAULT NULL,
  `email_replyto` varchar(160) DEFAULT NULL,
  `email_errorsto` varchar(160) DEFAULT NULL,
  `tag` varchar(128) DEFAULT NULL,
  `date_creat` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `date_appro` datetime DEFAULT NULL,
  `date_envoi` datetime DEFAULT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_user_appro` int(11) DEFAULT NULL,
  `joined_file1` varchar(255) DEFAULT NULL,
  `joined_file2` varchar(255) DEFAULT NULL,
  `joined_file3` varchar(255) DEFAULT NULL,
  `joined_file4` varchar(255) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_mailing`
--

LOCK TABLES `llx_mailing` WRITE;
/*!40000 ALTER TABLE `llx_mailing` DISABLE KEYS */;
INSERT INTO `llx_mailing` VALUES (3,1,'My commercial emailing',1,'Buy my product','Please buy my product','','',NULL,2,'dolibarr@domain.com','','',NULL,'2010-07-11 13:15:59','2010-07-11 13:46:20',NULL,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL),(4,0,'Copy of My commercial emailing',1,'Buy my product','This is a new &eacute;E&eacute;&eacute;&quot;Mailing content<br />\r\n<br />\r\n<style type=\"text/css\">\r\n<!--\r\na:link {\r\n	color: #E2017A;\r\n}\r\n--></style>\r\n<span style=\"color:#800080;\"><strong>You can adit it with the WYSIWYG editor.</strong></span><br />\r\nIt is\r\n<ul>\r\n	<li>\r\n		Fast</li>\r\n	<li>\r\n		Easy to use</li>\r\n	<li>\r\n		Pretty</li>\r\n</ul>','','',NULL,NULL,'dolibarr@domain.com','','',NULL,'2011-07-18 20:44:33',NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_mailing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_mailing_cibles`
--

DROP TABLE IF EXISTS `llx_mailing_cibles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_mailing_cibles` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_mailing` int(11) NOT NULL,
  `fk_contact` int(11) NOT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `email` varchar(160) NOT NULL,
  `other` varchar(255) DEFAULT NULL,
  `tag` varchar(128) DEFAULT NULL,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  `source_url` varchar(160) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `source_type` varchar(16) DEFAULT NULL,
  `date_envoi` datetime DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_mailing_cibles` (`fk_mailing`,`email`),
  KEY `idx_mailing_cibles_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_mailing_cibles`
--

LOCK TABLES `llx_mailing_cibles` WRITE;
/*!40000 ALTER TABLE `llx_mailing_cibles` DISABLE KEYS */;
INSERT INTO `llx_mailing_cibles` VALUES (1,1,0,'Dupont','Alain','toto@aa.com','Date fin=10/07/2011',NULL,0,'0',NULL,NULL,NULL),(2,2,0,'Swiss customer supplier','','abademail@aa.com','',NULL,0,'0',NULL,NULL,NULL),(3,2,0,'Smith Vick','','vsmith@email.com','',NULL,0,'0',NULL,NULL,NULL),(4,3,0,'Swiss customer supplier','','abademail@aa.com','',NULL,0,'0',NULL,NULL,NULL),(5,3,0,'Smith Vick','','vsmith@email.com','',NULL,0,'0',NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_mailing_cibles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_menu`
--

DROP TABLE IF EXISTS `llx_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_menu` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `menu_handler` varchar(16) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `module` varchar(64) DEFAULT NULL,
  `type` varchar(4) NOT NULL,
  `mainmenu` varchar(100) NOT NULL,
  `fk_menu` int(11) NOT NULL,
  `fk_leftmenu` varchar(24) DEFAULT NULL,
  `fk_mainmenu` varchar(24) DEFAULT NULL,
  `position` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `target` varchar(100) DEFAULT NULL,
  `titre` varchar(255) NOT NULL,
  `langs` varchar(100) DEFAULT NULL,
  `level` smallint(6) DEFAULT NULL,
  `leftmenu` varchar(100) DEFAULT NULL,
  `perms` varchar(255) DEFAULT NULL,
  `enabled` varchar(255) DEFAULT '1',
  `usertype` int(11) NOT NULL DEFAULT '0',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_menu_uk_menu` (`menu_handler`,`fk_menu`,`position`,`url`,`entity`),
  KEY `idx_menu_menuhandler_type` (`menu_handler`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=108509 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_menu`
--

LOCK TABLES `llx_menu` WRITE;
/*!40000 ALTER TABLE `llx_menu` DISABLE KEYS */;
INSERT INTO `llx_menu` VALUES (19289,'all',1,'cashdesk','top','cashdesk',0,NULL,NULL,100,'/cashdesk/index.php?user=__LOGIN__','pointofsale','CashDeskMenu','cashdesk',NULL,NULL,'1','$conf->cashdesk->enabled',0,'2012-12-08 13:11:09'),(87303,'all',1,'filemanager','top','filemanager',0,NULL,NULL,100,'/filemanager/index.php','','FileManager','filemanager@filemanager',NULL,NULL,'$user->rights->filemanager->read','$conf->filemanager->enabled',2,'2013-01-02 20:33:20'),(87422,'smartphone',1,NULL,'top','home',0,NULL,NULL,1,'/index.php?mainmenu=home&amp;leftmenu=','','Home','',-1,'','','1',2,'2013-02-24 18:29:15'),(87423,'smartphone',1,NULL,'top','companies',0,NULL,NULL,2,'/societe/index.php?mainmenu=companies&amp;leftmenu=','','ThirdParties','companies',-1,'','$user->rights->societe->lire || $user->rights->societe->contact->lire','$conf->societe->enabled || $conf->fournisseur->enabled',2,'2013-02-24 18:29:15'),(87424,'smartphone',1,NULL,'top','products',0,NULL,NULL,3,'/product/index.php?mainmenu=products&amp;leftmenu=','','Products/Services','products',-1,'','$user->rights->produit->lire||$user->rights->service->lire','$conf->product->enabled || $conf->service->enabled',0,'2013-02-24 18:29:15'),(87426,'smartphone',1,NULL,'top','commercial',0,NULL,NULL,5,'/comm/index.php?mainmenu=commercial&amp;leftmenu=','','Commercial','commercial',-1,'','$user->rights->societe->lire || $user->rights->societe->contact->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(87427,'smartphone',1,NULL,'top','accountancy',0,NULL,NULL,6,'/compta/index.php?mainmenu=accountancy&amp;leftmenu=','','MenuFinancial','compta',-1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->plancompte->lire || $user->rights->commande->lire || $user->rights->facture->lire','$conf->comptabilite->enabled || $conf->accounting->enabled || $conf->facture->enabled || $conf->deplacement->enabled || $conf->don->enabled',2,'2013-02-24 18:29:15'),(87428,'smartphone',1,NULL,'top','project',0,NULL,NULL,7,'/projet/index.php?mainmenu=project&amp;leftmenu=','','Projects','projects',-1,'','$user->rights->projet->lire','$conf->projet->enabled',0,'2013-02-24 18:29:15'),(87429,'smartphone',1,NULL,'top','tools',0,NULL,NULL,8,'/core/tools.php?mainmenu=tools&amp;leftmenu=','','Tools','other',-1,'','$user->rights->mailing->lire || $user->rights->export->lire || $user->rights->import->run','$conf->mailing->enabled || $conf->export->enabled || $conf->import->enabled',2,'2013-02-24 18:29:15'),(87432,'smartphone',1,NULL,'top','shop',0,NULL,NULL,11,'/boutique/index.php?mainmenu=shop&amp;leftmenu=','','OSCommerce','shop',-1,'','','! empty($conf->boutique->enabled)',0,'2013-02-24 18:29:15'),(87434,'smartphone',1,NULL,'top','members',0,NULL,NULL,15,'/adherents/index.php?mainmenu=members&amp;leftmenu=','','Members','members',-1,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(87435,'smartphone',1,NULL,'top','bank',0,NULL,NULL,6,'/compta/bank/index.php?mainmenu=bank&amp;leftmenu=bank','','MenuBankCash','banks',-1,'','$user->rights->banque->lire || $user->rights->prelevement->bons->lire','$conf->banque->enabled || $conf->prelevement->enabled',2,'2013-02-24 18:29:15'),(87521,'smartphone',1,NULL,'left','home',87422,NULL,NULL,0,'/admin/index.php?leftmenu=setup','','Setup','admin',0,'','','$user->admin',2,'2013-02-24 18:29:15'),(87522,'smartphone',1,NULL,'left','home',87521,NULL,NULL,1,'/admin/company.php?leftmenu=setup','','MenuCompanySetup','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87523,'smartphone',1,NULL,'left','home',87521,NULL,NULL,4,'/admin/ihm.php?leftmenu=setup','','GUISetup','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87524,'smartphone',1,NULL,'left','home',87521,NULL,NULL,2,'/admin/modules.php?leftmenu=setup','','Modules','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87525,'smartphone',1,NULL,'left','home',87521,NULL,NULL,5,'/admin/boxes.php?leftmenu=setup','','Boxes','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87526,'smartphone',1,NULL,'left','home',87521,NULL,NULL,3,'/admin/menus.php?leftmenu=setup','','Menus','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87527,'smartphone',1,NULL,'left','home',87521,NULL,NULL,6,'/admin/delais.php?leftmenu=setup','','Alerts','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87529,'smartphone',1,NULL,'left','home',87521,NULL,NULL,7,'/admin/perms.php?leftmenu=setup','','Security','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87530,'smartphone',1,NULL,'left','home',87521,NULL,NULL,9,'/admin/mails.php?leftmenu=setup','','Emails','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87531,'smartphone',1,NULL,'left','home',87521,NULL,NULL,8,'/admin/limits.php?leftmenu=setup','','MenuLimits','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87532,'smartphone',1,NULL,'left','home',87521,NULL,NULL,10,'/admin/dict.php?leftmenu=setup','','DictionarySetup','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87533,'smartphone',1,NULL,'left','home',87521,NULL,NULL,11,'/admin/const.php?leftmenu=setup','','OtherSetup','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87621,'smartphone',1,NULL,'left','home',87422,NULL,NULL,1,'/admin/system/index.php?leftmenu=system','','SystemInfo','admin',0,'','','$user->admin',2,'2013-02-24 18:29:15'),(87622,'smartphone',1,NULL,'left','home',87621,NULL,NULL,0,'/admin/system/dolibarr.php?leftmenu=system','','Dolibarr','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87623,'smartphone',1,NULL,'left','home',87622,NULL,NULL,1,'/admin/system/constall.php?leftmenu=system','','AllParameters','admin',2,'','','1',2,'2013-02-24 18:29:15'),(87624,'smartphone',1,NULL,'left','home',87622,NULL,NULL,4,'/admin/system/about.php?leftmenu=system','','About','admin',2,'','','1',2,'2013-02-24 18:29:15'),(87625,'smartphone',1,NULL,'left','home',87621,NULL,NULL,1,'/admin/system/os.php?leftmenu=system','','OS','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87626,'smartphone',1,NULL,'left','home',87621,NULL,NULL,2,'/admin/system/web.php?leftmenu=system','','WebServer','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87627,'smartphone',1,NULL,'left','home',87621,NULL,NULL,3,'/admin/system/phpinfo.php?leftmenu=system','','Php','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87628,'smartphone',1,NULL,'left','home',87622,NULL,NULL,3,'/admin/triggers.php?leftmenu=system','','Triggers','admin',2,'','','1',2,'2013-02-24 18:29:15'),(87629,'smartphone',1,NULL,'left','home',87622,NULL,NULL,2,'/admin/system/modules.php?leftmenu=system','','Modules','admin',2,'','','1',2,'2013-02-24 18:29:15'),(87631,'smartphone',1,NULL,'left','home',87621,NULL,NULL,4,'/admin/system/database.php?leftmenu=system','','Database','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87632,'smartphone',1,NULL,'left','home',87631,NULL,NULL,0,'/admin/system/database-tables.php?leftmenu=system','','Tables','admin',2,'','','1',2,'2013-02-24 18:29:15'),(87721,'smartphone',1,NULL,'left','home',87422,NULL,NULL,2,'/admin/tools/index.php?leftmenu=admintools','','SystemTools','admin',0,'','','$user->admin',2,'2013-02-24 18:29:15'),(87722,'smartphone',1,NULL,'left','home',87721,NULL,NULL,0,'/admin/tools/dolibarr_export.php?leftmenu=admintools','','Backup','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87723,'smartphone',1,NULL,'left','home',87721,NULL,NULL,1,'/admin/tools/dolibarr_import.php?leftmenu=admintools','','Restore','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87724,'smartphone',1,NULL,'left','home',87721,NULL,NULL,6,'/admin/tools/purge.php?leftmenu=admintools','','Purge','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87725,'smartphone',1,NULL,'left','home',87721,NULL,NULL,3,'/admin/tools/eaccelerator.php?leftmenu=admintools','','EAccelerator','admin',1,'','','1 && function_exists(\'eaccelerator_info\')',2,'2013-02-24 18:29:15'),(87726,'smartphone',1,NULL,'left','home',87721,NULL,NULL,2,'/admin/tools/update.php?leftmenu=admintools','','MenuUpgrade','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87727,'smartphone',1,NULL,'left','home',87721,NULL,NULL,4,'/admin/tools/listevents.php?leftmenu=admintools','','Audit','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87728,'smartphone',1,NULL,'left','home',87721,NULL,NULL,7,'/support/index.php?leftmenu=admintools','_blank','HelpCenter','help',1,'','','1',2,'2013-02-24 18:29:15'),(87729,'smartphone',1,NULL,'left','home',87721,NULL,NULL,5,'/admin/tools/listsessions.php?leftmenu=admintools','','Sessions','admin',1,'','','1',2,'2013-02-24 18:29:15'),(87821,'smartphone',1,NULL,'left','home',87422,NULL,NULL,3,'/user/home.php?leftmenu=users','','MenuUsersAndGroups','users',0,'','','1',2,'2013-02-24 18:29:15'),(87822,'smartphone',1,NULL,'left','home',87821,NULL,NULL,0,'/user/index.php?leftmenu=users','','Users','users',1,'','$user->rights->user->user->lire || $user->admin','1',2,'2013-02-24 18:29:15'),(87823,'smartphone',1,NULL,'left','home',87822,NULL,NULL,0,'/user/fiche.php?leftmenu=users&amp;action=create','','NewUser','users',2,'','$user->rights->user->user->creer || $user->admin','1',2,'2013-02-24 18:29:15'),(87824,'smartphone',1,NULL,'left','home',87821,NULL,NULL,1,'/user/group/index.php?leftmenu=users','','Groups','users',1,'','($conf->global->MAIN_USE_ADVANCED_PERMS?$user->rights->user->group_advance->read:$user->rights->user->user->lire) || $user->admin','1',2,'2013-02-24 18:29:15'),(87825,'smartphone',1,NULL,'left','home',87824,NULL,NULL,0,'/user/group/fiche.php?leftmenu=users&amp;action=create','','NewGroup','users',2,'','($conf->global->MAIN_USE_ADVANCED_PERMS?$user->rights->user->group_advance->write:$user->rights->user->user->creer) || $user->admin','1',2,'2013-02-24 18:29:15'),(87921,'smartphone',1,NULL,'left','companies',87423,NULL,NULL,0,'/societe/societe.php','','ThirdParty','companies',0,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(87922,'smartphone',1,NULL,'left','companies',87921,NULL,NULL,0,'/societe/soc.php?action=create','','MenuNewThirdParty','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(87924,'smartphone',1,NULL,'left','companies',87921,NULL,NULL,5,'/fourn/liste.php?leftmenu=suppliers','','ListSuppliersShort','suppliers',1,'','$user->rights->societe->lire && $user->rights->fournisseur->lire','$conf->societe->enabled && $conf->fournisseur->enabled',2,'2013-02-24 18:29:15'),(87925,'smartphone',1,NULL,'left','companies',87924,NULL,NULL,0,'/societe/soc.php?leftmenu=supplier&amp;action=create&amp;type=f','','NewSupplier','suppliers',2,'','$user->rights->societe->creer','$conf->societe->enabled && $conf->fournisseur->enabled',2,'2013-02-24 18:29:15'),(87927,'smartphone',1,NULL,'left','companies',87921,NULL,NULL,3,'/comm/prospect/list.php?leftmenu=prospects','','ListProspectsShort','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(87928,'smartphone',1,NULL,'left','companies',87927,NULL,NULL,0,'/societe/soc.php?leftmenu=prospects&amp;action=create&amp;type=p','','MenuNewProspect','companies',2,'','$user->rights->societe->creer','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(87930,'smartphone',1,NULL,'left','companies',87921,NULL,NULL,4,'/comm/list.php?leftmenu=customers','','ListCustomersShort','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(87931,'smartphone',1,NULL,'left','companies',87930,NULL,NULL,0,'/societe/soc.php?leftmenu=customers&amp;action=create&amp;type=c','','MenuNewCustomer','companies',2,'','$user->rights->societe->creer','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(88021,'smartphone',1,NULL,'left','companies',87423,NULL,NULL,1,'/contact/list.php?leftmenu=contacts','','ContactsAddresses||Contacts@$conf->global->SOCIETE_ADDRESSES_MANAGEMENT','companies',0,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(88022,'smartphone',1,NULL,'left','companies',88021,NULL,NULL,0,'/contact/fiche.php?leftmenu=contacts&amp;action=create','','NewContactAddress||NewContact@$conf->global->SOCIETE_ADDRESSES_MANAGEMENT','companies',1,'','$user->rights->societe->creer','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(88023,'smartphone',1,NULL,'left','companies',88021,NULL,NULL,1,'/contact/list.php?leftmenu=contacts','','List','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(88025,'smartphone',1,NULL,'left','companies',88023,NULL,NULL,1,'/contact/list.php?leftmenu=contacts&amp;type=p','','Prospects','companies',2,'','$user->rights->societe->contact->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(88026,'smartphone',1,NULL,'left','companies',88023,NULL,NULL,2,'/contact/list.php?leftmenu=contacts&amp;type=c','','Customers','companies',2,'','$user->rights->societe->contact->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(88027,'smartphone',1,NULL,'left','companies',88023,NULL,NULL,3,'/contact/list.php?leftmenu=contacts&amp;type=f','','Suppliers','companies',2,'','$user->rights->societe->contact->lire','$conf->societe->enabled && $conf->fournisseur->enabled',2,'2013-02-24 18:29:15'),(88028,'smartphone',1,NULL,'left','companies',88023,NULL,NULL,4,'/contact/list.php?leftmenu=contacts&amp;type=o','','Others','companies',2,'','$user->rights->societe->contact->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(88071,'smartphone',1,NULL,'left','companies',87423,NULL,NULL,3,'/categories/index.php?leftmenu=cat&amp;type=1','','SuppliersCategoriesShort','categories',0,'','$user->rights->categorie->lire','$conf->societe->enabled && $conf->categorie->enabled',2,'2013-02-24 18:29:15'),(88072,'smartphone',1,NULL,'left','companies',88071,NULL,NULL,0,'/categories/fiche.php?action=create&amp;type=1','','NewCategory','categories',1,'','$user->rights->categorie->creer','$conf->societe->enabled && $conf->categorie->enabled',2,'2013-02-24 18:29:15'),(88081,'smartphone',1,NULL,'left','companies',87423,NULL,NULL,4,'/categories/index.php?leftmenu=cat&amp;type=2','','CustomersProspectsCategoriesShort','categories',0,'','$user->rights->categorie->lire','$conf->fournisseur->enabled && $conf->categorie->enabled',2,'2013-02-24 18:29:15'),(88082,'smartphone',1,NULL,'left','companies',88081,NULL,NULL,0,'/categories/fiche.php?action=create&amp;type=2','','NewCategory','categories',1,'','$user->rights->categorie->creer','$conf->fournisseur->enabled && $conf->categorie->enabled',2,'2013-02-24 18:29:15'),(88121,'smartphone',1,NULL,'left','commercial',87426,NULL,NULL,1,'/comm/prospect/index.php?leftmenu=prospects','','Prospects','companies',0,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2013-02-24 18:29:15'),(88122,'smartphone',1,NULL,'left','commercial',88121,NULL,NULL,0,'/societe/soc.php?leftmenu=prospects&amp;action=create&amp;type=p','','MenuNewProspect','companies',1,'','$user->rights->societe->creer','$conf->societe->enabled',0,'2013-02-24 18:29:15'),(88123,'smartphone',1,NULL,'left','commercial',88121,NULL,NULL,1,'/comm/prospect/list.php?leftmenu=prospects','','List','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2013-02-24 18:29:15'),(88124,'smartphone',1,NULL,'left','commercial',88123,NULL,NULL,0,'/comm/prospect/list.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=-1','','LastProspectDoNotContact','companies',2,'','$user->rights->societe->lire','$conf->societe->enabled && $leftmenu==\"prospects\"',0,'2013-02-24 18:29:15'),(88125,'smartphone',1,NULL,'left','commercial',88123,NULL,NULL,1,'/comm/prospect/list.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=0','','LastProspectNeverContacted','companies',2,'','$user->rights->societe->lire','$conf->societe->enabled && $leftmenu==\"prospects\"',0,'2013-02-24 18:29:15'),(88126,'smartphone',1,NULL,'left','commercial',88123,NULL,NULL,2,'/comm/prospect/list.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=1','','LastProspectToContact','companies',2,'','$user->rights->societe->lire','$conf->societe->enabled && $leftmenu==\"prospects\"',0,'2013-02-24 18:29:15'),(88127,'smartphone',1,NULL,'left','commercial',88123,NULL,NULL,3,'/comm/prospect/list.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=2','','LastProspectContactInProcess','companies',2,'','$user->rights->societe->lire','$conf->societe->enabled && $leftmenu==\"prospects\"',0,'2013-02-24 18:29:15'),(88128,'smartphone',1,NULL,'left','commercial',88123,NULL,NULL,4,'/comm/prospect/list.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=3','','LastProspectContactDone','companies',2,'','$user->rights->societe->lire','$conf->societe->enabled && $leftmenu==\"prospects\"',0,'2013-02-24 18:29:15'),(88129,'smartphone',1,NULL,'left','commercial',88121,NULL,NULL,2,'/contact/list.php?leftmenu=prospects&amp;type=p','','Contacts','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2013-02-24 18:29:15'),(88221,'smartphone',1,NULL,'left','commercial',87426,NULL,NULL,2,'/comm/index.php?leftmenu=customers','','Customers','companies',0,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2013-02-24 18:29:15'),(88222,'smartphone',1,NULL,'left','commercial',88221,NULL,NULL,0,'/societe/soc.php?leftmenu=customers&amp;action=create&amp;type=c','','MenuNewCustomer','companies',1,'','$user->rights->societe->creer','$conf->societe->enabled',0,'2013-02-24 18:29:15'),(88223,'smartphone',1,NULL,'left','commercial',88221,NULL,NULL,1,'/comm/list.php?leftmenu=customers','','List','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2013-02-24 18:29:15'),(88224,'smartphone',1,NULL,'left','commercial',88221,NULL,NULL,2,'/contact/list.php?leftmenu=customers&amp;type=c','','Contacts','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2013-02-24 18:29:15'),(88321,'smartphone',1,NULL,'left','commercial',87426,NULL,NULL,3,'/contact/list.php?leftmenu=contacts','','Contacts','companies',0,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2013-02-24 18:29:15'),(88322,'smartphone',1,NULL,'left','commercial',88321,NULL,NULL,0,'/contact/fiche.php?leftmenu=contacts&amp;action=create','','NewContactAddress||NewContact@$conf->global->SOCIETE_ADDRESSES_MANAGEMENT','companies',1,'','$user->rights->societe->creer','$conf->societe->enabled',0,'2013-02-24 18:29:15'),(88323,'smartphone',1,NULL,'left','commercial',88321,NULL,NULL,1,'/contact/list.php?leftmenu=contacts&amp;action=create','','List','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2013-02-24 18:29:15'),(88331,'smartphone',1,NULL,'left','commercial',88323,NULL,NULL,1,'/contact/list.php?leftmenu=contacts&amp;type=p','','Prospects','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled && $leftmenu==\"prospects\"',0,'2013-02-24 18:29:15'),(88332,'smartphone',1,NULL,'left','commercial',88323,NULL,NULL,1,'/contact/list.php?leftmenu=contacts&amp;type=c','','Customers','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled && $leftmenu==\"prospects\"',0,'2013-02-24 18:29:15'),(88333,'smartphone',1,NULL,'left','commercial',88323,NULL,NULL,1,'/contact/list.php?leftmenu=contacts&amp;type=f','','Suppliers','companies',1,'','$user->rights->societe->lire && $user->rights->fournisseur->lire','$conf->societe->enabled && $conf->fournisseur->enabled && $leftmenu==\"prospects\"',0,'2013-02-24 18:29:15'),(88334,'smartphone',1,NULL,'left','commercial',88323,NULL,NULL,1,'/contact/list.php?leftmenu=contacts&amp;type=o','','Other','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled && $leftmenu==\"prospects\"',0,'2013-02-24 18:29:15'),(88521,'smartphone',1,NULL,'left','commercial',87426,NULL,NULL,4,'/comm/propal/card.php?leftmenu=propals','','Prop','propal',0,'','$user->rights->propale->lire','$conf->propal->enabled',2,'2013-02-24 18:29:15'),(88522,'smartphone',1,NULL,'left','commercial',88521,NULL,NULL,0,'/societe/societe.php?leftmenu=propals','','NewPropal','propal',1,'','$user->rights->propale->creer','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2013-02-24 18:29:15'),(88523,'smartphone',1,NULL,'left','commercial',88521,NULL,NULL,1,'/comm/propal/card.php?viewstatut=0','','PropalsDraft','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2013-02-24 18:29:15'),(88524,'smartphone',1,NULL,'left','commercial',88521,NULL,NULL,2,'/comm/propal/card.php?viewstatut=1','','PropalsOpened','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2013-02-24 18:29:15'),(88525,'smartphone',1,NULL,'left','commercial',88521,NULL,NULL,3,'/comm/propal/card.php?viewstatut=2,3,4','','PropalStatusClosedShort','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2013-02-24 18:29:15'),(88526,'smartphone',1,NULL,'left','commercial',88521,NULL,NULL,4,'/comm/propal/stats/index.php?leftmenu=propals','','Statistics','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2013-02-24 18:29:15'),(88621,'smartphone',1,NULL,'left','commercial',87426,NULL,NULL,5,'/commande/index.php?leftmenu=orders','','CustomersOrders','orders',0,'','$user->rights->commande->lire','$conf->commande->enabled',2,'2013-02-24 18:29:15'),(88622,'smartphone',1,NULL,'left','commercial',88621,NULL,NULL,0,'/societe/societe.php?leftmenu=orders','','NewOrder','orders',1,'','$user->rights->commande->creer','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-02-24 18:29:15'),(88623,'smartphone',1,NULL,'left','commercial',88621,NULL,NULL,1,'/commande/liste.php?leftmenu=orders','','List','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-02-24 18:29:15'),(88624,'smartphone',1,NULL,'left','commercial',88623,NULL,NULL,2,'/commande/liste.php?leftmenu=orders&amp;viewstatut=0','','StatusOrderDraftShort','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-02-24 18:29:15'),(88625,'smartphone',1,NULL,'left','commercial',88623,NULL,NULL,3,'/commande/liste.php?leftmenu=orders&amp;viewstatut=1','','StatusOrderValidated','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-02-24 18:29:15'),(88626,'smartphone',1,NULL,'left','commercial',88623,NULL,NULL,4,'/commande/liste.php?leftmenu=orders&amp;viewstatut=2','','StatusOrderOnProcessShort','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-02-24 18:29:15'),(88627,'smartphone',1,NULL,'left','commercial',88623,NULL,NULL,5,'/commande/liste.php?leftmenu=orders&amp;viewstatut=3','','StatusOrderToBill','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-02-24 18:29:15'),(88628,'smartphone',1,NULL,'left','commercial',88623,NULL,NULL,6,'/commande/liste.php?leftmenu=orders&amp;viewstatut=4','','StatusOrderProcessed','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-02-24 18:29:15'),(88629,'smartphone',1,NULL,'left','commercial',88623,NULL,NULL,7,'/commande/liste.php?leftmenu=orders&amp;viewstatut=-1','','StatusOrderCanceledShort','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-02-24 18:29:15'),(88630,'smartphone',1,NULL,'left','commercial',88621,NULL,NULL,4,'/commande/stats/index.php?leftmenu=orders','','Statistics','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-02-24 18:29:15'),(88721,'smartphone',1,NULL,'left','commercial',87424,NULL,NULL,6,'/expedition/index.php?leftmenu=sendings','','Shipments','orders',0,'','$user->rights->expedition->lire','$conf->expedition->enabled',2,'2013-02-24 18:29:15'),(88722,'smartphone',1,NULL,'left','commercial',88721,NULL,NULL,0,'/expedition/liste.php?leftmenu=sendings','','List','orders',1,'','$user->rights->expedition->lire','$conf->expedition->enabled && $leftmenu==\"sendings\"',2,'2013-02-24 18:29:15'),(88723,'smartphone',1,NULL,'left','commercial',88721,NULL,NULL,1,'/expedition/stats/index.php?leftmenu=sendings','','Statistics','orders',1,'','$user->rights->expedition->lire','$conf->expedition->enabled && $leftmenu==\"sendings\"',2,'2013-02-24 18:29:15'),(88821,'smartphone',1,NULL,'left','commercial',87426,NULL,NULL,7,'/contrat/index.php?leftmenu=contracts','','Contracts','contracts',0,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2013-02-24 18:29:15'),(88822,'smartphone',1,NULL,'left','commercial',88821,NULL,NULL,0,'/societe/societe.php?leftmenu=contracts','','NewContract','contracts',1,'','$user->rights->contrat->creer','$conf->contrat->enabled',2,'2013-02-24 18:29:15'),(88823,'smartphone',1,NULL,'left','commercial',88821,NULL,NULL,1,'/contrat/liste.php?leftmenu=contracts','','List','contracts',1,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2013-02-24 18:29:15'),(88824,'smartphone',1,NULL,'left','commercial',88821,NULL,NULL,2,'/contrat/services.php?leftmenu=contracts','','MenuServices','contracts',1,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2013-02-24 18:29:15'),(88825,'smartphone',1,NULL,'left','commercial',88824,NULL,NULL,0,'/contrat/services.php?leftmenu=contracts&amp;mode=0','','MenuInactiveServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled&&$leftmenu==\"contracts\"',2,'2013-02-24 18:29:15'),(88826,'smartphone',1,NULL,'left','commercial',88824,NULL,NULL,1,'/contrat/services.php?leftmenu=contracts&amp;mode=4','','MenuRunningServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled&&$leftmenu==\"contracts\"',2,'2013-02-24 18:29:15'),(88827,'smartphone',1,NULL,'left','commercial',88824,NULL,NULL,2,'/contrat/services.php?leftmenu=contracts&amp;mode=4&amp;filter=expired','','MenuExpiredServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled&&$leftmenu==\"contracts\"',2,'2013-02-24 18:29:15'),(88828,'smartphone',1,NULL,'left','commercial',88824,NULL,NULL,3,'/contrat/services.php?leftmenu=contracts&amp;mode=5','','MenuClosedServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled&&$leftmenu==\"contracts\"',2,'2013-02-24 18:29:15'),(88921,'smartphone',1,NULL,'left','commercial',87426,NULL,NULL,8,'/fichinter/list.php?leftmenu=ficheinter','','Interventions','interventions',0,'','$user->rights->ficheinter->lire','$conf->ficheinter->enabled',2,'2013-02-24 18:29:15'),(88922,'smartphone',1,NULL,'left','commercial',88921,NULL,NULL,0,'/fichinter/fiche.php?action=create&amp;leftmenu=ficheinter','','NewIntervention','interventions',1,'','$user->rights->ficheinter->creer','$conf->ficheinter->enabled && $leftmenu==\"ficheinter\"',2,'2013-02-24 18:29:15'),(88923,'smartphone',1,NULL,'left','commercial',88921,NULL,NULL,1,'/fichinter/list.php?leftmenu=ficheinter','','List','interventions',1,'','$user->rights->ficheinter->lire','$conf->ficheinter->enabled && $leftmenu==\"ficheinter\"',2,'2013-02-24 18:29:15'),(89021,'smartphone',1,NULL,'left','accountancy',87427,NULL,NULL,3,'/fourn/facture/index.php?leftmenu=suppliers_bills','','BillsSuppliers','bills',0,'','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled',2,'2013-02-24 18:29:15'),(89022,'smartphone',1,NULL,'left','accountancy',89021,NULL,NULL,0,'/fourn/facture/fiche.php?action=create&amp;leftmenu=suppliers_bills','','NewBill','bills',1,'','$user->rights->fournisseur->facture->creer','$conf->fournisseur->enabled',2,'2013-02-24 18:29:15'),(89023,'smartphone',1,NULL,'left','accountancy',89021,NULL,NULL,1,'/fourn/facture/impayees.php?leftmenu=suppliers_bills','','Unpaid','bills',1,'','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled',2,'2013-02-24 18:29:15'),(89024,'smartphone',1,NULL,'left','accountancy',89021,NULL,NULL,2,'/fourn/facture/paiement.php?leftmenu=suppliers_bills','','Payments','bills',1,'','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled',2,'2013-02-24 18:29:15'),(89121,'smartphone',1,NULL,'left','accountancy',87427,NULL,NULL,3,'/compta/facture/list.php?leftmenu=customers_bills','','BillsCustomers','bills',0,'','$user->rights->facture->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(89122,'smartphone',1,NULL,'left','accountancy',89121,NULL,NULL,3,'/compta/clients.php?action=facturer&amp;leftmenu=customers_bills','','NewBill','bills',1,'','$user->rights->facture->creer','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(89123,'smartphone',1,NULL,'left','accountancy',89121,NULL,NULL,4,'/compta/facture/fiche-rec.php?leftmenu=customers_bills','','Repeatable','bills',1,'','$user->rights->facture->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(89124,'smartphone',1,NULL,'left','accountancy',89121,NULL,NULL,5,'/compta/facture/impayees.php?action=facturer&amp;leftmenu=customers_bills','','Unpaid','bills',1,'','$user->rights->facture->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(89125,'smartphone',1,NULL,'left','accountancy',89121,NULL,NULL,6,'/compta/paiement/liste.php?leftmenu=customers_bills','','Payments','bills',1,'','$user->rights->facture->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(89131,'smartphone',1,NULL,'left','accountancy',89125,NULL,NULL,1,'/compta/paiement/rapport.php?leftmenu=customers_bills','','Reportings','bills',2,'','$user->rights->facture->lire','$conf->societe->enabled',2,'2013-02-24 18:29:15'),(89132,'smartphone',1,NULL,'left','accountancy',87435,NULL,NULL,9,'/compta/paiement/cheque/index.php?leftmenu=checks&amp;mainmenu=bank','','MenuChequeDeposits','bills',0,'','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled',2,'2013-02-24 18:29:15'),(89133,'smartphone',1,NULL,'left','accountancy',89132,NULL,NULL,0,'/compta/paiement/cheque/fiche.php?leftmenu=checks&amp;action=new','','NewCheckDeposit','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled',2,'2013-02-24 18:29:15'),(89134,'smartphone',1,NULL,'left','accountancy',89132,NULL,NULL,1,'/compta/paiement/cheque/liste.php?leftmenu=checks','','List','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled',2,'2013-02-24 18:29:15'),(89135,'smartphone',1,NULL,'left','accountancy',89121,NULL,NULL,8,'/compta/facture/stats/index.php?leftmenu=customers_bills','','Statistics','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled',2,'2013-02-24 18:29:15'),(89321,'smartphone',1,NULL,'left','accountancy',87427,NULL,NULL,3,'/commande/liste.php?leftmenu=orders&amp;viewstatut=3','','MenuOrdersToBill','orders',0,'','$user->rights->commande->lire','$conf->commande->enabled',0,'2013-02-24 18:29:15'),(89421,'smartphone',1,NULL,'left','accountancy',87427,NULL,NULL,4,'/compta/dons/index.php?leftmenu=donations&amp;mainmenu=accountancy','','Donations','donations',0,'','$user->rights->don->lire','$conf->don->enabled',2,'2013-02-24 18:29:15'),(89422,'smartphone',1,NULL,'left','accountancy',89421,NULL,NULL,0,'/compta/dons/fiche.php?leftmenu=donations&amp;mainmenu=accountancy&amp;action=create','','NewDonation','donations',1,'','$user->rights->don->creer','$conf->don->enabled && $leftmenu==\"donations\"',2,'2013-02-24 18:29:15'),(89423,'smartphone',1,NULL,'left','accountancy',89421,NULL,NULL,1,'/compta/dons/liste.php?leftmenu=donations&amp;mainmenu=accountancy','','List','donations',1,'','$user->rights->don->lire','$conf->don->enabled && $leftmenu==\"donations\"',2,'2013-02-24 18:29:15'),(89521,'smartphone',1,NULL,'left','accountancy',87427,NULL,NULL,5,'/compta/deplacement/index.php?leftmenu=tripsandexpenses','','TripsAndExpenses','trips',0,'','$user->rights->deplacement->lire','$conf->deplacement->enabled',0,'2013-02-24 18:29:15'),(89522,'smartphone',1,NULL,'left','accountancy',89521,NULL,NULL,1,'/compta/deplacement/fiche.php?action=create&amp;leftmenu=tripsandexpenses','','New','trips',1,'','$user->rights->deplacement->creer','$conf->deplacement->enabled && $leftmenu==\"tripsandexpenses\"',0,'2013-02-24 18:29:15'),(89523,'smartphone',1,NULL,'left','accountancy',89521,NULL,NULL,2,'/compta/deplacement/index.php?leftmenu=tripsandexpenses','','List','trips',1,'','$user->rights->deplacement->lire','$conf->deplacement->enabled && $leftmenu==\"tripsandexpenses\"',0,'2013-02-24 18:29:15'),(89524,'smartphone',1,NULL,'left','accountancy',89521,NULL,NULL,2,'/compta/deplacement/stats/index.php?leftmenu=tripsandexpenses','','Statistics','trips',1,'','$user->rights->deplacement->lire','$conf->deplacement->enabled && $leftmenu==\"tripsandexpenses\"',0,'2013-02-24 18:29:15'),(89621,'smartphone',1,NULL,'left','accountancy',87427,NULL,NULL,6,'/compta/charges/index.php?leftmenu=tax&amp;mainmenu=accountancy','','MenuTaxAndDividends','compta',0,'','$user->rights->tax->charges->lire','$conf->tax->enabled',0,'2013-02-24 18:29:15'),(89622,'smartphone',1,NULL,'left','accountancy',89621,NULL,NULL,1,'/compta/sociales/index.php?leftmenu=tax_social','','SocialContributions','',1,'','$user->rights->tax->charges->lire','$conf->tax->enabled',0,'2013-02-24 18:29:15'),(89623,'smartphone',1,NULL,'left','accountancy',89622,NULL,NULL,2,'/compta/sociales/charges.php?leftmenu=tax_social&amp;action=create','','MenuNewSocialContribution','',2,'','$user->rights->tax->charges->creer','$conf->tax->enabled && $leftmenu==\"tax_social\"',0,'2013-02-24 18:29:15'),(89624,'smartphone',1,NULL,'left','accountancy',89622,NULL,NULL,3,'/compta/charges/index.php?leftmenu=tax_social&amp;mainmenu=accountancy&amp;mode=sconly','','Payments','',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && $leftmenu==\"tax_social\"',0,'2013-02-24 18:29:15'),(89721,'smartphone',1,NULL,'left','accountancy',89621,NULL,NULL,7,'/compta/tva/index.php?leftmenu=tax_vat&amp;mainmenu=accountancy','','VAT','companies',1,'','$user->rights->tax->charges->lire','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS)',0,'2013-02-24 18:29:15'),(89722,'smartphone',1,NULL,'left','accountancy',89721,NULL,NULL,0,'/compta/tva/fiche.php?leftmenu=tax_vat&amp;action=create','','NewPayment','companies',2,'','$user->rights->tax->charges->creer','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS) && $leftmenu==\"tax_vat\"',0,'2013-02-24 18:29:15'),(89723,'smartphone',1,NULL,'left','accountancy',89721,NULL,NULL,1,'/compta/tva/reglement.php?leftmenu=tax_vat','','Payments','companies',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS) && $leftmenu==\"tax_vat\"',0,'2013-02-24 18:29:15'),(89724,'smartphone',1,NULL,'left','accountancy',89721,NULL,NULL,2,'/compta/tva/clients.php?leftmenu=tax_vat','','ReportByCustomers','companies',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS) && $leftmenu==\"tax_vat\"',0,'2013-02-24 18:29:15'),(89725,'smartphone',1,NULL,'left','accountancy',89721,NULL,NULL,3,'/compta/tva/quadri_detail.php?leftmenu=tax_vat','','ReportByQuarter','companies',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS) && $leftmenu==\"tax_vat\"',0,'2013-02-24 18:29:15'),(89821,'smartphone',1,NULL,'left','accountancy',87427,NULL,NULL,8,'/compta/ventilation/index.php?leftmenu=ventil','','Ventilation','companies',0,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-02-24 18:29:15'),(89822,'smartphone',1,NULL,'left','accountancy',89821,NULL,NULL,0,'/compta/ventilation/liste.php','','ToDispatch','companies',1,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-02-24 18:29:15'),(89823,'smartphone',1,NULL,'left','accountancy',89821,NULL,NULL,1,'/compta/ventilation/lignes.php','','Dispatched','companies',1,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-02-24 18:29:15'),(89824,'smartphone',1,NULL,'left','accountancy',89821,NULL,NULL,2,'/compta/param/','','Setup','companies',1,'','$user->rights->compta->ventilation->parametrer','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-02-24 18:29:15'),(89825,'smartphone',1,NULL,'left','accountancy',89824,NULL,NULL,0,'/compta/param/comptes/liste.php','','List','companies',2,'','$user->rights->compta->ventilation->parametrer','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-02-24 18:29:15'),(89826,'smartphone',1,NULL,'left','accountancy',89824,NULL,NULL,1,'/compta/param/comptes/fiche.php?action=create','','New','companies',2,'','$user->rights->compta->ventilation->parametrer','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-02-24 18:29:15'),(89827,'smartphone',1,NULL,'left','accountancy',89821,NULL,NULL,3,'/compta/export/','','Export','companies',1,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-02-24 18:29:15'),(89828,'smartphone',1,NULL,'left','accountancy',89827,NULL,NULL,0,'/compta/export/index.php','','New','companies',2,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-02-24 18:29:15'),(89829,'smartphone',1,NULL,'left','accountancy',89827,NULL,NULL,1,'/compta/export/liste.php','','List','companies',2,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-02-24 18:29:15'),(89921,'smartphone',1,NULL,'left','accountancy',87435,NULL,NULL,9,'/compta/prelevement/index.php?leftmenu=withdraw&amp;mainmenu=bank','','StandingOrders','withdrawals',0,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled',2,'2013-02-24 18:29:15'),(89922,'smartphone',1,NULL,'left','accountancy',89921,NULL,NULL,1,'/compta/prelevement/demandes.php?status=0&amp;leftmenu=withdraw','','StandingOrderToProcess','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2013-02-24 18:29:15'),(89923,'smartphone',1,NULL,'left','accountancy',89921,NULL,NULL,0,'/compta/prelevement/create.php?leftmenu=withdraw','','NewStandingOrder','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2013-02-24 18:29:15'),(89924,'smartphone',1,NULL,'left','accountancy',89921,NULL,NULL,2,'/compta/prelevement/bons.php?leftmenu=withdraw','','WithdrawalsReceipts','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2013-02-24 18:29:15'),(89925,'smartphone',1,NULL,'left','accountancy',89921,NULL,NULL,3,'/compta/prelevement/liste.php?leftmenu=withdraw','','WithdrawalsLines','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2013-02-24 18:29:15'),(89927,'smartphone',1,NULL,'left','accountancy',89921,NULL,NULL,5,'/compta/prelevement/rejets.php?leftmenu=withdraw','','Rejects','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2013-02-24 18:29:15'),(89928,'smartphone',1,NULL,'left','accountancy',89921,NULL,NULL,6,'/compta/prelevement/stats.php?leftmenu=withdraw','','Statistics','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2013-02-24 18:29:15'),(90021,'smartphone',1,NULL,'left','accountancy',87435,NULL,NULL,1,'/compta/bank/index.php?leftmenu=bank&amp;mainmenu=bank','','MenuBankCash','banks',0,'','$user->rights->banque->lire','$conf->banque->enabled',0,'2013-02-24 18:29:15'),(90022,'smartphone',1,NULL,'left','accountancy',90021,NULL,NULL,0,'/compta/bank/fiche.php?action=create&amp;leftmenu=bank','','MenuNewFinancialAccount','banks',1,'','$user->rights->banque->configurer','$conf->banque->enabled && $leftmenu==bank',0,'2013-02-24 18:29:15'),(90023,'smartphone',1,NULL,'left','accountancy',90021,NULL,NULL,1,'/compta/bank/categ.php?leftmenu=bank','','Rubriques','categories',1,'','$user->rights->banque->configurer','$conf->banque->enabled && $leftmenu==bank',0,'2013-02-24 18:29:15'),(90024,'smartphone',1,NULL,'left','accountancy',90021,NULL,NULL,2,'/compta/bank/search.php?leftmenu=bank','','ListTransactions','banks',1,'','$user->rights->banque->lire','$conf->banque->enabled && $leftmenu==bank',0,'2013-02-24 18:29:15'),(90025,'smartphone',1,NULL,'left','accountancy',90021,NULL,NULL,3,'/compta/bank/budget.php?leftmenu=bank','','ListTransactionsByCategory','banks',1,'','$user->rights->banque->lire','$conf->banque->enabled && $leftmenu==bank',0,'2013-02-24 18:29:15'),(90027,'smartphone',1,NULL,'left','accountancy',90021,NULL,NULL,5,'/compta/bank/virement.php?leftmenu=bank','','BankTransfers','banks',1,'','$user->rights->banque->transfer','$conf->banque->enabled && $leftmenu==bank',0,'2013-02-24 18:29:15'),(90121,'smartphone',1,NULL,'left','accountancy',87427,NULL,NULL,11,'/compta/resultat/index.php?leftmenu=ca&amp;mainmenu=accountancy','','Reportings','main',0,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-02-24 18:29:15'),(90122,'smartphone',1,NULL,'left','accountancy',90121,NULL,NULL,0,'/compta/resultat/index.php?leftmenu=ca','','ReportInOut','main',1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-02-24 18:29:15'),(90123,'smartphone',1,NULL,'left','accountancy',90122,NULL,NULL,0,'/compta/resultat/clientfourn.php?leftmenu=ca','','ByCompanies','main',2,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-02-24 18:29:15'),(90124,'smartphone',1,NULL,'left','accountancy',90121,NULL,NULL,1,'/compta/stats/index.php?leftmenu=ca','','ReportTurnover','main',1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-02-24 18:29:15'),(90125,'smartphone',1,NULL,'left','accountancy',90124,NULL,NULL,0,'/compta/stats/casoc.php?leftmenu=ca','','ByCompanies','main',2,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-02-24 18:29:15'),(90126,'smartphone',1,NULL,'left','accountancy',90124,NULL,NULL,1,'/compta/stats/cabyuser.php?leftmenu=ca','','ByUsers','main',2,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-02-24 18:29:15'),(90221,'smartphone',1,NULL,'left','products',87424,NULL,NULL,0,'/product/index.php?leftmenu=product&amp;type=0','','Products','products',0,'','$user->rights->produit->lire','$conf->product->enabled',2,'2013-02-24 18:29:15'),(90222,'smartphone',1,NULL,'left','products',90221,NULL,NULL,0,'/product/fiche.php?leftmenu=product&amp;action=create&amp;type=0','','NewProduct','products',1,'','$user->rights->produit->creer','$conf->product->enabled',2,'2013-02-24 18:29:15'),(90223,'smartphone',1,NULL,'left','products',90221,NULL,NULL,1,'/product/liste.php?leftmenu=product&amp;type=0','','List','products',1,'','$user->rights->produit->lire','$conf->product->enabled',2,'2013-02-24 18:29:15'),(90224,'smartphone',1,NULL,'left','products',90221,NULL,NULL,4,'/product/reassort.php?type=0','','Stocks','products',1,'','$user->rights->produit->lire && $user->rights->stock->lire','$conf->product->enabled',2,'2013-02-24 18:29:15'),(90321,'smartphone',1,NULL,'left','products',87424,NULL,NULL,1,'/product/index.php?leftmenu=service&amp;type=1','','Services','products',0,'','$user->rights->service->lire','$conf->service->enabled',2,'2013-02-24 18:29:15'),(90322,'smartphone',1,NULL,'left','products',90321,NULL,NULL,0,'/product/fiche.php?leftmenu=service&amp;action=create&amp;type=1','','NewService','products',1,'','$user->rights->service->creer','$conf->service->enabled',2,'2013-02-24 18:29:15'),(90323,'smartphone',1,NULL,'left','products',90321,NULL,NULL,1,'/product/liste.php?leftmenu=service&amp;type=1','','List','products',1,'','$user->rights->service->lire','$conf->service->enabled',2,'2013-02-24 18:29:15'),(90421,'smartphone',1,NULL,'left','products',87424,NULL,NULL,2,'/product/stats/index.php?leftmenu=stats','','Statistics','main',0,'','$user->rights->service->lire','$conf->product->enabled || $conf->service->enabled',2,'2013-02-24 18:29:15'),(90422,'smartphone',1,NULL,'left','products',90421,NULL,NULL,0,'/product/popuprop.php?leftmenu=stats','','Popularity','main',1,'','$user->rights->produit->lire && $user->rights->produit>lire','$conf->propal->enabled',2,'2013-02-24 18:29:15'),(90521,'smartphone',1,NULL,'left','products',87424,NULL,NULL,3,'/product/stock/index.php?leftmenu=stock','','Stock','stocks',0,'','$user->rights->stock->lire','$conf->stock->enabled',2,'2013-02-24 18:29:15'),(90522,'smartphone',1,NULL,'left','products',90521,NULL,NULL,0,'/product/stock/fiche.php?action=create','','MenuNewWarehouse','stocks',1,'','$user->rights->stock->creer','$conf->stock->enabled',2,'2013-02-24 18:29:15'),(90523,'smartphone',1,NULL,'left','products',90521,NULL,NULL,1,'/product/stock/liste.php','','List','stocks',1,'','$user->rights->stock->lire','$conf->stock->enabled',2,'2013-02-24 18:29:15'),(90524,'smartphone',1,NULL,'left','products',90521,NULL,NULL,2,'/product/stock/valo.php','','EnhancedValue','stocks',1,'','$user->rights->stock->lire','$conf->stock->enabled',2,'2013-02-24 18:29:15'),(90525,'smartphone',1,NULL,'left','products',90521,NULL,NULL,3,'/product/stock/mouvement.php','','Movements','stocks',1,'','$user->rights->stock->mouvement->lire','$conf->stock->enabled',2,'2013-02-24 18:29:15'),(90621,'smartphone',1,NULL,'left','products',87424,NULL,NULL,4,'/categories/index.php?leftmenu=cat&amp;type=0','','Categories','categories',0,'','$user->rights->categorie->lire','$conf->categorie->enabled',2,'2013-02-24 18:29:15'),(90622,'smartphone',1,NULL,'left','products',90621,NULL,NULL,0,'/categories/fiche.php?action=create&amp;type=0','','NewCategory','categories',1,'','$user->rights->categorie->creer','$conf->categorie->enabled',2,'2013-02-24 18:29:15'),(91021,'smartphone',1,NULL,'left','project',87428,NULL,NULL,0,'/projet/index.php?leftmenu=projects','','Projects','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91022,'smartphone',1,NULL,'left','project',91021,NULL,NULL,1,'/projet/fiche.php?leftmenu=projects&amp;action=create','','NewProject','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91023,'smartphone',1,NULL,'left','project',91021,NULL,NULL,2,'/projet/liste.php?leftmenu=projects','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91031,'smartphone',1,NULL,'left','project',87428,NULL,NULL,0,'/projet/index.php?leftmenu=projects&amp;mode=mine','','MyProjects','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91032,'smartphone',1,NULL,'left','project',91031,NULL,NULL,1,'/projet/fiche.php?leftmenu=projects&amp;action=create&amp;mode=mine','','NewProject','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91033,'smartphone',1,NULL,'left','project',91031,NULL,NULL,2,'/projet/liste.php?leftmenu=projects&amp;mode=mine','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91121,'smartphone',1,NULL,'left','project',87428,NULL,NULL,0,'/projet/activity/index.php?leftmenu=projects','','Activities','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91122,'smartphone',1,NULL,'left','project',91121,NULL,NULL,1,'/projet/tasks.php?leftmenu=projects&amp;action=create','','NewTask','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91123,'smartphone',1,NULL,'left','project',91121,NULL,NULL,2,'/projet/tasks/index.php?leftmenu=projects','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91124,'smartphone',1,NULL,'left','project',91121,NULL,NULL,3,'/projet/activity/list.php?leftmenu=projects','','NewTimeSpent','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91221,'smartphone',1,NULL,'left','project',87428,NULL,NULL,0,'/projet/activity/index.php?leftmenu=projects&amp;mode=mine','','MyActivities','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91222,'smartphone',1,NULL,'left','project',91221,NULL,NULL,1,'/projet/tasks.php?leftmenu=projects&amp;action=create&amp;mode=mine','','NewTask','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91223,'smartphone',1,NULL,'left','project',91221,NULL,NULL,2,'/projet/tasks/index.php?leftmenu=projects&amp;mode=mine','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91224,'smartphone',1,NULL,'left','project',91221,NULL,NULL,3,'/projet/activity/list.php?leftmenu=projects&amp;mode=mine','','NewTimeSpent','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-02-24 18:29:15'),(91321,'smartphone',1,NULL,'left','tools',87429,NULL,NULL,0,'/comm/mailing/index.php?leftmenu=mailing','','EMailings','mails',0,'','$user->rights->mailing->lire','$conf->mailing->enabled',0,'2013-02-24 18:29:15'),(91322,'smartphone',1,NULL,'left','tools',91321,NULL,NULL,0,'/comm/mailing/fiche.php?leftmenu=mailing&amp;action=create','','NewMailing','mails',1,'','$user->rights->mailing->creer','$conf->mailing->enabled',0,'2013-02-24 18:29:15'),(91323,'smartphone',1,NULL,'left','tools',91321,NULL,NULL,1,'/comm/mailing/liste.php?leftmenu=mailing','','List','mails',1,'','$user->rights->mailing->lire','$conf->mailing->enabled',0,'2013-02-24 18:29:15'),(91521,'smartphone',1,NULL,'left','tools',87429,NULL,NULL,2,'/exports/index.php?leftmenu=export','','FormatedExport','exports',0,'','$user->rights->export->lire','$conf->export->enabled',2,'2013-02-24 18:29:15'),(91522,'smartphone',1,NULL,'left','tools',91521,NULL,NULL,0,'/exports/export.php?leftmenu=export','','NewExport','exports',1,'','$user->rights->export->creer','$conf->export->enabled',2,'2013-02-24 18:29:15'),(91551,'smartphone',1,NULL,'left','tools',87429,NULL,NULL,2,'/imports/index.php?leftmenu=import','','FormatedImport','exports',0,'','$user->rights->import->run','$conf->import->enabled',2,'2013-02-24 18:29:15'),(91552,'smartphone',1,NULL,'left','tools',91551,NULL,NULL,0,'/imports/import.php?leftmenu=import','','NewImport','exports',1,'','$user->rights->import->run','$conf->import->enabled',2,'2013-02-24 18:29:15'),(91621,'smartphone',1,NULL,'left','members',87434,NULL,NULL,0,'/adherents/index.php?leftmenu=members&amp;mainmenu=members','','Members','members',0,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(91622,'smartphone',1,NULL,'left','members',91621,NULL,NULL,0,'/adherents/fiche.php?action=create','','NewMember','members',1,'','$user->rights->adherent->creer','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(91623,'smartphone',1,NULL,'left','members',91621,NULL,NULL,1,'/adherents/liste.php','','List','members',1,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(91624,'smartphone',1,NULL,'left','members',91623,NULL,NULL,2,'/adherents/liste.php?statut=-1','','MenuMembersToValidate','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(91625,'smartphone',1,NULL,'left','members',91623,NULL,NULL,3,'/adherents/liste.php?statut=1','','MenuMembersValidated','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(91626,'smartphone',1,NULL,'left','members',91623,NULL,NULL,4,'/adherents/liste.php?statut=1&amp;filter=outofdate','','MenuMembersNotUpToDate','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(91627,'smartphone',1,NULL,'left','members',91623,NULL,NULL,5,'/adherents/liste.php?statut=1&amp;filter=uptodate','','MenuMembersUpToDate','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(91628,'smartphone',1,NULL,'left','members',91623,NULL,NULL,6,'/adherents/liste.php?statut=0','','MenuMembersResiliated','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(91721,'smartphone',1,NULL,'left','members',87434,NULL,NULL,1,'/adherents/index.php?leftmenu=accountancy&amp;mainmenu=members','','Subscriptions','compta',0,'','$user->rights->adherent->cotisation->lire','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(91722,'smartphone',1,NULL,'left','members',91721,NULL,NULL,0,'/adherents/liste.php?statut=-1&amp;leftmenu=accountancy&amp;mainmenu=members','','NewSubscription','compta',1,'','$user->rights->adherent->cotisation->creer','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(91723,'smartphone',1,NULL,'left','members',91721,NULL,NULL,1,'/adherents/cotisations.php?leftmenu=accountancy','','List','compta',1,'','$user->rights->adherent->cotisation->lire','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(91921,'smartphone',1,NULL,'left','members',87434,NULL,NULL,3,'/adherents/index.php?leftmenu=export&amp;mainmenu=members','','Exports','members',0,'','$user->rights->adherent->export','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(91922,'smartphone',1,NULL,'left','members',91921,NULL,NULL,0,'/exports/index.php?leftmenu=export','','Datas','members',1,'','$user->rights->adherent->export','$conf->adherent->enabled && $conf->export->enabled',2,'2013-02-24 18:29:15'),(91923,'smartphone',1,NULL,'left','members',91921,NULL,NULL,1,'/adherents/htpasswd.php?leftmenu=export','','Filehtpasswd','members',1,'','$user->rights->adherent->export','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(91924,'smartphone',1,NULL,'left','members',91921,NULL,NULL,2,'/adherents/cartes/carte.php?leftmenu=export','','MembersCards','members',1,'','$user->rights->adherent->export','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(92121,'smartphone',1,NULL,'left','members',87434,NULL,NULL,5,'/adherents/index.php?leftmenu=setup&amp;mainmenu=members','','Setup','members',0,'','$user->rights->adherent->configurer','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(92122,'smartphone',1,NULL,'left','members',92121,NULL,NULL,0,'/adherents/type.php?leftmenu=setup','','MembersTypes','members',1,'','$user->rights->adherent->configurer','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(92123,'smartphone',1,NULL,'left','members',92121,NULL,NULL,1,'/adherents/options.php?leftmenu=setup','','MembersAttributes','members',1,'','$user->rights->adherent->configurer','$conf->adherent->enabled',2,'2013-02-24 18:29:15'),(92421,'smartphone',1,NULL,'left','commercial',87426,NULL,NULL,0,'/compta/index.php?leftmenu=suppliers','','Suppliers','companies',0,'','$user->rights->societe->lire && $user->rights->fournisseur->lire','$conf->fournisseur->enabled',2,'2013-02-24 18:29:15'),(92422,'smartphone',1,NULL,'left','commercial',92421,NULL,NULL,0,'/societe/soc.php?leftmenu=suppliers&amp;action=create&amp;type=f','','NewSupplier','companies',1,'','$user->rights->societe->creer && $user->rights->fournisseur->lire','$conf->fournisseur->enabled',2,'2013-02-24 18:29:15'),(92423,'smartphone',1,NULL,'left','commercial',92421,NULL,NULL,1,'/fourn/liste.php?leftmenu=suppliers','','List','companies',1,'','$user->rights->societe->lire && $user->rights->fournisseur->lire','$conf->fournisseur->enabled',2,'2013-02-24 18:29:15'),(92424,'smartphone',1,NULL,'left','commercial',92421,NULL,NULL,2,'/contact/list.php?leftmenu=suppliers&amp;type=f','','Contacts','companies',1,'','$user->rights->societe->lire && $user->rights->fournisseur->lire','$conf->fournisseur->enabled',2,'2013-02-24 18:29:15'),(92521,'smartphone',1,NULL,'left','commercial',87426,NULL,NULL,6,'/fourn/commande/index.php?leftmenu=orders_suppliers','','SuppliersOrders','orders',0,'','$user->rights->fournisseur->commande->lire','$conf->commande->enabled',2,'2013-02-24 18:29:15'),(92522,'smartphone',1,NULL,'left','commercial',92521,NULL,NULL,0,'/societe/societe.php?leftmenu=orders_suppliers','','NewOrder','orders',1,'','$user->rights->fournisseur->commande->creer','$conf->commande->enabled && $leftmenu==\"orders_suppliers\"',2,'2013-02-24 18:29:15'),(92523,'smartphone',1,NULL,'left','commercial',92521,NULL,NULL,1,'/fourn/commande/liste.php?leftmenu=orders_suppliers&amp;viewstatut=0','','List','orders',1,'','$user->rights->fournisseur->commande->lire','$conf->commande->enabled && $leftmenu==\"orders_suppliers\"',2,'2013-02-24 18:29:15'),(92529,'smartphone',1,NULL,'left','commercial',92521,NULL,NULL,7,'/commande/stats/index.php?leftmenu=orders_suppliers&amp;mode=supplier','','Statistics','orders',1,'','$user->rights->fournisseur->commande->lire','$conf->commande->enabled && $leftmenu==\"orders_suppliers\"',2,'2013-02-24 18:29:15'),(92621,'smartphone',1,NULL,'left','members',87434,NULL,NULL,3,'/categories/index.php?leftmenu=cat&amp;type=3','','MembersCategoriesShort','categories',0,'','$user->rights->categorie->lire','$conf->adherent->enabled && $conf->categorie->enabled',2,'2013-02-24 18:29:15'),(92622,'smartphone',1,NULL,'left','members',92621,NULL,NULL,0,'/categories/fiche.php?action=create&amp;type=3','','NewCategory','categories',1,'','$user->rights->categorie->creer','$conf->adherent->enabled && $conf->categorie->enabled',2,'2013-02-24 18:29:15'),(103094,'all',2,'agenda','top','agenda',0,NULL,NULL,100,'/comm/action/index.php','','Agenda','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2013-03-13 15:29:19'),(103095,'all',2,'agenda','left','agenda',103094,NULL,NULL,100,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda','','Actions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2013-03-13 15:29:19'),(103096,'all',2,'agenda','left','agenda',103095,NULL,NULL,101,'/comm/action/fiche.php?mainmenu=agenda&amp;leftmenu=agenda&amp;action=create','','NewAction','commercial',NULL,NULL,'($user->rights->agenda->myactions->create||$user->rights->agenda->allactions->create)','$conf->agenda->enabled',2,'2013-03-13 15:29:19'),(103097,'all',2,'agenda','left','agenda',103095,NULL,NULL,102,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda','','Calendar','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2013-03-13 15:29:19'),(103098,'all',2,'agenda','left','agenda',103097,NULL,NULL,103,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine','','MenuToDoMyActions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2013-03-13 15:29:19'),(103099,'all',2,'agenda','left','agenda',103097,NULL,NULL,104,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine','','MenuDoneMyActions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2013-03-13 15:29:19'),(103100,'all',2,'agenda','left','agenda',103097,NULL,NULL,105,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo','','MenuToDoActions','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2013-03-13 15:29:19'),(103101,'all',2,'agenda','left','agenda',103097,NULL,NULL,106,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done','','MenuDoneActions','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2013-03-13 15:29:19'),(103102,'all',2,'agenda','left','agenda',103095,NULL,NULL,112,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda','','List','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2013-03-13 15:29:19'),(103103,'all',2,'agenda','left','agenda',103102,NULL,NULL,113,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine','','MenuToDoMyActions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2013-03-13 15:29:19'),(103104,'all',2,'agenda','left','agenda',103102,NULL,NULL,114,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine','','MenuDoneMyActions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2013-03-13 15:29:19'),(103105,'all',2,'agenda','left','agenda',103102,NULL,NULL,115,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo','','MenuToDoActions','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2013-03-13 15:29:19'),(103106,'all',2,'agenda','left','agenda',103102,NULL,NULL,116,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done','','MenuDoneActions','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2013-03-13 15:29:19'),(103107,'all',2,'agenda','left','agenda',103095,NULL,NULL,120,'/comm/action/rapport/index.php?mainmenu=agenda&amp;leftmenu=agenda','','Reportings','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$conf->agenda->enabled',2,'2013-03-13 15:29:19'),(103108,'all',2,'pos','top','pos',0,NULL,NULL,100,'/pos/backend/listefac.php','','POS','pos@pos',NULL,'1','1','1',2,'2013-03-13 20:33:09'),(103109,'all',2,'pos','left','pos',103108,NULL,NULL,100,'/pos/backend/liste.php','','Tickets','pos@pos',NULL,NULL,'$user->rights->pos->backend','$conf->global->POS_USE_TICKETS',0,'2013-03-13 20:33:09'),(103110,'all',2,'pos','left','pos',103109,NULL,NULL,100,'/pos/backend/liste.php','','List','main',NULL,NULL,'$user->rights->pos->backend','$conf->global->POS_USE_TICKETS',0,'2013-03-13 20:33:09'),(103111,'all',2,'pos','left','pos',103110,NULL,NULL,100,'/pos/backend/liste.php?viewstatut=0','','StatusTicketDraft','pos@pos',NULL,NULL,'$user->rights->pos->backend','$conf->global->POS_USE_TICKETS',0,'2013-03-13 20:33:09'),(103112,'all',2,'pos','left','@pos',103110,NULL,NULL,100,'/pos/backend/liste.php?viewstatut=1','','StatusTicketClosed','main',NULL,NULL,'$user->rights->pos->backend','$conf->global->POS_USE_TICKETS',0,'2013-03-13 20:33:09'),(103113,'all',2,'pos','left','@pos',103110,NULL,NULL,100,'/pos/backend/liste.php?viewstatut=2','','StatusTicketProcessed','main',NULL,NULL,'$user->rights->pos->backend','$conf->global->POS_USE_TICKETS',0,'2013-03-13 20:33:09'),(103114,'all',2,'pos','left','@pos',103110,NULL,NULL,100,'/pos/backend/liste.php?viewtype=1','','StatusTicketReturned','main',NULL,NULL,'$user->rights->pos->backend','$conf->global->POS_USE_TICKETS',0,'2013-03-13 20:33:09'),(103115,'all',2,'pos','left','pos',103108,NULL,NULL,100,'/pos/backend/listefac.php','','Factures','pos@pos',NULL,NULL,'$user->rights->pos->backend','1',0,'2013-03-13 20:33:09'),(103116,'all',2,'pos','left','pos',103115,NULL,NULL,100,'/pos/backend/listefac.php','','List','main',NULL,NULL,'$user->rights->pos->backend','1',0,'2013-03-13 20:33:09'),(103117,'all',2,'pos','left','pos',103116,NULL,NULL,100,'/pos/backend/listefac.php?viewstatut=0','','BillStatusDraft','bills',NULL,NULL,'$user->rights->pos->backend','1',0,'2013-03-13 20:33:09'),(103118,'all',2,'pos','left','@pos',103116,NULL,NULL,100,'/pos/backend/listefac.php?viewstatut=1','','BillStatusValidated','bills',NULL,NULL,'$user->rights->pos->backend','1',0,'2013-03-13 20:33:09'),(103119,'all',2,'pos','left','@pos',103116,NULL,NULL,100,'/pos/backend/listefac.php?viewstatut=2&viewtype=0','','BillStatusPaid','bills',NULL,NULL,'$user->rights->pos->backend','1',0,'2013-03-13 20:33:09'),(103120,'all',2,'pos','left','@pos',103116,NULL,NULL,100,'/pos/backend/listefac.php?viewtype=2','','BillStatusReturned','main',NULL,NULL,'$user->rights->pos->backend','1',0,'2013-03-13 20:33:09'),(103121,'all',2,'pos','left','@pos',103108,NULL,NULL,100,'/pos/frontend/index.php','','POS','main',NULL,NULL,'$user->rights->pos->frontend','1',0,'2013-03-13 20:33:09'),(103122,'all',2,'pos','left','@pos',103121,NULL,NULL,100,'/pos/frontend/index.php','','NewTicket','main',NULL,NULL,'$user->rights->pos->frontend','1',0,'2013-03-13 20:33:09'),(103123,'all',2,'pos','left','@pos',103121,NULL,NULL,101,'/pos/backend/closes.php','','CloseandArching','main',NULL,NULL,'$user->rights->pos->backend','1',0,'2013-03-13 20:33:09'),(103124,'all',2,'pos','left','@pos',103108,NULL,NULL,100,'/pos/backend/terminal/cash.php','','Terminal','main',NULL,NULL,'$user->rights->pos->backend','1',0,'2013-03-13 20:33:09'),(103125,'all',2,'pos','left','@pos',103124,NULL,NULL,100,'/pos/backend/terminal/fiche.php?action=create','','NewCash','main',NULL,NULL,'$user->rights->pos->backend','1',0,'2013-03-13 20:33:09'),(103126,'all',2,'pos','left','@pos',103124,NULL,NULL,101,'/pos/backend/terminal/cash.php','','List','main',NULL,NULL,'$user->rights->pos->backend','1',0,'2013-03-13 20:33:09'),(103127,'all',2,'pos','left','@pos',103123,NULL,NULL,101,'/pos/backend/closes.php?viewstatut=0','','Arqueo','main',NULL,NULL,'$user->rights->pos->backend','1',0,'2013-03-13 20:33:09'),(103128,'all',2,'pos','left','@pos',103123,NULL,NULL,102,'/pos/backend/closes.php?viewstatut=1','','Closes','main',NULL,NULL,'$user->rights->pos->backend','1',0,'2013-03-13 20:33:09'),(103129,'all',2,'pos','left','@pos',103108,NULL,NULL,102,'/pos/backend/transfers.php','','Transfer','main',NULL,NULL,'$user->rights->pos->transfer','1',0,'2013-03-13 20:33:09'),(103130,'all',2,'pos','left','@pos',103108,NULL,NULL,102,'/pos/backend/resultat/index.php','','Rapport','main',NULL,NULL,'$user->rights->pos->stats','1',0,'2013-03-13 20:33:09'),(103131,'all',2,'pos','left','@pos',103130,NULL,NULL,102,'/pos/backend/resultat/casoc.php','','ReportsCustomer','main',NULL,NULL,'$user->rights->pos->stats','1',0,'2013-03-13 20:33:09'),(103132,'all',2,'pos','left','@pos',103130,NULL,NULL,102,'/pos/backend/resultat/causer.php','','ReportsUser','main',NULL,NULL,'$user->rights->pos->stats','1',0,'2013-03-13 20:33:09'),(103133,'all',2,'pos','left','@pos',103130,NULL,NULL,102,'/pos/backend/resultat/sellsjournal.php','','ReportsSells','main',NULL,NULL,'$user->rights->pos->stats','1',0,'2013-03-13 20:33:09'),(103134,'all',2,'opensurvey','top','opensurvey',0,NULL,NULL,200,'/opensurvey/index.php','','Surveys','opensurvey',NULL,NULL,'$user->rights->opensurvey->survey->read','$conf->opensurvey->enabled',0,'2013-03-13 20:33:42'),(103135,'all',2,'opensurvey','left','opensurvey',-1,NULL,'opensurvey',200,'/opensurvey/index.php?mainmenu=opensurvey&leftmenu=opensurvey','','Survey','opensurvey@opensurvey',NULL,'opensurvey','','$conf->opensurvey->enabled',0,'2013-03-13 20:33:42'),(103136,'all',2,'opensurvey','left','opensurvey',-1,'opensurvey','opensurvey',210,'/opensurvey/public/index.php','_blank','NewSurvey','opensurvey@opensurvey',NULL,'opensurvey_new','','$conf->opensurvey->enabled',0,'2013-03-13 20:33:42'),(103137,'all',2,'opensurvey','left','opensurvey',-1,'opensurvey','opensurvey',220,'/opensurvey/list.php','','List','opensurvey@opensurvey',NULL,'opensurvey_list','','$conf->opensurvey->enabled',0,'2013-03-13 20:33:42'),(103160,'all',1,'cron','left','home',-1,'modulesadmintools','home',200,'/cron/list.php?status=1','','CronListActive','cron',NULL,NULL,'$user->rights->cron->read','$leftmenu==\'modulesadmintools\'',2,'2013-03-23 17:24:25'),(103161,'all',1,'cron','left','home',-1,'modulesadmintools','home',201,'/cron/list.php?status=0','','CronListInactive','cron',NULL,NULL,'$user->rights->cron->read','$leftmenu==\'modulesadmintools\'',2,'2013-03-23 17:24:25'),(103162,'auguria',1,'','top','home',0,NULL,NULL,1,'/index.php?mainmenu=home&amp;leftmenu=','','Home','',-1,'','','1',2,'2013-03-24 02:31:47'),(103163,'auguria',1,'societe|fournisseur','top','companies',0,NULL,NULL,2,'/societe/index.php?mainmenu=companies&amp;leftmenu=','','ThirdParties','companies',-1,'','$user->rights->societe->lire || $user->rights->societe->contact->lire','$conf->societe->enabled || $conf->fournisseur->enabled',2,'2013-03-24 02:31:47'),(103164,'auguria',1,'product|service','top','products',0,NULL,NULL,3,'/product/index.php?mainmenu=products&amp;leftmenu=','','Products/Services','products',-1,'','$user->rights->produit->lire||$user->rights->service->lire','$conf->product->enabled || $conf->service->enabled',0,'2013-03-24 02:31:47'),(103166,'auguria',1,'propal|commande|fournisseur|contrat|ficheinter','top','commercial',0,NULL,NULL,5,'/comm/index.php?mainmenu=commercial&amp;leftmenu=','','Commercial','commercial',-1,'','$user->rights->societe->lire || $user->rights->societe->contact->lire','$conf->comptabilite->enabled || $conf->accounting->enabled || $conf->facture->enabled || $conf->deplacement->enabled || $conf->don->enabled  || $conf->tax->enabled',2,'2013-03-24 02:31:47'),(103167,'auguria',1,'comptabilite|accounting|facture|deplacement|don|tax','top','accountancy',0,NULL,NULL,6,'/compta/index.php?mainmenu=accountancy&amp;leftmenu=','','MenuFinancial','compta',-1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->plancompte->lire || $user->rights->facture->lire|| $user->rights->deplacement->lire || $user->rights->don->lire || $user->rights->tax->charges->lire','$conf->comptabilite->enabled || $conf->accounting->enabled || $conf->facture->enabled || $conf->deplacement->enabled || $conf->don->enabled  || $conf->tax->enabled',2,'2013-03-24 02:31:47'),(103168,'auguria',1,'projet','top','project',0,NULL,NULL,7,'/projet/index.php?mainmenu=project&amp;leftmenu=','','Projects','projects',-1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(103169,'auguria',1,'mailing|export|import','top','tools',0,NULL,NULL,8,'/core/tools.php?mainmenu=tools&amp;leftmenu=','','Tools','other',-1,'','$user->rights->mailing->lire || $user->rights->export->lire || $user->rights->import->run','$conf->mailing->enabled || $conf->export->enabled || $conf->import->enabled',2,'2013-03-24 02:31:47'),(103172,'auguria',1,'boutique','top','shop',0,NULL,NULL,11,'/boutique/index.php?mainmenu=shop&amp;leftmenu=','','OSCommerce','shop',-1,'','','! empty($conf->boutique->enabled)',0,'2013-03-24 02:31:47'),(103174,'auguria',1,'adherent','top','members',0,NULL,NULL,15,'/adherents/index.php?mainmenu=members&amp;leftmenu=','','Members','members',-1,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-03-24 02:31:47'),(103175,'auguria',1,'banque|prelevement','top','bank',0,NULL,NULL,6,'/compta/bank/index.php?mainmenu=bank&amp;leftmenu=bank','','MenuBankCash','banks',-1,'','$user->rights->banque->lire || $user->rights->prelevement->bons->lire','$conf->banque->enabled || $conf->prelevement->enabled',0,'2013-03-24 02:31:47'),(103261,'auguria',1,'','left','home',103162,NULL,NULL,0,'/admin/index.php?leftmenu=setup','','Setup','admin',0,'setup','','$user->admin',2,'2013-03-24 02:31:47'),(103262,'auguria',1,'','left','home',103261,NULL,NULL,1,'/admin/company.php?leftmenu=setup','','MenuCompanySetup','admin',1,'','','$leftmenu==\'setup\'',2,'2013-03-24 02:31:47'),(103263,'auguria',1,'','left','home',103261,NULL,NULL,4,'/admin/ihm.php?leftmenu=setup','','GUISetup','admin',1,'','','$leftmenu==\'setup\'',2,'2013-03-24 02:31:47'),(103264,'auguria',1,'','left','home',103261,NULL,NULL,2,'/admin/modules.php?leftmenu=setup','','Modules','admin',1,'','','$leftmenu==\'setup\'',2,'2013-03-24 02:31:47'),(103265,'auguria',1,'','left','home',103261,NULL,NULL,5,'/admin/boxes.php?leftmenu=setup','','Boxes','admin',1,'','','$leftmenu==\'setup\'',2,'2013-03-24 02:31:47'),(103266,'auguria',1,'','left','home',103261,NULL,NULL,3,'/admin/menus.php?leftmenu=setup','','Menus','admin',1,'','','$leftmenu==\'setup\'',2,'2013-03-24 02:31:47'),(103267,'auguria',1,'','left','home',103261,NULL,NULL,6,'/admin/delais.php?leftmenu=setup','','Alerts','admin',1,'','','$leftmenu==\'setup\'',2,'2013-03-24 02:31:47'),(103268,'auguria',1,'','left','home',103261,NULL,NULL,9,'/admin/pdf.php?leftmenu=setup','','PDF','admin',1,'','','$leftmenu==\'setup\'',2,'2013-03-24 02:31:47'),(103269,'auguria',1,'','left','home',103261,NULL,NULL,7,'/admin/proxy.php?leftmenu=setup','','Security','admin',1,'','','$leftmenu==\'setup\'',2,'2013-03-24 02:31:47'),(103270,'auguria',1,'','left','home',103261,NULL,NULL,10,'/admin/mails.php?leftmenu=setup','','Emails','admin',1,'','','$leftmenu==\'setup\'',2,'2013-03-24 02:31:47'),(103271,'auguria',1,'','left','home',103261,NULL,NULL,8,'/admin/limits.php?leftmenu=setup','','MenuLimits','admin',1,'','','$leftmenu==\'setup\'',2,'2013-03-24 02:31:47'),(103272,'auguria',1,'','left','home',103261,NULL,NULL,12,'/admin/dict.php?leftmenu=setup','','DictionnarySetup','admin',1,'','','$leftmenu==\'setup\'',2,'2013-03-24 02:31:47'),(103273,'auguria',1,'','left','home',103261,NULL,NULL,13,'/admin/const.php?leftmenu=setup','','OtherSetup','admin',1,'','','$leftmenu==\'setup\'',2,'2013-03-24 02:31:47'),(103274,'auguria',1,'','left','home',103261,NULL,NULL,11,'/admin/sms.php?leftmenu=setup','','Sms','admin',1,'','','$leftmenu==\'setup\'',2,'2013-03-24 02:31:47'),(103362,'auguria',1,'','left','home',103461,NULL,NULL,0,'/admin/system/dolibarr.php?leftmenu=admintools','','InfoDolibarr','admin',1,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103364,'auguria',1,'','left','home',103461,NULL,NULL,13,'/admin/system/about.php?leftmenu=admintools','','About','admin',1,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103365,'auguria',1,'','left','home',103461,NULL,NULL,1,'/admin/system/os.php?leftmenu=admintools','','InfoOS','admin',1,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103366,'auguria',1,'','left','home',103461,NULL,NULL,2,'/admin/system/web.php?leftmenu=admintools','','InfoWebServer','admin',1,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103367,'auguria',1,'','left','home',103461,NULL,NULL,3,'/admin/system/phpinfo.php?leftmenu=admintools','','InfoPHP','admin',1,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103368,'auguria',1,'','left','home',103362,NULL,NULL,3,'/admin/triggers.php?leftmenu=admintools','','Triggers','admin',2,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103369,'auguria',1,'','left','home',103362,NULL,NULL,2,'/admin/system/modules.php?leftmenu=admintools','','Modules','admin',2,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103371,'auguria',1,'','left','home',103461,NULL,NULL,4,'/admin/system/database.php?leftmenu=admintools','','InfoDatabase','admin',1,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103461,'auguria',1,'','left','home',103162,NULL,NULL,2,'/admin/tools/index.php?leftmenu=admintools','','SystemTools','admin',0,'admintools','','$user->admin',2,'2013-03-24 02:31:47'),(103462,'auguria',1,'','left','home',103461,NULL,NULL,5,'/admin/tools/dolibarr_export.php?leftmenu=admintools','','Backup','admin',1,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103463,'auguria',1,'','left','home',103461,NULL,NULL,6,'/admin/tools/dolibarr_import.php?leftmenu=admintools','','Restore','admin',1,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103464,'auguria',1,'','left','home',103461,NULL,NULL,11,'/admin/tools/purge.php?leftmenu=admintools','','Purge','admin',1,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103465,'auguria',1,'','left','home',103461,NULL,NULL,8,'/admin/tools/eaccelerator.php?leftmenu=admintools','','EAccelerator','admin',1,'','','$leftmenu==\'admintools\' && function_exists(\'eaccelerator_info\')',2,'2013-03-24 02:31:47'),(103466,'auguria',1,'','left','home',103461,NULL,NULL,7,'/admin/tools/update.php?leftmenu=admintools','','MenuUpgrade','admin',1,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103467,'auguria',1,'','left','home',103461,NULL,NULL,9,'/admin/tools/listevents.php?leftmenu=admintools','','Audit','admin',1,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103468,'auguria',1,'','left','home',103461,NULL,NULL,12,'/support/index.php?leftmenu=admintools','_blank','HelpCenter','help',1,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103469,'auguria',1,'','left','home',103461,NULL,NULL,10,'/admin/tools/listsessions.php?leftmenu=admintools','','Sessions','admin',1,'','','$leftmenu==\'admintools\'',2,'2013-03-24 02:31:47'),(103561,'auguria',1,'','left','home',103162,NULL,NULL,3,'/user/home.php?leftmenu=users','','MenuUsersAndGroups','users',0,'users','','1',2,'2013-03-24 02:31:47'),(103562,'auguria',1,'','left','home',103561,NULL,NULL,0,'/user/index.php?leftmenu=users','','Users','users',1,'','$user->rights->user->user->lire || $user->admin','$leftmenu==\'users\'',2,'2013-03-24 02:31:47'),(103563,'auguria',1,'','left','home',103562,NULL,NULL,0,'/user/fiche.php?leftmenu=users&amp;action=create','','NewUser','users',2,'','$user->rights->user->user->creer || $user->admin','$leftmenu==\'users\'',2,'2013-03-24 02:31:47'),(103564,'auguria',1,'','left','home',103561,NULL,NULL,1,'/user/group/index.php?leftmenu=users','','Groups','users',1,'','($conf->global->MAIN_USE_ADVANCED_PERMS?$user->rights->user->group_advance->read:$user->rights->user->user->lire) || $user->admin','$leftmenu==\'users\'',2,'2013-03-24 02:31:47'),(103565,'auguria',1,'','left','home',103564,NULL,NULL,0,'/user/group/fiche.php?leftmenu=users&amp;action=create','','NewGroup','users',2,'','($conf->global->MAIN_USE_ADVANCED_PERMS?$user->rights->user->group_advance->write:$user->rights->user->user->creer) || $user->admin','$leftmenu==\'users\'',2,'2013-03-24 02:31:47'),(103661,'auguria',1,'','left','companies',103163,NULL,NULL,0,'/societe/index.php?leftmenu=thirdparties','','ThirdParty','companies',0,'thirdparties','$user->rights->societe->lire','$conf->societe->enabled',2,'2013-03-24 02:31:47'),(103662,'auguria',1,'','left','companies',103661,NULL,NULL,0,'/societe/soc.php?action=create','','MenuNewThirdParty','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2013-03-24 02:31:47'),(103664,'auguria',1,'','left','companies',103661,NULL,NULL,5,'/fourn/liste.php?leftmenu=suppliers','','ListSuppliersShort','suppliers',1,'','$user->rights->societe->lire && $user->rights->fournisseur->lire','$conf->societe->enabled && $conf->fournisseur->enabled',2,'2013-03-24 02:31:47'),(103665,'auguria',1,'','left','companies',103664,NULL,NULL,0,'/societe/soc.php?leftmenu=supplier&amp;action=create&amp;type=f','','NewSupplier','suppliers',2,'','$user->rights->societe->creer','$conf->societe->enabled && $conf->fournisseur->enabled',2,'2013-03-24 02:31:47'),(103667,'auguria',1,'','left','companies',103661,NULL,NULL,3,'/comm/prospect/list.php?leftmenu=prospects','','ListProspectsShort','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2013-03-24 02:31:47'),(103668,'auguria',1,'','left','companies',103667,NULL,NULL,0,'/societe/soc.php?leftmenu=prospects&amp;action=create&amp;type=p','','MenuNewProspect','companies',2,'','$user->rights->societe->creer','$conf->societe->enabled',2,'2013-03-24 02:31:47'),(103670,'auguria',1,'','left','companies',103661,NULL,NULL,4,'/comm/list.php?leftmenu=customers','','ListCustomersShort','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2013-03-24 02:31:47'),(103671,'auguria',1,'','left','companies',103670,NULL,NULL,0,'/societe/soc.php?leftmenu=customers&amp;action=create&amp;type=c','','MenuNewCustomer','companies',2,'','$user->rights->societe->creer','$conf->societe->enabled',2,'2013-03-24 02:31:47'),(103761,'auguria',1,'','left','companies',103163,NULL,NULL,1,'/contact/list.php?leftmenu=contacts','','ContactsAddresses','companies',0,'contacts','$user->rights->societe->lire','$conf->societe->enabled',2,'2013-03-24 02:31:47'),(103762,'auguria',1,'','left','companies',103761,NULL,NULL,0,'/contact/fiche.php?leftmenu=contacts&amp;action=create','','NewContactAddress','companies',1,'','$user->rights->societe->creer','$conf->societe->enabled',2,'2013-03-24 02:31:47'),(103763,'auguria',1,'','left','companies',103761,NULL,NULL,1,'/contact/list.php?leftmenu=contacts','','List','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2013-03-24 02:31:47'),(103765,'auguria',1,'','left','companies',103763,NULL,NULL,1,'/contact/list.php?leftmenu=contacts&amp;type=p','','Prospects','companies',2,'','$user->rights->societe->contact->lire','$conf->societe->enabled',2,'2013-03-24 02:31:47'),(103766,'auguria',1,'','left','companies',103763,NULL,NULL,2,'/contact/list.php?leftmenu=contacts&amp;type=c','','Customers','companies',2,'','$user->rights->societe->contact->lire','$conf->societe->enabled',2,'2013-03-24 02:31:47'),(103767,'auguria',1,'','left','companies',103763,NULL,NULL,3,'/contact/list.php?leftmenu=contacts&amp;type=f','','Suppliers','companies',2,'','$user->rights->societe->contact->lire','$conf->societe->enabled && $conf->fournisseur->enabled',2,'2013-03-24 02:31:47'),(103768,'auguria',1,'','left','companies',103763,NULL,NULL,4,'/contact/list.php?leftmenu=contacts&amp;type=o','','Others','companies',2,'','$user->rights->societe->contact->lire','$conf->societe->enabled',2,'2013-03-24 02:31:47'),(103811,'auguria',1,'','left','companies',103163,NULL,NULL,3,'/categories/index.php?leftmenu=cat&amp;type=1','','SuppliersCategoriesShort','categories',0,'cat','$user->rights->categorie->lire','$conf->societe->enabled && $conf->categorie->enabled',2,'2013-03-24 02:31:47'),(103812,'auguria',1,'','left','companies',103811,NULL,NULL,0,'/categories/fiche.php?action=create&amp;type=1','','NewCategory','categories',1,'','$user->rights->categorie->creer','$conf->societe->enabled && $conf->categorie->enabled',2,'2013-03-24 02:31:47'),(103821,'auguria',1,'','left','companies',103163,NULL,NULL,4,'/categories/index.php?leftmenu=cat&amp;type=2','','CustomersProspectsCategoriesShort','categories',0,'cat','$user->rights->categorie->lire','$conf->fournisseur->enabled && $conf->categorie->enabled',2,'2013-03-24 02:31:47'),(103822,'auguria',1,'','left','companies',103821,NULL,NULL,0,'/categories/fiche.php?action=create&amp;type=2','','NewCategory','categories',1,'','$user->rights->categorie->creer','$conf->fournisseur->enabled && $conf->categorie->enabled',2,'2013-03-24 02:31:47'),(104261,'auguria',1,'','left','commercial',103166,NULL,NULL,4,'/comm/propal/index.php?leftmenu=propals','','Prop','propal',0,'propals','$user->rights->propale->lire','$conf->propal->enabled',2,'2013-03-24 02:31:47'),(104262,'auguria',1,'','left','commercial',104261,NULL,NULL,0,'/societe/societe.php?leftmenu=propals','','NewPropal','propal',1,'','$user->rights->propale->creer','$conf->propal->enabled',2,'2013-03-24 02:31:47'),(104263,'auguria',1,'','left','commercial',104261,NULL,NULL,1,'/comm/propal/list.php?leftmenu=propals','','List','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled',2,'2013-03-24 02:31:47'),(104264,'auguria',1,'','left','commercial',104263,NULL,NULL,2,'/comm/propal/list.php?leftmenu=propals&amp;viewstatut=0','','PropalsDraft','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2013-03-24 02:31:47'),(104265,'auguria',1,'','left','commercial',104263,NULL,NULL,3,'/comm/propal/list.php?leftmenu=propals&amp;viewstatut=1','','PropalsOpened','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2013-03-24 02:31:47'),(104266,'auguria',1,'','left','commercial',104263,NULL,NULL,4,'/comm/propal/list.php?leftmenu=propals&amp;viewstatut=2','','PropalStatusSigned','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2013-03-24 02:31:47'),(104267,'auguria',1,'','left','commercial',104263,NULL,NULL,5,'/comm/propal/list.php?leftmenu=propals&amp;viewstatut=3','','PropalStatusNotSigned','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2013-03-24 02:31:47'),(104268,'auguria',1,'','left','commercial',104263,NULL,NULL,6,'/comm/propal/list.php?leftmenu=propals&amp;viewstatut=4','','PropalStatusBilled','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled && $leftmenu==\"propals\"',2,'2013-03-24 02:31:47'),(104271,'auguria',1,'','left','commercial',104261,NULL,NULL,4,'/comm/propal/stats/index.php?leftmenu=propals','','Statistics','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled',2,'2013-03-24 02:31:47'),(104361,'auguria',1,'','left','commercial',103166,NULL,NULL,5,'/commande/index.php?leftmenu=orders','','CustomersOrders','orders',0,'orders','$user->rights->commande->lire','$conf->commande->enabled',2,'2013-03-24 02:31:47'),(104362,'auguria',1,'','left','commercial',104361,NULL,NULL,0,'/societe/societe.php?leftmenu=orders','','NewOrder','orders',1,'','$user->rights->commande->creer','$conf->commande->enabled',2,'2013-03-24 02:31:47'),(104363,'auguria',1,'','left','commercial',104361,NULL,NULL,1,'/commande/liste.php?leftmenu=orders','','List','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled',2,'2013-03-24 02:31:47'),(104364,'auguria',1,'','left','commercial',104363,NULL,NULL,2,'/commande/liste.php?leftmenu=orders&amp;viewstatut=0','','StatusOrderDraftShort','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-03-24 02:31:47'),(104365,'auguria',1,'','left','commercial',104363,NULL,NULL,3,'/commande/liste.php?leftmenu=orders&amp;viewstatut=1','','StatusOrderValidated','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-03-24 02:31:47'),(104366,'auguria',1,'','left','commercial',104363,NULL,NULL,4,'/commande/liste.php?leftmenu=orders&amp;viewstatut=2','','StatusOrderOnProcessShort','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-03-24 02:31:47'),(104367,'auguria',1,'','left','commercial',104363,NULL,NULL,5,'/commande/liste.php?leftmenu=orders&amp;viewstatut=3','','StatusOrderToBill','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-03-24 02:31:47'),(104368,'auguria',1,'','left','commercial',104363,NULL,NULL,6,'/commande/liste.php?leftmenu=orders&amp;viewstatut=4','','StatusOrderProcessed','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-03-24 02:31:47'),(104369,'auguria',1,'','left','commercial',104363,NULL,NULL,7,'/commande/liste.php?leftmenu=orders&amp;viewstatut=-1','','StatusOrderCanceledShort','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled && $leftmenu==\"orders\"',2,'2013-03-24 02:31:47'),(104370,'auguria',1,'','left','commercial',104361,NULL,NULL,4,'/commande/stats/index.php?leftmenu=orders','','Statistics','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled',2,'2013-03-24 02:31:47'),(104461,'auguria',1,'','left','commercial',103164,NULL,NULL,6,'/expedition/index.php?leftmenu=sendings','','Shipments','sendings',0,'sendings','$user->rights->expedition->lire','$conf->expedition->enabled',2,'2013-03-24 02:31:47'),(104462,'auguria',1,'','left','commercial',104461,NULL,NULL,0,'/expedition/fiche.php?action=create2&leftmenu=sendings','','NewSending','sendings',1,'','$user->rights->expedition->creer','$conf->expedition->enabled && $leftmenu==\"sendings\"',2,'2013-03-24 02:31:47'),(104463,'auguria',1,'','left','commercial',104461,NULL,NULL,1,'/expedition/liste.php?leftmenu=sendings','','List','sendings',1,'','$user->rights->expedition->lire','$conf->expedition->enabled && $leftmenu==\"sendings\"',2,'2013-03-24 02:31:47'),(104464,'auguria',1,'','left','commercial',104461,NULL,NULL,2,'/expedition/stats/index.php?leftmenu=sendings','','Statistics','sendings',1,'','$user->rights->expedition->lire','$conf->expedition->enabled && $leftmenu==\"sendings\"',2,'2013-03-24 02:31:47'),(104561,'auguria',1,'','left','commercial',103166,NULL,NULL,7,'/contrat/index.php?leftmenu=contracts','','Contracts','contracts',0,'contracts','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2013-03-24 02:31:47'),(104562,'auguria',1,'','left','commercial',104561,NULL,NULL,0,'/societe/societe.php?leftmenu=contracts','','NewContract','contracts',1,'','$user->rights->contrat->creer','$conf->contrat->enabled',2,'2013-03-24 02:31:47'),(104563,'auguria',1,'','left','commercial',104561,NULL,NULL,1,'/contrat/liste.php?leftmenu=contracts','','List','contracts',1,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2013-03-24 02:31:47'),(104564,'auguria',1,'','left','commercial',104561,NULL,NULL,2,'/contrat/services.php?leftmenu=contracts','','MenuServices','contracts',1,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2013-03-24 02:31:47'),(104565,'auguria',1,'','left','commercial',104564,NULL,NULL,0,'/contrat/services.php?leftmenu=contracts&amp;mode=0','','MenuInactiveServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled&&$leftmenu==\"contracts\"',2,'2013-03-24 02:31:47'),(104566,'auguria',1,'','left','commercial',104564,NULL,NULL,1,'/contrat/services.php?leftmenu=contracts&amp;mode=4','','MenuRunningServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled&&$leftmenu==\"contracts\"',2,'2013-03-24 02:31:47'),(104567,'auguria',1,'','left','commercial',104564,NULL,NULL,2,'/contrat/services.php?leftmenu=contracts&amp;mode=4&amp;filter=expired','','MenuExpiredServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled&&$leftmenu==\"contracts\"',2,'2013-03-24 02:31:47'),(104568,'auguria',1,'','left','commercial',104564,NULL,NULL,3,'/contrat/services.php?leftmenu=contracts&amp;mode=5','','MenuClosedServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled&&$leftmenu==\"contracts\"',2,'2013-03-24 02:31:47'),(104661,'auguria',1,'','left','commercial',103166,NULL,NULL,8,'/fichinter/list.php?leftmenu=ficheinter','','Interventions','interventions',0,'ficheinter','$user->rights->ficheinter->lire','$conf->ficheinter->enabled',2,'2013-03-24 02:31:47'),(104662,'auguria',1,'','left','commercial',104661,NULL,NULL,0,'/fichinter/fiche.php?action=create&amp;leftmenu=ficheinter','','NewIntervention','interventions',1,'','$user->rights->ficheinter->creer','$conf->ficheinter->enabled',2,'2013-03-24 02:31:47'),(104663,'auguria',1,'','left','commercial',104661,NULL,NULL,1,'/fichinter/list.php?leftmenu=ficheinter','','List','interventions',1,'','$user->rights->ficheinter->lire','$conf->ficheinter->enabled',2,'2013-03-24 02:31:47'),(104761,'auguria',1,'','left','accountancy',103167,NULL,NULL,3,'/fourn/facture/index.php?leftmenu=suppliers_bills','','BillsSuppliers','bills',0,'supplier_bills','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled',2,'2013-03-24 02:31:47'),(104762,'auguria',1,'','left','accountancy',104761,NULL,NULL,0,'/fourn/facture/fiche.php?action=create&amp;leftmenu=suppliers_bills','','NewBill','bills',1,'','$user->rights->fournisseur->facture->creer','$conf->fournisseur->enabled',2,'2013-03-24 02:31:47'),(104763,'auguria',1,'','left','accountancy',104761,NULL,NULL,1,'/fourn/facture/impayees.php?leftmenu=suppliers_bills','','Unpaid','bills',1,'','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled',2,'2013-03-24 02:31:47'),(104764,'auguria',1,'','left','accountancy',104761,NULL,NULL,2,'/fourn/facture/paiement.php?leftmenu=suppliers_bills','','Payments','bills',1,'','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled',2,'2013-03-24 02:31:47'),(104765,'auguria',1,'','left','accountancy',104761,NULL,NULL,8,'/compta/facture/stats/index.php?leftmenu=customers_bills&mode=supplier','','Statistics','bills',1,'','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled',2,'2013-03-24 02:31:47'),(104861,'auguria',1,'','left','accountancy',103167,NULL,NULL,3,'/compta/facture/list.php?leftmenu=customers_bills','','BillsCustomers','bills',0,'customer_bills','$user->rights->facture->lire','$conf->facture->enabled',2,'2013-03-24 02:31:47'),(104862,'auguria',1,'','left','accountancy',104861,NULL,NULL,3,'/compta/clients.php?action=facturer&amp;leftmenu=customers_bills','','NewBill','bills',1,'','$user->rights->facture->creer','$conf->facture->enabled',2,'2013-03-24 02:31:47'),(104863,'auguria',1,'','left','accountancy',104861,NULL,NULL,4,'/compta/facture/fiche-rec.php?leftmenu=customers_bills','','Repeatable','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled',2,'2013-03-24 02:31:47'),(104864,'auguria',1,'','left','accountancy',104861,NULL,NULL,5,'/compta/facture/impayees.php?action=facturer&amp;leftmenu=customers_bills','','Unpaid','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled',2,'2013-03-24 02:31:47'),(104865,'auguria',1,'','left','accountancy',104861,NULL,NULL,6,'/compta/paiement/liste.php?leftmenu=customers_bills','','Payments','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled',2,'2013-03-24 02:31:47'),(104871,'auguria',1,'','left','accountancy',104865,NULL,NULL,1,'/compta/paiement/rapport.php?leftmenu=customers_bills','','Reportings','bills',2,'','$user->rights->facture->lire','$conf->facture->enabled',2,'2013-03-24 02:31:47'),(104872,'auguria',1,'','left','accountancy',103175,NULL,NULL,9,'/compta/paiement/cheque/index.php?leftmenu=checks&amp;mainmenu=bank','','MenuChequeDeposits','bills',0,'checks','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled',2,'2013-03-24 02:31:47'),(104873,'auguria',1,'','left','accountancy',104872,NULL,NULL,0,'/compta/paiement/cheque/fiche.php?leftmenu=checks&amp;action=new','','NewCheckDeposit','compta',1,'','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled',2,'2013-03-24 02:31:47'),(104874,'auguria',1,'','left','accountancy',104872,NULL,NULL,1,'/compta/paiement/cheque/liste.php?leftmenu=checks','','List','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled',2,'2013-03-24 02:31:47'),(104875,'auguria',1,'','left','accountancy',104861,NULL,NULL,8,'/compta/facture/stats/index.php?leftmenu=customers_bills','','Statistics','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled',2,'2013-03-24 02:31:47'),(105061,'auguria',1,'','left','accountancy',103167,NULL,NULL,3,'/commande/liste.php?leftmenu=orders&amp;viewstatut=3','','MenuOrdersToBill','orders',0,'orders','$user->rights->commande->lire','$conf->commande->enabled',0,'2013-03-24 02:31:47'),(105161,'auguria',1,'','left','accountancy',103167,NULL,NULL,4,'/compta/dons/index.php?leftmenu=donations&amp;mainmenu=accountancy','','Donations','donations',0,'donations','$user->rights->don->lire','$conf->don->enabled',2,'2013-03-24 02:31:47'),(105162,'auguria',1,'','left','accountancy',105161,NULL,NULL,0,'/compta/dons/fiche.php?leftmenu=donations&amp;mainmenu=accountancy&amp;action=create','','NewDonation','donations',1,'','$user->rights->don->creer','$conf->don->enabled && $leftmenu==\"donations\"',2,'2013-03-24 02:31:47'),(105163,'auguria',1,'','left','accountancy',105161,NULL,NULL,1,'/compta/dons/liste.php?leftmenu=donations&amp;mainmenu=accountancy','','List','donations',1,'','$user->rights->don->lire','$conf->don->enabled && $leftmenu==\"donations\"',2,'2013-03-24 02:31:47'),(105261,'auguria',1,'','left','accountancy',103167,NULL,NULL,5,'/compta/deplacement/index.php?leftmenu=tripsandexpenses','','TripsAndExpenses','trips',0,'tripsandexpenses','$user->rights->deplacement->lire','$conf->deplacement->enabled',0,'2013-03-24 02:31:47'),(105262,'auguria',1,'','left','accountancy',105261,NULL,NULL,1,'/compta/deplacement/fiche.php?action=create&amp;leftmenu=tripsandexpenses','','New','trips',1,'','$user->rights->deplacement->creer','$conf->deplacement->enabled && $leftmenu==\"tripsandexpenses\"',0,'2013-03-24 02:31:47'),(105263,'auguria',1,'','left','accountancy',105261,NULL,NULL,2,'/compta/deplacement/list.php?leftmenu=tripsandexpenses','','List','trips',1,'','$user->rights->deplacement->lire','$conf->deplacement->enabled && $leftmenu==\"tripsandexpenses\"',0,'2013-03-24 02:31:47'),(105264,'auguria',1,'','left','accountancy',105261,NULL,NULL,2,'/compta/deplacement/stats/index.php?leftmenu=tripsandexpenses','','Statistics','trips',1,'','$user->rights->deplacement->lire','$conf->deplacement->enabled && $leftmenu==\"tripsandexpenses\"',0,'2013-03-24 02:31:47'),(105361,'auguria',1,'','left','accountancy',103167,NULL,NULL,6,'/compta/charges/index.php?leftmenu=tax&amp;mainmenu=accountancy','','MenuTaxAndDividends','compta',0,'tax','$user->rights->tax->charges->lire','$conf->tax->enabled',0,'2013-03-24 02:31:47'),(105362,'auguria',1,'','left','accountancy',105361,NULL,NULL,1,'/compta/sociales/index.php?leftmenu=tax_social','','SocialContributions','',1,'tax_social','$user->rights->tax->charges->lire','$conf->tax->enabled',0,'2013-03-24 02:31:47'),(105363,'auguria',1,'','left','accountancy',105362,NULL,NULL,2,'/compta/sociales/charges.php?leftmenu=tax_social&amp;action=create','','MenuNewSocialContribution','',2,'','$user->rights->tax->charges->creer','$conf->tax->enabled && $leftmenu==\"tax_social\"',0,'2013-03-24 02:31:47'),(105364,'auguria',1,'','left','accountancy',105362,NULL,NULL,3,'/compta/charges/index.php?leftmenu=tax_social&amp;mainmenu=accountancy&amp;mode=sconly','','Payments','',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && $leftmenu==\"tax_social\"',0,'2013-03-24 02:31:47'),(105461,'auguria',1,'','left','accountancy',105361,NULL,NULL,7,'/compta/tva/index.php?leftmenu=tax_vat&amp;mainmenu=accountancy','','VAT','companies',1,'tax_vat','$user->rights->tax->charges->lire','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS)',0,'2013-03-24 02:31:47'),(105462,'auguria',1,'','left','accountancy',105461,NULL,NULL,0,'/compta/tva/fiche.php?leftmenu=tax_vat&amp;action=create','','NewPayment','companies',2,'','$user->rights->tax->charges->creer','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS) && $leftmenu==\"tax_vat\"',0,'2013-03-24 02:31:47'),(105463,'auguria',1,'','left','accountancy',105461,NULL,NULL,1,'/compta/tva/reglement.php?leftmenu=tax_vat','','Payments','companies',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS) && $leftmenu==\"tax_vat\"',0,'2013-03-24 02:31:47'),(105464,'auguria',1,'','left','accountancy',105461,NULL,NULL,2,'/compta/tva/clients.php?leftmenu=tax_vat','','ReportByCustomers','companies',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS) && $leftmenu==\"tax_vat\"',0,'2013-03-24 02:31:47'),(105465,'auguria',1,'','left','accountancy',105461,NULL,NULL,3,'/compta/tva/quadri_detail.php?leftmenu=tax_vat','','ReportByQuarter','companies',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && empty($conf->global->TAX_DISABLE_VAT_MENUS) && $leftmenu==\"tax_vat\"',0,'2013-03-24 02:31:47'),(105561,'auguria',1,'','left','accountancy',103167,NULL,NULL,8,'/compta/ventilation/index.php?leftmenu=ventil','','Ventilation','companies',0,'ventil','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-03-24 02:31:47'),(105562,'auguria',1,'','left','accountancy',105561,NULL,NULL,0,'/compta/ventilation/liste.php','','ToDispatch','companies',1,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-03-24 02:31:47'),(105563,'auguria',1,'','left','accountancy',105561,NULL,NULL,1,'/compta/ventilation/lignes.php','','Dispatched','companies',1,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-03-24 02:31:47'),(105564,'auguria',1,'','left','accountancy',105561,NULL,NULL,2,'/compta/param/','','Setup','companies',1,'','$user->rights->compta->ventilation->parametrer','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-03-24 02:31:47'),(105565,'auguria',1,'','left','accountancy',105564,NULL,NULL,0,'/compta/param/comptes/liste.php','','List','companies',2,'','$user->rights->compta->ventilation->parametrer','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-03-24 02:31:47'),(105566,'auguria',1,'','left','accountancy',105564,NULL,NULL,1,'/compta/param/comptes/fiche.php?action=create','','New','companies',2,'','$user->rights->compta->ventilation->parametrer','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-03-24 02:31:47'),(105567,'auguria',1,'','left','accountancy',105561,NULL,NULL,3,'/compta/export/','','Export','companies',1,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-03-24 02:31:47'),(105568,'auguria',1,'','left','accountancy',105567,NULL,NULL,0,'/compta/export/index.php','','New','companies',2,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-03-24 02:31:47'),(105569,'auguria',1,'','left','accountancy',105567,NULL,NULL,1,'/compta/export/liste.php','','List','companies',2,'','$user->rights->compta->ventilation->lire','$conf->comptabilite->enabled && $conf->global->FACTURE_VENTILATION',0,'2013-03-24 02:31:47'),(105661,'auguria',1,'','left','accountancy',103175,NULL,NULL,9,'/compta/prelevement/index.php?leftmenu=withdraw&amp;mainmenu=bank','','StandingOrders','withdrawals',0,'withdraw','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled',2,'2013-03-24 02:31:47'),(105663,'auguria',1,'','left','accountancy',105661,NULL,NULL,0,'/compta/prelevement/create.php?leftmenu=withdraw','','NewStandingOrder','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2013-03-24 02:31:47'),(105664,'auguria',1,'','left','accountancy',105661,NULL,NULL,2,'/compta/prelevement/bons.php?leftmenu=withdraw','','WithdrawalsReceipts','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2013-03-24 02:31:47'),(105665,'auguria',1,'','left','accountancy',105661,NULL,NULL,3,'/compta/prelevement/liste.php?leftmenu=withdraw','','WithdrawalsLines','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2013-03-24 02:31:47'),(105667,'auguria',1,'','left','accountancy',105661,NULL,NULL,5,'/compta/prelevement/rejets.php?leftmenu=withdraw','','Rejects','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2013-03-24 02:31:47'),(105668,'auguria',1,'','left','accountancy',105661,NULL,NULL,6,'/compta/prelevement/stats.php?leftmenu=withdraw','','Statistics','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2013-03-24 02:31:47'),(105761,'auguria',1,'','left','accountancy',103175,NULL,NULL,1,'/compta/bank/index.php?leftmenu=bank&amp;mainmenu=bank','','MenuBankCash','banks',0,'bank','$user->rights->banque->lire','$conf->banque->enabled',0,'2013-03-24 02:31:47'),(105762,'auguria',1,'','left','accountancy',105761,NULL,NULL,0,'/compta/bank/fiche.php?action=create&amp;leftmenu=bank','','MenuNewFinancialAccount','banks',1,'','$user->rights->banque->configurer','$conf->banque->enabled && ($leftmenu==\"bank\" || $leftmenu==\"checks\" || $leftmenu==\"withdraw\")',0,'2013-03-24 02:31:47'),(105763,'auguria',1,'','left','accountancy',105761,NULL,NULL,1,'/compta/bank/categ.php?leftmenu=bank','','Rubriques','categories',1,'','$user->rights->banque->configurer','$conf->banque->enabled && ($leftmenu==\"bank\" || $leftmenu==\"checks\" || $leftmenu==\"withdraw\")',0,'2013-03-24 02:31:47'),(105764,'auguria',1,'','left','accountancy',105761,NULL,NULL,2,'/compta/bank/search.php?leftmenu=bank','','ListTransactions','banks',1,'','$user->rights->banque->lire','$conf->banque->enabled && ($leftmenu==\"bank\" || $leftmenu==\"checks\" || $leftmenu==\"withdraw\")',0,'2013-03-24 02:31:47'),(105765,'auguria',1,'','left','accountancy',105761,NULL,NULL,3,'/compta/bank/budget.php?leftmenu=bank','','ListTransactionsByCategory','banks',1,'','$user->rights->banque->lire','$conf->banque->enabled && ($leftmenu==\"bank\" || $leftmenu==\"checks\" || $leftmenu==\"withdraw\")',0,'2013-03-24 02:31:47'),(105767,'auguria',1,'','left','accountancy',105761,NULL,NULL,5,'/compta/bank/virement.php?leftmenu=bank','','BankTransfers','banks',1,'','$user->rights->banque->transfer','$conf->banque->enabled && ($leftmenu==\"bank\" || $leftmenu==\"checks\" || $leftmenu==\"withdraw\")',0,'2013-03-24 02:31:47'),(105861,'auguria',1,'','left','accountancy',103167,NULL,NULL,11,'/compta/resultat/index.php?leftmenu=ca&amp;mainmenu=accountancy','','Reportings','main',0,'ca','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-03-24 02:31:47'),(105862,'auguria',1,'','left','accountancy',105861,NULL,NULL,0,'/compta/resultat/index.php?leftmenu=ca','','ReportInOut','main',1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-03-24 02:31:47'),(105863,'auguria',1,'','left','accountancy',105862,NULL,NULL,0,'/compta/resultat/clientfourn.php?leftmenu=ca','','ByCompanies','main',2,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-03-24 02:31:47'),(105864,'auguria',1,'','left','accountancy',105861,NULL,NULL,1,'/compta/stats/index.php?leftmenu=ca','','ReportTurnover','main',1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-03-24 02:31:47'),(105865,'auguria',1,'','left','accountancy',105864,NULL,NULL,0,'/compta/stats/casoc.php?leftmenu=ca','','ByCompanies','main',2,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-03-24 02:31:47'),(105866,'auguria',1,'','left','accountancy',105864,NULL,NULL,1,'/compta/stats/cabyuser.php?leftmenu=ca','','ByUsers','main',2,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-03-24 02:31:47'),(105867,'auguria',1,'','left','accountancy',105861,NULL,NULL,1,'/compta/journal/sellsjournal.php?leftmenu=ca','','SellsJournal','main',1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-03-24 02:31:47'),(105868,'auguria',1,'','left','accountancy',105861,NULL,NULL,1,'/compta/journal/purchasesjournal.php?leftmenu=ca','','PurchasesJournal','main',1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->comptabilite->enabled || $conf->accounting->enabled',0,'2013-03-24 02:31:47'),(105961,'auguria',1,'','left','products',103164,NULL,NULL,0,'/product/index.php?leftmenu=product&amp;type=0','','Products','products',0,'product','$user->rights->produit->lire','$conf->product->enabled',2,'2013-03-24 02:31:47'),(105962,'auguria',1,'','left','products',105961,NULL,NULL,0,'/product/fiche.php?leftmenu=product&amp;action=create&amp;type=0','','NewProduct','products',1,'','$user->rights->produit->creer','$conf->product->enabled',2,'2013-03-24 02:31:47'),(105963,'auguria',1,'','left','products',105961,NULL,NULL,1,'/product/liste.php?leftmenu=product&amp;type=0','','List','products',1,'','$user->rights->produit->lire','$conf->product->enabled',2,'2013-03-24 02:31:47'),(105964,'auguria',1,'','left','products',105961,NULL,NULL,4,'/product/reassort.php?type=0','','Stocks','products',1,'','$user->rights->produit->lire && $user->rights->stock->lire','$conf->product->enabled',2,'2013-03-24 02:31:47'),(105965,'auguria',1,'','left','products',105961,NULL,NULL,5,'/product/popuprop.php?leftmenu=stats&amp;type=0','','Statistics','main',1,'','$user->rights->produit->lire','$conf->propal->enabled',2,'2013-03-24 02:31:47'),(106061,'auguria',1,'','left','products',103164,NULL,NULL,1,'/product/index.php?leftmenu=service&amp;type=1','','Services','products',0,'service','$user->rights->service->lire','$conf->service->enabled',2,'2013-03-24 02:31:47'),(106062,'auguria',1,'','left','products',106061,NULL,NULL,0,'/product/fiche.php?leftmenu=service&amp;action=create&amp;type=1','','NewService','products',1,'','$user->rights->service->creer','$conf->service->enabled',2,'2013-03-24 02:31:47'),(106063,'auguria',1,'','left','products',106061,NULL,NULL,1,'/product/liste.php?leftmenu=service&amp;type=1','','List','products',1,'','$user->rights->service->lire','$conf->service->enabled',2,'2013-03-24 02:31:47'),(106064,'auguria',1,'','left','products',106061,NULL,NULL,5,'/product/popuprop.php?leftmenu=stats&amp;type=1','','Statistics','main',1,'','$user->rights->service->lire','$conf->propal->enabled',2,'2013-03-24 02:31:47'),(106261,'auguria',1,'','left','products',103164,NULL,NULL,3,'/product/stock/index.php?leftmenu=stock','','Stock','stocks',0,'stock','$user->rights->stock->lire','$conf->stock->enabled',2,'2013-03-24 02:31:47'),(106262,'auguria',1,'','left','products',106261,NULL,NULL,0,'/product/stock/fiche.php?action=create','','MenuNewWarehouse','stocks',1,'','$user->rights->stock->creer','$conf->stock->enabled',2,'2013-03-24 02:31:47'),(106263,'auguria',1,'','left','products',106261,NULL,NULL,1,'/product/stock/liste.php','','List','stocks',1,'','$user->rights->stock->lire','$conf->stock->enabled',2,'2013-03-24 02:31:47'),(106264,'auguria',1,'','left','products',106261,NULL,NULL,2,'/product/stock/valo.php','','EnhancedValue','stocks',1,'','$user->rights->stock->lire','$conf->stock->enabled',2,'2013-03-24 02:31:47'),(106265,'auguria',1,'','left','products',106261,NULL,NULL,3,'/product/stock/mouvement.php','','Movements','stocks',1,'','$user->rights->stock->mouvement->lire','$conf->stock->enabled',2,'2013-03-24 02:31:47'),(106361,'auguria',1,'','left','products',103164,NULL,NULL,4,'/categories/index.php?leftmenu=cat&amp;type=0','','Categories','categories',0,'cat','$user->rights->categorie->lire','$conf->categorie->enabled',2,'2013-03-24 02:31:47'),(106362,'auguria',1,'','left','products',106361,NULL,NULL,0,'/categories/fiche.php?action=create&amp;type=0','','NewCategory','categories',1,'','$user->rights->categorie->creer','$conf->categorie->enabled',2,'2013-03-24 02:31:47'),(106761,'auguria',1,'','left','project',103168,NULL,NULL,0,'/projet/index.php?leftmenu=projects','','Projects','projects',0,'projects','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(106762,'auguria',1,'','left','project',106761,NULL,NULL,1,'/projet/fiche.php?leftmenu=projects&amp;action=create','','NewProject','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(106763,'auguria',1,'','left','project',106761,NULL,NULL,2,'/projet/liste.php?leftmenu=projects','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(106771,'auguria',1,'','left','project',103168,NULL,NULL,0,'/projet/index.php?leftmenu=projects&amp;mode=mine','','MyProjects','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(106772,'auguria',1,'','left','project',106771,NULL,NULL,1,'/projet/fiche.php?leftmenu=projects&amp;action=create&amp;mode=mine','','NewProject','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(106773,'auguria',1,'','left','project',106771,NULL,NULL,2,'/projet/liste.php?leftmenu=projects&amp;mode=mine','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(106861,'auguria',1,'','left','project',103168,NULL,NULL,0,'/projet/activity/index.php?leftmenu=projects','','Activities','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(106862,'auguria',1,'','left','project',106861,NULL,NULL,1,'/projet/tasks.php?leftmenu=projects&amp;action=create','','NewTask','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(106863,'auguria',1,'','left','project',106861,NULL,NULL,2,'/projet/tasks/index.php?leftmenu=projects','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(106864,'auguria',1,'','left','project',106861,NULL,NULL,3,'/projet/activity/list.php?leftmenu=projects','','NewTimeSpent','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(106961,'auguria',1,'','left','project',103168,NULL,NULL,0,'/projet/activity/index.php?leftmenu=projects&amp;mode=mine','','MyActivities','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(106962,'auguria',1,'','left','project',106961,NULL,NULL,1,'/projet/tasks.php?leftmenu=projects&amp;action=create&amp;mode=mine','','NewTask','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(106963,'auguria',1,'','left','project',106961,NULL,NULL,2,'/projet/tasks/index.php?leftmenu=projects&amp;mode=mine','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(106964,'auguria',1,'','left','project',106961,NULL,NULL,3,'/projet/activity/list.php?leftmenu=projects&amp;mode=mine','','NewTimeSpent','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2013-03-24 02:31:47'),(107061,'auguria',1,'','left','tools',103169,NULL,NULL,0,'/comm/mailing/index.php?leftmenu=mailing','','EMailings','mails',0,'mailing','$user->rights->mailing->lire','$conf->mailing->enabled',0,'2013-03-24 02:31:47'),(107062,'auguria',1,'','left','tools',107061,NULL,NULL,0,'/comm/mailing/fiche.php?leftmenu=mailing&amp;action=create','','NewMailing','mails',1,'','$user->rights->mailing->creer','$conf->mailing->enabled',0,'2013-03-24 02:31:47'),(107063,'auguria',1,'','left','tools',107061,NULL,NULL,1,'/comm/mailing/liste.php?leftmenu=mailing','','List','mails',1,'','$user->rights->mailing->lire','$conf->mailing->enabled',0,'2013-03-24 02:31:47'),(107261,'auguria',1,'','left','tools',103169,NULL,NULL,2,'/exports/index.php?leftmenu=export','','FormatedExport','exports',0,'export','$user->rights->export->lire','$conf->export->enabled',2,'2013-03-24 02:31:47'),(107262,'auguria',1,'','left','tools',107261,NULL,NULL,0,'/exports/export.php?leftmenu=export','','NewExport','exports',1,'','$user->rights->export->creer','$conf->export->enabled',2,'2013-03-24 02:31:47'),(107291,'auguria',1,'','left','tools',103169,NULL,NULL,2,'/imports/index.php?leftmenu=import','','FormatedImport','exports',0,'import','$user->rights->import->run','$conf->import->enabled',2,'2013-03-24 02:31:47'),(107292,'auguria',1,'','left','tools',107291,NULL,NULL,0,'/imports/import.php?leftmenu=import','','NewImport','exports',1,'','$user->rights->import->run','$conf->import->enabled',2,'2013-03-24 02:31:47'),(107361,'auguria',1,'','left','members',103174,NULL,NULL,0,'/adherents/index.php?leftmenu=members&amp;mainmenu=members','','Members','members',0,'members','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107362,'auguria',1,'','left','members',107361,NULL,NULL,0,'/adherents/fiche.php?leftmenu=members&amp;action=create','','NewMember','members',1,'','$user->rights->adherent->creer','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107363,'auguria',1,'','left','members',107361,NULL,NULL,1,'/adherents/liste.php','','List','members',1,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107364,'auguria',1,'','left','members',107363,NULL,NULL,2,'/adherents/liste.php?leftmenu=members&amp;statut=-1','','MenuMembersToValidate','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107365,'auguria',1,'','left','members',107363,NULL,NULL,3,'/adherents/liste.php?leftmenu=members&amp;statut=1','','MenuMembersValidated','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107366,'auguria',1,'','left','members',107363,NULL,NULL,4,'/adherents/liste.php?leftmenu=members&amp;statut=1&amp;filter=outofdate','','MenuMembersNotUpToDate','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107367,'auguria',1,'','left','members',107363,NULL,NULL,5,'/adherents/liste.php?leftmenu=members&amp;statut=1&amp;filter=uptodate','','MenuMembersUpToDate','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107368,'auguria',1,'','left','members',107363,NULL,NULL,6,'/adherents/liste.php?leftmenu=members&amp;statut=0','','MenuMembersResiliated','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107369,'auguria',1,'','left','members',107361,NULL,NULL,7,'/adherents/stats/geo.php?leftmenu=members&amp;mode=memberbycountry','','MenuMembersStats','members',1,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107461,'auguria',1,'','left','members',103174,NULL,NULL,1,'/adherents/index.php?leftmenu=members&amp;mainmenu=members','','Subscriptions','compta',0,'','$user->rights->adherent->cotisation->lire','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107462,'auguria',1,'','left','members',107461,NULL,NULL,0,'/adherents/liste.php?statut=-1&amp;leftmenu=accountancy&amp;mainmenu=members','','NewSubscription','compta',1,'','$user->rights->adherent->cotisation->creer','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107463,'auguria',1,'','left','members',107461,NULL,NULL,1,'/adherents/cotisations.php?leftmenu=members','','List','compta',1,'','$user->rights->adherent->cotisation->lire','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107464,'auguria',1,'','left','members',107461,NULL,NULL,7,'/adherents/stats/index.php?leftmenu=members','','MenuMembersStats','members',1,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107661,'auguria',1,'','left','members',103174,NULL,NULL,3,'/adherents/index.php?leftmenu=export&amp;mainmenu=members','','Exports','members',0,'export','$user->rights->adherent->export','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107662,'auguria',1,'','left','members',107661,NULL,NULL,0,'/exports/index.php?leftmenu=export','','Datas','members',1,'','$user->rights->adherent->export','$conf->adherent->enabled && $conf->export->enabled',2,'2013-03-24 02:31:48'),(107663,'auguria',1,'','left','members',107661,NULL,NULL,1,'/adherents/htpasswd.php?leftmenu=export','','Filehtpasswd','members',1,'','$user->rights->adherent->export','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107664,'auguria',1,'','left','members',107661,NULL,NULL,2,'/adherents/cartes/carte.php?leftmenu=export','','MembersCards','members',1,'','$user->rights->adherent->export','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107861,'auguria',1,'','left','members',103174,NULL,NULL,5,'/adherents/type.php?leftmenu=setup&amp;mainmenu=members','','MembersTypes','members',0,'setup','$user->rights->adherent->configurer','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107862,'auguria',1,'','left','members',107861,NULL,NULL,0,'/adherents/type.php?leftmenu=setup&amp;mainmenu=members&amp;action=create','','New','members',1,'','$user->rights->adherent->configurer','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(107863,'auguria',1,'','left','members',107861,NULL,NULL,1,'/adherents/type.php?leftmenu=setup&amp;mainmenu=members','','List','members',1,'','$user->rights->adherent->configurer','$conf->adherent->enabled',2,'2013-03-24 02:31:48'),(108261,'auguria',1,'','left','commercial',103166,NULL,NULL,6,'/fourn/commande/index.php?leftmenu=orders_suppliers','','SuppliersOrders','orders',0,'orders_suppliers','$user->rights->fournisseur->commande->lire','$conf->fournisseur->enabled',2,'2013-03-24 02:31:47'),(108262,'auguria',1,'','left','commercial',108261,NULL,NULL,0,'/societe/societe.php?leftmenu=orders_suppliers','','NewOrder','orders',1,'','$user->rights->fournisseur->commande->creer','$conf->fournisseur->enabled',2,'2013-03-24 02:31:47'),(108263,'auguria',1,'','left','commercial',108261,NULL,NULL,1,'/fourn/commande/liste.php?leftmenu=orders_suppliers&amp;viewstatut=0','','List','orders',1,'','$user->rights->fournisseur->commande->lire','$conf->fournisseur->enabled',2,'2013-03-24 02:31:47'),(108269,'auguria',1,'','left','commercial',108261,NULL,NULL,7,'/commande/stats/index.php?leftmenu=orders_suppliers&amp;mode=supplier','','Statistics','orders',1,'','$user->rights->fournisseur->commande->lire','$conf->fournisseur->enabled',2,'2013-03-24 02:31:47'),(108361,'auguria',1,'','left','members',103174,NULL,NULL,3,'/categories/index.php?leftmenu=cat&amp;type=3','','MembersCategoriesShort','categories',0,'cat','$user->rights->categorie->lire','$conf->adherent->enabled && $conf->categorie->enabled',2,'2013-03-24 02:31:48'),(108362,'auguria',1,'','left','members',108361,NULL,NULL,0,'/categories/fiche.php?action=create&amp;type=3','','NewCategory','categories',1,'','$user->rights->categorie->creer','$conf->adherent->enabled && $conf->categorie->enabled',2,'2013-03-24 02:31:48'),(108363,'all',1,'opensurvey','top','opensurvey',0,NULL,NULL,200,'/opensurvey/index.php','','Surveys','opensurvey@opensurvey',NULL,NULL,'$user->rights->opensurvey->survey->read','$conf->opensurvey->enabled',0,'2013-03-24 02:57:18'),(108364,'all',1,'opensurvey','left','opensurvey',-1,NULL,'opensurvey',200,'/opensurvey/index.php?mainmenu=opensurvey&leftmenu=opensurvey','','Survey','opensurvey@opensurvey',NULL,'opensurvey','','$conf->opensurvey->enabled',0,'2013-03-24 02:57:18'),(108365,'all',1,'opensurvey','left','opensurvey',-1,'opensurvey','opensurvey',210,'/opensurvey/public/index.php?origin=dolibarr','_blank','NewSurvey','opensurvey@opensurvey',NULL,'opensurvey_new','','$conf->opensurvey->enabled',0,'2013-03-24 02:57:18'),(108366,'all',1,'opensurvey','left','opensurvey',-1,'opensurvey','opensurvey',220,'/opensurvey/list.php','','List','opensurvey@opensurvey',NULL,'opensurvey_list','','$conf->opensurvey->enabled',0,'2013-03-24 02:57:18'),(108439,'all',1,'agenda','top','agenda',0,NULL,NULL,100,'/comm/action/index.php','','Agenda','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2014-04-05 14:19:21'),(108440,'all',1,'agenda','left','agenda',108439,NULL,NULL,100,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda','','Actions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2014-04-05 14:19:21'),(108441,'all',1,'agenda','left','agenda',108440,NULL,NULL,101,'/comm/action/fiche.php?mainmenu=agenda&amp;leftmenu=agenda&amp;action=create','','NewAction','commercial',NULL,NULL,'($user->rights->agenda->myactions->create||$user->rights->agenda->allactions->create)','$conf->agenda->enabled',2,'2014-04-05 14:19:21'),(108442,'all',1,'agenda','left','agenda',108440,NULL,NULL,102,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda','','Calendar','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2014-04-05 14:19:21'),(108443,'all',1,'agenda','left','agenda',108442,NULL,NULL,103,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine','','MenuToDoMyActions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2014-04-05 14:19:21'),(108444,'all',1,'agenda','left','agenda',108442,NULL,NULL,104,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine','','MenuDoneMyActions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2014-04-05 14:19:21'),(108445,'all',1,'agenda','left','agenda',108442,NULL,NULL,105,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo','','MenuToDoActions','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2014-04-05 14:19:21'),(108446,'all',1,'agenda','left','agenda',108442,NULL,NULL,106,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done','','MenuDoneActions','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2014-04-05 14:19:21'),(108447,'all',1,'agenda','left','agenda',108440,NULL,NULL,112,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda','','List','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2014-04-05 14:19:21'),(108448,'all',1,'agenda','left','agenda',108447,NULL,NULL,113,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine','','MenuToDoMyActions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2014-04-05 14:19:21'),(108449,'all',1,'agenda','left','agenda',108447,NULL,NULL,114,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine','','MenuDoneMyActions','agenda',NULL,NULL,'$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2014-04-05 14:19:21'),(108450,'all',1,'agenda','left','agenda',108447,NULL,NULL,115,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo','','MenuToDoActions','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2014-04-05 14:19:21'),(108451,'all',1,'agenda','left','agenda',108447,NULL,NULL,116,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done','','MenuDoneActions','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2014-04-05 14:19:21'),(108452,'all',1,'agenda','left','agenda',108440,NULL,NULL,120,'/comm/action/rapport/index.php?mainmenu=agenda&amp;leftmenu=agenda','','Reportings','agenda',NULL,NULL,'$user->rights->agenda->allactions->read','$conf->agenda->enabled',2,'2014-04-05 14:19:21'),(108453,'all',1,'ecm','top','ecm',0,NULL,NULL,100,'/ecm/index.php','','MenuECM','ecm',NULL,NULL,'$user->rights->ecm->read || $user->rights->ecm->upload || $user->rights->ecm->setup','$conf->ecm->enabled',2,'2014-04-05 14:19:22'),(108454,'all',1,'ecm','left','ecm',108453,NULL,NULL,101,'/ecm/index.php','','ECMArea','ecm',NULL,NULL,'$user->rights->ecm->read || $user->rights->ecm->upload','$user->rights->ecm->read || $user->rights->ecm->upload',2,'2014-04-05 14:19:22'),(108455,'all',1,'ecm','left','ecm',108454,NULL,NULL,100,'/ecm/docdir.php?action=create','','ECMNewSection','ecm',NULL,NULL,'$user->rights->ecm->setup','$user->rights->ecm->setup',2,'2014-04-05 14:19:22'),(108456,'all',1,'ecm','left','ecm',108454,NULL,NULL,102,'/ecm/index.php?action=file_manager','','ECMFileManager','ecm',NULL,NULL,'$user->rights->ecm->read || $user->rights->ecm->upload','$user->rights->ecm->read || $user->rights->ecm->upload',2,'2014-04-05 14:19:22');
/*!40000 ALTER TABLE `llx_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_milestone`
--

DROP TABLE IF EXISTS `llx_milestone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_milestone` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_element` int(11) NOT NULL,
  `elementtype` varchar(16) NOT NULL,
  `label` varchar(255) NOT NULL,
  `options` varchar(255) DEFAULT NULL,
  `priority` int(11) DEFAULT '0',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_milestone_fk_element` (`fk_element`,`elementtype`),
  KEY `idx_milestone_fk_user_modif` (`fk_user_modif`),
  CONSTRAINT `fk_milestone_fk_user_modif` FOREIGN KEY (`fk_user_modif`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_milestone`
--

LOCK TABLES `llx_milestone` WRITE;
/*!40000 ALTER TABLE `llx_milestone` DISABLE KEYS */;
INSERT INTO `llx_milestone` VALUES (2,779,'facture','azerty',NULL,0,'2013-03-09 12:19:30',NULL),(3,780,'facture','fsdf',NULL,0,'2013-03-09 13:01:08',NULL),(4,781,'facture','hhh',NULL,0,'2013-03-09 14:06:37',NULL);
/*!40000 ALTER TABLE `llx_milestone` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_monitoring_probes`
--

DROP TABLE IF EXISTS `llx_monitoring_probes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_monitoring_probes` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL,
  `groupname` varchar(64) DEFAULT NULL,
  `url` varchar(250) NOT NULL,
  `useproxy` int(11) DEFAULT '0',
  `checkkey` varchar(250) DEFAULT NULL,
  `maxval` int(11) DEFAULT NULL,
  `frequency` int(11) DEFAULT '60',
  `active` int(11) DEFAULT '1',
  `status` int(11) DEFAULT '0',
  `lastreset` datetime DEFAULT NULL,
  `oldesterrortext` text,
  `oldesterrordate` datetime DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_monitoring_probes` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_monitoring_probes`
--

LOCK TABLES `llx_monitoring_probes` WRITE;
/*!40000 ALTER TABLE `llx_monitoring_probes` DISABLE KEYS */;
INSERT INTO `llx_monitoring_probes` VALUES (1,'aaa',NULL,'http://www.chiensderace.com',0,'chiens',1000,10,1,1,'2011-04-20 23:46:41',NULL,NULL),(2,'ChatsDeRace',NULL,'http://www.chatsderace.com',0,'chats',1000,5,1,1,'2011-04-20 23:46:41',NULL,NULL);
/*!40000 ALTER TABLE `llx_monitoring_probes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_notify`
--

DROP TABLE IF EXISTS `llx_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_notify` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `daten` datetime DEFAULT NULL,
  `fk_action` int(11) NOT NULL,
  `fk_contact` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `objet_type` varchar(24) NOT NULL,
  `objet_id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `type` varchar(16) DEFAULT 'email',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_notify`
--

LOCK TABLES `llx_notify` WRITE;
/*!40000 ALTER TABLE `llx_notify` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_notify` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_notify_def`
--

DROP TABLE IF EXISTS `llx_notify_def`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_notify_def` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` date DEFAULT NULL,
  `fk_action` int(11) NOT NULL,
  `fk_soc` int(11) NOT NULL,
  `fk_contact` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `type` varchar(16) DEFAULT 'email',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_notify_def`
--

LOCK TABLES `llx_notify_def` WRITE;
/*!40000 ALTER TABLE `llx_notify_def` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_notify_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_opensurvey_comments`
--

DROP TABLE IF EXISTS `llx_opensurvey_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_opensurvey_comments` (
  `id_comment` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_sondage` char(16) NOT NULL,
  `comment` text NOT NULL,
  `usercomment` text,
  PRIMARY KEY (`id_comment`),
  KEY `idx_id_comment` (`id_comment`),
  KEY `idx_id_sondage` (`id_sondage`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_opensurvey_comments`
--

LOCK TABLES `llx_opensurvey_comments` WRITE;
/*!40000 ALTER TABLE `llx_opensurvey_comments` DISABLE KEYS */;
INSERT INTO `llx_opensurvey_comments` VALUES (2,'434dio8rxfljs3p1','aaa','aaa'),(5,'434dio8rxfljs3p1','aaa','aaa'),(6,'434dio8rxfljs3p1','gfh','jj'),(11,'434dio8rxfljs3p1','fsdf','fdsf'),(12,'3imby4hf7joiilsu','fsdf','aa'),(16,'3imby4hf7joiilsu','gdfg','gfdg'),(17,'3imby4hf7joiilsu','gfdgd','gdfgd'),(18,'om4e7azfiurnjtqe','fds','fdsf'),(26,'qgsfrgb922rqzocy','gfdg','gfdg'),(27,'qgsfrgb922rqzocy','gfdg','gfd'),(28,'m4467s2mtk6khmxc','hgf','hgfh'),(29,'m4467s2mtk6khmxc','fgh','hgf'),(30,'ckanvbe7kt3rdb3h','hfgh','fdfds'),(31,'m4467s2mtk6khmxc','hgfh','hgf');
/*!40000 ALTER TABLE `llx_opensurvey_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_opensurvey_sondage`
--

DROP TABLE IF EXISTS `llx_opensurvey_sondage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_opensurvey_sondage` (
  `id_sondage` varchar(16) NOT NULL,
  `id_sondage_admin` char(24) DEFAULT NULL,
  `commentaires` text,
  `mail_admin` varchar(128) DEFAULT NULL,
  `nom_admin` varchar(64) DEFAULT NULL,
  `titre` text,
  `date_fin` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `format` varchar(2) DEFAULT NULL,
  `mailsonde` varchar(2) DEFAULT '0',
  `survey_link_visible` int(11) DEFAULT '1',
  `origin` varchar(64) DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id_sondage`),
  KEY `idx_id_sondage_admin` (`id_sondage_admin`),
  KEY `idx_date_fin` (`date_fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_opensurvey_sondage`
--

LOCK TABLES `llx_opensurvey_sondage` WRITE;
/*!40000 ALTER TABLE `llx_opensurvey_sondage` DISABLE KEYS */;
INSERT INTO `llx_opensurvey_sondage` VALUES ('m4467s2mtk6khmxc','m4467s2mtk6khmxci2ysw682','fdffdshfghfj jhgjgh','aaa@aaa.com','fdfds','fdffds','2013-03-06 23:00:00','D+','1',1,'dolibarr','2000-01-01 00:00:00');
/*!40000 ALTER TABLE `llx_opensurvey_sondage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_opensurvey_sujet_studs`
--

DROP TABLE IF EXISTS `llx_opensurvey_sujet_studs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_opensurvey_sujet_studs` (
  `id_sondage` char(16) NOT NULL,
  `sujet` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_opensurvey_sujet_studs`
--

LOCK TABLES `llx_opensurvey_sujet_studs` WRITE;
/*!40000 ALTER TABLE `llx_opensurvey_sujet_studs` DISABLE KEYS */;
INSERT INTO `llx_opensurvey_sujet_studs` VALUES ('434dio8rxfljs3p1','1362697200,1363734000'),('3eyn2drokozf3j4s','1362438000@10h,1363129200@10h'),('z2qcqjh5pm1q4p99','r&eacute;solution 1,r&eacute;solution 2,aaa,fdsfsdfsd@checkbox'),('xfwtrseu3ok1c4m6','gdfgfd@yesno,gfdgd@pourcontre,llll@pourcontre'),('om4e7azfiurnjtqe','g dfgdfdfg dfg dg dfg g fdg dfgd fg fg d@pourcontre,mmlml@checkbox'),('fubmr7n293akha5j','check@checkbox,yesno@yesno,pc@pourcontre'),('icaanayi59qto4fl','check@checkbox,yesno@yesno,pc@pourcontre'),('ipbkufzz4lr2vbpx','pc@pourcontre,fdsffd@checkbox'),('3imby4hf7joiilsu','fsdf@yesno,fsdfsd@checkbox,fsdf@pourcontre'),('q41jpgfd4ii3g9vx','fdfsd@pourcontre,fdsfs@pourcontre,fdsfsdf@checkbox'),('xm6hysvkspo7gbx6','fdfsd@pourcontre,fdsfs@pourcontre,fdsfsdf@checkbox'),('99sbps3ba3s8pq7b','fdfsd@pourcontre,fdsfs@pourcontre,fdsfsdf@checkbox'),('6wstlvu2z9kxqweh','fdfsd@pourcontre,fdsfs@pourcontre,fdsfsdf@checkbox'),('783p7f377offci4v','fdfsd@pourcontre,fdsfs@pourcontre,fdsfsdf@checkbox'),('u4umbl5yb6lpydci','fdfsd@pourcontre,fdsfs@pourcontre,fdsfsdf@checkbox'),('dn2euwlf2d4wyy6m','fdfsd@pourcontre,fdsfs@pourcontre,fdsfsdf@checkbox'),('t896ed7af3ujdprx','fdfsd@pourcontre,fdsfs@pourcontre,fdsfsdf@checkbox'),('q5c4kucbbkuxjz8g','fdsfsd@pourcontre,fdsfs@pourcontre,fdsfsd@pourcontre'),('8mcdnf2hgcntfibe','fdsfsd@pourcontre,fdsfs@pourcontre,fdsfsd@pourcontre'),('7shynoad2x4zl8sw','fdsfsd@pourcontre,fdsfs@pourcontre,fdsfsd@pourcontre'),('x82rfs19p8fa21et','fdsfsd@pourcontre,fdsfs@pourcontre,fdsfsd@pourcontre'),('qgsfrgb922rqzocy','1364338800@20H-21H,1364338800@21H-22H,1364425200@20H-21H,1364425200@21H-22H,1364511600@20H-21H,1364511600@21H-22H'),('ah9xvaqu1ajjrqse','1391295600@2h-5h,1391295600@2h-5h,1364338800@20H-21H,1364338800@21H-22H,1364425200@20H-21H,1391295600@2h-5h'),('ckanvbe7kt3rdb3h','1363734000,1363820400'),('6v9xq6f9lemjiuba','1363734000,1363820400,1363906800,1363993200'),('m4467s2mtk6khmxc','1398981600,1357081200,1363734000,1363820400,1363993200,1398981600');
/*!40000 ALTER TABLE `llx_opensurvey_sujet_studs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_opensurvey_user_studs`
--

DROP TABLE IF EXISTS `llx_opensurvey_user_studs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_opensurvey_user_studs` (
  `id_users` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(64) NOT NULL,
  `id_sondage` varchar(16) NOT NULL,
  `reponses` varchar(100) NOT NULL,
  PRIMARY KEY (`id_users`),
  KEY `idx_id_users` (`id_users`),
  KEY `idx_nom` (`nom`),
  KEY `idx_id_sondage` (`id_sondage`),
  KEY `idx_opensurvey_user_studs_id_users` (`id_users`),
  KEY `idx_opensurvey_user_studs_nom` (`nom`),
  KEY `idx_opensurvey_user_studs_id_sondage` (`id_sondage`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_opensurvey_user_studs`
--

LOCK TABLES `llx_opensurvey_user_studs` WRITE;
/*!40000 ALTER TABLE `llx_opensurvey_user_studs` DISABLE KEYS */;
INSERT INTO `llx_opensurvey_user_studs` VALUES (1,'gfdgdf','om4e7azfiurnjtqe','01'),(2,'aa','3imby4hf7joiilsu','210'),(3,'fsdf','z2qcqjh5pm1q4p99','0110'),(5,'hfghf','z2qcqjh5pm1q4p99','1110'),(6,'qqqq','ah9xvaqu1ajjrqse','000111'),(7,'hjgh','ah9xvaqu1ajjrqse','000010'),(8,'bcvb','qgsfrgb922rqzocy','011000'),(9,'gdfg','ah9xvaqu1ajjrqse','001000'),(10,'ggg','ah9xvaqu1ajjrqse','000100'),(11,'gfdgd','ah9xvaqu1ajjrqse','001000'),(12,'hhhh','ah9xvaqu1ajjrqse','010000'),(13,'iii','ah9xvaqu1ajjrqse','000100'),(14,'kkk','ah9xvaqu1ajjrqse','001000'),(15,'lllll','ah9xvaqu1ajjrqse','000001'),(16,'kk','ah9xvaqu1ajjrqse','000001'),(17,'gggg','ah9xvaqu1ajjrqse','001000'),(18,'mmmm','ah9xvaqu1ajjrqse','000000'),(19,'jkjkj','ah9xvaqu1ajjrqse','000001'),(20,'azerty','8mcdnf2hgcntfibe','012'),(21,'hfghfg','8mcdnf2hgcntfibe','012'),(22,'fd','ckanvbe7kt3rdb3h','10'),(23,'gfdgdf','m4467s2mtk6khmxc','00011'),(24,'hgfh','m4467s2mtk6khmxc','000111');
/*!40000 ALTER TABLE `llx_opensurvey_user_studs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiement`
--

DROP TABLE IF EXISTS `llx_paiement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_paiement` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datep` datetime DEFAULT NULL,
  `amount` double(24,8) DEFAULT NULL,
  `fk_paiement` int(11) NOT NULL,
  `num_paiement` varchar(50) DEFAULT NULL,
  `note` text,
  `fk_bank` int(11) NOT NULL DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  `fk_export_compta` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_paiement`
--

LOCK TABLES `llx_paiement` WRITE;
/*!40000 ALTER TABLE `llx_paiement` DISABLE KEYS */;
INSERT INTO `llx_paiement` VALUES (1,1,'2010-07-10 14:59:41','2010-07-10 12:59:41','2010-07-10 12:00:00',0.02000000,4,'','',4,1,NULL,0,0),(2,1,'2011-07-18 20:50:24','2011-07-18 18:50:24','2011-07-08 12:00:00',20.00000000,6,'','',5,1,NULL,0,0),(3,1,'2011-07-18 20:50:47','2011-07-18 18:50:47','2011-07-08 12:00:00',10.00000000,4,'','',6,1,NULL,0,0),(5,1,'2011-08-01 03:34:11','2011-08-01 01:34:11','2011-08-01 03:34:11',5.63000000,6,'','Payment Invoice FA1108-0003',8,1,NULL,0,0),(6,1,'2011-08-06 20:33:54','2011-08-06 18:33:54','2011-08-06 20:33:53',5.98000000,4,'','Payment Invoice FA1108-0004',13,1,NULL,0,0),(8,1,'2011-08-08 02:53:40','2011-08-08 00:53:40','2011-08-08 12:00:00',26.10000000,4,'','',14,1,NULL,0,0),(9,1,'2011-08-08 02:55:58','2011-08-08 00:55:58','2011-08-08 12:00:00',26.96000000,1,'','',15,1,NULL,0,0),(17,1,'2012-12-09 15:28:44','2012-12-09 14:28:44','2012-12-09 12:00:00',2.00000000,4,'','',16,1,NULL,0,0),(18,1,'2012-12-09 15:28:53','2012-12-09 14:28:53','2012-12-09 12:00:00',-2.00000000,4,'','',17,1,NULL,0,0),(19,1,'2012-12-09 17:35:55','2012-12-09 16:35:55','2012-12-09 12:00:00',-2.00000000,4,'','',18,1,NULL,0,0),(20,1,'2012-12-09 17:37:02','2012-12-09 16:37:02','2012-12-09 12:00:00',2.00000000,4,'','',19,1,NULL,0,0),(21,1,'2012-12-09 18:35:07','2012-12-09 17:35:07','2012-12-09 12:00:00',-2.00000000,4,'','',20,1,NULL,0,0),(23,1,'2012-12-12 18:54:33','2012-12-12 17:54:33','2012-12-12 12:00:00',1.00000000,1,'','',21,1,NULL,0,0),(24,1,'2013-03-06 16:48:16','2013-03-06 15:48:16','2013-03-06 00:00:00',20.00000000,4,'','Adhésion/cotisation 2016',22,1,NULL,0,0),(25,1,'2013-03-20 14:30:11','2013-03-20 13:30:11','2013-03-20 00:00:00',10.00000000,2,'','Adhésion/cotisation 2011',23,1,NULL,0,0);
/*!40000 ALTER TABLE `llx_paiement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiement_facture`
--

DROP TABLE IF EXISTS `llx_paiement_facture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_paiement_facture` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_paiement` int(11) DEFAULT NULL,
  `fk_facture` int(11) DEFAULT NULL,
  `amount` double(24,8) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_paiement_facture` (`fk_paiement`,`fk_facture`),
  KEY `idx_paiement_facture_fk_facture` (`fk_facture`),
  KEY `idx_paiement_facture_fk_paiement` (`fk_paiement`),
  CONSTRAINT `fk_paiement_facture_fk_facture` FOREIGN KEY (`fk_facture`) REFERENCES `llx_facture` (`rowid`),
  CONSTRAINT `fk_paiement_facture_fk_paiement` FOREIGN KEY (`fk_paiement`) REFERENCES `llx_paiement` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_paiement_facture`
--

LOCK TABLES `llx_paiement_facture` WRITE;
/*!40000 ALTER TABLE `llx_paiement_facture` DISABLE KEYS */;
INSERT INTO `llx_paiement_facture` VALUES (1,1,1,0.02000000),(2,2,2,20.00000000),(3,3,2,10.00000000),(5,5,5,5.63000000),(6,6,6,5.98000000),(9,8,2,16.10000000),(10,8,8,10.00000000),(11,9,3,15.00000000),(12,9,9,11.96000000),(20,17,11,2.00000000),(21,18,12,-2.00000000),(22,19,10,-1.00000000),(23,19,12,-1.00000000),(24,20,9,1.00000000),(25,20,11,1.00000000),(26,21,12,-2.00000000),(28,23,55,1.00000000),(29,24,161,20.00000000),(30,25,210,10.00000000);
/*!40000 ALTER TABLE `llx_paiement_facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiementcharge`
--

DROP TABLE IF EXISTS `llx_paiementcharge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_paiementcharge` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_charge` int(11) DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datep` datetime DEFAULT NULL,
  `amount` double DEFAULT '0',
  `fk_typepaiement` int(11) NOT NULL,
  `num_paiement` varchar(50) DEFAULT NULL,
  `note` text,
  `fk_bank` int(11) NOT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_paiementcharge`
--

LOCK TABLES `llx_paiementcharge` WRITE;
/*!40000 ALTER TABLE `llx_paiementcharge` DISABLE KEYS */;
INSERT INTO `llx_paiementcharge` VALUES (4,4,'2011-08-05 23:11:37','2011-08-05 21:11:37','2011-08-05 12:00:00',10,2,'','',12,1,NULL);
/*!40000 ALTER TABLE `llx_paiementcharge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiementfourn`
--

DROP TABLE IF EXISTS `llx_paiementfourn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_paiementfourn` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` datetime DEFAULT NULL,
  `datep` datetime DEFAULT NULL,
  `amount` double DEFAULT '0',
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_paiement` int(11) NOT NULL,
  `num_paiement` varchar(50) DEFAULT NULL,
  `note` text,
  `fk_bank` int(11) NOT NULL,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_paiementfourn`
--

LOCK TABLES `llx_paiementfourn` WRITE;
/*!40000 ALTER TABLE `llx_paiementfourn` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_paiementfourn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_paiementfourn_facturefourn`
--

DROP TABLE IF EXISTS `llx_paiementfourn_facturefourn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_paiementfourn_facturefourn` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_paiementfourn` int(11) DEFAULT NULL,
  `fk_facturefourn` int(11) DEFAULT NULL,
  `amount` double DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_paiementfourn_facturefourn` (`fk_paiementfourn`,`fk_facturefourn`),
  KEY `idx_paiementfourn_facturefourn_fk_facture` (`fk_facturefourn`),
  KEY `idx_paiementfourn_facturefourn_fk_paiement` (`fk_paiementfourn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_paiementfourn_facturefourn`
--

LOCK TABLES `llx_paiementfourn_facturefourn` WRITE;
/*!40000 ALTER TABLE `llx_paiementfourn_facturefourn` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_paiementfourn_facturefourn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_pos_cash`
--

DROP TABLE IF EXISTS `llx_pos_cash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_pos_cash` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `code` varchar(3) DEFAULT NULL,
  `name` varchar(30) DEFAULT NULL,
  `tactil` tinyint(4) NOT NULL DEFAULT '0',
  `fk_paycash` int(11) DEFAULT NULL,
  `fk_modepaycash` int(11) DEFAULT NULL,
  `fk_paybank` int(11) DEFAULT NULL,
  `fk_modepaybank` int(11) DEFAULT NULL,
  `fk_warehouse` int(11) DEFAULT NULL,
  `fk_device` int(11) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `is_used` tinyint(4) DEFAULT '0',
  `fk_user_u` int(11) DEFAULT NULL,
  `fk_user_c` int(11) DEFAULT NULL,
  `fk_user_m` int(11) DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  `datea` datetime DEFAULT NULL,
  `is_closed` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_pos_cash`
--

LOCK TABLES `llx_pos_cash` WRITE;
/*!40000 ALTER TABLE `llx_pos_cash` DISABLE KEYS */;
INSERT INTO `llx_pos_cash` VALUES (1,1,'aaa','aaa',0,3,1,1,1,1,NULL,1,0,0,1,NULL,'2013-01-19 18:18:39','2013-01-19 18:18:39',0);
/*!40000 ALTER TABLE `llx_pos_cash` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_pos_control_cash`
--

DROP TABLE IF EXISTS `llx_pos_control_cash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_pos_control_cash` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_cash` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `date_c` datetime DEFAULT NULL,
  `type_control` tinyint(4) DEFAULT '0',
  `amount_teor` double(24,8) DEFAULT NULL,
  `amount_real` double(24,8) DEFAULT NULL,
  `amount_diff` double(24,8) DEFAULT NULL,
  `amount_mov_out` double(24,8) DEFAULT NULL,
  `amount_mov_int` double(24,8) DEFAULT NULL,
  `amount_next_day` double(24,8) DEFAULT NULL,
  `comment` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_pos_control_cash`
--

LOCK TABLES `llx_pos_control_cash` WRITE;
/*!40000 ALTER TABLE `llx_pos_control_cash` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_pos_control_cash` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_pos_facture`
--

DROP TABLE IF EXISTS `llx_pos_facture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_pos_facture` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_cash` int(11) NOT NULL,
  `fk_facture` int(11) NOT NULL,
  `fk_control_cash` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_pos_facture`
--

LOCK TABLES `llx_pos_facture` WRITE;
/*!40000 ALTER TABLE `llx_pos_facture` DISABLE KEYS */;
INSERT INTO `llx_pos_facture` VALUES (1,1,148,NULL),(2,1,149,NULL),(3,1,150,NULL),(4,1,151,NULL);
/*!40000 ALTER TABLE `llx_pos_facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_pos_moviments`
--

DROP TABLE IF EXISTS `llx_pos_moviments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_pos_moviments` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_cash` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `date_m` datetime DEFAULT NULL,
  `amount` double(24,8) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL,
  `comment` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_pos_moviments`
--

LOCK TABLES `llx_pos_moviments` WRITE;
/*!40000 ALTER TABLE `llx_pos_moviments` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_pos_moviments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_pos_ticketdet`
--

DROP TABLE IF EXISTS `llx_pos_ticketdet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_pos_ticketdet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_ticket` int(11) NOT NULL,
  `fk_parent_line` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `description` text,
  `tva_tx` double(6,3) DEFAULT NULL,
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `fk_remise_except` int(11) DEFAULT NULL,
  `subprice` double(24,8) DEFAULT NULL,
  `price` double(24,8) DEFAULT NULL,
  `total_ht` double(24,8) DEFAULT NULL,
  `total_tva` double(24,8) DEFAULT NULL,
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT NULL,
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `info_bits` int(11) DEFAULT '0',
  `fk_code_ventilation` int(11) NOT NULL DEFAULT '0',
  `fk_export_compta` int(11) NOT NULL DEFAULT '0',
  `rang` int(11) DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_fk_remise_except` (`fk_remise_except`,`fk_ticket`),
  KEY `idx_ticketdet_fk_ticket` (`fk_ticket`),
  KEY `idx_ticketdet_fk_product` (`fk_product`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_pos_ticketdet`
--

LOCK TABLES `llx_pos_ticketdet` WRITE;
/*!40000 ALTER TABLE `llx_pos_ticketdet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_pos_ticketdet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_bons`
--

DROP TABLE IF EXISTS `llx_prelevement_bons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_prelevement_bons` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(12) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime DEFAULT NULL,
  `amount` double DEFAULT '0',
  `statut` smallint(6) DEFAULT '0',
  `credite` smallint(6) DEFAULT '0',
  `note` text,
  `date_trans` datetime DEFAULT NULL,
  `method_trans` smallint(6) DEFAULT NULL,
  `fk_user_trans` int(11) DEFAULT NULL,
  `date_credit` datetime DEFAULT NULL,
  `fk_user_credit` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_prelevement_bons_ref` (`ref`,`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_prelevement_bons`
--

LOCK TABLES `llx_prelevement_bons` WRITE;
/*!40000 ALTER TABLE `llx_prelevement_bons` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_prelevement_bons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_facture`
--

DROP TABLE IF EXISTS `llx_prelevement_facture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_prelevement_facture` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL,
  `fk_prelevement_lignes` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_prelevement_facture_fk_prelevement_lignes` (`fk_prelevement_lignes`),
  CONSTRAINT `fk_prelevement_facture_fk_prelevement_lignes` FOREIGN KEY (`fk_prelevement_lignes`) REFERENCES `llx_prelevement_lignes` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_prelevement_facture`
--

LOCK TABLES `llx_prelevement_facture` WRITE;
/*!40000 ALTER TABLE `llx_prelevement_facture` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_prelevement_facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_facture_demande`
--

DROP TABLE IF EXISTS `llx_prelevement_facture_demande`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_prelevement_facture_demande` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL,
  `amount` double NOT NULL,
  `date_demande` datetime NOT NULL,
  `traite` smallint(6) DEFAULT '0',
  `date_traite` datetime DEFAULT NULL,
  `fk_prelevement_bons` int(11) DEFAULT NULL,
  `fk_user_demande` int(11) NOT NULL,
  `code_banque` varchar(7) DEFAULT NULL,
  `code_guichet` varchar(6) DEFAULT NULL,
  `number` varchar(255) DEFAULT NULL,
  `cle_rib` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_prelevement_facture_demande`
--

LOCK TABLES `llx_prelevement_facture_demande` WRITE;
/*!40000 ALTER TABLE `llx_prelevement_facture_demande` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_prelevement_facture_demande` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_lignes`
--

DROP TABLE IF EXISTS `llx_prelevement_lignes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_prelevement_lignes` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_prelevement_bons` int(11) DEFAULT NULL,
  `fk_soc` int(11) NOT NULL,
  `statut` smallint(6) DEFAULT '0',
  `client_nom` varchar(255) DEFAULT NULL,
  `amount` double DEFAULT '0',
  `code_banque` varchar(7) DEFAULT NULL,
  `code_guichet` varchar(6) DEFAULT NULL,
  `number` varchar(255) DEFAULT NULL,
  `cle_rib` varchar(5) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`),
  KEY `idx_prelevement_lignes_fk_prelevement_bons` (`fk_prelevement_bons`),
  CONSTRAINT `fk_prelevement_lignes_fk_prelevement_bons` FOREIGN KEY (`fk_prelevement_bons`) REFERENCES `llx_prelevement_bons` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_prelevement_lignes`
--

LOCK TABLES `llx_prelevement_lignes` WRITE;
/*!40000 ALTER TABLE `llx_prelevement_lignes` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_prelevement_lignes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_prelevement_rejet`
--

DROP TABLE IF EXISTS `llx_prelevement_rejet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_prelevement_rejet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_prelevement_lignes` int(11) DEFAULT NULL,
  `date_rejet` datetime DEFAULT NULL,
  `motif` int(11) DEFAULT NULL,
  `date_creation` datetime DEFAULT NULL,
  `fk_user_creation` int(11) DEFAULT NULL,
  `note` text,
  `afacturer` tinyint(4) DEFAULT '0',
  `fk_facture` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_prelevement_rejet`
--

LOCK TABLES `llx_prelevement_rejet` WRITE;
/*!40000 ALTER TABLE `llx_prelevement_rejet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_prelevement_rejet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_printer_ipp`
--

DROP TABLE IF EXISTS `llx_printer_ipp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_printer_ipp` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` datetime DEFAULT NULL,
  `printer_name` text NOT NULL,
  `printer_location` text NOT NULL,
  `printer_uri` varchar(256) NOT NULL,
  `copy` int(11) NOT NULL DEFAULT '1',
  `module` varchar(16) NOT NULL,
  `login` varchar(32) NOT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_printer_ipp`
--

LOCK TABLES `llx_printer_ipp` WRITE;
/*!40000 ALTER TABLE `llx_printer_ipp` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_printer_ipp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product`
--

DROP TABLE IF EXISTS `llx_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `virtual` tinyint(4) NOT NULL DEFAULT '0',
  `fk_parent` int(11) DEFAULT '0',
  `ref` varchar(128) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(128) DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  `description` text,
  `note` text,
  `customcode` varchar(32) DEFAULT NULL,
  `fk_country` int(11) DEFAULT NULL,
  `price` double(24,8) DEFAULT '0.00000000',
  `price_ttc` double(24,8) DEFAULT '0.00000000',
  `price_min` double(24,8) DEFAULT '0.00000000',
  `price_min_ttc` double(24,8) DEFAULT '0.00000000',
  `price_base_type` varchar(3) DEFAULT 'HT',
  `tva_tx` double(6,3) DEFAULT NULL,
  `recuperableonly` int(11) NOT NULL DEFAULT '0',
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `fk_user_author` int(11) DEFAULT NULL,
  `tosell` tinyint(4) DEFAULT '1',
  `tobuy` tinyint(4) DEFAULT '1',
  `fk_product_type` int(11) DEFAULT '0',
  `duration` varchar(6) DEFAULT NULL,
  `seuil_stock_alerte` int(11) DEFAULT '0',
  `barcode` varchar(255) DEFAULT NULL,
  `fk_barcode_type` int(11) DEFAULT '0',
  `accountancy_code_sell` varchar(15) DEFAULT NULL,
  `accountancy_code_buy` varchar(15) DEFAULT NULL,
  `partnumber` varchar(32) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `weight_units` tinyint(4) DEFAULT NULL,
  `length` float DEFAULT NULL,
  `length_units` tinyint(4) DEFAULT NULL,
  `surface` float DEFAULT NULL,
  `surface_units` tinyint(4) DEFAULT NULL,
  `volume` float DEFAULT NULL,
  `volume_units` tinyint(4) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `pmp` double(24,8) NOT NULL DEFAULT '0.00000000',
  `canvas` varchar(32) DEFAULT 'default@product',
  `finished` tinyint(4) DEFAULT NULL,
  `hidden` tinyint(4) DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  `desiredstock` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_ref` (`ref`,`entity`),
  KEY `idx_product_label` (`label`),
  KEY `idx_product_barcode` (`barcode`),
  KEY `idx_product_import_key` (`import_key`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product`
--

LOCK TABLES `llx_product` WRITE;
/*!40000 ALTER TABLE `llx_product` DISABLE KEYS */;
INSERT INTO `llx_product` VALUES (1,'2010-07-08 14:33:17','2013-03-12 09:30:24',0,0,'PINKDRESS',1,NULL,'Pink dress','A beatifull pink dress','',NULL,NULL,100.00000000,112.50000000,90.00000000,101.25000000,'HT',12.500,0,0.000,0.000,1,1,1,0,'',20,NULL,0,'','',NULL,100,0,NULL,0,NULL,0,NULL,0,2,0.00000000,NULL,1,0,NULL,0),(2,'2010-07-09 00:30:01','2013-01-19 17:31:58',0,0,'Product_P1',1,NULL,'Product P1','','','',32,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,1,0,'',NULL,NULL,0,'','',NULL,NULL,0,NULL,0,NULL,0,NULL,0,998,0.00000000,NULL,0,0,NULL,0),(3,'2010-07-09 00:30:25','2012-12-08 13:11:14',0,0,'Service_S1',1,NULL,'Service S1','','',NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,1,1,'1m',NULL,NULL,0,'','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00000000,NULL,0,0,NULL,0),(4,'2010-07-10 14:44:06','2013-01-19 17:22:48',0,0,'DECAP',1,NULL,'Decapsuleur','','',NULL,NULL,5.00000000,5.62500000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,1,0,'',NULL,NULL,0,'','',NULL,2,-3,NULL,0,NULL,0,NULL,0,1001,10.00000000,NULL,1,0,NULL,0),(5,'2011-07-20 23:11:38','2011-07-27 17:02:59',0,0,'aaaa',1,NULL,'aaaa','cccc','bbbb','',NULL,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,1,1,0,'',NULL,NULL,0,'','',NULL,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0.00000000,'',1,0,NULL,0),(6,'2011-07-29 22:16:44','2011-07-29 20:16:44',0,0,'Copy_of_aaaa',1,NULL,'aaaa','cccc','bbbb','',NULL,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,0,1,0,'',NULL,NULL,0,'','',NULL,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0.00000000,'',1,0,NULL,0),(7,'2011-07-29 22:31:21','2011-07-29 20:31:21',0,0,'Copy_of_Copy_of_aaaa',1,NULL,'aaaa','cccc','bbbb','',NULL,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,0,0,0,'',NULL,NULL,0,'','',NULL,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0.00000000,'',1,0,NULL,0),(8,'2011-07-29 22:46:54','2011-07-29 20:46:54',0,0,'Copy_of_Copy_of_Copy_of_aaaa',1,NULL,'aaaa','cccc','bbbb','',NULL,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,0,0,0,'',NULL,NULL,0,'','',NULL,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0.00000000,'',1,0,NULL,0),(10,'2008-12-31 00:00:00','2012-12-08 13:11:14',0,0,'PR123456',1,NULL,'My product','This is a description example for record','Some note',NULL,NULL,100.00000000,110.00000000,0.00000000,0.00000000,'HT',10.000,0,0.000,0.000,NULL,0,0,0,'1y',0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00000000,NULL,NULL,0,'20110729232310',0),(11,'2013-01-13 20:24:42','2013-01-19 17:22:48',0,0,'gh',1,NULL,'hfghf','','','',NULL,0.00000000,0.00000000,0.00000000,0.00000000,'HT',0.000,0,0.000,0.000,1,1,1,0,'',NULL,NULL,0,'','',NULL,NULL,0,NULL,0,NULL,0,NULL,0,-1,0.00000000,'',1,0,NULL,0);
/*!40000 ALTER TABLE `llx_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_association`
--

DROP TABLE IF EXISTS `llx_product_association`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_association` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_product_pere` int(11) NOT NULL DEFAULT '0',
  `fk_product_fils` int(11) NOT NULL DEFAULT '0',
  `qty` double DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_association` (`fk_product_pere`,`fk_product_fils`),
  KEY `idx_product_association` (`fk_product_fils`),
  KEY `idx_product_association_fils` (`fk_product_fils`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_association`
--

LOCK TABLES `llx_product_association` WRITE;
/*!40000 ALTER TABLE `llx_product_association` DISABLE KEYS */;
INSERT INTO `llx_product_association` VALUES (1,4,1,2),(2,5,1,1);
/*!40000 ALTER TABLE `llx_product_association` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_extrafields`
--

DROP TABLE IF EXISTS `llx_product_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_product_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_extrafields`
--

LOCK TABLES `llx_product_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_product_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_fournisseur_price`
--

DROP TABLE IF EXISTS `llx_product_fournisseur_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_fournisseur_price` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_product` int(11) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `ref_fourn` varchar(30) DEFAULT NULL,
  `fk_availability` int(11) DEFAULT NULL,
  `price` double(24,8) DEFAULT '0.00000000',
  `quantity` double DEFAULT NULL,
  `remise_percent` double NOT NULL DEFAULT '0',
  `remise` double NOT NULL DEFAULT '0',
  `unitprice` double(24,8) DEFAULT '0.00000000',
  `charges` double(24,8) DEFAULT '0.00000000',
  `unitcharges` double(24,8) DEFAULT '0.00000000',
  `tva_tx` double(6,3) NOT NULL DEFAULT '0.000',
  `info_bits` int(11) NOT NULL DEFAULT '0',
  `fk_user` int(11) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_fournisseur_price_ref` (`ref_fourn`,`fk_soc`,`quantity`,`entity`),
  KEY `idx_product_fournisseur_price_fk_user` (`fk_user`),
  KEY `idx_product_fourn_price_fk_product` (`fk_product`,`entity`),
  KEY `idx_product_fourn_price_fk_soc` (`fk_soc`,`entity`),
  CONSTRAINT `fk_product_fournisseur_price_fk_product` FOREIGN KEY (`fk_product`) REFERENCES `llx_product` (`rowid`),
  CONSTRAINT `fk_product_fournisseur_price_fk_user` FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_fournisseur_price`
--

LOCK TABLES `llx_product_fournisseur_price` WRITE;
/*!40000 ALTER TABLE `llx_product_fournisseur_price` DISABLE KEYS */;
INSERT INTO `llx_product_fournisseur_price` VALUES (1,'2010-07-11 18:45:42','2012-12-08 13:11:08',4,1,'ABCD',NULL,10.00000000,1,0,0,10.00000000,0.00000000,0.00000000,0.000,0,1,NULL,1);
/*!40000 ALTER TABLE `llx_product_fournisseur_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_fournisseur_price_log`
--

DROP TABLE IF EXISTS `llx_product_fournisseur_price_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_fournisseur_price_log` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `fk_product_fournisseur` int(11) NOT NULL,
  `price` double(24,8) DEFAULT '0.00000000',
  `quantity` double DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_fournisseur_price_log`
--

LOCK TABLES `llx_product_fournisseur_price_log` WRITE;
/*!40000 ALTER TABLE `llx_product_fournisseur_price_log` DISABLE KEYS */;
INSERT INTO `llx_product_fournisseur_price_log` VALUES (1,'2010-07-11 18:45:42',1,10.00000000,1,1);
/*!40000 ALTER TABLE `llx_product_fournisseur_price_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_lang`
--

DROP TABLE IF EXISTS `llx_product_lang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_lang` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_product` int(11) NOT NULL DEFAULT '0',
  `lang` varchar(5) NOT NULL DEFAULT '0',
  `label` varchar(255) NOT NULL,
  `description` text,
  `note` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_lang` (`fk_product`,`lang`),
  CONSTRAINT `fk_product_lang_fk_product` FOREIGN KEY (`fk_product`) REFERENCES `llx_product` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_lang`
--

LOCK TABLES `llx_product_lang` WRITE;
/*!40000 ALTER TABLE `llx_product_lang` DISABLE KEYS */;
INSERT INTO `llx_product_lang` VALUES (1,1,'en_US','Pink dress','A beatifull pink dress',''),(2,2,'en_US','Product P1','',''),(3,3,'en_US','Service S1','',''),(4,4,'fr_FR','Decapsuleur','',''),(5,5,'en_US','aaaa','cccc','bbbb'),(6,6,'en_US','aaaa','cccc','bbbb'),(7,7,'en_US','aaaa','cccc','bbbb'),(8,8,'en_US','aaaa','cccc','bbbb'),(9,11,'fr_FR','hfghf','',''),(10,2,'fr_FR','Product P1','','');
/*!40000 ALTER TABLE `llx_product_lang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_price`
--

DROP TABLE IF EXISTS `llx_product_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_price` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_product` int(11) NOT NULL,
  `date_price` datetime NOT NULL,
  `price_level` smallint(6) DEFAULT '1',
  `price` double(24,8) DEFAULT NULL,
  `price_ttc` double(24,8) DEFAULT NULL,
  `price_min` double(24,8) DEFAULT NULL,
  `price_min_ttc` double(24,8) DEFAULT NULL,
  `price_base_type` varchar(3) DEFAULT 'HT',
  `tva_tx` double(6,3) NOT NULL,
  `recuperableonly` int(11) NOT NULL DEFAULT '0',
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `fk_user_author` int(11) DEFAULT NULL,
  `tosell` tinyint(4) DEFAULT '1',
  `price_by_qty` int(11) NOT NULL DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_price`
--

LOCK TABLES `llx_product_price` WRITE;
/*!40000 ALTER TABLE `llx_product_price` DISABLE KEYS */;
INSERT INTO `llx_product_price` VALUES (1,1,'2010-07-08 12:33:17',1,'2010-07-08 14:33:17',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,0,NULL),(2,1,'2010-07-08 22:30:01',2,'2010-07-09 00:30:01',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,0,NULL),(3,1,'2010-07-08 22:30:25',3,'2010-07-09 00:30:25',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,0,NULL),(4,1,'2010-07-10 12:44:06',4,'2010-07-10 14:44:06',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,0,NULL),(5,1,'2011-07-20 21:11:38',5,'2011-07-20 23:11:38',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,1,0,NULL),(6,1,'2011-07-27 17:02:59',5,'2011-07-27 19:02:59',1,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,1,0,NULL),(7,1,'2011-07-29 20:16:44',6,'2011-07-29 22:16:44',1,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,0,0,NULL),(8,1,'2011-07-29 20:31:21',7,'2011-07-29 22:31:21',1,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,0,0,NULL),(9,1,'2011-07-29 20:46:54',8,'2011-07-29 22:46:54',1,10.00000000,11.96000000,0.00000000,0.00000000,'HT',19.600,0,0.000,0.000,1,0,0,NULL),(10,1,'2011-07-31 22:34:27',4,'2011-08-01 00:34:27',1,5.00000000,5.62500000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,0,NULL),(12,1,'2013-01-13 19:24:59',11,'2013-01-13 20:24:59',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',0.000,0,0.000,0.000,1,1,0,NULL),(13,1,'2013-03-12 09:30:24',1,'2013-03-12 10:30:24',1,100.00000000,112.50000000,90.00000000,101.25000000,'HT',12.500,0,0.000,0.000,1,1,0,NULL);
/*!40000 ALTER TABLE `llx_product_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_price_by_qty`
--

DROP TABLE IF EXISTS `llx_product_price_by_qty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_price_by_qty` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_product_price` int(11) NOT NULL,
  `date_price` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `price` double(24,8) DEFAULT '0.00000000',
  `price_ttc` double(24,8) DEFAULT '0.00000000',
  `remise_percent` double NOT NULL DEFAULT '0',
  `remise` double NOT NULL DEFAULT '0',
  `qty_min` double DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_price_by_qty_level` (`fk_product_price`,`qty_min`),
  KEY `idx_product_price_by_qty_fk_product_price` (`fk_product_price`),
  CONSTRAINT `fk_product_price_by_qty_fk_product_price` FOREIGN KEY (`fk_product_price`) REFERENCES `llx_product_price` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_price_by_qty`
--

LOCK TABLES `llx_product_price_by_qty` WRITE;
/*!40000 ALTER TABLE `llx_product_price_by_qty` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_price_by_qty` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_stock`
--

DROP TABLE IF EXISTS `llx_product_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_stock` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_product` int(11) NOT NULL,
  `fk_entrepot` int(11) NOT NULL,
  `reel` double DEFAULT NULL,
  `pmp` double(24,8) NOT NULL DEFAULT '0.00000000',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_stock` (`fk_product`,`fk_entrepot`),
  KEY `idx_product_stock_fk_product` (`fk_product`),
  KEY `idx_product_stock_fk_entrepot` (`fk_entrepot`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_stock`
--

LOCK TABLES `llx_product_stock` WRITE;
/*!40000 ALTER TABLE `llx_product_stock` DISABLE KEYS */;
INSERT INTO `llx_product_stock` VALUES (1,'2010-07-08 22:43:51',2,2,1000,0.00000000,NULL),(3,'2010-07-10 23:02:20',4,2,1000,0.00000000,NULL),(4,'2013-01-19 17:22:48',4,1,1,10.00000000,NULL),(5,'2013-01-19 17:22:48',1,1,2,0.00000000,NULL),(6,'2013-01-19 17:22:48',11,1,-1,0.00000000,NULL),(7,'2013-01-19 17:31:58',2,1,-2,0.00000000,NULL);
/*!40000 ALTER TABLE `llx_product_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_subproduct`
--

DROP TABLE IF EXISTS `llx_product_subproduct`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_subproduct` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_product` int(11) NOT NULL,
  `fk_product_subproduct` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `fk_product` (`fk_product`,`fk_product_subproduct`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_subproduct`
--

LOCK TABLES `llx_product_subproduct` WRITE;
/*!40000 ALTER TABLE `llx_product_subproduct` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_subproduct` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet`
--

DROP TABLE IF EXISTS `llx_projet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_projet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT NULL,
  `datec` date DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dateo` date DEFAULT NULL,
  `datee` date DEFAULT NULL,
  `ref` varchar(50) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `title` varchar(255) NOT NULL,
  `description` text,
  `fk_user_creat` int(11) NOT NULL,
  `public` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_projet_ref` (`ref`,`entity`),
  KEY `idx_projet_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_projet_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_projet`
--

LOCK TABLES `llx_projet` WRITE;
/*!40000 ALTER TABLE `llx_projet` DISABLE KEYS */;
INSERT INTO `llx_projet` VALUES (1,NULL,'2010-07-09','2010-07-11 13:28:28','2010-07-09',NULL,'PROJ1',1,'Project One','',1,0,1,NULL,NULL,'baleine'),(2,NULL,'2010-07-09','2010-07-08 22:49:56','2010-07-09',NULL,'PROJ2',1,'Project Two','',1,0,0,NULL,NULL,NULL),(3,1,'2010-07-09','2010-07-08 22:50:19','2010-07-09',NULL,'PROJABC',1,'Project to create ABC company','',1,0,0,NULL,NULL,NULL),(4,NULL,'2010-07-09','2010-07-08 22:50:49','2010-07-09',NULL,'PROJSHARED',1,'The Global project','',1,1,1,NULL,NULL,NULL),(5,NULL,'2010-07-11','2010-07-11 14:22:49','2010-07-11','2011-07-14','RMLL',1,'Projet gestion RMLL 2011','',1,1,1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_projet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet_extrafields`
--

DROP TABLE IF EXISTS `llx_projet_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_projet_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_projet_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_projet_extrafields`
--

LOCK TABLES `llx_projet_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_projet_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_projet_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet_task`
--

DROP TABLE IF EXISTS `llx_projet_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_projet_task` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(50) DEFAULT NULL,
  `fk_projet` int(11) NOT NULL,
  `fk_task_parent` int(11) NOT NULL DEFAULT '0',
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dateo` datetime DEFAULT NULL,
  `datee` datetime DEFAULT NULL,
  `datev` datetime DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  `description` text,
  `duration_effective` double NOT NULL DEFAULT '0',
  `planned_workload` double NOT NULL DEFAULT '0',
  `progress` int(11) DEFAULT '0',
  `priority` int(11) DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `note_private` text,
  `note_public` text,
  `rang` int(11) DEFAULT '0',
  `model_pdf` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_projet_task_fk_projet` (`fk_projet`),
  KEY `idx_projet_task_fk_user_creat` (`fk_user_creat`),
  KEY `idx_projet_task_fk_user_valid` (`fk_user_valid`),
  CONSTRAINT `fk_projet_task_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_projet_task_fk_user_creat` FOREIGN KEY (`fk_user_creat`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_projet_task_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_projet_task`
--

LOCK TABLES `llx_projet_task` WRITE;
/*!40000 ALTER TABLE `llx_projet_task` DISABLE KEYS */;
INSERT INTO `llx_projet_task` VALUES (1,'1',1,0,'2010-07-11 15:15:55','2013-09-08 23:06:14','2010-07-11 12:00:00',NULL,NULL,'Work on module','',25920000,0,0,0,1,NULL,0,NULL,NULL,0,NULL),(2,'2',5,0,'2010-07-11 16:23:53','2013-09-08 23:06:14','2010-07-11 12:00:00','2011-07-14 12:00:00',NULL,'Heberger site RMLL','',0,0,0,0,1,NULL,0,NULL,NULL,0,NULL);
/*!40000 ALTER TABLE `llx_projet_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet_task_extrafields`
--

DROP TABLE IF EXISTS `llx_projet_task_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_projet_task_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_projet_task_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_projet_task_extrafields`
--

LOCK TABLES `llx_projet_task_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_projet_task_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_projet_task_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_projet_task_time`
--

DROP TABLE IF EXISTS `llx_projet_task_time`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_projet_task_time` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_task` int(11) NOT NULL,
  `task_date` date DEFAULT NULL,
  `task_duration` double DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_projet_task_time`
--

LOCK TABLES `llx_projet_task_time` WRITE;
/*!40000 ALTER TABLE `llx_projet_task_time` DISABLE KEYS */;
INSERT INTO `llx_projet_task_time` VALUES (1,1,'2010-07-11',25920000,1,'');
/*!40000 ALTER TABLE `llx_projet_task_time` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_propal`
--

DROP TABLE IF EXISTS `llx_propal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_propal` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(255) DEFAULT NULL,
  `ref_int` varchar(255) DEFAULT NULL,
  `ref_client` varchar(255) DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  `datep` date DEFAULT NULL,
  `fin_validite` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_user_cloture` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `price` double DEFAULT '0',
  `remise_percent` double DEFAULT '0',
  `remise_absolue` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `tva` double(24,8) DEFAULT '0.00000000',
  `localtax1` double(24,8) DEFAULT '0.00000000',
  `localtax2` double(24,8) DEFAULT '0.00000000',
  `total` double(24,8) DEFAULT '0.00000000',
  `fk_account` int(11) DEFAULT NULL,
  `fk_currency` varchar(2) DEFAULT NULL,
  `fk_cond_reglement` int(11) DEFAULT NULL,
  `fk_mode_reglement` int(11) DEFAULT NULL,
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  `date_livraison` date DEFAULT NULL,
  `fk_availability` int(11) DEFAULT NULL,
  `fk_delivery_address` int(11) DEFAULT NULL,
  `fk_input_reason` int(11) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_propal_ref` (`ref`,`entity`),
  KEY `idx_propal_fk_soc` (`fk_soc`),
  KEY `idx_propal_fk_user_author` (`fk_user_author`),
  KEY `idx_propal_fk_user_valid` (`fk_user_valid`),
  KEY `idx_propal_fk_user_cloture` (`fk_user_cloture`),
  KEY `idx_propal_fk_projet` (`fk_projet`),
  KEY `idx_propal_fk_account` (`fk_account`),
  KEY `idx_propal_fk_currency` (`fk_currency`),
  CONSTRAINT `fk_propal_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_propal_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_propal_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_propal_fk_user_cloture` FOREIGN KEY (`fk_user_cloture`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_propal_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_propal`
--

LOCK TABLES `llx_propal` WRITE;
/*!40000 ALTER TABLE `llx_propal` DISABLE KEYS */;
INSERT INTO `llx_propal` VALUES (1,2,NULL,'2012-12-08 13:11:07','PR1007-0001',1,NULL,NULL,'','2010-07-09 01:33:49','2010-07-09','2010-07-24 12:00:00','2011-08-08 14:24:18',NULL,1,1,NULL,1,0,NULL,NULL,0,30.00000000,3.84000000,0.00000000,0.00000000,33.84000000,NULL,NULL,1,0,'','','azur',NULL,NULL,NULL,0,NULL,NULL),(2,1,NULL,'2012-12-08 13:11:07','PR1007-0002',1,NULL,NULL,'','2010-07-10 02:11:44','2010-07-10','2010-07-25 12:00:00','2010-07-10 02:12:55','2011-07-20 15:23:12',1,1,1,2,0,NULL,NULL,0,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,NULL,NULL,1,1,'','','azur',NULL,NULL,NULL,0,NULL,NULL),(3,4,NULL,'2012-12-08 13:11:07','PR1007-0003',1,NULL,NULL,'','2010-07-18 11:35:11','2010-07-18','2010-08-02 12:00:00','2010-07-18 11:36:18','2011-07-20 15:21:15',1,1,1,2,0,NULL,NULL,0,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,NULL,NULL,1,0,'','','azur',NULL,NULL,NULL,0,NULL,NULL),(4,17,NULL,'2012-12-08 13:11:07','PR1108-0004',1,NULL,NULL,'','2011-08-04 23:36:23','2011-08-05','2011-08-20 12:00:00','2011-08-08 14:24:24',NULL,1,1,NULL,1,0,NULL,NULL,0,30.00000000,5.88000000,0.00000000,0.00000000,35.88000000,NULL,NULL,1,0,'','','azur',NULL,0,NULL,0,NULL,NULL),(5,19,NULL,'2013-02-17 14:39:56','PR1302-0005',1,NULL,NULL,'','2013-02-17 15:39:56','2013-02-17','2013-03-04 12:00:00',NULL,NULL,1,NULL,NULL,0,0,NULL,NULL,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,1,0,'','','azur',NULL,0,NULL,0,NULL,NULL),(6,19,NULL,'2013-02-17 14:40:12','PR1302-0006',1,NULL,NULL,'','2013-02-17 15:40:12','2013-02-17','2013-03-04 12:00:00',NULL,NULL,1,NULL,NULL,0,0,NULL,NULL,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,1,0,'','','azur',NULL,0,NULL,0,NULL,NULL),(7,19,NULL,'2013-02-17 14:41:15','PR1302-0007',1,NULL,NULL,'','2013-02-17 15:41:15','2013-02-17','2013-03-04 12:00:00',NULL,NULL,1,NULL,NULL,0,0,NULL,NULL,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,1,0,'','','azur',NULL,0,NULL,0,NULL,NULL),(8,19,NULL,'2013-02-17 14:43:39','PR1302-0008',1,NULL,NULL,'','2013-02-17 15:43:39','2013-02-17','2013-03-04 12:00:00',NULL,NULL,1,NULL,NULL,0,0,NULL,NULL,0,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,NULL,NULL,1,0,'','','azur',NULL,0,NULL,0,NULL,NULL),(9,19,NULL,'2013-02-17 15:22:14','PR1302-0009',1,NULL,NULL,'','2013-02-17 15:53:01','2013-02-17','2013-03-04 12:00:00','2013-02-17 16:22:10','2013-02-17 16:22:14',1,1,1,2,0,NULL,NULL,0,60.00000000,11.76000000,0.00000000,0.00000000,71.76000000,NULL,NULL,1,0,'','','azur',NULL,0,NULL,0,NULL,NULL),(11,18,NULL,'2013-02-17 15:28:22','PR1302-0010',1,NULL,NULL,'gfdf','2013-02-17 16:27:18','2013-02-17','2013-03-04 12:00:00','2013-02-17 16:27:29','2013-02-17 16:28:22',1,1,1,2,0,NULL,NULL,0,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,NULL,NULL,1,0,'','','azur',NULL,0,NULL,0,NULL,NULL),(12,23,NULL,'2013-03-08 09:02:31','PR1303-0011',1,NULL,NULL,'fdfs','2013-03-08 10:00:23','2013-03-08','2013-03-23 12:00:00','2013-03-08 10:02:21','2013-03-08 10:02:31',1,1,1,2,0,NULL,NULL,0,5.00000000,0.00000000,0.00000000,0.00000000,5.00000000,NULL,NULL,1,0,'','','azur',NULL,0,NULL,0,NULL,NULL);
/*!40000 ALTER TABLE `llx_propal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_propal_extrafields`
--

DROP TABLE IF EXISTS `llx_propal_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_propal_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_propal_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_propal_extrafields`
--

LOCK TABLES `llx_propal_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_propal_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_propal_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_propaldet`
--

DROP TABLE IF EXISTS `llx_propaldet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_propaldet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_propal` int(11) DEFAULT NULL,
  `fk_parent_line` int(11) DEFAULT NULL,
  `fk_product` int(11) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `description` text,
  `fk_remise_except` int(11) DEFAULT NULL,
  `tva_tx` double(6,3) DEFAULT '0.000',
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax1_type` varchar(10) NOT NULL DEFAULT '0',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `localtax2_type` varchar(10) NOT NULL DEFAULT '0',
  `qty` double DEFAULT NULL,
  `remise_percent` double DEFAULT '0',
  `remise` double DEFAULT '0',
  `price` double DEFAULT NULL,
  `subprice` double(24,8) DEFAULT '0.00000000',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `total_tva` double(24,8) DEFAULT '0.00000000',
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `info_bits` int(11) DEFAULT '0',
  `fk_product_fournisseur_price` int(11) DEFAULT NULL,
  `buy_price_ht` double(24,8) DEFAULT '0.00000000',
  `special_code` int(10) unsigned DEFAULT '0',
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  KEY `idx_propaldet_fk_propal` (`fk_propal`),
  KEY `idx_propaldet_fk_product` (`fk_product`),
  CONSTRAINT `fk_propaldet_fk_propal` FOREIGN KEY (`fk_propal`) REFERENCES `llx_propal` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_propaldet`
--

LOCK TABLES `llx_propaldet` WRITE;
/*!40000 ALTER TABLE `llx_propaldet` DISABLE KEYS */;
INSERT INTO `llx_propaldet` VALUES (1,1,NULL,NULL,NULL,'Une machine à café',NULL,12.500,0.000,'',0.000,'',1,0,0,NULL,10.00000000,10.00000000,1.25000000,0.00000000,0.00000000,11.25000000,0,NULL,NULL,0,NULL,0.00000000,0,1),(2,2,NULL,NULL,NULL,'Product 1',NULL,0.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,1),(3,2,NULL,2,NULL,'',NULL,0.000,0.000,'',0.000,'',1,0,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,NULL,0.00000000,0,2),(4,3,NULL,NULL,NULL,'A new marvelous product',NULL,0.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,NULL,0.00000000,0,1),(5,1,NULL,5,NULL,'cccc',NULL,19.600,0.000,'',0.000,'',1,0,0,NULL,10.00000000,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,0,NULL,NULL,0,NULL,0.00000000,0,2),(11,1,NULL,4,NULL,'',NULL,0.000,0.000,'',0.000,'',1,0,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,NULL,0.00000000,0,3),(12,1,NULL,4,NULL,'',NULL,0.000,0.000,'',0.000,'',1,0,0,NULL,5.00000000,5.00000000,0.00000000,0.00000000,0.00000000,5.00000000,0,NULL,NULL,0,NULL,0.00000000,0,4),(13,1,NULL,4,NULL,'',NULL,12.500,0.000,'',0.000,'',1,0,0,NULL,5.00000000,5.00000000,0.63000000,0.00000000,0.00000000,5.63000000,0,NULL,NULL,0,NULL,0.00000000,0,5),(19,4,NULL,NULL,NULL,'bvbcvbcvbcbcbcb',NULL,19.600,0.000,'',0.000,'',1,0,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,NULL,0.00000000,0,1),(20,4,NULL,NULL,NULL,'ghjhgjghjgh',NULL,19.600,0.000,'',0.000,'',1,0,0,NULL,10.00000000,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,0,NULL,NULL,0,NULL,0.00000000,0,2),(21,4,NULL,NULL,NULL,'ghjghjhgjg',NULL,19.600,0.000,'',0.000,'',2,0,0,10,10.00000000,20.00000000,3.92000000,0.00000000,0.00000000,23.92000000,1,NULL,NULL,0,NULL,0.00000000,0,3),(22,9,NULL,NULL,NULL,'gdfg',NULL,19.600,0.000,'',0.000,'',1,0,0,NULL,10.00000000,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,0,NULL,NULL,0,0,0.00000000,0,1),(23,9,NULL,NULL,NULL,'gfdgd',NULL,19.600,0.000,'',0.000,'',1,0,0,NULL,50.00000000,50.00000000,9.80000000,0.00000000,0.00000000,59.80000000,1,NULL,NULL,0,0,0.00000000,0,2),(24,11,NULL,NULL,NULL,'gfdg',NULL,19.600,0.000,'',0.000,'',1,0,0,NULL,10.00000000,10.00000000,1.96000000,0.00000000,0.00000000,11.96000000,0,NULL,NULL,0,0,0.00000000,0,1),(25,12,NULL,NULL,NULL,'fdsfs',NULL,0.000,0.000,'',0.000,'',1,0,0,NULL,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,0,0.00000000,0,1),(26,12,NULL,NULL,NULL,'fsdfsf',NULL,0.000,0.000,'',0.000,'',1,0,0,NULL,-5.00000000,-5.00000000,0.00000000,0.00000000,0.00000000,-5.00000000,0,NULL,NULL,0,0,0.00000000,0,2);
/*!40000 ALTER TABLE `llx_propaldet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_propaldet_extrafields`
--

DROP TABLE IF EXISTS `llx_propaldet_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_propaldet_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_propaldet_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_propaldet_extrafields`
--

LOCK TABLES `llx_propaldet_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_propaldet_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_propaldet_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_rights_def`
--

DROP TABLE IF EXISTS `llx_rights_def`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_rights_def` (
  `id` int(11) NOT NULL DEFAULT '0',
  `libelle` varchar(255) DEFAULT NULL,
  `module` varchar(64) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `perms` varchar(50) DEFAULT NULL,
  `subperms` varchar(50) DEFAULT NULL,
  `type` varchar(1) DEFAULT NULL,
  `bydefault` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`,`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_rights_def`
--

LOCK TABLES `llx_rights_def` WRITE;
/*!40000 ALTER TABLE `llx_rights_def` DISABLE KEYS */;
INSERT INTO `llx_rights_def` VALUES (11,'Lire les factures','facture',1,'lire',NULL,'a',1),(11,'Lire les factures','facture',2,'lire',NULL,'a',1),(12,'Creer/modifier les factures','facture',1,'creer',NULL,'a',0),(12,'Creer/modifier les factures','facture',2,'creer',NULL,'a',0),(13,'Dévalider les factures','facture',1,'invoice_advance','unvalidate','a',0),(13,'Dévalider les factures','facture',2,'invoice_advance','unvalidate','a',0),(14,'Valider les factures','facture',1,'valider',NULL,'a',0),(14,'Valider les factures','facture',2,'valider',NULL,'a',0),(15,'Envoyer les factures par mail','facture',1,'invoice_advance','send','a',0),(15,'Envoyer les factures par mail','facture',2,'invoice_advance','send','a',0),(16,'Emettre des paiements sur les factures','facture',1,'paiement',NULL,'a',0),(16,'Emettre des paiements sur les factures','facture',2,'paiement',NULL,'a',0),(19,'Supprimer les factures','facture',1,'supprimer',NULL,'a',0),(19,'Supprimer les factures','facture',2,'supprimer',NULL,'a',0),(21,'Lire les propositions commerciales','propale',1,'lire',NULL,'r',1),(21,'Lire les propositions commerciales','propale',2,'lire',NULL,'r',1),(22,'Creer/modifier les propositions commerciales','propale',1,'creer',NULL,'w',0),(22,'Creer/modifier les propositions commerciales','propale',2,'creer',NULL,'w',0),(24,'Valider les propositions commerciales','propale',1,'valider',NULL,'d',0),(24,'Valider les propositions commerciales','propale',2,'valider',NULL,'d',0),(25,'Envoyer les propositions commerciales aux clients','propale',1,'propal_advance','send','d',0),(25,'Envoyer les propositions commerciales aux clients','propale',2,'propal_advance','send','d',0),(26,'Cloturer les propositions commerciales','propale',1,'cloturer',NULL,'d',0),(26,'Cloturer les propositions commerciales','propale',2,'cloturer',NULL,'d',0),(27,'Supprimer les propositions commerciales','propale',1,'supprimer',NULL,'d',0),(27,'Supprimer les propositions commerciales','propale',2,'supprimer',NULL,'d',0),(28,'Exporter les propositions commerciales et attributs','propale',1,'export',NULL,'r',0),(28,'Exporter les propositions commerciales et attributs','propale',2,'export',NULL,'r',0),(31,'Lire les produits','produit',1,'lire',NULL,'r',1),(31,'Lire les produits','produit',2,'lire',NULL,'r',1),(32,'Creer/modifier les produits','produit',1,'creer',NULL,'w',0),(32,'Creer/modifier les produits','produit',2,'creer',NULL,'w',0),(34,'Supprimer les produits','produit',1,'supprimer',NULL,'d',0),(34,'Supprimer les produits','produit',2,'supprimer',NULL,'d',0),(38,'Exporter les produits','produit',1,'export',NULL,'r',0),(38,'Exporter les produits','produit',2,'export',NULL,'r',0),(41,'Lire les projets et taches (partagés ou dont je suis contact)','projet',1,'lire',NULL,'r',1),(42,'Creer/modifier les projets et taches (partagés ou dont je suis contact)','projet',1,'creer',NULL,'w',0),(44,'Supprimer les projets et taches (partagés ou dont je suis contact)','projet',1,'supprimer',NULL,'d',0),(61,'Lire les fiches d\'intervention','ficheinter',1,'lire',NULL,'r',1),(62,'Creer/modifier les fiches d\'intervention','ficheinter',1,'creer',NULL,'w',0),(64,'Supprimer les fiches d\'intervention','ficheinter',1,'supprimer',NULL,'d',0),(67,'Exporter les fiches interventions','ficheinter',1,'export',NULL,'r',0),(68,'Envoyer les fiches d\'intervention par courriel','ficheinter',1,'ficheinter_advance','send','r',0),(71,'Read members\' card','adherent',1,'lire',NULL,'r',1),(72,'Create/modify members (need also user module permissions if member linked to a user)','adherent',1,'creer',NULL,'w',1),(74,'Remove members','adherent',1,'supprimer',NULL,'d',1),(75,'Setup types and attributes of members','adherent',1,'configurer',NULL,'w',1),(76,'Export members','adherent',1,'export',NULL,'r',0),(78,'Read subscriptions','adherent',1,'cotisation','lire','r',1),(79,'Create/modify/remove subscriptions','adherent',1,'cotisation','creer','w',1),(81,'Lire les commandes clients','commande',1,'lire',NULL,'r',1),(82,'Creer/modifier les commandes clients','commande',1,'creer',NULL,'w',0),(84,'Valider les commandes clients','commande',1,'valider',NULL,'d',0),(86,'Envoyer les commandes clients','commande',1,'order_advance','send','d',0),(87,'Cloturer les commandes clients','commande',1,'cloturer',NULL,'d',0),(88,'Annuler les commandes clients','commande',1,'annuler',NULL,'d',0),(89,'Supprimer les commandes clients','commande',1,'supprimer',NULL,'d',0),(91,'Lire les charges','tax',1,'charges','lire','r',1),(91,'Lire les charges','tax',2,'charges','lire','r',1),(92,'Creer/modifier les charges','tax',1,'charges','creer','w',0),(92,'Creer/modifier les charges','tax',2,'charges','creer','w',0),(93,'Supprimer les charges','tax',1,'charges','supprimer','d',0),(93,'Supprimer les charges','tax',2,'charges','supprimer','d',0),(94,'Exporter les charges','tax',1,'charges','export','r',0),(94,'Exporter les charges','tax',2,'charges','export','r',0),(95,'Lire CA, bilans, resultats','compta',1,'resultat','lire','r',1),(96,'Parametrer la ventilation','compta',1,'ventilation','parametrer','r',0),(97,'Lire les ventilations de factures','compta',1,'ventilation','lire','r',1),(98,'Ventiler les lignes de factures','compta',1,'ventilation','creer','r',0),(101,'Lire les expeditions','expedition',1,'lire',NULL,'r',1),(102,'Creer modifier les expeditions','expedition',1,'creer',NULL,'w',0),(104,'Valider les expeditions','expedition',1,'valider',NULL,'d',0),(105,'Envoyer les expeditions aux clients','expedition',1,'shipping_advance','send','d',0),(106,'Exporter les expeditions','expedition',1,'shipment','export','r',0),(109,'Supprimer les expeditions','expedition',1,'supprimer',NULL,'d',0),(111,'Lire les comptes bancaires','banque',1,'lire',NULL,'r',1),(111,'Lire les comptes bancaires','banque',2,'lire',NULL,'r',1),(112,'Creer/modifier montant/supprimer ecriture bancaire','banque',1,'modifier',NULL,'w',0),(112,'Creer/modifier montant/supprimer ecriture bancaire','banque',2,'modifier',NULL,'w',0),(113,'Configurer les comptes bancaires (creer, gerer categories)','banque',1,'configurer',NULL,'a',0),(113,'Configurer les comptes bancaires (creer, gerer categories)','banque',2,'configurer',NULL,'a',0),(114,'Rapprocher les ecritures bancaires','banque',1,'consolidate',NULL,'w',0),(114,'Rapprocher les ecritures bancaires','banque',2,'consolidate',NULL,'w',0),(115,'Exporter transactions et releves','banque',1,'export',NULL,'r',0),(115,'Exporter transactions et releves','banque',2,'export',NULL,'r',0),(116,'Virements entre comptes','banque',1,'transfer',NULL,'w',0),(116,'Virements entre comptes','banque',2,'transfer',NULL,'w',0),(117,'Gerer les envois de cheques','banque',1,'cheque',NULL,'w',0),(117,'Gerer les envois de cheques','banque',2,'cheque',NULL,'w',0),(121,'Lire les societes','societe',1,'lire',NULL,'r',1),(121,'Lire les societes','societe',2,'lire',NULL,'r',1),(122,'Creer modifier les societes','societe',1,'creer',NULL,'w',0),(122,'Creer modifier les societes','societe',2,'creer',NULL,'w',0),(125,'Supprimer les societes','societe',1,'supprimer',NULL,'d',0),(125,'Supprimer les societes','societe',2,'supprimer',NULL,'d',0),(126,'Exporter les societes','societe',1,'export',NULL,'r',0),(126,'Exporter les societes','societe',2,'export',NULL,'r',0),(141,'Lire tous les projets et taches (y compris prives qui ne me sont pas affectes)','projet',1,'all','lire','r',0),(142,'Creer/modifier tous les projets et taches (y compris prives qui ne me sont pas affectes)','projet',1,'all','creer','w',0),(144,'Supprimer tous les projets et taches (y compris prives qui ne me sont pas affectes)','projet',1,'all','supprimer','d',0),(151,'Read withdrawals','prelevement',1,'bons','lire','r',1),(152,'Create/modify a withdrawals','prelevement',1,'bons','creer','w',0),(153,'Send withdrawals to bank','prelevement',1,'bons','send','a',0),(154,'credit/refuse withdrawals','prelevement',1,'bons','credit','a',0),(161,'Lire les contrats','contrat',1,'lire',NULL,'r',1),(162,'Creer / modifier les contrats','contrat',1,'creer',NULL,'w',0),(163,'Activer un service d\'un contrat','contrat',1,'activer',NULL,'w',0),(164,'Desactiver un service d\'un contrat','contrat',1,'desactiver',NULL,'w',0),(165,'Supprimer un contrat','contrat',1,'supprimer',NULL,'d',0),(171,'Lire les deplacements','deplacement',1,'lire',NULL,'r',1),(172,'Creer/modifier les deplacements','deplacement',1,'creer',NULL,'w',0),(173,'Supprimer les deplacements','deplacement',1,'supprimer',NULL,'d',0),(178,'Exporter les deplacements','deplacement',1,'export',NULL,'d',0),(221,'Consulter les mailings','mailing',1,'lire',NULL,'r',1),(221,'Consulter les mailings','mailing',2,'lire',NULL,'r',1),(222,'Creer/modifier les mailings (sujet, destinataires...)','mailing',1,'creer',NULL,'w',1),(222,'Creer/modifier les mailings (sujet, destinataires...)','mailing',2,'creer',NULL,'w',0),(223,'Valider les mailings (permet leur envoi)','mailing',1,'valider',NULL,'w',0),(223,'Valider les mailings (permet leur envoi)','mailing',2,'valider',NULL,'w',0),(229,'Supprimer les mailings)','mailing',1,'supprimer',NULL,'d',1),(229,'Supprimer les mailings','mailing',2,'supprimer',NULL,'d',0),(237,'View recipients and info','mailing',1,'mailing_advance','recipient','r',0),(237,'View recipients and info','mailing',2,'mailing_advance','recipient','r',0),(238,'Manually send mailings','mailing',1,'mailing_advance','send','w',0),(238,'Manually send mailings','mailing',2,'mailing_advance','send','w',0),(239,'Delete mailings after validation and/or sent','mailing',1,'mailing_advance','delete','d',0),(239,'Delete mailings after validation and/or sent','mailing',2,'mailing_advance','delete','d',0),(241,'Lire les categories','categorie',1,'lire',NULL,'r',1),(242,'Creer/modifier les categories','categorie',1,'creer',NULL,'w',1),(243,'Supprimer les categories','categorie',1,'supprimer',NULL,'d',1),(251,'Consulter les autres utilisateurs','user',1,'user','lire','r',0),(252,'Consulter les permissions des autres utilisateurs','user',1,'user_advance','readperms','r',0),(253,'Creer/modifier utilisateurs internes et externes','user',1,'user','creer','w',0),(254,'Creer/modifier utilisateurs externes seulement','user',1,'user_advance','write','w',0),(255,'Modifier le mot de passe des autres utilisateurs','user',1,'user','password','w',0),(256,'Supprimer ou desactiver les autres utilisateurs','user',1,'user','supprimer','d',0),(262,'Consulter tous les tiers par utilisateurs internes (sinon uniquement si contact commercial). Non effectif pour utilisateurs externes (tjs limités à eux-meme).','societe',1,'client','voir','r',1),(262,'Consulter tous les tiers par utilisateurs internes (sinon uniquement si contact commercial). Non effectif pour utilisateurs externes (tjs limités à eux-meme).','societe',2,'client','voir','r',1),(281,'Lire les contacts','societe',1,'contact','lire','r',1),(281,'Lire les contacts','societe',2,'contact','lire','r',1),(282,'Creer modifier les contacts','societe',1,'contact','creer','w',0),(282,'Creer modifier les contacts','societe',2,'contact','creer','w',0),(283,'Supprimer les contacts','societe',1,'contact','supprimer','d',0),(283,'Supprimer les contacts','societe',2,'contact','supprimer','d',0),(286,'Exporter les contacts','societe',1,'contact','export','d',0),(286,'Exporter les contacts','societe',2,'contact','export','d',0),(331,'Lire les bookmarks','bookmark',1,'lire',NULL,'r',1),(332,'Creer/modifier les bookmarks','bookmark',1,'creer',NULL,'r',0),(333,'Supprimer les bookmarks','bookmark',1,'supprimer',NULL,'r',0),(341,'Consulter ses propres permissions','user',1,'self_advance','readperms','r',1),(342,'Creer/modifier ses propres infos utilisateur','user',1,'self','creer','w',1),(343,'Modifier son propre mot de passe','user',1,'self','password','w',1),(344,'Modifier ses propres permissions','user',1,'self_advance','writeperms','w',1),(351,'Consulter les groupes','user',1,'group_advance','read','r',0),(352,'Consulter les permissions des groupes','user',1,'group_advance','readperms','r',0),(353,'Creer/modifier les groupes et leurs permissions','user',1,'group_advance','write','w',0),(354,'Supprimer ou desactiver les groupes','user',1,'group_advance','delete','d',0),(358,'Exporter les utilisateurs','user',1,'user','export','r',0),(531,'Lire les services','service',1,'lire',NULL,'r',1),(532,'Creer/modifier les services','service',1,'creer',NULL,'w',0),(534,'Supprimer les services','service',1,'supprimer',NULL,'d',0),(538,'Exporter les services','service',1,'export',NULL,'r',0),(700,'Lire les dons','don',1,'lire',NULL,'r',1),(701,'Creer/modifier les dons','don',1,'creer',NULL,'w',0),(701,'Lire les dons','don',2,'lire',NULL,'r',1),(702,'Supprimer les dons','don',1,'supprimer',NULL,'d',0),(702,'Creer/modifier les dons','don',2,'creer',NULL,'w',0),(703,'Supprimer les dons','don',1,'supprimer',NULL,'d',0),(703,'Supprimer les dons','don',2,'supprimer',NULL,'d',0),(1001,'Lire les stocks','stock',1,'lire',NULL,'r',1),(1002,'Creer/Modifier les stocks','stock',1,'creer',NULL,'w',1),(1003,'Supprimer les stocks','stock',1,'supprimer',NULL,'d',1),(1004,'Lire mouvements de stocks','stock',1,'mouvement','lire','r',1),(1005,'Creer/modifier mouvements de stocks','stock',1,'mouvement','creer','w',1),(1101,'Lire les bons de livraison','expedition',1,'livraison','lire','r',1),(1102,'Creer modifier les bons de livraison','expedition',1,'livraison','creer','w',0),(1104,'Valider les bons de livraison','expedition',1,'livraison','valider','d',0),(1109,'Supprimer les bons de livraison','expedition',1,'livraison','supprimer','d',0),(1181,'Consulter les fournisseurs','fournisseur',1,'lire',NULL,'r',1),(1182,'Consulter les commandes fournisseur','fournisseur',1,'commande','lire','r',1),(1183,'Creer une commande fournisseur','fournisseur',1,'commande','creer','w',0),(1184,'Valider une commande fournisseur','fournisseur',1,'commande','valider','w',0),(1185,'Approuver une commande fournisseur','fournisseur',1,'commande','approuver','w',0),(1186,'Commander une commande fournisseur','fournisseur',1,'commande','commander','w',0),(1187,'Receptionner une commande fournisseur','fournisseur',1,'commande','receptionner','d',0),(1188,'Supprimer une commande fournisseur','fournisseur',1,'commande','supprimer','d',0),(1201,'Lire les exports','export',1,'lire',NULL,'r',1),(1202,'Creer/modifier un export','export',1,'creer',NULL,'w',1),(1231,'Consulter les factures fournisseur','fournisseur',1,'facture','lire','r',1),(1232,'Creer une facture fournisseur','fournisseur',1,'facture','creer','w',0),(1233,'Valider une facture fournisseur','fournisseur',1,'facture','valider','w',0),(1234,'Supprimer une facture fournisseur','fournisseur',1,'facture','supprimer','d',0),(1235,'Envoyer les factures par mail','fournisseur',1,'supplier_invoice_advance','send','a',0),(1236,'Exporter les factures fournisseurs, attributs et reglements','fournisseur',1,'facture','export','r',0),(1237,'Exporter les commande fournisseurs, attributs','fournisseur',1,'commande','export','r',0),(1251,'Run mass imports of external data (data load)','import',1,'run',NULL,'r',0),(1321,'Exporter les factures clients, attributs et reglements','facture',1,'facture','export','r',0),(1321,'Exporter les factures clients, attributs et reglements','facture',2,'facture','export','r',0),(1421,'Exporter les commandes clients et attributs','commande',1,'commande','export','r',0),(2401,'Read actions/tasks linked to his account','agenda',1,'myactions','read','r',1),(2401,'Read actions/tasks linked to his account','agenda',2,'myactions','read','r',1),(2402,'Create/modify actions/tasks linked to his account','agenda',1,'myactions','create','w',0),(2402,'Create/modify actions/tasks linked to his account','agenda',2,'myactions','create','w',0),(2403,'Delete actions/tasks linked to his account','agenda',1,'myactions','delete','w',0),(2403,'Delete actions/tasks linked to his account','agenda',2,'myactions','delete','w',0),(2411,'Read actions/tasks of others','agenda',1,'allactions','read','r',0),(2411,'Read actions/tasks of others','agenda',2,'allactions','read','r',0),(2412,'Create/modify actions/tasks of others','agenda',1,'allactions','create','w',0),(2412,'Create/modify actions/tasks of others','agenda',2,'allactions','create','w',0),(2413,'Delete actions/tasks of others','agenda',1,'allactions','delete','w',0),(2413,'Delete actions/tasks of others','agenda',2,'allactions','delete','w',0),(2414,'Export actions/tasks of others','agenda',1,'export',NULL,'w',0),(2501,'Consulter/Télécharger les documents','ecm',1,'read',NULL,'r',1),(2503,'Soumettre ou supprimer des documents','ecm',1,'upload',NULL,'w',1),(2515,'Administrer les rubriques de documents','ecm',1,'setup',NULL,'w',1),(20001,'Créer / Modifier / Lire ses demandes de congés payés','holiday',1,'write',NULL,'w',1),(20001,'Créer / Modifier / Lire ses demandes de congés payés','holiday',2,'write',NULL,'w',1),(20002,'Lire / Modifier toutes les demandes de congés payés','holiday',1,'lire_tous',NULL,'w',0),(20002,'Lire / Modifier toutes les demandes de congés payés','holiday',2,'lire_tous',NULL,'w',0),(20003,'Supprimer des demandes de congés payés','holiday',1,'delete',NULL,'w',0),(20003,'Supprimer des demandes de congés payés','holiday',2,'delete',NULL,'w',0),(20004,'Définir les congés payés des utilisateurs','holiday',1,'define_holiday',NULL,'w',0),(20004,'Définir les congés payés des utilisateurs','holiday',2,'define_holiday',NULL,'w',0),(20005,'Voir les logs de modification des congés payés','holiday',1,'view_log',NULL,'w',0),(20005,'Voir les logs de modification des congés payés','holiday',2,'view_log',NULL,'w',0),(20006,'Accéder au rapport mensuel des congés payés','holiday',1,'month_report',NULL,'w',0),(20006,'Accéder au rapport mensuel des congés payés','holiday',2,'month_report',NULL,'w',0),(23001,'Read cron jobs','cron',1,'read',NULL,'w',1),(23002,'Create cron Jobs','cron',1,'create',NULL,'w',0),(23003,'Delete cron Jobs','cron',1,'delete',NULL,'w',0),(23004,'Execute cron Jobs','cron',1,'execute',NULL,'w',0),(50101,'Use point of sale','cashdesk',1,'use',NULL,'a',1),(101201,'Read/Browse directories and files from the file manager','filemanager',1,'read',NULL,'r',1),(101202,'Can manage directories or files (upload/edit/delete) from the file manager','filemanager',1,'create',NULL,'w',0),(101250,'Read surveys','opensurvey',1,'survey','read','r',0),(101250,'Read surveys','opensurvey',2,'survey','read','r',0),(101251,'Create/modify surveys','opensurvey',1,'survey','write','w',0),(101251,'Create/modify surveys','opensurvey',2,'survey','write','w',0),(400051,'Use POS','pos',2,'frontend',NULL,'a',1),(400052,'Use Backend','pos',2,'backend',NULL,'a',1),(400053,'Make Transfers','pos',2,'transfer',NULL,'a',1),(400055,'Stats','pos',2,'stats',NULL,'a',1);
/*!40000 ALTER TABLE `llx_rights_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe`
--

DROP TABLE IF EXISTS `llx_societe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `statut` tinyint(4) DEFAULT '0',
  `parent` int(11) DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` datetime DEFAULT NULL,
  `datea` datetime DEFAULT NULL,
  `nom` varchar(60) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(128) DEFAULT NULL,
  `ref_int` varchar(60) DEFAULT NULL,
  `code_client` varchar(24) DEFAULT NULL,
  `code_fournisseur` varchar(24) DEFAULT NULL,
  `code_compta` varchar(24) DEFAULT NULL,
  `code_compta_fournisseur` varchar(24) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `zip` varchar(25) DEFAULT NULL,
  `town` varchar(50) DEFAULT NULL,
  `fk_departement` int(11) DEFAULT '0',
  `fk_pays` int(11) DEFAULT '0',
  `phone` varchar(20) DEFAULT NULL,
  `fax` varchar(20) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `skype` varchar(255) DEFAULT NULL,
  `fk_effectif` int(11) DEFAULT '0',
  `fk_typent` int(11) DEFAULT '0',
  `fk_forme_juridique` int(11) DEFAULT '0',
  `fk_currency` int(11) DEFAULT '0',
  `siren` varchar(128) DEFAULT NULL,
  `siret` varchar(128) DEFAULT NULL,
  `ape` varchar(128) DEFAULT NULL,
  `idprof4` varchar(128) DEFAULT NULL,
  `tva_intra` varchar(20) DEFAULT NULL,
  `capital` double DEFAULT NULL,
  `fk_stcomm` int(11) NOT NULL,
  `note_private` text,
  `note_public` text,
  `prefix_comm` varchar(5) DEFAULT NULL,
  `client` tinyint(4) DEFAULT '0',
  `fournisseur` tinyint(4) DEFAULT '0',
  `supplier_account` varchar(32) DEFAULT NULL,
  `fk_prospectlevel` varchar(12) DEFAULT NULL,
  `customer_bad` tinyint(4) DEFAULT '0',
  `customer_rate` double DEFAULT '0',
  `supplier_rate` double DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  `remise_client` double DEFAULT '0',
  `mode_reglement` tinyint(4) DEFAULT NULL,
  `cond_reglement` tinyint(4) DEFAULT NULL,
  `mode_reglement_supplier` int(11) DEFAULT NULL,
  `outstanding_limit` double(24,8) DEFAULT NULL,
  `cond_reglement_supplier` int(11) DEFAULT NULL,
  `tva_assuj` tinyint(4) DEFAULT '1',
  `localtax1_assuj` tinyint(4) DEFAULT '0',
  `localtax2_assuj` tinyint(4) DEFAULT '0',
  `barcode` varchar(255) DEFAULT NULL,
  `price_level` int(11) DEFAULT NULL,
  `default_lang` varchar(6) DEFAULT NULL,
  `canvas` varchar(32) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '1',
  `logo` varchar(255) DEFAULT NULL,
  `idprof5` varchar(128) DEFAULT NULL,
  `idprof6` varchar(128) DEFAULT NULL,
  `fk_barcode_type` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_societe_prefix_comm` (`prefix_comm`,`entity`),
  UNIQUE KEY `uk_societe_code_client` (`code_client`,`entity`),
  KEY `idx_societe_user_creat` (`fk_user_creat`),
  KEY `idx_societe_user_modif` (`fk_user_modif`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe`
--

LOCK TABLES `llx_societe` WRITE;
/*!40000 ALTER TABLE `llx_societe` DISABLE KEYS */;
INSERT INTO `llx_societe` VALUES (1,0,NULL,'2012-12-19 14:47:50','2010-07-08 14:21:44','2012-12-19 15:47:49','ABC and Co',1,NULL,NULL,'CU1212-0007','SU1212-0005','7050','6050','1 alalah road',NULL,'Delhi',0,4,NULL,NULL,NULL,NULL,NULL,NULL,4,NULL,0,'','','','','',5000,1,NULL,NULL,NULL,1,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,1,0,NULL,NULL,'en_IN',NULL,NULL,1,NULL,'','',0),(2,0,NULL,'2011-07-31 22:35:08','2010-07-08 14:23:48','2011-08-01 00:35:08','Belin SARL',1,NULL,NULL,'CU1108-0001','SU1108-0001',NULL,NULL,'11 rue de la paix.','75000','Paris',0,117,NULL,NULL,'dolibarr.fr',NULL,NULL,NULL,3,NULL,0,'123456789','','ACE14','','',10000,0,NULL,NULL,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'fr_FR',NULL,NULL,1,NULL,NULL,NULL,0),(3,0,NULL,'2010-07-08 20:42:12','2010-07-08 22:42:12','2010-07-08 22:42:12','Spanish Comp',1,NULL,NULL,'SPANISHCOMP',NULL,NULL,NULL,'1 via mallere',NULL,'Madrid',123,4,NULL,NULL,NULL,NULL,NULL,3,4,408,0,'','','','','',10000,0,NULL,NULL,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'es_AR',NULL,NULL,1,NULL,NULL,NULL,0),(4,0,NULL,'2013-03-03 23:09:48','2010-07-08 22:48:18','2013-03-04 00:08:04','Prospector Vaalen',1,NULL,NULL,'CU1303-0014',NULL,NULL,NULL,'',NULL,'Bruxelles',103,2,NULL,NULL,NULL,NULL,NULL,3,4,201,0,'12345678','','','','',0,0,NULL,NULL,NULL,3,0,NULL,'PL_LOW',0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,0,0,NULL,NULL,NULL,NULL,NULL,1,NULL,'','',0),(5,0,NULL,'2010-07-08 21:37:56','2010-07-08 23:22:57','2010-07-08 23:37:56','NoCountry Co',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,193,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'','','','','',0,0,NULL,NULL,NULL,0,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,0),(6,0,NULL,'2010-07-08 22:25:06','2010-07-09 00:15:09','2010-07-09 00:25:06','Swiss customer supplier',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,'Genevia',0,6,NULL,NULL,NULL,'abademail@aa.com',NULL,2,2,601,0,'','','','','',56000,0,NULL,NULL,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,0),(7,0,NULL,'2013-02-12 16:06:20','2010-07-09 01:24:26','2013-02-12 17:06:20','Generic customer',1,NULL,NULL,'CU1302-0011',NULL,NULL,NULL,'',NULL,NULL,0,7,NULL,NULL,NULL,'ttt@ttt.com',NULL,NULL,8,NULL,0,'','','','','',0,0,'Generic customer to use for Point Of Sale module.<br />',NULL,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'','',0),(8,0,NULL,'2010-07-10 12:54:27','2010-07-10 14:54:27','2010-07-10 14:54:27','Client salon',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'','','','','',0,0,NULL,NULL,NULL,0,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,0),(9,0,NULL,'2010-07-10 12:55:11','2010-07-10 14:54:44','2010-07-10 14:55:11','Client salon invidivdu',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,NULL,8,NULL,0,'','','','','',0,0,NULL,NULL,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,0),(10,0,NULL,'2012-12-08 16:38:30','2010-07-10 15:13:08','2012-12-08 17:38:30','Smith Vick',1,NULL,NULL,'CU1212-0005',NULL,NULL,NULL,'',NULL,NULL,0,102,NULL,NULL,NULL,'vsmith@email.com',NULL,NULL,0,NULL,0,'','','','','',0,0,NULL,NULL,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'','',0),(11,0,NULL,'2010-07-11 12:35:22','2010-07-10 18:35:57','2010-07-10 18:36:24','Mon client',1,NULL,NULL,NULL,NULL,'7051',NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'','','','','',0,0,NULL,NULL,NULL,3,0,NULL,'PL_LOW',0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,0),(12,0,NULL,'2013-02-20 19:07:21','2010-07-11 16:18:08','2013-02-20 20:07:21','Dupont Alain',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,'toto@aa.com',NULL,NULL,0,NULL,0,'','','','','',0,0,NULL,NULL,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'','',0),(13,0,NULL,'2010-07-11 15:13:20','2010-07-11 17:13:20','2010-07-11 17:13:20','Vendeur de chips',1,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'','','','','',0,0,NULL,NULL,NULL,0,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,0),(15,0,NULL,'2011-08-01 00:31:24','2011-08-01 02:31:24','2011-08-01 02:31:24','mmm',1,NULL,NULL,'CU1108-0002','SU1108-0002',NULL,NULL,'','78180','mmm',0,31,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'','','','','',0,0,NULL,NULL,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,0),(16,0,NULL,'2011-08-01 00:42:21','2011-08-01 02:31:43','2011-08-01 02:42:21','ppp',1,NULL,NULL,'CU1108-0003','SU1108-0003',NULL,NULL,'','78180','mmm',103,2,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'','','','','',0,0,NULL,NULL,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,0),(17,0,NULL,'2011-08-04 21:24:24','2011-08-01 02:41:26','2011-08-04 23:24:24','FFF SARL',1,NULL,NULL,'CU1108-0004','SU1108-0004',NULL,NULL,'The French Company',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,1,3,NULL,0,'','','','','',0,0,NULL,NULL,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,0),(18,0,NULL,'2013-01-12 13:38:32','2012-12-09 20:14:42','2013-01-12 14:38:32','doe john',1,NULL,NULL,'CU1212-0006',NULL,NULL,NULL,'',NULL,NULL,0,1,'111','2222',NULL,'johndoe@email.com',NULL,NULL,101,NULL,0,'','','','','',0,0,NULL,NULL,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,0,0,NULL,NULL,NULL,NULL,NULL,1,NULL,'','',0),(19,0,NULL,'2013-03-16 12:52:02','2013-01-12 12:23:05','2013-03-16 13:52:02','aaa',1,NULL,NULL,'CU1301-0008',NULL,NULL,NULL,'fdgfd','gggfd','fgfgfd',0,4,'gggh','0101',NULL,NULL,NULL,NULL,101,NULL,0,'','','10/10/2010','','',0,0,NULL,NULL,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'en_US','patient@cabinetmed',NULL,1,NULL,'','',0),(20,0,NULL,'2013-01-12 11:52:20','2013-01-12 12:52:20','2013-01-12 12:52:20','pppoo',1,NULL,NULL,'CU1301-0009',NULL,NULL,NULL,'pppoo',NULL,NULL,0,4,NULL,NULL,NULL,NULL,NULL,NULL,101,NULL,0,'','','','','',0,0,NULL,NULL,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'en_US','patient@cabinetmed',NULL,1,NULL,'','',0),(21,0,NULL,'2013-01-23 15:56:58','2013-01-23 16:56:58','2013-01-23 16:56:58','pa',1,NULL,NULL,'CU1301-0010',NULL,NULL,NULL,'',NULL,NULL,0,81,NULL,NULL,NULL,NULL,NULL,NULL,101,NULL,0,'','','','','',0,0,NULL,NULL,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'en_US','patient@cabinetmed',NULL,1,NULL,'','',0),(22,0,NULL,'2013-02-26 21:57:58','2013-02-26 22:57:50','2013-02-26 22:57:58','pppp',1,NULL,NULL,'CU1302-0012',NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,NULL,101,NULL,0,'','','','','',0,0,NULL,NULL,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'fr_FR','patient@cabinetmed',NULL,1,NULL,'','',0),(23,0,NULL,'2013-02-26 21:58:13','2013-02-26 22:58:13','2013-02-26 22:58:13','ttttt',1,NULL,NULL,'CU1302-0013','SU1302-0006',NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL,NULL,101,NULL,0,'','','','','',0,0,NULL,NULL,NULL,1,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'fr_FR',NULL,NULL,1,NULL,'','',0),(24,0,NULL,'2013-03-09 15:33:39','2013-03-06 17:13:59','2013-03-09 16:33:39','smith smith',1,NULL,NULL,'CU1303-0015',NULL,'411E123',NULL,'',NULL,NULL,0,11,NULL,NULL,NULL,'smith@email.com',NULL,NULL,0,NULL,0,'','','','','',0,0,NULL,NULL,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'','',0),(25,0,NULL,'2013-03-10 14:47:37','2013-03-10 15:47:37','2013-03-10 15:47:37','jlmkjlkj',1,NULL,NULL,'CU1303-0016','SU1303-0007',NULL,NULL,'',NULL,NULL,0,117,NULL,NULL,NULL,NULL,NULL,NULL,101,NULL,0,'','','','','',0,0,NULL,NULL,NULL,1,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'en_US',NULL,NULL,1,NULL,'','',0),(26,0,NULL,'2013-03-10 14:57:32','2013-03-10 15:57:32','2013-03-10 15:57:32','iiii',1,NULL,NULL,'CU1303-0017','SU1303-0008',NULL,NULL,'',NULL,NULL,290,117,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'','','','','',0,0,NULL,NULL,NULL,1,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'en_US',NULL,NULL,1,NULL,'','',0);
/*!40000 ALTER TABLE `llx_societe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_address`
--

DROP TABLE IF EXISTS `llx_societe_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_address` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `label` varchar(30) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT '0',
  `name` varchar(60) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `town` varchar(50) DEFAULT NULL,
  `fk_pays` int(11) DEFAULT '0',
  `phone` varchar(20) DEFAULT NULL,
  `fax` varchar(20) DEFAULT NULL,
  `note` text,
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe_address`
--

LOCK TABLES `llx_societe_address` WRITE;
/*!40000 ALTER TABLE `llx_societe_address` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_address` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_commerciaux`
--

DROP TABLE IF EXISTS `llx_societe_commerciaux`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_commerciaux` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_societe_commerciaux` (`fk_soc`,`fk_user`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe_commerciaux`
--

LOCK TABLES `llx_societe_commerciaux` WRITE;
/*!40000 ALTER TABLE `llx_societe_commerciaux` DISABLE KEYS */;
INSERT INTO `llx_societe_commerciaux` VALUES (1,2,2),(2,3,2),(3,15,1),(4,16,1),(5,17,1),(6,19,1),(8,19,3),(7,20,1),(9,21,1),(10,23,1),(11,25,1),(12,26,1);
/*!40000 ALTER TABLE `llx_societe_commerciaux` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_extrafields`
--

DROP TABLE IF EXISTS `llx_societe_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `jjjj` varchar(255) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_societe_extrafields` (`fk_object`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe_extrafields`
--

LOCK TABLES `llx_societe_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_societe_extrafields` DISABLE KEYS */;
INSERT INTO `llx_societe_extrafields` VALUES (1,'2011-06-22 16:23:01',40,'kkkk',NULL),(2,'2011-06-22 16:23:16',41,'jjj',NULL),(4,'2011-06-23 07:40:40',39,'lll',NULL),(12,'2011-06-29 13:03:12',42,NULL,NULL),(14,'2011-07-02 01:24:03',57,NULL,NULL),(16,'2011-07-02 14:11:29',60,NULL,NULL),(17,'2011-07-18 10:26:55',35,NULL,NULL),(18,'2011-07-31 22:35:08',2,NULL,NULL),(19,'2011-08-01 00:31:24',15,NULL,NULL),(22,'2011-08-01 00:42:21',16,NULL,NULL),(27,'2011-08-04 21:24:24',17,NULL,NULL),(28,'2012-12-08 16:38:30',10,NULL,NULL),(30,'2012-12-19 14:47:50',1,NULL,NULL),(31,'2013-01-12 13:38:32',18,NULL,NULL),(33,'2013-02-12 16:06:20',7,NULL,NULL),(34,'2013-02-20 19:07:21',12,'jjj',NULL),(36,'2013-02-26 21:57:58',22,'jjj',NULL),(37,'2013-02-26 21:58:13',23,NULL,NULL),(44,'2013-03-03 23:08:04',4,'jjj',NULL),(45,'2013-03-09 15:33:39',24,'jjj',NULL),(46,'2013-03-10 14:47:37',25,NULL,NULL),(47,'2013-03-10 14:57:32',26,NULL,NULL),(48,'2013-03-16 12:52:02',19,'jjj',NULL);
/*!40000 ALTER TABLE `llx_societe_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_log`
--

DROP TABLE IF EXISTS `llx_societe_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datel` datetime DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_statut` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `author` varchar(30) DEFAULT NULL,
  `label` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe_log`
--

LOCK TABLES `llx_societe_log` WRITE;
/*!40000 ALTER TABLE `llx_societe_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_prices`
--

DROP TABLE IF EXISTS `llx_societe_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_prices` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT '0',
  `tms` timestamp NULL DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `price_level` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe_prices`
--

LOCK TABLES `llx_societe_prices` WRITE;
/*!40000 ALTER TABLE `llx_societe_prices` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_remise`
--

DROP TABLE IF EXISTS `llx_societe_remise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_remise` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) NOT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `remise_client` double(6,3) NOT NULL DEFAULT '0.000',
  `note` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe_remise`
--

LOCK TABLES `llx_societe_remise` WRITE;
/*!40000 ALTER TABLE `llx_societe_remise` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_remise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_remise_except`
--

DROP TABLE IF EXISTS `llx_societe_remise_except`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_remise_except` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `amount_ht` double(24,8) NOT NULL,
  `amount_tva` double(24,8) NOT NULL DEFAULT '0.00000000',
  `amount_ttc` double(24,8) NOT NULL DEFAULT '0.00000000',
  `tva_tx` double(6,3) NOT NULL DEFAULT '0.000',
  `fk_user` int(11) NOT NULL,
  `fk_facture_line` int(11) DEFAULT NULL,
  `fk_facture` int(11) DEFAULT NULL,
  `fk_facture_source` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_societe_remise_except_fk_user` (`fk_user`),
  KEY `idx_societe_remise_except_fk_soc` (`fk_soc`),
  KEY `idx_societe_remise_except_fk_facture_line` (`fk_facture_line`),
  KEY `idx_societe_remise_except_fk_facture` (`fk_facture`),
  KEY `idx_societe_remise_except_fk_facture_source` (`fk_facture_source`),
  CONSTRAINT `fk_societe_remise_fk_facture` FOREIGN KEY (`fk_facture`) REFERENCES `llx_facture` (`rowid`),
  CONSTRAINT `fk_societe_remise_fk_facture_line` FOREIGN KEY (`fk_facture_line`) REFERENCES `llx_facturedet` (`rowid`),
  CONSTRAINT `fk_societe_remise_fk_facture_source` FOREIGN KEY (`fk_facture_source`) REFERENCES `llx_facture` (`rowid`),
  CONSTRAINT `fk_societe_remise_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_societe_remise_fk_user` FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe_remise_except`
--

LOCK TABLES `llx_societe_remise_except` WRITE;
/*!40000 ALTER TABLE `llx_societe_remise_except` DISABLE KEYS */;
INSERT INTO `llx_societe_remise_except` VALUES (1,23,'2013-03-08 10:02:54',5.00000000,0.00000000,5.00000000,0.000,1,775,NULL,NULL,'fsdfsf'),(2,19,'2013-03-19 09:36:15',10.00000000,1.25000000,11.25000000,12.500,1,1019,NULL,NULL,'hfghgf');
/*!40000 ALTER TABLE `llx_societe_remise_except` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_societe_rib`
--

DROP TABLE IF EXISTS `llx_societe_rib`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_societe_rib` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) NOT NULL,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `label` varchar(30) DEFAULT NULL,
  `bank` varchar(255) DEFAULT NULL,
  `code_banque` varchar(7) DEFAULT NULL,
  `code_guichet` varchar(6) DEFAULT NULL,
  `number` varchar(255) DEFAULT NULL,
  `cle_rib` varchar(5) DEFAULT NULL,
  `bic` varchar(20) DEFAULT NULL,
  `iban_prefix` varchar(34) DEFAULT NULL,
  `domiciliation` varchar(255) DEFAULT NULL,
  `proprio` varchar(60) DEFAULT NULL,
  `owner_address` text,
  `default_rib` tinyint(4) NOT NULL DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe_rib`
--

LOCK TABLES `llx_societe_rib` WRITE;
/*!40000 ALTER TABLE `llx_societe_rib` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_societe_rib` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_socpeople`
--

DROP TABLE IF EXISTS `llx_socpeople`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_socpeople` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_soc` int(11) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(128) DEFAULT NULL,
  `civilite` varchar(6) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `town` text,
  `fk_departement` int(11) DEFAULT NULL,
  `fk_pays` int(11) DEFAULT '0',
  `birthday` date DEFAULT NULL,
  `poste` varchar(80) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `phone_perso` varchar(30) DEFAULT NULL,
  `phone_mobile` varchar(30) DEFAULT NULL,
  `fax` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `jabberid` varchar(255) DEFAULT NULL,
  `skype` varchar(255) DEFAULT NULL,
  `priv` smallint(6) NOT NULL DEFAULT '0',
  `no_email` smallint(6) NOT NULL DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT '0',
  `fk_user_modif` int(11) DEFAULT NULL,
  `note_private` text,
  `note_public` text,
  `default_lang` varchar(6) DEFAULT NULL,
  `canvas` varchar(32) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  `statut` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  KEY `idx_socpeople_fk_soc` (`fk_soc`),
  KEY `idx_socpeople_fk_user_creat` (`fk_user_creat`),
  CONSTRAINT `fk_socpeople_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_socpeople_user_creat_user_rowid` FOREIGN KEY (`fk_user_creat`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_socpeople`
--

LOCK TABLES `llx_socpeople` WRITE;
/*!40000 ALTER TABLE `llx_socpeople` DISABLE KEYS */;
INSERT INTO `llx_socpeople` VALUES (1,'2010-07-08 14:26:14','2010-07-08 20:45:28',1,1,NULL,'MR','Samira','Aljoun','','','',297,117,'2010-07-08','Project leader','','','','','','',NULL,0,0,1,1,'Met during a congress at Dubai',NULL,NULL,NULL,NULL,1),(2,'2010-07-08 22:44:50','2010-07-08 20:59:57',NULL,1,NULL,'MR','Freeman','Public','','','',200,11,NULL,'','','','','','','',NULL,0,0,1,1,'A friend that is a free contact not linked to any company',NULL,NULL,NULL,NULL,1),(3,'2010-07-08 22:59:02','2010-07-08 20:59:35',NULL,1,NULL,'MR','Freeman','Private','','','',NULL,11,NULL,'','','','','','','',NULL,1,0,1,1,'This is a private contact',NULL,NULL,NULL,NULL,1),(4,'2010-07-09 00:16:58','2010-07-08 22:16:58',6,1,NULL,'MR','Rotchield','Evan','','','',NULL,6,NULL,'Bank director','','','','','','',NULL,0,0,1,1,'The bank director',NULL,NULL,NULL,NULL,1),(5,'2010-07-10 14:54:44','2010-07-10 12:54:44',9,1,NULL,'','Client salon invidivdu','','','','',NULL,NULL,NULL,'','','','','','','',NULL,0,0,1,1,'',NULL,NULL,NULL,NULL,1),(6,'2011-08-01 02:41:26','2011-08-01 00:41:26',17,1,NULL,'','aaa','','aaa','','',289,117,NULL,'','','','','','','',NULL,0,0,1,1,'',NULL,NULL,NULL,NULL,1),(7,'2013-02-12 17:05:57','2013-03-08 01:45:08',7,1,NULL,'','aaa','','','','',289,117,NULL,'','','','','','aaa@aaa.com','',NULL,0,0,1,1,'',NULL,NULL,NULL,NULL,1),(8,'2013-03-08 02:45:31','2013-03-08 01:45:31',7,1,NULL,'','kkkk','','','','',290,117,NULL,'','','','','','ttt@ttt.com','',NULL,0,0,1,1,'',NULL,NULL,NULL,NULL,1),(10,'2013-03-08 02:48:23','2013-03-08 01:48:23',7,1,NULL,'','fff','','','','',290,117,NULL,'','','','','','ttt@ttt.com','',NULL,0,0,1,1,'',NULL,NULL,NULL,NULL,1),(11,'2013-03-08 02:48:54','2013-03-08 01:48:54',7,1,NULL,'','iii','','','','',294,117,NULL,'','','','','','ttt@ttt.com','',NULL,0,0,1,1,'',NULL,NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `llx_socpeople` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_socpeople_extrafields`
--

DROP TABLE IF EXISTS `llx_socpeople_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_socpeople_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_socpeople_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_socpeople_extrafields`
--

LOCK TABLES `llx_socpeople_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_socpeople_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_socpeople_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_stock_mouvement`
--

DROP TABLE IF EXISTS `llx_stock_mouvement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_stock_mouvement` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datem` datetime DEFAULT NULL,
  `fk_product` int(11) NOT NULL,
  `fk_entrepot` int(11) NOT NULL,
  `value` double DEFAULT NULL,
  `price` float(13,4) DEFAULT '0.0000',
  `type_mouvement` smallint(6) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `label` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_stock_mouvement_fk_product` (`fk_product`),
  KEY `idx_stock_mouvement_fk_entrepot` (`fk_entrepot`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_stock_mouvement`
--

LOCK TABLES `llx_stock_mouvement` WRITE;
/*!40000 ALTER TABLE `llx_stock_mouvement` DISABLE KEYS */;
INSERT INTO `llx_stock_mouvement` VALUES (1,'2010-07-08 22:43:51','2010-07-09 00:43:51',2,2,1000,0.0000,0,1,'Correct stock'),(3,'2010-07-10 22:56:18','2010-07-11 00:56:18',4,2,500,0.0000,0,1,'Init'),(4,'2010-07-10 23:02:20','2010-07-11 01:02:20',4,2,500,0.0000,0,1,''),(5,'2010-07-11 16:49:44','2010-07-11 18:49:44',4,1,2,10.0000,3,1,''),(6,'2010-07-11 16:49:44','2010-07-11 18:49:44',1,1,4,0.0000,3,1,''),(7,'2013-01-19 17:22:48','2013-01-19 18:22:48',11,1,-1,0.0000,2,1,'Facture cr&eacute;&eacute;e dans DoliPOS'),(8,'2013-01-19 17:22:48','2013-01-19 18:22:48',4,1,-1,5.0000,2,1,'Facture cr&eacute;&eacute;e dans DoliPOS'),(9,'2013-01-19 17:22:48','2013-01-19 18:22:48',1,1,-2,0.0000,2,1,'Facture cr&eacute;&eacute;e dans DoliPOS'),(10,'2013-01-19 17:31:10','2013-01-19 18:31:10',2,1,-1,0.0000,2,1,'Facture cr&eacute;&eacute;e dans DoliPOS'),(11,'2013-01-19 17:31:58','2013-01-19 18:31:58',2,1,-1,0.0000,2,1,'Facture cr&eacute;&eacute;e dans DoliPOS');
/*!40000 ALTER TABLE `llx_stock_mouvement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_texts`
--

DROP TABLE IF EXISTS `llx_texts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_texts` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(32) DEFAULT NULL,
  `typemodele` varchar(32) DEFAULT NULL,
  `sortorder` smallint(6) DEFAULT NULL,
  `private` smallint(6) NOT NULL DEFAULT '0',
  `fk_user` int(11) DEFAULT NULL,
  `title` varchar(128) DEFAULT NULL,
  `filename` varchar(128) DEFAULT NULL,
  `content` text,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_texts`
--

LOCK TABLES `llx_texts` WRITE;
/*!40000 ALTER TABLE `llx_texts` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_texts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_tva`
--

DROP TABLE IF EXISTS `llx_tva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_tva` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datep` date DEFAULT NULL,
  `datev` date DEFAULT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `label` varchar(255) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `note` text,
  `fk_bank` int(11) DEFAULT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_tva`
--

LOCK TABLES `llx_tva` WRITE;
/*!40000 ALTER TABLE `llx_tva` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_tva` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_update_modules`
--

DROP TABLE IF EXISTS `llx_update_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_update_modules` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `modulekey` varchar(24) DEFAULT NULL,
  `datekey` date DEFAULT NULL,
  `versionkey` double DEFAULT NULL,
  `lastrequestdate` datetime DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_update_modules`
--

LOCK TABLES `llx_update_modules` WRITE;
/*!40000 ALTER TABLE `llx_update_modules` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_update_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user`
--

DROP TABLE IF EXISTS `llx_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_user` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `login` varchar(24) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `civilite` varchar(6) DEFAULT NULL,
  `ref_ext` varchar(50) DEFAULT NULL,
  `ref_int` varchar(50) DEFAULT NULL,
  `pass` varchar(32) DEFAULT NULL,
  `pass_crypted` varchar(128) DEFAULT NULL,
  `pass_temp` varchar(32) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `job` varchar(128) DEFAULT NULL,
  `skype` varchar(255) DEFAULT NULL,
  `office_phone` varchar(20) DEFAULT NULL,
  `office_fax` varchar(20) DEFAULT NULL,
  `user_mobile` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `signature` text,
  `admin` smallint(6) DEFAULT '0',
  `webcal_login` varchar(25) DEFAULT NULL,
  `phenix_login` varchar(25) DEFAULT NULL,
  `phenix_pass` varchar(128) DEFAULT NULL,
  `module_comm` smallint(6) DEFAULT '1',
  `module_compta` smallint(6) DEFAULT '1',
  `fk_societe` int(11) DEFAULT NULL,
  `fk_socpeople` int(11) DEFAULT NULL,
  `fk_member` int(11) DEFAULT NULL,
  `note` text,
  `datelastlogin` datetime DEFAULT NULL,
  `datepreviouslogin` datetime DEFAULT NULL,
  `egroupware_id` int(11) DEFAULT NULL,
  `ldap_sid` varchar(255) DEFAULT NULL,
  `statut` tinyint(4) DEFAULT '1',
  `photo` varchar(255) DEFAULT NULL,
  `lang` varchar(6) DEFAULT NULL,
  `openid` varchar(255) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `zip` varchar(25) DEFAULT NULL,
  `town` varchar(50) DEFAULT NULL,
  `fk_state` int(11) DEFAULT '0',
  `fk_country` int(11) DEFAULT '0',
  `color` varchar(6) DEFAULT NULL,
  `accountancy_code` varchar(24) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_user_login` (`login`,`entity`),
  UNIQUE KEY `uk_user_fk_socpeople` (`fk_socpeople`),
  UNIQUE KEY `uk_user_fk_member` (`fk_member`),
  KEY `uk_user_fk_societe` (`fk_societe`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_user`
--

LOCK TABLES `llx_user` WRITE;
/*!40000 ALTER TABLE `llx_user` DISABLE KEYS */;
INSERT INTO `llx_user` VALUES (1,'2010-07-08 13:20:11','2012-12-12 17:54:10','admin',0,NULL,NULL,NULL,'admin','21232f297a57a5a743894a0e4a801fc3',NULL,'SuperAdminName','Firstname','',NULL,'','','','bidon@mydomain.com','',1,'','','',1,1,NULL,NULL,NULL,'','2014-04-05 16:19:30','2013-11-07 01:01:51',NULL,'',1,'01.jpg',NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL),(2,'2010-07-08 13:54:48','2010-07-08 11:54:48','demo',1,NULL,NULL,NULL,'demo','fe01ce2a7fbac8fafaed7c982a04e229',NULL,'John','Doe',NULL,NULL,'09123123','','','johndoe@mycompany.com',NULL,0,'','','',1,1,NULL,NULL,NULL,'','2013-03-24 16:30:29','2010-07-08 14:12:02',NULL,'',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL),(3,'2010-07-11 16:18:59','2013-02-20 19:07:21','adupont',1,NULL,NULL,NULL,'adupont','00856ab2bbb748aa29aa335a6e3a2407',NULL,'Dupont','Alain','',NULL,'','','','toto@aa.com','',0,'','','',1,1,NULL,NULL,2,'','2012-12-21 17:38:55',NULL,NULL,'',1,NULL,NULL,NULL,2,NULL,NULL,NULL,0,0,NULL,NULL),(4,'2013-01-23 17:52:27','2013-02-20 19:48:01','aaa',1,NULL,NULL,NULL,'aaa','47bce5c74f589f4867dbd57e9ca9f808',NULL,'aaa','','',NULL,'','','','','',0,'','','',1,1,17,6,NULL,'','2013-02-25 10:18:41','2013-01-23 17:53:20',NULL,'',1,NULL,NULL,NULL,5,NULL,NULL,NULL,0,0,NULL,NULL),(5,'2013-01-23 17:52:37','2013-01-23 16:52:37','bbb',0,NULL,NULL,NULL,'bbb','08f8e0260c64418510cefb2b06eee5cd',NULL,'bbb','','',NULL,'','','','','',1,'','','',1,1,NULL,NULL,NULL,'',NULL,NULL,NULL,'',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL),(6,'2013-02-16 20:22:40','2013-02-16 19:22:40','aaab',2,NULL,NULL,NULL,'aaab','4c189b020ceb022e0ecc42482802e2b8',NULL,'aaab','','',NULL,'','','','','',0,'','','',1,1,NULL,NULL,NULL,'',NULL,NULL,NULL,'',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL),(7,'2013-02-16 20:48:15','2013-02-16 19:48:15','zzz',2,NULL,NULL,NULL,'zzz','f3abb86bd34cf4d52698f14c0da1dc60',NULL,'zzz','','',NULL,'','','','','',0,'','','',1,1,NULL,NULL,NULL,'',NULL,NULL,NULL,'',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL),(9,'2013-02-16 20:50:07','2013-03-24 16:10:14','zzzg',2,NULL,NULL,NULL,'jc28fg4h','93d789524fd223cf05eecea3f59cbe86',NULL,'zzzg','','',NULL,'','','','','fsdkkfsdf<br />\r\nfsdfsd<br />\r\n<strong>fsdfs</strong>',0,'','','',1,1,NULL,NULL,NULL,'',NULL,NULL,NULL,'',1,NULL,NULL,NULL,5,'','','',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_alert`
--

DROP TABLE IF EXISTS `llx_user_alert`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_user_alert` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) DEFAULT NULL,
  `fk_contact` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_user_alert`
--

LOCK TABLES `llx_user_alert` WRITE;
/*!40000 ALTER TABLE `llx_user_alert` DISABLE KEYS */;
INSERT INTO `llx_user_alert` VALUES (1,1,1,1);
/*!40000 ALTER TABLE `llx_user_alert` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_clicktodial`
--

DROP TABLE IF EXISTS `llx_user_clicktodial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_user_clicktodial` (
  `fk_user` int(11) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `login` varchar(32) DEFAULT NULL,
  `pass` varchar(64) DEFAULT NULL,
  `poste` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`fk_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_user_clicktodial`
--

LOCK TABLES `llx_user_clicktodial` WRITE;
/*!40000 ALTER TABLE `llx_user_clicktodial` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_user_clicktodial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_extrafields`
--

DROP TABLE IF EXISTS `llx_user_extrafields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_user_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_user_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_user_extrafields`
--

LOCK TABLES `llx_user_extrafields` WRITE;
/*!40000 ALTER TABLE `llx_user_extrafields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_user_extrafields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_param`
--

DROP TABLE IF EXISTS `llx_user_param`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_user_param` (
  `fk_user` int(11) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `param` varchar(64) NOT NULL,
  `value` varchar(255) NOT NULL,
  UNIQUE KEY `uk_user_param` (`fk_user`,`param`,`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_user_param`
--

LOCK TABLES `llx_user_param` WRITE;
/*!40000 ALTER TABLE `llx_user_param` DISABLE KEYS */;
INSERT INTO `llx_user_param` VALUES (1,1,'MAIN_BOXES_0','1'),(1,1,'MAIN_THEME','eldy'),(1,3,'THEME_ELDY_ENABLE_PERSONALIZED','1'),(1,1,'THEME_ELDY_RGB','ded0ed'),(1,3,'THEME_ELDY_RGB','d0ddc3');
/*!40000 ALTER TABLE `llx_user_param` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_user_rights`
--

DROP TABLE IF EXISTS `llx_user_rights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_user_rights` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `fk_id` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_user_rights` (`fk_user`,`fk_id`),
  CONSTRAINT `fk_user_rights_fk_user_user` FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=13202 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_user_rights`
--

LOCK TABLES `llx_user_rights` WRITE;
/*!40000 ALTER TABLE `llx_user_rights` DISABLE KEYS */;
INSERT INTO `llx_user_rights` VALUES (11425,1,11),(11401,1,12),(11406,1,13),(11411,1,14),(11416,1,15),(11421,1,16),(11427,1,19),(9726,1,21),(9700,1,22),(9706,1,24),(9711,1,25),(9716,1,26),(9722,1,27),(9728,1,28),(9978,1,31),(9968,1,32),(9974,1,34),(1910,1,36),(9980,1,38),(7184,1,61),(7181,1,62),(7183,1,64),(7185,1,67),(7186,1,68),(1678,1,71),(1673,1,72),(1675,1,74),(1679,1,75),(1677,1,76),(1681,1,78),(1682,1,79),(11389,1,81),(11372,1,82),(11376,1,84),(11379,1,86),(11382,1,87),(11386,1,88),(11390,1,89),(9796,1,91),(10097,1,95),(10099,1,96),(10103,1,97),(10104,1,98),(7139,1,101),(7134,1,102),(7136,1,104),(7137,1,105),(7138,1,106),(7140,1,109),(10229,1,111),(10201,1,112),(10207,1,113),(10213,1,114),(10219,1,115),(10225,1,116),(10231,1,117),(11323,1,121),(11313,1,122),(11319,1,125),(11325,1,126),(2307,1,151),(2304,1,152),(2306,1,153),(2308,1,154),(10092,1,161),(10093,1,162),(10094,1,163),(10095,1,164),(10096,1,165),(1585,1,170),(11541,1,171),(11534,1,172),(11538,1,173),(11542,1,178),(10000,1,221),(9990,1,222),(9996,1,223),(10002,1,229),(10007,1,237),(10011,1,238),(10015,1,239),(1686,1,241),(1685,1,242),(1687,1,243),(11527,1,251),(11489,1,252),(11492,1,253),(11495,1,254),(11498,1,255),(11502,1,256),(1617,1,258),(11330,1,262),(11349,1,281),(11339,1,282),(11345,1,283),(11351,1,286),(1763,1,331),(1762,1,332),(1764,1,333),(11505,1,341),(11507,1,342),(11509,1,343),(11511,1,344),(11523,1,351),(11516,1,352),(11520,1,353),(11524,1,354),(11528,1,358),(11365,1,531),(11358,1,532),(11362,1,534),(1625,1,536),(11366,1,538),(11557,1,700),(11547,1,701),(11553,1,702),(11559,1,703),(1755,1,1001),(1754,1,1002),(1756,1,1003),(1758,1,1004),(1759,1,1005),(7146,1,1101),(7143,1,1102),(7145,1,1104),(7147,1,1109),(11435,1,1181),(11483,1,1182),(11440,1,1183),(11444,1,1184),(11448,1,1185),(11452,1,1186),(11456,1,1187),(11460,1,1188),(1578,1,1201),(1579,1,1202),(11479,1,1231),(11466,1,1232),(11470,1,1233),(11474,1,1234),(11477,1,1235),(11480,1,1236),(11484,1,1237),(1736,1,1251),(11432,1,1321),(11393,1,1421),(8190,1,1791),(8187,1,1792),(8191,1,1793),(11283,1,2401),(11279,1,2402),(11285,1,2403),(11299,1,2411),(11295,1,2412),(11301,1,2413),(11305,1,2414),(1618,1,2500),(11569,1,2501),(11566,1,2503),(11570,1,2515),(9610,1,5001),(9611,1,5002),(8155,1,20001),(8159,1,20002),(8163,1,20003),(8167,1,20004),(8171,1,20005),(8175,1,20006),(10345,1,23001),(10338,1,23002),(10342,1,23003),(10346,1,23004),(7701,1,50101),(4984,1,50401),(4983,1,50402),(4985,1,50403),(4987,1,50411),(4988,1,50412),(4989,1,50415),(3564,1,100700),(3565,1,100701),(9596,1,101051),(9598,1,101052),(9600,1,101053),(9604,1,101060),(9605,1,101061),(7177,1,101201),(7178,1,101202),(10353,1,101250),(10355,1,101251),(8980,1,101261),(8981,1,101262),(7616,1,101331),(10030,1,101701),(10031,1,101702),(3582,1,102000),(3583,1,102001),(9819,1,400051),(9823,1,400052),(9827,1,400053),(9831,1,400055),(132,2,11),(133,2,12),(134,2,13),(135,2,14),(136,2,16),(137,2,19),(138,2,21),(139,2,22),(140,2,24),(141,2,25),(142,2,26),(143,2,27),(10359,2,31),(145,2,32),(10361,2,34),(146,2,36),(147,2,41),(148,2,42),(149,2,44),(150,2,61),(151,2,62),(152,2,64),(153,2,71),(154,2,72),(155,2,74),(156,2,75),(157,2,78),(158,2,79),(159,2,81),(160,2,82),(161,2,84),(162,2,86),(163,2,87),(164,2,88),(165,2,89),(166,2,91),(167,2,92),(168,2,93),(2475,2,95),(2476,2,96),(2477,2,97),(2478,2,98),(169,2,101),(170,2,102),(171,2,104),(172,2,109),(173,2,111),(174,2,112),(175,2,113),(176,2,114),(177,2,116),(178,2,117),(179,2,121),(180,2,122),(181,2,125),(182,2,141),(183,2,142),(184,2,144),(2479,2,151),(2480,2,152),(2481,2,153),(2482,2,154),(185,2,161),(186,2,162),(187,2,163),(188,2,164),(189,2,165),(190,2,170),(2471,2,171),(192,2,172),(2472,2,173),(193,2,221),(194,2,222),(195,2,229),(196,2,241),(197,2,242),(198,2,243),(199,2,251),(201,2,262),(202,2,281),(203,2,282),(204,2,283),(205,2,331),(2483,2,531),(207,2,532),(2484,2,534),(208,2,536),(2473,2,700),(210,2,701),(211,2,702),(2474,2,703),(212,2,1001),(213,2,1002),(214,2,1003),(215,2,1004),(216,2,1005),(217,2,1101),(218,2,1102),(219,2,1104),(220,2,1109),(221,2,1181),(222,2,1182),(223,2,1183),(224,2,1184),(225,2,1185),(226,2,1186),(227,2,1187),(228,2,1188),(229,2,1201),(230,2,1202),(231,2,1231),(232,2,1232),(233,2,1233),(234,2,1234),(235,2,1421),(236,2,2401),(237,2,2402),(238,2,2403),(239,2,2411),(240,2,2412),(241,2,2413),(242,2,2500),(2470,2,2501),(243,2,2515),(10363,2,20001),(10364,2,20002),(10365,2,20003),(10366,2,20004),(10367,2,20005),(10368,2,20006),(10362,2,50101),(10372,2,101250),(1807,3,11),(1808,3,31),(1809,3,36),(1810,3,41),(1811,3,61),(1812,3,71),(1813,3,72),(1814,3,74),(1815,3,75),(1816,3,78),(1817,3,79),(1818,3,91),(1819,3,95),(1820,3,97),(1821,3,111),(1822,3,121),(1823,3,122),(1824,3,125),(1825,3,161),(1826,3,170),(1827,3,171),(1828,3,172),(1829,3,221),(1830,3,222),(1831,3,229),(1832,3,241),(1833,3,242),(1834,3,243),(1835,3,251),(1836,3,255),(1837,3,256),(1838,3,262),(1839,3,281),(1840,3,282),(1841,3,283),(1842,3,331),(1843,3,531),(1844,3,536),(1845,3,700),(1846,3,1001),(1847,3,1002),(1848,3,1003),(1849,3,1004),(1850,3,1005),(1851,3,1181),(1852,3,1182),(1853,3,1201),(1854,3,1202),(1855,3,1231),(1856,3,2401),(1857,3,2402),(1858,3,2403),(1859,3,2411),(1860,3,2412),(1861,3,2413),(1862,3,2500),(1863,3,2515),(8026,4,11),(8027,4,21),(8028,4,31),(8029,4,41),(8030,4,61),(8031,4,71),(8032,4,72),(8033,4,74),(8034,4,75),(8035,4,78),(8036,4,79),(8037,4,81),(8038,4,91),(8039,4,95),(8040,4,97),(8041,4,101),(8042,4,111),(8043,4,121),(8044,4,151),(8045,4,161),(8046,4,171),(8047,4,221),(8048,4,222),(8049,4,229),(8050,4,241),(8051,4,242),(8052,4,243),(8146,4,251),(8147,4,253),(8053,4,262),(8054,4,281),(8055,4,331),(8056,4,341),(8057,4,342),(8058,4,343),(8059,4,344),(8060,4,531),(8061,4,700),(8062,4,1001),(8063,4,1002),(8064,4,1003),(8065,4,1004),(8066,4,1005),(8067,4,1101),(8068,4,1181),(8069,4,1182),(8070,4,1201),(8071,4,1202),(8072,4,1231),(8073,4,2401),(8074,4,2501),(8075,4,2503),(8076,4,2515),(8077,4,20001),(8078,4,50101),(8079,4,101201),(8080,4,101261),(8081,4,102000),(8082,4,400051),(8083,4,400052),(8084,4,400053),(8085,4,400055),(11428,5,11),(11404,5,12),(11408,5,13),(11414,5,14),(11418,5,15),(11424,5,16),(11430,5,19),(9729,5,21),(9703,5,22),(9709,5,24),(9713,5,25),(9719,5,26),(9725,5,27),(9731,5,28),(9981,5,31),(9971,5,32),(9977,5,34),(9983,5,38),(8089,5,41),(8090,5,61),(8091,5,71),(8092,5,72),(8093,5,74),(8094,5,75),(8095,5,78),(8096,5,79),(11391,5,81),(11374,5,82),(11378,5,84),(11380,5,86),(11384,5,87),(11388,5,88),(11392,5,89),(9799,5,91),(9789,5,92),(9795,5,93),(9801,5,94),(10098,5,95),(10100,5,96),(10105,5,97),(10106,5,98),(8101,5,101),(10232,5,111),(10204,5,112),(10210,5,113),(10216,5,114),(10222,5,115),(10228,5,116),(10234,5,117),(11326,5,121),(11316,5,122),(11322,5,125),(11328,5,126),(8104,5,151),(8105,5,161),(11543,5,171),(11536,5,172),(11540,5,173),(11544,5,178),(10003,5,221),(9993,5,222),(9999,5,223),(10005,5,229),(10009,5,237),(10013,5,238),(10017,5,239),(8110,5,241),(8111,5,242),(8112,5,243),(11529,5,251),(11490,5,252),(11494,5,253),(11496,5,254),(11500,5,255),(11504,5,256),(11332,5,262),(11352,5,281),(11342,5,282),(11348,5,283),(11354,5,286),(8115,5,331),(11506,5,341),(11508,5,342),(11510,5,343),(11512,5,344),(11525,5,351),(11518,5,352),(11522,5,353),(11526,5,354),(11530,5,358),(11367,5,531),(11360,5,532),(11364,5,534),(11368,5,538),(11560,5,700),(11550,5,701),(11556,5,702),(11562,5,703),(8122,5,1001),(8123,5,1002),(8124,5,1003),(8125,5,1004),(8126,5,1005),(8127,5,1101),(11436,5,1181),(11485,5,1182),(11442,5,1183),(11446,5,1184),(11450,5,1185),(11454,5,1186),(11458,5,1187),(11462,5,1188),(8130,5,1201),(8131,5,1202),(11481,5,1231),(11468,5,1232),(11472,5,1233),(11476,5,1234),(11478,5,1235),(11482,5,1236),(11486,5,1237),(11434,5,1321),(11394,5,1421),(8192,5,1791),(8189,5,1792),(8193,5,1793),(11286,5,2401),(11282,5,2402),(11288,5,2403),(11302,5,2411),(11298,5,2412),(11304,5,2413),(11306,5,2414),(11571,5,2501),(11568,5,2503),(11572,5,2515),(9612,5,5001),(9613,5,5002),(8157,5,20001),(8161,5,20002),(8165,5,20003),(8169,5,20004),(8173,5,20005),(8177,5,20006),(10347,5,23001),(10340,5,23002),(10344,5,23003),(10348,5,23004),(8138,5,50101),(9597,5,101051),(9599,5,101052),(9601,5,101053),(9606,5,101060),(9607,5,101061),(8139,5,101201),(10356,5,101250),(10358,5,101251),(8982,5,101261),(8983,5,101262),(10032,5,101701),(10033,5,101702),(8141,5,102000),(9821,5,400051),(9825,5,400052),(9829,5,400053),(9833,5,400055),(8194,6,11),(8195,6,21),(8196,6,31),(8197,6,41),(8198,6,61),(8199,6,71),(8200,6,72),(8201,6,74),(8202,6,75),(8203,6,78),(8204,6,79),(8205,6,81),(8206,6,91),(8207,6,95),(8208,6,97),(8209,6,101),(8210,6,111),(8211,6,121),(8212,6,151),(8213,6,161),(8214,6,171),(8215,6,221),(8216,6,222),(8217,6,229),(8218,6,241),(8219,6,242),(8220,6,243),(8221,6,262),(8222,6,281),(8223,6,331),(8224,6,341),(8225,6,342),(8226,6,343),(8227,6,344),(8228,6,531),(8229,6,700),(8230,6,1001),(8231,6,1002),(8232,6,1003),(8233,6,1004),(8234,6,1005),(8235,6,1101),(8236,6,1181),(8237,6,1182),(8238,6,1201),(8239,6,1202),(8240,6,1231),(8241,6,1791),(8242,6,2401),(8243,6,2501),(8244,6,2503),(8245,6,2515),(8246,6,5001),(8247,6,20001),(8248,6,50101),(8249,6,101201),(8250,6,101261),(8251,6,102000),(8252,6,400051),(8253,6,400052),(8254,6,400053),(8255,6,400055),(8256,7,11),(8257,7,21),(8258,7,31),(8259,7,41),(8260,7,61),(8261,7,71),(8262,7,72),(8263,7,74),(8264,7,75),(8265,7,78),(8266,7,79),(8267,7,81),(8268,7,91),(8269,7,95),(8270,7,97),(8271,7,101),(8272,7,111),(8273,7,121),(8274,7,151),(8275,7,161),(8276,7,171),(8277,7,221),(8278,7,222),(8279,7,229),(8280,7,241),(8281,7,242),(8282,7,243),(8283,7,262),(8284,7,281),(8285,7,331),(8286,7,341),(8287,7,342),(8288,7,343),(8289,7,344),(8290,7,531),(8291,7,700),(8292,7,1001),(8293,7,1002),(8294,7,1003),(8295,7,1004),(8296,7,1005),(8297,7,1101),(8298,7,1181),(8299,7,1182),(8300,7,1201),(8301,7,1202),(8302,7,1231),(8303,7,1791),(8304,7,2401),(8305,7,2501),(8306,7,2503),(8307,7,2515),(8308,7,5001),(8309,7,20001),(8310,7,50101),(8311,7,101201),(8312,7,101261),(8313,7,102000),(8314,7,400051),(8315,7,400052),(8316,7,400053),(8317,7,400055),(8318,9,11),(8319,9,21),(8320,9,31),(8321,9,41),(8322,9,61),(8323,9,71),(8324,9,72),(8325,9,74),(8326,9,75),(8327,9,78),(8328,9,79),(8329,9,81),(8330,9,91),(8331,9,95),(8332,9,97),(8333,9,101),(8334,9,111),(8335,9,121),(8336,9,151),(8337,9,161),(8338,9,171),(8339,9,221),(8340,9,222),(8341,9,229),(8342,9,241),(8343,9,242),(8344,9,243),(8345,9,262),(8346,9,281),(8347,9,331),(8348,9,341),(8349,9,342),(8350,9,343),(8351,9,344),(8352,9,531),(8353,9,700),(8354,9,1001),(8355,9,1002),(8356,9,1003),(8357,9,1004),(8358,9,1005),(8359,9,1101),(8360,9,1181),(8361,9,1182),(8362,9,1201),(8363,9,1202),(8364,9,1231),(8365,9,1791),(8366,9,2401),(8367,9,2501),(8368,9,2503),(8369,9,2515),(8370,9,5001),(8371,9,20001),(8372,9,50101),(8373,9,101201),(8374,9,101261),(8375,9,102000),(8376,9,400051),(8377,9,400052),(8378,9,400053),(8379,9,400055);
/*!40000 ALTER TABLE `llx_user_rights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_usergroup`
--

DROP TABLE IF EXISTS `llx_usergroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_usergroup` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `note` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_usergroup_name` (`nom`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_usergroup`
--

LOCK TABLES `llx_usergroup` WRITE;
/*!40000 ALTER TABLE `llx_usergroup` DISABLE KEYS */;
INSERT INTO `llx_usergroup` VALUES (1,'ggg',1,'2013-01-16 20:48:08','2013-01-16 19:48:08','ggg');
/*!40000 ALTER TABLE `llx_usergroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_usergroup_rights`
--

DROP TABLE IF EXISTS `llx_usergroup_rights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_usergroup_rights` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_usergroup` int(11) NOT NULL,
  `fk_id` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `fk_usergroup` (`fk_usergroup`,`fk_id`),
  CONSTRAINT `fk_usergroup_rights_fk_usergroup` FOREIGN KEY (`fk_usergroup`) REFERENCES `llx_usergroup` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_usergroup_rights`
--

LOCK TABLES `llx_usergroup_rights` WRITE;
/*!40000 ALTER TABLE `llx_usergroup_rights` DISABLE KEYS */;
INSERT INTO `llx_usergroup_rights` VALUES (1,1,2401),(2,1,2402),(3,1,2403),(4,1,2411),(5,1,2412),(6,1,2413);
/*!40000 ALTER TABLE `llx_usergroup_rights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_usergroup_user`
--

DROP TABLE IF EXISTS `llx_usergroup_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_usergroup_user` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_user` int(11) NOT NULL,
  `fk_usergroup` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_usergroup_user` (`entity`,`fk_user`,`fk_usergroup`),
  KEY `fk_usergroup_user_fk_user` (`fk_user`),
  KEY `fk_usergroup_user_fk_usergroup` (`fk_usergroup`),
  CONSTRAINT `fk_usergroup_user_fk_user` FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_usergroup_user_fk_usergroup` FOREIGN KEY (`fk_usergroup`) REFERENCES `llx_usergroup` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_usergroup_user`
--

LOCK TABLES `llx_usergroup_user` WRITE;
/*!40000 ALTER TABLE `llx_usergroup_user` DISABLE KEYS */;
INSERT INTO `llx_usergroup_user` VALUES (1,1,1,1);
/*!40000 ALTER TABLE `llx_usergroup_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_ventilation_achat`
--

DROP TABLE IF EXISTS `llx_ventilation_achat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_ventilation_achat` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_code_ventilation` int(11) DEFAULT NULL,
  `fk_facture` int(11) DEFAULT NULL,
  `fk_facture_fourn_det` int(11) DEFAULT NULL,
  `ventilation` varchar(255) DEFAULT NULL,
  `qty` double DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_ventilation_achat`
--

LOCK TABLES `llx_ventilation_achat` WRITE;
/*!40000 ALTER TABLE `llx_ventilation_achat` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_ventilation_achat` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-04-05 16:31:13
