<?php
/* Copyright (C) 2006-2017	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007		Patrick Raguin      	<patrick.raguin@gmail.com>
 * Copyright (C) 2010-2012	Regis Houssin       	<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2017	Juanjo Menent       	<jmenent@2byte.es>
 * Copyright (C) 2012		Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014		Teddy Andreotti			<125155@supinfo.com>
 * Copyright (C) 2015-2016  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2019       Lenin Rivas           	<lenin.rivas@servcom-it.com>
 * Copyright (C) 2020       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2021-2022	Anthony Berton       	<anthony.berton@bb2a.fr>
 * Copyright (C) 2023-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/lib/pdf.lib.php
 *	\brief      Set of functions used for PDF generation
 *	\ingroup    core
 */

include_once DOL_DOCUMENT_ROOT.'/core/lib/signature.lib.php';


/**
 *  Return array head with list of tabs to view object information.
 *
 *  @return	array<array{0:string,1:string,2:string}>	head array with tabs
 */
function pdf_admin_prepare_head()
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/pdf.php';
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'pdf_admin');

	if (isModEnabled("propal") || isModEnabled('invoice') || isModEnabled('reception')) {
		$head[$h][0] = DOL_URL_ROOT.'/admin/pdf_other.php';
		$head[$h][1] = $langs->trans("Others");
		$head[$h][2] = 'other';
		$h++;
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'pdf_admin', 'remove');

	return $head;
}


/**
 *	Return array with format properties of default PDF format
 *
 *	@param		?Translate		$outputlangs		Output lang to use to autodetect output format if we need 'auto' detection
 *  @param		'setup'|'auto'	$mode				'setup' = Use setup, 'auto' = Force autodetection whatever is setup
 *  @return     array{width:float|int,height:float|int,unit:string}		Array('width'=>w,'height'=>h,'unit'=>u);
 */
function pdf_getFormat(Translate $outputlangs = null, $mode = 'setup')
{
	global $conf, $db, $langs;

	dol_syslog("pdf_getFormat Get paper format with mode=".$mode." MAIN_PDF_FORMAT=".(!getDolGlobalString('MAIN_PDF_FORMAT') ? 'null' : $conf->global->MAIN_PDF_FORMAT)." outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null')." and langs->defaultlang=".(is_object($langs) ? $langs->defaultlang : 'null'));

	// Default value if setup was not done and/or entry into c_paper_format not defined
	$width = 210;
	$height = 297;
	$unit = 'mm';

	if ($mode == 'auto' || !getDolGlobalString('MAIN_PDF_FORMAT') || getDolGlobalString('MAIN_PDF_FORMAT') == 'auto') {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$pdfformat = dol_getDefaultFormat($outputlangs);
	} else {
		$pdfformat = getDolGlobalString('MAIN_PDF_FORMAT');
	}

	$sql = "SELECT code, label, width, height, unit FROM ".MAIN_DB_PREFIX."c_paper_format";
	$sql .= " WHERE code = '".$db->escape($pdfformat)."'";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$width = (int) $obj->width;
			$height = (int) $obj->height;
			$unit = $obj->unit;
		}
	}

	//print "pdfformat=".$pdfformat." width=".$width." height=".$height." unit=".$unit;
	return array('width' => $width, 'height' => $height, 'unit' => $unit);
}

/**
 *      Return a PDF instance object. We create a FPDI instance that instantiate TCPDF.
 *
 *      @param	array{float|int,float|int}|array{}|''	$format	Array(width,height). Keep empty to use default setup.
 *      @param	string		$metric         Unit of format ('mm')
 *      @param  'P'|'l'		$pagetype       'P' or 'l'
 *      @return TCPDF|TCPDI					PDF object
 */
function pdf_getInstance($format = '', $metric = 'mm', $pagetype = 'P')
{
	global $conf;

	// Define constant for TCPDF
	if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
		define('K_TCPDF_EXTERNAL_CONFIG', 1); // this avoid using tcpdf_config file
		define('K_PATH_CACHE', DOL_DATA_ROOT.'/admin/temp/');
		define('K_PATH_URL_CACHE', DOL_DATA_ROOT.'/admin/temp/');
		dol_mkdir(K_PATH_CACHE);
		define('K_BLANK_IMAGE', '_blank.png');
		define('PDF_PAGE_FORMAT', 'A4');
		define('PDF_PAGE_ORIENTATION', 'P');
		define('PDF_CREATOR', 'TCPDF');
		define('PDF_AUTHOR', 'TCPDF');
		define('PDF_HEADER_TITLE', 'TCPDF Example');
		define('PDF_HEADER_STRING', "by Dolibarr ERP CRM");
		define('PDF_UNIT', 'mm');
		define('PDF_MARGIN_HEADER', 5);
		define('PDF_MARGIN_FOOTER', 10);
		define('PDF_MARGIN_TOP', 27);
		define('PDF_MARGIN_BOTTOM', 25);
		define('PDF_MARGIN_LEFT', 15);
		define('PDF_MARGIN_RIGHT', 15);
		define('PDF_FONT_NAME_MAIN', 'helvetica');
		define('PDF_FONT_SIZE_MAIN', 10);
		define('PDF_FONT_NAME_DATA', 'helvetica');
		define('PDF_FONT_SIZE_DATA', 8);
		define('PDF_FONT_MONOSPACED', 'courier');
		define('PDF_IMAGE_SCALE_RATIO', 1.25);
		define('HEAD_MAGNIFICATION', 1.1);
		define('K_CELL_HEIGHT_RATIO', 1.25);
		define('K_TITLE_MAGNIFICATION', 1.3);
		define('K_SMALL_RATIO', 2 / 3);
		define('K_THAI_TOPCHARS', true);
		define('K_TCPDF_CALLS_IN_HTML', true);
		if (getDolGlobalString('TCPDF_THROW_ERRORS_INSTEAD_OF_DIE')) {
			define('K_TCPDF_THROW_EXCEPTION_ERROR', true);
		} else {
			define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
		}
	}

	// Load TCPDF
	require_once TCPDF_PATH.'tcpdf.php';

	// We need to instantiate tcpdi object (instead of tcpdf) to use merging features. But we can disable it (this will break all merge features).
	if (!getDolGlobalString('MAIN_DISABLE_TCPDI')) {
		require_once TCPDI_PATH.'tcpdi.php';
	}

	//$arrayformat=pdf_getFormat();
	//$format=array($arrayformat['width'],$arrayformat['height']);
	//$metric=$arrayformat['unit'];

	//$pdfa = false; // PDF default version
	$pdfa = getDolGlobalInt('PDF_USE_A', 0); 	// PDF/A-1 ou PDF/A-3

	if (!getDolGlobalString('MAIN_DISABLE_TCPDI') && class_exists('TCPDI')) {
		$pdf = new TCPDI($pagetype, $metric, $format, true, 'UTF-8', false, $pdfa);
	} else {
		$pdf = new TCPDF($pagetype, $metric, $format, true, 'UTF-8', false, $pdfa);
	}

	// Protection and encryption of pdf
	if (getDolGlobalString('PDF_SECURITY_ENCRYPTION')) {
		/* Permission supported by TCPDF
		- print : Print the document;
		- modify : Modify the contents of the document by operations other than those controlled by 'fill-forms', 'extract' and 'assemble';
		- copy : Copy or otherwise extract text and graphics from the document;
		- annot-forms : Add or modify text annotations, fill in interactive form fields, and, if 'modify' is also set, create or modify interactive form fields (including signature fields);
		- fill-forms : Fill in existing interactive form fields (including signature fields), even if 'annot-forms' is not specified;
		- extract : Extract text and graphics (in support of accessibility to users with disabilities or for other purposes);
		- assemble : Assemble the document (insert, rotate, or delete pages and create bookmarks or thumbnail images), even if 'modify' is not set;
		- print-high : Print the document to a representation from which a faithful digital copy of the PDF content could be generated. When this is not set, printing is limited to a low-level representation of the appearance, possibly of degraded quality.
		- owner : (inverted logic - only for public-key) when set permits change of encryption and enables all other permissions.
		*/

		// For TCPDF, we specify permission we want to block
		$pdfrights = (getDolGlobalString('PDF_SECURITY_ENCRYPTION_RIGHTS') ? json_decode($conf->global->PDF_SECURITY_ENCRYPTION_RIGHTS, true) : array('modify', 'copy')); // Json format in llx_const

		// Password for the end user
		$pdfuserpass = getDolGlobalString('PDF_SECURITY_ENCRYPTION_USERPASS');

		// Password of the owner, created randomly if not defined
		$pdfownerpass = (getDolGlobalString('PDF_SECURITY_ENCRYPTION_OWNERPASS') ? getDolGlobalString('PDF_SECURITY_ENCRYPTION_OWNERPASS') : null);

		// For encryption strength: 0 = RC4 40 bit; 1 = RC4 128 bit; 2 = AES 128 bit; 3 = AES 256 bit
		$encstrength = getDolGlobalInt('PDF_SECURITY_ENCRYPTION_STRENGTH', 0);

		// Array of recipients containing public-key certificates ('c') and permissions ('p').
		// For example: array(array('c' => 'file://../examples/data/cert/tcpdf.crt', 'p' => array('print')))
		$pubkeys = (getDolGlobalString('PDF_SECURITY_ENCRYPTION_PUBKEYS') ? json_decode($conf->global->PDF_SECURITY_ENCRYPTION_PUBKEYS, true) : null); // Json format in llx_const

		$pdf->SetProtection($pdfrights, $pdfuserpass, $pdfownerpass, $encstrength, $pubkeys);
	}

	return $pdf;
}

/**
 * Return if pdf file is protected/encrypted
 *
 * @param   string		$pathoffile		Path of file
 * @return  boolean     			    True or false
 */
function pdf_getEncryption($pathoffile)
{
	require_once TCPDF_PATH.'tcpdf_parser.php';

	$isencrypted = false;

	$content = file_get_contents($pathoffile);

	//ob_start();
	@($parser = new TCPDF_PARSER(ltrim($content)));
	list($xref, $data) = $parser->getParsedData();
	unset($parser);
	//ob_end_clean();

	if (isset($xref['trailer']['encrypt'])) {
		$isencrypted = true; // Secured pdf file are currently not supported
	}

	if (empty($data)) {
		$isencrypted = true; // Object list not found. Possible secured file
	}

	return $isencrypted;
}

/**
 *      Return font name to use for PDF generation
 *
 *      @param	Translate	$outputlangs    Output langs object
 *      @return string          			Name of font to use
 */
function pdf_getPDFFont($outputlangs)
{
	global $conf;

	if (getDolGlobalString('MAIN_PDF_FORCE_FONT')) {
		return $conf->global->MAIN_PDF_FORCE_FONT;
	}

	$font = 'Helvetica'; // By default, for FPDI, or ISO language on TCPDF
	if (class_exists('TCPDF')) {  // If TCPDF on, we can use an UTF8 one like DejaVuSans if required (slower)
		if ($outputlangs->trans('FONTFORPDF') != 'FONTFORPDF') {
			$font = $outputlangs->trans('FONTFORPDF');
		}
	}
	return $font;
}

/**
 *      Return font size to use for PDF generation
 *
 *      @param	Translate	$outputlangs     Output langs object
 *      @return int				             Size of font to use
 */
function pdf_getPDFFontSize($outputlangs)
{
	global $conf;

	$size = 10; // By default, for FPDI or ISO language on TCPDF
	if (class_exists('TCPDF')) {  // If TCPDF on, we can use an UTF8 font like DejaVuSans if required (slower)
		if ($outputlangs->trans('FONTSIZEFORPDF') != 'FONTSIZEFORPDF') {
			$size = (int) $outputlangs->trans('FONTSIZEFORPDF');
		}
	}
	if (getDolGlobalString('MAIN_PDF_FORCE_FONT_SIZE')) {
		$size = getDolGlobalString('MAIN_PDF_FORCE_FONT_SIZE');
	}

	return $size;
}


/**
 * Return height to use for Logo onto PDF
 *
 * @param	string		$logo		Full path to logo file to use
 * @param	bool		$url		Image with url (true or false)
 * @return	int|float
 */
function pdf_getHeightForLogo($logo, $url = false)
{
	global $conf;
	$height = (!getDolGlobalString('MAIN_DOCUMENTS_LOGO_HEIGHT') ? 20 : $conf->global->MAIN_DOCUMENTS_LOGO_HEIGHT);
	$maxwidth = 130;
	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	$tmp = dol_getImageSize($logo, $url);
	if ($tmp['height']) {
		$width = round($height * $tmp['width'] / $tmp['height']);
		if ($width > $maxwidth) {
			$height = $height * $maxwidth / $width;
		}
	}
	//print $tmp['width'].' '.$tmp['height'].' '.$width; exit;
	return $height;
}

