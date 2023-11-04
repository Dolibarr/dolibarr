<?php
/* Copyright (C) 2004-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2012-2013	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2019		Christophe Battarel <christophe@altairis.fr>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doleditor.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'fckeditor'));

$action = GETPOST('action', 'aZ09');
// Possible modes are:
// dolibarr_details
// dolibarr_notes
// dolibarr_readonly
// dolibarr_mailings
// Full (not sure this one is used)
$mode = GETPOST('mode') ?GETPOST('mode', 'alpha') : 'dolibarr_notes';

if (!$user->admin) {
	accessforbidden();
}

// Constant and translation of the module description
$modules = array(
	'NOTE_PUBLIC' => 'FCKeditorForNotePublic',
	'NOTE_PRIVATE' => 'FCKeditorForNotePrivate',
	'SOCIETE' => 'FCKeditorForCompany',
	'PRODUCTDESC' => 'FCKeditorForProduct',
	'DETAILS' => 'FCKeditorForProductDetails',
	'USERSIGN' => 'FCKeditorForUserSignature',
	'MAILING' => 'FCKeditorForMailing',
	'MAIL' => 'FCKeditorForMail',
	'TICKET' => 'FCKeditorForTicket',
);
// Conditions for the option to be offered
$conditions = array(
	'NOTE_PUBLIC' => 1,
	'NOTE_PRIVATE' => 1,
	'SOCIETE' => 1,
	'PRODUCTDESC' => (!empty($conf->product->enabled) || !empty($conf->service->enabled)),
	'DETAILS' => (!empty($conf->facture->enabled) || !empty($conf->propal->enabled) || !empty($conf->commande->enabled) || !empty($conf->supplier_proposal->enabled) || (!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_order->enabled) || !empty($conf->supplier_invoice->enabled)),
	'USERSIGN' => 1,
	'MAILING' => !empty($conf->mailing->enabled),
	'MAIL' => (!empty($conf->facture->enabled) || !empty($conf->propal->enabled) || !empty($conf->commande->enabled)),
	'TICKET' => !empty($conf->ticket->enabled),
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
);



/*
 *  Actions
 */

foreach ($modules as $const => $desc) {
	if ($action == 'enable_'.strtolower($const)) {
		dolibarr_set_const($db, "FCKEDITOR_ENABLE_".$const, "1", 'chaine', 0, '', $conf->entity);
		// If fckeditor is active in the product/service description, it is activated in the forms
		if ($const == 'PRODUCTDESC' && !empty($conf->global->PRODUIT_DESC_IN_FORM)) {
			dolibarr_set_const($db, "FCKEDITOR_ENABLE_DETAILS", "1", 'chaine', 0, '', $conf->entity);
		}
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	if ($action == 'disable_'.strtolower($const)) {
		dolibarr_del_const($db, "FCKEDITOR_ENABLE_".$const, $conf->entity);
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

if (GETPOST('save', 'alpha')) {
	$error = 0;

	$fckeditor_skin = GETPOST('fckeditor_skin', 'alpha');
	if (!empty($fckeditor_skin)) {
		if (!dolibarr_set_const($db, 'FCKEDITOR_SKIN', $fckeditor_skin, 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
	} else {
		$error++;
	}

	$fckeditor_test = GETPOST('formtestfield', 'restricthtml');
	if (!empty($fckeditor_test)) {
		if (!dolibarr_set_const($db, 'FCKEDITOR_TEST', $fckeditor_test, 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
	} else {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

/*
 * View
 */

llxHeader();

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AdvancedEditor"), $linkback, 'title_setup');
print '<br>';

if (empty($conf->use_javascript_ajax)) {
	setEventMessages(array($langs->trans("NotAvailable"), $langs->trans("JavascriptDisabled")), null, 'errors');
} else {
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">'.$langs->trans("ActivateFCKeditor").'</td>';
	print '<td class="center" width="100">'.$langs->trans("Action").'</td>';
	print "</tr>\n";

	// Modules
	foreach ($modules as $const => $desc) {
		// If this condition is not met, the option is not offered
		if (!$conditions[$const]) {
			continue;
		}

		print '<tr class="oddeven">';
		print '<td width="16">'.img_object("", $picto[$const]).'</td>';
		print '<td>'.$langs->trans($desc).'</td>';
		print '<td class="center" width="100">';
		$constante = 'FCKEDITOR_ENABLE_'.$const;
		$value = (isset($conf->global->$constante) ? $conf->global->$constante : 0);
		if ($value == 0) {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=enable_'.strtolower($const).'&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
		} elseif ($value == 1) {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=disable_'.strtolower($const).'&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
		}

		print "</td>";
		print '</tr>';
	}

	print '</table>'."\n";

	print '<br>'."\n";

	print '<form name="formtest" method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';

	// Skins
	show_skin(null, 1);
	print '<br>'."\n";

	$listofmodes = array('dolibarr_mailings', 'dolibarr_notes', 'dolibarr_details', 'dolibarr_readonly', 'Full', 'Full_inline');
	$linkstomode = '';
	foreach ($listofmodes as $newmode) {
		if ($linkstomode) {
			$linkstomode .= ' - ';
		}
		$linkstomode .= '<a href="'.$_SERVER["PHP_SELF"].'?mode='.$newmode.'">';
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
		$editor = new DolEditor('formtestfield', isset($conf->global->FCKEDITOR_TEST) ? $conf->global->FCKEDITOR_TEST : 'Test', '', 200, $mode, 'In', true, $uselocalbrowser, 1, 120, 8, $readonly);
		$editor->Create();
	} else {
		print '<div style="border: 1px solid #888;" contenteditable="true">';
		print $conf->global->FCKEDITOR_TEST;
		print '</div>';
	}
	print '<br><div class="center"><input class="button button-save" type="submit" name="save" value="'.$langs->trans("Save").'"></div>'."\n";
	print '<div id="divforlog"></div>';
	print '</form>'."\n";

	// Add env of ckeditor
	// This is to show how CKEditor detect browser to understand why editor is disabled or not. To help debug.
	/*
		print '<br><script language="javascript">
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
