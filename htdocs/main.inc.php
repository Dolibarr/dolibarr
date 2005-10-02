<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/main.inc.php
        \brief      Fichier de formatage générique des écrans Dolibarr
        \version    $Revision$
*/

require("master.inc.php");


// Verification du login.
// Cette verification est faite pour chaque accès. Après l'authentification,
// l'objet $user est initialisée. Notament $user->id, $user->login et $user->nom, $user->prenom
// \todo : Stocker les infos de $user en session persistente php et ajouter recup dans le fetch
//         depuis la sessions pour ne pas avoir a acceder a la base a chaque acces de page.
// \todo : Utiliser $user->id pour stocker l'id de l'auteur dans les tables plutot que $_SERVER["REMOTE_USER"]

if (!empty ($_SERVER["REMOTE_USER"]))
{
    // Authentification Apache OK, on va chercher les infos du user
    $user->fetch($_SERVER["REMOTE_USER"]);
    dolibarr_syslog ("Authentification ok (en mode Basic)");

    //exit;
}
else
{
    // Authentification Apache KO ou non active
    if (!empty ($dolibarr_auto_user))
    {
        // Mode forcé sur un utilisateur (pour debug, demo, ...)
        $user->fetch($dolibarr_auto_user);
        dolibarr_syslog ("Authentification ok (en mode force)");
        if (isset($_POST["loginfunction"]))
        {
            // Si phase de login initial
            $user->update_last_login_date();
        }
    }
    else
    {
        // Pas d'authentification Apache ni de mode forcé, on demande le login
        require_once DOL_DOCUMENT_ROOT."/includes/pear/Auth/Auth.php";

        $pear = $dolibarr_main_db_type.'://'.$dolibarr_main_db_user.':'.$dolibarr_main_db_pass.'@'.$dolibarr_main_db_host.'/'.$dolibarr_main_db_name;

        $params = array(
        "dsn" =>$pear,
        "table" => MAIN_DB_PREFIX."user",
        "usernamecol" => "login",
        "passwordcol" => "pass",
        "cryptType" => "none",
        );

        $aDol = new DOLIAuth("DB", $params, "loginfunction");
        $aDol->setSessionName("DOLSESSID_".$dolibarr_main_db_name);
        $aDol->start();
        $result = $aDol->getAuth();
        if ($result)
        {
            // Authentification Auth OK, on va chercher les infos du user
            $user->fetch($aDol->getUsername());
            dolibarr_syslog ("Authentification ok (en mode Pear)");
            if (isset($_POST["loginfunction"]))
            {
                // Si phase de login initial
                $user->update_last_login_date();
            }
        }
        else
        {
            if (isset($_POST["loginfunction"]))
            {
                // Echec authentification
                dolibarr_syslog("Authentification ko (en mode Pear) pour '".$_POST["username"]."'");
            }
            else 
            {
                // Non authentifié
                dolibarr_syslog("Authentification non réalisé");
            }
            // Le début de la page a été affiché par loginfunction. On ferme juste la page
            print "</div>\n</div>\n</body>\n</html>";
            exit;
        }
    }
}


/*
 * Overwrite configs global par configs perso
 * ------------------------------------------
 */
if (isset($user->conf->SIZE_LISTE_LIMIT) && $user->conf->SIZE_LISTE_LIMIT > 0)
{
    $conf->liste_limit = $user->conf->SIZE_LISTE_LIMIT;
}
if (isset($user->conf->MAIN_LANG_DEFAULT) && $user->conf->MAIN_LANG_DEFAULT)
{
    if ($conf->langage != $user->conf->MAIN_LANG_DEFAULT)
    {
        // Si on a un langage perso différent du langage global
        $conf->langage=dolibarr_set_php_lang($user->conf->MAIN_LANG_DEFAULT);
    
        $langs = new Translate(DOL_DOCUMENT_ROOT ."/langs", $conf->langage);
    }
}
if (isset($user->conf->MAIN_THEME) && $user->conf->MAIN_THEME)
{
    $conf->theme=$user->conf->MAIN_THEME;
    $conf->css  = "theme/".$conf->theme."/".$conf->theme.".css";
    // Si feuille de style en php existe
    if (file_exists(DOL_DOCUMENT_ROOT.'/'.$conf->css.".php")) $conf->css.=".php";
}
if (isset($user->conf->MAIN_DISABLE_JAVASCRIPT) && $user->conf->MAIN_DISABLE_JAVASCRIPT)
{
    $conf->use_javascript=! $user->conf->MAIN_DISABLE_JAVASCRIPT;
}

