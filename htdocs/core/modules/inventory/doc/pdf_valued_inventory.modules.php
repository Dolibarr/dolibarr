<?php
/* Copyright (C) 2026	Jose Martinez			<jose.martinez@pichinov.com>
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
 *	\file       htdocs/core/modules/inventory/doc/pdf_valued_inventory.modules.php
 *	\ingroup    stock
 *	\brief      File of class to build the valued inventory PDF (stock value for the accountant)
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/inventory/doc/pdf_standard_inventory.modules.php';


/**
 *	Class to build inventory documents with model valued_inventory.
 *
 *	One line per counted product with its quantity, its unit value (weighted average cost) and its total value,
 *	and the totals (number of products, quantity, value) at the top of the first page and at the end.
 *	Quantity of a line: counted quantity; when a line was not counted, its stock, which recording keeps unchanged.
 *	Unit value: cost entered on the inventory line, else the expected cost stored on the line, else the current
 *	weighted average cost of the product. Lines with a quantity of zero are not listed (they are worth nothing).
 */
class pdf_valued_inventory extends pdf_standard_inventory
{
	/**
	 * @var float Column positions
	 */
	public $posxqty;
	/**
	 * @var float Column positions
	 */
	public $posxunitvalue;
	/**
	 * @var float Column positions
	 */
	public $posxtotalvalue;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs;

		parent::__construct($db);

