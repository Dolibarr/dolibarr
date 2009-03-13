<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)       <raphael.bertrand@resultic.fr>
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
 \file       htdocs/includes/modules/fichinter/pdf_soleil.modules.php
 \ingroup    ficheinter
 \brief      Fichier de la classe permettant de generer les fiches d'intervention au modele Soleil
 \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/fichinter/modules_fichinter.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
 \class      pdf_soleil
 \brief      Classe permettant de g�n�rer les fiches d'intervention au mod�le Soleil
 */

class pdf_soleil extends ModelePDFFicheinter
{

	/**
	 \brief      Constructeur
	 \param	    db		Handler acces base de donnee
	 */
	function pdf_soleil($db=0)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = 'soleil';
		$this->description = $langs->trans("DocumentModelStandard");

		// Dimension page pour format A4
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 0;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 0;                 // Affiche mode reglement
		$this->option_condreg = 0;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 0;      // Affiche code produit-service
		$this->option_multilang = 0;               // Dispo en plusieurs langues
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		// Recupere code pays de l'emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->code_pays) $this->emetteur->code_pays=substr($langs->defaultlang,-2);    // By default, if not defined
	}

	/**
	 *	\brief      Fonction g�n�rant la fiche d'intervention sur le disque
	 *	\param	    fichinter		Object fichinter
	 *	\param		outputlangs		Lang output object
	 *	\return	    int     		1=ok, 0=ko
	 */
	function write_file($fichinter,$outputlangs)
	{
		global $user,$langs,$conf,$mysoc;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("interventions");

		$outputlangs->setPhpLang();

		if ($conf->fichinter->dir_output)
		{
			// If $fichinter is id instead of object
			if (! is_object($fichinter))
			{
				$id = $fichinter;
				$fichinter = new Fichinter($this->db);
				$result=$fichinter->fetch($id);
				if ($result < 0)
				{
					dol_print_error($db,$fichinter->error);
				}
			}

			$fichref = sanitizeFileName($fichinter->ref);
			$dir = $conf->fichinter->dir_output;
			if (! eregi('specimen',$fichref)) $dir.= "/" . $fichref;
			$file = $dir . "/" . $fichref . ".pdf";

			if (! file_exists($dir))
			{
				if (create_exdir($dir) < 0)
				{
					$this->error=$outputlangs->trans("ErrorCanNotCreateDir",$dir);
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

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);

				// New page
				$pdf->AddPage();
				$pagenb++;
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','', 9);
				$pdf->MultiCell(0, 4, '', 0, 'J');		// Set interline to 4

				//Affiche le filigrane brouillon - Print Draft Watermark
				if($fichinter->statut==0 && (! empty($conf->global->FICHINTER_DRAFT_WATERMARK)) )
				{
					$watermark_angle=atan($this->page_hauteur/$this->page_largeur);
					$watermark_x=5;
					$watermark_y=$this->page_hauteur-50;
					$watermark_width=$this->page_hauteur;
					$pdf->SetFont('Arial','B',50);
					$pdf->SetTextColor(255,192,203);
					//rotate
					$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$pdf->k,($pdf->h-$watermark_y)*$pdf->k,-$watermark_x*$pdf->k,-($pdf->h-$watermark_y)*$pdf->k));
					//print watermark
					$pdf->SetXY($watermark_x,$watermark_y);
					$pdf->Cell($watermark_width,25,$outputlangs->convToOutputCharset($conf->global->FICHINTER_DRAFT_WATERMARK),0,2,"C",0);
					//antirotate
					$pdf->_out('Q');
				}
				//Print content

				$posy=$this->marge_haute;

				$pdf->SetXY($this->marge_gauche,$posy);

				// Logo
				$logo=$conf->societe->dir_logos.'/'.$mysoc->logo;
				if ($mysoc->logo)
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
						$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
					}
				}

				// Nom emetteur
				$posy=40;
				$hautcadre=40;
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',8);

				$pdf->SetXY($this->marge_gauche,$posy);
				$pdf->SetFillColor(230,230,230);
				$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);


				$pdf->SetXY($this->marge_gauche+2,$posy+3);

				$pdf->SetTextColor(0,0,60);
				$pdf->SetFont('Arial','B',11);
				$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->nom), 0, 'L');

				// Caracteristiques emetteur
				$carac_emetteur = '';
				$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->adresse);
				$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->cp).' '.$outputlangs->convToOutputCharset($this->emetteur->ville);
				$carac_emetteur .= "\n";
				// Tel
				if ($this->emetteur->tel) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($this->emetteur->tel);
				// Fax
				if ($this->emetteur->fax) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($this->emetteur->fax);
				// EMail
				if ($this->emetteur->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($this->emetteur->email);
				// Web
				if ($this->emetteur->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($this->emetteur->url);

				$pdf->SetFont('Arial','',9);
				$pdf->SetXY($this->marge_gauche+2,$posy+9);
				$pdf->MultiCell(80,3, $carac_emetteur);


				/*
				 * Adresse Client
				 */
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','B',12);
				$fichinter->fetch_client();
				$pdf->SetXY(102,42);
				$pdf->MultiCell(86,5, $outputlangs->convToOutputCharset($fichinter->client->nom));
				$pdf->SetFont('Arial','B',11);
				$pdf->SetXY(102,$pdf->GetY());
				$pdf->MultiCell(66,5, $outputlangs->convToOutputCharset($fichinter->client->adresse) . "\n" . $outputlangs->convToOutputCharset($fichinter->client->cp) . " " . $outputlangs->convToOutputCharset($fichinter->client->ville));
				$pdf->rect(100, 40, 100, 40);


				$pdf->SetTextColor(0,0,100);
				$pdf->SetFont('Arial','B',14);
				$pdf->Text(11, 94, $outputlangs->trans("InterventionCard")." : ".$outputlangs->convToOutputCharset($fichinter->ref));

				$pdf->SetFillColor(220,220,220);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',10);

				$tab_top = 100;

				$pdf->SetXY (10, $tab_top);
				$pdf->MultiCell(190,8,$outputlangs->transnoentities("Description"),0,'L',0);
				$pdf->line(10, $tab_top + 8, 200, $tab_top + 8 );

				$pdf->SetFont('Arial','', 9);

				$pdf->MultiCell(0, 4, '', 0, 'J');		// Set interline to 3
				$pdf->SetXY (10, $tab_top + 8 );
				$desc=dol_htmlentitiesbr($fichinter->description,1);
				//print $outputlangs->convToOutputCharset($desc); exit;
				$pdf->writeHTMLCell(180, 3, 10, $tab_top + 8, $outputlangs->convToOutputCharset($desc), 0, 1);
				$nexY = $pdf->GetY();

				$tab_height = 0;
				$tab_top=$nexY;
				$pdf->line(10, $nexY, 200, $nexY);

				$pdf->MultiCell(0, 4, '', 0, 'J');		// Set interline to 4

				//dol_syslog("desc=".dol_htmlentitiesbr($fichinter->description));
				$num = sizeof($fichinter->lignes);
				$i=0;$j=0;
				$height=9;
				if ($num)
				{
					while ($i < $num)
					{
						$fichinterligne = $fichinter->lignes[$i];

						$valide = $fichinterligne->id ? $fichinterligne->fetch($fichinterligne->id) : 0;
						if ($valide>0)
						{
							$pdf->SetXY (10, $tab_top + $j * $height);
							$pdf->writeHTMLCell(0, 4, $this->marge_gauche, $tab_top + $j * $height,
							dol_htmlentitiesbr($outputlangs->transnoentities("Date")." : ".dol_print_date($fichinterligne->datei,'dayhour',false,$outputlangs,true)." - ".$outputlangs->transnoentities("Duration")." : ".ConvertSecondToTime($fichinterligne->duration),1,$outputlangs->charset_output), 0, 1, 0);
							$tab_height+=4;

							$pdf->SetXY (10, $tab_top + 4 + $j * $height);
							$pdf->writeHTMLCell(0, 4, $this->marge_gauche, $tab_top + 4 + $j * $height,
							dol_htmlentitiesbr($outputlangs->convToOutputCharset($fichinterligne->desc),1), 0, 1, 0);
							$tab_height+=dol_nboflines_bis($fichinterligne->desc,52)*4;

							$j++;
						}
						$i++;
					}
				}
				$pdf->line(10, $tab_top+$tab_height, 200, $tab_top+$tab_height);

				// Rectangle for title and all lines
				$pdf->Rect(10, 100, 190, $tab_height+ ($tab_top - 100));
				$pdf->SetXY (10, $pdf->GetY() + 20);
				$pdf->MultiCell(60, 5, '', 0, 'J', 0);

				$pdf->SetXY(20,220);
				$pdf->MultiCell(66,5, $outputlangs->transnoentities("NameAndSignatureOfInternalContact"),0,'L',0);

				$pdf->SetXY(20,225);
				$pdf->MultiCell(80,30, '', 1);

				$pdf->SetXY(110,220);
				$pdf->MultiCell(80,5, $outputlangs->transnoentities("NameAndSignatureOfExternalContact"),0,'L',0);

				$pdf->SetXY(110,225);
				$pdf->MultiCell(80,30, '', 1);

				$pdf->SetFont('Arial','', 9);   // On repositionne la police par defaut

				$this->_pagefoot($pdf,$outputlangs);
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file);
				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				$langs->setPhpLang();	// On restaure langue session
				return 1;
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","FICHEINTER_OUTPUTDIR");
			return 0;
		}
		$this->error=$langs->trans("ErrorUnknown");
		return 0;   // Erreur par defaut
	}

	/*
	 *   \brief      Affiche le pied de page
	 *   \param      pdf     objet PDF
	 */
	function _pagefoot(&$pdf,$outputlangs)
	{
		return pdf_pagefoot($pdf,$outputlangs,'FICHEINTER_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur);
	}

}

?>
