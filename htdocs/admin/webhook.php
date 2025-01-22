<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * \file    webhook/admin/webhook.php
 * \ingroup webhook
 * \brief   Webhook setup page.
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/webhook/lib/webhook.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "webhook"));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('webhooksetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'myobject';


$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}

$formSetup = new FormSetup($db);


$setupnotempty = count($formSetup->items);


$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconst', 'aZ09');
	$maskvalue = GETPOST('maskvalue', 'alpha');

	if ($maskconst && preg_match('/_MASK$/', $maskconst)) {
		$res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'setmod') {
	// TODO Check if numbering module chosen can be activated by calling method canBeActivated
	$tmpobjectkey = GETPOST('object', 'aZ09');
	if (!empty($tmpobjectkey)) {
		$constforval = 'WEBHOOK_'.strtoupper($tmpobjectkey)."_ADDON";
		dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$tmpobjectkey = GETPOST('object', 'aZ09');
		if (!empty($tmpobjectkey)) {
			$constforval = 'WEBHOOK_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
			if (getDolGlobalString($constforval) == "$value") {
				dolibarr_del_const($db, $constforval, $conf->entity);
			}
		}
	}
} elseif ($action == 'setdoc') {
	// Set or unset default model
	$tmpobjectkey = GETPOST('object', 'aZ09');
	if (!empty($tmpobjectkey)) {
		$constforval = 'WEBHOOK_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity)) {
			// The constant that was read before the new set
			// We therefore requires a variable to have a coherent view
			$conf->global->$constforval = $value;
		}

		// We disable/enable the document template (into llx_document_model table)
		$ret = delDocumentModel($value, $type);
		if ($ret > 0) {
			$ret = addDocumentModel($value, $type, $label, $scandir);
		}
	}
} elseif ($action == 'unsetdoc') {
	$tmpobjectkey = GETPOST('object', 'aZ09');
	if (!empty($tmpobjectkey)) {
		$constforval = 'WEBHOOK_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		dolibarr_del_const($db, $constforval, $conf->entity);
	}
}



/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "WebhookSetup";

llxHeader('', $langs->trans($page_name), $help_url, '', 0, 0, '', '', '', 'mod-admin page-webhook');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = webhookAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "webhook");

print '<br>';

// Setup page goes here
print '<span class="opacitymedium">'.$langs->trans("WebhookSetupPage", $langs->transnoentitiesnoconv("Targets")).'...</span><br><br>';


