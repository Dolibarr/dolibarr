<?PHP
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@cap-networks.com>
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

define_syslog_variables();
clearstatcache();

// Forcage du paramétrage PHP
//error_reporting(E_ALL);               // Dolibarr n'est pas utilisable en mode error E_ALL
error_reporting(E_ALL ^ E_NOTICE);      // Dolibarr n'est pas utilisable en mode error E_ALL

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
  $dolibarr_main_db_type='mysql';   // Pour compatibilité avec anciennes configs, si non défini, on prend 'mysql'
}
if (! $dolibarr_main_data_root) {
    // Si le répertoire documents non défini, on utilise celui par défaut
    $dolibarr_main_data_root=ereg_replace("/htdocs","",$dolibarr_main_document_root);
    $dolibarr_main_data_root.="/documents";
}
define('DOL_DOCUMENT_ROOT', $dolibarr_main_document_root);
define('DOL_DATA_ROOT', $dolibarr_main_data_root);


if (strtolower(substr($dolibarr_main_url_root, 0, 7)) == 'http://')
{
  $uri = substr($dolibarr_main_url_root, 7);
}
if (strtolower(substr($dolibarr_main_url_root, 0, 7)) == 'https:/')
{
  $uri = substr($dolibarr_main_url_root, 8);
}
$pos = strstr ($uri, '/');
if ($pos == '/')
{
  $pos = '';
}
define('DOL_URL_ROOT', $pos);
//define('DOL_URL_ROOT', $dolibarr_main_url_root);


/*
 * Initialisation de l'objet $conf
 */
require_once(DOL_DOCUMENT_ROOT."/conf/conf.class.php");
$conf = new Conf();
if (!strlen(getenv("LLX_DBNAME")))
{
  $conf->db->host = $dolibarr_main_db_host;
  $conf->db->name = $dolibarr_main_db_name;
  $conf->db->user = $dolibarr_main_db_user;
  $conf->db->pass = $dolibarr_main_db_pass;
  $conf->db->type = $dolibarr_main_db_type;
}
if (! $conf->db->type) { $conf->db->type = 'mysql'; }   // Pour compatibilité avec anciennes configs, si non défini, on prend 'mysql'
define('MAIN_DB_PREFIX','llx_');                        // A terme cette constante sera définie dans $conf

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
    dolibarr_print_error($db,"host=".$conf->db->host.", user=".$conf->db->user.", databasename=".$conf->db->name);
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
 * Positionne les gestionnaires de menu
 */
if (! $conf->global->MAIN_MENU_BARRETOP) $conf->global->MAIN_MENU_BARRETOP="default.php";
if (! $conf->global->MAIN_MENU_BARRELEFT) $conf->global->MAIN_MENU_BARRELEFT="default.php";
$conf->top_menu=$conf->global->MAIN_MENU_BARRETOP;
$conf->left_menu=$conf->global->MAIN_MENU_BARRELEFT;

/*
 * Positionne le langage global et localisation dans $conf->langage
 * et charge l'objet de traduction
 */
if (! $conf->global->MAIN_LANG_DEFAULT) $conf->global->MAIN_LANG_DEFAULT="fr_FR";
$conf->langage=dolibarr_set_php_lang($conf->global->MAIN_LANG_DEFAULT);

require_once(DOL_DOCUMENT_ROOT ."/translate.class.php");
$langs = new Translate(DOL_DOCUMENT_ROOT ."/langs", $conf->langage);


/*
 * Pour utiliser d'autres versions des librairies externes que les
 * versions embarquées dans Dolibarr, définir les constantes adequates:
 * Pour FPDF:           FPDF_PATH
 * Pour PEAR:           PEAR_PATH
 * Pour PHP_WriteExcel: PHP_WRITEEXCEL_PATH
 * Pour PHPlot:         PHPLOT_PATH
 * Pour MagpieRss:      MAGPIERSS_PATH
 */
