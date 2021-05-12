<?php
/* Copyright (C) 2008-2011	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013 		Florian Henry  		<florian.henry@open-concept.pro>
 * Copyright (C) 2015 		Juanjo Menent		<jmenent@2byte.es>
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
 *	    \file       htdocs/categories/admin/categorie.php
 *      \ingroup    categories
 *      \brief      Categorie admin pages
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';


if (!$user->admin)
accessforbidden();

// Load translation files required by the page
<<<<<<< HEAD
$langs->load("categories");

$action=GETPOST('action','aZ09');
=======
$langs->loadLangs(array("categories","admin"));

$action=GETPOST('action', 'aZ09');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

/*
 *	Actions
 */

<<<<<<< HEAD
if (preg_match('/set_([a-z0-9_\-]+)/i',$action,$reg))
=======
if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
    $code=$reg[1];
    if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        setEventMessages($db->lasterror(), null, 'errors');
    }
}

<<<<<<< HEAD
if (preg_match('/del_([a-z0-9_\-]+)/i',$action,$reg))
=======
if (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
    $code=$reg[1];
    if (dolibarr_del_const($db, $code, $conf->entity) > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
         setEventMessages($db->lasterror(), null, 'errors');
    }
}



/*
 * View
 */

$help_url='EN:Module Categories|FR:Module Catégories|ES:Módulo Categorías';
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

<<<<<<< HEAD
llxHeader('',$langs->trans("Categories"),$help_url);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CategoriesSetup"),$linkback,'title_setup');
=======
llxHeader('', $langs->trans("Categories"), $help_url);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CategoriesSetup"), $linkback, 'title_setup');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


$head=categoriesadmin_prepare_head();

dol_fiche_head($head, 'setup', $langs->trans("Categories"), -1, 'category');


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

$form = new Form($db);

// Mail required for members

print '<tr class="oddeven">';
print '<td>'.$langs->trans("CategorieRecursiv").'</td>';
<<<<<<< HEAD
print '<td align="center" width="20">'. $form->textwithpicto('',$langs->trans("CategorieRecursivHelp"),1,'help').'</td>';
=======
print '<td align="center" width="20">'. $form->textwithpicto('', $langs->trans("CategorieRecursivHelp"), 1, 'help').'</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('CATEGORIE_RECURSIV_ADD');
}
else
{
	if (empty($conf->global->CATEGORIE_RECURSIV_ADD))
	{
<<<<<<< HEAD
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_CATEGORIE_RECURSIV_ADD">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_CATEGORIE_RECURSIV_ADD">'.img_picto($langs->trans("Enabled"),'on').'</a>';
=======
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_CATEGORIE_RECURSIV_ADD">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_CATEGORIE_RECURSIV_ADD">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
}
print '</td></tr>';

print '</table>';

<<<<<<< HEAD
=======
// End of page
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
llxFooter();
$db->close();
