<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Destailleur Laurent  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
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
   \file       htdocs/compta/propal.php
   \ingroup    propale
   \brief      Page liste des propales (vision compta)
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/propal.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/project.class.php');
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');

$langs->load('companies');
$langs->load('compta');
$langs->load('orders');

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


// Sécurité accés client
$user->getrights('facture');
$user->getrights('propale');
if (!$user->rights->propale->lire) accessforbidden();
$socid='';
if ($_GET["socid"]) { $socid=$_GET["socid"]; }
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}
if ($_GET['propalid'] > 0)
{
  $propal = new Propal($db);
  $result=$propal->fetch($_GET['propalid']);
  if (! $result > 0)
    {
      dolibarr_print_error($db,$propal->error);
      exit;
    }
  if ($user->societe_id > 0 && $propal->socid <> $user->societe_id)
    accessforbidden();
}



/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_GET["action"] == 'setstatut')
{
  /*
   *  Classée la facture comme facturée
   */
  $propal = new Propal($db);
  $propal->id = $_GET["propalid"];
  $propal->cloture($user, $_GET["statut"], $note);

}

if ( $action == 'delete' )
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."propal WHERE rowid = $propalid;";
	if ( $db->query($sql) )
	{
	
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal = $propalid ;";
		if ( $db->query($sql) )
		{
			print '<div class="ok">'.$langs->trans("Deleted").'</div>';
		}
		else
		{
			dolibarr_print_error($db);
		}
	}
	else
	{
		dolibarr_print_error($db);
	}
	$propalid = 0;
	$brouillon = 1;
}



llxHeader();

$html = new Form($db);
$societestatic=new Societe($db);
$propalstatic=new Propal($db);

/*
 *
 * Mode fiche
 *
 */
