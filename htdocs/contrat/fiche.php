<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require("./pre.inc.php");
require("./contrat.class.php");
require("../facture.class.php");

llxHeader();

$id = $_GET["id"];
$mesg = '';

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


if ($action == 'add')
{
  $contrat = new Contrat($db);

  $id = $product->create($user);
  $action = '';
}

if ($_POST["action"] == 'miseenservice')
{
  $contrat = new Contrat($db);
  $contrat->id = $id;
  $contrat->fetch($id);
  $contrat->mise_en_service($user, 
    mktime($_POST["date_starthour"],$_POST["date_startmin"],0,$_POST["date_startmonth"],$_POST["date_startday"],$_POST["date_startyear"]),
    0,
    mktime($_POST["date_endhour"],$_POST["date_endmin"],0,$_POST["date_endmonth"],$_POST["date_endday"],$_POST["date_endyear"])
    );
}

if ($_GET["action"] == 'cloture')
{
  $contrat = new Contrat($db);
  $contrat->id = $id;
  $contrat->cloture($user);
}

if ($_GET["action"] == 'annule')
{
  $contrat = new Contrat($db);
  $contrat->id = $id;
  $contrat->annule($user);
}


if ($action == 'update' && $cancel <> 'Annuler')
{
  $product = new Product($db);

  $product->ref = $_POST["ref"];
  $product->libelle = $_POST["libelle"];
  $product->price = $_POST["price"];
  $product->tva_tx = $_POST["tva_tx"];
  $product->description = $_POST["desc"];
  $product->envente = $_POST["statut"];
  $product->duration_value = $_POST["duration_value"];
  $product->duration_unit = $_POST["duration_unit"];

  if (  $product->update($id, $user))
    {
      $action = '';
      $mesg = 'Fiche mise à jour';
    }
  else
    {
      $action = 'edit';
      $mesg = 'Fiche non mise à jour !' . "<br>" . $product->mesg_error;
    }
}
$html = new Form($db);


/*
 * Fiche création
 *
 */
