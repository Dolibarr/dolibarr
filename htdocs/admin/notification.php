<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2015 Laurent Destailleur  <eldy@users.sourceforge.org>
 * Copyright (C) 2013      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Bahfir Abbes         <contact@dolibarrpar.org>
 * Copyright (C) 2020      Thibault FOUCART     <suport@ptibogxiv.net>
 * Copyright (C) 2022      Anthony Berton     	<anthony.berton@bb2a.fr>
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
 *	    \file       htdocs/admin/notification.php
 *		\ingroup    notification
 *		\brief      Page to setup notification module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modNotification_Notification.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'other', 'orders', 'propal', 'bills', 'errors', 'mails'));

// Security check
if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$error = 0;


/*
 * Actions
 */

// Action to update or add a constant
if ($action == 'settemplates' && $user->admin) {
	$db->begin();

	if (!$error && is_array($_POST)) {
		$reg = array();
		foreach ($_POST as $key => $val) {
			if (!preg_match('/^constvalue_(.*)_TEMPLATE/', $key, $reg)) {
				continue;
			}

			$triggername = $reg[1];
			$constvalue = GETPOST($key, 'alpha');
			$consttype = 'emailtemplate:xxx';
			$tmparray = explode(':', $constvalue);
			if (!empty($tmparray[0]) && !empty($tmparray[1])) {
				$constvalue = $tmparray[0];
				$consttype = 'emailtemplate:'.$tmparray[1];
				//var_dump($constvalue);
				//var_dump($consttype);
				$res = dolibarr_set_const($db, $triggername.'_TEMPLATE', $constvalue, $consttype, 0, '', $conf->entity);
				if ($res < 0) {
					$error++;
					break;
				}
			} else {
				$res = dolibarr_del_const($db, $triggername.'_TEMPLATE', $conf->entity);
			}
		}
	}


	if (!$error) {
		$db->commit();

		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();

		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'setvalue' && $user->admin) {
	$db->begin();

	$result = dolibarr_set_const($db, "NOTIFICATION_EMAIL_FROM", GETPOST("email_from", "alphawithlgt"), 'chaine', 0, '', $conf->entity);
	if ($result < 0) {
		$error++;
	}

	$result = dolibarr_set_const($db, "NOTIFICATION_EMAIL_DISABLE_CONFIRM_MESSAGE", GETPOST("notif_disable", "alphawithlgt"), 'chaine', 0, '', $conf->entity);
	if ($result < 0) {
		$error++;
	}

	if (!$error) {
		$db->commit();

		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();

		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


if ($action == 'setfixednotif' && $user->admin) {
	$db->begin();

	if (!$error && is_array($_POST)) {
		$reg = array();
		foreach ($_POST as $key => $val) {
			if (!preg_match('/^NOTIF_(.*)_key$/', $key, $reg)) {
				continue;
			}

			$newval = '';
			$newkey = '';

			$shortkey = preg_replace('/_key$/', '', $key);
			//print $shortkey.'<br>';

			if (preg_match('/^NOTIF_(.*)_old_(.*)_key/', $key, $reg)) {
				dolibarr_del_const($db, 'NOTIFICATION_FIXEDEMAIL_'.$reg[1].'_THRESHOLD_HIGHER_'.$reg[2], $conf->entity);

				$newkey = 'NOTIFICATION_FIXEDEMAIL_'.$reg[1].'_THRESHOLD_HIGHER_'.((int) GETPOST($shortkey.'_amount'));
				$newval = GETPOST($shortkey.'_key');
				//print $newkey.' - '.$newval.'<br>';
			} elseif (preg_match('/^NOTIF_(.*)_new_key/', $key, $reg)) {
				// Add a new entry
				$newkey = 'NOTIFICATION_FIXEDEMAIL_'.$reg[1].'_THRESHOLD_HIGHER_'.((int) GETPOST($shortkey.'_amount'));
				$newval = GETPOST($shortkey.'_key');
			}

			if ($newkey && $newval) {
				$result = dolibarr_set_const($db, $newkey, $newval, 'chaine', 0, '', $conf->entity);
			}
		}
	}

	if (!$error) {
		$db->commit();

		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();

		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}



/*
 *	View
 */

$form = new Form($db);
$notify = new Notify($db);

llxHeader('', $langs->trans("NotificationSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("NotificationSetup"), $linkback, 'title_setup');

print '<span class="opacitymedium">';
print $langs->trans("NotificationsDesc").'<br>';
print $langs->trans("NotificationsDescUser").'<br>';
if (!empty($conf->societe->enabled)) {
	print $langs->trans("NotificationsDescContact").'<br>';
}
print $langs->trans("NotificationsDescGlobal").'<br>';
print '</span>';
print '<br>';

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setvalue">';

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven"><td>';
print $langs->trans("NotificationEMailFrom").'</td>';
print '<td>';
print img_picto('', 'email', 'class="pictofixedwidth"');
print '<input class="width150 quatrevingtpercentminusx" type="email" name="email_from" value="'.getDolGlobalString('NOTIFICATION_EMAIL_FROM').'">';
if (!empty($conf->global->NOTIFICATION_EMAIL_FROM) && !isValidEmail($conf->global->NOTIFICATION_EMAIL_FROM)) {
	print ' '.img_warning($langs->trans("ErrorBadEMail"));
}
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("NotificationDisableConfirmMessageContact").'</td>';
print '<td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('NOTIFICATION_EMAIL_DISABLE_CONFIRM_MESSAGE_CONTACT');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("NOTIFICATION_EMAIL_DISABLE_CONFIRM_MESSAGE_CONTACT", $arrval, getDolGlobalString('NOTIFICATION_EMAIL_DISABLE_CONFIRM_MESSAGE_CONTACT'));
}
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("NotificationDisableConfirmMessageUser").'</td>';
print '<td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('NOTIFICATION_EMAIL_DISABLE_CONFIRM_MESSAGE_USER');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("NOTIFICATION_EMAIL_DISABLE_CONFIRM_MESSAGE_USER", $arrval, getDolGlobalString('NOTIFICATION_EMAIL_DISABLE_CONFIRM_MESSAGE_USER'));
}
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans("NotificationDisableConfirmMessageFix").'</td>';
print '<td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('NOTIFICATION_EMAIL_DISABLE_CONFIRM_MESSAGE_FIX');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("NOTIFICATION_EMAIL_DISABLE_CONFIRM_MESSAGE_FIX", $arrval, getDolGlobalString('NOTIFICATION_EMAIL_DISABLE_CONFIRM_MESSAGE_FIX'));
}
print '</td>';
print '</tr>';
print '</table>';
print '</div>';

print $form->buttonsSaveCancel("Save", '');

print '</form>';


print '<br><br>';


print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="settemplates">';

// Notification per contacts
$title = $langs->trans("TemplatesForNotifications");

print load_fiche_titre($title, '', 'email');

// Load array of available notifications
$notificationtrigger = new InterfaceNotification($db);
$listofnotifiedevents = $notificationtrigger->getListOfManagedEvents();

// Editing global variables not related to a specific theme
$constantes = array();
foreach ($listofnotifiedevents as $notifiedevent) {
	$label = $langs->trans("Notify_".$notifiedevent['code']); //!=$langs->trans("Notify_".$notifiedevent['code'])?$langs->trans("Notify_".$notifiedevent['code']):$notifiedevent['label'];
	$elementLabel = $langs->trans(ucfirst($notifiedevent['elementtype']));

	$model = $notifiedevent['elementtype'];

	if ($notifiedevent['elementtype'] == 'order_supplier') {
		$elementLabel = $langs->trans('SupplierOrder');
	} elseif ($notifiedevent['elementtype'] == 'propal') {
		$elementLabel = $langs->trans('Proposal');
	} elseif ($notifiedevent['elementtype'] == 'facture') {
		$elementLabel = $langs->trans('Bill');
	} elseif ($notifiedevent['elementtype'] == 'commande') {
		$elementLabel = $langs->trans('Order');
	} elseif ($notifiedevent['elementtype'] == 'ficheinter') {
		$elementLabel = $langs->trans('Intervention');
	} elseif ($notifiedevent['elementtype'] == 'shipping') {
		$elementLabel = $langs->trans('Shipping');
	} elseif ($notifiedevent['elementtype'] == 'expensereport' || $notifiedevent['elementtype'] == 'expense_report') {
		$elementLabel = $langs->trans('ExpenseReport');
	}

	if ($notifiedevent['elementtype'] == 'propal') {
		$model = 'propal_send';
	} elseif ($notifiedevent['elementtype'] == 'commande') {
		$model = 'order_send';
	} elseif ($notifiedevent['elementtype'] == 'facture') {
		$model = 'facture_send';
	} elseif ($notifiedevent['elementtype'] == 'shipping') {
		$model = 'shipping_send';
	} elseif ($notifiedevent['elementtype'] == 'ficheinter') {
		$model = 'fichinter_send';
	} elseif ($notifiedevent['elementtype'] == 'expensereport') {
		$model = 'expensereport_send';
	} elseif ($notifiedevent['elementtype'] == 'order_supplier') {
		$model = 'order_supplier_send';
		// } elseif ($notifiedevent['elementtype'] == 'invoice_supplier') $model = 'invoice_supplier_send';
	} elseif ($notifiedevent['elementtype'] == 'member') {
		$model = 'member';
	}

	$constantes[$notifiedevent['code'].'_TEMPLATE'] = array('type'=>'emailtemplate:'.$model, 'label'=>$label);
}

$helptext = '';
form_constantes($constantes, 3, $helptext, 'EmailTemplate');

print $form->buttonsSaveCancel("Save", '');

/*
} else {
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Label").'</td>';
	//print '<td class="right">'.$langs->trans("NbOfTargetedContacts").'</td>';
	print "</tr>\n";

	print '<tr class="oddeven">';
	print '<td>';

	$i = 0;
	foreach ($listofnotifiedevents as $notifiedevent) {
		$label = $langs->trans("Notify_".$notifiedevent['code']); //!=$langs->trans("Notify_".$notifiedevent['code'])?$langs->trans("Notify_".$notifiedevent['code']):$notifiedevent['label'];
		$elementLabel = $langs->trans(ucfirst($notifiedevent['elementtype']));

		if ($notifiedevent['elementtype'] == 'order_supplier') {
			$elementLabel = $langs->trans('SupplierOrder');
		} elseif ($notifiedevent['elementtype'] == 'propal') {
			$elementLabel = $langs->trans('Proposal');
		} elseif ($notifiedevent['elementtype'] == 'facture') {
			$elementLabel = $langs->trans('Bill');
		} elseif ($notifiedevent['elementtype'] == 'commande') {
			$elementLabel = $langs->trans('Order');
		} elseif ($notifiedevent['elementtype'] == 'ficheinter') {
			$elementLabel = $langs->trans('Intervention');
		} elseif ($notifiedevent['elementtype'] == 'shipping') {
			$elementLabel = $langs->trans('Shipping');
		} elseif ($notifiedevent['elementtype'] == 'expensereport' || $notifiedevent['elementtype'] == 'expense_report') {
			$elementLabel = $langs->trans('ExpenseReport');
		}

		if ($i) {
			print ', ';
		}
		print $label;

		$i++;
	}

	print '</td></tr>';
	print '</table>';

	print '<div class="opacitymedium">';
	print '* '.$langs->trans("GoOntoUserCardToAddMore").'<br>';
	if (!empty($conf->societe->enabled)) {
		print '** '.$langs->trans("GoOntoContactCardToAddMore").'<br>';
	}
	print '</div>';
}
*/

print '</form>';


print '<br><br>';


print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setfixednotif">';
print '<input type="hidden" name="page_y" value="">';

print load_fiche_titre($langs->trans("ListOfFixedNotifications"), '', 'email');

print '<div class="info">';
print $langs->trans("Note").':<br>';
print '* '.$langs->trans("GoOntoUserCardToAddMore").'<br>';
if (!empty($conf->societe->enabled)) {
	print '** '.$langs->trans("GoOntoContactCardToAddMore").'<br>';
}
print '</div>';

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Module").'</td>';
print '<td>'.$langs->trans("Code").'</td>';
print '<td>'.$langs->trans("Label").'</td>';
print '<td>'.$langs->trans("FixedEmailTarget").'</td>';
print '<td>'.$langs->trans("Threshold").'</td>';
print '<td></td>';
print "</tr>\n";

foreach ($listofnotifiedevents as $notifiedevent) {
	$label = $langs->trans("Notify_".$notifiedevent['code']); //!=$langs->trans("Notify_".$notifiedevent['code'])?$langs->trans("Notify_".$notifiedevent['code']):$notifiedevent['label'];

	$elementPicto = $notifiedevent['elementtype'];
	$elementLabel = $langs->trans(ucfirst($notifiedevent['elementtype']));
	// Special cases
	if ($notifiedevent['elementtype'] == 'order_supplier') {
		$elementPicto = 'supplier_order';
		$elementLabel = $langs->trans('SupplierOrder');
	} elseif ($notifiedevent['elementtype'] == 'propal') {
		$elementLabel = $langs->trans('Proposal');
	} elseif ($notifiedevent['elementtype'] == 'facture') {
		$elementPicto = 'bill';
		$elementLabel = $langs->trans('Bill');
	} elseif ($notifiedevent['elementtype'] == 'commande') {
		$elementPicto = 'order';
		$elementLabel = $langs->trans('Order');
	} elseif ($notifiedevent['elementtype'] == 'ficheinter') {
		$elementPicto = 'intervention';
		$elementLabel = $langs->trans('Intervention');
	} elseif ($notifiedevent['elementtype'] == 'shipping') {
		$elementPicto = 'shipment';
		$elementLabel = $langs->trans('Shipping');
	} elseif ($notifiedevent['elementtype'] == 'expensereport' || $notifiedevent['elementtype'] == 'expense_report') {
		$elementPicto = 'expensereport';
		$elementLabel = $langs->trans('ExpenseReport');
	}

	$labelfortrigger = 'AmountHT';
	$codehasnotrigger = 0;
	if (preg_match('/^HOLIDAY/', $notifiedevent['code'])) {
		$codehasnotrigger++;
	}

	print '<tr class="oddeven">';
	print '<td>';
	print img_picto('', $elementPicto, 'class="pictofixedwidth"');
	print $elementLabel;
	print '</td>';
	print '<td>'.$notifiedevent['code'].'</td>';
	print '<td><span class="opacitymedium">'.$label.'</span></td>';
	print '<td>';
	$inputfieldalreadyshown = 0;
	// Notification with threshold
	foreach ($conf->global as $key => $val) {
		if ($val == '' || !preg_match('/^NOTIFICATION_FIXEDEMAIL_'.$notifiedevent['code'].'_THRESHOLD_HIGHER_(.*)/', $key, $reg)) {
			continue;
		}

		$param = 'NOTIFICATION_FIXEDEMAIL_'.$notifiedevent['code'].'_THRESHOLD_HIGHER_'.$reg[1];
		$value = GETPOST('NOTIF_'.$notifiedevent['code'].'_old_'.$reg[1].'_key') ?GETPOST('NOTIF_'.$notifiedevent['code'].'_old_'.$reg[1].'_key', 'alpha') : $conf->global->$param;

		$s = '<input type="text" class="minwidth200" name="NOTIF_'.$notifiedevent['code'].'_old_'.$reg[1].'_key" value="'.dol_escape_htmltag($value).'">'; // Do not use type="email" here, we must be able to enter a list of email with , separator.
		$arrayemail = explode(',', $value);
		$showwarning = 0;
		foreach ($arrayemail as $keydet => $valuedet) {
			$valuedet = trim($valuedet);
			if (!empty($valuedet) && !isValidEmail($valuedet, 1)) {
				$showwarning++;
			}
		}
		if ((!empty($conf->global->$param)) && $showwarning) {
			$s .= ' '.img_warning($langs->trans("ErrorBadEMail"));
		}
		print $form->textwithpicto($s, $langs->trans("YouCanUseCommaSeparatorForSeveralRecipients").'<br>'.$langs->trans("YouCanAlsoUseSupervisorKeyword"), 1, 'help', '', 0, 2);
		print '<br>';

		$inputfieldalreadyshown++;
	}
	// New entry input fields
	if (empty($inputfieldalreadyshown) || !$codehasnotrigger) {
		$s = '<input type="text" class="minwidth200" name="NOTIF_'.$notifiedevent['code'].'_new_key" value="">'; // Do not use type="email" here, we must be able to enter a list of email with , separator.
		print $form->textwithpicto($s, $langs->trans("YouCanUseCommaSeparatorForSeveralRecipients").'<br>'.$langs->trans("YouCanAlsoUseSupervisorKeyword"), 1, 'help', '', 0, 2);
	}
	print '</td>';

	print '<td>';
	// Notification with threshold
	$inputfieldalreadyshown = 0;
	foreach ($conf->global as $key => $val) {
		if ($val == '' || !preg_match('/^NOTIFICATION_FIXEDEMAIL_'.$notifiedevent['code'].'_THRESHOLD_HIGHER_(.*)/', $key, $reg)) {
			continue;
		}

		if (!$codehasnotrigger) {
			print $langs->trans($labelfortrigger).' >= <input type="text" size="4" name="NOTIF_'.$notifiedevent['code'].'_old_'.$reg[1].'_amount" value="'.dol_escape_htmltag($reg[1]).'">';
			print '<br>';

			$inputfieldalreadyshown++;
		}
	}
	// New entry input fields
	if (!$codehasnotrigger) {
		print $langs->trans($labelfortrigger).' >= <input type="text" size="4" name="NOTIF_'.$notifiedevent['code'].'_new_amount" value="">';
	}
	print '</td>';

	print '<td>';
	// TODO Add link to show message content

	print '</td>';
	print '</tr>';
}
print '</table>';
print '</div>';

print $form->buttonsSaveCancel("Save", '');

print '</form>';

// End of page
llxFooter();
$db->close();
