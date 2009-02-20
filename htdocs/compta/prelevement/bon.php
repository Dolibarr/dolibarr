<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/compta/prelevement/bon.php
        \ingroup    prelevement
        \brief      Fiche aperçu du bon de prelevement
        \version    $Id$
*/

require("./pre.inc.php");

$langs->load("bills");

/*
 * Sécurité accés client
 */
if (!$user->rights->prelevement->bons->lire) accessforbidden();


llxHeader('','Bon de prélèvement');

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Card");
$h++;      

if ($conf->use_preview_tabs)
{
    $head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/bon.php?id='.$_GET["id"];
    $head[$h][1] = $langs->trans("Preview");
    $hselected = $h;
    $h++;  
}

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/lignes.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Lines");
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/factures.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Bills");
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-rejet.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Rejets");
$h++;  

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-stat.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Statistics");
$h++;  

$prev_id = $_GET["id"];

$html = new Form($db);

if ($_GET["id"])
{
  $bon = new BonPrelevement($db,"");

  if ($bon->fetch($_GET["id"]) == 0)
    {
      dol_fiche_head($head, $hselected, 'Prélèvement : '. $bon->ref);

      print '<table class="border" width="100%">';

      print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td>'.$bon->ref.'</td></tr>';
      print '<tr><td width="20%">'.$langs->trans("Amount").'</td><td>'.price($bon->amount).'</td></tr>';
      print '<tr><td width="20%">'.$langs->trans("File").'</td><td>';

      $relativepath = 'bon/'.$bon->ref;

      print '<a href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart=prelevement&amp;file='.urlencode($relativepath).'">'.$bon->ref.'</a>';

      print '</td></tr>';
      print '</table><br />';

      $fileimage = DOL_DATA_ROOT.'/prelevement/bon/'.$bon->ref.'.ps.png.0';
      $fileps = DOL_DATA_ROOT.'/prelevement/bon/'.$bon->ref.'.ps';

      // Conversion du PDF en image png si fichier png non existant
      if (!file_exists($fileimage))
        {
	  print $fileimage;
	  if (function_exists(imagick_readimage))
	    {

	      $handle = imagick_readimage( $fileps ) ;
	      
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
	      
	      imagick_writeimage( $handle, $fileps .".png");
	    }
	  else
	    {
	      print "Les fonctions <i>imagick</i> ne sont pas disponibles sur ce PHP";
	    }
        }

      if (file_exists($fileimage))
	{
	  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=prelevement&file='.urlencode(basename($fileimage)).'">';
	  
	}
    }
  else
    {
      dol_print_error($db);
    }
}

print "</div>";

llxFooter('$Date$ - $Revision$');
?>