// Défini gestionnaire de manu à utiliser
if (! $user->societe_id)    // Si utilisateur interne
{
    $conf->top_menu=$conf->global->MAIN_MENU_BARRETOP;
    $conf->left_menu=$conf->global->MAIN_MENU_BARRELEFT;
    // Pour compatibilité
    if ($conf->top_menu == 'eldy.php') $conf->top_menu='eldy_frontoffice.php';
    if ($conf->left_menu == 'eldy.php') $conf->left_menu='eldy_frontoffice.php';
}
else                        // Si utilisateur externe
{
    $conf->top_menu=$conf->global->MAIN_MENUFRONT_BARRETOP;
    $conf->left_menu=$conf->global->MAIN_MENUFRONT_BARRELEFT;
}

// Si le login n'a pu être récupéré, on est identifié avec un compte qui n'existe pas.
// Tentative de hacking ?
if (! $user->login) accessforbidden();


dolibarr_syslog("Access to ".$_SERVER["PHP_SELF"]);


if (! defined('MAIN_INFO_SOCIETE_PAYS'))
{
  define('MAIN_INFO_SOCIETE_PAYS','1');
}

// On charge le fichier lang principal
$langs->load("main");

/*
 *
 */
if (defined("MAIN_NOT_INSTALLED"))
{
  Header("Location: ".DOL_URL_ROOT."/install/index.php");
}

// Constantes utilisées pour définir le nombre de lignes des textarea
if (! eregi("firefox",$_SERVER["HTTP_USER_AGENT"]))
{
    define('ROWS_1',1);
    define('ROWS_2',2);
    define('ROWS_3',3);
}
else
{
    define('ROWS_1',0);
    define('ROWS_2',1);
    define('ROWS_3',2);
}



/**
 *  \brief      Affiche en-tête html
 *  \param      head    lignes d'en-tete head
 *  \param      title   titre page web
 *  \param      target  target du menu Accueil
 */
function top_htmlhead($head, $title="", $target="") 
{
    global $user, $conf, $langs, $db;

    print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
    print "\n<html>";

    print $langs->lang_header();
    print $head;

    // Affiche meta
    print '<meta name="robots" content="noindex,nofollow">'."\n";      // Evite indexation par robots
    print '<meta name="author" content="'.$langs->trans("DevelopmentTeam").'">'."\n";

    // Affiche title
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

    // Affiche style sheets et link
    print '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.'/'.$conf->css.'">'."\n";
    print '<link rel="stylesheet" type="text/css" media="print" HREF="'.DOL_URL_ROOT.'/theme/print.css">'."\n";

    // Definition en alternate style sheet des feuilles de styles les plus maintenues
    // Les navigateurs qui supportent sont rares.
    print '<link rel="alternate stylesheet" type="text/css" title="Eldy" href="'.DOL_URL_ROOT.'/theme/eldy/eldy.css.php">'."\n";
    print '<link rel="alternate stylesheet" type="text/css" title="Freelug" href="'.DOL_URL_ROOT.'/theme/freelug/freelug.css">'."\n";
    print '<link rel="alternate stylesheet" type="text/css" title="Yellow" href="'.DOL_URL_ROOT.'/theme/yellow/yellow.css">'."\n";

    print '<link rel="top" title="'.$langs->trans("Home").'" href="'.DOL_URL_ROOT.'/">'."\n";
    print '<link rel="help" title="'.$langs->trans("Help").'" href="http://www.dolibarr.com/aide.fr.html">'."\n";
    print '<link rel="copyright" title="GNU General Public License" href="http://www.gnu.org/copyleft/gpl.html#SEC1">'."\n";
    print '<link rel="author" title="'.$langs->trans("DevelopmentTeam").'" href="http://www.dolibarr.com/dev.fr.html">'."\n";

    print "</head>\n";
}
  
