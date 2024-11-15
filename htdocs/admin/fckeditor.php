<?php
/* Copyright (C) 2004-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2012-2013	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2019		Christophe Battarel <christophe@altairis.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/admin/fckeditor.php
 *  \ingroup    fckeditor
 *  \brief      Activation page for the FCKeditor module in the other modules
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doleditor.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('admin', 'fckeditor', 'errors'));

$action = GETPOST('action', 'aZ09');
// Possible modes are:
// dolibarr_details
// dolibarr_notes
// dolibarr_readonly
// dolibarr_mailings
// Full (not sure this one is used)
$mode = GETPOST('mode') ? GETPOST('mode', 'alpha') : 'dolibarr_notes';

if (!$user->admin) {
	accessforbidden();
}

// Constant and translation of the module description
$modules = array(
	'NOTE_PUBLIC' => 'FCKeditorForNotePublic',
	'NOTE_PRIVATE' => 'FCKeditorForNotePrivate',
	'SOCIETE' => 'FCKeditorForCompany',
	'DETAILS' => 'FCKeditorForProductDetails',
	'USERSIGN' => 'FCKeditorForUserSignature',
	'MAILING' => 'FCKeditorForMailing',
	'MAIL' => 'FCKeditorForMail',
	'TICKET' => 'FCKeditorForTicket',
	//'SPECIALCHAR' => 'SpecialCharActivation',
);
// Conditions for the option to be offered
$conditions = array(
	'NOTE_PUBLIC' => 1,
	'NOTE_PRIVATE' => 1,
	'SOCIETE' => 1,
	'PRODUCTDESC' => (isModEnabled("product") || isModEnabled("service")),
	'DETAILS' => (isModEnabled('invoice') || isModEnabled("propal") || isModEnabled('order') || isModEnabled('supplier_proposal') || isModEnabled("supplier_order") || isModEnabled("supplier_invoice")),
	'USERSIGN' => 1,
	'MAILING' => isModEnabled('mailing'),
	'MAIL' => (isModEnabled('invoice') || isModEnabled("propal") || isModEnabled('order')),
	'TICKET' => isModEnabled('ticket'),
	//'SPECIALCHAR' => 1,
);
// Picto
$picto = array(
	'NOTE_PUBLIC' => 'generic',
	'NOTE_PRIVATE' => 'generic',
	'SOCIETE' => 'generic',
	'PRODUCTDESC' => 'product',
	'DETAILS' => 'product',
	'USERSIGN' => 'user',
	'MAILING' => 'email',
	'MAIL' => 'email',
	'TICKET' => 'ticket',
	//'SPECIALCHAR' => 'generic'
);



/*
 *  Actions
 */

foreach ($modules as $const => $desc) {
	if ($action == 'enable_'.strtolower($const)) {
		dolibarr_set_const($db, "FCKEDITOR_ENABLE_".$const, "1", 'chaine', 0, '', $conf->entity);

		// If fckeditor is active in the product/service description, it is activated in the forms
		if ($const == 'PRODUCTDESC' && getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE')) {
			dolibarr_set_const($db, "FCKEDITOR_ENABLE_DETAILS", "1", 'chaine', 0, '', $conf->entity);
		}
	}
	if ($action == 'disable_'.strtolower($const)) {
		dolibarr_set_const($db, "FCKEDITOR_ENABLE_".$const, "0", 'chaine', 0, '', $conf->entity);
	}
}

if (GETPOST('save', 'alpha')) {
	$error = 0;

	$fckeditor_skin = GETPOST('fckeditor_skin', 'alpha');
	if (!empty($fckeditor_skin)) {
		$result = dolibarr_set_const($db, 'FCKEDITOR_SKIN', $fckeditor_skin, 'chaine', 0, '', $conf->entity);
		if ($result <= 0) {
			$error++;
		}
	} else {
		$error++;
	}

	$fckeditor_test = GETPOST('formtestfield', 'restricthtml');
	if (!empty($fckeditor_test)) {
		$result = dolibarr_set_const($db, 'FCKEDITOR_TEST', $fckeditor_test, 'chaine', 0, '', $conf->entity);
		if ($result <= 0) {
			$error++;
		}
	} else {
		$error = -1;	// -1 means a warning message
	}

	if ($error == 0) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} elseif ($error == -1) {
		setEventMessages($langs->trans("EmptyMessageNotAllowedError"), null, 'warnings');
	} else {
		setEventMessages($langs->trans("Error").' '.$db->lasterror(), null, 'errors');
	}
}


/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-fckeditor');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AdvancedEditor"), $linkback, 'title_setup');
print '<br>';

