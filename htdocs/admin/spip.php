<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2013 Juanjo Menent		<jmenent@2byte.es>
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
 *   	\file       htdocs/admin/spip.php
 *		\ingroup    mailmanspip
 *		\brief      Page to setup the module MailmanSpip (SPIP)
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/mailmanspip.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "members", "mailmanspip"));

if (!$user->admin) accessforbidden();


$type = array('yesno', 'texte', 'chaine');

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

// Action mise a jour ou ajout d'une constante
if ($action == 'update' || $action == 'add')
{
	$constnamearray = GETPOST("constname", 'array');
	$constvaluearray = GETPOST("constvalue", 'array');
	$consttypearray = GETPOST("consttype", 'array');
	$constnotearray = GETPOST("constnote", 'array');

	// Action mise a jour ou ajout d'une constante
	if ($action == 'update' || $action == 'add')
	{
		foreach ($constnamearray as $key => $val)
		{
			$constname = dol_escape_htmltag($constnamearray[$key]);
			$constvalue = dol_escape_htmltag($constvaluearray[$key]);
			$consttype = dol_escape_htmltag($consttypearray[$key]);
			$constnote = dol_escape_htmltag($constnotearray[$key]);

			$res = dolibarr_set_const($db, $constname, $constvalue, $type[$consttype], 0, $constnote, $conf->entity);

			if (!($res > 0)) $error++;
		}

	 	if (!$error)
		{
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}
}

// Action activation d'un sous module du module adherent
if ($action == 'set')
{
	$result = dolibarr_set_const($db, $_GET["name"], $_GET["value"], '', 0, '', $conf->entity);
	if ($result < 0)
	{
		dol_print_error($db);
	}
}

// Action desactivation d'un sous module du module adherent
if ($action == 'unset')
{
	$result = dolibarr_del_const($db, $_GET["name"], $conf->entity);
	if ($result < 0)
	{
		dol_print_error($db);
	}
}



/*
 * View
 */

$help_url = '';

llxHeader('', $langs->trans("MailmanSpipSetup"), $help_url);


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MailmanSpipSetup"), $linkback, 'title_setup');


$head = mailmanspip_admin_prepare_head();


/*
 * Spip
 */
if (!empty($conf->global->ADHERENT_USE_SPIP))
{
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print dol_get_fiche_head($head, 'spip', $langs->trans("Setup"), -1, 'user');

	//$link=img_picto($langs->trans("Active"),'tick').' ';
	$link = '<a href="'.$_SERVER["PHP_SELF"].'?action=unset&token='.newToken().'&value=0&name=ADHERENT_USE_SPIP">';
	//$link.=$langs->trans("Disable");
	$link .= img_picto($langs->trans("Activated"), 'switch_on');
	$link .= '</a>';
	// Edition des varibales globales
	$constantes = array(
		'ADHERENT_SPIP_SERVEUR',
		'ADHERENT_SPIP_DB',
		'ADHERENT_SPIP_USER',
		'ADHERENT_SPIP_PASS'
	);

	print load_fiche_titre($langs->trans('SPIPTitle'), $link, '');
	print '<br>';

	form_constantes($constantes, 2);

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Update").'" name="update"></div>';

	print '</form>';
} else {
	print dol_get_fiche_head($head, 'spip', $langs->trans("Setup"), 0, 'user');

	$link = '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=set&amp;token='.newToken().'&amp;value=1&amp;name=ADHERENT_USE_SPIP">';
	//$link.=$langs->trans("Activate");
	$link .= img_picto($langs->trans("Disabled"), 'switch_off');
	$link .= '</a>';
	print load_fiche_titre($langs->trans('SPIPTitle'), $link, '');

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
