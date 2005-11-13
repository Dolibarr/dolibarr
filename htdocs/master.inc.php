<?PHP
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@cap-networks.com>
 * Copyright (C) 2005 	   Simon Tosser         <simon@kornog-computing.com> 
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/master.inc.php
        \brief      Fichier de preparation de l'environnement Dolibarr
        \version    $Revision$
*/

define('DOL_VERSION','2.0.0-alpha2');

// La fonction clearstatcache ne doit pas etre appelé de manière globale car ralenti.
// Elle doit etre appelée uniquement par les pages qui ont besoin d'absence de cache,
// comme par exemple document.php
//clearstatcache();     

// Forcage du parametrage PHP error_reporting (Dolibarr non utilisable en mode error E_ALL)
if (function_exists("define_syslog_variables"))
{
    define_syslog_variables();
}
//error_reporting(E_ALL);
error_reporting(E_ALL ^ E_NOTICE);

// Test si install ok
if (! @include_once("conf/conf.php"))
{
    Header("Location: install/index.php");
    exit;
}
else
{
    if (! isset($dolibarr_main_db_host))
    {
        Header("Location: install/index.php");
        exit;
    }
}

if (! isset($dolibarr_main_db_type))
{	
  $dolibarr_main_db_type='mysql';   // Pour compatibilit? avec anciennes configs, si non d?fini, on prend 'mysql'
}
if (! $dolibarr_main_data_root) {
    // Si le r?pertoire documents non d?fini, on utilise celui par d?faut
    $dolibarr_main_data_root=ereg_replace("/htdocs","",$dolibarr_main_document_root);
    $dolibarr_main_data_root.="/documents";
}
define('DOL_DOCUMENT_ROOT', $dolibarr_main_document_root);
define('DOL_DATA_ROOT', $dolibarr_main_data_root);

if (strtolower(substr($dolibarr_main_url_root, 0, 7)) == 'http://')
{
    $uri = substr($dolibarr_main_url_root, 7);
}
if (strtolower(substr($dolibarr_main_url_root, 0, 8)) == 'https://')
{
    $uri = substr($dolibarr_main_url_root, 8);
}
$pos = strstr ($uri, '/');      // $pos contient alors url sans nom domaine
if ($pos == '/') $pos = '';     // si $pos vaut /, on le met a ''
define('DOL_URL_ROOT', $pos);


/*
 * Initialisation de l'objet $conf
 */
require_once(DOL_DOCUMENT_ROOT."/conf/conf.class.php");
$conf = new Conf();
$conf->db->host   = $dolibarr_main_db_host;
$conf->db->name   = $dolibarr_main_db_name;
$conf->db->user   = $dolibarr_main_db_user;
$conf->db->pass   = $dolibarr_main_db_pass;
$conf->db->type   = $dolibarr_main_db_type;
if (! $conf->db->type) { $conf->db->type = 'mysql'; }   // Pour compatibilite avec anciennes configs, si non defini, on prend 'mysql'
// Defini prefix
if (isset($_SERVER["LLX_DBNAME"])) $dolibarr_main_db_prefix=$_SERVER["LLX_DBNAME"];
if (! isset($dolibarr_main_db_prefix) || ! $dolibarr_main_db_prefix) $dolibarr_main_db_prefix='llx_'; 
$conf->db->prefix = $dolibarr_main_db_prefix;
define('MAIN_DB_PREFIX',$dolibarr_main_db_prefix);

/*
 * Chargement des includes principaux
 */
require_once(DOL_DOCUMENT_ROOT ."/lib/".$conf->db->type.".lib.php");
require_once(DOL_DOCUMENT_ROOT ."/lib/functions.inc.php");
require_once(DOL_DOCUMENT_ROOT ."/user.class.php");
require_once(DOL_DOCUMENT_ROOT ."/menu.class.php");
require_once(DOL_DOCUMENT_ROOT ."/html.form.class.php");


$db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name);
if (! $db->connected) {
    dolibarr_print_error($db,"host=".$conf->db->host.", user=".$conf->db->user.", databasename=".$conf->db->name.", ".$db->error);
    exit;   
}


$user = new User($db);

/*
 * Definition de toutes les Constantes globales d'environnement
 * - En constante php (\todo a virer)
 * - En $conf->global->key=value
 */
