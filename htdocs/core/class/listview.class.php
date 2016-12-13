<?php
/*
 
 Copyright (C) 2013-2015 ATM Consulting <support@atm-consulting.fr>

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

class Listview {
	
	function __construct(&$db, $id ) {
			
		$this->db = &$db;
			
		$this->id = $id;
		
		$this->TTotalTmp=array();
		
		$this->sql = '';

	}
	private function init(&$TParam) {
		
		global $conf, $langs;
		
		if(!isset($TParam['hide']))$TParam['hide']=array();
		if(!isset($TParam['link']))$TParam['link']=array();
		if(!isset($TParam['type']))$TParam['type']=array();
		if(!isset($TParam['orderby']['noOrder']))$TParam['orderby']['noOrder']=array();
		if(!isset($TParam['no-select'])) $TParam['no-select'] = 1;
		
		if(!isset($TParam['liste']))$TParam['liste']=array();
		$TParam['liste'] = array_merge(array(
			'messageNothing'=>"Il n'y a aucun élément à afficher."
			,'picto_precedent'=>'&lt;'
			,'picto_suivant'=>'&gt;'
			,'order_down'=>img_down()
			,'order_up'=>img_up()
			,'noheader'=>0
			,'useBottomPagination'=>0
			,'image'=>''
			,'titre'=>'Liste'
			,'orderDown'=>''
			,'orderUp'=>''
			,'id'=>$this->id
			,'picto_search'=>img_picto('Search', 'search.png')
			,'head_search'=>''
			,'export'=>array()
			,'view_type'=>''
		),$TParam['liste']);
		
		if(empty($TParam['limit']))$TParam['limit']=array();
		if(!empty($_REQUEST['TListTBS'][$this->id]['page'])) $TParam['limit']['page'] = $_REQUEST['TListTBS'][$this->id]['page'];
		
		$TParam['limit']=array_merge(array('page'=>1, 'nbLine'=>$conf->liste_limit, 'global'=>0), $TParam['limit']);
		
		if(!empty($_REQUEST['TListTBS'][$this->id]['orderBy'])) {
			$TParam['orderBy'] = $_REQUEST['TListTBS'][$this->id]['orderBy']; 
		}
		
	//	print_r($TParam);
	}
	private function getSearchNull($key, &$TParam) {
		return !empty($TParam['search'][$key]['allow_is_null']);
	} 
	private function getSearchKey($key, &$TParam) {
		
		$TPrefixe=array();
		if(!empty($TParam['search'][$key]['table'])) {
			if (!is_array($TParam['search'][$key]['table'])) $TParam['search'][$key]['table'] = array($TParam['search'][$key]['table']);
			
			foreach ($TParam['search'][$key]['table'] as $prefix_table) {
				$TPrefixe[] = '`'.$prefix_table.'`.'; 
			}
		}
		
		$TKey=array();
		if(!empty($TParam['search'][$key]['field'])) {
			if (!is_array($TParam['search'][$key]['field'])) $TParam['search'][$key]['field'] = array($TParam['search'][$key]['field']);
			
			foreach ($TParam['search'][$key]['field'] as $i => $field) {
				$prefixe = !empty($TPrefixe[$i]) ? $TPrefixe[$i] : $TPrefixe[0];
				$TKey[] = $prefixe.'`'. $field .'`';
			}
		} else {
			$TKey[] =$TPrefixe[0].'`'. strtr($key,';','*').'`';
		}
		
		return $TKey;
	} 
	private function getSearchValue($value) {
		$value = strtr(trim($value),';','*');	
			
		return $value;
	}
	
	private function dateToSQLDate($date) {
		
		list($dd,$mm,$aaaa) = explode('/', substr($date,0,10));
		
		$value = date('Y-m-d', mktime(0,0,0,$mm,$dd,$aaaa));
		
		return $value;
	}
	
	private function addSqlFromTypeDate(&$TSQLMore, &$value, $sKey, $sBindKey)
	{
		if(is_array($value))
		{
			
			unset($this->TBind[$sBindKey]);
			// Si le type de "recherche" est "calendars" on a 2 champs de transmis, [début et fin] ou [que début] ou [que fin] => un BETWEEN Sql serait utile que dans le 1er cas 
			// donc l'utilisation des opérateur >= et <= permettent un fonctionnement générique
			$TSQLDate=array();
			if(!empty($value['deb']))
			{
				$valueDeb = $this->dateToSQLDate($value['deb']);
				
				if(isset($this->TBind[$sBindKey.'_start'])) // TODO can't use this in query case
				{
					$this->TBind[$sBindKey.'_start'] = $valueDeb;
				}
				
				$TSQLDate[]=$sKey." >= '".$valueDeb." 00:00:00'" ;
				
			}
			
			if(!empty($value['fin']))
			{
				$valueFin = $this->dateToSQLDate($value['fin']);
				if(isset($this->TBind[$sBindKey.'_end'])) { // TODO can't use this in query case
					$this->TBind[$sBindKey.'_end'] = $valueFin;
				}
				
				$TSQLDate[]=$sKey." <= '".$valueFin." 23:59:59'" ;	
				
			}
			
			if(!empty($TSQLDate)) $TSQLMore[] = implode(' AND ', $TSQLDate);
		}
		else
		{
			// Sinon je communique une date directement au format d/m/Y et la méthode du dessous reformat en Y-m-d
			$value = $this->dateToSQLDate($value);
			if(isset($this->TBind[$sBindKey]))
			{
				$this->TBind[$sBindKey] = $value;
			}
			else
			{
				// Le % en fin de chaine permet de trouver un resultat si le contenu est au format Y-m-d H:i:s et non en Y-m-d
				$TSQLMore[]=$sKey." LIKE '".$value."%'" ;
			}
		}
	}
	
	private function addSqlFromOther(&$TSQLMore, &$value, &$TParam, $sKey, $sBindKey, $key)
	{
	
		$value = $this->getSearchValue($value);
		
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
		
	
	}

	private function search($sql,&$TParam) {
	
		if(!empty($_REQUEST['TListTBS'][$this->id]['search'])) {
			$sqlGROUPBY='';
			if(strpos($sql,'GROUP BY')!==false) { //TODO regex
				list($sql, $sqlGROUPBY) = explode('GROUP BY', $sql);
			}
			
			if(strpos($sql,'WHERE ')===false)$sql.=' WHERE 1 '; //TODO regex
			
			foreach($_REQUEST['TListTBS'][$this->id]['search'] as $key=>$value)
			{
				$TsKey = $this->getSearchKey($key, $TParam);
				$TsBindKey = $this->getTsBindKey($TsKey);
				
				//if (!empty($value)) var_dump($TsKey, $TsBindKey, '==================================');
				$TSQLMore = array();
				
				$allow_is_null = $this->getSearchNull($key,$TParam);
				$search_on_null = false; //TODO useless
				
				foreach ($TsKey as $i => &$sKey)
				{
					//if (!empty($value)) var_dump($sKey);
					$sBindKey = $TsBindKey[$i];
					
					if($allow_is_null && !empty($_REQUEST['TListTBS'][$this->id]['search_on_null'][$key]))
					{
						$this->TBind[$sBindKey.'_null'] = $sKey.' IS NULL ';
						$TSQLMore[] = $sKey.' IS NULL ';
						$search_on_null = true;
						
						if(isset($this->TBind[$sBindKey])) $this->TBind[$sBindKey]= '';
						$value = '';
					}
					elseif($allow_is_null)
					{
						$this->TBind[$sBindKey.'_null'] =0; // $sKey.' IS NOT NULL ';
						//$TSQLMore[] =  $sKey.' IS NOT NULL ';
					}
					
					if($value!='') { // pas empty car biensûr le statut = 0 existe dans de nombreux cas
						
						if(isset($TParam['type'][$key]) && ($TParam['type'][$key]==='date' || $TParam['type'][$key]==='datetime'))
						{
							$this->addSqlFromTypeDate($TSQLMore, $value, $sKey, $sBindKey);
						}
						else
						{
							$this->addSqlFromOther($TSQLMore, $value, $TParam, $sKey, $sBindKey, $key);
						}
					}
				}

				if(!isset($this->TBind[$sBindKey]) && !empty($TSQLMore))
				{
					$sql.=' AND ( '.implode(' OR ',$TSQLMore).' ) ';
				}
				
			}
			
			if($sqlGROUPBY!='')	$sql.=' GROUP BY '.$sqlGROUPBY;
			
		}

		
		return $sql;
	}

	public function render($sql,$TParam=array()) {

		$THeader=array();
		$TField=array();	
		
		$this->init($TParam);
		
		$sql = $this->search($sql,$TParam);
		$sql = $this->order_by($sql, $TParam);		
		
		$this->parse_sql($THeader, $TField, $TParam, $sql);	
		
		list($TTotal, $TTotalGroup)=$this->get_total($TField, $TParam);
		
		return $this->renderList($THeader, $TField,$TTotal,$TTotalGroup, $TParam);	
		
	}

	private function setSearch(&$THeader, &$TParam) {
		global $langs;
		
		if(empty($TParam['search'])) return array();
		
		$TSearch=array();
		$form=new TFormCore;
		$form->strict_string_compare = true;
		
		$nb_search_in_bar = 0;
		
		if(!empty($TParam['search'])) {
			foreach($THeader as $key=>$libelle) { // init
				if(empty($TSearch[$key]))$TSearch[$key]='';
			}
		}		
		foreach($TParam['search'] as $key=>$param_search) {
			
		
			$value = isset($_REQUEST['TListTBS'][$this->id]['search'][$key]) ? $_REQUEST['TListTBS'][$this->id]['search'][$key] : '';
			
			$typeRecherche = (is_array($param_search) && isset($param_search['recherche'])) ? $param_search['recherche'] : $param_search;  
			
			if(is_array($typeRecherche)) {
				$typeRecherche = array(''=>' ') + $typeRecherche;
				$fsearch=$form->combo('','TListTBS['.$this->id.'][search]['.$key.']', $typeRecherche,$value,0,'',' listviewtbs="combo" init-value="'.$value.'" ');
			}
			else if($typeRecherche==='calendar') {
				$fsearch=$form->calendrier('','TListTBS['.$this->id.'][search]['.$key.']',$value,10,10,' listviewtbs="calendar" ');	
			}
			else if($typeRecherche==='calendars') {
				$fsearch=$form->calendrier('','TListTBS['.$this->id.'][search]['.$key.'][deb]',isset($value['deb'])?$value['deb']:'',10,10,' listviewtbs="calendars" ')
					.' '.$form->calendrier('','TListTBS['.$this->id.'][search]['.$key.'][fin]',isset($value['fin'])?$value['fin']:'',10,10,' listviewtbs="calendars" ');	
			}
			else if(is_string($typeRecherche)) {
				$fsearch=$TParam['search'][$key];	
			}
			else {
				$fsearch=$form->texte('','TListTBS['.$this->id.'][search]['.$key.']',$value,15,255,' listviewtbs="input" ');	
			}

			if(!empty($param_search['allow_is_null'])) {
				$valueNull = isset($_REQUEST['TListTBS'][$this->id]['search_on_null'][$key]) ? 1 : 0;
				$fsearch.=' '.$form->checkbox1('', 'TListTBS['.$this->id.'][search_on_null]['.$key.']',1, $valueNull,' onclick=" if($(this).is(\':checked\')){ $(this).prev().val(\'\'); }" ').img_help(1, $langs->trans('SearchOnNUllValue'));
			}
			

			if(!empty($THeader[$key]) || $this->getViewType($TParam) == 'chart') {
				$TSearch[$key] = $fsearch;
				$nb_search_in_bar++;
			}
			else {
				
				$libelle = !empty($TParam['title'][$key]) ? $TParam['title'][$key] : $key ;
				$TParam['liste']['head_search'].='<div>'.$libelle.' '.$fsearch.'</div>';	
			}
				
		}
		
		$search_button = ' <a href="#" onclick="TListTBS_submitSearch(this);" class="list-search-link">'.$TParam['liste']['picto_search'].'</a>';

		if(!empty($TParam['liste']['head_search'])) {
			$TParam['liste']['head_search'].='<div align="right">'.$langs->trans('Search').' '.$search_button.'</div>';
		}
		
		if($nb_search_in_bar>0) {
			end($TSearch);
			list($key,$v) = each($TSearch);
			$TSearch[$key].=$search_button;
		}
		else{
			$TSearch=array();
		}
		
		return $TSearch;
	}

	/*
	 * Function analysant et totalisant une colonne
	 * Supporté : sum, average
	 */
	private function get_total(&$TField, &$TParam) {
		$TTotal=$TTotalGroup=array();	
		
		if(!empty($TParam['math']) && !empty($TField[0])) {
			
			foreach($TField[0] as $field=>$value) {
				$TTotal[$field]='';	
				$TTotalGroup[$field] = '';
			}
		
			foreach($TParam['math'] as $field=>$typeMath){

				if(is_array($typeMath)) {
					$targetField = $typeMath[1];
					$typeMath = $typeMath[0];
				}
				else {
					$targetField = $field;
				}

				if($typeMath == 'groupsum') {
					$TTotalGroup[$field] = array('target'=>$targetField, 'values'=> $this->TTotalTmp['@groupsum'][$targetField]);
					
				}
				else if($typeMath=='average') {
					$TTotal[$field]=array_sum($this->TTotalTmp[$targetField]) / count($this->TTotalTmp[$targetField]);
				}
				elseif($typeMath=='count') {
					$TTotal[$field]=count($this->TTotalTmp[$targetField]);
				}
				else {
					$TTotal[$field]=array_sum($this->TTotalTmp[$targetField]);	
				}
								
			}
			
		
		}
		
		return array($TTotal,$TTotalGroup);
	}

	private function getJS(&$TParam) {
		$javaScript = '<script language="javascript">
		if(typeof(TListview_include)=="undefined") {
			document.write("<script type=\"text/javascript\" src=\"'.DOL_URL_ROOT.'/core/js/list.view.js\"></scr");
	  		document.write("ipt>");
		}
		</script>';

		return $javaScript;
	}

	private function setExport(&$TParam,$TField,$THeader) {
		global $langs;
		
		$Tab=array();
		if(!empty($TParam['export'])) {
			$token = GETPOST('token');
			if(empty($token)) $token = md5($this->id.time().rand(1,9999));

			$_SESSION['token_list_'.$token] = gzdeflate( serialize( array(
				'title'=>$this->title
				,'sql'=>$this->sql
				,'TBind'=>$this->TBind
				,'TChamps'=>$TField
				,'TEntete'=>$THeader
			) ) );

			foreach($TParam['export'] as $mode_export) {
				
				$Tab[] = array(
						'label'=>$langs->trans('Export'.$mode_export)
						,'url'=>dol_buildpath('/abricot/downlist.php',1)
						,'mode'=>$mode_export
						,'token'=>$token
						,'session_name'=>session_name()
				);
				
			}
			
		}
		
		
		return $Tab;
	}

	private function addTotalGroup($TField,$TTotalGroup) {
		global $langs;
		
		$Tab=array();
		
		$proto_total_line = array();
		
		$tagbase = $old_tagbase = null;
		
		$addGroupLine = false;
		
		foreach($TField as $k=>&$line) {
				
			if(empty($proto_total_line)) {
				foreach($line as $field=>$value) {
					$proto_total_line[$field] = '';
				}
				$group_line = $proto_total_line;	
			}
			
			$addGroupLine = false;
			
			$tagbase = '';
			foreach($line as $field=>$value) {
				
				if(!empty($TTotalGroup[$field])) {
					$tagbase.=$value.'|';
					$group_line[$field] = '<div style="text-align:right; font-weight:bold; color:#552266;">'.(empty($value) ? $langs->trans('Empty') : $value ).' : </div>';
					$group_line[$TTotalGroup[$field]['target']] = '<div style="text-align:right; font-weight:bold; color:#552266;">'.price($TTotalGroup[$field]['values'][$value]).'</div>';
					$addGroupLine = true;
				}
				
			}
			
			if(!is_null($old_tagbase) && $old_tagbase!=$tagbase && $addGroupLine) {
			//	var_dump(array($k,$tagbase,$old_tagbase,$empty_line));
				$Tab[] = $previous_group_line;
			}
			
			$old_tagbase = $tagbase;
			$previous_group_line = $group_line;
			$group_line = $proto_total_line;
			
			$Tab[] = $line;
			
			
			
		}
		if($addGroupLine) {
			$Tab[] = $previous_group_line;
		}
		
		
		return $Tab;
	}
	
	private function renderList(&$THeader, &$TField, &$TTotal,&$TTotalGroup, &$TParam) {
			
		$javaScript = $this->getJS($TParam);
		
		$TPagination=array(
			'pagination'=>array('pageSize'=>$TParam['limit']['nbLine'], 'pageNum'=>$TParam['limit']['page'], 'blockName'=>'champs', 'totalNB'=>count($TField))
		);
		
		$TSearch = $this->setSearch($THeader, $TParam);
		$TExport=$this->setExport($TParam, $TField, $THeader);
		$TField = $this->addTotalGroup($TField,$TTotalGroup);
		
		var_dump(
			array(
				'entete'=>$THeader
				,'champs'=>$TField
				,'recherche'=>$TSearch
				,'total'=>$TTotal
				,'export'=>$TExport
			)
			, array(
				'liste'=>array_merge(array('haveExport'=>count($TExport), 'id'=>$this->id, 'nb_columns'=>count($THeader) ,'totalNB'=>count($TField), 'nbSearch'=>count($TSearch), 'haveTotal'=>(int)!empty($TTotal), 'havePage'=>(int)!empty($TPagination) ), $TParam['liste'])
			)
			, $TPagination
			
		);
		
		$javaScript;
	}
	
	public function renderArray(&$db,$TField, $TParam=array()) {
		$this->typeRender = 'array';
		// on conserve db pour le traitement ultérieur des subQuery
		$THeader=array();
		$TField=array();	
		
		$this->init($TParam);
		
		$this->parse_array($THeader, $TField, $TParam,$TField);
		list($TTotal, $TTotalGroup)=$this->get_total($TField, $TParam);
		
		$this->renderList($THeader, $TField,$TTotal,$TTotalGroup, $TParam);	
		
	}

	private function order_by($sql, &$TParam) {
		$first = true;	
		//	print_r($TParam['orderBy']);
		if(!empty($TParam['orderBy'])) {
			
			if(strpos($sql,'LIMIT ')!==false) { //TODO regex
				list($sql, $sqlLIMIT) = explode('LIMIT ', $sql);
			}
			
			$sql.=' ORDER BY '; 
			foreach($TParam['orderBy'] as $field=>$order) {
				if(!$first) $sql.=',';
				
				if($order=='DESC')$TParam['liste']['orderDown'] = $field;
				else $TParam['liste']['orderUp'] = $field;
				
				if(strpos($field,'.')===false)	$sql.='`'.$field.'` '.$order;
				else $sql.=$field.' '.$order;
				
				$first=false;
			}
			
			if(!empty($sqlLIMIT))$sql.=' LIMIT '.$sqlLIMIT;
			
		}
		
		return $sql;
	}
	
	private function parse_array(&$THeader, &$TField, &$TParam, $TField) {
		$first=true;
		
		 $this->THideFlip = array_flip($TParam['hide']);
		$this->TTotalTmp=array();
		
		if(empty($TField)) return false;
		
		foreach($TField as $row) {
			if($first) {
				$this->init_entete($THeader, $TParam, $row);
				$first=false;
			}	
			
			$this->set_line($TField, $TParam, $row);
		}		

	}
	
	private function init_entete(&$THeader, &$TParam, $currentLine) {
		
		$TField=$TFieldVisibility=array();
		
		foreach ($currentLine as $field => $value) {
			$TField[$field]=true;
		}
		
		global $user;
		
		$contextpage=md5($_SERVER['PHP_SELF']);
		if((float)DOL_VERSION>=4.0 && empty($TParam['no-select'])) {
			
			dol_include_once('/core/class/html.form.class.php');
			
			global $db,$conf,$user;
			$form=new Form($db);
				
			$selectedfields = GETPOST('TListTBS_'.$this->id.'_selectedfields');
			
			if(!empty($selectedfields)) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				$tabparam['MAIN_SELECTEDFIELDS_'.$contextpage]=$selectedfields;
	    		$result=dol_set_user_param($db, $conf, $user, $tabparam);
			}
			
			$tmpvar='MAIN_SELECTEDFIELDS_'.$contextpage;
			if (! empty($user->conf->$tmpvar)) {
				$tmparray=explode(',', $user->conf->$tmpvar);
				$TParam['hide']=array();
		        foreach($TField as $field=>$dummy)
		        {
		          	$libelle = isset($TParam['title'][$field]) ? $TParam['title'][$field] : $field;

					if(!in_array($field,$tmparray)) {
				  		$TParam['hide'][] = $field;
						$visible = 0;
				  	}
					else{
						$visible = 1;
					}
		            
					$TFieldVisibility[$field]=array(
						'label'=>$libelle
						,'checked'=>$visible
					);
					
					
		        }
			}
			else{
				foreach($TField as $field=>$dummy)
		        {
		        	$libelle = isset($TParam['title'][$field]) ? $TParam['title'][$field] : $field;
					$visible = (!in_array($field,$TParam['hide'])) ? 1 : 0;	
					$TFieldVisibility[$field]=array(
						'label'=>$libelle
						,'checked'=>$visible
					);
				}
			}	

			$selectedfields=$form->multiSelectArrayWithCheckbox('TListTBS_'.$this->id.'_selectedfields', $TFieldVisibility, $contextpage);	// This also change content of $arrayfields_0
			
		}
		
		foreach ($currentLine as $field => $value) {
			$libelle = isset($TParam['title'][$field]) ? $TParam['title'][$field] : $field;
			$visible = (!in_array($field,$TParam['hide'])) ? 1 : 0;	
			
			if($visible) {
				$lastfield = $field;
				$THeader[$field] = array(
					'libelle'=>$libelle
					,'order'=>((in_array($field, $TParam['orderby']['noOrder']) || $this->typeRender != 'sql') ? 0 : 1)
					,'width'=>(!empty($TParam['size']['width'][$field]) ? $TParam['size']['width'][$field] : 'auto')
					,'text-align'=>(!empty($TParam['position']['text-align'][$field]) ? $TParam['position']['text-align'][$field] : 'auto')
					,'more'=>''
				);
				  
			}
		}
		
		if(!empty($selectedfields) && !empty($lastfield)) {
			$THeader[$lastfield]['more']='<div style="float:right">'.$selectedfields.'</div>';
		}
		
		
	}
	
	private function in_view(&$TParam, $line_number) {
		global $conf;

		if(!empty($_REQUEST['get-all-for-export'])) return true; // doit être dans la vue

		$page_number = !empty($TParam['limit']['page']) ? $TParam['limit']['page'] : 1;
		$line_per_page = !empty($TParam['limit']['nbLine']) ? $TParam['limit']['nbLine'] : $conf->liste_limit;
		
		$start = ($page_number-1) * $line_per_page;
		$end = ($page_number* $line_per_page) -1;
		
		if($line_number>=$start && $line_number<=$end) return true;
		else return false;
	}
	
	private function set_line(&$TField, &$TParam, $currentLine) {
		
			global $conf;
		
			$line_number = count($TField);
			
			if($this->in_view($TParam,$line_number)) {
				
				$row=array(); $trans = array();
				foreach($currentLine as $field=>$value) {
					
					if(is_object($value)) {
						if(get_class($value)=='stdClass') {$value=print_r($value, true);}
						else $value=(string)$value;
					} 
					
					if(isset($TParam['subQuery'][$field])) {
						$dbSub = new TPDOdb; //TODO finish it
						$dbSub->Execute( strtr($TParam['subQuery'][$field], array_merge( $trans, array('@val@'=>$value)  )) );
						$subResult = '';
						while($dbSub->Get_line()) {
							$subResult.= implode(', ',$dbSub->currentLine).'<br />';
						}
						$value=$subResult;
						$dbSub->close();
					}
					
					$trans['@'.$field.'@'] = $value;
					
					if(!empty($TParam['math'][$field])) {
						$float_value = (double)strip_tags($value);
						$this->TTotalTmp[$field][] = $float_value;
					}
					
					if(!in_array($field,$TParam['hide'])) {
						$row[$field]=$value;
						
						if(isset($TParam['eval'][$field]) && in_array($field,array_keys($row))) {
							$strToEval = 'return '.strtr( $TParam['eval'][$field] ,  array_merge( $trans, array('@val@'=>$row[$field])  )).';';
							$row[$field] = eval($strToEval);
						}
						
						if(isset($TParam['type'][$field]) && !isset($TParam['eval'][$field])) {
							if($TParam['type'][$field]=='date' 
								|| $TParam['type'][$field]=='datetime' ) {

								if($row[$field] != '0000-00-00 00:00:00' && $row[$field] != '1000-01-01 00:00:00' && $row[$field] != '0000-00-00' && !empty($row[$field])) {
									if($TParam['type'][$field]=='datetime')$row[$field] = dol_print_date(strtotime($row[$field]),'dayhoursec');
									else $row[$field] = dol_print_date(strtotime($row[$field]),'day');
								} else {
									$row[$field] = '';
								}
							}
							if($TParam['type'][$field]=='hour') { $row[$field] = date('H:i', strtotime($row[$field])); }
							if($TParam['type'][$field]=='money') { $row[$field] = '<div align="right">'.price($row[$field],0,'',1,-1,2).'</div>'; }
							if($TParam['type'][$field]=='number') { $row[$field] = '<div align="right">'.price($row[$field]).'</div>'; }
							if($TParam['type'][$field]=='integer') { $row[$field] = '<div align="right">'.(int)$row[$field].'</div>'; }
						}
	
	                                        if(isset($TParam['link'][$field])) {
	                                                if(empty($row[$field]) && $row[$field]!==0 && $row[$field]!=='0')$row[$field]='(vide)';
	                                                $row[$field]= strtr( $TParam['link'][$field],  array_merge( $trans, array('@val@'=>$row[$field])  )) ;
	                                        }
	                                        
	                                        if(isset($TParam['translate'][$field])) {
							if(isset($TParam['translate'][$field][''])) unset($TParam['translate'][$field]['']);
	                                                $row[$field] = strtr( $row[$field] , $TParam['translate'][$field]);
	                                        }
	
	
					} 
					
					
				} 
			}
			else{
				$row=array(); 

				foreach($currentLine as $field=>&$value) {
					if(!isset($this->THideFlip[$field])) {
						if(isset($TParam['math'][$field]) && !empty($TParam['math'][$field])) {
							$float_value = (double)strip_tags($value);
							$this->TTotalTmp[$field][] = $float_value;
						}
						
						$row[$field] = $value;
					}
				}
			}

			if(!empty($TParam['math'][$field])) {
			foreach($row as $field=>$value) {
				if(!empty($TParam['math'][$field]) && is_array($TParam['math'][$field])) {
						$toField = $TParam['math'][$field][1];
						$float_value = (double)strip_tags($row[$toField]);
						$this->TTotalTmp['@groupsum'][$toField][ $row[$field]  ] +=$float_value;
						
				}
			}
			}
			$TField[] = $row;	
	}
	
	private function limitSQL($sql,&$TParam) {
		
		if(!empty($TParam['limit']['global']) && strpos($sql,'LIMIT ')===false ) {
			
			$sql.=' LIMIT '.(int)$TParam['limit']['global'];
			
		}
		
		return $sql;
	}
	
	private function parse_sql( &$THeader, &$TField,&$TParam, $sql) {
		
		$this->sql = $this->limitSQL($sql, $TParam);
		
		$this->TTotalTmp=array();
		
		$this->THideFlip = array_flip($TParam['hide']);

		$res = $this->db->query($this->sql);
		if($res!==false) {
			
			dol_syslog(get_class($this)."::parse_sql id=".$this->id." sql=".$this->sql, LOG_DEBUG);
			
			$first=true;
			while($currentLine = $this->db->fetch_object($res)) {
				if($first) {
					$this->init_entete($THeader, $TParam, $currentLine);
					$first = false;
				}
				
				$this->set_line($TField, $TParam, $currentLine);
				
			}
			
		}
		else {
			dol_syslog(get_class($this)."::parse_sql id=".$this->id." sql=".$this->sql, LOG_ERR);
		}
		
	}	
}
