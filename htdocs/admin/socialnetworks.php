<?php
/* Copyright (C) 2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019 Alexandre Spangaro   <aspangaro@open-dsi.fr>
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

if (!$user->admin) accessforbidden();

$type = array('yesno', 'texte', 'chaine');

$action = GETPOST('action', 'aZ09');



/*
 * Action
 */
if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg))
{
    $code = $reg[1];
    if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

if (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg))
{
    $code = $reg[1];
    if (dolibarr_del_const($db, $code, $conf->entity) > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}


/*
 * View
 */

$help_url = '';

llxHeader('', $langs->trans("SocialNetworkSetup"), $help_url);


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SocialNetworkSetup"), $linkback, 'title_setup');

//$head = socialnetworks_admin_prepare_head();
$h = 0;
$head = array();
$head[$h][0] = DOL_URL_ROOT.'/admin/socialnetworks.php';
$head[$h][1] = $langs->trans("Setup");
$head[$h][2] = 'setup';
$h++;


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';

dol_fiche_head($head, 'setup', '', 0, 'user');

print '<br>';

$arrayofsocialnetworks = array('jabber'=>'Jabber', 'skype'=>'Skype', 'twitter'=>'Twitter', 'facebook'=>'Facebook', 'linkedin'=>'LinkedIn');

foreach ($arrayofsocialnetworks as $snkey => $snlabel) {
    $consttocheck = 'SOCIALNETWORKS_'.strtoupper($snkey);
    if ($conf->use_javascript_ajax) {
        $link = ajax_constantonoff($consttocheck);
    } else {
        $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
        $link = $form->selectarray($consttocheck, $arrval, $conf->global->$consttocheck);
    }

    print $langs->trans('EnableFeatureFor', $snlabel).' '.$link.'<br><br>';
}


dol_fiche_end();

print '</form>';


// End of page
llxFooter();
$db->close();
