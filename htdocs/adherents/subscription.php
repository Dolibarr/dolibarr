<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2018	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2017	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2015-2024	Alexandre Spangaro			<aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2025  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2019		Thibault FOUCART			<support@ptibogxiv.net>
 * Copyright (C) 2023		Waël Almoman				<info@almoman.com>
 * Copyright (C) 2024-2026	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026		Jon Bendtsen				<jon.bendtsen.github@jonb.dk>
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
 *       \file       htdocs/adherents/subscription.php
 *       \ingroup    member
 *       \brief      tab for Adding, editing, deleting a member's memberships
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var ExtraFields $extrafields
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

$langs->loadLangs(array("companies", "bills", "orders", "members", "users", "mails", 'other'));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ09');
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$id = GETPOSTINT('rowid') ? GETPOSTINT('rowid') : GETPOSTINT('id');
$rowid = $id;
$ref = GETPOST('ref', 'alphanohtml');
$typeid = GETPOSTINT('typeid');
$cancel = GETPOST('cancel', 'alpha');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
	$sortfield = "c.rowid";
}
if (!$sortorder) {
	$sortorder = "DESC";
}

$object = new Adherent($db);

$adht = new AdherentType($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$errmsg = '';

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('subscription'));

// PDF
$hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
$hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
$hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));

$datefrom = 0;
$dateto = 0;
$paymentdate = -1;

// Fetch object
if ($id > 0 || !empty($ref)) {
	// Load member
	$result = $object->fetch($id, $ref);

	// Define variables to know what current user can do on users
	$canadduser = ($user->admin || $user->hasRight("user", "user", "creer"));
	// Define variables to know what current user can do on properties of user linked to edited member
	if ($object->user_id) {
		// $User is the user who edits, $object->user_id is the id of the related user in the edited member
		$caneditfielduser = ((($user->id == $object->user_id) && $user->hasRight("user", "self", "creer"))
			|| (($user->id != $object->user_id) && $user->hasRight("user", "user", "creer")));
		$caneditpassworduser = ((($user->id == $object->user_id) && $user->hasRight("user", "self", "password"))
			|| (($user->id != $object->user_id) && $user->hasRight("user", "user", "password")));
	}
}

// Define variables to determine what the current user can do on the members
$permissiontoaddmember = $user->hasRight('adherent', 'creer');
$permissiontoeditextra = $permissiontoaddmember;
if (GETPOST('attribute', 'aZ09') && isset($extrafields->attributes[$object->table_element]['perms'][GETPOST('attribute', 'aZ09')])) {
	// For action 'update_extras', is there a specific permission set for the attribute to update
	$permissiontoeditextra = dol_eval((string) $extrafields->attributes[$object->table_element]['perms'][GETPOST('attribute', 'aZ09')]);
}

// Security check
$result = restrictedArea($user, 'adherent', $object->id, '', '', 'socid', 'rowid', 0);

// Preserve checkbox selections for linktoobject option
$invoices_to_link_post = GETPOST('invoices_to_link', 'array:int');
$orders_to_link_post = GETPOST('orders_to_link', 'array:int');
$invoicereftolink_post = GETPOST('invoicereftolink', 'alpha');
$orderreftolink_post = GETPOST('orderreftolink', 'alpha');

