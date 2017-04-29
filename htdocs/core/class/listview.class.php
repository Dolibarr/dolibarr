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
		),$TParam['list']);
		
		if (empty($TParam['limit'])) $TParam['limit'] = array();
		
		$page = GETPOST('page');
		if (!empty($page)) $TParam['limit']['page'] = $page+1; // TODO dolibarr start page at 0 instead 1
		
		$TParam['limit'] = array_merge(array('page'=>1, 'nbLine' => $conf->liste_limit, 'global'=>0), $TParam['limit']);
		
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
     * @param array     $TParam array of configuration
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
     * @param timestamp    $date   date to convert
     * @return int|string   Date TMS or ''
     */
    private function dateToSQLDate($date)
    {
		return $this->db->idate($date);
	}


    /**
     * @param array     $TSQLMore   contain some additional sql instructions
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
//				$valueDeb = $this->dateToSQLDate($value['start'].' 00:00:00');
				$TSQLDate[]=$sKey." >= '".$value['start']."'" ;
			}

			if(!empty($value['end']))
			{
//				$valueFin = $this->dateToSQLDate($value['end'].' 23:59:59');
				$TSQLDate[]=$sKey." <= '".$value['end']."'" ;
			}

			if(!empty($TSQLDate)) $TSQLMore[] = implode(' AND ', $TSQLDate);
		}
		else
		{
//			$value = $this->dateToSQLDate($value);
			$TSQLMore[]=$sKey." LIKE '".$value."%'" ;
		}
	}


    /**
     * @param array     $TSQLMore   contain some additional sql instructions
     * @param string    $value      value to filter
     * @param array     $TParam     array of configuration
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
     * @param array     $TParam array of configuration
     * @return string
     */
    private function search($sql, &$TParam)
    {
		$ListPOST = GETPOST('Listview');
		
		if (!GETPOST("button_removefilter_x") && !GETPOST("button_removefilter.x") && !GETPOST("button_removefilter"))
		{
			foreach ($TParam['search'] as $field => $info)
			{
				$TsKey = $this->getSearchKey($field, $TParam);
				$TSQLMore = array();
				$allow_is_null = $this->getSearchNull($field,$TParam);
				
				foreach ($TsKey as $i => &$sKey)
				{
					$value = '';
					if (isset($ListPOST[$this->id]['search'][$field])) $value = $ListPOST[$this->id]['search'][$field];
					
					if ($allow_is_null && !empty($ListPOST[$this->id]['search_on_null'][$field]))
					{
						$TSQLMore[] = $sKey.' IS NULL ';
						$value = '';
					}
					
					if (isset($TParam['type'][$field]) && ($TParam['type'][$field]==='date' || $TParam['type'][$field]==='datetime'))
					{
						$k = 'Listview_'.$this->id.'_search_'.$field;
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
     * @param array     $TParam array of configuration
     * @return string
     */
    public function render($sql, $TParam=array())
    {
		$TField=array();
		
		$this->init($TParam);
		$THeader = $this->initHeader($TParam);
		
		$sql = $this->search($sql,$TParam);
		$sql = $this->order_by($sql, $TParam);		
		
		$this->parse_sql($THeader, $TField, $TParam, $sql);	
		list($TTotal, $TTotalGroup)=$this->get_total($TField, $TParam);
		
		return $this->renderList($THeader, $TField, $TTotal, $TTotalGroup, $TParam);
	}

    /**
     * @param array     $THeader    the configuration of header
     * @param array     $TParam     array of configuration
     * @return array
     */
    private function setSearch(&$THeader, &$TParam)
    {
		global $langs, $form;
		
		if(empty($TParam['search'])) return array();
		
		$TSearch=array();
		
		$nb_search_in_bar = 0;
		
		if(!empty($TParam['search']))
		{
			foreach($THeader as $key => $libelle)
			{
				if(empty($TSearch[$key]))$TSearch[$key]='';
			}
		}	
		
		$ListPOST = GETPOST('Listview');
		$removeFilter = (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter"));
		foreach($TParam['search'] as $key => $param_search)
		{
			$value = isset($ListPOST[$this->id]['search'][$key]) ? $ListPOST[$this->id]['search'][$key] : '';
			if ($removeFilter) $value = '';
			
			$typeRecherche = (is_array($param_search) && isset($param_search['search_type'])) ? $param_search['search_type'] : $param_search;  
			
			if(is_array($typeRecherche))
			{
				$fsearch=$form->selectarray('Listview['.$this->id.'][search]['.$key.']', $typeRecherche,$value,1);
			}
			else if($typeRecherche==='calendar')
			{
				if (!$removeFilter) $value = GETPOST('Listview_'.$this->id.'_search_'.$key) ? mktime(0,0,0, (int) GETPOST('Listview_'.$this->id.'_search_'.$key.'month'), (int) GETPOST('Listview_'.$this->id.'_search_'.$key.'day'), (int) GETPOST('Listview_'.$this->id.'_search_'.$key.'year') ) : '';
				
				$fsearch = $form->select_date($value, 'Listview_'.$this->id.'_search_'.$key,0, 0, 1, "", 1, 0, 1);
			}
			else if($typeRecherche==='calendars')
			{
				$value_start = $value_end = '';
				if (!$removeFilter)
				{
					$value_start = GETPOST('Listview_'.$this->id.'_search_'.$key.'_start') ? mktime(0,0,0, (int) GETPOST('Listview_'.$this->id.'_search_'.$key.'_startmonth'), (int) GETPOST('Listview_'.$this->id.'_search_'.$key.'_startday'), (int) GETPOST('Listview_'.$this->id.'_search_'.$key.'_startyear') ) : '';
					$value_end = GETPOST('Listview_'.$this->id.'_search_'.$key.'_end') ? mktime(0,0,0, (int) GETPOST('Listview_'.$this->id.'_search_'.$key.'_endmonth'), (int) GETPOST('Listview_'.$this->id.'_search_'.$key.'_endday'), (int) GETPOST('Listview_'.$this->id.'_search_'.$key.'_endyear') ) : '';
				}
			
				$fsearch = $form->select_date($value_start, 'Listview_'.$this->id.'_search_'.$key.'_start',0, 0, 1, "", 1, 0, 1)
						 . $form->select_date($value_end, 'Listview_'.$this->id.'_search_'.$key.'_end',0, 0, 1, "", 1, 0, 1);
				
			}
			else if(is_string($typeRecherche))
			{
				$fsearch=$TParam['search'][$key];	
			}
			else
            {
				$fsearch='<input type="text" name="Listview['.$this->id.'][search]['.$key.']" id="Listview['.$this->id.'][search]['.$key.']" value="'.$value.'" size="15" />';
			}

			if(!empty($param_search['allow_is_null']))
			{
				$valueNull = isset($ListPOST[$this->id]['search_on_null'][$key]) ? 1 : 0;
				$fsearch.=' '.$form->checkbox1('', 'Listview['.$this->id.'][search_on_null]['.$key.']',1, $valueNull,' onclick=" if($(this).is(\':checked\')){ $(this).prev().val(\'\'); }" ').img_help(1, $langs->trans('SearchOnNUllValue'));
			}

			if(!empty($THeader[$key]))
			{
				$TSearch[$key] = $fsearch;
				$nb_search_in_bar++;
			}
			else
            {
				$label = !empty($TParam['title'][$key]) ? $TParam['title'][$key] : $key ;
				$TParam['list']['head_search'].= '<th>'.$label.'</th>';
//				$TParam['list']['head_search'].='<div><span style="min-width:200px;display:inline-block;">'.$label.'</span> '.$fsearch.'</div>';	
			}
		}
		
		$search_button = ' <a href="#" onclick="Listview_submitSearch(this);" class="list-search-link">'.img_search().'</a>';

		if(!empty($TParam['list']['head_search']))
		{
			$TParam['list']['head_search']='<div style="float:right;">'.$search_button.'</div>'.$TParam['list']['head_search'];
		}
		
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
     * @param $TField
     * @param $TParam
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

    /**
     * @param $TParam
     * @param $TField
     * @param $THeader
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
     * @param $TField
     * @param $TTotalGroup
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
     * @param $THeader
     * @param $TField
     * @param $TTotal
     * @param $TTotalGroup
     * @param $TParam
     * @return string
     */
    private function renderList(&$THeader, &$TField, &$TTotal, &$TTotalGroup, &$TParam)
    {
		global $bc;
		
		$TSearch = $this->setSearch($THeader, $TParam);
		$TExport = $this->setExport($TParam, $TField, $THeader);
		$TField = $this->addTotalGroup($TField,$TTotalGroup);
		
		$out = $this->getJS();
		
		$dolibarr_decalage = $this->totalRow > $this->totalRowToShow ? 1 : 0;
		ob_start();
		print_barre_liste($TParam['list']['title'], $TParam['limit']['page']-1, $_SERVER["PHP_SELF"], '&'.$TParam['list']['param_url'], $TParam['sortfield'], $TParam['sortorder'], '', $this->totalRowToShow+$dolibarr_decalage, $this->totalRow, $TParam['list']['image'], 0, '', '', $TParam['limit']['nbLine']);
		$out .= ob_get_clean();
		
	
		$out.= '<table id="'.$this->id.'" class="liste" width="100%"><thead>';
			
		$out.= '<tr class="liste_titre">';
		foreach($THeader as $field => $head)
		{
			$moreattrib = '';
			$search = '';
			$prefix = '';

			if ($field === 'selectedfields')
			{
				$moreattrib = 'align="right" ';
				$prefix = 'maxwidthsearch ';
			}

			if (empty($head['width'])) $head['width'] = 'auto';
			if (!empty($head['width']) && !empty($head['text-align'])) $moreattrib .= 'style="width:'.$head['width'].';text-align:'.$head['text-align'].'"';

			if (isset($TParam['search'][$field]['search_type']) && $TParam['search'][$field]['search_type'] !== false)
			{
				$TsKey = $this->getSearchKey($field, $TParam);
				if (!empty($TsKey)) $search = implode(',', $TsKey);
				else $search = $field;
			}

			$out .= getTitleFieldOfList($head['label'], 0, $_SERVER["PHP_SELF"], $search, '', $moreparam, $moreattrib, $TParam['sortfield'], $TParam['sortorder'], $prefix);
			$out .= $head['more'];
		}
		
		//$out .= '<th aligne="right" class="maxwidthsearch liste_titre">--</th>';
		$out .= '</tr>';
		
		if(count($TSearch)>0)
		{
			$out.='<tr class="liste_titre barre-recherche">';
			
			foreach ($THeader as $field => $head)
			{
				if ($field === 'selectedfields')
				{
					$out.= '<td class="liste_titre" align="right">'.$this->form->showFilterAndCheckAddButtons(0).'</td>';
				}
				else
				{
					$moreattrib = 'style="width:'.$head['width'].';text-align:'.$head['text-align'].'"';
					$out .= '<td class="liste_titre" '.$moreattrib.'>'.$TSearch[$field].'</td>';
				}
			}
			
			$out.='</tr>';
		}
				
		$out.='</thead><tbody>';
		
		if(empty($TField))
		{
			if (!empty($TParam['list']['messageNothing'])) $out .= '<tr class="pair" align="center"><td colspan="'.(count($TParam['title'])+1).'">'.$TParam['list']['messageNothing'].'</td></tr>';
		}
		else
        {
			$var=true;
			$line_number = 0;
			foreach($TField as $fields)
			{
				if($this->in_view($TParam, $line_number))
				{
					$var=!$var;
					$out.='<tr '.$bc[$var].'> <!-- '.$field.' -->';

					foreach ($THeader as $field => $head)
					{
						$moreattrib = 'style="width:'.$head['width'].';text-align:'.$head['text-align'].'"';
						$out.='<td class="'.$field.'" '.$moreattrib.'>'.$fields[$field].'</td>';
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
		
		return $out;
	}

    /**
     * @param $db
     * @param $TField
     * @param array $TParam
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
     * @param $sql
     * @param $TParam
     * @return string
     */
    private function order_by($sql, &$TParam)
    {
		global $db;
		
		if (!empty($TParam['sortfield']))
		{
			if(strpos($sql,'LIMIT ') !== false) list($sql, $sqlLIMIT) = explode('LIMIT ', $sql);
			
			$sql .= $db->order($TParam['sortfield'], $TParam['sortorder']);
			
			if (!empty($sqlLIMIT)) $sql .= ' LIMIT '.$sqlLIMIT;
		}
		
		return $sql;
	}

    /**
     * @param $THeader
     * @param $TField
     * @param $TParam
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
			$this->set_line($TField, $TParam, $row);
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
		if(!empty($TParam['allow-field-select']))
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
					'more'=>''
				);
			}
		}
		
		if(!empty($selectedfields))
		{
			$THeader['selectedfields']['label']='<div style="float:right">'.$selectedfields.'</div>';
		}
		
		return $THeader;
	}

    /**
     * @param $TParam
     * @param $line_number
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
     * @param $TField
     * @param $TParam
     * @param $currentLine
     */
    private function set_line(&$TField, &$TParam, $currentLine)
    {
        global $conf;

        $line_number = count($TField);

        if($this->in_view($TParam,$line_number))
        {
			$this->totalRowToShow++;
            $row=array(); $trans = array();
            foreach($currentLine as $field=>$value)
            {
                if(is_object($value))
                {
                    if(get_class($value)=='stdClass') {$value=print_r($value, true);}
                    else $value=(string) $value;
                }

                $trans['@'.$field.'@'] = $value;

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
                        $strToEval = 'return '.strtr( $TParam['eval'][$field] ,  array_merge( $trans, array('@val@'=>$row[$field])  )).';';
                        $row[$field] = eval($strToEval);
                    }

                    if(isset($TParam['type'][$field]) && !isset($TParam['eval'][$field]))
                    {
                        if($TParam['type'][$field]=='date' || $TParam['type'][$field]=='datetime' )
                        {
                            if($row[$field] != '0000-00-00 00:00:00' && $row[$field] != '1000-01-01 00:00:00' && $row[$field] != '0000-00-00' && !empty($row[$field]))
                            {
                                if($TParam['type'][$field]=='datetime')$row[$field] = dol_print_date(strtotime($row[$field]),'dayhoursec');
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
                        if($TParam['type'][$field]=='integer') { $row[$field] = '<div align="right">'.(int)$row[$field].'</div>'; }
                    }

                    if(isset($TParam['link'][$field]))
                    {
                        if(empty($row[$field]) && $row[$field]!==0 && $row[$field]!=='0')$row[$field]='(vide)';
                        $row[$field]= strtr( $TParam['link'][$field],  array_merge( $trans, array('@val@'=>$row[$field]))) ;
                    }

                    if(isset($TParam['translate'][$field]))
                    {
                        if(isset($TParam['translate'][$field][''])) unset($TParam['translate'][$field]['']);

                        $row[$field] = strtr( $row[$field] , $TParam['translate'][$field]);
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
     * @param $sql
     * @param $TParam
     * @return string
     */
    private function limitSQL($sql, &$TParam)
    {
		if(!empty($TParam['limit']['global']) && strpos($sql,'LIMIT ')===false )
		{
			$sql.=' LIMIT '.(int) $TParam['limit']['global'];
		}
		
		return $sql;
	}

    /**
     * @param $THeader
     * @param $TField
     * @param $TParam
     * @param $sql
     */
    private function parse_sql(&$THeader, &$TField, &$TParam, $sql)
    {
		$this->sql = $this->limitSQL($sql, $TParam);
		
		$this->TTotalTmp=array();
		$this->THideFlip = array_flip($TParam['hide']);
		
		$res = $this->db->query($this->sql);
		if($res!==false)
		{
			$this->totalRow = $this->db->num_rows($res);
			dol_syslog(get_class($this)."::parse_sql id=".$this->id." sql=".$this->sql, LOG_DEBUG);
			
			while($currentLine = $this->db->fetch_object($res))
            {
				$this->set_line($TField, $TParam, $currentLine);
			}
		}
		else
        {
			dol_syslog(get_class($this)."::parse_sql id=".$this->id." sql=".$this->sql, LOG_ERR);
		}
	}	
}
