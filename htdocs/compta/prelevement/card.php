<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2016 Juanjo Menent 		<jmenent@2byte.es>
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
 *	\file       htdocs/compta/prelevement/card.php
 *	\ingroup    prelevement
 *	\brief      Fiche prelevement
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/ligneprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");

if (!$user->rights->prelevement->bons->lire)
accessforbidden();

$langs->load("bills");
$langs->load("withdrawals");


// Security check
if ($user->societe_id > 0) accessforbidden();

// Get supervariables
$action = GETPOST('action','alpha');
$id = GETPOST('id','int');
$socid = GETPOST('socid','int');


$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='pl.fk_soc';
if (! $sortorder) $sortorder='DESC';


/*
 * Actions
 */

if ( $action == 'confirm_delete' )
{
	$bon = new BonPrelevement($db,"");
	$bon->fetch($id);

	$res=$bon->delete();
	if ($res > 0)
	{
		header("Location: index.php");
		exit;
	}
}

if ( $action == 'confirm_credite' && GETPOST('confirm','alpha') == 'yes')
{
	$bon = new BonPrelevement($db,"");
	$bon->fetch($id);

	$res=$bon->set_credite();
	if ($res >= 0)
	{
    	header("Location: card.php?id=".$id);
	   exit;
	}
}

if ($action == 'infotrans' && $user->rights->prelevement->bons->send)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$bon = new BonPrelevement($db,"");
	$bon->fetch($id);

	$dt = dol_mktime(12,0,0,GETPOST('remonth','int'),GETPOST('reday','int'),GETPOST('reyear','int'));

	/*
	if ($_FILES['userfile']['name'] && basename($_FILES['userfile']['name'],".ps") == $bon->ref)
	{
		$dir = $conf->prelevement->dir_output.'/receipts';

		if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $dir . "/" . dol_unescapefile($_FILES['userfile']['name']),1) > 0)
		{
			$bon->set_infotrans($user, $dt, GETPOST('methode','alpha'));
		}

		header("Location: card.php?id=".$id);
        exit;
	}
	else
	{
		dol_syslog("Fichier invalide",LOG_WARNING);
		$mesg='BadFile';
	}*/

	$error = $bon->set_infotrans($user, $dt, GETPOST('methode','alpha'));

	if ($error)
	{
		header("Location: card.php?id=".$id."&error=$error");
		exit;
	}
}

if ($action == 'infocredit' && $user->rights->prelevement->bons->credit)
{
	$bon = new BonPrelevement($db,"");
	$bon->fetch($id);
	$dt = dol_mktime(12,0,0,GETPOST('remonth','int'),GETPOST('reday','int'),GETPOST('reyear','int'));

	$error = $bon->set_infocredit($user, $dt);

	if ($error)
	{
		header("Location: card.php?id=".$id."&error=$error");
		exit;
	}
}


/*
 * View
 */

$bon = new BonPrelevement($db,"");
$form = new Form($db);

llxHeader('',$langs->trans("WithdrawalsReceipts"));


