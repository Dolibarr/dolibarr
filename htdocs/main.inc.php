<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
        \brief      Fichier de formatage generique des ecrans Dolibarr
        \version    $Revision$
*/

// Pour le tuning optionnel. Activer si la variable d'environnement DOL_TUNING
// est positionne A appeler avant tout.
if (isset($_SERVER['DOL_TUNING'])) $micro_start_time=microtime(true);


// Forcage du parametrage PHP magic_quots_gpc et nettoyage des parametres
// (Sinon il faudrait a chaque POST, conditionner
// la lecture de variable par stripslashes selon etat de get_magic_quotes).
// En mode off (recommande il faut juste faire addslashes au moment d'un insert/update.
@set_magic_quotes_runtime(0);
function stripslashes_deep($value)
{
   return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
}
if (get_magic_quotes_gpc())
{
   $_GET    = array_map('stripslashes_deep', $_GET);
   $_POST  = array_map('stripslashes_deep', $_POST);
   $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
   $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}


require_once("master.inc.php");


$bc[0]="class=\"impair\"";
$bc[1]="class=\"pair\"";



/*
 * Phase identification
 */

// $authmode contient la liste des différents modes d'identification à tester
// par ordre de préférence. Attention, rares sont les combinaisons possibles si
// plusieurs modes sont indiqués.
// Exemple: array('http','dolibarr');
// Exemple: array('ldap');
$authmode=array('http','dolibarr');
if (isset($dolibarr_auto_user)) $authmode=array('auto');

