<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
      \file       htdocs/compta/index.php
      \ingroup    compta
      \brief      Page accueil zone comptabilité
      \version    $Revision$
*/

require("./pre.inc.php");

$user->getrights(); // On a besoin des permissions sur plusieurs modules

if (!$user->rights->compta->general->lire)
  accessforbidden();

$langs->load("compta");
$langs->load("bills");

// Sécurité accés client
$socidp='';
if ($user->societe_id > 0)
{
  $action = '';
  $socidp = $user->societe_id;
}


llxHeader("",$langs->trans("AccountancyTreasuryArea"));


/*
 * Actions
 */

if (isset($_GET["action"]) && $_GET["action"] == 'add_bookmark')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bookmark WHERE fk_soc = ".$socidp." AND fk_user=".$user->id;
  if (! $db->query($sql) )
    {
      dolibarr_print_error($db);
    }
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark (fk_soc, dateb, fk_user) VALUES ($socidp, now(),".$user->id.");";
  if (! $db->query($sql) )
    {
      dolibarr_print_error($db);
    }
}

if (isset($_GET["action"]) && $_GET["action"] == 'del_bookmark')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bookmark WHERE rowid=".$_GET["bid"];
  $result = $db->query($sql);
}



/*
 * Affichage page
 *
 */
print_fiche_titre($langs->trans("AccountancyTreasuryArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

/*
 * Zone recherche facture
 */
if ($conf->facture->enabled)
{
  print '<form method="post" action="facture.php">';
  
  print '<table class="noborder" width="100%">';
  print "<tr class=\"liste_titre\">";
  print '<td colspan="3">'.$langs->trans("SearchABill").'</td></tr>';
  print "<tr $bc[0]><td>".$langs->trans("Ref").':</td><td><input type="text" name="sf_ref" class="flat" size="18"></td>';
  print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
  print "<tr $bc[0]><td>".$langs->trans("Other").':</td><td><input type="text" name="sall" class="flat" size="18"></td>';
  print '</tr>';
  print "</table></form><br>";
}


/**
 * Factures brouillons
 */
if ($conf->facture->enabled && $user->rights->facture->lire)
{  
  $sql  = "SELECT f.facnumber, f.rowid, f.total_ttc, s.nom, s.idp";
  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
  $sql .= " WHERE s.idp = f.fk_soc AND f.fk_statut = 0";
  
  if ($socidp)
    {
      $sql .= " AND f.fk_soc = $socidp";
    }
  
  $resql = $db->query($sql);
  
  if ( $resql )
    {
      $num = $db->num_rows($resql);
      if ($num)
        {
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre">';
	  print '<td colspan="3">'.$langs->trans("DraftBills").' ('.$num.')</td></tr>';
	  $i = 0;
	  $tot_ttc = 0;
	  $var = True;
	  while ($i < $num && $i < 20)
            {
	      $obj = $db->fetch_object($resql);
	      $var=!$var;
	      print '<tr '.$bc[$var].'><td nowrap><a href="facture.php?facid='.$obj->rowid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$obj->facnumber.'</a></td>';
	      print '<td><a href="fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->nom,20).'</a></td>';
	      print '<td align="right">'.price($obj->total_ttc).'</td>';
	      print '</tr>';
	      $tot_ttc+=$obj->total_ttc;
	      $i++;
            }
	  
	  print '<tr class="liste_total"><td colspan="2" align="left">'.$langs->trans("Total").'</td>';
	  print '<td align="right">'.price($tot_ttc).'</td>';
	  print '</tr>';
	  
	  print "</table><br>";
        }
      $db->free($resql);
    }
  else
    {
      dolibarr_print_error($db);
    }
}

/**
 * Charges a payer
 */
if ($conf->compta->enabled)
{
    if ($user->societe_id == 0)
    {

        $sql = "SELECT c.rowid, c.amount, cc.libelle";
        $sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as c, ".MAIN_DB_PREFIX."c_chargesociales as cc";
        $sql .= " WHERE c.fk_type = cc.id AND c.paye=0";

        $resql = $db->query($sql);

        if ( $resql )
        {
            $num = $db->num_rows($resql);
            if ($num)
            {
                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre">';
                print '<td colspan="2">'.$langs->trans("ContributionsToPay").' ('.$num.')</td></tr>';
                $i = 0;
                $tot_ttc=0;
                $var = True;
                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);
                    $var = !$var;
                    print "<tr $bc[$var]>";
                    print '<td><a href="'.DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$obj->libelle.'</td>';
                    print '<td align="right">'.price($obj->amount).'</td>';
                    print '</tr>';
                    $tot_ttc+=$obj->amount;
                    $i++;
                }

                print '<tr class="liste_total"><td align="left">'.$langs->trans("Total").'</td>';
                print '<td align="right">'.price($tot_ttc).'</td>';
                print '</tr>';

                print '</table><br>';
            }
            $db->free($resql);
        }
        else
        {
            dolibarr_print_error($db);
        }
    }
}


