<?PHP
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	 \file       htdocs/fourn/commande/index.php
 *	 \ingroup    commande fournisseur
 *	 \brief      Home page of supplier's orders area
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");

// Security check
$orderid = isset($_GET["orderid"])?$_GET["orderid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande_fournisseur', $orderid,'');

$langs->load("suppliers");
$langs->load("orders");


/*
 * 	View
 */

llxHeader('',$langs->trans("SuppliersOrdersArea"));

$commandestatic = new CommandeFournisseur($db);
$userstatic=new User($db);
$formfile = new FormFile($db);

print_barre_liste($langs->trans("SuppliersOrdersArea"), $page, "index.php", "", $sortfield, $sortorder, '', $num);

print '<table class="notopnoleftnoright" width="100%">';
print '<tr valign="top"><td class="notopnoleft" width="30%">';


/*
 * Search form
 */
$var=false;
print '<table class="noborder" width="100%">';
print '<form method="post" action="liste.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchOrder").'</td></tr>';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("Ref").':</td><td><input type="text" class="flat" name="search_ref" size=18></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print '<tr '.$bc[$var].'><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="search_all" size="18"></td>';
print '</tr>';
print "</form></table><br>\n";


/*
 * Statistics
 */

$sql = "SELECT count(cf.rowid), fk_statut";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."commande_fournisseur as cf";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE cf.fk_soc = s.rowid";
$sql.= " AND s.entity = ".$conf->entity;
if ($user->societe_id) $sql.=' AND cf.fk_soc = '.$user->societe_id;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " GROUP BY cf.fk_statut";

$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    $var=True;

    $total=0;
    $totalinprocess=0;
    $dataseries=array();
    $vals=array();
    //    0=Draft -> 1=Validated -> 2=Approved -> 3=Process runing -> 4=Received partially -> 5=Received totally -> (reopen) 4=Received partially
    //                                                          -> 7=Canceled/Never received -> (reopen) 3=Process runing
    //                                      -> 6=Canceled -> (reopen) 2=Approved
    while ($i < $num)
    {
        $row = $db->fetch_row($resql);
        if ($row)
        {
            if ($row[1]!=7 && $row[1]!=6 && $row[1]!=5)
            {
                $vals[$row[1]]=$row[0];
                $totalinprocess+=$row[0];
            }
            $total+=$row[0];
        }
        $i++;
    }
    $db->free($resql);

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("SuppliersOrders").'</td></tr>';
    print "</tr>\n";
    foreach (array(0,1,2,3,4,5,6) as $statut)
    {
        $dataseries[]=array('label'=>$commandestatic->LibStatut($statut,1),'data'=>(isset($vals[$statut])?(int) $vals[$statut]:0));
        if (! $conf->use_javascript_ajax)
        {
            $var=!$var;
            print "<tr ".$bc[$var].">";
            print '<td>'.$commandestatic->LibStatut($statut,0).'</td>';
            print '<td align="right"><a href="liste.php?statut='.$statut.'">'.(isset($vals[$statut])?$vals[$statut]:0).'</a></td>';
            print "</tr>\n";
        }
    }
    if ($conf->use_javascript_ajax)
    {
        print '<tr><td align="center" colspan="2">';
        $data=array('series'=>$dataseries);
        dol_print_graph('stats',300,180,$data,1,'pie',1);
        print '</td></tr>';
    }
    //if ($totalinprocess != $total)
    //print '<tr class="liste_total"><td>'.$langs->trans("Total").' ('.$langs->trans("SuppliersOrdersRunning").')</td><td align="right">'.$totalinprocess.'</td></tr>';
    print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">'.$total.'</td></tr>';

    print "</table><br>";
}
else
{
    dol_print_error($db);
}

/*
 * Commandes brouillons
 */
if ($conf->fournisseur->enabled)
{
    $sql = "SELECT c.rowid, c.ref, s.nom, s.rowid as socid";
    $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
    $sql.= ", ".MAIN_DB_PREFIX."societe as s";
    if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE c.fk_soc = s.rowid";
    $sql.= " AND c.entity = ".$conf->entity;
    $sql.= " AND c.fk_statut = 0";
    if ($socid) $sql.= " AND c.fk_soc = ".$socid;
    if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

    $resql=$db->query($sql);
    if ($resql)
    {
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td colspan="2">'.$langs->trans("DraftOrders").'</td></tr>';
        $langs->load("orders");
        $num = $db->num_rows($resql);
        if ($num)
        {
            $i = 0;
            $var = True;
            while ($i < $num)
            {
                $var=!$var;
                $obj = $db->fetch_object($resql);
                print "<tr $bc[$var]>";
                print '<td nowrap="nowrap">';
                print "<a href=\"fiche.php?id=".$obj->rowid."\">".img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref."</a></td>";
                print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($obj->nom,24).'</a></td></tr>';
                $i++;
            }
        }
        print "</table><br>";
    }
}


/*
 * List of users allowed
 */
$sql = "SELECT u.rowid, u.name, u.firstname";
$sql.= " FROM ".MAIN_DB_PREFIX."user as u,";
$sql.= " ".MAIN_DB_PREFIX."user_rights as ur";
$sql.= ", ".MAIN_DB_PREFIX."rights_def as rd";
$sql.= " WHERE u.rowid = ur.fk_user";
$sql.= " AND (u.entity IN (0,".$conf->entity.")";
$sql.= " AND rd.entity = ".$conf->entity.")";
$sql.= " AND ur.fk_id = rd.id";
$sql.= " AND module = 'fournisseur'";
$sql.= " AND perms = 'commande'";
$sql.= " AND subperms = 'approuver'";

