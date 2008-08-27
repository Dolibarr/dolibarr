<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/comm/index.php
        \ingroup    commercial
        \brief      Page acceuil de la zone commercial cliente
        \version    $Id$
*/
 
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/client.class.php");
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
if ($conf->propal->enabled)  require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/agenda.lib.php");

if (!$user->rights->societe->lire)
  accessforbidden();
	  
$langs->load("commercial");
$langs->load("orders");

// Sécurité accés client
$socid='';
if ($_GET["socid"]) { $socid=$_GET["socid"]; }
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

$max=5;


if ($conf->propal->enabled) $propalstatic=new Propal($db);
	


/*
 * Actions
 */

if (isset($_GET["action"]) && $_GET["action"] == 'add_bookmark')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bookmark WHERE fk_soc = ".$_GET["socid"]." AND fk_user=".$user->id;
  if (! $db->query($sql) )
    {
      dolibarr_print_error($db);
    }
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark (fk_soc, dateb, fk_user) VALUES (".$_GET["socid"].", ".$db->idate(mktime()).",".$user->id.");";
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
 * View
 */

$html = new Form($db);
$formfile = new FormFile($db);

llxHeader();

print_fiche_titre($langs->trans("CustomerArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

// Recherche Propal
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
if ($conf->contrat->enabled && $user->rights->contrat->lire)
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
    $sql = "SELECT p.rowid, p.ref, p.total_ht, s.rowid as socid, s.nom";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql.= " FROM ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."societe as s";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE p.fk_statut = 0 and p.fk_soc = s.rowid";
    if ($socid) 
    {
       $sql .= " AND s.rowid = ".$socid;
    }
    if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
 
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
                print '<tr '.$bc[$var].'><td  nowrap="nowrap">'."<a href=\"".DOL_URL_ROOT."/comm/propal.php?propalid=".$obj->rowid."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$obj->ref.'</a></td>';
                print '<td nowrap="nowrap"><a href="fiche.php?socid='.$obj->socid.'">'.dolibarr_trunc($obj->nom,18).'</a></td>';
                print '<td align="right" nowrap="nowrap">'.price($obj->total_ht).'</td></tr>';
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
if ($conf->commande->enabled && $user->rights->commande->lire)
{
    $langs->load("orders");
    $sql = "SELECT c.rowid, c.ref, c.total_ttc, s.nom, s.rowid as socid";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql.= " FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE c.fk_soc = s.rowid AND c.fk_statut = 0";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid)
    {
        $sql .= " AND c.fk_soc = ".$socid;
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
                print '<tr '.$bc[$var].'><td nowrap="nowrap"><a href="../commande/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref.'</a></td>';
                print '<td nowrap="nowrap"><a href="fiche.php?socid='.$obj->socid.'">'.dolibarr_trunc($obj->nom,18).'</a></td>';
                print '<td align="right" nowrap="nowrap">'.price($obj->total_ttc).'</td></tr>';
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
 if ($conf->bookmark->enabled)
 {
 	$sql = "SELECT s.rowid, s.nom,b.rowid as bid";
 	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."bookmark as b";
 	$sql .= " WHERE b.fk_soc = s.rowid AND b.fk_user = ".$user->id;
 	if ($socid)
  { 
    $sql .= " AND s.rowid = ".$socid; 
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
	      print '<td><a href="fiche.php?socid='.$obj->rowid.'">'.$obj->nom.'</a></td>';
	      print '<td align="right"><a href="index.php?action=del_bookmark&bid='.$obj->bid.'">';
	      print img_delete();
	      print '</a></td>';
	      print '</tr>';
	      $i++;
	    }
      print '</table>';
    }
  }
}

print '</td><td valign="top" width="70%" class="notopnoleftnoright">';


/*
 * Actions to do
 *
 */
if ($user->rights->agenda->myactions->read)
{
	show_array_actions_to_do(10);
}

/*
 * Last actions
 */
if ($user->rights->agenda->myactions->read)
{
	show_array_last_actions_done($max);
}

/*
 * Derniers clients enregistrés
 */
if ($user->rights->societe->lire)
{
    $sql = "SELECT s.rowid,s.nom,s.client,".$db->pdate("datec")." as datec";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE s.client in (1,2)";
    if ($socid)
    {
        $sql .= " AND s.rowid = $socid";
    }
    if (!$user->rights->societe->client->voir && !$socid) //restriction
    {
	      $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
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
            print '<td colspan="3">'.$langs->trans("BoxTitleLastCustomersOrProspects",$max).'</td></tr>';
            
            $i = 0;
            $var=false;
            while ($i < $num)
            {
                $objp = $db->fetch_object($resql);
                print "<tr $bc[$var]>";
                print "<td nowrap>";
				if ($objp->client == 1) print "<a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->rowid."\">".img_object($langs->trans("ShowCustomer"),"company")." ".$objp->nom."</a></td>";
				if ($objp->client == 2) print "<a href=\"".DOL_URL_ROOT."/comm/prospect/fiche.php?socid=".$objp->rowid."\">".img_object($langs->trans("ShowCustomer"),"company")." ".$objp->nom."</a></td>";
                print '<td align="right" nowrap>';
				if ($objp->client == 1) print $langs->trans("Customer");
				if ($objp->client == 2) print $langs->trans("Prospect");
				print "</td>";
                print '<td align="right" nowrap>'.dolibarr_print_date($objp->datec,'day')."</td>";
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
 * Derniers contrat
 *
 */
if ($conf->contrat->enabled && $user->rights->contrat->lire && 0) // \todo A REFAIRE DEPUIS NOUVEAU CONTRAT
{
  $langs->load("contracts");
  
  $sql = "SELECT s.nom, s.rowid, c.statut, c.rowid as contratid, p.ref, c.mise_en_service as datemes, c.fin_validite as datefin, c.date_cloture as dateclo";
  if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."product as p";
  if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
  $sql .= " WHERE c.fk_soc = s.rowid and c.fk_product = p.rowid";
  if ($socid)
  { 
      $sql .= " AND s.rowid = ".$socid; 
  }
  if (!$user->rights->societe->client->voir && !$socid) //restriction
  {
	    $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
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
	  
      $staticcontrat=new Contrat($db);
      
	  $var=false;
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object();
	      print "<tr $bc[$var]><td><a href=\"../contrat/fiche.php?id=".$obj->contratid."\">".img_object($langs->trans("ShowContract","contract"))." ".$obj->ref."</a></td>";
	      print "<td><a href=\"fiche.php?socid=".$obj->rowid."\">".img_object($langs->trans("ShowCompany","company"))." ".$obj->nom."</a></td>\n";      
	      print "<td align=\"right\">".$staticcontrat->LibStatut($obj->statut,3)."</td></tr>\n";
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
    $langs->load("propal");
    
    $sql = "SELECT s.nom, s.rowid, p.rowid as propalid, p.total as total_ttc, p.total_ht, p.ref, p.fk_statut, ".$db->pdate("p.datep")." as dp";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE p.fk_soc = s.rowid AND p.fk_statut = 1";
    if ($socid) $sql .= " AND s.rowid = ".$socid;
    if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
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
            print '<tr class="liste_titre"><td colspan="5">'.$langs->trans("ProposalsOpened").'</td></tr>';
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $var=!$var;
                print "<tr $bc[$var]>";
                print '<td nowrap="nowrap" width="140">';
                
                $propalstatic->id=$obj->propalid;
                $propalstatic->ref=$obj->ref;
                
                print '<table class="nobordernopadding"><tr class="nocellnopadd">';
                print '<td width="100" class="nobordernopadding" nowrap="nowrap">';
                print $propalstatic->getNomUrl(1);
                print '</td>';
                print '<td width="18" class="nobordernopadding" nowrap="nowrap">';
                if ($obj->dp < (time() - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
                print '</td>';
                print '<td width="16" align="center" class="nobordernopadding">';
                $filename=sanitize_string($obj->ref);
                $filedir=$conf->propal->dir_output . '/' . sanitize_string($obj->ref);
                $urlsource=$_SERVER['PHP_SELF'].'?propalid='.$obj->propalid;
                $formfile->show_documents('propal',$filename,$filedir,$urlsource,'','','','','',1);
                print '</td></tr></table>';
                
                print "</td>";
                print "<td align=\"left\"><a href=\"fiche.php?socid=".$obj->rowid."\">".img_object($langs->trans("ShowCompany"),"company")." ".dolibarr_trunc($obj->nom,44)."</a></td>\n";
                print "<td align=\"right\">";
                print dolibarr_print_date($obj->dp,'day')."</td>\n";
                print "<td align=\"right\">".price($obj->total_ttc)."</td>";
                print "<td align=\"center\" width=\"14\">".$propalstatic->LibStatut($obj->fk_statut,3)."</td>\n";
                print "</tr>\n";
                $i++;
                $total += $obj->total_ttc;
            }
            if ($total>0) {
                print '<tr class="liste_total"><td colspan="3" align="right">'.$langs->trans("Total")."</td><td align=\"right\">".price($total)."</td><td>&nbsp;</td></tr>";
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

if ($conf->propal->enabled && $user->rights->propale->lire)
{
    $NBMAX=5;
    
	$sql = "SELECT s.nom, s.rowid, p.rowid as propalid, p.total_ht, p.ref, p.fk_statut, ".$db->pdate("p.datep")." as dp";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE p.fk_soc = s.rowid AND p.fk_statut > 1";
	if ($socid)
	{ 
	  $sql .= " AND s.rowid = ".$socid; 
	}
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
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
		  print '<td nowrap="nowrap" width="140">';
		  
		  $propalstatic->id=$objp->propalid;
      $propalstatic->ref=$objp->ref;
                
      print '<table class="nobordernopadding"><tr class="nocellnopadd">';
      print '<td width="100" class="nobordernopadding" nowrap="nowrap">';
      print $propalstatic->getNomUrl(1);
      print '</td>';
		print '<td width="18" class="nobordernopadding" nowrap="nowrap">';
		print '&nbsp;';
		print '</td>';
      print '<td width="16" align="center" class="nobordernopadding">';
      $filename=sanitize_string($objp->ref);
      $filedir=$conf->propal->dir_output . '/' . sanitize_string($objp->ref);
      $urlsource=$_SERVER['PHP_SELF'].'?propalid='.$objp->propalid;
      $formfile->show_documents('propal',$filename,$filedir,$urlsource,'','','','','',1);
      print '</td></tr></table>';
      
      print '</td>';
		  
		  print '<td align="left"><a href="fiche.php?socid='.$objp->rowid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($objp->nom,44).'</a></td>';
		  print "<td align=\"right\">";
		  print dolibarr_print_date($objp->dp,'day')."</td>\n";	  
		  print "<td align=\"right\">".price($objp->total_ht)."</td>\n";
		  print "<td align=\"center\" width=\"14\">".$propalstatic->LibStatut($objp->fk_statut,3)."</td>\n";
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
