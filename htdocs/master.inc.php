<?PHP
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier			  <benoit.mortier@opensides.be>
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
 *
 */
define('DOL_VERSION','1.2.0-DEV');


if (! @include_once("conf/conf.php"))
{
  Header("Location: install/index.php");
}
else
{
  if (! isset($dolibarr_main_db_host))
    {
      Header("Location: install/index.php");
    }
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

require (DOL_DOCUMENT_ROOT."/conf/conf.class.php");
/*
 * Doit figurer aprés l'inclusion de conf.class.php pour overider certaines variables, à terme conf.class.php devra etre un fichier qui ne sera pas modifié par l'utilisateur
 */
$conf = new Conf();
if (!strlen(getenv("LLX_DBNAME")))
{
  $conf->db->host = $dolibarr_main_db_host;
  $conf->db->name = $dolibarr_main_db_name;
  $conf->db->user = $dolibarr_main_db_user;
  $conf->db->pass = $dolibarr_main_db_pass;
  $conf->db->type = $dolibarr_main_db_type;
}


// Si type non défini (pour compatibilité avec ancienne install), on
// travail avec mysql
if (! $conf->db->type) { $conf->db->type = 'mysql'; }


// A terme cette constante sera définie dans la base
define('MAIN_DB_PREFIX','llx_');

require (DOL_DOCUMENT_ROOT ."/lib/".$dolibarr_main_db_type.".lib.php");
require (DOL_DOCUMENT_ROOT ."/lib/functions.inc.php");
require (DOL_DOCUMENT_ROOT ."/html.form.class.php");
require DOL_DOCUMENT_ROOT ."/user.class.php";
//require "Smarty.class.php";


$db = new DoliDb();
$user = new User($db);

clearstatcache();


require (DOL_DOCUMENT_ROOT ."/product.class.php");
require (DOL_DOCUMENT_ROOT ."/menu.class.php");
require (DOL_DOCUMENT_ROOT ."/societe.class.php");
require (DOL_DOCUMENT_ROOT ."/boxes.php");
require (DOL_DOCUMENT_ROOT ."/address.class.php");
require (DOL_DOCUMENT_ROOT ."/notify.class.php");
require (DOL_DOCUMENT_ROOT ."/includes/fpdf/fpdf152/fpdf.php");

define('FPDF_FONTPATH',DOL_DOCUMENT_ROOT .'/includes/fpdf/fpdf152/font/');

/*
 * Definition de toutes les Constantes globales d'environnement
 *
 */
$sql = "SELECT name, value FROM ".MAIN_DB_PREFIX."const";
$result = $db->query($sql);
if ($result) 
{
  $numr = $db->num_rows();
  $i = 0;
  
  while ($i < $numr)
    {
      $objp = $db->fetch_object( $i);
      define ("$objp->name", $objp->value);
      $i++;
    }
}

/*
 * Positionne le langage et localisation dans $conf->langage
 * et charge l'objet de traduction
 */
if (! defined(MAIN_LANG_DEFAULT))
{
  define(MAIN_LANG_DEFAULT,"fr_FR");
}
$conf->langage=MAIN_LANG_DEFAULT;

// On corrige $conf->language si il ne vaut pas le code long: fr_FR par exemple
if (strlen($conf->langage) <= 3) {
    $conf->langage = strtolower($conf->langage)."_".strtoupper($conf->langage);
}
setlocale(LC_ALL, $conf->langage);
//setlocale(LC_TIME, $conf->language);

require (DOL_DOCUMENT_ROOT ."/translate.class.php");
$langs = new Translate(DOL_DOCUMENT_ROOT ."/langs", $conf->langage);



/*
 * Activation des modules
 * et inclusion de librairies dépendantes
 */
if (defined("MAIN_MODULE_EXTERNALRSS"))
{
  $conf->externalrss->enabled=MAIN_MODULE_EXTERNALRSS;
}
if (defined("MAIN_MODULE_COMMANDE"))
{
  $conf->commande->enabled=MAIN_MODULE_COMMANDE;
}
if (defined("MAIN_MODULE_EXPEDITION"))
{
  $conf->expedition->enabled=MAIN_MODULE_EXPEDITION;
}
if (defined("MAIN_MODULE_SOCIETE"))
{
  $conf->societe->enabled=MAIN_MODULE_SOCIETE; 
}
if (defined("MAIN_MODULE_COMMERCIAL"))
{
  $conf->commercial->enabled=MAIN_MODULE_COMMERCIAL;
}
if (defined("MAIN_MODULE_COMPTABILITE"))
{
  $conf->compta->enabled=MAIN_MODULE_COMPTABILITE;
}
if (defined("MAIN_MODULE_BANQUE"))
{
  $conf->banque->enabled=MAIN_MODULE_BANQUE;
}
if (defined("MAIN_MODULE_CAISSE"))
{
  $conf->caisse->enabled=MAIN_MODULE_CAISSE;
}
if (defined("MAIN_MODULE_DON"))
{
  $conf->don->enabled=MAIN_MODULE_DON;
}
if (defined("MAIN_MODULE_FOURNISSEUR"))
{
  $conf->fournisseur->enabled=MAIN_MODULE_FOURNISSEUR;
}
if (defined("MAIN_MODULE_FICHEINTER"))
{
  require (DOL_DOCUMENT_ROOT ."/includes/modules/fichinter/modules_fichinter.php");
  $conf->fichinter->enabled=MAIN_MODULE_FICHEINTER;
}
if (defined("MAIN_MODULE_ADHERENT"))
{
  $conf->adherent->enabled=MAIN_MODULE_ADHERENT;
}
if (defined("MAIN_MODULE_PRODUIT"))
{
  $conf->produit->enabled=MAIN_MODULE_PRODUIT;
}
if (defined("MAIN_MODULE_SERVICE"))
{
  $conf->service->enabled=MAIN_MODULE_SERVICE;
}
if (defined("MAIN_MODULE_STOCK"))
{
  $conf->stock->enabled=MAIN_MODULE_STOCK;
}
if (defined("MAIN_MODULE_CONTRAT"))
{
  $conf->contrat->enabled=MAIN_MODULE_CONTRAT;
}
if (defined("MAIN_MODULE_BOUTIQUE"))
{
  $conf->boutique->enabled=MAIN_MODULE_BOUTIQUE;
}
if (defined("MAIN_MODULE_PROJET"))
{
  $conf->projet->enabled=MAIN_MODULE_PROJET;
}
if (defined("BOUTIQUE_LIVRE"))
{
  $conf->boutique->livre->enabled=BOUTIQUE_LIVRE;
}
if (defined("BOUTIQUE_ALBUM"))
{
  $conf->boutique->album->enabled=BOUTIQUE_ALBUM;
}
if (defined("MAIN_MODULE_POSTNUKE"))
{
  $conf->postnuke->enabled=MAIN_MODULE_POSTNUKE;
}
if (defined("MAIN_MODULE_WEBCALENDAR"))
{
  $conf->webcal->enabled=MAIN_MODULE_WEBCALENDAR;
}
if (defined("MAIN_MODULE_FACTURE"))
{
  $conf->facture->enabled=MAIN_MODULE_FACTURE;
  require (DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");
}

if (defined("MAIN_MODULE_PROPALE"))
{
  $conf->propal->enabled=MAIN_MODULE_PROPALE;

  require (DOL_DOCUMENT_ROOT ."/includes/modules/propale/modules_propale.php");
  
  if (! defined("PROPALE_OUTPUTDIR"))
    {
      define('PROPALE_OUTPUTDIR', DOL_DOCUMENT_ROOT . "/document/propale/");
    }
  if (! defined("PROPALE_OUTPUT_URL"))
    {
      define('PROPALE_OUTPUT_URL', "/document/propale");
    }

  if (!defined("PROPALE_NEW_FORM_NB_PRODUCT"))
    {
      define("PROPALE_NEW_FORM_NB_PRODUCT", 4);
    }
}


/*
 * Modification de quelques variable de conf en fonction des Constantes
 */

if (defined("MAIN_MONNAIE")) {
	$conf->monnaie=MAIN_MONNAIE;
}
else {
	$conf->monnaie='euros';	
	define("MAIN_MONNAIE",'euros');		// TODO Virer cette ligne et remplacer dans le code le MAIN_MONNAIE par $conf->monnaie
}

/*
 * Option du module Compta: Defini le mode de calcul du CA
 */
$conf->compta->mode = 'RECETTES-DEPENSES';	// Par défaut
if (defined("COMPTA_MODE")) {
	$conf->compta->mode = COMPTA_MODE; 		// Peut etre 'CREANCES-DETTES' pour un CA en creances-dettes
}

/*
 * Option du module Facture
 */
if (defined("FACTURE_TVAOPTION") && FACTURE_TVAOPTION == 'franchise') {
	$conf->defaulttx='0';		# Taux par défaut des factures clients
}
else {
	$conf->defaulttx='';		# Pas de taux par défaut des factures clients, le premier sera pris
}

/*
 * SIZE_LISTE_LIMIT : constante de taille maximale des listes
 */
if (defined("SIZE_LISTE_LIMIT"))
{
  $conf->liste_limit=SIZE_LISTE_LIMIT;
}
else
{
  $conf->liste_limit=20;
}
if ($user->limite_liste > 0)
{
  $conf->liste_limit = $user->limite_liste;
}

if (defined("MAIN_THEME"))
{
  $conf->theme=MAIN_THEME;
  $conf->css  = "theme/".$conf->theme."/".$conf->theme.".css";
}
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


/*
 */
$bc[0]="class=\"impair\"";
$bc[1]="class=\"pair\"";


?>
