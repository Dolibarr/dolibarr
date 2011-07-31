<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/install/index.php
 *       \ingroup    install
 *       \brief      Affichage page selection langue si premiere install.
 *					 Si reinstall, passe directement a la page check.php
 *       \version    $Id: index.php,v 1.36 2011/07/31 23:26:19 eldy Exp $
 */
include_once("./inc.php");
include_once("../core/class/html.form.class.php");
include_once("../core/class/html.formadmin.class.php");


$err = 0;

// Si fichier conf existe deja et rempli, on est pas sur une premiere install,
// on ne passe donc pas par la page de choix de langue
if (file_exists($conffile) && isset($dolibarr_main_url_root))
{
    header("Location: check.php?testget=ok");
    exit;
}

$langs->load("admin");


/*
 * View
 */

$formadmin=new FormAdmin('');	// Note: $db does not exist yet but we don't need it, so we put ''.

pHeader("", "check");   // Etape suivante = index2


print '<center>';
print '<img src="../theme/dolibarr_logo.png" alt="Dolibarr logo"><br>';
print DOL_VERSION.'<br><br>';
print '</center>';

// Ask installation language
print '<br><br><center>';
print '<table>';

print '<tr>';
print '<td>'.$langs->trans("DefaultLanguage").' : </td><td align="left">';
print $formadmin->select_language('auto','selectlang',1,0,0,1);
print '</td>';
print '</tr>';

print '</table></center>';

print '<br><br>'.$langs->trans("SomeTranslationAreUncomplete");

// Si pas d'erreur, on affiche le bouton pour passer a l'etape suivante
if ($err == 0) pFooter(0);

?>
