<?php
/*
 EXPERIMENTAL
 
 Copyright (C) 2016 ATM Consulting <support@atm-consulting.fr>

 This program and all files within this directory and sub directory
 is free software: you can redistribute it and/or modify it under 
 the terms of the GNU General Public License as published by the 
 Free Software Foundation, either version 3 of the License, or any 
 later version.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 
 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	Class to manage the lists view
 */
class Listview
{
    /**
     *  Constructor
     *
     *  @param      DoliDB		$db      Database handler
     *  @param      string		$id      html id
     */
	function __construct(&$db, $id)
    {
		$this->db = &$db;
		$this->id = $id;
		$this->TTotalTmp=array();
		$this->sql = '';
		$this->form = null;
		$this->totalRowToShow=0;
		$this->totalRow=0;
		
		$this->TField=array();
		
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$this->extrafields = new ExtraFields($this->db);
		$this->extralabels = $this->extrafields->fetch_name_optionals_label('product');
		$this->search_array_options=$this->extrafields->getOptionalsFromPost($this->extralabels,'','search_');
	}

    /**
     * Function to init fields
     *
     * @param   array   $TParam     array of configuration of list
     * @return bool
     */
	private function init(&$TParam)
    {
		global $conf, $langs, $user;
		
		if(!isset($TParam['hide'])) $TParam['hide']=array();
		if(!isset($TParam['link'])) $TParam['link']=array();
		if(!isset($TParam['type'])) $TParam['type']=array();
		if(!isset($TParam['orderby']['noOrder'])) $TParam['orderby']['noOrder']=array();
		if(!isset($TParam['allow-fields-select'])) $TParam['allow-fields-select'] = 0;
		
		if(!isset($TParam['list']))$TParam['list']=array();
		$TParam['list'] = array_merge(array(
			'messageNothing'=>$langs->trans('ListMessageNothingToShow')
			,'noheader'=>0
			,'useBottomPagination'=>0
			,'image'=>''
			,'title'=>$langs->trans('List')
			,'orderDown'=>''
			,'orderUp'=>''
			,'id'=>$this->id
			,'head_search'=>''
			,'export'=>array()
			,'view_type'=>''
			,'massactions'=>array()
		),$TParam['list']);
		
		if (empty($TParam['limit'])) $TParam['limit'] = array();
		
		$page = GETPOST('page');
		if (!empty($page)) $TParam['limit']['page'] = $page;
		
		$TParam['limit'] = array_merge(array('page'=>0, 'nbLine' => $conf->liste_limit, 'global'=>0), $TParam['limit']);
		
		if (GETPOST('sortfield'))
		{
			$TParam['sortfield'] = GETPOST('sortfield');
			$TParam['sortorder'] = GETPOST('sortorder');
		}
		
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
		$this->form = new Form($this->db);
	}


    /**
     * Function to know if we can search on null value
     * @param   string  $key    field name
     * @param   array   $TParam array of configuration
     * @return bool
     */
    private function getSearchNull($key, &$TParam)
    {
		return !empty($TParam['search'][$key]['allow_is_null']);
	}

    /**
     * @param string    $key    field name
     * @param string     $TParam array of configuration
     * @return array
     */
	private function getSearchKey($key, &$TParam)
    {
		$TPrefixe = array();
		if(!empty($TParam['search'][$key]['table']))
		{
			if (!is_array($TParam['search'][$key]['table'])) $TParam['search'][$key]['table'] = array($TParam['search'][$key]['table']);
			
			foreach ($TParam['search'][$key]['table'] as $prefix_table)
			{
				$TPrefixe[] = $prefix_table.'.'; 
			}
		}
		
		$TKey=array();
		if(!empty($TParam['search'][$key]['field']))
		{
			if (!is_array($TParam['search'][$key]['field'])) $TParam['search'][$key]['field'] = array($TParam['search'][$key]['field']);
			
			foreach ($TParam['search'][$key]['field'] as $i => $field)
			{
				$prefixe = !empty($TPrefixe[$i]) ? $TPrefixe[$i] : $TPrefixe[0];
				$TKey[] = $prefixe. $field ;
			}
		}
		else
		{
			$TKey[] = $TPrefixe[0].$key;
		}
		
		return $TKey;
	}
    /**
     * @param string     $TSQLMore   contain some additional sql instructions
     * @param string    $value      date with read format
     * @param string    $sKey       field name
     */
    private function addSqlFromTypeDate(&$TSQLMore, &$value, $sKey)
	{
		if(is_array($value))
		{
			$TSQLDate=array();
			if(!empty($value['start']))
			{
				$TSQLDate[]=$sKey." >= '".$value['start']."'" ;
			}

			if(!empty($value['end']))
			{
				$TSQLDate[]=$sKey." <= '".$value['end']."'" ;
			}

			if(!empty($TSQLDate)) $TSQLMore[] = implode(' AND ', $TSQLDate);
		}
		else
		{
			$TSQLMore[]=$sKey." LIKE '".$value."%'" ;
		}
	}