/*
 * 	Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook) && $action == 'update_extras' && $permissiontoeditextra) {
	$error = 0;
	$object->oldcopy = dol_clone($object, 2);  // @phan-suppress-current-line PhanTypeMismatchProperty
	$attribute_name = GETPOST('attribute', 'aZ09');

	// Fill array 'array_options' with data from update form
	$ret = $extrafields->setOptionalsFromPost(null, $object, $attribute_name);
	if ($ret < 0) {
		$error++;
	}
	if (!$error) {
		$result = $object->updateExtraField($attribute_name, 'MEMBER_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
		}
	}
	if ($error) {
		$action = 'edit_extras';
	}
}

// Create third party from a member
if (empty($reshook) && $action == 'confirm_create_thirdparty' && $confirm == 'yes' && $user->hasRight('societe', 'creer')) {
	if ($result > 0) {
		// Creation of thirdparty
		$company = new Societe($db);
		$result = $company->create_from_member($object, GETPOST('companyname', 'alpha'), GETPOST('companyalias', 'alpha'), GETPOST('customercode', 'alpha'));

		if ($result < 0) {
			$langs->load("errors");
			setEventMessages($company->error, $company->errors, 'errors');
		} else {
			$object->socid = $result;

			$action = 'createsubscription';
		}
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if (empty($reshook) && $action == 'setuserid' && ($user->hasRight('user', 'self', 'creer') || $user->hasRight('user', 'user', 'creer'))) {
	$error = 0;
	if (!$user->hasRight('user', 'user', 'creer')) {    // If can edit only itself user, we can link to itself only
		if (GETPOSTINT("userid") != $user->id && GETPOSTINT("userid") != $object->user_id) {
			$error++;
			setEventMessages($langs->trans("ErrorUserPermissionAllowsToLinksToItselfOnly"), null, 'errors');
		}
	}

	if (!$error) {
		if (GETPOSTINT("userid") != $object->user_id) {  // If link differs from currently in database
			$result = $object->setUserId(GETPOSTINT("userid"));
			if ($result < 0) {
				dol_print_error(null, $object->error);
			}
			$action = '';
		}
	}
}

if (empty($reshook) && $action == 'setsocid' && $permissiontoaddmember) {
	$error = 0;
	if (GETPOSTINT('socid') != $object->socid) {    // If link differs from currently in database
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "adherent";
		$sql .= " WHERE fk_soc = " . ((int) GETPOSTINT('socid'));
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj && $obj->rowid > 0) {
				$othermember = new Adherent($db);
				$othermember->fetch($obj->rowid);
				$thirdparty = new Societe($db);
				$thirdparty->fetch(GETPOSTINT('socid'));
				$error++;
				setEventMessages($langs->trans("ErrorMemberIsAlreadyLinkedToThisThirdParty", $othermember->getFullName($langs), $othermember->login, $thirdparty->name), null, 'errors');
			}
		}

		if (!$error) {
			$result = $object->setThirdPartyId(GETPOSTINT('socid'));
			if ($result < 0) {
				dol_print_error(null, $object->error);
			}
			$action = '';
		}
	}
}

if (empty($reshook) && $user->hasRight('adherent', 'cotisation', 'creer') && $action == 'subscription' && !$cancel) {
	$error = 0;

	$langs->load("banks");

	$result = $object->fetch($rowid);
	$result = $adht->fetch($object->typeid);

	// Subscription information
	$datesubscription = 0;
	$datesubend = 0;
	$defaultdelay = !empty($adht->duration_value) ? $adht->duration_value : 1;
	$defaultdelayunit = !empty($adht->duration_unit) ? $adht->duration_unit : 'y';
	$paymentdate = ''; // Do not use 0 here, default value is '' that means not filled where 0 means 1970-01-01
	if (GETPOSTINT("reyear") && GETPOSTINT("remonth") && GETPOSTINT("reday")) {
		$datesubscription = dol_mktime(0, 0, 0, GETPOSTINT("remonth"), GETPOSTINT("reday"), GETPOSTINT("reyear"));
	}
	if (GETPOSTINT("endyear") && GETPOSTINT("endmonth") && GETPOSTINT("endday")) {
		$datesubend = dol_mktime(0, 0, 0, GETPOSTINT("endmonth"), GETPOSTINT("endday"), GETPOSTINT("endyear"));
	}
	if (GETPOSTINT("paymentyear") && GETPOSTINT("paymentmonth") && GETPOSTINT("paymentday")) {
		$paymentdate = dol_mktime(0, 0, 0, GETPOSTINT("paymentmonth"), GETPOSTINT("paymentday"), GETPOSTINT("paymentyear"));
	}
	$amount = price2num(GETPOST("subscription", 'alpha')); // Amount of subscription
	$label = GETPOST("label");

	// Payment information
	$accountid = GETPOSTINT("accountid");
	$operation = GETPOST("operation", "alphanohtml"); // Payment mode
	$num_chq = GETPOST("num_chq", "alphanohtml");
	$emetteur_nom = GETPOST("chqemetteur");
	$emetteur_banque = GETPOST("chqbank");
	$option = GETPOST("paymentsave");
	if (empty($option)) {
		$option = 'none';
	}
	$sendalsoemail = GETPOST("sendmail", 'alpha');

	// Check parameters
	if (!$datesubscription) {
		$error++;
		$langs->load("errors");
		$errmsg = $langs->trans("ErrorBadDateFormat", $langs->transnoentitiesnoconv("DateSubscription"));
		setEventMessages($errmsg, null, 'errors');
		$action = 'createsubscription';
	}
	if (GETPOST('end') && !$datesubend) {
		$error++;
		$langs->load("errors");
		$errmsg = $langs->trans("ErrorBadDateFormat", $langs->transnoentitiesnoconv("DateEndSubscription"));
		setEventMessages($errmsg, null, 'errors');
		$action = 'createsubscription';
	}
	if (!$datesubend) {
		$datesubend = dol_time_plus_duree(dol_time_plus_duree($datesubscription, $defaultdelay, $defaultdelayunit), -1, 'd');
	}
	if (($option == 'bankviainvoice' || $option == 'bankdirect') && !$paymentdate) {
		$error++;
		$errmsg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DatePayment"));
		setEventMessages($errmsg, null, 'errors');
		$action = 'createsubscription';
	}

	// Check if a payment is mandatory or not
	if ($adht->subscription) {	// Member type need subscriptions
		if (!is_numeric($amount)) {
			// If field is '' or not a numeric value
			$errmsg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount"));
			setEventMessages($errmsg, null, 'errors');
			$error++;
			$action = 'createsubscription';
		} else {
			// If an amount has been provided, we check also fields that becomes mandatory when amount is not null.
			if (isModEnabled('bank') && GETPOST("paymentsave") != 'none' && GETPOST("paymentsave") != 'linktoobject') {
				if (GETPOST("subscription")) {
					if (!GETPOST("label")) {
						$errmsg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Label"));
						setEventMessages($errmsg, null, 'errors');
						$error++;
						$action = 'createsubscription';
					}
					if (GETPOST("paymentsave") != 'invoiceonly' && !GETPOST("operation") && GETPOST("paymentsave") != 'linktoobject') {
						$errmsg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode"));
						setEventMessages($errmsg, null, 'errors');
						$error++;
						$action = 'createsubscription';
					}
					if (GETPOST("paymentsave") != 'invoiceonly' && !(GETPOSTINT("accountid") > 0) && GETPOST("paymentsave") != 'linktoobject') {
						$errmsg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("FinancialAccount"));
						setEventMessages($errmsg, null, 'errors');
						$error++;
						$action = 'createsubscription';
					}
				} else {
					if (GETPOSTINT("accountid")) {
						$errmsg = $langs->trans("ErrorDoNotProvideAccountsIfNullAmount");
						setEventMessages($errmsg, null, 'errors');
						$error++;
						$action = 'createsubscription';
					}
				}
			}
		}
	}

	// Check if user filled link fields but chose another option than "linktoobject"
	if ($action == 'subscription' && $option != 'linktoobject' && $user->hasRight('adherent', 'cotisation', 'creer')) {
		$link_inputs_exist = (!empty($invoices_to_link_post) || !empty($orders_to_link_post) || !empty($invoicereftolink_post) || !empty($orderreftolink_post));
		if ($link_inputs_exist) {
			setEventMessages($langs->trans("ErrorLinkFieldsFilledButPaymentSaveNone"), null, 'errors');
			$error++;
			$action = 'createsubscription';
		}
	}
	// Validate manual refs before creating subscription (only for linktoobject option)
	if ($action == 'subscription' && $option == 'linktoobject' && $user->hasRight('adherent', 'cotisation', 'creer')) {
		if (!empty($invoicereftolink_post)) {
			$tmpinvoice = new Facture($db);
			if ($tmpinvoice->fetch(0, $invoicereftolink_post) <= 0) {
				$error++;
				setEventMessages($langs->trans("ErrorInvoiceNotFound", $invoicereftolink_post), null, 'errors');
				$action = 'createsubscription';
			}
			unset($tmpinvoice);
		}
		if (!empty($orderreftolink_post)) {
			$tmporder = new Commande($db);
			if ($tmporder->fetch(0, $orderreftolink_post) <= 0) {
				$error++;
				setEventMessages($langs->trans("ErrorOrderNotFound", $orderreftolink_post), null, 'errors');
				$action = 'createsubscription';
			}
			unset($tmporder);
		}
	}
	// Record the subscription then complementary actions
	if (!$error && $action == 'subscription') {		// Test on permission already done
		$db->begin();

		// Create subscription
		$crowid = $object->subscription($datesubscription, (float) $amount, $accountid, $operation, $label, $num_chq, $emetteur_nom, $emetteur_banque, $datesubend);
		if ($crowid <= 0) {
			$error++;
			$errmsg = $object->error;
			setEventMessages($object->error, $object->errors, 'errors');
		}

		if (!$error) {
			$result = $object->subscriptionComplementaryActions($crowid, $option, $accountid, $datesubscription, $paymentdate, $operation, $label, (float) $amount, $num_chq, $emetteur_nom, $emetteur_banque);
			if ($result < 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				// If an invoice was created, it is into $object->invoice
			}
		}

		// ===== LINK TO EXISTING OBJECTS =====
		if ($option == 'linktoobject' && $crowid > 0) {
			$subscription = new Subscription($db);
			if ($subscription->fetch($crowid) > 0) {
				// Get thirdparty of the member for security validation
				$adhlink = new Adherent($db);
				$adhlink->fetch($subscription->fk_adherent);
				$thirdpartyidtolink = $adhlink->fk_soc;

				// Track links created in this request so we skip duplicates
				// (e.g. object both checked in list and typed as manual ref)
				$existinglinks = array();
				$nbcreatedlinks = 0;

				// --- Selected invoices (checkboxes) - must belong to member's thirdparty ---
				if (!empty($invoices_to_link_post)) {
					foreach ($invoices_to_link_post as $invoiceid) {
						$tmpinvoice = new Facture($db);
						if ($tmpinvoice->fetch($invoiceid) > 0) {
							if ($tmpinvoice->socid != $thirdpartyidtolink) {
								setEventMessages($langs->trans("ErrorInvoiceNotLinkedToThirdpartyOfMember", $tmpinvoice->ref), null, 'errors');
								continue;
							}
							if (!empty($existinglinks['facture-'.$tmpinvoice->id])) {
								continue;	// Already linked, skip silently
							}
							if ($subscription->add_object_linked('facture', $tmpinvoice->id) > 0) {
								$nbcreatedlinks++;
								$existinglinks['facture-'.$tmpinvoice->id] = 1;
							} else {
								setEventMessages($subscription->error, $subscription->errors, 'errors');
							}
						}
						unset($tmpinvoice);
					}
				}

				// --- Manual invoice ref - allowed to link to any thirdparty ---
				if (!empty($invoicereftolink_post)) {
					$tmpinvoice = new Facture($db);
					if ($tmpinvoice->fetch(0, $invoicereftolink_post) > 0) {
						if (!empty($existinglinks['facture-'.$tmpinvoice->id])) {
							setEventMessages($langs->trans("ErrorObjectAlreadyLinked", $tmpinvoice->ref), null, 'warnings');
						} elseif ($subscription->add_object_linked('facture', $tmpinvoice->id) > 0) {
							$nbcreatedlinks++;
							$existinglinks['facture-'.$tmpinvoice->id] = 1;
						} else {
							setEventMessages($subscription->error, $subscription->errors, 'errors');
						}
					} else {
						setEventMessages($langs->trans("ErrorInvoiceNotFound", $invoicereftolink_post), null, 'errors');
					}
					unset($tmpinvoice);
				}

				// --- Selected orders (checkboxes) - must belong to member's thirdparty ---
				if (!empty($orders_to_link_post)) {
					foreach ($orders_to_link_post as $orderid) {
						$tmporder = new Commande($db);
						if ($tmporder->fetch($orderid) > 0) {
							if ($tmporder->socid != $thirdpartyidtolink) {
								setEventMessages($langs->trans("ErrorOrderNotLinkedToThirdpartyOfMember", $tmporder->ref), null, 'errors');
								continue;
							}
							if (!empty($existinglinks['commande-'.$tmporder->id])) {
								continue;	// Already linked, skip silently
							}
							if ($subscription->add_object_linked('commande', $tmporder->id) > 0) {
								$nbcreatedlinks++;
								$existinglinks['commande-'.$tmporder->id] = 1;
							} else {
								setEventMessages($subscription->error, $subscription->errors, 'errors');
							}
						}
						unset($tmporder);
					}
				}

				// --- Manual order ref - allowed to link to any thirdparty ---
				if (!empty($orderreftolink_post)) {
					$tmporder = new Commande($db);
					if ($tmporder->fetch(0, $orderreftolink_post) > 0) {
						if (!empty($existinglinks['commande-'.$tmporder->id])) {
							setEventMessages($langs->trans("ErrorObjectAlreadyLinked", $tmporder->ref), null, 'warnings');
						} elseif ($subscription->add_object_linked('commande', $tmporder->id) > 0) {
							$nbcreatedlinks++;
							$existinglinks['commande-'.$tmporder->id] = 1;
						} else {
							setEventMessages($subscription->error, $subscription->errors, 'errors');
						}
					} else {
						setEventMessages($langs->trans("ErrorOrderNotFound", $orderreftolink_post), null, 'errors');
					}
					unset($tmporder);
				}

				if ($nbcreatedlinks > 0) {
					setEventMessages($langs->trans("XLinksCreated", $nbcreatedlinks), null, 'mesgs');
				}
			}
			unset($subscription);
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
			$action = 'createsubscription';
		}

		if (!$error) {
			setEventMessages("SubscriptionRecorded", null, 'mesgs');
		}

		// Send email
		if (!$error) {
			// Send confirmation Email
			if ($object->email && $sendalsoemail) {   // $object is 'Adherent'
				$parameters = array(
					'datesubscription' => $datesubscription,
					'amount' => $amount,
					'accountid' => $accountid,
					'operation' => $operation,
					'label' => $label,
					'num_chq' => $num_chq,
					'emetteur_nom' => $emetteur_nom,
					'emetteur_banque' => $emetteur_banque,
					'datesubend' => $datesubend
				);
				$reshook = $hookmanager->executeHooks('sendMail', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}

				if (empty($reshook)) {
					$subject = '';
					$msg = '';

					// Send subscription email
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
					$formmail = new FormMail($db);
					// Set output language
					$outputlangs = new Translate('', $conf);
					$outputlangs->setDefaultLang(!empty($object->default_lang) ? $object->default_lang : (empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang));
					// Load traductions files required by page
					$outputlangs->loadLangs(array("main", "members"));

					// Get email content from template
					$arraydefaultmessage = null;
					$labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_SUBSCRIPTION');

					if (!empty($labeltouse)) {
						$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
					}

					if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
						$subject = (string) $arraydefaultmessage->topic;
						$msg     = (string) $arraydefaultmessage->content;
					}

					$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
					$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnSubscription()), $substitutionarray, $outputlangs);

					// Attach a file ?
					$file = '';
					$listofpaths = array();
					$listofnames = array();
					$listofmimes = array();
					if (is_object($object->invoice) && (!is_object($arraydefaultmessage) || intval($arraydefaultmessage->joinfiles))) {
						$invoicediroutput = $conf->facture->dir_output;
						$fileparams = dol_most_recent_file($invoicediroutput.'/'.$object->invoice->ref, preg_quote($object->invoice->ref, '/').'[^\-]+');
						$file = $fileparams['fullname'];

						$listofpaths = array($file);
						$listofnames = array(basename($file));
						$listofmimes = array(dol_mimetype($file));
					}

					$moreinheader = 'X-Dolibarr-Info: send_an_email by adherents/subscription.php'."\r\n";

					$result = $object->sendEmail($texttosend, $subjecttosend, $listofpaths, $listofmimes, $listofnames, "", "", 0, -1, '', $moreinheader);
					if ($result < 0) {
						$errmsg = $object->error;
						setEventMessages($object->error, $object->errors, 'errors');
					} else {
						setEventMessages($langs->trans("EmailSentToMember", $object->email), null, 'mesgs');
					}
				}
			} else {
				setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
			}
		}

		// Clean some POST vars
		if (!$error) {
			$accountid = '';
			$operation = '';
			$label = '';
			$num_chq = '';
			$option = '';
		}
	}
}



/*
 * View
 */

