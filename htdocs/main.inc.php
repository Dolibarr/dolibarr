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

if (! @include_once("conf/conf.php") or ! isset($dolibarr_main_db_host))
{
  print "<center>\n";
  print "<b>Bienvenue sur Dolibarr</b><br><br>\n";
  print "Votre système n'est pas encore configuré, pour procéder à l'installation, cliquer <a href=\"install/\">ici</a>";
  print "</center>\n";
  exit ;  
}

define('DOL_DOCUMENT_ROOT', $dolibarr_main_document_root);

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

//XAVIER DUTOIT 18/09/2003 : si l'utilisateur n'est pas authentifié apache, on essaie pear Auth

if (!empty ($GLOBALS["REMOTE_USER"]))
{
  $user->fetch($GLOBALS["REMOTE_USER"]);
}  
else
{
  if (!empty ($dolibarr_auto_user))
    {
      $user->fetch($dolibarr_auto_user);
    }
  else
    {
      // TODO
      // Tester si auth est installé, et si non renvoyé erreur demandant install
      // d'une area d'authentification apache ou de auth.
      $modules_list = get_loaded_extensions();
      
      $ispearinstalled=0;
      foreach ($modules_list as $module) 
	{
	  if ($module == 'pear') { $ispearinstalled=1; }
	}
      

      $ispearinstalled=1; // MODIF RODO, le test ne marche pas

      if (! $ispearinstalled) {
	print "Pour fonctionner, Dolibarr a besoin :<br>\n";
	print "- Soit du module PHP 'pear' (actuellement, votre php contient les modules suivant: ".join($modules_list,',')."<br>\n";
	print "- Soit d'avoir son répertoire htdocs protégé par une authentification Web basique (Exemple pour Apache: Utilisation des directives Authxxx dans la configuration, ou utilisation du fichier .htaccess).<br>\n";
	print "<br><br>Vous devez respecter un de ces pré-requis pour continuer.\n";
	exit ;  
      }
      
      require_once "Auth/Auth.php";

      $params = array(
		      "dsn" => $conf->db->getdsn(),
		      "table" => MAIN_DB_PREFIX."user",
		      "usernamecol" => "login",
		      "passwordcol" => "pass",
		      "cryptType" => "none",
		      );
      $aDol = new Auth("DB", $params, "loginFunction");
      $aDol->start();
      $result = $aDol->getAuth();
      if ($result)
	{ 
	  $user->fetch($aDol->getUsername());
	}
      else
	{
	  /*
	   * Le début de la page est affiché par
	   * loginFunction
	   */
	  print '</div></div></body></html>';
	  die ;	  
	}
    }
}

require (DOL_DOCUMENT_ROOT ."/product.class.php");
require (DOL_DOCUMENT_ROOT ."/menu.class.php");
require (DOL_DOCUMENT_ROOT ."/societe.class.php");
require (DOL_DOCUMENT_ROOT ."/translate.class.php");
require (DOL_DOCUMENT_ROOT ."/boxes.php");
require (DOL_DOCUMENT_ROOT ."/address.class.php");
require (DOL_DOCUMENT_ROOT ."/notify.class.php");
require (DOL_DOCUMENT_ROOT ."/includes/fpdf/fpdf.php");

define('FPDF_FONTPATH',DOL_DOCUMENT_ROOT .'/includes/fpdf/font/');
/*
 * Definition de toutes les Constantes globales d'environement
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
 *
 *
 */

//$db->close();

if (defined("MAIN_NOT_INSTALLED"))
{
  Header("Location: install.php");
}

/*
 * Inclusion de librairies dépendantes de paramètres de conf
 */
