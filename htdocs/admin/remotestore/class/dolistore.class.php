<?php
/*
 * Copyright (C) 2017		 Oscss-Shop       <support@oscss-shop.fr>.
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
if (!class_exists('PrestaShopWebservice')) { // We keep this because some modules add this lib too into a different path. This is to avoid "Cannot declare class PrestaShopWebservice" errors.
	include_once DOL_DOCUMENT_ROOT.'/admin/remotestore/class/PSWebServiceLibrary.class.php';
}


/**
 * Class Dolistore
 */
class Dolistore
{
	/**
	 * beginning of pagination
	 * @var int
	 */
	public $start;

	/**
	 * end of pagination
	 * @var int
	 */
	public $end;

	/**
	 * @var int Pagination: display per page
	 */
	public $per_page;
	/**
	 * @var int The current categorie
	 */
	public $categorie;
	/**
	 * @var ?SimpleXMLElement
	 */
	public $categories; // an array of categories

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
	 * @var int
	 */
	public $lang; // the integer representing the lang in the store
	/**
	 * @var bool
	 */
	public $debug_api; // useful if no dialog
	/**
	 * @var PrestaShopWebservice
	 */
	public $api;
	/**
	 * @var ?SimpleXMLElement
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

		$this->url       = DOL_URL_ROOT.'/admin/modules.php?mode=marketplace';
		$this->shop_url  = 'https://www.dolistore.com/index.php?controller=product&id_product=';
		$this->debug_api = $debug;

		$langtmp    = explode('_', $langs->defaultlang);
		$lang       = $langtmp[0];
		$lang_array = array('en' => 1, 'fr' => 2, 'es' => 3, 'it' => 4, 'de' => 5); // Into table ps_lang of Prestashop - 1
		if (!in_array($lang, array_keys($lang_array))) {
			$lang = 'en';
		}
		$this->lang = $lang_array[$lang];
	}

	/**
	 * Load data from remote Dolistore market place.
	 * This fills ->categories
	 *
	 * @return	void
	 */
	public function getRemoteCategories()
	{
		global $conf;

		try {
			$this->api = new PrestaShopWebservice(getDolGlobalString('MAIN_MODULE_DOLISTORE_API_SRV'), getDolGlobalString('MAIN_MODULE_DOLISTORE_API_KEY'), $this->debug_api);
			dol_syslog("Call API with MAIN_MODULE_DOLISTORE_API_SRV = ".getDolGlobalString('MAIN_MODULE_DOLISTORE_API_SRV'));
			// conf MAIN_MODULE_DOLISTORE_API_KEY is for the login of basic auth. There is no password as it is public data.

			// Here we set the option array for the Webservice : we want categories resources
			$opt              = array();
			$opt['resource']  = 'categories';
			$opt['display']   = '[id,id_parent,nb_products_recursive,active,is_root_category,name,description]';
			$opt['sort']      = 'id_asc';

			// Call
			dol_syslog("Call API with opt = ".var_export($opt, true));
			$xml              = $this->api->get($opt);
			// @phan-suppress-next-line PhanPluginUnknownObjectMethodCall
			$this->categories = $xml->categories->children();
		} catch (PrestaShopWebserviceException $e) {
			// Here we are dealing with errors
			$trace = $e->getTrace();
			if ($trace[0]['args'][0] == 404) {
				die('Bad ID');
			} elseif ($trace[0]['args'][0] == 401) {
				die('Bad auth key');
			} else {
				print 'Can not access to ' . getDolGlobalString('MAIN_MODULE_DOLISTORE_API_SRV').'<br>';
				print $e->getMessage();
			}
		}
	}

