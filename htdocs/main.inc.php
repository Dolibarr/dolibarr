<?PHP
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
/*
 *
 *
 *
$GLOBALS['sessionid'] = isset($_GET['sessionid']) ? $_GET['sessionid'] : $_COOKIE['sessionid'];
if(!$GLOBALS['sessionid'])
{
  Header('Location: /login.php');
  exit;
}
*
 *
 *
 */

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
}

// A terme cette constante sera définie dans la base
define('MAIN_DB_PREFIX','llx_');

require (DOL_DOCUMENT_ROOT ."/lib/mysql.lib.php");
require (DOL_DOCUMENT_ROOT ."/lib/functions.inc.php");
require (DOL_DOCUMENT_ROOT ."/html.form.class.php");
require DOL_DOCUMENT_ROOT ."/user.class.php";
//require "Smarty.class.php";


$db = new DoliDb();
$user = new User($db);

clearstatcache();


// Verification du login.
// Cette verification est faite pour chaque accès. Après l'authentification,
// l'objet $user est initialisée. Notament $user->id, $user->login et $user->nom, $user->prenom
// TODO : Stocker les infos de $user en session persistente php et ajouter recup dans le fetch
//        depuis la sessions pour ne pas avoir a acceder a la base a chaque acces de page.
// TODO : Utiliser $user->id pour stocker l'id de l'auteur dans les tables plutot que $_SERVER["REMOTE_USER"]

if (!empty ($_SERVER["REMOTE_USER"]))
{
    // Authentification Apache OK, on va chercher les infos du user
    $user->fetch($_SERVER["REMOTE_USER"]);
}  
else
{
    // Authentification Apache OK ou non active
  if (!empty ($dolibarr_auto_user))
    {
      $user->fetch($dolibarr_auto_user);
    }
  else
    {
      // /usr/share/pear
      
      //require_once "Auth/Auth.php";
      require_once DOL_DOCUMENT_ROOT."/includes/pear/Auth/Auth.php";

      $params = array(
		      "dsn" => $conf->db->getdsn(),
		      "table" => MAIN_DB_PREFIX."user",
		      "usernamecol" => "login",
		      "passwordcol" => "pass",
		      "cryptType" => "none",
		      );
      $aDol = new DOLIAuth("DB", $params, "loginFunction");
      $aDol->start();
      $result = $aDol->getAuth();
      if ($result)
	{ 
        // Authentification Auth OK, on va chercher les infos du user
        $user->fetch($aDol->getUsername());
	}
      else
	{
	  /*
	   * Le début de la page est affiché par
	   * loginFunction
	   */
	  print "</div>\n</div>\n</body>\n</html>";
	  die ;	  
	}
    }
}

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
 *
 */
if (defined("MAIN_NOT_INSTALLED"))
{
  Header("Location: install.php");
}


/*
 * Activation des modules
 * et inclusion de librairies dépendantes
 */
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

  if (defined("FACTURE_ADDON"))
    if (is_readable(DOL_DOCUMENT_ROOT ."/includes/modules/facture/".FACTURE_ADDON."/".FACTURE_ADDON.".modules.php"))
      require(DOL_DOCUMENT_ROOT ."/includes/modules/facture/".FACTURE_ADDON."/".FACTURE_ADDON.".modules.php");

  if (defined("FACTURE_ADDON_PDF"))
    if (is_readable(DOL_DOCUMENT_ROOT ."/includes/modules/facture/pdf_".FACTURE_ADDON_PDF.".modules.php"))
      require(DOL_DOCUMENT_ROOT ."/includes/modules/facture/pdf_".FACTURE_ADDON_PDF.".modules.php");
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


/*
 * Barre de menu supérieure
 *
 */

