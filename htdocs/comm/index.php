<?php
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
 *
 */

/**
	    \file       htdocs/comm/index.php
        \ingroup    commercial
		\brief      Page acceuil de la zone commercial
		\version    $Revision$
*/
 
require("./pre.inc.php");
if ($conf->contrat->enabled) {
	  require_once("../contrat/contrat.class.php");
}
	  
$langs->load("commercial");
$langs->load("orders");

$user->getrights('propale');
$user->getrights('fichinter');
$user->getrights('commande');
$user->getrights('projet');


if ($user->societe_id > 0)
{
  $socidp = $user->societe_id;
}

llxHeader();


/*
 * Actions
 */

if ($_GET["action"] == 'add_bookmark')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bookmark WHERE fk_soc = ".$_GET["socidp"]." AND fk_user=".$user->id;
  if (! $db->query($sql) )
    {
      dolibarr_print_error($db);
    }
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark (fk_soc, dateb, fk_user) VALUES (".$_GET["socidp"].", now(),".$user->id.");";
  if (! $db->query($sql) )
    {
      dolibarr_print_error($db);
    }
}

if ($_GET["action"] == 'del_bookmark')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bookmark WHERE rowid=".$_GET["bid"];
  $result = $db->query($sql);
}


/*
 * Affichage page
 */

print_titre($langs->trans("CommercialArea"));

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';


/*
 * Recherche Propal
 */
if ($conf->propal->enabled) {
    $var=false;
	print '<form method="post" action="propal.php">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("SearchAProposal").'</td></tr>';
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("Ref").' : <input type="text" name="sf_ref">&nbsp;<input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print "</table></form><br>\n";
}

/*
 * Recherche Contrat
 */
if ($conf->contrat->enabled) {
    $var=false;
	print '<form method="post" action="'.DOL_URL_ROOT.'/contrat/liste.php">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("SearchAContract").'</td></tr>';
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("Ref").' : <input type="text" name="search_contract">&nbsp;<input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print "</table></form><br>\n";
}

/*
 * Liste des propal brouillons
 */
if ($conf->propal->enabled) {
	$sql = "SELECT p.rowid, p.ref, p.price, s.nom";
	$sql .= " FROM ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."societe as s";
	$sql .= " WHERE p.fk_statut = 0 and p.fk_soc = s.idp";
	
	if ( $db->query($sql) )
	{
	  $total = 0;
	  $num = $db->num_rows();
	  $i = 0;
	  $var=true;
	  if ($num > 0 )
	    {
	      print '<table class="noborder" width="100%">';
	      print "<tr class=\"liste_titre\">";
	      print "<td colspan=\"3\">".$langs->trans("ProposalsDraft")."</td></tr>";
	      
	      while ($i < $num)
		{
		  $obj = $db->fetch_object();
		  $var=!$var;
		  print '<tr '.$bc[$var].'><td width="25%">'."<a href=\"".DOL_URL_ROOT."/comm/propal.php?propalid=".$obj->rowid."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$obj->ref."</a></td><td>".$obj->nom."</td><td align=\"right\">".price($obj->price)."</td></tr>";
		  $i++;
		  $total += $obj->price;
		}
		if ($total>0) {
		  $var=!$var;
		  print '<tr '.$bc[$var]."><td colspan=\"2\"><i>".$langs->trans("Total")."</i></td><td align=\"right\"><i>".price($total)."</i></td></tr>";
		}
	      print "</table><br>";
	    }
	}
}

/*
 * Commandes à valider
 */
if ($conf->commande->enabled)
{
    $langs->load("orders");
    $sql = "SELECT c.rowid, c.ref, s.nom, s.idp FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
    $sql .= " WHERE c.fk_soc = s.idp AND c.fk_statut = 0";
    if ($socidp)
    {
      $sql .= " AND c.fk_soc = $socidp";
    }
    
    if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();
      if ($num)
        {
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre">';
	  print '<td colspan="2">'.$langs->trans("OrdersToValid").'</td></tr>';
          $i = 0;
          $var = False;
          while ($i < $num)
	    {
	      $obj = $db->fetch_object();
	      print "<tr $bc[$var]><td width=\"25%\"><a href=\"../commande/fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowOrder"),"order")." ".$obj->ref."</a></td>";
	      print '<td><a href="fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	      $i++;
	      $var=!$var;
	    }
	  print "</table><br>";     
        }
    }
}

