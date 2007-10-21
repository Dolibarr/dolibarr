<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/fourn/fiche.php
        \ingroup    fournisseur, facture
        \brief      Page de fiche fournisseur
        \version    $Revision$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

$langs->load('suppliers');
$langs->load('products');
$langs->load('bills');
$langs->load('orders');
$langs->load('companies');
$langs->load('commercial');

// Sécurité accés client
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}
if (! $socid) accessforbidden();



/*
 *  Actions
 */
 
// Protection restriction commercial
if (!$user->rights->commercial->client->voir && $socid && !$user->societe_id > 0)
{
  $sql = "SELECT sc.rowid";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."societe as s";
  $sql .= " WHERE sc.fk_soc = ".$socid." AND sc.fk_soc = s.rowid AND sc.fk_user = ".$user->id." AND s.fournisseur = 1";
  
  if ( $db->query($sql) )
    {
      if ( $db->num_rows() == 0) accessforbidden();
    }
}


/*
 * Mode fiche
 */  
$societe = new Fournisseur($db);

if ( $societe->fetch($socid) )
{
  $addons[0][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$socid;
  $addons[0][1] = $societe->nom;
  
  llxHeader('',$langs->trans('SupplierCard').' : '.$societe->nom, $addons);
  
  /*
   * Affichage onglets
   */
  $head = societe_prepare_head($societe);
  
  dolibarr_fiche_head($head, 'supplier', $societe->nom);
  
  
  print '<table width="100%" class="notopnoleftnoright">';
  print '<tr><td valign="top" width="50%" class="notopnoleft">';
  
  print '<table width="100%" class="border">';
  print '<tr><td width="20%">'.$langs->trans("Name").'</td><td width="80%" colspan="3">'.$societe->nom.'</td></tr>';
  
  print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$societe->prefix_comm.'</td></tr>';
  
  if ($societe->fournisseur)
    {
      print '<tr><td nowrap="nowrap">';
      print $langs->trans('SupplierCode').'</td><td colspan="3">';
      print $societe->code_fournisseur;
      if ($societe->check_codefournisseur() <> 0) print ' '.$langs->trans("WrongSupplierCode");
      print '</td></tr>';
    }
  
  print '<tr><td valign="top">'.$langs->trans("Address").'</td><td colspan="3">'.nl2br($societe->adresse).'</td></tr>';
  
  print '<tr><td>'.$langs->trans("Zip").'</td><td>'.$societe->cp.'</td>';
  print '<td>'.$langs->trans("Town").'</td><td>'.$societe->ville.'</td></tr>';
  print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">'.$societe->pays.'</td></tr>';
  print '<tr><td>'.$langs->trans("Phone").'</td><td>'.dolibarr_print_phone($societe->tel).'&nbsp;</td><td>'.$langs->trans("Fax").'</td><td>'.dolibarr_print_phone($societe->fax).'&nbsp;</td></tr>';
  print '<tr><td>'.$langs->trans("Web")."</td><td colspan=\"3\"><a href=\"http://$societe->url\">$societe->url</a>&nbsp;</td></tr>";
  
  // Assujeti à TVA ou pas
  print '<tr>';
  print '<td nowrap="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
  print yn($societe->tva_assuj);
  print '</td>';
  print '</tr>';
  
  print '</table>';
  
  
  print '</td><td valign="top" width="50%" class="notopnoleftnoright">';
  $var=true;
  
  $MAXLIST=5;
  
  // Lien recap
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre">';
  print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("Summary").'</td>';
  print '<td align="right"><a href="'.DOL_URL_ROOT.'/fourn/recap-fourn.php?socid='.$societe->id.'">'.$langs->trans("ShowSupplierPreview").'</a></td></tr></table></td>';
  print '</tr>';
  print '</table>';
  print '<br>';
  
  /*
   * Liste des commandes associées
   */
  $orderstatic = new CommandeFournisseur($db);
  
  $sql  = "SELECT p.rowid,p.ref,".$db->pdate("p.date_commande")." as dc, p.fk_statut";
  $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as p ";
	$sql.= " WHERE p.fk_soc =".$societe->id;
	$sql.= " ORDER BY p.date_commande DESC";
	$sql.= " ".$db->plimit($MAXLIST);
	$resql=$db->query($sql);
	if ($resql)
	{
		$i = 0 ;
		$num = $db->num_rows($resql);
		if ($num > 0)
		{
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td colspan="3">';
			print '<table class="noborder" width="100%"><tr><td>'.$langs->trans("LastOrders",($num<$MAXLIST?$num:$MAXLIST)).'</td>';
			print '<td align="right"><a href="commande/liste.php?socid='.$societe->id.'">'.$langs->trans("AllOrders").' ('.$num.')</td></tr></table>';
			print '</td></tr>';
		}
		while ($i < $num && $i <= $MAXLIST)
		{
			$obj = $db->fetch_object($resql);
			$var=!$var;

			print "<tr $bc[$var]>";
			print '<td><a href="commande/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowOrder"),"order")." ".$obj->ref.'</a></td>';
			print '<td align="center" width="80">';
			if ($obj->dc)
			{
				print dolibarr_print_date($obj->dc);
			}
			else
			{
				print "-";
			}
			print '</td>';
			print '<td align="right" nowrap="nowrap">'.$orderstatic->LibStatut($obj->fk_statut,5).'</td>';
			print '</tr>';
			$i++;
		}
		$db->free($resql);
		if ($num > 0)
		{
			print "</table><br>";
		}
	}
	else
	{
		dolibarr_print_error($db);
	}


	/*
	 * Liste des factures associées
	 */
	$MAXLIST=5;

	$langs->load('bills');
	$facturestatic = new FactureFournisseur($db);

	$sql = 'SELECT p.rowid,p.libelle,p.facnumber,p.fk_statut,'.$db->pdate('p.datef').' as df, total_ttc as amount, paye';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as p';
	$sql.= ' WHERE p.fk_soc = '.$societe->id;
	$sql.= ' ORDER BY p.datef DESC';
	$resql=$db->query($sql);
	if ($resql)
	{
		$i = 0 ;
		$num = $db->num_rows($resql);
		if ($num > 0)
		{
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td colspan="4">';
			print '<table class="noborder" width="100%"><tr><td>'.$langs->trans('LastSuppliersBills',($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="facture/index.php?socid='.$societe->id.'">'.$langs->trans('AllBills').' ('.$num.')</td></tr></table>';
			print '</td></tr>';
		}
		while ($i < min($num,$MAXLIST))
		{
			$obj = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td>';
			print '<a href="facture/fiche.php?facid='.$obj->rowid.'">';
			print img_object($langs->trans('ShowBill'),'bill').' '.$obj->facnumber.'</a> '.dolibarr_trunc($obj->libelle,14).'</td>';
			print '<td align="center" nowrap="nowrap">'.dolibarr_print_date($obj->df).'</td>';
			print '<td align="right" nowrap="nowrap">'.price($obj->amount).'</td>';
			print '<td align="right" nowrap="nowrap">'.$facturestatic->LibStatut($obj->paye,$obj->fk_statut,5).'</td>';
			print '</tr>';
			$i++;
		}
		$db->free($resql);
		if ($num > 0)
		{
			print '</table><br>';
		}
	}
	else
	{
		dolibarr_print_error($db);
	}

	/*
	 * Liste des produits
	 */
	if ($conf->produit->enabled || $conf->service->enabled)
	{
		$langs->load("products");
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("ProductsAndServices").'</td><td align="right">';
		print '<a href="'.DOL_URL_ROOT.'/fourn/product/liste.php?fourn_id='.$societe->id.'">'.$langs->trans("All").' ('.$societe->NbProduct().')';
		print '</a></td></tr></table>';
	}

	print '</td></tr>';
	print '</table>' . "\n";
	print '</div>';


	/*
	 *
	 * Barre d'actions
	 *
	 */

	print '<div class="tabsAction">';

	if ($user->rights->fournisseur->commande->creer)
	{
		$langs->load("orders");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?action=create&socid='.$societe->id.'">'.$langs->trans("AddOrder").'</a>';
	}

	if ($user->rights->fournisseur->facture->creer)
	{
		$langs->load("bills");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?action=create&socid='.$societe->id.'">'.$langs->trans("AddBill").'</a>';
	}

	print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$societe->id.'">'.$langs->trans("AddAction").'</a>';

	if ($user->rights->societe->contact->creer)
	{
		print "<a class=\"butAction\" href=\"".DOL_URL_ROOT.'/contact/fiche.php?socid='.$socid."&amp;action=create\">".$langs->trans("AddContact")."</a>";
	}

	print '</div>';


	/*
	 *
	 * Liste des contacts
	 *
	 */
	$langs->load("companies");

	print '<br>';
	
	print_titre($langs->trans("ContactsForCompany"));
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre"><td>'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Poste").'</td><td>'.$langs->trans("Tel").'</td>';
	print "<td>".$langs->trans("Fax")."</td><td>".$langs->trans("EMail")."</td>";
	print "<td align=\"center\">&nbsp;</td>";
	print '<td>&nbsp;</td>';
	print "</tr>";

	$sql = "SELECT p.rowid, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note";
	$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as p";
	$sql.= " WHERE p.fk_soc = ".$societe->id;
	$sql.= "  ORDER by p.datec";

	$result = $db->query($sql);

	$i = 0 ;
	$num = $db->num_rows($result);
	$var=true;

	while ($i < $num)
	{
		$obj = $db->fetch_object($result);
		$var = !$var;

		print "<tr $bc[$var]>";

		print '<td>';
		print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->rowid.'">';
		print img_object($langs->trans("ShowContact"),"contact");
		print ' '.$obj->firstname.' '. $obj->name.'</a>&nbsp;';

		if ($obj->note)
		{
			print "<br>".nl2br($obj->note);
		}
		print "</td>";
		print "<td>$obj->poste&nbsp;</td>";
		print '<td><a href="../comm/action/fiche.php?action=create&actioncode=AC_TEL&contactid='.$obj->rowid.'&socid='.$societe->id.'">'.$obj->phone.'</a>&nbsp;</td>';
		print '<td><a href="../comm/action/fiche.php?action=create&actioncode=AC_FAX&contactid='.$obj->rowid.'&socid='.$societe->id.'">'.$obj->fax.'</a>&nbsp;</td>';
		print '<td><a href="../comm/action/fiche.php?action=create&actioncode=AC_EMAIL&contactid='.$obj->rowid.'&socid='.$societe->id.'">'.$obj->email.'</a>&nbsp;</td>';

		if ($user->rights->societe->contact->creer)
		{
			print "<td align=\"center\"><a href=\"../contact/fiche.php?action=edit&amp;id=".$obj->rowid."\">".img_edit()."</a></td>";
		}

		print '<td align="center"><a href="../comm/action/fiche.php?action=create&actionid=5&contactid='.$obj->rowid.'&socid='.$societe->id.'">';
		print img_object($langs->trans("Rendez-Vous"),"action");
		print '</a></td>';

		print "</tr>\n";
		$i++;
	}
	print '</table>';
	print '<br>';

	/*
	 *      Listes des actions a faire
	 *
	 */
	print_titre($langs->trans("ActionsOnCompany"));
	print '<table width="100%" class="noborder">';
	print '<tr class="liste_titre"><td colspan="10"><a href="'.DOL_URL_ROOT.'/comm/action/index.php?socid='.$societe->id.'&amp;status=todo">'.$langs->trans("ActionsToDoShort").'</a></td><td align="right">&nbsp;</td></tr>';

	$sql = "SELECT a.id, a.label, ".$db->pdate("a.datep")." as dp, c.code as acode, c.libelle, u.login, a.propalrowid, a.fk_user_author, fk_contact, u.rowid ";
	$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u ";
	$sql .= " WHERE a.fk_soc = ".$societe->id;
	$sql .= " AND u.rowid = a.fk_user_author";
	$sql .= " AND c.id=a.fk_action AND a.percent < 100";
	$sql .= " ORDER BY a.datep DESC, a.id DESC";

	$result=$db->query($sql);
	if ($result)
	{
		$i = 0 ;
		$num = $db->num_rows($result);
		$var=true;

		while ($i < $num)
		{
			$var = !$var;

			$obj = $db->fetch_object($result);
			print "<tr $bc[$var]>";

			if ($oldyear == strftime("%Y",$obj->dp) )
			{
				print '<td width="30" align="center">|</td>';
			}
			else
			{
				print '<td width="30" align="center">'.strftime("%Y",$obj->dp)."</td>\n";
				$oldyear = strftime("%Y",$obj->dp);
			}

			if ($oldmonth == strftime("%Y%b",$obj->dp) )
			{
				print '<td width="30" align="center">|</td>';
			}
			else
			{
				print '<td width="30" align="center">' .strftime("%b",$obj->dp)."</td>\n";
				$oldmonth = strftime("%Y%b",$obj->dp);
			}

			print '<td width="20">'.strftime("%d",$obj->dp)."</td>\n";
			print '<td width="30" nowrap="nowrap">'.strftime("%H:%M",$obj->dp).'</td>';

			// Picto warning
			print '<td width="16">';
			if (date("U",$obj->dp) < time()) print ' '.img_warning("Late");
			else print '&nbsp;';
			print '</td>';

			// Status/Percent
			print '<td width="30">&nbsp;</td>';

			if ($obj->propalrowid)
			{
				print '<td><a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$obj->propalrowid.'">'.img_object($langs->trans("ShowAction"),"task");
				$transcode=$langs->trans("Action".$obj->acode);
				$libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
				print $libelle;
				print '</a></td>';
			}
			else
			{
				print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$obj->id.'">'.img_object($langs->trans("ShowAction"),"task");
				$transcode=$langs->trans("Action".$obj->acode);
				$libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
				print $libelle;
				print '</a></td>';
			}

			print '<td colspan="2">'.$obj->label.'</td>';

			// Contact pour cette action
			if ($obj->fk_contact)
			{
				$contact = new Contact($db);
				$contact->fetch($obj->fk_contact);
				print '<td><a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->fk_contact.'">'.img_object($langs->trans("ShowContact"),"contact").' '.$contact->getFullName($langs).'</a></td>';
			} else {
				print '<td>&nbsp;</td>';
			}

			// Auteur
			print '<td width="50" nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->fk_user_author.'">'.img_object($langs->trans("ShowUser"),"user").' '.$obj->login.'</a></td>';
			print "</tr>\n";
			$i++;
		}

		$db->free($result);
	} else {
		dolibarr_print_error($db);
	}
	print "</table><br>";

	/*
	 *      Listes des actions effectuées
	 */
	print '<table width="100%" class="noborder">';
	print '<tr class="liste_titre"><td colspan="11"><a href="'.DOL_URL_ROOT.'/comm/action/index.php?socid='.$societe->id.'&amp;status=done">'.$langs->trans("ActionsDoneShort").'</a></td></tr>';

	$sql = "SELECT a.id, a.label, ".$db->pdate("a.datea")." as da,";
	$sql.= " a.propalrowid, a.fk_facture, a.fk_user_author, a.fk_contact,";
	$sql.= " c.code as acode, c.libelle,";
	$sql.= " u.login, u.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u ";
	$sql.= " WHERE a.fk_soc = ".$societe->id;
	$sql.= " AND u.rowid = a.fk_user_author";
	$sql.= " AND c.id=a.fk_action AND a.percent = 100";
	$sql.= " ORDER BY a.datea DESC, a.id DESC";

	$result=$db->query($sql);
	if ($result)
	{
		$i = 0 ;
		$num = $db->num_rows($result);
		$var=true;

		while ($i < $num)
		{
			$var = !$var;

			$obj = $db->fetch_object($result);
			print "<tr $bc[$var]>";

			if ($oldyear == strftime("%Y",$obj->da) )
			{
				print '<td width="30" align="center">|</td>';
			} else {
				print '<td width="30" align="center">'.strftime("%Y",$obj->da)."</td>\n";
				$oldyear = strftime("%Y",$obj->da);
			}

			if ($oldmonth == strftime("%Y%b",$obj->da) )
			{
				print '<td width="30" align="center">|</td>';
			} else {
				print '<td width="30" align="center">'.strftime("%b",$obj->da)."</td>\n";
				$oldmonth = strftime("%Y%b",$obj->da);
			}

			print '<td width="20">'.strftime("%d",$obj->da)."</td>\n";
			print '<td width="30">'.strftime("%H:%M",$obj->da)."</td>\n";

			// Picto
			print '<td width="16">&nbsp;</td>';

			// Espace
			print '<td width="30">&nbsp;</td>';

			// Action
			print '<td>';
			print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$obj->id.'">'.img_object($langs->trans("ShowTask"),"task");
			$transcode=$langs->trans("Action".$obj->acode);
			$libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
			print $libelle;
			print '</a>';
			print '</td>';

			// Objet li
			print '<td>';
			if ($obj->propalrowid)
			{
				print '<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$obj->propalrowid.'">'.img_object($langs->trans("ShowPropal"),"propal");
				print $langs->trans("Propal");
				print '</a>';
			}
			if ($obj->fk_facture)
			{
				print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->fk_facture.'">'.img_object($langs->trans("ShowBill"),"bill");
				print $langs->trans("Invoice");
				print '</a>';
			}
			else print '&nbsp;';
			print '</td>';

			// Libell
			print "<td>$obj->label</td>";

			// Contact pour cette action
			if ($obj->fk_contact)
			{
				$contact = new Contact($db);
				$contact->fetch($obj->fk_contact);
				print '<td><a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$contact->id.'">'.img_object($langs->trans("ShowContact"),"contact").' '.$contact->getFullName($langs).'</a></td>';
			}
			else
			{
				print '<td>&nbsp;</td>';
			}

			// Auteur
			print '<td nowrap="nowrap" width="50"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';
			print "</tr>\n";
			$i++;
		}
		$db->free();
	}
	else
	{
		dolibarr_print_error($db);
	}


	print "</table>";

}
else
{
	dolibarr_print_error($db);
}
$db->close();

llxFooter('$Date$ - $Revision$');
?>
