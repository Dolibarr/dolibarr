<?php
/*
 * Copyright (C) 2017		 Oscss-Shop       <support@oscss-shop.fr>.
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
<<<<<<< HEAD
include_once DOL_DOCUMENT_ROOT.'/admin/dolistore/class/PSWebServiceLibrary.class.php';
=======
if (! class_exists('PrestaShopWebservice')) // We keep this because some modules add this lib too into a different path. This is to avoid "Cannot declare class PrestaShopWebservice" errors.
{
    include_once DOL_DOCUMENT_ROOT.'/admin/dolistore/class/PSWebServiceLibrary.class.php';
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


/**
 * Class Dolistore
 */
class Dolistore
{
<<<<<<< HEAD
	// params
	public $start;       // beginning of pagination
	public $end;         // end of pagination
=======
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

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	public $per_page;    // pagination: display per page
	public $categorie;   // the current categorie
	public $search;      // the search keywords

	// setups
	public $url;         // the url of this page
	public $shop_url;    // the url of the shop
<<<<<<< HEAD
	public $vat_rate;    // the vat rate used in the shop (prices are provided without vat)
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	public $lang;        // the integer representing the lang in the store
	public $debug_api;   // usefull if no dialog


	/**
	 * Constructor
	 *
	 * @param	boolean		$debug		Enable debug of request on screen
	 */
<<<<<<< HEAD
	function __construct($debug=false)
=======
	public function __construct($debug = false)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $conf, $langs;

		$this->url       = DOL_URL_ROOT.'/admin/modules.php?mode=marketplace';
		$this->shop_url  = 'https://www.dolistore.com/index.php?controller=product&id_product=';
<<<<<<< HEAD
		$this->vat_rate  = 1.2; // 20% de TVA
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->debug_api = $debug;

