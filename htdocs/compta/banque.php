<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne					<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2012		Juanjo Menent				<jmenent@2byte.es>
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
 *      \file       htdocs/admin/facture.php
 *		\ingroup    facture
 *		\brief      Page to setup invoice module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';


$langs->load("admin");
$langs->load("errors");
$langs->load('other');
$langs->load('bills');
$langs->load('companies');

$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$socid=GETPOST('socid','int');
$originid=GETPOST('originid','int');
$action = GETPOST('action','alpha');
$value = GETPOST('value','alpha');

$object=new Facture($db);
$object->fetch($id);

/*
 * Actions
 */

if ($action == 'setribchq')
{
	$rib = GETPOST('rib','alpha');
	$chq = GETPOST('chq','alpha');

	$res = dolibarr_set_const($db, "FACTURE_RIB_NUMBER",$rib,'chaine',0,'',$conf->entity);
    $res = dolibarr_set_const($db, "FACTURE_CHQ_NUMBER",$chq,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
    {
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}


/*
 * View
 */

$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);

llxHeader("",$langs->trans("BillsSetup"),'EN:Invoice_Configuration|FR:Configuration_module_facture|ES:ConfiguracionFactura');

$form=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$id.'">'.$langs->trans("CardBill").'</a>';
print_fiche_titre($langs->trans("BillsSetup"),$linkback,'setup');
print '<br>';


/*
 *  Modes de reglement
 *
 */
print '<br>';
print_titre($langs->trans("SuggestedPaymentModesIfNotDefinedInInvoice"));

print '<form action="'.$_SERVER["PHP_SELF"].'?facid='.$id.'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';

//print $id;
print '<table class="noborder" width="100%">';
$var=True;

print '<tr class="liste_titre">';
print '<td>';
print '<input type="hidden" name="action" value="setribchq">';
print $langs->trans("PaymentMode").'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";

if($object->mode_reglement_code == 'VIR')
{
$var=!$var;
print '<tr '.$bc[$var].'>';
print "<td>".$langs->trans("SuggestPaymentByRIBOnAccount")."</td>";
print "<td>";
if (! empty($conf->banque->enabled))
{
    $sql = "SELECT rowid, label";
    $sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
    $sql.= " WHERE clos = 0";
    $sql.= " AND courant = 1";
    $sql.= " AND entity = ".$conf->entity;
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        if ($num > 0)
        {
        	print '<select name="rib" class="flat" id="rib">';
        	print '<option value="0">'.$langs->trans("DoNotSuggestPaymentMode").'</option>';
            while ($i < $num)
            {
                $row = $db->fetch_row($resql);

                print '<option value="'.$row[0].'"';
                print $conf->global->FACTURE_RIB_NUMBER == $row[0] ? ' selected="selected"':'';
                print '>'.$row[1].'</option>';

                $i++;
            }
            print "</select>";
        }
        else
        {
        	print "<i>".$langs->trans("NoActiveBankAccountDefined")."</i>";
        }
    }
}
else
{
    print $langs->trans("BankModuleNotActive");
}
print "</td></tr>";
}
else if($object->mode_reglement_code == 'CHQ')
{
$var=!$var;
print '<tr '.$bc[$var].'>';
print "<td>".$langs->trans("SuggestPaymentByChequeToAddress")."</td>";
print "<td>";
print '<select class="flat" name="chq" id="chq">';
print '<option value="0">'.$langs->trans("DoNotSuggestPaymentMode").'</option>';
print '<option value="-1"'.($conf->global->FACTURE_CHQ_NUMBER?' selected="selected"':'').'>'.$langs->trans("MenuCompanySetup").' ('.($mysoc->name?$mysoc->name:$langs->trans("NotDefined")).')</option>';

$sql = "SELECT rowid, label";
$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
$sql.= " WHERE clos = 0";
$sql.= " AND courant = 1";
$sql.= " AND entity = ".$conf->entity;
$var=True;
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num)
    {
        $var=!$var;
        $row = $db->fetch_row($resql);

        print '<option value="'.$row[0].'"';
        print $conf->global->FACTURE_CHQ_NUMBER == $row[0] ? ' selected="selected"':'';
        print '>'.$langs->trans("OwnerOfBankAccount",$row[1]).'</option>';

        $i++;
    }
}
print "</select>";
print "</td></tr>";
}
print "</table>";
print "</form>";


//dol_fiche_end();

dol_htmloutput_mesg($mesg);


llxFooter();

$db->close();
?>