    /**
     * @param string     $TSQLMore   contain some additional sql instructions
     * @param string    $value      value to filter
     * @param string     $TParam     array of configuration
     * @param string    $sKey       field name
     * @param string    $key        reference of sKey to find value into TParam
     * @return bool
     */
    private function addSqlFromOther(&$TSQLMore, &$value, &$TParam, $sKey, $key)
	{
		// Do not use empty() function, statut 0 exist
		if ($value == '') return false;
		elseif($value==-1) return false;
			
		if(isset($TParam['operator'][$key]))
		{
			if($TParam['operator'][$key] == '<' || $TParam['operator'][$key] == '>' || $TParam['operator'][$key]=='=')
			{
				$TSQLMore[] = $sKey . ' ' . $TParam['operator'][$key] . ' "' . $value . '"';
			}
			elseif ($TParam['operator'][$key]=='IN')
			{
				$TSQLMore[] = $sKey . ' ' . $TParam['operator'][$key] . ' (' . $value . ')';
			}
			else
			{
				if(strpos($value,'%')===false) $value = '%'.$value.'%';
				$TSQLMore[]=$sKey." LIKE '".addslashes($value)."'" ;
			}
		}
		else
		{
            if(strpos($value,'%')===false) $value = '%'.$value.'%';
            $TSQLMore[]=$sKey." LIKE '".addslashes($value)."'" ;
		}
		
		return true;
	}


    /**
     * @param string    $sql    standard select sql
     * @param string     $TParam array of configuration
     * @return string
     */
    private function search($sql, &$TParam)
    {
		if (empty($TParam['no-auto-sql-search']) && !GETPOST("button_removefilter_x") && !GETPOST("button_removefilter.x") && !GETPOST("button_removefilter"))
		{
			foreach ($TParam['search'] as $field => $info)
			{
				$TsKey = $this->getSearchKey($field, $TParam);
				$TSQLMore = array();
				$allow_is_null = $this->getSearchNull($field,$TParam);
				
				$fieldname = !empty($info['fieldname']) ? $info['fieldname'] : 'Listview_'.$this->id.'_search_'.$field;
				
				foreach ($TsKey as $i => &$sKey)
				{
					$value = GETPOST($fieldname);
					$value_null = GETPOST('Listview_'.$this->id.'_search_on_null_'.$field);
					
					if ($allow_is_null && !empty($value_null))
					{
						$TSQLMore[] = $sKey.' IS NULL ';
						$value = '';
					}
					
					if (isset($TParam['type'][$field]) && ($TParam['type'][$field]==='date' || $TParam['type'][$field]==='datetime'))
					{
						$k = $fieldname;
						if ($info['search_type'] === 'calendars')
						{
							$value = array();
							
							$timestart = dol_mktime(0, 0, 0, GETPOST($k.'_startmonth'), GETPOST($k.'_startday'), GETPOST($k.'_startyear'));
							if ($timestart) $value['start'] = date('Y-m-d', $timestart);
							
							$timeend = dol_mktime(23, 59, 59, GETPOST($k.'_endmonth'), GETPOST($k.'_endday'), GETPOST($k.'_endyear'));
							if ($timeend) $value['end'] = date('Y-m-d', $timeend);
						}
						else
						{
							$time = dol_mktime(12, 0, 0, GETPOST($k.'month'), GETPOST($k.'day'), GETPOST($k.'year'));
							if ($time) $value = date('Y-m-d', $time);
						}
						
						if (!empty($value)) $this->addSqlFromTypeDate($TSQLMore, $value, $sKey);
					}
					else
					{
						$this->addSqlFromOther($TSQLMore, $value, $TParam, $sKey, $field);
					}
				}
				
				if (!empty($TSQLMore))
				{
					$sql.=' AND ( '.implode(' OR ',$TSQLMore).' ) ';
				}
			}
		}
		
		if ($sqlGROUPBY!='') $sql.=' GROUP BY '.$sqlGROUPBY;

		return $sql;
	}

