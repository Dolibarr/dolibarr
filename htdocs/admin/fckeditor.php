<?php
/* Copyright (C) 2004-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
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
 *
 */

/**
 *  \file       htdocs/admin/fckeditor.php
 *  \ingroup    fckeditor
 *  \brief      Page d'activation du module FCKeditor dans les autres modules
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$langs->load("admin");
$langs->load("fckeditor");

$action = GETPOST('action','alpha');

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
'PRODUCTDESC' => (! empty($conf->product->enabled) || ! empty($conf->service->enabled)),
'MAILING' => ! empty($conf->mailing->enabled),
'DETAILS' => (! empty($conf->facture->enabled) || ! empty($conf->propal->enabled) || ! empty($conf->commande->enabled)),
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
    if ($action == 'activate_'.strtolower($const))
    {
        dolibarr_set_const($db, "FCKEDITOR_ENABLE_".$const, "1",'chaine',0,'',$conf->entity);
        // Si fckeditor est active dans la description produit/service, on l'active dans les formulaires
        if ($const == 'PRODUCTDESC' && ! empty($conf->global->PRODUIT_DESC_IN_FORM))
        {
            dolibarr_set_const($db, "FCKEDITOR_ENABLE_DETAILS", "1",'chaine',0,'',$conf->entity);
        }
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    if ($action == 'disable_'.strtolower($const))
    {
        dolibarr_del_const($db, "FCKEDITOR_ENABLE_".$const,$conf->entity);
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
}

if (GETPOST('save','alpha'))
{
    $res=dolibarr_set_const($db, "FCKEDITOR_TEST", GETPOST('formtestfield'),'chaine',0,'',$conf->entity);

    if ($res > 0) $mesg=$langs->trans("RecordModifiedSuccessfully");
}



/*
 * View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("AdvancedEditor"),$linkback,'setup');
print '<br>';

$var=true;

if (empty($conf->use_javascript_ajax))
{
    dol_htmloutput_errors('',array($langs->trans("NotAvailable"),$langs->trans("JavascriptDisabled")),1);
}
else
{
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="2">'.$langs->trans("ActivateFCKeditor").'</td>';
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
        print '<td align="center" width="100">';
        $constante = 'FCKEDITOR_ENABLE_'.$const;
        $value = (isset($conf->global->$constante)?$conf->global->$constante:0);
        if ($value == 0)
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=activate_'.strtolower($const).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
        }
        else if ($value == 1)
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=disable_'.strtolower($const).'">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
        }

        print "</td>";
        print '</tr>';
    }

    print '</table>'."\n";

    dol_htmloutput_mesg($mesg);

    print '<br>'."\n";
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
}


llxFooter();
$db->close();
?>