<?php
/* Copyright (C) 2005     	Patrick Rouillon    <patrick@rouillon.net>
 * Copyright (C) 2005-2011	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2015	Philippe Grand      <philippe.grand@atoo-net.com>
 * Copyright (C) 2014       Charles-Fr Benke	<charles.fr@benke.fr>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *     \file       	htdocs/societe/societecontact.php
 *     \ingroup    	societe
 *     \brief      	Tab to manage differently contact.
 *     				Used when the unstable option MAIN_SUPPORT_SHARED_CONTACT_BETWEEN_THIRDPARTIES is on.
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('companies', 'orders'));

// Get parameters
$id = GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('socid');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "s.nom";
}
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Security check
if ($user->socid) {
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('contactthirdparty', 'globalcard'));

$result = restrictedArea($user, 'societe', $id, '');


// Initialize objects
$object = new Societe($db);

/*
 * Actions
 */

if ($action == 'addcontact' && $user->hasRight('societe', 'creer')) {
	$result = $object->fetch($id);

	if ($result > 0 && $id > 0) {
		$contactid = (GETPOSTINT('userid') ? GETPOSTINT('userid') : GETPOSTINT('contactid'));
		$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
		$result = $object->add_contact($contactid, $typeid, GETPOST("source", 'aZ09'));
	}

	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	} else {
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
			$langs->load("errors");
			$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
		} else {
			$mesg = '<div class="error">'.$object->error.'</div>';
		}
	}
} elseif ($action == 'swapstatut' && $user->hasRight('societe', 'creer')) {
	// bascule du statut d'un contact
	if ($object->fetch($id)) {
		$result = $object->swapContactStatus(GETPOSTINT('ligne'));
	} else {
		dol_print_error($db);
	}
} elseif ($action == 'deletecontact' && $user->hasRight('societe', 'creer')) {
	// Efface un contact
	$object->fetch($id);
	$result = $object->delete_contact(GETPOSTINT("lineid"));

	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	} else {
		dol_print_error($db);
	}
}


/*
 * View
 */

$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $langs->trans("ThirdParty"), $help_url);


$form = new Form($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);


// View and edit

