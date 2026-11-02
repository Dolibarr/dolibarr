<?php
/* Copyright (C) 2026		Frederic France			<frederic.france@free.fr>
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
 *	\file       htdocs/core/modules/dons/pdf_acroform_donation.modules.php
 *	\ingroup    don
 *	\brief      File of class to build receipts by filling the fields of an official PDF form
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


/**
 *	Class to build a donation receipt by filling the fields of a PDF form (AcroForm).
 *
 *	The form is an official PDF, for example the Cerfa n°16216*03 downloaded from the tax
 *	administration, dropped into the directory of the templates. Which value goes into which
 *	field of the form is read from a file of the same name with ".map.json" added, so that a
 *	new revision of the form only means dropping in the new file.
 */
class pdf_acroform_donation extends ModeleDon
{
	/**
	 * @var string Dolibarr version of the loaded document
	 */
	public $version = 'development';

	/**
	 * @var string Name of the constant that holds the directories to scan for templates
	 */
	public $scandir = 'DON_ADDON_PDF_ACROFORM_PATH';

	/**
	 * @var int Position of the template into the list
	 */
	public $position = 60;

	/**
	 * @var array{fullpath:string} Result of the generation
	 */
	public $result = array('fullpath' => '');


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		global $langs;

		$langs->loadLangs(array("main", "donations"));

		$this->db = $db;
		$this->name = "AcroForm";
		$this->description = $langs->trans('DonationsReceiptModel').' - '.$langs->trans('DocumentModelAcroFormPDF');