/**
 * Bookmark
 */
$sql = "SELECT s.idp, s.nom,b.rowid as bid";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."bookmark as b";
$sql .= " WHERE b.fk_soc = s.idp AND b.fk_user = ".$user->id;
$sql .= " ORDER BY lower(s.nom) ASC";

$resql = $db->query($sql);

if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;
  if ($num)
    {
      print '<table class="noborder" width="100%">';
      print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("Bookmarks")."</td></tr>\n";
      $var = True;
      while ($i < $num)
	{
	  $obj = $db->fetch_object($resql);
	  $var = !$var;
	  print "<tr $bc[$var]>";
	  print '<td><a href="fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td>';
	  print '<td align="right"><a href="index.php?action=del_bookmark&amp;bid='.$obj->bid.'">'.img_delete().'</a></td>';
	  print '</tr>';
	  $i++;
	}
      print '</table>';
    }
  $db->free($resql);
}
else
{
  dolibarr_print_error($db);
}


print '</td><td valign="top" width="70%" class="notopnoleftnoright">';


/*
 * Commandes à facturer
 */
if ($conf->commande->enabled && $user->rights->commande->lire)
{
  $langs->load("orders");

  $sql = "SELECT sum(f.total) as tot_fht, sum(f.total_ttc) as tot_fttc";
  $sql .= " ,s.nom, s.idp, p.rowid, p.ref, p.total_ht, p.total_ttc";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe AS s, ".MAIN_DB_PREFIX."commande AS p";
  $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."co_fa AS co_fa ON co_fa.fk_commande = p.rowid";
  $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture AS f ON co_fa.fk_facture = f.rowid";
  $sql .= " WHERE p.fk_soc = s.idp";
  if ($socidp)
    {
      $sql .= " AND p.fk_soc = $socidp";
    }
  $sql .= " AND p.fk_statut >=1	AND p.facture=0";
  $sql .= " GROUP BY p.rowid";

  $resql = $db->query($sql);

  if ( $resql )
    {
      $num = $db->num_rows($resql);
      if ($num)
	{
	  $i = 0;
	  print '<table class="noborder" width="100%">';
	  print "<tr class=\"liste_titre\">";
	  print '<td colspan="2">'.$langs->trans("OrdersToBill").' ('.$num.')</td>';
	  print '<td align="right">'.$langs->trans("AmountHT").'</td>';
	  print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
	  print '<td align="right">'.$langs->trans("ToBill").'</td>';
	  print '</tr>';
	  $var = True;
	  $tot_ht=$tot_ttc=$tot_tobill=0;
	  while ($i < $num)
	    {
            $obj = $db->fetch_object($resql);
//			if ($obj->total_ttc-$obj->tot_fttc >0)
//			{
	            $var=!$var;
				print "<tr $bc[$var]>";
				print "<td width=\"20%\"><a href=\"commande/fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowOrder"),"order").'</a>&nbsp;';
				print "<a href=\"commande/fiche.php?id=$obj->rowid\">".$obj->ref.'</a></td>';
				
				print '<td><a href="fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowCompany"),"company").'</a>&nbsp;';
				print '<a href="fiche.php?socid='.$obj->idp.'">'.dolibarr_trunc($obj->nom,50).'</a></td>';
				print '<td align="right">'.price($obj->total_ht).'</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '<td align="right">'.price($obj->total_ttc-$obj->tot_fttc).'</td></tr>';
				$tot_ht += $obj->total_ht;
				$tot_ttc += $obj->total_ttc;
				$tot_tobill += ($obj->total_ttc-$obj->tot_fttc);
//			}
            $i++;
	    }
      $var=!$var;

	  print '<tr class="liste_total"><td colspan="2" align="right">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToBill").': '.price($tot_tobill).')</font> </td>';
	  print '<td align="right">'.price($tot_ht).'</td>';
	  print '<td align="right">'.price($tot_ttc).'</td>';
	  print '<td align="right">'.price($tot_tobill).'</td>';
	  print '</tr>';
	  print '</table><br>';
	}
      $db->free($resql);
    }
  else
    {
      dolibarr_print_error($db);
    }
}