$sql = "SELECT name, value FROM ".MAIN_DB_PREFIX."const";
$result = $db->query($sql);
if ($result)
{
    $numr = $db->num_rows($result);
    $i = 0;

    while ($i < $numr)
    {
        $objp = $db->fetch_object($result);
        $key=$objp->name;
        $value=stripslashes($objp->value);
        define ("$key", $value);
        $conf->global->$key=$value;
        $i++;
    }
}
$db->free($result);

/*
 * Nettoyage variables des gestionnaires de menu
 * conf->menu_top et conf->menu_left sont d?finis dans main.inc.php (selon user)
 */
if (! $conf->global->MAIN_MENU_BARRETOP) $conf->global->MAIN_MENU_BARRETOP="default.php";
if (! $conf->global->MAIN_MENUFRONT_BARRETOP) $conf->global->MAIN_MENUFRONT_BARRETOP="default.php";
if (! $conf->global->MAIN_MENU_BARRELEFT) $conf->global->MAIN_MENU_BARRELEFT="default.php";
if (! $conf->global->MAIN_MENUFRONT_BARRELEFT) $conf->global->MAIN_MENUFRONT_BARRELEFT="default.php";

/*
 * Charge l'objet de traduction et positionne langage courant global 
 */
if (! $conf->global->MAIN_LANG_DEFAULT) $conf->global->MAIN_LANG_DEFAULT="fr_FR";

require_once(DOL_DOCUMENT_ROOT ."/translate.class.php");
$langs = new Translate(DOL_DOCUMENT_ROOT ."/langs");
$langs->setDefaultLang($conf->global->MAIN_LANG_DEFAULT);
$langs->setPhpLang($conf->global->MAIN_LANG_DEFAULT);


/*
 * Pour utiliser d'autres versions des librairies externes que les
 * versions embarqu?es dans Dolibarr, d?finir les constantes adequates:
 * Pour FPDF:           FPDF_PATH
 * Pour PEAR:           PEAR_PATH
 * Pour PHP_WriteExcel: PHP_WRITEEXCEL_PATH
 * Pour PHPlot:         PHPLOT_PATH
 * Pour MagpieRss:      MAGPIERSS_PATH
 */
if (! defined('FPDF_PATH'))           { define('FPDF_PATH',          DOL_DOCUMENT_ROOT .'/includes/fpdf/fpdf/'); }
if (! defined('PEAR_PATH'))           { define('PEAR_PATH',          DOL_DOCUMENT_ROOT .'/includes/pear/'); }
if (! defined('PHP_WRITEEXCEL_PATH')) { define('PHP_WRITEEXCEL_PATH',DOL_DOCUMENT_ROOT .'/includes/php_writeexcel/'); }
if (! defined('PHPLOT_PATH'))         { define('PHPLOT_PATH',        DOL_DOCUMENT_ROOT .'/includes/phplot/'); }
if (! defined('MAGPIERSS_PATH'))      { define('MAGPIERSS_PATH',     DOL_DOCUMENT_ROOT .'/includes/magpierss/'); }
if (! defined('JPGRAPH_PATH'))        { define('JPGRAPH_PATH',       DOL_DOCUMENT_ROOT .'/includes/jpgraph/'); }
define('FPDF_FONTPATH', FPDF_PATH . 'font/');
define('MAGPIE_DIR', MAGPIERSS_PATH);

// \todo Ajouter la ligne
// require_once(FPDF_PATH . "fpdf.php");
// dans le fichier pdfdetail_standard_modeles du module telephonie afin de pouvoir la suivante
if (defined("MAIN_MODULE_TELEPHONIE") && MAIN_MODULE_TELEPHONIE) require_once(FPDF_PATH . "fpdf.php");

/*
 * Autres parametres globaux de configurations
 */
$conf->users->dir_output=DOL_DATA_ROOT."/users";

/*
 * Utilise dans tous les upload de fichier
 * necessaire pour desactiver dans la demo
 */
if (defined('MAIN_UPLOAD_DOC') && MAIN_UPLOAD_DOC == 1)
{
  $conf->upload = 1;
}
else
{
  $conf->upload = 0;
}


/*
 * Definition des parametres d'activation de module et dependants des modules
 * Chargement d'include selon etat activation des modules
 */

