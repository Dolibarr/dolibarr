<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/adherents/agenda.php
 *  \ingroup    member
 *  \brief      Page of members events
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/member.lib.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php");

$langs->load("companies");
$langs->load("members");

$mesg=isset($_GET["mesg"])?'<div class="ok">'.$_GET["mesg"].'</div>':'';

$id = GETPOST("id");

// Security check
if (! $user->rights->adherent->lire) accessforbidden();

$object = new Adherent($db);
$result=$object->fetch($id);
if ($result > 0)
{
    $adht = new AdherentType($db);
    $result=$adht->fetch($object->typeid);
}


/*
 *	Actions
 */

// None



/*
 *	View
 */

$contactstatic = new Contact($db);

$form = new Form($db);

/*
 * Fiche categorie de client et/ou fournisseur
 */
if ($id)
{
	require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
	require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");

	$langs->load("companies");

	llxHeader("",$langs->trans("Agenda"),'');

	if ($conf->notification->enabled) $langs->load("mails");
	$head = member_prepare_head($object);

	dol_fiche_head($head, 'agenda', $langs->trans("Member"),0,'user');

	print '<table class="border" width="100%">';

	// Reference
	print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object,'id');
	print '</td>';
	print '</tr>';

	// Login
	if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
	{
	    print '<tr><td>'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.$object->login.'&nbsp;</td></tr>';
	}

	// Morphy
	print '<tr><td>'.$langs->trans("Nature").'</td><td class="valeur" >'.$object->getmorphylib().'</td>';
	/*print '<td rowspan="'.$rowspan.'" align="center" valign="middle" width="25%">';
	 print $form->showphoto('memberphoto',$member);
	print '</td>';*/
	print '</tr>';

	// Type
	print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

	// Company
	print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.$object->societe.'</td></tr>';

	// Civility
	print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$object->getCivilityLabel().'&nbsp;</td>';
	print '</tr>';

	// Lastname
	print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur" colspan="3">'.$object->lastname.'&nbsp;</td>';
	print '</tr>';

	// Firstname
	print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur" colspan="3">'.$object->firstname.'&nbsp;</td></tr>';

	// Status
	print '<tr><td>'.$langs->trans("Status").'</td><td class="valeur">'.$object->getLibStatut(4).'</td></tr>';

	print '</table>';

	print '</div>';


	dol_htmloutput_mesg($mesg);


    /*
     * Barre d'action
     */

    print '<div class="tabsAction">';

    if ($conf->agenda->enabled)
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$socid.'">'.$langs->trans("AddAction").'</a>';
    }

    print '</div>';

    print '<br>';

    print load_fiche_titre($langs->trans("ActionsOnMember"),'','');

    // List of todo actions
    show_actions_todo($conf,$langs,$db,$object);

    // List of done actions
    show_actions_done($conf,$langs,$db,$object);
}



llxFooter();

$db->close();
?>