$form = new Form($db);

$now = dol_now();

$title = $langs->trans("Member")." - ".$langs->trans("Subscriptions");
$help_url = "EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder";

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-member page-card_subscription');


$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
$param .= '&id='.$rowid;
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
// Add $param from extra fields
//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';


if (! ($object->id > 0)) {
	$langs->load("errors");
	print $langs->trans("ErrorRecordNotFound");
}


$adht->fetch($object->typeid);

$defaultdelay = !empty($adht->duration_value) ? $adht->duration_value : 1;
$defaultdelayunit = !empty($adht->duration_unit) ? $adht->duration_unit : 'y';

$head = member_prepare_head($object);

$rowspan = 10;
if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
	$rowspan++;
}
if (isModEnabled('societe')) {
	$rowspan++;
}

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="rowid" value="'.$object->id.'">';

print dol_get_fiche_head($head, 'subscription', $langs->trans("Member"), -1, 'user');

$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/adherents/list.php', ['restore_lastsearch_values' => 1]).'">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<a href="'.DOL_URL_ROOT.'/adherents/vcard.php?id='.$object->id.'" class="refid" tabindex="-1">';
$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard', 'class="valignmiddle marginleftonly paddingrightonly"');
$morehtmlref .= '</a>';

dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

// Login
if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
	print '<tr><td class="titlefieldmiddle">'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.dol_escape_htmltag($object->login).'</td></tr>';
}

// Type
print '<tr><td class="titlefieldmiddle">'.$langs->trans("Type").'</td>';
print '<td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

// Morphy
print '<tr><td>'.$langs->trans("MemberNature").'</td>';
print '<td class="valeur" >'.$object->getmorphylib('', 1).'</td>';
print '</tr>';

// Company
print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.dol_escape_htmltag($object->company).'</td></tr>';

// Civility
print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$object->getCivilityLabel().'</td>';
print '</tr>';

// Password
if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
	print '<tr><td>'.$langs->trans("Password").'</td><td>';
	if ($object->pass) {
		print preg_replace('/./i', '*', $object->pass);
	} else {
		if ($user->admin) {
			print '<!-- '.$langs->trans("Crypted").': '.$object->pass_indatabase_crypted.' -->';
		}
		print '<span class="opacitymedium">'.$langs->trans("Hidden").'</span>';
	}
	if (!empty($object->pass_indatabase) && empty($object->user_id)) {	// Show warning only for old password still in clear (does not happen anymore)
		$langs->load("errors");
		$htmltext = $langs->trans("WarningPasswordSetWithNoAccount");
		print ' '.$form->textwithpicto('', $htmltext, 1, 'warning');
	}
	print '</td></tr>';
}

