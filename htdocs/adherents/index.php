<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
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
 *       \file       htdocs/adherents/index.php
 *       \ingroup    member
 *       \brief      Page accueil module adherents
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';

$langs->load("companies");
$langs->load("members");

// Security check
$result=restrictedArea($user,'adherent');


/*
 * View
 */

llxHeader('',$langs->trans("Members"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$staticmember=new Adherent($db);
$statictype=new AdherentType($db);
$subscriptionstatic=new Subscription($db);

print load_fiche_titre($langs->trans("MembersArea"));


$var=True;

$Adherents=array();
$AdherentsAValider=array();
$MemberUpToDate=array();
$AdherentsResilies=array();

$AdherentType=array();

// Liste les adherents
$sql = "SELECT t.rowid, t.libelle, t.subscription,";
$sql.= " d.statut, count(d.rowid) as somme";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as t";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."adherent as d";
$sql.= " ON t.rowid = d.fk_adherent_type";
$sql.= " AND d.entity IN (".getEntity().")";
$sql.= " WHERE t.entity IN (".getEntity().")";
$sql.= " GROUP BY t.rowid, t.libelle, t.subscription, d.statut";

dol_syslog("index.php::select nb of members by type", LOG_DEBUG);
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num)
	{
		$objp = $db->fetch_object($result);

		$adhtype=new AdherentType($db);
		$adhtype->id=$objp->rowid;
		$adhtype->subscription=$objp->subscription;
		$adhtype->libelle=$objp->libelle;
		$AdherentType[$objp->rowid]=$adhtype;

		if ($objp->statut == -1) { $MemberToValidate[$objp->rowid]=$objp->somme; }
		if ($objp->statut == 1)  { $MembersValidated[$objp->rowid]=$objp->somme; }
		if ($objp->statut == 0)  { $MembersResiliated[$objp->rowid]=$objp->somme; }

		$i++;
	}
	$db->free($result);
}

$now=dol_now();

// List members up to date
// current rule: uptodate = the end date is in future whatever is type
// old rule: uptodate = if type does not need payment, that end date is null, if type need payment that end date is in future)
$sql = "SELECT count(*) as somme , d.fk_adherent_type";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
$sql.= " WHERE d.entity IN (".getEntity().")";
//$sql.= " AND d.statut = 1 AND ((t.subscription = 0 AND d.datefin IS NULL) OR d.datefin >= '".$db->idate($now)."')";
$sql.= " AND d.statut = 1 AND d.datefin >= '".$db->idate($now)."'";
$sql.= " AND t.rowid = d.fk_adherent_type";
$sql.= " GROUP BY d.fk_adherent_type";

dol_syslog("index.php::select nb of uptodate members by type", LOG_DEBUG);
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num)
	{
		$objp = $db->fetch_object($result);
		$MemberUpToDate[$objp->fk_adherent_type]=$objp->somme;
		$i++;
	}
	$db->free();
}


//print '<tr><td width="30%" class="notopnoleft" valign="top">';
print '<div class="fichecenter"><div class="fichethirdleft">';

// Search contact/address
if (! empty($conf->adherent->enabled) && $user->rights->adherent->lire)
{
	$listofsearchfields['search_member']=array('text'=>'Member');
}

if (count($listofsearchfields))
{
	print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder nohover centpercent">';
	$i=0;
	foreach($listofsearchfields as $key => $value)
	{
		if ($i == 0) print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
		print '<tr '.$bc[false].'>';
		print '<td class="nowrap"><label for="'.$key.'">'.$langs->trans($value["text"]).'</label>:</td><td><input type="text" class="flat inputsearch" name="'.$key.'" id="'.$key.'" size="18"></td>';
		if ($i == 0) print '<td rowspan="'.count($listofsearchfields).'"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
		print '</tr>';
		$i++;
	}
	print '</table>';	
	print '</form>';
	print '<br>';
}



