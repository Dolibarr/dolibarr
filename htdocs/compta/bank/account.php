<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

require("./pre.inc.php");

if (!$user->rights->banque->lire)
  accessforbidden();

$account=isset($_GET["account"])?$_GET["account"]:$_POST["account"];
$vline=isset($_GET["vline"])?$_GET["vline"]:$_POST["vline"];
$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];
$page=isset($_GET["page"])?$_GET["page"]:0;

if ($action == 'add' && $account)
{    

  if ($_POST["credit"] > 0)
    {
      $amount = $_POST["credit"];
    }
  else
    {
      $amount = - $_POST["credit"];
    }
  
  $dateop = $_POST["dateoy"].$_POST["dateo"];
  $operation=$_POST["operation"];
  $label=$_POST["label"];
  $operation=$_POST["operation"];
  $num_chq=$_POST["num_chq"];
  $cat1=$_POST["cat1"];

  $acct=new Account($db,$account);

  $insertid = $acct->addline($dateop, $operation, $label, $amount, $num_chq, $cat1);

  //print "<p>Account: insertid=$insertid - " . $action . " : ".$_GET["account"] . " - " . $_POST["account"]." - ".$account."</p>\n";

  if ($insertid == '')
    {
      print "Erreur: Probleme d'insertion : ".$db->error();
    }
  else
    {
      Header("Location: $PHP_SELF?account=" . $account);
    }
}
if ($action == 'del' && $account && $user->rights->banque->modifier)
{
  $acct=new Account($db,$account);
  $acct->deleteline($rowid);
}

/***********************************************************************************
 *
 *
 *
 */

llxHeader();

//print "<p>Page: $page - Account: " . $_GET["account"] . " - " . $HTTP_POST_VARS["account"]."</p>\n";

