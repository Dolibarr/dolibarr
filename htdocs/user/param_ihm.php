<?php
/* Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2015 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013	   Florian Henry        <florian.henry@open-concept.pro.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/user/param_ihm.php
 *       \brief      Page to show user setup for display
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("users");
$langs->load("languages");
$langs->load("projects");
$langs->load("members");

// Defini si peux lire/modifier permisssions
$canreaduser=($user->admin || $user->rights->user->user->lire);

$id = GETPOST('id','int');
$action = GETPOST('action','alpha');

if ($id)
{
    // $user est le user qui edite, $id est l'id de l'utilisateur edite
    $caneditfield=((($user->id == $id) && $user->rights->user->self->creer)
    || (($user->id != $id) && $user->rights->user->user->creer));
}

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2 = (($socid && $user->rights->user->self->creer)?'':'user');
if ($user->id == $id)	// A user can always read its own card
{
    $feature2='';
    $canreaduser=1;
}
$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);
if ($user->id <> $id && ! $canreaduser) accessforbidden();

$dirtop = "../core/menus/standard";
$dirleft = "../core/menus/standard";

// Charge utilisateur edite
$object = new User($db);
$object->fetch($id, '', '', 1);
$object->getrights();

// Liste des zone de recherche permanentes supportees
/* deprecated
$searchform=array("main_searchform_societe","main_searchform_contact","main_searchform_produitservice");
$searchformconst=array($conf->global->MAIN_SEARCHFORM_SOCIETE,$conf->global->MAIN_SEARCHFORM_CONTACT,$conf->global->MAIN_SEARCHFORM_PRODUITSERVICE);
$searchformtitle=array($langs->trans("Companies"),$langs->trans("Contacts"),$langs->trans("ProductsAndServices"));
*/

