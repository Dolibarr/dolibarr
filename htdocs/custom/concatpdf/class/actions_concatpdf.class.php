<?php
/* Copyright (C) 2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/concatpdf/class/actions_concatpdf.class.php
 *	\ingroup    societe
 *	\brief      File to control actions
 *	\version    $Id: actions_concatpdf.class.php,v 1.8 2011/09/11 18:41:48 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");


/**
 *	\class 		ActionsConcatPdf
 *	\brief 		Class to manage hooks for module ConcatPdf
 */
class ActionsConcatPdf
{
    var $db;
    var $error;
    var $errors=array();

    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    function ActionsConcatPdf($db)
    {
        $this->db = $db;
    }


    /**
     * Complete doc forms
     *
     * @param	array	$parameters		Array of parameters
     * @return	string					HTML content to add by hook
     */
    function formBuilddocOptions($parameters)
    {
        global $langs, $user, $conf;

        $langs->load("concatpdf@concatpdf");
        $htmlform=new Form($this->db);

        $out='';
        $out.='<tr class="liste_titre">';
        $out.='<td align="left" colspan="4" valign="top" class="formdoc">';
        $out.=$langs->trans("ConcatFile").' ';

        $morefiles=array();
        
        if ($parameters['modulepart'] == 'propal')
        {
        	$staticpdf=glob($conf->concatpdf->dir_output."/proposals/*.pdf");
        	$modelpdf=glob($conf->concatpdf->dir_output."/proposals/pdf_*.modules.php");
        }
        if ($parameters['modulepart'] == 'order'   || $parameters['modulepart'] == 'commande')
        {
        	$staticpdf=glob($conf->concatpdf->dir_output."/orders/*.pdf");
        	$modelpdf=glob($conf->concatpdf->dir_output."/orders/pdf_*.modules.php");
        }
        if ($parameters['modulepart'] == 'invoice' || $parameters['modulepart'] == 'facture')
        {
        	$staticpdf=glob($conf->concatpdf->dir_output."/invoices/*.pdf");
        	$modelpdf=glob($conf->concatpdf->dir_output."/invoices/pdf_*.modules.php");
        }

        if (! empty($staticpdf))
        {
            foreach ($staticpdf as $filename)
            {
            	$morefiles[] = basename($filename, ".pdf");
            }
        }
        if (! empty($modelpdf))
        {
        	foreach ($modelpdf as $filename)
        	{
        		$morefiles[] = basename($filename, ".php");
        	}
        }
        if (! empty($morefiles))
        {
            $out.= $htmlform->selectarray('concatpdffile',$morefiles,(GETPOST('concatpdffile')?GETPOST('concatpdffile'):-1),1,0,1);
        }

        return $out;
    }



    /**
     * Execute action
     *
     * @param	array	$parameters		Array of parameters
     * @param   Object	&$object    	Deprecated. This field is nto used
     * @param   string	$action     	'add', 'update', 'view'
     * @return  int 		        	<0 if KO,
     *                          		=0 if OK but we want to process standard actions too,
     *  	                            >0 if OK and we want to replace standard actions.
     */
    function afterPDFCreation($parameters,&$object,&$action)
    {
        global $langs,$conf;
        global $hookmanager;

        $outputlangs=$langs;

        $ret=0; $deltemp=0;
        dol_syslog(get_class($this).'::executeHooks action='.$action);
        
        $concatpdffile = GETPOST('concatpdffile');
        
        $element='';
        if ($parameters['object']->element == 'propal')  $element='proposals';
        if ($parameters['object']->element == 'order'   || $parameters['object']->element == 'commande') $element='orders';
        if ($parameters['object']->element == 'invoice' || $parameters['object']->element == 'facture')  $element='invoices';

        $filetoconcat1=$parameters['file'];
        $filetoconcat2='';
        //var_dump($parameters['object']->element); exit;
        if (preg_match('/^pdf_(.*)+\.modules/', $concatpdffile))
        {
        	require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
        	
        	$file = $conf->concatpdf->dir_output.'/'.$element.'/'.$concatpdffile.'.php';
        	$classname = str_replace('.modules', '', $concatpdffile);
        	require_once($file);
        	$obj = new $classname($db);
        	
        	// We save charset_output to restore it because write_file can change it if needed for
        	// output format that does not support UTF8.
        	$sav_charset_output=$outputlangs->charset_output;
        	// Change the output dir
        	$srctemplatepath = $conf->concatpdf->dir_temp;
        	// Generate pdf
        	$obj->write_file($parameters['object'], $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref, $hookmanager);
        	// Restore charset output
        	$outputlangs->charset_output=$sav_charset_output;
        	
        	$objectref = dol_sanitizeFileName($parameters['object']->ref);
        	$dir = $conf->concatpdf->dir_temp . "/" . $objectref;
        	$filetoconcat2 = $dir . "/" . $objectref . ".pdf";
        	
        	$deltemp++;
        }
        else
        {
        	$filetoconcat2=$conf->concatpdf->dir_output.'/'.$element.'/'.$concatpdffile.'.pdf';
        }
        
        dol_syslog(get_class($this).'::afterPDFCreation '.$filetoconcat1.' - '.$filetoconcat2);

        if ($filetoconcat2 && ! empty($concatpdffile) && $concatpdffile != '-1')
        {
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

            // Insert file 1
            $pagecount = $pdf->setSourceFile($filetoconcat1);
            for ($i = 1; $i <= $pagecount; $i++)
            {
                $tplidx = $pdf->importPage($i);
                $s = $pdf->getTemplatesize($tplidx);
                $pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
                $pdf->useTemplate($tplidx);
            }

            // Insert file 2
            $pagecount = $pdf->setSourceFile($filetoconcat2);
            for ($i = 1; $i <= $pagecount; $i++)
            {
                $tplidx = $pdf->importPage($i);
                $s = $pdf->getTemplatesize($tplidx);
                $pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
                $pdf->useTemplate($tplidx);
            }

            if ($pagecount)
            {
                $pdf->Output($filetoconcat1,'F');
                if (! empty($conf->global->MAIN_UMASK))
                {
                	@chmod($file, octdec($conf->global->MAIN_UMASK));
                }
                if ($deltemp)
                {
                	// Delete temp file
                	dol_delete_dir_recursive($dir);
                }
            }
        }

        return $ret;
    }

}

?>