if (! defined('FPDF_PATH'))           { define('FPDF_PATH',          DOL_DOCUMENT_ROOT .'/includes/fpdf/fpdf152/'); }
if (! defined('PEAR_PATH'))           { define('PEAR_PATH',          DOL_DOCUMENT_ROOT .'/includes/pear/'); }
if (! defined('PHP_WRITEEXCEL_PATH')) { define('PHP_WRITEEXCEL_PATH',DOL_DOCUMENT_ROOT .'/includes/php_writeexcel/'); }
if (! defined('PHPLOT_PATH'))         { define('PHPLOT_PATH',        DOL_DOCUMENT_ROOT .'/includes/phplot/'); }
if (! defined('MAGPIERSS_PATH'))      { define('MAGPIERSS_PATH',     DOL_DOCUMENT_ROOT .'/includes/magpierss/'); }
define('FPDF_FONTPATH', FPDF_PATH . 'font/');
define('MAGPIE_DIR', MAGPIERSS_PATH);

// \todo mettre cet include uniquement sur les éléments qui manipulent du PDF
require_once(FPDF_PATH . "fpdf.php");


/*
 * Autres paramètres globaux de configurations
 */
$conf->users->dir_output=DOL_DATA_ROOT."/users";

/*
 * Utilisé dans tous les upload de fichier
 * nécessaire pour désactiver dans la démo
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
 * Définition des paramètres d'activation de module et dépendants des modules
 * Chargement d'include selon etat activation des modules
 */
