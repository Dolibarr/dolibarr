<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.fr>
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
	\file       htdocs/fourn/facture/fiche.php
	\ingroup    facture
	\brief      Page des la fiche facture fournisseur
	\version    $Revision$
*/

require('./pre.inc.php');
require('./paiementfourn.class.php');

if (!$user->rights->fournisseur->facture->lire)
	accessforbidden();

$langs->load('bills');
$langs->load('suppliers');
$langs->load('companies');

// Sécurité accés client
if ($user->societe_id > 0)
{
	$action = '';
	$socidp = $user->societe_id;
}


$html = new Form($db);
$mesg='';
$action=isset($_GET['action'])?$_GET['action']:$_POST['action'];

if ($_POST['action'] == 'confirm_valid' && $_POST['confirm'] == 'yes' && $user->rights->fournisseur->facture->valider)
{
	$facturefourn=new FactureFournisseur($db);
	$facturefourn->fetch($_GET['facid']);
    $facturefourn->set_valid($user);
	Header('Location: fiche.php?facid='.$_GET['facid']);
	exit;
}


if ($_GET['action'] == 'payed')
{
	$facturefourn=new FactureFournisseur($db);
	$facturefourn->fetch($_GET['facid']);
    $facturefourn->set_payed($user);
}

if($_GET['action'] == 'deletepaiement')
{
	$facfou = new FactureFournisseur($db);
	$facfou->fetch($_GET['facid']);
	if ($facfou->statut == 1 && $facfou->paye == 0 && $user->societe_id == 0)
	{
		$paiementfourn = new PaiementFourn($db);
		$paiementfourn->fetch($_GET['paiement_id']);
		$paiementfourn->delete();
	}
}

if ($_POST['action'] == 'modif_libelle')
{
	$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn set libelle = \''.$form_libelle.'\' WHERE rowid = '.$_GET['facid'];
	$result = $db->query( $sql);
}


