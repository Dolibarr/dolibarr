#!/usr/bin/php
<?PHP
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       scripts/books/droits-editeurs.php
 *  \ingroup    editeurs
 *  \brief      Script de generation des courriers pour les editeurs
 * 	\version	$Id$
 */

require_once("../../htdocs/master.inc.php");
require_once(FPDF_PATH.'fpdf.php');
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/canvas/product.livre.class.php");

$error = 0;
$year = strftime("%Y", time());

//
$sql = "SELECT s.rowid as socid, s.nom";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
//$sql .= " , ".MAIN_DB_PREFIX."categorie_fournisseur as cf";
$sql .= " WHERE s.fournisseur = 1 ";
//$sql .= " AND s.rowid = cf.fk_societe";
//$sql .= " AND cf.fk_categorie = 2";

$resql=$db->query($sql);
if ($resql)
{
  while ($obj = $db->fetch_object($resql) )
    {
      $id       = $obj->socid;

      dol_syslog("droits-editeurs.php id:$id", LOG_DEBUG );

      $coupdf = new pdf_courrier_editeur($db, $langs);
      $coupdf->write($id, $year);
    }
}
else
{
  print $db->error();
  print $sql;
}

class pdf_courrier_editeur
{

  /**
     \brief      Constructeur
     \param	    db		Handler accès base de donnée
  */
  function pdf_courrier_editeur ($db=0, $langs)
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

  }

  /**
     \brief      Fonction générant la fiche d'intervention sur le disque
     \param	    id		id de la fiche intervention à générer
     \return	    int     1=ok, 0=ko
  */
  function write($id, $year)
  {
    $soc = new Societe($this->db);
    $soc->fetch($id);

    $fichref = $year;

    $dir = DOL_DATA_ROOT."/societe/courrier/" . get_exdir($id);
    $file = $dir . $fichref . ".pdf";

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
	$pdf=new FPDF('P','mm',$this->format);
	$pdf->Open();

	$books = array();
	$year_data = $year - 1;

	// On récupère données du mail
	$sql = "SELECT p.rowid, p.label, pc.taux, pc.quantite";
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
		$id       = $obj->socid;
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

	$pdf->Close();

	$pdf->Output($file);
	dol_syslog("droits-editeurs.php write $file", LOG_DEBUG );
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
