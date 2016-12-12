<?php
/* Copyright (C) 2016		ATM Consulting			<support@atm-consulting.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/class/coreobject.inventory.php
 *	\ingroup    core
 *	\brief      File of class to manage all object. Might be replace or merge into commonobject
 */
 
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class CoreObject extends CommonObject {
	
	 
	 /**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct(DoliDB &$db) {
		
		$this->db = &$db;
		
		$this->date_0 = '1001-01-01 00:00:00'; //TODO there is a solution for this ?
	}
	
	private function init() {
		
		$this->id = 0;
		$this->datec = time();
		$this->tms = time();
		
		if(!empty($this->__fields)) {
			foreach ($this->__fields as $field=>$info) {
		
		        if($this->is_date($info)){
					$this->{$field} = time();
		        }
		        elseif($this->is_array($info)){
					$this->{$field} = array();
		        }
		        elseif($this->is_int($info)){
					$this->{$field} = (int)0;
		        }
		        elseif($this->is_float($info)) {
					$this->{$field} = (double)0;
				}
		        else{
					$this->{$field} = '';
		        }
		    }
			
		    $this->to_delete=false;
			
			return true;
		}
		else{
			return false;
		}
			
	}
	
	
	private function is_date(Array &$info){
	
		if(is_array($info)) {
			if(isset($info['type']) && $info['type']=='date') return true;
			else return false;
		}
		else return false;
	}
	
	private function is_array($info) {
		
	  	if(is_array($info)) {
			if(isset($info['type']) && $info['type']=='array') return true;
			else return false;
		}
		else return false;
	}
	
	
	private function is_null($info){
		if(is_array($info)) {
			if(isset($info['type']) && $info['type']=='null') return true;
			else return false;
		}
		else return false;
	}
	
	private function is_int($info){
	
		if(is_array($info)) {
			if(isset($info['type']) && ($info['type']=='int' || $info['type']=='integer' )) return true;
			else return false;
		}
		else return false;
	}
	private function _is_float($info){
		if(is_array($info)) {
			if(isset($info['type']) && $info['type']=='float') return true;
			else return false;
		} else return false;
	}
	
	private function _is_text($info){
	  	if(is_array($info)) {
			if(isset($info['type']) && $info['type']=='text') return true;
			else return false;
		} else return false;
	}
	private function _is_index($info){
	  	if(is_array($info)) {
			if(isset($info['index']) && $info['index']==true) return true;
			else return false;
		} else return false;
	}
		
	private function set_save_query(){
		
		$query=array(
			'rowid'=>$this->id
			,'datec'=>($this->id>0 ? $this->db->jdate($this->datec) : time())
			,'tms'=>time()
		);
		
		foreach ($this->__fields as $field=>$info) {
	
			if($this->_is_date($info)){
				if(empty($this->{$field})){
					$query[$field] = $this->date_0;
				}
				else{
					$query[$field] = $this->db->jdate($this->{$field});
				}
		  	}
		  	else if($this->is_array($info)){
		  		    $query[$field] = serialize($this->{$field});
		  	}
		
		  	else if($this->is_int($info)){
		    	$query[$field] = (int)price2num($this->{$field});
		  	}
		
		  	else if($this->_is_float($info)){
		    	$query[$field] = (double)price2num($this->{$field});
		  	}
		
		  	elseif($this->_is_null($info)) {
		  		$query[$field] = (is_null($this->{$field}) || (empty($this->{$field}) && $this->{$field}!==0 && $this->{$field}!=='0')?null:$this->{$field});
		    }
		    else{
		       $query[$field] = $this->{$field};
		    }
		
	    }
	
		return $query;
	}
		
	
	public function fetch($id, $loadChild = true) {
		
		if(empty($id)) return false;

		$res = $db->query( 'SELECT '.$this->get_field_list().'datec,tms
						FROM '.$this->table_element.'
						WHERE rowid='.$id );
		if($obj = $db->fetch_object($res)) {
				$this->rowid=$id;
			
				$this->set_vars_by_db($db);

				$this->datec=$this->db->idate($obj->datec);
				$this->tms=$this->db->idate($obj->tms);
				
				if($loadChild) $this->loadChild($db);

				$this->run_trigger($db, 'load');

				return $this->id;
		}
		else {
				return false;
		}
		
	}
	public function update() {
		
		
		
	}
	public function create() {
		
		
		
	}
	
	
}