function top_menu($head, $title="", $target="") 
{
  global $user, $conf, $langs;

  print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
  print "\n<html>";

  $langs->load("main");
  print $langs->lang_header();
  print $head;

  print '<link rel="top" title="Accueil" href="'.DOL_URL_ROOT.'/">';
  print '<link rel="help" title="Aide" href="http://www.dolibarr.com/aide.fr.html">';

  print '<link rel="copyright" title="GNU General Public License" href="http://www.gnu.org/copyleft/gpl.html#SEC1">'."\n";
  print '<link rel="author" title="Equipe de développement" href="http://www.dolibarr.com/dev.fr.html">'."\n";

  print '<link rel="stylesheet" type="text/css" media="print" HREF="'.DOL_URL_ROOT.'/theme/print.css">'."\n";
  print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.'/'.$conf->css.'">'."\n";

  // TODO implementer les alternate css
  print '<link rel="alternate styleSheet" type="text/css" title="Rodolphe" href="'.DOL_URL_ROOT.'/theme/rodolphe/rodolphe.css">'."\n";
  print '<link rel="alternate styleSheet" type="text/css" title="Yellow" href="'.DOL_URL_ROOT.'/theme/yellow/yellow.css">'."\n";

  if (strlen($title) > 0)
    {
      print '<title>Dolibarr - '.$title.'</title>';
    }
  else
    {
      if (defined("MAIN_TITLE"))
	{
	  print "<title>".MAIN_TITLE."</title>";
	}
      else
	{
	  print '<title>Dolibarr</title>';
	}
    }
  print "\n";

  print "</head>\n";
  print '<body>';

  /*
   * Mise à jour entre 2 versions
   *
   */
  
  if (defined("MAIN_NEED_UPDATE"))
    {
      print '<table class="topbarre" width="100%">';
      print "<tr><td>Votre système nécessite d'être mis à jour. ";
      print "Pour cela ";
      print 'cliquez sur <A href="'.DOL_URL_ROOT.'/admin/system/update.php">Mettre à jour</A> !!</td></tr>';
      print "</table>";
    }

  /*
   * Barre superieure
   *
   */

  print '<table class="topbarre" width="100%">';
  print "<tr>";

  // Sommet menu de gauche, lien accueil
  global $PHP_SELF;
  $class="";
  if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "accueil") { $class="menusel"; }
  elseif (ereg("^".DOL_URL_ROOT."\/[^\\\/]+$",$PHP_SELF) || ereg("^".DOL_URL_ROOT."\/user\/",$PHP_SELF) || ereg("^".DOL_URL_ROOT."\/admin\/",$PHP_SELF)) { $class="menusel"; }
  print '<td width="200" class="menu"><table cellpadding=0 cellspacing=0 width="100%"><tr><td class="'.$class.'" align=center><a class="'.$class.'" href="'.DOL_URL_ROOT.'/index.php"'.($target?" target=$target":"").'>Accueil</a></td></tr></table></td>';

  // Sommet géré par gestionnaire de menu du haut
  print '<td class="menu">';
  if (!defined(MAIN_MENU_BARRETOP))
    {
      define("MAIN_MENU_BARRETOP","default.php");
    }
  require(DOL_DOCUMENT_ROOT ."/includes/menus/barre_top/".MAIN_MENU_BARRETOP);
  print '</td>';

  // Logout
  print '<td width="120" class="menu" align="center" valign="center">' ;
  if (! $_SERVER["REMOTE_USER"])  // Propose ou non de se deloguer si authentication Apache ou non
    {
      print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$user->id.'">'.$user->login.'</a>' ;

      print '<a href="'.DOL_URL_ROOT.'/user/logout.php">';
      print '<img border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png" alt="Logout" title="Logout"></a>';

    }
  else
    {
      print $user->login ;
    }
  print '</td>';
  
  print "</tr>\n";

  /*
   * Table principale
   *
   */
  print '<tr><td valign="top">';

}

/*
 * Barre de menu gauche
 *
 *
 *
 *
 */