/*
 * Statistics
 */

if ($conf->use_javascript_ajax)
{
    print '<table class="noborder nohover" width="100%">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
    print '<tr '.$bc[0].'><td align="center" colspan="2">';

    $SommeA=0;
    $SommeB=0;
    $SommeC=0;
    $SommeD=0;
    $dataval=array();
    $datalabels=array();
    $i=0;
    foreach ($AdherentType as $key => $adhtype)
    {
        $datalabels[]=array($i,$adhtype->getNomUrl(0,dol_size(16)));
        $dataval['draft'][]=array($i,isset($MemberToValidate[$key])?$MemberToValidate[$key]:0);
        $dataval['notuptodate'][]=array($i,isset($MembersValidated[$key])?$MembersValidated[$key]-(isset($MemberUpToDate[$key])?$MemberUpToDate[$key]:0):0);
        $dataval['uptodate'][]=array($i,isset($MemberUpToDate[$key])?$MemberUpToDate[$key]:0);
        $dataval['resiliated'][]=array($i,isset($MembersResiliated[$key])?$MembersResiliated[$key]:0);
        $SommeA+=isset($MemberToValidate[$key])?$MemberToValidate[$key]:0;
        $SommeB+=isset($MembersValidated[$key])?$MembersValidated[$key]-(isset($MemberUpToDate[$key])?$MemberUpToDate[$key]:0):0;
        $SommeC+=isset($MemberUpToDate[$key])?$MemberUpToDate[$key]:0;
        $SommeD+=isset($MembersResiliated[$key])?$MembersResiliated[$key]:0;
        $i++;
    }

    $dataseries=array();
    $dataseries[]=array('label'=>$langs->trans("MenuMembersNotUpToDate"),'data'=>round($SommeB));
    $dataseries[]=array('label'=>$langs->trans("MenuMembersUpToDate"),'data'=>round($SommeC));
    $dataseries[]=array('label'=>$langs->trans("MembersStatusResiliated"),'data'=>round($SommeD));
    $dataseries[]=array('label'=>$langs->trans("MembersStatusToValid"),'data'=>round($SommeA));
    $data=array('series'=>$dataseries);
    dol_print_graph('stats',300,180,$data,1,'pie',1);
    print '</td></tr>';
    print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">';
    print $SommeA+$SommeB+$SommeC+$SommeD;
    print '</td></tr>';
    print '</table>';
}


//print '</td><td class="notopnoleftnoright" valign="top">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


$var=true;

/*
 * Last modified members
 */
$max=5;

$sql = "SELECT a.rowid, a.statut, a.lastname, a.firstname, a.societe as company, a.fk_soc,";
$sql.= " a.tms as datem, datefin as date_end_subscription,";
$sql.= " ta.rowid as typeid, ta.libelle, ta.subscription";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as a, ".MAIN_DB_PREFIX."adherent_type as ta";
$sql.= " WHERE a.entity IN (".getEntity().")";
$sql.= " AND a.fk_adherent_type = ta.rowid";
$sql.= $db->order("a.tms","DESC");
$sql.= $db->plimit($max, 0);

$resql=$db->query($sql);
if ($resql)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4">'.$langs->trans("LastMembersModified",$max).'</td></tr>';

	$num = $db->num_rows($resql);
	if ($num)
	{
		$i = 0;
		$var = True;
		while ($i < $num)
		{
			$var=!$var;
			$obj = $db->fetch_object($resql);
			print "<tr ".$bc[$var].">";
			$staticmember->id=$obj->rowid;
			$staticmember->lastname=$obj->lastname;
			$staticmember->firstname=$obj->firstname;
			if (! empty($obj->fk_soc))
			{
				$staticmember->fk_soc = $obj->fk_soc;
				$staticmember->fetch_thirdparty();
				$staticmember->name=$staticmember->thirdparty->name;
			}
			else
			{
				$staticmember->name=$obj->company;
			}
			$staticmember->ref=$staticmember->getFullName($langs);
			$statictype->id=$obj->typeid;
			$statictype->libelle=$obj->libelle;
			print '<td>'.$staticmember->getNomUrl(1,32).'</td>';
			print '<td>'.$statictype->getNomUrl(1,32).'</td>';
			print '<td>'.dol_print_date($db->jdate($obj->datem),'dayhour').'</td>';
			print '<td align="right">'.$staticmember->LibStatut($obj->statut,($obj->subscription=='yes'?1:0),$db->jdate($obj->date_end_subscription),5).'</td>';
			print '</tr>';
			$i++;
		}
	}
	print "</table><br>";
}
else
{
	dol_print_error($db);
}


