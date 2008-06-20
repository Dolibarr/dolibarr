<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
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
 */

/**
	    \file       htdocs/comm/remx.php
        \ingroup    commercial, invoice
		\brief      Onglet de définition des avoirs
		\version    $Id$
*/

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");

$langs->load("orders");
$langs->load("bills");
$langs->load("companies");

// Sécurité si un client essaye d'accéder à une autre fiche que la sienne
$_socid = $_GET["id"];
if ($user->societe_id > 0) 
{
  $_socid = $user->societe_id;
}


/*
 * Actions
 */

if ($_POST["action"] == 'setremise')
{
	if (price2num($_POST["amount_ht"]) > 0)
	{
		if (! $_POST["desc"]) 
		{
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("ReasonDiscount")).'</div>';
		}

		$soc = new Societe($db);
		$soc->fetch($_GET["id"]);
		$soc->set_remise_except($_POST["amount_ht"],$user,$_POST["desc"],$_POST["tva_tx"]);
		
		if ($result > 0)
		{
			Header("Location: remx.php?id=".$_GET["id"]);
			exit;
		}
		else
		{
			$mesg='<div class="error">'.$soc->error.'</div>';
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldFormat",$langs->trans("NewGlobalDiscount")).'</div>';
	}
}

if ($_GET["action"] == 'remove')
{
	$db->begin();
	
	$soc = new Societe($db);
	$soc->fetch($_GET["id"]);
	$result=$soc->del_remise_except($_GET["remid"]);

	if ($result > 0)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
		$mesg='<div class="error">'.$soc->error.'</div>';
	}
}


/*
 * Affichage fiche des remises fixes
 */

$form=new Form($db);
$facturestatic=new Facture($db);
 
llxHeader();

