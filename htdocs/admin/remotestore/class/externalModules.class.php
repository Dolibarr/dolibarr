<?php
/*
 * Copyright (C) 2025		 Mohamed DAOUD       <mdaoud@dolicloud.com>
 *
 * This program is free software; you can redistribute it and/or modifyion 2.0 (the "License");
 * it under the terms of the GNU General Public License as published bypliance with the License.
 * the Free Software Foundation; either version 3 of the License, or
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

include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

/**
 * Class ExternalModules
 */
class ExternalModules
{
	/**
	 * @var int Pagination: current page
	 */
	public $no_page;

	/**
	 * @var int Pagination: display per page
	 */
	public $per_page;
	/**
	 * @var int The current categorie
	 */
	public $categorie;

	/**
	 * @var string The search keywords
	 */
	public $search;

	// setups
	/**
	 * @var string
	 */
	public $url; // the url of this page
	/**
	 * @var string
	 */
	public $shop_url; // the url of the shop
	/**
	 * @var string
	 */
	public $lang; // the integer representing the lang in the store
	/**
	 * @var bool
	 */
	public $debug_api; // useful if no dialog
	/**
	 * @var string
	 */
	public $dolistore_api_url;
	/**
	 * @var string
	 */
	public $dolistore_api_key;

	/**
	 * @var array<int, mixed>|null
	 */
	public $products;

	/**
	 * Constructor
	 *
	 * @param	boolean		$debug		Enable debug of request on screen
	 */
	public function __construct($debug = false)
	{
		global $langs;

		$this->dolistore_api_url = getDolGlobalString('MAIN_MODULE_DOLISTORE_API_SRV');
		$this->dolistore_api_key = getDolGlobalString('MAIN_MODULE_DOLISTORE_API_KEY');

		$this->url       = DOL_URL_ROOT.'/admin/modules.php?mode=marketplace';
		$this->shop_url  = 'https://www.dolistore.com/product.php?id=';
		$this->debug_api = $debug;

		$lang       = $langs->defaultlang;
		$lang_array = array('en_US', 'fr_FR', 'es_ES', 'it_IT', 'de_DE');
		if (!in_array($lang, $lang_array)) {
			$lang = 'en_US';
		}
		$this->lang = $lang;

		// TODO check access to all remote sources and define valid one
	}

	/**
	 * Test if we can access to remote Dolistore market place.
	 *
	 * @param string 						$resource Resource name
	 * @param array<string, mixed>|false 	$options Options for the request
	 *
	 * @return array{status_code:int,response:?string,header:string}
	 */
	public function callApi($resource, $options = false)
	{

		// If no dolistore_api_key is set, we can't access the API
		if (empty($this->dolistore_api_key) || empty($this->dolistore_api_url)) {
			return array('status_code' => 0, 'response' => null, 'header' => '');
		}

		$curl = curl_init();
		$httpheader = ['DOLAPIKEY: '.$this->dolistore_api_key];

		// Add basic auth if needed
		$basicAuthLogin = getDolGlobalString('MAIN_MODULE_DOLISTORE_BASIC_LOGIN');
		$basicAuthPassword = getDolGlobalString('MAIN_MODULE_DOLISTORE_BASIC_PASSWORD');

		if (!empty($basicAuthLogin) && !empty($basicAuthPassword)) {
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			$login = getDolGlobalString('MAIN_MODULE_DOLISTORE_BASIC_LOGIN');
			$password = getDolGlobalString('MAIN_MODULE_DOLISTORE_BASIC_PASSWORD');
			curl_setopt($curl, CURLOPT_USERPWD, $login . ':' . $password);
		}

		$url = $this->dolistore_api_url . $resource;

		if ($options) {
			$url .= '?' . http_build_query($options);
		}

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
		curl_setopt($curl, CURLOPT_HEADER, true);

		$response = curl_exec($curl);

		if ($response === false) {
			return array('status_code' => 0, 'response' => 'CURL Error: ' . curl_error($curl), 'header' => '');
		}

		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);

		// convert body to array if it is json
		if (strpos($header, 'Content-Type: application/json') !== false) {
			$body = json_decode($body, true);
		}

