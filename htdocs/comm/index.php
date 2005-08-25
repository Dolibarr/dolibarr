<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   \file       htdocs/comm/index.php
   \ingroup    commercial
   \brief      Page acceuil de la zone commercial
   \version    $Revision$
*/
 
require("./pre.inc.php");

if (!$user->rights->commercial->main->lire)
  accessforbidden();

if ($conf->contrat->enabled)
  require_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
	  
$langs->load("commercial");
$langs->load("orders");

// Securité accès client
$socidp='';
if ($user->societe_id > 0)
{
  $socidp = $user->societe_id;
}

$max=5;

llxHeader();

/*
 * Actions
 */

if (isset($_GET["action"]) && $_GET["action"] == 'add_bookmark')
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

if (isset($_GET["action"]) && $_GET["action"] == 'del_bookmark')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bookmark WHERE rowid=".$_GET["bid"];
  $result = $db->query($sql);
}


/*
 * Affichage page
 */

print_fiche_titre($langs->trans("CommercialArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';


/*
 * Recherche Propal
 */
if ($conf->propal->enabled && $user->rights->propale->lire)
{
  $var=false;
  print '<form method="post" action="'.DOL_URL_ROOT.'/comm/propal.php">';
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAProposal").'</td></tr>';
  print '<tr '.$bc[$var].'>';
  print '<td nowrap>'.$langs->trans("Ref").':</td><td><input type="text" class="flat" name="sf_ref" size="18"></td>';
  print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
  print '<tr '.$bc[$var].'><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td>';
  print '</tr>';
  print "</table></form>\n";
  print "<br />\n";
}

/*
 * Recherche Contrat
 */
if ($conf->contrat->enabled)
{
  $var=false;
  print '<form method="post" action="'.DOL_URL_ROOT.'/contrat/liste.php">';
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAContract").'</td></tr>';
  print '<tr '.$bc[$var].'>';
  print '<td nowrap>'.$langs->trans("Ref").':</td><td><input type="text" class="flat" name="search_contract" size="18"></td>';
  print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
  print '<tr '.$bc[$var].'><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td>';
  print '</tr>';
  print "</table></form>\n";
  print "<br>";
}

/*
 * Liste des propal brouillons
 */
if ($conf->propal->enabled && $user->rights->propale->lire)
{
  $sql = "SELECT p.rowid, p.ref, p.price, s.idp, s.nom";
  $sql .= " FROM ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."societe as s";
  $sql .= " WHERE p.fk_statut = 0 and p.fk_soc = s.idp";
  
  $resql=$db->query($sql);
  if ($resql)
    {
      $total = 0;
      $num = $db->num_rows($resql);
      if ($num > 0)
        {
	  print '<table class="noborder" width="100%">';
	  print "<tr class=\"liste_titre\">";
	  print "<td colspan=\"3\">".$langs->trans("ProposalsDraft")."</td></tr>";
	  
	  $i = 0;
	  $var=true;
	  while ($i < $num)
            {
	      $obj = $db->fetch_object($resql);
	      $var=!$var;
	      print '<tr '.$bc[$var].'><td nowrap>'."<a href=\"".DOL_URL_ROOT."/comm/propal.php?propalid=".$obj->rowid."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$obj->ref.'</a></td>';
	      print '<td><a href="fiche.php?socid='.$obj->idp.'">'.dolibarr_trunc($obj->nom,18).'</a></td><td align="right">'.price($obj->price).'</td></tr>';
	      $i++;
	      $total += $obj->price;
            }
	  if ($total>0)
	    {
	      $var=!$var;
	      print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" align="right">'.price($total)."</td></tr>";
	    }
	  print "</table><br>";
        }
      $db->free($resql);
    }
}


/*
 * Commandes brouillons
 */
if ($conf->commande->enabled)
{
  $langs->load("orders");
  $sql = "SELECT c.rowid, c.ref, c.total_ttc, s.nom, s.idp FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
  $sql .= " WHERE c.fk_soc = s.idp AND c.fk_statut = 0";
  if ($socidp)
    {
      $sql .= " AND c.fk_soc = $socidp";
    }
  
  $resql = $db->query($sql);
  if ($resql)
    {
      $total = 0;
      $num = $db->num_rows($resql);
      if ($num)
        {
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre">';
	  print '<td colspan="3">'.$langs->trans("DraftOrders").'</td></tr>';
	  
	  $i = 0;
	  $var = true;
	  while ($i < $num)
            {
	      $var=!$var;
	      $obj = $db->fetch_object($resql);
	      print "<tr $bc[$var]><td nowrap><a href=\"../commande/fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowOrder"),"order")." ".$obj->ref."</a></td>";
	      print '<td><a href="fiche.php?socid='.$obj->idp.'">'.dolibarr_trunc($obj->nom,18).'</a></td>';
	      print '<td align="right">'.price($obj->total_ttc).'</td></tr>';
	      $i++;
	      $total += $obj->total_ttc;
            }
	  if ($total>0)
	    {
	      $var=!$var;
	      print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" align="right">'.price($total)."</td></tr>";
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
      $var=true;
      
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

print '</td><td valign="top" width="70%" class="notopnoleftnoright">';


/*
 * Derniers clients enregistrés
 */
if ($user->rights->societe->lire)
{
    $sql = "SELECT s.idp,s.nom,".$db->pdate("datec")." as datec";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql.= " WHERE s.client = 1";
    if ($user->societe_id > 0)
    {
        $sql .= " AND s.idp = $user->societe_id";
    }
    $sql .= " ORDER BY s.datec DESC";
    $sql .= $db->plimit($max, 0);
    
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        if ($num)
        {
            $langs->load("boxes");
            
            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre">';
            print '<td colspan="2">'.$langs->trans("BoxTitleLastCustomers",$max).'</td></tr>';
            
            $i = 0;
            $var=false;
            while ($i < $num)
            {
                $objp = $db->fetch_object($resql);
                print "<tr $bc[$var]>";
                print "<td nowrap><a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->idp."\">".img_object($langs->trans("ShowCustomer"),"company")." ".$objp->nom."</a></td>";
                print '<td align="right" nowrap>'.dolibarr_print_date($objp->datec)."</td>";
                print '</tr>';
                $i++;
                $var=!$var;
            
            }
          print "</table><br>";
        
          $db->free($resql);
        }
    }
}

/*
 * Dernières actions commerciales effectuées
 */

$sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.code, c.libelle, a.fk_user_author, s.nom as sname, s.idp";
$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE c.id=a.fk_action AND a.percent >= 100 AND s.idp = a.fk_soc";
if ($socidp)
{ 
  $sql .= " AND s.idp = $socidp"; 
}
$sql .= " ORDER BY a.datea DESC";
$sql .= $db->plimit($max, 0);

$resql=$db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);

  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("LastDoneTasks",$max).'</td></tr>';
  $var = true;
  $i = 0;

  while ($i < $num) 
	{
	  $obj = $db->fetch_object($resql);
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print "<td><a href=\"action/fiche.php?id=$obj->id\">".img_object($langs->trans("ShowTask"),"task");
      $transcode=$langs->trans("Action".$obj->code);
      $libelle=($transcode!="Action".$obj->code?$transcode:$obj->libelle);
      print $libelle;
	  print '</a></td>';
	  
	  print "<td>".dolibarr_print_date($obj->da)."</td>";
	  print '<td><a href="fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowCustomer"),"company").' '.$obj->sname.'</a></td>';
	  $i++;
	}
  // TODO Ajouter rappel pour "il y a des contrats à mettre en service"
  // TODO Ajouter rappel pour "il y a des contrats qui arrivent à expiration"
  print "</table><br>";

  $db->free($resql);
} 
else
{
  dolibarr_print_error($db);
}


/*
 * Actions commerciales a faire
 *
 */

$sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.code, c.libelle, a.fk_user_author, s.nom as sname, s.idp";
$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE c.id=a.fk_action AND a.percent < 100 AND s.idp = a.fk_soc";
if ($socidp)
{ 
  $sql .= " AND s.idp = $socidp"; 
}
$sql .= " ORDER BY a.datea ASC";

$resql=$db->query($sql);
if ($resql) 
{
  $num = $db->num_rows($resql);
  if ($num > 0)
    { 
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("ActionsToDo").'</td></tr>';
      $var = true;
      $i = 0;
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object($resql);
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print "<td><a href=\"action/fiche.php?id=$obj->id\">".img_object($langs->trans("ShowTask"),"task");
      $transcode=$langs->trans("Action".$obj->code);
      $libelle=($transcode!="Action".$obj->code?$transcode:$obj->libelle);
      print $libelle;
	  print '</a></td>';
	
	  print '<td>'. dolibarr_print_date($obj->da);
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
  $db->free($resql);
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
 * Propales ouvertes
 *
 */
if ($conf->propal->enabled && $user->rights->propale->lire)
{
    $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price, p.ref, p.fk_statut, ".$db->pdate("p.datep")." as dp";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
    $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = 1";
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
            $var=true;

            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("ProposalsOpened").'</td></tr>';
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $var=!$var;
                print "<tr $bc[$var]><td width=\"15%\" nowrap><a href=\"propal.php?propalid=".$obj->propalid."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$obj->ref."</a>";
    		    if ($obj->dp < (time() - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
                print "</td>";
                print "<td><a href=\"fiche.php?socid=$obj->idp\">".img_object($langs->trans("ShowCompany"),"company")." ".dolibarr_trunc($obj->nom,44)."</a></td>\n";
                print "<td align=\"right\">";
                print dolibarr_print_date($obj->dp)."</td>\n";
                print "<td align=\"right\">".price($obj->price)."</td></tr>\n";
                $i++;
                $total += $obj->price;
            }
            if ($total>0) {
                print '<tr class="liste_total"><td colspan="3" align="right">'.$langs->trans("Total")."</td><td align=\"right\">".price($total)."</td></tr>";
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
 * Dernières propales fermées
 *
 */

if ($conf->propal->enabled && $user->rights->propale->lire) {
    $NBMAX=5;
    
	$sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price, p.ref, p.fk_statut, ".$db->pdate("p.datep")." as dp";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
	$sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut > 1";
	if ($socidp)
	{ 
	  $sql .= " AND s.idp = $socidp"; 
	}
	$sql .= " ORDER BY p.rowid DESC";
	$sql .= $db->plimit($NBMAX, 0);
	
	include_once("../propal.class.php");
	$propalstatic=new Propal($db);
	
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
		print '<td nowrap>';
		print '<a href="propal.php?propalid='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' ';
		print $objp->ref.'</a></td>';
		print '<td><a href="fiche.php?socid='.$objp->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($objp->nom,44).'</a></td>';
        print "<td>&nbsp;</td>";
		print "<td align=\"right\">";
		print dolibarr_print_date($objp->dp)."</td>\n";	  
		print "<td align=\"right\">".price($objp->price)."</td>\n";
		print "<td align=\"center\">".$propalstatic->LibStatut($objp->fk_statut,0)."</td>\n";
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
 

llxFooter('$Date$ - $Revision$');
?>
