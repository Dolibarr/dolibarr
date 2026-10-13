<?php
/* Copyright (C) 2006-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012	Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010		Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015 Claudio Aschieri				<c.aschieri@19.coop>
 * Copyright (C) 2024		MDW								<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026       Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/core/lib/reception.lib.php
 *  \brief      Function for reception module
 *  \ingroup    reception
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Reception	$object		Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function reception_prepare_head(Reception $object)
{
	global $db, $langs, $conf;

	$langs->loadLangs(array("sendings"));

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/reception/card.php?id=".$object->id;
	$head[$h][1] = $langs->trans("ReceptionCard");
	$head[$h][2] = 'reception';
	$h++;

	if ($object->statut ==  Reception::STATUS_DRAFT || ($object->statut == Reception::STATUS_VALIDATED && !getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION'))) {
		$head[$h][0] = DOL_URL_ROOT."/reception/dispatch.php?id=".$object->id;
		$head[$h][1] = $langs->trans("ReceptionDistribution");
		$head[$h][2] = 'dispatch';
		$h++;
	}

	if (!getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB')) {
		$objectsrc = $object;
		if ($object->origin == 'supplier_order' && $object->origin_id > 0) {
			$objectsrc = new CommandeFournisseur($db);
			$objectsrc->fetch($object->origin_id);
		}
		$nbContact = count($objectsrc->liste_contact(-1, 'internal')) + count($objectsrc->liste_contact(-1, 'external'));
		$head[$h][0] = DOL_URL_ROOT."/reception/contact.php?id=".$object->id;
		$head[$h][1] = $langs->trans("ContactsAddresses");
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contact';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'reception', 'add', 'core');

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->reception->dir_output."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/reception/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	$nbNote = 0;
	if (!empty($object->note_private)) {
		$nbNote++;
	}
	if (!empty($object->note_public)) {
		$nbNote++;
	}
	$head[$h][0] = DOL_URL_ROOT."/reception/note.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Notes");
	if ($nbNote > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
	}
	$head[$h][2] = 'note';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'reception', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'reception', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object information.
 *
 *  @return	array<array{0:string,1:string,2:string}>	head array with tabs
 */
function reception_admin_prepare_head()
{
	global $langs, $conf, $user, $extrafields;
	$langs->load("receptions");

	$extrafields->fetch_name_optionals_label('reception');
	$extrafields->fetch_name_optionals_label('receptiondet_batch');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/reception_setup.php";
	$head[$h][1] = $langs->trans("Reception");
	$head[$h][2] = 'reception';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'reception_admin');

	if (getDolGlobalString('MAIN_SUBMODULE_RECEPTION')) {
		$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/admin/extrafields.php', array('elementtype' => 'reception'));
		$head[$h][1] = $langs->trans("ExtraFields");
		$nbExtrafields = $extrafields->attributes['reception']['count'];
		if ($nbExtrafields > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
		}
		$head[$h][2] = 'attributes_reception';
		$h++;
	}

	if (getDolGlobalString('MAIN_SUBMODULE_RECEPTION')) {
		$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/admin/extrafields.php', array('elementtype' => 'receptiondet_batch'));
		$head[$h][1] = $langs->trans("ExtraFieldsLines");
		$nbExtrafields = $extrafields->attributes['receptiondet_batch']['count'];
		if ($nbExtrafields > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
		}
		$head[$h][2] = 'attributeslines_reception';
		$h++;
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'reception_admin', 'remove');

	return $head;
}

/**
 * Return the product/description cell of a line of the reception card
 *
 * @param	DoliDB						$db				Database handler
 * @param	Form						$form			Form helper
 * @param	Translate					$langs			Language of the user
 * @param	Translate					$outputlangs	Language used for the product label
 * @param	CommandeFournisseurDispatch	$line			Reception line
 * @param	int							$lineIndex		Index of the line, used as tooltip id
 * @param	bool						$showHead		False to return an empty cell
 * @return	string								HTML td
 */