if ($_GET["propalid"] > 0)
{
  if ($mesg) print "$mesg<br>";

  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);
  
  $societe = new Societe($db);
  $societe->fetch($propal->socid);

  $head = propal_prepare_head($propal);
  dolibarr_fiche_head($head, 'compta', $langs->trans('Proposal'));
  
  
  /*
   * Fiche propal
   *
   */
  print '<table class="border" width="100%">';

  // Ref
  print '<tr><td>'.$langs->trans('Ref').'</td><td colspan="5">'.$propal->ref_url.'</td></tr>';

  // Ref client
  print '<tr><td>';
  print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
  print $langs->trans('RefCustomer').'</td><td align="left">';
  print '</td>';
  if ($_GET['action'] != 'refclient' && $propal->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refclient&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('Edit')).'</a></td>';
  print '</tr></table>';
  print '</td><td colspan="5">';
  print $propal->ref_client;
  print '</td>';
  print '</tr>';
	
  $rowspan=8;
    
    // Société
    print '<tr><td>'.$langs->trans('Company').'</td><td colspan="5">'.$societe->getNomUrl(1).'</td></tr>';

	// Ligne info remises tiers
    print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
	if ($societe->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$societe->remise_client);
	else print $langs->trans("CompanyHasNoRelativeDiscount");
	$absolute_discount=$societe->getCurrentDiscount();
	print '. ';
	if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->monnaie));
	else print $langs->trans("CompanyHasNoAbsoluteDiscount");
	print '.';
	print '</td></tr>';

    // Dates
    print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3">';
    print dolibarr_print_date($propal->date,'%a %d %B %Y');
    print '</td>';

    if ($conf->projet->enabled) $rowspan++;

	// Note
    print '<td valign="top" colspan="2" width="50%" rowspan="'.$rowspan.'">'.$langs->trans('NotePublic').' :<br>'. nl2br($propal->note_public).'</td>';
    print '</tr>';

	// Date fin propal
	print '<tr>';
    print '<td>'.$langs->trans('DateEndPropal').'</td><td colspan="3">';
    if ($propal->fin_validite)
    {
        print dolibarr_print_date($propal->fin_validite,'%a %d %B %Y');
        if ($propal->statut == 1 && $propal->fin_validite < (time() - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
    }
    else
    {
        print $langs->trans("Unknown");
    }
    print '</td>';
    print '</tr>';

	// Conditions et modes de réglement
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentConditionsShort');
	print '</td>';
	if ($_GET['action'] != 'editconditions' && $propal->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editconditions')
	{
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->cond_reglement_id,'cond_reglement_id');
	}
	else
	{
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->cond_reglement_id,'none');
	}
	print '</td>';

	// Mode de paiement
	print '<tr>';
	print '<td width="25%">';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($_GET['action'] != 'editmode' && $propal->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editmode')
	{
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->mode_reglement_id,'mode_reglement_id');
	}
	else
	{
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->mode_reglement_id,'none');
	}
	print '</td></tr>';

    // Projet
    if ($conf->projet->enabled)
    {
        $langs->load("projects");
        print '<tr><td>'.$langs->trans('Project').'</td>';
        $numprojet = $societe->has_projects();
        if (! $numprojet)
        {
            print '<td colspan="2">';
            print $langs->trans("NoProject").'</td><td>';
            print '<a href=../projet/fiche.php?socid='.$societe->id.'&action=create>'.$langs->trans('AddProject').'</a>';
            print '</td>';
        }
        else
        {
            if ($propal->statut == 0 && $user->rights->propale->creer)
            {
                print '<td colspan="3">';
                $html->select_projects($societe->id, $propal->projetidp, 'projetidp');
                print '</td>';
            }
            else
            {
                if (!empty($propal->projetidp))
                {
                    print '<td colspan="3">';
                    $proj = new Project($db);
                    $proj->fetch($propal->projetidp);
                    print '<a href="../projet/fiche.php?id='.$propal->projetidp.'" title="'.$langs->trans('ShowProject').'">';
                    print $proj->title;
                    print '</a>';
                    print '</td>';
                }
                else {
                    print '<td colspan="3">&nbsp;</td>';
                }
            }
        }
        print '</tr>';
    }

    // Amount
    print '<tr><td height="10">'.$langs->trans('AmountHT').'</td>';
    print '<td align="right" colspan="2"><b>'.price($propal->price).'</b></td>';
    print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

    print '<tr><td height="10">'.$langs->trans('AmountVAT').'</td><td align="right" colspan="2">'.price($propal->total_tva).'</td>';
    print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
    print '<tr><td height="10">'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2">'.price($propal->total_ttc).'</td>';
    print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';


    // Statut
    print '<tr><td height="10">'.$langs->trans('Status').'</td><td align="left" colspan="3">'.$propal->getLibStatut(4).'</td></tr>';
    print '</table><br>';

    /*
    * Lignes de propale
    *
    */
	$sql = 'SELECT pt.rowid, pt.description, pt.price, pt.fk_product, pt.fk_remise_except,';
	$sql.= ' pt.qty, pt.tva_tx, pt.remise_percent, pt.subprice, pt.info_bits,';
	$sql.= ' p.label as product, p.ref, p.fk_product_type, p.rowid as prodid,';
	$sql.= ' p.description as product_desc';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'propaldet as pt';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product=p.rowid';
	$sql.= ' WHERE pt.fk_propal = '.$propal->id;
	$sql.= ' ORDER BY pt.rang ASC, pt.rowid';
    $resql = $db->query($sql);
    if ($resql)
    {
        $num_lignes = $db->num_rows($resql);
        $i = 0;
        $total = 0;

        print '<table class="noborder" width="100%">';
        if ($num_lignes)
        {
            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans('Description').'</td>';
            print '<td align="right" width="50">'.$langs->trans('VAT').'</td>';
            print '<td align="right" width="80">'.$langs->trans('PriceUHT').'</td>';
            print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';
            print '<td align="right" width="50">'.$langs->trans('ReductionShort').'</td>';
            print '<td align="right" width="50">'.$langs->trans('AmountHT').'</td>';
            print '<td width="16">&nbsp;</td>';
            print '<td width="16">&nbsp;</td>';
			print '<td width="16">&nbsp;</td>';
            print "</tr>\n";
        }
        $var=true;
        while ($i < $num_lignes)
        {
            $objp = $db->fetch_object($resql);
            $var=!$var;
            if ($_GET['action'] != 'editline' || $_GET['rowid'] != $objp->rowid)
            {
                print '<tr '.$bc[$var].'>';
                if ($objp->fk_product > 0)
                {
                    print '<td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
                    if ($objp->fk_product_type==1)
                    print img_object($langs->trans('ShowService'),'service');
                    else
                    print img_object($langs->trans('ShowProduct'),'product');
                    print ' '.$objp->ref.'</a> - '.stripslashes(nl2br($objp->product));
                    if ($objp->date_start && $objp->date_end)
                    {
                        print ' (Du '.dolibarr_print_date($objp->date_start).' au '.dolibarr_print_date($objp->date_end).')';
                    }
                    if ($objp->date_start && ! $objp->date_end)
                    {
                        print ' (A partir du '.dolibarr_print_date($objp->date_start).')';
                    }
                    if (! $objp->date_start && $objp->date_end)
                    {
                        print " (Jusqu'au ".dolibarr_print_date($objp->date_end).')';
                    }
                    print ($objp->description && $objp->description!=$objp->product)?'<br>'.stripslashes(nl2br($objp->description)):'';
                    print '</td>';
                }
				else
				{
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
					if (($objp->info_bits & 2) == 2)
					{
						print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$propal->socid.'">';
						print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
						print '</a>';
						if ($objp->description)
						{
							if ($objp->description == '(CREDIT_NOTE)')
							{
								$discount=new DiscountAbsolute($db);
								$discount->fetch($objp->fk_remise_except);
								print ' - '.$langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
							}
							else
							{
								print ' - '.nl2br($objp->description);
							}
						}
					}
					else
					{
						print nl2br($objp->description);
						if ($objp->date_start && $objp->date_end)
						{
							print ' (Du '.dolibarr_print_date($objp->date_start).' au '.dolibarr_print_date($objp->date_end).')';
						}
						if ($objp->date_start && ! $objp->date_end)
						{
							print ' (A partir du '.dolibarr_print_date($objp->date_start).')';
						}
						if (! $objp->date_start && $objp->date_end)
						{
							print " (Jusqu'au ".dolibarr_print_date($objp->date_end).')';
						}
					}
					print "</td>\n";
				}
                print '<td align="right">'.$objp->tva_tx.'%</td>';
                print '<td align="right">'.price($objp->subprice)."</td>\n";

				// Qty
				print '<td align="right">';
				if (($objp->info_bits & 2) != 2)
				{
					print $objp->qty;
				}
				else print '&nbsp;';
				print '</td>';

                if ($objp->remise_percent > 0)
                {
                    print '<td align="right">'.$objp->remise_percent."%</td>\n";
                }
                else
                {
                    print '<td>&nbsp;</td>';
                }
                print '<td align="right">'.price($objp->subprice*$objp->qty*(100-$objp->remise_percent)/100)."</td>\n";

				print '<td colspan="3">&nbsp;</td>';

                print '</tr>';
            }



            $total = $total + ($objp->qty * $objp->price);
            $i++;
        }
        $db->free($resql);
    }
    else
    {
        dolibarr_print_error($db);
    }

    print '</table>';
    
    
    print '</div>';

            
	/*
 	 * Boutons Actions
 	 */
	if ($propal->statut <> 4 && $user->societe_id == 0)
	{
		print '<div class="tabsAction">';

		if ($propal->statut == 2 && $user->rights->facture->creer)
		{
			print '<a class="butAction" href="facture.php?propalid='.$propal->id."&action=create\">".$langs->trans("BuildBill")."</a>";
		}
	
		if ($propal->statut == 2 && sizeof($propal->getInvoiceArrayList()))
		{
			print '<a class="butAction" href="propal.php?propalid='.$propal->id."&action=setstatut&statut=4\">".$langs->trans("ClassifyBilled")."</a>";
		}
	
		print "</div>";
		print "<br>\n";
	}
	


	print '<table width="100%"><tr><td width="50%" valign="top">';

    /*
     * Documents générés
     */
    $filename=sanitize_string($propal->ref);
    $filedir=$conf->propal->dir_output . "/" . sanitize_string($propal->ref);
    $urlsource=$_SERVER["PHP_SELF"]."?propalid=".$propal->id;
    $genallowed=0;
    $delallowed=0;
    
    $var=true;
    
    $somethingshown=$html->show_documents('propal',$filename,$filedir,$urlsource,$genallowed,$delallowed);
    

	/*
	 * Commandes rattachées
	 */
	if($conf->commande->enabled)
	{
		$propal->loadOrders();
		$coms = $propal->commandes;
		if (sizeof($coms) > 0)
		{
			$total=0;
			if ($somethingshown) { print '<br>'; $somethingshown=1; }
			print_titre($langs->trans('RelatedOrders'));
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Ref").'</td>';
			print '<td align="center">'.$langs->trans("Date").'</td>';
			print '<td align="right">'.$langs->trans("Price").'</td>';
			print '<td align="right">'.$langs->trans("Status").'</td>';
			print '</tr>';
			$var=true;
			for ($i = 0 ; $i < sizeof($coms) ; $i++)
			{
				$var=!$var;
				print '<tr '.$bc[$var].'><td>';
				print '<a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$coms[$i]->id.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$coms[$i]->ref."</a></td>\n";
				print '<td align="center">'.dolibarr_print_date($coms[$i]->date).'</td>';
				print '<td align="right">'.price($coms[$i]->total_ttc).'</td>';
				print '<td align="right">'.$coms[$i]->getLibStatut(3).'</td>';
				print "</tr>\n";
				$total = $total + $objp->total;
			}
			print '</table>';
		}
	}

	
	/*
	 * Factures associees
	 */
	$sql = "SELECT f.facnumber, f.total,".$db->pdate("f.datef")." as df, f.rowid as facid, f.fk_user_author, f.fk_statut, f.paye";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	if (!$conf->commande->enabled)
	{
		$sql .= ", ".MAIN_DB_PREFIX."fa_pr as fp";
		$sql .= " WHERE fp.fk_facture = f.rowid AND fp.fk_propal = ".$propal->id;
	}
	else
	{
		$sql .= ", ".MAIN_DB_PREFIX."co_pr as cp, ".MAIN_DB_PREFIX."co_fa as cf";
		$sql .= " WHERE cp.fk_propale = ".$propal->id." AND cf.fk_commande = cp.fk_commande AND cf.fk_facture = f.rowid";
	}
	
	$resql = $db->query($sql);
	if ($resql)
    {
		$num_fac_asso = $db->num_rows($resql);
		$i = 0; $total = 0;
		if ($somethingshown) { print '<br>'; $somethingshown=1; }
		if ($num_fac_asso > 1) print_titre($langs->trans("RelatedBills"));
		else print_titre($langs->trans("RelatedBill"));
		print '<table class="noborder" width="100%">';
		print "<tr class=\"liste_titre\">";
		print '<td>'.$langs->trans("Ref").'</td>';
		print '<td align="center">'.$langs->trans("Date").'</td>';
		print '<td align="right">'.$langs->trans("Price").'</td>';
		print '<td align="right">'.$langs->trans("Status").'</td>';
		print "</tr>\n";
		
		require_once(DOL_DOCUMENT_ROOT.'/facture.class.php');
		$staticfacture=new Facture($db);
		
		$var=True;
		while ($i < $num_fac_asso)
		{
			$objp = $db->fetch_object();
			$var=!$var;
			print "<tr $bc[$var]>";
			print '<td><a href="../compta/facture.php?facid='.$objp->facid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$objp->facnumber.'</a></td>';
			print '<td align="center">'.dolibarr_print_date($objp->df).'</td>';
			print '<td align="right">'.price($objp->total).'</td>';
			print '<td align="right">'.$staticfacture->LibStatut($objp->paye,$objp->fk_statut,3).'</td>';
			print "</tr>";
			$total = $total + $objp->total;
			$i++;
		}
		print "<tr class=\"liste_total\"><td align=\"right\" colspan=\"2\">".$langs->trans("TotalHT")."</td>";
		print "<td align=\"right\">".price($total)."</td>";
		print "<td>&nbsp;</td></tr>\n";
		print "</table>";
		$db->free();
	}


  print '</td><td valign="top" width="50%">';


  /*
   * Liste des actions propres à la propal
   */
  $sql = 'SELECT id, '.$db->pdate('a.datea'). ' as da, label, note, fk_user_author' ;
  $sql .= ' FROM '.MAIN_DB_PREFIX.'actioncomm as a';
  $sql .= ' WHERE a.fk_soc = '.$societe->id.' AND a.propalrowid = '.$propal->id ;
  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows($resql);
      if ($num)
	{
	  print_titre($langs->trans('ActionsOnPropal'));
	  $i = 0;
	  $total = 0;
	  $var=true;

	  print '<table class="border" width="100%">';
	  print '<tr '.$bc[$var].'><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans('Date').'</td><td>'.$langs->trans('Action').'</td><td>'.$langs->trans('By').'</td></tr>';
	  print "\n";

	  while ($i < $num)
	    {
	      $objp = $db->fetch_object($resql);
	      $var=!$var;
	      print '<tr '.$bc[$var].'>';
	      print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$objp->id.'">'.img_object($langs->trans('ShowTask'),'task').' '.$objp->id.'</a></td>';
	      print '<td>'.dolibarr_print_date($objp->da)."</td>\n";
	      print '<td>'.stripslashes($objp->label).'</td>';
	      $authoract = new User($db);
	      $authoract->id = $objp->fk_user_author;
	      $authoract->fetch('');
	      print '<td>'.$authoract->login.'</td>';
	      print "</tr>\n";
	      $i++;
	    }
	  print '</table>';
	}
    }
  else
    {
      dolibarr_print_error($db);
    }


  print '</td></tr></table>';
    
    
}
else
{
  /**
   *
   * Mode Liste des propales
   *
   */


  if (! $sortfield) $sortfield="p.datep";
  if (! $sortorder) $sortorder="DESC";
  if ($page == -1) $page = 0 ;

  $limit = $conf->liste_limit;
  $offset = $limit * $page ;
  $pageprev = $page - 1;
  $pagenext = $page + 1;


  $sql = "SELECT s.nom, s.idp, s.client,";
  $sql.= " p.rowid as propalid, p.price, p.ref, p.fk_statut,";
  $sql.= $db->pdate("p.datep")." as dp, ";
  $sql.= $db->pdate("p.fin_validite")." as dfin";
  if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
  $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
  if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
  $sql.= " WHERE p.fk_soc = s.idp";
  if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND s.idp = sc.fk_soc AND sc.fk_user = " .$user->id;
  if ($socid)           $sql .= " AND s.idp = $socid";
  if ($viewstatut <> '') $sql .= " AND p.fk_statut in ($viewstatut)"; // viewstatut peut etre combinaisons séparé par virgules
  if ($month > 0)        $sql .= " AND date_format(p.datep, '%Y-%m') = '$year-$month'";
  if ($year > 0)         $sql .= " AND date_format(p.datep, '%Y') = $year";
  $sql .= " ORDER BY $sortfield $sortorder, p.rowid DESC ";
  $sql .= $db->plimit($limit + 1,$offset);

	if ( $db->query($sql) )
	{
		$num = $db->num_rows();
		
		
		print_barre_liste($langs->trans("Proposals"), $page, "propal.php","&socid=$socid",$sortfield,$sortorder,'',$num);
		
		$i = 0;
		$var=true;
		
		print "<table class=\"noborder\" width=\"100%\">";
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Ref"),"propal.php","p.ref","","&year=$year&viewstatut=$viewstatut",'',$sortfield);
		print_liste_field_titre($langs->trans("Company"),"propal.php","s.nom","&viewstatut=$viewstatut","",'',$sortfield);
		print_liste_field_titre($langs->trans("Date"),"propal.php","p.datep","&viewstatut=$viewstatut","",'align="right"',$sortfield);
		print_liste_field_titre($langs->trans("Price"),"propal.php","p.price","&viewstatut=$viewstatut","",'align="right"',$sortfield);
		print_liste_field_titre($langs->trans("Status"),"propal.php","p.fk_statut","&viewstatut=$viewstatut","",'align="right"',$sortfield);
		print "</tr>\n";

		while ($i < min($num, $limit))
		{
			$objp = $db->fetch_object();
		
			$var=!$var;
			print "<tr $bc[$var]>";
		
			// Ref
			print '<td><a href="propal.php?propalid='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' ';
			print $objp->ref."</a>";
			if ($objp->fk_statut==1 && $obj->dfin < time() - $conf->propal->cloture->warning_delay)  { print " ".img_warning($langs->trans("Late")); }
			print "</td>\n";
		
			// Societe
			print "<td>";
			$societestatic->nom=$objp->nom;
			$societestatic->id=$objp->idp;
			$societestatic->client=$objp->client;
			print $societestatic->getNomUrl(1,'customer',44);
			print "</td>";
		
			// Date
			print "<td align=\"right\">";
			$y = strftime("%Y",$objp->dp);
			$m = strftime("%m",$objp->dp);
			print strftime("%d",$objp->dp)."\n";
			print " <a href=\"propal.php?year=$y&month=$m\">";
			print strftime("%B",$objp->dp)."</a>\n";
			print " <a href=\"propal.php?year=$y\">";
			print strftime("%Y",$objp->dp)."</a></td>\n";
		
			// Prix
			print "<td align=\"right\">".price($objp->price)."</td>\n";
			print "<td align=\"right\">".$propalstatic->LibStatut($objp->fk_statut,5)."</td>\n";
			print "</tr>\n";
		
			$i++;
		}
		
	  	print "</table>";
	  	$db->free();
	}
	else
	{
		dolibarr_print_error($db);
	}
}
$db->close();


llxFooter('$Date$ - $Revision$');

?>
