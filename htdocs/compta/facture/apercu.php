<?PHP
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	\file htdocs/compta/facture/apercu.php
		\ingroup    facture
		\brief      Page de l'onglet aperçu d'une facture
		\version    $Revision$
*/

require("./pre.inc.php");

$user->getrights('facture');
if (!$user->rights->facture->lire)
  accessforbidden();

$langs->load("bills");

require_once("../../facture.class.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

llxHeader('',$langs->trans("Bill"),'Facture');

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
      $head[$h][1] = $langs->trans("Bill")." : $fac->ref";
      $h++;
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture/apercu.php?facid='.$fac->id;
      $head[$h][1] = $langs->trans("Apercu");
      $hselected = $h;
      $h++;
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture/note.php?facid='.$fac->id;
      $head[$h][1] = $langs->trans("Note");
      $h++;      
      $head[$h][0] = DOL_URL_ROOT.'/compta/facture/info.php?facid='.$fac->id;
      $head[$h][1] = $langs->trans("Info");
      $h++;      
      
      dolibarr_fiche_head($head, $hselected, $soc->nom);

      
      /*
       *   Facture
       */
      print '<table class="border" width="100%">';
      print '<tr><td>'.$langs->trans("Customer").'</td>';
      print '<td colspan="3">';
      print '<b><a href="fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
      
      print "<td>Conditions de réglement : " . $fac->cond_reglement ."</td></tr>";
      
      print '<tr><td>'.$langs->trans("Date").'</td>';
      print "<td colspan=\"3\">".strftime("%A %d %B %Y",$fac->date)."</td>\n";
      print "<td>Date limite de réglement : " . strftime("%d %B %Y",$fac->date_lim_reglement) ."</td></tr>";
      
      print '<tr>';
      if ($conf->projet->enabled)
	{
	  $langs->load("projects");
	  print '<td height=\"10\">'.$langs->trans("Projet").'</td><td colspan="3">';
	  if ($fac->projetid > 0)
    	    {
    	      $projet = New Project($db);
    	      $projet->fetch($fac->projetid);
    	      print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$fac->projetid.'">'.$projet->title.'</a>';
    	    }
    	  else
    	    {
    	      print '-';
    	    }
    	  print "&nbsp;</td>";
	}
      else
	{
    	  print '<td height=\"10\">&nbsp;</td><td colspan="3">';
    	  print "&nbsp;</td>";
	}
      print '<td rowspan="4" valign="top">';
      
      /*
       * Documents
       *
       *
       */      
      $file = FAC_OUTPUTDIR . "/" . $fac->ref . "/" . $fac->ref . ".pdf";
      $filedetail = FAC_OUTPUTDIR . "/" . $fac->ref . "/" . $fac->ref . "-detail.pdf";
	
      if (file_exists($file))
	{
	  $encfile = urlencode($file);
	  print_titre("Documents");
	  print '<table class="border" width="100%">';
	  
	  print "<tr $bc[0]><td>Facture PDF</td>";
	  
	  print '<td><a href="'.DOL_URL_ROOT . '/document.php?file='.$encfile.'">'.$fac->ref.'.pdf</a></td>';
	  
	  print '<td align="right">'.filesize($file). ' bytes</td>';
	  print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
	  print '</tr>';
	  
	  if (file_exists($filedetail)) // facture détaillée supplémentaire
	    {
	      $encfile = urlencode($filedetail);
	      print "<tr $bc[0]><td>Facture détaillée</td>";
	      
	      print '<td><a href="'.DOL_URL_ROOT . '/document.php?file='.$encfile.'">'.$fac->ref.'-detail.pdf</a></td>';		  
	      print '<td align="right">'.filesize($filedetail). ' bytes</td>';
	      print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($filedetail)).'</td>';
	      print '</tr>';
	    }

	  $fileimage = $file.".png";
	  
	  print "</table>\n";
	}
      
      /* Conversion en image */
      
      if (!file_exists($fileimage))
	{
	  $handle = imagick_readimage( $file ) ;
	  if ( imagick_iserror( $handle ) )
	    {
	      $reason      = imagick_failedreason( $handle ) ;
	      $description = imagick_faileddescription( $handle ) ;
	      
	      print "handle failed!<BR>\nReason: $reason<BR>\nDescription: $description<BR>\n";
	    }
	  
	  imagick_convert( $handle, "PNG" ) ;

	  if ( imagick_iserror( $handle ) )
	    {
	      $reason      = imagick_failedreason( $handle ) ;
	      $description = imagick_faileddescription( $handle ) ;
	      
	      print "handle failed!<BR>\nReason: $reason<BR>\nDescription: $description<BR>\n";
	    }

	  imagick_writeimage( $handle, $file .".png");
	}

      /*
       *
       *
       */
      
      print "</td></tr>";
      
      print "<tr><td height=\"10\">".$langs->trans("Author")."</td><td colspan=\"3\">$author->fullname</td>";
      
      print '<tr><td height=\"10\">Remise globale</td>';
      print '<td align="right" colspan="2">'.$fac->remise_percent.'</td>';
      print '<td>%</td></tr>';
      
      print '<tr><td height=\"10\">'.$langs->trans("AmountHT").'</td>';
      print '<td align="right" colspan="2"><b>'.price($fac->total_ht).'</b></td>';
      print '<td>'.MAIN_MONNAIE.' HT</td></tr>';
                
      print "</table><br>";
	  

      
      
    }
  else
    {
      /* Facture non trouvée */
      print "Facture inexistante";
    }
}  

if (file_exists($fileimage))
{	  
  print '<img src="'.DOL_URL_ROOT . '/viewimage.php?file='.urlencode($fileimage).'">';
}
print '</div>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