		$this->type = 'pdf';
		$this->option_multilang = 1;
	}


	/**
	 *	Set the reason why the receipt could not be made. The file that holds these
	 *	messages is only loaded when one of them is needed.
	 *
	 *	@param	string	$key		Key of the message
	 *	@param	string	...$params	Values the message shows
	 *	@return	void
	 */
	protected function setError($key, ...$params)
	{
		global $langs;

		$langs->load("errors");
		$this->error = $langs->transnoentities($key, ...$params);
	}


	/**
	 *	Tell if the template can be used
	 *
	 *	@return	bool	true when it can
	 */
	public function isEnabled()
	{
		return true;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Fill the fields of the PDF form with the data of a donation
	 *
	 *	@param	Don			$don			Donation object
	 *	@param	Translate	$outputlangs	Lang object for output language
	 *	@param	string		$currency		Currency code
	 *	@return	int<-1,1>					>0 if OK, <=0 if KO
	 */
	public function write_file($don, $outputlangs, $currency = '')
	{
		// phpcs:enable
		global $conf, $langs;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "donations"));

		$currency = !empty($currency) ? $currency : $conf->currency;

		if (empty($conf->don->dir_output)) {
			$this->setError("ErrorConstantNotDefined", "DON_OUTPUTDIR");
			return 0;
		}

		if (!is_object($don)) {
			$id = $don;
			$don = new Don($this->db);
			$don->fetch($id);
		}

		$template = $this->getTemplatePath();
		if ($template === '') {
			return 0;
		}

		$map = $this->readMap($template);
		if ($map === null) {
			return 0;
		}

		$values = $this->buildValues($don, $outputlangs, $currency, $template, $map);
		if ($values === null) {
			return 0;
		}

		// Definition of $dir and $file
		if (!empty($don->specimen)) {
			$dir = $conf->don->dir_output;
			$file = $dir."/SPECIMEN.pdf";
		} else {
			$donref = dol_sanitizeFileName((string) $don->ref);
			$dir = $conf->don->dir_output."/".$donref;
			$file = $dir."/".$donref.".pdf";
		}

		if (!file_exists($dir) && dol_mkdir($dir) < 0) {
			$this->setError("ErrorCanNotCreateDir", $dir);
			return 0;
		}

		require_once DOL_DOCUMENT_ROOT.'/includes/fpdm/fpdm.php';

		$pdf = new FPDM($template);
		$pdf->useCheckboxParser = true;
		$pdf->Load($values, true);
		$pdf->Merge();
		$pdf->Output('F', $file);

		if (!file_exists($file)) {
			$this->setError("ErrorFailToCreateFile", $file);
			return 0;
		}

		dolChmod($file);

		$this->result = array('fullpath' => $file);

		return 1;
	}


	/**
	 *	Return the path of the PDF form to fill
	 *
	 *	@return	string	Path of the template, empty when it can not be found ($this->error is set)
	 */
	protected function getTemplatePath()
	{

		$name = dol_sanitizeFileName(getDolGlobalString('DON_ACROFORM_TEMPLATE'));
		if ($name === '') {
			$this->setError("ErrorNoAcroFormTemplateSelected");
			return '';
		}

		$dirs = explode(',', preg_replace('/[\r\n]+/', ',', getDolGlobalString($this->scandir)));
		foreach ($dirs as $dir) {
			$dir = trim($dir);
			if ($dir === '') {
				continue;
			}
			$dir = preg_replace('/DOL_DATA_ROOT\/*/', '', $dir);
			$path = DOL_DATA_ROOT.'/'.$dir.'/'.$name;
			if (file_exists($path)) {
				return $path;
			}
		}

		$this->setError("ErrorAcroFormTemplateNotFound", $name);

		return '';
	}


	/**
	 *	Read the file that tells which value goes into which field of the form
	 *
	 *	@param	string				$template	Path of the PDF form
	 *	@return	?array<string,mixed>			Content of the file, null when it can not be read ($this->error is set)
	 */
	protected function readMap($template)
	{
		$path = $template.'.map.json';
		if (!file_exists($path)) {
			$this->setError("ErrorAcroFormMapNotFound", basename($path));
			return null;
		}

		$map = json_decode((string) file_get_contents($path), true);
		if (!is_array($map) || !isset($map['fields']) || !is_array($map['fields'])) {
			$this->setError("ErrorAcroFormMapNotValid", basename($path));
			return null;
		}

		return $map;
	}


	/**
	 *	Build the value of every field of the form, refusing anything the form would not
	 *	store as it is: a receipt silently truncated is worse than no receipt at all
	 *
	 *	@param	Don					$don			Donation object
	 *	@param	Translate			$outputlangs	Lang object for output language
	 *	@param	string				$currency		Currency code
	 *	@param	string				$template		Path of the PDF form
	 *	@param	array<string,mixed>	$map			Content of the map file
	 *	@return	?array<string,string>				Value of each field, null on error ($this->error is set)
	 */
	protected function buildValues($don, $outputlangs, $currency, $template, $map)
	{
		require_once DOL_DOCUMENT_ROOT.'/includes/fpdm/fpdm.php';

		$reader = new FPDM($template);
		$reader->useCheckboxParser = true;
		$fields = $reader->ListFields();

		$substitutions = $this->getSubstitutions($don, $outputlangs, $currency);

		$values = array();
		foreach ($map['fields'] as $name => $definition) {
			if (!isset($fields[$name])) {
				$this->setError("ErrorAcroFormFieldNotInForm", $name);
				return null;
			}
			if (!is_array($definition)) {
				continue;
			}

			// A checkbox or a radio button stores one of the tokens the form itself declares
			if (isset($definition['token'])) {
				$token = (string) $definition['token'];
				if ($token === '') {
					continue;
				}
				if (!empty($fields[$name]['options']) && !in_array($token, $fields[$name]['options'], true)) {
					$this->setError("ErrorAcroFormTokenNotInField", $token, $name);
					return null;
				}
				$values[$name] = $token;
				continue;
			}

			$value = make_substitutions((string) (isset($definition['value']) ? $definition['value'] : ''), $substitutions, $outputlangs);
			$value = $this->applyTransform($value, (string) (isset($definition['transform']) ? $definition['transform'] : ''), $outputlangs, $currency);

			if ($value === '') {
				continue;
			}

			$maxlen = isset($fields[$name]['maxlen']) ? (int) $fields[$name]['maxlen'] : 0;
			if ($maxlen > 0 && dol_strlen($value) > $maxlen) {
				$this->setError("ErrorAcroFormValueTooLong", $name, (string) dol_strlen($value), (string) $maxlen);
				return null;
			}

			$values[$name] = $value;
		}

		return $values;
	}


	/**
	 *	Return the values a receipt can show. The keys are the ones of the existing receipt
	 *	template of the module, so that what is known of one works into the other.
	 *
	 *	@param	Don			$don			Donation object
	 *	@param	Translate	$outputlangs	Lang object for output language
	 *	@param	string		$currency		Currency code
	 *	@return	array<string,string>		Value of each key
	 */
	protected function getSubstitutions($don, $outputlangs, $currency)
	{
		$substitutions = getCommonSubstitutionArray($outputlangs, 0, null, $don);

		$substitutions['__REF__'] = (string) $don->ref;
		$substitutions['__DATE__'] = dol_print_date($don->date, 'day', false, $outputlangs);
		$substitutions['__NOW__'] = dol_print_date(dol_now(), 'day', false, $outputlangs);
		$substitutions['__AMOUNT__'] = price($don->amount);
		$substitutions['__DONATOR_FIRSTNAME__'] = (string) $don->firstname;
		$substitutions['__DONATOR_LASTNAME__'] = (string) $don->lastname;
		$substitutions['__DONATOR_SOCIETE__'] = (string) $don->societe;
		$substitutions['__DONATOR_ADDRESS__'] = (string) $don->address;
		$substitutions['__DONATOR_ZIP__'] = (string) $don->zip;
		$substitutions['__DONATOR_TOWN__'] = (string) $don->town;
		$substitutions['__DONATION_MESSAGE__'] = getDolGlobalString('DONATION_MESSAGE');

		complete_substitutions_array($substitutions, $outputlangs, $don);

		return $substitutions;
	}


	/**
	 *	Apply to a value the change the field of the form asks for. The list is closed on
	 *	purpose: a form asks for the amount in words or for the number of a street in its own
	 *	box, and a free syntax would have to be learnt by whoever sets the form up.
	 *
	 *	@param	string		$value			Value to change
	 *	@param	string		$transform		Name of the change, empty for none
	 *	@param	Translate	$outputlangs	Lang object for output language
	 *	@param	string		$currency		Currency code
	 *	@return	string						Value to store into the field
	 */
	protected function applyTransform($value, $transform, $outputlangs, $currency)
	{
		switch ($transform) {
			case 'amountinwords':
				return dol_convertToWord((float) price2num($value), $outputlangs, $outputlangs->transnoentitiesnoconv("Currency".$currency), true);
			case 'upper':
				return dol_strtoupper($value);
			case 'digitsonly':
				return preg_replace('/[^0-9]/', '', $value);
			case 'streetnumber':
				return preg_match('/^\s*(\d+\s*(?:bis|ter|quater)?)\b/i', $value, $reg) ? trim($reg[1]) : '';
			case 'streetname':
				return trim((string) preg_replace('/^\s*\d+\s*(?:bis|ter|quater)?\s*/i', '', $value));
			default:
				return $value;
		}
	}
}