/*
 * Last modified subscriptions
 */
$max=5;

$sql = "SELECT a.rowid, a.statut, a.lastname, a.firstname, a.societe as company, a.fk_soc,";
$sql.= " datefin as date_end_subscription,";
$sql.= " c.rowid as cid, c.tms as datem, c.datec as datec, c.dateadh as date_start, c.datef as date_end, c.subscription";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as a, ".MAIN_DB_PREFIX."subscription as c";
$sql.= " WHERE a.entity IN (".getEntity().")";
$sql.= " AND c.fk_adherent = a.rowid";
$sql.= $db->order("c.tms","DESC");
$sql.= $db->plimit($max, 0);

$resql=$db->query($sql);
if ($resql)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="5">'.$langs->trans("LastSubscriptionsModified",$max).'</td></tr>';

	$num = $db->num_rows($resql);
	if ($num)
	{
		$i = 0;
		$var = True;
		while ($i < $num)
		{
			$var=!$var;
			$obj = $db->fetch_object($resql);
			print "<tr ".$bc[$var].">";
			$subscriptionstatic->id=$obj->cid;
			$subscriptionstatic->ref=$obj->cid;
			$staticmember->id=$obj->rowid;
			$staticmember->lastname=$obj->lastname;
			$staticmember->firstname=$obj->firstname;
			if (! empty($obj->fk_soc)) {
				$staticmember->socid = $obj->fk_soc;
				$staticmember->fetch_thirdparty();
				$staticmember->name=$staticmember->thirdparty->name;
			} else {
				$staticmember->name=$obj->company;
			}
			$staticmember->ref=$staticmember->getFullName($langs);
			print '<td>'.$subscriptionstatic->getNomUrl(1).'</td>';
			print '<td>'.$staticmember->getNomUrl(1,32,'subscription').'</td>';
			print '<td>'.get_date_range($db->jdate($obj->date_start),$db->jdate($obj->date_end)).'</td>';
			print '<td align="right">'.price($obj->subscription).'</td>';
			//print '<td align="right">'.$staticmember->LibStatut($obj->statut,($obj->subscription=='yes'?1:0),$db->jdate($obj->date_end_subscription),5).'</td>';
			print '<td align="right">'.dol_print_date($db->jdate($obj->datem?$obj->datem:$obj->datec),'dayhour').'</td>';
			print '</tr>';
			$i++;
		}
	}
	print "</table><br>";
}
else
{
	dol_print_error($db);
}


// Summary of members by type
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("MembersTypes").'</td>';
print '<td align=right>'.$langs->trans("MembersStatusToValid").'</td>';
print '<td align=right>'.$langs->trans("MenuMembersNotUpToDate").'</td>';
print '<td align=right>'.$langs->trans("MenuMembersUpToDate").'</td>';
print '<td align=right>'.$langs->trans("MembersStatusResiliated").'</td>';
print "</tr>\n";

