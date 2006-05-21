<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/comm/remx.php
        \ingroup    commercial
		\brief      Onglet de définition des avoirs
		\version    $Revision$
*/

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

$user->getrights('propale');
$user->getrights('commande');
$user->getrights('projet');

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
	$soc = New Societe($db);
	$soc->fetch($_GET["id"]);
	$soc->set_remise_except($_POST["remise"],$user,$_POST["desc"]);
	
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

if ($_GET["action"] == 'remove')
{
	$soc = New Societe($db);
	$soc->fetch($_GET["id"]);
	$result=$soc->del_remise_except($_GET["remid"]);

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


/*
 * Fiche avoirs
 */

llxHeader();

if ($_socid > 0)
{
	if ($mesg) print "$mesg<br>";
	
	// On recupere les donnees societes par l'objet
	$objsoc = new Societe($db);
	$objsoc->id=$_socid;
	$objsoc->fetch($_socid,$to);
	
	$dac = strftime("%Y-%m-%d %H:%M", time());
	  
	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($objsoc);

	dolibarr_fiche_head($head, 'absolutediscount', $objsoc->nom);

    /*
     *
     *
     */
    print '<form method="POST" action="remx.php?id='.$objsoc->id.'">';
    print '<input type="hidden" name="action" value="setremise">';

    print '<table class="border" width="100%">';

	// Calcul avoirs en cours
    $remise_all=$remise_user=0;
    $sql = "SELECT SUM(rc.amount_ht) as amount, rc.fk_user";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc";
    $sql.= " WHERE rc.fk_soc =". $objsoc->id;
    $sql.= " AND fk_facture IS NULL";
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

    print '<tr><td width="33%">'.$langs->trans("CustomerAbsoluteDiscountAllUsers").'</td>';
    print '<td>'.$remise_all.'&nbsp;'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

    print '<tr><td width="33%">'.$langs->trans("CustomerAbsoluteDiscountMy").'</td>';
    print '<td>'.$remise_user.'&nbsp;'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
    print "</table>";
	print '<br>';
	
    print '<table class="border" width="100%">';
    print '<tr><td width="33%">'.$langs->trans("NewGlobalDiscount").'</td>';
    print '<td><input type="text" size="5" name="remise" value="'.$_POST["remise"].'">&nbsp;'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
    print '<tr><td width="33%">'.$langs->trans("NoteReason").'</td>';
    print '<td><input type="text" size="60" name="desc" value="'.$_POST["desc"].'"></td></tr>';
    
    print '<tr><td align="center" colspan="2"><input type="submit" class="button" value="'.$langs->trans("AddGlobalDiscount").'"></td></tr>';
        
    print "</table></form>";

    print "</td>\n";    
    print "</div>\n";    

    print '<br>';        


    /*
     * Liste avoir restant dus
     */
    $sql  = "SELECT rc.rowid, rc.amount_ht,".$db->pdate("rc.datec")." as dc, rc.description,";
    $sql.= " u.code, u.rowid as user_id";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc, ".MAIN_DB_PREFIX."user as u";
    $sql.= " WHERE rc.fk_soc =". $objsoc->id;
    $sql.= " AND u.rowid = rc.fk_user AND fk_facture IS NULL";
    $sql.= " ORDER BY rc.datec DESC";

    $resql=$db->query($sql);
    if ($resql)
    {
        print_titre($langs->trans("DiscountStillRemaining"));
        print '<table width="100%" class="noborder">';
        print '<tr class="liste_titre"><td width="80">'.$langs->trans("Date").'</td>';
        print '<td>'.$langs->trans("ReasonDiscount").'</td>';
        print '<td width="120" align="right">'.$langs->trans("Amount").'</td>';
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
            print '<td>'.dolibarr_print_date($obj->dc).'</td>';
            print '<td>'.$obj->description.'</td>';
            print '<td align="right">'.price($obj->amount_ht).'</td>';
            print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->code.'</td>';
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

    print '<br />';

    /*
     * Liste ristournes appliquées
     */
    $sql = "SELECT rc.rowid, rc.amount_ht,".$db->pdate("rc.datec")." as dc, rc.description, rc.fk_facture,";
    $sql.= " u.code, u.rowid as user_id,";
    $sql.= " f.facnumber";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc";
    $sql.= " , ".MAIN_DB_PREFIX."user as u";
    $sql.= " , ".MAIN_DB_PREFIX."facture as f";
    $sql.= " WHERE rc.fk_soc =". $objsoc->id;
    $sql.= " AND fk_facture = f.rowid";
    $sql.= " AND u.rowid = rc.fk_user AND fk_facture IS NOT NULL";
    $sql.= " ORDER BY rc.datec DESC";

    $resql=$db->query($sql);
    if ($resql)
    {
        print_titre($langs->trans("DiscountAlreadyCounted"));
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre"><td width="80">'.$langs->trans("Date").'</td>';
        print '<td>'.$langs->trans("ReasonDiscount").'</td>';
        print '<td width="120" align="right">'.$langs->trans("Amount").'</td>';
        print '<td align="center">'.$langs->trans("Bill").'</td>';
        print '<td align="center" width="100">'.$langs->trans("Author").'</td>';
        print '<td width="20">&nbsp;</td>';
        print '</tr>';

        $var = true;
        $i = 0 ;
        $num = $db->num_rows($resql);
        while ($i < $num )
        {
            $obj = $db->fetch_object($resql);
            $var = !$var;
            print "<tr $bc[$var]>";
            print '<td>'.dolibarr_print_date($obj->dc).'</td>';
            print '<td>'.$obj->description.'</td>';
            print '<td align="right">'.price($obj->amount_ht).'</td>';
            print '<td align="center"><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->fk_facture.'">'.img_object($langs->trans("ShowBill"),'bill').' '.$obj->facnumber.'</a></td>';
            print '<td align="center">'.$obj->code.'</td>';
            print '<td>&nbsp;</td>';
            print '</tr>';
            $i++;
        }
        $db->free($resql);
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