function getReceptionLineProductCell(DoliDB $db, Form $form, Translate $langs, Translate $outputlangs, CommandeFournisseurDispatch $line, int $lineIndex, bool $showHead): string
{
	$out = '<td class="linecoldescription">';
	if (!$showHead) {
		return $out."</td>\n";
	}

	// Predefined product or service
	if ($line->fk_product > 0) {
		if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
			$prod = new Product($db);
			$prod->fetch($line->fk_product);
			$label = (!empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $line->product->label;
		} else {
			$label = (!empty($line->product->label) ? $line->product->label : $line->product->product_label);
		}

		$text = $line->product->getNomUrl(1);
		$text .= ' - '.$label;
		$description = (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE') ? '' : dol_htmlentitiesbr($line->description));
		$out .= $form->textwithtooltip($text, $description, 3, 0, '', (string) $lineIndex);
		$out .= get_date_range(!empty($line->date_start) ? $line->date_start : 0, !empty($line->date_end) ? $line->date_end : 0);
		if (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE')) {
			$out .= (!empty($line->description) && $line->description != $label) ? '<br>'.dol_htmlentitiesbr($line->description) : '';
		}
	} else {
		if ($line->product_type == Product::TYPE_SERVICE) {
			$text = img_object($langs->trans('Service'), 'service');
		} else {
			$text = img_object($langs->trans('Product'), 'product');
		}

		if (!empty($line->label)) {
			$text .= ' <strong>'.$line->label.'</strong>';
			$out .= $form->textwithtooltip($text, $line->description, 3, 0, '', (string) $lineIndex);
		} else {
			$out .= $text.' '.nl2br($line->description);
		}

		$out .= get_date_range($line->date_start, $line->date_end);
	}

	return $out."</td>\n";
}

/**
 * Return the "Qty ordered" and "Qty in other receptions" cells of a line of the reception card
 *
 * @param	DoliDB						$db				Database handler
 * @param	Form						$form			Form helper
 * @param	Translate					$langs			Language of the user
 * @param	CommandeFournisseurDispatch	$line			Reception line
 * @param	array<int|string,array<int|string,array{reception_ref:string,reception_id:int|string,warehouse:int|string|null,qty:float|int|string,date_valid:int|string|null,date_delivery:int|string|null}>>	$alreadySent	Quantities already received, by purchase order line then by reception line
 * @param	int							$originId		Id of the purchase order, 0 if none (no "Qty in other receptions" cell)
 * @param	bool						$showHead		False to return empty cells
 * @return	string								HTML td
 */
function getReceptionLineQtyOrderedCells(DoliDB $db, Form $form, Translate $langs, CommandeFournisseurDispatch $line, array $alreadySent, int $originId, bool $showHead): string
{
	global $conf;

	$out = '<td class="center linecolqty">';
	if ($showHead) {
		$out .= $line->qty_asked;
	}
	$out .= '</td>';

	if ($originId <= 0) {
		return $out;
	}

	$out .= '<td class="center nowrap linecolqtyinotherreceptions">';
	if ($showHead) {
		$htmltooltip = '';
		$qtyalreadyreceived = 0;
		foreach ($alreadySent as $key => $val) {
			if ($line->fk_commandefourndet == $key) {
				$j = 0;
				foreach ($val as $receptionline_var) {
					if ($receptionline_var['reception_id'] == $line->fk_reception) {
						continue; // We want to show only "other receptions"
					}

					$j++;
					if ($j > 1) {
						$htmltooltip .= '<br>';
					}
					if (empty($conf->cache['reception'][$receptionline_var['reception_id']])) {
						$reception_static = new Reception($db);
						$reception_static->fetch((int) $receptionline_var['reception_id']);
						$conf->cache['reception'][$receptionline_var['reception_id']] = $reception_static;
					} else {
						$reception_static = $conf->cache['reception'][$receptionline_var['reception_id']];
					}
					$htmltooltip .= $reception_static->getNomUrl(1, 'nolink', 0, 0, 1);
					$htmltooltip .= ' - '.$receptionline_var['qty'];

					$htmltext = $langs->trans("DateValidation").' : '.(empty($receptionline_var['date_valid']) ? $langs->trans("Draft") : dol_print_date($receptionline_var['date_valid'], 'dayhour'));
					if (isModEnabled('stock') && $receptionline_var['warehouse'] > 0) {
						if (empty($conf->cache['warehouse'][$receptionline_var['warehouse']])) {
							$warehousestatic = new Entrepot($db);
							$warehousestatic->fetch((int) $receptionline_var['warehouse']);
							$conf->cache['warehouse'][$receptionline_var['warehouse']] = $warehousestatic;
						} else {
							$warehousestatic = $conf->cache['warehouse'][$receptionline_var['warehouse']];
						}
						$htmltext .= '<br>'.$langs->trans("From").' : '.$warehousestatic->getNomUrl(1, '', 0, 1);
					}
					$htmltooltip .= ' '.$form->textwithpicto('', $htmltext, 1);

					$qtyalreadyreceived += (float) $receptionline_var['qty'];
				}
				if ($j) {
					$htmltooltip = $langs->trans("QtyInOtherReceptions").'...<br><br>'.$htmltooltip.'<br><input type="submit" name="dummyhiddenbuttontogetfocus" style="display:none" autofocus>';
				}
			}
		}
		$out .= $form->textwithpicto((string) $qtyalreadyreceived, $htmltooltip, 1, 'info', '', 0, 3, 'tooltip'.$line->id);
	}

	return $out.'</td>';
}
