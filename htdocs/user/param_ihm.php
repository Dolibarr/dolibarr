<?php
/* Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2015 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013	   Florian Henry        <florian.henry@open-concept.pro.com>
 * Copyright (C) 2018      Ferran Marcet        <fmarcet@2byte.es>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/user/param_ihm.php
 *       \brief      Page to show user setup for display
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

// Load translation files required by page
$langs->loadLangs(array('companies', 'products', 'admin', 'users', 'languages', 'projects', 'members'));

// Defini si peux lire/modifier permisssions
$canreaduser = ($user->admin || $user->rights->user->user->lire);

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'userihm'; // To manage different context of search

if ($id) {
	// $user est le user qui edite, $id est l'id de l'utilisateur edite
	$caneditfield = ((($user->id == $id) && $user->rights->user->self->creer)
	|| (($user->id != $id) && $user->rights->user->user->creer));
}

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->rights->user->self->creer) ? '' : 'user');

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);
if ($user->id <> $id && !$canreaduser) {
	accessforbidden();
}

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
$formadmin = new FormAdmin($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('usercard', 'userihm', 'globalcard'));


/*
 * Actions
 */

$parameters = array('id'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'update' && ($caneditfield || !empty($user->admin))) {
		if (!GETPOST("cancel")) {
			$tabparam = array();

			if (GETPOST("check_MAIN_LANDING_PAGE") == "on") {
				$tabparam["MAIN_LANDING_PAGE"] = GETPOST("MAIN_LANDING_PAGE", 'alphanohtml');
			} else {
				$tabparam["MAIN_LANDING_PAGE"] = '';
			}

			if (GETPOST("check_MAIN_LANG_DEFAULT") == "on") {
				$tabparam["MAIN_LANG_DEFAULT"] = GETPOST("main_lang_default", 'aZ09');
			} else {
				$tabparam["MAIN_LANG_DEFAULT"] = '';
			}

			if (GETPOST("check_SIZE_LISTE_LIMIT") == "on") {
				$tabparam["MAIN_SIZE_LISTE_LIMIT"] = GETPOST("main_size_liste_limit", 'int');
			} else {
				$tabparam["MAIN_SIZE_LISTE_LIMIT"] = '';
			}

			if (GETPOST("check_AGENDA_DEFAULT_VIEW") == "on") {
				$tabparam["AGENDA_DEFAULT_VIEW"] = GETPOST("AGENDA_DEFAULT_VIEW", 'aZ09');
			} else {
				$tabparam["AGENDA_DEFAULT_VIEW"] = '';
			}

			if (GETPOST("check_MAIN_THEME") == "on") {
				$tabparam["MAIN_THEME"] = GETPOST('main_theme', 'aZ09');
			} else {
				$tabparam["MAIN_THEME"] = '';
			}

			$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_TOPMENU_BACK1', 'alphanohtml'), array()))));
			if ($val == '') {
				$tabparam['THEME_ELDY_TOPMENU_BACK1'] = '';
			} else {
				$tabparam['THEME_ELDY_TOPMENU_BACK1'] = join(
					',',
					colorStringToArray(GETPOST('THEME_ELDY_TOPMENU_BACK1', 'alphanohtml'), array())
				);
			}

			$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_BACKTITLE1', 'alphanohtml'), array()))));
			if ($val == '') {
				$tabparam['THEME_ELDY_BACKTITLE1'] = '';
			} else {
				$tabparam['THEME_ELDY_BACKTITLE1'] = join(
					',',
					colorStringToArray(GETPOST('THEME_ELDY_BACKTITLE1', 'alphanohtml'), array())
				);
			}

			if (GETPOST('check_THEME_ELDY_USE_HOVER') == 'on') {
				$tabparam["THEME_ELDY_USE_HOVER"] = 1;
			} else {
				$tabparam["THEME_ELDY_USE_HOVER"] = 0;
			}

			if (GETPOST('check_THEME_ELDY_USE_CHECKED') == 'on') {
				$tabparam["THEME_ELDY_USE_CHECKED"] = 1;
			} else {
				$tabparam["THEME_ELDY_USE_CHECKED"] = 0;
			}

			if (GETPOST('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$tabparam["MAIN_OPTIMIZEFORTEXTBROWSER"] = 1;
			} else {
				$tabparam["MAIN_OPTIMIZEFORTEXTBROWSER"] = 0;
			}

			if (GETPOST('MAIN_OPTIMIZEFORCOLORBLIND')) {
				$tabparam["MAIN_OPTIMIZEFORCOLORBLIND"] = GETPOST('MAIN_OPTIMIZEFORCOLORBLIND', 'aZ09');
			} else {
				$tabparam["MAIN_OPTIMIZEFORCOLORBLIND"] = 0;
			}

			$result = dol_set_user_param($db, $conf, $object, $tabparam);

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
			exit;
		}
	}
}

