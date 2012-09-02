<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/comm/remise.php
 *      \ingroup    societe
 *		\brief      Page to edit relative discount of a customer
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

$langs->load("companies");
$langs->load("orders");
$langs->load("bills");

$socid = GETPOST('id','int');
// Security check
if ($user->societe_id > 0)
{
	$socid = $user->societe_id;
}

$backtopage = GETPOST('backtopage','alpha');


/*
 * Actions
 */

if (GETPOST('cancel') && ! empty($backtopage))
{
     header("Location: ".$backtopage);
     exit;
}

if (GETPOST("action") == 'setremise')
{
	$soc = New Societe($db);
	$soc->fetch($_GET["id"]);
	$result=$soc->set_remise_client($_POST["remise"],$_POST["note"],$user);

	if ($result > 0)
	{
	    if (! empty($backtopage))
	    {
    		header("Location: ".$backtopage);
    		exit;
	    }
	    else
	    {
    		header("Location: remise.php?id=".$_GET["id"]);
    		exit;
	    }
	}
	else
	{
		$errmesg=$soc->error;
	}
}


/*
 * View
 */

$form=new Form($db);

llxHeader();


/*********************************************************************************
 *
 * Mode fiche
 *
 *********************************************************************************/
if ($socid > 0)
{
	// On recupere les donnees societes par l'objet
	$objsoc = new Societe($db);
	$objsoc->id=$socid;
	$objsoc->fetch($socid);

	dol_htmloutput_errors($errmesg);

	$head = societe_prepare_head($objsoc);

	dol_fiche_head($head, 'relativediscount', $langs->trans("ThirdParty"),0,'company');


	print '<table class="border" width="100%">';

    // Name
	print '<tr><td colspan="2" width="25%">'.$langs->trans('Name').'</td>';
	print '<td colspan="2">';
	print $form->showrefnav($objsoc,'id','',1,'rowid','nom');
	print '</td></tr>';

	// Remise
	print '<tr><td colspan="2" width="25%">';
	print $langs->trans("CustomerRelativeDiscount").'</td><td colspan="2">'.$objsoc->remise_client."%</td></tr>";

	print '</table>';
	print '<br>';

	print_fiche_titre($langs->trans("NewRelativeDiscount"),'','');

	print '<form method="POST" action="remise.php?id='.$objsoc->id.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="setremise">';
    print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print '<table class="border" width="100%">';

	// Nouvelle valeur
	print '<tr><td colspan="2">';
	print $langs->trans("NewValue").'</td><td colspan="2"><input type="text" size="5" name="remise" value="'.($_POST["remise"]?$_POST["remise"]:$objsoc->remise_client).'">%</td></tr>';

	// Motif/Note
	print '<tr><td colspan="2" width="25%">';
	print $langs->trans("NoteReason").'</td><td colspan="2"><input type="text" size="60" name="note" value="'.$_POST["note"].'"></td></tr>';

	print "</table>";

	print '<center>';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
    if (! empty($backtopage))
    {
        print '&nbsp; &nbsp; ';
	    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
    }
	print '</center>';

	print "</form>";

	dol_fiche_end();

	print '<br>';


	/*
	 * Liste de l'historique des avoirs
	 */
	$sql  = "SELECT rc.rowid,rc.remise_client,rc.note, rc.datec as dc,";
	$sql.= " u.login, u.rowid as user_id";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe_remise as rc, ".MAIN_DB_PREFIX."user as u";
	$sql.= " WHERE rc.fk_soc =". $objsoc->id;
	$sql.= " AND u.rowid = rc.fk_user_author";
	$sql.= " ORDER BY rc.datec DESC";

	$resql=$db->query($sql);
	if ($resql)
	{
		print '<table class="noborder" width="100%">';
		$tag = !$tag;
		print '<tr class="liste_titre">';
		print '<td width="160">'.$langs->trans("Date").'</td>';
		print '<td width="160" align="center">'.$langs->trans("CustomerRelativeDiscountShort").'</td>';
		print '<td align="left">'.$langs->trans("NoteReason").'</td>';
		print '<td align="center">'.$langs->trans("User").'</td>';
		print '</tr>';
		$i = 0 ;
		$num = $db->num_rows($resql);

		while ($i < $num )
		{
			$obj = $db->fetch_object($resql);
			$tag = !$tag;
			print '<tr '.$bc[$tag].'>';
			print '<td>'.dol_print_date($db->jdate($obj->dc),"dayhour").'</td>';
			print '<td align="center">'.$obj->remise_client.' %</td>';
			print '<td align="left">'.$obj->note.'</td>';
			print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';
			print '</tr>';
			$i++;
		}
		$db->free($resql);
		print "</table>";
	}
	else
	{
		dol_print_error($db);
	}

}

$db->close();

llxFooter();
?>
