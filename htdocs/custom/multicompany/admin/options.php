<?php
/* Copyright (C) 2011-2020	Regis Houssin	<regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       multicompany/admin/parameters.php
 *  \ingroup    multicompany
 *  \brief      Page d'administration/configuration du module Multi-Company
 */

$res=@include("../../main.inc.php");						// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");			// For "custom" directory

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/multicompany/class/actions_multicompany.class.php', 'ActionsMulticompany');

$langs->loadLangs(array('admin', 'multicompany@multicompany'));

// Security check
if (empty($user->admin) || ! empty($user->entity)) {
	accessforbidden();
}

$action=GETPOST('action','alpha');

$object = new ActionsMulticompany($db);


/*
 * Action
 */


/*
 * View
 */

$extrajs = array(
	'/multicompany/core/js/lib_head.js'
);

$help_url='EN:Module_MultiCompany|FR:Module_MultiSoci&eacute;t&eacute;';
llxHeader('', $langs->trans("MultiCompanySetup"), $help_url,'','','',$extrajs);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MultiCompanySetup"),$linkback,'multicompany@multicompany',0,'multicompany_title');

$head = multicompany_prepare_head();
dol_fiche_head($head, 'options', $langs->trans("ModuleSetup"), -1);

$level = checkMultiCompanyVersion();
if ($level === 1 || $level === -1)
{
	$text = $langs->trans("MultiCompanyIsOlderThanDolibarr");
	if ($level === -1) $text = $langs->trans("DolibarrIsOlderThanMulticompany");

	print '<div class="multicompany_checker">';
	dol_htmloutput_mesg($text, '', 'warning', 1);
	print '</div>';

}

$form=new Form($db);

$hidden=true;
$checkconfig = checkMulticompanyAutentication();
if ($checkconfig !== true) {
	if (! empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX)) {
		$hidden=false;
	}
	print '<div id="mc_hide_login_combobox_error"'.($hidden ? ' style="display:none;"' : '').'>'.get_htmloutput_mesg($langs->trans("ErrorMulticompanyConfAuthentication"),'','error',1).'</div>';
} else {
	if (empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX)) {
		$hidden=false;
	}
	print '<div id="dol_hide_login_combobox_error"'.($hidden ? ' style="display:none;"' : '').'>'.get_htmloutput_mesg($langs->trans("ErrorDolibarrConfAuthentication"),'','error',1).'</div>';
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

/*
 * System parameters
 */

// Login page combobox activation
print '<tr class="oddeven">';
print '<td><span class="fa fa-cogs"></span><span class="multiselect-title">'.$langs->trans("HideLoginCombobox").'</span></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">';
if ($checkconfig !== true) {
	$input = array(
		'showhide' => array(
			'#mc_hide_login_combobox_error'
		)
	);
} else {
	$input = array(
		'hideshow' => array(
			'#dol_hide_login_combobox_error'
		)
	);
}
$input['hideshow'][] = '#changeloginlogo';
$input['hideshow'][] = '#changeloginbackground';
$input['del'] = array('MULTICOMPANY_LOGIN_LOGO_BY_ENTITY', 'MULTICOMPANY_LOGIN_BACKGROUND_BY_ENTITY');
print ajax_mcconstantonoff('MULTICOMPANY_HIDE_LOGIN_COMBOBOX', $input, 0);
print '</td></tr>';

// Replace entity logo in login page
print '<tr id="changeloginlogo" class="oddeven"'.(! empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX) ? ' style="display:none;"' : '').'>';
print '<td><span class="fa fa-cogs"></span><span class="multiselect-title">'.$langs->trans("EntityLogoInLoginPage").'</span></td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
$input = array(
	'showhide' => array(
		'#changeloginbackground'
	),
	'del' => array(
		'MULTICOMPANY_LOGIN_BACKGROUND_BY_ENTITY'
	)
);
print ajax_mcconstantonoff('MULTICOMPANY_LOGIN_LOGO_BY_ENTITY', $input, 0);
print '</td></tr>';

// Replace entity background in login page
print '<tr id="changeloginbackground" class="oddeven"'.(empty($conf->global->MULTICOMPANY_LOGIN_LOGO_BY_ENTITY) ? ' style="display:none;"' : '').'>';
print '<td><span class="fa fa-cogs"></span><span class="multiselect-title">'.$langs->trans("EntityBackgroundInLoginPage").'</span></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">';
print ajax_mcconstantonoff('MULTICOMPANY_LOGIN_BACKGROUND_BY_ENTITY', '', 0);
print '</td></tr>';

