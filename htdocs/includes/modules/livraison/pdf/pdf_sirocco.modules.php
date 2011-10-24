<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/includes/modules/livraison/pdf/pdf_sirocco.modules.php
 *	\ingroup    livraison
 *	\brief      File of class to manage receving receipts with template Sirocco
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/livraison/modules_livraison.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');


/**
 *	\class      pdf_sirocco
 *	\brief      Classe permettant de generer les bons de livraison au modele Sirocco
 */
class pdf_sirocco extends ModelePDFDeliveryOrder
{

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$DB      Database handler
	 */
	function pdf_sirocco($db)
	{
		global $conf,$langs,$mysoc;

        $langs->load("main");
        $langs->load("bills");
		$langs->load("sendings");
		$langs->load("companies");

        $this->db = $db;
		$this->name = "sirocco";
		$this->description = $langs->trans("DocumentModelSirocco");

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		// Recupere emmetteur
        $this->emetteur=$mysoc;
        if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'�tait pas d�fini

		$this->tva=array();
	}


	/**
	 *	Fonction generant le bon de livraison sur le disque
	 *	@param	    object   		Object livraison a generer
	 *	@param		outputlangs		Lang output object
	 *	@return	    int         	1 if OK, <=0 if KO
	 */
	function write_file($object,$outputlangs)
	{
		global $user,$conf,$langs;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("deliveries");
		$outputlangs->load("sendings");

		if ($conf->expedition->dir_output."/receipt")
		{
			$object->fetch_thirdparty();

			$nblines = count($object->lines);

			$objectref = dol_sanitizeFileName($object->ref);
			$dir = $conf->expedition->dir_output."/receipt";
			if (! preg_match('/specimen/i',$objectref)) $dir.= "/" . $objectref;
			$file = $dir . "/" . $objectref . ".pdf";

			if (! file_exists($dir))
			{
				if (create_exdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
                $pdf=pdf_getInstance($this->format);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));

				// Complete object by loading several other informations
				$expedition=new Expedition($this->db);
				$result = $expedition->fetch($object->expedition_id);

				$commande = new Commande($this->db);
				if ($expedition->origin == 'commande')
				{
					$commande->fetch($expedition->origin_id);
				}
				$object->commande=$commande;


				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("DeliveryOrder"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("DeliveryOrder"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				$tab_top = 100;
				$tab_top_newpage = 50;
				$tab_height = 140;
				$tab_height_newpage = 190;

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				for ($i = 0 ; $i < $nblines ; $i++)
				{
					$curY = $nexY;

					$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page

                    // Description de la ligne produit
					//$libelleproduitservice=pdf_getlinedesc($object,$i,$outputlangs);
					pdf_writelinedesc($pdf,$object,$i,$outputlangs,100,3,30,$curY,1);
					//$pdf->writeHTMLCell(100, 3, 30, $curY, $outputlangs->convToOutputCharset($libelleproduitservice), 0, 1);

					$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page
					$nexY = $pdf->GetY();

					$pdf->SetXY(10, $curY );

					$pdf->MultiCell(20, 3, $outputlangs->convToOutputCharset($object->lines[$i]->ref), 0, 'C');

					// TODO Field not yet saved in database
					//$pdf->SetXY(133, $curY );
					//$pdf->MultiCell(10, 5, $object->lines[$i]->tva_tx, 0, 'C');

					$pdf->SetXY(145, $curY );
					$pdf->MultiCell(10, 3, $object->lines[$i]->qty_shipped, 0, 'C');

					// TODO Field not yet saved in database
					//$pdf->SetXY(156, $curY );
					//$pdf->MultiCell(20, 3, price($object->lines[$i]->price), 0, 'R', 0);

					// TODO Field not yet saved in database
					//$pdf->SetXY(174, $curY );
					//$total = price($object->lines[$i]->price * $object->lines[$i]->qty_shipped);
					//$pdf->MultiCell(26, 3, $total, 0, 'R', 0);

					$pdf->line(10, $curY-1, 200, $curY-1);


					$nexY+=2;    // Passe espace entre les lignes

					// Cherche nombre de lignes a venir pour savoir si place suffisante
					if ($i < ($nblines - 1) && empty($hidedesc))	// If it's not last line
					{
						//on recupere la description du produit suivant
						$follow_descproduitservice = $object->lines[$i+1]->desc;
						//on compte le nombre de ligne afin de verifier la place disponible (largeur de ligne 52 caracteres)
						$nblineFollowDesc = (dol_nboflines_bis($follow_descproduitservice,52,$outputlangs->charset_output)*4);
					}
					else	// If it's last line
					{
						$nblineFollowDesc = 0;
					}

					// Test if a new page is required
					if ($pagenb == 1)
					{
						$tab_top_in_current_page=$tab_top;
						$tab_height_in_current_page=$tab_height;
					}
					else
					{
						$tab_top_in_current_page=$tab_top_newpage;
						$tab_height_in_current_page=$tab_height_newpage;
					}

					if (($nexY+$nblineFollowDesc) > ($tab_top_in_current_page+$tab_height_in_current_page) && $i < ($nblignes - 1))
					{
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $tab_height + 20, $nexY, $outputlangs);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $tab_height_newpage, $nexY, $outputlangs);
						}

						$this->_pagefoot($pdf, $object, $outputlangs);

						// New page
						$pdf->AddPage();
						$pagenb++;
						$this->_pagehead($pdf, $object, 0, $outputlangs);
						$pdf->SetFont('','', $default_font_size - 1);
						$pdf->MultiCell(0, 3, '');		// Set interline to 3
						$pdf->SetTextColor(0,0,0);

						$nexY = $tab_top_newpage + 7;
					}
				}

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
					$bottomlasttab=$tab_top + $tab_height + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $tab_height_newpage, $nexY, $outputlangs);
					$bottomlasttab=$tab_top_newpage + $tab_height_newpage + 1;
				}

				/*
				 * Pied de page
				 */
				$this->_pagefoot($pdf, $object, $outputlangs);
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');
				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;
			}
		}
		else
		{
			$this->error=$langs->transnoentities("ErrorConstantNotDefined","LIVRAISON_OUTPUTDIR");
			return 0;
		}
	}

	/**
	 *   \brief      Affiche la grille des lignes
	 *   \param      pdf     objet PDF
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$pdf->SetFont('','', $default_font_size - 1);

		$pdf->SetXY(30, $tab_top+1);
		$pdf->MultiCell(60, 2, $outputlangs->transnoentities("Designation"), 0, 'L');

		$pdf->line(144, $tab_top, 144, $tab_top + $tab_height);
        $pdf->SetXY(147, $tab_top+1);
		$pdf->MultiCell(30, 2, $outputlangs->transnoentities("QtyShipped"), 0, 'L');

		$pdf->Rect(10, $tab_top, 190, $tab_height);
	}

	/**
	 *   	Show header of page
	 *
	 *   	@param      $pdf     		Object PDF
	 *   	@param      $object     	Object delivery
	 *      @param      $showaddress    0=no, 1=yes
	 *      @param      $outputlangs	Object lang for output
	 */
	function _pagehead(&$pdf, $object, $showaddress=1, $outputlangs)
	{
		global $langs,$conf,$mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$outputlangs->load("companies");

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

        $posx=$this->page_largeur-$this->marge_droite-100;
		$posy=$this->marge_haute;

		$pdf->SetXY($this->marge_gauche,$posy);

		if ($conf->global->MAIN_INFO_SOCIETE_NOM)
		{
			$pdf->SetTextColor(0,0,200);
			$pdf->SetFont('','B', $default_font_size + 2);
			$pdf->MultiCell(76, 4, $outputlangs->convToOutputCharset(MAIN_INFO_SOCIETE_NOM), 0, 'L');
		}

		// Sender properties
		$carac_emetteur = pdf_build_address($outputlangs,$this->emetteur);

		$pdf->SetFont('','', $default_font_size - 1);
		$pdf->SetXY($this->marge_gauche,$posy+4);
		$pdf->MultiCell(80, 3, $carac_emetteur, 0, 'L');


		/*
		 * Adresse Client
		 */

		// If SHIPPING contact defined on invoice, we use it
		$usecontact=false;
		$arrayidcontact=$object->commande->getIdContact('external','SHIPPING');
		if (count($arrayidcontact) > 0)
		{
			$usecontact=true;
			$result=$object->fetch_contact($arrayidcontact[0]);
		}

		// Recipient name
		if (! empty($usecontact))
		{
			// On peut utiliser le nom de la societe du contact
			if ($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) $socname = $object->contact->socname;
			else $socname = $object->client->nom;
			$carac_client_name=$outputlangs->convToOutputCharset($socname);
		}
		else
		{
			$carac_client_name=$outputlangs->convToOutputCharset($object->client->nom);
		}

		$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->client,$object->contact,$usecontact,'target');

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','B', $default_font_size + 1);

		$pdf->SetXY(102,42);
		$pdf->MultiCell(96,5, $carac_client_name, 0, 'L');
		$pdf->SetFont('','B', $default_font_size);
		$pdf->SetXY(102,47);
		$pdf->MultiCell(96,5, $carac_client, 0, 'L');
		$pdf->rect(100, 40, 100, 40);


		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 1);
        $pdf->SetXY($posx, 86);
		$pdf->MultiCell(100, 2, $outputlangs->transnoentities("Date")." : " . dol_print_date(($object->date_delivery?$object->date_delivery:$date->valid),"day",false,$outputlangs,true), 0, 'R');
        $pdf->SetXY($posx, 92);
		$pdf->MultiCell(100, 2, $outputlangs->transnoentities("DeliveryOrder")." ".$outputlangs->convToOutputCharset($object->ref), 0, 'R');

		if ($object->client->code_client)
		{
			$posy+=7;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->client->code_client), '', 'R');
		}

		$pdf->SetFont('','B', $default_font_size - 1);

		// Add origin linked objects
		// TODO extend to other objects
	    $object->fetchObjectLinked('','',$object->id,'delivery');

	    if (! empty($object->linkedObjects))
		{
			$outputlangs->load('orders');

			foreach($object->linkedObjects as $elementtype => $objects)
			{
				$object->fetchObjectLinked('','',$objects[0]->id,$objects[0]->element);

				foreach($object->linkedObjects as $elementtype => $objects)
				{
					$num=count($objects);
					for ($i=0;$i<$num;$i++)
					{
						$order=new Commande($this->db);
						$result=$order->fetch($objects[$i]->id);
						if ($result >= 0)
						{
							$posy+=5;
							$pdf->SetXY($posx,$posy);
							$pdf->SetFont('','', $default_font_size - 1);
							$text=$order->ref;
							if ($order->ref_client) $text.=' ('.$order->ref_client.')';
							$pdf->MultiCell(100, 4, $outputlangs->transnoentities("RefOrder")." : ".$outputlangs->transnoentities($text), '', 'R');
						}
					}
				}
			}
		}

	}

	/**
	 *   	\brief      Show footer of page
	 *   	\param      pdf     		PDF factory
	 * 		\param		object			Object invoice
	 *      \param      outputlangs		Object lang for output
	 * 		\remarks	Need this->emetteur object
	 */
	function _pagefoot(&$pdf,$object,$outputlangs)
	{
		return pdf_pagefoot($pdf,$outputlangs,'DELIVERY_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object);
	}
}

?>