// Module bookmark4u
$conf->bookmark4u->enabled=defined('MAIN_MODULE_BOOKMARK4U')?MAIN_MODULE_BOOKMARK4U:0;
$conf->bookmark->enabled=defined('MAIN_MODULE_BOOKMARK')?MAIN_MODULE_BOOKMARK:0;
// Module deplacement
$conf->deplacement->enabled=defined("MAIN_MODULE_DEPLACEMENT")?MAIN_MODULE_DEPLACEMENT:0;
// Module mailing
$conf->mailing->enabled=defined("MAIN_MODULE_MAILING")?MAIN_MODULE_MAILING:0;
// Module externalrss
$conf->externalrss->enabled=defined("MAIN_MODULE_EXTERNALRSS")?MAIN_MODULE_EXTERNALRSS:0;
// Module commande client
$conf->commande->enabled=defined("MAIN_MODULE_COMMANDE")?MAIN_MODULE_COMMANDE:0;
$conf->commande->dir_output=DOL_DATA_ROOT."/commande";
$conf->commande->dir_images=DOL_DATA_ROOT."/commande/images";
// Module expeditions
$conf->expedition->enabled=defined("MAIN_MODULE_EXPEDITION")?MAIN_MODULE_EXPEDITION:0;
$conf->expedition->dir_output=DOL_DATA_ROOT."/expedition";
$conf->expedition->dir_images=DOL_DATA_ROOT."/expedition/images";
// Module societe
$conf->societe->enabled=defined("MAIN_MODULE_SOCIETE")?MAIN_MODULE_SOCIETE:0;
$conf->societe->dir_output=DOL_DATA_ROOT."/societe";
$conf->societe->dir_images=DOL_DATA_ROOT."/societe/images";
if (defined('SOCIETE_OUTPUTDIR') && SOCIETE_OUTPUTDIR) { $conf->societe->dir_output=SOCIETE_OUTPUTDIR; }    # Pour passer outre le rep par d?faut
// Module commercial
$conf->commercial->enabled=defined("MAIN_MODULE_COMMERCIAL")?MAIN_MODULE_COMMERCIAL:0;
$conf->commercial->dir_output=DOL_DATA_ROOT."/rapport";
// Module comptaexpert
$conf->comptaexpert->enabled=defined("MAIN_MODULE_COMPTABILITE_EXPERT")?MAIN_MODULE_COMPTABILITE_EXPERT:0;
$conf->comptaexpert->dir_output=DOL_DATA_ROOT."/comptaexpert";
$conf->comptaexpert->dir_images=DOL_DATA_ROOT."/comptaexpert/images";
// Module compta
$conf->compta->enabled=defined("MAIN_MODULE_COMPTABILITE")?MAIN_MODULE_COMPTABILITE:0;
$conf->compta->dir_output=DOL_DATA_ROOT."/compta";
$conf->compta->dir_images=DOL_DATA_ROOT."/compta/images";
// Module banque
$conf->banque->enabled=defined("MAIN_MODULE_BANQUE")?MAIN_MODULE_BANQUE:0;
$conf->banque->dir_output=DOL_DATA_ROOT."/banque";
$conf->banque->dir_images=DOL_DATA_ROOT."/banque/images";
// Module don
$conf->don->enabled=defined("MAIN_MODULE_DON")?MAIN_MODULE_DON:0;
$conf->don->dir_output=DOL_DATA_ROOT."/dons";
$conf->don->dir_images=DOL_DATA_ROOT."/dons/images";
// Module syslog
$conf->syslog->enabled=defined("MAIN_MODULE_SYSLOG")?MAIN_MODULE_SYSLOG:0;
// Module fournisseur
$conf->fournisseur->enabled=defined("MAIN_MODULE_FOURNISSEUR")?MAIN_MODULE_FOURNISSEUR:0;
// Module ficheinter
$conf->fichinter->enabled=defined("MAIN_MODULE_FICHEINTER")?MAIN_MODULE_FICHEINTER:0;
$conf->fichinter->dir_output=DOL_DATA_ROOT."/ficheinter";
$conf->fichinter->dir_images=DOL_DATA_ROOT."/ficheinter/images";
if (defined('FICHEINTER_OUTPUTDIR') && FICHEINTER_OUTPUTDIR) { $conf->fichinter->dir_output=FICHEINTER_OUTPUTDIR; }    # Pour passer outre le rep par defaut
// Module adherent
$conf->adherent->enabled=defined("MAIN_MODULE_ADHERENT")?MAIN_MODULE_ADHERENT:0;
$conf->adherent->dir_output=DOL_DATA_ROOT."/adherent";
// Module produit
$conf->produit->enabled=defined("MAIN_MODULE_PRODUIT")?MAIN_MODULE_PRODUIT:0;
$conf->produit->dir_output=DOL_DATA_ROOT."/produit";
$conf->produit->dir_images=DOL_DATA_ROOT."/produit/images";
// Module service
$conf->service->enabled=defined("MAIN_MODULE_SERVICE")?MAIN_MODULE_SERVICE:0;
$conf->service->dir_output=DOL_DATA_ROOT."/produit";
$conf->service->dir_images=DOL_DATA_ROOT."/produit/images";
// Module stock
$conf->stock->enabled=defined("MAIN_MODULE_STOCK")?MAIN_MODULE_STOCK:0;
// Module code barre
$conf->barcode->enabled=defined("MAIN_MODULE_BARCODE")?MAIN_MODULE_BARCODE:0;
// Module categorie
$conf->categorie->enabled=defined("MAIN_MODULE_CATEGORIE")?MAIN_MODULE_CATEGORIE:0;
// Module contrat
$conf->contrat->enabled=defined("MAIN_MODULE_CONTRAT")?MAIN_MODULE_CONTRAT:0;
// Module projet
$conf->projet->enabled=defined("MAIN_MODULE_PROJET")?MAIN_MODULE_PROJET:0;
// Module oscommerce
$conf->boutique->enabled=defined("MAIN_MODULE_BOUTIQUE")?MAIN_MODULE_BOUTIQUE:0;
$conf->boutique->livre->enabled=defined("BOUTIQUE_LIVRE")?BOUTIQUE_LIVRE:0;
$conf->boutique->album->enabled=defined("BOUTIQUE_ALBUM")?BOUTIQUE_ALBUM:0;
// Module postnuke
$conf->postnuke->enabled=defined("MAIN_MODULE_POSTNUKE")?MAIN_MODULE_POSTNUKE:0;
// Module clicktodial
$conf->clicktodial->enabled=defined("MAIN_MODULE_CLICKTODIAL")?MAIN_MODULE_CLICKTODIAL:0;
// Module prelevement
$conf->prelevement->enabled=defined("MAIN_MODULE_PRELEVEMENT")?MAIN_MODULE_PRELEVEMENT:0;
$conf->prelevement->dir_output=DOL_DATA_ROOT."/prelevement";
$conf->prelevement->dir_images=DOL_DATA_ROOT."/prelevement/images";
// Module webcal
$conf->webcal->enabled=defined('MAIN_MODULE_WEBCALENDAR')?MAIN_MODULE_WEBCALENDAR:0;
$conf->webcal->db->type=defined('PHPWEBCALENDAR_TYPE')?PHPWEBCALENDAR_TYPE:'mysql';
$conf->webcal->db->host=defined('PHPWEBCALENDAR_HOST')?PHPWEBCALENDAR_HOST:'';
$conf->webcal->db->user=defined('PHPWEBCALENDAR_USER')?PHPWEBCALENDAR_USER:'';
$conf->webcal->db->pass=defined('PHPWEBCALENDAR_PASS')?PHPWEBCALENDAR_PASS:'';
$conf->webcal->db->name=defined('PHPWEBCALENDAR_DBNAME')?PHPWEBCALENDAR_DBNAME:'';
// Module facture
$conf->facture->enabled=defined("MAIN_MODULE_FACTURE")?MAIN_MODULE_FACTURE:0;
// \todo Ajouter la ligne
// require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");
// dans le fichier facturation-emission.php du module telephonie afin de pouvoir supprimer la suivante
if (defined("MAIN_MODULE_TELEPHONIE") && MAIN_MODULE_TELEPHONIE) require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");
$conf->facture->dir_output=DOL_DATA_ROOT."/facture";
$conf->facture->dir_images=DOL_DATA_ROOT."/facture/images";
if (defined('FAC_OUTPUTDIR') && FAC_OUTPUTDIR) { $conf->facture->dir_output=FAC_OUTPUTDIR; }                # Pour passer outre le rep par defaut
// Module propal
$conf->propal->enabled=defined("MAIN_MODULE_PROPALE")?MAIN_MODULE_PROPALE:0;
if (! defined("PROPALE_NEW_FORM_NB_PRODUCT")) define("PROPALE_NEW_FORM_NB_PRODUCT", 4);
$conf->propal->dir_output=DOL_DATA_ROOT."/propale";
$conf->propal->dir_images=DOL_DATA_ROOT."/propale/images";
if (defined('PROPALE_OUTPUTDIR') && PROPALE_OUTPUTDIR) { $conf->propal->dir_output=PROPALE_OUTPUTDIR; }    # Pour passer outre le rep par defaut
// Module telephonie
$conf->telephonie->enabled=defined("MAIN_MODULE_TELEPHONIE")?MAIN_MODULE_TELEPHONIE:0;
$conf->telephonie->dir_output=DOL_DATA_ROOT."/telephonie";
$conf->telephonie->dir_images=DOL_DATA_ROOT."/telephonie/images";
// Module energie
$conf->energie->enabled=defined("MAIN_MODULE_ENERGIE")?MAIN_MODULE_ENERGIE:0;
// Module domaine
$conf->domaine->enabled=0;
// Module voyage
$conf->voyage->enabled=0;
// Module actionscomm
$conf->actionscomm->dir_output=DOL_DATA_ROOT."/action";