if ($action == 'create')
{
  print "<form action=\"$PHP_SELF?type=".$_POST["type"]."\" method=\"post\">\n";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">\n";
  print '<input type="hidden" name="type" value="'.$_POST["type"].'">'."\n";
  print '<div class="titre">Nouveau</div><br>'."\n";
      
  print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
  print "<tr>";
  print '<td>Référence</td><td><input name="ref" size="20" value=""></td></tr>';
  print '<td>Libellé</td><td><input name="libelle" size="40" value=""></td></tr>';
  print '<tr><td>Prix de vente</td><TD><input name="price" size="10" value=""></td></tr>';    
  print '<tr><td>Taux TVA</td><TD>';
  print $html->select_tva("tva_tx");
  print ' %</td></tr>';    
  print "<tr><td valign=\"top\">Description</td><td>";
  print '<textarea name="desc" rows="8" cols="50">';
  print "</textarea></td></tr>";
  if ($_POST["type"] == 1)
    {
        // Si contrat de type service
      print '<tr><td>Durée</td><td><input name="duration_value" size="6" value="'.$product->duree.'">';
      print '<input name="duration_unit" type="radio" value="d">jour&nbsp;';
      print '<input name="duration_unit" type="radio" value="w">semaine&nbsp;';
      print '<input name="duration_unit" type="radio" value="m">mois&nbsp;';
      print '<input name="duration_unit" type="radio" value="y">année';
      print '</td></tr>';
    }
  
  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';      
}
else
{
/*
 * Fiche visu/édition
 *
 */
  if ($id)
    {
      $contrat = new Contrat($db);
      $result = $contrat->fetch($id);

      if ( $result )
    { 
      $date_start='';
      $date_end='';
          
	  print $mesg;

    /*
     * Affichage onglets
     */
    $h = 0;

    $hselected=$h;
    $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$socid;
    $head[$h][1] = 'Fiche contrat : '.$contrat->id;
    $h++;

    dolibarr_fiche_head($head, $hselected);

      
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
	  print "<tr>";
	  print '<td width="20%">Service</td><td colspan="3">'.($contrat->product->ref).' - '.($contrat->product->label_url).'</td>';
	  print '</tr><tr>';	
	  if ($contrat->factureid)
	    {
	      print '<td>Société</td><td>'.$contrat->societe->nom_url.'</td>';
	      print '<td>Facture</td><td><a href="../compta/facture.php?facid='.$contrat->factureid.'">Facture</td>';
	    }
	  else
	    {
	      print '<td>Société</td><td colspan="3">'.$contrat->societe->nom_url.'</td></tr>';
	    }

      // Affiche statut contrat
      $now=mktime();
      if ($contrat->enservice == 1)
	{
        if (! $contrat->date_fin_validite || $contrat->date_fin_validite >= $now) {
      	  $class = "normal";
    	  $statut="En service";
        }
        else {            
      	  $class = "error";
    	  $statut="<b>En service, expiré</b>";
        }
	}
      elseif($contrat->enservice == 2)
	{
   	  $class = "normal";
	  $statut= "Cloturé";
	}
      else
	{
  	  $class = "warning";
	  $statut= "<b>Ce contrat n'est pas en service</b>";
	}
	  print "<tr><td>Statut</td><td colspan=\"3\" class=\"$class\">$statut</td></tr>\n";

	  if ($request == 'miseenservice')
	    {
    	  // Si contrat lié à une ligne de facture, on recherche date debut et fin de la ligne
    	  if ($contrat->facturedetid) {
    	    $facturedet = new FactureLigne($db);
    	    $facturedet->fetch($contrat->facturedetid);
            $date_start=$facturedet->date_start;
	        $date_end=$facturedet->date_end;
    	  }

          // Si date_start et date_end ne sont pas connues de la ligne de facture, on les
          // definit à une valeur par défaut en fonction de la durée définie pour le service.
          if (! $date_start) { $date_start=mktime(); }
          if (! $date_end) {
            if ($contrat->product->duration) {
                // Si duree du service connue
                $duree_value = substr($contrat->product->duration,0,strlen($contrat->product->duration)-1);
                $duree_unit = substr($contrat->product->duration,-1);
        
                $month = date("m",$date_start);
                $day = date("d",$date_start);
                $year = date("Y",$date_start);
        
                switch($duree_unit) 
                  {
                  case "d":
            	$day = $day + $duree_value;
            	break;
                  case "w":
            	$day = $day + ($duree_value * 7);
            	break;
                  case "m":
            	$month = $month + $duree_value;
            	break;
                  case "y":
            	$year = $year + $duree_value;
            	break;
                  }
                $date_end = mktime(date("H",$date_start), date("i",$date_start), 0, $month, $day, $year);
            }
          }


	      print '<form action="fiche.php?id='.$id.'" method="post">';
	      print '<input type="hidden" name="action" value="miseenservice">';
	      
	      print '<tr><td>Durée standard pour ce service</td><td colspan="3">';
	      print $contrat->product->duration;
	      print '<input type="hidden" name="duration" value="'.$contrat->product->duration.'">';
	      print '</td></tr>';

	      // Date de début de mise en service
	      print '<tr><td>Date de mise en service</td><td colspan="3">';
	      print $html->select_date($date_start,'date_start',1,1);
	      print "&nbsp;";
	      print '</td></tr>';

	      // Date de fin prévue de mise en service
	      print '<tr><td>Date de fin prévue</td><td colspan="3">';
	      print $html->select_date($date_end,'date_end',1,1);
	      print "&nbsp;";
	      print '</td></tr>';
	      
	      print '<tr><td colspan="4" align="center">';
	      print '<input type="submit" value="Enregistrer">';
	      print '</td></tr>';
	      print '</form>';
	    }

	  if ($contrat->enservice > 0)
	    {
	      print "<tr><td valign=\"top\">Mis en service</td><td>".dolibarr_print_date($contrat->mise_en_service,"%d %B %Y à %H:%M");
	      print "</td>";
	      $contrat->user_service->fetch();
	      print '<td>par</td><td>'.$contrat->user_service->fullname.'</td></tr>';
	      
	      print '<tr><td valign="top">Fin de validité</td><td colspan="3">'.dolibarr_print_date($contrat->date_fin_validite,"%d %B %Y à %H:%M");
	    }
	  
	  if ($contrat->enservice == 2)
	    {
	      print "<tr><td valign=\"top\">Cloturé</td><td>".dolibarr_print_date($contrat->date_cloture,"%d %B %Y à %H:%M")."</td>";
	      $contrat->user_cloture->fetch();
	      print '<td>par</td><td>'.$contrat->user_cloture->fullname.'</td></tr>';
	    }


	  print "</table>";
      print '<br>';
      print '</div>';
	}
      
    }
  else
    {
      print "Error";
    }
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */


print '<div class="tabsAction">';

if (! $contrat->enservice)
{
    if ($request != 'miseenservice') {
        print '<a class="tabAction" href="fiche.php?request=miseenservice&id='.$id.'">Mettre en service...</a>';
    } else {
        print '<a class="tabAction" href="fiche.php?id='.$id.'">Ne pas mettre en service</a>';
    }
}
elseif ($contrat->enservice == 1)
{
  print '<a class="tabAction" href="fiche.php?action=annule&id='.$id.'">Mettre hors service</a>';
  print '<a class="tabAction" href="fiche.php?action=cloture&id='.$id.'">Clôturer</a>';
}
print '</div>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
