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
include_once DOL_DOCUMENT_ROOT.'/admin/dolistore/class/PSWebServiceLibrary.class.php';

/**
 * Class DolistoreModel
 */
class Dolistore extends DolistoreModel
{
    // params
    public $start       // beginning of pagination
        , $end          // end of pagination
        , $per_page     // pagination: display per page
        , $categorie    // the current categorie
        , $search       // the search keywords
        // setups
        , $url          // the url of this page
        , $shop_url     // the url of the shop
        , $vat_rate     // the vat rate used in the shop (prices are provided without vat)
        , $lang         // the integer representing the lang in the store
        , $debug_api;   // usefull if no dialog

    function __construct($options = array('start' => 0, 'end' => 10, 'per_page' => 50, 'categorie' => 0))
    {
        global $conf, $langs;

        $this->start     = $options['start'];
        $this->end       = $options['end'];
        $this->per_page  = $options['per_page'];
        $this->categorie = $options['categorie'];
        $this->search    = $options['search'];

        $this->url       = DOL_URL_ROOT.'/admin/modules.php?mode=marketplace';
        $this->shop_url  = 'https://www.dolistore.com/index.php?controller=product&id_product=';
        $this->vat_rate  = 1.2; // 20% de TVA
        $this->debug_api = false;

        if ($this->end == 0) {
            $this->end = $this->per_page;
        }

        $langtmp    = explode('_', $langs->defaultlang);
        $lang       = $langtmp[0];
        $lang_array = array('fr'=>1, 'en'=>2, 'es'=>3, 'it'=>4, 'de'=>5);
        if (! in_array($lang, array_keys($lang_array))) $lang = 'en';
        $this->lang = $lang_array[$lang]; // 1=fr 2=en ...

        try {
            $this->api = new PrestaShopWebservice($conf->global->MAIN_MODULE_DOLISTORE_API_SRV,
                $conf->global->MAIN_MODULE_DOLISTORE_API_KEY, $this->debug_api);

            // Here we set the option array for the Webservice : we want products resources
            $opt             = array();
            $opt['resource'] = 'products';
            // make a search to limit the id returned.
            if ($this->search != '') {
                $opt2        = array();
                $opt2['url'] = $conf->global->MAIN_MODULE_DOLISTORE_API_SRV.'/api/search?query='.$this->search.'&language='.$this->lang;
                // Call
                $xml         = $this->api->get($opt2);
                $products    = array();
                foreach ($xml->products->children() as $product) {
                    $products[] = (int) $product['id'];
                }
                $opt['filter[id]'] = '['.implode('|', $products).']';
            } elseif ($this->categorie != 0) {
                $opt2             = array();
                $opt2['resource'] = 'categories';
                $opt2['id']       = $this->categorie;
                // Call
                $xml              = $this->api->get($opt2);
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
            // Call
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
        } catch (PrestaShopWebserviceException $e) {
            // Here we are dealing with errors
            $trace = $e->getTrace();
            if ($trace[0]['args'][0] == 404) die('Bad ID');
            else if ($trace[0]['args'][0] == 401) die('Bad auth key');
            else die('Can not access to '.$conf->global->MAIN_MODULE_DOLISTORE_API_SRV);
        }
    }

    function get_previous_url()
    {
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

    function get_next_url()
    {
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

    function version_compare($v1, $v2)
    {
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


/**
 * Class DolistoreModel
 */
class DolistoreModel
{

    function get_categories($parent = 0)
    {
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
                    .'title="'.dol_escape_htmltag(strip_tags($cat->description->language[$this->lang])).'"'
                    .'>'.$cat->name->language[$this->lang].' <sup>'.$cat->nb_products_recursive.'</sup></a></h3>';
                $html .= self::get_categories($cat->id);
                $html .= "</li>\n";
            } elseif (trim($cat->id_parent) == $parent && $cat->active == 1 && trim($cat->id_parent) != 0) { // si cat est de ce niveau
                $select = ($cat->id == $this->categorie) ? ' selected' : '';
                $html   .= '<li><a class="link2cat'.$select.'" href="?mode=marketplace&categorie='.$cat->id.'"'
                    .' title="'.dol_escape_htmltag(strip_tags($cat->description->language[$this->lang])).'" '
                    .'>'.$cat->name->language[$this->lang].' <sup>'.$cat->nb_products_recursive.'</sup></a>';
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

    function get_products()
    {
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
                $image_url = dol_buildPath('/dolistore/ajax/image.php?id_product=', 2).$product->id.'&id_image='.$product->id_default_image;
                $images    = '<a href="'.$image_url.'" class="fancybox" rel="gallery'.$product->id.'" title="'.$product->name->language[$this->lang].', '.$langs->trans('Version').' '.$product->module_version.'">'.
                    '<img src="'.$image_url.'&quality=home_default" style="max-height:250px;max-width: 210px;" alt="" /></a>';
            } else {
                $images = '<img src="'.dol_buildPath('/dolistore/img/NoImageAvailable.png', 2).'" />';
            }

            // free or pay ?
            if ($product->price > 0) {
                $price         = '<h3>'.price(round((float) $product->price * $this->vat_rate, 2)).'&nbsp;&euro;</h3>';
                $download_link = '<a target="_blank" href="'.$this->shop_url.$product->id.'"><img width="32" src="'.dol_buildPath('/dolistore/img/follow.png',
                        2).'" /></a>';
            } else {
                $price         = $langs->trans('Free');
                $download_link = '<a target="_blank" href="'.$this->shop_url.$product->id.'"><img width="32" src="'.dol_buildPath('/dolistore/img/Download-128.png',
                        2).'" /></a>';
            }

            //checking versions
            if ($this->version_compare($product->dolibarr_min, DOL_VERSION) <= 0) {
                if ($this->version_compare($product->dolibarr_max, DOL_VERSION) >= 0) {
                    //compatible
                    $version    = '<span class="compatible">'.$langs->trans('CompatibleUpTo', $product->dolibarr_max,
                            $product->dolibarr_min, $product->dolibarr_max).'</span>';
                    $compatible = '';
                } else {
                    //never compatible, module expired
                    $version    = '<span class="notcompatible">'.$langs->trans('NotCompatible', DOL_VERSION,
                            $product->dolibarr_min, $product->dolibarr_max).'</span>';
                    $compatible = 'NotCompatible';
                }
            } else {
                //need update
                $version    = '<span class="compatibleafterupdate">'.$langs->trans('CompatibleAfterUpdate', DOL_VERSION,
                        $product->dolibarr_min, $product->dolibarr_max).'</span>';
                $compatible = 'NotCompatible';
            }

            //output template
            $html .= '<tr class="app '.$parity.' '.$compatible.'">
                <td align="center" width="210"><div class="newAppParent">'.$newapp.$images.'</div></td>
                <td class="margeCote"><h2 class="appTitle"><a target="_blank" href="'.$this->shop_url.$product->id.'">'.$product->name->language[$this->lang].'</a><span class="details button">Details</span>'
                .'<br/><small>'.$version.'</small></h2>
                    <small> '.dol_print_date(strtotime($product->date_upd)).' - '.$langs->trans('Référence').': '.$product->reference.' - '.$langs->trans('Id').': '.$product->id.'</small><br><br>'.$product->description_short->language[$this->lang].'</td>
                <td style="display:none;" class="long_description">'.$product->description->language[$this->lang].'</td>
                <td class="margeCote" align="right">'.$price.'</td>
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
}
