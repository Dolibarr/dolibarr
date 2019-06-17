<?php
/* Copyright (C) 2010-2012	Regis Houssin   <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2018	Charlene Benke  <charlie@patas-monkey.com>
 * Copyright (C) 2018      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/project/doc/pdf_beluga.modules.php
 *	\ingroup    project
 *	\brief      Fichier de la classe permettant de generer les projets au modele beluga
 *	\author	    Charlie Benke
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';

if (! empty($conf->propal->enabled))        require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->facture->enabled))       require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (! empty($conf->facture->enabled))       require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
if (! empty($conf->commande->enabled))      require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->fournisseur->enabled))   require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
if (! empty($conf->fournisseur->enabled))   require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
if (! empty($conf->contrat->enabled))       require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (! empty($conf->ficheinter->enabled))    require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
if (! empty($conf->deplacement->enabled))   require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
if (! empty($conf->expensereport->enabled)) require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
if (! empty($conf->agenda->enabled))        require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';



/**
 *	Class to manage generation of project document Baleine
 */

class pdf_beluga extends ModelePDFProjects
{
	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		// Translations
		$langs->loadLangs(array("main", "projects", "companies"));

		$this->db = $db;
		$this->name = "beluga";
		$this->description = $langs->trans("DocumentModelBeluga");

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;                    // Affiche logo FAC_PDF_LOGO
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_codeproduitservice = 1;      // Affiche code produit-service

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default if not defined