/*
 * View
 */

llxHeader();

// List of possible landing pages
$tmparray = array('index.php'=>'Dashboard');
if (!empty($conf->societe->enabled)) {
	$tmparray['societe/index.php?mainmenu=companies&leftmenu='] = 'ThirdPartiesArea';
}
if (!empty($conf->project->enabled)) {
	$tmparray['projet/index.php?mainmenu=project&leftmenu='] = 'ProjectsArea';
}
if (!empty($conf->holiday->enabled) || !empty($conf->expensereport->enabled)) {
	$tmparray['hrm/index.php?mainmenu=hrm&leftmenu='] = 'HRMArea'; // TODO Complete list with first level of menus
}
if (!empty($conf->product->enabled) || !empty($conf->service->enabled)) {
	$tmparray['product/index.php?mainmenu=products&leftmenu='] = 'ProductsAndServicesArea';
}
if (!empty($conf->propal->enabled) || !empty($conf->commande->enabled) || !empty($conf->ficheinter->enabled) || !empty($conf->contrat->enabled)) {
	$tmparray['comm/index.php?mainmenu=commercial&leftmenu='] = 'CommercialArea';
}
if (!empty($conf->comptabilite->enabled) || !empty($conf->accounting->enabled)) {
	$tmparray['compta/index.php?mainmenu=compta&leftmenu='] = 'AccountancyTreasuryArea';
}
if (!empty($conf->adherent->enabled)) {
	$tmparray['adherents/index.php?mainmenu=members&leftmenu='] = 'MembersArea';
}
if (isModEnabled('agenda')) {
	$tmparray['comm/action/index.php?mainmenu=agenda&leftmenu='] = 'Agenda';
}
if (!empty($conf->ticket->enabled)) {
	$tmparray['ticket/list.php?mainmenu=ticket&leftmenu='] = 'Tickets';
}

$head = user_prepare_head($object);

$title = $langs->trans("User");

if ($action == 'edit') {
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$id.'">';
}