if ($_POST['action'] == 'update')
{
	$datefacture = $db->idate(mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']));
	$date_echeance = $db->idate(mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']));

	$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn set ';
	$sql .= " facnumber='".trim($_POST['facnumber'])."'";
	$sql .= ", libelle='".trim($_POST['libelle'])."'";
	$sql .= ", note='".$_POST['note']."'";
	$sql .= ", datef = '$datefacture'";
	$sql .= ", date_lim_reglement = '$date_echeance'";
	$sql .= ' WHERE rowid = '.$_GET['facid'].' ;';
	$result = $db->query( $sql);
}
/*
 * Action création
 */
if ($_POST['action'] == 'add' && $user->rights->fournisseur->facture->creer)
{
	if ($_POST['facnumber'])
	{
		$datefacture = mktime(12,0,0,
			$_POST['remonth'],
			$_POST['reday'],
			$_POST['reyear']);

		$tva = 0;
		$amo = price2num($_POST['amount']);
		$tva = (price2num($_POST['tva_taux']) * $amo) / 100 ;
		$remise = 0;
		$total = $tva + $amo;

		$db->begin();

		// Creation facture
		$facfou = new FactureFournisseur($db);

		$facfou->ref           = $_POST['facnumber'];
		$facfou->socidp        = $_POST['socidp'];
		$facfou->libelle       = $_POST['libelle'];
		$facfou->date          = $datefacture;
		$facfou->date_echeance = mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']);
		$facfou->note          = $_POST['note'];

		$facid = $facfou->create($user);

		// Ajout des lignes de factures
		if ($facid > 0)
		{
			for ($i = 1 ; $i < 9 ; $i++)
			{
				$label = $_POST['label'.$i];
				$amount = price2num($_POST['amount'.$i]);
				$amountttc = price2num($_POST['amountttc'.$i]);
				$tauxtva = price2num($_POST['tauxtva'.$i]);
				$qty = $_POST['qty'.$i];

				if (strlen($label) > 0 && !empty($amount))
				{
					$atleastoneline=1;
					$ret=$facfou->addline($label, $amount, $tauxtva, $qty, 1);
					if ($ret < 0) $nberror++;
				}
				else if (strlen($label) > 0 && empty($amount))
				{
					$ht = $amountttc / (1 + ($tauxtva / 100));
					$atleastoneline=1;
					$ret=$facfou->addline($label, $ht, $tauxtva, $qty, 1);
					if ($ret < 0) $nberror++;
				}
			}
			if ($nberror)
			{
				$db->rollback();
				$mesg='<div class="error">'.$facfou->error.'</div>';
				$_GET['action']='create';
			}
			else
			{
				$db->commit();
				header('Location: fiche.php?facid='.$facid);
				exit;
			}
		}
		else
		{
			$db->rollback();
			$mesg='<div class="error">'.$facfou->error.'</div>';
			$_GET['action']='create';
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->trans('Ref')).'</div>';
		$_GET['action']='create';
	}
}

if ($_GET['action'] == 'del_ligne')
{
	$facfou = new FactureFournisseur($db,'',$_GET['facid']);
	$facfou->deleteline($_GET['ligne_id']);
	$_GET['action'] = 'edit';
}

if ($_GET['action'] == 'add_ligne')
{
	$facfou = new FactureFournisseur($db, '', $_GET['facid']);
	$tauxtva = price2num($_POST['tauxtva']);
	if (strlen($_POST['label']) > 0 && !empty($_POST['amount']))
	{
		$ht = price2num($_POST['amount']);
		$facfou->addline($_POST['label'], $ht, $tauxtva, $_POST['qty']);
	}
	else
	{
		$ttc = price2num($_POST['amountttc']);
		$ht = $ttc / (1 + ($tauxtva / 100));
		$facfou->addline($_POST['label'], $ht, $tauxtva, $_POST['qty']);
	}
	$_GET['action'] = 'edit';
}



/*********************************************************************
*
* Mode creation
*
**********************************************************************/

if ($_GET['action'] == 'create' or $_GET['action'] == 'copy')
{
	llxHeader();

	print_titre($langs->trans('NewBill'));

	if ($mesg) { print $mesg.'<br>'; }

	if ($_GET['action'] == 'copy')
	{
		$fac_ori = new FactureFournisseur($db);
		$fac_ori->fetch($_GET['facid']);
	}

	print '<form action="fiche.php" method="post">';
	print '<input type="hidden" name="action" value="add">';
	print '<table class="border" width="100%">';
	print '<tr><td>'.$langs->trans('Company').'</td>';

	print '<td>';
	$html->select_societes(empty($_GET['socid'])?'':$_GET['socid'],'socidp','s.fournisseur = 1');
	print '</td>';
	print '<td width="50%">'.$langs->trans('Comments').'</td></tr>';

	print '<tr><td>'.$langs->trans('Ref').'</td><td><input name="facnumber" type="text"></td>';

	print '<td width="50%" rowspan="4" valign="top"><textarea name="note" wrap="soft" cols="30" rows="6"></textarea></td></tr>';
	if ($_GET['action'] == 'copy')
	{
		print '<tr><td>'.$langs->trans('Label').'</td><td><input size="30" name="libelle" value="'.$fac_ori->libelle.'" type="text"></td></tr>';
	}
	else
	{
		print '<tr><td>'.$langs->trans('Label').'</td><td><input size="30" name="libelle" type="text"></td></tr>';
	}
	print '<tr><td>'.$langs->trans('Date').'</td><td>';
	$html->select_date();
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('DateEcheance').'</td><td>';
	$html->select_date('','ech');
	print '</td></tr>';

	print '</table><br>';

	print '<table class="border" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>&nbsp;</td><td>'.$langs->trans('Label').'</td>';
	print '<td align="center">'.$langs->trans('PriceUHT').'</td>';
	print '<td align="center">'.$langs->trans('Qty').'</td>';
	print '<td align="center">'.$langs->trans('VATRate').'</td>';
	print '<td align="center">'.$langs->trans('PriceUTTC').'</td>';
	print '</tr>';

	for ($i = 1 ; $i < 9 ; $i++)
	{
		if ($_GET['action'] == 'copy')
		{
			$value_label = $fac_ori->lignes[$i-1][0];
			$value_pu = $fac_ori->lignes[$i-1][1];
			$value_qty = $fac_ori->lignes[$i-1][3];
		}
		else
		{
			$value_qty = '1';
		}
		print '<tr><td>'.$i.'</td>';
		print '<td><input size="50" name="label'.$i.'" value="'.$value_label.'" type="text"></td>';
		print '<td align="center"><input type="text" size="8" name="amount'.$i.'" value="'.$value_pu.'"></td>';
		print '<td align="center"><input type="text" size="3" name="qty'.$i.'" value="'.$value_qty.'"></td>';
		print '<td align="center">';
		$html->select_tva('tauxtva'.$i);
		print '</td>';
		print '<td align="center"><input type="text" size="8" name="amountttc'.$i.'" value=""></td></tr>';
	}

	print '</table>';
	print '<center><input type="submit" class="button" value="'.$langs->trans('Save').'"></center>';
	print '</form>';
}
else
{
	if ($_GET['facid'] > 0)
	{
		/* *************************************************************************** */
		/*                                                                             */
		/* Fiche en mode visu ou edition                                               */
		/*                                                                             */
		/* *************************************************************************** */

		$fac = new FactureFournisseur($db);
		$fac->fetch($_GET['facid']);

		$societe = new Fournisseur($db);

		if ( $societe->fetch($fac->socidp) )
		{
			$addons[0][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$fac->socidp;
			$addons[0][1] = $societe->nom;
		}
		llxHeader('','', $addons);

		if ($mesg) { print '<br>'.$mesg.'<br>'; }

		if ($_GET['action'] == 'edit')
		{

			print_titre($langs->trans('Bill').': '.$fac->ref);

			print '<form action="fiche.php?facid='.$fac->id.'" method="post">';
			print '<input type="hidden" name="action" value="update">';

			print '<table class="border" width="100%">';
			print '<tr><td>'.$langs->trans('Company').'</td>';

			print '<td>'.stripslashes($fac->socnom).'</td>';
			print '<td width="50%" valign="top">'.$langs->trans('Comments').'</tr>';

			print '<tr><td valign="top">'.$langs->trans('Ref').'</td><td valign="top">';
			print '<input name="facnumber" type="text" value="'.$fac->ref.'"></td>';

			print '<td rowspan="8" valign="top">';
			print '<textarea name="note" wrap="soft" cols="60" rows="10">';
			print stripslashes($fac->note);
			print '</textarea></td></tr>';

			print '<tr><td valign="top">'.$langs->trans('Label').'</td><td>';
			print '<input size="30" name="libelle" type="text" value="'.stripslashes($fac->libelle).'"></td></tr>';

			print '<tr><td>'.$langs->trans('AmountHT').' / '.$langs->trans('AmountTTC').'</td>';
			print '<td>'.price($fac->total_ht).' / '.price($fac->total_ttc).'</td></tr>';

			print '<tr><td>'.$langs->trans('DateBill').'</td><td>';
			$html->select_date($fac->datep);
			print '</td></tr>';

			print '<tr><td>'.$langs->trans('DateEcheance').'</td><td>';
			$html->select_date($fac->date_echeance,'ech');
			print '</td></tr>';

			$authorfullname='&nbsp;';
			if ($fac->author)
			{
				$author = new User($db, $fac->author);
				$author->fetch('');
				$authorfullname=$author->fullname;
			}
			print '<tr><td>'.$langs->trans('Author').'</td><td>'.$authorfullname.'</td></tr>';
			print '<tr><td>'.$langs->trans('Status').'</td><td>'.$fac->LibStatut($fac->paye,$fac->statut).'</td></tr>';
			print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans('Save').'"></td></tr>';
			print '</table>';
			print '</form>';

			/*
			 * Lignes
			 *
			 */
			print '<br>';
			$var=true;

			print '<form action="fiche.php?facid='.$fac->id.'&amp;action=add_ligne" method="post">';
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><td>'.$langs->trans('Label').'</td>';
			print '<td align="center">'.$langs->trans('PriceUHT').'</td>';
			print '<td align="center">'.$langs->trans('PriceUTTC').'</td>';
			print '<td align="center">'.$langs->trans('Qty').'</td>';
			print '<td align="center">'.$langs->trans('TotalHT').'</td>';
			print '<td align="center">'.$langs->trans('VATRate').'</td>';
			print '<td align="center">'.$langs->trans('VAT').'</td>';
			print '<td align="right">'.$langs->trans('TotalTTC').'</td><td>&nbsp;</td></tr>';
			for ($i = 0 ; $i < sizeof($fac->lignes) ; $i++)
			{
				$var=!$var;
				print '<tr '.$bc[$var].'><td>'.$fac->lignes[$i][0].'</td>';
				print '<td align="center">'.price($fac->lignes[$i][1]).'</td>';
				print '<td align="center">'.price($fac->lignes[$i][1] * (1+($fac->lignes[$i][2]/100))).'</td>';
				print '<td align="center">'.$fac->lignes[$i][3].'</td>';
				print '<td align="center">'.price($fac->lignes[$i][4]).'</td>';
				print '<td align="center">'.$fac->lignes[$i][2].'</td>';
				print '<td align="center">'.price($fac->lignes[$i][5]).'</td>';
				print '<td align="right">'.price($fac->lignes[$i][6]).'</td>';
				print '<td align="center">';
				print '<a href="fiche.php?facid='.$fac->id.'&amp;action=del_ligne&amp;ligne_id='.$fac->lignes[$i][7].'">'.img_delete().'</a></td>';
				print '</tr>';
			}

			/* Nouvelle ligne */
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td>';
			print '<input size="30" name="label" type="text">';
			print '</td>';
			print '<td align="center">';
			print '<input size="8" name="amount" type="text">';
			print '</td>';
			print '<td align="center">';
			print '<input size="8" name="amountttc" type="text">';
			print '</td>';
			print '<td align="center">';
			print '<input size="2" name="qty" type="text" value="1">';
			print '</td>';
			print '<td align="center">-</td>';
			print '<td align="center">';
			$html->select_tva('tauxtva');
			print '</td><td align="center" colspan="2">';
			print '&nbsp;';
			print '</td><td align="center"><input type="submit" class="button" value="'.$langs->trans('Add').'"></td></tr>';
			print '</table>';
			print '</form>';
		}
		else
		{
			/*
			 *
			 */
			$h=0;

			$head[$h][0] = 'fiche.php?facid='.$fac->id;
			$head[$h][1] = $langs->trans('Card');
			$hselected = $h;
			$h++;

			$titre=$langs->trans('SupplierBill').': '.$fac->ref;
			dolibarr_fiche_head($head, $hselected, $titre);


			/*
			 * Confirmation de la validation
			 *
			 */
			if ($_GET['action'] == 'valid')
			{
				$html->form_confirm('fiche.php?facid='.$fac->id, $langs->trans('ValidateBill'), $langs->trans('ConfirmValidateBill', $fac->ref), 'confirm_valid');
				print '<br />';
			}

			print '<table border="0" width="100%">';
			print '<tr><td width="50%" valign="top">';

			/*
			 *   Facture
			 */
			print '<table class="border" width="100%">';
            
            // Ref
            print "<tr><td>".$langs->trans("Ref")."</td><td colspan=\"3\">".$fac->ref."</td>";
            print "</tr>\n";

            // Societe
			print '<tr><td>'.$langs->trans('Company').'</td><td colspan="2"><a href="../fiche.php?socid='.$fac->socidp.'">'.dolibarr_trunc($fac->socnom,24).'</a></td>';
			print '<td align="right"><a href="index.php?socid='.$fac->socidp.'">'.$langs->trans('OtherBills').'</a></td>';
			print '</tr>';

			print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3">';
			print dolibarr_print_date($fac->datep,'%A %d %B %Y').'</td></tr>';
			print '<tr><td>'.$langs->trans('Label').'</td><td colspan="3">';
			print $fac->libelle;
			print '</td>';
			print '</tr>';

			$authorfullname='&nbsp;';
			if ($fac->author)
			{
				$author = new User($db, $fac->author);
				$author->fetch('');
				$authorfullname=$author->fullname;
			}
			print '<tr><td>'.$langs->trans('Author').'</td><td colspan="3">'.$authorfullname.'</td>';
			print '<tr><td>'.$langs->trans('Status').'</td><td colspan="3">'.$fac->LibStatut($fac->paye,$fac->statut).'</td></tr>';

			print '<tr><td>'.$langs->trans('AmountHT').'</td><td><b>'.price($fac->total_ht).'</b></td><td colspan="2" align="left">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($fac->total_tva).'</td><td colspan="2" align="left">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountTTC').'</td><td>'.price($fac->total_ttc).'</td><td colspan="2" align="left">'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
			if (strlen($fac->note))
			{
				print '<tr><td>'.$langs->trans('Comments').'</td><td colspan="3">';
				print nl2br(stripslashes($fac->note));
				print '</td></tr>';
			}
			print '</table>';

			print '</td><td valign="top">';


			print '<table class="border" width="100%">';

			print '<tr>';
			print '<td>'.$langs->trans('DateEcheance').'</td><td>';
			print dolibarr_print_date($fac->date_echeance,'%A %d %B %Y').'</td></tr>';

			/*
			 * Liste des paiements
			 */
			print '<tr><td colspan="2">';
			print $langs->trans('Payments').' :<br>';
			$sql  = 'SELECT '.$db->pdate('datep').' as dp, pf.amount,';
			$sql .= ' c.libelle as paiement_type, p.num_paiement, p.rowid';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn as p';
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id';
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_paiementfourn = p.rowid';
			$sql .= ' WHERE pf.fk_facturefourn = '.$fac->id;
			$sql .= ' ORDER BY dp DESC';

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$i = 0; $totalpaye = 0;
				print '<table class="noborder" width="100%">';
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans('Date').'</td>';
				print '<td>'.$langs->trans('Type').'</td>';

				if ($fac->statut == 1 && $fac->paye == 0 && $user->societe_id == 0)
				{
					$tdsup=' colspan="2"';
				}
				print '<td align="right">'.$langs->trans('AmountTTC').'</td><td'.$tdsup.'>&nbsp;</td></tr>';

				$var=True;
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$var=!$var;
					print '<tr '.$bc[$var].'>';
					print '<td nowrap><a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans('Payment'),'payment').'</a> '.dolibarr_print_date($objp->dp)."</td>\n";
					print '<td>'.$objp->paiement_type.' '.$objp->num_paiement.'</td>';
					print '<td align="right">'.price($objp->amount).'</td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td>';

					if ($fac->statut == 1 && $fac->paye == 0 && $user->societe_id == 0)
					{
						print '<td align="center">';
						print '<a href="fiche.php?facid='.$fac->id.'&amp;action=deletepaiement&amp;paiement_id='.$objp->rowid.'">';
						print img_delete();
						print '</a></td>';
					}

					print '</tr>';
					$totalpaye += $objp->amount;
					$i++;
				}

				if ($fac->paye == 0)
				{
					print '<tr><td colspan="2" align="right">'.$langs->trans('AlreadyPayed').' :</td><td align="right"><b>'.price($totalpaye).'</b></td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
					print '<tr><td colspan="2" align="right">'.$langs->trans("Billed").' :</td><td align="right" style="border: 1px solid;">'.price($fac->total_ttc).'</td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

					$resteapayer = $fac->total_ttc - $totalpaye;

					print '<tr><td colspan="2" align="right">'.$langs->trans('RemainderToPay').' :</td>';
					print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($resteapayer).'</b></td><td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
				}
				print '</table>';
				$db->free($result);
			}
			else
			{
				dolibarr_print_error($db);
			}
			print '</td></tr>';
			print '</table>';



			print '</td></tr>';
			print '</table>';


			/*
			 * Lignes
			 *
			 */
            print '<br>';
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><td>'.$langs->trans('Label').'</td>';
			print '<td align="center">'.$langs->trans('PriceUHT').'</td>';
			print '<td align="center">'.$langs->trans('Qty').'</td>';
			print '<td align="center">'.$langs->trans('TotalHT').'</td>';
			print '<td align="center">'.$langs->trans('VATRate').'</td>';
			print '<td align="center">'.$langs->trans('VAT').'</td>';
			print '<td align="right">'.$langs->trans('TotalTTC').'</td></tr>';
			$var=1;
			for ($i = 0 ; $i < sizeof($fac->lignes) ; $i++)
			{
				$var=!$var;
				print '<tr '.$bc[$var].'><td>'.$fac->lignes[$i][0].'</td>';
				print '<td align="center">'.price($fac->lignes[$i][1]).'</td>';
				print '<td align="center">'.$fac->lignes[$i][3].'</td>';
				print '<td align="center">'.price($fac->lignes[$i][4]).'</td>';
				print '<td align="center">'.$fac->lignes[$i][2].' %</td>';
				print '<td align="center">'.price($fac->lignes[$i][5]).'</td>';
				print '<td align="right">'.price($fac->lignes[$i][6]).'</td>';
				print '</tr>';
			}
			print '</table>';
			print '</div>';
		}


		/*
		 * Boutons actions
		 */

		print '<div class="tabsAction">';

		if ($fac->statut == 0 && $user->societe_id == 0)
		{
			if ($_GET['action'] == 'edit')
			{
				print '<a class="butAction" href="fiche.php?facid='.$fac->id.'">'.$langs->trans('Cancel').'</a>';
			}
			else
			{
				print '<a class="butAction" href="fiche.php?facid='.$fac->id.'&amp;action=edit">'.$langs->trans('Edit').'</a>';
			}
		}

		if ($fac->statut == 1 && $fac->paye == 0  && $user->societe_id == 0)
		{
			print '<a class="butAction" href="paiement.php?facid='.$fac->id.'&amp;action=create">'.$langs->trans('DoPaiement').'</a>';
		}

		if ($fac->statut == 1 && price($resteapayer) <= 0 && $fac->paye == 0  && $user->societe_id == 0)
		{
			print '<a class="butAction" href="fiche.php?facid='.$fac->id.'&amp;action=payed">'.$langs->trans('ClassifyPayed').'</a>';
		}

		if ($fac->statut == 0 && $user->rights->fournisseur->facture->valider)
		{
			if ($_GET['action'] <> 'edit')
			print '<a class="butAction" href="fiche.php?facid='.$fac->id.'&amp;action=valid">'.$langs->trans('Valid').'</a>';
		}
		else
			if ($user->rights->fournisseur->facture->creer)
			{
				print '<a class="butAction" href="fiche.php?facid='.$fac->id.'&amp;action=copy&amp;socid='.$fac->socidp.'">'.$langs->trans('Copy').'</a>';
			}

		if ($_GET['action'] != 'edit' && $fac->statut == 0 && $user->rights->fournisseur->facture->creer)
		{
			print '<a class="butActionDelete" href="index.php?facid='.$fac->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}
		print '</div>';
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