/**
 * Function to try to calculate height of a HTML Content
 *
 * @param 	TCPDF     $pdf				PDF initialized object
 * @param 	string    $htmlcontent		HTML Content
 * @return 	int							Height
 * @see getStringHeight()
 */
function pdfGetHeightForHtmlContent(&$pdf, $htmlcontent)
{
	// store current object
	$pdf->startTransaction();
	// store starting values
	$start_y = $pdf->GetY();
	//var_dump($start_y);
	$start_page = $pdf->getPage();
	// call printing functions with content
	$pdf->writeHTMLCell(0, 0, 0, $start_y, $htmlcontent, 0, 1, false, true, 'J', true);
	// get the new Y
	$end_y = $pdf->GetY();
	$end_page = $pdf->getPage();
	// calculate height
	$height = 0;
	if ($end_page == $start_page) {
		$height = $end_y - $start_y;
	} else {
		for ($page = $start_page; $page <= $end_page; ++$page) {
			$pdf->setPage($page);
			$tmpm = $pdf->getMargins();
			$tMargin = $tmpm['top'];
			if ($page == $start_page) {
				// first page
				$height = $pdf->getPageHeight() - $start_y - $pdf->getBreakMargin();
			} elseif ($page == $end_page) {
				// last page
				$height = $end_y - $tMargin;
			} else {
				$height = $pdf->getPageHeight() - $tMargin - $pdf->getBreakMargin();
			}
		}
	}
	// restore previous object
	$pdf = $pdf->rollbackTransaction();

	return $height;
}


/**
 * Returns the name of the thirdparty
 *
 * @param   Societe|Contact     $thirdparty     Contact or thirdparty
 * @param   Translate           $outputlangs    Output language
 * @param   int<0,1>            $includealias   1=Include alias name after name
 * @return  string                              String with name of thirdparty (+ alias if requested)
 */
function pdfBuildThirdpartyName($thirdparty, Translate $outputlangs, $includealias = 0)
{
	global $conf;

	// Recipient name
	$socname = '';

	if ($thirdparty instanceof Societe) {
		$socname = $thirdparty->name;
		if (($includealias || getDolGlobalInt('PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME')) && !empty($thirdparty->name_alias)) {
			if (getDolGlobalInt('PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME') == 2) {
				$socname = $thirdparty->name_alias." - ".$thirdparty->name;
			} else {
				$socname = $thirdparty->name." - ".$thirdparty->name_alias;
			}
		}
	} elseif ($thirdparty instanceof Contact) {
		if ($thirdparty->socid > 0) {
			$thirdparty->fetch_thirdparty();
			$socname = $thirdparty->thirdparty->name;
			if (($includealias || getDolGlobalInt('PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME')) && !empty($thirdparty->thirdparty->name_alias)) {
				if (getDolGlobalInt('PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME') == 2) {
					$socname = $thirdparty->thirdparty->name_alias." - ".$thirdparty->thirdparty->name;
				} else {
					$socname = $thirdparty->thirdparty->name." - ".$thirdparty->thirdparty->name_alias;
				}
			}
		}
	} else {
		throw new InvalidArgumentException('Parameter 1 $thirdparty is not a Societe nor Contact');
	}

	return $outputlangs->convToOutputCharset($socname);
}


/**
 *   	Return a string with full address formatted for output on PDF documents
 *
 * 		@param	Translate	          $outputlangs		    Output langs object
 *   	@param  Societe		          $sourcecompany		Source company object
 *   	@param  Societe|string|null   $targetcompany		Target company object
 *      @param  Contact|string|null	  $targetcontact	    Target contact object
 * 		@param	int			          $usecontact		    Use contact instead of company
 * 		@param	string  	          $mode				    Address type ('source', 'target', 'targetwithdetails', 'targetwithdetails_xxx': target but include also phone/fax/email/url)
 *      @param  ?CommonObject         $object               Object we want to build document for
 * 		@return	string|int				    		        String with full address or -1 if KO
 */
function pdf_build_address($outputlangs, $sourcecompany, $targetcompany = '', $targetcontact = '', $usecontact = 0, $mode = 'source', $object = null)
{
	global $hookmanager;

	if ($mode == 'source' && !is_object($sourcecompany)) {
		return -1;
	}
	if ($mode == 'target' && !is_object($targetcompany)) {
		return -1;
	}

	if (!empty($sourcecompany->state_id) && empty($sourcecompany->state)) {
		$sourcecompany->state = getState($sourcecompany->state_id);
	}
	if (!empty($targetcompany->state_id) && empty($targetcompany->state)) {
		$targetcompany->state = getState($targetcompany->state_id);
	}

	$reshook = 0;
	$stringaddress = '';
	if (is_object($hookmanager)) {
		$parameters = array('sourcecompany' => &$sourcecompany, 'targetcompany' => &$targetcompany, 'targetcontact' => &$targetcontact, 'outputlangs' => $outputlangs, 'mode' => $mode, 'usecontact' => $usecontact);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_build_address', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		$stringaddress .= $hookmanager->resPrint;
	}
	if (empty($reshook)) {
		if ($mode == 'source') {
			$withCountry = 0;
			if (isset($targetcompany->country_code) && !empty($sourcecompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
				$withCountry = 1;
			}

			$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($sourcecompany, $withCountry, "\n", $outputlangs))."\n";

			if (!getDolGlobalString('MAIN_PDF_DISABLESOURCEDETAILS')) {
				// Phone
				if ($sourcecompany->phone) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("PhoneShort").": ".$outputlangs->convToOutputCharset($sourcecompany->phone);
				}
				// Fax
				if ($sourcecompany->fax) {
					$stringaddress .= ($stringaddress ? ($sourcecompany->phone ? " - " : "\n") : '').$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($sourcecompany->fax);
				}
				// EMail
				if ($sourcecompany->email) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($sourcecompany->email);
				}
				// Web
				if ($sourcecompany->url) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($sourcecompany->url);
				}
			}
			// Intra VAT
			if (getDolGlobalString('MAIN_TVAINTRA_IN_SOURCE_ADDRESS')) {
				if ($sourcecompany->tva_intra) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($sourcecompany->tva_intra);
				}
			}
			// Professional Ids
			$reg = array();
			if (getDolGlobalString('MAIN_PROFID1_IN_SOURCE_ADDRESS') && !empty($sourcecompany->idprof1)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId1", $sourcecompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof1);
			}
			if (getDolGlobalString('MAIN_PROFID2_IN_SOURCE_ADDRESS') && !empty($sourcecompany->idprof2)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId2", $sourcecompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof2);
			}
			if (getDolGlobalString('MAIN_PROFID3_IN_SOURCE_ADDRESS') && !empty($sourcecompany->idprof3)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId3", $sourcecompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof3);
			}
			if (getDolGlobalString('MAIN_PROFID4_IN_SOURCE_ADDRESS') && !empty($sourcecompany->idprof4)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId4", $sourcecompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof4);
			}
			if (getDolGlobalString('MAIN_PROFID5_IN_SOURCE_ADDRESS') && !empty($sourcecompany->idprof5)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId5", $sourcecompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof5);
			}
			if (getDolGlobalString('MAIN_PROFID6_IN_SOURCE_ADDRESS') && !empty($sourcecompany->idprof6)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId6", $sourcecompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof6);
			}
			if (getDolGlobalString('PDF_ADD_MORE_AFTER_SOURCE_ADDRESS')) {
				$stringaddress .= ($stringaddress ? "\n" : '') . getDolGlobalString('PDF_ADD_MORE_AFTER_SOURCE_ADDRESS');
			}
		}

		if ($mode == 'target' || preg_match('/targetwithdetails/', $mode)) {
			if ($usecontact) {
				if (is_object($targetcontact)) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($targetcontact->getFullName($outputlangs, 1));

					if (!empty($targetcontact->address)) {
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($targetcontact))."\n";
					} else {
						$companytouseforaddress = $targetcompany;

						// Contact on a thirdparty that is a different thirdparty than the thirdparty of object
						if ($targetcontact->socid > 0 && $targetcontact->socid != $targetcompany->id) {
							$targetcontact->fetch_thirdparty();
							$companytouseforaddress = $targetcontact->thirdparty;
						}

						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($companytouseforaddress))."\n";
					}
					// Country
					if (!empty($targetcontact->country_code) && $targetcontact->country_code != $sourcecompany->country_code) {
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcontact->country_code));
					} elseif (empty($targetcontact->country_code) && !empty($targetcompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code));
					}

					if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || preg_match('/targetwithdetails/', $mode)) {
						// Phone
						if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
							if (!empty($targetcontact->phone_pro) || !empty($targetcontact->phone_mobile)) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Phone").": ";
							}
							if (!empty($targetcontact->phone_pro)) {
								$stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_pro);
							}
							if (!empty($targetcontact->phone_pro) && !empty($targetcontact->phone_mobile)) {
								$stringaddress .= " / ";
							}
							if (!empty($targetcontact->phone_mobile)) {
								$stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_mobile);
							}
						}
						// Fax
						if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode)) {
							if ($targetcontact->fax) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcontact->fax);
							}
						}
						// EMail
						if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode)) {
							if ($targetcontact->email) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcontact->email);
							}
						}
						// Web
						if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode)) {
							if ($targetcontact->url) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcontact->url);
							}
						}
					}
				}
			} else {
				if (is_object($targetcompany)) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($targetcompany));
					// Country
					if (!empty($targetcompany->country_code) && $targetcompany->country_code != $sourcecompany->country_code) {
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code));
					} else {
						$stringaddress .= ($stringaddress ? "\n" : '');
					}

					if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || preg_match('/targetwithdetails/', $mode)) {
						// Phone
						if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
							if (!empty($targetcompany->phone)) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Phone").": ";
							}
							if (!empty($targetcompany->phone)) {
								$stringaddress .= $outputlangs->convToOutputCharset($targetcompany->phone);
							}
						}
						// Fax
						if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode)) {
							if ($targetcompany->fax) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcompany->fax);
							}
						}
						// EMail
						if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode)) {
							if ($targetcompany->email) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcompany->email);
							}
						}
						// Web
						if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode)) {
							if ($targetcompany->url) {
								$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcompany->url);
							}
						}
					}
				}
			}

			// Intra VAT
			if (!getDolGlobalString('MAIN_TVAINTRA_NOT_IN_ADDRESS')) {
				if ($usecontact && is_object($targetcontact) && getDolGlobalInt('MAIN_USE_COMPANY_NAME_OF_CONTACT')) {
					$targetcontact->fetch_thirdparty();
					if (!empty($targetcontact->thirdparty->id) && $targetcontact->thirdparty->tva_intra) {
						$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->transnoentities("VATIntraShort") . ': ' . $outputlangs->convToOutputCharset($targetcontact->thirdparty->tva_intra);
					}
				} elseif (!empty($targetcompany->tva_intra)) {
					$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($targetcompany->tva_intra);
				}
			}

			// Legal form
			if (getDolGlobalString('MAIN_LEGALFORM_IN_ADDRESS') && !empty($targetcompany->forme_juridique_code)) {
				$tmp = getFormeJuridiqueLabel((string) $targetcompany->forme_juridique_code);
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp;
			}

			// Professional Ids
			if (getDolGlobalString('MAIN_PROFID1_IN_ADDRESS') && !empty($targetcompany->idprof1)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId1", $targetcompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof1);
			}
			if (getDolGlobalString('MAIN_PROFID2_IN_ADDRESS') && !empty($targetcompany->idprof2)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId2", $targetcompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof2);
			}
			if (getDolGlobalString('MAIN_PROFID3_IN_ADDRESS') && !empty($targetcompany->idprof3)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId3", $targetcompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof3);
			}
			if (getDolGlobalString('MAIN_PROFID4_IN_ADDRESS') && !empty($targetcompany->idprof4)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId4", $targetcompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof4);
			}
			if (getDolGlobalString('MAIN_PROFID5_IN_ADDRESS') && !empty($targetcompany->idprof5)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId5", $targetcompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof5);
			}
			if (getDolGlobalString('MAIN_PROFID6_IN_ADDRESS') && !empty($targetcompany->idprof6)) {
				$tmp = $outputlangs->transcountrynoentities("ProfId6", $targetcompany->country_code);
				if (preg_match('/\((.+)\)/', $tmp, $reg)) {
					$tmp = $reg[1];
				}
				$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof6);
			}

			// Public note
			if (getDolGlobalString('MAIN_PUBLIC_NOTE_IN_ADDRESS')) {
				if ($mode == 'source' && !empty($sourcecompany->note_public)) {
					$stringaddress .= ($stringaddress ? "\n" : '').dol_string_nohtmltag($sourcecompany->note_public);
				}
				if (($mode == 'target' || preg_match('/targetwithdetails/', $mode)) && !empty($targetcompany->note_public)) {
					$stringaddress .= ($stringaddress ? "\n" : '').dol_string_nohtmltag($targetcompany->note_public);
				}
			}
		}
	}

	return $stringaddress;
}


