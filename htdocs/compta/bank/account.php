<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copytight (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copytight (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
	    \file       htdocs/compta/bank/account.php
		\ingroup    banque
		\brief      Page de détail des transactions bancaires
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");
require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/chargesociales.class.php");

if (!$user->rights->banque->lire)
    accessforbidden();

$langs->load("bills");


$account=isset($_GET["account"])?$_GET["account"]:$_POST["account"];
$vline=isset($_GET["vline"])?$_GET["vline"]:$_POST["vline"];
$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];
$page=isset($_GET["page"])?$_GET["page"]:0;
$negpage=isset($_GET["negpage"])?$_GET["negpage"]:0;
if ($negpage)
{
	$page=$_GET["nbpage"] - $negpage;
	if ($page > $_GET["nbpage"]) $page = $_GET["nbpage"];
}

$mesg='';

	
/*
* Action
*/

if ($_POST["action"] == 'add' && $account && ! isset($_POST["cancel"]) && $user->rights->banque->modifier)
{
	
	if ($_POST["credit"] > 0)
    {
        $amount = price2num($_POST["credit"]);
    }
    else
    {
        $amount = - price2num($_POST["debit"]);
    }

    $dateop = dolibarr_mktime(12,0,0,$_POST["opmonth"],$_POST["opday"],$_POST["opyear"]);
    $operation=$_POST["operation"];
    $num_chq=$_POST["num_chq"];
    $label=$_POST["label"];
    $cat1=$_POST["cat1"];

	if (! $dateop)    $mesg=$langs->trans("ErrorFieldRequired",$langs->trans("Date"));
	if (! $operation) $mesg=$langs->trans("ErrorFieldRequired",$langs->trans("Type"));
	if (! $amount)    $mesg=$langs->trans("ErrorFieldRequired",$langs->trans("Amount"));
	
	if (! $mesg)
	{
	    $acct=new Account($db,$account);
	    $insertid = $acct->addline($dateop, $operation, $label, $amount, $num_chq, $cat1, $user);
	    if ($insertid > 0)
	    {
	        Header("Location: account.php?account=" . $account);
	        exit;
	    }
	    else
	    {
	        dolibarr_print_error($db,$acct->error);
	    }
	}
	else
	{
		$_GET["action"]='addline';
	}
}
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"]=='yes' && $user->rights->banque->modifier)
{
	$accline=new AccountLine($db);
	$accline->fetch($_GET["rowid"]);
	$result=$accline->delete();
}


/*
* Affichage page
*/

llxHeader();
$societestatic=new Societe($db);
$chargestatic=new ChargeSociales($db);
$memberstatic=new Adherent($db);

$html = new Form($db);

if ($account || $_GET["ref"])
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
	if ($_GET["account"]) 
	{
		$result=$acct->fetch($_GET["account"]);
	}
	if ($_GET["ref"]) 
	{
		$result=$acct->fetch(0,$_GET["ref"]);
		$account=$acct->id;
	}

	// Chargement des categories bancaires dans $options
	$nbcategories=0;
	$sql = "SELECT rowid, label";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank_categ";
	$sql.= " ORDER BY label";
	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$num = $db->num_rows($result);
		$i = 0;
		$options = "<option value=\"0\" selected=\"true\">&nbsp;</option>";
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			$options .= "<option value=\"$obj->rowid\">$obj->label</option>\n";
			$nbcategories++;
			$i++;
		}
		$db->free($result);
	}

	
	// Definition de sql_rech et param
	$param='';
	$sql_rech='';
	$mode_search = 0;
	if ($_REQUEST["req_desc"])
	{
		$sql_rech.= " AND b.label like '%".$_REQUEST["req_desc"]."%'";
		$param.='&amp;req_desc='.urlencode($_REQUEST["req_desc"]);
		$mode_search = 1;
	}
	if ($_REQUEST["req_debit"])
	{
		$sql_rech.=" AND amount = -".$_REQUEST["req_debit"];
		$param.='&amp;req_debit='.urlencode($_REQUEST["req_debit"]);
		$mode_search = 1;
	}
	if ($_REQUEST["req_credit"])
	{
		$sql_rech.=" AND amount = ".$_REQUEST["req_credit"];
		$param.='&amp;req_credit='.urlencode($_REQUEST["req_credit"]);
		$mode_search = 1;
	}
	if ($_REQUEST["thirdparty"])
	{
		$sql_rech.=" AND (IFNULL(s.nom,'') like '%".$_REQUEST["thirdparty"]."%')";
		$param.='&amp;thirdparty='.urlencode($_REQUEST["thirdparty"]);
		$mode_search = 1;
	}

	$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."bank as b";
	if ($mode_search)
	{
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND bu.type='company'";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu.url_id = s.rowid";
	}
	$sql.= " WHERE b.fk_account=".$acct->id;
	$sql.= $sql_rech;
	
	dolibarr_syslog("account.php count transactions - sql=".$sql);
	$result=$db->query($sql);
	if ($result)
	{
		$obj = $db->fetch_object($result);
		$nbline = $obj->nb;
		$total_lines = $nbline;

		if ($nbline > $viewline )
		{
			$limit = $nbline - $viewline ;
		}
		else
		{
			$limit = $viewline;
		}

		$db->free($result);
	}
	else {
		dolibarr_print_error($db);
	}

	if ($page > 0)
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

	// Onglets
	$head=bank_prepare_head($acct);
	dolibarr_fiche_head($head,'journal',$langs->trans("FinancialAccount"),0);
	
	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $html->showrefnav($acct,'ref','',1,'ref');
	print '</td></tr>';

	// Label
	print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
	print '<td colspan="3">'.$acct->label.'</td></tr>';

	print '</table>';

	print '<br>';
	
	if ($mesg) print '<div class="error">'.$mesg.'</div>';

	
	/**
	* Bandeau recherche
	*/

	// Define navigation string
	$navig='';
	$navig.='<form action="'.$_SERVER["PHP_SELF"].'" name="newpage" method="GET">';
	$nbpage=floor($total_lines/$viewline)+($total_lines % $viewline > 0?1:0);  // Nombre de page total
	if ($limitsql > $viewline)
	{
		$navig.='<a href="account.php?account='.$acct->id.'&amp;page='.($page+1).$param.'">'.img_previous().'</a>';
	}
	$navig.= ' Page ';
	$navig.='<input type="text" name="negpage" size="1" class="flat" value="'.($nbpage-$page).'">';
	$navig.='<input type="hidden" name="req_desc"   value="'.$_REQUEST["req_desc"].'">';
	$navig.='<input type="hidden" name="req_debit"  value="'.$_REQUEST["req_debit"].'">';
	$navig.='<input type="hidden" name="req_credit" value="'.$_REQUEST["req_credit"].'">';
	$navig.='<input type="hidden" name="thirdparty" value="'.$_REQUEST["thirdparty"].'">';
	$navig.='<input type="hidden" name="nbpage"  value="'.$nbpage.'">';
	$navig.='<input type="hidden" name="account" value="'.($acct->id).'">';
	$navig.='/'.$nbpage.' ';
	if ($total_lines > $limitsql )
	{
		$navig.= '<a href="account.php?account='.$acct->id.'&amp;page='.($page-1).$param.'">'.img_next().'</a>';
	}
	$navig.='</form>';

	
	// Confirmation delete
	if ($_GET["action"]=='delete')
	{
		$text=$langs->trans('ConfirmDeleteTransaction');
		$html->form_confirm($_SERVER['PHP_SELF'].'?account='.$acct->id.'&amp;rowid='.$_GET["rowid"],$langs->trans('DeleteTransaction'),$text,'confirm_delete');
		print '<br />';
	}


	print '<table class="notopnoleftnoright" width="100%">';

	// Show title
	if (! $_GET["action"]=='addline' && ! $_GET["action"]=='delete')
	{
		print '<tr><td colspan="9" align="right">'.$navig.'</td></tr>';
	}

	
	// Formulaire de saisie d'une opération hors factures
	if ($user->rights->banque->modifier && $_GET["action"]=='addline')
	{
		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="vline" value="' . $vline . '">';
		print '<input type="hidden" name="account" value="' . $acct->id . '">';

		print '<tr>';
		print '<td align="left" colspan="9"><b>'.$langs->trans("AddBankRecordLong").'</b></td>';
		print '</tr>';

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>&nbsp;</td>';
		print '<td>'.$langs->trans("Type").'</td>';
		print '<td colspan="2">'.$langs->trans("Description").'</td>';
		print '<td align=right>'.$langs->trans("Debit").'</td>';
		print '<td align=right>'.$langs->trans("Credit").'</td>';
		print '<td colspan="2" align="center">&nbsp;</td>';
		print '</tr>';

		print '<tr '.$bc[false].'>';
		print '<td nowrap="nowrap" colspan="2">';
		$html->select_date(-1,'op',0,0,0,'transaction');
		print '</td>';
		print '<td nowrap="nowrap">';
		$html->select_types_paiements('','operation','1,2',1,1);
		print '<input name="num_chq" class="flat" type="text" size="4"></td>';
		print '<td colspan="2">';
		print '<input name="label" class="flat" type="text" size="32">';
		if ($nbcategories)
		{
			print '<br>'.$langs->trans("Category").': <select class="flat" name="cat1">'.$options.'</select>';
		}
		print '</td>';
		print '<td align=right><input name="debit" class="flat" type="text" size="4"></td>';
		print '<td align=right><input name="credit" class="flat" type="text" size="4"></td>';
		print '<td colspan="2" align="center">';
		print '<input type="submit" name="save" class="button" value="'.$langs->trans("Add").'"><br>';
		print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
		print '</td></tr>';
		print "</form>";

		print "<tr class=\"noborder\"><td colspan=\"8\">&nbsp;</td></tr>\n";
	}

	/*
	* Affiche tableau des transactions bancaires
	*
	*/

	// Ligne de titre tableau des ecritures
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Date").'</td>';
	print '<td>'.$langs->trans("Value").'</td>';
	print '<td>'.$langs->trans("Type").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td>'.$langs->trans("ThirdParty").'</td>';
	print '<td align="right">'.$langs->trans("Debit").'</td>';
	print '<td align="right">'.$langs->trans("Credit").'</td>';
	print '<td align="right" width="80">'.$langs->trans("BankBalance").'</td>';
	print '<td align="center" width="60">';
	if ($acct->type != 2 && $acct->rappro) print $langs->trans("AccountStatementShort");
	else print '&nbsp;';
	print '</td></tr>';

	print '<form action="'.$_SERVER["PHP_SELF"].'" name="search" method="POST">';
	print '<input type="hidden" name="action" value="search">';
	print '<input type="hidden" name="account" value="' . $acct->id . '">';

	print '<tr class="liste_titre">';
	print '<td colspan="3">&nbsp;</td>';
	print '<td><input type="text" class="flat" name="req_desc" value="'.$_REQUEST["req_desc"].'" size="24"></td>';
	print '<td><input type="text" class="flat" name="thirdparty" value="'.$_REQUEST["thirdparty"].'" size="16"></td>';
	print '<td align="right"><input type="text" class="flat" name="req_debit" value="'.$_REQUEST["req_debit"].'" size="4"></td>';
	print '<td align="right"><input type="text" class="flat" name="req_credit" value="'.$_REQUEST["req_credit"].'" size="4"></td>';
	print '<td align="center">&nbsp;</td>';
	print '<td align="center" width="40"><input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'"></td>';
	print "</tr>\n";
	print "</form>\n";

	/* Another solution
	* create temporary table solde type=heap select amount from llx_bank limit 100 ;
	* select sum(amount) from solde ;
	*/

	$sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do,".$db->pdate("b.datev")." as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type";
	if ($mode_search)
	{
		$sql.= ", s.rowid as socid, s.nom as thirdparty";
	}
	if ($mode_search && $conf->adherent->enabled)
	{

	}
	if ($mode_search && $conf->tax->enabled)
	{

	}
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	if ($mode_search)
	{
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND bu.type='company'";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu.url_id = s.rowid";
	}
	if ($mode_search && $conf->adherent->enabled)
	{
		// \TODO Mettre jointure sur adherent
		//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND bu.type='company'";
		//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu.url_id = s.rowid";
	}
	if ($mode_search && $conf->tax->enabled)
	{
		// \TODO Mettre jointure sur charges sociales
		//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND bu.type='company'";
		//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu.url_id = s.rowid";
	}
	$sql.= " WHERE fk_account=".$acct->id;
	$sql.= $sql_rech;
	$sql.= " ORDER BY b.datev ASC";
	$sql.= $db->plimit($limitsql, 0);

	dolibarr_syslog("account.php get transactions - sql=".$sql);
	$result = $db->query($sql);
	if ($result)
	{
		$total = 0;
        $time = time();

	    $var=true;
	    
	    $num = $db->num_rows($result);
	    $i = 0; $total = 0; $sep = 0;
	    
	    while ($i < $num)
	    {
	        $objp = $db->fetch_object($result);
	        $total = $total + $objp->amount;
	        if ($i >= ($nbline - $viewline))
	        {
	            $var=!$var;
	    
	            if ($objp->do > $time && !$sep)
	            {
	                $sep = 1 ;
	                print "<tr><td align=\"right\" colspan=\"6\">&nbsp;</td>";
	                print "<td align=\"right\" nowrap><b>".price($total - $objp->amount)."</b></td>";
	                print "<td>&nbsp;</td>";
	                print '</tr>';
	            }
	    
	            print "<tr $bc[$var]>";
	            print "<td nowrap>".dolibarr_print_date($objp->do,"day")."</td>\n";
	            print "<td nowrap>&nbsp;".dolibarr_print_date($objp->dv,"day")."</td>\n";
	            print "<td nowrap>&nbsp;".$objp->fk_type." ".($objp->num_chq?$objp->num_chq:"")."</td>\n";
	            print '<td><a href="ligne.php?rowid='.$objp->rowid.'&amp;account='.$acct->id.'">';
				if (eregi('^\((.*)\)$',$objp->label,$reg))
				{
					// Label générique car entre parenthèses. On l'affiche en le traduisant	
					print $langs->trans($reg[1]);
				}
				else
				{    
	            	print $objp->label;
	            }
	            print '</a>';

	            /*
	             * Ajout les liens autres que tiers
	             */
	            $links = $acct->get_url($objp->rowid);
	            foreach($links as $key=>$val)
	            {
	                if ($links[$key]['type']=='payment') {
						print ' - ';
	                    print '<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$links[$key]['url_id'].'">';
						if (eregi('^\((.*)\)$',$links[$key]['label'],$reg))
						{
							// Label générique car entre parenthèses. On l'affiche en le traduisant	
							if ($reg[1]=='paiement') $reg[1]='Payment';
							print $langs->trans($reg[1]);
						}
						else
						{    
			            	print $links[$key]['label'];
			            }
		                print '</a>';
	                }
	                else if ($links[$key]['type']=='payment_supplier') {
						print ' - ';
	                    print '<a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$links[$key]['url_id'].'">';
						if (eregi('^\((.*)\)$',$links[$key]['label'],$reg))
						{
							// Label générique car entre parenthèses. On l'affiche en le traduisant	
							if ($reg[1]=='paiement') $reg[1]='Payment';
							print $langs->trans($reg[1]);
						}
						else
						{    
			            	print $links[$key]['label'];
			            }
	                	print '</a>';
	                }
	                else if ($links[$key]['type']=='company') {
	                }
					else if ($links[$key]['type']=='sc') {
					}
					else if ($links[$key]['type']=='payment_sc') {
						print ' - ';
						print '<a href="'.DOL_URL_ROOT.'/compta/sociales/xxx.php?id='.$links[$key]['url_id'].'">';
						//print img_object($langs->trans('ShowPayment'),'payment').' ';
						print $langs->trans("SocialContributionPayment");
						print '</a>';
					}
					else if ($links[$key]['type']=='banktransfert') {
						/* Do not show this link (avoid confusion). Can be accessed from transaction detail.
						print ' - ';
						print '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$links[$key]['url_id'].'">';
						//print img_object($langs->trans('ShowPayment'),'payment').' ';
						print $langs->trans("TransactionWithOtherAccount");
						print '</a>';
						*/
					}
					else if ($links[$key]['type']=='member') {
					}
					else {
						print ' - ';
	    			    print '<a href="'.$links[$key]['url'].$links[$key]['url_id'].'">';
						if (eregi('^\((.*)\)$',$links[$key]['label'],$reg))
						{
							// Label générique car entre parenthèses. On l'affiche en le traduisant	
							if ($reg[1]=='paiement') $reg[1]='Payment';
							print $langs->trans($reg[1]);
						}
						else
						{    
			            	print $links[$key]['label'];
			            }
	                	print '</a>';
	                }
	            }
	            print '</td>';


	            /*
	             * Ajout les liens tiers
	             */
				print '<td>';
				foreach($links as $key=>$val)
	            {
	                if ($links[$key]['type']=='company') {
	                    $societestatic->id=$links[$key]['url_id'];
						$societestatic->nom=$links[$key]['label'];
						print $societestatic->getNomUrl(1,'',16);
	                }
					/*else if ($links[$key]['type']=='sc') {
	                    $chargestatic->id=$links[$key]['url_id'];
						if (eregi('^\((.*)\)$',$links[$key]['label'],$reg))
						{
							if ($reg[1]=='socialcontribution') $reg[1]='SocialContribution';
							$chargestatic->lib=$langs->trans($reg[1]);
						}
						else
						{
							$chargestatic->lib=$links[$key]['label'];
						}
						print $chargestatic->getNomUrl(1,'',16);
					}*/
					else if ($links[$key]['type']=='member') {
	                    $memberstatic->id=$links[$key]['url_id'];
						$memberstatic->ref=$links[$key]['label'];
						print $memberstatic->getNomUrl(1,16,'card');
					}
	            }
	            print '</td>';
				
	            if ($objp->amount < 0)
	            {
	                print "<td align=\"right\" nowrap>".price($objp->amount * -1)."</td><td>&nbsp;</td>\n";
	            }
	            else
	            {
	                print "<td>&nbsp;</td><td align=\"right\" nowrap>&nbsp;".price($objp->amount)."</td>\n";
	            }
	    
	            if ($action != 'search')
	            {
	                if ($total >= 0)
	                {
	                    print '<td align="right" nowrap>&nbsp;'.price($total).'</td>';
	                }
	                else
	                {
	                    print '<td align="right" class="error" nowrap>&nbsp;'.price($total).'</td>';
	                }
	            }
	            else
	            {
	                print '<td align="right">-</td>';
	            }
	    
	            // Relevé rappro ou lien edition
	            if ($objp->rappro && $acct->type != 2)  // Si non compte cash
	            {
	                print "<td align=\"center\" nowrap>&nbsp; ";
	                print "<a href=\"releve.php?num=$objp->num_releve&amp;account=$acct->id\">$objp->num_releve</a>";
	                print "</td>";
	            }
	            else
	            {
                    print '<td align="center">';
	                if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
	                {
	                    print '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$acct->id.'&amp;page='.$page.'">';
	                    print img_edit();
	                    print '</a>';
					}
					print '&nbsp;';
	                if ($user->rights->banque->modifier)
	                {
	                    print '<a href="'.DOL_URL_ROOT.'/compta/bank/account.php?action=delete&amp;rowid='.$objp->rowid.'&amp;account='.$acct->id.'&amp;page='.$page.'">';
	                    print img_delete();
	                    print '</a>';
	                }
					print '</td>';
	            }
	    
	            print "</tr>";
	    
	        }
	    
	        $i++;
	    }
		
		// Affichage total
		if ($page == 0 && $action != 'search')
		{
			print '<tr class="liste_total"><td align="left" colspan="7">'.$langs->trans("CurrentBalance").'</td>';
			print '<td align="right" nowrap>'.price($total).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		$db->free($result);
	}
	else
	{
		dolibarr_print_error($db);
	}


	print "</table>";

	print "\n</div>\n";

	/*
	*  Boutons actions
	*/
	if ($_GET["action"] != 'addline' && $_GET["action"] != 'delete')
	{
		print '<div class="tabsAction">';

		if ($acct->type != 2 && $acct->rappro)  // Si non compte cash et rapprochable
		{
			if ($user->rights->banque->consolidate)
			{
				print '<a class="butAction" href="rappro.php?account='.$acct->id.'">'.$langs->trans("Conciliate").'</a>';
			}
			else
			{
				print '<font class="butActionRefused">'.$langs->trans("Conciliate").'</font>';
			}
		}

		if ($user->rights->banque->modifier)
		{
			print '<a class="butAction" href="account.php?action=addline&amp;account='.$acct->id.'&amp;page='.$page.'">'.$langs->trans("AddBankRecord").'</a>';
		}
		else
		{
			print '<font class="butActionRefused">'.$langs->trans("AddBankRecord").'</font>';
		}

		print '</div>';
	}

	print '<br>';

}
else
{
	print $langs->trans("ErrorBankAccountNotFound");
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