$conf->bookmark4u->enabled=defined('MAIN_MODULE_BOOKMARK4U')?MAIN_MODULE_BOOKMARK4U:0;
$conf->deplacement->enabled=defined("MAIN_MODULE_DEPLACEMENT")?MAIN_MODULE_DEPLACEMENT:0;
$conf->mailing->enabled=defined("MAIN_MODULE_MAILING")?MAIN_MODULE_MAILING:0;
$conf->externalrss->enabled=defined("MAIN_MODULE_EXTERNALRSS")?MAIN_MODULE_EXTERNALRSS:0;
$conf->commande->enabled=defined("MAIN_MODULE_COMMANDE")?MAIN_MODULE_COMMANDE:0;
$conf->commande->dir_output=DOL_DATA_ROOT."/commande";
$conf->commande->dir_images=DOL_DATA_ROOT."/commande/images";
$conf->expedition->enabled=defined("MAIN_MODULE_EXPEDITION")?MAIN_MODULE_EXPEDITION:0;
$conf->expedition->dir_output=DOL_DATA_ROOT."/expedition";
$conf->expedition->dir_images=DOL_DATA_ROOT."/expedition/images";
$conf->societe->enabled=defined("MAIN_MODULE_SOCIETE")?MAIN_MODULE_SOCIETE:0;
if ($conf->societe->enabled) require_once(DOL_DOCUMENT_ROOT ."/societe.class.php");
$conf->societe->dir_output=DOL_DATA_ROOT."/societe";
$conf->societe->dir_images=DOL_DATA_ROOT."/societe/images";
if (defined('SOCIETE_OUTPUTDIR') && SOCIETE_OUTPUTDIR) { $conf->societe->dir_output=SOCIETE_OUTPUTDIR; }    # Pour passer outre le rep par défaut
$conf->commercial->enabled=defined("MAIN_MODULE_COMMERCIAL")?MAIN_MODULE_COMMERCIAL:0;
$conf->commercial->dir_output=DOL_DATA_ROOT."/rapport";
$conf->comptaexpert->enabled=defined("MAIN_MODULE_COMPTABILITE_EXPERT")?MAIN_MODULE_COMPTABILITE_EXPERT:0;
$conf->comptaexpert->dir_output=DOL_DATA_ROOT."/comptaexpert";
$conf->comptaexpert->dir_images=DOL_DATA_ROOT."/comptaexpert/images";
$conf->compta->enabled=defined("MAIN_MODULE_COMPTABILITE")?MAIN_MODULE_COMPTABILITE:0;
$conf->compta->dir_output=DOL_DATA_ROOT."/compta";
$conf->compta->dir_images=DOL_DATA_ROOT."/compta/images";
$conf->banque->enabled=defined("MAIN_MODULE_BANQUE")?MAIN_MODULE_BANQUE:0;
$conf->caisse->enabled=defined("MAIN_MODULE_CAISSE")?MAIN_MODULE_CAISSE:0;
$conf->don->enabled=defined("MAIN_MODULE_DON")?MAIN_MODULE_DON:0;
$conf->syslog->enabled=defined("MAIN_MODULE_SYSLOG")?MAIN_MODULE_SYSLOG:0;
$conf->fournisseur->enabled=defined("MAIN_MODULE_FOURNISSEUR")?MAIN_MODULE_FOURNISSEUR:0;
$conf->fichinter->enabled=defined("MAIN_MODULE_FICHEINTER")?MAIN_MODULE_FICHEINTER:0;
if ($conf->fichinter->enabled) require_once(DOL_DOCUMENT_ROOT ."/includes/modules/fichinter/modules_fichinter.php");
$conf->fichinter->dir_output=DOL_DATA_ROOT."/ficheinter";
$conf->fichinter->dir_images=DOL_DATA_ROOT."/ficheinter/images";
if (defined('FICHEINTER_OUTPUTDIR') && FICHEINTER_OUTPUTDIR) { $conf->fichinter->dir_output=FICHEINTER_OUTPUTDIR; }    # Pour passer outre le rep par défaut
$conf->adherent->enabled=defined("MAIN_MODULE_ADHERENT")?MAIN_MODULE_ADHERENT:0;
$conf->adherent->dir_output=DOL_DATA_ROOT."/adherent";
$conf->produit->enabled=defined("MAIN_MODULE_PRODUIT")?MAIN_MODULE_PRODUIT:0;
if ($conf->produit->enabled) require_once(DOL_DOCUMENT_ROOT ."/product.class.php");
$conf->produit->dir_output=DOL_DATA_ROOT."/produit";
$conf->produit->dir_images=DOL_DATA_ROOT."/produit/images";
$conf->barcode->enabled=defined("MAIN_MODULE_BARCODE")?MAIN_MODULE_BARCODE:0;
$conf->categorie->enabled=defined("MAIN_MODULE_CATEGORIE")?MAIN_MODULE_CATEGORIE:0;
$conf->service->enabled=defined("MAIN_MODULE_SERVICE")?MAIN_MODULE_SERVICE:0;
if ($conf->service->enabled) require_once(DOL_DOCUMENT_ROOT ."/product.class.php");
$conf->service->dir_output=DOL_DATA_ROOT."/produit";
$conf->service->dir_images=DOL_DATA_ROOT."/produit/images";
$conf->stock->enabled=defined("MAIN_MODULE_STOCK")?MAIN_MODULE_STOCK:0;
$conf->contrat->enabled=defined("MAIN_MODULE_CONTRAT")?MAIN_MODULE_CONTRAT:0;
$conf->boutique->enabled=defined("MAIN_MODULE_BOUTIQUE")?MAIN_MODULE_BOUTIQUE:0;
$conf->projet->enabled=defined("MAIN_MODULE_PROJET")?MAIN_MODULE_PROJET:0;
$conf->boutique->livre->enabled=defined("BOUTIQUE_LIVRE")?BOUTIQUE_LIVRE:0;
$conf->boutique->album->enabled=defined("BOUTIQUE_ALBUM")?BOUTIQUE_ALBUM:0;
$conf->postnuke->enabled=defined("MAIN_MODULE_POSTNUKE")?MAIN_MODULE_POSTNUKE:0;
$conf->clicktodial->enabled=defined("MAIN_MODULE_CLICKTODIAL")?MAIN_MODULE_CLICKTODIAL:0;
$conf->telephonie->enabled=defined("MAIN_MODULE_TELEPHONIE")?MAIN_MODULE_TELEPHONIE:0;
$conf->telephonie->dir_output=DOL_DATA_ROOT."/telephonie";
$conf->telephonie->dir_images=DOL_DATA_ROOT."/telephonie/images";
$conf->prelevement->enabled=defined("MAIN_MODULE_PRELEVEMENT")?MAIN_MODULE_PRELEVEMENT:0;
$conf->prelevement->dir_output=DOL_DATA_ROOT."/prelevement";
$conf->prelevement->dir_images=DOL_DATA_ROOT."/prelevement/images";

$conf->energie->enabled=defined("MAIN_MODULE_ENERGIE")?MAIN_MODULE_ENERGIE:0;