/**
 *   	Show header of page for PDF generation
 *
 *   	@param	TCPDF		$pdf     		Object PDF
 *      @param	Translate	$outputlangs	Object lang for output
 * 		@param	int			$page_height	Height of page
 *      @return	void
 */
function pdf_pagehead(&$pdf, $outputlangs, $page_height)
{
	global $conf;

	// Add a background image on document only if good setup of const
	if (getDolGlobalString('MAIN_USE_BACKGROUND_ON_PDF') && (getDolGlobalString('MAIN_USE_BACKGROUND_ON_PDF') != '-1')) {		// Warning, this option make TCPDF generation being crazy and some content disappeared behind the image
		$filepath = $conf->mycompany->dir_output.'/logos/' . getDolGlobalString('MAIN_USE_BACKGROUND_ON_PDF');
		if (file_exists($filepath)) {
			$pdf->SetAutoPageBreak(0, 0); // Disable auto pagebreak before adding image
			if (getDolGlobalString('MAIN_USE_BACKGROUND_ON_PDF_ALPHA')) {
				$pdf->SetAlpha($conf->global->MAIN_USE_BACKGROUND_ON_PDF_ALPHA);
			} // Option for change opacity of background
			$pdf->Image($filepath, (isset($conf->global->MAIN_USE_BACKGROUND_ON_PDF_X) ? $conf->global->MAIN_USE_BACKGROUND_ON_PDF_X : 0), (isset($conf->global->MAIN_USE_BACKGROUND_ON_PDF_Y) ? $conf->global->MAIN_USE_BACKGROUND_ON_PDF_Y : 0), 0, $page_height);
			if (getDolGlobalString('MAIN_USE_BACKGROUND_ON_PDF_ALPHA')) {
				$pdf->SetAlpha(1);
			}
			$pdf->SetPageMark(); // This option avoid to have the images missing on some pages
			$pdf->SetAutoPageBreak(1, 0); // Restore pagebreak
		}
	}
	if (getDolGlobalString('MAIN_ADD_PDF_BACKGROUND') && getDolGlobalString('MAIN_ADD_PDF_BACKGROUND') != '-1') {
		$pdf->SetPageMark(); // This option avoid to have the images missing on some pages
	}
}


/**
 *	Return array of possible substitutions for PDF content (without external module substitutions).
 *
 *	@param	Translate	    $outputlangs	Output language
 *	@param	null|string[]	$exclude        Array of family keys we want to exclude. For example array('mycompany', 'object', 'date', 'user', ...)
 *	@param	?Object			$object         Object
 *	@param	int<0,2>        $onlykey       	1=Do not calculate some heavy values of keys (performance enhancement when we need only the keys), 2=Values are truncated and html sanitized (to use for help tooltip)
 *  @param  null|string[]	$include        Array of family keys we want to include. For example array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...)
 *	@return	array<string,string>			Array of substitutions
 */
function pdf_getSubstitutionArray($outputlangs, $exclude = null, $object = null, $onlykey = 0, $include = null)
{
	$substitutionarray = getCommonSubstitutionArray($outputlangs, $onlykey, $exclude, $object, $include);
	$substitutionarray['__FROM_NAME__'] = '__FROM_NAME__';
	$substitutionarray['__FROM_EMAIL__'] = '__FROM_EMAIL__';
	return $substitutionarray;
}


/**
 *      Add a draft watermark on PDF files
 *
 *      @param	TCPDF      	$pdf            Object PDF
 *      @param  Translate	$outputlangs	Object lang
 *      @param  int		    $h		        Height of PDF
 *      @param  int		    $w		        Width of PDF
 *      @param  string	    $unit           Unit of height (mm, pt, ...)
 *      @param  string		$text           Text to show
 *      @return	void
 */
function pdf_watermark(&$pdf, $outputlangs, $h, $w, $unit, $text)
{
	// Print Draft Watermark
	if ($unit == 'pt') {
		$k = 1;
	} elseif ($unit == 'mm') {
		$k = 72 / 25.4;
	} elseif ($unit == 'cm') {
		$k = 72 / 2.54;
	} elseif ($unit == 'in') {
		$k = 72;
	} else {
		$k = 1;
		dol_print_error(null, 'Unexpected unit "'.$unit.'" for pdf_watermark');
	}

	// Make substitution
	$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, null);
	complete_substitutions_array($substitutionarray, $outputlangs, null);
	$text = make_substitutions($text, $substitutionarray, $outputlangs);
	$text = $outputlangs->convToOutputCharset($text);

	$savx = $pdf->getX();
	$savy = $pdf->getY();

	$watermark_angle = atan($h / $w) / 2;
	$watermark_x_pos = 0;
	$watermark_y_pos = $h / 3;
	$watermark_x = $w / 2;
	$watermark_y = $h / 3;
	$pdf->SetFont('', 'B', 40);
	$pdf->SetTextColor(255, 192, 203);

	//rotate
	$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', cos($watermark_angle), sin($watermark_angle), -sin($watermark_angle), cos($watermark_angle), $watermark_x * $k, ($h - $watermark_y) * $k, -$watermark_x * $k, -($h - $watermark_y) * $k));
	//print watermark
	$pdf->SetAlpha(0.5);
	$pdf->SetXY($watermark_x_pos, $watermark_y_pos);
	$pdf->Cell($w - 20, 25, $outputlangs->convToOutputCharset($text), "", 2, "C", 0);
	//antirotate
	$pdf->_out('Q');

	$pdf->SetXY($savx, $savy);
}


/**
 *  Show bank information for PDF generation
 *
 *  @param	TCPDF		$pdf            		Object PDF
 *  @param  Translate	$outputlangs     		Object lang
 *  @param  int			$curx            		X
 *  @param  int			$cury            		Y
 *  @param  Account		$account         		Bank account object
 *  @param  int			$onlynumber      		Output only number (bank+desk+key+number according to country, but without name of bank and address)
 *  @param	int			$default_font_size		Default font size
 *  @return	float                               The Y PDF position
 */
function pdf_bank(&$pdf, $outputlangs, $curx, $cury, $account, $onlynumber = 0, $default_font_size = 10)
{
	global $mysoc, $conf;

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbank.class.php';

	$diffsizetitle = getDolGlobalInt('PDF_DIFFSIZE_TITLE', 3);
	$diffsizecontent = getDolGlobalInt('PDF_DIFFSIZE_CONTENT', 4);
	$pdf->SetXY($curx, $cury);

	if (empty($onlynumber)) {
		$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByTransferOnThisBankAccount').':', 0, 'L', 0);
		$cury += 4;
	}

	$outputlangs->load("banks");

	// Use correct name of bank id according to country
	$bickey = "BICNumber";
	if ($account->getCountryCode() == 'IN') {
		$bickey = "SWIFT";
	}

	// Get format of bank account according to its country
	$usedetailedbban = $account->useDetailedBBAN();

	//$onlynumber=0; $usedetailedbban=1; // For tests
	if ($usedetailedbban) {
		$savcurx = $curx;

		if (empty($onlynumber)) {
			$pdf->SetFont('', '', $default_font_size - $diffsizecontent);
			$pdf->SetXY($curx, $cury);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Bank").': '.$outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
			$cury += 3;
		}

		if (!getDolGlobalString('PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN')) {    // Note that some countries still need bank number, BIC/IBAN not enough for them
			// Note:
			// bank = code_banque (FR), sort code (GB, IR. Example: 12-34-56)
			// desk = code guichet (FR), used only when $usedetailedbban = 1
			// number = account number
			// key = check control key used only when $usedetailedbban = 1
			if (empty($onlynumber)) {
				$pdf->line($curx + 1, $cury + 1, $curx + 1, $cury + 6);
			}


			foreach ($account->getFieldsToShow() as $val) {
				$pdf->SetXY($curx, $cury + 4);
				$pdf->SetFont('', '', $default_font_size - 3);

				if ($val == 'BankCode') {
					// Bank code
					$tmplength = 18;
					$content = $account->code_banque;
				} elseif ($val == 'DeskCode') {
					// Desk
					$tmplength = 18;
					$content = $account->code_guichet;
				} elseif ($val == 'BankAccountNumber') {
					// Number
					$tmplength = 24;
					$content = $account->number;
				} elseif ($val == 'BankAccountNumberKey') {
					// Key
					$tmplength = 15;
					$content = $account->cle_rib;
				} elseif ($val == 'IBAN' || $val == 'BIC') {
					// Key
					$tmplength = 0;
					$content = '';
				} else {
					dol_print_error($account->db, 'Unexpected value for getFieldsToShow: '.$val);
					break;
				}

				$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($content), 0, 'C', 0);
				$pdf->SetXY($curx, $cury + 1);
				$curx += $tmplength;
				$pdf->SetFont('', 'B', $default_font_size - $diffsizecontent);
				$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities($val), 0, 'C', 0);
				if (empty($onlynumber)) {
					$pdf->line($curx, $cury + 1, $curx, $cury + 7);
				}
			}

			$curx = $savcurx;
			$cury += 8;
		}
	} elseif (!empty($account->number)) {
		$pdf->SetFont('', 'B', $default_font_size - $diffsizecontent);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Bank").': '.$outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
		$cury += 3;

		$pdf->SetFont('', 'B', $default_font_size - $diffsizecontent);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("BankAccountNumber").': '.$outputlangs->convToOutputCharset($account->number), 0, 'L', 0);
		$cury += 3;

		if ($diffsizecontent <= 2) {
			$cury += 1;
		}
	}

	$pdf->SetFont('', '', $default_font_size - $diffsizecontent);

	if (empty($onlynumber) && (!empty($account->domiciliation) || !empty($account->address))) {
		$pdf->SetXY($curx, $cury);
		$val = $outputlangs->transnoentities("Residence").': '.$outputlangs->convToOutputCharset(empty($account->address) ? $account->domiciliation : $account->address);
		$pdf->MultiCell(100, 3, $val, 0, 'L', 0);
		//$nboflines=dol_nboflines_bis($val,120);
		//$cury+=($nboflines*3)+2;
		$tmpy = $pdf->getStringHeight(100, $val);
		$cury += $tmpy;
	}

	if (!empty($account->owner_name)) {
		$pdf->SetXY($curx, $cury);
		$val = $outputlangs->transnoentities("BankAccountOwner").': '.$outputlangs->convToOutputCharset($account->owner_name);
		$pdf->MultiCell(100, 3, $val, 0, 'L', 0);
		$tmpy = $pdf->getStringHeight(100, $val);
		$cury += $tmpy;
	} elseif (!$usedetailedbban) {
		$cury += 1;
	}

	// Use correct name of bank id according to country
	$ibankey = FormBank::getIBANLabel($account);

	if (!empty($account->iban)) {
		//Remove whitespaces to ensure we are dealing with the format we expect
		$ibanDisplay_temp = str_replace(' ', '', $outputlangs->convToOutputCharset($account->iban));
		$ibanDisplay = "";

		$nbIbanDisplay_temp = dol_strlen($ibanDisplay_temp);
		for ($i = 0; $i < $nbIbanDisplay_temp; $i++) {
			$ibanDisplay .= $ibanDisplay_temp[$i];
			if ($i % 4 == 3 && $i > 0) {
				$ibanDisplay .= " ";
			}
		}

		$pdf->SetFont('', 'B', $default_font_size - 3);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities($ibankey).': '.$ibanDisplay, 0, 'L', 0);
		$cury += 3;
	}

	if (!empty($account->bic)) {
		$pdf->SetFont('', 'B', $default_font_size - 3);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities($bickey).': '.$outputlangs->convToOutputCharset($account->bic), 0, 'L', 0);
	}

	return $pdf->getY();
}

/**
 *  Show footer of page for PDF generation
 *
 *	@param	TCPDF		$pdf     		The PDF factory
 *  @param  Translate	$outputlangs	Object lang for output
 * 	@param	string		$paramfreetext	Constant name of free text
 * 	@param	Societe		$fromcompany	Object company
 * 	@param	int			$marge_basse	Margin bottom we use for the autobreak
 * 	@param	int			$marge_gauche	Margin left (no more used)
 * 	@param	int			$page_hauteur	Page height
 * 	@param	CommonObject	$object			Object shown in PDF
 * 	@param	int<0,3>	$showdetails	Show company address details into footer (0=Nothing, 1=Show address, 2=Show managers, 3=Both)
 *  @param	int			$hidefreetext	1=Hide free text, 0=Show free text
 *  @param	int			$page_largeur	Page width
 *  @param	string		$watermark		Watermark text to print on page
 * 	@return	int							Return height of bottom margin including footer text
 */