if ($action == 'edit') {
	print dol_get_fiche_head($head, 'guisetup', $title, -1, 'user');

	$linkback = '';

	if ($user->rights->user->user->lire || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

	dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

	print '<div class="underbanner clearboth"></div>';

	print dol_get_fiche_end();


	if (!empty($conf->use_javascript_ajax)) {
		print '<script type="text/javascript">
        jQuery(document).ready(function() {
        	function init_myfunc()
        	{
        		if (jQuery("#check_MAIN_LANDING_PAGE").prop("checked")) { jQuery("#MAIN_LANDING_PAGE").removeAttr(\'disabled\'); }
        		else { jQuery("#MAIN_LANDING_PAGE").attr(\'disabled\',\'disabled\'); }

                if (jQuery("#check_MAIN_LANG_DEFAULT").prop("checked")) { jQuery("#main_lang_default").removeAttr(\'disabled\'); }
        		else { jQuery("#main_lang_default").attr(\'disabled\',\'disabled\'); }

                if (jQuery("#check_SIZE_LISTE_LIMIT").prop("checked")) { jQuery("#main_size_liste_limit").removeAttr(\'disabled\'); }
        		else { jQuery("#main_size_liste_limit").attr(\'disabled\',\'disabled\'); }

                if (jQuery("#check_AGENDA_DEFAULT_VIEW").prop("checked")) { jQuery("#AGENDA_DEFAULT_VIEW").removeAttr(\'disabled\'); }
        		else { jQuery("#AGENDA_DEFAULT_VIEW").attr(\'disabled\',\'disabled\'); }

                if (jQuery("#check_MAIN_THEME").prop("checked")) { jQuery(".themethumbs").removeAttr(\'disabled\'); }
        		else { jQuery(".themethumbs").attr(\'disabled\',\'disabled\'); }

                if (jQuery("#check_THEME_ELDY_TOPMENU_BACK1").prop("checked")) { jQuery("#colorpickerTHEME_ELDY_TOPMENU_BACK1").removeAttr(\'disabled\'); }
        		else { jQuery("#colorpickerTHEME_ELDY_TOPMENU_BACK1").attr(\'disabled\',\'disabled\'); }
            }
        	init_myfunc();
        	jQuery("#check_MAIN_LANDING_PAGE").click(function() { init_myfunc(); });
            jQuery("#check_MAIN_LANG_DEFAULT").click(function() { init_myfunc(); });
            jQuery("#check_SIZE_LISTE_LIMIT").click(function() { init_myfunc(); });
            jQuery("#check_AGENDA_DEFAULT_VIEW").click(function() { init_myfunc(); });
            jQuery("#check_MAIN_THEME").click(function() { init_myfunc(); });
            jQuery("#check_THEME_ELDY_TOPMENU_BACK1").click(function() { init_myfunc(); });
            jQuery("#check_THEME_ELDY_BACKTITLE1").click(function() { init_myfunc(); });
        });
        </script>';
	}


	clearstatcache();

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("DefaultValue").'</td><td>&nbsp;</td><td>'.$langs->trans("PersonalValue").'</td></tr>';

	// Language by default
	print '<tr class="oddeven"><td class="titlefieldmiddle">'.$langs->trans("Language").'</td>';
	print '<td>';
	$s = picto_from_langcode($conf->global->MAIN_LANG_DEFAULT);
	print $s ? $s.' ' : '';
	print ($conf->global->MAIN_LANG_DEFAULT == 'auto' ? $langs->trans("AutoDetectLang") : $langs->trans("Language_".$conf->global->MAIN_LANG_DEFAULT));
	print '</td>';
	print '<td class="nowrap" width="20%"><input class="oddeven" name="check_MAIN_LANG_DEFAULT" id="check_MAIN_LANG_DEFAULT" type="checkbox" '.(!empty($object->conf->MAIN_LANG_DEFAULT) ? " checked" : "");
	print empty($dolibarr_main_demo) ? '' : ' disabled="disabled"'; // Disabled for demo
	print '> <label for="check_MAIN_LANG_DEFAULT">'.$langs->trans("UsePersonalValue").'</label></td>';
	print '<td>';
	print $formadmin->select_language((!empty($object->conf->MAIN_LANG_DEFAULT) ? $object->conf->MAIN_LANG_DEFAULT : ''), 'main_lang_default', 1, null, 0, 0, (!empty($dolibarr_main_demo)));
	print '</td></tr>';

	// Landing page
	print '<tr class="oddeven"><td>'.$langs->trans("LandingPage").'</td>';
	print '<td>';
	print (empty($conf->global->MAIN_LANDING_PAGE) ? '' : $conf->global->MAIN_LANDING_PAGE);
	print '</td>';
	print '<td class="nowrap" width="20%"><input class="oddeven" name="check_MAIN_LANDING_PAGE" id="check_MAIN_LANDING_PAGE" type="checkbox" '.(!empty($object->conf->MAIN_LANDING_PAGE) ? " checked" : "");
	print empty($dolibarr_main_demo) ? '' : ' disabled="disabled"'; // Disabled for demo
	print '> <label for="check_MAIN_LANDING_PAGE">'.$langs->trans("UsePersonalValue").'</label></td>';
	print '<td>';
	print $form->selectarray('MAIN_LANDING_PAGE', $tmparray, (!empty($object->conf->MAIN_LANDING_PAGE) ? $object->conf->MAIN_LANDING_PAGE : ''), 0, 0, 0, '', 1);
	//print info_admin($langs->trans("WarningYouMayLooseAccess"), 0, 0, 0);
	print '</td></tr>';

	// Landing page for Agenda - AGENDA_DEFAULT_VIEW
	print '<tr class="oddeven">'."\n";
	print '<td>'.$langs->trans("AGENDA_DEFAULT_VIEW").'</td>'."\n";
	print '<td class="center">&nbsp;</td>'."\n";
	print '<td class="nowrap" width="20%"><input class="oddeven" name="check_AGENDA_DEFAULT_VIEW" id="check_AGENDA_DEFAULT_VIEW" type="checkbox" '.(!empty($object->conf->AGENDA_DEFAULT_VIEW) ? " checked" : "");
	print empty($dolibarr_main_demo) ? '' : ' disabled="disabled"'; // Disabled for demo
	print '> <label for="check_AGENDA_DEFAULT_VIEW">'.$langs->trans("UsePersonalValue").'</label></td>';
	print '<td>'."\n";
	$tmplist = array(''=>'&nbsp;', 'show_list'=>$langs->trans("ViewList"), 'show_month'=>$langs->trans("ViewCal"), 'show_week'=>$langs->trans("ViewWeek"), 'show_day'=>$langs->trans("ViewDay"), 'show_peruser'=>$langs->trans("ViewPerUser"));
	print $form->selectarray('AGENDA_DEFAULT_VIEW', $tmplist, $object->conf->AGENDA_DEFAULT_VIEW, 0, 0, 0, '');
	print '</td></tr>'."\n";

	// Max size of lists
	print '<tr class="oddeven"><td>'.$langs->trans("MaxSizeList").'</td>';
	print '<td>'.$conf->global->MAIN_SIZE_LISTE_LIMIT.'</td>';
	print '<td class="nowrap" width="20%"><input class="oddeven" name="check_SIZE_LISTE_LIMIT" id="check_SIZE_LISTE_LIMIT" type="checkbox" '.(!empty($object->conf->MAIN_SIZE_LISTE_LIMIT) ? " checked" : "");
	print empty($dolibarr_main_demo) ? '' : ' disabled="disabled"'; // Disabled for demo
	print '> <label for="check_SIZE_LISTE_LIMIT">'.$langs->trans("UsePersonalValue").'</label></td>';
	print '<td><input class="flat" name="main_size_liste_limit" id="main_size_liste_limit" size="4" value="'.(!empty($object->conf->MAIN_SIZE_LISTE_LIMIT) ? $object->conf->MAIN_SIZE_LISTE_LIMIT : '').'"></td></tr>';

	print '</table><br>';

	// Theme
	showSkins($object, (($user->admin || empty($dolibarr_main_demo)) ? 1 : 0), true);


	print $form->buttonsSaveCancel();
} else {
	print dol_get_fiche_head($head, 'guisetup', $title, -1, 'user');

	$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'" class="refid">';
	$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
	$morehtmlref .= '</a>';

	dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin, 'rowid', 'ref', $morehtmlref);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Login
	print '<tr><td class="titlefield">'.$langs->trans("Login").'</td>';
	if (!empty($object->ldap_sid) && $object->statut == 0) {
		print '<td class="error">';
		print $langs->trans("LoginAccountDisableInDolibarr");
		print '</td>';
	} else {
		print '<td>';
		$addadmin = '';
		if (property_exists($object, 'admin')) {
			if (!empty($conf->multicompany->enabled) && !empty($object->admin) && empty($object->entity)) {
				$addadmin .= img_picto($langs->trans("SuperAdministratorDesc"), "redstar", 'class="paddingleft"');
			} elseif (!empty($object->admin)) {
				$addadmin .= img_picto($langs->trans("AdministratorDesc"), "star", 'class="paddingleft"');
			}
		}
		print showValueWithClipboardCPButton($object->login).$addadmin;
		print '</td>';
	}
	print '</tr>'."\n";

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();


	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("DefaultValue").'</td><td>&nbsp;</td><td>'.$langs->trans("PersonalValue").'</td></tr>';

	// Language
	print '<tr class="oddeven"><td>'.$langs->trans("Language").'</td>';
	print '<td>';
	$s = picto_from_langcode($conf->global->MAIN_LANG_DEFAULT);
	print ($s ? $s.' ' : '');
	print (isset($conf->global->MAIN_LANG_DEFAULT) && $conf->global->MAIN_LANG_DEFAULT == 'auto' ? $langs->trans("AutoDetectLang") : $langs->trans("Language_".$conf->global->MAIN_LANG_DEFAULT));
	print '</td>';
	print '<td class="nowrap"><input class="oddeven" type="checkbox" disabled '.(!empty($object->conf->MAIN_LANG_DEFAULT) ? " checked" : "").'> '.$langs->trans("UsePersonalValue").'</td>';
	print '<td>';
	$s = (isset($object->conf->MAIN_LANG_DEFAULT) ? picto_from_langcode($object->conf->MAIN_LANG_DEFAULT) : '');
	print ($s ? $s.' ' : '');
	print (isset($object->conf->MAIN_LANG_DEFAULT) && $object->conf->MAIN_LANG_DEFAULT == 'auto' ? $langs->trans("AutoDetectLang") : (!empty($object->conf->MAIN_LANG_DEFAULT) ? $langs->trans("Language_".$object->conf->MAIN_LANG_DEFAULT) : ''));
	print '</td></tr>';

	// Landing page
	print '<tr class="oddeven"><td>'.$langs->trans("LandingPage").'</td>';
	print '<td>';
	print (empty($conf->global->MAIN_LANDING_PAGE) ? '' : $conf->global->MAIN_LANDING_PAGE);
	print '</td>';
	print '<td class="nowrap"><input class="oddeven" name="check_MAIN_LANDING_PAGE" disabled id="check_MAIN_LANDING_PAGE" type="checkbox" '.(!empty($object->conf->MAIN_LANDING_PAGE) ? " checked" : "");
	print empty($dolibarr_main_demo) ? '' : ' disabled="disabled"'; // Disabled for demo
	print '> '.$langs->trans("UsePersonalValue").'</td>';
	print '<td>';
	if (!empty($object->conf->MAIN_LANDING_PAGE)) {
		if (!empty($tmparray[$object->conf->MAIN_LANDING_PAGE])) {
			print $langs->trans($tmparray[$object->conf->MAIN_LANDING_PAGE]);
		} else {
			print $object->conf->MAIN_LANDING_PAGE;
		}
	}
	//print $form->selectarray('MAIN_LANDING_PAGE', $tmparray, (! empty($object->conf->MAIN_LANDING_PAGE)?$object->conf->MAIN_LANDING_PAGE:''), 0, 0, 0, '', 1);
	print '</td></tr>';

	// Landing page for Agenda - AGENDA_DEFAULT_VIEW
	print '<tr class="oddeven">'."\n";
	print '<td>'.$langs->trans("AGENDA_DEFAULT_VIEW").'</td>'."\n";
	print '<td class="center">&nbsp;</td>'."\n";
	print '<td class="nowrap" width="20%"><input class="oddeven" type="checkbox" disabled '.(!empty($object->conf->AGENDA_DEFAULT_VIEW) ? " checked" : "").'> '.$langs->trans("UsePersonalValue").'</td>';
	print '<td>'."\n";
	$tmplist = array(''=>'&nbsp;', 'show_list'=>$langs->trans("ViewList"), 'show_month'=>$langs->trans("ViewCal"), 'show_week'=>$langs->trans("ViewWeek"), 'show_day'=>$langs->trans("ViewDay"), 'show_peruser'=>$langs->trans("ViewPerUser"));
	if (!empty($object->conf->AGENDA_DEFAULT_VIEW)) {
		print $form->selectarray('AGENDA_DEFAULT_VIEW', $tmplist, $object->conf->AGENDA_DEFAULT_VIEW, 0, 0, 0, '', 0, 0, 1);
	}
	print '</td></tr>'."\n";

	// Max size for lists
	print '<tr class="oddeven"><td>'.$langs->trans("MaxSizeList").'</td>';
	print '<td>'.(!empty($conf->global->MAIN_SIZE_LISTE_LIMIT) ? $conf->global->MAIN_SIZE_LISTE_LIMIT : '&nbsp;').'</td>';
	print '<td class="nowrap" width="20%"><input class="oddeven" type="checkbox" disabled '.(!empty($object->conf->MAIN_SIZE_LISTE_LIMIT) ? " checked" : "").'> '.$langs->trans("UsePersonalValue").'</td>';
	print '<td>'.(!empty($object->conf->MAIN_SIZE_LISTE_LIMIT) ? $object->conf->MAIN_SIZE_LISTE_LIMIT : '&nbsp;').'</td></tr>';

	print '</table>';
	print '</div>';
	print '<br>';


	// Skin
	showSkins($object, 0, true);


	print '<div class="tabsAction">';
	if (empty($user->admin) && !empty($dolibarr_main_demo)) {
		print '<a class="butActionRefused classfortooltip" title="'.$langs->trans("FeatureDisabledInDemo").'" href="#">'.$langs->trans("Modify").'</a>';
	} else {
		if ($caneditfield || !empty($user->admin)) {       // Si utilisateur edite = utilisateur courant (pas besoin de droits particulier car il s'agit d'une page de modif d'output et non de données) ou si admin
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&id='.$object->id.'">'.$langs->trans("Modify").'</a>';
		} else {
			print '<a class="butActionRefused classfortooltip" title="'.$langs->trans("NotEnoughPermissions").'" href="#">'.$langs->trans("Modify").'</a>';
		}
	}

	print '</div>';
}

if ($action == 'edit') {
	print '</form>';
}

// End of page
llxFooter();
$db->close();