if (empty($conf->use_javascript_ajax)) {
	setEventMessages(null, array($langs->trans("NotAvailable"), $langs->trans("JavascriptDisabled")), 'errors');
} else {
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">'.$langs->trans("ActivateFCKeditor").'</td>';
	print '<td class="center width100"></td>';
	print "</tr>\n";

	// Modules
	foreach ($modules as $const => $desc) {
		// If this condition is not met, the option is not offered
		if (!$conditions[$const]) {
			continue;
		}

		$constante = 'FCKEDITOR_ENABLE_'.$const;
		print '<!-- constant = '.$constante.' -->'."\n";
		print '<tr class="oddeven">';
		print '<td class="width20">'.img_object("", $picto[$const]).'</td>';
		print '<td>';
		print $langs->trans($desc);
		if ($const == 'DETAILS') {
			print $form->textwithpicto('', '<span class="warning">'.$langs->trans("FCKeditorForProductDetails2").'</span>');
		}
		print '</td>';
		print '<td class="center centpercent width100">';
		$value = getDolGlobalInt($constante, 0);
		if ($value == 0) {
			print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=enable_'.strtolower($const).'&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
		} elseif ($value == 1) {
			if ($const == 'DETAILS') {
				print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=disable_'.strtolower($const).'&token='.newToken().'">'.img_picto($langs->trans("Enabled").' - '.$langs->trans("FCKeditorForProductDetails2"), 'switch_on', '', 0, 0, 0, '', 'warning').'</a>';
			} else {
				print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=disable_'.strtolower($const).'&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
			}
		}

		print "</td>";
		print '</tr>';
	}

	print '</table>'."\n";

	print '<br>'."\n";


	// Other options
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Other").'</td>';
	print '<td class="center"></td>';
	print "</tr>\n";

	$constante = 'FCKEDITOR_ENANLE_SPECIALCHAR';
	print '<!-- constant = '.$constante.' -->'."\n";
	print '<tr class="oddeven">';
	print '<td>';
	print $langs->trans('SpecialCharActivation');
	print '</td>';
	print '<td class="center width100">';
	$value = getDolGlobalInt($constante, 0);
	if ($value == 0) {
		print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=enable_'.strtolower($const).'&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
	} elseif ($value == 1) {
		print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=disable_'.strtolower($const).'&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
	}

	print "</td>";
	print '</tr>';

	print '</table>'."\n";

	print '<br>'."\n";


	print '<form name="formtest" method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="page_y" value="">';

	// Skins
	//show_skin(null, 1);
	//print '<br>'."\n";

	$listofmodes = array('dolibarr_readonly', 'dolibarr_details', 'dolibarr_notes', 'dolibarr_mailings', 'Full', 'Full_inline');
	$linkstomode = '';
	foreach ($listofmodes as $newmode) {
		if ($linkstomode) {
			$linkstomode .= ' - ';
		}
		$linkstomode .= '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?mode='.$newmode.'">';
		if ($mode == $newmode) {
			$linkstomode .= '<strong>';
		}
		$linkstomode .= $newmode;
		if ($mode == $newmode) {
			$linkstomode .= '</strong>';
		}
		$linkstomode .= '</a>';
	}
	$linkstomode .= '';
	print load_fiche_titre($langs->trans("TestSubmitForm"), $linkstomode, '');
	print '<input type="hidden" name="mode" value="'.dol_escape_htmltag($mode).'">';
	if ($mode != 'Full_inline') {
		$uselocalbrowser = true;
		$readonly = ($mode == 'dolibarr_readonly' ? 1 : 0);
		$editor = new DolEditor('formtestfield', getDolGlobalString('FCKEDITOR_TEST', 'Test'), '', 200, $mode, 'In', true, $uselocalbrowser, 1, 120, '8', $readonly);
		$editor->Create();
	} else {
		// CKEditor inline enabled with the contenteditable="true"
		print '<div style="border: 1px solid #888;" contenteditable="true">';
		print getDolGlobalString('FCKEDITOR_TEST');
		print '</div>';
	}
	print $form->buttonsSaveCancel("Save", '', array(), 0, 'reposition');
	print '<div id="divforlog"></div>';
	print '</form>'."\n";

	// Add env of ckeditor
	// This is to show how CKEditor detect browser to understand why editor is disabled or not. To help debug.
	/*
		print '<br><script type="text/javascript">
		function jsdump(obj, id) {
			var out = \'\';
			for (var i in obj) {
				out += i + ": " + obj[i] + "<br>\n";
			}

			jQuery("#"+id).html(out);
		}

		jsdump(CKEDITOR.env, "divforlog");
		</script>';
	}
	*/
}

// End of page
llxFooter();
$db->close();
