<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
        \file       htdocs/includes/modules/fichinter/pdf_soleil.modules.php
        \ingroup    ficheinter
        \brief      Fichier de la classe permettant de générer les fiches d'intervention au modèle Soleil
        \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/fichinter/modules_fichinter.php");


/**
        \class      pdf_soleil
        \brief      Classe permettant de générer les fiches d'intervention au modèle Soleil
*/

class pdf_soleil extends ModelePDFFicheinter
{

    /**
    		\brief      Constructeur
            \param	    db		Handler accès base de donnée
    */
    function pdf_soleil($db=0)
    {
		global $conf,$langs,$mysoc;

        $this->db = $db;
        $this->name = 'soleil';
        $this->description = "Modèle de fiche d'intervention standard";

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur,$this->page_hauteur);
        $this->marge_gauche=10;
        $this->marge_droite=10;
        $this->marge_haute=10;
        $this->marge_basse=10;
        
        // Recupere code pays de l'emmetteur
        $this->emetteur=$mysoc;
        if (! $this->emetteur->code_pays) $this->emetteur->code_pays=substr($langs->defaultlang,-2);    // Par defaut, si n'était pas défini
    }

    /**
            \brief      Fonction générant la fiche d'intervention sur le disque
            \param	    id		id de la fiche intervention à générer
            \return	    int     1=ok, 0=ko
    */
    function write_pdf_file($id)
    {
        global $user,$langs,$conf,$mysoc;

        if ($conf->fichinter->dir_output)
        {
	        $fich = new Fichinter($this->db,"",$id);
	        $result=$fich->fetch($id);
        	if ($result < 0)
        	{
        		dolibarr_print_error($db,$fich->error);
        	}
        
            $fichref = sanitize_string($fich->ref);
            $dir = $conf->fichinter->dir_output . "/" . $fichref;
            $file = $dir . "/" . $fichref . ".pdf";

            if (! file_exists($dir))
            {
                if (create_exdir($dir) < 0)
                {
                    $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                    return 0;
                }
            }

            if (file_exists($dir))
            {
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
                $pdf->AddPage();

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
		                $pdf->MultiCell(100, 3, $langs->trans("ErrorLogoFileNotFound",$logo), 0, 'L');
		                $pdf->MultiCell(100, 3, $langs->trans("ErrorGoToModuleSetup"), 0, 'L');
		            }
		        }

                $pdf->SetTextColor(70,70,170);
                if (defined("FAC_PDF_ADRESSE"))
                {
                    $pdf->SetFont('Arial','',12);
                    $pdf->MultiCell(40, 5, FAC_PDF_ADRESSE);
                }
                if (defined("FAC_PDF_TEL"))
                {
                    $pdf->SetFont('Arial','',10);
                    $pdf->MultiCell(40, 5, "Tél : ".FAC_PDF_TEL);
                }
                if (defined("MAIN_INFO_SIREN"))
                {
                    $pdf->SetFont('Arial','',10);
                    $pdf->MultiCell(40, 5, "SIREN : ".MAIN_INFO_SIREN);
                }

                if (defined("FAC_PDF_INTITULE2"))
                {
                    $pdf->SetXY(100,5);
                    $pdf->SetFont('Arial','B',14);
                    $pdf->SetTextColor(0,0,200);
                    $pdf->MultiCell(100, 10, FAC_PDF_INTITULE2, '' , 'R');
                }
                /*
                 * Adresse Client
                 */
                $pdf->SetTextColor(0,0,0);
                $pdf->SetFont('Arial','B',12);
                $fich->fetch_client();
                $pdf->SetXY(102,42);
                $pdf->MultiCell(66,5, $fich->client->nom);
                $pdf->SetFont('Arial','B',11);
                $pdf->SetXY(102,47);
                $pdf->MultiCell(66,5, $fich->client->adresse . "\n" . $fich->client->cp . " " . $fich->client->ville);
                $pdf->rect(100, 40, 100, 40);


                $pdf->SetTextColor(200,0,0);
                $pdf->SetFont('Arial','B',14);
                $pdf->Text(11, 88, "Date : " . strftime("%d %b %Y", $fich->date));
                $pdf->Text(11, 94, $langs->trans("InterventionCard")." : ".$fich->ref);

                $pdf->SetFillColor(220,220,220);
                $pdf->SetTextColor(0,0,0);
                $pdf->SetFont('Arial','',12);

                $tab_top = 100;
                $tab_height = 110;

                $pdf->SetXY (10, $tab_top);
                $pdf->MultiCell(190,8,$langs->trans("Description"),0,'L',0);
                $pdf->line(10, $tab_top + 8, 200, $tab_top + 8 );

                $pdf->Rect(10, $tab_top, 190, $tab_height);

                $pdf->SetFont('Arial','', 10);

                $pdf->SetXY (10, $tab_top + 8 );
                $pdf->MultiCell(190, 5, $fich->note, 0, 'J', 0);

                $pdf->Close();

                $pdf->Output($file);

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
}

?>
