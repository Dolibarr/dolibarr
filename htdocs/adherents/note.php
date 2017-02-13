<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Frederic France         <frederic.france@free.fr>
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
 *      \file       htdocs/adherents/note.php
 *      \ingroup    member
 *      \brief      Tab for note of a member
*/

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

$langs->load("companies");
$langs->load("members");
$langs->load("bills");

$action=GETPOST('action','alpha');
$id=GETPOST('id','int');

// Security check
$result=restrictedArea($user,'adherent',$id);

$object = new Adherent($db);
$result=$object->fetch($id);
if ($result > 0)
{
    $adht = new AdherentType($db);
    $result=$adht->fetch($object->typeid);
}

$permissionnote=$user->rights->adherent->creer;  // Used by the include of actions_setnotes.inc.php

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once



/*
 * View
 */
$title=$langs->trans("Member") . " - " . $langs->trans("Note");
$helpurl="EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros";
llxHeader("",$title,$helpurl);

$form = new Form($db);

if ($id)
{
	$head = member_prepare_head($object);

	dol_fiche_head($head, 'note', $langs->trans("Member"), 0, 'user');

	print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php">'.$langs->trans("BackToList").'</a>';
	
	dol_banner_tab($object, 'rowid', $linkback);
    
    print '<div class="fichecenter">';
    
    print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

    // Login
    if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
    {
        print '<tr><td class="titlefield">'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.$object->login.'&nbsp;</td></tr>';
    }

    // Type
    print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

    // Morphy
    print '<tr><td class="titlefield">'.$langs->trans("Nature").'</td><td class="valeur" >'.$object->getmorphylib().'</td>';
    /*print '<td rowspan="'.$rowspan.'" align="center" valign="middle" width="25%">';
    print $form->showphoto('memberphoto',$member);
    print '</td>';*/
    print '</tr>';

    // Company
    print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.$object->societe.'</td></tr>';

    // Civility
    print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$object->getCivilityLabel().'&nbsp;</td>';
    print '</tr>';

    print "</table>";

    print '</div>';
    print '<br>';


    $cssclass='titlefield';
    $permission = $user->rights->adherent->creer;  // Used by the include of notes.tpl.php
    include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';


    dol_fiche_end();

}


llxFooter();
$db->close();
