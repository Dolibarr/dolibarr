<?php
/* EXPERIMENTAL
 * 
 * Copyright (C) 2016		ATM Consulting			<support@atm-consulting.fr>
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
 *	\file       htdocs/core/class/coreobject.class.php
 *	\ingroup    core
 *	\brief      File of class to manage all object. Might be replace or merge into commonobject
 */
 
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class CoreObject extends CommonObject
{
	public $withChild = true;

	/**
	 *  @var Array $_fields Fields to synchronize with Database
	 */
	protected $fields=array();

    /**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct(DoliDB &$db)
    {
        $this->db = $db;
	}

    /**
     * Function to init fields
     *
     * @return bool
     */
	protected function init()
    {
		$this->id = 0;
		$this->datec = 0;
		$this->tms = 0;
		
		if (!empty($this->fields))
		{
			foreach ($this->fields as $field=>$info)
			{
		        if ($this->isDate($info)) $this->{$field} = time();
		        elseif ($this->isArray($info)) $this->{$field} = array();
		        elseif ($this->isInt($info)) $this->{$field} = (int) 0;
		        elseif ($this->isFloat($info)) $this->{$field} = (double) 0;
				else $this->{$field} = '';
		    }

            $this->to_delete=false;
            $this->is_clone=false;
			
			return true;
		}
		else
        {
			return false;
		}
			
	}

    /**
     * Test type of field
     *
     * @param   string  $field  name of field
     * @param   string  $type   type of field to test
     * @return                  value of field or false
     */
    private function checkFieldType($field, $type)
    {
		if (isset($this->fields[$field]) && method_exists($this, 'is_'.$type))
		{
			return $this->{'is_'.$type}($this->fields[$field]);
		}
		else
        {
            return false;
        }
	}

    /**
     *	Get object and children from database
     *
     *	@param      int			$id       		Id of object to load
     * 	@param		bool		$loadChild		used to load children from database
     *	@return     int         				>0 if OK, <0 if KO, 0 if not found
     */
	public function fetch($id, $loadChild = true)
    {
    	$res = $this->fetchCommon($id);
    	if($res>0) {
    		if ($loadChild) $this->fetchChild();
    	}
    	
    	return $res;
	}


    /**
     * Function to instantiate a new child
     *
     * @param   string  $tabName        Table name of child
     * @param   int     $id             If id is given, we try to return his key if exist or load if we try_to_load
     * @param   string  $key            Attribute name of the object id
     * @param   bool    $try_to_load    Force the fetch if an id is given
     * @return                          int
     */
    public function addChild($tabName, $id=0, $key='id', $try_to_load = false)
    {
		if(!empty($id))
		{
			foreach($this->{$tabName} as $k=>&$object)
			{
				if($object->{$key} === $id) return $k;
			}
		}
	
		$k = count($this->{$tabName});
	
		$className = ucfirst($tabName);
		$this->{$tabName}[$k] = new $className($this->db);
		if($id>0 && $key==='id' && $try_to_load)
		{
			$this->{$tabName}[$k]->fetch($id); 
		}

		return $k;
	}


    /**
     * Function to set a child as to delete
     *
     * @param   string  $tabName        Table name of child
     * @param   int     $id             Id of child to set as to delete
     * @param   string  $key            Attribute name of the object id
     * @return                          bool
     */
    public function removeChild($tabName, $id, $key='id')
    {
		foreach ($this->{$tabName} as &$object)
		{
			if ($object->{$key} == $id)
			{
				$object->to_delete = true;
				return true;
			}
		}
		return false;
	}


    /**
     * Function to fetch children objects
     */
    public function fetchChild()
    {
		if($this->withChild && !empty($this->childtables) && !empty($this->fk_element))
		{
			foreach($this->childtables as &$childTable)
			{
                $className = ucfirst($childTable);

                $this->{$className}=array();

                $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.$childTable.' WHERE '.$this->fk_element.' = '.$this->id;
                $res = $this->db->query($sql);

                if($res)
                {
                    while($obj = $this->db->fetch_object($res))
                    {
                        $o=new $className($this->db);
                        $o->fetch($obj->rowid);

                        $this->{$className}[] = $o;
                    }
                }
                else
                {
                    $this->errors[] = $this->db->lasterror();
                }
			}
		}
	}

    /**
     * Function to update children data
     *
     * @param   User    $user   user object
     */
	public function saveChild(User &$user)
    {
		if($this->withChild && !empty($this->childtables) && !empty($this->fk_element))
		{
			foreach($this->childtables as &$childTable)
			{
				$className = ucfirst($childTable);
				if(!empty($this->{$className}))
				{
					foreach($this->{$className} as $i => &$object)
					{
						$object->{$this->fk_element} = $this->id;
						
						$object->update($user);
						if($this->unsetChildDeleted && isset($object->to_delete) && $object->to_delete==true) unset($this->{$className}[$i]);
					}
				}
			}
		}
	}


    /**
     * Function to update object or create or delete if needed
     *
     * @param   User    $user   user object
     * @return                  < 0 if ko, > 0 if ok
     */
    public function update(User &$user)
    {
		if (empty($this->id)) return $this->create($user); // To test, with that, no need to test on high level object, the core decide it, update just needed
        elseif (isset($this->to_delete) && $this->to_delete==true) return $this->delete($user);

        $error = 0;
        $this->db->begin();

        $res = $this->updateCommon($user);
        if ($res)
        {
            $result = $this->call_trigger(strtoupper($this->element). '_UPDATE', $user);
            if ($result < 0) $error++;
            else $this->saveChild($user);
        }
        else
        {
            $error++;
            $this->error = $this->db->lasterror();
            $this->errors[] = $this->error;
        }

        if (empty($error))
        {
            $this->db->commit();
            return $this->id;
        }
        else
        {
            $this->db->rollback();
            return -1;
        }

	}

    /**
     * Function to create object in database
     *
     * @param   User    $user   user object
     * @return                  < 0 if ko, > 0 if ok
     */
    public function create(User &$user)
    {
		if($this->id > 0) return $this->update($user);

        $error = 0;
        $this->db->begin();

        $res = $this->createCommon($user);
		if($res)
		{
			$this->id = $this->db->last_insert_id($this->table_element);

			$result = $this->call_trigger(strtoupper($this->element). '_CREATE', $user);
            if ($result < 0) $error++;
            else $this->saveChild($user);
		}
		else
        {
            $error++;
            $this->error = $this->db->lasterror();
            $this->errors[] = $this->error;
		}

        if (empty($error))
        {
            $this->db->commit();
            return $this->id;
        }
        else
        {
            $this->db->rollback();
            return -1;
        }
	}

    /**
     * Function to delete object in database
     *
     * @param   User    $user   user object
     * @return                  < 0 if ko, > 0 if ok
     */
	public function delete(User &$user)
    {
		if ($this->id <= 0) return 0;

        $error = 0;
        $this->db->begin();

        $result = $this->call_trigger(strtoupper($this->element). '_DELETE', $user);
        if ($result < 0) $error++;

        if (!$error)
        {
            $this->deleteCommon($user);
            if($this->withChild && !empty($this->childtables))
            {
                foreach($this->childtables as &$childTable)
                {
                    $className = ucfirst($childTable);
                    if (!empty($this->{$className}))
                    {
                        foreach($this->{$className} as &$object)
                        {
                            $object->delete($user);
                        }
                    }
                }
            }
        }

        if (empty($error))
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error = $this->db->lasterror();
            $this->errors[] = $this->error;
            $this->db->rollback();
            return -1;
        }
	}


    /**
     * Function to get a formatted date
     *
     * @param   string  $field  Attribute to return
     * @param   string  $format Output date format
     * @return          string
     */
    public function getDate($field, $format='')
    {
		if(empty($this->{$field})) return '';
		else
        {
			return dol_print_date($this->{$field}, $format);
		}
	}

    /**
     * Function to set date in field
     *
     * @param   string  $field  field to set
     * @param   string  $date   formatted date to convert
     * @return                  mixed
     */
    public function setDate($field, $date)
    {
	  	if (empty($date))
	  	{
	  		$this->{$field} = 0;
	  	}
		else
        {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
			$this->{$field} = dol_stringtotime($date);
		}

		return $this->{$field};
	}


    /**
     * Function to update current object
     *
     * @param   array   $Tab    Array of values
     * @return                  int
     */
    public function setValues(&$Tab)
    {
		foreach ($Tab as $key => $value)
		{
			if($this->checkFieldType($key, 'date'))
			{
				$this->setDate($key, $value);
			}
			else if( $this->checkFieldType($key, 'array'))
			{
				$this->{$key} = $value;
			}
			else if( $this->checkFieldType($key, 'float') )
			{
				$this->{$key} = (double) price2num($value);
			}
			else if( $this->checkFieldType($key, 'int') ) {
				$this->{$key} = (int) price2num($value);
			}
			else
            {
				$this->{$key} = $value;
			}
		}

		return 1;
	}

}
