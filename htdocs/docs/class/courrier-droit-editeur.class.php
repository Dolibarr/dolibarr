<?PHP
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   \file       	docs/class/courrier-droit-editeur.class.php
   \ingroup    	editeurs
   \brief      	Classe de generation des courriers pour les editeurs
   \version		$Id$
*/

require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');


class pdf_courrier_droit_editeur 
{
  /**
     \brief      Constructeur
     \param	    db		Handler accès base de donnée
  */
  function pdf_courrier_droit_editeur ($db)
  {
    $this->langs = $langs;
    
    $this->db = $db;
    
    // Dimension page pour format A4
    $this->type = 'pdf';
    $this->page_largeur = 210;
    $this->page_hauteur = 297;
    $this->format = array($this->page_largeur,$this->page_hauteur);
    $this->marge_gauche=10;
    $this->marge_droite=10;
    $this->marge_haute=10;
    $this->marge_basse=10;

    $this->name = "Courrier des droits ".strftime("%Y", time());
    $this->file = '1'.strftime("%Y", time()).'.pdf';
  }

  /**
     \brief Génère le document
     \return int 0 = ok, <> 0 = ko
  */
  function Generate($numero)
  {
    global $conf;
    
    $this->file = $numero.strftime("%Y", time());
    $this->extension = "pdf";

    dolibarr_syslog("pdf_courrier_droit_editeur::Generate ", LOG_DEBUG );

    require_once(FPDF_PATH.'fpdf.php');
    require_once(DOL_DOCUMENT_ROOT."/product.class.php");
    require_once(DOL_DOCUMENT_ROOT."/product/canvas/product.livre.class.php");
    $error = 0;
    $year = strftime("%Y", time());
    
    // 
    $sql = "SELECT s.rowid,s.nom";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
    //$sql .= " , ".MAIN_DB_PREFIX."categorie_fournisseur as cf";
    $sql .= " WHERE s.fournisseur = 1 ";
    //$sql .= " AND s.rowid = cf.fk_societe";
    //$sql .= " AND cf.fk_categorie = 2";
    
    $resql=$this->db->query($sql);

    if ($resql) 
    {
    	$fichref = "Droits-$year";
	    $dir_all = DOL_DATA_ROOT."/ged/" . get_exdir($numero);
	    $file_all = $dir_all . $numero . ".pdf";
	
	    // Initialisation document vierge
      $pdf_all=new FPDI_Protection('P','mm',$this->format);
                               		
	    // Protection et encryption du pdf
      if ($conf->global->PDF_SECURITY_ENCRYPTION)
      {
         $pdfrights = array('print'); // Ne permet que l'impression du document
         $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
         $pdfownerpass = NULL; // Mot de passe du propriétaire, créé aléatoirement si pas défini
         $pdf_all->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
      }
	
	    $pdf_all->Open();


	    while ($obj = $this->db->fetch_object($resql) )
	    {
	      $id = $obj->rowid;
	    
	      dolibarr_syslog("droits-editeurs.php id:$id", LOG_DEBUG );
	    
	      $coupdf = new pdf_courrier_droit_editeur($this->db, $langs);

	      $fichref = "Droits-$year";
	      $dir = DOL_DATA_ROOT."/societe/courrier/" . get_exdir($id);
	      $file = $dir . $fichref . ".pdf";
	    
	      // Initialisation document vierge
        $pdf=new FPDI_Protection('P','mm',$this->format);
                        
        // Protection et encryption du pdf
        if ($conf->global->PDF_SECURITY_ENCRYPTION)
        {
     	    $pdfrights = array('print'); // Ne permet que l'impression du document
    	    $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
     	    $pdfownerpass = NULL; // Mot de passe du propriétaire, créé aléatoirement si pas défini
     	    $pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
        }
	    
	      $pdf->Open();

	      $coupdf->Write($id, $dir, $year, $pdf);
	      $coupdf->Write($id, $dir_all, $year, $pdf_all);
	    
	      $pdf->Close();	    
	      $pdf->Output($file);
	      dolibarr_syslog("droits-editeurs.php write $file", LOG_DEBUG );
	    }   
	
	   $pdf_all->Close();	    
	   $pdf_all->Output($file_all);
	   dolibarr_syslog("droits-editeurs.php write $fileall", LOG_DEBUG );
	
      }
    else
      {
	dolibarr_syslog("pdf_courrier_droit_editeur::Generate ".$db->error(), LOG_ERR );
      }

    return 0;
  }
  