if ($id > 0)
{
	$bon->fetch($id);

	$head = prelevement_prepare_head($bon);
	dol_fiche_head($head, 'prelevement', $langs->trans("WithdrawalsReceipts"), '', 'payment');

	if (GETPOST('error','alpha')!='')
	{
		print '<div class="error">'.$bon->getErrorString(GETPOST('error','alpha')).'</div>';
	}

	/*if ($action == 'credite')
	{
		print $form->formconfirm("card.php?id=".$bon->id,$langs->trans("ClassCredited"),$langs->trans("ClassCreditedConfirm"),"confirm_credite",'',1,1);

	}*/

	print '<table class="border" width="100%">';

	print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$bon->getNomUrl(1).'</td></tr>';
	print '<tr><td>'.$langs->trans("Date").'</td><td>'.dol_print_date($bon->datec,'day').'</td></tr>';
	print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($bon->amount).'</td></tr>';

	// Status
	print '<tr><td>'.$langs->trans('Status').'</td>';
	print '<td>'.$bon->getLibStatut(1).'</td>';
	print '</tr>';

	if($bon->date_trans <> 0)
	{
		$muser = new User($db);
		$muser->fetch($bon->user_trans);

		print '<tr><td>'.$langs->trans("TransData").'</td><td>';
		print dol_print_date($bon->date_trans,'day');
		print ' '.$langs->trans("By").' '.$muser->getFullName($langs).'</td></tr>';
		print '<tr><td>'.$langs->trans("TransMetod").'</td><td>';
		print $bon->methodes_trans[$bon->method_trans];
		print '</td></tr>';
	}
	if($bon->date_credit <> 0)
	{
		print '<tr><td>'.$langs->trans('CreditDate').'</td><td>';
		print dol_print_date($bon->date_credit,'day');
		print '</td></tr>';
	}

	print '</table>';

	print '<br>';

	print '<table class="border" width="100%"><tr><td class="titlefield">';
	print $langs->trans("WithdrawalFile").'</td><td>';
	$relativepath = 'receipts/'.$bon->ref.'.xml';
	print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart=prelevement&amp;file='.urlencode($relativepath).'">'.$relativepath.'</a>';
	print '</td></tr></table>';

	dol_fiche_end();



	if (empty($bon->date_trans) && $user->rights->prelevement->bons->send && $action=='settransmitted')
	{
		print '<form method="post" name="userfile" action="card.php?id='.$bon->id.'" enctype="multipart/form-data">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="infotrans">';
		print '<table class="border" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("NotifyTransmision").'</td></tr>';
		print '<tr '.$bc[false].'><td width="20%">'.$langs->trans("TransData").'</td><td>';
		print $form->select_date('','','','','',"userfile",1,1);
		print '</td></tr>';
		print '<tr '.$bc[false].'><td width="20%">'.$langs->trans("TransMetod").'</td><td>';
		print $form->selectarray("methode",$bon->methodes_trans);
		print '</td></tr>';
/*			print '<tr><td width="20%">'.$langs->trans("File").'</td><td>';
		print '<input type="hidden" name="max_file_size" value="'.$conf->maxfilesize.'">';
		print '<input class="flat" type="file" name="userfile"><br>';
		print '</td></tr>';*/
		print '</table><br>';
		print '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("SetToStatusSent")).'"></div>';
		print '</form>';
	}

	if (! empty($bon->date_trans) && $bon->date_credit == 0 && $user->rights->prelevement->bons->credit && $action=='setcredited')
	{
		print '<form name="infocredit" method="post" action="card.php?id='.$bon->id.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="infocredit">';
		print '<table class="border" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("NotifyCredit").'</td></tr>';
		print '<tr '.$bc[false].'><td>'.$langs->trans('CreditDate').'</td><td>';
		print $form->select_date('','','','','',"infocredit",1,1);
		print '</td></tr>';
		print '</table>';
		print '<br>'.$langs->trans("ThisWillAlsoAddPaymentOnInvoice");
		print '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("ClassCredited")).'"></div>';
		print '</form>';
	}


	// Actions
	if ($action != 'settransmitted' && $action != 'setcredited')
	{
		print "\n<div class=\"tabsAction\">\n";

		if (empty($bon->date_trans) && $user->rights->prelevement->bons->send)
		{
			print "<a class=\"butAction\" href=\"card.php?action=settransmitted&id=".$bon->id."\">".$langs->trans("SetToStatusSent")."</a>";
		}

		if (! empty($bon->date_trans) && $bon->date_credit == 0)
		{
			print "<a class=\"butAction\" href=\"card.php?action=setcredited&id=".$bon->id."\">".$langs->trans("ClassCredited")."</a>";
		}

		print "<a class=\"butActionDelete\" href=\"card.php?action=confirm_delete&id=".$bon->id."\">".$langs->trans("Delete")."</a>";

		print "</div>";
	}


	$ligne=new LignePrelevement($db,$user);

	/*
	 * Lines into withdraw request
	 */
	$sql = "SELECT pl.rowid, pl.statut, pl.amount,";
	$sql.= " s.rowid as socid, s.nom as name";
	$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
	$sql.= ", ".MAIN_DB_PREFIX."prelevement_bons as pb";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	$sql.= " WHERE pl.fk_prelevement_bons = ".$id;
	$sql.= " AND pl.fk_prelevement_bons = pb.rowid";
	$sql.= " AND pb.entity = ".$conf->entity;
	$sql.= " AND pl.fk_soc = s.rowid";
	if ($socid)	$sql.= " AND s.rowid = ".$socid;
	$sql.= $db->order($sortfield, $sortorder);
	$sql.= $db->plimit($conf->liste_limit+1, $offset);

	$result = $db->query($sql);

	if ($result)
	{
		$num = $db->num_rows($result);
		$i = 0;

		$urladd = "&amp;id=".$id;

		print_barre_liste("", $page, $_SERVER["PHP_SELF"], $urladd, $sortfield, $sortorder, '', $num);
		print"\n<!-- debut table -->\n";
		print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Lines"),$_SERVER["PHP_SELF"],"pl.rowid",'',$urladd);
		print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER["PHP_SELF"],"s.nom",'',$urladd);
		print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"pl.amount","",$urladd,'align="right"');
		print_liste_field_titre('');
		print "</tr>\n";

		$var=false;

		$total = 0;

		while ($i < min($num,$conf->liste_limit))
		{
			$obj = $db->fetch_object($result);

			print "<tr ".$bc[$var].">";

			// Status of line
			print "<td>";
			print $ligne->LibStatut($obj->statut,2);
			print "&nbsp;";
			print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/ligne.php?id='.$obj->rowid.'">';
			print sprintf("%06s",$obj->rowid);
			print '</a></td>';

			$thirdparty=new Societe($db);
			$thirdparty->fetch($obj->socid);
			print '<td>';
			print $thirdparty->getNomUrl(1);
			print "</td>\n";

			print '<td align="right">'.price($obj->amount)."</td>\n";

			print '<td>';

			if ($obj->statut == 3)
			{
		  		print '<b>'.$langs->trans("StatusRefused").'</b>';
			}
			else
			{
		  		print "&nbsp;";
			}

			print '</td></tr>';

			$total += $obj->amount;
			$var=!$var;
			$i++;
		}

		if ($num > 0)
		{
			print '<tr class="liste_total">';
			print '<td>'.$langs->trans("Total").'</td>';
			print '<td>&nbsp;</td>';
			print '<td align="right">'.price($total)."</td>\n";
			print '<td>&nbsp;</td>';
			print "</tr>\n";
		}

		print "</table>";
		$db->free($result);
	}
	else
	{
		dol_print_error($db);
	}




}


llxFooter();

$db->close();
