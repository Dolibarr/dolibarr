<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
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
 */

/**
 *	\file       htdocs/admin/prelevement.php
 *	\ingroup    prelevement
 *	\brief      Page configuration des prelevements
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("admin");
$langs->load("withdrawals");

// Security check
if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');


/*
 * Actions
 */

if ($action == "set")
{
    $db->begin();
    for ($i = 0 ; $i < 2 ; $i++)
    {
    	$res = dolibarr_set_const($db, GETPOST("nom$i",'alpha'), GETPOST("value$i",'alpha'),'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
    }

    $id=GETPOST('PRELEVEMENT_ID_BANKACCOUNT','int');
    $account = new Account($db, $id);

    if($account->fetch($id)>0)
    {
        $res = dolibarr_set_const($db, "PRELEVEMENT_ID_BANKACCOUNT", $id,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        $res = dolibarr_set_const($db, "PRELEVEMENT_CODE_BANQUE", $account->code_banque,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        $res = dolibarr_set_const($db, "PRELEVEMENT_CODE_GUICHET", $account->code_guichet,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        $res = dolibarr_set_const($db, "PRELEVEMENT_NUMERO_COMPTE", $account->number,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        $res = dolibarr_set_const($db, "PRELEVEMENT_NUMBER_KEY", $account->cle_rib,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        $res = dolibarr_set_const($db, "PRELEVEMENT_IBAN", $account->iban,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        $res = dolibarr_set_const($db, "PRELEVEMENT_BIC", $account->bic,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
        $res = dolibarr_set_const($db, "PRELEVEMENT_RAISON_SOCIALE", $account->proprio,'chaine',0,'',$conf->entity);
        if (! $res > 0) $error++;
    }
    else $error++;

    if (! $error)
    {
        $db->commit();
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $db->rollback();
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}

if ($action == "addnotif")
{
    $bon = new BonPrelevement($db);
    $bon->AddNotification($db,GETPOST('user','int'),$action);

    header("Location: prelevement.php");
    exit;
}

if ($action == "deletenotif")
{
    $bon = new BonPrelevement($db);
    $bon->DeleteNotificationById(GETPOST('notif','int'));

    header("Location: prelevement.php");
    exit;
}


/*
 *	View
 */

$form=new Form($db);

llxHeader('',$langs->trans("WithdrawalsSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("WithdrawalsSetup"),$linkback,'setup');
print '<br>';

$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/prelevement.php";
$head[$h][1] = $langs->trans("Withdrawals");
$head[$h][2] = 'Withdrawal';
$hselected=$h;
$h++;

dol_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

print '<form method="post" action="prelevement.php?action=set">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="30%">'.$langs->trans("Parameter").'</td>';
print '<td width="40%">'.$langs->trans("Value").'</td>';
print "</tr>";

//User
print '<tr class="impair"><td>'.$langs->trans("ResponsibleUser").'</td>';
print '<td align="left">';
print '<input type="hidden" name="nom0" value="PRELEVEMENT_USER">';
print $form->select_dolusers($conf->global->PRELEVEMENT_USER,'value0',1);
print '</td>';
print '</tr>';

//Profid1 of Transmitter
print '<tr class="pair"><td>'.$langs->trans("NumeroNationalEmetter").' - '.$langs->transcountry('ProfId1',$mysoc->country_code).'</td>';
print '<td align="left">';
print '<input type="hidden" name="nom1" value="PRELEVEMENT_NUMERO_NATIONAL_EMETTEUR">';
print '<input type="text"   name="value1" value="'.$conf->global->PRELEVEMENT_NUMERO_NATIONAL_EMETTEUR.'" size="9" ></td>';
print '</tr>';

// Bank account (from Banks module)
print '<tr class="impair"><td>'.$langs->trans("BankToReceiveWithdraw").'</td>';
print '<td align="left">';
print $form->select_comptes($conf->global->PRELEVEMENT_ID_BANKACCOUNT,'PRELEVEMENT_ID_BANKACCOUNT',0,"courant=1",1);
print '</td></tr>';
print '</table>';
print '<br>';

print '<center><input type="submit" class="button" value="'.$langs->trans("Save").'"></center>';

print '</form>';

print '<br>';

/*
 * Notifications
 */

if (! empty($conf->global->MAIN_MODULE_NOTIFICATION))
{
    $langs->load("mails");
    print_titre($langs->trans("Notifications"));

    $sql = "SELECT u.rowid, u.lastname, u.firstname, u.fk_societe, u.email";
    $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
    $sql.= " WHERE entity IN (0,".$conf->entity.")";

    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $var = true;
        $i = 0;
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            $var=!$var;
            if (!$obj->fk_societe)
            {
                $username=dolGetFirstLastname($obj->firstname,$obj->lastname);
                $internalusers[$obj->rowid] = $username;
            }

            $i++;
        }
        $db->free($resql);
    }

    // Get list of triggers for module withdraw
    $sql = "SELECT rowid, code, label";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_action_trigger";
    $sql.= " WHERE elementtype = 'withdraw'";
    $sql.= " ORDER BY rang ASC";

    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        $var = false;
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            $label=($langs->trans("Notify_".$obj->code)!="Notify_".$obj->code?$langs->trans("Notify_".$obj->code):$obj->label);
            $actions[$obj->rowid]=$label;
            $i++;
        }
        $db->free($resql);
    }


    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=addnotif">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("User").'</td>';
    print '<td>'.$langs->trans("Value").'</td>';
    print '<td align="right">'.$langs->trans("Action").'</td>';
    print "</tr>\n";

    print '<tr class="impair"><td align="left">';
    print $form->selectarray('user',$internalusers);//  select_users(0,'user',0);
    print '</td>';

    print '<td>';
    print $form->selectarray('action',$actions);//  select_users(0,'user',0);
    print '</td>';

    print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td></tr>';
}
// List of current notifications for objet_type='withdraw'
$sql = "SELECT u.lastname, u.firstname,";
$sql.= " nd.rowid, ad.code, ad.label";
$sql.= " FROM ".MAIN_DB_PREFIX."user as u,";
$sql.= " ".MAIN_DB_PREFIX."notify_def as nd,";
$sql.= " ".MAIN_DB_PREFIX."c_action_trigger as ad";
$sql.= " WHERE u.rowid = nd.fk_user";
$sql.= " AND nd.fk_action = ad.rowid";
$sql.= " AND u.entity IN (0,".$conf->entity.")";

$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    $var = false;
    while ($i < $num)
    {
        $obj = $db->fetch_object($resql);
        $var=!$var;

        print "<tr ".$bc[$var].">";
        print '<td>'.dolGetFirstLastname($obj->firstname,$obj->lastname).'</td>';
        $label=($langs->trans("Notify_".$obj->code)!="Notify_".$obj->code?$langs->trans("Notify_".$obj->code):$obj->label);
        print '<td>'.$label.'</td>';
        print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=deletenotif&amp;notif='.$obj->rowid.'">'.img_delete().'</a></td>';
        print '</tr>';
        $i++;
    }
    $db->free($resql);
}

print '</table>';
print '</form>';

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