$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    print '<table class="liste" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("UserWithApproveOrderGrant").'</td>';
    print "</tr>\n";
    $var=True;

    while ($i < $num)
    {
        $obj = $db->fetch_object($resql);
        $var=!$var;

        print "<tr $bc[$var]>";
        print '<td>';
        $userstatic->id=$obj->rowid;
        $userstatic->nom=$obj->name;
        $userstatic->prenom=$obj->firstname;
        print $userstatic->getNomUrl(1);
        print '</td>';
        print "</tr>\n";
        $i++;
    }
    print "</table><br>";
    $db->free($resql);
}
else
{
    dol_print_error($db);
}


print '</td><td width="70%" valign="top" class="notopnoleftnoright">';


/*
 * Last modified orders
 */
$max=5;

$sql = "SELECT c.rowid, c.ref, c.fk_statut, s.nom, s.rowid as socid,";
$sql.= " date_cloture as datec";
$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE c.fk_soc = s.rowid";
$sql.= " AND c.entity = ".$conf->entity;
//$sql.= " AND c.fk_statut > 2";
if ($socid) $sql .= " AND c.fk_soc = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " ORDER BY c.tms DESC";
$sql.= $db->plimit($max, 0);

$resql=$db->query($sql);
if ($resql)
{
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="4">'.$langs->trans("LastModifiedOrders",$max).'</td></tr>';

    $num = $db->num_rows($resql);
    if ($num)
    {
        $i = 0;
        $var = True;
        while ($i < $num)
        {
            $var=!$var;
            $obj = $db->fetch_object($resql);

            print "<tr $bc[$var]>";
            print '<td width="20%" nowrap="nowrap">';

            $commandestatic->id=$obj->rowid;
            $commandestatic->ref=$obj->ref;

            print '<table class="nobordernopadding"><tr class="nocellnopadd">';
            print '<td width="96" class="nobordernopadding" nowrap="nowrap">';
            print $commandestatic->getNomUrl(1);
            print '</td>';

            print '<td width="16" class="nobordernopadding" nowrap="nowrap">';
            print '&nbsp;';
            print '</td>';

            print '<td width="16" align="right" class="nobordernopadding">';
            $filename=dol_sanitizeFileName($obj->ref);
            $filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
            $urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
            $formfile->show_documents('commande',$filename,$filedir,$urlsource,'','','',1,'',1);
            print '</td></tr></table>';

            print '</td>';

            print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
            print '<td>'.dol_print_date($db->jdate($obj->datec)).'</td>';
            print '<td align="right">'.$commandestatic->LibStatut($obj->fk_statut,5).'</td>';
            print '</tr>';
            $i++;
        }
    }
    print "</table><br>";
}
else dol_print_error($db);


/*
 * Orders to process
 */
/*
 $sql = "SELECT c.rowid, c.ref, c.fk_statut, s.nom, s.rowid as socid";
 $sql.=" FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
 $sql.= ", ".MAIN_DB_PREFIX."societe as s";
 if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
 $sql.= " WHERE c.fk_soc = s.rowid";
 $sql.= " AND c.entity = ".$conf->entity;
 $sql.= " AND c.fk_statut = 1";
 if ($socid) $sql.= " AND c.fk_soc = ".$socid;
 if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
 $sql.= " ORDER BY c.rowid DESC";

 $resql=$db->query($sql);
 if ($resql)
 {
 $num = $db->num_rows($resql);

 print '<table class="noborder" width="100%">';
 print '<tr class="liste_titre">';
 print '<td colspan="3">'.$langs->trans("OrdersToProcess").' <a href="'.DOL_URL_ROOT.'/commande/liste.php?viewstatut=1">('.$num.')</a></td></tr>';

 if ($num)
 {
 $i = 0;
 $var = True;
 while ($i < $num)
 {
 $var=!$var;
 $obj = $db->fetch_object($resql);
 print "<tr $bc[$var]>";
 print '<td nowrap="nowrap">';

 $commandestatic->id=$obj->rowid;
 $commandestatic->ref=$obj->ref;

 print '<table class="nobordernopadding"><tr class="nocellnopadd">';
 print '<td width="96" class="nobordernopadding" nowrap="nowrap">';
 print $commandestatic->getNomUrl(1);
 print '</td>';

 print '<td width="16" class="nobordernopadding" nowrap="nowrap">';
 print '&nbsp;';
 print '</td>';

 print '<td width="16" align="right" class="nobordernopadding">';
 $filename=dol_sanitizeFileName($obj->ref);
 $filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
 $urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
 $formfile->show_documents('commande',$filename,$filedir,$urlsource,'','','',1,'',1);
 print '</td></tr></table>';

 print '</td>';

 print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($obj->nom,24).'</a></td>';

 print '<td align="right">'.$commandestatic->LibStatut($obj->fk_statut,$obj->facture,5).'</td>';

 print '</tr>';
 $i++;
 }
 }

 print "</table><br>";
 }
 */

print '</td></tr></table>';

$db->close();

llxFooter();
?>
