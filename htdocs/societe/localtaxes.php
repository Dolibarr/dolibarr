<?php
/* Copyright (C) 2013      Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/societe/localtaxes.php
 *  \ingroup    societe
 *  \brief      Page of third party localtaxes rates
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->load("companies");

// Security check
$socid = GETPOST('socid','int');
$vatid = GETPOST('vatid','int');
$action = GETPOST('action','alpha');

if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '&societe');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('localtaxesthirdparty'));


/*
 *	Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
$error=$hookmanager->error; $errors=array_merge($errors, (array) $hookmanager->errors);


/*
 *	View
 */

$contactstatic = new Contact($db);
$form = new Form($db);

if ($socid)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$langs->load("companies");


	$soc = new Societe($db);
	$result = $soc->fetch($socid);
	llxHeader("",$langs->trans("LocalTaxes"),'');

	if (! empty($conf->notification->enabled)) $langs->load("mails");
	$head = societe_prepare_head($soc);

	dol_fiche_head($head, 'localtaxes', $langs->trans("ThirdParty"),0,'company');

	print '<table class="border" width="100%">';

	print '<tr><td width="25%">'.$langs->trans("ThirdPartyName").'</td><td colspan="3">';
	print $form->showrefnav($soc,'socid','',0,'rowid','nom');
	print '</td></tr>';

    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$soc->prefix_comm.'</td></tr>';
    }

	if ($soc->client)
	{
		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $soc->code_client;
		if ($soc->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	}

	if ($soc->fournisseur)
	{
		print '<tr><td>';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $soc->code_fournisseur;
		if ($soc->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}

	if (! empty($conf->barcode->enabled))
	{
		print '<tr><td>'.$langs->trans('Gencod').'</td><td colspan="3">'.$soc->barcode.'</td></tr>';
	}

	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">";
	dol_print_address($soc->address, 'gmap', 'thirdparty', $soc->id);
	print "</td></tr>";

	// Zip / Town
	print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$soc->zip."</td>";
	print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$soc->town."</td></tr>";

	// Country
	if ($soc->country) {
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		$img=picto_from_langcode($soc->country_code);
		print ($img?$img.' ':'');
		print $soc->country;
		print '</td></tr>';
	}

	// EMail
	print '<tr><td>'.$langs->trans('EMail').'</td><td colspan="3">';
	print dol_print_email($soc->email,0,$soc->id,'AC_EMAIL');
	print '</td></tr>';

	// Web
	print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3">';
	print dol_print_url($soc->url);
	print '</td></tr>';

	// Phone / Fax
	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->phone,$soc->country_code,0,$soc->id,'AC_TEL').'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->country_code,0,$soc->id,'AC_FAX').'</td></tr>';


	if ($mysoc->localtax1_assuj=="1" && $mysoc->localtax2_assuj=="1")
	{
		print '<tr><td class="nowrap">'.$langs->transcountry('LocalTax1IsUsed',$mysoc->country_code).'</td><td colspan="3">';
		print yn($soc->localtax1_assuj);
		print '</td></tr>';
		print '<tr><td class="nowrap">'.$langs->transcountry('LocalTax2IsUsed',$mysoc->country_code).'</td><td colspan="3">';
		print yn($soc->localtax2_assuj);
		print '</td></tr>';
	}
	elseif($mysoc->localtax1_assuj=="1")
	{
		print '<tr><td>'.$langs->transcountry('LocalTax1IsUsed',$mysoc->country_code).'</td><td colspan="3">';
		print yn($soc->localtax1_assuj);
		print '</td></tr>';
	}
	elseif($mysoc->localtax2_assuj=="1")
	{
		print '<tr><td>'.$langs->transcountry('LocalTax2IsUsed',$mysoc->country_code).'</td><td colspan="3">';
		print yn($soc->localtax2_assuj);
		print '</td></tr>';
	}

	print '</table>';

	dol_fiche_end();


	print '<form action="'.$_SERVER["PHP_SELF"].'?socid='.$soc->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="updatetva">';
	print '<input type="hidden" name="vatid" value="'.$vatid.'">';

	// Localtaxes

	print '<table id="tablelines" class="noborder" width="100%">';

	print '<tr class="liste_titre nodrag nodrop">';

	// Description
	print '<td>'.$langs->trans('Description').'</td>';

	// VAT
	print '<td align="right" width="80">'.$langs->trans('VAT').' (%)</td>';

	// Localtax 1
	if ($mysoc->localtax1_assuj=="1" && $soc->localtax1_assuj)
		print '<td align="right" width="80">'.$langs->transcountry('LocalTax1',$mysoc->country_code).' (%)</td>';

	if ($mysoc->localtax2_assuj=="1" && $soc->localtax2_assuj)
		print '<td align="right" width="80">'.$langs->transcountry('LocalTax2',$mysoc->country_code).' (%)</td>';

	//print '<td width="10"></td>';
	if ($user->rights->societe->creer)
		print '<td width="10" class="nowrap"></td>'; // No width to allow autodim

	print "</tr>\n";


	$sql  = "SELECT DISTINCT t.rowid, t.note, t.taux, t.localtax1, t.localtax2, t.recuperableonly";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_pays as p";
	$sql.= " WHERE t.fk_pays = p.rowid";
	$sql.= " AND t.active = 1";
	$sql.= " AND p.code IN ('".$mysoc->country_code."')";
	$sql.= " ORDER BY t.taux ASC, t.recuperableonly ASC";

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		if ($num)
		{
			$var=True;
			for ($i = 0; $i < $num; $i++)
			{
				$var=!$var;


				$obj = $db->fetch_object($resql);

				if ($action == 'edit' && $obj->rowid==$vatid && $user->rights->societe->creer)
				{
					print '<tr '.$bc[$var].'>';

					print '<td>'.$obj->note.'</td>';
					print '<td align="right">'.$obj->taux.'</td>';

					if ($mysoc->localtax1_assuj=="1" && $soc->localtax1_assuj)
						print '<td align="right"><input size="4" type="text" class="flat" name="localtax1" value="'.$obj->localtax1.'"></td>';
					if ($mysoc->localtax2_assuj=="1" && $soc->localtax2_assuj)
						print '<td align="right"><input size="4" type="text" class="flat" name="localtax2" value="'.$obj->localtax2.'"></td>';

					print '<td align="right"><input type="submit" class="button" name="save" value="'.$langs->trans('Save').'">';
					print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td>';
					print '</tr>';
				}
				else
				{
					print '<tr '.$bc[$var].'>';

					print '<td>'.$obj->note.'</td>';
					print '<td align="right">'.$obj->taux.'</td>';
					if ($mysoc->localtax1_assuj=="1" && $soc->localtax1_assuj)
						print '<td align="right">'.$obj->localtax1.'</td>';
					if ($mysoc->localtax2_assuj=="1" && $soc->localtax2_assuj)
						print '<td align="right">'.$obj->localtax2.'</td>';
					print '<td align="right">';
					if ($user->rights->societe->creer)
					{
						// TODO Comment this because the action to save is not supported
						//print '<a href="'.$_SERVER["PHP_SELF"].'?action=edit&socid='.$soc->id.'&vatid='.$obj->rowid.'">'.img_edit().'</a>';
					}
					print '</td>';
					print "</tr>\n";
				}
			}
		}
	}

	print '</table>';

	print '</form>';
}


llxFooter();

$db->close();
?>
