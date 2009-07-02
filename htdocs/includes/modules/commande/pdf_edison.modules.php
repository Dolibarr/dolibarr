<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
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
 \file       htdocs/includes/modules/commande/pdf_edison.modules.php
 \ingroup    commande
 \brief      Fichier de la classe permettant de generer les commandes au modele Edison
 \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/commande/modules_commande.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

/**
 \class      pdf_edison
 \brief      Classe permettant de g�n�rer les commandes au mod�le Edison
 */

class pdf_edison extends ModelePDFCommandes
{
	var $emetteur;	// Objet societe qui emet

	/**
	 * 	\brief      Constructeur
	 *	\param	    db	    handler acc�s base de donn�e
	 */
	function pdf_edison($db=0)
	{
        global $conf,$langs,$mysoc;

        $langs->load("main");
        $langs->load("bills");

        $this->db = $db;
		$this->name = "edison";
		$this->description = "Modele de commande simple";

		// Dimension page pour format A4
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		$this->option_multilang = 0;               // Dispo en plusieurs langues
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'�tait pas d�fini

		// Defini position des colonnes
		$this->posxdesc=$this->marge_gauche+1;
		$this->posxtva=113;
		$this->posxup=126;
		$this->posxqty=145;
		$this->posxdiscount=162;
		$this->postotalht=174;

		$this->tva=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}


	/**	\brief      Renvoi derni�re erreur
	 \return     string      Derni�re erreur
	 */
	function pdferror()
	{
		return $this->error;
	}


	/**
	 *	\brief      Fonction generant la commande sur le disque
	 *	\param	    com				id de la propale a generer
	 *	\param		outputlangs		Lang output object
	 *	\return	    int     		1=ok, 0=ko
	 */
	function write_file($com,$outputlangs)
	{
		global $user,$conf,$langs,$mysoc;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
        $outputlangs->load("orders");

		// Definition de l'objet $com (pour compatibilite ascendante)
		if (! is_object($com))
		{
			$id = $com;
			$com = new Commande($this->db,"",$id);
			$ret=$com->fetch($id);
		}

		if ($conf->commande->dir_output)
		{
			// Definition of $dir and $file
			if ($com->specimen)
			{
				$dir = $conf->commande->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$comref = dol_sanitizeFileName($com->ref);
				$dir = $conf->commande->dir_output . "/" . $comref;
				$file = $dir . "/" . $comref . ".pdf";
			}

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
				// Protection et encryption du pdf
				if ($conf->global->PDF_SECURITY_ENCRYPTION)
				{
					$pdf=new FPDI_Protection('P','mm',$this->format);
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
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($com->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Order"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($com->ref)." ".$outputlangs->transnoentities("Order"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $com, 1, $outputlangs);
				$pdf->SetFont('Arial','', 9);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);


				$tab_top = 100;
				$tab_height = 140;

				$pdf->SetFillColor(220,220,220);

				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','', 9);

				$pdf->SetXY (10, $tab_top + 10 );

				$iniY = $pdf->GetY();
				$curY = $pdf->GetY();
				$nexY = $pdf->GetY();
				$nblignes = sizeof($com->lignes);

				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY;

					$pdf->writeHTMLCell(100, 3, 30, $curY, $outputlangs->convToOutputCharset($com->lignes[$i]->desc), 0, 1);

					$nexY = $pdf->GetY();

					$pdf->SetXY (10, $curY);
					$pdf->MultiCell(20, 3, $outputlangs->convToOutputCharset($com->lignes[$i]->ref), 0, 'C');

					$pdf->SetXY (133, $curY);
					$pdf->MultiCell(10, 3, vatrate($com->lignes[$i]->tva_tx), 0, 'C');

					$pdf->SetXY (145, $curY);
					$pdf->MultiCell(10, 3, price2num($com->lignes[$i]->qty), 0, 'C');

					$pdf->SetXY (156, $curY);
					$pdf->MultiCell(18, 3, price($com->lignes[$i]->price), 0, 'R', 0);

					$pdf->SetXY (174, $curY);
					$total = price($com->lignes[$i]->total_ht);
					$pdf->MultiCell(26, 3, $total, 0, 'R', 0);

					$nexY+=2;    // Passe espace entre les lignes

					// cherche nombre de lignes a venir pour savoir si place suffisante
					if ($i < ($nblignes - 1))	// If it's not last line
					{
						//on recupere la description du produit suivant
						$follow_descproduitservice = $outputlangs->convToOutputCharset($com->lignes[$i+1]->desc);
						//on compte le nombre de ligne afin de verifier la place disponible (largeur de ligne 52 caracteres)
						$nblineFollowDesc = (dol_nboflines_bis($follow_descproduitservice,52)*4);
					}
					else	// If it's last line
					{
						$nblineFollowDesc = 0;
					}

					if (($nexY+$nblineFollowDesc) > ($tab_top+$tab_height) && $i < ($nblignes - 1))
					{
						$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);

						// New page
						$pdf->AddPage();
						$pagenb++;
						$this->_pagehead($pdf, $com, 0, $outputlangs);
						$pdf->SetFont('Arial','', 9);
						$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
						$pdf->SetTextColor(0,0,0);

						$nexY = $tab_top + 8;
					}
				}

				$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);