Function left_menu($menu, $help_url='', $form_search='', $author='') 
{
  global $user, $conf, $rtplang;

  /*
   * Colonne de gauche
   *
   */
  print "\n<!-- Debut left menu -->\n";

  for ($i = 0 ; $i < sizeof($menu) ; $i++) 
    {
      print '<div class="leftmenu">'."\n";
      print '<a class="leftmenu" href="'.$menu[$i][0].'">'.$menu[$i][1].'</a>';

      for ($j = 2 ; $j < sizeof($menu[$i]) - 1 ; $j = $j +2) 
	{
	  print '<br><a class="leftsubmenu" href="'.$menu[$i][$j].'">'.$menu[$i][$j+1].'</a>';
	}      
      print "</div>\n";
    }

  if ((defined("MAIN_SEARCHFORM_SOCIETE") && MAIN_SEARCHFORM_SOCIETE > 0) || (defined("MAIN_SEARCHFORM_CONTACT") && MAIN_SEARCHFORM_CONTACT > 0))
    {
      print '<div class="leftmenu">'."\n";
      
      if (defined("MAIN_SEARCHFORM_SOCIETE") && MAIN_SEARCHFORM_SOCIETE > 0)
	{
	  if (strstr($_SERVER["SCRIPT_URL"], "/comm/prospect/"))
	  {
	    print '<form action="'.DOL_URL_ROOT.'/comm/prospect/prospects.php">';
	  }
	  else
	  {
	    print '<form action="'.DOL_URL_ROOT.'/societe.php">';
	  }
	  print '<A class="menu" href="'.DOL_URL_ROOT.'/comm/clients.php">Societes</A><br>';
	  print '<input type="hidden" name="mode" value="search">';
	  print '<input type="hidden" name="page" value="0">';
	  print '<input type="hidden" name="mode-search" value="soc">';
	  print '<input type="text" name="socname" class="flat" size="10">&nbsp;';
	  print '<input type="submit" class="flat" value="go">';
	  print "</form>\n";
	}
      
      if (defined("MAIN_SEARCHFORM_CONTACT") && MAIN_SEARCHFORM_CONTACT > 0)
	{
	  print '<form action="'.DOL_URL_ROOT.'/contact/index.php">';
	  print '<A class="menu" href="'.DOL_URL_ROOT.'/contact/index.php">Contacts</A><br>';
	  print '<input type="hidden" name="mode" value="search">';
	  print '<input type="hidden" name="mode-search" value="contact">';
	  print '<input type="text" class="flat" name="contactname" size="10">&nbsp;';
	  print '<input type="submit" class="flat" value="go">';
	  print "</form>\n";
	}

      if ($conf->produit->enabled)
	{
	  print '<form action="'.DOL_URL_ROOT.'/product/liste.php" method="post">';
	  print '<A class="menu" href="'.DOL_URL_ROOT.'/product/">Produits</A><br>';
	  print '<input type="text" class="flat" name="sall" size="10">&nbsp;';
	  print '<input type="submit" class="flat" value="go">';
	  print "</form>\n";
	}
      print "</div>";
    }

  /*
   * Formulaire de recherche
   */

  if (strlen($form_search) > 0)
    {
      print '<div class="leftmenu">';
      print $form_search;
      print '</div>';
    }

  /*
   * Lien vers l'aide en ligne
   */

  if (strlen($help_url) > 0)
    {
      print '<div class="leftmenu">';
      define('MAIN_AIDE_URL','http://www.dolibarr.com/wikidev/index.php');
      print '<a class="leftmenu" target="_blank" href="'.MAIN_AIDE_URL.'/'.$help_url.'">Aide</a></div>';
    }
  /*
   *
   *
   *

   if (is_object($author))
   {
   print '<tr><td class="auteurs">Auteur : ';
   print $author->fullname .'</td></tr>';
   }
  */
  print "<!-- Fin left menu -->\n";
  print "</td>";
  print "<td valign=\"top\" colspan=\"2\">\n";

}
/*
 * Impression du pied de page
 *
 *
 *
 */
function llxFooter($foot='') 
{
  global $dolibarr_auto_user;
  print "\n</td></tr>\n";
  /*
   *
   */
  print "</table>\n";

  print '<p id="powered-by-dolibarr">';
  print '<a href="http://savannah.gnu.org/bugs/?group_id=1915">Bug report</a>&nbsp;';
  //  print '<a href="http://savannah.gnu.org/projects/dolibarr/">Source Code</a>&nbsp;'.$foot.'</p>';
  // Suppression temporaire du footer
  print '<a href="http://savannah.gnu.org/projects/dolibarr/">Source Code</a></p>';
  if (!empty ($dolibarr_auto_user))
    {
  print '<p>
      <a href="http://validator.w3.org/check/referer"><img border="0"
          src="http://www.w3.org/Icons/valid-html40"
          alt="Valid HTML 4.0!" height="31" width="88"></a>
    </p>';
    }
  print "</body></html>";
}
?>