/*
 * Modification de quelques variable de conf en fonction des Constantes
 */

// conf->use_preview_tabs
$conf->use_preview_tabs=1;
if (isset($conf->global->MAIN_USE_PREVIEW_TABS)) $conf->use_preview_tabs=$conf->global->MAIN_USE_PREVIEW_TABS;

// conf->use_javascript
$conf->use_javascript=1;
if (isset($conf->global->MAIN_DISABLE_JAVASCRIPT)) $conf->use_javascript=! $conf->global->MAIN_DISABLE_JAVASCRIPT;

// conf->monnaie
if (! $conf->global->MAIN_MONNAIE) $conf->global->MAIN_MONNAIE='EUR';	
$conf->monnaie=$conf->global->MAIN_MONNAIE;

// $conf->compta->mode = Option du module Compta: Defini le mode de calcul des etats comptables (CA,...)
$conf->compta->mode = 'RECETTES-DEPENSES';  // Par d?faut
if (defined('COMPTA_MODE') && COMPTA_MODE) {
	// Peut etre 'RECETTES-DEPENSES' ou 'CREANCES-DETTES'
    $conf->compta->mode = COMPTA_MODE;
}

// $conf->defaulttx
if (defined('FACTURE_TVAOPTION') && FACTURE_TVAOPTION == 'franchise') {
	$conf->defaulttx='0';		// Taux par d?faut des factures clients
}
else {
	$conf->defaulttx='';		// Pas de taux par d?faut des factures clients, le premier sera pris
}