// Disable the new dropdown menu
print '<tr id="disabledropdownmenu" class="oddeven">';
print '<td><span class="fa fa-cogs"></span><span class="multiselect-title">'.$langs->trans("DisableSwitchEntityDropdownMenu").'</span></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">';
$input = array(
	'reload' => true
);
print ajax_mcconstantonoff('MULTICOMPANY_DROPDOWN_MENU_DISABLED', $input, 0);
print '</td></tr>';

// Hide/View top menu entity label
print '<tr id="showtopmenuentitylabel" class="oddeven">';
print '<td><span class="fa fa-cogs"></span><span class="multiselect-title">'.$langs->trans("ShowTopMenuEntityLabel").'</span></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">';
$input = array(
	'reload' => true
);
print ajax_mcconstantonoff('MULTICOMPANY_NO_TOP_MENU_ENTITY_LABEL', $input, 0, 1);
print '</td></tr>';

// Active by default during create
print '<tr class="oddeven">';
print '<td><span class="fa fa-cogs"></span><span class="multiselect-title">'.$langs->trans("EntityActiveByDefault").'</span></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">';
$input = array(
	'showhide' => array(
		'#visiblebydefault'
	),
	'del' => array(
		'MULTICOMPANY_VISIBLE_BY_DEFAULT'
	)
);
print ajax_mcconstantonoff('MULTICOMPANY_ACTIVE_BY_DEFAULT', $input, 0);
print '</td></tr>';

// Visible by default during create
print '<tr id="visiblebydefault" class="oddeven"'.(empty($conf->global->MULTICOMPANY_ACTIVE_BY_DEFAULT) ? ' style="display:none;"' : '').'>';
print '<td><span class="fa fa-cogs"></span><span class="multiselect-title">'.$langs->trans("EntityVisibleByDefault").'</span></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">';
print ajax_mcconstantonoff('MULTICOMPANY_VISIBLE_BY_DEFAULT', '', 0);
print '</td></tr>';

// Template management
$text = img_picto('', 'info','class="linkobject"');
$htmltext = $langs->trans("TemplateOfEntityManagementInfo");

print '<tr id="template" class="oddeven">';
print '<td><span class="fa fa-cogs"></span><span class="multiselect-title">'.$langs->trans("TemplateOfEntityManagement").' '.$form->textwithtooltip('',$htmltext,2,1,$text).'</span></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">';
print ajax_mcconstantonoff('MULTICOMPANY_TEMPLATE_MANAGEMENT', '', 0);
print '</td></tr>';

/*
 * Sharings parameters
 */
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

/* Mode de gestion des droits :
 * Mode Off : mode Off : pyramidale. Les droits et les groupes sont gérés dans chaque entité : les utilisateurs appartiennent au groupe de l'entity pour obtenir leurs droits
 * Mode On : mode On : transversale : Les groupes ne peuvent appartenir qu'a l'entity = 0 et c'est l'utilisateur qui appartient à tel ou tel entity
 */

$text = img_picto('', 'info','class="linkobject"');
$htmltext = $langs->trans("GroupModeTransversalInfoFull");

print '<tr class="oddeven">';
print '<td><span class="fa fa-users"></span><span class="multiselect-title">'.$langs->trans("GroupModeTransversal").' '.$form->textwithtooltip('',$htmltext,2,1,$text).'</span></td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
$input = array(
	'alert' => array(
		'set' => array(
			'info' => true,
			'height' => 200,
			'yesButton' => $langs->trans('Ok'),
			'title' => $langs->transnoentities('GroupModeTransversalTitle'),
			'content' => img_warning().' '.$langs->trans('GroupModeTransversalInfo')
		)
	)
);
print ajax_mcconstantonoff('MULTICOMPANY_TRANSVERSE_MODE', $input, 0);
print '</td></tr>';

