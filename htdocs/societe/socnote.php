<?php
/* Copyright (C) 2001-2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010           Juanjo Menent        <jmenent@2byte.es>
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
 *   \file       htdocs/societe/socnote.php
 *   \brief      Tab for notes on third party
 *   \ingroup    societe
 *   \version    $Id: socnote.php,v 1.23 2011/07/31 23:22:56 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

$action = isset($_GET["action"])?$_GET["action"]:$_POST["action"];

$langs->load("companies");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:$_POST["socid"];
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);



/*
 * Actions
 */

if ($action == 'add' && ! GETPOST('cancel'))
{
    $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET note='".$db->escape($_POST["note"])."' WHERE rowid=".$_POST["socid"];
    $result = $db->query($sql);

    $_GET["socid"]=$_POST["socid"];   // Pour retour sur fiche
    $socid = $_GET["socid"];
}


/*
 *	View
 */

if ($conf->global->MAIN_DIRECTEDITMODE && $user->rights->societe->creer) $action='edit';

$form = new Form($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty").' - '.$langs->trans("Notes"),$help_url);

if ($socid > 0)
{
    $societe = new Societe($db, $socid);
    $societe->fetch($socid);

    /*
     * Affichage onglets
     */
    if ($conf->notification->enabled) $langs->load("mails");

    $head = societe_prepare_head($societe);

    dol_fiche_head($head, 'note', $langs->trans("ThirdParty"),0,'company');


    print "<form method=\"post\" action=\"".DOL_URL_ROOT."/societe/socnote.php\">";
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    print '<table class="border" width="100%">';

    print '<tr><td width="20%">'.$langs->trans('ThirdPartyName').'</td>';
    print '<td colspan="3">';
    print $form->showrefnav($societe,'socid','',($user->societe_id?0:1),'rowid','nom');
    print '</td></tr>';

    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$societe->prefix_comm.'</td></tr>';
    }

    if ($societe->client)
    {
        print '<tr><td>';
        print $langs->trans('CustomerCode').'</td><td colspan="3">';
        print $societe->code_client;
        if ($societe->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
        print '</td></tr>';
    }

    if ($societe->fournisseur)
    {
        print '<tr><td>';
        print $langs->trans('SupplierCode').'</td><td colspan="3">';
        print $societe->code_fournisseur;
        if ($societe->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
        print '</td></tr>';
    }

    print '<tr><td valign="top">'.$langs->trans("Note").'</td>';
    print '<td valign="top">';
    if ($action == 'edit' && $user->rights->societe->creer)
    {
        print "<input type=\"hidden\" name=\"action\" value=\"add\">";
        print "<input type=\"hidden\" name=\"socid\" value=\"".$societe->id."\">";

        // Editeur wysiwyg
        require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
        $doleditor=new DolEditor('note',$societe->note,'',360,'dolibarr_notes','In',true,false,$conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_SOCIETE,20,70);
        $doleditor->Create();
    }
    else
    {
        print dol_textishtml($societe->note)?$societe->note:dol_nl2br($societe->note,1,true);
    }
    print "</td></tr>";

    if ($action == 'edit')
    {
        print '<tr><td colspan="2" align="center">';
        print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
        print ' &nbsp; ';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
        print '</td></tr>';
    }

    print "</table>";

    print '</form>';
}

print '</div>';


/*
 * Buttons
 */

if ($action != 'edit')
{
    print '<div class="tabsAction">';

    if ($user->rights->societe->creer)
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/societe/socnote.php?socid='.$societe->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
    }

    print '</div>';
}


$db->close();

llxFooter('$Date: 2011/07/31 23:22:56 $ - $Revision: 1.23 $');
?>