/**
 *  \brief      Affiche en-tête html + la barre de menu supérieure
 *  \param      head    lignes d'en-tete head
 *  \param      title   titre page web
 *  \param      target  target du menu Accueil
 */
function top_menu($head, $title="", $target="") 
{
    global $user, $conf, $langs, $db;

    top_htmlhead($head, $title, $target);

    print '<body>';
    print '<div class="body">';

    /*
     * Si la constante MAIN_NEED_UPDATE est définie (par le script de migration sql en général), c'est que
     * les données ont besoin d'un remaniement. Il faut passer le update.php
     */
    if (defined("MAIN_NEED_UPDATE") && MAIN_NEED_UPDATE)
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
     */
    print '<div class="tmenu">'."\n";

    // Charge le gestionnaire des entrées de menu du haut
    require_once(DOL_DOCUMENT_ROOT ."/includes/menus/barre_top/".$conf->top_menu);
    $menutop = new MenuTop($db);
    $menutop->atarget=$target;

    // Affiche le menu
    $menutop->showmenu();

    // Lien sur fiche du login
    print '<a class="login" href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$user->id.'"';
    print $menutop->atarget?(' target="'.$menutop->atarget.'"'):'';
    print '>'.$user->login.'</a>';

    // Lien logout
    if (! isset($_SERVER["REMOTE_USER"]) || ! $_SERVER["REMOTE_USER"])
    {
        print '<a href="'.DOL_URL_ROOT.'/user/logout.php"';
        print $menutop->atarget?(' target="'.$menutop->atarget.'"'):'';
        print '>';
        print '<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png" alt="'.$langs->trans("Logout").'" title="'.$langs->trans("Logout").'"></a>';
    }

    print "</div><!-- class=tmenu -->\n";

}


/**
 *  \brief      Affiche barre de menu gauche
 *  \param      menu_array      Tableau des entrée de menu
 *  \param      help_url        Url pour le lien aide ('' par defaut)
 *  \param      form_search     Formulaire de recherche permanant supplémentaire
 */
