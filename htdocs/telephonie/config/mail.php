<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * $Id$
 * $Source$
 */

/**
    \file       htdocs/telephonie/config/index.php
    \ingroup    telephonie
    \brief      Page configuration telephonie
    \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");

if (!$user->admin) accessforbidden();

if ($_GET["action"] == "set")
{

  dolibarr_set_const($db, 'TELEPHONIE_MAIL_FACTURATION_SUJET', $_POST["sujet"],'chaine',0,'',$conf->entity);

  dolibarr_set_const($db, 'TELEPHONIE_MAIL_FACTURATION_SIGNATURE', $_POST["signature"],'chaine',0,'',$conf->entity);

  Header("Location: mail.php");
}

/*
 *
 *
 *
 */
llxHeader('','Telephonie - Configuration');
print_titre("Configuration du module de Telephonie");

print "<br>";

/*
 *
 *
 */
print_titre("Emails");
print '<form method="post" action="mail.php?action=set">';
print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print '<tr class="liste_titre">';
print '<td>Nom</td>';
print '<td>Valeur</td><td>&nbsp;</td><td>&nbsp;</td>';
print "</tr>\n";

print '<tr class="pair"><td>';
print 'Compte de ventilation</td><td>';
print TELEPHONIE_MAIL_FACTURATION_SUJET;
print '</td><td>TELEPHONIE_MAIL_FACTURATION_SUJET</td></tr>';
print '<tr class="impair"><td>';
print 'Compte de ventilation</td><td>';
print nl2br(TELEPHONIE_MAIL_FACTURATION_SIGNATURE);
print '</td><td>TELEPHONIE_MAIL_FACTURATION_SIGNATURE</td></tr>';

$html = new Form($db);

print '<tr><td>Sujet</td><td>';

print '<input type="text" name="sujet" size="30" value="'.TELEPHONIE_MAIL_FACTURATION_SUJET.'"></td></tr>';

print '<tr><td>Signature</td><td>';

print '<textarea name="signature" cols="40" rows="10">'.TELEPHONIE_MAIL_FACTURATION_SIGNATURE.'</textarea></td></tr>';

print '<tr><td colspan="3" align="center"><input type="submit"></td></tr>';

print '</table>';
print '</form>';

$db->close();

llxFooter();
?>
