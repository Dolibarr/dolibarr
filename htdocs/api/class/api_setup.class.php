<?php
/* Copyright (C) 2016   Xebax Christy           <xebax@wanadoo.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2017	Regis Houssin	<regis.houssin@capnetworks.com>
 * Copyright (C) 2017	Neil Orley	<neil.orley@oeris.fr>
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ccountry.class.php';

/**
 * API class for dictionaries
 *
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class Setup extends DolibarrApi
{
    private $translations = null;

    /**
     * Constructor
     */
    function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /**
     * Get the list of payments types.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number {@min 0}
     * @param int       $active     Payment type is active or not {@min 0} {@max 1}
     * @param string    $sqlfilters SQL criteria to filter with. Syntax example "(t.code:=:'CHQ')"
     *
     * @url     GET dictionary/payment_types
     *
     * @return array [List of payment types]
     *
     * @throws 400 RestException
     * @throws 200 OK
     */
    function getPaymentTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
    {
        $list = array();

        $sql = "SELECT id, code, type, libelle as label, module";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_paiement as t";
        $sql.= " WHERE t.entity IN (".getEntity('c_paiement').")";
        $sql.= " AND t.active = ".$active;
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(400, 'error when validating parameter sqlfilters '.$sqlfilters);
            }
	          $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }


        $sql.= $this->db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit, $offset);
        }

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            for ($i = 0; $i < $min; $i++) {
                $list[] = $this->db->fetch_object($result);
            }
        } else {
            throw new RestException(400, $this->db->lasterror());
        }

        return $list;
    }

    /**
     * Get the list of countries.
     *
     * The names of the countries will be translated to the given language if
     * the $lang parameter is provided. The value of $lang must be a language
     * code supported by Dolibarr, for example 'en_US' or 'fr_FR'.
     * The returned list is sorted by country ID.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number (starting from zero)
     * @param string    $filter     To filter the countries by name
     * @param string    $lang       Code of the language the label of the countries must be translated to
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
     * @return List of countries
     *
     * @url     GET dictionary/countries
     *
     * @throws RestException
     */
    function getListOfCountries($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $filter = '', $lang = '', $sqlfilters = '')
    {
        $list = array();

        // Note: The filter is not applied in the SQL request because it must
        // be applied to the translated names, not to the names in database.
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_country as t";
        $sql.=" WHERE 1 = 1";
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $this->db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit, $offset);
        }

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            for ($i = 0; $i < $min; $i++) {
                $obj = $this->db->fetch_object($result);
                $country = new Ccountry($this->db);
                if ($country->fetch($obj->rowid) > 0) {
                    // Translate the name of the country if needed
                    // and then apply the filter if there is one.
                    $this->translateLabel($country, $lang);

                    if (empty($filter) || stripos($country->label, $filter) !== false) {
                        $list[] = $this->_cleanObjectDatas($country);
                    }
                }
            }
        } else {
            throw new RestException(503, 'Error when retrieving list of countries : '.$country->error);
        }

        return $list;
    }

    /**
     * Get country by ID.
     *
     * @param int       $id        ID of country
     * @param string    $lang      Code of the language the name of the
     *                             country must be translated to
     *
     * @url     GET dictionary/countries/{id}
     *
     * @throws RestException
     */
    function getCountryByID($id, $lang = '')
    {
        $country = new Ccountry($this->db);

        if ($country->fetch($id) < 0) {
            throw new RestException(503, 'Error when retrieving country : '.$country->error);
        }
        else if ($country->fetch($id) == 0) {
            throw new RestException(404, 'country not found');
        }

        $this->translateLabel($country, $lang);

        return $this->_cleanObjectDatas($country);
    }

    /**
     * Get the list of delivery times.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number {@min 0}
     * @param int       $active     Delivery times is active or not {@min 0} {@max 1}
     * @param string    $sqlfilters SQL criteria to filter with.
     *
     * @url     GET dictionary/availability
     *
     * @return array [List of availability]
     *
     * @throws 400 RestException
     * @throws 200 OK
     */
    function getAvailability($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
    {
        $list = array();

        $sql = "SELECT rowid, code, label";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_availability as t";
        $sql.= " WHERE t.active = ".$active;
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(400, 'error when validating parameter sqlfilters '.$sqlfilters);
            }
                  $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }


        $sql.= $this->db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit, $offset);
        }

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            for ($i = 0; $i < $min; $i++) {
                $list[] = $this->db->fetch_object($result);
            }
        } else {
            throw new RestException(400, $this->db->lasterror());
        }

        return $list;
    }

    /**
     * Clean sensible object datas
     *
     * @param object    $object    Object to clean
     * @return array 				Array of cleaned object properties
     */
    function _cleanObjectDatas($object)
    {
        $object = parent::_cleanObjectDatas($object);

        unset($object->error);
        unset($object->errors);

        return $object;
    }

    /**
     * Translate the name of the country to the given language.
     *
     * @param Ccountry $country   Country
     * @param string   $lang      Code of the language the name of the
     *                            country must be translated to
     */
    private function translateLabel($country, $lang)
    {
        if (!empty($lang)) {
            // Load the translations if this is a new language.
            if ($this->translations == null || $this->translations->getDefaultLang() !== $lang) {
                global $conf;
                $this->translations = new Translate('', $conf);
                $this->translations->setDefaultLang($lang);
                $this->translations->load('dict');
            }
            if ($country->code) {
                $key = 'Country'.$country->code;
                $translation = $this->translations->trans($key);
                if ($translation != $key) {
                    $country->label = html_entity_decode($translation);
                }
            }
        }
    }

    /**
     * Get the list of events types.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number (starting from zero)
     * @param string    $type       To filter on type of event
     * @param string    $module     To filter on module events
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
     * @return List of events types
     *
     * @url     GET dictionary/event_types
     *
     * @throws RestException
     */
    function getListOfEventTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $type = '', $module = '', $sqlfilters = '')
    {
        $list = array();

        $sql = "SELECT id, code, type, libelle as label, module";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as t";
        $sql.= " WHERE t.active = 1";
        if ($type) $sql.=" AND t.type LIKE '%" . $this->db->escape($type) . "%'";
        if ($module)    $sql.=" AND t.module LIKE '%" . $this->db->escape($module) . "%'";
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }


        $sql.= $this->db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit, $offset);
        }

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            for ($i = 0; $i < $min; $i++) {
                $list[] = $this->db->fetch_object($result);
            }
        } else {
            throw new RestException(503, 'Error when retrieving list of events types : '.$this->db->lasterror());
        }

        return $list;
    }


    /**
     * Get the list of extra fields.
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param string    $type       Type of element ('adherent', 'commande', 'thirdparty', 'facture', 'propal', 'product', ...)
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.label:like:'SO-%')"
     * @return List of extra fields
     *
     * @url     GET extrafields
     *
     * @throws RestException
     */
    function getListOfExtrafields($sortfield = "t.pos", $sortorder = 'ASC', $type = '', $sqlfilters = '')
    {
        $list = array();

        if ($type == 'thirdparty') $type='societe';
        if ($type == 'contact') $type='socpeople';

        $sql = "SELECT t.rowid, t.name, t.label, t.type, t.size, t.elementtype, t.fieldunique, t.fieldrequired, t.param, t.pos, t.alwayseditable, t.perms, t.list, t.fielddefault, t.fieldcomputed";
        $sql.= " FROM ".MAIN_DB_PREFIX."extrafields as t";
        $sql.= " WHERE t.entity IN (".getEntity('extrafields').")";
        if (! empty($type)) $sql.= " AND t.elementtype = '".$this->db->escape($type)."'";
        // Add sql filters
        if ($sqlfilters)
        {
        	if (! DolibarrApi::_checkFilters($sqlfilters))
        	{
        		throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
        	}
        	$regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
        	$sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $this->db->order($sortfield, $sortorder);

        $resql=$this->db->query($sql);
        if ($resql)
        {
        	if ($this->db->num_rows($resql))
        	{
        		while ($tab = $this->db->fetch_object($resql))
        		{
        			// New usage
        			$list[$tab->elementtype][$tab->name]['type']=$tab->type;
        			$list[$tab->elementtype][$tab->name]['label']=$tab->label;
        			$list[$tab->elementtype][$tab->name]['size']=$tab->size;
        			$list[$tab->elementtype][$tab->name]['elementtype']=$tab->elementtype;
        			$list[$tab->elementtype][$tab->name]['default']=$tab->fielddefault;
        			$list[$tab->elementtype][$tab->name]['computed']=$tab->fieldcomputed;
        			$list[$tab->elementtype][$tab->name]['unique']=$tab->fieldunique;
        			$list[$tab->elementtype][$tab->name]['required']=$tab->fieldrequired;
        			$list[$tab->elementtype][$tab->name]['param']=($tab->param ? unserialize($tab->param) : '');
        			$list[$tab->elementtype][$tab->name]['pos']=$tab->pos;
        			$list[$tab->elementtype][$tab->name]['alwayseditable']=$tab->alwayseditable;
        			$list[$tab->elementtype][$tab->name]['perms']=$tab->perms;
        			$list[$tab->elementtype][$tab->name]['list']=$tab->list;
        		}
        	}
        }
        else
        {
            throw new RestException(503, 'Error when retrieving list of extra fields : '.$this->db->lasterror());
        }

        if (! count($list))
        {
        	throw new RestException(404, 'No extrafield found');
        }

        return $list;
    }


    /**
     * Get the list of towns.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number (starting from zero)
     * @param string    $zipcode    To filter on zipcode
     * @param string    $town       To filter on city name
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
     * @return List of towns
     *
     * @url     GET dictionary/towns
     *
     * @throws RestException
     */
    function getListOfTowns($sortfield = "zip,town", $sortorder = 'ASC', $limit = 100, $page = 0, $zipcode = '', $town = '', $sqlfilters = '')
    {
        $list = array();

        $sql = "SELECT rowid AS id, zip, town, fk_county, fk_pays AS fk_country";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_ziptown as t";
        $sql.= " WHERE t.active = 1";
        if ($zipcode) $sql.=" AND t.zip LIKE '%" . $this->db->escape($zipcode) . "%'";
        if ($town)    $sql.=" AND t.town LIKE '%" . $this->db->escape($town) . "%'";
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }


        $sql.= $this->db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit, $offset);
        }

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            for ($i = 0; $i < $min; $i++) {
                $list[] = $this->db->fetch_object($result);
            }
        } else {
            throw new RestException(503, 'Error when retrieving list of towns : '.$this->db->lasterror());
        }

        return $list;
    }

    /**
     * Get the list of payments terms.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number {@min 0}
     * @param int       $active     Payment term is active or not {@min 0} {@max 1}
     * @param string    $sqlfilters SQL criteria to filter. Syntax example "(t.code:=:'CHQ')"
     *
     * @url     GET dictionary/payment_terms
     *
     * @return array List of payment terms
     *
     * @throws 400 RestException
     * @throws 200 OK
     */
    function getPaymentTerms($sortfield = "sortorder", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
    {
        $list = array();

        $sql = "SELECT rowid as id, code, sortorder, libelle as label, libelle_facture as descr, type_cdr, nbjour, decalage, module";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_payment_term as t";
        $sql.= " WHERE t.entity IN (".getEntity('c_payment_term').")";
        $sql.= " AND t.active = ".$active;
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(400, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
                $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }


        $sql.= $this->db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit, $offset);
        }

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            for ($i = 0; $i < $min; $i++) {
                $list[] = $this->db->fetch_object($result);
            }
        } else {
            throw new RestException(400, $this->db->lasterror());
        }

        return $list;
    }


    /**
     * Do a test of integrity for files and setup.
     *
     * @param string	$target			Can be 'local' or 'default' or Url of the signatures file to use for the test. Must be reachable by the tested Dolibarr.
     * @return 							Result of file and setup integrity check
     *
     * @url     GET checkintegrity
     *
     * @throws RestException
     */
    function getCheckIntegrity($target)
    {
    	global $langs, $conf;

    	if (! DolibarrApiAccess::$user->admin
    		&& (empty($conf->global->API_LOGIN_ALLOWED_FOR_INTEGRITY_CHECK) || DolibarrApiAccess::$user->login != $conf->global->API_LOGIN_ALLOWED_FOR_INTEGRITY_CHECK))
    	{
    		throw new RestException(503, 'Error API open to admin users only or to login user defined with constant API_LOGIN_ALLOWED_FOR_INTEGRITY_CHECK');
    	}

    	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    	require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

    	$langs->load("admin");

    	$outexpectedchecksum = '';
    	$outcurrentchecksum = '';

    	// Modified or missing files
    	$file_list = array('missing' => array(), 'updated' => array());

    	// Local file to compare to
    	$xmlshortfile = GETPOST('xmlshortfile')?GETPOST('xmlshortfile'):'/install/filelist-'.DOL_VERSION.'.xml';
    	$xmlfile = DOL_DOCUMENT_ROOT.$xmlshortfile;
    	// Remote file to compare to
    	$xmlremote = ($target == 'default' ? '' : $target);
    	if (empty($xmlremote) && ! empty($conf->global->MAIN_FILECHECK_URL)) $xmlremote = $conf->global->MAIN_FILECHECK_URL;
    	$param='MAIN_FILECHECK_URL_'.DOL_VERSION;
    	if (empty($xmlremote) && ! empty($conf->global->$param)) $xmlremote = $conf->global->$param;
    	if (empty($xmlremote)) $xmlremote = 'https://www.dolibarr.org/files/stable/signatures/filelist-'.DOL_VERSION.'.xml';

    	if ($target == 'local')
    	{
    		if (dol_is_file($xmlfile))
    		{
    			$xml = simplexml_load_file($xmlfile);
    		}
    		else
    		{
    			throw new RestException(500, $langs->trans('XmlNotFound') . ': ' . $xmlfile);
    		}
    	}
    	else
    	{
    		$xmlarray = getURLContent($xmlremote);

    		// Return array('content'=>response,'curl_error_no'=>errno,'curl_error_msg'=>errmsg...)
    		if (! $xmlarray['curl_error_no'] && $xmlarray['http_code'] != '404')
    		{
    			$xmlfile = $xmlarray['content'];
    			//print "xmlfilestart".$xmlfile."endxmlfile";
    			$xml = simplexml_load_string($xmlfile);
    		}
    		else
    		{
    			$errormsg=$langs->trans('XmlNotFound') . ': ' . $xmlremote.' - '.$xmlarray['http_code'].' '.$xmlarray['curl_error_no'].' '.$xmlarray['curl_error_msg'];
    			throw new RestException(500, $errormsg);
    		}
    	}



    	if ($xml)
    	{
    		$checksumconcat = array();
    		$file_list = array();
    		$out = '';

    		// Forced constants
    		if (is_object($xml->dolibarr_constants[0]))
    		{
    			$out.=load_fiche_titre($langs->trans("ForcedConstants"));

    			$out.='<div class="div-table-responsive-no-min">';
    			$out.='<table class="noborder">';
    			$out.='<tr class="liste_titre">';
    			$out.='<td>#</td>';
    			$out.='<td>' . $langs->trans("Constant") . '</td>';
    			$out.='<td align="center">' . $langs->trans("ExpectedValue") . '</td>';
    			$out.='<td align="center">' . $langs->trans("Value") . '</td>';
    			$out.='</tr>'."\n";

    			$i = 0;
    			foreach ($xml->dolibarr_constants[0]->constant as $constant)    // $constant is a simpleXMLElement
    			{
    				$constname=$constant['name'];
    				$constvalue=(string) $constant;
    				$constvalue = (empty($constvalue)?'0':$constvalue);
    				// Value found
    				$value='';
    				if ($constname && $conf->global->$constname != '') $value=$conf->global->$constname;
    				$valueforchecksum=(empty($value)?'0':$value);

    				$checksumconcat[]=$valueforchecksum;

    				$i++;
    				$out.='<tr class="oddeven">';
    				$out.='<td>'.$i.'</td>' . "\n";
    				$out.='<td>'.$constname.'</td>' . "\n";
    				$out.='<td align="center">'.$constvalue.'</td>' . "\n";
    				$out.='<td align="center">'.$valueforchecksum.'</td>' . "\n";
    				$out.="</tr>\n";
    			}

    			if ($i==0)
    			{
    				$out.='<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
    			}
    			$out.='</table>';
    			$out.='</div>';

    			$out.='<br>';
    		}

    		// Scan htdocs
    		if (is_object($xml->dolibarr_htdocs_dir[0]))
    		{
    			//var_dump($xml->dolibarr_htdocs_dir[0]['includecustom']);exit;
    			$includecustom=(empty($xml->dolibarr_htdocs_dir[0]['includecustom'])?0:$xml->dolibarr_htdocs_dir[0]['includecustom']);

    			// Defined qualified files (must be same than into generate_filelist_xml.php)
    			$regextoinclude='\.(php|php3|php4|php5|phtml|phps|phar|inc|css|scss|html|xml|js|json|tpl|jpg|jpeg|png|gif|ico|sql|lang|txt|yml|bak|md|mp3|mp4|wav|mkv|z|gz|zip|rar|tar|less|svg|eot|woff|woff2|ttf|manifest)$';
    			$regextoexclude='('.($includecustom?'':'custom|').'documents|conf|install|public\/test|Shared\/PCLZip|nusoap\/lib\/Mail|php\/example|php\/test|geoip\/sample.*\.php|ckeditor\/samples|ckeditor\/adapters)$';  // Exclude dirs
    			$scanfiles = dol_dir_list(DOL_DOCUMENT_ROOT, 'files', 1, $regextoinclude, $regextoexclude);

    			// Fill file_list with files in signature, new files, modified files
    			$ret = getFilesUpdated($file_list, $xml->dolibarr_htdocs_dir[0], '', DOL_DOCUMENT_ROOT, $checksumconcat, $scanfiles);		// Fill array $file_list
    			// Complete with list of new files
    			foreach ($scanfiles as $keyfile => $valfile)
    			{
    				$tmprelativefilename=preg_replace('/^'.preg_quote(DOL_DOCUMENT_ROOT,'/').'/','', $valfile['fullname']);
    				if (! in_array($tmprelativefilename, $file_list['insignature']))
    				{
    					$md5newfile=@md5_file($valfile['fullname']);    // Can fails if we don't have permission to open/read file
    					$file_list['added'][]=array('filename'=>$tmprelativefilename, 'md5'=>$md5newfile);
    				}
    			}

    			// Files missings
    			$out.=load_fiche_titre($langs->trans("FilesMissing"));

    			$out.='<div class="div-table-responsive-no-min">';
    			$out.='<table class="noborder">';
    			$out.='<tr class="liste_titre">';
    			$out.='<td>#</td>';
    			$out.='<td>' . $langs->trans("Filename") . '</td>';
    			$out.='<td align="center">' . $langs->trans("ExpectedChecksum") . '</td>';
    			$out.='</tr>'."\n";
    			$tmpfilelist = dol_sort_array($file_list['missing'], 'filename');
    			if (is_array($tmpfilelist) && count($tmpfilelist))
    			{
    				$i = 0;
    				foreach ($tmpfilelist as $file)
    				{
    					$i++;
    					$out.='<tr class="oddeven">';
    					$out.='<td>'.$i.'</td>' . "\n";
    					$out.='<td>'.$file['filename'].'</td>' . "\n";
    					$out.='<td align="center">'.$file['expectedmd5'].'</td>' . "\n";
    					$out.="</tr>\n";
    				}
    			}
    			else
    			{
    				$out.='<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
    			}
    			$out.='</table>';
    			$out.='</div>';

    			$out.='<br>';

    			// Files modified
    			$out.=load_fiche_titre($langs->trans("FilesModified"));

    			$totalsize=0;
    			$out.='<div class="div-table-responsive-no-min">';
    			$out.='<table class="noborder">';
    			$out.='<tr class="liste_titre">';
    			$out.='<td>#</td>';
    			$out.='<td>' . $langs->trans("Filename") . '</td>';
    			$out.='<td align="center">' . $langs->trans("ExpectedChecksum") . '</td>';
    			$out.='<td align="center">' . $langs->trans("CurrentChecksum") . '</td>';
    			$out.='<td align="right">' . $langs->trans("Size") . '</td>';
    			$out.='<td align="right">' . $langs->trans("DateModification") . '</td>';
    			$out.='</tr>'."\n";
    			$tmpfilelist2 = dol_sort_array($file_list['updated'], 'filename');
    			if (is_array($tmpfilelist2) && count($tmpfilelist2))
    			{
    				$i = 0;
    				foreach ($tmpfilelist2 as $file)
    				{
    					$i++;
    					$out.='<tr class="oddeven">';
    					$out.='<td>'.$i.'</td>' . "\n";
    					$out.='<td>'.$file['filename'].'</td>' . "\n";
    					$out.='<td align="center">'.$file['expectedmd5'].'</td>' . "\n";
    					$out.='<td align="center">'.$file['md5'].'</td>' . "\n";
    					$size = dol_filesize(DOL_DOCUMENT_ROOT.'/'.$file['filename']);
    					$totalsize += $size;
    					$out.='<td align="right">'.dol_print_size($size).'</td>' . "\n";
    					$out.='<td align="right">'.dol_print_date(dol_filemtime(DOL_DOCUMENT_ROOT.'/'.$file['filename']),'dayhour').'</td>' . "\n";
    					$out.="</tr>\n";
    				}
    				$out.='<tr class="liste_total">';
    				$out.='<td></td>' . "\n";
    				$out.='<td>'.$langs->trans("Total").'</td>' . "\n";
    				$out.='<td align="center"></td>' . "\n";
    				$out.='<td align="center"></td>' . "\n";
    				$out.='<td align="right">'.dol_print_size($totalsize).'</td>' . "\n";
    				$out.='<td align="right"></td>' . "\n";
    				$out.="</tr>\n";
    			}
    			else
    			{
    				$out.='<tr class="oddeven"><td colspan="5" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
    			}
    			$out.='</table>';
    			$out.='</div>';

    			$out.='<br>';

    			// Files added
    			$out.=load_fiche_titre($langs->trans("FilesAdded"));

    			$totalsize = 0;
    			$out.='<div class="div-table-responsive-no-min">';
    			$out.='<table class="noborder">';
    			$out.='<tr class="liste_titre">';
    			$out.='<td>#</td>';
    			$out.='<td>' . $langs->trans("Filename") . '</td>';
    			$out.='<td align="center">' . $langs->trans("ExpectedChecksum") . '</td>';
    			$out.='<td align="center">' . $langs->trans("CurrentChecksum") . '</td>';
    			$out.='<td align="right">' . $langs->trans("Size") . '</td>';
    			$out.='<td align="right">' . $langs->trans("DateModification") . '</td>';
    			$out.='</tr>'."\n";
    			$tmpfilelist3 = dol_sort_array($file_list['added'], 'filename');
    			if (is_array($tmpfilelist3) && count($tmpfilelist3))
    			{
    				$i = 0;
    				foreach ($tmpfilelist3 as $file)
    				{
    					$i++;
    					$out.='<tr class="oddeven">';
    					$out.='<td>'.$i.'</td>' . "\n";
    					$out.='<td>'.$file['filename'].'</td>' . "\n";
    					$out.='<td align="center">'.$file['expectedmd5'].'</td>' . "\n";
    					$out.='<td align="center">'.$file['md5'].'</td>' . "\n";
    					$size = dol_filesize(DOL_DOCUMENT_ROOT.'/'.$file['filename']);
    					$totalsize += $size;
    					$out.='<td align="right">'.dol_print_size($size).'</td>' . "\n";
    					$out.='<td align="right">'.dol_print_date(dol_filemtime(DOL_DOCUMENT_ROOT.'/'.$file['filename']),'dayhour').'</td>' . "\n";
    					$out.="</tr>\n";
    				}
    				$out.='<tr class="liste_total">';
    				$out.='<td></td>' . "\n";
    				$out.='<td>'.$langs->trans("Total").'</td>' . "\n";
    				$out.='<td align="center"></td>' . "\n";
    				$out.='<td align="center"></td>' . "\n";
    				$out.='<td align="right">'.dol_print_size($totalsize).'</td>' . "\n";
    				$out.='<td align="right"></td>' . "\n";
    				$out.="</tr>\n";
    			}
    			else
    			{
    				$out.='<tr class="oddeven"><td colspan="5" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
    			}
    			$out.='</table>';
    			$out.='</div>';


    			// Show warning
    			if (empty($tmpfilelist) && empty($tmpfilelist2) && empty($tmpfilelist3))
    			{
    				//setEventMessage($langs->trans("FileIntegrityIsStrictlyConformedWithReference"));
    			}
    			else
    			{
    				//setEventMessage($langs->trans("FileIntegritySomeFilesWereRemovedOrModified"), 'warnings');
    			}
    		}
    		else
    		{
    			throw new RestException(500, 'Error: Failed to found dolibarr_htdocs_dir into XML file '.$xmlfile);
    		}


    		// Scan scripts


    		asort($checksumconcat); // Sort list of checksum
    		//var_dump($checksumconcat);
    		$checksumget = md5(join(',',$checksumconcat));
    		$checksumtoget = trim((string) $xml->dolibarr_htdocs_dir_checksum);

    		$outexpectedchecksum = ($checksumtoget ? $checksumtoget : $langs->trans("Unknown"));
    		if ($checksumget == $checksumtoget)
    		{
    			if (count($file_list['added']))
    			{
    				$resultcode = 'warning';
    				$resultcomment='FileIntegrityIsOkButFilesWereAdded';
    				//$outcurrentchecksum =  $checksumget.' - <span class="'.$resultcode.'">'.$langs->trans("FileIntegrityIsOkButFilesWereAdded").'</span>';
    				$outcurrentchecksum =  $checksumget;
    			}
    			else
    			{
    				$resultcode = 'ok';
    				$resultcomment='Success';
    				//$outcurrentchecksum = '<span class="'.$resultcode.'">'.$checksumget.'</span>';
    				$outcurrentchecksum =  $checksumget;
    			}
    		}
    		else
    		{
    			$resultcode = 'error';
    			$resultcomment='Error';
    			//$outcurrentchecksum = '<span class="'.$resultcode.'">'.$checksumget.'</span>';
    			$outcurrentchecksum =  $checksumget;
    		}
    	}
    	else {
    		throw new RestException(404, 'No signature file known');
    	}

    	return array('resultcode'=>$resultcode, 'resultcomment'=>$resultcomment, 'expectedchecksum'=> $outexpectedchecksum, 'currentchecksum'=> $outcurrentchecksum, 'out'=>$out);
    }

}
