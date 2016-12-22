<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
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
 *      \file       htdocs/compta/prelevement/list.php
 *      \ingroup    prelevement
 *      \brief      Page liste des prelevements
 */
require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/ligneprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("withdrawals");
$langs->load("companies");
$langs->load("categories");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement','','','bons');

// Get supervariables
$page = GETPOST('page','int');
$sortorder = ((GETPOST('sortorder','alpha')=="")) ? "DESC" : GETPOST('sortorder','alpha');
$sortfield = ((GETPOST('sortfield','alpha')=="")) ? "p.datec" : GETPOST('sortfield','alpha');
$search_line = GETPOST('search_line','alpha');
$search_bon = GETPOST('search_bon','alpha');
$search_code = GETPOST('search_code','alpha');
$search_company = GETPOST('search_company','alpha');
$statut = GETPOST('statut','int');

$bon=new BonPrelevement($db,"");
$ligne=new LignePrelevement($db,$user);

$offset = $conf->liste_limit * $page ;

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_line="";
	$search_bon="";
	$search_code="";
    $search_company="";
	$statut="";
}

/*
 *  View
 */

llxHeader('',$langs->trans("WithdrawalsLines"));

$sql = "SELECT p.rowid, p.ref, p.statut, p.datec";
$sql.= " ,f.rowid as facid, f.facnumber, f.total_ttc";
$sql.= " , s.rowid as socid, s.nom as name, s.code_client";
$sql.= " , pl.amount, pl.statut as statut_ligne, pl.rowid as rowid_ligne";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql.= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql.= " , ".MAIN_DB_PREFIX."prelevement_facture as pf";
$sql.= " , ".MAIN_DB_PREFIX."facture as f";
$sql.= " , ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE pl.fk_prelevement_bons = p.rowid";
$sql.= " AND pf.fk_prelevement_lignes = pl.rowid";
$sql.= " AND pf.fk_facture = f.rowid";
$sql.= " AND f.fk_soc = s.rowid";
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND s.rowid = ".$socid;
if ($search_line)
{
    $sql.= " AND pl.rowid = '".$db->escape($search_line)."'";
}
if ($search_bon)
{
    $sql.= " AND p.ref LIKE '%".$db->escape($search_bon)."%'";
}
if ($search_code)
{
    $sql.= " AND s.code_client LIKE '%".$db->escape($search_code)."%'";
}
if ($search_company)
{
    $sql .= " AND s.nom LIKE '%".$db->escape($search_company)."%'";
}
$sql.=$db->order($sortfield,$sortorder);
$sql.=$db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;

    $urladd = "&amp;statut=".$statut;
    $urladd .= "&amp;search_bon=".$search_bon;

    print_barre_liste($langs->trans("WithdrawalsLines"), $page, $_SERVER["PHP_SELF"], $urladd, $sortfield, $sortorder, '', $num);

    print"\n<!-- debut table -->\n";
    print '<table class="liste" width="100%">';

    print '<tr class="liste_titre">';
    print '<td class="liste_titre">'.$langs->trans("Line").'</td>';
    print_liste_field_titre($langs->trans("WithdrawalsReceipts"),$_SERVER["PHP_SELF"],"p.ref");
    print_liste_field_titre($langs->trans("Bill"),$_SERVER["PHP_SELF"],"f.facnumber",'',$urladd);
    print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom");
    print_liste_field_titre($langs->trans("CustomerCode"),$_SERVER["PHP_SELF"],"s.code_client",'','','align="center"');
    print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"p.datec","","",'align="center"');
    print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"pl.amount","","",'align="right"');
    print_liste_field_titre('');
	print "</tr>\n";

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="GET">';
    print '<tr class="liste_titre">';
    print '<td class="liste_titre"><input type="text" class="flat" name="search_line" value="'. dol_escape_htmltag($search_line).'" size="6"></td>';
    print '<td class="liste_titre"><input type="text" class="flat" name="search_bon" value="'. dol_escape_htmltag($search_bon).'" size="8"></td>';
    print '<td>&nbsp;</td>';
    print '<td class="liste_titre"><input type="text" class="flat" name="search_company" value="'. dol_escape_htmltag($search_company).'" size="12"></td>';
    print '<td class="liste_titre" align="center"><input type="text" class="flat" name="search_code" value="'. dol_escape_htmltag($search_code).'" size="8"></td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
    print '</form>';

    $var=True;

    while ($i < min($num,$conf->liste_limit))
    {
        $obj = $db->fetch_object($result);

        $var=!$var;

        print "<tr ".$bc[$var]."><td>";

        print $ligne->LibStatut($obj->statut_ligne,2);
        print "&nbsp;";

        print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/ligne.php?id='.$obj->rowid_ligne.'">';
        print substr('000000'.$obj->rowid_ligne, -6);
        print '</a></td>';

        print '<td>';

        print $bon->LibStatut($obj->statut,2);
        print "&nbsp;";

        print '<a href="card.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";

        print '<td><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->facid.'">';
        print img_object($langs->trans("ShowBill"),"bill");
          print '&nbsp;<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->facid.'">'.$obj->facnumber."</a></td>\n";
        print '</a></td>';

        print '<td><a href="card.php?id='.$obj->rowid.'">'.$obj->name."</a></td>\n";

        print '<td align="center"><a href="card.php?id='.$obj->rowid.'">'.$obj->code_client."</a></td>\n";

        print '<td align="center">'.dol_print_date($db->jdate($obj->datec),'day')."</td>\n";

        print '<td align="right">'.price($obj->amount)."</td>\n";

        print '<td>&nbsp;</td>';

        print "</tr>\n";
        $i++;
    }
    print "</table>";
    $db->free($result);
}
else
{
    dol_print_error($db);
}

llxFooter();
$db->close();