// Date end subscription
print '<tr><td>'.$langs->trans("SubscriptionEndDate").'</td><td class="valeur">';
if ($object->datefin) {
	print dol_print_date($object->datefin, 'day');
	if ($object->hasDelay()) {
		print " ".img_warning($langs->trans("Late"));
	}
} else {
	if ($object->need_subscription == 0) {
		print $langs->trans("SubscriptionNotNeeded");
	} elseif (!$adht->subscription) {
		print $langs->trans("SubscriptionNotRecorded");
		if (Adherent::STATUS_VALIDATED == $object->status) {
			print " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft, not excluded and not resiliated
		}
	} else {
		print $langs->trans("SubscriptionNotReceived");
		if (Adherent::STATUS_VALIDATED == $object->status) {
			print " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft, not excluded and not resiliated
		}
	}
}
print '</td></tr>';

print '</table>';

print '</div>';

print '<div class="fichehalfright">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border tableforfield centpercent">';

// Tags / Categories
if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
	print '<tr><td>'.$langs->trans("Categories").'</td>';
	print '<td colspan="2">';
	print $form->showCategories($object->id, Categorie::TYPE_MEMBER, 1);
	print '</td></tr>';
}

// Birth Date
print '<tr><td class="titlefieldmiddle">'.$langs->trans("DateOfBirth").'</td><td class="valeur">'.dol_print_date($object->birth, 'day').'</td></tr>';

// Default language
if (getDolGlobalInt('MAIN_MULTILANGS')) {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	print '<tr><td>'.$langs->trans("DefaultLang").'</td><td>';
	//$s=picto_from_langcode($object->default_lang);
	//print ($s?$s.' ':'');
	$langs->load("languages");
	$labellang = ($object->default_lang ? $langs->trans('Language_'.$object->default_lang) : '');
	print picto_from_langcode($object->default_lang, 'class="paddingrightonly saturatemedium opacitylow"');
	print $labellang;
	print '</td></tr>';
}

// Public
$linkofpubliclist = DOL_MAIN_URL_ROOT.'/public/members/public_list.php'.((isModEnabled('multicompany')) ? '?entity='.$conf->entity : '');
print '<tr><td>'.$form->textwithpicto($langs->trans("MembershipPublic"), $langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist), 1, 'help', '', 0, 3, 'publicfile').'</td><td class="valeur">'.yn($object->public).'</td></tr>';

// Other attributes
$cols = 2;
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

// Third party Dolibarr
if (isModEnabled('societe')) {
	print '<tr><td>';
	print '<table class="nobordernopadding centpercent"><tr><td>';
	print $langs->trans("LinkedToDolibarrThirdParty");
	print '</td>';
	if ($action != 'editthirdparty' && $user->hasRight('adherent', 'creer')) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editthirdparty&token='.newToken().'&rowid='.$object->id.'">'.img_edit($langs->trans('SetLinkToThirdParty'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td colspan="2" class="valeur">';
	if ($action == 'editthirdparty') {
		$htmlname = 'socid';
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="form'.$htmlname.'">';
		print '<input type="hidden" name="rowid" value="'.$object->id.'">';
		print '<input type="hidden" name="action" value="set'.$htmlname.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<table class="nobordernopadding">';
		print '<tr><td>';
		print $form->select_company($object->socid, 'socid', '', 1);
		print '</td>';
		print '<td class="left"><input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></td>';
		print '</tr></table></form>';
	} else {
		if ($object->socid > 0) {
			$company = new Societe($db);
			$result = $company->fetch($object->socid);
			print $company->getNomUrl(1);

			// Show link to invoices
			$tmparray = $company->getOutstandingBills('customer');
			if (!empty($tmparray['refs'])) {
				print ' - '.img_picto($langs->trans("Invoices"), 'bill', 'class="paddingright"').'<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->socid.'">'.$langs->trans("Invoices").' ('.count($tmparray['refs']).')';
				// TODO Add alert if warning on at least one invoice late
				print '</a>';
			}
		} else {
			print '<span class="opacitymedium">'.$langs->trans("NoThirdPartyAssociatedToMember").'</span>';
		}
	}
	print '</td></tr>';
}

// Login Dolibarr - Link to user
print '<tr><td>';
print '<table class="nobordernopadding centpercent"><tr><td>';
print $langs->trans("LinkedToDolibarrUser");
print '</td>';
if ($action != 'editlogin' && $user->hasRight('adherent', 'creer')) {
	print '<td class="right">';
	if ($user->hasRight("user", "user", "creer")) {
		print '<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editlogin&token='.newToken().'&rowid='.$object->id.'">'.img_edit($langs->trans('SetLinkToUser'), 1).'</a>';
	}
	print '</td>';
}
print '</tr></table>';
print '</td><td colspan="2" class="valeur">';
if ($action == 'editlogin') {
	$form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id, (string) $object->user_id, 'userid', array());
} else {
	if ($object->user_id) {
		$linkeduser = new User($db);
		$linkeduser->fetch($object->user_id);
		print $linkeduser->getNomUrl(-1);
	} else {
		print '<span class="opacitymedium">'.$langs->trans("NoDolibarrAccess").'</span>';
	}
}
print '</td></tr>';

print "</table>\n";

print "</div></div>\n";
print '<div class="clearboth"></div>';

print dol_get_fiche_end();


/*
 * Action bar
 */

// Button to create a new subscription if member no draft (-1) neither resiliated (0) neither excluded (-2)
if ($user->hasRight('adherent', 'cotisation', 'creer')) {
	if ($action != 'createsubscription' && $action != 'create_thirdparty') {
		print '<div class="tabsAction">';

		if ($object->status > 0) {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.dolBuildUrl($_SERVER["PHP_SELF"], ['rowid' => $rowid, 'action' => 'createsubscription'], true).'">'.$langs->trans("AddSubscription")."</a></div>";
		} else {
			print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("AddSubscription").'</a></div>';
		}

		print '</div>';
	}
}

/*
 * List of subscriptions
 */