// Enable global sharings
if (! empty($conf->societe->enabled)
	|| ! empty($conf->product->enabled)
	|| ! empty($conf->service->enabled)
	|| ! empty($conf->categorie->enabled)
	|| ! empty($conf->adherent->enabled)
	|| ! empty($conf->agenda->enabled))
{
	print '<tr class="oddeven">';
	print '<td><span class="fa fa-project-diagram"></span><span class="multiselect-title">'.$langs->trans("EnableGlobalSharings").'</span></td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	$input = array(
		'alert' => array(
			'set' => array(
				'info' => true,
				'yesButton' => $langs->trans('Ok'),
				'title' => $langs->transnoentities('GlobalSharings'),
				'content' => img_warning().' '.$langs->trans('GlobalSharingsInfo')
			)
		),
		'showhide' => array(
			'#shareelementtitle',
			'#sharethirdparty'
		),
		'hide' => array(
			'#shareelementtitle',
			'#shareobjecttitle',
			'#sharethirdparty'
		),
		'del' => array(
			'MULTICOMPANY_THIRDPARTY_SHARING_ENABLED'
		)
	);
	foreach ($object->sharingelements as $key => $values)
	{
		if (! isset($values['disable'])) {
			if (isset($values['input']) && isset($values['input']['global'])) {
				if (isset($values['input']['global']['showhide']) && $values['input']['global']['showhide'] === true) {
					if (! isset($input['showhide'])) $input['showhide'] = array();
					array_push($input['showhide'], '#share'.$key);
				}
				if (isset($values['input']['global']['hide']) && $values['input']['global']['hide'] === true) {
					if (! isset($input['hide'])) $input['hide'] = array();
					array_push($input['hide'], '#share'.$key);
				}
				if (isset($values['input']['global']['del']) && $values['input']['global']['del'] === true) {
					if (! isset($input['del'])) $input['del'] = array();
					array_push($input['del'], 'MULTICOMPANY_'.strtoupper($key).'_SHARING_ENABLED');
				}
			}
		}
	}
	print ajax_mcconstantonoff('MULTICOMPANY_SHARINGS_ENABLED', $input, 0);
	print '</td></tr>';
}

$text = img_picto('', 'info','class="linkobject"');
$htmltext = $langs->trans("GlobalSharingsInfo");

print '<tr class="liste_titre" id="shareelementtitle"'.(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
print '<td>'.$langs->trans("ActivatingShares").' '.$form->textwithtooltip('',$htmltext,2,1,$text).'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

// Share thirparties and contacts
if (! empty($conf->societe->enabled))
{
	print '<tr id="sharethirdparty" class="oddeven"'.(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
	print '<td><span class="fa fa-'.$object->sharingelements['thirdparty']['icon'].'"></span><span class="multiselect-title">'.$langs->trans("ShareThirdpartiesAndContacts").'</span></td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	$input = array(
		'showhide' => array(
			'#shareobjecttitle'
		)
	);
	foreach ($object->sharingelements as $key => $values)
	{
		if (! isset($values['disable']) && ($values['type'] === 'object' || $values['type'] === 'objectnumber'))
		{
			if (isset($values['input']) && isset($values['input']['thirdparty'])) {
				if (isset($values['input']['thirdparty']['showhide']) && $values['input']['thirdparty']['showhide'] === true) {
					if (! isset($input['showhide'])) $input['showhide'] = array();
					array_push($input['showhide'], '#share'.$key);
				}
				if (isset($values['input']['thirdparty']['hide']) && $values['input']['thirdparty']['hide'] === true) {
					if (! isset($input['hide'])) $input['hide'] = array();
					array_push($input['hide'], '#share'.$key);
				}
				if (isset($values['input']['thirdparty']['del']) && $values['input']['thirdparty']['del'] === true) {
					if (! isset($input['del'])) $input['del'] = array();
					array_push($input['del'], 'MULTICOMPANY_'.strtoupper($key).'_SHARING_ENABLED');
				}
			}
		}
	}
	print ajax_mcconstantonoff('MULTICOMPANY_THIRDPARTY_SHARING_ENABLED', $input, 0);
	print '</td></tr>';
}

// Elements sharings
$text = img_picto('', 'info','class="linkobject"');

foreach ($object->sharingelements as $element => $params)
{
	if (! isset($params['disable']) && $params['type'] === 'element')
	{
		$tooltip = null;
		$display = ! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED);
		$module = ((isset($object->sharingmodulename[$element]) && !empty($object->sharingmodulename[$element])) ? $object->sharingmodulename[$element] : $element);
		$enabled = (! empty($params['enable']) ? dol_eval($params['enable'], 1) : $conf->$module->enabled);
		if (! empty($enabled))
		{
			$icon = (! empty($params['icon'])?$params['icon']:'cogs');

			if (! empty($params['lang'])) {
				$langs->load($params['lang']);
			}

			if (! empty($params['tooltip'])) {
				$htmltext = $langs->trans($params['tooltip']);
				$tooltip = $form->textwithtooltip('', $htmltext, 2, 1, $text);
			}

			if (! empty($params['display'])) {
				$display = ($display && dol_eval($params['display'], 1));
			}

			$display = ($display ? '' : ' style="display:none;"');

			print '<tr id="share'.$element.'" class="oddeven"'.$display.'>';
			print '<td><span class="fa fa-'.$icon.'"></span>';
			print '<span class="multiselect-title">'.$langs->trans("Share".ucfirst($element)).(! empty($tooltip) ? ' '.$tooltip : '').'</span></td>';
			print '<td align="center" width="20">&nbsp;</td>';

			print '<td align="center" width="100">';

			$input = array();
			foreach ($object->sharingelements as $key => $values) {
				if (! isset($values['disable']) && isset($values['input']) && isset($values['input'][$element])) {
					if (isset($values['input'][$element]['showhide']) && $values['input'][$element]['showhide'] === true) {
						if (! isset($input['showhide'])) $input['showhide'] = array();
						array_push($input['showhide'], '#share'.$key);
					}
					if (isset($values['input'][$element]['hide']) && $values['input'][$element]['hide'] === true) {
						if (! isset($input['hide'])) $input['hide'] = array();
						array_push($input['hide'], '#share'.$key);
					}
					if (isset($values['input'][$element]['del']) && $values['input'][$element]['del'] === true) {
						if (! isset($input['del'])) $input['del'] = array();
						array_push($input['del'], 'MULTICOMPANY_'.strtoupper($key).'_SHARING_ENABLED');
					}
				}
			}

			print ajax_mcconstantonoff('MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED', $input, 0);
			print '</td></tr>';
		}
	}
}

