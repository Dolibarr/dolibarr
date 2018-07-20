<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2016      Frédéric France      <frederic.france@free.fr>
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
 *   	\file       htdocs/compta/sociales/index.php
 *		\ingroup    tax
 *		\brief      Page to list all social contributions
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsocialcontrib.class.php';

// Load translation files required by the page
$langs->loadLangs(array('compta', 'banks', 'bills'));

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');

$search_ref = GETPOST('search_ref','int');
$search_label = GETPOST('search_label','alpha');
$search_amount = GETPOST('search_amount','alpha');
$search_status = GETPOST('search_status','int');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="cs.date_ech";
if (! $sortorder) $sortorder="DESC";

$year=GETPOST("year",'int');
$filtre=GETPOST("filtre",'int');

if (empty($_REQUEST['typeid']))
{
	$newfiltre=str_replace('filtre=','',$filtre);
	$filterarray=explode('-',$newfiltre);
	foreach($filterarray as $val)
	{
		$part=explode(':',$val);
		if ($part[0] == 'cs.fk_type') $typeid=$part[1];
	}
}
else
{
	$typeid=$_REQUEST['typeid'];
}

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All test are required to be compatible with all browsers
{
	$search_ref="";
	$search_label="";
	$search_amount="";
	$search_status='';
    $typeid="";
	$year="";
	$month="";
}

/*
 *	View
 */

$form = new Form($db);
$formsocialcontrib = new FormSocialContrib($db);
$chargesociale_static=new ChargeSociales($db);

llxHeader('', $langs->trans("SocialContributions"));

$sql = "SELECT cs.rowid as id, cs.fk_type as type, ";
$sql.= " cs.amount, cs.date_ech, cs.libelle, cs.paye, cs.periode,";
$sql.= " c.libelle as type_lib,";
$sql.= " SUM(pc.amount) as alreadypayed";
$sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c,";
$sql.= " ".MAIN_DB_PREFIX."chargesociales as cs";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON pc.fk_charge = cs.rowid";
$sql.= " WHERE cs.fk_type = c.id";
$sql.= " AND cs.entity = ".$conf->entity;
// Search criteria
if ($search_ref)	$sql.=" AND cs.rowid=".$db->escape($search_ref);
if ($search_label) 	$sql.=natural_search("cs.libelle", $search_label);
if ($search_amount) $sql.=natural_search("cs.amount", price2num(trim($search_amount)), 1);
if ($search_status != '' && $search_status >= 0) $sql.=" AND cs.paye = ".$db->escape($search_status);
if ($year > 0)
{
    $sql .= " AND (";
    // Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
    // ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
    $sql .= "   (cs.periode IS NOT NULL AND date_format(cs.periode, '%Y') = '".$year."') ";
    $sql .= "OR (cs.periode IS NULL AND date_format(cs.date_ech, '%Y') = '".$year."')";
    $sql .= ")";
}
if ($filtre) {
    $filtre=str_replace(":","=",$filtre);
    $sql .= " AND ".$filtre;
}
if ($typeid) {
    $sql .= " AND cs.fk_type=".$db->escape($typeid);
}
$sql.= " GROUP BY cs.rowid, cs.fk_type, cs.amount, cs.date_ech, cs.libelle, cs.paye, cs.periode, c.libelle";
$sql.= $db->order($sortfield,$sortorder);

