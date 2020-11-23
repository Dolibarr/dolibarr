<?php
/* Copyright (C) 2017 	Laurent Destailleur <eldy@stocks.sourceforge.net>
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
 *	\file       htdocs/core/modules/movement/doc/pdf_standard.modules.php
 *	\ingroup    societe
 *	\brief      File of class to build PDF documents for stocks movements
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/stock/modules_movement.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Class to build documents using ODF templates generator
 */
class pdf_stdandard extends ModelePDFMovement
{
	/**
	 * @var DoliDb Database handler
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
	 * @var string document type
	 */
	public $type;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.6 = array(5, 6)
	 */
	public $phpmin = array(5, 6);

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * @var int page_largeur
	 */
	public $page_largeur;

	/**
	 * @var int page_hauteur
	 */
	public $page_hauteur;

	/**
	 * @var array format
	 */
	public $format;

	/**
	 * @var int marge_gauche
	 */
	public $marge_gauche;

	/**
	 * @var int marge_droite
	 */
	public $marge_droite;

	/**
	 * @var int marge_haute
	 */
	public $marge_haute;

	/**
	 * @var int marge_basse
	 */
	public $marge_basse;

	/**
	 * @var Societe Issuer
	 */
	public $emetteur;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Load traductions files required by page
		$langs->loadLangs(array("main", "companies"));

		$this->db = $db;
		$this->name = "stdmouvement";
		$this->description = $langs->trans("DocumentModelStandardPDF");

		// Page size for A4 format
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

		$this->option_logo = 1; // Affiche logo
		$this->option_codestockservice = 0; // Affiche code stock-service
		$this->option_multilang = 1; // Dispo en plusieurs langues
		$this->option_freetext = 0; // Support add of a personalised text

		// Recupere emetteur
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined

		// Define position of columns
		$this->wref = 15;
		$this->posxidref = $this->marge_gauche;
		$this->posxdatemouv = $this->marge_gauche + 8;
		$this->posxdesc = 37;
		$this->posxlabel = 50;
		$this->posxtva = 80;
		$this->posxqty = 105;
		$this->posxup = 119;
		$this->posxunit = 136;
		$this->posxdiscount = 167;
		$this->postotalht = 180;