function pdf_pagefoot(&$pdf, $outputlangs, $paramfreetext, $fromcompany, $marge_basse, $marge_gauche, $page_hauteur, $object, $showdetails = 0, $hidefreetext = 0, $page_largeur = 0, $watermark = '')
{
	global $conf, $hookmanager;

	$outputlangs->load("dict");
	$line = '';
	$reg = array();
	$marginwithfooter = 0;  // Return value

	$dims = $pdf->getPageDimensions();

	// Line of free text
	if (empty($hidefreetext) && getDolGlobalString($paramfreetext)) {
		$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
		// More substitution keys
		$substitutionarray['__FROM_NAME__'] = $fromcompany->name;
		$substitutionarray['__FROM_EMAIL__'] = $fromcompany->email;
		complete_substitutions_array($substitutionarray, $outputlangs, $object);
		$newfreetext = make_substitutions(getDolGlobalString($paramfreetext), $substitutionarray, $outputlangs);

		// Make a change into HTML code to allow to include images from medias directory.
		// <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		// become
		// <img alt="" src="'.DOL_DATA_ROOT.'/medias/image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		$newfreetext = preg_replace('/(<img.*src=")[^\"]*viewimage\.php[^\"]*modulepart=medias[^\"]*file=([^\"]*)("[^\/]*\/>)/', '\1file:/'.DOL_DATA_ROOT.'/medias/\2\3', $newfreetext);

		$line .= $outputlangs->convToOutputCharset($newfreetext);
	}

	// First line of company infos
	$line1 = "";
	$line2 = "";
	$line3 = "";
	$line4 = "";

	if ($showdetails == 1 || $showdetails == 3) {
		// Company name
		if ($fromcompany->name) {
			$line1 .= ($line1 ? " - " : "").$outputlangs->transnoentities("RegisteredOffice").": ".$fromcompany->name;
		}
		// Address
		if ($fromcompany->address) {
			$line1 .= ($line1 ? " - " : "").str_replace("\n", ", ", $fromcompany->address);
		}
		// Zip code
		if ($fromcompany->zip) {
			$line1 .= ($line1 ? " - " : "").$fromcompany->zip;
		}
		// Town
		if ($fromcompany->town) {
			$line1 .= ($line1 ? " " : "").$fromcompany->town;
		}
		// Country
		if ($fromcompany->country) {
			$line1 .= ($line1 ? ", " : "").$fromcompany->country;
		}
		// Phone
		if ($fromcompany->phone) {
			$line2 .= ($line2 ? " - " : "").$outputlangs->transnoentities("Phone").": ".$fromcompany->phone;
		}
		// Fax
		if ($fromcompany->fax) {
			$line2 .= ($line2 ? " - " : "").$outputlangs->transnoentities("Fax").": ".$fromcompany->fax;
		}

		// URL
		if ($fromcompany->url) {
			$line2 .= ($line2 ? " - " : "").$fromcompany->url;
		}
		// Email
		if ($fromcompany->email) {
			$line2 .= ($line2 ? " - " : "").$fromcompany->email;
		}
	}
	if ($showdetails == 2 || $showdetails == 3 || (!empty($fromcompany->country_code) && $fromcompany->country_code == 'DE')) {
		// Managers
		if ($fromcompany->managers) {
			$line2 .= ($line2 ? " - " : "").$fromcompany->managers;
		}
	}

	// Line 3 of company infos
	// Juridical status
	if (!empty($fromcompany->forme_juridique_code) && $fromcompany->forme_juridique_code) {
		$line3 .= ($line3 ? " - " : "").$outputlangs->convToOutputCharset(getFormeJuridiqueLabel((string) $fromcompany->forme_juridique_code));
	}
	// Capital
	if (!empty($fromcompany->capital)) {
		$tmpamounttoshow = price2num($fromcompany->capital); // This field is a free string or a float
		if (is_numeric($tmpamounttoshow) && $tmpamounttoshow > 0) {
			$line3 .= ($line3 ? " - " : "").$outputlangs->transnoentities("CapitalOf", price($tmpamounttoshow, 0, $outputlangs, 0, 0, 0, $conf->currency));
		} elseif (!empty($fromcompany->capital)) {
			$line3 .= ($line3 ? " - " : "").$outputlangs->transnoentities("CapitalOf", $fromcompany->capital, $outputlangs);
		}
	}
	// Prof Id 1
	if (!empty($fromcompany->idprof1) && $fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || !$fromcompany->idprof2)) {
		$field = $outputlangs->transcountrynoentities("ProfId1", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line3 .= ($line3 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof1);
	}
	// Prof Id 2
	if (!empty($fromcompany->idprof2) && $fromcompany->idprof2) {
		$field = $outputlangs->transcountrynoentities("ProfId2", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line3 .= ($line3 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof2);
	}

	// Line 4 of company infos
	// Prof Id 3
	if (!empty($fromcompany->idprof3) && $fromcompany->idprof3) {
		$field = $outputlangs->transcountrynoentities("ProfId3", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof3);
	}
	// Prof Id 4
	if (!empty($fromcompany->idprof4) && $fromcompany->idprof4) {
		$field = $outputlangs->transcountrynoentities("ProfId4", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof4);
	}
	// Prof Id 5
	if (!empty($fromcompany->idprof5) && $fromcompany->idprof5) {
		$field = $outputlangs->transcountrynoentities("ProfId5", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof5);
	}
	// Prof Id 6
	if (!empty($fromcompany->idprof6) &&  $fromcompany->idprof6) {
		$field = $outputlangs->transcountrynoentities("ProfId6", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof6);
	}
	// Prof Id 7
	if (!empty($fromcompany->idprof7) &&  $fromcompany->idprof7) {
		$field = $outputlangs->transcountrynoentities("ProfId7", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof7);
	}
	// Prof Id 8
	if (!empty($fromcompany->idprof8) &&  $fromcompany->idprof8) {
		$field = $outputlangs->transcountrynoentities("ProfId8", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof8);
	}
	// Prof Id 9
	if (!empty($fromcompany->idprof9) &&  $fromcompany->idprof9) {
		$field = $outputlangs->transcountrynoentities("ProfId9", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof9);
	}
	// Prof Id 10
	if (!empty($fromcompany->idprof10) &&  $fromcompany->idprof10) {
		$field = $outputlangs->transcountrynoentities("ProfId10", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line4 .= ($line4 ? " - " : "").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof10);
	}
	// IntraCommunautary VAT
	if (!empty($fromcompany->tva_intra)  && $fromcompany->tva_intra != '') {
		$line4 .= ($line4 ? " - " : "").$outputlangs->transnoentities("VATIntraShort").": ".$outputlangs->convToOutputCharset($fromcompany->tva_intra);
	}

	$pdf->SetFont('', '', 7);
	$pdf->SetDrawColor(224, 224, 224);
	// Option for footer text color
	if (getDolGlobalString('PDF_FOOTER_TEXT_COLOR')) {
		list($r, $g, $b) = sscanf($conf->global->PDF_FOOTER_TEXT_COLOR, '%d, %d, %d');
		$pdf->SetTextColor($r, $g, $b);
	}

	// The start of the bottom of this page footer is positioned according to # of lines
	$freetextheight = 0;
	$align = null;
	if ($line) {	// Free text
		//$line="sample text<br>\nfd<strong>sf</strong>sdf<br>\nghfghg<br>";
		if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {
			$width = 20000;
			$align = 'L'; // By default, ask a manual break: We use a large value 20000, to not have automatic wrap. This make user understand, he need to add CR on its text.
			if (getDolGlobalString('MAIN_USE_AUTOWRAP_ON_FREETEXT')) {
				$width = 200;
				$align = 'C';
			}
			$freetextheight = $pdf->getStringHeight($width, $line);
		} else {
			$freetextheight = pdfGetHeightForHtmlContent($pdf, dol_htmlentitiesbr($line, 1, 'UTF-8', 0)); // New method (works for HTML content)
			//print '<br>'.$freetextheight;exit;
		}
	}

	$posy = 0;
	// For customized footer
	if (is_object($hookmanager)) {
		$parameters = array('line1' => $line1, 'line2' => $line2, 'line3' => $line3, 'line4' => $line4, 'outputlangs' => $outputlangs);
		$action = '';
		$hookmanager->executeHooks('pdf_pagefoot', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		if (!empty($hookmanager->resPrint) && $hidefreetext == 0) {
			$mycustomfooter = $hookmanager->resPrint;
			$mycustomfooterheight = pdfGetHeightForHtmlContent($pdf, dol_htmlentitiesbr($mycustomfooter, 1, 'UTF-8', 0));

			$marginwithfooter = $marge_basse + $freetextheight + $mycustomfooterheight;
			$posy = (float) $marginwithfooter;

			// Option for footer background color (without freetext zone)
			if (getDolGlobalString('PDF_FOOTER_BACKGROUND_COLOR')) {
				list($r, $g, $b) = sscanf($conf->global->PDF_FOOTER_BACKGROUND_COLOR, '%d, %d, %d');
				$pdf->SetAutoPageBreak(0, 0); // Disable auto pagebreak
				$pdf->Rect(0, $dims['hk'] - $posy + $freetextheight, $dims['wk'] + 1, $marginwithfooter + 1, 'F', array(), $fill_color = array($r, $g, $b));
				$pdf->SetAutoPageBreak(1, 0); // Restore pagebreak
			}

			if (getDolGlobalInt('PDF_FREETEXT_DISABLE_PAGEBREAK') === 1) {
				$pdf->SetAutoPageBreak(0, 0);
			} // Option for disable auto pagebreak
			if ($line) {	// Free text
				$pdf->SetXY($dims['lm'], -$posy);
				if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {   // by default
					$pdf->MultiCell(0, 3, $line, 0, $align, 0);
				} else {
					$pdf->writeHTMLCell($dims['wk'] - $dims['lm'] - $dims['rm'], $freetextheight, $dims['lm'], $dims['hk'] - $marginwithfooter, dol_htmlentitiesbr($line, 1, 'UTF-8', 0));
				}
				$posy -= $freetextheight;
			}
			if (getDolGlobalInt('PDF_FREETEXT_DISABLE_PAGEBREAK') === 1) {
				$pdf->SetAutoPageBreak(1, 0);
			} // Restore pagebreak

			$pdf->SetY(-$posy);

			// Hide footer line if footer background color is set
			if (!getDolGlobalString('PDF_FOOTER_BACKGROUND_COLOR')) {
				$pdf->line($dims['lm'], $dims['hk'] - $posy, $dims['wk'] - $dims['rm'], $dims['hk'] - $posy);
			}

			// Option for set top margin height of footer after freetext
			if (getDolGlobalString('PDF_FOOTER_TOP_MARGIN') || getDolGlobalInt('PDF_FOOTER_TOP_MARGIN') === 0) {
				$posy -= (float) getDolGlobalString('PDF_FOOTER_TOP_MARGIN');
			} else {
				$posy--;
			}

			if (getDolGlobalInt('PDF_FOOTER_DISABLE_PAGEBREAK') === 1) {
				$pdf->SetAutoPageBreak(0, 0);
			} // Option for disable auto pagebreak
			$pdf->writeHTMLCell($dims['wk'] - $dims['lm'] - $dims['rm'], $mycustomfooterheight, $dims['lm'], $dims['hk'] - $posy, dol_htmlentitiesbr($mycustomfooter, 1, 'UTF-8', 0));
			if (getDolGlobalInt('PDF_FOOTER_DISABLE_PAGEBREAK') === 1) {
				$pdf->SetAutoPageBreak(1, 0);
			} // Restore pagebreak

			$posy -= $mycustomfooterheight - 3;
		} else {
			// Else default footer
			$marginwithfooter = $marge_basse + $freetextheight + (!empty($line1) ? 3 : 0) + (!empty($line2) ? 3 : 0) + (!empty($line3) ? 3 : 0) + (!empty($line4) ? 3 : 0);
			$posy = (float) $marginwithfooter;

			// Option for footer background color (without freetext zone)
			if (getDolGlobalString('PDF_FOOTER_BACKGROUND_COLOR')) {
				list($r, $g, $b) = sscanf($conf->global->PDF_FOOTER_BACKGROUND_COLOR, '%d, %d, %d');
				$pdf->SetAutoPageBreak(0, 0); // Disable auto pagebreak
				$pdf->Rect(0, $dims['hk'] - $posy + $freetextheight, $dims['wk'] + 1, $marginwithfooter + 1, 'F', array(), $fill_color = array($r, $g, $b));
				$pdf->SetAutoPageBreak(1, 0); // Restore pagebreak
			}

			if (getDolGlobalInt('PDF_FREETEXT_DISABLE_PAGEBREAK') === 1) {
				$pdf->SetAutoPageBreak(0, 0);
			} // Option for disable auto pagebreak
			if ($line) {	// Free text
				$pdf->SetXY($dims['lm'], -$posy);
				if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {   // by default
					$pdf->MultiCell(0, 3, $line, 0, $align, 0);
				} else {
					$pdf->writeHTMLCell($dims['wk'] - $dims['lm'] - $dims['rm'], $freetextheight, $dims['lm'], $dims['hk'] - $marginwithfooter, dol_htmlentitiesbr($line, 1, 'UTF-8', 0));
				}
				$posy -= $freetextheight;
			}
			if (getDolGlobalInt('PDF_FREETEXT_DISABLE_PAGEBREAK') === 1) {
				$pdf->SetAutoPageBreak(1, 0);
			} // Restore pagebreak

			$pdf->SetY(-$posy);

			// Option for hide all footer (page number will no hidden)
			if (!getDolGlobalInt('PDF_FOOTER_HIDDEN')) {
				// Hide footer line if footer background color is set
				if (!getDolGlobalString('PDF_FOOTER_BACKGROUND_COLOR')) {
					$pdf->line($dims['lm'], $dims['hk'] - $posy, $dims['wk'] - $dims['rm'], $dims['hk'] - $posy);
				}

				// Option for set top margin height of footer after freetext
				if (getDolGlobalString('PDF_FOOTER_TOP_MARGIN') || getDolGlobalInt('PDF_FOOTER_TOP_MARGIN') === 0) {
					$posy -= (float) getDolGlobalString('PDF_FOOTER_TOP_MARGIN');
				} else {
					$posy--;
				}

				if (!empty($line1)) {
					$pdf->SetFont('', 'B', 7);
					$pdf->SetXY($dims['lm'], -$posy);
					$pdf->MultiCell($dims['wk'] - $dims['rm'] - $dims['lm'], 2, $line1, 0, 'C', 0);
					$posy -= 3;
					$pdf->SetFont('', '', 7);
				}

				if (!empty($line2)) {
					$pdf->SetFont('', 'B', 7);
					$pdf->SetXY($dims['lm'], -$posy);
					$pdf->MultiCell($dims['wk'] - $dims['rm'] - $dims['lm'], 2, $line2, 0, 'C', 0);
					$posy -= 3;
					$pdf->SetFont('', '', 7);
				}

				if (!empty($line3)) {
					$pdf->SetXY($dims['lm'], -$posy);
					$pdf->MultiCell($dims['wk'] - $dims['rm'] - $dims['lm'], 2, $line3, 0, 'C', 0);
				}

				if (!empty($line4)) {
					$posy -= 3;
					$pdf->SetXY($dims['lm'], -$posy);
					$pdf->MultiCell($dims['wk'] - $dims['rm'] - $dims['lm'], 2, $line4, 0, 'C', 0);
				}
			}
		}
	}

	// Show page nb and apply correction for some font.
	$pdf->SetXY($dims['wk'] - $dims['rm'] - 18 - getDolGlobalInt('PDF_FOOTER_PAGE_NUMBER_X', 0), -$posy - getDolGlobalInt('PDF_FOOTER_PAGE_NUMBER_Y', 0));

	$pagination = $pdf->PageNo().' / '.$pdf->getAliasNbPages();
	$fontRenderCorrection = 0;
	if (in_array(pdf_getPDFFont($outputlangs), array('freemono',  'DejaVuSans'))) {
		$fontRenderCorrection = 10;
	}
	$pdf->MultiCell(18 + $fontRenderCorrection, 2, $pagination, 0, 'R', 0);

	//  Show Draft Watermark
	if (!empty($watermark)) {
		pdf_watermark($pdf, $outputlangs, $page_hauteur, $page_largeur, 'mm', $watermark);
	}

	return $marginwithfooter;
}

/**
 *	Show linked objects for PDF generation
 *
 *	@param	TCPDF		$pdf				Object PDF
 *	@param	CommonObject	$object				Object
 *	@param  Translate	$outputlangs		Object lang
 *	@param  int			$posx				X
 *	@param  int			$posy				Y
 *	@param	float		$w					Width of cells. If 0, they extend up to the right margin of the page.
 *	@param	float		$h					Cell minimum height. The cell extends automatically if needed.
 *	@param	string		$align				Align
 *	@param	float		$default_font_size	Font size
 *	@return	float                           The Y PDF position
 */
function pdf_writeLinkedObjects(&$pdf, $object, $outputlangs, $posx, $posy, $w, $h, $align, $default_font_size)
{
	$linkedobjects = pdf_getLinkedObjects($object, $outputlangs);
	if (!empty($linkedobjects)) {
		foreach ($linkedobjects as $linkedobject) {
			$reftoshow = $linkedobject["ref_title"].' : '.$linkedobject["ref_value"];
			if (!empty($linkedobject["date_value"])) {
				$reftoshow .= ' / '.$linkedobject["date_value"];
			}

			$posy += 3;
			$pdf->SetXY($posx, $posy);
			$pdf->SetFont('', '', (float) $default_font_size - 2);
			$pdf->MultiCell($w, $h, $reftoshow, '', $align);
		}
	}

	return $pdf->getY();
}

/**
 *	Output line description into PDF
 *
 *  @param  TCPDF			$pdf               	PDF object
 *	@param	CommonObject	$object				Object
 *	@param	int				$i					Current line number
 *  @param  Translate		$outputlangs		Object lang for output
 *  @param  int				$w					Width
 *  @param  int				$h					Height
 *  @param  int				$posx				Pos x
 *  @param  int				$posy				Pos y
 *  @param  int<0,1>		$hideref       		Hide reference
 *  @param  int<0,1>		$hidedesc           Hide description
 * 	@param	int<0,1>		$issupplierline		Is it a line for a supplier object ?
 *  @param	'L'|'C'|'R'|'J'	$align				text alignment ('L', 'C', 'R', 'J' (default))
 * 	@return	string
 */
function pdf_writelinedesc(&$pdf, $object, $i, $outputlangs, $w, $h, $posx, $posy, $hideref = 0, $hidedesc = 0, $issupplierline = 0, $align = 'J')
{
	global $db, $conf, $langs, $hookmanager;

	$reshook = 0;
	$result = '';
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('pdf' => $pdf, 'i' => $i, 'outputlangs' => $outputlangs, 'w' => $w, 'h' => $h, 'posx' => $posx, 'posy' => $posy, 'hideref' => $hideref, 'hidedesc' => $hidedesc, 'issupplierline' => $issupplierline, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_writelinedesc', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) {
			$result .= $hookmanager->resPrint;
		}
	}
	if (empty($reshook)) {
		$labelproductservice = pdf_getlinedesc($object, $i, $outputlangs, $hideref, $hidedesc, $issupplierline);
		$labelproductservice = preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php[^\"]*modulepart=medias[^\"]*file=([^\"]*)/', '\1file:/'.DOL_DATA_ROOT.'/medias/\2\3', $labelproductservice, -1, $nbrep);

		//var_dump($labelproductservice);exit;

		// Fix bug of some HTML editors that replace links <img src="http://localhostgit/viewimage.php?modulepart=medias&file=image/efd.png" into <img src="http://localhostgit/viewimage.php?modulepart=medias&amp;file=image/efd.png"
		// We make the reverse, so PDF generation has the real URL.
		$nbrep = 0;
		$labelproductservice = preg_replace('/(<img[^>]*src=")([^"]*)(&amp;)([^"]*")/', '\1\2&\4', $labelproductservice, -1, $nbrep);

		if (getDolGlobalString('MARGIN_TOP_ZERO_UL')) {
			$pdf->setListIndentWidth(5);
			$TMarginList = ['ul' => [['h' => 0.1, ],['h' => 0.1, ]], 'li' => [['h' => 0.1, ],],];
			$pdf->setHtmlVSpace($TMarginList);
		}

		// Description
		$pdf->writeHTMLCell($w, $h, $posx, $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1, false, true, $align, true);
		$result .= $labelproductservice;
	}
	return $result;
}

/**
 *  Return line description translated in outputlangs and encoded into htmlentities and with <br>
 *
 *  @param  CommonObject	$object              Object
 *  @param  int			$i                   Current line number (0 = first line, 1 = second line, ...)
 *  @param  Translate	$outputlangs         Object langs for output
 *  @param  int			$hideref             Hide reference
 *  @param  int			$hidedesc            Hide description
 *  @param  int			$issupplierline      Is it a line for a supplier object ?
 *  @return string       				     String with line
 */
function pdf_getlinedesc($object, $i, $outputlangs, $hideref = 0, $hidedesc = 0, $issupplierline = 0)
{
	global $db, $conf, $langs;

	$idprod = (!empty($object->lines[$i]->fk_product) ? $object->lines[$i]->fk_product : false);
	$label = (!empty($object->lines[$i]->label) ? $object->lines[$i]->label : (!empty($object->lines[$i]->product_label) ? $object->lines[$i]->product_label : ''));
	$product_barcode = (!empty($object->lines[$i]->product_barcode) ? $object->lines[$i]->product_barcode : "");
	$desc = (!empty($object->lines[$i]->desc) ? $object->lines[$i]->desc : (!empty($object->lines[$i]->description) ? $object->lines[$i]->description : ''));
	$ref_supplier = (!empty($object->lines[$i]->ref_supplier) ? $object->lines[$i]->ref_supplier : (!empty($object->lines[$i]->ref_fourn) ? $object->lines[$i]->ref_fourn : '')); // TODO Not yet saved for supplier invoices, only supplier orders
	$note = (!empty($object->lines[$i]->note) ? $object->lines[$i]->note : '');
	$dbatch = (!empty($object->lines[$i]->detail_batch) ? $object->lines[$i]->detail_batch : false);

	if ($issupplierline) {
		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
		$prodser = new ProductFournisseur($db);
	} else {
		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		$prodser = new Product($db);

		if (getDolGlobalString('PRODUIT_CUSTOMER_PRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_AND_MULTIPRICES')) {
			include_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';
		}
	}

	if ($idprod) {
		$prodser->fetch($idprod);
		// If a predefined product and multilang and on other lang, we renamed label with label translated
		if (getDolGlobalInt('MAIN_MULTILANGS') && ($outputlangs->defaultlang != $langs->defaultlang)) {
			$translatealsoifmodified = (getDolGlobalString('MAIN_MULTILANG_TRANSLATE_EVEN_IF_MODIFIED')); // By default if value was modified manually, we keep it (no translation because we don't have it)

			// TODO Instead of making a compare to see if param was modified, check that content contains reference translation. If yes, add the added part to the new translation
			// ($textwasnotmodified is replaced with $textwasmodifiedorcompleted and we add completion).

			// Set label
			// If we want another language, and if label is same than default language (we did not force it to a specific value), we can use translation.
			//var_dump($outputlangs->defaultlang.' - '.$langs->defaultlang.' - '.$label.' - '.$prodser->label);exit;
			$textwasnotmodified = ($label == $prodser->label);
			if (!empty($prodser->multilangs[$outputlangs->defaultlang]["label"]) && ($textwasnotmodified || $translatealsoifmodified)) {
				$label = $prodser->multilangs[$outputlangs->defaultlang]["label"];
			}

			// Set desc
			// Manage HTML entities description test because $prodser->description is store with htmlentities but $desc no
			$textwasnotmodified = false;
			if (!empty($desc) && dol_textishtml($desc) && !empty($prodser->description) && dol_textishtml($prodser->description)) {
				$textwasnotmodified = (strpos(dol_html_entity_decode($desc, ENT_QUOTES | ENT_HTML5), dol_html_entity_decode($prodser->description, ENT_QUOTES | ENT_HTML5)) !== false);
			} else {
				$textwasnotmodified = ($desc == $prodser->description);
			}
			if (!empty($prodser->multilangs[$outputlangs->defaultlang]["description"])) {
				if ($textwasnotmodified) {
					$desc = str_replace($prodser->description, $prodser->multilangs[$outputlangs->defaultlang]["description"], $desc);
				} elseif ($translatealsoifmodified) {
					$desc = $prodser->multilangs[$outputlangs->defaultlang]["description"];
				}
			}

			// Set note
			$textwasnotmodified = ($note == $prodser->note_public);
			if (!empty($prodser->multilangs[$outputlangs->defaultlang]["other"]) && ($textwasnotmodified || $translatealsoifmodified)) {
				$note = $prodser->multilangs[$outputlangs->defaultlang]["other"];
			}
		}
	} elseif (($object->element == 'facture' || $object->element == 'facturefourn') && preg_match('/^\(DEPOSIT\).+/', $desc)) { // We must not replace '(DEPOSIT)' when it is alone, it will be translated and detailed later
		$desc = str_replace('(DEPOSIT)', $outputlangs->trans('Deposit'), $desc);
	}

	$libelleproduitservice = '';  // Default value
	if (!getDolGlobalString('PDF_HIDE_PRODUCT_LABEL_IN_SUPPLIER_LINES')) {
		// Description short of product line
		$libelleproduitservice = $label;
		if (!empty($libelleproduitservice) && getDolGlobalString('PDF_BOLD_PRODUCT_LABEL')) {
			// Adding <b> may convert the original string into a HTML string. So we have to first
			// convert \n into <br> we text is not already HTML.
			if (!dol_textishtml($libelleproduitservice)) {
				$libelleproduitservice = str_replace("\n", '<br>', $libelleproduitservice);
			}
			$libelleproduitservice = '<b>'.$libelleproduitservice.'</b>';
		}
	}


	// Add ref of subproducts
	if (getDolGlobalString('SHOW_SUBPRODUCT_REF_IN_PDF')) {
		$prodser->get_sousproduits_arbo();
		if (!empty($prodser->sousprods) && is_array($prodser->sousprods) && count($prodser->sousprods)) {
			$outputlangs->load('mrp');
			$tmparrayofsubproducts = reset($prodser->sousprods);

			$qtyText = null;
			if (isset($object->lines[$i]->qty) && !empty($object->lines[$i]->qty)) {
				$qtyText = $object->lines[$i]->qty;
			} elseif (isset($object->lines[$i]->qty_shipped) && !empty($object->lines[$i]->qty_shipped)) {
				$qtyText = $object->lines[$i]->qty;
			}

			if (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF')) {
				foreach ($tmparrayofsubproducts as $subprodval) {
					$libelleproduitservice = dol_concatdesc(
						dol_concatdesc($libelleproduitservice, " * ".$subprodval[3]),
						(!empty($qtyText) ?
							$outputlangs->trans('Qty').':'.$qtyText.' x '.$outputlangs->trans('AssociatedProducts').':'.$subprodval[1].'= '.$outputlangs->trans('QtyTot').':'.$subprodval[1] * $qtyText :
							$outputlangs->trans('Qty').' '.$outputlangs->trans('AssociatedProducts').':'.$subprodval[1])
					);
				}
			} else {
				foreach ($tmparrayofsubproducts as $subprodval) {
					$libelleproduitservice = dol_concatdesc(
						dol_concatdesc($libelleproduitservice, " * ".$subprodval[5].(($subprodval[5] && $subprodval[3]) ? ' - ' : '').$subprodval[3]),
						(!empty($qtyText) ?
							$outputlangs->trans('Qty').':'.$qtyText.' x '.$outputlangs->trans('AssociatedProducts').':'.$subprodval[1].'= '.$outputlangs->trans('QtyTot').':'.$subprodval[1] * $qtyText :
							$outputlangs->trans('Qty').' '.$outputlangs->trans('AssociatedProducts').':'.$subprodval[1])
					);
				}
			}
		}
	}

	if (isModEnabled('barcode') && getDolGlobalString('MAIN_GENERATE_DOCUMENTS_SHOW_PRODUCT_BARCODE') && !empty($product_barcode)) {
		$libelleproduitservice = dol_concatdesc($libelleproduitservice, $outputlangs->trans("BarCode")." ".$product_barcode);
	}

	// Description long of product line
	if (!empty($desc) && ($desc != $label)) {
		if ($desc == '(CREDIT_NOTE)' && $object->lines[$i]->fk_remise_except) {
			$discount = new DiscountAbsolute($db);
			$discount->fetch($object->lines[$i]->fk_remise_except);
			$sourceref = !empty($discount->discount_type) ? $discount->ref_invoice_supplier_source : $discount->ref_facture_source;
			$libelleproduitservice = $outputlangs->transnoentitiesnoconv("DiscountFromCreditNote", $sourceref);
		} elseif ($desc == '(DEPOSIT)' && $object->lines[$i]->fk_remise_except) {
			$discount = new DiscountAbsolute($db);
			$discount->fetch($object->lines[$i]->fk_remise_except);
			$sourceref = !empty($discount->discount_type) ? $discount->ref_invoice_supplier_source : $discount->ref_facture_source;
			$libelleproduitservice = $outputlangs->transnoentitiesnoconv("DiscountFromDeposit", $sourceref);
			// Add date of deposit
			if (getDolGlobalString('INVOICE_ADD_DEPOSIT_DATE')) {
				$libelleproduitservice .= ' ('.dol_print_date($discount->datec, 'day', '', $outputlangs).')';
			}
		} elseif ($desc == '(EXCESS RECEIVED)' && $object->lines[$i]->fk_remise_except) {
			$discount = new DiscountAbsolute($db);
			$discount->fetch($object->lines[$i]->fk_remise_except);
			$libelleproduitservice = $outputlangs->transnoentitiesnoconv("DiscountFromExcessReceived", $discount->ref_facture_source);
		} elseif ($desc == '(EXCESS PAID)' && $object->lines[$i]->fk_remise_except) {
			$discount = new DiscountAbsolute($db);
			$discount->fetch($object->lines[$i]->fk_remise_except);
			$libelleproduitservice = $outputlangs->transnoentitiesnoconv("DiscountFromExcessPaid", $discount->ref_invoice_supplier_source);
		} else {
			if ($idprod) {
				// Check if description must be output
				if (!empty($object->element)) {
					$tmpkey = 'MAIN_DOCUMENTS_HIDE_DESCRIPTION_FOR_'.strtoupper($object->element);
					if (getDolGlobalString($tmpkey)) {
						$hidedesc = 1;
					}
				}
				if (empty($hidedesc)) {
					if (getDolGlobalString('MAIN_DOCUMENTS_DESCRIPTION_FIRST')) {
						$libelleproduitservice = dol_concatdesc($desc, $libelleproduitservice);
					} else {
						if (getDolGlobalString('HIDE_LABEL_VARIANT_PDF') && $prodser->isVariant()) {
							$libelleproduitservice = $desc;
						} else {
							$libelleproduitservice = dol_concatdesc($libelleproduitservice, $desc);
						}
					}
				}
			} else {
				$libelleproduitservice = dol_concatdesc($libelleproduitservice, $desc);
			}
		}
	}

	// We add ref of product (and supplier ref if defined)
	$prefix_prodserv = "";
	$ref_prodserv = "";
	if (getDolGlobalString('PRODUCT_ADD_TYPE_IN_DOCUMENTS')) {   // In standard mode, we do not show this
		if ($prodser->isService()) {
			$prefix_prodserv = $outputlangs->transnoentitiesnoconv("Service")." ";
		} else {
			$prefix_prodserv = $outputlangs->transnoentitiesnoconv("Product")." ";
		}
	}

	if (empty($hideref)) {
		if ($issupplierline) {
			if (!getDolGlobalString('PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES')) {  // Common case
				$ref_prodserv = $prodser->ref; // Show local ref
				if ($ref_supplier) {
					$ref_prodserv .= ($prodser->ref ? ' (' : '').$outputlangs->transnoentitiesnoconv("SupplierRef").' '.$ref_supplier.($prodser->ref ? ')' : '');
				}
			} elseif (getDolGlobalInt('PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES') == 1) {
				$ref_prodserv = $ref_supplier;
			} elseif (getDolGlobalInt('PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES') == 2) {
				$ref_prodserv = $ref_supplier.' ('.$outputlangs->transnoentitiesnoconv("InternalRef").' '.$prodser->ref.')';
			}
		} else {
			$ref_prodserv = $prodser->ref; // Show local ref only

			if (getDolGlobalString('PRODUIT_CUSTOMER_PRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_AND_MULTIPRICES')) {
				$productCustomerPriceStatic = new ProductCustomerPrice($db);
				$filter = array('fk_product' => $idprod, 'fk_soc' => $object->socid);

				$nbCustomerPrices = $productCustomerPriceStatic->fetchAll('', '', 1, 0, $filter);

				if ($nbCustomerPrices > 0) {
					$productCustomerPrice = $productCustomerPriceStatic->lines[0];

					if (!empty($productCustomerPrice->ref_customer)) {
						switch ($conf->global->PRODUIT_CUSTOMER_PRICES_PDF_REF_MODE) {
							case 1:
								$ref_prodserv = $productCustomerPrice->ref_customer;
								break;

							case 2:
								$ref_prodserv = $productCustomerPrice->ref_customer . ' (' . $outputlangs->transnoentitiesnoconv('InternalRef') . ' ' . $ref_prodserv . ')';
								break;

							default:
								$ref_prodserv = $ref_prodserv . ' (' . $outputlangs->transnoentitiesnoconv('RefCustomer') . ' ' . $productCustomerPrice->ref_customer . ')';
						}
					}
				}
			}
		}

		if (!empty($libelleproduitservice) && !empty($ref_prodserv)) {
			$ref_prodserv .= " - ";
		}
	}

	if (!empty($ref_prodserv) && getDolGlobalString('PDF_BOLD_PRODUCT_REF_AND_PERIOD')) {
		if (!dol_textishtml($libelleproduitservice)) {
			$libelleproduitservice = str_replace("\n", '<br>', $libelleproduitservice);
		}
		$ref_prodserv = '<b>'.$ref_prodserv.'</b>';
		// $prefix_prodserv and $ref_prodser are not HTML var
	}
	$libelleproduitservice = $prefix_prodserv.$ref_prodserv.$libelleproduitservice;

	// Add an additional description for the category products
	if (getDolGlobalString('CATEGORY_ADD_DESC_INTO_DOC') && $idprod && isModEnabled('category')) {
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$categstatic = new Categorie($db);
		// recovering the list of all the categories linked to product
		$tblcateg = $categstatic->containing($idprod, Categorie::TYPE_PRODUCT);
		foreach ($tblcateg as $cate) {
			// Adding the descriptions if they are filled
			$desccateg = $cate->description;
			if ($desccateg) {
				$libelleproduitservice = dol_concatdesc($libelleproduitservice, $desccateg);
			}
		}
	}

	if (!empty($object->lines[$i]->date_start) || !empty($object->lines[$i]->date_end)) {
		$format = 'day';
		$period = '';
		// Show duration if exists
		if ($object->lines[$i]->date_start && $object->lines[$i]->date_end) {
			$period = '('.$outputlangs->transnoentitiesnoconv('DateFromTo', dol_print_date($object->lines[$i]->date_start, $format, false, $outputlangs), dol_print_date($object->lines[$i]->date_end, $format, false, $outputlangs)).')';
		}
		if ($object->lines[$i]->date_start && !$object->lines[$i]->date_end) {
			$period = '('.$outputlangs->transnoentitiesnoconv('DateFrom', dol_print_date($object->lines[$i]->date_start, $format, false, $outputlangs)).')';
		}
		if (!$object->lines[$i]->date_start && $object->lines[$i]->date_end) {
			$period = '('.$outputlangs->transnoentitiesnoconv('DateUntil', dol_print_date($object->lines[$i]->date_end, $format, false, $outputlangs)).')';
		}
		//print '>'.$outputlangs->charset_output.','.$period;
		if (getDolGlobalString('PDF_BOLD_PRODUCT_REF_AND_PERIOD')) {
			if (!dol_textishtml($libelleproduitservice)) {
				$libelleproduitservice = str_replace("\n", '<br>', $libelleproduitservice);
			}
			$libelleproduitservice .= '<br><b style="color:#333666;" ><em>'.$period.'</em></b>';
		} else {
			$libelleproduitservice = dol_concatdesc($libelleproduitservice, $period);
		}
		//print $libelleproduitservice;
	}

	// Show information for lot
	if (!empty($dbatch)) {
		// $object is a shipment.
		//var_dump($object->lines[$i]->details_entrepot);		// array from llx_expeditiondet (we can have several lines for one fk_origin_line)
		//var_dump($object->lines[$i]->detail_batch);			// array from llx_expeditiondet_batch (each line with a lot is linked to llx_expeditiondet)

		include_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
		include_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
		$tmpwarehouse = new Entrepot($db);
		$tmpproductbatch = new Productbatch($db);

		$format = 'day';
		foreach ($dbatch as $detail) {
			$dte = array();
			if ($detail->eatby) {
				$dte[] = $outputlangs->transnoentitiesnoconv('printEatby', dol_print_date($detail->eatby, $format, false, $outputlangs));
			}
			if ($detail->sellby) {
				$dte[] = $outputlangs->transnoentitiesnoconv('printSellby', dol_print_date($detail->sellby, $format, false, $outputlangs));
			}
			if ($detail->batch) {
				$dte[] = $outputlangs->transnoentitiesnoconv('printBatch', $detail->batch);
			}
			$dte[] = $outputlangs->transnoentitiesnoconv('printQty', $detail->qty);

			// Add also info of planned warehouse for lot
			if ($object->element == 'shipping' && $detail->fk_origin_stock > 0 && getDolGlobalInt('PRODUCTBATCH_SHOW_WAREHOUSE_ON_SHIPMENT')) {
				$resproductbatch = $tmpproductbatch->fetch($detail->fk_origin_stock);
				if ($resproductbatch > 0) {
					$reswarehouse = $tmpwarehouse->fetch($tmpproductbatch->warehouseid);
					if ($reswarehouse > 0) {
						$dte[] = $tmpwarehouse->ref;
					}
				}
			}

			$libelleproduitservice .= "__N__  ".implode(" - ", $dte);
		}
	} else {
		if (getDolGlobalInt('PRODUCTBATCH_SHOW_WAREHOUSE_ON_SHIPMENT')) {
			// TODO Show warehouse for shipment line without batch
		}
	}

	// Now we convert \n into br
	if (dol_textishtml($libelleproduitservice)) {
		$libelleproduitservice = preg_replace('/__N__/', '<br>', $libelleproduitservice);
	} else {
		$libelleproduitservice = preg_replace('/__N__/', "\n", $libelleproduitservice);
	}
	$libelleproduitservice = dol_htmlentitiesbr($libelleproduitservice, 1);

	return $libelleproduitservice;
}

/**
 *	Return line num
 *
 *	@param	CommonObject	$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	string
 */
function pdf_getlinenum($object, $i, $outputlangs, $hidedetails = 0)
{
	global $hookmanager;

	$reshook = 0;
	$result = '';
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlinenum', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		$result .= $hookmanager->resPrint;
	}
	if (empty($reshook)) {
		$result .= dol_htmlentitiesbr($object->lines[$i]->num);
	}
	return $result;
}


/**
 *	Return line product ref
 *
 *	@param	CommonObject	$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	string
 */
function pdf_getlineref($object, $i, $outputlangs, $hidedetails = 0)
{
	global $hookmanager;

	$reshook = 0;
	$result = '';
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlineref', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		$result .= $hookmanager->resPrint;
	}
	if (empty($reshook)) {
		$result .= dol_htmlentitiesbr($object->lines[$i]->product_ref);
	}
	return $result;
}