	/**
	 * Load data from remote Dolistore market place.
	 * This fills ->products
	 *
	 * @param 	array{start:int,end:int,per_page:int,categorie:int,search:string}	$options	Options. If 'categorie' is defined, we filter products on this category id
	 * @return	void
	 */
	public function getRemoteProducts($options = array('start' => 0, 'end' => 10, 'per_page' => 50, 'categorie' => 0, 'search' => ''))
	{
		global $conf;

		$this->start     = $options['start'];
		$this->end       = $options['end'];
		$this->per_page  = $options['per_page'];
		$this->categorie = $options['categorie'];
		$this->search    = $options['search'];

		if ($this->end == 0) {
			$this->end = $this->per_page;
		}

		try {
			$this->api = new PrestaShopWebservice(getDolGlobalString('MAIN_MODULE_DOLISTORE_API_SRV'), getDolGlobalString('MAIN_MODULE_DOLISTORE_API_KEY'), $this->debug_api);
			dol_syslog("Call API with MAIN_MODULE_DOLISTORE_API_SRV = ".getDolGlobalString('MAIN_MODULE_DOLISTORE_API_SRV'));
			// conf MAIN_MODULE_DOLISTORE_API_KEY is for the login of basic auth. There is no password as it is public data.

			// Here we set the option array for the Webservice : we want products resources
			$opt             = array();
			$opt['resource'] = 'products';
			$opt2            = array();

			// make a search to limit the id returned.
			if ($this->search != '') {
				$opt2['url'] = getDolGlobalString('MAIN_MODULE_DOLISTORE_API_SRV') . '/api/search?query='.$this->search.'&language='.$this->lang; // It seems for search, key start with

				// Call
				dol_syslog("Call API with opt2 = ".var_export($opt2, true));
				$xml         = $this->api->get($opt2);

				$products    = array();
				// @phan-suppress-next-line PhanPluginUnknownObjectMethodCall
				foreach ($xml->products->children() as $product) {
					$products[] = (int) $product['id'];
				}
				$opt['filter[id]'] = '['.implode('|', $products).']';
			} elseif ($this->categorie != 0) {   // We filter on category, so we first get list of product id in this category
				// $opt2['url'] is set by default to $this->url.'/api/'.$options['resource'];
				$opt2['resource'] = 'categories';
				$opt2['id']       = $this->categorie;

				// Call
				dol_syslog("Call API with opt2 = ".var_export($opt2, true));
				$xml              = $this->api->get($opt2);

				$products         = array();
				// @phan-suppress-next-line PhanPluginUnknownObjectMethodCall
				foreach ($xml->category->associations->products->children() as $product) {
					$products[] = (int) $product->id;
				}
				$opt['filter[id]'] = '['.implode('|', $products).']';
			}
			$opt['display']        = '[id,name,id_default_image,id_category_default,reference,price,condition,show_price,date_add,date_upd,description_short,description,module_version,dolibarr_min,dolibarr_max]';
			$opt['sort']           = 'id_desc';
			$opt['filter[active]'] = '[1]';
			$opt['limit']          = "$this->start,$this->end";
			// $opt['filter[id]'] contains list of product id that are result of search

			// Call API to get the detail
			dol_syslog("Call API with opt = ".var_export($opt, true));
			$xml                   = $this->api->get($opt);
			// @phan-suppress-next-line PhanPluginUnknownObjectMethodCall
			$this->products        = $xml->products->children();
		} catch (PrestaShopWebserviceException $e) {
			// Here we are dealing with errors
			$trace = $e->getTrace();
			if ($trace[0]['args'][0] == 404) {
				die('Bad ID');
			} elseif ($trace[0]['args'][0] == 401) {
				die('Bad auth key');
			} else {
				print 'Can not access to ' . getDolGlobalString('MAIN_MODULE_DOLISTORE_API_SRV').'<br>';
				print $e->getMessage();
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return tree of Dolistore categories. $this->categories must have been loaded before.
	 *
	 * @param 	int			$parent		Id of parent category
	 * @return 	string
	 */
	public function get_categories($parent = 0)
	{
		// phpcs:enable
		if (!isset($this->categories)) {
			die('not possible');
		}
		if ($parent != 0) {
			$html = '<ul>';
		} else {
			$html = '';
		}

		$nbofcateg = count($this->categories);
		for ($i = 0; $i < $nbofcateg; $i++) {
			$cat = $this->categories[$i];

			if ((string) $cat->name->language[$this->lang - 1] == 'Goodies') {
				continue;
			}

			if ($cat->is_root_category == 1 && $parent == 0) {
				$html .= '<li class="root"><h3 class="nomargesupinf marginleftonly"><a class="nomargesupinf link2cat" href="?mode=marketplace&categorie='.((int) $cat->id).'" ';
				$html .= 'title="'.dol_escape_htmltag(strip_tags($cat->description->language[$this->lang - 1])).'">';
				//$html .= dol_escape_htmltag($cat->name->language[$this->lang - 1]);
				$html .= 'DoliStore';
				$html .= ' &nbsp;<span class="opacitymedium small valignmiddle">('.dol_escape_htmltag($cat->nb_products_recursive).')</span></a></h3>';
				$html .= self::get_categories((int) $cat->id);
				$html .= "</li>\n";
			} elseif (trim($cat->id_parent) == $parent && $cat->active == 1 && trim($cat->id_parent) != 0) { // si cat est de ce niveau
				$select = ($cat->id == $this->categorie) ? ' selected' : '';
				$html .= '<li><a class="link2cat'.$select.'" href="?mode=marketplace&categorie='.((int) $cat->id).'"';
				$html .= ' title="'.dol_escape_htmltag(strip_tags($cat->description->language[$this->lang - 1])).'" ';
				$html .= '>'.dol_escape_htmltag($cat->name->language[$this->lang - 1]);
				$html .= ' &nbsp;<span class="opacitymedium small valignmiddle">('.dol_escape_htmltag($cat->nb_products_recursive).')</span></a>';
				$html .= self::get_categories((int) $cat->id);
				$html .= "</li>\n";
			}
		}

		if ($html == '<ul>') {
			return '';
		}
		if ($parent != 0) {
			return $html.'</ul>';
		} else {
			return $html;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return list of product formatted for output
	 *
	 * @param	int		$nbmaxtoshow	Nb max of product to show
	 * @return 	string					HTML output
	 */
	public function get_products($nbmaxtoshow = 10)
	{
		// phpcs:enable
		global $langs;

		$html       = "";
		$last_month = dol_now() - (30 * 24 * 60 * 60);
		$dolibarrversiontouse = DOL_VERSION;
		//$dolibarrversiontouse = 20.0;

		$i = 0;
		foreach ($this->products as $product) {
			$i++;
			if ($i > $nbmaxtoshow) {
				break;
			}

			// check new product ?
			$newapp = '';
			if ($last_month < strtotime($product->date_add)) {
				$newapp .= '<span class="newApp">'.$langs->trans('New').'</span> ';
			}

			// check updated ?
			if ($last_month < strtotime($product->date_upd) && $newapp == '') {
				$newapp .= '<span class="updatedApp">'.$langs->trans('Updated').'</span> ';
			}

			// add image or default ?
			if ($product->id_default_image != '') {
				$image_url = DOL_URL_ROOT.'/admin/remotestore/ajax/image.php?id_product='.urlencode((string) (((int) $product->id))).'&id_image='.urlencode((string) (((int) $product->id_default_image)));
				$images = '<a href="'.$image_url.'" class="documentpreview" target="_blank" rel="noopener noreferrer" mime="image/png" title="'.dol_escape_htmltag($product->name->language[$this->lang - 1].', '.$langs->trans('Version').' '.$product->module_version).'">';
				$images .= '<img class="imgstore" src="'.$image_url.'&quality=home_default" alt="" /></a>';
			} else {
				$images = '<img class="imgstore" src="'.DOL_URL_ROOT.'/admin/dolistore/img/NoImageAvailable.png" />';
			}

			// free or pay ?
			if ($product->price > 0) {
				$price = '<h3>'.price(price2num($product->price, 'MT'), 0, $langs, 1, -1, -1, 'EUR').' '.$langs->trans("HT").'</h3>';
				$download_link = '<a target="_blank" href="'.$this->shop_url.urlencode($product->id).'"><img width="32" src="'.DOL_URL_ROOT.'/admin/remotestore/img/follow.png" /></a>';
			} else {
				$price         = '<h3>'.$langs->trans('Free').'</h3>';
				$download_link = '<a class="paddingleft paddingright" target="_blank" href="'.$this->shop_url.urlencode($product->id).'"><img width="32" src="'.DOL_URL_ROOT.'/admin/remotestore/img/follow.png" /></a>';
				$download_link .= '<a class="paddingleft paddingright" target="_blank" rel="noopener noreferrer" href="'.$this->shop_url.urlencode($product->id).'"><img width="32" src="'.DOL_URL_ROOT.'/admin/dolistore/img/Download-128.png" /></a>';
			}

			// Set and check version
			$version = '';
			if ($this->version_compare($product->dolibarr_min, $dolibarrversiontouse) <= 0) {
				if ($this->version_compare($product->dolibarr_max, $dolibarrversiontouse) >= 0) {
					//compatible
					$version = '<span class="compatible">'.$langs->trans(
						'CompatibleUpTo',
						$product->dolibarr_max,
						$product->dolibarr_min,
						$product->dolibarr_max
					).'</span>';
					$compatible = '';
				} else {
					//never compatible, module expired
					$version = '<span class="notcompatible">'.$langs->trans(
						'NotCompatible',
						$dolibarrversiontouse,
						$product->dolibarr_min,
						$product->dolibarr_max
					).'</span>';
					$compatible = 'NotCompatible';
				}
			} else {
				//need update
				$version = '<span class="compatibleafterupdate">'.$langs->trans(
					'CompatibleAfterUpdate',
					$dolibarrversiontouse,
					$product->dolibarr_min,
					$product->dolibarr_max
				).'</span>';
				$compatible = 'NotCompatible';
			}

			//output template
			$html .= '<tr class="app oddeven '.dol_escape_htmltag($compatible).'">';
			$html .= '<td class="center" width="160"><div class="newAppParent">';
			$html .= $newapp.$images;	// No dol_escape_htmltag, it is already escape html
			$html .= '</div></td>';
			$html .= '<td class="margeCote"><h2 class="appTitle">';
			$html .= dol_escape_htmltag(dol_string_nohtmltag($product->name->language[$this->lang - 1]));
			$html .= '<br><small>';
			$html .= $version;			// No dol_escape_htmltag, it is already escape html
			$html .= '</small></h2>';
			$html .= '<small> '.dol_print_date(dol_stringtotime($product->date_upd), 'dayhour').' - '.$langs->trans('Ref').': '.dol_escape_htmltag($product->reference).' - '.dol_escape_htmltag($langs->trans('Id')).': '.((int) $product->id).'</small><br>';
			$html .= '<br>'.dol_escape_htmltag(dol_string_nohtmltag($product->description_short->language[$this->lang - 1]));
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

		if (count($this->products) > $nbmaxtoshow) {
			$html .= '<tr class=""><td colspan="3" class="center">';
			$html .= '<br><br>';
			$html .= $langs->trans("ThereIsMoreThanXAnswers", $nbmaxtoshow).'...';
			$html .= '<br><br>';
			$html .= '</td></tr>';
		}

		return $html;
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
		if ($this->start < $this->per_page) {
			$sub = 0;
		} else {
			$sub = $this->per_page;
		}
		$param_array['start'] = $this->start - $sub;
		$param_array['end']   = $this->end - $sub;
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
			$add = $this->per_page;
		}
		$param_array['start'] = $this->start + $add;
		$param_array['end']   = $this->end + $add;
		if ($this->categorie != 0) {
			$param_array['categorie'] = $this->categorie;
		}
		$param = http_build_query($param_array);
		return $this->url."&".$param;
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
}
