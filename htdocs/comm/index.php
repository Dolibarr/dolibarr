<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/comm/index.php
 *	\ingroup    commercial
 *	\brief      Home page of commercial area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
if (! empty($conf->contrat->enabled)) require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (! empty($conf->propal->enabled))  require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

if (! $user->rights->societe->lire) accessforbidden();

$langs->load("commercial");

$action=GETPOST('action', 'alpha');
$bid=GETPOST('bid', 'int');

// Securite acces client
$socid=GETPOST('socid','int');
if (isset($user->societe_id) && $user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}

$max=5;
$now=dol_now();

/*
 * Actions
 */


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$companystatic=new Societe($db);
if (! empty($conf->propal->enabled)) $propalstatic=new Propal($db);

llxHeader();

print_fiche_titre($langs->trans("CommercialArea"));

print '<div class="fichecenter"><div class="fichethirdleft">';

// Search proposal
if (! empty($conf->propal->enabled) && $user->rights->propal->lire)
{
	$var=false;
	print '<form method="post" action="'.DOL_URL_ROOT.'/comm/propal/list.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder nohover" width="100%">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAProposal").'</td></tr>';
	print '<tr '.$bc[$var].'>';
	print '<td class="nowrap"><label for="sf_ref">'.$langs->trans("Ref").'</label>:</td><td><input type="text" class="flat" name="sf_ref" id="sf_ref" size="18"></td>';
	print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print '<tr '.$bc[$var].'><td class="nowrap"><label for="sall">'.$langs->trans("Other").'</label>:</td><td><input type="text" class="flat" name="sall" id="sall" size="18"></td>';
	print '</tr>';
	print "</table></form>\n";
	print "<br>\n";
}

// Search customer order
if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
{
	$var=false;
	print '<table class="noborder nohover" width="100%">';
	print '<form method="post" action="'.DOL_URL_ROOT.'/commande/list.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchACustomerOrder").'</td></tr>';
	print '<tr '.$bc[$var].'><td>';
	print '<label for="sref">'.$langs->trans("Ref").'</label>:</td><td><input type="text" class="flat" name="sref" id="sref" size=18></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print '<tr '.$bc[$var].'><td class="nowrap"><label for="sall">'.$langs->trans("Other").'</label>:</td><td><input type="text" class="flat" name="sall" id="sall" size="18"></td>';
	print '</tr>';
	print "</form></table><br>\n";
}

// Search contract
if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire)
{
	$var=false;
	print '<form method="post" action="'.DOL_URL_ROOT.'/contrat/list.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder nohover" width="100%">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAContract").'</td></tr>';
	print '<tr '.$bc[$var].'>';
	print '<td class="nowrap"><label for="search_contract">'.$langs->trans("Ref").'</label>:</td><td><input type="text" class="flat" name="search_contract" id="search_contract" size="18"></td>';
	print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print '<tr '.$bc[$var].'><td class="nowrap"><label for="sall">'.$langs->trans("Other").'</label>:</td><td><input type="text" class="flat" name="sall" id="sall" size="18"></td>';
	print '</tr>';
	print "</table></form>\n";
	print "<br>";
}

// Search contract
if (! empty($conf->ficheinter->enabled) && $user->rights->ficheinter->lire)
{
	$var=false;
	print '<form method="post" action="'.DOL_URL_ROOT.'/fichinter/list.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder nohover" width="100%">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAnIntervention").'</td></tr>';
	print '<tr '.$bc[$var].'>';
	print '<td class="nowrap"><label for="search_contract">'.$langs->trans("Ref").'</label>:</td><td><input type="text" class="flat" name="search_inter" id="search_inter" size="18"></td>';
	print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print '<tr '.$bc[$var].'><td class="nowrap"><label for="sall">'.$langs->trans("Other").'</label>:</td><td><input type="text" class="flat" name="sall" id="sall" size="18"></td>';
	print '</tr>';
	print "</table></form>\n";
	print "<br>";
}

/*
 * Draft proposals
 */
