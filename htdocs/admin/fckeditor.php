<?php
/* Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin         <regis@dolibarr.fr>
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
 */

/**
 *  \file       htdocs/admin/fckeditor.php
 *  \ingroup    fckeditor
 *  \brief      Page d'activation du module FCKeditor dans les autres modules
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");

$langs->load("admin");
$langs->load("fckeditor");

if (!$user->admin) accessforbidden();

// Constante et traduction de la description du module
$modules = array(
'SOCIETE' => 'FCKeditorForCompany',
'PRODUCTDESC' => 'FCKeditorForProduct',
'MAILING' => 'FCKeditorForMailing',
'DETAILS' => 'FCKeditorForProductDetails',
);
// Conditions pour que l'option soit proposee
$conditions = array(
'SOCIETE' => 1,
'PRODUCTDESC' => ($conf->product->enabled||$conf->service->enabled),
'MAILING' => $conf->mailing->enabled,
'DETAILS' => ($conf->facture->enabled||$conf->propal->enabled||$conf->commande->enabled),
);
// Picto
$picto = array(
'SOCIETE' => 'generic',
'PRODUCTDESC' => 'product',
'MAILING' => 'email',
'DETAILS' => 'generic',
);



/*
 *  Actions
 */

foreach($modules as $const => $desc)
{
    if ($_GET["action"] == 'activate_'.strtolower($const))
    {
        dolibarr_set_const($db, "FCKEDITOR_ENABLE_".$const, "1",'chaine',0,'',$conf->entity);
        // Si fckeditor est active dans la description produit/service, on l'active dans les formulaires
        if ($const == 'PRODUCTDESC' && $conf->global->PRODUIT_DESC_IN_FORM)
        {
            dolibarr_set_const($db, "FCKEDITOR_ENABLE_DETAILS", "1",'chaine',0,'',$conf->entity);
        }
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    if ($_GET["action"] == 'disable_'.strtolower($const))
    {
        dolibarr_del_const($db, "FCKEDITOR_ENABLE_".$const,$conf->entity);
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
}

if ($_POST["save"])
{
    dolibarr_set_const($db, "FCKEDITOR_TEST", $_POST["formtestfield"],'chaine',0,'',$conf->entity);
}



/*
 * View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("AdvancedEditor"),$linkback,'setup');
print '<br>';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("ActivateFCKeditor").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Action").'</td>';
print "</tr>\n";

// Modules
foreach($modules as $const => $desc)
{
    // Si condition non remplie, on ne propose pas l'option
    if (! $conditions[$const]) continue;

    $var=!$var;
    print "<tr ".$bc[$var].">";
    print '<td width="16">'.img_object("",$picto[$const]).'</td>';
    print '<td>'.$langs->trans($desc).'</td>';
    print '<td align="center" width="20">';

    $constante = 'FCKEDITOR_ENABLE_'.$const;
    $value = $conf->global->$constante;

    print $value == 1 ? img_picto($langs->trans("Active"),'tick') : '&nbsp;';

    print '</td>';
    print '<td align="center" width="100">';

    if($value == 0)
    {
        print '<a href="fckeditor.php?action=activate_'.strtolower($const).'">'.$langs->trans("Activate").'</a>';
    }
    else if($value == 1)
    {
        print '<a href="fckeditor.php?action=disable_'.strtolower($const).'">'.$langs->trans("Disable").'</a>';
    }

    print "</td>";
    print '</tr>';
}

print '</table>'."\n";


print '<br>'."\n";
print '<!-- Editor name = '.$conf->global->FCKEDITOR_EDITORNAME.' -->';
print_fiche_titre($langs->trans("TestSubmitForm"),'','');
print '<form name="formtest" method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
$uselocalbrowser=true;
$editor=new DolEditor('formtestfield',isset($conf->global->FCKEDITOR_TEST)?$conf->global->FCKEDITOR_TEST:'Test','',200,'dolibarr_notes','In', true, $uselocalbrowser);
$editor->Create();
print '<center><br><input class="button" type="submit" name="save" value="'.$langs->trans("Save").'"></center>'."\n";
print '</form>'."\n";

/*
 print '<!-- Result -->';
 print $_POST["formtestfield"];
 print '<!-- Result -->';
 print $conf->global->FCKEDITOR_TEST;
 */

$db->close();

llxFooter();
?>