/*
 * Bookmark
 *
 */
$sql = "SELECT s.idp, s.nom,b.rowid as bid";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."bookmark as b";
$sql .= " WHERE b.fk_soc = s.idp AND b.fk_user = ".$user->id;
if ($socidp)
{ 
  $sql .= " AND s.idp = $socidp"; 
}
$sql .= " ORDER BY lower(s.nom) ASC";

if ( $db->query($sql) )
{
  $num = $db->num_rows();

  if ($num)
    {

      $i = 0;

      print '<table class="noborder" width="100%">';
      print "<tr class=\"liste_titre\">";
      print "<td colspan=\"2\">".$langs->trans("Bookmarks")."</td>";
      print "</tr>\n";
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object();
	  $var = !$var;
	  print "<tr $bc[$var]>";
	  print '<td><a href="fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td>';
	  print '<td align="right"><a href="index.php?action=del_bookmark&bid='.$obj->bid.'">';
	  print img_delete();
	  print '</a></td>';
	  print '</tr>';
	  $i++;
	}
      print '</table>';
    }
}

print '</td><td valign="top" width="70%">';


/*
 * Dernières actions commerciales effectuées
 *
 */

$sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.libelle, a.fk_user_author, s.nom as sname, s.idp";
$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE c.id=a.fk_action AND a.percent >= 100 AND s.idp = a.fk_soc";
if ($socidp)
{ 
  $sql .= " AND s.idp = $socidp"; 
}
$sql .= " ORDER BY a.datea DESC limit 5";

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();

  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("LastDoneTasks").'</td></tr>';
  $var = true;
  $i = 0;

  while ($i < $num ) 
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print "<td><a href=\"action/fiche.php?id=$obj->id\">".img_object($langs->trans("ShowTask"),"task").' '.$obj->libelle.' '.$obj->label.'</a></td>';
	  print "<td>".dolibarr_print_date($obj->da)."</td>";
	  print '<td><a href="fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowCustomer"),"company").' '.$obj->sname.'</a></td>';
	  $i++;
	}
  // TODO Ajouter rappel pour "il y a des contrats à mettre en service"
  // TODO Ajouter rappel pour "il y a des contrats qui arrivent à expiration"
  print "</table><br>";

  $db->free();
} 
else
{
  dolibarr_print_error($db);
}


/*
 * Actions commerciales a faire
 *
 */

$sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.libelle, a.fk_user_author, s.nom as sname, s.idp";
$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE c.id=a.fk_action AND a.percent < 100 AND s.idp = a.fk_soc";
if ($socidp)
{ 
  $sql .= " AND s.idp = $socidp"; 
}
$sql .= " ORDER BY a.datea ASC";

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  if ($num > 0)
    { 
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("ActionsToDo").'</td></tr>';
      $var = true;
      $i = 0;
      
      while ($i < $num ) 
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print "<td><a href=\"action/fiche.php?id=$obj->id\">".img_object($langs->trans("ShowTask"),"task")."</a> ".$obj->libelle.' '.$obj->label.'</a></td>';
	
	  print '<td>'. strftime("%d %b %Y",$obj->da);
          if (date("U",$obj->da) < time())
          {
	    print img_warning("Late");
          }
	  print "</td>";

	  print '<td><a href="fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowCustomer"),"company").' '.$obj->sname.'</a></td>';
	  $i++;
	}
      // TODO Ajouter rappel pour "il y a des contrats à mettre en service"
      // TODO Ajouter rappel pour "il y a des contrats qui arrivent à expiration"
      print "</table><br>";
    }
  $db->free();
} 
else
{
  dolibarr_print_error($db);
}

/*
 * Derniers contrat
 *
 */