$conf->webcal->enabled=defined('MAIN_MODULE_WEBCALENDAR')?MAIN_MODULE_WEBCALENDAR:0;
$conf->webcal->db->type=defined('PHPWEBCALENDAR_TYPE')?PHPWEBCALENDAR_TYPE:'mysql';
$conf->webcal->db->host=defined('PHPWEBCALENDAR_HOST')?PHPWEBCALENDAR_HOST:'';
$conf->webcal->db->user=defined('PHPWEBCALENDAR_USER')?PHPWEBCALENDAR_USER:'';
$conf->webcal->db->pass=defined('PHPWEBCALENDAR_PASS')?PHPWEBCALENDAR_PASS:'';
$conf->webcal->db->name=defined('PHPWEBCALENDAR_DBNAME')?PHPWEBCALENDAR_DBNAME:'';
$conf->webcal->syncro=defined('PHPWEBCALENDAR_SYNCRO')?PHPWEBCALENDAR_SYNCRO:'';
$conf->facture->enabled=defined("MAIN_MODULE_FACTURE")?MAIN_MODULE_FACTURE:0;
if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");
$conf->facture->dir_output=DOL_DATA_ROOT."/facture";
$conf->facture->dir_images=DOL_DATA_ROOT."/facture/images";
if (defined('FAC_OUTPUTDIR') && FAC_OUTPUTDIR) { $conf->facture->dir_output=FAC_OUTPUTDIR; }                # Pour passer outre le rep par défaut
$conf->propal->enabled=defined("MAIN_MODULE_PROPALE")?MAIN_MODULE_PROPALE:0;
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propale/modules_propale.php");
if (!defined("PROPALE_NEW_FORM_NB_PRODUCT")) define("PROPALE_NEW_FORM_NB_PRODUCT", 4);
$conf->propal->dir_output=DOL_DATA_ROOT."/propale";
$conf->propal->dir_images=DOL_DATA_ROOT."/propale/images";
if (defined('PROPALE_OUTPUTDIR') && PROPALE_OUTPUTDIR) { $conf->propal->dir_output=PROPALE_OUTPUTDIR; }    # Pour passer outre le rep par défaut
$conf->domaine->enabled=0;
$conf->voyage->enabled=0;


/*
 * Modification de quelques variable de conf en fonction des Constantes
 */
if (! $conf->global->MAIN_MONNAIE) $conf->global->MAIN_MONNAIE='EUR';	
$conf->monnaie=$conf->global->MAIN_MONNAIE;

/*
 * Option du module Compta: Defini le mode de calcul des etats comptables (CA,...)
 */
$conf->compta->mode = 'RECETTES-DEPENSES';  // Par défaut
if (defined('COMPTA_MODE') && COMPTA_MODE) {
	// Peut etre 'RECETTES-DEPENSES' ou 'CREANCES-DETTES'
    $conf->compta->mode = COMPTA_MODE;
}

/* \todo Ajouter une option Gestion de la TVA dans le module compta qui permet de désactiver la fonction TVA
 * (pour particuliers ou libéraux en franchise)
 * En attendant, valeur forcée à 1
 */
$conf->compta->tva=1;

/*
 * Option du module Facture
 */
if (defined('FACTURE_TVAOPTION') && FACTURE_TVAOPTION == 'franchise') {
	$conf->defaulttx='0';		// Taux par défaut des factures clients
}
else {
	$conf->defaulttx='';		// Pas de taux par défaut des factures clients, le premier sera pris
}

/*
 * SIZE_LISTE_LIMIT : constante de taille maximale des listes
 */
if (! $conf->global->SIZE_LISTE_LIMIT) $conf->global->SIZE_LISTE_LIMIT=20;
$conf->liste_limit=$conf->global->SIZE_LISTE_LIMIT;

/*
 * MAIN_THEME : theme
 */
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

// Delai de tolerance des alertes
$conf->actions->warning_delay=7*24*60*60;   // 1 semaine
$conf->commande->traitement->warning_delay=2*24*60*60;
$conf->propal->cloture->warning_delay=31*24*60*60;
$conf->propal->facturation->warning_delay=7*24*60*60;
$conf->facture->fournisseur->warning_delay=2*24*60*60;
$conf->facture->client->warning_delay=31*24*60*60;   // 1 mois
$conf->contrat->services->inactifs->warning_delay=0*24*60*60;
$conf->contrat->services->expires->warning_delay=0*24*60*60;
$conf->adherent->cotisation->warning_delay=31*24*60*60;   // 1 mois
$conf->bank->rappro->warning_delay=2*31*24*60*60;   // 2 mois

/*
 */
$bc[0]="class=\"impair\"";
$bc[1]="class=\"pair\"";
$yesno[0]="no";
$yesno[1]="yes";

?>