  /**
     \brief      Fonction générant le fichier
     \param	    id	    id de la societe
     \return	    int     1=ok, 0=ko
  */
  function Write($id, $dir, $year, &$pdf)
  {
    dolibarr_syslog("pdf_courrier_droit_editeur::Write $id,$year ", LOG_DEBUG );
    $soc = new Societe($this->db);
    $soc->fetch($id);
    	
    if (! file_exists($dir))
      {
	if (create_exdir($dir) < 0)
	  {
	    $this->error=$this->langs->trans("ErrorCanNotCreateDir",$dir);
	    return 0;
	  }
      }

    if (file_exists($dir))
      {
	// Initialisation document vierge


	$books = array();
	$year_data = $year - 1;

	// On récupère données du mail
	$sql = "SELECT p.rowid,p.label, pc.taux, pc.quantite";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= " , ".MAIN_DB_PREFIX."facturedet as fd";
	$sql .= " , ".MAIN_DB_PREFIX."product as p";
	$sql .= " , ".MAIN_DB_PREFIX."product_cnv_livre as pl";
	$sql .= " , ".MAIN_DB_PREFIX."product_cnv_livre_contrat as pc";

	$sql .= " WHERE fd.fk_facture = f.rowid";
	$sql .= " AND fd.fk_product = p.rowid";
	$sql .= " AND p.canvas = 'livre'";
	$sql .= " AND p.rowid = pl.rowid";
	$sql .= " AND pl.fk_contrat = pc.rowid";
	$sql .= " AND year (f.datef) <= ".($year_data);
	$sql .= " GROUP BY p.rowid";

	$resql=$this->db->query($sql);
	if ($resql) 
	  {
	    $i = 0;
	    while ($obj = $this->db->fetch_object($resql) )
	    {
		    $books[$i]['title'] = $obj->label;
		    $books[$i]['id'] = $obj->rowid;
		    $books[$i]['taux'] = $obj->taux;
		    $books[$i]['qty'] = $obj->quantite;

		    $i++;
	    }   
	    $this->db->free($resql);
	  }
	else
	  {
	    print $this->db->error();	    
	    print "$sql\n";
	  }

	foreach($books as $ref => $value)
	{
	  $livre = new ProductLivre($this->db);
	  $livre->FetchCanvas($value['id']);

	  $pdf->AddPage();
	  $qtycontrat = $value['qty'];
	  /*
	   * Adresse 
	   */


	  // Client destinataire
	  $posy=42;
	  $pdf->SetTextColor(0,0,0);
	  $pdf->SetFont('Arial','',8);
	  $pdf->SetXY(102,$posy-5);

	  // Nom client
	  $pdf->SetXY(102,$posy+3);
	  $pdf->SetFont('Arial','B',11);
	  $pdf->MultiCell(106,4, $soc->nom, 0, 'L');

	  // Caractéristiques client
	  $carac_client=$soc->adresse;
	  $carac_client.="\n".$soc->cp . " " . $soc->ville."\n";
	  $carac_client.=$soc->pays."\n";	

	  $pdf->SetFont('Arial','',9);
	  $pdf->SetXY(102,$posy+8);
	  $pdf->MultiCell(86,4, $carac_client);	    	  
	  /*
	   *
	   *
	   */
	  $pdf->SetTextColor(0,0,0);
	  $pdf->SetFont('Arial','',10);
	  
	  $pdf->SetXY(10,100);
	  
	  $pdf->MultiCell(190,5,"Je vous prie de trouver ci-dessous le récapitulatif des ventes du titre cité pour la période du 1er janvier au 31 décembre $year_data.");
	  
	  $pdf->SetXY(10,120);
	  $pdf->MultiCell(25,5,"Nom du titre : ");
	  $pdf->SetFont('Arial','B',10);
	  $pdf->SetXY(35,120);
	  $pdf->MultiCell(140,5,$value['title']);

	  $pdf->SetFont('Arial','',10);
	  $pdf->SetXY(10,140);
	  $pdf->MultiCell(46,5,"Quantité signée au contrat : ");
	  
	  $pdf->SetFont('Arial','B',10);
	  $pdf->SetXY(56,140);
	  $pdf->MultiCell(14,5,$qtycontrat,0,'R');	       	
	  	  
	  $sql = "SELECT p.label, sum(fd.qty), date_format(f.datef,'%Y')";
	  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	  $sql .= " , ".MAIN_DB_PREFIX."facturedet as fd";
	  $sql .= " , ".MAIN_DB_PREFIX."product as p";
	  $sql .= " WHERE fd.fk_facture = f.rowid";
	  $sql .= " AND p.rowid = '".$value['id']."'";
	  $sql .= " AND fd.fk_product = p.rowid";
	  $sql .= " AND p.canvas = 'livre'";
	  $sql .= " GROUP BY p.rowid, date_format(f.datef,'%Y') ORDER BY date_format(f.datef,'%Y') ASC";
	  	  
	  $resql=$this->db->query($sql);

	  $qtysell = 0;

	  if ($resql) 
	    {
	      $i = 0;
	      while ($row = $this->db->fetch_row($resql) )
		{
		  $i++;
		  
		  $pdf->SetFont('Arial','',10);
		  $pdf->SetXY(10,140 + ($i * 8) );
		  $pdf->MultiCell(44,5,"Quantité vendue en ".$row[2]." : ",0);
		  $pdf->SetFont('Arial','B',10);
		  $pdf->SetXY(54,140 + ($i * 8) );
		  $pdf->MultiCell(16,5,$row[1],0,'R');
		  
		  $qtysell += $row[1];
		  
		}   
	      $this->db->free($resql);
	    }
	  else
	    {
	      print $this->db->error();	    
	    }
	  

	  $pdf->SetFont('Arial','',10);
	  $pdf->SetXY(100,140 + ($i * 8) );
	  $pdf->MultiCell(15,5,"Solde : ",0);
	  $pdf->SetFont('Arial','B',10);
	  $pdf->SetXY(115,140 + ($i * 8) );
	  $pdf->MultiCell(16,5,($qtycontrat - $qtysell),0,'R');

	  $i++;
	  $pdf->SetFont('Arial','',10);
	  $pdf->SetXY(10,140 + ($i * 10) );
	  $pdf->MultiCell(50,5,"Taux des droits d'auteurs : ",0);
	  $pdf->SetFont('Arial','B',10);
	  $pdf->SetXY(60,140 + ($i * 10) );
	  $pdf->MultiCell(16,5, $value['taux']." %",0,'R');

	  $i++;
	  $pdf->SetFont('Arial','',10);
	  $pdf->SetXY(10,140 + ($i * 10) );
	  $pdf->MultiCell(50,5,"Prix de vente des livres HT : ",0);
	  $pdf->SetFont('Arial','B',10);
	  $pdf->SetXY(60,140 + ($i * 10) );
	  $pdf->MultiCell(16,5, sprintf("%.2f",$livre->price),0,'R');

	  $i++;
	  $pdf->SetFont('Arial','',10);
	  $pdf->SetXY(10,140 + ($i * 10) );
	  $pdf->MultiCell(50,5,"Prix de vente des livres TTC : ",0);
	  $pdf->SetFont('Arial','B',10);
	  $pdf->SetXY(60,140 + ($i * 10) );
	  $pdf->MultiCell(16,5, sprintf("%.2f",$livre->price_ttc),0,'R');

	  $i++;
	  $pdf->SetFont('Arial','',10);
	  $pdf->SetXY(10,150 + ($i * 10) );
	  $pdf->MultiCell(80,5,"Reste à devoir sur les droits pour l'année ".($year-1)." : ",0);
	  $pdf->SetFont('Arial','B',10);
	  $pdf->SetXY(90,140 + ($i * 10) );
	  $pdf->MultiCell(16,5, $pu_ttc,0,'R');

	  $i++;
	  $pdf->SetFont('Arial','',10);
	  $pdf->SetXY(10,150 + ($i * 10) + 20);
	  $pdf->MultiCell(190,5,"Nous restons à votre entière disposition pour de plus amples renseignements dont vous pouvez avoir besoin et vous remercions de la confiance que vous nous avez accordée.");
	  

	}
		


	return 0;
      }
    else
      {
	$this->error=$this->langs->trans("ErrorCanNotCreateDir",$dir);
	return -6;
      }
  }

  
}

?>