function left_menu($menu_array, $help_url='', $form_search='')
{
    global $user, $conf, $langs, $db;

    print '<div class="vmenuplusfiche">'."\n";
    print "\n";

    // Colonne de gauche
    print '<!-- Debut left vertical menu -->'."\n";
    print '<div class="vmenu">'."\n";


    // Autres entrées du menu par le gestionnaire
    require_once(DOL_DOCUMENT_ROOT ."/includes/menus/barre_left/".$conf->left_menu);
    $menu=new MenuLeft($db,$menu_array);
    $menu->showmenu();


    // Affichage des zones de recherche permanantes
    $addzonerecherche=0;
    if ($conf->societe->enabled && $conf->global->MAIN_SEARCHFORM_SOCIETE) $addzonerecherche=1;
    if ($conf->societe->enabled && $conf->global->MAIN_SEARCHFORM_CONTACT) $addzonerecherche=1;
    if (($conf->produit->enabled || $conf->service->enabled) && $conf->global->MAIN_SEARCHFORM_PRODUITSERVICE) $addzonerecherche=1;

    if ($addzonerecherche  && ($user->rights->societe->lire || $user->rights->produit->lire))
    {
        print '<div class="blockvmenupair">';

        if ($conf->societe->enabled && $conf->global->MAIN_SEARCHFORM_SOCIETE && $user->rights->societe->lire)
        {
            $langs->load("companies");
            printSearchForm(DOL_URL_ROOT.'/societe.php',DOL_URL_ROOT.'/societe.php',$langs->trans("Companies"),'soc','socname');
        }

        if ($conf->societe->enabled && $conf->global->MAIN_SEARCHFORM_CONTACT && $user->rights->societe->lire)
        {
            $langs->load("companies");
            printSearchForm(DOL_URL_ROOT.'/contact/index.php',DOL_URL_ROOT.'/contact/index.php',$langs->trans("Contacts"),'contact','contactname');
        }

        if (($conf->produit->enabled || $conf->service->enabled) && $conf->global->MAIN_SEARCHFORM_PRODUITSERVICE && $user->rights->produit->lire)
        {
            $langs->load("products");
            printSearchForm(DOL_URL_ROOT.'/product/liste.php',DOL_URL_ROOT.'/product/index.php',$langs->trans("Products")."/".$langs->trans("Services"),'products','sall');
        }

        /*
        if ($conf->categorie->enabled)
        {
        $langs->load("categories");
        printSearchForm(DOL_URL_ROOT.'/categories/search.php',DOL_URL_ROOT.'/categories/',$langs->trans("Categories"),'categories','catname');
        }
        */

        print '</div>';
    }

    // Zone de recherche supplémentaire
    if ($form_search)
    {
        print $form_search;
    }

    // Lien vers l'aide en ligne (uniquement si langue fr_FR)
    if ($help_url)
    {
        $helpbaseurl='';
        if ($langs->defaultlang == "fr_FR") $helpbaseurl='http://www.dolibarr.com/wikidev/index.php/%s';

        if ($helpbaseurl) print '<div class="help"><a class="help" target="_blank" href="'.sprintf($helpbaseurl,$help_url).'">'.$langs->trans("Help").'</a></div>';
    }

    if (MAIN_SHOW_BUGTRACK_LINK == 1)
    {
        // Lien vers le bugtrack
        $bugbaseurl='http://savannah.nongnu.org/bugs/?';
        $bugbaseurl.='func=additem&group=dolibarr&privacy=1&';
        $bugbaseurl.="&details=";
        $bugbaseurl.=urlencode("\n\n\n\n\n-------------\n");
        $bugbaseurl.=urlencode($langs->trans("Version").": ".DOL_VERSION."\n");
        $bugbaseurl.=urlencode($langs->trans("Server").": ".$_SERVER["SERVER_SOFTWARE"]."\n");
        $bugbaseurl.=urlencode($langs->trans("Url").": ".$_SERVER["REQUEST_URI"]."\n");
        print '<div class="help"><a class="help" target="_blank" href="'.$bugbaseurl.'">'.$langs->trans("FindBug").'</a></div>';
    }
    print "\n";
    print "</div>\n";
    print "<!-- Fin left vertical menu -->\n";

    print "\n";
    print '</div>'."\n";
    print '<div class="vmenuplusfiche">'."\n";
    print "\n";
    
    print '<!-- fiche -->'."\n";
    print '<div class="fiche">'."\n";

}



/**
 * \brief   Affiche une zone de recherche
 * \param   urlaction       url du post
 * \param   urlobject       url du lien sur titre de la zone de recherche
 * \param   title           titre de la zone de recherche
 * \param   htmlmodesearch  'search'
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


/**
 * \brief   Impression du pied de page
 * \param   foot    Non utilisé
 */
 
function llxFooter($foot='') 
{
  global $dolibarr_auto_user;

  print "\n</div>\n".'<!-- end div class="fiche" -->'."\n";
  print "\n</div>\n".'<!-- end div class="vmenuplusfiche" -->';
  print "\n</div>\n".'<!-- end div class="body" -->'."\n";

  print "</body>\n</html>\n";
}
?>
