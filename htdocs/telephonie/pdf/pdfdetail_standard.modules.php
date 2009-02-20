<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 */

require_once DOL_DOCUMENT_ROOT."/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/pdf/pdfdetail_standard.modeles.php";

class pdfdetail_standard {

  function pdfdetail_standard ($db=0, $ligne, $year, $month, $factel)
    { 
      $this->db = $db;
      $this->description = "Modèle de facture détaillée standard";
      $this->ligne = $ligne;
      $this->year  = $year;
      $this->month = $month;
      $this->factel = $factel;
    }

  /*
   *
   *
   */

  function write_pdf_file($factel, $ligne)
    {

      $fac = new Facture($this->db,"",$factel->fk_facture);
      $fac->fetch($factel->fk_facture);  
      $fac->fetch_client();

      $objlignetel = new LigneTel($this->db);
      $result = $objlignetel->fetch($ligne);

      if (defined("FAC_OUTPUTDIR"))
	{
	  $dir = FAC_OUTPUTDIR . "/" . $fac->ref . "/" ;
	  $file = $dir . $fac->ref . "-$ligne-detail.pdf";
	  
	  if (! file_exists($dir))
	    {
	      umask(0);
	      if (! mkdir($dir, 0755))
		{
		  $this->error="Erreur: Le répertoire '$dir' n'existe pas et Dolibarr n'a pu le créer.";
		  return 0;
		}
	    }

	  if (file_exists($dir))
	    {

	      $this->pdf = new pdfdetail_standard_modeles('P','mm','A4');

	      $this->pdf->fac = $fac;

	      $this->pdf->factel = $this->factel;

	      $this->pdf->client_nom     = $fac->client->nom; 
	      $this->pdf->client_adresse = $fac->client->adresse;
	      $this->pdf->client_cp      = $fac->client->cp;
	      $this->pdf->client_ville   = $fac->client->ville;

	      $this->pdf->ligne = $ligne;

	      $this->pdf->year  = $this->year;
	      $this->pdf->month = $this->month;

	      $this->pdf->ligne_ville = '';
	      if ($objlignetel->code_analytique)
		{
		  $soca = new Societe($this->db);
		  $soca->fetch($objlignetel->client_id);

		  $this->pdf->ligne = $ligne . " (".$objlignetel->code_analytique.")";
		  $this->pdf->ligne_ville = $soca->ville;
		}

	      $this->pdf->AliasNbPages();
	      $this->pdf->Open();

	      $this->pdf->SetTitle($fac->ref);
	      $this->pdf->SetSubject("Facture détaillée");
	      $this->pdf->SetCreator("Dolibarr");
	      $this->pdf->SetAuthor("");

              $this->pdf->SetMargins(10, 10, 10);

              $this->pdf->SetAutoPageBreak(1, 24);

	      $this->pdf->SetLineWidth(0.1);

	      $this->pdf->tab_top = 53;
	      $this->pdf->tab_height = 222;

	      /*
	       *
	       *
	       */

	      $this->pdf->FirstPage = 1;
	      $this->pdf->AddPage();

	      $this->pdf->SetFillColor(230,230,230);

	      /*
	       * Détails des comm
	       *
	       */

	      $this->pdf->SetFont('Arial','', 12);
		  
	      $Y = $this->pdf->tab_top + 4;
	      $this->pdf->SetXY(10, $Y);
	      $this->pdf->MultiCell(100, 4, "Détails de vos communications", 0,'L',0);

	      $this->pdf->SetFont('Arial','', 9);

	      $Y = $this->pdf->GetY();
	      $this->pdf->SetXY(10, $Y);
	      $this->pdf->MultiCell(150, 4, "10 Destinations les plus coûteuses", 0,'L',0);

	      $this->pdf->SetXY(140, $Y);
	      $this->pdf->MultiCell(20, 4, "Durée", 0,'R',0);

	      $this->pdf->SetXY(160, $Y);
	      $this->pdf->MultiCell(20, 4, "Nb appels", 0,'R',0);

	      $this->pdf->SetXY(180, $Y);
	      $this->pdf->MultiCell(20, 4, "Coût", 0,'R',0);

	      $sql = "SELECT count(*) as cc, sum(t.cout_vente) as cout_vente, sum(t.duree) as duree, t.dest";
	      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details as t ";
	      $sql .= " WHERE t.fk_telephonie_facture =".$factel->id;
	      $sql .= " GROUP BY t.dest";
	      $sql .= " ORDER BY cout_vente DESC";
	      $sql .= " LIMIT 10";

	      $resql = $this->db->query($sql);

	      if ( $resql )
		{
		  $num = $this->db->num_rows($resql);
		  $i = 0;

		  $this->pdf->SetFont('Arial','', 9);		  
		  $var = 0;
		  $line_height = 4;

		  $graph_values = array();
		  $graph_values_duree = array();
		  $graph_labels = array();

		  while ($i < $num)
		    {
		      $obj = $this->db->fetch_object($i);
		      $var=!$var;
		
		      $Y = $this->pdf->GetY();
      
		      $this->pdf->SetXY(10, $Y);
		      $this->pdf->MultiCell(100, $line_height, $obj->dest, 0,'L',$var);

		      if ($Y > $this->pdf->GetY())
			$Y = $this->pdf->GetY() - $line_height;


		      $h = floor($obj->duree / 3600);
		      $m = floor(($obj->duree - ($h * 3600)) / 60);
		      $s = ($obj->duree - ( ($h * 3600 ) + ($m * 60) ) );

		      if ($h > 0)
			{
			  $dt = $h . " h " . $m ." min " . $s ." sec" ; 
			}
		      else
			{
			  if ($m > 0)
			    {
			      $dt = $m ." min " . $s ." sec" ; 
			    }
			  else
			    {
			      $dt =  $s ." sec" ; 
			    }
			}
		      
		      $this->pdf->SetXY(110, $Y);
		      $this->pdf->MultiCell(50, $line_height,$dt, 0,'R',$var);
		      
		      $this->pdf->SetXY(160, $Y);
		      $this->pdf->MultiCell(20, $line_height,$obj->cc, 0,'R',$var);

		      $this->pdf->SetXY(180, $Y);
		      $this->pdf->MultiCell(20, $line_height,sprintf("%01.3f",$obj->cout_vente), 0,'R',$var);

		      array_push($graph_values, $obj->cc);
		      array_push($graph_values_duree, $obj->duree);
		      array_push($graph_labels, $obj->dest);

		      $i++;
		    }
		}
	      else
		{
		  dol_syslog("Erreur SQl");
		  dol_syslog($this->db->error());
		}
	      /*
	       * Appels les plus important
	       *
	       */

	      $this->pdf->SetFont('Arial','', 12);
		  
	      $Y = $this->pdf->GetY() + 10;
	      $this->pdf->SetXY(10, $Y);
	      $this->pdf->MultiCell(100, 4, "TOP 10 des numéros appelés en coût", 0,'L',0);

	      $this->pdf->SetFont('Arial','', 9);

	      $Y = $this->pdf->GetY();
	      $this->pdf->SetXY(10, $Y);
	      $this->pdf->MultiCell(150, 4, "Destination", 0,'L',0);

	      $this->pdf->SetXY(140, $Y);
	      $this->pdf->MultiCell(20, 4, "Durée", 0,'R',0);

	      $this->pdf->SetXY(160, $Y);
	      $this->pdf->MultiCell(20, 4, "Nb appels", 0,'R',0);

	      $this->pdf->SetXY(180, $Y);
	      $this->pdf->MultiCell(20, 4, "Coût", 0,'R',0);

	      $sql = "SELECT count(*) as cc, sum(t.cout_vente) as cout_vente, sum(t.duree) as duree, t.numero, t.dest";
	      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details as t ";
	      $sql .= " WHERE fk_telephonie_facture =".$factel->id;
	      $sql .= " GROUP BY t.numero";
	      $sql .= " ORDER BY cout_vente DESC";
	      $sql .= " LIMIT 10";

	      if ( $this->db->query($sql) )
		{
		  $num = $this->db->num_rows();

		  $this->pdf->SetFont('Arial','', 9);
		  
		  $i = 0;
		  $var = 0;
		  $line_height = 4;

		  while ($i < $num)
		    {
		      $obj = $this->db->fetch_object($i);
		      $var=!$var;
		
		      $Y = $this->pdf->GetY();
      
		      $this->pdf->SetXY(10, $Y);
		      $this->pdf->MultiCell(80, $line_height, $obj->dest, 0,'L',$var);

		      if ($Y > $this->pdf->GetY())
			$Y = $this->pdf->GetY() - $line_height;

		      $this->pdf->SetXY(90, $Y);
		      $this->pdf->MultiCell(30, $line_height, $obj->numero, 0,'L',$var);

		      $h = floor($obj->duree / 3600);
		      $m = floor(($obj->duree - ($h * 3600)) / 60);
		      $s = ($obj->duree - ( ($h * 3600 ) + ($m * 60) ) );

		      if ($h > 0)
			{
			  $dt = $h . " h " . $m ." min " . $s ." sec" ; 
			}
		      else
			{
			  if ($m > 0)
			    {
			      $dt = $m ." min " . $s ." sec" ; 
			    }
			  else
			    {
			      $dt =  $s ." sec" ; 
			    }
			}

		      $this->pdf->SetXY(120, $Y);
		      $this->pdf->MultiCell(40, $line_height,$dt, 0,'R',$var);

		      $this->pdf->SetXY(160, $Y);
		      $this->pdf->MultiCell(20, $line_height,$obj->cc, 0,'R',$var);

		      $this->pdf->SetXY(180, $Y);
		      $this->pdf->MultiCell(20, $line_height,sprintf("%01.3f",$obj->cout_vente), 0,'R',$var);

		      $i++;
		    }
		}
	      /*
	       *
	       *
	       */
	      include_once ("/usr/share/jpgraph/jpgraph.php");
	      include_once ("/usr/share/jpgraph/jpgraph_pie.php");

	      $graph = new PieGraph(450,200,"auto");
	      $graph->img->SetImgFormat("jpeg");
	      $graph->SetFrame(false);

	      // Setup title
	      $graph->title->Set("Répartition des destinations en nombre d'appel");
	      $graph->title->SetFont(FF_FONT1,FS_BOLD);
	      
	      $p1 = new PiePlot($graph_values);
	      $p1->SetCenter(0.25,0.5);
	      
	      // Label font and color setup
	      $p1->SetFont(FF_FONT1,FS_BOLD);
	      $p1->SetFontColor("darkred");
	      $p1->SetSize(0.3);	      
	      $p1->SetLegends($graph_labels);
	      $graph->legend->Pos(0.05,0.15);
	      
	      $graph->Add($p1);

	      $file_graph = "/tmp/graph".$factel->ligne.".jpg";

	      $handle = $graph->Stroke($file_graph);

	      $this->pdf->Image($file_graph, 11, ($this->pdf->GetY() + 10), 0, 0, 'JPG');

	      /*
	       * Liste des appels
	       *
	       *
	       *
	       */

	      $sql = "SELECT t.ligne, ".$this->db->pdate("t.date")." as pdate";
	      $sql .= " , t.numero, t.dest, t.duree, t.cout_vente";
	      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details as t ";
	      $sql .= " WHERE fk_telephonie_facture =".$factel->id;
	      $sql .= " ORDER BY t.date ASC";
	
	      $resql = $this->db->query($sql) ;
      
	      if ( $resql )
		{
		  $num = $this->db->num_rows($resql);

		  $this->pdf->AddPage();
		  
		  $i = 0;
		  $var = 1;
		  $line_height = 2;
		  $this->colonne = 1;

		  while ($i < $num)
		    {
		      $obj = $this->db->fetch_object($resql);

		      $Y = $this->pdf->GetY();
		
		      if ($this->inc > 106 && $this->colonne == 1)
			{
			  $col = 95;
			  $Y = $this->pdf->tab_top + 6;
			  $this->inc = 0;
			  $this->colonne = 2;
			  $old_dest='';
			  $old_date='';
			}

		      if ($this->inc > 106 && $this->colonne == 2)
			{
			  $this->pdf->AddPage();
			  $var = 0;
			  $col = 0;
			  $Y = $this->pdf->tab_top + 6;
			  $this->inc = 0;
			  $old_dest='';
			  $old_date='';
			  $this->colonne = 1;
			}

		      $var=!$var;

		      $this->pdf->SetFont('Arial','', 6);

		      $this->pdf->SetXY (10 + $col, $Y);

		      if ($old_date == strftime("%d/%m/%Y", $obj->pdate))
			{
			  $date = "";
			}
		      else
			{
			  $old_date = strftime("%d/%m/%Y", $obj->pdate) ;
			  $date = strftime("%d/%m/%y",$obj->pdate);
			}

		      $this->pdf->MultiCell(11, $line_height, $date, 0,'L',$var);

		      if ($Y > $this->pdf->GetY())
			$Y = $this->pdf->GetY() - $line_height;

		      $this->pdf->SetXY (21 + $col, $Y);
		      $heure = strftime("%H:%M:%S",$obj->pdate);
		      $this->pdf->MultiCell(11, $line_height, $heure, 0,'L',$var);

		      $this->pdf->SetXY (32 + $col, $Y);
		      $numero = ereg_replace("^00","",$obj->numero);
		      $this->pdf->MultiCell(17, $line_height, $numero, 0,'L',$var);

		      $this->pdf->SetXY (48 + $col, $Y);

		      if ($obj->dest == $old_dest)
			{
			  $dest = ' "';
			}
		      else
			{
			  $old_dest = $obj->dest ;
			  $dest = $obj->dest;
			}

		      $this->pdf->MultiCell(37, $line_height, $dest, 0, 'L',$var);

		      $this->pdf->SetXY (85 + $col, $Y);
		      $this->pdf->MultiCell(10, $line_height, $obj->duree, 0, 'R',$var);

		      $this->pdf->SetXY (95 + $col, $Y);
		      $this->pdf->MultiCell(10, $line_height, sprintf("%01.3f", $obj->cout_vente), 0,'R',$var);

		      $i++;
		      $this->inc++;
		    }
		}
	      else
		{
		  dol_syslog("Erreur lecture des communications");
		}

	      /*
	       *
	       */
	     
	      $this->pdf->Close();	      
	      $this->pdf->Output($file);

	      $this->filename = $file;

	      dol_syslog("Write $file");

	      if(file_exists($file_graph))
		{
		  unlink($file_graph);
		}

	      return 0;
	    }
	  else
	    {
	      $this->error="Erreur: répertoire '$dir' n'existe pas, créa impossible.";
	      return -1;
	    }
	}
      else
	{
            $this->error="Erreur: FAC_OUTPUTDIR non défini !";
            return -2;
	}
    }
}
?>
