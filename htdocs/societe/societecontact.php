<?php
/* Copyright (C) 2005     	Patrick Rouillon    <patrick@rouillon.net>
 * Copyright (C) 2005-2011	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2015	Philippe Grand      <philippe.grand@atoo-net.com>
 * Copyright (C) 2014       Charles-Fr Benke	<charles.fr@benke.fr>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2020       Maxime DEMAREST     <maxime@indelog.fr>
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
 *     \file       htdocs/societe/societecontact.php
 *     \ingroup    societe
 *     \brief      Onglet de gestion des contacts additionnel d'une société
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$langs->loadLangs(array("orders", "companies"));

$id = GETPOST('id', 'int') ?GETPOST('id', 'int') : GETPOST('socid', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (!$sortorder) $sortorder = "ASC";
if (!$sortfield) $sortfield = "s.nom";
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'societe', $id, '');

$object = new Societe($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('contactthirdparty', 'globalcard'));


/*
 * Actions
 */

if ($action == 'addcontact' && $user->rights->societe->contact->creer)
{
	$result = $object->fetch($id);
    if ($result > 0)
    {
        $error = 0;
        if (GETPOSTISSET('contactid'))
        {
            $contactid = GETPOST('contactid', 'int');
            // Check if the contact not belongs third-party (else contact can be added twice first in societe_contacts, second in element_contact)
            $arr_soc_contact = $object->contact_array();
            if (!empty($arr_soc_contact[$contactid]))
            {
                setEventMessage($langs->trans('ErrorThisContactBelongsToThisThirdParty'), 'errors');
                $error++;
            }
        }
        else
        {
            $contactid = GETPOST('userid', 'int');
        }

        if (empty($error))
        {
            $result = $object->add_contact($contactid, GETPOST('type', 'int'), GETPOST('source', 'alpha'));
        }

        if ($result > 1)
        {
        }
        elseif ($result == 0)
        {
            setEventMessage($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), 'errors');
        }
        elseif ($result < 1)
        {
            var_dump($object->error);
            if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
            {
                $langs->load("errors");
                setEventMessage($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), 'errors');
            } else {
                setEventMessage($object->error, 'errors');
            }
        }
    }
}

// Efface un contact
elseif ($action == 'deletecontact' && $user->rights->societe->contact->creer)
{
	$object->fetch($id);
	$result = $object->delete_contact($_GET["lineid"]);

	if ($result < 1)
		dol_print_error($db);
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


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || !empty($ref))
{
	if ($object->fetch($id, $ref) > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($object->socid);

        $backtopage = DOL_URL_ROOT.'/societe/societecontact.php?socid='.$object->id;

		$head = societe_prepare_head($object);
		dol_fiche_head($head, 'contact', $langs->trans("ThirdParty"), -1, 'company');

        $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

        dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

    	print '<div class="fichecenter">';

        print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent">';

		if (!empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
		{
		    print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
		}

		if ($object->client)
		{
		    print '<tr><td class="titlefield">';
		    print $langs->trans('CustomerCode').'</td><td colspan="3">';
		    print $object->code_client;
		    if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		    print '</td></tr>';
		}

		if ($object->fournisseur)
		{
		    print '<tr><td class="titlefield">';
		    print $langs->trans('SupplierCode').'</td><td colspan="3">';
		    print $object->code_fournisseur;
		    if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		    print '</td></tr>';
		}
		print '</table>';

		print '</div>';

        if ($user->rights->societe->contact->creer)
        {
            $buttitle = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("AddContact") : $langs->trans("AddContactAddress"));
            $buttitle.= '</br>'.$langs->trans('ToThisThirdParty');
            $newcardbutton .= dolGetButtonTitle($buttitle, '', 'fa fa-plus-circle', DOL_URL_ROOT.'/contact/card.php?socid='.$object->id.'&amp;action=create&amp;backtopage='.urlencode($backtopage));
        }
        print load_fiche_titre($langs->trans('AddSharedContactToThirdParty'), $newcardbutton, 'address');

		// Contacts lines (modules that overwrite templates must declare this into descriptor)
		$dirtpls = array_merge($conf->modules_parts['tpl'], array('/core/tpl'));
		foreach ($dirtpls as $reldir)
		{
			$res = @include dol_buildpath($reldir.'/contacts.tpl.php');
			if ($res) break;
		}

		// additionnal list with adherents of company
        // TODO Merge this in contats.tpl.php and CommonObject::liste_contact()
		if (!empty($conf->adherent->enabled) && $user->rights->adherent->lire)
		{
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

			$membertypestatic = new AdherentType($db);
			$memberstatic = new Adherent($db);

			$langs->load("members");
			$sql = "SELECT d.rowid, d.login, d.lastname, d.firstname, d.societe as company, d.fk_soc,";
			$sql .= " d.datefin,";
			$sql .= " d.email, d.fk_adherent_type as type_id, d.morphy, d.statut,";
			$sql .= " t.libelle as type, t.subscription";
			$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d";
			$sql .= ", ".MAIN_DB_PREFIX."adherent_type as t";
			$sql .= " WHERE d.fk_soc = ".$id;
			$sql .= " AND d.fk_adherent_type = t.rowid";

			dol_syslog("get list sql=".$sql);
			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);

				if ($num > 0)
				{
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
					while ($i < $num && $i < $conf->liste_limit)
					{
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
						print ((!empty($objp->lastname) || !empty($objp->firstname)) ? dol_trunc($memberstatic->getFullName($langs)) : '');
						print (((!empty($objp->lastname) || !empty($objp->firstname)) && !empty($companyname)) ? ' / ' : '');
						print (!empty($companyname) ? dol_trunc($companyname, 32) : '');
						print "</a></td>\n";

						// Login
						print "<td>".$objp->login."</td>\n";

						// Type
						$membertypestatic->id = $objp->type_id;
						$membertypestatic->libelle = $objp->type;
						$membertypestatic->label = $objp->type;

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
						if ($datefin)
						{
							print '<td class="center nowrap">';
							print dol_print_date($datefin, 'day');
							if ($memberstatic->hasDelay()) {
								print " ".img_warning($langs->trans("SubscriptionLate"));
							}
							print '</td>';
						} else {
							print '<td class="left nowrap">';
							if ($objp->subscription == 'yes')
							{
								print $langs->trans("SubscriptionNotReceived");
								if ($objp->statut > 0) print " ".img_warning();
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
