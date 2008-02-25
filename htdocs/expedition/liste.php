<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
        \file       htdocs/expedition/liste.php
        \ingroup    expedition
        \brief      Page de la liste des expéditions/livraisons
		\version	$Id$
*/

require("./pre.inc.php");

$langs->load('companies');

// Security check
$expeditionid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expedition',$expeditionid,'',1);


$sortfield=isset($_GET["sortfield"])?$_GET["sortfield"]:"";
$sortorder=isset($_GET["sortorder"])?$_GET["sortorder"]:"";
if (! $sortfield) $sortfield="e.rowid";
if (! $sortorder) $sortorder="DESC";

$limit = $conf->liste_limit;
$offset = $limit * $_GET["page"] ;



/******************************************************************************/
/*                                                                            */
/*                   Fin des  Actions                                         */
/*                                                                            */
/******************************************************************************/


llxHeader('',$langs->trans('ListOfSendings'));

$clause = " WHERE ";
$sql = "SELECT e.rowid, e.ref,".$db->pdate("e.date_expedition")." as date_expedition, e.fk_statut";
$sql.= ", s.nom as socname, s.rowid as socid";
$sql.= ", ori.ref as oriref, ori.rowid as oriid";
$sql.= " FROM ".MAIN_DB_PREFIX."expedition as e";
if ($conf->commande->enabled)
{
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."co_exp as ce ON e.rowid = ce.fk_expedition";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as ori ON ce.fk_commande = ori.rowid"; 
}
else
{
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."pr_exp as pe ON e.rowid = pe.fk_expedition";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."propal as ori ON pe.fk_commande = ori.rowid";
}
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
if (!$user->rights->commercial->client->voir && !$socid)
{
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
	$sql.= $clause." sc.fk_user = " .$user->id;
	$clause = " AND ";
}
if ($socid)
{ 
  $sql.= $clause." e.fk_soc = ".$socid; 
  $clause = " AND ";
}
if ($_POST["sf_ref"])
{
  $sql.= $clause." e.ref like '%".addslashes($_POST["sf_ref"])."%'";
  $clause = " AND ";
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
  if ($conf->commande->enabled)
  {
  	print_liste_field_titre($langs->trans("Order"),"liste.php","ori.ref", "", "&amp;socid=$socid",'width="25%" align="left"',$sortfield,$sortorder);
  }
  else
  {
  	print_liste_field_titre($langs->trans("Proposal"),"liste.php","ori.ref", "", "&amp;socid=$socid",'width="25%" align="left"',$sortfield,$sortorder);
  }
  print_liste_field_titre($langs->trans("Date"),"liste.php","e.date_expedition","","&amp;socid=$socid", 'width="25%" align="right" colspan="2"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Status"),"liste.php","e.fk_statut","","&amp;socid=$socid",'width="10%" align="center"',$sortfield,$sortorder);
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
    if ($conf->commande->enabled)
    {
    	print '<td><a href="'.DOL_URL_ROOT.'/expedition/commande.php?id='.$objp->oriid.'">'.$objp->oriref.'</a></td>';
    }
    else
    {
    	print '<td><a href="'.DOL_URL_ROOT.'/expedition/propal.php?propalid='.$objp->oriid.'">'.$objp->oriref.'</a></td>';
    }

    $now = time();
    $lim = 3600 * 24 * 15 ;
      
    if ( ($now - $objp->date_expedition) > $lim && $objp->statutid == 1 )
    {
    	print "<td><b> &gt; 15 jours</b></td>";
    }
    else
    {
    	print "<td>&nbsp;</td>";
    }
    
    print "<td align=\"right\">";
    $y = strftime("%Y",$objp->date_expedition);
    $m = strftime("%m",$objp->date_expedition);
     
    print strftime("%d",$objp->date_expedition)."\n";
    print " <a href=\"propal.php?year=$y&amp;month=$m\">";
    print strftime("%B",$objp->date_expedition)."</a>\n";
    print " <a href=\"propal.php?year=$y\">";
    print strftime("%Y",$objp->date_expedition)."</a></TD>\n";      
    
    print '<td align="center">'.$expedition->statuts[$objp->fk_statut].'</td>';
    print "</tr>\n";
    
    $i++;
  }
  
  print "</table>";
  $db->free($resql);
}
else
{
  dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');

?>
