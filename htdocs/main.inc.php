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
$conf = new Conf();


// Definition des caractéristiques de la base de données
if (!strlen(getenv("LLX_DBNAME")))
{
  $conf->db->type = $dolibarr_main_db_type;
  $conf->db->host = $dolibarr_main_db_host;
  $conf->db->name = $dolibarr_main_db_name;
  $conf->db->user = $dolibarr_main_db_user;
  $conf->db->pass = $dolibarr_main_db_pass;
}
// Si type non défini (pour compatibilité avec ancienne install), on
// travail avec mysql
if (! $conf->db->type) { $conf->db->type = 'mysql'; }
    
    
// A terme cette constante sera définie dans la base
define('MAIN_DB_PREFIX','llx_');

require (DOL_DOCUMENT_ROOT ."/lib/".$conf->db->type.".lib.php");
require (DOL_DOCUMENT_ROOT ."/lib/functions.inc.php");
require (DOL_DOCUMENT_ROOT ."/html.form.class.php");
require DOL_DOCUMENT_ROOT ."/user.class.php";


$db = new DoliDb();
$user = new User($db);

clearstatcache();


// Verification du login.
// Cette verification est faite pour chaque accès. Après l'authentification,
// l'objet $user est initialisée. Notament $user->id, $user->login et $user->nom, $user->prenom
// \todo : Stocker les infos de $user en session persistente php et ajouter recup dans le fetch
//        depuis la sessions pour ne pas avoir a acceder a la base a chaque acces de page.
// \todo : Utiliser $user->id pour stocker l'id de l'auteur dans les tables plutot que $_SERVER["REMOTE_USER"]

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

      $aDol = new DOLIAuth("DB", $params, "loginfunction");
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
	   * loginfunction
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
  Header("Location: install/index.php");
}


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

  print '<link rel="top" title="'.$langs->trans("Home").'" href="'.DOL_URL_ROOT.'/">';
  print '<link rel="help" title="'.$langs->trans("Help").'" href="http://www.dolibarr.com/aide.fr.html">';

  print '<link rel="copyright" title="GNU General Public License" href="http://www.gnu.org/copyleft/gpl.html#SEC1">'."\n";
  print '<link rel="author" title="'.$langs->trans("DevelopmentTeam").'" href="http://www.dolibarr.com/dev.fr.html">'."\n";

  print '<link rel="stylesheet" type="text/css" media="print" HREF="'.DOL_URL_ROOT.'/theme/print.css">'."\n";
  print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.'/'.$conf->css.'">'."\n";

  // Definition en laternate style sheet des feuilles de styles les plus maintenues
  print '<link rel="alternate styleSheet" type="text/css" title="Rodolphe" href="'.DOL_URL_ROOT.'/theme/rodolphe/rodolphe.css">'."\n";
  print '<link rel="alternate styleSheet" type="text/css" title="Yellow" href="'.DOL_URL_ROOT.'/theme/yellow/yellow.css">'."\n";
  print '<link rel="alternate styleSheet" type="text/css" title="Eldy" href="'.DOL_URL_ROOT.'/theme/eldy/eldy.css">'."\n";

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
  print '<div class="body">';
  /*
   * Mise à jour entre 2 versions
   *
   */
  
  if (defined("MAIN_NEED_UPDATE"))
    {
      print '<table class="topbarre" width="100%">';
      print "<tr><td>Votre système nécessite d'être mis à jour. ";
      print 'Pour cela cliquez sur <A href="'.DOL_URL_ROOT.'/admin/system/update.php">Mettre à jour</A> !!</td></tr>';
      print "</table>";
    }

  /*
   * Barre de menu supérieure
   *
   */
  print '<div class="tmenu">'."\n";

  // Sommet menu de gauche, lien accueil
  $class="tmenu"; $id="";
  if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "accueil") { $class="tmenu"; $id="sel"; }
  elseif (ereg("^".DOL_URL_ROOT."\/[^\\\/]+$",$_SERVER["PHP_SELF"]) || ereg("^".DOL_URL_ROOT."\/user\/",$_SERVER["PHP_SELF"]) || ereg("^".DOL_URL_ROOT."\/admin\/",$_SERVER["PHP_SELF"])) { $class="tmenu"; $id="sel"; }

  print '<a class="tmenu" id="'.$id.'" href="'.DOL_URL_ROOT.'/index.php"'.($target?" target=$target":"").'>'.$langs->trans("Home").'</a>';

  if (!defined(MAIN_MENU_BARRETOP))
    {
      define("MAIN_MENU_BARRETOP","default.php");
    }
  require(DOL_DOCUMENT_ROOT ."/includes/menus/barre_top/".MAIN_MENU_BARRETOP);


  // Logout

  if (! $_SERVER["REMOTE_USER"])  // Propose ou non de se deloguer si authentication Apache ou non
    {
      print '<a class="login" href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$user->id.'">'.$user->login.'</a>' ;
      print '<a href="'.DOL_URL_ROOT.'/user/logout.php">';
      print '<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png" alt="'.$langs->trans("Logout").'" title="'.$langs->trans("Logout").'"></a>';      
    }
  else
    {
      print $user->login ;
    }
  print "</div><!-- class=tmenu -->\n";
}