/**
 *	Return line ref_supplier
 *
 *	@param	Contrat|CommandeFournisseur|FactureFournisseur|Facture|Product|Reception|SupplierProposal	$object	Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int<0,2>	$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	string
 */
function pdf_getlineref_supplier($object, $i, $outputlangs, $hidedetails = 0)
{
	global $hookmanager;

	$reshook = 0;
	$result = '';
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlineref_supplier', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		$result .= $hookmanager->resPrint;
	}
	if (empty($reshook)) {
		$result .= dol_htmlentitiesbr($object->lines[$i]->ref_supplier);
	}
	return $result;
}

/**
 *	Return line vat rate
 *
 *	@param	SupplierProposal|CommandeFournisseur|FactureFournisseur|Propal|Facture|Commande|ExpenseReport|StockTransfer	$object	Object
 *	@param	int				$i					Current line number
 *  @param  Translate		$outputlangs		Object langs for output
 *  @param	int<0,2>		$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	string
 */
function pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails = 0)
{
	global $conf, $hookmanager, $mysoc;

	$result = '';
	$reshook = 0;
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlinevatrate', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) {
			$result .= $hookmanager->resPrint;
		}
	}
	if (empty($reshook)) {
		if (empty($hidedetails) || $hidedetails > 1) {
			$tmpresult = '';

			$tmpresult .= vatrate($object->lines[$i]->tva_tx, 0, $object->lines[$i]->info_bits, -1);
			if (!getDolGlobalString('MAIN_PDF_MAIN_HIDE_SECOND_TAX')) {
				if ($object->lines[$i]->total_localtax1 != 0) {
					if (preg_replace('/[\s0%]/', '', $tmpresult)) {
						$tmpresult .= '/';
					} else {
						$tmpresult = '';
					}
					$tmpresult .= vatrate((string) abs($object->lines[$i]->localtax1_tx), 0);
				}
			}
			if (!getDolGlobalString('MAIN_PDF_MAIN_HIDE_THIRD_TAX')) {
				if ($object->lines[$i]->total_localtax2 != 0) {
					if (preg_replace('/[\s0%]/', '', $tmpresult)) {
						$tmpresult .= '/';
					} else {
						$tmpresult = '';
					}
					$tmpresult .= vatrate((string) abs($object->lines[$i]->localtax2_tx), 0);
				}
			}
			$tmpresult .= '%';

			$result .= $tmpresult;
		}
	}
	return $result;
}