		if (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) || !empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) $this->posxtva = $this->posxup;
		$this->posxpicture = $this->posxtva - (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH) ? 20 : $conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH); // width of images
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxpicture -= 20;
			$this->posxtva -= 20;
			$this->posxup -= 20;
			$this->posxqty -= 20;
			$this->posxunit -= 20;
			$this->posxdiscount -= 20;
			$this->postotalht -= 20;
		}
		$this->tva = array();
		$this->localtax1 = array();
		$this->localtax2 = array();
		$this->atleastoneratenotnull = 0;
		$this->atleastonediscount = 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Function to build a document on disk using the generic odt module.
	 *
	 *	@param		MouvementStock	$object				Object source to build document
	 *	@param		Translate		$outputlangs		Lang output object
	 * 	@param		string			$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int				$hidedetails		Do not show line details
	 *  @param		int				$hidedesc			Do not show desc
	 *  @param		int				$hideref			Do not show ref
	 *	@return		int         						1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $db, $hookmanager;

		if (!is_object($outputlangs)) $outputlangs = $langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output = 'ISO-8859-1';

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "stocks", "orders", "deliveries"));

		/**
		 * TODO: get from object
		 */

		$id = GETPOST('id', 'int');
		$ref = GETPOST('ref', 'alpha');
		$msid = GETPOST('msid', 'int');
		$product_id = GETPOST("product_id");
		$action = GETPOST('action', 'aZ09');
		$cancel = GETPOST('cancel', 'alpha');
		$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'movementlist';

		$idproduct = GETPOST('idproduct', 'int');
		$year = GETPOST("year");
		$month = GETPOST("month");
		$search_ref = GETPOST('search_ref', 'alpha');
		$search_movement = GETPOST("search_movement");
		$search_product_ref = trim(GETPOST("search_product_ref"));
		$search_product = trim(GETPOST("search_product"));
		$search_warehouse = trim(GETPOST("search_warehouse"));
		$search_inventorycode = trim(GETPOST("search_inventorycode"));
		$search_user = trim(GETPOST("search_user"));
		$search_batch = trim(GETPOST("search_batch"));
		$search_qty = trim(GETPOST("search_qty"));
		$search_type_mouvement = GETPOST('search_type_mouvement', 'int');

		$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
		$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
		$sortfield = GETPOST("sortfield", 'alpha');
		$sortorder = GETPOST("sortorder", 'alpha');
		if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
		$offset = $limit * $page;
		if (!$sortfield) $sortfield = "m.datem";
		if (!$sortorder) $sortorder = "DESC";

		$pdluoid = GETPOST('pdluoid', 'int');

		// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
		$hookmanager->initHooks(array('movementlist'));
		$extrafields = new ExtraFields($this->db);

		// fetch optionals attributes and labels
		$extrafields->fetch_name_optionals_label('movement');
		$search_array_options = $extrafields->getOptionalsFromPost('movement', '', 'search_');

		$productlot = new ProductLot($this->db);
		$productstatic = new Product($this->db);
		$warehousestatic = new Entrepot($this->db);
		$movement = new MouvementStock($this->db);
		$userstatic = new User($this->db);
		$element = 'movement';

		$sql = "SELECT p.rowid, p.ref as product_ref, p.label as produit, p.tobatch, p.fk_product_type as type, p.entity,";
		$sql .= " e.ref as warehouse_ref, e.rowid as entrepot_id, e.lieu,";
		$sql .= " m.rowid as mid, m.value as qty, m.datem, m.fk_user_author, m.label, m.inventorycode, m.fk_origin, m.origintype,";
		$sql .= " m.batch, m.price,";
		$sql .= " m.type_mouvement,";
		$sql .= " pl.rowid as lotid, pl.eatby, pl.sellby,";
		$sql .= " u.login, u.photo, u.lastname, u.firstname";
		// Add fields from extrafields
		if (!empty($extrafields->attributes[$element]['label'])) {
			foreach ($extrafields->attributes[$element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
		}
		// Add fields from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;
		$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
		$sql .= " ".MAIN_DB_PREFIX."product as p,";
		$sql .= " ".MAIN_DB_PREFIX."stock_mouvement as m";
		if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (m.rowid = ef.fk_object)";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON m.fk_user_author = u.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot as pl ON m.batch = pl.batch AND m.fk_product = pl.fk_product";
		$sql .= " WHERE m.fk_product = p.rowid";
		if ($msid > 0) $sql .= " AND m.rowid = ".$msid;
		$sql .= " AND m.fk_entrepot = e.rowid";
		$sql .= " AND e.entity IN (".getEntity('stock').")";
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) $sql .= " AND p.fk_product_type = 0";
		if ($id > 0) $sql .= " AND e.rowid ='".$id."'";
		if ($month > 0)
		{
			if ($year > 0)
				$sql .= " AND m.datem BETWEEN '".$this->db->idate(dol_get_first_day($year, $month, false))."' AND '".$this->db->idate(dol_get_last_day($year, $month, false))."'";
			else $sql .= " AND date_format(m.datem, '%m') = '$month'";
		} elseif ($year > 0)
		{
			$sql .= " AND m.datem BETWEEN '".$this->db->idate(dol_get_first_day($year, 1, false))."' AND '".$this->db->idate(dol_get_last_day($year, 12, false))."'";
		}
		if ($idproduct > 0) $sql .= " AND p.rowid = ".((int) $idproduct);
		if (!empty($search_ref))			$sql .= natural_search('m.rowid', $search_ref, 1);
		if (!empty($search_movement))      $sql .= natural_search('m.label', $search_movement);
		if (!empty($search_inventorycode)) $sql .= natural_search('m.inventorycode', $search_inventorycode);
		if (!empty($search_product_ref))   $sql .= natural_search('p.ref', $search_product_ref);
		if (!empty($search_product))       $sql .= natural_search('p.label', $search_product);
		if ($search_warehouse > 0)          $sql .= " AND e.rowid = ".((int) $this->db->escape($search_warehouse));
		if (!empty($search_user))          $sql .= natural_search('u.login', $search_user);
		if (!empty($search_batch))         $sql .= natural_search('m.batch', $search_batch);
		if ($search_qty != '')				$sql .= natural_search('m.value', $search_qty, 1);
		if ($search_type_mouvement > 0)		$sql .= " AND m.type_mouvement = '".$this->db->escape($search_type_mouvement)."'";
		// Add where from extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;
		$sql .= $this->db->order($sortfield, $sortorder);

		$nbtotalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$result = $this->db->query($sql);
			$nbtotalofrecords = $this->db->num_rows($result);
			if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
			{
				$page = 0;
				$offset = 0;
			}
		}

		if (empty($search_inventorycode)) $sql .= $this->db->plimit($limit + 1, $offset);


		$resql = $this->db->query($sql);
		$nbtotalofrecords = $this->db->num_rows($result);

		/*
         * END TODO
         **/

		//$nblines = count($object->lines);

		if ($conf->stock->dir_output)
		{
			if ($resql)
			{
				$product = new Product($this->db);
				$object = new Entrepot($this->db);

				if ($idproduct > 0)
				{
					$product->fetch($idproduct);
				}
				if ($id > 0 || $ref)
				{
					$result = $object->fetch($id, $ref);
					if ($result < 0)
					{
						dol_print_error($this->db);
					}
				}

				$num = $this->db->num_rows($resql);

				$arrayofselected = is_array($toselect) ? $toselect : array();

				$i = 0;
				$help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
				if ($msid) $texte = $langs->trans('StockMovementForId', $msid);
				else {
					$texte = $langs->trans("ListOfStockMovements");
					if ($id) $texte .= ' ('.$langs->trans("ForThisWarehouse").')';
				}
			}

			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->stock->dir_output."/movement";
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				if (!empty($search_inventorycode)) $objectref .= "_".$id."_".$search_inventorycode;
				if ($search_type_mouvement) $objectref .= "_".$search_type_mouvement;
				$dir = $conf->stock->dir_output."/movement/".$objectref;
				$file = $dir."/".$objectref.".pdf";
			}

			$stockFournisseur = new ProductFournisseur($this->db);
			$supplierprices = $stockFournisseur->list_product_fournisseur_price($object->id);
			$object->supplierprices = $supplierprices;

			$productstatic = new Product($this->db);

			if (!file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return -1;
				}
			}

			if (file_exists($dir))
			{
				// Add pdfgeneration hook
				if (!is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);

				$heightforinfotot = 40; // Height reserved to output the info and total part
				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)

				if (class_exists('TCPDF'))
				{
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));
				// Set path to the background PDF File
				if (empty($conf->global->MAIN_DISABLE_FPDI) && !empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
				{
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Stock"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Stock")." ".$outputlangs->convToOutputCharset($object->label));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right


				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 42;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 42 : 10);
				$tab_height = 130;
				$tab_height_newpage = 150;

				/* ************************************************************************** */
				/*                                                                            */
				/* Affichage de la liste des produits du MouvementStock                           */
				/*                                                                            */
				/* ************************************************************************** */

				$nexY += 5;
				$nexY = $pdf->GetY();
				$nexY += 10;

				$totalunit = 0;
				$totalvalue = $totalvaluesell = 0;
				$arrayofuniqueproduct = array();

				//dol_syslog('List products', LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql)
				{
					$num = $this->db->num_rows($resql);
					$i = 0;
					$nblines = $num;
					for ($i = 0; $i < $nblines; $i++)
					{
						$objp = $this->db->fetch_object($resql);

						// Multilangs
						if (!empty($conf->global->MAIN_MULTILANGS)) // si l'option est active
						{
							$sql = "SELECT label";
							$sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
							$sql .= " WHERE fk_product=".$objp->rowid;
							$sql .= " AND lang='".$this->db->escape($langs->getDefaultLang())."'";
							$sql .= " LIMIT 1";

							$result = $this->db->query($sql);
							if ($result)
							{
								$objtp = $this->db->fetch_object($result);
								if ($objtp->label != '') $objp->produit = $objtp->label;
							}
						}

						$curY = $nexY;
						$pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
						$pdf->SetTextColor(0, 0, 0);

						$pdf->setTopMargin($tab_top_newpage);
						$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
						$pageposbefore = $pdf->getPage();

						// Description of product line
						$curX = $this->posxdesc - 1;

						$showpricebeforepagebreak = 1;

						$pdf->startTransaction();
						pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxtva - $curX, 3, $curX, $curY, $hideref, $hidedesc);
						$pageposafter = $pdf->getPage();
						if ($pageposafter > $pageposbefore)	// There is a pagebreak
						{
							$pdf->rollbackTransaction(true);
							$pageposafter = $pageposbefore;
							//print $pageposafter.'-'.$pageposbefore;exit;
							$pdf->setPageOrientation('', 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.
							pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxtva - $curX, 4, $curX, $curY, $hideref, $hidedesc);
							$pageposafter = $pdf->getPage();
							$posyafter = $pdf->GetY();
							if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot)))	// There is no space left for total+free text
							{
								if ($i == ($nblines - 1))	// No more lines, and no space left to show total, so we create a new page
								{
									$pdf->AddPage('', '', true);
									if (!empty($tplidx)) $pdf->useTemplate($tplidx);
									if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
									$pdf->setPage($pageposafter + 1);
								}
							} else {
								// We found a page break

								// Allows data in the first page if description is long enough to break in multiples pages
								if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE))
									$showpricebeforepagebreak = 1;
								else $showpricebeforepagebreak = 0;
							}
						} else // No pagebreak
						{
							$pdf->commitTransaction();
						}
						$posYAfterDescription = $pdf->GetY();

						$nexY = $pdf->GetY();
						$pageposafter = $pdf->getPage();

						$pdf->setPage($pageposbefore);
						$pdf->setTopMargin($this->marge_haute);
						$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.

						// We suppose that a too long description is moved completely on next page
						if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
							$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
						}

						$pdf->SetFont('', '', $default_font_size - 1); // On repositionne la police par defaut

						// $objp = $this->db->fetch_object($resql);

						$userstatic->id = $objp->fk_user_author;
						$userstatic->login = $objp->login;
						$userstatic->lastname = $objp->lastname;
						$userstatic->firstname = $objp->firstname;
						$userstatic->photo = $objp->photo;

						$productstatic->id = $objp->rowid;
						$productstatic->ref = $objp->product_ref;
						$productstatic->label = $objp->produit;
						$productstatic->type = $objp->type;
						$productstatic->entity = $objp->entity;
						$productstatic->status_batch = $objp->tobatch;

						$productlot->id = $objp->lotid;
						$productlot->batch = $objp->batch;
						$productlot->eatby = $objp->eatby;
						$productlot->sellby = $objp->sellby;

						$warehousestatic->id = $objp->entrepot_id;
						$warehousestatic->label = $objp->warehouse_ref;
						$warehousestatic->lieu = $objp->lieu;

						$arrayofuniqueproduct[$objp->rowid] = $objp->produit;
						if (!empty($objp->fk_origin)) {
							$origin = $movement->get_origin($objp->fk_origin, $objp->origintype);
						} else {
							$origin = '';
						}

						// Id movement.
						$pdf->SetXY($this->posxidref, $curY);
						$pdf->MultiCell($this->posxdesc - $this->posxidref - 0.8, 3, $objp->mid, 0, 'L');

						// Date.
						$pdf->SetXY($this->posxdatemouv, $curY);
						$pdf->MultiCell($this->posxdesc - $this->posxdatemouv - 0.8, 6, dol_print_date($this->db->jdate($objp->datem), 'dayhour'), 0, 'L');

						// Ref.
						$pdf->SetXY($this->posxdesc, $curY);
						$pdf->MultiCell($this->posxlabel - $this->posxdesc - 0.8, 3, $productstatic->ref, 0, 'L');

						// Label
						$pdf->SetXY($this->posxlabel + 0.8, $curY);
						$pdf->MultiCell($this->posxqty - $this->posxlabel - 0.8, 6, $productstatic->label, 0, 'L');

						// Lot/serie
						$pdf->SetXY($this->posxqty, $curY);
						$pdf->MultiCell($this->posxup - $this->posxqty - 0.8, 3, $productlot->batch, 0, 'R');

						// Inv. code
						$pdf->SetXY($this->posxup, $curY);
						$pdf->MultiCell($this->posxunit - $this->posxup - 0.8, 3, $objp->inventorycode, 0, 'R');

						// Label mouvement
						$pdf->SetXY($this->posxunit, $curY);
						$pdf->MultiCell($this->posxdiscount - $this->posxunit - 0.8, 3, $objp->label, 0, 'R');
						$totalvalue += price2num($objp->ppmp * $objp->value, 'MT');

						// Origin
						$pricemin = $objp->price;
						$pdf->SetXY($this->posxdiscount, $curY);
						$pdf->MultiCell($this->postotalht - $this->posxdiscount - 0.8, 3, $origin, 0, 'R', 0);

						// Qty
						$valtoshow = price2num($objp->qty, 'MS');
						$towrite = (empty($valtoshow) ? '0' : $valtoshow);
						$totalunit += $objp->qty;

						$pdf->SetXY($this->postotalht, $curY);
						$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->postotalht, 3, $objp->qty, 0, 'R', 0);

						$totalvaluesell += price2num($pricemin * $objp->value, 'MT');

						$nexY += 3.5; // Add space between lines
						// Add line
						if (!empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1))
						{
							$pdf->setPage($pageposafter);
							$pdf->SetLineStyle(array('dash'=>'1,1', 'color'=>array(80, 80, 80)));
							//$pdf->SetDrawColor(190,190,200);
							$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
							$pdf->SetLineStyle(array('dash'=>0));
						}

						$nexY += 2; // Add space between lines

						// Detect if some page were added automatically and output _tableau for past pages
						while ($pagenb < $pageposafter)
						{
							$pdf->setPage($pagenb);
							if ($pagenb == 1)
							{
								$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
							} else {
								$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
							}
							$this->_pagefoot($pdf, $object, $outputlangs, 1);
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
							if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak)
						{
							if ($pagenb == 1)
							{
								$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
							} else {
								$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
							}
							$this->_pagefoot($pdf, $object, $outputlangs, 1);
							// New page
							$pdf->AddPage();
							if (!empty($tplidx)) $pdf->useTemplate($tplidx);
							$pagenb++;
							if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
						}
					}

					$this->db->free($resql);

					/**
					 * footer table
					 */
					$nexY = $pdf->GetY();
					$nexY += 5;
					$curY = $nexY;

					$pdf->SetLineStyle(array('dash'=>'0', 'color'=>array(220, 26, 26)));
					$pdf->line($this->marge_gauche, $curY - 1, $this->page_largeur - $this->marge_droite, $curY - 1);
					$pdf->SetLineStyle(array('dash'=>0));

					$pdf->SetFont('', 'B', $default_font_size - 1);
					$pdf->SetTextColor(0, 0, 120);

					// Total
					$pdf->SetXY($this->posxidref, $curY);
					$pdf->MultiCell($this->posxdesc - $this->posxidref, 3, $langs->trans("Total"), 0, 'L');

					// Total Qty
					$pdf->SetXY($this->postotalht, $curY);
					$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->postotalht, 3, $totalunit, 0, 'R', 0);
				} else {
					dol_print_error($this->db);
				}

				if ($notetoshow)
				{
					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
					$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

					$tab_top = 88;

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					$nexY = $pdf->GetY();
					$height_note = $nexY - $tab_top;

					// Rect takes a length in 3rd parameter
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1);

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY + 6;
				} else {
					$height_note = 0;
				}

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				$tab_top = $tab_top_newpage + 21;

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}

				$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;

				// Affiche zone infos
				//$posy=$this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				// Affiche zone totaux
				//$posy=$this->_tableau_tot($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0)
				{
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
				}

				if (!empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				$this->result = array('fullpath'=>$file);

				return 1; // No error
			} else {
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->trans("ErrorConstantNotDefined", "PRODUCT_OUTPUTDIR");
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom = 0;
		if ($hidetop) $hidetop = -1;

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);

		if (empty($hidetop))
		{
			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			//$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
			if (!empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, 5, 'F', null, explode(',', $conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR));
		}

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', 'B', $default_font_size - 3);

		// Output Rect
		//$this->printRect($pdf,$this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect takes a length in 3rd parameter and 4th parameter

		$pdf->SetLineStyle(array('dash'=>'0', 'color'=>array(220, 26, 26)));
		$pdf->SetDrawColor(220, 26, 26);
		$pdf->line($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite, $tab_top);
		$pdf->SetLineStyle(array('dash'=>0));
		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetTextColor(0, 0, 120);

		//Ref mouv
		if (empty($hidetop))
		{
			//$pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);	// line takes a position y in 2nd parameter and 4th parameter
			$pdf->SetXY($this->posxidref, $tab_top + 1);
			$pdf->MultiCell($this->posxdatemouv - $this->posxdatemouv - 0.8, 3, $outputlangs->transnoentities("Ref"), '', 'L');
		}

		//Date mouv
		//$pdf->line($this->posxlabel-1, $tab_top, $this->posxlabel-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxdatemouv, $tab_top + 1);
			$pdf->MultiCell($this->posxdesc - $this->posxdatemouv, 2, $outputlangs->transnoentities("Date"), '', 'C');
		}

		//Ref Product
		//$pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxdesc - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxlabel - $this->posxdesc, 2, $outputlangs->transnoentities("Ref. Product"), '', 'C');
		}

		//Label Product
		//$pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxlabel - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxqty - $this->posxlabel, 2, $outputlangs->transnoentities("Label"), '', 'C');
		}

		//Lot/serie Product
		//$pdf->line($this->posxqty - 1, $tab_top, $this->posxqty - 1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxqty, $tab_top + 1);
			$pdf->MultiCell($this->posxup - $this->posxqty, 2, $outputlangs->transnoentities("Lot/Série"), '', 'C');
		}

		//Code Inv
		//$pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxup - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxunit - $this->posxup, 2, $outputlangs->transnoentities("Inventory Code"), '', 'C');
		}

		//Label mouvement
		//$pdf->line($this->posxunit, $tab_top, $this->posxunit, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxunit, $tab_top + 1);
			$pdf->MultiCell($this->posxdiscount - $this->posxunit, 2, $outputlangs->transnoentities("Label Mouvement"), '', 'C');
		}

		//Origin
		//$pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxdiscount + 2, $tab_top + 1);
			$pdf->MultiCell($this->postotalht - $this->posxdiscount - 0.8, 2, $outputlangs->transnoentities("Origin"), '', 'C');
		}

		//Qty
		//$pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->postotalht + 2, $tab_top + 1);
			$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->postotalht, 2, $outputlangs->transnoentities("Qty"), '', 'C');
		}

		$pdf->SetDrawColor(220, 26, 26);
		$pdf->SetLineStyle(array('dash'=>'0', 'color'=>array(220, 26, 26)));
		$pdf->line($this->marge_gauche, $tab_top + 11, $this->page_largeur - $this->marge_droite, $tab_top + 11);
		$pdf->SetLineStyle(array('dash'=>0));
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param	string		$titlekey		Translation key to show as title of document
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = "")
	{
		global $conf, $langs, $db, $hookmanager;

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "propal", "companies", "bills", "orders", "stocks"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if ($object->type == 1) $titlekey = 'ServiceSheet';
		else $titlekey = 'StockSheet';

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Show Draft Watermark
		if ($object->statut == 0 && (!empty($conf->global->COMMANDE_DRAFT_WATERMARK)))
		{
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->COMMANDE_DRAFT_WATERMARK);
		}

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - 100;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		$logo = $conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				$height = pdf_getHeightForLogo($logo);
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text = $this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title = $outputlangs->transnoentities("Warehouse");
		$pdf->MultiCell(100, 3, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size);

		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);

		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->label), '', 'R');

		$posy += 5;
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("LocationSummary").' :', '', 'R');

		$posy += 4;
		$pdf->SetXY($posx - 50, $posy);
		$pdf->MultiCell(150, 3, $object->lieu, '', 'R');


		// Parent MouvementStock
		$posy += 4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ParentWarehouse").' :', '', 'R');

		$posy += 4;
		$pdf->SetXY($posx - 50, $posy);
		$e = new MouvementStock($this->db);
		if (!empty($object->fk_parent) && $e->fetch($object->fk_parent) > 0)
		{
			$pdf->MultiCell(150, 3, $e->label, '', 'R');
		} else {
			$pdf->MultiCell(150, 3, $outputlangs->transnoentities("None"), '', 'R');
		}

		// Description
		$nexY = $pdf->GetY();
		$nexY += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->writeHTMLCell(190, 2, $this->marge_gauche, $nexY, '<b>'.$outputlangs->transnoentities("Description").' : </b>'.nl2br($object->description), 0, 1);
		$nexY = $pdf->GetY();

		$calcproductsunique = $object->nb_different_products();
		$calcproducts = $object->nb_products();

		// Total nb of different products
		$pdf->writeHTMLCell(190, 2, $this->marge_gauche, $nexY, '<b>'.$outputlangs->transnoentities("NumberOfDifferentProducts").' : </b>'.(empty($calcproductsunique['nb']) ? '0' : $calcproductsunique['nb']), 0, 1);
		$nexY = $pdf->GetY();

		// Nb of products
		$valtoshow = price2num($calcproducts['nb'], 'MS');
		$pdf->writeHTMLCell(190, 2, $this->marge_gauche, $nexY, '<b>'.$outputlangs->transnoentities("NumberOfProducts").' : </b>'.(empty($valtoshow) ? '0' : $valtoshow), 0, 1);
		$nexY = $pdf->GetY();

		// Value
		$pdf->writeHTMLCell(190, 2, $this->marge_gauche, $nexY, '<b>'.$outputlangs->transnoentities("EstimatedStockValueShort").' : </b>'.price((empty($calcproducts['value']) ? '0' : price2num($calcproducts['value'], 'MT')), 0, $langs, 0, -1, -1, $conf->currency), 0, 1);
		$nexY = $pdf->GetY();


		// Last movement
		$sql = "SELECT max(m.datem) as datem";
		$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
		$sql .= " WHERE m.fk_entrepot = ".((int) $object->id);
		$resqlbis = $this->db->query($sql);
		if ($resqlbis)
		{
			$obj = $this->db->fetch_object($resqlbis);
			$lastmovementdate = $this->db->jdate($obj->datem);
		} else {
			dol_print_error($this->db);
		}

		if ($lastmovementdate)
		{
			$toWrite = dol_print_date($lastmovementdate, 'dayhour').' ';
		} else {
			$toWrite = $outputlangs->transnoentities("None");
		}

		$pdf->writeHTMLCell(190, 2, $this->marge_gauche, $nexY, '<b>'.$outputlangs->transnoentities("LastMovement").' : </b>'.$toWrite, 0, 1);
		$nexY = $pdf->GetY();


		/*if ($object->ref_client)
	    {
	        $posy+=5;
	        $pdf->SetXY($posx,$posy);
	        $pdf->SetTextColor(0,0,60);
	        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("RefCustomer")." : " . $outputlangs->convToOutputCharset($object->ref_client), '', 'R');
	    }*/

		/*$posy+=4;
	    $pdf->SetXY($posx,$posy);
	    $pdf->SetTextColor(0,0,60);
	    $pdf->MultiCell(100, 3, $outputlangs->transnoentities("OrderDate")." : " . dol_print_date($object->date,"%d %b %Y",false,$outputlangs,true), '', 'R');
	    */

		// Get contact
		/*
	    if (!empty($conf->global->DOC_SHOW_FIRST_SALES_REP))
	    {
	        $arrayidcontact=$object->getIdContact('internal','SALESREPFOLL');
	        if (count($arrayidcontact) > 0)
	        {
	            $usertmp=new User($this->db);
	            $usertmp->fetch($arrayidcontact[0]);
	            $posy+=4;
	            $pdf->SetXY($posx,$posy);
	            $pdf->SetTextColor(0,0,60);
	            $pdf->MultiCell(100, 3, $langs->trans("SalesRepresentative")." : ".$usertmp->getFullName($langs), '', 'R');
	        }
	    }*/

		$posy += 2;

		// Show list of linked objects
		//$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);

		if ($showaddress)
		{
			/*
	        // Sender properties
	        $carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty);

	        // Show sender
	        $posy=42;
	        $posx=$this->marge_gauche;
	        if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-80;
	        $hautcadre=40;

	        // Show sender frame
	        $pdf->SetTextColor(0,0,0);
	        $pdf->SetFont('','', $default_font_size - 2);
	        $pdf->SetXY($posx,$posy-5);
	        $pdf->MultiCell(66,5, $outputlangs->transnoentities("BillFrom").":", 0, 'L');
	        $pdf->SetXY($posx,$posy);
	        $pdf->SetFillColor(230,230,230);
	        $pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
	        $pdf->SetTextColor(0,0,60);

	        // Show sender name
	        $pdf->SetXY($posx+2,$posy+3);
	        $pdf->SetFont('','B', $default_font_size);
	        $pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
	        $posy=$pdf->getY();

	        // Show sender information
	        $pdf->SetXY($posx+2,$posy);
	        $pdf->SetFont('','', $default_font_size - 1);
	        $pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');
	        */
		}

		$pdf->SetTextColor(0, 0, 0);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show footer of page. Need this->emetteur object
	 *
	 *  @param	TCPDF		$pdf     			PDF
	 *  @param	Object		$object				Object to show
	 *  @param	Translate	$outputlangs		Object lang for output
	 *  @param	int			$hidefreetext		1=Hide free text
	 *  @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 0 : $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf, $outputlangs, 'PRODUCT_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}
}
