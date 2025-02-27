<?php
/*
 * Copyright (C) 2025		 Mohamed DAOUD       <mdaoud@dolicloud.com>
 * Copyright (C) 2025		MDW					<mdeweerd@users.noreply.github.com>
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

	/**
	 * @var string GitHub YAML file URL
	 */
	public $file_source_url;

	/**
	 * @var string Cache file path for GitHub modules YAML file content (local)
	 */
	public $cache_file;

	/**
	 * // the url of this page
	 * @var string
	 */
	public $url;
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
	 * @var int
	 */
	public $dolistoreApiStatus;

	/**
	 * @var string
	 */
	public $dolistoreApiError;

	/**
	 * @var int
	 */
	public $githubFileStatus;

	/**
	 * @var int // number of online providers
	 */
	public $numberOfProviders;

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

		$this->file_source_url = "https://raw.githubusercontent.com/Dolibarr/dolibarr-community-modules/refs/heads/main/index.yaml";
		$this->cache_file = DOL_DOCUMENT_ROOT.'/admin/remotestore/sources/github_modules_file.yaml';
		$this->getRemoteYamlFile($this->file_source_url, 86400);

		$lang       = $langs->defaultlang;
		$lang_array = array('en_US', 'fr_FR', 'es_ES', 'it_IT', 'de_DE');
		if (!in_array($lang, $lang_array)) {
			$lang = 'en_US';
		}
		$this->lang = $lang;

		// Check access to Dolistore API
		$this->dolistoreApiStatus = $this->checkApiStatus();
		$this->githubFileStatus = file_exists($this->cache_file) ? 1 : 0;

		// Count the number of online providers
		$this->numberOfProviders = $this->dolistoreApiStatus + $this->githubFileStatus;
	}

	/**
	 * Test if we can access to remote Dolistore market place.
	 *
	 * @param string 						$resource Resource name
	 * @param array<string, mixed>|false 	$options Options for the request
	 *
	 * @return array{status_code:int,response:null|string|array<string,mixed>,header:string}
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
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

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
	 * Fetch modules from a cache YAML file
	 * @param array<string, mixed> $options Options for filter
	 *
	 * @return list<array<string, array<string, string|null>|string|null>> List of modules
	 */
	public function fetchModulesFromFile($options = array())
	{
		$modules = array();

		if (!empty($this->cache_file) && file_exists($this->cache_file)) {
			dol_syslog(__METHOD__ . " - Loading cache file: " . $this->cache_file, LOG_DEBUG);

			$content = file_get_contents($this->cache_file);
			if ($content !== false) {
				$modules = $this->readYaml($content);
			} else {
				dol_syslog(__METHOD__ . " - Error reading cache file", LOG_ERR);
			}
		}

		return $modules;
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

		$html       = "";
		$last_month = dol_now() - (30 * 24 * 60 * 60);
		$dolibarrversiontouse = DOL_VERSION;

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

		// Fetch the products from Dolistore source
		$dolistoreProducts = array();
		if ($this->dolistoreApiStatus > 0 && $options['search_source_dolistore'] == 1) {
			$getDolistoreProducts = $this->callApi('products', $data);
			if (!isset($getDolistoreProducts['response']) || !is_array($getDolistoreProducts['response']) || ($getDolistoreProducts['status_code'] != 200 && $getDolistoreProducts['status_code'] != 201)) {
				$dolistoreProducts = array();
			} else {
				$dolistoreProducts = $this->adaptData($getDolistoreProducts['response'], 'dolistore');
			}
		}

		// fetch from github repo
		$fileProducts = array();
		if (!empty($this->githubFileStatus) && $options['search_source_github'] == 1) {
			$fileProducts = $this->fetchModulesFromFile($data);
			$fileProducts = $this->adaptData($fileProducts, 'github');
			$fileProducts = $this->applyFilters($fileProducts, $data);
		}

		// merge both sources
		$this->products = array_values(array_merge($dolistoreProducts, $fileProducts));

		// Sort products list by datec
		usort(
			$this->products,
			/**
			 * Compare creation dates
			 *
			 * @param array<string, mixed> $a First product for comparison.
			 * @param array<string, mixed> $b Second product for comparison.
			 * @return int
			 */
			static function ($a, $b) {
				return strtotime($b['datec'] ?? '0') - strtotime($a['datec'] ?? '0');
			}
		);

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
				$images .= '<img class="imgstore" src="'.$product["cover_photo_url"].'" alt="" /></a>';
			} else {
				$images = '<img class="imgstore" src="'.DOL_URL_ROOT.'/admin/dolistore/img/NoImageAvailable.png" />';
			}

			// free or pay ?
			if ($product["price_ttc"] > 0) {
				$price = '<h3>'.price(price2num($product["price_ttc"], 'MT'), 0, $langs, 1, -1, -1, 'EUR').' '.$langs->trans("TTC").'</h3>';
				$download_link = '<a target="_blank" href="'.$this->shop_url.urlencode($product['id']).'"><img width="32" src="'.DOL_URL_ROOT.'/admin/remotestore/img/follow.png" /></a>';
			} else {
				$download_link = '#';
				$price         = '<h3>'.$langs->trans('Free').'</h3>';
				if ($product['source'] === 'Dolistore') {
					$download_link = '<a class="paddingleft paddingright" target="_blank" href="'.$this->shop_url.urlencode($product["id"]).'"><img width="32" src="'.DOL_URL_ROOT.'/admin/remotestore/img/follow.png" /></a>';
					$download_link .= '<a class="paddingleft paddingright" target="_blank" href="'.$product["download_link"].'" rel="noopener noreferrer"><img width="32" src="'.DOL_URL_ROOT.'/admin/remotestore/img/Download-128.png" /></a>';
				}

				if ($product['source'] === 'Github') {
					$download_link = '<a class="paddingleft paddingright" target="_blank" href="'.$product["link"].'"><img width="32" src="'.DOL_URL_ROOT.'/admin/remotestore/img/follow.png" /></a>';
					$download_link .= '<a class="paddingleft paddingright" target="_blank" href="'.$product["link"].'" rel="noopener noreferrer"><img width="32" src="'.DOL_URL_ROOT.'/admin/remotestore/img/Download-128.png" /></a>';
				}
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
			$html .= '<small> '.dol_print_date(dol_stringtotime($product['tms']), 'day').' - '.$langs->trans('Ref').': '.dol_escape_htmltag($product["ref"]).' - '.dol_escape_htmltag($langs->trans('Id')).': '.((int) $product["id"]).'</small><br>';
			$html .= '<small>'.$langs->trans('Source').': '.$product["source"].'</small><br>';
			$html .= '<br>'.dol_escape_htmltag(dol_string_nohtmltag($product["description"]));
			$html .= '</td>';
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

	/**
	 * get YAML file from remote source and put it in cache file for one day
	 * @param string 	$file_source_url URL of the remote source
	 * @param int 		$cache_time Cache time
	 *
	 * @return string Uri of the cache file
	 */
	public function getRemoteYamlFile($file_source_url, $cache_time)
	{
		$cache_file = $this->cache_file;

		// Check if cache directory exists
		if (!file_exists(dirname($cache_file))) {
			mkdir(dirname($cache_file), 0777, true);
		}

		if (!file_exists($cache_file) || filemtime($cache_file) < dol_now() - $cache_time) {
			$yaml = file_get_contents($file_source_url);
			if (!empty($yaml)) {
				file_put_contents($cache_file, $yaml);
			}
		}

		return $cache_file;
	}


	/**
	 * Read a YAML string and convert it to an array
	 * @param string $yaml YAML string
	 *
	 * @return list<array<string, array<string, string|null>|string|null>> Parsed array representation
	 */
	public function readYaml($yaml)
	{
		$data = [];
		$currentPackage = null;
		$currentSection = null;

		foreach (explode("\n", trim($yaml)) as $line) {
			$trimmedLine = trim($line);

			// Ignore empty lines and comments
			if ($trimmedLine === '' || strpos($trimmedLine, '#') === 0) {
				continue;
			}

			// Match a new package entry (e.g., "- modulename: 'helloasso'")
			if (preg_match('/^\s*-\s*modulename:\s*["\']?(.*?)["\']?$/', $trimmedLine, $matches)) {
				if ($currentPackage !== null) {
					$data[] = $currentPackage;
				}
				$currentPackage = ['modulename' => $matches[1]];
				$currentSection = null;
				continue;
			}

			// If the key doesn't start with fr, en, es, it, de, treat it as a section
			if (!preg_match('/^\s*(fr|en|es|it|de):\s*["\']?(.*?)["\']?$/', $trimmedLine)) {
				$currentSection = null;
			}

			// Match a top-level key-value pair (e.g., "author: 'Dolicloud'")
			if (preg_match('/^(\w[\w-]*):\s*["\']?(.*?)["\']?$/', $trimmedLine, $matches)) {
				if ($currentPackage !== null) {
					if ($currentSection) {
						// Store in the nested section
						$currentPackage[$currentSection][$matches[1]] = $matches[2] === '' ? null : $matches[2];
					} else {
						// Store as a normal key-value pair
						$currentPackage[$matches[1]] = $matches[2] === '' ? null : $matches[2];
					}
				}

				// Match a nested section (e.g., "label:")
				if (preg_match('/^\s*(label|description):\s*$/', $trimmedLine, $matches)) {
					$currentSection = $matches[1];
					$currentPackage[$currentSection] = []; // Initialize as an empty array for nested sections
				}

				continue;
			}
		}

		// Add the last package if available
		if ($currentPackage !== null) {
			$data[] = $currentPackage;
		}

		return $data;
	}

	/**
	 * Adapter data fetched from github remote source to the expected format
	 * @param array<string, mixed>|list<array<string, array<string, string|null>|string|null>> $data Data fetched from github remote source
	 * @param string $source Source of the data
	 *
	 * @return list<array<string, array<string, string|null>|string|null>> Data adapted to the expected format
	 */
	public function adaptData($data, $source)
	{
		$adaptedData = [];
		$urldolibarrmodules = 'https://www.dolistore.com';

		if (!is_array($data) || empty($data) || empty($source)) {
			return $adaptedData;
		}

		if ($source === 'github') {
			foreach ($data as $package) {
				if (empty($package['modulename'])) {
					continue;
				}
				$adaptedPackage = [
					'ref' => str_replace(' ', '', $package['author'].'@'.$package['modulename'].'@'.$package['current_version']),
					'label' => !empty($package['label'][substr($this->lang, 0, 2)])
						? $package['label'][substr($this->lang, 0, 2)]
						: (!empty($package['label']['en']) ? $package['label']['en'] : $package['modulename']),
					'description' => !empty($package['description'][substr($this->lang, 0, 2)])
						? $package['description'][substr($this->lang, 0, 2)]
						: (!empty($package['description']['en']) ? $package['description']['en'] : ''),
					'datec' => (!empty($package['created_at']) && is_string($package['created_at']))
						? date('Y-m-d H:i:s', strtotime($package['created_at']))
						: '',
					'tms' => (!empty($package['last_updated_at']) && is_string($package['last_updated_at']))
						? date('Y-m-d H:i:s', strtotime($package['last_updated_at']))
						: '',
					'price_ttc' => 0,
					'dolibarr_min' => !empty($package['dolibarrmin'])
						? $package['dolibarrmin']
						: 'unknown',
					'dolibarr_max' => !empty($package['dolibarrmax'])
						? $package['dolibarrmax']
						: 'unknown',
					'module_version' => !empty($package['current_version'])
						? $package['current_version']
						: 'unknown',
					'cover_photo_url' => !empty($package['cover'])
						? $package['cover']
						: '#',
					'category' => (!empty($package['category']) && is_string($package['category']))
						? explode(',', str_replace(' ', '', (string) $package['category']))
						: array(),
					'link' => !empty($package['git'])
						? $package['git']
						: '#',
					'source' => 'Github'
				];

				$adaptedData[] = $adaptedPackage;
			}
		}

		if ($source === 'dolistore') {
			foreach ($data as $package) {
				$adaptedPackage = [
					'id' => $package['id'],
					'ref' => $package['ref'],
					'label' => $package['label'],
					'description' => $package['description'],
					'datec' => $package['datec'],
					'tms' => $package['tms'],
					'price_ttc' => $package['price_ttc'],
					'dolibarr_min' => $package['dolibarr_min'],
					'dolibarr_max' => $package['dolibarr_max'],
					'module_version' => $package['module_version'],
					'cover_photo_url' => $urldolibarrmodules.$package['cover_photo_url'],
					'source' => 'Dolistore'
				];

				$adaptedData[] = $adaptedPackage;
			}
		}

		return $adaptedData;
	}

	/**
	 * Apply filters to the data
	 * @param list<array<string, mixed>> $list Data to filter
	 * @param array<string, mixed> $options Options for the filter
	 *
	 * @return list<array<string, mixed>> Filtered data
	 */
	public function applyFilters($list, $options)
	{
		$filteredData = $list;

		// Sort products list by datec
		usort(
			$filteredData,
			/**
			 * Compare creation times
			 * @param array<string, mixed> $a First product for comparison.
			 * @param array<string, mixed> $b Second product for comparison.
			 *
			 * @return int
			 */
			static function ($a, $b) {
				return strtotime($b['datec'] ?? '0') - strtotime($a['datec'] ?? '0');
			}
		);

		if (!empty($options['search'])) {
			$filteredData = array_filter(
				$filteredData,
				/**
				 * Filter packages that have a label or description with the search string
				 *
				 * @param array<string, mixed> $package
				 *
				 * @return bool
				 */
				static function ($package) use ($options) {
					return stripos($package['label'], $options['search']) !== false || stripos($package['description'], $options['search']) !== false;
				}
			);
		}

		if (!empty($options['categorieid'])) {
			$filteredData = array_filter(
				$filteredData,
				/**
				 * Filter the packages that belong to the filtered category
				 *
				 * @param array<string, mixed> $package
				 *
				 * @return bool
				 */
				static function ($package) use ($options) {
					return in_array($options['categorieid'], $package['category']);
				}
			);
		}

		// Pagination
		$filteredData = array_values($filteredData);
		$filteredData = array_slice($filteredData, ($options['page'] - 1) * $options['limit'], $options['limit']);

		return $filteredData;
	}

	/**
	 * Check if an Dolistore API is up
	 *
	 * @return int
	 */
	public function checkApiStatus()
	{

		$testRequest = $this->callApi('categories');

		if (!isset($testRequest['response']) || !is_array($testRequest['response']) || ($testRequest['status_code'] != 200 && $testRequest['status_code'] != 201)) {
			$this->dolistoreApiError = $this->checkStatusCode($testRequest);
			return 0;
		} else {
			return 1;
		}
	}

	/**
	 * Retrieve the status icon
	 * @param mixed $status Status
	 * @param mixed $mode	Mode
	 *
	 * @return string
	 */
	public function libStatut($status, $mode = 3)
	{
		global $langs;

		$statusType = 'status4';
		if ($status == 0) {
			$statusType = 'status6';
		}

		$labelStatus = [];
		$labelStatusShort = [];

		$labelStatus[0] = $this->dolistoreApiError;
		$labelStatus[1] = $langs->transnoentitiesnoconv("online");
		$labelStatusShort[0] = $this->dolistoreApiError;
		$labelStatusShort[1] = $langs->transnoentitiesnoconv("online");

		return dolGetStatus($labelStatus[$status], $labelStatusShort[$status], '', $statusType, $mode);
	}
}