// Objects sharings
$text = img_picto('', 'info','class="linkobject"');
$htmltext = $langs->trans("ObjectSharingsInfo");
$display=(! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) && ! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) ? '' : ' style="display:none;"');
print '<tr class="liste_titre" id="shareobjecttitle"'.$display.'>';
print '<td>'.$langs->trans("ActivatingObjectShares").' '.$form->textwithtooltip('', $htmltext, 2, 1, $text).'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

foreach ($object->sharingelements as $element => $params)
{
	if (! isset($params['disable']) && ($params['type'] === 'object' || $params['type'] === 'objectnumber'))
	{
		$tooltip = null;
		$display = ! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED);
		$module = ((isset($object->sharingmodulename[$element]) && !empty($object->sharingmodulename[$element])) ? $object->sharingmodulename[$element] : $element);
		$enabled = (! empty($params['enable']) ? dol_eval($params['enable'], 1) : $conf->$module->enabled);
		if (! empty($enabled))
		{
			$icon = (! empty($params['icon'])?$params['icon']:'cogs');

			if (! empty($params['lang'])) {
				$langs->load($params['lang']);
			}

			if (! empty($params['tooltip'])) {
				$htmltext = $langs->trans($params['tooltip']);
				$tooltip = $form->textwithtooltip('', $htmltext, 2, 1, $text);
			}

			if (! empty($params['display'])) {
				$display = ($display && dol_eval($params['display'], 1));
			}

			$display = ($display ? '' : ' style="display:none;"');

			print '<tr id="share'.$element.'" class="oddeven"'.$display.'>';
			print '<td><span class="fa fa-'.$icon.'"></span>';
			print '<span class="multiselect-title">'.$langs->trans("Share".ucfirst($element)).(! empty($tooltip) ? ' '.$tooltip : '').'</span></td>';
			print '<td align="center" width="20">&nbsp;</td>';

			print '<td align="center" width="100">';
			$input = array();
			print ajax_mcconstantonoff('MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED', $input, 0);
			print '</td></tr>';
		}
	}
}

// Dictionnaries
if (1==2 && ! empty($object->sharingdicts))
{
	$text = img_picto('', 'info','class="linkobject"');
	$htmltext = $langs->trans("DictsSharingsInfo");

	print '<tr class="liste_titre" id="dictsharetitle">';
	print '<td>'.$langs->trans("ActivatingDictsShares").' '.$form->textwithtooltip('',$htmltext,2,1,$text).'</td>'."\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
	print '</tr>';

	foreach ($object->sharingdicts as $dict => $data)
	{
		print '<tr id="share'.$dict.'" class="oddeven">';
		print '<td>'.$langs->trans("Share".ucfirst($dict)).'</td>';
		print '<td align="center" width="20">&nbsp;</td>';

		print '<td align="center" width="100">';
		print ajax_mcconstantonoff('MULTICOMPANY_'.strtoupper($dict).'_SHARING_DISABLED', '', 0);
		print '</td></tr>';
	}
}

print '</table>';

// Card end
dol_fiche_end();
// Footer
llxFooter();
// Close database handler
$db->close();