if ($conf->facture->enabled && $user->rights->facture->lire)
{

  /*
   * Factures impayées
   *
   */

  $sql = "SELECT f.facnumber, f.rowid, s.nom, s.idp, f.total, f.total_ttc, ".$db->pdate("f.date_lim_reglement")." as datelimite, sum(pf.amount) as am";
  $sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f left join ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
  $sql.= " WHERE s.idp = f.fk_soc AND f.paye = 0 AND f.fk_statut = 1";
  if ($socidp) $sql .= " AND f.fk_soc = $socidp";
  $sql.= " GROUP BY f.facnumber, f.rowid, s.nom, s.idp, f.total, f.total_ttc";
  $sql.= " ORDER BY f.datef ASC, f.facnumber ASC";

  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;

      if ($num)
	{
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre"><td colspan="2"><a href="'.DOL_URL_ROOT.'/compta/facture/impayees.php">'.$langs->trans("BillsCustomersUnpayed",min($conf->liste_limit,$num)).' ('.$num.')</a></td>';
	  print '<td align="right">'.$langs->trans("AmountHT").'</td><td align="right">'.$langs->trans("AmountTTC").'</td><td align="right">'.$langs->trans("Received").'</td></tr>';
	  $var = True;
	  $total_ttc = $totalam = $total = 0;
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object($resql);

	      if ($i < $conf->liste_limit)
		{
		  $var=!$var;
		  print '<tr '.$bc[$var].'>';
		  print '<td nowrap><a href="facture.php?facid='.$obj->rowid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$obj->facnumber.'</a>';
		  if ($obj->datelimite < (time() - $conf->facture->client->warning_delay)) print img_warning($langs->trans("Late"));
		  print '</td>';
		  print '<td><a href="fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowCustomer"),"company").' '.dolibarr_trunc($obj->nom,44).'</a></td>';
		  print '<td align="right">'.price($obj->total).'</td>';
		  print '<td align="right">'.price($obj->total_ttc).'</td>';
		  print '<td align="right">'.price($obj->am).'</td></tr>';
		}
	      $total_ttc +=  $obj->total_ttc;
		  $total += $obj->total;
	      $totalam +=  $obj->am;
	      $i++;
	    }
	  $var=!$var;

	  print '<tr class="liste_total"><td colspan="2" align="right">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToTake").': '.price($total_ttc-$totalam).')</font> </td>';
	  print '<td align="right">'.price($total).'</td>';
	  print '<td align="right">'.price($total_ttc).'</td>';
	  print '<td align="right">'.price($totalam).'</td>';
	  print '</tr>';
	  print '</table><br>';
	}
      $db->free($resql);
    }
  else
    {
      dolibarr_print_error($db);
    }
}


// \todo Mettre ici recup des actions en rapport avec la compta
$resql = 0;
if ($resql)
{
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("TasksToDo").'</td>';
  print "</tr>\n";
  $var = True;
  $i = 0;
  while ($i < $db->num_rows($resql) )
    {
      $obj = $db->fetch_object($resql);
      $var=!$var;

      print "<tr $bc[$var]><td>".strftime("%d %b %Y",$obj->da)."</td><td><a href=\"action/fiche.php\">$obj->libelle $obj->label</a></td></tr>";
      $i++;
    }
  $db->free($resql);
  print "</table><br>";
}


/*
 * Factures a payer
 */
if ($conf->facture->enabled) {

    if ($user->societe_id == 0)
    {
      $sql = "SELECT ff.rowid, ff.facnumber, ff.libelle, ff.total_ht, ff.total_ttc, s.nom, s.idp";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as ff";
      $sql .= " WHERE s.idp = ff.fk_soc";
      $sql .= " AND ff.paye=0";

      $result=$db->query($sql);
      if ($result)
        {
          $num = $db->num_rows();
          if ($num)
    	{
    	  print '<table class="noborder" width="100%">';
          print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("BillsSuppliersUnpayed").' ('.$num.')</td>';
          print '<td align="right">'.$langs->trans("AmountHT").'</td>';
          print '<td align="right">'.$langs->trans("AmountTTC").'</td></tr>';
    	  print "</tr>\n";
    	  $i = 0;
    	  $var = True;
    	  $total = $total_ttc = $totalam = 0;
    	  while ($i < $num)
    	    {
    	      $obj = $db->fetch_object($result);
    	      $var = !$var;
        	  print '<tr '.$bc[$var].'><td><a href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?facid='.$obj->rowid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$obj->facnumber.'</a></td>';
    		  print '<td><a href="fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowSupplier"),"company").' '.dolibarr_trunc($obj->nom,50).'</a></td>';
		      print '<td align="right">'.price($obj->total_ht).'</td>';
              print '<td align="right">'.price($obj->total_ttc).'</td>';
    	      print '</tr>';
        	  $total += $obj->total_ht;
        	  $total_ttc +=  $obj->total_ttc;
        	  $totalam +=  $obj->am;
    	      $i++;
            }
          $var=!$var;
          
          print '<tr class="liste_total"><td colspan="2" align="right">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToPay").': '.price($total_ttc-$totalam).')</font> </td>';
          print '<td align="right">'.price($total).'</td>';
          print '<td align="right">'.price($total_ttc).'</td>';
    	  print '</tr>';
    	  print '</table><br>';
    	}
        }
      else
        {
          dolibarr_print_error($db);
        }
    }

}


print '</td></tr>';

print '</table>';

$db->close();

 
llxFooter('$Date$ - $Revision$');
?>