// Si la demande du login a déjà eu lieu, on le récupère depuis la session
// sinon appel du module qui réalise sa demande.
// A l'issu de cette phase, la variable $login sera définie.
$login='';
if (! session_id() && ! isset($_SESSION["dol_user"])  && ! isset($_SESSION["dol_token"]))
{
    session_name("DOLSESSID_".$dolibarr_main_db_name);
    session_start();

	// On est pas déjà authentifié, on demande le login/mot de passe
	// A l'issu de cette demande, le login et un jeton doivent avoir été placé
	// en session dans dol_user et dol_token et la page rappelée.

	// MODE AUTO
	if (in_array('auto',$authmode) && ! $login)
	{
		$login=$dolibarr_auto_user;
	    dolibarr_syslog ("Authentification ok (en mode force)");
	}

	// MODE HTTP (Basic)
	if (in_array('http',$authmode) && ! $login)
	{
		$login=$_SERVER["REMOTE_USER"];
	}

	// MODE DOLIBARR
	if (in_array('dolibarr',$authmode) && ! $login)
	{
    	require_once(DOL_DOCUMENT_ROOT."/includes/pear/Auth/Auth.php");

    	$pear = $dolibarr_main_db_type.'://'.$dolibarr_main_db_user.':'.$dolibarr_main_db_pass.'@'.$dolibarr_main_db_host.'/'.$dolibarr_main_db_name;

	    $params = array(
		    "dsn" => $pear,
		    "table" => MAIN_DB_PREFIX."user",
		    "usernamecol" => "login",
		    "passwordcol" => "pass",
		    "cryptType" => "none",
	    );

	    $aDol = new DOLIAuth("DB", $params, "loginfunction");
	    $aDol->setSessionName("DOLSESSID_".$dolibarr_main_db_name);
    	$aDol->start();
	    $result = $aDol->getAuth();	// Si deja logue avec succes, renvoie vrai, sinon effectue un redirect sur page loginfunction et renvoie false
	    if ($result)
	    {
	        // Authentification Auth OK, on va chercher le login
			$login=$aDol->getUsername();
	        dolibarr_syslog ("Authentification ok (en mode Pear Base Dolibarr)");
		}
		else
		{
	        if (isset($_POST["loginfunction"]))
	        {
	            // Echec authentification
	            dolibarr_syslog("Authentification ko (en mode Pear Base Dolibarr) pour '".$_POST["username"]."'");
	        }
	        else 
	        {
	            // Non authentifie
	            //dolibarr_syslog("Authentification non realise");
	        }
	        // Le debut de la page a ete affichee par par getAuth qui a utilisé loginfunction.
	        // On ferme donc juste la page de logon.
	        print "</div>\n</div>\n</body>\n</html>";
	        exit;
        }
	}

	// MODE LDAP
	if (in_array('ldap',$authmode) && ! $login)
	{
		if ($conf->ldap->enabled)
		{
		    // Authentification Apache KO ou non active, pas de mode force on demande le login
		    require_once(DOL_DOCUMENT_ROOT."/includes/pear/Auth/Auth.php");
		
		    //if ($conf->global->LDAP_SERVER_PROTOCOLVERSION == 3)
		    //{
		    	$ldap = 'ldap://'.$conf->global->LDAP_ADMIN_DN.':'.$conf->global->LDAP_ADMIN_PASS.'@'.$conf->global->LDAP_SERVER_HOST.':'.$conf->global->LDAP_SERVER_PORT.'/'.$conf->global->LDAP_SERVER_DN;
		    //}
		    //else
		    //{
		    //	$ldap = 'ldap2://'.$conf->global->LDAP_ADMIN_DN.':'.$conf->global->LDAP_ADMIN_PASS.'@'.$conf->global->LDAP_SERVER_HOST.':'.$conf->global->LDAP_SERVER_PORT.'/'.$conf->global->LDAP_SERVER_DN;
		    //}
		
		    $params = array(
			    'dsn' => $ldap,
			    'host' => $conf->global->LDAP_SERVER_HOST,
			    'port' => $conf->global->LDAP_SERVER_PORT,
			    'version' => $conf->global->LDAP_SERVER_PORT,
			    'basedn' => $conf->global->LDAP_SERVER_DN,
			    'binddn' => $conf->global->LDAP_ADMIN_DN,
			    'bindpw' => $conf->global->LDAP_ADMIN_PASS,
			    'userattr' => $conf->global->LDAP_FIELD_LOGIN_SAMBA,
			    'userfilter' => '(objectClass=user)',
		    );
		
		    $aDol = new DOLIAuth("DB", $params, "loginfunction");
		    $aDol->setSessionName("DOLSESSID_".$dolibarr_main_db_name);
		    $aDol->start();
		    $result = $aDol->getAuth();	// Si deja logue avec succes, renvoie vrai, sinon effectue un redirect sur page loginfunction et renvoie false
		    if ($result)
		    {
		        // Authentification Auth OK, on va chercher le login
				$login=$aDol->getUsername();
		        dolibarr_syslog ("Authentification ok (en mode Pear Base LDAP)");
		    }
		    else
		    {
		        if (isset($_POST["loginfunction"]))
		        {
		            // Echec authentification
		            dolibarr_syslog("Authentification ko (en mode Pear Base LDAP) pour '".$_POST["username"]."'");
		        }
		        else 
		        {
		            // Non authentifie
		            //dolibarr_syslog("Authentification non realise");
		        }
		        // Le debut de la page a ete affichee par getAuth qui a utilisé loginfunction.
		        // On ferme donc juste la page de logon.
		        print "</div>\n</div>\n</body>\n</html>";
		        exit;
		    }
		}
    }
}
else
{
	// On est déjà en session
    $login=$_SESSION["dol_user"];
}


// Charge l'objet user depuis son login
$user->fetch($login);
if (! $user->id)
{
	dolibarr_print_error($langs->trans("ErrorCantLoadUserFromDolibarrDatabase"));
	exit;
}

// Est-ce une nouvelle session
if (! isset($_SESSION["dol_user"]))
{
    // Nouvelle session pour ce login
    dolibarr_syslog("New session in DOLSESSID_".$dolibarr_main_db_name.": ".session_id());
    $user->update_last_login_date();
    $_SESSION["dol_user"]=$user;
}


// Si user admin, on force droits sur les modules base
if ($user->admin)
{
    $user->rights->user->user->lire=1;
    $user->rights->user->user->creer=1;
    $user->rights->user->user->password=1;
    $user->rights->user->user->supprimer=1;
    $user->rights->user->self->creer=1;
    $user->rights->user->self->password=1;
}

/**
 * Overwrite configs global par configs perso
 * ------------------------------------------
 */
if (isset($user->conf->MAIN_SIZE_LISTE_LIMIT) && $user->conf->MAIN_SIZE_LISTE_LIMIT > 0)
{
    $conf->liste_limit = $user->conf->MAIN_SIZE_LISTE_LIMIT;
}
if (isset($user->conf->PRODUIT_LIMIT_SIZE))
{
    $conf->produit->limit_size = $user->conf->PRODUIT_LIMIT_SIZE;
}
if (isset($user->conf->MAIN_LANG_DEFAULT) && $user->conf->MAIN_LANG_DEFAULT)
{
    if ($langs->getDefaultLang() != $user->conf->MAIN_LANG_DEFAULT)
    {
        // Si on a un langage perso different du langage courant global
        $langs->setDefaultLang($user->conf->MAIN_LANG_DEFAULT);
        $langs->setPhpLang($user->conf->MAIN_LANG_DEFAULT);
    }
}

