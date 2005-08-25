<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require "./pre.inc.php";
require_once DOL_DOCUMENT_ROOT."/lib/dolibarrmail.class.php";

$mesg = '';

llxHeader("","","Fiche Contrat");

if ($_GET["id"])
{
  if ($_GET["action"] <> 're-edit')
    {
      $contrat = new TelephonieContrat($db);
      
      if ($contrat->fetch($_GET["id"]) == 0)
	{
	  $result = 1;
	}
    }
  
  if ( $result )
    { 
      if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	{
	  
	  $h=0;
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/fiche.php?id=".$contrat->id;
	  $head[$h][1] = $langs->trans("Contrat");
	  $h++;
	  
	  $nser = $contrat->count_associated_services();
	  
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/services.php?id=".$contrat->id;
	  if ($nser > 0)
	    {
	      $head[$h][1] = $langs->trans("Services")." (".$nser.")";
	    }
	  else
	    {
	      $head[$h][1] = $langs->trans("Services");
	    }
	  $h++;
	  
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/stats.php?id=".$contrat->id;
	  $head[$h][1] = $langs->trans("Stats");
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/contrat/info.php?id=".$contrat->id;
	  $head[$h][1] = $langs->trans("Infos");
	  $hselected = $h;
	  $h++;

	  dolibarr_fiche_head($head, $hselected, 'Contrat : '.$contrat->ref);
	  
	  print_fiche_titre('Fiche Contrat', $mesg);
	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  
	  $client_comm = new Societe($db, $contrat->client_comm_id);
	  $client_comm->fetch($contrat->client_comm_id);
	  
	  print '<tr><td width="20%">Référence</td><td>'.$contrat->ref.'</td>';
	  print '<td>Facturé : '.$contrat->facturable.'</td></tr>';
	  
	  print '<tr><td width="20%">Client</td><td>';
	  print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$client_comm->id.'">';
	  
	  print $client_comm->nom.'</a></td><td>'.$client_comm->code_client;
	  print '</td></tr>';
	  
	  $commercial = new User($db, $contrat->commercial_sign_id);
	  $commercial->fetch();
	  
	  print '<tr><td width="20%">Commercial Signature</td>';
	  print '<td colspan="2">'.$commercial->fullname.'</td></tr>';
	  
	  $commercial_suiv = new User($db, $contrat->commercial_suiv_id);
	  $commercial_suiv->fetch();
	  
	  print '<tr><td width="20%">Commercial Suivi</td>';
	  print '<td colspan="2">'.$commercial_suiv->fullname.'</td></tr>';
	  
	  $cuser_suiv = new User($db, $contrat->user_creat);
	  $cuser_suiv->fetch();
	  
	  print '<tr><td width="20%">Créé par</td>';
	  print '<td colspan="2">'.$cuser_suiv->fullname;
	  print '</td></tr>';

	  print '<tr><td width="20%">Créé le</td>';
	  print '<td colspan="2">'.strftime("%e %B %Y",$contrat->date_creat);
	  print '</td></tr>';

	  print "</table><br />";
	}
    }
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