    /**
     * @param string    $sql    standard select sql
     * @param string     $TParam array of configuration
     * @return string
     */
    public function render($sql, $TParam=array())
    {
        global $conf;
        
        $TField= & $this->TField;
		
		$this->init($TParam);

        $THeader = $this->initHeader($TParam);
		
		$sql = $this->search($sql,$TParam);
		$sql.= $this->db->order($TParam['sortfield'], $TParam['sortorder']);
	
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
		    $result = $this->db->query($sql);
		    $this->totalRow = $this->db->num_rows($result);
		}
		
		$this->parse_sql($THeader, $TField, $TParam, $sql);
		list($TTotal, $TTotalGroup)=$this->get_total($TField, $TParam);
		
		return $this->renderList($THeader, $TField, $TTotal, $TTotalGroup, $TParam);
	}

    /**
     * @param string     $THeader    the configuration of header
     * @param string     $TParam     array of configuration
     * @return array
     */
    private function setSearch(&$THeader, &$TParam)
    {
		global $langs, $form;
		
		if(empty($TParam['search'])) return array();
		
		$TSearch=array();
		
		$nb_search_in_bar = 0;
		
		foreach($THeader as $key => $libelle)
		{
			if(empty($TSearch[$key]))$TSearch[$key]='';
		}
		
		$removeFilter = (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter"));
		foreach($TParam['search'] as $key => $param_search)
		{
			if ($removeFilter) $value = '';
			
			$typeRecherche = (is_array($param_search) && isset($param_search['search_type'])) ? $param_search['search_type'] : $param_search;  
			
			$fieldname = !empty($param_search['fieldname']) ? $param_search['fieldname'] : 'Listview_'.$this->id.'_search_'.$key;
			$value = $removeFilter ? '' : GETPOST($fieldname);
			
			if(is_array($typeRecherche))
			{
				$fsearch=$form->selectarray($fieldname, $typeRecherche,$value,1);
			}
			else if($typeRecherche==='calendar')
			{
				if (!$removeFilter) $value = GETPOST($fieldname) ? mktime(0,0,0, (int) GETPOST($fieldname.'month'), (int) GETPOST($fieldname.$key.'day'), (int) GETPOST($fieldname.'year') ) : '';
				
				$fsearch = $form->select_date($value, $fieldname,0, 0, 1, "", 1, 0, 1);
			}
			else if($typeRecherche==='calendars')
			{
				$value_start = $value_end = '';
				if (!$removeFilter)
				{
					$value_start = GETPOST($fieldname.'_start') ? mktime(0,0,0, (int) GETPOST($fieldname.'_startmonth'), (int) GETPOST($fieldname.'_startday'), (int) GETPOST($fieldname.'_startyear') ) : '';
					$value_end = GETPOST($fieldname.'_end') ? mktime(0,0,0, (int) GETPOST($fieldname.'_endmonth'), (int) GETPOST($fieldname.'_endday'), (int) GETPOST($fieldname.'_endyear') ) : '';
				}
			
				$fsearch = $form->select_date($value_start,$fieldname.'_start',0, 0, 1, "", 1, 0, 1)
				. $form->select_date($value_end, $fieldname.'_end',0, 0, 1, "", 1, 0, 1);
				
			}
			else if(is_string($typeRecherche))
			{
				$fsearch=$TParam['search'][$key];	
			}
			else
            {
            	$fsearch='<input type="text" name="'.$fieldname.'" id="'.$fieldname.'" value="'.$value.'" size="10" />';
			}

			if(!empty($param_search['allow_is_null']))
			{
				$valueNull = GETPOST($fieldname.'search_on_null_'.$key) ? 1 : 0;
				$fsearch.=' '.$form->checkbox1('', $fieldname.'search_on_null_'.$key,1, $valueNull,' onclick=" if($(this).is(\':checked\')){ $(this).prev().val(\'\'); }" ').img_help(1, $langs->trans('SearchOnNUllValue'));
			}

			if(!empty($THeader[$key]))
			{
				$TSearch[$key] = $fsearch;
				$nb_search_in_bar++;
			}
		}
		
		$search_button = ' <a href="#" onclick="Listview_submitSearch(this);" class="list-search-link">'.img_search().'</a>';
		$search_button .= ' <a href="#" onclick="Listview_clearSearch(this);" class="list-search-link">'.img_searchclear().'</a>';
		
		if($nb_search_in_bar>0)
		{
			end($TSearch);
			list($key,$v) = each($TSearch);
			$TSearch[$key].=$search_button;
		}
		else
        {
			$TSearch=array();
		}
		
		return $TSearch;
	}

    /**
     * Function to analyse and calculate the total from a column
     *
     * @param string $TField    TField
     * @param string $TParam    TParam
     * @return array
     */
    private function get_total(&$TField, &$TParam)
    {
		$TTotal=$TTotalGroup=array();	
		
		if(!empty($TParam['math']) && !empty($TField[0]))
		{
			foreach($TField[0] as $field=>$value)
			{
				$TTotal[$field]='';	
				$TTotalGroup[$field] = '';
			}
		
			foreach($TParam['math'] as $field=>$typeMath)
			{
				if(is_array($typeMath))
				{
					$targetField = $typeMath[1];
					$typeMath = $typeMath[0];
				}
				else
                {
					$targetField = $field;
				}

				if($typeMath == 'groupsum')
				{
					$TTotalGroup[$field] = array('target'=>$targetField, 'values'=> $this->TTotalTmp['@groupsum'][$targetField]);
				}
				else if($typeMath=='average')
				{
					$TTotal[$field]=array_sum($this->TTotalTmp[$targetField]) / count($this->TTotalTmp[$targetField]);
				}
				elseif($typeMath=='count')
                {
					$TTotal[$field]=count($this->TTotalTmp[$targetField]);
				}
				else
                {
					$TTotal[$field]=array_sum($this->TTotalTmp[$targetField]);
				}
			}
		}
		
		return array($TTotal,$TTotalGroup);
	}

    /**
     * @return string
     */
	/*
    private function getJS()
    {
		$javaScript = '<script language="javascript">
		if(typeof(Listview_include)=="undefined") {
			document.write("<script type=\"text/javascript\" src=\"'.DOL_URL_ROOT.'/core/js/listview.js?version='.DOL_VERSION.'\"></scr");
	  		document.write("ipt>");
		}
		</script>';

		return $javaScript;
	}
    */
	
    /**
     * @param string $TParam    TParam
     * @param string $TField    TField
     * @param string $THeader   THeader
     * @return array
     */
    private function setExport(&$TParam, $TField, $THeader)
    {
		global $langs;
		
		$Tab=array();
		if(!empty($TParam['export']))
		{
			$token = GETPOST('token');
			if(empty($token)) $token = md5($this->id.time().rand(1,9999));

            $_SESSION['token_list_'.$token] = gzdeflate( serialize( array(
                'title'=>$this->title,
                'sql'=>$this->sql,
                'TBind'=>$this->TBind,
                'TChamps'=>$TField,
                'TEntete'=>$THeader
            )));

            foreach($TParam['export'] as $mode_export)
            {
                $Tab[] = array(
                    'label'=>$langs->trans('Export'.$mode_export),
                    'url'=>dol_buildpath('/abricot/downlist.php',1),
                    'mode'=>$mode_export,
                    'token'=>$token,
                    'session_name'=>session_name()
                );
			}
			
		}
		
		return $Tab;
	}

    /**
     * @param string $TField        TField
     * @param string $TTotalGroup   TTotalGroup
     * @return array
     */
    private function addTotalGroup($TField, $TTotalGroup)
    {
		global $langs;
		
		$Tab=array();
		$proto_total_line = array();
		$tagbase = $old_tagbase = null;
		$addGroupLine = false;
		
		foreach($TField as $k=>&$line)
		{
			if(empty($proto_total_line))
			{
				foreach($line as $field=>$value)
				{
					$proto_total_line[$field] = '';
				}
				$group_line = $proto_total_line;	
			}
			
			$addGroupLine = false;
			
			$tagbase = '';
			foreach($line as $field=>$value)
			{
				if(!empty($TTotalGroup[$field]))
				{
					$tagbase.=$value.'|';
					$group_line[$field] = '<div style="text-align:right; font-weight:bold; color:#552266;">'.(empty($value) ? $langs->trans('Empty') : $value ).' : </div>';
					$group_line[$TTotalGroup[$field]['target']] = '<div style="text-align:right; font-weight:bold; color:#552266;">'.price($TTotalGroup[$field]['values'][$value]).'</div>';
					$addGroupLine = true;
				}
			}
			
			if(!is_null($old_tagbase) && $old_tagbase!=$tagbase && $addGroupLine)
			{
				$Tab[] = $previous_group_line;
			}
			
			$old_tagbase = $tagbase;
			$previous_group_line = $group_line;
			$group_line = $proto_total_line;
			
			$Tab[] = $line;
		}

		if($addGroupLine)
		{
			$Tab[] = $previous_group_line;
		}
		
		return $Tab;
	}

    /**
     * @param string $THeader   THeader
     * @param string $TField    TField
     * @param string $TTotal    TTotal
     * @param string $TTotalGroup   TTotalGroup
     * @param string $TParam        TParam
     * @return string
     */
    private function renderList(&$THeader, &$TField, &$TTotal, &$TTotalGroup, &$TParam)
    {
		global $bc,$form;
		
		$TSearch = $this->setSearch($THeader, $TParam);
		$TExport = $this->setExport($TParam, $TField, $THeader);
		$TField = $this->addTotalGroup($TField,$TTotalGroup);
		
		//$out = $this->getJS();
		
		$massactionbutton= empty($TParam['list']['massactions']) ? '' : $form->selectMassAction('', $TParam['list']['massactions']);
		
		$dolibarr_decalage = $this->totalRow > $this->totalRowToShow ? 1 : 0;
		ob_start();
		print_barre_liste($TParam['list']['title'], $TParam['limit']['page'], $_SERVER["PHP_SELF"], '&'.$TParam['list']['param_url'], $TParam['sortfield'], $TParam['sortorder'], $massactionbutton, $this->totalRowToShow+$dolibarr_decalage, $this->totalRow, $TParam['list']['image'], 0, '', '', $TParam['limit']['nbLine']);
		$out .= ob_get_clean();
		
		$classliste='liste';
		if(!empty($TParam['head_search'])) {
			$out.='<div class="liste_titre liste_titre_bydiv centpercent">';
			$out.=$TParam['head_search'];
			$out.='</div>';
			
			$classliste.=' listwithfilterbefore';
		}
	
		$out.= '<div class="div-table-responsive">';
		$out.= '<table id="'.$this->id.'" class="'.$classliste.'" width="100%"><thead>';
			
    	if(count($TSearch)>0)
		{
			$out.='<tr class="liste_titre liste_titre_search barre-recherche">';
			
			foreach ($THeader as $field => $head)
			{
				if ($field === 'selectedfields')
				{
					$out.= '<th class="liste_titre" align="right">'.$this->form->showFilterAndCheckAddButtons(0).'</th>';
				}
				else
				{
					$moreattrib = 'style="width:'.$head['width'].';text-align:'.$head['text-align'].'"';
					$out .= '<th class="liste_titre" '.$moreattrib.'>'.$TSearch[$field].'</th>';
				}
			}
			
			$out.='</tr>';
		}
				
		$out.= '<tr class="liste_titre">';
		foreach($THeader as $field => $head)
		{
			$moreattrib = '';
			$search = '';
			$prefix = '';

			$label = $head['label'];
			
			if ($field === 'selectedfields')
			{
				$moreattrib = 'align="right" ';
				$prefix = 'maxwidthsearch ';

				if(!empty($TParam['list']['massactions'])) {
					$label.=$form->showCheckAddButtons('checkforselect', 1);
				}
				
			}

			if (empty($head['width'])) $head['width'] = 'auto';
			if (!empty($head['width']) && !empty($head['text-align'])) $moreattrib .= 'style="width:'.$head['width'].';text-align:'.$head['text-align'].'"';

			if (isset($TParam['search'][$field]['search_type']) && $TParam['search'][$field]['search_type'] !== false)
			{
				$TsKey = $this->getSearchKey($field, $TParam);
				if (!empty($TsKey)) $search = implode(',', $TsKey);
				else $search = $field;
			}

			$out .= getTitleFieldOfList($label, 0, $_SERVER["PHP_SELF"], $search, '', $moreparam, $moreattrib, $TParam['sortfield'], $TParam['sortorder'], $prefix);
			$out .= $head['more'];
		}

		//$out .= '<th aligne="right" class="maxwidthsearch liste_titre">--</th>';
		$out .= '</tr>';
		
		$out.='</thead><tbody>';
		
		if(empty($TField))
		{
			if (!empty($TParam['list']['messageNothing'])) $out .= '<tr class="oddeven"><td colspan="'.(count($THeader)+1).'"><span class="opacitymedium">'.$TParam['list']['messageNothing'].'</span></td></tr>';
		}
		else
        {
			$line_number = 0;
			foreach($TField as $fields)
			{
				if($this->in_view($TParam, $line_number))
				{
					$out.='<tr class="oddeven"> <!-- '.$field.' -->';

					foreach ($THeader as $field => $head)
					{
						$value_aff =(isset($fields[$field]) ? $fields[$field] : '&nbsp;');
						
						if ($field === 'selectedfields')
						{
							$head['text-align']='center';
							if(!empty($TParam['list']['massactions'])) {
								$arrayofselected=array(); // TODO get in param
								$selected=0;
								if (in_array($obj->rowid, $arrayofselected)) $selected=1;
								$value_aff.='<input id="cb'.$fields['rowid'].'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$fields['rowid'].'"'.($selected?' checked="checked"':'').'>';
							}
						}
						
						$moreattrib = 'style="width:'.$head['width'].';text-align:'.$head['text-align'].'"';
						$out.='<td class="'.$field.'" '.$moreattrib.'>'.$value_aff.'</td>';
					}

					$out.='</tr>';
				}
				
				$line_number++;
			}
			
			$out.='</tbody>';
			
			if (!empty($TParam['list']['haveTotal']))
			{
				$out.='<tfoot><tr class="liste_total">';
			
				foreach ($THeader as $field => $head)
				{
					if (isset($TTotal[$field]))
					{
						$moreattrib = 'style="width:'.$head['width'].';text-align:'.$head['text-align'].'"';
						$out.='<td align="right" class="'.$field.'" '.$moreattrib.'>'.price($TTotal[$field]).'</td>';
					}
				}
					
				$out.='</tr></tfoot>';
			}
		}

		$out .= '</table>';
		$out .= '</div>';
		
		return $out;
	}

    /**
     * @param string $db        Db
     * @param string $TField    TField
     * @param string $TParam    TParam
     */
    public function renderArray(&$db, $TField, $TParam=array())
    {
		$this->typeRender = 'array';

		$TField=array();	
		
		$this->init($TParam);
		$THeader = $this->initHeader($TParam);
		
		$this->parse_array($THeader, $TField, $TParam);
		list($TTotal, $TTotalGroup)=$this->get_total($TField, $TParam);
		
		$this->renderList($THeader, $TField, $TTotal, $TTotalGroup, $TParam);
	}


    /**
     * @param string $THeader   THeader
     * @param string $TField    TField
     * @param string $TParam    TParam
     * @return bool
     */
    private function parse_array(&$THeader, &$TField, &$TParam)
    {
		$this->totalRow = count($TField);
		
		$this->THideFlip = array_flip($TParam['hide']);
		$this->TTotalTmp=array();
		
		if (empty($TField)) return false;
		
		foreach($TField as $row)
		{
			$this->set_line($THeader, $TField, $TParam, $row);
		}
	}

	
	private function initHeader(&$TParam)
	{
		global $user,$conf;
		
		$THeader = array();
		
		$TField=$TFieldVisibility=array();
		foreach ($TParam['title'] as $field => $value)
		{
			$TField[$field]=true;
		}
		
		$contextpage=md5($_SERVER['PHP_SELF']);
		if(!empty($TParam['allow-fields-select']))
		{
			$selectedfields = GETPOST('Listview'.$this->id.'_selectedfields');
			
			if(!empty($selectedfields))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				$tabparam['MAIN_SELECTEDFIELDS_'.$contextpage] = $selectedfields;
	    		$result=dol_set_user_param($this->db, $conf, $user, $tabparam);
			}
			
			$tmpvar='MAIN_SELECTEDFIELDS_'.$contextpage;
			if (! empty($user->conf->{$tmpvar}))
			{
				$tmparray = explode(',', $user->conf->{$tmpvar});
				$TParam['hide'] = array();
		        foreach($TField as $field => $dummy)
		        {
		          	$label = $TParam['title'][$field];
					if(!in_array($field, $tmparray))
					{
				  		$TParam['hide'][] = $field;
						$visible = 0;
				  	}
					else
					{
						// Overrive search from extrafields
						// for the type as 'checkbox', 'chkbxlst', 'sellist' we should use code instead of id (example: I declare a 'chkbxlst' to have a link with dictionnairy, I have to extend it with the 'code' instead 'rowid')
						if (isset($this->extralabels[$field])) $TParam['search'][$field] = $this->extrafields->showInputField($field, $this->search_array_options['search_options_'.$field], '', '', 'search_');
						$visible = 1;
					}
		            
					$TFieldVisibility[$field] = array(
						'label'=>$label
						,'checked'=>$visible
					);
		        }
			}
			else
            {
				foreach($TField as $field=>$dummy)
		        {
		        	$label = isset($TParam['title'][$field]) ? $TParam['title'][$field] : $field;
					$visible = (!in_array($field, $TParam['hide'])) ? 1 : 0;
					$TFieldVisibility[$field]=array(
						'label'=>$label,
						'checked'=>$visible
					);
				}
			}	

			$selectedfields = $this->form->multiSelectArrayWithCheckbox('Listview'.$this->id.'_selectedfields', $TFieldVisibility, $contextpage);	// This also change content of $arrayfields_0
		}

		foreach ($TParam['title'] as $field => $label)
		{
			$visible = (!in_array($field, $TParam['hide'])) ? 1 : 0;
			if($visible)
			{
				$THeader[$field] = array(
					'label'=>$label,
					'order'=>(in_array($field, $TParam['orderby']['noOrder']) ? 0 : 1),
					'width'=>(!empty($TParam['size']['width'][$field]) ? $TParam['size']['width'][$field] : 'auto'),
					'text-align'=>(!empty($TParam['position']['text-align'][$field]) ? $TParam['position']['text-align'][$field] : 'auto'),
					'rank'=>(!empty($TParam['position']['rank'][$field]) ? $TParam['position']['rank'][$field] : 0),
					'more'=>''
				);
			}
		}
		
		uasort($THeader,array('Listview','sortHeaderRank'));
		
		$THeader['selectedfields']['label']=$selectedfields;
		
		return $THeader;
	}
	
	public function sortHeaderRank(&$a, &$b) {
		if($a['rank']>$b['rank']) return 1;
		else if($a['rank']<$b['rank']) return -1;
		else return 0;
		
	}

    /**
     * @param string $TParam        TParam
     * @param string $line_number   aaa
     * @return bool
     */
    private function in_view(&$TParam, $line_number)
    {
		global $conf;

		if(!empty($_REQUEST['get-all-for-export'])) return true;

		$page_number = !empty($TParam['limit']['page']) ? $TParam['limit']['page'] : 1;
		$line_per_page = !empty($TParam['limit']['nbLine']) ? $TParam['limit']['nbLine'] : $conf->liste_limit;
		
		$start = ($page_number-1) * $line_per_page;
		$end = ($page_number* $line_per_page) -1;
		
		if($line_number>=$start && $line_number<=$end) return true;
		else return false;
	}

    /**
     * Apply function to result and set fields array
     * 
     * @param string $THeader       array of headers
     * @param string $TField        array of fields
     * @param string $TParam        array of parameters
     * @param string $currentLine   object containing current sql result
     */
    private function set_line(&$THeader, &$TField, &$TParam, $currentLine)
    {
        global $conf;

        $line_number = count($TField);

        if($this->in_view($TParam,$line_number))
        {
			$this->totalRowToShow++;
            $row=array(); 
            $trans = array();
            foreach($currentLine as $kF=>$vF)$trans['@'.$kF.'@'] = addslashes($vF);
            
            foreach($THeader as $field=>$dummy)
            {
            	$value = isset($currentLine->{$field}) ? $currentLine->{$field}: '';
            	
                if(is_object($value))
                {
                    if(get_class($value)=='stdClass') {$value=print_r($value, true);}
                    else $value=(string) $value;
                }

                $trans['@'.$field.'@'] = addslashes($value);

                if(!empty($TParam['math'][$field]))
                {
                    $float_value = (double) strip_tags($value);
                    $this->TTotalTmp[$field][] = $float_value;
                }

                if(!in_array($field,$TParam['hide']))
                {
                    $row[$field]=$value;

                    if(isset($TParam['eval'][$field]) && in_array($field,array_keys($row)))
                    {
                        $strToEval = 'return '.strtr( $TParam['eval'][$field],  array_merge( $trans, array('@val@'=>addslashes( $row[$field] ))  )).';';
                        $row[$field] = eval($strToEval);
                        
                    }

                    if(isset($TParam['type'][$field]) && !isset($TParam['eval'][$field]))
                    {
                        if($TParam['type'][$field]=='date' || $TParam['type'][$field]=='datetime' )
                        {
                        
                            if($row[$field] != '0000-00-00 00:00:00' && $row[$field] != '1000-01-01 00:00:00' && $row[$field] != '0000-00-00' && !empty($row[$field]))
                            {
                                if($TParam['type'][$field]=='datetime')$row[$field] = dol_print_date(strtotime($row[$field]),'dayhour');
                                else $row[$field] = dol_print_date(strtotime($row[$field]),'day');
                            }
                            else
                            {
                                $row[$field] = '';
                            }
                        }

                        if($TParam['type'][$field]=='hour') { $row[$field] = date('H:i', strtotime($row[$field])); }
                        if($TParam['type'][$field]=='money') { $row[$field] = '<div align="right">'.price($row[$field],0,'',1,-1,2).'</div>'; }
                        if($TParam['type'][$field]=='number') { $row[$field] = '<div align="right">'.price($row[$field]).'</div>'; }
                        if($TParam['type'][$field]=='integer') { $row[$field] = '<div align="right">'.((int) $row[$field]).'</div>'; }
                    }

                    if(isset($TParam['link'][$field]))
                    {
                        if(empty($row[$field]) && $row[$field]!==0 && $row[$field]!=='0')$row[$field]='(vide)';
                        $row[$field]= strtr( $TParam['link'][$field],  array_merge( $trans, array('@val@'=>$row[$field]))) ;
                    }

                    if(isset($TParam['translate'][$field]))
                    {
                        if(isset($TParam['translate'][$field][''])) unset($TParam['translate'][$field]['']);

                        $row[$field] = strtr( $row[$field], $TParam['translate'][$field]);
                    }
                }
            }
        }
        else
        {
            $row = array();

            foreach($currentLine as $field=>&$value)
            {
                if(!isset($this->THideFlip[$field]))
                {
                    if(isset($TParam['math'][$field]) && !empty($TParam['math'][$field]))
                    {
                        $float_value = (double) strip_tags($value);
                        $this->TTotalTmp[$field][] = $float_value;
                    }

                    $row[$field] = $value;
                }
            }
        }

        if(!empty($TParam['math'][$field]))
        {
            foreach($row as $field=>$value)
            {
                if(!empty($TParam['math'][$field]) && is_array($TParam['math'][$field]))
                {
                    $toField = $TParam['math'][$field][1];
                    $float_value = (double) strip_tags($row[$toField]);
                    $this->TTotalTmp['@groupsum'][$toField][ $row[$field]  ] += $float_value;
                }
            }
        }

        $TField[] = $row;
	}

    /**
     * @param string $sql       sql
     * @param string $TParam    TParam
     * @return string
     */
    private function limitSQL($sql, &$TParam)
    {
		if(!empty($TParam['limit']['global']) && strpos($sql,'LIMIT ')===false )
		{
			$sql.=' LIMIT '.(int) $TParam['limit']['global'];
		}
		else if(!empty($TParam['limit'])) $sql.= $this->db->plimit($TParam['limit']['nbLine']+1, $TParam['limit']['page'] * $TParam['limit']['nbLine']);
		
		
		return $sql;
	}

    /**
     * @param string $THeader   THeader
     * @param string $TField    TField
     * @param string $TParam    TParam
     * @param string $sql       sql
     */
    private function parse_sql(&$THeader, &$TField, &$TParam, $sql)
    {
    	$this->sql = $this->limitSQL($sql, $TParam);
		
		$this->TTotalTmp=array();
		$this->THideFlip = array_flip($TParam['hide']);
		
		$res = $this->db->query($this->sql);
		if($res!==false)
		{
			dol_syslog(get_class($this)."::parse_sql id=".$this->id." sql=".$this->sql, LOG_DEBUG);
			
			if(empty($this->totalRow))$this->totalRow = $this->db->num_rows($res);
			
			while($currentLine = $this->db->fetch_object($res))
            {
				$this->set_line($THeader, $TField, $TParam, $currentLine);
			}
		}
		else
        {
			dol_syslog(get_class($this)."::parse_sql id=".$this->id." sql=".$this->sql, LOG_ERR);
		}
	}	
	
	static function getCachedOjbect($class_name, $fk_object) {
		global $db, $TCacheListObject;
		
		if(!class_exists($class_name)) return false;
		
		if(empty($TCacheListObject)) $TCacheListObject = array();
		if(empty($TCacheListObject[$class_name])) $TCacheListObject[$class_name]  =array();
		
		if(empty($TCacheListObject[$class_name][$fk_object])) {
			$TCacheListObject[$class_name][$fk_object]= new $class_name($db);
			if( $TCacheListObject[$class_name][$fk_object]->fetch($fk_object)<0) {
				return false;
			}
		}
		
		return $TCacheListObject[$class_name][$fk_object];
	}
	
}