				$tab2_top = 241;
				$tab2_lh = 7;
				$tab2_height = $tab2_lh * 4;

				$pdf->SetFont('Arial','', 11);

				$pdf->Rect(132, $tab2_top, 68, $tab2_height);

				$pdf->line(132, $tab2_top + $tab2_height - ($tab2_lh*3), 200, $tab2_top + $tab2_height - ($tab2_lh*3) );
				$pdf->line(132, $tab2_top + $tab2_height - ($tab2_lh*2), 200, $tab2_top + $tab2_height - ($tab2_lh*2) );
				$pdf->line(132, $tab2_top + $tab2_height - $tab2_lh, 200, $tab2_top + $tab2_height - $tab2_lh );

				$pdf->line(174, $tab2_top, 174, $tab2_top + $tab2_height);

				$pdf->SetXY (132, $tab2_top + 0);
				$pdf->MultiCell(42, $tab2_lh, $langs->transnoentities("TotalHT"), 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + $tab2_lh);
				$pdf->MultiCell(42, $tab2_lh, $langs->transnoentities("TotalVAT"), 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + ($tab2_lh*2));
				$pdf->MultiCell(42, $tab2_lh, $langs->transnoentities("TotalTTC"), 1, 'R', 1);

				$pdf->SetXY (174, $tab2_top + 0);
				$pdf->MultiCell(26, $tab2_lh, price($com->total_ht), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + $tab2_lh);
				$pdf->MultiCell(26, $tab2_lh, price($com->total_tva), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + ($tab2_lh*2));
				$pdf->MultiCell(26, $tab2_lh, price($com->total_ttc), 1, 'R', 1);

