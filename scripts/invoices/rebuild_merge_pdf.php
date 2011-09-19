#!/usr/bin/php
<?php
/*
 * Copyright (C) 2009-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       scripts/invoices/rebuild_merge_pdf.php
 *      \ingroup    facture
 *      \brief      Script to rebuild PDF and merge PDF files into one
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You ar usingr PH for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

// Include Dolibarr environment
require_once($path."../../htdocs/master.inc.php");
// After this $db is an opened handler to database. We close it at end of file.
require_once(DOL_DOCUMENT_ROOT."/cron/functions_cron.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/facture/modules_facture.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');


// Load main language strings
$langs->load("main");

// Global variables
$version='1.24';
$error=0;


// -------------------- START OF YOUR CODE HERE --------------------
@set_time_limit(0);
print "***** ".$script_file." (".$version.") *****\n";

// Check parameters
if (! isset($argv[1]))
{
	usage();
	exit;
}

$diroutputpdf=$conf->facture->dir_output . '/temp';
$newmodel='';		// To force a new model
$newlangid='en_EN';	// To force a new lang id
$filter=array();
$option='';

foreach ($argv as $key => $value)
{
	$found=false;

	// Define options
	if (preg_match('/^lang=/i',$value))
	{
		$found=true;
		$valarray=explode('=',$value);
		$newlangid=$valarray[1];
		print 'Use language '.$newlangid.".\n";
	}

	if ($value == 'filter=all')
	{
		$found=true;
		$option.=(empty($option)?'':'_').'all';
		$filter[]='all';

		print 'Rebuild PDF for all invoices'."\n";
	}

	if ($value == 'filter=date')
	{
		$found=true;
		$option.=(empty($option)?'':'_').'date_'.$argv[$key+1].'_'.$argv[$key+2];
		$filter[]='date';

		$dateafterdate=dol_stringtotime($argv[$key+1]);
		$datebeforedate=dol_stringtotime($argv[$key+2]);
		print 'Rebuild PDF for invoices validated between '.dol_print_date($dateafterdate,'day')." and ".dol_print_date($datebeforedate,'day').".\n";
	}

	if ($value == 'filter=payments')
	{
		$found=true;
		$option.=(empty($option)?'':'_').'payments_'.$argv[$key+1].'_'.$argv[$key+2];
		$filter[]='payments';

		$paymentdateafter=dol_stringtotime($argv[$key+1]);
		$paymentdatebefore=dol_stringtotime($argv[$key+2]);
		print 'Rebuild PDF for invoices with at least one payment between '.dol_print_date($paymentdateafter,'day')." and ".dol_print_date($paymentdatebefore,'day').".\n";
	}

	if ($value == 'filter=nopayment')
	{
		$found=true;
		$option.=(empty($option)?'':'_').'nopayment';
		$filter[]='nopayment';

		print 'Rebuild PDF for invoices with no payment done yet.'."\n";
	}

    if ($value == 'filter=nodeposit')
    {
        $found=true;
        $option.=(empty($option)?'':'_').'nodeposit';
        $filter[]='nodeposit';

        print 'Exclude deposit invoices'."\n";
    }
    if ($value == 'filter=noreplacement')
    {
        $found=true;
        $option.=(empty($option)?'':'_').'noreplacement';
        $filter[]='noreplacement';

        print 'Exclude replacement invoices'."\n";
    }
    if ($value == 'filter=nocreditnote')
    {
        $found=true;
        $option.=(empty($option)?'':'_').'nocreditnote';
        $filter[]='nocreditnote';

        print 'Exclude credit note invoices'."\n";
    }

	if (! $found && preg_match('/filter=/i',$value))
	{
		usage();
		exit;
	}
}

// Check if an option and a filter has been provided
if (empty($option) && count($filter) <= 0)
{
	usage();
	exit;
}
// Check if there is no uncompatible choice
if (in_array('payments',$filter) && in_array('nopayment',$filter))
{
	usage();
	exit;
}


// Define SQL and SQL order request to select invoices
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
	$sqlwhere.= " AND f.datef >= ".$db->idate($dateafterdate);
	$sqlwhere.= " AND f.datef <= ".$db->idate($datebeforedate);
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
	$sqlwhere.= " AND p.datep >= ".$db->idate($paymentdateafter);
	$sqlwhere.= " AND p.datep <= ".$db->idate($paymentdatebefore);
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
dol_syslog("scripts/invoices/rebuild_merge.php: sql=",$sql);

print '--- start'."\n";

// Start of transaction
//$db->begin();

$error = 0;
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
            	print "Build PDF for invoice ".$obj->facnumber." - Lang = ".$outputlangs->defaultlang."\n";
				$result=facture_pdf_create($db, $fac, '', $newmodel?$newmodel:$fac->modelpdf, $outputlangs);

				// Add file into files array
				$files[] = $conf->facture->dir_output.'/'.$fac->ref.'/'.$fac->ref.'.pdf';
			}

			if ($result <= 0)
			{
				print "Error: Failed to build PDF for invoice ".$fac->ref."\n";
			}

            $cpt++;
        }


        // Now, build a merged files with all files in $files array
		//---------------------------------------------------------

        // Create empty PDF
        $pdf=pdf_getInstance();
        if (class_exists('TCPDF'))
        {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputlangs));

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
            print "Merge PDF file for invoice ".$file."\n";

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
		create_exdir($diroutputpdf);

		// Save merged file
		$filename='mergedpdf';

		if (! empty($option)) $filename.='_'.$option;

		if ($pagecount)
		{
			$file=$diroutputpdf.'/'.$filename.'.pdf';
			$pdf->Output($file,'F');
			if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));
		}

		print "Merged PDF has been built in ".$file."\n";
    }
    else
    {
        print "No invoices with payments in this range.\n";
    }
}
else
{
    dol_print_error($db);
    dol_syslog("scripts/invoices/rebuild_merge.php: Error");
}


// -------------------- END OF YOUR CODE --------------------

if (! $error)
{
	//$db->commit();
	print '--- end ok'."\n";
}
else
{
	print '--- end error code='.$error."\n";
	//$db->rollback();
}

$db->close();

return $error;


/**
 * Show usage of script
 *
 * @return unknown
 */
function usage()
{
	global $script_file;

    print "Rebuild PDF files for some invoices and merge PDF files into one.\n";
	print "\n";
	print "To build/merge PDF for invoices in a date range:\n";
	print "Usage:   ".$script_file." filter=date dateafter datebefore [lang=langcode]\n";
	print "To build/merge PDF for invoices with at least one payment in a date range:\n";
	print "Usage:   ".$script_file." filter=payments dateafter datebefore [lang=langcode]\n";
	print "To build/merge PDF for all invoices, use filter=all\n";
	print "Usage:   ".$script_file." filter=all\n";
	print "To build/merge PDF for invoices with no payments, use filter=nopayment\n";
	print "Usage:   ".$script_file." filter=nopayment\n";
    print "To exclude credit notes, use filter=nocreditnote\n";
    print "To exclude replacement invoices, use filter=noreplacement\n";
    print "To exclude deposit invoices, use filter=nodeposit\n";
    print "\n";
	print "Example: ".$script_file." filter=payments 20080101 20081231 lang=fr_FR\n";
	print "Example: ".$script_file." filter=all lang=it_IT\n";
	print "\n";
	print "Note that some filters can be cumulated.\n";
}
?>
