<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id: fiche.php,v 1.14 2011/07/31 22:23:31 eldy Exp $
 */


/**
        \file       htdocs/compta/param/comptes/fiche.php
        \ingroup    compta
        \brief      Page de la fiche des comptes comptables
        \version    $Revision: 1.14 $
*/

require("../../../main.inc.php");

$mesg = '';

if ($_POST["action"] == 'add' && $user->rights->compta->ventilation->parametrer)
{
  $compte = new ComptaCompte($db);

  $compte->numero   = $_POST["numero"];
  $compte->intitule = $_POST["intitule"];

  $e_compte = $compte;

  $res = $compte->create($user);

  if ($res == 0)
    {
      Header("Location: liste.php");
    }
  else
    {
      if ($res == -3)
	{
	  $_error = 1;
	  $_GET["action"] = "create";
	  $_GET["type"] = $_POST["type"];
	}
      if ($res == -4)
	{
	  $_error = 2;
	  $_GET["action"] = "create";
	  $_GET["type"] = $_POST["type"];
	}
    }
}

llxHeader("","Nouveau compte");

/*
 * Cr�ation d'un compte
 *
 */
if ($_GET["action"] == 'create' && $user->rights->compta->ventilation->parametrer)
{
    $html = new Form($db);
    $nbligne=0;

    print_fiche_titre($langs->trans("NewAccount"));

    print '<form action="fiche.php" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="type" value="'.$_GET["type"].'">'."\n";

    print '<table class="border" width="100%">';
    print '<tr>';
    print '<td>'.$langs->trans("AccountNumber").'</td><td><input name="numero" size="20" value="'.$compte->numero.'">';
    if ($_error == 1)
    {
        print "Ce num�ro de compte existe d�j�";
    }
    if ($_error == 2)
    {
        print "Valeur(s) manquante(s)";
    }
    print '</td></tr>';
    print '<tr><td>'.$langs->trans("Label").'</td><td><input name="intitule" size="40" value="'.$compte->intitule.'"></td></tr>';

    print '<tr><td>&nbsp;</td><td><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';
    print '</table>';
    print '</form>';
}

$db->close();

llxFooter('$Date: 2011/07/31 22:23:31 $ - $Revision: 1.14 $');
?>