$form = new Form($db);
$formadmin=new FormAdmin($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$contextpage=array('usercard','userihm','globalcard');
$hookmanager->initHooks($contextpage);


/*
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if ($action == 'update' && ($caneditfield || !empty($user->admin))) {
		if (!$_POST["cancel"]) {
			$tabparam = array();

			if (GETPOST("check_MAIN_LANDING_PAGE") == "on") {
				$tabparam["MAIN_LANDING_PAGE"] = $_POST["MAIN_LANDING_PAGE"];
			} else {
				$tabparam["MAIN_LANDING_PAGE"] = '';
			}

			if (GETPOST("check_MAIN_LANG_DEFAULT") == "on") {
				$tabparam["MAIN_LANG_DEFAULT"] = $_POST["main_lang_default"];
			} else {
				$tabparam["MAIN_LANG_DEFAULT"] = '';
			}

			if (GETPOST("check_SIZE_LISTE_LIMIT") == "on") {
				$tabparam["MAIN_SIZE_LISTE_LIMIT"] = $_POST["main_size_liste_limit"];
			} else {
				$tabparam["MAIN_SIZE_LISTE_LIMIT"] = '';
			}

			if (GETPOST("check_MAIN_THEME") == "on") {
				$tabparam["MAIN_THEME"] = $_POST["main_theme"];
			} else {
				$tabparam["MAIN_THEME"] = '';
			}

			$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_TOPMENU_BACK1'), array()))));
			if ($val == '') {
				$tabparam['THEME_ELDY_TOPMENU_BACK1'] = '';
			} else {
				$tabparam['THEME_ELDY_TOPMENU_BACK1'] = join(',',
					colorStringToArray(GETPOST('THEME_ELDY_TOPMENU_BACK1'), array()));
			}

			$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_BACKTITLE1'), array()))));
			if ($val == '') {
				$tabparam['THEME_ELDY_BACKTITLE1'] = '';
			} else {
				$tabparam['THEME_ELDY_BACKTITLE1'] = join(',',
					colorStringToArray(GETPOST('THEME_ELDY_BACKTITLE1'), array()));
			}

			if (GETPOST('check_THEME_ELDY_USE_HOVER') == 'on') {
				$tabparam["THEME_ELDY_USE_HOVER"] = 1;
			} else {
				$tabparam["THEME_ELDY_USE_HOVER"] = 0;
			}

			if (GETPOST('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			    $tabparam["MAIN_OPTIMIZEFORTEXTBROWSER"] = 1;
			} else {
			    $tabparam["MAIN_OPTIMIZEFORTEXTBROWSER"] = 0;
			}

			$result = dol_set_user_param($db, $conf, $object, $tabparam);

			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
			exit;
		}
	}
}

/*
 * View
 */

llxHeader();

// List of possible landing pages
$tmparray=array('index.php'=>'Dashboard');
if (! empty($conf->societe->enabled)) $tmparray['societe/index.php?mainmenu=companies&leftmenu=']='ThirdPartiesArea';
if (! empty($conf->projet->enabled)) $tmparray['projet/index.php?mainmenu=project&leftmenu=']='ProjectsArea';
if (! empty($conf->holiday->enabled) || ! empty($conf->expensereport->enabled)) $tmparray['hrm/index.php?mainmenu=hrm&leftmenu=']='HRMArea';   // TODO Complete list with first level of menus
if (! empty($conf->product->enabled) || ! empty($conf->service->enabled)) $tmparray['product/index.php?mainmenu=products&leftmenu=']='ProductsAndServicesArea';
if (! empty($conf->propal->enabled) || ! empty($conf->commande->enabled) || ! empty($conf->ficheinter->enabled) || ! empty($conf->contrat->enabled)) $tmparray['comm/index.php?mainmenu=commercial&leftmenu=']='CommercialArea';
if (! empty($conf->compta->enabled) || ! empty($conf->accounting->enabled)) $tmparray['compta/index.php?mainmenu=compta&leftmenu=']='AccountancyTreasuryArea';
if (! empty($conf->adherent->enabled)) $tmparray['adherents/index.php?mainmenu=members&leftmenu=']='MembersArea';
if (! empty($conf->agenda->enabled)) $tmparray['comm/action/index.php?mainmenu=agenda&leftmenu=']='Agenda';

$head = user_prepare_head($object);

$title = $langs->trans("User");

if ($action == 'edit')
{
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$id.'">';
}


if ($action == 'edit')
{
    dol_fiche_head($head, 'guisetup', $title, -1, 'user');

	$linkback = '';

	if ($user->rights->user->user->lire || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';
	}

    dol_banner_tab($object,'id',$linkback,$user->rights->user->user->lire || $user->admin);

    if (! empty($conf->use_javascript_ajax))
    {/*
        print '<script type="text/javascript" language="javascript">
    	jQuery(document).ready(function() {
    		$("#main_lang_default").change(function() {
    			$("#check_MAIN_LANG_DEFAULT").prop("checked", true);
    		});
    		$("#main_size_liste_limit").keyup(function() {
    			if ($(this).val().length) $("#check_SIZE_LISTE_LIMIT").prop("checked", true);
    			else $("#check_SIZE_LISTE_LIMIT").prop("checked", false);
    		});
    	});
    	</script>';*/
    }
    if (! empty($conf->use_javascript_ajax))
    {
        print '<script type="text/javascript" language="javascript">
        jQuery(document).ready(function() {
        	function init_myfunc()
        	{
        		if (jQuery("#check_MAIN_LANDING_PAGE").prop("checked")) { jQuery("#MAIN_LANDING_PAGE").removeAttr(\'disabled\'); }
        		else { jQuery("#MAIN_LANDING_PAGE").attr(\'disabled\',\'disabled\'); }

                if (jQuery("#check_MAIN_LANG_DEFAULT").prop("checked")) { jQuery("#main_lang_default").removeAttr(\'disabled\'); }
        		else { jQuery("#main_lang_default").attr(\'disabled\',\'disabled\'); }

                if (jQuery("#check_SIZE_LISTE_LIMIT").prop("checked")) { jQuery("#main_size_liste_limit").removeAttr(\'disabled\'); }
        		else { jQuery("#main_size_liste_limit").attr(\'disabled\',\'disabled\'); }

                if (jQuery("#check_MAIN_THEME").prop("checked")) { jQuery(".themethumbs").removeAttr(\'disabled\'); }
        		else { jQuery(".themethumbs").attr(\'disabled\',\'disabled\'); }

                if (jQuery("#check_THEME_ELDY_TOPMENU_BACK1").prop("checked")) { jQuery("#colorpickerTHEME_ELDY_TOPMENU_BACK1").removeAttr(\'disabled\'); }
        		else { jQuery("#colorpickerTHEME_ELDY_TOPMENU_BACK1").attr(\'disabled\',\'disabled\'); }
            }
        	init_myfunc();
        	jQuery("#check_MAIN_LANDING_PAGE").click(function() { init_myfunc(); });
            jQuery("#check_SIZE_LISTE_LIMIT").click(function() { init_myfunc(); });
            jQuery("#check_MAIN_LANG_DEFAULT").click(function() { init_myfunc(); });
            jQuery("#check_MAIN_THEME").click(function() { init_myfunc(); });
            jQuery("#check_THEME_ELDY_TOPMENU_BACK1").click(function() { init_myfunc(); });
            jQuery("#check_THEME_ELDY_BACKTITLE1").click(function() { init_myfunc(); });
        });
        </script>';
    }


    clearstatcache();
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="25%">'.$langs->trans("Parameter").'</td><td width="25%">'.$langs->trans("DefaultValue").'</td><td>&nbsp;</td><td>'.$langs->trans("PersonalValue").'</td></tr>';

    // Landing page

    print '<tr class="oddeven"><td>'.$langs->trans("LandingPage").'</td>';
    print '<td>';
    print (empty($conf->global->MAIN_LANDING_PAGE)?'':$conf->global->MAIN_LANDING_PAGE);
    print '</td>';
    print '<td align="left" class="nowrap" width="20%"><input '.$bc[$var].' name="check_MAIN_LANDING_PAGE" id="check_MAIN_LANDING_PAGE" type="checkbox" '.(! empty($object->conf->MAIN_LANDING_PAGE)?" checked":"");
    print empty($dolibarr_main_demo)?'':' disabled="disabled"';	// Disabled for demo
    print '> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td>';
    print $form->selectarray('MAIN_LANDING_PAGE', $tmparray, (! empty($object->conf->MAIN_LANDING_PAGE)?$object->conf->MAIN_LANDING_PAGE:''), 0, 0, 0, '', 1);
    //print info_admin($langs->trans("WarningYouMayLooseAccess"), 0, 0, 0);
    print '</td></tr>';

    // Langue par defaut

    print '<tr class="oddeven"><td>'.$langs->trans("Language").'</td>';
    print '<td>';
    $s=picto_from_langcode($conf->global->MAIN_LANG_DEFAULT);
    print $s?$s.' ':'';
    print ($conf->global->MAIN_LANG_DEFAULT=='auto'?$langs->trans("AutoDetectLang"):$langs->trans("Language_".$conf->global->MAIN_LANG_DEFAULT));
    print '</td>';
    print '<td align="left" class="nowrap" width="20%"><input '.$bc[$var].' name="check_MAIN_LANG_DEFAULT" id="check_MAIN_LANG_DEFAULT" type="checkbox" '.(! empty($object->conf->MAIN_LANG_DEFAULT)?" checked":"");
    print empty($dolibarr_main_demo)?'':' disabled="disabled"';	// Disabled for demo
    print '> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td>';
    print $formadmin->select_language((! empty($object->conf->MAIN_LANG_DEFAULT)?$object->conf->MAIN_LANG_DEFAULT:''),'main_lang_default',1,null,0,0,(! empty($dolibarr_main_demo)));
    print '</td></tr>';

    // Taille max des listes

    print '<tr class="oddeven"><td>'.$langs->trans("MaxSizeList").'</td>';
    print '<td>'.$conf->global->MAIN_SIZE_LISTE_LIMIT.'</td>';
    print '<td align="left" class="nowrap" width="20%"><input '.$bc[$var].' name="check_SIZE_LISTE_LIMIT" id="check_SIZE_LISTE_LIMIT" type="checkbox" '.(! empty($object->conf->MAIN_SIZE_LISTE_LIMIT)?" checked":"");
    print empty($dolibarr_main_demo)?'':' disabled="disabled"';	// Disabled for demo
    print '> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td><input class="flat" name="main_size_liste_limit" id="main_size_liste_limit" size="4" value="' . (! empty($object->conf->MAIN_SIZE_LISTE_LIMIT)?$object->conf->MAIN_SIZE_LISTE_LIMIT:'') . '"></td></tr>';

    print '</table><br>';

    // Theme
    show_theme($object, (($user->admin || empty($dolibarr_main_demo))?1:0), true);

    dol_fiche_end();


    print '<div class="center">';
    print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';

}
else
{
    dol_fiche_head($head, 'guisetup', $title, -1, 'user');

    $linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object,'id',$linkback,$user->rights->user->user->lire || $user->admin);

    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="25%">'.$langs->trans("Parameter").'</td><td width="25%">'.$langs->trans("DefaultValue").'</td><td>&nbsp;</td><td>'.$langs->trans("PersonalValue").'</td></tr>';

    // Landing page

    print '<tr class="oddeven"><td>'.$langs->trans("LandingPage").'</td>';
    print '<td>';
    print (empty($conf->global->MAIN_LANDING_PAGE)?'':$conf->global->MAIN_LANDING_PAGE);
    print '</td>';
    print '<td align="left" class="nowrap"><input '.$bc[$var].' name="check_MAIN_LANDING_PAGE" disabled id="check_MAIN_LANDING_PAGE" type="checkbox" '.(! empty($object->conf->MAIN_LANDING_PAGE)?" checked":"");
    print empty($dolibarr_main_demo)?'':' disabled="disabled"';	// Disabled for demo
    print '> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td>';
    if (! empty($tmparray[$object->conf->MAIN_LANDING_PAGE]))
    {
        print $langs->trans($tmparray[$object->conf->MAIN_LANDING_PAGE]);
    }
    else print $object->conf->MAIN_LANDING_PAGE;
    //print $form->selectarray('MAIN_LANDING_PAGE', $tmparray, (! empty($object->conf->MAIN_LANDING_PAGE)?$object->conf->MAIN_LANDING_PAGE:''), 0, 0, 0, '', 1);
    print '</td></tr>';

    // Language

    print '<tr class="oddeven"><td>'.$langs->trans("Language").'</td>';
    print '<td>';
    $s=picto_from_langcode($conf->global->MAIN_LANG_DEFAULT);
    print ($s?$s.' ':'');
    print (isset($conf->global->MAIN_LANG_DEFAULT) && $conf->global->MAIN_LANG_DEFAULT=='auto'?$langs->trans("AutoDetectLang"):$langs->trans("Language_".$conf->global->MAIN_LANG_DEFAULT));
    print '</td>';
    print '<td align="left" class="nowrap"><input '.$bc[$var].' type="checkbox" disabled '.(! empty($object->conf->MAIN_LANG_DEFAULT)?" checked":"").'> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td>';
    $s=(isset($object->conf->MAIN_LANG_DEFAULT) ? picto_from_langcode($object->conf->MAIN_LANG_DEFAULT) : '');
    print ($s?$s.' ':'');
    print (isset($object->conf->MAIN_LANG_DEFAULT) && $object->conf->MAIN_LANG_DEFAULT=='auto'?$langs->trans("AutoDetectLang"):(! empty($object->conf->MAIN_LANG_DEFAULT)?$langs->trans("Language_".$object->conf->MAIN_LANG_DEFAULT):''));
    print '</td></tr>';


    print '<tr class="oddeven"><td>'.$langs->trans("MaxSizeList").'</td>';
    print '<td>'.(! empty($conf->global->MAIN_SIZE_LISTE_LIMIT)?$conf->global->MAIN_SIZE_LISTE_LIMIT:'&nbsp;').'</td>';
    print '<td align="left" class="nowrap" width="20%"><input '.$bc[$var].' type="checkbox" disabled '.(! empty($object->conf->MAIN_SIZE_LISTE_LIMIT)?" checked":"").'> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td>' . (! empty($object->conf->MAIN_SIZE_LISTE_LIMIT)?$object->conf->MAIN_SIZE_LISTE_LIMIT:'&nbsp;') . '</td></tr>';

    print '</table><br>';


    // Skin
    show_theme($object,0,true);

    dol_fiche_end();


    print '<div class="tabsAction">';
    if (empty($user->admin) && ! empty($dolibarr_main_demo))
    {
        print "<a class=\"butActionRefused\" title=\"".$langs->trans("FeatureDisabledInDemo")."\" href=\"#\">".$langs->trans("Modify")."</a>";
    }
    else
    {
        if ($caneditfield || ! empty($user->admin))       // Si utilisateur edite = utilisateur courant (pas besoin de droits particulier car il s'agit d'une page de modif d'output et non de données) ou si admin
        {
            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$object->id.'">'.$langs->trans("Modify").'</a>';
        }
        else
        {
            print "<a class=\"butActionRefused\" title=\"".$langs->trans("NotEnoughPermissions")."\" href=\"#\">".$langs->trans("Modify")."</a>";
        }
    }

    print '</div>';

}

if ($action == 'edit')
{
    print '</form>';
}

llxFooter();
$db->close();