if (! empty($conf->propal->enabled) && $user->rights->propal->lire)
{
	$sql = "SELECT p.rowid, p.ref, p.total_ht, s.rowid as socid, s.nom as name, s.client, s.canvas";
	$sql.= " FROM ".MAIN_DB_PREFIX."propal as p";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE p.fk_statut = 0";
	$sql.= " AND p.fk_soc = s.rowid";
	$sql.= " AND p.entity = ".$conf->entity;
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND s.rowid = ".$socid;

	$resql=$db->query($sql);
	if ($resql)
	{
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("ProposalsDraft").'</td></tr>';

		$total = 0;
		$num = $db->num_rows($resql);
		if ($num > 0)
		{
			$i = 0;
			$var=true;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$var=!$var;
				print '<tr '.$bc[$var].'><td  class="nowrap">';
				$propalstatic->id=$obj->rowid;
				$propalstatic->ref=$obj->ref;
				print $propalstatic->getNomUrl(1);
				print '</td>';
				print '<td class="nowrap">';
				$companystatic->id=$obj->socid;
				$companystatic->name=$obj->name;
				$companystatic->client=$obj->client;
				$companystatic->canvas=$obj->canvas;
				print $companystatic->getNomUrl(1,'customer',16);
				print '</td>';
				print '<td align="right" class="nowrap">'.price($obj->total_ht).'</td></tr>';
				$i++;
				$total += $obj->total_ht;
			}
			if ($total>0)
			{
				$var=!$var;
				print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" align="right">'.price($total)."</td></tr>";
			}
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}


/*
 * Draft orders
 */
if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
{
	$langs->load("orders");

	$sql = "SELECT c.rowid, c.ref, c.total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.fk_statut = 0";
	$sql.= " AND c.entity = ".$conf->entity;
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND c.fk_soc = ".$socid;

	$resql = $db->query($sql);
	if ($resql)
	{
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("DraftOrders").'</td></tr>';

		$total = 0;
		$num = $db->num_rows($resql);
		if ($num)
		{
			$i = 0;
			$var = true;
			while ($i < $num)
			{
				$var=!$var;
				$obj = $db->fetch_object($resql);
				print '<tr '.$bc[$var].'><td class="nowrap"><a href="../commande/card.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref.'</a></td>';
				print '<td class="nowrap">';
				$companystatic->id=$obj->socid;
				$companystatic->name=$obj->name;
				$companystatic->client=$obj->client;
                $companystatic->canvas=$obj->canvas;
				print $companystatic->getNomUrl(1,'customer',16);
				print '</td>';
				print '<td align="right" class="nowrap">'.price($obj->total_ttc).'</td></tr>';
				$i++;
				$total += $obj->total_ttc;
			}
			if ($total>0)
			{
				$var=!$var;
				print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" align="right">'.price($total)."</td></tr>";
			}
		}
		print "</table><br>";

		$db->free($resql);
	}
}


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


$NBMAX=3;
$max=3;


/*
 * Last modified customers or prospects
 */
if (! empty($conf->societe->enabled) && $user->rights->societe->lire)
{
	$langs->load("boxes");

	$sql = "SELECT s.rowid, s.nom as name, s.client, s.datec, s.tms, s.canvas";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.client IN (1, 2, 3)";
	$sql.= " AND s.entity IN (".getEntity($companystatic->element, 1).")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND s.rowid = $socid";
	$sql .= " ORDER BY s.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$var=false;
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="2">'.$langs->trans("BoxTitleLastCustomersOrProspects",$max).'</td>';
		print '<td align="right">'.$langs->trans("DateModificationShort").'</td>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				$companystatic->id=$objp->rowid;
				$companystatic->name=$objp->name;
				$companystatic->client=$objp->client;
                $companystatic->canvas=$objp->canvas;
				print '<tr '.$bc[$var].'>';
				print '<td class="nowrap">'.$companystatic->getNomUrl(1,'customer',48).'</td>';
				print '<td align="right" nowrap>';
				print $companystatic->getLibCustProspStatut();
				print "</td>";
				print '<td align="right" nowrap>'.dol_print_date($db->jdate($objp->tms),'day')."</td>";
				print '</tr>';
				$i++;
				$var=!$var;

			}

			$db->free($resql);
		}
		else
		{
			print '<tr '.$bc[$var].'><td colspan="3">'.$langs->trans("None").'</td></tr>';
		}
		print "</table><br>";
	}
}

// Last suppliers
if (! empty($conf->fournisseur->enabled) && $user->rights->societe->lire)
{
	$langs->load("boxes");

	$sql = "SELECT s.nom as name, s.rowid, s.datec as dc, s.canvas, s.tms as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.fournisseur = 1";
	$sql.= " AND s.entity IN (".getEntity($companystatic->element, 1).")";
	if (! $user->rights->societe->client->voir && ! $user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND s.rowid = ".$socid;
	$sql.= " ORDER BY s.datec DESC";
	$sql.= $db->plimit($max, 0);

	$result = $db->query($sql);
	if ($result)
	{
		$var=false;
		$num = $db->num_rows($result);
		$i = 0;

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td>'.$langs->trans("BoxTitleLastModifiedSuppliers",min($max,$num)).'</td>';
		print '<td align="right">'.$langs->trans("DateModificationShort").'</td>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num && $i < $max)
			{
				$objp = $db->fetch_object($result);
				$companystatic->id=$objp->rowid;
                $companystatic->name=$objp->name;
                $companystatic->canvas=$objp->canvas;
                print '<tr '.$bc[$var].'>';
				print '<td class="nowrap">'.$companystatic->getNomUrl(1,'supplier',44).'</td>';
				print '<td align="right">'.dol_print_date($db->jdate($objp->dm),'day').'</td>';
				print '</tr>';
				$var=!$var;
				$i++;
			}

		}
		else
		{
			print '<tr '.$bc[$var].'><td colspan="2">'.$langs->trans("None").'</td></tr>';
		}
		print '</table><br>';
	}
}