// Remplace conf->css par valeur personnalise
if (isset($user->conf->MAIN_THEME) && $user->conf->MAIN_THEME)
{
    $conf->theme=$user->conf->MAIN_THEME;
    $conf->css  = "theme/".$conf->theme."/".$conf->theme.".css";
}
// Si feuille de style en php existe
if (file_exists(DOL_DOCUMENT_ROOT.'/'.$conf->css.".php")) $conf->css.=".php";

if (isset($user->conf->MAIN_DISABLE_JAVASCRIPT) && $user->conf->MAIN_DISABLE_JAVASCRIPT)
{
    $conf->use_javascript=! $user->conf->MAIN_DISABLE_JAVASCRIPT;
}

// Defini gestionnaire de menu a utiliser
if (! $user->societe_id)    // Si utilisateur interne
{
    $conf->top_menu=$conf->global->MAIN_MENU_BARRETOP;
    $conf->left_menu=$conf->global->MAIN_MENU_BARRELEFT;
    // Pour compatibilite    if ($conf->top_menu == 'eldy.php') $conf->top_menu='eldy_backoffice.php';
    if ($conf->left_menu == 'eldy.php') $conf->left_menu='eldy_backoffice.php';
}
else                        // Si utilisateur externe
{
    $conf->top_menu=$conf->global->MAIN_MENUFRONT_BARRETOP;
    $conf->left_menu=$conf->global->MAIN_MENUFRONT_BARRELEFT;
}

// Si le login n'a pu etre recupere, on est identifie avec un compte qui n'existe pas.
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
    exit;
}

// Constantes utilise pour definir le nombre de lignes des textarea
if (! eregi("firefox",$_SERVER["HTTP_USER_AGENT"]))
{
    define('ROWS_1',1);
    define('ROWS_2',2);
    define('ROWS_3',3);
    define('ROWS_4',4);
    define('ROWS_5',5);
    define('ROWS_6',6);
    define('ROWS_7',7);
    define('ROWS_8',8);
    define('ROWS_9',9);
}
else
{
    define('ROWS_1',0);
    define('ROWS_2',1);
    define('ROWS_3',2);
    define('ROWS_4',3);
    define('ROWS_5',4);
    define('ROWS_6',5);
    define('ROWS_7',6);
    define('ROWS_8',7);
    define('ROWS_9',8);
}



/**
 *  \brief      Affiche en-tete html
 *  \param      head    lignes d'en-tete head
 *  \param      title   titre page web
 *  \param      target  target du menu Accueil
 */
function top_htmlhead($head, $title="", $target="") 
{
    global $user, $conf, $langs, $db;
    
    //header("Content-type: text/html; charset=UTF-8");
    header("Content-type: text/html; charset=iso-8859-1");

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

    if ($conf->use_javascript || $conf->use_ajax)
    {
        print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/lib/lib_head.js"></script>';
    }
    if ($conf->use_ajax)
    {
        print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/lib/prototype.js"></script>';
    }
    
    print "</head>\n";
}
  
/**
 *  \brief      Affiche en-tete html + la barre de menu superieure
 *  \param      head    lignes d'en-tete head
 *  \param      title   titre page web
 *  \param      target  target du menu Accueil
 */
function top_menu($head, $title="", $target="") 
{
    global $user, $conf, $langs, $db;

    top_htmlhead($head, $title, $target);

    print '<body id="mainbody"><div id="dhtmltooltip"></div>';

    /*
     * Si la constante MAIN_NEED_UPDATE est definie (par le script de migration sql en general), c'est que
     * les donnees ont besoin d'un remaniement. Il faut passer le update.php
     */
    if ($conf->global->MAIN_NEED_UPDATE)
    {
        $langs->load("admin");
        print '<div class="fiche">'."\n";
        print '<table class="noborder" width="100%">';
        print '<tr><td>';
        print $langs->trans("UpdateRequired",DOL_URL_ROOT.'/install/index.php');
        print '</td></tr>';
        print "</table>";
        llxFooter();
        exit;
    }


    /*
     * Barre de menu superieure
     */
    print "\n".'<!-- Start top horizontal menu -->'."\n";
    print '<div class="tmenu">'."\n";

    // Charge le gestionnaire des entrees de menu du haut
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
        print '<img class="login" border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/logout.png"';
        print ' alt="'.$langs->trans("Logout").'" title="'.$langs->trans("Logout").'"';
        print '>';
        print '</a>';
    }

    print "\n</div>\n<!-- End top horizontal menu -->\n";

}


