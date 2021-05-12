<?php
/* Copyright (C) 2006-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *		\file       htdocs/core/modules/export/export_csv.modules.php
 *		\ingroup    export
 *		\brief      File of class to build exports with CSV format
 *		\author	    Laurent Destailleur
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/export/modules_export.php';

<<<<<<< HEAD
=======
// avoid timeout for big export
set_time_limit(0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

/**
 *	Class to build export files with format CSV
 */
class ExportCsv extends ModeleExports
{
<<<<<<< HEAD
	var $id;
	var $label;
	var $extension;
	var $version;

	var $label_lib;
	var $version_lib;

	var $separator;

	var $handle;    // Handle fichier
=======
	/**
	 * @var string ID ex: csv, tsv, excel...
	 */
	public $id;

	/**
     * @var string export files label
     */
    public $label;

	public $extension;

	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';

	public $label_lib;

	public $version_lib;

	public $separator;

	public $handle;    // Handle fichier
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


	/**
	 *	Constructor
	 *
	 *	@param	    DoliDB	$db      Database handler
	 */
<<<<<<< HEAD
	function __construct($db)
	{
		global $conf,$langs;
=======
	public function __construct($db)
	{
		global $conf, $langs;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->db = $db;

		$this->separator=',';
		if (! empty($conf->global->EXPORT_CSV_SEPARATOR_TO_USE)) $this->separator=$conf->global->EXPORT_CSV_SEPARATOR_TO_USE;
		$this->escape='"';
		$this->enclosure='"';

		$this->id='csv';                // Same value then xxx in file name export_xxx.modules.php
		$this->label = 'CSV';             // Label of driver
<<<<<<< HEAD
		$this->desc=$langs->trans("CSVFormatDesc",$this->separator,$this->enclosure,$this->escape);
=======
		$this->desc=$langs->trans("CSVFormatDesc", $this->separator, $this->enclosure, $this->escape);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->extension='csv';         // Extension for generated file by this driver
		$this->picto='mime/other';		// Picto
		$this->version='1.32';         // Driver version

		// If driver use an external library, put its name here
		$this->label_lib='Dolibarr';
		$this->version_lib=DOL_VERSION;
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}

	/**
	 * getDriverId
	 *
	 * @return string
	 */
<<<<<<< HEAD
	function getDriverId()
=======
	public function getDriverId()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->id;
	}

	/**
	 * getDriverLabel
	 *
	 * @return 	string			Return driver label
	 */
<<<<<<< HEAD
	function getDriverLabel()
=======
	public function getDriverLabel()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->label;
	}

	/**
	 * getDriverDesc
	 *
	 * @return string
	 */
<<<<<<< HEAD
	function getDriverDesc()
=======
	public function getDriverDesc()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->desc;
	}

	/**
	 * getDriverExtension
	 *
	 * @return string
	 */
<<<<<<< HEAD
	function getDriverExtension()
=======
	public function getDriverExtension()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->extension;
	}

	/**
	 * getDriverVersion
	 *
	 * @return string
	 */
<<<<<<< HEAD
	function getDriverVersion()
=======
	public function getDriverVersion()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->version;
	}

	/**
	 * getLabelLabel
	 *
	 * @return string
	 */
<<<<<<< HEAD
	function getLibLabel()
=======
	public function getLibLabel()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->label_lib;
	}

	/**
	 * getLibVersion
	 *
	 * @return string
	 */
<<<<<<< HEAD
	function getLibVersion()
=======
	public function getLibVersion()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->version_lib;
	}


<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 *	Open output file
	 *
	 *	@param		string		$file			Path of filename to generate
	 * 	@param		Translate	$outputlangs	Output language object
	 *	@return		int							<0 if KO, >=0 if OK
	 */