if ($_socid > 0)
{
	if ($mesg) print $mesg."<br>";
	
	// On recupere les donnees societes par l'objet
	$objsoc = new Societe($db);
	$objsoc->id=$_socid;
	$objsoc->fetch($_socid,$to);
	
	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($objsoc);

	dolibarr_fiche_head($head, 'absolutediscount', $objsoc->nom);


    print '<form method="POST" action="remx.php?id='.$objsoc->id.'">';
    print '<input type="hidden" name="action" value="setremise">';

    print '<table class="border" width="100%">';

	// Calcul avoirs en cours
    $remise_all=$remise_user=0;
    $sql = "SELECT SUM(rc.amount_ht) as amount, rc.fk_user";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc";
    $sql.= " WHERE rc.fk_soc =". $objsoc->id;
    $sql.= " AND (fk_facture_line IS NULL AND fk_facture IS NULL)";
    $sql.= " GROUP BY rc.fk_user";
    $resql=$db->query($sql);
    if ($resql)
    {
        $obj = $db->fetch_object($resql);
        $remise_all+=$obj->amount;
        if ($obj->fk_user == $user->id) $remise_user+=$obj->amount;
    }
    else
    {
    	dolibarr_print_error($db);
    }

    print '<tr><td width="38%">'.$langs->trans("CustomerAbsoluteDiscountAllUsers").'</td>';
    print '<td>'.$remise_all.'&nbsp;'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

    print '<tr><td>'.$langs->trans("CustomerAbsoluteDiscountMy").'</td>';
    print '<td>'.$remise_user.'&nbsp;'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
    print "</table>";
	print '<br>';
	
    print '<table class="border" width="100%">';
    print '<tr><td width="38%">'.$langs->trans("NewGlobalDiscount").'</td>';
    print '<td><input type="text" size="5" name="amount_ht" value="'.$_POST["amount_ht"].'">&nbsp;'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
    print '<tr><td width="38%">'.$langs->trans("VAT").'</td>';
    print '<td>';
	$form->select_tva('tva_tx','0','',$mysoc,'');
	print '</td></tr>';
    print '<tr><td>'.$langs->trans("NoteReason").'</td>';
    print '<td><input type="text" size="60" name="desc" value="'.$_POST["desc"].'"></td></tr>';
    
    print '<tr><td align="center" colspan="2"><input type="submit" class="button" value="'.$langs->trans("AddGlobalDiscount").'"></td></tr>';
        
    print "</table></form>";

    print "</div>\n";    

    print '<br>';        


    /*
     * Liste remises fixes restant en cours (= liees a acune facture ni ligne de facture)
     */
    $sql = "SELECT rc.rowid, rc.amount_ht, rc.amount_tva, rc.amount_ttc, rc.tva_tx,";
	$sql.= $db->pdate("rc.datec")." as dc, rc.description,";
    $sql.= " rc.fk_facture_source,";
    $sql.= " u.login, u.rowid as user_id,";
	$sql.= " fa.facnumber as ref, fa.type as type";
    $sql.= " FROM  ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."societe_remise_except as rc";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa ON rc.fk_facture_source = fa.rowid";
	$sql.= " WHERE rc.fk_soc =". $objsoc->id;
    $sql.= " AND u.rowid = rc.fk_user";
	$sql.= " AND (rc.fk_facture_line IS NULL AND rc.fk_facture IS NULL)";
    $sql.= " ORDER BY rc.datec DESC";

    $resql=$db->query($sql);
    if ($resql)
    {
        print_titre($langs->trans("DiscountStillRemaining"));
        print '<table width="100%" class="noborder">';
        print '<tr class="liste_titre"><td width="120">'.$langs->trans("Date").'</td>';
        print '<td>'.$langs->trans("ReasonDiscount").'</td>';
        print '<td width="120" align="right">'.$langs->trans("AmountHT").'</td>';
        print '<td width="80" align="right">'.$langs->trans("VATRate").'</td>';
        print '<td width="120" align="right">'.$langs->trans("AmountTTC").'</td>';
        print '<td align="center" width="100">'.$langs->trans("DiscountOfferedBy").'</td>';
        print '<td width="20">&nbsp;</td>';
        print '</tr>';

        $var = true;
        $i = 0 ;
        $num = $db->num_rows($resql);
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            $var = !$var;
            print "<tr $bc[$var]>";
            print '<td>'.dolibarr_print_date($obj->dc,'dayhour').'</td>';
            print '<td>';
			if ($obj->description == '(CREDIT_NOTE)')
			{
				$facturestatic->id=$obj->fk_facture_source;
				$facturestatic->ref=$obj->ref;
				$facturestatic->type=$obj->type;
				print $langs->trans("CreditNote").' '.$facturestatic->getNomURl(1);
			}
			else
			{
				print $obj->description;
			}
			print '</td>';
            print '<td align="right">'.price($obj->amount_ht).'</td>';
            print '<td align="right">'.price2num($obj->tva_tx,'MU').'%</td>';
            print '<td align="right">'.price($obj->amount_ttc).'</td>';
            print '<td align="center">';
			print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login;
			print '</td>';
			if ($obj->user_id == $user->id) print '<td><a href="'.$_SERVER["PHP_SELF"].'?id='.$objsoc->id.'&amp;action=remove&amp;remid='.$obj->rowid.'">'.img_delete($langs->trans("RemoveDiscount")).'</td>';
            else print '<td>&nbsp;</td>';
            print '</tr>';
            $i++;
        }
        $db->free($resql);
        print "</table>";
    }
    else
    {
    	dolibarr_print_error($db);
    }

    print '<br>';

    /*
     * Liste ristournes appliquées (=liees a une ligne de facture ou facture)
     */
    // Remises liees a lignes de factures
    $sql = "SELECT rc.rowid, rc.amount_ht, rc.amount_tva, rc.amount_ttc, rc.tva_tx,";
	$sql.= $db->pdate("rc.datec")." as dc, rc.description, rc.fk_facture_line, rc.fk_facture,";
    $sql.= " rc.fk_facture_source,";
	$sql.= " u.login, u.rowid as user_id,";
    $sql.= " f.rowid, f.facnumber,";
	$sql.= " fa.facnumber as ref, fa.type as type";
    $sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
    $sql.= " , ".MAIN_DB_PREFIX."user as u";
    $sql.= " , ".MAIN_DB_PREFIX."facturedet as fc";
    $sql.= " , ".MAIN_DB_PREFIX."societe_remise_except as rc";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa ON rc.fk_facture_source = fa.rowid";
    $sql.= " WHERE rc.fk_soc =". $objsoc->id;
    $sql.= " AND rc.fk_facture_line = fc.rowid";
    $sql.= " AND fc.fk_facture = f.rowid";
    $sql.= " AND rc.fk_user = u.rowid";
	$sql.= " ORDER BY dc DESC";
	//$sql.= " UNION ";
    // Remises liees a factures
	$sql2= "SELECT rc.rowid, rc.amount_ht, rc.amount_tva, rc.amount_ttc, rc.tva_tx,";
	$sql2.= $db->pdate("rc.datec")." as dc, rc.description, rc.fk_facture_line, rc.fk_facture,";
    $sql2.= " rc.fk_facture_source,";
	$sql2.= " u.login, u.rowid as user_id,";
    $sql2.= " f.rowid, f.facnumber,";
	$sql2.= " fa.facnumber as ref, fa.type as type";
    $sql2.= " FROM ".MAIN_DB_PREFIX."facture as f";
    $sql2.= " , ".MAIN_DB_PREFIX."user as u";
    $sql2.= " , ".MAIN_DB_PREFIX."societe_remise_except as rc";
    $sql2.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa ON rc.fk_facture_source = fa.rowid";
    $sql2.= " WHERE rc.fk_soc =". $objsoc->id;
    $sql2.= " AND rc.fk_facture = f.rowid";
    $sql2.= " AND rc.fk_user = u.rowid";
	
	$sql2.= " ORDER BY dc DESC";

    $resql=$db->query($sql);
	$resql2=null;
    if ($resql) $resql2=$db->query($sql2);
	if ($resql2)
    { 
        print_titre($langs->trans("DiscountAlreadyCounted"));
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre"><td width="120">'.$langs->trans("Date").'</td>';
        print '<td>'.$langs->trans("ReasonDiscount").'</td>';
        print '<td align="left">'.$langs->trans("Invoice").'</td>';
        print '<td width="120" align="right">'.$langs->trans("AmountHT").'</td>';
        print '<td width="80" align="right">'.$langs->trans("VATRate").'</td>';
        print '<td width="120" align="right">'.$langs->trans("AmountTTC").'</td>';
        print '<td align="center" width="100">'.$langs->trans("Author").'</td>';
        print '<td width="20">&nbsp;</td>';
        print '</tr>';

        $var = true;
		$tab_sqlobj=array();
		$tab_sqlobjOrder=array();
        $num = $db->num_rows($resql);
		for ($i = 0;$i < $num;$i++)
			{
			$sqlobj = $db->fetch_object($resql);
			$tab_sqlobj[] = $sqlobj;
			$tab_sqlobjOrder[]=$sqlobj->dc;
			}
		$db->free($resql);	
		
		$num = $db->num_rows($resql2);
		for ($i = 0;$i < $num;$i++)
			{
			$sqlobj = $db->fetch_object($resql2);
			$tab_sqlobj[] = $sqlobj;
			$tab_sqlobjOrder[]= $sqlobj->dc;
			}
		$db->free($resql2);
		array_multisort ($tab_sqlobjOrder,SORT_DESC,$tab_sqlobj);
		
		$num = sizeOf($tab_sqlobj);
		$i = 0 ;
        while ($i < $num )
        {
            $obj = array_shift($tab_sqlobj);
            $var = !$var;
            print "<tr $bc[$var]>";
            print '<td>'.dolibarr_print_date($obj->dc,'dayhour').'</td>';
            print '<td>';
			if ($obj->description == '(CREDIT_NOTE)')
			{
				$facturestatic->id=$obj->fk_facture_source;
				$facturestatic->ref=$obj->ref;
				$facturestatic->type=$obj->type;
				print $langs->trans("CreditNote").' '.$facturestatic->getNomURl(1);
			}
			else
			{
				print $obj->description;
			}
			print '</td>';
            print '<td align="left"><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->rowid.'">'.img_object($langs->trans("ShowBill"),'bill').' '.$obj->facnumber.'</a></td>';
            print '<td align="right">'.price($obj->amount_ht).'</td>';
            print '<td align="right">'.price2num($obj->tva_tx,'MU').'%</td>';
            print '<td align="right">'.price($obj->amount_ttc).'</td>';
            print '<td align="center">';
			print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login;
			print '</td>';
            print '<td>&nbsp;</td>';
            print '</tr>';
            $i++;
        }
        print "</table>";
    }
    else
    {
        print dolibarr_print_error($db);
    }

}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