		$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		return array('status_code' => $status_code, 'response' => $body, 'header' => $header);
	}

	/**
	 * Generate HTML for categories and their children.
	 *
	 * @return string HTML string representing the categories and their children.
	 */
	public function getCategories()
	{
		$organized_tree = array();
		$html = '';

		$data = [
			'lang' => $this->lang
		];

		$resCategories = $this->callApi('categories', $data);
		if (isset($resCategories['response']) && is_array($resCategories['response'])) {
			$organized_tree = $resCategories['response'];
		} else {
			return $html ;
		}

		// TODO call all sources and merge them

		$html = '';
		foreach ($organized_tree as $key => $value) {
			if ($value['label'] != "Versions" && $value['label'] != "Specials") {
				$html .= '<li>';
				$html .= '<a href="?mode=marketplace&categorie=' . $value['rowid'] . '">' . $value['label'] . '</a>';
				if (isset($value['children'])) {
					$html .= '<ul>';
					usort($value['children'], $this->buildSorter('position'));
					foreach ($value['children'] as $key_children => $value_children) {
						$html .= '<li>';
						$html .= '<a href="?mode=marketplace&categorie=' . $value_children['rowid'] . '" title="' . dol_escape_htmltag(strip_tags($value_children['description'])) . '">' . $value_children['label'] . '</a>';
						$html .= '</li>';
					}
					$html .= '</ul>';
				}
				$html .= '</li>';
			}
		}
		return $html;
	}

	/**
	 * Generate HTML for products.
	 * @param array<string, mixed> $options Options for the request
	 * @return string|null HTML string representing the products.
	 */
	public function getProducts($options)
	{

		global $langs;

		// TODO call all sources and merge them

		$html       = "";
		$last_month = dol_now() - (30 * 24 * 60 * 60);
		$dolibarrversiontouse = DOL_VERSION;
		$urldolibarrmodules = 'https://www.dolistore.com';

		$this->products = array();

		$this->categorie = $options['categorie'] ?? 0;
		$this->per_page  = $options['per_page'] ?? 11;
		$this->no_page  = $options['no_page'] ?? 1;
		$this->search    = $options['search'] ?? '';

		$data = [
			'categorieid' 	=> $this->categorie,
			'limit' 		=> $this->per_page,
			'page' 			=> $this->no_page,
			'search' 		=> $this->search,
			'lang' 			=> $this->lang
		];

		$resProducts = $this->callApi('products', $data);
		if (!isset($resProducts['response']) || !is_array($resProducts['response']) || ($resProducts['status_code'] != 200 && $resProducts['status_code'] != 201)) {
			$html = $this->checkStatusCode($resProducts);

			return $html;
		}

		$this->products = $resProducts['response'];

		$i = 0;
		foreach ($this->products as $product) {
			$i++;

			// check new product ?
			$newapp = '';
			if ($last_month < strtotime($product['datec'])) {
				$newapp .= '<span class="newApp">'.$langs->trans('New').'</span> ';
			}

			// check updated ?
			if ($last_month < strtotime($product['tms']) && $newapp == '') {
				$newapp .= '<span class="updatedApp">'.$langs->trans('Updated').'</span> ';
			}

			// add image or default ?
			if ($product["cover_photo_url"] != '') {
				$images = '<a href="'.$product["cover_photo_url"].'" class="documentpreview" target="_blank" rel="noopener noreferrer" mime="image/png" title="'.dol_escape_htmltag($product["label"].', '.$langs->trans('Version').' '.$product["module_version"]).'">';
				$images .= '<img class="imgstore" src="'.$urldolibarrmodules.$product["cover_photo_url"].'" alt="" /></a>';
			} else {
				$images = '<img class="imgstore" src="'.DOL_URL_ROOT.'/admin/dolistore/img/NoImageAvailable.png" />';
			}

			// free or pay ?
			if ($product["price_ttc"] > 0) {
				$price = '<h3>'.price(price2num($product["price_ttc"], 'MT'), 0, $langs, 1, -1, -1, 'EUR').' '.$langs->trans("TTC").'</h3>';
				$download_link = '<a target="_blank" href="'.$this->shop_url.urlencode($product['id']).'"><img width="32" src="'.DOL_URL_ROOT.'/admin/remotestore/img/follow.png" /></a>';
			} else {
				$price         = '<h3>'.$langs->trans('Free').'</h3>';
				$download_link = '<a class="paddingleft paddingright" target="_blank" href="'.$this->shop_url.urlencode($product["id"]).'"><img width="32" src="'.DOL_URL_ROOT.'/admin/remotestore/img/follow.png" /></a>';
				$download_link .= '<a class="paddingleft paddingright" target="_blank" href="'.$urldolibarrmodules.$product["download_link"].'" rel="noopener noreferrer"><img width="32" src="'.DOL_URL_ROOT.'/admin/remotestore/img/Download-128.png" /></a>';
			}

			// Set and check version
			$version = '';
			if ($this->version_compare($product["dolibarr_min"], $dolibarrversiontouse) <= 0) {
				if ($this->version_compare($product["dolibarr_max"], $dolibarrversiontouse) >= 0) {
					//compatible
					$version = '<span class="compatible">'.$langs->trans(
						'CompatibleUpTo',
						$product["dolibarr_max"],
						$product["dolibarr_min"],
						$product["dolibarr_max"]
					).'</span>';
					$compatible = '';
				} else {
					//never compatible, module expired
					$version = '<span class="notcompatible">'.$langs->trans(
						'NotCompatible',
						$dolibarrversiontouse,
						$product["dolibarr_min"],
						$product["dolibarr_max"]
					).'</span>';
					$compatible = 'NotCompatible';
				}
			} else {
				//need update
				$version = '<span class="compatibleafterupdate">'.$langs->trans(
					'CompatibleAfterUpdate',
					$dolibarrversiontouse,
					$product["dolibarr_min"],
					$product["dolibarr_max"]
				).'</span>';
				$compatible = 'NotCompatible';
			}

			//output template
			$html .= '<tr class="app oddeven '.dol_escape_htmltag($compatible).'">';
			$html .= '<td class="center" width="160"><div class="newAppParent">';
			$html .= $newapp.$images;	// No dol_escape_htmltag, it is already escape html
			$html .= '</div></td>';
			$html .= '<td class="margeCote"><h2 class="appTitle">';
			$html .= dol_escape_htmltag(dol_string_nohtmltag($product["label"]));
			$html .= '<br><small>';
			$html .= $version;			// No dol_escape_htmltag, it is already escape html
			$html .= '</small></h2>';
			$html .= '<small> '.dol_print_date(dol_stringtotime($product['tms']), 'dayhour').' - '.$langs->trans('Ref').': '.dol_escape_htmltag($product["ref"]).' - '.dol_escape_htmltag($langs->trans('Id')).': '.((int) $product["id"]).'</small><br>';
			$html .= '<br>'.dol_escape_htmltag(dol_string_nohtmltag($product["description"]));
			$html.= '</td>';
			// do not load if display none
			$html .= '<td class="margeCote center amount">';
			$html .= $price;
			$html .= '</td>';
			$html .= '<td class="margeCote nowraponall">'.$download_link.'</td>';
			$html .= '</tr>';
		}

		if (empty($this->products)) {
			$html .= '<tr class=""><td colspan="3" class="center">';
			$html .= '<br><br>';
			$langs->load("website");
			$html .= $langs->trans("noResultsWereFound").'...';
			$html .= '<br><br>';
			$html .= '</td></tr>';
		}

		if (count($this->products) > $data['limit']) {
			$html .= '<tr class=""><td colspan="3" class="center">';
			$html .= '<br><br>';
			$html .= $langs->trans("ThereIsMoreThanXAnswers", $data["limit"]).'...';
			$html .= '<br><br>';
			$html .= '</td></tr>';
		}


		return $html ;
	}

	/**
	 * Sort an array by a key
	 * @param string $key Key to sort by
	 *
	 * @return Closure(array<string, mixed>, array<string, mixed>): int
	 */
	public function buildSorter(string $key): Closure
	{
		return
		/**
		 * @param array<string, mixed> $a
		 * @param array<string, mixed> $b
		 * @return int
		 */
		function (array $a, array $b) use ($key) {
			$valA = isset($a[$key]) && is_scalar($a[$key]) ? (string) $a[$key] : '';
			$valB = isset($b[$key]) && is_scalar($b[$key]) ? (string) $b[$key] : '';

			return strnatcmp($valA, $valB);
		};
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * version compare
	 *
	 * @param   string  $v1     version 1
	 * @param   string  $v2     version 2
	 * @return int              result of compare
	 */
	public function version_compare($v1, $v2)
	{
		// phpcs:enable
		$v1       = explode('.', $v1);
		$v2       = explode('.', $v2);
		$ret      = 0;
		$level    = 0;
		$count1   = count($v1);
		$count2   = count($v2);
		$maxcount = max($count1, $count2);
		while ($level < $maxcount) {
			$operande1 = isset($v1[$level]) ? $v1[$level] : 'x';
			$operande2 = isset($v2[$level]) ? $v2[$level] : 'x';
			$level++;
			if (strtoupper($operande1) == 'X' || strtoupper($operande2) == 'X' || $operande1 == '*' || $operande2 == '*') {
				break;
			}
			if ($operande1 < $operande2) {
				$ret = -$level;
				break;
			}
			if ($operande1 > $operande2) {
				$ret = $level;
				break;
			}
		}
		//print join('.',$versionarray1).'('.count($versionarray1).') / '.join('.',$versionarray2).'('.count($versionarray2).') => '.$ret.'<br>'."\n";
		return $ret;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * get previous link
	 *
	 * @param   string    $text     symbol previous
	 * @return  string              html previous link
	 */
	public function get_previous_link($text = '<<')
	{
		// phpcs:enable
		return '<a href="'.$this->get_previous_url().'" class="button">'.dol_escape_htmltag($text).'</a>';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * get next link
	 *
	 * @param   string    $text     symbol next
	 * @return  string              html next link
	 */
	public function get_next_link($text = '>>')
	{
		// phpcs:enable
		return '<a href="'.$this->get_next_url().'" class="button">'.dol_escape_htmltag($text).'</a>';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * get previous url
	 *
	 * @return string    previous url
	 */
	public function get_previous_url()
	{
		// phpcs:enable
		$param_array = array();
		if ($this->no_page > 1) {
			$sub = 1;
		} else {
			$sub = 0;
		}
		$param_array['no_page'] = $this->no_page - $sub;
		if ($this->categorie != 0) {
			$param_array['categorie'] = $this->categorie;
		}
		$param = http_build_query($param_array);
		return $this->url."&".$param;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * get next url
	 *
	 * @return string    next url
	 */
	public function get_next_url()
	{
		// phpcs:enable
		$param_array = array();
		if ($this->products !== null && count($this->products) < $this->per_page) {
			$add = 0;
		} else {
			$add = 1;
		}
		$param_array['no_page'] = $this->no_page + $add;
		if ($this->categorie != 0) {
			$param_array['categorie'] = $this->categorie;
		}
		$param = http_build_query($param_array);
		return $this->url."&".$param;
	}

	/**
	 * Check the status code of the request
	 *
	 * @param array{status_code:int,response:null|string|array{errors:array{code:int,message:string}[]},header:string} $request Response elements of CURL request
	 *
	 * @return string|null
	 */
	protected function checkStatusCode($request)
	{
		// Define error messages
		$error_messages = [
			204 => 'No content',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			500 => 'Internal Server Error',
		];

		// If status code is 200 or 201, return an empty string
		if ($request['status_code'] === 200 || $request['status_code'] === 201) {
			return '';
		}

		// Get the predefined error message or use a default one
		$error_message = $error_messages[$request['status_code']] ?? 'Unexpected HTTP status: ' . $request['status_code'];

		// Append error details if available
		if (!empty($request['response']) && isset($request['response']['errors']) && is_array($request['response']['errors'])) {
			foreach ($request['response']['errors'] as $error) {
				$error_message .= ' - (Code ' . $error['code'] . '): ' . $error['message'];
			}
		}

		// Return the formatted error message
		return sprintf('This call to the API failed and returned an HTTP status of %d. That means: %s.', $request['status_code'], $error_message);
	}
}