/**
 *  \brief      Affiche barre de menu gauche
 *  \param      menu_array      Tableau des entrees de menu
 *  \param      help_url        Url pour le lien aide ('' par defaut)
 *  \param      form_search     Formulaire de recherche permanant supplementaire
 */
function left_menu($menu_array, $help_url='', $form_search='')
{
    global $user, $conf, $langs, $db;

    print '<div class="vmenuplusfiche">'."\n";
    print "\n";

    // Colonne de gauche
    print '<!-- Debut left vertical menu -->'."\n";
    print '<div class="vmenu">'."\n";


    // Autres entrees du menu par le gestionnaire
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
            printSearchForm(DOL_URL_ROOT.'/societe.php',DOL_URL_ROOT.'/societe.php',
                img_object($langs->trans("List"),'company').' '.$langs->trans("Companies"),'soc','socname');
        }

        if ($conf->societe->enabled && $conf->global->MAIN_SEARCHFORM_CONTACT && $user->rights->societe->lire)
        {
            $langs->load("companies");
            printSearchForm(DOL_URL_ROOT.'/contact/index.php',DOL_URL_ROOT.'/contact/index.php',
                img_object($langs->trans("List"),'contact').' '.$langs->trans("Contacts"),'contact','contactname','contact');
        }

        if (($conf->produit->enabled || $conf->service->enabled) && $conf->global->MAIN_SEARCHFORM_PRODUITSERVICE && $user->rights->produit->lire)
        {
            $langs->load("products");
            printSearchForm(DOL_URL_ROOT.'/product/liste.php',DOL_URL_ROOT.'/product/index.php',
                img_object($langs->trans("List"),'product').' '.$langs->trans("Products")."/".$langs->trans("Services"),'products','sall','product');
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

    // Zone de recherche supplementaire
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

    if ($conf->global->MAIN_SHOW_BUGTRACK_LINK == 1)
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
 *  \brief   Affiche une zone de recherche
 *  \param   urlaction          Url du post
 *  \param   urlobject          Url du lien sur titre de la zone de recherche
 *  \param   title              Titre de la zone de recherche
 *  \param   htmlmodesearch     'search'
 *  \param   htmlinputname      Nom du champ input du formulaire
 */
 
function printSearchForm($urlaction,$urlobject,$title,$htmlmodesearch='search',$htmlinputname)
{
    global $langs;
    print '<form action="'.$urlaction.'" method="post">';
    print '<a class="vmenu" href="'.$urlobject.'">';
    print $title.'</a><br>';
    print '<input type="hidden" name="mode" value="search">';
    print '<input type="hidden" name="mode-search" value="'.$htmlmodesearch.'">';
    print '<input type="text" class="flat" name="'.$htmlinputname.'" size="10">&nbsp;';
    print '<input type="submit" class="button" value="'.$langs->trans("Go").'">';
    print "</form>";
}


/**
 *		\brief   	Impression du pied de page
 *		\remarks	Ferme 2 div
 * 		\param   	foot    Non utilise
 */
 
function llxFooter($foot='',$limitIEbug=1) 
{
    global $conf, $dolibarr_auto_user, $micro_start_time;
    
    print "\n</div>\n".'<!-- end div class="fiche" -->'."\n";
    print "\n</div>\n".'<!-- end div class="vmenuplusfiche" -->';
    
    if (isset($_SERVER['DOL_TUNING']))
    {
        print '<script language="javascript" type="text/javascript">window.status="Build time: '.ceil(1000*(microtime(true)-$micro_start_time)).' ms"</script>';
        print "\n";
    } 

    if ($conf->use_javascript)
    {
        print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/lib/lib_foot.js"></script>';
    }

    // Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent
    if ($limitIEbug) print "\n".'<div class="tabsAction">&nbsp;</div>'."\n";
    
    print "</body>\n";
    print "</html>\n";
}
?>
