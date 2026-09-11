<?php
/*
 * Copyright (C) 2025		Mohamed DAOUD       <mdaoud@dolicloud.com>
 * Copyright (C) 2025-2026	MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025       Frédéric France     <frederic.france@free.fr>
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
	 * @var string
	 */
	public $githubFileError;

	/**
	 * @var string
	 */
	public $error;

	/**
	 * @var int // number of online providers
	 */
	public $numberOfProviders;

	/**
	 * @var array<int, mixed>|null
	 */
	public $products;

	/**
	 * @var int Total number of products
	 */
	public $numberTotalOfProducts;

	/**
	 * @var int Total number of pages
	 */
	public $numberTotalOfPages;

	/**
	 * @var int Number of products displayed on the page.
	 */
	public $numberOfProducts;

	/**
	 * Constructor
	 *
	 * @param	boolean		$debug		Enable debug of request on screen
	 */
	public function __construct($debug = false)
	{
		global $langs;

		$this->debug_api = $debug;

		$this->url       = DOL_URL_ROOT.'/admin/modules.php?mode=marketplace';

		// For dolistore modules
		$this->dolistore_api_url = getDolGlobalString('MAIN_MODULE_DOLISTORE_API_SRV', 'https://www.dolistore.com/api/');	// 'https://www.dolistore.com/api/', 'https://admin2.dolibarr.org/api/index.php/marketplace/'
		$this->dolistore_api_key = getDolGlobalString('MAIN_MODULE_DOLISTORE_API_KEY', 'dolistorepublicapi');
		$this->shop_url  = getDolGlobalString('MAIN_MODULE_DOLISTORE_SHOP_URL', 'https://www.dolistore.com');

		// For community modules
		$this->file_source_url = "https://raw.githubusercontent.com/Dolibarr/dolibarr-community-modules/refs/heads/main/index.yaml";
		$this->cache_file = DOL_DATA_ROOT.'/admin/temp/remote_github_modules_file.yaml';

		$lang       = $langs->defaultlang;
		$lang_array = array('en_US', 'fr_FR', 'es_ES', 'it_IT', 'de_DE');
		if (!in_array($lang, $lang_array)) {
			$lang = 'en_US';
		}
		$this->lang = $lang;
	}

	/**
	 * loadRemoteSources
	 *
	 * @param	boolean		$debug		Enable debug of request on screen
	 * @return	void
	 */
	public function loadRemoteSources($debug = false)
	{
		// Check access to Community repo
		if (getDolGlobalString('MAIN_ENABLE_EXTERNALMODULES_COMMUNITY')) {
			$cachedelay
forgithubrepo = getDolGlobalInt('MAIN_REMOTE_GITHUBREPO_CACHE_DELAY', 86400);

			$this->getRemoteYamlFile($this->file_source_url, $cachedelayforgithubrepo);

			$this->githubFileError = $this->error;
			$this->githubFileStatus = dol_is_file($this->cache_file) ? 1 : 0;
		}

		// Check access to Dolistore API /api/categories -> /api/index.php/marketplace/categories
		if (getDolGlobalString('MAIN_ENABLE_EXTERNALMODULES_DOLISTORE')) {
			$this->dolistoreApiStatus = $this->checkApiStatus();
		}

		// Count the number of online providers
		$this->numberOfProviders = $this->dolistoreApiStatus + $this->githubFileStatus;
	}

	/**
	 * Test if we can access to remote Dolistore market place.
	 *
	 * @param string 						$resource 	Resource relative URL ('categories' or 'products')
	 * @param array<string, mixed>|false 	$options 	Options for the request
	 * @return array{status_code:int,response:null|string|array<string,mixed>}
	 */
	public function callApi($resource, $options = false)
	{
		// If no dolistore_api_key is set, we can't access the API
		if (empty($this->dolistore_api_key) || empty($this->dolistore_api_url)) {
			return array('status_code' => 0, 'response' => null);
		}

		// Add basic auth if needed
		$basicAuthLogin = getDolGlobalString('MAIN_MODULE_DOLISTORE_BASIC_LOGIN');
		$basicAuthPassword = getDolGlobalString('MAIN_MODULE_DOLISTORE_BASIC_PASSWORD');

		$httpheader = array('DOLAPIKEY: '.$this->dolistore_api_key);
		if ($basicAuthLogin) {
			$httpheader[] = 'Authorization: Basic '.base64_encode($basicAuthLogin.':'.$basicAuthPassword);
		}

		$url = $this->dolistore_api_url . (preg_match('/\/$/', $this->dolistore_api_url) ? '' : '/') . $resource;

		$options['apikey'] = $this->dolistore_api_key;

		if ($options) {
			$url .= '?' . http_build_query($options);
		}

		$response = getURLContent($url, 'GET', '', 1, $httpheader, array('https'), 0, -1, 5, 5);

		$status_code = $response['http_code'];
		$body = 'Error';

		if ($status_code == 200) {
			$body = $respons
e['content'];
			$body = json_decode($body, true);
			$returnarray = array(
				'status_code' => $status_code,
				'response' => $body
			);
		} else {
			$returnarray = array(
				'status_code' => $status_code,
				'response' => $body
			);
			if (!empty($response['curl_error_no'])) {
				$returnarray['curl_error_no'] = $response['curl_error_no'];
			}
			if (!empty($response['curl_error_msg'])) {
				$returnarray['curl_error_msg'] = $response['curl_error_msg'];
			}
		}

		return $returnarray;
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
	 * @param int $active The active category id
	 *
	 * @return string HTML string representing the categories and their children.
	 */
	public function getCategories($active = 0)
	{
		$organized_tree = array();
		$html = '';

		$data = [
			'lang' => $this->lang
		];

		$current = $active;

		$resCategories = $this->callApi('categories', $data);
		if (isset($resCategories['response']) && is_array($resCategories['response'])) {
			$organized_tree = $resCategories['response'];
		} else {
			return $html ;
		}

		$html = '';
		foreach ($organized_tree as $key => $value) {
			if ($value['label'] != "Versions" && $value['label'] != "Specials") {
				$html .= '<li' . ($current == $value['rowid'] ? ' class="active"' : '') . '>';
				$html .= '<a href="?mo
de=marketplace&categorie=' . $value['rowid'] . '">' . $value['label'] . '</a>';
				if (isset($value['children'])) {
					$html .= '<ul>';
					usort($value['children'], $this->buildSorter('position'));
					foreach ($value['children'] as $key_children => $value_children) {
						$html .= '<li' . ($current == $value_children['rowid'] ? ' class="active"' : '') . '>';
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
	 *
	 * @param 	array<string,mixed> 	$options 	Options for the request
	 * @param 	array<string,DolibarrModules>	$modules	Array of locally installed modules (keyed by module class name)
	 * @return 	string|null 						HTML string representing the products.
	 */
	public function getProducts($options, $modules = array())
	{
		global $langs;

		$langs->load("products");

		$html       = "";
		$last_month = dol_now() - (30 * 24 * 60 * 60);
		$dolibarrversiontouse = DOL_VERSION;	// full string with version

		$this->products = array();

		// Build a map of installed external module names to their versions (lowercase name => version)
		$installedModules = array();
		if (is_array($modules)) {
			foreach ($modules as $objMod) {
				if (is_object($objMod) && $objMod->isCoreOrExternalModule() != 'core') {
					$moduleName = strtolower($objMod->name);
					$moduleVersion = $objMod->getVersion(0);
					$installedModules[$moduleName] = $moduleVersion;
				}
			}
		}

		$this->categorie = $options['categorie'] ?? 0;
		$this->per_page  = $options['per_page'] ?? 11;
		$this->no_page  = $options['no_page'] ?? 1;
		$this->search    = $options['search'] ?? '';

		$this->per_page = 11;	// We fix number of products per page to 11

		// Length of $search must be at least 2 charact
ers
		if (!empty($this->search) && strlen(str_replace(' ', '', (string) $this->search)) < 2) {
			$html .= '<tr class=""><td colspan="3" class="center">';
			$html .= '<br><br>';
			$html .= $langs->trans("SearchStringMinLength").'...';
			$html .= '<br><br>';
			$html .= '</td></tr>';
			return $html;
		}

		$data = [
			'categorieid' 	=> $this->categorie,
			'limit' 		=> $this->per_page,
			'page' 			=> $this->no_page,
			'search' 		=> $this->search,
			'lang' 			=> $this->lang
		];


		$this->numberTotalOfProducts = 0;

		// Special case of category goodies
		if ($this->categorie == 87) {
			$html = '<div class="shop-container">
                            <div class="shop-image">
								<a href="https://merch.dolibarr.org/" target="_blank">
	                                <img src="https://www.dolistore.com/medias/image/marketplace/img/goodies-shop.jpg" width="50%" alt="DoliStore Merch and Gifts" />
	                                <div class="shop-overlay">
	                                    <button target="new" class="shop-button">'.$langs->trans("GoodiesButtonTitle").' <i class="icon-chevron-right"></i></button>
	                                </div>
                                </a>
                            </div>
                        </div>';

			return $html;
		}

		// Fetch the products from Dolistore source

		$dolistoreProducts = array();
		$dolistoreProductsTotal = 0;
		if ($this->dolistoreApiStatus > 0 && getDolGlobalInt('MAIN_ENABLE_EXTERNALMODULES_DOLISTORE')) {
			$getDolistoreProducts = $this->callApi('products', $data);

			if (!isset($getDolistoreProducts['response']) || !is_array($getDolistoreProducts['response']) || ($getDolistoreProducts['status_code'] != 200 && $getDolistoreProducts['status_code'] != 201)) {
				$dolistoreProducts = array();
				$dolistoreProductsTotal = 0;
			} else {
				$dolistoreProducts = $this->adaptData($getDolistoreProducts['response']['products'], 'dolistore');
				$dolistoreProductsTotal = (int) $getD
olistoreProducts['response']['total'];
				$this->numberTotalOfProducts += $dolistoreProductsTotal;
			}
		}

		// Fetch the products from the github repo

		$fileProducts = array();
		$fileProductsTotal = 0;
		if (!empty($this->githubFileStatus) && getDolGlobalInt('MAIN_ENABLE_EXTERNALMODULES_COMMUNITY')) {
			$fileProducts = $this->fetchModulesFromFile($data);			// Return an array with all modules from the cache filecontent in $data

			$fileProducts = $this->adaptData($fileProducts, 'githubcommunity');

			$fileProducts = $this->applyFilters($fileProducts, $data);

			$fileProductsTotal = $fileProducts['total'];

			$this->numberTotalOfProducts += $fileProductsTotal;

			$fileProducts = $fileProducts['data'];
		}

		// Number of pages
		$this->numberTotalOfPages = (int) ceil(max($fileProductsTotal / $this->per_page, $dolistoreProductsTotal / $this->per_page));

		// Merge both sources (github community modules have priority on dolistore).
		$this->products = $dolistoreProducts;
		foreach ($fileProducts as $fileProduct) {
			$id = $fileProduct['id'];
			if ($id > 0) {
				if (empty($this->products[$id])) {		// Not already present in array
					array_unshift($this->products, $fileProduct);
				} else {
					$this->products[$id] = $fileProduct;
					$this->products[$id]['category'] = $fileProduct['category'];
				}
			} else {
				array_unshift($this->products, $fileProduct);
			}
		}


		$i = 0;
		foreach ($this->products as $product) {
			$i++;

			// check new product ?
			$newapp = '';
			if ($last_month < strtotime($product['datec']) && $product["status"] != 'soon' && $product["status"] != 'development' && $product["status"] != 'experimental') {
				$newapp .= '<span class="newApp" title="'.$product['tms'].'">'.$langs->trans('New').'</span> ';
			}

			// check updated ?
			if ($newapp == '' && $last_month < strtotime($product['tms']) && $product["status"] != 'soon' && $product["status"] != 'development' && $product["status"] != 'experimental') {
				$newapp .=
 '<span class="updatedApp" title="'.$product['tms'].'">'.$langs->trans('UpdatedRecently').'</span> ';
			}

			// add image or default ?
			if ($product["cover_photo_url"] != '' && $product["cover_photo_url"] != '#') {
				$images = '<a href="'.$product["cover_photo_url"].'" class="documentpreview" target="_blank" rel="noopener noreferrer" mime="image/png" title="'.dol_escape_htmltag($product["label"].', '.$langs->trans('Version').' '.$product["module_version"]).'">';
				$images .= '<img class="imgstore" src="'.$product["cover_photo_url"].'" alt="" /></a>';
			} else {
				$images = '<img class="imgstore" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png" />';
			}

			// Set and check version
			$version = '';
			$compatible = '';
			if ($product["status"] == 'soon' || $product["status"] == 'development' || $product["status"] == 'experimental') {
				$version = '<span class="warning">'.$langs->trans("NotYetAvailable").' - '.$langs->trans("StillInDevelopment").'</span>';
				$compatible = 'NotCompatible';
			} elseif ($this->versionCompare($product["dolibarr_min"], $dolibarrversiontouse) <= 0) {
				if (!empty($product["dolibarr_max"]) && $product["dolibarr_max"] != 'auto' && $product["dolibarr_max"] != 'unknown' && $this->versionCompare($product["dolibarr_max"], $dolibarrversiontouse) >= 0) {
					// Compatible
					$version = '<span class="compatible hideonsmartphone">'.$langs->trans(
						'CompatibleUpTo',
						$dolibarrversiontouse,
						$product["dolibarr_min"],
						$product["dolibarr_max"]
					).'</span>';
					$compatible = '';
				} else {
					// Never compatible, module expired
					$version = '<span class="warning">'.$langs->trans(
						'NotCompatible',
						$dolibarrversiontouse,
						$product["dolibarr_min"],
						$product["dolibarr_max"]
					).'</span>';
					$compatible = 'NotCompatible';
				}
			} else {
				if ($product["dolibarr_min"] == 'auto' || $product["dolibarr_min"] != 'unknown') {
					// Never compatible, module expired
			
		$version = '<span class="warning">'.$langs->trans(
						'NotCompatible',
						$dolibarrversiontouse,
						$product["dolibarr_min"],
						$product["dolibarr_max"]
					).'</span>';
					$compatible = 'NotCompatible';
				} else {
					// Need update
					$version = '<span class="compatibleafterupdate">'.$langs->trans(
						'CompatibleAfterUpdate',
						$dolibarrversiontouse,
						$product["dolibarr_min"],
						$product["dolibarr_max"]
					).'</span>';
					$compatible = 'NotCompatible';
				}
			}

			// free or pay ?
			$install_link = '';
			if (array_key_exists('price_ht', $product) && price2num($product["price_ht"]) > 0) {
				$price = '<h3>'.price(price2num($product["price_ht"], 'MT'), 0, $langs, 1, -1, -1, 'EUR').' '.$langs->trans("HT").'</h3>';

				$download_link = '<a class="paddingleft paddingright valignmiddle" target="_blank" title="'.$langs->trans("View").'" href="'.$this->shop_url.'/product.php?id='.((int) $product['id']).'">';
				$download_link .= img_picto('', 'url', 'class="size2x paddingright"');
				$download_link .= '</a>';
			} else {
				$download_link = '#';
				if ($product['source'] === 'dolistore') {	// 0 on dolistore may mean 0 or a complementary fee to subscribe
					$urlview = $this->shop_url.'/product.php?id='.((int) $product["id"]);
					$price = '<h3><a href="'.$urlview.'" target="_blank">'.$langs->trans('SeeOnDoliStore').'</a></h3>';
				} elseif ($product['source'] === 'githubcommunity') {
					if (array_key_exists('price_ht', $product) && empty($product['price_ht'])) {
						if ($product['status'] == 'soon') {
							$price = '<h3>'.$langs->trans('StillInDevelopment').'</h3>';
						} else {
							$price = '<h3>'.$langs->trans('Free').'</h3>';
						}
					} else {
						if ($product["dolistore-download"]) {
							$price = '<h3><a href="'.$product["dolistore-download"].'" target="_blank">'.$langs->trans('SeeOnDoliStore').'</a></h3>';
						} else {
							$price = '<h3>'.$langs->trans('Unknown').'</h3>';
						}
					}

				} else {
					$price = '<h3>'.$langs->trans('Unknown').'</h3>';
				}

				if ($product['source'] === 'githubcommunity') {
					$download_link = '<a class="paddingleft paddingright valignmiddle" target="_blank" title="'.$langs->trans("Sources").'"  href="'.$product["link"].'">';
					$download_link .= img_picto('', 'file-code', 'class="size2x paddingright colorgrey"');
					$download_link .= '</a>';

					$urlview = $product["dolistore-download"];		// View on Dolistore
					if ($urlview) {
						$download_link .= '<a class="paddingleft paddingright valignmiddle" target="_blank" title="'.$langs->trans("View").'" href="'.$urlview.'" rel="noopener noreferrer">';
						$download_link .= img_picto('', 'url', 'class="size2x"');
						$download_link .= '</a>';
					}

					if (!empty($product['direct-download']) && $product['direct-download'] == 'yes') {
						$reg = array();
						if (preg_match('/https:.*\?id=(\d+)$/', $urlview, $reg)) {
							$urldownload = 'https://www.dolistore.com/_service_download.php?t=free&p='.$reg[1];
							$download_link .= '<a class="paddingleft paddingright valignmiddle" target="_blank" title="'.$langs->trans("Download").'" href="'.$urldownload.'" rel="noopener noreferrer">';
							$download_link .= img_picto('', 'download', 'class="size2x paddingright"');
							$download_link .= '</a>';
						}
					}
				} elseif ($product['source'] === 'dolistore') {
					$urlview = $this->shop_url.'/product.php?id='.((int) $product["id"]);
					$urldownload = 'https://www.dolistore.com/_service_download.php?t=free&p=' . $product['id'];
					$download_link = '<a class="paddingleft paddingright valignmiddle" target="_blank" title="'.$langs->trans("View").'" href="'.$urlview.'">';
					$download_link .= img_picto('', 'url', 'class="size2x"');
					$download_link .= '</a>';
					$download_link .= '<a class="paddingleft paddingright" target="_blank" title="'.$langs->trans("Download").'" href="'.$urldownload.'" rel="noopener noreferrer">';
					$downloa
d_link .= img_picto('', 'download', 'class="size2x paddingright"');
					$download_link .= '</a>';
				}

				// Direct install
				if (($product['direct-download'] && in_array($product['direct-download'], array('yes', 'dolistore'))) || $product['source'] === 'dolistore') {
					$urldownload = '';

					if ($product['source'] === 'githubcommunity') {
						$current_version = $product['module_version'] ?? '';
						$module_name = strtolower(preg_replace('/@.*$/', '', $product['ref'] ?? ''));

						// Remove "-" followed by current version at the end of the string if it exists
						$module_name = preg_replace('/-' . preg_quote($current_version, '/') . '$/', '', $module_name);

						$urldownload = 'https://github.com/Dolibarr/dolibarr-community-modules/raw/refs/heads/main/dev/build/bin/module_' . $module_name . '-' . $current_version . '.zip';

						$reg = array();
						$urlview = $product["dolistore-download"];		// View on Dolistore

						// For community modules, we download from community repo.
						// But we can force to download from dolistore if MAIN_DOWNLOAD_FROM_DOLISTORE_IN_PRIORITY is set (less reliable, less up to date)
						if ($product["direct-download"] == 'dolistore' || getDolGlobalString("MAIN_DOWNLOAD_FROM_DOLISTORE_IN_PRIORITY")) {
							if (preg_match('/https:.*\?id=(\d+)$/', $urlview, $reg)) {
								$urldownload = 'https://www.dolistore.com/_service_download.php?t=free&p='.$reg[1];
							}
						}
					}
					if ($product['source'] === 'dolistore') {
						$urldownload = 'https://www.dolistore.com/_service_download.php?t=free&p=' . $product['id'];
					}


					$disableInstall = ($compatible === 'NotCompatible') && !getDolGlobalInt('MAIN_FEATURES_LEVEL');
					// $disableInstall = false; // TODO: remove this.
					$disableInfo = $disableInstall ? dol_string_nohtmltag($version) : '';
					$fields = ['action' => 'install', 'token' => newToken()];
					foreach ($product as $key => $value) {
						$fields['producttoinstall['.$key.']'] = $val
ue;
					}

					$installConfirmMessage = $langs->transnoentities(
							"extModuleConfirmInstallText",
							$product['label'] ?? '',
							$product['module_version'] ?? '',
							$product['ref'] ?? '',
							!empty($product['tms']) ? dol_print_date($product['tms'], '%d/%m/%Y') : ''
						);
					$installConfirmMessage .= $langs->trans("Path").' : '.$urldownload;

					// Check if module is already installed locally to show "Upgrade" or "Re-install" instead of "Install"
					$buttonLabel = $langs->trans("Install");
					$remoteVersion = $product['module_version'] ?? '';
					$remoteModuleName = strtolower(preg_replace('/@.*$/', '', $product['ref'] ?? ''));
					// Remove "-" followed by current version at the end of the string if it exists
					$remoteModuleName = preg_replace('/-' . preg_quote($remoteVersion, '/') . '$/', '', $remoteModuleName);
					if (!empty($installedModules[$remoteModuleName]) && $remoteVersion && $remoteVersion != 'unknown') {
						$localVersion = $installedModules[$remoteModuleName];
						// $localVersion is guaranteed non-empty here (see !empty() test above), so only the 'unknown' value must be excluded
						if ($localVersion != 'unknown') {
							$versionDiff = $this->versionCompare($localVersion, $remoteVersion);
							if ($versionDiff < 0) {
								$buttonLabel = $langs->trans("Upgrade");
							} elseif ($versionDiff == 0) {
								$buttonLabel = $langs->trans("ReInstall");
							}
						}
					}

					$install_link = '<button class="valignmiddle ' . ($disableInstall ? 'butActionRefused' : 'butAction') . ' paddingleft paddingright"'
						. ($disableInfo     ? ' title="' . dol_escape_htmltag($disableInfo) . '"' : '')
						. (!$disableInstall ? ' data-confirm' : '')
						. (!$disableInstall ? ' data-fields="' . dol_escape_htmltag(json_encode($fields)) . '"' : '')
						. (!$disableInstall ? ' data-url="' . dol_escape_htmltag($this->url) . '"' : '')
						. (!$disableInstall ? ' data-confirm-title="' . dol_escape_htmltag($
langs->trans("extModuleConfirmInstallTitle")) . '"' : '')
						. (!$disableInstall ? ' data-confirm-text="' . dol_escape_htmltag($installConfirmMessage) . '"' : '')
						. (!$disableInstall ? ' data-confirm-label="' . dol_escape_htmltag($buttonLabel) . '"' : '')
						. '>' . $buttonLabel . '</button>';
				}
			}

			// Output the line
			$html .= '<tr class="'.(getDolOptimizeSmallScreen() ? 'app' : 'app app2').' oddeven nohover '.dol_escape_htmltag($compatible).'">';

			// Logo
			$html .= '<td class="center width150"><div class="newAppParent">';
			$html .= $newapp.$images;	// No dol_escape_htmltag, it is already escape html
			$html .= '</div></td>';

			// Description
			$html .= '<td class="margeCote minwidth400imp"><h2 class="appTitle">';
			$html .= dolPrintHTML(dol_string_nohtmltag(ucfirst($product["label"])));
			if (!empty($product['author']) && $product['author'] != 'unkownauthor') {
				$html .= '<span class="small"> &nbsp; - &nbsp; '.img_picto('', 'company', 'class="pictofixedwidth"');
				if (!empty($product['author_url'])) {
					$html .= '<a href="'.$product['author_url'].'" target="_blank">'.$product['author'].'</a>';
				} else {
					$html .= $product['author'];
				}
				$html .= '</span>';
			}
			$html .= '<br><span class="small">';
			$html .= $version;			// Version Dolibarr. No dol_escape_htmltag, it is already escape html
			$html .= '</span>';
			$html .= '</h2>';

			$html .= '<small class="appDateCreation appRef"> ';
			if (empty($product['tms'])) {
				$html .= img_picto($langs->trans('DateCreation'), 'calendar', 'class="pictofixedwidth"').'<span class="opacitymedium"><span class="hideonsmartphone">'.$langs->trans("DateCreation").': </span>';
				$html .= (!empty($product['datec']) ? dol_print_date(dol_stringtotime($product['datec']), 'day') : $langs->trans("Unknown")).'</span>';
			} else {
				$html .= img_picto($langs->trans('DateModification'), 'calendar', 'class="pictofixedwidth"').'<span class="opacitymedium">'.dol_print_date(d
ol_stringtotime($product['tms']), 'day').'</span>';
			}
			$html .= ' &nbsp; &nbsp; ';

			$html .= '<div class="appSource inline-block valigntop">';
			if ($product["source"] == 'dolistore') {
				$html .= '<img border="0" title="'.dolPrintHTML($langs->trans('Source').": DoliStore").'" class="imgautosize valignmiddle inline-block pictofixedwidth" style="height: 14px" src="'.DOL_URL_ROOT.'/theme/dolistore_squarred.svg">';
			} elseif ($product["source"] == 'githubcommunity') {
				$html .= img_picto($langs->trans('Source').': GitHub community repo', 'group', 'class="pictofixedwidth valignmiddle"');
			} else {
				$html .= img_picto($langs->trans('Source').': '.$langs->trans('Other'), 'generic', 'class="pictofixedwidth"');
			}
			$html .= '</div>';

			$html .= $langs->trans('Ref').' '.dolPrintHTML(preg_replace('/@.*$/', '', $product["ref"]));
			$html .= '</small><br>';


			$html .= '&nbsp;';
			if (!empty($product['phpmin']) && $product['phpmin'] != 'unknown') {
				$html .= ' <span class="badge-secondary small" style="padding: 3px; border-radius: 5px">PHP min '.$product['phpmin'].'</span>';
			}
			if (!empty($product['phpmax']) && $product['phpmax'] != 'unknown') {
				$html .= ' <span class="badge-secondary small" style="padding: 3px; border-radius: 5px">PHP max '.$product['phpmax'].'</span>';
			}
			$html .= '<br>';

			$html .= '<br>';
			$html .= '<div class="storedesc">'.dolPrintHTML(dol_string_nohtmltag($product["description"])).'</div>';
			$html .= '</td>';

			if (getDolOptimizeSmallScreen()) {
				$html .= '</tr><tr class="app2 oddeven nohover borderbottom '.dol_escape_htmltag($compatible).'">';
			}

			// Price - do not load if display none
			$html .= '<td class="margeCote center amount'.(getDolOptimizeSmallScreen() ? ' left" colspan="2"' : '"').'>';
			$html .= $price;
			if (($product['direct-download'] && in_array($product['direct-download'], array('yes', 'dolistore'))) || ($product['source'] === 'dolistore' && empty((float) $product['price_h
t']))) {
				if ($install_link) {
					$html .= $install_link;
				}
			}

			if (!getDolOptimizeSmallScreen()) {
				$html .= '</td>';
				$html .= '<td class="margeCote nowraponall">';
			}

			// Links
			$html .= $download_link;
			$html .= '</td>';

			$html .= '</tr>';
		}

		if (empty($this->products)) {
			$colspan = (getDolOptimizeSmallScreen() ? 1 : 3);
			$langs->load("website");

			$html .= '<tr class=""><td colspan="'.$colspan.'" class="center">';
			$html .= '<br><br>';
			$html .= $langs->trans("noResultsWereFound").'...';
			$html .= '<br><br>';
			$html .= '</td></tr>';
		}

		// JS for confirm install
		$confirmLabel = 'dol_escape_js($langs->trans("Install"))';
		$cancelLabel = 'dol_escape_js($langs->trans("Cancel"))';
		$html .= '<script>
		$(document).on("click","[data-confirm]",function(){
			var button = $(this);
			var confirmTitle = button.data("confirm-title");
			var confirmText = button.data("confirm-text");
			var buttons = {};
			buttons[button.data("confirm-label")||\'' . $confirmLabel . '\'] = function(){
				var form = $("<form method=\'POST\' style=\'display:none\'>").attr("action", button.data("url"));
				$.each(button.data("fields"), function(name, value){
					form.append($("<input type=\'hidden\'>").attr("name", name).val(value));
				});
				$("body").append(form);
				form.submit();
				$(this).dialog("close");
			};
			buttons[\'' . $cancelLabel . '\'] = function(){$(this).dialog("close");};
			$("<div>").html(confirmText).dialog({
				title: confirmTitle,
				minWidth: 580,
				modal: true,
				buttons: buttons
			});
		});
		</script>';

		$this->numberOfProducts = count($this->products);

		return $html;
	}

	/**
	 * Sort an array by a key
	 *
	 * @param string $key Key to sort by
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
		function 
(array $a, array $b) use ($key) {
			$valA = isset($a[$key]) && is_scalar($a[$key]) ? (string) $a[$key] : '';
			$valB = isset($b[$key]) && is_scalar($b[$key]) ? (string) $b[$key] : '';

			return strnatcmp($valA, $valB);
		};
	}

	/**
	 * version compare
	 *
	 * @param   string  $v1     version 1
	 * @param   string  $v2     version 2
	 * @return int              result of compare
	 */
	public function versionCompare($v1, $v2)
	{
		// Clean v1 and v2
		$v1 = str_replace(array('v', 'V'), '', $v1);
		$v2 = str_replace(array('v', 'V'), '', $v2);

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
		return 
'<a href="'.$this->get_next_url().'" class="button">'.dol_escape_htmltag($text).'</a>';
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
		if (!empty($this->search)) {
			$param_array['search_keyword'] = $this->search;
		}
		$param_array['no_page'] = $this->no_page - $sub;
		if ($this->categorie != 0) {
			$param_array['categorie'] = $this->categorie;
		}
		$param = http_build_query($param_array);
		return $this->url."&".$param;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.Sc

... [Content truncated]