		$this->name = "valued_inventory";
		$this->description = $langs->trans("InventoryValuedPdfModelDesc");
		$this->titlekey = "InventoryValued";
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
		global $user, $langs, $conf, $hookmanager;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "dict", "companies", "stocks", "products", "other"));

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

		// Load the lines of the inventory with their quantity and unit value
		$lines = array();
		$nbzero = 0;
		if ($isspecimen) {
			for ($i = 1; $i <= 5; $i++) {
				$line = new stdClass();
				$line->product_ref = 'PROD'.sprintf('%04d', $i);
				$line->product_label = 'Product '.$i;
				$line->warehouse_ref = 'WH-A';
				$line->batch = '';
				$line->qty = 10 * $i;
				$line->unitvalue = 2.5 * $i;
				$lines[] = $line;
			}
			$recorded = false;
		} else {
			$recorded = ((int) $object->status >= Inventory::STATUS_RECORDED);
			$sql = "SELECT id.qty_stock, id.qty_view, id.qty_regulated, id.batch, id.pmp_real, id.pmp_expected,";
			$sql .= " p.ref as product_ref, p.label as product_label, p.pmp as product_pmp, e.ref as warehouse_ref";
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
				// Counted quantity; a line not counted keeps its stock when the inventory is recorded
				if ($obj->qty_view !== null) {
					$qty = (float) $obj->qty_view;
				} elseif ($recorded) {
					$qty = (float) $obj->qty_stock + (float) $obj->qty_regulated;
				} else {
					$qty = (float) $obj->qty_stock;
				}
				if ($qty == 0) {
					$nbzero++;
					continue;
				}
				if ($obj->pmp_real !== null && (float) $obj->pmp_real != 0) {
					$unitvalue = (float) $obj->pmp_real;
				} elseif ($obj->pmp_expected !== null && (float) $obj->pmp_expected != 0) {
					$unitvalue = (float) $obj->pmp_expected;
				} else {
					$unitvalue = (float) $obj->product_pmp;
				}
				$line = new stdClass();
				$line->product_ref = (string) $obj->product_ref;
				$line->product_label = dol_string_nohtmltag((string) $obj->product_label, 1);
				$line->warehouse_ref = (string) $obj->warehouse_ref;
				$line->batch = (string) $obj->batch;
				$line->qty = $qty;
				$line->unitvalue = $unitvalue;
				$lines[] = $line;
			}
		}

		// Totals, needed before the lines because they are printed at the top
		$nblines = count($lines);
		$totalqty = 0;
		$totalvalue = 0;
		$nbnovalue = 0;
		foreach ($lines as $line) {
			$line->totalvalue = (float) price2num($line->qty * $line->unitvalue, 'MT');
			$totalqty += $line->qty;
			$totalvalue += $line->totalvalue;
			if ($line->unitvalue == 0) {
				$nbnovalue++;
			}
		}

		// Create pdf instance
		$pdf = pdf_getInstance($this->format);
		$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
		$pdf->SetAutoPageBreak(true, 0);

		$heightforinfotot = 20; // Height reserved to output the totals at the end
		$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5);
		$heightforfooter = $this->marge_basse + 8;

		if (class_exists('TCPDF')) {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont(pdf_getPDFFont($outputlangs));

		$pdf->Open();
		$pdf->SetDrawColor(128, 128, 128);

		$pdf->SetTitle($outputlangs->convToOutputCharset($isspecimen ? 'SPECIMEN' : $object->ref));
		$pdf->SetSubject($outputlangs->transnoentities("InventoryValued"));
		$pdf->SetCreator("Dolibarr ".DOL_VERSION);
		$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		$pdf->SetKeyWords($outputlangs->convToOutputCharset($isspecimen ? 'SPECIMEN' : $object->ref)." ".$outputlangs->transnoentities("InventoryValued"));
		if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
			$pdf->SetCompression(false);
		}

		// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
		$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

		// Columns (from left)
		$this->posxproduct = $this->marge_gauche;
		$this->posxlabel = $this->marge_gauche + 30;
		$this->posxwarehouse = $this->page_largeur - $this->marge_droite - 106;
		$this->posxbatch = $this->page_largeur - $this->marge_droite - 80;
		$this->posxqty = $this->page_largeur - $this->marge_droite - 64;
		$this->posxunitvalue = $this->page_largeur - $this->marge_droite - 48;
		$this->posxtotalvalue = $this->page_largeur - $this->marge_droite - 26;
		$this->posxend = $this->page_largeur - $this->marge_droite;

		// First page: head, then the totals
		$pdf->AddPage();
		$this->pageHead($pdf, $object, 1, $outputlangs, $recorded, $isspecimen);
		$tab_top = $this->summaryBlock($pdf, $this->marge_haute + 30, $outputlangs, $nblines, $totalqty, $totalvalue, $nbzero, $nbnovalue);
		$tab_top_newpage = 10;

		$this->tableHeaderValued($pdf, $tab_top, $outputlangs);
		$nexY = $tab_top + 7;

		for ($i = 0; $i < $nblines; $i++) {
			$line = $lines[$i];
			$curY = $nexY;
			$pdf->SetFont('', '', $default_font_size - 2);

			// Page break management
			if ($curY > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {
				$this->pageFoot($pdf, $object, $outputlangs, 1);
				$pdf->AddPage();
				$this->tableHeaderValued($pdf, $tab_top_newpage, $outputlangs);
				$curY = $tab_top_newpage + 7;
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetTextColor(0, 0, 0);
			}

			$pdf->SetXY($this->posxproduct, $curY);
			$pdf->MultiCell($this->posxlabel - $this->posxproduct - 1, 3, $outputlangs->convToOutputCharset($line->product_ref), 0, 'L');
			$pdf->SetXY($this->posxlabel, $curY);
			$pdf->MultiCell($this->posxwarehouse - $this->posxlabel - 1, 3, $outputlangs->convToOutputCharset(pdf_truncate_text($pdf, $line->product_label, $this->posxwarehouse - $this->posxlabel - 2)), 0, 'L');
			$pdf->SetXY($this->posxwarehouse, $curY);
			$pdf->MultiCell($this->posxbatch - $this->posxwarehouse - 1, 3, $outputlangs->convToOutputCharset(pdf_truncate_text($pdf, $line->warehouse_ref, $this->posxbatch - $this->posxwarehouse - 2)), 0, 'L');
			$pdf->SetXY($this->posxbatch, $curY);
			$pdf->MultiCell($this->posxqty - $this->posxbatch - 1, 3, $outputlangs->convToOutputCharset(pdf_truncate_text($pdf, $line->batch, $this->posxqty - $this->posxbatch - 2)), 0, 'L');
			$pdf->SetXY($this->posxqty, $curY);
			$pdf->MultiCell($this->posxunitvalue - $this->posxqty - 1, 3, price2num($line->qty, 'MS'), 0, 'R');
			$pdf->SetXY($this->posxunitvalue, $curY);
			$pdf->MultiCell($this->posxtotalvalue - $this->posxunitvalue - 1, 3, price(price2num($line->unitvalue, 'MU'), 0, $outputlangs, 1, -1, -1), 0, 'R');
			$pdf->SetXY($this->posxtotalvalue, $curY);
			$pdf->MultiCell($this->posxend - $this->posxtotalvalue, 3, price($line->totalvalue, 0, $outputlangs, 1, -1, 2), 0, 'R');

			$nexY = $curY + 5;
		}

		// Totals at the end
		$curY = $nexY + 2;
		if ($curY > ($this->page_hauteur - ($heightforfooter + $heightforinfotot))) {
			$this->pageFoot($pdf, $object, $outputlangs, 1);
			$pdf->AddPage();
			$curY = $tab_top_newpage;
		}
		$pdf->line($this->marge_gauche, $curY, $this->page_largeur - $this->marge_droite, $curY);
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetXY($this->posxproduct, $curY + 1);
		$pdf->MultiCell($this->posxqty - $this->posxproduct - 1, 4, $outputlangs->transnoentities("Total").' ('.$nblines.' '.$outputlangs->transnoentities("Products").')', 0, 'L');
		$pdf->SetXY($this->posxqty, $curY + 1);
		$pdf->MultiCell($this->posxunitvalue - $this->posxqty - 1, 4, price2num($totalqty, 'MS'), 0, 'R');
		$pdf->SetXY($this->posxunitvalue, $curY + 1);
		$pdf->MultiCell($this->posxend - $this->posxunitvalue, 4, price(price2num($totalvalue, 'MT'), 0, $outputlangs, 1, -1, -1, $conf->currency), 0, 'R');

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
	 *  Show the totals block of the first page
	 *
	 *  @param	TCPDF|TCPDI	$pdf			Object PDF
	 *  @param	float		$posy			Top position of the block
	 *  @param	Translate	$outputlangs	Langs object
	 *  @param	int			$nblines		Number of products listed
	 *  @param	float		$totalqty		Total quantity
	 *  @param	float		$totalvalue		Total value
	 *  @param	int			$nbzero			Number of lines at zero, not listed
	 *  @param	int			$nbnovalue		Number of products without unit value
	 *  @return	float						Position under the block
	 */
	protected function summaryBlock(&$pdf, $posy, $outputlangs, $nblines, $totalqty, $totalvalue, $nbzero, $nbnovalue)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$width = ($this->page_largeur - $this->marge_gauche - $this->marge_droite) / 3;
		$boxes = array(
			array($outputlangs->transnoentities("Products"), (string) $nblines),
			array($outputlangs->transnoentities("TotalQuantity"), price2num($totalqty, 'MS')),
			array($outputlangs->transnoentities("TotalValue"), price(price2num($totalvalue, 'MT'), 0, $outputlangs, 1, -1, -1, $conf->currency)),
		);
		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetTextColor(0, 0, 60);
		foreach ($boxes as $i => $box) {
			$x = $this->marge_gauche + $i * $width;
			$pdf->RoundedRect($x + 1, $posy, $width - 2, 15, 2, '1111', 'D');
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($x + 1, $posy + 2);
			$pdf->MultiCell($width - 2, 4, $box[0], 0, 'C');
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$pdf->SetXY($x + 1, $posy + 7);
			$pdf->MultiCell($width - 2, 6, $box[1], 0, 'C');
		}
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche, $posy + 17);
		$note = $outputlangs->transnoentities("InventoryValuedNote");
		if ($nbzero) {
			$note .= ' '.$outputlangs->transnoentities("InventoryValuedNbZero", $nbzero);
		}
		if ($nbnovalue) {
			$note .= ' '.$outputlangs->transnoentities("InventoryValuedNbNoValue", $nbnovalue);
		}
		$pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $note, 0, 'L');

		return $pdf->GetY() + 3;
	}

	/**
	 *  Show table header
	 *
	 *  @param	TCPDF|TCPDI	$pdf			Object PDF
	 *  @param	float		$tab_top		Top position of table
	 *  @param	Translate	$outputlangs	Langs object
	 *  @return	void
	 */
	protected function tableHeaderValued(&$pdf, $tab_top, $outputlangs)
	{
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
		$pdf->MultiCell($this->posxqty - $this->posxbatch - 1, 3, $outputlangs->transnoentities("Batch"), 0, 'L');
		$pdf->SetXY($this->posxqty, $posy);
		$pdf->MultiCell($this->posxunitvalue - $this->posxqty - 1, 3, $outputlangs->transnoentities("Qty"), 0, 'R');
		$pdf->SetXY($this->posxunitvalue, $posy);
		$pdf->MultiCell($this->posxtotalvalue - $this->posxunitvalue - 1, 3, $outputlangs->transnoentities("PMPValueShort"), 0, 'R');
		$pdf->SetXY($this->posxtotalvalue, $posy);
		$pdf->MultiCell($this->posxend - $this->posxtotalvalue, 3, $outputlangs->transnoentities("TotalValue"), 0, 'R');

		$pdf->line($this->marge_gauche, $tab_top + 6, $this->page_largeur - $this->marge_droite, $tab_top + 6);
	}
}
