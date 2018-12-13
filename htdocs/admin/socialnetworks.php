<?php
/* Copyright (C) 2018 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       htdocs/admin/socialnetworks.php
 *		\ingroup    socialnetworks
 *		\brief      Page to setup the module Social Networks
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/mailmanspip.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "errors"));

if (! $user->admin) accessforbidden();

$type=array('yesno','texte','chaine');

$action = GETPOST('action','aZ09');



/*
 * Actions
 */

// Action activation d'un sous module du module adherent
if ($action == 'set')
{
	$result=dolibarr_set_const($db, $_GET["name"], $_GET["value"], '', 0, '', $conf->entity);
	if ($result < 0)
	{
		dol_print_error($db);
	}
}

// Action desactivation d'un sous module du module adherent
if ($action == 'unset')
{
	$result=dolibarr_del_const($db, $_GET["name"], $conf->entity);
	if ($result < 0)
	{
		dol_print_error($db);
	}
}


/*
 * View
 */

$help_url='';

llxHeader('',$langs->trans("SocialNetworkSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SocialNetworkSetup"),$linkback,'title_setup');

//$head = socialnetworks_admin_prepare_head();
$h=0;
$head = array();
$head[$h][0] = DOL_URL_ROOT.'/admin/socialnetworks.php';
$head[$h][1] = $langs->trans("Setup");
$head[$h][2] = 'setup';
$h++;


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';

dol_fiche_head($head, 'setup', '', 0, 'user');

print '<br>';

$arrayofsocialnetworks=array('jabber'=>'Jabber', 'skype'=>'Skype', 'twitter'=>'Twitter', 'facebook'=>'Facebook');

foreach($arrayofsocialnetworks as $snkey => $snlabel)
{
	$consttocheck = 'SOCIALNETWORKS_'.strtoupper($snkey);
	if (! empty($conf->global->$consttocheck))
	{
		//$link=img_picto($langs->trans("Active"),'tick').' ';
		$link='<a href="'.$_SERVER["PHP_SELF"].'?action=unset&value=0&name='.$consttocheck.'">';
		//$link.=$langs->trans("Disable");
		$link.=img_picto($langs->trans("Activated"),'switch_on');
		$link.='</a>';
	}
	else
	{
		$link='<a href="'.$_SERVER["PHP_SELF"].'?action=set&value=1&name='.$consttocheck.'">';
		//$link.=img_$langs->trans("Activate")
		$link.=img_picto($langs->trans("Disabled"),'switch_off');
		$link.='</a>';
	}
	print $langs->trans('EnableFeatureFor', $snlabel).' '.$link.'<br><br>';
}


dol_fiche_end();

print '</form>';


// End of page
llxFooter();
$db->close();
