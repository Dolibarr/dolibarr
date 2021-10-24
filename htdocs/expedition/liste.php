<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *      \file       htdocs/expedition/liste.php
 *      \ingroup    expedition
 *      \brief      Page de la liste des expeditions/livraisons
 *		\version	$Id$
 */

require("./pre.inc.php");

$langs->load('companies');

// Security check
$expeditionid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expedition',$expeditionid,'');


$sortfield=isset($_GET["sortfield"])?$_GET["sortfield"]:"";
$sortorder=isset($_GET["sortorder"])?$_GET["sortorder"]:"";
if (! $sortfield) $sortfield="e.ref";
if (! $sortorder) $sortorder="DESC";

$limit = $conf->liste_limit;
$offset = $limit * $_GET["page"] ;


/*
 * View
 */

$helpurl='EN:Module_Shipments|FR:Module_Exp&eacute;ditions|ES:M&oacute;dulo_Expediciones';
llxHeader('',$langs->trans('ListOfSendings'),$helpurl);

$sql = "SELECT e.rowid, e.ref,".$db->pdate("e.date_expedition")." as date_expedition, e.fk_statut";
$sql.= ", s.nom as socname, s.rowid as socid";
$sql.= " FROM (".MAIN_DB_PREFIX."expedition as e";
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql.= ")";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
$sql.= " WHERE e.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql.= " AND e.fk_soc = sc.fk_soc";
	$sql.= " AND sc.fk_user = " .$user->id;
}
if ($socid)
{
  $sql.= " AND e.fk_soc = ".$socid;
}
if ($_POST["sf_ref"])
{
  $sql.= " AND e.ref like '%".addslashes($_POST["sf_ref"])."%'";
}

$sql.= " ORDER BY $sortfield $sortorder";
$sql.= $db->plimit($limit + 1,$offset);

$resql=$db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);

  $expedition = new Expedition($db);

  print_barre_liste($langs->trans('ListOfSendings'), $_GET["page"], "liste.php","&amp;socid=$socid",$sortfield,$sortorder,'',$num);

  $i = 0;
  print '<table class="noborder" width="100%">';

  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Ref"),"liste.php","e.ref","","&amp;socid=$socid",'width="15%"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Company"),"liste.php","s.nom", "", "&amp;socid=$socid",'width="25%" align="left"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Date"),"liste.php","e.date_expedition","","&amp;socid=$socid", 'width="25%" align="right"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Status"),"liste.php","e.fk_statut","","&amp;socid=$socid",'width="10%" align="right"',$sortfield,$sortorder);
  print "</tr>\n";
  $var=True;

  while ($i < min($num,$limit))
  {
  	$objp = $db->fetch_object($resql);

    $var=!$var;
    print "<tr $bc[$var]>";
    print "<td><a href=\"fiche.php?id=".$objp->rowid."\">".img_object($langs->trans("ShowSending"),"sending").'</a>&nbsp;';
    print "<a href=\"fiche.php?id=".$objp->rowid."\">".$objp->ref."</a></td>\n";
    print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->socid.'">'.$objp->socname.'</a></td>';
    print "<td align=\"right\">";
    $y = dol_print_date($objp->date_expedition,"%Y");
    $m = dol_print_date($objp->date_expedition,"%m");
    $mt = dol_print_date($objp->date_expedition,"%b");
    $d = dol_print_date($objp->date_expedition,"%d");
    print $d."\n";
    print " <a href=\"propal.php?year=$y&amp;month=$m\">";
    print $m."</a>\n";
    print " <a href=\"propal.php?year=$y\">";
    print $y."</a></TD>\n";

    print '<td align="right">'.$expedition->LibStatut($objp->fk_statut,5).'</td>';
    print "</tr>\n";

    $i++;
  }

  print "</table>";
  $db->free($resql);
}
else
{
  dol_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