if ($id > 0 || !empty($ref)) {
	if ($object->fetch($id, $ref) > 0) {
		$head = societe_prepare_head($object);
		print dol_get_fiche_head($head, 'contactext', $langs->trans("ThirdParty"), -1, 'company');

		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

		print '<div class="fichecenter">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent">';

		// Prospect/Customer
		/*print '<tr><td class="titlefield">'.$langs->trans('ProspectCustomer').'</td><td>';
		print $object->getLibCustProspStatut();
		print '</td></tr>';

		// Supplier
		print '<tr><td>'.$langs->trans('Supplier').'</td><td>';
		print yn($object->fournisseur);
		print '</td></tr>';*/

		if (getDolGlobalString('SOCIETE_USEPREFIX')) {  // Old not used prefix field
			print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
		}

		if ($object->client) {
			print '<tr><td class="titlefield">';
			print $langs->trans('CustomerCode').'</td><td colspan="3">';
			print $object->code_client;
			$tmpcheck = $object->check_codeclient();
			if ($tmpcheck != 0 && $tmpcheck != -5) {
				print ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
			}
			print '</td></tr>';
		}

		if ($object->fournisseur) {
			print '<tr><td class="titlefield">';
			print $langs->trans('SupplierCode').'</td><td colspan="3">';
			print $object->code_fournisseur;
			$tmpcheck = $object->check_codefournisseur();
			if ($tmpcheck != 0 && $tmpcheck != -5) {
				print ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
			}
			print '</td></tr>';
		}
		print '</table>';

		print '</div>';

		print '</form>';
		print '<br>';

		// Contacts lines (modules that overwrite templates must declare this into descriptor)
		$dirtpls = array_merge($conf->modules_parts['tpl'], array('/core/tpl'));
		foreach ($dirtpls as $reldir) {
			$res = @include dol_buildpath($reldir.'/contacts.tpl.php');
			if ($res) {
				break;
			}
		}

		// additional list with adherents of company
		if (isModEnabled('member') && $user->hasRight('adherent', 'lire')) {
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

			$membertypestatic = new AdherentType($db);
			$memberstatic = new Adherent($db);

			$langs->load("members");
			$sql = "SELECT d.rowid, d.login, d.lastname, d.firstname, d.societe as company, d.fk_soc,";
			$sql .= " d.datefin,";
			$sql .= " d.email, d.fk_adherent_type as type_id, d.morphy, d.statut,";
			$sql .= " t.libelle as type_label, t.subscription";
			$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d";
			$sql .= ", ".MAIN_DB_PREFIX."adherent_type as t";
			$sql .= " WHERE d.fk_soc = ".((int) $id);
			$sql .= " AND d.fk_adherent_type = t.rowid";

			dol_syslog("get list sql=".$sql);
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);

				if ($num > 0) {
					$param = '';

					$titre = $langs->trans("MembersListOfTiers");
					print '<br>';

					print_barre_liste($titre, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, 0, '');

					print "<table class=\"noborder\" width=\"100%\">";
					print '<tr class="liste_titre">';
					print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "d.rowid", $param, "", "", $sortfield, $sortorder);
					print_liste_field_titre("NameSlashCompany", $_SERVER["PHP_SELF"], "d.lastname", $param, "", "", $sortfield, $sortorder);
					print_liste_field_titre("Login", $_SERVER["PHP_SELF"], "d.login", $param, "", "", $sortfield, $sortorder);
					print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "t.libelle", $param, "", "", $sortfield, $sortorder);
					print_liste_field_titre("Person", $_SERVER["PHP_SELF"], "d.morphy", $param, "", "", $sortfield, $sortorder);
					print_liste_field_titre("EMail", $_SERVER["PHP_SELF"], "d.email", $param, "", "", $sortfield, $sortorder);
					print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "d.statut,d.datefin", $param, "", "", $sortfield, $sortorder);
					print_liste_field_titre("EndSubscription", $_SERVER["PHP_SELF"], "d.datefin", $param, "", '', $sortfield, $sortorder, 'center ');
					print "</tr>\n";

					$i = 0;
					while ($i < $num && $i < $conf->liste_limit) {
						$objp = $db->fetch_object($resql);

						$datefin = $db->jdate($objp->datefin);
						$memberstatic->id = $objp->rowid;
						$memberstatic->ref = $objp->rowid;
						$memberstatic->lastname = $objp->lastname;
						$memberstatic->firstname = $objp->firstname;
						$memberstatic->statut = $objp->statut;
						$memberstatic->datefin = $db->jdate($objp->datefin);

						$companyname = $objp->company;

						print '<tr class="oddeven">';

						// Ref
						print "<td>";
						print $memberstatic->getNomUrl(1);
						print "</td>\n";

						// Lastname
						print "<td><a href=\"card.php?rowid=$objp->rowid\">";
						print((!empty($objp->lastname) || !empty($objp->firstname)) ? dol_trunc($memberstatic->getFullName($langs)) : '');
						print(((!empty($objp->lastname) || !empty($objp->firstname)) && !empty($companyname)) ? ' / ' : '');
						print(!empty($companyname) ? dol_trunc($companyname, 32) : '');
						print "</a></td>\n";

						// Login
						print "<td>".$objp->login."</td>\n";

						// Type
						$membertypestatic->id = $objp->type_id;
						$membertypestatic->libelle = $objp->type_label;	// deprecated
						$membertypestatic->label = $objp->type_label;

						print '<td class="nowrap">';
						print $membertypestatic->getNomUrl(1, 32);
						print '</td>';

						// Moral/Physique
						print "<td>".$memberstatic->getmorphylib($objp->morphy)."</td>\n";

						// EMail
						print "<td>".dol_print_email($objp->email, 0, 0, 1)."</td>\n";

						// Statut
						print '<td class="nowrap">';
						print $memberstatic->LibStatut($objp->statut, $objp->subscription, $datefin, 2);
						print "</td>";

						// End of subscription date
						if ($datefin) {
							print '<td class="center nowrap">';
							print dol_print_date($datefin, 'day');
							if ($memberstatic->hasDelay()) {
								print " ".img_warning($langs->trans("SubscriptionLate"));
							}
							print '</td>';
						} else {
							print '<td class="left nowrap">';
							if (!empty($objp->subscription)) {
								print $langs->trans("SubscriptionNotReceived");
								if ($objp->statut > 0) {
									print " ".img_warning();
								}
							} else {
								print '&nbsp;';
							}
							print '</td>';
						}

						print "</tr>\n";
						$i++;
					}
					print "</table>\n";
				}
			}
		}
	} else {
		// Contrat non trouve
		print "ErrorRecordNotFound";
	}
}

// End of page
llxFooter();
$db->close();