/**
 *	Return line unit price excluding tax
 *
 *	@param	SupplierProposal|CommandeFournisseur|Propal|Facture|FactureFournisseur|Commande|StockTransfer	$object	Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int<0,2>	$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	string
 */
function pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails = 0)
{
	global $conf, $hookmanager;

	$sign = 1;
	if (isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
		$sign = -1;
	}

	$result = '';
	$reshook = 0;
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlineupexcltax', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) {
			$result .= $hookmanager->resPrint;
		}
	}
	if (empty($reshook)) {
		if (empty($hidedetails) || $hidedetails > 1) {
			$subprice = (isModEnabled("multicurrency") && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice);
			$result .= price($sign * $subprice, 0, $outputlangs);
		}
	}
	return $result;
}

/**
 *	Return line unit price including tax
 *
 *	@param	SupplierProposal|CommandeFournisseur|Propal|Facture|Commande	$object	Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int<0,2>	$hidedetails		Hide value (0 = no,	1 = yes, 2 = just special lines)
 *  @return	string
 */
function pdf_getlineupwithtax($object, $i, $outputlangs, $hidedetails = 0)
{
	global $hookmanager, $conf;

	$sign = 1;
	if (isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
		$sign = -1;
	}

	$result = '';
	$reshook = 0;
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlineupwithtax', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) {
			$result .= $hookmanager->resPrint;
		}
	}
	if (empty($reshook)) {
		if (empty($hidedetails) || $hidedetails > 1) {
			$result .= price($sign * (($object->lines[$i]->subprice) + ($object->lines[$i]->subprice) * ($object->lines[$i]->tva_tx) / 100), 0, $outputlangs);
		}
	}
	return $result;
}

