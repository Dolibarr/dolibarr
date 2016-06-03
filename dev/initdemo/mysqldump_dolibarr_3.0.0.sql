-- MySQL dump 10.13  Distrib 5.1.49, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: dolibarrold
-- ------------------------------------------------------
-- Server version	5.1.49-1ubuntu8.1

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
  `label` varchar(128) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `account_parent` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_accountingaccount_fk_pcg_version` (`fk_pcg_version`),
  CONSTRAINT `fk_accountingaccount_fk_pcg_version` FOREIGN KEY (`fk_pcg_version`) REFERENCES `llx_accountingsystem` (`pcg_version`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_accountingaccount`
--

LOCK TABLES `llx_accountingaccount` WRITE;
/*!40000 ALTER TABLE `llx_accountingaccount` DISABLE KEYS */;
INSERT INTO `llx_accountingaccount` VALUES (1,'PCG99-ABREGE','CAPIT','CAPITAL','Capital','101','1'),(2,'PCG99-ABREGE','CAPIT','XXXXXX','Ecarts de réévaluation','105','1'),(3,'PCG99-ABREGE','CAPIT','XXXXXX','Réserve légale','1061','1'),(4,'PCG99-ABREGE','CAPIT','XXXXXX','Réserves statutaires ou contractuelles','1063','1'),(5,'PCG99-ABREGE','CAPIT','XXXXXX','Réserves réglementées','1064','1'),(6,'PCG99-ABREGE','CAPIT','XXXXXX','Autres réserves','1068','1'),(7,'PCG99-ABREGE','CAPIT','XXXXXX','Compte de l\'exploitant','108','1'),(8,'PCG99-ABREGE','CAPIT','XXXXXX','Résultat de l\'exercice','12','1'),(9,'PCG99-ABREGE','CAPIT','XXXXXX','Amortissements dérogatoires','145','1'),(10,'PCG99-ABREGE','CAPIT','XXXXXX','Provision spéciale de réévaluation','146','1'),(11,'PCG99-ABREGE','CAPIT','XXXXXX','Plus-values réinvesties','147','1'),(12,'PCG99-ABREGE','CAPIT','XXXXXX','Autres provisions réglementées','148','1'),(13,'PCG99-ABREGE','CAPIT','XXXXXX','Provisions pour risques et charges','15','1'),(14,'PCG99-ABREGE','CAPIT','XXXXXX','Emprunts et dettes assimilees','16','1'),(15,'PCG99-ABREGE','IMMO','XXXXXX','Immobilisations incorporelles','20','2'),(16,'PCG99-ABREGE','IMMO','XXXXXX','Frais d\'établissement','201','20'),(17,'PCG99-ABREGE','IMMO','XXXXXX','Droit au bail','206','20'),(18,'PCG99-ABREGE','IMMO','XXXXXX','Fonds commercial','207','20'),(19,'PCG99-ABREGE','IMMO','XXXXXX','Autres immobilisations incorporelles','208','20'),(20,'PCG99-ABREGE','IMMO','XXXXXX','Immobilisations corporelles','21','2'),(21,'PCG99-ABREGE','IMMO','XXXXXX','Immobilisations en cours','23','2'),(22,'PCG99-ABREGE','IMMO','XXXXXX','Autres immobilisations financieres','27','2'),(23,'PCG99-ABREGE','IMMO','XXXXXX','Amortissements des immobilisations incorporelles','280','2'),(24,'PCG99-ABREGE','IMMO','XXXXXX','Amortissements des immobilisations corporelles','281','2'),(25,'PCG99-ABREGE','IMMO','XXXXXX','Provisions pour dépréciation des immobilisations incorporelles','290','2'),(26,'PCG99-ABREGE','IMMO','XXXXXX','Provisions pour dépréciation des immobilisations corporelles','291','2'),(27,'PCG99-ABREGE','IMMO','XXXXXX','Provisions pour dépréciation des autres immobilisations financières','297','2'),(28,'PCG99-ABREGE','STOCK','XXXXXX','Matieres premières','31','3'),(29,'PCG99-ABREGE','STOCK','XXXXXX','Autres approvisionnements','32','3'),(30,'PCG99-ABREGE','STOCK','XXXXXX','En-cours de production de biens','33','3'),(31,'PCG99-ABREGE','STOCK','XXXXXX','En-cours de production de services','34','3'),(32,'PCG99-ABREGE','STOCK','XXXXXX','Stocks de produits','35','3'),(33,'PCG99-ABREGE','STOCK','XXXXXX','Stocks de marchandises','37','3'),(34,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des matières premières','391','3'),(35,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des autres approvisionnements','392','3'),(36,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des en-cours de production de biens','393','3'),(37,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des en-cours de production de services','394','3'),(38,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des stocks de produits','395','3'),(39,'PCG99-ABREGE','STOCK','XXXXXX','Provisions pour dépréciation des stocks de marchandises','397','3'),(40,'PCG99-ABREGE','TIERS','SUPPLIER','Fournisseurs et Comptes rattachés','400','4'),(41,'PCG99-ABREGE','TIERS','XXXXXX','Fournisseurs débiteurs','409','4'),(42,'PCG99-ABREGE','TIERS','CUSTOMER','Clients et Comptes rattachés','410','4'),(43,'PCG99-ABREGE','TIERS','XXXXXX','Clients créditeurs','419','4'),(44,'PCG99-ABREGE','TIERS','XXXXXX','Personnel','421','4'),(45,'PCG99-ABREGE','TIERS','XXXXXX','Personnel','428','4'),(46,'PCG99-ABREGE','TIERS','XXXXXX','Sécurité sociale et autres organismes sociaux','43','4'),(47,'PCG99-ABREGE','TIERS','XXXXXX','Etat - impôts sur bénéfice','444','4'),(48,'PCG99-ABREGE','TIERS','XXXXXX','Etat - Taxes sur chiffre affaire','445','4'),(49,'PCG99-ABREGE','TIERS','XXXXXX','Autres impôts, taxes et versements assimilés','447','4'),(50,'PCG99-ABREGE','TIERS','XXXXXX','Groupe et associes','45','4'),(51,'PCG99-ABREGE','TIERS','XXXXXX','Associés','455','45'),(52,'PCG99-ABREGE','TIERS','XXXXXX','Débiteurs divers et créditeurs divers','46','4'),(53,'PCG99-ABREGE','TIERS','XXXXXX','Comptes transitoires ou d\'attente','47','4'),(54,'PCG99-ABREGE','TIERS','XXXXXX','Charges à répartir sur plusieurs exercices','481','4'),(55,'PCG99-ABREGE','TIERS','XXXXXX','Charges constatées d\'avance','486','4'),(56,'PCG99-ABREGE','TIERS','XXXXXX','Produits constatés d\'avance','487','4'),(57,'PCG99-ABREGE','TIERS','XXXXXX','Provisions pour dépréciation des comptes de clients','491','4'),(58,'PCG99-ABREGE','TIERS','XXXXXX','Provisions pour dépréciation des comptes de débiteurs divers','496','4'),(59,'PCG99-ABREGE','FINAN','XXXXXX','Valeurs mobilières de placement','50','5'),(60,'PCG99-ABREGE','FINAN','BANK','Banques, établissements financiers et assimilés','51','5'),(61,'PCG99-ABREGE','FINAN','CASH','Caisse','53','5'),(62,'PCG99-ABREGE','FINAN','XXXXXX','Régies d\'avance et accréditifs','54','5'),(63,'PCG99-ABREGE','FINAN','XXXXXX','Virements internes','58','5'),(64,'PCG99-ABREGE','FINAN','XXXXXX','Provisions pour dépréciation des valeurs mobilières de placement','590','5'),(65,'PCG99-ABREGE','CHARGE','PRODUCT','Achats','60','6'),(66,'PCG99-ABREGE','CHARGE','XXXXXX','Variations des stocks','603','60'),(67,'PCG99-ABREGE','CHARGE','SERVICE','Services extérieurs','61','6'),(68,'PCG99-ABREGE','CHARGE','XXXXXX','Autres services extérieurs','62','6'),(69,'PCG99-ABREGE','CHARGE','XXXXXX','Impôts, taxes et versements assimiles','63','6'),(70,'PCG99-ABREGE','CHARGE','XXXXXX','Rémunérations du personnel','641','6'),(71,'PCG99-ABREGE','CHARGE','XXXXXX','Rémunération du travail de l\'exploitant','644','6'),(72,'PCG99-ABREGE','CHARGE','SOCIAL','Charges de sécurité sociale et de prévoyance','645','6'),(73,'PCG99-ABREGE','CHARGE','XXXXXX','Cotisations sociales personnelles de l\'exploitant','646','6'),(74,'PCG99-ABREGE','CHARGE','XXXXXX','Autres charges de gestion courante','65','6'),(75,'PCG99-ABREGE','CHARGE','XXXXXX','Charges financières','66','6'),(76,'PCG99-ABREGE','CHARGE','XXXXXX','Charges exceptionnelles','67','6'),(77,'PCG99-ABREGE','CHARGE','XXXXXX','Dotations aux amortissements et aux provisions','681','6'),(78,'PCG99-ABREGE','CHARGE','XXXXXX','Dotations aux amortissements et aux provisions','686','6'),(79,'PCG99-ABREGE','CHARGE','XXXXXX','Dotations aux amortissements et aux provisions','687','6'),(80,'PCG99-ABREGE','CHARGE','XXXXXX','Participation des salariés aux résultats','691','6'),(81,'PCG99-ABREGE','CHARGE','XXXXXX','Impôts sur les bénéfices','695','6'),(82,'PCG99-ABREGE','CHARGE','XXXXXX','Imposition forfaitaire annuelle des sociétés','697','6'),(83,'PCG99-ABREGE','CHARGE','XXXXXX','Produits','699','6'),(84,'PCG99-ABREGE','PROD','PRODUCT','Ventes de produits finis','701','7'),(85,'PCG99-ABREGE','PROD','SERVICE','Prestations de services','706','7'),(86,'PCG99-ABREGE','PROD','PRODUCT','Ventes de marchandises','707','7'),(87,'PCG99-ABREGE','PROD','PRODUCT','Produits des activités annexes','708','7'),(88,'PCG99-ABREGE','PROD','XXXXXX','Rabais, remises et ristournes accordés par l\'entreprise','709','7'),(89,'PCG99-ABREGE','PROD','XXXXXX','Variation des stocks','713','7'),(90,'PCG99-ABREGE','PROD','XXXXXX','Production immobilisée','72','7'),(91,'PCG99-ABREGE','PROD','XXXXXX','Produits nets partiels sur opérations à long terme','73','7'),(92,'PCG99-ABREGE','PROD','XXXXXX','Subventions d\'exploitation','74','7'),(93,'PCG99-ABREGE','PROD','XXXXXX','Autres produits de gestion courante','75','7'),(94,'PCG99-ABREGE','PROD','XXXXXX','Jetons de présence et rémunérations d\'administrateurs, gérants,...','753','75'),(95,'PCG99-ABREGE','PROD','XXXXXX','Ristournes perçues des coopératives','754','75'),(96,'PCG99-ABREGE','PROD','XXXXXX','Quotes-parts de résultat sur opérations faites en commun','755','75'),(97,'PCG99-ABREGE','PROD','XXXXXX','Produits financiers','76','7'),(98,'PCG99-ABREGE','PROD','XXXXXX','Produits exceptionnels','77','7'),(99,'PCG99-ABREGE','PROD','XXXXXX','Reprises sur amortissements et provisions','781','7'),(100,'PCG99-ABREGE','PROD','XXXXXX','Reprises sur provisions pour risques','786','7'),(101,'PCG99-ABREGE','PROD','XXXXXX','Reprises sur provisions','787','7'),(102,'PCG99-ABREGE','PROD','XXXXXX','Transferts de charges','79','7');
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
-- Table structure for table `llx_accountingsystem`
--

