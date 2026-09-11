<?php
/* Copyright (C) 2026	Jose MARTINEZ	<jose.martinez@pichinov.com>
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
 */

/**
 *	\file       htdocs/core/modules/inventory/doc/pdf_standard_inventory.modules.php
 *	\ingroup    stock
 *	\brief      File of class to build PDF document for an inventory (count sheet / result sheet)
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/inventory/modules_inventory.php';
require_once DOL_DOCUMENT_ROOT.'/product/inventory/class/inventory.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Class to build inventory documents with model standard_inventory.
 *
 *	While the inventory is not yet recorded, the counted and gap columns are
 *	printed empty so the sheet can be used as a physical count sheet.
 */
class pdf_standard_inventory extends ModelePDFInventory
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
	 * @var int 	Save the name of generated file as the main doc when generating a doc with this template
	 */
	public $update_main_doc_field;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';

	/**
	 * @var Societe Issuer object
	 */
	public $emetteur;

	/**
	 * @var float Column positions
	 */
	public $posxproduct;
	/**
	 * @var float Column positions
	 */
	public $posxlabel;
	/**
	 * @var float Column positions
	 */
	public $posxwarehouse;
	/**
	 * @var float Column positions
	 */
	public $posxbatch;
	/**
	 * @var float Column positions
	 */
	public $posxqtystock;
	/**
	 * @var float Column positions
	 */
	public $posxqtyview;
	/**
	 * @var float Column positions
	 */
	public $posxgap;
	/**
	 * @var float Column positions
	 */
	public $posxend;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "stocks", "products"));

		$this->db = $db;
		$this->name = "standard_inventory";
		$this->description = $langs->trans("InventorySheetPdfModelDesc");
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

		// Dimension page
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
		$this->marge_droite = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
		$this->marge_haute = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
		$this->marge_basse = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);

		$this->option_logo = 1; // Display logo
		$this->option_multilang = 1; // Available in several languages
		$this->option_freetext = 1; // Support add of a personalised text

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Inventory	$object				Object Inventory to generate
	 *  @param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int<0,1>	$hidedetails		Do not show line details
	 *  @param		int<0,1>	$hidedesc			Do not show desc
	 *  @param		int<0,1>	$hideref			Do not show ref
	 *  @return		int<-1,1>						1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $hookmanager;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "dict", "companies", "stocks", "products"));

		$isspecimen = empty($object->id);

		if (empty($conf->stock->multidir_output[$isspecimen ? $conf->entity : ($object->entity ? $object->entity : $conf->entity)])) {
			$this->error = 'Error: no output dir defined for module stock';
			return 0;
		}
		$basedir = $conf->stock->multidir_output[$isspecimen ? $conf->entity : ($object->entity ? $object->entity : $conf->entity)].'/inventory';

		if ($isspecimen) {
			$dir = $basedir;
			$file = $dir.'/SPECIMEN.pdf';
		} else {
			$objectref = dol_sanitizeFileName((string) $object->ref);
			$dir = $basedir.'/'.$objectref;
			$file = $dir.'/'.$objectref.'.pdf';
		}

		if (!file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		}

		// Load the lines of the inventory
		$lines = array();
		if ($isspecimen) {
			for ($i = 1; $i <= 5; $i++) {
				$line = new stdClass();
				$line->product_ref = 'PROD'.sprintf('%04d', $i);
				$line->product_label = 'Product '.$i;
				$line->warehouse_ref = 'WH-A';
				$line->batch = '';
				$line->qty_stock = 10 * $i;
				$line->qty_view = 10 * $i - ($i % 2);
				$line->pmp = 2.5 * $i;
				$lines[] = $line;
			}
			$recorded = false;
		} else {
			$recorded = ((int) $object->status >= Inventory::STATUS_RECORDED);
			$sql = "SELECT id.rowid, id.qty_stock, id.qty_view, id.qty_regulated, id.batch, id.pmp_real, id.pmp_expected,";
			$sql .= " p.ref as product_ref, p.label as product_label, e.ref as warehouse_ref";
			$sql .= " FROM ".MAIN_DB_PREFIX."inventorydet as id";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = id.fk_product";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as e ON e.rowid = id.fk_warehouse";
			$sql .= " WHERE id.fk_inventory = ".((int) $object->id);
			$sql .= " ORDER BY e.ref, p.ref";
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				return 0;
			}
			while ($obj = $this->db->fetch_object($resql)) {
				$line = new stdClass();
				$line->product_ref = (string) $obj->product_ref;
				$line->product_label = (string) $obj->product_label;
				$line->warehouse_ref = (string) $obj->warehouse_ref;
				$line->batch = (string) $obj->batch;
				$line->qty_stock = $obj->qty_stock;
				$line->qty_view = $recorded ? ($obj->qty_view !== null ? $obj->qty_view : ($obj->qty_stock + $obj->qty_regulated)) : $obj->qty_view;
				$line->pmp = ($obj->pmp_real !== null && (float) $obj->pmp_real != 0) ? $obj->pmp_real : $obj->pmp_expected;
				$lines[] = $line;
			}
		}

		// Create pdf instance
		$pdf = pdf_getInstance($this->format);
		$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
		$pdf->SetAutoPageBreak(true, 0);

		$heightforinfotot = 30; // Height reserved to output the totals table
		$heightforsignature = ($isspecimen || !$recorded) ? 25 : 0; // Height reserved for the signature block on count sheets
		$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5);
		$heightforfooter = $this->marge_basse + 8;

		if (class_exists('TCPDF')) {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont(pdf_getPDFFont($outputlangs));

		$pdf->Open();
		$pagenb = 0;
		$pdf->SetDrawColor(128, 128, 128);

		$pdf->SetTitle($outputlangs->convToOutputCharset($isspecimen ? 'SPECIMEN' : $object->ref));
		$pdf->SetSubject($outputlangs->transnoentities("Inventory"));
		$pdf->SetCreator("Dolibarr ".DOL_VERSION);
		$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		$pdf->SetKeyWords($outputlangs->convToOutputCharset($isspecimen ? 'SPECIMEN' : $object->ref)." ".$outputlangs->transnoentities("Inventory"));
		if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
			$pdf->SetCompression(false);
		}

		// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
		$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

		// Columns (from left)
		$this->posxproduct = $this->marge_gauche;
		$this->posxlabel = $this->marge_gauche + 30;
		$this->posxwarehouse = $this->page_largeur - $this->marge_droite - 92;
		$this->posxbatch = $this->page_largeur - $this->marge_droite - 70;
		$this->posxqtystock = $this->page_largeur - $this->marge_droite - 50;
		$this->posxqtyview = $this->page_largeur - $this->marge_droite - 34;
		$this->posxgap = $this->page_largeur - $this->marge_droite - 18;
		$this->posxend = $this->page_largeur - $this->marge_droite;

		// New page
		$pdf->AddPage();
		$pagenb++;
		$top_shift = $this->pageHead($pdf, $object, 1, $outputlangs, $recorded, $isspecimen);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->MultiCell(0, 3, ''); // Set interline to 3
		$pdf->SetTextColor(0, 0, 0);

		$tab_top = 42 + $top_shift;
		$tab_top_newpage = 10;

		// Table header
		$this->tableHeader($pdf, $tab_top, $outputlangs, $recorded);
		$nexY = $tab_top + 7;

		$nblines = count($lines);
		$totalgap = 0;
		$totalgapvalue = 0;
		$nbgap = 0;

		for ($i = 0; $i < $nblines; $i++) {
			$line = $lines[$i];
			$curY = $nexY;
			$pdf->SetFont('', '', $default_font_size - 2);

			// Page break management
			if ($curY > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot + $heightforsignature))) {
				$this->pageFoot($pdf, $object, $outputlangs, 1);
				$pdf->AddPage();
				$pagenb++;
				$this->tableHeader($pdf, $tab_top_newpage, $outputlangs, $recorded);
				$curY = $tab_top_newpage + 7;
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetTextColor(0, 0, 0);
			}

			// Product ref
			$pdf->SetXY($this->posxproduct, $curY);
			$pdf->MultiCell($this->posxlabel - $this->posxproduct - 1, 3, $outputlangs->convToOutputCharset($line->product_ref), 0, 'L');
			// Label
			$pdf->SetXY($this->posxlabel, $curY);
			$pdf->MultiCell($this->posxwarehouse - $this->posxlabel - 1, 3, $outputlangs->convToOutputCharset(dol_trunc($line->product_label, 45)), 0, 'L');
			// Warehouse
			$pdf->SetXY($this->posxwarehouse, $curY);
			$pdf->MultiCell($this->posxbatch - $this->posxwarehouse - 1, 3, $outputlangs->convToOutputCharset(dol_trunc($line->warehouse_ref, 12)), 0, 'L');
			// Batch
			$pdf->SetXY($this->posxbatch, $curY);
			$pdf->MultiCell($this->posxqtystock - $this->posxbatch - 1, 3, $outputlangs->convToOutputCharset(dol_trunc($line->batch, 10)), 0, 'L');
			// Expected qty
			$pdf->SetXY($this->posxqtystock, $curY);
			$pdf->MultiCell($this->posxqtyview - $this->posxqtystock - 1, 3, ($line->qty_stock !== null && $line->qty_stock !== '') ? price2num($line->qty_stock, 'MS') : '', 0, 'R');

			if ($recorded) {
				$gap = (float) $line->qty_view - (float) $line->qty_stock;
				// Counted qty
				$pdf->SetXY($this->posxqtyview, $curY);
				$pdf->MultiCell($this->posxgap - $this->posxqtyview - 1, 3, price2num($line->qty_view, 'MS'), 0, 'R');
				// Gap
				if ($gap != 0) {
					$pdf->SetTextColor(150, 0, 0);
					$nbgap++;
				}
				$pdf->SetXY($this->posxgap, $curY);
				$pdf->MultiCell($this->posxend - $this->posxgap, 3, ($gap > 0 ? '+' : '').price2num($gap, 'MS'), 0, 'R');
				$pdf->SetTextColor(0, 0, 0);
				$totalgap += $gap;
				$totalgapvalue += $gap * (float) $line->pmp;
			} else {
				// Count sheet: empty writable cells
				$pdf->SetXY($this->posxqtyview, $curY - 1);
				$pdf->MultiCell($this->posxgap - $this->posxqtyview - 1, 5, '', 1, 'R');
				$pdf->SetXY($this->posxgap, $curY - 1);
				$pdf->MultiCell($this->posxend - $this->posxgap, 5, '', 1, 'R');
			}

			$nexY = $curY + ($recorded ? 5 : 6);
		}

		// Totals
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$curY = $nexY + 4;
		if ($curY > ($this->page_hauteur - ($heightforfooter + $heightforinfotot + $heightforsignature))) {
			$this->pageFoot($pdf, $object, $outputlangs, 1);
			$pdf->AddPage();
			$pagenb++;
			$curY = $tab_top_newpage;
		}
		$pdf->SetXY($this->posxproduct, $curY);
		$pdf->MultiCell(60, 4, $outputlangs->transnoentities("NbOfLines").' : '.$nblines, 0, 'L');
		if ($recorded) {
			$pdf->SetXY($this->posxproduct, $curY + 5);
			$pdf->MultiCell(120, 4, $outputlangs->transnoentities("Difference").' : '.($totalgap > 0 ? '+' : '').price2num($totalgap, 'MS').' ('.$nbgap.' '.$outputlangs->transnoentities("Lines").')', 0, 'L');
			$pdf->SetXY($this->posxproduct, $curY + 10);
			$pdf->MultiCell(120, 4, $outputlangs->transnoentities("Valuation").' : '.price(price2num($totalgapvalue, 'MT'), 0, $outputlangs, 1, -1, -1, $conf->currency), 0, 'L');
		}

		// Signature block on count sheets
		if (!$recorded) {
			$posy = $curY + 8;
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($this->posxproduct, $posy);
			$pdf->MultiCell(80, 4, $outputlangs->transnoentities("CountedBy").' : ____________________', 0, 'L');
			$pdf->SetXY($this->page_largeur - $this->marge_droite - 80, $posy);
			$pdf->MultiCell(80, 4, $outputlangs->transnoentities("DateAndSignature").' :', 0, 'L');
			$pdf->Rect($this->page_largeur - $this->marge_droite - 80, $posy + 5, 80, 15);
		}

		// Page foot
		$this->pageFoot($pdf, $object, $outputlangs, 0);
		if (method_exists($pdf, 'AliasNbPages')) {
			$pdf->AliasNbPages();  // @phan-suppress-current-line PhanUndeclaredMethod
		}

		$pdf->Close();

		$pdf->Output($file, 'F');

		// Add pdfgeneration hook
		$hookmanager->initHooks(array('pdfgeneration'));
		$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
		global $action;
		$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) {
			$this->error = $hookmanager->error;
			$this->errors = $hookmanager->errors;
		}

		dolChmod($file);

		$this->result = array('fullpath' => $file);

		return 1; // No error
	}

	/**
	 *  Show table header
	 *
	 *  @param	TCPDF|TCPDI	$pdf			Object PDF
	 *  @param	float		$tab_top		Top position of table
	 *  @param	Translate	$outputlangs	Langs object
	 *  @param	bool		$recorded		Inventory is recorded (gap columns filled)
	 *  @return	void
	 */
	protected function tableHeader(&$pdf, $tab_top, $outputlangs, $recorded)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetDrawColor(128, 128, 128);
		$pdf->line($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite, $tab_top);

		$posy = $tab_top + 1;
		$pdf->SetXY($this->posxproduct, $posy);
		$pdf->MultiCell($this->posxlabel - $this->posxproduct - 1, 3, $outputlangs->transnoentities("ProductRef"), 0, 'L');
		$pdf->SetXY($this->posxlabel, $posy);
		$pdf->MultiCell($this->posxwarehouse - $this->posxlabel - 1, 3, $outputlangs->transnoentities("Label"), 0, 'L');
		$pdf->SetXY($this->posxwarehouse, $posy);
		$pdf->MultiCell($this->posxbatch - $this->posxwarehouse - 1, 3, $outputlangs->transnoentities("Warehouse"), 0, 'L');
		$pdf->SetXY($this->posxbatch, $posy);
		$pdf->MultiCell($this->posxqtystock - $this->posxbatch - 1, 3, $outputlangs->transnoentities("Batch"), 0, 'L');
		$pdf->SetXY($this->posxqtystock, $posy);
		$pdf->MultiCell($this->posxqtyview - $this->posxqtystock - 1, 3, $outputlangs->transnoentities("ExpectedQty"), 0, 'R');
		$pdf->SetXY($this->posxqtyview, $posy);
		$pdf->MultiCell($this->posxgap - $this->posxqtyview - 1, 3, $outputlangs->transnoentities("RealQty"), 0, 'R');
		$pdf->SetXY($this->posxgap, $posy);
		$pdf->MultiCell($this->posxend - $this->posxgap, 3, $outputlangs->transnoentities("Difference"), 0, 'R');

		$pdf->line($this->marge_gauche, $tab_top + 6, $this->page_largeur - $this->marge_droite, $tab_top + 6);
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF|TCPDI	$pdf			Object PDF
	 *  @param	Inventory	$object			Object to show
	 *  @param	int<0,1>	$showaddress	0=no, 1=yes
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @param	bool		$recorded		Inventory is recorded
	 *  @param	bool		$isspecimen		Specimen mode
	 *  @return	float|int					Top shift of linked object lines
	 */
	protected function pageHead(&$pdf, $object, $showaddress, $outputlangs, $recorded, $isspecimen)
	{
		global $conf, $langs, $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - 100;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		$logo = $conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
		if ($mysoc->logo) {
			if (is_readable($logo)) {
				$height = pdf_getHeightForLogo($logo);
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->SetTextColor(0, 0, 60);
			}
		} else {
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($mysoc->name), 0, 'L');
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title = $outputlangs->transnoentities("Inventory");
		if (!$recorded) {
			$title .= ' - '.$outputlangs->transnoentities("Draft");
		}
		$pdf->MultiCell(100, 4, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size);
		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : ".($isspecimen ? 'SPECIMEN' : $outputlangs->convToOutputCharset($object->ref)), '', 'R');

		$pdf->SetFont('', '', $default_font_size - 1);

		if (!$isspecimen && !empty($object->date_inventory)) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Date")." : ".dol_print_date($this->db->jdate($object->date_inventory), "day", false, $outputlangs, true), '', 'R');
		}
		if (!$isspecimen && !empty($object->title)) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($object->title), '', 'R');
		}

		$pdf->SetTextColor(0, 0, 0);

		return 0;
	}

	/**
	 *  Show footer of page. Need this->emetteur object
	 *
	 *  @param	TCPDF|TCPDI	$pdf				PDF
	 *  @param	Inventory	$object				Object to show
	 *  @param	Translate	$outputlangs		Object lang for output
	 *  @param	int<0,1>	$hidefreetext		1=Hide free text
	 *  @return	int								Return height of bottom margin including footer text
	 */
	protected function pageFoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);
		return pdf_pagefoot($pdf, $outputlangs, 'INVENTORY_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}
}
