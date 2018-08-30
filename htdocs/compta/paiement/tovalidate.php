<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * 	\file       htdocs/compta/paiement/tovalidate.php
 * 	\ingroup    compta
 * 	\brief      Page list payment to validate. Visible in menu when option BILL_ADD_PAYMENT_VALIDATION is on.
 */

require '../../main.inc.php';

// Load translation files required by the page
$langs->load("bills");

// Security check
if (! $user->rights->facture->lire)
  accessforbidden();

$socid=0;
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}


$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.rowid";


/*
 * Actions
 */



/*
 * View
 */

llxHeader();

$sql = "SELECT p.rowid, p.datep as dp, p.amount, p.statut";
$sql.=", c.libelle as paiement_type, p.num_paiement";
$sql.= " FROM ".MAIN_DB_PREFIX."paiement as p LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_paiement = c.id";
if ($socid)
{
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON pf.fk_facture = f.rowid";
}
$sql.= " WHERE p.entity IN (" . getEntity('facture') . ')';
if ($socid)
{
    $sql.= " AND f.fk_soc = ".$socid;
}
$sql.= " AND p.statut = 0";

$sql.= $db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
    	$page = 0;
    	$offset = 0;
    }
}

$sql.= $db->plimit($limit + 1,$offset);

$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    print_barre_liste($langs->trans("ReceivedCustomersPaymentsToValid"), $page, $_SERVER["PHP_SELF"],"",$sortfield,$sortorder,'',$num);

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre("Ref",$_SERVER["PHP_SELF"],"p.rowid","","",'width="60"',$sortfield,$sortorder);
    print_liste_field_titre("Date",$_SERVER["PHP_SELF"],"dp","","",'width="80" align="center"',$sortfield,$sortorder);
    print_liste_field_titre("Type",$_SERVER["PHP_SELF"],"c.libelle","","","",$sortfield,$sortorder);
    print_liste_field_titre("AmountTTC",$_SERVER["PHP_SELF"],"c.libelle","","",'align="right"',$sortfield,$sortorder);
    print_liste_field_titre('');
    print "</tr>\n";

    while ($i < min($num,$limit))
    {
        $objp = $db->fetch_object($resql);

        print '<tr class="oddeven">';
        print '<td><a href="'.DOL_URL_ROOT.'/compta/paiement/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowPayment"),"payment").' '.$objp->rowid.'</a></td>';
        print '<td width="80" align="center">'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";
        print "<td>$objp->paiement_type $objp->num_paiement</td>\n";
        print '<td align="right">'.price($objp->amount).'</td>';
        print '<td align="center">';

        if ($objp->statut == 0)
        {
            print '<a href="card.php?id='.$objp->rowid.'&amp;action=valide">'.$langs->trans("PaymentStatusToValidShort").'</a>';
        }
        else
        {
            print "-";
        }

        print '</td>';
        print "</tr>";
        $i++;
    }
    print "</table>";
}

llxFooter();
$db->close();