/**
 *	Return line quantity
 *
 *	@param	Delivery|Asset|Commande|Facture|CommandeFournisseur|FactureFournisseur|SupplierProposal|Propal|StockTransfer|MyObject	$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int<0,2>	$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @return	string
 */
function pdf_getlineqty($object, $i, $outputlangs, $hidedetails = 0)
{
	global $hookmanager;

	$result = '';
	$reshook = 0;
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlineqty', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) {
			$result = $hookmanager->resPrint;
		}
	}
	if (empty($reshook)) {
		if ($object->lines[$i]->special_code == 3) {
			return '';
		}
		if (empty($hidedetails) || $hidedetails > 1) {
			$result .= $object->lines[$i]->qty;
		}
	}
	return $result;
}

/**
 *	Return line quantity asked
 *
 *	@param	Delivery	$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int<0,2>	$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	string
 */
function pdf_getlineqty_asked($object, $i, $outputlangs, $hidedetails = 0)
{
	global $hookmanager;

	$reshook = 0;
	$result = '';
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlineqty_asked', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) {
			$result .= $hookmanager->resPrint;
		}
	}
	if (empty($reshook)) {
		if ($object->lines[$i]->special_code == 3) {
			return '';
		}
		if (empty($hidedetails) || $hidedetails > 1) {
			$result .= $object->lines[$i]->qty_asked;
		}
	}
	return $result;
}

/**
 *	Return line quantity shipped
 *
 *	@param	Delivery	$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int<0,2>	$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	string
 */
function pdf_getlineqty_shipped($object, $i, $outputlangs, $hidedetails = 0)
{
	global $hookmanager;

	$reshook = 0;
	$result = '';
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlineqty_shipped', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) {
			$result .= $hookmanager->resPrint;
		}
	}
	if (empty($reshook)) {
		if ($object->lines[$i]->special_code == 3) {
			return '';
		}
		if (empty($hidedetails) || $hidedetails > 1) {
			$result .= $object->lines[$i]->qty_shipped;
		}
	}
	return $result;
}

/**
 *	Return line keep to ship quantity
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int<0,2>	$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	string
 */
function pdf_getlineqty_keeptoship($object, $i, $outputlangs, $hidedetails = 0)
{
	global $hookmanager;

	$reshook = 0;
	$result = '';
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlineqty_keeptoship', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) {
			$result .= $hookmanager->resPrint;
		}
	}
	if (empty($reshook)) {
		if ($object->lines[$i]->special_code == 3) {
			return '';
		}
		if (empty($hidedetails) || $hidedetails > 1) {
			$result .= ($object->lines[$i]->qty_asked - $object->lines[$i]->qty_shipped);
		}
	}
	return $result;
}

/**
 *	Return line unit
 *
 *	@param	SupplierProposal|CommandeFournisseur|Propal|Facture|FactureFournisseur|Commande|StockTransfer	$object	Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int<0,2>	$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @return	string							Value for unit cell
 */
function pdf_getlineunit($object, $i, $outputlangs, $hidedetails = 0)
{
	global $hookmanager, $langs;

	$reshook = 0;
	$result = '';
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array(
			'i' => $i,
			'outputlangs' => $outputlangs,
			'hidedetails' => $hidedetails,
			'special_code' => $special_code
		);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlineunit', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) {
			$result .= $hookmanager->resPrint;
		}
	}
	if (empty($reshook)) {
		if (empty($hidedetails) || $hidedetails > 1) {
			$result .= $langs->transnoentitiesnoconv($object->lines[$i]->getLabelOfUnit('short'));
		}
	}
	return $result;
}


/**
 *	Return line remise percent
 *
 *	@param	SupplierProposal|CommandeFournisseur|Propal|Facture|FactureFournisseur|Commande|StockTransfer	$object	Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int<0,2>	$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	string
 */
function pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails = 0)
{
	global $hookmanager;

	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	$reshook = 0;
	$result = '';
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlineremisepercent', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) {
			$result .= $hookmanager->resPrint;
		}
	}
	if (empty($reshook)) {
		if ($object->lines[$i]->special_code == 3) {
			return '';
		}
		if (empty($hidedetails) || $hidedetails > 1) {
			$result .= dol_print_reduction($object->lines[$i]->remise_percent, $outputlangs);
		}
	}
	return $result;
}

/**
 * Return line percent
 *
 * @param Facture		$object             Object
 * @param int			$i                  Current line number
 * @param Translate		$outputlangs        Object langs for output
 * @param int<0,2>		$hidedetails        Hide details (0=no, 1=yes, 2=just special lines)
 * @param ?HookManager	$hookmanager        Hook manager instance
 * @return string
 */
function pdf_getlineprogress($object, $i, $outputlangs, $hidedetails = 0, $hookmanager = null)
{
	if (empty($hookmanager)) {
		global $hookmanager;
	}
	global $conf;

	$reshook = 0;
	$result = '';
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlineprogress', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) {
			return $hookmanager->resPrint;
		}
	}
	if (empty($reshook)) {
		if ($object->lines[$i]->special_code == 3) {
			return '';
		}
		if (empty($hidedetails) || $hidedetails > 1) {
			if (getDolGlobalString('SITUATION_DISPLAY_DIFF_ON_PDF')) {
				$prev_progress = 0;
				if (method_exists($object->lines[$i], 'get_prev_progress')) {
					$prev_progress = $object->lines[$i]->get_prev_progress($object->id);
				}
				$result = round($object->lines[$i]->situation_percent - $prev_progress, 1).'%';
			} else {
				$result = round($object->lines[$i]->situation_percent, 1).'%';
			}
		}
	}
	return $result;
}

/**
 *	Return line total excluding tax
 *
 *	@param	Commande|Facture|Propal|FactureFournisseur|CommandeFournisseur|SupplierProposal	$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int<0,2>	$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	string							Return total of line excl tax
 */
function pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails = 0)
{
	global $conf, $hookmanager;

	$sign = 1;
	if (isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
		$sign = -1;
	}

	$reshook = 0;
	$result = '';
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code, 'sign' => $sign);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlinetotalexcltax', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) {
			$result .= $hookmanager->resPrint;
		}
	}
	if (empty($reshook)) {
		if (!empty($object->lines[$i]) && $object->lines[$i]->special_code == 3) {
			$result .= $outputlangs->transnoentities("Option");
		} elseif (empty($hidedetails) || $hidedetails > 1) {
			$total_ht = (isModEnabled("multicurrency") && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht);
			if (!empty($object->lines[$i]->situation_percent) && $object->lines[$i]->situation_percent > 0) {
				// TODO Remove this. The total should be saved correctly in database instead of being modified here.
				$prev_progress = 0;
				$progress = 1;
				if (method_exists($object->lines[$i], 'get_prev_progress')) {
					$prev_progress = $object->lines[$i]->get_prev_progress($object->id);
					$progress = ($object->lines[$i]->situation_percent - $prev_progress) / 100;
				}
				$result .= price($sign * ($total_ht / ($object->lines[$i]->situation_percent / 100)) * $progress, 0, $outputlangs);
			} else {
				$result .= price($sign * $total_ht, 0, $outputlangs);
			}
		}
	}
	return $result;
}

/**
 *	Return line total including tax
 *
 *	@param	Commande|Facture|Propal|FactureFournisseur|CommandeFournisseur|SupplierProposal	$object				Object
 *	@param	int			$i					Current line number
 *  @param 	Translate	$outputlangs		Object langs for output
 *  @param	int<0,2>	$hidedetails		Hide value (0 = no, 1 = yes, 2 = just special lines)
 *  @return	string							Return total of line incl tax
 */
function pdf_getlinetotalwithtax($object, $i, $outputlangs, $hidedetails = 0)
{
	global $hookmanager, $conf;

	$sign = 1;
	if (isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
		$sign = -1;
	}

	$reshook = 0;
	$result = '';
	//if (is_object($hookmanager) && ( (isset($object->lines[$i]->product_type) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || !empty($object->lines[$i]->fk_parent_line) ) )
	if (is_object($hookmanager)) {   // Old code is commented on preceding line. Reproduce this test in the pdf_xxx function if you don't want your hook to run
		$special_code = empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line) && $object->lines[$i]->fk_parent_line > 0) {
			$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		}
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlinetotalwithtax', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) {
			$result .= $hookmanager->resPrint;
		}
	}
	if (empty($reshook)) {
		if ($object->lines[$i]->special_code == 3) {
			$result .= $outputlangs->transnoentities("Option");
		} elseif (empty($hidedetails) || $hidedetails > 1) {
			$total_ttc = (isModEnabled("multicurrency") && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_total_ttc : $object->lines[$i]->total_ttc);
			if (isset($object->lines[$i]->situation_percent) && $object->lines[$i]->situation_percent > 0) {
				// TODO Remove this. The total should be saved correctly in database instead of being modified here.
				$prev_progress = 0;
				$progress = 1;
				if (method_exists($object->lines[$i], 'get_prev_progress')) {
					$prev_progress = $object->lines[$i]->get_prev_progress($object->id);
					$progress = ($object->lines[$i]->situation_percent - $prev_progress) / 100;
				}
				$result .= price($sign * ($total_ttc / ($object->lines[$i]->situation_percent / 100)) * $progress, 0, $outputlangs);
			} else {
				$result .= price($sign * $total_ttc, 0, $outputlangs);
			}
		}
	}
	return $result;
}

/**
 * 	Return linked objects to use for document generation.
 *  Warning: To save space, this function returns only one link per link type (all links are concated on same record string). This function is used by pdf_writeLinkedObjects
 *
 * 	@param	CommonObject	$object			Object
 * 	@param	Translate		$outputlangs	Object lang for output
 * 	@return	array<string,array<string,null|int|float|string>>	Linked objects
 */
function pdf_getLinkedObjects(&$object, $outputlangs)
{
	global $db, $hookmanager;

	$linkedobjects = array();

	$object->fetchObjectLinked();

	foreach ($object->linkedObjects as $objecttype => $objects) {
		if ($objecttype == 'facture') {
			// For invoice, we don't want to have a reference line on document. Image we are using recurring invoice, we will have a line longer than document width.
		} elseif ($objecttype == 'propal' || $objecttype == 'supplier_proposal') {
			'@phan-var-force array<Propal|SupplierProposal> $objects';
			$outputlangs->load('propal');

			foreach ($objects as $elementobject) {
				$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefProposal");
				$linkedobjects[$objecttype]['ref_value'] = $outputlangs->transnoentities($elementobject->ref);
				$linkedobjects[$objecttype]['date_title'] = $outputlangs->transnoentities("DatePropal");
				$linkedobjects[$objecttype]['date_value'] = dol_print_date($elementobject->date, 'day', '', $outputlangs);
			}
		} elseif ($objecttype == 'commande' || $objecttype == 'supplier_order') {
			'@phan-var-force array<Commande|CommandeFournisseur> $objects';
			$outputlangs->load('orders');

			if (count($objects) == 1) {
				$elementobject = array_shift($objects);
				$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefOrder");
				$linkedobjects[$objecttype]['ref_value'] = $outputlangs->transnoentities($elementobject->ref).(!empty($elementobject->ref_client) ? ' ('.$elementobject->ref_client.')' : '').(!empty($elementobject->ref_supplier) ? ' ('.$elementobject->ref_supplier.')' : '');
				$linkedobjects[$objecttype]['date_title'] = $outputlangs->transnoentities("OrderDate");
				$linkedobjects[$objecttype]['date_value'] = dol_print_date($elementobject->date, 'day', '', $outputlangs);
			}
		} elseif ($objecttype == 'contrat') {
			'@phan-var-force Contrat[] $objects';
			$outputlangs->load('contracts');
			foreach ($objects as $elementobject) {
				$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefContract");
				$linkedobjects[$objecttype]['ref_value'] = $outputlangs->transnoentities($elementobject->ref);
				$linkedobjects[$objecttype]['date_title'] = $outputlangs->transnoentities("DateContract");
				$linkedobjects[$objecttype]['date_value'] = dol_print_date($elementobject->date_contrat, 'day', '', $outputlangs);
			}
		} elseif ($objecttype == 'fichinter') {
			'@phan-var-force Fichinter[] $objects';
			$outputlangs->load('interventions');
			foreach ($objects as $elementobject) {
				$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("InterRef");
				$linkedobjects[$objecttype]['ref_value'] = $outputlangs->transnoentities($elementobject->ref);
				$linkedobjects[$objecttype]['date_title'] = $outputlangs->transnoentities("InterDate");
				$linkedobjects[$objecttype]['date_value'] = dol_print_date($elementobject->datec, 'day', '', $outputlangs);
			}
		} elseif ($objecttype == 'shipping') {
			'@phan-var-force Expedition[] $objects';
			$outputlangs->loadLangs(array("orders", "sendings"));

			if (count($objects) > 1) {
				$order = null;
				if (empty($object->linkedObjects['commande']) && $object->element != 'commande') {
					$object->note_public = dol_concatdesc($object->note_public, $outputlangs->transnoentities("RefOrder").' / '.$outputlangs->transnoentities("RefSending").' :');
				} else {
					$object->note_public = dol_concatdesc($object->note_public, $outputlangs->transnoentities("RefSending").' :');
				}
				// We concat this record info into fields xxx_value. title is overwrote.
				foreach ($objects as $elementobject) {
					if (empty($object->linkedObjects['commande']) && $object->element != 'commande') {    // There is not already a link to order and object is not the order, so we show also info with order
						$elementobject->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);
						if (!empty($elementobject->linkedObjectsIds['commande'])) {
							include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
							$order = new Commande($db);
							$ret = $order->fetch(reset($elementobject->linkedObjectsIds['commande']));
							if ($ret < 1) {
								$order = null;
							}
						}
					}

					if (! is_object($order)) {
						$object->note_public = dol_concatdesc($object->note_public, $outputlangs->transnoentities($elementobject->ref));
					} else {
						$object->note_public = dol_concatdesc($object->note_public, $outputlangs->convToOutputCharset($order->ref).($order->ref_client ? ' ('.$order->ref_client.')' : ''));
						$object->note_public = dol_concatdesc($object->note_public, ' / '.$outputlangs->transnoentities($elementobject->ref));
					}
				}
			} elseif (count($objects) == 1) {
				$elementobject = array_shift($objects);
				$order = null;
				// We concat this record info into fields xxx_value. title is overwrote.
				if (empty($object->linkedObjects['commande']) && $object->element != 'commande') {    // There is not already a link to order and object is not the order, so we show also info with order
					$elementobject->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);
					if (!empty($elementobject->linkedObjectsIds['commande'])) {
						include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
						$order = new Commande($db);
						$ret = $order->fetch(reset($elementobject->linkedObjectsIds['commande']));
						if ($ret < 1) {
							$order = null;
						}
					}
				}

				if (! is_object($order)) {
					$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefSending");
					if (empty($linkedobjects[$objecttype]['ref_value'])) {
						$linkedobjects[$objecttype]['ref_value'] = '';
					} else {
						$linkedobjects[$objecttype]['ref_value'] .= ' / ';
					}
					$linkedobjects[$objecttype]['ref_value'] .= $outputlangs->transnoentities($elementobject->ref);
					$linkedobjects[$objecttype]['date_value'] = dol_print_date(empty($elementobject->date_shipping) ? $elementobject->date_delivery : $elementobject->date_shipping, 'day', '', $outputlangs);
				} else {
					$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefOrder").' / '.$outputlangs->transnoentities("RefSending");
					if (empty($linkedobjects[$objecttype]['ref_value'])) {
						$linkedobjects[$objecttype]['ref_value'] = $outputlangs->convToOutputCharset($order->ref).($order->ref_client ? ' ('.$order->ref_client.')' : '');
					}
					$linkedobjects[$objecttype]['ref_value'] .= ' / '.$outputlangs->transnoentities($elementobject->ref);
					$linkedobjects[$objecttype]['date_value'] = dol_print_date(empty($elementobject->date_shipping) ? $elementobject->date_delivery : $elementobject->date_shipping, 'day', '', $outputlangs);
				}
			}
		}
	}

	// For add external linked objects
	if (is_object($hookmanager)) {
		$parameters = array('linkedobjects' => $linkedobjects, 'outputlangs' => $outputlangs);
		$action = '';
		$hookmanager->executeHooks('pdf_getLinkedObjects', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		if (!empty($hookmanager->resArray)) {
			$linkedobjects = $hookmanager->resArray;
		}
	}

	return $linkedobjects;
}

/**
 * Return dimensions to use for images onto PDF checking that width and height are not higher than
 * maximum (20x32 by default).
 *
 * @param	string		$realpath		Full path to photo file to use
 * @return	array{width:int,height:int}	Height and width to use to output image (in pdf user unit, so mm)
 */
function pdf_getSizeForImage($realpath)
{
	global $conf;

	$maxwidth = getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH', 20);
	$maxheight = getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_HEIGHT', 32);
	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	$tmp = dol_getImageSize($realpath);
	$width = 0;
	$height = 0;
	if ($tmp['height']) {
		$width = (int) round($maxheight * $tmp['width'] / $tmp['height']); // I try to use maxheight
		if ($width > $maxwidth) {	// Pb with maxheight, so i use maxwidth
			$width = $maxwidth;
			$height = (int) round($maxwidth * $tmp['height'] / $tmp['width']);
		} else { // No pb with maxheight
			$height = $maxheight;
		}
	}
	return array('width' => $width, 'height' => $height);
}

/**
 *	Return line total amount discount
 *
 *	@param	Facture			$object				Object
 *	@param	int				$i					Current line number
 *  @param  Translate		$outputlangs		Object langs for output
 *  @param	int<0,2>		$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	float|string						Return total of line excl tax
 */
function pdfGetLineTotalDiscountAmount($object, $i, $outputlangs, $hidedetails = 0)
{
	global $conf, $hookmanager;

	$sign = 1;
	if (isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
		$sign = -1;
	}
	if ($object->lines[$i]->special_code == 3) {
		return $outputlangs->transnoentities("Option");
	} else {
		if (is_object($hookmanager)) {
			$special_code = $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line)) {
				$special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			}

			$parameters = array(
				'i' => $i,
				'outputlangs' => $outputlangs,
				'hidedetails' => $hidedetails,
				'special_code' => $special_code
			);

			$action = '';

			if ($hookmanager->executeHooks('getlinetotalremise', $parameters, $object, $action) > 0) {	// Note that $action and $object may have been modified by some hooks
				if (isset($hookmanager->resArray['linetotalremise'])) {
					return $hookmanager->resArray['linetotalremise'];
				} else {
					return (float) $hookmanager->resPrint;	// For backward compatibility
				}
			}
		}

		if (empty($hidedetails) || $hidedetails > 1) {
			return $sign * (($object->lines[$i]->subprice * (float) $object->lines[$i]->qty) - $object->lines[$i]->total_ht);
		}
	}
	return 0;
}