if ($action != 'createsubscription' && $action != 'create_thirdparty') {
	$sql = "SELECT d.rowid, d.firstname, d.lastname, d.societe, d.fk_adherent_type as type,";
	$sql .= " c.rowid as crowid, c.subscription,";
	$sql .= " c.datec, c.fk_type as cfk_type,";
	$sql .= " c.dateadh as dateh, c.note as clabel,";
	$sql .= " c.datef,";
	$sql .= " c.fk_bank,";
	$sql .= " b.rowid as bid,";
	$sql .= " ba.rowid as baid, ba.label, ba.bank, ba.ref, ba.account_number, ba.fk_accountancy_journal, ba.number, ba.currency_code";
	$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."subscription as c";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON c.fk_bank = b.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
	$sql .= " WHERE d.rowid = c.fk_adherent AND d.rowid=".((int) $rowid);
	$sql .= $db->order($sortfield, $sortorder);

	$result = $db->query($sql);
	if ($result) {
		$subscriptionstatic = new Subscription($db);

		$num = $db->num_rows($result);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">'."\n";

		print '<tr class="liste_titre">';
		print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], 'c.rowid', '', $param, '', $sortfield, $sortorder);
		print_liste_field_titre('DateCreation', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre('Type', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre('DateStart', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre('DateEnd', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre('Amount', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre('Note', $_SERVER["PHP_SELF"], 'clabel', '', $param, '', $sortfield, $sortorder);
		if (isModEnabled('bank')) {
			print_liste_field_titre('Account', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
		}
		print "</tr>\n";

		$accountstatic = new Account($db);
		$adh = new Adherent($db);
		$adht = new AdherentType($db);

		$i = 0;
		while ($i < $num) {
			$objp = $db->fetch_object($result);

			$adh->id = $objp->rowid;
			$adh->typeid = $objp->type;

			$subscriptionstatic->ref = $objp->crowid;
			$subscriptionstatic->id = $objp->crowid;
			$subscriptionstatic->note_public = $objp->clabel;

			$typeid = $objp->cfk_type;
			if ($typeid > 0) {
				$adht->fetch($typeid);
			}

			print '<tr class="oddeven">';
			print '<td class="tdoverflowmax150">'.$subscriptionstatic->getNomUrl(1).'</td>';
			print '<td class="center nowraponall">'.dol_print_date($db->jdate($objp->datec), 'dayhour')."</td>\n";
			print '<td class="center tdoverflowmax125">';
			if ($typeid > 0) {
				print $adht->getNomUrl(1);
			}
			print '</td>';
			print '<td class="center">'.dol_print_date($db->jdate($objp->dateh), 'day')."</td>\n";
			print '<td class="center">'.dol_print_date($db->jdate($objp->datef), 'day')."</td>\n";
			print '<td class="right amount">'.price($objp->subscription).'</td>';
			print '<td class="left">'.$objp->clabel.'</td>';
			if (isModEnabled('bank')) {
				print '<td class="tdoverflowmax100 right">';
				if ($objp->bid) {
					$accountstatic->label = $objp->label;
					$accountstatic->id = $objp->baid;
					$accountstatic->number = $objp->number;
					$accountstatic->account_number = $objp->account_number;
					$accountstatic->currency_code = $objp->currency_code;

					if (isModEnabled('accounting') && $objp->fk_accountancy_journal > 0) {
						$accountingjournal = new AccountingJournal($db);
						$accountingjournal->fetch($objp->fk_accountancy_journal);

						$accountstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);
					}

					$accountstatic->ref = $objp->ref;
					print $accountstatic->getNomUrl(1);
				} else {
					print '&nbsp;';
				}
				print '</td>';
			}
			print "</tr>";
			$i++;
		}

		if (empty($num)) {
			$colspan = 7;
			if (isModEnabled('bank')) {
				$colspan++;
			}
			print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}

		print "</table>";
		print '</div>';
	} else {
		dol_print_error($db);
	}
}


if (($action != 'createsubscription' && $action != 'create_thirdparty')) {
	// Show online payment link
	// The list can be complete by the hook 'doValidatePayment' executed inside getValidOnlinePaymentMethods()
	include_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
	$validpaymentmethod = getValidOnlinePaymentMethods('');
	$useonlinepayment = count($validpaymentmethod);

	if ($useonlinepayment) {
		print '<br>';

		require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
		print showOnlinePaymentUrl('membersubscription', $object->ref);
		print '<br>';
	}
}

/*
 * Add new subscription form
 */
if (($action == 'createsubscription' || $action == 'create_thirdparty') && $user->hasRight('adherent', 'cotisation', 'creer')) {
	print '<br>';

	print load_fiche_titre($langs->trans("NewCotisation"));

	// Define default choice for complementary actions
	$bankdirect = 0; // 1 means option by default is write to bank direct with no invoice
	$invoiceonly = 0; // 1 means option by default is invoice only
	$bankviainvoice = 0; // 1 means option by default is write to bank via invoice
	if (GETPOST('paymentsave')) {
		if (GETPOST('paymentsave') == 'bankdirect') {
			$bankdirect = 1;
		}
		if (GETPOST('paymentsave') == 'invoiceonly') {
			$invoiceonly = 1;
		}
		if (GETPOST('paymentsave') == 'bankviainvoice') {
			$bankviainvoice = 1;
		}
	} else {
		if (getDolGlobalString('ADHERENT_BANK_USE') == 'bankviainvoice' && isModEnabled('bank') && isModEnabled('societe') && isModEnabled('invoice')) {
			$bankviainvoice = 1;
		} elseif (getDolGlobalString('ADHERENT_BANK_USE') == 'bankdirect' && isModEnabled('bank')) {
			$bankdirect = 1;
		} elseif (getDolGlobalString('ADHERENT_BANK_USE') == 'invoiceonly' && isModEnabled('bank') && isModEnabled('societe') && isModEnabled('invoice')) {
			$invoiceonly = 1;
		}
	}

	print "\n\n<!-- Form add subscription -->\n";

	if ($conf->use_javascript_ajax) {
		//var_dump($bankdirect.'-'.$bankviainvoice.'-'.$invoiceonly);
		print "\n".'<script type="text/javascript">';
		print '$(document).ready(function () {
					$(".bankswitchclass, .bankswitchclass2").'.(($bankdirect || $bankviainvoice) ? 'show()' : 'hide()').';
					$(".linkobjectswitchclass").'.(GETPOST('paymentsave', 'aZ09') == 'linktoobject' ? 'show()' : 'hide()').';
					$("#none, #invoiceonly").click(function() {
						$(".bankswitchclass").hide();
						$(".bankswitchclass2").hide();
						$(".linkobjectswitchclass").hide();
					});
					$("#bankdirect, #bankviainvoice").click(function() {
						$(".bankswitchclass").show();
						$(".bankswitchclass2").show();
						$(".linkobjectswitchclass").hide();
					});
					$("#linktoobject").click(function() {
						$(".bankswitchclass").hide();
						$(".bankswitchclass2").hide();
						$(".linkobjectswitchclass").show();
					});
					$("#selectoperation").change(function() {
						var code = $(this).val();
						if (code == "CHQ")
						{
							$(".fieldrequireddyn").addClass("fieldrequired");
							if ($("#fieldchqemetteur").val() == "")
							{
								$("#fieldchqemetteur").val($("#memberlabel").val());
							}
						}
						else
						{
							$(".fieldrequireddyn").removeClass("fieldrequired");
						}
					});
					';
		if (GETPOST('paymentsave')) {
			print '$("#'.GETPOST('paymentsave', 'aZ09').'").prop("checked", true);';
		}
		print '});';
		print '</script>'."\n";
	}


	// Confirm create third party
	if ($action == 'create_thirdparty') {
		$companyalias = '';
		$fullname = $object->getFullName($langs);

		if ($object->morphy == 'mor') {
			$companyname = $object->company;
			if (!empty($fullname)) {
				$companyalias = $fullname;
			}
		} else {
			$companyname = $fullname;
			if (!empty($object->company)) {
				$companyalias = $object->company;
			}
		}

		// Create a form array
		$formquestion = array(
			array('label' => $langs->trans("NameToCreate"), 'type' => 'text', 'name' => 'companyname', 'value' => $companyname, 'morecss' => 'minwidth300', 'moreattr' => 'maxlength="128"'),
			array('label' => $langs->trans("AliasNames"), 'type' => 'text', 'name' => 'companyalias', 'value' => $companyalias, 'morecss' => 'minwidth300', 'moreattr' => 'maxlength="128"')
		);
		// If customer code was forced to "required", we ask it at creation to avoid error later
		if (getDolGlobalString('MAIN_COMPANY_CODE_ALWAYS_REQUIRED')) {
			$tmpcompany = new Societe($db);
			$tmpcompany->name = (string) $companyname;
			$tmpcompany->get_codeclient($tmpcompany, 0);
			$customercode = $tmpcompany->code_client;
			$formquestion[] = array(
				'label' => $langs->trans("CustomerCode"),
				'type' => 'text',
				'name' => 'customercode',
				'value' => $customercode,
				'morecss' => 'minwidth300',
				'moreattr' => 'maxlength="128"',
			);
		}
		// @todo Add other extrafields mandatory for thirdparty creation

		print $form->formconfirm($_SERVER["PHP_SELF"]."?rowid=".$object->id, $langs->trans("CreateDolibarrThirdParty"), $langs->trans("ConfirmCreateThirdParty"), "confirm_create_thirdparty", $formquestion, 1);
	}


	print '<form name="subscription" method="POST" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="subscription">';
	print '<input type="hidden" name="rowid" value="'.$rowid.'">';
	print '<input type="hidden" name="memberlabel" id="memberlabel" value="'.dol_escape_htmltag($object->getFullName($langs)).'">';
	print '<input type="hidden" name="thirdpartylabel" id="thirdpartylabel" value="'.dol_escape_htmltag($object->company).'">';

	print dol_get_fiche_head(array());

	print '<div class="div-table-responsive">';
	print '<table class="border centpercent">'."\n";
	print '<tbody>';

	// Date payment
	if (GETPOST('paymentyear') && GETPOST('paymentmonth') && GETPOST('paymentday')) {
		$paymentdate = dol_mktime(0, 0, 0, GETPOSTINT('paymentmonth'), GETPOSTINT('paymentday'), GETPOSTINT('paymentyear'));
	}

	// Date start subscription
	print '<tr>';
	$currentyear = dol_print_date($now, "%Y");
	$currentmonth = dol_print_date($now, "%m");
	print '<td class="fieldrequired">'.$langs->trans("DateSubscription").'</td><td>';
	if (GETPOST('reday')) {
		$datefrom = dol_mktime(0, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
	}
	if (!$datefrom) {
		// Guess the subscription start date
		// By default, the subscription start date is the end date of previous membership ($object->datefin) + 1 day, or the date of
		// the validation of the member if no previous date exists.
		$datefrom = ($object->datefin ? dol_time_plus_duree($object->datefin, 1, 'd') : $object->datevalid);

		if (getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER')) {
			$datefrom = dol_time_plus_duree($now, (int) substr(getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER'), 0, -1), substr(getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER'), -1));
		} elseif ($object->datefin > 0 && dol_time_plus_duree($object->datefin, $defaultdelay, $defaultdelayunit) > $now) {
			$datefrom = dol_time_plus_duree($object->datefin, 1, 'd');
		}
		// Now do a correction of the suggested date
		if (getDolGlobalString('MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF') === "m") {
			$datefrom = dol_get_first_day((int) dol_print_date($datefrom, "%Y"), (int) dol_print_date($datefrom, "%m"));
		} elseif (getDolGlobalString('MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF') === "3m") {
			$datefrom = dol_time_plus_duree($object->datefin, -3, 'm');
			$datefrom = dol_get_first_day((int) dol_print_date($datefrom, "%Y"), (int) dol_print_date($datefrom, "%m"));
		} elseif (getDolGlobalString('MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF') === "Y") {
			$datefrom = dol_get_first_day((int) dol_print_date($datefrom, "%Y"));
		}
	}
	print $form->selectDate($datefrom, '', 0, 0, 0, "subscription", 1, 1);
	print "</td></tr>";

	// Date end subscription
	if (GETPOST('endday')) {
		$dateto = dol_mktime(0, 0, 0, GETPOSTINT('endmonth'), GETPOSTINT('endday'), GETPOSTINT('endyear'));
	}
	if (!$dateto) {
		if (getDolGlobalInt('MEMBER_SUBSCRIPTION_SUGGEST_END_OF_MONTH')) {
			$dateto = dol_get_last_day((int) dol_print_date($datefrom, "%Y"), (int) dol_print_date($datefrom, "%m"));
		} elseif (getDolGlobalInt('MEMBER_SUBSCRIPTION_SUGGEST_END_OF_YEAR')) {
			$dateto = dol_get_last_day((int) dol_print_date($datefrom, "%Y"));
		} else {
			$dateto = -1; // By default, no date is suggested
		}
	}
	print '<tr><td>'.$langs->trans("DateEndSubscription").'</td><td>';
	print $form->selectDate($dateto, 'end', 0, 0, 0, "subscription", 1, 0);
	print "</td></tr>";

	if ($adht->subscription) {
		// Amount
		print '<tr><td class="fieldrequired">'.$langs->trans("Amount").'</td>';
		print '<td><input autofocus class="width50" type="text" name="subscription" value="'.(GETPOSTISSET('subscription') ? GETPOST('subscription') : (is_null($adht->amount) ? '' : price($adht->amount, 0, '', 0))).'"> '.$langs->trans("Currency".getDolCurrency()) .'</td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Note").'</td>';
		print '<td><input name="label" type="text" size="32" value="';
		if (!getDolGlobalString('MEMBER_NO_DEFAULT_LABEL')) {
			print $langs->trans("Subscription").' '.dol_print_date(($datefrom ? $datefrom : dol_now()), "%Y");
		}
		print '"></td></tr>';

		// Complementary action
		if ((isModEnabled('bank') || isModEnabled('invoice')) && !getDolGlobalString('ADHERENT_SUBSCRIPTION_HIDECOMPLEMENTARYACTIONS')) {
			$company = new Societe($db);
			if ($object->socid) {
				$result = $company->fetch($object->socid);
			}

			// No more action
			print '<tr><td class="tdtop fieldrequired">'.$langs->trans('MoreActions');
			print '</td>';
			print '<td class="line-height-large">';

			print '<input type="radio" class="moreaction" id="none" name="paymentsave" value="none"'.(empty($bankdirect) && empty($invoiceonly) && empty($bankviainvoice) ? ' checked' : '').'>';
			print '<label for="none"> '.$langs->trans("None").'</label><br>';
			// Add entry into bank account
			if (isModEnabled('bank')) {
				print '<input type="radio" class="moreaction" id="bankdirect" name="paymentsave" value="bankdirect"'.(!empty($bankdirect) ? ' checked' : '');
				print '><label for="bankdirect">  '.$langs->trans("MoreActionBankDirect").'</label><br>';
			}
			// Add invoice with no payments
			if (isModEnabled('societe') && isModEnabled('invoice')) {
				print '<input type="radio" class="moreaction" id="invoiceonly" name="paymentsave" value="invoiceonly"'.(!empty($invoiceonly) ? ' checked' : '');
				//if (empty($object->fk_soc)) print ' disabled';
				print '><label for="invoiceonly"> '.$langs->trans("MoreActionInvoiceOnly");
				if ($object->socid) {
					print ' ('.$langs->trans("ThirdParty").': '.$company->getNomUrl(1).')';
				} else {
					print ' (';
					if (empty($object->socid)) {
						print img_warning($langs->trans("NoThirdPartyAssociatedToMember"));
					}
					print $langs->trans("NoThirdPartyAssociatedToMember");
					print ' - <a href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=create_thirdparty&token='.newToken().'">';
					print $langs->trans("CreateDolibarrThirdParty");
					print '</a>)';
				}
				if (!getDolGlobalString('ADHERENT_VAT_FOR_SUBSCRIPTIONS') || getDolGlobalString('ADHERENT_VAT_FOR_SUBSCRIPTIONS') != 'defaultforfoundationcountry') {
					print '. <span class="opacitymedium">'.$langs->trans("NoVatOnSubscription", 0).'</span>';
				}
				if (getDolGlobalString('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS') && (isModEnabled('product') || isModEnabled('service'))) {
					$prodtmp = new Product($db);
					$result = $prodtmp->fetch(getDolGlobalInt('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS'));
					if ($result < 0) {
						setEventMessage($prodtmp->error, 'errors');
					}
					print '. '.$langs->transnoentitiesnoconv("ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS", $prodtmp->getNomUrl(1)); // must use noentitiesnoconv to avoid to encode html into getNomUrl of product
				}
				print '</label><br>';
			}
			// Add invoice with payments
			if (isModEnabled('bank') && isModEnabled('societe') && isModEnabled('invoice')) {
				print '<input type="radio" class="moreaction" id="bankviainvoice" name="paymentsave" value="bankviainvoice"'.(!empty($bankviainvoice) ? ' checked' : '');
				//if (empty($object->socid)) print ' disabled';
				print '><label for="bankviainvoice">  '.$langs->trans("MoreActionBankViaInvoice");
				if ($object->socid) {
					print ' ('.$langs->trans("ThirdParty").': '.$company->getNomUrl(1).')';
				} else {
					print ' (';
					if (empty($object->socid)) {
						print img_warning($langs->trans("NoThirdPartyAssociatedToMember"));
					}
					print $langs->trans("NoThirdPartyAssociatedToMember");
					print ' - <a href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=create_thirdparty&token='.newToken().'">';
					print $langs->trans("CreateDolibarrThirdParty");
					print '</a>)';
				}
				if (!getDolGlobalString('ADHERENT_VAT_FOR_SUBSCRIPTIONS') || getDolGlobalString('ADHERENT_VAT_FOR_SUBSCRIPTIONS') != 'defaultforfoundationcountry') {
					print '. <span class="opacitymedium">'.$langs->trans("NoVatOnSubscription", 0).'</span>';
				}
				if (getDolGlobalString('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS') && (isModEnabled('product') || isModEnabled('service'))) {
					$prodtmp = new Product($db);
					$result = $prodtmp->fetch(getDolGlobalInt('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS'));
					if ($result < 0) {
						setEventMessage($prodtmp->error, 'errors');
					}
					print '. '.$langs->transnoentitiesnoconv("ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS", $prodtmp->getNomUrl(1)); // must use noentitiesnoconv to avoid to encode html into getNomUrl of product
				}
				print '</label><br>';
			}
			// ===== START link BLOCK: Link to existing objects =====
			if ((isModEnabled('invoice') || isModEnabled('order')) && isModEnabled('societe') && $object->socid > 0) {
				print '<input type="radio" class="moreaction" id="linktoobject" name="paymentsave" value="linktoobject">';
				print '<label for="linktoobject">  '.$langs->trans("MoreActionLinkToExistingObject").'</label><br>';

				// ===== COMMON SETUP FOR BOTH BLOCKS =====
				// Show project column only if module enabled and user can read projects
				$showproject = (isModEnabled('project') && $user->hasRight('projet', 'lire'));
				require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$projectstatic = new Project($db);

				print '<div class="linkobjectswitchclass" style="margin-left: 20px; margin-top: 5px; display:none;">';
				// Invoices of the thirdparty, the 5 newest only
				// ===== START BLOCK: Invoices =====
				if (isModEnabled('invoice') && $user->hasRight('facture', 'lire')) {
					$sql_invoices = "SELECT f.rowid, f.fk_projet";
					$sql_invoices .= " FROM ".MAIN_DB_PREFIX."facture as f";
					$sql_invoices .= " WHERE f.fk_soc = ".((int) $object->socid)." AND f.entity IN (".getEntity('invoice').")";
					$sql_invoices .= $db->order("f.datef", "DESC");
					// Keep only the 5 latest invoices, the list can be long
					$sql_invoices .= $db->plimit(5);

					print '<div class="div-table-responsive-no-min">';
					print '<table class="noborder allwidth">';
					print '<tr class="liste_titre">';
					// Block title is placed on the 2 first header cells to save space, so the Ref column has no more title
					print '<td colspan="2" class="left">'.$langs->trans("LastBills", 5).'</td>';
					print '<td>'.$langs->trans("RefCustomer").'</td>';
					if ($showproject) {
						print '<td>'.$langs->trans("Project").'</td>';
					}
					print '<td class="center">'.$langs->trans("Date").'</td>';
					print '<td class="right">'.$langs->trans("AmountHT").'</td>';
					print '<td class="right">'.$langs->trans("Status").'</td>';
					print '</tr>';

					$resql_invoices = $db->query($sql_invoices);
					if ($resql_invoices) {
						$num_invoices = $db->num_rows($resql_invoices);
						$invoicestatic = new Facture($db);

						$j = 0;
						while ($j < $num_invoices) {
							$objinv = $db->fetch_object($resql_invoices);
							$invoicestatic->fetch($objinv->rowid);

							print '<tr class="oddeven">';
							print '<td class="center" style="padding: 4px;"><input type="checkbox" name="invoices_to_link[]" value="'.$invoicestatic->id.'" style="width: 14px; height: 14px;"'.(is_array($invoices_to_link_post) && in_array($invoicestatic->id, $invoices_to_link_post) ? ' checked' : '').'></td>';
							print '<td class="linkedcol-name tdoverflowmax150">'.$invoicestatic->getNomUrl(1).'</td>';
							print '<td class="linkedcol-ref tdoverflowmax150 nopaddingtopimp nopaddingbottomimp" title="'.dol_escape_htmltag($invoicestatic->ref_client).'">';
							if ($invoicestatic->ref_client) {
								print '<span class="small">'.dol_escape_htmltag($invoicestatic->ref_client).'</span>';
							} else {
								print '&nbsp;';
							}
							print '</td>';
							if ($showproject) {
								$project_label = '&nbsp;';
								if (!empty($objinv->fk_projet) && $projectstatic->fetch($objinv->fk_projet) > 0) {
									$project_label = $projectstatic->getNomUrl(1);
								}
								print '<td class="linkedcol-project">'.$project_label.'</td>';
							}
							print '<td class="linkedcol-date center">'.dol_print_date($invoicestatic->date, 'day').'</td>';
							print '<td class="linkedcol-amount right">'.price($invoicestatic->total_ht).'</td>';
							print '<td class="linkedcol-statut right">'.$invoicestatic->getLibStatut(3).'</td>';
							print '</tr>';
							$j++;
						}
						$colspan = $showproject ? 7 : 6;
						if (!$num_invoices) {
							print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
						}
					} else {
						dol_print_error($db);
					}

					// Footer row - manual ref input
					print '<tr class="liste_titre">';
					print '<td></td>';
					print '<td><input type="text" name="invoicereftolink" placeholder="'.dol_escape_htmltag($langs->trans("Ref")).'" style="width: 95%;" value="'.dol_escape_htmltag($invoicereftolink_post).'"></td>';
					print '<td colspan="'.($showproject ? 5 : 4).'"></td>';
					print '</tr>';

					print '</table>';
					print '</div>';
				}
				// ===== END BLOCK: Invoices =====

				// Orders of the thirdparty, the 5 newest only
				// ===== START BLOCK: Orders =====
				if (isModEnabled('order') && $user->hasRight('commande', 'lire')) {
					$sql_orders = "SELECT c.rowid, c.fk_projet";
					$sql_orders .= " FROM ".MAIN_DB_PREFIX."commande as c";
					$sql_orders .= " WHERE c.fk_soc = ".((int) $object->socid)." AND c.entity IN (".getEntity('commande').")";
					$sql_orders .= $db->order("c.date_commande", "DESC");
					// Keep only the 5 latest orders, the list can be long
					$sql_orders .= $db->plimit(5);

					print '<div class="div-table-responsive-no-min">';
					print '<table class="noborder allwidth">';
					print '<tr class="liste_titre">';
					// Block title is placed on the 2 first header cells to save space, so the Ref column has no more title
					print '<td colspan="2" class="left">'.$langs->trans("LastOrders", 5).'</td>';
					print '<td>'.$langs->trans("RefCustomer").'</td>';
					if ($showproject) {
						print '<td>'.$langs->trans("Project").'</td>';
					}
					print '<td class="center">'.$langs->trans("Date").'</td>';
					print '<td class="right">'.$langs->trans("AmountHT").'</td>';
					print '<td class="right">'.$langs->trans("Status").'</td>';
					print '</tr>';

					$resql_orders = $db->query($sql_orders);
					if ($resql_orders) {
						$num_orders = $db->num_rows($resql_orders);
						$orderstatic = new Commande($db);

						$j = 0;
						while ($j < $num_orders) {
							$objord = $db->fetch_object($resql_orders);
							$orderstatic->fetch($objord->rowid);

							print '<tr class="oddeven">';
							print '<td class="center" style="padding: 4px;"><input type="checkbox" name="orders_to_link[]" value="'.$orderstatic->id.'" style="width: 14px; height: 14px;"'.(is_array($orders_to_link_post) && in_array($orderstatic->id, $orders_to_link_post) ? ' checked' : '').'></td>';
							print '<td class="linkedcol-name tdoverflowmax150">'.$orderstatic->getNomUrl(1).'</td>';
							print '<td class="linkedcol-ref tdoverflowmax150 nopaddingtopimp nopaddingbottomimp" title="'.dol_escape_htmltag($orderstatic->ref_client).'">';
							if ($orderstatic->ref_client) {
								print '<span class="small">'.dol_escape_htmltag($orderstatic->ref_client).'</span>';
							} else {
								print '&nbsp;';
							}
							print '</td>';
							if ($showproject) {
								$project_label = '&nbsp;';
								if (!empty($objord->fk_projet) && $projectstatic->fetch($objord->fk_projet) > 0) {
									$project_label = $projectstatic->getNomUrl(1);
								}
								print '<td class="linkedcol-project">'.$project_label.'</td>';
							}
							print '<td class="linkedcol-date center">'.dol_print_date($orderstatic->date, 'day').'</td>';
							print '<td class="linkedcol-amount right">'.price($orderstatic->total_ht).'</td>';
							print '<td class="linkedcol-statut right">'.$orderstatic->getLibStatut(3).'</td>';
							print '</tr>';
							$j++;
						}
						$colspan = $showproject ? 7 : 6;
						if (!$num_orders) {
							print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
						}
					} else {
						dol_print_error($db);
					}

					// Footer row - manual ref input
					print '<tr class="liste_titre">';
					print '<td></td>';
					print '<td><input type="text" name="orderreftolink" placeholder="'.dol_escape_htmltag($langs->trans("Ref")).'" style="width: 95%;" value="'.dol_escape_htmltag($orderreftolink_post).'"></td>';
					print '<td colspan="'.($showproject ? 5 : 4).'"></td>';
					print '</tr>';

					print '</table>';
					print '</div>';
				}
				// ===== END BLOCK: Orders =====
				print '</div>';
			}
			// ===== END link BLOCK =====
			print '</td></tr>';

			// Bank account
			print '<tr class="bankswitchclass"><td class="fieldrequired">'.$langs->trans("FinancialAccount").'</td><td>';
			print img_picto('', 'bank_account', 'class="pictofixedwidth"');
			$form->select_comptes(GETPOST('accountid'), 'accountid', 0, '', 2, '', 0, 'minwidth200');
			print "</td></tr>\n";

			// Payment mode
			print '<tr class="bankswitchclass"><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td>';
			print $form->select_types_paiements(GETPOST('operation'), 'operation', '', 2, 1, 0, 0, 1, 'minwidth200', 1);
			print "</td></tr>\n";

			// Date of payment
			print '<tr class="bankswitchclass"><td class="fieldrequired">'.$langs->trans("DatePayment").'</td><td>';
			print $form->selectDate(isset($paymentdate) ? $paymentdate : -1, 'payment', 0, 0, 1, 'subscription', 1, 1);
			print "</td></tr>\n";

			print '<tr class="bankswitchclass2"><td>'.$langs->trans('Numero');
			print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
			print '</td>';
			print '<td><input id="fieldnum_chq" name="num_chq" type="text" size="8" value="'.(!GETPOST('num_chq') ? '' : GETPOST('num_chq')).'"></td></tr>';

			print '<tr class="bankswitchclass2 fieldrequireddyn"><td>'.$langs->trans('CheckTransmitter');
			print ' <em>('.$langs->trans("ChequeMaker").')</em>';
			print '</td>';
			print '<td><input id="fieldchqemetteur" name="chqemetteur" size="32" type="text" value="'.(!GETPOST('chqemetteur') ? '' : GETPOST('chqemetteur')).'"></td></tr>';

			print '<tr class="bankswitchclass2"><td>'.$langs->trans('Bank');
			print ' <em>('.$langs->trans("ChequeBank").')</em>';
			print '</td>';
			print '<td><input id="chqbank" name="chqbank" size="32" type="text" value="'.(!GETPOST('chqbank') ? '' : GETPOST('chqbank')).'"></td></tr>';
		}
	}

	print '<tr><td></td><td></td></tr>';

	print '<tr><td>'.$langs->trans("SendAcknowledgementByMail").'</td>';
	print '<td>';
	if (!$object->email) {
		print $langs->trans("NoEMail");
	} else {
		$adht = new AdherentType($db);
		$adht->fetch($object->typeid);

		// Send subscription email
		$subject = '';
		$msg = '';

		// Send subscription email
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		// Set output language
		$outputlangs = new Translate('', $conf);
		$outputlangs->setDefaultLang(!empty($object->default_lang) ? $object->default_lang : (empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang));
		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "members"));
		// Get email content from template
		$arraydefaultmessage = null;
		$labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_SUBSCRIPTION');

		if (!empty($labeltouse)) {
			$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
		}

		if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
			$subject = (string) $arraydefaultmessage->topic;
			$msg     = (string) $arraydefaultmessage->content;
		}

		$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
		complete_substitutions_array($substitutionarray, $outputlangs, $object);
		$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
		$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnSubscription()), $substitutionarray, $outputlangs);

		$tmp = '<input name="sendmail" type="checkbox"'.(GETPOST('sendmail', 'alpha') ? ' checked' : (getDolGlobalString('ADHERENT_DEFAULT_SENDINFOBYMAIL') ? ' checked' : '')).'>';
		$helpcontent = '';
		$helpcontent .= '<b>'.$langs->trans("MailFrom").'</b>: '.getDolGlobalString('ADHERENT_MAIL_FROM', $conf->email_from).'<br>'."\n";
		$helpcontent .= '<b>'.$langs->trans("MailRecipient").'</b>: '.$object->email.'<br>'."\n";
		$helpcontent .= '<b>'.$langs->trans("MailTopic").'</b>:<br>'."\n";
		if ($subjecttosend) {
			$helpcontent .= $subjecttosend."\n";
		} else {
			$langs->load("errors");
			$helpcontent .= '<span class="error">'.$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Module310Name")).'</span>'."\n";
		}
		$helpcontent .= "<br>";
		$helpcontent .= '<b>'.$langs->trans("MailText").'</b>:<br>';
		if ($texttosend) {
			$helpcontent .= dol_htmlentitiesbr($texttosend)."\n";
		} else {
			$langs->load("errors");
			$helpcontent .= '<span class="error">'.$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Module310Name")).'</span>'."\n";
		}
		// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
		print $form->textwithpicto($tmp, $helpcontent, 1, 'help', '', 0, 2, 'helpemailtosend');
	}
	print '</td></tr>';
	print '</tbody>';
	print '</table>';
	print '</div>';

	print dol_get_fiche_end();

	print '<div class="center">';
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);
	if (empty($reshook)) {
		print '<input type="submit" class="button" name="add" value="'.$langs->trans("AddSubscription").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	}
	print '</div>';

	print '</form>';

	print "\n<!-- End form subscription -->\n\n";
}


// End of page
llxFooter();
$db->close();