foreach ($AdherentType as $key => $adhtype)
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td>'.$adhtype->getNomUrl(1, dol_size(32)).'</td>';
	print '<td align="right">'.(isset($MemberToValidate[$key]) && $MemberToValidate[$key] > 0?$MemberToValidate[$key]:'').' '.$staticmember->LibStatut(-1,$adhtype->subscription,0,3).'</td>';
	print '<td align="right">'.(isset($MembersValidated[$key]) && ($MembersValidated[$key]-(isset($MemberUpToDate[$key])?$MemberUpToDate[$key]:0) > 0) ? $MembersValidated[$key]-(isset($MemberUpToDate[$key])?$MemberUpToDate[$key]:0):'').' '.$staticmember->LibStatut(1,$adhtype->subscription,0,3).'</td>';
	print '<td align="right">'.(isset($MemberUpToDate[$key]) && $MemberUpToDate[$key] > 0 ? $MemberUpToDate[$key]:'').' '.$staticmember->LibStatut(1,$adhtype->subscription,$now,3).'</td>';
	print '<td align="right">'.(isset($MembersResiliated[$key]) && $MembersResiliated[$key]> 0 ?$MembersResiliated[$key]:'').' '.$staticmember->LibStatut(0,$adhtype->subscription,0,3).'</td>';
	print "</tr>\n";
}
print '<tr class="liste_total">';
print '<td class="liste_total">'.$langs->trans("Total").'</td>';
print '<td class="liste_total" align="right">'.$SommeA.' '.$staticmember->LibStatut(-1,$adhtype->subscription,0,3).'</td>';
print '<td class="liste_total" align="right">'.$SommeB.' '.$staticmember->LibStatut(1,$adhtype->subscription,0,3).'</td>';
print '<td class="liste_total" align="right">'.$SommeC.' '.$staticmember->LibStatut(1,$adhtype->subscription,$now,3).'</td>';
print '<td class="liste_total" align="right">'.$SommeD.' '.$staticmember->LibStatut(0,$adhtype->subscription,0,3).'</td>';
print '</tr>';

print "</table>\n";
print "<br>\n";


// List of subscription by year
$Total=array();
$Number=array();
$tot=0;
$numb=0;

$sql = "SELECT c.subscription, c.dateadh as dateh";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."subscription as c";
$sql.= " WHERE d.entity IN (".getEntity().")";
$sql.= " AND d.rowid = c.fk_adherent";
if(isset($date_select) && $date_select != '')
{
	$sql .= " AND c.dateadh LIKE '".$date_select."%'";
}
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num)
	{
		$objp = $db->fetch_object($result);
		$year=dol_print_date($db->jdate($objp->dateh),"%Y");
		$Total[$year]=(isset($Total[$year])?$Total[$year]:0)+$objp->subscription;
		$Number[$year]=(isset($Number[$year])?$Number[$year]:0)+1;
		$tot+=$objp->subscription;
		$numb+=1;
		$i++;
	}
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Subscriptions").'</td>';
print '<td align="right">'.$langs->trans("Number").'</td>';
print '<td align="right">'.$langs->trans("AmountTotal").'</td>';
print '<td align="right">'.$langs->trans("AmountAverage").'</td>';
print "</tr>\n";

$var=true;
krsort($Total);
foreach ($Total as $key=>$value)
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print "<td><a href=\"./subscription/list.php?date_select=$key\">$key</a></td>";
	print "<td align=\"right\">".$Number[$key]."</td>";
	print "<td align=\"right\">".price($value)."</td>";
	print "<td align=\"right\">".price(price2num($value/$Number[$key],'MT'))."</td>";
	print "</tr>\n";
}

// Total
print '<tr class="liste_total">';
print '<td>'.$langs->trans("Total").'</td>';
print "<td align=\"right\">".$numb."</td>";
print '<td align="right">'.price($tot)."</td>";
print "<td align=\"right\">".price(price2num($numb>0?($tot/$numb):0,'MT'))."</td>";
print "</tr>\n";
print "</table><br>\n";

//print '</td></tr></table>';
print '</div></div></div>';


llxFooter();
$db->close();
