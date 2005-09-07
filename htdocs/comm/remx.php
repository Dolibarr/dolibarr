<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
	    \file       htdocs/comm/remx.php
        \ingroup    commercial
		\brief      Onglet de définition des avoirs
		\version    $Revision$
*/

require_once("./pre.inc.php");

$user->getrights('propale');
$user->getrights('commande');
$user->getrights('projet');


$langs->load("orders");
$langs->load("bills");
$langs->load("companies");


if ($_POST["action"] == 'setremise')
{
  $soc = New Societe($db);
  $soc->fetch($_GET["id"]);
  $soc->set_remise_except($_POST["remise"],$user);

  Header("Location: remx.php?id=".$_GET["id"]);
}


llxHeader();

$_socid = $_GET["id"];

// Sécurité si un client essaye d'accéder à une autre fiche que la sienne
if ($user->societe_id > 0) 
{
  $_socid = $user->societe_id;
}


/*********************************************************************************
 *
 * Mode fiche
 *
 *
 *********************************************************************************/  
if ($_socid > 0)
{
  // On recupere les donnees societes par l'objet
  $objsoc = new Societe($db);
  $objsoc->id=$_socid;
  $objsoc->fetch($_socid,$to);
  
  $dac = strftime("%Y-%m-%d %H:%M", time());
  if ($errmesg)
    {
      print "<b>$errmesg</b><br>";
    }
  
  /*
   * Affichage onglets
   */
  $h = 0;
  
  $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$objsoc->id;
  $head[$h][1] = $langs->trans("Company");
  $h++;
  
  if ($objsoc->client==1)
    {
      $hselected=$h;
      $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$objsoc->id;
      $head[$h][1] = $langs->trans("Customer");
      $h++;
    }
  if ($objsoc->client==2)
    {
      $hselected=$h;
      $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$obj->socid;
      $head[$h][1] = $langs->trans("Prospect");
      $h++;
    }
  if ($objsoc->fournisseur)
    {
      $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$objsoc->id;
      $head[$h][1] = $langs->trans("Supplier");
      $h++;
    }
  
  if ($conf->compta->enabled) {
    $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Accountancy");
    $h++;
  }

    $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Note");
    $h++;

    if ($user->societe_id == 0)
    {
        $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$objsoc->id;
        $head[$h][1] = $langs->trans("Documents");
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Notifications");
    $h++;
    
    if ($user->societe_id == 0)
      {
	$head[$h][0] = DOL_URL_ROOT."/comm/index.php?socidp=$objsoc->id&action=add_bookmark";
	$head[$h][1] = img_object($langs->trans("BookmarkThisPage"),'bookmark');
	$head[$h][2] = 'image';
      }
      
    dolibarr_fiche_head($head, $hselected, $objsoc->nom);

    /*
     *
     *
     */
    print '<form method="POST" action="remx.php?id='.$objsoc->id.'">';
    print '<input type="hidden" name="action" value="setremise">';

    print '<table class="border" width="100%">';

    $remise_all=$remise_user=0;
    $sql  = "SELECT rc.amount_ht,".$db->pdate("rc.datec")." as dc, rc.fk_user";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc";
    $sql .= " WHERE rc.fk_soc =". $objsoc->id;
    $sql .= " AND fk_facture IS NULL";
    $resql=$db->query($sql);
    if ($resql)
    {
        $obj = $db->fetch_object($resql);
        $remise_all+=$obj->amount_ht;
        if ($obj->fk_user == $user->id) $remise_user+=$obj->amount_ht;
    }

    print '<tr><td width="33%">'.$langs->trans("CustomerAbsoluteDiscountAllUsers").'</td>';
    print '<td>'.$remise_all.'&nbsp;'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

    print '<tr><td width="33%">'.$langs->trans("CustomerAbsoluteDiscountMy").'</td>';
    print '<td>'.$remise_user.'&nbsp;'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
    print '<tr><td>'.$langs->trans("NewValue").'</td>';
    print '<td><input type="text" size="5" name="remise" value="'.$remise_user.'">&nbsp;'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
    
    print '<tr><td align="center" colspan="2">&nbsp;<input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';
        
    print "</table></form>";

    print "</td>\n";    
    print "</div>\n";    

    print '<br>';        

    /*
     * Liste
     */
    $sql  = "SELECT rc.amount_ht,".$db->pdate("rc.datec")." as dc, u.code, u.rowid as user_id";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc";
    $sql .= " , ".MAIN_DB_PREFIX."user as u";
    $sql .= " WHERE rc.fk_soc =". $objsoc->id;
    $sql .= " AND u.rowid = rc.fk_user AND fk_facture IS NULL";
    $sql .= " ORDER BY rc.datec DESC";

    $resql=$db->query($sql);
    if ($resql)
    {
        print_titre($langs->trans("Ristournes restant dues"));
        print '<table width="100%" class="noborder">';
        print '<tr class="liste_titre"><td width="80">'.$langs->trans("Date").'</td>';
        print '<td width="120" align="right">'.$langs->trans("AmountTTC").'</td><td>&nbsp;</td><td width="100">'.$langs->trans("Accordée par").'</td></tr>';

        $var = true;
        $i = 0 ;
        $num = $db->num_rows($resql);
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            $var = !$var;
            print "<tr $bc[$var]>";
            print '<td>'.dolibarr_print_date($obj->dc).'</td>';
            print '<td align="right">'.price($obj->amount_ht).'</td>';
            print '<td>&nbsp;</td>';
            print '<td><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->code.'</td></tr>';
    
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
    $sql  = "SELECT rc.amount_ht,".$db->pdate("rc.datec")." as dc, u.code, u.rowid as user_id,";
    $sql.= " rc.fk_facture, f.facnumber";
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
        print_titre($langs->trans("Ristournes appliquées"));
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre"><td width="80">'.$langs->trans("Date").'</td>';
        print '<td width="120" align="right">'.$langs->trans("AmountTTC").'</td><td align="center">'.$langs->trans("Bill").'</td><td width="100">'.$langs->trans("Author").'</td></tr>';

        $var = true;
        $i = 0 ;
        $num = $db->num_rows($resql);
        while ($i < $num )
        {
            $obj = $db->fetch_object($resql);
            $var = !$var;
            print "<tr $bc[$var]>";
            print '<td>'.dolibarr_print_date($obj->dc).'</td>';
            print '<td align="right">'.price($obj->amount_ht).'</td>';
            print '<td align="center"><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->fk_facture.'">'.img_object($langs->trans("ShowBill"),'bill').' '.$obj->facnumber.'</a></td>';
            print '<td>'.$obj->code.'</td></tr>';
    
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