/*
 * \brief   Barre de menu gauche
 *
 *
 */
function left_menu($menu, $help_url='', $form_search='', $author='') 
{
  global $user, $conf, $langs;

  /*
   * Colonne de gauche
   *
   */
  print "\n<!-- Debut left menu -->\n";
  print '<div class="vmenu">'."\n";
  
  for ($i = 0 ; $i < sizeof($menu) ; $i++) 
    {
      if (($i%2==0))
	{
	  print '<div class="blockvmenuimpair">'."\n";
	}
      else
	{
	  print '<div class="blockvmenupair">'."\n";
	}
      print '<a class="vmenu" href="'.$menu[$i][0].'">'.$menu[$i][1].'</a><br>';

      for ($j = 2 ; $j < sizeof($menu[$i]) - 1 ; $j = $j +2) 
	{
	  print '<a class="vsmenu" href="'.$menu[$i][$j].'">'.$menu[$i][$j+1].'</a><br>';
	}
      print '</div>';
    }

  /*
   * Affichage des zones de recherche permanantes
   */
  $addzonerecherche=0;
  if ($conf->societe->enabled && defined("MAIN_SEARCHFORM_SOCIETE") && MAIN_SEARCHFORM_SOCIETE > 0) $addzonerecherche=1;
  if ($conf->societe->enabled && defined("MAIN_SEARCHFORM_CONTACT") && MAIN_SEARCHFORM_CONTACT > 0) $addzonerecherche=1;
  if (($conf->produit->enabled || $conf->service->enabled) && defined("MAIN_SEARCHFORM_PRODUITSERVICE") && MAIN_SEARCHFORM_PRODUITSERVICE > 0) $addzonerecherche=1;
  
  if ($addzonerecherche)
    {    
      print '<div class="blockvmenupair">';

      if ($conf->societe->enabled && defined("MAIN_SEARCHFORM_SOCIETE") && MAIN_SEARCHFORM_SOCIETE > 0)
	{
	  $langs->load("companies");
	  
	  if (strstr($_SERVER["SCRIPT_URL"], "/comm/prospect/"))
	    {
	      $url=DOL_URL_ROOT.'/comm/prospect/prospects.php';
	    }
	  else
	    {
	      $url=DOL_URL_ROOT.'/societe.php';
	    }
	  
	  printSearchForm($url,DOL_URL_ROOT.'/comm/clients.php',$langs->trans("Companies"),'soc','socname');
	}
      
      if ($conf->societe->enabled && defined("MAIN_SEARCHFORM_CONTACT") && MAIN_SEARCHFORM_CONTACT > 0)
	{
	  $langs->load("companies");
	  printSearchForm(DOL_URL_ROOT.'/contact/index.php',DOL_URL_ROOT.'/contact/index.php',$langs->trans("Contacts"),'contact','contactname');
	}
      
      if (($conf->produit->enabled || $conf->service->enabled) && defined("MAIN_SEARCHFORM_PRODUITSERVICE") && MAIN_SEARCHFORM_PRODUITSERVICE > 0)
	{
	  $langs->load("products");
	  printSearchForm(DOL_URL_ROOT.'/product/liste.php',DOL_URL_ROOT.'/product/',$langs->trans("Products")."/".$langs->trans("Services"),'products','sall');
	}                  

      print '</div>';
    }
  
  /*
   * Zone de recherche supplémentaire
   */
  
  if (strlen($form_search) > 0)
    {
      print $form_search;
    }
  
  /*
   * Lien vers l'aide en ligne
   */

  if (strlen($help_url) > 0)
    {

      define('MAIN_AIDE_URL','http://www.dolibarr.com/wikidev/index.php');
      print '<a class="leftmenu" target="_blank" href="'.MAIN_AIDE_URL.'/'.$help_url.'">'.$langs->trans("Help").'</a>';
    }

  print "</div>\n";
  print "<!-- Fin left menu -->\n";


  print '<div class="fiche">'."\n";

}



/*
 * \brief   Affiche une zone de recherche
 * \param   urlaction       url du post
 * \param   urlobject       url du lien sur titre de la zone de recherche
 * \param   title           titre de la zone de recherche
 * \param   htmlinputname   nom du champ input du formulaire
 */
function printSearchForm($urlaction,$urlobject,$title,$htmlmodesearch='search',$htmlinputname)
{
  print '<form action="'.$urlaction.'" method="post">';
  print '<a class="vmenu" href="'.$urlobject.'">'.$title.'</a><br>';
  print '<input type="hidden" name="mode" value="search">';
  print '<input type="hidden" name="mode-search" value="'.$htmlmodesearch.'">';
  print '<input type="text" class="flat" name="'.$htmlinputname.'" size="10">&nbsp;';
  print '<input type="submit" class="flat" value="go">';
  print "</form>\n";
}


/*
 * \brief   Impression du pied de page
 * \param   foot    Non utilisé
 */
function llxFooter($foot='') 
{
  global $dolibarr_auto_user;
  print "\n".'</div><!-- div class="fiche" -->'."\n";
  print '</div><!-- div class="body" -->'."\n";
  print "</body>\n</html>\n";
}
?>