				// Pied de page
				$this->_pagefoot($pdf,$com,$outputlangs);
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file);
				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;
			}
		}
		else
		{
			$this->error=$langs->transnoentities("ErrorConstantNotDefined","COMMANDE_OUTPUTDIR");
			return 0;
		}

		$this->error=$langs->transnoentities("ErrorUnknown");
		return 0;   // Erreur par defaut
	}

	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		global $langs,$conf;
		$langs->load("main");
		$langs->load("bills");

		$pdf->SetFont('Arial','',11);

		$pdf->Text(30,$tab_top + 5,$outputlangs->transnoentities("Designation"));

		$pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
		$pdf->Text(134,$tab_top + 5,$outputlangs->transnoentities("VAT"));

		$pdf->line(144, $tab_top, 144, $tab_top + $tab_height);
		$pdf->Text(147,$tab_top + 5,$outputlangs->transnoentities("Qty"));

		$pdf->line(156, $tab_top, 156, $tab_top + $tab_height);
		$pdf->Text(160,$tab_top + 5,$outputlangs->transnoentities("PriceU"));

		$pdf->line(174, $tab_top, 174, $tab_top + $tab_height);
		$pdf->Text(187,$tab_top + 5,$outputlangs->transnoentities("Total"));

		//      $pdf->Rect(10, $tab_top, 190, $nexY - $tab_top);
		$pdf->Rect(10, $tab_top, 190, $tab_height);

		$pdf->line(10, $tab_top + 8, 200, $tab_top + 8);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',10);
		$titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$conf->monnaie));
		$pdf->Text(200 - $pdf->GetStringWidth($titre), 98, $titre);
	}


	function _pagehead(&$pdf, $object, $showaddress=1, $outputlangs)
	{
		global $conf,$langs,$mysoc;
		$langs->load("orders");

		pdf_pagehead($pdf,$outputlangs,$pdf->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && (! empty($conf->global->COMMANDE_DRAFT_WATERMARK)) )
		{
			$watermark_angle=atan($this->page_hauteur/$this->page_largeur);
			$watermark_x=5;
			$watermark_y=$this->page_hauteur-25; //Set to $this->page_hauteur-50 or less if problems
			$watermark_width=$this->page_hauteur;
			$pdf->SetFont('Arial','B',50);
			$pdf->SetTextColor(255,192,203);
			//rotate
			$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$pdf->k,($pdf->h-$watermark_y)*$pdf->k,-$watermark_x*$pdf->k,-($pdf->h-$watermark_y)*$pdf->k));
			//print watermark
			$pdf->SetXY($watermark_x,$watermark_y);
			$pdf->Cell($watermark_width,25,$outputlangs->convToOutputCharset($conf->global->COMMANDE_DRAFT_WATERMARK),0,2,"C",0);
			//antirotate
			$pdf->_out('Q');
		}


		$posy=$this->marge_haute;
		$pdf->SetXY($this->marge_gauche,$posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, 24);
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('Arial','B',8);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->nom;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		$pdf->SetFont('Arial','B',13);
		$pdf->SetXY(100,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('Arial','B',12);
		$posy+=20;

		// Sender properties
		$carac_emetteur = '';
		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->adresse);
		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->cp).' '.$outputlangs->convToOutputCharset($this->emetteur->ville);
		$carac_emetteur .= "\n";
		// Tel
		if ($this->emetteur->tel) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($this->emetteur->tel);
		// Fax
		if ($this->emetteur->fax) $carac_emetteur .= ($carac_emetteur ? ($this->emetteur->tel ? " - " : "\n") : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($this->emetteur->fax);
		// EMail
		if ($this->emetteur->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($this->emetteur->email);
		// Web
		if ($this->emetteur->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($this->emetteur->url);

		$pdf->SetFont('Arial','',9);
		$pdf->SetXY(10,$posy);
		$pdf->MultiCell(80, 4, $carac_emetteur);

		// Client destinataire
		$client = new Societe($this->db);
		$client->fetch($object->socid);
		$object->client = $client;

		// If CUSTOMER contact defined on invoice, we use it
		$usecontact=false;
		//if ($conf->global->COMMANDE_USE_CUSTOMER_CONTACT_AS_RECIPIENT)
		//{
			$arrayidcontact=$object->getIdContact('external','CUSTOMER');
			if (sizeof($arrayidcontact) > 0)
			{
				$usecontact=true;
				$result=$object->fetch_contact($arrayidcontact[0]);
			}
		//}

		if ($usecontact)
		{
			// Nom societe
			$carac_client_name=$outputlangs->convToOutputCharset($object->client->nom);

			// Customer name
			$carac_client = "\n".$object->contact->getFullName($outputlangs,1,1);

			// Customer properties
			$carac_client.="\n".$outputlangs->convToOutputCharset($object->contact->address);
			$carac_client.="\n".$outputlangs->convToOutputCharset($object->contact->cp) . " " . $outputlangs->convToOutputCharset($object->contact->ville)."\n";
			if ($object->contact->pays_code != $this->emetteur->pays_code) $carac_client.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$object->contact->pays_code))."\n";
		}
		else
		{
			// Customer name
			$carac_client_name=$outputlangs->convToOutputCharset($object->client->nom);

			// Nom du contact suivi commande si c'est une soci�t�
			$arrayidcontact = $object->getIdContact('external','CUSTOMER');
			if (sizeof($arrayidcontact) > 0)
			{
				$object->fetch_contact($arrayidcontact[0]);
				// On verifie si c'est une societe ou un particulier
				if( !preg_match('#'.$object->contact->getFullName($outputlangs,1).'#isU',$object->client->nom) )
				{
					$carac_client .= "\n".$object->contact->getFullName($outputlangs,1,1);
				}
			}

			// Caracteristiques client
			$carac_client.="\n".$outputlangs->convToOutputCharset($object->client->adresse);
			$carac_client.="\n".$outputlangs->convToOutputCharset($object->client->cp) . " " . $outputlangs->convToOutputCharset($object->client->ville)."\n";
			if ($object->client->pays_code != $this->emetteur->pays_code) $carac_client.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$object->client->pays_code))."\n";
		}
		// Numero TVA intracom
		if ($object->client->tva_intra) $carac_client.="\n".$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($object->client->tva_intra);

		// Show customer/recipient
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','B',12);
		$pdf->SetXY(102,42);
		$pdf->MultiCell(96, 5, $carac_client_name);
		$pdf->SetFont('Arial','',11);
		$pdf->SetXY(102,$pdf->GetY());
		$pdf->MultiCell(96, 5, $carac_client);

		$pdf->rect(100, 40, 100, 40);

		$pdf->SetTextColor(200,0,0);
		$pdf->SetFont('Arial','B',12);
		$pdf->Text(11, 88, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date,'day',false,$outputlangs));
		$pdf->Text(11, 94, $outputlangs->transnoentities("Order")." ".$outputlangs->convToOutputCharset($object->ref));
	}

    /*
     *   \brief      Affiche le pied de page
     *   \param      pdf     objet PDF
     */
    function _pagefoot(&$pdf,$object,$outputlangs)
    {
		return pdf_pagefoot($pdf,$outputlangs,'COMMANDE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur);
    }
}

?>
