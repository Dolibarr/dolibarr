<?php
/* Copyright (C) 2026		Laurent Destailleur		<eldy@users.sourceforge.net>
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

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
require_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website2.lib.php';

/**
 * \file    htdocs/website/class/api_websites.class.php
 * \ingroup website
 * \brief   File for API management of websites.
 */

/**
 * API class for websites
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Websites extends DolibarrApi
{
	/**
	 * @var Website {@type Website}
	 */
	public $website;

	/**
	 * @var WebsitePage {@type WebsitePage}
	 */
	public $websitepage;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;

		$this->db = $db;
		$this->website = new Website($this->db);
		$this->websitepage = new WebsitePage($this->db);
	}

	/**
	 * Get properties of a website object by id
	 *
	 * Return an array with website information.
	 *
	 * @param   int     $id             ID of website
	 * @return  array|mixed             Properties of website
	 * @phan-return Website
	 * @phpstan-return Website
	 *
	 * @url     GET {id}
	 *
	 * @throws  RestException   403     Access denied
	 * @throws  RestException   404     Website not found
	 */
	public function get($id)
	{
		return $this->_fetchWebsite($id);
	}

	/**
	 * Get properties of a website object by ref
	 *
	 * Return an array with website information.
	 *
	 * @param   string  $ref            Ref of website
	 * @return  array|mixed             Properties of website
	 * @phan-return Website
	 * @phpstan-return Website
	 *
	 * @url     GET ref/{ref}
	 *
	 * @throws  RestException   403     Access denied
	 * @throws  RestException   404     Website not found
	 */
	public function getByRef($ref)
	{
		return $this->_fetchWebsite(0, $ref);
	}

	/**
	 * List websites
	 *
	 * Get a list of websites
	 *
	 * @param   string  $sortfield      Sort field
	 * @param   string  $sortorder      Sort order
	 * @param   int     $limit          Limit for list
	 * @param   int     $page           Page number
	 * @param   string  $sqlfilters     Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%')"
	 * @param   string  $properties     Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return  array                   Array of website objects
	 * @phan-return Website[]
	 * @phpstan-return Website[]
	 *
	 * @throws  RestException   400     Bad sqlfilters
	 * @throws  RestException   403     Access denied
	 * @throws  RestException   503     Error retrieving list of websites
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('website', 'read')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		$sql = "SELECT t.rowid, t.ref";
		$sql .= " FROM ".$this->db->prefix()."website as t";
		$sql .= " WHERE t.entity IN (".getEntity('website').")";

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		$sqlTotals = str_replace('SELECT t.rowid, t.ref', 'SELECT count(t.rowid) as total', $sql);

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$website_static = new Website($this->db);
				if ($website_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($website_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve website list : '.$this->db->lasterror());
		}

		// If pagination data is requested (page > 0), the response will contain element data with all values and element pagination with pagination data
		if ($page > 0) {
			$totalsResult = $this->db->query($sqlTotals);
			$total = $this->db->fetch_object($totalsResult)->total;

			$tmp = $obj_ret;
			$obj_ret = array();

			$obj_ret['data'] = $tmp;
			$obj_ret['pagination'] = array(
				'total' => (int) $total,
				'page' => $page,
				'page_count' => (int) ceil((int) $total / $limit),
				'limit' => $limit
			);
		}

		return $obj_ret;
	}

	/**
	 * List pages of a website
	 *
	 * Get a list of pages for a given website
	 *
	 * @param   int     $id             ID of website
	 * @param   string  $sortfield      Sort field
	 * @param   string  $sortorder      Sort order
	 * @param   int     $limit          Limit for list
	 * @param   int     $page           Page number
	 * @param   string  $sqlfilters     Other criteria to filter answers separated by a comma. Syntax example "(t.pageurl:like:'home')"
	 * @param   string  $properties     Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return  array                   Array of website page objects
	 * @phan-return WebsitePage[]
	 * @phpstan-return WebsitePage[]
	 *
	 * @url     GET {id}/pages
	 *
	 * @throws  RestException   400     Bad sqlfilters
	 * @throws  RestException   403     Access denied
	 * @throws  RestException   404     Website not found
	 * @throws  RestException   503     Error retrieving list of pages
	 */
	public function indexPages($id, $sortfield = "t.pageurl", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('website', 'read')) {
			throw new RestException(403);
		}

		$result = $this->website->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Website not found');
		}

		if (!DolibarrApi::_checkAccessToResource('website', $this->website->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$obj_ret = array();

		$sql = "SELECT t.rowid, t.pageurl";
		$sql .= " FROM ".$this->db->prefix()."website_page as t";
		$sql .= " WHERE t.fk_website = ".((int) $id);

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		$sqlTotals = str_replace('SELECT t.rowid, t.pageurl', 'SELECT count(t.rowid) as total', $sql);

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$page_static = new WebsitePage($this->db);
				if ($page_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($page_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve page list : '.$this->db->lasterror());
		}

		if ($page > 0) {
			$totalsResult = $this->db->query($sqlTotals);
			$total = $this->db->fetch_object($totalsResult)->total;

			$tmp = $obj_ret;
			$obj_ret = array();

			$obj_ret['data'] = $tmp;
			$obj_ret['pagination'] = array(
				'total' => (int) $total,
				'page' => $page,
				'page_count' => (int) ceil((int) $total / $limit),
				'limit' => $limit
			);
		}

		return $obj_ret;
	}

	/**
	 * Get properties of a website page by id
	 *
	 * Return an array with page information.
	 *
	 * @param   int     $id             ID of website
	 * @param   int     $pageid         ID of page
	 * @return  array|mixed             Object with cleaned properties
	 * @phan-return WebsitePage
	 * @phpstan-return WebsitePage
	 *
	 * @url     GET {id}/pages/{pageid}
	 *
	 * @throws  RestException   403     Access denied
	 * @throws  RestException   404     Website or page not found
	 */
	public function getPage($id, $pageid)
	{
		if (!DolibarrApiAccess::$user->hasRight('website', 'read')) {
			throw new RestException(403);
		}

		$result = $this->website->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Website not found');
		}

		if (!DolibarrApi::_checkAccessToResource('website', $this->website->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->websitepage->fetch($pageid);
		if (!$result) {
			throw new RestException(404, 'Page not found');
		}

		// Check that the page belongs to the website
		if ($this->websitepage->fk_website != $this->website->id) {
			throw new RestException(404, 'Page not found for this website');
		}

		return $this->_cleanObjectDatas($this->websitepage);
	}

	/**
	 * Update a website page
	 *
	 * Update a page of a website by its ID. The request data can include any field
	 * of the website_page table (title, description, content, pageurl, status, etc.).
	 *
	 * @param   int     $id             ID of website
	 * @param   int     $pageid         ID of page to update
	 * @param   array   $request_data   Page data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return  array|mixed				Object after update
	 * @phan-return WebsitePage
	 * @phpstan-return WebsitePage
	 *
	 * @url     PUT {id}/pages/{pageid}
	 *
	 * @throws  RestException   403     Access denied
	 * @throws  RestException   404     Website or page not found
	 * @throws  RestException   500     Error updating page
	 */
	public function putPage($id, $pageid, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('website', 'write')) {
			throw new RestException(403);
		}

		$result = $this->website->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Website not found');
		}

		if (!DolibarrApi::_checkAccessToResource('website', $this->website->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->websitepage->fetch($pageid);
		if (!$result) {
			throw new RestException(404, 'Page not found');
		}

		// Check that the page belongs to the website
		if ($this->websitepage->fk_website != $this->website->id) {
			throw new RestException(404, 'Page not found for this website');
		}

		// Keep the old PHP content to detect if PHP content changed
		$phpfullcodestringold = dolKeepOnlyPhpCode($this->websitepage->content);

		// Apply request data to the page object
		foreach ($request_data as $field => $value) {
			if ($field == 'id' || $field == 'rowid') {
				continue;
			}
			if ($field == 'fk_website') {
				// Do not allow changing the website ownership of a page via this endpoint
				continue;
			}
			if ($field === 'caller') {
				$this->websitepage->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->websitepage->array_options[$index] = $this->_checkValExtrafieldsForAPI($index, $val, $this->websitepage);
				}
				continue;
			}
			$this->websitepage->$field = $this->_checkValForAPI($field, $value, $this->websitepage);
		}

		// Security: check PHP content if user does not have writephp permission
		$phpfullcodestring = dolKeepOnlyPhpCode($this->websitepage->content);
		if ($phpfullcodestringold != $phpfullcodestring) {
			if (!DolibarrApiAccess::$user->hasRight('website', 'writephp')) {
				throw new RestException(403, 'NotAllowedToAddDynamicContent');
			}
		}

		// Clean data: remove head section from content (same as web interface)
		$this->websitepage->content = preg_replace('/<head>.*<\/head>/ims', '', $this->websitepage->content);

		// Update the page in database
		$result = $this->websitepage->update(DolibarrApiAccess::$user);
		if ($result < 0) {
			throw new RestException(500, $this->websitepage->error);
		}

		// Regenerate static files on disk (same as web interface in index.php)
		$this->_regeneratePageFiles($this->website, $this->websitepage);

		return $this->_cleanObjectDatas($this->websitepage);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 * @phpstan-template T
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 *
	 * @phpstan-param T $object
	 * @phpstan-return T
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		// Remove properties not relevant for the website API response and not already cleaned by the parent
		unset($object->canvas);
		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->statut);
		unset($object->region_id);
		unset($object->state_id);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->barcode_type);
		unset($object->barcode_type_coder);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->user);
		unset($object->fk_account);
		unset($object->shipping_method_id);
		unset($object->fk_incoterms);
		unset($object->label_incoterms);
		unset($object->location_incoterms);
		unset($object->multicurrency_code);
		unset($object->multicurrency_tx);
		unset($object->multicurrency_total_ht);
		unset($object->multicurrency_total_ttc);
		unset($object->multicurrency_total_tva);
		unset($object->multicurrency_total_localtax1);
		unset($object->multicurrency_total_localtax2);

		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->demand_reason_id);
		unset($object->transport_mode_id);
		unset($object->shipping_method);
		unset($object->model_pdf);
		unset($object->last_main_doc);
		unset($object->lines);
		unset($object->actiontypecode);
		unset($object->civility_code);
		unset($object->date_cloture);
		unset($object->user_closing_id);
		unset($object->totalpaid);
		unset($object->totalpaid_multicurrency);
		unset($object->cond_reglement_supplier_id);
		unset($object->deposit_percent);
		unset($object->warehouse_id);
		unset($object->array_languages);
		unset($object->contacts_ids);
		unset($object->contacts_ids_internal);
		unset($object->other_linked_objects);
		unset($object->linkedObjectsIds);
		unset($object->origin_type);
		unset($object->origin_id);
		unset($object->product);
		unset($object->fk_project);
		unset($object->contact_id);
		unset($object->validation);
		unset($object->user_validation_id);
		unset($object->tms);

		return $object;
	}

	/**
	 * Get properties of a website object.
	 *
	 * @param   int     $id     ID of website
	 * @param   string  $ref    Ref of website
	 * @return  Object          Object with cleaned properties
	 * @phan-return Website
	 * @phpstan-return Website
	 *
	 * @throws  RestException   400     Bad value for parameter id or ref
	 * @throws  RestException   403     Access denied
	 * @throws  RestException   404     Website not found
	 */
	private function _fetchWebsite($id, $ref = '')
	{
		if (empty($id) && empty($ref)) {
			throw new RestException(400, 'bad value for parameter id or ref');
		}

		if (!DolibarrApiAccess::$user->hasRight('website', 'read')) {
			throw new RestException(403);
		}

		$result = $this->website->fetch($id, $ref);
		if (!$result) {
			throw new RestException(404, 'Website not found');
		}

		if (!DolibarrApi::_checkAccessToResource('website', $this->website->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->website);
	}

	/**
	 * Regenerate static page files on disk (master.inc.php, alias, tpl content).
	 * This replicates the behavior of the web interface in htdocs/website/index.php
	 * when a page is updated (action = updatesource/updatecontent).
	 *
	 * @param   Website      $website        Website object
	 * @param   WebsitePage  $websitepage    WebsitePage object
	 * @return  void
	 */
	private function _regeneratePageFiles($website, $websitepage)
	{
		global $conf;
		global $dolibarr_main_data_root;

		$pathofwebsite = $dolibarr_main_data_root.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.$website->ref;

		$filemaster = $pathofwebsite.'/master.inc.php';
		$filealias = $pathofwebsite.'/'.$websitepage->pageurl.'.php';
		$filetpl = $pathofwebsite.'/page'.$websitepage->id.'.tpl.php';

		dol_mkdir($pathofwebsite);

		// Regenerate the master.inc.php
		$result = dolSaveMasterFile($filemaster);
		if (!$result) {
			dol_syslog("Failed to write the master file ".$filemaster, LOG_WARNING);
		}

		// Save page alias
		$result = dolSavePageAlias($filealias, $website, $websitepage);
		if (!$result) {
			dol_syslog("Failed to write the alias file ".basename($filealias), LOG_WARNING);
		}

		// Save page content (the .tpl.php file)
		$result = dolSavePageContent($filetpl, $website, $websitepage, 1);
		if (!$result) {
			dol_syslog("Failed to write the tpl file ".$filetpl, LOG_WARNING);
		}
	}
}