/*
 * Last actions
 */
if ($user->rights->agenda->myactions->read)
{
	show_array_last_actions_done($max);
}


/*
 * Actions to do
 */
if ($user->rights->agenda->myactions->read)
{
	show_array_actions_to_do(10);
}


/*
 * Last contracts
 */
if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire && 0) // TODO A REFAIRE DEPUIS NOUVEAU CONTRAT
{
	$langs->load("contracts");

	$sql = "SELECT s.nom as name, s.rowid, s.canvas, ";
	$sql.= " c.statut, c.rowid as contratid, p.ref, c.mise_en_service as datemes, c.fin_validite as datefin, c.date_cloture as dateclo";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= ", ".MAIN_DB_PREFIX."contrat as c";
	$sql.= ", ".MAIN_DB_PREFIX."product as p";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.entity = ".$conf->entity;
	$sql.= " AND c.fk_product = p.rowid";
	if (! $user->rights->societe->client->voir && ! $socid)	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND s.rowid = ".$socid;
	$sql.= " ORDER BY c.tms DESC";
	$sql.= $db->plimit(5, 0);

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		if ($num > 0)
		{
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("LastContracts",5).'</td></tr>';
			$i = 0;

			$staticcontrat=new Contrat($db);

			$var=false;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print "<tr ".$bc[$var]."><td><a href=\"../contrat/card.php?id=".$obj->contratid."\">".img_object($langs->trans("ShowContract","contract"), 'contract')." ".$obj->ref."</a></td>";
				print '<td>';
                $companystatic->id=$objp->rowid;
                $companystatic->name=$objp->name;
                $companystatic->canvas=$objp->canvas;
                print $companystatic->getNomUrl(1,'customer',44);
				print '</td>'."\n";
				print "<td align=\"right\">".$staticcontrat->LibStatut($obj->statut,3)."</td></tr>\n";
				$var=!$var;
				$i++;
			}
			print "</table><br>";
		}
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * Opened proposals
 */
if (! empty($conf->propal->enabled) && $user->rights->propal->lire)
{
	$langs->load("propal");

	$sql = "SELECT s.nom as name, s.rowid, p.rowid as propalid, p.total as total_ttc, p.total_ht, p.ref, p.fk_statut, p.datep as dp, p.fin_validite as dfv";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= ", ".MAIN_DB_PREFIX."propal as p";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE p.fk_soc = s.rowid";
	$sql.= " AND p.entity = ".$conf->entity;
	$sql.= " AND p.fk_statut = 1";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND s.rowid = ".$socid;
	$sql.= " ORDER BY p.rowid DESC";

	$result=$db->query($sql);
	if ($result)
	{
		$total = 0;
		$num = $db->num_rows($result);
		$i = 0;
		if ($num > 0)
		{
			$var=true;

			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><td colspan="5">'.$langs->trans("ProposalsOpened").' <a href="'.DOL_URL_ROOT.'/comm/propal/list.php?viewstatut=1">('.$num.')</td></tr>';

			$nbofloop=min($num, (empty($conf->global->MAIN_MAXLIST_OVERLOAD)?500:$conf->global->MAIN_MAXLIST_OVERLOAD));
			while ($i < $nbofloop)
			{
				$obj = $db->fetch_object($result);
				$var=!$var;
				print '<tr '.$bc[$var].'>';

				// Ref
				print '<td class="nowrap" width="140">';

				$propalstatic->id=$obj->propalid;
				$propalstatic->ref=$obj->ref;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td class="nobordernopadding nowrap">';
				print $propalstatic->getNomUrl(1);
				print '</td>';
				print '<td width="18" class="nobordernopadding nowrap">';
				if ($db->jdate($obj->dfv) < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
				print '</td>';
				print '<td width="16" align="center" class="nobordernopadding">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->propal->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->propalid;
				print $formfile->getDocumentsLink($propalstatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print "</td>";

				print '<td align="left"><a href="card.php?socid='.$obj->rowid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($obj->name,44).'</a></td>'."\n";
				print '<td align="right">';
				print dol_print_date($db->jdate($obj->dp),'day').'</td>'."\n";
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '<td align="center" width="14">'.$propalstatic->LibStatut($obj->fk_statut,3).'</td>'."\n";
				print '</tr>'."\n";
				$i++;
				$total += $obj->total_ttc;
			}
			if ($num > $nbofloop)
			{
				print '<tr class="liste_total"><td colspan="5">'.$langs->trans("XMoreLines", ($num - $nbofloop))."</td></tr>";
			}
			else if ($total>0)
			{
				print '<tr class="liste_total"><td colspan="3">'.$langs->trans("Total")."</td><td align=\"right\">".price($total)."</td><td>&nbsp;</td></tr>";
			}
			print "</table><br>";
		}
	}
	else
	{
		dol_print_error($db);
	}
}


print '</div></div></div>';

llxFooter();

$db->close();