		// Defini position des colonnes
		$this->posxref=$this->marge_gauche+1;
		$this->posxdate=$this->marge_gauche+25;
		$this->posxsociety=$this->marge_gauche+45;
		$this->posxamountht=$this->marge_gauche+110;
		$this->posxamountttc=$this->marge_gauche+135;
		$this->posxstatut=$this->marge_gauche+165;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxref-=20;
			$this->posxdate-=20;
			$this->posxsociete-=20;
			$this->posxamountht-=20;
			$this->posxamountttc-=20;
			$this->posstatut-=20;
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Fonction generant le projet sur le disque
	 *
	 *	@param	Project		$object   		Object project a generer
	 *	@param	Translate	$outputlangs	Lang output object
	 *	@return	int         				1 if OK, <=0 if KO
	 */
	function write_file($object,$outputlangs)
	{
        // phpcs:enable
		global $conf, $hookmanager, $langs, $user;

        $formproject=new FormProjets($this->db);

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		// Load traductions files requiredby by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "projects"));

		if ($conf->projet->dir_output)
		{
			//$nblignes = count($object->lines);  // This is set later with array of tasks

			$objectref = dol_sanitizeFileName($object->ref);
			$dir = $conf->projet->dir_output;
			if (! preg_match('/specimen/i',$objectref)) $dir.= "/" . $objectref;
			$file = $dir . "/" . $objectref . ".pdf";

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('beforePDFCreation',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

				// Create pdf instance
				$pdf=pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1,0);

				$heightforinfotot = 40;	// Height reserved to output the info and total part
		        $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            $heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
	            if ($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS >0) $heightforfooter+= 6;

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                // Set path to the background PDF File
                if (! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
                {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

				// Complete object by loading several other informations
				$task = new Task($this->db);
				$tasksarray = array();
				$tasksarray = $task->getTasksArray(0, 0, $object->id);

				// Special case when used with object = specimen, we may return all lines
				if (! $object->id > 0) {
					$tasksarray=array_slice($tasksarray, 0, min(5, count($tasksarray)));
				}

				$object->lines=$tasksarray;
				$nblignes=count($object->lines);

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Project"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Project"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				$tab_top = 50;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
				$tab_height = 170;
				$tab_height_newpage = 190;

				// Show public note
				$notetoshow=empty($object->note_public)?'':$object->note_public;
				if ($notetoshow)
				{
					$substitutionarray=pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);

					$tab_top -= 2;

					$pdf->SetFont('','', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxref-1, $tab_top-2, dol_htmlentitiesbr($notetoshow), 0, 1);
					$nexY = $pdf->GetY();
					$height_note=$nexY-$tab_top;

					// Rect prend une longueur en 3eme param
					$pdf->SetDrawColor(192,192,192);
					$pdf->Rect($this->marge_gauche, $tab_top-2, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+2);

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY+6;
				}
				else
				{
					$height_note=0;
				}

				$heightoftitleline = 10;
				$iniY = $tab_top + $heightoftitleline + 1;
				$curY = $tab_top + $heightoftitleline + 1;
				$nexY = $tab_top + $heightoftitleline + 1;

                $listofreferent=array(
                    'propal'=>array(
                    	'name'=>"Proposals",
                    	'title'=>"ListProposalsAssociatedProject",
                    	'class'=>'Propal',
                    	'table'=>'propal',
                        'datefieldname'=>'datep',
                    	'test'=>$conf->propal->enabled && $user->rights->propale->lire,
                        'lang'=>'propal'),
                    'order'=>array(
                    	'name'=>"CustomersOrders",
                    	'title'=>"ListOrdersAssociatedProject",
                    	'class'=>'Commande',
                    	'table'=>'commande',
                    	'datefieldname'=>'date_commande',
                    	'test'=>$conf->commande->enabled && $user->rights->commande->lire,
                        'lang'=>'orders'),
                    'invoice'=>array(
                    	'name'=>"CustomersInvoices",
                    	'title'=>"ListInvoicesAssociatedProject",
                    	'class'=>'Facture',
                    	'margin'=>'add',
                    	'table'=>'facture',
                    	'datefieldname'=>'datef',
                    	'test'=>$conf->facture->enabled && $user->rights->facture->lire,
                        'lang'=>'bills'),
                    'invoice_predefined'=>array(
                    	'name'=>"PredefinedInvoices",
                    	'title'=>"ListPredefinedInvoicesAssociatedProject",
                    	'class'=>'FactureRec',
                    	'table'=>'facture_rec',
                    	'datefieldname'=>'datec',
                    	'test'=>$conf->facture->enabled && $user->rights->facture->lire,
                        'lang'=>'bills'),
                    'order_supplier'=>array(
                    	'name'=>"SuppliersOrders",
                    	'title'=>"ListSupplierOrdersAssociatedProject",
                    	'class'=>'CommandeFournisseur',
                    	'table'=>'commande_fournisseur',
                    	'datefieldname'=>'date_commande',
                    	'test'=>$conf->fournisseur->enabled && $user->rights->fournisseur->commande->lire,
                        'lang'=>'orders'),
                    'invoice_supplier'=>array(
                    	'name'=>"BillsSuppliers",
                    	'title'=>"ListSupplierInvoicesAssociatedProject",
                    	'class'=>'FactureFournisseur',
                    	'margin'=>'minus',
                    	'table'=>'facture_fourn',
                    	'datefieldname'=>'datef',
                    	'test'=>$conf->fournisseur->enabled && $user->rights->fournisseur->facture->lire,
                        'lang'=>'bills'),
                    'contract'=>array(
                    	'name'=>"Contracts",
                    	'title'=>"ListContractAssociatedProject",
                    	'class'=>'Contrat',
                    	'table'=>'contrat',
                    	'datefieldname'=>'date_contrat',
                    	'test'=>$conf->contrat->enabled && $user->rights->contrat->lire,
                        'lang'=>'contract'),
                    'intervention'=>array(
                    	'name'=>"Interventions",
                    	'title'=>"ListFichinterAssociatedProject",
                    	'class'=>'Fichinter',
                    	'table'=>'fichinter',
                    	'datefieldname'=>'date_valid',
                    	'disableamount'=>1,
                    	'test'=>$conf->ficheinter->enabled && $user->rights->ficheinter->lire,
                        'lang'=>'interventions'),
                    'trip'=>array(
                    	'name'=>"TripsAndExpenses",
                    	'title'=>"ListExpenseReportsAssociatedProject",
                    	'class'=>'Deplacement',
                    	'table'=>'deplacement',
                    	'datefieldname'=>'dated',
                    	'margin'=>'minus',
                    	'disableamount'=>1,
                    	'test'=>$conf->deplacement->enabled && $user->rights->deplacement->lire,
                        'lang'=>'trip'),
                    'expensereport'=>array(
                    	'name'=>"ExpensesReports",
                    	'title'=>"ListExpenseReportsAssociatedProject",
                    	'class'=>'ExpenseReport',
                    	'table'=>'expensereport',
                    	'datefieldname'=>'dated',
                    	'margin'=>'minus',
                    	'disableamount'=>1,
                    	'test'=>$conf->expensereport->enabled && $user->rights->expensereport->lire,
                        'lang'=>'trip'),
                    'agenda'=>array(
                    	'name'=>"Agenda",
                    	'title'=>"ListActionsAssociatedProject",
                    	'class'=>'ActionComm',
                    	'table'=>'actioncomm',
                    	'datefieldname'=>'datep',
                    	'disableamount'=>1,
                    	'test'=>$conf->agenda->enabled && $user->rights->agenda->allactions->read,
                        'lang'=>'agenda')
                );


                foreach ($listofreferent as $key => $value)
                {
                	$title=$value['title'];
                	$classname=$value['class'];
                	$tablename=$value['table'];
                	$datefieldname=$value['datefieldname'];
                	$qualified=$value['test'];
                	$langstoload=$value['lang'];
                	$langs->load($langstoload);

                    if (! $qualified) continue;

                    //var_dump("$key, $tablename, $datefieldname, $dates, $datee");
                    $elementarray = $object->get_element_list($key, $tablename, $datefieldname, $dates, $datee);

                    if ($key == 'agenda')
                    {
//                    	var_dump($elementarray);
                    }

                    $num = count($elementarray);
                    if ($num >= 0)
                    {
                        $nexY = $pdf->GetY() + 5;

                        $curY = $nexY;
                        $pdf->SetFont('','', $default_font_size - 1);   // Into loop to work with multipage
                        $pdf->SetTextColor(0,0,0);

                        $pdf->SetXY($this->posxref, $curY);
                        $pdf->MultiCell($this->posxstatut - $this->posxref, 3, $outputlangs->transnoentities($title), 0, 'L');

                        $selectList = $formproject->select_element($tablename, $project->thirdparty->id);
                        $nexY = $pdf->GetY() + 1;
                        $curY = $nexY;
                        $pdf->SetXY($this->posxref, $curY);
                        $pdf->MultiCell($this->posxdate - $this->posxref, 3, $outputlangs->transnoentities("Ref"), 1, 'L');
                        $pdf->SetXY($this->posxdate, $curY);
                        $pdf->MultiCell($this->posxsociety - $this->posxdate, 3, $outputlangs->transnoentities("Date"), 1, 'C');
                        $pdf->SetXY($this->posxsociety, $curY);
                        $titlethirdparty=$outputlangs->transnoentities("ThirdParty");
                        if ($classname == 'ExpenseReport') $titlethirdparty=$langs->trans("User");
                        $pdf->MultiCell($this->posxamountht - $this->posxsociety, 3, $titlethirdparty, 1, 'L');
                        if (empty($value['disableamount'])) {
                            $pdf->SetXY($this->posxamountht, $curY);
                            $pdf->MultiCell($this->posxamountttc - $this->posxamountht, 3, $outputlangs->transnoentities("AmountHTShort"), 1, 'R');
                            $pdf->SetXY($this->posxamountttc, $curY);
                            $pdf->MultiCell($this->posxstatut - $this->posxamountttc, 3, $outputlangs->transnoentities("AmountTTCShort"), 1, 'R');
                        } else {
                            $pdf->SetXY($this->posxamountht, $curY);
                            $pdf->MultiCell($this->posxstatut - $this->posxamountht, 3, "", 1, 'R');
                        }
                        $pdf->SetXY($this->posxstatut, $curY);
                        $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxstatut, 3, $outputlangs->transnoentities("Statut"), 1, 'R');

                        if (is_array($elementarray) && count($elementarray) > 0)
                        {
                            $nexY = $pdf->GetY();

                            $total_ht = 0;
                            $total_ttc = 0;
                            $num = count($elementarray);

				// Loop on each lines
				for ($i = 0; $i < $num; $i ++)
				{
					$curY = $nexY;
					$pdf->SetFont('','', $default_font_size - 1);   // Into loop to work with multipage
					$pdf->SetTextColor(0,0,0);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();

					// Description of line
					$idofelement=$elementarray[$i];
                             	if ($classname == 'ExpenseReport')
                               	{
                               		// We get id of expense report
                               		$expensereportline=new ExpenseReportLine($this->db);
                               		$expensereportline->fetch($idofelement);
                               		$idofelement = $expensereportline->fk_expensereport;
                               	}

                                $element = new $classname($this->db);
                                $element->fetch($idofelement);
                                $element->fetch_thirdparty();
                                // print $classname;

                                $qualifiedfortotal = true;
                                if ($key == 'invoice') {
                                    if ($element->close_code == 'replaced')
                                        $qualifiedfortotal = false; // Replacement invoice
                                }

					$showpricebeforepagebreak=1;

					$pdf->startTransaction();
					// Label
					$pdf->SetXY($this->posxref, $curY);
					$pdf->MultiCell($this->posxdate - $this->posxref, 3, $element->ref, 1, 'L');
					$pageposafter=$pdf->getPage();
					if ($pageposafter > $pageposbefore)	// There is a pagebreak
					{
						$pdf->rollbackTransaction(true);
						$pageposafter=$pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						// Label
						$pdf->SetXY($this->posxref, $curY);
						$posybefore=$pdf->GetY();
						$pdf->MultiCell($this->posxdate - $this->posxref, 3, $element->ref, 1, 'L');
						$pageposafter=$pdf->getPage();
						$posyafter=$pdf->GetY();
						if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
						{
							if ($i == ($num-1))	// No more lines, and no space left to show total, so we create a new page
							{
								$pdf->AddPage('','',true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
								$pdf->setPage($pageposafter+1);
							}
						}
						else
						{
							// We found a page break
							$showpricebeforepagebreak=0;
							$forcedesconsamepage=1;
							if ($forcedesconsamepage)
							{
								$pdf->rollbackTransaction(true);
								$pageposafter=$pageposbefore;
								$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.

								$pdf->AddPage('','',true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
								$pdf->setPage($pageposafter+1);
								$pdf->SetFont('','',  $default_font_size - 1);   // On repositionne la police par defaut
								$pdf->MultiCell(0, 3, '');		// Set interline to 3
								$pdf->SetTextColor(0,0,0);

								$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
								$curY = $tab_top_newpage + $heightoftitleline + 1;

								// Label
								$pdf->SetXY($this->posxref, $curY);
								$posybefore=$pdf->GetY();
								$pdf->MultiCell($this->posxdate - $this->posxref, 3, $element->ref, 1, 'L');
								$pageposafter=$pdf->getPage();
								$posyafter=$pdf->GetY();
							}
						}
						//var_dump($i.' '.$posybefore.' '.$posyafter.' '.($this->page_hauteur -  ($heightforfooter + $heightforfreetext + $heightforinfotot)).' '.$showpricebeforepagebreak);
					}
					else	// No pagebreak
					{
						$pdf->commitTransaction();
					}
					$posYAfterDescription=$pdf->GetY();

					$nexY = $pdf->GetY();
					$pageposafter=$pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						//var_dump($pageposbefore.'-'.$pageposafter.'-'.$showpricebeforepagebreak);
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage + $heightoftitleline + 1;
					}

					$pdf->SetFont('','',  $default_font_size - 1);   // On repositionne la police par defaut

					// Date
					if ($tablename == 'commande_fournisseur' || $tablename == 'supplier_order')
						$date = $element->date_commande;
					else {
						$date = $element->date;
						if (empty($date))
							$date = $element->datep;
						if (empty($date))
							$date = $element->date_contrat;
						if (empty($date))
							$date = $element->datev; // Fiche inter
					}

					$pdf->SetXY($this->posxdate, $curY);
					$pdf->MultiCell($this->posxsociety - $this->posxdate, 3, dol_print_date($date, 'day'), 1, 'C');

					$pdf->SetXY($this->posxsociety, $curY);
					if ($classname == 'ExpenseReport')
					{
						$fuser=new User($this->db);
						$fuser->fetch($element->fk_user_author);
						$pdf->MultiCell($this->posxamountht - $this->posxsociety, 3, $fuser->getFullName($outputlangs), 1, 'L');
					}
					else
					{
						$pdf->MultiCell($this->posxamountht - $this->posxsociety, 3, (is_object($element->thirdparty)?$element->thirdparty->name:''), 1, 'L');
					}

                                // Amount without tax
                                if (empty($value['disableamount'])) {
                                    $pdf->SetXY($this->posxamountht, $curY);
                                    $pdf->MultiCell($this->posxamountttc - $this->posxamountht, 3, (isset($element->total_ht) ? price($element->total_ht) : '&nbsp;'), 1, 'R');
                                    $pdf->SetXY($this->posxamountttc, $curY);
                                    $pdf->MultiCell($this->posxstatut - $this->posxamountttc, 3, (isset($element->total_ttc) ? price($element->total_ttc) : '&nbsp;'), 1, 'R');
                                } else {
                                	$pdf->SetXY($this->posxamountht, $curY);
                                	if ($key == 'agenda')
                                	{
                                		$textforamount = dol_trunc($element->label, 26);
                                		$pdf->MultiCell($this->posxstatut - $this->posxamountht, 3, $textforamount, 1, 'L');
                                	}
                                	else
                                	{
	                                    $pdf->MultiCell($this->posxstatut - $this->posxamountht, 3, "", 1, 'R');
                                	}
                                }

                                // Status
                                if ($element instanceof CommonInvoice) {
                                    // This applies for Facture and FactureFournisseur
                                    $outputstatut = $element->getLibStatut(1, $element->getSommePaiement());
                                } else {
                                    $outputstatut = $element->getLibStatut(1);
                                }
                                $pdf->SetXY($this->posxstatut, $curY);
                                $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxstatut, 3, $outputstatut, 1, 'R', false, 1, '', '', true, 0, true);

                                if ($qualifiedfortotal) {
                                    $total_ht = $total_ht + $element->total_ht;
                                    $total_ttc = $total_ttc + $element->total_ttc;
                                }
                                $nexY = $pdf->GetY();
                                $curY = $nexY;
                            }

                            if (empty($value['disableamount'])) {
                                $curY = $nexY;
                                $pdf->SetXY($this->posxref, $curY);
                                $pdf->MultiCell($this->posxamountttc - $this->posxref, 3, "TOTAL", 1, 'L');
                                $pdf->SetXY($this->posxamountht, $curY);
                                $pdf->MultiCell($this->posxamountttc - $this->posxamountht, 3, (isset($element->total_ht) ? price($total_ht) : '&nbsp;'), 1, 'R');
                                $pdf->SetXY($this->posxamountttc, $curY);
                                $pdf->MultiCell($this->posxstatut - $this->posxamountttc, 3, (isset($element->total_ttc) ? price($total_ttc) : '&nbsp;'), 1, 'R');
                                $pdf->SetXY($this->posxstatut, $curY);
                                $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxstatut, 3, $outputlangs->transnoentities("Nb") . " " . $num, 1, 'L');
                            }
                            $nexY = $pdf->GetY() + 5;
                            $curY = $nexY;
                        }
                    }

					$nexY+=2;    // Passe espace entre les lignes

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter)
					{
						$pdf->setPage($pagenb);
						$this->_pagefoot($pdf,$object,$outputlangs,1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					}
				}

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				$this->result = array('fullpath'=>$file);

				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->transnoentities("ErrorConstantNotDefined","PROJECT_OUTPUTDIR");
			return 0;
		}
	}


	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0)
	{
		global $conf,$mysoc;

		$heightoftitleline = 10;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetDrawColor(128,128,128);

		// Draw rect of all tab (title + lines). Rect prend une longueur en 3eme param
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);

		// line prend une position y en 3eme param
		$pdf->line($this->marge_gauche, $tab_top+$heightoftitleline, $this->page_largeur-$this->marge_droite, $tab_top+$heightoftitleline);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','', $default_font_size);

		$pdf->SetXY($this->posxref, $tab_top+1);
		$pdf->MultiCell($this->posxlabel-$this->posxref,3, $outputlangs->transnoentities("Tasks"),'','L');

		$pdf->SetXY($this->posxlabel, $tab_top+1);
		$pdf->MultiCell($this->posxworkload-$this->posxlabel, 3, $outputlangs->transnoentities("Description"), 0, 'L');

		$pdf->SetXY($this->posxworkload, $tab_top+1);
		$pdf->MultiCell($this->posxprogress-$this->posxworkload, 3, $outputlangs->transnoentities("PlannedWorkloadShort"), 0, 'R');

		$pdf->SetXY($this->posxprogress, $tab_top+1);
		$pdf->MultiCell($this->posxdatestart-$this->posxprogress, 3, '%', 0, 'R');

		$pdf->SetXY($this->posxdatestart, $tab_top+1);
		$pdf->MultiCell($this->posxdateend-$this->posxdatestart, 3, '', 0, 'C');

		$pdf->SetXY($this->posxdateend, $tab_top+1);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxdatestart, 3, '', 0, 'C');
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Project		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $langs,$conf,$mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

        $posx=$this->page_largeur-$this->marge_droite-100;
		$posy=$this->marge_haute;

		$pdf->SetXY($this->marge_gauche,$posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
		if ($mysoc->logo)
		{
			if (is_readable($logo))
			{
			    $height=pdf_getHeightForLogo($logo);
			    $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		}
		else $pdf->MultiCell(100, 4, $outputlangs->transnoentities($this->emetteur->name), 0, 'L');

		$pdf->SetFont('','B', $default_font_size + 3);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Project")." ".$outputlangs->convToOutputCharset($object->ref), '', 'R');
		$pdf->SetFont('','', $default_font_size + 2);

		$posy+=6;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("DateStart")." : " . dol_print_date($object->date_start,'day',false,$outputlangs,true), '', 'R');

		$posy+=6;
		$pdf->SetXY($posx,$posy);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("DateEnd")." : " . dol_print_date($object->date_end,'day',false,$outputlangs,true), '', 'R');

		if (is_object($object->thirdparty))
		{
			$posy+=6;
			$pdf->SetXY($posx,$posy);
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities("ThirdParty")." : " . $object->thirdparty->getFullName($outputlangs), '', 'R');
		}

		$pdf->SetTextColor(0,0,60);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			$pdf     			PDF
	 * 		@param	Project		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	integer
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		global $conf;
		$showdetails=$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf,$outputlangs,'PROJECT_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,$showdetails,$hidefreetext);
	}
}
