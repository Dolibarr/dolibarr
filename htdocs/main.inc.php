<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier	<benoit.mortier@opensides.be>
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

/*!
    \file       htdocs/master.inc.php
    \brief      Fichier de formatage générique des ecrans Dolibarr
    \version    $Revision$
*/

require("master.inc.php");

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
			
      $pear = $dolibarr_main_db_type.'://'.$dolibarr_main_db_user.':'.$dolibarr_main_db_pass.'@'.$dolibarr_main_db_host.'/'.$dolibarr_main_db_name;
			
      $params = array(
//		      "dsn" => $conf->db->getdsn(),
		      "dsn" =>$pear, //$db->getdsn($dolibarr_main_db_type,$dolibarr_main_db_user,$dolibarr_main_db_pass,$dolibarr_main_db_host,$dolibarr_main_db_name),
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
				dolibarr_syslog ("auth demarre va chercher les infos du user");
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




/*
 *
 */
if (defined("MAIN_NOT_INSTALLED"))
{
  Header("Location: install/index.php");
}





/*!
 *  \brief      Affiche en-tête html + la barre de menu supérieure
 *  \param      head    lignes d'en-tete head
 *  \param      title   titre page web
 *  \param      head    target du menu Accueil
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
       $langs->load("admin");
      print '<div class="fiche">'."\n";
      print '<table class="noborder" width="100%">';
      print '<tr><td>';
      print $langs->trans("UpdateRequired",DOL_URL_ROOT.'/admin/system/update.php');
      print '</td></tr>';
      print "</table>";
      llxFooter();
      exit;
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

  // Lien sur fiche du login
  print '<a class="login" href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$user->id.'">'.$user->login.'</a>' ;

  // Lien logout
  if (! $_SERVER["REMOTE_USER"])
    {
      print '<a href="'.DOL_URL_ROOT.'/user/logout.php">';
      print '<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png" alt="'.$langs->trans("Logout").'" title="'.$langs->trans("Logout").'"></a>';      
    }

  print "</div><!-- class=tmenu -->\n";


}


/*!
 *  \brief      Affiche barre de menu gauche
 *  \param      menu            Objet du menu gauche
 *  \param      help_url        Url pour le lien aide ('' par defaut)
 *  \param      form_search     Formulaire de recherche permanant
 *  \param      author          Auteur de la page pour ajout en en-tete html
 */
 
function left_menu($menu, $help_url='', $form_search='', $author='') 
{
  global $user, $conf, $langs;

  print '<div class="vmenuplusfiche" width="158">'."\n";

  /*
   * Colonne de gauche
   *
   */
  print "\n<!-- Debut left vertical menu -->\n";
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
      print '<div class="help"><a class="help" target="_blank" href="'.MAIN_AIDE_URL.'/'.$help_url.'">'.$langs->trans("Help").'</a></div>';
    }

  print "</div>\n";
  print "<!-- Fin left vertical menu -->\n";

  print '</div>'."\n";
  print '<div class="vmenuplusfiche">'."\n";

  print '<div class="fiche">'."\n";

}



/*!
 * \brief   Affiche une zone de recherche
 * \param   urlaction       url du post
 * \param   urlobject       url du lien sur titre de la zone de recherche
 * \param   title           titre de la zone de recherche
 * \param   htmlinputname   nom du champ input du formulaire
 */
 
function printSearchForm($urlaction,$urlobject,$title,$htmlmodesearch='search',$htmlinputname)
{
  global $langs;
  print '<form action="'.$urlaction.'" method="post">';
  print '<a class="vmenu" href="'.$urlobject.'">'.$title.'</a><br>';
  print '<input type="hidden" name="mode" value="search">';
  print '<input type="hidden" name="mode-search" value="'.$htmlmodesearch.'">';
  print '<input type="text" class="flat" name="'.$htmlinputname.'" size="10">&nbsp;';
  print '<input type="submit" class="button" value="'.$langs->trans("Go").'">';
  print "</form>";
}


/*!
 * \brief   Impression du pied de page
 * \param   foot    Non utilisé
 */
 
function llxFooter($foot='') 
{
  global $dolibarr_auto_user;

  print '</div><!-- div class="fiche" -->'."\n";

  print "\n".'</div><!-- div class="vmenuplusfiche" -->'."\n";
  print '</div><!-- div class="body" -->'."\n";
  print "</body>\n</html>\n";
}
?>
