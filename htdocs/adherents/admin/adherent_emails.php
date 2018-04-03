<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      J. Fernando Lagrange <fernando@demo-tic.org>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *   	\file       htdocs/adherents/admin/adherent.php
 *		\ingroup    member
 *		\brief      Page to setup the module Foundation
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';

$langs->load("admin");
$langs->load("members");

if (! $user->admin) accessforbidden();


$oldtypetonewone=array('texte'=>'text','chaine'=>'string');	// old type to new ones

$action = GETPOST('action','alpha');


/*
 * Actions
 */

//
if ($action == 'updateall')
{
    $db->begin();
    $res1=$res2=$res3=$res4=$res5=$res6=0;
    $res1=dolibarr_set_const($db, 'XXXX', GETPOST('ADHERENT_LOGIN_NOT_REQUIRED', 'alpha'), 'chaine', 0, '', $conf->entity);
    if ($res1 < 0 || $res2 < 0 || $res3 < 0 || $res4 < 0 || $res5 < 0 || $res6 < 0)
    {
        setEventMessages('ErrorFailedToSaveDate', null, 'errors');
        $db->rollback();
    }
    else
    {
        setEventMessages('RecordModifiedSuccessfully', null, 'mesgs');
        $db->commit();
    }
}

// Action mise a jour ou ajout d'une constante
if ($action == 'update' || $action == 'add')
{
	$constlineid = GETPOST('rowid','int');
	$constname=GETPOST('constname','alpha');

	$constvalue=(GETPOSTISSET('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname, 'alpha') : GETPOST('constvalue'));
	$consttype=(GETPOSTISSET('consttype_'.$constname) ? GETPOST('consttype_'.$constname, 'alphanohtml') : GETPOST('consttype'));
	$constnote=(GETPOSTISSET('constnote_'.$constname) ? GETPOST('constnote_'.$constname, 'none') : GETPOST('constnote'));

	$typetouse = empty($oldtypetonewone[$consttype]) ? $consttype : $oldtypetonewone[$consttype];

	$res=dolibarr_set_const($db,$constname, $constvalue, $typetouse, 0, $constnote, $conf->entity);

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

// Action activation d'un sous module du module adherent
if ($action == 'set')
{
    $result=dolibarr_set_const($db, GETPOST('name','alpha'), GETPOST('value'), '', 0, '', $conf->entity);
    if ($result < 0)
    {
        print $db->error();
    }
}

// Action desactivation d'un sous module du module adherent
if ($action == 'unset')
{
    $result=dolibarr_del_const($db,GETPOST('name','alpha'),$conf->entity);
    if ($result < 0)
    {
        print $db->error();
    }
}



/*
 * View
 */

$form = new Form($db);

$help_url='EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';

llxHeader('',$langs->trans("MembersSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MembersSetup"),$linkback,'title_setup');


$head = member_admin_prepare_head();

dol_fiche_head($head, 'emails', $langs->trans("Members"), -1, 'user');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="updateall">';

/*
 * Editing global variables not related to a specific theme
 */
$constantes=array(
	'ADHERENT_MAIL_FROM'=>'string',
	'ADHERENT_AUTOREGISTER_NOTIF_MAIL_SUBJECT'=>'string',
	'ADHERENT_AUTOREGISTER_NOTIF_MAIL'=>'html',
	'ADHERENT_EMAIL_TEMPLATE_AUTOREGISTER'		=>'emailtemplate:member',		/* old was ADHERENT_AUTOREGISTER_MAIL */
	'ADHERENT_EMAIL_TEMPLATE_MEMBER_VALIDATION'	=>'emailtemplate:member',		/* old was ADHERENT_MAIL_VALID */
	'ADHERENT_EMAIL_TEMPLATE_SUBSCRIPTION'		=>'emailtemplate:member',		/* old was ADHERENT_MAIL_COTIS */
	'ADHERENT_EMAIL_TEMPLATE_REMIND_EXPIRATION' =>'emailtemplate:member',
	'ADHERENT_EMAIL_TEMPLATE_CANCELATION'		=>'emailtemplate:member',		/* old was ADHERENT_MAIL_RESIL */
);

$helptext='*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
$helptext.='__DOL_MAIN_URL_ROOT__, __ID__, __FIRSTNAME__, __LASTNAME__, __FULLNAME__, __LOGIN__, __PASSWORD__, ';
$helptext.='__COMPANY__, __ADDRESS__, __ZIP__, __TOWN__, __COUNTRY__, __EMAIL__, __BIRTH__, __PHOTO__, __TYPE__, ';
//$helptext.='__YEAR__, __MONTH__, __DAY__';	// Not supported

form_constantes($constantes, 0, $helptext);

dol_fiche_end();


llxFooter();

$db->close();