<<<<<<< HEAD
	function open_file($file,$outputlangs)
	{
=======
	public function open_file($file, $outputlangs)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		global $langs;

		dol_syslog("ExportCsv::open_file file=".$file);

		$ret=1;

		$outputlangs->load("exports");
		$this->handle = fopen($file, "wt");
		if (! $this->handle)
		{
			$langs->load("errors");
<<<<<<< HEAD
			$this->error=$langs->trans("ErrorFailToCreateFile",$file);
=======
			$this->error=$langs->trans("ErrorFailToCreateFile", $file);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			$ret=-1;
		}

		return $ret;
	}

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * 	Output header into file
	 *
	 * 	@param		Translate	$outputlangs	Output language object
	 * 	@return		int							<0 if KO, >0 if OK
	 */
<<<<<<< HEAD
	function write_header($outputlangs)
	{
=======
	public function write_header($outputlangs)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		return 0;
	}


<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * 	Output title line into file
	 *
     *  @param      array		$array_export_fields_label   	Array with list of label of fields
     *  @param      array		$array_selected_sorted       	Array with list of field to export
     *  @param      Translate	$outputlangs    				Object lang to translate values
     *  @param		array		$array_types					Array with types of fields
	 * 	@return		int											<0 if KO, >0 if OK
	 */
<<<<<<< HEAD
	function write_title($array_export_fields_label,$array_selected_sorted,$outputlangs,$array_types)
	{
=======
	public function write_title($array_export_fields_label, $array_selected_sorted, $outputlangs, $array_types)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		global $conf;

		if (! empty($conf->global->EXPORT_CSV_FORCE_CHARSET))
		{
			$outputlangs->charset_output = $conf->global->EXPORT_CSV_FORCE_CHARSET;
		}
		else
		{
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		foreach($array_selected_sorted as $code => $value)
		{
			$newvalue=$outputlangs->transnoentities($array_export_fields_label[$code]);		// newvalue is now $outputlangs->charset_output encoded
<<<<<<< HEAD
			$newvalue=$this->csvClean($newvalue,$outputlangs->charset_output);

			fwrite($this->handle,$newvalue.$this->separator);
		}
		fwrite($this->handle,"\n");
=======
			$newvalue=$this->csvClean($newvalue, $outputlangs->charset_output);

			fwrite($this->handle, $newvalue.$this->separator);
		}
		fwrite($this->handle, "\n");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		return 0;
	}


<<<<<<< HEAD
	/**
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     *	Output record line into file
     *
     *  @param     	array		$array_selected_sorted      Array with list of field to export
     *  @param     	resource	$objp                       A record from a fetch with all fields from select
     *  @param     	Translate	$outputlangs    			Object lang to translate values
     *  @param		array		$array_types				Array with types of fields
	 * 	@return		int										<0 if KO, >0 if OK
	 */
