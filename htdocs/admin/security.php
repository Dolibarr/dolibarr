<?php
/* Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/admin/security.php
        \ingroup    setup
        \brief      Page de configuration du module sécurité
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("users");
$langs->load("admin");

if (!$user->admin) accessforbidden();


/*
 * Actions
 */
if ($_GET["action"] == 'setgeneraterule')
{
	if (! dolibarr_set_const($db, 'USER_PASSWORD_GENERATED',$_GET["value"]))
	{
		dolibarr_print_error($db);
	}
	else
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

if ($_GET["action"] == 'activate_encrypt')
{
    dolibarr_set_const($db, "DATABASE_PWD_ENCRYPTED", "1");
    Header("Location: security.php");
    exit;
}
else if ($_GET["action"] == 'disable_encrypt')
{
	dolibarr_del_const($db, "DATABASE_PWD_ENCRYPTED");
    Header("Location: security.php");
    exit;
}

/*
 * Affichage onglet
 */

llxHeader();

print_fiche_titre($langs->trans("SecuritySetup"),'','setup');

print $langs->trans("GeneratedPasswordDesc")."<br>\n";
print "<br>\n";


$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/perms.php";
$head[$h][1] = $langs->trans("DefaultRights");
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/security.php";
$head[$h][1] = $langs->trans("Passwords");
$hselected=$h;
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/security_other.php";
$head[$h][1] = $langs->trans("Miscellanous");
$h++;

dolibarr_fiche_head($head, $hselected, $langs->trans("Security"));


$var=false;
$form = new Form($db);


// Choix du gestionnaire du générateur de mot de passe
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="USER_PASSWORD_GENERATED">';
print '<input type="hidden" name="consttype" value="yesno">';

// Charge tableau des modules generation
$dir = "../includes/modules/security/generate";
clearstatcache();
$handle=opendir($dir);
$i=1;
while (($file = readdir($handle))!==false)
{
    if (eregi('(modGeneratePass[a-z]+).class.php',$file,$reg))
    {
        // Chargement de la classe de numérotation
        $classname = $reg[1];
        require_once($dir.'/'.$file);

        $obj = new $classname($db,$conf,$langs,$user);
        $arrayhandler[$obj->id]=$obj;
		$i++;
    }
}
closedir($handle);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("RuleForGeneratedPasswords").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center">'.$langs->trans("Activated").'</td>';
print '</tr>';

foreach ($arrayhandler as $key => $module)
{
        $var = !$var;
        print '<tr '.$bc[$var].'><td width="100">';
        print ucfirst($key);
        print "</td><td>\n";
        print $arrayhandler[$key]->getDescription();
        print '</td>';

        // Affiche example
        print '<td width="60">'.$module->getExample().'</td>';

        print '<td width="50" align="center">';
        if ($conf->global->USER_PASSWORD_GENERATED == $key)
        {
            $title='';
            print img_tick($title);
        }
        else
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=setgeneraterule&amp;value='.$key.'">'.$langs->trans("Activate").'</a>';
        }
        print "</td></tr>\n";
}
print '</table>';
print '</form>';
print '<br>';

// Bon de livraison activation/desactivation
$var=false;
print "<form method=\"post\" action=\"security.php\">";
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("Encryption").'</td>';
print '<td>&nbsp;</td>';
print '<td align="center">'.$langs->trans("Activated").'</td>';
print '</tr>';
print "<input type=\"hidden\" name=\"action\" value=\"encrypt\">";
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("EncryptedPasswordInDatabase").'</td>';
print '<td>&nbsp;</td>';
print '<td align="center" width="20">';

if($conf->global->DATABASE_PWD_ENCRYPTED == 1)
{
	print img_tick();
}

print '</td>';
print '<td align="center" width="100">';

if($conf->global->DATABASE_PWD_ENCRYPTED == 0)
{
	print '<a href="security.php?action=activate_encrypt">'.$langs->trans("Activate").'</a>';
}
else if($conf->global->DATABASE_PWD_ENCRYPTED == 1)
{
	print '<a href="security.php?action=disable_encrypt">'.$langs->trans("Disable").'</a>';
}

print "</td>";
print '</tr>';
print '</table>';
print '</form>';


//print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';

print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
