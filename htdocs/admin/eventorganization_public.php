<?php
/* Copyright (C) 2021		Florian Henry			<florian.henry@scopen.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/admin/eventorganization.php
 * \ingroup eventorganization
 * \brief   EventOrganization setup page.
 */

// Load Dolibarr environment
require '../main.inc.php';

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/eventorganization.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_url_root
 */

// Translations
$langs->loadLangs(array("admin", "eventorganization", "categories"));

// Parameters
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$scandir = GETPOST('scan_dir', 'alpha');
$type = 'myobject';

$arrayofparameters = array(
	'EVENTORGANIZATION_SECUREKEY' => array('type' => 'securekey', 'enabled' => 1, 'css' => ''),
);

$error = 0;
$setupnotempty = 0;

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

// Access control
if (empty($user->admin)) {
	accessforbidden();
}

if (empty($action)) {
	$action = 'edit';
}


/*
 * Actions
 */

if ($cancel) {
	$action  = '';
}

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';



/*
 * View
 */

$form = new Form($db);

$page_name = "EventOrganizationSetup";

llxHeader('', $langs->trans($page_name), '', '', 0, 0, '', '', '', 'mod-admin page-eventorganization');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = eventorganizationAdminPrepareHead();
print dol_get_fiche_head($head, 'public', $langs->trans($page_name), -1, 'eventorganization');

// Setup page goes here
// print '<span class="opacitymedium">'.$langs->trans("EventOrganizationSetupPage").'</span><br>';
print '<br>';