$totalnboflines=0;
$result=$db->query($sql);
if ($result)
{
    $totalnboflines = $db->num_rows($result);
}
$sql.= $db->plimit($limit+1,$offset);

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$param='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($year)   $param.='&amp;year='.$year;
	if ($typeid) $param.='&amp;typeid='.$typeid;

	$newcardbutton='';
	if($user->rights->tax->charges->creer)
	{
		$newcardbutton='<a class="butActionNew" href="'.DOL_URL_ROOT.'/compta/sociales/card.php?action=create">'.$langs->trans('MenuNewSocialContribution');
		$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
		$newcardbutton.= '</a>';
	}

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

	if ($year)
	{
	    $center=($year?"<a href='index.php?year=".($year-1)."'>".img_previous()."</a> ".$langs->trans("Year")." $year <a href='index.php?year=".($year+1)."'>".img_next()."</a>":"");
	    print_barre_liste($langs->trans("SocialContributions"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $center, $num, $totalnboflines, 'title_accountancy.png', 0, $newcardbutton, '', $limit);
	}
	else
	{
		print_barre_liste($langs->trans("SocialContributions"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalnboflines, 'title_accountancy.png', 0, $newcardbutton, '', $limit);
	}

	if (empty($mysoc->country_id) && empty($mysoc->country_code))
	{
		print '<div class="error">';
		$langs->load("errors");
		$countrynotdefined=$langs->trans("ErrorSetACountryFirst");
		print $countrynotdefined;
		print '</div>';
	}
	else
	{
	    print '<div class="div-table-responsive">';
	    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

		print '<tr class="liste_titre_filter">';
		// Ref
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="3" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
		// Label
		print '<td class="liste_titre"><input type="text" class="flat" size="8" name="search_label" value="'.dol_escape_htmltag($search_label).'"></td>';
		// Type
		print '<td class="liste_titre" align="left">';
	    $formsocialcontrib->select_type_socialcontrib($typeid,'typeid',1,0,0,'maxwidth100onsmartphone');
	    print '</td>';
		// Period end date
		print '<td class="liste_titre">&nbsp;</td>';
	    // Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="6" name="search_amount" value="'.dol_escape_htmltag($search_amount).'">';
		print '</td>';
		print '<td class="liste_titre">&nbsp;</td>';
		// Status
		print '<td class="liste_titre maxwidthonsmartphone" align="right">';
		$liststatus=array('0'=>$langs->trans("Unpaid"), '1'=>$langs->trans("Paid"));
		print $form->selectarray('search_status', $liststatus, $search_status, 1);
		print '</td>';

        print '<td class="liste_titre" align="right">';
        $searchpicto=$form->showFilterAndCheckAddButtons(0);
        print $searchpicto;
        print '</td>';
		print "</tr>\n";

		print '<tr class="liste_titre">';
		print_liste_field_titre("Ref",$_SERVER["PHP_SELF"],"id","",$param,"",$sortfield,$sortorder);
		print_liste_field_titre("Label",$_SERVER["PHP_SELF"],"cs.libelle","",$param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre("Type",$_SERVER["PHP_SELF"],"type","",$param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre("PeriodEndDate",$_SERVER["PHP_SELF"],"periode","",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre("Amount",$_SERVER["PHP_SELF"],"cs.amount","",$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre("DateDue",$_SERVER["PHP_SELF"],"cs.date_ech","",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre("Status",$_SERVER["PHP_SELF"],"cs.paye","",$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
		print "</tr>\n";

		$i=0;
		$totalarray=array();
		while ($i < min($num,$limit))
		{
			$obj = $db->fetch_object($resql);

			$chargesociale_static->id=$obj->id;
			$chargesociale_static->ref=$obj->id;
			$chargesociale_static->lib=$obj->libelle;
			$chargesociale_static->type_libelle=$obj->type_lib;

			print '<tr class="oddeven">';

			// Ref
			print '<td width="60">';
			print $chargesociale_static->getNomUrl(1,'20');
			print '</td>';

			// Label
			print '<td>'.dol_trunc($obj->libelle,42).'</td>';

			// Type
			print '<td>'.$obj->type_lib.'</td>';

			// Date end period
			print '<td align="center">';
			if ($obj->periode)
			{
				print '<a href="index.php?year='.strftime("%Y",$db->jdate($obj->periode)).'">'.dol_print_date($db->jdate($obj->periode),'day').'</a>';
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';

			// Amount
			print '<td align="right" width="100">'.price($obj->amount).'</td>';
			if (! $i) $totalarray['nbfield']++;
		    if (! $i) $totalarray['totalttcfield']=$totalarray['nbfield'];
			$totalarray['totalttc'] += $obj->amount;

			// Due date
			print '<td width="110" align="center">'.dol_print_date($db->jdate($obj->date_ech), 'day').'</td>';

			print '<td align="right" class="nowrap">'.$chargesociale_static->LibStatut($obj->paye,5,$obj->alreadypayed).'</td>';

			print '<td></td>';

			print '</tr>';
			$i++;
		}

		// Show total line
		if (isset($totalarray['totalttcfield']))
		{
		    print '<tr class="liste_total">';
            if ($num < $limit && empty($offset)) print '<td align="left">'.$langs->trans("Total").'</td>';
            else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
            print '<td></td>';
            print '<td></td>';
            print '<td></td>';
            print '<td align="right">'.price($totalarray['totalttc']).'</td>';
	        print '<td></td>';
	        print '<td></td>';
	        print '<td></td>';
	        print '</tr>';
		}

		print '</table>';
		print '</div>';
	}
	print '</form>';
}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();