		$langtmp    = explode('_', $langs->defaultlang);
		$lang       = $langtmp[0];
		$lang_array = array('en'=>1, 'fr'=>2, 'es'=>3, 'it'=>4, 'de'=>5);	// Into table ps_lang of Prestashop - 1
		if (! in_array($lang, array_keys($lang_array))) $lang = 'en';
		$this->lang = $lang_array[$lang];
	}

	/**
	 * Load data from remote Dolistore market place.
	 * This fills ->categories
	 *
<<<<<<< HEAD
	 * @param 	array 	$options	Options
	 * @return	void
	 */
	function getRemoteData($options = array('start' => 0, 'end' => 10, 'per_page' => 50, 'categorie' => 0))
	{
		global $conf, $langs;
=======
	 * @return	void
	 */
	public function getRemoteCategories()
	{
	    global $conf;

	    try {
	        $this->api = new PrestaShopWebservice($conf->global->MAIN_MODULE_DOLISTORE_API_SRV, $conf->global->MAIN_MODULE_DOLISTORE_API_KEY, $this->debug_api);
	        dol_syslog("Call API with MAIN_MODULE_DOLISTORE_API_SRV = ".$conf->global->MAIN_MODULE_DOLISTORE_API_SRV);
	        // $conf->global->MAIN_MODULE_DOLISTORE_API_KEY is for the login of basic auth. There is no password as it is public data.

	        // Here we set the option array for the Webservice : we want categories resources
	        $opt              = array();
	        $opt['resource']  = 'categories';
	        $opt['display']   = '[id,id_parent,nb_products_recursive,active,is_root_category,name,description]';
	        $opt['sort']      = 'id_asc';

	        // Call
	        dol_syslog("Call API with opt = ".var_export($opt, true));
	        $xml              = $this->api->get($opt);
	        $this->categories = $xml->categories->children();
	    } catch (PrestaShopWebserviceException $e) {
	        // Here we are dealing with errors
	        $trace = $e->getTrace();
	        if ($trace[0]['args'][0] == 404) die('Bad ID');
	        elseif ($trace[0]['args'][0] == 401) die('Bad auth key');
	        else
	        {
	            print 'Can not access to '.$conf->global->MAIN_MODULE_DOLISTORE_API_SRV.'<br>';
	            print $e->getMessage();
	        }
	    }
	}

	/**
	 * Load data from remote Dolistore market place.
	 * This fills ->products
	 *
	 * @param 	array 	$options	Options. If 'categorie' is defined, we filter products on this category id
	 * @return	void
	 */
	public function getRemoteProducts($options = array('start' => 0, 'end' => 10, 'per_page' => 50, 'categorie' => 0, 'search' => ''))
	{
		global $conf;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		$this->start     = $options['start'];
		$this->end       = $options['end'];
		$this->per_page  = $options['per_page'];
		$this->categorie = $options['categorie'];
		$this->search    = $options['search'];

		if ($this->end == 0) {
			$this->end = $this->per_page;
		}

		try {
			$this->api = new PrestaShopWebservice($conf->global->MAIN_MODULE_DOLISTORE_API_SRV, $conf->global->MAIN_MODULE_DOLISTORE_API_KEY, $this->debug_api);
			dol_syslog("Call API with MAIN_MODULE_DOLISTORE_API_SRV = ".$conf->global->MAIN_MODULE_DOLISTORE_API_SRV);
			// $conf->global->MAIN_MODULE_DOLISTORE_API_KEY is for the login of basic auth. There is no password as it is public data.

			// Here we set the option array for the Webservice : we want products resources
			$opt             = array();
			$opt['resource'] = 'products';
			$opt2            = array();

			// make a search to limit the id returned.
			if ($this->search != '') {
<<<<<<< HEAD
				$opt2['url'] = $conf->global->MAIN_MODULE_DOLISTORE_API_SRV.'/api/search?query='.$this->search.'&language='.$this->lang;

				// Call
				//var_dump($this->api);

				dol_syslog("Call API with opt = ".var_export($opt, true));
				dol_syslog("Call API with opt2 = ".var_export($opt2, true));

				$xml         = $this->api->get($opt2);
=======
				$opt2['url'] = $conf->global->MAIN_MODULE_DOLISTORE_API_SRV.'/api/search?query='.$this->search.'&language='.$this->lang;  // It seems for search, key start with

				// Call
				dol_syslog("Call API with opt2 = ".var_export($opt2, true));
				$xml         = $this->api->get($opt2);

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				$products    = array();
				foreach ($xml->products->children() as $product) {
					$products[] = (int) $product['id'];
				}
				$opt['filter[id]'] = '['.implode('|', $products).']';
<<<<<<< HEAD
			} elseif ($this->categorie != 0) {
				$opt2['resource'] = 'categories';
				$opt2['id']       = $this->categorie;
				// Call
				$xml              = $this->api->get($opt2);
=======
			} elseif ($this->categorie != 0) {   // We filter on category, so we first get list of product id in this category
			    // $opt2['url'] is set by default to $this->url.'/api/'.$options['resource'];
				$opt2['resource'] = 'categories';
				$opt2['id']       = $this->categorie;

				// Call
				dol_syslog("Call API with opt2 = ".var_export($opt2, true));
				$xml              = $this->api->get($opt2);

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				$products         = array();
				foreach ($xml->category->associations->products->children() as $product) {
					$products[] = (int) $product->id;
				}
				$opt['filter[id]'] = '['.implode('|', $products).']';
			}
			$opt['display']        = '[id,name,id_default_image,id_category_default,reference,price,condition,show_price,date_add,date_upd,description_short,description,module_version,dolibarr_min,dolibarr_max]';
			$opt['sort']           = 'id_desc';
			$opt['filter[active]'] = '[1]';
			$opt['limit']          = "$this->start,$this->end";
			// $opt['filter[id]'] contais list of product id that are result of search

<<<<<<< HEAD

			// Call API to get the detail
			dol_syslog("Call API with opt = ".var_export($opt, true));
			dol_syslog("Call API with opt2 = ".var_export($opt2, true));
			$xml                   = $this->api->get($opt);
			$this->products        = $xml->products->children();


			// Here we set the option array for the Webservice : we want categories resources
			$opt              = array();
			$opt['resource']  = 'categories';
			$opt['display']   = '[id,id_parent,nb_products_recursive,active,is_root_category,name,description]';
			$opt['sort']      = 'id_asc';
			// Call
			$xml              = $this->api->get($opt);
			$this->categories = $xml->categories->children();
=======
			// Call API to get the detail
			dol_syslog("Call API with opt = ".var_export($opt, true));
			$xml                   = $this->api->get($opt);
			$this->products        = $xml->products->children();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		} catch (PrestaShopWebserviceException $e) {
			// Here we are dealing with errors
			$trace = $e->getTrace();
			if ($trace[0]['args'][0] == 404) die('Bad ID');
<<<<<<< HEAD
			else if ($trace[0]['args'][0] == 401) die('Bad auth key');
=======
			elseif ($trace[0]['args'][0] == 401) die('Bad auth key');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			else
			{
				print 'Can not access to '.$conf->global->MAIN_MODULE_DOLISTORE_API_SRV.'<br>';
				print $e->getMessage();
			}
		}
	}

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Return tree of Dolistore categories. $this->categories must have been loaded before.
	 *
	 * @param 	int			$parent		Id of parent category
	 * @return 	string
	 */
<<<<<<< HEAD
	function get_categories($parent = 0)
	{
=======
	public function get_categories($parent = 0)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		if (!isset($this->categories)) die('not possible');
		if ($parent != 0) {
			$html = '<ul>';
		} else {
			$html = '';
		}

		$nbofcateg = count($this->categories);
		for ($i = 0; $i < $nbofcateg; $i++)
		{
			$cat = $this->categories[$i];
			if ($cat->is_root_category == 1 && $parent == 0) {
				$html .= '<li class="root"><h3 class="nomargesupinf"><a class="nomargesupinf link2cat" href="?mode=marketplace&categorie='.$cat->id.'" '
<<<<<<< HEAD
					.'title="'.dol_escape_htmltag(strip_tags($cat->description->language[$this->lang])).'"'
						.'>'.$cat->name->language[$this->lang].' <sup>'.$cat->nb_products_recursive.'</sup></a></h3>';
=======
					.'title="'.dol_escape_htmltag(strip_tags($cat->description->language[$this->lang - 1])).'"'
						.'>'.$cat->name->language[$this->lang - 1].' <sup>'.$cat->nb_products_recursive.'</sup></a></h3>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
						$html .= self::get_categories($cat->id);
						$html .= "</li>\n";
			} elseif (trim($cat->id_parent) == $parent && $cat->active == 1 && trim($cat->id_parent) != 0) { // si cat est de ce niveau
				$select = ($cat->id == $this->categorie) ? ' selected' : '';
				$html   .= '<li><a class="link2cat'.$select.'" href="?mode=marketplace&categorie='.$cat->id.'"'
<<<<<<< HEAD
					.' title="'.dol_escape_htmltag(strip_tags($cat->description->language[$this->lang])).'" '
						.'>'.$cat->name->language[$this->lang].' <sup>'.$cat->nb_products_recursive.'</sup></a>';
=======
					.' title="'.dol_escape_htmltag(strip_tags($cat->description->language[$this->lang - 1])).'" '
						.'>'.$cat->name->language[$this->lang - 1].' <sup>'.$cat->nb_products_recursive.'</sup></a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
						$html   .= self::get_categories($cat->id);
						$html   .= "</li>\n";
			} else {

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

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Return list of product formated for output
	 *
	 * @return string			HTML output
	 */
<<<<<<< HEAD
	function get_products()
	{
=======
	public function get_products()
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		global $langs, $conf;
		$html       = "";
		$parity     = "pair";
		$last_month = time() - (30 * 24 * 60 * 60);
		foreach ($this->products as $product) {
			$parity = ($parity == "impair") ? 'pair' : 'impair';

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
				$image_url = DOL_URL_ROOT.'/admin/dolistore/ajax/image.php?id_product='.$product->id.'&id_image='.$product->id_default_image;
<<<<<<< HEAD
				$images    = '<a href="'.$image_url.'" class="fancybox" rel="gallery'.$product->id.'" title="'.$product->name->language[$this->lang].', '.$langs->trans('Version').' '.$product->module_version.'">'.
=======
				$images    = '<a href="'.$image_url.'" class="fancybox" rel="gallery'.$product->id.'" title="'.$product->name->language[$this->lang - 1].', '.$langs->trans('Version').' '.$product->module_version.'">'.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					'<img src="'.$image_url.'&quality=home_default" style="max-height:250px;max-width: 210px;" alt="" /></a>';
			} else {
				$images = '<img src="'.DOL_URL_ROOT.'/admin/dolistore/img/NoImageAvailable.png" />';
			}

			// free or pay ?
			if ($product->price > 0) {
<<<<<<< HEAD
				$price         = '<h3>'.price(round((float) $product->price * $this->vat_rate, 2)).'&nbsp;&euro;</h3>';
=======
			    $price         = '<h3>'.price(price2num($product->price, 'MT'), 0, $langs, 1, -1, -1, 'EUR').' '.$langs->trans("HT").'</h3>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				$download_link = '<a target="_blank" href="'.$this->shop_url.$product->id.'"><img width="32" src="'.DOL_URL_ROOT.'/admin/dolistore/img/follow.png" /></a>';
			} else {
				$price         = '<h3>'.$langs->trans('Free').'</h3>';
				$download_link = '<a target="_blank" href="'.$this->shop_url.$product->id.'"><img width="32" src="'.DOL_URL_ROOT.'/admin/dolistore/img/Download-128.png" /></a>';
				$download_link.= '<br><br><a target="_blank" href="'.$this->shop_url.$product->id.'"><img width="32" src="'.DOL_URL_ROOT.'/admin/dolistore/img/follow.png" /></a>';
			}

			//checking versions
			if ($this->version_compare($product->dolibarr_min, DOL_VERSION) <= 0) {
				if ($this->version_compare($product->dolibarr_max, DOL_VERSION) >= 0) {
					//compatible
<<<<<<< HEAD
					$version    = '<span class="compatible">'.$langs->trans('CompatibleUpTo', $product->dolibarr_max,
						$product->dolibarr_min, $product->dolibarr_max).'</span>';
						$compatible = '';
				} else {
					//never compatible, module expired
					$version    = '<span class="notcompatible">'.$langs->trans('NotCompatible', DOL_VERSION,
						$product->dolibarr_min, $product->dolibarr_max).'</span>';
						$compatible = 'NotCompatible';
=======
                    $version = '<span class="compatible">'.$langs->trans('CompatibleUpTo', $product->dolibarr_max,
						$product->dolibarr_min, $product->dolibarr_max).'</span>';
					$compatible = '';
				} else {
					//never compatible, module expired
                    $version = '<span class="notcompatible">'.$langs->trans('NotCompatible', DOL_VERSION,
						$product->dolibarr_min, $product->dolibarr_max).'</span>';
					$compatible = 'NotCompatible';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				}
			} else {
				//need update
				$version    = '<span class="compatibleafterupdate">'.$langs->trans('CompatibleAfterUpdate', DOL_VERSION,
					$product->dolibarr_min, $product->dolibarr_max).'</span>';
<<<<<<< HEAD
					$compatible = 'NotCompatible';
=======
				$compatible = 'NotCompatible';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			}

			//.'<br><a class="inline-block valignmiddle" target="_blank" href="'.$this->shop_url.$product->id.'"><span class="details button">'.$langs->trans("SeeInMarkerPlace").'</span></a>

			//output template
			$html .= '<tr class="app '.$parity.' '.$compatible.'">
<<<<<<< HEAD
                <td align="center" width="210"><div class="newAppParent">'.$newapp.$images.'</div></td>
                <td class="margeCote"><h2 class="appTitle">'.$product->name->language[$this->lang]
						.'<br/><small>'.$version.'</small></h2>
                    <small> '.dol_print_date(dol_stringtotime($product->date_upd), 'dayhour').' - '.$langs->trans('Ref').': '.$product->reference.' - '.$langs->trans('Id').': '.$product->id.'</small><br><br>'.$product->description_short->language[$this->lang].'</td>
                <td style="display:none;" class="long_description">'.$product->description->language[$this->lang].'</td>
                <td class="margeCote" align="center">'.$price.'
                </td>
                <td class="margeCote">'.$download_link.'</td>
                </tr>';
		}
		return $html;
	}

	function get_previous_link($text = '<<')
	{
		return '<a href="'.$this->get_previous_url().'" class="button">'.$text.'</a>';
	}

	function get_next_link($text = '>>')
	{
		return '<a href="'.$this->get_next_url().'" class="button">'.$text.'</a>';
	}

	function get_previous_url()
	{
=======
                <td class="center" width="210"><div class="newAppParent">'.$newapp.$images.'</div></td>
                <td class="margeCote"><h2 class="appTitle">'.$product->name->language[$this->lang - 1]
						.'<br/><small>'.$version.'</small></h2>
                    <small> '.dol_print_date(dol_stringtotime($product->date_upd), 'dayhour').' - '.$langs->trans('Ref').': '.$product->reference.' - '.$langs->trans('Id').': '.$product->id.'</small><br><br>'.$product->description_short->language[$this->lang - 1].'</td>
                <td style="display:none;" class="long_description">'.$product->description->language[$this->lang - 1].'</td>
                <td class="margeCote center">'.$price.'
                </td>
                <td class="margeCote">'.$download_link.'</td>
                </tr>';
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
		return '<a href="'.$this->get_previous_url().'" class="button">'.$text.'</a>';
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
		return '<a href="'.$this->get_next_url().'" class="button">'.$text.'</a>';
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
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	}

	function get_next_url()
	{
=======
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
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$param_array = array();
		if (count($this->products) < $this->per_page) {
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

<<<<<<< HEAD
	function version_compare($v1, $v2)
	{
=======
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
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
