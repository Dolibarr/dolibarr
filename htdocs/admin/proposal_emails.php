<?php
/* Copyright (C) 2020      Thibault FOUCART             <support@ptibogxiv.net>
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
 *	\file       htdocs/admin/proposal_emails.php
 *	\ingroup    commande
 *	\brief      Setup page of module Proposal
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin","propal"));

if (! $user->admin) accessforbidden();


$oldtypetonewone=array('texte'=>'text','chaine'=>'string');	// old type to new ones

$action = GETPOST('action', 'alpha');

$error = 0;

// Editing global variables not related to a specific theme
$constantes=array(
    //'MEMBER_REMINDER_EMAIL'=>array('type'=>'yesno', 'label'=>$langs->trans('MEMBER_REMINDER_EMAIL', $langs->transnoentities("Module2300Name"))),
    'PROPOSAL_EMAIL_TEMPLATE_VALIDATED'	=>'emailtemplate:propal_send',
    'PROPOSAL_EMAIL_TEMPLATE_SIGNED'	=>'emailtemplate:propal_send',		
    'PROPOSAL_EMAIL_TEMPLATE_NOTSIGNED'		=>'emailtemplate:propal_send',
    'PROPOSAL_EMAIL_TEMPLATE_BILLED'		=>'emailtemplate:propal_send',				
    'PROPOSAL_MAIL_FROM'=>'string',
);



/*
 * Actions
 */

// Action to update or add a constant
if ($action == 'update' || $action == 'add')
{
	$constlineid = GETPOST('rowid', 'int');
	$constname=GETPOST('constname', 'alpha');

	$constvalue=(GETPOSTISSET('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname, 'alpha') : GETPOST('constvalue'));
	$consttype=(GETPOSTISSET('consttype_'.$constname) ? GETPOST('consttype_'.$constname, 'alphanohtml') : GETPOST('consttype'));
	$constnote=(GETPOSTISSET('constnote_'.$constname) ? GETPOST('constnote_'.$constname, 'none') : GETPOST('constnote'));

	$typetouse = empty($oldtypetonewone[$consttype]) ? $consttype : $oldtypetonewone[$consttype];

	$res=dolibarr_set_const($db, $constname, $constvalue, $typetouse, 0, $constnote, $conf->entity);

	if (! $res > 0) $error++;

	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

// Action to enable a submodule of the adherent module
if ($action == 'set')
{
    $result=dolibarr_set_const($db, GETPOST('name', 'alpha'), GETPOST('value'), '', 0, '', $conf->entity);
    if ($result < 0)
    {
        print $db->error();
    }
}

// Action to disable a submodule of the adherent module
if ($action == 'unset')
{
    $result=dolibarr_del_const($db, GETPOST('name', 'alpha'), $conf->entity);
    if ($result < 0)
    {
        print $db->error();
    }
}



/*
 * View
 */

$form = new Form($db);

llxHeader("", $langs->trans("PropalSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("PropalSetup"), $linkback, 'title_setup');


$head = propal_admin_prepare_head();

dol_fiche_head($head, 'emails', $langs->trans("Proposals"), -1, 'invoice');

// TODO Use global form
//print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
//print '<input type="hidden" name="token" value="'.newToken().'">';
//print '<input type="hidden" name="action" value="updateall">';

$helptext='*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
$helptext.='__DOL_MAIN_URL_ROOT__, __ID__, __FIRSTNAME__, __LASTNAME__, __FULLNAME__, __LOGIN__, __PASSWORD__, ';
$helptext.='__COMPANY__, __ADDRESS__, __ZIP__, __TOWN__, __COUNTRY__, __EMAIL__, __BIRTH__, __PHOTO__, __TYPE__, ';
//$helptext.='__YEAR__, __MONTH__, __DAY__';	// Not supported

form_constantes($constantes, 0, $helptext);

//print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Update").'" name="update"></div>';
//print '</form>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();