// $conf->liste_limit = constante de taille maximale des listes
if (! $conf->global->SIZE_LISTE_LIMIT) $conf->global->SIZE_LISTE_LIMIT=20;
$conf->liste_limit=$conf->global->SIZE_LISTE_LIMIT;

// $conf->produit->limit_size = constante de taille maximale des select de produit
if (! isset($conf->global->PRODUIT_LIMIT_SIZE)) $conf->global->PRODUIT_LIMIT_SIZE=50;
$conf->produit->limit_size=$conf->global->PRODUIT_LIMIT_SIZE;

// $conf->theme et $conf->css
if (! $conf->global->MAIN_THEME) $conf->global->MAIN_THEME="eldy";
$conf->theme=$conf->global->MAIN_THEME;
$conf->css  = "theme/".$conf->theme."/".$conf->theme.".css";

// $conf->email_from          = email pour envoi par Dolibarr des mails auto (notifications, ...)
// $conf->mailing->email_from = email pour envoi par Dolibarr des mailings
$conf->email_from="dolibarr-robot@domain.com";
if (defined('MAIN_EMAIL_FROM'))
{
    $conf->email_from=MAIN_EMAIL_FROM;
}
if (defined('MAILING_EMAIL_FROM'))
{
    $conf->mailing->email_from=MAILING_EMAIL_FROM;
}
else $conf->mailing->email_from=$conf->email_from;