DROP TABLE IF EXISTS `llx_accountingsystem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_accountingsystem` (
  `pcg_version` varchar(12) NOT NULL,
  `fk_pays` int(11) NOT NULL,
  `label` varchar(128) NOT NULL,
  `datec` varchar(12) NOT NULL,
  `fk_author` varchar(20) DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` smallint(6) DEFAULT '0',
  PRIMARY KEY (`pcg_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_accountingsystem`
--

LOCK TABLES `llx_accountingsystem` WRITE;
/*!40000 ALTER TABLE `llx_accountingsystem` DISABLE KEYS */;
INSERT INTO `llx_accountingsystem` VALUES ('PCG99-ABREGE',1,'The simple accountancy french plan','2010-07-10',NULL,'2010-07-10 12:48:32',0);
/*!40000 ALTER TABLE `llx_accountingsystem` ENABLE KEYS */;
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
-- Table structure for table `llx_action_def`
--

DROP TABLE IF EXISTS `llx_action_def`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_action_def` (
  `rowid` int(11) NOT NULL,
  `code` varchar(32) NOT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `titre` varchar(255) NOT NULL,
  `description` text,
  `objet_type` varchar(16) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_action_def`
--

LOCK TABLES `llx_action_def` WRITE;
/*!40000 ALTER TABLE `llx_action_def` DISABLE KEYS */;
INSERT INTO `llx_action_def` VALUES (1,'NOTIFY_VAL_FICHINTER','2010-07-10 12:48:35','Validation fiche intervention','Executed when a intervention is validated','ficheinter'),(2,'NOTIFY_VAL_FAC','2010-07-10 12:48:35','Validation facture client','Executed when a customer invoice is approved','facture'),(3,'NOTIFY_APP_ORDER_SUPPLIER','2010-07-10 12:48:35','Approbation commande fournisseur','Executed when a supplier order is approved','order_supplier'),(4,'NOTIFY_REF_ORDER_SUPPLIER','2010-07-10 12:48:35','Refus commande fournisseur','Executed when a supplier order is refused','order_supplier'),(5,'NOTIFY_VAL_ORDER','2010-07-10 12:48:35','Validation commande client','Executed when a customer order is validated','order'),(6,'NOTIFY_VAL_PROPAL','2010-07-10 12:48:35','Validation proposition client','Executed when a commercial proposal is validated','propal'),(7,'NOTIFY_TRN_WITHDRAW','2011-02-06 11:18:39','Transmit withdraw','Executed when a withdrawal is transmited','withdraw'),(8,'NOTIFY_CRD_WITHDRAW','2011-02-06 11:18:39','Credite withdraw','Executed when a withdrawal is credited','withdraw'),(9,'NOTIFY_EMT_WITHDRAW','2011-02-06 11:18:39','Emit withdraw','Executed when a withdrawal is emited','withdraw');
/*!40000 ALTER TABLE `llx_action_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_actioncomm`
--

DROP TABLE IF EXISTS `llx_actioncomm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_actioncomm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datep` datetime DEFAULT NULL,
  `datep2` datetime DEFAULT NULL,
  `datea` datetime DEFAULT NULL,
  `datea2` datetime DEFAULT NULL,
  `fk_action` int(11) DEFAULT NULL,
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
  `fk_user_done` int(11) DEFAULT NULL,
  `priority` smallint(6) DEFAULT NULL,
  `fulldayevent` smallint(6) NOT NULL DEFAULT '0',
  `punctual` smallint(6) NOT NULL DEFAULT '1',
  `percent` smallint(6) NOT NULL DEFAULT '0',
  `location` varchar(128) DEFAULT NULL,
  `durationp` double DEFAULT NULL,
  `durationa` double DEFAULT NULL,
  `note` text,
  `propalrowid` int(11) DEFAULT NULL,
  `fk_commande` int(11) DEFAULT NULL,
  `fk_facture` int(11) DEFAULT NULL,
  `fk_supplier_order` int(11) DEFAULT NULL,
  `fk_supplier_invoice` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_actioncomm_datea` (`datea`),
  KEY `idx_actioncomm_fk_soc` (`fk_soc`),
  KEY `idx_actioncomm_fk_contact` (`fk_contact`),
  KEY `idx_actioncomm_fk_facture` (`fk_facture`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_actioncomm`
--

LOCK TABLES `llx_actioncomm` WRITE;
/*!40000 ALTER TABLE `llx_actioncomm` DISABLE KEYS */;
INSERT INTO `llx_actioncomm` VALUES (1,'2010-07-08 14:21:44','2010-07-08 14:21:44',NULL,NULL,50,'Company AAA and Co added into Dolibarr','2010-07-08 14:21:44','2010-07-08 12:21:44',1,NULL,NULL,1,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company AAA and Co added into Dolibarr\nAuthor: admin',NULL,NULL,NULL,NULL,NULL),(2,'2010-07-08 14:23:48','2010-07-08 14:23:48',NULL,NULL,50,'Company Belin SARL added into Dolibarr','2010-07-08 14:23:48','2010-07-08 12:23:48',1,NULL,NULL,2,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company Belin SARL added into Dolibarr\nAuthor: admin',NULL,NULL,NULL,NULL,NULL),(3,'2010-07-08 22:42:12','2010-07-08 22:42:12',NULL,NULL,50,'Company Spanish Comp added into Dolibarr','2010-07-08 22:42:12','2010-07-08 20:42:12',1,NULL,NULL,3,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company Spanish Comp added into Dolibarr\nAuthor: admin',NULL,NULL,NULL,NULL,NULL),(4,'2010-07-08 22:48:18','2010-07-08 22:48:18',NULL,NULL,50,'Company Prospector Vaalen added into Dolibarr','2010-07-08 22:48:18','2010-07-08 20:48:18',1,NULL,NULL,4,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company Prospector Vaalen added into Dolibarr\nAuthor: admin',NULL,NULL,NULL,NULL,NULL),(5,'2010-07-08 23:22:57','2010-07-08 23:22:57',NULL,NULL,50,'Company NoCountry Co added into Dolibarr','2010-07-08 23:22:57','2010-07-08 21:22:57',1,NULL,NULL,5,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company NoCountry Co added into Dolibarr\nAuthor: admin',NULL,NULL,NULL,NULL,NULL),(6,'2010-07-09 00:15:09','2010-07-09 00:15:09',NULL,NULL,50,'Company Swiss customer added into Dolibarr','2010-07-09 00:15:09','2010-07-08 22:15:09',1,NULL,NULL,6,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company Swiss customer added into Dolibarr\nAuthor: admin',NULL,NULL,NULL,NULL,NULL),(7,'2010-07-09 01:24:26','2010-07-09 01:24:26',NULL,NULL,50,'Company Generic customer added into Dolibarr','2010-07-09 01:24:26','2010-07-08 23:24:26',1,NULL,NULL,7,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Company Generic customer added into Dolibarr\nAuthor: admin',NULL,NULL,NULL,NULL,NULL),(8,'2010-07-10 14:54:27','2010-07-10 14:54:27',NULL,NULL,50,'Société Client salon ajoutée dans Dolibarr','2010-07-10 14:54:27','2010-07-10 12:54:27',1,NULL,NULL,8,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Société Client salon ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL,NULL,NULL,NULL),(9,'2010-07-10 14:54:44','2010-07-10 14:54:44',NULL,NULL,50,'Société Client salon invidivdu ajoutée dans Doliba','2010-07-10 14:54:44','2010-07-10 12:54:44',1,NULL,NULL,9,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Société Client salon invidivdu ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL,NULL,NULL,NULL),(10,'2010-07-10 14:56:10','2010-07-10 14:56:10',NULL,NULL,50,'Facture FA1007-0001 validée dans Dolibarr','2010-07-10 14:56:10','2010-07-10 12:56:10',1,NULL,NULL,9,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Facture FA1007-0001 validée dans Dolibarr\nAuteur: admin',NULL,NULL,1,NULL,NULL),(11,'2010-07-10 14:58:53','2010-07-10 14:58:53',NULL,NULL,50,'Facture FA1007-0001 validée dans Dolibarr','2010-07-10 14:58:53','2010-07-10 12:58:53',1,NULL,NULL,9,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Facture FA1007-0001 validée dans Dolibarr\nAuteur: admin',NULL,NULL,1,NULL,NULL),(12,'2010-07-10 15:00:55','2010-07-10 15:00:55',NULL,NULL,50,'Facture FA1007-0001 passée à payée dans Dolibarr','2010-07-10 15:00:55','2010-07-10 13:00:55',1,NULL,NULL,9,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Facture FA1007-0001 passée à payée dans Dolibarr\nAuteur: admin',NULL,NULL,1,NULL,NULL),(13,'2010-07-10 15:13:08','2010-07-10 15:13:08',NULL,NULL,50,'Société Smith Vick ajoutée dans Dolibarr','2010-07-10 15:13:08','2010-07-10 13:13:08',1,NULL,NULL,10,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Société Smith Vick ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL,NULL,NULL,NULL),(14,'2010-07-10 15:21:00','2010-07-10 16:21:00',NULL,NULL,5,'RDV avec mon chef','2010-07-10 15:21:48','2010-07-10 13:21:48',1,NULL,NULL,NULL,NULL,0,1,NULL,0,0,1,0,'',3600,NULL,'',NULL,NULL,NULL,NULL,NULL),(15,'2010-07-10 18:18:16','2010-07-10 18:18:16',NULL,NULL,50,'Contrat CONTRAT1 validé dans Dolibarr','2010-07-10 18:18:16','2010-07-10 16:18:16',1,NULL,NULL,2,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Contrat CONTRAT1 validé dans Dolibarr\nAuteur: admin',NULL,NULL,NULL,NULL,NULL),(16,'2010-07-10 18:35:57','2010-07-10 18:35:57',NULL,NULL,50,'Société Mon client ajoutée dans Dolibarr','2010-07-10 18:35:57','2010-07-10 16:35:57',1,NULL,NULL,11,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Société Mon client ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL,NULL,NULL,NULL),(17,'2010-07-11 16:18:08','2010-07-11 16:18:08',NULL,NULL,50,'Société Dupont Alain ajoutée dans Dolibarr','2010-07-11 16:18:08','2010-07-11 14:18:08',1,NULL,NULL,12,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Société Dupont Alain ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL,NULL,NULL,NULL),(18,'2010-07-11 17:11:00','2010-07-11 17:17:00',NULL,NULL,5,'Rendez-vous','2010-07-11 17:11:22','2010-07-11 15:11:22',1,NULL,NULL,NULL,NULL,0,1,NULL,0,0,1,0,'gfgdfgdf',360,NULL,'',NULL,NULL,NULL,NULL,NULL),(19,'2010-07-11 17:13:20','2010-07-11 17:13:20',NULL,NULL,50,'Société Vendeur de chips ajoutée dans Dolibarr','2010-07-11 17:13:20','2010-07-11 15:13:20',1,NULL,NULL,13,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Société Vendeur de chips ajoutée dans Dolibarr\nAuteur: admin',NULL,NULL,NULL,NULL,NULL),(20,'2010-07-11 17:15:42','2010-07-11 17:15:42',NULL,NULL,50,'Commande CF1007-0001 validée','2010-07-11 17:15:42','2010-07-11 15:15:42',1,NULL,NULL,13,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Commande CF1007-0001 validée\nAuteur: admin',NULL,NULL,NULL,NULL,NULL),(21,'2010-07-11 18:47:33','2010-07-11 18:47:33',NULL,NULL,50,'Commande CF1007-0002 validée','2010-07-11 18:47:33','2010-07-11 16:47:33',1,NULL,NULL,1,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Commande CF1007-0002 validée\nAuteur: admin',NULL,NULL,NULL,NULL,NULL),(22,'2010-07-18 11:36:18','2010-07-18 11:36:18',NULL,NULL,50,'Proposition PR1007-0003 validée','2010-07-18 11:36:18','2010-07-18 09:36:18',1,NULL,NULL,4,NULL,0,NULL,1,0,0,1,100,'',NULL,NULL,'Proposition PR1007-0003 validée\nAuteur: admin',3,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_actioncomm` ENABLE KEYS */;
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
  `civilite` varchar(6) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `login` varchar(50) NOT NULL,
  `pass` varchar(50) DEFAULT NULL,
  `fk_adherent_type` int(11) NOT NULL,
  `morphy` varchar(3) NOT NULL,
  `societe` varchar(50) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `adresse` text,
  `cp` varchar(30) DEFAULT NULL,
  `ville` varchar(50) DEFAULT NULL,
  `fk_departement` int(11) DEFAULT NULL,
  `pays` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `phone_perso` varchar(30) DEFAULT NULL,
  `phone_mobile` varchar(30) DEFAULT NULL,
  `naiss` date DEFAULT NULL,
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
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_adherent_login` (`login`,`entity`),
  UNIQUE KEY `uk_adherent_fk_soc` (`fk_soc`),
  KEY `idx_adherent_fk_adherent_type` (`fk_adherent_type`),
  CONSTRAINT `adherent_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_adherent_adherent_type` FOREIGN KEY (`fk_adherent_type`) REFERENCES `llx_adherent_type` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_adherent`
--

LOCK TABLES `llx_adherent` WRITE;
/*!40000 ALTER TABLE `llx_adherent` DISABLE KEYS */;
INSERT INTO `llx_adherent` VALUES (1,1,NULL,'Smith','Vick','vsmith','vsx1n8tf',2,'phy',NULL,10,NULL,NULL,NULL,NULL,102,'vsmith@email.com',NULL,NULL,NULL,'1960-07-07',NULL,1,0,NULL,NULL,'2010-07-10 15:12:56','2010-07-08 23:50:00','2010-07-10 13:13:08',1,1,1,NULL),(2,1,NULL,'Dupont','Alain','adupont','sng2bdf6',2,'phy',NULL,12,NULL,NULL,NULL,NULL,1,'toto@aa.com',NULL,NULL,NULL,'1972-07-08',NULL,1,1,'2013-07-17 00:00:00',NULL,'2010-07-10 15:03:32','2010-07-10 15:03:09','2010-07-18 10:20:33',1,1,1,NULL);
/*!40000 ALTER TABLE `llx_adherent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent_options`
--

DROP TABLE IF EXISTS `llx_adherent_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_adherent_options` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_member` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_adherent_options` (`fk_member`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_adherent_options`
--

LOCK TABLES `llx_adherent_options` WRITE;
/*!40000 ALTER TABLE `llx_adherent_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_adherent_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_adherent_options_label`
--

DROP TABLE IF EXISTS `llx_adherent_options_label`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_adherent_options_label` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `label` varchar(255) NOT NULL,
  `type` varchar(8) DEFAULT NULL,
  `size` int(11) DEFAULT '0',
  `pos` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_adherent_options_label_name` (`name`,`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_adherent_options_label`
--

LOCK TABLES `llx_adherent_options_label` WRITE;
/*!40000 ALTER TABLE `llx_adherent_options_label` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_adherent_options_label` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
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
-- Table structure for table `llx_bank`
--

DROP TABLE IF EXISTS `llx_bank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_bank` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bank`
--

LOCK TABLES `llx_bank` WRITE;
/*!40000 ALTER TABLE `llx_bank` DISABLE KEYS */;
INSERT INTO `llx_bank` VALUES (1,'2010-07-08 23:56:14','2010-07-08','2010-07-08',2000.00000000,'(Initial balance)',1,NULL,NULL,'SOLD',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(2,'2010-07-09 00:00:24','2010-07-09','2010-07-09',500.00000000,'(Initial balance)',2,NULL,NULL,'SOLD',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(3,'2010-07-10 13:33:42','2010-07-10','2010-07-10',0.00000000,'(Solde initial)',3,NULL,NULL,'SOLD',NULL,NULL,0,NULL,0,NULL,NULL,NULL),(4,'2010-07-10 14:59:41','2010-07-10','2010-07-10',0.02000000,'(CustomerInvoicePayment)',3,1,NULL,'LIQ',NULL,NULL,0,NULL,0,NULL,'Client salon invidivdu',NULL);
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
  `code_banque` varchar(7) DEFAULT NULL,
  `code_guichet` varchar(6) DEFAULT NULL,
  `number` varchar(255) DEFAULT NULL,
  `cle_rib` varchar(5) DEFAULT NULL,
  `bic` varchar(11) DEFAULT NULL,
  `iban_prefix` varchar(34) DEFAULT NULL,
  `country_iban` varchar(2) DEFAULT NULL,
  `cle_iban` varchar(2) DEFAULT NULL,
  `domiciliation` varchar(255) DEFAULT NULL,
  `fk_departement` int(11) DEFAULT NULL,
  `fk_pays` int(11) NOT NULL,
  `proprio` varchar(60) DEFAULT NULL,
  `adresse_proprio` varchar(255) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bank_account`
--

LOCK TABLES `llx_bank_account` WRITE;
/*!40000 ALTER TABLE `llx_bank_account` DISABLE KEYS */;
INSERT INTO `llx_bank_account` VALUES (1,'2010-07-08 23:56:14','2010-07-08 21:56:14','SWIBAC','Swiss bank account',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,181,6,NULL,NULL,1,0,1,NULL,'','EUR',1500,1500,'<br />'),(2,'2010-07-09 00:00:24','2010-07-08 22:00:24','SWIBAC2','Swiss bank account 2',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,179,6,NULL,NULL,1,1,1,NULL,'','EUR',200,400,'<br />'),(3,'2010-07-10 13:33:42','2010-07-10 11:33:42','ACCOUNTCASH','Account for cash',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,1,NULL,NULL,2,0,1,NULL,'','EUR',0,0,'<br />');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_bank_url`
--

LOCK TABLES `llx_bank_url` WRITE;
/*!40000 ALTER TABLE `llx_bank_url` DISABLE KEYS */;
INSERT INTO `llx_bank_url` VALUES (1,4,1,'/dolibarrnew/compta/paiement/fiche.php?id=','(paiement)','payment'),(2,4,9,'/dolibarrnew/compta/fiche.php?socid=','Client salon invidivdu','company');
/*!40000 ALTER TABLE `llx_bank_url` ENABLE KEYS */;
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
  `box_id` int(11) NOT NULL,
  `position` smallint(6) NOT NULL,
  `box_order` varchar(3) NOT NULL,
  `fk_user` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_boxes` (`box_id`,`position`,`fk_user`),
  KEY `idx_boxes_boxid` (`box_id`),
  KEY `idx_boxes_fk_user` (`fk_user`),
  CONSTRAINT `fk_boxes_box_id` FOREIGN KEY (`box_id`) REFERENCES `llx_boxes_def` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_boxes`
--

LOCK TABLES `llx_boxes` WRITE;
/*!40000 ALTER TABLE `llx_boxes` DISABLE KEYS */;
INSERT INTO `llx_boxes` VALUES (2,2,0,'B10',0),(3,3,0,'A13',0),(12,20,0,'B02',0),(61,3,0,'A01',1),(62,2,0,'B01',1),(63,20,0,'B02',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_boxes_def`
--

LOCK TABLES `llx_boxes_def` WRITE;
/*!40000 ALTER TABLE `llx_boxes_def` DISABLE KEYS */;
INSERT INTO `llx_boxes_def` VALUES (2,'box_clients.php',1,'2010-07-08 11:26:20',NULL),(3,'box_prospect.php',1,'2010-07-08 11:26:20',NULL),(20,'box_actions.php',1,'2010-07-08 11:29:29',NULL),(21,'box_bookmarks.php',1,'2010-07-08 11:30:03',NULL),(39,'box_contracts.php',1,'2010-07-10 16:12:46',NULL),(41,'box_comptes.php',1,'2010-07-11 17:08:03',NULL),(44,'box_produits.php',1,'2010-07-11 17:08:03',NULL),(45,'box_propales.php',1,'2010-07-18 09:34:11',NULL),(62,'box_services_vendus.php',1,'2011-02-06 11:20:36',NULL),(63,'box_commandes.php',1,'2011-02-06 11:20:36',NULL),(64,'box_factures_imp.php',1,'2011-02-06 11:20:36',NULL),(65,'box_factures.php',1,'2011-02-06 11:20:36',NULL),(66,'box_fournisseurs.php',1,'2011-02-06 11:20:36',NULL),(67,'box_factures_fourn_imp.php',1,'2011-02-06 11:20:36',NULL),(68,'box_factures_fourn.php',1,'2011-02-06 11:20:36',NULL);
/*!40000 ALTER TABLE `llx_boxes_def` ENABLE KEYS */;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_actioncomm`
--

LOCK TABLES `llx_c_actioncomm` WRITE;
/*!40000 ALTER TABLE `llx_c_actioncomm` DISABLE KEYS */;
INSERT INTO `llx_c_actioncomm` VALUES (1,'AC_TEL','system','Phone call',NULL,1,NULL),(2,'AC_FAX','system','Fax send',NULL,1,NULL),(3,'AC_PROP','system','Send commercial proposal by email','propal',1,NULL),(4,'AC_EMAIL','system','Send Email',NULL,1,NULL),(5,'AC_RDV','system','Rendez-vous',NULL,1,NULL),(8,'AC_COM','system','Send customer order by email','order',1,NULL),(9,'AC_FAC','system','Send customer invoice by email','invoice',1,NULL),(30,'AC_SUP_ORD','system','Send supplier invoice by email','order_supplier',1,NULL),(31,'AC_SUP_INV','system','Send supplier invoice by email','invoice_supplier',1,NULL),(50,'AC_OTH','system','Other',NULL,1,NULL);
/*!40000 ALTER TABLE `llx_c_actioncomm` ENABLE KEYS */;
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
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=231 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_chargesociales`
--

LOCK TABLES `llx_c_chargesociales` WRITE;
/*!40000 ALTER TABLE `llx_c_chargesociales` DISABLE KEYS */;
INSERT INTO `llx_c_chargesociales` VALUES (1,'Allocations familiales',1,1,'TAXFAM',1),(2,'CSG Deductible',1,1,'TAXCSGD',1),(3,'CSG/CRDS NON Deductible',0,1,'TAXCSGND',1),(10,'Taxe apprentissage',0,1,'TAXAPP',1),(11,'Taxe professionnelle',0,1,'TAXPRO',1),(20,'Impots locaux/fonciers',0,1,'TAXFON',1),(25,'Impots revenus',0,1,'TAXREV',1),(30,'Assurance Sante',0,1,'TAXSECU',1),(40,'Mutuelle',0,1,'TAXMUT',1),(50,'Assurance vieillesse',0,1,'TAXRET',1),(60,'Assurance Chomage',0,1,'TAXCHOM',1),(201,'ONSS',1,1,'TAXBEONSS',2),(210,'Precompte professionnel',1,1,'TAXBEPREPRO',2),(220,'Prime d\'existence',1,1,'TAXBEPRIEXI',2),(230,'Precompte immobilier',1,1,'TAXBEPREIMMO',2);
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
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_civilite`
--

LOCK TABLES `llx_c_civilite` WRITE;
/*!40000 ALTER TABLE `llx_c_civilite` DISABLE KEYS */;
INSERT INTO `llx_c_civilite` VALUES (1,'MME','Madame',1),(3,'MR','Monsieur',1),(5,'MLE','Mademoiselle',1),(7,'MTRE','Maître',1);
/*!40000 ALTER TABLE `llx_c_civilite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_currencies`
--

DROP TABLE IF EXISTS `llx_c_currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_currencies` (
  `code` varchar(2) NOT NULL,
  `code_iso` varchar(3) NOT NULL,
  `label` varchar(64) DEFAULT NULL,
  `labelsing` varchar(64) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`code`),
  UNIQUE KEY `uk_c_currencies_code_iso` (`code_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_currencies`
--

LOCK TABLES `llx_c_currencies` WRITE;
/*!40000 ALTER TABLE `llx_c_currencies` DISABLE KEYS */;
INSERT INTO `llx_c_currencies` VALUES ('AD','AUD','Dollars australiens',NULL,1),('AE','AED','Arabes emirats dirham',NULL,1),('BT','THB','Bath thailandais',NULL,1),('CD','DKK','Couronnes dannoises',NULL,1),('CF','XAF','Francs cfa beac',NULL,1),('CN','NOK','Couronnes norvegiennes',NULL,1),('CS','SEK','Couronnes suedoises',NULL,1),('CZ','CZK','Couronnes tcheques',NULL,1),('DA','DZD','Dinar algérien',NULL,1),('DC','CAD','Dollars canadiens',NULL,1),('DH','MAD','Dirham',NULL,1),('DR','GRD','Drachme (grece)',NULL,1),('DS','SGD','Dollars singapour',NULL,1),('DU','USD','Dollars us',NULL,1),('EC','XEU','Ecus',NULL,1),('EG','EGP','Livre egyptienne',NULL,1),('ES','PTE','Escudos',NULL,0),('EU','EUR','Euros',NULL,1),('FB','BEF','Francs belges',NULL,0),('FF','FRF','Francs francais',NULL,0),('FH','HUF','Forint hongrois',NULL,1),('FL','LUF','Francs luxembourgeois',NULL,0),('FO','NLG','Florins',NULL,1),('FS','CHF','Francs suisses',NULL,1),('ID','IDR','Rupiahs d\'indonesie',NULL,1),('IN','INR','Roupie indienne',NULL,1),('KR','KRW','Won coree du sud',NULL,1),('LI','IEP','Livres irlandaises',NULL,1),('LK','LKR','Roupies sri lanka',NULL,1),('LR','ITL','Lires',NULL,0),('LS','GBP','Livres sterling',NULL,1),('LT','LTL','Litas',NULL,1),('MA','DEM','Deutsch mark',NULL,0),('MF','FIM','Mark finlandais',NULL,1),('MU','MUR','Roupies mauritiennes',NULL,1),('NZ','NZD','Dollar neo-zelandais',NULL,1),('PA','ARP','Pesos argentins',NULL,1),('PC','CLP','Pesos chilien',NULL,1),('PE','ESP','Pesete',NULL,1),('PL','PLN','Zlotys polonais',NULL,1),('RB','BRL','Real bresilien',NULL,1),('RU','SUR','Rouble',NULL,1),('SA','ATS','Shiliing autrichiens',NULL,1),('SK','SKK','Couronnes slovaques',NULL,1),('SR','SAR','Saudi riyal',NULL,1),('TD','TND','Dinar tunisien',NULL,1),('TR','TRL','Livre turque',NULL,1),('TW','TWD','Dollar taiwanais',NULL,1),('YC','CNY','Yuang chinois',NULL,1),('YE','JPY','Yens',NULL,1),('ZA','ZAR','Rand africa',NULL,1);
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
  `cheflieu` varchar(7) DEFAULT NULL,
  `tncc` int(11) DEFAULT NULL,
  `ncc` varchar(50) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_departements` (`code_departement`,`fk_region`),
  KEY `idx_departements_fk_region` (`fk_region`)
) ENGINE=InnoDB AUTO_INCREMENT=374 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_departements`
--

LOCK TABLES `llx_c_departements` WRITE;
/*!40000 ALTER TABLE `llx_c_departements` DISABLE KEYS */;
INSERT INTO `llx_c_departements` VALUES (1,'0',0,'0',0,'-','-',1),(2,'01',82,'01053',5,'AIN','Ain',1),(3,'02',22,'02408',5,'AISNE','Aisne',1),(4,'03',83,'03190',5,'ALLIER','Allier',1),(5,'04',93,'04070',4,'ALPES-DE-HAUTE-PROVENCE','Alpes-de-Haute-Provence',1),(6,'05',93,'05061',4,'HAUTES-ALPES','Hautes-Alpes',1),(7,'06',93,'06088',4,'ALPES-MARITIMES','Alpes-Maritimes',1),(8,'07',82,'07186',5,'ARDECHE','Ardèche',1),(9,'08',21,'08105',4,'ARDENNES','Ardennes',1),(10,'09',73,'09122',5,'ARIEGE','Ariège',1),(11,'10',21,'10387',5,'AUBE','Aube',1),(12,'11',91,'11069',5,'AUDE','Aude',1),(13,'12',73,'12202',5,'AVEYRON','Aveyron',1),(14,'13',93,'13055',4,'BOUCHES-DU-RHONE','Bouches-du-Rhône',1),(15,'14',25,'14118',2,'CALVADOS','Calvados',1),(16,'15',83,'15014',2,'CANTAL','Cantal',1),(17,'16',54,'16015',3,'CHARENTE','Charente',1),(18,'17',54,'17300',3,'CHARENTE-MARITIME','Charente-Maritime',1),(19,'18',24,'18033',2,'CHER','Cher',1),(20,'19',74,'19272',3,'CORREZE','Corrèze',1),(21,'2A',94,'2A004',3,'CORSE-DU-SUD','Corse-du-Sud',1),(22,'2B',94,'2B033',3,'HAUTE-CORSE','Haute-Corse',1),(23,'21',26,'21231',3,'COTE-D\'OR','Côte-d\'Or',1),(24,'22',53,'22278',4,'COTES-D\'ARMOR','Côtes-d\'Armor',1),(25,'23',74,'23096',3,'CREUSE','Creuse',1),(26,'24',72,'24322',3,'DORDOGNE','Dordogne',1),(27,'25',43,'25056',2,'DOUBS','Doubs',1),(28,'26',82,'26362',3,'DROME','Drôme',1),(29,'27',23,'27229',5,'EURE','Eure',1),(30,'28',24,'28085',1,'EURE-ET-LOIR','Eure-et-Loir',1),(31,'29',53,'29232',2,'FINISTERE','Finistère',1),(32,'30',91,'30189',2,'GARD','Gard',1),(33,'31',73,'31555',3,'HAUTE-GARONNE','Haute-Garonne',1),(34,'32',73,'32013',2,'GERS','Gers',1),(35,'33',72,'33063',3,'GIRONDE','Gironde',1),(36,'34',91,'34172',5,'HERAULT','Hérault',1),(37,'35',53,'35238',1,'ILLE-ET-VILAINE','Ille-et-Vilaine',1),(38,'36',24,'36044',5,'INDRE','Indre',1),(39,'37',24,'37261',1,'INDRE-ET-LOIRE','Indre-et-Loire',1),(40,'38',82,'38185',5,'ISERE','Isère',1),(41,'39',43,'39300',2,'JURA','Jura',1),(42,'40',72,'40192',4,'LANDES','Landes',1),(43,'41',24,'41018',0,'LOIR-ET-CHER','Loir-et-Cher',1),(44,'42',82,'42218',3,'LOIRE','Loire',1),(45,'43',83,'43157',3,'HAUTE-LOIRE','Haute-Loire',1),(46,'44',52,'44109',3,'LOIRE-ATLANTIQUE','Loire-Atlantique',1),(47,'45',24,'45234',2,'LOIRET','Loiret',1),(48,'46',73,'46042',2,'LOT','Lot',1),(49,'47',72,'47001',0,'LOT-ET-GARONNE','Lot-et-Garonne',1),(50,'48',91,'48095',3,'LOZERE','Lozère',1),(51,'49',52,'49007',0,'MAINE-ET-LOIRE','Maine-et-Loire',1),(52,'50',25,'50502',3,'MANCHE','Manche',1),(53,'51',21,'51108',3,'MARNE','Marne',1),(54,'52',21,'52121',3,'HAUTE-MARNE','Haute-Marne',1),(55,'53',52,'53130',3,'MAYENNE','Mayenne',1),(56,'54',41,'54395',0,'MEURTHE-ET-MOSELLE','Meurthe-et-Moselle',1),(57,'55',41,'55029',3,'MEUSE','Meuse',1),(58,'56',53,'56260',2,'MORBIHAN','Morbihan',1),(59,'57',41,'57463',3,'MOSELLE','Moselle',1),(60,'58',26,'58194',3,'NIEVRE','Nièvre',1),(61,'59',31,'59350',2,'NORD','Nord',1),(62,'60',22,'60057',5,'OISE','Oise',1),(63,'61',25,'61001',5,'ORNE','Orne',1),(64,'62',31,'62041',2,'PAS-DE-CALAIS','Pas-de-Calais',1),(65,'63',83,'63113',2,'PUY-DE-DOME','Puy-de-Dôme',1),(66,'64',72,'64445',4,'PYRENEES-ATLANTIQUES','Pyrénées-Atlantiques',1),(67,'65',73,'65440',4,'HAUTES-PYRENEES','Hautes-Pyrénées',1),(68,'66',91,'66136',4,'PYRENEES-ORIENTALES','Pyrénées-Orientales',1),(69,'67',42,'67482',2,'BAS-RHIN','Bas-Rhin',1),(70,'68',42,'68066',2,'HAUT-RHIN','Haut-Rhin',1),(71,'69',82,'69123',2,'RHONE','Rhône',1),(72,'70',43,'70550',3,'HAUTE-SAONE','Haute-Saône',1),(73,'71',26,'71270',0,'SAONE-ET-LOIRE','Saône-et-Loire',1),(74,'72',52,'72181',3,'SARTHE','Sarthe',1),(75,'73',82,'73065',3,'SAVOIE','Savoie',1),(76,'74',82,'74010',3,'HAUTE-SAVOIE','Haute-Savoie',1),(77,'75',11,'75056',0,'PARIS','Paris',1),(78,'76',23,'76540',3,'SEINE-MARITIME','Seine-Maritime',1),(79,'77',11,'77288',0,'SEINE-ET-MARNE','Seine-et-Marne',1),(80,'78',11,'78646',4,'YVELINES','Yvelines',1),(81,'79',54,'79191',4,'DEUX-SEVRES','Deux-Sèvres',1),(82,'80',22,'80021',3,'SOMME','Somme',1),(83,'81',73,'81004',2,'TARN','Tarn',1),(84,'82',73,'82121',0,'TARN-ET-GARONNE','Tarn-et-Garonne',1),(85,'83',93,'83137',2,'VAR','Var',1),(86,'84',93,'84007',0,'VAUCLUSE','Vaucluse',1),(87,'85',52,'85191',3,'VENDEE','Vendée',1),(88,'86',54,'86194',3,'VIENNE','Vienne',1),(89,'87',74,'87085',3,'HAUTE-VIENNE','Haute-Vienne',1),(90,'88',41,'88160',4,'VOSGES','Vosges',1),(91,'89',26,'89024',5,'YONNE','Yonne',1),(92,'90',43,'90010',0,'TERRITOIRE DE BELFORT','Territoire de Belfort',1),(93,'91',11,'91228',5,'ESSONNE','Essonne',1),(94,'92',11,'92050',4,'HAUTS-DE-SEINE','Hauts-de-Seine',1),(95,'93',11,'93008',3,'SEINE-SAINT-DENIS','Seine-Saint-Denis',1),(96,'94',11,'94028',2,'VAL-DE-MARNE','Val-de-Marne',1),(97,'95',11,'95500',2,'VAL-D\'OISE','Val-d\'Oise',1),(98,'971',1,'97105',3,'GUADELOUPE','Guadeloupe',1),(99,'972',2,'97209',3,'MARTINIQUE','Martinique',1),(100,'973',3,'97302',3,'GUYANE','Guyane',1),(101,'974',4,'97411',3,'REUNION','Réunion',1),(102,'01',201,'',1,'ANVERS','Anvers',1),(103,'02',203,'',3,'BRUXELLES-CAPITALE','Bruxelles-Capitale',1),(104,'03',202,'',2,'BRABANT-WALLON','Brabant-Wallon',1),(105,'04',201,'',1,'BRABANT-FLAMAND','Brabant-Flamand',1),(106,'05',201,'',1,'FLANDRE-OCCIDENTALE','Flandre-Occidentale',1),(107,'06',201,'',1,'FLANDRE-ORIENTALE','Flandre-Orientale',1),(108,'07',202,'',2,'HAINAUT','Hainaut',1),(109,'08',201,'',2,'LIEGE','Liège',1),(110,'09',202,'',1,'LIMBOURG','Limbourg',1),(111,'10',202,'',2,'LUXEMBOURG','Luxembourg',1),(112,'11',201,'',2,'NAMUR','Namur',1),(113,'NSW',2801,'',1,'','New South Wales',1),(114,'VIC',2801,'',1,'','Victoria',1),(115,'QLD',2801,'',1,'','Queensland',1),(116,'SA',2801,'',1,'','South Australia',1),(117,'ACT',2801,'',1,'','Australia Capital Territory',1),(118,'TAS',2801,'',1,'','Tasmania',1),(119,'WA',2801,'',1,'','Western Australia',1),(120,'NT',2801,'',1,'','Northern Territory',1),(121,'01',419,'',19,'PAIS VASCO','País Vasco',1),(122,'02',404,'',4,'ALBACETE','Albacete',1),(123,'03',411,'',11,'ALICANTE','Alicante',1),(124,'04',401,'',1,'ALMERIA','Almería',1),(125,'05',403,'',3,'AVILA','Avila',1),(126,'06',412,'',12,'BADAJOZ','Badajoz',1),(127,'07',414,'',14,'ISLAS BALEARES','Islas Baleares',1),(128,'08',406,'',6,'BARCELONA','Barcelona',1),(129,'09',403,'',8,'BURGOS','Burgos',1),(130,'10',412,'',12,'CACERES','Cáceres',1),(131,'11',401,'',1,'CADIz','Cádiz',1),(132,'12',411,'',11,'CASTELLON','Castellón',1),(133,'13',404,'',4,'CIUDAD REAL','Ciudad Real',1),(134,'14',401,'',1,'CORDOBA','Córdoba',1),(135,'15',413,'',13,'LA CORUÑA','La Coruña',1),(136,'16',404,'',4,'CUENCA','Cuenca',1),(137,'17',406,'',6,'GERONA','Gerona',1),(138,'18',401,'',1,'GRANADA','Granada',1),(139,'19',404,'',4,'GUADALAJARA','Guadalajara',1),(140,'20',419,'',19,'GUIPUZCOA','Guipúzcoa',1),(141,'21',401,'',1,'HUELVA','Huelva',1),(142,'22',402,'',2,'HUESCA','Huesca',1),(143,'23',401,'',1,'JAEN','Jaén',1),(144,'24',403,'',3,'LEON','León',1),(145,'25',406,'',6,'LERIDA','Lérida',1),(146,'26',415,'',15,'LA RIOJA','La Rioja',1),(147,'27',413,'',13,'LUGO','Lugo',1),(148,'28',416,'',16,'MADRID','Madrid',1),(149,'29',401,'',1,'MALAGA','Málaga',1),(150,'30',417,'',17,'MURCIA','Murcia',1),(151,'31',408,'',8,'NAVARRA','Navarra',1),(152,'32',413,'',13,'ORENSE','Orense',1),(153,'33',418,'',18,'ASTURIAS','Asturias',1),(154,'34',403,'',3,'PALENCIA','Palencia',1),(155,'35',405,'',5,'LAS PALMAS','Las Palmas',1),(156,'36',413,'',13,'PONTEVEDRA','Pontevedra',1),(157,'37',403,'',3,'SALAMANCA','Salamanca',1),(158,'38',405,'',5,'STA. CRUZ DE TENERIFE','Sta. Cruz de Tenerife',1),(159,'39',410,'',10,'CANTABRIA','Cantabria',1),(160,'40',403,'',3,'SEGOVIA','Segovia',1),(161,'41',401,'',1,'SEVILLA','Sevilla',1),(162,'42',403,'',3,'SORIA','Soria',1),(163,'43',406,'',6,'TARRAGONA','Tarragona',1),(164,'44',402,'',2,'TERUEL','Teruel',1),(165,'45',404,'',5,'TOLEDO','Toledo',1),(166,'46',411,'',11,'VALENCIA','Valencia',1),(167,'47',403,'',3,'VALLADOLID','Valladolid',1),(168,'48',419,'',19,'VIZCAYA','Vizcaya',1),(169,'49',403,'',3,'ZAMORA','Zamora',1),(170,'50',402,'',1,'ZARAGOZA','Zaragoza',1),(171,'51',407,'',7,'CEUTA','Ceuta',1),(172,'52',409,'',9,'MELILLA','Melilla',1),(173,'53',420,'',20,'OTROS','Otros',1),(174,'AG',601,NULL,NULL,'ARGOVIE','Argovie',1),(175,'AI',601,NULL,NULL,'APPENZELL RHODES INTERIEURES','Appenzell Rhodes intérieures',1),(176,'AR',601,NULL,NULL,'APPENZELL RHODES EXTERIEURES','Appenzell Rhodes extérieures',1),(177,'BE',601,NULL,NULL,'BERNE','Berne',1),(178,'BL',601,NULL,NULL,'BALE CAMPAGNE','Bâle Campagne',1),(179,'BS',601,NULL,NULL,'BALE VILLE','Bâle Ville',1),(180,'FR',601,NULL,NULL,'FRIBOURG','Fribourg',1),(181,'GE',601,NULL,NULL,'GENEVE','Genève',1),(182,'GL',601,NULL,NULL,'GLARIS','Glaris',1),(183,'GR',601,NULL,NULL,'GRISONS','Grisons',1),(184,'JU',601,NULL,NULL,'JURA','Jura',1),(185,'LU',601,NULL,NULL,'LUCERNE','Lucerne',1),(186,'NE',601,NULL,NULL,'NEUCHATEL','Neuchâtel',1),(187,'NW',601,NULL,NULL,'NIDWALD','Nidwald',1),(188,'OW',601,NULL,NULL,'OBWALD','Obwald',1),(189,'SG',601,NULL,NULL,'SAINT-GALL','Saint-Gall',1),(190,'SH',601,NULL,NULL,'SCHAFFHOUSE','Schaffhouse',1),(191,'SO',601,NULL,NULL,'SOLEURE','Soleure',1),(192,'SZ',601,NULL,NULL,'SCHWYZ','Schwyz',1),(193,'TG',601,NULL,NULL,'THURGOVIE','Thurgovie',1),(194,'TI',601,NULL,NULL,'TESSIN','Tessin',1),(195,'UR',601,NULL,NULL,'URI','Uri',1),(196,'VD',601,NULL,NULL,'VAUD','Vaud',1),(197,'VS',601,NULL,NULL,'VALAIS','Valais',1),(198,'ZG',601,NULL,NULL,'ZUG','Zug',1),(199,'ZH',601,NULL,NULL,'ZURICH','Zürich',1),(200,'AL',1101,'',0,'ALABAMA','Alabama',1),(201,'AK',1101,'',0,'ALASKA','Alaska',1),(202,'AZ',1101,'',0,'ARIZONA','Arizona',1),(203,'AR',1101,'',0,'ARKANSAS','Arkansas',1),(204,'CA',1101,'',0,'CALIFORNIA','California',1),(205,'CO',1101,'',0,'COLORADO','Colorado',1),(206,'CT',1101,'',0,'CONNECTICUT','Connecticut',1),(207,'DE',1101,'',0,'DELAWARE','Delaware',1),(208,'FL',1101,'',0,'FLORIDA','Florida',1),(209,'GA',1101,'',0,'GEORGIA','Georgia',1),(210,'HI',1101,'',0,'HAWAII','Hawaii',1),(211,'ID',1101,'',0,'IDAHO','Idaho',1),(212,'IL',1101,'',0,'ILLINOIS','Illinois',1),(213,'IN',1101,'',0,'INDIANA','Indiana',1),(214,'IA',1101,'',0,'IOWA','Iowa',1),(215,'KS',1101,'',0,'KANSAS','Kansas',1),(216,'KY',1101,'',0,'KENTUCKY','Kentucky',1),(217,'LA',1101,'',0,'LOUISIANA','Louisiana',1),(218,'ME',1101,'',0,'MAINE','Maine',1),(219,'MD',1101,'',0,'MARYLAND','Maryland',1),(220,'MA',1101,'',0,'MASSACHUSSETTS','Massachusetts',1),(221,'MI',1101,'',0,'MICHIGAN','Michigan',1),(222,'MN',1101,'',0,'MINNESOTA','Minnesota',1),(223,'MS',1101,'',0,'MISSISSIPPI','Mississippi',1),(224,'MO',1101,'',0,'MISSOURI','Missouri',1),(225,'MT',1101,'',0,'MONTANA','Montana',1),(226,'NE',1101,'',0,'NEBRASKA','Nebraska',1),(227,'NV',1101,'',0,'NEVADA','Nevada',1),(228,'NH',1101,'',0,'NEW HAMPSHIRE','New Hampshire',1),(229,'NJ',1101,'',0,'NEW JERSEY','New Jersey',1),(230,'NM',1101,'',0,'NEW MEXICO','New Mexico',1),(231,'NY',1101,'',0,'NEW YORK','New York',1),(232,'NC',1101,'',0,'NORTH CAROLINA','North Carolina',1),(233,'ND',1101,'',0,'NORTH DAKOTA','North Dakota',1),(234,'OH',1101,'',0,'OHIO','Ohio',1),(235,'OK',1101,'',0,'OKLAHOMA','Oklahoma',1),(236,'OR',1101,'',0,'OREGON','Oregon',1),(237,'PA',1101,'',0,'PENNSYLVANIA','Pennsylvania',1),(238,'RI',1101,'',0,'RHODE ISLAND','Rhode Island',1),(239,'SC',1101,'',0,'SOUTH CAROLINA','South Carolina',1),(240,'SD',1101,'',0,'SOUTH DAKOTA','South Dakota',1),(241,'TN',1101,'',0,'TENNESSEE','Tennessee',1),(242,'TX',1101,'',0,'TEXAS','Texas',1),(243,'UT',1101,'',0,'UTAH','Utah',1),(244,'VT',1101,'',0,'VERMONT','Vermont',1),(245,'VA',1101,'',0,'VIRGINIA','Virginia',1),(246,'WA',1101,'',0,'WASHINGTON','Washington',1),(247,'WV',1101,'',0,'WEST VIRGINIA','West Virginia',1),(248,'WI',1101,'',0,'WISCONSIN','Wisconsin',1),(249,'WY',1101,'',0,'WYOMING','Wyoming',1),(250,'SS',8601,NULL,NULL,NULL,'San Salvador',1),(251,'SA',8603,NULL,NULL,NULL,'Santa Ana',1),(252,'AH',8603,NULL,NULL,NULL,'Ahuachapan',1),(253,'SO',8603,NULL,NULL,NULL,'Sonsonate',1),(254,'US',8602,NULL,NULL,NULL,'Usulutan',1),(255,'SM',8602,NULL,NULL,NULL,'San Miguel',1),(256,'MO',8602,NULL,NULL,NULL,'Morazan',1),(257,'LU',8602,NULL,NULL,NULL,'La Union',1),(258,'LL',8601,NULL,NULL,NULL,'La Libertad',1),(259,'CH',8601,NULL,NULL,NULL,'Chalatenango',1),(260,'CA',8601,NULL,NULL,NULL,'Cabañas',1),(261,'LP',8601,NULL,NULL,NULL,'La Paz',1),(262,'SV',8601,NULL,NULL,NULL,'San Vicente',1),(263,'CU',8601,NULL,NULL,NULL,'Cuscatlan',1),(264,'2301',2301,'',0,'CATAMARCA','Catamarca',1),(265,'2302',2301,'',0,'YUJUY','Yujuy',1),(266,'2303',2301,'',0,'TUCAMAN','Tucamán',1),(267,'2304',2301,'',0,'SANTIAGO DEL ESTERO','Santiago del Estero',1),(268,'2305',2301,'',0,'SALTA','Salta',1),(269,'2306',2302,'',0,'CHACO','Chaco',1),(270,'2307',2302,'',0,'CORRIENTES','Corrientes',1),(271,'2308',2302,'',0,'ENTRE RIOS','Entre Ríos',1),(272,'2309',2302,'',0,'FORMOSA MISIONES','Formosa Misiones',1),(273,'2310',2302,'',0,'SANTA FE','Santa Fe',1),(274,'2311',2303,'',0,'LA RIOJA','La Rioja',1),(275,'2312',2303,'',0,'MENDOZA','Mendoza',1),(276,'2313',2303,'',0,'SAN JUAN','San Juan',1),(277,'2314',2303,'',0,'SAN LUIS','San Luis',1),(278,'2315',2304,'',0,'CORDOBA','Córdoba',1),(279,'2316',2304,'',0,'BUENOS AIRES','Buenos Aires',1),(280,'2317',2304,'',0,'CABA','Caba',1),(281,'2318',2305,'',0,'LA PAMPA','La Pampa',1),(282,'2319',2305,'',0,'NEUQUEN','Neuquén',1),(283,'2320',2305,'',0,'RIO NEGRO','Río Negro',1),(284,'2321',2305,'',0,'CHUBUT','Chubut',1),(285,'2322',2305,'',0,'SANTA CRUZ','Santa Cruz',1),(286,'2323',2305,'',0,'TIERRA DEL FUEGO','Tierra del Fuego',1),(287,'2324',2305,'',0,'ISLAS MALVINAS','Islas Malvinas',1),(288,'2325',2305,'',0,'ANTARTIDA','Antártida',1),(289,'AN',11701,NULL,0,'AN','Andaman & Nicobar',1),(290,'AP',11701,NULL,0,'AP','Andhra Pradesh',1),(291,'AR',11701,NULL,0,'AR','Arunachal Pradesh',1),(292,'AS',11701,NULL,0,'AS','Assam',1),(293,'BR',11701,NULL,0,'BR','Bihar',1),(294,'CG',11701,NULL,0,'CG','Chattisgarh',1),(295,'CH',11701,NULL,0,'CH','Chandigarh',1),(296,'DD',11701,NULL,0,'DD','Daman & Diu',1),(297,'DL',11701,NULL,0,'DL','Delhi',1),(298,'DN',11701,NULL,0,'DN','Dadra and Nagar Haveli',1),(299,'GA',11701,NULL,0,'GA','Goa',1),(300,'GJ',11701,NULL,0,'GJ','Gujarat',1),(301,'HP',11701,NULL,0,'HP','Himachal Pradesh',1),(302,'HR',11701,NULL,0,'HR','Haryana',1),(303,'JH',11701,NULL,0,'JH','Jharkhand',1),(304,'JK',11701,NULL,0,'JK','Jammu & Kashmir',1),(305,'KA',11701,NULL,0,'KA','Karnataka',1),(306,'KL',11701,NULL,0,'KL','Kerala',1),(307,'LD',11701,NULL,0,'LD','Lakshadweep',1),(308,'MH',11701,NULL,0,'MH','Maharashtra',1),(309,'ML',11701,NULL,0,'ML','Meghalaya',1),(310,'MN',11701,NULL,0,'MN','Manipur',1),(311,'MP',11701,NULL,0,'MP','Madhya Pradesh',1),(312,'MZ',11701,NULL,0,'MZ','Mizoram',1),(313,'NL',11701,NULL,0,'NL','Nagaland',1),(314,'OR',11701,NULL,0,'OR','Orissa',1),(315,'PB',11701,NULL,0,'PB','Punjab',1),(316,'PY',11701,NULL,0,'PY','Puducherry',1),(317,'RJ',11701,NULL,0,'RJ','Rajasthan',1),(318,'SK',11701,NULL,0,'SK','Sikkim',1),(319,'TN',11701,NULL,0,'TN','Tamil Nadu',1),(320,'TR',11701,NULL,0,'TR','Tripura',1),(321,'UL',11701,NULL,0,'UL','Uttarakhand',1),(322,'UP',11701,NULL,0,'UP','Uttar Pradesh',1),(323,'WB',11701,NULL,0,'WB','West Bengal',1);
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_effectif`
--

LOCK TABLES `llx_c_effectif` WRITE;
/*!40000 ALTER TABLE `llx_c_effectif` DISABLE KEYS */;
INSERT INTO `llx_c_effectif` VALUES (0,'EF0','-',1),(1,'EF1-5','1 - 5',1),(2,'EF6-10','6 - 10',1),(3,'EF11-50','11 - 50',1),(4,'EF51-100','51 - 100',1),(5,'EF100-500','100 - 500',1),(6,'EF500-','> 500',1);
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
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_c_forme_juridique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=399 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_forme_juridique`
--

LOCK TABLES `llx_c_forme_juridique` WRITE;
/*!40000 ALTER TABLE `llx_c_forme_juridique` DISABLE KEYS */;
INSERT INTO `llx_c_forme_juridique` VALUES (249,0,0,'-',0,1),(250,11,1,'Artisan Commerçant (EI)',0,1),(251,12,1,'Commerçant (EI)',0,1),(252,13,1,'Artisan (EI)',0,1),(253,14,1,'Officier public ou ministériel',0,1),(254,15,1,'Profession libérale (EI)',0,1),(255,16,1,'Exploitant agricole',0,1),(256,17,1,'Agent commercial',0,1),(257,18,1,'Associé Gérant de société',0,1),(258,19,1,'Personne physique',0,1),(259,21,1,'Indivision',0,1),(260,22,1,'Société créée de fait',0,1),(261,23,1,'Société en participation',0,1),(262,27,1,'Paroisse hors zone concordataire',0,1),(263,29,1,'Groupement de droit privé non doté de la personnalité morale',0,1),(264,31,1,'Personne morale de droit étranger, immatriculée au RCS',0,1),(265,32,1,'Personne morale de droit étranger, non immatriculée au RCS',0,1),(266,35,1,'Régime auto-entrepreneur',0,1),(267,41,1,'Établissement public ou régie à caractère industriel ou commercial',0,1),(268,51,1,'Société coopérative commerciale particulière',0,1),(269,52,1,'Société en nom collectif',0,1),(270,53,1,'Société en commandite',0,1),(271,54,1,'Société à responsabilité limitée (SARL)',0,1),(272,55,1,'Société anonyme à conseil d administration',0,1),(273,56,1,'Société anonyme à directoire',0,1),(274,57,1,'Société par actions simplifiée',0,1),(275,58,1,'Entreprise Unipersonnelle à Responsabilité Limitée (EURL)',0,1),(276,61,1,'Caisse d\'épargne et de prévoyance',0,1),(277,62,1,'Groupement d\'intérêt économique (GIE)',0,1),(278,63,1,'Société coopérative agricole',0,1),(279,64,1,'Société non commerciale d assurances',0,1),(280,65,1,'Société civile',0,1),(281,69,1,'Personnes de droit privé inscrites au RCS',0,1),(282,71,1,'Administration de l état',0,1),(283,72,1,'Collectivité territoriale',0,1),(284,73,1,'Établissement public administratif',0,1),(285,74,1,'Personne morale de droit public administratif',0,1),(286,81,1,'Organisme gérant régime de protection social à adhésion obligatoire',0,1),(287,82,1,'Organisme mutualiste',0,1),(288,83,1,'Comité d entreprise',0,1),(289,84,1,'Organisme professionnel',0,1),(290,85,1,'Organisme de retraite à adhésion non obligatoire',0,1),(291,91,1,'Syndicat de propriétaires',0,1),(292,92,1,'Association loi 1901 ou assimilé',0,1),(293,93,1,'Fondation',0,1),(294,99,1,'Personne morale de droit privé',0,1),(295,200,2,'Indépendant',0,1),(296,201,2,'SPRL - Société à responsabilité limitée',0,1),(297,202,2,'SA   - Société Anonyme',0,1),(298,203,2,'SCRL - Société coopérative à responsabilité limitée',0,1),(299,204,2,'ASBL - Association sans but Lucratif',0,1),(300,205,2,'SCRI - Société coopérative à responsabilité illimitée',0,1),(301,206,2,'SCS  - Société en commandite simple',0,1),(302,207,2,'SCA  - Société en commandite par action',0,1),(303,208,2,'SNC  - Société en nom collectif',0,1),(304,209,2,'GIE  - Groupement d intérêt économique',0,1),(305,210,2,'GEIE - Groupement européen d intérêt économique',0,1),(306,500,5,'Limited liability corporation (GmbH)',0,1),(307,501,5,'Stock corporation (AG)',0,1),(308,502,5,'Partnerships general or limited (GmbH & CO. KG)',0,1),(309,503,5,'Sole proprietor / Private business',0,1),(310,600,6,'Raison Individuelle',0,1),(311,601,6,'Société Simple',0,1),(312,602,6,'Société en nom collectif',0,1),(313,603,6,'Société en commandite',0,1),(314,604,6,'Société anonyme (SA)',0,1),(315,605,6,'Société en commandite par actions',0,1),(316,606,6,'Société à responsabilité limitée (SARL)',0,1),(317,607,6,'Société coopérative',0,1),(318,608,6,'Association',0,1),(319,609,6,'Fondation',0,1),(320,700,7,'Sole Trader',0,1),(321,701,7,'Partnership',0,1),(322,702,7,'Private Limited Company by shares (LTD)',0,1),(323,703,7,'Public Limited Company',0,1),(324,704,7,'Workers Cooperative',0,1),(325,705,7,'Limited Liability Partnership',0,1),(326,706,7,'Franchise',0,1),(327,1000,10,'Société à responsabilité limitée (SARL)',0,1),(328,1001,10,'Société en Nom Collectif (SNC)',0,1),(329,1002,10,'Société en Commandite Simple (SCS)',0,1),(330,1003,10,'société en participation',0,1),(331,1004,10,'Société Anonyme (SA)',0,1),(332,1005,10,'Société Unipersonnelle à Responsabilité Limitée (SUARL)',0,1),(333,1006,10,'Groupement d\'intérêt économique (GEI)',0,1),(334,1007,10,'Groupe de sociétés',0,1),(335,401,4,'Empresario Individual',0,1),(336,402,4,'Comunidad de Bienes',0,1),(337,403,4,'Sociedad Civil',0,1),(338,404,4,'Sociedad Colectiva',0,1),(339,405,4,'Sociedad Limitada',0,1),(340,406,4,'Sociedad Anónima',0,1),(341,407,4,'Sociedad Comandataria por Acciones',0,1),(342,408,4,'Sociedad Comandataria Simple',0,1),(343,409,4,'Sociedad Laboral',0,1),(344,410,4,'Sociedad Cooperativa',0,1),(345,411,4,'Sociedad de Garantía Recíproca',0,1),(346,412,4,'Entidad de Capital-Riesgo',0,1),(347,413,4,'Agrupación de Interés Económico',0,1),(348,414,4,'Sociedad de Inversión Mobiliaria',0,1),(349,415,4,'Agrupación sin Ánimo de Lucro',0,1),(350,15201,152,'Mauritius Private Company Limited By Shares',0,1),(351,15202,152,'Mauritius Company Limited By Guarantee',0,1),(352,15203,152,'Mauritius Public Company Limited By Shares',0,1),(353,15204,152,'Mauritius Foreign Company',0,1),(354,15205,152,'Mauritius GBC1 (Offshore Company)',0,1),(355,15206,152,'Mauritius GBC2 (International Company)',0,1),(356,15207,152,'Mauritius General Partnership',0,1),(357,15208,152,'Mauritius Limited Partnership',0,1),(358,15209,152,'Mauritius Sole Proprietorship',0,1),(359,15210,152,'Mauritius Trusts',0,1),(360,2301,23,'Monotributista',0,1),(361,2302,23,'Sociedad Civil',0,1),(362,2303,23,'Sociedades Comerciales',0,1),(363,2304,23,'Sociedades de Hecho',0,1),(364,2305,23,'Sociedades Irregulares',0,1),(365,2306,23,'Sociedad Colectiva',0,1),(366,2307,23,'Sociedad en Comandita Simple',0,1),(367,2308,23,'Sociedad de Capital e Industria',0,1),(368,2309,23,'Sociedad Accidental o en participación',0,1),(369,2310,23,'Sociedad de Responsabilidad Limitada',0,1),(370,2311,23,'Sociedad Anónima',0,1),(371,2312,23,'Sociedad Anónima con Participación Estatal Mayoritaria',0,1),(372,2313,23,'Sociedad en Comandita por Acciones (arts. 315 a 324, LSC)',0,1);
/*!40000 ALTER TABLE `llx_c_forme_juridique` ENABLE KEYS */;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_paiement`
--

LOCK TABLES `llx_c_paiement` WRITE;
/*!40000 ALTER TABLE `llx_c_paiement` DISABLE KEYS */;
INSERT INTO `llx_c_paiement` VALUES (0,'','-',3,1),(1,'TIP','TIP',2,1),(2,'VIR','Virement',2,1),(3,'PRE','Prélèvement',2,1),(4,'LIQ','Espèces',2,1),(5,'VAD','Paiement en ligne',2,0),(6,'CB','Carte Bancaire',2,1),(7,'CHQ','Chèque',2,1),(8,'TRA','Traite',2,0),(9,'LCR','LCR',2,0),(10,'FAC','Factor',2,0),(11,'PRO','Proforma',2,0);
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
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=226 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_paper_format`
--

LOCK TABLES `llx_c_paper_format` WRITE;
/*!40000 ALTER TABLE `llx_c_paper_format` DISABLE KEYS */;
INSERT INTO `llx_c_paper_format` VALUES (1,'4A0','Format 4A0',1682.00,2378.00,'mm',1),(2,'2A0','Format 2A0',1189.00,1682.00,'mm',1),(3,'A0','Format A0',840.00,1189.00,'mm',1),(4,'A1','Format A1',594.00,840.00,'mm',1),(5,'A2','Format A2',420.00,594.00,'mm',1),(6,'A3','Format A3',297.00,420.00,'mm',1),(7,'A4','Format A4',210.00,297.00,'mm',1),(8,'A5','Format A5',148.00,210.00,'mm',1),(9,'A6','Format A6',105.00,148.00,'mm',1),(100,'USLetter','Format Letter (A)',216.00,279.00,'mm',0),(105,'USLegal','Format Legal',216.00,356.00,'mm',0),(110,'USExecutive','Format Executive',190.00,254.00,'mm',0),(115,'USLedger','Format Ledger/Tabloid (B)',279.00,432.00,'mm',0),(200,'Canadian P1','Format Canadian P1',560.00,860.00,'mm',0),(205,'Canadian P2','Format Canadian P2',430.00,560.00,'mm',0),(210,'Canadian P3','Format Canadian P3',280.00,430.00,'mm',0),(215,'Canadian P4','Format Canadian P4',215.00,280.00,'mm',0),(220,'Canadian P5','Format Canadian P5',140.00,215.00,'mm',0),(225,'Canadian P6','Format Canadian P6',107.00,140.00,'mm',0);
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
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_payment_term`
--

LOCK TABLES `llx_c_payment_term` WRITE;
/*!40000 ALTER TABLE `llx_c_payment_term` DISABLE KEYS */;
INSERT INTO `llx_c_payment_term` VALUES (1,'RECEP',1,1,'A réception','Réception de facture',0,0,NULL),(2,'30D',2,1,'30 jours','Réglement à 30 jours',0,30,NULL),(3,'30DENDMONTH',3,1,'30 jours fin de mois','Réglement à 30 jours fin de mois',1,30,NULL),(4,'60D',4,1,'60 jours','Réglement à 60 jours',0,60,NULL),(5,'60DENDMONTH',5,1,'60 jours fin de mois','Réglement à 60 jours fin de mois',1,60,NULL);
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
  UNIQUE KEY `code` (`code`)
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
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_prospectlevel`
--

LOCK TABLES `llx_c_prospectlevel` WRITE;
/*!40000 ALTER TABLE `llx_c_prospectlevel` DISABLE KEYS */;
INSERT INTO `llx_c_prospectlevel` VALUES ('PL_HIGH','High',4,1),('PL_LOW','Low',2,1),('PL_MEDIUM','Medium',3,1),('PL_NONE','None',1,1);
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
  `cheflieu` varchar(7) DEFAULT NULL,
  `tncc` int(11) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `code_region` (`code_region`),
  KEY `idx_c_regions_fk_pays` (`fk_pays`),
  CONSTRAINT `fk_c_regions_fk_pays` FOREIGN KEY (`fk_pays`) REFERENCES `llx_c_pays` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=15213 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_regions`
--

LOCK TABLES `llx_c_regions` WRITE;
/*!40000 ALTER TABLE `llx_c_regions` DISABLE KEYS */;
INSERT INTO `llx_c_regions` VALUES (1,0,0,'0',0,'-',1),(101,1,1,'97105',3,'Guadeloupe',1),(102,2,1,'97209',3,'Martinique',1),(103,3,1,'97302',3,'Guyane',1),(104,4,1,'97411',3,'Réunion',1),(105,11,1,'75056',1,'Île-de-France',1),(106,21,1,'51108',0,'Champagne-Ardenne',1),(107,22,1,'80021',0,'Picardie',1),(108,23,1,'76540',0,'Haute-Normandie',1),(109,24,1,'45234',2,'Centre',1),(110,25,1,'14118',0,'Basse-Normandie',1),(111,26,1,'21231',0,'Bourgogne',1),(112,31,1,'59350',2,'Nord-Pas-de-Calais',1),(113,41,1,'57463',0,'Lorraine',1),(114,42,1,'67482',1,'Alsace',1),(115,43,1,'25056',0,'Franche-Comté',1),(116,52,1,'44109',4,'Pays de la Loire',1),(117,53,1,'35238',0,'Bretagne',1),(118,54,1,'86194',2,'Poitou-Charentes',1),(119,72,1,'33063',1,'Aquitaine',1),(120,73,1,'31555',0,'Midi-Pyrénées',1),(121,74,1,'87085',2,'Limousin',1),(122,82,1,'69123',2,'Rhône-Alpes',1),(123,83,1,'63113',1,'Auvergne',1),(124,91,1,'34172',2,'Languedoc-Roussillon',1),(125,93,1,'13055',0,'Provence-Alpes-Côte d\'Azur',1),(126,94,1,'2A004',0,'Corse',1),(201,201,2,'',1,'Flandre',1),(202,202,2,'',2,'Wallonie',1),(203,203,2,'',3,'Bruxelles-Capitale',1),(401,401,4,'',0,'Andalucia',1),(402,402,4,'',0,'Aragón',1),(403,403,4,'',0,'Castilla y León',1),(404,404,4,'',0,'Castilla la Mancha',1),(405,405,4,'',0,'Canarias',1),(406,406,4,'',0,'Cataluña',1),(407,407,4,'',0,'Comunidad de Ceuta',1),(408,408,4,'',0,'Comunidad Foral de Navarra',1),(409,409,4,'',0,'Comunidad de Melilla',1),(410,410,4,'',0,'Cantabria',1),(411,411,4,'',0,'Comunidad Valenciana',1),(412,412,4,'',0,'Extemadura',1),(413,413,4,'',0,'Galicia',1),(414,414,4,'',0,'Islas Baleares',1),(415,415,4,'',0,'La Rioja',1),(416,416,4,'',0,'Comunidad de Madrid',1),(417,417,4,'',0,'Región de Murcia',1),(418,418,4,'',0,'Principado de Asturias',1),(419,419,4,'',0,'Pais Vasco',1),(420,420,4,'',0,'Otros',1),(601,601,6,'',1,'Cantons',1),(1001,1001,10,'',0,'Ariana',1),(1002,1002,10,'',0,'Béja',1),(1003,1003,10,'',0,'Ben Arous',1),(1004,1004,10,'',0,'Bizerte',1),(1005,1005,10,'',0,'Gabès',1),(1006,1006,10,'',0,'Gafsa',1),(1007,1007,10,'',0,'Jendouba',1),(1008,1008,10,'',0,'Kairouan',1),(1009,1009,10,'',0,'Kasserine',1),(1010,1010,10,'',0,'Kébili',1),(1011,1011,10,'',0,'La Manouba',1),(1012,1012,10,'',0,'Le Kef',1),(1013,1013,10,'',0,'Mahdia',1),(1014,1014,10,'',0,'Médenine',1),(1015,1015,10,'',0,'Monastir',1),(1016,1016,10,'',0,'Nabeul',1),(1017,1017,10,'',0,'Sfax',1),(1018,1018,10,'',0,'Sidi Bouzid',1),(1019,1019,10,'',0,'Siliana',1),(1020,1020,10,'',0,'Sousse',1),(1021,1021,10,'',0,'Tataouine',1),(1022,1022,10,'',0,'Tozeur',1),(1023,1023,10,'',0,'Tunis',1),(1024,1024,10,'',0,'Zaghouan',1),(1101,1101,11,'',0,'United-States',1),(2301,2301,23,'',0,'Norte',1),(2302,2302,23,'',0,'Litoral',1),(2303,2303,23,'',0,'Cuyana',1),(2304,2304,23,'',0,'Central',1),(2305,2305,23,'',0,'Patagonia',1),(2801,2801,28,'',0,'Australia',1),(8601,8601,86,NULL,NULL,'Central',1),(8602,8602,86,NULL,NULL,'Oriental',1),(8603,8603,86,NULL,NULL,'Occidental',1),(11701,11701,117,'',0,'India',1),(15201,15201,152,'',0,'Rivière Noire',1),(15202,15202,152,'',0,'Flacq',1),(15203,15203,152,'',0,'Grand Port',1),(15204,15204,152,'',0,'Moka',1),(15205,15205,152,'',0,'Pamplemousses',1),(15206,15206,152,'',0,'Plaines Wilhems',1),(15207,15207,152,'',0,'Port-Louis',1),(15208,15208,152,'',0,'Rivière du Rempart',1),(15209,15209,152,'',0,'Savanne',1),(15210,15210,152,'',0,'Rodrigues',1),(15211,15211,152,'',0,'Les îles Agaléga',1),(15212,15212,152,'',0,'Les écueils des Cargados Carajos',1);
/*!40000 ALTER TABLE `llx_c_regions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_c_shipment_mode`
--

DROP TABLE IF EXISTS `llx_c_shipment_mode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_c_shipment_mode` (
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
-- Dumping data for table `llx_c_shipment_mode`
--

LOCK TABLES `llx_c_shipment_mode` WRITE;
/*!40000 ALTER TABLE `llx_c_shipment_mode` DISABLE KEYS */;
INSERT INTO `llx_c_shipment_mode` VALUES (1,'2010-10-09 23:43:16','CATCH','Catch','Catch by client',1),(2,'2010-10-09 23:43:16','TRANS','Transporter','Generic transporter',1),(3,'2010-10-09 23:43:16','COLSUI','Colissimo Suivi','Colissimo Suivi',0);
/*!40000 ALTER TABLE `llx_c_shipment_mode` ENABLE KEYS */;
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
  UNIQUE KEY `code` (`code`)
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
  `localtax2` double NOT NULL DEFAULT '0',
  `recuperableonly` int(11) NOT NULL DEFAULT '0',
  `note` varchar(128) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `accountancy_code` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2462 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_tva`
--

LOCK TABLES `llx_c_tva` WRITE;
/*!40000 ALTER TABLE `llx_c_tva` DISABLE KEYS */;
INSERT INTO `llx_c_tva` VALUES (11,1,19.6,0,0,0,'VAT standard rate (France hors DOM-TOM)',1,NULL),(12,1,8.5,0,0,0,'VAT standard rate (DOM sauf Guyane et Saint-Martin)',0,NULL),(13,1,8.5,0,0,1,'VAT standard rate (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par acheteur',0,NULL),(14,1,5.5,0,0,0,'VAT reduced rate (France hors DOM-TOM)',1,NULL),(15,1,0,0,0,0,'VAT Rate 0 ou non applicable',1,NULL),(16,1,2.1,0,0,0,'VAT super-reduced rate',1,NULL),(21,2,21,0,0,0,'VAT standard rate',1,NULL),(22,2,6,0,0,0,'VAT reduced rate',1,NULL),(23,2,0,0,0,0,'VAT Rate 0 ou non applicable',1,NULL),(24,2,12,0,0,0,'VAT reduced rate',1,NULL),(31,3,20,0,0,0,'VAT standard rate',1,NULL),(32,3,10,0,0,0,'VAT reduced rate',1,NULL),(33,3,4,0,0,0,'VAT super-reduced rate',1,NULL),(34,3,0,0,0,0,'VAT Rate 0',1,NULL),(41,4,18,4,0,0,'VAT standard rate',1,NULL),(42,4,8,1,0,0,'VAT reduced rate',1,NULL),(43,4,4,0.5,0,0,'VAT super-reduced rate',1,NULL),(44,4,0,0,0,0,'VAT Rate 0',1,NULL),(51,5,19,0,0,0,'VAT standard rate',1,NULL),(52,5,7,0,0,0,'VAT reduced rate',1,NULL),(53,5,0,0,0,0,'VAT Rate 0',1,NULL),(61,6,7.6,0,0,0,'VAT standard rate',1,NULL),(62,6,3.6,0,0,0,'VAT reduced rate',1,NULL),(63,6,2.4,0,0,0,'VAT super-reduced rate',1,NULL),(64,6,0,0,0,0,'VAT Rate 0',1,NULL),(71,7,17.5,0,0,0,'VAT standard rate',1,NULL),(72,7,5,0,0,0,'VAT reduced rate',1,NULL),(73,7,0,0,0,0,'VAT Rate 0',1,NULL),(91,9,17,0,0,0,'VAT standard rate',1,NULL),(92,9,13,0,0,0,'VAT reduced rate 0',1,NULL),(93,9,3,0,0,0,'VAT super reduced rate 0',1,NULL),(94,9,0,0,0,0,'VAT Rate 0',1,NULL),(101,10,6,0,0,0,'VAT 6%',1,NULL),(102,10,12,0,0,0,'VAT 12%',1,NULL),(103,10,18,0,0,0,'VAT 18%',1,NULL),(104,10,7.5,0,0,0,'VAT 6% Majoré à 25% (7.5%)',1,NULL),(105,10,15,0,0,0,'VAT 12% Majoré à 25% (15%)',1,NULL),(106,10,22.5,0,0,0,'VAT 18% Majoré à 25% (22.5%)',1,NULL),(107,10,0,0,0,0,'VAT Rate 0',1,NULL),(121,12,20,0,0,0,'VAT standard rate',1,NULL),(122,12,14,0,0,0,'VAT reduced rate',1,NULL),(123,12,10,0,0,0,'VAT reduced rate',1,NULL),(124,12,7,0,0,0,'VAT super-reduced rate',1,NULL),(125,12,0,0,0,0,'VAT Rate 0',1,NULL),(141,14,7,0,0,0,'VAT standard rate',1,NULL),(142,14,0,0,0,0,'VAT Rate 0',1,NULL),(171,17,19,0,0,0,'VAT standard rate',1,NULL),(172,17,6,0,0,0,'VAT reduced rate',1,NULL),(173,17,0,0,0,0,'VAT Rate 0',1,NULL),(201,20,25,0,0,0,'VAT standard rate',1,NULL),(202,20,12,0,0,0,'VAT reduced rate',1,NULL),(203,20,6,0,0,0,'VAT super-reduced rate',1,NULL),(204,20,0,0,0,0,'VAT Rate 0',1,NULL),(251,25,20,0,0,0,'VAT standard rate',1,NULL),(252,25,12,0,0,0,'VAT reduced rate',1,NULL),(253,25,0,0,0,0,'VAT Rate 0',1,NULL),(254,25,5,0,0,0,'VAT reduced rate',1,NULL),(281,28,10,0,0,0,'VAT standard rate',1,NULL),(282,28,0,0,0,0,'VAT Rate 0',1,NULL),(411,41,20,0,0,0,'VAT standard rate',1,NULL),(412,41,10,0,0,0,'VAT reduced rate',1,NULL),(413,41,0,0,0,0,'VAT Rate 0',1,NULL),(671,67,19,0,0,0,'VAT standard rate',1,NULL),(672,67,0,0,0,0,'VAT Rate 0',1,NULL),(861,86,13,0,0,0,'IVA 13',1,NULL),(862,86,0,0,0,0,'SIN IVA',1,NULL),(1171,117,12.5,0,0,0,'VAT standard rate',1,NULL),(1172,117,4,0,0,0,'VAT reduced rate',1,NULL),(1173,117,1,0,0,0,'VAT super-reduced rate',1,NULL),(1174,117,0,0,0,0,'VAT Rate 0',1,NULL),(1231,123,0,0,0,0,'VAT Rate 0',1,NULL),(1232,123,5,0,0,0,'VAT Rate 5',1,NULL),(1401,140,15,0,0,0,'VAT standard rate',1,NULL),(1402,140,12,0,0,0,'VAT reduced rate',1,NULL),(1403,140,6,0,0,0,'VAT reduced rate',1,NULL),(1404,140,3,0,0,0,'VAT super-reduced rate',1,NULL),(1405,140,0,0,0,0,'VAT Rate 0',1,NULL),(1521,152,0,0,0,0,'VAT Rate 0',1,NULL),(1522,152,15,0,0,0,'VAT Rate 15',1,NULL),(1931,193,0,0,0,0,'No VAT in SPM',1,NULL),(2461,246,0,0,0,0,'VAT Rate 0',1,NULL);
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
  `code` varchar(16) NOT NULL,
  `libelle` varchar(64) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_c_type_contact_uk` (`element`,`source`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_type_contact`
--

LOCK TABLES `llx_c_type_contact` WRITE;
/*!40000 ALTER TABLE `llx_c_type_contact` DISABLE KEYS */;
INSERT INTO `llx_c_type_contact` VALUES (10,'contrat','internal','SALESREPSIGN','Commercial signataire du contrat',1),(11,'contrat','internal','SALESREPFOLL','Commercial suivi du contrat',1),(20,'contrat','external','BILLING','Contact client facturation contrat',1),(21,'contrat','external','CUSTOMER','Contact client suivi contrat',1),(22,'contrat','external','SALESREPSIGN','Contact client signataire contrat',1),(31,'propal','internal','SALESREPFOLL','Commercial à l\'origine de la propale',1),(40,'propal','external','BILLING','Contact client facturation propale',1),(41,'propal','external','CUSTOMER','Contact client suivi propale',1),(50,'facture','internal','SALESREPFOLL','Responsable suivi du paiement',1),(60,'facture','external','BILLING','Contact client facturation',1),(61,'facture','external','SHIPPING','Contact client livraison',1),(62,'facture','external','SERVICE','Contact client prestation',1),(70,'invoice_supplier','internal','SALESREPFOLL','Responsable suivi du paiement',1),(71,'invoice_supplier','external','BILLING','Contact fournisseur facturation',1),(72,'invoice_supplier','external','SHIPPING','Contact fournisseur livraison',1),(73,'invoice_supplier','external','SERVICE','Contact fournisseur prestation',1),(91,'commande','internal','SALESREPFOLL','Responsable suivi de la commande',1),(100,'commande','external','BILLING','Contact client facturation commande',1),(101,'commande','external','CUSTOMER','Contact client suivi commande',1),(102,'commande','external','SHIPPING','Contact client livraison commande',1),(120,'fichinter','internal','INTERREPFOLL','Responsable suivi de l\'intervention',1),(121,'fichinter','internal','INTERVENING','Intervenant',1),(130,'fichinter','external','BILLING','Contact client facturation intervention',1),(131,'fichinter','external','CUSTOMER','Contact client suivi de l\'intervention',1),(140,'order_supplier','internal','SALESREPFOLL','Responsable suivi de la commande',1),(141,'order_supplier','internal','SHIPPING','Responsable réception de la commande',1),(142,'order_supplier','external','BILLING','Contact fournisseur facturation commande',1),(143,'order_supplier','external','CUSTOMER','Contact fournisseur suivi commande',1),(145,'order_supplier','external','SHIPPING','Contact fournisseur livraison commande',1),(160,'project','internal','PROJECTLEADER','Chef de Projet',1),(161,'project','internal','CONTRIBUTOR','Intervenant',1),(170,'project','external','PROJECTLEADER','Chef de Projet',1),(171,'project','external','CONTRIBUTOR','Intervenant',1),(180,'project_task','internal','TASKEXECUTIVE','Responsable',1),(181,'project_task','internal','CONTRIBUTOR','Intervenant',1),(190,'project_task','external','TASKEXECUTIVE','Responsable',1),(191,'project_task','external','CONTRIBUTOR','Intervenant',1);
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_type_fees`
--

LOCK TABLES `llx_c_type_fees` WRITE;
/*!40000 ALTER TABLE `llx_c_type_fees` DISABLE KEYS */;
INSERT INTO `llx_c_type_fees` VALUES (1,'TF_OTHER','Other',1),(2,'TF_TRIP','Trip',1),(3,'TF_LUNCH','Lunch',1);
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_c_typent`
--

LOCK TABLES `llx_c_typent` WRITE;
/*!40000 ALTER TABLE `llx_c_typent` DISABLE KEYS */;
INSERT INTO `llx_c_typent` VALUES (0,'TE_UNKNOWN','-',1),(1,'TE_STARTUP','Start-up',0),(2,'TE_GROUP','Grand groupe',1),(3,'TE_MEDIUM','PME/PMI',1),(4,'TE_SMALL','TPE',1),(5,'TE_ADMIN','Administration',1),(6,'TE_WHOLE','Grossiste',0),(7,'TE_RETAIL','Revendeur',0),(8,'TE_PRIVATE','Particulier',1),(100,'TE_OTHER','Autres',1);
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
  `fk_county` int(11) NOT NULL,
  `zip` varchar(10) NOT NULL,
  `town` varchar(255) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`),
  KEY `idx_c_ziptown_fk_county` (`fk_county`),
  CONSTRAINT `fk_c_ziptown_fk_county` FOREIGN KEY (`fk_county`) REFERENCES `llx_c_departements` (`rowid`)
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
  `label` varchar(255) DEFAULT NULL,
  `type` tinyint(4) NOT NULL DEFAULT '1',
  `entity` int(11) NOT NULL DEFAULT '1',
  `description` text,
  `fk_soc` int(11) DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_categorie_ref` (`label`,`type`,`entity`),
  KEY `idx_categorie_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie`
--

LOCK TABLES `llx_categorie` WRITE;
/*!40000 ALTER TABLE `llx_categorie` DISABLE KEYS */;
INSERT INTO `llx_categorie` VALUES (1,'MySupplierCategory',1,1,'This is description of category MyCategory for suppliers<br />',NULL,0,NULL),(2,'MyCategory',2,1,'This is description of MyCategory for customer and prospects<br />',NULL,0,NULL),(3,'Hot products',0,1,'This is description of hot products<br />',NULL,0,NULL),(4,'Cold products',0,1,'This is a description of cold products<br />',NULL,0,NULL),(5,'Sub category hot',0,1,'<br />',NULL,0,NULL),(6,'New products',0,1,'<br />',NULL,0,NULL),(7,'Homme',3,1,'<br />',NULL,0,NULL),(8,'Femme',3,1,'<br />',NULL,0,NULL),(9,'Forfait moyen',1,1,'<br />',NULL,0,NULL),(10,'XL Cutomers',2,1,'<br />',NULL,0,NULL);
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
  UNIQUE KEY `uk_categorie_association_fk_categorie_fille` (`fk_categorie_fille`),
  CONSTRAINT `fk_categorie_asso_fk_categorie_fille` FOREIGN KEY (`fk_categorie_fille`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_asso_fk_categorie_mere` FOREIGN KEY (`fk_categorie_mere`) REFERENCES `llx_categorie` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie_association`
--

LOCK TABLES `llx_categorie_association` WRITE;
/*!40000 ALTER TABLE `llx_categorie_association` DISABLE KEYS */;
INSERT INTO `llx_categorie_association` VALUES (3,5);
/*!40000 ALTER TABLE `llx_categorie_association` ENABLE KEYS */;
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
  UNIQUE KEY `fk_categorie` (`fk_categorie`,`fk_societe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_categorie_fournisseur`
--

LOCK TABLES `llx_categorie_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_categorie_fournisseur` DISABLE KEYS */;
INSERT INTO `llx_categorie_fournisseur` VALUES (1,2),(9,2);
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
INSERT INTO `llx_categorie_product` VALUES (5,1),(5,2),(5,3),(6,2),(6,3);
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
INSERT INTO `llx_categorie_societe` VALUES (2,2);
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
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_chargesociales`
--

LOCK TABLES `llx_chargesociales` WRITE;
/*!40000 ALTER TABLE `llx_chargesociales` DISABLE KEYS */;
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
  `fk_projet` int(11) DEFAULT '0',
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(30) DEFAULT NULL,
  `ref_client` varchar(30) DEFAULT NULL,
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
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  `facture` tinyint(4) DEFAULT '0',
  `fk_cond_reglement` int(11) DEFAULT NULL,
  `fk_mode_reglement` int(11) DEFAULT NULL,
  `date_livraison` date DEFAULT NULL,
  `fk_adresse_livraison` int(11) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_commande_ref` (`ref`,`entity`),
  KEY `idx_commande_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_commande_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande`
--

LOCK TABLES `llx_commande` WRITE;
/*!40000 ALTER TABLE `llx_commande` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_commande` ENABLE KEYS */;
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
  `ref_supplier` varchar(30) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT '0',
  `date_creation` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `date_commande` date DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_user_cloture` int(11) DEFAULT NULL,
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
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  `fk_methode_commande` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_commande_fournisseur_ref` (`ref`,`fk_soc`,`entity`),
  KEY `idx_commande_fournisseur_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_commande_fournisseur_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande_fournisseur`
--

LOCK TABLES `llx_commande_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseur` VALUES (1,'2010-07-11 15:19:36',13,'CF1007-0001',1,NULL,0,'2010-07-11 17:13:40','2010-07-11 17:15:42',NULL,'2010-07-11',1,1,NULL,0,5,0,0,0,39.20000000,0.00000000,0.00000000,200.00000000,239.20000000,NULL,NULL,'muscadet',2),(2,'2010-07-11 16:48:00',1,'CF1007-0002',1,NULL,0,'2010-07-11 18:46:28','2010-07-11 18:47:33',NULL,'2010-07-11',1,1,NULL,0,3,0,0,0,0.00000000,0.00000000,0.00000000,200.00000000,200.00000000,NULL,NULL,'muscadet',4);
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande_fournisseur_log`
--

LOCK TABLES `llx_commande_fournisseur_log` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseur_log` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseur_log` VALUES (1,'2010-07-11 15:13:40','2010-07-11 17:13:40',1,0,1,NULL),(2,'2010-07-11 15:15:42','2010-07-11 17:15:42',1,1,1,NULL),(3,'2010-07-11 15:16:28','2010-07-11 17:16:28',1,2,1,NULL),(4,'2010-07-11 15:19:14','2010-07-11 00:00:00',1,3,1,NULL),(5,'2010-07-11 15:19:36','2010-07-11 00:00:00',1,5,1,NULL),(6,'2010-07-11 16:46:28','2010-07-11 18:46:28',2,0,1,NULL),(7,'2010-07-11 16:47:33','2010-07-11 18:47:33',2,1,1,NULL),(8,'2010-07-11 16:47:41','2010-07-11 18:47:41',2,2,1,NULL),(9,'2010-07-11 16:48:00','2010-07-11 00:00:00',2,3,1,NULL);
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
  `localtax2_tx` double(6,3) DEFAULT '0.000',
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
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commande_fournisseurdet`
--

LOCK TABLES `llx_commande_fournisseurdet` WRITE;
/*!40000 ALTER TABLE `llx_commande_fournisseurdet` DISABLE KEYS */;
INSERT INTO `llx_commande_fournisseurdet` VALUES (1,1,NULL,'','','Chips',19.600,0.000,0.000,10,0,0,20.00000000,200.00000000,39.20000000,0.00000000,0.00000000,239.20000000,0,NULL,NULL,0),(2,2,4,'ABCD','Decapsuleur','',0.000,0.000,0.000,20,0,0,10.00000000,200.00000000,0.00000000,0.00000000,0.00000000,200.00000000,0,NULL,NULL,0);
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
  `description` text,
  `tva_tx` double(6,3) DEFAULT NULL,
  `localtax1_tx` double(6,3) DEFAULT NULL,
  `localtax2_tx` double(6,3) DEFAULT NULL,
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
  `marge_tx` double(6,3) DEFAULT '0.000',
  `marque_tx` double(6,3) DEFAULT '0.000',
  `special_code` int(10) unsigned DEFAULT '0',
  `rang` int(11) DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_commandedet_fk_commande` (`fk_commande`),
  CONSTRAINT `fk_commandedet_fk_commande` FOREIGN KEY (`fk_commande`) REFERENCES `llx_commande` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_commandedet`
--

LOCK TABLES `llx_commandedet` WRITE;
/*!40000 ALTER TABLE `llx_commandedet` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_commandedet` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=804 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_const`
--

LOCK TABLES `llx_const` WRITE;
/*!40000 ALTER TABLE `llx_const` DISABLE KEYS */;
INSERT INTO `llx_const` VALUES (2,'MAIN_FEATURES_LEVEL',0,'0','chaine',1,'Level of features to show (0=stable only, 1=stable+experimental, 2=stable+experimental+development','2010-07-08 11:17:57'),(3,'MAIN_POPUP_CALENDAR',0,'eldy','chaine',0,'Popup calendar module','2010-07-08 11:17:57'),(4,'SYSLOG_FILE',0,'DOL_DATA_ROOT/dolibarr.log','chaine',0,'Directory where to write log file','2010-07-08 11:17:57'),(5,'SYSLOG_LEVEL',0,'7','chaine',0,'Level of debug info to show','2010-07-08 11:17:57'),(6,'MAIN_MAIL_SMTP_SERVER',0,'','chaine',0,'Host or ip address for SMTP server','2010-07-08 11:17:57'),(7,'MAIN_MAIL_SMTP_PORT',0,'','chaine',0,'Port for SMTP server','2010-07-08 11:17:57'),(8,'MAIN_UPLOAD_DOC',0,'2048','chaine',0,'Max size for file upload (0 means no upload allowed)','2010-07-08 11:17:57'),(9,'MAIN_SEARCHFORM_SOCIETE',0,'1','yesno',0,'Show form for quick company search','2010-07-08 11:17:57'),(10,'MAIN_SEARCHFORM_CONTACT',0,'1','yesno',0,'Show form for quick contact search','2010-07-08 11:17:57'),(11,'MAIN_SEARCHFORM_PRODUITSERVICE',0,'1','yesno',0,'Show form for quick product search','2010-07-08 11:17:58'),(12,'MAIN_SEARCHFORM_ADHERENT',0,'1','yesno',0,'Show form for quick member search','2010-07-08 11:17:58'),(13,'MAIN_CONFIRM_AJAX',0,'1','chaine',0,'Use Ajax popup to make confirmations','2010-07-08 11:17:58'),(15,'MAIN_MAIL_EMAIL_FROM',1,'dolibarr-robot@domain.com','chaine',0,'EMail emetteur pour les emails automatiques Dolibarr','2010-07-08 11:17:58'),(16,'MAIN_SIZE_LISTE_LIMIT',0,'25','chaine',0,'Longueur maximum des listes','2010-07-08 11:17:58'),(17,'MAIN_SHOW_WORKBOARD',0,'1','yesno',0,'Affichage tableau de bord de travail Dolibarr','2010-07-08 11:17:58'),(23,'MAIN_DELAY_ACTIONS_TODO',1,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur actions planifiées non réalisées','2010-07-08 11:17:58'),(24,'MAIN_DELAY_ORDERS_TO_PROCESS',1,'2','chaine',0,'Tolérance de retard avant alerte (en jours) sur commandes non traitées','2010-07-08 11:17:58'),(25,'MAIN_DELAY_PROPALS_TO_CLOSE',1,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur propales à cloturer','2010-07-08 11:17:58'),(26,'MAIN_DELAY_PROPALS_TO_BILL',1,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur propales non facturées','2010-07-08 11:17:58'),(27,'MAIN_DELAY_SUPPLIER_BILLS_TO_PAY',1,'2','chaine',0,'Tolérance de retard avant alerte (en jours) sur factures fournisseur impayées','2010-07-08 11:17:58'),(28,'MAIN_DELAY_CUSTOMER_BILLS_UNPAYED',1,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur factures client impayées','2010-07-08 11:17:58'),(29,'MAIN_DELAY_NOT_ACTIVATED_SERVICES',1,'0','chaine',0,'Tolérance de retard avant alerte (en jours) sur services à activer','2010-07-08 11:17:58'),(30,'MAIN_DELAY_RUNNING_SERVICES',1,'0','chaine',0,'Tolérance de retard avant alerte (en jours) sur services expirés','2010-07-08 11:17:58'),(31,'MAIN_DELAY_MEMBERS',1,'31','chaine',0,'Tolérance de retard avant alerte (en jours) sur cotisations adhérent en retard','2010-07-08 11:17:58'),(32,'MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE',1,'62','chaine',0,'Tolérance de retard avant alerte (en jours) sur rapprochements bancaires à faire','2010-07-08 11:17:58'),(33,'SOCIETE_NOLIST_COURRIER',0,'1','yesno',0,'Liste les fichiers du repertoire courrier','2010-07-08 11:17:58'),(34,'SOCIETE_CODECLIENT_ADDON',1,'mod_codeclient_leopard','yesno',0,'Module to control third parties codes','2010-07-08 11:17:58'),(35,'SOCIETE_CODECOMPTA_ADDON',1,'mod_codecompta_panicum','yesno',0,'Module to control third parties codes','2010-07-08 11:17:58'),(36,'ADHERENT_MAIL_REQUIRED',1,'1','yesno',0,'EMail required to create a new member','2010-07-08 11:17:58'),(37,'ADHERENT_MAIL_FROM',1,'adherents@domain.com','chaine',0,'Sender EMail for automatic emails','2010-07-08 11:17:58'),(38,'ADHERENT_MAIL_RESIL',1,'Your subscription has been resiliated.\r\nWe hope to see you soon again','texte',0,'Mail resiliation','2010-07-08 11:17:58'),(39,'ADHERENT_MAIL_VALID',1,'Your subscription has been validated.\r\nThis is a remind of your personal information :\r\n\r\n%INFOS%\r\n\r\n','texte',0,'Mail de validation','2010-07-08 11:17:59'),(40,'ADHERENT_MAIL_COTIS',1,'Hello %PRENOM%,\r\nThanks for your subscription.\r\nThis email confirms that your subscription has been received and processed.\r\n\r\n','texte',0,'Mail de validation de cotisation','2010-07-08 11:17:59'),(41,'ADHERENT_MAIL_VALID_SUBJECT',1,'Your subscription has been validated','chaine',0,'Sujet du mail de validation','2010-07-08 11:17:59'),(42,'ADHERENT_MAIL_RESIL_SUBJECT',1,'Resiliating your subscription','chaine',0,'Sujet du mail de resiliation','2010-07-08 11:17:59'),(43,'ADHERENT_MAIL_COTIS_SUBJECT',1,'Receipt of your subscription','chaine',0,'Sujet du mail de validation de cotisation','2010-07-08 11:17:59'),(44,'MAILING_EMAIL_FROM',1,'dolibarr@domain.com','chaine',0,'EMail emmetteur pour les envois d emailings','2010-07-08 11:17:59'),(45,'ADHERENT_USE_MAILMAN',1,'0','yesno',0,'Utilisation de Mailman','2010-07-08 11:17:59'),(46,'ADHERENT_MAILMAN_UNSUB_URL',1,'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&user=%EMAIL%','chaine',0,'Url de desinscription aux listes mailman','2010-07-08 11:17:59'),(47,'ADHERENT_MAILMAN_URL',1,'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%','chaine',0,'Url pour les inscriptions mailman','2010-07-08 11:17:59'),(48,'ADHERENT_MAILMAN_LISTS',1,'test-test,test-test2','chaine',0,'Listes auxquelles inscrire les nouveaux adherents','2010-07-08 11:17:59'),(49,'ADHERENT_MAILMAN_ADMINPW',1,'','chaine',0,'Mot de passe Admin des liste mailman','2010-07-08 11:17:59'),(50,'ADHERENT_MAILMAN_SERVER',1,'lists.domain.com','chaine',0,'Serveur hebergeant les interfaces d Admin des listes mailman','2010-07-08 11:17:59'),(51,'ADHERENT_MAILMAN_LISTS_COTISANT',1,'','chaine',0,'Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement','2010-07-08 11:17:59'),(52,'ADHERENT_USE_SPIP',1,'0','yesno',0,'Utilisation de SPIP ?','2010-07-08 11:17:59'),(53,'ADHERENT_USE_SPIP_AUTO',1,'0','yesno',0,'Utilisation de SPIP automatiquement','2010-07-08 11:17:59'),(54,'ADHERENT_SPIP_USER',1,'user','chaine',0,'user spip','2010-07-08 11:17:59'),(55,'ADHERENT_SPIP_PASS',1,'pass','chaine',0,'Pass de connection','2010-07-08 11:17:59'),(56,'ADHERENT_SPIP_SERVEUR',1,'localhost','chaine',0,'serveur spip','2010-07-08 11:17:59'),(57,'ADHERENT_SPIP_DB',1,'spip','chaine',0,'db spip','2010-07-08 11:17:59'),(58,'ADHERENT_CARD_HEADER_TEXT',1,'%ANNEE%','chaine',0,'Texte imprime sur le haut de la carte adherent','2010-07-08 11:17:59'),(59,'ADHERENT_CARD_FOOTER_TEXT',1,'Association AZERTY','chaine',0,'Texte imprime sur le bas de la carte adherent','2010-07-08 11:17:59'),(61,'FCKEDITOR_ENABLE_USER',1,'1','yesno',0,'Activation fckeditor sur notes utilisateurs','2010-07-08 11:17:59'),(62,'FCKEDITOR_ENABLE_SOCIETE',1,'1','yesno',0,'Activation fckeditor sur notes societe','2010-07-08 11:17:59'),(63,'FCKEDITOR_ENABLE_PRODUCTDESC',1,'1','yesno',0,'Activation fckeditor sur notes produits','2010-07-08 11:17:59'),(64,'FCKEDITOR_ENABLE_MEMBER',1,'1','yesno',0,'Activation fckeditor sur notes adherent','2010-07-08 11:17:59'),(65,'FCKEDITOR_ENABLE_MAILING',1,'1','yesno',0,'Activation fckeditor sur emailing','2010-07-08 11:17:59'),(66,'OSC_DB_HOST',1,'localhost','chaine',0,'Host for OSC database for OSCommerce module 1','2010-07-08 11:17:59'),(67,'DON_ADDON_MODEL',1,'html_cerfafr','chaine',0,'','2010-07-08 11:18:00'),(68,'PROPALE_ADDON',1,'mod_propale_marbre','chaine',0,'','2010-07-08 11:18:00'),(69,'PROPALE_ADDON_PDF',1,'azur','chaine',0,'','2010-07-08 11:18:00'),(70,'COMMANDE_ADDON',1,'mod_commande_marbre','chaine',0,'','2010-07-08 11:18:00'),(71,'COMMANDE_ADDON_PDF',1,'einstein','chaine',0,'','2010-07-08 11:18:00'),(72,'COMMANDE_SUPPLIER_ADDON',1,'mod_commande_fournisseur_muguet','chaine',0,'','2010-07-08 11:18:00'),(73,'COMMANDE_SUPPLIER_ADDON_PDF',1,'muscadet','chaine',0,'','2010-07-08 11:18:00'),(74,'EXPEDITION_ADDON',1,'enlevement','chaine',0,'','2010-07-08 11:18:00'),(76,'FICHEINTER_ADDON',1,'pacific','chaine',0,'','2010-07-08 11:18:00'),(77,'FICHEINTER_ADDON_PDF',1,'soleil','chaine',0,'','2010-07-08 11:18:00'),(79,'FACTURE_ADDON_PDF',1,'crabe','chaine',0,'','2010-07-08 11:18:00'),(80,'PROPALE_VALIDITY_DURATION',1,'15','chaine',0,'Durée de validitée des propales','2010-07-08 11:18:00'),(230,'COMPANY_ADDON_PDF_ODT_PATH',1,'DOL_DATA_ROOT/doctemplates/thirdparties','chaine',0,NULL,'2010-07-08 11:26:20'),(238,'LIVRAISON_ADDON_PDF',1,'typhon','chaine',0,'Nom du gestionnaire de generation des commandes en PDF','2010-07-08 11:26:27'),(239,'LIVRAISON_ADDON',1,'mod_livraison_jade','chaine',0,'Nom du gestionnaire de numerotation des bons de livraison','2010-07-08 11:26:27'),(242,'MAIN_SUBMODULE_EXPEDITION',1,'1','chaine',0,'','2010-07-08 11:26:34'),(245,'FACTURE_ADDON_PDF_ODT_PATH',1,'DOL_DATA_ROOT/doctemplates/invoices','chaine',0,NULL,'2010-07-08 11:28:53'),(249,'DON_FORM',1,'fsfe.fr.php','chaine',0,'Nom du gestionnaire de formulaire de dons','2010-07-08 11:29:00'),(252,'MAIN_MODULE_ADHERENT',1,'1',NULL,0,NULL,'2010-07-08 11:29:05'),(253,'ADHERENT_BANK_USE_AUTO',1,'','yesno',0,'Insertion automatique des cotisation dans le compte banquaire','2010-07-08 11:29:05'),(254,'ADHERENT_BANK_ACCOUNT',1,'','chaine',0,'ID du Compte banquaire utilise','2010-07-08 11:29:05'),(255,'ADHERENT_BANK_CATEGORIE',1,'','chaine',0,'ID de la categorie banquaire des cotisations','2010-07-08 11:29:05'),(256,'ADHERENT_ETIQUETTE_TYPE',1,'L7163','chaine',0,'Type d etiquette (pour impression de planche d etiquette)','2010-07-08 11:29:05'),(260,'MAIN_MODULE_STOCK',1,'1',NULL,0,NULL,'2010-07-08 11:29:18'),(269,'PROJECT_ADDON_PDF',1,'baleine','chaine',0,'Nom du gestionnaire de generation des projets en PDF','2010-07-08 11:29:33'),(270,'PROJECT_ADDON',1,'mod_project_simple','chaine',0,'Nom du gestionnaire de numerotation des projets','2010-07-08 11:29:33'),(271,'MAIN_MODULE_MAILING',1,'1',NULL,0,NULL,'2010-07-08 11:29:37'),(272,'MAIN_MODULE_EXPORT',1,'1',NULL,0,NULL,'2010-07-08 11:29:41'),(273,'MAIN_MODULE_IMPORT',1,'1',NULL,0,NULL,'2010-07-08 11:29:45'),(274,'MAIN_MODULE_CATEGORIE',1,'1',NULL,0,NULL,'2010-07-08 11:29:59'),(275,'MAIN_MODULE_BOOKMARK',1,'1',NULL,0,NULL,'2010-07-08 11:30:03'),(276,'MAIN_MODULE_WEBSERVICES',1,'1',NULL,0,NULL,'2010-07-08 11:30:30'),(278,'MAIN_MODULE_GEOIPMAXMIND',1,'1',NULL,0,NULL,'2010-07-08 11:30:36'),(279,'MAIN_MODULE_EXTERNALRSS',1,'1',NULL,0,NULL,'2010-07-08 11:30:38'),(280,'MAIN_SECURITY_DISABLEFORGETPASSLINK',1,'1','chaine',0,'','2010-07-08 11:32:59'),(292,'MAIN_MODULE_FCKEDITOR',1,'1',NULL,0,NULL,'2010-07-08 11:56:27'),(328,'MAIN_REMOVE_INSTALL_WARNING',1,'1','',1,'','2010-07-08 12:06:35'),(341,'MAIN_INFO_SOCIETE_LOGO',1,'dolibarr_logo1.png','chaine',0,'','2010-07-08 12:07:45'),(342,'MAIN_INFO_SOCIETE_LOGO_SMALL',1,'dolibarr_logo1_small.png','chaine',0,'','2010-07-08 12:07:45'),(343,'MAIN_INFO_SOCIETE_LOGO_MINI',1,'dolibarr_logo1_mini.png','chaine',0,'','2010-07-08 12:07:46'),(368,'STOCK_USERSTOCK_AUTOCREATE',1,'1','chaine',0,'','2010-07-08 22:44:59'),(369,'EXPEDITION_ADDON_PDF',1,'merou','chaine',0,'','2010-07-08 22:58:07'),(370,'MAIN_SUBMODULE_LIVRAISON',1,'1','chaine',0,'','2010-07-08 23:00:29'),(377,'FACTURE_ADDON',1,'mod_facture_terre','chaine',0,'','2010-07-08 23:08:12'),(380,'ADHERENT_CARD_TEXT',1,'%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','',0,'Texte imprime sur la carte adherent','2010-07-08 23:14:46'),(381,'ADHERENT_CARD_TEXT_RIGHT',1,'aaa','',0,'','2010-07-08 23:14:55'),(384,'PRODUIT_SOUSPRODUITS',1,'1','chaine',0,'','2010-07-08 23:22:12'),(385,'PRODUIT_USE_SEARCH_TO_SELECT',1,'1','chaine',0,'','2010-07-08 23:22:19'),(386,'STOCK_CALCULATE_ON_SHIPMENT',1,'1','chaine',0,'','2010-07-08 23:23:21'),(387,'STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER',1,'1','chaine',0,'','2010-07-08 23:23:26'),(392,'MAIN_AGENDA_XCAL_EXPORTKEY',1,'dolibarr','chaine',0,'','2010-07-08 23:27:50'),(393,'MAIN_AGENDA_EXPORT_PAST_DELAY',1,'100','chaine',0,'','2010-07-08 23:27:50'),(523,'MAIN_AGENDA_ACTIONAUTO_COMPANY_CREATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(524,'MAIN_AGENDA_ACTIONAUTO_CONTRACT_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(525,'MAIN_AGENDA_ACTIONAUTO_PROPAL_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(526,'MAIN_AGENDA_ACTIONAUTO_PROPAL_SENTBYMAIL',1,'1','chaine',0,'','2010-07-10 12:48:49'),(527,'MAIN_AGENDA_ACTIONAUTO_ORDER_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(528,'MAIN_AGENDA_ACTIONAUTO_ORDER_SENTBYMAIL',1,'1','chaine',0,'','2010-07-10 12:48:49'),(529,'MAIN_AGENDA_ACTIONAUTO_BILL_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:49'),(530,'MAIN_AGENDA_ACTIONAUTO_BILL_PAYED',1,'1','chaine',0,'','2010-07-10 12:48:49'),(531,'MAIN_AGENDA_ACTIONAUTO_BILL_CANCEL',1,'1','chaine',0,'','2010-07-10 12:48:49'),(532,'MAIN_AGENDA_ACTIONAUTO_BILL_SENTBYMAIL',1,'1','chaine',0,'','2010-07-10 12:48:49'),(533,'MAIN_AGENDA_ACTIONAUTO_ORDER_SUPPLIER_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:50'),(534,'MAIN_AGENDA_ACTIONAUTO_BILL_SUPPLIER_VALIDATE',1,'1','chaine',0,'','2010-07-10 12:48:50'),(536,'MAIN_VERSION_LAST_INSTALL',0,'2.9.0-beta','chaine',0,'Dolibarr version when install','2010-07-10 12:49:23'),(538,'MAIN_MODULE_COMPTABILITE',1,'1',NULL,0,NULL,'2010-07-10 13:38:15'),(542,'MAIN_MODULE_TAX',1,'1',NULL,0,NULL,'2010-07-10 13:38:20'),(549,'MAIN_MODULE_CONTRAT',1,'1',NULL,0,NULL,'2010-07-10 16:12:46'),(580,'MAIN_MENU_BARRETOP',1,'eldy_backoffice.php','chaine',0,'','2010-07-10 23:21:08'),(581,'MAIN_MENU_BARRELEFT',1,'eldy_backoffice.php','chaine',0,'','2010-07-10 23:21:09'),(582,'MAIN_MENUFRONT_BARRETOP',1,'eldy_frontoffice.php','chaine',0,'','2010-07-10 23:21:09'),(583,'MAIN_MENUFRONT_BARRELEFT',1,'eldy_frontoffice.php','chaine',0,'','2010-07-10 23:21:09'),(602,'MAIN_MODULE_PROJET',1,'1',NULL,0,NULL,'2010-07-11 13:26:54'),(603,'MAIN_MODULE_FICHEINTER',1,'1',NULL,0,NULL,'2010-07-11 14:06:52'),(605,'MAIN_MODULE_CASHDESK',1,'1',NULL,0,NULL,'2010-07-11 17:08:03'),(606,'MAIN_MODULE_BANQUE',1,'1',NULL,0,NULL,'2010-07-11 17:08:03'),(609,'MAIN_MODULE_PRODUCT',1,'1',NULL,0,NULL,'2010-07-11 17:08:03'),(610,'CASHDESK_ID_THIRDPARTY',1,'7','chaine',0,'','2010-07-11 17:08:18'),(611,'CASHDESK_ID_BANKACCOUNT_CASH',1,'3','chaine',0,'','2010-07-11 17:08:18'),(612,'CASHDESK_ID_BANKACCOUNT_CHEQUE',1,'1','chaine',0,'','2010-07-11 17:08:18'),(613,'CASHDESK_ID_BANKACCOUNT_CB',1,'1','chaine',0,'','2010-07-11 17:08:18'),(614,'CASHDESK_ID_WAREHOUSE',1,'2','chaine',0,'','2010-07-11 17:08:18'),(649,'MAIN_MODULE_PROPALE',1,'1',NULL,0,NULL,'2010-07-18 09:34:11'),(653,'MAIN_MODULE_EXPEDITION',1,'1',NULL,0,NULL,'2010-07-18 09:34:14'),(660,'LDAP_USER_DN',1,'ou=users,dc=my-domain,dc=com','chaine',0,NULL,'2010-07-18 10:25:27'),(661,'LDAP_GROUP_DN',1,'ou=groups,dc=my-domain,dc=com','chaine',0,NULL,'2010-07-18 10:25:27'),(662,'LDAP_FILTER_CONNECTION',1,'&(objectClass=user)(objectCategory=person)','chaine',0,NULL,'2010-07-18 10:25:27'),(663,'LDAP_FIELD_LOGIN',1,'uid','chaine',0,NULL,'2010-07-18 10:25:27'),(664,'LDAP_FIELD_FULLNAME',1,'cn','chaine',0,NULL,'2010-07-18 10:25:27'),(665,'LDAP_FIELD_NAME',1,'sn','chaine',0,NULL,'2010-07-18 10:25:27'),(666,'LDAP_FIELD_FIRSTNAME',1,'givenname','chaine',0,NULL,'2010-07-18 10:25:27'),(667,'LDAP_FIELD_MAIL',1,'mail','chaine',0,NULL,'2010-07-18 10:25:27'),(668,'LDAP_FIELD_PHONE',1,'telephonenumber','chaine',0,NULL,'2010-07-18 10:25:27'),(669,'LDAP_FIELD_FAX',1,'facsimiletelephonenumber','chaine',0,NULL,'2010-07-18 10:25:27'),(670,'LDAP_FIELD_MOBILE',1,'mobile','chaine',0,NULL,'2010-07-18 10:25:27'),(671,'LDAP_SERVER_TYPE',1,'openldap','chaine',0,'','2010-07-18 10:25:46'),(672,'LDAP_SERVER_PROTOCOLVERSION',1,'3','chaine',0,'','2010-07-18 10:25:47'),(673,'LDAP_SERVER_HOST',1,'localhost','chaine',0,'','2010-07-18 10:25:47'),(674,'LDAP_SERVER_PORT',1,'389','chaine',0,'','2010-07-18 10:25:47'),(675,'LDAP_SERVER_USE_TLS',1,'0','chaine',0,'','2010-07-18 10:25:47'),(676,'LDAP_SYNCHRO_ACTIVE',1,'dolibarr2ldap','chaine',0,'','2010-07-18 10:25:47'),(677,'LDAP_CONTACT_ACTIVE',1,'1','chaine',0,'','2010-07-18 10:25:47'),(678,'LDAP_MEMBER_ACTIVE',1,'1','chaine',0,'','2010-07-18 10:25:47'),(691,'INVOICE_SUPPLIER_ADDON_PDF',1,'canelle','chaine',0,'','2011-02-06 11:18:27'),(692,'MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS',1,'7','chaine',0,'Tolérance de retard avant alerte (en jours) sur commandes fournisseurs non traitées','2011-02-06 11:18:28'),(708,'MAIN_MODULE_AGENDA',1,'1',NULL,0,NULL,'2011-02-06 11:20:36'),(709,'MAIN_MODULE_SOCIETE',1,'1',NULL,0,NULL,'2011-02-06 11:20:36'),(710,'MAIN_MODULE_SERVICE',1,'1',NULL,0,NULL,'2011-02-06 11:20:36'),(711,'MAIN_MODULE_COMMANDE',1,'1',NULL,0,NULL,'2011-02-06 11:20:36'),(712,'MAIN_MODULE_FACTURE',1,'1',NULL,0,NULL,'2011-02-06 11:20:36'),(713,'MAIN_MODULE_FOURNISSEUR',1,'1',NULL,0,NULL,'2011-02-06 11:20:36'),(714,'MAIN_MODULE_USER',0,'1',NULL,0,NULL,'2011-02-06 11:20:36'),(715,'MAIN_MODULE_DEPLACEMENT',1,'1',NULL,0,NULL,'2011-02-06 11:20:36'),(716,'MAIN_MODULE_DON',1,'1',NULL,0,NULL,'2011-02-06 11:20:36'),(717,'MAIN_MODULE_ECM',1,'1',NULL,0,NULL,'2011-02-06 11:20:36'),(718,'MAIN_MODULE_PAYBOX',1,'1',NULL,0,NULL,'2011-02-06 11:20:36'),(719,'MAIN_VERSION_LAST_UPGRADE',0,'3.0.0-beta','chaine',0,'Dolibarr version for last upgrade','2011-02-06 11:20:38'),(721,'MAIN_LOGEVENTS_USER_LOGIN',1,'1','chaine',0,'','2011-02-06 11:26:53'),(722,'MAIN_LOGEVENTS_USER_LOGIN_FAILED',1,'1','chaine',0,'','2011-02-06 11:26:53'),(723,'MAIN_LOGEVENTS_USER_LOGOUT',1,'1','chaine',0,'','2011-02-06 11:26:53'),(724,'MAIN_LOGEVENTS_USER_CREATE',1,'1','chaine',0,'','2011-02-06 11:26:53'),(725,'MAIN_LOGEVENTS_USER_MODIFY',1,'1','chaine',0,'','2011-02-06 11:26:53'),(726,'MAIN_LOGEVENTS_USER_NEW_PASSWORD',1,'1','chaine',0,'','2011-02-06 11:26:53'),(727,'MAIN_LOGEVENTS_USER_ENABLEDISABLE',1,'1','chaine',0,'','2011-02-06 11:26:53'),(728,'MAIN_LOGEVENTS_USER_DELETE',1,'1','chaine',0,'','2011-02-06 11:26:53'),(729,'MAIN_LOGEVENTS_GROUP_CREATE',1,'1','chaine',0,'','2011-02-06 11:26:53'),(730,'MAIN_LOGEVENTS_GROUP_MODIFY',1,'1','chaine',0,'','2011-02-06 11:26:53'),(731,'MAIN_LOGEVENTS_GROUP_DELETE',1,'1','chaine',0,'','2011-02-06 11:26:53'),(765,'MAIN_INFO_SOCIETE_PAYS',1,'7:GB:Royaume-Uni','chaine',0,'','2011-02-06 11:27:46'),(766,'MAIN_INFO_SOCIETE_NOM',1,'MyBigCompany','chaine',0,'','2011-02-06 11:27:46'),(767,'MAIN_INFO_SOCIETE_ADRESSE',1,'21 Jump street','chaine',0,'','2011-02-06 11:27:46'),(768,'MAIN_INFO_SOCIETE_VILLE',1,'MyTown','chaine',0,'','2011-02-06 11:27:46'),(769,'MAIN_INFO_SOCIETE_CP',1,'75500','chaine',0,'','2011-02-06 11:27:46'),(770,'MAIN_INFO_SOCIETE_DEPARTEMENT',1,'0','chaine',0,'','2011-02-06 11:27:46'),(771,'MAIN_MONNAIE',1,'EUR','chaine',0,'','2011-02-06 11:27:46'),(772,'MAIN_INFO_SOCIETE_TEL',1,'09123123','chaine',0,'','2011-02-06 11:27:46'),(773,'MAIN_INFO_SOCIETE_FAX',1,'09123124','chaine',0,'','2011-02-06 11:27:46'),(774,'MAIN_INFO_SOCIETE_MAIL',1,'myemail@mybigcompany.com','chaine',0,'','2011-02-06 11:27:46'),(775,'MAIN_INFO_SOCIETE_WEB',1,'http://www.dolibarr.org','chaine',0,'','2011-02-06 11:27:47'),(776,'MAIN_INFO_SOCIETE_NOTE',1,'This is note about my company','chaine',0,'','2011-02-06 11:27:47'),(777,'MAIN_INFO_CAPITAL',1,'10000','chaine',0,'','2011-02-06 11:27:47'),(778,'MAIN_INFO_SOCIETE_FORME_JURIDIQUE',1,'702','chaine',0,'','2011-02-06 11:27:47'),(779,'MAIN_INFO_TVAINTRA',1,'IN1234567','chaine',0,'','2011-02-06 11:27:47'),(780,'SOCIETE_FISCAL_MONTH_START',1,'0','chaine',0,'','2011-02-06 11:27:47'),(781,'FACTURE_TVAOPTION',1,'reel','chaine',0,'','2011-02-06 11:27:47'),(782,'MAIN_LANG_DEFAULT',1,'auto','chaine',0,'','2011-02-06 11:28:53'),(783,'MAIN_MULTILANGS',1,'1','chaine',0,'','2011-02-06 11:28:53'),(784,'MAIN_SIZE_LISTE_LIMIT',1,'25','chaine',0,'','2011-02-06 11:28:53'),(785,'MAIN_DISABLE_JAVASCRIPT',1,'0','chaine',0,'','2011-02-06 11:28:54'),(786,'MAIN_CONFIRM_AJAX',1,'1','chaine',0,'','2011-02-06 11:28:54'),(787,'MAIN_POPUP_CALENDAR',1,'eldy','chaine',0,'','2011-02-06 11:28:54'),(788,'MAIN_START_WEEK',1,'1','chaine',0,'','2011-02-06 11:28:54'),(789,'MAIN_SHOW_LOGO',1,'0','chaine',0,'','2011-02-06 11:28:54'),(790,'MAIN_FIRSTNAME_NAME_POSITION',1,'0','chaine',0,'','2011-02-06 11:28:54'),(791,'MAIN_THEME',1,'auguria','chaine',0,'','2011-02-06 11:28:54'),(792,'MAIN_SEARCHFORM_CONTACT',1,'1','chaine',0,'','2011-02-06 11:28:54'),(793,'MAIN_SEARCHFORM_SOCIETE',1,'1','chaine',0,'','2011-02-06 11:28:54'),(794,'MAIN_SEARCHFORM_PRODUITSERVICE',1,'1','chaine',0,'','2011-02-06 11:28:54'),(795,'MAIN_SEARCHFORM_ADHERENT',1,'1','chaine',0,'','2011-02-06 11:28:54'),(796,'MAIN_HELPCENTER_DISABLELINK',0,'1','chaine',0,'','2011-02-06 11:28:54'),(797,'MAIN_HOME',1,'__(NoteSomeFeaturesAreDisabled)__<br /><br />__(SomeTranslationAreUncomplete)__','chaine',0,'','2011-02-06 11:28:54'),(798,'MAIN_HELP_DISABLELINK',0,'0','chaine',0,'','2011-02-06 11:28:54'),(799,'MAIN_PROFID1_IN_ADDRESS',1,'0','chaine',0,'','2011-02-06 11:28:54'),(800,'MAIN_PROFID2_IN_ADDRESS',1,'0','chaine',0,'','2011-02-06 11:28:54'),(801,'MAIN_PROFID3_IN_ADDRESS',1,'0','chaine',0,'','2011-02-06 11:28:54'),(802,'MAIN_PROFID4_IN_ADDRESS',1,'0','chaine',0,'','2011-02-06 11:28:54'),(803,'SYSTEMTOOLS_MYSQLDUMP',1,'/usr/bin/mysqldump','chaine',0,'','2011-02-06 11:32:17');
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
  `note` text,
  `note_public` text,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_contrat_ref` (`ref`,`entity`),
  KEY `idx_contrat_fk_soc` (`fk_soc`),
  KEY `idx_contrat_fk_user_author` (`fk_user_author`),
  CONSTRAINT `fk_contrat_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_contrat_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_contrat`
--

LOCK TABLES `llx_contrat` WRITE;
/*!40000 ALTER TABLE `llx_contrat` DISABLE KEYS */;
INSERT INTO `llx_contrat` VALUES (1,'CONTRACT1',1,'2010-07-08 23:53:55','2010-07-09 01:53:25','2010-07-09 00:00:00',1,NULL,NULL,NULL,3,NULL,2,2,1,NULL,NULL,NULL,NULL),(2,'CONTRAT1',1,'2010-07-10 16:18:16','2010-07-10 18:13:37','2010-07-10 00:00:00',1,NULL,NULL,NULL,2,NULL,2,2,1,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_contrat` ENABLE KEYS */;
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
  `localtax2_tx` double(6,3) DEFAULT '0.000',
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
  `info_bits` int(11) DEFAULT '0',
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_contratdet`
--

LOCK TABLES `llx_contratdet` WRITE;
/*!40000 ALTER TABLE `llx_contratdet` DISABLE KEYS */;
INSERT INTO `llx_contratdet` VALUES (1,'2010-07-08 23:54:23',1,3,4,'','',NULL,NULL,'2010-07-09 00:00:00','2010-07-09 12:00:00','2011-07-01 12:00:00',NULL,0.000,0.000,0.000,1,0,0.00000000,0,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,0,1,1,''),(2,'2010-07-10 16:14:14',2,NULL,0,'','Abonnement annuel assurance',NULL,NULL,'2010-07-10 00:00:00',NULL,'2011-07-10 00:00:00',NULL,1.000,0.000,0.000,1,0,10.00000000,10,0,10.00000000,0.10000000,0.00000000,0.00000000,10.10000000,0,0,NULL,NULL,NULL),(3,'2010-07-10 16:18:45',2,3,4,'','',NULL,NULL,'2010-07-10 00:00:00','2010-07-10 12:00:00','2011-07-09 12:00:00',NULL,0.000,0.000,0.000,1,0,0.00000000,0,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,0,1,1,''),(4,'2010-07-10 16:18:08',2,3,0,'','',NULL,NULL,'2010-07-10 00:00:00',NULL,NULL,NULL,0.000,0.000,0.000,1,10,40.00000000,40,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,0,NULL,1,'');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_cotisation`
--

LOCK TABLES `llx_cotisation` WRITE;
/*!40000 ALTER TABLE `llx_cotisation` DISABLE KEYS */;
INSERT INTO `llx_cotisation` VALUES (1,'2010-07-10 13:05:30','2010-07-10 15:05:30',2,'2010-07-10 00:00:00','2011-07-10',20,NULL,'Adhésion/cotisation 2010'),(2,'2010-07-11 14:20:00','2010-07-11 16:20:00',2,'2011-07-11 00:00:00','2012-07-10',10,NULL,'Adhésion/cotisation 2011'),(3,'2010-07-18 10:20:33','2010-07-18 12:20:33',2,'2012-07-11 00:00:00','2013-07-17',10,NULL,'Adhésion/cotisation 2012');
/*!40000 ALTER TABLE `llx_cotisation` ENABLE KEYS */;
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
  `type` varchar(12) NOT NULL,
  `fk_statut` int(11) NOT NULL DEFAULT '1',
  `km` double DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT '0',
  `note` text,
  `note_public` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_deplacement`
--

LOCK TABLES `llx_deplacement` WRITE;
/*!40000 ALTER TABLE `llx_deplacement` DISABLE KEYS */;
INSERT INTO `llx_deplacement` VALUES (1,NULL,1,'2010-07-09 01:58:04','2010-07-08 23:58:18','2010-07-09 12:00:00',2,1,'TF_LUNCH',1,10,2,1,NULL,NULL);
/*!40000 ALTER TABLE `llx_deplacement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_document`
--

DROP TABLE IF EXISTS `llx_document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_document` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_extension` varchar(5) NOT NULL,
  `date_generation` datetime DEFAULT NULL,
  `fk_owner` int(11) DEFAULT NULL,
  `fk_group` int(11) DEFAULT NULL,
  `permissions` char(9) DEFAULT 'rw-rw-rw',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_document`
--

LOCK TABLES `llx_document` WRITE;
/*!40000 ALTER TABLE `llx_document` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_document` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_document_model`
--

LOCK TABLES `llx_document_model` WRITE;
/*!40000 ALTER TABLE `llx_document_model` DISABLE KEYS */;
INSERT INTO `llx_document_model` VALUES (9,'merou',1,'shipping',NULL,NULL),(15,'fsfe.fr.php',1,'donation',NULL,NULL),(21,'baleine',1,'project',NULL,NULL),(22,'soleil',1,'ficheinter',NULL,NULL),(24,'azur',1,'propal',NULL,NULL),(26,'rouget',1,'shipping',NULL,NULL),(27,'typhon',1,'delivery',NULL,NULL),(36,'einstein',1,'order',NULL,NULL),(37,'crabe',1,'invoice',NULL,NULL),(38,'muscadet',1,'order_supplier',NULL,NULL),(39,'html_cerfafr',1,'donation',NULL,NULL);
/*!40000 ALTER TABLE `llx_document_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_dolibarr_modules`
--

DROP TABLE IF EXISTS `llx_dolibarr_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_dolibarr_modules` (
  `numero` int(11) NOT NULL DEFAULT '0',
  `entity` int(11) NOT NULL DEFAULT '1',
  `active` tinyint(4) NOT NULL DEFAULT '0',
  `active_date` datetime NOT NULL,
  `active_version` varchar(25) NOT NULL,
  PRIMARY KEY (`numero`,`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_dolibarr_modules`
--

LOCK TABLES `llx_dolibarr_modules` WRITE;
/*!40000 ALTER TABLE `llx_dolibarr_modules` DISABLE KEYS */;
INSERT INTO `llx_dolibarr_modules` VALUES (0,1,1,'2011-02-06 12:20:36','dolibarr'),(1,1,1,'2011-02-06 12:20:36','dolibarr'),(10,1,1,'2010-07-10 15:38:15','dolibarr'),(20,1,1,'2010-07-18 11:34:11','dolibarr'),(22,1,1,'2010-07-08 13:29:37','dolibarr'),(25,1,1,'2011-02-06 12:20:36','dolibarr'),(30,1,1,'2011-02-06 12:20:36','dolibarr'),(40,1,1,'2011-02-06 12:20:36','dolibarr'),(50,1,1,'2010-07-11 19:08:03','dolibarr'),(52,1,1,'2010-07-08 13:29:18','dolibarr'),(53,1,1,'2011-02-06 12:20:36','dolibarr'),(54,1,1,'2010-07-10 18:12:46','dolibarr'),(70,1,1,'2010-07-11 16:06:52','dolibarr'),(75,1,1,'2011-02-06 12:20:36','dolibarr'),(80,1,1,'2010-07-18 11:34:14','dolibarr'),(85,1,1,'2010-07-11 19:08:03','dolibarr'),(240,1,1,'2010-07-08 13:29:41','dolibarr'),(250,1,1,'2010-07-08 13:29:45','dolibarr'),(310,1,1,'2010-07-08 13:29:05','dolibarr'),(320,1,1,'2010-07-08 13:30:38','dolibarr'),(330,1,1,'2010-07-08 13:30:03','dolibarr'),(400,1,1,'2010-07-11 15:26:54','dolibarr'),(500,1,1,'2010-07-10 15:38:20','dolibarr'),(700,1,1,'2011-02-06 12:20:36','dolibarr'),(1780,1,1,'2010-07-08 13:29:59','dolibarr'),(2000,1,1,'2010-07-08 13:56:27','dolibarr'),(2400,1,1,'2011-02-06 12:20:36','dolibarr'),(2500,1,1,'2011-02-06 12:20:36','dolibarr'),(2600,1,1,'2010-07-08 13:30:30','dolibarr'),(2900,1,1,'2010-07-08 13:30:36','dolibarr'),(10000,1,1,'2010-07-10 01:40:33','dolibarr'),(50000,1,1,'2011-02-06 12:20:36','dolibarr'),(50100,1,1,'2010-07-11 19:08:03','dolibarr');
/*!40000 ALTER TABLE `llx_dolibarr_modules` ENABLE KEYS */;
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
  `prenom` varchar(50) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `societe` varchar(50) DEFAULT NULL,
  `adresse` text,
  `cp` varchar(30) DEFAULT NULL,
  `ville` varchar(50) DEFAULT NULL,
  `pays` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `public` smallint(6) NOT NULL DEFAULT '1',
  `fk_don_projet` int(11) DEFAULT NULL,
  `fk_user_author` int(11) NOT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_don`
--

LOCK TABLES `llx_don` WRITE;
/*!40000 ALTER TABLE `llx_don` DISABLE KEYS */;
INSERT INTO `llx_don` VALUES (1,NULL,1,'2010-07-08 23:57:17',1,'2010-07-09 01:55:33','2010-07-09 12:00:00',10,1,'Donator','','Guest company','','','','France','',1,1,1,1,'',NULL,'html_cerfafr',NULL);
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
  `label` varchar(32) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_parent` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `cachenbofdoc` int(11) NOT NULL DEFAULT '0',
  `date_c` datetime DEFAULT NULL,
  `date_m` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_user_c` int(11) DEFAULT NULL,
  `fk_user_m` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_ecm_directories` (`label`,`fk_parent`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_ecm_directories`
--

LOCK TABLES `llx_ecm_directories` WRITE;
/*!40000 ALTER TABLE `llx_ecm_directories` DISABLE KEYS */;
INSERT INTO `llx_ecm_directories` VALUES (1,'Répertoire_1',1,0,'',1,'2010-07-11 16:27:26','2010-07-11 14:27:44',1,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_element_contact`
--

LOCK TABLES `llx_element_contact` WRITE;
/*!40000 ALTER TABLE `llx_element_contact` DISABLE KEYS */;
INSERT INTO `llx_element_contact` VALUES (1,'2010-07-09 00:49:43',4,1,160,1),(2,'2010-07-09 00:49:56',4,2,160,1),(3,'2010-07-09 00:50:19',4,3,160,1),(4,'2010-07-09 00:50:42',4,4,160,1),(5,'2010-07-09 01:52:36',4,1,120,1),(6,'2010-07-09 01:53:25',4,1,10,2),(7,'2010-07-09 01:53:25',4,1,11,2),(8,'2010-07-10 18:13:37',4,2,10,2),(9,'2010-07-10 18:13:37',4,2,11,2),(10,'2010-07-11 15:15:55',4,1,180,1),(11,'2010-07-11 16:22:36',4,5,160,1),(12,'2010-07-11 16:23:53',4,2,180,1);
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
  `sourcetype` varchar(16) NOT NULL,
  `fk_target` int(11) NOT NULL,
  `targettype` varchar(16) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_element_element_idx1` (`fk_source`,`sourcetype`,`fk_target`,`targettype`),
  KEY `idx_element_element_fk_target` (`fk_target`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_element_element`
--

LOCK TABLES `llx_element_element` WRITE;
/*!40000 ALTER TABLE `llx_element_element` DISABLE KEYS */;
INSERT INTO `llx_element_element` VALUES (1,2,'contrat',2,'facture');
/*!40000 ALTER TABLE `llx_element_element` ENABLE KEYS */;
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
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_entity`
--

LOCK TABLES `llx_entity` WRITE;
/*!40000 ALTER TABLE `llx_entity` DISABLE KEYS */;
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
  `cp` varchar(10) DEFAULT NULL,
  `ville` varchar(50) DEFAULT NULL,
  `fk_departement` int(11) DEFAULT NULL,
  `fk_pays` int(11) DEFAULT '0',
  `statut` tinyint(4) DEFAULT '1',
  `valo_pmp` float(12,4) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_entrepot_label` (`label`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_entrepot`
--

LOCK TABLES `llx_entrepot` WRITE;
/*!40000 ALTER TABLE `llx_entrepot` DISABLE KEYS */;
INSERT INTO `llx_entrepot` VALUES (1,'2010-07-09 00:31:22','2010-07-08 22:40:36','WAREHOUSEHOUSTON',1,'Warehouse located at Houston','Warehouse houston','','','Houston',NULL,11,1,NULL,1),(2,'2010-07-09 00:41:03','2010-07-08 22:41:03','WAREHOUSEPARIS',1,'<br />','Warehouse Paris','','75000','Paris',NULL,1,1,NULL,1),(3,'2010-07-11 16:18:59','2010-07-11 14:18:59','Stock personnel Dupont',1,'Cet entrep&ocirc;t repr&eacute;sente le stock personnel de Alain Dupont','','','','',NULL,0,1,NULL,1);
/*!40000 ALTER TABLE `llx_entrepot` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_events`
--

LOCK TABLES `llx_events` WRITE;
/*!40000 ALTER TABLE `llx_events` DISABLE KEYS */;
INSERT INTO `llx_events` VALUES (25,'2010-07-18 10:53:08','USER_LOGIN_FAILED',1,'2010-07-18 12:53:08',NULL,'Identifiants login ou mot de passe incorrects - login=admi','::1','Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.6) Gecko/20100628 Ubuntu/10.04 (lucid) Firefox/3.6.6',NULL),(26,'2010-07-18 10:53:11','USER_LOGIN',1,'2010-07-18 12:53:11',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.6) Gecko/20100628 Ubuntu/10.04 (lucid) Firefox/3.6.6',NULL),(27,'2010-07-21 23:19:22','USER_LOGIN',1,'2010-07-22 01:19:22',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.6) Gecko/20100628 Ubuntu/10.04 (lucid) Firefox/3.6.6',NULL),(28,'2011-02-06 11:20:40','USER_LOGIN_FAILED',1,'2011-02-06 12:20:40',NULL,'ErrorCantLoadUserFromDolibarrDatabase - login=dolibarrold','::1','Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.13) Gecko/20101206 Ubuntu/10.10 (maverick) Firefox/3.6.13',NULL),(29,'2011-02-06 11:20:59','USER_LOGIN',1,'2011-02-06 12:20:59',1,'(UserLogged,admin)','::1','Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.13) Gecko/20101206 Ubuntu/10.10 (maverick) Firefox/3.6.13',NULL);
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
  `date_creation` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `date_expedition` datetime DEFAULT NULL,
  `date_delivery` datetime DEFAULT NULL,
  `fk_address` int(11) DEFAULT NULL,
  `fk_expedition_methode` int(11) DEFAULT NULL,
  `tracking_number` varchar(50) DEFAULT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `height` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `size_units` int(11) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `weight_units` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `note` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_expedition_uk_ref` (`ref`,`entity`),
  KEY `idx_expedition_fk_soc` (`fk_soc`),
  KEY `idx_expedition_fk_user_author` (`fk_user_author`),
  KEY `idx_expedition_fk_user_valid` (`fk_user_valid`),
  KEY `idx_expedition_fk_expedition_methode` (`fk_expedition_methode`),
  CONSTRAINT `fk_expedition_fk_expedition_methode` FOREIGN KEY (`fk_expedition_methode`) REFERENCES `llx_expedition_methode` (`rowid`),
  CONSTRAINT `fk_expedition_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_expedition_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_expedition_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_expedition`
--

LOCK TABLES `llx_expedition` WRITE;
/*!40000 ALTER TABLE `llx_expedition` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_expeditiondet`
--

LOCK TABLES `llx_expeditiondet` WRITE;
/*!40000 ALTER TABLE `llx_expeditiondet` DISABLE KEYS */;
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
-- Table structure for table `llx_extra_fields`
--

DROP TABLE IF EXISTS `llx_extra_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_extra_fields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `entity` int(11) NOT NULL DEFAULT '1',
  `object` varchar(64) NOT NULL,
  `assign` int(11) DEFAULT NULL,
  `name` varchar(64) NOT NULL,
  `label` varchar(64) NOT NULL,
  `format` varchar(8) NOT NULL,
  `fieldsize` int(11) DEFAULT NULL,
  `maxlength` int(11) DEFAULT NULL,
  `options` varchar(45) DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_extra_fields_name` (`name`,`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_extra_fields`
--

LOCK TABLES `llx_extra_fields` WRITE;
/*!40000 ALTER TABLE `llx_extra_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_extra_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_extra_fields_options`
--

DROP TABLE IF EXISTS `llx_extra_fields_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_extra_fields_options` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_extra_fields` int(11) NOT NULL,
  `value` varchar(255) NOT NULL,
  `rank` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_extra_fields_options_fk_extra_fields` (`fk_extra_fields`),
  CONSTRAINT `fk_extra_fields_options_fk_extra_fields` FOREIGN KEY (`fk_extra_fields`) REFERENCES `llx_extra_fields` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_extra_fields_options`
--

LOCK TABLES `llx_extra_fields_options` WRITE;
/*!40000 ALTER TABLE `llx_extra_fields_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_extra_fields_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_extra_fields_values`
--

DROP TABLE IF EXISTS `llx_extra_fields_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_extra_fields_values` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `entity` int(11) NOT NULL DEFAULT '1',
  `datec` datetime DEFAULT NULL,
  `datem` datetime DEFAULT NULL,
  `fk_object` int(11) NOT NULL,
  `fk_extra_fields` int(11) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `fk_user_create` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_extra_fields_values_fk_extra_fields` (`fk_extra_fields`,`entity`),
  CONSTRAINT `fk_extra_fields_values_fk_extra_fields` FOREIGN KEY (`fk_extra_fields`) REFERENCES `llx_extra_fields` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_extra_fields_values`
--

LOCK TABLES `llx_extra_fields_values` WRITE;
/*!40000 ALTER TABLE `llx_extra_fields_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_extra_fields_values` ENABLE KEYS */;
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
  `ref_ext` varchar(30) DEFAULT NULL,
  `type` smallint(6) NOT NULL DEFAULT '0',
  `ref_client` varchar(30) DEFAULT NULL,
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
  `total` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT '0.00000000',
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_facture_source` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `fk_cond_reglement` int(11) NOT NULL DEFAULT '1',
  `fk_mode_reglement` int(11) DEFAULT NULL,
  `date_lim_reglement` date DEFAULT NULL,
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_facture_uk_facnumber` (`facnumber`,`entity`),
  KEY `idx_facture_fk_soc` (`fk_soc`),
  KEY `idx_facture_fk_user_author` (`fk_user_author`),
  KEY `idx_facture_fk_user_valid` (`fk_user_valid`),
  KEY `idx_facture_fk_facture_source` (`fk_facture_source`),
  KEY `idx_facture_fk_projet` (`fk_projet`),
  CONSTRAINT `fk_facture_fk_facture_source` FOREIGN KEY (`fk_facture_source`) REFERENCES `llx_facture` (`rowid`),
  CONSTRAINT `fk_facture_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_facture_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_facture_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_facture_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facture`
--

LOCK TABLES `llx_facture` WRITE;
/*!40000 ALTER TABLE `llx_facture` DISABLE KEYS */;
INSERT INTO `llx_facture` VALUES (1,'FA1007-0001',1,NULL,0,NULL,NULL,9,'2010-07-10 14:55:26','2010-07-10',NULL,'2010-07-10 13:00:55',1,0.00000000,NULL,NULL,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.02000000,0.02000000,2,1,1,NULL,1,1,0,'2010-07-10',NULL,NULL,'crabe',NULL),(2,'(PROV2)',1,NULL,0,NULL,NULL,2,'2010-07-10 18:20:13','2010-07-10',NULL,'2010-07-10 16:20:13',0,10.00000000,NULL,NULL,0,NULL,NULL,0.10000000,0.00000000,0.00000000,46.00000000,46.10000000,0,1,NULL,NULL,NULL,1,0,'2010-07-10',NULL,NULL,'crabe',NULL);
/*!40000 ALTER TABLE `llx_facture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_facture_fourn`
--

DROP TABLE IF EXISTS `llx_facture_fourn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_facture_fourn` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `facnumber` varchar(50) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
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
  `fk_cond_reglement` int(11) NOT NULL DEFAULT '1',
  `date_lim_reglement` date DEFAULT NULL,
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_facture_fourn_ref` (`facnumber`,`fk_soc`,`entity`),
  KEY `idx_facture_fourn_date_lim_reglement` (`date_lim_reglement`),
  KEY `idx_facture_fourn_fk_soc` (`fk_soc`),
  KEY `idx_facture_fourn_fk_user_author` (`fk_user_author`),
  KEY `idx_facture_fourn_fk_user_valid` (`fk_user_valid`),
  KEY `idx_facture_fourn_fk_projet` (`fk_projet`),
  CONSTRAINT `fk_facture_fourn_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_facture_fourn_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_facture_fourn_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`),
  CONSTRAINT `fk_facture_fourn_fk_user_valid` FOREIGN KEY (`fk_user_valid`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facture_fourn`
--

LOCK TABLES `llx_facture_fourn` WRITE;
/*!40000 ALTER TABLE `llx_facture_fourn` DISABLE KEYS */;
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
  `tva_tx` double(6,3) DEFAULT NULL,
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
  `total_ht` double(24,8) DEFAULT NULL,
  `tva` double(24,8) DEFAULT NULL,
  `total_localtax1` double(24,8) DEFAULT '0.00000000',
  `total_localtax2` double(24,8) DEFAULT '0.00000000',
  `total_ttc` double(24,8) DEFAULT NULL,
  `product_type` int(11) DEFAULT '0',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_facture_fourn_det_fk_facture` (`fk_facture_fourn`),
  CONSTRAINT `fk_facture_fourn_det_fk_facture` FOREIGN KEY (`fk_facture_fourn`) REFERENCES `llx_facture_fourn` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facture_fourn_det`
--

LOCK TABLES `llx_facture_fourn_det` WRITE;
/*!40000 ALTER TABLE `llx_facture_fourn_det` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_facture_fourn_det` ENABLE KEYS */;
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
  `note` text,
  `note_public` text,
  `frequency` int(11) DEFAULT NULL,
  `last_gen` varchar(7) DEFAULT NULL,
  `unit_frequency` varchar(2) DEFAULT 'd',
  `date_when` datetime DEFAULT NULL,
  `date_last_gen` datetime DEFAULT NULL,
  `nb_gen_done` int(11) DEFAULT NULL,
  `nb_gen_max` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_facture_rec_uk_titre` (`titre`,`entity`),
  KEY `idx_facture_rec_fk_soc` (`fk_soc`),
  KEY `idx_facture_rec_fk_user_author` (`fk_user_author`),
  KEY `idx_facture_rec_fk_projet` (`fk_projet`),
  CONSTRAINT `fk_facture_rec_fk_projet` FOREIGN KEY (`fk_projet`) REFERENCES `llx_projet` (`rowid`),
  CONSTRAINT `fk_facture_rec_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_facture_rec_fk_user_author` FOREIGN KEY (`fk_user_author`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
  `special_code` int(10) unsigned DEFAULT '0',
  `rang` int(11) DEFAULT '0',
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_fk_remise_except` (`fk_remise_except`),
  KEY `idx_facturedet_fk_facture` (`fk_facture`),
  CONSTRAINT `fk_facturedet_fk_facture` FOREIGN KEY (`fk_facture`) REFERENCES `llx_facture` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_facturedet`
--

LOCK TABLES `llx_facturedet` WRITE;
/*!40000 ALTER TABLE `llx_facturedet` DISABLE KEYS */;
INSERT INTO `llx_facturedet` VALUES (1,1,NULL,4,'',0.000,0.000,0.000,2,0,0,NULL,0.01000000,0.01000000,0.02000000,0.00000000,0.00000000,0.00000000,0.02000000,0,NULL,NULL,0,0,0,0,1,NULL),(3,2,NULL,3,'Service S1',0.000,0.000,0.000,1,10,4,NULL,40.00000000,36.00000000,36.00000000,0.00000000,0.00000000,0.00000000,36.00000000,1,'2010-07-10 00:00:00',NULL,0,0,0,0,2,NULL),(4,2,NULL,NULL,'Abonnement annuel assurance',1.000,0.000,0.000,1,0,0,NULL,10.00000000,10.00000000,10.00000000,0.10000000,0.00000000,0.00000000,10.10000000,0,'2010-07-10 00:00:00','2011-07-10 00:00:00',0,0,0,0,3,NULL);
/*!40000 ALTER TABLE `llx_facturedet` ENABLE KEYS */;
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
  `description` text,
  `tva_tx` double(6,3) DEFAULT NULL,
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
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
  `special_code` int(10) unsigned DEFAULT '0',
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
  `model_pdf` varchar(50) DEFAULT NULL,
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
INSERT INTO `llx_fichinter` VALUES (1,2,1,0,'FI1007-0001',1,'2010-07-08 23:51:54','2010-07-09 01:42:41','2010-07-09 01:51:54',NULL,1,1,1,51000,NULL,NULL,NULL,'soleil'),(2,1,0,0,'FI1007-0002',1,'2010-07-11 14:08:19','2010-07-11 16:07:51',NULL,NULL,1,NULL,0,3600,'ferfrefeferf',NULL,NULL,'soleil');
/*!40000 ALTER TABLE `llx_fichinter` ENABLE KEYS */;
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
INSERT INTO `llx_fichinterdet` VALUES (1,1,'2010-07-07 04:00:00','Intervention sur site',3600,0),(2,1,'2010-07-08 11:00:00','Autre',47400,0),(3,2,'2010-07-11 05:00:00','Pres',3600,0);
/*!40000 ALTER TABLE `llx_fichinterdet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_fournisseur_ca`
--

DROP TABLE IF EXISTS `llx_fournisseur_ca`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_fournisseur_ca` (
  `fk_societe` int(11) DEFAULT NULL,
  `date_calcul` datetime DEFAULT NULL,
  `year` smallint(5) unsigned DEFAULT NULL,
  `ca_genere` float DEFAULT NULL,
  `ca_achat` float(11,2) DEFAULT '0.00',
  UNIQUE KEY `fk_societe` (`fk_societe`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_fournisseur_ca`
--

LOCK TABLES `llx_fournisseur_ca` WRITE;
/*!40000 ALTER TABLE `llx_fournisseur_ca` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_fournisseur_ca` ENABLE KEYS */;
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
  `date_creation` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `date_delivery` date DEFAULT NULL,
  `fk_address` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `total_ht` double(24,8) DEFAULT '0.00000000',
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
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
  `body` text,
  `bgcolor` varchar(8) DEFAULT NULL,
  `bgimage` varchar(255) DEFAULT NULL,
  `cible` varchar(60) DEFAULT NULL,
  `nbemail` int(11) DEFAULT NULL,
  `email_from` varchar(160) DEFAULT NULL,
  `email_replyto` varchar(160) DEFAULT NULL,
  `email_errorsto` varchar(160) DEFAULT NULL,
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
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_mailing`
--

LOCK TABLES `llx_mailing` WRITE;
/*!40000 ALTER TABLE `llx_mailing` DISABLE KEYS */;
INSERT INTO `llx_mailing` VALUES (3,1,'My commercial emailing',1,'Buy my product','Please buy my product<br />','','',NULL,2,'dolibarr@domain.com','','','2010-07-11 13:15:59','2010-07-11 13:46:20',NULL,NULL,1,1,NULL,NULL,NULL,NULL,NULL);
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
  `nom` varchar(160) DEFAULT NULL,
  `prenom` varchar(160) DEFAULT NULL,
  `email` varchar(160) NOT NULL,
  `other` varchar(255) DEFAULT NULL,
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
INSERT INTO `llx_mailing_cibles` VALUES (1,1,0,'Dupont','Alain','toto@aa.com','Date fin=10/07/2011',0,'0',NULL,NULL,NULL),(2,2,0,'Swiss customer supplier','','abademail@aa.com','',0,'0',NULL,NULL,NULL),(3,2,0,'Smith Vick','','vsmith@email.com','',0,'0',NULL,NULL,NULL),(4,3,0,'Swiss customer supplier','','abademail@aa.com','',0,'0',NULL,NULL,NULL),(5,3,0,'Smith Vick','','vsmith@email.com','',0,'0',NULL,NULL,NULL);
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
  `position` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `target` varchar(100) DEFAULT NULL,
  `titre` varchar(255) NOT NULL,
  `langs` varchar(100) DEFAULT NULL,
  `level` smallint(6) DEFAULT NULL,
  `leftmenu` varchar(1) DEFAULT '1',
  `perms` varchar(255) DEFAULT NULL,
  `enabled` varchar(255) DEFAULT '1',
  `usertype` int(11) NOT NULL DEFAULT '0',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `idx_menu_uk_menu` (`menu_handler`,`fk_menu`,`position`,`url`,`entity`),
  KEY `idx_menu_menuhandler_type` (`menu_handler`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=19270 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_menu`
--

LOCK TABLES `llx_menu` WRITE;
/*!40000 ALTER TABLE `llx_menu` DISABLE KEYS */;
INSERT INTO `llx_menu` VALUES (1,'all',1,'ecm','top','ecm',0,100,'/ecm/index.php','','MenuECM','ecm',0,'1','$user->rights->ecm->download || $user->rights->ecm->upload || $user->rights->ecm->setup','$conf->ecm->enabled',2,'2010-07-08 11:28:47'),(2,'all',1,'ecm','left','ecm',1,101,'/ecm/index.php','','ECMArea','ecm',0,'0','$user->rights->ecm->download || $user->rights->ecm->upload','$user->rights->ecm->download || $user->rights->ecm->upload',2,'2010-07-08 11:28:47'),(3,'all',1,'ecm','left','ecm',2,100,'/ecm/docdir.php?action=create','','ECMNewSection','ecm',0,'0','$user->rights->ecm->setup','$user->rights->ecm->setup',2,'2010-07-08 11:28:47'),(4,'all',1,'ecm','left','ecm',2,102,'/ecm/index.php?action=file_manager','','ECMFileManager','ecm',0,'0','$user->rights->ecm->download || $user->rights->ecm->upload','$user->rights->ecm->download || $user->rights->ecm->upload',2,'2010-07-08 11:28:47'),(5,'all',1,'ecm','left','ecm',2,103,'/ecm/search.php','','Search','ecm',0,'0','$user->rights->ecm->download','$user->rights->ecm->download',2,'2010-07-08 11:28:47'),(14424,'all',1,'cashdesk','top','cashdesk',0,100,'/cashdesk/index.php','','CashDeskMenu','@cashdesk',0,'1','1','0',0,'2010-07-11 17:08:03'),(14439,'auguria',1,NULL,'top','home',0,1,'/index.php?mainmenu=home&amp;leftmenu=','','Home','',-1,'','','1',2,'2010-07-21 23:19:59'),(14440,'auguria',1,NULL,'top','companies',0,2,'/index.php?mainmenu=companies&amp;leftmenu=','','ThirdParties','companies',-1,'','','$conf->societe->enabled || $conf->fournisseur->enabled',2,'2010-07-21 23:19:59'),(14441,'auguria',1,NULL,'top','products',0,3,'/product/index.php?mainmenu=products&amp;leftmenu=','','Products/Services','products',-1,'','$user->rights->produit->lire||$user->rights->service->lire','$conf->product->enabled || $conf->service->enabled',0,'2010-07-21 23:19:59'),(14443,'auguria',1,NULL,'top','commercial',0,5,'/comm/index.php?mainmenu=commercial&amp;leftmenu=','','Commercial','commercial',-1,'','$user->rights->societe->lire || $user->rights->societe->contact->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(14444,'auguria',1,NULL,'top','accountancy',0,6,'/compta/index.php?mainmenu=accountancy&amp;leftmenu=','','MenuFinancial','compta',-1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->plancompte->lire || $user->rights->commande->lire || $user->rights->facture->lire || $user->rights->banque->lire','$conf->compta->enabled || $conf->accounting->enabled || $conf->banque->enabled || $conf->facture->enabled || $conf->deplacement->enabled',2,'2010-07-21 23:19:59'),(14445,'auguria',1,NULL,'top','project',0,7,'/projet/index.php?mainmenu=project&amp;leftmenu=','','Projects','projects',-1,'','$user->rights->projet->lire','$conf->projet->enabled',0,'2010-07-21 23:19:59'),(14446,'auguria',1,NULL,'top','tools',0,8,'/index.php?mainmenu=tools&amp;leftmenu=','','Tools','other',-1,'','$user->rights->mailing->lire || $user->rights->bookmark->lire || $user->rights->export->lire','$conf->mailing->enabled || $conf->export->enabled || $conf->global->MAIN_MODULE_IMPORT || $conf->global->MAIN_MODULE_DOMAIN',2,'2010-07-21 23:19:59'),(14449,'auguria',1,NULL,'top','shop',0,11,'/boutique/index.php?mainmenu=shop&amp;leftmenu=','','OSCommerce','shop',-1,'','','! empty($conf->boutique->enabled)',0,'2010-07-21 23:19:59'),(14450,'auguria',1,NULL,'top','shop',0,12,'/oscommerce_ws/index.php?mainmenu=shop&amp;leftmenu=','','OSCommerce','shop',-1,'','','! empty($conf->oscommercews->enabled)',0,'2010-07-21 23:19:59'),(14451,'auguria',1,NULL,'top','members',0,15,'/adherents/index.php?mainmenu=members&amp;leftmenu=','','Members','members',-1,'','','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(14538,'auguria',1,NULL,'left','home',14439,0,'/admin/index.php?leftmenu=setup','','Setup','admin',0,'','','$user->admin',2,'2010-07-21 23:19:59'),(14539,'auguria',1,NULL,'left','home',14538,1,'/admin/company.php?leftmenu=setup','','MenuCompanySetup','admin',1,'','','$leftmenu==\"setup\"',2,'2010-07-21 23:19:59'),(14540,'auguria',1,NULL,'left','home',14538,4,'/admin/ihm.php?leftmenu=setup','','GUISetup','admin',1,'','','$leftmenu==\"setup\"',2,'2010-07-21 23:19:59'),(14541,'auguria',1,NULL,'left','home',14538,2,'/admin/modules.php?leftmenu=setup','','Modules','admin',1,'','','$leftmenu==\"setup\"',2,'2010-07-21 23:19:59'),(14542,'auguria',1,NULL,'left','home',14538,5,'/admin/boxes.php?leftmenu=setup','','Boxes','admin',1,'','','$leftmenu==\"setup\"',2,'2010-07-21 23:19:59'),(14543,'auguria',1,NULL,'left','home',14538,3,'/admin/menus.php?leftmenu=setup','','Menus','admin',1,'','','$leftmenu==\"setup\"',2,'2010-07-21 23:19:59'),(14544,'auguria',1,NULL,'left','home',14538,6,'/admin/delais.php?leftmenu=setup','','Alerts','admin',1,'','','$leftmenu==\"setup\"',2,'2010-07-21 23:19:59'),(14546,'auguria',1,NULL,'left','home',14538,7,'/admin/perms.php?leftmenu=setup','','Security','admin',1,'','','$leftmenu==\"setup\"',2,'2010-07-21 23:19:59'),(14547,'auguria',1,NULL,'left','home',14538,8,'/admin/mails.php?leftmenu=setup','','Emails','admin',1,'','','$leftmenu==\"setup\"',2,'2010-07-21 23:19:59'),(14548,'auguria',1,NULL,'left','home',14538,9,'/admin/limits.php?leftmenu=setup','','MenuLimits','admin',1,'','','$leftmenu==\"setup\"',2,'2010-07-21 23:19:59'),(14549,'auguria',1,NULL,'left','home',14538,10,'/admin/dict.php?leftmenu=setup','','DictionnarySetup','admin',1,'','','$leftmenu==\"setup\"',2,'2010-07-21 23:19:59'),(14550,'auguria',1,NULL,'left','home',14538,11,'/admin/const.php?leftmenu=setup','','OtherSetup','admin',1,'','','$leftmenu==\"setup\"',2,'2010-07-21 23:19:59'),(14638,'auguria',1,NULL,'left','home',14439,1,'/admin/system/index.php?leftmenu=system','','SystemInfo','admin',0,'','','$user->admin',2,'2010-07-21 23:19:59'),(14639,'auguria',1,NULL,'left','home',14638,0,'/admin/system/dolibarr.php?leftmenu=system','','Dolibarr','admin',1,'','','$leftmenu==\"system\"',2,'2010-07-21 23:19:59'),(14640,'auguria',1,NULL,'left','home',14639,1,'/admin/system/constall.php?leftmenu=system','','AllParameters','admin',2,'','','$leftmenu==\"system\"',2,'2010-07-21 23:19:59'),(14641,'auguria',1,NULL,'left','home',14639,4,'/admin/system/about.php?leftmenu=system','','About','admin',2,'','','$leftmenu==\"system\"',2,'2010-07-21 23:19:59'),(14642,'auguria',1,NULL,'left','home',14638,1,'/admin/system/os.php?leftmenu=system','','OS','admin',1,'','','$leftmenu==\"system\"',2,'2010-07-21 23:19:59'),(14643,'auguria',1,NULL,'left','home',14638,2,'/admin/system/web.php?leftmenu=system','','WebServer','admin',1,'','','$leftmenu==\"system\"',2,'2010-07-21 23:19:59'),(14644,'auguria',1,NULL,'left','home',14638,3,'/admin/system/phpinfo.php?leftmenu=system','','Php','admin',1,'','','$leftmenu==\"system\"',2,'2010-07-21 23:19:59'),(14645,'auguria',1,NULL,'left','home',14639,3,'/admin/triggers.php?leftmenu=system','','Triggers','admin',2,'','','$leftmenu==\"system\"',2,'2010-07-21 23:19:59'),(14646,'auguria',1,NULL,'left','home',14639,2,'/admin/system/modules.php?leftmenu=system','','Modules','admin',2,'','','$leftmenu==\"system\"',2,'2010-07-21 23:19:59'),(14648,'auguria',1,NULL,'left','home',14638,4,'/admin/system/database.php?leftmenu=system','','Database','admin',1,'','','$leftmenu==\"system\"',2,'2010-07-21 23:19:59'),(14649,'auguria',1,NULL,'left','home',14648,0,'/admin/system/database-tables.php?leftmenu=system','','Tables','admin',2,'','','$leftmenu==\"system\"',2,'2010-07-21 23:19:59'),(14650,'auguria',1,NULL,'left','home',14648,1,'/admin/system/database-tables-contraintes.php?leftmenu=system','','Constraints','admin',2,'','','$leftmenu==\"system\"',2,'2010-07-21 23:19:59'),(14738,'auguria',1,NULL,'left','home',14439,2,'/admin/tools/index.php?leftmenu=admintools','','SystemTools','admin',0,'','','$user->admin',2,'2010-07-21 23:19:59'),(14739,'auguria',1,NULL,'left','home',14738,0,'/admin/tools/dolibarr_export.php?leftmenu=admintools','','Backup','admin',1,'','','$leftmenu==\"admintools\"',2,'2010-07-21 23:19:59'),(14740,'auguria',1,NULL,'left','home',14738,1,'/admin/tools/dolibarr_import.php?leftmenu=admintools','','Restore','admin',1,'','','$leftmenu==\"admintools\"',2,'2010-07-21 23:19:59'),(14741,'auguria',1,NULL,'left','home',14738,6,'/admin/tools/purge.php?leftmenu=admintools','','Purge','admin',1,'','','$leftmenu==\"admintools\"',2,'2010-07-21 23:19:59'),(14742,'auguria',1,NULL,'left','home',14738,3,'/admin/tools/eaccelerator.php?leftmenu=admintools','','EAccelerator','admin',1,'','','$leftmenu==\"admintools\" && function_exists(\'eaccelerator_info\')',2,'2010-07-21 23:19:59'),(14743,'auguria',1,NULL,'left','home',14738,2,'/admin/tools/update.php?leftmenu=admintools','','MenuUpgrade','admin',1,'','','$leftmenu==\"admintools\"',2,'2010-07-21 23:19:59'),(14744,'auguria',1,NULL,'left','home',14738,4,'/admin/tools/listevents.php?leftmenu=admintools','','Audit','admin',1,'','','$leftmenu==\"admintools\"',2,'2010-07-21 23:19:59'),(14745,'auguria',1,NULL,'left','home',14738,7,'/support/index.php?leftmenu=admintools','_blank','HelpCenter','help',1,'','','$leftmenu==\"admintools\"',2,'2010-07-21 23:19:59'),(14746,'auguria',1,NULL,'left','home',14738,5,'/admin/tools/listsessions.php?leftmenu=admintools','','Sessions','admin',1,'','','$leftmenu==\"admintools\"',2,'2010-07-21 23:19:59'),(14838,'auguria',1,NULL,'left','home',14439,3,'/user/home.php?leftmenu=users','','MenuUsersAndGroups','users',0,'','','1',2,'2010-07-21 23:19:59'),(14839,'auguria',1,NULL,'left','home',14838,0,'/user/index.php?leftmenu=users','','Users','users',1,'','$user->rights->user->user->lire || $user->admin','$leftmenu==\"users\"',2,'2010-07-21 23:19:59'),(14840,'auguria',1,NULL,'left','home',14839,0,'/user/fiche.php?leftmenu=users&action=create','','NewUser','users',2,'','$user->rights->user->user->creer || $user->admin','$leftmenu==\"users\"',2,'2010-07-21 23:19:59'),(14841,'auguria',1,NULL,'left','home',14838,1,'/user/group/index.php?leftmenu=users','','Groups','users',1,'','$user->rights->user->user->lire || $user->admin','$leftmenu==\"users\"',2,'2010-07-21 23:19:59'),(14842,'auguria',1,NULL,'left','home',14841,0,'/user/group/fiche.php?leftmenu=users&action=create','','NewGroup','users',2,'','$user->rights->user->user->creer || $user->admin','$leftmenu==\"users\"',2,'2010-07-21 23:19:59'),(14938,'auguria',1,NULL,'left','companies',14440,0,'/societe/societe.php','','ThirdParty','companies',0,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(14939,'auguria',1,NULL,'left','companies',14938,0,'/societe/soc.php?action=create','','MenuNewThirdParty','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(14941,'auguria',1,NULL,'left','companies',14938,2,'/fourn/liste.php?leftmenu=suppliers','','Suppliers','suppliers',1,'','$user->rights->societe->lire && $user->rights->fournisseur->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(14942,'auguria',1,NULL,'left','companies',14941,0,'/societe/soc.php?leftmenu=supplier&action=create&type=f','','NewSupplier','suppliers',2,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(14943,'auguria',1,NULL,'left','companies',14941,1,'/contact/index.php?leftmenu=suppliers&type=f','','Contacts','suppliers',2,'','$user->rights->societe->lire && $user->rights->fournisseur->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(14944,'auguria',1,NULL,'left','companies',14938,3,'/comm/prospect/prospects.php?leftmenu=prospects','','Prospects','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(14945,'auguria',1,NULL,'left','companies',14944,0,'/societe/soc.php?leftmenu=prospects&action=create&type=p','','MenuNewProspect','companies',2,'','$user->rights->societe->creer','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(14946,'auguria',1,NULL,'left','companies',14944,1,'/contact/index.php?leftmenu=customers&type=p','','Contacts','companies',2,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(14947,'auguria',1,NULL,'left','companies',14938,4,'/comm/clients.php?leftmenu=customers','','Customers','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(14948,'auguria',1,NULL,'left','companies',14947,0,'/societe/soc.php?leftmenu=customers&action=create&type=c','','MenuNewCustomer','companies',2,'','$user->rights->societe->creer','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(14949,'auguria',1,NULL,'left','companies',14947,1,'/contact/index.php?leftmenu=customers&type=c','','Contacts','companies',2,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(15038,'auguria',1,NULL,'left','companies',14440,1,'/contact/index.php?leftmenu=contacts','','Contacts','companies',0,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(15039,'auguria',1,NULL,'left','companies',15038,0,'/contact/fiche.php?leftmenu=contacts&action=create','','NewContact','companies',1,'','$user->rights->societe->creer','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(15040,'auguria',1,NULL,'left','companies',15038,1,'/contact/index.php?leftmenu=contacts','','List','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(15088,'auguria',1,NULL,'left','companies',14440,3,'/categories/index.php?leftmenu=cat&type=1','','SuppliersCategoriesShort','categories',0,'','$user->rights->categorie>lire','$conf->societe->enabled && $conf->categorie->enabled',2,'2010-07-21 23:19:59'),(15089,'auguria',1,NULL,'left','companies',15088,0,'/categories/fiche.php?action=create&type=1','','NewCat','categories',1,'','$user->rights->categorie>creer','$conf->societe->enabled && $conf->categorie->enabled',2,'2010-07-21 23:19:59'),(15098,'auguria',1,NULL,'left','companies',14440,3,'/categories/index.php?leftmenu=cat&type=2','','CustomersProspectsCategoriesShort','categories',0,'','$user->rights->categorie>lire','$conf->fournisseur->enabled && $conf->categorie->enabled',2,'2010-07-21 23:19:59'),(15099,'auguria',1,NULL,'left','companies',15098,0,'/categories/fiche.php?action=create&type=2','','NewCat','categories',1,'','$user->rights->categorie>creer','$conf->fournisseur->enabled && $conf->categorie->enabled',2,'2010-07-21 23:19:59'),(15138,'auguria',1,NULL,'left','commercial',14443,0,'/comm/prospect/index.php?leftmenu=prospects','','Prospects','companies',0,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15139,'auguria',1,NULL,'left','commercial',15138,0,'/societe/soc.php?leftmenu=prospects&action=create&type=c','','MenuNewProspect','companies',1,'','$user->rights->societe->creer','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15140,'auguria',1,NULL,'left','commercial',15138,1,'/comm/prospect/prospects.php?leftmenu=prospects','','List','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15141,'auguria',1,NULL,'left','commercial',15140,0,'/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=-1','','LastProspectDoNotContact','companies',2,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15142,'auguria',1,NULL,'left','commercial',15140,1,'/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=0','','LastProspectNeverContacted','companies',2,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15143,'auguria',1,NULL,'left','commercial',15140,2,'/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=1','','LastProspectToContact','companies',2,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15144,'auguria',1,NULL,'left','commercial',15140,3,'/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=2','','LastProspectContactInProcess','companies',2,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15145,'auguria',1,NULL,'left','commercial',15140,4,'/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=3','','LastProspectContactDone','companies',2,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15146,'auguria',1,NULL,'left','commercial',15138,2,'/contact/index.php?leftmenu=prospects&type=p','','Contacts','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15238,'auguria',1,NULL,'left','commercial',14443,1,'/comm/index.php?leftmenu=customers','','Customers','companies',0,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15239,'auguria',1,NULL,'left','commercial',15238,0,'/societe/soc.php?leftmenu=customers&action=create&type=c','','MenuNewCustomer','companies',1,'','$user->rights->societe->creer','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15240,'auguria',1,NULL,'left','commercial',15238,1,'/comm/clients.php?leftmenu=customers','','List','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15241,'auguria',1,NULL,'left','commercial',15238,2,'/contact/index.php?leftmenu=customers&type=c','','Contacts','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15338,'auguria',1,NULL,'left','commercial',14443,2,'/contact/index.php?leftmenu=contacts','','Contacts','companies',0,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15339,'auguria',1,NULL,'left','commercial',15338,0,'/contact/fiche.php?leftmenu=contacts&action=create','','NewContact','companies',1,'','$user->rights->societe->creer','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15340,'auguria',1,NULL,'left','commercial',15338,1,'/contact/index.php?leftmenu=contacts&action=create','','List','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15348,'auguria',1,NULL,'left','commercial',15340,1,'/contact/index.php?leftmenu=contacts&type=p','','Prospects','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15349,'auguria',1,NULL,'left','commercial',15340,1,'/contact/index.php?leftmenu=contacts&type=c','','Customers','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15350,'auguria',1,NULL,'left','commercial',15340,1,'/contact/index.php?leftmenu=contacts&type=f','','Suppliers','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15351,'auguria',1,NULL,'left','commercial',15340,1,'/contact/index.php?leftmenu=contacts&type=o','','Other','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',0,'2010-07-21 23:19:59'),(15538,'auguria',1,NULL,'left','commercial',14443,4,'/comm/propal/card.php?leftmenu=propals','','Prop','propal',0,'','$user->rights->propale->lire','$conf->propal->enabled',2,'2010-07-21 23:19:59'),(15539,'auguria',1,NULL,'left','commercial',15538,0,'/societe/societe.php?leftmenu=propals','','NewPropal','propal',1,'','$user->rights->propale->creer','$conf->propal->enabled',2,'2010-07-21 23:19:59'),(15540,'auguria',1,NULL,'left','commercial',15538,1,'/comm/propal/card.php?viewstatut=0','','PropalsDraft','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled',2,'2010-07-21 23:19:59'),(15541,'auguria',1,NULL,'left','commercial',15538,2,'/comm/propal/card.php?viewstatut=1','','PropalsOpened','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled',2,'2010-07-21 23:19:59'),(15542,'auguria',1,NULL,'left','commercial',15538,3,'/comm/propal/card.php?viewstatut=2,3,4','','PropalStatusClosedShort','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled',2,'2010-07-21 23:19:59'),(15543,'auguria',1,NULL,'left','commercial',15538,4,'/comm/propal/stats/index.php?leftmenu=propals','','Statistics','propal',1,'','$user->rights->propale->lire','$conf->propal->enabled',2,'2010-07-21 23:19:59'),(15638,'auguria',1,NULL,'left','commercial',14443,5,'/commande/index.php?leftmenu=orders','','Orders','orders',0,'','$user->rights->commande->lire','$conf->commande->enabled',2,'2010-07-21 23:19:59'),(15639,'auguria',1,NULL,'left','commercial',15638,0,'/societe/societe.php?leftmenu=orders','','NewOrder','orders',1,'','$user->rights->commande->creer','$conf->commande->enabled',2,'2010-07-21 23:19:59'),(15640,'auguria',1,NULL,'left','commercial',15638,1,'/commande/liste.php?leftmenu=orders&viewstatut=0','','StatusOrderDraftShort','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled',2,'2010-07-21 23:19:59'),(15641,'auguria',1,NULL,'left','commercial',15638,2,'/commande/liste.php?leftmenu=orders&viewstatut=1','','StatusOrderValidated','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled',2,'2010-07-21 23:19:59'),(15642,'auguria',1,NULL,'left','commercial',15638,3,'/commande/liste.php?leftmenu=orders&viewstatut=2','','StatusOrderOnProcessShort','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled',2,'2010-07-21 23:19:59'),(15643,'auguria',1,NULL,'left','commercial',15638,4,'/commande/liste.php?leftmenu=orders&viewstatut=3','','StatusOrderToBill','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled',2,'2010-07-21 23:19:59'),(15644,'auguria',1,NULL,'left','commercial',15638,5,'/commande/liste.php?leftmenu=orders&viewstatut=4','','StatusOrderProcessed','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled',2,'2010-07-21 23:19:59'),(15645,'auguria',1,NULL,'left','commercial',15638,6,'/commande/liste.php?leftmenu=orders&viewstatut=-1','','StatusOrderCanceledShort','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled',2,'2010-07-21 23:19:59'),(15646,'auguria',1,NULL,'left','commercial',15638,7,'/commande/stats/index.php?leftmenu=orders','','Statistics','orders',1,'','$user->rights->commande->lire','$conf->commande->enabled',2,'2010-07-21 23:19:59'),(15738,'auguria',1,NULL,'left','commercial',14443,6,'/expedition/index.php?leftmenu=sendings','','Sendings','orders',0,'','$user->rights->expedition->lire','$conf->expedition->enabled',2,'2010-07-21 23:19:59'),(15739,'auguria',1,NULL,'left','commercial',15738,0,'/expedition/liste.php?leftmenu=sendings','','List','orders',1,'','$user->rights->expedition->lire','$conf->expedition->enabled',2,'2010-07-21 23:19:59'),(15740,'auguria',1,NULL,'left','commercial',15738,1,'/expedition/stats/index.php?leftmenu=sendings','','Statistics','orders',1,'','$user->rights->expedition->lire','$conf->expedition->enabled',2,'2010-07-21 23:19:59'),(15838,'auguria',1,NULL,'left','commercial',14443,7,'/contrat/index.php?leftmenu=contracts','','Contracts','contracts',0,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2010-07-21 23:19:59'),(15839,'auguria',1,NULL,'left','commercial',15838,0,'/societe/societe.php?leftmenu=contracts','','NewContract','contracts',1,'','$user->rights->contrat->creer','$conf->contrat->enabled',2,'2010-07-21 23:19:59'),(15840,'auguria',1,NULL,'left','commercial',15838,1,'/contrat/liste.php?leftmenu=contracts','','List','contracts',1,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2010-07-21 23:19:59'),(15841,'auguria',1,NULL,'left','commercial',15838,2,'/contrat/services.php?leftmenu=contracts','','MenuServices','contracts',1,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2010-07-21 23:19:59'),(15842,'auguria',1,NULL,'left','commercial',15840,0,'/contrat/services.php?leftmenu=contracts&mode=0','','MenuInactiveServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2010-07-21 23:19:59'),(15843,'auguria',1,NULL,'left','commercial',15840,1,'/contrat/services.php?leftmenu=contracts&mode=4','','MenuRunningServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2010-07-21 23:19:59'),(15844,'auguria',1,NULL,'left','commercial',15840,2,'/contrat/services.php?leftmenu=contracts&mode=4&filter=expired','','MenuExpiredServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2010-07-21 23:19:59'),(15845,'auguria',1,NULL,'left','commercial',15840,3,'/contrat/services.php?leftmenu=contracts&mode=5','','MenuClosedServices','contracts',2,'','$user->rights->contrat->lire','$conf->contrat->enabled',2,'2010-07-21 23:19:59'),(15938,'auguria',1,NULL,'left','commercial',14443,8,'/fichinter/index.php?leftmenu=ficheinter','','Interventions','interventions',0,'','$user->rights->ficheinter->lire','$conf->ficheinter->enabled',2,'2010-07-21 23:19:59'),(15939,'auguria',1,NULL,'left','commercial',15938,0,'/fichinter/fiche.php?action=create&leftmenu=ficheinter','','NewIntervention','interventions',1,'','$user->rights->ficheinter->creer','$conf->ficheinter->enabled',2,'2010-07-21 23:19:59'),(15940,'auguria',1,NULL,'left','commercial',15938,1,'/fichinter/index.php?leftmenu=ficheinter','','List','interventions',1,'','$user->rights->ficheinter->lire','$conf->ficheinter->enabled',2,'2010-07-21 23:19:59'),(16038,'auguria',1,NULL,'left','accountancy',14444,0,'/compta/index.php?leftmenu=suppliers','','Suppliers','companies',0,'','$user->rights->societe->lire && $user->rights->fournisseur->lire','$conf->fournisseur->enabled',2,'2010-07-21 23:19:59'),(16039,'auguria',1,NULL,'left','accountancy',16038,0,'/societe/soc.php?leftmenu=suppliers&action=create&type=f','','NewSupplier','companies',1,'','$user->rights->societe->creer && $user->rights->fournisseur->lire','$conf->fournisseur->enabled',2,'2010-07-21 23:19:59'),(16040,'auguria',1,NULL,'left','accountancy',16038,1,'/fourn/liste.php?leftmenu=suppliers','','List','companies',1,'','$user->rights->societe->lire && $user->rights->fournisseur->lire','$conf->fournisseur->enabled',2,'2010-07-21 23:19:59'),(16041,'auguria',1,NULL,'left','accountancy',16038,2,'/contact/index.php?leftmenu=suppliers&type=f','','Contacts','companies',1,'','$user->rights->societe->lire && $user->rights->fournisseur->lire','$conf->fournisseur->enabled',2,'2010-07-21 23:19:59'),(16042,'auguria',1,NULL,'left','accountancy',16038,3,'/fourn/facture/index.php?leftmenu=suppliers_bills','','BillsSuppliers','bills',1,'','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled',2,'2010-07-21 23:19:59'),(16043,'auguria',1,NULL,'left','accountancy',16042,0,'/fourn/facture/fiche.php?action=create&leftmenu=suppliers_bills','','NewBill','bills',2,'','$user->rights->fournisseur->facture->creer','$conf->fournisseur->enabled && $leftmenu==\"suppliers_bills\"',2,'2010-07-21 23:19:59'),(16044,'auguria',1,NULL,'left','accountancy',16042,1,'/fourn/facture/impayees.php?leftmenu=suppliers_bills','','Unpaid','bills',2,'','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled && $leftmenu==\"suppliers_bills\"',2,'2010-07-21 23:19:59'),(16045,'auguria',1,NULL,'left','accountancy',16042,2,'/fourn/facture/paiement.php?leftmenu=suppliers_bills','','Payments','bills',2,'','$user->rights->fournisseur->facture->lire','$conf->fournisseur->enabled && $leftmenu==\"suppliers_bills\"',2,'2010-07-21 23:19:59'),(16138,'auguria',1,NULL,'left','accountancy',14444,1,'/compta/index.php?leftmenu=customers','','Customers','companies',0,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(16139,'auguria',1,NULL,'left','accountancy',16138,0,'/societe/soc.php?leftmenu=customers&action=create&type=c','','MenuNewCustomer','companies',1,'','$user->rights->societe->creer','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(16140,'auguria',1,NULL,'left','accountancy',16138,1,'/compta/clients.php?leftmenu=customers','','List','companies',1,'','$user->rights->societe->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(16141,'auguria',1,NULL,'left','accountancy',16138,2,'/contact/index.php?leftmenu=customers&type=c','','Contacts','companies',1,'','$user->rights->societe->lire && $user->rights->fournisseur->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(16142,'auguria',1,NULL,'left','accountancy',16138,3,'/compta/facture.php?leftmenu=customers_bills','','BillsCustomers','bills',1,'','$user->rights->facture->lire','$conf->societe->enabled',2,'2010-07-21 23:19:59'),(16143,'auguria',1,NULL,'left','accountancy',16142,3,'/compta/clients.php?action=facturer&leftmenu=customers_bills','','NewBill','bills',2,'','$user->rights->facture->creer','$conf->societe->enabled && $leftmenu==\"customers_bills\"',2,'2010-07-21 23:19:59'),(16144,'auguria',1,NULL,'left','accountancy',16142,4,'/compta/facture/fiche-rec.php?leftmenu=customers_bills','','Repeatable','bills',2,'','$user->rights->facture->lire','$conf->societe->enabled && $leftmenu==\"customers_bills\"',2,'2010-07-21 23:19:59'),(16145,'auguria',1,NULL,'left','accountancy',16142,5,'/compta/facture/impayees.php?action=facturer&leftmenu=customers_bills','','Unpaid','bills',2,'','$user->rights->facture->lire','$conf->societe->enabled && $leftmenu==\"customers_bills\"',2,'2010-07-21 23:19:59'),(16146,'auguria',1,NULL,'left','accountancy',16142,6,'/compta/paiement/liste.php?leftmenu=customers_bills','','Payments','bills',2,'','$user->rights->facture->lire','$conf->societe->enabled && $leftmenu==\"customers_bills\"',2,'2010-07-21 23:19:59'),(16148,'auguria',1,NULL,'left','accountancy',16146,1,'/compta/paiement/rapport.php?leftmenu=customers_bills','','Reportings','bills',3,'','$user->rights->facture->lire','$conf->societe->enabled && $leftmenu==\"customers_bills\"',2,'2010-07-21 23:19:59'),(16149,'auguria',1,NULL,'left','accountancy',14444,1,'/compta/paiement/cheque/index.php?leftmenu=checks','','MenuChequeDeposits','bills',0,'','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled',2,'2010-07-21 23:19:59'),(16150,'auguria',1,NULL,'left','accountancy',16149,0,'/compta/paiement/cheque/fiche.php?leftmenu=checks&action=new','','NewCheckDeposit','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled && $leftmenu==\"checks\"',2,'2010-07-21 23:19:59'),(16151,'auguria',1,NULL,'left','accountancy',16149,1,'/compta/paiement/cheque/liste.php?leftmenu=checks','','MenuChequesReceipts','bills',1,'','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled && $leftmenu==\"checks\"',2,'2010-07-21 23:19:59'),(16152,'auguria',1,NULL,'left','accountancy',16142,8,'/compta/facture/stats/index.php?leftmenu=customers_bills','','Statistics','bills',2,'','$user->rights->facture->lire','$conf->facture->enabled && $conf->banque->enabled && $leftmenu==\"customers_bills\"',2,'2010-07-21 23:19:59'),(16153,'auguria',1,NULL,'left','accountancy',16138,4,'/compta/paiement/cheque/index.php','','CheckReceipt','bills',1,'','$user->rights->banque->cheque','$conf->facture->enabled && $conf->banque->enabled',1,'2010-07-21 23:19:59'),(16154,'auguria',1,NULL,'left','accountancy',16142,9,'/compta/paiement/cheque/fiche.php?action=new','','New','bills',2,'','$user->rights->banque->cheque','$conf->facture->enabled && $conf->banque->enabled',1,'2010-07-21 23:19:59'),(16155,'auguria',1,NULL,'left','accountancy',16142,10,'/compta/paiement/cheque/liste.php','','List','bills',2,'','$user->rights->banque->cheque','$conf->facture->enabled && $conf->banque->enabled',1,'2010-07-21 23:19:59'),(16238,'auguria',1,NULL,'left','accountancy',14444,2,'/compta/propal.php?leftmenu=propal','','Prop','propal',0,'','$user->rights->propale->lire','$conf->propal->enabled',2,'2010-07-21 23:19:59'),(16338,'auguria',1,NULL,'left','accountancy',14444,3,'/compta/commande/liste.php?leftmenu=orders&status=3&afacturer=1','','MenuOrdersToBill','orders',0,'','$user->rights->commande->lire','$conf->commande->enabled',0,'2010-07-21 23:19:59'),(16438,'auguria',1,NULL,'left','accountancy',14444,4,'/compta/dons/index.php?leftmenu=donations&mainmenu=accountancy','','Donations','donations',0,'','$user->rights->don->lire','$conf->don->enabled',2,'2010-07-21 23:19:59'),(16439,'auguria',1,NULL,'left','accountancy',16438,0,'/compta/dons/fiche.php?leftmenu=donations&mainmenu=accountancy&action=create','','NewDonation','donations',1,'','$user->rights->don->creer','$conf->don->enabled && $leftmenu==\"donations\"',2,'2010-07-21 23:19:59'),(16440,'auguria',1,NULL,'left','accountancy',16438,1,'/compta/dons/liste.php?leftmenu=donations&mainmenu=accountancy','','List','donations',1,'','$user->rights->don->lire','$conf->don->enabled && $leftmenu==\"donations\"',2,'2010-07-21 23:19:59'),(16441,'auguria',1,NULL,'left','accountancy',16438,2,'/compta/dons/stats.php?leftmenu=donations&mainmenu=accountancy','','Statistics','donations',1,'','$user->rights->don->lire','$conf->don->enabled && $leftmenu==\"donations\"',2,'2010-07-21 23:19:59'),(16538,'auguria',1,NULL,'left','accountancy',14444,5,'/compta/deplacement/index.php?leftmenu=tripsandexpenses','','TripsAndExpenses','trips',0,'','$user->rights->deplacement->lire','$conf->deplacement->enabled',0,'2010-07-21 23:19:59'),(16539,'auguria',1,NULL,'left','accountancy',16538,1,'/compta/deplacement/fiche.php?action=create&leftmenu=tripsandexpenses','','New','trips',1,'','$user->rights->deplacement->creer','$conf->deplacement->enabled && $leftmenu==\"tripsandexpenses\"',0,'2010-07-21 23:19:59'),(16540,'auguria',1,NULL,'left','accountancy',16538,2,'/compta/deplacement/index.php?leftmenu=tripsandexpenses','','List','trips',1,'','$user->rights->deplacement->lire','$conf->deplacement->enabled && $leftmenu==\"tripsandexpenses\"',0,'2010-07-21 23:19:59'),(16541,'auguria',1,NULL,'left','accountancy',16538,2,'/compta/deplacement/stats/index.php?leftmenu=tripsandexpenses','','Statistics','trips',1,'','$user->rights->deplacement->lire','$conf->deplacement->enabled && $leftmenu==\"tripsandexpenses\"',0,'2010-07-21 23:19:59'),(16638,'auguria',1,NULL,'left','accountancy',14444,6,'/compta/charges/index.php?leftmenu=tax&mainmenu=accountancy','','MenuTaxAndDividends','compta',0,'','$user->rights->tax->charges->lire','$conf->tax->enabled',0,'2010-07-21 23:19:59'),(16639,'auguria',1,NULL,'left','accountancy',16638,1,'/compta/sociales/index.php?leftmenu=tax_social','','SocialContributions','',1,'','$user->rights->tax->charges->lire','$conf->tax->enabled',0,'2010-07-21 23:19:59'),(16640,'auguria',1,NULL,'left','accountancy',16639,2,'/compta/sociales/charges.php?leftmenu=tax_social&action=create','','MenuNewSocialContribution','',2,'','$user->rights->tax->charges->creer','$conf->tax->enabled && $leftmenu==\"tax_social\"',0,'2010-07-21 23:19:59'),(16641,'auguria',1,NULL,'left','accountancy',16639,3,'/compta/charges/index.php?leftmenu=tax_social&mainmenu=accountancy&mode=sconly','','Payments','',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && $leftmenu==\"tax_social\"',0,'2010-07-21 23:19:59'),(16738,'auguria',1,NULL,'left','accountancy',16638,7,'/compta/tva/index.php?leftmenu=tax_vat&mainmenu=accountancy','','VAT','companies',1,'','$user->rights->tax->charges->lire','$conf->tax->enabled && $conf->compta->tva',0,'2010-07-21 23:19:59'),(16739,'auguria',1,NULL,'left','accountancy',16738,0,'/compta/tva/fiche.php?leftmenu=tax_vat&action=create','','NewPayment','companies',2,'','$user->rights->tax->charges->creer','$conf->tax->enabled && $conf->compta->tva && $leftmenu==\"tax_vat\"',0,'2010-07-21 23:19:59'),(16740,'auguria',1,NULL,'left','accountancy',16738,1,'/compta/tva/reglement.php?leftmenu=tax_vat','','Payments','companies',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && $conf->compta->tva && $leftmenu==\"tax_vat\"',0,'2010-07-21 23:19:59'),(16741,'auguria',1,NULL,'left','accountancy',16738,2,'/compta/tva/clients.php?leftmenu=tax_vat','','ReportByCustomers','companies',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && $conf->compta->tva && $leftmenu==\"tax_vat\"',0,'2010-07-21 23:19:59'),(16742,'auguria',1,NULL,'left','accountancy',16738,3,'/compta/tva/quadri_detail.php?leftmenu=tax_vat','','ReportByQuarter','companies',2,'','$user->rights->tax->charges->lire','$conf->tax->enabled && $conf->compta->tva && $leftmenu==\"tax_vat\"',0,'2010-07-21 23:19:59'),(16838,'auguria',1,NULL,'left','accountancy',14444,8,'/compta/ventilation/index.php?leftmenu=ventil','','Ventilation','companies',0,'','$user->rights->compta->ventilation->lire','$conf->compta->enabled && $conf->global->FACTURE_VENTILATION',0,'2010-07-21 23:19:59'),(16839,'auguria',1,NULL,'left','accountancy',16838,0,'/compta/ventilation/liste.php','','ToDispatch','companies',1,'','$user->rights->compta->ventilation->lire','$conf->compta->enabled && $conf->global->FACTURE_VENTILATION',0,'2010-07-21 23:19:59'),(16840,'auguria',1,NULL,'left','accountancy',16838,1,'/compta/ventilation/lignes.php','','Dispatched','companies',1,'','$user->rights->compta->ventilation->lire','$conf->compta->enabled && $conf->global->FACTURE_VENTILATION',0,'2010-07-21 23:19:59'),(16841,'auguria',1,NULL,'left','accountancy',16838,2,'/compta/param/','','Setup','companies',1,'','$user->rights->compta->ventilation->parametrer','$conf->compta->enabled && $conf->global->FACTURE_VENTILATION',0,'2010-07-21 23:19:59'),(16842,'auguria',1,NULL,'left','accountancy',16841,0,'/compta/param/comptes/liste.php','','List','companies',2,'','$user->rights->compta->ventilation->parametrer','$conf->compta->enabled && $conf->global->FACTURE_VENTILATION',0,'2010-07-21 23:19:59'),(16843,'auguria',1,NULL,'left','accountancy',16841,1,'/compta/param/comptes/fiche.php?action=create','','New','companies',2,'','$user->rights->compta->ventilation->parametrer','$conf->compta->enabled && $conf->global->FACTURE_VENTILATION',0,'2010-07-21 23:19:59'),(16844,'auguria',1,NULL,'left','accountancy',16838,3,'/compta/export/','','Export','companies',1,'','$user->rights->compta->ventilation->lire','$conf->compta->enabled && $conf->global->FACTURE_VENTILATION',0,'2010-07-21 23:19:59'),(16845,'auguria',1,NULL,'left','accountancy',16844,0,'/compta/export/index.php','','New','companies',2,'','$user->rights->compta->ventilation->lire','$conf->compta->enabled && $conf->global->FACTURE_VENTILATION',0,'2010-07-21 23:19:59'),(16846,'auguria',1,NULL,'left','accountancy',16844,1,'/compta/export/liste.php','','List','companies',2,'','$user->rights->compta->ventilation->lire','$conf->compta->enabled && $conf->global->FACTURE_VENTILATION',0,'2010-07-21 23:19:59'),(16938,'auguria',1,NULL,'left','accountancy',14444,9,'/compta/prelevement/index.php?leftmenu=withdraw','','StandingOrders','withdrawals',0,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled',2,'2010-07-21 23:19:59'),(16939,'auguria',1,NULL,'left','accountancy',16938,0,'/compta/prelevement/demandes.php?status=0&leftmenu=withdraw','','StandingOrderToProcess','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2010-07-21 23:19:59'),(16940,'auguria',1,NULL,'left','accountancy',16938,1,'/compta/prelevement/create.php?leftmenu=withdraw','','NewStandingOrder','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2010-07-21 23:19:59'),(16941,'auguria',1,NULL,'left','accountancy',16938,2,'/compta/prelevement/bons.php?leftmenu=withdraw','','WithdrawalsReceipts','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2010-07-21 23:19:59'),(16942,'auguria',1,NULL,'left','accountancy',16938,3,'/compta/prelevement/liste.php?leftmenu=withdraw','','WithdrawalsLines','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2010-07-21 23:19:59'),(16943,'auguria',1,NULL,'left','accountancy',16938,4,'/compta/prelevement/liste_factures.php?leftmenu=withdraw','','WithdrawedBills','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2010-07-21 23:19:59'),(16944,'auguria',1,NULL,'left','accountancy',16938,5,'/compta/prelevement/rejets.php?leftmenu=withdraw','','Rejects','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2010-07-21 23:19:59'),(16945,'auguria',1,NULL,'left','accountancy',16938,6,'/compta/prelevement/stats.php?leftmenu=withdraw','','Statistics','withdrawals',1,'','$user->rights->prelevement->bons->lire','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2010-07-21 23:19:59'),(16946,'auguria',1,NULL,'left','accountancy',16938,7,'/compta/prelevement/config.php?leftmenu=withdraw','','Setup','withdrawals',1,'','$user->rights->prelevement->bons->configurer','$conf->prelevement->enabled && $leftmenu==\"withdraw\"',2,'2010-07-21 23:19:59'),(17038,'auguria',1,NULL,'left','accountancy',14444,10,'/compta/bank/index.php?leftmenu=bank','','MenuBankCash','banks',0,'','$user->rights->banque->lire','$conf->banque->enabled',0,'2010-07-21 23:19:59'),(17039,'auguria',1,NULL,'left','accountancy',17038,0,'/compta/bank/fiche.php?action=create&leftmenu=bank','','MenuNewFinancialAccount','banks',1,'','$user->rights->banque->configurer','$conf->banque->enabled && $leftmenu==bank',0,'2010-07-21 23:19:59'),(17040,'auguria',1,NULL,'left','accountancy',17038,1,'/compta/bank/categ.php?leftmenu=bank','','Rubriques','categories',1,'','$user->rights->banque->configurer','$conf->banque->enabled && $leftmenu==bank',0,'2010-07-21 23:19:59'),(17041,'auguria',1,NULL,'left','accountancy',17038,2,'/compta/bank/search.php?leftmenu=bank','','ListTransactions','banks',1,'','$user->rights->banque->lire','$conf->banque->enabled && $leftmenu==bank',0,'2010-07-21 23:19:59'),(17042,'auguria',1,NULL,'left','accountancy',17038,3,'/compta/bank/budget.php?leftmenu=bank','','ListTransactionsByCategory','banks',1,'','$user->rights->banque->lire','$conf->banque->enabled && $leftmenu==bank',0,'2010-07-21 23:19:59'),(17044,'auguria',1,NULL,'left','accountancy',17038,5,'/compta/bank/virement.php?leftmenu=bank','','BankTransfers','banks',1,'','$user->rights->banque->modifier','$conf->banque->enabled && $leftmenu==bank',0,'2010-07-21 23:19:59'),(17138,'auguria',1,NULL,'left','accountancy',14444,11,'/compta/resultat/index.php?leftmenu=ca&mainmenu=accountancy','','Reportings','main',0,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->compta->enabled || $conf->accounting->enabled',0,'2010-07-21 23:19:59'),(17139,'auguria',1,NULL,'left','accountancy',17138,0,'/compta/resultat/index.php?leftmenu=ca','','ReportInOut','main',1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->compta->enabled || $conf->accounting->enabled',0,'2010-07-21 23:19:59'),(17140,'auguria',1,NULL,'left','accountancy',17139,0,'/compta/resultat/clientfourn.php?leftmenu=ca','','ByCompanies','main',2,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->compta->enabled || $conf->accounting->enabled',0,'2010-07-21 23:19:59'),(17141,'auguria',1,NULL,'left','accountancy',17138,1,'/compta/stats/index.php?leftmenu=ca','','ReportTurnover','main',1,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->compta->enabled || $conf->accounting->enabled',0,'2010-07-21 23:19:59'),(17142,'auguria',1,NULL,'left','accountancy',17141,0,'/compta/stats/casoc.php?leftmenu=ca','','ByCompanies','main',2,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->compta->enabled || $conf->accounting->enabled',0,'2010-07-21 23:19:59'),(17143,'auguria',1,NULL,'left','accountancy',17141,1,'/compta/stats/cabyuser.php?leftmenu=ca','','ByUsers','main',2,'','$user->rights->compta->resultat->lire || $user->rights->accounting->comptarapport->lire','$conf->compta->enabled || $conf->accounting->enabled',0,'2010-07-21 23:19:59'),(17238,'auguria',1,NULL,'left','products',14441,0,'/product/index.php?leftmenu=product&type=0','','Products','products',0,'','$user->rights->produit->lire','$conf->product->enabled',2,'2010-07-21 23:19:59'),(17239,'auguria',1,NULL,'left','products',17238,0,'/product/fiche.php?leftmenu=product&action=create&type=0','','NewProduct','products',1,'','$user->rights->produit->creer','$conf->product->enabled',2,'2010-07-21 23:19:59'),(17240,'auguria',1,NULL,'left','products',17238,1,'/product/liste.php?leftmenu=product&type=0','','ProductList','products',1,'','$user->rights->produit->lire','$conf->product->enabled',2,'2010-07-21 23:19:59'),(17241,'auguria',1,NULL,'left','products',17238,4,'/product/reassort.php?type=0','','Stocks','products',1,'','$user->rights->produit->lire && $user->rights->stock->lire','$conf->product->enabled',2,'2010-07-21 23:19:59'),(17242,'auguria',1,NULL,'left','products',17238,2,'/product/fiche.php?leftmenu=product&action=create&type=0&canvas=livre@droitpret','','NewBook','products',1,'','$user->rights->produit->creer','$conf->product->enabled && $conf->droitpret->enabled',2,'2010-07-21 23:19:59'),(17243,'auguria',1,NULL,'left','products',17238,3,'/product/liste.php?leftmenu=product&type=0&canvas=livre@droitpret','','BookList','products',1,'','$user->rights->produit->lire','$conf->product->enabled && $conf->droitpret->enabled',2,'2010-07-21 23:19:59'),(17338,'auguria',1,NULL,'left','products',14441,1,'/product/index.php?leftmenu=service&type=1','','Services','products',0,'','$user->rights->service->lire','$conf->service->enabled',2,'2010-07-21 23:19:59'),(17339,'auguria',1,NULL,'left','products',17338,0,'/product/fiche.php?leftmenu=service&action=create&type=1','','NewService','products',1,'','$user->rights->service->creer','$conf->service->enabled',2,'2010-07-21 23:19:59'),(17340,'auguria',1,NULL,'left','products',17338,1,'/product/liste.php?leftmenu=service&type=1','','List','products',1,'','$user->rights->service->lire','$conf->service->enabled',2,'2010-07-21 23:19:59'),(17438,'auguria',1,NULL,'left','products',14441,2,'/product/stats/index.php?leftmenu=stats','','Statistics','main',0,'','$user->rights->service->lire','$conf->product->enabled || $conf->service->enabled',2,'2010-07-21 23:19:59'),(17439,'auguria',1,NULL,'left','products',17438,0,'/product/popuprop.php?leftmenu=stats','','Popularity','main',1,'','$user->rights->produit->lire && $user->rights->produit>lire','$conf->propal->enabled',2,'2010-07-21 23:19:59'),(17538,'auguria',1,NULL,'left','products',14441,3,'/product/stock/index.php?leftmenu=stock','','Stock','stocks',0,'','$user->rights->stock->lire','$conf->stock->enabled',2,'2010-07-21 23:19:59'),(17539,'auguria',1,NULL,'left','products',17538,0,'/product/stock/fiche.php?action=create','','MenuNewWarehouse','stocks',1,'','$user->rights->stock->creer','$conf->stock->enabled',2,'2010-07-21 23:19:59'),(17540,'auguria',1,NULL,'left','products',17538,1,'/product/stock/liste.php','','List','stocks',1,'','$user->rights->stock->lire','$conf->stock->enabled',2,'2010-07-21 23:19:59'),(17541,'auguria',1,NULL,'left','products',17538,2,'/product/stock/valo.php','','EnhancedValue','stocks',1,'','$user->rights->stock->lire','$conf->stock->enabled',2,'2010-07-21 23:19:59'),(17542,'auguria',1,NULL,'left','products',17538,3,'/product/stock/mouvement.php','','Movements','stocks',1,'','$user->rights->stock->mouvement->lire','$conf->stock->enabled',2,'2010-07-21 23:19:59'),(17638,'auguria',1,NULL,'left','products',14441,4,'/categories/index.php?leftmenu=cat&type=0','','Categories','categories',0,'','$user->rights->categorie>lire','$conf->categorie->enabled',2,'2010-07-21 23:19:59'),(17639,'auguria',1,NULL,'left','products',17638,0,'/categories/fiche.php?action=create&type=0','','NewCat','categories',1,'','$user->rights->categorie>creer','$conf->categorie->enabled',2,'2010-07-21 23:19:59'),(18038,'auguria',1,NULL,'left','project',14445,0,'/projet/index.php?leftmenu=projects','','Projects','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18039,'auguria',1,NULL,'left','project',18038,1,'/projet/fiche.php?leftmenu=projects&action=create','','NewProject','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18040,'auguria',1,NULL,'left','project',18038,2,'/projet/liste.php?leftmenu=projects','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18048,'auguria',1,NULL,'left','project',14445,0,'/projet/index.php?leftmenu=projects&mode=mine','','MyProjects','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18049,'auguria',1,NULL,'left','project',18048,1,'/projet/fiche.php?leftmenu=projects&action=create&mode=mine','','NewProject','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18050,'auguria',1,NULL,'left','project',18048,2,'/projet/liste.php?leftmenu=projects&mode=mine','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18138,'auguria',1,NULL,'left','project',14445,0,'/projet/activity/index.php?leftmenu=projects','','Activities','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18139,'auguria',1,NULL,'left','project',18138,1,'/projet/tasks.php?leftmenu=projects&action=create','','NewTask','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18140,'auguria',1,NULL,'left','project',18138,2,'/projet/tasks/index.php?leftmenu=projects','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18141,'auguria',1,NULL,'left','project',18138,3,'/projet/activity/list.php?leftmenu=projects','','NewTimeSpent','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18238,'auguria',1,NULL,'left','project',14445,0,'/projet/activity/index.php?leftmenu=projects&mode=mine','','MyActivities','projects',0,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18239,'auguria',1,NULL,'left','project',18238,1,'/projet/tasks.php?leftmenu=projects&action=create&mode=mine','','NewTask','projects',1,'','$user->rights->projet->creer','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18240,'auguria',1,NULL,'left','project',18238,2,'/projet/tasks/index.php?leftmenu=projects&mode=mine','','List','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18241,'auguria',1,NULL,'left','project',18238,3,'/projet/activity/list.php?leftmenu=projects&mode=mine','','NewTimeSpent','projects',1,'','$user->rights->projet->lire','$conf->projet->enabled',2,'2010-07-21 23:19:59'),(18338,'auguria',1,NULL,'left','tools',14446,0,'/comm/mailing/index.php?leftmenu=mailing','','EMailings','mails',0,'','$user->rights->mailing->lire','$conf->mailing->enabled',0,'2010-07-21 23:19:59'),(18339,'auguria',1,NULL,'left','tools',18338,0,'/comm/mailing/fiche.php?leftmenu=mailing&action=create','','NewMailing','mails',1,'','$user->rights->mailing->creer','$conf->mailing->enabled',0,'2010-07-21 23:19:59'),(18340,'auguria',1,NULL,'left','tools',18338,1,'/comm/mailing/liste.php?leftmenu=mailing','','List','mails',1,'','$user->rights->mailing->lire','$conf->mailing->enabled',0,'2010-07-21 23:19:59'),(18538,'auguria',1,NULL,'left','tools',14446,2,'/exports/index.php?leftmenu=export','','FormatedExport','exports',0,'','$user->rights->export->lire','$conf->export->enabled',2,'2010-07-21 23:19:59'),(18539,'auguria',1,NULL,'left','tools',18538,0,'/exports/export.php?leftmenu=export','','NewExport','exports',1,'','$user->rights->export->creer','$conf->export->enabled',2,'2010-07-21 23:19:59'),(18568,'auguria',1,NULL,'left','tools',14446,2,'/imports/index.php?leftmenu=import','','FormatedImport','exports',0,'','$user->rights->import->run','$conf->import->enabled',2,'2010-07-21 23:19:59'),(18569,'auguria',1,NULL,'left','tools',18568,0,'/imports/import.php?leftmenu=import','','NewImport','exports',1,'','$user->rights->import->run','$conf->import->enabled',2,'2010-07-21 23:19:59'),(18638,'auguria',1,NULL,'left','members',14451,0,'/adherents/index.php?leftmenu=members&mainmenu=members','','Members','members',0,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18639,'auguria',1,NULL,'left','members',18638,0,'/adherents/fiche.php?action=create','','NewMember','members',1,'','$user->rights->adherent->creer','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18640,'auguria',1,NULL,'left','members',18638,1,'/adherents/liste.php','','List','members',1,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18641,'auguria',1,NULL,'left','members',18640,2,'/adherents/liste.php?statut=-1','','MenuMembersToValidate','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18642,'auguria',1,NULL,'left','members',18640,3,'/adherents/liste.php?statut=1','','MenuMembersValidated','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18643,'auguria',1,NULL,'left','members',18640,4,'/adherents/liste.php?statut=1&filter=outofdate','','MenuMembersNotUpToDate','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18644,'auguria',1,NULL,'left','members',18640,5,'/adherents/liste.php?statut=1&filter=uptodate','','MenuMembersUpToDate','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18645,'auguria',1,NULL,'left','members',18640,6,'/adherents/liste.php?statut=0','','MenuMembersResiliated','members',2,'','$user->rights->adherent->lire','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18738,'auguria',1,NULL,'left','members',14451,1,'/adherents/index.php?leftmenu=accountancy&mainmenu=members','','Subscriptions','compta',0,'','$user->rights->adherent->cotisation->lire','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18739,'auguria',1,NULL,'left','members',18738,0,'/adherents/liste.php?statut=-1&leftmenu=accountancy&mainmenu=members','','NewSubscription','compta',1,'','$user->rights->adherent->cotisation->creer','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18740,'auguria',1,NULL,'left','members',18738,1,'/adherents/cotisations.php?leftmenu=accountancy','','List','compta',1,'','$user->rights->adherent->cotisation->lire','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18938,'auguria',1,NULL,'left','members',14451,3,'/adherents/index.php?leftmenu=export&mainmenu=members','','Exports','members',0,'','$user->rights->adherent->export','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18939,'auguria',1,NULL,'left','members',18938,0,'/exports/index.php?leftmenu=export','','Datas','members',1,'','$user->rights->adherent->export','$conf->adherent->enabled && $conf->export->enabled',2,'2010-07-21 23:19:59'),(18940,'auguria',1,NULL,'left','members',18938,1,'/adherents/htpasswd.php?leftmenu=export','','Filehtpasswd','members',1,'','$user->rights->adherent->export','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18941,'auguria',1,NULL,'left','members',18938,2,'/adherents/cartes/carte.php?leftmenu=export','_blank','MembersCards','members',1,'','$user->rights->adherent->export','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(18942,'auguria',1,NULL,'left','members',18938,3,'/adherents/cartes/etiquette.php?leftmenu=export','_blank','MembersTickets','members',1,'','$user->rights->adherent->export','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(19038,'auguria',1,NULL,'left','members',14451,4,'/adherents/public.php?leftmenu=member_public','','MemberPublicLinks','members',0,'','$user->rights->adherent->export','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(19138,'auguria',1,NULL,'left','members',14451,5,'/adherents/index.php?leftmenu=setup&mainmenu=members','','Setup','members',0,'','$user->rights->adherent->configurer','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(19139,'auguria',1,NULL,'left','members',19138,0,'/adherents/type.php?leftmenu=setup','','MembersTypes','members',1,'','$user->rights->adherent->configurer','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(19140,'auguria',1,NULL,'left','members',19138,1,'/adherents/options.php?leftmenu=setup','','MembersAttributes','members',1,'','$user->rights->adherent->configurer','$conf->adherent->enabled',2,'2010-07-21 23:19:59'),(19238,'auguria',1,NULL,'left','products',14441,5,'/product/droitpret/index.php?leftmenu=droitpret','','Droit de pret','products',0,'','$user->rights->droitpret->lire','$conf->droitpret->enabled',2,'2010-07-21 23:19:59'),(19239,'auguria',1,NULL,'left','products',19238,1,'/product/droitpret/index.php?leftmenu=droitpret','','Generer rapport','products',1,'','$user->rights->droitpret->creer','$conf->droitpret->enabled',2,'2010-07-21 23:19:59'),(19255,'all',1,'agenda','top','agenda',0,100,'/comm/action/index.php','','Agenda','agenda',0,'1','$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2011-02-06 11:20:36'),(19256,'all',1,'agenda','left','agenda',19255,100,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda','','Actions','agenda',0,'0','$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2011-02-06 11:20:36'),(19257,'all',1,'agenda','left','agenda',19256,101,'/comm/action/fiche.php?mainmenu=agenda&amp;leftmenu=agenda&amp;action=create','','NewAction','commercial',0,'0','($user->rights->agenda->myactions->create||$user->rights->agenda->allactions->create)','$conf->agenda->enabled',2,'2011-02-06 11:20:36'),(19258,'all',1,'agenda','left','agenda',19256,102,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda','','Calendar','agenda',0,'0','$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2011-02-06 11:20:36'),(19259,'all',1,'agenda','left','agenda',19258,103,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine','','MenuToDoMyActions','agenda',0,'0','$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2011-02-06 11:20:36'),(19260,'all',1,'agenda','left','agenda',19258,104,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine','','MenuDoneMyActions','agenda',0,'0','$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2011-02-06 11:20:36'),(19261,'all',1,'agenda','left','agenda',19258,105,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo','','MenuToDoActions','agenda',0,'0','$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2011-02-06 11:20:36'),(19262,'all',1,'agenda','left','agenda',19258,106,'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done','','MenuDoneActions','agenda',0,'0','$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2011-02-06 11:20:36'),(19263,'all',1,'agenda','left','agenda',19256,112,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda','','List','agenda',0,'0','$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2011-02-06 11:20:36'),(19264,'all',1,'agenda','left','agenda',19263,113,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine','','MenuToDoMyActions','agenda',0,'0','$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2011-02-06 11:20:36'),(19265,'all',1,'agenda','left','agenda',19263,114,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine','','MenuDoneMyActions','agenda',0,'0','$user->rights->agenda->myactions->read','$conf->agenda->enabled',2,'2011-02-06 11:20:36'),(19266,'all',1,'agenda','left','agenda',19263,115,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo','','MenuToDoActions','agenda',0,'0','$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2011-02-06 11:20:36'),(19267,'all',1,'agenda','left','agenda',19263,116,'/comm/action/listactions.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done','','MenuDoneActions','agenda',0,'0','$user->rights->agenda->allactions->read','$user->rights->agenda->allactions->read',2,'2011-02-06 11:20:36'),(19268,'all',1,'agenda','left','agenda',19256,120,'/comm/action/rapport/index.php?mainmenu=agenda&amp;leftmenu=agenda','','Reportings','agenda',0,'0','$user->rights->agenda->allactions->read','$conf->agenda->enabled',2,'2011-02-06 11:20:36');
/*!40000 ALTER TABLE `llx_menu` ENABLE KEYS */;
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
-- Table structure for table `llx_paiement`
--

DROP TABLE IF EXISTS `llx_paiement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_paiement` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datep` datetime DEFAULT NULL,
  `amount` double DEFAULT '0',
  `fk_paiement` int(11) NOT NULL,
  `num_paiement` varchar(50) DEFAULT NULL,
  `note` text,
  `fk_bank` int(11) NOT NULL DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  `statut` smallint(6) NOT NULL DEFAULT '0',
  `fk_export_compta` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_paiement`
--

LOCK TABLES `llx_paiement` WRITE;
/*!40000 ALTER TABLE `llx_paiement` DISABLE KEYS */;
INSERT INTO `llx_paiement` VALUES (1,'2010-07-10 14:59:41','2010-07-10 12:59:41','2010-07-10 12:00:00',0.02,4,'','',4,1,NULL,0,0);
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
  `amount` double DEFAULT '0',
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_paiement_facture` (`fk_paiement`,`fk_facture`),
  KEY `idx_paiement_facture_fk_facture` (`fk_facture`),
  KEY `idx_paiement_facture_fk_paiement` (`fk_paiement`),
  CONSTRAINT `fk_paiement_facture_fk_facture` FOREIGN KEY (`fk_facture`) REFERENCES `llx_facture` (`rowid`),
  CONSTRAINT `fk_paiement_facture_fk_paiement` FOREIGN KEY (`fk_paiement`) REFERENCES `llx_paiement` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_paiement_facture`
--

LOCK TABLES `llx_paiement_facture` WRITE;
/*!40000 ALTER TABLE `llx_paiement_facture` DISABLE KEYS */;
INSERT INTO `llx_paiement_facture` VALUES (1,1,1,0.02);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_paiementcharge`
--

LOCK TABLES `llx_paiementcharge` WRITE;
/*!40000 ALTER TABLE `llx_paiementcharge` DISABLE KEYS */;
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
-- Table structure for table `llx_pos_tmp`
--

DROP TABLE IF EXISTS `llx_pos_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_pos_tmp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_article` int(11) NOT NULL,
  `qte` double NOT NULL,
  `fk_tva` int(11) NOT NULL,
  `remise_percent` double NOT NULL,
  `remise` double NOT NULL,
  `total_ht` double(24,8) NOT NULL,
  `total_tva` double(24,8) NOT NULL,
  `total_ttc` double(24,8) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_pos_tmp`
--

LOCK TABLES `llx_pos_tmp` WRITE;
/*!40000 ALTER TABLE `llx_pos_tmp` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_pos_tmp` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
-- Table structure for table `llx_prelevement_notifications`
--

DROP TABLE IF EXISTS `llx_prelevement_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_prelevement_notifications` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `action` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_prelevement_notifications`
--

LOCK TABLES `llx_prelevement_notifications` WRITE;
/*!40000 ALTER TABLE `llx_prelevement_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_prelevement_notifications` ENABLE KEYS */;
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
  `ref` varchar(32) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(32) DEFAULT NULL,
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
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_ref` (`ref`,`entity`),
  KEY `idx_product_label` (`label`),
  KEY `idx_product_barcode` (`barcode`),
  KEY `idx_product_import_key` (`import_key`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product`
--

LOCK TABLES `llx_product` WRITE;
/*!40000 ALTER TABLE `llx_product` DISABLE KEYS */;
INSERT INTO `llx_product` VALUES (1,'2010-07-08 14:33:17','2010-07-21 23:19:50',0,0,'PIDRESS',1,NULL,'Pink dress','A beatifull pink dress','',NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,1,0,'',20,NULL,0,'','',NULL,100,0,NULL,0,NULL,0,NULL,0,NULL,0.00000000,'default@product',1,0,NULL),(2,'2010-07-09 00:30:01','2010-07-21 23:19:50',0,0,'Product_P1',1,NULL,'Product P1','','',NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,1,0,'',NULL,NULL,0,'','',NULL,NULL,0,NULL,0,NULL,0,NULL,0,NULL,0.00000000,'default@product',1,0,NULL),(3,'2010-07-09 00:30:25','2010-07-21 23:19:50',0,0,'Service_S1',1,NULL,'Service S1','','',NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,1,1,'1m',NULL,NULL,0,'','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00000000,'service@product',0,0,NULL),(4,'2010-07-10 14:44:06','2010-07-21 23:19:50',0,0,'DECAP',1,NULL,'Decapsuleur','','',NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1,1,0,'',NULL,NULL,0,'','',NULL,2,-3,NULL,0,NULL,0,NULL,0,NULL,10.00000000,'default@product',1,0,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_association`
--

LOCK TABLES `llx_product_association` WRITE;
/*!40000 ALTER TABLE `llx_product_association` DISABLE KEYS */;
INSERT INTO `llx_product_association` VALUES (1,4,1,2);
/*!40000 ALTER TABLE `llx_product_association` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_ca`
--

DROP TABLE IF EXISTS `llx_product_ca`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_ca` (
  `fk_product` int(11) DEFAULT NULL,
  `date_calcul` datetime DEFAULT NULL,
  `year` smallint(5) unsigned DEFAULT NULL,
  `ca_genere` float DEFAULT NULL,
  UNIQUE KEY `fk_product` (`fk_product`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_ca`
--

LOCK TABLES `llx_product_ca` WRITE;
/*!40000 ALTER TABLE `llx_product_ca` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_product_ca` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `llx_product_fournisseur`
--

DROP TABLE IF EXISTS `llx_product_fournisseur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_product_fournisseur` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `datec` datetime DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_product` int(11) DEFAULT NULL,
  `fk_soc` int(11) DEFAULT NULL,
  `ref_fourn` varchar(30) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `fk_user_author` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_fournisseur_ref` (`ref_fourn`,`fk_soc`,`entity`),
  KEY `idx_product_fourn_fk_product` (`fk_product`,`entity`),
  KEY `idx_product_fourn_fk_soc` (`fk_soc`,`entity`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_fournisseur`
--

LOCK TABLES `llx_product_fournisseur` WRITE;
/*!40000 ALTER TABLE `llx_product_fournisseur` DISABLE KEYS */;
INSERT INTO `llx_product_fournisseur` VALUES (1,'2010-07-11 18:45:42','2010-07-11 16:45:42',4,1,'ABCD',1,1);
/*!40000 ALTER TABLE `llx_product_fournisseur` ENABLE KEYS */;
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
  `fk_product_fournisseur` int(11) NOT NULL,
  `price` double(24,8) DEFAULT '0.00000000',
  `quantity` double DEFAULT NULL,
  `unitprice` double(24,8) DEFAULT '0.00000000',
  `fk_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_product_fournisseur_price_fk_user` (`fk_user`),
  KEY `idx_product_fournisseur_price_fk_product_fournisseur` (`fk_product_fournisseur`),
  CONSTRAINT `fk_product_fournisseur_price_fk_product_fournisseur` FOREIGN KEY (`fk_product_fournisseur`) REFERENCES `llx_product_fournisseur` (`rowid`),
  CONSTRAINT `fk_product_fournisseur_price_fk_user` FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_fournisseur_price`
--

LOCK TABLES `llx_product_fournisseur_price` WRITE;
/*!40000 ALTER TABLE `llx_product_fournisseur_price` DISABLE KEYS */;
INSERT INTO `llx_product_fournisseur_price` VALUES (1,'2010-07-11 18:45:42','2010-07-11 16:45:42',1,10.00000000,1,10.00000000,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_lang`
--

LOCK TABLES `llx_product_lang` WRITE;
/*!40000 ALTER TABLE `llx_product_lang` DISABLE KEYS */;
INSERT INTO `llx_product_lang` VALUES (1,1,'en_US','Pink dress','A beatifull pink dress',''),(2,2,'en_US','Product P1','',''),(3,3,'en_US','Service S1','',''),(4,4,'fr_FR','Decapsuleur','','');
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
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_price`
--

LOCK TABLES `llx_product_price` WRITE;
/*!40000 ALTER TABLE `llx_product_price` DISABLE KEYS */;
INSERT INTO `llx_product_price` VALUES (1,'2010-07-08 12:33:17',1,'2010-07-08 14:33:17',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1),(2,'2010-07-08 22:30:01',2,'2010-07-09 00:30:01',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1),(3,'2010-07-08 22:30:25',3,'2010-07-09 00:30:25',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1),(4,'2010-07-10 12:44:06',4,'2010-07-10 14:44:06',1,0.00000000,0.00000000,0.00000000,0.00000000,'HT',12.500,0,0.000,0.000,1,1);
/*!40000 ALTER TABLE `llx_product_price` ENABLE KEYS */;
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
  `location` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_product_stock` (`fk_product`,`fk_entrepot`),
  KEY `idx_product_stock_fk_product` (`fk_product`),
  KEY `idx_product_stock_fk_entrepot` (`fk_entrepot`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_product_stock`
--

LOCK TABLES `llx_product_stock` WRITE;
/*!40000 ALTER TABLE `llx_product_stock` DISABLE KEYS */;
INSERT INTO `llx_product_stock` VALUES (1,'2010-07-08 22:43:51',2,2,1000,0.00000000,NULL),(3,'2010-07-10 23:02:20',4,2,1000,0.00000000,NULL),(4,'2010-07-11 16:49:44',4,1,2,10.00000000,NULL),(5,'2010-07-11 16:49:44',1,1,4,0.00000000,NULL);
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
  `model_pdf` varchar(50) DEFAULT NULL,
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
-- Table structure for table `llx_projet_task`
--

DROP TABLE IF EXISTS `llx_projet_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `llx_projet_task` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
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
  `progress` int(11) DEFAULT '0',
  `priority` int(11) DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) NOT NULL DEFAULT '0',
  `note_private` text,
  `note_public` text,
  `rang` int(11) DEFAULT '0',
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
INSERT INTO `llx_projet_task` VALUES (1,1,0,'2010-07-11 15:15:55','2011-02-06 11:20:20','2010-07-11 12:00:00',NULL,NULL,'Work on module','',25920000,0,0,1,NULL,0,NULL,NULL,0),(2,5,0,'2010-07-11 16:23:53','2010-07-11 14:25:39','2010-07-11 12:00:00','2011-07-14 12:00:00',NULL,'Heberger site RMLL','',0,0,0,1,NULL,0,NULL,NULL,0);
/*!40000 ALTER TABLE `llx_projet_task` ENABLE KEYS */;
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
  `fk_projet` int(11) DEFAULT '0',
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `ref_ext` varchar(30) DEFAULT NULL,
  `ref_client` varchar(30) DEFAULT NULL,
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
  `fk_cond_reglement` int(11) DEFAULT NULL,
  `fk_mode_reglement` int(11) DEFAULT NULL,
  `note` text,
  `note_public` text,
  `model_pdf` varchar(50) DEFAULT NULL,
  `date_livraison` date DEFAULT NULL,
  `fk_adresse_livraison` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_propal_ref` (`ref`,`entity`),
  KEY `idx_propal_fk_soc` (`fk_soc`),
  CONSTRAINT `fk_propal_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_propal`
--

LOCK TABLES `llx_propal` WRITE;
/*!40000 ALTER TABLE `llx_propal` DISABLE KEYS */;
INSERT INTO `llx_propal` VALUES (1,2,0,'PR1007-0001',1,NULL,'','2010-07-09 01:33:49','2010-07-09','2010-07-24 12:00:00','2010-07-09 01:34:23',NULL,1,1,NULL,1,0,NULL,NULL,0,10.00000000,1.25000000,0.00000000,0.00000000,11.25000000,1,0,'','','azur',NULL,NULL),(2,1,0,'PR1007-0002',1,NULL,'','2010-07-10 02:11:44','2010-07-10','2010-07-25 12:00:00','2010-07-10 02:12:55',NULL,1,1,NULL,1,0,NULL,NULL,0,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,1,1,'','','azur',NULL,NULL),(3,4,0,'PR1007-0003',1,NULL,'','2010-07-18 11:35:11','2010-07-18','2010-08-02 12:00:00','2010-07-18 11:36:18',NULL,1,1,NULL,1,0,NULL,NULL,0,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,1,0,'','','azur',NULL,NULL);
/*!40000 ALTER TABLE `llx_propal` ENABLE KEYS */;
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
  `description` text,
  `fk_remise_except` int(11) DEFAULT NULL,
  `tva_tx` double(6,3) DEFAULT '0.000',
  `localtax1_tx` double(6,3) DEFAULT '0.000',
  `localtax2_tx` double(6,3) DEFAULT '0.000',
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
  `pa_ht` double(24,8) DEFAULT '0.00000000',
  `marge_tx` double(6,3) DEFAULT '0.000',
  `marque_tx` double(6,3) DEFAULT '0.000',
  `special_code` int(10) unsigned DEFAULT '0',
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  KEY `idx_propaldet_fk_propal` (`fk_propal`),
  CONSTRAINT `fk_propaldet_fk_propal` FOREIGN KEY (`fk_propal`) REFERENCES `llx_propal` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_propaldet`
--

LOCK TABLES `llx_propaldet` WRITE;
/*!40000 ALTER TABLE `llx_propaldet` DISABLE KEYS */;
INSERT INTO `llx_propaldet` VALUES (1,1,NULL,NULL,'Une machine à café',NULL,12.500,0.000,0.000,1,0,0,NULL,10.00000000,10.00000000,1.25000000,0.00000000,0.00000000,11.25000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,1),(2,2,NULL,NULL,'Product 1',NULL,0.000,0.000,0.000,1,0,0,NULL,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,1),(3,2,NULL,2,'',NULL,0.000,0.000,0.000,1,0,0,NULL,NULL,0.00000000,0.00000000,0.00000000,0.00000000,0.00000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,2),(4,3,NULL,NULL,'A new marvelous product',NULL,0.000,0.000,0.000,1,0,0,NULL,10.00000000,10.00000000,0.00000000,0.00000000,0.00000000,10.00000000,0,NULL,NULL,0,0.00000000,NULL,NULL,0,1);
/*!40000 ALTER TABLE `llx_propaldet` ENABLE KEYS */;
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
INSERT INTO `llx_rights_def` VALUES (11,'Lire les factures','facture',1,'lire',NULL,'a',1),(12,'Creer/modifier les factures','facture',1,'creer',NULL,'a',0),(13,'Dévalider les factures','facture',1,'unvalidate',NULL,'a',0),(14,'Valider les factures','facture',1,'valider',NULL,'a',0),(15,'Envoyer les factures par mail','facture',1,'envoyer',NULL,'a',0),(16,'Emettre des paiements sur les factures','facture',1,'paiement',NULL,'a',0),(19,'Supprimer les factures','facture',1,'supprimer',NULL,'a',0),(21,'Lire les propositions commerciales','propale',1,'lire',NULL,'r',1),(22,'Creer/modifier les propositions commerciales','propale',1,'creer',NULL,'w',0),(24,'Valider les propositions commerciales','propale',1,'valider',NULL,'d',0),(25,'Envoyer les propositions commerciales aux clients','propale',1,'envoyer',NULL,'d',0),(26,'Cloturer les propositions commerciales','propale',1,'cloturer',NULL,'d',0),(27,'Supprimer les propositions commerciales','propale',1,'supprimer',NULL,'d',0),(28,'Exporter les propositions commerciales et attributs','propale',1,'export',NULL,'r',0),(31,'Lire les produits','produit',1,'lire',NULL,'r',1),(32,'Creer/modifier les produits','produit',1,'creer',NULL,'w',0),(34,'Supprimer les produits','produit',1,'supprimer',NULL,'d',0),(36,'Voir/gérer les produits cachés','produit',1,'hidden',NULL,'r',1),(38,'Exporter les produits','produit',1,'export',NULL,'r',0),(41,'Lire les projets et taches (partagés ou dont je suis contact)','projet',1,'lire',NULL,'r',1),(42,'Creer/modifier les projets et taches (partagés ou dont je suis contact)','projet',1,'creer',NULL,'w',0),(44,'Supprimer les projets et taches (partagés ou dont je suis contact)','projet',1,'supprimer',NULL,'d',0),(61,'Lire les fiches d\'intervention','ficheinter',1,'lire',NULL,'r',1),(62,'Creer/modifier les fiches d\'intervention','ficheinter',1,'creer',NULL,'w',0),(64,'Supprimer les fiches d\'intervention','ficheinter',1,'supprimer',NULL,'d',0),(67,'Exporter les fiches interventions','ficheinter',1,'export',NULL,'r',0),(71,'Read members\' card','adherent',1,'lire',NULL,'r',1),(72,'Create/modify members (need also user module permissions if member linked to a user)','adherent',1,'creer',NULL,'w',1),(74,'Remove members','adherent',1,'supprimer',NULL,'d',1),(75,'Setup types and attributes of members','adherent',1,'configurer',NULL,'w',1),(76,'Export members','adherent',1,'export',NULL,'r',0),(78,'Read subscriptions','adherent',1,'cotisation','lire','r',1),(79,'Create/modify/remove subscriptions','adherent',1,'cotisation','creer','w',1),(81,'Lire les commandes clients','commande',1,'lire',NULL,'r',1),(82,'Creer/modifier les commandes clients','commande',1,'creer',NULL,'w',0),(84,'Valider les commandes clients','commande',1,'valider',NULL,'d',0),(86,'Envoyer les commandes clients','commande',1,'envoyer',NULL,'d',0),(87,'Cloturer les commandes clients','commande',1,'cloturer',NULL,'d',0),(88,'Annuler les commandes clients','commande',1,'annuler',NULL,'d',0),(89,'Supprimer les commandes clients','commande',1,'supprimer',NULL,'d',0),(91,'Lire les charges','tax',1,'charges','lire','r',1),(92,'Creer/modifier les charges','tax',1,'charges','creer','w',0),(93,'Supprimer les charges','tax',1,'charges','supprimer','d',0),(94,'Exporter les charges','tax',1,'charges','export','r',0),(95,'Lire CA, bilans, resultats','compta',1,'resultat','lire','r',1),(96,'Parametrer la ventilation','compta',1,'ventilation','parametrer','r',0),(97,'Lire les ventilations de factures','compta',1,'ventilation','lire','r',1),(98,'Ventiler les lignes de factures','compta',1,'ventilation','creer','r',0),(101,'Lire les expeditions','expedition',1,'lire',NULL,'r',1),(102,'Creer modifier les expeditions','expedition',1,'creer',NULL,'w',0),(104,'Valider les expeditions','expedition',1,'valider',NULL,'d',0),(109,'Supprimer les expeditions','expedition',1,'supprimer',NULL,'d',0),(111,'Lire les comptes bancaires','banque',1,'lire',NULL,'r',1),(112,'Creer/modifier montant/supprimer ecriture bancaire','banque',1,'modifier',NULL,'w',0),(113,'Configurer les comptes bancaires (creer, gerer categories)','banque',1,'configurer',NULL,'a',0),(114,'Rapprocher les ecritures bancaires','banque',1,'consolidate',NULL,'w',0),(115,'Exporter transactions et releves','banque',1,'export',NULL,'r',0),(116,'Virements entre comptes','banque',1,'transfer',NULL,'w',0),(117,'Gerer les envois de cheques','banque',1,'cheque',NULL,'w',0),(121,'Lire les societes','societe',1,'lire',NULL,'r',1),(122,'Creer modifier les societes','societe',1,'creer',NULL,'w',0),(125,'Supprimer les societes','societe',1,'supprimer',NULL,'d',0),(126,'Exporter les societes','societe',1,'export',NULL,'r',0),(141,'Lire tous les projets et taches (y compris prives qui ne me sont pas affectes)','projet',1,'all','lire','r',0),(142,'Creer/modifier tous les projets et taches (y compris prives qui ne me sont pas affectes)','projet',1,'all','creer','w',0),(144,'Supprimer tous les projets et taches (y compris prives qui ne me sont pas affectes)','projet',1,'all','supprimer','d',0),(161,'Lire les contrats','contrat',1,'lire',NULL,'r',1),(162,'Creer / modifier les contrats','contrat',1,'creer',NULL,'w',0),(163,'Activer un service d\'un contrat','contrat',1,'activer',NULL,'w',0),(164,'Desactiver un service d\'un contrat','contrat',1,'desactiver',NULL,'w',0),(165,'Supprimer un contrat','contrat',1,'supprimer',NULL,'d',0),(171,'Lire les deplacements','deplacement',1,'lire',NULL,'r',1),(172,'Creer/modifier les deplacements','deplacement',1,'creer',NULL,'w',0),(173,'Supprimer les deplacements','deplacement',1,'supprimer',NULL,'d',0),(178,'Exporter les deplacements','deplacement',1,'export',NULL,'d',0),(221,'Consulter les mailings','mailing',1,'lire',NULL,'r',1),(222,'Creer/modifier les mailings (sujet, destinataires...)','mailing',1,'creer',NULL,'w',1),(223,'Valider les mailings (permet leur envoi)','mailing',1,'valider',NULL,'w',0),(229,'Supprimer les mailings)','mailing',1,'supprimer',NULL,'d',1),(241,'Lire les categories','categorie',1,'lire',NULL,'r',1),(242,'Creer/modifier les categories','categorie',1,'creer',NULL,'w',1),(243,'Supprimer les categories','categorie',1,'supprimer',NULL,'d',1),(251,'Consulter les autres utilisateurs','user',1,'user','lire','r',0),(252,'Consulter les permissions des autres utilisateurs','user',1,'user_advance','readperms','r',0),(253,'Creer/modifier utilisateurs internes et externes','user',1,'user','creer','w',0),(254,'Creer/modifier utilisateurs externes seulement','user',1,'user_advance','write','w',0),(255,'Modifier le mot de passe des autres utilisateurs','user',1,'user','password','w',0),(256,'Supprimer ou desactiver les autres utilisateurs','user',1,'user','supprimer','d',0),(262,'Consulter tous les tiers par utilisateurs internes (sinon uniquement si contact commercial). Non effectif pour utilisateurs externes (tjs limités à eux-meme).','societe',1,'client','voir','r',1),(281,'Lire les contacts','societe',1,'contact','lire','r',1),(282,'Creer modifier les contacts','societe',1,'contact','creer','w',0),(283,'Supprimer les contacts','societe',1,'contact','supprimer','d',0),(286,'Exporter les contacts','societe',1,'contact','export','d',0),(331,'Lire les bookmarks','bookmark',1,'lire',NULL,'r',1),(332,'Creer/modifier les bookmarks','bookmark',1,'creer',NULL,'r',0),(333,'Supprimer les bookmarks','bookmark',1,'supprimer',NULL,'r',0),(341,'Consulter ses propres permissions','user',1,'self_advance','readperms','r',1),(342,'Creer/modifier ses propres infos utilisateur','user',1,'self','creer','w',1),(343,'Modifier son propre mot de passe','user',1,'self','password','w',1),(344,'Modifier ses propres permissions','user',1,'self_advance','writeperms','w',1),(351,'Consulter les groupes','user',1,'group_advance','read','r',0),(352,'Consulter les permissions des groupes','user',1,'group_advance','readperms','r',0),(353,'Creer/modifier les groupes et leurs permissions','user',1,'group_advance','write','w',0),(354,'Supprimer ou desactiver les groupes','user',1,'group_advance','delete','d',0),(358,'Exporter les utilisateurs','user',1,'user','export','r',0),(531,'Lire les services','service',1,'lire',NULL,'r',1),(532,'Creer/modifier les services','service',1,'creer',NULL,'w',0),(534,'Supprimer les services','service',1,'supprimer',NULL,'d',0),(536,'Voir/gérer les services cachés','service',1,'hidden',NULL,'r',1),(538,'Exporter les services','service',1,'export',NULL,'r',0),(700,'Lire les dons','don',1,'lire',NULL,'r',1),(701,'Creer/modifier les dons','don',1,'creer',NULL,'w',0),(702,'Supprimer les dons','don',1,'supprimer',NULL,'d',0),(703,'Supprimer les dons','don',1,'supprimer',NULL,'d',0),(1001,'Lire les stocks','stock',1,'lire',NULL,'r',1),(1002,'Creer/Modifier les stocks','stock',1,'creer',NULL,'w',1),(1003,'Supprimer les stocks','stock',1,'supprimer',NULL,'d',1),(1004,'Lire mouvements de stocks','stock',1,'mouvement','lire','r',1),(1005,'Creer/modifier mouvements de stocks','stock',1,'mouvement','creer','w',1),(1101,'Lire les bons de livraison','expedition',1,'livraison','lire','r',1),(1102,'Creer modifier les bons de livraison','expedition',1,'livraison','creer','w',0),(1104,'Valider les bons de livraison','expedition',1,'livraison','valider','d',0),(1109,'Supprimer les bons de livraison','expedition',1,'livraison','supprimer','d',0),(1181,'Consulter les fournisseurs','fournisseur',1,'lire',NULL,'r',1),(1182,'Consulter les commandes fournisseur','fournisseur',1,'commande','lire','r',1),(1183,'Creer une commande fournisseur','fournisseur',1,'commande','creer','w',0),(1184,'Valider une commande fournisseur','fournisseur',1,'commande','valider','w',0),(1185,'Approuver une commande fournisseur','fournisseur',1,'commande','approuver','w',0),(1186,'Commander une commande fournisseur','fournisseur',1,'commande','commander','w',0),(1187,'Receptionner une commande fournisseur','fournisseur',1,'commande','receptionner','d',0),(1188,'Supprimer une commande fournisseur','fournisseur',1,'commande','supprimer','d',0),(1201,'Lire les exports','export',1,'lire',NULL,'r',1),(1202,'Creer/modifier un export','export',1,'creer',NULL,'w',1),(1231,'Consulter les factures fournisseur','fournisseur',1,'facture','lire','r',1),(1232,'Creer une facture fournisseur','fournisseur',1,'facture','creer','w',0),(1233,'Valider une facture fournisseur','fournisseur',1,'facture','valider','w',0),(1234,'Supprimer une facture fournisseur','fournisseur',1,'facture','supprimer','d',0),(1235,'Envoyer les factures par mail','fournisseur',1,'facture','envoyer','a',0),(1236,'Exporter les factures fournisseurs, attributs et reglements','fournisseur',1,'facture','export','r',0),(1251,'Run mass imports of external data (data load)','import',1,'run',NULL,'r',0),(1321,'Exporter les factures clients, attributs et reglements','facture',1,'facture','export','r',0),(1421,'Exporter les commandes clients et attributs','commande',1,'commande','export','r',0),(2401,'Read actions/tasks linked to his account','agenda',1,'myactions','read','r',1),(2402,'Create/modify actions/tasks linked to his account','agenda',1,'myactions','create','w',0),(2403,'Delete actions/tasks linked to his account','agenda',1,'myactions','delete','w',0),(2411,'Read actions/tasks of others','agenda',1,'allactions','read','r',0),(2412,'Create/modify actions/tasks of others','agenda',1,'allactions','create','w',0),(2413,'Delete actions/tasks of others','agenda',1,'allactions','delete','w',0),(2500,'Consulter les documents','ecm',1,'download',NULL,'r',1),(2501,'Soumettre ou supprimer des documents','ecm',1,'upload',NULL,'w',0),(2502,'Soumettre ou supprimer des documents','ecm',1,'upload',NULL,'w',1),(2515,'Administrer les rubriques de documents','ecm',1,'setup',NULL,'w',1);
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
  `ref_ext` varchar(60) DEFAULT NULL,
  `code_client` varchar(15) DEFAULT NULL,
  `code_fournisseur` varchar(15) DEFAULT NULL,
  `code_compta` varchar(15) DEFAULT NULL,
  `code_compta_fournisseur` varchar(15) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `ville` varchar(50) DEFAULT NULL,
  `fk_departement` int(11) DEFAULT '0',
  `fk_pays` int(11) DEFAULT '0',
  `tel` varchar(20) DEFAULT NULL,
  `fax` varchar(20) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `fk_secteur` int(11) DEFAULT '0',
  `fk_effectif` int(11) DEFAULT '0',
  `fk_typent` int(11) DEFAULT '0',
  `fk_forme_juridique` int(11) DEFAULT '0',
  `siren` varchar(16) DEFAULT NULL,
  `siret` varchar(16) DEFAULT NULL,
  `ape` varchar(16) DEFAULT NULL,
  `idprof4` varchar(16) DEFAULT NULL,
  `tva_intra` varchar(20) DEFAULT NULL,
  `capital` double DEFAULT NULL,
  `description` text,
  `fk_stcomm` smallint(6) DEFAULT '0',
  `note` text,
  `services` tinyint(4) DEFAULT '0',
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
  `tva_assuj` tinyint(4) DEFAULT '1',
  `localtax1_assuj` tinyint(4) DEFAULT '0',
  `localtax2_assuj` tinyint(4) DEFAULT '0',
  `gencod` varchar(255) DEFAULT NULL,
  `price_level` int(11) DEFAULT NULL,
  `default_lang` varchar(6) DEFAULT NULL,
  `canvas` varchar(32) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_societe_prefix_comm` (`prefix_comm`,`entity`),
  UNIQUE KEY `uk_societe_code_client` (`code_client`,`entity`),
  KEY `idx_societe_user_creat` (`fk_user_creat`),
  KEY `idx_societe_user_modif` (`fk_user_modif`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe`
--

LOCK TABLES `llx_societe` WRITE;
/*!40000 ALTER TABLE `llx_societe` DISABLE KEYS */;
INSERT INTO `llx_societe` VALUES (1,0,NULL,'2010-07-10 16:35:31','2010-07-08 14:21:44','2010-07-10 18:35:31','ABC and Co',1,NULL,NULL,NULL,'7050','6050','1 alalah road',NULL,'Delhi',297,117,NULL,NULL,NULL,NULL,0,NULL,4,NULL,'','','','','',5000,NULL,1,NULL,0,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'en_IN',NULL,NULL),(2,0,NULL,'2010-07-08 12:23:48','2010-07-08 14:23:48','2010-07-08 14:23:48','Belin SARL',1,NULL,NULL,NULL,NULL,NULL,'11 rue de la paix.','75000','Paris',77,1,NULL,NULL,'dolibarr.fr',NULL,0,NULL,3,54,'123456789','','ACE14','','',10000,NULL,0,NULL,0,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'fr_FR',NULL,NULL),(3,0,NULL,'2010-07-08 20:42:12','2010-07-08 22:42:12','2010-07-08 22:42:12','Spanish Comp',1,NULL,'SPANISHCOMP',NULL,NULL,NULL,'1 via mallere',NULL,'Madrid',123,4,NULL,NULL,NULL,NULL,0,3,4,408,'','','','','',10000,NULL,0,NULL,0,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,'es_AR',NULL,NULL),(4,0,NULL,'2010-07-08 20:48:18','2010-07-08 22:48:18','2010-07-08 22:48:18','Prospector Vaalen',1,NULL,NULL,NULL,NULL,NULL,'',NULL,'Bruxelles',103,2,NULL,NULL,NULL,NULL,0,3,4,201,'12345678','','','','',0,NULL,0,NULL,0,NULL,2,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,0,NULL,'2010-07-08 21:37:56','2010-07-08 23:22:57','2010-07-08 23:37:56','NoCountry Co',1,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,193,NULL,NULL,NULL,NULL,0,NULL,0,NULL,'','','','','',0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,0,NULL,'2010-07-08 22:25:06','2010-07-09 00:15:09','2010-07-09 00:25:06','Swiss customer supplier',1,NULL,NULL,NULL,NULL,NULL,'',NULL,'Genevia',0,6,NULL,NULL,NULL,'abademail@aa.com',0,2,2,601,'','','','','',56000,NULL,0,NULL,0,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,0,NULL,'2010-07-08 23:24:53','2010-07-09 01:24:26','2010-07-09 01:24:26','Generic customer',1,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,7,NULL,NULL,NULL,NULL,0,NULL,8,NULL,'','','','','',0,NULL,0,'Generic customer to use for Point Of Sale module.<br />',0,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,0,NULL,'2010-07-10 12:54:27','2010-07-10 14:54:27','2010-07-10 14:54:27','Client salon',1,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,0,NULL,0,NULL,'','','','','',0,NULL,0,NULL,0,NULL,0,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(9,0,NULL,'2010-07-10 12:55:11','2010-07-10 14:54:44','2010-07-10 14:55:11','Client salon invidivdu',1,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,0,NULL,8,NULL,'','','','','',0,NULL,0,NULL,0,NULL,3,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(10,0,NULL,'2010-07-10 13:13:08','2010-07-10 15:13:08','2010-07-10 15:13:08','Smith Vick',1,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,102,NULL,NULL,NULL,'vsmith@email.com',0,NULL,0,NULL,'','','','','',0,NULL,0,NULL,0,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,0,NULL,'2010-07-11 12:35:22','2010-07-10 18:35:57','2010-07-10 18:36:24','Mon client',1,NULL,NULL,NULL,'7051',NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,0,NULL,0,NULL,'','','','','',0,NULL,0,NULL,0,NULL,3,0,NULL,'PL_LOW',0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(12,0,NULL,'2010-07-11 14:18:08','2010-07-11 16:18:08','2010-07-11 16:18:08','Dupont Alain',1,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,'toto@aa.com',0,NULL,0,NULL,'','','','','',0,NULL,0,NULL,0,NULL,1,0,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(13,0,NULL,'2010-07-11 15:13:20','2010-07-11 17:13:20','2010-07-11 17:13:20','Vendeur de chips',1,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,0,1,NULL,NULL,NULL,NULL,0,NULL,0,NULL,'','','','','',0,NULL,0,NULL,0,NULL,0,1,NULL,NULL,0,0,0,1,1,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
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
  `cp` varchar(10) DEFAULT NULL,
  `ville` varchar(50) DEFAULT NULL,
  `fk_pays` int(11) DEFAULT '0',
  `tel` varchar(20) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe_commerciaux`
--

LOCK TABLES `llx_societe_commerciaux` WRITE;
/*!40000 ALTER TABLE `llx_societe_commerciaux` DISABLE KEYS */;
INSERT INTO `llx_societe_commerciaux` VALUES (1,2,2),(2,3,2);
/*!40000 ALTER TABLE `llx_societe_commerciaux` ENABLE KEYS */;
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
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `price_level` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
  `remise_client` double DEFAULT '0',
  `note` text,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_societe_remise_except`
--

LOCK TABLES `llx_societe_remise_except` WRITE;
/*!40000 ALTER TABLE `llx_societe_remise_except` DISABLE KEYS */;
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
  `bic` varchar(10) DEFAULT NULL,
  `iban_prefix` varchar(34) DEFAULT NULL,
  `domiciliation` varchar(255) DEFAULT NULL,
  `proprio` varchar(60) DEFAULT NULL,
  `adresse_proprio` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
  `civilite` varchar(6) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `cp` varchar(25) DEFAULT NULL,
  `ville` varchar(255) DEFAULT NULL,
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
  `priv` smallint(6) NOT NULL DEFAULT '0',
  `fk_user_creat` int(11) DEFAULT '0',
  `fk_user_modif` int(11) DEFAULT NULL,
  `note` text,
  `default_lang` varchar(6) DEFAULT NULL,
  `canvas` varchar(32) DEFAULT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_socpeople_fk_soc` (`fk_soc`),
  KEY `idx_socpeople_fk_user_creat` (`fk_user_creat`),
  CONSTRAINT `fk_socpeople_fk_soc` FOREIGN KEY (`fk_soc`) REFERENCES `llx_societe` (`rowid`),
  CONSTRAINT `fk_socpeople_user_creat_user_rowid` FOREIGN KEY (`fk_user_creat`) REFERENCES `llx_user` (`rowid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_socpeople`
--

LOCK TABLES `llx_socpeople` WRITE;
/*!40000 ALTER TABLE `llx_socpeople` DISABLE KEYS */;
INSERT INTO `llx_socpeople` VALUES (1,'2010-07-08 14:26:14','2010-07-08 20:45:28',1,1,'MR','Samira','Aljoun','','','',297,117,'2010-07-08','Project leader','','','','','','',0,1,1,'Met during a congress at Dubai',NULL,NULL,NULL),(2,'2010-07-08 22:44:50','2010-07-08 20:59:57',NULL,1,'MR','Freeman','Public','','','',200,11,NULL,'','','','','','','',0,1,1,'A friend that is a free contact not linked to any company',NULL,NULL,NULL),(3,'2010-07-08 22:59:02','2010-07-08 20:59:35',NULL,1,'MR','Freeman','Private','','','',NULL,11,NULL,'','','','','','','',1,1,1,'This is a private contact',NULL,NULL,NULL),(4,'2010-07-09 00:16:58','2010-07-08 22:16:58',6,1,'MR','Rotchield','Evan','','','',NULL,6,NULL,'Bank director','','','','','','',0,1,1,'The bank director',NULL,NULL,NULL),(5,'2010-07-10 14:54:44','2010-07-10 12:54:44',9,1,'','Client salon invidivdu','','','','',NULL,NULL,NULL,'','','','','','','',0,1,1,'',NULL,NULL,NULL);
/*!40000 ALTER TABLE `llx_socpeople` ENABLE KEYS */;
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
  `value` int(11) DEFAULT NULL,
  `price` float(13,4) DEFAULT '0.0000',
  `type_mouvement` smallint(6) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `label` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_stock_mouvement_fk_product` (`fk_product`),
  KEY `idx_stock_mouvement_fk_entrepot` (`fk_entrepot`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_stock_mouvement`
--

LOCK TABLES `llx_stock_mouvement` WRITE;
/*!40000 ALTER TABLE `llx_stock_mouvement` DISABLE KEYS */;
INSERT INTO `llx_stock_mouvement` VALUES (1,'2010-07-08 22:43:51','2010-07-09 00:43:51',2,2,1000,0.0000,0,1,'Correct stock'),(3,'2010-07-10 22:56:18','2010-07-11 00:56:18',4,2,500,0.0000,0,1,'Init'),(4,'2010-07-10 23:02:20','2010-07-11 01:02:20',4,2,500,0.0000,0,1,''),(5,'2010-07-11 16:49:44','2010-07-11 18:49:44',4,1,2,10.0000,3,1,''),(6,'2010-07-11 16:49:44','2010-07-11 18:49:44',1,1,4,0.0000,3,1,'');
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
  `ref_ext` varchar(30) DEFAULT NULL,
  `pass` varchar(32) DEFAULT NULL,
  `pass_crypted` varchar(128) DEFAULT NULL,
  `pass_temp` varchar(32) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `office_phone` varchar(20) DEFAULT NULL,
  `office_fax` varchar(20) DEFAULT NULL,
  `user_mobile` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
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
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_user_login` (`login`,`entity`),
  UNIQUE KEY `uk_user_fk_socpeople` (`fk_socpeople`),
  UNIQUE KEY `uk_user_fk_member` (`fk_member`),
  KEY `uk_user_fk_societe` (`fk_societe`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_user`
--

LOCK TABLES `llx_user` WRITE;
/*!40000 ALTER TABLE `llx_user` DISABLE KEYS */;
INSERT INTO `llx_user` VALUES (1,'2010-07-08 13:20:11','2010-07-18 09:37:04','admin',0,NULL,'admin','21232f297a57a5a743894a0e4a801fc3',NULL,'SuperAdminName','Firstname','','','','',1,'','','',1,1,NULL,NULL,NULL,'','2011-02-06 12:20:59','2010-07-22 01:19:21',NULL,'',1,NULL,NULL,NULL),(2,'2010-07-08 13:54:48','2010-07-08 11:54:48','demo',1,NULL,'demo','fe01ce2a7fbac8fafaed7c982a04e229',NULL,'John','Doe','09123123','','','johndoe@mycompany.com',0,'','','',1,1,NULL,NULL,NULL,'','2010-07-08 14:12:02',NULL,NULL,'',1,NULL,NULL,NULL),(3,'2010-07-11 16:18:59','2010-07-11 14:18:59','adupont',1,NULL,'sng2bdf6','c4cb8e25c3d261afac062a8d1274212c',NULL,'Dupont','Alain','','','','toto@aa.com',0,'','','',1,1,NULL,NULL,2,'',NULL,NULL,NULL,'',1,NULL,NULL,NULL);
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
INSERT INTO `llx_user_param` VALUES (0,1,'MAIN_BOXES_0','1'),(1,1,'MAIN_BOXES_0','1');
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
) ENGINE=InnoDB AUTO_INCREMENT=2013 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_user_rights`
--

LOCK TABLES `llx_user_rights` WRITE;
/*!40000 ALTER TABLE `llx_user_rights` DISABLE KEYS */;
INSERT INTO `llx_user_rights` VALUES (1888,1,11),(1879,1,12),(1881,1,13),(1883,1,14),(1885,1,15),(1887,1,16),(1889,1,19),(1924,1,21),(1915,1,22),(1917,1,24),(1919,1,25),(1921,1,26),(1923,1,27),(1925,1,28),(1911,1,31),(1908,1,32),(1910,1,36),(1912,1,38),(1778,1,41),(1777,1,42),(1779,1,44),(1790,1,61),(1787,1,62),(1789,1,64),(1791,1,67),(1678,1,71),(1673,1,72),(1675,1,74),(1679,1,75),(1677,1,76),(1681,1,78),(1682,1,79),(1995,1,81),(1986,1,82),(1988,1,84),(1990,1,86),(1992,1,87),(1994,1,88),(1996,1,89),(1656,1,91),(1653,1,92),(1655,1,93),(1657,1,94),(1601,1,95),(1602,1,96),(1605,1,97),(1606,1,98),(1975,1,101),(1972,1,102),(1974,1,104),(1976,1,109),(1875,1,111),(1866,1,112),(1868,1,113),(1870,1,114),(1872,1,115),(1874,1,116),(1876,1,117),(2003,1,121),(2000,1,122),(2002,1,125),(2004,1,126),(1783,1,141),(1782,1,142),(1784,1,144),(1719,1,161),(1714,1,162),(1716,1,163),(1718,1,164),(1720,1,165),(1585,1,170),(1582,1,171),(1584,1,172),(1586,1,178),(1693,1,221),(1690,1,222),(1692,1,223),(1694,1,229),(1686,1,241),(1685,1,242),(1687,1,243),(1616,1,251),(1609,1,252),(1611,1,253),(1613,1,254),(1614,1,255),(1615,1,256),(1617,1,258),(2005,1,262),(2011,1,281),(2008,1,282),(2010,1,283),(2012,1,286),(1763,1,331),(1762,1,332),(1764,1,333),(1626,1,531),(1623,1,532),(1625,1,536),(1627,1,538),(1734,1,700),(1733,1,701),(1735,1,702),(1755,1,1001),(1754,1,1002),(1756,1,1003),(1758,1,1004),(1759,1,1005),(1982,1,1101),(1979,1,1102),(1981,1,1104),(1983,1,1109),(1628,1,1181),(1640,1,1182),(1631,1,1183),(1633,1,1184),(1635,1,1185),(1637,1,1186),(1639,1,1187),(1641,1,1188),(1578,1,1201),(1579,1,1202),(1649,1,1231),(1644,1,1232),(1646,1,1233),(1648,1,1234),(1650,1,1236),(1736,1,1251),(1890,1,1321),(1997,1,1421),(1705,1,2401),(1704,1,2402),(1706,1,2403),(1710,1,2411),(1709,1,2412),(1711,1,2413),(1618,1,2500),(1619,1,2501),(1620,1,2515),(132,2,11),(133,2,12),(134,2,13),(135,2,14),(136,2,16),(137,2,19),(138,2,21),(139,2,22),(140,2,24),(141,2,25),(142,2,26),(143,2,27),(144,2,31),(145,2,32),(146,2,36),(147,2,41),(148,2,42),(149,2,44),(150,2,61),(151,2,62),(152,2,64),(153,2,71),(154,2,72),(155,2,74),(156,2,75),(157,2,78),(158,2,79),(159,2,81),(160,2,82),(161,2,84),(162,2,86),(163,2,87),(164,2,88),(165,2,89),(166,2,91),(167,2,92),(168,2,93),(169,2,101),(170,2,102),(171,2,104),(172,2,109),(173,2,111),(174,2,112),(175,2,113),(176,2,114),(177,2,116),(178,2,117),(179,2,121),(180,2,122),(181,2,125),(182,2,141),(183,2,142),(184,2,144),(185,2,161),(186,2,162),(187,2,163),(188,2,164),(189,2,165),(190,2,170),(191,2,171),(192,2,172),(193,2,221),(194,2,222),(195,2,229),(196,2,241),(197,2,242),(198,2,243),(199,2,251),(200,2,255),(201,2,262),(202,2,281),(203,2,282),(204,2,283),(205,2,331),(206,2,531),(207,2,532),(208,2,536),(209,2,700),(210,2,701),(211,2,702),(212,2,1001),(213,2,1002),(214,2,1003),(215,2,1004),(216,2,1005),(217,2,1101),(218,2,1102),(219,2,1104),(220,2,1109),(221,2,1181),(222,2,1182),(223,2,1183),(224,2,1184),(225,2,1185),(226,2,1186),(227,2,1187),(228,2,1188),(229,2,1201),(230,2,1202),(231,2,1231),(232,2,1232),(233,2,1233),(234,2,1234),(235,2,1421),(236,2,2401),(237,2,2402),(238,2,2403),(239,2,2411),(240,2,2412),(241,2,2413),(242,2,2500),(243,2,2515),(1807,3,11),(1808,3,31),(1809,3,36),(1810,3,41),(1811,3,61),(1812,3,71),(1813,3,72),(1814,3,74),(1815,3,75),(1816,3,78),(1817,3,79),(1818,3,91),(1819,3,95),(1820,3,97),(1821,3,111),(1822,3,121),(1823,3,122),(1824,3,125),(1825,3,161),(1826,3,170),(1827,3,171),(1828,3,172),(1829,3,221),(1830,3,222),(1831,3,229),(1832,3,241),(1833,3,242),(1834,3,243),(1835,3,251),(1836,3,255),(1837,3,256),(1838,3,262),(1839,3,281),(1840,3,282),(1841,3,283),(1842,3,331),(1843,3,531),(1844,3,536),(1845,3,700),(1846,3,1001),(1847,3,1002),(1848,3,1003),(1849,3,1004),(1850,3,1005),(1851,3,1181),(1852,3,1182),(1853,3,1201),(1854,3,1202),(1855,3,1231),(1856,3,2401),(1857,3,2402),(1858,3,2403),(1859,3,2411),(1860,3,2412),(1861,3,2413),(1862,3,2500),(1863,3,2515);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_usergroup`
--

LOCK TABLES `llx_usergroup` WRITE;
/*!40000 ALTER TABLE `llx_usergroup` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_usergroup_rights`
--

LOCK TABLES `llx_usergroup_rights` WRITE;
/*!40000 ALTER TABLE `llx_usergroup_rights` DISABLE KEYS */;
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
  `fk_user` int(11) NOT NULL,
  `fk_usergroup` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `fk_user` (`fk_user`,`fk_usergroup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `llx_usergroup_user`
--

LOCK TABLES `llx_usergroup_user` WRITE;
/*!40000 ALTER TABLE `llx_usergroup_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `llx_usergroup_user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2011-02-06 12:32:18