if ($action == 'edit') {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td></td></tr>';

	foreach ($arrayofparameters as $constname => $val) {
		// @phpstan-ignore-next-line
		if ($val['enabled'] == 1) {
			$setupnotempty++;
			print '<tr class="oddeven"><td><!-- '.$constname.' -->';
			$tooltiphelp = (($langs->trans($constname . 'Tooltip') != $constname . 'Tooltip') ? $langs->trans($constname . 'Tooltip') : '');
			$tooltiphelp .= (($langs->trans($constname . 'Tooltip2') && $langs->trans($constname . 'Tooltip2') != $constname . 'Tooltip2') ? '<br><br>'."\n".$langs->trans($constname . 'Tooltip2') : '');
			print '<span id="helplink'.$constname.'" class="spanforparamtooltip">'.$form->textwithpicto($langs->trans($constname), $tooltiphelp, 1, 'info', '', 0, 3, 'tootips'.$constname).'</span>';
			print '</td><td>';

			/*if ($val['type'] == 'textarea') {
				print '<textarea class="flat" name="'.$constname.'" id="'.$constname.'" cols="50" rows="5" wrap="soft">' . "\n";
				print getDolGlobalString($constname);
				print "</textarea>\n";
			} elseif ($val['type'] == 'html') {
				require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
				$doleditor = new DolEditor($constname, getDolGlobalString($constname), '', 160, 'dolibarr_notes', '', false, false, isModEnabled('fckeditor'), ROWS_5, '90%');
				$doleditor->Create();
			} elseif ($val['type'] == 'yesno') {
				print $form->selectyesno($constname, getDolGlobalString($constname), 1);
			} elseif (preg_match('/emailtemplate:/', $val['type'])) {
				include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
				$formmail = new FormMail($db);

				$tmp = explode(':', $val['type']);
				$nboftemplates = $formmail->fetchAllEMailTemplate($tmp[1], $user, null, 1); // We set lang=null to get in priority record with no lang
				//$arraydefaultmessage = $formmail->getEMailTemplate($db, $tmp[1], $user, null, 0, 1, '');
				$arrayofmessagename = array();
				if (is_array($formmail->lines_model)) {
					foreach ($formmail->lines_model as $modelmail) {
						//var_dump($modelmail);
						$moreonlabel = '';
						if (!empty($arrayofmessagename[$modelmail->label])) {
							$moreonlabel = ' <span class="opacitymedium">(' . $langs->trans("SeveralLangugeVariatFound") . ')</span>';
						}
						// The 'label' is the key that is unique if we exclude the language
						$arrayofmessagename[$modelmail->id] = $langs->trans(preg_replace('/\(|\)/', '', $modelmail->label)) . $moreonlabel;
					}
				}
				print $form->selectarray($constname, $arrayofmessagename, getDolGlobalString($constname), 'None', 0, 0, '', 0, 0, 0, '', '', 1);
			} elseif (preg_match('/category:/', $val['type'])) {
				require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
				$formother = new FormOther($db);

				$tmp = explode(':', $val['type']);
				print img_picto('', 'category', 'class="pictofixedwidth"');
				print $formother->select_categories($tmp[1], getDolGlobalInt($constname), $constname, 0, $langs->trans('CustomersProspectsCategoriesShort'));
			} elseif (preg_match('/thirdparty_type/', $val['type'])) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
				$formcompany = new FormCompany($db);
				print $formcompany->selectProspectCustomerType(getDolGlobalString($constname), $constname, 'customerorprospect', 'form', '', '1');
			} elseif ($val['type'] == 'securekey') { */
			print '<input type="text" class="flat" id="'.$constname.'" name="'.$constname.'" value="'.(GETPOST($constname, 'alpha') ? GETPOST($constname, 'alpha') : getDolGlobalString($constname)).'" size="40">';
			if (!empty($conf->use_javascript_ajax)) {
				print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token'.$constname.'" class="linkobject"');
			}

			// Add button to autosuggest a key
			include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			print dolJSToSetRandomPassword($constname, 'generate_token'.$constname);
			/* } elseif ($val['type'] == 'product') {
				if (isModEnabled("product") || isModEnabled("service")) {
					$selected = getDolGlobalInt($constname);
					print img_picto('', 'product', 'class="pictofixedwidth"');
					print $form->select_produits($selected, $constname, '', 0, 0, 1, 2, '', 0, array(), 0, '1', 0, 'maxwidth500 widthcentpercentminusx', 0, '', null, 1);
				}
			} else {
				print '<input name="' . $constname . '"  class="flat ' . (empty($val['css']) ? 'minwidth200' : $val['css']) . '" value="' . getDolGlobalString($constname) . '">';
			}*/
			print '</td></tr>';
		}
	}
	print '</table>';

	print $form->buttonsSaveCancel('Save', '');

	print '</form>';
	print '<br>';
} else {
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td></td></tr>';

	foreach ($arrayofparameters as $constname => $val) {
		$setupnotempty++;
		print '<tr class="oddeven">';
		print '<td><!-- '.$constname.' -->';
		$tooltiphelp = (($langs->trans($constname . 'Tooltip') != $constname . 'Tooltip') ? $langs->trans($constname . 'Tooltip') : '');
		$tooltiphelp .= (($langs->trans($constname . 'Tooltip2') && $langs->trans($constname . 'Tooltip2') != $constname . 'Tooltip2') ? '<br><br>'."\n".$langs->trans($constname . 'Tooltip2') : '');
		print $form->textwithpicto($langs->trans($constname), $tooltiphelp);
		print '</td><td>';
		print getDolGlobalString($constname);
		print '</td>';

		print '</tr>';
	}

	print '</table>';

	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
	print '</div>';
}


// Page end
print dol_get_fiche_end();


//if (getDolGlobalString('EVENTORGANIZATION_ENABLE_PUBLIC')) {
	print '<br>';
	//print $langs->trans('FollowingLinksArePublic').'<br>';
	print img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans('BlankSubscriptionForm').'</span><br>';
if (isModEnabled('multicompany')) {
	$entity_qr = '?entity='.$conf->entity;
} else {
	$entity_qr = '';
}

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	print '<div class="urllink">';
	print $langs->trans("TheLinkIsAvailableOnTheProjectEventCard");
	//print '<input type="text" id="publicurlmember" class="quatrevingtpercentminusx" value="'.$urlwithroot.'/public/project/index.php'.$entity_qr.'">';
	//print '<a target="_blank" rel="noopener noreferrer" href="'.$urlwithroot.'/public/project/index.php'.$entity_qr.'">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
	print '</div>';
	print ajax_autoselect('publicurlmember');
//}


llxFooter();
$db->close();
