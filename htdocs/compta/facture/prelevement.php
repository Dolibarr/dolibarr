<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!	  
  \file       htdocs/compta/facture/prelevement.php
  \ingroup    facture
  \brief      Gestion des prelevement d'une facture
  \version    $Revision$
*/

require("./pre.inc.php");

$user->getrights('facture');
if (!$user->rights->facture->lire)
  accessforbidden();

$langs->load("bills");

require_once(DOL_DOCUMENT_ROOT."/facture.class.php");

if ($_GET["action"] == "new")
{
  $fact = new Facture($db);
  if ($fact->fetch($_GET["facid"]))
  {
    $result = $fact->demande_prelevement($user);
    if ($result == 0)
      {
	Header("Location: prelevement.php?facid=".$fact->id);
      }
  }
}


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

llxHeader('',$langs->trans("Bill"),'Facture - Prélèvement');

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */

if ($_GET["facid"] > 0)
{      
  $fac = New Facture($db);
  if ( $fac->fetch($_GET["facid"], $user->societe_id) > 0)
    {	  
      $soc = new Societe($db, $fac->socidp);
      $soc->fetch($fac->socidp);
      $author = new User($db);
      $author->id = $fac->user_author;
      $author->fetch();
      
      $h = 0;      
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture.php?facid='.$fac->id;
      $head[$h][1] = $langs->trans("CardBill");
      $h++;
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture/apercu.php?facid='.$fac->id;
      $head[$h][1] = $langs->trans("Apercu");
      $h++;
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$fac->id;
      $head[$h][1] = $langs->trans("Prélèvement");
      $hselected = $h;
      $h++;
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture/note.php?facid='.$fac->id;
      $head[$h][1] = $langs->trans("Note");
      $h++;      
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture/info.php?facid='.$fac->id;
      $head[$h][1] = $langs->trans("Info");
      $h++;      
      
      dolibarr_fiche_head($head, $hselected, $langs->trans("Bill")." : $fac->ref");

      /*
       *   Facture
       */
      print '<table class="border" width="100%">';
      print '<tr><td>'.$langs->trans("Company").'</td>';
      print '<td colspan="3">';
      print '<b><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
      
      print "<td>Conditions de réglement : " . $fac->cond_reglement ."</td></tr>";
      
      print '<tr><td>'.$langs->trans("Date").'</td>';
      print "<td colspan=\"3\">".strftime("%A %d %B %Y",$fac->date)."</td>\n";
      print "<td>Date limite de réglement : " . strftime("%d %B %Y",$fac->date_lim_reglement) ."</td></tr>";
      
      print '<tr><td height="10">'.$langs->trans("Author").'</td><td colspan="4">'.$author->fullname.'</td></tr>';
      
      print '<tr><td height="10">'.$langs->trans("AmountHT").'</td>';
      print '<td align="right" colspan="2"><b>'.price($fac->total_ht).'</b></td>';
      print '<td>'.$conf->monnaie.' HT</td><td>&nbsp;</td></tr>';
                
      print '<tr><td height="10">'.$langs->trans("AmountTTC").'</td>';
      print '<td align="right" colspan="2"><b>'.price($fac->total_ttc).'</b></td>';
      print '<td>'.$conf->monnaie.' HT</td><td>&nbsp;</td></tr>';

      print "</table>";

      print "<div class=\"tabsAction\">\n";
      
      // Valider
      if ($fac->statut > 0 && $fac->paye == 0 && $fac->mode_reglement == 3)
	{
	  if ($user->rights->facture->creer)
	    {
	      print '<a class="tabAction" href="prelevement.php?facid='.$fac->id.'&amp;action=new">Faire une demande de prélèvement</a>';
	    }
	}
      print "</div><br/>";
      /*
       * Prélèvement
       */

      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr class="liste_titre">';      
      print '<td align="center">Date demande</td>';
      print '<td align="center">Date traitement</td>';
      print '<td align="center">Bon prélèvement</td>';
      print '<td align="center">Utilisateur</td>';
      print '</tr>';      
      $var=True;

      $sql = "SELECT pfd.rowid, pfd.traite,".$db->pdate("pfd.date_demande")." as date_demande";
      $sql .= " ,".$db->pdate("pfd.date_traite")." as date_traite";
      $sql .= " , pfd.fk_prelevement";
      $sql .= " , u.name, u.firstname";
      $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
      $sql .= " , ".MAIN_DB_PREFIX."user as u";
      $sql .= " WHERE fk_facture = ".$fac->id;
      $sql .= " AND pfd.fk_user_demande = u.rowid";
      $sql .= " AND pfd.traite = 0";
      $sql .= " ORDER BY pfd.date_demande DESC";
      
      $result = $db->query($sql);
      if ($result)
	{
	  $num = $db->num_rows();
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object($i);	
	      $var=!$var;
	      
	      print "<tr $bc[$var]>";	      	      
	      print '<td align="center">'.strftime("%d/%m/%Y",$obj->date_demande)."</td>\n";
	      print '<td align="center">En attente de traitement</td>';
	      print '<td align="center">-</td>';
	      print '<td align="center" colspan="2">'.$obj->firstname." ".$obj->name.'</td>';	      
	      print "</tr>\n";
	      $i++;
	    }

	  $db->free();
	}
      else 
	{
	  print $db->error() . ' ' . $sql;
	}
      
      $sql = "SELECT pfd.rowid, pfd.traite,".$db->pdate("pfd.date_demande")." as date_demande";
      $sql .= " ,".$db->pdate("pfd.date_traite")." as date_traite";
      $sql .= " , pfd.fk_prelevement";
      $sql .= " , u.name, u.firstname";
      $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
      $sql .= " , ".MAIN_DB_PREFIX."user as u";
      $sql .= " WHERE fk_facture = ".$fac->id;
      $sql .= " AND pfd.fk_user_demande = u.rowid";
      $sql .= " AND pfd.traite = 1";
      $sql .= " ORDER BY pfd.date_demande DESC";
      
      $result = $db->query($sql);
      if ($result)
	{
	  $num = $db->num_rows();
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object($i);	
	      $var=!$var;
	      
	      print "<tr $bc[$var]>";
	      	      
	      print '<td align="center">'.strftime("%d/%m/%Y",$obj->date_demande)."</td>\n";

	      print '<td align="center">'.strftime("%d/%m/%Y",$obj->date_traite)."</td>\n";
	      print '<td align="center">';
	      print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$obj->fk_prelevement;
	      print '">'.$obj->fk_prelevement."</a></td>\n";

	      print '<td align="center" colspan="2">'.$obj->firstname." ".$obj->name.'</td>';
	      
	      print "</tr>\n";
	      $i++;
	    }

	  $db->free();
	}
      else 
	{
	  print $db->error() . ' ' . $sql;
	}

      print "</table>";      

    }
  else
    {
      /* Facture non trouvée */
      print $langs->trans("ErrorBillNotFound");
    }
}  

print '</div>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
