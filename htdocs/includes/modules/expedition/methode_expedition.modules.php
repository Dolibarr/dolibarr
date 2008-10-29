<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *	\file       htdocs/includes/modules/expedition/methode_expedition.modules.php
 *	\ingroup    expedition
 *	\brief      Fichier contenant la classe mere de generation de bon de livraison en PDF
 *				et la classe mere de numerotation des bons de livraisons
 * 	\version	$Id$
 */

require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');


/**
 *	\class      methode_expedition
 *	\brief      Classe mere des methodes expeditions
 */
class methode_expedition
{

	function methode_expedition($db=0)
	{
		$this->db = $db;
		$this->name = "NON DEFINIT";
		$this->description = "ERREUR DANS LA DEFINITION DU MODULE.";
	}


	function Active($statut)
	{
		// Mise a jour du statut
		$sql = "UPDATE ".MAIN_DB_PREFIX."expedition_methode set statut = $statut ";
		$sql.= " WHERE rowid = ".$this->id;

		$resql = $this->db->query($sql);

		if ($resql)
		{
			$af = $this->db->affected_rows($resql);

			if ($af == 0 && $statut == 1)
	  {
	  	// On cre la methode dans la base
	  	 
	  	$sql = "INSERT INTO ".MAIN_DB_PREFIX."expedition_methode";
	  	$sql .= " (rowid, statut, code, libelle, description)";
	  	$sql .= " VALUES (".$this->id.",1,'$this->code','$this->name','$this->description')";
	  	 
	  	$resql = $this->db->query($sql);
	  	 
	  	if (! $resql)
	  	{
	  		dolibarr_syslog("methode_expedition::Active Erreur 2");
	  	}
	  }
		}
		else
		{
			dolibarr_syslog("methode_expedition::Active Erreur 1");
		}
	}

	
	function write_file($id,$outputlangs)
	{
		global $conf, $user;

		$propale = new Propal($this->db,"",$id);
		if ($propale->fetch($id))
		{
			$file = $dir . "/" . $propale->ref . ".pdf";

			if (file_exists($dir))
			{
				// Protection et encryption du pdf
				if ($conf->global->PDF_SECURITY_ENCRYPTION)
				{
					$pdf=new FPDI_Protection('P','mm','A4');
					$pdfrights = array('print'); // Ne permet que l'impression du document
					$pdfuserpass = ''; // Mot de passe pour l'utilisateur final
					$pdfownerpass = NULL; // Mot de passe du propri�taire, cr�� al�atoirement si pas d�fini
					$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
				}
				else
				{
					$pdf=new FPDI('P','mm',$this->format);
				}

				$pdf->Open();
				$pdf->AddPage();

				$pdf->SetTitle($propale->ref);
				$pdf->SetSubject($outputlangs->trans("Proposal"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($user->fullname);

				$this->_pagehead($pdf, $propale);

				$tab_top = 100;
				$tab_height = 150;

				$pdf->SetFillColor(220,220,220);

				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','', 10);

				$pdf->SetXY (10, $tab_top + 10 );

				$iniY = $pdf->GetY();
				$curY = $pdf->GetY();
				$nexY = $pdf->GetY();
				$nblignes = sizeof($propale->lignes);

				for ($i = 0 ; $i < $nblignes ; $i++)
				{

					$curY = $nexY;

					$pdf->SetXY (30, $curY );

					$pdf->MultiCell(100, 5, $propale->lignes[$i]->desc, 0, 'J', 0);

					$nexY = $pdf->GetY();
					 
					$pdf->SetXY (10, $curY );

					$pdf->MultiCell(20, 5, $propale->lignes[$i]->ref, 0, 'C');

					$pdf->SetXY (133, $curY );
					$pdf->MultiCell(10, 5, $propale->lignes[$i]->tva_tx, 0, 'C');

					$pdf->SetXY (145, $curY );
					$pdf->MultiCell(10, 5, $propale->lignes[$i]->qty, 0, 'C');

					$pdf->SetXY (156, $curY );
					$pdf->MultiCell(18, 5, price($propale->lignes[$i]->price), 0, 'R', 0);
					 
					$pdf->SetXY (174, $curY );
					$total = price($propale->lignes[$i]->price * $propale->lignes[$i]->qty);
					$pdf->MultiCell(26, 5, $total, 0, 'R', 0);

					$pdf->line(10, $curY, 200, $curY );

					if ($nexY > 240 && $i < $nblignes - 1)
					{
						$this->_tableau($pdf, $tab_top, $tab_height, $nexY);
						$pdf->AddPage();
						$nexY = $iniY;
						$this->_pagehead($pdf, $propale);
						$pdf->SetTextColor(0,0,0);
						$pdf->SetFont('Arial','', 10);
					}
				}

				$this->_tableau($pdf, $tab_top, $tab_height, $nexY);
				/*
				 *
				 */
				$tab2_top = 254;
				$tab2_lh = 7;
				$tab2_height = $tab2_lh * 3;

				$pdf->SetFont('Arial','', 11);

				$pdf->Rect(132, $tab2_top, 68, $tab2_height);

				$pdf->line(132, $tab2_top + $tab2_height - ($tab2_lh*3), 200, $tab2_top + $tab2_height - ($tab2_lh*3) );
				$pdf->line(132, $tab2_top + $tab2_height - ($tab2_lh*2), 200, $tab2_top + $tab2_height - ($tab2_lh*2) );
				$pdf->line(132, $tab2_top + $tab2_height - $tab2_lh, 200, $tab2_top + $tab2_height - $tab2_lh );

				$pdf->line(174, $tab2_top, 174, $tab2_top + $tab2_height);

				$pdf->SetXY (132, $tab2_top + 0);
				$pdf->MultiCell(42, $tab2_lh, "Total HT", 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + $tab2_lh);
				$pdf->MultiCell(42, $tab2_lh, "Total TVA", 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + ($tab2_lh*2));
				$pdf->MultiCell(42, $tab2_lh, "Total TTC", 1, 'R', 1);

				$pdf->SetXY (174, $tab2_top + 0);
				$pdf->MultiCell(26, $tab2_lh, price($propale->total_ht), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + $tab2_lh);
				$pdf->MultiCell(26, $tab2_lh, price($propale->total_tva), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + ($tab2_lh*2));
				$pdf->MultiCell(26, $tab2_lh, price($propale->total_ttc), 1, 'R', 1);

				/*
				 *
				 */

				$pdf->Output($file);
				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

			}
		}
	}

	function _tableau(&$pdf, $tab_top, $tab_height, $nexY)
	{

		$pdf->SetFont('Arial','',11);

		$pdf->Text(30,$tab_top + 5,'D�signation');

		$pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
		$pdf->Text(134,$tab_top + 5,'TVA');

		$pdf->line(144, $tab_top, 144, $tab_top + $tab_height);
		$pdf->Text(147,$tab_top + 5,'Qt�');

		$pdf->line(156, $tab_top, 156, $tab_top + $tab_height);
		$pdf->Text(160,$tab_top + 5,'P.U.');

		$pdf->line(174, $tab_top, 174, $tab_top + $tab_height);
		$pdf->Text(187,$tab_top + 5,'Total');

		//      $pdf->Rect(10, $tab_top, 190, $nexY - $tab_top);
		$pdf->Rect(10, $tab_top, 190, $tab_height);


		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',10);
		$titre = $langs->trans("AmountInCurrency",$langs->trans("Currency".$conf->monnaie));
		$pdf->Text(200 - $pdf->GetStringWidth($titre), 98, $titre);

	}

	function _pagehead(&$pdf, $propale)
	{
		$pdf->SetXY(10,5);
		if (defined("FAC_PDF_INTITULE"))
		{
			$pdf->SetTextColor(0,0,200);
			$pdf->SetFont('Times','B',14);
			$pdf->MultiCell(76, 8, FAC_PDF_INTITULE, 0, 'L');
		}

		$pdf->SetTextColor(70,70,170);
		if (defined("FAC_PDF_ADRESSE"))
		{
			$pdf->SetFont('Times','',12);
			$pdf->MultiCell(76, 5, FAC_PDF_ADRESSE);
		}
		if (defined("FAC_PDF_TEL"))
		{
			$pdf->SetFont('Times','',10);
			$pdf->MultiCell(76, 5, "T�l : ".FAC_PDF_TEL);
		}
		if (defined("FAC_PDF_SIREN"))
		{
			$pdf->SetFont('Times','',10);
			$pdf->MultiCell(76, 5, "SIREN : ".FAC_PDF_SIREN);
		}

		if (defined("FAC_PDF_INTITULE2"))
		{
			$pdf->SetXY(100,5);
			$pdf->SetFont('Times','B',14);
			$pdf->SetTextColor(0,0,200);
			$pdf->MultiCell(100, 10, FAC_PDF_INTITULE2, '' , 'R');
		}
		/*
		 * Adresse Client
		 */
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Courier','B',12);
		$propale->fetch_client();
		$pdf->SetXY(102,42);
		$pdf->MultiCell(96,5, $propale->client->nom);
		$pdf->SetFont('Courier','B',11);
		$pdf->SetXY(102,47);
		$pdf->MultiCell(96,5, $propale->client->adresse . "\n" . $propale->client->cp . " " . $propale->client->ville);
		$pdf->rect(100, 40, 100, 40);


		$pdf->SetTextColor(200,0,0);
		$pdf->SetFont('Courier','B',12);
		$pdf->Text(11, 88, $outputlangs->trans("Date")." : " . dolibarr_print_date($propale->date,'day',false,$outputlangs));
		$pdf->Text(11, 94, $outputlangs->trans("Proposal")." : ".$propale->ref);
	}

}

?>