if ($conf->contrat->enabled && 0) // \todo A REFAIRE DEPUIS NOUVEAU CONTRAT
{
  $langs->load("contracts");
  
  $sql = "SELECT s.nom, s.idp, c.statut, c.rowid, p.ref, c.mise_en_service as datemes, c.fin_validite as datefin, c.date_cloture as dateclo";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."product as p WHERE c.fk_soc = s.idp and c.fk_product = p.rowid";
  if ($socidp)
    { 
      $sql .= " AND s.idp = $socidp"; 
    }
  $sql .= " ORDER BY c.tms DESC";
  $sql .= $db->plimit(5, 0);
  
  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      
      if ($num > 0)
	{
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("LastContracts",5).'</td></tr>';
	  $i = 0;
	  
      $contrat=new Contrat($db);
      
	  $var=false;
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object();
	      print "<tr $bc[$var]><td><a href=\"../contrat/fiche.php?id=".$obj->rowid."\">".img_object($langs->trans("ShowContract","contract"))." ".$obj->ref."</a></td>";
	      print "<td><a href=\"fiche.php?socid=$obj->idp\">".img_object($langs->trans("ShowCompany","company"))." ".$obj->nom."</a></td>\n";      
	      print "<td align=\"right\">".$contrat->LibStatut($obj->enservice)."</td></tr>\n";
	      $var=!$var;
	      $i++;
	    }
	  print "</table><br>";
	}
    }
  else
    {
      dolibarr_print_error($db);   
    }  
}

/*
 * Dernières propales ouvertes
 *
 */
if ($conf->propal->enabled) {

    $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price, p.ref,".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
    $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id AND p.fk_statut = 1";
    if ($socidp) $sql .= " AND s.idp = $socidp"; 
    $sql .= " ORDER BY p.rowid DESC";
    
    $result=$db->query($sql);
    if ($result)
    {
	  $total = 0;
      $num = $db->num_rows($result);
      $i = 0;
      if ($num > 0)
	{
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("ProposalsOpened").'</td></tr>';
	  $var=false;
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object($result);
	      print "<tr $bc[$var]><td width=\"15%\"><a href=\"propal.php?propalid=".$obj->propalid."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$obj->ref."</a></td>";
	      print "<td width=\"30%\"><a href=\"fiche.php?socid=$obj->idp\">".img_object($langs->trans("ShowCompany"),"company")." ".$obj->nom."</a></td>\n";      
	      print "<td align=\"right\">";
	      print strftime("%e %b %Y",$obj->dp)."</td>\n";	  
	      print "<td align=\"right\">".price($obj->price)."</td></tr>\n";
	      $var=!$var;
	      $i++;
		  $total += $obj->price;
	    }
		if ($total>0) {
		  print "<tr $bc[$var]><td colspan=\"3\" align=\"right\"><i>".$langs->trans("Total")."</i></td><td align=\"right\"><i>".price($total)."</i></td></tr>";
		}
	  print "</table><br>";
	}
    }
    
}

/*
 * Dernières propales fermées
 *
 */

if ($conf->propal->enabled) {
    $NBMAX=5;
    
	$sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price, p.ref,".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
	$sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id AND p.fk_statut > 1";
	if ($socidp)
	{ 
	  $sql .= " AND s.idp = $socidp"; 
	}
	$sql .= " ORDER BY p.rowid DESC";
	$sql .= $db->plimit($NBMAX, 0);
	
	if ( $db->query($sql) )
	    {
	    $num = $db->num_rows();
	      
	    $i = 0;
	    print '<table class="noborder" width="100%">';      
	    print '<tr class="liste_titre"><td colspan="6">'.$langs->trans("LastClosedProposals",$NBMAX).'</td></tr>';
	    $var=False;	      
	    while ($i < $num)
	      {
		$objp = $db->fetch_object();		  
		print "<tr $bc[$var]>";
		print '<td width="15%">';
		print '<a href="propal.php?propalid='.$objp->propalid.'">'.img_file().'</a>';
		print '&nbsp;<a href="propal.php?propalid='.$objp->propalid.'">'.$objp->ref.'</a></td>';
		print "<td width=\"30%\"><a href=\"fiche.php?socid=$objp->idp\">$objp->nom</a></td>\n";      
		
		$now = time();
		$lim = 3600 * 24 * 15 ;
		
		if ( ($now - $objp->dp) > $lim && $objp->statutid == 1 )
		  {
		    print "<td><b> &gt; 15 jours</b></td>";
		  }
		else
		  {
		    print "<td>&nbsp;</td>";
		  }
		
		print "<td align=\"right\">";
		print strftime("%e %b %Y",$objp->dp)."</td>\n";	  
		print "<td align=\"right\">".price($objp->price)."</td>\n";
		print "<td align=\"center\">$objp->statut</td>\n";
		print "</tr>\n";
		$i++;
		$var=!$var;
		
	      }
	    
	    print "</table>";
	    $db->free();
	    }
}


print '</td></tr>';
print '</table>';

$db->close();
 

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
