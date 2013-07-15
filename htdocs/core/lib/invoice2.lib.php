<?php
/*
 * Copyright (C) 2009-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
*/

/**
 *      \file       htdocs/core/lib/invoice2.lib.php
 *      \ingroup    facture
 *      \brief      Function to rebuild PDF and merge PDF files into one
 */

require_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');


/**
 * Function to build a compiled PDF
 *
 * @param	DoliDB		$db						Database handler
 * @param	Translate	$langs					Object langs
 * @param	Conf		$conf					Object conf
 * @param	string		$diroutputpdf			Dir to output file
 * @param	string		$newlangid				Lang id
 * @param 	array		$filter					Array with filters
 * @param 	date		$dateafterdate			Invoice after date
 * @param 	date 		$datebeforedate			Invoice before date
 * @param 	date		$paymentdateafter		Payment after date (must includes hour)
 * @param 	date		$paymentdatebefore		Payment before date (must includes hour)
 * @param	int			$usestdout				Add information onto standard output
 * @param	int			$regenerate				''=Use existing PDF files, 'nameofpdf'=Regenerate all PDF files using the template
 * @param	string		$option					Suffix to add into file name of generated PDF
 * @return	int									Error code
 */
function rebuild_merge_pdf($db, $langs, $conf, $diroutputpdf, $newlangid, $filter, $dateafterdate, $datebeforedate, $paymentdateafter, $paymentdatebefore, $usestdout, $regenerate=0, $option='')
{
	$sql = "SELECT DISTINCT f.rowid, f.facnumber";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sqlwhere='';
	$sqlorder='';
	if (in_array('all',$filter))
	{
		$sqlorder = " ORDER BY f.facnumber ASC";
	}
	if (in_array('date',$filter))
	{
		if (empty($sqlwhere)) $sqlwhere=' WHERE ';
		else $sqlwhere.=" AND";
		$sqlwhere.= " f.fk_statut > 0";
		$sqlwhere.= " AND f.datef >= '".$db->idate($dateafterdate)."'";
		$sqlwhere.= " AND f.datef <= '".$db->idate($datebeforedate)."'";
		$sqlorder = " ORDER BY f.datef ASC";
	}
	if (in_array('nopayment',$filter))
	{
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid = pf.fk_facture";
		if (empty($sqlwhere)) $sqlwhere=' WHERE ';
		else $sqlwhere.=" AND";
		$sqlwhere.= " f.fk_statut > 0";
		$sqlwhere.= " AND pf.fk_paiement IS NULL";
	}
	if (in_array('payments',$filter))
	{
		$sql.= ", ".MAIN_DB_PREFIX."paiement_facture as pf,";
		$sql.= " ".MAIN_DB_PREFIX."paiement as p";
		if (empty($sqlwhere)) $sqlwhere=' WHERE ';
		else $sqlwhere.=" AND";
		$sqlwhere.= " f.fk_statut > 0";
		$sqlwhere.= " AND f.rowid = pf.fk_facture";
		$sqlwhere.= " AND pf.fk_paiement = p.rowid";
		$sqlwhere.= " AND p.datep >= '".$db->idate($paymentdateafter)."'";
		$sqlwhere.= " AND p.datep <= '".$db->idate($paymentdatebefore)."'";
		$sqlorder = " ORDER BY p.datep ASC";
	}
	if (in_array('nodeposit',$filter))
	{
	    if (empty($sqlwhere)) $sqlwhere=' WHERE ';
	    else $sqlwhere.=" AND";
	    $sqlwhere.=' type <> 3';
	}
	if (in_array('noreplacement',$filter))
	{
	    if (empty($sqlwhere)) $sqlwhere=' WHERE ';
	    else $sqlwhere.=" AND";
	    $sqlwhere.=' type <> 1';
	}
	if (in_array('nocreditnote',$filter))
	{
	    if (empty($sqlwhere)) $sqlwhere=' WHERE ';
	    else $sqlwhere.=" AND";
	    $sqlwhere.=' type <> 2';
	}
	if ($sqlwhere) $sql.=$sqlwhere;
	if ($sqlorder) $sql.=$sqlorder;

	//print $sql; exit;
	dol_syslog("scripts/invoices/rebuild_merge.php: sql=".$sql);

	if ($usestdout) print '--- start'."\n";

	// Start of transaction
	//$db->begin();

	$error = 0;
	$result = 0;
	$files = array() ;		// liste les fichiers

	dol_syslog("scripts/invoices/rebuild_merge.php sql=".$sql);
	if ( $resql=$db->query($sql) )
	{
	    $num = $db->num_rows($resql);
	    $cpt = 0;
	    $oldemail = '';
	    $message = '';
	    $total = '';

	    if ($num)
	    {
	    	// First loop on each resultset to build PDF
	    	// -----------------------------------------

	        while ($cpt < $num)
	        {
	            $obj = $db->fetch_object($resql);

				$fac = new Facture($db);
				$result=$fac->fetch($obj->rowid);
				if ($result > 0)
				{
					$outputlangs = $langs;
					if (! empty($newlangid))
					{
						if ($outputlangs->defaultlang != $newlangid)
						{
							$outputlangs = new Translate("",$conf);
							$outputlangs->setDefaultLang($newlangid);
						}
					}
					$filename=$conf->facture->dir_output.'/'.$fac->ref.'/'.$fac->ref.'.pdf';
					if ($regenerate || ! dol_is_file($filename))
					{
	            	    if ($usestdout) print "Build PDF for invoice ".$obj->facnumber." - Lang = ".$outputlangs->defaultlang."\n";
	    				$result=facture_pdf_create($db, $fac, $regenerate?$regenerate:$fac->modelpdf, $outputlangs);
					}
					else {
					    if ($usestdout) print "PDF for invoice ".$obj->facnumber." already exists\n";
					}

					// Add file into files array
					$files[] = $filename;
				}

				if ($result <= 0)
				{
					$error++;
					if ($usestdout) print "Error: Failed to build PDF for invoice ".($fac->ref?$fac->ref:' id '.$obj->rowid)."\n";
					else dol_syslog("Failed to build PDF for invoice ".($fac->ref?$fac->ref:' id '.$obj->rowid), LOG_ERR);
				}

	            $cpt++;
	        }


	        // Define format of output PDF
	        $formatarray=pdf_getFormat($langs);
	        $page_largeur = $formatarray['width'];
	        $page_hauteur = $formatarray['height'];
	        $format = array($page_largeur,$page_hauteur);

	        if ($usestdout) print "Using output PDF format ".join('x',$format)."\n";
	        else dol_syslog("Using output PDF format ".join('x',$format), LOG_ERR);


	        // Now, build a merged files with all files in $files array
			//---------------------------------------------------------

	        // Create empty PDF
	        $pdf=pdf_getInstance($format);
	        if (class_exists('TCPDF'))
	        {
	            $pdf->setPrintHeader(false);
	            $pdf->setPrintFooter(false);
	        }
	        $pdf->SetFont(pdf_getPDFFont($langs));

	        if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);
			//$pdf->SetCompression(false);


			//$pdf->Open();
			//$pdf->AddPage();
			//$title=$langs->trans("BillsCustomersUnpaid");
			//if ($option=='late') $title=$langs->trans("BillsCustomersUnpaid");
			//$pdf->MultiCell(100, 3, $title, 0, 'J');

			// Add all others
			foreach($files as $file)
			{
	            if ($usestdout) print "Merge PDF file for invoice ".$file."\n";
	            else dol_syslog("Merge PDF file for invoice ".$file);

				// Charge un document PDF depuis un fichier.
				$pagecount = $pdf->setSourceFile($file);
				for ($i = 1; $i <= $pagecount; $i++)
	            {
	                 $tplidx = $pdf->importPage($i);
	                 $s = $pdf->getTemplatesize($tplidx);
	                 $pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
	                 $pdf->useTemplate($tplidx);
	            }
			}

			// Create output dir if not exists
			dol_mkdir($diroutputpdf);

			// Save merged file
			$filename='mergedpdf';

			if (! empty($option)) $filename.='_'.$option;
			$file=$diroutputpdf.'/'.$filename.'.pdf';

			if (! $error && $pagecount)
			{
				$pdf->Output($file,'F');
				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));
			}

			if ($usestdout)
			{
				if (! $error) print "Merged PDF has been built in ".$file."\n";
				else print "Can't build PDF ".$file."\n";
			}

			$result = 1;
	    }
	    else
	    {
	        if ($usestdout) print "No invoices found for criteria.\n";
	        else dol_syslog("No invoices found for criteria");
	        $result = 0;
	    }
	}
	else
	{
	    dol_print_error($db);
	    dol_syslog("scripts/invoices/rebuild_merge.php: Error");
	    $error++;
	}

	if ($error) return -1;
	else return $result;
}

?>
