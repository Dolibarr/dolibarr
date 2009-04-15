#!/usr/bin/php
<?PHP
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       scripts/invoices/rebuild_merge_pdf.php
 *      \ingroup    facture
 *      \brief      Script to rebuild PDF and merge PDF files into one
 *		\version	$Id$
 */

// Test if batch mode
$sapi_type = php_sapi_name();
$script_file=__FILE__;
if (eregi('([^\\\/]+)$',$script_file,$reg)) $script_file=$reg[1];
$path=eregi_replace($script_file,'',$_SERVER["PHP_SELF"]);

if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PH for CGI/Web. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

// Include Dolibarr environment
require_once($path."../../htdocs/master.inc.php");
// After this $db is an opened handler to database. We close it at end of file.
require_once(DOL_DOCUMENT_ROOT."/cron/functions_cron.lib.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/facture/modules_facture.php");
require_once(DOL_DOCUMENT_ROOT."/includes/fpdf/fpdfi/fpdi.php");


// Load main language strings
$langs->load("main");

// Global variables
$version='$Revision$';
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
	if (eregi('^lang=',$value))
	{
		$found=true;
		$valarray=split('=',$value);
		$newlangid=$valarray[1];
		print 'Use language '.$newlangid.".\n";
	}

	if ($value == 'filter=all')
	{
		$found=true;
		$option.='all';
		$filter[]='all';

		print 'Rebuild PDF for all invoices'."\n";
	}

	if ($value == 'filter=date')
	{
		$found=true;
		$option.=(empty($option)?'':'_').'date_'.$argv[$key+1].'_'.$argv[$key+2];
		$filter[]='date';

		$dateafter=dol_stringtotime($argv[$key+1]);
		$datebefore=dol_stringtotime($argv[$key+2]);
		print 'Rebuild PDF for invoices validated between '.dol_print_date($dateafter,'day')." and ".dol_print_date($datebefore,'day').".\n";
	}

	if ($value == 'filter=payments')
	{
		$found=true;
		$option.=(empty($option)?'':'_').'payments_'.$argv[$key+1].'_'.$argv[$key+2];
		$filter[]='payments';

		$dateafter=dol_stringtotime($argv[$key+1]);
		$datebefore=dol_stringtotime($argv[$key+2]);
		print 'Rebuild PDF for invoices with at least one payment between '.dol_print_date($dateafter,'day')." and ".dol_print_date($datebefore,'day').".\n";
	}

	if ($value == 'filter=nopayment')
	{
		$found=true;
		$option.=(empty($option)?'':'_').'nopayment';
		$filter[]='nopayment';

		print 'Rebuild PDF for invoices with no payment done yet.'."\n";
	}

	if (! $found && eregi('filter=',$value))
	{
		usage();
		exit;
	}
}

// Check if an option and a filter has been provided
if (empty($option) && sizeof($filter) <= 0)
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
	$sqlwhere.= " AND f.datef >= ".$db->idate($dateafter);
	$sqlwhere.= " AND f.datef <= ".$db->idate($datebefore);
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
	$sqlwhere.= " AND p.datep >= ".$db->idate($dateafter);
	$sqlwhere.= " AND p.datep <= ".$db->idate($datebefore);
	$sqlorder = " ORDER BY p.datep ASC";
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
				$result=facture_pdf_create($db, $fac->id, '', $newmodel?$newmodel:$fac->modelpdf, $outputlangs);

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
		$pdf=new FPDI('P','mm','A4');
		if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);
		//$pdf->SetCompression(false);

		//$pdf->Open();
		//$pdf->AddPage();
		//$title=$langs->trans("BillsCustomersUnpayed");
		//if ($option=='late') $title=$langs->trans("BillsCustomersUnpayed");
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
			$pdf->Output($file);
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
	print "To build/merge PDF for invoices with at least one payment in a date range:\n";
	print "Usage:   ".$script_file." filter=payments dateafter datebefore [lang=langcode]\n";
	print "To build/merge PDF for invoices ina date range:\n";
	print "Usage:   ".$script_file." filter=date dateafter datebefore [lang=langcode]\n";
	print "To build/merge PDF for all invoices, use filter=all\n";
	print "Usage:   ".$script_file." filter=all\n";
	print "To build/merge PDF for invoices with no payments, use filter=nopayment\n";
	print "Usage:   ".$script_file." filter=nopayment\n";
	print "\n";
	print "Example: ".$script_file." filter=payments 20080101 20081231 lang=fr_FR\n";
	print "Example: ".$script_file." filter=all lang=it_IT\n";
}
?>