<<<<<<< HEAD
	function write_record($array_selected_sorted,$objp,$outputlangs,$array_types)
	{
=======
	public function write_record($array_selected_sorted, $objp, $outputlangs, $array_types)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		global $conf;

		if (! empty($conf->global->EXPORT_CSV_FORCE_CHARSET))
		{
			$outputlangs->charset_output = $conf->global->EXPORT_CSV_FORCE_CHARSET;
		}
		else
		{
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		$this->col=0;
<<<<<<< HEAD
		foreach($array_selected_sorted as $code => $value)
		{
			if (strpos($code,' as ') == 0) $alias=str_replace(array('.','-','(',')'),'_',$code);
			else $alias=substr($code, strpos($code, ' as ') + 4);
			if (empty($alias)) dol_print_error('','Bad value for field with key='.$code.'. Try to redefine export.');
=======

		$reg=array();

		foreach($array_selected_sorted as $code => $value)
		{
			if (strpos($code, ' as ') == 0) $alias=str_replace(array('.','-','(',')'), '_', $code);
			else $alias=substr($code, strpos($code, ' as ') + 4);
			if (empty($alias)) dol_print_error('', 'Bad value for field with key='.$code.'. Try to redefine export.');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

			$newvalue=$outputlangs->convToOutputCharset($objp->$alias);		// objp->$alias must be utf8 encoded as any var in memory	// newvalue is now $outputlangs->charset_output encoded
			$typefield=isset($array_types[$code])?$array_types[$code]:'';

			// Translation newvalue
<<<<<<< HEAD
			if (preg_match('/^\((.*)\)$/i',$newvalue,$reg)) $newvalue=$outputlangs->transnoentities($reg[1]);

			$newvalue=$this->csvClean($newvalue,$outputlangs->charset_output);
=======
			if (preg_match('/^\((.*)\)$/i', $newvalue, $reg)) $newvalue=$outputlangs->transnoentities($reg[1]);

			$newvalue=$this->csvClean($newvalue, $outputlangs->charset_output);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

			if (preg_match('/^Select:/i', $typefield, $reg) && $typefield = substr($typefield, 7))
			{
				$array = unserialize($typefield);
				$array = $array['options'];
				$newvalue = $array[$newvalue];
			}
<<<<<<< HEAD
			
			fwrite($this->handle,$newvalue.$this->separator);
			$this->col++;
		}

		fwrite($this->handle,"\n");
		return 0;
	}

=======

			fwrite($this->handle, $newvalue.$this->separator);
			$this->col++;
		}

		fwrite($this->handle, "\n");
		return 0;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * 	Output footer into file
	 *
	 * 	@param		Translate	$outputlangs	Output language object
	 * 	@return		int							<0 if KO, >0 if OK
	 */
<<<<<<< HEAD
	function write_footer($outputlangs)
	{
		return 0;
	}

=======
	public function write_footer($outputlangs)
	{
        // phpcs:enable
		return 0;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * 	Close file handle
	 *
	 * 	@return		int							<0 if KO, >0 if OK
	 */
<<<<<<< HEAD
	function close_file()
	{
=======
	public function close_file()
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		fclose($this->handle);
		return 0;
	}


	/**
	 * Clean a cell to respect rules of CSV file cells
	 * Note: It uses $this->separator
	 * Note: We keep this function public to be able to test
	 *
	 * @param 	string	$newvalue	String to clean
	 * @param	string	$charset	Input AND Output character set
	 * @return 	string				Value cleaned
	 */
	public function csvClean($newvalue, $charset)
	{
		global $conf;
		$addquote=0;
<<<<<<< HEAD
		
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		// Rule Dolibarr: No HTML
   		//print $charset.' '.$newvalue."\n";
   		//$newvalue=dol_string_nohtmltag($newvalue,0,$charset);
   		$newvalue=dol_htmlcleanlastbr($newvalue);
   		//print $charset.' '.$newvalue."\n";
<<<<<<< HEAD
		
		// Rule 1 CSV: No CR, LF in cells (except if USE_STRICT_CSV_RULES is on, we can keep record as it is but we must add quotes)
		$oldvalue=$newvalue;
		$newvalue=str_replace("\r",'',$newvalue);
		$newvalue=str_replace("\n",'\n',$newvalue);
=======

		// Rule 1 CSV: No CR, LF in cells (except if USE_STRICT_CSV_RULES is on, we can keep record as it is but we must add quotes)
		$oldvalue=$newvalue;
		$newvalue=str_replace("\r", '', $newvalue);
		$newvalue=str_replace("\n", '\n', $newvalue);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		if (! empty($conf->global->USE_STRICT_CSV_RULES) && $oldvalue != $newvalue)
		{
			// If strict use of CSV rules, we just add quote
			$newvalue=$oldvalue;
			$addquote=1;
		}
<<<<<<< HEAD
		
		// Rule 2 CSV: If value contains ", we must escape with ", and add "
		if (preg_match('/"/',$newvalue))
		{
			$addquote=1;
			$newvalue=str_replace('"','""',$newvalue);
		}

		// Rule 3 CSV: If value contains separator, we must add "
		if (preg_match('/'.$this->separator.'/',$newvalue))
=======

		// Rule 2 CSV: If value contains ", we must escape with ", and add "
		if (preg_match('/"/', $newvalue))
		{
			$addquote=1;
			$newvalue=str_replace('"', '""', $newvalue);
		}

		// Rule 3 CSV: If value contains separator, we must add "
		if (preg_match('/'.$this->separator.'/', $newvalue))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		{
			$addquote=1;
		}

		return ($addquote?'"':'').$newvalue.($addquote?'"':'');
	}
<<<<<<< HEAD

}

=======
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
