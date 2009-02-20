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
 * Génère un PDF de la première page de résumé et un tableur des communications
 */

require_once DOL_DOCUMENT_ROOT."/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/pdf/pdfdetail_standard.modeles.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/pdf/xlsdetail_nodet.modules.php";


class pdfdetail_nodet {

  function pdfdetail_nodet ($db=0, $ligne, $year, $month, $factel)
    { 
      $this->db = $db;
      $this->description = "Modèle de facture détaillée sans les communications";
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
    $xpdf = 0;
    $this->_write_pdf_file($factel, $ligne, $xpdf, 0);
  }

  function _write_pdf_file($factel, $ligne, &$pdf, $output)
    {
      $fac = new Facture($this->db,"",$factel->fk_facture);
      $fac->fetch($factel->fk_facture);  
      $fac->fetch_client();

      $objlignetel = new LigneTel($this->db);

      $result = $objlignetel->fetch($ligne);

      if (defined("FAC_OUTPUTDIR"))
	{
	  $dir  = FAC_OUTPUTDIR . "/" . $fac->ref . "/" ;
	  $file = $dir . $fac->ref . "-$ligne-detail.pdf";

	  if (strlen($objlignetel->code_analytique) > 0)
	    {
	      $file = $dir . $fac->ref . "-$ligne-$objlignetel->code_analytique-detail.pdf";
	    }
	  
	  if (! file_exists($dir))
	    {
	      umask(0);
	      if (! mkdir($dir, 0755))
		{
		  $this->error="Erreur: Le répertoire '$dir' n'existe pas et Dolibarr n'a pu le créer.";
		  return 0;
		}
	    }

	  if (file_exists($dir) OR $output)
	    {
	      if ($output == 0)
		{
		  $pdf = new pdfdetail_standard_modeles('P','mm','A4');
		}

	      $pdf->fac = $fac;

	      $pdf->factel = $this->factel;

	      $pdf->client_nom     = $fac->client->nom; 
	      $pdf->client_adresse = $fac->client->adresse;
	      $pdf->client_cp      = $fac->client->cp;
	      $pdf->client_ville   = $fac->client->ville;

	      $pdf->ligne = $ligne;

	      $pdf->year  = $this->year;
	      $pdf->month = $this->month;

	      $pdf->ligne_ville = '';
	      if ($objlignetel->code_analytique)
		{
		  $soca = new Societe($this->db);
		  $soca->fetch($objlignetel->client_id);

		  $pdf->ligne = $ligne . " (".$objlignetel->code_analytique.")";
		  $pdf->ligne_ville = $soca->ville;
		}

	      $pdf->AliasNbPages();
	      if ($output == 0)
		{
		  $pdf->Open();

		  $pdf->SetTitle($fac->ref);
		  $pdf->SetSubject("Facture détaillée");
		  $pdf->SetCreator("Dolibarr");
		  $pdf->SetAuthor("");
		  
		  $pdf->SetMargins(10, 10, 10);
		}
              $pdf->SetAutoPageBreak(1, 24);
	      $pdf->SetLineWidth(0.1);

	      $pdf->tab_top = 53;
	      $pdf->tab_height = 222;

	      /*
	       *
	       */
	      $pdf->FirstPage = 1;
	      $pdf->AddPage();
	      $this->Header($pdf, $output);

	      $pdf->SetFillColor(230,230,230);
	      /*
	       * Détails des comm
	       *
	       */

	      $pdf->SetFont('Arial','', 12);
		  
	      $Y = $pdf->tab_top + 4;
	      $pdf->SetXY(10, $Y);
	      $pdf->MultiCell(100, 4, "Détails de vos communications", 0,'L',0);

	      $pdf->SetFont('Arial','', 9);

	      $Y = $pdf->GetY();

	      $pdf->SetXY(140, $Y);
	      $pdf->MultiCell(20, 4, "Durée", 0,'R',0);

	      $pdf->SetXY(160, $Y);
	      $pdf->MultiCell(20, 4, "Nb appels", 0,'R',0);

	      $pdf->SetXY(180, $Y);
	      $pdf->MultiCell(20, 4, "Coût", 0,'R',0);

	      $sql = "SELECT count(*) as cc, sum(t.cout_vente) as cout_vente, sum(t.duree) as duree, t.dest";
	      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details as t ";
	      $sql .= " WHERE t.fk_telephonie_facture =".$factel->id;
	      $sql .= " GROUP BY t.dest";
	      $sql .= " ORDER BY cout_vente DESC";
	      $sql .= " LIMIT 10";

	      $resql = $this->db->query($sql);

	      $total_duree = 0;
	      $total_nb = 0;
	      $total_cout = 0;

	      if ( $resql )
		{
		  $num = $this->db->num_rows($resql);
		  $i = 0;

		  $pdf->SetFont('Arial','', 9);		  
		  $var = 0;
		  $line_height = 4;

		  $graph_values = array();
		  $graph_values_duree = array();
		  $graph_labels = array();

		  while ($i < $num)
		    {
		      $obj = $this->db->fetch_object($i);
		      $var=!$var;
		
		      $Y = $pdf->GetY();
      
		      $pdf->SetXY(10, $Y);
		      $pdf->MultiCell(100, $line_height, $obj->dest, 0,'L',$var);

		      if ($Y > $pdf->GetY())
			$Y = $pdf->GetY() - $line_height;


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
		      
		      $pdf->SetXY(110, $Y);
		      $pdf->MultiCell(50, $line_height,$dt, 0,'R',$var);
		      
		      $pdf->SetXY(160, $Y);
		      $pdf->MultiCell(20, $line_height,$obj->cc, 0,'R',$var);

		      $pdf->SetXY(180, $Y);
		      $pdf->MultiCell(20, $line_height,sprintf("%01.3f",$obj->cout_vente), 0,'R',$var);

		      array_push($graph_values, $obj->cc);
		      array_push($graph_values_duree, $obj->duree);
		      array_push($graph_labels, $obj->dest);

		      $total_duree = $total_duree + $obj->duree;
		      $total_nb = $total_nb + $obj->cc;
		      $total_cout = $total_cout + $obj->cout_vente;

		      $i++;
		    }
		}
	      else
		{
		  dol_syslog("Erreur SQl");
		  dol_syslog($this->db->error());
		}

	      $h = floor($total_duree / 3600);
	      $m = floor(($total_duree - ($h * 3600)) / 60);
	      $s = ($total_duree - ( ($h * 3600 ) + ($m * 60) ) );
	      
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
	      
	      $var=!$var;

	      $pdf->SetXY(10, $Y + $line_height);
	      $pdf->MultiCell(100, $line_height,"Total : ", 0,'R',$var);

	      $pdf->SetXY(110, $Y + $line_height);
	      $pdf->MultiCell(50, $line_height,$dt, 0,'R',$var);

	      $pdf->SetXY(160, $Y + $line_height);
	      $pdf->MultiCell(20, $line_height,$total_nb, 0,'R',$var);
	      
	      $pdf->SetXY(180, $Y + $line_height);
	      $pdf->MultiCell(20, $line_height,sprintf("%01.3f",$total_cout), 0,'R',$var);


	      /*
	       * Appels les plus important
	       *
	       */

	      $pdf->SetFont('Arial','', 12);
		  
	      $Y = $pdf->GetY() + 10;
	      $pdf->SetXY(10, $Y);
	      $pdf->MultiCell(100, 4, "TOP 10 des numéros appelés en coût", 0,'L',0);

	      $pdf->SetFont('Arial','', 9);

	      $Y = $pdf->GetY();
	      $pdf->SetXY(10, $Y);
	      $pdf->MultiCell(150, 4, "Destination", 0,'L',0);

	      $pdf->SetXY(140, $Y);
	      $pdf->MultiCell(20, 4, "Durée", 0,'R',0);

	      $pdf->SetXY(160, $Y);
	      $pdf->MultiCell(20, 4, "Nb appels", 0,'R',0);

	      $pdf->SetXY(180, $Y);
	      $pdf->MultiCell(20, 4, "Coût", 0,'R',0);

	      $sql = "SELECT count(*) as cc, sum(t.cout_vente) as cout_vente, sum(t.duree) as duree, t.numero, t.dest";
	      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details as t ";
	      $sql .= " WHERE fk_telephonie_facture =".$factel->id;
	      $sql .= " GROUP BY t.numero";
	      $sql .= " ORDER BY cout_vente DESC";
	      $sql .= " LIMIT 10";

	      if ( $this->db->query($sql) )
		{
		  $num = $this->db->num_rows();

		  $pdf->SetFont('Arial','', 9);
		  
		  $i = 0;
		  $var = 0;
		  $line_height = 4;

		  while ($i < $num)
		    {
		      $obj = $this->db->fetch_object($i);
		      $var=!$var;
		
		      $Y = $pdf->GetY();
      
		      $pdf->SetXY(10, $Y);
		      $pdf->MultiCell(80, $line_height, $obj->dest, 0,'L',$var);

		      if ($Y > $pdf->GetY())
			$Y = $pdf->GetY() - $line_height;

		      $pdf->SetXY(90, $Y);
		      $pdf->MultiCell(30, $line_height, $obj->numero, 0,'L',$var);

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

		      $pdf->SetXY(120, $Y);
		      $pdf->MultiCell(40, $line_height,$dt, 0,'R',$var);

		      $pdf->SetXY(160, $Y);
		      $pdf->MultiCell(20, $line_height,$obj->cc, 0,'R',$var);

		      $pdf->SetXY(180, $Y);
		      $pdf->MultiCell(20, $line_height,sprintf("%01.3f",$obj->cout_vente), 0,'R',$var);

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

	      $pdf->Image($file_graph, 11, ($pdf->GetY() + 10), 0, 0, 'JPG');

	      /*
	       *
	       */
	      if ($output == 0)
		{
		  $pdf->Close();	      
		  $pdf->Output($file);
		  dol_syslog("Write $file");
		}
	      $this->filename = $file;


	      if(file_exists($file_graph))
		{
		  unlink($file_graph);
		}

	      if ($output == 0)
		{
		  /* Génération du tableur */
		  $xlsdet = new xlsdetail_nodet($this->db);
		  $xlsdet->GenerateFile($objlignetel, $fac, $factel);
		}
	      return 0;
	    }
	  else
	    {
	      $this->error="Erreur: Le répertoire '$dir' n'existe pas et Dolibarr n'a pu le créer.";
	      return -1;
	    }
	}
      else
	{
            $this->error="Erreur: FAC_OUTPUTDIR non défini !";
            return -2;
	}
    }




  /*
   * Header
   */

  function Header(&$pdf, $output)
  {
    $pdf->SetXY(10,5);
    
    // 400x186

    $logo_file = DOL_DOCUMENT_ROOT."/../documents/logo.jpg";

    if (file_exists($logo_file))
    {
      $pdf->Image($logo_file, 10, 5, 60, 27.9, 'JPG');
    }

    $pdf->SetTextColor(0,90,200);
    $pdf->SetFont('Arial','',10);
    $pdf->SetXY(11,31);
    $pdf->MultiCell(89, 4, "Facture détaillée : ".$pdf->fac->ref);

    $pdf->SetX(11);
    $pdf->MultiCell(89, 4, "Ligne : " . $pdf->ligne);

    $pdf->SetX(11);

    $libelle = "Du ".strftime("%d/%m/%Y",$pdf->factel->get_comm_min_date($pdf->year.$pdf->month));
    $libelle .= " au ".strftime("%d/%m/%Y",$pdf->factel->get_comm_max_date($pdf->year.$pdf->month));
    $pdf->MultiCell(89, 4, $libelle, 0);

    $pdf->SetX(11);
    if ($output == 0)
      {
	$pdf->MultiCell(80, 4, "Page : ". $pdf->PageNo() ."/{nb}", 0);
      }

    // Clients spéciaux

    if ($pdf->ligne_ville)
      {
	$pdf->SetX(11);
	$pdf->MultiCell(80, 4, "Agence : ". $pdf->ligne_ville, 0);
      }

    $pdf->rect(10, 30, 95, 23);
    
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',10);

    $pdf->SetXY(107, 31);

    $pdf->MultiCell(66,4, $pdf->client_nom);

    $pdf->SetX(107);
    $pdf->MultiCell(86,4, $pdf->client_adresse . "\n" . $pdf->client_cp . " " . $pdf->client_ville);

    $pdf->rect(105, 30, 95, 23);

    /*
     * On positionne le curseur pour la liste
     */        
    $pdf->SetXY(10,$pdf->tab_top + 6);
    $pdf->colonne = 1;
    $pdf->inc = 0;
  }

  /* 
   * Footer
   */

  function Footer(&$pdf)
  {

    if ($pdf->FirstPage == 1)
      {
	$pdf->FirstPage = 0;
      }
    else
      {

	$pdf->SetFont('Arial','',8);
    
	$pdf->Text(11, $pdf->tab_top + 3,'Date');
	$pdf->Text(106, $pdf->tab_top + 3,'Date');
	
	$w = 33;
	
	$pdf->Text($w+1, $pdf->tab_top + 3,'Numéro');
	$pdf->Text($w+96, $pdf->tab_top + 3,'Numéro');
	
	$w = 47;
	
	$pdf->Text($w+1, $pdf->tab_top + 3,'Destination');
	$pdf->Text($w+96, $pdf->tab_top + 3,'Destination');
	
	$w = 86;
	
	$pdf->Text($w+1, $pdf->tab_top + 3,'Durée');
	$pdf->Text($w+96, $pdf->tab_top + 3,'Durée');
	
	$w = 98;
	
	$pdf->Text($w+1, $pdf->tab_top + 3,'HT');
	$pdf->Text($w+96, $pdf->tab_top + 3,'HT');
	
	$pdf->line(10, $pdf->tab_top + 4, 200, $pdf->tab_top + 4 );

	/* Ligne Médiane */

	$pdf->line(105, $pdf->tab_top, 105, $pdf->tab_top + $pdf->tab_height);
	
      }

    $pdf->Rect(10, $pdf->tab_top, 190, $pdf->tab_height);

  }








}
?>
