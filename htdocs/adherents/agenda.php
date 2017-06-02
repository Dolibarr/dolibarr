<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/adherents/agenda.php
 *  \ingroup    member
 *  \brief      Page of members events
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

$langs->load("companies");
$langs->load("members");

$id = GETPOST('id','int')?GETPOST('id','int'):GETPOST('rowid','int');

// Security check
$result=restrictedArea($user,'adherent',$id);

$object = new Adherent($db);
$result=$object->fetch($id);
if ($result > 0)
{
	$object->fetch_thirdparty();

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
if ($object->id > 0)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$langs->load("companies");

	$title=$langs->trans("Member") . " - " . $langs->trans("Agenda");
	$helpurl="EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros";
	llxHeader("",$title,$helpurl);

	if (! empty($conf->notification->enabled)) $langs->load("mails");
	$head = member_prepare_head($object);

	dol_fiche_head($head, 'agenda', $langs->trans("Member"), -1, 'user');

	$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php">'.$langs->trans("BackToList").'</a>';
	
	dol_banner_tab($object, 'rowid', $linkback);
    
	print '<div class="fichecenter">';
	
	print '<div class="underbanner clearboth"></div>';
	
	$object->info($id);
	print dol_print_object_info($object, 1);
	
	print '</div>';	

	dol_fiche_end();
	

    /*
     * Barre d'action
     */

    print '<div class="tabsAction">';

    if (! empty($conf->agenda->enabled))
    {
        print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&backtopage=1&origin=member&originid='.$id.'">'.$langs->trans("AddAction").'</a></div>';
    }

    print '</div>';

    $out='';

    print load_fiche_titre($langs->trans("ActionsOnMember"),$out,'');

    // List of actions
    show_actions_done($conf,$langs,$db,$object,null,0,'','');
}



llxFooter();

$db->close();
