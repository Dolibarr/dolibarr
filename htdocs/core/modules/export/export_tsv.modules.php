<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
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
 *		\brief      File of class to build export files with format TSV
 *		\author	    Laurent Destailleur
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/export/modules_export.php';


/**
 *	Class to build export files with format TSV
 */
class ExportTsv extends ModeleExports
{
<<<<<<< HEAD
    var $id;
    var $label;
    var $extension;
    var $version;

    var $label_lib;
    var $version_lib;

    var $separator="\t";

    var $handle;    // Handle fichier


    /**
	 *	Constructor
	 *
	 *	@param	    DoliDB	$db      Database handler
     */
    function __construct($db)
=======
    /**
	 * @var string ID
	 */
	public $id;

    /**
     * @var string label
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

    public $separator="\t";

    public $handle;    // Handle fichier


    /**
     *  Constructor
     *
     *  @param      DoliDB	$db      Database handler
     */
    public function __construct($db)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        global $conf, $langs;
        $this->db = $db;

        $this->id='tsv';                // Same value then xxx in file name export_xxx.modules.php
        $this->label = 'TSV';             // Label of driver
        $this->desc = $langs->trans('TsvFormatDesc');
        $this->extension='tsv';         // Extension for generated file by this driver
        $this->picto='mime/other';		// Picto
        $this->version='1.15';         // Driver version

        // If driver use an external library, put its name here
        $this->label_lib='Dolibarr';
        $this->version_lib=DOL_VERSION;
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
	 * getLibLabel
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
    /**
	*	Open output file
	*
	 *	@param		string		$file			Path of filename to generate
	*	@param		Translate	$outputlangs	Output language object
	*	@return		int							<0 if KO, >=0 if OK
	*/
	function open_file($file,$outputlangs)
    {
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *   Open output file
     *
     *  @param      string		$file			Path of filename to generate
     *  @param      Translate	$outputlangs	Output language object
     *  @return     int							<0 if KO, >=0 if OK
     */
    public function open_file($file, $outputlangs)
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        global $langs;

        dol_syslog("ExportTsv::open_file file=".$file);

<<<<<<< HEAD
		$ret=1;
=======
        $ret=1;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
	 * 	@param		Translate	$outputlangs		Output language object
	 * 	@return		int								<0 if KO, >0 if OK
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
	/**
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     *  Output title line into file
     *
     *  @param      array		$array_export_fields_label   	Array with list of label of fields
     *  @param      array		$array_selected_sorted       	Array with list of field to export
     *  @param      Translate	$outputlangs    				Object lang to translate values
     *  @param		array		$array_types					Array with types of fields
	 * 	@return		int											<0 if KO, >0 if OK
<<<<<<< HEAD
	 */
    function write_title($array_export_fields_label,$array_selected_sorted,$outputlangs,$array_types)
    {
        foreach($array_selected_sorted as $code => $value)
        {
            $newvalue=$outputlangs->transnoentities($array_export_fields_label[$code]);		// newvalue is now $outputlangs->charset_output encoded
			$newvalue=$this->tsv_clean($newvalue,$outputlangs->charset_output);

			fwrite($this->handle,$newvalue.$this->separator);
        }
        fwrite($this->handle,"\n");
=======
     */
    public function write_title($array_export_fields_label, $array_selected_sorted, $outputlangs, $array_types)
    {
        // phpcs:enable
        foreach($array_selected_sorted as $code => $value)
        {
            $newvalue=$outputlangs->transnoentities($array_export_fields_label[$code]);		// newvalue is now $outputlangs->charset_output encoded
			$newvalue=$this->tsv_clean($newvalue, $outputlangs->charset_output);

			fwrite($this->handle, $newvalue.$this->separator);
        }
        fwrite($this->handle, "\n");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        return 0;
    }


<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * 	Output record line into file
	 *
	 *  @param      array		$array_selected_sorted      Array with list of field to export
	 *  @param      resource	$objp                       A record from a fetch with all fields from select
	 *  @param      Translate	$outputlangs                Object lang to translate values
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

		$this->col=0;
 		foreach($array_selected_sorted as $code => $value)
        {
<<<<<<< HEAD
			if (strpos($code,' as ') == 0) $alias=str_replace(array('.','-','(',')'),'_',$code);
			else $alias=substr($code, strpos($code, ' as ') + 4);
            if (empty($alias)) dol_print_error('','Bad value for field with code='.$code.'. Try to redefine export.');
=======
			if (strpos($code, ' as ') == 0) $alias=str_replace(array('.','-','(',')'), '_', $code);
			else $alias=substr($code, strpos($code, ' as ') + 4);
            if (empty($alias)) dol_print_error('', 'Bad value for field with code='.$code.'. Try to redefine export.');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

            $newvalue=$outputlangs->convToOutputCharset($objp->$alias);		// objp->$alias must be utf8 encoded as any var in memory // newvalue is now $outputlangs->charset_output encoded
            $typefield=isset($array_types[$code])?$array_types[$code]:'';

            // Translation newvalue
<<<<<<< HEAD
			if (preg_match('/^\((.*)\)$/i',$newvalue,$reg)) $newvalue=$outputlangs->transnoentities($reg[1]);

			$newvalue=$this->tsv_clean($newvalue,$outputlangs->charset_output);
			
=======
			if (preg_match('/^\((.*)\)$/i', $newvalue, $reg)) $newvalue=$outputlangs->transnoentities($reg[1]);

			$newvalue=$this->tsv_clean($newvalue, $outputlangs->charset_output);

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
	 * 	@param		Translate	$outputlangs		Output language object
	 * 	@return		int								<0 if KO, >0 if OK
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

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * Clean a cell to respect rules of TSV file cells
     *
     * @param 	string	$newvalue	String to clean
<<<<<<< HEAD
	 * @param	string	$charset	Input AND Output character set
     * @return 	string				Value cleaned
     */
    function tsv_clean($newvalue, $charset)
    {
		// Rule Dolibarr: No HTML
		$newvalue=dol_string_nohtmltag($newvalue, 1, $charset);

		// Rule 1 TSV: No CR, LF in cells
    	$newvalue=str_replace("\r",'',$newvalue);
        $newvalue=str_replace("\n",'\n',$newvalue);

        // Rule 2 TSV: If value contains tab, we must replace by space
		if (preg_match('/'.$this->separator.'/',$newvalue))
		{
			$newvalue=str_replace("\t"," ",$newvalue);
		}

    	return $newvalue;
    }

}

=======
     * @param	string	$charset	Input AND Output character set
     * @return 	string				Value cleaned
     */
    public function tsv_clean($newvalue, $charset)
    {
        // phpcs:enable
        // Rule Dolibarr: No HTML
        $newvalue=dol_string_nohtmltag($newvalue, 1, $charset);

        // Rule 1 TSV: No CR, LF in cells
        $newvalue=str_replace("\r", '', $newvalue);
        $newvalue=str_replace("\n", '\n', $newvalue);

        // Rule 2 TSV: If value contains tab, we must replace by space
        if (preg_match('/'.$this->separator.'/', $newvalue)) {
            $newvalue=str_replace("\t", " ", $newvalue);
        }

        return $newvalue;
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
