<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/install/index.php
 *       \ingroup    install
 *       \brief      Affichage page selection langue si premiere install.
 *					 Si reinstall, passe directement a la page check.php
 *       \version    $Id$
 */
include_once("./inc.php");
include_once("../core/class/html.form.class.php");
include_once("../html.formadmin.class.php");


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
print '<table><tr>';
print '<td>'.$langs->trans("DefaultLanguage").' : </td><td align="left">';
$formadmin->select_lang('auto','selectlang',1);
print '</td>';
print '</tr></table></center>';


// Si pas d'erreur, on affiche le bouton pour passer a l'etape suivante
if ($err == 0) pFooter(0);

?>
