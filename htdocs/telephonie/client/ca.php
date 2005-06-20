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

require("./pre.inc.php");

$mesg = '';

llxHeader("","Téléphonie - Client");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}
/*
 * Affichage
 *
 */

if ($_GET["id"])
{
  $soc = new Societe($db);
  $result = $soc->fetch($_GET["id"]);

  if ( $result == 1 )
    { 
      if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	{
	  $h=0;
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/fiche.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Contrats");
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/lignes.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Lignes");
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/factures.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Factures");
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/ca.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("CA");
	  $hselected = $h;
	  $h++;
	  	
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/tarifs.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Tarifs");
	  $h++;


	  dolibarr_fiche_head($head, $hselected, 'Client : '.$soc->nom);

	  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
	  print '<tr><td width="20%">'.$langs->trans('Name').'</td><td>'.$soc->nom.'</td>';
	  print '<td>'.$soc->code_client.'</td></tr>';
	  
	  print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"2\">".nl2br($soc->adresse)."<br>".$soc->cp." ".$soc->ville." ".$soc->pays."</td></tr>";
	  

	  print '</table><br />';


	  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
	  print '<tr><td width="50%" align="center">Chiffre d\'affaire</td>';
	  print '<td width="50%" align="center">Gain</td></tr>';

	  print '<tr><td width="50%" valign="top" align="center">';

	  $mesg_no_graph = 'Nous avons pas assez de données à ce jour pour générer ce graphique.';

	  $img_root = DOL_DATA_ROOT."/graph/".substr($soc->id,-1)."/telephonie/client/";

	  $file = $img_root.$soc->id."/graphca.png";

	  if (file_exists($file)) 
	    {
	      print '<img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="CA Mensuel">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  print '</td><td width="50%" valign="top" align="center">';

	  $file = $img_root.$soc->id."/graphgain.png";
	  if (file_exists($file)) 
	    {
	      print '<img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="CA Mensuel">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  print '</td></tr>';


	  print '<tr><td width="50%" align="center">Appels</td>';
	  print '<td width="50%" align="center">Communications</td></tr>';

	  print '<tr><td width="50%" valign="top" align="center">';

	  $file = $img_root.$soc->id."/graphappelsdureemoyenne.png";

	  if (file_exists($file)) 
	    {
	      print '<img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="CA Mensuel">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  print '</td><td width="50%" valign="top" align="center">';

	  $file = $img_root.$soc->id."/nb-comm-mensuel.png";

	  if (file_exists($file)) 
	    {
	      print '<img src="'.DOL_URL_ROOT.'/telephonie/showgraph.php?graph='.$file.'" alt="CA Mensuel">';
	    }
	  else
	    {
	      print $mesg_no_graph;
	    }

	  print '</td></tr></table>';
	}
    }
}
else
{
  print "Error";
}

print '</div>';

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