if ($account > 0)
{
  if ($vline)
    {
      $viewline = $vline;
    }
  else
    {
      $viewline = 20;
    }
  $acct = new Account($db);
  $acct->fetch($account);
  print_titre("Journal de trésorerie du compte : " .$acct->label);
  print '<br>';

  // Chargement des categories dans $options
  $sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_categ;";
  $result = $db->query($sql);
  if ($result)
    {
      $var=True;  
      $num = $db->num_rows();
      $i = 0;
      $options = "<option value=\"0\" SELECTED></option>";
      while ($i < $num)
	{
	  $obj = $db->fetch_object($i);
	  $options .= "<option value=\"$obj->rowid\">$obj->label</option>\n"; $i++;
	}
      $db->free();
    }

  /*
   *
   *
   */
  if ($HTTP_POST_VARS["req_desc"]) 
    { 
      $sql_rech = " AND lower(b.label) like '%".strtolower($HTTP_POST_VARS["req_desc"])."%'";
      $mode_search = 1;
    }
  else 
    {
  	  $mode_search = 0;
    }
  /*
   *
   *
   */
  $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."bank as b WHERE 1=1";
//  $sql .= " AND b.dateo <= now()";
  $sql .= " AND b.fk_account=".$acct->id;

  $sql .= $sql_rech;
  if ( $db->query($sql) )
    {
      $nbline = $db->result (0, 0);
      $total_lines = $nbline;
      $db->free();
    
      if ($nbline > $viewline )
	{
	  $limit = $nbline - $viewline ;
	}
      else
	{
	  $limit = $viewline;
	}
    }

  if ($page > 0 && $mode_search == 0)
    {
      $limitsql = $nbline - ($page * $viewline);
      if ($limitsql < $viewline)
	{
	  $limitsql = $viewline;
	}
      $nbline = $limitsql;
    }
  else
    {
      $page = 0;
      $limitsql = $nbline;
    }
  //print "$page $viewline $nbline $limitsql";

  /*
   * Formulaire de recherche
   *
   */  
  print '<form method="post" action="'.$PHP_SELF.'">';
  print '<input type="hidden" name="action" value="search">';
  print '<input type="hidden" name="account" value="' . $acct->id . '">';
  print '<table class="border" width="100%" cellspacing="0" cellpadding="2">';
  print "<tr>";
  print '<td>';
  if ($limitsql > $viewline)
    {
      print '<a href="account.php?account='.$acct->id.'&amp;page='.($page+1).'"><img alt="Page précédente" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1leftarrow.png" border="0"></a>';
    }
  if ($total_lines > $limitsql )
    {
      print '<a href="account.php?account='.$acct->id.'&amp;page='.($page-1).'"><img alt="Page suivante" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1rightarrow.png" border="0"></a>';
    }
  print '</td>';
  print '<td colspan="3"><input type="text" name="req_desc" value="'.$HTTP_POST_VARS["req_desc"].'" size="40"></TD>';
  print '<td align="right"><input type="text" name="req_debit" value="'.$HTTP_POST_VARS["req_debit"].'" size="6"></TD>';
  print '<td align="right"><input type="text" name="req_credit" value="'.$HTTP_POST_VARS["req_credit"].'" size="6"></TD>';
  print '<td align="center"><input type="submit" value="Chercher"></td>';
  print '<td align="center">';
  if ($user->rights->banque->modifier)
    {
      print '<a href="rappro.php?account='.$acct->id.'">Rappro</a>';
    }
  else
    {
      print "&nbsp;";
    }
  print '</td>';
  print "</tr>\n";
  print "</form>\n";
  /*
   *
   *
   */
  if ($user->rights->banque->modifier)
    {
      print '<form method="post" action="'.$PHP_SELF.'">';
      print '<input type="hidden" name="action" value="add">';
      print '<input type="hidden" name="vline" value="' . $vline . '">';
      print '<input type="hidden" name="account" value="' . $acct->id . '">';
    }
  print '<tr class="liste_titre">';
  print '<td>Date Ope</td><td>Valeur</td><td>Type</td><td>Description</td>';
  print '<td align="right">Débit</td><td align="right">Crédit</td><td align="right">Solde</td>';
  print '<td align="center">Relevé</td></tr>';

  // DEBUG
  // print "<tr><td>$nbline</td><td>$viewline</td><td>total_lines $total_lines</td><td>limitsql $limitsql</td></tr>";
      
  /* Another solution
   * create temporary table solde type=heap select amount from llx_bank limit 100 ;
   * select sum(amount) from solde ;
   */

  $sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do,".$db->pdate("b.datev")." as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type";
  $sql .= " FROM ".MAIN_DB_PREFIX."bank as b ";
  $sql .= " WHERE fk_account=".$acct->id;

  if ($req_debit) 
    { 
      $sql .= " AND b.amount = -".$req_debit;
    }

  $sql .= $sql_rech;

  if ($vue)
    {
      if ($vue == 'credit')
	{
	  $sql .= " AND b.amount >= 0 ";
	}
      else
	{
	  $sql .= " AND b.amount < 0 ";
	}
    }

//  $sql .= " AND b.dateo <= now()";

  $sql .= " ORDER BY b.datev ASC";
  $sql .= $db->plimit($limitsql, 0);

  $result = $db->query($sql);
  if ($result)
    {
      _print_lines($db, $sql, $acct);
    }

  /*
   * Opérations hors factures
   *
   */
  if ($user->rights->banque->modifier)
    {
      print "<tr><td colspan=\"7\">&nbsp;</td></tr>\n";
      print "<tr>";
      print "<td align=\"left\" colspan=\"8\"><b>Saisie d'une transaction hors facture</b></td></tr>";
      print '<tr>';
      print '<td><input name="dateoy" type="text" size="3" value="'.strftime("%Y",time()).'" maxlength="4">';
      print '<input name="dateo" type="text" size="3" maxlength="4"></td>';
      print '<td colspan="2"><select name="operation">';
      print '<option value="CB">CB';
      print '<option value="CHQ">CHQ';
      print '<option value="DEP">DEP';
      print '<option value="TIP">TIP';
      print '<option value="PRE">PRE';
      print '<option value="VIR">VIR';
      print '</select><input name="num_chq" type="text" size="4"></td>';
      print '<td>';
      print '<input name="label" type="text" size="40"></td>';

      print '<td align=right><input name="debit" type="text" size="8"></td>';
      print '<td align=right><input name="credit" type="text" size="8"></td>';
      print "<td colspan=\"2\" align=\"center\">";
      print "<select name=\"cat1\">$options</select>";
      print '</td></tr>';
      print '<tr>';
      print '<td><small>YYYY MMDD</small></td><td colspan="2">&nbsp;</td><td>Description</td><td align=right>0000.00</td><td align=right>0000.00</td>';
      print '<td colspan="2" align="center"><input type="submit" value="Ajouter"></td>';
      print '</tr>';
    }

  print "</table>";
  if ($user->rights->banque->modifier)
    {
      print "</form>";
    }
}
else
{
  print "Erreur : numéro de compte inexistant";
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");


/*
 *
 */
Function _print_lines($db,$sql,$acct)
{
  global $bc, $nbline, $viewline, $user, $page;
  $var=True;  
  $num = $db->num_rows();
  $i = 0; $total = 0;
  $sep = 0;
      
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  $total = $total + $objp->amount;
	  $time = time();
	  if ($i >= ($nbline - $viewline))
	    {
	      $var=!$var;

	      if ($objp->do > $time && !$sep)
		{
		  $sep = 1 ;
		  print "<tr><td align=\"right\" colspan=\"5\">&nbsp;</td>";
		  print "<td align=\"right\"><b>".price($total - $objp->amount)."</b></td>";
		  print "<td>&nbsp;</td>";
		  print '</tr>';
		}
		  
	      print "<tr $bc[$var]>";
	      print "<td>".strftime("%d %b %y",$objp->do)."</td>\n";
	      print "<td>".strftime("%d/%m/%y",$objp->dv)."</td>\n";
	      print "<td>".$objp->fk_type." ".($objp->num_chq?$objp->num_chq:"")."</TD>\n";
		  
//	      if ($objp->num_chq)
//		{
//		  print "<td><a href=\"ligne.php?rowid=$objp->rowid&amp;account=$acct->id\">$objp->label</a></td>";
//		}
//	      else
//		{
		  //Xavier DUTOIT : Ajout d'un lien pour modifier la ligne
		  print "<td><a href=\"ligne.php?rowid=$objp->rowid&amp;account=$acct->id\">$objp->label</a>";
		  /*
		   * Ajout les liens
		   */
		  $urls_line = $acct->get_url($objp->rowid);
		  $numurl = sizeof($urls_line);
		  $k = 0;
		  while ($k < $numurl)
		    {
		      print ' <a href="'.$urls_line[$k][0].$urls_line[$k][1].'">'.$urls_line[$k][2].'</a>';
		      $k++;
		    }
		  print '</td>';
//		}
	      
	      if ($objp->amount < 0)
		{
		  print "<td align=\"right\">".price($objp->amount * -1)."</TD><td>&nbsp;</td>\n";
		}
	      else
		{
		  print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</TD>\n";
		}
	      
	      if ($action !='search')
		{
		  if ($total > 0)
		    {
		      print '<td align="right">'.price($total)."</TD>\n";
		    }
		  else
		    {
		      print "<td align=\"right\"><b>".price($total)."</b></TD>\n";
		    }
		}
	      else
		{
		  print '<td align="right">-</TD>';
		}
	      
	      if ($objp->rappro)
		{
		  print "<td align=\"center\"><a href=\"releve.php?num=$objp->num_releve&amp;account=$acct->id\">$objp->num_releve</a></td>";
		}
	      else
		{
		  if ($user->rights->banque->modifier)
		    {
		      print "<td align=\"center\"><a href=\"$PHP_SELF?action=del&amp;rowid=$objp->rowid&amp;account=$acct->id&amp;page=$page\">";
		      print img_delete();
		      print "</a></td>";
		    }
		  else
		    {
		      print "<td align=\"center\">&nbsp;</td>";
		    }
		}
	      
	      print "</tr>";
	      
	    }
	  
	  $i++;
	}
      $db->free();
}
?>