// $conf->adherent->email_resil, ...
if (defined("MAIN_MAIL_RESIL"))
{
  $conf->adherent->email_resil=MAIN_MAIL_RESIL;
}
if (defined("MAIN_MAIL_RESIL_SUBJECT"))
{
  $conf->adherent->email_resil_subject=MAIN_MAIL_RESIL_SUBJECT;
}
if (defined("MAIN_MAIL_VALID"))
{
  $conf->adherent->email_valid=MAIN_MAIL_VALID;
}
if (defined("MAIN_MAIL_VALID_SUBJECT"))
{
  $conf->adherent->email_valid_subject=MAIN_MAIL_VALID_SUBJECT;
}
if (defined("MAIN_MAIL_EDIT"))
{
  $conf->adherent->email_edit=MAIN_MAIL_EDIT;
}
if (defined("MAIN_MAIL_EDIT_SUBJECT"))
{
  $conf->adherent->email_edit_subject=MAIN_MAIL_EDIT_SUBJECT;
}
if (defined("MAIN_MAIL_NEW"))
{
  $conf->adherent->email_new=MAIN_MAIL_NEW;
}
if (defined("MAIN_MAIL_NEW_SUBJECT"))
{
  $conf->adherent->email_new_subject=MAIN_MAIL_NEW_SUBJECT;
}

// Format de la date
// \todo Mettre format dans fichier langue
$conf->format_date_text_short="%d %b %Y";
$conf->format_date_short="%d/%m/%Y";


/* 
 * Creation objet mysoc
 * Objet Societe qui contient carac de l'institution gérée par Dolibarr.
 */
require_once(DOL_DOCUMENT_ROOT ."/societe.class.php");
$mysoc=new Societe($db);
$mysoc->id=0;
$mysoc->nom=$conf->global->MAIN_INFO_SOCIETE_NOM;
$mysoc->adresse=$conf->global->MAIN_INFO_SOCIETE_ADRESSE;
$mysoc->cp=$conf->global->MAIN_INFO_SOCIETE_CP;
$mysoc->ville=$conf->global->MAIN_INFO_SOCIETE_VILLE;
$mysoc->pays_code=$conf->global->MAIN_INFO_SOCIETE_PAYS;
$mysoc->tel=$conf->global->MAIN_INFO_SOCIETE_TEL;
$mysoc->fax=$conf->global->MAIN_INFO_SOCIETE_FAX;
$mysoc->url=$conf->global->MAIN_INFO_SOCIETE_WEB;
$mysoc->siren=$conf->global->MAIN_INFO_SIREN;
$mysoc->siret=$conf->global->MAIN_INFO_SIRET;
$mysoc->ape=$conf->global->MAIN_INFO_APE;
$mysoc->rcs=$conf->global->MAIN_INFO_RCS;
$mysoc->tvaintra=$conf->global->MAIN_INFO_TVAINTRA;
$mysoc->capital=$conf->global->MAIN_INFO_CAPITAL;
$mysoc->forme_juridique_code=$conf->global->MAIN_INFO_FORME_JURIDIQUE;
$mysoc->email=$conf->global->MAIN_INFO_SOCIETE_MAIL;


/* \todo Ajouter une option Gestion de la TVA dans le module compta qui permet de d?sactiver la fonction TVA
 * (pour particuliers ou lib?raux en franchise)
 * En attendant, valeur forc?e ? 1
 */
$conf->compta->tva=1;

// Delais de tolerance des alertes
$conf->actions->warning_delay=$conf->global->MAIN_DELAY_ACTIONS_TODO*24*60*60;
$conf->commande->traitement->warning_delay=$conf->global->MAIN_DELAY_ORDERS_TO_PROCESS*24*60*60;
$conf->propal->cloture->warning_delay=$conf->global->MAIN_DELAY_PROPALS_TO_CLOSE*24*60*60;
$conf->propal->facturation->warning_delay=$conf->global->MAIN_DELAY_PROPALS_TO_BILL*24*60*60;
$conf->facture->fournisseur->warning_delay=$conf->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY*24*60*60;
$conf->facture->client->warning_delay=$conf->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED*24*60*60;
$conf->contrat->services->inactifs->warning_delay=$conf->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES*24*60*60;
$conf->contrat->services->expires->warning_delay=$conf->global->MAIN_DELAY_RUNNING_SERVICES*24*60*60;
$conf->adherent->cotisation->warning_delay=$conf->global->MAIN_DELAY_MEMBERS*24*60*60;
$conf->bank->rappro->warning_delay=$conf->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE*24*60*60;


/*
 */
$bc[0]="class=\"impair\"";
$bc[1]="class=\"pair\"";
$yesno[0]="no";
$yesno[1]="yes";

if ( ! defined('MENTION_NPR') ) define('MENTION_NPR','(npr)');
?>