if ($action == 'edit') {
	if ($useFormSetup && (float) DOL_VERSION >= 15) {  // @phpstan-ignore-line
		print $formSetup->generateOutput(true);
	} else {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

		// @phan-suppress-next-line PhanEmptyForeach
		foreach ($arrayofparameters as $constname => $val) {
			if ($val['enabled'] == 1) {
				$setupnotempty++;
				print '<tr class="oddeven"><td>';
				$tooltiphelp = (($langs->trans($constname . 'Tooltip') != $constname . 'Tooltip') ? $langs->trans($constname . 'Tooltip') : '');
				print '<span id="helplink'.$constname.'" class="spanforparamtooltip">'.$form->textwithpicto($langs->trans($constname), $tooltiphelp, 1, 'info', '', 0, 3, 'tootips'.$constname).'</span>';
				print '</td><td>';

				if ($val['type'] == 'textarea') {
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
					print $formother->select_categories($tmp[1], getDolGlobalString($constname), $constname, 0, $langs->trans('CustomersProspectsCategoriesShort'));
				} elseif (preg_match('/thirdparty_type/', $val['type'])) {
					require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
					$formcompany = new FormCompany($db);
					print $formcompany->selectProspectCustomerType(getDolGlobalString($constname), $constname);
				} elseif ($val['type'] == 'securekey') {
					print '<input required="required" type="text" class="flat" id="'.$constname.'" name="'.$constname.'" value="'.(GETPOST($constname, 'alpha') ? GETPOST($constname, 'alpha') : getDolGlobalString($constname)).'" size="40">';
					if (!empty($conf->use_javascript_ajax)) {
						print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token'.$constname.'" class="linkobject"');
					}

					// Add button to autosuggest a key
					include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
					print dolJSToSetRandomPassword($constname, 'generate_token'.$constname);
				} elseif ($val['type'] == 'product') {
					if (isModEnabled("product") || isModEnabled("service")) {
						$selected = getDolGlobalString($constname);
						$form->select_produits($selected, $constname, '', 0);
					}
				} else {
					print '<input name="'.$constname.'"  class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'" value="'.getDolGlobalString($constname).'">';
				}
				print '</td></tr>';
			}
		}
		print '</table>';

		print '<br><div class="center">';
		print '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
		print '</div>';

		print '</form>';
	}

	print '<br>';
} else {
	if ($useFormSetup && (float) DOL_VERSION >= 15) {  // @phpstan-ignore-line
		if (!empty($formSetup->items)) {
			print $formSetup->generateOutput();
		}
	} else {
		if (!empty($arrayofparameters)) {
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

			foreach ($arrayofparameters as $constname => $val) {
				if ($val['enabled'] == 1) {
					$setupnotempty++;
					print '<tr class="oddeven"><td>';
					$tooltiphelp = (($langs->trans($constname . 'Tooltip') != $constname . 'Tooltip') ? $langs->trans($constname . 'Tooltip') : '');
					print $form->textwithpicto($langs->trans($constname), $tooltiphelp);
					print '</td><td>';

					if ($val['type'] == 'textarea') {
						print dol_nl2br(getDolGlobalString($constname));
					} elseif ($val['type'] == 'html') {
						print getDolGlobalString($constname);
					} elseif ($val['type'] == 'yesno') {
						print ajax_constantonoff($constname);
					} elseif (preg_match('/emailtemplate:/', $val['type'])) {
						include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
						$formmail = new FormMail($db);

						$tmp = explode(':', $val['type']);

						$template = $formmail->getEMailTemplate($db, $tmp[1], $user, $langs, getDolGlobalString($constname));
						if ($template < 0) {
							setEventMessages(null, $formmail->errors, 'errors');
						}
						print $langs->trans($template->label);
					} elseif (preg_match('/category:/', $val['type'])) {
						$c = new Categorie($db);
						$result = $c->fetch(getDolGlobalString($constname));
						if ($result < 0) {
							setEventMessages(null, $c->errors, 'errors');
						} elseif ($result > 0) {
							$ways = $c->print_all_ways(' &gt;&gt; ', 'none', 0, 1); // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formatted text
							$toprint = array();
							foreach ($ways as $way) {
								$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"' . ($c->color ? ' style="background: #' . $c->color . ';"' : ' style="background: #bbb"') . '>' . $way . '</li>';
							}
							print '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
						}
					} elseif (preg_match('/thirdparty_type/', $val['type'])) {
						if (getDolGlobalInt($constname) == 2) {
							print $langs->trans("Prospect");
						} elseif (getDolGlobalInt($constname) == 3) {
							print $langs->trans("ProspectCustomer");
						} elseif (getDolGlobalInt($constname) == 1) {
							print $langs->trans("Customer");
						} elseif (getDolGlobalInt($constname) == 0) {
							print $langs->trans("NorProspectNorCustomer");
						}
					} elseif ($val['type'] == 'product') {
						$product = new Product($db);
						$resprod = $product->fetch(getDolGlobalInt($constname));
						if ($resprod > 0) {
							print $product->ref;
						} elseif ($resprod < 0) {
							setEventMessages(null, $object->errors, "errors");
						}
					} else {
						print getDolGlobalString($constname);
					}
					print '</td></tr>';
				}
			}

			print '</table>';
		}
	}

	if ($setupnotempty) {
		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
		print '</div>';
	} else {
		//print '<br>'.$langs->trans("NothingToSetup");
	}
}

/*
if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("NothingToSetup");
}
*/

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
