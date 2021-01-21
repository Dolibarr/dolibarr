<?php
/* Copyright (C) 2004-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *       \file       htdocs/install/index.php
 *       \ingroup    install
 *       \brief      Show page to select language. This is done only for a first installation.
 *					 For a reinstall this page redirect to page check.php
 */
include_once 'inc.php';
include_once '../core/class/html.form.class.php';
include_once '../core/class/html.formadmin.class.php';

global $langs;

$err = 0;

// If the config file exists and is filled, we're not on first install so we skip the language selection page
if (file_exists($conffile) && isset($dolibarr_main_url_root))
{
	header("Location: check.php?testget=ok");
	exit;
}

$langs->load("admin");


/*
 * View
 */

$formadmin = new FormAdmin(''); // Note: $db does not exist yet but we don't need it, so we put ''.

pHeader("", "check"); // Next step = check


// Ask installation language
print '<br><br><div class="center">';
print '<table>';

print '<tr>';
print '<td>'.$langs->trans("DefaultLanguage").' : </td><td>';
print $formadmin->select_language('auto', 'selectlang', 1, 0, 0, 1);
print '</td>';
print '</tr>';

print '</table></div>';

print '<br><br><span class="opacitymedium">'.$langs->trans("SomeTranslationAreUncomplete").'</span>';

// If there's no error, we display the next step button
if ($err == 0) pFooter(0);