if (defined("MAIN_MODULE_FACTURE") && MAIN_MODULE_FACTURE)
{
  require (DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");

  if (defined("FACTURE_ADDON"))
    if (is_readable(DOL_DOCUMENT_ROOT ."/includes/modules/facture/".FACTURE_ADDON."/".FACTURE_ADDON.".modules.php"))
      require(DOL_DOCUMENT_ROOT ."/includes/modules/facture/".FACTURE_ADDON."/".FACTURE_ADDON.".modules.php");

  if (defined("FACTURE_ADDON_PDF"))
    require(DOL_DOCUMENT_ROOT ."/includes/modules/facture/pdf_".FACTURE_ADDON_PDF.".modules.php");

}

if (defined("MAIN_MODULE_PROPALE") && MAIN_MODULE_PROPALE)
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
 * TODO RODO
 */
define('MAIN_MONNAIE','euros');
// Modification de quelques variable de conf en fonction des Constantes
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

if (defined("FACTURE_TVAOPTION") && FACTURE_TVAOPTION == 'franchise') {
	$conf->defaulttx='0';		# Taux par défaut des factures clients
}
else {
	$conf->defaulttx='';		# Pas de taux par défaut des factures clients, le premier sera pris
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

if (defined("MAIN_MODULE_COMMANDE"))
{
  $conf->commande->enabled=MAIN_MODULE_COMMANDE;
}

if (defined("MAIN_MODULE_SOCIETE") && MAIN_MODULE_SOCIETE)
{
  $conf->societe = 1 ; 
}

if (defined("MAIN_MODULE_COMMERCIAL"))
{
  $conf->commercial->enabled=MAIN_MODULE_COMMERCIAL;
}

if (defined("MAIN_MODULE_COMPTABILITE"))
{
  $conf->compta->enabled=MAIN_MODULE_COMPTABILITE;
}

if (defined("MAIN_MODULE_DON") && MAIN_MODULE_DON)
{
  $conf->don->enabled=MAIN_MODULE_DON;
}

if (defined("MAIN_MODULE_FOURNISSEUR"))
{
  $conf->fournisseur->enabled=MAIN_MODULE_FOURNISSEUR;
}

if (defined("MAIN_MODULE_FICHEINTER") && MAIN_MODULE_FICHEINTER)
{
  require (DOL_DOCUMENT_ROOT ."/includes/modules/fichinter/modules_fichinter.php");

  $conf->fichinter->enabled=MAIN_MODULE_FICHEINTER;
}

if (defined("MAIN_MODULE_COMMANDE") && MAIN_MODULE_COMMANDE)
{
  $conf->commande->enabled=MAIN_MODULE_COMMANDE;
}

if (defined("MAIN_MODULE_ADHERENT"))
{
  $conf->adherent->enabled=MAIN_MODULE_ADHERENT;
}

if (defined("MAIN_MODULE_PRODUIT"))
{
  $conf->produit->enabled=MAIN_MODULE_PRODUIT;
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

/*
 */
if(!isset($application_lang))
{
  $application_lang = "fr";
}
$rtplang = new rtplang(DOL_DOCUMENT_ROOT ."/langs", "en", "en", $application_lang);
$rtplang->debug=1;
/*
 */
$bc[0]="class=\"impair\"";
$bc[1]="class=\"pair\"";

setlocale(LC_TIME, "fr_FR");

/*
 * Barre de menu supérieure
 *
 *
 */

function top_menu($head, $title="") 
{
  global $user, $conf, $rtplang;

  print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
  print "\n<html>";

  print $rtplang->lang_header();

  //  print "<HTML><HEAD>";
  print $head;
  //  print '<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">';
  //  print '<BASE href="'.DOL_URL_ROOT.'/">';

  print '<link rel="top" title="Accueil" href="'.DOL_URL_ROOT.'/">';
  print '<link rel="help" title="Aide" href="http://www.dolibarr.com/aide.fr.html">';

  print '<link rel="copyright" title="GNU General Public License" href="http://www.gnu.org/copyleft/gpl.html#SEC1">'."\n";
  print '<link rel="author" title="Equipe de développement" href="http://www.dolibarr.com/dev.fr.html">'."\n";

  print '<link rel="stylesheet" type="text/css" media="print" HREF="'.DOL_URL_ROOT.'/theme/print.css">'."\n";

  print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.'/'.$conf->css.'">'."\n";
  // TODO implementer les alternate css
  //  print '<link rel="alternate styleSheet" type="text/css" title="yellow" href="'.DOL_URL_ROOT.'/theme/yellow/yellow.css">'."\n";

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
      print 'cliquez sur <A href="'.DOL_URL_ROOT.'/admin/system/update.php">Mise à jour</A> !!</td></tr>';
      print "</table>";
    }

  /*
   * Barre superieure
   *
   */

  print '<table class="topbarre" width="100%">';

  print "<tr>";
  print '<td width="15%" class="menu" align="center"><A class="menu" href="'.DOL_URL_ROOT.'/index.php">Accueil</A></TD>';

  if (!defined(MAIN_MENU_BARRETOP))
    {
      define("MAIN_MENU_BARRETOP","default.php");
    }

  require(DOL_DOCUMENT_ROOT ."/includes/menus/barre_top/".MAIN_MENU_BARRETOP);

  print '<td width="15%" class="menu" align="center">'.strftime(" %d %B - %H:%M",time()).'</TD>';

  print '<td width="10%" class="menu" align="center">' ;

  if (empty ($GLOBALS["REMOTE_USER"]))
    {
      print '<a href="'.DOL_URL_ROOT.'/user/logout.php" title="logout">'.$user->login.'</a>' ;
    }
  else
    {
      print $user->login ;
    }
  print '</td></tr>';

  //    print '</table>';
  /*
   * Table principale
   *
   */
  //  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="3">';

  print '<tr><td valign="top" align="right">';

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
  print "\n".'<table class="leftmenu" border="0" width="100%" cellspacing="1" cellpadding="4">'."\n";


  for ($i = 0 ; $i < sizeof($menu) ; $i++) 
    {

      print '<tr><td class="barre" valign="top">';
      print '<A class="leftmenu" href="'.$menu[$i][0].'">'.$menu[$i][1].'</a>';

      for ($j = 2 ; $j < sizeof($menu[$i]) - 1 ; $j = $j +2) 
	{
	  print '<br>&nbsp;-&nbsp;<a class="submenu" href="'.$menu[$i][$j].'">'.$menu[$i][$j+1].'</a>';
	}
      print '</td></tr>';
      
    }

  if ((defined("MAIN_SEARCHFORM_SOCIETE") && MAIN_SEARCHFORM_SOCIETE > 0) || (defined("MAIN_SEARCHFORM_CONTACT") && MAIN_SEARCHFORM_CONTACT > 0))
    {
      print '<tr><td class="barre" valign="top" align="right">';
      
      if (defined("MAIN_SEARCHFORM_SOCIETE") && MAIN_SEARCHFORM_SOCIETE > 0)
	{
	  print '<form action="'.DOL_URL_ROOT.'/societe.php">';
	  print '<A class="menu" href="'.DOL_URL_ROOT.'/comm/clients.php">Societes</A><br>';
	  print '<input type="hidden" name="mode" value="search">';
	  print '<input type="hidden" name="mode-search" value="soc">';
	  print '<input type="text" name="socname" class="flat" size="10">&nbsp;';
	  print '<input type="submit" class="flat" value="go">';
	  print "</form>\n";
	}
      
      if (defined("MAIN_SEARCHFORM_CONTACT") && MAIN_SEARCHFORM_CONTACT > 0)
	{
	  print '<form action="'.DOL_URL_ROOT.'/comm/contact.php">';
	  print '<A class="menu" href="'.DOL_URL_ROOT.'/comm/contact.php">Contacts</A><br>';
	  print '<input type="hidden" name="mode" value="search">';
	  print '<input type="hidden" name="mode-search" value="contact">';
	  print '<input type="text" class="flat" name="contactname" size="10">&nbsp;';
	  print '<input type="submit" class="flat" value="go">';
	  print "</form>\n";
	}

      if (defined("MAIN_MODULE_PRODUIT") && MAIN_MODULE_PRODUIT > 0)
	{
	  print '<form action="'.DOL_URL_ROOT.'/product/liste.php" method="post">';
	  print '<A class="menu" href="'.DOL_URL_ROOT.'/product/">Produits</A><br>';
	  print '<input type="text" class="flat" name="sall" size="10">&nbsp;';
	  print '<input type="submit" class="flat" value="go">';
	  print "</form>\n";
	}
      print '</td></tr>';
    }

  /*
   * Formulaire de recherche
   */

  if (strlen($form_search) > 0)
    {
      print '<tr><td class="barre" align="right">';
      print $form_search;
      print '</td></tr>';
    }

  /*
   * Lien vers l'aide en ligne
   */

  if (strlen($help_url) > 0)
    {
      define('MAIN_AIDE_URL','http://www.dolibarr.com/documentation/dolibarr-user.html');
      print '<tr><td class="barre"><a target="_blank" href="'.MAIN_AIDE_URL.'/'.$help_url.'">Aide</a></td></tr>';
    }
  /*
   *
   *
   */

  if (is_object($author))
  {

    print '<tr><td class="auteurs">Auteur : ';
    print $author->fullname .'</td></tr>';
  }

  print '</table>'."\n";

  print '</td><td valign="top" width="85%" colspan="6">'."\n";


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
